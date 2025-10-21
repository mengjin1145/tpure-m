<?php
/**
 * 🔧 Tpure 主题 - 500 错误诊断工具
 * 
 * 使用方法：
 * 1. 上传到主题目录：zb_users/theme/tpure/
 * 2. 访问：http://你的域名/zb_users/theme/tpure/diagnose-500.php
 * 3. 查看详细错误信息
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tpure 500错误诊断</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        h2 { color: #666; margin: 20px 0 10px; font-size: 18px; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table td { padding: 8px; border: 1px solid #ddd; }
        table td:first-child { width: 200px; font-weight: bold; background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 5px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>🔧 Tpure 主题 - 500错误诊断工具</h1>
        <p style="color: #666; margin-bottom: 20px;">
            当前时间：<?php echo date('Y-m-d H:i:s'); ?>
        </p>

        <?php
        // ==================== 步骤1：检查基础环境 ====================
        echo "<h2>📋 步骤1：基础环境检查</h2>";
        echo "<table>";
        
        $checks = [
            'PHP版本' => phpversion(),
            '当前目录' => getcwd(),
            '主题目录' => __DIR__,
        ];
        
        foreach ($checks as $key => $value) {
            echo "<tr><td>{$key}</td><td>{$value}</td></tr>";
        }
        echo "</table>";

        // ==================== 步骤2：检查文件是否存在 ====================
        echo "<h2>📁 步骤2：核心文件检查</h2>";
        
        $files = [
            'include.php',
            'lib/helpers.php',
            'lib/functions-core.php',
            'lib/ajax.php',
            'lib/fullpage-cache.php',
            'lib/http-cache.php',
            'lib/cache.php',
            'lib/statistics.php',
            'lib/database.php',
            'lib/hot-cache.php',
            'lib/theme-admin.php',
            'lib/debug-handler.php',
        ];
        
        echo "<table>";
        foreach ($files as $file) {
            $path = __DIR__ . '/' . $file;
            $exists = file_exists($path);
            $status = $exists ? '<span class="success">✓ 存在</span>' : '<span class="error">✗ 缺失</span>';
            $size = $exists ? ' (' . number_format(filesize($path)) . ' bytes)' : '';
            echo "<tr><td>{$file}</td><td>{$status}{$size}</td></tr>";
        }
        echo "</table>";

        // ==================== 步骤3：文件大小对比 ====================
        echo "<h2>📏 步骤3：文件大小对比</h2>";
        
        $sizeChecks = [
            'lib/functions-core.php' => [
                'current' => filesize(__DIR__ . '/lib/functions-core.php'),
                'expected' => 26000, // 516行新版本约26KB
                'critical' => true
            ],
            'include.php' => [
                'current' => filesize(__DIR__ . '/include.php'),
                'expected' => 24000, // 532行约24KB
                'critical' => true
            ],
        ];
        
        echo "<table>";
        foreach ($sizeChecks as $file => $info) {
            $status = 'info';
            $message = '正常';
            
            if ($info['current'] < $info['expected'] * 0.8) {
                $status = 'error';
                $message = '⚠️ 文件太小，可能是旧版本';
            } elseif ($info['current'] < $info['expected'] * 0.95) {
                $status = 'warning';
                $message = '⚠️ 文件偏小';
            } else {
                $status = 'success';
                $message = '✓ 文件完整';
            }
            
            $statusClass = $status === 'error' ? 'error' : ($status === 'warning' ? 'warning' : 'success');
            
            echo "<tr>";
            echo "<td>{$file}</td>";
            echo "<td>";
            echo "<span class='{$statusClass}'>{$message}</span><br>";
            echo "当前：" . number_format($info['current']) . " bytes<br>";
            echo "期望：约 " . number_format($info['expected']) . " bytes";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // ==================== 步骤4：检查关键函数 ====================
        echo "<h2>🔍 步骤4：关键函数检查</h2>";
        
        // 先尝试加载主题文件
        try {
            require_once __DIR__ . '/lib/helpers.php';
            echo "<p class='success'>✓ lib/helpers.php 加载成功</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ lib/helpers.php 加载失败：" . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        try {
            require_once __DIR__ . '/lib/functions-core.php';
            echo "<p class='success'>✓ lib/functions-core.php 加载成功</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ lib/functions-core.php 加载失败：" . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // 检查关键函数
        $required_functions = [
            'tpure_esc_url',
            'tpure_esc_attr',
            'tpure_SubMenu',
            'tpure_AddMenu',
            'tpure_Header',
            'tpure_Exclude_CategorySelect',
            'tpure_color',
            'tpure_CreateModule',
            'tpure_SideContent',
            'tpure_navcate',
            'tpure_Refresh',
            'tpure_ErrorCode',
        ];
        
        echo "<table>";
        $missing = [];
        foreach ($required_functions as $func) {
            $exists = function_exists($func);
            $status = $exists ? '<span class="success">✓ 存在</span>' : '<span class="error">✗ 缺失</span>';
            echo "<tr><td>{$func}()</td><td>{$status}</td></tr>";
            if (!$exists) {
                $missing[] = $func;
            }
        }
        echo "</table>";

        // ==================== 步骤5：诊断结果总结 ====================
        echo "<h2>📊 步骤5：诊断结果总结</h2>";
        
        if (empty($missing)) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;'>";
            echo "<h3 style='color: #155724; margin-bottom: 10px;'>✅ 所有检查通过！</h3>";
            echo "<p>所有核心文件和函数都已正确加载。</p>";
            echo "<p><strong>下一步：</strong></p>";
            echo "<ol style='margin-left: 20px;'>";
            echo "<li>清除缓存：访问 <code>clear-cache.php</code></li>";
            echo "<li>访问前台首页测试</li>";
            echo "<li>访问后台主题配置页面</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
            echo "<h3 style='color: #721c24; margin-bottom: 10px;'>❌ 发现问题</h3>";
            echo "<p><strong>缺失的函数（" . count($missing) . "个）：</strong></p>";
            echo "<ul style='margin-left: 20px;'>";
            foreach ($missing as $func) {
                echo "<li><code>{$func}()</code></li>";
            }
            echo "</ul>";
            echo "<p style='margin-top: 15px;'><strong>解决方案：</strong></p>";
            echo "<ol style='margin-left: 20px;'>";
            echo "<li>重新上传 <code>lib/functions-core.php</code> 文件（本地516行版本）</li>";
            echo "<li>重新上传 <code>include.php</code> 文件（包含 tpure_esc_attr 函数）</li>";
            echo "<li>清除服务器缓存</li>";
            echo "<li>重新运行此诊断工具</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>

        <h2>🔗 相关链接</h2>
        <ul style="line-height: 2;">
            <li><a href="clear-cache.php" target="_blank">清除缓存</a></li>
            <li><a href="test-functions.php" target="_blank">函数检测工具</a></li>
            <li><a href="simple-test.php" target="_blank">简化诊断工具</a></li>
        </ul>
    </div>
</div>
</body>
</html>

