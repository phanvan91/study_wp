<?php
/**
 * Bootstrap Quản trị Người dùng WordPress
 *
 * @package WordPress
 * @subpackage Administration
 * @since 3.1.0
 */

define( 'WP_USER_ADMIN', true );

require_once dirname( __DIR__ ) . '/admin.php';

if ( ! is_multisite() ) {
	wp_redirect( admin_url() );
	exit;
}

$redirect_user_admin_request = ( 0 !== strcasecmp( $current_blog->domain, $current_site->domain ) || 0 !== strcasecmp( $current_blog->path, $current_site->path ) );

/**
 * Lọc xem có nên chuyển hướng yêu cầu đến Quản trị Người dùng trong Multisite hay không.
 *
 * @since 3.2.0
 *
 * @param bool $redirect_user_admin_request Có nên chuyển hướng yêu cầu hay không.
 */
$redirect_user_admin_request = apply_filters( 'redirect_user_admin_request', $redirect_user_admin_request );

if ( $redirect_user_admin_request ) {
	wp_redirect( user_admin_url() );
	exit;
}

unset( $redirect_user_admin_request );
