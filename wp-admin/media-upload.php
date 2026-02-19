<?php
/**
 * Quản lý file media được tải lên.
 *
 * Có nhiều bộ lọc ở đây cho media. Plugin có thể mở rộng chức năng
 * bằng cách hook vào các bộ lọc.
 *
 * @package WordPress
 * @subpackage Administration
 */

if ( ! isset( $_GET['inline'] ) ) {
	define( 'IFRAME_REQUEST', true );
}

/** Tải Bootstrap Quản trị WordPress */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'upload_files' ) ) {
	wp_die( __( 'Sorry, you are not allowed to upload files.' ), 403 );
}

wp_enqueue_script( 'plupload-handlers' );
wp_enqueue_script( 'image-edit' );
wp_enqueue_script( 'set-post-thumbnail' );
wp_enqueue_style( 'imgareaselect' );
wp_enqueue_script( 'media-gallery' );

header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );

// ID phải là số nguyên.
$ID      = isset( $ID ) ? (int) $ID : 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
$post_id = isset( $post_id ) ? (int) $post_id : 0;

// Yêu cầu một ID cho màn hình chỉnh sửa.
if ( isset( $action ) && 'edit' === $action && ! $ID ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	wp_die(
		'<h1>' . __( 'An error occurred during the upload process.' ) . '</h1>' .
		'<p>' . __( 'Invalid item ID. You can view all media items in the <a href="upload.php">Media Library</a>.' ) . '</p>',
		403
	);
}

if ( ! empty( $_REQUEST['post_id'] ) && ! current_user_can( 'edit_post', $_REQUEST['post_id'] ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to edit this item.' ) . '</p>',
		403
	);
}

// Loại upload: image, video, file, ...?
if ( isset( $_GET['type'] ) ) {
	$type = (string) $_GET['type'];
} else {
	/**
	 * Lọc loại upload media mặc định trong popup media cũ (trước 3.5.0).
	 *
	 * @since 2.5.0
	 *
	 * @param string $type Loại upload media mặc định. Các giá trị có thể bao gồm
	 *                     'image', 'audio', 'video', 'file', v.v. Mặc định 'file'.
	 */
	$type = apply_filters( 'media_upload_default_type', 'file' );
}

// Tab: gallery, library, hoặc theo loại cụ thể.
if ( isset( $_GET['tab'] ) ) {
	$tab = (string) $_GET['tab'];
} else {
	/**
	 * Lọc tab mặc định trong popup media cũ (trước 3.5.0).
	 *
	 * @since 2.5.0
	 *
	 * @param string $tab Tab mặc định của popup media. Mặc định 'type' (Từ Máy tính).
	 */
	$tab = apply_filters( 'media_upload_default_tab', 'type' );
}

$body_id = 'media-upload';

// Để mã xử lý hành động quyết định cách xử lý yêu cầu.
if ( 'type' === $tab || 'type_url' === $tab || ! array_key_exists( $tab, media_upload_tabs() ) ) {
	/**
	 * Kích hoạt bên trong các view loại upload cụ thể trong popup media cũ (trước 3.5.0)
	 * dựa trên tab hiện tại.
	 *
	 * Phần động của tên hook, `$type`, tham chiếu đến loại upload media
	 * cụ thể.
	 *
	 * Hook chỉ kích hoạt nếu `$tab` hiện tại là 'type' (Từ Máy tính),
	 * 'type_url' (Từ URL), hoặc, nếu tab không tồn tại (tức là chưa
	 * được đăng ký qua bộ lọc {@see 'media_upload_tabs'}).
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `media_upload_audio`
	 *  - `media_upload_file`
	 *  - `media_upload_image`
	 *  - `media_upload_video`
	 *
	 * @since 2.5.0
	 */
	do_action( "media_upload_{$type}" );
} else {
	/**
	 * Kích hoạt bên trong các view tab upload giới hạn và cụ thể trong popup
	 * media cũ (trước 3.5.0).
	 *
	 * Phần động của tên hook, `$tab`, tham chiếu đến tab upload media
	 * cụ thể. Các giá trị có thể bao gồm 'library' (Thư viện Media),
	 * hoặc bất kỳ tab tùy chỉnh nào được đăng ký qua bộ lọc {@see 'media_upload_tabs'}.
	 *
	 * @since 2.5.0
	 */
	do_action( "media_upload_{$tab}" );
}
