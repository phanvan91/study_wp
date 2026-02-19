<?php
/**
 * Xử lý Trackbacks và Pingbacks được gửi đến WordPress.
 *
 * @since 0.71
 *
 * @package WordPress
 * @subpackage Trackbacks
 */

if ( empty( $wp ) ) {
	require_once __DIR__ . '/wp-load.php';
	wp( array( 'tb' => '1' ) );
}

// Luôn chạy như một người dùng chưa xác thực.
wp_set_current_user( 0 );

/**
 * Phản hồi một trackback.
 *
 * Phản hồi với một thông báo XML lỗi hoặc thành công.
 *
 * @since 0.71
 *
 * @param int|bool $error         Có lỗi xảy ra hay không.
 *                                Mặc định '0'. Chấp nhận '0' hoặc '1', true hoặc false.
 * @param string   $error_message Thông báo lỗi nếu có lỗi xảy ra. Mặc định chuỗi rỗng.
 */
function trackback_response( $error = 0, $error_message = '' ) {
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );

	if ( $error ) {
		echo '<?xml version="1.0" encoding="utf-8"?' . ">\n";
		echo "<response>\n";
		echo "<error>1</error>\n";
		echo "<message>$error_message</message>\n";
		echo '</response>';
		die();
	} else {
		echo '<?xml version="1.0" encoding="utf-8"?' . ">\n";
		echo "<response>\n";
		echo "<error>0</error>\n";
		echo '</response>';
	}
}

if ( ! isset( $_GET['tb_id'] ) || ! $_GET['tb_id'] ) {
	$post_id = explode( '/', $_SERVER['REQUEST_URI'] );
	$post_id = (int) $post_id[ count( $post_id ) - 1 ];
}

$trackback_url = isset( $_POST['url'] ) ? $_POST['url'] : '';
$charset       = isset( $_POST['charset'] ) ? $_POST['charset'] : '';

// Ba giá trị này được stripslash ở đây để chúng có thể được escape đúng cách sau mb_convert_encoding().
$title     = isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '';
$excerpt   = isset( $_POST['excerpt'] ) ? wp_unslash( $_POST['excerpt'] ) : '';
$blog_name = isset( $_POST['blog_name'] ) ? wp_unslash( $_POST['blog_name'] ) : '';

if ( $charset ) {
	$charset = str_replace( array( ',', ' ' ), '', strtoupper( trim( $charset ) ) );

	// Xác thực charset "sender" được chỉ định có sẵn trên site nhận.
	if ( function_exists( 'mb_list_encodings' ) && ! in_array( $charset, mb_list_encodings(), true ) ) {
		$charset = '';
	}
}

if ( ! $charset ) {
	$charset = 'ASCII, UTF-8, ISO-8859-1, JIS, EUC-JP, SJIS';
}

// Không có trường hợp sử dụng hợp lệ cho UTF-7.
if ( str_contains( $charset, 'UTF-7' ) ) {
	die;
}

// Dành cho các trackback quốc tế.
if ( function_exists( 'mb_convert_encoding' ) ) {
	$title     = mb_convert_encoding( $title, get_option( 'blog_charset' ), $charset );
	$excerpt   = mb_convert_encoding( $excerpt, get_option( 'blog_charset' ), $charset );
	$blog_name = mb_convert_encoding( $blog_name, get_option( 'blog_charset' ), $charset );
}

// Escape các giá trị để sử dụng trong trackback.
$title     = wp_slash( $title );
$excerpt   = wp_slash( $excerpt );
$blog_name = wp_slash( $blog_name );

if ( is_single() || is_page() ) {
	$post_id = $posts[0]->ID;
}

if ( ! isset( $post_id ) || ! (int) $post_id ) {
	trackback_response( 1, __( 'I really need an ID for this to work.' ) );
}

if ( empty( $title ) && empty( $trackback_url ) && empty( $blog_name ) ) {
	// Nếu nó không giống trackback chút nào.
	wp_redirect( get_permalink( $post_id ) );
	exit;
}

if ( ! empty( $trackback_url ) && ! empty( $title ) ) {
	/**
	 * Kích hoạt trước khi trackback được thêm vào bài viết.
	 *
	 * @since 4.7.0
	 *
	 * @param int    $post_id       ID bài viết liên quan đến trackback.
	 * @param string $trackback_url URL của trackback.
	 * @param string $charset       Bộ ký tự.
	 * @param string $title         Tiêu đề trackback.
	 * @param string $excerpt       Đoạn trích trackback.
	 * @param string $blog_name     Tên trang web.
	 */
	do_action( 'pre_trackback_post', $post_id, $trackback_url, $charset, $title, $excerpt, $blog_name );

	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );

	if ( ! pings_open( $post_id ) ) {
		trackback_response( 1, __( 'Sorry, trackbacks are closed for this item.' ) );
	}

	$title   = wp_html_excerpt( $title, 250, '&#8230;' );
	$excerpt = wp_html_excerpt( $excerpt, 252, '&#8230;' );

	$comment_post_id      = (int) $post_id;
	$comment_author       = $blog_name;
	$comment_author_email = '';
	$comment_author_url   = $trackback_url;
	$comment_content      = "<strong>$title</strong>\n\n$excerpt";
	$comment_type         = 'trackback';

	$dupe = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_author_url = %s",
			$comment_post_id,
			$comment_author_url
		)
	);

	if ( $dupe ) {
		trackback_response( 1, __( 'There is already a ping from that URL for this post.' ) );
	}

	$commentdata = array(
		'comment_post_ID' => $comment_post_id,
	);

	$commentdata += compact(
		'comment_author',
		'comment_author_email',
		'comment_author_url',
		'comment_content',
		'comment_type'
	);

	$result = wp_new_comment( $commentdata );

	if ( is_wp_error( $result ) ) {
		trackback_response( 1, $result->get_error_message() );
	}

	$trackback_id = $wpdb->insert_id;

	/**
	 * Kích hoạt sau khi trackback được thêm vào bài viết.
	 *
	 * @since 1.2.0
	 *
	 * @param int $trackback_id ID của trackback.
	 */
	do_action( 'trackback_post', $trackback_id );

	trackback_response( 0 );
}
