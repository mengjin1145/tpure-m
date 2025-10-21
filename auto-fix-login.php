<?php
/**
 * 自动修复登录页面问题
 * 
 * 此脚本会自动修改 include.php，添加登录页面排除逻辑
 */

header('Content-Type: text/html; charset=utf-8');

$themeDir = dirname(__FILE__);
$includeFile = $themeDir . '/include.php';
$backupFile = $themeDir . '/include.php.before-login-fix-' . date('YmdHis');

$success = false;
$message = '';
$error = '';

// 执行修复
if (isset($_POST['do_fix']) && $_POST['do_fix'] === 'yes') {
    
    if (!file_exists($includeFile)) {
        $error = 'include.php 文件不存在！';
    } elseif (!is_readable($includeFile)) {
        $error = 'include.php 文件不可读！';
    } elseif (!is_writable($includeFile)) {
        $error = 'include.php 文件不可写！请检查文件权限。';
    } else {
        // 读取原文件
        $content = file_get_contents($includeFile);
        
        // 检查是否已经修复过
        if (strpos($content, '$isLoginPage') !== false) {
            $message = '✓ 检测到已经修复过，无需重复修复。';
            $success = true;
        } else {
            // 备份原文件
            if (copy($includeFile, $backupFile)) {
                $message .= "✓ 已备份原文件到: " . basename($backupFile) . "<br>";
                
                // 查找并替换
                $search = "if (!defined('ZBP_IN_ADMIN') && !isset(\$_COOKIE['username']) && \$_SERVER['REQUEST_METHOD'] === 'GET') {";
                
                $replace = "// 🔧 修复：排除登录页面，避免全页面缓存影响登录表单显示\n" .
                          "\$isLoginPage = (isset(\$_SERVER['REQUEST_URI']) && strpos(\$_SERVER['REQUEST_URI'], '/zb_system/login.php') !== false);\n" .
                          "if (!defined('ZBP_IN_ADMIN') && !isset(\$_COOKIE['username']) && \$_SERVER['REQUEST_METHOD'] === 'GET' && !\$isLoginPage) {";
                
                if (strpos($content, $search) !== false) {
                    $newContent = str_replace($search, $replace, $content);
                    
                    if (file_put_contents($includeFile, $newContent)) {
                        $success = true;
                        $message .= "✓ 已成功修复 include.php 文件！<br>";
                        $message .= "✓ 现在登录页面应该可以正常显示了。";
                    } else {
                        $error = '写入文件失败！请检查文件权限。';
                    }
                } else {
                    $error = '未找到需要修改的代码位置。include.php 文件可能已被修改。';
                }
            } else {
                $error = '备份文件失败！请检查目录权限。';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>自动修复登录页面 - Tpure主题</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 700px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .alert {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            line-height: 1.8;
        }
        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #0188fb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 10px 10px 0;
        }
        .btn:hover {
            background: #0170d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1,136,251,0.4);
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .status-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .status-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .status-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .status-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 140px;
        }
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        .button-group {
            text-align: center;
            margin-top: 30px;
        }
        .note {
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 自动修复登录页面</h1>
        <p class="subtitle">一键修复Tpure主题登录页面显示问题</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <h3 style="margin-bottom: 10px;">✅ 修复成功！</h3>
                <?php echo $message; ?>
            </div>
            
            <div class="note">
                <strong>后续步骤：</strong><br>
                1. 清除浏览器缓存（按 Ctrl+Shift+Delete）<br>
                2. 访问登录页面测试是否可以看到输入框<br>
                3. 如果还有问题，请查看备份文件并手动回滚
            </div>
            
            <div class="button-group">
                <a href="../../../zb_system/login.php" class="btn btn-success" target="_blank">测试登录页面</a>
                <a href="javascript:location.reload()" class="btn btn-secondary">返回</a>
            </div>
            
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <h3 style="margin-bottom: 10px;">❌ 修复失败</h3>
                <?php echo $error; ?>
            </div>
            
            <div class="alert alert-info">
                <h3 style="margin-bottom: 10px;">💡 解决建议</h3>
                1. 检查 include.php 文件权限（需要可写）<br>
                2. 使用 <a href="fix-login-page.php" style="color: #0188fb;">手动修复工具</a> 查看详细说明<br>
                3. 或联系主题开发者获取帮助
            </div>
            
            <div class="button-group">
                <a href="fix-login-page.php" class="btn btn-success">查看手动修复方法</a>
                <a href="javascript:location.reload()" class="btn btn-secondary">重试</a>
            </div>
            
        <?php else: ?>
            
            <div class="status-box">
                <h3>📊 系统检测</h3>
                <div class="status-item">
                    <span class="status-label">include.php:</span>
                    <?php if (file_exists($includeFile)): ?>
                        <span class="status-ok">✓ 存在</span>
                    <?php else: ?>
                        <span class="status-error">✗ 不存在</span>
                    <?php endif; ?>
                </div>
                <div class="status-item">
                    <span class="status-label">文件可读:</span>
                    <?php if (is_readable($includeFile)): ?>
                        <span class="status-ok">✓ 是</span>
                    <?php else: ?>
                        <span class="status-error">✗ 否</span>
                    <?php endif; ?>
                </div>
                <div class="status-item">
                    <span class="status-label">文件可写:</span>
                    <?php if (is_writable($includeFile)): ?>
                        <span class="status-ok">✓ 是</span>
                    <?php else: ?>
                        <span class="status-error">✗ 否（需要修改权限）</span>
                    <?php endif; ?>
                </div>
                <div class="status-item">
                    <span class="status-label">文件大小:</span>
                    <?php echo number_format(filesize($includeFile)); ?> 字节
                </div>
            </div>

            <div class="alert alert-warning">
                <h3 style="margin-bottom: 10px;">⚠️ 修复说明</h3>
                <p>此工具将自动修改 <strong>include.php</strong> 文件，添加登录页面排除逻辑。</p>
                <p style="margin-top: 10px;">修复前会自动备份原文件，如有问题可随时恢复。</p>
            </div>

            <div class="note">
                <strong>修复内容：</strong><br>
                在全页面缓存判断前添加登录页面检测，确保登录页面不受缓存影响。
            </div>

            <?php if (!is_writable($includeFile)): ?>
                <div class="alert alert-error">
                    <strong>⚠️ 警告：</strong>include.php 文件不可写！<br>
                    请先修改文件权限后再执行自动修复，或使用<a href="fix-login-page.php" style="color: #721c24; text-decoration: underline;">手动修复方法</a>。
                </div>
            <?php endif; ?>

            <form method="post" style="text-align: center;">
                <div class="button-group">
                    <?php if (is_writable($includeFile)): ?>
                        <button type="submit" name="do_fix" value="yes" class="btn btn-success">
                            🚀 开始自动修复
                        </button>
                    <?php endif; ?>
                    <a href="fix-login-page.php" class="btn btn-secondary">查看手动修复方法</a>
                </div>
            </form>
            
        <?php endif; ?>

        <div class="footer">
            <p>Tpure主题 v5.12 | 登录页面自动修复工具</p>
        </div>
    </div>
</body>
</html>


