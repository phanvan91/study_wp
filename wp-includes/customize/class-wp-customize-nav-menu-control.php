<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menu_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Điều khiển Menu Điều hướng trong Tùy biến.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Nav_Menu_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menu';

	/**
	 * Không hiển thị nội dung điều khiển - sử dụng mẫu JS thay thế.
	 *
	 * @since 4.3.0
	 */
	public function render_content() {}

	/**
	 * Mẫu JS/Underscore cho giao diện điều khiển.
	 *
	 * @since 4.3.0
	 */
	public function content_template() {
		$add_items = __( 'Add Items' );
		?>
		<p class="new-menu-item-invitation">
			<?php
			printf(
				/* translators: %s: Văn bản nút "Add Items". */
				__( 'Time to add some links! Click &#8220;%s&#8221; to start putting pages, categories, and custom links in your menu. Add as many things as you would like.' ),
				$add_items
			);
			?>
		</p>
		<div class="customize-control-nav_menu-buttons">
			<button type="button" class="button add-new-menu-item" aria-label="<?php esc_attr_e( 'Add or remove menu items' ); ?>" aria-expanded="false" aria-controls="available-menu-items">
				<?php echo $add_items; ?>
			</button>
			<button type="button" class="button-link reorder-toggle" aria-label="<?php esc_attr_e( 'Reorder menu items' ); ?>" aria-describedby="reorder-items-desc-{{ data.menu_id }}">
				<span class="reorder"><?php _e( 'Reorder' ); ?></span>
				<span class="reorder-done"><?php _e( 'Done' ); ?></span>
			</button>
		</div>
		<p class="screen-reader-text" id="reorder-items-desc-{{ data.menu_id }}">
			<?php
			/* translators: Văn bản trợ năng ẩn. */
			_e( 'When in reorder mode, additional controls to reorder menu items will be available in the items list above.' );
			?>
		</p>
		<?php
	}

	/**
	 * Trả về các tham số cho điều khiển này.
	 *
	 * @since 4.3.0
	 *
	 * @return array Các tham số đã được xuất.
	 */
	public function json() {
		$exported            = parent::json();
		$exported['menu_id'] = $this->setting->term_id;

		return $exported;
	}
}
