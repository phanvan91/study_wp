<?php
/**
 * Khởi tạo Quản trị WordPress
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Đang ở trong Màn hình Quản trị WordPress
 *
 * @since 2.3.2
 */
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

if ( ! defined( 'WP_NETWORK_ADMIN' ) ) {
	define( 'WP_NETWORK_ADMIN', false );
}

if ( ! defined( 'WP_USER_ADMIN' ) ) {
	define( 'WP_USER_ADMIN', false );
}

if ( ! WP_NETWORK_ADMIN && ! WP_USER_ADMIN ) {
	define( 'WP_BLOG_ADMIN', true );
}

if ( isset( $_GET['import'] ) && ! defined( 'WP_LOAD_IMPORTERS' ) ) {
	define( 'WP_LOAD_IMPORTERS', true );
}

/** Tải phần Khởi động WordPress */
require_once dirname( __DIR__ ) . '/wp-load.php';

nocache_headers();

if ( get_option( 'db_upgraded' ) ) {

	flush_rewrite_rules();
	update_option( 'db_upgraded', false, true );

	/**
	 * Kích hoạt vào lần tải trang tiếp theo sau khi nâng cấp CSDL thành công.
	 *
	 * @since 2.8.0
	 */
	do_action( 'after_db_upgrade' );

} elseif ( ! wp_doing_ajax() && empty( $_POST )
	&& (int) get_option( 'db_version' ) !== $wp_db_version
) {

	if ( ! is_multisite() ) {
		wp_redirect( admin_url( 'upgrade.php?_wp_http_referer=' . urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		exit;
	}

	/**
	 * Lọc xem có nên thực hiện quy trình nâng cấp CSDL multisite hay không.
	 *
	 * Trên site đơn, người dùng sẽ được chuyển hướng đến wp-admin/upgrade.php.
	 * Trên multisite, quy trình nâng cấp CSDL được tự động kích hoạt, nhưng chỉ
	 * khi bộ lọc này trả về true.
	 *
	 * Nếu mạng có 50 site trở xuống, nó sẽ chạy mỗi lần. Ngược lại,
	 * nó sẽ tự điều tiết để giảm tải.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param bool $do_mu_upgrade Có thực hiện quy trình nâng cấp Multisite hay không. Mặc định true.
	 */
	if ( apply_filters( 'do_mu_upgrade', true ) ) {
		$c = get_blog_count();

		/*
		 * Nếu có 50 site trở xuống, chạy mỗi lần. Ngược lại, điều tiết để giảm tải:
		 * cố gắng không vượt quá giá trị ngưỡng, với một số sai lệch +/- cho phép.
		 */
		if ( $c <= 50 || ( $c > 50 && mt_rand( 0, (int) ( $c / 50 ) ) === 1 ) ) {
			require_once ABSPATH . WPINC . '/http.php';
			$response = wp_remote_get(
				admin_url( 'upgrade.php?step=1' ),
				array(
					'timeout'     => 120,
					'httpversion' => '1.1',
				)
			);
			/** Action này được ghi tài liệu trong wp-admin/network/upgrade.php */
			do_action( 'after_mu_upgrade', $response );
			unset( $response );
		}
		unset( $c );
	}
}

require_once ABSPATH . 'wp-admin/includes/admin.php';

auth_redirect();

// Lên lịch thu gom Thùng rác.
if ( ! wp_next_scheduled( 'wp_scheduled_delete' ) && ! wp_installing() ) {
	wp_schedule_event( time(), 'daily', 'wp_scheduled_delete' );
}

// Lên lịch dọn dẹp transient.
if ( ! wp_next_scheduled( 'delete_expired_transients' ) && ! wp_installing() ) {
	wp_schedule_event( time(), 'daily', 'delete_expired_transients' );
}

set_screen_options();

$date_format = __( 'F j, Y' );
$time_format = __( 'g:i a' );

wp_enqueue_script( 'common' );

/**
 * $pagenow được thiết lập trong vars.php.
 * $wp_importers đôi khi được thiết lập trong wp-admin/includes/import.php.
 * Các biến còn lại được import dưới dạng biến toàn cục ở nơi khác, được khai báo là biến toàn cục ở đây.
 *
 * @global string $pagenow      Tên file của màn hình hiện tại.
 * @global array  $wp_importers
 * @global string $hook_suffix
 * @global string $plugin_page
 * @global string $typenow      Loại bài viết của màn hình hiện tại.
 * @global string $taxnow       Taxonomy của màn hình hiện tại.
 */
global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;

$page_hook = null;

$editing = false;

if ( isset( $_GET['page'] ) ) {
	$plugin_page = wp_unslash( $_GET['page'] );
	$plugin_page = plugin_basename( $plugin_page );
}

if ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) {
	$typenow = $_REQUEST['post_type'];
} else {
	$typenow = '';
}

if ( isset( $_REQUEST['taxonomy'] ) && taxonomy_exists( $_REQUEST['taxonomy'] ) ) {
	$taxnow = $_REQUEST['taxonomy'];
} else {
	$taxnow = '';
}

if ( WP_NETWORK_ADMIN ) {
	require ABSPATH . 'wp-admin/network/menu.php';
} elseif ( WP_USER_ADMIN ) {
	require ABSPATH . 'wp-admin/user/menu.php';
} else {
	require ABSPATH . 'wp-admin/menu.php';
}

if ( current_user_can( 'manage_options' ) ) {
	wp_raise_memory_limit( 'admin' );
}

/**
 * Kích hoạt khi màn hình quản trị hoặc script đang được khởi tạo.
 *
 * Lưu ý, hook này không chỉ chạy trên các màn hình quản trị dành cho người dùng.
 * Nó cũng chạy trên admin-ajax.php và admin-post.php.
 *
 * Hook này tương tự với hook tổng quát hơn {@see 'init'}, được kích hoạt sớm hơn.
 *
 * @since 2.5.0
 */
do_action( 'admin_init' );

if ( isset( $plugin_page ) ) {
	if ( ! empty( $typenow ) ) {
		$the_parent = $pagenow . '?post_type=' . $typenow;
	} else {
		$the_parent = $pagenow;
	}

	$page_hook = get_plugin_page_hook( $plugin_page, $the_parent );
	if ( ! $page_hook ) {
		$page_hook = get_plugin_page_hook( $plugin_page, $plugin_page );

		// Tương thích ngược cho các plugin sử dụng add_management_page().
		if ( empty( $page_hook ) && 'edit.php' === $pagenow && get_plugin_page_hook( $plugin_page, 'tools.php' ) ) {
			// Có thể có các tham số riêng của plugin trên URL, nên cần toàn bộ chuỗi truy vấn.
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$query_string = $_SERVER['QUERY_STRING'];
			} else {
				$query_string = 'page=' . $plugin_page;
			}
			wp_redirect( admin_url( 'tools.php?' . $query_string ) );
			exit;
		}
	}
	unset( $the_parent );
}

$hook_suffix = '';
if ( isset( $page_hook ) ) {
	$hook_suffix = $page_hook;
} elseif ( isset( $plugin_page ) ) {
	$hook_suffix = $plugin_page;
} elseif ( isset( $pagenow ) ) {
	$hook_suffix = $pagenow;
}

set_current_screen();

// Xử lý các trang quản trị của plugin.
if ( isset( $plugin_page ) ) {
	if ( $page_hook ) {
		/**
		 * Kích hoạt trước khi một màn hình cụ thể được tải.
		 *
		 * Hook load-* kích hoạt trong nhiều ngữ cảnh. Hook này dành cho các màn hình plugin
		 * nơi callback được cung cấp khi màn hình được đăng ký.
		 *
		 * Phần động của tên hook, `$page_hook`, tham chiếu đến hỗn hợp thông tin
		 * trang plugin bao gồm:
		 * 1. Loại trang. Nếu trang plugin được đăng ký như trang con, chẳng hạn cho
		 *    Cài đặt, loại trang sẽ là 'settings'. Ngược lại loại là 'toplevel'.
		 * 2. Dấu phân cách '_page_'.
		 * 3. Tên cơ sở của plugin không bao gồm phần mở rộng file.
		 *
		 * Kết hợp lại, ba phần tạo thành `$page_hook`. Trích dẫn ví dụ trên,
		 * tên hook được sử dụng sẽ là 'load-settings_page_pluginbasename'.
		 *
		 * @see get_plugin_page_hook()
		 *
		 * @since 2.1.0
		 */
		do_action( "load-{$page_hook}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		if ( ! isset( $_GET['noheader'] ) ) {
			require_once ABSPATH . 'wp-admin/admin-header.php';
		}

		/**
		 * Được sử dụng để gọi callback đã đăng ký cho một màn hình plugin.
		 *
		 * Hook này sử dụng tên hook động, `$page_hook`, tham chiếu đến hỗn hợp thông tin
		 * trang plugin bao gồm:
		 * 1. Loại trang. Nếu trang plugin được đăng ký như trang con, chẳng hạn cho
		 *    Cài đặt, loại trang sẽ là 'settings'. Ngược lại loại là 'toplevel'.
		 * 2. Dấu phân cách '_page_'.
		 * 3. Tên cơ sở của plugin không bao gồm phần mở rộng file.
		 *
		 * Kết hợp lại, ba phần tạo thành `$page_hook`. Trích dẫn ví dụ trên,
		 * tên hook được sử dụng sẽ là 'settings_page_pluginbasename'.
		 *
		 * @see get_plugin_page_hook()
		 *
		 * @since 1.5.0
		 */
		do_action( $page_hook );
	} else {
		if ( validate_file( $plugin_page ) ) {
			wp_die( __( 'Invalid plugin page.' ) );
		}

		if ( ! ( file_exists( WP_PLUGIN_DIR . "/$plugin_page" ) && is_file( WP_PLUGIN_DIR . "/$plugin_page" ) )
			&& ! ( file_exists( WPMU_PLUGIN_DIR . "/$plugin_page" ) && is_file( WPMU_PLUGIN_DIR . "/$plugin_page" ) )
		) {
			/* translators: %s: Admin page generated by a plugin. */
			wp_die( sprintf( __( 'Cannot load %s.' ), htmlentities( $plugin_page ) ) );
		}

		/**
		 * Kích hoạt trước khi một màn hình cụ thể được tải.
		 *
		 * Hook load-* kích hoạt trong nhiều ngữ cảnh. Hook này dành cho các màn hình plugin
		 * nơi file cần tải được include trực tiếp, thay vì sử dụng hàm.
		 *
		 * Phần động của tên hook, `$plugin_page`, tham chiếu đến tên cơ sở của plugin.
		 *
		 * @see plugin_basename()
		 *
		 * @since 1.5.0
		 */
		do_action( "load-{$plugin_page}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		if ( ! isset( $_GET['noheader'] ) ) {
			require_once ABSPATH . 'wp-admin/admin-header.php';
		}

		if ( file_exists( WPMU_PLUGIN_DIR . "/$plugin_page" ) ) {
			include WPMU_PLUGIN_DIR . "/$plugin_page";
		} else {
			include WP_PLUGIN_DIR . "/$plugin_page";
		}
	}

	require_once ABSPATH . 'wp-admin/admin-footer.php';

	exit;
} elseif ( isset( $_GET['import'] ) ) {

	$importer = $_GET['import'];

	if ( ! current_user_can( 'import' ) ) {
		wp_die( __( 'Sorry, you are not allowed to import content into this site.' ) );
	}

	if ( validate_file( $importer ) ) {
		wp_redirect( admin_url( 'import.php?invalid=' . $importer ) );
		exit;
	}

	if ( ! isset( $wp_importers[ $importer ] ) || ! is_callable( $wp_importers[ $importer ][2] ) ) {
		wp_redirect( admin_url( 'import.php?invalid=' . $importer ) );
		exit;
	}

	/**
	 * Kích hoạt trước khi một màn hình trình nhập được tải.
	 *
	 * Phần động của tên hook, `$importer`, tham chiếu đến slug của trình nhập.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `load-importer-blogger`
	 *  - `load-importer-wpcat2tag`
	 *  - `load-importer-livejournal`
	 *  - `load-importer-mt`
	 *  - `load-importer-rss`
	 *  - `load-importer-tumblr`
	 *  - `load-importer-wordpress`
	 *
	 * @since 3.5.0
	 */
	do_action( "load-importer-{$importer}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

	// Được sử dụng trong thẻ title HTML.
	$title        = __( 'Import' );
	$parent_file  = 'tools.php';
	$submenu_file = 'import.php';

	if ( ! isset( $_GET['noheader'] ) ) {
		require_once ABSPATH . 'wp-admin/admin-header.php';
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	define( 'WP_IMPORTING', true );

	/**
	 * Lọc xem có nên lọc dữ liệu nhập qua kses khi nhập hay không.
	 *
	 * Multisite sử dụng hook này để lọc tất cả dữ liệu qua kses theo mặc định,
	 * vì quản trị viên cấp cao có thể đang hỗ trợ một người dùng không đáng tin cậy.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $force Có buộc dữ liệu phải lọc qua kses hay không. Mặc định false.
	 */
	if ( apply_filters( 'force_filtered_html_on_import', false ) ) {
		kses_init_filters();  // Luôn lọc dữ liệu nhập bằng kses trên multisite.
	}

	call_user_func( $wp_importers[ $importer ][2] );

	require_once ABSPATH . 'wp-admin/admin-footer.php';

	// Đảm bảo các quy tắc rewrite được xả.
	flush_rewrite_rules( false );

	exit;
} else {
	/**
	 * Kích hoạt trước khi một màn hình cụ thể được tải.
	 *
	 * Hook load-* kích hoạt trong nhiều ngữ cảnh. Hook này dành cho các màn hình lõi.
	 *
	 * Phần động của tên hook, `$pagenow`, là biến toàn cục
	 * tham chiếu đến tên file của màn hình hiện tại, chẳng hạn 'admin.php',
	 * 'post-new.php' v.v. Một hook đầy đủ cho trường hợp sau sẽ là
	 * 'load-post-new.php'.
	 *
	 * @since 2.1.0
	 */
	do_action( "load-{$pagenow}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

	/*
	 * Các hook sau được kích hoạt để đảm bảo tương thích ngược.
	 * Trong tất cả các trường hợp khác, nên sử dụng 'load-' . $pagenow thay thế.
	 */
	if ( 'page' === $typenow ) {
		if ( 'post-new.php' === $pagenow ) {
			do_action( 'load-page-new.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		} elseif ( 'post.php' === $pagenow ) {
			do_action( 'load-page.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}
	} elseif ( 'edit-tags.php' === $pagenow ) {
		if ( 'category' === $taxnow ) {
			do_action( 'load-categories.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		} elseif ( 'link_category' === $taxnow ) {
			do_action( 'load-edit-link-categories.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}
	} elseif ( 'term.php' === $pagenow ) {
		do_action( 'load-edit-tags.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}
}

if ( ! empty( $_REQUEST['action'] ) ) {
	$action = $_REQUEST['action'];

	/**
	 * Kích hoạt khi biến yêu cầu 'action' được gửi.
	 *
	 * Phần động của tên hook, `$action`, tham chiếu đến
	 * action được lấy từ yêu cầu `GET` hoặc `POST`.
	 *
	 * @since 2.6.0
	 */
	do_action( "admin_action_{$action}" );
}
