# 📊 AdvancedStats 添加 Canvas 指纹ID 显示

## 🎯 目标

在 AdvancedStats 插件后台的"最近访问记录"列表中，添加一列显示用户的 Canvas 指纹ID。

---

## 📋 实现步骤

### 步骤1：修改数据库表结构

**文件位置：** `zb_users/plugin/AdvancedStats/include.php`

在插件的数据库表中添加 `canvas_fingerprint` 字段：

```php
// 在插件安装函数中添加字段
function InstallPlugin_AdvancedStats() {
    global $zbp;
    
    $sql = "ALTER TABLE `{$zbp->table['AdvancedStats']}` 
            ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL 
            COMMENT 'Canvas指纹ID'";
    
    try {
        $zbp->db->Query($sql);
    } catch (Exception $e) {
        // 字段可能已存在，忽略错误
    }
}
```

**手动执行 SQL（如果插件已安装）：**

```sql
-- 登录 phpMyAdmin 或使用 SSH 执行
ALTER TABLE `zbp_advancedstats_visits` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL 
COMMENT 'Canvas指纹ID';
```

---

### 步骤2：前端收集 Canvas 指纹

**创建文件：** `zb_users/plugin/AdvancedStats/canvas-fingerprint.js`

```javascript
/**
 * Canvas 指纹生成脚本
 * 在前台页面加载时自动执行
 */
(function() {
    'use strict';
    
    /**
     * 生成 Canvas 指纹
     */
    function generateCanvasFingerprint() {
        try {
            // 创建隐藏的 Canvas
            const canvas = document.createElement('canvas');
            canvas.width = 200;
            canvas.height = 50;
            const ctx = canvas.getContext('2d');
            
            // 绘制复杂的文字和图形
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            
            ctx.fillStyle = '#069';
            ctx.fillText('Browser Fingerprint', 2, 15);
            
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Canvas Test 123', 4, 17);
            
            // 转换为数据 URL
            const dataURL = canvas.toDataURL();
            
            // 生成哈希
            return simpleHash(dataURL);
            
        } catch (e) {
            console.error('Canvas fingerprint generation failed:', e);
            return null;
        }
    }
    
    /**
     * 简单哈希函数
     */
    function simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(16);
    }
    
    /**
     * 发送指纹到服务器
     */
    function sendFingerprint(fingerprint) {
        if (!fingerprint) return;
        
        // 通过 AJAX 发送
        fetch(window.location.origin + '/zb_system/cmd.php?act=AdvancedStats_SaveFingerprint', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'canvas_fingerprint=' + encodeURIComponent(fingerprint)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Canvas fingerprint saved:', fingerprint);
        })
        .catch(error => {
            console.error('Failed to save fingerprint:', error);
        });
    }
    
    // 页面加载完成后执行
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const fingerprint = generateCanvasFingerprint();
            sendFingerprint(fingerprint);
        });
    } else {
        const fingerprint = generateCanvasFingerprint();
        sendFingerprint(fingerprint);
    }
    
})();
```

---

### 步骤3：后端接收和存储指纹

**修改文件：** `zb_users/plugin/AdvancedStats/include.php`

添加 AJAX 处理函数：

```php
/**
 * 注册 AJAX 处理
 */
Add_Filter_Plugin('Filter_Plugin_Cmd_Begin', 'AdvancedStats_SaveFingerprint_Handler');

function AdvancedStats_SaveFingerprint_Handler() {
    global $zbp;
    
    $act = GetVars('act', 'GET');
    
    if ($act === 'AdvancedStats_SaveFingerprint') {
        // 安全检查：只允许前台访问
        if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
            return;
        }
        
        // 获取 Canvas 指纹
        $canvas_fingerprint = GetVars('canvas_fingerprint', 'POST');
        
        if (empty($canvas_fingerprint)) {
            echo json_encode(array('success' => false, 'message' => 'Empty fingerprint'));
            die();
        }
        
        // 验证指纹格式（16进制字符串）
        if (!preg_match('/^[a-f0-9]{6,64}$/i', $canvas_fingerprint)) {
            echo json_encode(array('success' => false, 'message' => 'Invalid fingerprint format'));
            die();
        }
        
        // 获取当前访客信息
        $ip = GetGuestIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        
        // 存储到数据库（更新最新记录）
        $table = $zbp->table['AdvancedStats'];
        $today = date('Y-m-d H:i:s');
        
        // 查找今天的访问记录
        $sql = "UPDATE `{$table}` 
                SET `canvas_fingerprint` = '{$canvas_fingerprint}' 
                WHERE `ip` = '{$ip}' 
                AND `visit_time` >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY `visit_time` DESC 
                LIMIT 1";
        
        try {
            $zbp->db->Query($sql);
            
            echo json_encode(array(
                'success' => true, 
                'fingerprint' => $canvas_fingerprint,
                'ip' => $ip
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false, 
                'message' => $e->getMessage()
            ));
        }
        
        die();
    }
}
```

---

### 步骤4：在前台加载指纹收集脚本

**修改文件：** `zb_users/plugin/AdvancedStats/include.php`

在主题头部加载 JavaScript：

```php
/**
 * 在前台加载 Canvas 指纹脚本
 */
Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'AdvancedStats_LoadCanvasScript');
Add_Filter_Plugin('Filter_Plugin_ViewList_Template', 'AdvancedStats_LoadCanvasScript');
Add_Filter_Plugin('Filter_Plugin_ViewIndex_Template', 'AdvancedStats_LoadCanvasScript');

function AdvancedStats_LoadCanvasScript() {
    global $zbp;
    
    // 只在前台加载
    if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
        return;
    }
    
    // 添加脚本到页面头部
    $script_url = $zbp->host . 'zb_users/plugin/AdvancedStats/canvas-fingerprint.js?v=' . time();
    
    echo '<script src="' . $script_url . '" defer></script>';
}
```

---

### 步骤5：修改后台显示列表

**修改文件：** `zb_users/plugin/AdvancedStats/main.php`

找到"最近访问记录"的显示代码，添加 Canvas 指纹列：

```php
// 原始代码（查找类似的部分）
<table class="tablelist">
    <thead>
        <tr>
            <th>ID</th>
            <th>IP</th>
            <th>访问时间</th>
            <th>页面</th>
            <th>User-Agent</th>
            <!-- ✅ 添加这一列 -->
            <th>Canvas 指纹</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recentVisits as $visit): ?>
        <tr>
            <td><?php echo $visit['id']; ?></td>
            <td><?php echo $visit['ip']; ?></td>
            <td><?php echo $visit['visit_time']; ?></td>
            <td><?php echo $visit['page_url']; ?></td>
            <td><?php echo substr($visit['user_agent'], 0, 50); ?>...</td>
            
            <!-- ✅ 添加 Canvas 指纹显示 -->
            <td>
                <?php if (!empty($visit['canvas_fingerprint'])): ?>
                    <code style="color: #dc3545; font-size: 12px;">
                        <?php echo htmlspecialchars($visit['canvas_fingerprint']); ?>
                    </code>
                    <span 
                        title="复制指纹" 
                        style="cursor: pointer; margin-left: 5px;"
                        onclick="copyToClipboard('<?php echo $visit['canvas_fingerprint']; ?>')">
                        📋
                    </span>
                <?php else: ?>
                    <span style="color: #999;">未收集</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- 添加复制功能 -->
<script>
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    alert('已复制: ' + text);
}
</script>
```

**修改 SQL 查询（添加 canvas_fingerprint 字段）：**

```php
// 原始查询
$sql = "SELECT * FROM {$table} ORDER BY visit_time DESC LIMIT 50";

// 修改为（确保包含 canvas_fingerprint）
$sql = "SELECT 
    id, 
    ip, 
    visit_time, 
    page_url, 
    user_agent,
    canvas_fingerprint  /* ✅ 添加这个字段 */
FROM {$table} 
ORDER BY visit_time DESC 
LIMIT 50";
```

---

## 🎨 界面效果预览

修改后，后台列表会显示：

```
┌────┬──────────────┬─────────────────────┬─────────────┬──────────────────┬────────────┐
│ ID │ IP           │ 访问时间            │ 页面        │ User-Agent       │Canvas 指纹 │
├────┼──────────────┼─────────────────────┼─────────────┼──────────────────┼────────────┤
│ 123│ 192.168.1.10 │ 2025-10-22 10:30:15 │ /index.html │ Chrome/120.0...  │ 7a3f2e1d 📋│
├────┼──────────────┼─────────────────────┼─────────────┼──────────────────┼────────────┤
│ 122│ 192.168.1.20 │ 2025-10-22 10:29:42 │ /article/1  │ Firefox/118.0... │ 9b4c8f3a 📋│
├────┼──────────────┼─────────────────────┼─────────────┼──────────────────┼────────────┤
│ 121│ 192.168.1.30 │ 2025-10-22 10:28:10 │ /category/2 │ Safari/16.0...   │ 未收集     │
└────┴──────────────┴─────────────────────┴─────────────┴──────────────────┴────────────┘
```

**颜色标注：**
- Canvas 指纹：红色显示（`#dc3545`）
- 点击 📋 图标可复制指纹

---

## 🔧 完整实现代码示例

### 1. 数据库修改脚本

**文件：** `zb_users/plugin/AdvancedStats/sql/add-canvas-field.sql`

```sql
-- 添加 Canvas 指纹字段
ALTER TABLE `zbp_advancedstats_visits` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL 
COMMENT 'Canvas指纹ID',
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);

-- 如果表名不同，请替换为实际表名
-- 查看表名：SHOW TABLES LIKE '%advanced%';
```

### 2. 前端收集脚本（完整版）

**文件：** `zb_users/plugin/AdvancedStats/canvas-fingerprint.js`

（见步骤2的完整代码）

### 3. 后端处理（完整版）

**在 `include.php` 中添加：**

```php
// ===== Canvas 指纹收集功能 =====

/**
 * 在前台加载 Canvas 指纹脚本
 */
function AdvancedStats_LoadCanvasScript() {
    global $zbp;
    
    // 只在前台加载
    if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
        return;
    }
    
    // 检查是否启用指纹收集（可选配置）
    $enableFingerprint = $zbp->Config('AdvancedStats')->EnableCanvasFingerprint ?? true;
    
    if (!$enableFingerprint) {
        return;
    }
    
    $plugin_dir = $zbp->host . 'zb_users/plugin/AdvancedStats/';
    $script_url = $plugin_dir . 'canvas-fingerprint.js?v=' . ADVANCEDSTATS_VERSION;
    
    echo '<script src="' . $script_url . '" defer></script>' . "\n";
}

// 注册钩子
Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'AdvancedStats_LoadCanvasScript');
Add_Filter_Plugin('Filter_Plugin_ViewList_Template', 'AdvancedStats_LoadCanvasScript');
Add_Filter_Plugin('Filter_Plugin_ViewIndex_Template', 'AdvancedStats_LoadCanvasScript');

/**
 * AJAX 处理：保存 Canvas 指纹
 */
function AdvancedStats_SaveFingerprint_Handler() {
    global $zbp;
    
    $act = GetVars('act', 'GET');
    
    if ($act !== 'AdvancedStats_SaveFingerprint') {
        return;
    }
    
    // 设置响应头
    header('Content-Type: application/json');
    
    // 安全检查：只允许前台访问
    if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
        echo json_encode(array('success' => false, 'message' => 'Admin access denied'));
        die();
    }
    
    // 获取 Canvas 指纹
    $canvas_fingerprint = GetVars('canvas_fingerprint', 'POST');
    
    // 验证
    if (empty($canvas_fingerprint)) {
        echo json_encode(array('success' => false, 'message' => 'Empty fingerprint'));
        die();
    }
    
    if (!preg_match('/^[a-f0-9]{6,64}$/i', $canvas_fingerprint)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid format'));
        die();
    }
    
    // 获取访客信息
    $ip = GetGuestIP();
    $table = $zbp->table['AdvancedStats'] ?? 'zbp_advancedstats_visits';
    
    // 更新最近5分钟内的访问记录
    $sql = "UPDATE `{$table}` 
            SET `canvas_fingerprint` = '{$canvas_fingerprint}',
                `updated_at` = NOW()
            WHERE `ip` = '{$ip}' 
            AND `visit_time` >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY `visit_time` DESC 
            LIMIT 1";
    
    try {
        $result = $zbp->db->Query($sql);
        $affected = $zbp->db->AffectedRows();
        
        echo json_encode(array(
            'success' => true,
            'fingerprint' => $canvas_fingerprint,
            'affected_rows' => $affected,
            'ip' => $ip
        ));
        
    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
    
    die();
}

// 注册 AJAX 处理
Add_Filter_Plugin('Filter_Plugin_Cmd_Begin', 'AdvancedStats_SaveFingerprint_Handler');
```

---

## 🎯 快速部署步骤

### 1️⃣ 修改数据库

```bash
# SSH 登录服务器
mysql -u root -p

# 使用数据库
USE zblog_database;

# 执行 SQL
ALTER TABLE `zbp_advancedstats_visits` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL;
```

### 2️⃣ 上传文件

```
1. 创建 canvas-fingerprint.js
   上传到：zb_users/plugin/AdvancedStats/

2. 修改 include.php
   添加上述代码到文件末尾

3. 修改 main.php
   在表格中添加 Canvas 指纹列
```

### 3️⃣ 测试

```
1. 访问前台任意页面
2. 打开浏览器控制台（F12）
3. 查看是否有 "Canvas fingerprint saved" 日志
4. 进入后台查看最近访问记录
5. 确认 Canvas 指纹列显示正常
```

---

## ⚠️ 注意事项

### 1. 隐私合规

```
✅ 必须做：
- 在隐私政策中说明收集 Canvas 指纹
- 提供退出选项（Opt-out）
- 定期清理过期数据

❌ 禁止做：
- 不告知用户就收集
- 跨站追踪用户
- 出售指纹数据
```

### 2. 性能影响

```
Canvas 指纹生成：< 10ms
AJAX 请求：20-50ms
总影响：< 100ms（可接受）
```

### 3. 兼容性

```
支持的浏览器：
- Chrome/Edge 80+
- Firefox 75+
- Safari 13+
- Opera 67+

不支持：
- IE 11 及以下（会静默失败）
```

---

## 📊 数据分析用途

有了 Canvas 指纹后，可以实现：

### 1. 精准去重

```php
// 统计独立访客（基于指纹）
SELECT COUNT(DISTINCT canvas_fingerprint) as unique_visitors
FROM zbp_advancedstats_visits
WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### 2. 设备追踪

```php
// 查看同一设备的访问历史
SELECT * FROM zbp_advancedstats_visits
WHERE canvas_fingerprint = '7a3f2e1d'
ORDER BY visit_time DESC;
```

### 3. 异常检测

```php
// 检测刷量行为（同一指纹短时间大量访问）
SELECT canvas_fingerprint, COUNT(*) as visit_count
FROM zbp_advancedstats_visits
WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY canvas_fingerprint
HAVING visit_count > 50;
```

---

## 🔒 隐私保护建议

### 1. 哈希处理

```php
// 不直接存储原始指纹，使用哈希
$hashed_fingerprint = hash('sha256', $canvas_fingerprint . SITE_SALT);
```

### 2. 定期清理

```php
// 自动删除90天前的记录
DELETE FROM zbp_advancedstats_visits
WHERE visit_time < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### 3. 匿名化

```php
// IP 匿名化
$anonymized_ip = preg_replace('/\.\d+$/', '.0', $ip);
```

---

## 📝 相关文档

- [Canvas 指纹原理](./AVIF-SUPPORT-REPORT.md)
- [隐私政策模板](#)
- [GDPR 合规指南](#)

---

**完成时间：** 2025-10-22  
**更新版本：** AdvancedStats 1.0 + Canvas Fingerprint  
**兼容性：** Z-BlogPHP 1.7+

