<?php
/**
 * Redis 密码配置修复工具
 */

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

if (!$zbp->CheckRights('root')) {
    die('请先登录后台');
}

echo '<meta charset="utf-8">';
echo '<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.btn { padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>';

echo '<h1>🔧 Redis 密码配置修复</h1>';

// 检查 zbpcache 配置
echo '<div class="box">';
echo '<h3>步骤 1: 检查 zbpcache 插件配置</h3>';

$zbpcacheConfigFile = $zbp->usersdir . 'plugin/zbpcache/plugin.xml';
if (file_exists($zbpcacheConfigFile)) {
    echo '<p class="success">✓ zbpcache 插件配置文件存在</p>';
    
    // 检查 Redis 配置
    $redisHost = $zbp->Config('zbpcache')->redis_host ?: '127.0.0.1';
    $redisPort = $zbp->Config('zbpcache')->redis_port ?: 6379;
    $redisPassword = $zbp->Config('zbpcache')->redis_password ?: '';
    
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th>配置项</th><th>当前值</th></tr>';
    echo '<tr><td>Redis 地址</td><td>' . $redisHost . '</td></tr>';
    echo '<tr><td>Redis 端口</td><td>' . $redisPort . '</td></tr>';
    echo '<tr><td>Redis 密码</td><td>' . ($redisPassword ? '已设置 (****)' : '<span class="error">未设置</span>') . '</td></tr>';
    echo '</table>';
} else {
    echo '<p class="error">✗ zbpcache 插件配置文件不存在</p>';
}
echo '</div>';

// 测试 Redis 连接
echo '<div class="box">';
echo '<h3>步骤 2: 测试 Redis 连接</h3>';

if (extension_loaded('redis')) {
    $redis = new Redis();
    
    // 测试无密码连接
    echo '<h4>测试 1: 无密码连接</h4>';
    try {
        if (@$redis->connect('127.0.0.1', 6379, 2)) {
            echo '<p class="success">✓ 连接成功</p>';
            
            // 测试写入
            $testResult = @$redis->set('test_key', 'test_value');
            if ($testResult === false) {
                $error = $redis->getLastError();
                echo '<p class="error">✗ 写入失败: ' . $error . '</p>';
                
                if (strpos($error, 'NOAUTH') !== false) {
                    echo '<p class="warning">⚠️ Redis 需要密码认证</p>';
                }
            } else {
                echo '<p class="success">✓ 写入测试成功</p>';
                $redis->del('test_key');
            }
            $redis->close();
        } else {
            echo '<p class="error">✗ 连接失败</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ 错误: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="error">✗ Redis 扩展未安装</p>';
}
echo '</div>';

// 配置修复表单
echo '<div class="box">';
echo '<h3>步骤 3: 配置 Redis 密码</h3>';

if (isset($_POST['fix_redis'])) {
    $password = $_POST['redis_password'];
    
    echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">';
    echo '<strong>修复结果：</strong><br><br>';
    
    // 测试密码是否正确
    $redis = new Redis();
    try {
        if ($redis->connect('127.0.0.1', 6379, 2)) {
            if (!empty($password)) {
                $authResult = @$redis->auth($password);
                if ($authResult) {
                    echo '• Redis 密码认证: <span class="success">✓ 成功</span><br>';
                    
                    // 测试写入
                    $testResult = @$redis->set('test_key', 'test_value', 10);
                    if ($testResult) {
                        echo '• Redis 写入测试: <span class="success">✓ 成功</span><br>';
                        $redis->del('test_key');
                        
                        // 保存到 zbpcache 配置
                        $zbp->Config('zbpcache')->redis_password = $password;
                        if ($zbp->SaveConfig('zbpcache')) {
                            echo '• 保存配置: <span class="success">✓ 成功</span><br>';
                            echo '<br><p class="success"><strong>✓ Redis 密码配置完成！</strong></p>';
                            echo '<p><a href="cache-diagnostic.php">返回诊断页面查看</a></p>';
                        } else {
                            echo '• 保存配置: <span class="error">✗ 失败</span><br>';
                        }
                    } else {
                        echo '• Redis 写入测试: <span class="error">✗ 失败</span><br>';
                    }
                } else {
                    echo '<span class="error">✗ 密码认证失败，请检查密码是否正确</span><br>';
                }
            }
            $redis->close();
        }
    } catch (Exception $e) {
        echo '<span class="error">✗ 错误: ' . $e->getMessage() . '</span>';
    }
    echo '</div>';
}

echo '<form method="post">';
echo '<p><strong>请输入 Redis 密码：</strong></p>';
echo '<p><input type="text" name="redis_password" placeholder="如果没有密码请留空" style="width: 300px; padding: 8px;" value=""></p>';
echo '<p><small style="color: #666;">提示：Redis 密码通常在 redis.conf 中的 requirepass 配置项</small></p>';
echo '<p><button type="submit" name="fix_redis" class="btn">测试并保存密码</button></p>';
echo '</form>';

echo '<hr>';
echo '<h4>💡 如何查看 Redis 密码？</h4>';
echo '<p>方法 1: 查看 Redis 配置文件</p>';
echo '<pre>cat /etc/redis/redis.conf | grep requirepass</pre>';
echo '<p>方法 2: 使用宝塔面板</p>';
echo '<pre>软件商店 → Redis → 设置 → 配置修改 → 搜索 requirepass</pre>';
echo '<p>方法 3: 如果没有设置密码</p>';
echo '<pre>可以在 redis.conf 中注释掉 requirepass 行，然后重启 Redis</pre>';

echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="cache-diagnostic.php">← 返回诊断页面</a>';
echo '</div>';
?>

