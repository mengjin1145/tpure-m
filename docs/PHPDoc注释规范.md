# PHPDoc 注释规范

> 版本: 5.0.6  
> 更新日期: 2025-10-12

## 1. 文件头注释

每个PHP文件开头必须包含文件注释：

```php
<?php
/**
 * Tpure 主题 - 模块名称
 * 
 * 简短描述文件功能
 * 
 * @package Tpure
 * @subpackage 子包名称（可选）
 * @version 5.0.6
 * @author TOYEAN
 * @link https://www.toyean.com/
 * @since 5.0.0
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}
```

## 2. 函数注释

### 2.1 基本格式

```php
/**
 * 函数简短描述（一行）
 * 
 * 详细描述函数功能、用途、注意事项
 * 可以多行
 * 
 * @param 类型 $参数名 参数描述
 * @param 类型 $参数名 参数描述（可选）
 * @return 返回类型 返回值描述
 * @throws 异常类型 异常描述（如果有）
 * @since 版本号
 * @deprecated 废弃版本号 废弃原因（如果已废弃）
 * @see 相关函数
 * @example 示例代码（可选）
 */
function function_name($param1, $param2 = '') {
    // 函数体
}
```

### 2.2 参数类型说明

支持的类型标注：

- **基础类型**: `string`, `int`, `float`, `bool`, `array`, `object`, `resource`, `null`
- **混合类型**: `mixed` (任意类型)
- **类类型**: `ClassName`, `\Namespace\ClassName`
- **复合类型**: 使用 `|` 分隔，如 `string|int`, `array|false`
- **数组类型**: `array`, `string[]`, `int[]`, `ClassName[]`
- **可选类型**: 使用 `null` 表示，如 `string|null`

### 2.3 完整示例

```php
/**
 * 发送邮件
 * 
 * 使用SMTP协议发送HTML格式邮件，支持附件和抄送。
 * 邮件发送前会自动验证收件人地址合法性。
 * 
 * @param string $to 收件人邮箱地址
 * @param string $subject 邮件主题，长度不超过200字符
 * @param string $content 邮件内容（HTML格式）
 * @param array $attachments 附件路径数组（可选）
 * @param string|null $cc 抄送地址（可选）
 * @return bool 发送成功返回true，失败返回false
 * @throws \Exception 当SMTP配置错误时抛出异常
 * @since 5.0.0
 * @see tpure_validate_email() 邮箱验证函数
 * 
 * @example
 * ```php
 * $result = tpure_send_email(
 *     'user@example.com',
 *     '测试邮件',
 *     '<p>这是一封测试邮件</p>'
 * );
 * if ($result) {
 *     echo '发送成功';
 * }
 * ```
 */
function tpure_send_email($to, $subject, $content, $attachments = [], $cc = null) {
    // 函数实现
}
```

## 3. 类注释

### 3.1 类注释格式

```php
/**
 * 类简短描述
 * 
 * 详细描述类的功能和用途
 * 
 * @package Tpure
 * @subpackage Security
 * @since 5.0.6
 * @author TOYEAN
 * 
 * @property string $propertyName 属性描述
 * @method mixed methodName() 魔术方法描述
 */
class ClassName {
    // 类实现
}
```

### 3.2 完整示例

```php
/**
 * 错误处理类
 * 
 * 提供统一的错误处理、日志记录和异常管理功能。
 * 支持自定义错误处理器和友好错误页面显示。
 * 
 * @package Tpure
 * @subpackage ErrorHandling
 * @since 5.0.6
 * @author TOYEAN
 */
class TpureErrorHandler {
    
    /**
     * 错误日志文件路径
     * 
     * @var string
     */
    private static $logPath = '';
    
    /**
     * 是否启用调试模式
     * 
     * @var bool
     */
    private static $debug = false;
    
    /**
     * 初始化错误处理器
     * 
     * 设置错误和异常处理器，配置日志路径。
     * 仅在调试模式下启用自定义错误处理。
     * 
     * @return void
     * @since 5.0.6
     */
    public static function init() {
        // 方法实现
    }
}
```

## 4. 属性注释

```php
class MyClass {
    /**
     * 用户ID
     * 
     * @var int
     */
    public $userId;
    
    /**
     * 配置数组
     * 
     * @var array<string, mixed>
     */
    private $config = [];
    
    /**
     * 数据库连接实例
     * 
     * @var \mysqli|null
     */
    protected $dbConnection = null;
}
```

## 5. 常量注释

```php
/**
 * 主题版本号
 * 
 * @var string
 */
define('TPURE_VERSION', '5.0.6');

class MyClass {
    /**
     * 最大上传文件大小（字节）
     * 
     * @var int
     */
    const MAX_UPLOAD_SIZE = 5242880; // 5MB
}
```

## 6. 特殊标签说明

### 6.1 @param 参数标签

```php
/**
 * @param string $name 用户名
 * @param int $age 年龄（可选，默认18）
 * @param array $options 选项数组
 *        - 'email' (string) 邮箱地址
 *        - 'phone' (string) 手机号码
 *        - 'address' (string) 联系地址
 */
function register($name, $age = 18, $options = []) {
    // 实现
}
```

### 6.2 @return 返回值标签

```php
/**
 * @return bool 成功返回true，失败返回false
 */
function save() { }

/**
 * @return array<int, string> 用户名数组
 */
function getUserNames() { }

/**
 * @return \Post|null 文章对象，不存在返回null
 */
function getPost($id) { }

/**
 * @return void 无返回值
 */
function logMessage($msg) { }
```

### 6.3 @throws 异常标签

```php
/**
 * @throws \InvalidArgumentException 当参数无效时
 * @throws \RuntimeException 当文件无法写入时
 */
function writeFile($path, $content) {
    // 实现
}
```

### 6.4 @since 版本标签

```php
/**
 * @since 5.0.0 首次引入
 * @since 5.0.6 添加了$maxSize参数
 */
function uploadFile($file, $maxSize = 5242880) {
    // 实现
}
```

### 6.5 @deprecated 废弃标签

```php
/**
 * 旧版邮件发送函数
 * 
 * @deprecated 5.0.6 使用 tpure_send_email() 替代
 * @see tpure_send_email()
 */
function tpure_sendmail($to, $subject, $body) {
    // 向后兼容实现
    return tpure_send_email($to, $subject, $body);
}
```

### 6.6 @see 引用标签

```php
/**
 * @see tpure_esc_html() HTML转义
 * @see tpure_esc_attr() 属性转义
 * @see tpure_esc_url() URL转义
 */
function tpure_sanitize_output($data) {
    // 实现
}
```

### 6.7 @todo 待办标签

```php
/**
 * @todo 添加缓存支持
 * @todo 优化数据库查询性能
 */
function getArticleList() {
    // 实现
}
```

### 6.8 @global 全局变量标签

```php
/**
 * @global \ZBlogPHP $zbp Z-BlogPHP核心对象
 * @global \Post $article 当前文章对象
 */
function display_article() {
    global $zbp, $article;
    // 实现
}
```

## 7. 钩子函数注释

```php
/**
 * 文章发布钩子
 * 
 * 在文章发布成功后触发，用于发送邮件通知。
 * 
 * @hook Filter_Plugin_PostArticle_Succeed
 * @param \Post $article 文章对象
 * @return void
 * @since 5.0.0
 */
function tpure_article_publish_hook($article) {
    // 实现
}
```

## 8. Z-BlogPHP 特定类型

```php
/**
 * 获取文章列表
 * 
 * @param int $count 获取数量
 * @return \Post[] 文章对象数组
 */
function get_articles($count = 10) {
    // 实现
}

/**
 * 获取当前用户
 * 
 * @return \Member|null 用户对象
 */
function get_current_user() {
    global $zbp;
    return $zbp->user;
}

/**
 * 获取分类信息
 * 
 * @param int $id 分类ID
 * @return \Category|false 分类对象，不存在返回false
 */
function get_category($id) {
    // 实现
}
```

## 9. 内联注释

```php
function process_data($data) {
    // 第一步：验证数据
    if (!validate($data)) {
        return false;
    }
    
    // 第二步：处理数据
    $processed = [];
    foreach ($data as $item) {
        // 跳过空值
        if (empty($item)) {
            continue;
        }
        
        /* 
         * 复杂处理逻辑
         * 需要多行说明
         */
        $processed[] = transform($item);
    }
    
    // 第三步：保存结果
    return save($processed);
}
```

## 10. 注释最佳实践

### 10.1 DO - 推荐做法

```php
/**
 * 验证邮箱地址格式
 * 
 * 使用正则表达式验证邮箱格式是否合法，
 * 支持国际化域名和Unicode字符。
 * 
 * @param string $email 要验证的邮箱地址
 * @return bool 合法返回true，否则返回false
 */
function tpure_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
```

### 10.2 DON'T - 避免做法

```php
// ❌ 注释过于简单
/**
 * 验证邮箱
 */
function tpure_validate_email($email) { }

// ❌ 注释与代码不符
/**
 * @return bool
 */
function get_user() {
    return $user; // 实际返回对象，不是bool
}

// ❌ 重复代码内容
/**
 * 返回true如果email合法
 */
function is_valid_email($email) {
    // 注释只是重复了函数名，没有提供额外信息
}

// ❌ 注释过时未更新
/**
 * @param string $path 文件路径
 */
function process($path, $encoding) {
    // 函数签名已改变，但注释未更新
}
```

## 11. IDE 支持

### 11.1 PHPStorm/VSCode 提示

良好的PHPDoc注释能让IDE提供更好的代码提示：

```php
/**
 * @return \Post
 */
function get_post() {
    // IDE会知道返回Post对象，提供Post类的方法提示
}

/**
 * @var \Post $article
 */
foreach ($articles as $article) {
    // IDE会为$article提供Post类的属性和方法提示
    echo $article->Title;
}
```

### 11.2 类型提示增强

```php
/**
 * @param array{
 *     name: string,
 *     age: int,
 *     email: string
 * } $user 用户信息数组
 */
function save_user($user) {
    // IDE会知道数组的具体结构
}
```

## 12. 注释生成工具

### 12.1 phpDocumentor

生成API文档：

```bash
phpdoc -d ./lib -t ./docs/api
```

### 12.2 检查注释完整性

创建检查脚本 `scripts/check-phpdoc.php`:

```php
<?php
/**
 * 检查PHPDoc注释完整性
 */

$files = glob('lib/*.php');
$errors = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // 检查所有函数是否有注释
    preg_match_all('/function\s+(\w+)\s*\(/', $content, $matches);
    
    foreach ($matches[1] as $function) {
        // 检查函数前是否有/** */注释
        if (!preg_match('/\/\*\*[\s\S]*?\*\/\s*function\s+' . $function . '/', $content)) {
            $errors[] = "$file: 函数 $function 缺少PHPDoc注释";
        }
    }
}

if (empty($errors)) {
    echo "✅ 所有函数都有PHPDoc注释\n";
} else {
    echo "❌ 发现 " . count($errors) . " 个问题:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
```

## 13. 注释模板

### 13.1 函数模板

```php
/**
 * [函数功能简短描述]
 * 
 * [详细描述]
 * 
 * @param [类型] $[参数名] [参数描述]
 * @return [返回类型] [返回值描述]
 * @since 5.0.6
 */
function tpure_function_name($param) {
    // TODO: 实现函数逻辑
}
```

### 13.2 类模板

```php
/**
 * [类功能简短描述]
 * 
 * [详细描述]
 * 
 * @package Tpure
 * @since 5.0.6
 */
class TpureClassName {
    
    /**
     * [属性描述]
     * 
     * @var [类型]
     */
    private $property;
    
    /**
     * [方法功能简短描述]
     * 
     * @param [类型] $[参数名] [参数描述]
     * @return [返回类型] [返回值描述]
     */
    public function methodName($param) {
        // TODO: 实现方法逻辑
    }
}
```

---

**遵循此规范，可以让代码更易维护，协作更高效，IDE提示更智能。**

