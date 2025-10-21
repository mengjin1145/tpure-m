<?php
/**
 * Tpure 主题 - 主题管理函数库
 * 
 * @package Tpure
 * @version 5.0.7
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
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
        if (function_exists('tpure_SideContent')) {
            $item->Content = tpure_SideContent($item);
            $item->Save();
        }
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
            if (function_exists('tpure_SideContent')) {
                $t->Content = tpure_SideContent($t);
            }
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
                    $str .= '<div class="sidecmtinfo"><em>' . $item->Author->StaticName . '</em>' . tpure_TimeAgo($item->Time()) . '</div>';
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

/**
 * 自动更新文章归档缓存
 */
function tpure_ArchiveAutoCache() {
    global $zbp;
    if (isset($zbp->Config('tpure')->PostAUTOARCHIVEON) && $zbp->Config('tpure')->PostAUTOARCHIVEON) {
        if (function_exists('tpure_CreateArchiveCache')) {
            tpure_CreateArchiveCache();
        }
    }
}

/**
 * 删除文章归档缓存
 */
function tpure_delArchive() {
    global $zbp;
    $dir = $zbp->usersdir . 'cache/theme/' . $zbp->theme . '/';
    if (file_exists($dir)) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "archive.html";
                if (!is_dir($fullpath)) {
                    @unlink($fullpath);
                } else {
                    @deldir($fullpath);
                }
            }
        }
        closedir($dh);
    }
}

/**
 * 创建文章归档缓存文件
 * 
 * @param string $str HTML内容
 * @return bool
 */
function tpure_CreateArchiveCache($str = false) {
    global $zbp;
    $path = $zbp->usersdir . 'cache/theme/tpure/';
    if (!file_exists($path)) {
        @mkdir($path, 0755, true);
    }
    if (!file_exists($path)) {
        return false;
    }
    if (!$str) {
        if (function_exists('tpure_CreateArchiveHTML')) {
            $str = tpure_CreateArchiveHTML();
        } else {
            return false;
        }
    }
    $filePath = $path . 'archive.html';
    $file = fopen($filePath, "w");
    fwrite($file, $str);
    fclose($file);
    if (!file_exists($filePath)) {
        return false;
    }
    return true;
}

