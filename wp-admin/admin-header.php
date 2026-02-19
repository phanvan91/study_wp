<?php
/**
 * Mẫu Header Quản trị WordPress
 *
 * @package WordPress
 * @subpackage Administration
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
if ( ! defined( 'WP_ADMIN' ) ) {
	require_once __DIR__ . '/admin.php';
}

/**
 * Trong trường hợp admin-header.php được include trong một hàm.
 *
 * @global string    $title              Tiêu đề của màn hình hiện tại.
 * @global string    $hook_suffix
 * @global WP_Screen $current_screen     Đối tượng màn hình hiện tại của WordPress.
 * @global WP_Locale $wp_locale          Đối tượng ngôn ngữ ngày giờ của WordPress.
 * @global string    $pagenow            Tên file của màn hình hiện tại.
 * @global string    $update_title
 * @global int       $total_update_count
 * @global string    $parent_file
 * @global string    $typenow            Loại bài viết của màn hình hiện tại.
 */
global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow,
	$update_title, $total_update_count, $parent_file, $typenow;

// Bắt các plugin include admin-header.php trước khi admin.php hoàn tất.
if ( empty( $current_screen ) ) {
	set_current_screen();
}

get_admin_page_title();
$title = strip_tags( $title );

if ( is_network_admin() ) {
	/* translators: Network admin screen title. %s: Network title. */
	$admin_title = sprintf( __( 'Network Admin: %s' ), get_network()->site_name );
} elseif ( is_user_admin() ) {
	/* translators: User dashboard screen title. %s: Network title. */
	$admin_title = sprintf( __( 'User Dashboard: %s' ), get_network()->site_name );
} else {
	$admin_title = get_bloginfo( 'name' );
}

if ( $admin_title === $title ) {
	/* translators: Admin screen title. %s: Admin screen name. */
	$admin_title = sprintf( __( '%s &#8212; WordPress' ), $title );
} else {
	$screen_title = $title;

	if ( 'post' === $current_screen->base && 'add' !== $current_screen->action ) {
		$post_title = get_the_title();
		if ( ! empty( $post_title ) ) {
			$post_type_obj = get_post_type_object( $typenow );
			$screen_title  = sprintf(
				/* translators: Editor admin screen title. 1: "Edit item" text for the post type, 2: Post title. */
				__( '%1$s &#8220;%2$s&#8221;' ),
				$post_type_obj->labels->edit_item,
				$post_title
			);
		}
	}

	/* translators: Admin screen title. 1: Admin screen name, 2: Network or site name. */
	$admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), $screen_title, $admin_title );
}

if ( wp_is_recovery_mode() ) {
	/* translators: %s: Admin screen title. */
	$admin_title = sprintf( __( 'Recovery Mode &#8212; %s' ), $admin_title );
}

/**
 * Lọc nội dung thẻ title cho một trang quản trị.
 *
 * @since 3.1.0
 *
 * @param string $admin_title Tiêu đề trang, với ngữ cảnh bổ sung được thêm vào.
 * @param string $title       Tiêu đề trang gốc.
 */
$admin_title = apply_filters( 'admin_title', $admin_title, $title );

wp_user_settings();

_wp_admin_html_begin();
?>
<title><?php echo esc_html( $admin_title ); ?></title>
<?php

wp_enqueue_style( 'colors' );
wp_enqueue_script( 'utils' );
wp_enqueue_script( 'svg-painter' );

$admin_body_class = preg_replace( '/[^a-z0-9_-]+/i', '-', $hook_suffix );
?>
<script type="text/javascript">
addLoadEvent = function(func){if(typeof jQuery!=='undefined')jQuery(function(){func();});else if(typeof wpOnload!=='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>',
	pagenow = '<?php echo esc_js( $current_screen->id ); ?>',
	typenow = '<?php echo esc_js( $current_screen->post_type ); ?>',
	adminpage = '<?php echo esc_js( $admin_body_class ); ?>',
	thousandsSeparator = '<?php echo esc_js( $wp_locale->number_format['thousands_sep'] ); ?>',
	decimalPoint = '<?php echo esc_js( $wp_locale->number_format['decimal_point'] ); ?>',
	isRtl = <?php echo (int) is_rtl(); ?>;
</script>
<?php

/**
 * Kích hoạt khi nạp script cho tất cả các trang quản trị.
 *
 * @since 2.8.0
 *
 * @param string $hook_suffix Trang quản trị hiện tại.
 */
do_action( 'admin_enqueue_scripts', $hook_suffix );

/**
 * Kích hoạt khi các style được in cho một trang quản trị cụ thể dựa trên $hook_suffix.
 *
 * @since 2.6.0
 */
do_action( "admin_print_styles-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Kích hoạt khi các style được in cho tất cả các trang quản trị.
 *
 * @since 2.6.0
 */
do_action( 'admin_print_styles' );

/**
 * Kích hoạt khi các script được in cho một trang quản trị cụ thể dựa trên $hook_suffix.
 *
 * @since 2.1.0
 */
do_action( "admin_print_scripts-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Kích hoạt khi các script được in cho tất cả các trang quản trị.
 *
 * @since 2.1.0
 */
do_action( 'admin_print_scripts' );

/**
 * Kích hoạt trong phần head cho một trang quản trị cụ thể.
 *
 * Phần động của tên hook, `$hook_suffix`, tham chiếu đến hậu tố hook
 * cho trang quản trị.
 *
 * @since 2.1.0
 */
do_action( "admin_head-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Kích hoạt trong phần head cho tất cả các trang quản trị.
 *
 * @since 2.1.0
 */
do_action( 'admin_head' );

if ( 'f' === get_user_setting( 'mfold' ) ) {
	$admin_body_class .= ' folded';
}

if ( ! get_user_setting( 'unfold' ) ) {
	$admin_body_class .= ' auto-fold';
}

if ( is_admin_bar_showing() ) {
	$admin_body_class .= ' admin-bar';
}

if ( is_rtl() ) {
	$admin_body_class .= ' rtl';
}

if ( $current_screen->post_type ) {
	$admin_body_class .= ' post-type-' . $current_screen->post_type;
}

if ( $current_screen->taxonomy ) {
	$admin_body_class .= ' taxonomy-' . $current_screen->taxonomy;
}

$admin_body_class .= ' branch-' . str_replace( array( '.', ',' ), '-', (float) get_bloginfo( 'version' ) );
$admin_body_class .= ' version-' . str_replace( '.', '-', preg_replace( '/^([.0-9]+).*/', '$1', get_bloginfo( 'version' ) ) );
$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );
$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

if ( wp_is_mobile() ) {
	$admin_body_class .= ' mobile';
}

if ( is_multisite() ) {
	$admin_body_class .= ' multisite';
}

if ( is_network_admin() ) {
	$admin_body_class .= ' network-admin';
}

$admin_body_class .= ' no-customize-support svg';

if ( $current_screen->is_block_editor() ) {
	$admin_body_class .= ' block-editor-page wp-embed-responsive';
}

$admin_body_class .= ' wp-theme-' . sanitize_html_class( get_template() );
if ( is_child_theme() ) {
	$admin_body_class .= ' wp-child-theme-' . sanitize_html_class( get_stylesheet() );
}

$error_get_last = error_get_last();

// In một lớp CSS để làm cho lỗi PHP hiển thị.
if ( $error_get_last && WP_DEBUG && WP_DEBUG_DISPLAY && ini_get( 'display_errors' )
	// Không in lớp cho các thông báo PHP trong wp-config.php, vì chúng xảy ra trước khi WP_DEBUG có hiệu lực,
	// và không nên được hiển thị với mức `error_reporting` đã được thiết lập trước đó trong wp-load.php.
	&& ( E_NOTICE !== $error_get_last['type'] || 'wp-config.php' !== wp_basename( $error_get_last['file'] ) )
) {
	$admin_body_class .= ' php-error';
}

unset( $error_get_last );

?>
</head>
<?php
/**
 * Lọc các lớp CSS cho thẻ body trong trang quản trị.
 *
 * Bộ lọc này khác với các bộ lọc {@see 'post_class'} và {@see 'body_class'}
 * ở hai điểm quan trọng:
 *
 * 1. `$classes` là một chuỗi các tên lớp phân cách bằng dấu cách thay vì mảng.
 * 2. Không phải tất cả các lớp quản trị lõi đều có thể lọc được, đáng chú ý: wp-admin, wp-core-ui,
 *    và no-js không thể bị xóa.
 *
 * @since 2.3.0
 *
 * @param string $classes Danh sách các lớp CSS phân cách bằng dấu cách.
 */
$admin_body_classes = apply_filters( 'admin_body_class', '' );
$admin_body_classes = ltrim( $admin_body_classes . ' ' . $admin_body_class );
?>
<body class="wp-admin wp-core-ui no-js <?php echo esc_attr( $admin_body_classes ); ?>">
<script type="text/javascript">
	document.body.className = document.body.className.replace('no-js','js');
</script>

<?php
// Đảm bảo các lớp body tùy biến là chính xác càng sớm càng tốt.
if ( current_user_can( 'customize' ) ) {
	wp_customize_support_script();
}
?>

<div id="wpwrap">
<?php require ABSPATH . 'wp-admin/menu-header.php'; ?>
<div id="wpcontent">

<?php
/**
 * Kích hoạt ở đầu phần nội dung trong một trang quản trị.
 *
 * @since 3.0.0
 */
do_action( 'in_admin_header' );
?>

<div id="wpbody" role="main">
<?php
unset( $blog_name, $total_update_count, $update_title );

$current_screen->set_parentage( $parent_file );

?>

<div id="wpbody-content">
<?php

$current_screen->render_screen_meta();

if ( is_network_admin() ) {
	/**
	 * In các thông báo màn hình quản trị mạng.
	 *
	 * @since 3.1.0
	 */
	do_action( 'network_admin_notices' );
} elseif ( is_user_admin() ) {
	/**
	 * In các thông báo màn hình quản trị người dùng.
	 *
	 * @since 3.1.0
	 */
	do_action( 'user_admin_notices' );
} else {
	/**
	 * In các thông báo màn hình quản trị.
	 *
	 * @since 3.1.0
	 */
	do_action( 'admin_notices' );
}

/**
 * In các thông báo màn hình quản trị chung.
 *
 * @since 3.1.0
 */
do_action( 'all_admin_notices' );

if ( 'options-general.php' === $parent_file ) {
	require ABSPATH . 'wp-admin/options-head.php';
}
