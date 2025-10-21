<?php
/**
 * 修复登录页面显示问题
 * 
 * 问题原因：主题的全页面缓存逻辑可能影响了后台登录页面
 * 解决方案：在include.php中添加登录页面的排除判断
 * 
 * 使用方法：在浏览器中访问此文件，按照提示操作
 */

// 设置字符编码
header('Content-Type: text/html; charset=utf-8');

$themeDir = dirname(__FILE__);
$includeFile = $themeDir . '/include.php';
$backupFile = $themeDir . '/include.php.login-fix-backup-' . date('Y-m-d-His');

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修复登录页面 - Tpure主题</title>
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
            max-width: 800px;
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
        .status {
            background: #f8f9fa;
            border-left: 4px solid #0188fb;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .status h3 {
            color: #0188fb;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .status-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        .problem {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .problem h3 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .problem ul {
            margin-left: 20px;
            color: #856404;
        }
        .problem li {
            margin: 8px 0;
            line-height: 1.6;
        }
        .solution {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .solution h3 {
            color: #155724;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .solution ol {
            margin-left: 20px;
            color: #155724;
        }
        .solution li {
            margin: 8px 0;
            line-height: 1.8;
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
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: "Courier New", monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .manual-fix {
            background: #e7f3ff;
            border-left: 4px solid #0188fb;
            padding: 20px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .manual-fix h3 {
            color: #0188fb;
            margin-bottom: 15px;
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
        .note strong {
            color: #dc3545;
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
        <h1>🔧 登录页面修复工具</h1>
        <p class="subtitle">Tpure主题 - 登录页面显示问题诊断与修复</p>

        <div class="status">
            <h3>📊 当前状态检测</h3>
            <div class="status-item">
                <span class="status-label">include.php:</span>
                <?php if (file_exists($includeFile)): ?>
                    <span class="status-ok">✓ 文件存在</span>
                    <span style="color: #666; margin-left: 10px;">
                        (大小: <?php echo number_format(filesize($includeFile)); ?> 字节)
                    </span>
                <?php else: ?>
                    <span class="status-error">✗ 文件不存在</span>
                <?php endif; ?>
            </div>
            <div class="status-item">
                <span class="status-label">文件权限:</span>
                <?php if (is_writable($includeFile)): ?>
                    <span class="status-ok">✓ 可写</span>
                <?php else: ?>
                    <span class="status-error">✗ 不可写（需要手动修复）</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="problem">
            <h3>⚠️ 问题分析</h3>
            <ul>
                <li><strong>症状：</strong>访问登录页面时，看不到用户名和密码输入框</li>
                <li><strong>原因：</strong>主题的全页面缓存代码可能在登录页面执行时产生了影响</li>
                <li><strong>位置：</strong>include.php 第44行的全页面缓存判断逻辑</li>
                <li><strong>影响：</strong>登录页面的表单元素可能被缓存逻辑干扰或隐藏</li>
            </ul>
        </div>

        <div class="solution">
            <h3>✅ 解决方案</h3>
            <p style="margin-bottom: 15px; color: #155724;">需要在 <code>include.php</code> 中添加对登录页面的明确排除：</p>
            
            <div class="note">
                <strong>修改位置：</strong>include.php 第44行附近，找到：
                <div class="code-block">if (!defined('ZBP_IN_ADMIN') && !isset($_COOKIE['username']) && $_SERVER['REQUEST_METHOD'] === 'GET') {</div>
                
                <strong>修改为：</strong>
                <div class="code-block">// 排除登录页面、后台页面和已登录用户
$isLoginPage = (strpos($_SERVER['REQUEST_URI'], '/zb_system/login.php') !== false);
if (!defined('ZBP_IN_ADMIN') && !isset($_COOKIE['username']) && $_SERVER['REQUEST_METHOD'] === 'GET' && !$isLoginPage) {</div>
            </div>
        </div>

        <div class="manual-fix">
            <h3>📝 手动修复步骤</h3>
            <ol>
                <li><strong>备份文件：</strong>复制 <code>include.php</code> 文件并重命名为 <code>include.php.backup</code></li>
                <li><strong>打开文件：</strong>使用文本编辑器（如Notepad++）打开 <code>include.php</code></li>
                <li><strong>找到第44行：</strong>搜索 <code>!defined('ZBP_IN_ADMIN')</code></li>
                <li><strong>在第44行前添加：</strong>
                    <div class="code-block">// 🔧 修复：排除登录页面，避免全页面缓存影响登录表单显示
$isLoginPage = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/zb_system/login.php') !== false);</div>
                </li>
                <li><strong>修改第44行：</strong>在条件末尾添加 <code>&& !$isLoginPage</code>，完整代码为：
                    <div class="code-block">if (!defined('ZBP_IN_ADMIN') && !isset($_COOKIE['username']) && $_SERVER['REQUEST_METHOD'] === 'GET' && !$isLoginPage) {</div>
                </li>
                <li><strong>保存文件：</strong>确保使用 UTF-8 无BOM 编码保存</li>
                <li><strong>测试：</strong>清除浏览器缓存后访问登录页面</li>
            </ol>
        </div>

        <div class="note">
            <strong>⚠️ 重要提示：</strong><br>
            1. 修改前务必备份原文件<br>
            2. 如果修改后网站报错，请立即恢复备份文件<br>
            3. 建议在测试环境先测试修改效果<br>
            4. 修复后清除浏览器缓存再测试登录页面
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../../../zb_system/login.php" class="btn btn-success" target="_blank">测试登录页面</a>
            <a href="javascript:location.reload()" class="btn btn-secondary">刷新此页面</a>
        </div>

        <div class="footer">
            <p>Tpure主题 v5.12 | 登录页面修复工具</p>
            <p style="margin-top: 5px; font-size: 12px;">如有问题，请联系主题开发者或查看文档</p>
        </div>
    </div>
</body>
</html>

