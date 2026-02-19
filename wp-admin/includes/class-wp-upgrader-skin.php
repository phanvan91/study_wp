<?php
/**
 * API Nâng cấp: Lớp WP_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện chung cho các lớp Nâng cấp WordPress. Giao diện này được thiết kế để mở rộng cho các mục đích cụ thể.
 *
 * @since 2.8.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 */
#[AllowDynamicProperties]
class WP_Upgrader_Skin {

	/**
	 * Lưu trữ dữ liệu trình nâng cấp.
	 *
	 * @since 2.8.0
	 * @var WP_Upgrader
	 */
	public $upgrader;

	/**
	 * Phần tiêu đề đã hoàn thành hay chưa.
	 *
	 * @since 2.8.0
	 * @var bool
	 */
	public $done_header = false;

	/**
	 * Phần chân trang đã hoàn thành hay chưa.
	 *
	 * @since 2.8.0
	 * @var bool
	 */
	public $done_footer = false;

	/**
	 * Lưu trữ kết quả của quá trình nâng cấp.
	 *
	 * @since 2.8.0
	 * @var string|bool|WP_Error
	 */
	public $result = false;

	/**
	 * Lưu trữ các tùy chọn của quá trình nâng cấp.
	 *
	 * @since 2.8.0
	 * @var array
	 */
	public $options = array();

	/**
	 * Hàm khởi tạo.
	 *
	 * Thiết lập giao diện chung cho các lớp Nâng cấp WordPress.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args Tùy chọn. Các tham số giao diện nâng cấp WordPress
	 *                    để ghi đè các tùy chọn mặc định. Mặc định mảng rỗng.
	 */
	public function __construct( $args = array() ) {
		$defaults      = array(
			'url'     => '',
			'nonce'   => '',
			'title'   => '',
			'context' => false,
		);
		$this->options = wp_parse_args( $args, $defaults );
	}

	/**
	 * Thiết lập mối quan hệ giữa giao diện đang sử dụng và trình nâng cấp.
	 *
	 * @since 2.8.0
	 *
	 * @param WP_Upgrader $upgrader
	 */
	public function set_upgrader( &$upgrader ) {
		if ( is_object( $upgrader ) ) {
			$this->upgrader =& $upgrader;
		}
		$this->add_strings();
	}

	/**
	 * Thiết lập các chuỗi sử dụng trong quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function add_strings() {
	}

	/**
	 * Thiết lập kết quả của quá trình nâng cấp.
	 *
	 * @since 2.8.0
	 *
	 * @param string|bool|WP_Error $result Kết quả của quá trình nâng cấp.
	 */
	public function set_result( $result ) {
		$this->result = $result;
	}

	/**
	 * Hiển thị biểu mẫu cho người dùng để yêu cầu thông tin FTP/SSH
	 * nhằm kết nối tới hệ thống tệp.
	 *
	 * @since 2.8.0
	 * @since 4.6.0 Giá trị mặc định của tham số `$context` thay đổi từ `false` sang chuỗi rỗng.
	 *
	 * @see request_filesystem_credentials()
	 *
	 * @param bool|WP_Error $error                        Tùy chọn. Yêu cầu hiện tại có thất bại kết nối hay không,
	 *                                                    hoặc một đối tượng lỗi. Mặc định false.
	 * @param string        $context                      Tùy chọn. Đường dẫn đầy đủ tới thư mục được kiểm tra
	 *                                                    quyền ghi. Mặc định rỗng.
	 * @param bool          $allow_relaxed_file_ownership Tùy chọn. Cho phép quyền ghi Group/World hay không. Mặc định false.
	 * @return bool True nếu thành công, false nếu thất bại.
	 */
	public function request_filesystem_credentials( $error = false, $context = '', $allow_relaxed_file_ownership = false ) {
		$url = $this->options['url'];
		if ( ! $context ) {
			$context = $this->options['context'];
		}
		if ( ! empty( $this->options['nonce'] ) ) {
			$url = wp_nonce_url( $url, $this->options['nonce'] );
		}

		$extra_fields = array();

		return request_filesystem_credentials( $url, '', $error, $context, $extra_fields, $allow_relaxed_file_ownership );
	}

	/**
	 * Hiển thị phần tiêu đề trước quá trình cập nhật.
	 *
	 * @since 2.8.0
	 */
	public function header() {
		if ( $this->done_header ) {
			return;
		}
		$this->done_header = true;
		echo '<div class="wrap">';
		echo '<h1>' . $this->options['title'] . '</h1>';
	}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật.
	 *
	 * @since 2.8.0
	 */
	public function footer() {
		if ( $this->done_footer ) {
			return;
		}
		$this->done_footer = true;
		echo '</div>';
	}

	/**
	 * Hiển thị thông báo lỗi về việc cập nhật.
	 *
	 * @since 2.8.0
	 *
	 * @param string|WP_Error $errors Các lỗi.
	 */
	public function error( $errors ) {
		if ( ! $this->done_header ) {
			$this->header();
		}
		if ( is_string( $errors ) ) {
			$this->feedback( $errors );
		} elseif ( is_wp_error( $errors ) && $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $message ) {
				if ( $errors->get_error_data() && is_string( $errors->get_error_data() ) ) {
					$this->feedback( $message . ' ' . esc_html( strip_tags( $errors->get_error_data() ) ) );
				} else {
					$this->feedback( $message );
				}
			}
		}
	}

	/**
	 * Hiển thị một thông báo về quá trình cập nhật.
	 *
	 * @since 2.8.0
	 * @since 5.9.0 Đổi tên `$string` (từ khóa dành riêng PHP) thành `$feedback` để hỗ trợ tham số đặt tên PHP 8.
	 *
	 * @param string $feedback Dữ liệu thông báo.
	 * @param mixed  ...$args  Các chuỗi thay thế tùy chọn.
	 */
	public function feedback( $feedback, ...$args ) {
		if ( isset( $this->upgrader->strings[ $feedback ] ) ) {
			$feedback = $this->upgrader->strings[ $feedback ];
		}

		if ( str_contains( $feedback, '%' ) ) {
			if ( $args ) {
				$args     = array_map( 'strip_tags', $args );
				$args     = array_map( 'esc_html', $args );
				$feedback = vsprintf( $feedback, $args );
			}
		}
		if ( empty( $feedback ) ) {
			return;
		}
		show_message( $feedback );
	}

	/**
	 * Thực hiện hành động trước khi cập nhật.
	 *
	 * @since 2.8.0
	 */
	public function before() {}

	/**
	 * Thực hiện hành động sau khi cập nhật.
	 *
	 * @since 2.8.0
	 */
	public function after() {}

	/**
	 * Xuất JavaScript gọi hàm để giảm số lượng bản cập nhật.
	 *
	 * @since 3.9.0
	 *
	 * @param string $type Loại số đếm cập nhật cần giảm. Các giá trị có thể bao gồm 'plugin',
	 *                     'theme', 'translation', v.v.
	 */
	protected function decrement_update_count( $type ) {
		if ( ! $this->result || is_wp_error( $this->result ) || 'up_to_date' === $this->result ) {
			return;
		}

		if ( defined( 'IFRAME_REQUEST' ) ) {
			echo '<script type="text/javascript">
					if ( window.postMessage && JSON ) {
						window.parent.postMessage(
							JSON.stringify( {
								action: "decrementUpdateCount",
								upgradeType: "' . $type . '"
							} ),
							window.location.protocol + "//" + window.location.hostname
								+ ( "" !== window.location.port ? ":" + window.location.port : "" )
						);
					}
				</script>';
		} else {
			echo '<script type="text/javascript">
					(function( wp ) {
						if ( wp && wp.updates && wp.updates.decrementCount ) {
							wp.updates.decrementCount( "' . $type . '" );
						}
					})( window.wp );
				</script>';
		}
	}

	/**
	 * Hiển thị phần tiêu đề trước quá trình cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 */
	public function bulk_header() {}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 */
	public function bulk_footer() {}

	/**
	 * Ẩn thông báo lỗi `process_failed` khi cập nhật bằng cách tải lên tệp zip.
	 *
	 * @since 5.5.0
	 *
	 * @param WP_Error $wp_error Đối tượng WP_Error.
	 * @return bool True nếu lỗi cần được ẩn, false nếu ngược lại.
	 */
	public function hide_process_failed( $wp_error ) {
		return false;
	}
}
