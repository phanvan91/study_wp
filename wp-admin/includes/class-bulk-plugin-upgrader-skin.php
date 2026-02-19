<?php
/**
 * API Nâng cấp: Lớp Bulk_Plugin_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện nâng cấp hàng loạt plugin cho việc nâng cấp plugin WordPress.
 *
 * @since 3.0.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see Bulk_Upgrader_Skin
 */
class Bulk_Plugin_Upgrader_Skin extends Bulk_Upgrader_Skin {

	/**
	 * Thông tin plugin.
	 *
	 * Phương thức Plugin_Upgrader::bulk_upgrade() sẽ điền thông tin này
	 * với dữ liệu lấy từ hàm get_plugin_data().
	 *
	 * @since 3.0.0
	 * @var array Dữ liệu plugin. Các giá trị sẽ rỗng nếu plugin không cung cấp.
	 */
	public $plugin_info = array();

	/**
	 * Thiết lập các chuỗi sử dụng trong quá trình cập nhật.
	 *
	 * @since 3.0.0
	 */
	public function add_strings() {
		parent::add_strings();
		/* translators: 1: Plugin name, 2: Number of the plugin, 3: Total number of plugins being updated. */
		$this->upgrader->strings['skin_before_update_header'] = __( 'Updating Plugin %1$s (%2$d/%3$d)' );
	}

	/**
	 * Thực hiện hành động trước khi cập nhật hàng loạt plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title
	 */
	public function before( $title = '' ) {
		parent::before( $this->plugin_info['Title'] );
	}

	/**
	 * Thực hiện hành động sau khi cập nhật hàng loạt plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title
	 */
	public function after( $title = '' ) {
		parent::after( $this->plugin_info['Title'] );
		$this->decrement_update_count( 'plugin' );
	}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 */
	public function bulk_footer() {
		parent::bulk_footer();

		$update_actions = array(
			'plugins_page' => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'plugins.php' ),
				__( 'Go to Plugins page' )
			),
			'updates_page' => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'update-core.php' ),
				__( 'Go to WordPress Updates page' )
			),
		);

		if ( ! current_user_can( 'activate_plugins' ) ) {
			unset( $update_actions['plugins_page'] );
		}

		/**
		 * Lọc danh sách liên kết hành động có sẵn sau khi cập nhật hàng loạt plugin.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $update_actions Mảng các liên kết hành động plugin.
		 * @param array    $plugin_info    Mảng thông tin cho plugin được cập nhật cuối cùng.
		 */
		$update_actions = apply_filters( 'update_bulk_plugins_complete_actions', $update_actions, $this->plugin_info );

		if ( ! empty( $update_actions ) ) {
			$this->feedback( implode( ' | ', (array) $update_actions ) );
		}
	}
}
