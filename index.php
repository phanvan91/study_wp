<?php
// Include debug helper
// Include helper debug
require_once __DIR__ . '/wp-debug-helper.php';

/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * Mặt trước của ứng dụng WordPress. File này không làm gì, nhưng load
 * wp-blog-header.php để thực hiện và báo cho WordPress load theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * Báo cho WordPress load theme và xuất ra.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

/** Loads the WordPress Environment and Template */
/** Load môi trường và template của WordPress */
require __DIR__ . '/wp-blog-header.php';
