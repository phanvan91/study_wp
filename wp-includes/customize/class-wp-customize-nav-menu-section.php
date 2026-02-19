<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menu_Section
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Phần Menu Tùy biến
 *
 * Phần tùy chỉnh chỉ cần thiết trong JS.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Section
 */
class WP_Customize_Nav_Menu_Section extends WP_Customize_Section {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menu';

	/**
	 * Lấy các tham số phần cho JS.
	 *
	 * @since 4.3.0
	 * @return array Các tham số đã được xuất.
	 */
	public function json() {
		$exported            = parent::json();
		$exported['menu_id'] = (int) preg_replace( '/^nav_menu\[(-?\d+)\]/', '$1', $this->id );

		return $exported;
	}
}
