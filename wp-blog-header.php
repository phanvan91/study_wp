<?php
/**
 * Nạp môi trường và template của WordPress.
 *
 * @package WordPress
 */
if ( ! isset( $wp_did_header ) ) {

	$wp_did_header = true;

	// Nạp thư viện WordPress.
	require_once __DIR__ . '/wp-load.php';

	// Thiết lập truy vấn WordPress.
	wp();

	// Nạp template của theme.
	require_once ABSPATH . WPINC . '/template-loader.php';

}
