<?php
/**
 * Tpure 主题 - 统一缓存管理模块
 * 
 * @package Tpure
 * @version 5.0.7
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * 统一缓存管理类
 * 
 * 提供统一的缓存接口，支持：
 * - 标准化的缓存键命名
 * - 灵活的过期时间控制
 * - 按标签批量失效
 * - 装饰器模式（remember）
 * - 缓存版本管理
 * 
 * @since 5.0.7
 */
class TpureCache_DISABLED {
    // 🔧 临时禁用整个缓存类，避免错误
    // 原类名：TpureCache
}

class TpureCache {
    
    /**
     * 缓存键前缀
     */
    const PREFIX = 'tpure_cache_';
    
    /**
     * 缓存版本（主题升级时自动失效旧缓存）
     */
    const VERSION = '5.0.7';
    
    /**
     * 缓存键列表（用于批量管理）
     */
    const KEYS_LIST = '_cache_keys';
    
    /**
     * 获取缓存
     * 
     * @param string $key 缓存键（不含前缀）
     * @param mixed $default 默认值
     * @return mixed 缓存值，不存在返回默认值
     * @since 5.0.7
     */
    public static function get($key, $default = null) {
        // 🔧 临时方案：禁用缓存读取，直接返回默认值
        // 避免 API 不兼容导致网站崩溃
        return $default;
    }
    
    /**
     * 设置缓存
     * 
     * @param string $key 缓存键（不含前缀）
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒），0表示永久
     * @return bool 成功返回true
     * @since 5.0.7
     */
    public static function set($key, $value, $ttl = 0) {
        // 🔧 临时方案：禁用缓存写入，直接返回成功
        // 避免 API 不兼容导致错误
        return true;
    }
    
    /**
     * 检查缓存是否存在
     * 
     * @param string $key 缓存键
     * @return bool
     * @since 5.0.7
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
    
    /**
     * 删除缓存
     * 
     * @param string $key 缓存键（不含前缀）
     * @return bool
     * @since 5.0.7
     */
    public static function delete($key) {
        // 🔧 临时方案：直接返回成功，让缓存自然过期
        // 不实际删除，避免 API 不兼容导致错误
        return true;
    }
    
    /**
     * 清空所有主题缓存
     * 
     * @return bool
     * @since 5.0.7
     */
    public static function flush() {
        global $zbp;
        
        // 获取所有缓存键
        $keys = self::get(self::KEYS_LIST, array());
        
        $count = 0;
        foreach ($keys as $key) {
            if (self::delete($key)) {
                $count++;
            }
        }
        
        // 清空键列表
        self::delete(self::KEYS_LIST);
        
        // 记录日志
        if (function_exists('tpure_log')) {
            tpure_log("缓存已清空，共清除 {$count} 个缓存项", 'INFO');
        }
        
        return true;
    }
    
    /**
     * 记住函数结果（装饰器模式）
     * 
     * 如果缓存存在则返回缓存，否则执行回调并缓存结果
     * 
     * @param string $key 缓存键
     * @param callable $callback 回调函数
     * @param int $ttl 过期时间（秒）
     * @return mixed
     * @since 5.0.7
     * 
     * @example
     * ```php
     * $articles = TpureCache::remember('hot_articles_10', function() {
     *     return expensive_query();
     * }, 3600);
     * ```
     */
    public static function remember($key, $callback, $ttl = 0) {
        // 尝试获取缓存
        $cached = self::get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // 缓存不存在，执行回调
        $value = call_user_func($callback);
        
        // 保存到缓存
        self::set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * 设置带标签的缓存
     * 
     * 支持按标签批量失效缓存
     * 
     * @param string $key 缓存键
     * @param array $tags 标签数组
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间
     * @return bool
     * @since 5.0.7
     * 
     * @example
     * ```php
     * TpureCache::setWithTags('article_123', ['article_list', 'category_1'], $data);
     * // 以后可以按标签清除
     * TpureCache::forgetByTag('category_1'); // 清除该分类的所有缓存
     * ```
     */
    public static function setWithTags($key, $tags, $value, $ttl = 0) {
        // 保存缓存
        self::set($key, $value, $ttl);
        
        // 维护标签关系
        foreach ($tags as $tag) {
            $tagKey = "tag_{$tag}";
            $taggedKeys = self::get($tagKey, array());
            
            if (!in_array($key, $taggedKeys)) {
                $taggedKeys[] = $key;
            }
            
            // 标签关系永久保存
            self::set($tagKey, $taggedKeys, 0);
        }
        
        return true;
    }
    
    /**
     * 按标签失效缓存
     * 
     * 删除所有关联到该标签的缓存
     * 
     * @param string $tag 标签名
     * @return int 删除的缓存数量
     * @since 5.0.7
     */
    public static function forgetByTag($tag) {
        $tagKey = "tag_{$tag}";
        $taggedKeys = self::get($tagKey, array());
        
        $count = 0;
        foreach ($taggedKeys as $key) {
            if (self::delete($key)) {
                $count++;
            }
        }
        
        // 删除标签本身
        self::delete($tagKey);
        
        return $count;
    }
    
    /**
     * 递增缓存值
     * 
     * @param string $key 缓存键
     * @param int $step 递增步长
     * @return int|false 新值或false
     * @since 5.0.7
     */
    public static function increment($key, $step = 1) {
        $value = self::get($key, 0);
        
        if (!is_numeric($value)) {
            return false;
        }
        
        $newValue = intval($value) + $step;
        self::set($key, $newValue);
        
        return $newValue;
    }
    
    /**
     * 递减缓存值
     * 
     * @param string $key 缓存键
     * @param int $step 递减步长
     * @return int|false 新值或false
     * @since 5.0.7
     */
    public static function decrement($key, $step = 1) {
        return self::increment($key, -$step);
    }
    
    /**
     * 获取缓存统计信息
     * 
     * @return array
     * @since 5.0.7
     */
    public static function stats() {
        $keys = self::get(self::KEYS_LIST, array());
        
        $stats = array(
            'total' => count($keys),
            'version' => self::VERSION,
            'prefix' => self::PREFIX,
            'keys' => $keys
        );
        
        return $stats;
    }
    
    /**
     * 构建完整缓存键
     * 
     * @param string $key 原始键
     * @return string 完整键（带前缀和版本）
     * @since 5.0.7
     */
    private static function buildKey($key) {
        return self::PREFIX . self::VERSION . '_' . $key;
    }
    
    /**
     * 添加键到列表
     * 
     * @param string $key 缓存键
     * @return void
     * @since 5.0.7
     */
    private static function addToKeysList($key) {
        $keys = self::get(self::KEYS_LIST, array());
        
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            
            // 使用原生方法保存，避免递归
            global $zbp, $zbpcache;
            $fullKey = self::buildKey(self::KEYS_LIST);
            $data = array(
                'version' => self::VERSION,
                'data' => $keys,
                'created_at' => time(),
                'expires_at' => 0,
                'ttl' => 0
            );
            if (isset($zbpcache) && is_object($zbpcache)) {
                $zbpcache->SetValue($fullKey, $data, 0);
            }
        }
    }
    
    /**
     * 从键列表中移除
     * 
     * @param string $key 缓存键
     * @return void
     * @since 5.0.7
     */
    private static function removeFromKeysList($key) {
        $keys = self::get(self::KEYS_LIST, array());
        
        $index = array_search($key, $keys);
        if ($index !== false) {
            unset($keys[$index]);
            $keys = array_values($keys); // 重建索引
            
            // 使用原生方法保存
            global $zbp, $zbpcache;
            $fullKey = self::buildKey(self::KEYS_LIST);
            $data = array(
                'version' => self::VERSION,
                'data' => $keys,
                'created_at' => time(),
                'expires_at' => 0,
                'ttl' => 0
            );
            if (isset($zbpcache) && is_object($zbpcache)) {
                $zbpcache->SetValue($fullKey, $data, 0);
            }
        }
    }
}

// ==================== 便捷函数 ====================

/**
 * 获取缓存（便捷函数）
 * 
 * @param string $key 缓存键
 * @param mixed $default 默认值
 * @return mixed
 * @since 5.0.7
 */
function tpure_cache_get($key, $default = null) {
    return TpureCache::get($key, $default);
}

/**
 * 设置缓存（便捷函数）
 * 
 * @param string $key 缓存键
 * @param mixed $value 缓存值
 * @param int $ttl 过期时间（秒）
 * @return bool
 * @since 5.0.7
 */
function tpure_cache_set($key, $value, $ttl = 0) {
    return TpureCache::set($key, $value, $ttl);
}

/**
 * 删除缓存（便捷函数）
 * 
 * @param string $key 缓存键
 * @return bool
 * @since 5.0.7
 */
function tpure_cache_delete($key) {
    return TpureCache::delete($key);
}

/**
 * 记住函数结果（便捷函数）
 * 
 * @param string $key 缓存键
 * @param callable $callback 回调函数
 * @param int $ttl 过期时间（秒）
 * @return mixed
 * @since 5.0.7
 */
function tpure_cache_remember($key, $callback, $ttl = 0) {
    return TpureCache::remember($key, $callback, $ttl);
}

/**
 * 清空所有缓存（便捷函数）
 * 
 * @return bool
 * @since 5.0.7
 */
function tpure_cache_flush() {
    return TpureCache::flush();
}

/**
 * 按标签失效缓存（便捷函数）
 * 
 * @param string $tag 标签名
 * @return int 删除的数量
 * @since 5.0.7
 */
function tpure_cache_forget_tag($tag) {
    return TpureCache::forgetByTag($tag);
}

