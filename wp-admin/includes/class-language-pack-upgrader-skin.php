<?php
/**
 * API Nâng cấp: Lớp Language_Pack_Upgrader_Skin
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Giao diện nâng cấp bản dịch cho việc nâng cấp bản dịch WordPress.
 *
 * @since 3.7.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see WP_Upgrader_Skin
 */
class Language_Pack_Upgrader_Skin extends WP_Upgrader_Skin {
	public $language_update        = null;
	public $done_header            = false;
	public $done_footer            = false;
	public $display_footer_actions = true;

	/**
	 * Hàm khởi tạo.
	 *
	 * Thiết lập giao diện nâng cấp gói ngôn ngữ.
	 *
	 * @since 3.7.0
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'url'                => '',
			'nonce'              => '',
			'title'              => __( 'Update Translations' ),
			'skip_header_footer' => false,
		);
		$args     = wp_parse_args( $args, $defaults );
		if ( $args['skip_header_footer'] ) {
			$this->done_header            = true;
			$this->done_footer            = true;
			$this->display_footer_actions = false;
		}
		parent::__construct( $args );
	}

	/**
	 * Thực hiện hành động trước khi cập nhật gói ngôn ngữ.
	 *
	 * @since 3.7.0
	 */
	public function before() {
		$name = $this->upgrader->get_name_for_update( $this->language_update );

		echo '<div class="update-messages lp-show-latest">';

		/* translators: 1: Project name (plugin, theme, or WordPress), 2: Language. */
		printf( '<h2>' . __( 'Updating translations for %1$s (%2$s)&#8230;' ) . '</h2>', $name, $this->language_update->language );
	}

	/**
	 * Hiển thị thông báo lỗi về việc cập nhật.
	 *
	 * @since 3.7.0
	 * @since 5.9.0 Đổi tên `$error` thành `$errors` để hỗ trợ tham số đặt tên PHP 8.
	 *
	 * @param string|WP_Error $errors Các lỗi.
	 */
	public function error( $errors ) {
		echo '<div class="lp-error">';
		parent::error( $errors );
		echo '</div>';
	}

	/**
	 * Thực hiện hành động sau khi cập nhật gói ngôn ngữ.
	 *
	 * @since 3.7.0
	 */
	public function after() {
		echo '</div>';
	}

	/**
	 * Hiển thị phần chân trang sau quá trình cập nhật hàng loạt.
	 *
	 * @since 3.7.0
	 */
	public function bulk_footer() {
		$this->decrement_update_count( 'translation' );

		$update_actions = array(
			'updates_page' => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'update-core.php' ),
				__( 'Go to WordPress Updates page' )
			),
		);

		/**
		 * Lọc danh sách liên kết hành động có sẵn sau khi cập nhật bản dịch.
		 *
		 * @since 3.7.0
		 *
		 * @param string[] $update_actions Mảng các liên kết cập nhật bản dịch.
		 */
		$update_actions = apply_filters( 'update_translations_complete_actions', $update_actions );

		if ( $update_actions && $this->display_footer_actions ) {
			$this->feedback( implode( ' | ', $update_actions ) );
		}
	}
}
