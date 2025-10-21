<?php
/**
 * Tpure 主题 - 核心函数库
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

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
        echo '<li><a href="?act=' . htmlspecialchars($v[1]) . '" ' . 
             ($v[3] == true ? 'target="_blank"' : '') . 
             ' class="' . ($id == $v[1] ? 'on' : '') . '">' . 
             htmlspecialchars($v[0]) . '</a></li>';
    }
}

/**
 * 后台顶部菜单
 */
function tpure_AddMenu() {
    global $zbp;
    $menuArray = array(
        '0' => array(
            'url' => $zbp->host . 'zb_users/theme/tpure/main.php?act=base',
            'name' => $zbp->lang['tpure']['set'],
            'target' => '_self'
        )
    );
    return $menuArray;
}

/**
 * 后台 header 自定义
 */
function tpure_Header() {
    global $zbp;
    echo '<link rel="stylesheet" href="' . $zbp->host . 'zb_users/theme/tpure/script/admin.css?v=' . 
         $zbp->themeapp->version . '" type="text/css" />';
}

/**
 * 分类选择下拉框（支持钩子过滤）
 * 
 * @param int $selectid 当前选中的分类ID
 * @param string $selectname 下拉框的name属性
 * @return string HTML下拉框代码
 */
function tpure_Exclude_CategorySelect($selectid = 0, $selectname = '') {
    global $zbp;

    // 🔧 安全性增强：过滤输入参数
    $selectid = (int)$selectid;
    $selectname = htmlspecialchars($selectname, ENT_QUOTES, 'UTF-8');

    // 获取所有分类列表
    $s = '<select class="selectpicker" id="' . $selectname . '" name="' . $selectname . '">';
    $category_array = $zbp->GetCategoryList('*', null, null, null, null);
    
    foreach ($category_array as $cate) {
        if ($cate->ParentID == 0) {
            $s .= tpure_OutputOptionItemsOfCategories($cate->ID, $selectid, 0);
        }
    }
    $s .= '</select>';

    // 触发钩子，允许其他插件修改输出
    if (isset($GLOBALS['hooks']['Filter_Plugin_OutputOptionItemsOfCategories'])) {
        foreach ($GLOBALS['hooks']['Filter_Plugin_OutputOptionItemsOfCategories'] as $fpname => &$fpsignal) {
            $fpname($s, $selectid, $selectname);
        }
    }

    return $s;
}

/**
 * 递归输出分类选项（内部辅助函数）
 * 
 * @param int $id 分类ID
 * @param int $selectid 选中的分类ID
 * @param int $level 层级（用于缩进）
 * @return string HTML option标签
 */
function tpure_OutputOptionItemsOfCategories($id, $selectid, $level) {
    global $zbp;

    // 🔧 安全性增强：过滤输入参数
    $id = (int)$id;
    $selectid = (int)$selectid;
    $level = (int)$level;

    $category = $zbp->GetCategoryByID($id);
    if (!$category || !$category->ID) {
        return '';
    }

    // 构建选项
    $s = '<option value="' . $category->ID . '"';
    if ($selectid == $category->ID) {
        $s .= ' selected="selected"';
    }
    $s .= '>';
    
    // 添加层级缩进
    for ($i = 0; $i < $level; $i++) {
        $s .= '&nbsp;&nbsp;&nbsp;&nbsp;';
    }
    $s .= htmlspecialchars($category->Name, ENT_QUOTES, 'UTF-8');
    $s .= '</option>';

    // 递归处理子分类
    $category_array = $zbp->GetCategoryList('*', null, null, null, null);
    foreach ($category_array as $cate) {
        if ($cate->ParentID == $category->ID) {
            $s .= tpure_OutputOptionItemsOfCategories($cate->ID, $selectid, $level + 1);
        }
    }

    return $s;
}

/**
 * 登录页 Header 自定义（原版样式）
 * 参考原版 include.php 第206-262行
 */
function tpure_LoginHeader() {
    global $zbp;
    
    // 🔧 修复原版bug：定义 bloghost 变量
    $bloghost = $zbp->host;
    $bloghost = rtrim($bloghost, '/');
    
    // 原版Logo获取逻辑：优先使用主题配置的Logo，否则使用站点名称
    $logo = $zbp->Config('tpure')->PostLOGO && $zbp->Config('tpure')->PostLOGOON == 1 ? $zbp->Config('tpure')->PostLOGO : $zbp->name;
    
    echo <<<CSSJS
    <style>
        input:-webkit-autofill { -webkit-text-fill-color:#000 !important; background-color:transparent; background-image:none; transition:background-color 50000s ease-in-out 0s; }
        .bg { height:100%; background:url({$zbp->host}zb_users/theme/tpure/style/images/banner.jpg) no-repeat center top; background-size:cover; }
        .logo { width:100%; height:auto; margin:0; padding:20px 0 10px; text-align:center; border-bottom:1px solid #eee; }
        .logo img { width:auto; height:50px; margin:auto; background:none; display:block; }
        #wrapper { width:440px; min-height:400px; height:auto; border-radius:8px; background:#fff; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); }
        .login { width:auto; height:auto; padding:30px 40px 20px; }
        .login input[type="text"], .login input[type="password"] { width:100%; height:42px; float:none; padding:0 14px; font-size:16px; line-height:42px; border:1px solid #e4e8eb; outline:0; border-radius:3px; box-sizing:border-box; }
        .login input[type="password"] { font-size:24px; letter-spacing:5px; }
        .login input[type="text"]:focus, .login input[type="password"]:focus { color:#0188fb; background-color:#fff; border-color:#aab7c1; outline:0; box-shadow:0 0 0 0.2rem rgba(31,73,119,0.1); }
        .login dl { height:auto; }
        .login dd { margin-bottom:14px; }
        .login dd.submit, .login dd.password, .login dd.username, .login dd.validcode { width:auto; float:none; overflow:visible; }
        .login dd.validcode { height:auto; position:relative; }
        .login dd.validcode label { margin-bottom:4px; }
        .login dd.validcode img { height:38px; position:absolute; top:auto; right:2px; bottom:2px; }
        .login dd.checkbox { width:170px; float:none; margin:0 0 10px; }
        .login dd.checkbox input[type="checkbox"] { width:16px; height:16px; margin-right:6px;; }
        .login label { width:auto; margin-bottom:5px; padding:0; font-size:16px; text-align:left; }
        .logintitle { padding:0 70px; font-size:24px; color:#0188fb; line-height:40px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden; position:relative; display:block; }
        .logintitle:before,.logintitle:after { content:""; width:40px; height:0; border-top:1px solid #ddd; position:absolute; top:20px; right:30px; }
        .logintitle:before { right:auto; left:30px; }
        .button { width:100%; height:42px; float:none; font-size:16px; line-height:42px; border-radius:3px; outline:0; box-shadow:1px 3px 5px 0 rgba(72,108,255,0.3); background:#0188fb; }
        .button:hover { background:#0188fb; }
        @media only screen and (max-width: 768px){
            .login { padding:30px 30px 10px; }
            .login dd { float:left; margin-bottom:14px; padding:0; }
            .login dd.validcode label { margin-bottom:5px; }
            .login dd.checkbox { width:auto; padding:0; }
            .login dd.submit { margin-right:0; }
        }
        @media only screen and (max-width: 520px){
            #wrapper { width:96%; margin:0 auto; }
            .login dd.username label, .login dd.password label { width:100%; }
        }
        </style>
        <script>
        $(function(){
        var bloghost = "{$bloghost}";
        function check_is_img(url) {
            return (url.match(/\.(jpeg|jpg|gif|png|svg)$/) != null)
        }
        if(check_is_img("{$logo}")){
            $(".logo").find("img").replaceWith('<img src="{$logo}"/>').end().wrapInner("<a href='"+bloghost+"'/>");
        }else{
            $(".logo").find("img").replaceWith('<span class="logintitle">{$logo}<span>').end().wrapInner("<a href='"+bloghost+"'/>");
        }
        });
    </script>
CSSJS;
}

/**
 * 默认模板选择钩子
 * 用于设置不同页面类型的默认模板
 */
function tpure_DefaultTemplate(&$template) {
    global $zbp;
    
    // 首页样式设置（第2页及以后）
    if($template->GetTags('type') == 'index' && $template->GetTags('page') != '1'){
        switch($zbp->Config('tpure')->PostINDEXSTYLE){
            case "1":
                $template->SetTemplate('forum');
                break;
            case "2":
                $template->SetTemplate('album');
                break;
            case "3":
                $template->SetTemplate('sticker');
                break;
            case "4":
                $template->SetTemplate('hotspot');
                break;
            default:
                $template->SetTemplate('catalog');
        }
    }
    
    // 标签页模板设置 - 修复：使用自定义 tags 模板
    if($template->GetTags('type') == 'tag') {
        $template->SetTemplate('tags');
    }
    
    // 分类页模板设置
    if($template->GetTags('type') == 'category') {
        $category = $template->GetTags('category');
        if($category && isset($category->Template) && !empty($category->Template)) {
            if($category->Template != 'forum' && $category->Template != 'album' && $category->Template != 'sticker' && $category->Template != 'hotspot') {
                $template->SetTemplate('catalog');
            }
        } else {
            $template->SetTemplate('catalog');
        }
    }
    
    // 日期归档页
    if($template->GetTags('type') == 'date'){
        $template->SetTemplate('catalog');
    }
    
    // 作者页模板设置
    if($template->GetTags('type') == 'author'){
        $author = $template->GetTags('author');
        if($author && isset($author->Template) && !empty($author->Template)) {
            if($author->Template != 'catalog' && $author->Template != 'forum' && $author->Template != 'album' && $author->Template != 'sticker' && $author->Template != 'hotspot'){
                $template->SetTemplate('author');
            }
        } else {
            $template->SetTemplate('author');
        }
    }
}

// ==================== 🔧 修复：补充缺失的核心函数 ====================

/**
 * 导航分类面包屑（递归）
 * 在catalog.php模板中使用，显示分类层级导航
 */
function tpure_navcate($id) {
    $html = '';
    $navcate = new Category;
    $navcate->LoadInfoByID($id);
    $html = ' &gt; <a href="' . $navcate->Url . '" title="查看' . $navcate->Name . '中的全部文章">' . $navcate->Name . '</a> ' . $html;
    if (($navcate->ParentID) > 0) {
        tpure_navcate($navcate->ParentID);
    }
    echo $html;
}

/**
 * 刷新主题配置（重建模块等）
 */
function tpure_Refresh() {
    global $zbp;
    
    // 删除已编译的模板缓存文件
    $compile_dir = $zbp->usersdir . 'cache/compiled/' . $zbp->theme . '/';
    if (is_dir($compile_dir)) {
        $files = glob($compile_dir . '*.php');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
    
    // 重建模板
    $zbp->BuildTemplate();
    
    // 清除主题缓存
    if (function_exists('tpure_clear_all_cache')) {
        tpure_clear_all_cache();
    }
}

/**
 * 错误代码处理
 * 挂接口：Add_Filter_Plugin('Filter_Plugin_Zbp_ShowError', 'tpure_ErrorCode')
 */
function tpure_ErrorCode($errorCode) {
    global $zbp;
    if ($errorCode == 6) {
        // 登录过期
        if ($zbp->Config('tpure')->PostERRORTOPAGE) {
            Redirect($zbp->Config('tpure')->PostERRORTOPAGE);
        } else {
            Redirect($zbp->host . 'zb_system/login.php');
        }
        die();
    } elseif ($errorCode == 82) {
        // 网站关闭
        echo tpure_CloseSite();
        die();
    }
}

/**
 * 网站关闭页面
 */
function tpure_CloseSite() {
    global $zbp;
    
    $template = file_get_contents($zbp->path . 'zb_users/theme/' . $zbp->theme . '/template/closesite.html');
    
    if ($zbp->Config('tpure')->PostCLOSESITEBGON == '1') {
        $bgclass = 'bgmask';
    } else {
        $bgclass = '';
    }
    
    $search = array(
        '{$PostCLOSESITEBG}',
        '{$PostCLOSESITEBGCLASS}',
        '{$PostCLOSESITELOGO}',
        '{$PostCLOSESITECON}'
    );
    
    $replace = array(
        $zbp->Config('tpure')->PostCLOSESITEBG,
        $bgclass,
        $zbp->Config('tpure')->PostCLOSESITELOGO,
        $zbp->Config('tpure')->PostCLOSESITECON
    );
    
    return str_replace($search, $replace, $template);
}

/**
 * 生成主题颜色CSS
 * 
 * @return string CSS样式
 */
function tpure_color() {
    global $zbp;
    $skin = '';
    $color = $zbp->Config('tpure')->PostCOLOR;
    
    // 主题色相关样式
    $skin .= "a, a:hover,.menu li a:hover,.menu li.on a { color:#{$color}; }";
    $skin .= ".menu li:before,.schfixed button,.post h2 em {background:#{$color};}";
    $skin .= ".menuico span,.lazyline {background-color:#{$color}}";
    
    // 背景色
    $bgcolor = $zbp->Config('tpure')->PostBGCOLOR;
    $skin .= ".wrapper,.main,.indexcon,.closepage { background:#{$bgcolor}; }";
    
    // 侧边栏布局
    $sidelayout = $zbp->Config('tpure')->PostSIDELAYOUT;
    if ($sidelayout == 'l') {
        $skin .= ".sidebar { float:left; } .content { float:right; }@media screen and (max-width:1200px){.content { float:none; margin:0; }}";
    }
    
    // 字体
    $font = $zbp->Config('tpure')->PostFONT;
    if ($font) {
        $skin .= "body,input,textarea {font-family:{$font}}";
    }
    
    // 背景图片
    if ($zbp->Config('tpure')->PostBGIMGSTYLE == '2') {
        $bgimgstyle = "background-attachment:fixed; background-position:center top; background-size:cover;";
    } else {
        $bgimgstyle = "background-attachment:fixed; background-repeat:repeat;";
    }
    if ($zbp->Config('tpure')->PostBGIMGON) {
        $skin .= ".indexcon,.main { background-image:url(" . $zbp->Config('tpure')->PostBGIMG . ");" . $bgimgstyle . " }";
    }
    
    // 头部和底部颜色
    $headbgcolor = $zbp->Config('tpure')->PostHEADBGCOLOR;
    $footbgcolor = $zbp->Config('tpure')->PostFOOTBGCOLOR;
    $footfontcolor = $zbp->Config('tpure')->PostFOOTFONTCOLOR;
    if ($headbgcolor) {
        $skin .= ".header { background-color:#{$headbgcolor};}";
    }
    if ($footbgcolor && $footfontcolor) {
        $skin .= ".footer { color:#{$footfontcolor}; background-color:#{$footbgcolor}; } .footer a { color:#{$footfontcolor}; }";
    }
    
    // 自定义CSS
    $customcss = $zbp->Config('tpure')->PostCUSTOMCSS;
    $skin .= "{$customcss}";
    
    return $skin;
}

/**
 * 创建主题自定义模块
 */
function tpure_CreateModule() {
    global $zbp;
    
    // 刷新浏览总量
    $all_views = ($zbp->option['ZC_LARGE_DATA'] == true || $zbp->option['ZC_VIEWNUMS_TURNOFF'] == true) ? 0 : GetValueInArrayByCurrent($zbp->db->Query('SELECT SUM(log_ViewNums) AS num FROM ' . $GLOBALS['table']['Post']), 'num');
    $zbp->cache->all_view_nums = $all_views;
    $zbp->SaveCache();
    
    $module_list = array(
        array("tpure_hotviewarticle", "tpure_HotViewArticle", "ul", "热门阅读", "0"),
        array("tpure_hotcmtarticle", "tpure_HotCmtArticle", "ul", "热评文章", "0"),
        array("tpure_newarticle", "tpure_NewArticle", "ul", "最新文章", "0"),
        array("tpure_recarticle", "tpure_RecArticle", "ul", "推荐阅读", "0"),
        array("tpure_avatarcomment", "tpure_AvatarComment", "ul", "最近评论", "0"),
        array("tpure_newcomment", "tpure_NewComment", "ul", "最新评论", "0"),
        array("tpure_user", "tpure_User", "div", "站长简介", "1"),
        array("tpure_readers", "tpure_Readers", "ul", "读者墙", "0"),
    );
    
    $module_filenames = array();
    foreach ($module_list as $item) {
        array_push($module_filenames, $item[0]);
    }
    
    $modules = $zbp->GetModuleList(array("*"), array(
        array("IN", "mod_FileName", $module_filenames),
    ));
    
    $has_modules = array();
    foreach ($modules as $item) {
        $item->Content = tpure_SideContent($item);
        $item->Save();
        array_push($has_modules, $item->FileName);
    }
    
    foreach ($module_filenames as $k => $item) {
        if (!array_search($item, $has_modules)) {
            $module = $module_list[$k];
            $t = new Module();
            $t->Name = $module[3];
            $t->IsHideTitle = $module[4];
            $t->FileName = $module[0];
            $t->Source = "theme_tpure";
            $t->SidebarID = 0;
            $t->Content = tpure_SideContent($t);
            $t->HtmlID = $module[1];
            $t->Type = $module[2];
            $t->Save();
        }
    }
}

/**
 * 模块内容生成（简化版）
 * 
 * @param object $module 模块对象
 * @return string HTML内容
 */
function tpure_SideContent(&$module) {
    global $zbp;
    $str = "";
    
    if ($zbp->Config('tpure')->PostBLANKSTYLE == 2) {
        $blankstyle = ' target="_blank"';
    } else {
        $blankstyle = '';
    }
    
    switch ($module->FileName) {
        case 'tpure_hotviewarticle':
            $num = $module->MaxLi > 0 ? $module->MaxLi : 5;
            if (function_exists('tpure_GetHotArticleList')) {
                $hotArtList = tpure_GetHotArticleList($num);
                foreach ($hotArtList as $item) {
                    $str .= '<li class="sideitem">';
                    $str .= '<a href="' . $item->Url . '"' . $blankstyle . ' title="' . $item->Title . '" class="itemtitle">' . $item->Title . '</a>';
                    $str .= '<p class="sideinfo"><span class="view">' . $item->ViewNums . ' ' . $zbp->lang['tpure']['viewnum'] . '</span>' . $item->Category->Name . '</p>';
                    $str .= '</li>';
                }
            }
            break;
            
        case 'tpure_newcomment':
            $num = $module->MaxLi > 0 ? $module->MaxLi : 5;
            if (function_exists('tpure_GetNewComment')) {
                $newCmtList = tpure_GetNewComment($num);
                foreach ($newCmtList as $item) {
                    $str .= '<li class="sideitem">';
                    $str .= '<div class="sidecmtinfo"><em>' . $item->Author->StaticName . '</em>';
                    if (function_exists('tpure_TimeAgo')) {
                        $str .= tpure_TimeAgo($item->Time());
                    }
                    $str .= '</div>';
                    $str .= '<div class="sidecmtcon"><a href="' . $item->Post->Url . '#cmt' . $item->ID . '"' . $blankstyle . '>' . $item->Content . '</a></div>';
                    $str .= '</li>';
                }
            }
            break;
            
        default:
            $str = '<!-- Module: ' . $module->FileName . ' -->';
            break;
    }
    
    return $str;
}