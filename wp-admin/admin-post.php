<?php
/**
 * Trình xử lý Yêu cầu Chung (POST/GET) của WordPress
 *
 * Dành cho việc xử lý gửi biểu mẫu trong theme và plugin.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Chúng ta đang ở trong Màn hình Quản trị WordPress */
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

/** Tải phần Khởi động WordPress */
require_once dirname( __DIR__ ) . '/wp-load.php';

/** Cho phép các yêu cầu cross-domain (từ giao diện front-end). */
send_origin_headers();

require_once ABSPATH . 'wp-admin/includes/admin.php';

nocache_headers();

/** Action này được ghi tài liệu trong wp-admin/admin.php */
do_action( 'admin_init' );

$action = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

// Từ chối các tham số không hợp lệ.
if ( ! is_scalar( $action ) ) {
	wp_die( '', 400 );
}

if ( ! is_user_logged_in() ) {
	if ( empty( $action ) ) {
		/**
		 * Kích hoạt khi có yêu cầu admin post chưa xác thực mà không có action nào được cung cấp.
		 *
		 * @since 2.6.0
		 */
		do_action( 'admin_post_nopriv' );
	} else {
		// Nếu không có action nào được đăng ký, trả về phản hồi Bad Request.
		if ( ! has_action( "admin_post_nopriv_{$action}" ) ) {
			wp_die( '', 400 );
		}

		/**
		 * Kích hoạt khi có yêu cầu admin post chưa xác thực cho action đã cho.
		 *
		 * Phần động của tên hook, `$action`, tham chiếu đến
		 * action yêu cầu đã cho.
		 *
		 * @since 2.6.0
		 */
		do_action( "admin_post_nopriv_{$action}" );
	}
} else {
	if ( empty( $action ) ) {
		/**
		 * Kích hoạt khi có yêu cầu admin post đã xác thực mà không có action nào được cung cấp.
		 *
		 * @since 2.6.0
		 */
		do_action( 'admin_post' );
	} else {
		// Nếu không có action nào được đăng ký, trả về phản hồi Bad Request.
		if ( ! has_action( "admin_post_{$action}" ) ) {
			wp_die( '', 400 );
		}

		/**
		 * Kích hoạt khi có yêu cầu admin post đã xác thực cho action đã cho.
		 *
		 * Phần động của tên hook, `$action`, tham chiếu đến
		 * action yêu cầu đã cho.
		 *
		 * @since 2.6.0
		 */
		do_action( "admin_post_{$action}" );
	}
}
