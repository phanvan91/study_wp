<?php
/**
 * API Tùy biến: Lớp WP_Customize_Cropped_Image_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Điều khiển Hình ảnh Cắt xén trong Tùy biến.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Image_Control
 */
class WP_Customize_Cropped_Image_Control extends WP_Customize_Image_Control {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'cropped_image';

	/**
	 * Chiều rộng đề xuất cho hình ảnh cắt xén.
	 *
	 * @since 4.3.0
	 * @var int
	 */
	public $width = 150;

	/**
	 * Chiều cao đề xuất cho hình ảnh cắt xén.
	 *
	 * @since 4.3.0
	 * @var int
	 */
	public $height = 150;

	/**
	 * Chiều rộng có linh hoạt hay không.
	 *
	 * @since 4.3.0
	 * @var bool
	 */
	public $flex_width = false;

	/**
	 * Chiều cao có linh hoạt hay không.
	 *
	 * @since 4.3.0
	 * @var bool
	 */
	public $flex_height = false;

	/**
	 * Nạp các script/style liên quan đến điều khiển.
	 *
	 * @since 4.3.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'customize-views' );

		parent::enqueue();
	}

	/**
	 * Làm mới các tham số được truyền tới JavaScript qua JSON.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();

		$this->json['width']       = absint( $this->width );
		$this->json['height']      = absint( $this->height );
		$this->json['flex_width']  = absint( $this->flex_width );
		$this->json['flex_height'] = absint( $this->flex_height );
	}
}
