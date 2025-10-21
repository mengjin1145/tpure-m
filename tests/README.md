# Tpure 主题单元测试

> 版本: 5.0.6  
> 更新日期: 2025-10-12

## 📋 目录

- [简介](#简介)
- [快速开始](#快速开始)
- [测试结构](#测试结构)
- [编写测试](#编写测试)
- [运行测试](#运行测试)
- [断言方法](#断言方法)
- [最佳实践](#最佳实践)

## 简介

本测试框架是为 Tpure 主题定制的轻量级单元测试工具，符合 Z-BlogPHP 开发规范。

### 特性

- ✅ 轻量级，无需外部依赖
- ✅ 简单易用的断言方法
- ✅ 清晰的测试报告
- ✅ 支持测试前置/后置操作
- ✅ 自动发现和运行测试

## 快速开始

### 1. 运行所有测试

```bash
php tests/run-tests.php
```

### 2. 运行特定测试

```bash
php tests/run-tests.php SecurityTest
```

### 3. 查看测试结果

```
============================================================
运行测试: 安全函数测试
============================================================

✅ testEscHtml
✅ testEscAttr
✅ testEscUrl
✅ testSanitizeText
✅ testValidateId
✅ testValidateEmail

============================================================
测试结果统计
============================================================
总计: 6
✅ 通过: 6
❌ 失败: 0
⏭️  跳过: 0
通过率: 100%
============================================================
```

## 测试结构

```
tests/
├── README.md              # 测试文档（本文件）
├── TestCase.php           # 测试基类
├── run-tests.php          # 测试运行器
├── SecurityTest.php       # 安全函数测试
├── HelpersTest.php        # 辅助函数测试（待创建）
└── AjaxTest.php           # AJAX函数测试（待创建）
```

## 编写测试

### 1. 创建测试类

```php
<?php
/**
 * 示例测试类
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../lib/your-module.php';

class YourModuleTest extends TestCase {
    
    public function __construct() {
        parent::__construct('模块名称测试');
    }
    
    /**
     * 测试前置操作（可选）
     */
    public function setUp() {
        // 初始化测试环境
    }
    
    /**
     * 测试后置操作（可选）
     */
    public function tearDown() {
        // 清理测试环境
    }
    
    /**
     * 测试方法（必须以test开头）
     */
    public function testSomething() {
        $result = your_function();
        $this->assertEquals('expected', $result);
    }
}
```

### 2. 测试方法命名规范

- 方法名必须以 `test` 开头
- 使用驼峰命名法
- 名称应清晰表达测试意图

```php
// ✅ 好的命名
public function testEmailValidation()
public function testXssProtection()
public function testFileUploadSizeLimit()

// ❌ 不好的命名
public function test1()
public function test()
public function checkEmail()  // 没有test前缀
```

### 3. 测试用例组织

每个测试方法应该测试一个特定的功能点：

```php
// ✅ 推荐：每个方法测试一个功能
public function testValidEmail() {
    $this->assertTrue(tpure_validate_email('user@example.com'));
}

public function testInvalidEmail() {
    $this->assertFalse(tpure_validate_email('invalid'));
}

// ❌ 不推荐：一个方法测试太多
public function testEmail() {
    // 测试太多不同的情况...
}
```

## 运行测试

### 命令行选项

```bash
# 运行所有测试
php tests/run-tests.php

# 运行特定测试类
php tests/run-tests.php SecurityTest

# 在CI/CD中运行（会返回退出码）
php tests/run-tests.php && echo "测试通过" || echo "测试失败"
```

### 退出码

- `0`: 所有测试通过
- `1`: 有测试失败

### PHP版本要求

- PHP 5.6 或更高版本
- Z-BlogPHP 1.5 或更高版本

## 断言方法

测试基类提供了丰富的断言方法：

### 真值断言

```php
$this->assertTrue($value);                  // 断言为true
$this->assertFalse($value);                 // 断言为false
```

### 相等断言

```php
$this->assertEquals($expected, $actual);     // 断言相等（===）
$this->assertNotEquals($expected, $actual);  // 断言不相等
```

### null断言

```php
$this->assertNull($value);                  // 断言为null
$this->assertNotNull($value);               // 断言不为null
```

### 包含断言

```php
$this->assertContains($needle, $haystack);    // 断言包含
$this->assertNotContains($needle, $haystack); // 断言不包含
```

### 数组断言

```php
$this->assertArrayHasKey($key, $array);     // 断言数组包含键
```

### 空值断言

```php
$this->assertEmpty($value);                 // 断言为空
$this->assertNotEmpty($value);              // 断言不为空
```

### 类型断言

```php
$this->assertInstanceOf(ClassName::class, $object);  // 断言类型
```

### 断言示例

```php
public function testExample() {
    // 测试字符串相等
    $result = tpure_esc_html('Hello');
    $this->assertEquals('Hello', $result);
    
    // 测试布尔值
    $isValid = tpure_validate_email('user@example.com');
    $this->assertTrue($isValid);
    
    // 测试包含
    $html = '<div>test</div>';
    $this->assertContains('test', $html);
    
    // 测试数组键
    $config = ['key' => 'value'];
    $this->assertArrayHasKey('key', $config);
    
    // 测试null
    $result = someFunction();
    $this->assertNotNull($result);
    
    // 测试空值
    $array = [];
    $this->assertEmpty($array);
}
```

## 最佳实践

### 1. 测试独立性

每个测试应该独立，不依赖其他测试：

```php
// ✅ 好的做法
public function testA() {
    $result = function_a();
    $this->assertTrue($result);
}

public function testB() {
    $result = function_b();
    $this->assertTrue($result);
}

// ❌ 不好的做法
private $sharedData;

public function testA() {
    $this->sharedData = function_a();
}

public function testB() {
    // 依赖testA的结果
    $result = function_b($this->sharedData);
}
```

### 2. 使用setUp和tearDown

```php
class DatabaseTest extends TestCase {
    private $connection;
    
    public function setUp() {
        // 每个测试前创建数据库连接
        $this->connection = createTestConnection();
    }
    
    public function tearDown() {
        // 每个测试后关闭连接
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    public function testQuery() {
        $result = $this->connection->query('SELECT 1');
        $this->assertNotNull($result);
    }
}
```

### 3. 测试边界条件

```php
public function testIdValidation() {
    // 测试有效值
    $this->assertEquals(1, tpure_validate_id(1));
    $this->assertEquals(999, tpure_validate_id(999));
    
    // 测试边界值
    $this->assertFalse(tpure_validate_id(0));
    $this->assertFalse(tpure_validate_id(-1));
    
    // 测试无效类型
    $this->assertFalse(tpure_validate_id('abc'));
    $this->assertFalse(tpure_validate_id(null));
    $this->assertFalse(tpure_validate_id([]));
}
```

### 4. 有意义的断言消息

```php
// ✅ 好的做法
$this->assertEquals(
    'expected@example.com',
    $result,
    '邮箱格式化失败'
);

// ❌ 不好的做法
$this->assertEquals('expected@example.com', $result);
```

### 5. 测试异常情况

```php
public function testInvalidInput() {
    try {
        dangerousFunction(null);
        $this->assertTrue(false, '应该抛出异常');
    } catch (Exception $e) {
        $this->assertTrue(true);
        $this->assertContains('Invalid', $e->getMessage());
    }
}
```

### 6. 一个测试只测一件事

```php
// ✅ 好的做法
public function testEscapeHtmlTag() {
    $result = tpure_esc_html('<script>');
    $this->assertContains('&lt;', $result);
}

public function testEscapeQuotes() {
    $result = tpure_esc_html('"test"');
    $this->assertContains('&quot;', $result);
}

// ❌ 不好的做法
public function testEscape() {
    // 测试太多不同的东西
    $result1 = tpure_esc_html('<script>');
    $this->assertContains('&lt;', $result1);
    
    $result2 = tpure_esc_attr('value');
    // ...
    
    $result3 = tpure_esc_url('http://...');
    // ...
}
```

## 覆盖率

### 当前测试覆盖情况

- ✅ **lib/security.php** - 100%
- ⏳ **lib/helpers.php** - 待完善
- ⏳ **lib/ajax.php** - 待完善
- ⏳ **lib/mail.php** - 待完善
- ⏳ **lib/error-handler.php** - 待完善

### 测试覆盖目标

- 核心安全函数: 100%
- 公共辅助函数: 80%+
- AJAX处理函数: 80%+
- 错误处理: 80%+

## CI/CD 集成

### GitHub Actions 示例

```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      
      - name: Run Tests
        run: php tests/run-tests.php
```

### 本地Git钩子

在 `.git/hooks/pre-commit` 中添加：

```bash
#!/bin/sh
php tests/run-tests.php
if [ $? -ne 0 ]; then
    echo "测试失败，提交已取消"
    exit 1
fi
```

## 故障排除

### 常见问题

**Q: 运行测试时出现 "Class not found"**

A: 确保正确引入了被测试的文件：
```php
require_once __DIR__ . '/../lib/your-module.php';
```

**Q: 测试通过但功能实际有问题**

A: 检查测试用例是否覆盖了所有边界条件和异常情况。

**Q: 如何测试需要数据库的功能**

A: 使用 setUp 创建测试数据，tearDown 清理数据：
```php
public function setUp() {
    // 创建测试数据
}

public function tearDown() {
    // 清理测试数据
}
```

## 贡献测试

欢迎贡献新的测试用例！

### 贡献步骤

1. 创建新的测试文件 `YourModuleTest.php`
2. 继承 `TestCase` 类
3. 编写测试方法（以 `test` 开头）
4. 运行测试确保通过
5. 提交 Pull Request

### 测试命名规范

- 文件名: `ModuleNameTest.php`
- 类名: `ModuleNameTest`
- 方法名: `test功能描述()`

## 参考资源

- [PHPUnit 文档](https://phpunit.de/documentation.html)
- [Z-BlogPHP 开发文档](https://docs.zblogcn.com/)
- [测试驱动开发(TDD)最佳实践](https://martinfowler.com/bliki/TestDrivenDevelopment.html)

---

**Happy Testing! 🎉**

