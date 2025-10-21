{* Template Name:标签云带侧栏模板 * Template Type:tags *}
<!DOCTYPE html>
<html xml:lang="{$lang['lang_bcp47']}" lang="{$lang['lang_bcp47']}">
<head>
{template:header}
</head>
<body class="{$type}{if GetVars('night','COOKIE') } night{/if}">
<div class="wrapper">
    {template:navbar}
    <div class="main{if $zbp->Config('tpure')->PostFIXMENUON == '1'} fixed{/if}">
        <div class="mask"></div>
        <div class="wrap">
            {if $zbp->Config('tpure')->PostSITEMAPON=='1'}
            <div class="sitemap">{$lang['tpure']['sitemap']}<a href="{$host}">{$zbp->Config('tpure')->PostSITEMAPTXT ? $zbp->Config('tpure')->PostSITEMAPTXT : $lang['tpure']['index']}</a> &gt; 标签列表</div>
            {/if}
            <div{if $zbp->Config('tpure')->PostFIXSIDEBARSTYLE == '0'} id="sticky"{/if}>
                <div class="content listcon">
                   <div class="block custom{if $zbp->Config('tpure')->PostBIGPOSTIMGON == '1'} large{/if}">
                        <h1 class="page-title">标签列表</h1>
                        <div class="tag-filter">
                            <a href="?" class="filter-item{if !GetVars('order','GET')} active{/if}">最新内容</a>
                            <a href="?order=view" class="filter-item{if GetVars('order','GET') == 'view'} active{/if}">最热内容</a>
                        </div>
                        {php}
                        // 分页设置
                        $perPage = 20; // 每页显示20篇文章
                        $currentPage = max(1, intval(GetVars('page', 'GET')));
                        $offset = ($currentPage - 1) * $perPage;
                        
                        // 设置排序方式
                        $order = GetVars('order','GET') == 'view' ? 
                            array('log_ViewNums'=>'DESC') : 
                            array('log_PostTime'=>'DESC');
                        
                        // 获取当前标签的文章总数
                        $totalArticles = $zbp->GetArticleList(
                            array('log_ID'),
                            array(
                                array('=', 'log_Status', 0),
                                array('LIKE', 'log_Tag', '%{' . $tag->ID . '}%')
                            ),
                            null,
                            null,
                            null,
                            false
                        );
                        
                        $totalCount = count($totalArticles);
                        $totalPages = ceil($totalCount / $perPage);
                        
                        // 获取当前页的文章
                        $articles = $zbp->GetArticleList(
                            array('*'),
                            array(
                                array('=', 'log_Status', 0),
                                array('LIKE', 'log_Tag', '%{' . $tag->ID . '}%')
                            ),
                            $order,
                            array($perPage, $offset)
                        );
                        
                        // 显示当前标签信息
                        echo '<div class="tag-info">';
                        echo '<h2 class="current-tag">'.$tag->Name.' <span class="tag-count">（共 '.$totalCount.' 篇文章）</span></h2>';
                        echo '</div>';
                        
                        // 显示文章列表
                        if(count($articles) > 0) {
                            foreach($articles as $article) {
                                // 获取文章标题，去除空格和特殊字符
                                $title = isset($article->Title) ? trim($article->Title) : '';
                                
                                // 处理各种空值情况
                                if(empty($title) || $title == '' || $title == 'null' || $title == 'undefined') {
                                    $title = '（无标题）';
                                }
                                
                                // HTML 转义防止 XSS
                                $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                                
                                // 获取缩略图
                                $thumb = tpure_Thumb($article);
                                $hasThumb = !empty($thumb);
                                
                                echo '<div class="post item tag-article'.($hasThumb ? ' has-thumb' : '').'">';
                                
                                // 显示缩略图
                                if($hasThumb) {
                                    echo '<div class="article-thumb">';
                                    echo '<a href="'.$article->Url.'">';
                                    echo '<img src="'.$thumb.'" alt="'.$title.'" loading="lazy">';
                                    echo '</a>';
                                    echo '</div>';
                                }
                                
                                echo '<div class="article-content">';
                                echo '<h3 class="article-title"><a href="'.$article->Url.'">'.$title.'</a></h3>';
                                echo '<div class="article-meta">';
                                echo '<span class="date">'.tpure_TimeAgo($article->Time()).'</span>';
                                echo '<span class="view">'.$article->ViewNums.' 阅读</span>';
                                echo '<span class="comment">'.$article->CommNums.' 评论</span>';
                                echo '</div>';
                                
                                // 处理摘要
                                $intro = '';
                                if($article->Intro) {
                                    // 去除HTML标签并截取摘要
                                    $intro = strip_tags($article->Intro);
                                    $intro = trim($intro);
                                    if($intro) {
                                        $intro = SubStrUTF8($intro, 0, 150);
                                        $intro = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
                                        echo '<div class="article-intro">'.$intro.'...</div>';
                                    }
                                }
                                echo '</div>'; // article-content
                                echo '</div>'; // tag-article
                            }
                        } else {
                            echo '<div class="no-articles">该标签下暂无文章</div>';
                        }
                        
                        // 输出分页导航
                        if($totalPages > 1) {
                            echo '<div class="pagebar">';
                            
                            // 构建基础URL - 使用当前页面URL
                            $currentUrl = strtok($_SERVER['REQUEST_URI'], '?'); // 获取不带参数的URL
                            $orderParam = GetVars('order','GET') == 'view' ? '?order=view' : '';
                            $baseUrl = $currentUrl . $orderParam;
                            $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
                            
                            // 上一页
                            if($currentPage > 1) {
                                $prevUrl = $baseUrl . $separator . 'page=' . ($currentPage - 1);
                                echo '<a href="'.$prevUrl.'" class="prev">上一页</a>';
                            }
                            
                            // 页码
                            for($i = 1; $i <= $totalPages; $i++) {
                                if($i == $currentPage) {
                                    echo '<span class="current">'.$i.'</span>';
                                } else {
                                    $pageUrl = $baseUrl . $separator . 'page=' . $i;
                                    echo '<a href="'.$pageUrl.'">'.$i.'</a>';
                                }
                            }
                            
                            // 下一页
                            if($currentPage < $totalPages) {
                                $nextUrl = $baseUrl . $separator . 'page=' . ($currentPage + 1);
                                echo '<a href="'.$nextUrl.'" class="next">下一页</a>';
                            }
                            
                            echo '</div>';
                        }
                        {/php}
                    </div>
                </div>
                <div class="sidebar{if $zbp->Config('tpure')->PostFIXMENUON == '1'} fixed{/if}{if tpure_isMobile() && $zbp->Config('tpure')->PostSIDEMOBILEON=='1'} show{/if}">
                    {template:sidebar}
                </div>
            </div>
        </div>
    </div>
</div>
{template:footer}
<style>
/* 标签列表页样式 */
.page-title {
    font-size: 28px;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.tag-filter {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.filter-item {
    padding: 8px 20px;
    border-radius: 20px;
    background: #f5f5f5;
    color: #666;
    text-decoration: none;
    transition: all 0.3s;
}

.filter-item:hover {
    background: #e0e0e0;
    color: #333;
}

.filter-item.active {
    background: #0188fb;
    color: white;
}

/* 当前标签信息 */
.tag-info {
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
}

.current-tag {
    font-size: 24px;
    margin: 0;
    color: white;
}

.tag-count {
    font-size: 14px;
    opacity: 0.9;
    font-weight: normal;
}

/* 文章列表样式 */
.tag-article {
    margin-bottom: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #0188fb;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    display: flex;
    gap: 20px;
}

.tag-article:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* 缩略图样式 */
.article-thumb {
    flex-shrink: 0;
    width: 180px;
    height: 120px;
    border-radius: 6px;
    overflow: hidden;
}

.article-thumb a {
    display: block;
    width: 100%;
    height: 100%;
}

.article-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.article-thumb:hover img {
    transform: scale(1.1);
}

/* 文章内容区域 */
.article-content {
    flex: 1;
    min-width: 0;
}

.article-title {
    font-size: 20px;
    margin: 0 0 12px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.article-title a {
    color: #333;
    text-decoration: none;
}

.article-title a:hover {
    color: #0188fb;
}

.article-meta {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: #999;
    margin-bottom: 12px;
}

.article-meta .date:before {
    content: '📅 ';
}

.article-meta .view:before {
    content: '👁 ';
}

.article-meta .comment:before {
    content: '💬 ';
}

.article-intro {
    color: #666;
    line-height: 1.6;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.no-articles {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    font-size: 16px;
}

/* 响应式设计 */
@media (max-width: 768px) {
    .tag-article {
        flex-direction: column;
        gap: 15px;
    }
    
    .article-thumb {
        width: 100%;
        height: 200px;
    }
    
    .article-title {
        font-size: 18px;
    }
    
    .tag-filter {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .filter-item {
        padding: 6px 15px;
        font-size: 13px;
    }
}

/* 分页导航样式 */
.pagebar {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 40px 0 20px;
    padding: 20px 0;
}

.pagebar a,
.pagebar span {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s;
}

.pagebar a {
    background: #f5f5f5;
    color: #666;
}

.pagebar a:hover {
    background: #0188fb;
    color: white;
}

.pagebar .current {
    background: #0188fb;
    color: white;
    font-weight: bold;
}

.pagebar .prev,
.pagebar .next {
    font-weight: bold;
}

/* 响应式布局 */
@media (max-width: 768px) {
    .tag-filter {
        flex-direction: column;
        gap: 10px;
    }
    
    .tag-group {
        padding: 15px;
    }
    
    .tag-title {
        font-size: 18px;
    }
    
    .pagebar {
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .pagebar a,
    .pagebar span {
        padding: 6px 12px;
        font-size: 14px;
    }
}
</style>
</body>
</html>
