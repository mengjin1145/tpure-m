<?php
/**
 * Tpure 主题 - 主入口文件
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 * @link https://www.toyean.com/
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

// 定义主题根目录
define('TPURE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// 安全加载核心模块（按依赖顺序）
$core_modules = array(
    'lib/constants.php',     // 常量定义（最先加载）
    'lib/error-handler.php', // 错误处理器
    'lib/security.php',      // 安全函数
    'lib/cache.php',         // 缓存管理
    'lib/http-cache.php',    // HTTP缓存（浏览器缓存）
    'lib/database.php',      // 数据库优化
    'lib/helpers.php',       // 辅助函数
    'lib/ajax.php',          // Ajax处理
    'lib/mail.php',          // 邮件处理
    'lib/statistics.php',    // 访问统计
);

foreach ($core_modules as $module) {
    $module_path = TPURE_DIR . $module;
    if (file_exists($module_path)) {
        require_once $module_path;
    }
}

// 初始化错误处理器（如果类存在）
if (class_exists('TpureErrorHandler')) {
    TpureErrorHandler::init();
}

// 注册缓存失效钩子（如果函数存在）
if (function_exists('tpure_register_cache_hooks')) {
    tpure_register_cache_hooks();
}

// 加载插件依赖（保持向后兼容）
require TPURE_DIR . 'plugin' . DIRECTORY_SEPARATOR . 'searchstr.php';
require TPURE_DIR . 'plugin' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'sendmail.php';
require TPURE_DIR . 'plugin' . DIRECTORY_SEPARATOR . 'ipLocation' . DIRECTORY_SEPARATOR . 'function.php';

// 注册主题
RegisterPlugin("tpure", "ActivePlugin_tpure");

/**
 * 主题激活函数 - 注册所有钩子
 */
function ActivePlugin_tpure() {
    global $zbp;
    
    // 定义 URL 相关常量（依赖 $zbp 对象）
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
    
    // 加载主题语言包（安全调用）
    if (method_exists($zbp, 'LoadLanguage')) {
        $zbp->LoadLanguage('theme', 'tpure');
    }
    
    // 初始化HTTP缓存（启用Gzip压缩）
    if (class_exists('TpureHttpCache') && method_exists('TpureHttpCache', 'enableGzip')) {
        TpureHttpCache::enableGzip();
    }
    
    // SEO相关钩子
    if ($zbp->Config('tpure')->SEOON == '1') {
        Add_Filter_Plugin('Filter_Plugin_Category_Edit_Response', 'tpure_CategorySEO');
        Add_Filter_Plugin('Filter_Plugin_Tag_Edit_Response', 'tpure_TagSEO');
        Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'tpure_SingleSEO');
    }
    
    // 管理后台钩子
    Add_Filter_Plugin('Filter_Plugin_Admin_TopMenu', 'tpure_AddMenu');
    Add_Filter_Plugin('Filter_Plugin_Admin_Header', 'tpure_Header');
    
    // 核心钩子
    Add_Filter_Plugin('Filter_Plugin_Zbp_Load', 'tpure_Refresh');
    Add_Filter_Plugin('Filter_Plugin_ViewSearch_Template', 'tpure_SearchMain');
    
    // Ajax钩子（使用安全版本）
    Add_Filter_Plugin('Filter_Plugin_Cmd_Ajax', 'tpure_CmdAjax');
    
    // 文章列表钩子
    Add_Filter_Plugin('Filter_Plugin_ViewList_Core', 'tpure_Exclude_Category');
    Add_Filter_Plugin('Filter_Plugin_ViewList_Template', 'tpure_DefaultTemplate');
    
    // 编辑器钩子
    Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'tpure_Edit_Response');
    Add_Filter_Plugin('Filter_Plugin_Member_Edit_Response', 'tpure_MemberEdit_Response');
    
    // 模块钩子
    Add_Filter_Plugin('Filter_Plugin_PostModule_Succeed', 'tpure_CreateModule');
    Add_Filter_Plugin('Filter_Plugin_PostComment_Succeed', 'tpure_CreateModule');
    Add_Filter_Plugin('Filter_Plugin_DelComment_Succeed', 'tpure_CreateModule');
    Add_Filter_Plugin('Filter_Plugin_CheckComment_Succeed', 'tpure_CreateModule');
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Succeed', 'tpure_CreateModule');
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Del', 'tpure_CreateModule');
    
    // 归档缓存钩子
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Succeed', 'tpure_ArchiveAutoCache');
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Del', 'tpure_ArchiveAutoCache');
    
    // 错误处理钩子
    Add_Filter_Plugin('Filter_Plugin_Zbp_ShowError', 'tpure_ErrorCode');
    
    // 媒体钩子
    if ($zbp->Config('tpure')->PostVIDEOON == '1') {
        Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'tpure_ZBvideoLoad');
    }
    if ($zbp->Config('tpure')->PostZBAUDIOON == '1') {
        Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'tpure_ZBaudioLoad');
    }
    
    // 自定义代码钩子
    Add_Filter_Plugin('Filter_Plugin_Zbp_MakeTemplatetags', 'tpure_CustomCode');
    Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'tpure_SingleCode');
    
    // 大数据钩子
    Add_Filter_Plugin('Filter_Plugin_LargeData_Article', 'tpure_LargeDataArticle');
    
    // 邮件通知钩子（使用安全版本）
    if ($zbp->Config('tpure')->PostMAILON == '1') {
        Add_Filter_Plugin('Filter_Plugin_PostArticle_Core', 'tpure_ArticleCore');
        Add_Filter_Plugin('Filter_Plugin_PostArticle_Succeed', 'tpure_ArticleSendmail');
        Add_Filter_Plugin('Filter_Plugin_PostComment_Succeed', 'tpure_CmtSendmail');
    }
    
    // 登录页钩子
    if ($zbp->Config('tpure')->PostLOGINON == '1') {
        Add_Filter_Plugin('Filter_Plugin_Login_Header', 'tpure_LoginHeader');
    }
    
    // 文章全文展开钩子
    if ($zbp->Config('tpure')->PostVIEWALLON == '1') {
        Add_Filter_Plugin('Filter_Plugin_Edit_Response3', 'tpure_ArticleViewall');
    }
    
    // 图片灯箱钩子
    if ($zbp->Config('tpure')->PostFANCYBOXON == '1') {
        Add_Filter_Plugin('Filter_Plugin_Zbp_MakeTemplatetags', 'tpure_Fancybox');
        Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'tpure_FancyboxRegex');
    }
    
    // 同分类上下篇钩子
    if ($zbp->Config('tpure')->PostCATEPREVNEXTON == '1') {
        Add_Filter_Plugin('Filter_Plugin_Post_Prev', 'tpure_Post_Prev');
        Add_Filter_Plugin('Filter_Plugin_Post_Next', 'tpure_Post_Next');
    }
    
    // 图片懒加载钩子
    if ($zbp->Config('tpure')->PostLAZYLOADON == '1') {
        Add_Filter_Plugin('Filter_Plugin_Zbp_BuildTemplate', 'tpure_ListIMGLazyLoad');
        Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'tpure_ContentIMGLazyLoad');
    }
    
    // 自定义侧栏模块名称
    $zbp->lang['msg']['theme_module'] = $zbp->lang['tpure']['thememodule'];
    $zbp->lang['msg']['sidebar'] = $zbp->lang['tpure']['index'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar2'] = $zbp->lang['tpure']['catalog'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar3'] = $zbp->lang['tpure']['article'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar4'] = $zbp->lang['tpure']['page'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar5'] = $zbp->lang['tpure']['search'] . $zbp->lang['tpure']['page'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar6'] = $zbp->lang['tpure']['tagscloud'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar7'] = $zbp->lang['tpure']['archive'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar8'] = $zbp->lang['tpure']['member'] . $zbp->lang['tpure']['sidebar'];
    $zbp->lang['msg']['sidebar9'] = $zbp->lang['tpure']['readers'] . $zbp->lang['tpure']['sidebar'];
    
    // 验证码字符串
    $zbp->option['ZC_VERIFYCODE_STRING'] = $zbp->Config('tpure')->VerifyCode;
}

// ====================
// 以下是保留的核心函数
// 其他函数已移至lib目录的相应模块中
// ====================

/**
 * 主题设置页导航
 */
function tpure_SubMenu($id) {
    global $zbp;
    $arySubMenu = array(
        0 => array($zbp->lang['tpure']['baseset'], 'base', 'left', false),
        1 => array($zbp->lang['tpure']['seoset'], 'seo', 'left', false),
        2 => array($zbp->lang['tpure']['colorset'], 'color', 'left', false),
        3 => array($zbp->lang['tpure']['sideset'], 'side', 'left', false),
        4 => array($zbp->lang['tpure']['slideset'], 'slide', 'left', false),
        5 => array($zbp->lang['tpure']['mailset'], 'mail', 'left', false),
        6 => array($zbp->lang['tpure']['configset'], 'config', 'left', false),
    );
    foreach ($arySubMenu as $k => $v) {
        echo '<li><a href="?act=' . tpure_esc_attr($v[1]) . '" ' . 
             ($v[3] == true ? 'target="_blank"' : '') . 
             ' class="' . ($id == $v[1] ? 'on' : '') . '">' . 
             tpure_esc_html($v[0]) . '</a></li>';
    }
}

/**
 * 后台右上角添加主题设置入口
 */
function tpure_AddMenu(&$m) {
    global $zbp;
    $m[] = MakeTopMenu(
        "root", 
        $zbp->lang['tpure']['themeset'], 
        $zbp->host . "zb_users/theme/tpure/main.php?act=base", 
        "", 
        "topmenu_tpure", 
        "icon-grid-1x2-fill"
    );
}

/**
 * 后台管理页面顶部背景图片
 */
function tpure_Header() {
    global $zbp, $bloghost;
    $ajaxpost = ($zbp->Config('tpure')->PostAJAXPOSTON == '0') ? 0 : 1;
    echo '<style>.header{background:url(' . tpure_esc_url($bloghost . 'zb_users/theme/tpure/style/images/banner.jpg') . 
         ') no-repeat center center;background-size:cover;}</style>';
    echo '<script>window.theme = {ajaxpost:' . intval($ajaxpost) . '}</script>';
}
