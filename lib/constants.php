<?php
/**
 * Tpure 主题 - 常量定义
 * 
 * @package Tpure
 * @version 5.0.6
 * @author TOYEAN
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

// ==================== 主题版本信息 ====================
define('TPURE_VERSION', '5.0.6');
define('TPURE_VERSION_BUILD', '20251012');

// ==================== 安全相关常量 ====================
// 文件上传
define('TPURE_MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('TPURE_ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('TPURE_ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// 日志文件
define('TPURE_MAX_LOG_SIZE', 5 * 1024 * 1024); // 5MB
define('TPURE_LOG_DIR', ZBP_PATH . 'zb_users/theme/tpure/logs/');

// CSRF令牌
define('TPURE_CSRF_TOKEN_LENGTH', 32);

// ==================== 内容相关常量 ====================
// 文章摘要
define('TPURE_DEFAULT_INTRO_LENGTH', 110);
define('TPURE_DEFAULT_INTRO_SUFFIX', '...');

// 搜索结果
define('TPURE_MAX_SEARCH_RESULTS', 5);
define('TPURE_SEARCH_LIMIT', 6);

// 文章标题长度
define('TPURE_MAX_TITLE_LENGTH', 200);

// 评论相关
define('TPURE_MAX_COMMENT_LENGTH', 10000);

// ==================== 邮件相关常量 ====================
// 邮件主题长度
define('TPURE_MAX_EMAIL_SUBJECT_LENGTH', 200);

// SMTP端口
define('TPURE_DEFAULT_SMTP_PORT', 465);
define('TPURE_DEFAULT_SMTP_PORT_TLS', 587);

// ==================== 缓存相关常量 ====================
// 缓存过期时间（秒）
define('TPURE_CACHE_EXPIRE_HOUR', 3600); // 1小时
define('TPURE_CACHE_EXPIRE_DAY', 86400); // 1天
define('TPURE_CACHE_EXPIRE_WEEK', 604800); // 7天
define('TPURE_CACHE_EXPIRE_MONTH', 2592000); // 30天

// 归档缓存
define('TPURE_ARCHIVE_CACHE_KEY', 'tpure_archive_cache');

// ==================== 分页相关常量 ====================
// 默认分页数量
define('TPURE_DEFAULT_PAGE_SIZE', 10);
define('TPURE_DEFAULT_AJAX_PAGE_THRESHOLD', 3);

// ==================== 图片相关常量 ====================
// 缩略图尺寸
define('TPURE_THUMB_WIDTH', 210);
define('TPURE_THUMB_HEIGHT', 147);

// 随机缩略图数量
define('TPURE_RANDOM_THUMB_COUNT', 10);

// 懒加载
define('TPURE_LAZYLOAD_THRESHOLD', 200);
define('TPURE_LAZYLOAD_PLACEHOLDER', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// ==================== 时间相关常量 ====================
// 时间格式
define('TPURE_DATE_FORMAT', 'Y-m-d H:i:s');
define('TPURE_DATE_FORMAT_SHORT', 'Y-m-d');

// ==================== 正则表达式常量 ====================
// 邮箱验证
define('TPURE_EMAIL_REGEX', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');

// QQ邮箱提取
define('TPURE_QQ_EMAIL_REGEX', '/^(\d+)@qq\.com$/i');

// 图片标签匹配
define('TPURE_IMG_TAG_REGEX', '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i');

// HTML标签清理
define('TPURE_HTML_TAG_REGEX', '/<[^>]+>/');

// ==================== 用户权限相关常量 ====================
// Z-BlogPHP内置权限名称
define('TPURE_RIGHT_UPLOAD', 'UploadPst'); // 文件上传权限
define('TPURE_RIGHT_ROOT', 'root'); // 管理员权限

// ==================== HTTP状态码常量 ====================
define('TPURE_HTTP_OK', 200);
define('TPURE_HTTP_BAD_REQUEST', 400);
define('TPURE_HTTP_UNAUTHORIZED', 401);
define('TPURE_HTTP_FORBIDDEN', 403);
define('TPURE_HTTP_NOT_FOUND', 404);
define('TPURE_HTTP_SERVER_ERROR', 500);

// ==================== 响应状态码常量 ====================
define('TPURE_RESPONSE_SUCCESS', 1);
define('TPURE_RESPONSE_FAILURE', 0);

// ==================== 夜间模式时间常量 ====================
define('TPURE_NIGHT_MODE_DEFAULT_START', 18); // 18:00
define('TPURE_NIGHT_MODE_DEFAULT_END', 6); // 6:00

// ==================== 字符编码常量 ====================
define('TPURE_CHARSET', 'UTF-8');
define('TPURE_HTML_ENTITIES_FLAGS', ENT_QUOTES | ENT_HTML5);

// ==================== 模板相关常量 ====================
// 模板目录
define('TPURE_TEMPLATE_DIR', ZBP_PATH . 'zb_users/theme/tpure/template/');

// 邮件模板
define('TPURE_MAIL_TEMPLATE_NEW_ARTICLE', 'mail_new_article.html');
define('TPURE_MAIL_TEMPLATE_EDIT_ARTICLE', 'mail_edit_article.html');
define('TPURE_MAIL_TEMPLATE_NEW_COMMENT', 'mail_new_comment.html');
define('TPURE_MAIL_TEMPLATE_REPLY_COMMENT', 'mail_reply_comment.html');

// ==================== JavaScript配置常量 ====================
// Swiper默认配置
define('TPURE_SWIPER_DEFAULT_AUTOPLAY', 2500); // 毫秒
define('TPURE_SWIPER_ANIMATION_SPEED', 600); // 毫秒

// AJAX超时时间
define('TPURE_AJAX_TIMEOUT', 10000); // 10秒

// ==================== 调试相关常量 ====================
define('TPURE_DEBUG', false); // 生产环境应设为false

// ==================== 模块ID常量 ====================
// 主题创建的模块ID前缀
define('TPURE_MODULE_PREFIX', 'tpure_');

// 常用模块ID
define('TPURE_MODULE_HOT_ARTICLES', 'tpure_hot_articles');
define('TPURE_MODULE_RECENT_ARTICLES', 'tpure_recent_articles');
define('TPURE_MODULE_RECENT_COMMENTS', 'tpure_recent_comments');
define('TPURE_MODULE_TAG_CLOUD', 'tpure_tag_cloud');
define('TPURE_MODULE_ARCHIVE', 'tpure_archive');

// ==================== 文章类型常量 ====================
define('TPURE_POST_TYPE_ARTICLE', 0); // 普通文章
define('TPURE_POST_TYPE_PAGE', 1); // 页面

// ==================== 评论状态常量 ====================
define('TPURE_COMMENT_STATUS_PUBLIC', 0); // 公开
define('TPURE_COMMENT_STATUS_PENDING', 1); // 待审核

// ==================== SEO相关常量 ====================
// 描述长度限制
define('TPURE_SEO_DESCRIPTION_MAX_LENGTH', 160);
define('TPURE_SEO_KEYWORDS_MAX_COUNT', 10);

// ==================== 性能相关常量 ====================
// 数据库查询限制
define('TPURE_MAX_QUERY_LIMIT', 100);

// 热门文章查询数量
define('TPURE_HOT_ARTICLES_COUNT', 10);

// 最新评论查询数量
define('TPURE_RECENT_COMMENTS_COUNT', 10);

// ==================== 错误消息常量 ====================
define('TPURE_ERROR_PERMISSION_DENIED', '权限不足');
define('TPURE_ERROR_INVALID_REQUEST', '无效的请求');
define('TPURE_ERROR_UPLOAD_FAILED', '文件上传失败');
define('TPURE_ERROR_FILE_TOO_LARGE', '文件大小超过限制');
define('TPURE_ERROR_INVALID_FILE_TYPE', '不支持的文件类型');
define('TPURE_ERROR_EMAIL_SEND_FAILED', '邮件发送失败');
define('TPURE_ERROR_INVALID_EMAIL', '无效的邮箱地址');
define('TPURE_ERROR_CSRF_TOKEN_INVALID', 'CSRF令牌验证失败');

// ==================== 成功消息常量 ====================
define('TPURE_SUCCESS_UPLOAD', '上传成功');
define('TPURE_SUCCESS_EMAIL_SENT', '邮件发送成功');
define('TPURE_SUCCESS_SAVE', '保存成功');

// ==================== 路径相关常量 ====================
// 主题目录（仅文件系统路径，URL 在 include.php 中定义）
define('TPURE_THEME_PATH', ZBP_PATH . 'zb_users/theme/tpure/');

// 静态资源目录（仅文件系统路径）
define('TPURE_STYLE_PATH', TPURE_THEME_PATH . 'style/');
define('TPURE_SCRIPT_PATH', TPURE_THEME_PATH . 'script/');
define('TPURE_PLUGIN_PATH', TPURE_THEME_PATH . 'plugin/');

