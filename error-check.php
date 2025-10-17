<?php
/**
 * 错误诊断 - 查看首页加载问题
 */

// 显示所有错误
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>🔍 首页加载诊断</h1><hr>';

// 1. 加载 Z-BlogPHP
require dirname(__FILE__) . '/../../../zb_system/function/c_system_base.php';
$zbp->Load();

// 2. 模拟首页加载
echo '<h2>测试首页文章列表...</h2>';

try {
    // 获取文章列表（模拟首页）
    $articles = $zbp->GetArticleList(
        array('*'),
        array(array('=', 'log_Status', 0)),
        array('log_PostTime' => 'DESC'),
        array(10),
        null
    );
    
    echo '<p>✅ 成功获取 ' . count($articles) . ' 篇文章</p>';
    
    // 测试每篇文章的渲染
    foreach ($articles as $article) {
        echo '<div style="padding:10px; margin:10px 0; background:#f8f9fa;">';
        echo '<strong>文章 ID: ' . $article->ID . '</strong><br>';
        echo '标题: ' . $article->Title . '<br>';
        
        // 测试缩略图函数（可能的问题点）
        if (function_exists('tpure_Thumb')) {
            try {
                $thumb = tpure_Thumb($article);
                echo '缩略图: ' . ($thumb ? '✅ ' . $thumb : '❌ 无') . '<br>';
            } catch (Exception $e) {
                echo '❌ 缩略图错误: ' . $e->getMessage() . '<br>';
            }
        }
        
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<p style="color:red;">❌ 错误: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<hr>';
echo '<p><a href="' . $zbp->host . '">返回首页</a></p>';
?>

