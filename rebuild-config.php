<?php
/**
 * 配置文件重建工具
 * 修复 tpure 配置段缺失的问题
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
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.btn-danger { background: #dc3545; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; max-height: 400px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; }
</style>';

echo '<h1>🔧 配置文件重建工具</h1>';

$configFile = $zbp->usersdir . 'c_option.php';
$backupFile = $zbp->usersdir . 'c_option.php.backup.' . date('YmdHis');

// 步骤 1: 检查当前状态
echo '<div class="box">';
echo '<h3>步骤 1: 当前配置文件状态</h3>';
echo '<table>';
echo '<tr><td>文件路径</td><td>' . $configFile . '</td></tr>';
echo '<tr><td>文件大小</td><td>' . (file_exists($configFile) ? filesize($configFile) . ' 字节' : '不存在') . '</td></tr>';
echo '<tr><td>最后修改</td><td>' . (file_exists($configFile) ? date('Y-m-d H:i:s', filemtime($configFile)) : 'N/A') . '</td></tr>';
echo '</table>';

// 读取并显示当前配置文件内容
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    echo '<h4>当前配置文件内容预览：</h4>';
    echo '<pre>' . htmlspecialchars(substr($content, 0, 2000)) . (strlen($content) > 2000 ? "\n... (文件较长，仅显示前2000字符)" : "") . '</pre>';
    
    // 检查是否有 tpure 配置
    $hasTpure = (strpos($content, "'tpure'") !== false || strpos($content, '"tpure"') !== false);
    echo '<p>是否包含 tpure 配置段: ' . ($hasTpure ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</p>';
}
echo '</div>';

// 步骤 2: 显示内存中的配置
echo '<div class="box">';
echo '<h3>步骤 2: 内存中的 tpure 配置</h3>';

$tpureConfig = $zbp->Config('tpure');
$configArray = [];

// 获取所有配置项
if (is_object($tpureConfig)) {
    $reflection = new ReflectionObject($tpureConfig);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    
    echo '<p>找到 ' . count($properties) . ' 个配置项</p>';
    echo '<details><summary>点击查看所有配置项</summary>';
    echo '<table>';
    echo '<tr><th>配置项</th><th>值</th></tr>';
    
    foreach ($properties as $prop) {
        $name = $prop->getName();
        $value = $prop->getValue($tpureConfig);
        $configArray[$name] = $value;
        
        // 只显示前50个
        if (count($configArray) <= 50) {
            echo '<tr><td>' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars(substr(print_r($value, true), 0, 100)) . '</td></tr>';
        }
    }
    
    echo '</table>';
    echo '</details>';
}
echo '</div>';

// 步骤 3: 修复操作
echo '<div class="box">';
echo '<h3>步骤 3: 配置文件修复</h3>';

if (isset($_POST['rebuild_config'])) {
    echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">';
    echo '<h4>修复过程：</h4>';
    
    // 1. 备份原文件
    if (file_exists($configFile)) {
        $backupResult = copy($configFile, $backupFile);
        echo '<p>1. 备份原配置文件: ' . ($backupResult ? '<span class="success">✓ 成功</span> → ' . $backupFile : '<span class="error">✗ 失败</span>') . '</p>';
    }
    
    // 2. 尝试保存配置
    echo '<p>2. 保存 tpure 配置...</p>';
    
    // 确保缓存配置存在
    $zbp->Config('tpure')->CacheFullPageOn = 'ON';
    $zbp->Config('tpure')->CacheHotContentOn = 'ON';
    $zbp->Config('tpure')->CacheBrowserOn = 'ON';
    $zbp->Config('tpure')->CacheTemplateOn = 'ON';
    
    // 执行保存
    $saveResult = $zbp->SaveConfig('tpure');
    
    echo '<p>   保存结果: ' . ($saveResult ? '<span class="success">✓ 成功</span>' : '<span class="error">✗ 失败</span>') . '</p>';
    
    // 3. 验证文件内容
    clearstatcache();
    if (file_exists($configFile)) {
        $newContent = file_get_contents($configFile);
        $newSize = filesize($configFile);
        $hasTpure = (strpos($newContent, "'tpure'") !== false || strpos($newContent, '"tpure"') !== false);
        
        echo '<p>3. 验证修复结果:</p>';
        echo '<ul>';
        echo '<li>新文件大小: ' . $newSize . ' 字节</li>';
        echo '<li>包含 tpure 配置: ' . ($hasTpure ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</li>';
        echo '<li>最后修改: ' . date('Y-m-d H:i:s', filemtime($configFile)) . '</li>';
        echo '</ul>';
        
        if ($hasTpure) {
            echo '<p class="success"><strong>✓ 配置文件修复成功！</strong></p>';
            
            // 检查缓存配置
            $cacheFound = 0;
            $cacheConfigs = ['CacheFullPageOn', 'CacheHotContentOn', 'CacheBrowserOn', 'CacheTemplateOn'];
            foreach ($cacheConfigs as $key) {
                if (strpos($newContent, $key) !== false) {
                    $cacheFound++;
                }
            }
            echo '<p>找到 ' . $cacheFound . ' / 4 个缓存配置项</p>';
            
            echo '<p style="margin-top: 20px;">';
            echo '<a href="cache-diagnostic.php" class="btn">返回诊断页面验证</a>';
            echo '<a href="check-config-file.php" class="btn">重新检查配置文件</a>';
            echo '</p>';
        } else {
            echo '<p class="error"><strong>✗ 修复失败，tpure 配置段仍未写入</strong></p>';
            echo '<p>可能的原因：</p>';
            echo '<ul>';
            echo '<li>文件权限问题</li>';
            echo '<li>SaveConfig 函数异常</li>';
            echo '<li>配置对象未正确初始化</li>';
            echo '</ul>';
        }
    }
    
    echo '</div>';
}

echo '<form method="post" onsubmit="return confirm(\'确认要重建配置文件吗？\\n\\n操作前会自动备份原文件。\');">';
echo '<p><strong>修复说明：</strong></p>';
echo '<ol>';
echo '<li>自动备份当前配置文件</li>';
echo '<li>重新保存 tpure 配置到文件</li>';
echo '<li>验证配置是否正确写入</li>';
echo '</ol>';
echo '<p><button type="submit" name="rebuild_config" class="btn">开始修复配置文件</button></p>';
echo '</form>';

echo '</div>';

// 步骤 4: 手动编辑（高级）
echo '<div class="box" style="background: #fff3cd;">';
echo '<h3>步骤 4: 高级选项 - 查看 SaveConfig 详细信息</h3>';

if (isset($_POST['debug_save'])) {
    echo '<div style="background: white; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">';
    echo '<h4>SaveConfig 调试信息：</h4>';
    
    // 打开错误显示
    $oldErrorLevel = error_reporting(E_ALL);
    $oldDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', 1);
    
    ob_start();
    
    try {
        echo '<pre>';
        echo "调用 SaveConfig 前的状态:\n";
        echo "- 配置对象类型: " . get_class($zbp->Config('tpure')) . "\n";
        echo "- 配置文件路径: " . $configFile . "\n";
        echo "- 文件可写: " . (is_writable($configFile) ? '是' : '否') . "\n\n";
        
        echo "执行 SaveConfig('tpure')...\n";
        $result = $zbp->SaveConfig('tpure');
        echo "返回值: " . ($result ? 'true' : 'false') . "\n";
        
        clearstatcache();
        echo "\n保存后的文件状态:\n";
        echo "- 文件大小: " . filesize($configFile) . " 字节\n";
        echo "- 最后修改: " . date('Y-m-d H:i:s', filemtime($configFile)) . "\n";
        
        $content = file_get_contents($configFile);
        echo "- 包含 'tpure': " . (strpos($content, 'tpure') !== false ? '是' : '否') . "\n";
        
        echo '</pre>';
    } catch (Exception $e) {
        echo '<p class="error">错误: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    
    $output = ob_get_clean();
    echo $output;
    
    // 恢复错误设置
    error_reporting($oldErrorLevel);
    ini_set('display_errors', $oldDisplayErrors);
    
    echo '</div>';
}

echo '<form method="post">';
echo '<p>如果上述修复失败，可以查看 SaveConfig 的详细执行过程：</p>';
echo '<p><button type="submit" name="debug_save" class="btn" style="background: #ffc107; color: #000;">调试 SaveConfig 过程</button></p>';
echo '</form>';

echo '</div>';

// 显示备份文件列表
echo '<div class="box">';
echo '<h3>配置文件备份列表</h3>';

$backupFiles = glob($zbp->usersdir . 'c_option.php.backup.*');
if ($backupFiles) {
    echo '<table>';
    echo '<tr><th>备份文件</th><th>大小</th><th>创建时间</th></tr>';
    
    rsort($backupFiles); // 最新的在前面
    foreach (array_slice($backupFiles, 0, 10) as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $time = filemtime($file);
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($filename) . '</td>';
        echo '<td>' . $size . ' 字节</td>';
        echo '<td>' . date('Y-m-d H:i:s', $time) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else {
    echo '<p>暂无备份文件</p>';
}
echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="cache-diagnostic.php">← 返回诊断页面</a> | ';
echo '<a href="check-config-file.php">检查配置文件</a>';
echo '</div>';
?>

