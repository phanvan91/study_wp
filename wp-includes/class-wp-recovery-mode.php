<?php
/**
 * API bảo vệ lỗi: Lớp WP_Recovery_Mode
 *
 * @package WordPress
 * @since 5.2.0
 */

/**
 * Lớp cốt lõi dùng để triển khai Chế độ Phục hồi.
 *
 * @since 5.2.0
 */
#[AllowDynamicProperties]
class WP_Recovery_Mode {

	const EXIT_ACTION = 'exit_recovery_mode';

	/**
	 * Dịch vụ để xử lý cookie.
	 *
	 * @since 5.2.0
	 * @var WP_Recovery_Mode_Cookie_Service
	 */
	private $cookie_service;

	/**
	 * Dịch vụ để tạo khóa chế độ phục hồi.
	 *
	 * @since 5.2.0
	 * @var WP_Recovery_Mode_Key_Service
	 */
	private $key_service;

	/**
	 * Dịch vụ để tạo và xác thực các liên kết chế độ phục hồi.
	 *
	 * @since 5.2.0
	 * @var WP_Recovery_Mode_Link_Service
	 */
	private $link_service;

	/**
	 * Dịch vụ để xử lý việc gửi email có liên kết chế độ phục hồi.
	 *
	 * @since 5.2.0
	 * @var WP_Recovery_Mode_Email_Service
	 */
	private $email_service;

	/**
	 * Chế độ phục hồi đã được khởi tạo hay chưa.
	 *
	 * @since 5.2.0
	 * @var bool
	 */
	private $is_initialized = false;

	/**
	 * Chế độ phục hồi có hoạt động trong phiên này hay không.
	 *
	 * @since 5.2.0
	 * @var bool
	 */
	private $is_active = false;

	/**
	 * Lấy ID đại diện cho phiên chế độ phục hồi hiện tại.
	 *
	 * @since 5.2.0
	 * @var string
	 */
	private $session_id = '';

	/**
	 * Hàm khởi tạo WP_Recovery_Mode.
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		$this->cookie_service = new WP_Recovery_Mode_Cookie_Service();
		$this->key_service    = new WP_Recovery_Mode_Key_Service();
		$this->link_service   = new WP_Recovery_Mode_Link_Service( $this->cookie_service, $this->key_service );
		$this->email_service  = new WP_Recovery_Mode_Email_Service( $this->link_service );
	}

	/**
	 * Khởi tạo chế độ phục hồi cho yêu cầu hiện tại.
	 *
	 * @since 5.2.0
	 */
	public function initialize() {
		$this->is_initialized = true;

		add_action( 'wp_logout', array( $this, 'exit_recovery_mode' ) );
		add_action( 'login_form_' . self::EXIT_ACTION, array( $this, 'handle_exit_recovery_mode' ) );
		add_action( 'recovery_mode_clean_expired_keys', array( $this, 'clean_expired_keys' ) );

		if ( ! wp_next_scheduled( 'recovery_mode_clean_expired_keys' ) && ! wp_installing() ) {
			wp_schedule_event( time(), 'daily', 'recovery_mode_clean_expired_keys' );
		}

		if ( defined( 'WP_RECOVERY_MODE_SESSION_ID' ) ) {
			$this->is_active  = true;
			$this->session_id = WP_RECOVERY_MODE_SESSION_ID;

			return;
		}

		if ( $this->cookie_service->is_cookie_set() ) {
			$this->handle_cookie();

			return;
		}

		$this->link_service->handle_begin_link( $this->get_link_ttl() );
	}

	/**
	 * Kiểm tra xem chế độ phục hồi có hoạt động hay không.
	 *
	 * Điều này sẽ không thay đổi sau khi chế độ phục hồi đã được khởi tạo. {@see WP_Recovery_Mode::run()}.
	 *
	 * @since 5.2.0
	 *
	 * @return bool True nếu chế độ phục hồi hoạt động, false nếu ngược lại.
	 */
	public function is_active() {
		return $this->is_active;
	}

	/**
	 * Lấy ID phiên chế độ phục hồi.
	 *
	 * @since 5.2.0
	 *
	 * @return string ID phiên nếu chế độ phục hồi hoạt động, chuỗi rỗng nếu ngược lại.
	 */
	public function get_session_id() {
		return $this->session_id;
	}

	/**
	 * Kiểm tra xem chế độ phục hồi đã được khởi tạo hay chưa.
	 *
	 * Chế độ phục hồi không nên được sử dụng cho đến thời điểm này. Khởi tạo xảy ra ngay trước khi tải plugin.
	 *
	 * @since 5.2.0
	 *
	 * @return bool
	 */
	public function is_initialized() {
		return $this->is_initialized;
	}

	/**
	 * Xử lý một lỗi nghiêm trọng xảy ra.
	 *
	 * API gọi nên ngay lập tức die() sau khi gọi hàm này.
	 *
	 * @since 5.2.0
	 *
	 * @param array $error Chi tiết lỗi từ `error_get_last()`.
	 * @return true|WP_Error|void True nếu lỗi đã được xử lý và tiêu đề đã được gửi.
	 *                            Hoặc yêu cầu sẽ thoát để thử bắt nhiều lỗi cùng một lúc.
	 *                            WP_Error nếu một lỗi xảy ra ngăn nó được xử lý.
	 */
	public function handle_error( array $error ) {

		$extension = $this->get_extension_for_error( $error );

		if ( ! $extension || $this->is_network_plugin( $extension ) ) {
			return new WP_Error( 'invalid_source', __( 'Error not caused by a plugin or theme.' ) );
		}

		if ( ! $this->is_active() ) {
			if ( ! is_protected_endpoint() ) {
				return new WP_Error( 'non_protected_endpoint', __( 'Error occurred on a non-protected endpoint.' ) );
			}

			if ( ! function_exists( 'wp_generate_password' ) ) {
				require_once ABSPATH . WPINC . '/pluggable.php';
			}

			return $this->email_service->maybe_send_recovery_mode_email( $this->get_email_rate_limit(), $error, $extension );
		}

		if ( ! $this->store_error( $error ) ) {
			return new WP_Error( 'storage_error', __( 'Failed to store the error.' ) );
		}

		if ( headers_sent() ) {
			return true;
		}

		$this->redirect_protected();
	}

	/**
	 * Kết thúc phiên chế độ phục hồi hiện tại.
	 *
	 * @since 5.2.0
	 *
	 * @return bool True nếu thành công, false nếu thất bại.
	 */
	public function exit_recovery_mode() {
		if ( ! $this->is_active() ) {
			return false;
		}

		$this->email_service->clear_rate_limit();
		$this->cookie_service->clear_cookie();

		wp_paused_plugins()->delete_all();
		wp_paused_themes()->delete_all();

		return true;
	}

	/**
	 * Xử lý yêu cầu thoát Chế độ Phục hồi.
	 *
	 * @since 5.2.0
	 */
	public function handle_exit_recovery_mode() {
		$redirect_to = wp_get_referer();

		// Kiểm tra an toàn trong trường hợp người giới thiệu trả về false.
		if ( ! $redirect_to ) {
			$redirect_to = is_user_logged_in() ? admin_url() : home_url();
		}

		if ( ! $this->is_active() ) {
			wp_safe_redirect( $redirect_to );
			die;
		}

		if ( ! isset( $_GET['action'] ) || self::EXIT_ACTION !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], self::EXIT_ACTION ) ) {
			wp_die( __( 'Exit recovery mode link expired.' ), 403 );
		}

		if ( ! $this->exit_recovery_mode() ) {
			wp_die( __( 'Failed to exit recovery mode. Please try again later.' ) );
		}

		wp_safe_redirect( $redirect_to );
		die;
	}

	/**
	 * Xóa bất kỳ khóa chế độ phục hồi nào đã hết hạn theo TTL liên kết.
	 *
	 * Thực thi theo lịch trình cron hàng ngày.
	 *
	 * @since 5.2.0
	 */
	public function clean_expired_keys() {
		$this->key_service->clean_expired_keys( $this->get_link_ttl() );
	}

	/**
	 * Xử lý việc kiểm tra cookie chế độ phục hồi và xác thực nó.
	 *
	 * @since 5.2.0
	 */
	protected function handle_cookie() {
		$validated = $this->cookie_service->validate_cookie();

		if ( is_wp_error( $validated ) ) {
			$this->cookie_service->clear_cookie();

			$validated->add_data( array( 'status' => 403 ) );
			wp_die( $validated );
		}

		$session_id = $this->cookie_service->get_session_id_from_cookie();
		if ( is_wp_error( $session_id ) ) {
			$this->cookie_service->clear_cookie();

			$session_id->add_data( array( 'status' => 403 ) );
			wp_die( $session_id );
		}

		$this->is_active  = true;
		$this->session_id = $session_id;
	}

	/**
	 * Lấy giới hạn tỷ lệ giữa việc gửi các liên kết email chế độ phục hồi mới.
	 *
	 * @since 5.2.0
	 *
	 * @return int Giới hạn tỷ lệ tính bằng giây.
	 */
	protected function get_email_rate_limit() {
		/**
		 * Lọc giới hạn tỷ lệ giữa việc gửi các liên kết email chế độ phục hồi mới.
		 *
		 * @since 5.2.0
		 *
		 * @param int $rate_limit Thời gian chờ tính bằng giây. Mặc định là 1 ngày.
		 */
		return apply_filters( 'recovery_mode_email_rate_limit', DAY_IN_SECONDS );
	}

	/**
	 * Lấy số giây liên kết chế độ phục hồi có hiệu lực.
	 *
	 * @since 5.2.0
	 *
	 * @return int Khoảng thời gian tính bằng giây.
	 */
	protected function get_link_ttl() {

		$rate_limit = $this->get_email_rate_limit();
		$valid_for  = $rate_limit;

		/**
		 * Lọc lượng thời gian liên kết email chế độ phục hồi có hiệu lực.
		 *
		 * TTL phải ít nhất bằng giới hạn tỷ lệ email.
		 *
		 * @since 5.2.0
		 *
		 * @param int $valid_for Số giây liên kết có hiệu lực.
		 */
		$valid_for = apply_filters( 'recovery_mode_email_link_ttl', $valid_for );

		return max( $valid_for, $rate_limit );
	}

	/**
	 * Lấy phần mở rộng mà lỗi xảy ra.
	 *
	 * @since 5.2.0
	 *
	 * @global string[] $wp_theme_directories
	 *
	 * @param array $error Chi tiết lỗi từ `error_get_last()`.
	 * @return array|false {
	 *     Chi tiết phần mở rộng.
	 *
	 *     @type string $slug Slug của phần mở rộng. Đây là thư mục của plugin hoặc theme.
	 *     @type string $type Loại phần mở rộng. Hoặc 'plugin' hoặc 'theme'.
	 * }
	 */
	protected function get_extension_for_error( $error ) {
		global $wp_theme_directories;

		if ( ! isset( $error['file'] ) ) {
			return false;
		}

		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return false;
		}

		$error_file    = wp_normalize_path( $error['file'] );
		$wp_plugin_dir = wp_normalize_path( WP_PLUGIN_DIR );

		if ( str_starts_with( $error_file, $wp_plugin_dir ) ) {
			$path  = str_replace( $wp_plugin_dir . '/', '', $error_file );
			$parts = explode( '/', $path );

			return array(
				'type' => 'plugin',
				'slug' => $parts[0],
			);
		}

		if ( empty( $wp_theme_directories ) ) {
			return false;
		}

		foreach ( $wp_theme_directories as $theme_directory ) {
			$theme_directory = wp_normalize_path( $theme_directory );

			if ( str_starts_with( $error_file, $theme_directory ) ) {
				$path  = str_replace( $theme_directory . '/', '', $error_file );
				$parts = explode( '/', $path );

				return array(
					'type' => 'theme',
					'slug' => $parts[0],
				);
			}
		}

		return false;
	}

	/**
	 * Kiểm tra xem phần mở rộng đã cho có phải là một plugin được kích hoạt mạng hay không.
	 *
	 * @since 5.2.0
	 *
	 * @param array $extension Dữ liệu phần mở rộng.
	 * @return bool True nếu là plugin mạng, false nếu ngược lại.
	 */
	protected function is_network_plugin( $extension ) {
		if ( 'plugin' !== $extension['type'] ) {
			return false;
		}

		if ( ! is_multisite() ) {
			return false;
		}

		$network_plugins = wp_get_active_network_plugins();

		foreach ( $network_plugins as $plugin ) {
			if ( str_starts_with( $plugin, $extension['slug'] . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Lưu trữ lỗi đã cho để phần mở rộng gây ra nó bị tạm dừng.
	 *
	 * @since 5.2.0
	 *
	 * @param array $error Chi tiết lỗi từ `error_get_last()`.
	 * @return bool True nếu lỗi được lưu trữ thành công, false nếu ngược lại.
	 */
	protected function store_error( $error ) {
		$extension = $this->get_extension_for_error( $error );

		if ( ! $extension ) {
			return false;
		}

		switch ( $extension['type'] ) {
			case 'plugin':
				return wp_paused_plugins()->set( $extension['slug'], $error );
			case 'theme':
				return wp_paused_themes()->set( $extension['slug'], $error );
			default:
				return false;
		}
	}

	/**
	 * Chuyển hướng yêu cầu hiện tại để cho phép khôi phục nhiều lỗi cùng một lúc.
	 *
	 * Việc chuyển hướng sẽ chỉ xảy ra khi trên một điểm cuối được bảo vệ.
	 *
	 * Phải đảm bảo rằng phương thức này chỉ được gọi khi một lỗi thực sự xảy ra và sẽ không xảy ra trên
	 * yêu cầu tiếp theo nữa. Nếu không, nó sẽ tạo ra một vòng lặp chuyển hướng.
	 *
	 * @since 5.2.0
	 */
	protected function redirect_protected() {
		// Pluggable thường được tải sau các plugin, vì vậy chúng tôi bao gồm nó ở đây để có chức năng chuyển hướng.
		if ( ! function_exists( 'wp_safe_redirect' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
		}

		$scheme = is_ssl() ? 'https://' : 'http://';

		$url = "{$scheme}{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		wp_safe_redirect( $url );
		exit;
	}
}
