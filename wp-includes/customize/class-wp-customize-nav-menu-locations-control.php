<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menu_Locations_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.9.0
 */

/**
 * Lớp Điều khiển Vị trí Menu Điều hướng trong Tùy biến.
 *
 * @since 4.9.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Nav_Menu_Locations_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $type = 'nav_menu_locations';

	/**
	 * Không hiển thị nội dung điều khiển - sử dụng mẫu JS thay thế.
	 *
	 * @since 4.9.0
	 */
	public function render_content() {}

	/**
	 * Mẫu JS/Underscore cho giao diện điều khiển.
	 *
	 * @since 4.9.0
	 */
	public function content_template() {
		if ( current_theme_supports( 'menus' ) ) :
			?>
			<# var elementId; #>
			<ul class="menu-location-settings">
				<li class="customize-control assigned-menu-locations-title">
					<span class="customize-control-title">{{ wp.customize.Menus.data.l10n.locationsTitle }}</span>
					<# if ( data.isCreating ) { #>
						<p>
							<?php echo _x( 'Where do you want this menu to appear?', 'menu locations' ); ?>
							<?php
							printf(
								/* translators: 1: URL tài liệu, 2: Thuộc tính liên kết bổ sung, 3: Văn bản trợ năng. */
								_x( '(If you plan to use a menu <a href="%1$s" %2$s>widget%3$s</a>, skip this step.)', 'menu locations' ),
								__( 'https://wordpress.org/documentation/article/manage-wordpress-widgets/' ),
								' class="external-link" target="_blank"',
								sprintf(
									'<span class="screen-reader-text"> %s</span>',
									/* translators: Văn bản trợ năng ẩn. */
									__( '(opens in a new tab)' )
								)
							);
							?>
						</p>
					<# } else { #>
						<p><?php echo _x( 'Here&#8217;s where this menu appears. If you would like to change that, pick another location.', 'menu locations' ); ?></p>
					<# } #>
				</li>

				<?php foreach ( get_registered_nav_menus() as $location => $description ) : ?>
					<# elementId = _.uniqueId( 'customize-nav-menu-control-location-' ); #>
					<li class="customize-control customize-control-checkbox assigned-menu-location">
						<span class="customize-inside-control-row">
							<input id="{{ elementId }}" type="checkbox" data-menu-id="{{ data.menu_id }}" data-location-id="<?php echo esc_attr( $location ); ?>" class="menu-location" />
							<label for="{{ elementId }}">
								<?php echo $description; ?>
								<span class="theme-location-set">
									<?php
									printf(
										/* translators: %s: Tên menu. */
										_x( '(Current: %s)', 'menu location' ),
										'<span class="current-menu-location-name-' . esc_attr( $location ) . '"></span>'
									);
									?>
								</span>
							</label>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
		endif;
	}
}
