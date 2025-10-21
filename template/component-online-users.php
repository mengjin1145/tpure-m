<?php
/**
 * Tpure 主题 - 实时在线人数组件
 * 
 * 在模板中引入：
 * <?php include $zbp->templatepath . '/component-online-users.php'; ?>
 * 
 * @package Tpure
 * @version 5.0.7
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

// 获取在线人数
$onlineCount = 0;
if (class_exists('TpureStatistics')) {
    $onlineCount = TpureStatistics::getOnlineCount();
}

// 获取今日访问量
$todayVisits = 0;
if (class_exists('TpureStatistics')) {
    $todayVisits = TpureStatistics::getTotalVisits('', 1);
}

// 获取总访问量
$totalVisits = 0;
if (class_exists('TpureStatistics')) {
    $totalVisits = TpureStatistics::getTotalVisits('', 0);
}

?>

<!-- 实时在线人数组件 -->
<div class="tpure-online-widget" id="tpure-online-widget">
    <div class="widget-header">
        <h3 class="widget-title">
            <i class="iconfont icon-user"></i>
            网站统计
        </h3>
    </div>
    
    <div class="widget-body">
        <!-- 实时在线 -->
        <div class="stat-item online">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                    <circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="2" opacity="0.3"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">实时在线</div>
                <div class="stat-value" id="online-count" data-count="<?php echo $onlineCount; ?>">
                    <?php echo $onlineCount; ?>
                </div>
            </div>
            <div class="stat-badge pulse">
                <span class="pulse-dot"></span>
            </div>
        </div>
        
        <!-- 今日访问 -->
        <div class="stat-item today">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" fill="currentColor" opacity="0.3"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">今日访问</div>
                <div class="stat-value"><?php echo number_format($todayVisits); ?></div>
            </div>
        </div>
        
        <!-- 总访问量 -->
        <div class="stat-item total">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M3 13h2v8H3v-8zm4-6h2v14H7V7zm4-4h2v18h-2V3zm4 9h2v9h-2v-9zm4-3h2v12h-2V9z" fill="currentColor" opacity="0.3"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">总访问量</div>
                <div class="stat-value"><?php echo number_format($totalVisits); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
/* 在线人数组件样式 */
.tpure-online-widget {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.tpure-online-widget .widget-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.tpure-online-widget .widget-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tpure-online-widget .widget-body {
    padding: 10px;
}

.tpure-online-widget .stat-item {
    display: flex;
    align-items: center;
    padding: 15px 10px;
    border-radius: 6px;
    transition: background-color 0.3s;
    position: relative;
}

.tpure-online-widget .stat-item:hover {
    background: #f8f9fa;
}

.tpure-online-widget .stat-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-right: 12px;
    flex-shrink: 0;
}

.tpure-online-widget .stat-item.online .stat-icon {
    background: #e8f5e9;
    color: #4caf50;
}

.tpure-online-widget .stat-item.today .stat-icon {
    background: #fff3e0;
    color: #ff9800;
}

.tpure-online-widget .stat-item.total .stat-icon {
    background: #e3f2fd;
    color: #2196f3;
}

.tpure-online-widget .stat-content {
    flex: 1;
}

.tpure-online-widget .stat-label {
    font-size: 13px;
    color: #666;
    margin-bottom: 4px;
}

.tpure-online-widget .stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    font-variant-numeric: tabular-nums;
}

.tpure-online-widget .stat-item.online .stat-value {
    color: #4caf50;
}

/* 脉动动画 */
.tpure-online-widget .stat-badge {
    position: relative;
    width: 16px;
    height: 16px;
}

.tpure-online-widget .pulse-dot {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 8px;
    height: 8px;
    background: #4caf50;
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

.tpure-online-widget .pulse-dot::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    background: #4caf50;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: pulse 2s ease-out infinite;
}

@keyframes pulse {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(3);
    }
}

/* 数字跳动动画 */
@keyframes countUp {
    from {
        transform: translateY(10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.tpure-online-widget .stat-value.updated {
    animation: countUp 0.5s ease-out;
}

/* 深色模式支持 */
@media (prefers-color-scheme: dark) {
    .tpure-online-widget {
        background: #1e1e1e;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .tpure-online-widget .widget-header {
        border-bottom-color: #333;
    }
    
    .tpure-online-widget .widget-title {
        color: #e0e0e0;
    }
    
    .tpure-online-widget .stat-item:hover {
        background: #2a2a2a;
    }
    
    .tpure-online-widget .stat-label {
        color: #999;
    }
    
    .tpure-online-widget .stat-value {
        color: #e0e0e0;
    }
}

/* 响应式 */
@media (max-width: 768px) {
    .tpure-online-widget .stat-value {
        font-size: 20px;
    }
    
    .tpure-online-widget .stat-icon {
        width: 36px;
        height: 36px;
    }
}
</style>

<script>
/**
 * 实时在线人数更新
 */
(function() {
    'use strict';
    
    const onlineCountElement = document.getElementById('online-count');
    
    if (!onlineCountElement) {
        return;
    }
    
    /**
     * 更新在线人数
     */
    function updateOnlineCount() {
        fetch('<?php echo $zbp->host; ?>zb_system/cmd.php?act=ajax&src=tpure_stats_online')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.count !== undefined) {
                    const oldCount = parseInt(onlineCountElement.dataset.count || '0');
                    const newCount = parseInt(data.count);
                    
                    if (oldCount !== newCount) {
                        // 添加更新动画
                        onlineCountElement.classList.add('updated');
                        
                        // 数字动画
                        animateValue(onlineCountElement, oldCount, newCount, 500);
                        
                        // 更新数据属性
                        onlineCountElement.dataset.count = newCount;
                        
                        // 移除动画类
                        setTimeout(() => {
                            onlineCountElement.classList.remove('updated');
                        }, 500);
                    }
                }
            })
            .catch(error => {
                console.warn('更新在线人数失败:', error);
            });
    }
    
    /**
     * 数字动画
     */
    function animateValue(element, start, end, duration) {
        const range = end - start;
        const startTime = Date.now();
        
        function update() {
            const currentTime = Date.now();
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // 缓动函数
            const easeOutQuad = progress * (2 - progress);
            const current = Math.round(start + range * easeOutQuad);
            
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }
    
    // 初始加载后30秒开始更新
    setTimeout(() => {
        updateOnlineCount();
        
        // 每30秒更新一次
        setInterval(updateOnlineCount, 30000);
    }, 30000);
    
})();
</script>

