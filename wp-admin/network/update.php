<?php
/**
 * Trang quản trị mạng lưới Cập nhật/Cài đặt Plugin/Giao diện.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */

if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'update-selected', 'activate-plugin', 'update-selected-themes' ), true ) ) {
	define( 'IFRAME_REQUEST', true );
}

/** Tải Bootstrap Quản trị WordPress */
require_once __DIR__ . '/admin.php';

require ABSPATH . 'wp-admin/update.php';
