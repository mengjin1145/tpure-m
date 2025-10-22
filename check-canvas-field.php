<?php
/**
 * AdvancedStats 插件 - 检查 Canvas 指纹字段
 * 
 * 功能：
 * 1. 检查数据库表是否存在
 * 2. 检查是否已有 canvas_fingerprint 字段
 * 3. 如果有字段，显示最新的指纹数据
 * 4. 提供一键添加字段的 SQL
 */

require '../../../../zb_system/function/c_system_base.php';
$zbp->Load();

$action = 'root';
if (!$zbp->CheckRights($action)) {
    die('❌ 无权限访问');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>检查 Canvas 指纹字段</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
        h2 { color: #2196F3; margin-top: 30px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; color: #0c5460; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #0b7dda; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 AdvancedStats - Canvas 指纹字段检查</h1>

    <?php
    // 1. 检查表是否存在
    $tableName = $zbp->table['Post'] . '_stats_visits';
    echo "<h2>📋 步骤1：检查数据表</h2>";
    
    $checkTableSql = "SHOW TABLES LIKE '{$tableName}'";
    $tableResult = $zbp->db->Query($checkTableSql);
    
    if (empty($tableResult)) {
        echo '<div class="status error">';
        echo "❌ 数据表不存在：<code>{$tableName}</code><br>";
        echo "请先安装或启用 AdvancedStats 插件！";
        echo '</div>';
        exit;
    }
    
    echo '<div class="status success">';
    echo "✅ 数据表存在：<code>{$tableName}</code>";
    echo '</div>';
    
    // 2. 查看表结构
    echo "<h2>📊 步骤2：检查表结构</h2>";
    
    $descSql = "DESC `{$tableName}`";
    $columns = $zbp->db->Query($descSql);
    
    echo '<table>';
    echo '<tr><th>字段名</th><th>类型</th><th>允许NULL</th><th>键</th><th>默认值</th></tr>';
    
    $hasCanvasField = false;
    foreach ($columns as $col) {
        $isCanvasField = (strpos($col['Field'], 'canvas') !== false || strpos($col['Field'], 'fingerprint') !== false);
        if ($isCanvasField) {
            $hasCanvasField = true;
            echo '<tr style="background: #fff3cd; font-weight: bold;">';
        } else {
            echo '<tr>';
        }
        echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
        echo '<td><code>' . htmlspecialchars($col['Type']) . '</code></td>';
        echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Default']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // 3. Canvas 字段状态
    echo "<h2>🎯 步骤3：Canvas 指纹字段状态</h2>";
    
    if ($hasCanvasField) {
        echo '<div class="status success">';
        echo "✅ 已找到 Canvas/Fingerprint 相关字段！";
        echo '</div>';
        
        // 查询最新的指纹数据
        echo "<h2>📈 步骤4：最新的指纹数据</h2>";
        
        $dataSql = "SELECT id, visitor_id, ip, date, time, canvas_fingerprint 
                    FROM `{$tableName}` 
                    WHERE canvas_fingerprint IS NOT NULL AND canvas_fingerprint != ''
                    ORDER BY id DESC LIMIT 10";
        
        try {
            $fingerprintData = $zbp->db->Query($dataSql);
            
            if (!empty($fingerprintData)) {
                echo '<div class="status success">';
                echo "✅ 找到 " . count($fingerprintData) . " 条包含 Canvas 指纹的记录";
                echo '</div>';
                
                echo '<table>';
                echo '<tr><th>ID</th><th>访客ID</th><th>IP</th><th>日期</th><th>时间</th><th>Canvas指纹</th></tr>';
                foreach ($fingerprintData as $row) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td><code>' . htmlspecialchars(substr($row['visitor_id'], 0, 8)) . '...</code></td>';
                    echo '<td>' . htmlspecialchars($row['ip']) . '</td>';
                    echo '<td>' . $row['date'] . '</td>';
                    echo '<td>' . $row['time'] . '</td>';
                    echo '<td style="background: #f0f4ff;"><code>🔒 ' . htmlspecialchars($row['canvas_fingerprint']) . '</code></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="status warning">';
                echo "⚠️ 字段存在，但尚无数据<br>";
                echo "可能原因：<br>";
                echo "1. 前端 JS 尚未配置<br>";
                echo "2. 尚无访客访问<br>";
                echo "3. 指纹收集功能未启用";
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="status error">';
            echo "❌ 查询失败：" . $e->getMessage();
            echo '</div>';
        }
        
    } else {
        echo '<div class="status error">';
        echo "❌ 未找到 canvas_fingerprint 字段！";
        echo '</div>';
        
        // 提供 SQL
        echo "<h2>🔧 解决方案：添加字段</h2>";
        
        echo '<div class="status info">';
        echo "执行以下 SQL 语句添加字段：";
        echo '</div>';
        
        $alterSql = "ALTER TABLE `{$tableName}` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Canvas指纹ID' AFTER `visitor_id`,
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);";
        
        echo '<pre>' . htmlspecialchars($alterSql) . '</pre>';
        
        echo '<div class="status warning">';
        echo "<strong>⚠️ 注意：</strong><br>";
        echo "1. 请在 phpMyAdmin 或 SSH 中执行此 SQL<br>";
        echo "2. 执行前请备份数据库<br>";
        echo "3. 表名：<code>{$tableName}</code><br>";
        echo "4. 字段将添加在 <code>visitor_id</code> 字段后面";
        echo '</div>';
    }
    
    // 5. 总结
    echo "<h2>✅ 下一步操作</h2>";
    
    if ($hasCanvasField) {
        echo '<div class="status success">';
        echo "<strong>字段已就绪！</strong>现在你可以：<br><br>";
        echo "1. 修改 <code>/zb_users/plugin/AdvancedStats/main.php</code><br>";
        echo "2. 在"最近访问记录"表格中添加 Canvas 指纹列<br>";
        echo "3. 参考文档：<code>ADD-CANVAS-FINGERPRINT-TO-MAIN.md</code>";
        echo '</div>';
    } else {
        echo '<div class="status warning">';
        echo "<strong>需要先添加字段！</strong>步骤：<br><br>";
        echo "1. 复制上面的 SQL 语句<br>";
        echo "2. 在 phpMyAdmin 中执行<br>";
        echo "3. 刷新本页面验证";
        echo '</div>';
    }
    ?>

    <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;">
        <strong>相关文件：</strong><br>
        • 插件目录：<code>/zb_users/plugin/AdvancedStats/</code><br>
        • 数据库表：<code><?php echo $tableName; ?></code><br>
        • 管理页面：<code>/zb_users/plugin/AdvancedStats/main.php</code>
    </p>
</div>

</body>
</html>

