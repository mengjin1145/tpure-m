<?php
/**
 * Tpure 主题 - 测试基类
 * 
 * @package Tpure\Tests
 * @version 5.0.6
 * @author TOYEAN
 */

/**
 * 测试基类
 * 
 * 提供基本的断言方法和测试辅助功能
 */
class TestCase {
    
    /**
     * 测试名称
     * 
     * @var string
     */
    protected $testName = '';
    
    /**
     * 测试统计
     * 
     * @var array
     */
    protected static $stats = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0
    ];
    
    /**
     * 测试失败信息
     * 
     * @var array
     */
    protected static $failures = [];
    
    /**
     * 构造函数
     * 
     * @param string $testName 测试名称
     */
    public function __construct($testName = '') {
        $this->testName = $testName ?: get_class($this);
    }
    
    /**
     * 测试前置操作
     * 
     * @return void
     */
    public function setUp() {
        // 子类可重写
    }
    
    /**
     * 测试后置操作
     * 
     * @return void
     */
    public function tearDown() {
        // 子类可重写
    }
    
    /**
     * 运行测试
     * 
     * @return bool
     */
    public function run() {
        $methods = get_class_methods($this);
        $testMethods = array_filter($methods, function($method) {
            return strpos($method, 'test') === 0;
        });
        
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "运行测试: {$this->testName}\n";
        echo str_repeat('=', 60) . "\n\n";
        
        foreach ($testMethods as $method) {
            $this->runTest($method);
        }
        
        return self::$stats['failed'] === 0;
    }
    
    /**
     * 运行单个测试方法
     * 
     * @param string $method 方法名
     * @return void
     */
    protected function runTest($method) {
        self::$stats['total']++;
        
        try {
            $this->setUp();
            $this->$method();
            $this->tearDown();
            
            self::$stats['passed']++;
            echo "✅ {$method}\n";
        } catch (AssertionException $e) {
            self::$stats['failed']++;
            self::$failures[] = [
                'test' => $this->testName,
                'method' => $method,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            echo "❌ {$method}\n";
            echo "   {$e->getMessage()}\n";
        } catch (Exception $e) {
            self::$stats['failed']++;
            self::$failures[] = [
                'test' => $this->testName,
                'method' => $method,
                'message' => 'Exception: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            echo "💥 {$method}\n";
            echo "   Exception: {$e->getMessage()}\n";
        }
    }
    
    /**
     * 获取测试统计
     * 
     * @return array
     */
    public static function getStats() {
        return self::$stats;
    }
    
    /**
     * 输出测试统计
     * 
     * @return void
     */
    public static function printStats() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "测试结果统计\n";
        echo str_repeat('=', 60) . "\n";
        echo "总计: " . self::$stats['total'] . "\n";
        echo "✅ 通过: " . self::$stats['passed'] . "\n";
        echo "❌ 失败: " . self::$stats['failed'] . "\n";
        echo "⏭️  跳过: " . self::$stats['skipped'] . "\n";
        
        $passRate = self::$stats['total'] > 0 
            ? round((self::$stats['passed'] / self::$stats['total']) * 100, 2) 
            : 0;
        echo "通过率: {$passRate}%\n";
        
        if (!empty(self::$failures)) {
            echo "\n" . str_repeat('-', 60) . "\n";
            echo "失败详情:\n";
            echo str_repeat('-', 60) . "\n";
            foreach (self::$failures as $i => $failure) {
                echo ($i + 1) . ") {$failure['test']}::{$failure['method']}\n";
                echo "   {$failure['message']}\n";
                echo "   @ {$failure['file']}:{$failure['line']}\n\n";
            }
        }
        
        echo str_repeat('=', 60) . "\n";
    }
    
    // ==================== 断言方法 ====================
    
    /**
     * 断言为真
     * 
     * @param mixed $condition 条件
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertTrue($condition, $message = '') {
        if ($condition !== true) {
            throw new AssertionException(
                $message ?: '断言失败: 期望值为 true，实际为 ' . var_export($condition, true)
            );
        }
    }
    
    /**
     * 断言为假
     * 
     * @param mixed $condition 条件
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertFalse($condition, $message = '') {
        if ($condition !== false) {
            throw new AssertionException(
                $message ?: '断言失败: 期望值为 false，实际为 ' . var_export($condition, true)
            );
        }
    }
    
    /**
     * 断言相等
     * 
     * @param mixed $expected 期望值
     * @param mixed $actual 实际值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new AssertionException(
                $message ?: sprintf(
                    '断言失败: 期望值为 %s，实际为 %s',
                    var_export($expected, true),
                    var_export($actual, true)
                )
            );
        }
    }
    
    /**
     * 断言不相等
     * 
     * @param mixed $expected 期望值
     * @param mixed $actual 实际值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertNotEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            throw new AssertionException(
                $message ?: sprintf(
                    '断言失败: 期望值不为 %s',
                    var_export($expected, true)
                )
            );
        }
    }
    
    /**
     * 断言为null
     * 
     * @param mixed $value 值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertNull($value, $message = '') {
        if ($value !== null) {
            throw new AssertionException(
                $message ?: '断言失败: 期望值为 null，实际为 ' . var_export($value, true)
            );
        }
    }
    
    /**
     * 断言不为null
     * 
     * @param mixed $value 值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new AssertionException(
                $message ?: '断言失败: 期望值不为 null'
            );
        }
    }
    
    /**
     * 断言包含
     * 
     * @param mixed $needle 要查找的值
     * @param array|string $haystack 数组或字符串
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertContains($needle, $haystack, $message = '') {
        $contains = is_array($haystack) 
            ? in_array($needle, $haystack) 
            : strpos($haystack, $needle) !== false;
            
        if (!$contains) {
            throw new AssertionException(
                $message ?: sprintf(
                    '断言失败: %s 不包含 %s',
                    var_export($haystack, true),
                    var_export($needle, true)
                )
            );
        }
    }
    
    /**
     * 断言不包含
     * 
     * @param mixed $needle 要查找的值
     * @param array|string $haystack 数组或字符串
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertNotContains($needle, $haystack, $message = '') {
        $contains = is_array($haystack) 
            ? in_array($needle, $haystack) 
            : strpos($haystack, $needle) !== false;
            
        if ($contains) {
            throw new AssertionException(
                $message ?: sprintf(
                    '断言失败: %s 包含 %s',
                    var_export($haystack, true),
                    var_export($needle, true)
                )
            );
        }
    }
    
    /**
     * 断言数组包含键
     * 
     * @param string|int $key 键名
     * @param array $array 数组
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertArrayHasKey($key, $array, $message = '') {
        if (!array_key_exists($key, $array)) {
            throw new AssertionException(
                $message ?: "断言失败: 数组不包含键 '{$key}'"
            );
        }
    }
    
    /**
     * 断言为空
     * 
     * @param mixed $value 值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertEmpty($value, $message = '') {
        if (!empty($value)) {
            throw new AssertionException(
                $message ?: '断言失败: 期望值为空，实际为 ' . var_export($value, true)
            );
        }
    }
    
    /**
     * 断言不为空
     * 
     * @param mixed $value 值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertNotEmpty($value, $message = '') {
        if (empty($value)) {
            throw new AssertionException(
                $message ?: '断言失败: 期望值不为空'
            );
        }
    }
    
    /**
     * 断言类型匹配
     * 
     * @param string $expected 期望类型
     * @param mixed $actual 实际值
     * @param string $message 失败消息
     * @throws AssertionException
     */
    protected function assertInstanceOf($expected, $actual, $message = '') {
        if (!($actual instanceof $expected)) {
            throw new AssertionException(
                $message ?: sprintf(
                    '断言失败: 期望类型为 %s，实际为 %s',
                    $expected,
                    get_class($actual)
                )
            );
        }
    }
}

/**
 * 断言异常类
 */
class AssertionException extends Exception {
    
}

