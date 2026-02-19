<?php
/**
 * Các hàm cần thiết để tải WordPress.
 *
 * @package WordPress
 */

/**
 * Trả về giao thức HTTP được gửi bởi máy chủ.
 *
 * @since 4.4.0
 *
 * @return string Giao thức HTTP. Mặc định: HTTP/1.0.
 */
function wp_get_server_protocol() {
	$protocol = isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : '';

	if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3' ), true ) ) {
		$protocol = 'HTTP/1.0';
	}

	return $protocol;
}

/**
 * Sửa các biến `$_SERVER` cho các cấu hình khác nhau.
 *
 * @since 3.0.0
 * @access private
 *
 * @global string $PHP_SELF Tên tệp của script đang thực thi,
 *                          tương đối so với thư mục gốc tài liệu.
 */
function wp_fix_server_vars() {
	global $PHP_SELF;

	$default_server_values = array(
		'SERVER_SOFTWARE' => '',
		'REQUEST_URI'     => '',
	);

	$_SERVER = array_merge( $default_server_values, $_SERVER );

	// Sửa cho IIS khi chạy với PHP ISAPI.
	if ( empty( $_SERVER['REQUEST_URI'] )
		|| ( 'cgi-fcgi' !== PHP_SAPI && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) )
	) {

		if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
			// IIS Mod-Rewrite.
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		} elseif ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
			// IIS Isapi_Rewrite.
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		} else {
			// Sử dụng ORIG_PATH_INFO nếu không có PATH_INFO.
			if ( ! isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) ) {
				$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
			}

			// Một số cấu hình IIS + PHP đặt script-name trong path-info (không cần thêm lần nữa).
			if ( isset( $_SERVER['PATH_INFO'] ) ) {
				if ( $_SERVER['PATH_INFO'] === $_SERVER['SCRIPT_NAME'] ) {
					$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
				} else {
					$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
				}
			}

			// Thêm chuỗi truy vấn nếu nó tồn tại và không rỗng.
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
	}

	// Sửa cho PHP dạng CGI host đặt SCRIPT_FILENAME kết thúc bằng php.cgi cho tất cả yêu cầu.
	if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && str_ends_with( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' ) ) {
		$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
	}

	// Sửa cho Dreamhost và các PHP CGI host khác.
	if ( isset( $_SERVER['SCRIPT_NAME'] ) && str_contains( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) ) {
		unset( $_SERVER['PATH_INFO'] );
	}

	// Sửa PHP_SELF rỗng.
	$PHP_SELF = $_SERVER['PHP_SELF'];
	if ( empty( $PHP_SELF ) ) {
		$_SERVER['PHP_SELF'] = preg_replace( '/(\?.*)?$/', '', $_SERVER['REQUEST_URI'] );
		$PHP_SELF            = $_SERVER['PHP_SELF'];
	}

	wp_populate_basic_auth_from_authorization_header();
}

/**
 * Điền thông tin Basic Auth từ header Authorization.
 *
 * Một số máy chủ chạy ở chế độ CGI hoặc FastCGI không truyền header Authorization
 * đến WordPress. Nếu nó đã được viết lại thành header `HTTP_AUTHORIZATION`,
 * điền vào các biến $_SERVER thích hợp thay thế.
 *
 * @since 5.6.0
 */
function wp_populate_basic_auth_from_authorization_header() {
	// Nếu không có gì để lấy, trả về sớm.
	if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) && ! isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
		return;
	}

	// Nếu bất kỳ khóa PHP_AUTH nào đã được đặt, không làm gì.
	if ( isset( $_SERVER['PHP_AUTH_USER'] ) || isset( $_SERVER['PHP_AUTH_PW'] ) ) {
		return;
	}

	// Từ điều kiện trước, một trong hai phải được đặt.
	$header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

	// Kiểm tra xem mẫu có khớp với mong đợi không.
	if ( ! preg_match( '%^Basic [a-z\d/+]*={0,2}$%i', $header ) ) {
		return;
	}

	// Bỏ `Basic `, token sẽ bắt đầu từ ký tự thứ sáu.
	$token    = substr( $header, 6 );
	$userpass = base64_decode( $token );

	// Phải có ít nhất một dấu hai chấm trong chuỗi.
	if ( ! str_contains( $userpass, ':' ) ) {
		return;
	}

	list( $user, $pass ) = explode( ':', $userpass, 2 );

	// Đặt chúng vào đúng khóa mà chúng ta mong đợi sau đó.
	$_SERVER['PHP_AUTH_USER'] = $user;
	$_SERVER['PHP_AUTH_PW']   = $pass;
}

/**
 * Kiểm tra phiên bản PHP yêu cầu, và phần mở rộng mysqli hoặc
 * một drop-in cơ sở dữ liệu.
 *
 * Dừng chương trình nếu không đáp ứng yêu cầu.
 *
 * @since 3.0.0
 * @access private
 *
 * @global string   $required_php_version    Chuỗi phiên bản PHP yêu cầu.
 * @global string[] $required_php_extensions Tên các phần mở rộng PHP yêu cầu.
 * @global string   $wp_version              Chuỗi phiên bản WordPress.
 */
function wp_check_php_mysql_versions() {
	global $required_php_version, $required_php_extensions, $wp_version;

	$php_version = PHP_VERSION;

	if ( version_compare( $required_php_version, $php_version, '>' ) ) {
		$protocol = wp_get_server_protocol();
		header( sprintf( '%s 500 Internal Server Error', $protocol ), true, 500 );
		header( 'Content-Type: text/html; charset=utf-8' );
		printf(
			'Your server is running PHP version %1$s but WordPress %2$s requires at least %3$s.',
			$php_version,
			$wp_version,
			$required_php_version
		);
		exit( 1 );
	}

	$missing_extensions = array();

	if ( isset( $required_php_extensions ) && is_array( $required_php_extensions ) ) {
		foreach ( $required_php_extensions as $extension ) {
			if ( extension_loaded( $extension ) ) {
				continue;
			}

			$missing_extensions[] = sprintf(
				'WordPress %1$s requires the <code>%2$s</code> PHP extension.',
				$wp_version,
				$extension
			);
		}
	}

	if ( count( $missing_extensions ) > 0 ) {
		$protocol = wp_get_server_protocol();
		header( sprintf( '%s 500 Internal Server Error', $protocol ), true, 500 );
		header( 'Content-Type: text/html; charset=utf-8' );
		echo implode( '<br>', $missing_extensions );
		exit( 1 );
	}

	// Đoạn này chạy trước khi các hằng số mặc định được định nghĩa, nên không thể giả định WP_CONTENT_DIR đã được đặt.
	$wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';

	if ( ! function_exists( 'mysqli_connect' )
		&& ! file_exists( $wp_content_dir . '/db.php' )
	) {
		require_once ABSPATH . WPINC . '/functions.php';
		wp_load_translations_early();

		$message = '<p>' . __( 'Your PHP installation appears to be missing the MySQL extension which is required by WordPress.' ) . "</p>\n";

		$message .= '<p>' . sprintf(
			/* translators: %s: mysqli. */
			__( 'Please check that the %s PHP extension is installed and enabled.' ),
			'<code>mysqli</code>'
		) . "</p>\n";

		$message .= '<p>' . sprintf(
			/* translators: %s: Support forums URL. */
			__( 'If you are unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress support forums</a>.' ),
			__( 'https://wordpress.org/support/forums/' )
		) . "</p>\n";

		$args = array(
			'exit' => false,
			'code' => 'mysql_not_found',
		);
		wp_die(
			$message,
			__( 'Requirements Not Met' ),
			$args
		);
		exit( 1 );
	}
}

/**
 * Lấy loại môi trường hiện tại.
 *
 * Loại có thể được đặt thông qua biến hệ thống toàn cục `WP_ENVIRONMENT_TYPE`,
 * hoặc một hằng số cùng tên.
 *
 * Các giá trị có thể là 'local', 'development', 'staging', và 'production'.
 * Nếu không được đặt, loại mặc định là 'production'.
 *
 * @since 5.5.0
 * @since 5.5.1 Thêm loại 'local'.
 * @since 5.5.1 Loại bỏ khả năng thay đổi danh sách các loại.
 *
 * @return string Loại môi trường hiện tại.
 */
function wp_get_environment_type() {
	static $current_env = '';

	if ( ! defined( 'WP_RUN_CORE_TESTS' ) && $current_env ) {
		return $current_env;
	}

	$wp_environments = array(
		'local',
		'development',
		'staging',
		'production',
	);

	// Thêm ghi chú về hằng số WP_ENVIRONMENT_TYPES đã bị ngừng hỗ trợ.
	if ( defined( 'WP_ENVIRONMENT_TYPES' ) && function_exists( '_deprecated_argument' ) ) {
		if ( function_exists( '__' ) ) {
			/* translators: %s: WP_ENVIRONMENT_TYPES */
			$message = sprintf( __( 'The %s constant is no longer supported.' ), 'WP_ENVIRONMENT_TYPES' );
		} else {
			$message = sprintf( 'The %s constant is no longer supported.', 'WP_ENVIRONMENT_TYPES' );
		}

		_deprecated_argument(
			'define()',
			'5.5.1',
			$message
		);
	}

	// Kiểm tra xem biến môi trường đã được đặt chưa, nếu `getenv` có sẵn trên hệ thống.
	if ( function_exists( 'getenv' ) ) {
		$has_env = getenv( 'WP_ENVIRONMENT_TYPE' );
		if ( false !== $has_env ) {
			$current_env = $has_env;
		}
	}

	// Lấy môi trường từ hằng số, giá trị này ghi đè biến hệ thống toàn cục.
	if ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE ) {
		$current_env = WP_ENVIRONMENT_TYPE;
	}

	// Đảm bảo môi trường là một giá trị được phép, và không bị đặt nhầm thành giá trị không hợp lệ.
	if ( ! in_array( $current_env, $wp_environments, true ) ) {
		$current_env = 'production';
	}

	return $current_env;
}

/**
 * Lấy chế độ phát triển hiện tại.
 *
 * Chế độ phát triển ảnh hưởng đến cách một số phần của ứng dụng WordPress hoạt động,
 * điều này liên quan khi phát triển cho WordPress.
 *
 * Chế độ phát triển có thể được đặt thông qua hằng số `WP_DEVELOPMENT_MODE` trong `wp-config.php`.
 * Các giá trị có thể là 'core', 'plugin', 'theme', 'all', hoặc chuỗi rỗng để tắt
 * chế độ phát triển. 'all' là giá trị đặc biệt để biểu thị rằng cả ba chế độ phát triển
 * ('core', 'plugin', và 'theme') đều được bật.
 *
 * Chế độ phát triển được xem xét riêng biệt với `WP_DEBUG` và wp_get_environment_type().
 * Nó không ảnh hưởng đến đầu ra debug, mà là các sắc thái chức năng trong WordPress.
 *
 * Hàm này lấy giá trị chế độ phát triển hiện tại. Để kiểm tra xem
 * một chế độ phát triển cụ thể có được bật hay không, sử dụng wp_is_development_mode().
 *
 * @since 6.3.0
 *
 * @return string Chế độ phát triển hiện tại.
 */
function wp_get_development_mode() {
	static $current_mode = null;

	if ( ! defined( 'WP_RUN_CORE_TESTS' ) && null !== $current_mode ) {
		return $current_mode;
	}

	$development_mode = WP_DEVELOPMENT_MODE;

	// Dành riêng cho kiểm thử core, dựa vào biến toàn cục `$_wp_tests_development_mode`.
	if ( defined( 'WP_RUN_CORE_TESTS' ) && isset( $GLOBALS['_wp_tests_development_mode'] ) ) {
		$development_mode = $GLOBALS['_wp_tests_development_mode'];
	}

	$valid_modes = array(
		'core',
		'plugin',
		'theme',
		'all',
		'',
	);

	if ( ! in_array( $development_mode, $valid_modes, true ) ) {
		$development_mode = '';
	}

	$current_mode = $development_mode;

	return $current_mode;
}

/**
 * Kiểm tra xem trang web có đang ở chế độ phát triển được chỉ định hay không.
 *
 * @since 6.3.0
 *
 * @param string $mode Chế độ phát triển cần kiểm tra. Có thể là 'core', 'plugin', 'theme', hoặc 'all'.
 * @return bool True nếu chế độ được chỉ định nằm trong chế độ phát triển hiện tại, false nếu không.
 */
function wp_is_development_mode( $mode ) {
	$current_mode = wp_get_development_mode();
	if ( empty( $current_mode ) ) {
		return false;
	}

	// Trả về true nếu chế độ hiện tại bao gồm tất cả các chế độ.
	if ( 'all' === $current_mode ) {
		return true;
	}

	// Trả về true nếu chế độ hiện tại là chế độ được chỉ định.
	return $mode === $current_mode;
}

/**
 * Đảm bảo toàn bộ WordPress không được tải khi xử lý yêu cầu favicon.ico.
 *
 * Thay vào đó, gửi các header cho favicon có độ dài bằng không và thoát.
 *
 * @since 3.0.0
 * @deprecated 5.4.0 Ngừng hỗ trợ, ưu tiên sử dụng do_favicon().
 */
function wp_favicon_request() {
	if ( '/favicon.ico' === $_SERVER['REQUEST_URI'] ) {
		header( 'Content-Type: image/vnd.microsoft.icon' );
		exit;
	}
}

/**
 * Dừng chương trình với thông báo bảo trì khi điều kiện được đáp ứng.
 *
 * Thông báo mặc định có thể được thay thế bằng cách sử dụng drop-in (maintenance.php trong
 * thư mục wp-content).
 *
 * @since 3.0.0
 * @access private
 */
function wp_maintenance() {
	// Trả về nếu chế độ bảo trì bị tắt.
	if ( ! wp_is_maintenance_mode() ) {
		return;
	}

	if ( file_exists( WP_CONTENT_DIR . '/maintenance.php' ) ) {
		require_once WP_CONTENT_DIR . '/maintenance.php';
		die();
	}

	require_once ABSPATH . WPINC . '/functions.php';
	wp_load_translations_early();

	header( 'Retry-After: 600' );

	wp_die(
		__( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' ),
		__( 'Maintenance' ),
		503
	);
}

/**
 * Kiểm tra xem chế độ bảo trì có được bật không.
 *
 * Kiểm tra tệp trong thư mục gốc WordPress có tên ".maintenance".
 * Tệp này sẽ chứa biến $upgrading, được đặt là thời gian tệp
 * được tạo. Nếu tệp được tạo cách đây ít hơn 10 phút, WordPress
 * đang ở chế độ bảo trì.
 *
 * @since 5.5.0
 *
 * @global int $upgrading Dấu thời gian Unix đánh dấu khi nâng cấp WordPress bắt đầu.
 *
 * @return bool True nếu chế độ bảo trì được bật, false nếu không.
 */
function wp_is_maintenance_mode() {
	global $upgrading;

	if ( ! file_exists( ABSPATH . '.maintenance' ) || wp_installing() ) {
		return false;
	}

	require ABSPATH . '.maintenance';

	// Nếu dấu thời gian $upgrading cũ hơn 10 phút, coi như bảo trì đã kết thúc.
	if ( ( time() - $upgrading ) >= 10 * MINUTE_IN_SECONDS ) {
		return false;
	}

	// Không bật chế độ bảo trì khi đang quét lỗi nghiêm trọng.
	if ( is_int( $upgrading ) && isset( $_REQUEST['wp_scrape_key'], $_REQUEST['wp_scrape_nonce'] ) ) {
		$key   = stripslashes( $_REQUEST['wp_scrape_key'] );
		$nonce = stripslashes( $_REQUEST['wp_scrape_nonce'] );

		if ( md5( $upgrading ) === $key && (int) $nonce === $upgrading ) {
			return false;
		}
	}

	/**
	 * Lọc xem có bật chế độ bảo trì hay không.
	 *
	 * Bộ lọc này chạy trước khi có thể được sử dụng bởi plugin. Nó được thiết kế cho
	 * các runtime không phải web. Nếu bộ lọc này trả về true, chế độ bảo trì sẽ
	 * được kích hoạt và yêu cầu sẽ kết thúc. Nếu false, yêu cầu sẽ được phép
	 * tiếp tục xử lý ngay cả khi chế độ bảo trì nên được kích hoạt.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $enable_checks Có bật chế độ bảo trì hay không. Mặc định true.
	 * @param int  $upgrading     Dấu thời gian được đặt trong tệp .maintenance.
	 */
	if ( ! apply_filters( 'enable_maintenance_mode', true, $upgrading ) ) {
		return false;
	}

	return true;
}

/**
 * Lấy thời gian đã trôi qua kể từ đầu script PHP này.
 *
 * @since 5.8.0
 *
 * @return float Số giây kể từ khi script PHP bắt đầu.
 */
function timer_float() {
	return microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'];
}

/**
 * Khởi động bộ đếm thời gian vi mô của WordPress.
 *
 * @since 0.71
 * @access private
 *
 * @global float $timestart Dấu thời gian Unix được đặt ở đầu tải trang.
 * @see timer_stop()
 *
 * @return bool Luôn trả về true.
 */
function timer_start() {
	global $timestart;

	$timestart = microtime( true );

	return true;
}

/**
 * Lấy hoặc hiển thị thời gian từ khi bắt đầu tải trang đến khi hàm được gọi.
 *
 * @since 0.71
 *
 * @global float   $timestart Số giây từ khi timer_start() được gọi.
 * @global float   $timeend   Số giây từ khi hàm được gọi.
 *
 * @param int|bool $display   Có echo hay trả về kết quả. Chấp nhận 0|false để trả về,
 *                            1|true để echo. Mặc định 0|false.
 * @param int      $precision Số chữ số sau dấu thập phân để hiển thị.
 *                            Mặc định 3.
 * @return string Kết quả tính toán thời gian "giây.micro giây". Số được định dạng
 *                cho người đọc, được bản địa hóa và làm tròn.
 */
function timer_stop( $display = 0, $precision = 3 ) {
	global $timestart, $timeend;

	$timeend   = microtime( true );
	$timetotal = $timeend - $timestart;

	if ( function_exists( 'number_format_i18n' ) ) {
		$r = number_format_i18n( $timetotal, $precision );
	} else {
		$r = number_format( $timetotal, $precision );
	}

	if ( $display ) {
		echo $r;
	}

	return $r;
}

/**
 * Đặt báo cáo lỗi PHP dựa trên cài đặt debug của WordPress.
 *
 * Sử dụng ba hằng số: `WP_DEBUG`, `WP_DEBUG_DISPLAY`, và `WP_DEBUG_LOG`.
 * Cả ba đều có thể được định nghĩa trong wp-config.php. Mặc định, `WP_DEBUG` và
 * `WP_DEBUG_LOG` được đặt là false, và `WP_DEBUG_DISPLAY` được đặt là true.
 *
 * Khi `WP_DEBUG` là true, tất cả thông báo PHP sẽ được báo cáo. WordPress cũng sẽ
 * hiển thị các thông báo nội bộ: khi một hàm WordPress đã ngừng hỗ trợ, tham số
 * hàm, hoặc tệp được sử dụng. Mã ngừng hỗ trợ có thể được loại bỏ trong phiên bản
 * sau.
 *
 * Các nhà phát triển plugin và theme được khuyến khích sử dụng `WP_DEBUG`
 * trong môi trường phát triển của họ.
 *
 * `WP_DEBUG_DISPLAY` và `WP_DEBUG_LOG` không có tác dụng trừ khi `WP_DEBUG`
 * là true.
 *
 * Khi `WP_DEBUG_DISPLAY` là true, WordPress sẽ buộc hiển thị lỗi.
 * `WP_DEBUG_DISPLAY` mặc định là true. Định nghĩa nó là null ngăn WordPress
 * thay đổi cài đặt cấu hình toàn cục. Định nghĩa `WP_DEBUG_DISPLAY`
 * là false sẽ buộc ẩn lỗi.
 *
 * Khi `WP_DEBUG_LOG` là true, lỗi sẽ được ghi vào `wp-content/debug.log`.
 * Khi `WP_DEBUG_LOG` là một đường dẫn hợp lệ, lỗi sẽ được ghi vào tệp chỉ định.
 *
 * Lỗi không bao giờ được hiển thị cho các yêu cầu XML-RPC, REST, `ms-files.php`, và Ajax.
 *
 * @since 3.0.0
 * @since 5.1.0 `WP_DEBUG_LOG` có thể là đường dẫn tệp.
 * @access private
 */
function wp_debug_mode() {
	/**
	 * Lọc xem có cho phép kiểm tra chế độ debug hay không.
	 *
	 * Bộ lọc này chạy trước khi có thể được sử dụng bởi plugin. Nó được thiết kế cho
	 * các runtime không phải web. Trả về false khiến các hằng số `WP_DEBUG` và liên quan
	 * không được kiểm tra và các giá trị PHP mặc định cho lỗi
	 * sẽ được sử dụng trừ khi bạn tự cập nhật chúng.
	 *
	 * Để sử dụng bộ lọc này bạn phải định nghĩa một biến toàn cục `$wp_filter` trước
	 * khi WordPress tải, thường trong `wp-config.php`.
	 *
	 * Ví dụ:
	 *
	 *     $GLOBALS['wp_filter'] = array(
	 *         'enable_wp_debug_mode_checks' => array(
	 *             10 => array(
	 *                 array(
	 *                     'accepted_args' => 0,
	 *                     'function'      => function() {
	 *                         return false;
	 *                     },
	 *                 ),
	 *             ),
	 *         ),
	 *     );
	 *
	 * @since 4.6.0
	 *
	 * @param bool $enable_debug_mode Có cho phép kiểm tra chế độ debug hay không. Mặc định true.
	 */
	if ( ! apply_filters( 'enable_wp_debug_mode_checks', true ) ) {
		return;
	}

	if ( WP_DEBUG ) {
		error_reporting( E_ALL );

		if ( WP_DEBUG_DISPLAY ) {
			ini_set( 'display_errors', 1 );
		} elseif ( null !== WP_DEBUG_DISPLAY ) {
			ini_set( 'display_errors', 0 );
		}

		if ( in_array( strtolower( (string) WP_DEBUG_LOG ), array( 'true', '1' ), true ) ) {
			$log_path = WP_CONTENT_DIR . '/debug.log';
		} elseif ( is_string( WP_DEBUG_LOG ) ) {
			$log_path = WP_DEBUG_LOG;
		} else {
			$log_path = false;
		}

		if ( $log_path ) {
			ini_set( 'log_errors', 1 );
			ini_set( 'error_log', $log_path );
		}
	} else {
		error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
	}

	/*
	 * Kiểm tra 'REST_REQUEST' ở đây mang tính lạc quan vì hằng số này
	 * nhiều khả năng chưa được đặt tại thời điểm này ngay cả khi thực tế đây là yêu cầu REST.
	 */
	if ( defined( 'XMLRPC_REQUEST' ) || defined( 'REST_REQUEST' ) || defined( 'MS_FILES_REQUEST' )
		|| ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
		|| wp_doing_ajax() || wp_is_json_request()
	) {
		ini_set( 'display_errors', 0 );
	}
}

/**
 * Đặt vị trí của thư mục ngôn ngữ.
 *
 * Để đặt thư mục thủ công, định nghĩa hằng số `WP_LANG_DIR`
 * trong wp-config.php.
 *
 * Nếu thư mục ngôn ngữ tồn tại trong `WP_CONTENT_DIR`, nó
 * sẽ được sử dụng. Nếu không, thư mục ngôn ngữ được giả định nằm
 * trong `WPINC`.
 *
 * @since 3.0.0
 * @access private
 */
function wp_set_lang_dir() {
	if ( ! defined( 'WP_LANG_DIR' ) ) {
		if ( file_exists( WP_CONTENT_DIR . '/languages' ) && @is_dir( WP_CONTENT_DIR . '/languages' )
			|| ! @is_dir( ABSPATH . WPINC . '/languages' )
		) {
			/**
			 * Đường dẫn máy chủ của thư mục ngôn ngữ.
			 *
			 * Không có dấu gạch chéo đầu, không có dấu gạch chéo cuối, đường dẫn đầy đủ, không tương đối với ABSPATH
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', WP_CONTENT_DIR . '/languages' );

			if ( ! defined( 'LANGDIR' ) ) {
				// Đường dẫn tương đối tĩnh cũ được duy trì cho tương thích ngược hạn chế - không hoạt động trong một số trường hợp.
				define( 'LANGDIR', 'wp-content/languages' );
			}
		} else {
			/**
			 * Đường dẫn máy chủ của thư mục ngôn ngữ.
			 *
			 * Không có dấu gạch chéo đầu, không có dấu gạch chéo cuối, đường dẫn đầy đủ, không tương đối với `ABSPATH`.
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', ABSPATH . WPINC . '/languages' );

			if ( ! defined( 'LANGDIR' ) ) {
				// Đường dẫn tương đối cũ được duy trì cho tương thích ngược.
				define( 'LANGDIR', WPINC . '/languages' );
			}
		}
	}
}

/**
 * Tải tệp lớp cơ sở dữ liệu và khởi tạo biến toàn cục `$wpdb`.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 */
function require_wp_db() {
	global $wpdb;

	require_once ABSPATH . WPINC . '/class-wpdb.php';

	if ( file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
		require_once WP_CONTENT_DIR . '/db.php';
	}

	if ( isset( $wpdb ) ) {
		return;
	}

	$dbuser     = defined( 'DB_USER' ) ? DB_USER : '';
	$dbpassword = defined( 'DB_PASSWORD' ) ? DB_PASSWORD : '';
	$dbname     = defined( 'DB_NAME' ) ? DB_NAME : '';
	$dbhost     = defined( 'DB_HOST' ) ? DB_HOST : '';

	$wpdb = new wpdb( $dbuser, $dbpassword, $dbname, $dbhost );
}

/**
 * Đặt tiền tố bảng cơ sở dữ liệu và các chỉ định định dạng cho
 * các cột bảng cơ sở dữ liệu.
 *
 * Các cột không được liệt kê ở đây mặc định là `%s`.
 *
 * @since 3.0.0
 * @access private
 *
 * @global wpdb   $wpdb         Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 * @global string $table_prefix Tiền tố bảng cơ sở dữ liệu.
 */
function wp_set_wpdb_vars() {
	global $wpdb, $table_prefix;

	if ( ! empty( $wpdb->error ) ) {
		dead_db();
	}

	$wpdb->field_types = array(
		'post_author'      => '%d',
		'post_parent'      => '%d',
		'menu_order'       => '%d',
		'term_id'          => '%d',
		'term_group'       => '%d',
		'term_taxonomy_id' => '%d',
		'parent'           => '%d',
		'count'            => '%d',
		'object_id'        => '%d',
		'term_order'       => '%d',
		'ID'               => '%d',
		'comment_ID'       => '%d',
		'comment_post_ID'  => '%d',
		'comment_parent'   => '%d',
		'user_id'          => '%d',
		'link_id'          => '%d',
		'link_owner'       => '%d',
		'link_rating'      => '%d',
		'option_id'        => '%d',
		'blog_id'          => '%d',
		'meta_id'          => '%d',
		'post_id'          => '%d',
		'user_status'      => '%d',
		'umeta_id'         => '%d',
		'comment_karma'    => '%d',
		'comment_count'    => '%d',
		// Multisite:
		'active'           => '%d',
		'cat_id'           => '%d',
		'deleted'          => '%d',
		'lang_id'          => '%d',
		'mature'           => '%d',
		'public'           => '%d',
		'site_id'          => '%d',
		'spam'             => '%d',
	);

	$prefix = $wpdb->set_prefix( $table_prefix );

	if ( is_wp_error( $prefix ) ) {
		wp_load_translations_early();
		wp_die(
			sprintf(
				/* translators: 1: $table_prefix, 2: wp-config.php */
				__( '<strong>Error:</strong> %1$s in %2$s can only contain numbers, letters, and underscores.' ),
				'<code>$table_prefix</code>',
				'<code>wp-config.php</code>'
			)
		);
	}
}

/**
 * Bật/tắt `$_wp_using_ext_object_cache` mà không trực tiếp
 * chạm vào biến toàn cục.
 *
 * @since 3.7.0
 *
 * @global bool $_wp_using_ext_object_cache
 *
 * @param bool $using Có đang sử dụng bộ nhớ đệm đối tượng bên ngoài hay không.
 * @return bool Cài đặt 'đang sử dụng' hiện tại.
 */
function wp_using_ext_object_cache( $using = null ) {
	global $_wp_using_ext_object_cache;

	$current_using = $_wp_using_ext_object_cache;

	if ( null !== $using ) {
		$_wp_using_ext_object_cache = $using;
	}

	return $current_using;
}

/**
 * Khởi động bộ nhớ đệm đối tượng WordPress.
 *
 * Nếu tệp object-cache.php tồn tại trong thư mục wp-content,
 * nó sử dụng drop-in đó làm bộ nhớ đệm đối tượng bên ngoài.
 *
 * @since 3.0.0
 * @access private
 *
 * @global array $wp_filter Lưu trữ tất cả các bộ lọc.
 */
function wp_start_object_cache() {
	global $wp_filter;
	static $first_init = true;

	// Chỉ thực hiện các kiểm tra sau đây một lần.

	/**
	 * Lọc xem có bật tải drop-in object-cache.php hay không.
	 *
	 * Bộ lọc này chạy trước khi có thể được sử dụng bởi plugin. Nó được thiết kế cho các runtime
	 * không phải web. Nếu trả về false, object-cache.php sẽ không bao giờ được tải.
	 *
	 * @since 5.8.0
	 *
	 * @param bool $enable_object_cache Có bật tải object-cache.php (nếu có) hay không.
	 *                                  Mặc định true.
	 */
	if ( $first_init && apply_filters( 'enable_loading_object_cache_dropin', true ) ) {
		if ( ! function_exists( 'wp_cache_init' ) ) {
			/*
			 * Đây là tình huống bình thường. Lần chạy đầu tiên của hàm này. Không có
			 * backend bộ nhớ đệm nào được tải.
			 *
			 * Chúng ta thử tải một backend bộ nhớ đệm tùy chỉnh, và sau đó, nếu nó
			 * dẫn đến hàm wp_cache_init() tồn tại, chúng ta ghi nhận
			 * rằng bộ nhớ đệm đối tượng bên ngoài đang được sử dụng.
			 */
			if ( file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
				require_once WP_CONTENT_DIR . '/object-cache.php';

				if ( function_exists( 'wp_cache_init' ) ) {
					wp_using_ext_object_cache( true );
				}

				// Khởi tạo lại các hook được thêm thủ công bởi object-cache.php.
				if ( $wp_filter ) {
					$wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
				}
			}
		} elseif ( ! wp_using_ext_object_cache() && file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			/*
			 * Đôi khi advanced-cache.php có thể tải object-cache.php trước
			 * khi hàm này chạy. Điều này phá vỡ kiểm tra function_exists()
			 * ở trên và có thể khiến wp_using_ext_object_cache() trả về
			 * false khi thực tế bộ nhớ đệm bên ngoài đang được sử dụng.
			 */
			wp_using_ext_object_cache( true );
		}
	}

	if ( ! wp_using_ext_object_cache() ) {
		require_once ABSPATH . WPINC . '/cache.php';
	}

	require_once ABSPATH . WPINC . '/cache-compat.php';

	/*
	 * Nếu bộ nhớ đệm hỗ trợ đặt lại, đặt lại thay vì khởi tạo nếu đã
	 * được khởi tạo. Đặt lại báo hiệu cho bộ nhớ đệm rằng các ID toàn cục
	 * đã thay đổi và nó có thể cần cập nhật khóa và dọn dẹp bộ nhớ đệm.
	 */
	if ( ! $first_init && function_exists( 'wp_cache_switch_to_blog' ) ) {
		wp_cache_switch_to_blog( get_current_blog_id() );
	} elseif ( function_exists( 'wp_cache_init' ) ) {
		wp_cache_init();
	}

	if ( function_exists( 'wp_cache_add_global_groups' ) ) {
		wp_cache_add_global_groups(
			array(
				'blog-details',
				'blog-id-cache',
				'blog-lookup',
				'blog_meta',
				'global-posts',
				'image_editor',
				'networks',
				'network-queries',
				'sites',
				'site-details',
				'site-options',
				'site-queries',
				'site-transient',
				'theme_files',
				'translation_files',
				'rss',
				'users',
				'user-queries',
				'user_meta',
				'useremail',
				'userlogins',
				'userslugs',
			)
		);

		wp_cache_add_non_persistent_groups( array( 'counts', 'plugins', 'theme_json' ) );
	}

	$first_init = false;
}

/**
 * Chuyển hướng đến trình cài đặt nếu WordPress chưa được cài đặt.
 *
 * Dừng chương trình với thông báo lỗi khi Multisite được bật.
 *
 * @since 3.0.0
 * @access private
 */
function wp_not_installed() {
	if ( is_blog_installed() || wp_installing() ) {
		return;
	}

	nocache_headers();

	if ( is_multisite() ) {
		wp_die( __( 'The site you have requested is not installed properly. Please contact the system administrator.' ) );
	}

	require ABSPATH . WPINC . '/kses.php';
	require ABSPATH . WPINC . '/pluggable.php';

	$link = wp_guess_url() . '/wp-admin/install.php';

	wp_redirect( $link );
	die();
}

/**
 * Lấy mảng các tệp plugin bắt buộc (must-use).
 *
 * Thư mục mặc định là wp-content/mu-plugins. Để thay đổi thư mục
 * mặc định thủ công, định nghĩa `WPMU_PLUGIN_DIR` và `WPMU_PLUGIN_URL`
 * trong wp-config.php.
 *
 * @since 3.0.0
 * @access private
 *
 * @return string[] Mảng các đường dẫn tuyệt đối của tệp cần include.
 */
function wp_get_mu_plugins() {
	$mu_plugins = array();

	if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
		return $mu_plugins;
	}

	$dh = opendir( WPMU_PLUGIN_DIR );
	if ( ! $dh ) {
		return $mu_plugins;
	}

	while ( ( $plugin = readdir( $dh ) ) !== false ) {
		if ( str_ends_with( $plugin, '.php' ) ) {
			$mu_plugins[] = WPMU_PLUGIN_DIR . '/' . $plugin;
		}
	}

	closedir( $dh );

	sort( $mu_plugins );

	return $mu_plugins;
}

/**
 * Lấy mảng các tệp plugin đang hoạt động và hợp lệ.
 *
 * Trong khi nâng cấp hoặc cài đặt WordPress, không có plugin nào được trả về.
 *
 * Thư mục mặc định là `wp-content/plugins`. Để thay đổi thư mục
 * mặc định thủ công, định nghĩa `WP_PLUGIN_DIR` và `WP_PLUGIN_URL`
 * trong `wp-config.php`.
 *
 * @since 3.0.0
 * @access private
 *
 * @return string[] Mảng các đường dẫn tệp plugin tương đối so với thư mục plugins.
 */
function wp_get_active_and_valid_plugins() {
	$plugins        = array();
	$active_plugins = (array) get_option( 'active_plugins', array() );

	// Kiểm tra tệp hack nếu tùy chọn được bật.
	if ( get_option( 'hack_file' ) && file_exists( ABSPATH . 'my-hacks.php' ) ) {
		_deprecated_file( 'my-hacks.php', '1.5.0' );
		array_unshift( $plugins, ABSPATH . 'my-hacks.php' );
	}

	if ( empty( $active_plugins ) || wp_installing() ) {
		return $plugins;
	}

	$network_plugins = is_multisite() ? wp_get_active_network_plugins() : false;

	foreach ( $active_plugins as $plugin ) {
		if ( ! validate_file( $plugin )                     // $plugin phải hợp lệ là tệp.
			&& str_ends_with( $plugin, '.php' )             // $plugin phải kết thúc bằng '.php'.
			&& file_exists( WP_PLUGIN_DIR . '/' . $plugin ) // $plugin phải tồn tại.
			// Chưa được bao gồm như plugin mạng.
			&& ( ! $network_plugins || ! in_array( WP_PLUGIN_DIR . '/' . $plugin, $network_plugins, true ) )
		) {
			$plugins[] = WP_PLUGIN_DIR . '/' . $plugin;
		}
	}

	/*
	 * Loại bỏ plugin khỏi danh sách plugin đang hoạt động khi chúng ta đang ở endpoint
	 * nên được bảo vệ chống WSOD và plugin đang bị tạm dừng.
	 */
	if ( wp_is_recovery_mode() ) {
		$plugins = wp_skip_paused_plugins( $plugins );
	}

	return $plugins;
}

/**
 * Lọc danh sách plugin được cung cấp, loại bỏ các plugin bị tạm dừng.
 *
 * @since 5.2.0
 *
 * @global WP_Paused_Extensions_Storage $_paused_plugins
 *
 * @param string[] $plugins Mảng các đường dẫn tuyệt đối tệp chính của plugin.
 * @return string[] Mảng plugin đã được lọc, không bao gồm các plugin bị tạm dừng.
 */
function wp_skip_paused_plugins( array $plugins ) {
	$paused_plugins = wp_paused_plugins()->get_all();

	if ( empty( $paused_plugins ) ) {
		return $plugins;
	}

	foreach ( $plugins as $index => $plugin ) {
		list( $plugin ) = explode( '/', plugin_basename( $plugin ) );

		if ( array_key_exists( $plugin, $paused_plugins ) ) {
			unset( $plugins[ $index ] );

			// Lưu danh sách plugin bị tạm dừng để hiển thị thông báo quản trị.
			$GLOBALS['_paused_plugins'][ $plugin ] = $paused_plugins[ $plugin ];
		}
	}

	return $plugins;
}

/**
 * Lấy mảng các theme đang hoạt động và hợp lệ.
 *
 * Trong khi nâng cấp hoặc cài đặt WordPress, không có theme nào được trả về.
 *
 * @since 5.1.0
 * @access private
 *
 * @global string $pagenow            Tên tệp của màn hình hiện tại.
 * @global string $wp_stylesheet_path Đường dẫn đến thư mục stylesheet của theme hiện tại.
 * @global string $wp_template_path   Đường dẫn đến thư mục template của theme hiện tại.
 *
 * @return string[] Mảng các đường dẫn tuyệt đối đến thư mục theme.
 */
function wp_get_active_and_valid_themes() {
	global $pagenow, $wp_stylesheet_path, $wp_template_path;

	$themes = array();

	if ( wp_installing() && 'wp-activate.php' !== $pagenow ) {
		return $themes;
	}

	if ( is_child_theme() ) {
		$themes[] = $wp_stylesheet_path;
	}

	$themes[] = $wp_template_path;

	/*
	 * Loại bỏ theme khỏi danh sách theme đang hoạt động khi chúng ta đang ở endpoint
	 * nên được bảo vệ chống WSOD và theme đang bị tạm dừng.
	 */
	if ( wp_is_recovery_mode() ) {
		$themes = wp_skip_paused_themes( $themes );

		// Nếu không có theme hoạt động và hợp lệ nào, bỏ qua tải theme.
		if ( empty( $themes ) ) {
			add_filter( 'wp_using_themes', '__return_false' );
		}
	}

	return $themes;
}

/**
 * Lọc danh sách theme được cung cấp, loại bỏ các theme bị tạm dừng.
 *
 * @since 5.2.0
 *
 * @global WP_Paused_Extensions_Storage $_paused_themes
 *
 * @param string[] $themes Mảng các đường dẫn tuyệt đối thư mục theme.
 * @return string[] Mảng đường dẫn tuyệt đối đến theme đã được lọc, không bao gồm các theme bị tạm dừng.
 */
function wp_skip_paused_themes( array $themes ) {
	$paused_themes = wp_paused_themes()->get_all();

	if ( empty( $paused_themes ) ) {
		return $themes;
	}

	foreach ( $themes as $index => $theme ) {
		$theme = basename( $theme );

		if ( array_key_exists( $theme, $paused_themes ) ) {
			unset( $themes[ $index ] );

			// Lưu danh sách theme bị tạm dừng để hiển thị thông báo quản trị.
			$GLOBALS['_paused_themes'][ $theme ] = $paused_themes[ $theme ];
		}
	}

	return $themes;
}

/**
 * Xác định xem WordPress có đang ở Chế độ Phục hồi hay không.
 *
 * Trong chế độ này, các plugin hoặc theme gây ra WSOD sẽ bị tạm dừng.
 *
 * @since 5.2.0
 *
 * @return bool
 */
function wp_is_recovery_mode() {
	return wp_recovery_mode()->is_active();
}

/**
 * Xác định xem chúng ta có đang ở endpoint nên được bảo vệ chống WSOD hay không.
 *
 * @since 5.2.0
 *
 * @global string $pagenow Tên tệp của màn hình hiện tại.
 *
 * @return bool True nếu endpoint hiện tại nên được bảo vệ.
 */
function is_protected_endpoint() {
	// Bảo vệ các trang đăng nhập.
	if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
		return true;
	}

	// Bảo vệ backend quản trị.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return true;
	}

	// Bảo vệ các action Ajax có thể giúp giải quyết lỗi nghiêm trọng nên có sẵn.
	if ( is_protected_ajax_action() ) {
		return true;
	}

	/**
	 * Lọc xem yêu cầu hiện tại có đang đến endpoint được bảo vệ hay không.
	 *
	 * Bộ lọc này chỉ được kích hoạt khi một endpoint được yêu cầu mà chưa được bảo vệ bởi
	 * WordPress core. Do đó, nó chỉ cho phép cung cấp thêm các endpoint được bảo vệ
	 * ngoài backend quản trị, trang đăng nhập và các action Ajax được bảo vệ.
	 *
	 * @since 5.2.0
	 *
	 * @param bool $is_protected_endpoint Endpoint hiện tại có được bảo vệ hay không.
	 *                                    Mặc định false.
	 */
	return (bool) apply_filters( 'is_protected_endpoint', false );
}

/**
 * Xác định xem chúng ta có đang xử lý action Ajax nên được bảo vệ chống WSOD hay không.
 *
 * @since 5.2.0
 *
 * @return bool True nếu action Ajax hiện tại nên được bảo vệ.
 */
function is_protected_ajax_action() {
	if ( ! wp_doing_ajax() ) {
		return false;
	}

	if ( ! isset( $_REQUEST['action'] ) ) {
		return false;
	}

	$actions_to_protect = array(
		'edit-theme-plugin-file', // Lưu thay đổi trong trình chỉnh sửa mã core.
		'heartbeat',              // Giữ nhịp tim hoạt động.
		'install-plugin',         // Cài đặt plugin mới.
		'install-theme',          // Cài đặt theme mới.
		'search-plugins',         // Tìm kiếm trong danh sách plugin.
		'search-install-plugins', // Tìm kiếm plugin trong màn hình cài đặt plugin.
		'update-plugin',          // Cập nhật plugin hiện có.
		'update-theme',           // Cập nhật theme hiện có.
		'activate-plugin',        // Kích hoạt plugin hiện có.
	);

	/**
	 * Lọc mảng các action Ajax được bảo vệ.
	 *
	 * Bộ lọc này chỉ được kích hoạt khi đang thực hiện Ajax và yêu cầu Ajax có thuộc tính 'action'.
	 *
	 * @since 5.2.0
	 *
	 * @param string[] $actions_to_protect Mảng chuỗi các action Ajax cần bảo vệ.
	 */
	$actions_to_protect = (array) apply_filters( 'wp_protected_ajax_actions', $actions_to_protect );

	if ( ! in_array( $_REQUEST['action'], $actions_to_protect, true ) ) {
		return false;
	}

	return true;
}

/**
 * Đặt mã hóa nội bộ.
 *
 * Trong hầu hết các trường hợp, mã hóa nội bộ mặc định là latin1, điều này
 * không hữu ích, vì chúng ta muốn sử dụng các hàm `mb_` cho chuỗi `utf-8`.
 *
 * @since 3.0.0
 * @access private
 */
function wp_set_internal_encoding() {
	if ( function_exists( 'mb_internal_encoding' ) ) {
		$charset = get_option( 'blog_charset' );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $charset || ! @mb_internal_encoding( $charset ) ) {
			mb_internal_encoding( 'UTF-8' );
		}
	}
}

/**
 * Thêm magic quotes vào `$_GET`, `$_POST`, `$_COOKIE`, và `$_SERVER`.
 *
 * Cũng buộc `$_REQUEST` là `$_GET + $_POST`. Nếu cần `$_SERVER`,
 * `$_COOKIE`, hoặc `$_ENV`, sử dụng trực tiếp các superglobal đó.
 *
 * @since 3.0.0
 * @access private
 */
function wp_magic_quotes() {
	// Escape với wpdb.
	$_GET    = add_magic_quotes( $_GET );
	$_POST   = add_magic_quotes( $_POST );
	$_COOKIE = add_magic_quotes( $_COOKIE );
	$_SERVER = add_magic_quotes( $_SERVER );

	// Buộc REQUEST là GET + POST.
	$_REQUEST = array_merge( $_GET, $_POST );
}

/**
 * Chạy ngay trước khi PHP dừng thực thi.
 *
 * @since 1.2.0
 * @access private
 */
function shutdown_action_hook() {
	/**
	 * Kích hoạt ngay trước khi PHP dừng thực thi.
	 *
	 * @since 1.2.0
	 */
	do_action( 'shutdown' );

	wp_cache_close();
}

/**
 * Sao chép một đối tượng.
 *
 * @since 2.7.0
 * @deprecated 3.2.0
 *
 * @param object $input_object Đối tượng cần sao chép.
 * @return object Đối tượng đã được sao chép.
 */
function wp_clone( $input_object ) {
	// Sử dụng ngoặc tròn cho clone để tương thích PHP 4. Xem #17880.
	return clone( $input_object );
}

/**
 * Xác định xem yêu cầu hiện tại có phải cho màn hình đăng nhập hay không.
 *
 * @since 6.1.0
 *
 * @see wp_login_url()
 *
 * @return bool True nếu đang trong màn hình đăng nhập WordPress, false nếu không.
 */
function is_login() {
	return false !== stripos( wp_login_url(), $_SERVER['SCRIPT_NAME'] );
}

/**
 * Xác định xem yêu cầu hiện tại có phải cho trang giao diện quản trị hay không.
 *
 * Không kiểm tra xem người dùng có phải là quản trị viên hay không; sử dụng current_user_can()
 * để kiểm tra vai trò và khả năng.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Thẻ Điều kiện} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 1.5.1
 *
 * @global WP_Screen $current_screen Đối tượng màn hình hiện tại của WordPress.
 *
 * @return bool True nếu đang trong giao diện quản trị WordPress, false nếu không.
 */
function is_admin() {
	if ( isset( $GLOBALS['current_screen'] ) ) {
		return $GLOBALS['current_screen']->in_admin();
	} elseif ( defined( 'WP_ADMIN' ) ) {
		return WP_ADMIN;
	}

	return false;
}

/**
 * Xác định xem yêu cầu hiện tại có phải cho giao diện quản trị của trang web hay không.
 *
 * Ví dụ: `/wp-admin/`
 *
 * Không kiểm tra xem người dùng có phải là quản trị viên hay không; sử dụng current_user_can()
 * để kiểm tra vai trò và khả năng.
 *
 * @since 3.1.0
 *
 * @global WP_Screen $current_screen Đối tượng màn hình hiện tại của WordPress.
 *
 * @return bool True nếu đang trong các trang quản trị trang web WordPress.
 */
function is_blog_admin() {
	if ( isset( $GLOBALS['current_screen'] ) ) {
		return $GLOBALS['current_screen']->in_admin( 'site' );
	} elseif ( defined( 'WP_BLOG_ADMIN' ) ) {
		return WP_BLOG_ADMIN;
	}

	return false;
}

/**
 * Xác định xem yêu cầu hiện tại có phải cho giao diện quản trị mạng hay không.
 *
 * Ví dụ: `/wp-admin/network/`
 *
 * Không kiểm tra xem người dùng có phải là quản trị viên hay không; sử dụng current_user_can()
 * để kiểm tra vai trò và khả năng.
 *
 * Không kiểm tra xem trang web có phải là mạng Multisite hay không; sử dụng is_multisite()
 * để kiểm tra xem Multisite có được bật hay không.
 *
 * @since 3.1.0
 *
 * @global WP_Screen $current_screen Đối tượng màn hình hiện tại của WordPress.
 *
 * @return bool True nếu đang trong các trang quản trị mạng WordPress.
 */
function is_network_admin() {
	if ( isset( $GLOBALS['current_screen'] ) ) {
		return $GLOBALS['current_screen']->in_admin( 'network' );
	} elseif ( defined( 'WP_NETWORK_ADMIN' ) ) {
		return WP_NETWORK_ADMIN;
	}

	return false;
}

/**
 * Xác định xem yêu cầu hiện tại có phải cho màn hình quản trị người dùng hay không.
 *
 * Ví dụ: `/wp-admin/user/`
 *
 * Không kiểm tra xem người dùng có phải là quản trị viên hay không; sử dụng current_user_can()
 * để kiểm tra vai trò và khả năng.
 *
 * @since 3.1.0
 *
 * @global WP_Screen $current_screen Đối tượng màn hình hiện tại của WordPress.
 *
 * @return bool True nếu đang trong các trang quản trị người dùng WordPress.
 */
function is_user_admin() {
	if ( isset( $GLOBALS['current_screen'] ) ) {
		return $GLOBALS['current_screen']->in_admin( 'user' );
	} elseif ( defined( 'WP_USER_ADMIN' ) ) {
		return WP_USER_ADMIN;
	}

	return false;
}

/**
 * Xác định xem Multisite có được bật hay không.
 *
 * @since 3.0.0
 *
 * @return bool True nếu Multisite được bật, false nếu không.
 */
function is_multisite() {
	if ( defined( 'MULTISITE' ) ) {
		return MULTISITE;
	}

	if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) ) {
		return true;
	}

	return false;
}

/**
 * Chuyển đổi giá trị thành số nguyên không âm.
 *
 * @since 2.5.0
 *
 * @param mixed $maybeint Dữ liệu bạn muốn chuyển đổi thành số nguyên không âm.
 * @return int Số nguyên không âm.
 */
function absint( $maybeint ) {
	return abs( (int) $maybeint );
}

/**
 * Lấy ID trang web hiện tại.
 *
 * @since 3.1.0
 *
 * @global int $blog_id
 *
 * @return int ID trang web.
 */
function get_current_blog_id() {
	global $blog_id;

	return absint( $blog_id );
}

/**
 * Lấy ID mạng hiện tại.
 *
 * @since 4.6.0
 *
 * @return int ID của mạng hiện tại.
 */
function get_current_network_id() {
	if ( ! is_multisite() ) {
		return 1;
	}

	$current_network = get_network();

	if ( ! isset( $current_network->id ) ) {
		return get_main_network_id();
	}

	return absint( $current_network->id );
}

/**
 * Cố gắng tải sớm các bản dịch.
 *
 * Được sử dụng cho các lỗi gặp phải trong quá trình tải ban đầu, trước khi
 * ngôn ngữ được phát hiện và tải đúng cách.
 *
 * Được thiết kế cho các chuỗi tải bất thường (như setup-config.php) hoặc khi
 * script sau đó sẽ dừng với lỗi, nếu không có nguy cơ
 * tệp có thể bị include hai lần.
 *
 * @since 3.4.0
 * @access private
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry Đăng ký Textdomain WordPress.
 * @global WP_Locale              $wp_locale              Đối tượng ngôn ngữ ngày và giờ WordPress.
 */
function wp_load_translations_early() {
	global $wp_textdomain_registry, $wp_locale;
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded = true;

	if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
		return;
	}

	// Chúng ta cần $wp_local_package.
	require ABSPATH . WPINC . '/version.php';

	// Dịch thuật và bản địa hóa.
	require_once ABSPATH . WPINC . '/pomo/mo.php';
	require_once ABSPATH . WPINC . '/l10n/class-wp-translation-controller.php';
	require_once ABSPATH . WPINC . '/l10n/class-wp-translations.php';
	require_once ABSPATH . WPINC . '/l10n/class-wp-translation-file.php';
	require_once ABSPATH . WPINC . '/l10n/class-wp-translation-file-mo.php';
	require_once ABSPATH . WPINC . '/l10n/class-wp-translation-file-php.php';
	require_once ABSPATH . WPINC . '/l10n.php';
	require_once ABSPATH . WPINC . '/class-wp-textdomain-registry.php';
	require_once ABSPATH . WPINC . '/class-wp-locale.php';
	require_once ABSPATH . WPINC . '/class-wp-locale-switcher.php';

	// Thư viện chung.
	require_once ABSPATH . WPINC . '/plugin.php';

	$locales   = array();
	$locations = array();

	if ( ! $wp_textdomain_registry instanceof WP_Textdomain_Registry ) {
		$wp_textdomain_registry = new WP_Textdomain_Registry();
	}

	while ( true ) {
		if ( defined( 'WPLANG' ) ) {
			if ( '' === WPLANG ) {
				break;
			}
			$locales[] = WPLANG;
		}

		if ( isset( $wp_local_package ) ) {
			$locales[] = $wp_local_package;
		}

		if ( ! $locales ) {
			break;
		}

		if ( defined( 'WP_LANG_DIR' ) && @is_dir( WP_LANG_DIR ) ) {
			$locations[] = WP_LANG_DIR;
		}

		if ( defined( 'WP_CONTENT_DIR' ) && @is_dir( WP_CONTENT_DIR . '/languages' ) ) {
			$locations[] = WP_CONTENT_DIR . '/languages';
		}

		if ( @is_dir( ABSPATH . 'wp-content/languages' ) ) {
			$locations[] = ABSPATH . 'wp-content/languages';
		}

		if ( @is_dir( ABSPATH . WPINC . '/languages' ) ) {
			$locations[] = ABSPATH . WPINC . '/languages';
		}

		if ( ! $locations ) {
			break;
		}

		$locations = array_unique( $locations );

		foreach ( $locales as $locale ) {
			foreach ( $locations as $location ) {
				if ( file_exists( $location . '/' . $locale . '.mo' ) ) {
					load_textdomain( 'default', $location . '/' . $locale . '.mo', $locale );

					if ( defined( 'WP_SETUP_CONFIG' ) && file_exists( $location . '/admin-' . $locale . '.mo' ) ) {
						load_textdomain( 'default', $location . '/admin-' . $locale . '.mo', $locale );
					}

					break 2;
				}
			}
		}

		break;
	}

	$wp_locale = new WP_Locale();
}

/**
 * Kiểm tra hoặc đặt WordPress có đang ở chế độ "cài đặt" hay không.
 *
 * Nếu hằng số `WP_INSTALLING` được định nghĩa trong quá trình bootstrap, `wp_installing()` sẽ mặc định là `true`.
 *
 * @since 4.4.0
 *
 * @param bool $is_installing Tùy chọn. True để đặt WP vào chế độ Cài đặt, false để tắt chế độ Cài đặt.
 *                            Bỏ qua tham số này nếu bạn chỉ muốn lấy trạng thái hiện tại.
 * @return bool True nếu WP đang cài đặt, ngược lại false. Khi `$is_installing` được truyền, hàm sẽ
 *              trả về WP có đang ở chế độ cài đặt trước khi thay đổi thành `$is_installing` hay không.
 */
function wp_installing( $is_installing = null ) {
	static $installing = null;

	// Hỗ trợ cho hằng số `WP_INSTALLING`, được định nghĩa trước khi WP tải.
	if ( is_null( $installing ) ) {
		$installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
	}

	if ( ! is_null( $is_installing ) ) {
		$old_installing = $installing;
		$installing     = $is_installing;

		return (bool) $old_installing;
	}

	return (bool) $installing;
}

/**
 * Xác định xem SSL có được sử dụng hay không.
 *
 * @since 2.6.0
 * @since 4.6.0 Chuyển từ functions.php sang load.php.
 *
 * @return bool True nếu SSL, ngược lại false.
 */
function is_ssl() {
	if ( isset( $_SERVER['HTTPS'] ) ) {
		if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
			return true;
		}

		if ( '1' === (string) $_SERVER['HTTPS'] ) {
			return true;
		}
	} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' === (string) $_SERVER['SERVER_PORT'] ) ) {
		return true;
	}

	return false;
}

/**
 * Chuyển đổi giá trị byte viết tắt thành giá trị byte số nguyên.
 *
 * @since 2.3.0
 * @since 4.6.0 Chuyển từ media.php sang load.php.
 *
 * @link https://www.php.net/manual/en/function.ini-get.php
 * @link https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
 *
 * @param string $value Giá trị byte (PHP ini), dạng viết tắt hoặc thông thường.
 * @return int Giá trị byte số nguyên.
 */
function wp_convert_hr_to_bytes( $value ) {
	$value = strtolower( trim( $value ) );
	$bytes = (int) $value;

	if ( str_contains( $value, 'g' ) ) {
		$bytes *= GB_IN_BYTES;
	} elseif ( str_contains( $value, 'm' ) ) {
		$bytes *= MB_IN_BYTES;
	} elseif ( str_contains( $value, 'k' ) ) {
		$bytes *= KB_IN_BYTES;
	}

	// Xử lý các giá trị lớn (float) vượt quá kích thước số nguyên tối đa.
	return min( $bytes, PHP_INT_MAX );
}

/**
 * Xác định xem giá trị PHP ini có thể thay đổi tại runtime hay không.
 *
 * @since 4.6.0
 *
 * @link https://www.php.net/manual/en/function.ini-get-all.php
 *
 * @param string $setting Tên cài đặt ini cần kiểm tra.
 * @return bool True nếu giá trị có thể thay đổi tại runtime. False nếu không.
 */
function wp_is_ini_value_changeable( $setting ) {
	static $ini_all;

	if ( ! isset( $ini_all ) ) {
		$ini_all = false;
		// Đôi khi `ini_get_all()` bị tắt qua tùy chọn `disable_functions` vì lý do "bảo mật".
		if ( function_exists( 'ini_get_all' ) ) {
			$ini_all = ini_get_all();
		}
	}

	if ( isset( $ini_all[ $setting ]['access'] )
		&& ( INI_ALL === $ini_all[ $setting ]['access'] || INI_USER === $ini_all[ $setting ]['access'] )
	) {
		return true;
	}

	// Nếu không thể lấy thông tin chi tiết, giả định một cách an toàn rằng có thể thay đổi.
	if ( ! is_array( $ini_all ) ) {
		return true;
	}

	return false;
}

/**
 * Xác định xem yêu cầu hiện tại có phải là yêu cầu Ajax WordPress hay không.
 *
 * @since 4.7.0
 *
 * @return bool True nếu là yêu cầu Ajax WordPress, false nếu không.
 */
function wp_doing_ajax() {
	/**
	 * Lọc xem yêu cầu hiện tại có phải là yêu cầu Ajax WordPress hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param bool $wp_doing_ajax Yêu cầu hiện tại có phải là yêu cầu Ajax WordPress hay không.
	 */
	return apply_filters( 'wp_doing_ajax', defined( 'DOING_AJAX' ) && DOING_AJAX );
}

/**
 * Xác định xem yêu cầu hiện tại có nên sử dụng theme hay không.
 *
 * @since 5.1.0
 *
 * @return bool True nếu nên sử dụng theme, false nếu không.
 */
function wp_using_themes() {
	/**
	 * Lọc xem yêu cầu hiện tại có nên sử dụng theme hay không.
	 *
	 * @since 5.1.0
	 *
	 * @param bool $wp_using_themes Yêu cầu hiện tại có nên sử dụng theme hay không.
	 */
	return apply_filters( 'wp_using_themes', defined( 'WP_USE_THEMES' ) && WP_USE_THEMES );
}

/**
 * Xác định xem yêu cầu hiện tại có phải là yêu cầu cron WordPress hay không.
 *
 * @since 4.8.0
 *
 * @return bool True nếu là yêu cầu cron WordPress, false nếu không.
 */
function wp_doing_cron() {
	/**
	 * Lọc xem yêu cầu hiện tại có phải là yêu cầu cron WordPress hay không.
	 *
	 * @since 4.8.0
	 *
	 * @param bool $wp_doing_cron Yêu cầu hiện tại có phải là yêu cầu cron WordPress hay không.
	 */
	return apply_filters( 'wp_doing_cron', defined( 'DOING_CRON' ) && DOING_CRON );
}

/**
 * Kiểm tra xem biến cho trước có phải là WordPress Error hay không.
 *
 * Trả về xem `$thing` có phải là thực thể của lớp `WP_Error` hay không.
 *
 * @since 2.1.0
 *
 * @param mixed $thing Biến cần kiểm tra.
 * @return bool Biến có phải là thực thể của WP_Error hay không.
 */
function is_wp_error( $thing ) {
	$is_wp_error = ( $thing instanceof WP_Error );

	if ( $is_wp_error ) {
		/**
		 * Kích hoạt khi `is_wp_error()` được gọi và tham số của nó là thực thể của `WP_Error`.
		 *
		 * @since 5.6.0
		 *
		 * @param WP_Error $thing Đối tượng lỗi được truyền vào `is_wp_error()`.
		 */
		do_action( 'is_wp_error_instance', $thing );
	}

	return $is_wp_error;
}

/**
 * Xác định xem có cho phép sửa đổi tệp hay không.
 *
 * @since 4.8.0
 *
 * @param string $context Ngữ cảnh sử dụng.
 * @return bool True nếu cho phép sửa đổi tệp, false nếu không.
 */
function wp_is_file_mod_allowed( $context ) {
	/**
	 * Lọc xem có cho phép sửa đổi tệp hay không.
	 *
	 * @since 4.8.0
	 *
	 * @param bool   $file_mod_allowed Có cho phép sửa đổi tệp hay không.
	 * @param string $context          Ngữ cảnh sử dụng.
	 */
	return apply_filters( 'file_mod_allowed', ! defined( 'DISALLOW_FILE_MODS' ) || ! DISALLOW_FILE_MODS, $context );
}

/**
 * Bắt đầu quét lỗi tệp đã chỉnh sửa.
 *
 * @since 4.9.0
 */
function wp_start_scraping_edited_file_errors() {
	if ( ! isset( $_REQUEST['wp_scrape_key'] ) || ! isset( $_REQUEST['wp_scrape_nonce'] ) ) {
		return;
	}

	$key   = substr( sanitize_key( wp_unslash( $_REQUEST['wp_scrape_key'] ) ), 0, 32 );
	$nonce = wp_unslash( $_REQUEST['wp_scrape_nonce'] );
	if ( empty( $key ) || empty( $nonce ) ) {
		return;
	}

	$transient = get_transient( 'scrape_key_' . $key );
	if ( false === $transient ) {
		return;
	}

	if ( $transient !== $nonce ) {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex' );
			nocache_headers();
		}
		echo "###### wp_scraping_result_start:$key ######";
		echo wp_json_encode(
			array(
				'code'    => 'scrape_nonce_failure',
				'message' => __( 'Scrape key check failed. Please try again.' ),
			)
		);
		echo "###### wp_scraping_result_end:$key ######";
		die();
	}

	if ( ! defined( 'WP_SANDBOX_SCRAPING' ) ) {
		define( 'WP_SANDBOX_SCRAPING', true );
	}

	register_shutdown_function( 'wp_finalize_scraping_edited_file_errors', $key );
}

/**
 * Hoàn tất quét lỗi tệp đã chỉnh sửa.
 *
 * @since 4.9.0
 *
 * @param string $scrape_key Khóa quét.
 */
function wp_finalize_scraping_edited_file_errors( $scrape_key ) {
	$error = error_get_last();

	echo "\n###### wp_scraping_result_start:$scrape_key ######\n";

	if ( ! empty( $error )
		&& in_array( $error['type'], array( E_CORE_ERROR, E_COMPILE_ERROR, E_ERROR, E_PARSE, E_USER_ERROR, E_RECOVERABLE_ERROR ), true )
	) {
		$error = str_replace( ABSPATH, '', $error );
		echo wp_json_encode( $error );
	} else {
		echo wp_json_encode( true );
	}

	echo "\n###### wp_scraping_result_end:$scrape_key ######\n";
}

/**
 * Kiểm tra xem yêu cầu hiện tại có phải là yêu cầu JSON, hoặc mong đợi phản hồi JSON hay không.
 *
 * @since 5.0.0
 *
 * @return bool True nếu header `Accepts` hoặc `Content-Type` chứa `application/json`.
 *              False nếu không.
 */
function wp_is_json_request() {
	if ( isset( $_SERVER['HTTP_ACCEPT'] ) && wp_is_json_media_type( $_SERVER['HTTP_ACCEPT'] ) ) {
		return true;
	}

	if ( isset( $_SERVER['CONTENT_TYPE'] ) && wp_is_json_media_type( $_SERVER['CONTENT_TYPE'] ) ) {
		return true;
	}

	return false;
}

/**
 * Kiểm tra xem yêu cầu hiện tại có phải là yêu cầu JSONP, hoặc mong đợi phản hồi JSONP hay không.
 *
 * @since 5.2.0
 *
 * @return bool True nếu là yêu cầu JSONP, false nếu không.
 */
function wp_is_jsonp_request() {
	if ( ! isset( $_GET['_jsonp'] ) ) {
		return false;
	}

	if ( ! function_exists( 'wp_check_jsonp_callback' ) ) {
		require_once ABSPATH . WPINC . '/functions.php';
	}

	$jsonp_callback = $_GET['_jsonp'];
	if ( ! wp_check_jsonp_callback( $jsonp_callback ) ) {
		return false;
	}

	/** Bộ lọc này được ghi tài liệu trong wp-includes/rest-api/class-wp-rest-server.php */
	$jsonp_enabled = apply_filters( 'rest_jsonp_enabled', true );

	return $jsonp_enabled;
}

/**
 * Kiểm tra xem chuỗi có phải là JSON Media Type hợp lệ hay không.
 *
 * @since 5.6.0
 *
 * @param string $media_type Chuỗi Media Type cần kiểm tra.
 * @return bool True nếu chuỗi là JSON Media Type hợp lệ.
 */
function wp_is_json_media_type( $media_type ) {
	static $cache = array();

	if ( ! isset( $cache[ $media_type ] ) ) {
		$cache[ $media_type ] = (bool) preg_match( '/(^|\s|,)application\/([\w!#\$&-\^\.\+]+\+)?json(\+oembed)?($|\s|;|,)/i', $media_type );
	}

	return $cache[ $media_type ];
}

/**
 * Kiểm tra xem yêu cầu hiện tại có phải là yêu cầu XML, hoặc mong đợi phản hồi XML hay không.
 *
 * @since 5.2.0
 *
 * @return bool True nếu header `Accepts` hoặc `Content-Type` chứa `text/xml`
 *              hoặc một trong các loại MIME liên quan. False nếu không.
 */
function wp_is_xml_request() {
	$accepted = array(
		'text/xml',
		'application/rss+xml',
		'application/atom+xml',
		'application/rdf+xml',
		'text/xml+oembed',
		'application/xml+oembed',
	);

	if ( isset( $_SERVER['HTTP_ACCEPT'] ) ) {
		foreach ( $accepted as $type ) {
			if ( str_contains( $_SERVER['HTTP_ACCEPT'], $type ) ) {
				return true;
			}
		}
	}

	if ( isset( $_SERVER['CONTENT_TYPE'] ) && in_array( $_SERVER['CONTENT_TYPE'], $accepted, true ) ) {
		return true;
	}

	return false;
}

/**
 * Kiểm tra xem trang web này có được bảo vệ bởi HTTP Basic Auth hay không.
 *
 * Hiện tại, điều này chỉ kiểm tra sự hiện diện của thông tin đăng nhập Basic Auth. Do đó, gọi
 * hàm này với ngữ cảnh khác với ngữ cảnh hiện tại có thể cho kết quả không chính xác.
 * Trong phiên bản tương lai, đánh giá này có thể được thực hiện mạnh mẽ hơn.
 *
 * Hiện tại, điều này chỉ được sử dụng bởi Application Passwords để ngăn xung đột vì nó cũng sử dụng
 * Basic Auth.
 *
 * @since 5.6.1
 *
 * @global string $pagenow Tên tệp của màn hình hiện tại.
 *
 * @param string $context Ngữ cảnh cần kiểm tra bảo vệ. Chấp nhận 'login', 'admin', và 'front'.
 *                        Mặc định là ngữ cảnh hiện tại.
 * @return bool Trang web có được bảo vệ bởi Basic Auth hay không.
 */
function wp_is_site_protected_by_basic_auth( $context = '' ) {
	global $pagenow;

	if ( ! $context ) {
		if ( 'wp-login.php' === $pagenow ) {
			$context = 'login';
		} elseif ( is_admin() ) {
			$context = 'admin';
		} else {
			$context = 'front';
		}
	}

	$is_protected = ! empty( $_SERVER['PHP_AUTH_USER'] ) || ! empty( $_SERVER['PHP_AUTH_PW'] );

	/**
	 * Lọc xem trang web có được bảo vệ bởi HTTP Basic Auth hay không.
	 *
	 * @since 5.6.1
	 *
	 * @param bool $is_protected Trang web có được bảo vệ bởi Basic Auth hay không.
	 * @param string $context    Ngữ cảnh kiểm tra bảo vệ. Một trong 'login', 'admin', hoặc 'front'.
	 */
	return apply_filters( 'wp_is_site_protected_by_basic_auth', $is_protected, $context );
}
