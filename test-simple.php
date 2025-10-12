<?php
/**
 * 最简单的测试 - 检查 PHP 是否正常
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP 工作正常！<br>";
echo "PHP 版本: " . PHP_VERSION . "<br>";
echo "当前时间: " . date('Y-m-d H:i:s') . "<br>";

// 测试 include.php 语法
echo "<hr>";
echo "检查 include.php 语法...<br>";

$includeFile = __DIR__ . '/include.php';
if (file_exists($includeFile)) {
    echo "include.php 存在<br>";
    
    // 使用 php -l 命令检查语法（如果可用）
    $output = array();
    $return = 0;
    @exec("php -l " . escapeshellarg($includeFile), $output, $return);
    
    if ($return === 0) {
        echo "✓ 语法检查通过<br>";
        echo implode("<br>", $output);
    } else {
        echo "✗ 语法检查失败<br>";
        echo implode("<br>", $output);
    }
} else {
    echo "include.php 不存在<br>";
}

echo "<hr>";
echo "测试完成！";

