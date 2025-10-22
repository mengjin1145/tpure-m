<?php
/**
 * AdvancedStats 插件指纹检测分析工具
 * 
 * 功能：分析插件收集的设备指纹信息
 * 
 * 使用方法：
 * 访问：https://你的域名/zb_users/theme/tpure/check-advancedstats-fingerprint.php
 */

// 检测是否已安装 Z-BlogPHP
$zbpPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/zb_system/function/c_system_base.php';

if (!file_exists($zbpPath)) {
    die('❌ 错误：未找到 Z-BlogPHP 系统文件');
}

// 加载 Z-BlogPHP
require $zbpPath;

$zbp = new ZBlogPHP();
$zbp->Load();

header('Content-Type: text/html; charset=utf-8');

// 检查插件是否存在
$pluginDir = ZBP_PATH . 'zb_users/plugin/AdvancedStats/';
$pluginExists = is_dir($pluginDir);

// 分析 JavaScript 文件
$jsFiles = array();
$fingerprints = array();

if ($pluginExists) {
    // 查找所有 JS 文件
    $files = glob($pluginDir . '*.js');
    foreach ($files as $file) {
        $jsFiles[] = array(
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'content' => file_get_contents($file)
        );
    }
    
    // 分析指纹收集项
    foreach ($jsFiles as $js) {
        $content = $js['content'];
        
        // 检测常见的指纹收集方法
        $patterns = array(
            'screen.width' => '屏幕宽度',
            'screen.height' => '屏幕高度',
            'window.innerWidth' => '窗口内宽度',
            'window.innerHeight' => '窗口内高度',
            'navigator.userAgent' => '浏览器标识（User-Agent）',
            'navigator.platform' => '操作系统平台',
            'navigator.language' => '浏览器语言',
            'navigator.languages' => '浏览器语言列表',
            'navigator.plugins' => '浏览器插件列表',
            'navigator.mimeTypes' => '支持的 MIME 类型',
            'navigator.hardwareConcurrency' => 'CPU 核心数',
            'navigator.deviceMemory' => '设备内存',
            'navigator.maxTouchPoints' => '触摸点数量',
            'navigator.vendor' => '浏览器厂商',
            'navigator.connection' => '网络连接信息',
            'screen.colorDepth' => '屏幕色深',
            'screen.pixelDepth' => '像素深度',
            'window.devicePixelRatio' => '设备像素比（DPR）',
            'Date().getTimezoneOffset' => '时区偏移',
            'canvas' => 'Canvas 指纹',
            'WebGL' => 'WebGL 指纹',
            'AudioContext' => 'Audio 指纹',
            'localStorage' => '本地存储',
            'sessionStorage' => '会话存储',
            'IndexedDB' => 'IndexedDB',
            'cookie' => 'Cookie',
            'navigator.getBattery' => '电池信息',
            'navigator.geolocation' => '地理位置',
            'Notification' => '通知权限',
            'MediaDevices' => '媒体设备',
        );
        
        foreach ($patterns as $pattern => $description) {
            if (stripos($content, $pattern) !== false) {
                if (!isset($fingerprints[$description])) {
                    $fingerprints[$description] = array(
                        'pattern' => $pattern,
                        'files' => array()
                    );
                }
                $fingerprints[$description]['files'][] = $js['name'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🔍 AdvancedStats 指纹检测分析</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
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
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .fingerprint-item {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .fingerprint-name {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .fingerprint-pattern {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 14px;
        }
        .fingerprint-files {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .risk-level {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .risk-high {
            background: #dc3545;
            color: white;
        }
        .risk-medium {
            background: #ffc107;
            color: #333;
        }
        .risk-low {
            background: #28a745;
            color: white;
        }
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin-bottom: 10px;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 AdvancedStats 指纹检测分析</h1>
            <p>分析插件收集的设备指纹和用户信息</p>
        </div>
        
        <div class="content">
            <?php if (!$pluginExists): ?>
                <div class="status error">
                    ❌ <strong>AdvancedStats 插件未安装</strong>
                </div>
            <?php else: ?>
                <div class="status success">
                    ✅ <strong>已找到 AdvancedStats 插件</strong>
                </div>
                
                <!-- 概览 -->
                <div class="summary">
                    <div class="summary-card">
                        <h3><?php echo count($jsFiles); ?></h3>
                        <p>JavaScript 文件</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo count($fingerprints); ?></h3>
                        <p>检测到的指纹项</p>
                    </div>
                </div>
                
                <!-- 收集的指纹信息 -->
                <div class="section">
                    <h2>📊 检测到的设备指纹信息</h2>
                    
                    <?php if (empty($fingerprints)): ?>
                        <div class="status success">
                            ✅ <strong>未检测到明显的指纹收集代码</strong>
                        </div>
                    <?php else: ?>
                        <?php foreach ($fingerprints as $name => $info): ?>
                        <div class="fingerprint-item">
                            <div class="fingerprint-name">
                                <?php echo htmlspecialchars($name); ?>
                                <?php
                                // 风险等级判断
                                $risk = 'low';
                                $highRisk = array('Canvas 指纹', 'WebGL 指纹', 'Audio 指纹', '地理位置', '媒体设备', '电池信息');
                                $mediumRisk = array('浏览器插件列表', 'CPU 核心数', '设备内存', '网络连接信息');
                                
                                if (in_array($name, $highRisk)) {
                                    $risk = 'high';
                                } elseif (in_array($name, $mediumRisk)) {
                                    $risk = 'medium';
                                }
                                ?>
                                <span class="risk-level risk-<?php echo $risk; ?>">
                                    <?php echo $risk === 'high' ? '高风险' : ($risk === 'medium' ? '中风险' : '低风险'); ?>
                                </span>
                            </div>
                            <div class="fingerprint-pattern">
                                JavaScript API: <code><?php echo htmlspecialchars($info['pattern']); ?></code>
                            </div>
                            <div class="fingerprint-files">
                                📄 使用文件: <?php echo implode(', ', $info['files']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- JavaScript 文件列表 -->
                <div class="section">
                    <h2>📁 JavaScript 文件</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>大小</th>
                                <th>路径</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jsFiles as $js): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($js['name']); ?></strong></td>
                                <td><?php echo number_format($js['size']); ?> bytes</td>
                                <td style="font-family: monospace; font-size: 12px;">
                                    <?php echo htmlspecialchars(str_replace(ZBP_PATH, '', $js['path'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 隐私说明 -->
                <div class="info-box">
                    <h3>🛡️ 设备指纹用途说明</h3>
                    <p><strong>设备类型检测：</strong></p>
                    <ul style="margin: 10px 0 10px 20px;">
                        <li><strong>屏幕尺寸</strong> (screen.width/height) - 判断是手机/平板/电脑</li>
                        <li><strong>触摸点数</strong> (maxTouchPoints) - 区分触屏设备和电脑</li>
                        <li><strong>设备像素比</strong> (devicePixelRatio) - 检测高清屏幕</li>
                        <li><strong>User-Agent</strong> - 识别浏览器和操作系统</li>
                    </ul>
                    <p style="margin-top: 15px;"><strong>合法用途：</strong></p>
                    <ul style="margin: 10px 0 10px 20px;">
                        <li>✅ 统计网站访客的设备类型分布</li>
                        <li>✅ 优化不同设备的显示效果</li>
                        <li>✅ 防止恶意刷访问量（指纹去重）</li>
                    </ul>
                    <p style="margin-top: 15px;"><strong>⚠️ 隐私风险：</strong></p>
                    <ul style="margin: 10px 0 10px 20px;">
                        <li>⚠️ Canvas/WebGL 指纹可用于跨站追踪</li>
                        <li>⚠️ 设备指纹可能泄露硬件信息</li>
                        <li>⚠️ 组合多个指纹可实现精准识别</li>
                    </ul>
                </div>
                
                <!-- 实际检测演示 -->
                <div class="section">
                    <h2>🎯 当前设备指纹信息（演示）</h2>
                    <p style="margin-bottom: 15px; color: #666;">
                        以下是您的浏览器当前的设备信息（仅本地显示，不会上传）：
                    </p>
                    <div id="fingerprint-demo" style="background: white; padding: 15px; border-radius: 8px;">
                        <p>正在检测...</p>
                    </div>
                </div>
                
            <?php endif; ?>
            
            <div class="status warning">
                <div>
                    <strong>💡 建议：</strong><br>
                    1. 设备指纹用于统计分析是合理的，但应告知用户<br>
                    2. 不应收集敏感信息（地理位置、摄像头、麦克风）<br>
                    3. 定期清理过期的统计数据<br>
                    4. 遵守 GDPR、CCPA 等隐私法规
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // 演示设备指纹检测
    (function() {
        const demo = document.getElementById('fingerprint-demo');
        if (!demo) return;
        
        const info = {
            '设备类型': /Mobile|Android|iPhone|iPad/i.test(navigator.userAgent) ? '移动设备' : '桌面设备',
            '屏幕分辨率': `${screen.width} × ${screen.height}`,
            '窗口尺寸': `${window.innerWidth} × ${window.innerHeight}`,
            '设备像素比': window.devicePixelRatio || 1,
            '色深': screen.colorDepth + ' bit',
            '触摸支持': navigator.maxTouchPoints > 0 ? '是 (' + navigator.maxTouchPoints + ' 点)' : '否',
            'CPU 核心': navigator.hardwareConcurrency || '未知',
            '浏览器语言': navigator.language,
            '操作系统': navigator.platform,
            '浏览器': navigator.userAgent.split(' ').pop().split('/')[0],
            '时区偏移': new Date().getTimezoneOffset() / 60 + ' 小时'
        };
        
        let html = '<table style="width: 100%; border-collapse: collapse;">';
        for (let key in info) {
            html += `<tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">${key}</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">${info[key]}</td>
            </tr>`;
        }
        html += '</table>';
        
        demo.innerHTML = html;
    })();
    </script>
</body>
</html>

