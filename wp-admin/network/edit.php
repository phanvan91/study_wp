<?php
/**
 * Trình xử lý hành động cho các trang quản trị Multisite.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

/** Tải Bootstrap Quản trị WordPress */
require_once __DIR__ . '/admin.php';

$action = ( isset( $_GET['action'] ) ) ? $_GET['action'] : '';

if ( empty( $action ) ) {
	wp_redirect( network_admin_url() );
	exit;
}

/**
 * Kích hoạt ngay trước trình xử lý hành động trong một số màn hình Quản trị Mạng lưới.
 *
 * Hook này kích hoạt trên nhiều màn hình trong Quản trị Mạng lưới Multisite,
 * bao gồm Người dùng, Cài đặt Mạng lưới, và Cài đặt Trang web.
 *
 * @since 3.0.0
 */
do_action( 'wpmuadminedit' );

/**
 * Kích hoạt hành động xử lý được yêu cầu.
 *
 * Phần động của tên hook, `$action`, tham chiếu đến tên
 * của hành động được yêu cầu lấy từ yêu cầu `GET`.
 *
 * @since 3.1.0
 */
do_action( "network_admin_edit_{$action}" );

wp_redirect( network_admin_url() );
exit;
