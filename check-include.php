<?php
/**
 * 检查 include.php 是否包含 tpure_esc_attr() 函数
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>检查 include.php</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .card { background: #fff; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-left: 3px solid #007bff; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 检查 include.php 文件</h1>
        
        <?php
        $includePath = __DIR__ . '/include.php';
        
        echo "<h2>1️⃣ 文件基本信息</h2>";
        if (file_exists($includePath)) {
            $size = filesize($includePath);
            $modified = date('Y-m-d H:i:s', filemtime($includePath));
            echo "<p class='success'>✓ 文件存在</p>";
            echo "<p>文件大小：" . number_format($size) . " bytes</p>";
            echo "<p>修改时间：{$modified}</p>";
        } else {
            echo "<p class='error'>✗ 文件不存在！</p>";
            exit;
        }
        
        echo "<h2>2️⃣ 检查函数定义</h2>";
        $content = file_get_contents($includePath);
        
        // 检查 tpure_esc_attr
        if (strpos($content, 'function tpure_esc_attr') !== false) {
            echo "<p class='success'>✓ 包含 tpure_esc_attr() 函数定义</p>";
            
            // 提取函数代码
            preg_match('/function tpure_esc_attr\([^{]*\)\s*{[^}]*}/s', $content, $matches);
            if (!empty($matches[0])) {
                echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
            }
        } else {
            echo "<p class='error'>✗ 未找到 tpure_esc_attr() 函数定义！</p>";
            echo "<p style='color: red;'>这就是导致500错误的原因！</p>";
        }
        
        // 检查 tpure_esc_url
        if (strpos($content, 'function tpure_esc_url') !== false) {
            echo "<p class='success'>✓ 包含 tpure_esc_url() 函数定义</p>";
        } else {
            echo "<p class='error'>✗ 未找到 tpure_esc_url() 函数定义</p>";
        }
        
        echo "<h2>3️⃣ 检查关键常量</h2>";
        if (strpos($content, "define('TPURE_DIR'") !== false) {
            echo "<p class='success'>✓ 包含 TPURE_DIR 常量定义</p>";
        } else {
            echo "<p class='error'>✗ 未找到 TPURE_DIR 常量定义</p>";
        }
        
        echo "<h2>4️⃣ 文件行数统计</h2>";
        $lines = substr_count($content, "\n") + 1;
        echo "<p>总行数：{$lines} 行</p>";
        if ($lines >= 520 && $lines <= 550) {
            echo "<p class='success'>✓ 行数正常（期望：532行左右）</p>";
        } else {
            echo "<p class='error'>⚠️ 行数异常（当前：{$lines}行，期望：532行）</p>";
        }
        
        echo "<h2>5️⃣ 诊断结果</h2>";
        $hasTpureEscAttr = strpos($content, 'function tpure_esc_attr') !== false;
        $hasTpureDirDefine = strpos($content, "define('TPURE_DIR'") !== false;
        $sizeOk = $size >= 23000 && $size <= 25000;
        $linesOk = $lines >= 520 && $lines <= 550;
        
        if ($hasTpureEscAttr && $hasTpureDirDefine && $sizeOk && $linesOk) {
            echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
            echo "<h3 style='color: #155724;'>✅ include.php 文件完整</h3>";
            echo "<p>文件包含所有必需的函数和常量。</p>";
            echo "<p><strong>如果仍然出现错误，请：</strong></p>";
            echo "<ol>";
            echo "<li>清除 PHP OpCache：重启 PHP-FPM 或访问 clear-cache.php</li>";
            echo "<li>清除浏览器缓存</li>";
            echo "<li>重新访问网站</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
            echo "<h3 style='color: #721c24;'>❌ include.php 文件不完整</h3>";
            echo "<p><strong>缺失项：</strong></p>";
            echo "<ul>";
            if (!$hasTpureEscAttr) echo "<li>tpure_esc_attr() 函数</li>";
            if (!$hasTpureDirDefine) echo "<li>TPURE_DIR 常量</li>";
            if (!$sizeOk) echo "<li>文件大小异常</li>";
            if (!$linesOk) echo "<li>文件行数异常</li>";
            echo "</ul>";
            echo "<p><strong>解决方案：</strong></p>";
            echo "<ol>";
            echo "<li>重新上传本地的 include.php 文件（532行版本）</li>";
            echo "<li>确保上传模式为二进制（Binary）而非文本（ASCII）</li>";
            echo "<li>上传后再次运行此检查工具</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
        
        <h2>🔗 相关链接</h2>
        <p>
            <a href="diagnose-500.php">完整诊断工具</a> |
            <a href="clear-cache.php">清除缓存</a> |
            <a href="../../../">返回首页</a>
        </p>
    </div>
</body>
</html>

