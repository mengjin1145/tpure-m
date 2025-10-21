<?php
/**
 * Tpure 主题 - 访问统计聚合脚本
 * 
 * 功能：将 Redis 中的访问统计数据聚合到 MySQL
 * 
 * 使用方法：
 * 1. Linux Cron:
 *    0 1 * * * /usr/bin/php /path/to/tpure/cron/aggregate-stats.php
 * 
 * 2. Windows 任务计划:
 *    php.exe D:\wwwroot\tpure\cron\aggregate-stats.php
 * 
 * 3. Z-BlogPHP 插件（推荐）:
 *    Add_Filter_Plugin('Filter_Plugin_Zbp_BuildTemplate', 'tpure_cron_aggregate');
 * 
 * @package Tpure
 * @version 5.0.7
 */

// 自动检测 Z-BlogPHP 路径
if (!defined('ZBP_PATH')) {
    // 方法1：从环境变量获取
    if (getenv('ZBP_PATH')) {
        define('ZBP_PATH', getenv('ZBP_PATH'));
    }
    // 方法2：相对路径推测（假设主题在 zb_users/theme/tpure/）
    elseif (file_exists(__DIR__ . '/../../../zb_system/function/c_system_base.php')) {
        define('ZBP_PATH', realpath(__DIR__ . '/../../../') . '/');
    }
    // 方法3：从命令行参数获取
    elseif (isset($argv[1]) && file_exists($argv[1] . '/zb_system/function/c_system_base.php')) {
        define('ZBP_PATH', rtrim($argv[1], '/') . '/');
    }
    else {
        die("Error: Cannot find Z-BlogPHP installation. Please set ZBP_PATH environment variable.\n");
    }
}

// 加载 Z-BlogPHP 核心
require ZBP_PATH . 'zb_system/function/c_system_base.php';

/**
 * 日志输出
 */
function cron_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
    
    // 输出到控制台
    echo $logMessage;
    
    // 写入日志文件
    $logFile = ZBP_PATH . 'zb_users/logs/tpure_cron.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * 主聚合函数
 */
function aggregate_statistics() {
    global $zbp;
    
    cron_log('========== 开始聚合统计数据 ==========');
    
    // 加载 Z-BlogPHP
    $zbp->Load();
    
    // 检查统计模块是否可用
    if (!class_exists('TpureStatistics')) {
        cron_log('错误: TpureStatistics 类不存在，请检查主题安装', 'ERROR');
        return false;
    }
    
    // 检查 Redis 是否可用
    if (!TpureStatistics::isRedisAvailable()) {
        cron_log('警告: Redis 不可用，无需聚合', 'WARNING');
        return false;
    }
    
    // 默认聚合昨天的数据
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('-1 day'));
    
    cron_log("聚合日期: {$date}");
    
    try {
        // 执行聚合
        $count = TpureStatistics::aggregateFromRedis($date);
        
        if ($count > 0) {
            cron_log("✅ 成功聚合 {$count} 条记录", 'SUCCESS');
        } else {
            cron_log("⚠️ 没有数据需要聚合", 'WARNING');
        }
        
        // 清理过期数据（保留365天）
        cron_log('开始清理过期数据...');
        $deleted = TpureStatistics::cleanExpiredData(365);
        
        if ($deleted > 0) {
            cron_log("✅ 成功清理 {$deleted} 条过期记录", 'SUCCESS');
        }
        
        cron_log('========== 聚合完成 ==========');
        
        return true;
        
    } catch (Exception $e) {
        cron_log("错误: {$e->getMessage()}", 'ERROR');
        cron_log("堆栈: {$e->getTraceAsString()}", 'ERROR');
        return false;
    }
}

/**
 * 检查是否正在运行（防止重复执行）
 */
function check_lock() {
    $lockFile = ZBP_PATH . 'zb_users/cache/tpure_cron.lock';
    
    // 检查锁文件
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        $currentTime = time();
        
        // 如果锁文件超过30分钟，认为上次执行异常，删除锁文件
        if ($currentTime - $lockTime > 1800) {
            cron_log('检测到过期的锁文件，已清理', 'WARNING');
            @unlink($lockFile);
        } else {
            cron_log('任务正在运行中，跳过本次执行', 'INFO');
            exit(0);
        }
    }
    
    // 创建锁文件
    @touch($lockFile);
    
    // 注册清理函数
    register_shutdown_function(function() use ($lockFile) {
        @unlink($lockFile);
    });
}

// ==================== 执行脚本 ====================

// 检查锁
check_lock();

// 执行聚合
$success = aggregate_statistics();

// 退出码
exit($success ? 0 : 1);

