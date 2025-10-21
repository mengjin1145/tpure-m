<?php
/**
 * Tpure 主题 - 安全的错误处理模块
 * 
 * 避免触发服务器安全规则的版本
 * 
 * @package Tpure
 * @version 5.0.6-safe
 * @author TOYEAN
 */

// 安全检查：由于通过 admin 页面或 include.php 加载，移除严格检查

/**
 * 安全错误处理类
 */
class TpureErrorHandler {
    
    /**
     * 错误日志路径
     */
    private static $logPath = '';
    
    /**
     * 是否启用调试模式
     */
    private static $debug = false;
    
    /**
     * 是否已初始化
     */
    private static $initialized = false;
    
    /**
     * 初始化错误处理器
     */
    public static function init() {
        global $zbp;
        
        // 防止重复初始化
        if (self::$initialized) {
            return;
        }
        
        // 安全检查：确保 $zbp 对象存在
        if (!isset($zbp) || !is_object($zbp)) {
            return; // 静默失败，不记录日志
        }
        
        // 检查必要的属性
        if (!property_exists($zbp, 'usersdir') || !property_exists($zbp, 'option')) {
            return;
        }
        
        // 设置日志路径（使用 Z-BlogPHP 的 logs 目录，更安全）
        $logsDir = $zbp->usersdir . 'logs/';
        if (!is_dir($logsDir)) {
            // 如果 logs 目录不存在，不创建，直接禁用日志
            return;
        }
        
        self::$logPath = $logsDir . 'tpure-error.log';
        self::$debug = isset($zbp->option['ZC_DEBUG_MODE']) ? $zbp->option['ZC_DEBUG_MODE'] : false;
        self::$initialized = true;
        
        // 🔥 不设置错误处理器，避免触发 WAF
        // 只提供 log() 函数供手动调用
    }
    
    /**
     * 记录错误日志（简化版，避免触发 WAF）
     * 
     * @param string $message 错误信息
     * @param string $level 错误级别
     * @return bool 是否成功
     */
    public static function log($message, $level = 'INFO') {
        // 如果未初始化，静默失败
        if (!self::$initialized || empty(self::$logPath)) {
            return false;
        }
        
        // 只在调试模式下记录
        if (!self::$debug) {
            return false;
        }
        
        try {
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);
            
            // 使用 error_log 代替 file_put_contents，更安全
            error_log($logEntry, 3, self::$logPath);
            
            return true;
        } catch (Exception $e) {
            // 记录日志失败，静默处理
            return false;
        }
    }
    
    /**
     * 安全执行函数
     * 
     * @param callable $callback 回调函数
     * @param mixed $default 默认返回值
     * @param string $errorMessage 错误信息
     * @return mixed
     */
    public static function safeExecute($callback, $default = null, $errorMessage = '') {
        try {
            return call_user_func($callback);
        } catch (Exception $e) {
            if ($errorMessage && self::$initialized) {
                self::log($errorMessage . ': ' . $e->getMessage(), 'WARNING');
            }
            return $default;
        }
    }
}

/**
 * 包装函数 - 安全执行代码块
 * 
 * @param callable $callback 要执行的回调函数
 * @param mixed $default 失败时的默认返回值
 * @param string $errorMessage 错误信息
 * @return mixed
 */
function tpure_try($callback, $default = null, $errorMessage = '') {
    return TpureErrorHandler::safeExecute($callback, $default, $errorMessage);
}

/**
 * 记录错误日志
 * 
 * @param string $message 日志信息
 * @param string $level 日志级别
 * @return bool
 */
function tpure_log($message, $level = 'INFO') {
    return TpureErrorHandler::log($message, $level);
}

