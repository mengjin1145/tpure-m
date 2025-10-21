<?php
/**
 * Tpure 主题 - 缓存优化测试工具（增强版）
 * 
 * 功能：
 * 1. 检测缓存配置状态
 * 2. 一键开启/关闭缓存
 * 3. 实时调试缓存功能
 * 4. 性能对比测试
 * 
 * 访问：http://你的域名/zb_users/theme/tpure/test-cache-optimization.php
 * 
 * @version 2.0
 * @date 2025-01-20
 */

header('Content-Type: text/html; charset=utf-8');

// 引入Z-BlogPHP核心
require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

// ==================== 处理POST请求 ====================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'enable_all':
                // 开启所有缓存
                $zbp->Config('tpure')->CacheFullPageOn = 'ON';
                $zbp->Config('tpure')->CacheHotContentOn = 'ON';
                $zbp->Config('tpure')->CacheBrowserOn = 'ON';
                $zbp->Config('tpure')->CacheTemplateOn = 'ON';
                $zbp->SaveConfig('tpure');
                $message = '✅ 已开启所有缓存功能';
                $messageType = 'success';
                break;
                
            case 'disable_all':
                // 关闭所有缓存
                $zbp->Config('tpure')->CacheFullPageOn = 'OFF';
                $zbp->Config('tpure')->CacheHotContentOn = 'OFF';
                $zbp->Config('tpure')->CacheBrowserOn = 'OFF';
                $zbp->Config('tpure')->CacheTemplateOn = 'OFF';
                $zbp->SaveConfig('tpure');
                $message = '⚠️ 已关闭所有缓存功能';
                $messageType = 'warning';
                break;
                
            case 'clear_cache':
                // 清除缓存
                $cleared = 0;
                if (extension_loaded('redis')) {
                    try {
                        $redis = new Redis();
                        $redis->connect('127.0.0.1', 6379, 2);
                        
                        // 读取Redis密码
                        $password = '';
                        $configCacheFile = $zbp->usersdir . 'cache/config_zbpcache.php';
                        if (file_exists($configCacheFile)) {
                            $configData = @include $configCacheFile;
                            if (is_array($configData) && isset($configData['redis_password'])) {
                                $password = $configData['redis_password'];
                            }
                        }
                        
                        if ($password) {
                            $redis->auth($password);
                        }
                        
                        // 清除tpure相关缓存
                        $keys = $redis->keys('tpure:*');
                        if ($keys) {
                            foreach ($keys as $key) {
                                $redis->del($key);
                                $cleared++;
                            }
                        }
                        
                        $redis->close();
                        $message = "✅ 已清除 {$cleared} 个Redis缓存项";
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = '❌ Redis清除失败：' . $e->getMessage();
                        $messageType = 'error';
                    }
                } else {
                    $message = '⚠️ Redis扩展未安装，无法清除缓存';
                    $messageType = 'warning';
                }
                break;
                
            case 'toggle_cache':
                // 切换单个缓存
                $cacheType = $_POST['cache_type'];
                $currentValue = $zbp->Config('tpure')->$cacheType ?? 'OFF';
                $newValue = ($currentValue === 'ON') ? 'OFF' : 'ON';
                $zbp->Config('tpure')->$cacheType = $newValue;
                $zbp->SaveConfig('tpure');
                $message = "✅ {$cacheType} 已切换为 {$newValue}";
                $messageType = 'success';
                break;
        }
    }
}

// ==================== 获取当前配置 ====================
$config = array(
    'CacheFullPageOn' => $zbp->Config('tpure')->CacheFullPageOn ?? 'OFF',
    'CacheHotContentOn' => $zbp->Config('tpure')->CacheHotContentOn ?? 'OFF',
    'CacheBrowserOn' => $zbp->Config('tpure')->CacheBrowserOn ?? 'OFF',
    'CacheTemplateOn' => $zbp->Config('tpure')->CacheTemplateOn ?? 'OFF',
);

// 检测Redis状态
$redisAvailable = false;
$redisInfo = array();
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 2);
        
        $password = '';
        $configCacheFile = $zbp->usersdir . 'cache/config_zbpcache.php';
        if (file_exists($configCacheFile)) {
            $configData = @include $configCacheFile;
            if (is_array($configData) && isset($configData['redis_password'])) {
                $password = $configData['redis_password'];
            }
        }
        
        if ($password) {
            $redis->auth($password);
        }
        
        $redis->ping();
        $redisAvailable = true;
        
        // 获取缓存统计
        $redisInfo['keys'] = count($redis->keys('tpure:*'));
        $redisInfo['memory'] = $redis->info('memory')['used_memory_human'] ?? 'N/A';
        
        $redis->close();
    } catch (Exception $e) {
        $redisInfo['error'] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tpure 缓存优化测试（增强版）</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { color: #0188fb; border-bottom: 3px solid #0188fb; padding-bottom: 15px; margin-bottom: 20px; font-size: 28px; }
        h2 { color: #333; margin: 30px 0 15px; font-size: 20px; display: flex; align-items: center; }
        h2::before { content: ''; width: 4px; height: 20px; background: #0188fb; margin-right: 10px; }
        
        .alert { padding: 15px 20px; margin: 20px 0; border-radius: 8px; font-weight: bold; animation: slideIn 0.3s; }
        .alert.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .alert.warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card { background: #fff; padding: 25px; margin: 20px 0; border-radius: 10px; border: 1px solid #e0e0e0; transition: all 0.3s; }
        .card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.1); transform: translateY(-2px); }
        
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-weight: bold; font-size: 14px; }
        .status-badge.on { background: #28a745; color: white; }
        .status-badge.off { background: #dc3545; color: white; }
        .status-badge.available { background: #17a2b8; color: white; }
        .status-badge.unavailable { background: #6c757d; color: white; }
        
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        
        .btn { display: inline-block; padding: 12px 24px; margin: 5px; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .btn-danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .btn-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .btn-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0; }
        
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .info-box strong { color: #1976d2; }
        
        .debug-section { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .debug-item { margin: 10px 0; padding: 10px; background: white; border-radius: 4px; }
        .debug-label { font-weight: bold; color: #495057; margin-right: 10px; }
        .debug-value { color: #0188fb; font-family: monospace; }
        
        code { background: #f4f4f4; padding: 3px 8px; border-radius: 4px; font-family: 'Courier New', monospace; color: #e83e8c; }
        
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #6c757d; }
        
        @media (max-width: 768px) {
            body { margin: 10px; padding: 10px; }
            .container { padding: 15px; }
            h1 { font-size: 22px; }
            .btn-group { flex-direction: column; }
            table { font-size: 14px; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Tpure 缓存优化测试（增强版）</h1>
        <p style="color: #6c757d; margin-bottom: 20px;">测试时间：<?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php if ($message): ?>
        <div class="alert <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- 快速操作按钮 -->
        <div class="card">
            <h2>⚡ 快速操作</h2>
            <div class="btn-group">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="enable_all">
                    <button type="submit" class="btn btn-success" onclick="return confirm('确定开启所有缓存功能吗？')">
                        ✅ 一键开启所有缓存
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="disable_all">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('确定关闭所有缓存功能吗？')">
                        ❌ 一键关闭所有缓存
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('确定清除所有Redis缓存吗？')">
                        🗑️ 清除Redis缓存
                    </button>
                </form>
                
                <a href="?" class="btn btn-info">🔄 刷新页面</a>
                <a href="<?php echo $zbp->host; ?>zb_system/cmd.php?act=BuildTemplate" class="btn btn-primary" onclick="return confirm('确定重新编译模板吗？')">
                    🔨 重新编译模板
                </a>
            </div>
        </div>
        
        <!-- Redis状态 -->
        <div class="card">
            <h2>1️⃣ Redis扩展检测</h2>
            <?php if (extension_loaded('redis')): ?>
                <div class="status-badge on">✅ 已安装</div>
                <p style="margin-top: 10px;">Redis扩展版本：<?php echo phpversion('redis'); ?></p>
                
                <?php if ($redisAvailable): ?>
                    <div class="status-badge available" style="margin-left: 10px;">✅ 连接成功</div>
                    <div class="info-box">
                        <strong>📊 Redis统计信息：</strong><br>
                        • Tpure缓存键数量：<code><?php echo $redisInfo['keys']; ?></code> 个<br>
                        • Redis内存占用：<code><?php echo $redisInfo['memory']; ?></code>
                    </div>
                <?php else: ?>
                    <div class="status-badge unavailable" style="margin-left: 10px;">❌ 连接失败</div>
                    <?php if (isset($redisInfo['error'])): ?>
                        <p style="color: #dc3545; margin-top: 10px;">错误信息：<?php echo htmlspecialchars($redisInfo['error']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="status-badge off">❌ 未安装</div>
                <p style="color: #dc3545; margin-top: 10px;">请联系主机商安装Redis扩展</p>
            <?php endif; ?>
        </div>
        
        <!-- 缓存配置状态 -->
        <div class="card">
            <h2>2️⃣ 缓存配置状态（可快速切换）</h2>
            <table>
                <thead>
                    <tr>
                        <th>功能名称</th>
                        <th>当前状态</th>
                        <th>依赖</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>全页面缓存</strong><br><small>首页5分钟，其他页面1小时</small></td>
                        <td><span class="status-badge <?php echo $config['CacheFullPageOn'] === 'ON' ? 'on' : 'off'; ?>"><?php echo $config['CacheFullPageOn']; ?></span></td>
                        <td>需要Redis</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_cache">
                                <input type="hidden" name="cache_type" value="CacheFullPageOn">
                                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                                    切换为 <?php echo $config['CacheFullPageOn'] === 'ON' ? 'OFF' : 'ON'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>热门内容缓存</strong><br><small>热门文章/分类/标签，1小时</small></td>
                        <td><span class="status-badge <?php echo $config['CacheHotContentOn'] === 'ON' ? 'on' : 'off'; ?>"><?php echo $config['CacheHotContentOn']; ?></span></td>
                        <td>需要Redis</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_cache">
                                <input type="hidden" name="cache_type" value="CacheHotContentOn">
                                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                                    切换为 <?php echo $config['CacheHotContentOn'] === 'ON' ? 'OFF' : 'ON'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>浏览器缓存（HTTP）</strong><br><small>静态资源缓存头优化</small></td>
                        <td><span class="status-badge <?php echo $config['CacheBrowserOn'] === 'ON' ? 'on' : 'off'; ?>"><?php echo $config['CacheBrowserOn']; ?></span></td>
                        <td>无需Redis</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_cache">
                                <input type="hidden" name="cache_type" value="CacheBrowserOn">
                                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                                    切换为 <?php echo $config['CacheBrowserOn'] === 'ON' ? 'OFF' : 'ON'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>模板编译缓存</strong><br><small>Z-BlogPHP原生模板缓存</small></td>
                        <td><span class="status-badge <?php echo $config['CacheTemplateOn'] === 'ON' ? 'on' : 'off'; ?>"><?php echo $config['CacheTemplateOn']; ?></span></td>
                        <td>无需Redis</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_cache">
                                <input type="hidden" name="cache_type" value="CacheTemplateOn">
                                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                                    切换为 <?php echo $config['CacheTemplateOn'] === 'ON' ? 'OFF' : 'ON'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- 调试信息 -->
        <div class="card">
            <h2>3️⃣ 调试信息</h2>
            <div class="debug-section">
                <div class="debug-item">
                    <span class="debug-label">📁 配置文件路径：</span>
                    <span class="debug-value"><?php echo $zbp->usersdir . 'c_option.php'; ?></span>
                </div>
                <div class="debug-item">
                    <span class="debug-label">🔧 配置读取方式：</span>
                    <span class="debug-value">$zbp->Config('tpure')->CacheFullPageOn</span>
                </div>
                <div class="debug-item">
                    <span class="debug-label">💾 当前配置内容：</span>
                    <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 6px; overflow-x: auto; margin-top: 10px;"><?php 
                    $debugConfig = array(
                        'CacheFullPageOn' => $zbp->Config('tpure')->CacheFullPageOn ?? 'NOT SET',
                        'CacheHotContentOn' => $zbp->Config('tpure')->CacheHotContentOn ?? 'NOT SET',
                        'CacheBrowserOn' => $zbp->Config('tpure')->CacheBrowserOn ?? 'NOT SET',
                        'CacheTemplateOn' => $zbp->Config('tpure')->CacheTemplateOn ?? 'NOT SET',
                    );
                    echo json_encode($debugConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    ?></pre>
                </div>
                <div class="debug-item">
                    <span class="debug-label">🐛 调试模式：</span>
                    <span class="debug-value">
                        <?php 
                        if (defined('TPURE_DEBUG') && TPURE_DEBUG === true) {
                            echo '<span class="status-badge on">已开启</span>';
                        } else {
                            echo '<span class="status-badge off">未开启</span>';
                        }
                        ?>
                    </span>
                </div>
                <div class="debug-item">
                    <span class="debug-label">🌐 当前URL：</span>
                    <span class="debug-value"><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></span>
                </div>
                <div class="debug-item">
                    <span class="debug-label">⏰ 服务器时间：</span>
                    <span class="debug-value"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- 性能测试说明 -->
        <div class="card">
            <h2>4️⃣ 性能测试指南</h2>
            <div class="info-box">
                <strong>💡 如何测试缓存效果：</strong><br>
                <ol style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">
                    <li>按 <kbd>F12</kbd> 打开浏览器开发者工具</li>
                    <li>切换到 <code>Network</code> 标签页</li>
                    <li>访问首页，查看响应头中的 <code>X-Cache</code> 字段：
                        <ul style="margin-top: 5px;">
                            <li><code>X-Cache: MISS</code> = 缓存未命中（首次访问，响应时间约200-500ms）</li>
                            <li><code>X-Cache: HIT</code> = 缓存命中（加速访问，响应时间约50-100ms）</li>
                        </ul>
                    </li>
                    <li>刷新页面，应该看到 <code>X-Cache: HIT</code>，响应时间显著降低</li>
                    <li>预期性能提升：<strong>80-90%</strong></li>
                </ol>
            </div>
            
            <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
                <strong>⚠️ 注意事项：</strong><br>
                • 全页面缓存和热门内容缓存需要Redis扩展支持<br>
                • 缓存开启后，修改文章需要清除缓存或等待过期<br>
                • 生产环境建议关闭调试模式（include.php中设置TPURE_DEBUG=false）<br>
                • 定期清理过期缓存，保持Redis内存健康
            </div>
        </div>
        
        <!-- 文件检测 -->
        <div class="card">
            <h2>5️⃣ 缓存文件检测</h2>
            <table>
                <thead>
                    <tr>
                        <th>文件</th>
                        <th>状态</th>
                        <th>说明</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cacheFiles = array(
                        'lib/cache.php' => '统一缓存管理',
                        'lib/fullpage-cache.php' => '全页面缓存实现',
                        'lib/hot-cache.php' => '热门内容缓存',
                        'lib/http-cache.php' => 'HTTP缓存优化',
                        'include.php' => '主题核心文件',
                        'main.php' => '主题配置页面',
                    );
                    
                    foreach ($cacheFiles as $file => $desc) {
                        $path = dirname(__FILE__) . '/' . $file;
                        $exists = file_exists($path);
                        $size = $exists ? filesize($path) : 0;
                        echo '<tr>';
                        echo '<td><code>' . htmlspecialchars($file) . '</code></td>';
                        echo '<td>' . ($exists ? '<span class="status-badge on">✅ 存在</span>' : '<span class="status-badge off">❌ 缺失</span>') . '</td>';
                        echo '<td>' . htmlspecialchars($desc) . ($exists ? ' (' . number_format($size) . ' bytes)' : '') . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>🎯 测试完成！如有问题，请查看 <a href="https://github.com/mengjin1145/tpure-m" target="_blank" style="color: #0188fb;">GitHub仓库</a></p>
            <p style="margin-top: 10px; font-size: 14px;">Tpure Theme by TOYEAN | Enhanced Cache Test Tool v2.0</p>
        </div>
    </div>
</body>
</html>
