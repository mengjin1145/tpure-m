<?php
/**
 * 缓存配置诊断工具
 * 用于检查缓存开关状态和配置
 */

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

// 🔓 允许未登录访问查看信息，仅测试保存需要权限
$isLoggedIn = $zbp->CheckRights('root');

echo '<meta charset="utf-8">';
echo '<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.success { color: #28a745; }
.warning { color: #ffc107; }
.error { color: #dc3545; }
.title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
.item { padding: 8px 0; border-bottom: 1px solid #eee; }
.item:last-child { border-bottom: none; }
.label { font-weight: bold; display: inline-block; width: 200px; }
.value { color: #666; }
.on { color: #28a745; font-weight: bold; }
.off { color: #dc3545; font-weight: bold; }
</style>';

echo '<h1>🔍 缓存配置诊断报告</h1>';

// 显示登录状态
if (!$isLoggedIn) {
    echo '<div class="box" style="background: #fff3cd; border-left: 4px solid #ffc107;">';
    echo '<p><strong>⚠️ 当前未登录</strong></p>';
    echo '<p>您可以查看诊断信息，但无法执行测试保存操作。<a href="' . $zbp->host . 'zb_system/login.php">点击登录</a></p>';
    echo '</div>';
}

// ========== 步骤 1: 检查配置项是否存在 ==========
echo '<div class="box">';
echo '<div class="title">步骤 1: 检查配置项是否存在</div>';

$configs = [
    'CacheFullPageOn' => 'Redis 全页面缓存',
    'CacheHotContentOn' => '热门内容 HTML 缓存',
    'CacheBrowserOn' => '浏览器缓存（HTTP）',
    'CacheTemplateOn' => '模板缓存'
];

foreach ($configs as $key => $name) {
    $exists = $zbp->Config('tpure')->HasKey($key);
    $value = $exists ? $zbp->Config('tpure')->$key : '不存在';
    $status = $exists ? '<span class="success">✓ 存在</span>' : '<span class="error">✗ 不存在</span>';
    
    echo '<div class="item">';
    echo '<span class="label">' . $name . ':</span> ';
    echo $status . ' ';
    echo '<span class="value">当前值: ' . ($value === '1' || $value === 'ON' ? '<span class="on">ON</span>' : '<span class="off">OFF</span>') . '</span>';
    echo '</div>';
}
echo '</div>';

// ========== 步骤 2: 检查配置文件内容 ==========
echo '<div class="box">';
echo '<div class="title">步骤 2: 检查配置文件内容</div>';

$configFile = $zbp->usersdir . 'c_option.php';
if (file_exists($configFile)) {
    echo '<div class="item"><span class="success">✓ 配置文件存在:</span> ' . $configFile . '</div>';
    
    // 读取配置文件内容
    $content = file_get_contents($configFile);
    $hasCache = false;
    foreach ($configs as $key => $name) {
        if (strpos($content, "'$key'") !== false || strpos($content, "\"$key\"") !== false) {
            echo '<div class="item"><span class="success">✓</span> 配置文件中找到: ' . $key . '</div>';
            $hasCache = true;
        }
    }
    
    if (!$hasCache) {
        echo '<div class="item"><span class="warning">⚠ 配置文件中未找到缓存相关配置</span></div>';
    }
} else {
    echo '<div class="item"><span class="error">✗ 配置文件不存在</span></div>';
}
echo '</div>';

// ========== 步骤 3: 检查 Redis 可用性 ==========
echo '<div class="box">';
echo '<div class="title">步骤 3: 检查 Redis 可用性</div>';

// 检查 Redis 扩展
if (extension_loaded('redis')) {
    echo '<div class="item"><span class="success">✓ Redis 扩展已安装</span></div>';
    
    // 尝试连接 Redis
    try {
        $redis = new Redis();
        if (@$redis->connect('127.0.0.1', 6379, 2)) {
            echo '<div class="item"><span class="success">✓ Redis 连接成功</span></div>';
            
            // 测试写入
            $testKey = 'tpure_test_' . time();
            if ($redis->set($testKey, 'test', 10)) {
                echo '<div class="item"><span class="success">✓ Redis 写入测试成功</span></div>';
                $redis->del($testKey);
            } else {
                echo '<div class="item"><span class="error">✗ Redis 写入测试失败</span></div>';
            }
            $redis->close();
        } else {
            echo '<div class="item"><span class="error">✗ Redis 连接失败（请检查 Redis 服务是否启动）</span></div>';
        }
    } catch (Exception $e) {
        echo '<div class="item"><span class="error">✗ Redis 错误: ' . $e->getMessage() . '</span></div>';
    }
} else {
    echo '<div class="item"><span class="error">✗ Redis 扩展未安装</span></div>';
    echo '<div class="item"><span class="warning">提示: Redis 全页面缓存和热门内容缓存需要 Redis 支持</span></div>';
}
echo '</div>';

// ========== 步骤 4: 检查 zbpcache 插件 ==========
echo '<div class="box">';
echo '<div class="title">步骤 4: 检查 zbpcache 插件</div>';

$zbpcachePlugin = $zbp->LoadApp('plugin', 'zbpcache');
if ($zbpcachePlugin->isloaded) {
    echo '<div class="item"><span class="success">✓ zbpcache 插件已安装并启用</span></div>';
} else {
    echo '<div class="item"><span class="error">✗ zbpcache 插件未安装或未启用</span></div>';
    echo '<div class="item"><span class="warning">提示: 全页面缓存功能需要安装 zbpcache 插件</span></div>';
}
echo '</div>';

// ========== 步骤 5: 模拟保存测试 ==========
echo '<div class="box">';
echo '<div class="title">步骤 5: 配置保存测试</div>';
echo '<div class="item">';
echo '<form method="post" style="margin-top: 10px;">';
echo '<p>选择要测试的缓存开关：</p>';
foreach ($configs as $key => $name) {
    $checked = ($zbp->Config('tpure')->$key == '1' || $zbp->Config('tpure')->$key == 'ON') ? 'checked' : '';
    echo '<label style="display: block; margin: 5px 0;">';
    echo '<input type="checkbox" name="test_' . $key . '" value="ON" ' . $checked . '> ' . $name;
    echo '</label>';
}
echo '<button type="submit" name="test_save" style="margin-top: 10px; padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">测试保存</button>';
echo '</form>';
echo '</div>';

// 处理测试保存
if (isset($_POST['test_save'])) {
    if (!$isLoggedIn) {
        echo '<div class="item" style="margin-top: 15px; padding: 15px; background: #f8d7da; border-left: 4px solid #dc3545;">';
        echo '<strong class="error">✗ 权限不足</strong><br><br>';
        echo '测试保存操作需要登录后台管理。<a href="' . $zbp->host . 'zb_system/login.php">点击登录</a>';
        echo '</div>';
    } else {
        echo '<div class="item" style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff;">';
        echo '<strong>保存测试结果:</strong><br><br>';
        
        foreach ($configs as $key => $name) {
            $value = isset($_POST['test_' . $key]) ? 'ON' : 'OFF';
            $zbp->Config('tpure')->$key = $value;
            echo '• ' . $name . ': ' . ($value === 'ON' ? '<span class="on">ON</span>' : '<span class="off">OFF</span>') . '<br>';
        }
        
        $saveResult = $zbp->SaveConfig('tpure');
        
        if ($saveResult) {
            echo '<br><span class="success">✓ 配置保存成功！</span><br>';
            echo '<small style="color: #666;">请刷新本页面查看最新状态</small>';
        } else {
            echo '<br><span class="error">✗ 配置保存失败</span>';
        }
        echo '</div>';
    }
}
echo '</div>';

// ========== 步骤 6: 功能状态建议 ==========
echo '<div class="box">';
echo '<div class="title">步骤 6: 功能状态与建议</div>';

// Redis 全页面缓存
$fullPageOn = $zbp->Config('tpure')->CacheFullPageOn;
echo '<div class="item">';
echo '<span class="label">Redis 全页面缓存:</span> ';
if ($fullPageOn == 'ON' || $fullPageOn == '1') {
    if (!$zbpcachePlugin->isloaded) {
        echo '<span class="warning">⚠ 已开启，但 zbpcache 插件未安装</span>';
    } elseif (!extension_loaded('redis')) {
        echo '<span class="warning">⚠ 已开启，但 Redis 扩展未安装</span>';
    } else {
        echo '<span class="success">✓ 已开启且环境正常</span>';
    }
} else {
    echo '<span class="off">OFF (未开启)</span>';
}
echo '</div>';

// 热门内容缓存
$hotContentOn = $zbp->Config('tpure')->CacheHotContentOn;
echo '<div class="item">';
echo '<span class="label">热门内容 HTML 缓存:</span> ';
if ($hotContentOn == 'ON' || $hotContentOn == '1') {
    if (!extension_loaded('redis')) {
        echo '<span class="warning">⚠ 已开启，但 Redis 扩展未安装</span>';
    } else {
        echo '<span class="success">✓ 已开启且环境正常</span>';
    }
} else {
    echo '<span class="off">OFF (未开启)</span>';
}
echo '</div>';

// 浏览器缓存
$browserOn = $zbp->Config('tpure')->CacheBrowserOn;
echo '<div class="item">';
echo '<span class="label">浏览器缓存:</span> ';
echo ($browserOn == 'ON' || $browserOn == '1') ? '<span class="on">ON (已开启)</span>' : '<span class="off">OFF (未开启)</span>';
echo '</div>';

// 模板缓存
$templateOn = $zbp->Config('tpure')->CacheTemplateOn;
echo '<div class="item">';
echo '<span class="label">模板缓存:</span> ';
echo ($templateOn == 'ON' || $templateOn == '1') ? '<span class="on">ON (已开启)</span>' : '<span class="off">OFF (未开启)</span>';
echo '</div>';

echo '</div>';

// ========== 操作建议 ==========
echo '<div class="box" style="background: #fff3cd; border-left: 4px solid #ffc107;">';
echo '<div class="title">💡 操作建议</div>';
echo '<ol style="line-height: 1.8;">';
echo '<li>如果配置项不存在，请访问主题设置的"主题配置"页面保存一次设置</li>';
echo '<li>如果要使用 Redis 缓存，请先安装 Redis 扩展和 zbpcache 插件</li>';
echo '<li>浏览器缓存和模板缓存不依赖 Redis，可以直接使用</li>';
echo '<li>修改配置后，建议清空缓存并刷新网站验证效果</li>';
echo '</ol>';
echo '</div>';

echo '<div style="margin-top: 20px; text-align: center; color: #999;">';
echo '<a href="main.php?act=config" style="color: #007bff;">← 返回主题配置页面</a>';
echo '</div>';
?>

