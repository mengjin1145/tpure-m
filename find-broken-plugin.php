<?php
/**
 * Tpure 主题 - 查找损坏的插件 XML
 * 
 * 功能：扫描所有插件的 plugin.xml，找出格式错误的文件
 * 
 * 使用方法：
 * 1. 上传到主题目录：zb_users/theme/tpure/
 * 2. 访问：https://你的域名/zb_users/theme/tpure/find-broken-plugin.php
 * 3. 查看结果，禁用或修复损坏的插件
 */

// 检测是否已安装 Z-BlogPHP
$zbpPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/zb_system/function/c_system_base.php';

if (!file_exists($zbpPath)) {
    die('❌ 错误：未找到 Z-BlogPHP 系统文件，请确认文件路径是否正确。');
}

// 加载 Z-BlogPHP（c_system_base.php 会自动加载所有依赖）
require $zbpPath;

// 初始化
$zbp = new ZBlogPHP();
$zbp->Load();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🔍 Tpure - 查找损坏的插件</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .status {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .plugin-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .plugin-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .plugin-card.broken {
            border-color: #dc3545;
            background: #fff5f5;
        }
        .plugin-card.ok {
            border-color: #28a745;
            background: #f8fff8;
        }
        .plugin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .plugin-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .plugin-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .plugin-status.ok {
            background: #28a745;
            color: white;
        }
        .plugin-status.broken {
            background: #dc3545;
            color: white;
        }
        .plugin-info {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        .plugin-path {
            color: #999;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-top: 10px;
            word-break: break-all;
        }
        .error-detail {
            background: #fff;
            border-left: 4px solid #dc3545;
            padding: 12px;
            margin-top: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #721c24;
        }
        .fix-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .fix-button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .summary-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .progress {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 查找损坏的插件 XML</h1>
            <p>扫描所有插件的 plugin.xml 文件，找出格式错误的文件</p>
        </div>
        
        <div class="content">
            <?php
            $pluginDir = ZBP_PATH . 'zb_users/plugin/';
            
            if (!is_dir($pluginDir)) {
                echo '<div class="status error">❌ 错误：插件目录不存在：' . $pluginDir . '</div>';
                exit;
            }
            
            // 扫描所有插件
            $plugins = array();
            $brokenPlugins = array();
            $okPlugins = array();
            
            $dirs = scandir($pluginDir);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                // 跳过文件（只处理目录）
                $fullPath = $pluginDir . $dir;
                if (!is_dir($fullPath)) {
                    continue;
                }
                
                // 跳过压缩包（.tar.gz, .zip 等）
                if (preg_match('/\.(tar\.gz|zip|rar|7z)$/i', $dir)) {
                    continue;
                }
                
                $xmlPath = $fullPath . '/plugin.xml';
                
                if (!file_exists($xmlPath)) {
                    continue;
                }
                
                $pluginInfo = array(
                    'dir' => $dir,
                    'path' => $xmlPath,
                    'size' => filesize($xmlPath),
                    'ok' => false,
                    'error' => ''
                );
                
                // 尝试解析 XML
                libxml_use_internal_errors(true);
                $xmlContent = file_get_contents($xmlPath);
                $xml = simplexml_load_string($xmlContent);
                
                if ($xml === false) {
                    $pluginInfo['ok'] = false;
                    $errors = libxml_get_errors();
                    $errorMessages = array();
                    foreach ($errors as $error) {
                        $errorMessages[] = "Line {$error->line}: {$error->message}";
                    }
                    $pluginInfo['error'] = implode("\n", $errorMessages);
                    libxml_clear_errors();
                    $brokenPlugins[] = $pluginInfo;
                } else {
                    $pluginInfo['ok'] = true;
                    $pluginInfo['name'] = (string)$xml->name;
                    $pluginInfo['version'] = (string)$xml->version;
                    $pluginInfo['author'] = (string)$xml->author->name;
                    $okPlugins[] = $pluginInfo;
                }
                
                $plugins[] = $pluginInfo;
            }
            
            $totalPlugins = count($plugins);
            $brokenCount = count($brokenPlugins);
            $okCount = count($okPlugins);
            ?>
            
            <!-- 统计概览 -->
            <div class="summary">
                <div class="summary-card">
                    <h3><?php echo $totalPlugins; ?></h3>
                    <p>总插件数</p>
                </div>
                <div class="summary-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3><?php echo $okCount; ?></h3>
                    <p>正常插件</p>
                </div>
                <div class="summary-card" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);">
                    <h3><?php echo $brokenCount; ?></h3>
                    <p>损坏插件</p>
                </div>
            </div>
            
            <!-- 进度条 -->
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $totalPlugins > 0 ? ($okCount / $totalPlugins * 100) : 0; ?>%;"></div>
            </div>
            
            <?php if ($brokenCount > 0): ?>
                <div class="status error">
                    ❌ <strong>发现 <?php echo $brokenCount; ?> 个损坏的插件！</strong> 请禁用或修复它们。
                </div>
                
                <h2 style="margin: 30px 0 15px 0; color: #dc3545;">🔴 损坏的插件</h2>
                
                <?php foreach ($brokenPlugins as $plugin): ?>
                <div class="plugin-card broken">
                    <div class="plugin-header">
                        <div class="plugin-name">📦 <?php echo htmlspecialchars($plugin['dir']); ?></div>
                        <span class="plugin-status broken">XML 错误</span>
                    </div>
                    
                    <div class="plugin-info">
                        📂 目录：<?php echo htmlspecialchars($plugin['dir']); ?>
                    </div>
                    
                    <div class="plugin-info">
                        📄 文件大小：<?php echo number_format($plugin['size']); ?> bytes
                    </div>
                    
                    <div class="plugin-path">
                        <?php echo htmlspecialchars($plugin['path']); ?>
                    </div>
                    
                    <div class="error-detail">
                        <strong>错误详情：</strong><br>
                        <?php echo nl2br(htmlspecialchars($plugin['error'])); ?>
                    </div>
                    
                    <a href="<?php echo $zbp->host; ?>zb_system/admin/index.php?act=PluginMng" class="fix-button" target="_blank">
                        🔧 去插件管理禁用
                    </a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="status success">
                    ✅ <strong>太好了！所有插件 XML 格式都正常。</strong>
                </div>
            <?php endif; ?>
            
            <?php if ($okCount > 0): ?>
                <h2 style="margin: 30px 0 15px 0; color: #28a745;">✅ 正常的插件</h2>
                
                <?php foreach ($okPlugins as $plugin): ?>
                <div class="plugin-card ok">
                    <div class="plugin-header">
                        <div class="plugin-name">📦 <?php echo htmlspecialchars($plugin['name'] ?? $plugin['dir']); ?></div>
                        <span class="plugin-status ok">正常</span>
                    </div>
                    
                    <div class="plugin-info">
                        📂 目录：<?php echo htmlspecialchars($plugin['dir']); ?>
                    </div>
                    
                    <?php if (!empty($plugin['version'])): ?>
                    <div class="plugin-info">
                        🏷️ 版本：<?php echo htmlspecialchars($plugin['version']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($plugin['author'])): ?>
                    <div class="plugin-info">
                        👤 作者：<?php echo htmlspecialchars($plugin['author']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="plugin-path">
                        <?php echo htmlspecialchars($plugin['path']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="status warning" style="margin-top: 30px;">
                <div>
                    <strong>💡 修复建议：</strong><br>
                    1. 禁用损坏的插件（后台 → 插件管理 → 禁用）<br>
                    2. 重新安装或更新插件<br>
                    3. 联系插件作者获取修复版本<br>
                    4. 手动编辑 plugin.xml 修复 XML 格式错误
                </div>
            </div>
        </div>
        
        <div class="footer">
            🛠️ Tpure 主题诊断工具 | 生成时间：<?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
