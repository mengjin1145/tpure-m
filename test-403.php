<?php
/**
 * 403 问题排查工具
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$themeDir = __DIR__ . '/';

echo "<h2>403 问题排查工具</h2>";

// 检查文件
$files = [
    'include.php' => '当前版本（新版246行）',
    'include.php.backup' => '备份版本（原版1897行）',
    'include.php.tmp' => '临时保存的新版本',
    'include-minimal.php' => '最小化测试版本',
];

echo "<h3>文件列表：</h3><ul>";
foreach ($files as $file => $desc) {
    $path = $themeDir . $file;
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    $status = $exists ? "✅ {$size} 字节" : "❌ 不存在";
    echo "<li><b>{$file}</b>: {$status} - {$desc}</li>";
}
echo "</ul>";

// 操作
if (isset($_GET['action'])) {
    echo "<hr><h3>执行操作：</h3>";
    
    switch ($_GET['action']) {
        case 'use_minimal':
            // 使用最小化版本
            if (file_exists($themeDir . 'include-minimal.php')) {
                // 备份当前版本
                if (!file_exists($themeDir . 'include.php.tmp')) {
                    copy($themeDir . 'include.php', $themeDir . 'include.php.tmp');
                }
                
                // 复制最小化版本
                if (copy($themeDir . 'include-minimal.php', $themeDir . 'include.php')) {
                    echo "✅ 已切换到最小化版本<br>";
                    echo "<p style='background:#ffffcc;padding:15px;border-left:4px solid #ff9800;'>";
                    echo "<b>测试说明：</b><br>";
                    echo "1. 现在访问 <a href='http://www.dcyzq.com/' target='_blank'>http://www.dcyzq.com/</a><br>";
                    echo "2. 如果能正常访问，说明问题在钩子函数中<br>";
                    echo "3. 如果仍然 403，说明问题在核心模块加载中<br>";
                    echo "</p>";
                } else {
                    echo "❌ 切换失败<br>";
                }
            } else {
                echo "❌ include-minimal.php 不存在<br>";
            }
            break;
            
        case 'use_new':
            // 恢复新版本
            if (file_exists($themeDir . 'include.php.tmp')) {
                if (copy($themeDir . 'include.php.tmp', $themeDir . 'include.php')) {
                    echo "✅ 已恢复新版本<br>";
                } else {
                    echo "❌ 恢复失败<br>";
                }
            } else {
                echo "❌ 临时文件不存在<br>";
            }
            break;
            
        case 'use_backup':
            // 使用原版
            if (file_exists($themeDir . 'include.php.backup')) {
                if (!file_exists($themeDir . 'include.php.tmp')) {
                    copy($themeDir . 'include.php', $themeDir . 'include.php.tmp');
                }
                
                if (copy($themeDir . 'include.php.backup', $themeDir . 'include.php')) {
                    echo "✅ 已切换到原版<br>";
                } else {
                    echo "❌ 切换失败<br>";
                }
            } else {
                echo "❌ 备份文件不存在<br>";
            }
            break;
    }
    
    echo "<br><a href='test-403.php'>← 返回</a>";
    
} else {
    // 显示操作选项
    echo "<hr><h3>测试步骤：</h3>";
    echo "<div style='background:#f5f5f5;padding:20px;'>";
    
    echo "<p><b>步骤 1: 测试最小化版本</b></p>";
    echo "<a href='?action=use_minimal' style='display:inline-block;padding:10px 20px;background:#2196f3;color:white;text-decoration:none;border-radius:5px;margin-bottom:20px;'>🔍 使用最小化版本</a>";
    echo "<p style='color:#666;font-size:14px;margin-left:20px;'>最小化版本只加载核心模块，不注册任何钩子</p>";
    
    echo "<hr style='margin:20px 0;'>";
    
    echo "<p><b>步骤 2: 根据结果判断</b></p>";
    echo "<ul style='font-size:14px;color:#666;'>";
    echo "<li>如果最小化版本能访问 → 问题在钩子函数中</li>";
    echo "<li>如果最小化版本仍 403 → 问题在核心模块中</li>";
    echo "</ul>";
    
    echo "<hr style='margin:20px 0;'>";
    
    echo "<p><b>其他操作：</b></p>";
    echo "<a href='?action=use_new' style='display:inline-block;padding:8px 16px;background:#4caf50;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>✅ 恢复新版本</a>";
    echo "<a href='?action=use_backup' style='display:inline-block;padding:8px 16px;background:#ff9800;color:white;text-decoration:none;border-radius:5px;'>⚠️ 使用原版</a>";
    
    echo "</div>";
}

