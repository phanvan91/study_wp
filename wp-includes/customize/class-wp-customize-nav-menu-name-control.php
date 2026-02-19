<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menu_Name_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Điều khiển tùy biến đại diện cho trường tên của một menu nhất định.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Nav_Menu_Name_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển, được sử dụng bởi JS.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menu_name';

	/**
	 * Không thực hiện gì vì chúng ta đang sử dụng mẫu JS.
	 *
	 * @since 4.3.0
	 */
	protected function render_content() {}

	/**
	 * Hiển thị mẫu Underscore cho điều khiển này.
	 *
	 * @since 4.3.0
	 */
	protected function content_template() {
		?>
		<label>
			<# if ( data.label ) { #>
				<span class="customize-control-title">{{ data.label }}</span>
			<# } #>
			<input type="text" class="menu-name-field live-update-section-title"
				<# if ( data.description ) { #>
					aria-describedby="{{ data.section }}-description"
				<# } #>
				/>
		</label>
		<# if ( data.description ) { #>
			<p id="{{ data.section }}-description">{{ data.description }}</p>
		<# } #>
		<?php
	}
}
