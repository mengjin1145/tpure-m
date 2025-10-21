# Tpure 主题 - 错误管理功能使用指南

## 📚 目录
1. [功能概述](#功能概述)
2. [启用调试模式](#启用调试模式)
3. [错误处理器说明](#错误处理器说明)
4. [使用方法](#使用方法)
5. [查看错误日志](#查看错误日志)
6. [常见问题](#常见问题)

---

## 功能概述

Tpure 主题内置了三个级别的错误处理器，帮助您快速定位和解决问题：

| 文件 | 用途 | 适用场景 |
|------|------|---------|
| `lib/error-handler.php` | 完整错误处理器 | 开发环境，详细错误记录 |
| `lib/error-handler-safe.php` | 安全错误处理器 | 生产环境，避免触发 WAF |
| `lib/debug-handler.php` | 调试模式处理器 | 调试模式，实时显示错误 |

---

## 启用调试模式

### 方法1：修改 `include.php`（推荐）

在 `include.php` 文件开头添加：

```php
<?php
// 🔧 调试模式开关
define('TPURE_DEBUG', true);  // 开启调试
// define('TPURE_DEBUG', false);  // 关闭调试

require '../../../zb_system/function/c_system_base.php';
// ... 后续代码
```

### 方法2：通过 Z-BlogPHP 设置

在 Z-BlogPHP 后台 → 网站设置 → 全局设置 → 开启调试模式

---

## 错误处理器说明

### 1️⃣ 调试模式处理器 (`debug-handler.php`)

**特点：**
- ✅ 实时在页面上显示错误
- ✅ 详细的堆栈跟踪
- ✅ 彩色错误提示
- ⚠️ 仅在 `TPURE_DEBUG = true` 时加载

**显示效果：**
```
🔴 Tpure Turbo Debug - Warning
错误信息：Undefined variable: test
文件：/path/to/file.php
行号：123
```

### 2️⃣ 完整错误处理器 (`error-handler.php`)

**特点：**
- ✅ 记录详细错误日志
- ✅ 自定义错误处理
- ✅ 友好错误页面（生产环境）
- ✅ 日志自动轮转（10MB）

**使用示例：**
```php
// 初始化错误处理器
TpureErrorHandler::init();

// 记录日志
tpure_log('用户登录成功', 'INFO');
tpure_log('缓存写入失败', 'WARNING');
tpure_log('数据库连接错误', 'ERROR');

// 安全执行代码
$result = tpure_try(function() {
    // 可能出错的代码
    return getData();
}, null, '获取数据失败');
```

### 3️⃣ 安全错误处理器 (`error-handler-safe.php`)

**特点：**
- ✅ 避免触发服务器 WAF
- ✅ 简化日志记录
- ✅ 静默失败处理
- ✅ 使用 `error_log` 代替 `file_put_contents`

**适用场景：**
生产环境，服务器有严格的安全策略

---

## 使用方法

### 场景1：调试配置保存问题

在 `main.php` 中添加调试代码：

```php
if (isset($_POST['PostAJAXPOSTON'])) {
    tpure_log('开始保存配置', 'INFO');
    
    CheckIsRefererValid();
    
    tpure_log('POST数据: ' . print_r($_POST, true), 'DEBUG');
    
    $zbp->Config('tpure')->PostAJAXPOSTON = $_POST['PostAJAXPOSTON'];
    
    tpure_log('配置已设置，准备保存', 'INFO');
    
    $result = $zbp->SaveConfig('tpure');
    
    if ($result) {
        tpure_log('配置保存成功', 'INFO');
    } else {
        tpure_log('配置保存失败', 'ERROR');
    }
}
```

### 场景2：安全执行数据库查询

```php
$articles = tpure_try(function() use ($zbp) {
    return $zbp->GetArticleList('*', '', '', 10);
}, [], '获取文章列表失败');
```

### 场景3：捕获缓存操作错误

```php
$cached = tpure_try(function() use ($zbpcache, $key) {
    return $zbpcache->Get($key);
}, null, 'Redis缓存读取失败');

if ($cached === null) {
    // 缓存未命中或失败，使用数据库查询
}
```

---

## 查看错误日志

### 日志文件位置

- **标准日志：** `zb_users/logs/tpure-error.log`
- **旧版日志：** `zb_users/cache/error.log`

### 通过 FTP 查看

1. 连接 FTP
2. 进入 `zb_users/logs/` 目录
3. 下载 `tpure-error.log`
4. 用文本编辑器打开

### 日志格式

```
[2025-10-21 18:30:15] [INFO] 用户登录成功
[2025-10-21 18:30:20] [WARNING] 缓存写入失败: Redis connection timeout
[2025-10-21 18:30:25] [ERROR] 数据库查询错误: Table not found
```

### 日志级别

| 级别 | 说明 | 示例 |
|------|------|------|
| `INFO` | 一般信息 | 操作成功、状态变更 |
| `WARNING` | 警告信息 | 缓存失败、配置缺失 |
| `ERROR` | 错误信息 | 数据库错误、文件不存在 |
| `CRITICAL` | 严重错误 | 系统异常、致命错误 |

---

## 常见问题

### Q1: 调试模式下页面报错怎么办？

**A:** 这是正常的！调试模式会显示所有错误。查看错误信息，根据提示修复问题。

### Q2: 日志文件太大怎么办？

**A:** 错误处理器会自动轮转日志：
- 当日志超过 10MB 时
- 自动重命名为 `tpure-error.log.2025-10-21-183015.bak`
- 创建新的日志文件

### Q3: 生产环境应该用哪个错误处理器？

**A:** 推荐使用 `error-handler-safe.php`：
- 不会触发服务器 WAF
- 只在调试模式下记录日志
- 静默处理错误，不影响用户体验

### Q4: 如何临时关闭错误处理？

**A:** 在 `include.php` 中：

```php
// 方法1：关闭调试模式
define('TPURE_DEBUG', false);

// 方法2：注释掉错误处理器加载
// if (TPURE_DEBUG && file_exists(...)) {
//     require_once dirname(__FILE__) . '/lib/debug-handler.php';
// }
```

### Q5: 错误日志文件权限问题

**A:** 确保日志目录有写权限：

```bash
chmod 755 zb_users/logs/
chmod 644 zb_users/logs/tpure-error.log
```

---

## 最佳实践

### 1. 开发环境

```php
// include.php 开头
define('TPURE_DEBUG', true);

// 使用 debug-handler.php
// 实时查看错误，快速定位问题
```

### 2. 测试环境

```php
// include.php 开头
define('TPURE_DEBUG', true);

// 使用 error-handler.php
// 记录详细日志，便于回溯问题
```

### 3. 生产环境

```php
// include.php 开头
define('TPURE_DEBUG', false);

// 使用 error-handler-safe.php
// 仅在必要时记录日志，避免性能影响
```

---

## 技术支持

如有问题，请访问：
- 官方网站：https://www.toyean.com
- 主题交流群：https://jq.qq.com/?_wv=1027&k=44zyTKi

---

*最后更新：2025-10-21*

