<?php
/**
 * Tpure 主题 - 访问统计后台报表
 * 
 * @package Tpure
 * @version 5.0.7
 */

// 开启错误显示（调试用）
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 错误捕获函数
function tpure_stats_error_handler($errno, $errstr, $errfile, $errline) {
    $error_msg = "错误 [$errno]: $errstr 在 $errfile 第 $errline 行";
    error_log("[Tpure Stats] " . $error_msg);
    echo "<div style='background:#f44336;color:#fff;padding:20px;margin:20px;border-radius:5px;'>";
    echo "<h3>🚫 发生错误</h3>";
    echo "<p><strong>错误信息：</strong> " . htmlspecialchars($errstr) . "</p>";
    echo "<p><strong>错误文件：</strong> " . htmlspecialchars($errfile) . "</p>";
    echo "<p><strong>错误行号：</strong> " . $errline . "</p>";
    echo "</div>";
}

set_error_handler('tpure_stats_error_handler');

try {
    // 加载 Z-BlogPHP 核心
    require '../../../../zb_system/function/c_system_base.php';
    
    $zbp->Load();
    
    // 加载主题模块
    $themeDir = $zbp->usersdir . 'theme/' . $zbp->theme . '/';
    
    // 加载错误处理器（提供 tpure_log 函数）- 检查类是否已存在
    if (!class_exists('TpureErrorHandler')) {
        if (file_exists($themeDir . 'lib/error-handler-safe.php')) {
            require_once $themeDir . 'lib/error-handler-safe.php';
        } elseif (file_exists($themeDir . 'lib/error-handler.php')) {
            require_once $themeDir . 'lib/error-handler.php';
        }
    }
    
    // 加载统计模块 - 检查类是否已存在
    if (!class_exists('TpureStatistics')) {
        if (file_exists($themeDir . 'lib/statistics.php')) {
            require_once $themeDir . 'lib/statistics.php';
        } else {
            throw new Exception('错误：统计模块未找到。请确保主题已正确安装。路径：' . $themeDir . 'lib/statistics.php');
        }
    }
    
    // 检查统计类是否存在
    if (!class_exists('TpureStatistics')) {
        throw new Exception('错误：TpureStatistics 类未定义。请检查 lib/statistics.php 文件。');
    }
    
    // 权限验证已移除 - 允许直接访问诊断工具
    
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>访问统计 - 加载失败</title></head><body>";
    echo "<div style='background:#f44336;color:#fff;padding:20px;margin:20px auto;border-radius:5px;max-width:800px;'>";
    echo "<h3>🚫 加载失败</h3>";
    echo "<p><strong>错误信息：</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>堆栈跟踪：</strong></p>";
    echo "<pre style='background:#fff;color:#333;padding:10px;border-radius:3px;overflow:auto;max-height:300px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div></body></html>";
    die();
}

// 初始化统计表（如果未安装）
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    TpureStatistics::install();
    $zbp->SetHint('good', '✅ 统计表安装成功！');
    Redirect('./statistics.php');
}

// 聚合数据
if (isset($_GET['action']) && $_GET['action'] === 'aggregate') {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('-1 day'));
    $count = TpureStatistics::aggregateFromRedis($date);
    $zbp->SetHint('good', "✅ 成功聚合 {$count} 条数据！");
    Redirect('./statistics.php');
}

// 清理过期数据
if (isset($_GET['action']) && $_GET['action'] === 'clean') {
    $days = isset($_GET['days']) ? intval($_GET['days']) : 365;
    $count = TpureStatistics::cleanExpiredData($days);
    $zbp->SetHint('good', "✅ 成功清理 {$count} 条过期数据！");
    Redirect('./statistics.php');
}

// 获取时间范围
$dateRange = isset($_GET['range']) ? $_GET['range'] : 'today';
$customStart = isset($_GET['start']) ? $_GET['start'] : '';
$customEnd = isset($_GET['end']) ? $_GET['end'] : '';

switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'last7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        break;
    case 'last30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        break;
    case 'custom':
        $startDate = $customStart;
        $endDate = $customEnd;
        break;
    default:
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
}

// 获取统计数据（带错误处理）
try {
    // 检查统计表是否已安装（通过尝试查询表来判断）
    $tableName = $zbp->db->dbpre . 'tpure_visit_stats';
    $tableInstalled = false;
    
    // 尝试查询表，如果成功说明表存在
    try {
        $testSql = "SELECT 1 FROM `{$tableName}` LIMIT 1";
        $zbp->db->Query($testSql);
        $tableInstalled = true;
    } catch (Exception $e) {
        // 表不存在或查询失败
        $tableInstalled = false;
    }
    
    if (!$tableInstalled) {
        // 显示安装提示页面
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>访问统计 - 未安装</title>
            <link rel="stylesheet" href="<?php echo $zbp->host; ?>zb_system/css/admin.css">
        </head>
        <body>
            <div style="max-width:800px;margin:50px auto;padding:20px;">
                <div style="background:#2196f3;color:#fff;padding:30px;border-radius:8px;text-align:center;">
                    <h1 style="margin:0 0 20px;">📊 访问统计系统</h1>
                    <p style="font-size:18px;margin:0 0 30px;">欢迎使用 Tpure 主题访问统计系统！</p>
                    <p style="margin:0 0 30px;">统计表尚未安装，请点击下面的按钮完成安装。</p>
                    <a href="?action=install" style="display:inline-block;background:#fff;color:#2196f3;padding:15px 40px;border-radius:25px;text-decoration:none;font-size:18px;font-weight:bold;">
                        🚀 立即安装
                    </a>
                </div>
                
                <div style="background:#f5f5f5;padding:20px;margin-top:20px;border-radius:8px;">
                    <h3>📝 安装后的功能：</h3>
                    <ul style="line-height:2;">
                        <li>✅ 实时在线人数统计</li>
                        <li>✅ 每日访问量统计</li>
                        <li>✅ 访问趋势图（最近30天）</li>
                        <li>✅ 热门文章排行榜</li>
                        <li>✅ 热门分类排行榜</li>
                        <li>✅ 热门标签排行榜</li>
                        <li>✅ 支持 Redis 缓存加速</li>
                    </ul>
                </div>
            </div>
        </body>
        </html>
        <?php
        die();
    }
    
    $totalVisits = TpureStatistics::getTotalVisits('', 0);
    $todayVisits = TpureStatistics::getTotalVisits('', 1);
    $indexVisits = TpureStatistics::getTotalVisits(TpureStatistics::PAGE_INDEX, 0);
    $onlineCount = TpureStatistics::getOnlineCount();
    
    // 获取热门内容
    $popularArticles = TpureStatistics::getPopularContent(TpureStatistics::PAGE_ARTICLE, 7, 10);
    $popularCategories = TpureStatistics::getPopularContent(TpureStatistics::PAGE_CATEGORY, 7, 10);
    $popularTags = TpureStatistics::getPopularContent(TpureStatistics::PAGE_TAG, 7, 10);
    
    // 获取趋势数据（最近30天）
    $trendData = TpureStatistics::getStats('', 0, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
    
    // 按日期分组统计
    $dailyStats = array();
    foreach ($trendData as $row) {
        $date = $row['stat_Date'];
        if (!isset($dailyStats[$date])) {
            $dailyStats[$date] = array(
                'date' => $date,
                'visits' => 0,
                'unique_visitors' => 0
            );
        }
        $dailyStats[$date]['visits'] += intval($row['stat_VisitCount']);
        $dailyStats[$date]['unique_visitors'] += intval($row['stat_UniqueVisitors']);
    }
    ksort($dailyStats);
    
    // Redis 状态
    $redisAvailable = TpureStatistics::isRedisAvailable();
    
    // 检查是否有数据
    $hasData = ($totalVisits > 0 || $todayVisits > 0 || !empty($popularArticles));
    
} catch (Exception $e) {
    echo "<div style='background:#ff9800;color:#fff;padding:20px;margin:20px;border-radius:5px;'>";
    echo "<h3>⚠️ 获取统计数据失败</h3>";
    echo "<p><strong>错误信息：</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>错误文件：</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>错误行号：</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>堆栈跟踪：</strong></p>";
    echo "<pre style='background:#fff;color:#333;padding:10px;border-radius:3px;overflow:auto;max-height:300px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p style='margin-top:15px;'><strong>可能的原因：</strong></p>";
    echo "<ul style='margin:0;padding-left:20px;'>";
    echo "<li>统计表未安装 - <a href='?action=install' style='color:#fff;text-decoration:underline;'>点击安装</a></li>";
    echo "<li>数据库连接失败</li>";
    echo "<li>Redis 配置错误（如果使用 Redis）</li>";
    echo "</ul>";
    echo "</div>";
    die();
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>访问统计 - Tpure 主题</title>
    <link rel="stylesheet" href="<?php echo $zbp->host; ?>zb_system/css/admin.css">
    <style>
        .stats-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media screen and (max-width: 768px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover::after {
            opacity: 1;
        }
        
        .stat-card h3 {
            margin: 0 0 12px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        
        .stat-card .icon {
            font-size: 28px;
            margin-bottom: 12px;
            opacity: 0.9;
        }
        
        .stat-card.online {
            border-left: 4px solid #4caf50;
        }
        
        .stat-card.online .value {
            color: #4caf50;
        }
        
        .stat-card.online::after {
            color: #4caf50;
        }
        
        .stat-card.total {
            border-left: 4px solid #2196f3;
        }
        
        .stat-card.total .value {
            color: #2196f3;
        }
        
        .stat-card.total::after {
            color: #2196f3;
        }
        
        .stat-card.today {
            border-left: 4px solid #ff9800;
        }
        
        .stat-card.today .value {
            color: #ff9800;
        }
        
        .stat-card.today::after {
            color: #ff9800;
        }
        
        .stats-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-section h2 {
            margin: 0 0 20px;
            font-size: 18px;
            color: #333;
        }
        
        .chart-container {
            height: 300px;
            margin-top: 20px;
        }
        
        .popular-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .popular-list li {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }
        
        .popular-list li:last-child {
            border-bottom: none;
        }
        
        .popular-list li:hover {
            background: #f9f9f9;
            padding-left: 20px;
        }
        
        .popular-list .rank {
            width: 28px;
            height: 28px;
            background: #e8e8e8;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 13px;
            margin-right: 12px;
            flex-shrink: 0;
            color: #666;
        }
        
        .popular-list .top3 {
            background: linear-gradient(135deg, #ff9800, #ffc107);
            color: #fff;
            box-shadow: 0 2px 6px rgba(255, 152, 0, 0.25);
        }
        
        .popular-list .title {
            flex: 1;
            margin-right: 15px;
            overflow: hidden;
        }
        
        .popular-list .title a {
            color: #333;
            text-decoration: none;
            transition: color 0.2s ease;
            font-size: 14px;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .popular-list .title a:hover {
            color: #2196f3;
            text-decoration: underline;
        }
        
        .popular-list .visits {
            font-weight: 600;
            color: #2196f3;
            flex-shrink: 0;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-bar select,
        .filter-bar input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-bar button {
            padding: 8px 16px;
            background: #2196f3;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-bar button:hover {
            background: #1976d2;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.success {
            background: #e8f5e9;
            color: #4caf50;
        }
        
        .status-badge.warning {
            background: #fff3e0;
            color: #ff9800;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #2196f3;
            color: #fff;
        }
        
        .btn-success {
            background: #4caf50;
            color: #fff;
        }
        
        .btn-warning {
            background: #ff9800;
            color: #fff;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab.active {
            border-bottom-color: #2196f3;
            color: #2196f3;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* 左右布局响应式 */
        .two-column-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media screen and (max-width: 1200px) {
            .two-column-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* 紧凑型表格样式 */
        .compact-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .compact-table thead tr {
            background: #f5f5f5;
        }
        
        .compact-table th {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
            font-size: 12px;
            font-weight: 600;
        }
        
        .compact-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .compact-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .compact-table .today-row {
            background: #e3f2fd;
        }
        
        .compact-table .today-row:hover {
            background: #bbdefb;
        }
    </style>
</head>
<body>
    <div class="stats-container">
        <!-- 页头 -->
        <div class="stats-header">
            <div>
                <h1>📊 访问统计</h1>
                <p style="color: #666; margin: 5px 0 0;">
                    Redis 状态: 
                    <?php if ($redisAvailable): ?>
                        <span class="status-badge success">✓ 可用</span>
                    <?php else: ?>
                        <span class="status-badge warning">⚠ 不可用（使用 MySQL）</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="action-buttons">
                <a href="?action=aggregate" class="btn btn-success" onclick="return confirm('确认聚合昨天的数据？');">
                    🔄 聚合数据
                </a>
                <a href="?action=clean&days=365" class="btn btn-warning" onclick="return confirm('确认清理365天前的数据？');">
                    🗑️ 清理数据
                </a>
                <a href="<?php echo $zbp->host; ?>zb_users/theme/<?php echo $zbp->theme; ?>/" class="btn btn-primary" target="_blank">
                    🏠 访问网站
                </a>
            </div>
        </div>
        
        <!-- 统计卡片 -->
        <div class="stats-cards">
            <div class="stat-card online">
                <div class="icon">👥</div>
                <h3>实时在线</h3>
                <div class="value" id="online-count"><?php echo $onlineCount; ?></div>
                <small style="color: #999;">5分钟内活跃用户</small>
            </div>
            
            <div class="stat-card today">
                <div class="icon">📅</div>
                <h3>今日访问</h3>
                <div class="value"><?php echo number_format($todayVisits); ?></div>
                <small style="color: #999;">访问次数</small>
            </div>
            
            <div class="stat-card total">
                <div class="icon">📈</div>
                <h3>总访问量</h3>
                <div class="value"><?php echo number_format($totalVisits); ?></div>
                <small style="color: #999;">历史累计</small>
            </div>
            
            <div class="stat-card">
                <div class="icon">🏠</div>
                <h3>首页访问</h3>
                <div class="value"><?php echo number_format($indexVisits); ?></div>
                <small style="color: #999;">首页累计访问</small>
            </div>
        </div>
        
        <!-- 左右布局：趋势图 + 每日明细 -->
        <div class="two-column-layout">
            <!-- 左侧：访问趋势图 -->
            <div class="stats-section">
                <h2>📊 访问趋势（最近30天）</h2>
                <div style="height: 350px;">
                    <canvas id="trend-chart"></canvas>
                </div>
            </div>
            
            <!-- 右侧：每日明细表格 -->
            <div class="stats-section">
                <h2>📅 每日访问明细（最近7天）</h2>
            <?php
            // 获取最近7天的详细数据
            $last7DaysData = array_slice(array_reverse($dailyStats), 0, 7, true);
            $last7DaysData = array_reverse($last7DaysData);
            ?>
            <div style="overflow-x:auto;">
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>日期</th>
                            <th style="text-align:right;">访问</th>
                            <th style="text-align:right;">UV</th>
                            <th style="text-align:right;">平均</th>
                            <th style="text-align:center;">趋势</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prevVisits = 0;
                        foreach ($last7DaysData as $date => $data): 
                            $visits = intval($data['visits']);
                            $uv = intval($data['unique_visitors']);
                            $avgPerUser = $uv > 0 ? round($visits / $uv, 2) : 0;
                            
                            // 计算趋势
                            $trend = '';
                            $trendColor = '#666';
                            if ($prevVisits > 0) {
                                $change = $visits - $prevVisits;
                                $changePercent = round(($change / $prevVisits) * 100, 1);
                                if ($change > 0) {
                                    $trend = "↑ {$changePercent}%";
                                    $trendColor = '#4caf50';
                                } elseif ($change < 0) {
                                    $trend = "↓ {$changePercent}%";
                                    $trendColor = '#f44336';
                                } else {
                                    $trend = "→ 0%";
                                }
                            }
                            $prevVisits = $visits;
                            
                            // 判断是否为今天
                            $isToday = ($date === date('Y-m-d'));
                            $rowClass = $isToday ? 'today-row' : '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td>
                                <strong><?php echo date('m-d', strtotime($date)); ?></strong>
                                <small style="color:#999;"><?php echo date('D', strtotime($date)); ?></small>
                                <?php if ($isToday): ?>
                                <span style="color:#2196f3;font-size:11px;margin-left:5px;">●</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;font-weight:bold;color:#2196f3;">
                                <?php echo number_format($visits); ?>
                            </td>
                            <td style="text-align:right;color:#666;">
                                <?php echo number_format($uv); ?>
                            </td>
                            <td style="text-align:right;color:#666;">
                                <?php echo $avgPerUser; ?>
                            </td>
                            <td style="text-align:center;color:<?php echo $trendColor; ?>;font-weight:bold;font-size:12px;">
                                <?php echo $trend ?: '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($last7DaysData)): ?>
                        <tr>
                            <td colspan="5" style="padding:40px;text-align:center;color:#999;">
                                暂无数据
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
        
        <!-- 热门内容 Tabs -->
        <div class="stats-section">
            <h2>🔥 热门内容（最近7天）</h2>
            
            <div class="tabs">
                <div class="tab active" onclick="switchTab('articles')">📝 热门文章</div>
                <div class="tab" onclick="switchTab('categories')">📁 热门分类</div>
                <div class="tab" onclick="switchTab('tags')">🏷️ 热门标签</div>
            </div>
            
            <!-- 热门文章 -->
            <div id="tab-articles" class="tab-content active">
                <?php if (empty($popularArticles)): ?>
                    <p style="color: #999; text-align: center; padding: 40px;">暂无数据</p>
                <?php else: ?>
                    <ul class="popular-list">
                        <?php foreach ($popularArticles as $i => $item): ?>
                        <li>
                            <span class="rank <?php echo $i < 3 ? 'top3' : ''; ?>">
                                <?php echo $i + 1; ?>
                            </span>
                            <span class="title">
                                <a href="<?php echo $item['url']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </span>
                            <span class="visits">
                                <?php echo number_format($item['total_visits']); ?> 次
                                <small style="color: #999;">(<?php echo $item['total_unique_visitors']; ?> UV)</small>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- 热门分类 -->
            <div id="tab-categories" class="tab-content">
                <?php if (empty($popularCategories)): ?>
                    <p style="color: #999; text-align: center; padding: 40px;">暂无数据</p>
                <?php else: ?>
                    <ul class="popular-list">
                        <?php foreach ($popularCategories as $i => $item): ?>
                        <li>
                            <span class="rank <?php echo $i < 3 ? 'top3' : ''; ?>">
                                <?php echo $i + 1; ?>
                            </span>
                            <span class="title">
                                <a href="<?php echo $item['url']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </span>
                            <span class="visits">
                                <?php echo number_format($item['total_visits']); ?> 次
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- 热门标签 -->
            <div id="tab-tags" class="tab-content">
                <?php if (empty($popularTags)): ?>
                    <p style="color: #999; text-align: center; padding: 40px;">暂无数据</p>
                <?php else: ?>
                    <ul class="popular-list">
                        <?php foreach ($popularTags as $i => $item): ?>
                        <li>
                            <span class="rank <?php echo $i < 3 ? 'top3' : ''; ?>">
                                <?php echo $i + 1; ?>
                            </span>
                            <span class="title">
                                <a href="<?php echo $item['url']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </span>
                            <span class="visits">
                                <?php echo number_format($item['total_visits']); ?> 次
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Tab 切换
        function switchTab(tab) {
            // 隐藏所有标签页
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // 显示选中的标签页
            event.target.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        // 绘制趋势图
        const trendData = <?php echo json_encode(array_values($dailyStats)); ?>;
        
        if (trendData.length > 0) {
            const ctx = document.getElementById('trend-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => {
                        const date = new Date(d.date);
                        return (date.getMonth() + 1) + '/' + date.getDate();
                    }),
                    datasets: [{
                        label: '访问次数',
                        data: trendData.map(d => d.visits),
                        borderColor: '#2196f3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }, {
                        label: '独立访客',
                        data: trendData.map(d => d.unique_visitors),
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toLocaleString();
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        } else {
            document.getElementById('trend-chart').parentElement.innerHTML = '<p style="text-align:center;color:#999;padding:100px 0;">暂无趋势数据</p>';
        }
        
        // 自动刷新在线人数（每10秒）
        setInterval(function() {
            fetch('<?php echo $zbp->host; ?>zb_system/cmd.php?act=ajax&src=tpure_stats_online')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('online-count').textContent = data.count;
                    }
                })
                .catch(err => console.error('刷新在线人数失败:', err));
        }, 10000);
    </script>
</body>
</html>

