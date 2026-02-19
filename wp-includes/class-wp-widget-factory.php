<?php
/**
 * API Widget: Lớp WP_Widget_Factory
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.4.0
 */

/**
 * Singleton đăng ký và khởi tạo các lớp WP_Widget.
 *
 * @since 2.8.0
 * @since 4.4.0 Chuyển sang file riêng từ wp-includes/widgets.php
 */
#[AllowDynamicProperties]
class WP_Widget_Factory {

	/**
	 * Mảng các widget.
	 *
	 * @since 2.8.0
	 * @var array
	 */
	public $widgets = array();

	/**
	 * Hàm khởi tạo PHP5.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		add_action( 'widgets_init', array( $this, '_register_widgets' ), 100 );
	}

	/**
	 * Hàm khởi tạo PHP4.
	 *
	 * @since 2.8.0
	 * @deprecated 4.3.0 Sử dụng __construct() thay thế.
	 *
	 * @see WP_Widget_Factory::__construct()
	 */
	public function WP_Widget_Factory() {
		_deprecated_constructor( 'WP_Widget_Factory', '4.3.0' );
		self::__construct();
	}

	/**
	 * Đăng ký một lớp con widget.
	 *
	 * @since 2.8.0
	 * @since 4.6.0 Cập nhật tham số `$widget` để cũng chấp nhận đối tượng thể hiện WP_Widget
	 *              thay vì chỉ tên lớp con `WP_Widget`.
	 *
	 * @param string|WP_Widget $widget Tên lớp con `WP_Widget` hoặc một thể hiện của lớp con `WP_Widget`.
	 */
	public function register( $widget ) {
		if ( $widget instanceof WP_Widget ) {
			$this->widgets[ spl_object_hash( $widget ) ] = $widget;
		} else {
			$this->widgets[ $widget ] = new $widget();
		}
	}

	/**
	 * Hủy đăng ký một lớp con widget.
	 *
	 * @since 2.8.0
	 * @since 4.6.0 Cập nhật tham số `$widget` để cũng chấp nhận đối tượng thể hiện WP_Widget
	 *              thay vì chỉ tên lớp con `WP_Widget`.
	 *
	 * @param string|WP_Widget $widget Tên lớp con `WP_Widget` hoặc một thể hiện của lớp con `WP_Widget`.
	 */
	public function unregister( $widget ) {
		if ( $widget instanceof WP_Widget ) {
			unset( $this->widgets[ spl_object_hash( $widget ) ] );
		} else {
			unset( $this->widgets[ $widget ] );
		}
	}

	/**
	 * Phương thức tiện ích để thêm widget vào biến toàn cục widget đã đăng ký.
	 *
	 * @since 2.8.0
	 *
	 * @global array $wp_registered_widgets
	 */
	public function _register_widgets() {
		global $wp_registered_widgets;
		$keys       = array_keys( $this->widgets );
		$registered = array_keys( $wp_registered_widgets );
		$registered = array_map( '_get_widget_id_base', $registered );

		foreach ( $keys as $key ) {
			// Không đăng ký widget mới nếu widget cũ có cùng id đã được đăng ký.
			if ( in_array( $this->widgets[ $key ]->id_base, $registered, true ) ) {
				unset( $this->widgets[ $key ] );
				continue;
			}

			$this->widgets[ $key ]->_register();
		}
	}

	/**
	 * Trả về đối tượng WP_Widget đã đăng ký cho loại widget đã cho.
	 *
	 * @since 5.8.0
	 *
	 * @param string $id_base ID loại widget.
	 * @return WP_Widget|null
	 */
	public function get_widget_object( $id_base ) {
		$key = $this->get_widget_key( $id_base );
		if ( '' === $key ) {
			return null;
		}

		return $this->widgets[ $key ];
	}

	/**
	 * Trả về khóa đã đăng ký cho loại widget đã cho.
	 *
	 * @since 5.8.0
	 *
	 * @param string $id_base ID loại widget.
	 * @return string
	 */
	public function get_widget_key( $id_base ) {
		foreach ( $this->widgets as $key => $widget_object ) {
			if ( $widget_object->id_base === $id_base ) {
				return $key;
			}
		}

		return '';
	}
}
