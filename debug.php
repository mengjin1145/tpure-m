<?php
/**
 * Tpure 主题 - 调试脚本
 * 用于诊断 HTTP 500 错误
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 智能检测主题目录
$currentDir = dirname(__FILE__);

// 检查当前文件是否在主题目录中
if (basename($currentDir) === 'tpure' && 
    strpos($currentDir, 'zb_users') !== false) {
    // 文件在主题目录
    $themeDir = $currentDir . DIRECTORY_SEPARATOR;
    $zbpPath = dirname(dirname(dirname($currentDir))) . DIRECTORY_SEPARATOR;
} else {
    // 文件在其他位置（如网站根目录），需要查找主题目录
    $themeDir = $currentDir . DIRECTORY_SEPARATOR . 'zb_users' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'tpure' . DIRECTORY_SEPARATOR;
    $zbpPath = $currentDir . DIRECTORY_SEPARATOR;
}

// 定义必要的常量（模拟 Z-BlogPHP 环境）
if (!defined('ZBP_PATH')) {
    define('ZBP_PATH', $zbpPath);
}

echo "=== Tpure 主题调试信息 ===\n\n";

// 1. 检查 PHP 版本
echo "PHP 版本: " . PHP_VERSION . "\n\n";

// 2. 显示检测到的目录
echo "当前文件位置: {$currentDir}\n";
echo "检测到的主题目录: {$themeDir}\n";
echo "检测到的 ZBP 根目录: {$zbpPath}\n\n";

// 3. 检查核心模块文件是否存在
$modules = array(
    'lib/constants.php',
    'lib/error-handler.php',
    'lib/security.php',
    'lib/cache.php',
    'lib/http-cache.php',
    'lib/database.php',
    'lib/helpers.php',
    'lib/ajax.php',
    'lib/mail.php',
);

echo "=== 检查模块文件 ===\n";
foreach ($modules as $module) {
    $path = $themeDir . $module;
    $exists = file_exists($path);
    $status = $exists ? '✓ 存在' : '✗ 不存在';
    echo "{$status}: {$module}\n";
    
    // 检查语法（如果 exec 可用）
    if ($exists && function_exists('exec')) {
        $output = array();
        $return_var = 0;
        @exec("php -l " . escapeshellarg($path), $output, $return_var);
        if ($return_var !== 0) {
            echo "   ⚠ 语法错误: " . implode("\n", $output) . "\n";
        }
    }
}

echo "\n=== 尝试逐个加载模块 ===\n";
foreach ($modules as $module) {
    $path = $themeDir . $module;
    if (!file_exists($path)) {
        echo "跳过: {$module} (文件不存在)\n";
        continue;
    }
    
    try {
        echo "加载: {$module} ... ";
        require_once $path;
        echo "✓ 成功\n";
    } catch (Error $e) {
        echo "✗ 失败\n";
        echo "   错误: " . $e->getMessage() . "\n";
        echo "   文件: " . $e->getFile() . "\n";
        echo "   行号: " . $e->getLine() . "\n";
        break;
    } catch (Exception $e) {
        echo "✗ 失败\n";
        echo "   异常: " . $e->getMessage() . "\n";
        break;
    }
}

echo "\n=== 检查关键类和函数 ===\n";
echo "TpureErrorHandler 类: " . (class_exists('TpureErrorHandler') ? '✓ 存在' : '✗ 不存在') . "\n";
echo "TpureCache 类: " . (class_exists('TpureCache') ? '✓ 存在' : '✗ 不存在') . "\n";
echo "TpureHttpCache 类: " . (class_exists('TpureHttpCache') ? '✓ 存在' : '✗ 不存在') . "\n";
echo "tpure_register_cache_hooks 函数: " . (function_exists('tpure_register_cache_hooks') ? '✓ 存在' : '✗ 不存在') . "\n";

echo "\n=== 检查插件依赖文件 ===\n";
$pluginFiles = array(
    'plugin/searchstr.php',
    'plugin/phpmailer/sendmail.php',
    'plugin/ipLocation/function.php'
);

foreach ($pluginFiles as $pluginFile) {
    $path = $themeDir . $pluginFile;
    echo (file_exists($path) ? '✓' : '✗') . " {$pluginFile}\n";
}

echo "\n=== 测试关键函数 ===\n";

// 测试安全函数
try {
    $testStr = '<script>alert(1)</script>';
    $escaped = tpure_esc_html($testStr);
    echo "✓ tpure_esc_html() 可用\n";
} catch (Throwable $e) {
    echo "✗ tpure_esc_html() 错误: " . $e->getMessage() . "\n";
}

echo "\n=== 查找错误日志 ===\n";
$logDir = $zbpPath . 'zb_users/logs/';
if (is_dir($logDir)) {
    $logFiles = glob($logDir . '*.txt');
    if ($logFiles) {
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo "最新日志: " . basename($logFiles[0]) . "\n\n";
        
        // 读取最后 30 行
        $content = file_get_contents($logFiles[0]);
        $lines = explode("\n", $content);
        $lastLines = array_slice($lines, -30);
        
        echo "=== 最后 30 行 ===\n";
        echo implode("\n", $lastLines);
    } else {
        echo "未找到日志文件\n";
    }
}

echo "\n\n=== 调试完成 ===\n";

