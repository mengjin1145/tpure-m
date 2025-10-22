-- =====================================================
-- AdvancedStats 插件 - 添加 Canvas 指纹字段
-- =====================================================
-- 
-- 功能：在访问记录表中添加 Canvas 指纹存储字段
-- 
-- 使用方法：
-- 1. 登录 phpMyAdmin 或使用 SSH
-- 2. 选择 Z-BlogPHP 数据库
-- 3. 执行此 SQL 脚本
-- 
-- 注意：请根据实际表名修改 SQL
-- 
-- =====================================================

-- 方法1：已知确切表名
-- 如果你的表名是 zbp_advancedstats_visits
ALTER TABLE `zbp_advancedstats_visits` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Canvas指纹ID',
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);

-- =====================================================
-- 方法2：查找表名（先执行这个查看表名）
-- =====================================================
SHOW TABLES LIKE '%advanced%';

-- 可能的表名：
-- zbp_advancedstats
-- zbp_advancedstats_visits  
-- zbp_advancedstats_log
-- zbp_plugin_advancedstats

-- =====================================================
-- 方法3：如果不确定表名，逐个尝试
-- =====================================================

-- 尝试1
ALTER TABLE `zbp_advancedstats` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Canvas指纹ID',
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);

-- 尝试2（如果上面失败）
ALTER TABLE `zbp_advancedstats_log` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Canvas指纹ID',
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);

-- 尝试3（如果上面也失败）
ALTER TABLE `zbp_plugin_advancedstats` 
ADD COLUMN `canvas_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Canvas指纹ID',
ADD INDEX `idx_canvas_fingerprint` (`canvas_fingerprint`);

-- =====================================================
-- 验证字段是否添加成功
-- =====================================================

-- 查看表结构（替换为实际表名）
DESC `zbp_advancedstats_visits`;

-- 或者
SHOW COLUMNS FROM `zbp_advancedstats_visits` LIKE 'canvas_fingerprint';

-- =====================================================
-- 测试查询（确认字段可用）
-- =====================================================

-- 查看是否有数据
SELECT canvas_fingerprint, COUNT(*) as count
FROM `zbp_advancedstats_visits`
GROUP BY canvas_fingerprint;

-- 查看最近的指纹
SELECT id, ip, visit_time, canvas_fingerprint
FROM `zbp_advancedstats_visits`
ORDER BY visit_time DESC
LIMIT 10;

-- =====================================================
-- 如果需要删除字段（回滚）
-- =====================================================

-- 删除字段和索引
ALTER TABLE `zbp_advancedstats_visits`
DROP INDEX `idx_canvas_fingerprint`,
DROP COLUMN `canvas_fingerprint`;

-- =====================================================
-- 完整的表结构参考
-- =====================================================

/*
CREATE TABLE IF NOT EXISTS `zbp_advancedstats_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL COMMENT '访客IP',
  `user_agent` text COMMENT '浏览器标识',
  `visit_time` datetime NOT NULL COMMENT '访问时间',
  `page_url` varchar(500) DEFAULT NULL COMMENT '访问页面',
  `page_title` varchar(200) DEFAULT NULL COMMENT '页面标题',
  `referer` varchar(500) DEFAULT NULL COMMENT '来源页面',
  `session_id` varchar(64) DEFAULT NULL COMMENT '会话ID',
  `device_type` varchar(20) DEFAULT NULL COMMENT '设备类型',
  `browser` varchar(50) DEFAULT NULL COMMENT '浏览器',
  `os` varchar(50) DEFAULT NULL COMMENT '操作系统',
  `canvas_fingerprint` varchar(64) DEFAULT NULL COMMENT 'Canvas指纹ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_visit_time` (`visit_time`),
  KEY `idx_canvas_fingerprint` (`canvas_fingerprint`),
  KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问记录表';
*/

-- =====================================================
-- 数据分析查询示例
-- =====================================================

-- 1. 统计独立设备数（基于指纹）
SELECT COUNT(DISTINCT canvas_fingerprint) as unique_devices
FROM `zbp_advancedstats_visits`
WHERE canvas_fingerprint IS NOT NULL
AND visit_time >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 2. 查看同一设备的访问历史
SELECT 
    id,
    ip,
    visit_time,
    page_url,
    canvas_fingerprint
FROM `zbp_advancedstats_visits`
WHERE canvas_fingerprint = '7a3f2e1d'  -- 替换为实际指纹
ORDER BY visit_time DESC;

-- 3. 检测异常访问（同一设备短时间大量访问）
SELECT 
    canvas_fingerprint,
    COUNT(*) as visit_count,
    MIN(visit_time) as first_visit,
    MAX(visit_time) as last_visit,
    COUNT(DISTINCT ip) as different_ips
FROM `zbp_advancedstats_visits`
WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
AND canvas_fingerprint IS NOT NULL
GROUP BY canvas_fingerprint
HAVING visit_count > 50
ORDER BY visit_count DESC;

-- 4. IP 与指纹对应关系（检测代理/VPN）
SELECT 
    ip,
    COUNT(DISTINCT canvas_fingerprint) as fingerprint_count,
    GROUP_CONCAT(DISTINCT canvas_fingerprint) as fingerprints
FROM `zbp_advancedstats_visits`
WHERE canvas_fingerprint IS NOT NULL
GROUP BY ip
HAVING fingerprint_count > 3
ORDER BY fingerprint_count DESC;

-- 5. 指纹收集率统计
SELECT 
    DATE(visit_time) as date,
    COUNT(*) as total_visits,
    SUM(CASE WHEN canvas_fingerprint IS NOT NULL THEN 1 ELSE 0 END) as with_fingerprint,
    ROUND(SUM(CASE WHEN canvas_fingerprint IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as collection_rate
FROM `zbp_advancedstats_visits`
WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(visit_time)
ORDER BY date DESC;

-- =====================================================
-- 维护查询
-- =====================================================

-- 清理90天前的数据
DELETE FROM `zbp_advancedstats_visits`
WHERE visit_time < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- 清理空指纹的旧数据
DELETE FROM `zbp_advancedstats_visits`
WHERE canvas_fingerprint IS NULL
AND visit_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 优化表
OPTIMIZE TABLE `zbp_advancedstats_visits`;

-- =====================================================
-- 完成
-- =====================================================
-- 
-- 执行后请验证：
-- 1. 字段是否添加成功
-- 2. 索引是否创建成功
-- 3. 是否能正常插入和查询数据
-- 
-- =====================================================

