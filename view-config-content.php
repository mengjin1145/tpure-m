<?php
/**
 * 查看和修复配置文件内容
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
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; max-height: 500px; }
.btn { padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.danger { background: #dc3545; }
</style>';

echo '<h1>🔍 配置文件完整内容查看</h1>';

$configFile = $zbp->usersdir . 'c_option.php';
$backupFile = $zbp->usersdir . 'c_option.php.backup.' . date('YmdHis');

// 显示文件完整内容
echo '<div class="box">';
echo '<h3>配置文件完整内容</h3>';
echo '<p>文件路径: <code>' . $configFile . '</code></p>';
echo '<p>文件大小: ' . filesize($configFile) . ' 字节</p>';

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    echo '<h4>完整内容：</h4>';
    echo '<pre>' . htmlspecialchars($content) . '</pre>';
} else {
    echo '<p class="error">配置文件不存在！</p>';
}
echo '</div>';

// 分析配置结构
echo '<div class="box">';
echo '<h3>配置结构分析</h3>';

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    
    // 检查是否是有效的 PHP 数组
    echo '<ul>';
    echo '<li>文件开头: ' . (substr($content, 0, 5) === '<?php' ? '<span class="success">✓ 正常</span>' : '<span class="error">✗ 异常</span>') . '</li>';
    echo '<li>包含 return: ' . (strpos($content, 'return') !== false ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</li>';
    echo '<li>包含 array: ' . (strpos($content, 'array') !== false ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</li>';
    echo '<li>包含 tpure: ' . (strpos($content, 'tpure') !== false ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</li>';
    echo '</ul>';
    
    // 尝试加载配置
    echo '<h4>尝试解析配置：</h4>';
    try {
        $loadedConfig = @include($configFile);
        if (is_array($loadedConfig)) {
            echo '<p class="success">✓ 配置文件格式正确</p>';
            echo '<p>配置项数量: ' . count($loadedConfig) . '</p>';
            echo '<p>包含的配置组: ' . implode(', ', array_keys($loadedConfig)) . '</p>';
        } else {
            echo '<p class="error">✗ 配置文件格式错误（不是数组）</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ 解析失败: ' . $e->getMessage() . '</p>';
    }
}
echo '</div>';

// 备份和修复选项
echo '<div class="box">';
echo '<h3>修复选项</h3>';

// 备份配置文件
if (isset($_POST['backup_config'])) {
    if (file_exists($configFile)) {
        $backupResult = copy($configFile, $backupFile);
        if ($backupResult) {
            echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;">';
            echo '<p class="success">✓ 配置文件已备份到: ' . $backupFile . '</p>';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;">';
            echo '<p class="error">✗ 备份失败</p>';
            echo '</div>';
        }
    }
}

// 重建配置文件
if (isset($_POST['rebuild_config'])) {
    echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">';
    echo '<h4>正在重建配置文件...</h4>';
    
    // 1. 先备份当前文件
    if (file_exists($configFile)) {
        copy($configFile, $backupFile);
        echo '<p>✓ 已备份当前文件到: ' . basename($backupFile) . '</p>';
    }
    
    // 2. 确保所有缓存配置都存在
    if (!$zbp->Config('tpure')->HasKey('CacheFullPageOn')) {
        $zbp->Config('tpure')->CacheFullPageOn = 'ON';
    }
    if (!$zbp->Config('tpure')->HasKey('CacheHotContentOn')) {
        $zbp->Config('tpure')->CacheHotContentOn = 'ON';
    }
    if (!$zbp->Config('tpure')->HasKey('CacheBrowserOn')) {
        $zbp->Config('tpure')->CacheBrowserOn = 'ON';
    }
    if (!$zbp->Config('tpure')->HasKey('CacheTemplateOn')) {
        $zbp->Config('tpure')->CacheTemplateOn = 'ON';
    }
    
    echo '<p>✓ 已设置缓存配置项</p>';
    
    // 3. 强制保存
    $saveResult = $zbp->SaveConfig('tpure');
    
    if ($saveResult) {
        echo '<p class="success">✓ 配置文件重建成功！</p>';
        
        // 验证
        clearstatcache();
        $newSize = filesize($configFile);
        $newContent = file_get_contents($configFile);
        
        echo '<p>新文件大小: ' . $newSize . ' 字节</p>';
        echo '<p>包含 tpure: ' . (strpos($newContent, 'tpure') !== false ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</p>';
        
        $cacheConfigs = ['CacheFullPageOn', 'CacheHotContentOn', 'CacheBrowserOn', 'CacheTemplateOn'];
        $foundCount = 0;
        foreach ($cacheConfigs as $key) {
            if (strpos($newContent, "'$key'") !== false || strpos($newContent, "\"$key\"") !== false) {
                $foundCount++;
            }
        }
        echo '<p>找到缓存配置项: ' . $foundCount . ' / ' . count($cacheConfigs) . '</p>';
        
        if ($foundCount == count($cacheConfigs)) {
            echo '<p class="success"><strong>✓ 配置文件修复完成！所有缓存配置已正确写入！</strong></p>';
        } else {
            echo '<p class="warning">⚠ 部分配置项未写入，可能需要手动检查</p>';
        }
        
        echo '<p style="margin-top: 15px;"><a href="cache-diagnostic.php" class="btn">返回诊断页面验证</a></p>';
    } else {
        echo '<p class="error">✗ 配置保存失败</p>';
        echo '<p>可能的原因：</p>';
        echo '<ul>';
        echo '<li>文件权限问题</li>';
        echo '<li>磁盘空间不足</li>';
        echo '<li>服务器配置限制</li>';
        echo '</ul>';
    }
    
    echo '</div>';
}

echo '<form method="post" style="margin: 15px 0;">';
echo '<h4>步骤 1: 备份当前配置文件</h4>';
echo '<p><button type="submit" name="backup_config" class="btn">备份配置文件</button></p>';
echo '</form>';

echo '<form method="post" style="margin: 15px 0;" onsubmit="return confirm(\'确定要重建配置文件吗？已自动创建备份。\');">';
echo '<h4>步骤 2: 重建配置文件（推荐）</h4>';
echo '<p class="warning">⚠️ 此操作会重新生成配置文件，会自动创建备份</p>';
echo '<p><button type="submit" name="rebuild_config" class="btn">重建配置文件</button></p>';
echo '</form>';

echo '</div>';

// 手动修复指南
echo '<div class="box" style="background: #fff3cd;">';
echo '<h3>💡 如果自动修复失败</h3>';
echo '<p><strong>手动修复步骤：</strong></p>';
echo '<ol>';
echo '<li>通过 FTP 下载当前的 <code>c_option.php</code> 文件</li>';
echo '<li>在主题配置页面（main.php?act=config）保存一次所有设置</li>';
echo '<li>再次下载 <code>c_option.php</code> 对比变化</li>';
echo '<li>如果文件仍然很小（<2KB），可能是 SaveConfig 函数有问题</li>';
echo '<li>检查服务器错误日志：<code>/www/server/php/xx/var/log/php-fpm.log</code></li>';
echo '</ol>';
echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="check-config-file.php">← 返回配置检查</a> | ';
echo '<a href="cache-diagnostic.php">查看诊断报告</a>';
echo '</div>';
?>


