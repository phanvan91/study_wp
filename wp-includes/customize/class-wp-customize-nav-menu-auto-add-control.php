<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menu_Auto_Add_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Điều khiển tùy biến đại diện cho trường auto_add của một menu nhất định.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Nav_Menu_Auto_Add_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển, được sử dụng bởi JS.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menu_auto_add';

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
		<# var elementId = _.uniqueId( 'customize-nav-menu-auto-add-control-' ); #>
		<span class="customize-control-title"><?php _e( 'Menu Options' ); ?></span>
		<span class="customize-inside-control-row">
			<input id="{{ elementId }}" type="checkbox" class="auto_add" />
			<label for="{{ elementId }}">
				<?php _e( 'Automatically add new top-level pages to this menu' ); ?>
			</label>
		</span>
		<?php
	}
}
