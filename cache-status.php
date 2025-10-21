<?php
/**
 * 缓存状态检测与诊断工具
 * 用于检测主题缓存配置状态和诊断保存问题
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');

// 尝试加载 Z-BlogPHP
$zbpLoaded = false;
$zbpError = '';

try {
    $baseFile = __DIR__ . '/../../../zb_system/function/c_system_base.php';
    if (file_exists($baseFile)) {
        require_once $baseFile;
        $zbp->Load();
        $zbpLoaded = true;
    } else {
        $zbpError = 'Z-BlogPHP 核心文件不存在：' . $baseFile;
    }
} catch (Exception $e) {
    $zbpError = $e->getMessage();
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>缓存诊断工具</title>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5}
.container{max-width:1200px;margin:0 auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
h2{color:#333;border-bottom:3px solid #0188fb;padding-bottom:10px}
h3{color:#0188fb;margin-top:30px}
.status{display:inline-block;padding:5px 15px;border-radius:4px;color:#fff;font-weight:bold;margin-left:10px}
.status.on{background:#4caf50}
.status.off{background:#f44336}
.status.unknown{background:#9e9e9e}
table{width:100%;border-collapse:collapse;margin:20px 0}
th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd}
th{background:#0188fb;color:#fff}
tr:hover{background:#f5f5f5}
.info-box{background:#e3f2fd;border-left:4px solid #0188fb;padding:15px;margin:20px 0}
.warning-box{background:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin:20px 0}
.success-box{background:#d4edda;border-left:4px solid #28a745;padding:15px;margin:20px 0}
.error-box{background:#f8d7da;border-left:4px solid #dc3545;padding:15px;margin:20px 0}
.btn{display:inline-block;padding:10px 20px;background:#0188fb;color:#fff;text-decoration:none;border-radius:4px;margin:5px}
.btn:hover{background:#0170d8}
code{background:#f5f5f5;padding:2px 6px;border-radius:3px;font-family:monospace}
pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto}
</style></head><body>";

echo "<div class='container'>";
echo "<h2>🔍 缓存诊断工具</h2>";
echo "<p>生成时间: " . date('Y-m-d H:i:s') . "</p>";

if (!$zbpLoaded) {
    echo "<div class='error-box'>";
    echo "<h3>❌ Z-BlogPHP 加载失败</h3>";
    echo "<p>错误信息: " . htmlspecialchars($zbpError) . "</p>";
    echo "<p>无法继续检测，请确保 Z-BlogPHP 正常运行。</p>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
}

// ==================== 1. 配置状态 ====================
echo "<h3>1️⃣ 缓存配置状态</h3>";
echo "<table>";
echo "<thead><tr><th>配置项</th><th>当前值</th><th>状态</th><th>说明</th></tr></thead>";
echo "<tbody>";

// 安全获取配置
function getConfig($key, $default = null) {
    global $zbp;
    try {
        if (isset($zbp->Config('tpure')->$key)) {
            return $zbp->Config('tpure')->$key;
        }
    } catch (Exception $e) {
        // 忽略错误
    }
    return $default;
}

// 检查各项配置
$configs = array(
    'CacheFullPageOn' => array(
        'name' => 'Redis 全页面缓存',
        'default' => 'OFF',
        'desc' => '缓存完整的 HTML 页面，极大提升性能'
    ),
    'CacheHotContentOn' => array(
        'name' => '热门内容 HTML 缓存',
        'default' => 'OFF',
        'desc' => '缓存热门文章、评论等模块的 HTML'
    ),
    'CacheBrowserOn' => array(
        'name' => '浏览器缓存（HTTP）',
        'default' => 'OFF',
        'desc' => '设置 Cache-Control 头，让浏览器缓存资源'
    ),
    'CacheTemplateOn' => array(
        'name' => 'Z-BlogPHP 模板缓存',
        'default' => 'ON',
        'desc' => '缓存编译后的模板文件，避免重复编译'
    ),
);

foreach ($configs as $key => $info) {
    $value = getConfig($key);
    $isSet = ($value !== null);
    $displayValue = $value !== null ? $value : '未设置';
    
    echo "<tr>";
    echo "<td><strong>{$info['name']}</strong></td>";
    echo "<td><code>{$displayValue}</code></td>";
    
    if (!$isSet) {
        echo "<td><span class='status unknown'>未初始化</span></td>";
        echo "<td>⚠️ 配置未初始化，将使用默认值 <code>{$info['default']}</code></td>";
    } elseif ($value === 'ON') {
        echo "<td><span class='status on'>已启用</span></td>";
        echo "<td>✅ {$info['desc']}</td>";
    } else {
        echo "<td><span class='status off'>已禁用</span></td>";
        echo "<td>💤 {$info['desc']}</td>";
    }
    echo "</tr>";
}

echo "</tbody></table>";

// ==================== 2. Redis 连接状态 ====================
echo "<h3>2️⃣ Redis 连接状态</h3>";

$redisAvailable = false;
$redisMessage = '';

if (!extension_loaded('redis')) {
    $redisMessage = "<div class='error-box'>❌ <strong>Redis 扩展未安装</strong><br>请在服务器上安装 PHP Redis 扩展才能使用 Redis 缓存功能。</div>";
} else {
    try {
        // 检查 $zbpcache 是否可用
        if (isset($GLOBALS['zbpcache']) && is_object($GLOBALS['zbpcache'])) {
            $zbpcache = $GLOBALS['zbpcache'];
            
            // 尝试写入测试数据
            $testKey = 'tpure_cache_test_' . time();
            $testValue = 'test_' . rand(1000, 9999);
            
            $zbpcache->Set($testKey, $testValue, 10);
            $readValue = $zbpcache->Get($testKey);
            
            if ($readValue === $testValue) {
                $redisAvailable = true;
                $redisMessage = "<div class='success-box'>✅ <strong>Redis 连接正常</strong><br>已成功连接到 Redis 服务器，可以使用 Redis 缓存功能。</div>";
                $zbpcache->Del($testKey);
            } else {
                $redisMessage = "<div class='warning-box'>⚠️ <strong>Redis 读写异常</strong><br>可以连接但无法正常读写数据，请检查 Redis 配置。</div>";
            }
        } else {
            $redisMessage = "<div class='warning-box'>⚠️ <strong>zbpcache 插件未加载</strong><br>请安装并启用 zbpcache 插件，并配置 Redis 密码。<br><a href='https://www.zblogcn.com/zblogphp/app/?id=227' target='_blank'>点击下载 zbpcache 插件</a></div>";
        }
    } catch (Exception $e) {
        $redisMessage = "<div class='error-box'>❌ <strong>Redis 连接失败</strong><br>错误信息: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo $redisMessage;

// ==================== 3. 配置文件检查 ====================
echo "<h3>3️⃣ 配置文件检查</h3>";

$configFile = dirname(__FILE__) . '/../../cache/config_tpure.php';
echo "<p>配置文件路径: <code>" . htmlspecialchars($configFile) . "</code></p>";

if (file_exists($configFile)) {
    echo "<div class='success-box'>✅ 配置文件存在</div>";
    
    echo "<p><strong>文件信息:</strong></p>";
    echo "<ul>";
    echo "<li>文件大小: " . filesize($configFile) . " 字节</li>";
    echo "<li>最后修改: " . date('Y-m-d H:i:s', filemtime($configFile)) . "</li>";
    echo "<li>可读: " . (is_readable($configFile) ? '是' : '否') . "</li>";
    echo "<li>可写: " . (is_writable($configFile) ? '是' : '否') . "</li>";
    echo "</ul>";
    
    // 尝试读取配置
    try {
        $tpureConfig = @include $configFile;
        if (is_array($tpureConfig)) {
            echo "<p><strong>配置内容:</strong></p>";
            echo "<pre>";
            foreach ($configs as $key => $info) {
                $value = isset($tpureConfig[$key]) ? $tpureConfig[$key] : '未设置';
                echo htmlspecialchars("{$key}: {$value}") . "\n";
            }
            echo "</pre>";
        } else {
            echo "<div class='warning-box'>⚠️ 配置文件格式异常</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error-box'>❌ 读取配置文件失败: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='warning-box'>⚠️ 配置文件不存在，配置可能未保存或首次使用</div>";
}

// ==================== 4. 保存测试 ====================
echo "<h3>4️⃣ 配置保存测试</h3>";

if (isset($_GET['test']) && $_GET['test'] === 'save') {
    try {
        $testKey = 'CacheTestSave_' . time();
        $testValue = 'TestValue_' . rand(1000, 9999);
        
        $zbp->Config('tpure')->$testKey = $testValue;
        $zbp->SaveConfig('tpure');
        
        // 重新读取验证
        $savedValue = getConfig($testKey);
        
        if ($savedValue === $testValue) {
            echo "<div class='success-box'>✅ <strong>保存测试成功</strong><br>测试键: <code>{$testKey}</code><br>测试值: <code>{$testValue}</code><br>读取值: <code>{$savedValue}</code></div>";
            
            // 清理测试数据
            unset($zbp->Config('tpure')->$testKey);
            $zbp->SaveConfig('tpure');
        } else {
            echo "<div class='error-box'>❌ <strong>保存测试失败</strong><br>测试键: <code>{$testKey}</code><br>写入值: <code>{$testValue}</code><br>读取值: <code>{$savedValue}</code></div>";
        }
    } catch (Exception $e) {
        echo "<div class='error-box'>❌ <strong>保存测试异常</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<p><a href='?test=save' class='btn'>🧪 运行保存测试</a></p>";
    echo "<p class='info-box'>点击按钮测试配置保存功能是否正常</p>";
}

// ==================== 5. 性能优化建议 ====================
echo "<h3>5️⃣ 性能优化建议</h3>";

$recommendations = array();

$fullPageCache = getConfig('CacheFullPageOn', 'OFF');
$hotCache = getConfig('CacheHotContentOn', 'OFF');
$browserCache = getConfig('CacheBrowserOn', 'OFF');
$templateCache = getConfig('CacheTemplateOn', 'ON');

if ($fullPageCache === 'OFF' && $redisAvailable) {
    $recommendations[] = "🚀 <strong>建议启用「Redis 全页面缓存」</strong> - 可将页面响应时间从 1000ms+ 降低到 50ms，性能提升 20 倍！";
}

if ($hotCache === 'OFF' && $redisAvailable) {
    $recommendations[] = "📊 <strong>建议启用「热门内容 HTML 缓存」</strong> - 可减少侧边栏模块的数据库查询，提升页面加载速度。";
}

if ($browserCache === 'OFF') {
    $recommendations[] = "🌐 <strong>建议启用「浏览器缓存」</strong> - 静态资源可缓存 30 天，减少重复下载，节省带宽。";
}

if ($templateCache === 'OFF') {
    $recommendations[] = "📄 <strong>建议启用「模板缓存」</strong> - 避免每次请求都重新编译模板，提升性能。";
}

if (!$redisAvailable && ($fullPageCache === 'ON' || $hotCache === 'ON')) {
    $recommendations[] = "⚠️ <strong>Redis 未连接，但已启用 Redis 相关缓存</strong> - 请先安装 zbpcache 插件并配置 Redis，否则缓存功能无法生效。";
}

if (count($recommendations) > 0) {
    echo "<div class='info-box'>";
    foreach ($recommendations as $rec) {
        echo "<p style='margin:10px 0'>{$rec}</p>";
    }
    echo "</div>";
} else {
    echo "<div class='success-box'>✅ 缓存配置已优化，无需调整！</div>";
}

// ==================== 6. 快捷操作 ====================
echo "<h3>6️⃣ 快捷操作</h3>";
echo "<p>";
echo "<a href='main.php?act=config' class='btn'>⚙️ 缓存配置</a>";
echo "<a href='javascript:location.reload()' class='btn'>🔄 刷新检测</a>";
echo "<a href='main.php?act=base' class='btn'>🏠 返回设置</a>";
echo "</p>";

echo "<hr style='margin:40px 0'>";
echo "<p style='text-align:center;color:#999'>Tpure 主题 · 缓存诊断工具</p>";
echo "</div>";
echo "</body></html>";
?>

