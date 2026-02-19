<?php
/**
 * API Tùy biến: Lớp WP_Customize_Image_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Điều khiển Hình ảnh trong Tùy biến.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Upload_Control
 */
class WP_Customize_Image_Control extends WP_Customize_Upload_Control {
	/**
	 * Loại điều khiển.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $type = 'image';

	/**
	 * Loại MIME của điều khiển phương tiện.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $mime_type = 'image';

	/**
	 * @since 3.4.2
	 * @deprecated 4.1.0
	 */
	public function prepare_control() {}

	/**
	 * @since 3.4.0
	 * @deprecated 4.1.0
	 *
	 * @param string $id
	 * @param string $label
	 * @param mixed  $callback
	 */
	public function add_tab( $id, $label, $callback ) {
		_deprecated_function( __METHOD__, '4.1.0' );
	}

	/**
	 * @since 3.4.0
	 * @deprecated 4.1.0
	 *
	 * @param string $id
	 */
	public function remove_tab( $id ) {
		_deprecated_function( __METHOD__, '4.1.0' );
	}

	/**
	 * @since 3.4.0
	 * @deprecated 4.1.0
	 *
	 * @param string $url
	 * @param string $thumbnail_url
	 */
	public function print_tab_image( $url, $thumbnail_url = null ) {
		_deprecated_function( __METHOD__, '4.1.0' );
	}
}
