<?php
/**
 * 缓存配置保存测试
 * 模拟主题配置页面的保存操作
 */

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

// 🔧 调试模式：显示详细权限信息
$hasRights = $zbp->CheckRights('root');

if (!$hasRights) {
    echo '<meta charset="utf-8">';
    echo '<style>body{font-family:Arial;padding:20px;background:#fff3cd;}
    .box{background:white;padding:20px;margin:10px 0;border-radius:5px;border-left:4px solid #ffc107;}
    h2{color:#dc3545;}</style>';
    echo '<div class="box"><h2>🔒 权限不足</h2>';
    echo '<p>请先 <a href="' . $zbp->host . 'zb_system/login.php">登录后台</a> 后再访问此页面。</p>';
    echo '<p>当前登录状态：' . ($zbp->user->ID > 0 ? '已登录 (' . $zbp->user->Name . ')' : '未登录') . '</p>';
    echo '</div>';
    die();
}

echo '<meta charset="utf-8">';
echo '<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; }
.btn { padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn:hover { background: #0056b3; }
.current { background: #e7f3ff; }
</style>';

echo '<h1>🧪 缓存配置保存测试</h1>';

// 处理保存操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_test'])) {
    echo '<div class="box" style="background: #d4edda; border-left: 4px solid #28a745;">';
    echo '<h3>保存操作执行中...</h3>';
    
    // 保存前的值
    echo '<p><strong>保存前的值:</strong></p>';
    echo '<ul>';
    echo '<li>CacheFullPageOn: ' . ($zbp->Config('tpure')->CacheFullPageOn ?: '未设置') . '</li>';
    echo '<li>CacheHotContentOn: ' . ($zbp->Config('tpure')->CacheHotContentOn ?: '未设置') . '</li>';
    echo '<li>CacheBrowserOn: ' . ($zbp->Config('tpure')->CacheBrowserOn ?: '未设置') . '</li>';
    echo '<li>CacheTemplateOn: ' . ($zbp->Config('tpure')->CacheTemplateOn ?: '未设置') . '</li>';
    echo '</ul>';
    
    // 模拟保存（与 main.php 相同的逻辑）
    $zbp->Config('tpure')->CacheFullPageOn = isset($_POST['CacheFullPageOn']) ? $_POST['CacheFullPageOn'] : 'OFF';
    $zbp->Config('tpure')->CacheHotContentOn = isset($_POST['CacheHotContentOn']) ? $_POST['CacheHotContentOn'] : 'OFF';
    $zbp->Config('tpure')->CacheBrowserOn = isset($_POST['CacheBrowserOn']) ? $_POST['CacheBrowserOn'] : 'OFF';
    $zbp->Config('tpure')->CacheTemplateOn = isset($_POST['CacheTemplateOn']) ? $_POST['CacheTemplateOn'] : 'ON';
    
    // 保存配置
    $saveResult = $zbp->SaveConfig('tpure');
    
    if ($saveResult) {
        echo '<p class="success">✓ 配置保存成功！</p>';
    } else {
        echo '<p class="error">✗ 配置保存失败！</p>';
    }
    
    // 保存后的值
    echo '<p><strong>保存后的值:</strong></p>';
    echo '<ul>';
    echo '<li>CacheFullPageOn: ' . $zbp->Config('tpure')->CacheFullPageOn . '</li>';
    echo '<li>CacheHotContentOn: ' . $zbp->Config('tpure')->CacheHotContentOn . '</li>';
    echo '<li>CacheBrowserOn: ' . $zbp->Config('tpure')->CacheBrowserOn . '</li>';
    echo '<li>CacheTemplateOn: ' . $zbp->Config('tpure')->CacheTemplateOn . '</li>';
    echo '</ul>';
    
    echo '<p style="margin-top: 15px;"><a href="' . $_SERVER['PHP_SELF'] . '" class="btn">刷新页面查看</a></p>';
    echo '</div>';
}

// 显示当前配置
echo '<div class="box">';
echo '<h3>当前缓存配置状态</h3>';
echo '<table>';
echo '<tr><th>配置项</th><th>当前值</th><th>说明</th></tr>';
echo '<tr class="current">';
echo '<td>CacheFullPageOn</td>';
echo '<td><strong>' . ($zbp->Config('tpure')->CacheFullPageOn ?: 'OFF') . '</strong></td>';
echo '<td>Redis 全页面缓存</td>';
echo '</tr>';
echo '<tr>';
echo '<td>CacheHotContentOn</td>';
echo '<td><strong>' . ($zbp->Config('tpure')->CacheHotContentOn ?: 'OFF') . '</strong></td>';
echo '<td>热门内容 HTML 缓存</td>';
echo '</tr>';
echo '<tr class="current">';
echo '<td>CacheBrowserOn</td>';
echo '<td><strong>' . ($zbp->Config('tpure')->CacheBrowserOn ?: 'OFF') . '</strong></td>';
echo '<td>浏览器缓存（HTTP）</td>';
echo '</tr>';
echo '<tr>';
echo '<td>CacheTemplateOn</td>';
echo '<td><strong>' . ($zbp->Config('tpure')->CacheTemplateOn ?: 'ON') . '</strong></td>';
echo '<td>模板缓存</td>';
echo '</tr>';
echo '</table>';
echo '</div>';

// 测试表单
echo '<div class="box">';
echo '<h3>保存测试表单</h3>';
echo '<form method="post">';
echo '<p><strong>请选择要启用的缓存功能：</strong></p>';

$configs = [
    'CacheFullPageOn' => 'Redis 全页面缓存',
    'CacheHotContentOn' => '热门内容 HTML 缓存',
    'CacheBrowserOn' => '浏览器缓存（HTTP）',
    'CacheTemplateOn' => '模板缓存'
];

foreach ($configs as $key => $name) {
    $checked = ($zbp->Config('tpure')->$key == 'ON' || $zbp->Config('tpure')->$key == '1') ? 'checked' : '';
    echo '<div style="margin: 10px 0;">';
    echo '<label>';
    echo '<input type="checkbox" name="' . $key . '" value="ON" ' . $checked . '>';
    echo ' <strong>' . $name . '</strong>';
    echo '</label>';
    echo '</div>';
}

echo '<p style="margin-top: 20px;">';
echo '<button type="submit" name="save_test" class="btn">保存配置</button>';
echo '</p>';
echo '</form>';
echo '</div>';

// 调试信息
echo '<div class="box" style="background: #fff3cd;">';
echo '<h3>调试信息</h3>';
echo '<pre style="background: white; padding: 15px; overflow-x: auto;">';
echo "POST数据:\n";
print_r($_POST);
echo "\n配置对象:\n";
print_r($zbp->Config('tpure'));
echo '</pre>';
echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="main.php?act=config">← 返回主题配置页面</a> | ';
echo '<a href="cache-diagnostic.php">查看诊断报告</a>';
echo '</div>';
?>

