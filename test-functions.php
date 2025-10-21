<?php
/**
 * 主题函数检测脚本
 * 检测main.php需要的所有函数是否存在
 */

// 加载Z-BlogPHP核心
require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

// 需要检测的函数列表
$required_functions = array(
    // 主菜单相关
    'tpure_SubMenu',
    'tpure_AddMenu',
    'tpure_Header',
    
    // 配置页面需要的函数
    'tpure_Exclude_CategorySelect',
    'tpure_OutputOptionItemsOfCategories',
    'tpure_color',
    'tpure_CreateModule',
    'tpure_SideContent',
    
    // 其他关键函数  
    'tpure_navcate',
    'tpure_Refresh',
    'tpure_ErrorCode',
    'tpure_MemberAvatar',
    'tpure_Thumb',
    'tpure_TimeAgo',
    'tpure_isMobile',
    'tpure_IsToday',
);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>主题函数检测</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #0188fb; padding-bottom: 10px; }
        .result { margin: 20px 0; }
        .function-item { padding: 10px; margin: 5px 0; border-radius: 4px; }
        .exists { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .missing { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .summary { padding: 15px; margin: 20px 0; border-radius: 4px; font-size: 16px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 20px 0; border-radius: 4px; border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Tpure主题函数检测</h1>
        
        <div class="info">
            <strong>检测目的：</strong>确保main.php（主题配置页面）需要的所有函数都已正确加载。<br>
            <strong>主题目录：</strong><?php echo dirname(__FILE__); ?><br>
            <strong>检测时间：</strong><?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <div class="result">
            <h2>函数检测结果：</h2>
            <?php
            $missing_count = 0;
            $exists_count = 0;
            
            foreach ($required_functions as $func) {
                if (function_exists($func)) {
                    echo "<div class='function-item exists'>✓ {$func}() - 存在</div>";
                    $exists_count++;
                } else {
                    echo "<div class='function-item missing'>✗ {$func}() - <strong>缺失</strong></div>";
                    $missing_count++;
                }
            }
            ?>
        </div>
        
        <div class="summary <?php echo $missing_count > 0 ? 'error' : 'success'; ?>">
            检测完成：共 <?php echo count($required_functions); ?> 个函数，
            <span style="color: #28a745;">存在 <?php echo $exists_count; ?> 个</span>，
            <span style="color: #dc3545;">缺失 <?php echo $missing_count; ?> 个</span>
        </div>
        
        <?php if ($missing_count > 0): ?>
            <div class="info">
                <h3>🔧 修复建议：</h3>
                <ol>
                    <li>确保 <code>lib/functions-missing.php</code> 文件存在</li>
                    <li>确保 <code>include.php</code> 中已添加该文件的加载</li>
                    <li>清除编译缓存：删除 <code>zb_users/cache/compiled/</code> 目录</li>
                    <li>刷新页面重新检测</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="info">
                <h3>✅ 所有函数都已正确加载！</h3>
                <p>现在可以正常访问主题配置页面了：<br>
                <a href="main.php?act=base" target="_blank" style="color: #0188fb;">点击这里访问主题配置</a></p>
            </div>
        <?php endif; ?>
        
        <div class="result">
            <h3>已加载的文件列表：</h3>
            <div style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;">
                <?php
                $included_files = get_included_files();
                foreach ($included_files as $file) {
                    if (strpos($file, 'tpure') !== false) {
                        echo htmlspecialchars($file) . "<br>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>

