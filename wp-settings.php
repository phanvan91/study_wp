<?php
/**
 * Được sử dụng để thiết lập và sửa các biến chung và include
 * thư viện procedural và class của WordPress.
 *
 * Cho phép một số cấu hình trong wp-config.php (xem default-constants.php)
 *
 * @package WordPress
 */

/**
 * Lưu trữ vị trí thư mục WordPress chứa functions, classes, và core content.
 *
 * @since 1.0.0
 */
define( 'WPINC', 'wp-includes' );

/**
 * Thông tin phiên bản cho bản phát hành WordPress hiện tại.
 *
 * Các giá trị này không thể được globalize trực tiếp trong version.php. Khi cập nhật,
 * include version.php từ một cài đặt khác và không ghi đè
 * các giá trị này nếu đã được thiết lập.
 *
 * @global string   $wp_version              Chuỗi phiên bản WordPress.
 * @global int      $wp_db_version           Phiên bản cơ sở dữ liệu WordPress.
 * @global string   $tinymce_version         Phiên bản TinyMCE.
 * @global string   $required_php_version    Chuỗi phiên bản PHP yêu cầu.
 * @global string[] $required_php_extensions Tên các extension PHP yêu cầu.
 * @global string   $required_mysql_version  Chuỗi phiên bản MySQL yêu cầu.
 * @global string   $wp_local_package        Mã locale của gói.
 */
global $wp_version, $wp_db_version, $tinymce_version, $required_php_version, $required_php_extensions, $required_mysql_version, $wp_local_package;
require ABSPATH . WPINC . '/version.php';
require ABSPATH . WPINC . '/compat.php';
require ABSPATH . WPINC . '/load.php';

// Kiểm tra phiên bản PHP yêu cầu và extension MySQL hoặc database drop-in.
wp_check_php_mysql_versions();

// Include các file cần thiết cho việc khởi tạo.
require ABSPATH . WPINC . '/class-wp-paused-extensions-storage.php';
require ABSPATH . WPINC . '/class-wp-exception.php';
require ABSPATH . WPINC . '/class-wp-fatal-error-handler.php';
require ABSPATH . WPINC . '/class-wp-recovery-mode-cookie-service.php';
require ABSPATH . WPINC . '/class-wp-recovery-mode-key-service.php';
require ABSPATH . WPINC . '/class-wp-recovery-mode-link-service.php';
require ABSPATH . WPINC . '/class-wp-recovery-mode-email-service.php';
require ABSPATH . WPINC . '/class-wp-recovery-mode.php';
require ABSPATH . WPINC . '/error-protection.php';
require ABSPATH . WPINC . '/default-constants.php';
require_once ABSPATH . WPINC . '/plugin.php';

/**
 * Nếu chưa được cấu hình, `$blog_id` sẽ mặc định là 1 trong cấu hình single site.
 * Trong multisite, nó sẽ bị ghi đè mặc định trong ms-settings.php.
 *
 * @since 2.0.0
 *
 * @global int $blog_id
 */
global $blog_id;

// Thiết lập các hằng số mặc định ban đầu bao gồm WP_MEMORY_LIMIT, WP_MAX_MEMORY_LIMIT, WP_DEBUG, SCRIPT_DEBUG, WP_CONTENT_DIR và WP_CACHE.
wp_initial_constants();

// Đăng ký shutdown handler cho fatal errors càng sớm càng tốt.
wp_register_fatal_error_handler();

// WordPress tính toán offsets từ UTC.
// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
date_default_timezone_set( 'UTC' );

// Chuẩn hóa các biến $_SERVER trên các thiết lập.
wp_fix_server_vars();

// Kiểm tra xem site có đang ở chế độ bảo trì không.
wp_maintenance();

// Bắt đầu bộ đếm thời gian tải.
timer_start();

// Kiểm tra xem chế độ WP_DEBUG có được bật không.
wp_debug_mode();

/**
 * Lọc xác định có bật tải advanced-cache.php drop-in không.
 *
 * Filter này chạy trước khi nó có thể được sử dụng bởi plugins. Nó được thiết kế cho
 * các runtime không phải web. Nếu trả về false, advanced-cache.php sẽ không bao giờ được tải.
 *
 * @since 4.6.0
 *
 * @param bool $enable_advanced_cache Có bật tải advanced-cache.php (nếu có) không.
 *                                    Mặc định true.
 */
if ( WP_CACHE && apply_filters( 'enable_loading_advanced_cache_dropin', true ) && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
	// Cho plugin caching nâng cao sử dụng. Sử dụng static drop-in vì bạn chỉ muốn một cái.
	include WP_CONTENT_DIR . '/advanced-cache.php';

	// Khởi tạo lại các hooks được thêm thủ công bởi advanced-cache.php.
	if ( $wp_filter ) {
		$wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
	}
}

// Định nghĩa WP_LANG_DIR nếu chưa được thiết lập.
wp_set_lang_dir();

// Tải các file WordPress giai đoạn đầu.
require ABSPATH . WPINC . '/class-wp-list-util.php';
require ABSPATH . WPINC . '/class-wp-token-map.php';
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/meta.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/class-wp-meta-query.php';
require ABSPATH . WPINC . '/class-wp-matchesmapregex.php';
require ABSPATH . WPINC . '/class-wp.php';
require ABSPATH . WPINC . '/class-wp-error.php';
require ABSPATH . WPINC . '/pomo/mo.php';
require ABSPATH . WPINC . '/l10n/class-wp-translation-controller.php';
require ABSPATH . WPINC . '/l10n/class-wp-translations.php';
require ABSPATH . WPINC . '/l10n/class-wp-translation-file.php';
require ABSPATH . WPINC . '/l10n/class-wp-translation-file-mo.php';
require ABSPATH . WPINC . '/l10n/class-wp-translation-file-php.php';

/**
 * @since 0.71
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 */
global $wpdb;
// Include class wpdb và, nếu có, database drop-in db.php.
require_wp_db();

/**
 * @since 3.3.0
 *
 * @global string $table_prefix Tiền tố bảng cơ sở dữ liệu.
 */
$GLOBALS['table_prefix'] = $table_prefix;

// Thiết lập tiền tố bảng cơ sở dữ liệu và các format specifiers cho các cột bảng.
wp_set_wpdb_vars();

// Khởi động WordPress object cache, hoặc external object cache nếu drop-in có mặt.
wp_start_object_cache();

// Đính kèm các filter mặc định.
require ABSPATH . WPINC . '/default-filters.php';

// Khởi tạo multisite nếu được bật.
if ( is_multisite() ) {
	require ABSPATH . WPINC . '/class-wp-site-query.php';
	require ABSPATH . WPINC . '/class-wp-network-query.php';
	require ABSPATH . WPINC . '/ms-blogs.php';
	require ABSPATH . WPINC . '/ms-settings.php';
} elseif ( ! defined( 'MULTISITE' ) ) {
	define( 'MULTISITE', false );
}

register_shutdown_function( 'shutdown_action_hook' );

// Dừng hầu hết WordPress khỏi việc được tải nếu SHORTINIT được bật.
if ( SHORTINIT ) {
	return false;
}

// Tải thư viện L10n (bản địa hóa).
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . WPINC . '/class-wp-textdomain-registry.php';
require_once ABSPATH . WPINC . '/class-wp-locale.php';
require_once ABSPATH . WPINC . '/class-wp-locale-switcher.php';

// Chạy trình cài đặt nếu WordPress chưa được cài đặt.
wp_not_installed();

// Tải hầu hết WordPress.
require ABSPATH . WPINC . '/class-wp-walker.php';
require ABSPATH . WPINC . '/class-wp-ajax-response.php';
require ABSPATH . WPINC . '/capabilities.php';
require ABSPATH . WPINC . '/class-wp-roles.php';
require ABSPATH . WPINC . '/class-wp-role.php';
require ABSPATH . WPINC . '/class-wp-user.php';
require ABSPATH . WPINC . '/class-wp-query.php';
require ABSPATH . WPINC . '/query.php';
require ABSPATH . WPINC . '/class-wp-date-query.php';
require ABSPATH . WPINC . '/theme.php';
require ABSPATH . WPINC . '/class-wp-theme.php';
require ABSPATH . WPINC . '/class-wp-theme-json-schema.php';
require ABSPATH . WPINC . '/class-wp-theme-json-data.php';
require ABSPATH . WPINC . '/class-wp-theme-json.php';
require ABSPATH . WPINC . '/class-wp-theme-json-resolver.php';
require ABSPATH . WPINC . '/class-wp-duotone.php';
require ABSPATH . WPINC . '/global-styles-and-settings.php';
require ABSPATH . WPINC . '/class-wp-block-template.php';
require ABSPATH . WPINC . '/class-wp-block-templates-registry.php';
require ABSPATH . WPINC . '/block-template-utils.php';
require ABSPATH . WPINC . '/block-template.php';
require ABSPATH . WPINC . '/theme-templates.php';
require ABSPATH . WPINC . '/theme-previews.php';
require ABSPATH . WPINC . '/template.php';
require ABSPATH . WPINC . '/https-detection.php';
require ABSPATH . WPINC . '/https-migration.php';
require ABSPATH . WPINC . '/class-wp-user-request.php';
require ABSPATH . WPINC . '/user.php';
require ABSPATH . WPINC . '/class-wp-user-query.php';
require ABSPATH . WPINC . '/class-wp-session-tokens.php';
require ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php';
require ABSPATH . WPINC . '/general-template.php';
require ABSPATH . WPINC . '/link-template.php';
require ABSPATH . WPINC . '/author-template.php';
require ABSPATH . WPINC . '/robots-template.php';
require ABSPATH . WPINC . '/post.php';
require ABSPATH . WPINC . '/class-walker-page.php';
require ABSPATH . WPINC . '/class-walker-page-dropdown.php';
require ABSPATH . WPINC . '/class-wp-post-type.php';
require ABSPATH . WPINC . '/class-wp-post.php';
require ABSPATH . WPINC . '/post-template.php';
require ABSPATH . WPINC . '/revision.php';
require ABSPATH . WPINC . '/post-formats.php';
require ABSPATH . WPINC . '/post-thumbnail-template.php';
require ABSPATH . WPINC . '/category.php';
require ABSPATH . WPINC . '/class-walker-category.php';
require ABSPATH . WPINC . '/class-walker-category-dropdown.php';
require ABSPATH . WPINC . '/category-template.php';
require ABSPATH . WPINC . '/comment.php';
require ABSPATH . WPINC . '/class-wp-comment.php';
require ABSPATH . WPINC . '/class-wp-comment-query.php';
require ABSPATH . WPINC . '/class-walker-comment.php';
require ABSPATH . WPINC . '/comment-template.php';
require ABSPATH . WPINC . '/rewrite.php';
require ABSPATH . WPINC . '/class-wp-rewrite.php';
require ABSPATH . WPINC . '/feed.php';
require ABSPATH . WPINC . '/bookmark.php';
require ABSPATH . WPINC . '/bookmark-template.php';
require ABSPATH . WPINC . '/kses.php';
require ABSPATH . WPINC . '/cron.php';
require ABSPATH . WPINC . '/deprecated.php';
require ABSPATH . WPINC . '/script-loader.php';
require ABSPATH . WPINC . '/taxonomy.php';
require ABSPATH . WPINC . '/class-wp-taxonomy.php';
require ABSPATH . WPINC . '/class-wp-term.php';
require ABSPATH . WPINC . '/class-wp-term-query.php';
require ABSPATH . WPINC . '/class-wp-tax-query.php';
require ABSPATH . WPINC . '/update.php';
require ABSPATH . WPINC . '/canonical.php';
require ABSPATH . WPINC . '/shortcodes.php';
require ABSPATH . WPINC . '/embed.php';
require ABSPATH . WPINC . '/class-wp-embed.php';
require ABSPATH . WPINC . '/class-wp-oembed.php';
require ABSPATH . WPINC . '/class-wp-oembed-controller.php';
require ABSPATH . WPINC . '/media.php';
require ABSPATH . WPINC . '/http.php';
require ABSPATH . WPINC . '/html-api/html5-named-character-references.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-attribute-token.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-span.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-doctype-info.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-text-replacement.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-decoder.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-tag-processor.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-unsupported-exception.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-active-formatting-elements.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-open-elements.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-token.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-stack-event.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-processor-state.php';
require ABSPATH . WPINC . '/html-api/class-wp-html-processor.php';
require ABSPATH . WPINC . '/class-wp-http.php';
require ABSPATH . WPINC . '/class-wp-http-streams.php';
require ABSPATH . WPINC . '/class-wp-http-curl.php';
require ABSPATH . WPINC . '/class-wp-http-proxy.php';
require ABSPATH . WPINC . '/class-wp-http-cookie.php';
require ABSPATH . WPINC . '/class-wp-http-encoding.php';
require ABSPATH . WPINC . '/class-wp-http-response.php';
require ABSPATH . WPINC . '/class-wp-http-requests-response.php';
require ABSPATH . WPINC . '/class-wp-http-requests-hooks.php';
require ABSPATH . WPINC . '/widgets.php';
require ABSPATH . WPINC . '/class-wp-widget.php';
require ABSPATH . WPINC . '/class-wp-widget-factory.php';
require ABSPATH . WPINC . '/nav-menu-template.php';
require ABSPATH . WPINC . '/nav-menu.php';
require ABSPATH . WPINC . '/admin-bar.php';
require ABSPATH . WPINC . '/class-wp-application-passwords.php';
require ABSPATH . WPINC . '/rest-api.php';
require ABSPATH . WPINC . '/rest-api/class-wp-rest-server.php';
require ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php';
require ABSPATH . WPINC . '/rest-api/class-wp-rest-request.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-posts-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-attachments-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-global-styles-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-post-types-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-post-statuses-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-revisions-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-global-styles-revisions-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-template-revisions-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-autosaves-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-template-autosaves-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-taxonomies-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-terms-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-menu-items-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-menus-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-menu-locations-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-users-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-comments-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-search-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-blocks-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-block-types-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-block-renderer-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-settings-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-themes-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-plugins-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-block-directory-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-edit-site-export-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-pattern-directory-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-block-patterns-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-block-pattern-categories-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-application-passwords-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-site-health-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-sidebars-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-widget-types-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-widgets-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-templates-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-url-details-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-navigation-fallback-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-font-families-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-font-faces-controller.php';
require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-font-collections-controller.php';
require ABSPATH . WPINC . '/rest-api/fields/class-wp-rest-meta-fields.php';
require ABSPATH . WPINC . '/rest-api/fields/class-wp-rest-comment-meta-fields.php';
require ABSPATH . WPINC . '/rest-api/fields/class-wp-rest-post-meta-fields.php';
require ABSPATH . WPINC . '/rest-api/fields/class-wp-rest-term-meta-fields.php';
require ABSPATH . WPINC . '/rest-api/fields/class-wp-rest-user-meta-fields.php';
require ABSPATH . WPINC . '/rest-api/search/class-wp-rest-search-handler.php';
require ABSPATH . WPINC . '/rest-api/search/class-wp-rest-post-search-handler.php';
require ABSPATH . WPINC . '/rest-api/search/class-wp-rest-term-search-handler.php';
require ABSPATH . WPINC . '/rest-api/search/class-wp-rest-post-format-search-handler.php';
require ABSPATH . WPINC . '/sitemaps.php';
require ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps.php';
require ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps-index.php';
require ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps-provider.php';
require ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps-registry.php';
require ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps-renderer.php';
require ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps-stylesheet.php';
require ABSPATH . WPINC . '/sitemaps/providers/class-wp-sitemaps-posts.php';
require ABSPATH . WPINC . '/sitemaps/providers/class-wp-sitemaps-taxonomies.php';
require ABSPATH . WPINC . '/sitemaps/providers/class-wp-sitemaps-users.php';
require ABSPATH . WPINC . '/class-wp-block-bindings-source.php';
require ABSPATH . WPINC . '/class-wp-block-bindings-registry.php';
require ABSPATH . WPINC . '/class-wp-block-editor-context.php';
require ABSPATH . WPINC . '/class-wp-block-type.php';
require ABSPATH . WPINC . '/class-wp-block-pattern-categories-registry.php';
require ABSPATH . WPINC . '/class-wp-block-patterns-registry.php';
require ABSPATH . WPINC . '/class-wp-block-styles-registry.php';
require ABSPATH . WPINC . '/class-wp-block-type-registry.php';
require ABSPATH . WPINC . '/class-wp-block.php';
require ABSPATH . WPINC . '/class-wp-block-list.php';
require ABSPATH . WPINC . '/class-wp-block-metadata-registry.php';
require ABSPATH . WPINC . '/class-wp-block-parser-block.php';
require ABSPATH . WPINC . '/class-wp-block-parser-frame.php';
require ABSPATH . WPINC . '/class-wp-block-parser.php';
require ABSPATH . WPINC . '/class-wp-classic-to-block-menu-converter.php';
require ABSPATH . WPINC . '/class-wp-navigation-fallback.php';
require ABSPATH . WPINC . '/block-bindings.php';
require ABSPATH . WPINC . '/block-bindings/pattern-overrides.php';
require ABSPATH . WPINC . '/block-bindings/post-meta.php';
require ABSPATH . WPINC . '/blocks.php';
require ABSPATH . WPINC . '/blocks/index.php';
require ABSPATH . WPINC . '/block-editor.php';
require ABSPATH . WPINC . '/block-patterns.php';
require ABSPATH . WPINC . '/class-wp-block-supports.php';
require ABSPATH . WPINC . '/block-supports/utils.php';
require ABSPATH . WPINC . '/block-supports/align.php';
require ABSPATH . WPINC . '/block-supports/custom-classname.php';
require ABSPATH . WPINC . '/block-supports/generated-classname.php';
require ABSPATH . WPINC . '/block-supports/settings.php';
require ABSPATH . WPINC . '/block-supports/elements.php';
require ABSPATH . WPINC . '/block-supports/colors.php';
require ABSPATH . WPINC . '/block-supports/typography.php';
require ABSPATH . WPINC . '/block-supports/border.php';
require ABSPATH . WPINC . '/block-supports/layout.php';
require ABSPATH . WPINC . '/block-supports/position.php';
require ABSPATH . WPINC . '/block-supports/spacing.php';
require ABSPATH . WPINC . '/block-supports/dimensions.php';
require ABSPATH . WPINC . '/block-supports/duotone.php';
require ABSPATH . WPINC . '/block-supports/shadow.php';
require ABSPATH . WPINC . '/block-supports/background.php';
require ABSPATH . WPINC . '/block-supports/block-style-variations.php';
require ABSPATH . WPINC . '/block-supports/aria-label.php';
require ABSPATH . WPINC . '/style-engine.php';
require ABSPATH . WPINC . '/style-engine/class-wp-style-engine.php';
require ABSPATH . WPINC . '/style-engine/class-wp-style-engine-css-declarations.php';
require ABSPATH . WPINC . '/style-engine/class-wp-style-engine-css-rule.php';
require ABSPATH . WPINC . '/style-engine/class-wp-style-engine-css-rules-store.php';
require ABSPATH . WPINC . '/style-engine/class-wp-style-engine-processor.php';
require ABSPATH . WPINC . '/fonts/class-wp-font-face-resolver.php';
require ABSPATH . WPINC . '/fonts/class-wp-font-collection.php';
require ABSPATH . WPINC . '/fonts/class-wp-font-face.php';
require ABSPATH . WPINC . '/fonts/class-wp-font-library.php';
require ABSPATH . WPINC . '/fonts/class-wp-font-utils.php';
require ABSPATH . WPINC . '/fonts.php';
require ABSPATH . WPINC . '/class-wp-script-modules.php';
require ABSPATH . WPINC . '/script-modules.php';
require ABSPATH . WPINC . '/interactivity-api/class-wp-interactivity-api.php';
require ABSPATH . WPINC . '/interactivity-api/class-wp-interactivity-api-directives-processor.php';
require ABSPATH . WPINC . '/interactivity-api/interactivity-api.php';
require ABSPATH . WPINC . '/class-wp-plugin-dependencies.php';
require ABSPATH . WPINC . '/class-wp-url-pattern-prefixer.php';
require ABSPATH . WPINC . '/class-wp-speculation-rules.php';
require ABSPATH . WPINC . '/speculative-loading.php';

add_action( 'after_setup_theme', array( wp_script_modules(), 'add_hooks' ) );
add_action( 'after_setup_theme', array( wp_interactivity(), 'add_hooks' ) );

/**
 * @since 3.3.0
 *
 * @global WP_Embed $wp_embed Đối tượng WordPress Embed.
 */
$GLOBALS['wp_embed'] = new WP_Embed();

/**
 * Đối tượng WordPress Textdomain Registry.
 *
 * Được sử dụng để hỗ trợ dịch thuật just-in-time cho các text domain được tải thủ công.
 *
 * @since 6.1.0
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry WordPress Textdomain Registry.
 */
$GLOBALS['wp_textdomain_registry'] = new WP_Textdomain_Registry();
$GLOBALS['wp_textdomain_registry']->init();

// Tải các file dành riêng cho multisite.
if ( is_multisite() ) {
	require ABSPATH . WPINC . '/ms-functions.php';
	require ABSPATH . WPINC . '/ms-default-filters.php';
	require ABSPATH . WPINC . '/ms-deprecated.php';
}

// Định nghĩa các hằng số phụ thuộc vào API để lấy giá trị mặc định.
// Định nghĩa các hằng số thư mục must-use plugin, có thể bị ghi đè trong sunrise.php drop-in.
wp_plugin_directory_constants();

/**
 * @since 3.9.0
 *
 * @global array $wp_plugin_paths
 */
$GLOBALS['wp_plugin_paths'] = array();

// Tải các must-use plugin.
foreach ( wp_get_mu_plugins() as $mu_plugin ) {
	$_wp_plugin_file = $mu_plugin;
	include_once $mu_plugin;
	$mu_plugin = $_wp_plugin_file; // Tránh bị ghi đè biến $mu_plugin trong một plugin.

	/**
	 * Kích hoạt khi một must-use plugin đã được tải xong.
	 *
	 * @since 5.1.0
	 *
	 * @param string $mu_plugin Đường dẫn đầy đủ đến file chính của plugin.
	 */
	do_action( 'mu_plugin_loaded', $mu_plugin );
}
unset( $mu_plugin, $_wp_plugin_file );

// Tải các plugin được kích hoạt trên toàn mạng.
if ( is_multisite() ) {
	foreach ( wp_get_active_network_plugins() as $network_plugin ) {
		wp_register_plugin_realpath( $network_plugin );

		$_wp_plugin_file = $network_plugin;
		include_once $network_plugin;
		$network_plugin = $_wp_plugin_file; // Tránh bị ghi đè biến $network_plugin trong một plugin.

		/**
		 * Kích hoạt khi một plugin được kích hoạt trên toàn mạng đã được tải xong.
		 *
		 * @since 5.1.0
		 *
		 * @param string $network_plugin Đường dẫn đầy đủ đến file chính của plugin.
		 */
		do_action( 'network_plugin_loaded', $network_plugin );
	}
	unset( $network_plugin, $_wp_plugin_file );
}

/**
 * Kích hoạt khi tất cả must-use và plugin kích hoạt toàn mạng đã được tải xong.
 *
 * @since 2.8.0
 */
do_action( 'muplugins_loaded' );

if ( is_multisite() ) {
	ms_cookie_constants();
}

// Định nghĩa các hằng số sau khi multisite được tải.
wp_cookie_constants();

// Định nghĩa và áp dụng các hằng số SSL.
wp_ssl_constants();

// Tạo các biến toàn cục chung.
require ABSPATH . WPINC . '/vars.php';

// Làm cho taxonomies và posts khả dụng cho plugins và themes.
// @plugin authors: cảnh báo: chúng sẽ được đăng ký lại trên hook init.
create_initial_taxonomies();
create_initial_post_types();

wp_start_scraping_edited_file_errors();

// Đăng ký thư mục gốc theme mặc định.
register_theme_directory( get_theme_root() );

if ( ! is_multisite() && wp_is_fatal_error_handler_enabled() ) {
	// Xử lý người dùng yêu cầu liên kết chế độ khôi phục và khởi tạo chế độ khôi phục.
	wp_recovery_mode()->initialize();
}

// Để get_plugin_data() khả dụng theo cách tương thích với các plugin cũng tải file này, xem #62244.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Tải các plugin đang hoạt động.
foreach ( wp_get_active_and_valid_plugins() as $plugin ) {
	wp_register_plugin_realpath( $plugin );

	$plugin_data = get_plugin_data( $plugin, false, false );

	$textdomain = $plugin_data['TextDomain'];
	if ( $textdomain ) {
		if ( $plugin_data['DomainPath'] ) {
			$GLOBALS['wp_textdomain_registry']->set_custom_path( $textdomain, dirname( $plugin ) . $plugin_data['DomainPath'] );
		} else {
			$GLOBALS['wp_textdomain_registry']->set_custom_path( $textdomain, dirname( $plugin ) );
		}
	}

	$_wp_plugin_file = $plugin;
	include_once $plugin;
	$plugin = $_wp_plugin_file; // Tránh bị ghi đè biến $plugin trong một plugin.

	/**
	 * Kích hoạt khi một plugin đã kích hoạt được tải xong.
	 *
	 * @since 5.1.0
	 *
	 * @param string $plugin Đường dẫn đầy đủ đến file chính của plugin.
	 */
	do_action( 'plugin_loaded', $plugin );
}
unset( $plugin, $_wp_plugin_file, $plugin_data, $textdomain );

// Tải các hàm pluggable (có thể ghi đè).
require ABSPATH . WPINC . '/pluggable.php';
require ABSPATH . WPINC . '/pluggable-deprecated.php';

// Thiết lập mã hóa nội bộ.
wp_set_internal_encoding();

// Chạy wp_cache_postload() nếu object cache được bật và hàm tồn tại.
if ( WP_CACHE && function_exists( 'wp_cache_postload' ) ) {
	wp_cache_postload();
}

/**
 * Kích hoạt khi các plugin đã kích hoạt được tải xong.
 *
 * Các hàm pluggable cũng khả dụng tại thời điểm này trong thứ tự tải.
 *
 * @since 1.5.0
 */
do_action( 'plugins_loaded' );

// Định nghĩa các hằng số ảnh hưởng đến chức năng nếu chưa được định nghĩa.
wp_functionality_constants();

// Thêm magic quotes và thiết lập $_REQUEST ( $_GET + $_POST ).
wp_magic_quotes();

/**
 * Kích hoạt khi các cookie bình luận được làm sạch.
 *
 * @since 2.0.11
 */
do_action( 'sanitize_comment_cookies' );

/**
 * Đối tượng WordPress Query
 *
 * @since 2.0.0
 *
 * @global WP_Query $wp_the_query Đối tượng WordPress Query.
 */
$GLOBALS['wp_the_query'] = new WP_Query();

/**
 * Giữ tham chiếu đến {@see $wp_the_query}.
 * Sử dụng biến toàn cục này cho các truy vấn WordPress
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 */
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

/**
 * Giữ đối tượng WordPress Rewrite để tạo URL đẹp
 *
 * @since 1.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 */
$GLOBALS['wp_rewrite'] = new WP_Rewrite();

/**
 * Đối tượng WordPress
 *
 * @since 2.0.0
 *
 * @global WP $wp Thực thể môi trường WordPress hiện tại.
 */
$GLOBALS['wp'] = new WP();

/**
 * Đối tượng WordPress Widget Factory
 *
 * @since 2.8.0
 *
 * @global WP_Widget_Factory $wp_widget_factory
 */
$GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();

/**
 * Vai trò người dùng WordPress
 *
 * @since 2.0.0
 *
 * @global WP_Roles $wp_roles Đối tượng quản lý vai trò WordPress.
 */
$GLOBALS['wp_roles'] = new WP_Roles();

/**
 * Kích hoạt trước khi theme được tải.
 *
 * @since 2.6.0
 */
do_action( 'setup_theme' );

// Định nghĩa các hằng số và biến toàn cục liên quan đến template.
wp_templating_constants();
wp_set_template_globals();

// Tải text domain bản địa hóa mặc định.
load_default_textdomain();

$locale      = get_locale();
$locale_file = WP_LANG_DIR . "/$locale.php";
if ( ( 0 === validate_file( $locale ) ) && is_readable( $locale_file ) ) {
	require $locale_file;
}
unset( $locale_file );

/**
 * Đối tượng WordPress Locale để tải ngày tháng theo locale và các chuỗi khác nhau.
 *
 * @since 2.1.0
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày giờ WordPress.
 */
$GLOBALS['wp_locale'] = new WP_Locale();

/**
 * Đối tượng WordPress Locale Switcher để chuyển đổi ngôn ngữ.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher Đối tượng chuyển đổi ngôn ngữ WordPress.
 */
$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
$GLOBALS['wp_locale_switcher']->init();

// Tải các hàm cho theme đang hoạt động, cho cả theme cha và theme con nếu có.
foreach ( wp_get_active_and_valid_themes() as $theme ) {
	$wp_theme = wp_get_theme( basename( $theme ) );

	$wp_theme->load_textdomain();

	if ( file_exists( $theme . '/functions.php' ) ) {
		include $theme . '/functions.php';
	}
}
unset( $theme, $wp_theme );

/**
 * Kích hoạt sau khi theme được tải.
 *
 * @since 3.0.0
 */
do_action( 'after_setup_theme' );

// Tạo một instance của WP_Site_Health để các sự kiện Cron có thể kích hoạt.
if ( ! class_exists( 'WP_Site_Health' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
}
WP_Site_Health::get_instance();

// Thiết lập người dùng hiện tại.
$GLOBALS['wp']->init();

/**
 * Kích hoạt sau khi WordPress đã tải xong nhưng trước khi bất kỳ header nào được gửi.
 *
 * Hầu hết WP đã được tải ở giai đoạn này, và người dùng đã được xác thực. WP tiếp tục
 * tải trên hook {@see 'init'} tiếp theo (ví dụ: widgets), và nhiều plugin khởi tạo
 * chính mình trên đó vì nhiều lý do khác nhau (ví dụ: cần người dùng, taxonomy, v.v.).
 *
 * Nếu bạn muốn gắn một action khi WP đã tải xong, hãy sử dụng hook {@see 'wp_loaded'} bên dưới.
 *
 * @since 1.5.0
 */
do_action( 'init' );

// Kiểm tra trạng thái site.
if ( is_multisite() ) {
	$file = ms_site_check();
	if ( true !== $file ) {
		require $file;
		die();
	}
	unset( $file );
}

/**
 * Hook này được kích hoạt khi WP, tất cả plugin, và theme đã được tải và khởi tạo đầy đủ.
 *
 * Các yêu cầu Ajax nên sử dụng wp-admin/admin-ajax.php. admin-ajax.php có thể xử lý các yêu cầu
 * cho người dùng chưa đăng nhập.
 *
 * @link https://developer.wordpress.org/plugins/javascript/ajax
 *
 * @since 3.0.0
 */
do_action( 'wp_loaded' );
