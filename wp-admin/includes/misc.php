<?php
/**
 * API Quản trị WordPress đa dụng.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Trả về máy chủ có đang chạy Apache với mô-đun mod_rewrite được tải hay không.
 *
 * @since 2.0.0
 *
 * @return bool Máy chủ có đang chạy Apache với mô-đun mod_rewrite được tải hay không.
 */
function got_mod_rewrite() {
	$got_rewrite = apache_mod_loaded( 'mod_rewrite', true );

	/**
	 * Lọc kết quả kiểm tra Apache và mod_rewrite có tồn tại hay không.
	 *
	 * Bộ lọc này trước đây được dùng để ép viết lại URL cho các máy chủ khác,
	 * như nginx. Sử dụng bộ lọc {@see 'got_url_rewrite'} trong got_url_rewrite() thay thế.
	 *
	 * @since 2.5.0
	 *
	 * @see got_url_rewrite()
	 *
	 * @param bool $got_rewrite Apache và mod_rewrite có tồn tại hay không.
	 */
	return apply_filters( 'got_rewrite', $got_rewrite );
}

/**
 * Trả về máy chủ có hỗ trợ viết lại URL hay không.
 *
 * Phát hiện mod_rewrite của Apache, hỗ trợ đường dẫn tĩnh IIS 7.0+, và nginx.
 *
 * @since 3.7.0
 *
 * @global bool $is_nginx
 * @global bool $is_caddy
 *
 * @return bool Máy chủ có hỗ trợ viết lại URL hay không.
 */
function got_url_rewrite() {
	$got_url_rewrite = ( got_mod_rewrite() || $GLOBALS['is_nginx'] || $GLOBALS['is_caddy'] || iis7_supports_permalinks() );

	/**
	 * Lọc kết quả kiểm tra viết lại URL có khả dụng hay không.
	 *
	 * @since 3.7.0
	 *
	 * @param bool $got_url_rewrite Viết lại URL có khả dụng hay không.
	 */
	return apply_filters( 'got_url_rewrite', $got_url_rewrite );
}

/**
 * Trích xuất chuỗi nằm giữa các đánh dấu BEGIN và END trong tệp .htaccess.
 *
 * @since 1.5.0
 *
 * @param string $filename Tên tệp để trích xuất chuỗi.
 * @param string $marker   Đánh dấu để trích xuất chuỗi.
 * @return string[] Mảng các chuỗi từ tệp (.htaccess) nằm giữa các đánh dấu BEGIN và END.
 */
function extract_from_markers( $filename, $marker ) {
	$result = array();

	if ( ! file_exists( $filename ) ) {
		return $result;
	}

	$markerdata = explode( "\n", implode( '', file( $filename ) ) );

	$state = false;

	foreach ( $markerdata as $markerline ) {
		if ( str_contains( $markerline, '# END ' . $marker ) ) {
			$state = false;
		}

		if ( $state ) {
			if ( str_starts_with( $markerline, '#' ) ) {
				continue;
			}

			$result[] = $markerline;
		}

		if ( str_contains( $markerline, '# BEGIN ' . $marker ) ) {
			$state = true;
		}
	}

	return $result;
}

/**
 * Chèn một mảng chuỗi vào tệp (.htaccess), đặt giữa
 * các đánh dấu BEGIN và END.
 *
 * Thay thế thông tin đánh dấu hiện có. Giữ lại dữ liệu
 * xung quanh. Tạo tệp nếu chưa tồn tại.
 *
 * @since 1.5.0
 *
 * @param string       $filename  Tên tệp cần thay đổi.
 * @param string       $marker    Đánh dấu cần thay đổi.
 * @param array|string $insertion Nội dung mới để chèn.
 * @return bool True nếu ghi thành công, false nếu thất bại.
 */
function insert_with_markers( $filename, $marker, $insertion ) {
	if ( ! file_exists( $filename ) ) {
		if ( ! is_writable( dirname( $filename ) ) ) {
			return false;
		}

		if ( ! touch( $filename ) ) {
			return false;
		}

		// Đảm bảo tệp được tạo với bộ quyền tối thiểu.
		$perms = fileperms( $filename );

		if ( $perms ) {
			chmod( $filename, $perms | 0644 );
		}
	} elseif ( ! is_writable( $filename ) ) {
		return false;
	}

	if ( ! is_array( $insertion ) ) {
		$insertion = explode( "\n", $insertion );
	}

	$switched_locale = switch_to_locale( get_locale() );

	$instructions = sprintf(
		/* translators: 1: Marker. */
		__(
			'The directives (lines) between "BEGIN %1$s" and "END %1$s" are
dynamically generated, and should only be modified via WordPress filters.
Any changes to the directives between these markers will be overwritten.'
		),
		$marker
	);

	$instructions = explode( "\n", $instructions );

	foreach ( $instructions as $line => $text ) {
		$instructions[ $line ] = '# ' . $text;
	}

	/**
	 * Lọc các hướng dẫn nội tuyến được chèn trước nội dung được tạo động.
	 *
	 * @since 5.3.0
	 *
	 * @param string[] $instructions Mảng các dòng chứa hướng dẫn nội tuyến.
	 * @param string   $marker       Đánh dấu đang được chèn.
	 */
	$instructions = apply_filters( 'insert_with_markers_inline_instructions', $instructions, $marker );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	$insertion = array_merge( $instructions, $insertion );

	$start_marker = "# BEGIN {$marker}";
	$end_marker   = "# END {$marker}";

	$fp = fopen( $filename, 'r+' );

	if ( ! $fp ) {
		return false;
	}

	// Cố gắng khóa tệp. Nếu hệ thống tệp hỗ trợ khóa, nó sẽ chặn cho đến khi khóa được lấy.
	flock( $fp, LOCK_EX );

	$lines = array();

	while ( ! feof( $fp ) ) {
		$lines[] = rtrim( fgets( $fp ), "\r\n" );
	}

	// Tách tệp hiện có thành các dòng trước đánh dấu, và các dòng xuất hiện sau đánh dấu.
	$pre_lines        = array();
	$post_lines       = array();
	$existing_lines   = array();
	$found_marker     = false;
	$found_end_marker = false;

	foreach ( $lines as $line ) {
		if ( ! $found_marker && str_contains( $line, $start_marker ) ) {
			$found_marker = true;
			continue;
		} elseif ( ! $found_end_marker && str_contains( $line, $end_marker ) ) {
			$found_end_marker = true;
			continue;
		}

		if ( ! $found_marker ) {
			$pre_lines[] = $line;
		} elseif ( $found_marker && $found_end_marker ) {
			$post_lines[] = $line;
		} else {
			$existing_lines[] = $line;
		}
	}

	// Kiểm tra xem có thay đổi hay không.
	if ( $existing_lines === $insertion ) {
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return true;
	}

	// Tạo dữ liệu tệp mới.
	$new_file_data = implode(
		"\n",
		array_merge(
			$pre_lines,
			array( $start_marker ),
			$insertion,
			array( $end_marker ),
			$post_lines
		)
	);

	// Ghi từ đầu tệp và cắt bớt đến độ dài đó.
	fseek( $fp, 0 );
	$bytes = fwrite( $fp, $new_file_data );

	if ( $bytes ) {
		ftruncate( $fp, ftell( $fp ) );
	}

	fflush( $fp );
	flock( $fp, LOCK_UN );
	fclose( $fp );

	return (bool) $bytes;
}

/**
 * Cập nhật tệp htaccess với các quy tắc hiện tại nếu có quyền ghi.
 *
 * Luôn ghi vào tệp nếu nó tồn tại và có quyền ghi để đảm bảo
 * xóa sạch các quy tắc cũ.
 *
 * @since 1.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần viết lại URL của WordPress.
 *
 * @return bool|null True nếu ghi thành công, false nếu thất bại. Null trong multisite.
 */
function save_mod_rewrite_rules() {
	global $wp_rewrite;

	if ( is_multisite() ) {
		return;
	}

	// Đảm bảo get_home_path() đã được khai báo.
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$home_path     = get_home_path();
	$htaccess_file = $home_path . '.htaccess';

	/*
	 * Nếu tệp chưa tồn tại, kiểm tra quyền ghi vào thư mục
	 * và xem có quy tắc nào không. Nếu không, kiểm tra quyền ghi vào tệp.
	 */
	if ( ! file_exists( $htaccess_file ) && is_writable( $home_path ) && $wp_rewrite->using_mod_rewrite_permalinks()
		|| is_writable( $htaccess_file )
	) {
		if ( got_mod_rewrite() ) {
			$rules = explode( "\n", $wp_rewrite->mod_rewrite_rules() );

			return insert_with_markers( $htaccess_file, 'WordPress', $rules );
		}
	}

	return false;
}

/**
 * Cập nhật tệp web.config của IIS với các quy tắc hiện tại nếu có quyền ghi.
 * Nếu đường dẫn tĩnh không yêu cầu quy tắc viết lại thì xóa các quy tắc khỏi tệp web.config.
 *
 * @since 2.8.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần viết lại URL của WordPress.
 *
 * @return bool|null True nếu ghi thành công, false nếu thất bại. Null trong multisite.
 */
function iis7_save_url_rewrite_rules() {
	global $wp_rewrite;

	if ( is_multisite() ) {
		return;
	}

	// Đảm bảo get_home_path() đã được khai báo.
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$home_path       = get_home_path();
	$web_config_file = $home_path . 'web.config';

	// Sử dụng win_is_writable() thay vì is_writable() vì lỗi trong PHP trên Windows.
	if ( iis7_supports_permalinks()
		&& ( ! file_exists( $web_config_file ) && win_is_writable( $home_path ) && $wp_rewrite->using_mod_rewrite_permalinks()
			|| win_is_writable( $web_config_file ) )
	) {
		$rule = $wp_rewrite->iis7_url_rewrite_rules( false );

		if ( ! empty( $rule ) ) {
			return iis7_add_rewrite_rule( $web_config_file, $rule );
		} else {
			return iis7_delete_rewrite_rule( $web_config_file );
		}
	}

	return false;
}

/**
 * Cập nhật tệp "đã chỉnh sửa gần đây" cho trình soạn thảo tệp plugin hoặc giao diện.
 *
 * @since 1.5.0
 *
 * @param string $file
 */
function update_recently_edited( $file ) {
	$oldfiles = (array) get_option( 'recently_edited' );

	if ( $oldfiles ) {
		$oldfiles   = array_reverse( $oldfiles );
		$oldfiles[] = $file;
		$oldfiles   = array_reverse( $oldfiles );
		$oldfiles   = array_unique( $oldfiles );

		if ( 5 < count( $oldfiles ) ) {
			array_pop( $oldfiles );
		}
	} else {
		$oldfiles[] = $file;
	}

	update_option( 'recently_edited', $oldfiles );
}

/**
 * Tạo cấu trúc cây cho danh sách tệp của trình soạn thảo tệp giao diện.
 *
 * @since 4.9.0
 * @access private
 *
 * @param array $allowed_files Danh sách đường dẫn tệp giao diện.
 * @return array Cấu trúc cây để liệt kê các tệp giao diện.
 */
function wp_make_theme_file_tree( $allowed_files ) {
	$tree_list = array();

	foreach ( $allowed_files as $file_name => $absolute_filename ) {
		$list     = explode( '/', $file_name );
		$last_dir = &$tree_list;

		foreach ( $list as $dir ) {
			$last_dir =& $last_dir[ $dir ];
		}

		$last_dir = $file_name;
	}

	return $tree_list;
}

/**
 * Xuất danh sách tệp đã định dạng cho trình soạn thảo tệp giao diện.
 *
 * @since 4.9.0
 * @access private
 *
 * @global string $relative_file Tên tệp đang được chỉnh sửa tương đối so với
 *                               thư mục giao diện.
 * @global string $stylesheet    Tên stylesheet của giao diện đang được chỉnh sửa.
 *
 * @param array|string $tree  Danh sách đường dẫn tệp/thư mục, hoặc tên tệp.
 * @param int          $level Mức aria-level cho lần lặp hiện tại.
 * @param int          $size  Giá trị aria-setsize cho lần lặp hiện tại.
 * @param int          $index Giá trị aria-posinset cho lần lặp hiện tại.
 */
function wp_print_theme_file_tree( $tree, $level = 2, $size = 1, $index = 1 ) {
	global $relative_file, $stylesheet;

	if ( is_array( $tree ) ) {
		$index = 0;
		$size  = count( $tree );

		foreach ( $tree as $label => $theme_file ) :
			++$index;

			if ( ! is_array( $theme_file ) ) {
				wp_print_theme_file_tree( $theme_file, $level, $index, $size );
				continue;
			}
			?>
			<li role="treeitem" aria-expanded="true" tabindex="-1"
				aria-level="<?php echo esc_attr( $level ); ?>"
				aria-setsize="<?php echo esc_attr( $size ); ?>"
				aria-posinset="<?php echo esc_attr( $index ); ?>">
				<span class="folder-label"><?php echo esc_html( $label ); ?> <span class="screen-reader-text">
					<?php
					/* translators: Hidden accessibility text. */
					_e( 'folder' );
					?>
				</span><span aria-hidden="true" class="icon"></span></span>
				<ul role="group" class="tree-folder"><?php wp_print_theme_file_tree( $theme_file, $level + 1, $index, $size ); ?></ul>
			</li>
			<?php
		endforeach;
	} else {
		$filename = $tree;
		$url      = add_query_arg(
			array(
				'file'  => rawurlencode( $tree ),
				'theme' => rawurlencode( $stylesheet ),
			),
			self_admin_url( 'theme-editor.php' )
		);
		?>
		<li role="none" class="<?php echo esc_attr( $relative_file === $filename ? 'current-file' : '' ); ?>">
			<a role="treeitem" tabindex="<?php echo esc_attr( $relative_file === $filename ? '0' : '-1' ); ?>"
				href="<?php echo esc_url( $url ); ?>"
				aria-level="<?php echo esc_attr( $level ); ?>"
				aria-setsize="<?php echo esc_attr( $size ); ?>"
				aria-posinset="<?php echo esc_attr( $index ); ?>">
				<?php
				$file_description = esc_html( get_file_description( $filename ) );

				if ( $file_description !== $filename && wp_basename( $filename ) !== $file_description ) {
					$file_description .= '<br /><span class="nonessential">(' . esc_html( $filename ) . ')</span>';
				}

				if ( $relative_file === $filename ) {
					echo '<span class="notice notice-info">' . $file_description . '</span>';
				} else {
					echo $file_description;
				}
				?>
			</a>
		</li>
		<?php
	}
}

/**
 * Tạo cấu trúc cây cho danh sách tệp của trình soạn thảo tệp plugin.
 *
 * @since 4.9.0
 * @access private
 *
 * @param array $plugin_editable_files Danh sách đường dẫn tệp plugin.
 * @return array Cấu trúc cây để liệt kê các tệp plugin.
 */
function wp_make_plugin_file_tree( $plugin_editable_files ) {
	$tree_list = array();

	foreach ( $plugin_editable_files as $plugin_file ) {
		$list     = explode( '/', preg_replace( '#^.+?/#', '', $plugin_file ) );
		$last_dir = &$tree_list;

		foreach ( $list as $dir ) {
			$last_dir =& $last_dir[ $dir ];
		}

		$last_dir = $plugin_file;
	}

	return $tree_list;
}

/**
 * Xuất danh sách tệp đã định dạng cho trình soạn thảo tệp plugin.
 *
 * @since 4.9.0
 * @access private
 *
 * @param array|string $tree  Danh sách đường dẫn tệp/thư mục, hoặc tên tệp.
 * @param string       $label Tên tệp hoặc thư mục cần hiển thị.
 * @param int          $level Mức aria-level cho lần lặp hiện tại.
 * @param int          $size  Giá trị aria-setsize cho lần lặp hiện tại.
 * @param int          $index Giá trị aria-posinset cho lần lặp hiện tại.
 */
function wp_print_plugin_file_tree( $tree, $label = '', $level = 2, $size = 1, $index = 1 ) {
	global $file, $plugin;

	if ( is_array( $tree ) ) {
		$index = 0;
		$size  = count( $tree );

		foreach ( $tree as $label => $plugin_file ) :
			++$index;

			if ( ! is_array( $plugin_file ) ) {
				wp_print_plugin_file_tree( $plugin_file, $label, $level, $index, $size );
				continue;
			}
			?>
			<li role="treeitem" aria-expanded="true" tabindex="-1"
				aria-level="<?php echo esc_attr( $level ); ?>"
				aria-setsize="<?php echo esc_attr( $size ); ?>"
				aria-posinset="<?php echo esc_attr( $index ); ?>">
				<span class="folder-label"><?php echo esc_html( $label ); ?> <span class="screen-reader-text">
					<?php
					/* translators: Hidden accessibility text. */
					_e( 'folder' );
					?>
				</span><span aria-hidden="true" class="icon"></span></span>
				<ul role="group" class="tree-folder"><?php wp_print_plugin_file_tree( $plugin_file, '', $level + 1, $index, $size ); ?></ul>
			</li>
			<?php
		endforeach;
	} else {
		$url = add_query_arg(
			array(
				'file'   => rawurlencode( $tree ),
				'plugin' => rawurlencode( $plugin ),
			),
			self_admin_url( 'plugin-editor.php' )
		);
		?>
		<li role="none" class="<?php echo esc_attr( $file === $tree ? 'current-file' : '' ); ?>">
			<a role="treeitem" tabindex="<?php echo esc_attr( $file === $tree ? '0' : '-1' ); ?>"
				href="<?php echo esc_url( $url ); ?>"
				aria-level="<?php echo esc_attr( $level ); ?>"
				aria-setsize="<?php echo esc_attr( $size ); ?>"
				aria-posinset="<?php echo esc_attr( $index ); ?>">
				<?php
				if ( $file === $tree ) {
					echo '<span class="notice notice-info">' . esc_html( $label ) . '</span>';
				} else {
					echo esc_html( $label );
				}
				?>
			</a>
		</li>
		<?php
	}
}

/**
 * Xóa bộ nhớ đệm quy tắc viết lại nếu `siteurl`, `home` hoặc `page_on_front` thay đổi.
 *
 * @since 2.1.0
 *
 * @param string $old_value
 * @param string $value
 */
function update_home_siteurl( $old_value, $value ) {
	if ( wp_installing() ) {
		return;
	}

	if ( is_multisite() && ms_is_switched() ) {
		delete_option( 'rewrite_rules' );
	} else {
		flush_rewrite_rules();
	}
}

/**
 * Đặt lại các biến toàn cục dựa trên `$_GET` và `$_POST`.
 *
 * Hàm này đặt lại các biến toàn cục dựa trên tên được truyền
 * trong mảng `$vars` thành giá trị của `$_POST[$var]` hoặc `$_GET[$var]` hoặc
 * chuỗi rỗng nếu không có giá trị nào được định nghĩa.
 *
 * @since 2.0.0
 *
 * @param array $vars Mảng các biến toàn cục cần đặt lại.
 */
function wp_reset_vars( $vars ) {
	foreach ( $vars as $var ) {
		if ( empty( $_POST[ $var ] ) ) {
			if ( empty( $_GET[ $var ] ) ) {
				$GLOBALS[ $var ] = '';
			} else {
				$GLOBALS[ $var ] = $_GET[ $var ];
			}
		} else {
			$GLOBALS[ $var ] = $_POST[ $var ];
		}
	}
}

/**
 * Hiển thị thông báo quản trị được cung cấp.
 *
 * @since 2.1.0
 *
 * @param string|WP_Error $message
 */
function show_message( $message ) {
	if ( is_wp_error( $message ) ) {
		if ( $message->get_error_data() && is_string( $message->get_error_data() ) ) {
			$message = $message->get_error_message() . ': ' . $message->get_error_data();
		} else {
			$message = $message->get_error_message();
		}
	}

	echo "<p>$message</p>\n";
	wp_ob_end_flush_all();
	flush();
}

/**
 * @since 2.8.0
 *
 * @param string $content
 * @return string[] Mảng tên các hàm.
 */
function wp_doc_link_parse( $content ) {
	if ( ! is_string( $content ) || empty( $content ) ) {
		return array();
	}

	if ( ! function_exists( 'token_get_all' ) ) {
		return array();
	}

	$tokens           = token_get_all( $content );
	$count            = count( $tokens );
	$functions        = array();
	$ignore_functions = array();

	for ( $t = 0; $t < $count - 2; $t++ ) {
		if ( ! is_array( $tokens[ $t ] ) ) {
			continue;
		}

		if ( T_STRING === $tokens[ $t ][0] && ( '(' === $tokens[ $t + 1 ] || '(' === $tokens[ $t + 2 ] ) ) {
			// Nếu đây là hàm hoặc lớp được định nghĩa cục bộ, sẽ không có tài liệu nào khả dụng.
			if ( ( isset( $tokens[ $t - 2 ][1] ) && in_array( $tokens[ $t - 2 ][1], array( 'function', 'class' ), true ) )
				|| ( isset( $tokens[ $t - 2 ][0] ) && T_OBJECT_OPERATOR === $tokens[ $t - 1 ][0] )
			) {
				$ignore_functions[] = $tokens[ $t ][1];
			}

			// Thêm vào ngăn xếp các tham chiếu duy nhất.
			$functions[] = $tokens[ $t ][1];
		}
	}

	$functions = array_unique( $functions );
	sort( $functions );

	/**
	 * Lọc danh sách các hàm và lớp cần bỏ qua khi tra cứu tài liệu.
	 *
	 * @since 2.8.0
	 *
	 * @param string[] $ignore_functions Mảng tên các hàm và lớp cần bỏ qua.
	 */
	$ignore_functions = apply_filters( 'documentation_ignore_functions', $ignore_functions );

	$ignore_functions = array_unique( $ignore_functions );

	$output = array();

	foreach ( $functions as $function ) {
		if ( in_array( $function, $ignore_functions, true ) ) {
			continue;
		}

		$output[] = $function;
	}

	return $output;
}

/**
 * Lưu tùy chọn số dòng khi liệt kê bài viết, trang, bình luận, v.v.
 *
 * @since 2.8.0
 */
function set_screen_options() {
	if ( ! isset( $_POST['wp_screen_options'] ) || ! is_array( $_POST['wp_screen_options'] ) ) {
		return;
	}

	check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

	$user = wp_get_current_user();

	if ( ! $user ) {
		return;
	}

	$option = $_POST['wp_screen_options']['option'];
	$value  = $_POST['wp_screen_options']['value'];

	if ( sanitize_key( $option ) !== $option ) {
		return;
	}

	$map_option = $option;
	$type       = str_replace( 'edit_', '', $map_option );
	$type       = str_replace( '_per_page', '', $type );

	if ( in_array( $type, get_taxonomies(), true ) ) {
		$map_option = 'edit_tags_per_page';
	} elseif ( in_array( $type, get_post_types(), true ) ) {
		$map_option = 'edit_per_page';
	} else {
		$option = str_replace( '-', '_', $option );
	}

	switch ( $map_option ) {
		case 'edit_per_page':
		case 'users_per_page':
		case 'edit_comments_per_page':
		case 'upload_per_page':
		case 'edit_tags_per_page':
		case 'plugins_per_page':
		case 'export_personal_data_requests_per_page':
		case 'remove_personal_data_requests_per_page':
			// Quản trị mạng.
		// Quản trị mạng.
		case 'sites_network_per_page':
		case 'users_network_per_page':
		case 'site_users_network_per_page':
		case 'plugins_network_per_page':
		case 'themes_network_per_page':
		case 'site_themes_network_per_page':
			$value = (int) $value;

			if ( $value < 1 || $value > 999 ) {
				return;
			}

			break;

		default:
			$screen_option = false;

			if ( str_ends_with( $option, '_page' ) || 'layout_columns' === $option ) {
				/**
				 * Lọc giá trị tùy chọn màn hình trước khi được thiết lập.
				 *
				 * Bộ lọc cũng có thể được dùng để sửa đổi các cài đặt `[items]_per_page`
				 * không chuẩn. Xem hàm cha để biết danh sách đầy đủ các tùy chọn chuẩn.
				 *
				 * Trả về false từ bộ lọc sẽ bỏ qua việc lưu tùy chọn hiện tại.
				 *
				 * @since 2.8.0
				 * @since 5.4.2 Chỉ áp dụng cho các tùy chọn kết thúc bằng '_page',
				 *              hoặc tùy chọn 'layout_columns'.
				 *
				 * @see set_screen_options()
				 *
				 * @param mixed  $screen_option Giá trị để lưu thay vì giá trị tùy chọn.
				 *                              Mặc định false (để bỏ qua việc lưu tùy chọn hiện tại).
				 * @param string $option        Tên tùy chọn.
				 * @param int    $value         Giá trị tùy chọn.
				 */
				$screen_option = apply_filters( 'set-screen-option', $screen_option, $option, $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			}

			/**
			 * Lọc giá trị tùy chọn màn hình trước khi được thiết lập.
			 *
			 * Phần động của tên hook, `$option`, tham chiếu đến tên tùy chọn.
			 *
			 * Trả về false từ bộ lọc sẽ bỏ qua việc lưu tùy chọn hiện tại.
			 *
			 * @since 5.4.2
			 *
			 * @see set_screen_options()
			 *
			 * @param mixed   $screen_option Giá trị để lưu thay vì giá trị tùy chọn.
			 *                               Mặc định false (để bỏ qua việc lưu tùy chọn hiện tại).
			 * @param string  $option        Tên tùy chọn.
			 * @param int     $value         Giá trị tùy chọn.
			 */
			$value = apply_filters( "set_screen_option_{$option}", $screen_option, $option, $value );

			if ( false === $value ) {
				return;
			}

			break;
	}

	update_user_meta( $user->ID, $option, $value );

	$url = remove_query_arg( array( 'pagenum', 'apage', 'paged' ), wp_get_referer() );

	if ( isset( $_POST['mode'] ) ) {
		$url = add_query_arg( array( 'mode' => $_POST['mode'] ), $url );
	}

	wp_safe_redirect( $url );
	exit;
}

/**
 * Kiểm tra xem quy tắc viết lại cho WordPress đã tồn tại trong tệp cấu hình IIS 7+ chưa.
 *
 * @since 2.8.0
 *
 * @param string $filename Đường dẫn tệp đến tệp cấu hình.
 * @return bool
 */
function iis7_rewrite_rule_exists( $filename ) {
	if ( ! file_exists( $filename ) ) {
		return false;
	}

	if ( ! class_exists( 'DOMDocument', false ) ) {
		return false;
	}

	$doc = new DOMDocument();

	if ( $doc->load( $filename ) === false ) {
		return false;
	}

	$xpath = new DOMXPath( $doc );
	$rules = $xpath->query( '/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'wordpress\')] | /configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'WordPress\')]' );

	if ( 0 === $rules->length ) {
		return false;
	}

	return true;
}

/**
 * Xóa quy tắc viết lại WordPress khỏi tệp web.config nếu nó tồn tại.
 *
 * @since 2.8.0
 *
 * @param string $filename Tên của tệp cấu hình.
 * @return bool
 */
function iis7_delete_rewrite_rule( $filename ) {
	// Nếu tệp cấu hình không tồn tại thì quy tắc cũng không tồn tại, nên không có gì để xóa.
	if ( ! file_exists( $filename ) ) {
		return true;
	}

	if ( ! class_exists( 'DOMDocument', false ) ) {
		return false;
	}

	$doc                     = new DOMDocument();
	$doc->preserveWhiteSpace = false;

	if ( $doc->load( $filename ) === false ) {
		return false;
	}

	$xpath = new DOMXPath( $doc );
	$rules = $xpath->query( '/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'wordpress\')] | /configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'WordPress\')]' );

	if ( $rules->length > 0 ) {
		$child  = $rules->item( 0 );
		$parent = $child->parentNode;
		$parent->removeChild( $child );
		$doc->formatOutput = true;
		saveDomDocument( $doc, $filename );
	}

	return true;
}

/**
 * Thêm quy tắc viết lại WordPress vào tệp cấu hình IIS 7+.
 *
 * @since 2.8.0
 *
 * @param string $filename     Đường dẫn tệp đến tệp cấu hình.
 * @param string $rewrite_rule Đoạn XML chứa quy tắc viết lại URL.
 * @return bool
 */
function iis7_add_rewrite_rule( $filename, $rewrite_rule ) {
	if ( ! class_exists( 'DOMDocument', false ) ) {
		return false;
	}

	// Nếu tệp cấu hình không tồn tại thì tạo mới.
	if ( ! file_exists( $filename ) ) {
		$fp = fopen( $filename, 'w' );
		fwrite( $fp, '<configuration/>' );
		fclose( $fp );
	}

	$doc                     = new DOMDocument();
	$doc->preserveWhiteSpace = false;

	if ( $doc->load( $filename ) === false ) {
		return false;
	}

	$xpath = new DOMXPath( $doc );

	// Kiểm tra trước xem quy tắc đã tồn tại chưa vì trong trường hợp đó không cần thêm lại.
	$wordpress_rules = $xpath->query( '/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'wordpress\')] | /configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'WordPress\')]' );

	if ( $wordpress_rules->length > 0 ) {
		return true;
	}

	// Kiểm tra XPath đến quy tắc viết lại và tạo các nút XML nếu chúng chưa tồn tại.
	$xml_nodes = $xpath->query( '/configuration/system.webServer/rewrite/rules' );

	if ( $xml_nodes->length > 0 ) {
		$rules_node = $xml_nodes->item( 0 );
	} else {
		$rules_node = $doc->createElement( 'rules' );

		$xml_nodes = $xpath->query( '/configuration/system.webServer/rewrite' );

		if ( $xml_nodes->length > 0 ) {
			$rewrite_node = $xml_nodes->item( 0 );
			$rewrite_node->appendChild( $rules_node );
		} else {
			$rewrite_node = $doc->createElement( 'rewrite' );
			$rewrite_node->appendChild( $rules_node );

			$xml_nodes = $xpath->query( '/configuration/system.webServer' );

			if ( $xml_nodes->length > 0 ) {
				$system_web_server_node = $xml_nodes->item( 0 );
				$system_web_server_node->appendChild( $rewrite_node );
			} else {
				$system_web_server_node = $doc->createElement( 'system.webServer' );
				$system_web_server_node->appendChild( $rewrite_node );

				$xml_nodes = $xpath->query( '/configuration' );

				if ( $xml_nodes->length > 0 ) {
					$config_node = $xml_nodes->item( 0 );
					$config_node->appendChild( $system_web_server_node );
				} else {
					$config_node = $doc->createElement( 'configuration' );
					$doc->appendChild( $config_node );
					$config_node->appendChild( $system_web_server_node );
				}
			}
		}
	}

	$rule_fragment = $doc->createDocumentFragment();
	$rule_fragment->appendXML( $rewrite_rule );
	$rules_node->appendChild( $rule_fragment );

	$doc->encoding     = 'UTF-8';
	$doc->formatOutput = true;
	saveDomDocument( $doc, $filename );

	return true;
}

/**
 * Saves the XML document into a file.
 *
 * @since 2.8.0
 *
 * @param DOMDocument $doc
 * @param string      $filename
 */
function saveDomDocument( $doc, $filename ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$config = $doc->saveXML();
	$config = preg_replace( "/([^\r])\n/", "$1\r\n", $config );

	$fp = fopen( $filename, 'w' );
	fwrite( $fp, $config );
	fclose( $fp );
}

/**
 * Displays the default admin color scheme picker (Used in user-edit.php).
 *
 * @since 3.0.0
 *
 * @global array $_wp_admin_css_colors
 *
 * @param int $user_id User ID.
 */
function admin_color_scheme_picker( $user_id ) {
	global $_wp_admin_css_colors;

	ksort( $_wp_admin_css_colors );

	if ( isset( $_wp_admin_css_colors['fresh'] ) ) {
		// Set Default ('fresh') and Light should go first.
		$_wp_admin_css_colors = array_filter(
			array_merge(
				array(
					'fresh'  => '',
					'light'  => '',
					'modern' => '',
				),
				$_wp_admin_css_colors
			)
		);
	}

	$current_color = get_user_option( 'admin_color', $user_id );

	if ( empty( $current_color ) || ! isset( $_wp_admin_css_colors[ $current_color ] ) ) {
		$current_color = 'fresh';
	}
	?>
	<fieldset id="color-picker" class="scheme-list">
		<legend class="screen-reader-text"><span>
			<?php
			/* translators: Hidden accessibility text. */
			_e( 'Admin Color Scheme' );
			?>
		</span></legend>
		<?php
		wp_nonce_field( 'save-color-scheme', 'color-nonce', false );
		foreach ( $_wp_admin_css_colors as $color => $color_info ) :

			?>
			<div class="color-option <?php echo ( $color === $current_color ) ? 'selected' : ''; ?>">
				<input name="admin_color" id="admin_color_<?php echo esc_attr( $color ); ?>" type="radio" value="<?php echo esc_attr( $color ); ?>" class="tog" <?php checked( $color, $current_color ); ?> />
				<input type="hidden" class="css_url" value="<?php echo esc_url( $color_info->url ); ?>" />
				<input type="hidden" class="icon_colors" value="<?php echo esc_attr( wp_json_encode( array( 'icons' => $color_info->icon_colors ) ) ); ?>" />
				<label for="admin_color_<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $color_info->name ); ?></label>
				<div class="color-palette">
				<?php
				foreach ( $color_info->colors as $html_color ) {
					?>
					<div class="color-palette-shade" style="background-color: <?php echo esc_attr( $html_color ); ?>">&nbsp;</div>
					<?php
				}
				?>
				</div>
			</div>
			<?php

		endforeach;
		?>
	</fieldset>
	<?php
}

/**
 *
 * @global array $_wp_admin_css_colors
 */
function wp_color_scheme_settings() {
	global $_wp_admin_css_colors;

	$color_scheme = get_user_option( 'admin_color' );

	// It's possible to have a color scheme set that is no longer registered.
	if ( empty( $_wp_admin_css_colors[ $color_scheme ] ) ) {
		$color_scheme = 'fresh';
	}

	if ( ! empty( $_wp_admin_css_colors[ $color_scheme ]->icon_colors ) ) {
		$icon_colors = $_wp_admin_css_colors[ $color_scheme ]->icon_colors;
	} elseif ( ! empty( $_wp_admin_css_colors['fresh']->icon_colors ) ) {
		$icon_colors = $_wp_admin_css_colors['fresh']->icon_colors;
	} else {
		// Fall back to the default set of icon colors if the default scheme is missing.
		$icon_colors = array(
			'base'    => '#a7aaad',
			'focus'   => '#72aee6',
			'current' => '#fff',
		);
	}

	echo '<script type="text/javascript">var _wpColorScheme = ' . wp_json_encode( array( 'icons' => $icon_colors ) ) . ";</script>\n";
}

/**
 * Displays the viewport meta in the admin.
 *
 * @since 5.5.0
 */
function wp_admin_viewport_meta() {
	/**
	 * Filters the viewport meta in the admin.
	 *
	 * @since 5.5.0
	 *
	 * @param string $viewport_meta The viewport meta.
	 */
	$viewport_meta = apply_filters( 'admin_viewport_meta', 'width=device-width,initial-scale=1.0' );

	if ( empty( $viewport_meta ) ) {
		return;
	}

	echo '<meta name="viewport" content="' . esc_attr( $viewport_meta ) . '">';
}

/**
 * Adds viewport meta for mobile in Customizer.
 *
 * Hooked to the {@see 'admin_viewport_meta'} filter.
 *
 * @since 5.5.0
 *
 * @param string $viewport_meta The viewport meta.
 * @return string Filtered viewport meta.
 */
function _customizer_mobile_viewport_meta( $viewport_meta ) {
	return trim( $viewport_meta, ',' ) . ',minimum-scale=0.5,maximum-scale=1.2';
}

/**
 * Checks lock status for posts displayed on the Posts screen.
 *
 * @since 3.6.0
 *
 * @param array  $response  The Heartbeat response.
 * @param array  $data      The $_POST data sent.
 * @param string $screen_id The screen ID.
 * @return array The Heartbeat response.
 */
function wp_check_locked_posts( $response, $data, $screen_id ) {
	$checked = array();

	if ( array_key_exists( 'wp-check-locked-posts', $data ) && is_array( $data['wp-check-locked-posts'] ) ) {
		foreach ( $data['wp-check-locked-posts'] as $key ) {
			$post_id = absint( substr( $key, 5 ) );

			if ( ! $post_id ) {
				continue;
			}

			$user_id = wp_check_post_lock( $post_id );

			if ( $user_id ) {
				$user = get_userdata( $user_id );

				if ( $user && current_user_can( 'edit_post', $post_id ) ) {
					$send = array(
						'name' => $user->display_name,
						/* translators: %s: User's display name. */
						'text' => sprintf( __( '%s is currently editing' ), $user->display_name ),
					);

					if ( get_option( 'show_avatars' ) ) {
						$send['avatar_src']    = get_avatar_url( $user->ID, array( 'size' => 18 ) );
						$send['avatar_src_2x'] = get_avatar_url( $user->ID, array( 'size' => 36 ) );
					}

					$checked[ $key ] = $send;
				}
			}
		}
	}

	if ( ! empty( $checked ) ) {
		$response['wp-check-locked-posts'] = $checked;
	}

	return $response;
}

/**
 * Checks lock status on the New/Edit Post screen and refresh the lock.
 *
 * @since 3.6.0
 *
 * @param array  $response  The Heartbeat response.
 * @param array  $data      The $_POST data sent.
 * @param string $screen_id The screen ID.
 * @return array The Heartbeat response.
 */
function wp_refresh_post_lock( $response, $data, $screen_id ) {
	if ( array_key_exists( 'wp-refresh-post-lock', $data ) ) {
		$received = $data['wp-refresh-post-lock'];
		$send     = array();

		$post_id = absint( $received['post_id'] );

		if ( ! $post_id ) {
			return $response;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $response;
		}

		$user_id = wp_check_post_lock( $post_id );
		$user    = get_userdata( $user_id );

		if ( $user ) {
			$error = array(
				'name' => $user->display_name,
				/* translators: %s: User's display name. */
				'text' => sprintf( __( '%s has taken over and is currently editing.' ), $user->display_name ),
			);

			if ( get_option( 'show_avatars' ) ) {
				$error['avatar_src']    = get_avatar_url( $user->ID, array( 'size' => 64 ) );
				$error['avatar_src_2x'] = get_avatar_url( $user->ID, array( 'size' => 128 ) );
			}

			$send['lock_error'] = $error;
		} else {
			$new_lock = wp_set_post_lock( $post_id );

			if ( $new_lock ) {
				$send['new_lock'] = implode( ':', $new_lock );
			}
		}

		$response['wp-refresh-post-lock'] = $send;
	}

	return $response;
}

/**
 * Checks nonce expiration on the New/Edit Post screen and refresh if needed.
 *
 * @since 3.6.0
 *
 * @param array  $response  The Heartbeat response.
 * @param array  $data      The $_POST data sent.
 * @param string $screen_id The screen ID.
 * @return array The Heartbeat response.
 */
function wp_refresh_post_nonces( $response, $data, $screen_id ) {
	if ( array_key_exists( 'wp-refresh-post-nonces', $data ) ) {
		$received = $data['wp-refresh-post-nonces'];

		$response['wp-refresh-post-nonces'] = array( 'check' => 1 );

		$post_id = absint( $received['post_id'] );

		if ( ! $post_id ) {
			return $response;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $response;
		}

		$response['wp-refresh-post-nonces'] = array(
			'replace' => array(
				'getpermalinknonce'    => wp_create_nonce( 'getpermalink' ),
				'samplepermalinknonce' => wp_create_nonce( 'samplepermalink' ),
				'closedpostboxesnonce' => wp_create_nonce( 'closedpostboxes' ),
				'_ajax_linking_nonce'  => wp_create_nonce( 'internal-linking' ),
				'_wpnonce'             => wp_create_nonce( 'update-post_' . $post_id ),
			),
		);
	}

	return $response;
}

/**
 * Refresh nonces used with meta boxes in the block editor.
 *
 * @since 6.1.0
 *
 * @param array  $response  The Heartbeat response.
 * @param array  $data      The $_POST data sent.
 * @return array The Heartbeat response.
 */
function wp_refresh_metabox_loader_nonces( $response, $data ) {
	if ( empty( $data['wp-refresh-metabox-loader-nonces'] ) ) {
		return $response;
	}

	$received = $data['wp-refresh-metabox-loader-nonces'];
	$post_id  = (int) $received['post_id'];

	if ( ! $post_id ) {
		return $response;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $response;
	}

	$response['wp-refresh-metabox-loader-nonces'] = array(
		'replace' => array(
			'metabox_loader_nonce' => wp_create_nonce( 'meta-box-loader' ),
			'_wpnonce'             => wp_create_nonce( 'update-post_' . $post_id ),
		),
	);

	return $response;
}

/**
 * Adds the latest Heartbeat and REST API nonce to the Heartbeat response.
 *
 * @since 5.0.0
 *
 * @param array $response The Heartbeat response.
 * @return array The Heartbeat response.
 */
function wp_refresh_heartbeat_nonces( $response ) {
	// Refresh the Rest API nonce.
	$response['rest_nonce'] = wp_create_nonce( 'wp_rest' );

	// Refresh the Heartbeat nonce.
	$response['heartbeat_nonce'] = wp_create_nonce( 'heartbeat-nonce' );

	return $response;
}

/**
 * Disables suspension of Heartbeat on the Add/Edit Post screens.
 *
 * @since 3.8.0
 *
 * @global string $pagenow The filename of the current screen.
 *
 * @param array $settings An array of Heartbeat settings.
 * @return array Filtered Heartbeat settings.
 */
function wp_heartbeat_set_suspension( $settings ) {
	global $pagenow;

	if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
		$settings['suspension'] = 'disable';
	}

	return $settings;
}

/**
 * Performs autosave with heartbeat.
 *
 * @since 3.9.0
 *
 * @param array $response The Heartbeat response.
 * @param array $data     The $_POST data sent.
 * @return array The Heartbeat response.
 */
function heartbeat_autosave( $response, $data ) {
	if ( ! empty( $data['wp_autosave'] ) ) {
		$saved = wp_autosave( $data['wp_autosave'] );

		if ( is_wp_error( $saved ) ) {
			$response['wp_autosave'] = array(
				'success' => false,
				'message' => $saved->get_error_message(),
			);
		} elseif ( empty( $saved ) ) {
			$response['wp_autosave'] = array(
				'success' => false,
				'message' => __( 'Error while saving.' ),
			);
		} else {
			/* translators: Draft saved date format, see https://www.php.net/manual/datetime.format.php */
			$draft_saved_date_format = __( 'g:i:s a' );
			$response['wp_autosave'] = array(
				'success' => true,
				/* translators: %s: Date and time. */
				'message' => sprintf( __( 'Draft saved at %s.' ), date_i18n( $draft_saved_date_format ) ),
			);
		}
	}

	return $response;
}

/**
 * Removes single-use URL parameters and create canonical link based on new URL.
 *
 * Removes specific query string parameters from a URL, create the canonical link,
 * put it in the admin header, and change the current URL to match.
 *
 * @since 4.2.0
 */
function wp_admin_canonical_url() {
	$removable_query_args = wp_removable_query_args();

	if ( empty( $removable_query_args ) ) {
		return;
	}

	// Ensure we're using an absolute URL.
	$current_url  = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$filtered_url = remove_query_arg( $removable_query_args, $current_url );

	/**
	 * Filters the admin canonical URL value.
	 *
	 * @since 6.5.0
	 *
	 * @param string $filtered_url The admin canonical URL value.
	 */
	$filtered_url = apply_filters( 'wp_admin_canonical_url', $filtered_url );
	?>
	<link id="wp-admin-canonical" rel="canonical" href="<?php echo esc_url( $filtered_url ); ?>" />
	<script>
		if ( window.history.replaceState ) {
			window.history.replaceState( null, null, document.getElementById( 'wp-admin-canonical' ).href + window.location.hash );
		}
	</script>
	<?php
}

/**
 * Outputs JS that reloads the page if the user navigated to it with the Back or Forward button.
 *
 * Used on the Edit Post and Add New Post screens. Needed to ensure the page is not loaded from browser cache,
 * so the post title and editor content are the last saved versions. Ideally this script should run first in the head.
 *
 * @since 4.6.0
 */
function wp_page_reload_on_back_button_js() {
	?>
	<script>
		if ( typeof performance !== 'undefined' && performance.navigation && performance.navigation.type === 2 ) {
			document.location.reload( true );
		}
	</script>
	<?php
}

/**
 * Sends a confirmation request email when a change of site admin email address is attempted.
 *
 * The new site admin address will not become active until confirmed.
 *
 * @since 3.0.0
 * @since 4.9.0 This function was moved from wp-admin/includes/ms.php so it's no longer Multisite specific.
 *
 * @param string $old_value The old site admin email address.
 * @param string $value     The proposed new site admin email address.
 */
function update_option_new_admin_email( $old_value, $value ) {
	if ( get_option( 'admin_email' ) === $value || ! is_email( $value ) ) {
		return;
	}

	$hash            = md5( $value . time() . wp_rand() );
	$new_admin_email = array(
		'hash'     => $hash,
		'newemail' => $value,
	);
	update_option( 'adminhash', $new_admin_email, false );

	$switched_locale = switch_to_user_locale( get_current_user_id() );

	/* translators: Do not translate USERNAME, ADMIN_URL, EMAIL, SITENAME, SITEURL: those are placeholders. */
	$email_text = __(
		'Howdy,

A site administrator (###USERNAME###) recently requested to have the
administration email address changed on this site:
###SITEURL###

To confirm this change, please click on the following link:
###ADMIN_URL###

You can safely ignore and delete this email if you do not want to
take this action.

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
	);

	/**
	 * Filters the text of the email sent when a change of site admin email address is attempted.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *  - ###USERNAME###  The current user's username.
	 *  - ###ADMIN_URL### The link to click on to confirm the email change.
	 *  - ###EMAIL###     The proposed new site admin email address.
	 *  - ###SITENAME###  The name of the site.
	 *  - ###SITEURL###   The URL to the site.
	 *
	 * @since MU (3.0.0)
	 * @since 4.9.0 This filter is no longer Multisite specific.
	 *
	 * @param string $email_text      Text in the email.
	 * @param array  $new_admin_email {
	 *     Data relating to the new site admin email address.
	 *
	 *     @type string $hash     The secure hash used in the confirmation link URL.
	 *     @type string $newemail The proposed new site admin email address.
	 * }
	 */
	$content = apply_filters( 'new_admin_email_content', $email_text, $new_admin_email );

	$current_user = wp_get_current_user();
	$content      = str_replace( '###USERNAME###', $current_user->user_login, $content );
	$content      = str_replace( '###ADMIN_URL###', esc_url( self_admin_url( 'options.php?adminhash=' . $hash ) ), $content );
	$content      = str_replace( '###EMAIL###', $value, $content );
	$content      = str_replace( '###SITENAME###', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $content );
	$content      = str_replace( '###SITEURL###', home_url(), $content );

	if ( '' !== get_option( 'blogname' ) ) {
		$site_title = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	} else {
		$site_title = parse_url( home_url(), PHP_URL_HOST );
	}

	$subject = sprintf(
		/* translators: New admin email address notification email subject. %s: Site title. */
		__( '[%s] New Admin Email Address' ),
		$site_title
	);

	/**
	 * Filters the subject of the email sent when a change of site admin email address is attempted.
	 *
	 * @since 6.5.0
	 *
	 * @param string $subject Subject of the email.
	 */
	$subject = apply_filters( 'new_admin_email_subject', $subject );

	wp_mail( $value, $subject, $content );

	if ( $switched_locale ) {
		restore_previous_locale();
	}
}

/**
 * Appends '(Draft)' to draft page titles in the privacy page dropdown
 * so that unpublished content is obvious.
 *
 * @since 4.9.8
 * @access private
 *
 * @param string  $title Page title.
 * @param WP_Post $page  Page data object.
 * @return string Page title.
 */
function _wp_privacy_settings_filter_draft_page_titles( $title, $page ) {
	if ( 'draft' === $page->post_status && 'privacy' === get_current_screen()->id ) {
		/* translators: %s: Page title. */
		$title = sprintf( __( '%s (Draft)' ), $title );
	}

	return $title;
}

/**
 * Checks if the user needs to update PHP.
 *
 * @since 5.1.0
 * @since 5.1.1 Added the {@see 'wp_is_php_version_acceptable'} filter.
 *
 * @return array|false {
 *     Array of PHP version data. False on failure.
 *
 *     @type string $recommended_version The PHP version recommended by WordPress.
 *     @type string $minimum_version     The minimum required PHP version.
 *     @type bool   $is_supported        Whether the PHP version is actively supported.
 *     @type bool   $is_secure           Whether the PHP version receives security updates.
 *     @type bool   $is_acceptable       Whether the PHP version is still acceptable or warnings
 *                                       should be shown and an update recommended.
 * }
 */
function wp_check_php_version() {
	$version = PHP_VERSION;
	$key     = md5( $version );

	$response = get_site_transient( 'php_check_' . $key );

	if ( false === $response ) {
		$url = 'http://api.wordpress.org/core/serve-happy/1.0/';

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$url = add_query_arg( 'php_version', $version, $url );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $response ) ) {
			return false;
		}

		set_site_transient( 'php_check_' . $key, $response, WEEK_IN_SECONDS );
	}

	if ( isset( $response['is_acceptable'] ) && $response['is_acceptable'] ) {
		/**
		 * Filters whether the active PHP version is considered acceptable by WordPress.
		 *
		 * Returning false will trigger a PHP version warning to show up in the admin dashboard to administrators.
		 *
		 * This filter is only run if the wordpress.org Serve Happy API considers the PHP version acceptable, ensuring
		 * that this filter can only make this check stricter, but not loosen it.
		 *
		 * @since 5.1.1
		 *
		 * @param bool   $is_acceptable Whether the PHP version is considered acceptable. Default true.
		 * @param string $version       PHP version checked.
		 */
		$response['is_acceptable'] = (bool) apply_filters( 'wp_is_php_version_acceptable', true, $version );
	}

	$response['is_lower_than_future_minimum'] = false;

	// The minimum supported PHP version will be updated to 7.4 in the future. Check if the current version is lower.
	if ( version_compare( $version, '7.4', '<' ) ) {
		$response['is_lower_than_future_minimum'] = true;

		// Force showing of warnings.
		$response['is_acceptable'] = false;
	}

	return $response;
}
