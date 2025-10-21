<?php
/**
 * Tpure 主题 - 错误诊断工具
 * 快速检测错误处理功能状态
 */

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>错误处理诊断工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 3px solid #0188fb;
            padding-bottom: 10px;
        }
        h3 {
            color: #0188fb;
            margin-top: 30px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            color: #fff;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.on {
            background: #4caf50;
        }
        .status.off {
            background: #f44336;
        }
        .status.warning {
            background: #ff9800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #0188fb;
            color: #fff;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #0188fb;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0188fb;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        .btn:hover {
            background: #0170d8;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🔍 错误处理功能诊断</h2>
    <p>生成时间: <?php echo date('Y-m-d H:i:s'); ?></p>

    <?php
    // 1. 检查错误处理器文件
    echo "<h3>1️⃣ 错误处理器文件检查</h3>";
    echo "<table>";
    echo "<thead><tr><th>文件</th><th>状态</th><th>说明</th></tr></thead>";
    echo "<tbody>";
    
    $handlers = [
        'lib/error-handler.php' => '完整错误处理器',
        'lib/error-handler-safe.php' => '安全错误处理器',
        'lib/debug-handler.php' => '调试模式处理器',
    ];
    
    foreach ($handlers as $file => $desc) {
        $exists = file_exists(__DIR__ . '/' . $file);
        $status = $exists ? "<span class='status on'>存在</span>" : "<span class='status off'>缺失</span>";
        echo "<tr>";
        echo "<td><code>{$file}</code></td>";
        echo "<td>{$status}</td>";
        echo "<td>{$desc}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // 2. 检查 TPURE_DEBUG 常量
    echo "<h3>2️⃣ 调试模式状态</h3>";
    
    $includeFile = __DIR__ . '/include.php';
    $debugEnabled = false;
    
    if (file_exists($includeFile)) {
        $content = file_get_contents($includeFile);
        
        // 检查是否定义了 TPURE_DEBUG
        if (preg_match("/define\s*\(\s*['\"]TPURE_DEBUG['\"]\s*,\s*true\s*\)/i", $content)) {
            $debugEnabled = true;
            echo "<div class='success-box'>";
            echo "✅ <strong>调试模式已启用</strong><br>";
            echo "在 <code>include.php</code> 中找到 <code>define('TPURE_DEBUG', true);</code>";
            echo "</div>";
        } elseif (preg_match("/define\s*\(\s*['\"]TPURE_DEBUG['\"]\s*,\s*false\s*\)/i", $content)) {
            echo "<div class='warning-box'>";
            echo "⚠️ <strong>调试模式已禁用</strong><br>";
            echo "在 <code>include.php</code> 中找到 <code>define('TPURE_DEBUG', false);</code>";
            echo "</div>";
        } else {
            echo "<div class='info-box'>";
            echo "ℹ️ <strong>调试模式未配置</strong><br>";
            echo "在 <code>include.php</code> 中未找到 <code>TPURE_DEBUG</code> 定义";
            echo "</div>";
        }
    } else {
        echo "<div class='error-box'>";
        echo "❌ 无法读取 <code>include.php</code> 文件";
        echo "</div>";
    }
    
    // 3. 检查日志目录
    echo "<h3>3️⃣ 日志目录检查</h3>";
    
    $logDirs = [
        '../../logs/' => 'Z-BlogPHP 日志目录（推荐）',
        '../../cache/' => '缓存目录（旧版）',
    ];
    
    echo "<table>";
    echo "<thead><tr><th>目录</th><th>状态</th><th>权限</th><th>说明</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($logDirs as $dir => $desc) {
        $fullPath = __DIR__ . '/' . $dir;
        $exists = is_dir($fullPath);
        $writable = $exists && is_writable($fullPath);
        
        $status = $exists ? 
            ($writable ? "<span class='status on'>可写</span>" : "<span class='status warning'>只读</span>") :
            "<span class='status off'>不存在</span>";
        
        $permission = $exists ? substr(sprintf('%o', fileperms($fullPath)), -4) : 'N/A';
        
        echo "<tr>";
        echo "<td><code>{$dir}</code></td>";
        echo "<td>{$status}</td>";
        echo "<td><code>{$permission}</code></td>";
        echo "<td>{$desc}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // 4. 检查错误日志文件
    echo "<h3>4️⃣ 错误日志文件检查</h3>";
    
    $logFiles = [
        '../../logs/tpure-error.log' => '当前日志文件',
        '../../cache/error.log' => '旧版日志文件',
    ];
    
    echo "<table>";
    echo "<thead><tr><th>相对路径</th><th>完整路径</th><th>状态</th><th>大小</th><th>最后修改</th></tr></thead>";
    echo "<tbody>";
    
    $hasLogs = false;
    
    foreach ($logFiles as $file => $desc) {
        $fullPath = __DIR__ . '/' . $file;
        $realPath = realpath($fullPath);
        $exists = file_exists($fullPath);
        
        if ($exists) {
            $hasLogs = true;
            $size = filesize($fullPath);
            $sizeFormatted = $size > 1024 * 1024 ? 
                round($size / (1024 * 1024), 2) . ' MB' : 
                round($size / 1024, 2) . ' KB';
            
            $mtime = date('Y-m-d H:i:s', filemtime($fullPath));
            
            echo "<tr>";
            echo "<td><code>{$file}</code><br><small>{$desc}</small></td>";
            echo "<td><code style='font-size:11px;'>" . htmlspecialchars($realPath) . "</code></td>";
            echo "<td><span class='status on'>存在</span></td>";
            echo "<td>{$sizeFormatted}</td>";
            echo "<td>{$mtime}</td>";
            echo "</tr>";
        } else {
            // 即使文件不存在，也显示完整路径
            $expectedPath = str_replace('/', DIRECTORY_SEPARATOR, $fullPath);
            
            echo "<tr>";
            echo "<td><code>{$file}</code><br><small>{$desc}</small></td>";
            echo "<td><code style='font-size:11px;'>" . htmlspecialchars($expectedPath) . "</code></td>";
            echo "<td><span class='status off'>不存在</span></td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    
    echo "</tbody></table>";
    
    if (!$hasLogs) {
        echo "<div class='info-box'>";
        echo "ℹ️ 暂无错误日志文件。这可能是因为：";
        echo "<ul>";
        echo "<li>调试模式未启用</li>";
        echo "<li>错误处理器未初始化</li>";
        echo "<li>还没有发生需要记录的错误</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // 5. 测试错误处理功能
    echo "<h3>5️⃣ 功能测试</h3>";
    
    if (isset($_GET['test']) && $_GET['test'] === 'log') {
        // 测试日志功能
        try {
            // 尝试加载 Z-BlogPHP
            $zbpBase = __DIR__ . '/../../../zb_system/function/c_system_base.php';
            if (file_exists($zbpBase)) {
                require_once $zbpBase;
                $zbp->Load();
                
                // 加载错误处理器
                if (file_exists(__DIR__ . '/lib/error-handler-safe.php')) {
                    require_once __DIR__ . '/lib/error-handler-safe.php';
                    TpureErrorHandler::init();
                    
                    // 写入测试日志
                    $testMessage = '测试日志 - ' . date('Y-m-d H:i:s');
                    $result = tpure_log($testMessage, 'INFO');
                    
                    if ($result) {
                        echo "<div class='success-box'>";
                        echo "✅ <strong>日志测试成功！</strong><br>";
                        echo "测试信息: <code>{$testMessage}</code><br>";
                        echo "请检查日志文件查看记录。";
                        echo "</div>";
                    } else {
                        echo "<div class='warning-box'>";
                        echo "⚠️ <strong>日志写入失败</strong><br>";
                        echo "可能原因：";
                        echo "<ul>";
                        echo "<li>调试模式未启用</li>";
                        echo "<li>日志目录没有写权限</li>";
                        echo "<li>错误处理器未正确初始化</li>";
                        echo "</ul>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error-box'>";
                    echo "❌ 错误处理器文件不存在";
                    echo "</div>";
                }
            } else {
                echo "<div class='error-box'>";
                echo "❌ 无法加载 Z-BlogPHP";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error-box'>";
            echo "❌ <strong>测试失败：</strong>" . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
    } else {
        echo "<p><a href='?test=log' class='btn'>🧪 测试日志功能</a></p>";
        echo "<p class='info-box'>点击按钮测试错误日志功能是否正常工作</p>";
    }
    
    // 6. 快速操作
    echo "<h3>6️⃣ 快速操作</h3>";
    echo "<p>";
    echo "<a href='docs/ERROR-GUIDE.md' class='btn' target='_blank'>📖 查看使用指南</a>";
    echo "<a href='cache-status.php' class='btn'>🔍 缓存诊断</a>";
    echo "<a href='javascript:location.reload()' class='btn'>🔄 刷新检测</a>";
    echo "</p>";
    
    // 7. 建议
    echo "<h3>7️⃣ 使用建议</h3>";
    
    if (!$debugEnabled) {
        echo "<div class='warning-box'>";
        echo "<strong>⚠️ 调试模式未启用</strong><br><br>";
        echo "如需启用错误处理功能，请在 <code>include.php</code> 文件开头添加：<br>";
        echo "<pre>define('TPURE_DEBUG', true);</pre>";
        echo "</div>";
    }
    
    $logsDir = __DIR__ . '/../../logs/';
    if (!is_dir($logsDir) || !is_writable($logsDir)) {
        echo "<div class='warning-box'>";
        echo "<strong>⚠️ 日志目录不可写</strong><br><br>";
        echo "请设置目录权限：<br>";
        echo "<pre>chmod 755 " . realpath($logsDir) . "</pre>";
        echo "</div>";
    }
    
    if ($debugEnabled && is_writable($logsDir)) {
        echo "<div class='success-box'>";
        echo "✅ <strong>错误处理功能已就绪！</strong><br><br>";
        echo "您可以：<br>";
        echo "<ul>";
        echo "<li>使用 <code>tpure_log()</code> 记录日志</li>";
        echo "<li>使用 <code>tpure_try()</code> 安全执行代码</li>";
        echo "<li>查看 <code>zb_users/logs/tpure-error.log</code> 获取错误信息</li>";
        echo "</ul>";
        echo "</div>";
    }
    ?>

    <hr style="margin: 40px 0;">
    <p style="text-align: center; color: #999;">Tpure 主题 · 错误处理诊断工具</p>
</div>
</body>
</html>

