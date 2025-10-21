<?php
/**
 * Tpure 主题 - 测试运行器
 * 
 * 运行所有测试用例并生成报告
 * 
 * @package Tpure\Tests
 * @version 5.0.6
 * @author TOYEAN
 * 
 * 使用方法:
 * php tests/run-tests.php
 * 或指定特定测试:
 * php tests/run-tests.php SecurityTest
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 启动session（某些测试需要）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║             Tpure 主题单元测试运行器 v5.0.6              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

// 加载测试基类
require_once __DIR__ . '/TestCase.php';

// 获取所有测试文件
$testFiles = glob(__DIR__ . '/*Test.php');

if (empty($testFiles)) {
    echo "\n❌ 没有找到测试文件\n\n";
    exit(1);
}

// 检查是否指定了特定测试
$specifiedTest = isset($argv[1]) ? $argv[1] : null;

// 运行测试
$startTime = microtime(true);

foreach ($testFiles as $testFile) {
    $testClass = basename($testFile, '.php');
    
    // 如果指定了测试，只运行该测试
    if ($specifiedTest && $testClass !== $specifiedTest) {
        continue;
    }
    
    require_once $testFile;
    
    if (class_exists($testClass)) {
        $test = new $testClass();
        $test->run();
    }
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 3);

// 输出统计信息
TestCase::printStats();

echo "\n⏱️  运行时间: {$duration} 秒\n";
echo "📅 测试时间: " . date('Y-m-d H:i:s') . "\n\n";

// 根据测试结果设置退出码
$stats = TestCase::getStats();
exit($stats['failed'] > 0 ? 1 : 0);

