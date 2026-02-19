<?php
/**
 * Header Tùy chọn WordPress.
 *
 * Hiển thị thông báo đã cập nhật, nếu biến updated là một phần của URL query.
 *
 * @package WordPress
 * @subpackage Administration
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$action = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) ) {
	// Để tương thích ngược với các plugin không sử dụng Settings API và chỉ đặt updated=1 trong redirect.
	add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'success' );
}

settings_errors();
