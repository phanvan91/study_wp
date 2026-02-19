<?php
/**
 * Trình xử lý hành động cho các bảng điều khiển quản trị Multisite.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

require_once __DIR__ . '/admin.php';

wp_redirect( network_admin_url() );
exit;
