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

// 确保数据库连接已初始化
if (!isset($zbp->db) || !is_object($zbp->db)) {
    // 尝试手动初始化数据库连接
    try {
        $zbp->OpenConnect();
    } catch (Exception $e) {
        // 数据库连接失败时忽略，继续其他检测
    }
}

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
                
                <!-- 实际检测 -->
                <div class="section">
                    <h2>🔍 实际指纹收集检测</h2>
                    
                    <?php
                    // 检查插件的实际配置和功能
                    $includeFile = $pluginDir . 'include.php';
                    $functionFile = $pluginDir . 'function.php';
                    $mainFile = $pluginDir . 'main.php';
                    
                    $actualCollection = array();
                    
                    // 检查是否有这些文件
                    $pluginFiles = array();
                    if (file_exists($includeFile)) {
                        $pluginFiles['include.php'] = file_get_contents($includeFile);
                    }
                    if (file_exists($functionFile)) {
                        $pluginFiles['function.php'] = file_get_contents($functionFile);
                    }
                    if (file_exists($mainFile)) {
                        $pluginFiles['main.php'] = file_get_contents($mainFile);
                    }
                    
                    // 检测数据库记录内容
                    $dbPatterns = array(
                        'user_agent' => 'User-Agent（浏览器标识）',
                        'device_type' => '设备类型',
                        'screen_width' => '屏幕宽度',
                        'screen_height' => '屏幕高度',
                        'browser_name' => '浏览器名称',
                        'browser_version' => '浏览器版本',
                        'os_name' => '操作系统名称',
                        'os_version' => '操作系统版本',
                        'device_model' => '设备型号',
                        'ip_address' => 'IP地址',
                        'fingerprint' => '设备指纹ID',
                        'canvas' => 'Canvas指纹',
                        'webgl' => 'WebGL指纹',
                        'timezone' => '时区',
                        'language' => '语言',
                        'plugins' => '浏览器插件',
                        'cpu_cores' => 'CPU核心数',
                        'memory' => '设备内存',
                        'touch_support' => '触摸支持',
                        'pixel_ratio' => '设备像素比',
                    );
                    
                    foreach ($pluginFiles as $fileName => $content) {
                        foreach ($dbPatterns as $pattern => $name) {
                            if (stripos($content, $pattern) !== false) {
                                $actualCollection[$name] = array(
                                    'field' => $pattern,
                                    'file' => $fileName
                                );
                            }
                        }
                    }
                    
                    // 检查数据库表
                    $dbTables = array();
                    try {
                        // 安全检查：确保数据库连接已初始化
                        if (isset($zbp->db) && is_object($zbp->db)) {
                            $tables = $zbp->db->Query("SHOW TABLES LIKE '%advanced%'");
                            
                            if ($tables && is_array($tables)) {
                                foreach ($tables as $table) {
                                    $tableName = reset($table);
                                    $dbTables[] = $tableName;
                                    
                                    // 获取表结构
                                    $columns = $zbp->db->Query("SHOW COLUMNS FROM `{$tableName}`");
                                    if ($columns && is_array($columns)) {
                                        foreach ($columns as $col) {
                                            $colName = $col['Field'];
                                            foreach ($dbPatterns as $pattern => $name) {
                                                if (stripos($colName, $pattern) !== false) {
                                                    $actualCollection[$name] = array(
                                                        'field' => $colName,
                                                        'table' => $tableName,
                                                        'type' => $col['Type']
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // 静默失败，不影响其他检测
                        $dbTables = array();
                    }
                    ?>
                    
                    <?php if (empty($actualCollection)): ?>
                        <div class="status warning">
                            ⚠️ <strong>未检测到数据库字段收集</strong> - 可能使用其他方式存储或未启用
                        </div>
                    <?php else: ?>
                        <div class="status error">
                            🔴 <strong>检测到 <?php echo count($actualCollection); ?> 项数据收集</strong>
                        </div>
                        
                        <table style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>收集项</th>
                                    <th>数据库字段/变量</th>
                                    <th>来源</th>
                                    <th>风险</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actualCollection as $name => $info): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                                    <td style="font-family: monospace; font-size: 12px;">
                                        <?php echo htmlspecialchars($info['field']); ?>
                                        <?php if (isset($info['type'])): ?>
                                        <br><span style="color: #999;">(<?php echo $info['type']; ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($info['table'])): ?>
                                            表: <?php echo htmlspecialchars($info['table']); ?>
                                        <?php elseif (isset($info['file'])): ?>
                                            文件: <?php echo htmlspecialchars($info['file']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $risk = 'low';
                                        $highRisk = array('Canvas指纹', 'WebGL指纹', '设备指纹ID');
                                        $mediumRisk = array('浏览器插件', 'CPU核心数', '设备内存', '设备型号');
                                        
                                        if (in_array($name, $highRisk)) {
                                            $risk = 'high';
                                        } elseif (in_array($name, $mediumRisk)) {
                                            $risk = 'medium';
                                        }
                                        ?>
                                        <span class="risk-level risk-<?php echo $risk; ?>">
                                            <?php echo $risk === 'high' ? '高' : ($risk === 'medium' ? '中' : '低'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                    <?php if (!empty($dbTables)): ?>
                        <div style="margin-top: 20px;">
                            <h3 style="margin-bottom: 10px;">📊 数据库表</h3>
                            <div class="fingerprint-pattern">
                                <?php foreach ($dbTables as $table): ?>
                                    <div>✓ <?php echo htmlspecialchars($table); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 收集的指纹信息 -->
                <div class="section">
                    <h2>📊 JavaScript 代码检测</h2>
                    
                    <?php if (empty($fingerprints)): ?>
                        <div class="status success">
                            ✅ <strong>未在 JS 文件中检测到明显的指纹收集代码</strong>
                            <p style="margin-top: 10px; color: #666; font-size: 14px;">
                                这可能意味着：<br>
                                1. 插件未使用 JavaScript 收集设备信息<br>
                                2. 使用了混淆或加密的代码<br>
                                3. 通过服务器端 PHP 收集数据（更隐蔽）
                            </p>
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
                
                <!-- Canvas/WebGL 指纹检测演示 -->
                <div class="section">
                    <h2>🎨 Canvas/WebGL 指纹实时检测</h2>
                    <p style="margin-bottom: 15px; color: #666;">
                        下面演示 Canvas 和 WebGL 指纹是如何生成的（仅本地显示，不会上传）：
                    </p>
                    
                    <!-- Canvas 指纹 -->
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <h3 style="color: #667eea; margin-bottom: 10px;">🖼️ Canvas 指纹</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <p style="font-weight: bold; margin-bottom: 5px;">渲染的图像：</p>
                                <canvas id="canvas-fingerprint" width="300" height="60" style="border: 1px solid #ddd; border-radius: 4px;"></canvas>
                            </div>
                            <div>
                                <p style="font-weight: bold; margin-bottom: 5px;">生成的指纹哈希：</p>
                                <div id="canvas-hash" style="font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px; word-break: break-all; font-size: 12px;">
                                    计算中...
                                </div>
                            </div>
                        </div>
                        <p style="margin-top: 10px; font-size: 14px; color: #666;">
                            <strong>原理：</strong>不同设备渲染相同文字和图形会产生微小差异，转换为哈希后可作为唯一设备ID
                        </p>
                    </div>
                    
                    <!-- WebGL 指纹 -->
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <h3 style="color: #667eea; margin-bottom: 10px;">🎮 WebGL 指纹</h3>
                        <div id="webgl-fingerprint" style="font-family: monospace; font-size: 13px; background: #f8f9fa; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;">
                            检测中...
                        </div>
                        <p style="margin-top: 10px; font-size: 14px; color: #666;">
                            <strong>原理：</strong>读取 GPU 型号、驱动版本、支持的扩展等信息，组合成唯一标识
                        </p>
                    </div>
                    
                    <!-- 风险提示 -->
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px;">
                        <strong>⚠️ 隐私风险：</strong><br>
                        <ul style="margin: 10px 0 0 20px; font-size: 14px;">
                            <li>Canvas/WebGL 指纹在您的设备上几乎是唯一的</li>
                            <li>清除 Cookie、更换浏览器都无法改变指纹</li>
                            <li>网站可以通过指纹跨站追踪您的行为</li>
                            <li>只有更换硬件或使用 Tor 浏览器才能有效防护</li>
                        </ul>
                    </div>
                </div>
                
                <!-- 基础设备信息 -->
                <div class="section">
                    <h2>🎯 基础设备信息</h2>
                    <p style="margin-bottom: 15px; color: #666;">
                        这些是网站通常收集的基础信息（风险较低）：
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
    // ===== Canvas 指纹生成 =====
    (function() {
        const canvas = document.getElementById('canvas-fingerprint');
        const hashDiv = document.getElementById('canvas-hash');
        
        if (!canvas || !hashDiv) return;
        
        const ctx = canvas.getContext('2d');
        
        // 绘制复杂的图形和文字（模拟真实指纹收集）
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        
        ctx.fillStyle = '#069';
        ctx.fillText('Hello, Canvas! 😊', 2, 15);
        
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('你好世界 123', 4, 17);
        
        // 转换为数据URL
        const dataURL = canvas.toDataURL();
        
        // 简单哈希函数（实际使用 MD5 或 SHA256）
        function simpleHash(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash).toString(16);
        }
        
        const hash = simpleHash(dataURL);
        
        hashDiv.innerHTML = `
            <strong>唯一指纹ID：</strong><br>
            <span style="color: #dc3545; font-size: 16px;">${hash}</span><br><br>
            <strong>数据大小：</strong>${(dataURL.length / 1024).toFixed(2)} KB<br>
            <strong>格式：</strong>PNG Base64<br><br>
            <span style="color: #666; font-size: 11px;">
                这个哈希值在你的设备上几乎是唯一的！<br>
                不同的显卡、驱动、操作系统会产生不同的值
            </span>
        `;
    })();
    
    // ===== WebGL 指纹生成 =====
    (function() {
        const webglDiv = document.getElementById('webgl-fingerprint');
        if (!webglDiv) return;
        
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        
        if (!gl) {
            webglDiv.innerHTML = '<span style="color: #dc3545;">❌ 您的浏览器不支持 WebGL</span>';
            return;
        }
        
        // 收集 WebGL 信息
        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        
        const webglData = {
            'GPU 厂商': debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : gl.getParameter(gl.VENDOR),
            'GPU 渲染器': debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER),
            'WebGL 版本': gl.getParameter(gl.VERSION),
            '着色器版本': gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
            '最大纹理尺寸': gl.getParameter(gl.MAX_TEXTURE_SIZE),
            '最大视口尺寸': gl.getParameter(gl.MAX_VIEWPORT_DIMS).join(' × '),
            '最大渲染缓冲': gl.getParameter(gl.MAX_RENDERBUFFER_SIZE),
            '最大顶点属性': gl.getParameter(gl.MAX_VERTEX_ATTRIBS),
            '最大顶点统一向量': gl.getParameter(gl.MAX_VERTEX_UNIFORM_VECTORS),
            '最大片段统一向量': gl.getParameter(gl.MAX_FRAGMENT_UNIFORM_VECTORS),
            '最大纹理单元': gl.getParameter(gl.MAX_COMBINED_TEXTURE_IMAGE_UNITS),
        };
        
        // 获取支持的扩展
        const extensions = gl.getSupportedExtensions();
        
        // 生成 HTML
        let html = '<div style="margin-bottom: 15px;">';
        html += '<strong style="color: #dc3545; font-size: 14px;">🔴 高风险信息（可精准识别设备）：</strong><br><br>';
        
        for (let key in webglData) {
            html += `<div style="margin-bottom: 8px;">
                <strong>${key}:</strong> 
                <span style="color: #dc3545;">${webglData[key]}</span>
            </div>`;
        }
        
        html += '</div>';
        
        html += '<div style="border-top: 1px solid #ddd; padding-top: 15px;">';
        html += `<strong>支持的扩展 (${extensions.length} 个):</strong><br><br>`;
        html += '<div style="max-height: 150px; overflow-y: auto; font-size: 11px; line-height: 1.8;">';
        extensions.forEach(ext => {
            const isHighRisk = ext.includes('debug') || ext.includes('renderer');
            html += `<span style="display: inline-block; margin: 2px; padding: 2px 8px; background: ${isHighRisk ? '#f8d7da' : '#e9ecef'}; border-radius: 12px; ${isHighRisk ? 'color: #721c24;' : 'color: #666;'}">${ext}</span>`;
        });
        html += '</div></div>';
        
        webglDiv.innerHTML = html;
    })();
    
    // ===== 基础设备信息 =====
    (function() {
        const demo = document.getElementById('fingerprint-demo');
        if (!demo) return;
        
        // 正确检测浏览器
        function getBrowserName() {
            const ua = navigator.userAgent;
            
            // 按照优先级检测（Chrome 必须在 Safari 之前检测）
            if (ua.indexOf('Edg') > -1) return 'Edge';
            if (ua.indexOf('OPR') > -1 || ua.indexOf('Opera') > -1) return 'Opera';
            if (ua.indexOf('Chrome') > -1) return 'Chrome';  // Chrome 在 Safari 之前
            if (ua.indexOf('Safari') > -1) return 'Safari';
            if (ua.indexOf('Firefox') > -1) return 'Firefox';
            if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident/') > -1) return 'IE';
            
            return '未知浏览器';
        }
        
        // 检测操作系统
        function getOSName() {
            const ua = navigator.userAgent;
            const platform = navigator.platform;
            
            if (ua.indexOf('Win') > -1) return 'Windows';
            if (ua.indexOf('Mac') > -1) return 'macOS';
            if (ua.indexOf('Linux') > -1) return 'Linux';
            if (ua.indexOf('Android') > -1) return 'Android';
            if (ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) return 'iOS';
            
            return platform || '未知';
        }
        
        const info = {
            '设备类型': /Mobile|Android|iPhone|iPad/i.test(navigator.userAgent) ? '移动设备' : '桌面设备',
            '屏幕分辨率': `${screen.width} × ${screen.height}`,
            '窗口尺寸': `${window.innerWidth} × ${window.innerHeight}`,
            '设备像素比': window.devicePixelRatio || 1,
            '色深': screen.colorDepth + ' bit',
            '触摸支持': navigator.maxTouchPoints > 0 ? '是 (' + navigator.maxTouchPoints + ' 点)' : '否',
            'CPU 核心': navigator.hardwareConcurrency || '未知',
            '浏览器语言': navigator.language,
            '操作系统': getOSName(),
            '浏览器': getBrowserName(),
            '时区偏移': (new Date().getTimezoneOffset() / -60) + ' (UTC' + (new Date().getTimezoneOffset() / -60 >= 0 ? '+' : '') + (new Date().getTimezoneOffset() / -60) + ')'
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

