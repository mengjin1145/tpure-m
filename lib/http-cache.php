<?php
/**
 * Tpure 主题 - HTTP缓存优化模块
 * 
 * 功能：
 * - 浏览器缓存控制（Cache-Control、ETag）
 * - Gzip压缩输出
 * - 静态资源缓存策略
 * - 动态内容协商缓存
 * 
 * @package Tpure
 * @version 5.0.7
 * @since 5.0.7
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * HTTP缓存管理类
 * 
 * @since 5.0.7
 */
class TpureHttpCache {
    
    /**
     * 缓存时间配置（秒）
     */
    const CACHE_TIME_STATIC = 2592000;   // 静态资源: 30天
    const CACHE_TIME_IMAGE = 2592000;    // 图片: 30天
    const CACHE_TIME_CSS_JS = 604800;    // CSS/JS: 7天
    const CACHE_TIME_HTML = 3600;        // HTML: 1小时
    const CACHE_TIME_FEED = 1800;        // RSS: 30分钟
    const CACHE_TIME_API = 300;          // API: 5分钟
    
    /**
     * 是否已发送缓存头
     */
    private static $headersSent = false;
    
    /**
     * 初始化HTTP缓存
     * 
     * @return void
     */
    public static function init() {
        // 启用Gzip压缩
        self::enableGzip();
        
        // 根据请求类型设置缓存策略
        add_action('Filter_Plugin_ViewList_Core', array(__CLASS__, 'setCacheHeaders'));
    }
    
    /**
     * 设置缓存头
     * 
     * @param string $contentType 内容类型
     * @param int $maxAge 最大缓存时间（秒）
     * @param bool $public 是否公开缓存
     * @return void
     */
    public static function setCacheHeaders($contentType = 'text/html', $maxAge = null, $public = true) {
        global $zbp;
        
        // 防止重复发送
        if (self::$headersSent || headers_sent()) {
            return;
        }
        
        // 🆕 后台页面不缓存（优先检查）
        if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
            self::setNoCache();
            return;
        }
        
        // 🆕 已登录的管理用户不缓存（Level 1-4: 管理员、编辑、作者、协作者）
        // 注意：数字越小权限越高，Level 6 是游客
        if ($zbp->user->ID > 0 && $zbp->user->Level <= 4) {
            self::setNoCache();
            return;
        }
        
        // 🆕 系统路径不缓存（/zb_system/、/zb_users/plugin/）
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/zb_system/') !== false || 
            strpos($requestUri, '/zb_users/plugin/') !== false) {
            self::setNoCache();
            return;
        }
        
        // 自动检测内容类型和缓存时间
        if ($maxAge === null) {
            $maxAge = self::detectCacheTime();
        }
        
        // 设置内容类型（如果响应头尚未发送）
        if (!headers_sent()) {
            header("Content-Type: {$contentType}; charset=utf-8");
        }
        
        // 设置缓存控制
        $cacheControl = $public ? 'public' : 'private';
        header("Cache-Control: {$cacheControl}, max-age={$maxAge}, must-revalidate");
        
        // 设置过期时间
        $expires = gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT';
        header("Expires: {$expires}");
        
        // 设置Vary头（支持内容协商）
        header("Vary: Accept-Encoding, User-Agent");
        
        // ETag支持
        $etag = self::generateETag();
        if ($etag) {
            header("ETag: \"{$etag}\"");
            
            // 检查客户端ETag
            if (self::checkETag($etag)) {
                http_response_code(304);
                self::$headersSent = true;
                exit;
            }
        }
        
        // Last-Modified支持
        $lastModified = self::getLastModified();
        if ($lastModified) {
            header("Last-Modified: {$lastModified}");
            
            // 检查If-Modified-Since
            if (self::checkIfModifiedSince($lastModified)) {
                http_response_code(304);
                self::$headersSent = true;
                exit;
            }
        }
        
        // 安全头
        header("X-Content-Type-Options: nosniff");
        
        self::$headersSent = true;
    }
    
    /**
     * 设置不缓存
     * 
     * @return void
     */
    public static function setNoCache() {
        if (headers_sent()) {
            return;
        }
        
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        
        self::$headersSent = true;
    }
    
    /**
     * 自动检测缓存时间
     * 
     * @return int 缓存时间（秒）
     */
    private static function detectCacheTime() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // 图片
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico)$/i', $requestUri)) {
            return self::CACHE_TIME_IMAGE;
        }
        
        // CSS/JS
        if (preg_match('/\.(css|js)$/i', $requestUri)) {
            return self::CACHE_TIME_CSS_JS;
        }
        
        // 字体
        if (preg_match('/\.(woff|woff2|ttf|eot|otf)$/i', $requestUri)) {
            return self::CACHE_TIME_STATIC;
        }
        
        // RSS Feed
        if (strpos($requestUri, 'feed.php') !== false || strpos($requestUri, 'rss') !== false) {
            return self::CACHE_TIME_FEED;
        }
        
        // API请求
        if (strpos($requestUri, 'api.php') !== false || strpos($requestUri, 'zb_system/api') !== false) {
            return self::CACHE_TIME_API;
        }
        
        // 默认HTML页面
        return self::CACHE_TIME_HTML;
    }
    
    /**
     * 生成ETag
     * 
     * @return string|null
     */
    private static function generateETag() {
        global $zbp;
        
        // 基于以下因素生成ETag：
        // 1. 博客最后更新时间
        // 2. 当前请求URI
        // 3. 主题版本
        $factors = array(
            $zbp->option['ZC_BLOG_LASTUPDATE'] ?? time(),
            $_SERVER['REQUEST_URI'] ?? '',
            $zbp->option['ZC_BLOG_THEME_VERSION'] ?? '5.0.7',
            $zbp->user->ID // 区分登录状态
        );
        
        $etag = md5(implode('|', $factors));
        
        return $etag;
    }
    
    /**
     * 检查客户端ETag
     * 
     * @param string $etag 服务器ETag
     * @return bool 是否匹配
     */
    private static function checkETag($etag) {
        if (!isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return false;
        }
        
        $clientETag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
        
        return $clientETag === $etag;
    }
    
    /**
     * 获取Last-Modified时间
     * 
     * @return string|null GMT格式时间
     */
    private static function getLastModified() {
        global $zbp;
        
        // 使用博客最后更新时间
        $lastUpdate = $zbp->option['ZC_BLOG_LASTUPDATE'] ?? null;
        
        if (!$lastUpdate) {
            return null;
        }
        
        return gmdate('D, d M Y H:i:s', $lastUpdate) . ' GMT';
    }
    
    /**
     * 检查If-Modified-Since
     * 
     * @param string $lastModified 服务器Last-Modified时间
     * @return bool 是否未修改
     */
    private static function checkIfModifiedSince($lastModified) {
        if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return false;
        }
        
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
        
        // 去除可能的分号后面的内容（某些浏览器会添加）
        $ifModifiedSince = preg_replace('/;.*$/', '', $ifModifiedSince);
        
        return $ifModifiedSince === $lastModified;
    }
    
    /**
     * 启用Gzip压缩
     * 
     * @param bool $htmlOnly 仅对 HTML 启用（默认 true，避免与服务器 Gzip 冲突）
     * @return bool 是否成功启用
     */
    public static function enableGzip($htmlOnly = true) {
        // 检查条件
        if (headers_sent()) {
            return false;
        }
        
        // 已经启用
        if (ini_get('zlib.output_compression')) {
            return true;
        }
        
        // 检查扩展
        if (!extension_loaded('zlib')) {
            return false;
        }
        
        // 客户端不支持
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || 
            strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
            return false;
        }
        
        // 某些请求不压缩
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // 已压缩的文件不再压缩
        if (preg_match('/\.(jpg|jpeg|png|gif|zip|rar|7z|pdf)$/i', $requestUri)) {
            return false;
        }
        
        // 默认情况下，JS/CSS/PHP 也不压缩（由服务器处理，避免二次压缩导致乱码）
        if ($htmlOnly) {
            // 排除 JS/CSS 文件
            if (preg_match('/\.(js|css)$/i', $requestUri)) {
                return false;
            }
            
            // 排除 PHP 文件（如 script.php、api.php 等）
            if (preg_match('/\.php$/i', $requestUri) && 
                !preg_match('/index\.php$/i', $requestUri)) {
                return false;
            }
        }
        
        // 后台页面和系统页面不压缩（避免干扰）
        if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
            return false;
        }
        
        // 🆕 排除所有系统路径（避免清除缓存等页面出现乱码）
        if (strpos($requestUri, '/zb_system/') !== false || 
            strpos($requestUri, '/zb_users/plugin/') !== false) {
            return false;
        }
        
        // 启用压缩
        if (ob_start('ob_gzhandler')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 设置静态资源缓存（用于CDN或nginx配置参考）
     * 
     * @return array 缓存配置
     */
    public static function getStaticCacheConfig() {
        return array(
            'images' => array(
                'pattern' => '\.(jpg|jpeg|png|gif|webp|svg|ico)$',
                'max_age' => self::CACHE_TIME_IMAGE,
                'public' => true
            ),
            'fonts' => array(
                'pattern' => '\.(woff|woff2|ttf|eot|otf)$',
                'max_age' => self::CACHE_TIME_STATIC,
                'public' => true
            ),
            'css_js' => array(
                'pattern' => '\.(css|js)$',
                'max_age' => self::CACHE_TIME_CSS_JS,
                'public' => true
            ),
            'html' => array(
                'pattern' => '\.(html|htm)$',
                'max_age' => self::CACHE_TIME_HTML,
                'public' => true
            ),
            'feeds' => array(
                'pattern' => 'feed\.php|rss',
                'max_age' => self::CACHE_TIME_FEED,
                'public' => true
            )
        );
    }
}

/**
 * 便捷函数：设置页面缓存
 * 
 * @param int $maxAge 缓存时间（秒）
 * @param bool $public 是否公开缓存
 * @return void
 */
function tpure_set_page_cache($maxAge = 3600, $public = true) {
    TpureHttpCache::setCacheHeaders('text/html', $maxAge, $public);
}

/**
 * 便捷函数：设置不缓存
 * 
 * @return void
 */
function tpure_set_no_cache() {
    TpureHttpCache::setNoCache();
}

/**
 * 便捷函数：启用Gzip
 * 
 * @return bool
 */
function tpure_enable_gzip() {
    return TpureHttpCache::enableGzip();
}

