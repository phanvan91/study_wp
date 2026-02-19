<?php
/**
 * API Tùy biến: Lớp WP_Widget_Form_Customize_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp điều khiển tùy biến biểu mẫu Widget.
 *
 * @since 3.9.0
 *
 * @see WP_Customize_Control
 */
class WP_Widget_Form_Customize_Control extends WP_Customize_Control {
	/**
	 * Loại điều khiển tùy biến.
	 *
	 * @since 3.9.0
	 * @var string
	 */
	public $type = 'widget_form';

	/**
	 * ID Widget.
	 *
	 * @since 3.9.0
	 * @var string
	 */
	public $widget_id;

	/**
	 * ID cơ sở của Widget.
	 *
	 * @since 3.9.0
	 * @var string
	 */
	public $widget_id_base;

	/**
	 * ID thanh bên.
	 *
	 * @since 3.9.0
	 * @var string
	 */
	public $sidebar_id;

	/**
	 * Trạng thái widget.
	 *
	 * @since 3.9.0
	 * @var bool True nếu mới, false nếu ngược lại. Mặc định false.
	 */
	public $is_new = false;

	/**
	 * Chiều rộng widget.
	 *
	 * @since 3.9.0
	 * @var int
	 */
	public $width;

	/**
	 * Chiều cao widget.
	 *
	 * @since 3.9.0
	 * @var int
	 */
	public $height;

	/**
	 * Chế độ widget.
	 *
	 * @since 3.9.0
	 * @var bool True nếu rộng, false nếu ngược lại. Mặc định false.
	 */
	public $is_wide = false;

	/**
	 * Thu thập các tham số điều khiển để xuất sang JavaScript.
	 *
	 * @since 3.9.0
	 *
	 * @global array $wp_registered_widgets
	 */
	public function to_json() {
		global $wp_registered_widgets;

		parent::to_json();
		$exported_properties = array( 'widget_id', 'widget_id_base', 'sidebar_id', 'width', 'height', 'is_wide' );
		foreach ( $exported_properties as $key ) {
			$this->json[ $key ] = $this->$key;
		}

		// Lấy widget_control và widget_content.
		require_once ABSPATH . 'wp-admin/includes/widgets.php';

		$widget = $wp_registered_widgets[ $this->widget_id ];
		if ( ! isset( $widget['params'][0] ) ) {
			$widget['params'][0] = array();
		}

		$args = array(
			'widget_id'   => $widget['id'],
			'widget_name' => $widget['name'],
		);

		$args                 = wp_list_widget_controls_dynamic_sidebar(
			array(
				0 => $args,
				1 => $widget['params'][0],
			)
		);
		$widget_control_parts = $this->manager->widgets->get_widget_control_parts( $args );

		$this->json['widget_control'] = $widget_control_parts['control'];
		$this->json['widget_content'] = $widget_control_parts['content'];
	}

	/**
	 * Ghi đè render_content để không thực hiện gì vì nội dung được xuất qua to_json để nhúng trì hoãn.
	 *
	 * @since 3.9.0
	 */
	public function render_content() {}

	/**
	 * Kiểm tra xem widget hiện tại có được hiển thị trên trang hay không.
	 *
	 * @since 4.0.0
	 *
	 * @return bool Widget có được hiển thị hay không.
	 */
	public function active_callback() {
		return $this->manager->widgets->is_widget_rendered( $this->widget_id );
	}
}
