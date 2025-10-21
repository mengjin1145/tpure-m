<?php
/**
 * Tpure 主题 - 数据库查询优化模块
 * 
 * @package Tpure
 * @version 5.0.7
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * 数据库查询优化类
 */
class TpureDatabase {
    
    /**
     * 查询性能监控
     * 
     * @var array
     */
    private static $queryStats = array(
        'count' => 0,
        'time' => 0,
        'queries' => array()
    );
    
    /**
     * 预加载缓存
     * 
     * @var array
     */
    private static $preloadCache = array(
        'categories' => array(),
        'members' => array(),
        'tags' => array()
    );
    
    /**
     * 获取热门文章列表（优化版）
     * 
     * 优化点：
     * 1. 使用索引字段排序
     * 2. 只查询必要字段
     * 3. 添加缓存支持
     * 4. 分类过滤优化
     * 
     * @param int $count 数量
     * @param int $cateId 分类ID（可选）
     * @param bool $useCache 是否使用缓存
     * @return array<\Post> 文章列表
     */
    public static function getHotArticles($count = 10, $cateId = 0, $useCache = true) {
        global $zbp;
        
        // 缓存键
        $cacheKey = "hot_articles_{$count}_{$cateId}";
        
        // 尝试从缓存获取
        if ($useCache) {
            $cached = tpure_cache_get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        
        // 构建查询条件（利用索引）
        $w = array(
            array('=', 'log_Status', 0), // log_Status 字段有索引
        );
        
        if ($cateId > 0) {
            // log_CateID 字段有索引
            $w[] = array('=', 'log_CateID', $cateId);
        }
        
        // 使用 log_ViewNums 排序（该字段有索引）
        $order = array('log_ViewNums' => 'DESC', 'log_PostTime' => 'DESC');
        
        // 只查询必要字段（减少内存占用和网络传输）
        $articles = $zbp->GetArticleList(
            array(
                'log_ID',
                'log_CateID',
                'log_AuthorID',
                'log_Title',
                'log_Url',
                'log_Intro',
                'log_PostTime',
                'log_ViewNums',
                'log_CommNums'
            ),
            $w,
            $order,
            array($count),
            null,
            false // 不加载 Metas，按需加载
        );
        
        $queryTime = microtime(true) - $startTime;
        self::logQuery('getHotArticles', $queryTime, array('count' => $count, 'cateId' => $cateId));
        
        // 缓存结果（1小时）
        if ($useCache) {
            tpure_cache_set($cacheKey, $articles, TPURE_CACHE_EXPIRE_HOUR);
        }
        
        return $articles;
    }
    
    /**
     * 获取最新文章列表（优化版）
     * 
     * @param int $count 数量
     * @param int $cateId 分类ID（可选）
     * @param bool $useCache 是否使用缓存
     * @return array<\Post> 文章列表
     */
    public static function getRecentArticles($count = 10, $cateId = 0, $useCache = true) {
        global $zbp;
        
        $cacheKey = "recent_articles_{$count}_{$cateId}";
        
        if ($useCache) {
            $cached = tpure_cache_get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        
        $w = array(
            array('=', 'log_Status', 0),
        );
        
        if ($cateId > 0) {
            $w[] = array('=', 'log_CateID', $cateId);
        }
        
        // 使用 log_PostTime 排序（主键索引的一部分，性能好）
        $order = array('log_PostTime' => 'DESC', 'log_ID' => 'DESC');
        
        $articles = $zbp->GetArticleList(
            array(
                'log_ID',
                'log_CateID',
                'log_AuthorID',
                'log_Title',
                'log_Url',
                'log_Intro',
                'log_PostTime',
                'log_ViewNums',
                'log_CommNums'
            ),
            $w,
            $order,
            array($count),
            null,
            false
        );
        
        $queryTime = microtime(true) - $startTime;
        self::logQuery('getRecentArticles', $queryTime, array('count' => $count, 'cateId' => $cateId));
        
        if ($useCache) {
            tpure_cache_set($cacheKey, $articles, TPURE_CACHE_EXPIRE_HOUR);
        }
        
        return $articles;
    }
    
    /**
     * 获取最新评论（优化版）
     * 
     * @param int $count 数量
     * @param bool $useCache 是否使用缓存
     * @return array<\Comment> 评论列表
     */
    public static function getRecentComments($count = 10, $useCache = true) {
        global $zbp;
        
        $cacheKey = "recent_comments_{$count}";
        
        if ($useCache) {
            $cached = tpure_cache_get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        
        $w = array(
            array('=', 'comm_IsChecking', 0), // 已审核
        );
        
        // 使用 comm_PostTime 排序（有索引）
        $order = array('comm_PostTime' => 'DESC', 'comm_ID' => 'DESC');
        
        $comments = $zbp->GetCommentList(
            '*',
            $w,
            $order,
            array($count),
            null
        );
        
        $queryTime = microtime(true) - $startTime;
        self::logQuery('getRecentComments', $queryTime, array('count' => $count));
        
        if ($useCache) {
            tpure_cache_set($cacheKey, $comments, TPURE_CACHE_EXPIRE_HOUR);
        }
        
        return $comments;
    }
    
    /**
     * 预加载文章关联数据（解决N+1查询问题）
     * 
     * 一次性加载所有相关的分类、作者、标签数据
     * 避免在循环中重复查询数据库
     * 
     * @param array $articles 文章列表
     * @param array $includes 需要预加载的关联数据 ['category', 'author', 'tags']
     * @return array 包含预加载数据的文章列表
     */
    public static function preloadArticleRelations(&$articles, $includes = array('category', 'author')) {
        if (empty($articles)) {
            return $articles;
        }
        
        global $zbp;
        $startTime = microtime(true);
        
        // 收集需要加载的ID
        $cateIds = array();
        $authorIds = array();
        $articleIds = array();
        
        foreach ($articles as $article) {
            if (in_array('category', $includes) && !empty($article->CateID)) {
                $cateIds[] = $article->CateID;
            }
            if (in_array('author', $includes) && !empty($article->AuthorID)) {
                $authorIds[] = $article->AuthorID;
            }
            if (in_array('tags', $includes)) {
                $articleIds[] = $article->ID;
            }
        }
        
        // 去重
        $cateIds = array_unique($cateIds);
        $authorIds = array_unique($authorIds);
        $articleIds = array_unique($articleIds);
        
        // 批量加载分类
        if (!empty($cateIds) && in_array('category', $includes)) {
            $categories = self::batchLoadCategories($cateIds);
            
            // 关联到文章
            foreach ($articles as &$article) {
                if (isset($categories[$article->CateID])) {
                    $article->_preloadedCategory = $categories[$article->CateID];
                }
            }
        }
        
        // 批量加载作者
        if (!empty($authorIds) && in_array('author', $includes)) {
            $authors = self::batchLoadMembers($authorIds);
            
            // 关联到文章
            foreach ($articles as &$article) {
                if (isset($authors[$article->AuthorID])) {
                    $article->_preloadedAuthor = $authors[$article->AuthorID];
                }
            }
        }
        
        // 批量加载标签
        if (!empty($articleIds) && in_array('tags', $includes)) {
            $tagsMap = self::batchLoadTags($articleIds);
            
            // 关联到文章
            foreach ($articles as &$article) {
                if (isset($tagsMap[$article->ID])) {
                    $article->_preloadedTags = $tagsMap[$article->ID];
                } else {
                    $article->_preloadedTags = array();
                }
            }
        }
        
        $queryTime = microtime(true) - $startTime;
        self::logQuery('preloadArticleRelations', $queryTime, array(
            'articles' => count($articles),
            'includes' => implode(',', $includes)
        ));
        
        return $articles;
    }
    
    /**
     * 批量加载分类
     * 
     * @param array $cateIds 分类ID数组
     * @return array 分类数组（以ID为键）
     */
    private static function batchLoadCategories($cateIds) {
        global $zbp;
        
        // 检查预加载缓存
        $needLoad = array();
        $result = array();
        
        foreach ($cateIds as $id) {
            if (isset(self::$preloadCache['categories'][$id])) {
                $result[$id] = self::$preloadCache['categories'][$id];
            } else {
                $needLoad[] = $id;
            }
        }
        
        // 需要加载的ID
        if (!empty($needLoad)) {
            $w = array(
                array('IN', 'cate_ID', $needLoad)
            );
            
            $categories = $zbp->GetCategoryList('*', $w);
            
            foreach ($categories as $cate) {
                $result[$cate->ID] = $cate;
                self::$preloadCache['categories'][$cate->ID] = $cate;
            }
        }
        
        return $result;
    }
    
    /**
     * 批量加载用户
     * 
     * @param array $memberIds 用户ID数组
     * @return array 用户数组（以ID为键）
     */
    private static function batchLoadMembers($memberIds) {
        global $zbp;
        
        $needLoad = array();
        $result = array();
        
        foreach ($memberIds as $id) {
            if (isset(self::$preloadCache['members'][$id])) {
                $result[$id] = self::$preloadCache['members'][$id];
            } else {
                $needLoad[] = $id;
            }
        }
        
        if (!empty($needLoad)) {
            $w = array(
                array('IN', 'mem_ID', $needLoad)
            );
            
            $members = $zbp->GetMemberList('*', $w);
            
            foreach ($members as $member) {
                $result[$member->ID] = $member;
                self::$preloadCache['members'][$member->ID] = $member;
            }
        }
        
        return $result;
    }
    
    /**
     * 批量加载文章标签
     * 
     * @param array $articleIds 文章ID数组
     * @return array 标签数组（以文章ID为键）
     */
    private static function batchLoadTags($articleIds) {
        global $zbp;
        
        // Z-BlogPHP的标签存储在关联表中
        // 这里需要查询 zbp_tag 和 zbp_post_tag 表
        
        $sql = "SELECT pt.log_ID, t.* 
                FROM " . $zbp->table['Tag'] . " t 
                INNER JOIN " . $zbp->table['Post_Tag'] . " pt ON t.tag_ID = pt.tag_ID 
                WHERE pt.log_ID IN (" . implode(',', array_map('intval', $articleIds)) . ") 
                ORDER BY pt.log_ID, t.tag_Name";
        
        $result = array();
        $rows = $zbp->db->Query($sql);
        
        foreach ($rows as $row) {
            $logId = $row['log_ID'];
            if (!isset($result[$logId])) {
                $result[$logId] = array();
            }
            
            // 创建标签对象
            $tag = new Tag();
            $tag->LoadInfoByArray($row);
            $result[$logId][] = $tag;
        }
        
        return $result;
    }
    
    /**
     * 游标分页查询（替代 OFFSET）
     * 
     * 传统的 OFFSET 分页在大数据量时性能很差
     * 游标分页使用 WHERE id < last_id 的方式，性能更好
     * 
     * @param int $lastId 上一页最后一条记录的ID
     * @param int $limit 每页数量
     * @param int $cateId 分类ID（可选）
     * @param string $orderField 排序字段
     * @param string $orderType 排序方向
     * @return array 文章列表
     */
    public static function cursorPaginate($lastId = 0, $limit = 10, $cateId = 0, $orderField = 'log_ID', $orderType = 'DESC') {
        global $zbp;
        
        $startTime = microtime(true);
        
        $w = array(
            array('=', 'log_Status', 0),
        );
        
        // 游标条件
        if ($lastId > 0) {
            if ($orderType === 'DESC') {
                $w[] = array('<', $orderField, $lastId);
            } else {
                $w[] = array('>', $orderField, $lastId);
            }
        }
        
        // 分类过滤
        if ($cateId > 0) {
            $w[] = array('=', 'log_CateID', $cateId);
        }
        
        // 排序
        $order = array($orderField => $orderType);
        
        // 如果主排序字段不是ID，添加ID作为第二排序字段保证一致性
        if ($orderField !== 'log_ID') {
            $order['log_ID'] = $orderType;
        }
        
        $articles = $zbp->GetArticleList(
            array(
                'log_ID',
                'log_CateID',
                'log_AuthorID',
                'log_Title',
                'log_Url',
                'log_Intro',
                'log_PostTime',
                'log_ViewNums',
                'log_CommNums'
            ),
            $w,
            $order,
            array($limit),
            null,
            false
        );
        
        $queryTime = microtime(true) - $startTime;
        self::logQuery('cursorPaginate', $queryTime, array(
            'lastId' => $lastId,
            'limit' => $limit,
            'orderField' => $orderField
        ));
        
        return $articles;
    }
    
    /**
     * 获取文章总数（优化版，使用缓存）
     * 
     * @param int $cateId 分类ID（可选）
     * @param bool $useCache 是否使用缓存
     * @return int 文章总数
     */
    public static function getArticleCount($cateId = 0, $useCache = true) {
        global $zbp;
        
        $cacheKey = "article_count_{$cateId}";
        
        if ($useCache) {
            $cached = tpure_cache_get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $w = array(
            array('=', 'log_Status', 0),
        );
        
        if ($cateId > 0) {
            $w[] = array('=', 'log_CateID', $cateId);
        }
        
        $count = $zbp->db->Count(
            $zbp->table['Post'],
            $w
        );
        
        if ($useCache) {
            // 文章总数变化不频繁，缓存1天
            tpure_cache_set($cacheKey, $count, TPURE_CACHE_EXPIRE_DAY);
        }
        
        return $count;
    }
    
    /**
     * 记录查询日志（用于性能分析）
     * 
     * @param string $queryName 查询名称
     * @param float $time 执行时间
     * @param array $params 查询参数
     */
    private static function logQuery($queryName, $time, $params = array()) {
        self::$queryStats['count']++;
        self::$queryStats['time'] += $time;
        
        if (defined('TPURE_DEBUG') && TPURE_DEBUG) {
            self::$queryStats['queries'][] = array(
                'name' => $queryName,
                'time' => round($time * 1000, 2) . 'ms',
                'params' => $params
            );
        }
    }
    
    /**
     * 获取查询统计信息
     * 
     * @return array 统计信息
     */
    public static function getQueryStats() {
        return array(
            'count' => self::$queryStats['count'],
            'time' => round(self::$queryStats['time'] * 1000, 2) . 'ms',
            'queries' => self::$queryStats['queries']
        );
    }
    
    /**
     * 清空预加载缓存
     */
    public static function clearPreloadCache() {
        self::$preloadCache = array(
            'categories' => array(),
            'members' => array(),
            'tags' => array()
        );
    }
}

/**
 * 便捷函数：获取热门文章
 * 
 * @param int $count 数量
 * @param int $cateId 分类ID
 * @return array 文章列表
 */
function tpure_get_hot_articles($count = 10, $cateId = 0) {
    return TpureDatabase::getHotArticles($count, $cateId);
}

/**
 * 便捷函数：获取最新文章
 * 
 * @param int $count 数量
 * @param int $cateId 分类ID
 * @return array 文章列表
 */
function tpure_get_recent_articles($count = 10, $cateId = 0) {
    return TpureDatabase::getRecentArticles($count, $cateId);
}

/**
 * 便捷函数：获取最新评论
 * 
 * @param int $count 数量
 * @return array 评论列表
 */
function tpure_get_recent_comments($count = 10) {
    return TpureDatabase::getRecentComments($count);
}

/**
 * 便捷函数：预加载文章关联数据
 * 
 * @param array $articles 文章列表
 * @param array $includes 需要预加载的关联数据
 * @return array 包含预加载数据的文章列表
 */
function tpure_preload_articles(&$articles, $includes = array('category', 'author')) {
    return TpureDatabase::preloadArticleRelations($articles, $includes);
}

/**
 * 便捷函数：游标分页
 * 
 * @param int $lastId 上一页最后一条记录的ID
 * @param int $limit 每页数量
 * @param int $cateId 分类ID
 * @return array 文章列表
 */
function tpure_cursor_paginate($lastId = 0, $limit = 10, $cateId = 0) {
    return TpureDatabase::cursorPaginate($lastId, $limit, $cateId);
}

