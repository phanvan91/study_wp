<?php
/**
 * API Nâng cấp: Lớp Bulk_Plugin_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện nâng cấp hàng loạt giao diện cho việc nâng cấp giao diện WordPress.
 *
 * @since 3.0.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see Bulk_Upgrader_Skin
 */
class Bulk_Theme_Upgrader_Skin extends Bulk_Upgrader_Skin {

	/**
	 * Thông tin giao diện.
	 *
	 * Phương thức Theme_Upgrader::bulk_upgrade() sẽ điền thông tin này
	 * với dữ liệu lấy từ phương thức Theme_Upgrader::theme_info(),
	 * mà lần lượt gọi hàm wp_get_theme().
	 *
	 * @since 3.0.0
	 * @var WP_Theme|false Đối tượng thông tin giao diện, hoặc false.
	 */
	public $theme_info = false;

	/**
	 * Thiết lập các chuỗi sử dụng trong quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function add_strings() {
		parent::add_strings();
		/* translators: 1: Theme name, 2: Number of the theme, 3: Total number of themes being updated. */
		$this->upgrader->strings['skin_before_update_header'] = __( 'Updating Theme %1$s (%2$d/%3$d)' );
	}

	/**
	 * Thực hiện hành động trước khi cập nhật hàng loạt giao diện.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title
	 */
	public function before( $title = '' ) {
		parent::before( $this->theme_info->display( 'Name' ) );
	}

	/**
	 * Thực hiện hành động sau khi cập nhật hàng loạt giao diện.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title
	 */
	public function after( $title = '' ) {
		parent::after( $this->theme_info->display( 'Name' ) );
		$this->decrement_update_count( 'theme' );
	}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 */
	public function bulk_footer() {
		parent::bulk_footer();

		$update_actions = array(
			'themes_page'  => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'themes.php' ),
				__( 'Go to Themes page' )
			),
			'updates_page' => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'update-core.php' ),
				__( 'Go to WordPress Updates page' )
			),
		);

		if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) ) {
			unset( $update_actions['themes_page'] );
		}

		/**
		 * Lọc danh sách liên kết hành động có sẵn sau khi cập nhật hàng loạt giao diện.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $update_actions Mảng các liên kết hành động giao diện.
		 * @param WP_Theme $theme_info     Đối tượng giao diện cho giao diện được cập nhật cuối cùng.
		 */
		$update_actions = apply_filters( 'update_bulk_theme_complete_actions', $update_actions, $this->theme_info );

		if ( ! empty( $update_actions ) ) {
			$this->feedback( implode( ' | ', (array) $update_actions ) );
		}
	}
}
