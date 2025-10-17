<?php
/**
 * 强制清除所有缓存（无需登录）
 */

// 显示错误
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>🔥 强制清除缓存</h1><hr>';

// 定义路径
$paths = array(
    '../../../zb_users/cache/compiled/' => '编译缓存',
    '../../../zb_users/cache/' => '缓存文件'
);

$totalDeleted = 0;

foreach ($paths as $path => $name) {
    $fullPath = dirname(__FILE__) . '/' . $path;
    
    if (is_dir($fullPath)) {
        echo "<h3>清除: {$name}</h3>";
        
        // 删除所有 .php 文件
        $files = glob($fullPath . '*.php');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) != 'index.html') {
                if (unlink($file)) {
                    echo '✅ 已删除: ' . basename($file) . '<br>';
                    $totalDeleted++;
                }
            }
        }
        
        // 删除 compiled 子目录下的文件
        if (is_dir($fullPath . 'compiled/')) {
            $compiledFiles = glob($fullPath . 'compiled/*');
            foreach ($compiledFiles as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        echo '✅ 已删除: compiled/' . basename($file) . '<br>';
                        $totalDeleted++;
                    }
                }
            }
        }
        
        // 删除 tpure 主题编译缓存
        if (is_dir($fullPath . 'compiled/tpure/')) {
            $tpureFiles = glob($fullPath . 'compiled/tpure/*');
            foreach ($tpureFiles as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        echo '✅ 已删除: compiled/tpure/' . basename($file) . '<br>';
                        $totalDeleted++;
                    }
                }
            }
        }
    }
}

echo '<hr>';
echo "<h2 style='color:green;'>🎉 完成！共删除 {$totalDeleted} 个缓存文件</h2>";
echo '<p><strong>现在访问：</strong></p>';
echo '<ul>';
echo '<li><a href="' . str_replace('/zb_users/theme/tpure/force-clear-cache.php', '/tags.html', $_SERVER['PHP_SELF']) . '" style="color:#0188fb;font-size:18px;">📌 标签页</a></li>';
echo '<li><a href="' . str_replace('/zb_users/theme/tpure/force-clear-cache.php', '/', $_SERVER['PHP_SELF']) . '" style="color:#0188fb;font-size:18px;">🏠 首页</a></li>';
echo '</ul>';
?>

