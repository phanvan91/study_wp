<?php
/**
 * Xử lý Tiến trình Ajax của WordPress
 *
 * @package WordPress
 * @subpackage Administration
 *
 * @link https://developer.wordpress.org/plugins/javascript/ajax
 */

/**
 * Đang thực thi tiến trình Ajax.
 *
 * @since 2.1.0
 */
define( 'DOING_AJAX', true );
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

/** Tải phần Khởi động WordPress */
require_once dirname( __DIR__ ) . '/wp-load.php';

/** Cho phép các yêu cầu cross-domain (từ giao diện front-end). */
send_origin_headers();

header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
header( 'X-Robots-Tag: noindex' );

// Yêu cầu tham số action hợp lệ.
if ( empty( $_REQUEST['action'] ) || ! is_scalar( $_REQUEST['action'] ) ) {
	wp_die( '0', 400 );
}

/** Tải các API Quản trị WordPress */
require_once ABSPATH . 'wp-admin/includes/admin.php';

/** Tải các Trình xử lý Ajax cho WordPress Core */
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

send_nosniff_header();
nocache_headers();

/** Action này được ghi tài liệu trong wp-admin/admin.php */
do_action( 'admin_init' );

$core_actions_get = array(
	'fetch-list',
	'ajax-tag-search',
	'wp-compression-test',
	'imgedit-preview',
	'oembed-cache',
	'autocomplete-user',
	'dashboard-widgets',
	'logged-in',
	'rest-nonce',
);

$core_actions_post = array(
	'oembed-cache',
	'image-editor',
	'delete-comment',
	'delete-tag',
	'delete-link',
	'delete-meta',
	'delete-post',
	'trash-post',
	'untrash-post',
	'delete-page',
	'dim-comment',
	'add-link-category',
	'add-tag',
	'get-tagcloud',
	'get-comments',
	'replyto-comment',
	'edit-comment',
	'add-menu-item',
	'add-meta',
	'add-user',
	'closed-postboxes',
	'hidden-columns',
	'update-welcome-panel',
	'menu-get-metabox',
	'wp-link-ajax',
	'menu-locations-save',
	'menu-quick-search',
	'meta-box-order',
	'get-permalink',
	'sample-permalink',
	'inline-save',
	'inline-save-tax',
	'find_posts',
	'widgets-order',
	'save-widget',
	'delete-inactive-widgets',
	'set-post-thumbnail',
	'date_format',
	'time_format',
	'wp-remove-post-lock',
	'dismiss-wp-pointer',
	'upload-attachment',
	'get-attachment',
	'query-attachments',
	'save-attachment',
	'save-attachment-compat',
	'send-link-to-editor',
	'send-attachment-to-editor',
	'save-attachment-order',
	'media-create-image-subsizes',
	'heartbeat',
	'get-revision-diffs',
	'save-user-color-scheme',
	'update-widget',
	'query-themes',
	'parse-embed',
	'set-attachment-thumbnail',
	'parse-media-shortcode',
	'destroy-sessions',
	'install-plugin',
	'activate-plugin',
	'update-plugin',
	'crop-image',
	'generate-password',
	'save-wporg-username',
	'delete-plugin',
	'search-plugins',
	'search-install-plugins',
	'activate-plugin',
	'update-theme',
	'delete-theme',
	'install-theme',
	'get-post-thumbnail-html',
	'get-community-events',
	'edit-theme-plugin-file',
	'wp-privacy-export-personal-data',
	'wp-privacy-erase-personal-data',
	'health-check-site-status-result',
	'health-check-dotorg-communication',
	'health-check-is-in-debug-mode',
	'health-check-background-updates',
	'health-check-loopback-requests',
	'health-check-get-sizes',
	'toggle-auto-updates',
	'send-password-reset',
);

// Đã ngừng sử dụng.
$core_actions_post_deprecated = array(
	'wp-fullscreen-save-post',
	'press-this-save-post',
	'press-this-add-category',
	'health-check-dotorg-communication',
	'health-check-is-in-debug-mode',
	'health-check-background-updates',
	'health-check-loopback-requests',
);

$core_actions_post = array_merge( $core_actions_post, $core_actions_post_deprecated );

// Đăng ký các lệnh gọi Ajax lõi.
if ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], $core_actions_get, true ) ) {
	add_action( 'wp_ajax_' . $_GET['action'], 'wp_ajax_' . str_replace( '-', '_', $_GET['action'] ), 1 );
}

if ( ! empty( $_POST['action'] ) && in_array( $_POST['action'], $core_actions_post, true ) ) {
	add_action( 'wp_ajax_' . $_POST['action'], 'wp_ajax_' . str_replace( '-', '_', $_POST['action'] ), 1 );
}

add_action( 'wp_ajax_nopriv_generate-password', 'wp_ajax_nopriv_generate_password' );

add_action( 'wp_ajax_nopriv_heartbeat', 'wp_ajax_nopriv_heartbeat', 1 );

// Đăng ký các lệnh gọi Ajax cho Phụ thuộc Plugin.
add_action( 'wp_ajax_check_plugin_dependencies', array( 'WP_Plugin_Dependencies', 'check_plugin_dependencies_during_ajax' ) );

$action = $_REQUEST['action'];

if ( is_user_logged_in() ) {
	// Nếu không có action nào được đăng ký, trả về phản hồi Bad Request.
	if ( ! has_action( "wp_ajax_{$action}" ) ) {
		wp_die( '0', 400 );
	}

	/**
	 * Kích hoạt các action Ajax đã xác thực cho người dùng đã đăng nhập.
	 *
	 * Phần động của tên hook, `$action`, tham chiếu đến
	 * tên của callback action Ajax đang được kích hoạt.
	 *
	 * @since 2.1.0
	 */
	do_action( "wp_ajax_{$action}" );
} else {
	// Nếu không có action nào được đăng ký, trả về phản hồi Bad Request.
	if ( ! has_action( "wp_ajax_nopriv_{$action}" ) ) {
		wp_die( '0', 400 );
	}

	/**
	 * Kích hoạt các action Ajax chưa xác thực cho người dùng chưa đăng nhập.
	 *
	 * Phần động của tên hook, `$action`, tham chiếu đến
	 * tên của callback action Ajax đang được kích hoạt.
	 *
	 * @since 2.8.0
	 */
	do_action( "wp_ajax_nopriv_{$action}" );
}

// Trạng thái mặc định.
wp_die( '0' );
