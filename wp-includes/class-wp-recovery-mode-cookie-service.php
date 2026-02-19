<?php
/**
 * API bảo vệ lỗi: Lớp WP_Recovery_Mode_Cookie_Service
 *
 * @package WordPress
 * @since 5.2.0
 */

/**
 * Lớp cốt lõi dùng để thiết lập, xác thực và xóa cookie xác định một phiên Chế độ Phục hồi.
 *
 * @since 5.2.0
 */
#[AllowDynamicProperties]
final class WP_Recovery_Mode_Cookie_Service {

	/**
	 * Kiểm tra xem cookie chế độ phục hồi đã được thiết lập hay chưa.
	 *
	 * @since 5.2.0
	 *
	 * @return bool True nếu cookie đã được thiết lập, false nếu ngược lại.
	 */
	public function is_cookie_set() {
		return ! empty( $_COOKIE[ RECOVERY_MODE_COOKIE ] );
	}

	/**
	 * Thiết lập cookie chế độ phục hồi.
	 *
	 * Điều này phải được thực hiện ngay sau khi thoát khỏi yêu cầu.
	 *
	 * @since 5.2.0
	 */
	public function set_cookie() {

		$value = $this->generate_cookie();

		/**
		 * Lọc thời gian hiệu lực của cookie Chế độ Phục hồi.
		 *
		 * @since 5.2.0
		 *
		 * @param int $length Thời gian tính bằng giây.
		 */
		$length = apply_filters( 'recovery_mode_cookie_length', WEEK_IN_SECONDS );

		$expire = time() + $length;

		setcookie( RECOVERY_MODE_COOKIE, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

		if ( COOKIEPATH !== SITECOOKIEPATH ) {
			setcookie( RECOVERY_MODE_COOKIE, $value, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Xóa cookie chế độ phục hồi.
	 *
	 * @since 5.2.0
	 */
	public function clear_cookie() {
		setcookie( RECOVERY_MODE_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( RECOVERY_MODE_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Xác thực cookie chế độ phục hồi.
	 *
	 * @since 5.2.0
	 *
	 * @param string $cookie Tùy chọn chỉ định chuỗi cookie.
	 *                       Nếu bỏ qua, nó sẽ được lấy từ biến siêu toàn cầu.
	 * @return true|WP_Error True nếu thành công, đối tượng lỗi nếu thất bại.
	 */
	public function validate_cookie( $cookie = '' ) {

		if ( ! $cookie ) {
			if ( empty( $_COOKIE[ RECOVERY_MODE_COOKIE ] ) ) {
				return new WP_Error( 'no_cookie', __( 'No cookie present.' ) );
			}

			$cookie = $_COOKIE[ RECOVERY_MODE_COOKIE ];
		}

		$parts = $this->parse_cookie( $cookie );

		if ( is_wp_error( $parts ) ) {
			return $parts;
		}

		list( , $created_at, $random, $signature ) = $parts;

		if ( ! ctype_digit( $created_at ) ) {
			return new WP_Error( 'invalid_created_at', __( 'Invalid cookie format.' ) );
		}

		/** This filter is documented in wp-includes/class-wp-recovery-mode-cookie-service.php */
		$length = apply_filters( 'recovery_mode_cookie_length', WEEK_IN_SECONDS );

		if ( time() > $created_at + $length ) {
			return new WP_Error( 'expired', __( 'Cookie expired.' ) );
		}

		$to_sign = sprintf( 'recovery_mode|%s|%s', $created_at, $random );
		$hashed  = $this->recovery_mode_hash( $to_sign );

		if ( ! hash_equals( $signature, $hashed ) ) {
			return new WP_Error( 'signature_mismatch', __( 'Invalid cookie.' ) );
		}

		return true;
	}

	/**
	 * Lấy định danh phiên từ cookie.
	 *
	 * Cookie phải được xác thực trước khi gọi API này.
	 *
	 * @since 5.2.0
	 *
	 * @param string $cookie Tùy chọn chỉ định chuỗi cookie.
	 *                       Nếu bỏ qua, nó sẽ được lấy từ biến siêu toàn cầu.
	 * @return string|WP_Error ID phiên nếu thành công, hoặc đối tượng lỗi nếu thất bại.
	 */
	public function get_session_id_from_cookie( $cookie = '' ) {
		if ( ! $cookie ) {
			if ( empty( $_COOKIE[ RECOVERY_MODE_COOKIE ] ) ) {
				return new WP_Error( 'no_cookie', __( 'No cookie present.' ) );
			}

			$cookie = $_COOKIE[ RECOVERY_MODE_COOKIE ];
		}

		$parts = $this->parse_cookie( $cookie );
		if ( is_wp_error( $parts ) ) {
			return $parts;
		}

		list( , , $random ) = $parts;

		return sha1( $random );
	}

	/**
	 * Phân tích cookie thành bốn phần.
	 *
	 * @since 5.2.0
	 *
	 * @param string $cookie Nội dung cookie.
	 * @return array|WP_Error Mảng các phần cookie, hoặc đối tượng lỗi nếu thất bại.
	 */
	private function parse_cookie( $cookie ) {
		$cookie = base64_decode( $cookie );
		$parts  = explode( '|', $cookie );

		if ( 4 !== count( $parts ) ) {
			return new WP_Error( 'invalid_format', __( 'Invalid cookie format.' ) );
		}

		return $parts;
	}

	/**
	 * Tạo giá trị cookie chế độ phục hồi.
	 *
	 * Cookie là một chuỗi được mã hóa base64 với định dạng sau:
	 *
	 * recovery_mode|iat|rand|signature
	 *
	 * Trong đó "recovery_mode" là một chuỗi hằng,
	 * iat là thời gian cookie được tạo,
	 * rand là một mật khẩu được tạo ngẫu nhiên cũng được dùng làm định danh phiên
	 * và signature là một hmac của 3 phần trước đó.
	 *
	 * @since 5.2.0
	 *
	 * @return string Nội dung cookie đã tạo.
	 */
	private function generate_cookie() {
		$to_sign = sprintf( 'recovery_mode|%s|%s', time(), wp_generate_password( 20, false ) );
		$signed  = $this->recovery_mode_hash( $to_sign );

		return base64_encode( sprintf( '%s|%s', $to_sign, $signed ) );
	}

	/**
	 * Lấy một dạng `wp_hash()` cụ thể cho Chế độ Phục hồi.
	 *
	 * Chúng ta không thể sử dụng `wp_hash()` vì nó được định nghĩa trong `pluggable.php`
	 * mà không được tải cho đến sau khi các plugin được tải, quá muộn để xác minh cookie chế độ phục hồi.
	 *
	 * Hàm này cố gắng sử dụng các salt `AUTH` trước, nhưng nếu chúng không hợp lệ,
	 * các salt cụ thể sẽ được tạo và lưu trữ.
	 *
	 * @since 5.2.0
	 *
	 * @param string $data Dữ liệu cần băm.
	 * @return string|false Dữ liệu $data đã băm, hoặc false nếu thất bại.
	 */
	private function recovery_mode_hash( $data ) {
		$default_keys = array_unique(
			array(
				'put your unique phrase here',
				/*
				 * translators: This string should only be translated if wp-config-sample.php is localized.
				 * You can check the localized release package or
				 * https://i18n.svn.wordpress.org/<locale code>/branches/<wp version>/dist/wp-config-sample.php
				 */
				__( 'put your unique phrase here' ),
			)
		);

		if ( ! defined( 'AUTH_KEY' ) || in_array( AUTH_KEY, $default_keys, true ) ) {
			$auth_key = get_site_option( 'recovery_mode_auth_key' );

			if ( ! $auth_key ) {
				if ( ! function_exists( 'wp_generate_password' ) ) {
					require_once ABSPATH . WPINC . '/pluggable.php';
				}

				$auth_key = wp_generate_password( 64, true, true );
				update_site_option( 'recovery_mode_auth_key', $auth_key );
			}
		} else {
			$auth_key = AUTH_KEY;
		}

		if ( ! defined( 'AUTH_SALT' ) || in_array( AUTH_SALT, $default_keys, true ) || AUTH_SALT === $auth_key ) {
			$auth_salt = get_site_option( 'recovery_mode_auth_salt' );

			if ( ! $auth_salt ) {
				if ( ! function_exists( 'wp_generate_password' ) ) {
					require_once ABSPATH . WPINC . '/pluggable.php';
				}

				$auth_salt = wp_generate_password( 64, true, true );
				update_site_option( 'recovery_mode_auth_salt', $auth_salt );
			}
		} else {
			$auth_salt = AUTH_SALT;
		}

		$secret = $auth_key . $auth_salt;

		return hash_hmac( 'sha1', $data, $secret );
	}
}
