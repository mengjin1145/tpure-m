<?php
/**
 * Tpure 主题 - 安全函数库
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * 安全转义输出（防XSS）
 * 
 * @param string $string 要转义的字符串
 * @param bool $doubleEncode 是否双重编码
 * @return string 转义后的字符串
 */
function tpure_esc_html($string, $doubleEncode = true) {
    if ($string === null || $string === '') {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8', $doubleEncode);
}

/**
 * 转义属性值（防XSS）
 * 
 * @param string $string 要转义的字符串
 * @return string 转义后的字符串
 */
function tpure_esc_attr($string) {
    if ($string === null || $string === '') {
        return '';
    }
    $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    $string = str_replace(array("\r", "\n"), '', $string);
    return $string;
}

/**
 * 转义URL（防XSS）
 * 
 * @param string $url URL地址
 * @return string 转义后的URL
 */
function tpure_esc_url($url) {
    if ($url === null || $url === '') {
        return '';
    }
    
    // 移除不可见字符
    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);
    
    // 移除危险协议
    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = str_replace($strip, '', $url);
    $url = str_replace(';//', '://', $url);
    
    // 只允许安全的协议
    $allowed_protocols = array('http', 'https', 'ftp', 'ftps', 'mailto', 'tel');
    if (preg_match('/^([a-z0-9]+):/', $url, $matches)) {
        if (!in_array($matches[1], $allowed_protocols)) {
            return '';
        }
    }
    
    return $url;
}

/**
 * 清理文本内容（去除HTML标签和脚本）
 * 
 * @param string $string 要清理的字符串
 * @return string 清理后的字符串
 */
function tpure_sanitize_text($string) {
    if ($string === null || $string === '') {
        return '';
    }
    
    // 去除所有HTML标签
    $string = strip_tags($string);
    
    // 去除多余的空白
    $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    
    return trim($string);
}

/**
 * 验证整数ID（防SQL注入）
 * 
 * @param mixed $id 要验证的ID
 * @return int|false 验证后的整数ID，失败返回false
 */
function tpure_validate_id($id) {
    if (!is_numeric($id)) {
        return false;
    }
    
    $id = intval($id);
    
    if ($id < 1) {
        return false;
    }
    
    return $id;
}

/**
 * 验证邮箱地址（防邮件注入）
 * 
 * @param string $email 邮箱地址
 * @return string|false 验证后的邮箱，失败返回false
 */
function tpure_validate_email($email) {
    if (empty($email)) {
        return false;
    }
    
    // 过滤危险字符
    $email = str_replace(array("\r", "\n", "%0a", "%0d", "bcc:", "to:", "cc:"), '', $email);
    
    // 验证邮箱格式
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    
    if ($email === false) {
        return false;
    }
    
    return $email;
}

/**
 * 验证文件上传
 * 
 * @param array $file $_FILES数组
 * @param array $options 配置选项
 * @return array 包含status和message的数组
 */
function tpure_validate_upload($file, $options = array()) {
    $defaults = array(
        'allowed_types' => array('image/jpeg', 'image/png', 'image/gif', 'image/webp'),
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_extensions' => array('jpg', 'jpeg', 'png', 'gif', 'webp')
    );
    
    $options = array_merge($defaults, $options);
    
    // 检查是否有错误
    if (!isset($file['error']) || is_array($file['error'])) {
        return array('status' => false, 'message' => '无效的文件上传参数');
    }
    
    // 检查上传错误
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return array('status' => false, 'message' => '没有文件被上传');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return array('status' => false, 'message' => '文件大小超过限制');
        default:
            return array('status' => false, 'message' => '文件上传出现未知错误');
    }
    
    // 检查文件大小
    if ($file['size'] > $options['max_size']) {
        return array('status' => false, 'message' => '文件大小超过' . ($options['max_size'] / 1024 / 1024) . 'MB限制');
    }
    
    // 检查MIME类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $options['allowed_types'])) {
        return array('status' => false, 'message' => '不支持的文件类型：' . $mime);
    }
    
    // 检查文件扩展名
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $options['allowed_extensions'])) {
        return array('status' => false, 'message' => '不支持的文件扩展名：' . $ext);
    }
    
    // 对于图片，验证是否真的是图片
    if (strpos($mime, 'image/') === 0) {
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return array('status' => false, 'message' => '文件不是有效的图片');
        }
    }
    
    return array('status' => true, 'message' => '验证通过');
}

/**
 * 生成安全的随机字符串
 * 
 * @param int $length 字符串长度
 * @return string 随机字符串
 */
function tpure_generate_token($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    } else {
        // 降级方案
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $token;
    }
}

/**
 * 验证CSRF令牌
 * 
 * @param string $token 要验证的令牌
 * @param string $action 操作名称
 * @return bool 是否验证通过
 */
function tpure_verify_token($token, $action = 'default') {
    global $zbp;
    
    if (empty($token)) {
        return false;
    }
    
    $stored_token = $zbp->cookie->get('tpure_token_' . $action);
    
    if (empty($stored_token)) {
        return false;
    }
    
    return hash_equals($stored_token, $token);
}

/**
 * 创建CSRF令牌
 * 
 * @param string $action 操作名称
 * @return string CSRF令牌
 */
function tpure_create_token($action = 'default') {
    global $zbp;
    
    $token = tpure_generate_token(32);
    $zbp->cookie->set('tpure_token_' . $action, $token, 3600);
    
    return $token;
}

/**
 * 清理HTML内容（保留安全标签）
 * 
 * @param string $html HTML内容
 * @param array $allowed_tags 允许的标签
 * @return string 清理后的HTML
 */
function tpure_kses($html, $allowed_tags = array()) {
    if (empty($allowed_tags)) {
        // 默认允许的安全标签
        $allowed_tags = array(
            'a' => array('href' => true, 'title' => true, 'target' => true),
            'abbr' => array('title' => true),
            'b' => array(),
            'blockquote' => array(),
            'br' => array(),
            'code' => array(),
            'div' => array('class' => true),
            'em' => array(),
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
            'i' => array(),
            'img' => array('src' => true, 'alt' => true, 'title' => true),
            'li' => array(),
            'ol' => array(),
            'p' => array(),
            'pre' => array(),
            'span' => array('class' => true),
            'strong' => array(),
            'ul' => array(),
        );
    }
    
    // 简化版本的HTML清理
    // 实际项目中建议使用HTMLPurifier等专业库
    $allowed = '<' . implode('><', array_keys($allowed_tags)) . '>';
    return strip_tags($html, $allowed);
}

/**
 * 记录安全日志
 * 
 * @param string $message 日志消息
 * @param string $level 日志级别（info/warning/error）
 */
function tpure_security_log($message, $level = 'warning') {
    global $zbp;
    
    $log_dir = $zbp->usersdir . 'cache/';
    $log_file = $log_dir . 'security.log';
    
    // 确保目录存在
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    // 日志大小限制（5MB）
    if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) {
        @rename($log_file, $log_file . '.' . date('Y-m-d-His') . '.bak');
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
    
    $log_entry = sprintf(
        "[%s] [%s] [IP:%s] [URI:%s] [UA:%s] %s\n",
        $timestamp,
        strtoupper($level),
        $ip,
        $uri,
        substr($user_agent, 0, 100),
        $message
    );
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

