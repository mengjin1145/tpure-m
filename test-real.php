<?php
/**
 * 真实环境测试 - 模拟 Z-BlogPHP 加载主题
 */

// 开启所有错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== 真实环境测试 ===\n\n";

// 智能检测 Z-BlogPHP 根目录
$currentDir = __DIR__;

echo "当前文件位置: {$currentDir}\n";

// 检查当前文件位置
if (strpos($currentDir, 'zb_users/theme/tpure') !== false || 
    strpos($currentDir, 'zb_users\\theme\\tpure') !== false) {
    // 文件在主题目录
    $zbpPath = dirname(dirname(dirname($currentDir))) . DIRECTORY_SEPARATOR;
    echo "检测: 文件在主题目录\n";
} else {
    // 文件在网站根目录
    $zbpPath = $currentDir . DIRECTORY_SEPARATOR;
    echo "检测: 文件在网站根目录\n";
}

echo "Z-BlogPHP 根目录: {$zbpPath}\n\n";

// 检查 Z-BlogPHP 是否存在
$zbpFile = $zbpPath . 'zb_system/function/c_system_base.php';

if (!file_exists($zbpFile)) {
    die("✗ Z-BlogPHP 未找到\n路径: {$zbpFile}\n");
}

echo "✓ Z-BlogPHP 存在\n";
echo "路径: {$zbpPath}\n\n";

// 加载 Z-BlogPHP
echo "=== 加载 Z-BlogPHP ===\n";

try {
    // 定义必要的常量
    define('ZBP_PATH', $zbpPath);
    define('ZBP_MANAGE', false);
    
    // 加载核心
    require $zbpFile;
    
    echo "✓ Z-BlogPHP 核心加载成功\n";
    echo "✓ \$zbp 对象已创建\n\n";
    
    // 检查主题
    echo "=== 检查主题 ===\n";
    echo "当前主题: {$zbp->theme}\n";
    
    if ($zbp->theme !== 'tpure') {
        echo "\n⚠️  警告: 当前主题不是 tpure，请在后台启用 tpure 主题\n";
    } else {
        echo "✓ tpure 主题已启用\n";
    }
    
    // 测试主题文件
    $themeInclude = ZBP_PATH . 'zb_users/theme/tpure/include.php';
    if (file_exists($themeInclude)) {
        echo "✓ 主题 include.php 存在\n";
    } else {
        echo "✗ 主题 include.php 不存在\n";
    }
    
    echo "\n=== Z-BlogPHP 信息 ===\n";
    echo "版本: {$zbp->version}\n";
    echo "主机: {$zbp->host}\n";
    echo "数据库: " . get_class($zbp->db) . "\n";
    
    echo "\n✓ 测试完成！没有发现致命错误。\n";
    echo "\n如果网站仍然白板，可能的原因：\n";
    echo "1. 主题未启用（不是 tpure）\n";
    echo "2. 某个钩子函数中有错误\n";
    echo "3. 模板文件中有错误\n";
    
} catch (Throwable $e) {
    echo "\n✗ 错误！\n";
    echo "类型: " . get_class($e) . "\n";
    echo "信息: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    echo "\n堆栈:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

