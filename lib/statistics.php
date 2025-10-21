<?php
/**
 * Tpure 主题 - 访问统计模块（基于 Z-BlogPHP Cache 框架 + Redis）
 * 
 * @package Tpure
 * @version 5.0.7
 * @author TOYEAN
 * @link https://www.toyean.com/
 */

// 安全检查：确保在 Z-BlogPHP 环境中运行
// 注意：由于此文件通过 include.php 或 admin 页面加载，ZBP_PATH 和 $zbp 都应该已存在
// 移除过于严格的检查以避免兼容性问题

/**
 * 访问统计类（Redis + MySQL 双存储方案）
 */
class TpureStatistics {
    
    /**
     * 数据库表名（不含前缀）
     */
    const TABLE_STATS = 'tpure_visit_stats';
    
    /**
     * 获取完整表名（含前缀）
     * 
     * @return string
     */
    private static function getTableName() {
        global $zbp;
        // Z-BlogPHP 数据库前缀属性是 dbpre，不是 prefix
        return $zbp->db->dbpre . self::TABLE_STATS;
    }
    
    /**
     * 页面类型常量
     */
    const PAGE_INDEX = 'index';
    const PAGE_ARTICLE = 'article';
    const PAGE_CATEGORY = 'category';
    const PAGE_TAG = 'tag';
    
    /**
     * Redis 键前缀
     */
    const REDIS_PREFIX = 'tpure:visit:';
    const REDIS_POPULAR_PREFIX = 'tpure:popular:';
    const REDIS_ONLINE_PREFIX = 'tpure:online:';
    
    /**
     * 初始化统计表
     * 
     * @return bool
     */
    public static function install() {
        global $zbp;
        
        // 创建统计表
        $sql = "CREATE TABLE IF NOT EXISTS `" . self::getTableName() . "` (
          `stat_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '统计ID',
          `stat_PageType` varchar(20) NOT NULL COMMENT '页面类型',
          `stat_PageID` int(11) NOT NULL DEFAULT '0' COMMENT '页面ID',
          `stat_Date` date NOT NULL COMMENT '统计日期',
          `stat_VisitCount` int(11) NOT NULL DEFAULT '0' COMMENT '访问次数',
          `stat_UniqueVisitors` int(11) NOT NULL DEFAULT '0' COMMENT '独立访客数',
          `stat_CreateTime` datetime NOT NULL COMMENT '创建时间',
          `stat_UpdateTime` datetime NOT NULL COMMENT '更新时间',
          PRIMARY KEY (`stat_ID`),
          UNIQUE KEY `idx_unique_visit` (`stat_PageType`, `stat_PageID`, `stat_Date`),
          KEY `idx_page_type` (`stat_PageType`),
          KEY `idx_date` (`stat_Date`),
          KEY `idx_visit_count` (`stat_VisitCount`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Tpure主题访问统计表';";
        
        $result = $zbp->db->QueryMulit($sql);
        
        if ($result) {
            tpure_log('访问统计表创建成功', 'INFO');
            
            // 检查 Z-BlogPHP Cache 框架是否可用
            if (self::isRedisAvailable()) {
                tpure_log('Redis 缓存可用，将使用 Redis 进行实时统计', 'INFO');
            } else {
                tpure_log('Redis 不可用，将直接写入 MySQL', 'WARNING');
            }
            
            return true;
        } else {
            tpure_log('访问统计表创建失败: ' . $zbp->db->last_error, 'ERROR');
            return false;
        }
    }
    
    /**
     * 检查统计表是否存在
     * 
     * @return bool
     */
    public static function checkTableExists() {
        global $zbp;
        
        // 🛡️ 安全检查：确保数据库已初始化
        if (!isset($zbp) || !is_object($zbp) || !isset($zbp->db) || !is_object($zbp->db)) {
            if (function_exists('tpure_log')) {
                tpure_log('检查统计表跳过：数据库未就绪', 'DEBUG');
            }
            return false;
        }
        
        try {
            $tableName = self::getTableName();
            $sql = "SHOW TABLES LIKE '{$tableName}'";
            $result = $zbp->db->Query($sql);
            
            // 检查是否有结果（使用 count() 更兼容）
            if ($result && is_array($result) && count($result) > 0) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            if (function_exists('tpure_log')) {
                tpure_log('检查统计表失败: ' . $e->getMessage(), 'ERROR');
            }
            return false;
        }
    }
    
    /**
     * 检查 zbpcache 插件是否可用
     * 
     * @return bool
     */
    public static function isRedisAvailable() {
        global $zbpcache;
        
        // 检查 zbpcache 插件是否已加载
        if (!isset($zbpcache) || !is_object($zbpcache)) {
            return false;
        }
        
        try {
            // 测试写入读取
            $testKey = 'tpure_cache_test';
            $zbpcache->Set($testKey, 'test', 10);
            $result = $zbpcache->Get($testKey);
            $zbpcache->Del($testKey);
            
            return $result === 'test';
        } catch (Exception $e) {
            if (function_exists('tpure_log')) {
                tpure_log('zbpcache 检测失败: ' . $e->getMessage(), 'WARNING');
            }
            return false;
        }
    }
    
    /**
     * 记录访问（写入 Redis）
     * 
     * @param string $pageType 页面类型
     * @param int $pageID 页面ID（首页为0）
     * @return bool
     */
    public static function recordVisit($pageType, $pageID = 0) {
        global $zbp;
        
        // 🛡️ 安全检查：确保数据库已初始化
        if (!isset($zbp) || !is_object($zbp) || !isset($zbp->db) || !is_object($zbp->db)) {
            if (function_exists('tpure_log')) {
                tpure_log("访问记录跳过：数据库未就绪", 'DEBUG');
            }
            return false; // 静默失败，不影响页面显示
        }
        
        // 验证页面类型
        if (!self::isValidPageType($pageType)) {
            if (function_exists('tpure_log')) {
                tpure_log("访问记录跳过：无效页面类型 ({$pageType})", 'DEBUG');
            }
            return false;
        }
        
        // 过滤条件
        if (self::shouldSkipRecording()) {
            if (function_exists('tpure_log')) {
                tpure_log("访问记录跳过：触发过滤条件", 'DEBUG');
            }
            return false;
        }
        
        // 验证页面ID
        $pageID = intval($pageID);
        
        // 当前日期
        $today = date('Y-m-d');
        
        // 访客IP
        $ip = tpure_get_client_ip();
        
        // 记录日志
        if (function_exists('tpure_log')) {
            tpure_log("记录访问：类型={$pageType}, ID={$pageID}, IP={$ip}, 日期={$today}", 'INFO');
        }
        
        // 如果 Redis 可用，写入 Redis
        if (self::isRedisAvailable()) {
            $result = self::recordToRedis($pageType, $pageID, $today, $ip);
            if (function_exists('tpure_log')) {
                tpure_log("Redis记录结果: " . ($result ? '成功' : '失败'), $result ? 'INFO' : 'ERROR');
            }
            return $result;
        } else {
            // Redis 不可用，直接写入 MySQL
            $result = self::recordToMySQL($pageType, $pageID, $today, $ip);
            if (function_exists('tpure_log')) {
                tpure_log("MySQL记录结果: " . ($result ? '成功' : '失败'), $result ? 'INFO' : 'ERROR');
            }
            return $result;
        }
    }
    
    /**
     * 记录到 zbpcache (Redis)
     * 
     * @param string $pageType
     * @param int $pageID
     * @param string $date
     * @param string $ip
     * @return bool
     */
    private static function recordToRedis($pageType, $pageID, $date, $ip) {
        global $zbpcache;
        
        if (!isset($zbpcache)) {
            return self::recordToMySQL($pageType, $pageID, $date, $ip);
        }
        
        try {
            // 构建 Redis 键名
            $visitKey = self::REDIS_PREFIX . "{$date}:{$pageType}:{$pageID}";
            $ipSetKey = $visitKey . ':ips';
            $popularKey = self::REDIS_POPULAR_PREFIX . $pageType;
            
            // 1. 访问计数 +1 (Hash)
            $visitData = $zbpcache->Get($visitKey);
            if (!$visitData) {
                $visitData = array('count' => 0, 'uv' => 0);
            }
            $visitData['count'] = intval($visitData['count']) + 1;
            
            // 2. 独立访客去重 (Set)
            $ipSet = $zbpcache->Get($ipSetKey);
            if (!$ipSet || !is_array($ipSet)) {
                $ipSet = array();
            }
            if (!in_array($ip, $ipSet)) {
                $ipSet[] = $ip;
                $visitData['uv'] = count($ipSet);
            }
            
            // 保存到 zbpcache（3天过期 = 259200秒）
            $zbpcache->Set($visitKey, $visitData, 259200);
            $zbpcache->Set($ipSetKey, $ipSet, 259200);
            
            // 3. 热门内容排行
            $popularData = $zbpcache->Get($popularKey);
            if (!$popularData || !is_array($popularData)) {
                $popularData = array();
            }
            if (!isset($popularData[$pageID])) {
                $popularData[$pageID] = 0;
            }
            $popularData[$pageID]++;
            
            // 按访问次数降序排序
            arsort($popularData);
            
            // 只保留 TOP 100
            $popularData = array_slice($popularData, 0, 100, true);
            
            // 保存到 zbpcache（永久 = 0秒）
            $zbpcache->Set($popularKey, $popularData, 0);
            
            // 4. 实时在线人数
            self::updateOnlineUsers($ip);
            
            return true;
            
        } catch (Exception $e) {
            if (function_exists('tpure_log')) {
                tpure_log('zbpcache 写入失败: ' . $e->getMessage(), 'ERROR');
            }
            // 降级到 MySQL
            return self::recordToMySQL($pageType, $pageID, $date, $ip);
        }
    }
    
    /**
     * 记录到 MySQL
     * 
     * @param string $pageType
     * @param int $pageID
     * @param string $date
     * @param string $ip
     * @return bool
     */
    private static function recordToMySQL($pageType, $pageID, $date, $ip) {
        global $zbp;
        
        $table = self::getTableName();
        
        // 使用 INSERT ... ON DUPLICATE KEY UPDATE 简化逻辑
        // 不依赖缓存，独立访客数暂时设为 0
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO `{$table}` 
                (stat_PageType, stat_PageID, stat_Date, stat_VisitCount, stat_UniqueVisitors, stat_CreateTime, stat_UpdateTime) 
                VALUES 
                ('{$pageType}', {$pageID}, '{$date}', 1, 0, '{$now}', '{$now}')
                ON DUPLICATE KEY UPDATE 
                stat_VisitCount = stat_VisitCount + 1,
                stat_UpdateTime = '{$now}'";
        
        $zbp->db->QueryMulit($sql);
        
        return true;
    }
    
    /**
     * 更新实时在线人数
     * 
     * @param string $ip
     */
    private static function updateOnlineUsers($ip) {
        global $zbpcache;
        
        if (!isset($zbpcache)) {
            return;
        }
        
        try {
            $onlineKey = self::REDIS_ONLINE_PREFIX . 'users';
            
            // 获取在线用户列表
            $onlineUsers = $zbpcache->Get($onlineKey);
            if (!$onlineUsers || !is_array($onlineUsers)) {
                $onlineUsers = array();
            }
            
            // 当前时间戳
            $now = time();
            
            // 清理5分钟前的用户
            $fiveMinutesAgo = $now - 300;
            foreach ($onlineUsers as $userIp => $timestamp) {
                if ($timestamp < $fiveMinutesAgo) {
                    unset($onlineUsers[$userIp]);
                }
            }
            
            // 添加/更新当前用户
            $onlineUsers[$ip] = $now;
            
            // 保存（10分钟过期 = 600秒）
            $zbpcache->Set($onlineKey, $onlineUsers, 600);
            
        } catch (Exception $e) {
            // 静默失败，不影响主流程
        }
    }
    
    /**
     * 获取实时在线人数
     * 
     * @return int
     */
    public static function getOnlineCount() {
        global $zbpcache;
        
        if (!isset($zbpcache)) {
            return 0;
        }
        
        try {
            $onlineKey = self::REDIS_ONLINE_PREFIX . 'users';
            
            $onlineUsers = $zbpcache->Get($onlineKey);
            if (!$onlineUsers || !is_array($onlineUsers)) {
                return 0;
            }
            
            // 清理过期用户
            $now = time();
            $fiveMinutesAgo = $now - 300;
            $count = 0;
            
            foreach ($onlineUsers as $timestamp) {
                if ($timestamp >= $fiveMinutesAgo) {
                    $count++;
                }
            }
            
            return $count;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * 从 Redis 聚合数据到 MySQL（定时任务）
     * 
     * @param string $date 日期（默认昨天）
     * @return int 聚合条数
     */
    public static function aggregateFromRedis($date = '') {
        global $zbp, $zbpcache;
        
        if (empty($date)) {
            $date = date('Y-m-d', strtotime('-1 day'));
        }
        
        if (!isset($zbpcache) || !self::isRedisAvailable()) {
            if (function_exists('tpure_log')) {
                tpure_log('zbpcache 不可用，跳过聚合', 'WARNING');
            }
            return 0;
        }
        
        try {
            $table = self::getTableName();
            $aggregated = 0;
            
            // 遍历所有页面类型
            $pageTypes = array(self::PAGE_INDEX, self::PAGE_ARTICLE, self::PAGE_CATEGORY, self::PAGE_TAG);
            
            foreach ($pageTypes as $pageType) {
                // 获取热门排行榜（包含了所有有访问的 pageID）
                $popularKey = self::REDIS_POPULAR_PREFIX . $pageType;
                $popularData = $zbpcache->Get($popularKey);
                
                if (!$popularData || !is_array($popularData)) {
                    continue;
                }
                
                // 遍历每个 pageID
                foreach ($popularData as $pageID => $score) {
                    $visitKey = self::REDIS_PREFIX . "{$date}:{$pageType}:{$pageID}";
                    $ipSetKey = $visitKey . ':ips';
                    
                    $visitData = $zbpcache->Get($visitKey);
                    $ipSet = $zbpcache->Get($ipSetKey);
                    
                    if (!$visitData || !is_array($visitData)) {
                        continue;
                    }
                    
                    $visitCount = intval($visitData['count']);
                    $uniqueVisitors = is_array($ipSet) ? count($ipSet) : 0;
                    
                    if ($visitCount > 0) {
                        // 写入或更新 MySQL
                        $now = date('Y-m-d H:i:s');
                        $sql = "INSERT INTO `{$table}` 
                                (stat_PageType, stat_PageID, stat_Date, stat_VisitCount, stat_UniqueVisitors, stat_CreateTime, stat_UpdateTime) 
                                VALUES 
                                ('{$pageType}', {$pageID}, '{$date}', {$visitCount}, {$uniqueVisitors}, '{$now}', '{$now}')
                                ON DUPLICATE KEY UPDATE 
                                stat_VisitCount = stat_VisitCount + {$visitCount},
                                stat_UniqueVisitors = {$uniqueVisitors},
                                stat_UpdateTime = '{$now}'";
                        
                        $zbp->db->QueryMulit($sql);
                        $aggregated++;
                        
                        // 删除 zbpcache 中的数据（已聚合）
                        $zbpcache->Del($visitKey);
                        $zbpcache->Del($ipSetKey);
                    }
                }
            }
            
            if (function_exists('tpure_log')) {
                tpure_log("zbpcache 数据聚合完成，共聚合 {$aggregated} 条记录", 'INFO');
            }
            
            // 清除查询缓存
            self::clearStatsCache();
            
            return $aggregated;
            
        } catch (Exception $e) {
            if (function_exists('tpure_log')) {
                tpure_log('zbpcache 聚合失败: ' . $e->getMessage(), 'ERROR');
            }
            return 0;
        }
    }
    
    /**
     * 获取访问统计（合并 Redis 和 MySQL 数据）
     * 
     * @param string $pageType 页面类型（空=所有）
     * @param int $pageID 页面ID（0=所有）
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param int $limit 返回条数
     * @return array
     */
    public static function getStats($pageType = '', $pageID = 0, $startDate = '', $endDate = '', $limit = 0) {
        global $zbp;
        
        // 直接从 MySQL 查询（不使用缓存）
        $mysqlData = self::getStatsFromMySQL($pageType, $pageID, $startDate, $endDate, $limit);
        
        return $mysqlData;
    }
    
    /**
     * 从 MySQL 查询统计数据
     * 
     * @param string $pageType
     * @param int $pageID
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */
    private static function getStatsFromMySQL($pageType, $pageID, $startDate, $endDate, $limit) {
        global $zbp;
        
        // 构建查询条件
        $where = array();
        
        if (!empty($pageType) && self::isValidPageType($pageType)) {
            $where[] = "stat_PageType = '{$pageType}'";
        }
        
        if ($pageID > 0) {
            $where[] = "stat_PageID = " . intval($pageID);
        }
        
        if (!empty($startDate)) {
            $where[] = "stat_Date >= '{$startDate}'";
        }
        
        if (!empty($endDate)) {
            $where[] = "stat_Date <= '{$endDate}'";
        }
        
        $whereClause = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        
        // 构建查询SQL
        $table = self::getTableName();
        $sql = "SELECT * FROM `{$table}`{$whereClause} ORDER BY stat_Date DESC, stat_VisitCount DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        // 执行查询
        $result = $zbp->db->Query($sql);
        
        return $result ? $result : array();
    }
    
    /**
     * 从 Redis 获取今日实时统计
     * 
     * @param string $pageType
     * @param int $pageID
     * @return array
     */
    private static function getTodayStatsFromRedis($pageType, $pageID) {
        global $zbpcache;
        
        if (!isset($zbpcache) || !self::isRedisAvailable()) {
            return array();
        }
        
        try {
            $today = date('Y-m-d');
            $result = array();
            
            // 如果指定了 pageType 和 pageID
            if (!empty($pageType) && $pageID > 0) {
                $visitKey = self::REDIS_PREFIX . "{$today}:{$pageType}:{$pageID}";
                $ipSetKey = $visitKey . ':ips';
                
                $visitData = $zbpcache->Get($visitKey);
                $ipSet = $zbpcache->Get($ipSetKey);
                
                if ($visitData && is_array($visitData)) {
                    $result[] = array(
                        'stat_PageType' => $pageType,
                        'stat_PageID' => $pageID,
                        'stat_Date' => $today,
                        'stat_VisitCount' => intval($visitData['count']),
                        'stat_UniqueVisitors' => is_array($ipSet) ? count($ipSet) : 0,
                        'stat_UpdateTime' => date('Y-m-d H:i:s')
                    );
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            if (function_exists('tpure_log')) {
                tpure_log('从 zbpcache 获取今日数据失败: ' . $e->getMessage(), 'ERROR');
            }
            return array();
        }
    }
    
    /**
     * 合并统计数据
     * 
     * @param array $mysqlData
     * @param array $redisData
     * @return array
     */
    private static function mergeStats($mysqlData, $redisData) {
        if (empty($redisData)) {
            return $mysqlData;
        }
        
        // 构建 MySQL 数据的索引
        $index = array();
        foreach ($mysqlData as $i => $row) {
            $key = $row['stat_PageType'] . '_' . $row['stat_PageID'] . '_' . $row['stat_Date'];
            $index[$key] = $i;
        }
        
        // 合并 Redis 数据
        foreach ($redisData as $row) {
            $key = $row['stat_PageType'] . '_' . $row['stat_PageID'] . '_' . $row['stat_Date'];
            
            if (isset($index[$key])) {
                // 累加
                $mysqlData[$index[$key]]['stat_VisitCount'] += $row['stat_VisitCount'];
                $mysqlData[$index[$key]]['stat_UniqueVisitors'] = $row['stat_UniqueVisitors'];
            } else {
                // 新增
                $mysqlData[] = $row;
            }
        }
        
        return $mysqlData;
    }
    
    /**
     * 获取热门内容（从 Redis 或 MySQL）
     * 
     * @param string $pageType 页面类型
     * @param int $days 统计天数（0=所有时间）
     * @param int $limit 返回条数
     * @return array
     */
    public static function getPopularContent($pageType, $days = 7, $limit = 10) {
        global $zbp;
        
        // 直接从 MySQL 获取（不使用缓存）
        $popularContent = self::getPopularFromMySQL($pageType, $days, $limit);
        
        return $popularContent;
    }
    
    /**
     * 从 Redis 获取热门内容
     * 
     * @param string $pageType
     * @param int $limit
     * @return array
     */
    private static function getPopularFromRedis($pageType, $limit) {
        global $zbp, $zbpcache;
        
        if (!isset($zbpcache)) {
            return array();
        }
        
        try {
            $popularKey = self::REDIS_POPULAR_PREFIX . $pageType;
            
            $popularData = $zbpcache->Get($popularKey);
            if (!$popularData || !is_array($popularData)) {
                return array();
            }
            
            // 取 TOP N
            $topItems = array_slice($popularData, 0, $limit, true);
            
            // 获取详细信息
            $result = array();
            foreach ($topItems as $pageID => $visits) {
                $item = array(
                    'id' => $pageID,
                    'total_visits' => $visits,
                    'total_unique_visitors' => 0,
                    'title' => '',
                    'url' => ''
                );
                
                // 根据页面类型获取详细信息
                switch ($pageType) {
                    case self::PAGE_ARTICLE:
                        $article = $zbp->GetPostByID($pageID);
                        if ($article && $article->ID > 0) {
                            $item['title'] = $article->Title;
                            $item['url'] = $article->Url;
                        }
                        break;
                    
                    case self::PAGE_CATEGORY:
                        $category = $zbp->GetCategoryByID($pageID);
                        if ($category && $category->ID > 0) {
                            $item['title'] = $category->Name;
                            $item['url'] = $category->Url;
                        }
                        break;
                    
                    case self::PAGE_TAG:
                        $tag = $zbp->GetTagByID($pageID);
                        if ($tag && $tag->ID > 0) {
                            $item['title'] = $tag->Name;
                            $item['url'] = $tag->Url;
                        }
                        break;
                }
                
                if (!empty($item['title'])) {
                    $result[] = $item;
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            tpure_log('从 Redis 获取热门内容失败: ' . $e->getMessage(), 'ERROR');
            return array();
        }
    }
    
    /**
     * 从 MySQL 获取热门内容
     * 
     * @param string $pageType
     * @param int $days
     * @param int $limit
     * @return array
     */
    private static function getPopularFromMySQL($pageType, $days, $limit) {
        global $zbp;
        
        // 构建查询条件
        $where = "stat_PageType = '{$pageType}'";
        
        if ($days > 0) {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $where .= " AND stat_Date >= '{$startDate}'";
        }
        
        // 查询SQL（按访问次数求和分组）
        $table = self::getTableName();
        $sql = "SELECT stat_PageID, 
                       SUM(stat_VisitCount) as total_visits,
                       SUM(stat_UniqueVisitors) as total_unique_visitors
                FROM `{$table}` 
                WHERE {$where}
                GROUP BY stat_PageID
                ORDER BY total_visits DESC
                LIMIT " . intval($limit);
        
        $result = $zbp->db->Query($sql);
        
        // 获取详细信息
        $popularContent = array();
        
        if ($result && count($result) > 0) {
            foreach ($result as $row) {
                $pageID = intval($row['stat_PageID']);
                $item = array(
                    'id' => $pageID,
                    'total_visits' => intval($row['total_visits']),
                    'total_unique_visitors' => intval($row['total_unique_visitors']),
                    'title' => '',
                    'url' => ''
                );
                
                // 根据页面类型获取详细信息
                switch ($pageType) {
                    case self::PAGE_ARTICLE:
                        $article = $zbp->GetPostByID($pageID);
                        if ($article && $article->ID > 0) {
                            $item['title'] = $article->Title;
                            $item['url'] = $article->Url;
                        }
                        break;
                    
                    case self::PAGE_CATEGORY:
                        $category = $zbp->GetCategoryByID($pageID);
                        if ($category && $category->ID > 0) {
                            $item['title'] = $category->Name;
                            $item['url'] = $category->Url;
                        }
                        break;
                    
                    case self::PAGE_TAG:
                        $tag = $zbp->GetTagByID($pageID);
                        if ($tag && $tag->ID > 0) {
                            $item['title'] = $tag->Name;
                            $item['url'] = $tag->Url;
                        }
                        break;
                }
                
                if (!empty($item['title'])) {
                    $popularContent[] = $item;
                }
            }
        }
        
        return $popularContent;
    }
    
    /**
     * 获取总访问量
     * 
     * @param string $pageType 页面类型（空=所有）
     * @param int $days 统计天数（0=所有时间）
     * @return int
     */
    public static function getTotalVisits($pageType = '', $days = 0) {
        global $zbp;
        
        // 直接从 MySQL 查询（不使用缓存）
        $total = self::getTotalVisitsFromMySQL($pageType, $days);
        
        return $total;
    }
    
    /**
     * 从 MySQL 获取总访问量
     * 
     * @param string $pageType
     * @param int $days
     * @return int
     */
    private static function getTotalVisitsFromMySQL($pageType, $days) {
        global $zbp;
        
        // 构建查询条件
        $where = array();
        
        if (!empty($pageType) && self::isValidPageType($pageType)) {
            $where[] = "stat_PageType = '{$pageType}'";
        }
        
        if ($days > 0) {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $where[] = "stat_Date >= '{$startDate}'";
        }
        
        $whereClause = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        
        // 查询SQL
        $table = self::getTableName();
        $sql = "SELECT SUM(stat_VisitCount) as total FROM `{$table}`{$whereClause}";
        
        $result = $zbp->db->Query($sql);
        
        $total = 0;
        if ($result && count($result) > 0) {
            $total = intval($result[0]['total']);
        }
        
        return $total;
    }
    
    /**
     * 从 Redis 获取今日总访问量
     * 
     * @param string $pageType
     * @return int
     */
    private static function getTodayTotalFromRedis($pageType) {
        global $zbpcache;
        
        if (!isset($zbpcache) || !self::isRedisAvailable()) {
            return 0;
        }
        
        try {
            $total = 0;
            
            // 从热门排行榜统计
            if (!empty($pageType)) {
                $popularKey = self::REDIS_POPULAR_PREFIX . $pageType;
                $popularData = $zbpcache->Get($popularKey);
                
                if ($popularData && is_array($popularData)) {
                    $today = date('Y-m-d');
                    
                    foreach ($popularData as $pageID => $score) {
                        $visitKey = self::REDIS_PREFIX . "{$today}:{$pageType}:{$pageID}";
                        $visitData = $zbpcache->Get($visitKey);
                        
                        if ($visitData && is_array($visitData) && isset($visitData['count'])) {
                            $total += intval($visitData['count']);
                        }
                    }
                }
            }
            
            return $total;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * 清理过期数据
     * 
     * @param int $daysToKeep 保留天数（默认365天）
     * @return int 删除条数
     */
    public static function cleanExpiredData($daysToKeep = 365) {
        global $zbp;
        
        $expireDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        $table = self::getTableName();
        
        $sql = "DELETE FROM `{$table}` WHERE stat_Date < '{$expireDate}'";
        
        $zbp->db->QueryMulit($sql);
        
        $deleted = $zbp->db->affected_rows;
        
        if ($deleted > 0) {
            tpure_log("清理过期统计数据: 删除 {$deleted} 条记录", 'INFO');
        }
        
        return $deleted;
    }
    
    /**
     * 验证页面类型
     * 
     * @param string $pageType
     * @return bool
     */
    private static function isValidPageType($pageType) {
        return in_array($pageType, array(
            self::PAGE_INDEX,
            self::PAGE_ARTICLE,
            self::PAGE_CATEGORY,
            self::PAGE_TAG
        ));
    }
    
    /**
     * 是否应该跳过记录
     * 
     * @return bool
     */
    private static function shouldSkipRecording() {
        global $zbp;
        
        // 后台不记录
        if (defined('ZBP_IN_ADMIN') && ZBP_IN_ADMIN) {
            return true;
        }
        
        // 爬虫不记录
        if (self::isBot()) {
            return true;
        }
        
        // 检查是否启用管理员过滤（默认不过滤，方便测试）
        $filterAdmin = false; // 改为 true 则不记录管理员访问
        try {
            $config = $zbp->Config('tpure');
            if (isset($config->StatsFilterAdmin)) {
                $filterAdmin = ($config->StatsFilterAdmin == '1');
            }
        } catch (Exception $e) {
            // 配置不存在，使用默认值
        }
        
        // 管理员过滤（可配置）
        if ($filterAdmin && $zbp->user->ID > 0 && $zbp->user->Level == 1) {
            if (function_exists('tpure_log')) {
                tpure_log('管理员访问已过滤（用户: ' . $zbp->user->Name . '）', 'DEBUG');
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * 检测是否为爬虫
     * 
     * @return bool
     */
    private static function isBot() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }
        
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $bots = array('bot', 'spider', 'crawler', 'slurp', 'curl', 'wget');
        
        foreach ($bots as $bot) {
            if (strpos($userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 清除统计缓存
     * 
     * @param string $pageType
     * @param int $pageID
     */
    private static function clearStatsCache($pageType = '', $pageID = 0) {
        global $zbpcache;
        
        if (!isset($zbpcache)) {
            return;
        }
        
        // 清除相关缓存（zbpcache 插件）
        try {
            // 清除统计查询缓存
            $zbpcache->Del("visit_stats_{$pageType}");
            $zbpcache->Del("popular_content_{$pageType}");
            $zbpcache->Del("total_visits_{$pageType}");
        } catch (Exception $e) {
            // 静默失败
        }
    }
}

/**
 * 获取客户端真实IP
 * 
 * @return string
 */
function tpure_get_client_ip() {
    $ip = '';
    
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // 处理多个IP的情况（取第一个）
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    
    // 验证IP格式
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    
    return $ip;
}

/**
 * 便捷函数：获取统计数据
 * 
 * @param string $pageType
 * @param int $pageID
 * @param string $startDate
 * @param string $endDate
 * @param int $limit
 * @return array
 */
function tpure_get_visit_stats($pageType = '', $pageID = 0, $startDate = '', $endDate = '', $limit = 0) {
    return TpureStatistics::getStats($pageType, $pageID, $startDate, $endDate, $limit);
}

/**
 * 便捷函数：获取热门内容
 * 
 * @param string $pageType
 * @param int $days
 * @param int $limit
 * @return array
 */
function tpure_get_popular_content($pageType, $days = 7, $limit = 10) {
    return TpureStatistics::getPopularContent($pageType, $days, $limit);
}

/**
 * 便捷函数：获取总访问量
 * 
 * @param string $pageType
 * @param int $days
 * @return int
 */
function tpure_get_total_visits($pageType = '', $days = 0) {
    return TpureStatistics::getTotalVisits($pageType, $days);
}

/**
 * 便捷函数：获取实时在线人数
 * 
 * @return int
 */
function tpure_get_online_count() {
    return TpureStatistics::getOnlineCount();
}

/**
 * 便捷函数：聚合 Redis 数据到 MySQL（定时任务调用）
 * 
 * @param string $date
 * @return int
 */
function tpure_aggregate_visit_data($date = '') {
    return TpureStatistics::aggregateFromRedis($date);
}

/**
 * 钩子函数：自动记录页面访问
 * 在每次页面加载时自动调用
 * 
 * @return void
 */
function tpure_auto_record_visit_hook() {
    global $zbp, $type, $id;
    
    // 🛡️ 安全检查：确保 $zbp 和数据库已初始化
    if (!isset($zbp) || !is_object($zbp)) {
        return; // 静默失败，不影响页面显示
    }
    
    // 🛡️ 安全检查：确保数据库连接已就绪
    if (!isset($zbp->db) || !is_object($zbp->db)) {
        if (function_exists('tpure_log')) {
            tpure_log("访问统计跳过：数据库未就绪", 'DEBUG');
        }
        return; // 数据库未就绪，跳过统计
    }
    
    // 记录钩子被调用
    if (function_exists('tpure_log')) {
        tpure_log("访问统计钩子被触发：type={$type}, id=" . (isset($id) ? $id : 'null'), 'DEBUG');
    }
    
    // 只记录前台访问，排除后台和特殊页面
    if (defined('ZBP_IN_ADMIN') || (isset($zbp->isAdmin) && $zbp->isAdmin)) {
        if (function_exists('tpure_log')) {
            tpure_log("访问统计跳过：后台访问", 'DEBUG');
        }
        return;
    }
    
    try {
        // 确定页面类型和ID
        $pageType = TpureStatistics::PAGE_INDEX;
        $pageID = 0;
        
        switch ($type) {
            case 'index':
                $pageType = TpureStatistics::PAGE_INDEX;
                $pageID = 0;
                break;
                
            case 'article':
            case 'post':
                $pageType = TpureStatistics::PAGE_ARTICLE;
                $pageID = isset($id) ? intval($id) : 0;
                break;
                
            case 'category':
                $pageType = TpureStatistics::PAGE_CATEGORY;
                $pageID = isset($id) ? intval($id) : 0;
                break;
                
            case 'tags':
            case 'tag':
                $pageType = TpureStatistics::PAGE_TAG;
                $pageID = isset($id) ? intval($id) : 0;
                break;
                
            case 'page':
                $pageType = TpureStatistics::PAGE_PAGE;
                $pageID = isset($id) ? intval($id) : 0;
                break;
                
            default:
                // 其他类型暂不记录
                if (function_exists('tpure_log')) {
                    tpure_log("访问统计跳过：未支持的页面类型 ({$type})", 'DEBUG');
                }
                return;
        }
        
        // 记录访问
        if (function_exists('tpure_log')) {
            tpure_log("准备记录访问：pageType={$pageType}, pageID={$pageID}", 'DEBUG');
        }
        TpureStatistics::recordVisit($pageType, $pageID);
        
    } catch (Exception $e) {
        // 静默失败，不影响页面显示
        if (function_exists('tpure_log')) {
            tpure_log('记录访问失败: ' . $e->getMessage(), 'ERROR');
        }
    }
}

