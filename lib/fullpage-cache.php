<?php
/**
 * Tpure 主题 - 全页面 Redis 缓存管理
 * 
 * 功能：
 * - 清除全页面缓存
 * - 清除指定页面缓存
 * - 批量清除缓存
 * 
 * @package Tpure
 * @version 5.15
 * @since 5.15
 */

/**
 * 清除全页面缓存
 * 
 * 当内容更新时（发布文章、评论等），自动清除相关页面的缓存
 * 
 * @return bool 成功返回 true，失败返回 false
 */
function tpure_clear_fullpage_cache() {
    if (!extension_loaded('redis')) {
        return false;
    }
    
    try {
        // 🔧 创建 Redis 连接（需要使用 scan/del 等高级方法）
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        // 🔑 从 zbpcache 配置读取密码并认证
        global $zbp;
        if (isset($zbp) && isset($zbp->Config('zbpcache')->redis_password)) {
            $password = $zbp->Config('zbpcache')->redis_password;
            if (!empty($password)) {
                $redis->auth($password);
            }
        }
        
        // 🚀 优化：使用 SCAN 而不是 KEYS（生产环境更安全）
        $iterator = null;
        $pattern = 'tpure:fullpage:*';
        $deletedCount = 0;
        
        // 批量删除所有全页面缓存
        while ($keys = $redis->scan($iterator, $pattern, 100)) {
            foreach ($keys as $key) {
                $redis->del($key);
                $deletedCount++;
            }
        }
        
        // 记录日志（如果启用了调试模式）
        if (function_exists('tpure_log') && defined('TPURE_DEBUG') && TPURE_DEBUG) {
            tpure_log("全页面缓存已清除（共 {$deletedCount} 个）", 'INFO');
        }
        
        return true;
        
    } catch (Exception $e) {
        // Redis 连接失败，静默失败
        if (function_exists('tpure_log') && defined('TPURE_DEBUG') && TPURE_DEBUG) {
            tpure_log("清除全页面缓存失败：" . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * 清除指定 URL 的缓存
 * 
 * @param string $url 页面 URL（可以是相对路径或完整 URL）
 * @return bool
 */
function tpure_clear_page_cache($url) {
    if (!extension_loaded('redis')) {
        return false;
    }
    
    try {
        // 🔧 创建 Redis 连接（需要使用高级方法）
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        // 🔑 从 zbpcache 配置读取密码并认证
        global $zbp;
        if (isset($zbp) && isset($zbp->Config('zbpcache')->redis_password)) {
            $password = $zbp->Config('zbpcache')->redis_password;
            if (!empty($password)) {
                $redis->auth($password);
            }
        }
        
        // 提取 URI 路径
        $uri = parse_url($url, PHP_URL_PATH);
        if (!$uri) {
            $uri = $url;
        }
        
        // 构建缓存键
        $cacheKey = 'tpure:fullpage:' . md5($uri);
        
        // 删除缓存
        $result = $redis->del($cacheKey);
        
        return $result > 0;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 🆕 全页面缓存写入函数
 * 
 * 在页面渲染完成后，将HTML内容写入Redis缓存
 * 
 * @param string $template 模板对象
 * @return string 返回处理后的模板
 */
function tpure_fullpage_cache_handler(&$template) {
    global $zbp;
    
    // 检查是否启用全页面缓存
    if (($zbp->Config('tpure')->CacheFullPageOn ?? 'OFF') !== 'ON') {
        return $template;
    }
    
    // 检查Redis扩展
    if (!extension_loaded('redis')) {
        return $template;
    }
    
    // 只对游客启用缓存（登录用户不缓存）
    if ($zbp->user && $zbp->user->ID > 0) {
        return $template;
    }
    
    // 只缓存GET请求
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return $template;
    }
    
    // 获取当前请求URI
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // 排除特定页面（管理后台、API等）
    $excludePatterns = array('/zb_system/', '/zb_users/plugin/', '?', '&');
    foreach ($excludePatterns as $pattern) {
        if (strpos($requestUri, $pattern) !== false) {
            return $template;
        }
    }
    
    try {
        // 连接Redis
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 2);
        
        // 认证
        $password = '';
        $configCacheFile = $zbp->usersdir . 'cache/config_zbpcache.php';
        if (file_exists($configCacheFile)) {
            $configData = @include $configCacheFile;
            if (is_array($configData) && isset($configData['redis_password'])) {
                $password = $configData['redis_password'];
            }
        }
        
        if ($password) {
            $redis->auth($password);
        }
        
        // 构建缓存键
        $cacheKey = 'tpure:fullpage:' . md5($requestUri);
        
        // 首先尝试读取缓存
        $cachedContent = $redis->get($cacheKey);
        
        if ($cachedContent !== false) {
            // 缓存命中，添加响应头并返回
            header('X-Cache: HIT');
            header('X-Cache-Key: ' . $cacheKey);
            echo $cachedContent;
            exit; // 直接输出缓存，停止后续渲染
        }
        
        // 缓存未命中，继续正常渲染
        header('X-Cache: MISS');
        header('X-Cache-Key: ' . $cacheKey);
        
        // 注册输出缓冲区处理函数，在页面输出前写入缓存
        ob_start(function($content) use ($redis, $cacheKey, $requestUri) {
            // 只缓存成功的HTML响应
            if (strpos($content, '<!DOCTYPE') !== false || strpos($content, '<html') !== false) {
                // 判断缓存时间
                $ttl = 3600; // 默认1小时
                
                // 首页缓存时间更短（5分钟）
                if ($requestUri === '/' || $requestUri === '/index.php') {
                    $ttl = 300;
                }
                
                // 写入缓存
                $redis->setex($cacheKey, $ttl, $content);
                
                // 调试日志
                if (defined('TPURE_DEBUG') && TPURE_DEBUG && function_exists('tpure_log')) {
                    tpure_log("全页面缓存已写入：{$cacheKey}（过期：{$ttl}秒）", 'INFO');
                }
            }
            
            return $content;
        });
        
        $redis->close();
        
    } catch (Exception $e) {
        // Redis错误，静默失败，继续正常渲染
        if (defined('TPURE_DEBUG') && TPURE_DEBUG && function_exists('tpure_log')) {
            tpure_log("全页面缓存写入失败：" . $e->getMessage(), 'ERROR');
        }
    }
    
    return $template;
}

/**
 * 获取全页面缓存统计信息
 * 
 * @return array ['total' => 总数, 'size' => 总大小（字节）]
 */
function tpure_get_fullpage_cache_stats() {
    $stats = array(
        'total' => 0,
        'size' => 0,
        'keys' => array()
    );
    
    if (!extension_loaded('redis')) {
        return $stats;
    }
    
    try {
        // 🔧 创建 Redis 连接（需要使用 scan/ttl 等高级方法）
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        // 🔑 从 zbpcache 配置读取密码并认证
        global $zbp;
        if (isset($zbp) && isset($zbp->Config('zbpcache')->redis_password)) {
            $password = $zbp->Config('zbpcache')->redis_password;
            if (!empty($password)) {
                $redis->auth($password);
            }
        }
        
        $iterator = null;
        $pattern = 'tpure:fullpage:*';
        
        while ($keys = $redis->scan($iterator, $pattern, 100)) {
            foreach ($keys as $key) {
                $stats['total']++;
                $value = $redis->get($key);
                if ($value !== false) {
                    $size = strlen($value);
                    $stats['size'] += $size;
                    $stats['keys'][] = array(
                        'key' => $key,
                        'size' => $size,
                        'ttl' => $redis->ttl($key)
                    );
                }
            }
        }
        
        return $stats;
        
    } catch (Exception $e) {
        return $stats;
    }
}

