<?php
/**
 * JavaScript错误诊断工具
 */

header('Content-Type: text/html; charset=utf-8');

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>JavaScript错误诊断</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #0188fb; border-bottom: 3px solid #0188fb; padding-bottom: 10px; }
        .card { background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #0188fb; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0166c7; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 JavaScript错误诊断</h1>
    
    <div class="card">
        <h2>📊 错误说明</h2>
        <div class="error">
            <strong>错误1和2：</strong> <code>Unexpected token ';'</code><br>
            页面中有多余的分号，通常是PHP/JavaScript混写错误
        </div>
        <div class="error">
            <strong>错误3：</strong> <code>PageStayTimeTracker已被声明</code><br>
            统计脚本被重复加载，来自AdvancedStats插件
        </div>
    </div>
    
    <div class="card">
        <h2>🔧 检查项目</h2>
        
        <?php
        // 1. 检查是否启用了AdvancedStats插件
        $advancedStatsEnabled = false;
        $pluginList = $zbp->GetPluginList();
        foreach ($pluginList as $plugin) {
            if ($plugin->ID == 'AdvancedStats') {
                $advancedStatsEnabled = $plugin->Status == 1;
                break;
            }
        }
        ?>
        
        <table>
            <tr>
                <th>检查项</th>
                <th>状态</th>
                <th>说明</th>
            </tr>
            <tr>
                <td><strong>AdvancedStats插件</strong></td>
                <td><?php echo $advancedStatsEnabled ? '<span style="color: #28a745;">✅ 已启用</span>' : '<span style="color: #6c757d;">❌ 未启用</span>'; ?></td>
                <td><?php echo $advancedStatsEnabled ? '这个插件可能导致stay_time_tracker.js重复加载' : '插件未启用'; ?></td>
            </tr>
            <tr>
                <td><strong>stay_time_tracker.js文件</strong></td>
                <td>
                    <?php
                    $jsFile = $zbp->usersdir . 'plugin/AdvancedStats/script/stay_time_tracker.js';
                    $exists = file_exists($jsFile);
                    echo $exists ? '<span style="color: #28a745;">✅ 存在</span>' : '<span style="color: #dc3545;">❌ 不存在</span>';
                    ?>
                </td>
                <td><?php echo $exists ? '文件大小：' . filesize($jsFile) . ' bytes' : ''; ?></td>
            </tr>
            <tr>
                <td><strong>缓存状态</strong></td>
                <td>
                    <?php
                    $cacheOn = ($zbp->Config('tpure')->CacheFullPageOn ?? 'OFF') === 'ON';
                    echo $cacheOn ? '<span style="color: #28a745;">✅ 已开启</span>' : '<span style="color: #6c757d;">❌ 已关闭</span>';
                    ?>
                </td>
                <td><?php echo $cacheOn ? '可能缓存了损坏的HTML，建议清除' : ''; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>✅ 解决方案</h2>
        
        <div class="warning">
            <strong>方案1：清除缓存（推荐）</strong><br>
            1. 访问：<a href="test-cache-optimization.php" target="_blank">缓存测试工具</a><br>
            2. 点击"🗑️ 清除Redis缓存"按钮<br>
            3. 刷新问题页面
        </div>
        
        <div class="warning">
            <strong>方案2：禁用AdvancedStats插件</strong><br>
            1. 访问：<a href="../../../zb_system/cmd.php?act=PluginMng" target="_blank">插件管理</a><br>
            2. 找到"AdvancedStats"插件<br>
            3. 点击"禁用"<br>
            4. 刷新前台页面测试
        </div>
        
        <div class="warning">
            <strong>方案3：检查header.php模板</strong><br>
            查看 <code>header.php</code> 中是否有重复的script标签：<br>
            <pre><?php
            $headerFile = dirname(__FILE__) . '/header.php';
            if (file_exists($headerFile)) {
                $content = file_get_contents($headerFile);
                // 检查stay_time_tracker.js出现次数
                $count = substr_count($content, 'stay_time_tracker.js');
                echo "stay_time_tracker.js 在header.php中出现 {$count} 次\n";
                
                if ($count > 1) {
                    echo "\n⚠️ 警告：脚本被引入多次！请检查模板。";
                }
            } else {
                echo "header.php 文件不存在";
            }
            ?></pre>
        </div>
    </div>
    
    <div class="card">
        <h2>🔍 查看stay_time_tracker.js内容</h2>
        <?php
        $jsFile = $zbp->usersdir . 'plugin/AdvancedStats/script/stay_time_tracker.js';
        if (file_exists($jsFile)) {
            $content = file_get_contents($jsFile);
            echo '<pre style="max-height: 400px; overflow-y: auto;">' . htmlspecialchars($content) . '</pre>';
            
            // 检查是否有重复声明
            if (substr_count($content, 'class PageStayTimeTracker') > 1) {
                echo '<div class="error">⚠️ 文件内部就有重复声明！插件文件可能损坏。</div>';
            }
        } else {
            echo '<div class="warning">文件不存在</div>';
        }
        ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="test-cache-optimization.php" class="btn">缓存测试工具</a>
        <a href="?" class="btn">🔄 刷新本页</a>
    </div>
</body>
</html>

