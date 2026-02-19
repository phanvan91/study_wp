<?php
/**
 * API Hệ thống tệp: Chức năng cấp cao nhất
 *
 * Các hàm để đọc, ghi, sửa đổi và xóa tệp trên hệ thống tệp.
 * Bao gồm chức năng dành riêng cho giao diện cũng như các thao tác tải lên,
 * lưu trữ và hiển thị đầu ra khi cần thiết.
 *
 * @package WordPress
 * @subpackage Filesystem
 * @since 2.3.0
 */

/** Mô tả cho các tệp giao diện. */
$wp_file_descriptions = array(
	'functions.php'         => __( 'Theme Functions' ),
	'header.php'            => __( 'Theme Header' ),
	'footer.php'            => __( 'Theme Footer' ),
	'sidebar.php'           => __( 'Sidebar' ),
	'comments.php'          => __( 'Comments' ),
	'searchform.php'        => __( 'Search Form' ),
	'404.php'               => __( '404 Template' ),
	'link.php'              => __( 'Links Template' ),
	'theme.json'            => __( 'Theme Styles & Block Settings' ),
	// Trang lưu trữ.
	'index.php'             => __( 'Main Index Template' ),
	'archive.php'           => __( 'Archives' ),
	'author.php'            => __( 'Author Template' ),
	'taxonomy.php'          => __( 'Taxonomy Template' ),
	'category.php'          => __( 'Category Template' ),
	'tag.php'               => __( 'Tag Template' ),
	'home.php'              => __( 'Posts Page' ),
	'search.php'            => __( 'Search Results' ),
	'date.php'              => __( 'Date Template' ),
	// Nội dung.
	'singular.php'          => __( 'Singular Template' ),
	'single.php'            => __( 'Single Post' ),
	'page.php'              => __( 'Single Page' ),
	'front-page.php'        => __( 'Homepage' ),
	'privacy-policy.php'    => __( 'Privacy Policy Page' ),
	// Tệp đính kèm.
	'attachment.php'        => __( 'Attachment Template' ),
	'image.php'             => __( 'Image Attachment Template' ),
	'video.php'             => __( 'Video Attachment Template' ),
	'audio.php'             => __( 'Audio Attachment Template' ),
	'application.php'       => __( 'Application Attachment Template' ),
	// Nhúng.
	'embed.php'             => __( 'Embed Template' ),
	'embed-404.php'         => __( 'Embed 404 Template' ),
	'embed-content.php'     => __( 'Embed Content Template' ),
	'header-embed.php'      => __( 'Embed Header Template' ),
	'footer-embed.php'      => __( 'Embed Footer Template' ),
	// Bảng kiểu.
	'style.css'             => __( 'Stylesheet' ),
	'editor-style.css'      => __( 'Visual Editor Stylesheet' ),
	'editor-style-rtl.css'  => __( 'Visual Editor RTL Stylesheet' ),
	'rtl.css'               => __( 'RTL Stylesheet' ),
	// Khác.
	'my-hacks.php'          => __( 'my-hacks.php (legacy hacks support)' ),
	'.htaccess'             => __( '.htaccess (for rewrite rules )' ),
	// Tệp không còn sử dụng.
	'wp-layout.css'         => __( 'Stylesheet' ),
	'wp-comments.php'       => __( 'Comments Template' ),
	'wp-comments-popup.php' => __( 'Popup Comments Template' ),
	'comments-popup.php'    => __( 'Popup Comments' ),
);

/**
 * Lấy mô tả cho các tệp giao diện WordPress tiêu chuẩn.
 *
 * @since 1.5.0
 *
 * @global array $wp_file_descriptions Mô tả các tệp giao diện.
 * @global array $allowed_files        Danh sách các tệp được phép.
 *
 * @param string $file Đường dẫn hệ thống tệp hoặc tên tệp.
 * @return string Mô tả tệp từ $wp_file_descriptions hoặc tên cơ sở của $file nếu mô tả không tồn tại.
 *                Thêm 'Page Template' vào tên cơ sở của $file nếu tệp là mẫu trang.
 */
function get_file_description( $file ) {
	global $wp_file_descriptions, $allowed_files;

	$dirname   = pathinfo( $file, PATHINFO_DIRNAME );
	$file_path = $allowed_files[ $file ];

	if ( isset( $wp_file_descriptions[ basename( $file ) ] ) && '.' === $dirname ) {
		return $wp_file_descriptions[ basename( $file ) ];
	} elseif ( file_exists( $file_path ) && is_file( $file_path ) ) {
		$template_data = implode( '', file( $file_path ) );

		if ( preg_match( '|Template Name:(.*)$|mi', $template_data, $name ) ) {
			/* translators: %s: Template name. */
			return sprintf( __( '%s Page Template' ), _cleanup_header_comment( $name[1] ) );
		}
	}

	return trim( basename( $file ) );
}

/**
 * Lấy đường dẫn tuyệt đối trên hệ thống tệp đến thư mục gốc của bản cài đặt WordPress.
 *
 * @since 1.5.0
 *
 * @return string Đường dẫn đầy đủ trên hệ thống tệp đến thư mục gốc của bản cài đặt WordPress.
 */
function get_home_path() {
	$home    = set_url_scheme( get_option( 'home' ), 'http' );
	$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );

	if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
		$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
		$pos                 = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
		$home_path           = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
		$home_path           = trailingslashit( $home_path );
	} else {
		$home_path = ABSPATH;
	}

	return str_replace( '\\', '/', $home_path );
}

/**
 * Trả về danh sách tất cả các tệp trong thư mục được chỉ định và tất cả thư mục con sâu tối đa 100 cấp.
 *
 * Độ sâu đệ quy có thể được kiểm soát bằng tham số $levels.
 *
 * @since 2.6.0
 * @since 4.9.0 Thêm tham số `$exclusions`.
 * @since 6.3.0 Thêm tham số `$include_hidden`.
 *
 * @param string   $folder         Tùy chọn. Đường dẫn đầy đủ đến thư mục. Mặc định rỗng.
 * @param int      $levels         Tùy chọn. Số cấp thư mục để duyệt, Mặc định 100 (giới hạn vòng lặp PHP).
 * @param string[] $exclusions     Tùy chọn. Danh sách các thư mục và tệp cần bỏ qua.
 * @param bool     $include_hidden Tùy chọn. Có bao gồm chi tiết các tệp ẩn (tiền tố ".") hay không.
 *                                 Mặc định false.
 * @return string[]|false Mảng các tệp khi thành công, false khi thất bại.
 */
function list_files( $folder = '', $levels = 100, $exclusions = array(), $include_hidden = false ) {
	if ( empty( $folder ) ) {
		return false;
	}

	$folder = trailingslashit( $folder );

	if ( ! $levels ) {
		return false;
	}

	$files = array();

	$dir = @opendir( $folder );

	if ( $dir ) {
		while ( ( $file = readdir( $dir ) ) !== false ) {
			// Bỏ qua liên kết thư mục hiện tại và thư mục cha.
			if ( in_array( $file, array( '.', '..' ), true ) ) {
				continue;
			}

			// Bỏ qua các tệp ẩn và tệp bị loại trừ.
			if ( ( ! $include_hidden && '.' === $file[0] ) || in_array( $file, $exclusions, true ) ) {
				continue;
			}

			if ( is_dir( $folder . $file ) ) {
				$files2 = list_files( $folder . $file, $levels - 1, array(), $include_hidden );
				if ( $files2 ) {
					$files = array_merge( $files, $files2 );
				} else {
					$files[] = $folder . $file . '/';
				}
			} else {
				$files[] = $folder . $file;
			}
		}

		closedir( $dir );
	}

	return $files;
}

/**
 * Lấy danh sách các phần mở rộng tệp có thể chỉnh sửa trong plugin.
 *
 * @since 4.9.0
 *
 * @param string $plugin Đường dẫn đến tệp plugin tương đối với thư mục plugins.
 * @return string[] Mảng các phần mở rộng tệp có thể chỉnh sửa.
 */
function wp_get_plugin_file_editable_extensions( $plugin ) {

	$default_types = array(
		'bash',
		'conf',
		'css',
		'diff',
		'htm',
		'html',
		'http',
		'inc',
		'include',
		'js',
		'json',
		'jsx',
		'less',
		'md',
		'patch',
		'php',
		'php3',
		'php4',
		'php5',
		'php7',
		'phps',
		'phtml',
		'sass',
		'scss',
		'sh',
		'sql',
		'svg',
		'text',
		'txt',
		'xml',
		'yaml',
		'yml',
	);

	/**
	 * Lọc danh sách các loại tệp được phép chỉnh sửa trong trình sửa tệp plugin.
	 *
	 * @since 2.8.0
	 * @since 4.9.0 Thêm tham số `$plugin`.
	 *
	 * @param string[] $default_types Mảng các phần mở rộng tệp plugin có thể chỉnh sửa.
	 * @param string   $plugin        Đường dẫn đến tệp plugin tương đối với thư mục plugins.
	 */
	$file_types = (array) apply_filters( 'editable_extensions', $default_types, $plugin );

	return $file_types;
}

/**
 * Lấy danh sách các phần mở rộng tệp có thể chỉnh sửa cho một giao diện cụ thể.
 *
 * @since 4.9.0
 *
 * @param WP_Theme $theme Đối tượng giao diện.
 * @return string[] Mảng các phần mở rộng tệp có thể chỉnh sửa.
 */
function wp_get_theme_file_editable_extensions( $theme ) {

	$default_types = array(
		'bash',
		'conf',
		'css',
		'diff',
		'htm',
		'html',
		'http',
		'inc',
		'include',
		'js',
		'json',
		'jsx',
		'less',
		'md',
		'patch',
		'php',
		'php3',
		'php4',
		'php5',
		'php7',
		'phps',
		'phtml',
		'sass',
		'scss',
		'sh',
		'sql',
		'svg',
		'text',
		'txt',
		'xml',
		'yaml',
		'yml',
	);

	/**
	 * Lọc danh sách các loại tệp được phép chỉnh sửa trong trình sửa tệp giao diện.
	 *
	 * @since 4.4.0
	 *
	 * @param string[] $default_types Mảng các phần mở rộng tệp giao diện có thể chỉnh sửa.
	 * @param WP_Theme $theme         Đối tượng giao diện đang hoạt động.
	 */
	$file_types = apply_filters( 'wp_theme_editor_filetypes', $default_types, $theme );

	// Đảm bảo rằng các loại mặc định vẫn còn.
	return array_unique( array_merge( $file_types, $default_types ) );
}

/**
 * In ra các mẫu trình sửa tệp (cho plugin và giao diện).
 *
 * @since 4.9.0
 */
function wp_print_file_editor_templates() {
	?>
	<script type="text/html" id="tmpl-wp-file-editor-notice">
		<div class="notice inline notice-{{ data.type || 'info' }} {{ data.alt ? 'notice-alt' : '' }} {{ data.dismissible ? 'is-dismissible' : '' }} {{ data.classes || '' }}">
			<# if ( 'php_error' === data.code ) { #>
				<p>
					<?php
					printf(
						/* translators: 1: Line number, 2: File path. */
						__( 'Your PHP code changes were not applied due to an error on line %1$s of file %2$s. Please fix and try saving again.' ),
						'{{ data.line }}',
						'{{ data.file }}'
					);
					?>
				</p>
				<pre>{{ data.message }}</pre>
			<# } else if ( 'file_not_writable' === data.code ) { #>
				<p>
					<?php
					printf(
						/* translators: %s: Documentation URL. */
						__( 'You need to make this file writable before you can save your changes. See <a href="%s">Changing File Permissions</a> for more information.' ),
						__( 'https://developer.wordpress.org/advanced-administration/server/file-permissions/' )
					);
					?>
				</p>
			<# } else { #>
				<p>{{ data.message || data.code }}</p>

				<# if ( 'lint_errors' === data.code ) { #>
					<p>
						<# var elementId = 'el-' + String( Math.random() ); #>
						<input id="{{ elementId }}"  type="checkbox">
						<label for="{{ elementId }}"><?php _e( 'Update anyway, even though it might break your site?' ); ?></label>
					</p>
				<# } #>
			<# } #>
			<# if ( data.dismissible ) { #>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text">
					<?php
					/* translators: Hidden accessibility text. */
					_e( 'Dismiss' );
					?>
				</span></button>
			<# } #>
		</div>
	</script>
	<?php
}

/**
 * Cố gắng chỉnh sửa một tệp cho giao diện hoặc plugin.
 *
 * Khi chỉnh sửa tệp PHP, các yêu cầu loopback sẽ được gửi đến trang quản trị và trang chủ
 * để kiểm tra xem có lỗi nghiêm trọng nào được đưa vào không. Nếu có, thay đổi PHP sẽ được
 * hoàn tác.
 *
 * @since 4.9.0
 *
 * @param string[] $args {
 *     Các tham số. Lưu ý rằng tất cả các giá trị tham số đã được unslash. Tuy nhiên, chúng
 *     đến trực tiếp từ `$_POST` và không được xác thực hoặc làm sạch dữ liệu theo bất kỳ cách nào.
 *
 *     @type string $file       Đường dẫn tương đối đến tệp.
 *     @type string $plugin     Đường dẫn đến tệp plugin tương đối với thư mục plugins.
 *     @type string $theme      Giao diện đang được chỉnh sửa.
 *     @type string $newcontent Nội dung mới cho tệp.
 *     @type string $nonce      Nonce.
 * }
 * @return true|WP_Error True khi thành công hoặc `WP_Error` khi thất bại.
 */
function wp_edit_theme_plugin_file( $args ) {
	if ( empty( $args['file'] ) ) {
		return new WP_Error( 'missing_file' );
	}

	if ( 0 !== validate_file( $args['file'] ) ) {
		return new WP_Error( 'bad_file' );
	}

	if ( ! isset( $args['newcontent'] ) ) {
		return new WP_Error( 'missing_content' );
	}

	if ( ! isset( $args['nonce'] ) ) {
		return new WP_Error( 'missing_nonce' );
	}

	$file    = $args['file'];
	$content = $args['newcontent'];

	$plugin    = null;
	$theme     = null;
	$real_file = null;

	if ( ! empty( $args['plugin'] ) ) {
		$plugin = $args['plugin'];

		if ( ! current_user_can( 'edit_plugins' ) ) {
			return new WP_Error( 'unauthorized', __( 'Sorry, you are not allowed to edit plugins for this site.' ) );
		}

		if ( ! wp_verify_nonce( $args['nonce'], 'edit-plugin_' . $file ) ) {
			return new WP_Error( 'nonce_failure' );
		}

		if ( ! array_key_exists( $plugin, get_plugins() ) ) {
			return new WP_Error( 'invalid_plugin' );
		}

		if ( 0 !== validate_file( $file, get_plugin_files( $plugin ) ) ) {
			return new WP_Error( 'bad_plugin_file_path', __( 'Sorry, that file cannot be edited.' ) );
		}

		$editable_extensions = wp_get_plugin_file_editable_extensions( $plugin );

		$real_file = WP_PLUGIN_DIR . '/' . $file;

		$is_active = in_array(
			$plugin,
			(array) get_option( 'active_plugins', array() ),
			true
		);

	} elseif ( ! empty( $args['theme'] ) ) {
		$stylesheet = $args['theme'];

		if ( 0 !== validate_file( $stylesheet ) ) {
			return new WP_Error( 'bad_theme_path' );
		}

		if ( ! current_user_can( 'edit_themes' ) ) {
			return new WP_Error( 'unauthorized', __( 'Sorry, you are not allowed to edit templates for this site.' ) );
		}

		$theme = wp_get_theme( $stylesheet );
		if ( ! $theme->exists() ) {
			return new WP_Error( 'non_existent_theme', __( 'The requested theme does not exist.' ) );
		}

		if ( ! wp_verify_nonce( $args['nonce'], 'edit-theme_' . $stylesheet . '_' . $file ) ) {
			return new WP_Error( 'nonce_failure' );
		}

		if ( $theme->errors() && 'theme_no_stylesheet' === $theme->errors()->get_error_code() ) {
			return new WP_Error(
				'theme_no_stylesheet',
				__( 'The requested theme does not exist.' ) . ' ' . $theme->errors()->get_error_message()
			);
		}

		$editable_extensions = wp_get_theme_file_editable_extensions( $theme );

		$allowed_files = array();
		foreach ( $editable_extensions as $type ) {
			switch ( $type ) {
				case 'php':
					$allowed_files = array_merge( $allowed_files, $theme->get_files( 'php', -1 ) );
					break;
				case 'css':
					$style_files                = $theme->get_files( 'css', -1 );
					$allowed_files['style.css'] = $style_files['style.css'];
					$allowed_files              = array_merge( $allowed_files, $style_files );
					break;
				default:
					$allowed_files = array_merge( $allowed_files, $theme->get_files( $type, -1 ) );
					break;
			}
		}

		// So sánh dựa trên đường dẫn tương đối.
		if ( 0 !== validate_file( $file, array_keys( $allowed_files ) ) ) {
			return new WP_Error( 'disallowed_theme_file', __( 'Sorry, that file cannot be edited.' ) );
		}

		$real_file = $theme->get_stylesheet_directory() . '/' . $file;

		$is_active = ( get_stylesheet() === $stylesheet || get_template() === $stylesheet );

	} else {
		return new WP_Error( 'missing_theme_or_plugin' );
	}

	// Đảm bảo tệp tồn tại thực sự.
	if ( ! is_file( $real_file ) ) {
		return new WP_Error( 'file_does_not_exist', __( 'File does not exist! Please double check the name and try again.' ) );
	}

	// Đảm bảo phần mở rộng tệp được phép.
	$extension = null;
	if ( preg_match( '/\.([^.]+)$/', $real_file, $matches ) ) {
		$extension = strtolower( $matches[1] );
		if ( ! in_array( $extension, $editable_extensions, true ) ) {
			return new WP_Error( 'illegal_file_type', __( 'Files of this type are not editable.' ) );
		}
	}

	$previous_content = file_get_contents( $real_file );

	if ( ! is_writable( $real_file ) ) {
		return new WP_Error( 'file_not_writable' );
	}

	$f = fopen( $real_file, 'w+' );

	if ( false === $f ) {
		return new WP_Error( 'file_not_writable' );
	}

	$written = fwrite( $f, $content );
	fclose( $f );

	if ( false === $written ) {
		return new WP_Error( 'unable_to_write', __( 'Unable to write to file.' ) );
	}

	wp_opcache_invalidate( $real_file, true );

	if ( $is_active && 'php' === $extension ) {

		$scrape_key   = md5( rand() );
		$transient    = 'scrape_key_' . $scrape_key;
		$scrape_nonce = (string) rand();
		// Không nên mất quá 60 giây để thực hiện hai yêu cầu loopback.
		set_transient( $transient, $scrape_nonce, 60 );

		$cookies       = wp_unslash( $_COOKIE );
		$scrape_params = array(
			'wp_scrape_key'   => $scrape_key,
			'wp_scrape_nonce' => $scrape_nonce,
		);
		$headers       = array(
			'Cache-Control' => 'no-cache',
		);

		/** Bộ lọc này được ghi tài liệu trong wp-includes/class-wp-http-streams.php */
		$sslverify = apply_filters( 'https_local_ssl_verify', false );

		// Bao gồm xác thực Basic trong các yêu cầu loopback.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		// Đảm bảo tiến trình PHP không kết thúc trước khi các yêu cầu loopback hoàn tất.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 5 * MINUTE_IN_SECONDS );
		}

		// Thời gian chờ các yêu cầu loopback hoàn tất.
		$timeout = 100; // 100 giây.

		$needle_start = "###### wp_scraping_result_start:$scrape_key ######";
		$needle_end   = "###### wp_scraping_result_end:$scrape_key ######";

		// Thử yêu cầu loopback đến trình sửa để xem người dùng có gây ra lỗi trắng màn hình không.
		if ( $plugin ) {
			$url = add_query_arg( compact( 'plugin', 'file' ), admin_url( 'plugin-editor.php' ) );
		} elseif ( isset( $stylesheet ) ) {
			$url = add_query_arg(
				array(
					'theme' => $stylesheet,
					'file'  => $file,
				),
				admin_url( 'theme-editor.php' )
			);
		} else {
			$url = admin_url();
		}

		if ( function_exists( 'session_status' ) && PHP_SESSION_ACTIVE === session_status() ) {
			/*
			 * Đóng mọi phiên đang hoạt động để ngăn các yêu cầu HTTP bị hết thời gian chờ
			 * khi cố gắng kết nối lại trang web.
			 */
			session_write_close();
		}

		$url                    = add_query_arg( $scrape_params, $url );
		$r                      = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout', 'sslverify' ) );
		$body                   = wp_remote_retrieve_body( $r );
		$scrape_result_position = strpos( $body, $needle_start );

		$loopback_request_failure = array(
			'code'    => 'loopback_request_failed',
			'message' => __( 'Unable to communicate back with site to check for fatal errors, so the PHP change was reverted. You will need to upload your PHP file change by some other means, such as by using SFTP.' ),
		);
		$json_parse_failure       = array(
			'code' => 'json_parse_error',
		);

		$result = null;

		if ( false === $scrape_result_position ) {
			$result = $loopback_request_failure;
		} else {
			$error_output = substr( $body, $scrape_result_position + strlen( $needle_start ) );
			$error_output = substr( $error_output, 0, strpos( $error_output, $needle_end ) );
			$result       = json_decode( trim( $error_output ), true );
			if ( empty( $result ) ) {
				$result = $json_parse_failure;
			}
		}

		// Thử gửi yêu cầu đến trang chủ để xem khách truy cập có bị lỗi trắng màn hình không.
		if ( true === $result ) {
			$url                    = home_url( '/' );
			$url                    = add_query_arg( $scrape_params, $url );
			$r                      = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout', 'sslverify' ) );
			$body                   = wp_remote_retrieve_body( $r );
			$scrape_result_position = strpos( $body, $needle_start );

			if ( false === $scrape_result_position ) {
				$result = $loopback_request_failure;
			} else {
				$error_output = substr( $body, $scrape_result_position + strlen( $needle_start ) );
				$error_output = substr( $error_output, 0, strpos( $error_output, $needle_end ) );
				$result       = json_decode( trim( $error_output ), true );
				if ( empty( $result ) ) {
					$result = $json_parse_failure;
				}
			}
		}

		delete_transient( $transient );

		if ( true !== $result ) {
			// Hoàn tác thay đổi tệp.
			file_put_contents( $real_file, $previous_content );
			wp_opcache_invalidate( $real_file, true );

			if ( ! isset( $result['message'] ) ) {
				$message = __( 'An error occurred. Please try again later.' );
			} else {
				$message = $result['message'];
				unset( $result['message'] );
			}

			return new WP_Error( 'php_error', $message, $result );
		}
	}

	if ( $theme instanceof WP_Theme ) {
		$theme->cache_delete();
	}

	return true;
}


/**
 * Trả về tên tệp tạm thời duy nhất.
 *
 * Xin lưu ý rằng hàm gọi phải xóa hoặc di chuyển tệp.
 *
 * Tên tệp dựa trên tham số được truyền vào hoặc mặc định là dấu thời gian unix hiện tại,
 * trong khi thư mục có thể được truyền vào, hoặc để trống sẽ mặc định là thư mục
 * tạm thời có thể ghi.
 *
 * @since 2.6.0
 *
 * @param string $filename Tùy chọn. Tên tệp cơ sở cho tệp duy nhất. Mặc định rỗng.
 * @param string $dir      Tùy chọn. Thư mục để lưu trữ tệp. Mặc định rỗng.
 * @return string Tên tệp có thể ghi.
 */
function wp_tempnam( $filename = '', $dir = '' ) {
	if ( empty( $dir ) ) {
		$dir = get_temp_dir();
	}

	if ( empty( $filename ) || in_array( $filename, array( '.', '/', '\\' ), true ) ) {
		$filename = uniqid();
	}

	// Sử dụng tên cơ sở của tệp đã cho (không có phần mở rộng) làm tên cho thư mục tạm thời.
	$temp_filename = basename( $filename );
	$temp_filename = preg_replace( '|\.[^.]*$|', '', $temp_filename );

	// Nếu thư mục là giá trị falsey, sử dụng tên thư mục cha thay thế.
	if ( ! $temp_filename ) {
		return wp_tempnam( dirname( $filename ), $dir );
	}

	// Thêm hậu tố dữ liệu ngẫu nhiên để tránh xung đột tên tệp.
	$temp_filename .= '-' . wp_generate_password( 6, false );
	$temp_filename .= '.tmp';
	$temp_filename  = wp_unique_filename( $dir, $temp_filename );

	/*
	 * Hệ thống tệp thường có giới hạn 255 ký tự cho tên tệp.
	 *
	 * Nếu tên tệp duy nhất được tạo vượt quá giới hạn này, cắt ngắn tên tệp
	 * ban đầu và thử lại.
	 *
	 * Vì có khả năng tên tệp bị cắt ngắn có thể đã tồn tại, tạo ra hậu tố
	 * "-1" hoặc "-10" có thể vượt quá giới hạn một lần nữa, nên cắt ngắn
	 * xuống còn 252 thay thế.
	 */
	$characters_over_limit = strlen( $temp_filename ) - 252;
	if ( $characters_over_limit > 0 ) {
		$filename = substr( $filename, 0, -$characters_over_limit );
		return wp_tempnam( $filename, $dir );
	}

	$temp_filename = $dir . $temp_filename;

	$fp = @fopen( $temp_filename, 'x' );

	if ( ! $fp && is_writable( $dir ) && file_exists( $temp_filename ) ) {
		return wp_tempnam( $filename, $dir );
	}

	if ( $fp ) {
		fclose( $fp );
	}

	return $temp_filename;
}

/**
 * Đảm bảo rằng tệp được yêu cầu chỉnh sửa có được phép chỉnh sửa.
 *
 * Hàm sẽ dừng thực thi nếu bạn không được phép chỉnh sửa tệp.
 *
 * @since 1.5.0
 *
 * @param string   $file          Tệp mà người dùng đang cố chỉnh sửa.
 * @param string[] $allowed_files Tùy chọn. Mảng các tệp được phép chỉnh sửa.
 *                                `$file` phải khớp chính xác với một mục.
 * @return string|void Trả về tên tệp khi thành công, dừng thực thi khi thất bại.
 */
function validate_file_to_edit( $file, $allowed_files = array() ) {
	$code = validate_file( $file, $allowed_files );

	if ( ! $code ) {
		return $file;
	}

	switch ( $code ) {
		case 1:
			wp_die( __( 'Sorry, that file cannot be edited.' ) );

			// case 2 :
			// wp_die( __('Sorry, cannot call files with their real path.' ));

		case 3:
			wp_die( __( 'Sorry, that file cannot be edited.' ) );
	}
}

/**
 * Xử lý tải lên tệp PHP trong WordPress.
 *
 * Làm sạch tên tệp, kiểm tra phần mở rộng cho loại mime, và di chuyển tệp
 * đến thư mục phù hợp trong thư mục uploads.
 *
 * @access private
 * @since 4.0.0
 *
 * @see wp_handle_upload_error
 *
 * @param array       $file      {
 *     Tham chiếu đến một phần tử đơn từ `$_FILES`. Gọi hàm một lần cho mỗi tệp được tải lên.
 *
 *     @type string $name     Tên gốc của tệp trên máy khách.
 *     @type string $type     Loại mime của tệp, nếu trình duyệt cung cấp thông tin này.
 *     @type string $tmp_name Tên tệp tạm thời nơi tệp tải lên được lưu trữ trên máy chủ.
 *     @type int    $size     Kích thước tệp tải lên, tính bằng byte.
 *     @type int    $error    Mã lỗi liên quan đến việc tải lên tệp này.
 * }
 * @param array|false $overrides {
 *     Mảng các tham số ghi đè cho tệp này, hoặc boolean false nếu không có tham số nào được cung cấp.
 *
 *     @type callable $upload_error_handler     Hàm gọi khi có lỗi trong quá trình tải lên.
 *                                              Xem {@see wp_handle_upload_error()}.
 *     @type callable $unique_filename_callback Hàm gọi khi xác định tên tệp duy nhất cho tệp.
 *                                              Xem {@see wp_unique_filename()}.
 *     @type string[] $upload_error_strings     Các chuỗi mô tả lỗi được chỉ ra trong
 *                                              `$_FILES[{form field}]['error']`.
 *     @type bool     $test_form                Có kiểm tra tham số `$_POST['action']` đúng như mong đợi không.
 *     @type bool     $test_size                Có kiểm tra kích thước tệp lớn hơn 0 byte không.
 *     @type bool     $test_type                Có kiểm tra loại mime của tệp đúng như mong đợi không.
 *     @type string[] $mimes                    Mảng các loại mime được phép, khóa bởi regex phần mở rộng tệp.
 * }
 * @param string      $time      Thời gian được định dạng theo 'yyyy/mm'.
 * @param string      $action    Giá trị mong đợi cho `$_POST['action']`.
 * @return array {
 *     Khi thành công, trả về mảng kết hợp các thuộc tính tệp.
 *     Khi thất bại, trả về `$overrides['upload_error_handler']( &$file, $message )`
 *     hoặc `array( 'error' => $message )`.
 *
 *     @type string $file Tên tệp của tệp vừa tải lên.
 *     @type string $url  URL của tệp vừa tải lên.
 *     @type string $type Loại mime của tệp vừa tải lên.
 * }
 */
function _wp_handle_upload( &$file, $overrides, $time, $action ) {
	// Hàm xử lý lỗi mặc định.
	if ( ! function_exists( 'wp_handle_upload_error' ) ) {
		function wp_handle_upload_error( &$file, $message ) {
			return array( 'error' => $message );
		}
	}

	/**
	 * Lọc dữ liệu cho một tệp trước khi nó được tải lên WordPress.
	 *
	 * Phần động của tên hook, `$action`, tham chiếu đến hành động bài viết.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `wp_handle_sideload_prefilter`
	 *  - `wp_handle_upload_prefilter`
	 *
	 * @since 2.9.0 với tên 'wp_handle_upload_prefilter'.
	 * @since 4.0.0 Chuyển đổi thành hook động với `$action`.
	 *
	 * @param array $file {
	 *     Tham chiếu đến một phần tử đơn từ `$_FILES`.
	 *
	 *     @type string $name     Tên gốc của tệp trên máy khách.
	 *     @type string $type     Loại mime của tệp, nếu trình duyệt cung cấp thông tin này.
	 *     @type string $tmp_name Tên tệp tạm thời nơi tệp tải lên được lưu trữ trên máy chủ.
	 *     @type int    $size     Kích thước tệp tải lên, tính bằng byte.
	 *     @type int    $error    Mã lỗi liên quan đến việc tải lên tệp này.
	 * }
	 */
	$file = apply_filters( "{$action}_prefilter", $file );

	/**
	 * Lọc các tham số ghi đè cho một tệp trước khi nó được tải lên WordPress.
	 *
	 * Phần động của tên hook, `$action`, tham chiếu đến hành động bài viết.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `wp_handle_sideload_overrides`
	 *  - `wp_handle_upload_overrides`
	 *
	 * @since 5.7.0
	 *
	 * @param array|false $overrides Mảng các tham số ghi đè cho tệp này. Boolean false nếu không có
	 *                               tham số nào được cung cấp. Xem {@see _wp_handle_upload()}.
	 * @param array       $file      {
	 *     Tham chiếu đến một phần tử đơn từ `$_FILES`.
	 *
	 *     @type string $name     Tên gốc của tệp trên máy khách.
	 *     @type string $type     Loại mime của tệp, nếu trình duyệt cung cấp thông tin này.
	 *     @type string $tmp_name Tên tệp tạm thời nơi tệp tải lên được lưu trữ trên máy chủ.
	 *     @type int    $size     Kích thước tệp tải lên, tính bằng byte.
	 *     @type int    $error    Mã lỗi liên quan đến việc tải lên tệp này.
	 * }
	 */
	$overrides = apply_filters( "{$action}_overrides", $overrides, $file );

	// Bạn có thể định nghĩa hàm riêng và truyền tên vào $overrides['upload_error_handler'].
	$upload_error_handler = 'wp_handle_upload_error';
	if ( isset( $overrides['upload_error_handler'] ) ) {
		$upload_error_handler = $overrides['upload_error_handler'];
	}

	// Có thể một hoặc nhiều hàm 'wp_handle_upload_prefilter' đã báo lỗi cho tệp. Xử lý điều đó một cách nhẹ nhàng.
	if ( isset( $file['error'] ) && ! is_numeric( $file['error'] ) && $file['error'] ) {
		return call_user_func_array( $upload_error_handler, array( &$file, $file['error'] ) );
	}

	// Cài đặt các ghi đè của người dùng. Lưu ý rằng điều này sẽ vô hiệu bảo hành của bạn.

	// Bạn có thể định nghĩa hàm riêng và truyền tên vào $overrides['unique_filename_callback'].
	$unique_filename_callback = null;
	if ( isset( $overrides['unique_filename_callback'] ) ) {
		$unique_filename_callback = $overrides['unique_filename_callback'];
	}

	/*
	 * Điều này ban đầu có thể không được dự định cho phép ghi đè,
	 * nhưng trong lịch sử đã cho phép.
	 */
	if ( isset( $overrides['upload_error_strings'] ) ) {
		$upload_error_strings = $overrides['upload_error_strings'];
	} else {
		// Theo php.net, các chuỗi mô tả lỗi được chỉ ra trong $_FILES[{form field}]['error'].
		$upload_error_strings = array(
			false,
			sprintf(
				/* translators: 1: upload_max_filesize, 2: php.ini */
				__( 'The uploaded file exceeds the %1$s directive in %2$s.' ),
				'upload_max_filesize',
				'php.ini'
			),
			sprintf(
				/* translators: %s: MAX_FILE_SIZE */
				__( 'The uploaded file exceeds the %s directive that was specified in the HTML form.' ),
				'MAX_FILE_SIZE'
			),
			__( 'The uploaded file was only partially uploaded.' ),
			__( 'No file was uploaded.' ),
			'',
			__( 'Missing a temporary folder.' ),
			__( 'Failed to write file to disk.' ),
			__( 'File upload stopped by extension.' ),
		);
	}

	// Tất cả các kiểm tra được bật mặc định. Hầu hết có thể được tắt bằng $overrides[{test_name}] = false;
	$test_form = isset( $overrides['test_form'] ) ? $overrides['test_form'] : true;
	$test_size = isset( $overrides['test_size'] ) ? $overrides['test_size'] : true;

	// Nếu bạn ghi đè điều này, bạn phải cung cấp $ext và $type!!
	$test_type = isset( $overrides['test_type'] ) ? $overrides['test_type'] : true;
	$mimes     = isset( $overrides['mimes'] ) ? $overrides['mimes'] : null;

	// Một form post đúng sẽ vượt qua kiểm tra này.
	if ( $test_form && ( ! isset( $_POST['action'] ) || $_POST['action'] !== $action ) ) {
		return call_user_func_array( $upload_error_handler, array( &$file, __( 'Invalid form submission.' ) ) );
	}

	// Một tải lên thành công sẽ vượt qua kiểm tra này. Không có lý do để ghi đè kiểm tra này.
	if ( isset( $file['error'] ) && $file['error'] > 0 ) {
		return call_user_func_array( $upload_error_handler, array( &$file, $upload_error_strings[ $file['error'] ] ) );
	}

	// Một tệp được tải lên đúng cách sẽ vượt qua kiểm tra này. Không nên có lý do để ghi đè kiểm tra này.
	$test_uploaded_file = 'wp_handle_upload' === $action ? is_uploaded_file( $file['tmp_name'] ) : @is_readable( $file['tmp_name'] );
	if ( ! $test_uploaded_file ) {
		return call_user_func_array( $upload_error_handler, array( &$file, __( 'Specified file failed upload test.' ) ) );
	}

	$test_file_size = 'wp_handle_upload' === $action ? $file['size'] : filesize( $file['tmp_name'] );
	// Một tệp không rỗng sẽ vượt qua kiểm tra này.
	if ( $test_size && ! ( $test_file_size > 0 ) ) {
		if ( is_multisite() ) {
			$error_msg = __( 'File is empty. Please upload something more substantial.' );
		} else {
			$error_msg = sprintf(
				/* translators: 1: php.ini, 2: post_max_size, 3: upload_max_filesize */
				__( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your %1$s file or by %2$s being defined as smaller than %3$s in %1$s.' ),
				'php.ini',
				'post_max_size',
				'upload_max_filesize'
			);
		}

		return call_user_func_array( $upload_error_handler, array( &$file, $error_msg ) );
	}

	// Một loại MIME đúng sẽ vượt qua kiểm tra này. Ghi đè $mimes hoặc sử dụng bộ lọc upload_mimes.
	if ( $test_type ) {
		$wp_filetype     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
		$ext             = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
		$type            = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
		$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];

		// Kiểm tra xem wp_check_filetype_and_ext() có xác định tên tệp không chính xác không.
		if ( $proper_filename ) {
			$file['name'] = $proper_filename;
		}

		if ( ( ! $type || ! $ext ) && ! current_user_can( 'unfiltered_upload' ) ) {
			return call_user_func_array( $upload_error_handler, array( &$file, __( 'Sorry, you are not allowed to upload this file type.' ) ) );
		}

		if ( ! $type ) {
			$type = $file['type'];
		}
	} else {
		$type = '';
	}

	/*
	 * Một thư mục uploads có quyền ghi sẽ vượt qua kiểm tra này. Một lần nữa, không có lý do
	 * để ghi đè kiểm tra này.
	 */
	$uploads = wp_upload_dir( $time );
	if ( ! ( $uploads && false === $uploads['error'] ) ) {
		return call_user_func_array( $upload_error_handler, array( &$file, $uploads['error'] ) );
	}

	$filename = wp_unique_filename( $uploads['path'], $file['name'], $unique_filename_callback );

	// Di chuyển tệp đến thư mục uploads.
	$new_file = $uploads['path'] . "/$filename";

	/**
	 * Lọc có bỏ qua việc di chuyển tệp tải lên sau khi vượt qua tất cả kiểm tra hay không.
	 *
	 * Nếu một giá trị khác null được trả về từ bộ lọc, việc di chuyển tệp và bất kỳ
	 * báo cáo lỗi liên quan nào sẽ bị bỏ qua hoàn toàn.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed    $move_new_file Nếu null (mặc định) di chuyển tệp sau khi tải lên.
	 * @param array    $file          {
	 *     Tham chiếu đến một phần tử đơn từ `$_FILES`.
	 *
	 *     @type string $name     Tên gốc của tệp trên máy khách.
	 *     @type string $type     Loại mime của tệp, nếu trình duyệt cung cấp thông tin này.
	 *     @type string $tmp_name Tên tệp tạm thời nơi tệp tải lên được lưu trữ trên máy chủ.
	 *     @type int    $size     Kích thước tệp tải lên, tính bằng byte.
	 *     @type int    $error    Mã lỗi liên quan đến việc tải lên tệp này.
	 * }
	 * @param string   $new_file      Tên tệp của tệp vừa tải lên.
	 * @param string   $type          Loại mime của tệp vừa tải lên.
	 */
	$move_new_file = apply_filters( 'pre_move_uploaded_file', null, $file, $new_file, $type );

	if ( null === $move_new_file ) {
		if ( 'wp_handle_upload' === $action ) {
			$move_new_file = @move_uploaded_file( $file['tmp_name'], $new_file );
		} else {
			// Sử dụng copy và unlink vì rename làm hỏng luồng dữ liệu.
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$move_new_file = @copy( $file['tmp_name'], $new_file );
			unlink( $file['tmp_name'] );
		}

		if ( false === $move_new_file ) {
			if ( str_starts_with( $uploads['basedir'], ABSPATH ) ) {
				$error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
			} else {
				$error_path = basename( $uploads['basedir'] ) . $uploads['subdir'];
			}

			return $upload_error_handler(
				$file,
				sprintf(
					/* translators: %s: Destination file path. */
					__( 'The uploaded file could not be moved to %s.' ),
					$error_path
				)
			);
		}
	}

	// Đặt quyền tệp đúng.
	$stat  = stat( dirname( $new_file ) );
	$perms = $stat['mode'] & 0000666;
	chmod( $new_file, $perms );

	// Tính toán URL.
	$url = $uploads['url'] . "/$filename";

	if ( is_multisite() ) {
		clean_dirsize_cache( $new_file );
	}

	/**
	 * Lọc mảng dữ liệu cho tệp đã tải lên.
	 *
	 * @since 2.1.0
	 *
	 * @param array  $upload {
	 *     Mảng dữ liệu tải lên.
	 *
	 *     @type string $file Tên tệp của tệp vừa tải lên.
	 *     @type string $url  URL của tệp vừa tải lên.
	 *     @type string $type Loại mime của tệp vừa tải lên.
	 * }
	 * @param string $context Loại hành động tải lên. Các giá trị bao gồm 'upload' hoặc 'sideload'.
	 */
	return apply_filters(
		'wp_handle_upload',
		array(
			'file' => $new_file,
			'url'  => $url,
			'type' => $type,
		),
		'wp_handle_sideload' === $action ? 'sideload' : 'upload'
	);
}

/**
 * Hàm bọc cho _wp_handle_upload().
 *
 * Truyền action {@see 'wp_handle_upload'}.
 *
 * @since 2.0.0
 *
 * @see _wp_handle_upload()
 *
 * @param array       $file      Tham chiếu đến một phần tử đơn của `$_FILES`.
 *                               Gọi hàm một lần cho mỗi tệp được tải lên.
 *                               Xem _wp_handle_upload() để biết các giá trị được chấp nhận.
 * @param array|false $overrides Tùy chọn. Mảng kết hợp tên => giá trị
 *                               để ghi đè các biến mặc định. Mặc định false.
 *                               Xem _wp_handle_upload() để biết các giá trị được chấp nhận.
 * @param string|null $time      Tùy chọn. Thời gian định dạng 'yyyy/mm'. Mặc định null.
 * @return array Xem _wp_handle_upload() để biết giá trị trả về.
 */
function wp_handle_upload( &$file, $overrides = false, $time = null ) {
	/*
	 *  $_POST['action'] phải được thiết lập và giá trị của nó phải bằng $overrides['action']
	 *  hoặc giá trị sau:
	 */
	$action = 'wp_handle_upload';
	if ( isset( $overrides['action'] ) ) {
		$action = $overrides['action'];
	}

	return _wp_handle_upload( $file, $overrides, $time, $action );
}

/**
 * Hàm bọc cho _wp_handle_upload().
 *
 * Truyền action {@see 'wp_handle_sideload'}.
 *
 * @since 2.6.0
 *
 * @see _wp_handle_upload()
 *
 * @param array       $file      Tham chiếu đến một phần tử đơn của `$_FILES`.
 *                               Gọi hàm một lần cho mỗi tệp được tải lên.
 *                               Xem _wp_handle_upload() để biết các giá trị được chấp nhận.
 * @param array|false $overrides Tùy chọn. Mảng kết hợp tên => giá trị
 *                               để ghi đè các biến mặc định. Mặc định false.
 *                               Xem _wp_handle_upload() để biết các giá trị được chấp nhận.
 * @param string|null $time      Tùy chọn. Thời gian định dạng 'yyyy/mm'. Mặc định null.
 * @return array Xem _wp_handle_upload() để biết giá trị trả về.
 */
function wp_handle_sideload( &$file, $overrides = false, $time = null ) {
	/*
	 *  $_POST['action'] phải được thiết lập và giá trị của nó phải bằng $overrides['action']
	 *  hoặc giá trị sau:
	 */
	$action = 'wp_handle_sideload';
	if ( isset( $overrides['action'] ) ) {
		$action = $overrides['action'];
	}

	return _wp_handle_upload( $file, $overrides, $time, $action );
}

/**
 * Tải xuống một URL đến tệp tạm thời cục bộ sử dụng API HTTP WordPress.
 *
 * Xin lưu ý rằng hàm gọi phải xóa hoặc di chuyển tệp.
 *
 * @since 2.5.0
 * @since 5.2.0 Thêm Xác minh Chữ ký với SoftFail.
 * @since 5.9.0 Thêm hỗ trợ cho tên tệp Content-Disposition.
 *
 * @param string $url                    URL của tệp cần tải xuống.
 * @param int    $timeout                Thời gian chờ cho yêu cầu tải xuống tệp.
 *                                       Mặc định 300 giây.
 * @param bool   $signature_verification Có thực hiện Xác minh Chữ ký hay không.
 *                                       Mặc định false.
 * @return string|WP_Error Tên tệp khi thành công, WP_Error khi thất bại.
 */
function download_url( $url, $timeout = 300, $signature_verification = false ) {
	// CẢNH BÁO: Tệp không được tự động xóa, script phải xóa hoặc di chuyển tệp.
	if ( ! $url ) {
		return new WP_Error( 'http_no_url', __( 'No URL Provided.' ) );
	}

	$url_path     = parse_url( $url, PHP_URL_PATH );
	$url_filename = '';
	if ( is_string( $url_path ) && '' !== $url_path ) {
		$url_filename = basename( $url_path );
	}

	$tmpfname = wp_tempnam( $url_filename );
	if ( ! $tmpfname ) {
		return new WP_Error( 'http_no_file', __( 'Could not create temporary file.' ) );
	}

	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout'  => $timeout,
			'stream'   => true,
			'filename' => $tmpfname,
		)
	);

	if ( is_wp_error( $response ) ) {
		unlink( $tmpfname );
		return $response;
	}

	$response_code = wp_remote_retrieve_response_code( $response );

	if ( 200 !== $response_code ) {
		$data = array(
			'code' => $response_code,
		);

		// Lấy một mẫu nội dung phản hồi để phục vụ mục đích gỡ lỗi.
		$tmpf = fopen( $tmpfname, 'rb' );

		if ( $tmpf ) {
			/**
			 * Lọc kích thước tối đa nội dung phản hồi lỗi trong `download_url()`.
			 *
			 * @since 5.1.0
			 *
			 * @see download_url()
			 *
			 * @param int $size Kích thước tối đa nội dung phản hồi lỗi. Mặc định 1 KB.
			 */
			$response_size = apply_filters( 'download_url_error_max_body_size', KB_IN_BYTES );

			$data['body'] = fread( $tmpf, $response_size );
			fclose( $tmpf );
		}

		unlink( $tmpfname );

		return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ), $data );
	}

	$content_disposition = wp_remote_retrieve_header( $response, 'Content-Disposition' );

	if ( $content_disposition ) {
		$content_disposition = strtolower( $content_disposition );

		if ( str_starts_with( $content_disposition, 'attachment; filename=' ) ) {
			$tmpfname_disposition = sanitize_file_name( substr( $content_disposition, 21 ) );
		} else {
			$tmpfname_disposition = '';
		}

		// Tên tệp tiềm năng phải là chuỗi hợp lệ.
		if ( $tmpfname_disposition && is_string( $tmpfname_disposition )
			&& ( 0 === validate_file( $tmpfname_disposition ) )
		) {
			$tmpfname_disposition = dirname( $tmpfname ) . '/' . $tmpfname_disposition;

			if ( rename( $tmpfname, $tmpfname_disposition ) ) {
				$tmpfname = $tmpfname_disposition;
			}

			if ( ( $tmpfname !== $tmpfname_disposition ) && file_exists( $tmpfname_disposition ) ) {
				unlink( $tmpfname_disposition );
			}
		}
	}

	$mime_type = wp_remote_retrieve_header( $response, 'content-type' );
	if ( $mime_type && 'tmp' === pathinfo( $tmpfname, PATHINFO_EXTENSION ) ) {
		$valid_mime_types = array_flip( get_allowed_mime_types() );
		if ( ! empty( $valid_mime_types[ $mime_type ] ) ) {
			$extensions     = explode( '|', $valid_mime_types[ $mime_type ] );
			$new_image_name = substr( $tmpfname, 0, -4 ) . ".{$extensions[0]}";
			if ( 0 === validate_file( $new_image_name ) ) {
				if ( rename( $tmpfname, $new_image_name ) ) {
					$tmpfname = $new_image_name;
				}

				if ( ( $tmpfname !== $new_image_name ) && file_exists( $new_image_name ) ) {
					unlink( $new_image_name );
				}
			}
		}
	}

	$content_md5 = wp_remote_retrieve_header( $response, 'Content-MD5' );

	if ( $content_md5 ) {
		$md5_check = verify_file_md5( $tmpfname, $content_md5 );

		if ( is_wp_error( $md5_check ) ) {
			unlink( $tmpfname );
			return $md5_check;
		}
	}

	// Nếu hàm gọi mong đợi xác minh chữ ký xảy ra, kiểm tra xem URL này có hỗ trợ không.
	if ( $signature_verification ) {
		/**
		 * Lọc danh sách các host cần thực hiện Xác minh Chữ ký.
		 *
		 * @since 5.2.0
		 *
		 * @param string[] $hostnames Danh sách tên host.
		 */
		$signed_hostnames = apply_filters( 'wp_signature_hosts', array( 'wordpress.org', 'downloads.wordpress.org', 's.w.org' ) );

		$signature_verification = in_array( parse_url( $url, PHP_URL_HOST ), $signed_hostnames, true );
	}

	// Thực hiện xác minh chữ ký nếu được hỗ trợ.
	if ( $signature_verification ) {
		$signature = wp_remote_retrieve_header( $response, 'X-Content-Signature' );

		if ( ! $signature ) {
			/*
			 * Lấy chữ ký từ một tệp nếu header không được bao gồm.
			 * WordPress.org lưu trữ chữ ký tại $package_url.sig.
			 */

			$signature_url = false;

			if ( is_string( $url_path ) && ( str_ends_with( $url_path, '.zip' ) || str_ends_with( $url_path, '.tar.gz' ) ) ) {
				$signature_url = str_replace( $url_path, $url_path . '.sig', $url );
			}

			/**
			 * Lọc URL nơi chữ ký cho một tệp được lưu trữ.
			 *
			 * @since 5.2.0
			 *
			 * @param false|string $signature_url URL nơi có thể tìm thấy chữ ký cho tệp, hoặc false nếu không biết.
			 * @param string $url                 URL đang được xác minh.
			 */
			$signature_url = apply_filters( 'wp_signature_url', $signature_url, $url );

			if ( $signature_url ) {
				$signature_request = wp_safe_remote_get(
					$signature_url,
					array(
						'limit_response_size' => 10 * KB_IN_BYTES, // 10KB should be large enough for quite a few signatures.
					)
				);

				if ( ! is_wp_error( $signature_request ) && 200 === wp_remote_retrieve_response_code( $signature_request ) ) {
					$signature = explode( "\n", wp_remote_retrieve_body( $signature_request ) );
				}
			}
		}

		// Thực hiện các kiểm tra.
		$signature_verification = verify_file_signature( $tmpfname, $signature, $url_filename );
	}

	if ( is_wp_error( $signature_verification ) ) {
		if (
			/**
			 * Lọc xem các lỗi Xác minh Chữ ký có được phép thất bại nhẹ hay không.
			 *
			 * CẢNH BÁO: Điều này có thể bị loại bỏ trong phiên bản tương lai.
			 *
			 * @since 5.2.0
			 *
			 * @param bool   $signature_softfail Có cho phép thất bại nhẹ hay không.
			 * @param string $url                URL đang được truy cập.
			 */
			apply_filters( 'wp_signature_softfail', true, $url )
		) {
			$signature_verification->add_data( $tmpfname, 'softfail-filename' );
		} else {
			// Thất bại cứng.
			unlink( $tmpfname );
		}

		return $signature_verification;
	}

	return $tmpfname;
}

/**
 * Tính toán và so sánh MD5 của một tệp với giá trị mong đợi.
 *
 * @since 3.7.0
 *
 * @param string $filename     Tên tệp cần kiểm tra MD5.
 * @param string $expected_md5 MD5 mong đợi của tệp, là md5 thô được mã hóa base64,
 *                             hoặc md5 được mã hóa hex.
 * @return bool|WP_Error True khi thành công, false khi định dạng MD5 không xác định/không mong đợi,
 *                       WP_Error khi thất bại.
 */
function verify_file_md5( $filename, $expected_md5 ) {
	if ( 32 === strlen( $expected_md5 ) ) {
		$expected_raw_md5 = pack( 'H*', $expected_md5 );
	} elseif ( 24 === strlen( $expected_md5 ) ) {
		$expected_raw_md5 = base64_decode( $expected_md5 );
	} else {
		return false; // Định dạng không xác định.
	}

	$file_md5 = md5_file( $filename, true );

	if ( $file_md5 === $expected_raw_md5 ) {
		return true;
	}

	return new WP_Error(
		'md5_mismatch',
		sprintf(
			/* translators: 1: File checksum, 2: Expected checksum value. */
			__( 'The checksum of the file (%1$s) does not match the expected checksum value (%2$s).' ),
			bin2hex( $file_md5 ),
			bin2hex( $expected_raw_md5 )
		)
	);
}

/**
 * Xác minh nội dung của một tệp dựa trên chữ ký ED25519 của nó.
 *
 * @since 5.2.0
 *
 * @param string       $filename            Tệp cần xác thực.
 * @param string|array $signatures          Chữ ký được cung cấp cho tệp.
 * @param string|false $filename_for_errors Tùy chọn. Tên tệp thân thiện cho thông báo lỗi.
 * @return bool|WP_Error True khi thành công, false nếu xác minh không được thực hiện,
 *                       hoặc WP_Error mô tả tình trạng lỗi.
 */
function verify_file_signature( $filename, $signatures, $filename_for_errors = false ) {
	if ( ! $filename_for_errors ) {
		$filename_for_errors = wp_basename( $filename );
	}

	// Kiểm tra xem chúng ta có thể xử lý chữ ký hay không.
	if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) || ! in_array( 'sha384', array_map( 'strtolower', hash_algos() ), true ) ) {
		return new WP_Error(
			'signature_verification_unsupported',
			sprintf(
				/* translators: %s: The filename of the package. */
				__( 'The authenticity of %s could not be verified as signature verification is unavailable on this system.' ),
				'<span class="code">' . esc_html( $filename_for_errors ) . '</span>'
			),
			( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ? 'sodium_crypto_sign_verify_detached' : 'sha384' )
		);
	}

	// Xác minh tốc độ chạy của Sodium_Compat có thể chấp nhận được.
	if ( ! extension_loaded( 'sodium' ) && ! ParagonIE_Sodium_Compat::polyfill_is_fast() ) {
		$sodium_compat_is_fast = false;

		// Cho phép phiên bản cũ của Sodium_Compat được tải trước phiên bản đi kèm WordPress.
		if ( method_exists( 'ParagonIE_Sodium_Compat', 'runtime_speed_test' ) ) {
			/*
			 * Chạy `ParagonIE_Sodium_Compat::runtime_speed_test()` ở chế độ số nguyên tối ưu,
			 * vì đó là những gì WordPress sử dụng trong quá trình xác minh chữ ký.
			 */
			// phpcs:disable WordPress.NamingConventions.ValidVariableName
			$old_fastMult                      = ParagonIE_Sodium_Compat::$fastMult;
			ParagonIE_Sodium_Compat::$fastMult = true;
			$sodium_compat_is_fast             = ParagonIE_Sodium_Compat::runtime_speed_test( 100, 10 );
			ParagonIE_Sodium_Compat::$fastMult = $old_fastMult;
			// phpcs:enable
		}

		/*
		 * Điều này không thể được thực hiện trong một khoảng thời gian hợp lý.
		 * https://github.com/paragonie/sodium_compat#help-sodium_compat-is-slow-how-can-i-make-it-fast
		 */
		if ( ! $sodium_compat_is_fast ) {
			return new WP_Error(
				'signature_verification_unsupported',
				sprintf(
					/* translators: %s: The filename of the package. */
					__( 'The authenticity of %s could not be verified as signature verification is unavailable on this system.' ),
					'<span class="code">' . esc_html( $filename_for_errors ) . '</span>'
				),
				array(
					'php'                => PHP_VERSION,
					'sodium'             => defined( 'SODIUM_LIBRARY_VERSION' ) ? SODIUM_LIBRARY_VERSION : ( defined( 'ParagonIE_Sodium_Compat::VERSION_STRING' ) ? ParagonIE_Sodium_Compat::VERSION_STRING : false ),
					'polyfill_is_fast'   => false,
					'max_execution_time' => ini_get( 'max_execution_time' ),
				)
			);
		}
	}

	if ( ! $signatures ) {
		return new WP_Error(
			'signature_verification_no_signature',
			sprintf(
				/* translators: %s: The filename of the package. */
				__( 'The authenticity of %s could not be verified as no signature was found.' ),
				'<span class="code">' . esc_html( $filename_for_errors ) . '</span>'
			),
			array(
				'filename' => $filename_for_errors,
			)
		);
	}

	$trusted_keys = wp_trusted_keys();
	$file_hash    = hash_file( 'sha384', $filename, true );

	mbstring_binary_safe_encoding();

	$skipped_key       = 0;
	$skipped_signature = 0;

	foreach ( (array) $signatures as $signature ) {
		$signature_raw = base64_decode( $signature );

		// Đảm bảo chỉ xét các chữ ký có độ dài hợp lệ.
		if ( SODIUM_CRYPTO_SIGN_BYTES !== strlen( $signature_raw ) ) {
			++$skipped_signature;
			continue;
		}

		foreach ( (array) $trusted_keys as $key ) {
			$key_raw = base64_decode( $key );

			// Chỉ cho phép các khóa công khai hợp lệ đi qua.
			if ( SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $key_raw ) ) {
				++$skipped_key;
				continue;
			}

			if ( sodium_crypto_sign_verify_detached( $signature_raw, $file_hash, $key_raw ) ) {
				reset_mbstring_encoding();
				return true;
			}
		}
	}

	reset_mbstring_encoding();

	return new WP_Error(
		'signature_verification_failed',
		sprintf(
			/* translators: %s: The filename of the package. */
			__( 'The authenticity of %s could not be verified.' ),
			'<span class="code">' . esc_html( $filename_for_errors ) . '</span>'
		),
		// Dữ liệu lỗi hữu ích cho việc gỡ lỗi:
		array(
			'filename'    => $filename_for_errors,
			'keys'        => $trusted_keys,
			'signatures'  => $signatures,
			'hash'        => bin2hex( $file_hash ),
			'skipped_key' => $skipped_key,
			'skipped_sig' => $skipped_signature,
			'php'         => PHP_VERSION,
			'sodium'      => defined( 'SODIUM_LIBRARY_VERSION' ) ? SODIUM_LIBRARY_VERSION : ( defined( 'ParagonIE_Sodium_Compat::VERSION_STRING' ) ? ParagonIE_Sodium_Compat::VERSION_STRING : false ),
		)
	);
}

/**
 * Lấy danh sách các khóa ký được WordPress tin cậy.
 *
 * @since 5.2.0
 *
 * @return string[] Mảng các khóa ký được mã hóa base64.
 */
function wp_trusted_keys() {
	$trusted_keys = array();

	if ( time() < 1617235200 ) {
		// Khóa WordPress.org #1 - Khóa này chỉ hợp lệ trước ngày 1 tháng 4 năm 2021.
		$trusted_keys[] = 'fRPyrxb/MvVLbdsYi+OOEv4xc+Eqpsj+kkAS6gNOkI0=';
	}

	// TODO: Thêm khóa #2 với thời hạn dài hơn.

	/**
	 * Lọc các khóa ký hợp lệ được sử dụng để xác minh nội dung tệp.
	 *
	 * @since 5.2.0
	 *
	 * @param string[] $trusted_keys Các khóa đáng tin cậy có thể ký gói.
	 */
	return apply_filters( 'wp_trusted_keys', $trusted_keys );
}

/**
 * Xác định xem tệp đã cho có phải là tệp ZIP hợp lệ hay không.
 *
 * Hàm này không kiểm tra để đảm bảo tệp tồn tại. Các tệp không tồn tại
 * không phải là ZIP hợp lệ, nên chúng cũng sẽ trả về false.
 *
 * @since 6.4.4
 *
 * @param string $file Đường dẫn đầy đủ đến tệp ZIP.
 * @return bool Tệp có phải là tệp ZIP hợp lệ hay không.
 */
function wp_zip_file_is_valid( $file ) {
	/** This filter is documented in wp-admin/includes/file.php */
	if ( class_exists( 'ZipArchive', false ) && apply_filters( 'unzip_file_use_ziparchive', true ) ) {
		$archive          = new ZipArchive();
		$archive_is_valid = $archive->open( $file, ZipArchive::CHECKCONS );
		if ( true === $archive_is_valid ) {
			$archive->close();
			return true;
		}
	}

	// Chuyển sang PclZip nếu ZipArchive không khả dụng, hoặc gặp lỗi khi mở tệp.
	require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

	$archive          = new PclZip( $file );
	$archive_is_valid = is_array( $archive->properties() );

	return $archive_is_valid;
}

/**
 * Giải nén một tệp ZIP được chỉ định đến một vị trí trên hệ thống tệp thông qua
 * Lớp trừu tượng hệ thống tệp WordPress.
 *
 * Giả định rằng WP_Filesystem() đã được gọi và thiết lập. Không giải nén
 * thư mục __MACOSX ở cấp gốc, nếu có.
 *
 * Cố gắng tăng giới hạn bộ nhớ PHP lên 256M trước khi giải nén. Tuy nhiên,
 * bộ nhớ cần thiết nhất không nên lớn hơn nhiều so với chính tệp lưu trữ.
 *
 * @since 2.5.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
 *
 * @param string $file Đường dẫn đầy đủ và tên tệp của lưu trữ ZIP.
 * @param string $to   Đường dẫn đầy đủ trên hệ thống tệp để giải nén lưu trữ đến.
 * @return true|WP_Error True khi thành công, WP_Error khi thất bại.
 */
function unzip_file( $file, $to ) {
	global $wp_filesystem;

	if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
		return new WP_Error( 'fs_unavailable', __( 'Could not access filesystem.' ) );
	}

	// Giải nén có thể sử dụng nhiều bộ nhớ, nhưng hy vọng không nhiều đến mức này.
	wp_raise_memory_limit( 'admin' );

	$needed_dirs = array();
	$to          = trailingslashit( $to );

	// Xác định các thư mục cha cần thiết (của thư mục nâng cấp).
	if ( ! $wp_filesystem->is_dir( $to ) ) { // Chỉ xử lý thư mục cha nếu không có thư mục con tồn tại.
		$path = preg_split( '![/\\\]!', untrailingslashit( $to ) );
		for ( $i = count( $path ); $i >= 0; $i-- ) {
			if ( empty( $path[ $i ] ) ) {
				continue;
			}

			$dir = implode( '/', array_slice( $path, 0, $i + 1 ) );
			if ( preg_match( '!^[a-z]:$!i', $dir ) ) { // Bỏ qua nếu nó trông giống ký tự ổ đĩa Windows.
				continue;
			}

			if ( ! $wp_filesystem->is_dir( $dir ) ) {
				$needed_dirs[] = $dir;
			} else {
				break; // Một thư mục tồn tại, do đó chúng ta không cần kiểm tra các cấp bên dưới.
			}
		}
	}

	/**
	 * Lọc có sử dụng ZipArchive để giải nén lưu trữ hay không.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $ziparchive Có sử dụng ZipArchive hay không. Mặc định true.
	 */
	if ( class_exists( 'ZipArchive', false ) && apply_filters( 'unzip_file_use_ziparchive', true ) ) {
		$result = _unzip_file_ziparchive( $file, $to, $needed_dirs );
		if ( true === $result ) {
			return $result;
		} elseif ( is_wp_error( $result ) ) {
			if ( 'incompatible_archive' !== $result->get_error_code() ) {
				return $result;
			}
		}
	}
	// Chuyển sang PclZip nếu ZipArchive không khả dụng, hoặc gặp lỗi khi mở tệp.
	return _unzip_file_pclzip( $file, $to, $needed_dirs );
}

/**
 * Cố gắng giải nén lưu trữ bằng lớp ZipArchive.
 *
 * Hàm này không nên được gọi trực tiếp, sử dụng `unzip_file()` thay thế.
 *
 * Giả định rằng WP_Filesystem() đã được gọi và thiết lập.
 *
 * @since 3.0.0
 * @access private
 *
 * @see unzip_file()
 *
 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
 *
 * @param string   $file        Đường dẫn đầy đủ và tên tệp của lưu trữ ZIP.
 * @param string   $to          Đường dẫn đầy đủ trên hệ thống tệp để giải nén lưu trữ đến.
 * @param string[] $needed_dirs Danh sách một phần các thư mục cần thiết cần được tạo.
 * @return true|WP_Error True khi thành công, WP_Error khi thất bại.
 */
function _unzip_file_ziparchive( $file, $to, $needed_dirs = array() ) {
	global $wp_filesystem;

	$z = new ZipArchive();

	$zopen = $z->open( $file, ZIPARCHIVE::CHECKCONS );

	if ( true !== $zopen ) {
		return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ), array( 'ziparchive_error' => $zopen ) );
	}

	$uncompressed_size = 0;

	for ( $i = 0; $i < $z->numFiles; $i++ ) {
		$info = $z->statIndex( $i );

		if ( ! $info ) {
			$z->close();
			return new WP_Error( 'stat_failed_ziparchive', __( 'Could not retrieve file from archive.' ) );
		}

		if ( str_starts_with( $info['name'], '__MACOSX/' ) ) { // Bỏ qua thư mục __MACOSX do OS X tạo.
			continue;
		}

		// Không giải nén các tệp không hợp lệ:
		if ( 0 !== validate_file( $info['name'] ) ) {
			continue;
		}

		$uncompressed_size += $info['size'];

		$dirname = dirname( $info['name'] );

		if ( str_ends_with( $info['name'], '/' ) ) {
			// Thư mục.
			$needed_dirs[] = $to . untrailingslashit( $info['name'] );
		} elseif ( '.' !== $dirname ) {
			// Đường dẫn đến tệp.
			$needed_dirs[] = $to . untrailingslashit( $dirname );
		}
	}

	// Đủ dung lượng để giải nén tệp và sao chép nội dung, với bộ đệm 10%.
	$required_space = $uncompressed_size * 2.1;

	/*
	 * disk_free_space() có thể trả về false. Giả định rằng bất kỳ giá trị falsey nào là lỗi.
	 * Một đĩa có 0 byte trống có vấn đề lớn hơn.
	 * Yêu cầu đủ dung lượng để giải nén tệp và sao chép nội dung, với bộ đệm 10%.
	 */
	if ( wp_doing_cron() ) {
		$available_space = function_exists( 'disk_free_space' ) ? @disk_free_space( WP_CONTENT_DIR ) : false;

		if ( $available_space && ( $required_space > $available_space ) ) {
			$z->close();
			return new WP_Error(
				'disk_full_unzip_file',
				__( 'Could not copy files. You may have run out of disk space.' ),
				compact( 'uncompressed_size', 'available_space' )
			);
		}
	}

	$needed_dirs = array_unique( $needed_dirs );

	foreach ( $needed_dirs as $dir ) {
		// Kiểm tra thư mục cha của các thư mục đều tồn tại trong mảng tạo.
		if ( untrailingslashit( $to ) === $dir ) { // Bỏ qua thư mục làm việc, chúng ta biết nó tồn tại (hoặc sẽ tồn tại).
			continue;
		}

		if ( ! str_contains( $dir, $to ) ) { // Nếu thư mục không nằm trong thư mục làm việc, bỏ qua.
			continue;
		}

		$parent_folder = dirname( $dir );

		while ( ! empty( $parent_folder )
			&& untrailingslashit( $to ) !== $parent_folder
			&& ! in_array( $parent_folder, $needed_dirs, true )
		) {
			$needed_dirs[] = $parent_folder;
			$parent_folder = dirname( $parent_folder );
		}
	}

	asort( $needed_dirs );

	// Tạo các thư mục đó nếu cần:
	foreach ( $needed_dirs as $_dir ) {
		// Chỉ kiểm tra xem thư mục có tồn tại không khi tạo thất bại. Giảm I/O theo cách này.
		if ( ! $wp_filesystem->mkdir( $_dir, FS_CHMOD_DIR ) && ! $wp_filesystem->is_dir( $_dir ) ) {
			$z->close();
			return new WP_Error( 'mkdir_failed_ziparchive', __( 'Could not create directory.' ), $_dir );
		}
	}

	/**
	 * Lọc việc giải nén lưu trữ để ghi đè bằng quy trình tùy chỉnh.
	 *
	 * @since 6.4.0
	 *
	 * @param null|true|WP_Error $result         Kết quả của việc ghi đè. True khi thành công, ngược lại WP_Error. Mặc định null.
	 * @param string             $file           Đường dẫn đầy đủ và tên tệp của lưu trữ ZIP.
	 * @param string             $to             Đường dẫn đầy đủ trên hệ thống tệp để giải nén lưu trữ đến.
	 * @param string[]           $needed_dirs    Danh sách đầy đủ các thư mục cần thiết cần được tạo.
	 * @param float              $required_space Dung lượng cần thiết để giải nén tệp và sao chép nội dung, với bộ đệm 10%.
	 */
	$pre = apply_filters( 'pre_unzip_file', null, $file, $to, $needed_dirs, $required_space );

	if ( null !== $pre ) {
		// Đảm bảo tệp lưu trữ ZIP đã được đóng.
		$z->close();

		return $pre;
	}

	for ( $i = 0; $i < $z->numFiles; $i++ ) {
		$info = $z->statIndex( $i );

		if ( ! $info ) {
			$z->close();
			return new WP_Error( 'stat_failed_ziparchive', __( 'Could not retrieve file from archive.' ) );
		}

		if ( str_ends_with( $info['name'], '/' ) ) { // Thư mục.
			continue;
		}

		if ( str_starts_with( $info['name'], '__MACOSX/' ) ) { // Không giải nén các tệp thư mục __MACOSX do OS X tạo.
			continue;
		}

		// Không giải nén các tệp không hợp lệ:
		if ( 0 !== validate_file( $info['name'] ) ) {
			continue;
		}

		$contents = $z->getFromIndex( $i );

		if ( false === $contents ) {
			$z->close();
			return new WP_Error( 'extract_failed_ziparchive', __( 'Could not extract file from archive.' ), $info['name'] );
		}

		if ( ! $wp_filesystem->put_contents( $to . $info['name'], $contents, FS_CHMOD_FILE ) ) {
			$z->close();
			return new WP_Error( 'copy_failed_ziparchive', __( 'Could not copy file.' ), $info['name'] );
		}
	}

	$z->close();

	/**
	 * Lọc kết quả giải nén lưu trữ.
	 *
	 * @since 6.4.0
	 *
	 * @param true|WP_Error $result         Kết quả giải nén lưu trữ. True khi thành công, ngược lại WP_Error. Mặc định true.
	 * @param string        $file           Đường dẫn đầy đủ và tên tệp của lưu trữ ZIP.
	 * @param string        $to             Đường dẫn đầy đủ trên hệ thống tệp nơi lưu trữ được giải nén đến.
	 * @param string[]      $needed_dirs    Danh sách đầy đủ các thư mục cần thiết đã được tạo.
	 * @param float         $required_space Dung lượng cần thiết để giải nén tệp và sao chép nội dung, với bộ đệm 10%.
	 */
	$result = apply_filters( 'unzip_file', true, $file, $to, $needed_dirs, $required_space );

	unset( $needed_dirs );

	return $result;
}

/**
 * Cố gắng giải nén lưu trữ bằng thư viện PclZip.
 *
 * Hàm này không nên được gọi trực tiếp, sử dụng `unzip_file()` thay thế.
 *
 * Giả định rằng WP_Filesystem() đã được gọi và thiết lập.
 *
 * @since 3.0.0
 * @access private
 *
 * @see unzip_file()
 *
 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
 *
 * @param string   $file        Đường dẫn đầy đủ và tên tệp của lưu trữ ZIP.
 * @param string   $to          Đường dẫn đầy đủ trên hệ thống tệp để giải nén lưu trữ đến.
 * @param string[] $needed_dirs Danh sách một phần các thư mục cần thiết cần được tạo.
 * @return true|WP_Error True khi thành công, WP_Error khi thất bại.
 */
function _unzip_file_pclzip( $file, $to, $needed_dirs = array() ) {
	global $wp_filesystem;

	mbstring_binary_safe_encoding();

	require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

	$archive = new PclZip( $file );

	$archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING );

	reset_mbstring_encoding();

	// Lưu trữ có hợp lệ không?
	if ( ! is_array( $archive_files ) ) {
		return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ), $archive->errorInfo( true ) );
	}

	if ( 0 === count( $archive_files ) ) {
		return new WP_Error( 'empty_archive_pclzip', __( 'Empty archive.' ) );
	}

	$uncompressed_size = 0;

	// Xác định các thư mục con cần thiết (Từ bên trong lưu trữ).
	foreach ( $archive_files as $file ) {
		if ( str_starts_with( $file['filename'], '__MACOSX/' ) ) { // Bỏ qua thư mục __MACOSX do OS X tạo.
			continue;
		}

		$uncompressed_size += $file['size'];

		$needed_dirs[] = $to . untrailingslashit( $file['folder'] ? $file['filename'] : dirname( $file['filename'] ) );
	}

	// Đủ dung lượng để giải nén tệp và sao chép nội dung, với bộ đệm 10%.
	$required_space = $uncompressed_size * 2.1;

	/*
	 * disk_free_space() có thể trả về false. Giả định rằng bất kỳ giá trị falsey nào là lỗi.
	 * Một đĩa có 0 byte trống có vấn đề lớn hơn.
	 * Yêu cầu đủ dung lượng để giải nén tệp và sao chép nội dung, với bộ đệm 10%.
	 */
	if ( wp_doing_cron() ) {
		$available_space = function_exists( 'disk_free_space' ) ? @disk_free_space( WP_CONTENT_DIR ) : false;

		if ( $available_space && ( $required_space > $available_space ) ) {
			return new WP_Error(
				'disk_full_unzip_file',
				__( 'Could not copy files. You may have run out of disk space.' ),
				compact( 'uncompressed_size', 'available_space' )
			);
		}
	}

	$needed_dirs = array_unique( $needed_dirs );

	foreach ( $needed_dirs as $dir ) {
		// Kiểm tra thư mục cha của các thư mục đều tồn tại trong mảng tạo.
		if ( untrailingslashit( $to ) === $dir ) { // Bỏ qua thư mục làm việc, chúng ta biết nó tồn tại (hoặc sẽ tồn tại).
			continue;
		}

		if ( ! str_contains( $dir, $to ) ) { // Nếu thư mục không nằm trong thư mục làm việc, bỏ qua.
			continue;
		}

		$parent_folder = dirname( $dir );

		while ( ! empty( $parent_folder )
			&& untrailingslashit( $to ) !== $parent_folder
			&& ! in_array( $parent_folder, $needed_dirs, true )
		) {
			$needed_dirs[] = $parent_folder;
			$parent_folder = dirname( $parent_folder );
		}
	}

	asort( $needed_dirs );

	// Tạo các thư mục đó nếu cần:
	foreach ( $needed_dirs as $_dir ) {
		// Chỉ kiểm tra xem thư mục có tồn tại không khi tạo thất bại. Giảm I/O theo cách này.
		if ( ! $wp_filesystem->mkdir( $_dir, FS_CHMOD_DIR ) && ! $wp_filesystem->is_dir( $_dir ) ) {
			return new WP_Error( 'mkdir_failed_pclzip', __( 'Could not create directory.' ), $_dir );
		}
	}

	/** This filter is documented in src/wp-admin/includes/file.php */
	$pre = apply_filters( 'pre_unzip_file', null, $file, $to, $needed_dirs, $required_space );

	if ( null !== $pre ) {
		return $pre;
	}

	// Extract the files from the zip.
	foreach ( $archive_files as $file ) {
		if ( $file['folder'] ) {
			continue;
		}

		if ( str_starts_with( $file['filename'], '__MACOSX/' ) ) { // Don't extract the OS X-created __MACOSX directory files.
			continue;
		}

		// Don't extract invalid files:
		if ( 0 !== validate_file( $file['filename'] ) ) {
			continue;
		}

		if ( ! $wp_filesystem->put_contents( $to . $file['filename'], $file['content'], FS_CHMOD_FILE ) ) {
			return new WP_Error( 'copy_failed_pclzip', __( 'Could not copy file.' ), $file['filename'] );
		}
	}

	/** This action is documented in src/wp-admin/includes/file.php */
	$result = apply_filters( 'unzip_file', true, $file, $to, $needed_dirs, $required_space );

	unset( $needed_dirs );

	return $result;
}

/**
 * Copies a directory from one location to another via the WordPress Filesystem
 * Abstraction.
 *
 * Assumes that WP_Filesystem() has already been called and setup.
 *
 * @since 2.5.0
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 *
 * @param string   $from      Source directory.
 * @param string   $to        Destination directory.
 * @param string[] $skip_list An array of files/folders to skip copying.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function copy_dir( $from, $to, $skip_list = array() ) {
	global $wp_filesystem;

	$dirlist = $wp_filesystem->dirlist( $from );

	if ( false === $dirlist ) {
		return new WP_Error( 'dirlist_failed_copy_dir', __( 'Directory listing failed.' ), basename( $from ) );
	}

	$from = trailingslashit( $from );
	$to   = trailingslashit( $to );

	if ( ! $wp_filesystem->exists( $to ) && ! $wp_filesystem->mkdir( $to ) ) {
		return new WP_Error(
			'mkdir_destination_failed_copy_dir',
			__( 'Could not create the destination directory.' ),
			basename( $to )
		);
	}

	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		if ( in_array( $filename, $skip_list, true ) ) {
			continue;
		}

		if ( 'f' === $fileinfo['type'] ) {
			if ( ! $wp_filesystem->copy( $from . $filename, $to . $filename, true, FS_CHMOD_FILE ) ) {
				// If copy failed, chmod file to 0644 and try again.
				$wp_filesystem->chmod( $to . $filename, FS_CHMOD_FILE );

				if ( ! $wp_filesystem->copy( $from . $filename, $to . $filename, true, FS_CHMOD_FILE ) ) {
					return new WP_Error( 'copy_failed_copy_dir', __( 'Could not copy file.' ), $to . $filename );
				}
			}

			wp_opcache_invalidate( $to . $filename );
		} elseif ( 'd' === $fileinfo['type'] ) {
			if ( ! $wp_filesystem->is_dir( $to . $filename ) ) {
				if ( ! $wp_filesystem->mkdir( $to . $filename, FS_CHMOD_DIR ) ) {
					return new WP_Error( 'mkdir_failed_copy_dir', __( 'Could not create directory.' ), $to . $filename );
				}
			}

			// Generate the $sub_skip_list for the subdirectory as a sub-set of the existing $skip_list.
			$sub_skip_list = array();

			foreach ( $skip_list as $skip_item ) {
				if ( str_starts_with( $skip_item, $filename . '/' ) ) {
					$sub_skip_list[] = preg_replace( '!^' . preg_quote( $filename, '!' ) . '/!i', '', $skip_item );
				}
			}

			$result = copy_dir( $from . $filename, $to . $filename, $sub_skip_list );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
	}

	return true;
}

/**
 * Moves a directory from one location to another.
 *
 * Recursively invalidates OPcache on success.
 *
 * If the renaming failed, falls back to copy_dir().
 *
 * Assumes that WP_Filesystem() has already been called and setup.
 *
 * This function is not designed to merge directories, copy_dir() should be used instead.
 *
 * @since 6.2.0
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 *
 * @param string $from      Source directory.
 * @param string $to        Destination directory.
 * @param bool   $overwrite Optional. Whether to overwrite the destination directory if it exists.
 *                          Default false.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function move_dir( $from, $to, $overwrite = false ) {
	global $wp_filesystem;

	if ( trailingslashit( strtolower( $from ) ) === trailingslashit( strtolower( $to ) ) ) {
		return new WP_Error( 'source_destination_same_move_dir', __( 'The source and destination are the same.' ) );
	}

	if ( $wp_filesystem->exists( $to ) ) {
		if ( ! $overwrite ) {
			return new WP_Error( 'destination_already_exists_move_dir', __( 'The destination folder already exists.' ), $to );
		} elseif ( ! $wp_filesystem->delete( $to, true ) ) {
			// Can't overwrite if the destination couldn't be deleted.
			return new WP_Error( 'destination_not_deleted_move_dir', __( 'The destination directory already exists and could not be removed.' ) );
		}
	}

	if ( $wp_filesystem->move( $from, $to ) ) {
		/*
		 * When using an environment with shared folders,
		 * there is a delay in updating the filesystem's cache.
		 *
		 * This is a known issue in environments with a VirtualBox provider.
		 *
		 * A 200ms delay gives time for the filesystem to update its cache,
		 * prevents "Operation not permitted", and "No such file or directory" warnings.
		 *
		 * This delay is used in other projects, including Composer.
		 * @link https://github.com/composer/composer/blob/2.5.1/src/Composer/Util/Platform.php#L228-L233
		 */
		usleep( 200000 );
		wp_opcache_invalidate_directory( $to );

		return true;
	}

	// Fall back to a recursive copy.
	if ( ! $wp_filesystem->is_dir( $to ) ) {
		if ( ! $wp_filesystem->mkdir( $to, FS_CHMOD_DIR ) ) {
			return new WP_Error( 'mkdir_failed_move_dir', __( 'Could not create directory.' ), $to );
		}
	}

	$result = copy_dir( $from, $to, array( basename( $to ) ) );

	// Clear the source directory.
	if ( true === $result ) {
		$wp_filesystem->delete( $from, true );
	}

	return $result;
}

/**
 * Initializes and connects the WordPress Filesystem Abstraction classes.
 *
 * This function will include the chosen transport and attempt connecting.
 *
 * Plugins may add extra transports, And force WordPress to use them by returning
 * the filename via the {@see 'filesystem_method_file'} filter.
 *
 * @since 2.5.0
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 *
 * @param array|false  $args                         Optional. Connection args, These are passed
 *                                                   directly to the `WP_Filesystem_*()` classes.
 *                                                   Default false.
 * @param string|false $context                      Optional. Context for get_filesystem_method().
 *                                                   Default false.
 * @param bool         $allow_relaxed_file_ownership Optional. Whether to allow Group/World writable.
 *                                                   Default false.
 * @return bool|null True on success, false on failure,
 *                   null if the filesystem method class file does not exist.
 */
function WP_Filesystem( $args = false, $context = false, $allow_relaxed_file_ownership = false ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';

	$method = get_filesystem_method( $args, $context, $allow_relaxed_file_ownership );

	if ( ! $method ) {
		return false;
	}

	if ( ! class_exists( "WP_Filesystem_$method" ) ) {

		/**
		 * Filters the path for a specific filesystem method class file.
		 *
		 * @since 2.6.0
		 *
		 * @see get_filesystem_method()
		 *
		 * @param string $path   Path to the specific filesystem method class file.
		 * @param string $method The filesystem method to use.
		 */
		$abstraction_file = apply_filters( 'filesystem_method_file', ABSPATH . 'wp-admin/includes/class-wp-filesystem-' . $method . '.php', $method );

		if ( ! file_exists( $abstraction_file ) ) {
			return;
		}

		require_once $abstraction_file;
	}
	$method = "WP_Filesystem_$method";

	$wp_filesystem = new $method( $args );

	/*
	 * Define the timeouts for the connections. Only available after the constructor is called
	 * to allow for per-transport overriding of the default.
	 */
	if ( ! defined( 'FS_CONNECT_TIMEOUT' ) ) {
		define( 'FS_CONNECT_TIMEOUT', 30 ); // 30 seconds.
	}
	if ( ! defined( 'FS_TIMEOUT' ) ) {
		define( 'FS_TIMEOUT', 30 ); // 30 seconds.
	}

	if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
		return false;
	}

	if ( ! $wp_filesystem->connect() ) {
		return false; // There was an error connecting to the server.
	}

	// Set the permission constants if not already set.
	if ( ! defined( 'FS_CHMOD_DIR' ) ) {
		define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
	}
	if ( ! defined( 'FS_CHMOD_FILE' ) ) {
		define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
	}

	return true;
}

/**
 * Determines which method to use for reading, writing, modifying, or deleting
 * files on the filesystem.
 *
 * The priority of the transports are: Direct, SSH2, FTP PHP Extension, FTP Sockets
 * (Via Sockets class, or `fsockopen()`). Valid values for these are: 'direct', 'ssh2',
 * 'ftpext' or 'ftpsockets'.
 *
 * The return value can be overridden by defining the `FS_METHOD` constant in `wp-config.php`,
 * or filtering via {@see 'filesystem_method'}.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wordpress-upgrade-constants
 *
 * Plugins may define a custom transport handler, See WP_Filesystem().
 *
 * @since 2.5.0
 *
 * @global callable $_wp_filesystem_direct_method
 *
 * @param array  $args                         Optional. Connection details. Default empty array.
 * @param string $context                      Optional. Full path to the directory that is tested
 *                                             for being writable. Default empty.
 * @param bool   $allow_relaxed_file_ownership Optional. Whether to allow Group/World writable.
 *                                             Default false.
 * @return string The transport to use, see description for valid return values.
 */
function get_filesystem_method( $args = array(), $context = '', $allow_relaxed_file_ownership = false ) {
	// Please ensure that this is either 'direct', 'ssh2', 'ftpext', or 'ftpsockets'.
	$method = defined( 'FS_METHOD' ) ? FS_METHOD : false;

	if ( ! $context ) {
		$context = WP_CONTENT_DIR;
	}

	// If the directory doesn't exist (wp-content/languages) then use the parent directory as we'll create it.
	if ( WP_LANG_DIR === $context && ! is_dir( $context ) ) {
		$context = dirname( $context );
	}

	$context = trailingslashit( $context );

	if ( ! $method ) {

		$temp_file_name = $context . 'temp-write-test-' . str_replace( '.', '-', uniqid( '', true ) );
		$temp_handle    = @fopen( $temp_file_name, 'w' );
		if ( $temp_handle ) {

			// Attempt to determine the file owner of the WordPress files, and that of newly created files.
			$wp_file_owner   = false;
			$temp_file_owner = false;
			if ( function_exists( 'fileowner' ) ) {
				$wp_file_owner   = @fileowner( __FILE__ );
				$temp_file_owner = @fileowner( $temp_file_name );
			}

			if ( false !== $wp_file_owner && $wp_file_owner === $temp_file_owner ) {
				/*
				 * WordPress is creating files as the same owner as the WordPress files,
				 * this means it's safe to modify & create new files via PHP.
				 */
				$method                                  = 'direct';
				$GLOBALS['_wp_filesystem_direct_method'] = 'file_owner';
			} elseif ( $allow_relaxed_file_ownership ) {
				/*
				 * The $context directory is writable, and $allow_relaxed_file_ownership is set,
				 * this means we can modify files safely in this directory.
				 * This mode doesn't create new files, only alter existing ones.
				 */
				$method                                  = 'direct';
				$GLOBALS['_wp_filesystem_direct_method'] = 'relaxed_ownership';
			}

			fclose( $temp_handle );
			@unlink( $temp_file_name );
		}
	}

	if ( ! $method && isset( $args['connection_type'] ) && 'ssh' === $args['connection_type'] && extension_loaded( 'ssh2' ) ) {
		$method = 'ssh2';
	}
	if ( ! $method && extension_loaded( 'ftp' ) ) {
		$method = 'ftpext';
	}
	if ( ! $method && ( extension_loaded( 'sockets' ) || function_exists( 'fsockopen' ) ) ) {
		$method = 'ftpsockets'; // Sockets: Socket extension; PHP Mode: FSockopen / fwrite / fread.
	}

	/**
	 * Filters the filesystem method to use.
	 *
	 * @since 2.6.0
	 *
	 * @param string $method                       Filesystem method to return.
	 * @param array  $args                         An array of connection details for the method.
	 * @param string $context                      Full path to the directory that is tested for being writable.
	 * @param bool   $allow_relaxed_file_ownership Whether to allow Group/World writable.
	 */
	return apply_filters( 'filesystem_method', $method, $args, $context, $allow_relaxed_file_ownership );
}

/**
 * Displays a form to the user to request for their FTP/SSH details in order
 * to connect to the filesystem.
 *
 * All chosen/entered details are saved, excluding the password.
 *
 * Hostnames may be in the form of hostname:portnumber (eg: wordpress.org:2467)
 * to specify an alternate FTP/SSH port.
 *
 * Plugins may override this form by returning true|false via the {@see 'request_filesystem_credentials'} filter.
 *
 * @since 2.5.0
 * @since 4.6.0 The `$context` parameter default changed from `false` to an empty string.
 *
 * @global string $pagenow The filename of the current screen.
 *
 * @param string        $form_post                    The URL to post the form to.
 * @param string        $type                         Optional. Chosen type of filesystem. Default empty.
 * @param bool|WP_Error $error                        Optional. Whether the current request has failed
 *                                                    to connect, or an error object. Default false.
 * @param string        $context                      Optional. Full path to the directory that is tested
 *                                                    for being writable. Default empty.
 * @param array         $extra_fields                 Optional. Extra `POST` fields to be checked
 *                                                    for inclusion in the post. Default null.
 * @param bool          $allow_relaxed_file_ownership Optional. Whether to allow Group/World writable.
 *                                                    Default false.
 * @return bool|array True if no filesystem credentials are required,
 *                    false if they are required but have not been provided,
 *                    array of credentials if they are required and have been provided.
 */
function request_filesystem_credentials( $form_post, $type = '', $error = false, $context = '', $extra_fields = null, $allow_relaxed_file_ownership = false ) {
	global $pagenow;

	/**
	 * Filters the filesystem credentials.
	 *
	 * Returning anything other than an empty string will effectively short-circuit
	 * output of the filesystem credentials form, returning that value instead.
	 *
	 * A filter should return true if no filesystem credentials are required, false if they are required but have not been
	 * provided, or an array of credentials if they are required and have been provided.
	 *
	 * @since 2.5.0
	 * @since 4.6.0 The `$context` parameter default changed from `false` to an empty string.
	 *
	 * @param mixed         $credentials                  Credentials to return instead. Default empty string.
	 * @param string        $form_post                    The URL to post the form to.
	 * @param string        $type                         Chosen type of filesystem.
	 * @param bool|WP_Error $error                        Whether the current request has failed to connect,
	 *                                                    or an error object.
	 * @param string        $context                      Full path to the directory that is tested for
	 *                                                    being writable.
	 * @param array         $extra_fields                 Extra POST fields.
	 * @param bool          $allow_relaxed_file_ownership Whether to allow Group/World writable.
	 */
	$req_cred = apply_filters( 'request_filesystem_credentials', '', $form_post, $type, $error, $context, $extra_fields, $allow_relaxed_file_ownership );

	if ( '' !== $req_cred ) {
		return $req_cred;
	}

	if ( empty( $type ) ) {
		$type = get_filesystem_method( array(), $context, $allow_relaxed_file_ownership );
	}

	if ( 'direct' === $type ) {
		return true;
	}

	if ( is_null( $extra_fields ) ) {
		$extra_fields = array( 'version', 'locale' );
	}

	$credentials = get_option(
		'ftp_credentials',
		array(
			'hostname' => '',
			'username' => '',
		)
	);

	$submitted_form = wp_unslash( $_POST );

	// Verify nonce, or unset submitted form field values on failure.
	if ( ! isset( $_POST['_fs_nonce'] ) || ! wp_verify_nonce( $_POST['_fs_nonce'], 'filesystem-credentials' ) ) {
		unset(
			$submitted_form['hostname'],
			$submitted_form['username'],
			$submitted_form['password'],
			$submitted_form['public_key'],
			$submitted_form['private_key'],
			$submitted_form['connection_type']
		);
	}

	$ftp_constants = array(
		'hostname'    => 'FTP_HOST',
		'username'    => 'FTP_USER',
		'password'    => 'FTP_PASS',
		'public_key'  => 'FTP_PUBKEY',
		'private_key' => 'FTP_PRIKEY',
	);

	/*
	 * If defined, set it to that. Else, if POST'd, set it to that. If not, set it to an empty string.
	 * Otherwise, keep it as it previously was (saved details in option).
	 */
	foreach ( $ftp_constants as $key => $constant ) {
		if ( defined( $constant ) ) {
			$credentials[ $key ] = constant( $constant );
		} elseif ( ! empty( $submitted_form[ $key ] ) ) {
			$credentials[ $key ] = $submitted_form[ $key ];
		} elseif ( ! isset( $credentials[ $key ] ) ) {
			$credentials[ $key ] = '';
		}
	}

	// Sanitize the hostname, some people might pass in odd data.
	$credentials['hostname'] = preg_replace( '|\w+://|', '', $credentials['hostname'] ); // Strip any schemes off.

	if ( strpos( $credentials['hostname'], ':' ) ) {
		list( $credentials['hostname'], $credentials['port'] ) = explode( ':', $credentials['hostname'], 2 );
		if ( ! is_numeric( $credentials['port'] ) ) {
			unset( $credentials['port'] );
		}
	} else {
		unset( $credentials['port'] );
	}

	if ( ( defined( 'FTP_SSH' ) && FTP_SSH ) || ( defined( 'FS_METHOD' ) && 'ssh2' === FS_METHOD ) ) {
		$credentials['connection_type'] = 'ssh';
	} elseif ( ( defined( 'FTP_SSL' ) && FTP_SSL ) && 'ftpext' === $type ) { // Only the FTP Extension understands SSL.
		$credentials['connection_type'] = 'ftps';
	} elseif ( ! empty( $submitted_form['connection_type'] ) ) {
		$credentials['connection_type'] = $submitted_form['connection_type'];
	} elseif ( ! isset( $credentials['connection_type'] ) ) { // All else fails (and it's not defaulted to something else saved), default to FTP.
		$credentials['connection_type'] = 'ftp';
	}

	if ( ! $error
		&& ( ! empty( $credentials['hostname'] ) && ! empty( $credentials['username'] ) && ! empty( $credentials['password'] )
			|| 'ssh' === $credentials['connection_type'] && ! empty( $credentials['public_key'] ) && ! empty( $credentials['private_key'] )
		)
	) {
		$stored_credentials = $credentials;

		if ( ! empty( $stored_credentials['port'] ) ) { // Save port as part of hostname to simplify above code.
			$stored_credentials['hostname'] .= ':' . $stored_credentials['port'];
		}

		unset(
			$stored_credentials['password'],
			$stored_credentials['port'],
			$stored_credentials['private_key'],
			$stored_credentials['public_key']
		);

		if ( ! wp_installing() ) {
			update_option( 'ftp_credentials', $stored_credentials, false );
		}

		return $credentials;
	}

	$hostname        = isset( $credentials['hostname'] ) ? $credentials['hostname'] : '';
	$username        = isset( $credentials['username'] ) ? $credentials['username'] : '';
	$public_key      = isset( $credentials['public_key'] ) ? $credentials['public_key'] : '';
	$private_key     = isset( $credentials['private_key'] ) ? $credentials['private_key'] : '';
	$port            = isset( $credentials['port'] ) ? $credentials['port'] : '';
	$connection_type = isset( $credentials['connection_type'] ) ? $credentials['connection_type'] : '';

	if ( $error ) {
		$error_string = __( '<strong>Error:</strong> Could not connect to the server. Please verify the settings are correct.' );
		if ( is_wp_error( $error ) ) {
			$error_string = esc_html( $error->get_error_message() );
		}
		wp_admin_notice(
			$error_string,
			array(
				'id'                 => 'message',
				'additional_classes' => array( 'error' ),
			)
		);
	}

	$types = array();
	if ( extension_loaded( 'ftp' ) || extension_loaded( 'sockets' ) || function_exists( 'fsockopen' ) ) {
		$types['ftp'] = __( 'FTP' );
	}
	if ( extension_loaded( 'ftp' ) ) { // Only this supports FTPS.
		$types['ftps'] = __( 'FTPS (SSL)' );
	}
	if ( extension_loaded( 'ssh2' ) ) {
		$types['ssh'] = __( 'SSH2' );
	}

	/**
	 * Filters the connection types to output to the filesystem credentials form.
	 *
	 * @since 2.9.0
	 * @since 4.6.0 The `$context` parameter default changed from `false` to an empty string.
	 *
	 * @param string[]      $types       Types of connections.
	 * @param array         $credentials Credentials to connect with.
	 * @param string        $type        Chosen filesystem method.
	 * @param bool|WP_Error $error       Whether the current request has failed to connect,
	 *                                   or an error object.
	 * @param string        $context     Full path to the directory that is tested for being writable.
	 */
	$types = apply_filters( 'fs_ftp_connection_types', $types, $credentials, $type, $error, $context );
	?>
<form action="<?php echo esc_url( $form_post ); ?>" method="post">
<div id="request-filesystem-credentials-form" class="request-filesystem-credentials-form">
	<?php
	// Print a H1 heading in the FTP credentials modal dialog, default is a H2.
	$heading_tag = 'h2';
	if ( 'plugins.php' === $pagenow || 'plugin-install.php' === $pagenow ) {
		$heading_tag = 'h1';
	}
	echo "<$heading_tag id='request-filesystem-credentials-title'>" . __( 'Connection Information' ) . "</$heading_tag>";
	?>
<p id="request-filesystem-credentials-desc">
	<?php
	$label_user = __( 'Username' );
	$label_pass = __( 'Password' );
	_e( 'To perform the requested action, WordPress needs to access your web server.' );
	echo ' ';
	if ( ( isset( $types['ftp'] ) || isset( $types['ftps'] ) ) ) {
		if ( isset( $types['ssh'] ) ) {
			_e( 'Please enter your FTP or SSH credentials to proceed.' );
			$label_user = __( 'FTP/SSH Username' );
			$label_pass = __( 'FTP/SSH Password' );
		} else {
			_e( 'Please enter your FTP credentials to proceed.' );
			$label_user = __( 'FTP Username' );
			$label_pass = __( 'FTP Password' );
		}
		echo ' ';
	}
	_e( 'If you do not remember your credentials, you should contact your web host.' );

	$hostname_value = esc_attr( $hostname );
	if ( ! empty( $port ) ) {
		$hostname_value .= ":$port";
	}

	$password_value = '';
	if ( defined( 'FTP_PASS' ) ) {
		$password_value = '*****';
	}
	?>
</p>
<label for="hostname">
	<span class="field-title"><?php _e( 'Hostname' ); ?></span>
	<input name="hostname" type="text" id="hostname" aria-describedby="request-filesystem-credentials-desc" class="code" placeholder="<?php esc_attr_e( 'example: www.wordpress.org' ); ?>" value="<?php echo $hostname_value; ?>"<?php disabled( defined( 'FTP_HOST' ) ); ?> />
</label>
<div class="ftp-username">
	<label for="username">
		<span class="field-title"><?php echo $label_user; ?></span>
		<input name="username" type="text" id="username" value="<?php echo esc_attr( $username ); ?>"<?php disabled( defined( 'FTP_USER' ) ); ?> />
	</label>
</div>
<div class="ftp-password">
	<label for="password">
		<span class="field-title"><?php echo $label_pass; ?></span>
		<input name="password" type="password" id="password" value="<?php echo $password_value; ?>"<?php disabled( defined( 'FTP_PASS' ) ); ?> spellcheck="false" />
		<?php
		if ( ! defined( 'FTP_PASS' ) ) {
			_e( 'This password will not be stored on the server.' );
		}
		?>
	</label>
</div>
<fieldset>
<legend><?php _e( 'Connection Type' ); ?></legend>
	<?php
	$disabled = disabled( ( defined( 'FTP_SSL' ) && FTP_SSL ) || ( defined( 'FTP_SSH' ) && FTP_SSH ), true, false );
	foreach ( $types as $name => $text ) :
		?>
	<label for="<?php echo esc_attr( $name ); ?>">
		<input type="radio" name="connection_type" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $name ); ?>" <?php checked( $name, $connection_type ); ?> <?php echo $disabled; ?> />
		<?php echo $text; ?>
	</label>
		<?php
	endforeach;
	?>
</fieldset>
	<?php
	if ( isset( $types['ssh'] ) ) {
		$hidden_class = '';
		if ( 'ssh' !== $connection_type || empty( $connection_type ) ) {
			$hidden_class = ' class="hidden"';
		}
		?>
<fieldset id="ssh-keys"<?php echo $hidden_class; ?>>
<legend><?php _e( 'Authentication Keys' ); ?></legend>
<label for="public_key">
	<span class="field-title"><?php _e( 'Public Key:' ); ?></span>
	<input name="public_key" type="text" id="public_key" aria-describedby="auth-keys-desc" value="<?php echo esc_attr( $public_key ); ?>"<?php disabled( defined( 'FTP_PUBKEY' ) ); ?> />
</label>
<label for="private_key">
	<span class="field-title"><?php _e( 'Private Key:' ); ?></span>
	<input name="private_key" type="text" id="private_key" value="<?php echo esc_attr( $private_key ); ?>"<?php disabled( defined( 'FTP_PRIKEY' ) ); ?> />
</label>
<p id="auth-keys-desc"><?php _e( 'Enter the location on the server where the public and private keys are located. If a passphrase is needed, enter that in the password field above.' ); ?></p>
</fieldset>
		<?php
	}

	foreach ( (array) $extra_fields as $field ) {
		if ( isset( $submitted_form[ $field ] ) ) {
			echo '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . esc_attr( $submitted_form[ $field ] ) . '" />';
		}
	}

	/*
	 * Make sure the `submit_button()` function is available during the REST API call
	 * from WP_Site_Health_Auto_Updates::test_check_wp_filesystem_method().
	 */
	if ( ! function_exists( 'submit_button' ) ) {
		require_once ABSPATH . 'wp-admin/includes/template.php';
	}
	?>
	<p class="request-filesystem-credentials-action-buttons">
		<?php wp_nonce_field( 'filesystem-credentials', '_fs_nonce', false, true ); ?>
		<button class="button cancel-button" data-js-action="close" type="button"><?php _e( 'Cancel' ); ?></button>
		<?php submit_button( __( 'Proceed' ), 'primary', 'upgrade', false ); ?>
	</p>
</div>
</form>
	<?php
	return false;
}

/**
 * Prints the filesystem credentials modal when needed.
 *
 * @since 4.2.0
 */
function wp_print_request_filesystem_credentials_modal() {
	$filesystem_method = get_filesystem_method();

	ob_start();
	$filesystem_credentials_are_stored = request_filesystem_credentials( self_admin_url() );
	ob_end_clean();

	$request_filesystem_credentials = ( 'direct' !== $filesystem_method && ! $filesystem_credentials_are_stored );
	if ( ! $request_filesystem_credentials ) {
		return;
	}
	?>
	<div id="request-filesystem-credentials-dialog" class="notification-dialog-wrap request-filesystem-credentials-dialog">
		<div class="notification-dialog-background"></div>
		<div class="notification-dialog" role="dialog" aria-labelledby="request-filesystem-credentials-title" tabindex="0">
			<div class="request-filesystem-credentials-dialog-content">
				<?php request_filesystem_credentials( site_url() ); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Attempts to clear the opcode cache for an individual PHP file.
 *
 * This function can be called safely without having to check the file extension
 * or availability of the OPcache extension.
 *
 * Whether or not invalidation is possible is cached to improve performance.
 *
 * @since 5.5.0
 *
 * @link https://www.php.net/manual/en/function.opcache-invalidate.php
 *
 * @param string $filepath Path to the file, including extension, for which the opcode cache is to be cleared.
 * @param bool   $force    Invalidate even if the modification time is not newer than the file in cache.
 *                         Default false.
 * @return bool True if opcache was invalidated for `$filepath`, or there was nothing to invalidate.
 *              False if opcache invalidation is not available, or is disabled via filter.
 */
function wp_opcache_invalidate( $filepath, $force = false ) {
	static $can_invalidate = null;

	/*
	 * Check to see if WordPress is able to run `opcache_invalidate()` or not, and cache the value.
	 *
	 * First, check to see if the function is available to call, then if the host has restricted
	 * the ability to run the function to avoid a PHP warning.
	 *
	 * `opcache.restrict_api` can specify the path for files allowed to call `opcache_invalidate()`.
	 *
	 * If the host has this set, check whether the path in `opcache.restrict_api` matches
	 * the beginning of the path of the origin file.
	 *
	 * `$_SERVER['SCRIPT_FILENAME']` approximates the origin file's path, but `realpath()`
	 * is necessary because `SCRIPT_FILENAME` can be a relative path when run from CLI.
	 *
	 * For more details, see:
	 * - https://www.php.net/manual/en/opcache.configuration.php
	 * - https://www.php.net/manual/en/reserved.variables.server.php
	 * - https://core.trac.wordpress.org/ticket/36455
	 */
	if ( null === $can_invalidate
		&& function_exists( 'opcache_invalidate' )
		&& ( ! ini_get( 'opcache.restrict_api' )
			|| stripos( realpath( $_SERVER['SCRIPT_FILENAME'] ), ini_get( 'opcache.restrict_api' ) ) === 0 )
	) {
		$can_invalidate = true;
	}

	// If invalidation is not available, return early.
	if ( ! $can_invalidate ) {
		return false;
	}

	// Verify that file to be invalidated has a PHP extension.
	if ( '.php' !== strtolower( substr( $filepath, -4 ) ) ) {
		return false;
	}

	/**
	 * Filters whether to invalidate a file from the opcode cache.
	 *
	 * @since 5.5.0
	 *
	 * @param bool   $will_invalidate Whether WordPress will invalidate `$filepath`. Default true.
	 * @param string $filepath        The path to the PHP file to invalidate.
	 */
	if ( apply_filters( 'wp_opcache_invalidate_file', true, $filepath ) ) {
		return opcache_invalidate( $filepath, $force );
	}

	return false;
}

/**
 * Attempts to clear the opcode cache for a directory of files.
 *
 * @since 6.2.0
 *
 * @see wp_opcache_invalidate()
 * @link https://www.php.net/manual/en/function.opcache-invalidate.php
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 *
 * @param string $dir The path to the directory for which the opcode cache is to be cleared.
 */
function wp_opcache_invalidate_directory( $dir ) {
	global $wp_filesystem;

	if ( ! is_string( $dir ) || '' === trim( $dir ) ) {
		if ( WP_DEBUG ) {
			$error_message = sprintf(
				/* translators: %s: The function name. */
				__( '%s expects a non-empty string.' ),
				'<code>wp_opcache_invalidate_directory()</code>'
			);
			wp_trigger_error( '', $error_message );
		}
		return;
	}

	$dirlist = $wp_filesystem->dirlist( $dir, false, true );

	if ( empty( $dirlist ) ) {
		return;
	}

	/*
	 * Recursively invalidate opcache of files in a directory.
	 *
	 * WP_Filesystem_*::dirlist() returns an array of file and directory information.
	 *
	 * This does not include a path to the file or directory.
	 * To invalidate files within sub-directories, recursion is needed
	 * to prepend an absolute path containing the sub-directory's name.
	 *
	 * @param array  $dirlist Array of file/directory information from WP_Filesystem_Base::dirlist(),
	 *                        with sub-directories represented as nested arrays.
	 * @param string $path    Absolute path to the directory.
	 */
	$invalidate_directory = static function ( $dirlist, $path ) use ( &$invalidate_directory ) {
		$path = trailingslashit( $path );

		foreach ( $dirlist as $name => $details ) {
			if ( 'f' === $details['type'] ) {
				wp_opcache_invalidate( $path . $name, true );
			} elseif ( is_array( $details['files'] ) && ! empty( $details['files'] ) ) {
				$invalidate_directory( $details['files'], $path . $name );
			}
		}
	};

	$invalidate_directory( $dirlist, $dir );
}
