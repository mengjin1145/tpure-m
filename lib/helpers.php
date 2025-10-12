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
 * Z-Blog 1.7+ 版本缩略图
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
 * 从关键词位置开始截取字符串
 * 
 * @param string $string 字符串
 * @param string $keyword 关键词
 * @param int $length 长度
 * @return string
 */
function tpure_SubStrStartUTF8($string, $keyword, $length) {
    if (empty($keyword)) {
        return mb_substr($string, 0, $length, 'UTF-8');
    }
    
    $pos = mb_stripos($string, $keyword, 0, 'UTF-8');
    
    if ($pos !== false) {
        // 从关键词前20个字符开始
        $start = max(0, $pos - 20);
        return mb_substr($string, $start, $length, 'UTF-8');
    }
    
    return mb_substr($string, 0, $length, 'UTF-8');
}

/**
 * Unicode字符转换
 * 
 * @param string $str 字符串
 * @return string
 */
function tpure_CodeToString($str) {
    $to = array(" ", "  ", "   ", "    ", "\"", "<", ">", "&");
    $pre = array('&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;', '&quot;', '&lt', '&gt', '&amp');
    
    return str_replace($pre, $to, $str);
}

