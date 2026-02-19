<?php
/**
 * Màn hình quản trị Quản lý Tùy chọn.
 *
 * Nếu truy cập trực tiếp qua trình duyệt, trang này hiển thị danh sách tất cả
 * các tùy chọn đã lưu cùng với các trường có thể chỉnh sửa giá trị. Dữ liệu
 * được serialize không được hỗ trợ và không có cách nào để xóa tùy chọn qua
 * trang này. Trang này không được liên kết từ bất kỳ nơi nào khác trong admin.
 *
 * File này cũng là đích đến của các biểu mẫu trong các trang tùy chọn lõi và
 * tùy chỉnh sử dụng Settings API. Trong trường hợp này, nó lưu các giá trị
 * tùy chọn mới và đưa người dùng trở lại trang gốc.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Khởi tạo Quản trị WordPress */
require_once __DIR__ . '/admin.php';

// Được sử dụng trong thẻ HTML title.
$title       = __( 'Settings' );
$this_file   = 'options.php';
$parent_file = 'options-general.php';

$action      = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
$option_page = ! empty( $_REQUEST['option_page'] ) ? sanitize_text_field( $_REQUEST['option_page'] ) : '';

$capability = 'manage_options';

// This is for back compat and will eventually be removed.
if ( empty( $option_page ) ) {
	$option_page = 'options';
} else {

	/**
	 * Lọc quyền hạn cần thiết khi sử dụng Settings API.
	 *
	 * Theo mặc định, các nhóm tùy chọn cho tất cả cài đặt đã đăng ký yêu cầu quyền manage_options.
	 * Bộ lọc này cần thiết để thay đổi quyền hạn yêu cầu cho một trang tùy chọn nhất định.
	 *
	 * @since 3.2.0
	 *
	 * @param string $capability Quyền hạn được sử dụng cho trang, mặc định là manage_options.
	 */
	$capability = apply_filters( "option_page_capability_{$option_page}", $capability );
}

if ( ! current_user_can( $capability ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to manage options for this site.' ) . '</p>',
		403
	);
}

// Xử lý các yêu cầu thay đổi email quản trị.
if ( ! empty( $_GET['adminhash'] ) ) {
	$new_admin_details = get_option( 'adminhash' );
	$redirect          = 'options-general.php?updated=false';

	if ( is_array( $new_admin_details )
		&& hash_equals( $new_admin_details['hash'], $_GET['adminhash'] )
		&& ! empty( $new_admin_details['newemail'] )
	) {
		update_option( 'admin_email', $new_admin_details['newemail'] );
		delete_option( 'adminhash' );
		delete_option( 'new_admin_email' );
		$redirect = 'options-general.php?updated=true';
	}

	wp_redirect( admin_url( $redirect ) );
	exit;
} elseif ( ! empty( $_GET['dismiss'] ) && 'new_admin_email' === $_GET['dismiss'] ) {
	check_admin_referer( 'dismiss-' . get_current_blog_id() . '-new_admin_email' );
	delete_option( 'adminhash' );
	delete_option( 'new_admin_email' );
	wp_redirect( admin_url( 'options-general.php?updated=true' ) );
	exit;
}

if ( is_multisite() && ! current_user_can( 'manage_network_options' ) && 'update' !== $action ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to delete these items.' ) . '</p>',
		403
	);
}

$allowed_options            = array(
	'general'    => array(
		'blogname',
		'blogdescription',
		'site_icon',
		'gmt_offset',
		'date_format',
		'time_format',
		'start_of_week',
		'timezone_string',
		'WPLANG',
		'new_admin_email',
	),
	'discussion' => array(
		'default_pingback_flag',
		'default_ping_status',
		'default_comment_status',
		'comments_notify',
		'moderation_notify',
		'comment_moderation',
		'require_name_email',
		'comment_previously_approved',
		'comment_max_links',
		'moderation_keys',
		'disallowed_keys',
		'show_avatars',
		'avatar_rating',
		'avatar_default',
		'close_comments_for_old_posts',
		'close_comments_days_old',
		'thread_comments',
		'thread_comments_depth',
		'page_comments',
		'comments_per_page',
		'default_comments_page',
		'comment_order',
		'comment_registration',
		'show_comments_cookies_opt_in',
	),
	'media'      => array(
		'thumbnail_size_w',
		'thumbnail_size_h',
		'thumbnail_crop',
		'medium_size_w',
		'medium_size_h',
		'large_size_w',
		'large_size_h',
		'image_default_size',
		'image_default_align',
		'image_default_link_type',
	),
	'reading'    => array(
		'posts_per_page',
		'posts_per_rss',
		'rss_use_excerpt',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
		'blog_public',
	),
	'writing'    => array(
		'default_category',
		'default_email_category',
		'default_link_category',
		'default_post_format',
	),
);
$allowed_options['misc']    = array();
$allowed_options['options'] = array();
$allowed_options['privacy'] = array();

/**
 * Lọc xem chức năng đăng bài qua email có được bật hay không.
 *
 * @since 3.0.0
 *
 * @param bool $enabled Chức năng đăng bài qua email có được bật không. Mặc định true.
 */
if ( apply_filters( 'enable_post_by_email_configuration', true ) ) {
	$allowed_options['writing'][] = 'mailserver_url';
	$allowed_options['writing'][] = 'mailserver_port';
	$allowed_options['writing'][] = 'mailserver_login';
	$allowed_options['writing'][] = 'mailserver_pass';
}

if ( ! is_utf8_charset() ) {
	$allowed_options['reading'][] = 'blog_charset';
}

if ( get_site_option( 'initial_db_version' ) < 32453 ) {
	$allowed_options['writing'][] = 'use_smilies';
	$allowed_options['writing'][] = 'use_balanceTags';
}

if ( ! is_multisite() ) {
	if ( ! defined( 'WP_SITEURL' ) ) {
		$allowed_options['general'][] = 'siteurl';
	}
	if ( ! defined( 'WP_HOME' ) ) {
		$allowed_options['general'][] = 'home';
	}

	$allowed_options['general'][] = 'users_can_register';
	$allowed_options['general'][] = 'default_role';

	if ( '1' === get_option( 'blog_public' ) ) {
		$allowed_options['writing'][] = 'ping_sites';
	}

	$allowed_options['media'][] = 'uploads_use_yearmonth_folders';

	/*
	 * Nếu upload_url_path không phải giá trị mặc định (rỗng),
	 * hoặc upload_path không phải giá trị mặc định ('wp-content/uploads' hoặc rỗng),
	 * chúng có thể được chỉnh sửa, ngược lại chúng bị khóa.
	 */
	if ( get_option( 'upload_url_path' )
		|| get_option( 'upload_path' ) && 'wp-content/uploads' !== get_option( 'upload_path' )
	) {
		$allowed_options['media'][] = 'upload_path';
		$allowed_options['media'][] = 'upload_url_path';
	}
}

/**
 * Lọc danh sách các tùy chọn được phép.
 *
 * @since 2.7.0
 * @deprecated 5.5.0 Sử dụng {@see 'allowed_options'} thay thế.
 *
 * @param array $allowed_options Danh sách các tùy chọn được phép.
 */
$allowed_options = apply_filters_deprecated(
	'whitelist_options',
	array( $allowed_options ),
	'5.5.0',
	'allowed_options',
	__( 'Please consider writing more inclusive code.' )
);

/**
 * Lọc danh sách các tùy chọn được phép.
 *
 * @since 5.5.0
 *
 * @param array $allowed_options Danh sách các tùy chọn được phép.
 */
$allowed_options = apply_filters( 'allowed_options', $allowed_options );

if ( 'update' === $action ) { // Chúng ta đang lưu cài đặt được gửi từ một trang cài đặt.
	if ( 'options' === $option_page && ! isset( $_POST['option_page'] ) ) { // Đây là để tương thích ngược và cuối cùng sẽ bị xóa.
		$unregistered = true;
		check_admin_referer( 'update-options' );
	} else {
		$unregistered = false;
		check_admin_referer( $option_page . '-options' );
	}

	if ( ! isset( $allowed_options[ $option_page ] ) ) {
		wp_die(
			sprintf(
				/* translators: %s: The options page name. */
				__( '<strong>Error:</strong> The %s options page is not in the allowed options list.' ),
				'<code>' . esc_html( $option_page ) . '</code>'
			)
		);
	}

	if ( 'options' === $option_page ) {
		if ( is_multisite() && ! current_user_can( 'manage_network_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to modify unregistered settings for this site.' ) );
		}
		$options = isset( $_POST['page_options'] ) ? explode( ',', wp_unslash( $_POST['page_options'] ) ) : null;
	} else {
		$options = $allowed_options[ $option_page ];
	}

	if ( 'general' === $option_page ) {
		// Xử lý các định dạng ngày/giờ tùy chỉnh.
		if ( ! empty( $_POST['date_format'] ) && isset( $_POST['date_format_custom'] )
			&& '\c\u\s\t\o\m' === wp_unslash( $_POST['date_format'] )
		) {
			$_POST['date_format'] = $_POST['date_format_custom'];
		}

		if ( ! empty( $_POST['time_format'] ) && isset( $_POST['time_format_custom'] )
			&& '\c\u\s\t\o\m' === wp_unslash( $_POST['time_format'] )
		) {
			$_POST['time_format'] = $_POST['time_format_custom'];
		}

		// Ánh xạ múi giờ UTC+- sang gmt_offsets và đặt timezone_string thành rỗng.
		if ( ! empty( $_POST['timezone_string'] ) && preg_match( '/^UTC[+-]/', $_POST['timezone_string'] ) ) {
			$_POST['gmt_offset']      = $_POST['timezone_string'];
			$_POST['gmt_offset']      = preg_replace( '/UTC\+?/', '', $_POST['gmt_offset'] );
			$_POST['timezone_string'] = '';
		} elseif ( isset( $_POST['timezone_string'] ) && ! in_array( $_POST['timezone_string'], timezone_identifiers_list( DateTimeZone::ALL_WITH_BC ), true ) ) {
			// Đặt lại về giá trị hiện tại.
			$current_timezone_string = get_option( 'timezone_string' );

			if ( ! empty( $current_timezone_string ) ) {
				$_POST['timezone_string'] = $current_timezone_string;
			} else {
				$_POST['gmt_offset']      = get_option( 'gmt_offset' );
				$_POST['timezone_string'] = '';
			}

			add_settings_error(
				'general',
				'settings_updated',
				__( 'The timezone you have entered is not valid. Please select a valid timezone.' ),
				'error'
			);
		}

		// Xử lý cài đặt bản dịch.
		if ( ! empty( $_POST['WPLANG'] ) && current_user_can( 'install_languages' ) ) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';

			if ( wp_can_install_language_pack() ) {
				$language = wp_download_language_pack( $_POST['WPLANG'] );
				if ( $language ) {
					$_POST['WPLANG'] = $language;
				}
			}
		}
	}

	if ( $options ) {
		$user_language_old = get_user_locale();

		foreach ( $options as $option ) {
			if ( $unregistered ) {
				_deprecated_argument(
					'options.php',
					'2.7.0',
					sprintf(
						/* translators: 1: The option/setting, 2: Documentation URL. */
						__( 'The %1$s setting is unregistered. Unregistered settings are deprecated. See <a href="%2$s">documentation on the Settings API</a>.' ),
						'<code>' . esc_html( $option ) . '</code>',
						__( 'https://developer.wordpress.org/plugins/settings/settings-api/' )
					)
				);
			}

			$option = trim( $option );
			$value  = null;
			if ( isset( $_POST[ $option ] ) ) {
				$value = $_POST[ $option ];
				if ( ! is_array( $value ) ) {
					$value = trim( $value );
				}
				$value = wp_unslash( $value );
			}
			update_option( $option, $value );
		}

		/*
		 * Chuyển đổi bản dịch trong trường hợp WPLANG bị thay đổi.
		 * Biến toàn cục $locale được sử dụng trong get_locale(),
		 * được dùng làm giá trị dự phòng trong get_user_locale().
		 */
		unset( $GLOBALS['locale'] );
		$user_language_new = get_user_locale();
		if ( $user_language_old !== $user_language_new ) {
			load_default_textdomain( $user_language_new );
		}
	} else {
		add_settings_error( 'general', 'settings_updated', __( 'Settings save failed.' ), 'error' );
	}

	/*
	 * Xử lý lỗi cài đặt và quay lại trang tùy chọn.
	 */

	// Nếu không có lỗi cài đặt nào được đăng ký, thêm thông báo 'đã cập nhật' chung.
	if ( ! count( get_settings_errors() ) ) {
		add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'success' );
	}

	set_transient( 'settings_errors', get_settings_errors(), 30 ); // 30 giây.

	// Chuyển hướng trở lại trang cài đặt đã được gửi.
	$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
	wp_redirect( $goback );
	exit;
}

require_once ABSPATH . 'wp-admin/admin-header.php';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'All Settings' ); ?></h1>

	<?php
	wp_admin_notice(
		'<strong>' . __( 'Warning:' ) . '</strong> ' . __( 'This page allows direct access to your site settings. You can break things here. Please be cautious!' ),
		array(
			'type' => 'warning',
		)
	);
	?>
	<form name="form" action="options.php" method="post" id="all-options">
		<?php wp_nonce_field( 'options-options' ); ?>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="option_page" value="options" />
		<table class="form-table" role="presentation">
<?php
$options = $wpdb->get_results( "SELECT * FROM $wpdb->options ORDER BY option_name" );

foreach ( (array) $options as $option ) :
	$disabled = false;

	if ( '' === $option->option_name ) {
		continue;
	}

	if ( is_serialized( $option->option_value ) ) {
		if ( is_serialized_string( $option->option_value ) ) {
			// Đây là chuỗi được serialize, vì vậy chúng ta nên hiển thị nó.
			$value               = maybe_unserialize( $option->option_value );
			$options_to_update[] = $option->option_name;
			$class               = 'all-options';
		} else {
			$value    = 'SERIALIZED DATA';
			$disabled = true;
			$class    = 'all-options disabled';
		}
	} else {
		$value               = $option->option_value;
		$options_to_update[] = $option->option_name;
		$class               = 'all-options';
	}

	$name = esc_attr( $option->option_name );
	?>
<tr>
	<th scope="row"><label for="<?php echo $name; ?>"><?php echo esc_html( $option->option_name ); ?></label></th>
<td>
	<?php if ( str_contains( $value, "\n" ) ) : ?>
		<textarea class="<?php echo $class; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" cols="30" rows="5"><?php echo esc_textarea( $value ); ?></textarea>
	<?php else : ?>
		<input class="regular-text <?php echo $class; ?>" type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr( $value ); ?>"<?php disabled( $disabled, true ); ?> />
	<?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>

<input type="hidden" name="page_options" value="<?php echo esc_attr( implode( ',', $options_to_update ) ); ?>" />

<?php submit_button( __( 'Save Changes' ), 'primary', 'Update' ); ?>

</form>
</div>

<?php
require_once ABSPATH . 'wp-admin/admin-footer.php';
