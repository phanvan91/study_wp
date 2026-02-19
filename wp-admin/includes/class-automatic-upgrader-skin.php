<?php
/**
 * API Nâng cấp: Lớp Automatic_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện nâng cấp cho việc tự động nâng cấp WordPress.
 *
 * Giao diện này được thiết kế để sử dụng khi không cần hiển thị đầu ra, tất cả đầu ra
 * được ghi lại và lưu trữ để bên gọi xử lý và ghi log/gửi email/loại bỏ.
 *
 * @since 3.7.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see Bulk_Upgrader_Skin
 */
class Automatic_Upgrader_Skin extends WP_Upgrader_Skin {
	protected $messages = array();

	/**
	 * Xác định xem trình nâng cấp có cần thông tin FTP/SSH để kết nối
	 * tới hệ thống tệp hay không.
	 *
	 * @since 3.7.0
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
		if ( $context ) {
			$this->options['context'] = $context;
		}
		/*
		 * TODO: Sửa lại request_filesystem_credentials(), hoặc tách nó ra, để cho phép yêu cầu phiên bản không có đầu ra.
		 * Hàm này sẽ hiển thị biểu mẫu thông tin đăng nhập khi thất bại. Chúng ta không muốn điều đó, nên chỉ ẩn bằng bộ đệm.
		 */
		ob_start();
		$result = parent::request_filesystem_credentials( $error, $context, $allow_relaxed_file_ownership );
		ob_end_clean();
		return $result;
	}

	/**
	 * Lấy các thông báo nâng cấp.
	 *
	 * @since 3.7.0
	 *
	 * @return string[] Các thông báo trong quá trình nâng cấp.
	 */
	public function get_upgrade_messages() {
		return $this->messages;
	}

	/**
	 * Lưu trữ một thông báo về quá trình nâng cấp.
	 *
	 * @since 3.7.0
	 * @since 5.9.0 Đổi tên `$data` thành `$feedback` để hỗ trợ tham số đặt tên PHP 8.
	 *
	 * @param string|array|WP_Error $feedback Dữ liệu thông báo.
	 * @param mixed                 ...$args  Các chuỗi thay thế tùy chọn.
	 */
	public function feedback( $feedback, ...$args ) {
		if ( is_wp_error( $feedback ) ) {
			$string = $feedback->get_error_message();
		} elseif ( is_array( $feedback ) ) {
			return;
		} else {
			$string = $feedback;
		}

		if ( ! empty( $this->upgrader->strings[ $string ] ) ) {
			$string = $this->upgrader->strings[ $string ];
		}

		if ( str_contains( $string, '%' ) ) {
			if ( ! empty( $args ) ) {
				$string = vsprintf( $string, $args );
			}
		}

		$string = trim( $string );

		// Chỉ cho phép HTML cơ bản trong các thông báo, vì nó sẽ được sử dụng trong email/log thay vì hiển thị trực tiếp trên trình duyệt.
		$string = wp_kses(
			$string,
			array(
				'a'      => array(
					'href' => true,
				),
				'br'     => true,
				'em'     => true,
				'strong' => true,
			)
		);

		if ( empty( $string ) ) {
			return;
		}

		$this->messages[] = $string;
	}

	/**
	 * Tạo một bộ đệm đầu ra mới.
	 *
	 * @since 3.7.0
	 */
	public function header() {
		ob_start();
	}

	/**
	 * Lấy nội dung đã đệm, xóa bộ đệm và xử lý đầu ra.
	 *
	 * @since 3.7.0
	 */
	public function footer() {
		$output = ob_get_clean();
		if ( ! empty( $output ) ) {
			$this->feedback( $output );
		}
	}
}
