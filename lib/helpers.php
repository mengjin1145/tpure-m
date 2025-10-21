<?php
/**
 * Tpure 主题 - 辅助函数库
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * 获取文章缩略图
 * 
 * @param object $Source 文章对象
 * @param string $IsThumb 是否强制使用默认缩略图
 * @return string 缩略图URL
 */
function tpure_Thumb($Source, $IsThumb = '0') {
    global $zbp;
    
    if (!isset($Source) || !is_object($Source)) {
        return '';
    }
    
    $ThumbSrc = '';
    $randnum = mt_rand(1, 10);
    
    // 使用新版缩略图API（Z-BlogPHP 1.7+）
    if (ZC_VERSION_COMMIT >= 2800 && $zbp->Config('tpure')->PostTHUMBNEWON == '1') {
        return tpure_Thumb_new($Source, $IsThumb);
    }
    
    // 传统方式
    $pattern = "/<img[^>]+src=\"(?<url>[^\"]+)\"[^>]*>/";
    $content = isset($Source->Content) ? $Source->Content : '';
    preg_match_all($pattern, $content, $matchContent);
    
    if ($zbp->Config('tpure')->PostIMGON == '1') {
        // 优先使用自定义缩略图
        if (isset($Source->Metas->proimg) && !empty($Source->Metas->proimg)) {
            $ThumbSrc = $Source->Metas->proimg;
        }
        // 使用文章首图
        elseif (isset($matchContent[1][0])) {
            $ThumbSrc = $matchContent[1][0];
        }
        // 使用默认缩略图
        elseif ($zbp->Config('tpure')->PostTHUMBON == '1') {
            $ThumbSrc = $zbp->Config('tpure')->PostTHUMB;
        }
        // 使用随机缩略图
        elseif ($zbp->Config('tpure')->PostRANDTHUMBON == '1') {
            $ThumbSrc = $zbp->host . "zb_users/theme/" . $zbp->theme . "/include/thumb/" . $randnum . ".jpg";
        }
        // 强制使用默认缩略图
        elseif ($IsThumb == '1') {
            $ThumbSrc = $zbp->Config('tpure')->PostTHUMB;
        }
    }
    
    return tpure_esc_url($ThumbSrc);
}

/**
 * 获取文章缩略图（Z-BlogPHP 1.7+ 新版API）
 * 
 * @param object $Source 文章对象
 * @param string $IsThumb 是否强制使用默认缩略图 '0'=否 '1'=是
 * @return string 缩略图URL
 * @since 5.0.0
 */
function tpure_Thumb_new($Source, $IsThumb) {
    global $zbp;
    
    $ThumbSrc = '';
    $randnum = mt_rand(1, 10);
    
    if ($zbp->Config('tpure')->PostIMGON == '1') {
        if (isset($Source->Metas->proimg) && !empty($Source->Metas->proimg)) {
            $ThumbSrc = $Source->Metas->proimg;
        }
        elseif (isset($Source->ImageCount) && $Source->ImageCount >= 1) {
            $thumbs = $Source->Thumbs(210, 147, 1);
            if (count($thumbs) > 0) {
                $ThumbSrc = $thumbs[0];
            }
        }
        elseif ($zbp->Config('tpure')->PostTHUMBON == '1') {
            $ThumbSrc = $zbp->Config('tpure')->PostTHUMB;
        }
        elseif ($zbp->Config('tpure')->PostRANDTHUMBON == '1') {
            $ThumbSrc = $zbp->host . "zb_users/theme/" . $zbp->theme . "/include/thumb/" . $randnum . ".jpg";
        }
        elseif ($IsThumb == '1') {
            $ThumbSrc = $zbp->Config('tpure')->PostTHUMB;
        }
    }
    
    return tpure_esc_url($ThumbSrc);
}

/**
 * 获取用户头像（安全版本）
 * 
 * @param object $member 用户对象
 * @param string $email 邮箱地址（可选）
 * @return string 头像URL
 */
function tpure_MemberAvatar($member, $email = null) {
    global $zbp;
    
    if (!is_object($member)) {
        return tpure_esc_url($zbp->host . 'zb_users/avatar/0.png');
    }
    
    $avatar = '';
    
    // 1. 优先使用自定义头像
    if (isset($member->Metas->memberimg) && !empty($member->Metas->memberimg)) {
        $avatar = $member->Metas->memberimg;
    }
    // 2. 使用QQ头像或Gravatar
    elseif (isset($email) && !empty($email)) {
        $avatar = tpure_get_avatar_url($email);
    }
    elseif (isset($member->Email) && $member->Email !== 'null@null.com') {
        $avatar = tpure_get_avatar_url($member->Email);
    }
    // 3. 使用上传的头像
    elseif (is_file($zbp->usersdir . 'avatar/' . $member->ID . '.png')) {
        $avatar = $zbp->host . 'zb_users/avatar/' . $member->ID . '.png';
    }
    // 4. 使用默认头像
    else {
        $avatar = $zbp->host . 'zb_users/avatar/0.png';
    }
    
    return tpure_esc_url($avatar);
}

/**
 * 获取头像URL（QQ或Gravatar）
 */
function tpure_get_avatar_url($email) {
    global $zbp;
    
    // 验证邮箱
    $email = tpure_validate_email($email);
    if ($email === false) {
        return $zbp->host . 'zb_users/avatar/0.png';
    }
    
    // 检查是否为QQ邮箱
    if (preg_match('/^(\d+)@qq\.com$/i', $email, $matches)) {
        return 'https://q2.qlogo.cn/headimg_dl?dst_uin=' . $matches[1] . '&spec=100';
    }
    
    // 使用Gravatar
    if ($zbp->CheckPlugin('Gravatar')) {
        $default_url = $zbp->Config('Gravatar')->default_url;
        return str_replace('{%emailmd5%}', md5(strtolower(trim($email))), $default_url);
    }
    
    // 使用系统默认
    return $zbp->host . 'zb_users/avatar/0.png';
}

/**
 * 时间友好显示
 * 
 * @param string $ptime 时间字符串
 * @return string 友好时间显示
 */
function tpure_TimeAgo($ptime) {
    global $zbp;
    
    // 使用标准格式
    if ($zbp->Config('tpure')->PostTIMESTYLE != '0') {
        $ptime = strtotime($ptime);
        $format = $zbp->Config('tpure')->PostTIMEFORMAT;
        
        switch ($format) {
            case '5':
                return date('Y年m月d日 H:i:s', $ptime);
            case '4':
                return date('Y年m月d日 H:i', $ptime);
            case '3':
                return date('Y年m月d日', $ptime);
            case '2':
                return date('Y-m-d H:i:s', $ptime);
            case '1':
                return date('Y-m-d H:i', $ptime);
            default:
                return date('Y-m-d', $ptime);
        }
    }
    
    // 相对时间
    $ptime = strtotime($ptime);
    $etime = time() - $ptime;
    
    if ($etime < 1) {
        return '刚刚';
    }
    
    $interval = array(
        12 * 30 * 24 * 60 * 60  => '年前<span class="datetime"> (' . date('Y-m-d', $ptime) . ')</span>',
        30 * 24 * 60 * 60       => '个月前<span class="datetime"> (' . date('m-d', $ptime) . ')</span>',
        7 * 24 * 60 * 60        => '周前<span class="datetime"> (' . date('m-d', $ptime) . ')</span>',
        24 * 60 * 60            => '天前',
        60 * 60                 => '小时前',
        60                      => '分钟前',
        1                       => '秒前',
    );
    
    foreach ($interval as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . $str;
        }
    }
    
    return '刚刚';
}

/**
 * 判断是否为移动端
 * 
 * @return bool
 */
function tpure_isMobile() {
    if (isset($_GET['must_use_mobile'])) {
        return true;
    }
    
    $is_mobile = false;
    $regex = '/android|adr|iphone|ipad|linux|windows\sphone|kindle|gt\-p|gt\-n|rim\stablet|opera|meego|Mobile|Silk|BlackBerry|opera\smini/i';
    
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    if (preg_match($regex, $user_agent)) {
        $is_mobile = true;
    }
    
    return $is_mobile;
}

/**
 * 判断是否为今天
 * 
 * @param string $date 日期字符串
 * @return bool
 */
function tpure_IsToday($date) {
    $greyday = $date;
    $formatday = substr($greyday, 0, 10);
    $today = date('Y-m-d');
    
    return ($formatday === $today);
}

/**
 * UTF-8字符串截取
 * 
 * @param string $string 字符串
 * @param int $start 开始位置
 * @param int $length 长度
 * @return string
 */
function tpure_SubStrUTF8($string, $start, $length) {
    return mb_substr($string, $start, $length, 'UTF-8');
}

/**
 * Unicode字符转换
 * 
 * @param string $str 字符串
 * @return string
 */
if (!function_exists('tpure_CodeToString')) {
    function tpure_CodeToString($str) {
        $to = array(" ", "  ", "   ", "    ", "\"", "<", ">", "&");
        $pre = array('&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;', '&quot;', '&lt', '&gt', '&amp');
        
        return str_replace($pre, $to, $str);
    }
}

/**
 * 判断列表模板类型
 * 
 * @param string $listtype 列表类型
 * @return string 模板名称
 */
function tpure_JudgeListTemplate($listtype) {
    global $zbp;
    
    // 如果没有传入参数，从配置中获取
    if (empty($listtype)) {
        $listtype = $zbp->Config('tpure')->PostSEARCHSTYLE;
    }
    
    switch($listtype) {
        case 1:
            $template = 'forum';
            break;
        case 2:
            $template = 'album';
            break;
        case 3:
            $template = 'sticker';
            break;
        case 4:
            $template = 'hotspot';
            break;
        default:
            $template = '';
    }
    
    return $template;
}

// ==================== 数据库查询优化函数 ====================

/**
 * 获取热门文章列表（优化版 - 索引优化 + 缓存 + N+1解决）
 * 
 * @param int $num 数量
 * @param string $type 类型 'view'=按浏览量 'cmt'=按评论数
 * @return array 文章列表
 */
function tpure_GetHotArticleList($num = 5, $type = "view") {
    global $zbp;
    
    // 缓存键
    $cacheKey = "hot_articles_{$num}_{$type}";
    
    // 尝试从缓存获取
    if (function_exists('tpure_cache_get')) {
        $cached = tpure_cache_get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    // 确定时间范围
    if ($type == "cmt") {
        $days = $zbp->Config('tpure')->PostSIDECMTDAY ?: 90;
    } else {
        $days = $zbp->Config('tpure')->PostSIDEVIEWDAY ?: 90;
    }
    $timeLimit = time() - $days * 86400;
    
    // 构建查询条件（使用索引字段）
    $w = array(
        array("=", "log_Type", 0),
        array("=", "log_Status", 0),
        array(">", "log_PostTime", $timeLimit),
    );
    
    // 排序字段
    $order = ($type == "view") 
        ? array("log_ViewNums" => "DESC") 
        : array("log_CommNums" => "DESC");
    
    // 只查询必要字段
    $fields = array(
        'log_ID', 'log_CateID', 'log_AuthorID',
        'log_Title', 'log_Url', 'log_PostTime',
        'log_ViewNums', 'log_CommNums', 'log_Intro'
    );
    
    // 执行查询
    $articles = $zbp->GetArticleList($fields, $w, $order, array($num));
    
    // 批量预加载关联数据
    if (!empty($articles) && function_exists('tpure_preload_article_relations')) {
        $articles = tpure_preload_article_relations($articles);
    }
    
    // 缓存结果（1小时）
    if (function_exists('tpure_cache_set')) {
        tpure_cache_set($cacheKey, $articles, 3600);
    }
    
    return $articles;
}

/**
 * 获取推荐文章列表（优化版）
 */
function tpure_GetRecArticle() {
    global $zbp;
    
    $cacheKey = "rec_articles";
    
    if (function_exists('tpure_cache_get')) {
        $cached = tpure_cache_get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $ids = $zbp->Config('tpure')->PostSIDERECID;
    if (empty($ids)) {
        return array();
    }
    
    $ids = explode(",", $ids);
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids);
    
    if (empty($ids)) {
        return array();
    }
    
    // 使用IN查询
    $w = array(
        array("=", "log_Type", 0),
        array("=", "log_Status", 0),
        array("IN", "log_ID", $ids),
    );
    
    $articlesMap = $zbp->GetArticleList('*', $w);
    
    // 按指定顺序排列
    $articles = array();
    foreach ($ids as $id) {
        foreach ($articlesMap as $article) {
            if ($article->ID == $id) {
                $articles[] = $article;
                break;
            }
        }
    }
    
    if (function_exists('tpure_cache_set')) {
        tpure_cache_set($cacheKey, $articles, 86400);
    }
    
    return $articles;
}

/**
 * 获取最新评论列表（优化版 - 解决N+1查询）
 */
function tpure_GetNewComment($num = 5) {
    global $zbp;
    
    $cacheKey = "new_comments_{$num}";
    
    if (function_exists('tpure_cache_get')) {
        $cached = tpure_cache_get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $w = array(array("=", "comm_IsChecking", 0));
    
    $comments = $zbp->GetCommentList(
        array('*'), 
        $w, 
        array("comm_PostTime" => "DESC"), 
        array($num)
    );
    
    // 批量预加载父评论（解决N+1查询）
    if (!empty($comments)) {
        $parentIds = array();
        foreach ($comments as $comment) {
            if ($comment->ParentID > 0) {
                $parentIds[] = $comment->ParentID;
            }
        }
        
        if (!empty($parentIds)) {
            $parentIds = array_unique($parentIds);
            $parentComments = $zbp->GetCommentList(
                array('*'),
                array(array('IN', 'comm_ID', $parentIds))
            );
            
            $parentMap = array();
            foreach ($parentComments as $parent) {
                $parentMap[$parent->ID] = $parent;
                $parent->Content = TransferHTML($parent->Content, '[nohtml]');
            }
            
            foreach ($comments as &$comment) {
                if ($comment->ParentID > 0 && isset($parentMap[$comment->ParentID])) {
                    $comment->Parent = $parentMap[$comment->ParentID];
                }
                $comment->Content = TransferHTML($comment->Content, '[nohtml]');
            }
        } else {
            foreach ($comments as &$comment) {
                $comment->Content = TransferHTML($comment->Content, '[nohtml]');
            }
        }
    }
    
    if (function_exists('tpure_cache_set')) {
        tpure_cache_set($cacheKey, $comments, 1800);
    }
    
    return $comments;
}

/**
 * 批量预加载文章关联数据（解决N+1查询）
 */
function tpure_preload_article_relations($articles) {
    global $zbp;
    
    if (empty($articles)) {
        return $articles;
    }
    
    $cateIds = array();
    $authorIds = array();
    
    foreach ($articles as $article) {
        if (isset($article->CateID) && $article->CateID > 0) {
            $cateIds[] = $article->CateID;
        }
        if (isset($article->AuthorID) && $article->AuthorID > 0) {
            $authorIds[] = $article->AuthorID;
        }
    }
    
    $cateIds = array_unique($cateIds);
    $authorIds = array_unique($authorIds);
    
    // 批量加载分类
    $cateMap = array();
    if (!empty($cateIds)) {
        $categories = $zbp->GetCategoryList(
            '*',
            array(array('IN', 'cate_ID', $cateIds))
        );
        foreach ($categories as $cate) {
            $cateMap[$cate->ID] = $cate;
        }
    }
    
    // 批量加载作者
    $authorMap = array();
    if (!empty($authorIds)) {
        $authors = $zbp->GetMemberList(
            '*',
            array(array('IN', 'mem_ID', $authorIds))
        );
        foreach ($authors as $author) {
            $authorMap[$author->ID] = $author;
        }
    }
    
    // 关联数据
    foreach ($articles as &$article) {
        if (isset($cateMap[$article->CateID])) {
            $article->_preloadedCategory = $cateMap[$article->CateID];
        }
        if (isset($authorMap[$article->AuthorID])) {
            $article->_preloadedAuthor = $authorMap[$article->AuthorID];
        }
    }
    
    return $articles;
}

/**
 * 游标分页查询文章列表（优化OFFSET性能）
 * 
 * @param int $lastId 最后一条记录ID
 * @param int $limit 每页数量
 * @param int $cateId 分类ID
 * @return array 包含articles, next_cursor, has_more
 */
function tpure_GetArticleListCursor($lastId = 0, $limit = 10, $cateId = 0) {
    global $zbp;
    
    $w = array(
        array('=', 'log_Type', 0),
        array('=', 'log_Status', 0),
    );
    
    if ($cateId > 0) {
        $w[] = array('=', 'log_CateID', $cateId);
    }
    
    // 游标分页：使用ID代替OFFSET
    if ($lastId > 0) {
        $w[] = array('<', 'log_ID', $lastId);
    }
    
    // 多查一条判断是否还有下一页
    $articles = $zbp->GetArticleList(
        '*',
        $w,
        array('log_ID' => 'DESC'),
        array($limit + 1)
    );
    
    $hasMore = count($articles) > $limit;
    if ($hasMore) {
        array_pop($articles);
    }
    
    $nextCursor = 0;
    if (!empty($articles)) {
        $lastArticle = end($articles);
        $nextCursor = $lastArticle->ID;
    }
    
    if (!empty($articles)) {
        $articles = tpure_preload_article_relations($articles);
    }
    
    return array(
        'articles' => $articles,
        'next_cursor' => $nextCursor,
        'has_more' => $hasMore
    );
}

// ==================== 缓存失效策略 ====================

/**
 * 注册缓存失效钩子
 * 
 * @since 5.0.7
 */
function tpure_register_cache_hooks() {
    // 检查 Z-BlogPHP 函数是否可用
    if (!function_exists('Add_Filter_Plugin')) {
        return;
    }
    
    // 文章发布/更新时清除缓存
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Succeed', 'tpure_invalidate_article_cache');
    
    // 文章删除时清除缓存
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Del', 'tpure_invalidate_article_cache');
    
    // 评论发布时清除缓存
    Add_Filter_Plugin('Filter_Plugin_PostComment_Succeed', 'tpure_invalidate_comment_cache');
    
    // 评论删除时清除缓存
    Add_Filter_Plugin('Filter_Plugin_Comment_Del', 'tpure_invalidate_comment_cache');
}

/**
 * 文章相关缓存失效
 * 
 * @param object $article 文章对象
 * @return void
 * @since 5.0.7
 */
function tpure_invalidate_article_cache($article = null) {
    if (!class_exists('TpureCache')) {
        return;
    }
    
    // 🔧 临时注释：缓存清除功能暂时禁用
    // TpureCache::delete('hot_articles_5_view');
    // TpureCache::delete('hot_articles_5_cmt');
    // TpureCache::delete('hot_articles_10_view');
    // TpureCache::delete('hot_articles_10_cmt');
    // TpureCache::delete('rec_articles');
    // TpureCache::delete('archive_list');
    // TpureCache::forgetByTag('article_list');
    
    // 🔧 临时注释：缓存清除功能暂时禁用
    // if ($article && isset($article->CateID) && $article->CateID > 0) {
    //     TpureCache::forgetByTag('category_' . $article->CateID);
    // }
    
    // 记录日志
    if (function_exists('tpure_log')) {
        $articleId = $article ? $article->ID : 'unknown';
        tpure_log("文章 #{$articleId} 相关缓存已清除", 'INFO');
    }
}

/**
 * 评论相关缓存失效
 * 
 * @param object $comment 评论对象
 * @return void
 * @since 5.0.7
 */
function tpure_invalidate_comment_cache($comment = null) {
    if (!class_exists('TpureCache')) {
        return;
    }
    
    // 🔧 临时注释：缓存清除功能暂时禁用
    // TpureCache::delete('new_comments_5');
    // TpureCache::delete('new_comments_10');
    // TpureCache::delete('new_comments_15');
    // TpureCache::delete('hot_articles_5_cmt');
    // TpureCache::delete('hot_articles_10_cmt');
    // TpureCache::forgetByTag('comment_list');
    
    // 记录日志
    if (function_exists('tpure_log')) {
        $commentId = $comment ? $comment->ID : 'unknown';
        tpure_log("评论 #{$commentId} 相关缓存已清除", 'INFO');
    }
}

/**
 * 清除所有主题缓存（管理员操作）
 * 
 * @return bool
 * @since 5.0.7
 */
function tpure_clear_all_cache() {
    if (!class_exists('TpureCache')) {
        return false;
    }
    
    $result = TpureCache::flush();
    
    // 记录日志
    if (function_exists('tpure_log') && $result) {
        tpure_log("所有主题缓存已手动清除", 'INFO');
    }
    
    return $result;
}

/**
 * 获取缓存统计信息
 * 
 * @return array
 * @since 5.0.7
 */
function tpure_get_cache_stats() {
    if (!class_exists('TpureCache')) {
        return array();
    }
    
    return TpureCache::stats();
}

// ==================== 调试辅助函数 ====================

/**
 * 开启错误显示（调试用）
 * 
 * 用于开发和调试阶段显示详细的错误信息
 * 生产环境请务必关闭！
 * 
 * @param bool $display 是否显示错误 true=显示 false=隐藏
 * @param int $level 错误级别 E_ALL=所有错误 E_ERROR=仅严重错误
 * @return void
 * @since 5.0.7
 * 
 * @example
 * ```php
 * // 开启所有错误显示（开发环境）
 * tpure_enable_error_display(true, E_ALL);
 * 
 * // 仅显示严重错误
 * tpure_enable_error_display(true, E_ERROR);
 * 
 * // 关闭错误显示（生产环境）
 * tpure_enable_error_display(false);
 * ```
 */
function tpure_enable_error_display($display = true, $level = E_ALL) {
    if ($display) {
        // 开启错误报告
        error_reporting($level);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        
        // 记录日志
        if (function_exists('tpure_log')) {
            tpure_log('错误显示已开启（调试模式）', 'WARNING');
        }
    } else {
        // 关闭错误显示
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        
        // 记录日志
        if (function_exists('tpure_log')) {
            tpure_log('错误显示已关闭（生产模式）', 'INFO');
        }
    }
}

/**
 * 调试输出变量内容
 * 
 * 格式化输出变量，方便调试
 * 
 * @param mixed $var 要输出的变量
 * @param string $label 标签（可选）
 * @param bool $return 是否返回字符串而不直接输出
 * @return string|void
 * @since 5.0.7
 * 
 * @example
 * ```php
 * $data = array('name' => 'test', 'value' => 123);
 * tpure_debug($data, '数据内容');
 * ```
 */
function tpure_debug($var, $label = '', $return = false) {
    $output = '';
    
    // 添加标签
    if (!empty($label)) {
        $output .= "<h3 style='color:#2196f3;margin:10px 0;'>DEBUG: {$label}</h3>";
    }
    
    // 格式化输出
    $output .= '<pre style="background:#f5f5f5;padding:15px;border:1px solid #ddd;border-radius:4px;margin:10px 0;overflow:auto;">';
    $output .= htmlspecialchars(print_r($var, true));
    $output .= '</pre>';
    
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * 记录调试信息到浏览器控制台
 * 
 * @param mixed $data 要记录的数据
 * @param string $type 类型 log|info|warn|error
 * @return void
 * @since 5.0.7
 * 
 * @example
 * ```php
 * tpure_console_log('这是一条日志');
 * tpure_console_log('警告信息', 'warn');
 * tpure_console_log(['data' => 'value'], 'info');
 * ```
 */
function tpure_console_log($data, $type = 'log') {
    $validTypes = array('log', 'info', 'warn', 'error');
    if (!in_array($type, $validTypes)) {
        $type = 'log';
    }
    
    // 转换为JSON
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    // 输出到浏览器控制台
    echo "<script>console.{$type}(" . $json . ");</script>";
}

/**
 * 检查调试模式是否开启
 * 
 * @return bool
 * @since 5.0.7
 */
function tpure_is_debug_mode() {
    global $zbp;
    
    // 检查 Z-BlogPHP 调试模式
    if (isset($zbp->option['ZC_DEBUG_MODE']) && $zbp->option['ZC_DEBUG_MODE']) {
        return true;
    }
    
    // 检查主题配置
    try {
        $config = $zbp->Config('tpure');
        if (isset($config->PostDEBUGMODE) && $config->PostDEBUGMODE == '1') {
            return true;
        }
    } catch (Exception $e) {
        // 配置不存在
    }
    
    // 检查 PHP 错误显示设置
    if (ini_get('display_errors') == '1') {
        return true;
    }
    
    return false;
}

/**
 * 性能计时器（开始）
 * 
 * @param string $name 计时器名称
 * @return void
 * @since 5.0.7
 * 
 * @example
 * ```php
 * tpure_timer_start('database_query');
 * // ... 执行查询 ...
 * $time = tpure_timer_end('database_query');
 * echo "查询耗时: {$time}ms";
 * ```
 */
function tpure_timer_start($name = 'default') {
    global $tpure_timers;
    
    if (!isset($tpure_timers)) {
        $tpure_timers = array();
    }
    
    $tpure_timers[$name] = microtime(true);
}

/**
 * 性能计时器（结束）
 * 
 * @param string $name 计时器名称
 * @param bool $format 是否格式化输出
 * @return float|string 耗时（毫秒）
 * @since 5.0.7
 */
function tpure_timer_end($name = 'default', $format = false) {
    global $tpure_timers;
    
    if (!isset($tpure_timers[$name])) {
        return 0;
    }
    
    $elapsed = (microtime(true) - $tpure_timers[$name]) * 1000; // 转换为毫秒
    
    // 清除计时器
    unset($tpure_timers[$name]);
    
    if ($format) {
        return number_format($elapsed, 2) . ' ms';
    }
    
    return $elapsed;
}

/**
 * 内存使用监控
 * 
 * @param bool $peak 是否返回峰值内存
 * @param bool $format 是否格式化输出
 * @return int|string 内存使用量（字节或格式化字符串）
 * @since 5.0.7
 * 
 * @example
 * ```php
 * echo '当前内存: ' . tpure_memory_usage(false, true);
 * echo '峰值内存: ' . tpure_memory_usage(true, true);
 * ```
 */
function tpure_memory_usage($peak = false, $format = false) {
    $bytes = $peak ? memory_get_peak_usage(true) : memory_get_usage(true);
    
    if ($format) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    
    return $bytes;
}

/**
 * SQL 查询日志记录
 * 
 * @param string $sql SQL语句
 * @param float $time 执行时间（毫秒）
 * @return void
 * @since 5.0.7
 */
function tpure_log_sql($sql, $time = 0) {
    global $tpure_sql_queries;
    
    if (!isset($tpure_sql_queries)) {
        $tpure_sql_queries = array();
    }
    
    $tpure_sql_queries[] = array(
        'sql' => $sql,
        'time' => $time,
        'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
    );
    
    // 记录到日志文件
    if (function_exists('tpure_log')) {
        $logMsg = sprintf('SQL[%.2fms]: %s', $time, $sql);
        tpure_log($logMsg, 'DEBUG');
    }
}

/**
 * 获取所有 SQL 查询记录
 * 
 * @return array
 * @since 5.0.7
 */
function tpure_get_sql_queries() {
    global $tpure_sql_queries;
    
    return isset($tpure_sql_queries) ? $tpure_sql_queries : array();
}

/**
 * 输出调试信息面板
 * 
 * 在页面底部显示性能和调试信息
 * 
 * @return void
 * @since 5.0.7
 */
function tpure_debug_panel() {
    // 仅在调试模式下显示
    if (!tpure_is_debug_mode()) {
        return;
    }
    
    $memory = tpure_memory_usage(false, true);
    $memoryPeak = tpure_memory_usage(true, true);
    $sqlQueries = tpure_get_sql_queries();
    $sqlCount = count($sqlQueries);
    $sqlTime = 0;
    
    foreach ($sqlQueries as $query) {
        $sqlTime += $query['time'];
    }
    
    ?>
    <div id="tpure-debug-panel" style="position:fixed;bottom:0;left:0;right:0;background:#2d2d2d;color:#f8f8f2;padding:10px 20px;font-family:monospace;font-size:12px;z-index:9999;border-top:3px solid #2196f3;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;gap:20px;">
                <span>⏱️ 查询数: <strong style="color:#2196f3;"><?php echo $sqlCount; ?></strong></span>
                <span>⚡ SQL耗时: <strong style="color:#4caf50;"><?php echo number_format($sqlTime, 2); ?> ms</strong></span>
                <span>💾 内存: <strong style="color:#ff9800;"><?php echo $memory; ?></strong></span>
                <span>📊 峰值: <strong style="color:#f44336;"><?php echo $memoryPeak; ?></strong></span>
            </div>
            <button onclick="document.getElementById('tpure-debug-panel').style.display='none'" style="background:#f44336;color:#fff;border:none;padding:5px 15px;border-radius:3px;cursor:pointer;">关闭</button>
        </div>
    </div>
    <?php
}

/**
 * 安全的 var_dump（带样式）
 * 
 * @param mixed $var 要输出的变量
 * @param bool $exit 是否立即退出
 * @return void
 * @since 5.0.7
 */
function tpure_dump($var, $exit = false) {
    echo '<pre style="background:#2d2d2d;color:#f8f8f2;padding:20px;border-radius:4px;margin:20px;overflow:auto;font-family:Consolas,monospace;line-height:1.5;">';
    var_dump($var);
    echo '</pre>';
    
    if ($exit) {
        die();
    }
}

/**
 * 生成响应式图片标签（支持 WebP/AVIF）
 * 
 * @param object $article 文章对象
 * @param array $options 配置选项
 * @return string 完整的 <picture> HTML 标签
 * @since 5.12
 */
function tpure_responsive_image($article, $options = array()) {
    global $zbp;
    
    // 默认配置
    $defaults = array(
        'width' => 400,
        'height' => 300,
        'lazy' => true,
        'class' => 'thumbnail-img',
        'check_exists' => false  // 是否检查文件存在（会影响性能）
    );
    
    $options = array_merge($defaults, $options);
    
    // 获取原始缩略图URL
    $thumbSrc = tpure_Thumb($article);
    
    if (empty($thumbSrc)) {
        return '';
    }
    
    // 生成不同格式的图片URL
    $thumbWebp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $thumbSrc);
    $thumbAvif = preg_replace('/\.(jpg|jpeg|png)$/i', '.avif', $thumbSrc);
    
    // 构建 HTML
    $html = '<picture>';
    
    // AVIF 格式（最优）
    if ($options['check_exists']) {
        $avifPath = str_replace($zbp->host, ZBP_PATH, $thumbAvif);
        if (file_exists($avifPath)) {
            $html .= sprintf('<source srcset="%s" type="image/avif">', tpure_esc_url($thumbAvif));
        }
    } else {
        // 不检查文件，直接输出（推荐，性能更好）
        $html .= sprintf('<source srcset="%s" type="image/avif">', tpure_esc_url($thumbAvif));
    }
    
    // WebP 格式（次优）
    if ($options['check_exists']) {
        $webpPath = str_replace($zbp->host, ZBP_PATH, $thumbWebp);
        if (file_exists($webpPath)) {
            $html .= sprintf('<source srcset="%s" type="image/webp">', tpure_esc_url($thumbWebp));
        }
    } else {
        $html .= sprintf('<source srcset="%s" type="image/webp">', tpure_esc_url($thumbWebp));
    }
    
    // 原图（兜底）
    $html .= '<img ';
    $html .= sprintf('src="%s" ', tpure_esc_url($thumbSrc));
    $html .= sprintf('alt="%s" ', tpure_esc_attr($article->Title));
    
    // 宽高属性（防止 CLS 布局偏移）
    if ($options['width']) {
        $html .= sprintf('width="%d" ', $options['width']);
    }
    if ($options['height']) {
        $html .= sprintf('height="%d" ', $options['height']);
    }
    
    // 懒加载
    if ($options['lazy']) {
        $html .= 'loading="lazy" ';
        $html .= 'decoding="async" ';
    }
    
    // CSS 类
    if ($options['class']) {
        $html .= sprintf('class="%s" ', tpure_esc_attr($options['class']));
    }
    
    $html .= '>';
    $html .= '</picture>';
    
    return $html;
}

/**
 * 快捷函数：输出响应式缩略图
 * 
 * @param object $article 文章对象
 * @param int $width 宽度（可选）
 * @param int $height 高度（可选）
 * @return void
 * @since 5.12
 */
function tpure_show_responsive_thumb($article, $width = 400, $height = 300) {
    echo tpure_responsive_image($article, array(
        'width' => $width,
        'height' => $height
    ));
}

