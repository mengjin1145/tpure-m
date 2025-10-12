<?php
/**
 * 快速切换 include.php 版本（用于测试）
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$themeDir = __DIR__ . '/';
$includeNew = $themeDir . 'include.php';
$includeBackup = $themeDir . 'include.php.backup';
$includeTmp = $themeDir . 'include.php.tmp';

echo "<h2>Include.php 版本切换</h2>";

// 检查文件
echo "<h3>当前文件状态：</h3>";
echo "include.php: " . (file_exists($includeNew) ? "✅ 存在 (" . filesize($includeNew) . " 字节)" : "❌ 不存在") . "<br>";
echo "include.php.backup: " . (file_exists($includeBackup) ? "✅ 存在 (" . filesize($includeBackup) . " 字节)" : "❌ 不存在") . "<br>";

// 处理切换操作
if (isset($_GET['action'])) {
    echo "<hr><h3>执行操作：</h3>";
    
    if ($_GET['action'] === 'use_backup') {
        // 使用备份版本
        if (file_exists($includeBackup)) {
            // 先备份当前版本
            if (copy($includeNew, $includeTmp)) {
                echo "✅ 当前版本已保存为 include.php.tmp<br>";
            }
            
            // 复制备份版本为当前版本
            if (copy($includeBackup, $includeNew)) {
                echo "✅ 已切换到备份版本（原版1897行）<br>";
                echo "<br><b>现在测试网站：</b> <a href='http://www.dcyzq.com/' target='_blank'>http://www.dcyzq.com/</a><br>";
            } else {
                echo "❌ 切换失败！<br>";
            }
        } else {
            echo "❌ 备份文件不存在！<br>";
        }
    } 
    elseif ($_GET['action'] === 'use_new') {
        // 使用新版本
        if (file_exists($includeTmp)) {
            if (copy($includeTmp, $includeNew)) {
                echo "✅ 已切换回新版本（246行）<br>";
                echo "<br><b>现在测试网站：</b> <a href='http://www.dcyzq.com/' target='_blank'>http://www.dcyzq.com/</a><br>";
            } else {
                echo "❌ 切换失败！<br>";
            }
        } else {
            echo "❌ 临时文件不存在！<br>";
        }
    }
    
    echo "<br><a href='switch-include.php'>← 返回</a>";
    
} else {
    // 显示操作按钮
    echo "<hr><h3>操作选项：</h3>";
    echo "<p><a href='?action=use_backup' style='display:inline-block;padding:10px 20px;background:#ff9800;color:white;text-decoration:none;border-radius:5px;'>⚠️ 临时使用原版（1897行）</a></p>";
    echo "<p style='color:#666;margin-left:20px;'>用途：测试原版是否能正常访问</p>";
    
    if (file_exists($includeTmp)) {
        echo "<p><a href='?action=use_new' style='display:inline-block;padding:10px 20px;background:#4caf50;color:white;text-decoration:none;border-radius:5px;'>✅ 恢复新版本（246行）</a></p>";
        echo "<p style='color:#666;margin-left:20px;'>用途：切换回优化后的版本</p>";
    }
}

echo "<hr>";
echo "<h3>说明：</h3>";
echo "<ol>";
echo "<li>点击「临时使用原版」后，访问网站首页测试</li>";
echo "<li>如果原版能正常访问，说明是新版本的问题</li>";
echo "<li>如果原版也不能访问，说明是服务器或其他配置问题</li>";
echo "<li>测试完成后，点击「恢复新版本」</li>";
echo "</ol>";

