<?php
/**
 * Trình chỉnh sửa widget dựa trên khối, được sử dụng trong widgets.php.
 *
 * @package WordPress
 * @subpackage Administration
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Đánh dấu rằng chúng ta đang tải trình chỉnh sửa khối.
$current_screen = get_current_screen();
$current_screen->is_block_editor( true );

$block_editor_context = new WP_Block_Editor_Context( array( 'name' => 'core/edit-widgets' ) );

$preload_paths = array(
	array( rest_get_route_for_post_type_items( 'attachment' ), 'OPTIONS' ),
	'/wp/v2/widget-types?context=edit&per_page=-1',
	'/wp/v2/sidebars?context=edit&per_page=-1',
	'/wp/v2/widgets?context=edit&per_page=-1&_embed=about',
);
block_editor_rest_api_preload( $preload_paths, $block_editor_context );

$editor_settings = get_block_editor_settings(
	array_merge( get_legacy_widget_block_editor_settings(), array( 'styles' => get_block_editor_theme_styles() ) ),
	$block_editor_context
);

// Trình chỉnh sửa widget không hỗ trợ Thư mục Khối, nên không tải bất kỳ
// tài nguyên nào của nó. Điều này cũng ngăn 'wp-editor' được đưa vào hàng đợi mà chúng ta
// không thể tải trong màn hình widget vì nhiều script widget phụ thuộc vào `wp.editor`.
remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );

wp_add_inline_script(
	'wp-edit-widgets',
	sprintf(
		'wp.domReady( function() {
			wp.editWidgets.initialize( "widgets-editor", %s );
		} );',
		wp_json_encode( $editor_settings )
	)
);

// Tải trước các lược đồ khối đã đăng ký trên máy chủ.
wp_add_inline_script(
	'wp-blocks',
	'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
);

// Tải trước các nguồn liên kết khối đã đăng ký trên máy chủ.
$registered_sources = get_all_registered_block_bindings_sources();
if ( ! empty( $registered_sources ) ) {
	$filtered_sources = array();
	foreach ( $registered_sources as $source ) {
		$filtered_sources[] = array(
			'name'        => $source->name,
			'label'       => $source->label,
			'usesContext' => $source->uses_context,
		);
	}
	$script = sprintf( 'for ( const source of %s ) { wp.blocks.registerBlockBindingsSource( source ); }', wp_json_encode( $filtered_sources ) );
	wp_add_inline_script(
		'wp-blocks',
		$script
	);
}

wp_add_inline_script(
	'wp-blocks',
	sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $block_editor_context ) ) ),
	'after'
);

wp_enqueue_script( 'wp-edit-widgets' );
wp_enqueue_script( 'admin-widgets' );
wp_enqueue_style( 'wp-edit-widgets' );

/** Hành động này được ghi chú trong wp-admin/edit-form-blocks.php */
do_action( 'enqueue_block_editor_assets' );

/** Hành động này được ghi chú trong wp-admin/widgets-form.php */
do_action( 'sidebar_admin_setup' );

require_once ABSPATH . 'wp-admin/admin-header.php';

/** Hành động này được ghi chú trong wp-admin/widgets-form.php */
do_action( 'widgets_admin_page' );
?>

<div id="widgets-editor" class="blocks-widgets-container">
	<?php // JavaScript bị vô hiệu hóa. ?>
	<div class="wrap hide-if-js widgets-editor-no-js">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
		<?php
		if ( file_exists( WP_PLUGIN_DIR . '/classic-widgets/classic-widgets.php' ) ) {
			// Nếu Classic Widgets đã được cài đặt, cung cấp liên kết để kích hoạt plugin.
			$installed           = true;
			$plugin_activate_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=classic-widgets/classic-widgets.php', 'activate-plugin_classic-widgets/classic-widgets.php' );
			$message             = sprintf(
				/* translators: %s: Link to activate the Classic Widgets plugin. */
				__( 'The block widgets require JavaScript. Please enable JavaScript in your browser settings, or activate the <a href="%s">Classic Widgets plugin</a>.' ),
				esc_url( $plugin_activate_url )
			);
		} else {
			// Nếu Classic Widgets chưa được cài đặt, cung cấp liên kết để cài đặt.
			$installed          = false;
			$plugin_install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=classic-widgets' ), 'install-plugin_classic-widgets' );
			$message            = sprintf(
				/* translators: %s: A link to install the Classic Widgets plugin. */
				__( 'The block widgets require JavaScript. Please enable JavaScript in your browser settings, or install the <a href="%s">Classic Widgets plugin</a>.' ),
				esc_url( $plugin_install_url )
			);
		}
		/**
		 * Lọc thông báo hiển thị trong giao diện widget khối khi JavaScript
		 * không được bật trong trình duyệt.
		 *
		 * @since 6.4.0
		 *
		 * @param string $message   Thông báo đang được hiển thị.
		 * @param bool   $installed Liệu plugin Classic Widget đã được cài đặt hay chưa.
		 */
		$message = apply_filters( 'block_widgets_no_javascript_message', $message, $installed );
		wp_admin_notice(
			$message,
			array(
				'type'               => 'error',
				'additional_classes' => array( 'hide-if-js' ),
			)
		);
		?>
	</div>
</div>

<?php
/** Hành động này được ghi chú trong wp-admin/widgets-form.php */
do_action( 'sidebar_admin_page' );

require_once ABSPATH . 'wp-admin/admin-footer.php';
