<?php
/**
 * Các hàm liên quan đến đăng ký và phân tích cú pháp block.
 *
 * @package WordPress
 * @subpackage Blocks
 * @since 5.0.0
 */

/**
 * Xoá tiền tố đường dẫn của tài nguyên block nếu có.
 *
 * @since 5.5.0
 *
 * @param string $asset_handle_or_path Handle tài nguyên hoặc đường dẫn có tiền tố.
 * @return string Đường dẫn không có tiền tố hoặc giá trị gốc.
 */
function remove_block_asset_path_prefix( $asset_handle_or_path ) {
	$path_prefix = 'file:';
	if ( ! str_starts_with( $asset_handle_or_path, $path_prefix ) ) {
		return $asset_handle_or_path;
	}
	$path = substr(
		$asset_handle_or_path,
		strlen( $path_prefix )
	);
	if ( str_starts_with( $path, './' ) ) {
		$path = substr( $path, 2 );
	}
	return $path;
}

/**
 * Tạo tên cho tài nguyên dựa trên tên block
 * và tên trường được cung cấp.
 *
 * @since 5.5.0
 * @since 6.1.0 Thêm tham số `$index`.
 * @since 6.5.0 Thêm hỗ trợ cho trường `viewScriptModule`.
 *
 * @param string $block_name Tên của block.
 * @param string $field_name Tên của trường metadata.
 * @param int    $index      Tùy chọn. Chỉ mục của tài nguyên khi truyền nhiều mục.
 *                           Mặc định 0.
 * @return string Tên tài nguyên được tạo cho trường của block.
 */
function generate_block_asset_handle( $block_name, $field_name, $index = 0 ) {
	if ( str_starts_with( $block_name, 'core/' ) ) {
		$asset_handle = str_replace( 'core/', 'wp-block-', $block_name );
		if ( str_starts_with( $field_name, 'editor' ) ) {
			$asset_handle .= '-editor';
		}
		if ( str_starts_with( $field_name, 'view' ) ) {
			$asset_handle .= '-view';
		}
		if ( str_ends_with( strtolower( $field_name ), 'scriptmodule' ) ) {
			$asset_handle .= '-script-module';
		}
		if ( $index > 0 ) {
			$asset_handle .= '-' . ( $index + 1 );
		}
		return $asset_handle;
	}

	$field_mappings = array(
		'editorScript'     => 'editor-script',
		'editorStyle'      => 'editor-style',
		'script'           => 'script',
		'style'            => 'style',
		'viewScript'       => 'view-script',
		'viewScriptModule' => 'view-script-module',
		'viewStyle'        => 'view-style',
	);
	$asset_handle   = str_replace( '/', '-', $block_name ) .
		'-' . $field_mappings[ $field_name ];
	if ( $index > 0 ) {
		$asset_handle .= '-' . ( $index + 1 );
	}
	return $asset_handle;
}

/**
 * Lấy URL đến tài nguyên block.
 *
 * @since 6.4.0
 *
 * @param string $path Đường dẫn đã chuẩn hoá đến tài nguyên block.
 * @return string|false URL đến tài nguyên block hoặc false khi thất bại.
 */
function get_block_asset_url( $path ) {
	if ( empty( $path ) ) {
		return false;
	}

	// Đường dẫn cần được chuẩn hoá để hoạt động trong môi trường Windows.
	static $wpinc_path_norm = '';
	if ( ! $wpinc_path_norm ) {
		$wpinc_path_norm = wp_normalize_path( realpath( ABSPATH . WPINC ) );
	}

	if ( str_starts_with( $path, $wpinc_path_norm ) ) {
		return includes_url( str_replace( $wpinc_path_norm, '', $path ) );
	}

	static $template_paths_norm = array();

	$template = get_template();
	if ( ! isset( $template_paths_norm[ $template ] ) ) {
		$template_paths_norm[ $template ] = wp_normalize_path( realpath( get_template_directory() ) );
	}

	if ( str_starts_with( $path, trailingslashit( $template_paths_norm[ $template ] ) ) ) {
		return get_theme_file_uri( str_replace( $template_paths_norm[ $template ], '', $path ) );
	}

	if ( is_child_theme() ) {
		$stylesheet = get_stylesheet();
		if ( ! isset( $template_paths_norm[ $stylesheet ] ) ) {
			$template_paths_norm[ $stylesheet ] = wp_normalize_path( realpath( get_stylesheet_directory() ) );
		}

		if ( str_starts_with( $path, trailingslashit( $template_paths_norm[ $stylesheet ] ) ) ) {
			return get_theme_file_uri( str_replace( $template_paths_norm[ $stylesheet ], '', $path ) );
		}
	}

	return plugins_url( basename( $path ), $path );
}

/**
 * Tìm ID module script cho trường metadata block đã chọn. Hàm phát hiện
 * khi đường dẫn đến tệp được cung cấp và tuỳ chọn tìm tệp tài nguyên
 * tương ứng với các chi tiết cần thiết để đăng ký module script với
 * ID module được tạo tự động. Trả về ID module script chưa xử lý
 * trong trường hợp khác.
 *
 * @since 6.5.0
 *
 * @param array  $metadata   Metadata của block.
 * @param string $field_name Tên trường để lấy từ metadata.
 * @param int    $index      Tùy chọn. Chỉ mục của ID module script để đăng ký khi truyền
 *                           nhiều mục. Mặc định 0.
 * @return string|false ID module script hoặc false khi thất bại.
 */
function register_block_script_module_id( $metadata, $field_name, $index = 0 ) {
	if ( empty( $metadata[ $field_name ] ) ) {
		return false;
	}

	$module_id = $metadata[ $field_name ];
	if ( is_array( $module_id ) ) {
		if ( empty( $module_id[ $index ] ) ) {
			return false;
		}
		$module_id = $module_id[ $index ];
	}

	$module_path = remove_block_asset_path_prefix( $module_id );
	if ( $module_id === $module_path ) {
		return $module_id;
	}

	$path                  = dirname( $metadata['file'] );
	$module_asset_raw_path = $path . '/' . substr_replace( $module_path, '.asset.php', - strlen( '.js' ) );
	$module_id             = generate_block_asset_handle( $metadata['name'], $field_name, $index );
	$module_asset_path     = wp_normalize_path(
		realpath( $module_asset_raw_path )
	);

	$module_path_norm = wp_normalize_path( realpath( $path . '/' . $module_path ) );
	$module_uri       = get_block_asset_url( $module_path_norm );

	$module_asset        = ! empty( $module_asset_path ) ? require $module_asset_path : array();
	$module_dependencies = isset( $module_asset['dependencies'] ) ? $module_asset['dependencies'] : array();
	$block_version       = isset( $metadata['version'] ) ? $metadata['version'] : false;
	$module_version      = isset( $module_asset['version'] ) ? $module_asset['version'] : $block_version;

	wp_register_script_module(
		$module_id,
		$module_uri,
		$module_dependencies,
		$module_version
	);

	return $module_id;
}

/**
 * Tìm handle script cho trường metadata block đã chọn. Hàm phát hiện
 * khi đường dẫn đến tệp được cung cấp và tuỳ chọn tìm tệp tài nguyên
 * tương ứng với các chi tiết cần thiết để đăng ký script với tên
 * handle được tạo tự động. Trả về handle script chưa xử lý trong trường hợp khác.
 *
 * @since 5.5.0
 * @since 6.1.0 Thêm tham số `$index`.
 * @since 6.5.0 Tệp tài nguyên là tuỳ chọn. Thêm hỗ trợ handle script trong tệp tài nguyên.
 *
 * @param array  $metadata   Metadata của block.
 * @param string $field_name Tên trường để lấy từ metadata.
 * @param int    $index      Tùy chọn. Chỉ mục của script để đăng ký khi truyền nhiều mục.
 *                           Mặc định 0.
 * @return string|false Handle script được cung cấp trực tiếp hoặc được tạo thông qua
 *                      việc đăng ký script, hoặc false khi thất bại.
 */
function register_block_script_handle( $metadata, $field_name, $index = 0 ) {
	if ( empty( $metadata[ $field_name ] ) ) {
		return false;
	}

	$script_handle_or_path = $metadata[ $field_name ];
	if ( is_array( $script_handle_or_path ) ) {
		if ( empty( $script_handle_or_path[ $index ] ) ) {
			return false;
		}
		$script_handle_or_path = $script_handle_or_path[ $index ];
	}

	$script_path = remove_block_asset_path_prefix( $script_handle_or_path );
	if ( $script_handle_or_path === $script_path ) {
		return $script_handle_or_path;
	}

	$path                  = dirname( $metadata['file'] );
	$script_asset_raw_path = $path . '/' . substr_replace( $script_path, '.asset.php', - strlen( '.js' ) );
	$script_asset_path     = wp_normalize_path(
		realpath( $script_asset_raw_path )
	);

	// Tệp tài nguyên cho block là tuỳ chọn. Xem https://core.trac.wordpress.org/ticket/60460.
	$script_asset  = ! empty( $script_asset_path ) ? require $script_asset_path : array();
	$script_handle = isset( $script_asset['handle'] ) ?
		$script_asset['handle'] :
		generate_block_asset_handle( $metadata['name'], $field_name, $index );
	if ( wp_script_is( $script_handle, 'registered' ) ) {
		return $script_handle;
	}

	$script_path_norm    = wp_normalize_path( realpath( $path . '/' . $script_path ) );
	$script_uri          = get_block_asset_url( $script_path_norm );
	$script_dependencies = isset( $script_asset['dependencies'] ) ? $script_asset['dependencies'] : array();
	$block_version       = isset( $metadata['version'] ) ? $metadata['version'] : false;
	$script_version      = isset( $script_asset['version'] ) ? $script_asset['version'] : $block_version;
	$script_args         = array();
	if ( 'viewScript' === $field_name && $script_uri ) {
		$script_args['strategy'] = 'defer';
	}

	$result = wp_register_script(
		$script_handle,
		$script_uri,
		$script_dependencies,
		$script_version,
		$script_args
	);
	if ( ! $result ) {
		return false;
	}

	if ( ! empty( $metadata['textdomain'] ) && in_array( 'wp-i18n', $script_dependencies, true ) ) {
		wp_set_script_translations( $script_handle, $metadata['textdomain'] );
	}

	return $script_handle;
}

/**
 * Tìm handle style cho trường metadata block. Hàm phát hiện khi đường dẫn
 * đến tệp được cung cấp và đăng ký style với tên handle được tạo
 * tự động. Trả về handle style chưa xử lý trong trường hợp khác.
 *
 * @since 5.5.0
 * @since 6.1.0 Thêm tham số `$index`.
 *
 * @param array  $metadata   Metadata của block.
 * @param string $field_name Tên trường để lấy từ metadata.
 * @param int    $index      Tùy chọn. Chỉ mục của style để đăng ký khi truyền nhiều mục.
 *                           Mặc định 0.
 * @return string|false Handle style được cung cấp trực tiếp hoặc được tạo thông qua
 *                      việc đăng ký style, hoặc false khi thất bại.
 */
function register_block_style_handle( $metadata, $field_name, $index = 0 ) {
	if ( empty( $metadata[ $field_name ] ) ) {
		return false;
	}

	$style_handle = $metadata[ $field_name ];
	if ( is_array( $style_handle ) ) {
		if ( empty( $style_handle[ $index ] ) ) {
			return false;
		}
		$style_handle = $style_handle[ $index ];
	}

	$style_handle_name = generate_block_asset_handle( $metadata['name'], $field_name, $index );
	// Nếu handle style đã được đăng ký, bỏ qua việc đăng ký lại.
	if ( wp_style_is( $style_handle_name, 'registered' ) ) {
		return $style_handle_name;
	}

	static $wpinc_path_norm = '';
	if ( ! $wpinc_path_norm ) {
		$wpinc_path_norm = wp_normalize_path( realpath( ABSPATH . WPINC ) );
	}

	$is_core_block = isset( $metadata['file'] ) && str_starts_with( $metadata['file'], $wpinc_path_norm );
	// Bỏ qua việc đăng ký style riêng lẻ cho mỗi block core khi có phiên bản đóng gói.
	if ( $is_core_block && ! wp_should_load_separate_core_block_assets() ) {
		return false;
	}

	$style_path      = remove_block_asset_path_prefix( $style_handle );
	$is_style_handle = $style_handle === $style_path;
	// Chỉ cho phép truyền handle style cho các block core.
	if ( $is_core_block && ! $is_style_handle ) {
		return false;
	}
	// Trả về handle style trừ khi đó là mục đầu tiên cho mỗi block core cần xử lý đặc biệt.
	if ( $is_style_handle && ! ( $is_core_block && 0 === $index ) ) {
		return $style_handle;
	}

	// Kiểm tra xem style có nên có hậu tố ".min" hay không.
	$suffix = SCRIPT_DEBUG ? '' : '.min';
	if ( $is_core_block ) {
		$style_path = ( 'editorStyle' === $field_name ) ? "editor{$suffix}.css" : "style{$suffix}.css";
	}

	$style_path_norm = wp_normalize_path( realpath( dirname( $metadata['file'] ) . '/' . $style_path ) );
	$style_uri       = get_block_asset_url( $style_path_norm );

	$block_version = ! $is_core_block && isset( $metadata['version'] ) ? $metadata['version'] : false;
	$version       = $style_path_norm && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( $style_path_norm ) : $block_version;
	$result        = wp_register_style(
		$style_handle_name,
		$style_uri,
		array(),
		$version
	);
	if ( ! $result ) {
		return false;
	}

	if ( $style_uri ) {
		wp_style_add_data( $style_handle_name, 'path', $style_path_norm );

		if ( $is_core_block ) {
			$rtl_file = str_replace( "{$suffix}.css", "-rtl{$suffix}.css", $style_path_norm );
		} else {
			$rtl_file = str_replace( '.css', '-rtl.css', $style_path_norm );
		}

		if ( is_rtl() && file_exists( $rtl_file ) ) {
			wp_style_add_data( $style_handle_name, 'rtl', 'replace' );
			wp_style_add_data( $style_handle_name, 'suffix', $suffix );
			wp_style_add_data( $style_handle_name, 'path', $rtl_file );
		}
	}

	return $style_handle_name;
}

/**
 * Lấy schema i18n cho metadata của block đọc từ tệp `block.json`.
 *
 * @since 5.9.0
 *
 * @return object Schema cho metadata của block.
 */
function get_block_metadata_i18n_schema() {
	static $i18n_block_schema;

	if ( ! isset( $i18n_block_schema ) ) {
		$i18n_block_schema = wp_json_file_decode( __DIR__ . '/block-i18n.json' );
	}

	return $i18n_block_schema;
}

/**
 * Đăng ký tất cả các loại block từ bộ sưu tập metadata block.
 *
 * Hàm này có thể tham chiếu đến một bộ sưu tập metadata đã đăng ký trước đó hoặc, nếu tham số `$manifest` được cung cấp,
 * đăng ký bộ sưu tập metadata trực tiếp trong cùng lời gọi hàm.
 *
 * @since 6.8.0
 * @see wp_register_block_metadata_collection()
 * @see register_block_type_from_metadata()
 *
 * @param string $path     Đường dẫn cơ sở tuyệt đối cho bộ sưu tập (ví dụ: WP_PLUGIN_DIR . '/my-plugin/blocks/').
 * @param string $manifest Tùy chọn. Đường dẫn tuyệt đối đến tệp manifest chứa bộ sưu tập metadata,
 *                         để đăng ký bộ sưu tập. Nếu tham số này không được cung cấp, tham số `$path`
 *                         phải tham chiếu đến bộ sưu tập metadata block đã được đăng ký trước đó.
 */
function wp_register_block_types_from_metadata_collection( $path, $manifest = '' ) {
	if ( $manifest ) {
		wp_register_block_metadata_collection( $path, $manifest );
	}

	$block_metadata_files = WP_Block_Metadata_Registry::get_collection_block_metadata_files( $path );
	foreach ( $block_metadata_files as $block_metadata_file ) {
		register_block_type_from_metadata( $block_metadata_file );
	}
}

/**
 * Đăng ký bộ sưu tập metadata block.
 *
 * Hàm này cho phép core và các plugin bên thứ ba đăng ký bộ sưu tập metadata block
 * của họ tại một vị trí tập trung. Đăng ký bộ sưu tập có thể cải thiện hiệu suất
 * bằng cách tránh đọc nhiều lần từ hệ thống tệp và phân tích JSON.
 *
 * @since 6.7.0
 *
 * @param string $path     Đường dẫn cơ sở nơi các tệp block cho bộ sưu tập nằm.
 * @param string $manifest Đường dẫn đến tệp manifest cho bộ sưu tập.
 */
function wp_register_block_metadata_collection( $path, $manifest ) {
	WP_Block_Metadata_Registry::register_collection( $path, $manifest );
}

/**
 * Đăng ký loại block từ metadata được lưu trữ trong tệp `block.json`.
 *
 * @since 5.5.0
 * @since 5.7.0 Thêm hỗ trợ cho trường `textdomain` và xử lý i18n cho tất cả các trường có thể dịch.
 * @since 5.9.0 Thêm hỗ trợ cho các trường `variations` và `viewScript`.
 * @since 6.1.0 Thêm hỗ trợ cho trường `render`.
 * @since 6.3.0 Thêm trường `selectors`.
 * @since 6.4.0 Thêm hỗ trợ cho trường `blockHooks`.
 * @since 6.5.0 Thêm hỗ trợ cho các trường `allowedBlocks`, `viewScriptModule` và `viewStyle`.
 * @since 6.7.0 Cho phép tên tệp PHP làm đối số `variations`.
 *
 * @param string $file_or_folder Đường dẫn đến tệp JSON chứa định nghĩa metadata cho
 *                               block hoặc đường dẫn đến thư mục chứa tệp `block.json`.
 *                               Nếu cung cấp đường dẫn đến tệp JSON, tên tệp phải kết thúc bằng `block.json`.
 * @param array  $args           Tùy chọn. Mảng các đối số loại block. Chấp nhận bất kỳ thuộc tính công khai
 *                               nào của `WP_Block_Type`. Xem WP_Block_Type::__construct() để biết thông tin
 *                               về các đối số được chấp nhận. Mặc định mảng rỗng.
 * @return WP_Block_Type|false Loại block đã đăng ký khi thành công, hoặc false khi thất bại.
 */
function register_block_type_from_metadata( $file_or_folder, $args = array() ) {
	/*
	 * Lấy mảng metadata từ tệp PHP.
	 * Điều này cải thiện hiệu suất cho các block core vì chỉ cần đọc một tệp PHP duy nhất
	 * thay vì đọc một tệp JSON cho mỗi block, rồi giải mã từ JSON sang PHP.
	 * Sử dụng biến static đảm bảo metadata chỉ được đọc một lần mỗi request.
	 */

	$file_or_folder = wp_normalize_path( $file_or_folder );

	$metadata_file = ( ! str_ends_with( $file_or_folder, 'block.json' ) ) ?
		trailingslashit( $file_or_folder ) . 'block.json' :
		$file_or_folder;

	$is_core_block        = str_starts_with( $file_or_folder, wp_normalize_path( ABSPATH . WPINC ) );
	$metadata_file_exists = $is_core_block || file_exists( $metadata_file );
	$registry_metadata    = WP_Block_Metadata_Registry::get_metadata( $file_or_folder );

	if ( $registry_metadata ) {
		$metadata = $registry_metadata;
	} elseif ( $metadata_file_exists ) {
		$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );
	} else {
		$metadata = array();
	}

	if ( ! is_array( $metadata ) || ( empty( $metadata['name'] ) && empty( $args['name'] ) ) ) {
		return false;
	}

	$metadata['file'] = $metadata_file_exists ? wp_normalize_path( realpath( $metadata_file ) ) : null;

	/**
	 * Lọc metadata được cung cấp để đăng ký loại block.
	 *
	 * @since 5.7.0
	 *
	 * @param array $metadata Metadata để đăng ký loại block.
	 */
	$metadata = apply_filters( 'block_type_metadata', $metadata );

	// Thêm `style` và `editor_style` cho các block core nếu thiếu.
	if ( ! empty( $metadata['name'] ) && str_starts_with( $metadata['name'], 'core/' ) ) {
		$block_name = str_replace( 'core/', '', $metadata['name'] );

		if ( ! isset( $metadata['style'] ) ) {
			$metadata['style'] = "wp-block-$block_name";
		}
		if ( current_theme_supports( 'wp-block-styles' ) && wp_should_load_separate_core_block_assets() ) {
			$metadata['style']   = (array) $metadata['style'];
			$metadata['style'][] = "wp-block-{$block_name}-theme";
		}
		if ( ! isset( $metadata['editorStyle'] ) ) {
			$metadata['editorStyle'] = "wp-block-{$block_name}-editor";
		}
	}

	$settings          = array();
	$property_mappings = array(
		'apiVersion'      => 'api_version',
		'name'            => 'name',
		'title'           => 'title',
		'category'        => 'category',
		'parent'          => 'parent',
		'ancestor'        => 'ancestor',
		'icon'            => 'icon',
		'description'     => 'description',
		'keywords'        => 'keywords',
		'attributes'      => 'attributes',
		'providesContext' => 'provides_context',
		'usesContext'     => 'uses_context',
		'selectors'       => 'selectors',
		'supports'        => 'supports',
		'styles'          => 'styles',
		'variations'      => 'variations',
		'example'         => 'example',
		'allowedBlocks'   => 'allowed_blocks',
	);
	$textdomain        = ! empty( $metadata['textdomain'] ) ? $metadata['textdomain'] : null;
	$i18n_schema       = get_block_metadata_i18n_schema();

	foreach ( $property_mappings as $key => $mapped_key ) {
		if ( isset( $metadata[ $key ] ) ) {
			$settings[ $mapped_key ] = $metadata[ $key ];
			if ( $metadata_file_exists && $textdomain && isset( $i18n_schema->$key ) ) {
				$settings[ $mapped_key ] = translate_settings_using_i18n_schema( $i18n_schema->$key, $settings[ $key ], $textdomain );
			}
		}
	}

	if ( ! empty( $metadata['render'] ) ) {
		$template_path = wp_normalize_path(
			realpath(
				dirname( $metadata['file'] ) . '/' .
				remove_block_asset_path_prefix( $metadata['render'] )
			)
		);
		if ( $template_path ) {
			/**
			 * Render block trên server.
			 *
			 * @since 6.1.0
			 *
			 * @param array    $attributes Các thuộc tính của block.
			 * @param string   $content    Nội dung mặc định của block.
			 * @param WP_Block $block      Thực thể block.
			 *
			 * @return string Trả về nội dung block.
			 */
			$settings['render_callback'] = static function ( $attributes, $content, $block ) use ( $template_path ) {
				ob_start();
				require $template_path;
				return ob_get_clean();
			};
		}
	}

	// Nếu `variations` là chuỗi, đó là tên tệp PHP
	// tạo ra các biến thể.
	if ( ! empty( $metadata['variations'] ) && is_string( $metadata['variations'] ) ) {
		$variations_path = wp_normalize_path(
			realpath(
				dirname( $metadata['file'] ) . '/' .
				remove_block_asset_path_prefix( $metadata['variations'] )
			)
		);
		if ( $variations_path ) {
			/**
			 * Tạo danh sách các biến thể block.
			 *
			 * @since 6.7.0
			 *
			 * @return string Trả về danh sách các biến thể block.
			 */
			$settings['variation_callback'] = static function () use ( $variations_path ) {
				$variations = require $variations_path;
				return $variations;
			};
			// Trường `variations` của thực thể block chỉ được phép là một mảng
			// (các biến thể block đã biết). Chúng ta unset nó để thực thể block
			// cung cấp getter trả về kết quả của `variation_callback` thay thế.
			unset( $settings['variations'] );
		}
	}

	$settings = array_merge( $settings, $args );

	$script_fields = array(
		'editorScript' => 'editor_script_handles',
		'script'       => 'script_handles',
		'viewScript'   => 'view_script_handles',
	);
	foreach ( $script_fields as $metadata_field_name => $settings_field_name ) {
		if ( ! empty( $settings[ $metadata_field_name ] ) ) {
			$metadata[ $metadata_field_name ] = $settings[ $metadata_field_name ];
		}
		if ( ! empty( $metadata[ $metadata_field_name ] ) ) {
			$scripts           = $metadata[ $metadata_field_name ];
			$processed_scripts = array();
			if ( is_array( $scripts ) ) {
				for ( $index = 0; $index < count( $scripts ); $index++ ) {
					$result = register_block_script_handle(
						$metadata,
						$metadata_field_name,
						$index
					);
					if ( $result ) {
						$processed_scripts[] = $result;
					}
				}
			} else {
				$result = register_block_script_handle(
					$metadata,
					$metadata_field_name
				);
				if ( $result ) {
					$processed_scripts[] = $result;
				}
			}
			$settings[ $settings_field_name ] = $processed_scripts;
		}
	}

	$module_fields = array(
		'viewScriptModule' => 'view_script_module_ids',
	);
	foreach ( $module_fields as $metadata_field_name => $settings_field_name ) {
		if ( ! empty( $settings[ $metadata_field_name ] ) ) {
			$metadata[ $metadata_field_name ] = $settings[ $metadata_field_name ];
		}
		if ( ! empty( $metadata[ $metadata_field_name ] ) ) {
			$modules           = $metadata[ $metadata_field_name ];
			$processed_modules = array();
			if ( is_array( $modules ) ) {
				for ( $index = 0; $index < count( $modules ); $index++ ) {
					$result = register_block_script_module_id(
						$metadata,
						$metadata_field_name,
						$index
					);
					if ( $result ) {
						$processed_modules[] = $result;
					}
				}
			} else {
				$result = register_block_script_module_id(
					$metadata,
					$metadata_field_name
				);
				if ( $result ) {
					$processed_modules[] = $result;
				}
			}
			$settings[ $settings_field_name ] = $processed_modules;
		}
	}

	$style_fields = array(
		'editorStyle' => 'editor_style_handles',
		'style'       => 'style_handles',
		'viewStyle'   => 'view_style_handles',
	);
	foreach ( $style_fields as $metadata_field_name => $settings_field_name ) {
		if ( ! empty( $settings[ $metadata_field_name ] ) ) {
			$metadata[ $metadata_field_name ] = $settings[ $metadata_field_name ];
		}
		if ( ! empty( $metadata[ $metadata_field_name ] ) ) {
			$styles           = $metadata[ $metadata_field_name ];
			$processed_styles = array();
			if ( is_array( $styles ) ) {
				for ( $index = 0; $index < count( $styles ); $index++ ) {
					$result = register_block_style_handle(
						$metadata,
						$metadata_field_name,
						$index
					);
					if ( $result ) {
						$processed_styles[] = $result;
					}
				}
			} else {
				$result = register_block_style_handle(
					$metadata,
					$metadata_field_name
				);
				if ( $result ) {
					$processed_styles[] = $result;
				}
			}
			$settings[ $settings_field_name ] = $processed_styles;
		}
	}

	if ( ! empty( $metadata['blockHooks'] ) ) {
		/**
		 * Ánh xạ chuỗi vị trí camelCased (từ block.json) sang vị trí loại block snake_cased.
		 *
		 * @var array
		 */
		$position_mappings = array(
			'before'     => 'before',
			'after'      => 'after',
			'firstChild' => 'first_child',
			'lastChild'  => 'last_child',
		);

		$settings['block_hooks'] = array();
		foreach ( $metadata['blockHooks'] as $anchor_block_name => $position ) {
			// Tránh đệ quy vô hạn (gắn hook vào chính nó).
			if ( $metadata['name'] === $anchor_block_name ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'Cannot hook block to itself.' ),
					'6.4.0'
				);
				continue;
			}

			if ( ! isset( $position_mappings[ $position ] ) ) {
				continue;
			}

			$settings['block_hooks'][ $anchor_block_name ] = $position_mappings[ $position ];
		}
	}

	/**
	 * Lọc các thiết lập được xác định từ metadata loại block.
	 *
	 * @since 5.7.0
	 *
	 * @param array $settings Mảng các thiết lập đã xác định để đăng ký loại block.
	 * @param array $metadata Metadata được cung cấp để đăng ký loại block.
	 */
	$settings = apply_filters( 'block_type_metadata_settings', $settings, $metadata );

	$metadata['name'] = ! empty( $settings['name'] ) ? $settings['name'] : $metadata['name'];

	return WP_Block_Type_Registry::get_instance()->register(
		$metadata['name'],
		$settings
	);
}

/**
 * Đăng ký loại block. Cách khuyên dùng là đăng ký loại block sử dụng
 * metadata được lưu trữ trong tệp `block.json`.
 *
 * @since 5.0.0
 * @since 5.8.0 Tham số đầu tiên giờ chấp nhận đường dẫn đến tệp `block.json`.
 *
 * @param string|WP_Block_Type $block_type Tên loại block bao gồm namespace, hoặc
 *                                         đường dẫn đến tệp JSON chứa định nghĩa metadata cho block,
 *                                         hoặc đường dẫn đến thư mục chứa tệp `block.json`,
 *                                         hoặc một thực thể WP_Block_Type đầy đủ.
 *                                         Trong trường hợp WP_Block_Type được cung cấp, tham số $args sẽ bị bỏ qua.
 * @param array                $args       Tùy chọn. Mảng các đối số loại block. Chấp nhận bất kỳ thuộc tính công khai
 *                                         nào của `WP_Block_Type`. Xem WP_Block_Type::__construct() để biết thông tin
 *                                         về các đối số được chấp nhận. Mặc định mảng rỗng.
 *
 * @return WP_Block_Type|false Loại block đã đăng ký khi thành công, hoặc false khi thất bại.
 */
function register_block_type( $block_type, $args = array() ) {
	if ( is_string( $block_type ) && file_exists( $block_type ) ) {
		return register_block_type_from_metadata( $block_type, $args );
	}

	return WP_Block_Type_Registry::get_instance()->register( $block_type, $args );
}

/**
 * Hủy đăng ký loại block.
 *
 * @since 5.0.0
 *
 * @param string|WP_Block_Type $name Tên loại block bao gồm namespace, hoặc
 *                                   một thực thể WP_Block_Type đầy đủ.
 * @return WP_Block_Type|false Loại block đã hủy đăng ký khi thành công, hoặc false khi thất bại.
 */
function unregister_block_type( $name ) {
	return WP_Block_Type_Registry::get_instance()->unregister( $name );
}

/**
 * Xác định xem bài viết hoặc chuỗi nội dung có chứa block hay không.
 *
 * Kiểm tra này ưu tiên hiệu suất hơn độ chính xác tuyệt đối, phát hiện
 * mẫu của block nhưng không xác nhận cấu trúc của nó. Để có độ chính xác tuyệt đối,
 * bạn nên sử dụng trình phân tích block trên nội dung bài viết.
 *
 * @since 5.0.0
 *
 * @see parse_blocks()
 *
 * @param int|string|WP_Post|null $post Tùy chọn. Nội dung bài viết, ID bài viết, hoặc đối tượng bài viết.
 *                                      Mặc định là $post toàn cục.
 * @return bool Liệu bài viết có chứa block hay không.
 */
function has_blocks( $post = null ) {
	if ( ! is_string( $post ) ) {
		$wp_post = get_post( $post );

		if ( ! $wp_post instanceof WP_Post ) {
			return false;
		}

		$post = $wp_post->post_content;
	}

	return str_contains( (string) $post, '<!-- wp:' );
}

/**
 * Xác định xem bài viết hoặc chuỗi có chứa loại block cụ thể hay không.
 *
 * Kiểm tra này ưu tiên hiệu suất hơn độ chính xác tuyệt đối, phát hiện
 * xem loại block có tồn tại hay không nhưng không xác nhận cấu trúc và không kiểm tra
 * các pattern đồng bộ (trước đây gọi là block tái sử dụng). Để có độ chính xác tuyệt đối,
 * bạn nên sử dụng trình phân tích block trên nội dung bài viết.
 *
 * @since 5.0.0
 *
 * @see parse_blocks()
 *
 * @param string                  $block_name Tên đầy đủ của loại block cần tìm.
 * @param int|string|WP_Post|null $post       Tùy chọn. Nội dung bài viết, ID bài viết, hoặc đối tượng bài viết.
 *                                            Mặc định là $post toàn cục.
 * @return bool Liệu nội dung bài viết có chứa block được chỉ định hay không.
 */
function has_block( $block_name, $post = null ) {
	if ( ! has_blocks( $post ) ) {
		return false;
	}

	if ( ! is_string( $post ) ) {
		$wp_post = get_post( $post );
		if ( $wp_post instanceof WP_Post ) {
			$post = $wp_post->post_content;
		}
	}

	/*
	 * Chuẩn hóa tên block để bao gồm namespace, nếu được cung cấp không có namespace.
	 * Điều này khớp với hành vi của WordPress 5.0.0 - 5.3.0 khi so khớp block
	 * theo tên serialized của chúng.
	 */
	if ( ! str_contains( $block_name, '/' ) ) {
		$block_name = 'core/' . $block_name;
	}

	// Kiểm tra sự tồn tại của block bằng tên đầy đủ.
	$has_block = str_contains( $post, '<!-- wp:' . $block_name . ' ' );

	if ( ! $has_block ) {
		/*
		 * Nếu tên block đã cho sẽ serialize thành tên khác, kiểm tra
		 * sự tồn tại bằng dạng serialized.
		 */
		$serialized_block_name = strip_core_block_namespace( $block_name );
		if ( $serialized_block_name !== $block_name ) {
			$has_block = str_contains( $post, '<!-- wp:' . $serialized_block_name . ' ' );
		}
	}

	return $has_block;
}

/**
 * Trả về mảng tên của tất cả các loại block động đã đăng ký.
 *
 * @since 5.0.0
 *
 * @return string[] Mảng tên các block động.
 */
function get_dynamic_block_names() {
	$dynamic_block_names = array();

	$block_types = WP_Block_Type_Registry::get_instance()->get_all_registered();
	foreach ( $block_types as $block_type ) {
		if ( $block_type->is_dynamic() ) {
			$dynamic_block_names[] = $block_type->name;
		}
	}

	return $dynamic_block_names;
}

/**
 * Lấy các loại block được gắn hook vào block đã cho, được nhóm theo loại block neo và vị trí tương đối.
 *
 * @since 6.4.0
 *
 * @return array[] Mảng các loại block được nhóm theo loại block neo và vị trí tương đối.
 */
function get_hooked_blocks() {
	$block_types   = WP_Block_Type_Registry::get_instance()->get_all_registered();
	$hooked_blocks = array();
	foreach ( $block_types as $block_type ) {
		if ( ! ( $block_type instanceof WP_Block_Type ) || ! is_array( $block_type->block_hooks ) ) {
			continue;
		}
		foreach ( $block_type->block_hooks as $anchor_block_type => $relative_position ) {
			if ( ! isset( $hooked_blocks[ $anchor_block_type ] ) ) {
				$hooked_blocks[ $anchor_block_type ] = array();
			}
			if ( ! isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ) {
				$hooked_blocks[ $anchor_block_type ][ $relative_position ] = array();
			}
			$hooked_blocks[ $anchor_block_type ][ $relative_position ][] = $block_type->name;
		}
	}

	return $hooked_blocks;
}

/**
 * Trả về mã đánh dấu cho các block được gắn hook vào block neo đã cho ở vị trí tương đối cụ thể.
 *
 * @since 6.5.0
 * @access private
 *
 * @param array                           $parsed_anchor_block Block neo, ở định dạng mảng block đã phân tích.
 * @param string                          $relative_position   Vị trí tương đối của các block được gắn hook.
 *                                                             Có thể là 'before', 'after', 'first_child', hoặc 'last_child'.
 * @param array                           $hooked_blocks       Mảng các loại block được gắn hook, nhóm theo block neo và vị trí tương đối.
 * @param WP_Block_Template|WP_Post|array $context             Template block, template part, hoặc pattern mà block neo thuộc về.
 * @return string
 */
function insert_hooked_blocks( &$parsed_anchor_block, $relative_position, $hooked_blocks, $context ) {
	$anchor_block_type  = $parsed_anchor_block['blockName'];
	$hooked_block_types = isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] )
		? $hooked_blocks[ $anchor_block_type ][ $relative_position ]
		: array();

	/**
	 * Lọc danh sách các loại block được gắn hook cho loại block neo và vị trí tương đối đã cho.
	 *
	 * @since 6.4.0
	 *
	 * @param string[]                        $hooked_block_types Danh sách các loại block được gắn hook.
	 * @param string                          $relative_position  Vị trí tương đối của các block được gắn hook.
	 *                                                            Có thể là 'before', 'after', 'first_child', hoặc 'last_child'.
	 * @param string                          $anchor_block_type  Loại block neo.
	 * @param WP_Block_Template|WP_Post|array $context            Template block, template part, đối tượng bài viết,
	 *                                                            hoặc pattern mà block neo thuộc về.
	 */
	$hooked_block_types = apply_filters( 'hooked_block_types', $hooked_block_types, $relative_position, $anchor_block_type, $context );

	$markup = '';
	foreach ( $hooked_block_types as $hooked_block_type ) {
		$parsed_hooked_block = array(
			'blockName'    => $hooked_block_type,
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerContent' => array(),
		);

		/**
		 * Lọc mảng block đã phân tích cho block được gắn hook cụ thể.
		 *
		 * @since 6.5.0
		 *
		 * @param array|null                      $parsed_hooked_block Mảng block đã phân tích cho loại block được gắn hook, hoặc null để ẩn block.
		 * @param string                          $hooked_block_type   Tên loại block được gắn hook.
		 * @param string                          $relative_position   Vị trí tương đối của block được gắn hook.
		 * @param array                           $parsed_anchor_block Block neo, ở định dạng mảng block đã phân tích.
		 * @param WP_Block_Template|WP_Post|array $context             Template block, template part, đối tượng bài viết,
		 *                                                             hoặc pattern mà block neo thuộc về.
		 */
		$parsed_hooked_block = apply_filters( 'hooked_block', $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context );

		/**
		 * Lọc mảng block đã phân tích cho block được gắn hook cụ thể.
		 *
		 * Phần động của tên hook, `$hooked_block_type`, tham chiếu đến tên loại block của block được gắn hook cụ thể.
		 *
		 * @since 6.5.0
		 *
		 * @param array|null                      $parsed_hooked_block Mảng block đã phân tích cho loại block được gắn hook, hoặc null để ẩn block.
		 * @param string                          $hooked_block_type   Tên loại block được gắn hook.
		 * @param string                          $relative_position   Vị trí tương đối của block được gắn hook.
		 * @param array                           $parsed_anchor_block Block neo, ở định dạng mảng block đã phân tích.
		 * @param WP_Block_Template|WP_Post|array $context             Template block, template part, đối tượng bài viết,
		 *                                                             hoặc pattern mà block neo thuộc về.
		 */
		$parsed_hooked_block = apply_filters( "hooked_block_{$hooked_block_type}", $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context );

		if ( null === $parsed_hooked_block ) {
			continue;
		}

		// Có thể bộ lọc trả về block thuộc loại khác, nên chúng ta kiểm tra rõ ràng
		// `$hooked_block_type` gốc trong metadata `ignoredHookedBlocks`.
		if (
			! isset( $parsed_anchor_block['attrs']['metadata']['ignoredHookedBlocks'] ) ||
			! in_array( $hooked_block_type, $parsed_anchor_block['attrs']['metadata']['ignoredHookedBlocks'], true )
		) {
			$markup .= serialize_block( $parsed_hooked_block );
		}
	}

	return $markup;
}

/**
 * Thêm danh sách các loại block được gắn hook vào danh sách loại block được gắn hook bị bỏ qua của block neo.
 *
 * Hàm này chỉ dành cho sử dụng nội bộ.
 *
 * @since 6.5.0
 * @access private
 *
 * @param array                           $parsed_anchor_block Block neo, ở định dạng mảng block đã phân tích.
 * @param string                          $relative_position   Vị trí tương đối của các block được gắn hook.
 *                                                             Có thể là 'before', 'after', 'first_child', hoặc 'last_child'.
 * @param array                           $hooked_blocks       Mảng các loại block được gắn hook, nhóm theo block neo và vị trí tương đối.
 * @param WP_Block_Template|WP_Post|array $context             Template block, template part, hoặc pattern mà block neo thuộc về.
 * @return string Chuỗi rỗng.
 */
function set_ignored_hooked_blocks_metadata( &$parsed_anchor_block, $relative_position, $hooked_blocks, $context ) {
	$anchor_block_type  = $parsed_anchor_block['blockName'];
	$hooked_block_types = isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] )
		? $hooked_blocks[ $anchor_block_type ][ $relative_position ]
		: array();

	/** Bộ lọc này được ghi nhận trong wp-includes/blocks.php */
	$hooked_block_types = apply_filters( 'hooked_block_types', $hooked_block_types, $relative_position, $anchor_block_type, $context );
	if ( empty( $hooked_block_types ) ) {
		return '';
	}

	foreach ( $hooked_block_types as $index => $hooked_block_type ) {
		$parsed_hooked_block = array(
			'blockName'    => $hooked_block_type,
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerContent' => array(),
		);

		/** Bộ lọc này được ghi nhận trong wp-includes/blocks.php */
		$parsed_hooked_block = apply_filters( 'hooked_block', $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context );

		/** Bộ lọc này được ghi nhận trong wp-includes/blocks.php */
		$parsed_hooked_block = apply_filters( "hooked_block_{$hooked_block_type}", $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context );

		if ( null === $parsed_hooked_block ) {
			unset( $hooked_block_types[ $index ] );
		}
	}

	$previously_ignored_hooked_blocks = isset( $parsed_anchor_block['attrs']['metadata']['ignoredHookedBlocks'] )
		? $parsed_anchor_block['attrs']['metadata']['ignoredHookedBlocks']
		: array();

	$parsed_anchor_block['attrs']['metadata']['ignoredHookedBlocks'] = array_unique(
		array_merge(
			$previously_ignored_hooked_blocks,
			$hooked_block_types
		)
	);

	// Mã đánh dấu cho các block được gắn hook đã được tạo (trong `insert_hooked_blocks`).
	return '';
}

/**
 * Chạy thuật toán block được gắn hook trên nội dung đã cho.
 *
 * @since 6.6.0
 * @since 6.7.0 Thêm thuộc tính `theme` vào các block Template Part, ngay cả khi không có block được gắn hook nào được đăng ký.
 * @since 6.8.0 Tham số `$context` mặc định là `null`, trong trường hợp đó `get_post()` sẽ được gọi để sử dụng bài viết hiện tại làm ngữ cảnh.
 * @access private
 *
 * @param string                               $content  Nội dung đã tuần tự hóa.
 * @param WP_Block_Template|WP_Post|array|null $context  Template block, template part, đối tượng bài viết, hoặc pattern
 *                                                       mà các block thuộc về. Nếu đặt là `null`, `get_post()`
 *                                                       sẽ được gọi để sử dụng bài viết hiện tại làm ngữ cảnh.
 *                                                       Mặc định: `null`.
 * @param callable                             $callback Hàm sẽ được gọi cho mỗi block để tạo mã đánh dấu
 *                                                       cho danh sách các block được gắn hook vào nó.
 *                                                       Mặc định: 'insert_hooked_blocks'.
 * @return string Mã đánh dấu đã tuần tự hóa.
 */
function apply_block_hooks_to_content( $content, $context = null, $callback = 'insert_hooked_blocks' ) {
	// Mặc định sử dụng bài viết hiện tại nếu không có ngữ cảnh nào được cung cấp.
	if ( null === $context ) {
		$context = get_post();
	}

	$hooked_blocks = get_hooked_blocks();

	$before_block_visitor = '_inject_theme_attribute_in_template_part_block';
	$after_block_visitor  = null;
	if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
		$before_block_visitor = make_before_block_visitor( $hooked_blocks, $context, $callback );
		$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $context, $callback );
	}

	$block_allows_multiple_instances = array();
	/*
	 * Xóa các block được gắn hook khỏi `$hooked_block_types` nếu chúng có `multiple` đặt là false và
	 * đã có mặt trong `$content`.
	 */
	foreach ( $hooked_blocks as $anchor_block_type => $relative_positions ) {
		foreach ( $relative_positions as $relative_position => $hooked_block_types ) {
			foreach ( $hooked_block_types as $index => $hooked_block_type ) {
				$hooked_block_type_definition =
					WP_Block_Type_Registry::get_instance()->get_registered( $hooked_block_type );

				$block_allows_multiple_instances[ $hooked_block_type ] =
					block_has_support( $hooked_block_type_definition, 'multiple', true );

				if (
					! $block_allows_multiple_instances[ $hooked_block_type ] &&
					has_block( $hooked_block_type, $content )
				) {
					unset( $hooked_blocks[ $anchor_block_type ][ $relative_position ][ $index ] );
				}
			}
			if ( empty( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ) {
				unset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] );
			}
		}
		if ( empty( $hooked_blocks[ $anchor_block_type ] ) ) {
			unset( $hooked_blocks[ $anchor_block_type ] );
		}
	}

	/*
	 * Chúng ta cũng cần xử lý trường hợp block được gắn hook ban đầu không có mặt trong
	 * `$content` và chúng ta được phép chèn nó một lần -- nhưng không phải lần nữa.
	 */
	$suppress_single_instance_blocks = static function ( $hooked_block_types ) use ( &$block_allows_multiple_instances, $content ) {
		static $single_instance_blocks_present_in_content = array();
		foreach ( $hooked_block_types as $index => $hooked_block_type ) {
			if ( ! isset( $block_allows_multiple_instances[ $hooked_block_type ] ) ) {
				$hooked_block_type_definition =
					WP_Block_Type_Registry::get_instance()->get_registered( $hooked_block_type );

				$block_allows_multiple_instances[ $hooked_block_type ] =
					block_has_support( $hooked_block_type_definition, 'multiple', true );
			}

			if ( $block_allows_multiple_instances[ $hooked_block_type ] ) {
				continue;
			}

			// Block không cho phép nhiều thực thể, nên chúng ta cần kiểm tra xem nó đã có mặt chưa.
			if (
				in_array( $hooked_block_type, $single_instance_blocks_present_in_content, true ) ||
				has_block( $hooked_block_type, $content )
			) {
				unset( $hooked_block_types[ $index ] );
			} else {
				// Chúng ta có thể chèn block một lần, nhưng cần nhớ không chèn lại lần nữa.
				$single_instance_blocks_present_in_content[] = $hooked_block_type;
			}
		}
		return $hooked_block_types;
	};
	add_filter( 'hooked_block_types', $suppress_single_instance_blocks, PHP_INT_MAX );
	$content = traverse_and_serialize_blocks(
		parse_blocks( $content ),
		$before_block_visitor,
		$after_block_visitor
	);
	remove_filter( 'hooked_block_types', $suppress_single_instance_blocks, PHP_INT_MAX );

	return $content;
}

/**
 * Chạy thuật toán Block Hooks trên nội dung của đối tượng bài viết.
 *
 * Hàm này khác với `apply_block_hooks_to_content` ở chỗ nó tính đến
 * thông tin block được gắn hook bị bỏ qua từ metadata của bài viết.
 * Điều này đảm bảo rằng bất kỳ block nào được gắn hook là con đầu tiên hoặc cuối cùng
 * của block tương ứng với loại bài viết đều được xử lý chính xác.
 *
 * @since 6.8.0
 * @access private
 *
 * @param string       $content  Nội dung đã tuần tự hóa.
 * @param WP_Post|null $post     Đối tượng bài viết mà nội dung thuộc về. Nếu đặt là `null`,
 *                               `get_post()` sẽ được gọi để sử dụng bài viết hiện tại làm ngữ cảnh.
 *                               Mặc định: `null`.
 * @param callable     $callback Hàm sẽ được gọi cho mỗi block để tạo mã đánh dấu
 *                               cho danh sách các block được gắn hook vào nó.
 *                               Mặc định: 'insert_hooked_blocks'.
 * @return string Mã đánh dấu đã tuần tự hóa.
 */
function apply_block_hooks_to_content_from_post_object( $content, $post = null, $callback = 'insert_hooked_blocks' ) {
	// Mặc định sử dụng bài viết hiện tại nếu không có ngữ cảnh nào được cung cấp.
	if ( null === $post ) {
		$post = get_post();
	}

	if ( ! $post instanceof WP_Post ) {
		return apply_block_hooks_to_content( $content, $post, $callback );
	}

	/*
	 * Nếu nội dung được tạo bằng trình soạn thảo cổ điển hoặc sử dụng một block Classic đơn lẻ
	 * (`core/freeform`), nó có thể không chứa bất kỳ mã đánh dấu block nào.
	 * Tuy nhiên, chúng ta vẫn có thể cần chèn các block được gắn hook vào vị trí con đầu tiên hoặc
	 * con cuối cùng của block cha. Để có thể áp dụng thuật toán Block Hooks, chúng ta bọc
	 * nội dung trong một block bao ngoài `core/freeform`.
	 */
	if ( ! has_blocks( $content ) ) {
		$original_content = $content;

		$content_wrapped_in_classic_block = get_comment_delimited_block_content(
			'core/freeform',
			array(),
			$content
		);

		$content = $content_wrapped_in_classic_block;
	}

	$attributes = array();

	// Nếu ngữ cảnh là đối tượng bài viết, thông tin `ignoredHookedBlocks` được lưu trong post meta của nó.
	$ignored_hooked_blocks = get_post_meta( $post->ID, '_wp_ignored_hooked_blocks', true );
	if ( ! empty( $ignored_hooked_blocks ) ) {
		$ignored_hooked_blocks  = json_decode( $ignored_hooked_blocks, true );
		$attributes['metadata'] = array(
			'ignoredHookedBlocks' => $ignored_hooked_blocks,
		);
	}

	/*
	 * Chúng ta cần bọc nội dung trong một block bao ngoài tạm thời với metadata đó
	 * để thuật toán Block Hooks có thể chèn các block được gắn hook là con đầu tiên hoặc cuối cùng
	 * của block bao ngoài.
	 * Để thực hiện điều đó, chúng ta cần xác định loại block bao ngoài dựa trên loại bài viết.
	 */
	if ( 'wp_navigation' === $post->post_type ) {
		$wrapper_block_type = 'core/navigation';
	} elseif ( 'wp_block' === $post->post_type ) {
		$wrapper_block_type = 'core/block';
	} else {
		$wrapper_block_type = 'core/post-content';
	}

	$content = get_comment_delimited_block_content(
		$wrapper_block_type,
		$attributes,
		$content
	);

	/*
	 * Chúng ta cần tránh chèn bất kỳ block nào được gắn hook vào vị trí `before` và `after`
	 * của block bao ngoài tạm thời mà chúng ta tạo để bọc nội dung.
	 * Xem https://core.trac.wordpress.org/ticket/63287 để biết thêm chi tiết.
	 */
	$suppress_blocks_from_insertion_before_and_after_wrapper_block = static function ( $hooked_block_types, $relative_position, $anchor_block_type ) use ( $wrapper_block_type ) {
		if (
			$wrapper_block_type === $anchor_block_type &&
			in_array( $relative_position, array( 'before', 'after' ), true )
		) {
			return array();
		}
		return $hooked_block_types;
	};

	// Áp dụng Block Hooks.
	add_filter( 'hooked_block_types', $suppress_blocks_from_insertion_before_and_after_wrapper_block, PHP_INT_MAX, 3 );
	$content = apply_block_hooks_to_content( $content, $post, $callback );
	remove_filter( 'hooked_block_types', $suppress_blocks_from_insertion_before_and_after_wrapper_block, PHP_INT_MAX );

	// Cuối cùng, chúng ta cần xóa block bao ngoài tạm thời.
	$content = remove_serialized_parent_block( $content );

	// Nếu chúng ta đã bọc nội dung trong block `core/freeform`, chúng ta cũng cần xóa nó.
	if ( ! empty( $content_wrapped_in_classic_block ) ) {
		/*
		 * Chúng ta không thể đơn giản sử dụng remove_serialized_parent_block() ở đây,
		 * vì hàm đó giả định rằng block bao ngoài nằm ở cấp cao nhất.
		 * Tuy nhiên, giờ có thể có một block được gắn hook được chèn bên cạnh nó
		 * (là con đầu tiên hoặc cuối cùng của block cha).
		 */
		$content = str_replace( $content_wrapped_in_classic_block, $original_content, $content );
	}

	return $content;
}

/**
 * Nhận mã đánh dấu đã tuần tự hóa của block và các block con bên trong, trả về mã đánh dấu đã tuần tự hóa của các block con bên trong.
 *
 * @since 6.6.0
 * @access private
 *
 * @param string $serialized_block Mã đánh dấu đã tuần tự hóa của block và các block con bên trong.
 * @return string Mã đánh dấu đã tuần tự hóa của các block con bên trong.
 */
function remove_serialized_parent_block( $serialized_block ) {
	$start = strpos( $serialized_block, '-->' ) + strlen( '-->' );
	$end   = strrpos( $serialized_block, '<!--' );
	return substr( $serialized_block, $start, $end - $start );
}

/**
 * Nhận mã đánh dấu đã tuần tự hóa của block và các block con bên trong, trả về mã đánh dấu đã tuần tự hóa của block bao ngoài.
 *
 * @since 6.7.0
 * @access private
 *
 * @see remove_serialized_parent_block()
 *
 * @param string $serialized_block Mã đánh dấu đã tuần tự hóa của block và các block con bên trong.
 * @return string Mã đánh dấu đã tuần tự hóa của block bao ngoài.
 */
function extract_serialized_parent_block( $serialized_block ) {
	$start = strpos( $serialized_block, '-->' ) + strlen( '-->' );
	$end   = strrpos( $serialized_block, '<!--' );
	return substr( $serialized_block, 0, $start ) . substr( $serialized_block, $end );
}

/**
 * Cập nhật wp_postmeta với danh sách các block được gắn hook bị bỏ qua
 * trong đó các block con bên trong được lưu trữ dưới dạng nội dung bài viết.
 *
 * @since 6.6.0
 * @since 6.8.0 Hỗ trợ các loại bài viết không phải `wp_navigation`.
 * @access private
 *
 * @param stdClass $post Đối tượng bài viết.
 * @return stdClass Đối tượng bài viết đã cập nhật.
 */
function update_ignored_hooked_blocks_postmeta( $post ) {
	/*
	 * Trong trường hợp này người dùng có thể đã thử tạo đối tượng bài viết mới qua REST API.
	 * Khi đó chúng ta sẽ không có ID bài viết để làm việc và lưu trữ meta.
	 */
	if ( empty( $post->ID ) ) {
		return $post;
	}

	/*
	 * Bỏ qua việc tạo meta khi các đối tượng tiêu thụ cố ý cập nhật các trường cụ thể
	 * và bỏ qua việc cập nhật nội dung.
	 */
	if ( ! isset( $post->post_content ) ) {
		return $post;
	}

	/*
	 * Bỏ qua việc tạo meta nếu loại bài viết chưa được thiết lập.
	 */
	if ( ! isset( $post->post_type ) ) {
		return $post;
	}

	$attributes = array();

	$ignored_hooked_blocks = get_post_meta( $post->ID, '_wp_ignored_hooked_blocks', true );
	if ( ! empty( $ignored_hooked_blocks ) ) {
		$ignored_hooked_blocks  = json_decode( $ignored_hooked_blocks, true );
		$attributes['metadata'] = array(
			'ignoredHookedBlocks' => $ignored_hooked_blocks,
		);
	}

	if ( 'wp_navigation' === $post->post_type ) {
		$wrapper_block_type = 'core/navigation';
	} elseif ( 'wp_block' === $post->post_type ) {
		$wrapper_block_type = 'core/block';
	} else {
		$wrapper_block_type = 'core/post-content';
	}

	$markup = get_comment_delimited_block_content(
		$wrapper_block_type,
		$attributes,
		$post->post_content
	);

	$existing_post = get_post( $post->ID );
	// Gộp đối tượng bài viết hiện tại với đối tượng bài viết đã cập nhật để truyền cho thuật toán block hooks làm ngữ cảnh.
	$context          = (object) array_merge( (array) $existing_post, (array) $post );
	$context          = new WP_Post( $context ); // Convert to WP_Post object.
	$serialized_block = apply_block_hooks_to_content( $markup, $context, 'set_ignored_hooked_blocks_metadata' );
	$root_block       = parse_blocks( $serialized_block )[0];

	$ignored_hooked_blocks = isset( $root_block['attrs']['metadata']['ignoredHookedBlocks'] )
		? $root_block['attrs']['metadata']['ignoredHookedBlocks']
		: array();

	if ( ! empty( $ignored_hooked_blocks ) ) {
		$existing_ignored_hooked_blocks = get_post_meta( $post->ID, '_wp_ignored_hooked_blocks', true );
		if ( ! empty( $existing_ignored_hooked_blocks ) ) {
			$existing_ignored_hooked_blocks = json_decode( $existing_ignored_hooked_blocks, true );
			$ignored_hooked_blocks          = array_unique( array_merge( $ignored_hooked_blocks, $existing_ignored_hooked_blocks ) );
		}

		if ( ! isset( $post->meta_input ) ) {
			$post->meta_input = array();
		}
		$post->meta_input['_wp_ignored_hooked_blocks'] = json_encode( $ignored_hooked_blocks );
	}

	$post->post_content = remove_serialized_parent_block( $serialized_block );
	return $post;
}

/**
 * Trả về mã đánh dấu cho các block được gắn hook vào block neo đã cho ở vị trí tương đối cụ thể và sau đó
 * thêm danh sách loại block được gắn hook vào danh sách loại block được gắn hook bị bỏ qua của block neo.
 *
 * Hàm này chỉ dành cho sử dụng nội bộ.
 *
 * @since 6.6.0
 * @access private
 *
 * @param array                           $parsed_anchor_block Block neo, ở định dạng mảng block đã phân tích.
 * @param string                          $relative_position   Vị trí tương đối của các block được gắn hook.
 *                                                             Có thể là 'before', 'after', 'first_child', hoặc 'last_child'.
 * @param array                           $hooked_blocks       Mảng loại block được gắn hook, nhóm theo block neo và vị trí tương đối.
 * @param WP_Block_Template|WP_Post|array $context             Mẫu block, phần mẫu, hoặc pattern mà block neo thuộc về.
 * @return string
 */
function insert_hooked_blocks_and_set_ignored_hooked_blocks_metadata( &$parsed_anchor_block, $relative_position, $hooked_blocks, $context ) {
	$markup  = insert_hooked_blocks( $parsed_anchor_block, $relative_position, $hooked_blocks, $context );
	$markup .= set_ignored_hooked_blocks_metadata( $parsed_anchor_block, $relative_position, $hooked_blocks, $context );

	return $markup;
}

/**
 * Gắn hook vào phản hồi REST API cho endpoint Bài viết và thêm các block con bên trong đầu tiên và cuối cùng.
 *
 * @since 6.6.0
 * @since 6.8.0 Hỗ trợ các loại bài viết không phải `wp_navigation`.
 *
 * @param WP_REST_Response $response Đối tượng phản hồi.
 * @param WP_Post          $post     Đối tượng bài viết.
 * @return WP_REST_Response Đối tượng phản hồi.
 */
function insert_hooked_blocks_into_rest_response( $response, $post ) {
	if ( empty( $response->data['content']['raw'] ) ) {
		return $response;
	}

	$response->data['content']['raw'] = apply_block_hooks_to_content_from_post_object(
		$response->data['content']['raw'],
		$post,
		'insert_hooked_blocks_and_set_ignored_hooked_blocks_metadata'
	);

	// Nếu nội dung đã render trước đó là rỗng, chúng ta giữ nguyên như vậy.
	if ( empty( $response->data['content']['rendered'] ) ) {
		return $response;
	}

	// `apply_block_hooks_to_content` đã được gọi ở trên. Đảm bảo nó không được gọi lại như một bộ lọc.
	$priority = has_filter( 'the_content', 'apply_block_hooks_to_content_from_post_object' );
	if ( false !== $priority ) {
		remove_filter( 'the_content', 'apply_block_hooks_to_content_from_post_object', $priority );
	}

	/** This filter is documented in wp-includes/post-template.php */
	$response->data['content']['rendered'] = apply_filters(
		'the_content',
		$response->data['content']['raw']
	);

	// Khôi phục bộ lọc nếu nó đã được thiết lập ban đầu.
	if ( false !== $priority ) {
		add_filter( 'the_content', 'apply_block_hooks_to_content_from_post_object', $priority );
	}

	return $response;
}

/**
 * Trả về hàm chèn thuộc tính giao diện vào, và các block được gắn hook trước, một block đã cho.
 *
 * Hàm trả về có thể được sử dụng làm tham số `$pre_callback` cho `traverse_and_serialize_block(s)`,
 * nơi nó sẽ chèn thuộc tính `theme` vào tất cả các block Template Part, và thêm mã đánh dấu
 * cho bất kỳ block nào được gắn hook `before` block đã cho và là `first_child` của block cha tương ứng.
 *
 * Hàm này chỉ dành cho sử dụng nội bộ.
 *
 * @since 6.4.0
 * @since 6.5.0 Thêm tham số $callback.
 * @access private
 *
 * @param array                           $hooked_blocks Mảng các block được gắn hook vào block đã cho khác.
 * @param WP_Block_Template|WP_Post|array $context       Mẫu block, phần mẫu, đối tượng bài viết,
 *                                                       hoặc pattern mà các block thuộc về.
 * @param callable                        $callback      Hàm sẽ được gọi cho mỗi block để tạo
 *                                                       mã đánh dấu cho danh sách block được gắn hook vào nó.
 *                                                       Mặc định: 'insert_hooked_blocks'.
 * @return callable Hàm trả về mã đánh dấu đã tuần tự hóa cho block đã cho,
 *                  bao gồm mã đánh dấu cho bất kỳ block được gắn hook nào trước nó.
 */
function make_before_block_visitor( $hooked_blocks, $context, $callback = 'insert_hooked_blocks' ) {
	/**
	 * Chèn các block được gắn hook trước block đã cho, chèn thuộc tính `theme` vào block Template Part, và trả về mã đánh dấu đã tuần tự hóa.
	 *
	 * Nếu block hiện tại là block Template Part, chèn thuộc tính `theme`.
	 * Ngoài ra, thêm mã đánh dấu cho bất kỳ block nào được gắn hook `before` block đã cho và là
	 * `first_child` của block cha tương ứng, vào trước mã đánh dấu đã tuần tự hóa cho block đã cho.
	 *
	 * @param array $block        Block để chèn thuộc tính giao diện vào, và các block được gắn hook trước. Truyền theo tham chiếu.
	 * @param array $parent_block Block cha của block đã cho. Truyền theo tham chiếu. Mặc định null.
	 * @param array $prev         Block anh em trước của block đã cho. Mặc định null.
	 * @return string Mã đánh dấu đã tuần tự hóa cho block đã cho, với mã đánh dấu cho bất kỳ block được gắn hook nào được thêm vào trước.
	 */
	return function ( &$block, &$parent_block = null, $prev = null ) use ( $hooked_blocks, $context, $callback ) {
		_inject_theme_attribute_in_template_part_block( $block );

		$markup = '';

		if ( $parent_block && ! $prev ) {
			// Ứng cử viên cho việc chèn first-child.
			$markup .= call_user_func_array(
				$callback,
				array( &$parent_block, 'first_child', $hooked_blocks, $context )
			);
		}

		$markup .= call_user_func_array(
			$callback,
			array( &$block, 'before', $hooked_blocks, $context )
		);

		return $markup;
	};
}

/**
 * Trả về hàm chèn các block được gắn hook sau một block đã cho.
 *
 * Hàm trả về có thể được sử dụng làm tham số `$post_callback` cho `traverse_and_serialize_block(s)`,
 * nơi nó sẽ nối thêm mã đánh dấu cho bất kỳ block nào được gắn hook `after` block đã cho và là
 * `last_child` của block cha tương ứng.
 *
 * Hàm này chỉ dành cho sử dụng nội bộ.
 *
 * @since 6.4.0
 * @since 6.5.0 Thêm tham số $callback.
 * @access private
 *
 * @param array                           $hooked_blocks Mảng các block được gắn hook vào block khác.
 * @param WP_Block_Template|WP_Post|array $context       Mẫu block, phần mẫu, đối tượng bài viết,
 *                                                       hoặc pattern mà các block thuộc về.
 * @param callable                        $callback      Hàm sẽ được gọi cho mỗi block để tạo
 *                                                       mã đánh dấu cho danh sách block được gắn hook vào nó.
 *                                                       Mặc định: 'insert_hooked_blocks'.
 * @return callable Hàm trả về mã đánh dấu đã tuần tự hóa cho block đã cho,
 *                  bao gồm mã đánh dấu cho bất kỳ block được gắn hook nào sau nó.
 */
function make_after_block_visitor( $hooked_blocks, $context, $callback = 'insert_hooked_blocks' ) {
	/**
	 * Chèn các block được gắn hook sau block đã cho, và trả về mã đánh dấu đã tuần tự hóa.
	 *
	 * Nối thêm mã đánh dấu cho bất kỳ block nào được gắn hook `after` block đã cho và là
	 * `last_child` của block cha tương ứng, vào sau mã đánh dấu đã tuần tự hóa cho block đã cho.
	 *
	 * @param array $block        Block để chèn các block được gắn hook sau. Truyền theo tham chiếu.
	 * @param array $parent_block Block cha của block đã cho. Truyền theo tham chiếu. Mặc định null.
	 * @param array $next         Block anh em tiếp theo của block đã cho. Mặc định null.
	 * @return string Mã đánh dấu đã tuần tự hóa cho block đã cho, với mã đánh dấu cho bất kỳ block được gắn hook nào được nối thêm vào.
	 */
	return function ( &$block, &$parent_block = null, $next = null ) use ( $hooked_blocks, $context, $callback ) {
		$markup = call_user_func_array(
			$callback,
			array( &$block, 'after', $hooked_blocks, $context )
		);

		if ( $parent_block && ! $next ) {
			// Ứng cử viên cho việc chèn last-child.
			$markup .= call_user_func_array(
				$callback,
				array( &$parent_block, 'last_child', $hooked_blocks, $context )
			);
		}

		return $markup;
	};
}

/**
 * Với mảng thuộc tính đã cho, trả về chuỗi ở định dạng thuộc tính đã tuần tự hóa
 * chuẩn bị cho nội dung bài viết.
 *
 * Kết quả tuần tự hóa là chuỗi mã hóa JSON, với chuỗi thoát unicode
 * thay thế cho các ký tự có thể gây trở ngại khi nhúng
 * kết quả vào comment HTML.
 *
 * Hàm này phải tạo đầu ra đồng bộ với đầu ra của
 * hàm JavaScript serializeAttributes trong trình soạn thảo block để
 * đảm bảo hoạt động nhất quán giữa PHP và JavaScript.
 *
 * @since 5.3.1
 *
 * @param array $block_attributes Đối tượng thuộc tính.
 * @return string Thuộc tính đã tuần tự hóa.
 */
function serialize_block_attributes( $block_attributes ) {
	$encoded_attributes = wp_json_encode( $block_attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$encoded_attributes = preg_replace( '/--/', '\\u002d\\u002d', $encoded_attributes );
	$encoded_attributes = preg_replace( '/</', '\\u003c', $encoded_attributes );
	$encoded_attributes = preg_replace( '/>/', '\\u003e', $encoded_attributes );
	$encoded_attributes = preg_replace( '/&/', '\\u0026', $encoded_attributes );
	// Regex: /\\"/
	$encoded_attributes = preg_replace( '/\\\\"/', '\\u0022', $encoded_attributes );

	return $encoded_attributes;
}

/**
 * Trả về tên block để sử dụng cho tuần tự hóa. Hàm này sẽ xóa
 * namespace "core/" mặc định khỏi tên block.
 *
 * @since 5.3.1
 *
 * @param string|null $block_name Tùy chọn. Tên block gốc. Null nếu tên block không xác định,
 *                                ví dụ block Classic có tên được đặt là null. Mặc định null.
 * @return string Tên block để sử dụng cho tuần tự hóa.
 */
function strip_core_block_namespace( $block_name = null ) {
	if ( is_string( $block_name ) && str_starts_with( $block_name, 'core/' ) ) {
		return substr( $block_name, 5 );
	}

	return $block_name;
}

/**
 * Trả về nội dung của block, bao gồm các ký tự phân cách comment.
 *
 * @since 5.3.1
 *
 * @param string|null $block_name       Tên block. Null nếu tên block không xác định,
 *                                      ví dụ block Classic có tên được đặt là null.
 * @param array       $block_attributes Thuộc tính block.
 * @param string      $block_content    Nội dung lưu của block.
 * @return string Nội dung block được phân cách bằng comment.
 */
function get_comment_delimited_block_content( $block_name, $block_attributes, $block_content ) {
	if ( is_null( $block_name ) ) {
		return $block_content;
	}

	$serialized_block_name = strip_core_block_namespace( $block_name );
	$serialized_attributes = empty( $block_attributes ) ? '' : serialize_block_attributes( $block_attributes ) . ' ';

	if ( empty( $block_content ) ) {
		return sprintf( '<!-- wp:%s %s/-->', $serialized_block_name, $serialized_attributes );
	}

	return sprintf(
		'<!-- wp:%s %s-->%s<!-- /wp:%s -->',
		$serialized_block_name,
		$serialized_attributes,
		$block_content,
		$serialized_block_name
	);
}

/**
 * Trả về nội dung của block, bao gồm các ký tự phân cách comment, tuần tự hóa tất cả
 * thuộc tính từ block đã phân tích đã cho.
 *
 * Nên sử dụng khi chuẩn bị block để lưu vào nội dung bài viết.
 * Ưu tiên `render_block` khi chuẩn bị block để hiển thị. Khác với
 * `render_block`, hàm này không đánh giá `render_callback` của block, mà sẽ
 * giữ nguyên mã đánh dấu như đã phân tích.
 *
 * @since 5.3.1
 *
 * @param array $block {
 *     Mảng liên kết của một đối tượng block đã phân tích. Xem WP_Block_Parser_Block.
 *
 *     @type string   $blockName    Tên của block.
 *     @type array    $attrs        Thuộc tính từ các ký tự phân cách comment block.
 *     @type array[]  $innerBlocks  Danh sách các block con bên trong. Mảng các mảng có
 *                                  cùng cấu trúc với mảng này.
 *     @type string   $innerHTML    HTML bên trong các ký tự phân cách comment block.
 *     @type array    $innerContent Danh sách các đoạn chuỗi và điểm đánh dấu null nơi
 *                                  tìm thấy các block con bên trong.
 * }
 * @return string Chuỗi HTML đã render.
 */
function serialize_block( $block ) {
	$block_content = '';

	$index = 0;
	foreach ( $block['innerContent'] as $chunk ) {
		$block_content .= is_string( $chunk ) ? $chunk : serialize_block( $block['innerBlocks'][ $index++ ] );
	}

	if ( ! is_array( $block['attrs'] ) ) {
		$block['attrs'] = array();
	}

	return get_comment_delimited_block_content(
		$block['blockName'],
		$block['attrs'],
		$block_content
	);
}

/**
 * Trả về chuỗi nối của tổng hợp tuần tự hóa các block đã phân tích
 * đã cho.
 *
 * @since 5.3.1
 *
 * @param array[] $blocks {
 *     Mảng các cấu trúc block.
 *
 *     @type array ...$0 {
 *         Mảng liên kết của một đối tượng block đã phân tích. Xem WP_Block_Parser_Block.
 *
 *         @type string   $blockName    Tên của block.
 *         @type array    $attrs        Thuộc tính từ các ký tự phân cách comment block.
 *         @type array[]  $innerBlocks  Danh sách các block con bên trong. Mảng các mảng có
 *                                      cùng cấu trúc với mảng này.
 *         @type string   $innerHTML    HTML bên trong các ký tự phân cách comment block.
 *         @type array    $innerContent Danh sách các đoạn chuỗi và điểm đánh dấu null nơi
 *                                      tìm thấy các block con bên trong.
 *     }
 * }
 * @return string Chuỗi HTML đã render.
 */
function serialize_blocks( $blocks ) {
	return implode( '', array_map( 'serialize_block', $blocks ) );
}

/**
 * Duyệt cây block đã phân tích và áp dụng callback trước và sau khi tuần tự hóa.
 *
 * Duyệt đệ quy block và các block con bên trong, áp dụng hai callback được cung cấp làm
 * tham số, callback đầu tiên trước khi tuần tự hóa block, và callback thứ hai sau khi tuần tự hóa.
 * Nếu callback nào trả về giá trị chuỗi, nó sẽ được thêm vào trước và nối thêm vào sau mã đánh dấu
 * block đã tuần tự hóa tương ứng.
 *
 * Các callback sẽ nhận tham chiếu đến block hiện tại làm tham số đầu tiên, để chúng
 * cũng có thể sửa đổi nó, và block cha của block hiện tại làm tham số thứ hai. Cuối cùng,
 * `$pre_callback` nhận block trước đó, trong khi `$post_callback` nhận
 * block tiếp theo làm tham số thứ ba.
 *
 * Các block đã tuần tự hóa được trả về bao gồm các ký tự phân cách comment, với tất cả thuộc tính đã tuần tự hóa.
 *
 * Hàm này nên được sử dụng khi cần sửa đổi block đã lưu, hoặc chèn mã đánh dấu
 * vào giá trị trả về. Ưu tiên `serialize_block` khi chuẩn bị block để lưu vào nội dung bài viết.
 *
 * Hàm này chỉ dành cho sử dụng nội bộ.
 *
 * @since 6.4.0
 * @access private
 *
 * @see serialize_block()
 *
 * @param array    $block         Mảng liên kết của một đối tượng block đã phân tích. Xem WP_Block_Parser_Block.
 * @param callable $pre_callback  Callback để chạy trên mỗi block trong cây trước khi nó được duyệt và tuần tự hóa.
 *                                Được gọi với các tham số sau: &$block, $parent_block, $previous_block.
 *                                Giá trị chuỗi trả về sẽ được thêm vào trước mã đánh dấu block đã tuần tự hóa.
 * @param callable $post_callback Callback để chạy trên mỗi block trong cây sau khi nó được duyệt và tuần tự hóa.
 *                                Được gọi với các tham số sau: &$block, $parent_block, $next_block.
 *                                Giá trị chuỗi trả về sẽ được nối thêm vào sau mã đánh dấu block đã tuần tự hóa.
 * @return string Mã đánh dấu block đã tuần tự hóa.
 */
function traverse_and_serialize_block( $block, $pre_callback = null, $post_callback = null ) {
	$block_content = '';
	$block_index   = 0;

	foreach ( $block['innerContent'] as $chunk ) {
		if ( is_string( $chunk ) ) {
			$block_content .= $chunk;
		} else {
			$inner_block = $block['innerBlocks'][ $block_index ];

			if ( is_callable( $pre_callback ) ) {
				$prev = 0 === $block_index
					? null
					: $block['innerBlocks'][ $block_index - 1 ];

				$block_content .= call_user_func_array(
					$pre_callback,
					array( &$inner_block, &$block, $prev )
				);
			}

			if ( is_callable( $post_callback ) ) {
				$next = count( $block['innerBlocks'] ) - 1 === $block_index
					? null
					: $block['innerBlocks'][ $block_index + 1 ];

				$post_markup = call_user_func_array(
					$post_callback,
					array( &$inner_block, &$block, $next )
				);
			}

			$block_content .= traverse_and_serialize_block( $inner_block, $pre_callback, $post_callback );
			$block_content .= isset( $post_markup ) ? $post_markup : '';

			++$block_index;
		}
	}

	if ( ! is_array( $block['attrs'] ) ) {
		$block['attrs'] = array();
	}

	return get_comment_delimited_block_content(
		$block['blockName'],
		$block['attrs'],
		$block_content
	);
}

/**
 * Thay thế các pattern trong cây block bằng nội dung của chúng.
 *
 * @since 6.6.0
 *
 * @param array $blocks Mảng các block.
 *
 * @return array Mảng các block với pattern được thay thế bằng nội dung của chúng.
 */
function resolve_pattern_blocks( $blocks ) {
	static $inner_content;
	// Theo dõi các tham chiếu đã thấy để tránh vòng lặp vô hạn.
	static $seen_refs = array();
	$i                = 0;
	while ( $i < count( $blocks ) ) {
		if ( 'core/pattern' === $blocks[ $i ]['blockName'] ) {
			$attrs = $blocks[ $i ]['attrs'];

			if ( empty( $attrs['slug'] ) ) {
				++$i;
				continue;
			}

			$slug = $attrs['slug'];

			if ( isset( $seen_refs[ $slug ] ) ) {
				// Bỏ qua các pattern đệ quy.
				array_splice( $blocks, $i, 1 );
				continue;
			}

			$registry = WP_Block_Patterns_Registry::get_instance();
			$pattern  = $registry->get_registered( $slug );

			// Bỏ qua các pattern không xác định.
			if ( ! $pattern ) {
				++$i;
				continue;
			}

			$blocks_to_insert   = parse_blocks( $pattern['content'] );
			$seen_refs[ $slug ] = true;
			$prev_inner_content = $inner_content;
			$inner_content      = null;
			$blocks_to_insert   = resolve_pattern_blocks( $blocks_to_insert );
			$inner_content      = $prev_inner_content;
			unset( $seen_refs[ $slug ] );
			array_splice( $blocks, $i, 1, $blocks_to_insert );

			// Nếu chúng ta có nội dung bên trong, chúng ta cần chèn null vào
			// mảng nội dung bên trong, nếu không serialize_blocks sẽ bỏ qua
			// các block.
			if ( $inner_content ) {
				$null_indices  = array_keys( $inner_content, null, true );
				$content_index = $null_indices[ $i ];
				$nulls         = array_fill( 0, count( $blocks_to_insert ), null );
				array_splice( $inner_content, $content_index, 1, $nulls );
			}

			// Bỏ qua các block đã chèn.
			$i += count( $blocks_to_insert );
		} else {
			if ( ! empty( $blocks[ $i ]['innerBlocks'] ) ) {
				$prev_inner_content           = $inner_content;
				$inner_content                = $blocks[ $i ]['innerContent'];
				$blocks[ $i ]['innerBlocks']  = resolve_pattern_blocks(
					$blocks[ $i ]['innerBlocks']
				);
				$blocks[ $i ]['innerContent'] = $inner_content;
				$inner_content                = $prev_inner_content;
			}
			++$i;
		}
	}
	return $blocks;
}

/**
 * Với mảng các cây block đã phân tích, áp dụng callback trước và sau khi tuần tự hóa chúng và
 * trả về đầu ra nối của chúng.
 *
 * Duyệt đệ quy các block và các block con bên trong, áp dụng hai callback được cung cấp làm
 * tham số, callback đầu tiên trước khi tuần tự hóa block, và callback thứ hai sau khi tuần tự hóa.
 * Nếu callback nào trả về giá trị chuỗi, nó sẽ được thêm vào trước và nối thêm vào sau mã đánh dấu
 * block đã tuần tự hóa tương ứng.
 *
 * Các callback sẽ nhận tham chiếu đến block hiện tại làm tham số đầu tiên, để chúng
 * cũng có thể sửa đổi nó, và block cha của block hiện tại làm tham số thứ hai. Cuối cùng,
 * `$pre_callback` nhận block trước đó, trong khi `$post_callback` nhận
 * block tiếp theo làm tham số thứ ba.
 *
 * Các block đã tuần tự hóa được trả về bao gồm các ký tự phân cách comment, với tất cả thuộc tính đã tuần tự hóa.
 *
 * Hàm này nên được sử dụng khi cần sửa đổi block đã lưu, hoặc chèn mã đánh dấu
 * vào giá trị trả về. Ưu tiên `serialize_blocks` khi chuẩn bị block để lưu vào nội dung bài viết.
 *
 * Hàm này chỉ dành cho sử dụng nội bộ.
 *
 * @since 6.4.0
 * @access private
 *
 * @see serialize_blocks()
 *
 * @param array[]  $blocks        Mảng các block đã phân tích. Xem WP_Block_Parser_Block.
 * @param callable $pre_callback  Callback để chạy trên mỗi block trong cây trước khi nó được duyệt và tuần tự hóa.
 *                                Được gọi với các tham số sau: &$block, $parent_block, $previous_block.
 *                                Giá trị chuỗi trả về sẽ được thêm vào trước mã đánh dấu block đã tuần tự hóa.
 * @param callable $post_callback Callback để chạy trên mỗi block trong cây sau khi nó được duyệt và tuần tự hóa.
 *                                Được gọi với các tham số sau: &$block, $parent_block, $next_block.
 *                                Giá trị chuỗi trả về sẽ được nối thêm vào sau mã đánh dấu block đã tuần tự hóa.
 * @return string Mã đánh dấu block đã tuần tự hóa.
 */
function traverse_and_serialize_blocks( $blocks, $pre_callback = null, $post_callback = null ) {
	$result       = '';
	$parent_block = null; // Ở cấp cao nhất, không có block cha để truyền vào callback; tuy nhiên callback mong đợi một tham chiếu.

	$pre_callback_is_callable  = is_callable( $pre_callback );
	$post_callback_is_callable = is_callable( $post_callback );

	foreach ( $blocks as $index => $block ) {
		if ( $pre_callback_is_callable ) {
			$prev = 0 === $index
				? null
				: $blocks[ $index - 1 ];

			$result .= call_user_func_array(
				$pre_callback,
				array( &$block, &$parent_block, $prev )
			);
		}

		if ( $post_callback_is_callable ) {
			$next = count( $blocks ) - 1 === $index
				? null
				: $blocks[ $index + 1 ];

			$post_markup = call_user_func_array(
				$post_callback,
				array( &$block, &$parent_block, $next )
			);
		}

		$result .= traverse_and_serialize_block( $block, $pre_callback, $post_callback );
		$result .= isset( $post_markup ) ? $post_markup : '';
	}

	return $result;
}

/**
 * Lọc và làm sạch nội dung block để loại bỏ HTML không được phép
 * khỏi các giá trị thuộc tính block đã phân tích.
 *
 * @since 5.3.1
 *
 * @param string         $text              Văn bản có thể chứa nội dung block.
 * @param array[]|string $allowed_html      Tùy chọn. Mảng các phần tử và thuộc tính HTML được phép,
 *                                          hoặc tên ngữ cảnh như 'post'. Xem wp_kses_allowed_html()
 *                                          để biết danh sách các tên ngữ cảnh được chấp nhận. Mặc định 'post'.
 * @param string[]       $allowed_protocols Tùy chọn. Mảng các giao thức URL được phép.
 *                                          Mặc định là kết quả của wp_allowed_protocols().
 * @return string Kết quả nội dung đã lọc và làm sạch.
 */
function filter_block_content( $text, $allowed_html = 'post', $allowed_protocols = array() ) {
	$result = '';

	if ( str_contains( $text, '<!--' ) && str_contains( $text, '--->' ) ) {
		$text = preg_replace_callback( '%<!--(.*?)--->%', '_filter_block_content_callback', $text );
	}

	$blocks = parse_blocks( $text );
	foreach ( $blocks as $block ) {
		$block   = filter_block_kses( $block, $allowed_html, $allowed_protocols );
		$result .= serialize_block( $block );
	}

	return $result;
}

/**
 * Callback được sử dụng cho thay thế biểu thức chính quy trong filter_block_content().
 *
 * @since 6.2.1
 * @access private
 *
 * @param array $matches Mảng các kết quả khớp của preg_replace_callback.
 * @return string Chuỗi thay thế.
 */
function _filter_block_content_callback( $matches ) {
	return '<!--' . rtrim( $matches[1], '-' ) . '-->';
}

/**
 * Lọc và làm sạch block đã phân tích để loại bỏ HTML không được phép
 * khỏi các giá trị thuộc tính block.
 *
 * @since 5.3.1
 *
 * @param WP_Block_Parser_Block $block             Đối tượng block đã phân tích.
 * @param array[]|string        $allowed_html      Mảng các phần tử và thuộc tính HTML được phép,
 *                                                 hoặc tên ngữ cảnh như 'post'. Xem wp_kses_allowed_html()
 *                                                 để biết danh sách các tên ngữ cảnh được chấp nhận.
 * @param string[]              $allowed_protocols Tùy chọn. Mảng các giao thức URL được phép.
 *                                                 Mặc định là kết quả của wp_allowed_protocols().
 * @return array Kết quả đối tượng block đã lọc và làm sạch.
 */
function filter_block_kses( $block, $allowed_html, $allowed_protocols = array() ) {
	$block['attrs'] = filter_block_kses_value( $block['attrs'], $allowed_html, $allowed_protocols, $block );

	if ( is_array( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $i => $inner_block ) {
			$block['innerBlocks'][ $i ] = filter_block_kses( $inner_block, $allowed_html, $allowed_protocols );
		}
	}

	return $block;
}

/**
 * Lọc và làm sạch giá trị thuộc tính block đã phân tích để loại bỏ
 * HTML không được phép.
 *
 * @since 5.3.1
 * @since 6.5.5 Thêm tham số `$block_context`.
 *
 * @param string[]|string $value             Giá trị thuộc tính cần lọc.
 * @param array[]|string  $allowed_html      Mảng các phần tử và thuộc tính HTML được phép,
 *                                           hoặc tên ngữ cảnh như 'post'. Xem wp_kses_allowed_html()
 *                                           để biết danh sách các tên ngữ cảnh được chấp nhận.
 * @param string[]        $allowed_protocols Tùy chọn. Mảng các giao thức URL được phép.
 *                                           Mặc định là kết quả của wp_allowed_protocols().
 * @param array           $block_context     Tùy chọn. Block mà thuộc tính thuộc về, ở định dạng mảng block đã phân tích.
 * @return string[]|string Kết quả đã lọc và làm sạch.
 */
function filter_block_kses_value( $value, $allowed_html, $allowed_protocols = array(), $block_context = null ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $inner_value ) {
			$filtered_key   = filter_block_kses_value( $key, $allowed_html, $allowed_protocols, $block_context );
			$filtered_value = filter_block_kses_value( $inner_value, $allowed_html, $allowed_protocols, $block_context );

			if ( isset( $block_context['blockName'] ) && 'core/template-part' === $block_context['blockName'] ) {
				$filtered_value = filter_block_core_template_part_attributes( $filtered_value, $filtered_key, $allowed_html );
			}
			if ( $filtered_key !== $key ) {
				unset( $value[ $key ] );
			}

			$value[ $filtered_key ] = $filtered_value;
		}
	} elseif ( is_string( $value ) ) {
		return wp_kses( $value, $allowed_html, $allowed_protocols );
	}

	return $value;
}

/**
 * Làm sạch giá trị thuộc tính `tagName` của block Template Part.
 *
 * @since 6.5.5
 *
 * @param string         $attribute_value Giá trị thuộc tính cần lọc.
 * @param string         $attribute_name  Tên thuộc tính.
 * @param array[]|string $allowed_html    Mảng các phần tử và thuộc tính HTML được phép,
 *                                        hoặc tên ngữ cảnh như 'post'. Xem wp_kses_allowed_html()
 *                                        để biết danh sách các tên ngữ cảnh được chấp nhận.
 * @return string Giá trị thuộc tính đã làm sạch.
 */
function filter_block_core_template_part_attributes( $attribute_value, $attribute_name, $allowed_html ) {
	if ( empty( $attribute_value ) || 'tagName' !== $attribute_name ) {
		return $attribute_value;
	}
	if ( ! is_array( $allowed_html ) ) {
		$allowed_html = wp_kses_allowed_html( $allowed_html );
	}
	return isset( $allowed_html[ $attribute_value ] ) ? $attribute_value : '';
}

/**
 * Phân tích các block từ chuỗi nội dung, và render những block phù hợp cho tóm tắt.
 *
 * Vì tóm tắt nên là một chuỗi văn bản ngắn liên quan đến toàn bộ nội dung bài viết,
 * hàm này render các block có khả năng chứa văn bản như vậy nhất.
 *
 * @since 5.0.0
 *
 * @param string $content Nội dung cần phân tích.
 * @return string Nội dung đã phân tích và lọc.
 */
function excerpt_remove_blocks( $content ) {
	if ( ! has_blocks( $content ) ) {
		return $content;
	}

	$allowed_inner_blocks = array(
		// Các block Classic có blockName được đặt là null.
		null,
		'core/freeform',
		'core/heading',
		'core/html',
		'core/list',
		'core/media-text',
		'core/paragraph',
		'core/preformatted',
		'core/pullquote',
		'core/quote',
		'core/table',
		'core/verse',
	);

	$allowed_wrapper_blocks = array(
		'core/columns',
		'core/column',
		'core/group',
	);

	/**
	 * Lọc danh sách các block có thể được sử dụng làm block bao ngoài, cho phép
	 * tạo tóm tắt từ `innerBlocks` của các block bao ngoài này.
	 *
	 * @since 5.8.0
	 *
	 * @param string[] $allowed_wrapper_blocks Danh sách tên các block bao ngoài được phép.
	 */
	$allowed_wrapper_blocks = apply_filters( 'excerpt_allowed_wrapper_blocks', $allowed_wrapper_blocks );

	$allowed_blocks = array_merge( $allowed_inner_blocks, $allowed_wrapper_blocks );

	/**
	 * Lọc danh sách các block có thể đóng góp vào tóm tắt.
	 *
	 * Nếu một block động được thêm vào danh sách này, nó không được tạo ra
	 * tóm tắt khác, vì điều này sẽ gây ra vòng lặp vô hạn.
	 *
	 * @since 5.0.0
	 *
	 * @param string[] $allowed_blocks Danh sách tên các block được phép.
	 */
	$allowed_blocks = apply_filters( 'excerpt_allowed_blocks', $allowed_blocks );
	$blocks         = parse_blocks( $content );
	$output         = '';

	foreach ( $blocks as $block ) {
		if ( in_array( $block['blockName'], $allowed_blocks, true ) ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				if ( in_array( $block['blockName'], $allowed_wrapper_blocks, true ) ) {
					$output .= _excerpt_render_inner_blocks( $block, $allowed_blocks );
					continue;
				}

				// Bỏ qua block nếu nó có các block con bên trong không được phép hoặc lồng nhau.
				foreach ( $block['innerBlocks'] as $inner_block ) {
					if (
						! in_array( $inner_block['blockName'], $allowed_inner_blocks, true ) ||
						! empty( $inner_block['innerBlocks'] )
					) {
						continue 2;
					}
				}
			}

			$output .= render_block( $block );
		}
	}

	return $output;
}

/**
 * Phân tích mã đánh dấu chú thích cuối trang từ chuỗi nội dung,
 * và render những phần phù hợp cho tóm tắt.
 *
 * @since 6.3.0
 *
 * @param string $content Nội dung cần phân tích.
 * @return string Nội dung đã phân tích và lọc.
 */
function excerpt_remove_footnotes( $content ) {
	if ( ! str_contains( $content, 'data-fn=' ) ) {
		return $content;
	}

	return preg_replace(
		'_<sup data-fn="[^"]+" class="[^"]+">\s*<a href="[^"]+" id="[^"]+">\d+</a>\s*</sup>_',
		'',
		$content
	);
}

/**
 * Render các block con bên trong từ các block bao ngoài được phép
 * để tạo tóm tắt.
 *
 * @since 5.8.0
 * @access private
 *
 * @param array $parsed_block   Block đã phân tích.
 * @param array $allowed_blocks Danh sách các block con bên trong được phép.
 * @return string Các block con bên trong đã render.
 */
function _excerpt_render_inner_blocks( $parsed_block, $allowed_blocks ) {
	$output = '';

	foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
		if ( ! in_array( $inner_block['blockName'], $allowed_blocks, true ) ) {
			continue;
		}

		if ( empty( $inner_block['innerBlocks'] ) ) {
			$output .= render_block( $inner_block );
		} else {
			$output .= _excerpt_render_inner_blocks( $inner_block, $allowed_blocks );
		}
	}

	return $output;
}

/**
 * Render một block đơn lẻ thành chuỗi HTML.
 *
 * @since 5.0.0
 *
 * @global WP_Post $post Bài viết cần chỉnh sửa.
 *
 * @param array $parsed_block {
 *     Mảng liên kết của block đang được render. Xem WP_Block_Parser_Block.
 *
 *     @type string   $blockName    Tên của block.
 *     @type array    $attrs        Thuộc tính từ các ký tự phân cách comment block.
 *     @type array[]  $innerBlocks  Danh sách các block con bên trong. Mảng các mảng có
 *                                  cùng cấu trúc với mảng này.
 *     @type string   $innerHTML    HTML bên trong các ký tự phân cách comment block.
 *     @type array    $innerContent Danh sách các đoạn chuỗi và điểm đánh dấu null nơi
 *                                  tìm thấy các block con bên trong.
 * }
 * @return string Chuỗi HTML đã render.
 */
function render_block( $parsed_block ) {
	global $post;
	$parent_block = null;

	/**
	 * Allows render_block() to be short-circuited, by returning a non-null value.
	 *
	 * @since 5.1.0
	 * @since 5.9.0 The `$parent_block` parameter was added.
	 *
	 * @param string|null   $pre_render   The pre-rendered content. Default null.
	 * @param array         $parsed_block {
	 *     An associative array of the block being rendered. See WP_Block_Parser_Block.
	 *
	 *     @type string   $blockName    Name of block.
	 *     @type array    $attrs        Attributes from block comment delimiters.
	 *     @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
	 *                                  have the same structure as this one.
	 *     @type string   $innerHTML    HTML from inside block comment delimiters.
	 *     @type array    $innerContent List of string fragments and null markers where
	 *                                  inner blocks were found.
	 * }
	 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 */
	$pre_render = apply_filters( 'pre_render_block', null, $parsed_block, $parent_block );
	if ( ! is_null( $pre_render ) ) {
		return $pre_render;
	}

	$source_block = $parsed_block;

	/**
	 * Filters the block being rendered in render_block(), before it's processed.
	 *
	 * @since 5.1.0
	 * @since 5.9.0 The `$parent_block` parameter was added.
	 *
	 * @param array         $parsed_block {
	 *     An associative array of the block being rendered. See WP_Block_Parser_Block.
	 *
	 *     @type string   $blockName    Name of block.
	 *     @type array    $attrs        Attributes from block comment delimiters.
	 *     @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
	 *                                  have the same structure as this one.
	 *     @type string   $innerHTML    HTML from inside block comment delimiters.
	 *     @type array    $innerContent List of string fragments and null markers where
	 *                                  inner blocks were found.
	 * }
	 * @param array         $source_block {
	 *     An un-modified copy of `$parsed_block`, as it appeared in the source content.
	 *     See WP_Block_Parser_Block.
	 *
	 *     @type string   $blockName    Name of block.
	 *     @type array    $attrs        Attributes from block comment delimiters.
	 *     @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
	 *                                  have the same structure as this one.
	 *     @type string   $innerHTML    HTML from inside block comment delimiters.
	 *     @type array    $innerContent List of string fragments and null markers where
	 *                                  inner blocks were found.
	 * }
	 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 */
	$parsed_block = apply_filters( 'render_block_data', $parsed_block, $source_block, $parent_block );

	$context = array();

	if ( $post instanceof WP_Post ) {
		$context['postId'] = $post->ID;

		/*
		 * The `postType` context is largely unnecessary server-side, since the ID
		 * is usually sufficient on its own. That being said, since a block's
		 * manifest is expected to be shared between the server and the client,
		 * it should be included to consistently fulfill the expectation.
		 */
		$context['postType'] = $post->post_type;
	}

	/**
	 * Filters the default context provided to a rendered block.
	 *
	 * @since 5.5.0
	 * @since 5.9.0 The `$parent_block` parameter was added.
	 *
	 * @param array         $context      Default context.
	 * @param array         $parsed_block {
	 *     An associative array of the block being rendered. See WP_Block_Parser_Block.
	 *
	 *     @type string   $blockName    Name of block.
	 *     @type array    $attrs        Attributes from block comment delimiters.
	 *     @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
	 *                                  have the same structure as this one.
	 *     @type string   $innerHTML    HTML from inside block comment delimiters.
	 *     @type array    $innerContent List of string fragments and null markers where
	 *                                  inner blocks were found.
	 * }
	 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 */
	$context = apply_filters( 'render_block_context', $context, $parsed_block, $parent_block );

	$block = new WP_Block( $parsed_block, $context );

	return $block->render();
}

/**
 * Parses blocks out of a content string.
 *
 * @since 5.0.0
 *
 * @param string $content Post content.
 * @return array[] {
 *     Array of block structures.
 *
 *     @type array ...$0 {
 *         An associative array of a single parsed block object. See WP_Block_Parser_Block.
 *
 *         @type string   $blockName    Name of block.
 *         @type array    $attrs        Attributes from block comment delimiters.
 *         @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
 *                                      have the same structure as this one.
 *         @type string   $innerHTML    HTML from inside block comment delimiters.
 *         @type array    $innerContent List of string fragments and null markers where
 *                                      inner blocks were found.
 *     }
 * }
 */
function parse_blocks( $content ) {
	/**
	 * Filter to allow plugins to replace the server-side block parser.
	 *
	 * @since 5.0.0
	 *
	 * @param string $parser_class Name of block parser class.
	 */
	$parser_class = apply_filters( 'block_parser_class', 'WP_Block_Parser' );

	$parser = new $parser_class();
	return $parser->parse( $content );
}

/**
 * Parses dynamic blocks out of `post_content` and re-renders them.
 *
 * @since 5.0.0
 *
 * @param string $content Post content.
 * @return string Updated post content.
 */
function do_blocks( $content ) {
	$blocks                = parse_blocks( $content );
	$top_level_block_count = count( $blocks );
	$output                = '';

	/**
	 * Parsed blocks consist of a list of top-level blocks. Those top-level
	 * blocks may themselves contain nested inner blocks. However, every
	 * top-level block is rendered independently, meaning there are no data
	 * dependencies between them.
	 *
	 * Ideally, therefore, the parser would only need to parse one complete
	 * top-level block at a time, render it, and move on. Unfortunately, this
	 * is not possible with {@see \parse_blocks()} because it must parse the
	 * entire given document at once.
	 *
	 * While the current implementation prevents this optimization, it’s still
	 * possible to reduce the peak memory use when calls to `render_block()`
	 * on those top-level blocks are memory-heavy (which many of them are).
	 * By setting each parsed block to `NULL` after rendering it, any memory
	 * allocated during the render will be freed and reused for the next block.
	 * Before making this change, that memory was retained and would lead to
	 * out-of-memory crashes for certain posts that now run with this change.
	 */
	for ( $i = 0; $i < $top_level_block_count; $i++ ) {
		$output      .= render_block( $blocks[ $i ] );
		$blocks[ $i ] = null;
	}

	// If there are blocks in this content, we shouldn't run wpautop() on it later.
	$priority = has_filter( 'the_content', 'wpautop' );
	if ( false !== $priority && doing_filter( 'the_content' ) && has_blocks( $content ) ) {
		remove_filter( 'the_content', 'wpautop', $priority );
		add_filter( 'the_content', '_restore_wpautop_hook', $priority + 1 );
	}

	return $output;
}

/**
 * If do_blocks() needs to remove wpautop() from the `the_content` filter, this re-adds it afterwards,
 * for subsequent `the_content` usage.
 *
 * @since 5.0.0
 * @access private
 *
 * @param string $content The post content running through this filter.
 * @return string The unmodified content.
 */
function _restore_wpautop_hook( $content ) {
	$current_priority = has_filter( 'the_content', '_restore_wpautop_hook' );

	add_filter( 'the_content', 'wpautop', $current_priority - 1 );
	remove_filter( 'the_content', '_restore_wpautop_hook', $current_priority );

	return $content;
}

/**
 * Returns the current version of the block format that the content string is using.
 *
 * If the string doesn't contain blocks, it returns 0.
 *
 * @since 5.0.0
 *
 * @param string $content Content to test.
 * @return int The block format version is 1 if the content contains one or more blocks, 0 otherwise.
 */
function block_version( $content ) {
	return has_blocks( $content ) ? 1 : 0;
}

/**
 * Registers a new block style.
 *
 * @since 5.3.0
 * @since 6.6.0 Added support for registering styles for multiple block types.
 *
 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-styles/
 *
 * @param string|string[] $block_name       Block type name including namespace or array of namespaced block type names.
 * @param array           $style_properties Array containing the properties of the style name, label,
 *                                          style_handle (name of the stylesheet to be enqueued),
 *                                          inline_style (string containing the CSS to be added),
 *                                          style_data (theme.json-like array to generate CSS from).
 *                                          See WP_Block_Styles_Registry::register().
 * @return bool True if the block style was registered with success and false otherwise.
 */
function register_block_style( $block_name, $style_properties ) {
	return WP_Block_Styles_Registry::get_instance()->register( $block_name, $style_properties );
}

/**
 * Unregisters a block style.
 *
 * @since 5.3.0
 *
 * @param string $block_name       Block type name including namespace.
 * @param string $block_style_name Block style name.
 * @return bool True if the block style was unregistered with success and false otherwise.
 */
function unregister_block_style( $block_name, $block_style_name ) {
	return WP_Block_Styles_Registry::get_instance()->unregister( $block_name, $block_style_name );
}

/**
 * Checks whether the current block type supports the feature requested.
 *
 * @since 5.8.0
 * @since 6.4.0 The `$feature` parameter now supports a string.
 *
 * @param WP_Block_Type $block_type    Block type to check for support.
 * @param string|array  $feature       Feature slug, or path to a specific feature to check support for.
 * @param mixed         $default_value Optional. Fallback value for feature support. Default false.
 * @return bool Whether the feature is supported.
 */
function block_has_support( $block_type, $feature, $default_value = false ) {
	$block_support = $default_value;
	if ( $block_type instanceof WP_Block_Type ) {
		if ( is_array( $feature ) && count( $feature ) === 1 ) {
			$feature = $feature[0];
		}

		if ( is_array( $feature ) ) {
			$block_support = _wp_array_get( $block_type->supports, $feature, $default_value );
		} elseif ( isset( $block_type->supports[ $feature ] ) ) {
			$block_support = $block_type->supports[ $feature ];
		}
	}

	return true === $block_support || is_array( $block_support );
}

/**
 * Converts typography keys declared under `supports.*` to `supports.typography.*`.
 *
 * Displays a `_doing_it_wrong()` notice when a block using the older format is detected.
 *
 * @since 5.8.0
 *
 * @param array $metadata Metadata for registering a block type.
 * @return array Filtered metadata for registering a block type.
 */
function wp_migrate_old_typography_shape( $metadata ) {
	if ( ! isset( $metadata['supports'] ) ) {
		return $metadata;
	}

	$typography_keys = array(
		'__experimentalFontFamily',
		'__experimentalFontStyle',
		'__experimentalFontWeight',
		'__experimentalLetterSpacing',
		'__experimentalTextDecoration',
		'__experimentalTextTransform',
		'fontSize',
		'lineHeight',
	);

	foreach ( $typography_keys as $typography_key ) {
		$support_for_key = isset( $metadata['supports'][ $typography_key ] ) ? $metadata['supports'][ $typography_key ] : null;

		if ( null !== $support_for_key ) {
			_doing_it_wrong(
				'register_block_type_from_metadata()',
				sprintf(
					/* translators: 1: Block type, 2: Typography supports key, e.g: fontSize, lineHeight, etc. 3: block.json, 4: Old metadata key, 5: New metadata key. */
					__( 'Block "%1$s" is declaring %2$s support in %3$s file under %4$s. %2$s support is now declared under %5$s.' ),
					$metadata['name'],
					"<code>$typography_key</code>",
					'<code>block.json</code>',
					"<code>supports.$typography_key</code>",
					"<code>supports.typography.$typography_key</code>"
				),
				'5.8.0'
			);

			_wp_array_set( $metadata['supports'], array( 'typography', $typography_key ), $support_for_key );
			unset( $metadata['supports'][ $typography_key ] );
		}
	}

	return $metadata;
}

/**
 * Helper function that constructs a WP_Query args array from
 * a `Query` block properties.
 *
 * It's used in Query Loop, Query Pagination Numbers and Query Pagination Next blocks.
 *
 * @since 5.8.0
 * @since 6.1.0 Added `query_loop_block_query_vars` filter and `parents` support in query.
 * @since 6.7.0 Added support for the `format` property in query.
 *
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 *
 * @return array Returns the constructed WP_Query arguments.
 */
function build_query_vars_from_query_block( $block, $page ) {
	$query = array(
		'post_type'    => 'post',
		'order'        => 'DESC',
		'orderby'      => 'date',
		'post__not_in' => array(),
		'tax_query'    => array(),
	);

	if ( isset( $block->context['query'] ) ) {
		if ( ! empty( $block->context['query']['postType'] ) ) {
			$post_type_param = $block->context['query']['postType'];
			if ( is_post_type_viewable( $post_type_param ) ) {
				$query['post_type'] = $post_type_param;
			}
		}
		if ( isset( $block->context['query']['sticky'] ) && ! empty( $block->context['query']['sticky'] ) ) {
			$sticky = get_option( 'sticky_posts' );
			if ( 'only' === $block->context['query']['sticky'] ) {
				/*
				 * Passing an empty array to post__in will return have_posts() as true (and all posts will be returned).
				 * Logic should be used before hand to determine if WP_Query should be used in the event that the array
				 * being passed to post__in is empty.
				 *
				 * @see https://core.trac.wordpress.org/ticket/28099
				 */
				$query['post__in']            = ! empty( $sticky ) ? $sticky : array( 0 );
				$query['ignore_sticky_posts'] = 1;
			} elseif ( 'exclude' === $block->context['query']['sticky'] ) {
				$query['post__not_in'] = array_merge( $query['post__not_in'], $sticky );
			} elseif ( 'ignore' === $block->context['query']['sticky'] ) {
				$query['ignore_sticky_posts'] = 1;
			}
		}
		if ( ! empty( $block->context['query']['exclude'] ) ) {
			$excluded_post_ids     = array_map( 'intval', $block->context['query']['exclude'] );
			$excluded_post_ids     = array_filter( $excluded_post_ids );
			$query['post__not_in'] = array_merge( $query['post__not_in'], $excluded_post_ids );
		}
		if (
			isset( $block->context['query']['perPage'] ) &&
			is_numeric( $block->context['query']['perPage'] )
		) {
			$per_page = absint( $block->context['query']['perPage'] );
			$offset   = 0;

			if (
				isset( $block->context['query']['offset'] ) &&
				is_numeric( $block->context['query']['offset'] )
			) {
				$offset = absint( $block->context['query']['offset'] );
			}

			$query['offset']         = ( $per_page * ( $page - 1 ) ) + $offset;
			$query['posts_per_page'] = $per_page;
		}
		// Migrate `categoryIds` and `tagIds` to `tax_query` for backwards compatibility.
		if ( ! empty( $block->context['query']['categoryIds'] ) || ! empty( $block->context['query']['tagIds'] ) ) {
			$tax_query_back_compat = array();
			if ( ! empty( $block->context['query']['categoryIds'] ) ) {
				$tax_query_back_compat[] = array(
					'taxonomy'         => 'category',
					'terms'            => array_filter( array_map( 'intval', $block->context['query']['categoryIds'] ) ),
					'include_children' => false,
				);
			}
			if ( ! empty( $block->context['query']['tagIds'] ) ) {
				$tax_query_back_compat[] = array(
					'taxonomy'         => 'post_tag',
					'terms'            => array_filter( array_map( 'intval', $block->context['query']['tagIds'] ) ),
					'include_children' => false,
				);
			}
			$query['tax_query'] = array_merge( $query['tax_query'], $tax_query_back_compat );
		}
		if ( ! empty( $block->context['query']['taxQuery'] ) ) {
			$tax_query = array();
			foreach ( $block->context['query']['taxQuery'] as $taxonomy => $terms ) {
				if ( is_taxonomy_viewable( $taxonomy ) && ! empty( $terms ) ) {
					$tax_query[] = array(
						'taxonomy'         => $taxonomy,
						'terms'            => array_filter( array_map( 'intval', $terms ) ),
						'include_children' => false,
					);
				}
			}
			$query['tax_query'] = array_merge( $query['tax_query'], $tax_query );
		}
		if ( ! empty( $block->context['query']['format'] ) && is_array( $block->context['query']['format'] ) ) {
			$formats = $block->context['query']['format'];
			/*
			 * Validate that the format is either `standard` or a supported post format.
			 * - First, add `standard` to the array of valid formats.
			 * - Then, remove any invalid formats.
			 */
			$valid_formats = array_merge( array( 'standard' ), get_post_format_slugs() );
			$formats       = array_intersect( $formats, $valid_formats );

			/*
			 * The relation needs to be set to `OR` since the request can contain
			 * two separate conditions. The user may be querying for items that have
			 * either the `standard` format or a specific format.
			 */
			$formats_query = array( 'relation' => 'OR' );

			/*
			 * The default post format, `standard`, is not stored in the database.
			 * If `standard` is part of the request, the query needs to exclude all post items that
			 * have a format assigned.
			 */
			if ( in_array( 'standard', $formats, true ) ) {
				$formats_query[] = array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'operator' => 'NOT EXISTS',
				);
				// Remove the `standard` format, since it cannot be queried.
				unset( $formats[ array_search( 'standard', $formats, true ) ] );
			}
			// Add any remaining formats to the formats query.
			if ( ! empty( $formats ) ) {
				// Add the `post-format-` prefix.
				$terms           = array_map(
					static function ( $format ) {
						return "post-format-$format";
					},
					$formats
				);
				$formats_query[] = array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => $terms,
					'operator' => 'IN',
				);
			}

			/*
			 * Add `$formats_query` to `$query`, as long as it contains more than one key:
			 * If `$formats_query` only contains the initial `relation` key, there are no valid formats to query,
			 * and the query should not be modified.
			 */
			if ( count( $formats_query ) > 1 ) {
				// Enable filtering by both post formats and other taxonomies by combining them with `AND`.
				if ( empty( $query['tax_query'] ) ) {
					$query['tax_query'] = $formats_query;
				} else {
					$query['tax_query'] = array(
						'relation' => 'AND',
						$query['tax_query'],
						$formats_query,
					);
				}
			}
		}

		if (
			isset( $block->context['query']['order'] ) &&
				in_array( strtoupper( $block->context['query']['order'] ), array( 'ASC', 'DESC' ), true )
		) {
			$query['order'] = strtoupper( $block->context['query']['order'] );
		}
		if ( isset( $block->context['query']['orderBy'] ) ) {
			$query['orderby'] = $block->context['query']['orderBy'];
		}
		if (
			isset( $block->context['query']['author'] )
		) {
			if ( is_array( $block->context['query']['author'] ) ) {
				$query['author__in'] = array_filter( array_map( 'intval', $block->context['query']['author'] ) );
			} elseif ( is_string( $block->context['query']['author'] ) ) {
				$query['author__in'] = array_filter( array_map( 'intval', explode( ',', $block->context['query']['author'] ) ) );
			} elseif ( is_int( $block->context['query']['author'] ) && $block->context['query']['author'] > 0 ) {
				$query['author'] = $block->context['query']['author'];
			}
		}
		if ( ! empty( $block->context['query']['search'] ) ) {
			$query['s'] = $block->context['query']['search'];
		}
		if ( ! empty( $block->context['query']['parents'] ) && is_post_type_hierarchical( $query['post_type'] ) ) {
			$query['post_parent__in'] = array_unique( array_map( 'intval', $block->context['query']['parents'] ) );
		}
	}

	/**
	 * Filters the arguments which will be passed to `WP_Query` for the Query Loop Block.
	 *
	 * Anything to this filter should be compatible with the `WP_Query` API to form
	 * the query context which will be passed down to the Query Loop Block's children.
	 * This can help, for example, to include additional settings or meta queries not
	 * directly supported by the core Query Loop Block, and extend its capabilities.
	 *
	 * Please note that this will only influence the query that will be rendered on the
	 * front-end. The editor preview is not affected by this filter. Also, worth noting
	 * that the editor preview uses the REST API, so, ideally, one should aim to provide
	 * attributes which are also compatible with the REST API, in order to be able to
	 * implement identical queries on both sides.
	 *
	 * @since 6.1.0
	 *
	 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
	 * @param WP_Block $block Block instance.
	 * @param int      $page  Current query's page.
	 */
	return apply_filters( 'query_loop_block_query_vars', $query, $block, $page );
}

/**
 * Helper function that returns the proper pagination arrow HTML for
 * `QueryPaginationNext` and `QueryPaginationPrevious` blocks based
 * on the provided `paginationArrow` from `QueryPagination` context.
 *
 * It's used in QueryPaginationNext and QueryPaginationPrevious blocks.
 *
 * @since 5.9.0
 *
 * @param WP_Block $block   Block instance.
 * @param bool     $is_next Flag for handling `next/previous` blocks.
 * @return string|null The pagination arrow HTML or null if there is none.
 */
function get_query_pagination_arrow( $block, $is_next ) {
	$arrow_map = array(
		'none'    => '',
		'arrow'   => array(
			'next'     => '→',
			'previous' => '←',
		),
		'chevron' => array(
			'next'     => '»',
			'previous' => '«',
		),
	);
	if ( ! empty( $block->context['paginationArrow'] ) && array_key_exists( $block->context['paginationArrow'], $arrow_map ) && ! empty( $arrow_map[ $block->context['paginationArrow'] ] ) ) {
		$pagination_type = $is_next ? 'next' : 'previous';
		$arrow_attribute = $block->context['paginationArrow'];
		$arrow           = $arrow_map[ $block->context['paginationArrow'] ][ $pagination_type ];
		$arrow_classes   = "wp-block-query-pagination-$pagination_type-arrow is-arrow-$arrow_attribute";
		return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>";
	}
	return null;
}

/**
 * Helper function that constructs a comment query vars array from the passed
 * block properties.
 *
 * It's used with the Comment Query Loop inner blocks.
 *
 * @since 6.0.0
 *
 * @param WP_Block $block Block instance.
 * @return array Returns the comment query parameters to use with the
 *               WP_Comment_Query constructor.
 */
function build_comment_query_vars_from_block( $block ) {

	$comment_args = array(
		'orderby'       => 'comment_date_gmt',
		'order'         => 'ASC',
		'status'        => 'approve',
		'no_found_rows' => false,
	);

	if ( is_user_logged_in() ) {
		$comment_args['include_unapproved'] = array( get_current_user_id() );
	} else {
		$unapproved_email = wp_get_unapproved_comment_author_email();

		if ( $unapproved_email ) {
			$comment_args['include_unapproved'] = array( $unapproved_email );
		}
	}

	if ( ! empty( $block->context['postId'] ) ) {
		$comment_args['post_id'] = (int) $block->context['postId'];
	}

	if ( get_option( 'thread_comments' ) ) {
		$comment_args['hierarchical'] = 'threaded';
	} else {
		$comment_args['hierarchical'] = false;
	}

	if ( get_option( 'page_comments' ) === '1' || get_option( 'page_comments' ) === true ) {
		$per_page     = get_option( 'comments_per_page' );
		$default_page = get_option( 'default_comments_page' );
		if ( $per_page > 0 ) {
			$comment_args['number'] = $per_page;

			$page = (int) get_query_var( 'cpage' );
			if ( $page ) {
				$comment_args['paged'] = $page;
			} elseif ( 'oldest' === $default_page ) {
				$comment_args['paged'] = 1;
			} elseif ( 'newest' === $default_page ) {
				$max_num_pages = (int) ( new WP_Comment_Query( $comment_args ) )->max_num_pages;
				if ( 0 !== $max_num_pages ) {
					$comment_args['paged'] = $max_num_pages;
				}
			}
		}
	}

	return $comment_args;
}

/**
 * Helper function that returns the proper pagination arrow HTML for
 * `CommentsPaginationNext` and `CommentsPaginationPrevious` blocks based on the
 * provided `paginationArrow` from `CommentsPagination` context.
 *
 * It's used in CommentsPaginationNext and CommentsPaginationPrevious blocks.
 *
 * @since 6.0.0
 *
 * @param WP_Block $block           Block instance.
 * @param string   $pagination_type Optional. Type of the arrow we will be rendering.
 *                                  Accepts 'next' or 'previous'. Default 'next'.
 * @return string|null The pagination arrow HTML or null if there is none.
 */
function get_comments_pagination_arrow( $block, $pagination_type = 'next' ) {
	$arrow_map = array(
		'none'    => '',
		'arrow'   => array(
			'next'     => '→',
			'previous' => '←',
		),
		'chevron' => array(
			'next'     => '»',
			'previous' => '«',
		),
	);
	if ( ! empty( $block->context['comments/paginationArrow'] ) && ! empty( $arrow_map[ $block->context['comments/paginationArrow'] ][ $pagination_type ] ) ) {
		$arrow_attribute = $block->context['comments/paginationArrow'];
		$arrow           = $arrow_map[ $block->context['comments/paginationArrow'] ][ $pagination_type ];
		$arrow_classes   = "wp-block-comments-pagination-$pagination_type-arrow is-arrow-$arrow_attribute";
		return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>";
	}
	return null;
}

/**
 * Strips all HTML from the content of footnotes, and sanitizes the ID.
 *
 * This function expects slashed data on the footnotes content.
 *
 * @access private
 * @since 6.3.2
 *
 * @param string $footnotes JSON-encoded string of an array containing the content and ID of each footnote.
 * @return string Filtered content without any HTML on the footnote content and with the sanitized ID.
 */
function _wp_filter_post_meta_footnotes( $footnotes ) {
	$footnotes_decoded = json_decode( $footnotes, true );
	if ( ! is_array( $footnotes_decoded ) ) {
		return '';
	}
	$footnotes_sanitized = array();
	foreach ( $footnotes_decoded as $footnote ) {
		if ( ! empty( $footnote['content'] ) && ! empty( $footnote['id'] ) ) {
			$footnotes_sanitized[] = array(
				'id'      => sanitize_key( $footnote['id'] ),
				'content' => wp_unslash( wp_filter_post_kses( wp_slash( $footnote['content'] ) ) ),
			);
		}
	}
	return wp_json_encode( $footnotes_sanitized );
}

/**
 * Adds the filters for footnotes meta field.
 *
 * @access private
 * @since 6.3.2
 */
function _wp_footnotes_kses_init_filters() {
	add_filter( 'sanitize_post_meta_footnotes', '_wp_filter_post_meta_footnotes' );
}

/**
 * Removes the filters for footnotes meta field.
 *
 * @access private
 * @since 6.3.2
 */
function _wp_footnotes_remove_filters() {
	remove_filter( 'sanitize_post_meta_footnotes', '_wp_filter_post_meta_footnotes' );
}

/**
 * Registers the filter of footnotes meta field if the user does not have `unfiltered_html` capability.
 *
 * @access private
 * @since 6.3.2
 */
function _wp_footnotes_kses_init() {
	_wp_footnotes_remove_filters();
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		_wp_footnotes_kses_init_filters();
	}
}

/**
 * Initializes the filters for footnotes meta field when imported data should be filtered.
 *
 * This filter is the last one being executed on {@see 'force_filtered_html_on_import'}.
 * If the input of the filter is true, it means we are in an import situation and should
 * enable kses, independently of the user capabilities. So in that case we call
 * _wp_footnotes_kses_init_filters().
 *
 * @access private
 * @since 6.3.2
 *
 * @param string $arg Input argument of the filter.
 * @return string Input argument of the filter.
 */
function _wp_footnotes_force_filtered_html_on_import_filter( $arg ) {
	// If `force_filtered_html_on_import` is true, we need to init the global styles kses filters.
	if ( $arg ) {
		_wp_footnotes_kses_init_filters();
	}
	return $arg;
}
