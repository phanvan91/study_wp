<?php
/**
 * Định nghĩa các hằng số và biến toàn cục có thể được ghi đè, thường trong wp-config.php.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

/**
 * Định nghĩa các hằng số upload cho Multisite.
 *
 * Tồn tại để tương thích ngược với phục vụ tệp cũ qua
 * wp-includes/ms-files.php (wp-content/blogs.php trong MU).
 *
 * @since 3.0.0
 */
function ms_upload_constants() {
	// Bộ lọc này được gắn trong ms-default-filters.php nhưng tệp đó không được bao gồm trong SHORTINIT.
	add_filter( 'default_site_option_ms_files_rewriting', '__return_true' );

	if ( ! get_site_option( 'ms_files_rewriting' ) ) {
		return;
	}

	// Thư mục uploads gốc tương đối so với ABSPATH.
	if ( ! defined( 'UPLOADBLOGSDIR' ) ) {
		define( 'UPLOADBLOGSDIR', 'wp-content/blogs.dir' );
	}

	/*
	 * Lưu ý, site chính trong mạng hậu MU sử dụng wp-content/uploads.
	 * Điều này được xử lý trong wp_upload_dir() bằng cách bỏ qua UPLOADS cho trường hợp này.
	 */
	if ( ! defined( 'UPLOADS' ) ) {
		$site_id = get_current_blog_id();

		define( 'UPLOADS', UPLOADBLOGSDIR . '/' . $site_id . '/files/' );

		// Thư mục uploads tương đối so với ABSPATH.
		if ( 'wp-content/blogs.dir' === UPLOADBLOGSDIR && ! defined( 'BLOGUPLOADDIR' ) ) {
			define( 'BLOGUPLOADDIR', WP_CONTENT_DIR . '/blogs.dir/' . $site_id . '/files/' );
		}
	}
}

/**
 * Định nghĩa các hằng số cookie cho Multisite.
 *
 * @since 3.0.0
 */
function ms_cookie_constants() {
	$current_network = get_network();

	/**
	 * @since 1.2.0
	 */
	if ( ! defined( 'COOKIEPATH' ) ) {
		define( 'COOKIEPATH', $current_network->path );
	}

	/**
	 * @since 1.5.0
	 */
	if ( ! defined( 'SITECOOKIEPATH' ) ) {
		define( 'SITECOOKIEPATH', $current_network->path );
	}

	/**
	 * @since 2.6.0
	 */
	if ( ! defined( 'ADMIN_COOKIE_PATH' ) ) {
		$site_path = parse_url( get_option( 'siteurl' ), PHP_URL_PATH );
		if ( ! is_subdomain_install() || is_string( $site_path ) && trim( $site_path, '/' ) ) {
			define( 'ADMIN_COOKIE_PATH', SITECOOKIEPATH );
		} else {
			define( 'ADMIN_COOKIE_PATH', SITECOOKIEPATH . 'wp-admin' );
		}
	}

	/**
	 * @since 2.0.0
	 */
	if ( ! defined( 'COOKIE_DOMAIN' ) && is_subdomain_install() ) {
		if ( ! empty( $current_network->cookie_domain ) ) {
			define( 'COOKIE_DOMAIN', '.' . $current_network->cookie_domain );
		} else {
			define( 'COOKIE_DOMAIN', '.' . $current_network->domain );
		}
	}
}

/**
 * Định nghĩa các hằng số tệp cho Multisite.
 *
 * Tồn tại để tương thích ngược với phục vụ tệp cũ qua
 * wp-includes/ms-files.php (wp-content/blogs.php trong MU).
 *
 * @since 3.0.0
 */
function ms_file_constants() {
	/**
	 * Hỗ trợ tùy chọn cho header X-Sendfile
	 *
	 * @since 3.0.0
	 */
	if ( ! defined( 'WPMU_SENDFILE' ) ) {
		define( 'WPMU_SENDFILE', false );
	}

	/**
	 * Hỗ trợ tùy chọn cho header X-Accel-Redirect
	 *
	 * @since 3.0.0
	 */
	if ( ! defined( 'WPMU_ACCEL_REDIRECT' ) ) {
		define( 'WPMU_ACCEL_REDIRECT', false );
	}
}

/**
 * Định nghĩa các hằng số subdomain cho Multisite và xử lý cảnh báo và thông báo.
 *
 * VHOST đã bị loại bỏ để ưu tiên SUBDOMAIN_INSTALL, là kiểu bool.
 *
 * Khi gọi lần đầu, các hằng số được kiểm tra và định nghĩa. Khi gọi lần hai,
 * chúng ta đã tải xong bản dịch và có thể kích hoạt cảnh báo dễ dàng.
 *
 * @since 3.0.0
 */
function ms_subdomain_constants() {
	static $subdomain_error      = null;
	static $subdomain_error_warn = null;

	if ( false === $subdomain_error ) {
		return;
	}

	if ( $subdomain_error ) {
		$vhost_deprecated = sprintf(
			/* translators: 1: VHOST, 2: SUBDOMAIN_INSTALL, 3: wp-config.php, 4: is_subdomain_install() */
			__( 'The constant %1$s <strong>is deprecated</strong>. Use the boolean constant %2$s in %3$s to enable a subdomain configuration. Use %4$s to check whether a subdomain configuration is enabled.' ),
			'<code>VHOST</code>',
			'<code>SUBDOMAIN_INSTALL</code>',
			'<code>wp-config.php</code>',
			'<code>is_subdomain_install()</code>'
		);

		if ( $subdomain_error_warn ) {
			wp_trigger_error(
				__FUNCTION__,
				sprintf(
					/* translators: 1: VHOST, 2: SUBDOMAIN_INSTALL */
					__( '<strong>Conflicting values for the constants %1$s and %2$s.</strong> The value of %2$s will be assumed to be your subdomain configuration setting.' ),
					'<code>VHOST</code>',
					'<code>SUBDOMAIN_INSTALL</code>'
				) . ' ' . $vhost_deprecated,
				E_USER_WARNING
			);
		} else {
			_deprecated_argument( 'define()', '3.0.0', $vhost_deprecated );
		}

		return;
	}

	if ( defined( 'SUBDOMAIN_INSTALL' ) && defined( 'VHOST' ) ) {
		$subdomain_error = true;
		if ( SUBDOMAIN_INSTALL !== ( 'yes' === VHOST ) ) {
			$subdomain_error_warn = true;
		}
	} elseif ( defined( 'SUBDOMAIN_INSTALL' ) ) {
		$subdomain_error = false;
		define( 'VHOST', SUBDOMAIN_INSTALL ? 'yes' : 'no' );
	} elseif ( defined( 'VHOST' ) ) {
		$subdomain_error = true;
		define( 'SUBDOMAIN_INSTALL', 'yes' === VHOST );
	} else {
		$subdomain_error = false;
		define( 'SUBDOMAIN_INSTALL', false );
		define( 'VHOST', 'no' );
	}
}
