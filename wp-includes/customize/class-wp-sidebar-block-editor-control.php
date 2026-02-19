<?php
/**
 * API Tùy biến: Lớp WP_Sidebar_Block_Editor_Control.
 *
 * @package WordPress
 * @subpackage Customize
 * @since 5.8.0
 */

/**
 * Lớp cốt lõi được sử dụng để triển khai điều khiển trình soạn thảo khối widget
 * trong trình tùy biến.
 *
 * @since 5.8.0
 *
 * @see WP_Customize_Control
 */
class WP_Sidebar_Block_Editor_Control extends WP_Customize_Control {
	/**
	 * Loại điều khiển.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public $type = 'sidebar_block_editor';

	/**
	 * Hiển thị vùng chứa trình soạn thảo khối widget.
	 *
	 * @since 5.8.0
	 */
	public function render_content() {
		// Hiển thị một điều khiển rỗng. JavaScript trong
		// @wordpress/customize-widgets sẽ xử lý phần còn lại.
	}
}
