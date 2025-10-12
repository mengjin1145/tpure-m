<?php
/**
 * 强制显示错误的测试脚本
 */

// 最强的错误显示设置
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '0');

// 自定义错误处理器
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<br><b>错误 [{$errno}]:</b> {$errstr}<br>";
    echo "文件: {$errfile}<br>";
    echo "行号: {$errline}<br>";
    return false;
});

// 自定义异常处理器
set_exception_handler(function($e) {
    echo "<br><b>异常:</b> " . $e->getMessage() . "<br>";
    echo "文件: " . $e->getFile() . "<br>";
    echo "行号: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
});

// 注册关闭函数捕获致命错误
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<br><b>致命错误:</b> {$error['message']}<br>";
        echo "文件: {$error['file']}<br>";
        echo "行号: {$error['line']}<br>";
    }
});

echo "=== 强制错误显示测试 ===<br><br>";

// 开始输出缓冲
ob_start();

echo "步骤 1: 准备环境...<br>";
flush();
ob_flush();

// 定义 ZBP_PATH
$currentDir = __DIR__;
if (strpos($currentDir, 'zb_users/theme/tpure') !== false || 
    strpos($currentDir, 'zb_users\\theme\\tpure') !== false) {
    $zbpPath = dirname(dirname(dirname($currentDir))) . DIRECTORY_SEPARATOR;
} else {
    $zbpPath = $currentDir . DIRECTORY_SEPARATOR;
}

define('ZBP_PATH', $zbpPath);
echo "ZBP_PATH: " . ZBP_PATH . "<br>";
flush();
ob_flush();

echo "<br>步骤 2: 加载 include.php...<br>";
flush();
ob_flush();

try {
    $includeFile = ZBP_PATH . 'zb_users/theme/tpure/include.php';
    
    if (!file_exists($includeFile)) {
        die("include.php 不存在: {$includeFile}<br>");
    }
    
    echo "开始 require...<br>";
    flush();
    ob_flush();
    
    require $includeFile;
    
    echo "<br>✓ include.php 加载成功！<br>";
    
} catch (Throwable $e) {
    echo "<br>✗ 捕获到异常:<br>";
    echo "类型: " . get_class($e) . "<br>";
    echo "信息: " . $e->getMessage() . "<br>";
    echo "文件: " . $e->getFile() . "<br>";
    echo "行号: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br>=== 测试完成 ===<br>";

// 输出缓冲区
ob_end_flush();

