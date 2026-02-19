<?php
/**
 * API Bảo vệ Lỗi: Lớp WP_Fatal_Error_Handler
 *
 * @package WordPress
 * @since 5.2.0
 */

/**
 * Lớp lõi được sử dụng làm trình xử lý tắt máy mặc định cho các lỗi nghiêm trọng.
 *
 * Một drop-in 'fatal-error-handler.php' có thể được sử dụng để ghi đè instance của lớp này và sử dụng một
 * triển khai tùy chỉnh cho trình xử lý lỗi nghiêm trọng mà WordPress đăng ký. Lớp tùy chỉnh nên kế thừa lớp này và
 * có thể ghi đè các phương thức riêng lẻ khi cần thiết. File phải trả về instance của lớp cần được
 * đăng ký.
 *
 * @since 5.2.0
 */
#[AllowDynamicProperties]
class WP_Fatal_Error_Handler {

	/**
	 * Chạy trình xử lý tắt máy.
	 *
	 * Phương thức này được đăng ký thông qua `register_shutdown_function()`.
	 *
	 * @since 5.2.0
	 *
	 * @global WP_Locale $wp_locale Đối tượng locale ngày giờ của WordPress.
	 */
	public function handle() {
		if ( defined( 'WP_SANDBOX_SCRAPING' ) && WP_SANDBOX_SCRAPING ) {
			return;
		}

		// Không kích hoạt trình xử lý lỗi nghiêm trọng khi đang cài đặt cập nhật.
		if ( wp_is_maintenance_mode() ) {
			return;
		}

		try {
			// Thoát nếu không tìm thấy lỗi.
			$error = $this->detect_error();
			if ( ! $error ) {
				return;
			}

			if ( ! isset( $GLOBALS['wp_locale'] ) && function_exists( 'load_default_textdomain' ) ) {
				load_default_textdomain();
			}

			$handled = false;

			if ( ! is_multisite() && wp_recovery_mode()->is_initialized() ) {
				$handled = wp_recovery_mode()->handle_error( $error );
			}

			// Hiển thị template lỗi PHP nếu header chưa được gửi.
			if ( is_admin() || ! headers_sent() ) {
				$this->display_error_template( $error, $handled );
			}
		} catch ( Exception $e ) {
			// Bắt ngoại lệ và giữ im lặng.
		}
	}

	/**
	 * Phát hiện lỗi gây ra sự cố nếu cần được xử lý.
	 *
	 * @since 5.2.0
	 *
	 * @return array|null Thông tin lỗi được trả về bởi `error_get_last()`, hoặc null
	 *                    nếu không có lỗi nào được ghi nhận hoặc lỗi không cần được xử lý.
	 */
	protected function detect_error() {
		$error = error_get_last();

		// Không có lỗi, bỏ qua mã xử lý lỗi.
		if ( null === $error ) {
			return null;
		}

		// Thoát nếu lỗi này không cần được xử lý.
		if ( ! $this->should_handle_error( $error ) ) {
			return null;
		}

		return $error;
	}

	/**
	 * Xác định xem có phải đang xử lý một lỗi mà WordPress nên xử lý hay không
	 * để bảo vệ backend quản trị khỏi WSOD (Màn hình trắng chết chóc).
	 *
	 * @since 5.2.0
	 *
	 * @param array $error Thông tin lỗi được lấy từ `error_get_last()`.
	 * @return bool Liệu WordPress có nên xử lý lỗi này hay không.
	 */
	protected function should_handle_error( $error ) {
		$error_types_to_handle = array(
			E_ERROR,
			E_PARSE,
			E_USER_ERROR,
			E_COMPILE_ERROR,
			E_RECOVERABLE_ERROR,
		);

		if ( isset( $error['type'] ) && in_array( $error['type'], $error_types_to_handle, true ) ) {
			return true;
		}

		/**
		 * Lọc xem một lỗi được ném ra có nên được xử lý bởi trình xử lý lỗi nghiêm trọng hay không.
		 *
		 * Bộ lọc này chỉ được kích hoạt nếu lỗi chưa được cấu hình để WordPress lõi xử lý. Do đó,
		 * nó chỉ cho phép thêm các quy tắc mới cho những lỗi nào nên được xử lý, chứ không loại bỏ
		 * các quy tắc hiện có.
		 *
		 * @since 5.2.0
		 *
		 * @param bool  $should_handle_error Liệu lỗi có nên được xử lý bởi trình xử lý lỗi nghiêm trọng hay không.
		 * @param array $error               Thông tin lỗi được lấy từ `error_get_last()`.
		 */
		return (bool) apply_filters( 'wp_should_handle_php_error', false, $error );
	}

	/**
	 * Hiển thị template lỗi PHP và gửi mã trạng thái HTTP, thường là 500.
	 *
	 * Một drop-in 'php-error.php' có thể được sử dụng làm template tùy chỉnh. Drop-in này nên kiểm soát mã trạng thái HTTP và
	 * in markup HTML chỉ ra rằng đã xảy ra lỗi PHP. Lưu ý rằng drop-in này có thể được thực thi
	 * rất sớm trong quá trình khởi động WordPress, vì vậy bất kỳ hàm lõi nào được sử dụng mà không thuộc
	 * `wp-includes/load.php` nên được kiểm tra trước khi gọi.
	 *
	 * Nếu không có drop-in nào khả dụng, phương thức này sẽ gọi {@see WP_Fatal_Error_Handler::display_default_error_template()}.
	 *
	 * @since 5.2.0
	 * @since 5.3.0 Tham số `$handled` được thêm vào.
	 *
	 * @param array         $error   Thông tin lỗi được lấy từ `error_get_last()`.
	 * @param true|WP_Error $handled Liệu Recovery Mode đã xử lý lỗi nghiêm trọng hay chưa.
	 */
	protected function display_error_template( $error, $handled ) {
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			// Load custom PHP error template, if present.
			$php_error_pluggable = WP_CONTENT_DIR . '/php-error.php';
			if ( is_readable( $php_error_pluggable ) ) {
				require_once $php_error_pluggable;

				return;
			}
		}

		// Otherwise, display the default error template.
		$this->display_default_error_template( $error, $handled );
	}

	/**
	 * Displays the default PHP error template.
	 *
	 * This method is called conditionally if no 'php-error.php' drop-in is available.
	 *
	 * It calls {@see wp_die()} with a message indicating that the site is experiencing technical difficulties and a
	 * login link to the admin backend. The {@see 'wp_php_error_message'} and {@see 'wp_php_error_args'} filters can
	 * be used to modify these parameters.
	 *
	 * @since 5.2.0
	 * @since 5.3.0 The `$handled` parameter was added.
	 *
	 * @param array         $error   Error information retrieved from `error_get_last()`.
	 * @param true|WP_Error $handled Whether Recovery Mode handled the fatal error.
	 */
	protected function display_default_error_template( $error, $handled ) {
		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		if ( ! function_exists( 'wp_die' ) ) {
			require_once ABSPATH . WPINC . '/functions.php';
		}

		if ( ! class_exists( 'WP_Error' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-error.php';
		}

		if ( true === $handled && wp_is_recovery_mode() ) {
			$message = __( 'There has been a critical error on this website, putting it in recovery mode. Please check the Themes and Plugins screens for more details. If you just installed or updated a theme or plugin, check the relevant page for that first.' );
		} elseif ( is_protected_endpoint() && wp_recovery_mode()->is_initialized() ) {
			if ( is_multisite() ) {
				$message = __( 'There has been a critical error on this website. Please reach out to your site administrator, and inform them of this error for further assistance.' );
			} else {
				$message = sprintf(
					/* translators: %s: Support forums URL. */
					__( 'There has been a critical error on this website. Please check your site admin email inbox for instructions. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
					__( 'https://wordpress.org/support/forums/' )
				);
			}
		} else {
			$message = __( 'There has been a critical error on this website.' );
		}

		$message = sprintf(
			'<p>%s</p><p><a href="%s">%s</a></p>',
			$message,
			/* translators: Documentation about troubleshooting. */
			__( 'https://wordpress.org/documentation/article/faq-troubleshooting/' ),
			__( 'Learn more about troubleshooting WordPress.' )
		);

		$args = array(
			'response' => 500,
			'exit'     => false,
		);

		/**
		 * Filters the message that the default PHP error template displays.
		 *
		 * @since 5.2.0
		 *
		 * @param string $message HTML error message to display.
		 * @param array  $error   Error information retrieved from `error_get_last()`.
		 */
		$message = apply_filters( 'wp_php_error_message', $message, $error );

		/**
		 * Filters the arguments passed to {@see wp_die()} for the default PHP error template.
		 *
		 * @since 5.2.0
		 *
		 * @param array $args Associative array of arguments passed to `wp_die()`. By default these contain a
		 *                    'response' key, and optionally 'link_url' and 'link_text' keys.
		 * @param array $error Error information retrieved from `error_get_last()`.
		 */
		$args = apply_filters( 'wp_php_error_args', $args, $error );

		$wp_error = new WP_Error(
			'internal_server_error',
			$message,
			array(
				'error' => $error,
			)
		);

		wp_die( $wp_error, '', $args );
	}
}
