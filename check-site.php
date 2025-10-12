<?php
/**
 * 检查网站状态
 */

// 强制错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>网站状态检查</h2>";

// 检查首页
echo "<h3>1. 检查网站首页</h3>";
$homeUrl = 'http://www.dcyzq.com/';
echo "URL: <a href='{$homeUrl}' target='_blank'>{$homeUrl}</a><br>";

$ch = curl_init($homeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ 错误: {$error}<br>";
} else {
    echo "HTTP 状态码: <b>{$httpCode}</b><br>";
    
    if ($httpCode == 200) {
        echo "✅ 网站可以访问<br>";
        
        // 提取内容（去掉头部）
        $headerSize = strpos($response, "\r\n\r\n");
        $body = substr($response, $headerSize + 4);
        $bodyLength = strlen($body);
        
        echo "响应内容长度: {$bodyLength} 字节<br>";
        
        if ($bodyLength < 100) {
            echo "⚠️ 内容太短，可能有问题<br>";
            echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";
        } else {
            echo "✅ 内容长度正常<br>";
            echo "前 500 字符:<br>";
            echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";
        }
    } elseif ($httpCode == 500) {
        echo "❌ HTTP 500 错误！服务器内部错误<br>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    } else {
        echo "⚠️ 异常状态码<br>";
    }
}

// 检查主题文件
echo "<hr>";
echo "<h3>2. 检查主题文件</h3>";

$currentDir = __DIR__;
if (strpos($currentDir, 'zb_users/theme/tpure') !== false) {
    $themeDir = $currentDir . '/';
} else {
    $themeDir = $currentDir . '/zb_users/theme/tpure/';
}

echo "主题目录: {$themeDir}<br><br>";

$files = [
    'include.php' => 7000,  // 预期大小（字节）
    'lib/helpers.php' => 20000,
    'lib/error-handler.php' => 8000,
    'lib/security.php' => 10000,
    'lib/cache.php' => 8000,
];

foreach ($files as $file => $expectedSize) {
    $path = $themeDir . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $status = ($size > $expectedSize * 0.8) ? '✅' : '⚠️';
        echo "{$status} {$file}: {$size} 字节<br>";
    } else {
        echo "❌ {$file}: 不存在<br>";
    }
}

// 检查 Z-BlogPHP 错误日志
echo "<hr>";
echo "<h3>3. 检查错误日志</h3>";

$zbpPath = dirname(dirname(dirname($themeDir)));
$logDir = $zbpPath . '/zb_users/logs/';

echo "日志目录: {$logDir}<br>";

if (is_dir($logDir)) {
    $logFiles = glob($logDir . '*.txt');
    if ($logFiles) {
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo "最新日志: " . basename($logFiles[0]) . "<br>";
        echo "修改时间: " . date('Y-m-d H:i:s', filemtime($logFiles[0])) . "<br><br>";
        
        $content = file_get_contents($logFiles[0]);
        $lines = explode("\n", $content);
        $lastLines = array_slice($lines, -20);
        
        echo "<b>最后 20 行:</b><br>";
        echo "<pre style='background:#f5f5f5;padding:10px;'>";
        echo htmlspecialchars(implode("\n", $lastLines));
        echo "</pre>";
    } else {
        echo "✅ 没有错误日志<br>";
    }
} else {
    echo "❌ 日志目录不存在<br>";
}

echo "<hr>";
echo "<p><b>检查完成！</b></p>";

