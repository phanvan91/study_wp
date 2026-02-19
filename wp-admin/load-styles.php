<?php

/*
 * Hàm error_reporting() có thể bị vô hiệu hóa trong php.ini. Trên các hệ thống như vậy,
 * tốt nhất nên thêm một hàm giả vào file wp-config.php, nhưng vì lời gọi hàm này
 * được chạy trước khi wp-config.php được tải, nó được bọc trong kiểm tra function_exists().
 */
if ( function_exists( 'error_reporting' ) ) {
	/*
	 * Vô hiệu hóa báo cáo lỗi.
	 *
	 * Đặt thành error_reporting( -1 ) để debug.
	 */
	error_reporting( 0 );
}

// Đặt ABSPATH cho việc thực thi.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

define( 'WPINC', 'wp-includes' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

require ABSPATH . 'wp-admin/includes/noop.php';
require ABSPATH . WPINC . '/theme.php';
require ABSPATH . WPINC . '/class-wp-theme-json-resolver.php';
require ABSPATH . WPINC . '/global-styles-and-settings.php';
require ABSPATH . WPINC . '/script-loader.php';
require ABSPATH . WPINC . '/version.php';

$protocol = $_SERVER['SERVER_PROTOCOL'];
if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3' ), true ) ) {
	$protocol = 'HTTP/1.0';
}

$load = $_GET['load'];
if ( is_array( $load ) ) {
	ksort( $load );
	$load = implode( '', $load );
}

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
$load = array_unique( explode( ',', $load ) );

if ( empty( $load ) ) {
	header( "$protocol 400 Bad Request" );
	exit;
}

$rtl            = ( isset( $_GET['dir'] ) && 'rtl' === $_GET['dir'] );
$expires_offset = 31536000; // 1 năm.
$out            = '';

$wp_styles = new WP_Styles();
wp_default_styles( $wp_styles );

$etag = $wp_styles->get_etag( $load );

if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $etag ) {
	header( "$protocol 304 Not Modified" );
	exit;
}

foreach ( $load as $handle ) {
	if ( ! array_key_exists( $handle, $wp_styles->registered ) ) {
		continue;
	}

	$style = $wp_styles->registered[ $handle ];

	if ( empty( $style->src ) ) {
		continue;
	}

	$path = ABSPATH . $style->src;

	if ( $rtl && ! empty( $style->extra['rtl'] ) ) {
		// Tất cả các style mặc định đều có file RTL độc lập hoàn toàn.
		$path = str_replace( '.min.css', '-rtl.min.css', $path );
	}

	$content = get_file( $path ) . "\n";

	// Lưu ý: str_starts_with() không được sử dụng ở đây, vì wp-includes/compat.php không được tải trong file này.
	if ( 0 === strpos( $style->src, '/' . WPINC . '/css/' ) ) {
		$content = str_replace( '../images/', '../' . WPINC . '/images/', $content );
		$content = str_replace( '../js/tinymce/', '../' . WPINC . '/js/tinymce/', $content );
		$content = str_replace( '../fonts/', '../' . WPINC . '/fonts/', $content );
		$out    .= $content;
	} else {
		$out .= str_replace( '../images/', 'images/', $content );
	}
}

header( "Etag: $etag" );
header( 'Content-Type: text/css; charset=UTF-8' );
header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
header( "Cache-Control: public, max-age=$expires_offset" );

echo $out;
exit;
