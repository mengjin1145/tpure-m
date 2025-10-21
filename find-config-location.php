<?php
/**
 * 配置文件位置查找工具
 * 确认 Z-BlogPHP 和主题配置的实际存储位置
 */

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

// 🔓 允许未登录访问，仅在需要写入操作时检查权限
$isLoggedIn = $zbp->CheckRights('root');

echo '<meta charset="utf-8">';
echo '<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.info { color: #17a2b8; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; max-height: 300px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; font-weight: bold; }
.highlight { background: #fff3cd; }
</style>';

echo '<h1>🔍 配置文件位置查找</h1>';

// 显示登录状态
if (!$isLoggedIn) {
    echo '<div class="box" style="background: #fff3cd; border-left: 4px solid #ffc107;">';
    echo '<p><strong>⚠️ 当前未登录</strong></p>';
    echo '<p>您可以查看配置信息，但无法执行测试保存操作。</p>';
    echo '<p><a href="' . $zbp->host . 'zb_system/login.php">点击登录</a></p>';
    echo '</div>';
} else {
    echo '<div class="box" style="background: #d4edda; border-left: 4px solid #28a745;">';
    echo '<p><strong>✓ 已登录</strong> - 用户: ' . $zbp->user->Name . '</p>';
    echo '</div>';
}

// 步骤 1: Z-BlogPHP 系统信息
echo '<div class="box">';
echo '<h3>步骤 1: Z-BlogPHP 系统信息</h3>';
echo '<table>';
echo '<tr><th>项目</th><th>值</th></tr>';
echo '<tr><td>Z-BlogPHP 版本</td><td>' . $zbp->version . '</td></tr>';
echo '<tr><td>安装路径</td><td>' . $zbp->path . '</td></tr>';
echo '<tr><td>用户目录</td><td>' . $zbp->usersdir . '</td></tr>';
echo '<tr><td>当前主题</td><td>' . $zbp->theme . '</td></tr>';
echo '</table>';
echo '</div>';

// 步骤 2: 检查所有可能的配置文件位置
echo '<div class="box">';
echo '<h3>步骤 2: 检查所有可能的配置文件位置</h3>';

$possibleLocations = [
    'zb_users/c_option.php' => $zbp->usersdir . 'c_option.php',
    'zb_users/theme/tpure/c_option.php' => $zbp->usersdir . 'theme/tpure/c_option.php',
    'zb_users/data/c_option.php' => $zbp->usersdir . 'data/c_option.php',
    'zb_users/plugin/tpure/c_option.php' => $zbp->usersdir . 'plugin/tpure/c_option.php',
];

echo '<table>';
echo '<tr><th>位置</th><th>存在</th><th>大小</th><th>最后修改</th></tr>';

$foundFiles = [];
foreach ($possibleLocations as $desc => $path) {
    $exists = file_exists($path);
    echo '<tr' . ($exists ? ' class="highlight"' : '') . '>';
    echo '<td>' . $desc . '</td>';
    echo '<td>' . ($exists ? '<span class="success">✓ 存在</span>' : '<span class="error">✗ 不存在</span>') . '</td>';
    echo '<td>' . ($exists ? filesize($path) . ' 字节' : '-') . '</td>';
    echo '<td>' . ($exists ? date('Y-m-d H:i:s', filemtime($path)) : '-') . '</td>';
    echo '</tr>';
    
    if ($exists) {
        $foundFiles[$desc] = $path;
    }
}
echo '</table>';
echo '</div>';

// 步骤 3: 检查 Config 对象使用的实际路径
echo '<div class="box">';
echo '<h3>步骤 3: Config 对象使用的实际路径</h3>';

// 通过反射获取 Config 类的内部信息
try {
    $configReflection = new ReflectionClass(get_class($zbp->Config('tpure')));
    
    echo '<table>';
    echo '<tr><th>属性</th><th>值</th></tr>';
    
    // 获取私有属性（如果有）
    $properties = $configReflection->getProperties();
    foreach ($properties as $prop) {
        $prop->setAccessible(true);
        $value = $prop->getValue($zbp->Config('tpure'));
        
        if (!is_object($value) && !is_array($value)) {
            echo '<tr>';
            echo '<td>' . $prop->getName() . '</td>';
            echo '<td>' . htmlspecialchars(print_r($value, true)) . '</td>';
            echo '</tr>';
        }
    }
    echo '</table>';
    
} catch (Exception $e) {
    echo '<p class="error">无法获取 Config 对象信息: ' . $e->getMessage() . '</p>';
}

// 检查父类
$zbpReflection = new ReflectionClass($zbp);
echo '<h4>ZBP 对象的配置相关方法：</h4>';
echo '<ul>';
$methods = $zbpReflection->getMethods();
foreach ($methods as $method) {
    $name = $method->getName();
    if (stripos($name, 'config') !== false || stripos($name, 'save') !== false) {
        echo '<li>' . $name . '</li>';
    }
}
echo '</ul>';

echo '</div>';

// 步骤 4: 实际测试 SaveConfig
echo '<div class="box">';
echo '<h3>步骤 4: 实际测试 SaveConfig 写入位置</h3>';

if (isset($_POST['test_save'])) {
    if (!$isLoggedIn) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;">';
        echo '<p class="error"><strong>✗ 权限不足</strong></p>';
        echo '<p>测试保存操作需要登录后台管理。</p>';
        echo '<p><a href="' . $zbp->host . 'zb_system/login.php">点击登录</a></p>';
        echo '</div>';
    } else {
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">';
        
        // 记录所有文件的修改时间
    echo '<p><strong>保存前各文件的修改时间：</strong></p>';
    $beforeTimes = [];
    foreach ($foundFiles as $desc => $path) {
        $beforeTimes[$path] = filemtime($path);
        echo '• ' . $desc . ': ' . date('Y-m-d H:i:s', $beforeTimes[$path]) . '<br>';
    }
    
    // 设置一个测试配置
    $testKey = 'TestConfigLocation_' . time();
    $zbp->Config('tpure')->$testKey = 'test_value_' . time();
    
    echo '<p style="margin-top: 15px;"><strong>执行 SaveConfig(\'tpure\')...</strong></p>';
    
    $result = $zbp->SaveConfig('tpure');
    echo '<p>返回值: ' . ($result ? '<span class="success">true</span>' : '<span class="error">false</span>') . '</p>';
    
    // 检查哪个文件被修改了
    clearstatcache();
    echo '<p style="margin-top: 15px;"><strong>保存后各文件的修改时间：</strong></p>';
    
    $modified = false;
    foreach ($foundFiles as $desc => $path) {
        $afterTime = filemtime($path);
        $changed = ($afterTime != $beforeTimes[$path]);
        
        if ($changed) {
            echo '<p class="success">✓ <strong>' . $desc . '</strong> 被修改了！</p>';
            echo '• 修改前: ' . date('Y-m-d H:i:s', $beforeTimes[$path]) . '<br>';
            echo '• 修改后: ' . date('Y-m-d H:i:s', $afterTime) . '<br>';
            echo '• 文件大小: ' . filesize($path) . ' 字节<br>';
            
            // 检查是否包含测试配置
            $content = file_get_contents($path);
            $hasTestKey = (strpos($content, $testKey) !== false);
            echo '• 包含测试配置: ' . ($hasTestKey ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '<br>';
            
            if ($hasTestKey) {
                echo '<br><p class="success"><strong>🎯 确认：配置实际保存位置是 ' . $desc . '</strong></p>';
                echo '<p>完整路径: <code>' . $path . '</code></p>';
            }
            
            $modified = true;
        } else {
            echo '• ' . $desc . ': 未修改<br>';
        }
    }
    
    if (!$modified) {
        echo '<p class="error"><strong>⚠️ 警告：没有任何文件被修改！</strong></p>';
        echo '<p>这说明 SaveConfig 可能没有真正执行写入操作。</p>';
    }
    
    echo '</div>';
    } // 结束 isLoggedIn 检查
}

echo '<form method="post">';
echo '<p>点击下方按钮，执行一次测试保存，查看实际写入的文件：</p>';
if ($isLoggedIn) {
    echo '<p><button type="submit" name="test_save" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">测试 SaveConfig 写入位置</button></p>';
} else {
    echo '<p><button type="button" disabled style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: not-allowed;" title="需要登录">测试 SaveConfig 写入位置 (需要登录)</button></p>';
    echo '<p style="color: #dc3545; font-size: 14px;">⚠️ 此操作需要登录后台管理</p>';
}
echo '</form>';

echo '</div>';

// 步骤 5: 检查现有配置文件内容
echo '<div class="box">';
echo '<h3>步骤 5: 查看主配置文件中的 tpure 配置</h3>';

$mainConfigFile = $zbp->usersdir . 'c_option.php';
if (file_exists($mainConfigFile)) {
    $content = file_get_contents($mainConfigFile);
    
    echo '<p><strong>文件路径：</strong>' . $mainConfigFile . '</p>';
    echo '<p><strong>文件大小：</strong>' . filesize($mainConfigFile) . ' 字节</p>';
    
    // 查找 tpure 配置段
    if (preg_match('/[\'"]tpure[\'"]\s*=>\s*array\s*\((.*?)\),?\s*[\'"](?:zbp|[a-zA-Z])/s', $content, $matches)) {
        echo '<p class="success">✓ 找到 tpure 配置段</p>';
        echo '<details><summary>点击查看 tpure 配置内容（前2000字符）</summary>';
        echo '<pre>' . htmlspecialchars(substr($matches[1], 0, 2000)) . (strlen($matches[1]) > 2000 ? "\n..." : "") . '</pre>';
        echo '</details>';
    } else {
        echo '<p class="error">✗ 未找到 tpure 配置段</p>';
        
        // 显示整个文件内容的前1000字符
        echo '<details><summary>点击查看整个配置文件内容（前1000字符）</summary>';
        echo '<pre>' . htmlspecialchars(substr($content, 0, 1000)) . (strlen($content) > 1000 ? "\n..." : "") . '</pre>';
        echo '</details>';
    }
}

echo '</div>';

// 步骤 6: 搜索整个 zb_users 目录下的配置文件
echo '<div class="box">';
echo '<h3>步骤 6: 搜索整个 zb_users 目录</h3>';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($zbp->usersdir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

echo '<p>搜索包含 "tpure" 配置的 .php 文件...</p>';
echo '<table>';
echo '<tr><th>文件路径</th><th>大小</th><th>修改时间</th></tr>';

$found = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filepath = $file->getPathname();
        $content = @file_get_contents($filepath);
        
        if ($content && (strpos($content, "'tpure'") !== false || strpos($content, '"tpure"') !== false)) {
            echo '<tr class="highlight">';
            echo '<td>' . str_replace($zbp->path, '', $filepath) . '</td>';
            echo '<td>' . $file->getSize() . ' 字节</td>';
            echo '<td>' . date('Y-m-d H:i:s', $file->getMTime()) . '</td>';
            echo '</tr>';
            $found++;
            
            if ($found >= 10) break; // 限制显示数量
        }
    }
}

if ($found === 0) {
    echo '<tr><td colspan="3">未找到包含 tpure 配置的文件</td></tr>';
}

echo '</table>';
echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="rebuild-config.php">← 返回配置重建页面</a>';
echo '</div>';
?>

