<?php
/**
 * Được sử dụng để thiết lập và sửa các biến chung và bao gồm
 * thư viện thủ tục và lớp Multisite.
 *
 * Cho phép một số cấu hình trong wp-config.php (xem ms-default-constants.php)
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Các đối tượng đại diện cho mạng hiện tại và site hiện tại.
 *
 * Chúng có thể được điền thông qua tệp `sunrise.php` tùy chỉnh. Nếu không, thì tệp
 * này sẽ cố gắng điền chúng dựa trên yêu cầu hiện tại.
 *
 * @global WP_Network $current_site Mạng hiện tại.
 * @global object     $current_blog Site hiện tại.
 * @global string     $domain       Đã loại bỏ. Tên miền của site tìm thấy khi tải.
 *                                  Sử dụng `get_site()->domain` thay thế.
 * @global string     $path         Đã loại bỏ. Đường dẫn của site tìm thấy khi tải.
 *                                  Sử dụng `get_site()->path` thay thế.
 * @global int        $site_id      Đã loại bỏ. ID của mạng tìm thấy khi tải.
 *                                  Sử dụng `get_current_network_id()` thay thế.
 * @global bool       $public       Đã loại bỏ. Site tìm thấy khi tải có công khai hay không.
 *                                  Sử dụng `get_site()->public` thay thế.
 *
 * @since 3.0.0
 */
global $current_site, $current_blog, $domain, $path, $site_id, $public;

/** Lớp WP_Network */
require_once ABSPATH . WPINC . '/class-wp-network.php';

/** Lớp WP_Site */
require_once ABSPATH . WPINC . '/class-wp-site.php';

/** Trình tải Multisite */
require_once ABSPATH . WPINC . '/ms-load.php';

/** Các hằng số Multisite mặc định */
require_once ABSPATH . WPINC . '/ms-default-constants.php';

if ( defined( 'SUNRISE' ) ) {
	include_once WP_CONTENT_DIR . '/sunrise.php';
}

/** Kiểm tra và định nghĩa SUBDOMAIN_INSTALL và hằng số VHOST đã loại bỏ. */
ms_subdomain_constants();

// Khối này sẽ xử lý yêu cầu nếu các đối tượng mạng hiện tại hoặc site hiện tại
// chưa được điền trong phạm vi toàn cục thông qua thứ gì đó như `sunrise.php`.
if ( ! isset( $current_site ) || ! isset( $current_blog ) ) {

	$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );
	if ( str_ends_with( $domain, ':80' ) ) {
		$domain               = substr( $domain, 0, -3 );
		$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -3 );
	} elseif ( str_ends_with( $domain, ':443' ) ) {
		$domain               = substr( $domain, 0, -4 );
		$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -4 );
	}

	$path = stripslashes( $_SERVER['REQUEST_URI'] );
	if ( is_admin() ) {
		$path = preg_replace( '#(.*)/wp-admin/.*#', '$1/', $path );
	}
	list( $path ) = explode( '?', $path );

	$bootstrap_result = ms_load_current_site_and_network( $domain, $path, is_subdomain_install() );

	if ( true === $bootstrap_result ) {
		// `$current_blog` và `$current_site` đã được điền.
	} elseif ( false === $bootstrap_result ) {
		ms_not_installed( $domain, $path );
	} else {
		header( 'Location: ' . $bootstrap_result );
		exit;
	}
	unset( $bootstrap_result );

	$blog_id = $current_blog->blog_id;
	$public  = $current_blog->public;

	if ( empty( $current_blog->site_id ) ) {
		// Điều này có từ [MU134] và không còn phù hợp nữa,
		// nhưng có thể xảy ra với các tham số truyền cho insert_blog() v.v.
		$current_blog->site_id = 1;
	}

	$site_id = $current_blog->site_id;
	wp_load_core_site_options( $site_id );
}

$wpdb->set_prefix( $table_prefix, false ); // $table_prefix có thể được đặt trong sunrise.php.
$wpdb->set_blog_id( $current_blog->blog_id, $current_blog->site_id );
$table_prefix       = $wpdb->get_blog_prefix();
$_wp_switched_stack = array();
$switched           = false;

// Cần khởi tạo lại bộ nhớ đệm sau khi blog_id được đặt.
wp_start_object_cache();

if ( ! $current_site instanceof WP_Network ) {
	$current_site = new WP_Network( $current_site );
}

if ( ! $current_blog instanceof WP_Site ) {
	$current_blog = new WP_Site( $current_blog );
}

// Định nghĩa các hằng số thư mục upload.
ms_upload_constants();

/**
 * Kích hoạt sau khi site hiện tại và mạng hiện tại đã được phát hiện và tải
 * trong quá trình khởi động multisite.
 *
 * @since 4.6.0
 */
do_action( 'ms_loaded' );
