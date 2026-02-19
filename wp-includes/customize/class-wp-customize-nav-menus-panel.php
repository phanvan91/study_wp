<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menus_Panel
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Bảng điều khiển Menu Điều hướng trong Tùy biến
 *
 * Cần thiết để thêm các tùy chọn màn hình.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Panel
 */
class WP_Customize_Nav_Menus_Panel extends WP_Customize_Panel {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menus';

	/**
	 * Hiển thị các tùy chọn màn hình cho Menu.
	 *
	 * @since 4.3.0
	 */
	public function render_screen_options() {
		// Thêm các tùy chọn màn hình.
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		add_filter( 'manage_nav-menus_columns', 'wp_nav_menu_manage_columns' );

		// Hiển thị tùy chọn màn hình.
		$screen = WP_Screen::get( 'nav-menus.php' );
		$screen->render_screen_options( array( 'wrap' => false ) );
	}

	/**
	 * Trả về các tùy chọn nâng cao cho trang menu điều hướng.
	 *
	 * Thuộc tính tiêu đề liên kết được thêm vào vì đây là khái niệm tương đối nâng cao cho người dùng mới.
	 *
	 * @since 4.3.0
	 * @deprecated 4.5.0 Không còn được sử dụng, thay thế bằng wp_nav_menu_manage_columns().
	 */
	public function wp_nav_menu_manage_columns() {
		_deprecated_function( __METHOD__, '4.5.0', 'wp_nav_menu_manage_columns' );
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		return wp_nav_menu_manage_columns();
	}

	/**
	 * Mẫu Underscore (JS) cho nội dung của bảng điều khiển này (nhưng không phải vùng chứa).
	 *
	 * Các biến lớp cho lớp bảng điều khiển này có sẵn trong đối tượng JS `data`;
	 * xuất các biến tùy chỉnh bằng cách ghi đè WP_Customize_Panel::json().
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Panel::print_template()
	 */
	protected function content_template() {
		?>
		<li class="panel-meta customize-info accordion-section <# if ( ! data.description ) { #> cannot-expand<# } #>">
			<button type="button" class="customize-panel-back" tabindex="-1">
				<span class="screen-reader-text">
					<?php
					/* translators: Văn bản trợ năng ẩn. */
					_e( 'Back' );
					?>
				</span>
			</button>
			<div class="accordion-section-title">
				<span class="preview-notice">
					<?php
					/* translators: %s: Tiêu đề trang/bảng điều khiển trong Trình tùy biến. */
					printf( __( 'You are customizing %s' ), '<strong class="panel-title">{{ data.title }}</strong>' );
					?>
				</span>
				<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false">
					<span class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						_e( 'Help' );
						?>
					</span>
				</button>
				<button type="button" class="customize-screen-options-toggle" aria-expanded="false">
					<span class="screen-reader-text">
						<?php
						/* translators: Văn bản trợ năng ẩn. */
						_e( 'Menu Options' );
						?>
					</span>
				</button>
			</div>
			<# if ( data.description ) { #>
			<div class="description customize-panel-description">{{{ data.description }}}</div>
			<# } #>
			<div id="screen-options-wrap">
				<?php $this->render_screen_options(); ?>
			</div>
		</li>
		<?php
		// LƯU Ý: Phần sau là cách giải quyết thay thế cho việc không thể xử lý (và do đó gán nhãn) một danh sách các phần như một tổng thể.
		?>
		<li class="customize-control-title customize-section-title-nav_menus-heading"><?php _e( 'Menus' ); ?></li>
		<?php
	}
}
