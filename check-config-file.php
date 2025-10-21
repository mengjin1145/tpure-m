<?php
/**
 * 配置文件检查工具
 */

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

if (!$zbp->CheckRights('root')) {
    die('请先登录后台');
}

echo '<meta charset="utf-8">';
echo '<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.success { color: #28a745; }
.error { color: #dc3545; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; }
</style>';

echo '<h1>📄 配置文件检查</h1>';

// 检查配置文件
$configFile = $zbp->usersdir . 'c_option.php';

echo '<div class="box">';
echo '<h3>配置文件信息</h3>';
echo '<table>';
echo '<tr><th>项目</th><th>值</th></tr>';
echo '<tr><td>文件路径</td><td>' . $configFile . '</td></tr>';
echo '<tr><td>文件存在</td><td>' . (file_exists($configFile) ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</td></tr>';

if (file_exists($configFile)) {
    echo '<tr><td>文件大小</td><td>' . filesize($configFile) . ' 字节</td></tr>';
    echo '<tr><td>可读</td><td>' . (is_readable($configFile) ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</td></tr>';
    echo '<tr><td>可写</td><td>' . (is_writable($configFile) ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</td></tr>';
    echo '<tr><td>最后修改</td><td>' . date('Y-m-d H:i:s', filemtime($configFile)) . '</td></tr>';
}
echo '</table>';
echo '</div>';

// 检查内存中的配置
echo '<div class="box">';
echo '<h3>内存中的缓存配置</h3>';
echo '<table>';
echo '<tr><th>配置项</th><th>值</th></tr>';
echo '<tr><td>CacheFullPageOn</td><td>' . ($zbp->Config('tpure')->CacheFullPageOn ?: '未设置') . '</td></tr>';
echo '<tr><td>CacheHotContentOn</td><td>' . ($zbp->Config('tpure')->CacheHotContentOn ?: '未设置') . '</td></tr>';
echo '<tr><td>CacheBrowserOn</td><td>' . ($zbp->Config('tpure')->CacheBrowserOn ?: '未设置') . '</td></tr>';
echo '<tr><td>CacheTemplateOn</td><td>' . ($zbp->Config('tpure')->CacheTemplateOn ?: '未设置') . '</td></tr>';
echo '</table>';
echo '</div>';

// 检查配置文件内容
echo '<div class="box">';
echo '<h3>配置文件中的 tpure 配置</h3>';

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    
    // 搜索缓存相关配置
    $cacheConfigs = [
        'CacheFullPageOn' => '全页面缓存',
        'CacheHotContentOn' => '热门内容缓存',
        'CacheBrowserOn' => '浏览器缓存',
        'CacheTemplateOn' => '模板缓存'
    ];
    
    echo '<table>';
    echo '<tr><th>配置项</th><th>文件中是否存在</th></tr>';
    
    foreach ($cacheConfigs as $key => $name) {
        $found = (strpos($content, "'$key'") !== false || strpos($content, "\"$key\"") !== false);
        echo '<tr>';
        echo '<td>' . $name . ' (' . $key . ')</td>';
        echo '<td>' . ($found ? '<span class="success">✓ 存在</span>' : '<span class="error">✗ 不存在</span>') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // 显示 tpure 配置片段
    echo '<hr>';
    echo '<h4>配置文件中的 tpure 部分（前 3000 字符）：</h4>';
    
    // 查找 'tpure' 部分
    if (preg_match('/\'tpure\'\s*=>\s*array\s*\((.*?)\),\s*\'zbp/s', $content, $matches)) {
        $tpureConfig = $matches[1];
        $preview = substr($tpureConfig, 0, 3000);
        echo '<pre>' . htmlspecialchars($preview) . ($tpureConfig ? "\n... (配置较长，仅显示前3000字符)" : "") . '</pre>';
    } else {
        echo '<p class="error">未找到 tpure 配置段</p>';
    }
}
echo '</div>';

// 强制保存测试
echo '<div class="box">';
echo '<h3>强制保存测试</h3>';

if (isset($_POST['force_save'])) {
    echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">';
    
    // 确保配置存在
    if (!isset($zbp->Config('tpure')->CacheFullPageOn)) {
        $zbp->Config('tpure')->CacheFullPageOn = 'ON';
    }
    if (!isset($zbp->Config('tpure')->CacheHotContentOn)) {
        $zbp->Config('tpure')->CacheHotContentOn = 'OFF';
    }
    if (!isset($zbp->Config('tpure')->CacheBrowserOn)) {
        $zbp->Config('tpure')->CacheBrowserOn = 'OFF';
    }
    if (!isset($zbp->Config('tpure')->CacheTemplateOn)) {
        $zbp->Config('tpure')->CacheTemplateOn = 'ON';
    }
    
    echo '<p>正在保存配置...</p>';
    
    $result = $zbp->SaveConfig('tpure');
    
    if ($result) {
        echo '<p class="success"><strong>✓ 配置保存成功！</strong></p>';
        
        // 重新读取配置文件验证
        clearstatcache();
        $content = file_get_contents($configFile);
        
        $found = 0;
        foreach ($cacheConfigs as $key => $name) {
            if (strpos($content, "'$key'") !== false || strpos($content, "\"$key\"") !== false) {
                $found++;
            }
        }
        
        echo '<p>文件中找到 ' . $found . ' / ' . count($cacheConfigs) . ' 个缓存配置项</p>';
        echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">刷新页面查看最新状态</a></p>';
    } else {
        echo '<p class="error"><strong>✗ 配置保存失败</strong></p>';
    }
    
    echo '</div>';
}

echo '<form method="post">';
echo '<p>如果配置未正确写入文件，可以点击下方按钮强制保存：</p>';
echo '<p><button type="submit" name="force_save" style="padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">强制保存配置到文件</button></p>';
echo '</form>';

echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="cache-diagnostic.php">← 返回诊断页面</a> | ';
echo '<a href="main.php?act=config">前往主题配置</a>';
echo '</div>';
?>

