<?php
// Nạp helper debug
require_once __DIR__ . '/wp-debug-helper.php';

/**
 * Điểm vào chính của ứng dụng WordPress. File này không làm gì cả,
 * nhưng nạp wp-blog-header.php để thực hiện và báo cho WordPress nạp theme.
 *
 * @package WordPress
 */

/**
 * Báo cho WordPress nạp theme và xuất ra giao diện.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

/** Nạp môi trường và template của WordPress */
require __DIR__ . '/wp-blog-header.php';
