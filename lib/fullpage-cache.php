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

