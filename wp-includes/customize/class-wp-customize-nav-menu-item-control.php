<?php
/**
 * API Tùy biến: Lớp WP_Customize_Nav_Menu_Item_Control
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
class WP_Customize_Nav_Menu_Item_Control extends WP_Customize_Control {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menu_item';

	/**
	 * Cài đặt mục menu điều hướng.
	 *
	 * @since 4.3.0
	 * @var WP_Customize_Nav_Menu_Item_Setting
	 */
	public $setting;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Đối tượng khởi tạo Trình tùy biến.
	 * @param string               $id      ID của điều khiển.
	 * @param array                $args    Tùy chọn. Các tham số để ghi đè giá trị mặc định của thuộc tính lớp.
	 *                                      Xem WP_Customize_Control::__construct() để biết thông tin
	 *                                      về các tham số được chấp nhận. Mặc định là mảng rỗng.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Không hiển thị nội dung điều khiển - được hiển thị bằng mẫu JS.
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
		?>
		<div class="menu-item-bar">
			<div class="menu-item-handle">
				<span class="item-type" aria-hidden="true">{{ data.item_type_label }}</span>
				<span class="item-title" aria-hidden="true">
					<span class="spinner"></span>
					<span class="menu-item-title<# if ( ! data.title && ! data.original_title ) { #> no-title<# } #>">{{ data.title || data.original_title || wp.customize.Menus.data.l10n.untitled }}</span>
					<# if ( 0 === data.depth ) { #>
						<span class="is-submenu" style="display: none;"><?php _e( 'sub item' ); ?></span>
					<# } else { #>
						<span class="is-submenu"><?php _e( 'sub item' ); ?></span>
					<# } #>
				</span>
				<span class="item-controls">
					<button type="button" class="button-link item-edit" aria-expanded="false"><span class="screen-reader-text">
					<# if ( 0 === data.depth ) { #>
						<?php
						/* translators: 1: Tiêu đề mục menu, 2: Loại mục menu. 3: Chỉ số mục, 4: Tổng số mục. */
						printf( __( 'Edit %1$s (%2$s, %3$d of %4$d)' ), '{{ data.title || data.original_title || wp.customize.Menus.data.l10n.untitled }}', '{{ data.item_type_label }}', '', '' );
						?>
					<# } else if ( 1 === data.depth ) { #>
						<?php
							/* translators: 1: Tiêu đề mục menu, 2: Loại mục menu, 3: Chỉ số mục, 4: Tổng số mục, 5: Mục cha. */
							printf( __( 'Edit %1$s (%2$s, sub-item %3$d of %4$d under %5$s)' ), '{{ data.title || data.original_title || wp.customize.Menus.data.l10n.untitled }}', '{{ data.item_type_label }}', '', '', '' );
						?>
					<# } else { #>
						<?php
							/* translators: 1: Tiêu đề mục menu, 2: Loại mục menu, 3: Chỉ số mục, 4: Tổng số mục, 5: Mục cha, 6: Độ sâu mục. */
							printf( __( 'Edit %1$s (%2$s, sub-item %3$d of %4$d under %5$s, level %6$s)' ), '{{ data.title || data.original_title || wp.customize.Menus.data.l10n.untitled }}', '{{ data.item_type_label }}', '', '', '', '{{data.depth}}' );
						?>
					<# } #>
					</span><span class="toggle-indicator" aria-hidden="true"></span></button>
					<button type="button" class="button-link item-delete submitdelete deletion"><span class="screen-reader-text">
					<?php
						/* translators: 1: Tiêu đề mục menu, 2: Loại mục menu. */
						printf( __( 'Remove Menu Item: %1$s (%2$s)' ), '{{ data.title || data.original_title || wp.customize.Menus.data.l10n.untitled }}', '{{ data.item_type_label }}' );
					?>
					</span></button>
				</span>
			</div>
		</div>

		<div class="menu-item-settings" id="menu-item-settings-{{ data.menu_item_id }}">
			<# if ( 'custom' === data.item_type ) { #>
			<p class="field-url description description-thin">
				<label for="edit-menu-item-url-{{ data.menu_item_id }}">
					<?php _e( 'URL' ); ?><br />
					<input class="widefat code edit-menu-item-url" type="text" id="edit-menu-item-url-{{ data.menu_item_id }}" name="menu-item-url" />
				</label>
			</p>
		<# } #>
			<p class="description description-thin">
				<label for="edit-menu-item-title-{{ data.menu_item_id }}">
					<?php _e( 'Navigation Label' ); ?><br />
					<input type="text" id="edit-menu-item-title-{{ data.menu_item_id }}" placeholder="{{ data.original_title }}" class="widefat edit-menu-item-title" name="menu-item-title" />
				</label>
			</p>
			<p class="field-link-target description description-thin">
				<label for="edit-menu-item-target-{{ data.menu_item_id }}">
					<input type="checkbox" id="edit-menu-item-target-{{ data.menu_item_id }}" class="edit-menu-item-target" value="_blank" name="menu-item-target" />
					<?php _e( 'Open link in a new tab' ); ?>
				</label>
			</p>
			<p class="field-title-attribute field-attr-title description description-thin">
				<label for="edit-menu-item-attr-title-{{ data.menu_item_id }}">
					<?php _e( 'Title Attribute' ); ?><br />
					<input type="text" id="edit-menu-item-attr-title-{{ data.menu_item_id }}" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title" />
				</label>
			</p>
			<p class="field-css-classes description description-thin">
				<label for="edit-menu-item-classes-{{ data.menu_item_id }}">
					<?php _e( 'CSS Classes' ); ?><br />
					<input type="text" id="edit-menu-item-classes-{{ data.menu_item_id }}" class="widefat code edit-menu-item-classes" name="menu-item-classes" />
				</label>
			</p>
			<p class="field-xfn description description-thin">
				<label for="edit-menu-item-xfn-{{ data.menu_item_id }}">
					<?php _e( 'Link Relationship (XFN)' ); ?><br />
					<input type="text" id="edit-menu-item-xfn-{{ data.menu_item_id }}" class="widefat code edit-menu-item-xfn" name="menu-item-xfn" />
				</label>
			</p>
			<p class="field-description description description-thin">
				<label for="edit-menu-item-description-{{ data.menu_item_id }}">
					<?php _e( 'Description' ); ?><br />
					<textarea id="edit-menu-item-description-{{ data.menu_item_id }}" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description">{{ data.description }}</textarea>
					<span class="description"><?php _e( 'The description will be displayed in the menu if the active theme supports it.' ); ?></span>
				</label>
			</p>

			<?php
			/**
			 * Kích hoạt ở cuối mẫu trường biểu mẫu cho các mục menu điều hướng trong trình tùy biến.
			 *
			 * Các trường bổ sung có thể được hiển thị ở đây và quản lý trong JavaScript.
			 *
			 * @since 5.4.0
			 */
			do_action( 'wp_nav_menu_item_custom_fields_customize_template' );
			?>

			<div class="menu-item-actions description-thin submitbox">
				<# if ( ( 'post_type' === data.item_type || 'taxonomy' === data.item_type ) && '' !== data.original_title ) { #>
				<p class="link-to-original">
					<?php
						/* translators: Tiêu đề gốc của mục menu điều hướng. %s: Tiêu đề gốc. */
						printf( __( 'Original: %s' ), '<a class="original-link" href="{{ data.url }}">{{ data.original_title }}</a>' );
					?>
				</p>
				<# } #>

				<button type="button" class="button-link button-link-delete item-delete submitdelete deletion"><?php _e( 'Remove' ); ?></button>
				<span class="spinner"></span>
			</div>
			<input type="hidden" name="menu-item-db-id[{{ data.menu_item_id }}]" class="menu-item-data-db-id" value="{{ data.menu_item_id }}" />
			<input type="hidden" name="menu-item-parent-id[{{ data.menu_item_id }}]" class="menu-item-data-parent-id" value="{{ data.parent }}" />
		</div><!-- .menu-item-settings-->
		<ul class="menu-item-transport"></ul>
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
		$exported                 = parent::json();
		$exported['menu_item_id'] = $this->setting->post_id;

		return $exported;
	}
}
