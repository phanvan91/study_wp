<?php
/**
 * API Nâng cấp: Lớp WP_Ajax_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện Nâng cấp cho các nâng cấp WordPress qua Ajax.
 *
 * Giao diện này được thiết kế để sử dụng cho các cập nhật Ajax.
 *
 * @since 4.6.0
 *
 * @see Automatic_Upgrader_Skin
 */
class WP_Ajax_Upgrader_Skin extends Automatic_Upgrader_Skin {

	/**
	 * Thông tin plugin.
	 *
	 * Phương thức Plugin_Upgrader::bulk_upgrade() sẽ điền thông tin này
	 * với dữ liệu lấy từ hàm get_plugin_data().
	 *
	 * @var array Dữ liệu plugin. Các giá trị sẽ rỗng nếu không được plugin cung cấp.
	 */
	public $plugin_info = array();

	/**
	 * Thông tin giao diện.
	 *
	 * Phương thức Theme_Upgrader::bulk_upgrade() sẽ điền thông tin này
	 * với dữ liệu lấy từ phương thức Theme_Upgrader::theme_info(),
	 * mà phương thức đó gọi hàm wp_get_theme().
	 *
	 * @var WP_Theme|false Đối tượng thông tin giao diện, hoặc false.
	 */
	public $theme_info = false;

	/**
	 * Lưu trữ đối tượng WP_Error.
	 *
	 * @since 4.6.0
	 *
	 * @var null|WP_Error
	 */
	protected $errors = null;

	/**
	 * Hàm khởi tạo.
	 *
	 * Thiết lập giao diện nâng cấp Ajax WordPress.
	 *
	 * @since 4.6.0
	 *
	 * @see WP_Upgrader_Skin::__construct()
	 *
	 * @param array $args Tùy chọn. Các tham số giao diện nâng cấp Ajax WordPress để
	 *                    ghi đè các tùy chọn mặc định. Xem WP_Upgrader_Skin::__construct().
	 *                    Mặc định mảng rỗng.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->errors = new WP_Error();
	}

	/**
	 * Lấy danh sách các lỗi.
	 *
	 * @since 4.6.0
	 *
	 * @return WP_Error Các lỗi trong quá trình nâng cấp.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Lấy chuỗi các thông báo lỗi.
	 *
	 * @since 4.6.0
	 *
	 * @return string Các thông báo lỗi trong quá trình nâng cấp.
	 */
	public function get_error_messages() {
		$messages = array();

		foreach ( $this->errors->get_error_codes() as $error_code ) {
			$error_data = $this->errors->get_error_data( $error_code );

			if ( $error_data && is_string( $error_data ) ) {
				$messages[] = $this->errors->get_error_message( $error_code ) . ' ' . esc_html( strip_tags( $error_data ) );
			} else {
				$messages[] = $this->errors->get_error_message( $error_code );
			}
		}

		return implode( ', ', $messages );
	}

	/**
	 * Lưu trữ một thông báo lỗi về quá trình nâng cấp.
	 *
	 * @since 4.6.0
	 * @since 5.3.0 Chính thức hóa tham số `...$args` hiện có bằng cách thêm nó
	 *              vào chữ ký hàm.
	 *
	 * @param string|WP_Error $errors  Các lỗi.
	 * @param mixed           ...$args Các chuỗi thay thế tùy chọn.
	 */
	public function error( $errors, ...$args ) {
		if ( is_string( $errors ) ) {
			$string = $errors;
			if ( ! empty( $this->upgrader->strings[ $string ] ) ) {
				$string = $this->upgrader->strings[ $string ];
			}

			if ( str_contains( $string, '%' ) ) {
				if ( ! empty( $args ) ) {
					$string = vsprintf( $string, $args );
				}
			}

			// Đếm các lỗi hiện có để tạo mã lỗi duy nhất.
			$errors_count = count( $this->errors->get_error_codes() );
			$this->errors->add( 'unknown_upgrade_error_' . ( $errors_count + 1 ), $string );
		} elseif ( is_wp_error( $errors ) ) {
			foreach ( $errors->get_error_codes() as $error_code ) {
				$this->errors->add( $error_code, $errors->get_error_message( $error_code ), $errors->get_error_data( $error_code ) );
			}
		}

		parent::error( $errors, ...$args );
	}

	/**
	 * Lưu trữ một thông báo về quá trình nâng cấp.
	 *
	 * @since 4.6.0
	 * @since 5.3.0 Chính thức hóa tham số `...$args` hiện có bằng cách thêm nó
	 *              vào chữ ký hàm.
	 * @since 5.9.0 Đổi tên `$data` thành `$feedback` để hỗ trợ tham số có tên trong PHP 8.
	 *
	 * @param string|array|WP_Error $feedback Dữ liệu thông báo.
	 * @param mixed                 ...$args  Các chuỗi thay thế tùy chọn.
	 */
	public function feedback( $feedback, ...$args ) {
		if ( is_wp_error( $feedback ) ) {
			foreach ( $feedback->get_error_codes() as $error_code ) {
				$this->errors->add( $error_code, $feedback->get_error_message( $error_code ), $feedback->get_error_data( $error_code ) );
			}
		}

		parent::feedback( $feedback, ...$args );
	}
}
