# AdvancedStats 插件 - 添加 Canvas 指纹ID显示

## 📌 需求
在 `main.php` 的"最近访问记录（最新50条）"表格中，**在 ID 列后面添加 Canvas 指纹ID 列**

---

## 🔍 检查数据库表结构

### 1. 确认表名
```sql
SHOW TABLES LIKE '%stats_visits%';
```
通常是：`zbp_post_stats_visits`

### 2. 检查是否已有 canvas_fingerprint 字段
```sql
DESC zbp_post_stats_visits;
```

### 3. 如果没有，添加字段
```sql
ALTER TABLE `zbp_post_stats_visits` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Canvas指纹ID' AFTER `visitor_id`,
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);
```

---

## 📝 修改 main.php

### 位置：`zb_users/plugin/AdvancedStats/main.php`

### 步骤1：添加表头（约第776行）

**原代码：**
```php
<tr style="background: #f8f9fa;">
    <th width="60" style="padding: 12px;">ID</th>
    <th style="padding: 12px;">时间</th>
    <th style="padding: 12px;">IP地址</th>
    ...
```

**修改为：**
```php
<tr style="background: #f8f9fa;">
    <th width="60" style="padding: 12px;">ID</th>
    <th width="120" style="padding: 12px;">Canvas指纹</th>
    <th style="padding: 12px;">时间</th>
    <th style="padding: 12px;">IP地址</th>
    ...
```

### 步骤2：添加数据列（约第842行）

**在 `<td align="center">...</td>` （显示 ID）后面，添加：**

```php
<td align="center" style="padding: 10px; color: #999;">
    <?php echo $visit['id']; ?>
</td>
<!-- 👇 新增：Canvas 指纹列 -->
<td style="padding: 10px; font-family: 'Courier New', monospace; font-size: 11px;">
    <?php if (!empty($visit['canvas_fingerprint'])): ?>
        <span style="color: #667eea; background: #f0f4ff; padding: 4px 8px; border-radius: 4px; display: inline-block;">
            🔒 <?php echo htmlspecialchars(substr($visit['canvas_fingerprint'], 0, 8)); ?>
        </span>
    <?php else: ?>
        <span style="color: #999;">-</span>
    <?php endif; ?>
</td>
<!-- 👆 新增结束 -->
<td style="padding: 10px; font-family: 'Courier New', monospace; font-size: 12px;">
    <?php echo $visit['date'] . '<br>' . $visit['time']; ?>
</td>
```

---

## 🎨 效果预览

```
┌────┬──────────────┬────────────────┬──────────┬────────┐
│ ID │ Canvas指纹   │ 时间           │ IP地址   │ ...    │
├────┼──────────────┼────────────────┼──────────┼────────┤
│ 1  │ 🔒 7a3f2e1d │ 2025-10-22     │ 1.2.3.4  │ ...    │
│    │              │ 10:30:15       │          │        │
├────┼──────────────┼────────────────┼──────────┼────────┤
│ 2  │ -            │ 2025-10-22     │ 5.6.7.8  │ ...    │
│    │              │ 10:25:08       │          │        │
└────┴──────────────┴────────────────┴──────────┴────────┘
```

---

## ✅ 验证

1. 上传修改后的 `main.php`
2. 访问 `/zb_users/plugin/AdvancedStats/main.php`
3. 查看"最近访问记录"表格是否有 Canvas 指纹列

---

## 📊 完整修改示例

### 完整的表头和数据行代码

```php
<!-- 表头 -->
<table class="tableBorder" style="width: 100%;">
    <tr style="background: #f8f9fa;">
        <th width="60" style="padding: 12px;">ID</th>
        <th width="120" style="padding: 12px;">Canvas指纹</th>
        <th style="padding: 12px;">时间</th>
        <th style="padding: 12px;">IP地址</th>
        <th style="padding: 12px;">访问页面</th>
        <th style="padding: 12px;">来源类型</th>
        <th style="padding: 12px;">设备类型</th>
        <th style="padding: 12px;">浏览器</th>
        <th style="padding: 12px;">User-Agent</th>
    </tr>
    
    <!-- 数据行 -->
    <?php if (!empty($recentVisits)): ?>
    <?php foreach ($recentVisits as $visit): ?>
    <tr style="border-bottom: 1px solid #e9ecef;">
        <!-- ID 列 -->
        <td align="center" style="padding: 10px; color: #999;">
            <?php echo $visit['id']; ?>
        </td>
        
        <!-- Canvas 指纹列 -->
        <td style="padding: 10px; font-family: 'Courier New', monospace; font-size: 11px;">
            <?php if (!empty($visit['canvas_fingerprint'])): ?>
                <span style="color: #667eea; background: #f0f4ff; padding: 4px 8px; border-radius: 4px; display: inline-block;" title="完整指纹: <?php echo htmlspecialchars($visit['canvas_fingerprint']); ?>">
                    🔒 <?php echo htmlspecialchars(substr($visit['canvas_fingerprint'], 0, 8)); ?>
                </span>
            <?php else: ?>
                <span style="color: #999;">-</span>
            <?php endif; ?>
        </td>
        
        <!-- 时间列 -->
        <td style="padding: 10px; font-family: 'Courier New', monospace; font-size: 12px;">
            <?php echo $visit['date'] . '<br>' . $visit['time']; ?>
        </td>
        
        <!-- ... 其他列保持不变 ... -->
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
</table>
```

---

## 🔧 注意事项

1. **备份文件**：修改前先备份 `main.php`
2. **表名**：根据实际表名修改 SQL（可能是 `zbp_post_stats_visits` 或其他）
3. **字段长度**：Canvas 指纹通常是 8-16 位十六进制字符串
4. **显示格式**：只显示前 8 位，悬停时显示完整指纹（`title` 属性）

---

## 📌 相关文件

- 插件目录：`/www/wwwroot/www.dcyzq.cn/zb_users/plugin/AdvancedStats/`
- 主管理页：`main.php`（约1400行，第774-900行是访问记录表格部分）
- 数据库表：`zbp_post_stats_visits`

