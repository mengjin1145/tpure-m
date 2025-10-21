<?php die(); ?>{* Template Name:相关文章模块(勿选) *}
{if $zbp->Config('tpure')->PostRELATEON == '1'}
{php}
$aid=$article->ID;
$tagid=$article->Tags;
$cid=$article->Category->ID;
if($zbp->Config('tpure')->PostRELATENUM){
	$relatenum = $zbp->Config('tpure')->PostRELATENUM;
}else{
	$relatenum = '6';
}
$str = '';
if(empty($tagid)){
	$tagrd=0;
}else{
	$tagrd=array_rand($tagid);
}
if(sizeof($tagid)>0 && ($tagid[$tagrd]->Count)>1 && $zbp->Config('tpure')->PostRELATECATE == '0'){
	$tagi='%{'.$tagrd.'}%';
	$where = array(array('=','log_Status','0'),array('like','log_Tag',$tagi),array('<>','log_ID',$aid));
}else{
	$where = array(array('=','log_Status','0'),array('=','log_CateID',$cid),array('<>','log_ID',$aid));
}
switch ($zbp->option['ZC_DATABASE_TYPE']) {
	case 'mysql':
	case 'mysqli':
	case 'pdo_mysql':
		$order = array('rand()'=>'');
	break;
	case 'sqlite':
	case 'sqlite3':
		$order = array('random()'=>'');
	break;
}

if(ZC_VERSION_COMMIT >= 2800){
	$w = array();
	$str = '';
	if($zbp->Config('tpure')->PostRELATENUM)
	{
		$w['random'] = $zbp->Config('tpure')->PostRELATENUM;
	}else{
		$w['random'] = '6';
	}
	if($article->TagsCount && $zbp->Config('tpure')->PostRELATECATE != '1')
	{
	  $w['is_related'] = $article->ID;
	}else{
	  $w['cate'] = $article->Category->ID;
	}
	$array = GetList($w);
	
	// 🆕 预加载关联数据，解决N+1查询问题（仅在类存在且方法可调用时执行）
	if (class_exists('TpureDatabase') && method_exists('TpureDatabase', 'preloadArticleRelations') && count($array) > 0) {
		try {
			TpureDatabase::preloadArticleRelations($array);
		} catch (Exception $e) {
			// 静默失败，不影响页面显示
		}
	}
}else{
	$array = $zbp->GetArticleList(array('*'),$where,$order,array($relatenum),'');
	
	// 🆕 预加载关联数据，解决N+1查询问题（仅在类存在且方法可调用时执行）
	if (class_exists('TpureDatabase') && method_exists('TpureDatabase', 'preloadArticleRelations') && count($array) > 0) {
		try {
			TpureDatabase::preloadArticleRelations($array);
		} catch (Exception $e) {
			// 静默失败，不影响页面显示
		}
	}
}
{/php}
{if count($array)>0}
<div class="block">
	<div class="posttitle"><h4>{if $zbp->Config('tpure')->PostRELATETITLE == '1'}“{$article.Title}” {$zbp->lang['tpure']['of']}{/if}{$zbp->lang['tpure']['relate']}</h4></div>
	<div class="relatecon{if $zbp->Config('tpure')->PostRELATESTYLE == '1'}{if $zbp->Config('tpure')->PostRELATEDIALLEL == '1'} diallel{else} onlyone{/if}{/if}">
{php}
	foreach ($array as $related) {
		$content = $related->Content;
		$intro = preg_replace('/[\r\n\s]+/', ' ', trim(SubStrUTF8(TransferHTML($content,'[nohtml]'),$zbp->Config('tpure')->PostINTRONUM)).'...');
		if(tpure_Thumb($related) != ''){
			$isimg = ' class="isimg"';
		}else{
			$isimg = '';
		}
		if(($related->ID) != $article->ID){
			if($zbp->Config('tpure')->PostRELATESTYLE == '0'){
				$str .= '<div class="relate">';
					if(tpure_Thumb($related) != ''){$str .= '<div class="relateimg"><a href="'.$related->Url.'" title="'.$related->Title.'"><img src="'.tpure_Thumb($related).'" alt="'.$related->Title.'"></a></div>';}
				$str .= '<div class="relateinfo">
						<h3><a href="'.$related->Url.'" title="'.$related->Title.'">'.$related->Title.'</a></h3>
						<p'.$isimg.'>';
						if(tpure_isMobile()){$str .= '<a href="'.$related->Url.'">';}
							$str .= $intro;
						if(tpure_isMobile()){$str .= '</a>';}
						$str .= '</p>';
					$str .= '</div>
				</div>';
			}else{
				$str .= '<div class="relatelist"><a href="'.$related->Url.'" title="'.$related->Title.'">'.$related->Title.'</a><span class="posttime">'.tpure_TimeAgo($related->Time()).'</span></div>';
			}
		}
	}
{/php}
{$str}
	</div>
</div>
{/if}
{/if}