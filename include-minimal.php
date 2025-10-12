<?php
/**
 * Tpure 主题 - 最小化版本（用于排查403问题）
 * 
 * 逐步加载功能，找出导致 403 的代码
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

// 定义主题根目录
define('TPURE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// ========== 阶段 1: 只加载核心模块（不执行任何钩子）==========
$core_modules = array(
    'lib/constants.php',
    'lib/error-handler.php',
    'lib/security.php',
    'lib/cache.php',
    'lib/http-cache.php',
    'lib/database.php',
    'lib/helpers.php',
    'lib/ajax.php',
    'lib/mail.php',
);

foreach ($core_modules as $module) {
    $module_path = TPURE_DIR . $module;
    if (file_exists($module_path)) {
        require_once $module_path;
    }
}

// 初始化错误处理器
if (class_exists('TpureErrorHandler')) {
    TpureErrorHandler::init();
}

// ========== 阶段 2: 加载插件依赖 ==========
require TPURE_DIR . 'plugin' . DIRECTORY_SEPARATOR . 'searchstr.php';
require TPURE_DIR . 'plugin' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'sendmail.php';
require TPURE_DIR . 'plugin' . DIRECTORY_SEPARATOR . 'ipLocation' . DIRECTORY_SEPARATOR . 'function.php';

// ========== 阶段 3: 注册主题（但不执行钩子）==========
RegisterPlugin("tpure", "ActivePlugin_tpure_minimal");

/**
 * 最小化激活函数 - 只定义URL常量，不注册任何钩子
 */
function ActivePlugin_tpure_minimal() {
    global $zbp;
    
    // 定义 URL 相关常量
    if (!defined('TPURE_THEME_URL')) {
        define('TPURE_THEME_URL', $zbp->host . 'zb_users/theme/tpure/');
    }
    if (!defined('TPURE_STYLE_URL')) {
        define('TPURE_STYLE_URL', TPURE_THEME_URL . 'style/');
    }
    if (!defined('TPURE_SCRIPT_URL')) {
        define('TPURE_SCRIPT_URL', TPURE_THEME_URL . 'script/');
    }
    if (!defined('TPURE_PLUGIN_URL')) {
        define('TPURE_PLUGIN_URL', TPURE_THEME_URL . 'plugin/');
    }
    
    // 加载主题语言包
    if (method_exists($zbp, 'LoadLanguage')) {
        $zbp->LoadLanguage('theme', 'tpure');
    }
    
    // ⚠️ 暂时不初始化 HTTP 缓存
    // if (class_exists('TpureHttpCache') && method_exists('TpureHttpCache', 'enableGzip')) {
    //     TpureHttpCache::enableGzip();
    // }
    
    // ⚠️ 暂时不注册任何钩子
    // 如果这个版本能正常访问，说明问题在钩子函数中
}

// ========== 最小化的必要函数 ==========

/**
 * 主题配置页面（原版保留）
 */
function InstallPlugin_tpure() {
    global $zbp;
    require $zbp->path . 'zb_users/theme/tpure/admin/install.php';
}

/**
 * 主题卸载
 */
function UninstallPlugin_tpure() {
    // 清空主题配置
}

