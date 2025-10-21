<?php
/**
 * 简化版诊断脚本 - 不加载主题，直接测试
 */
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 简化版诊断</h1>";

// 1. 检查文件是否存在
echo "<h2>1. 文件存在性检查：</h2>";
$files = array(
    'lib/functions-missing.php',
    'lib/functions-core.php',
    'lib/theme-admin.php',
    'lib/helpers.php',
    'include.php',
);

foreach ($files as $file) {
    $exists = file_exists($file);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✓ 存在' : '✗ 不存在';
    $size = $exists ? ' (' . number_format(filesize($file)) . ' bytes)' : '';
    echo "<div style='color: {$color};'>{$file}: {$status}{$size}</div>";
}

// 2. 尝试直接包含文件并检查语法
echo "<h2>2. 文件语法检查：</h2>";

// 先定义必要的常量
if (!defined('ZBP_PATH')) {
    define('ZBP_PATH', dirname(__FILE__) . '/../../../zb_system/');
}
if (!defined('TPURE_DIR')) {
    define('TPURE_DIR', dirname(__FILE__) . '/');
}

// 逐个加载文件
$loadOrder = array(
    'lib/helpers.php',
    'lib/functions-core.php',
    'lib/functions-missing.php',
);

foreach ($loadOrder as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "<div style='color: green;'>✓ {$file} 加载成功</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>✗ {$file} 加载失败: " . $e->getMessage() . "</div>";
        } catch (Error $e) {
            echo "<div style='color: red;'>✗ {$file} 语法错误: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div style='color: orange;'>⚠ {$file} 文件不存在</div>";
    }
}

// 3. 检查缺失的函数
echo "<h2>3. 函数存在性检查：</h2>";
$missingFunctions = array(
    'tpure_color',
    'tpure_CreateModule',
    'tpure_SideContent',
    'tpure_navcate',
    'tpure_Refresh',
    'tpure_ErrorCode',
);

foreach ($missingFunctions as $func) {
    $exists = function_exists($func);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✓ 存在' : '✗ 缺失';
    echo "<div style='color: {$color};'>{$func}(): {$status}</div>";
}

// 4. 检查include.php的关键配置
echo "<h2>4. include.php 配置检查：</h2>";
$includeContent = file_get_contents('include.php');

// 检查是否加载了functions-missing.php
if (strpos($includeContent, "functions-missing.php") !== false) {
    echo "<div style='color: green;'>✓ include.php 包含 functions-missing.php 加载代码</div>";
} else {
    echo "<div style='color: red;'>✗ include.php 没有加载 functions-missing.php</div>";
}

// 检查TPURE_DIR定义
$defineCount = substr_count($includeContent, "define('TPURE_DIR'");
if ($defineCount == 1) {
    echo "<div style='color: green;'>✓ TPURE_DIR 只定义了 {$defineCount} 次</div>";
} else {
    echo "<div style='color: orange;'>⚠ TPURE_DIR 定义了 {$defineCount} 次（应该只有1次）</div>";
}

echo "<h2>5. 诊断建议：</h2>";
$allFunctionsExist = true;
foreach ($missingFunctions as $func) {
    if (!function_exists($func)) {
        $allFunctionsExist = false;
        break;
    }
}

if ($allFunctionsExist) {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<strong>✅ 所有函数都已加载！</strong><br>";
    echo "现在可以尝试访问主题配置页面了。";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<strong>❌ 还有函数缺失！</strong><br>";
    echo "请确保：<br>";
    echo "1. <code>lib/functions-missing.php</code> 文件已上传到服务器<br>";
    echo "2. 文件权限正确（644 或 755）<br>";
    echo "3. 文件内容完整（应该是635行）<br>";
    echo "4. 清除 <code>zb_users/cache/compiled/tpure/</code> 目录<br>";
    echo "</div>";
}
?>


