<?php
/**
 * 查找损坏的插件XML文件
 */

header('Content-Type: text/html; charset=utf-8');

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>查找损坏的插件</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #0188fb; border-bottom: 3px solid #0188fb; padding-bottom: 10px; }
        .result { background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #0188fb; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0166c7; }
    </style>
</head>
<body>
    <h1>🔍 查找损坏的插件XML文件</h1>
';

$pluginDir = $zbp->usersdir . 'plugin/';
$brokenPlugins = array();
$validPlugins = array();

// 扫描所有插件
$plugins = glob($pluginDir . '*/plugin.xml');

echo '<div class="result">';
echo '<h2>正在检查 ' . count($plugins) . ' 个插件...</h2>';

foreach ($plugins as $xmlFile) {
    $pluginName = basename(dirname($xmlFile));
    
    // 读取XML文件
    $xmlContent = @file_get_contents($xmlFile);
    
    if ($xmlContent === false) {
        $brokenPlugins[] = array(
            'name' => $pluginName,
            'file' => $xmlFile,
            'error' => '无法读取文件'
        );
        continue;
    }
    
    // 尝试解析XML
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($xmlContent);
    
    if ($xml === false) {
        $errors = libxml_get_errors();
        $errorMsg = array();
        foreach ($errors as $error) {
            $errorMsg[] = "行 {$error->line}: {$error->message}";
        }
        libxml_clear_errors();
        
        $brokenPlugins[] = array(
            'name' => $pluginName,
            'file' => $xmlFile,
            'error' => implode('<br>', $errorMsg)
        );
    } else {
        $validPlugins[] = array(
            'name' => $pluginName,
            'file' => $xmlFile
        );
    }
}

echo '</div>';

// 显示损坏的插件
if (count($brokenPlugins) > 0) {
    echo '<div class="result">';
    echo '<h2>❌ 发现 ' . count($brokenPlugins) . ' 个损坏的插件</h2>';
    echo '<table>';
    echo '<tr><th>插件名称</th><th>XML文件</th><th>错误信息</th><th>操作</th></tr>';
    
    foreach ($brokenPlugins as $plugin) {
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($plugin['name']) . '</strong></td>';
        echo '<td><code>' . htmlspecialchars($plugin['file']) . '</code></td>';
        echo '<td class="error">' . $plugin['error'] . '</td>';
        echo '<td><a href="?fix=' . urlencode($plugin['name']) . '" class="btn" onclick="return confirm(\'确定要尝试修复吗？\')">尝试修复</a></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
    
    echo '<div class="warning">';
    echo '<strong>⚠️ 建议操作：</strong><br>';
    echo '1. 备份插件目录：<code>/zb_users/plugin/</code><br>';
    echo '2. 禁用或删除损坏的插件<br>';
    echo '3. 重新安装该插件<br>';
    echo '4. 或者联系插件作者修复XML文件';
    echo '</div>';
} else {
    echo '<div class="success">';
    echo '<h2>✅ 所有插件XML文件正常</h2>';
    echo '<p>检查完成，未发现损坏的插件。</p>';
    echo '</div>';
}

// 显示正常的插件
echo '<div class="result">';
echo '<h2>✅ 正常的插件（' . count($validPlugins) . ' 个）</h2>';
echo '<table>';
echo '<tr><th>插件名称</th><th>XML文件</th></tr>';

foreach ($validPlugins as $plugin) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($plugin['name']) . '</td>';
    echo '<td><code>' . htmlspecialchars($plugin['file']) . '</code></td>';
    echo '</tr>';
}

echo '</table>';
echo '</div>';

// 处理修复请求
if (isset($_GET['fix'])) {
    $fixPlugin = $_GET['fix'];
    $xmlFile = $pluginDir . $fixPlugin . '/plugin.xml';
    
    if (file_exists($xmlFile)) {
        echo '<div class="result">';
        echo '<h2>🔧 尝试修复：' . htmlspecialchars($fixPlugin) . '</h2>';
        
        $content = file_get_contents($xmlFile);
        
        // 常见修复
        $fixed = false;
        
        // 1. 检查sidebars标签是否正确闭合
        if (strpos($content, '<sidebars>') !== false && strpos($content, '</sidebars>') === false) {
            $content = str_replace('</plugin>', '</sidebars></plugin>', $content);
            $fixed = true;
            echo '<p>✅ 修复：添加缺失的 &lt;/sidebars&gt; 标签</p>';
        }
        
        // 2. 检查其他未闭合标签
        preg_match_all('/<(\w+)[^>]*>/', $content, $openTags);
        preg_match_all('/<\/(\w+)>/', $content, $closeTags);
        
        if ($fixed) {
            file_put_contents($xmlFile, $content);
            echo '<div class="success">修复成功！请刷新页面重新检查。</div>';
        } else {
            echo '<div class="warning">无法自动修复，请手动编辑XML文件。</div>';
        }
        
        echo '</div>';
    }
}

echo '<p style="text-align: center; margin-top: 40px; color: #6c757d;">
    <a href="?" class="btn">🔄 重新检查</a>
    <a href="../../../zb_system/cmd.php?act=PluginMng" class="btn">返回插件管理</a>
</p>';

echo '</body></html>';
?>

