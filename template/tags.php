{* Template Name:标签云带侧栏模板 * Template Type:page *}
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
                            <a href="{$host}{$article.Url}" class="filter-item{if !GetVars('order','GET')} active{/if}">最新内容</a>
                            <a href="{$host}{$article.Url}?order=view" class="filter-item{if GetVars('order','GET') == 'view'} active{/if}">最热内容</a>
                        </div>
                        {php}
                        // 分页设置
                        $perPage = 10; // 每页显示10个标签
                        $currentPage = max(1, intval(GetVars('page', 'GET')));
                        
                        // 获取所有有文章的标签（先获取总数）
                        $allTags = $zbp->GetTagList(
                            array('*'),
                            array(array('>', 'tag_Count', '0')),
                            array('tag_Count'=>'DESC'),
                            null
                        );
                        
                        $totalTags = count($allTags);
                        $totalPages = ceil($totalTags / $perPage);
                        
                        // 获取当前页的标签
                        $offset = ($currentPage - 1) * $perPage;
                        $tags = $zbp->GetTagList(
                            array('*'),
                            array(array('>', 'tag_Count', '0')),
                            array('tag_Count'=>'DESC'),
                            array($perPage, $offset)
                        );
                        
                        // 设置排序方式
                        $order = GetVars('order','GET') == 'view' ? 
                            array('log_ViewNums'=>'DESC') : 
                            array('log_PostTime'=>'DESC');
                        
                        // 遍历每个标签
                        foreach($tags as $tag) {
                            // 获取该标签的文章（限制10篇）
                            $articles = $zbp->GetArticleList(
                                array('*'),
                                array(
                                    array('=', 'log_Status', 0),
                                    array('LIKE', 'log_Tag', '%{' . $tag->ID . '}%')
                                ),
                                $order,
                                array(10)
                            );
                            
                            if(count($articles) > 0) {
                                echo '<div class="post item tag-group">';
                                echo '<h2 class="tag-title"><a href="'.$tag->Url.'">'.$tag->Name.'</a> <span class="tag-count">('.$tag->Count.')</span></h2>';
                                echo '<div class="tag-articles">';
                                
                                foreach($articles as $article) {
                                    echo '<div class="tag-article">';
                                    echo '<h3><a href="'.$article->Url.'">'.$article->Title.'</a></h3>';
                                    echo '<div class="article-meta">';
                                    echo '<span class="date">'.tpure_TimeAgo($article->Time()).'</span>';
                                    echo '<span class="view">'.$article->ViewNums.' 阅读</span>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        
                        // 输出分页导航
                        if($totalPages > 1) {
                            echo '<div class="pagebar">';
                            
                            // 构建基础URL
                            $orderParam = GetVars('order','GET') == 'view' ? '?order=view' : '';
                            $baseUrl = $article->Url . $orderParam;
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

.tag-group {
    margin-bottom: 30px;
    padding: 20px;
    background: #fafafa;
    border-radius: 8px;
}

.tag-title {
    font-size: 22px;
    margin-bottom: 15px;
    color: #333;
}

.tag-title a {
    color: #0188fb;
    text-decoration: none;
}

.tag-title a:hover {
    text-decoration: underline;
}

.tag-count {
    font-size: 14px;
    color: #999;
    font-weight: normal;
}

.tag-articles {
    display: grid;
    gap: 15px;
}

.tag-article {
    padding: 15px;
    background: white;
    border-radius: 6px;
    border-left: 3px solid #0188fb;
    transition: all 0.3s;
}

.tag-article:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tag-article h3 {
    font-size: 16px;
    margin: 0 0 10px 0;
}

.tag-article h3 a {
    color: #333;
    text-decoration: none;
}

.tag-article h3 a:hover {
    color: #0188fb;
}

.article-meta {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: #999;
}

.article-meta .date:before {
    content: '📅 ';
}

.article-meta .view:before {
    content: '👁 ';
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
