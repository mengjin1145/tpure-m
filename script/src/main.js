/**
 * Tpure 主题 - 主JavaScript文件
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 * @link https://www.toyean.com/
 */

(function($) {
    'use strict';

    // 主题配置（从window.tpure读取）
    const config = window.tpure || {};

    /**
     * 初始化函数
     */
    function init() {
        // 基础功能
        initNavigation();
        initSearch();
        initMobileMenu();
        
        // 可选功能
        if (config.slideon) initSlideshow();
        if (config.lazyload) initLazyLoad();
        if (config.night) initNightMode();
        if (config.backtotop) initBackToTop();
        if (config.ajaxpager) initAjaxPager();
        
        // 其他功能
        initComments();
        initForms();
        initArchive();
        
        // 安全功能
        if (config.selectstart === false) disableTextSelection();
        
        console.log('✓ Tpure Theme v' + config.version + ' loaded');
    }

    /**
     * 导航菜单
     */
    function initNavigation() {
        // 当前页面高亮
        const currentPath = window.location.pathname;
        $('.menu a').each(function() {
            if ($(this).attr('href') === currentPath) {
                $(this).closest('li').addClass('on');
            }
        });

        // 移动端菜单切换
        $('.menuico').on('click', function() {
            $(this).toggleClass('on');
            $('.menu, .fademask').toggleClass('on');
        });

        // 遮罩层点击关闭
        $('.fademask').on('click', function() {
            $(this).removeClass('on');
            $('.menu, .menuico').removeClass('on');
        });

        // 子菜单展开/折叠
        $('.subcate > a').on('click', function(e) {
            if (window.innerWidth <= 1080) {
                e.preventDefault();
                $(this).parent().toggleClass('slidedown');
                $(this).next('.subnav').slideToggle('fast');
            }
        });
    }

    /**
     * 搜索功能
     */
    function initSearch() {
        let searchTimeout = null;

        // 搜索图标点击
        $('.schico a').on('click', function(e) {
            e.preventDefault();
            $('.schfixed').addClass('on');
            $('.schinput').focus();
        });

        // 搜索关闭
        $('.schclose, .schbg').on('click', function() {
            $('.schfixed').removeClass('on');
        });

        // ESC键关闭搜索
        $(document).on('keyup', function(e) {
            if (e.keyCode === 27) {
                $('.schfixed').removeClass('on');
                $('.helloschiinput').val('');
            }
        });

        // Ajax搜索（如果启用）
        if (config.ajaxsearch) {
            $('.helloschiinput').on('input propertychange', function() {
                const $input = $(this);
                const keyword = $.trim($input.val());
                const $result = $('.ajaxresult');

                clearTimeout(searchTimeout);

                if (!keyword) {
                    $result.hide().empty();
                    return;
                }

                searchTimeout = setTimeout(function() {
                    $.get(zbp.host + 'search.php?q=' + encodeURIComponent(keyword), function(data) {
                        if (data.post && data.post.length > 0) {
                            $result.empty().show();
                            
                            data.post.forEach(function(item) {
                                const html = `
                                    <div class="schitem">
                                        ${item.img ? '<span class="schimg"><img src="' + item.img + '"></span>' : ''}
                                        <div class="schitemcon">
                                            <strong>${item.title}</strong>
                                            <em>${item.intro}</em>
                                        </div>
                                    </div>
                                `;
                                $result.append($('<a/>').attr('href', item.url).html(html));
                            });

                            if (data.more) {
                                $result.append(
                                    '<div class="schmore"><a href="' + zbp.host + 'search.php?q=' + 
                                    encodeURIComponent(keyword) + '">查看更多</a></div>'
                                );
                            }
                        } else {
                            $result.html('<div class="schnull">没有搜到相关内容，切换关键词试试。</div>').show();
                        }
                    }, 'json');
                }, 100);

                // 点击外部关闭结果
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.ajaxresult, .helloschiinput').length) {
                        $result.hide();
                    }
                });
            });
        }

        // 搜索表单提交验证
        $('form').on('submit', function() {
            const keyword = $(this).find('input[type=text]').val();
            if (!$.trim(keyword)) {
                $(this).find('input[type=text]').focus();
                return false;
            }
        });
    }

    /**
     * 移动端菜单
     */
    function initMobileMenu() {
        if (!window.matchMedia) return;

        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        
        if (isMobile) {
            // 移动端特殊处理
            $('.menu li > a').on('click', function() {
                const $parent = $(this).parent();
                if ($parent.hasClass('subcate')) {
                    return false;
                }
            });
        }
    }

    /**
     * 幻灯片
     */
    function initSlideshow() {
        if (!window.Swiper || $('.swiper-slide').length < 2) return;

        const options = {
            observeParents: true,
            observer: true,
            pagination: '.swiper-pagination',
            paginationClickable: true,
            slidesPerView: 1,
            spaceBetween: 0,
            autoplay: config.slidetime || 2500,
            loop: true,
            prevButton: '.swiper-button-prev',
            nextButton: '.swiper-button-next'
        };

        if (config.slideeffect) {
            options.effect = 'fade';
        }

        const swiper = new Swiper('.swiper-container', options);

        // 鼠标悬停暂停
        if (config.slidedisplay) {
            $('.swiper-slide').on('mouseenter', function() {
                swiper.stopAutoplay();
            }).on('mouseleave', function() {
                swiper.startAutoplay();
            });
        }
    }

    /**
     * 图片懒加载
     */
    function initLazyLoad() {
        if (!$.fn.lazyload) return;

        $('img').lazyload({
            effect: 'show',
            threshold: 200,
            data_attribute: 'original'
        });

        // 监听DOM变化，为新图片应用懒加载
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        $(mutation.addedNodes).find('img').lazyload({
                            effect: 'show',
                            threshold: 200,
                            data_attribute: 'original'
                        });
                    }
                });
            });

            const target = document.querySelector('.content');
            if (target) {
                observer.observe(target, {
                    childList: true,
                    subtree: true
                });
            }
        }

        // 懒加载进度条和数字
        if (config.lazyline) {
            $('body').append('<div class="lazyline"></div>');
        }
        if (config.lazynum) {
            $('body').append('<div class="lazynum"></div>');
        }
    }

    /**
     * 夜间模式
     */
    function initNightMode() {
        const $nightBtn = $('.setnight');
        if (!$nightBtn.length) return;

        // 检查cookie
        const isNight = zbp.cookie.get('night') === '1' || $('body').hasClass('night');

        // 设置初始状态
        if (isNight) {
            $('body').addClass('night');
            $nightBtn.attr('title', '开灯').addClass('black');
        } else {
            $('body').removeClass('night');
            $nightBtn.attr('title', '关灯').removeClass('black');
        }

        // 自动切换（如果启用）
        if (config.setnightauto) {
            const hour = new Date().getHours();
            const start = parseInt(config.setnightstart) || 22;
            const end = parseInt(config.setnightover) || 6;
            
            if (hour >= start || hour < end) {
                if (!isNight) {
                    $('body').addClass('night');
                    zbp.cookie.set('night', '1');
                    $nightBtn.attr('title', '开灯').addClass('black');
                }
            }
        }

        // 点击切换
        $nightBtn.on('click', function() {
            if ($('body').hasClass('night')) {
                $('body').removeClass('night');
                zbp.cookie.set('night', '0');
                $(this).attr('title', '关灯').removeClass('black');
                console.log('夜间模式关闭');
            } else {
                $('body').addClass('night');
                zbp.cookie.set('night', '1');
                $(this).attr('title', '开灯').addClass('black');
                console.log('夜间模式开启');
            }
        });
    }

    /**
     * 返回顶部
     */
    function initBackToTop() {
        const $backBtn = $('<a class="backtotop"><i></i></a>')
            .appendTo('body')
            .attr('title', '返回顶部');

        // 滚动显示/隐藏
        $(window).on('scroll', function() {
            if ($(window).scrollTop() >= (config.backtotopvalue || 500)) {
                $backBtn.show();
            } else {
                $backBtn.hide();
            }
        });

        // 点击返回顶部
        $backBtn.on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 100);
        });
    }

    /**
     * Ajax分页
     */
    function initAjaxPager() {
        if (!$.fn.ias) return;

        $.ias({
            thresholdMargin: -100,
            triggerPageThreshold: parseInt(config.loadpagenum) || 3,
            history: false,
            container: '.content',
            item: '.item',
            pagination: '.pagebar',
            next: '.pagebar .next-page a',
            loader: '<div class="pagination-loading">数据载入中...</div>',
            trigger: '下一页',
            onPageChange: function(pageNum, pageUrl, $items) {
                // 更新Google Analytics
                if (window._gaq) {
                    window._gaq.push(['_trackPageview', new URL(pageUrl).pathname]);
                }
            }
        });
    }

    /**
     * 评论功能
     */
    function initComments() {
        // 评论回复
        zbp.plugin.on('comment.reply.start', 'tpure', function(commentId) {
            $('#inpRevID').val(commentId);
            
            const $tempForm = $('#temp-frm');
            const $replyForm = $('#divCommentPost');
            const $cancelBtn = $('#cancel-reply');

            $replyForm.wrap($tempForm).addClass('reply-frm');
            $('#comment-' + commentId).parents('.cmts').find('.cmtsfoot').before($replyForm);
            
            $cancelBtn.show().on('click', function() {
                const $placeholder = $('#temp-frm');
                if ($placeholder.length && $replyForm.length) {
                    $placeholder.before($replyForm);
                    $placeholder.remove();
                    $(this).hide();
                    $replyForm.removeClass('reply-frm');
                }
                $('#inpRevID').val(0);
                return false;
            });

            try {
                $('#txaArticle').focus();
            } catch (e) {}
            
            return false;
        });

        // 评论加载
        zbp.plugin.on('comment.got', 'tpure', function(data, textStatus) {
            $('#AjaxComment').html('Waiting...');
        });

        // 评论成功
        zbp.plugin.on('comment.post.success', 'tpure', function(data, textStatus, jqXHR, replid) {
            $('#divCommentPost').addClass('nocmt').removeClass('reply-frm');
            
            if (replid.replyid.toString() !== '0') {
                $('#comment-' + replid.replyid).parents('.cmts').find('.cmtsitem').remove();
                $('#comment-' + replid.replyid).parents('.cmts').append(data.data.html);
            }
            
            $('#cancel-reply').click();
            location.reload();
        });

        // 头像加载
        const defaultAvatar = $('#gravatar').attr('src');
        const $emailInput = $('#inpEmail');

        if ($emailInput.length && defaultAvatar) {
            $emailInput.on('input propertychange', function() {
                const email = $(this).val();
                
                if (/^[1-9][0-9]{4,9}@(qq|QQ).com/.test(email)) {
                    const qq = email.substring(0, email.indexOf('@'));
                    $('#gravatar').attr('src', 'https://q2.qlogo.cn/headimg_dl?dst_uin=' + qq + '&spec=100');
                } else if (/^\w+([-+.]\w+)*@\w+([-.]$+)*\.\w+([-.]\w+)*$/.test(email)) {
                    const emailMd5 = hex_md5(email);
                    $('#gravatar').attr('src', defaultAvatar.replace('{%emailmd5%}', emailMd5));
                } else {
                    $('#gravatar').attr('src', defaultAvatar);
                }
            });
        }
    }

    /**
     * 表单处理
     */
    function initForms() {
        // 确认对话框
        $('[data-confirm]').on('click', function() {
            const message = $(this).data('confirm');
            return confirm(message);
        });

        // 焦点样式
        $('textarea, input').on('focus', function() {
            $(this).parent('.text').addClass('on');
        }).on('blur', function() {
            setTimeout(() => {
                $(this).parent('.text').removeClass('on');
            }, 140);
        });

        // 外链新窗口打开
        if (config.linkblank) {
            $('a').each(function() {
                const href = $(this).attr('href');
                if (href && href.indexOf(window.location.host) === -1 && 
                    href.indexOf('://') > -1) {
                    $(this).attr('target', '_blank');
                }
            });
        }
    }

    /**
     * 归档展开/折叠
     */
    function initArchive() {
        if (!$('.archivefold').length) return;

        const $foldBtn = $('.archivefold');
        const foldText = $foldBtn.data('foldtext') || '展开';
        const originalText = $foldBtn.text();

        // 初始折叠
        $('.archiveitem:not(:first) .archivelist').hide();
        $('.archiveitem:not(:first) .archivedate').removeClass('on');

        $foldBtn.on('click', function() {
            if ($(this).hasClass('on')) {
                $(this).removeClass('on').text(foldText);
                $foldBtn.attr('data-foldtext', originalText);
                $('.archiveitem:not(:first) .archivelist').slideUp('fast');
                $('.archivedate').removeClass('on');
            } else {
                $(this).addClass('on').text(originalText);
                $foldBtn.attr('data-foldtext', foldText);
                $('.archiveitem:not(:first) .archivelist').slideDown('fast');
                $('.archivedate').addClass('on');
            }
        });
    }

    /**
     * 禁用文本选择
     */
    function disableTextSelection() {
        document.onselectstart = function() { return false; };
        $(document).on('contextmenu dblclick selectstart', function() {
            return false;
        });
    }

    // 文档加载完成后执行
    $(document).ready(init);

})(jQuery);

