<?php
/**
 * API Tùy biến: Lớp WP_Customize_Sidebar_Section
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Phần tùy biến đại diện cho vùng widget (thanh bên).
 *
 * @since 4.1.0
 *
 * @see WP_Customize_Section
 */
class WP_Customize_Sidebar_Section extends WP_Customize_Section {

	/**
	 * Loại của phần này.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $type = 'sidebar';

	/**
	 * Định danh duy nhất.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $sidebar_id;

	/**
	 * Thu thập các tham số được truyền tới JavaScript phía client qua JSON.
	 *
	 * @since 4.1.0
	 *
	 * @return array Mảng được xuất tới client dưới dạng JSON.
	 */
	public function json() {
		$json              = parent::json();
		$json['sidebarId'] = $this->sidebar_id;
		return $json;
	}

	/**
	 * Kiểm tra xem thanh bên hiện tại có được hiển thị trên trang hay không.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Thanh bên có được hiển thị hay không.
	 */
	public function active_callback() {
		return $this->manager->widgets->is_sidebar_rendered( $this->sidebar_id );
	}
}
