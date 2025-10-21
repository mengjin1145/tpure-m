<?php
/**
 * Header 资源加载优化片段
 * 
 * 使用方法：将此文件内容替换 template/header.php 中第323-361行的资源加载部分
 * 
 * 优化内容：
 * 1. DNS预解析和预连接
 * 2. 关键CSS内联
 * 3. 非关键CSS异步加载
 * 4. JS延迟加载
 * 5. 资源预加载
 */
?>

    <!-- ==================== 性能优化：DNS预解析 ==================== -->
    <link rel="dns-prefetch" href="//<?php echo $_SERVER['HTTP_HOST']; ?>">
    <?php if ($zbp->Config('tpure')->PostSHAREARTICLEON == '1' || $zbp->Config('tpure')->PostSHAREPAGEON == '1'): ?>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <?php endif; ?>
    
    <!-- ==================== Favicon ==================== -->
    <?php if ($zbp->Config('tpure')->PostFAVICONON): ?>
    <link rel="shortcut icon" href="<?php echo $zbp->Config('tpure')->PostFAVICON; ?>" type="image/x-icon">
    <?php endif; ?>
    
    <meta name="generator" content="<?php echo $zblogphp; ?>">
    
    <!-- ==================== 关键CSS内联（首屏必需） ==================== -->
    <style id="critical-css">
    /* 关键CSS - 首屏渲染必需 */
    body{margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:16px;line-height:1.6;color:#333;background:#fff}
    .header{position:relative;width:100%;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,.1)}
    .main{max-width:1200px;margin:0 auto;padding:20px}
    a{color:#0188fb;text-decoration:none}
    img{max-width:100%;height:auto}
    </style>
    
    <!-- ==================== 资源预加载 ==================== -->
    <?php
    // 获取资源清单（如果存在）
    $manifestPath = $zbp->path . 'zb_users/theme/' . $zbp->theme . '/manifest.json';
    $useVersionHash = file_exists($manifestPath);
    
    if ($useVersionHash) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $cssUrl = $host . 'zb_users/theme/' . $theme . '/' . $manifest['assets']['css']['url'];
        $jsUrl = $host . 'zb_users/theme/' . $theme . '/' . $manifest['assets']['js']['url'];
    } else {
        $cssUrl = $host . 'zb_users/theme/' . $theme . '/style/' . $style . '.css?v=' . $zbp->themeapp->version;
        $jsUrl = $host . 'zb_users/theme/' . $theme . '/script/common.js?v=' . $zbp->themeapp->version;
    }
    ?>
    
    <!-- 预加载关键资源 -->
    <link rel="preload" href="<?php echo $cssUrl; ?>" as="style">
    <link rel="preload" href="<?php echo $host; ?>zb_system/script/jquery-latest.min.js" as="script">
    <link rel="preload" href="<?php echo $jsUrl; ?>" as="script">
    
    <!-- ==================== CSS加载（异步） ==================== -->
    <!-- 主样式表（异步加载） -->
    <link rel="stylesheet" href="<?php echo $cssUrl; ?>" media="print" onload="this.media='all';this.onload=null">
    <noscript><link rel="stylesheet" href="<?php echo $cssUrl; ?>"></noscript>
    
    <!-- 自定义皮肤 -->
    <?php if ($zbp->Config('tpure')->PostCOLORON == '1'): ?>
    <link rel="stylesheet" href="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/include/skin.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/include/skin.css"></noscript>
    <?php endif; ?>
    
    <!-- 分享插件样式 -->
    <?php if ($zbp->Config('tpure')->PostSHAREARTICLEON == '1' || $zbp->Config('tpure')->PostSHAREPAGEON == '1'): ?>
    <link rel="stylesheet" href="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/share/share.css" media="print" onload="this.media='all'">
    <?php endif; ?>
    
    <!-- 轮播插件样式 -->
    <?php if ($zbp->Config('tpure')->PostSLIDEON == '1'): ?>
    <link rel="stylesheet" href="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/swiper/swiper.min.css" media="print" onload="this.media='all'">
    <?php endif; ?>
    
    <!-- 播放器样式 -->
    <?php if ($type == 'article' && isset($article->Metas->video) && $article->Metas->video): ?>
    <link rel="stylesheet" href="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/dplayer/DPlayer.min.css" media="print" onload="this.media='all'">
    <?php endif; ?>
    
    <!-- ==================== JS加载（延迟） ==================== -->
    <!-- jQuery（核心依赖，正常加载） -->
    <script src="<?php echo $host; ?>zb_system/script/jquery-latest.min.js"></script>
    
    <!-- Z-BlogPHP核心脚本（defer） -->
    <script defer src="<?php echo $host; ?>zb_system/script/zblogphp.js"></script>
    <script defer src="<?php echo $host; ?>zb_system/script/c_html_js_add.php"></script>
    
    <!-- 主题脚本（defer） -->
    <script defer src="<?php echo $jsUrl; ?>"></script>
    
    <!-- 分享插件 -->
    <?php if ($zbp->Config('tpure')->PostSHAREARTICLEON == '1' || $zbp->Config('tpure')->PostSHAREPAGEON == '1'): ?>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/share/share.js"></script>
    <?php endif; ?>
    
    <!-- 轮播插件 -->
    <?php if ($zbp->Config('tpure')->PostSLIDEON == '1'): ?>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/swiper/swiper.min.js"></script>
    <?php endif; ?>
    
    <!-- 视频播放器 -->
    <?php if ($type == 'article' && isset($article->Metas->video) && $article->Metas->video): ?>
        <?php if (strpos($article->Metas->video, '.m3u8') !== false || strpos($article->Metas->video, '.flv') !== false): ?>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/dplayer/hls.min.js"></script>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/dplayer/flv.min.js"></script>
        <?php endif; ?>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/dplayer/DPlayer.min.js"></script>
    <?php endif; ?>
    
    <!-- DPI检测 -->
    <?php if ($zbp->Config('tpure')->PostCHECKDPION == '1' && !tpure_isMobile()): ?>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/checkdpi/jquery.detectZoom.js"></script>
    <?php endif; ?>
    
    <!-- 二维码 -->
    <?php if ($zbp->Config('tpure')->PostQRON == '1'): ?>
    <script defer src="<?php echo $host; ?>zb_users/theme/<?php echo $theme; ?>/plugin/qrcode/jquery.qrcode.min.js"></script>
    <?php endif; ?>
    
    <!-- ==================== 主题配置（内联JS） ==================== -->
    <script>
    // 主题配置对象
    window.tpure = {
        <?php if ($zbp->Config('tpure')->PostBLANKSTYLE == '2'): ?>linkblank: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostQRON == '1'): ?>qr: true,<?php endif; ?>
        qrsize: <?php echo $zbp->Config('tpure')->PostQRSIZE ? $zbp->Config('tpure')->PostQRSIZE : '70'; ?>,
        <?php if ($zbp->Config('tpure')->PostSLIDEON == '1'): ?>slideon: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSLIDEDISPLAY == '1'): ?>slidedisplay: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSLIDETIME): ?>slidetime: <?php echo $zbp->Config('tpure')->PostSLIDETIME; ?>,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSLIDEPAGETYPE == '1'): ?>slidepagetype: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSLIDEEFFECTON == '1'): ?>slideeffect: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostBANNERDISPLAYON == '1'): ?>bannerdisplay: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostVIEWALLON == '1'): ?>viewall: true,<?php endif; ?>
        viewallstyle: <?php echo $zbp->Config('tpure')->PostVIEWALLSTYLE ? '1' : '0'; ?>,
        <?php if ($zbp->Config('tpure')->PostVIEWALLHEIGHT): ?>viewallheight: '<?php echo $zbp->Config('tpure')->PostVIEWALLHEIGHT; ?>',<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostAJAXON == '1'): ?>ajaxpager: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostLOADPAGENUM): ?>loadpagenum: '<?php echo $zbp->Config('tpure')->PostLOADPAGENUM; ?>',<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostLAZYLOADON == '1'): ?>lazyload: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostLAZYLINEON == '1'): ?>lazyline: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostLAZYNUMON == '1'): ?>lazynum: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSETNIGHTON): ?>night: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSETNIGHTAUTOON): ?>setnightauto: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSETNIGHTSTART): ?>setnightstart: '<?php echo $zbp->Config('tpure')->PostSETNIGHTSTART; ?>',<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSETNIGHTOVER): ?>setnightover: '<?php echo $zbp->Config('tpure')->PostSETNIGHTOVER; ?>',<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSELECTON == '1'): ?>selectstart: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostSINGLEKEY == '1'): ?>singlekey: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostPAGEKEY == '1'): ?>pagekey: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostTFONTSIZEON == '1'): ?>tfontsize: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostFIXSIDEBARON == '1'): ?>fixsidebar: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostFIXSIDEBARSTYLE): ?>fixsidebarstyle: '1',<?php else: ?>fixsidebarstyle: '0',<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostREMOVEPON == '1'): ?>removep: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostLANGON == '1'): ?>lang: true,<?php endif; ?>
        <?php if ($zbp->Config('tpure')->PostBACKTOTOPON == '1'): ?>backtotop: true,<?php endif; ?>
        backtotopvalue: <?php echo $zbp->Config('tpure')->PostBACKTOTOPVALUE ? $zbp->Config('tpure')->PostBACKTOTOPVALUE : 0; ?>,
        version: '<?php echo $zbp->themeapp->version; ?>'
    };
    </script>
    
    <!-- ==================== 其他配置 ==================== -->
    <?php if ($zbp->Config('tpure')->PostBLANKSTYLE == '1'): ?>
    <base target="_blank">
    <?php endif; ?>
    
    <!-- 灰度模式 -->
    <?php if ($zbp->Config('tpure')->PostGREYON == '1'): ?>
        <?php if ($zbp->Config('tpure')->PostGREYDAY && tpure_IsToday($zbp->Config('tpure')->PostGREYDAY)): ?>
            <?php if ($zbp->Config('tpure')->PostGREYSTATE == '0'): ?>
                <?php if ($type == 'index'): ?>
    <style>html{filter:grayscale(100%)}*{filter:gray}</style>
                <?php endif; ?>
            <?php else: ?>
    <style>html{filter:grayscale(100%)}*{filter:gray}</style>
            <?php endif; ?>
        <?php elseif (!$zbp->Config('tpure')->PostGREYDAY): ?>
    <style>html{filter:grayscale(100%)}*{filter:gray}</style>
        <?php endif; ?>
    <?php endif; ?>

