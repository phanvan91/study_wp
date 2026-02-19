<?php
/**
 * API Tùy biến: Lớp WP_Customize_Upload_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Điều khiển Tải lên trong Tùy biến.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Media_Control
 */
class WP_Customize_Upload_Control extends WP_Customize_Media_Control {
	/**
	 * Loại điều khiển.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $type = 'upload';

	/**
	 * Loại MIME của điều khiển phương tiện.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $mime_type = '';

	/**
	 * Nhãn các nút.
	 *
	 * @since 4.1.0
	 * @var array
	 */
	public $button_labels = array();

	public $removed = '';         // Không sử dụng.
	public $context;              // Không sử dụng.
	public $extensions = array(); // Không sử dụng.

	/**
	 * Làm mới các tham số được truyền tới JavaScript qua JSON.
	 *
	 * @since 3.4.0
	 *
	 * @uses WP_Customize_Media_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();

		$value = $this->value();
		if ( $value ) {
			// Lấy mô hình đính kèm cho tệp hiện có.
			$attachment_id = attachment_url_to_postid( $value );
			if ( $attachment_id ) {
				$this->json['attachment'] = wp_prepare_attachment_for_js( $attachment_id );
			}
		}
	}
}
