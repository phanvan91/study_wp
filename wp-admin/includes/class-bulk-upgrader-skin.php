<?php
/**
 * API Nâng cấp: Lớp Bulk_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện nâng cấp hàng loạt chung cho việc nâng cấp WordPress.
 *
 * @since 3.0.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see WP_Upgrader_Skin
 */
class Bulk_Upgrader_Skin extends WP_Upgrader_Skin {

	/**
	 * Quá trình cập nhật hàng loạt đã bắt đầu hay chưa.
	 *
	 * @since 3.0.0
	 * @var bool
	 */
	public $in_loop = false;

	/**
	 * Lưu trữ thông báo lỗi về việc cập nhật.
	 *
	 * @since 3.0.0
	 * @var string|false
	 */
	public $error = false;

	/**
	 * Hàm khởi tạo.
	 *
	 * Thiết lập giao diện chung cho các lớp Nâng cấp hàng loạt.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'url'   => '',
			'nonce' => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		parent::__construct( $args );
	}

	/**
	 * Thiết lập các chuỗi sử dụng trong quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function add_strings() {
		$this->upgrader->strings['skin_upgrade_start'] = __( 'The update process is starting. This process may take a while on some hosts, so please be patient.' );
		/* translators: 1: Title of an update, 2: Error message. */
		$this->upgrader->strings['skin_update_failed_error'] = __( 'An error occurred while updating %1$s: %2$s' );
		/* translators: %s: Title of an update. */
		$this->upgrader->strings['skin_update_failed'] = __( 'The update of %s failed.' );
		/* translators: %s: Title of an update. */
		$this->upgrader->strings['skin_update_successful'] = __( '%s updated successfully.' );
		$this->upgrader->strings['skin_upgrade_end']       = __( 'All updates have been completed.' );
	}

	/**
	 * Hiển thị thông báo về việc cập nhật.
	 *
	 * @since 3.0.0
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
		if ( $this->in_loop ) {
			echo "$feedback<br />\n";
		} else {
			echo "<p>$feedback</p>\n";
		}
	}

	/**
	 * Hiển thị phần tiêu đề trước quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function header() {
		// Không có gì. Nội dung này sẽ được hiển thị trong một iframe.
	}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function footer() {
		// Không có gì. Nội dung này sẽ được hiển thị trong một iframe.
	}

	/**
	 * Hiển thị thông báo lỗi về việc cập nhật.
	 *
	 * @since 3.0.0
	 * @since 5.9.0 Đổi tên `$error` thành `$errors` để hỗ trợ tham số đặt tên PHP 8.
	 *
	 * @param string|WP_Error $errors Các lỗi.
	 */
	public function error( $errors ) {
		if ( is_string( $errors ) && isset( $this->upgrader->strings[ $errors ] ) ) {
			$this->error = $this->upgrader->strings[ $errors ];
		}

		if ( is_wp_error( $errors ) ) {
			$messages = array();
			foreach ( $errors->get_error_messages() as $emessage ) {
				if ( $errors->get_error_data() && is_string( $errors->get_error_data() ) ) {
					$messages[] = $emessage . ' ' . esc_html( strip_tags( $errors->get_error_data() ) );
				} else {
					$messages[] = $emessage;
				}
			}
			$this->error = implode( ', ', $messages );
		}
		echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').hide();</script>';
	}

	/**
	 * Hiển thị phần tiêu đề trước quá trình cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 */
	public function bulk_header() {
		$this->feedback( 'skin_upgrade_start' );
	}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 */
	public function bulk_footer() {
		$this->feedback( 'skin_upgrade_end' );
	}

	/**
	 * Thực hiện hành động trước khi cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title
	 */
	public function before( $title = '' ) {
		$this->in_loop = true;
		printf( '<h2>' . $this->upgrader->strings['skin_before_update_header'] . ' <span class="spinner waiting-' . $this->upgrader->update_current . '"></span></h2>', $title, $this->upgrader->update_current, $this->upgrader->update_count );
		echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').css("display", "inline-block");</script>';
		// Thẻ div thông báo tiến trình này sẽ được di chuyển qua JavaScript khi nhấp vào "Xem chi tiết.".
		echo '<div class="update-messages hide-if-js" id="progress-' . esc_attr( $this->upgrader->update_current ) . '"><p>';
		$this->flush_output();
	}

	/**
	 * Thực hiện hành động sau khi cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title
	 */
	public function after( $title = '' ) {
		echo '</p></div>';
		if ( $this->error || ! $this->result ) {
			if ( $this->error ) {
				$after_error_message = sprintf( $this->upgrader->strings['skin_update_failed_error'], $title, '<strong>' . $this->error . '</strong>' );
			} else {
				$after_error_message = sprintf( $this->upgrader->strings['skin_update_failed'], $title );
			}
			wp_admin_notice(
				$after_error_message,
				array(
					'additional_classes' => array( 'error' ),
				)
			);

			echo '<script type="text/javascript">jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').show();</script>';
		}
		if ( $this->result && ! is_wp_error( $this->result ) ) {
			if ( ! $this->error ) {
				echo '<div class="updated js-update-details" data-update-details="progress-' . esc_attr( $this->upgrader->update_current ) . '">' .
					'<p>' . sprintf( $this->upgrader->strings['skin_update_successful'], $title ) .
					' <button type="button" class="hide-if-no-js button-link js-update-details-toggle" aria-expanded="false">' . __( 'More details.' ) . '<span class="dashicons dashicons-arrow-down" aria-hidden="true"></span></button>' .
					'</p></div>';
			}

			echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').hide();</script>';
		}

		$this->reset();
		$this->flush_output();
	}

	/**
	 * Đặt lại các thuộc tính sử dụng trong quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function reset() {
		$this->in_loop = false;
		$this->error   = false;
	}

	/**
	 * Xả tất cả các bộ đệm đầu ra.
	 *
	 * @since 3.0.0
	 */
	public function flush_output() {
		wp_ob_end_flush_all();
		flush();
	}
}
