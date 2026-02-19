<?php
/**
 * Trang quản trị mạng lưới Cài đặt Giao diện.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.1.0
 */

if ( isset( $_GET['tab'] ) && ( 'theme-information' === $_GET['tab'] ) ) {
	define( 'IFRAME_REQUEST', true );
}

/** Tải Bootstrap Quản trị WordPress */
require_once __DIR__ . '/admin.php';

require ABSPATH . 'wp-admin/theme-install.php';
