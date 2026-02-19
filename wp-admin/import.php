<?php
/**
 * Màn hình quản trị Nhập WordPress
 *
 * @package WordPress
 * @subpackage Administration
 */

define( 'WP_LOAD_IMPORTERS', true );

/** Tải Bootstrap WordPress */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'import' ) ) {
	wp_die( __( 'Sorry, you are not allowed to import content into this site.' ) );
}

// Sử dụng trong thẻ HTML title.
$title = __( 'Import' );

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => '<p>' . __( 'This screen lists links to plugins to import data from blogging/content management platforms. Choose the platform you want to import from, and click Install Now when you are prompted in the popup window. If your platform is not listed, click the link to search the plugin directory for other importer plugins to see if there is one for your platform.' ) . '</p>' .
			'<p>' . __( 'In previous versions of WordPress, all importers were built-in. They have been turned into plugins since most people only use them once or infrequently.' ) . '</p>',
	)
);

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/tools-import-screen/">Documentation on Import</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

if ( current_user_can( 'install_plugins' ) ) {
	// Danh sách các trình nhập phổ biến từ API WordPress.org.
	$popular_importers = wp_get_popular_importers();
} else {
	$popular_importers = array();
}

// Phát hiện và chuyển hướng các trình nhập không hợp lệ như 'movabletype', được đăng ký là 'mt'.
if ( ! empty( $_GET['invalid'] ) && isset( $popular_importers[ $_GET['invalid'] ] ) ) {
	$importer_id = $popular_importers[ $_GET['invalid'] ]['importer-id'];
	if ( $importer_id !== $_GET['invalid'] ) { // Ngăn vòng lặp chuyển hướng.
		wp_redirect( admin_url( 'admin.php?import=' . $importer_id ) );
		exit;
	}
	unset( $importer_id );
}

add_thickbox();
wp_enqueue_script( 'plugin-install' );
wp_enqueue_script( 'updates' );

require_once ABSPATH . 'wp-admin/admin-header.php';
$parent_file = 'tools.php';
?>

<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>
<?php
if ( ! empty( $_GET['invalid'] ) ) :
	$importer_not_installed = '<strong>' . __( 'Error:' ) . '</strong> ' . sprintf(
		/* translators: %s: Slug của trình nhập. */
		__( 'The %s importer is invalid or is not installed.' ),
		'<strong>' . esc_html( $_GET['invalid'] ) . '</strong>'
	);
	wp_admin_notice(
		$importer_not_installed,
		array(
			'additional_classes' => array( 'error' ),
		)
	);
endif;
?>
<p><?php _e( 'If you have posts or comments in another system, WordPress can import those into this site. To get started, choose a system to import from below:' ); ?></p>

<?php
// Các trình nhập đã đăng ký (đã cài đặt). Chúng được lưu trong biến toàn cục $wp_importers.
$importers = get_importers();

// Nếu một trình nhập phổ biến chưa được đăng ký, tạo một đăng ký giả liên kết đến trình cài đặt plugin.
foreach ( $popular_importers as $pop_importer => $pop_data ) {
	if ( isset( $importers[ $pop_importer ] ) ) {
		continue;
	}
	if ( isset( $importers[ $pop_data['importer-id'] ] ) ) {
		continue;
	}

	// Điền mảng các trình nhập đã đăng ký (đã cài đặt) với dữ liệu của các trình nhập phổ biến từ API WordPress.org.
	$importers[ $pop_data['importer-id'] ] = array(
		$pop_data['name'],
		$pop_data['description'],
		'install' => $pop_data['plugin-slug'],
	);
}

if ( empty( $importers ) ) {
	echo '<p>' . __( 'No importers are available.' ) . '</p>'; // TODO: Làm cho hữu ích hơn.
} else {
	uasort( $importers, '_usort_by_first_member' );
	?>
<table class="widefat importers striped">

	<?php
	foreach ( $importers as $importer_id => $data ) {
		$plugin_slug         = '';
		$action              = '';
		$is_plugin_installed = false;

		if ( isset( $data['install'] ) ) {
			$plugin_slug = $data['install'];

			if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
				// Có vẻ như trình nhập đã được cài đặt, nhưng chưa kích hoạt.
				$plugins = get_plugins( '/' . $plugin_slug );
				if ( ! empty( $plugins ) ) {
					$keys        = array_keys( $plugins );
					$plugin_file = $plugin_slug . '/' . $keys[0];
					$url         = wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'activate',
								'plugin' => $plugin_file,
								'from'   => 'import',
							),
							admin_url( 'plugins.php' )
						),
						'activate-plugin_' . $plugin_file
					);
					$action      = sprintf(
						'<a href="%s" aria-label="%s">%s</a>',
						esc_url( $url ),
						/* translators: %s: Tên trình nhập. */
						esc_attr( sprintf( __( 'Run %s' ), $data[0] ) ),
						__( 'Run Importer' )
					);

					$is_plugin_installed = true;
				}
			}

			if ( empty( $action ) ) {
				if ( is_main_site() ) {
					$url    = wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'install-plugin',
								'plugin' => $plugin_slug,
								'from'   => 'import',
							),
							self_admin_url( 'update.php' )
						),
						'install-plugin_' . $plugin_slug
					);
					$action = sprintf(
						'<a href="%1$s" class="install-now" data-slug="%2$s" data-name="%3$s" aria-label="%4$s">%5$s</a>',
						esc_url( $url ),
						esc_attr( $plugin_slug ),
						esc_attr( $data[0] ),
						/* translators: %s: Tên trình nhập. */
						esc_attr( sprintf( _x( 'Install %s now', 'plugin' ), $data[0] ) ),
						_x( 'Install Now', 'plugin' )
					);
				} else {
					$action = sprintf(
						/* translators: %s: URL đến màn hình Nhập trên site chính. */
						__( 'This importer is not installed. Please install importers from <a href="%s">the main site</a>.' ),
						get_admin_url( get_current_network_id(), 'import.php' )
					);
				}
			}
		} else {
			$url    = add_query_arg(
				array(
					'import' => $importer_id,
				),
				self_admin_url( 'admin.php' )
			);
			$action = sprintf(
				'<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( $url ),
				/* translators: %s: Tên trình nhập. */
				esc_attr( sprintf( __( 'Run %s' ), $data[0] ) ),
				__( 'Run Importer' )
			);

			$is_plugin_installed = true;
		}

		if ( ! $is_plugin_installed && is_main_site() ) {
			$url     = add_query_arg(
				array(
					'tab'       => 'plugin-information',
					'plugin'    => $plugin_slug,
					'from'      => 'import',
					'TB_iframe' => 'true',
					'width'     => 600,
					'height'    => 550,
				),
				network_admin_url( 'plugin-install.php' )
			);
			$action .= sprintf(
				' | <a href="%1$s" class="thickbox open-plugin-details-modal" aria-label="%2$s">%3$s</a>',
				esc_url( $url ),
				/* translators: %s: Tên trình nhập. */
				esc_attr( sprintf( __( 'More information about %s' ), $data[0] ) ),
				__( 'Details' )
			);
		}

		echo "
			<tr class='importer-item'>
				<td class='import-system'>
					<span class='importer-title'>{$data[0]}</span>
					<span class='importer-action'>{$action}</span>
				</td>
				<td class='desc'>
					<span class='importer-desc'>{$data[1]}</span>
				</td>
			</tr>";
	}
	?>
</table>
	<?php
}

if ( current_user_can( 'install_plugins' ) ) {
	echo '<p>' . sprintf(
		/* translators: %s: URL đến màn hình Thêm Plugin. */
		__( 'If the importer you need is not listed, <a href="%s">search the plugin directory</a> to see if an importer is available.' ),
		esc_url( network_admin_url( 'plugin-install.php?tab=search&type=tag&s=importer' ) )
	) . '</p>';
}

/**
 * Kích hoạt ở cuối màn hình Nhập.
 *
 * @since 6.8.0
 */
do_action( 'import_filters' );
?>

</div>

<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();

require_once ABSPATH . 'wp-admin/admin-footer.php';
