<?php
/**
 * API Quản trị Plugin WordPress
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Phân tích nội dung plugin để lấy metadata của plugin.
 *
 * Tất cả các header của plugin phải nằm trên dòng riêng. Mô tả plugin không được có
 * ký tự xuống dòng, nếu không chỉ một phần mô tả sẽ được hiển thị.
 * Phần dưới đây được định dạng để in.
 *
 *     /*
 *     Plugin Name: Tên của plugin.
 *     Plugin URI: Trang chủ của plugin.
 *     Description: Mô tả plugin.
 *     Author: Tên tác giả plugin.
 *     Author URI: Liên kết đến website tác giả.
 *     Version: Phiên bản plugin.
 *     Text Domain: Tùy chọn. Định danh duy nhất, nên giống với giá trị được sử dụng trong
 *          load_plugin_textdomain().
 *     Domain Path: Tùy chọn. Chỉ hữu ích khi các bản dịch nằm trong thư mục
 *          phía trên đường dẫn gốc của plugin. Ví dụ, nếu các file .mo nằm
 *          trong thư mục locale thì Domain Path sẽ là "/locale/" và
 *          phải có dấu gạch chéo đầu tiên. Mặc định là thư mục gốc nơi plugin
 *          được đặt.
 *     Network: Tùy chọn. Chỉ định "Network: true" để yêu cầu plugin phải được kích hoạt
 *          trên tất cả các site trong bản cài đặt. Điều này sẽ ngăn plugin
 *          được kích hoạt trên một site đơn lẻ khi Multisite được bật.
 *     Requires at least: Tùy chọn. Chỉ định phiên bản WordPress tối thiểu yêu cầu.
 *     Requires PHP: Tùy chọn. Chỉ định phiên bản PHP tối thiểu yêu cầu.
 *     * / # Xóa khoảng trắng để đóng comment.
 *
 * 8 KB đầu tiên của file sẽ được đọc và nếu dữ liệu plugin không nằm
 * trong 8 KB đầu tiên đó, tác giả plugin nên sửa lại plugin
 * và di chuyển các header dữ liệu plugin lên đầu.
 *
 * File plugin được giả định có quyền cho phép script đọc
 * file. Tuy nhiên điều này không được kiểm tra và file chỉ được mở để
 * đọc.
 *
 * @since 1.5.0
 * @since 5.3.0 Thêm hỗ trợ cho header `Requires at least` và `Requires PHP`.
 * @since 5.8.0 Thêm hỗ trợ cho header `Update URI`.
 * @since 6.5.0 Thêm hỗ trợ cho header `Requires Plugins`.
 *
 * @param string $plugin_file Đường dẫn tuyệt đối đến file plugin chính.
 * @param bool   $markup      Tùy chọn. Dữ liệu trả về có áp dụng đánh dấu HTML không.
 *                            Mặc định true.
 * @param bool   $translate   Tùy chọn. Dữ liệu trả về có được dịch không. Mặc định true.
 * @return array {
 *     Dữ liệu plugin. Giá trị sẽ trống nếu plugin không cung cấp.
 *
 *     @type string $Name            Tên của plugin. Nên là duy nhất.
 *     @type string $PluginURI       URI của plugin.
 *     @type string $Version         Phiên bản plugin.
 *     @type string $Description     Mô tả plugin.
 *     @type string $Author          Tên tác giả plugin.
 *     @type string $AuthorURI       Địa chỉ website tác giả plugin (nếu có).
 *     @type string $TextDomain      Textdomain của plugin.
 *     @type string $DomainPath      Đường dẫn thư mục tương đối đến các file .mo.
 *     @type bool   $Network         Plugin có chỉ được kích hoạt trên toàn mạng không.
 *     @type string $RequiresWP      Phiên bản WordPress tối thiểu yêu cầu.
 *     @type string $RequiresPHP     Phiên bản PHP tối thiểu yêu cầu.
 *     @type string $UpdateURI       ID của plugin cho mục đích cập nhật, nên là URI.
 *     @type string $RequiresPlugins Danh sách slug plugin dot org phân tách bằng dấu phẩy.
 *     @type string $Title           Tiêu đề plugin và liên kết đến site của plugin (nếu có).
 *     @type string $AuthorName      Tên tác giả plugin.
 * }
 */
function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {

	$default_headers = array(
		'Name'            => 'Plugin Name',
		'PluginURI'       => 'Plugin URI',
		'Version'         => 'Version',
		'Description'     => 'Description',
		'Author'          => 'Author',
		'AuthorURI'       => 'Author URI',
		'TextDomain'      => 'Text Domain',
		'DomainPath'      => 'Domain Path',
		'Network'         => 'Network',
		'RequiresWP'      => 'Requires at least',
		'RequiresPHP'     => 'Requires PHP',
		'UpdateURI'       => 'Update URI',
		'RequiresPlugins' => 'Requires Plugins',
		// Site Wide Only không còn được sử dụng, thay bằng Network.
		'_sitewide'       => 'Site Wide Only',
	);

	$plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

	// Site Wide Only là header cũ cho Network.
	if ( ! $plugin_data['Network'] && $plugin_data['_sitewide'] ) {
		/* translators: 1: Site Wide Only: true, 2: Network: true */
		_deprecated_argument( __FUNCTION__, '3.0.0', sprintf( __( 'The %1$s plugin header is deprecated. Use %2$s instead.' ), '<code>Site Wide Only: true</code>', '<code>Network: true</code>' ) );
		$plugin_data['Network'] = $plugin_data['_sitewide'];
	}
	$plugin_data['Network'] = ( 'true' === strtolower( $plugin_data['Network'] ) );
	unset( $plugin_data['_sitewide'] );

	// Nếu không có text domain được định nghĩa, sử dụng slug của plugin.
	if ( ! $plugin_data['TextDomain'] ) {
		$plugin_slug = dirname( plugin_basename( $plugin_file ) );
		if ( '.' !== $plugin_slug && ! str_contains( $plugin_slug, '/' ) ) {
			$plugin_data['TextDomain'] = $plugin_slug;
		}
	}

	if ( $markup || $translate ) {
		$plugin_data = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, $markup, $translate );
	} else {
		$plugin_data['Title']      = $plugin_data['Name'];
		$plugin_data['AuthorName'] = $plugin_data['Author'];
	}

	return $plugin_data;
}

/**
 * Làm sạch dữ liệu plugin, tùy chọn thêm đánh dấu HTML, tùy chọn dịch.
 *
 * @since 2.7.0
 *
 * @see get_plugin_data()
 *
 * @access private
 *
 * @param string $plugin_file Đường dẫn đến file plugin chính.
 * @param array  $plugin_data Mảng dữ liệu plugin. Xem get_plugin_data().
 * @param bool   $markup      Tùy chọn. Dữ liệu trả về có áp dụng đánh dấu HTML không.
 *                            Mặc định true.
 * @param bool   $translate   Tùy chọn. Dữ liệu trả về có được dịch không. Mặc định true.
 * @return array Dữ liệu plugin. Giá trị sẽ trống nếu plugin không cung cấp.
 *               Xem get_plugin_data() để biết danh sách các giá trị có thể.
 */
function _get_plugin_data_markup_translate( $plugin_file, $plugin_data, $markup = true, $translate = true ) {

	// Làm sạch tên file plugin thành đường dẫn tương đối so với WP_PLUGIN_DIR.
	$plugin_file = plugin_basename( $plugin_file );

	// Dịch các trường.
	if ( $translate ) {
		$textdomain = $plugin_data['TextDomain'];
		if ( $textdomain ) {
			if ( ! is_textdomain_loaded( $textdomain ) ) {
				if ( $plugin_data['DomainPath'] ) {
					load_plugin_textdomain( $textdomain, false, dirname( $plugin_file ) . $plugin_data['DomainPath'] );
				} else {
					load_plugin_textdomain( $textdomain, false, dirname( $plugin_file ) );
				}
			}
		} elseif ( 'hello.php' === basename( $plugin_file ) ) {
			$textdomain = 'default';
		}
		if ( $textdomain ) {
			foreach ( array( 'Name', 'PluginURI', 'Description', 'Author', 'AuthorURI', 'Version' ) as $field ) {
				if ( ! empty( $plugin_data[ $field ] ) ) {
					// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
					$plugin_data[ $field ] = translate( $plugin_data[ $field ], $textdomain );
				}
			}
		}
	}

	// Làm sạch các trường.
	$allowed_tags_in_links = array(
		'abbr'    => array( 'title' => true ),
		'acronym' => array( 'title' => true ),
		'code'    => true,
		'em'      => true,
		'strong'  => true,
	);

	$allowed_tags      = $allowed_tags_in_links;
	$allowed_tags['a'] = array(
		'href'  => true,
		'title' => true,
	);

	/*
	 * Tên được đánh dấu bên trong thẻ <a>. Không cho phép các thẻ này.
	 * Tác giả cũng vậy, nhưng một số plugin đã sử dụng <a> ở đây (bỏ qua Author URI).
	 */
	$plugin_data['Name']   = wp_kses( $plugin_data['Name'], $allowed_tags_in_links );
	$plugin_data['Author'] = wp_kses( $plugin_data['Author'], $allowed_tags );

	$plugin_data['Description'] = wp_kses( $plugin_data['Description'], $allowed_tags );
	$plugin_data['Version']     = wp_kses( $plugin_data['Version'], $allowed_tags );

	$plugin_data['PluginURI'] = esc_url( $plugin_data['PluginURI'] );
	$plugin_data['AuthorURI'] = esc_url( $plugin_data['AuthorURI'] );

	$plugin_data['Title']      = $plugin_data['Name'];
	$plugin_data['AuthorName'] = $plugin_data['Author'];

	// Áp dụng đánh dấu HTML.
	if ( $markup ) {
		if ( $plugin_data['PluginURI'] && $plugin_data['Name'] ) {
			$plugin_data['Title'] = '<a href="' . $plugin_data['PluginURI'] . '">' . $plugin_data['Name'] . '</a>';
		}

		if ( $plugin_data['AuthorURI'] && $plugin_data['Author'] ) {
			$plugin_data['Author'] = '<a href="' . $plugin_data['AuthorURI'] . '">' . $plugin_data['Author'] . '</a>';
		}

		$plugin_data['Description'] = wptexturize( $plugin_data['Description'] );

		if ( $plugin_data['Author'] ) {
			$plugin_data['Description'] .= sprintf(
				/* translators: %s: Plugin author. */
				' <cite>' . __( 'By %s.' ) . '</cite>',
				$plugin_data['Author']
			);
		}
	}

	return $plugin_data;
}

/**
 * Lấy danh sách các file của plugin.
 *
 * @since 2.8.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return string[] Mảng tên file tương đối so với thư mục gốc plugin.
 */
function get_plugin_files( $plugin ) {
	$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
	$dir         = dirname( $plugin_file );

	$plugin_files = array( plugin_basename( $plugin_file ) );

	if ( is_dir( $dir ) && WP_PLUGIN_DIR !== $dir ) {

		/**
		 * Lọc mảng các thư mục và file bị loại trừ khi quét thư mục.
		 *
		 * @since 4.9.0
		 *
		 * @param string[] $exclusions Mảng các thư mục và file bị loại trừ.
		 */
		$exclusions = (array) apply_filters( 'plugin_files_exclusions', array( 'CVS', 'node_modules', 'vendor', 'bower_components' ) );

		$list_files = list_files( $dir, 100, $exclusions );
		$list_files = array_map( 'plugin_basename', $list_files );

		$plugin_files = array_merge( $plugin_files, $list_files );
		$plugin_files = array_values( array_unique( $plugin_files ) );
	}

	return $plugin_files;
}

/**
 * Kiểm tra thư mục plugins và lấy tất cả file plugin cùng dữ liệu plugin.
 *
 * WordPress chỉ hỗ trợ các file plugin trong thư mục plugins gốc
 * (wp-content/plugins) và trong một thư mục phía trên thư mục plugins
 * (wp-content/plugins/my-plugin). File mà nó tìm kiếm chứa dữ liệu plugin
 * và phải được tìm thấy ở hai vị trí đó. Nên giữ các file plugin
 * trong thư mục riêng của chúng.
 *
 * File chứa dữ liệu plugin là file sẽ được include và do đó
 * cần có phần thực thi chính cho plugin. Điều này không có nghĩa
 * mọi thứ phải nằm trong file đó và nên chia file
 * để dễ bảo trì. Chỉ giữ mọi thứ trong một file để
 * tối ưu hóa tối đa.
 *
 * @since 1.5.0
 *
 * @param string $plugin_folder Tùy chọn. Đường dẫn tương đối đến thư mục plugin đơn.
 * @return array[] Mảng các mảng dữ liệu plugin, được đánh khóa bởi tên file plugin. Xem get_plugin_data().
 */
function get_plugins( $plugin_folder = '' ) {

	$cache_plugins = wp_cache_get( 'plugins', 'plugins' );
	if ( ! $cache_plugins ) {
		$cache_plugins = array();
	}

	if ( isset( $cache_plugins[ $plugin_folder ] ) ) {
		return $cache_plugins[ $plugin_folder ];
	}

	$wp_plugins  = array();
	$plugin_root = WP_PLUGIN_DIR;
	if ( ! empty( $plugin_folder ) ) {
		$plugin_root .= $plugin_folder;
	}

	// Các file trong thư mục wp-content/plugins.
	$plugins_dir  = @opendir( $plugin_root );
	$plugin_files = array();

	if ( $plugins_dir ) {
		while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
			if ( str_starts_with( $file, '.' ) ) {
				continue;
			}

			if ( is_dir( $plugin_root . '/' . $file ) ) {
				$plugins_subdir = @opendir( $plugin_root . '/' . $file );

				if ( $plugins_subdir ) {
					while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) {
						if ( str_starts_with( $subfile, '.' ) ) {
							continue;
						}

						if ( str_ends_with( $subfile, '.php' ) ) {
							$plugin_files[] = "$file/$subfile";
						}
					}

					closedir( $plugins_subdir );
				}
			} elseif ( str_ends_with( $file, '.php' ) ) {
				$plugin_files[] = $file;
			}
		}

		closedir( $plugins_dir );
	}

	if ( empty( $plugin_files ) ) {
		return $wp_plugins;
	}

	foreach ( $plugin_files as $plugin_file ) {
		if ( ! is_readable( "$plugin_root/$plugin_file" ) ) {
			continue;
		}

		// Không áp dụng đánh dấu/dịch vì sẽ được lưu cache.
		$plugin_data = get_plugin_data( "$plugin_root/$plugin_file", false, false );

		if ( empty( $plugin_data['Name'] ) ) {
			continue;
		}

		$wp_plugins[ plugin_basename( $plugin_file ) ] = $plugin_data;
	}

	uasort( $wp_plugins, '_sort_uname_callback' );

	$cache_plugins[ $plugin_folder ] = $wp_plugins;
	wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

	return $wp_plugins;
}

/**
 * Kiểm tra thư mục mu-plugins và lấy tất cả file mu-plugin cùng dữ liệu plugin.
 *
 * WordPress chỉ bao gồm các file mu-plugin trong thư mục mu-plugins gốc (wp-content/mu-plugins).
 *
 * @since 3.0.0
 * @return array[] Mảng các mảng dữ liệu mu-plugin, được đánh khóa bởi tên file plugin. Xem get_plugin_data().
 */
function get_mu_plugins() {
	$wp_plugins   = array();
	$plugin_files = array();

	if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
		return $wp_plugins;
	}

	// Các file trong thư mục wp-content/mu-plugins.
	$plugins_dir = @opendir( WPMU_PLUGIN_DIR );
	if ( $plugins_dir ) {
		while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
			if ( str_ends_with( $file, '.php' ) ) {
				$plugin_files[] = $file;
			}
		}
	} else {
		return $wp_plugins;
	}

	closedir( $plugins_dir );

	if ( empty( $plugin_files ) ) {
		return $wp_plugins;
	}

	foreach ( $plugin_files as $plugin_file ) {
		if ( ! is_readable( WPMU_PLUGIN_DIR . "/$plugin_file" ) ) {
			continue;
		}

		// Không áp dụng đánh dấu/dịch vì sẽ được lưu cache.
		$plugin_data = get_plugin_data( WPMU_PLUGIN_DIR . "/$plugin_file", false, false );

		if ( empty( $plugin_data['Name'] ) ) {
			$plugin_data['Name'] = $plugin_file;
		}

		$wp_plugins[ $plugin_file ] = $plugin_data;
	}

	if ( isset( $wp_plugins['index.php'] ) && filesize( WPMU_PLUGIN_DIR . '/index.php' ) <= 30 ) {
		// Im lặng là vàng.
		unset( $wp_plugins['index.php'] );
	}

	uasort( $wp_plugins, '_sort_uname_callback' );

	return $wp_plugins;
}

/**
 * Khai báo callback để sắp xếp mảng theo khóa 'Name'.
 *
 * @since 3.1.0
 *
 * @access private
 *
 * @param array $a Mảng có khóa 'Name'.
 * @param array $b Mảng có khóa 'Name'.
 * @return int Trả về 0 hoặc 1 dựa trên so sánh hai chuỗi.
 */
function _sort_uname_callback( $a, $b ) {
	return strnatcasecmp( $a['Name'], $b['Name'] );
}

/**
 * Kiểm tra thư mục wp-content và lấy tất cả drop-in cùng dữ liệu plugin.
 *
 * @since 3.0.0
 * @return array[] Mảng các mảng dữ liệu plugin drop-in, được đánh khóa bởi tên file plugin. Xem get_plugin_data().
 */
function get_dropins() {
	$dropins      = array();
	$plugin_files = array();

	$_dropins = _get_dropins();

	// Các file trong thư mục wp-content.
	$plugins_dir = @opendir( WP_CONTENT_DIR );
	if ( $plugins_dir ) {
		while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
			if ( isset( $_dropins[ $file ] ) ) {
				$plugin_files[] = $file;
			}
		}
	} else {
		return $dropins;
	}

	closedir( $plugins_dir );

	if ( empty( $plugin_files ) ) {
		return $dropins;
	}

	foreach ( $plugin_files as $plugin_file ) {
		if ( ! is_readable( WP_CONTENT_DIR . "/$plugin_file" ) ) {
			continue;
		}

		// Không áp dụng đánh dấu/dịch vì sẽ được lưu cache.
		$plugin_data = get_plugin_data( WP_CONTENT_DIR . "/$plugin_file", false, false );

		if ( empty( $plugin_data['Name'] ) ) {
			$plugin_data['Name'] = $plugin_file;
		}

		$dropins[ $plugin_file ] = $plugin_data;
	}

	uksort( $dropins, 'strnatcasecmp' );

	return $dropins;
}

/**
 * Trả về các plugin drop-in mà WordPress sử dụng.
 *
 * Chỉ bao gồm drop-in Multisite khi is_multisite()
 *
 * @since 3.0.0
 *
 * @return array[] {
 *     Khóa là tên file. Giá trị là mảng dữ liệu về drop-in.
 *
 *     @type array ...$0 {
 *         Dữ liệu về drop-in.
 *
 *         @type string      $0 Mục đích của drop-in.
 *         @type string|true $1 Tên hằng số phải là true để drop-in
 *                              được sử dụng, hoặc true nếu không cần hằng số.
 *     }
 * }
 */
function _get_dropins() {
	$dropins = array(
		'advanced-cache.php'      => array( __( 'Advanced caching plugin.' ), 'WP_CACHE' ),  // WP_CACHE
		'db.php'                  => array( __( 'Custom database class.' ), true ),          // Auto on load.
		'db-error.php'            => array( __( 'Custom database error message.' ), true ),  // Auto on error.
		'install.php'             => array( __( 'Custom installation script.' ), true ),     // Auto on installation.
		'maintenance.php'         => array( __( 'Custom maintenance message.' ), true ),     // Auto on maintenance.
		'object-cache.php'        => array( __( 'External object cache.' ), true ),          // Auto on load.
		'php-error.php'           => array( __( 'Custom PHP error message.' ), true ),       // Auto on error.
		'fatal-error-handler.php' => array( __( 'Custom PHP fatal error handler.' ), true ), // Auto on error.
	);

	if ( is_multisite() ) {
		$dropins['sunrise.php']        = array( __( 'Executed before Multisite is loaded.' ), 'SUNRISE' ); // SUNRISE
		$dropins['blog-deleted.php']   = array( __( 'Custom site deleted message.' ), true );   // Auto on deleted blog.
		$dropins['blog-inactive.php']  = array( __( 'Custom site inactive message.' ), true );  // Auto on inactive blog.
		$dropins['blog-suspended.php'] = array( __( 'Custom site suspended message.' ), true ); // Auto on archived or spammed blog.
	}

	return $dropins;
}

/**
 * Xác định xem plugin có đang hoạt động không.
 *
 * Chỉ các plugin được cài trong thư mục plugins/ mới có thể hoạt động.
 *
 * Các plugin trong thư mục mu-plugins/ không thể được "kích hoạt," nên hàm này sẽ
 * trả về false cho các plugin đó.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.5.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return bool True nếu nằm trong danh sách plugin hoạt động. False nếu không.
 */
function is_plugin_active( $plugin ) {
	return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || is_plugin_active_for_network( $plugin );
}

/**
 * Xác định xem plugin có không hoạt động không.
 *
 * Ngược lại với is_plugin_active(). Được sử dụng làm callback.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 3.1.0
 *
 * @see is_plugin_active()
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return bool True nếu không hoạt động. False nếu đang hoạt động.
 */
function is_plugin_inactive( $plugin ) {
	return ! is_plugin_active( $plugin );
}

/**
 * Xác định xem plugin có đang hoạt động cho toàn bộ mạng không.
 *
 * Chỉ các plugin được cài trong thư mục plugins/ mới có thể hoạt động.
 *
 * Các plugin trong thư mục mu-plugins/ không thể được "kích hoạt," nên hàm này sẽ
 * trả về false cho các plugin đó.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 3.0.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return bool True nếu hoạt động cho toàn mạng, ngược lại false.
 */
function is_plugin_active_for_network( $plugin ) {
	if ( ! is_multisite() ) {
		return false;
	}

	$plugins = get_site_option( 'active_sitewide_plugins' );
	if ( isset( $plugins[ $plugin ] ) ) {
		return true;
	}

	return false;
}

/**
 * Kiểm tra "Network: true" trong header plugin để xem plugin có nên
 * chỉ được kích hoạt như plugin toàn mạng không. Plugin cũng sẽ hoạt động
 * khi Multisite không được bật.
 *
 * Kiểm tra "Site Wide Only: true" để tương thích ngược.
 *
 * @since 3.0.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return bool True nếu plugin chỉ dành cho mạng, ngược lại false.
 */
function is_network_only_plugin( $plugin ) {
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
	if ( $plugin_data ) {
		return $plugin_data['Network'];
	}
	return false;
}

/**
 * Thử kích hoạt plugin trong "sandbox" và chuyển hướng khi thành công.
 *
 * Plugin đã được kích hoạt sẽ không bị kích hoạt lại.
 *
 * Cách hoạt động là thiết lập chuyển hướng đến lỗi trước khi thử
 * include file plugin. Nếu plugin thất bại, chuyển hướng sẽ không
 * bị ghi đè bằng thông báo thành công. Ngoài ra, các tùy chọn sẽ không
 * được cập nhật và hook kích hoạt sẽ không được gọi khi plugin lỗi.
 *
 * Cần lưu ý rằng đoạn code bên dưới không thực sự ngăn chặn lỗi
 * trong file. Code không nên được sử dụng ở nơi khác để tái tạo
 * "sandbox", vốn sử dụng chuyển hướng để hoạt động.
 * {@source 13 1}
 *
 * Nếu tìm thấy lỗi hoặc có văn bản được xuất ra, nó sẽ được bắt lại
 * để đảm bảo chuyển hướng thành công sẽ cập nhật chuyển hướng lỗi.
 *
 * @since 2.5.0
 * @since 5.2.0 Kiểm tra tương thích phiên bản WordPress và PHP.
 *
 * @param string $plugin       Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @param string $redirect     Tùy chọn. URL để chuyển hướng đến.
 * @param bool   $network_wide Tùy chọn. Có bật plugin cho tất cả site trong mạng
 *                             hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
 * @param bool   $silent       Tùy chọn. Có ngăn gọi các hook kích hoạt không. Mặc định false.
 * @return null|WP_Error Null khi thành công, WP_Error khi file không hợp lệ.
 */
function activate_plugin( $plugin, $redirect = '', $network_wide = false, $silent = false ) {
	$plugin = plugin_basename( trim( $plugin ) );

	if ( is_multisite() && ( $network_wide || is_network_only_plugin( $plugin ) ) ) {
		$network_wide        = true;
		$current             = get_site_option( 'active_sitewide_plugins', array() );
		$_GET['networkwide'] = 1; // Back compat for plugins looking for this value.
	} else {
		$current = get_option( 'active_plugins', array() );
	}

	$valid = validate_plugin( $plugin );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$requirements = validate_plugin_requirements( $plugin );
	if ( is_wp_error( $requirements ) ) {
		return $requirements;
	}

	if ( $network_wide && ! isset( $current[ $plugin ] )
		|| ! $network_wide && ! in_array( $plugin, $current, true )
	) {
		if ( ! empty( $redirect ) ) {
			// Chúng ta sẽ ghi đè điều này sau nếu plugin có thể được include mà không có lỗi nghiêm trọng.
			wp_redirect( add_query_arg( '_error_nonce', wp_create_nonce( 'plugin-activation-error_' . $plugin ), $redirect ) );
		}

		ob_start();

		// Tải plugin để kiểm tra xem nó có gây ra lỗi nào không.
		plugin_sandbox_scrape( $plugin );

		if ( ! $silent ) {
			/**
			 * Kích hoạt trước khi plugin được bật.
			 *
			 * Nếu plugin được kích hoạt ngầm (ví dụ trong quá trình cập nhật),
			 * hook này không được kích hoạt.
			 *
			 * @since 2.9.0
			 *
			 * @param string $plugin       Đường dẫn đến file plugin tương đối so với thư mục plugins.
			 * @param bool   $network_wide Có bật plugin cho tất cả site trong mạng
			 *                             hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
			 */
			do_action( 'activate_plugin', $plugin, $network_wide );

			/**
			 * Kích hoạt khi một plugin cụ thể đang được bật.
			 *
			 * Hook này là hook "kích hoạt" được sử dụng nội bộ bởi register_activation_hook().
			 * Phần động của tên hook, `$plugin`, tham chiếu đến basename của plugin.
			 *
			 * Nếu plugin được kích hoạt ngầm (ví dụ trong quá trình cập nhật), hook này không được kích hoạt.
			 *
			 * @since 2.0.0
			 *
			 * @param bool $network_wide Có bật plugin cho tất cả site trong mạng
			 *                           hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
			 */
			do_action( "activate_{$plugin}", $network_wide );
		}

		if ( $network_wide ) {
			$current            = get_site_option( 'active_sitewide_plugins', array() );
			$current[ $plugin ] = time();
			update_site_option( 'active_sitewide_plugins', $current );
		} else {
			$current   = get_option( 'active_plugins', array() );
			$current[] = $plugin;
			sort( $current );
			update_option( 'active_plugins', $current );
		}

		if ( ! $silent ) {
			/**
			 * Kích hoạt sau khi một plugin đã được bật.
			 *
			 * Nếu plugin được kích hoạt ngầm (ví dụ trong quá trình cập nhật),
			 * hook này không được kích hoạt.
			 *
			 * @since 2.9.0
			 *
			 * @param string $plugin       Đường dẫn đến file plugin tương đối so với thư mục plugins.
			 * @param bool   $network_wide Có bật plugin cho tất cả site trong mạng
			 *                             hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
			 */
			do_action( 'activated_plugin', $plugin, $network_wide );
		}

		if ( ob_get_length() > 0 ) {
			$output = ob_get_clean();
			return new WP_Error( 'unexpected_output', __( 'The plugin generated unexpected output.' ), $output );
		}

		ob_end_clean();
	}

	return null;
}

/**
 * Vô hiệu hóa một hoặc nhiều plugin.
 *
 * Hook vô hiệu hóa bị tắt bởi trình nâng cấp plugin bằng cách sử dụng tham số
 * $silent.
 *
 * @since 2.5.0
 *
 * @param string|string[] $plugins      Một plugin hoặc danh sách plugin cần vô hiệu hóa.
 * @param bool            $silent       Ngăn gọi các hook vô hiệu hóa. Mặc định false.
 * @param bool|null       $network_wide Có vô hiệu hóa plugin cho tất cả site trong mạng không.
 *                                      Giá trị null sẽ vô hiệu hóa plugin cho cả mạng
 *                                      và site hiện tại. Chỉ Multisite. Mặc định null.
 */
function deactivate_plugins( $plugins, $silent = false, $network_wide = null ) {
	if ( is_multisite() ) {
		$network_current = get_site_option( 'active_sitewide_plugins', array() );
	}
	$current    = get_option( 'active_plugins', array() );
	$do_blog    = false;
	$do_network = false;

	foreach ( (array) $plugins as $plugin ) {
		$plugin = plugin_basename( trim( $plugin ) );
		if ( ! is_plugin_active( $plugin ) ) {
			continue;
		}

		$network_deactivating = ( false !== $network_wide ) && is_plugin_active_for_network( $plugin );

		if ( ! $silent ) {
			/**
			 * Kích hoạt trước khi một plugin bị vô hiệu hóa.
			 *
			 * Nếu plugin bị vô hiệu hóa ngầm (ví dụ trong quá trình cập nhật),
			 * hook này không được kích hoạt.
			 *
			 * @since 2.9.0
			 *
			 * @param string $plugin               Đường dẫn đến file plugin tương đối so với thư mục plugins.
			 * @param bool   $network_deactivating Plugin có bị vô hiệu hóa cho tất cả site trong mạng
			 *                                     hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
			 */
			do_action( 'deactivate_plugin', $plugin, $network_deactivating );
		}

		if ( false !== $network_wide ) {
			if ( is_plugin_active_for_network( $plugin ) ) {
				$do_network = true;
				unset( $network_current[ $plugin ] );
			} elseif ( $network_wide ) {
				continue;
			}
		}

		if ( true !== $network_wide ) {
			$key = array_search( $plugin, $current, true );
			if ( false !== $key ) {
				$do_blog = true;
				unset( $current[ $key ] );
			}
		}

		if ( $do_blog && wp_is_recovery_mode() ) {
			list( $extension ) = explode( '/', $plugin );
			wp_paused_plugins()->delete( $extension );
		}

		if ( ! $silent ) {
			/**
			 * Kích hoạt khi một plugin cụ thể đang bị vô hiệu hóa.
			 *
			 * Hook này là hook "vô hiệu hóa" được sử dụng nội bộ bởi register_deactivation_hook().
			 * Phần động của tên hook, `$plugin`, tham chiếu đến basename của plugin.
			 *
			 * Nếu plugin bị vô hiệu hóa ngầm (ví dụ trong quá trình cập nhật), hook này không được kích hoạt.
			 *
			 * @since 2.0.0
			 *
			 * @param bool $network_deactivating Plugin có bị vô hiệu hóa cho tất cả site trong mạng
			 *                                   hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
			 */
			do_action( "deactivate_{$plugin}", $network_deactivating );

			/**
			 * Kích hoạt sau khi một plugin đã bị vô hiệu hóa.
			 *
			 * Nếu plugin bị vô hiệu hóa ngầm (ví dụ trong quá trình cập nhật),
			 * hook này không được kích hoạt.
			 *
			 * @since 2.9.0
			 *
			 * @param string $plugin               Đường dẫn đến file plugin tương đối so với thư mục plugins.
			 * @param bool   $network_deactivating Plugin có bị vô hiệu hóa cho tất cả site trong mạng
			 *                                     hay chỉ site hiện tại. Chỉ Multisite. Mặc định false.
			 */
			do_action( 'deactivated_plugin', $plugin, $network_deactivating );
		}
	}

	if ( $do_blog ) {
		update_option( 'active_plugins', $current );
	}
	if ( $do_network ) {
		update_site_option( 'active_sitewide_plugins', $network_current );
	}
}

/**
 * Kích hoạt nhiều plugin.
 *
 * Khi WP_Error được trả về, không có nghĩa là một trong các plugin có
 * lỗi. Nghĩa là một hoặc nhiều đường dẫn file plugin không hợp lệ.
 *
 * Việc thực thi sẽ bị dừng ngay khi một trong các plugin có lỗi.
 *
 * @since 2.6.0
 *
 * @param string|string[] $plugins      Một plugin hoặc danh sách plugin cần kích hoạt.
 * @param string          $redirect     Chuyển hướng đến trang sau khi kích hoạt thành công.
 * @param bool            $network_wide Có bật plugin cho tất cả site trong mạng không.
 *                                      Mặc định false.
 * @param bool            $silent       Ngăn gọi các hook kích hoạt. Mặc định false.
 * @return true|WP_Error True khi hoàn thành hoặc WP_Error nếu có lỗi trong quá trình kích hoạt plugin.
 */
function activate_plugins( $plugins, $redirect = '', $network_wide = false, $silent = false ) {
	if ( ! is_array( $plugins ) ) {
		$plugins = array( $plugins );
	}

	$errors = array();
	foreach ( $plugins as $plugin ) {
		if ( ! empty( $redirect ) ) {
			$redirect = add_query_arg( 'plugin', $plugin, $redirect );
		}
		$result = activate_plugin( $plugin, $redirect, $network_wide, $silent );
		if ( is_wp_error( $result ) ) {
			$errors[ $plugin ] = $result;
		}
	}

	if ( ! empty( $errors ) ) {
		return new WP_Error( 'plugins_invalid', __( 'One of the plugins is invalid.' ), $errors );
	}

	return true;
}

/**
 * Xóa thư mục và file của plugin cho một danh sách plugin.
 *
 * @since 2.6.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Lớp con filesystem của WordPress.
 *
 * @param string[] $plugins    Danh sách đường dẫn plugin cần xóa, tương đối so với thư mục plugins.
 * @param string   $deprecated Không sử dụng.
 * @return bool|null|WP_Error True khi thành công, false nếu `$plugins` rỗng, `WP_Error` khi thất bại.
 *                            `null` nếu cần thông tin xác thực filesystem để tiếp tục.
 */
function delete_plugins( $plugins, $deprecated = '' ) {
	global $wp_filesystem;

	if ( empty( $plugins ) ) {
		return false;
	}

	$checked = array();
	foreach ( $plugins as $plugin ) {
		$checked[] = 'checked[]=' . $plugin;
	}

	$url = wp_nonce_url( 'plugins.php?action=delete-selected&verify-delete=1&' . implode( '&', $checked ), 'bulk-plugins' );

	ob_start();
	$credentials = request_filesystem_credentials( $url );
	$data        = ob_get_clean();

	if ( false === $credentials ) {
		if ( ! empty( $data ) ) {
			require_once ABSPATH . 'wp-admin/admin-header.php';
			echo $data;
			require_once ABSPATH . 'wp-admin/admin-footer.php';
			exit;
		}
		return;
	}

	if ( ! WP_Filesystem( $credentials ) ) {
		ob_start();
		// Kết nối thất bại. Hiển thị lỗi và yêu cầu lại.
		request_filesystem_credentials( $url, '', true );
		$data = ob_get_clean();

		if ( ! empty( $data ) ) {
			require_once ABSPATH . 'wp-admin/admin-header.php';
			echo $data;
			require_once ABSPATH . 'wp-admin/admin-footer.php';
			exit;
		}
		return;
	}

	if ( ! is_object( $wp_filesystem ) ) {
		return new WP_Error( 'fs_unavailable', __( 'Could not access filesystem.' ) );
	}

	if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
		return new WP_Error( 'fs_error', __( 'Filesystem error.' ), $wp_filesystem->errors );
	}

	// Lấy thư mục plugin gốc.
	$plugins_dir = $wp_filesystem->wp_plugins_dir();
	if ( empty( $plugins_dir ) ) {
		return new WP_Error( 'fs_no_plugins_dir', __( 'Unable to locate WordPress plugin directory.' ) );
	}

	$plugins_dir = trailingslashit( $plugins_dir );

	$plugin_translations = wp_get_installed_translations( 'plugins' );

	$errors = array();

	foreach ( $plugins as $plugin_file ) {
		// Chạy hook gỡ cài đặt.
		if ( is_uninstallable_plugin( $plugin_file ) ) {
			uninstall_plugin( $plugin_file );
		}

		/**
		 * Kích hoạt ngay trước khi thử xóa plugin.
		 *
		 * @since 4.4.0
		 *
		 * @param string $plugin_file Đường dẫn đến file plugin tương đối so với thư mục plugins.
		 */
		do_action( 'delete_plugin', $plugin_file );

		$this_plugin_dir = trailingslashit( dirname( $plugins_dir . $plugin_file ) );

		/*
		 * Nếu plugin nằm trong thư mục riêng, xóa đệ quy thư mục đó.
		 * Kiểm tra cơ bản dựa trên việc plugin có chứa dấu phân tách thư mục VÀ không phải thư mục plugin gốc.
		 */
		if ( strpos( $plugin_file, '/' ) && $this_plugin_dir !== $plugins_dir ) {
			$deleted = $wp_filesystem->delete( $this_plugin_dir, true );
		} else {
			$deleted = $wp_filesystem->delete( $plugins_dir . $plugin_file );
		}

		/**
		 * Kích hoạt ngay sau khi thử xóa plugin.
		 *
		 * @since 4.4.0
		 *
		 * @param string $plugin_file Đường dẫn đến file plugin tương đối so với thư mục plugins.
		 * @param bool   $deleted     Việc xóa plugin có thành công không.
		 */
		do_action( 'deleted_plugin', $plugin_file, $deleted );

		if ( ! $deleted ) {
			$errors[] = $plugin_file;
			continue;
		}

		$plugin_slug = dirname( $plugin_file );

		if ( 'hello.php' === $plugin_file ) {
			$plugin_slug = 'hello-dolly';
		}

		// Xóa file ngôn ngữ, không hiển thị lỗi.
		if ( '.' !== $plugin_slug && ! empty( $plugin_translations[ $plugin_slug ] ) ) {
			$translations = $plugin_translations[ $plugin_slug ];

			foreach ( $translations as $translation => $data ) {
				$wp_filesystem->delete( WP_LANG_DIR . '/plugins/' . $plugin_slug . '-' . $translation . '.po' );
				$wp_filesystem->delete( WP_LANG_DIR . '/plugins/' . $plugin_slug . '-' . $translation . '.mo' );
				$wp_filesystem->delete( WP_LANG_DIR . '/plugins/' . $plugin_slug . '-' . $translation . '.l10n.php' );

				$json_translation_files = glob( WP_LANG_DIR . '/plugins/' . $plugin_slug . '-' . $translation . '-*.json' );
				if ( $json_translation_files ) {
					array_map( array( $wp_filesystem, 'delete' ), $json_translation_files );
				}
			}
		}
	}

	// Xóa các plugin đã xóa khỏi danh sách cập nhật plugin.
	$current = get_site_transient( 'update_plugins' );
	if ( $current ) {
		// Không xóa các plugin chưa được xóa.
		$deleted = array_diff( $plugins, $errors );

		foreach ( $deleted as $plugin_file ) {
			unset( $current->response[ $plugin_file ] );
		}

		set_site_transient( 'update_plugins', $current );
	}

	if ( ! empty( $errors ) ) {
		if ( 1 === count( $errors ) ) {
			/* translators: %s: Plugin filename. */
			$message = __( 'Could not fully remove the plugin %s.' );
		} else {
			/* translators: %s: Comma-separated list of plugin filenames. */
			$message = __( 'Could not fully remove the plugins %s.' );
		}

		return new WP_Error( 'could_not_remove_plugin', sprintf( $message, implode( ', ', $errors ) ) );
	}

	return true;
}

/**
 * Xác thực các plugin đang hoạt động.
 *
 * Xác thực tất cả plugin đang hoạt động, vô hiệu hóa các plugin không hợp lệ
 * và trả về mảng các plugin đã bị vô hiệu hóa.
 *
 * @since 2.5.0
 * @return WP_Error[] Mảng lỗi plugin được đánh khóa bởi tên file plugin.
 */
function validate_active_plugins() {
	$plugins = get_option( 'active_plugins', array() );
	// Kiểm tra kiểu biến: array.
	if ( ! is_array( $plugins ) ) {
		update_option( 'active_plugins', array() );
		$plugins = array();
	}

	if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
		$plugins         = array_merge( $plugins, array_keys( $network_plugins ) );
	}

	if ( empty( $plugins ) ) {
		return array();
	}

	$invalid = array();

	// Các plugin không hợp lệ bị vô hiệu hóa.
	foreach ( $plugins as $plugin ) {
		$result = validate_plugin( $plugin );
		if ( is_wp_error( $result ) ) {
			$invalid[ $plugin ] = $result;
			deactivate_plugins( $plugin, true );
		}
	}
	return $invalid;
}

/**
 * Xác thực đường dẫn plugin.
 *
 * Kiểm tra rằng file plugin chính tồn tại và là plugin hợp lệ. Xem validate_file().
 *
 * @since 2.5.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return int|WP_Error 0 khi thành công, WP_Error khi thất bại.
 */
function validate_plugin( $plugin ) {
	if ( validate_file( $plugin ) ) {
		return new WP_Error( 'plugin_invalid', __( 'Invalid plugin path.' ) );
	}
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
		return new WP_Error( 'plugin_not_found', __( 'Plugin file does not exist.' ) );
	}

	$installed_plugins = get_plugins();
	if ( ! isset( $installed_plugins[ $plugin ] ) ) {
		return new WP_Error( 'no_plugin_header', __( 'The plugin does not have a valid header.' ) );
	}
	return 0;
}

/**
 * Xác thực yêu cầu plugin cho phiên bản WordPress và PHP.
 *
 * Sử dụng thông tin từ các header `Requires at least`, `Requires PHP` và `Requires Plugins`
 * được định nghĩa trong file PHP chính của plugin.
 *
 * @since 5.2.0
 * @since 5.3.0 Thêm hỗ trợ đọc header từ file PHP chính của plugin,
 *              với `readme.txt` làm phương án dự phòng.
 * @since 5.8.0 Loại bỏ hỗ trợ sử dụng `readme.txt` làm phương án dự phòng.
 * @since 6.5.0 Thêm hỗ trợ cho header 'Requires Plugins'.
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return true|WP_Error True nếu đáp ứng yêu cầu, WP_Error khi thất bại.
 */
function validate_plugin_requirements( $plugin ) {
	$plugin_headers = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

	$requirements = array(
		'requires'         => ! empty( $plugin_headers['RequiresWP'] ) ? $plugin_headers['RequiresWP'] : '',
		'requires_php'     => ! empty( $plugin_headers['RequiresPHP'] ) ? $plugin_headers['RequiresPHP'] : '',
		'requires_plugins' => ! empty( $plugin_headers['RequiresPlugins'] ) ? $plugin_headers['RequiresPlugins'] : '',
	);

	$compatible_wp  = is_wp_version_compatible( $requirements['requires'] );
	$compatible_php = is_php_version_compatible( $requirements['requires_php'] );

	$php_update_message = '</p><p>' . sprintf(
		/* translators: %s: URL to Update PHP page. */
		__( '<a href="%s">Learn more about updating PHP</a>.' ),
		esc_url( wp_get_update_php_url() )
	);

	$annotation = wp_get_update_php_annotation();

	if ( $annotation ) {
		$php_update_message .= '</p><p><em>' . $annotation . '</em>';
	}

	if ( ! $compatible_wp && ! $compatible_php ) {
		return new WP_Error(
			'plugin_wp_php_incompatible',
			'<p>' . sprintf(
				/* translators: 1: Current WordPress version, 2: Current PHP version, 3: Plugin name, 4: Required WordPress version, 5: Required PHP version. */
				_x( '<strong>Error:</strong> Current versions of WordPress (%1$s) and PHP (%2$s) do not meet minimum requirements for %3$s. The plugin requires WordPress %4$s and PHP %5$s.', 'plugin' ),
				get_bloginfo( 'version' ),
				PHP_VERSION,
				$plugin_headers['Name'],
				$requirements['requires'],
				$requirements['requires_php']
			) . $php_update_message . '</p>'
		);
	} elseif ( ! $compatible_php ) {
		return new WP_Error(
			'plugin_php_incompatible',
			'<p>' . sprintf(
				/* translators: 1: Current PHP version, 2: Plugin name, 3: Required PHP version. */
				_x( '<strong>Error:</strong> Current PHP version (%1$s) does not meet minimum requirements for %2$s. The plugin requires PHP %3$s.', 'plugin' ),
				PHP_VERSION,
				$plugin_headers['Name'],
				$requirements['requires_php']
			) . $php_update_message . '</p>'
		);
	} elseif ( ! $compatible_wp ) {
		return new WP_Error(
			'plugin_wp_incompatible',
			'<p>' . sprintf(
				/* translators: 1: Current WordPress version, 2: Plugin name, 3: Required WordPress version. */
				_x( '<strong>Error:</strong> Current WordPress version (%1$s) does not meet minimum requirements for %2$s. The plugin requires WordPress %3$s.', 'plugin' ),
				get_bloginfo( 'version' ),
				$plugin_headers['Name'],
				$requirements['requires']
			) . '</p>'
		);
	}

	WP_Plugin_Dependencies::initialize();

	if ( WP_Plugin_Dependencies::has_unmet_dependencies( $plugin ) ) {
		$dependency_names       = WP_Plugin_Dependencies::get_dependency_names( $plugin );
		$unmet_dependencies     = array();
		$unmet_dependency_names = array();

		foreach ( $dependency_names as $dependency => $dependency_name ) {
			$dependency_file = WP_Plugin_Dependencies::get_dependency_filepath( $dependency );

			if ( false === $dependency_file ) {
				$unmet_dependencies['not_installed'][ $dependency ] = $dependency_name;
				$unmet_dependency_names[]                           = $dependency_name;
			} elseif ( is_plugin_inactive( $dependency_file ) ) {
				$unmet_dependencies['inactive'][ $dependency ] = $dependency_name;
				$unmet_dependency_names[]                      = $dependency_name;
			}
		}

		$error_message = sprintf(
			/* translators: 1: Plugin name, 2: Number of plugins, 3: A comma-separated list of plugin names. */
			_n(
				'<strong>Error:</strong> %1$s requires %2$d plugin to be installed and activated: %3$s.',
				'<strong>Error:</strong> %1$s requires %2$d plugins to be installed and activated: %3$s.',
				count( $unmet_dependency_names )
			),
			$plugin_headers['Name'],
			count( $unmet_dependency_names ),
			implode( wp_get_list_item_separator(), $unmet_dependency_names )
		);

		if ( is_multisite() ) {
			if ( current_user_can( 'manage_network_plugins' ) ) {
				$error_message .= ' ' . sprintf(
					/* translators: %s: Link to the plugins page. */
					__( '<a href="%s">Manage plugins</a>.' ),
					esc_url( network_admin_url( 'plugins.php' ) )
				);
			} else {
				$error_message .= ' ' . __( 'Please contact your network administrator.' );
			}
		} else {
			$error_message .= ' ' . sprintf(
				/* translators: %s: Link to the plugins page. */
				__( '<a href="%s">Manage plugins</a>.' ),
				esc_url( admin_url( 'plugins.php' ) )
			);
		}

		return new WP_Error(
			'plugin_missing_dependencies',
			"<p>{$error_message}</p>",
			$unmet_dependencies
		);
	}

	return true;
}

/**
 * Xác định xem plugin có thể được gỡ cài đặt không.
 *
 * @since 2.7.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return bool Plugin có thể được gỡ cài đặt hay không.
 */
function is_uninstallable_plugin( $plugin ) {
	$file = plugin_basename( $plugin );

	$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );
	if ( isset( $uninstallable_plugins[ $file ] ) || file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		return true;
	}

	return false;
}

/**
 * Gỡ cài đặt một plugin đơn lẻ.
 *
 * Gọi hook gỡ cài đặt, nếu có.
 *
 * @since 2.7.0
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return true|void True nếu file uninstall.php của plugin đã được tìm thấy và include.
 *                   Void trong trường hợp khác.
 */
function uninstall_plugin( $plugin ) {
	$file = plugin_basename( $plugin );

	$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );

	/**
	 * Kích hoạt trong uninstall_plugin() ngay trước khi plugin được gỡ cài đặt.
	 *
	 * @since 4.5.0
	 *
	 * @param string $plugin                Đường dẫn đến file plugin tương đối so với thư mục plugins.
	 * @param array  $uninstallable_plugins Các plugin có thể gỡ cài đặt.
	 */
	do_action( 'pre_uninstall_plugin', $plugin, $uninstallable_plugins );

	if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		if ( isset( $uninstallable_plugins[ $file ] ) ) {
			unset( $uninstallable_plugins[ $file ] );
			update_option( 'uninstall_plugins', $uninstallable_plugins );
		}
		unset( $uninstallable_plugins );

		define( 'WP_UNINSTALL_PLUGIN', $file );

		wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file );
		include_once WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php';

		return true;
	}

	if ( isset( $uninstallable_plugins[ $file ] ) ) {
		$callable = $uninstallable_plugins[ $file ];
		unset( $uninstallable_plugins[ $file ] );
		update_option( 'uninstall_plugins', $uninstallable_plugins );
		unset( $uninstallable_plugins );

		wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file );
		include_once WP_PLUGIN_DIR . '/' . $file;

		add_action( "uninstall_{$file}", $callable );

		/**
		 * Kích hoạt trong uninstall_plugin() khi plugin đã được gỡ cài đặt.
		 *
		 * Action này nối tiền tố 'uninstall_' với basename của plugin
		 * được truyền vào uninstall_plugin() để tạo action có tên động.
		 *
		 * @since 2.7.0
		 */
		do_action( "uninstall_{$file}" );
	}
}

//
// Menu.
//

/**
 * Thêm một trang menu cấp cao nhất.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 1.5.0
 *
 * @global array $menu
 * @global array $admin_page_hooks
 * @global array $_registered_pages
 * @global array $_parent_pages
 *
 * @param string    $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string    $menu_title Văn bản sử dụng cho menu.
 * @param string    $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string    $menu_slug  Tên slug để tham chiếu đến menu này. Nên là duy nhất cho trang menu này và chỉ
 *                              bao gồm các ký tự chữ thường, số, gạch ngang và gạch dưới để tương thích
 *                              với sanitize_key().
 * @param callable  $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param string    $icon_url   Tùy chọn. URL đến biểu tượng sử dụng cho menu này.
 *                              * Truyền SVG mã hóa base64 sử dụng data URI, sẽ được tô màu phù hợp
 *                                với bảng màu. Nên bắt đầu bằng 'data:image/svg+xml;base64,'.
 *                              * Truyền tên lớp helper Dashicons để sử dụng biểu tượng font,
 *                                ví dụ 'dashicons-chart-pie'.
 *                              * Truyền 'none' để để trống div.wp-menu-image và thêm biểu tượng qua CSS.
 * @param int|float $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string Hook_suffix của trang kết quả.
 */
function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) {
	global $menu, $admin_page_hooks, $_registered_pages, $_parent_pages;

	$menu_slug = plugin_basename( $menu_slug );

	$admin_page_hooks[ $menu_slug ] = sanitize_title( $menu_title );

	$hookname = get_plugin_page_hookname( $menu_slug, '' );

	if ( ! empty( $callback ) && ! empty( $hookname ) && current_user_can( $capability ) ) {
		add_action( $hookname, $callback );
	}

	if ( empty( $icon_url ) ) {
		$icon_url   = 'dashicons-admin-generic';
		$icon_class = 'menu-icon-generic ';
	} else {
		$icon_url   = set_url_scheme( $icon_url );
		$icon_class = '';
	}

	$new_menu = array( $menu_title, $capability, $menu_slug, $page_title, 'menu-top ' . $icon_class . $hookname, $hookname, $icon_url );

	if ( null !== $position && ! is_numeric( $position ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: add_menu_page() */
				__( 'The seventh parameter passed to %s should be numeric representing menu position.' ),
				'<code>add_menu_page()</code>'
			),
			'6.0.0'
		);
		$position = null;
	}

	if ( null === $position || ! is_numeric( $position ) ) {
		$menu[] = $new_menu;
	} elseif ( isset( $menu[ (string) $position ] ) ) {
		$collision_avoider = base_convert( substr( md5( $menu_slug . $menu_title ), -4 ), 16, 10 ) * 0.00001;
		$position          = (string) ( $position + $collision_avoider );
		$menu[ $position ] = $new_menu;
	} else {
		/*
		 * Ép kiểu vị trí menu sang chuỗi.
		 *
		 * Điều này cho phép truyền số thực làm vị trí. PHP thường ép số thực sang
		 * số nguyên, cách này đảm bảo số thực giữ lại phần thập phân.
		 *
		 * Chuỗi chứa giá trị số nguyên, ví dụ "10", được xử lý như chỉ mục số.
		 */
		$position          = (string) $position;
		$menu[ $position ] = $new_menu;
	}

	$_registered_pages[ $hookname ] = true;

	// Không có parent vì là cấp cao nhất.
	$_parent_pages[ $menu_slug ] = false;

	return $hookname;
}

/**
 * Thêm một trang menu con.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 1.5.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @global array $submenu
 * @global array $menu
 * @global array $_wp_real_parent_file
 * @global bool  $_wp_submenu_nopriv
 * @global array $_registered_pages
 * @global array $_parent_pages
 *
 * @param string    $parent_slug Tên slug cho menu cha (hoặc tên file của trang quản trị
 *                               WordPress chuẩn).
 * @param string    $page_title  Văn bản hiển thị trong thẻ title của trang khi menu
 *                               được chọn.
 * @param string    $menu_title  Văn bản sử dụng cho menu.
 * @param string    $capability  Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string    $menu_slug   Tên slug để tham chiếu đến menu này. Nên là duy nhất cho menu này
 *                               và chỉ bao gồm các ký tự chữ thường, số, gạch ngang và gạch dưới
 *                               để tương thích với sanitize_key().
 * @param callable  $callback    Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int|float $position    Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	global $submenu, $menu, $_wp_real_parent_file, $_wp_submenu_nopriv,
		$_registered_pages, $_parent_pages;

	$menu_slug   = plugin_basename( $menu_slug );
	$parent_slug = plugin_basename( $parent_slug );

	if ( isset( $_wp_real_parent_file[ $parent_slug ] ) ) {
		$parent_slug = $_wp_real_parent_file[ $parent_slug ];
	}

	if ( ! current_user_can( $capability ) ) {
		$_wp_submenu_nopriv[ $parent_slug ][ $menu_slug ] = true;
		return false;
	}

	/*
	 * Nếu parent chưa có submenu, thêm liên kết đến parent
	 * làm mục đầu tiên trong submenu. Nếu file submenu giống file
	 * parent thì ai đó đang cố liên kết ngược lại parent thủ công. Trong
	 * trường hợp này, không tự động thêm liên kết ngược để tránh trùng lặp.
	 */
	if ( ! isset( $submenu[ $parent_slug ] ) && $menu_slug !== $parent_slug ) {
		foreach ( (array) $menu as $parent_menu ) {
			if ( $parent_menu[2] === $parent_slug && current_user_can( $parent_menu[1] ) ) {
				$submenu[ $parent_slug ][] = array_slice( $parent_menu, 0, 4 );
			}
		}
	}

	$new_sub_menu = array( $menu_title, $capability, $menu_slug, $page_title );

	if ( null !== $position && ! is_numeric( $position ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: add_submenu_page() */
				__( 'The seventh parameter passed to %s should be numeric representing menu position.' ),
				'<code>add_submenu_page()</code>'
			),
			'5.3.0'
		);
		$position = null;
	}

	if (
		null === $position ||
		( ! isset( $submenu[ $parent_slug ] ) || $position >= count( $submenu[ $parent_slug ] ) )
	) {
		$submenu[ $parent_slug ][] = $new_sub_menu;
	} else {
		// Kiểm tra vị trí âm.
		$position = max( $position, 0 );
		if ( 0 === $position ) {
			// Với vị trí âm hoặc `0`, thêm submenu vào đầu.
			array_unshift( $submenu[ $parent_slug ], $new_sub_menu );
		} else {
			$position = absint( $position );
			// Lấy tất cả các mục trước điểm chèn.
			$before_items = array_slice( $submenu[ $parent_slug ], 0, $position, true );
			// Lấy tất cả các mục sau điểm chèn.
			$after_items = array_slice( $submenu[ $parent_slug ], $position, null, true );
			// Thêm mục mới.
			$before_items[] = $new_sub_menu;
			// Gộp các mục.
			$submenu[ $parent_slug ] = array_merge( $before_items, $after_items );
		}
	}

	// Sắp xếp mảng parent.
	ksort( $submenu[ $parent_slug ] );

	$hookname = get_plugin_page_hookname( $menu_slug, $parent_slug );
	if ( ! empty( $callback ) && ! empty( $hookname ) ) {
		add_action( $hookname, $callback );
	}

	$_registered_pages[ $hookname ] = true;

	/*
	 * Tương thích ngược cho các plugin sử dụng add_management_page().
	 * Xem wp-admin/admin.php để biết về chuyển hướng từ edit.php sang tools.php.
	 */
	if ( 'tools.php' === $parent_slug ) {
		$_registered_pages[ get_plugin_page_hookname( $menu_slug, 'edit.php' ) ] = true;
	}

	// Không có parent vì là cấp cao nhất.
	$_parent_pages[ $menu_slug ] = $parent_slug;

	return $hookname;
}

/**
 * Thêm trang menu con vào menu chính Công cụ.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 1.5.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_management_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'tools.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Cài đặt.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 1.5.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Giao diện.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.0.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'themes.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Plugin.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 3.0.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_plugins_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'plugins.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Người dùng/Hồ sơ.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.1.3
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_users_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	if ( current_user_can( 'edit_users' ) ) {
		$parent = 'users.php';
	} else {
		$parent = 'profile.php';
	}
	return add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Bảng tin.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.7.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_dashboard_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'index.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Bài viết.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.7.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_posts_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'edit.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Phương tiện.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.7.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_media_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'upload.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Liên kết.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.7.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_links_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'link-manager.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Trang.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.7.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_pages_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'edit.php?post_type=page', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Thêm trang menu con vào menu chính Bình luận.
 *
 * Hàm này nhận một capability sẽ được sử dụng để xác định trang có
 * được bao gồm trong menu hay không.
 *
 * Hàm được hook vào để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có capability yêu cầu.
 *
 * @since 2.7.0
 * @since 5.3.0 Thêm tham số `$position`.
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ title của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Capability yêu cầu để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param int      $position   Tùy chọn. Vị trí trong thứ tự menu mà mục này nên xuất hiện.
 * @return string|false Hook_suffix của trang kết quả, hoặc false nếu người dùng không có capability yêu cầu.
 */
function add_comments_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return add_submenu_page( 'edit-comments.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position );
}

/**
 * Xóa một menu quản trị cấp cao nhất.
 *
 * Ví dụ sử dụng:
 *
 *  - `remove_menu_page( 'tools.php' )`
 *  - `remove_menu_page( 'plugin_menu_slug' )`
 *
 * @since 3.1.0
 *
 * @global array $menu
 *
 * @param string $menu_slug Slug của menu.
 * @return array|false Menu đã xóa khi thành công, false nếu không tìm thấy.
 */
function remove_menu_page( $menu_slug ) {
	global $menu;

	foreach ( $menu as $i => $item ) {
		if ( $menu_slug === $item[2] ) {
			unset( $menu[ $i ] );
			return $item;
		}
	}

	return false;
}

/**
 * Xóa một menu con quản trị.
 *
 * Ví dụ sử dụng:
 *
 *  - `remove_submenu_page( 'themes.php', 'nav-menus.php' )`
 *  - `remove_submenu_page( 'tools.php', 'plugin_submenu_slug' )`
 *  - `remove_submenu_page( 'plugin_menu_slug', 'plugin_submenu_slug' )`
 *
 * @since 3.1.0
 *
 * @global array $submenu
 *
 * @param string $menu_slug    Slug cho menu cha.
 * @param string $submenu_slug Slug của menu con.
 * @return array|false Menu con đã xóa khi thành công, false nếu không tìm thấy.
 */
function remove_submenu_page( $menu_slug, $submenu_slug ) {
	global $submenu;

	if ( ! isset( $submenu[ $menu_slug ] ) ) {
		return false;
	}

	foreach ( $submenu[ $menu_slug ] as $i => $item ) {
		if ( $submenu_slug === $item[2] ) {
			unset( $submenu[ $menu_slug ][ $i ] );
			return $item;
		}
	}

	return false;
}

/**
 * Lấy URL để truy cập một trang menu cụ thể dựa trên slug đã đăng ký.
 *
 * Nếu slug chưa được đăng ký đúng cách, sẽ không trả về URL.
 *
 * @since 3.0.0
 *
 * @global array $_parent_pages
 *
 * @param string $menu_slug Tên slug để tham chiếu đến menu này (nên là duy nhất cho menu này).
 * @param bool   $display   Tùy chọn. Có hiển thị URL hay không. Mặc định true.
 * @return string URL trang menu.
 */
function menu_page_url( $menu_slug, $display = true ) {
	global $_parent_pages;

	if ( isset( $_parent_pages[ $menu_slug ] ) ) {
		$parent_slug = $_parent_pages[ $menu_slug ];

		if ( $parent_slug && ! isset( $_parent_pages[ $parent_slug ] ) ) {
			$url = admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
		} else {
			$url = admin_url( 'admin.php?page=' . $menu_slug );
		}
	} else {
		$url = '';
	}

	$url = esc_url( $url );

	if ( $display ) {
		echo $url;
	}

	return $url;
}

//
// Hỗ trợ Menu có thể ghi đè -- Riêng tư.
//
/**
 * Lấy file cha của trang quản trị hiện tại.
 *
 * @since 1.5.0
 *
 * @global string $parent_file
 * @global array  $menu
 * @global array  $submenu
 * @global string $pagenow              Tên file của màn hình hiện tại.
 * @global string $typenow              Loại bài viết của màn hình hiện tại.
 * @global string $plugin_page
 * @global array  $_wp_real_parent_file
 * @global array  $_wp_menu_nopriv
 * @global array  $_wp_submenu_nopriv
 *
 * @param string $parent_page Tùy chọn. Tên slug cho menu cha (hoặc tên file
 *                            của trang quản trị WordPress chuẩn). Mặc định chuỗi rỗng.
 * @return string File cha của trang quản trị hiện tại.
 */
function get_admin_page_parent( $parent_page = '' ) {
	global $parent_file, $menu, $submenu, $pagenow, $typenow,
		$plugin_page, $_wp_real_parent_file, $_wp_menu_nopriv, $_wp_submenu_nopriv;

	if ( ! empty( $parent_page ) && 'admin.php' !== $parent_page ) {
		if ( isset( $_wp_real_parent_file[ $parent_page ] ) ) {
			$parent_page = $_wp_real_parent_file[ $parent_page ];
		}

		return $parent_page;
	}

	if ( 'admin.php' === $pagenow && isset( $plugin_page ) ) {
		foreach ( (array) $menu as $parent_menu ) {
			if ( $parent_menu[2] === $plugin_page ) {
				$parent_file = $plugin_page;

				if ( isset( $_wp_real_parent_file[ $parent_file ] ) ) {
					$parent_file = $_wp_real_parent_file[ $parent_file ];
				}

				return $parent_file;
			}
		}
		if ( isset( $_wp_menu_nopriv[ $plugin_page ] ) ) {
			$parent_file = $plugin_page;

			if ( isset( $_wp_real_parent_file[ $parent_file ] ) ) {
					$parent_file = $_wp_real_parent_file[ $parent_file ];
			}

			return $parent_file;
		}
	}

	if ( isset( $plugin_page ) && isset( $_wp_submenu_nopriv[ $pagenow ][ $plugin_page ] ) ) {
		$parent_file = $pagenow;

		if ( isset( $_wp_real_parent_file[ $parent_file ] ) ) {
			$parent_file = $_wp_real_parent_file[ $parent_file ];
		}

		return $parent_file;
	}

	foreach ( array_keys( (array) $submenu ) as $parent_page ) {
		foreach ( $submenu[ $parent_page ] as $submenu_array ) {
			if ( isset( $_wp_real_parent_file[ $parent_page ] ) ) {
				$parent_page = $_wp_real_parent_file[ $parent_page ];
			}

			if ( ! empty( $typenow ) && "$pagenow?post_type=$typenow" === $submenu_array[2] ) {
				$parent_file = $parent_page;
				return $parent_page;
			} elseif ( empty( $typenow ) && $pagenow === $submenu_array[2]
				&& ( empty( $parent_file ) || ! str_contains( $parent_file, '?' ) )
			) {
				$parent_file = $parent_page;
				return $parent_page;
			} elseif ( isset( $plugin_page ) && $plugin_page === $submenu_array[2] ) {
				$parent_file = $parent_page;
				return $parent_page;
			}
		}
	}

	if ( empty( $parent_file ) ) {
		$parent_file = '';
	}
	return '';
}

/**
 * Lấy tiêu đề của trang quản trị hiện tại.
 *
 * @since 1.5.0
 *
 * @global string $title       Tiêu đề của màn hình hiện tại.
 * @global array  $menu
 * @global array  $submenu
 * @global string $pagenow     Tên file của màn hình hiện tại.
 * @global string $typenow     Loại bài viết của màn hình hiện tại.
 * @global string $plugin_page
 *
 * @return string Tiêu đề của trang quản trị hiện tại.
 */
function get_admin_page_title() {
	global $title, $menu, $submenu, $pagenow, $typenow, $plugin_page;

	if ( ! empty( $title ) ) {
		return $title;
	}

	$hook = get_plugin_page_hook( $plugin_page, $pagenow );

	$parent  = get_admin_page_parent();
	$parent1 = $parent;

	if ( empty( $parent ) ) {
		foreach ( (array) $menu as $menu_array ) {
			if ( isset( $menu_array[3] ) ) {
				if ( $menu_array[2] === $pagenow ) {
					$title = $menu_array[3];
					return $menu_array[3];
				} elseif ( isset( $plugin_page ) && $plugin_page === $menu_array[2] && $hook === $menu_array[5] ) {
					$title = $menu_array[3];
					return $menu_array[3];
				}
			} else {
				$title = $menu_array[0];
				return $title;
			}
		}
	} else {
		foreach ( array_keys( $submenu ) as $parent ) {
			foreach ( $submenu[ $parent ] as $submenu_array ) {
				if ( isset( $plugin_page )
					&& $plugin_page === $submenu_array[2]
					&& ( $pagenow === $parent
						|| $plugin_page === $parent
						|| $plugin_page === $hook
						|| 'admin.php' === $pagenow && $parent1 !== $submenu_array[2]
						|| ! empty( $typenow ) && "$pagenow?post_type=$typenow" === $parent )
					) {
						$title = $submenu_array[3];
						return $submenu_array[3];
				}

				if ( $submenu_array[2] !== $pagenow || isset( $_GET['page'] ) ) { // Không phải trang hiện tại.
					continue;
				}

				if ( isset( $submenu_array[3] ) ) {
					$title = $submenu_array[3];
					return $submenu_array[3];
				} else {
					$title = $submenu_array[0];
					return $title;
				}
			}
		}
		if ( empty( $title ) ) {
			foreach ( $menu as $menu_array ) {
				if ( isset( $plugin_page )
					&& $plugin_page === $menu_array[2]
					&& 'admin.php' === $pagenow
					&& $parent1 === $menu_array[2]
				) {
						$title = $menu_array[3];
						return $menu_array[3];
				}
			}
		}
	}

	return $title;
}

/**
 * Lấy hook được gắn vào trang quản trị của plugin.
 *
 * @since 1.5.0
 *
 * @param string $plugin_page Tên slug của trang plugin.
 * @param string $parent_page Tên slug cho menu cha (hoặc tên file của trang
 *                            quản trị WordPress chuẩn).
 * @return string|null Hook được gắn vào trang plugin, null trong trường hợp khác.
 */
function get_plugin_page_hook( $plugin_page, $parent_page ) {
	$hook = get_plugin_page_hookname( $plugin_page, $parent_page );
	if ( has_action( $hook ) ) {
		return $hook;
	} else {
		return null;
	}
}

/**
 * Lấy tên hook cho trang quản trị của plugin.
 *
 * @since 1.5.0
 *
 * @global array $admin_page_hooks
 *
 * @param string $plugin_page Tên slug của trang plugin.
 * @param string $parent_page Tên slug cho menu cha (hoặc tên file của trang
 *                            quản trị WordPress chuẩn).
 * @return string Tên hook cho trang plugin.
 */
function get_plugin_page_hookname( $plugin_page, $parent_page ) {
	global $admin_page_hooks;

	$parent = get_admin_page_parent( $parent_page );

	$page_type = 'admin';
	if ( empty( $parent_page ) || 'admin.php' === $parent_page || isset( $admin_page_hooks[ $plugin_page ] ) ) {
		if ( isset( $admin_page_hooks[ $plugin_page ] ) ) {
			$page_type = 'toplevel';
		} elseif ( isset( $admin_page_hooks[ $parent ] ) ) {
			$page_type = $admin_page_hooks[ $parent ];
		}
	} elseif ( isset( $admin_page_hooks[ $parent ] ) ) {
		$page_type = $admin_page_hooks[ $parent ];
	}

	$plugin_name = preg_replace( '!\.php!', '', $plugin_page );

	return $page_type . '_page_' . $plugin_name;
}

/**
 * Xác định xem người dùng hiện tại có thể truy cập trang quản trị hiện tại không.
 *
 * @since 1.5.0
 *
 * @global string $pagenow            Tên file của màn hình hiện tại.
 * @global array  $menu
 * @global array  $submenu
 * @global array  $_wp_menu_nopriv
 * @global array  $_wp_submenu_nopriv
 * @global string $plugin_page
 * @global array  $_registered_pages
 *
 * @return bool True nếu người dùng hiện tại có thể truy cập trang quản trị, false trong trường hợp khác.
 */
function user_can_access_admin_page() {
	global $pagenow, $menu, $submenu, $_wp_menu_nopriv, $_wp_submenu_nopriv,
		$plugin_page, $_registered_pages;

	$parent = get_admin_page_parent();

	if ( ! isset( $plugin_page ) && isset( $_wp_submenu_nopriv[ $parent ][ $pagenow ] ) ) {
		return false;
	}

	if ( isset( $plugin_page ) ) {
		if ( isset( $_wp_submenu_nopriv[ $parent ][ $plugin_page ] ) ) {
			return false;
		}

		$hookname = get_plugin_page_hookname( $plugin_page, $parent );

		if ( ! isset( $_registered_pages[ $hookname ] ) ) {
			return false;
		}
	}

	if ( empty( $parent ) ) {
		if ( isset( $_wp_menu_nopriv[ $pagenow ] ) ) {
			return false;
		}
		if ( isset( $_wp_submenu_nopriv[ $pagenow ][ $pagenow ] ) ) {
			return false;
		}
		if ( isset( $plugin_page ) && isset( $_wp_submenu_nopriv[ $pagenow ][ $plugin_page ] ) ) {
			return false;
		}
		if ( isset( $plugin_page ) && isset( $_wp_menu_nopriv[ $plugin_page ] ) ) {
			return false;
		}

		foreach ( array_keys( $_wp_submenu_nopriv ) as $key ) {
			if ( isset( $_wp_submenu_nopriv[ $key ][ $pagenow ] ) ) {
				return false;
			}
			if ( isset( $plugin_page ) && isset( $_wp_submenu_nopriv[ $key ][ $plugin_page ] ) ) {
				return false;
			}
		}

		return true;
	}

	if ( isset( $plugin_page ) && $plugin_page === $parent && isset( $_wp_menu_nopriv[ $plugin_page ] ) ) {
		return false;
	}

	if ( isset( $submenu[ $parent ] ) ) {
		foreach ( $submenu[ $parent ] as $submenu_array ) {
			if ( isset( $plugin_page ) && $submenu_array[2] === $plugin_page ) {
				return current_user_can( $submenu_array[1] );
			} elseif ( $submenu_array[2] === $pagenow ) {
				return current_user_can( $submenu_array[1] );
			}
		}
	}

	foreach ( $menu as $menu_array ) {
		if ( $menu_array[2] === $parent ) {
			return current_user_can( $menu_array[1] );
		}
	}

	return true;
}

/* Các hàm danh sách được phép */

/**
 * Làm mới giá trị danh sách tùy chọn được phép qua hook 'allowed_options'.
 *
 * Xem bộ lọc {@see 'allowed_options'}.
 *
 * @since 2.7.0
 * @since 5.5.0 `$new_whitelist_options` được đổi tên thành `$new_allowed_options`.
 *              Hãy cân nhắc viết code bao hàm hơn.
 *
 * @global array $new_allowed_options
 *
 * @param array $options
 * @return array
 */
function option_update_filter( $options ) {
	global $new_allowed_options;

	if ( is_array( $new_allowed_options ) ) {
		$options = add_allowed_options( $new_allowed_options, $options );
	}

	return $options;
}

/**
 * Thêm một mảng tùy chọn vào danh sách tùy chọn được phép.
 *
 * @since 5.5.0
 *
 * @global array $allowed_options
 *
 * @param array        $new_options
 * @param string|array $options
 * @return array
 */
function add_allowed_options( $new_options, $options = '' ) {
	if ( '' === $options ) {
		global $allowed_options;
	} else {
		$allowed_options = $options;
	}

	foreach ( $new_options as $page => $keys ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $allowed_options[ $page ] ) || ! is_array( $allowed_options[ $page ] ) ) {
				$allowed_options[ $page ]   = array();
				$allowed_options[ $page ][] = $key;
			} else {
				$pos = array_search( $key, $allowed_options[ $page ], true );
				if ( false === $pos ) {
					$allowed_options[ $page ][] = $key;
				}
			}
		}
	}

	return $allowed_options;
}

/**
 * Xóa một danh sách tùy chọn khỏi danh sách tùy chọn được phép.
 *
 * @since 5.5.0
 *
 * @global array $allowed_options
 *
 * @param array        $del_options
 * @param string|array $options
 * @return array
 */
function remove_allowed_options( $del_options, $options = '' ) {
	if ( '' === $options ) {
		global $allowed_options;
	} else {
		$allowed_options = $options;
	}

	foreach ( $del_options as $page => $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $allowed_options[ $page ] ) && is_array( $allowed_options[ $page ] ) ) {
				$pos = array_search( $key, $allowed_options[ $page ], true );
				if ( false !== $pos ) {
					unset( $allowed_options[ $page ][ $pos ] );
				}
			}
		}
	}

	return $allowed_options;
}

/**
 * Xuất các trường nonce, action và option_page cho trang cài đặt.
 *
 * @since 2.7.0
 *
 * @param string $option_group Tên nhóm cài đặt. Nên khớp với tên nhóm
 *                             được sử dụng trong register_setting().
 */
function settings_fields( $option_group ) {
	echo "<input type='hidden' name='option_page' value='" . esc_attr( $option_group ) . "' />";
	echo '<input type="hidden" name="action" value="update" />';
	wp_nonce_field( "$option_group-options" );
}

/**
 * Xóa bộ nhớ đệm plugin được sử dụng bởi get_plugins() và mặc định, bộ nhớ đệm cập nhật plugin.
 *
 * @since 3.7.0
 *
 * @param bool $clear_update_cache Có xóa bộ nhớ đệm cập nhật plugin không. Mặc định true.
 */
function wp_clean_plugins_cache( $clear_update_cache = true ) {
	if ( $clear_update_cache ) {
		delete_site_transient( 'update_plugins' );
	}
	wp_cache_delete( 'plugins', 'plugins' );
}

/**
 * Tải một plugin nhất định để thử tạo lỗi.
 *
 * @since 3.0.0
 * @since 4.4.0 Hàm được chuyển vào file `wp-admin/includes/plugin.php`.
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 */
function plugin_sandbox_scrape( $plugin ) {
	if ( ! defined( 'WP_SANDBOX_SCRAPING' ) ) {
		define( 'WP_SANDBOX_SCRAPING', true );
	}

	wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $plugin );
	include_once WP_PLUGIN_DIR . '/' . $plugin;
}

/**
 * Khai báo hàm trợ giúp để thêm nội dung vào Hướng dẫn Chính sách Quyền riêng tư.
 *
 * Plugin và theme nên đề xuất văn bản để đưa vào chính sách quyền riêng tư của site.
 * Văn bản đề xuất nên chứa thông tin về bất kỳ chức năng nào ảnh hưởng đến quyền riêng tư người dùng,
 * và sẽ được hiển thị trên màn hình Hướng dẫn Chính sách Quyền riêng tư.
 *
 * Plugin hoặc theme có thể sử dụng hàm này nhiều lần miễn là giúp trình bày tốt hơn
 * nội dung chính sách đề xuất. Ví dụ các plugin dạng module như WooCommerce hoặc Jetpack
 * có thể thêm hoặc xóa nội dung đề xuất tùy thuộc vào các module/extension được bật.
 * Để biết thêm thông tin xem Plugin Handbook:
 * https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/.
 *
 * Nội dung HTML của `$policy_text` hỗ trợ sử dụng lớp CSS chuyên biệt `.privacy-policy-tutorial`
 * có thể được dùng để cung cấp thông tin bổ sung. Bất kỳ nội dung nào nằm trong
 * các phần tử HTML có lớp CSS `.privacy-policy-tutorial` sẽ bị bỏ qua
 * khỏi clipboard khi nội dung phần được sao chép.
 *
 * Dành để sử dụng với action `'admin_init'`.
 *
 * @since 4.9.6
 *
 * @param string $plugin_name Tên plugin hoặc theme đang đề xuất nội dung
 *                            cho chính sách quyền riêng tư của site.
 * @param string $policy_text Nội dung đề xuất để đưa vào chính sách.
 */
function wp_add_privacy_policy_content( $plugin_name, $policy_text ) {
	if ( ! is_admin() ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: admin_init */
				__( 'The suggested privacy policy content should be added only in wp-admin by using the %s (or later) action.' ),
				'<code>admin_init</code>'
			),
			'4.9.7'
		);
		return;
	} elseif ( ! doing_action( 'admin_init' ) && ! did_action( 'admin_init' ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: admin_init */
				__( 'The suggested privacy policy content should be added by using the %s (or later) action. Please see the inline documentation.' ),
				'<code>admin_init</code>'
			),
			'4.9.7'
		);
		return;
	}

	if ( ! class_exists( 'WP_Privacy_Policy_Content' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';
	}

	WP_Privacy_Policy_Content::add( $plugin_name, $policy_text );
}

/**
 * Xác định xem plugin có hoạt động về mặt kỹ thuật nhưng bị tạm dừng trong quá trình
 * tải không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 5.2.0
 *
 * @global WP_Paused_Extensions_Storage $_paused_plugins
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return bool True nếu nằm trong danh sách plugin bị tạm dừng. False nếu không.
 */
function is_plugin_paused( $plugin ) {
	if ( ! isset( $GLOBALS['_paused_plugins'] ) ) {
		return false;
	}

	if ( ! is_plugin_active( $plugin ) ) {
		return false;
	}

	list( $plugin ) = explode( '/', $plugin );

	return array_key_exists( $plugin, $GLOBALS['_paused_plugins'] );
}

/**
 * Lấy lỗi đã được ghi lại cho plugin bị tạm dừng.
 *
 * @since 5.2.0
 *
 * @global WP_Paused_Extensions_Storage $_paused_plugins
 *
 * @param string $plugin Đường dẫn đến file plugin tương đối so với thư mục plugins.
 * @return array|false Mảng thông tin lỗi như được trả về bởi `error_get_last()`,
 *                     hoặc false nếu không có lỗi nào được ghi lại.
 */
function wp_get_plugin_error( $plugin ) {
	if ( ! isset( $GLOBALS['_paused_plugins'] ) ) {
		return false;
	}

	list( $plugin ) = explode( '/', $plugin );

	if ( ! array_key_exists( $plugin, $GLOBALS['_paused_plugins'] ) ) {
		return false;
	}

	return $GLOBALS['_paused_plugins'][ $plugin ];
}

/**
 * Thử tiếp tục chạy một plugin đơn lẻ.
 *
 * Nếu có URL chuyển hướng được cung cấp, trước tiên đảm bảo plugin không còn
 * gây ra lỗi nghiêm trọng.
 *
 * Cách hoạt động là thiết lập chuyển hướng đến lỗi trước khi thử
 * include file plugin. Nếu plugin thất bại, chuyển hướng sẽ không
 * bị ghi đè bằng thông báo thành công và plugin sẽ không được tiếp tục.
 *
 * @since 5.2.0
 *
 * @param string $plugin   Plugin đơn lẻ cần tiếp tục chạy.
 * @param string $redirect Tùy chọn. URL để chuyển hướng đến. Mặc định chuỗi rỗng.
 * @return true|WP_Error True khi thành công, false nếu `$plugin` không bị tạm dừng,
 *                       `WP_Error` khi thất bại.
 */
function resume_plugin( $plugin, $redirect = '' ) {
	/*
	 * Chúng ta sẽ ghi đè điều này sau nếu plugin có thể được tiếp tục
	 * mà không gây ra lỗi nghiêm trọng.
	 */
	if ( ! empty( $redirect ) ) {
		wp_redirect(
			add_query_arg(
				'_error_nonce',
				wp_create_nonce( 'plugin-resume-error_' . $plugin ),
				$redirect
			)
		);

		// Tải plugin để kiểm tra xem nó có gây ra lỗi nghiêm trọng không.
		ob_start();
		plugin_sandbox_scrape( $plugin );
		ob_clean();
	}

	list( $extension ) = explode( '/', $plugin );

	$result = wp_paused_plugins()->delete( $extension );

	if ( ! $result ) {
		return new WP_Error(
			'could_not_resume_plugin',
			__( 'Could not resume the plugin.' )
		);
	}

	return true;
}

/**
 * Hiển thị thông báo quản trị trong trường hợp một số plugin bị tạm dừng do lỗi.
 *
 * @since 5.2.0
 *
 * @global string                       $pagenow         Tên file của màn hình hiện tại.
 * @global WP_Paused_Extensions_Storage $_paused_plugins
 */
function paused_plugins_notice() {
	if ( 'plugins.php' === $GLOBALS['pagenow'] ) {
		return;
	}

	if ( ! current_user_can( 'resume_plugins' ) ) {
		return;
	}

	if ( ! isset( $GLOBALS['_paused_plugins'] ) || empty( $GLOBALS['_paused_plugins'] ) ) {
		return;
	}

	$message = sprintf(
		'<strong>%s</strong><br>%s</p><p><a href="%s">%s</a>',
		__( 'One or more plugins failed to load properly.' ),
		__( 'You can find more details and make changes on the Plugins screen.' ),
		esc_url( admin_url( 'plugins.php?plugin_status=paused' ) ),
		__( 'Go to the Plugins screen' )
	);
	wp_admin_notice(
		$message,
		array( 'type' => 'error' )
	);
}

/**
 * Hiển thị thông báo quản trị khi plugin bị vô hiệu hóa trong quá trình cập nhật.
 *
 * Hiển thị thông báo quản trị trong trường hợp plugin bị vô hiệu hóa trong quá trình
 * nâng cấp do không tương thích với phiên bản WordPress hiện tại.
 *
 * @since 5.8.0
 * @access private
 *
 * @global string $pagenow    Tên file của màn hình hiện tại.
 * @global string $wp_version Chuỗi phiên bản WordPress.
 */
function deactivated_plugins_notice() {
	if ( 'plugins.php' === $GLOBALS['pagenow'] ) {
		return;
	}

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$blog_deactivated_plugins = get_option( 'wp_force_deactivated_plugins' );
	$site_deactivated_plugins = array();

	if ( false === $blog_deactivated_plugins ) {
		// Tùy chọn không có trong cơ sở dữ liệu, thêm mảng rỗng để tránh các truy vấn DB thêm trong các lần tải tiếp theo.
		update_option( 'wp_force_deactivated_plugins', array(), false );
	}

	if ( is_multisite() ) {
		$site_deactivated_plugins = get_site_option( 'wp_force_deactivated_plugins' );
		if ( false === $site_deactivated_plugins ) {
			// Tùy chọn không có trong cơ sở dữ liệu, thêm mảng rỗng để tránh các truy vấn DB thêm trong các lần tải tiếp theo.
			update_site_option( 'wp_force_deactivated_plugins', array() );
		}
	}

	if ( empty( $blog_deactivated_plugins ) && empty( $site_deactivated_plugins ) ) {
		// Không có plugin bị vô hiệu hóa.
		return;
	}

	$deactivated_plugins = array_merge( $blog_deactivated_plugins, $site_deactivated_plugins );

	foreach ( $deactivated_plugins as $plugin ) {
		if ( ! empty( $plugin['version_compatible'] ) && ! empty( $plugin['version_deactivated'] ) ) {
			$explanation = sprintf(
				/* translators: 1: Name of deactivated plugin, 2: Plugin version deactivated, 3: Current WP version, 4: Compatible plugin version. */
				__( '%1$s %2$s was deactivated due to incompatibility with WordPress %3$s, please upgrade to %1$s %4$s or later.' ),
				$plugin['plugin_name'],
				$plugin['version_deactivated'],
				$GLOBALS['wp_version'],
				$plugin['version_compatible']
			);
		} else {
			$explanation = sprintf(
				/* translators: 1: Name of deactivated plugin, 2: Plugin version deactivated, 3: Current WP version. */
				__( '%1$s %2$s was deactivated due to incompatibility with WordPress %3$s.' ),
				$plugin['plugin_name'],
				! empty( $plugin['version_deactivated'] ) ? $plugin['version_deactivated'] : '',
				$GLOBALS['wp_version'],
				$plugin['version_compatible']
			);
		}

		$message = sprintf(
			'<strong>%s</strong><br>%s</p><p><a href="%s">%s</a>',
			sprintf(
				/* translators: %s: Name of deactivated plugin. */
				__( '%s plugin deactivated during WordPress upgrade.' ),
				$plugin['plugin_name']
			),
			$explanation,
			esc_url( admin_url( 'plugins.php?plugin_status=inactive' ) ),
			__( 'Go to the Plugins screen' )
		);
		wp_admin_notice( $message, array( 'type' => 'warning' ) );
	}

	// Xóa rỗng các tùy chọn.
	update_option( 'wp_force_deactivated_plugins', array(), false );
	if ( is_multisite() ) {
		update_site_option( 'wp_force_deactivated_plugins', array() );
	}
}
