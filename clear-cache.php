<?php
/**
 * 清除模板缓存 - 紧急修复工具
 */

require dirname(__FILE__) . '/../../../zb_system/function/c_system_base.php';
$zbp->Load();

// 检查权限
if ($zbp->user->Level > 1) {
    die('⛔ 需要管理员权限');
}

echo '<h1>🧹 清除缓存工具</h1><hr>';

// 1. 清除编译缓存
$cacheDir = ZBP_PATH . 'zb_users/cache/compiled/';
$count = 0;

if (is_dir($cacheDir)) {
    // 删除主题编译缓存
    $files = glob($cacheDir . 'tpure/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    
    // 删除所有编译缓存
    $files = glob($cacheDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    echo '<p>✅ 已清除 ' . $count . ' 个编译缓存文件</p>';
} else {
    echo '<p>⚠️ 编译缓存目录不存在</p>';
}

// 清除缓存目录
$cacheFiles = array(
    ZBP_PATH . 'zb_users/cache/nm_cache.php',
    ZBP_PATH . 'zb_users/cache/tags_cache.php',
    ZBP_PATH . 'zb_users/cache/categories_cache.php'
);

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo '<p>✅ 已删除: ' . basename($file) . '</p>';
    }
}

// 2. 清除配置缓存
$zbp->SaveConfig('system');
echo '<p>✅ 已刷新系统配置</p>';

// 3. 清除主题缓存
$zbp->SaveConfig('tpure');
echo '<p>✅ 已刷新主题配置</p>';

echo '<hr>';
echo '<h2>🎉 缓存清除完成！</h2>';
echo '<p><a href="' . $zbp->host . '" style="display:inline-block; padding:12px 24px; background:#28a745; color:white; text-decoration:none; border-radius:6px; font-size:16px;">访问首页测试</a></p>';
?>

