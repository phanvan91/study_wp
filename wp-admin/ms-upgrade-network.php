<?php
/**
 * Bảng điều khiển nâng cấp Multisite.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

require_once __DIR__ . '/admin.php';

wp_redirect( network_admin_url( 'upgrade.php' ) );
exit;
