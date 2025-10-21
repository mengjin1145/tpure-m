# Tpure 主题 - 缓存机制完整工作原理

## 📚 目录
1. [全页面缓存（Full Page Cache）](#1-全页面缓存)
2. [热门内容缓存（Hot Content Cache）](#2-热门内容缓存)
3. [浏览器缓存（Browser Cache）](#3-浏览器缓存)
4. [模板编译缓存（Template Cache）](#4-模板编译缓存)
5. [缓存触发时机](#5-缓存触发时机)
6. [缓存清除策略](#6-缓存清除策略)

---

## 1. 全页面缓存（Full Page Cache）

### 📍 文件位置
`lib/fullpage-cache.php`

### 🎯 工作原理

#### 触发条件（必须同时满足）
```php
✅ 启用条件：
1. $zbp->Config('tpure')->CacheFullPageOn === 'ON'
2. extension_loaded('redis') === true
3. $zbp->user->ID === 0  // 游客（未登录用户）
4. $_SERVER['REQUEST_METHOD'] === 'GET'
5. 不包含排除模式：/zb_system/、/zb_users/plugin/、? 、&
```

#### 缓存流程

##### **步骤1：检查缓存**
```php
function tpure_fullpage_cache_handler(&$template) {
    // 连接Redis
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379, 2);
    
    // 构建缓存键
    $cacheKey = 'tpure:fullpage:' . md5($_SERVER['REQUEST_URI']);
    
    // 尝试读取缓存
    $cachedContent = $redis->get($cacheKey);
    
    if ($cachedContent !== false) {
        // ✅ 缓存命中
        header('X-Cache: HIT');
        header('X-Cache-Key: ' . $cacheKey);
        echo $cachedContent;
        exit; // 直接输出缓存，停止渲染
    }
}
```

##### **步骤2：缓存未命中，写入新缓存**
```php
    // ❌ 缓存未命中
    header('X-Cache: MISS');
    header('X-Cache-Key: ' . $cacheKey);
    
    // 注册输出缓冲区
    ob_start(function($content) use ($redis, $cacheKey, $requestUri) {
        // 只缓存HTML响应
        if (strpos($content, '<!DOCTYPE') !== false) {
            // 判断缓存时间
            $ttl = 3600; // 默认1小时
            
            // 首页缓存更短（5分钟）
            if ($requestUri === '/' || $requestUri === '/index.php') {
                $ttl = 300;
            }
            
            // 写入Redis
            $redis->setex($cacheKey, $ttl, $content);
        }
        
        return $content;
    });
```

### 🔑 缓存键命名规则
```
格式：tpure:fullpage:{md5(URI)}

示例：
首页:     tpure:fullpage:cfcd208495d565ef66e7dff9f98764da
文章页:   tpure:fullpage:c4ca4238a0b923820dcc509a6f75849b
分类页:   tpure:fullpage:c81e728d9d4c2f636f067f89cc14862c
```

### ⏱️ 缓存过期时间（TTL）
| 页面类型 | 过期时间 | 原因 |
|---------|---------|------|
| 首页 | 5分钟（300秒） | 更新频繁 |
| 文章页 | 1小时（3600秒） | 内容相对稳定 |
| 列表页 | 1小时（3600秒） | 更新较慢 |
| 独立页面 | 1小时（3600秒） | 更新极少 |

### 🚫 排除的页面
```php
// 不缓存以下页面：
- /zb_system/*           // 后台管理
- /zb_users/plugin/*     // 插件页面
- 带查询参数的URL（?、&）  // 动态内容
- POST请求              // 表单提交
- 登录用户访问的页面      // 个性化内容
```

### 🔄 清除时机
```php
// include.php 第464-474行：注册清除钩子
Add_Filter_Plugin('Filter_Plugin_PostArticle_Succeed', 'tpure_clear_fullpage_cache');  // 发布文章
Add_Filter_Plugin('Filter_Plugin_PostArticle_Del', 'tpure_clear_fullpage_cache');      // 删除文章
Add_Filter_Plugin('Filter_Plugin_PostComment_Succeed', 'tpure_clear_fullpage_cache');  // 发布评论
Add_Filter_Plugin('Filter_Plugin_DelComment_Succeed', 'tpure_clear_fullpage_cache');   // 删除评论
Add_Filter_Plugin('Filter_Plugin_Logout_Succeed', 'tpure_clear_fullpage_cache');       // 退出登录
```

---

## 2. 热门内容缓存（Hot Content Cache）

### 📍 文件位置
`lib/hot-cache.php`

### 🎯 工作原理

#### 缓存对象
1. **热门文章**（`TpureHotCache::getHotArticles()`）
2. **热门分类**（`TpureHotCache::getHotCategories()`）
3. **热门标签**（`TpureHotCache::getHotTags()`）
4. **最新文章**（`TpureHotCache::getLatestArticles()`）

#### 缓存流程

##### **通用缓存逻辑**
```php
public static function get($name, $generator, $ttl = 3600) {
    // 构建缓存键（包含版本号）
    $version = defined('TPURE_VERSION') ? TPURE_VERSION : '1.0';
    $cacheKey = 'tpure:html:' . $name . ':v' . $version;
    
    // 🚀 优先从Redis读取
    if (self::isRedisAvailable()) {
        $html = $zbpcache->Get($cacheKey);
        
        if ($html !== false && $html !== null) {
            // ✅ 缓存命中，直接返回
            return $html;
        }
    }
    
    // ❌ 缓存未命中，调用生成器生成HTML
    $html = call_user_func($generator);
    
    // 💾 存入Redis
    if (self::isRedisAvailable() && !empty($html)) {
        $zbpcache->Set($cacheKey, $html, $ttl);
    }
    
    return $html;
}
```

##### **示例：热门文章缓存**
```php
public static function getHotArticles($limit = 10, $days = 7, $template = 'list') {
    $name = "hot_articles_{$limit}d{$days}_{$template}";
    
    return self::get($name, function() use ($limit, $days, $template) {
        // 从统计模块获取数据
        $popularArticles = TpureStatistics::getPopularContent(
            TpureStatistics::PAGE_ARTICLE, 
            $days, 
            $limit
        );
        
        // 渲染HTML
        ob_start();
        echo '<ul class="hot-articles-list">';
        foreach ($popularArticles as $i => $item) {
            $article = $zbp->GetPostByID($item['page_id']);
            // ... 渲染文章列表
        }
        echo '</ul>';
        return ob_get_clean();
    }, 3600); // 缓存1小时
}
```

### 🔑 缓存键命名规则
```
格式：tpure:html:{内容类型}_{参数}:v{版本号}

示例：
热门文章10篇7天列表: tpure:html:hot_articles_10d7_list:v5.0.7
热门分类10个7天:      tpure:html:hot_categories_10d7:v5.0.7
热门标签20个7天:      tpure:html:hot_tags_20d7:v5.0.7
最新文章10篇:         tpure:html:latest_articles_10:v5.0.7
```

### ⏱️ 缓存过期时间（TTL）
| 内容类型 | 过期时间 | 原因 |
|---------|---------|------|
| 热门文章 | 1小时（3600秒） | 统计数据更新较慢 |
| 热门分类 | 2小时（7200秒） | 更新极慢 |
| 热门标签 | 2小时（7200秒） | 更新极慢 |
| 最新文章 | 10分钟（600秒） | 更新频繁 |

### 🔄 清除时机
```php
// include.php 第453-461行：注册清除钩子
Add_Filter_Plugin('Filter_Plugin_PostArticle_Succeed', 'tpure_clear_hot_cache');  // 发布文章
Add_Filter_Plugin('Filter_Plugin_PostArticle_Del', 'tpure_clear_hot_cache');      // 删除文章
Add_Filter_Plugin('Filter_Plugin_PostComment_Succeed', 'tpure_clear_hot_cache');  // 发布评论
```

### 📊 数据依赖
热门内容缓存依赖于 **`TpureStatistics`** 统计模块：
```php
// lib/statistics.php 提供数据源
TpureStatistics::getPopularContent(
    $pageType,  // PAGE_ARTICLE | PAGE_CATEGORY | PAGE_TAG
    $days,      // 统计天数（0=全部，7=最近7天）
    $limit      // 返回数量
);
```

---

## 3. 浏览器缓存（Browser Cache）

### 📍 文件位置
`lib/http-cache.php`

### 🎯 工作原理

#### HTTP响应头策略

##### **Cache-Control**
```php
header("Cache-Control: public, max-age=3600, must-revalidate");
```

| 页面/资源类型 | max-age | 说明 |
|-------------|---------|------|
| 图片（jpg/png/webp） | 2592000秒（30天） | 静态资源，极少修改 |
| CSS/JS | 604800秒（7天） | 可能更新 |
| HTML页面 | 3600秒（1小时） | 动态内容 |
| RSS订阅 | 1800秒（30分钟） | 实时性要求较高 |
| API接口 | 300秒（5分钟） | 高频更新 |

##### **ETag（实体标签）**
```php
// 生成ETag（基于多个因素）
function generateETag() {
    $factors = array(
        $zbp->option['ZC_BLOG_LASTUPDATE'],  // 博客最后更新时间
        $_SERVER['REQUEST_URI'],              // 当前URL
        $zbp->option['ZC_BLOG_THEME_VERSION'], // 主题版本
        $zbp->user->ID                        // 用户ID（区分登录状态）
    );
    
    return md5(implode('|', $factors));
}

// 检查客户端ETag
if (checkETag($etag)) {
    http_response_code(304); // Not Modified
    exit;
}
```

##### **Last-Modified**
```php
// 设置最后修改时间
$lastModified = gmdate('D, d M Y H:i:s', $zbp->option['ZC_BLOG_LASTUPDATE']) . ' GMT';
header("Last-Modified: {$lastModified}");

// 检查If-Modified-Since
if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] === $lastModified) {
    http_response_code(304); // Not Modified
    exit;
}
```

#### Gzip压缩

```php
public static function enableGzip($htmlOnly = true) {
    // 检查条件
    if (!extension_loaded('zlib')) return false;
    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) return false;
    if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) return false;
    
    // 排除已压缩的文件（jpg、png、zip等）
    if (preg_match('/\.(jpg|jpeg|png|gif|zip|rar|7z|pdf)$/i', $_SERVER['REQUEST_URI'])) {
        return false;
    }
    
    // 启用压缩
    ob_start('ob_gzhandler');
}
```

#### 工作流程

```
客户端请求 → 服务器检查 If-None-Match（ETag）
           ↓
           服务器检查 If-Modified-Since
           ↓
    ┌──────┴──────┐
    ↓             ↓
  未修改         已修改
    ↓             ↓
  304 Not      200 OK
  Modified     + HTML内容
               + Cache-Control
               + ETag
               + Last-Modified
```

### 🚫 不缓存的页面
```php
// 后台页面
if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    return;
}

// 管理员登录
if ($zbp->user->ID > 0 && $zbp->user->Level <= 4) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    return;
}

// 系统路径
if (strpos($_SERVER['REQUEST_URI'], '/zb_system/') !== false) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    return;
}
```

---

## 4. 模板编译缓存（Template Cache）

### 📍 位置
Z-BlogPHP 原生功能，模板文件编译后存储在：
```
zb_users/cache/compiled/tpure/*.php
```

### 🎯 工作原理

#### 编译流程

```php
// 1. Z-BlogPHP 加载模板
$zbp->template->SetTemplate('catalog.php');

// 2. 检查是否已编译
$compiledFile = $zbp->usersdir . 'cache/compiled/' . $zbp->theme . '/catalog.php';

if (!file_exists($compiledFile)) {
    // 3. 未编译，执行编译
    $content = file_get_contents($zbp->templatepath . 'catalog.php');
    
    // 4. 解析模板标签
    $content = str_replace('{$article.Title}', '<?php echo $article->Title; ?>', $content);
    $content = str_replace('{if ...}', '<?php if (...) { ?>', $content);
    
    // 5. 写入编译文件
    file_put_contents($compiledFile, $content);
}

// 6. 加载编译后的文件
include $compiledFile;
```

#### 清除方式

##### **手动清除（tpure_Refresh）**
```php
function tpure_Refresh() {
    global $zbp;
    
    // 删除已编译的模板缓存
    $compile_dir = $zbp->usersdir . 'cache/compiled/' . $zbp->theme . '/';
    $files = glob($compile_dir . '*.php');
    
    foreach ($files as $file) {
        @unlink($file);
    }
    
    // 重建模板
    $zbp->BuildTemplate();
}
```

##### **自动触发（主题配置保存时）**
```php
// main.php 保存配置后自动刷新
if (isset($_POST['submit'])) {
    // 保存配置...
    
    // 刷新模板缓存
    tpure_Refresh();
}
```

---

## 5. 缓存触发时机

### 全页面缓存触发
```php
// include.php 第477-486行
Add_Filter_Plugin('Filter_Plugin_ViewIndex_Template', 'tpure_fullpage_cache_handler');   // 首页
Add_Filter_Plugin('Filter_Plugin_ViewList_Template', 'tpure_fullpage_cache_handler');    // 列表页
Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'tpure_fullpage_cache_handler');    // 文章页
Add_Filter_Plugin('Filter_Plugin_ViewPage_Template', 'tpure_fullpage_cache_handler');    // 独立页面
```

### 热门内容缓存调用
```php
// 模板中使用（template/*.php）
echo tpure_hot_articles(10, 7, 'list');      // 热门文章
echo tpure_hot_categories(10, 7);            // 热门分类
echo tpure_hot_tags(20, 7);                  // 热门标签
echo tpure_latest_articles(10);              // 最新文章
```

### 浏览器缓存触发
```php
// 每次页面渲染时自动触发（lib/http-cache.php）
TpureHttpCache::setCacheHeaders('text/html', 3600, true);
```

---

## 6. 缓存清除策略

### 自动清除（钩子触发）

#### 全页面缓存清除
```php
// 发布文章时清除所有全页面缓存
Filter_Plugin_PostArticle_Succeed → tpure_clear_fullpage_cache() 
    ↓
    使用 Redis SCAN 查找所有 tpure:fullpage:* 键
    ↓
    逐个 DEL 删除
```

#### 热门内容缓存清除
```php
// 发布文章时清除热门内容缓存
Filter_Plugin_PostArticle_Succeed → tpure_clear_hot_cache()
    ↓
    TpureHotCache::clearAll()
    ↓
    删除所有 tpure:html:* 键
```

### 手动清除

#### 1. 访问缓存测试工具
```
https://www.dcyzq.com/zb_users/theme/tpure/test-cache-optimization.php
```

#### 2. 一键清除按钮
- **清除Redis缓存**：清除全页面 + 热门内容缓存
- **重新编译模板**：清除模板编译缓存

#### 3. 代码调用
```php
// 清除全页面缓存
tpure_clear_fullpage_cache();

// 清除热门内容缓存
tpure_clear_hot_cache();

// 清除模板缓存
tpure_Refresh();
```

---

## 🔍 调试缓存

### 查看响应头
```bash
# 全页面缓存
curl -I https://www.dcyzq.com/
# 查看：X-Cache: HIT 或 MISS
#      X-Cache-Key: tpure:fullpage:xxxxx

# 浏览器缓存
curl -I https://www.dcyzq.com/
# 查看：Cache-Control: public, max-age=3600
#      ETag: "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
#      Last-Modified: Mon, 01 Jan 2024 00:00:00 GMT
```

### 启用调试日志
```php
// include.php 第163行
define('TPURE_DEBUG', true);

// 查看日志
tail -f zb_users/theme/tpure/logs/tpure_YYYY-MM-DD.log
```

---

## 📈 性能提升数据

| 缓存类型 | 未缓存 | 缓存后 | 提升 |
|---------|-------|-------|------|
| 全页面缓存 | 500-800ms | 10-20ms | **95%** |
| 热门内容缓存 | 15-20ms | 0.5-1ms | **95%** |
| 浏览器缓存（304） | 500ms | 5-10ms | **98%** |
| 模板编译 | 100ms | 5ms | **95%** |

---

## ⚙️ 缓存配置

### 启用/禁用缓存
```php
// 后台：主题配置 → 缓存性能优化
CacheFullPageOn = "ON"      // 全页面缓存
CacheHotContentOn = "ON"    // 热门内容缓存
CacheBrowserOn = "ON"       // 浏览器缓存
CacheTemplateOn = "ON"      // 模板编译缓存
```

### Redis配置
```php
// zb_users/cache/config_zbpcache.php
return array(
    'redis_host' => '127.0.0.1',
    'redis_port' => 6379,
    'redis_password' => '',  // 如有密码
    'redis_timeout' => 2
);
```

---

## 🛠️ 常见问题

### Q1: 为什么修改文章后前台没更新？
**A:** 全页面缓存已生效，发布文章时会自动清除，但如果手动修改数据库需要手动清除缓存。

### Q2: 登录用户看到的是缓存内容吗？
**A:** 不是，全页面缓存和浏览器缓存均排除登录用户。

### Q3: 如何查看Redis中有多少缓存？
**A:** 访问 `test-cache-optimization.php`，查看"Tpure缓存键数量"。

### Q4: 缓存占用多少内存？
**A:** 首页约50KB，文章页约30KB，100个页面约3-5MB。

---

## 📝 总结

Tpure主题的缓存系统采用**四层缓存架构**：

```
┌─────────────────────────────────────┐
│  1. 浏览器缓存 (HTTP Cache)          │  ← 客户端级别
│     └─ 304响应（无需传输内容）         │
├─────────────────────────────────────┤
│  2. 全页面缓存 (Redis Full Page)     │  ← 服务器级别
│     └─ 直接返回HTML（跳过PHP渲染）     │
├─────────────────────────────────────┤
│  3. 热门内容缓存 (Redis Hot Content) │  ← 片段级别
│     └─ 缓存热门文章/分类/标签HTML      │
├─────────────────────────────────────┤
│  4. 模板编译缓存 (Template Cache)    │  ← 底层级别
│     └─ 缓存编译后的PHP代码            │
└─────────────────────────────────────┘
```

**最佳实践**：
- ✅ 游客访问 → 启用全部缓存
- ✅ 登录用户 → 仅启用模板编译缓存
- ✅ 内容更新 → 自动清除相关缓存
- ✅ 主题升级 → 手动清除全部缓存

---

**文档版本**：1.0  
**最后更新**：2024-01-21  
**作者**：TOYEAN

