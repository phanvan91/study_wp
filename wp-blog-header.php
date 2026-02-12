<?php
/**
 * Loads the WordPress environment and template.
 *
 * Load file môi trường và template của WordPress.
 *
 * @package WordPress
 */
if ( ! isset( $wp_did_header ) ) {

	$wp_did_header = true;

	// Load the WordPress library.
	// Load thư viện WordPress.
	require_once __DIR__ . '/wp-load.php';

	// Set up the WordPress query.
	// Thiết lập WordPress query.
	wp();

	// Load the theme template.
	// Load template của theme.
	require_once ABSPATH . WPINC . '/template-loader.php';

}
