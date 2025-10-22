# Tpure 主题 - JS 版本号控制实施指南

## 🎯 目标

为所有 JS 文件添加版本号，确保：
- ✅ 缓存带来的性能优势（节省70%流量）
- ✅ 更新时立即生效（修改版本号即可）
- ✅ 避免7天等待期的问题

---

## 📋 当前问题分析

### Tpure 主题中的 JS 文件

```
主题 JS 文件：
├── script/custom.js           ← 自定义脚本
├── script/jquery.min.js       ← jQuery库
├── plugin/codemirror/*.js     ← 代码编辑器
├── plugin/dplayer/DPlayer.js  ← 视频播放器
└── plugin/checkdpi/*.js       ← DPI检测

插件 JS 文件：
└── ../plugin/AdvancedStats/stay_time_tracker.js  ← 停留时间统计
```

### 当前加载方式（可能有问题）

```html
<!-- ❌ 没有版本号 -->
<script src="/zb_users/theme/tpure/script/custom.js"></script>

<!-- ⚠️ 问题：更新后用户要等7天才能看到新版本 -->
```

---

## ✅ 解决方案：统一版本号管理

### 方案1：使用主题版本号（推荐）

#### 步骤1：确认主题版本号位置

**文件：** `theme.xml`

```xml
<?xml version="1.0" encoding="utf-8"?>
<theme version="php">
    <id>tpure</id>
    <name>Tpure</name>
    <version>5.0.12</version>  <!-- ← 主题版本号 -->
    ...
</theme>
```

#### 步骤2：在模板中引用版本号

**文件：** `template/header.php` 或 `include.php`

```php
<!-- 原代码（无版本号） -->
<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/script/custom.js"></script>

<!-- 改为（带版本号） -->
<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/script/custom.js?v=<?php echo $zbp->theme->version; ?>"></script>
```

**实际输出：**
```html
<script src="https://www.dcyzq.com/zb_users/theme/tpure/script/custom.js?v=5.0.12"></script>
```

#### 步骤3：更新 JS 后递增版本号

```xml
<!-- 修改前 -->
<version>5.0.12</version>

<!-- 修复了 custom.js 的Bug，改为： -->
<version>5.0.13</version>
```

**结果：**
- 旧版 URL：`custom.js?v=5.0.12`（浏览器缓存失效）
- 新版 URL：`custom.js?v=5.0.13`（立即下载）✅

---

### 方案2：使用文件修改时间（开发环境）

**适用场景：** 开发阶段，频繁修改 JS

```php
<?php
// 获取文件修改时间作为版本号
$jsFile = $zbp->usersdir . 'theme/tpure/script/custom.js';
$jsVersion = file_exists($jsFile) ? filemtime($jsFile) : time();
?>

<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/script/custom.js?v=<?php echo $jsVersion; ?>"></script>
```

**实际输出：**
```html
<script src="custom.js?v=1729583425"></script>
<!-- 文件修改后，时间戳变化，立即更新 -->
```

**优点：**
- 文件一修改，版本号自动变化
- 无需手动管理版本号

**缺点：**
- 性能略低（需要调用 `filemtime()`）
- 不适合生产环境

---

### 方案3：独立的 JS 版本管理（大型项目）

**文件：** `include.php`

```php
<?php
/**
 * JS 版本号配置
 * 每次修改对应的 JS 文件后，手动递增版本号
 */
define('TPURE_JS_VERSIONS', array(
    'custom'     => '2.1.0',  // custom.js 的版本
    'jquery'     => '3.6.0',  // jQuery 版本
    'dplayer'    => '1.27.1', // DPlayer 版本
    'stay_time'  => '1.5.2',  // stay_time_tracker.js 版本
));

/**
 * 获取 JS 文件的版本号
 */
function tpure_js_version($key) {
    $versions = TPURE_JS_VERSIONS;
    return isset($versions[$key]) ? $versions[$key] : '1.0.0';
}
?>
```

**在模板中使用：**

```php
<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/script/custom.js?v=<?php echo tpure_js_version('custom'); ?>"></script>

<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/plugin/dplayer/DPlayer.min.js?v=<?php echo tpure_js_version('dplayer'); ?>"></script>
```

**优点：**
- 每个 JS 文件独立版本号
- 只更新改动的文件
- 便于追踪哪个文件被修改

---

## 🔧 实际操作步骤

### 第一步：检查当前 JS 加载位置

在 Tpure 主题中搜索所有 JS 加载：

```bash
# 搜索 <script src= 标签
grep -r "<script src=" template/
grep -r "<script src=" include.php
grep -r "<script src=" main.php
```

**常见位置：**
- `template/header.php` - 页头 JS
- `template/footer.php` - 页脚 JS
- `include.php` - 主题核心文件
- `post-single.php` - 文章页特定 JS
- `main.php` - 后台配置页 JS

---

### 第二步：批量添加版本号

#### 示例：修改 `template/header.php`

**查找所有 JS 加载：**

```php
<!-- 原代码 -->
<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/script/custom.js"></script>
<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/plugin/dplayer/DPlayer.min.js"></script>
```

**替换为：**

```php
<!-- 添加版本号 -->
<?php
// 统一使用主题版本号
$theme_version = $zbp->theme->version ?? '5.0.12';
?>

<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/script/custom.js?v=<?php echo $theme_version; ?>"></script>
<script src="<?php echo $zbp->host; ?>zb_users/theme/tpure/plugin/dplayer/DPlayer.min.js?v=<?php echo $theme_version; ?>"></script>
```

---

### 第三步：创建版本号辅助函数

**文件：** `lib/helpers.php`（在现有文件中添加）

```php
/**
 * 生成带版本号的资源 URL
 * 
 * @param string $path 资源相对路径（相对于主题目录）
 * @param string $version 版本号（可选，默认使用主题版本号）
 * @return string 完整的资源 URL
 */
function tpure_asset_url($path, $version = null) {
    global $zbp;
    
    // 如果未指定版本号，使用主题版本号
    if ($version === null) {
        $version = $zbp->theme->version ?? '1.0.0';
    }
    
    // 移除开头的斜杠
    $path = ltrim($path, '/');
    
    // 生成完整 URL
    $url = $zbp->host . 'zb_users/theme/tpure/' . $path;
    
    // 添加版本号参数
    if ($version !== false) {
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $separator . 'v=' . $version;
    }
    
    return $url;
}
```

**在模板中使用：**

```php
<!-- 简化后的代码 -->
<script src="<?php echo tpure_asset_url('script/custom.js'); ?>"></script>
<script src="<?php echo tpure_asset_url('plugin/dplayer/DPlayer.min.js'); ?>"></script>

<!-- 指定特定版本号 -->
<script src="<?php echo tpure_asset_url('script/legacy.js', '1.0.0'); ?>"></script>

<!-- 不使用版本号（特殊情况） -->
<script src="<?php echo tpure_asset_url('script/no-cache.js', false); ?>"></script>
```

---

### 第四步：处理第三方插件的 JS

**问题：** AdvancedStats 插件的 `stay_time_tracker.js` 也需要版本控制

#### 方法A：在插件的 `include.php` 中添加

**文件：** `/zb_users/plugin/AdvancedStats/include.php`

```php
// 在页面加载 JS 的钩子中添加版本号
Add_Filter_Plugin('Filter_Plugin_Zbp_BuildTemplate', 'AdvancedStats_AddVersionToJS');

function AdvancedStats_AddVersionToJS() {
    global $zbp;
    
    // 获取插件版本号
    $version = $zbp->LoadApp('plugin', 'AdvancedStats')->version ?? '1.0';
    
    // 输出 JS（带版本号）
    echo '<script src="' . $zbp->host . 'zb_users/plugin/AdvancedStats/stay_time_tracker.js?v=' . $version . '"></script>';
}
```

#### 方法B：在主题中统一管理

**文件：** `include.php`

```php
// 如果检测到 AdvancedStats 插件已启用
if ($zbp->CheckPlugin('AdvancedStats')) {
    // 获取插件版本
    $stats_version = $zbp->LoadApp('plugin', 'AdvancedStats')->version ?? '1.0';
    
    // 注册 JS 加载钩子
    Add_Filter_Plugin('Filter_Plugin_Zbp_BuildTemplate', function() use ($zbp, $stats_version) {
        echo '<script src="' . $zbp->host . 'zb_users/plugin/AdvancedStats/stay_time_tracker.js?v=' . $stats_version . '"></script>';
    });
}
```

---

## 📊 版本号策略

### 语义化版本号（推荐）

**格式：** `主版本号.次版本号.修订号`

```
5.0.12
│ │ │
│ │ └── 修订号：Bug修复、小调整（向下兼容）
│ └──── 次版本号：新功能（向下兼容）
└────── 主版本号：重大更新（可能不兼容）
```

**示例：**

| 变更 | 版本号变化 | 说明 |
|-----|-----------|------|
| 修复 custom.js 的拼写错误 | 5.0.12 → 5.0.13 | 小修复，递增修订号 |
| 添加新的 JS 功能模块 | 5.0.13 → 5.1.0 | 新功能，递增次版本号 |
| 完全重写 JS 架构 | 5.1.0 → 6.0.0 | 重大更新，递增主版本号 |

---

### 时间戳版本号（自动化）

**格式：** `年月日时分秒` 或 `Unix时间戳`

```php
// 使用当前时间
$version = date('YmdHis');  // 20251022143020

// 或使用文件修改时间
$version = filemtime($js_file);  // 1729583425
```

**优点：**
- 自动递增
- 无需手动管理

**缺点：**
- 不够直观
- 无法体现版本意义

---

## 🎯 完整实施清单

### 必须修改的文件

- [ ] **template/header.php** - 页头 JS 加载
- [ ] **template/footer.php** - 页脚 JS 加载
- [ ] **include.php** - 核心 JS 加载
- [ ] **post-single.php** - 文章页 JS
- [ ] **lib/helpers.php** - 添加 `tpure_asset_url()` 函数
- [ ] **theme.xml** - 确保版本号存在且正确

### 可选修改的文件

- [ ] **main.php** - 后台配置页 JS
- [ ] **template/comments.php** - 评论区 JS
- [ ] **sidebar.php** - 侧边栏 JS

---

## 🧪 测试验证

### 步骤1：检查 HTML 源码

访问网站，右键查看源代码，搜索 `<script`：

```html
<!-- ✅ 正确：带版本号 -->
<script src="https://www.dcyzq.com/zb_users/theme/tpure/script/custom.js?v=5.0.13"></script>

<!-- ❌ 错误：无版本号 -->
<script src="https://www.dcyzq.com/zb_users/theme/tpure/script/custom.js"></script>
```

### 步骤2：测试版本更新

1. 修改 `theme.xml` 中的版本号：`5.0.12` → `5.0.13`
2. 刷新网站首页
3. 查看源代码，确认 JS URL 中的版本号已变为 `5.0.13`
4. 在浏览器开发者工具中查看 Network 标签
5. 确认 JS 文件被重新下载（Status: `200 OK`，而非 `304` 或 `from cache`）

### 步骤3：Chrome 开发者工具验证

```
按 F12 → Network 标签 → 刷新页面

查看 custom.js：
  Request URL: https://www.dcyzq.com/.../custom.js?v=5.0.13
  Status: 200 OK（首次）或 200 (from cache)（再次访问）
  
修改版本号后再次刷新：
  Request URL: https://www.dcyzq.com/.../custom.js?v=5.0.14
  Status: 200 OK（强制重新下载）✅
```

---

## ⚡ 自动化方案（高级）

### 使用 Gulp/Webpack 自动生成版本号

**package.json**

```json
{
  "scripts": {
    "build": "gulp build && node scripts/update-version.js"
  }
}
```

**scripts/update-version.js**

```javascript
const fs = require('fs');
const crypto = require('crypto');

// 读取 theme.xml
let themeXml = fs.readFileSync('theme.xml', 'utf8');

// 计算 custom.js 的哈希值
const jsContent = fs.readFileSync('script/custom.js');
const jsHash = crypto.createHash('md5').update(jsContent).digest('hex').substring(0, 8);

// 更新版本号
const currentVersion = themeXml.match(/<version>([\d.]+)<\/version>/)[1];
const newVersion = incrementVersion(currentVersion);

themeXml = themeXml.replace(
  /<version>[\d.]+<\/version>/,
  `<version>${newVersion}</version>`
);

fs.writeFileSync('theme.xml', themeXml);

console.log(`✅ 版本号已更新：${currentVersion} → ${newVersion}`);

function incrementVersion(version) {
  const parts = version.split('.');
  parts[2] = parseInt(parts[2]) + 1;
  return parts.join('.');
}
```

---

## 📝 维护指南

### 日常开发流程

```
1. 修改 JS 文件（如 custom.js）
   ↓
2. 测试功能是否正常
   ↓
3. 修改 theme.xml 中的版本号
   <version>5.0.12</version> → <version>5.0.13</version>
   ↓
4. 上传到服务器
   - theme.xml
   - script/custom.js
   ↓
5. 清除服务器缓存（如果有 Redis）
   ↓
6. 访问网站，按 Ctrl+F5 强制刷新
   ↓
7. 验证新版本已生效
```

### 紧急Bug修复流程

```
1. 发现严重Bug
   ↓
2. 立即修复 JS 文件
   ↓
3. 版本号跳级（5.0.12 → 5.0.14）
   ↓
4. 立即上传并发布公告：
   "紧急修复已发布，请按 Ctrl+F5 刷新页面！"
   ↓
5. 监控错误日志，确认Bug已修复
```

---

## 🎓 总结

### 最佳实践

| 做法 | 推荐度 | 说明 |
|-----|-------|------|
| **主题版本号** | ⭐⭐⭐⭐⭐ | 简单、统一、易维护 |
| **独立版本号** | ⭐⭐⭐⭐ | 适合大型项目 |
| **文件时间戳** | ⭐⭐⭐ | 适合开发环境 |
| **不用版本号** | ❌ | 导致7天更新延迟 |

### 核心原则

```
1. ✅ 所有 JS 必须带版本号
2. ✅ 版本号统一管理（theme.xml）
3. ✅ 修改 JS 后必须递增版本号
4. ✅ 使用辅助函数简化代码
5. ⚠️ 紧急修复时跳级版本号
```

### 预期效果

**实施前：**
- 更新 JS 后用户要等 7天 才能看到新版本
- 可能导致功能失效、Bug 无法修复

**实施后：**
- 更新 JS 后用户**立即**看到新版本（只需修改版本号）
- 保留缓存的性能优势（节省 70% 流量）
- **完美平衡性能和更新速度** ✅

---

**相关文档：**
- [浏览器缓存HTTP说明.md](./浏览器缓存HTTP说明.md)
- [JS缓存影响说明.md](./JS缓存影响说明.md)

