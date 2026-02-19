<?php
/**
 * API Tùy biến: Lớp WP_Customize_Filter_Setting
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Một cài đặt được sử dụng để lọc giá trị, nhưng sẽ không lưu kết quả.
 *
 * Kết quả nên được xử lý đúng cách bằng một cài đặt hoặc callback khác.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Setting
 */
class WP_Customize_Filter_Setting extends WP_Customize_Setting {

	/**
	 * Lưu giá trị của cài đặt, sử dụng API liên quan.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $value Giá trị cần cập nhật.
	 */
	public function update( $value ) {}
}
