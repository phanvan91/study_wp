<?php
/**
 * API Nâng cấp: Lớp Plugin_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện nâng cấp plugin cho việc nâng cấp plugin WordPress.
 *
 * @since 2.8.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see WP_Upgrader_Skin
 */
class Plugin_Upgrader_Skin extends WP_Upgrader_Skin {

	/**
	 * Lưu trữ slug plugin trong Thư mục Plugin.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $plugin = '';

	/**
	 * Plugin có đang hoạt động hay không.
	 *
	 * @since 2.8.0
	 *
	 * @var bool
	 */
	public $plugin_active = false;

	/**
	 * Plugin có đang hoạt động cho toàn bộ mạng hay không.
	 *
	 * @since 2.8.0
	 *
	 * @var bool
	 */
	public $plugin_network_active = false;

	/**
	 * Hàm khởi tạo.
	 *
	 * Thiết lập giao diện nâng cấp plugin.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args Tùy chọn. Các tham số giao diện nâng cấp plugin
	 *                    để ghi đè các tùy chọn mặc định. Mặc định mảng rỗng.
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'url'    => '',
			'plugin' => '',
			'nonce'  => '',
			'title'  => __( 'Update Plugin' ),
		);
		$args     = wp_parse_args( $args, $defaults );

		$this->plugin = $args['plugin'];

		$this->plugin_active         = is_plugin_active( $this->plugin );
		$this->plugin_network_active = is_plugin_active_for_network( $this->plugin );

		parent::__construct( $args );
	}

	/**
	 * Thực hiện hành động sau khi cập nhật một plugin.
	 *
	 * @since 2.8.0
	 */
	public function after() {
		$this->plugin = $this->upgrader->plugin_info();
		if ( ! empty( $this->plugin ) && ! is_wp_error( $this->result ) && $this->plugin_active ) {
			// Hiện chỉ được sử dụng khi JS bị tắt cho cập nhật một plugin?
			printf(
				'<iframe title="%s" style="border:0;overflow:hidden" width="100%%" height="170" src="%s"></iframe>',
				esc_attr__( 'Update progress' ),
				wp_nonce_url( 'update.php?action=activate-plugin&networkwide=' . $this->plugin_network_active . '&plugin=' . urlencode( $this->plugin ), 'activate-plugin_' . $this->plugin )
			);
		}

		$this->decrement_update_count( 'plugin' );

		$update_actions = array(
			'activate_plugin' => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $this->plugin ), 'activate-plugin_' . $this->plugin ),
				__( 'Activate Plugin' )
			),
			'plugins_page'    => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'plugins.php' ),
				__( 'Go to Plugins page' )
			),
		);

		if ( $this->plugin_active || ! $this->result || is_wp_error( $this->result ) || ! current_user_can( 'activate_plugin', $this->plugin ) ) {
			unset( $update_actions['activate_plugin'] );
		}

		/**
		 * Lọc danh sách liên kết hành động có sẵn sau khi cập nhật một plugin.
		 *
		 * @since 2.7.0
		 *
		 * @param string[] $update_actions Mảng các liên kết hành động plugin.
		 * @param string   $plugin         Đường dẫn tới tệp plugin tương đối so với thư mục plugin.
		 */
		$update_actions = apply_filters( 'update_plugin_complete_actions', $update_actions, $this->plugin );

		if ( ! empty( $update_actions ) ) {
			$this->feedback( implode( ' | ', (array) $update_actions ) );
		}
	}
}
