<?php
/**
 * API bảo vệ lỗi: Lớp WP_Recovery_Mode_Link_Handler
 *
 * @package WordPress
 * @since 5.2.0
 */

/**
 * Lớp cốt lõi dùng để tạo và xử lý các liên kết chế độ phục hồi.
 *
 * @since 5.2.0
 */
#[AllowDynamicProperties]
class WP_Recovery_Mode_Link_Service {
	const LOGIN_ACTION_ENTER   = 'enter_recovery_mode';
	const LOGIN_ACTION_ENTERED = 'entered_recovery_mode';

	/**
	 * Dịch vụ để tạo và xác thực các khóa chế độ phục hồi.
	 *
	 * @since 5.2.0
	 * @var WP_Recovery_Mode_Key_Service
	 */
	private $key_service;

	/**
	 * Dịch vụ để xử lý cookie.
	 *
	 * @since 5.2.0
	 * @var WP_Recovery_Mode_Cookie_Service
	 */
	private $cookie_service;

	/**
	 * Hàm khởi tạo WP_Recovery_Mode_Link_Service.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Recovery_Mode_Cookie_Service $cookie_service Dịch vụ để xử lý việc thiết lập cookie chế độ phục hồi.
	 * @param WP_Recovery_Mode_Key_Service    $key_service    Dịch vụ để xử lý việc tạo các khóa chế độ phục hồi.
	 */
	public function __construct( WP_Recovery_Mode_Cookie_Service $cookie_service, WP_Recovery_Mode_Key_Service $key_service ) {
		$this->cookie_service = $cookie_service;
		$this->key_service    = $key_service;
	}

	/**
	 * Tạo URL để bắt đầu chế độ phục hồi.
	 *
	 * Chỉ một URL chế độ phục hồi có thể hợp lệ tại cùng một thời điểm.
	 *
	 * @since 5.2.0
	 *
	 * @return string URL đã tạo.
	 */
	public function generate_url() {
		$token = $this->key_service->generate_recovery_mode_token();
		$key   = $this->key_service->generate_and_store_recovery_mode_key( $token );

		return $this->get_recovery_mode_begin_url( $token, $key );
	}

	/**
	 * Vào chế độ phục hồi khi người dùng truy cập wp-login.php với một liên kết chế độ phục hồi hợp lệ.
	 *
	 * @since 5.2.0
	 *
	 * @global string $pagenow Tên tệp của màn hình hiện tại.
	 *
	 * @param int $ttl Số giây liên kết có hiệu lực.
	 */
	public function handle_begin_link( $ttl ) {
		if ( ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			return;
		}

		if ( ! isset( $_GET['action'], $_GET['rm_token'], $_GET['rm_key'] ) || self::LOGIN_ACTION_ENTER !== $_GET['action'] ) {
			return;
		}

		if ( ! function_exists( 'wp_generate_password' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
		}

		$validated = $this->key_service->validate_recovery_mode_key( $_GET['rm_token'], $_GET['rm_key'], $ttl );

		if ( is_wp_error( $validated ) ) {
			wp_die( $validated, '' );
		}

		$this->cookie_service->set_cookie();

		$url = add_query_arg( 'action', self::LOGIN_ACTION_ENTERED, wp_login_url() );
		wp_redirect( $url );
		die;
	}

	/**
	 * Lấy URL để bắt đầu chế độ phục hồi.
	 *
	 * @since 5.2.0
	 *
	 * @param string $token Mã thông báo Chế độ Phục hồi được tạo bởi {@see generate_recovery_mode_token()}.
	 * @param string $key   Khóa Chế độ Phục hồi được tạo bởi {@see generate_and_store_recovery_mode_key()}.
	 * @return string URL bắt đầu chế độ phục hồi.
	 */
	private function get_recovery_mode_begin_url( $token, $key ) {

		$url = add_query_arg(
			array(
				'action'   => self::LOGIN_ACTION_ENTER,
				'rm_token' => $token,
				'rm_key'   => $key,
			),
			wp_login_url()
		);

		/**
		 * Lọc URL để bắt đầu chế độ phục hồi.
		 *
		 * @since 5.2.0
		 *
		 * @param string $url   URL bắt đầu chế độ phục hồi đã tạo.
		 * @param string $token Mã thông báo dùng để xác định khóa.
		 * @param string $key   Khóa chế độ phục hồi.
		 */
		return apply_filters( 'recovery_mode_begin_url', $url, $token, $key );
	}
}
