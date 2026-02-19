<?php
/**
 * API Tùy biến: Lớp WP_Customize_Header_Image_Setting
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Cài đặt được sử dụng để lọc giá trị, nhưng sẽ không lưu kết quả.
 *
 * Kết quả nên được xử lý đúng cách bằng một cài đặt hoặc callback khác.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Setting
 */
final class WP_Customize_Header_Image_Setting extends WP_Customize_Setting {

	/**
	 * Chuỗi định danh duy nhất cho cài đặt.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $id = 'header_image_data';

	/**
	 * @since 3.4.0
	 *
	 * @global Custom_Image_Header $custom_image_header
	 *
	 * @param mixed $value Giá trị cần cập nhật.
	 */
	public function update( $value ) {
		global $custom_image_header;

		// Nếu _custom_header_background_just_in_time() không khởi tạo được $custom_image_header khi không phải is_admin().
		if ( empty( $custom_image_header ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-custom-image-header.php';
			$args                   = get_theme_support( 'custom-header' );
			$admin_head_callback    = isset( $args[0]['admin-head-callback'] ) ? $args[0]['admin-head-callback'] : null;
			$admin_preview_callback = isset( $args[0]['admin-preview-callback'] ) ? $args[0]['admin-preview-callback'] : null;
			$custom_image_header    = new Custom_Image_Header( $admin_head_callback, $admin_preview_callback );
		}

		/*
		 * Nếu giá trị không tồn tại (đã bị xóa hoặc ngẫu nhiên),
		 * sử dụng giá trị header_image.
		 */
		if ( ! $value ) {
			$value = $this->manager->get_setting( 'header_image' )->post_value();
		}

		if ( is_array( $value ) && isset( $value['choice'] ) ) {
			$custom_image_header->set_header_image( $value['choice'] );
		} else {
			$custom_image_header->set_header_image( $value );
		}
	}
}
