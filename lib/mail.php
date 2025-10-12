<?php
/**
 * Tpure 主题 - 邮件处理函数
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * 发送邮件（安全版本）
 * 
 * @param string $to 收件人
 * @param string $subject 主题
 * @param string $content 内容
 * @return bool 是否发送成功
 */
function tpure_send_mail($to, $subject, $content) {
    global $zbp;
    
    // 验证收件人邮箱
    $to = tpure_validate_email($to);
    if ($to === false) {
        tpure_security_log('邮件发送失败: 无效的收件人地址', 'warning');
        return false;
    }
    
    // 过滤主题（防止邮件头注入）
    $subject = tpure_sanitize_email_header($subject);
    if (empty($subject)) {
        $subject = $zbp->name;
    }
    
    // 限制主题长度
    if (mb_strlen($subject, 'UTF-8') > 200) {
        $subject = mb_substr($subject, 0, 200, 'UTF-8');
    }
    
    // 清理内容（移除危险标签，保留安全HTML）
    $content = tpure_kses($content);
    
    try {
        // 调用phpmailer发送邮件
        require_once dirname(dirname(__FILE__)) . '/plugin/phpmailer/sendmail.php';
        return tpure_SendEmail($to, $subject, $content);
    } catch (Exception $e) {
        tpure_security_log('邮件发送异常: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 过滤邮件头（防注入）
 * 
 * @param string $str 要过滤的字符串
 * @return string 过滤后的字符串
 */
function tpure_sanitize_email_header($str) {
    // 移除可能导致邮件头注入的字符
    $str = str_replace(array("\r", "\n", "%0a", "%0d", "%0A", "%0D"), '', $str);
    
    // 移除邮件头关键字
    $str = preg_replace('/\b(to|cc|bcc|from|subject|content-type|mime-version):/i', '', $str);
    
    return trim($str);
}

/**
 * 新文章邮件通知（安全版本）
 */
function tpure_ArticleSendmail($article) {
    global $zbp;
    
    // 检查是否启用邮件通知
    if ($zbp->Config('tpure')->PostMAILON != '1') {
        return;
    }
    
    // 只处理新文章
    if (!isset($GLOBALS['is_new_article']) || $GLOBALS['is_new_article'] !== true) {
        return;
    }
    
    // 检查是否启用新文章通知
    if (!$zbp->Config('tpure')->PostNEWARTICLEMAILSENDON) {
        return;
    }
    
    $mailto = $zbp->Config('tpure')->MAIL_TO;
    
    // 验证收件人
    $mailto = tpure_validate_email($mailto);
    if ($mailto === false) {
        tpure_security_log('新文章通知: 无效的收件人地址', 'warning');
        return;
    }
    
    // 构建邮件内容
    $subject = tpure_sanitize_email_header(
        $zbp->user->StaticName . '发布了一篇新文章 《' . $article->Title . '》'
    );
    
    $intro = mb_substr(
        tpure_sanitize_text($article->Intro),
        0,
        $zbp->Config('tpure')->PostINTRONUM ?: 150,
        'UTF-8'
    ) . '...';
    
    $logo = '';
    if ($zbp->Config('tpure')->PostLOGOON) {
        $logo_url = tpure_esc_url($zbp->Config('tpure')->PostLOGO);
        $logo = '<img src="' . $logo_url . '" style="height:40px;line-height:0;border:none;display:block;">';
    } else {
        $logo = '<span style="font-size:22px; color:#666;">' . tpure_esc_html($zbp->name) . '</span>';
    }
    
    $content = sprintf(
        '<table width="700" align="center" cellpadding="0" cellspacing="0" style="margin-top:30px; border:1px solid rgb(230,230,230);">
            <tbody>
                <tr><td>
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tbody><tr>
                            <td width="30"></td>
                            <td width="640" style="padding:20px 0 10px;">
                                <a href="%s" target="_blank" style="text-decoration:none; display:inline-block; vertical-align:top;">%s</a>
                            </td>
                            <td width="30"></td>
                        </tr></tbody>
                    </table>
                </td></tr>
                <tr><td>
                    <table><tbody>
                        <tr>
                            <td width="30"></td>
                            <td width="640">
                                <p style="margin:0; padding:30px 0 0px; font-size:14px; color:#151515; font-family:microsoft yahei; font-weight:bold; border-top:1px solid #eee;">管理员，你好！</p>
                                <p style="font-size:14px; color:#151515; font-family:microsoft yahei;">
                                    %s 在 [ %s ] 发布了新文章 <em style="font-weight:bold;font-style:normal; margin: 0 5px;">《%s》</em>：
                                </p>
                            </td>
                            <td width="30"></td>
                        </tr>
                        <tr>
                            <td width="30"></td>
                            <td width="640">
                                <p style="margin:0 0 20px; padding:15px 20px; font-size:16px; color:#7d8795; font-family:microsoft yahei; line-height:22px; border:1px solid #e6e6e6; background-color:#f5f5f5;">%s</p>
                                <p style="margin:0 0 30px; text-align:center;">
                                    <a href="%s" target="_blank" style="margin:0 auto; padding:12px 25px; font-size:14px; color:#fff; font-family:microsoft yahei; font-weight:bold; text-decoration:none; text-transform:capitalize; border:0; border-radius:50px; cursor:pointer; box-shadow:0 1px 2px rgba(0, 0, 0, 0.1); background-color:#206ffd; background-image:linear-gradient(to top, #206dfd 0%%, #2992ff 100%%); display: inline-block;">查看文章的完整内容</a>
                                </p>
                            </td>
                            <td width="30"></td>
                        </tr>
                    </tbody></table>
                </td></tr>
                <tr><td>
                    <table align="center" cellspacing="0" style="background-color:rgb(245,245,245); line-height: 28px; padding: 13px 23px; color: #7d8795; font-weight:500; border-top:1px solid #e6e6e6;" width="100%%" bgcolor="#e6e6e6">
                        <tbody><tr>
                            <td style="font-family:microsoft yahei; font-size:14px; vertical-align:top; text-align:center;" valign="top">%s - %s</td>
                        </tr></tbody>
                    </table>
                </td></tr>
            </tbody>
        </table>',
        tpure_esc_url($zbp->host),
        $logo,
        tpure_esc_html($zbp->user->StaticName),
        tpure_esc_html($zbp->name),
        tpure_esc_html($article->Title),
        tpure_esc_html($intro),
        tpure_esc_url($article->Url),
        tpure_esc_html($zbp->name),
        tpure_esc_html($zbp->subname)
    );
    
    tpure_send_mail($mailto, $subject, $content);
}

/**
 * 评论邮件通知（安全版本）
 */
function tpure_CmtSendmail($cmt) {
    global $zbp;
    
    // 检查是否启用邮件通知
    if ($zbp->Config('tpure')->PostMAILON != '1') {
        return;
    }
    
    if (!$zbp->Config('tpure')->PostCMTMAILSENDON) {
        return;
    }
    
    $logid = tpure_validate_id($cmt->LogID);
    if ($logid === false) {
        return;
    }
    
    $log = new Post();
    $log->LoadinfoByID($logid);
    $log_author = $zbp->GetPostByID($logid)->Author;
    
    // 验证作者邮箱
    $author_email = tpure_validate_email($log_author->Email);
    if ($author_email === false || $author_email === 'null@null.com') {
        return;
    }
    
    $subject = tpure_sanitize_email_header(
        '日志《' . $log->Title . '》收到了新的评论'
    );
    
    $content = '评论内容：' . tpure_esc_html($cmt->Content);
    
    // 这里可以构建更完整的HTML邮件模板
    // 为简化代码，这里只展示核心逻辑
    
    tpure_send_mail($author_email, $subject, $content);
}

/**
 * 新文章核心判断
 */
function tpure_ArticleCore($article) {
    if ($article->ID > 0) {
        $GLOBALS['is_new_article'] = false;
    } else {
        $GLOBALS['is_new_article'] = true;
    }
}

