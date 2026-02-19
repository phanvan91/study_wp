<?php
/**
 * API Tùy biến: Lớp WP_Customize_Themes_Panel
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.9.0
 */

/**
 * Lớp Bảng điều khiển Giao diện trong Tùy biến
 *
 * @since 4.9.0
 *
 * @see WP_Customize_Panel
 */
class WP_Customize_Themes_Panel extends WP_Customize_Panel {

	/**
	 * Loại bảng điều khiển.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $type = 'themes';

	/**
	 * Mẫu Underscore (JS) để hiển thị vùng chứa của bảng điều khiển này.
	 *
	 * Bảng điều khiển giao diện hiển thị tiêu đề tùy chỉnh với giao diện đang hoạt động và nút chuyển đổi giao diện.
	 *
	 * @see WP_Customize_Panel::print_template()
	 *
	 * @since 4.9.0
	 */
	protected function render_template() {
		?>
		<li id="accordion-section-{{ data.id }}" class="accordion-section control-panel-themes">
			<h3 class="accordion-section-title">
				<?php
				if ( $this->manager->is_theme_active() ) {
					echo '<span class="customize-action">' . __( 'Active theme' ) . '</span> {{ data.title }}';
				} else {
					echo '<span class="customize-action">' . __( 'Previewing theme' ) . '</span> {{ data.title }}';
				}
				?>
				<?php if ( current_user_can( 'switch_themes' ) ) : ?>
					<button type="button" class="button change-theme" aria-label="<?php esc_attr_e( 'Change theme' ); ?>"><?php _ex( 'Change', 'theme' ); ?></button>
				<?php endif; ?>
			</h3>
			<ul class="accordion-sub-container control-panel-content"></ul>
		</li>
		<?php
	}

	/**
	 * Mẫu Underscore (JS) cho nội dung của bảng điều khiển này (nhưng không phải vùng chứa).
	 *
	 * Các biến lớp cho lớp bảng điều khiển này có sẵn trong đối tượng JS `data`;
	 * xuất các biến tùy chỉnh bằng cách ghi đè WP_Customize_Panel::json().
	 *
	 * @since 4.9.0
	 *
	 * @see WP_Customize_Panel::print_template()
	 */
	protected function content_template() {
		?>
		<li class="panel-meta customize-info accordion-section <# if ( ! data.description ) { #> cannot-expand<# } #>">
			<button class="customize-panel-back" tabindex="-1" type="button"><span class="screen-reader-text">
				<?php
				/* translators: Văn bản trợ năng ẩn. */
				_e( 'Back' );
				?>
			</span></button>
			<div class="accordion-section-title">
				<span class="preview-notice">
					<?php
					printf(
						/* translators: %s: Tiêu đề bảng điều khiển Giao diện trong Trình tùy biến. */
						__( 'You are browsing %s' ),
						'<strong class="panel-title">' . __( 'Themes' ) . '</strong>'
					); // Tách chuỗi riêng để nhất quán với các bảng điều khiển khác.
					?>
				</span>
				<?php if ( current_user_can( 'install_themes' ) && ! is_multisite() ) : ?>
					<# if ( data.description ) { #>
						<button class="customize-help-toggle dashicons dashicons-editor-help" type="button" aria-expanded="false"><span class="screen-reader-text">
							<?php
							/* translators: Văn bản trợ năng ẩn. */
							_e( 'Help' );
							?>
						</span></button>
					<# } #>
				<?php endif; ?>
			</div>
			<?php if ( current_user_can( 'install_themes' ) && ! is_multisite() ) : ?>
				<# if ( data.description ) { #>
					<div class="description customize-panel-description">
						{{{ data.description }}}
					</div>
				<# } #>
			<?php endif; ?>

			<div class="customize-control-notifications-container"></div>
		</li>
		<li class="customize-themes-full-container-container">
			<div class="customize-themes-full-container">
				<div class="customize-themes-notifications"></div>
			</div>
		</li>
		<?php
	}
}
