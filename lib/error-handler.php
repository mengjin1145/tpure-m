<?php
/**
 * Tpure 主题 - 错误处理模块
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * 统一错误处理类
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
     * 初始化错误处理器
     */
    public static function init() {
        global $zbp;
        
        // 安全检查：确保 $zbp 对象存在且有必要的属性
        if (!isset($zbp) || !is_object($zbp) || !property_exists($zbp, 'usersdir')) {
            self::$logPath = dirname(dirname(__DIR__)) . '/cache/error.log';
            self::$debug = false;
            return;
        }
        
        self::$logPath = $zbp->usersdir . 'cache/error.log';
        self::$debug = $zbp->option['ZC_DEBUG_MODE'] ?? false;
        
        // 设置错误处理器（仅在调试模式）
        if (self::$debug) {
            set_error_handler([__CLASS__, 'handleError']);
            set_exception_handler([__CLASS__, 'handleException']);
        }
    }
    
    /**
     * 错误处理器
     * 
     * @param int $errno 错误级别
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行号
     * @return bool
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = self::getErrorType($errno);
        $message = sprintf(
            "[%s] %s in %s on line %d",
            $errorType,
            $errstr,
            $errfile,
            $errline
        );
        
        self::log($message, 'ERROR');
        
        return true;
    }
    
    /**
     * 异常处理器
     * 
     * @param Exception $exception 异常对象
     */
    public static function handleException($exception) {
        $message = sprintf(
            "[EXCEPTION] %s in %s on line %d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        self::log($message, 'CRITICAL');
        
        // 如果不是调试模式，显示友好错误页面
        if (!self::$debug) {
            self::showFriendlyError();
        }
    }
    
    /**
     * 记录错误日志
     * 
     * @param string $message 错误信息
     * @param string $level 错误级别
     */
    public static function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = sprintf(
            "[%s] [%s] [IP:%s] [URI:%s] %s\n",
            $timestamp,
            $level,
            $ip,
            $uri,
            $message
        );
        
        // 日志文件大小限制（10MB）
        if (file_exists(self::$logPath) && filesize(self::$logPath) > 10 * 1024 * 1024) {
            @rename(self::$logPath, self::$logPath . '.' . date('Y-m-d-His') . '.bak');
        }
        
        @file_put_contents(self::$logPath, $logEntry, FILE_APPEND | LOCK_EX);
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
            $message = $errorMessage ?: 'Safe execute failed';
            self::log($message . ': ' . $e->getMessage(), 'WARNING');
            return $default;
        }
    }
    
    /**
     * 获取错误类型名称
     * 
     * @param int $errno 错误编号
     * @return string
     */
    private static function getErrorType($errno) {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED',
        ];
        
        return $errorTypes[$errno] ?? 'UNKNOWN';
    }
    
    /**
     * 显示友好错误页面
     */
    private static function showFriendlyError() {
        if (headers_sent()) {
            return;
        }
        
        http_response_code(500);
        
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站遇到了一点问题</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            font-size: 72px;
            margin: 0;
        }
        h2 {
            color: #333;
            font-size: 24px;
            margin: 20px 0;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oops!</h1>
        <h2>网站遇到了一点问题</h2>
        <p>很抱歉，网站暂时无法访问。我们的技术团队已经收到通知，正在努力修复。</p>
        <p>请稍后再试，或联系网站管理员。</p>
        <a href="/" class="btn">返回首页</a>
    </div>
</body>
</html>';
        exit;
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
 */
function tpure_log($message, $level = 'INFO') {
    TpureErrorHandler::log($message, $level);
}

