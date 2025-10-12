<?php
/**
 * 测试 include.php 加载 - 深度诊断
 */

// 开启所有错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<pre>";
echo "=== 测试 include.php 加载 ===\n\n";

// 模拟 Z-BlogPHP 环境
define('ZBP_PATH', __DIR__ . '/');

echo "步骤 1: 定义 ZBP_PATH = " . ZBP_PATH . "\n";

// 模拟 $zbp 对象（最小化版本）
class MockZbp {
    public $host = 'http://www.dcyzq.com/';
    public $user;
    
    public function __construct() {
        $this->user = new stdClass();
        $this->user->ID = 0;
        $this->user->Level = 0;
    }
    
    public function Config($name) {
        $config = new stdClass();
        $config->PostMAILON = '0';
        $config->PostLOGINON = '0';
        $config->PostVIEWALLON = '0';
        $config->PostFANCYBOXON = '0';
        return $config;
    }
    
    public function Cache() {
        return new MockCache();
    }
}

class MockCache {
    public function Get($key) {
        return null;
    }
    
    public function Set($key, $value, $ttl) {
        return true;
    }
    
    public function Del($key) {
        return true;
    }
}

// 创建全局 $zbp 对象
$zbp = new MockZbp();
echo "步骤 2: 创建模拟 \$zbp 对象\n";

// 模拟必要的 Z-BlogPHP 函数
if (!function_exists('Add_Filter_Plugin')) {
    function Add_Filter_Plugin($hook, $function) {
        // echo "  注册钩子: {$hook} -> {$function}\n";
        return true;
    }
}

if (!function_exists('RegisterPlugin')) {
    function RegisterPlugin($name, $function) {
        echo "步骤 3: 注册主题插件: {$name}\n";
        return true;
    }
}

echo "步骤 4: 模拟函数已定义\n\n";

// 测试加载 include.php
echo "=== 开始加载 include.php ===\n";

try {
    $includeFile = __DIR__ . '/zb_users/theme/tpure/include.php';
    
    if (!file_exists($includeFile)) {
        die("✗ 错误: include.php 不存在\n路径: {$includeFile}\n");
    }
    
    echo "✓ include.php 文件存在\n";
    echo "开始 require...\n\n";
    
    require $includeFile;
    
    echo "\n✓ include.php 加载成功！\n";
    
    // 测试主题激活函数
    if (function_exists('ActivePlugin_tpure')) {
        echo "\n=== 测试主题激活函数 ===\n";
        ActivePlugin_tpure();
        echo "✓ ActivePlugin_tpure() 执行成功\n";
    }
    
} catch (Error $e) {
    echo "\n✗ 致命错误！\n";
    echo "错误类型: " . get_class($e) . "\n";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "错误文件: " . $e->getFile() . "\n";
    echo "错误行号: " . $e->getLine() . "\n";
    echo "\n堆栈跟踪:\n";
    echo $e->getTraceAsString() . "\n";
    
} catch (Exception $e) {
    echo "\n✗ 异常！\n";
    echo "异常类型: " . get_class($e) . "\n";
    echo "异常信息: " . $e->getMessage() . "\n";
    echo "异常文件: " . $e->getFile() . "\n";
    echo "异常行号: " . $e->getLine() . "\n";
}

echo "\n=== 测试完成 ===\n";
echo "</pre>";

