<?php
/**
 * 简单文件检查工具 - 不依赖Z-BlogPHP
 * 检查修复文件是否已正确上传
 */

header('Content-Type: text/html; charset=utf-8');

// 设置主题目录
$theme_dir = dirname(__FILE__);

// 需要检查的文件列表
$files_to_check = array(
    'lib/functions-missing.php' => '遗漏函数补丁（最重要！）',
    'lib/helpers.php' => '辅助函数库',
    'lib/functions-core.php' => '核心函数库',
    'lib/theme-admin.php' => '主题管理函数',
    'lib/ajax.php' => 'Ajax处理',
    'include.php' => '主题入口文件',
    'main.php' => '主题配置页面',
);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件检查工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
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
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0188fb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        .file-check {
            margin: 10px 0;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .file-check.exists {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .file-check.missing {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .file-info {
            flex: 1;
        }
        .file-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        .file-desc {
            font-size: 13px;
            color: #666;
        }
        .file-status {
            font-weight: bold;
            font-size: 16px;
        }
        .exists .file-status {
            color: #28a745;
        }
        .missing .file-status {
            color: #dc3545;
        }
        .summary {
            margin: 30px 0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        .summary.success {
            background: #d4edda;
            color: #155724;
        }
        .summary.error {
            background: #f8d7da;
            color: #721c24;
        }
        .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 13px;
        }
        .details h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: "Courier New", monospace;
            font-size: 12px;
            margin: 10px 0;
        }
        .action-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .action-box h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .action-box ol {
            margin-left: 20px;
            color: #856404;
        }
        .action-box li {
            margin: 8px 0;
            line-height: 1.6;
        }
        .btn-group {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 5px;
            background: #0188fb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #0170d9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 文件检查工具</h1>
        <p class="subtitle">Tpure主题修复 - 文件完整性检查</p>

        <div class="info-box">
            <strong>主题目录：</strong><?php echo htmlspecialchars($theme_dir); ?><br>
            <strong>检查时间：</strong><?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>服务器：</strong><?php echo php_uname(); ?>
        </div>

        <h2 style="margin: 20px 0 10px; color: #333;">文件检查结果：</h2>

        <?php
        $missing_count = 0;
        $exists_count = 0;
        $missing_files = array();

        foreach ($files_to_check as $file => $desc) {
            $full_path = $theme_dir . '/' . $file;
            $exists = file_exists($full_path);
            
            if ($exists) {
                $exists_count++;
                $file_size = filesize($full_path);
                $file_size_kb = round($file_size / 1024, 2);
                
                echo '<div class="file-check exists">';
                echo '<div class="file-info">';
                echo '<div class="file-name">✓ ' . htmlspecialchars($file) . '</div>';
                echo '<div class="file-desc">' . htmlspecialchars($desc) . ' - 大小: ' . $file_size_kb . ' KB</div>';
                echo '</div>';
                echo '<div class="file-status">存在</div>';
                echo '</div>';
            } else {
                $missing_count++;
                $missing_files[] = $file;
                
                echo '<div class="file-check missing">';
                echo '<div class="file-info">';
                echo '<div class="file-name">✗ ' . htmlspecialchars($file) . '</div>';
                echo '<div class="file-desc">' . htmlspecialchars($desc) . '</div>';
                echo '</div>';
                echo '<div class="file-status">缺失！</div>';
                echo '</div>';
            }
        }
        ?>

        <div class="summary <?php echo $missing_count > 0 ? 'error' : 'success'; ?>">
            检查完成：共 <?php echo count($files_to_check); ?> 个文件，
            <span style="color: #28a745;">存在 <?php echo $exists_count; ?> 个</span>，
            <span style="color: #dc3545;">缺失 <?php echo $missing_count; ?> 个</span>
        </div>

        <?php if ($missing_count > 0): ?>
            <div class="action-box">
                <h3>⚠️ 需要立即上传以下文件：</h3>
                <ol>
                    <?php foreach ($missing_files as $file): ?>
                        <li><strong><?php echo htmlspecialchars($file); ?></strong> - <?php echo htmlspecialchars($files_to_check[$file]); ?></li>
                    <?php endforeach; ?>
                </ol>
                <p style="margin-top: 15px; color: #856404;">
                    <strong>上传路径：</strong><br>
                    <code style="background: #fff; padding: 5px; border-radius: 3px;">
                        /www/wwwroot/www.dcyzq.cn/zb_users/theme/tpure/
                    </code>
                </p>
            </div>

            <div class="details">
                <h3>📝 上传步骤：</h3>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li>使用FTP或宝塔面板文件管理器</li>
                    <li>定位到 <code>/www/wwwroot/www.dcyzq.cn/zb_users/theme/tpure/</code></li>
                    <li>上传缺失的文件到对应目录</li>
                    <li>刷新本页面重新检查</li>
                </ol>
            </div>

        <?php else: ?>
            <div class="info-box" style="background: #d4edda; border-color: #28a745;">
                <h3 style="color: #155724; margin-bottom: 10px;">✅ 所有文件都已正确上传！</h3>
                <p style="color: #155724;">接下来的步骤：</p>
                <ol style="margin-left: 20px; margin-top: 10px; color: #155724;">
                    <li>清除服务器缓存：删除 <code>zb_users/cache/compiled/tpure/</code> 目录下所有文件</li>
                    <li>访问主题配置页面测试：<a href="main.php?act=base" style="color: #0188fb;">main.php?act=base</a></li>
                    <li>如果还有问题，查看错误日志</li>
                </ol>
            </div>

            <?php
            // 检查include.php中是否包含functions-missing.php的加载
            $include_content = file_get_contents($theme_dir . '/include.php');
            $has_missing_php = strpos($include_content, 'functions-missing.php') !== false;
            ?>

            <div class="details">
                <h3>🔍 include.php 配置检查：</h3>
                <?php if ($has_missing_php): ?>
                    <div style="color: #28a745; margin-top: 10px;">
                        ✓ include.php 已正确配置加载 functions-missing.php
                    </div>
                <?php else: ?>
                    <div style="color: #dc3545; margin-top: 10px;">
                        ✗ include.php 中未找到 functions-missing.php 的加载！<br>
                        <span style="font-size: 13px;">请确保第245行包含：<code>'lib/functions-missing.php'</code></span>
                    </div>
                <?php endif; ?>

                <?php
                // 检查TPURE_DIR常量定义
                $has_tpure_dir = strpos($include_content, 'TPURE_DIR') !== false;
                ?>
                <div style="margin-top: 10px;">
                    <?php if ($has_tpure_dir): ?>
                        <div style="color: #28a745;">✓ TPURE_DIR 常量已定义</div>
                    <?php else: ?>
                        <div style="color: #dc3545;">✗ TPURE_DIR 常量未定义（需要修复）</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="details">
            <h3>🛠️ 清除缓存命令：</h3>
            <p style="margin-bottom: 10px;">通过SSH执行：</p>
            <div class="code-block">rm -rf /www/wwwroot/www.dcyzq.cn/zb_users/cache/compiled/tpure/*</div>
            <p style="margin-top: 10px;">或通过宝塔面板：文件 → 定位到该目录 → 删除所有.php文件</p>
        </div>

        <div class="btn-group">
            <?php if ($missing_count == 0): ?>
                <a href="main.php?act=base" class="btn">访问主题配置</a>
            <?php endif; ?>
            <a href="javascript:location.reload()" class="btn btn-secondary">刷新检查</a>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 14px;">
            <p>Tpure主题 v5.12 Turbo | 文件检查工具 v1.0</p>
        </div>
    </div>
</body>
</html>

