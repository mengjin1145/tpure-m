<?php
/**
 * Tpure 主题 - Ajax处理函数
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * Ajax搜索处理
 */
function tpure_ajax_search() {
    global $zbp;
    
    // 获取并清理搜索关键词
    $q = isset($_POST['q']) ? tpure_sanitize_text($_POST['q']) : '';
    
    if (empty($q)) {
        echo json_encode(array('post' => array(), 'more' => false));
        exit;
    }
    
    // 构建搜索条件
    $w = array(
        array('search', 'log_Title', 'log_Content', $q),
        array('=', 'log_Status', 0),
    );
    
    // 搜索文章
    $articles = $zbp->GetArticleList('*', $w, array('log_PostTime' => 'DESC'), 6);
    
    $res = array('post' => array());
    
    foreach ($articles as $k => $article) {
        if ($k == 5) break;
        
        $intro = tpure_get_excerpt($article, $q);
        
        $res['post'][] = array(
            'title' => tpure_esc_html($article->Title),
            'img' => tpure_esc_url(tpure_Thumb($article)),
            'url' => tpure_esc_url($article->Url),
            'intro' => $intro
        );
    }
    
    $res['more'] = count($articles) > 5;
    
    header('Content-Type: application/json');
    echo json_encode($res);
    exit;
}

/**
 * Ajax上传处理（已加强安全验证）
 */
function tpure_ajax_upload() {
    global $zbp;
    
    // 权限检查
    if (!$zbp->CheckRights('UploadPst')) {
        tpure_security_log('未授权的文件上传尝试', 'error');
        echo json_encode(array('error' => '权限不足'));
        exit;
    }
    
    // 验证CSRF令牌（如果启用）
    // $token = $_POST['token'] ?? '';
    // if (!tpure_verify_token($token, 'upload')) {
    //     echo json_encode(array('error' => 'Invalid token'));
    //     exit;
    // }
    
    // 验证上传文件
    if (!isset($_FILES['file'])) {
        echo json_encode(array('error' => '没有文件被上传'));
        exit;
    }
    
    $validation = tpure_validate_upload($_FILES['file']);
    
    if (!$validation['status']) {
        tpure_security_log('文件上传验证失败: ' . $validation['message'], 'warning');
        echo json_encode(array('error' => $validation['message']));
        exit;
    }
    
    // 执行上传
    try {
        Add_Filter_Plugin('Filter_Plugin_Upload_SaveFile', 'tpure_Upload_SaveFile_Ajax');
        $_POST['auto_rename'] = 1;
        PostUpload();
        
        $url = isset($GLOBALS['tmp_ul']) ? $GLOBALS['tmp_ul']->Url : '';
        
        echo json_encode(array('url' => tpure_esc_url($url)));
    } catch (Exception $e) {
        tpure_security_log('文件上传失败: ' . $e->getMessage(), 'error');
        echo json_encode(array('error' => '文件上传失败'));
    }
    
    exit;
}

/**
 * Ajax上传文件保存钩子
 */
function tpure_Upload_SaveFile_Ajax($tmp, $ul) {
    $GLOBALS['tmp_ul'] = $ul;
}

/**
 * Ajax命令处理
 */
function tpure_CmdAjax($src) {
    // 搜索
    if ($src === 'tpure_search') {
        tpure_ajax_search();
    }
    
    // 上传
    if ($src === 'tpure_upload') {
        tpure_ajax_upload();
    }
}

/**
 * 获取文章摘要（搜索结果用）
 * 
 * @param object $article 文章对象
 * @param string $keyword 搜索关键词
 * @return string 高亮的摘要
 */
function tpure_get_excerpt($article, $keyword = '') {
    global $zbp;
    
    $intro_num = $zbp->Config('tpure')->PostINTRONUM ?: 110;
    
    // 获取文章内容
    $content = TransferHTML($article->Intro, '[nohtml]');
    
    // 查找关键词位置
    if (!empty($keyword)) {
        $pos = mb_stripos($content, $keyword, 0, 'UTF-8');
        if ($pos !== false) {
            // 从关键词前一点开始截取
            $start = max(0, $pos - 20);
            $content = mb_substr($content, $start, $intro_num, 'UTF-8');
        } else {
            $content = mb_substr($content, 0, $intro_num, 'UTF-8');
        }
    } else {
        $content = mb_substr($content, 0, $intro_num, 'UTF-8');
    }
    
    // 清理空白字符
    $content = preg_replace('/[\r\n\s]+/', ' ', trim($content)) . '...';
    
    // 高亮关键词
    if (!empty($keyword)) {
        $content = str_ireplace(
            tpure_esc_html($keyword), 
            '<mark>' . tpure_esc_html($keyword) . '</mark>', 
            tpure_esc_html($content)
        );
    } else {
        $content = tpure_esc_html($content);
    }
    
    return $content;
}

/**
 * JSON响应辅助函数
 */
function tpure_json_response($code, $msg = '', $data = '') {
    $json = array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    );
    
    header('Content-Type: application/json');
    echo json_encode($json);
    exit;
}

