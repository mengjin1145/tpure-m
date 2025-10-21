<?php
/**
 * Tpure 主题 - 调试错误处理器
 * 
 * 仅在 TPURE_DEBUG = true 时加载
 * 提供详细的错误显示和调试信息
 * 
 * @package Tpure
 * @version 5.9
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

// 开启所有错误报告
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// 自定义错误处理器（显示详细错误）
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorTypes = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    );
    
    $type = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'Unknown Error';
    
    echo "<div style='background:#fed7d7;color:#742a2a;padding:20px;margin:10px;border-left:4px solid #f56565;font-family:monospace;'>";
    echo "<h3 style='margin:0 0 10px 0;'>🔴 Tpure Turbo Debug - {$type}</h3>";
    echo "<p><strong>错误信息：</strong>{$errstr}</p>";
    echo "<p><strong>文件：</strong>{$errfile}</p>";
    echo "<p><strong>行号：</strong>{$errline}</p>";
    echo "<p><strong>错误级别：</strong>{$errno}</p>";
    echo "<pre style='background:#fff;padding:10px;overflow:auto;'>";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    echo "</pre>";
    echo "</div>";
    
    return false;  // 继续执行 PHP 默认错误处理
});

// 捕获致命错误
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        echo "<div style='background:#fed7d7;color:#742a2a;padding:20px;margin:10px;border-left:4px solid #f56565;font-family:monospace;'>";
        echo "<h3 style='margin:0 0 10px 0;'>💀 Tpure Turbo - Fatal Error</h3>";
        echo "<p><strong>错误信息：</strong>{$error['message']}</p>";
        echo "<p><strong>文件：</strong>{$error['file']}</p>";
        echo "<p><strong>行号：</strong>{$error['line']}</p>";
        echo "</div>";
    }
});

