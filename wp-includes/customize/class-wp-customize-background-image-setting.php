<?php
/**
 * API Tùy biến: Lớp WP_Customize_Background_Image_Setting
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Cài đặt Hình nền trong Trình tùy biến.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Setting
 */
final class WP_Customize_Background_Image_Setting extends WP_Customize_Setting {

	/**
	 * Chuỗi định danh duy nhất cho cài đặt.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $id = 'background_image_thumb';

	/**
	 * @since 3.4.0
	 *
	 * @param mixed $value Giá trị cần cập nhật. Không được sử dụng.
	 */
	public function update( $value ) {
		remove_theme_mod( 'background_image_thumb' );
	}
}
