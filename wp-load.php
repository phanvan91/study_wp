<?php
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the wp-config.php file. The wp-config.php
 * file will then load the wp-settings.php file, which
 * will then set up the WordPress environment.
 *
 * If the wp-config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * wp-config.php file.
 *
 * Will also search for wp-config.php in WordPress' parent
 * directory to allow the WordPress directory to remain
 * untouched.
 *
 * File bootstrap để thiết lập hằng số ABSPATH
 * và load file wp-config.php. File wp-config.php
 * sau đó sẽ load file wp-settings.php, file này
 * sẽ thiết lập môi trường WordPress.
 *
 * Nếu không tìm thấy file wp-config.php thì sẽ hiển thị
 * lỗi yêu cầu người dùng thiết lập file wp-config.php.
 *
 * Cũng sẽ tìm kiếm wp-config.php trong thư mục cha của WordPress
 * để cho phép thư mục WordPress giữ nguyên không thay đổi.
 *
 * @package WordPress
 */

/** Define ABSPATH as this file's directory */
/** Định nghĩa ABSPATH là thư mục chứa file này */
dd(ABSPATH);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/*
 * The error_reporting() function can be disabled in php.ini. On systems where that is the case,
 * it's best to add a dummy function to the wp-config.php file, but as this call to the function
 * is run prior to wp-config.php loading, it is wrapped in a function_exists() check.
 *
 * Hàm error_reporting() có thể bị vô hiệu hóa trong php.ini. Trên các hệ thống như vậy,
 * tốt nhất là thêm một hàm giả vào file wp-config.php, nhưng vì lời gọi hàm này
 * được chạy trước khi wp-config.php được load, nên nó được bọc trong kiểm tra function_exists().
 */
if ( function_exists( 'error_reporting' ) ) {
	/*
	 * Initialize error reporting to a known set of levels.
	 *
	 * This will be adapted in wp_debug_mode() located in wp-includes/load.php based on WP_DEBUG.
	 * @see https://www.php.net/manual/en/errorfunc.constants.php List of known error levels.
	 *
	 * Khởi tạo error reporting với một tập hợp các mức lỗi đã biết.
	 *
	 * Điều này sẽ được điều chỉnh trong wp_debug_mode() nằm trong wp-includes/load.php dựa trên WP_DEBUG.
	 * @see https://www.php.net/manual/en/errorfunc.constants.php Danh sách các mức lỗi đã biết.
	 */
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

/*
 * If wp-config.php exists in the WordPress root, or if it exists in the root and wp-settings.php
 * doesn't, load wp-config.php. The secondary check for wp-settings.php has the added benefit
 * of avoiding cases where the current directory is a nested installation, e.g. / is WordPress(a)
 * and /blog/ is WordPress(b).
 *
 * If neither set of conditions is true, initiate loading the setup process.
 *
 * Nếu wp-config.php tồn tại trong thư mục gốc WordPress, hoặc nếu nó tồn tại ở thư mục gốc và wp-settings.php
 * không tồn tại, thì load wp-config.php. Kiểm tra thứ cấp cho wp-settings.php có lợi ích bổ sung
 * là tránh các trường hợp thư mục hiện tại là một cài đặt lồng nhau, ví dụ: / là WordPress(a)
 * và /blog/ là WordPress(b).
 *
 * Nếu không có điều kiện nào đúng, bắt đầu quá trình load setup.
 */
if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

	/** The config file resides in ABSPATH */
	/** File config nằm trong ABSPATH */
	require_once ABSPATH . 'wp-config.php';

} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

	/** The config file resides one level above ABSPATH but is not part of another installation */
	/** File config nằm ở một cấp trên ABSPATH nhưng không phải là phần của một cài đặt khác */
	require_once dirname( ABSPATH ) . '/wp-config.php';

} else {

	// A config file doesn't exist.
	// File config không tồn tại.

	define( 'WPINC', 'wp-includes' );
	require_once ABSPATH . WPINC . '/version.php';
	require_once ABSPATH . WPINC . '/compat.php';
	require_once ABSPATH . WPINC . '/load.php';

	// Check for the required PHP version and for the MySQL extension or a database drop-in.
	// Kiểm tra phiên bản PHP yêu cầu và extension MySQL hoặc database drop-in.
	wp_check_php_mysql_versions();

	// Standardize $_SERVER variables across setups.
	// Chuẩn hóa các biến $_SERVER trên các thiết lập.
	wp_fix_server_vars();

	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	require_once ABSPATH . WPINC . '/functions.php';

	$path = wp_guess_url() . '/wp-admin/setup-config.php';

	// Redirect to setup-config.php.
	// Chuyển hướng đến setup-config.php.
	if ( ! str_contains( $_SERVER['REQUEST_URI'], 'setup-config' ) ) {
		header( 'Location: ' . $path );
		exit;
	}

	wp_load_translations_early();

	// Die with an error message.
	// Dừng với thông báo lỗi.
	$die = '<p>' . sprintf(
		/* translators: %s: wp-config.php */
		__( "There doesn't seem to be a %s file. It is needed before the installation can continue." ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: 1: Documentation URL, 2: wp-config.php */
		__( 'Need more help? <a href="%1$s">Read the support article on %2$s</a>.' ),
		__( 'https://developer.wordpress.org/advanced-administration/wordpress/wp-config/' ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: %s: wp-config.php */
		__( "You can create a %s file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file." ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p><a href="' . $path . '" class="button button-large">' . __( 'Create a Configuration File' ) . '</a></p>';

	wp_die( $die, __( 'WordPress &rsaquo; Error' ) );
}
