<?php
/**
 * 缓存触发测试工具
 * 手动触发缓存生成，验证缓存功能是否正常工作
 */

header('Content-Type: text/html; charset=utf-8');

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

$message = '';
$messageType = '';

// 检查是否有触发缓存的请求
if (isset($_GET['trigger'])) {
    // 检查Redis连接
    if (!extension_loaded('redis')) {
        $message = '❌ Redis扩展未安装';
        $messageType = 'error';
    } else {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379, 2);
            
            // 读取密码
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
            
            // 手动写入测试缓存
            $testKey = 'tpure:test:' . time();
            $testValue = 'Tpure Cache Test - ' . date('Y-m-d H:i:s');
            
            $redis->setex($testKey, 300, $testValue); // 5分钟过期
            
            $message = "✅ 测试缓存写入成功！<br>键名：<code>{$testKey}</code><br>值：<code>{$testValue}</code>";
            $messageType = 'success';
            
            $redis->close();
        } catch (Exception $e) {
            $message = '❌ Redis操作失败：' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}

// 获取当前Redis键列表
$redisKeys = array();
$redisConnected = false;

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
        $redisConnected = true;
        
        // 获取所有tpure相关的键
        $allKeys = $redis->keys('tpure:*');
        if ($allKeys) {
            foreach ($allKeys as $key) {
                $ttl = $redis->ttl($key);
                $type = $redis->type($key);
                $size = strlen($redis->get($key));
                
                $redisKeys[] = array(
                    'key' => $key,
                    'ttl' => $ttl,
                    'type' => $type,
                    'size' => $size
                );
            }
        }
        
        $redis->close();
    } catch (Exception $e) {
        // 忽略错误
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>缓存触发测试</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { color: #0188fb; border-bottom: 3px solid #0188fb; padding-bottom: 15px; margin-bottom: 20px; }
        h2 { color: #333; margin: 30px 0 15px; font-size: 20px; }
        
        .alert { padding: 15px 20px; margin: 20px 0; border-radius: 8px; font-weight: bold; }
        .alert.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .card { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #dee2e6; }
        
        .btn { display: inline-block; padding: 12px 24px; margin: 5px; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; color: white; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #e9ecef; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        
        code { background: #f4f4f4; padding: 3px 8px; border-radius: 4px; font-family: monospace; color: #e83e8c; }
        
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px; }
        
        .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #0188fb; border-radius: 4px; }
        .step strong { color: #0188fb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 缓存触发测试工具</h1>
        
        <?php if ($message): ?>
        <div class="alert <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📊 当前Redis缓存状态</h2>
            <p><strong>Tpure缓存键数量：</strong><?php echo count($redisKeys); ?> 个</p>
            
            <?php if (count($redisKeys) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>缓存键</th>
                        <th>过期时间（秒）</th>
                        <th>类型</th>
                        <th>大小（字节）</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($redisKeys as $item): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($item['key']); ?></code></td>
                        <td><?php echo $item['ttl'] > 0 ? $item['ttl'] : '永久'; ?></td>
                        <td><?php echo $item['type']; ?></td>
                        <td><?php echo number_format($item['size']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="warning-box">
                <strong>⚠️ 当前没有任何缓存键</strong><br>
                这可能是因为您还没有访问过网站前台页面。
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>🧪 测试缓存生成</h2>
            
            <div class="info-box">
                <strong>💡 测试说明：</strong><br>
                点击下面的按钮手动写入一个测试缓存到Redis，验证缓存功能是否正常工作。
            </div>
            
            <a href="?trigger=1" class="btn">🚀 写入测试缓存</a>
            <a href="?" class="btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">🔄 刷新页面</a>
        </div>
        
        <div class="card">
            <h2>📝 正确的缓存生成流程</h2>
            
            <div class="step">
                <strong>步骤1：</strong> 访问网站前台页面（如首页）<br>
                <a href="<?php echo $zbp->host; ?>" target="_blank" class="btn" style="margin-top: 10px; font-size: 14px;">打开首页</a>
            </div>
            
            <div class="step">
                <strong>步骤2：</strong> 等待1-2秒，让缓存生成完成
            </div>
            
            <div class="step">
                <strong>步骤3：</strong> 刷新本页面，查看缓存键数量是否增加<br>
                <a href="?" class="btn" style="margin-top: 10px; font-size: 14px;">🔄 刷新本页</a>
            </div>
            
            <div class="step">
                <strong>步骤4：</strong> 再次访问首页，使用F12查看响应头的<code>X-Cache</code>字段<br>
                应该显示：<code>X-Cache: HIT</code>
            </div>
        </div>
        
        <div class="card">
            <h2>🔍 检查缓存钩子是否注册</h2>
            
            <?php
            // 检查钩子是否存在
            $hooks = array(
                'Filter_Plugin_ViewIndex_Template' => '首页缓存钩子',
                'Filter_Plugin_ViewList_Template' => '列表页缓存钩子',
                'Filter_Plugin_ViewPost_Template' => '文章页缓存钩子',
            );
            
            echo '<table>';
            echo '<tr><th>钩子名称</th><th>说明</th><th>状态</th></tr>';
            
            foreach ($hooks as $hookName => $desc) {
                $exists = isset($GLOBALS['hooks'][$hookName]);
                echo '<tr>';
                echo '<td><code>' . htmlspecialchars($hookName) . '</code></td>';
                echo '<td>' . htmlspecialchars($desc) . '</td>';
                echo '<td>' . ($exists ? '<span style="color: #28a745;">✅ 已注册</span>' : '<span style="color: #dc3545;">❌ 未注册</span>') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
            
            <div class="info-box" style="margin-top: 20px;">
                <strong>📌 说明：</strong><br>
                如果钩子显示"未注册"，说明缓存功能代码没有正确加载。<br>
                请检查 <code>include.php</code> 是否正确引入了缓存相关文件。
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="test-cache-optimization.php" class="btn">返回主测试页</a>
        </div>
    </div>
</body>
</html>

