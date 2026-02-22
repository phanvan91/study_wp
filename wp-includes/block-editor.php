<?php
/**
 * API Trình soạn thảo Block.
 *
 * @package WordPress
 * @subpackage Editor
 * @since 5.8.0
 */

/**
 * Trả về danh sách các chuyên mục mặc định cho các loại block.
 *
 * @since 5.8.0
 * @since 6.3.0 Block Tái sử dụng được đổi tên thành Mẫu (Patterns).
 *
 * @return array[] Mảng các chuyên mục cho các loại block.
 */
function get_default_block_categories() {
	return array(
		array(
			'slug'  => 'text',
			'title' => _x( 'Text', 'block category' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'media',
			'title' => _x( 'Media', 'block category' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'design',
			'title' => _x( 'Design', 'block category' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'widgets',
			'title' => _x( 'Widgets', 'block category' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'theme',
			'title' => _x( 'Theme', 'block category' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'embed',
			'title' => _x( 'Embeds', 'block category' ),
			'icon'  => null,
		),
		array(
			'slug'  => 'reusable',
			'title' => _x( 'Patterns', 'block category' ),
			'icon'  => null,
		),
	);
}

/**
 * Trả về tất cả các chuyên mục cho các loại block sẽ hiển thị trong trình soạn thảo block.
 *
 * @since 5.0.0
 * @since 5.8.0 Có thể truyền ngữ cảnh trình soạn thảo block làm tham số.
 *
 * @param WP_Post|WP_Block_Editor_Context $post_or_block_editor_context Đối tượng bài viết hiện tại hoặc
 *                                                                      ngữ cảnh trình soạn thảo block.
 *
 * @return array[] Mảng các chuyên mục cho các loại block.
 */
function get_block_categories( $post_or_block_editor_context ) {
	$block_categories     = get_default_block_categories();
	$block_editor_context = $post_or_block_editor_context instanceof WP_Post ?
		new WP_Block_Editor_Context(
			array(
				'post' => $post_or_block_editor_context,
			)
		) : $post_or_block_editor_context;

	/**
	 * Lọc mảng chuyên mục mặc định cho các loại block.
	 *
	 * @since 5.8.0
	 *
	 * @param array[]                 $block_categories     Mảng các chuyên mục cho các loại block.
	 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
	 */
	$block_categories = apply_filters( 'block_categories_all', $block_categories, $block_editor_context );

	if ( ! empty( $block_editor_context->post ) ) {
		$post = $block_editor_context->post;

		/**
		 * Lọc mảng chuyên mục mặc định cho các loại block.
		 *
		 * @since 5.0.0
		 * @deprecated 5.8.0 Sử dụng bộ lọc {@see 'block_categories_all'} thay thế.
		 *
		 * @param array[] $block_categories Mảng các chuyên mục cho các loại block.
		 * @param WP_Post $post             Bài viết đang được tải.
		 */
		$block_categories = apply_filters_deprecated( 'block_categories', array( $block_categories, $post ), '5.8.0', 'block_categories_all' );
	}

	return $block_categories;
}

/**
 * Lấy danh sách các loại block được phép sử dụng trong trình soạn thảo block.
 *
 * @since 5.8.0
 *
 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
 *
 * @return bool|string[] Mảng các slug loại block, hoặc boolean để bật/tắt tất cả.
 */
function get_allowed_block_types( $block_editor_context ) {
	$allowed_block_types = true;

	/**
	 * Lọc các loại block được phép cho tất cả các loại trình soạn thảo.
	 *
	 * @since 5.8.0
	 *
	 * @param bool|string[]           $allowed_block_types  Mảng các slug loại block, hoặc boolean để bật/tắt tất cả.
	 *                                                      Mặc định true (tất cả loại block đã đăng ký được hỗ trợ).
	 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
	 */
	$allowed_block_types = apply_filters( 'allowed_block_types_all', $allowed_block_types, $block_editor_context );

	if ( ! empty( $block_editor_context->post ) ) {
		$post = $block_editor_context->post;

		/**
		 * Lọc các loại block được phép cho trình soạn thảo.
		 *
		 * @since 5.0.0
		 * @deprecated 5.8.0 Sử dụng bộ lọc {@see 'allowed_block_types_all'} thay thế.
		 *
		 * @param bool|string[] $allowed_block_types Mảng các slug loại block, hoặc boolean để bật/tắt tất cả.
		 *                                           Mặc định true (tất cả loại block đã đăng ký được hỗ trợ).
		 * @param WP_Post       $post                Dữ liệu tài nguyên bài viết.
		 */
		$allowed_block_types = apply_filters_deprecated( 'allowed_block_types', array( $allowed_block_types, $post ), '5.8.0', 'allowed_block_types_all' );
	}

	return $allowed_block_types;
}

/**
 * Trả về các cài đặt mặc định của trình soạn thảo block.
 *
 * @since 5.8.0
 *
 * @return array Các cài đặt mặc định của trình soạn thảo block.
 */
function get_default_block_editor_settings() {
	// Cài đặt phương tiện.

	// wp_max_upload_size() có thể tốn tài nguyên, nên chỉ gọi khi liên quan đến người dùng hiện tại.
	$max_upload_size = 0;
	if ( current_user_can( 'upload_files' ) ) {
		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}
	}

	/** Bộ lọc này được ghi chú trong wp-admin/includes/media.php */
	$image_size_names = apply_filters(
		'image_size_names_choose',
		array(
			'thumbnail' => __( 'Thumbnail' ),
			'medium'    => __( 'Medium' ),
			'large'     => __( 'Large' ),
			'full'      => __( 'Full Size' ),
		)
	);

	$available_image_sizes = array();
	foreach ( $image_size_names as $image_size_slug => $image_size_name ) {
		$available_image_sizes[] = array(
			'slug' => $image_size_slug,
			'name' => $image_size_name,
		);
	}

	$default_size       = get_option( 'image_default_size', 'large' );
	$image_default_size = in_array( $default_size, array_keys( $image_size_names ), true ) ? $default_size : 'large';

	$image_dimensions = array();
	$all_sizes        = wp_get_registered_image_subsizes();
	foreach ( $available_image_sizes as $size ) {
		$key = $size['slug'];
		if ( isset( $all_sizes[ $key ] ) ) {
			$image_dimensions[ $key ] = $all_sizes[ $key ];
		}
	}

	// Những kiểu này được sử dụng nếu tùy chọn "không có kiểu theme" được kích hoạt hoặc trên
	// các theme không có kiểu trình soạn thảo riêng.
	$default_editor_styles_file = ABSPATH . WPINC . '/css/dist/block-editor/default-editor-styles.css';

	static $default_editor_styles_file_contents = false;
	if ( ! $default_editor_styles_file_contents && file_exists( $default_editor_styles_file ) ) {
		$default_editor_styles_file_contents = file_get_contents( $default_editor_styles_file );
	}

	$default_editor_styles = array();
	if ( $default_editor_styles_file_contents ) {
		$default_editor_styles = array(
			array( 'css' => $default_editor_styles_file_contents ),
		);
	}

	$editor_settings = array(
		'alignWide'                        => get_theme_support( 'align-wide' ),
		'allowedBlockTypes'                => true,
		'allowedMimeTypes'                 => get_allowed_mime_types(),
		'defaultEditorStyles'              => $default_editor_styles,
		'blockCategories'                  => get_default_block_categories(),
		'isRTL'                            => is_rtl(),
		'imageDefaultSize'                 => $image_default_size,
		'imageDimensions'                  => $image_dimensions,
		'imageEditing'                     => true,
		'imageSizes'                       => $available_image_sizes,
		'maxUploadFileSize'                => $max_upload_size,
		'__experimentalDashboardLink'      => admin_url( '/' ),
		// Cờ sau đây được yêu cầu để kích hoạt định dạng block Gallery mới trên ứng dụng di động trong 5.9.
		'__unstableGalleryWithImageBlocks' => true,
	);

	$theme_settings = get_classic_theme_supports_block_editor_settings();
	foreach ( $theme_settings as $key => $value ) {
		$editor_settings[ $key ] = $value;
	}

	return $editor_settings;
}

/**
 * Trả về các cài đặt trình soạn thảo block cần thiết để sử dụng block Widget Cũ (Legacy Widget)
 * vốn không được đăng ký theo mặc định.
 *
 * @since 5.8.0
 *
 * @return array Các cài đặt để sử dụng với get_block_editor_settings().
 */
function get_legacy_widget_block_editor_settings() {
	$editor_settings = array();

	/**
	 * Lọc danh sách các ID loại widget **không** nên được cung cấp bởi
	 * block Widget Cũ (Legacy Widget).
	 *
	 * Trả về mảng rỗng sẽ làm cho tất cả widget khả dụng.
	 *
	 * @since 5.8.0
	 *
	 * @param string[] $widgets Mảng các ID loại widget bị loại trừ.
	 */
	$editor_settings['widgetTypesToHideFromLegacyWidgetBlock'] = apply_filters(
		'widget_types_to_hide_from_legacy_widget_block',
		array(
			'pages',
			'calendar',
			'archives',
			'media_audio',
			'media_image',
			'media_gallery',
			'media_video',
			'search',
			'text',
			'categories',
			'recent-posts',
			'recent-comments',
			'rss',
			'tag_cloud',
			'custom_html',
			'block',
		)
	);

	return $editor_settings;
}

/**
 * Thu thập các tài nguyên trình soạn thảo block cần được tải vào iframe của trình soạn thảo.
 *
 * @since 6.0.0
 * @access private
 *
 * @global WP_Styles  $wp_styles  Thực thể WP_Styles hiện tại.
 * @global WP_Scripts $wp_scripts Thực thể WP_Scripts hiện tại.
 *
 * @return array {
 *     Các tài nguyên trình soạn thảo block.
 *
 *     @type string|false $styles  Chuỗi chứa HTML cho các kiểu.
 *     @type string|false $scripts Chuỗi chứa HTML cho các script.
 * }
 */
function _wp_get_iframed_editor_assets() {
	global $wp_styles, $wp_scripts;

	// Lưu lại thực thể styles và scripts để khôi phục sau.
	$current_wp_styles  = $wp_styles;
	$current_wp_scripts = $wp_scripts;

	// Tạo các thực thể mới để thu thập tài nguyên.
	$wp_styles  = new WP_Styles();
	$wp_scripts = new WP_Scripts();

	/*
	 * Đăng ký tất cả các kiểu và script hiện đã đăng ký. Các action tiếp theo
	 * enqueue tài nguyên, nhưng không nhất thiết phải đăng ký chúng.
	 */
	$wp_styles->registered  = $current_wp_styles->registered;
	$wp_scripts->registered = $current_wp_scripts->registered;

	/*
	 * Nhìn chung chúng ta không cần kiểu reset cho trình soạn thảo iframe.
	 * Tuy nhiên, nếu là theme cổ điển, margin sẽ được thêm vào mỗi block,
	 * được reset cụ thể cho các mục danh sách, nên các theme cổ điển phụ thuộc
	 * vào các kiểu reset này.
	 */
	$wp_styles->done =
		wp_theme_has_theme_json() ? array( 'wp-reset-editor-styles' ) : array();

	wp_enqueue_script( 'wp-polyfill' );
	// Enqueue các handle `editorStyle` cho tất cả block lõi, và các phụ thuộc.
	wp_enqueue_style( 'wp-edit-blocks' );

	if ( current_theme_supports( 'wp-block-styles' ) ) {
		wp_enqueue_style( 'wp-block-library-theme' );
	}

	/*
	 * Chúng ta không muốn tải script TRÌNH SOẠN THẢO trong iframe, chỉ enqueue
	 * các tài nguyên giao diện trước cho nội dung.
	 */
	add_filter( 'should_load_block_editor_scripts_and_styles', '__return_false' );
	do_action( 'enqueue_block_assets' );
	remove_filter( 'should_load_block_editor_scripts_and_styles', '__return_false' );

	$block_registry = WP_Block_Type_Registry::get_instance();

	/*
	 * Ngoài ra, enqueue các tài nguyên `editorStyle` cho tất cả các block,
	 * chứa kiểu chỉ dành cho trình soạn thảo cho các block (nội dung trình soạn thảo).
	 */
	foreach ( $block_registry->get_all_registered() as $block_type ) {
		if ( isset( $block_type->editor_style_handles ) && is_array( $block_type->editor_style_handles ) ) {
			foreach ( $block_type->editor_style_handles as $style_handle ) {
				wp_enqueue_style( $style_handle );
			}
		}
	}

	/**
	 * Xóa trình xử lý `print_emoji_styles` đã ngừng sử dụng.
	 * Tránh làm hỏng việc tạo kiểu với thông báo ngừng sử dụng.
	 */
	$has_emoji_styles = has_action( 'wp_print_styles', 'print_emoji_styles' );
	if ( $has_emoji_styles ) {
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	ob_start();
	wp_print_styles();
	wp_print_font_faces();
	wp_print_font_faces_from_style_variations();
	$styles = ob_get_clean();

	if ( $has_emoji_styles ) {
		add_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	ob_start();
	wp_print_head_scripts();
	wp_print_footer_scripts();
	$scripts = ob_get_clean();

	// Khôi phục các thực thể ban đầu.
	$wp_styles  = $current_wp_styles;
	$wp_scripts = $current_wp_scripts;

	return array(
		'styles'  => $styles,
		'scripts' => $scripts,
	);
}

/**
 * Tìm lần xuất hiện đầu tiên của một block cụ thể trong mảng các block.
 *
 * @since 6.3.0
 *
 * @param array  $blocks     Mảng các block.
 * @param string $block_name Tên của block cần tìm.
 * @return array Block được tìm thấy, hoặc mảng rỗng nếu không tìm thấy.
 */
function wp_get_first_block( $blocks, $block_name ) {
	foreach ( $blocks as $block ) {
		if ( $block_name === $block['blockName'] ) {
			return $block;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found_block = wp_get_first_block( $block['innerBlocks'], $block_name );

			if ( ! empty( $found_block ) ) {
				return $found_block;
			}
		}
	}

	return array();
}

/**
 * Lấy các thuộc tính block Nội dung Bài viết từ template bài viết hiện tại.
 *
 * @since 6.3.0
 * @since 6.4.0 Trả về null nếu không có block nội dung bài viết.
 * @access private
 *
 * @global int $post_ID
 *
 * @return array|null Mảng thuộc tính block Nội dung Bài viết hoặc null nếu block Nội dung Bài viết không tồn tại.
 */
function wp_get_post_content_block_attributes() {
	global $post_ID;

	$is_block_theme = wp_is_block_theme();

	if ( ! $is_block_theme || ! $post_ID ) {
		return null;
	}

	$template_slug = get_page_template_slug( $post_ID );

	if ( ! $template_slug ) {
		$post_slug      = 'singular';
		$page_slug      = 'singular';
		$template_types = get_block_templates();

		foreach ( $template_types as $template_type ) {
			if ( 'page' === $template_type->slug ) {
				$page_slug = 'page';
			}
			if ( 'single' === $template_type->slug ) {
				$post_slug = 'single';
			}
		}

		$what_post_type = get_post_type( $post_ID );
		switch ( $what_post_type ) {
			case 'page':
				$template_slug = $page_slug;
				break;
			default:
				$template_slug = $post_slug;
				break;
		}
	}

	$current_template = get_block_templates( array( 'slug__in' => array( $template_slug ) ) );

	if ( ! empty( $current_template ) ) {
		$template_blocks    = parse_blocks( $current_template[0]->content );
		$post_content_block = wp_get_first_block( $template_blocks, 'core/post-content' );

		if ( isset( $post_content_block['attrs'] ) ) {
			return $post_content_block['attrs'];
		}
	}

	return null;
}

/**
 * Trả về các cài đặt trình soạn thảo block theo ngữ cảnh cho ngữ cảnh trình soạn thảo đã chọn.
 *
 * @since 5.8.0
 *
 * @param array                   $custom_settings      Các cài đặt tùy chỉnh để sử dụng với loại trình soạn thảo đã cho.
 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
 *
 * @return array Các cài đặt trình soạn thảo block theo ngữ cảnh.
 */
function get_block_editor_settings( array $custom_settings, $block_editor_context ) {
	$editor_settings = array_merge(
		get_default_block_editor_settings(),
		array(
			'allowedBlockTypes' => get_allowed_block_types( $block_editor_context ),
			'blockCategories'   => get_block_categories( $block_editor_context ),
		),
		$custom_settings
	);

	$global_styles = array();
	$presets       = array(
		array(
			'css'            => 'variables',
			'__unstableType' => 'presets',
			'isGlobalStyles' => true,
		),
		array(
			'css'            => 'presets',
			'__unstableType' => 'presets',
			'isGlobalStyles' => true,
		),
	);
	foreach ( $presets as $preset_style ) {
		$actual_css = wp_get_global_stylesheet( array( $preset_style['css'] ) );
		if ( '' !== $actual_css ) {
			$preset_style['css'] = $actual_css;
			$global_styles[]     = $preset_style;
		}
	}

	if ( wp_theme_has_theme_json() ) {
		$block_classes = array(
			'css'            => 'styles',
			'__unstableType' => 'theme',
			'isGlobalStyles' => true,
		);
		$actual_css    = wp_get_global_stylesheet( array( $block_classes['css'] ) );
		if ( '' !== $actual_css ) {
			$block_classes['css'] = $actual_css;
			$global_styles[]      = $block_classes;
		}

		/*
		 * Thêm CSS tùy chỉnh dưới dạng stylesheet riêng để bất kỳ CSS không hợp lệ nào
		 * được người dùng nhập không làm hỏng các kiểu toàn cục khác.
		 */
		$global_styles[] = array(
			'css'            => wp_get_global_stylesheet( array( 'custom-css' ) ),
			'__unstableType' => 'user',
			'isGlobalStyles' => true,
		);
	} else {
		// Nếu không có tệp `theme.json`, đảm bảo các kiểu bố cục cơ sở vẫn khả dụng.
		$block_classes = array(
			'css'            => 'base-layout-styles',
			'__unstableType' => 'base-layout',
			'isGlobalStyles' => true,
		);
		$actual_css    = wp_get_global_stylesheet( array( $block_classes['css'] ) );
		if ( '' !== $actual_css ) {
			$block_classes['css'] = $actual_css;
			$global_styles[]      = $block_classes;
		}
	}

	$editor_settings['styles'] = array_merge( $global_styles, get_block_editor_theme_styles() );

	$editor_settings['__experimentalFeatures'] = wp_get_global_settings();
	// Các cài đặt này có thể cần được cập nhật dựa trên dữ liệu từ các nguồn theme.json.
	if ( isset( $editor_settings['__experimentalFeatures']['color']['palette'] ) ) {
		$colors_by_origin          = $editor_settings['__experimentalFeatures']['color']['palette'];
		$editor_settings['colors'] = isset( $colors_by_origin['custom'] ) ?
			$colors_by_origin['custom'] : (
				isset( $colors_by_origin['theme'] ) ?
					$colors_by_origin['theme'] :
					$colors_by_origin['default']
			);
	}
	if ( isset( $editor_settings['__experimentalFeatures']['color']['gradients'] ) ) {
		$gradients_by_origin          = $editor_settings['__experimentalFeatures']['color']['gradients'];
		$editor_settings['gradients'] = isset( $gradients_by_origin['custom'] ) ?
			$gradients_by_origin['custom'] : (
				isset( $gradients_by_origin['theme'] ) ?
					$gradients_by_origin['theme'] :
					$gradients_by_origin['default']
			);
	}
	if ( isset( $editor_settings['__experimentalFeatures']['typography']['fontSizes'] ) ) {
		$font_sizes_by_origin         = $editor_settings['__experimentalFeatures']['typography']['fontSizes'];
		$editor_settings['fontSizes'] = isset( $font_sizes_by_origin['custom'] ) ?
			$font_sizes_by_origin['custom'] : (
				isset( $font_sizes_by_origin['theme'] ) ?
					$font_sizes_by_origin['theme'] :
					$font_sizes_by_origin['default']
			);
	}
	if ( isset( $editor_settings['__experimentalFeatures']['color']['custom'] ) ) {
		$editor_settings['disableCustomColors'] = ! $editor_settings['__experimentalFeatures']['color']['custom'];
		unset( $editor_settings['__experimentalFeatures']['color']['custom'] );
	}
	if ( isset( $editor_settings['__experimentalFeatures']['color']['customGradient'] ) ) {
		$editor_settings['disableCustomGradients'] = ! $editor_settings['__experimentalFeatures']['color']['customGradient'];
		unset( $editor_settings['__experimentalFeatures']['color']['customGradient'] );
	}
	if ( isset( $editor_settings['__experimentalFeatures']['typography']['customFontSize'] ) ) {
		$editor_settings['disableCustomFontSizes'] = ! $editor_settings['__experimentalFeatures']['typography']['customFontSize'];
		unset( $editor_settings['__experimentalFeatures']['typography']['customFontSize'] );
	}
	if ( isset( $editor_settings['__experimentalFeatures']['typography']['lineHeight'] ) ) {
		$editor_settings['enableCustomLineHeight'] = $editor_settings['__experimentalFeatures']['typography']['lineHeight'];
		unset( $editor_settings['__experimentalFeatures']['typography']['lineHeight'] );
	}
	if ( isset( $editor_settings['__experimentalFeatures']['spacing']['units'] ) ) {
		$editor_settings['enableCustomUnits'] = $editor_settings['__experimentalFeatures']['spacing']['units'];
		unset( $editor_settings['__experimentalFeatures']['spacing']['units'] );
	}
	if ( isset( $editor_settings['__experimentalFeatures']['spacing']['padding'] ) ) {
		$editor_settings['enableCustomSpacing'] = $editor_settings['__experimentalFeatures']['spacing']['padding'];
		unset( $editor_settings['__experimentalFeatures']['spacing']['padding'] );
	}
	if ( isset( $editor_settings['__experimentalFeatures']['spacing']['customSpacingSize'] ) ) {
		$editor_settings['disableCustomSpacingSizes'] = ! $editor_settings['__experimentalFeatures']['spacing']['customSpacingSize'];
		unset( $editor_settings['__experimentalFeatures']['spacing']['customSpacingSize'] );
	}

	if ( isset( $editor_settings['__experimentalFeatures']['spacing']['spacingSizes'] ) ) {
		$spacing_sizes_by_origin         = $editor_settings['__experimentalFeatures']['spacing']['spacingSizes'];
		$editor_settings['spacingSizes'] = isset( $spacing_sizes_by_origin['custom'] ) ?
			$spacing_sizes_by_origin['custom'] : (
				isset( $spacing_sizes_by_origin['theme'] ) ?
					$spacing_sizes_by_origin['theme'] :
					$spacing_sizes_by_origin['default']
			);
	}

	$editor_settings['__unstableResolvedAssets']         = _wp_get_iframed_editor_assets();
	$editor_settings['__unstableIsBlockBasedTheme']      = wp_is_block_theme();
	$editor_settings['localAutosaveInterval']            = 15;
	$editor_settings['disableLayoutStyles']              = current_theme_supports( 'disable-layout-styles' );
	$editor_settings['__experimentalDiscussionSettings'] = array(
		'commentOrder'         => get_option( 'comment_order' ),
		'commentsPerPage'      => get_option( 'comments_per_page' ),
		'defaultCommentsPage'  => get_option( 'default_comments_page' ),
		'pageComments'         => get_option( 'page_comments' ),
		'threadComments'       => get_option( 'thread_comments' ),
		'threadCommentsDepth'  => get_option( 'thread_comments_depth' ),
		'defaultCommentStatus' => get_option( 'default_comment_status' ),
		'avatarURL'            => get_avatar_url(
			'',
			array(
				'size'          => 96,
				'force_default' => true,
				'default'       => get_option( 'avatar_default' ),
			)
		),
	);

	$post_content_block_attributes = wp_get_post_content_block_attributes();

	if ( isset( $post_content_block_attributes ) ) {
		$editor_settings['postContentAttributes'] = $post_content_block_attributes;
	}

	$editor_settings['canUpdateBlockBindings'] = current_user_can( 'edit_block_binding', $block_editor_context );

	/**
	 * Lọc các cài đặt để truyền cho trình soạn thảo block cho tất cả loại trình soạn thảo.
	 *
	 * @since 5.8.0
	 *
	 * @param array                   $editor_settings      Các cài đặt trình soạn thảo mặc định.
	 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
	 */
	$editor_settings = apply_filters( 'block_editor_settings_all', $editor_settings, $block_editor_context );

	if ( ! empty( $block_editor_context->post ) ) {
		$post = $block_editor_context->post;

		/**
		 * Lọc các cài đặt để truyền cho trình soạn thảo block.
		 *
		 * @since 5.0.0
		 * @deprecated 5.8.0 Sử dụng bộ lọc {@see 'block_editor_settings_all'} thay thế.
		 *
		 * @param array   $editor_settings Các cài đặt trình soạn thảo mặc định.
		 * @param WP_Post $post            Bài viết đang được chỉnh sửa.
		 */
		$editor_settings = apply_filters_deprecated( 'block_editor_settings', array( $editor_settings, $post ), '5.8.0', 'block_editor_settings_all' );
	}

	return $editor_settings;
}

/**
 * Tải trước dữ liệu phổ biến được sử dụng với trình soạn thảo block bằng cách chỉ định mảng
 * các đường dẫn REST API sẽ được tải trước cho ngữ cảnh trình soạn thảo block đã cho.
 *
 * @since 5.8.0
 *
 * @global WP_Post    $post       Đối tượng bài viết toàn cục.
 * @global WP_Scripts $wp_scripts Đối tượng WP_Scripts để in script.
 * @global WP_Styles  $wp_styles  Đối tượng WP_Styles để in kiểu.
 *
 * @param (string|string[])[]     $preload_paths        Danh sách các đường dẫn cần tải trước.
 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
 */
function block_editor_rest_api_preload( array $preload_paths, $block_editor_context ) {
	global $post, $wp_scripts, $wp_styles;

	/**
	 * Lọc mảng các đường dẫn REST API sẽ được sử dụng để tải trước dữ liệu phổ biến cho trình soạn thảo block.
	 *
	 * @since 5.8.0
	 *
	 * @param (string|string[])[]     $preload_paths        Mảng các đường dẫn cần tải trước.
	 * @param WP_Block_Editor_Context $block_editor_context Ngữ cảnh trình soạn thảo block hiện tại.
	 */
	$preload_paths = apply_filters( 'block_editor_rest_api_preload_paths', $preload_paths, $block_editor_context );

	if ( ! empty( $block_editor_context->post ) ) {
		$selected_post = $block_editor_context->post;

		/**
		 * Lọc mảng các đường dẫn sẽ được tải trước.
		 *
		 * Tải trước dữ liệu phổ biến bằng cách chỉ định mảng các đường dẫn REST API sẽ được tải trước.
		 *
		 * @since 5.0.0
		 * @deprecated 5.8.0 Sử dụng bộ lọc {@see 'block_editor_rest_api_preload_paths'} thay thế.
		 *
		 * @param (string|string[])[] $preload_paths Mảng các đường dẫn cần tải trước.
		 * @param WP_Post             $selected_post Bài viết đang được chỉnh sửa.
		 */
		$preload_paths = apply_filters_deprecated( 'block_editor_preload_paths', array( $preload_paths, $selected_post ), '5.8.0', 'block_editor_rest_api_preload_paths' );
	}

	if ( empty( $preload_paths ) ) {
		return;
	}

	/*
	 * Đảm bảo biến toàn cục $post, $wp_scripts, và $wp_styles giữ nguyên sau khi
	 * dữ liệu API được tải trước.
	 * Vì việc tải trước API có thể gọi the_content và các bộ lọc khác, plugin
	 * có thể vô tình sửa đổi biến toàn cục $post hoặc enqueue các tài nguyên không
	 * dành cho trình soạn thảo block.
	 */
	$backup_global_post = ! empty( $post ) ? clone $post : $post;
	$backup_wp_scripts  = ! empty( $wp_scripts ) ? clone $wp_scripts : $wp_scripts;
	$backup_wp_styles   = ! empty( $wp_styles ) ? clone $wp_styles : $wp_styles;

	foreach ( $preload_paths as &$path ) {
		if ( is_string( $path ) && ! str_starts_with( $path, '/' ) ) {
			$path = '/' . $path;
			continue;
		}

		if ( is_array( $path ) && is_string( $path[0] ) && ! str_starts_with( $path[0], '/' ) ) {
			$path[0] = '/' . $path[0];
		}
	}

	unset( $path );

	$preload_data = array_reduce(
		$preload_paths,
		'rest_preload_api_request',
		array()
	);

	// Khôi phục biến toàn cục $post, $wp_scripts, và $wp_styles về trạng thái trước khi tải trước API.
	$post       = $backup_global_post;
	$wp_scripts = $backup_wp_scripts;
	$wp_styles  = $backup_wp_styles;

	wp_add_inline_script(
		'wp-api-fetch',
		sprintf(
			'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
			wp_json_encode( $preload_data )
		),
		'after'
	);
}

/**
 * Tạo mảng các kiểu theme để tải vào trình soạn thảo block.
 *
 * @since 5.8.0
 *
 * @global array $editor_styles
 *
 * @return array Mảng các kiểu theme cho trình soạn thảo block.
 */
function get_block_editor_theme_styles() {
	global $editor_styles;

	$styles = array();

	if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
		foreach ( $editor_styles as $style ) {
			if ( preg_match( '~^(https?:)?//~', $style ) ) {
				$response = wp_remote_get( $style );
				if ( ! is_wp_error( $response ) ) {
					$styles[] = array(
						'css'            => wp_remote_retrieve_body( $response ),
						'__unstableType' => 'theme',
						'isGlobalStyles' => false,
					);
				}
			} else {
				$file = get_theme_file_path( $style );
				if ( is_file( $file ) ) {
					$styles[] = array(
						'css'            => file_get_contents( $file ),
						'baseURL'        => get_theme_file_uri( $style ),
						'__unstableType' => 'theme',
						'isGlobalStyles' => false,
					);
				}
			}
		}
	}

	return $styles;
}

/**
 * Trả về các cài đặt hỗ trợ theme cổ điển cho trình soạn thảo block.
 *
 * @since 6.2.0
 * @since 6.6.0 Thêm hỗ trợ cho 'editor-spacing-sizes' theme support.
 *
 * @return array Các cài đặt hỗ trợ theme cổ điển.
 */
function get_classic_theme_supports_block_editor_settings() {
	$theme_settings = array(
		'disableCustomColors'    => get_theme_support( 'disable-custom-colors' ),
		'disableCustomFontSizes' => get_theme_support( 'disable-custom-font-sizes' ),
		'disableCustomGradients' => get_theme_support( 'disable-custom-gradients' ),
		'disableLayoutStyles'    => get_theme_support( 'disable-layout-styles' ),
		'enableCustomLineHeight' => get_theme_support( 'custom-line-height' ),
		'enableCustomSpacing'    => get_theme_support( 'custom-spacing' ),
		'enableCustomUnits'      => get_theme_support( 'custom-units' ),
	);

	// Cài đặt theme.
	$color_palette = current( (array) get_theme_support( 'editor-color-palette' ) );
	if ( false !== $color_palette ) {
		$theme_settings['colors'] = $color_palette;
	}

	$font_sizes = current( (array) get_theme_support( 'editor-font-sizes' ) );
	if ( false !== $font_sizes ) {
		$theme_settings['fontSizes'] = $font_sizes;
	}

	$gradient_presets = current( (array) get_theme_support( 'editor-gradient-presets' ) );
	if ( false !== $gradient_presets ) {
		$theme_settings['gradients'] = $gradient_presets;
	}

	$spacing_sizes = current( (array) get_theme_support( 'editor-spacing-sizes' ) );
	if ( false !== $spacing_sizes ) {
		$theme_settings['spacingSizes'] = $spacing_sizes;
	}

	return $theme_settings;
}

/**
 * Khởi tạo xem trước trang web.
 *
 * Hàm này thiết lập IFRAME_REQUEST thành true nếu tham số xem trước trang web được đặt.
 *
 * @since 6.8.0
 */
function wp_initialize_site_preview_hooks() {
	if (
		! defined( 'IFRAME_REQUEST' ) &&
		isset( $_GET['wp_site_preview'] ) &&
		1 === (int) $_GET['wp_site_preview'] &&
		current_user_can( 'edit_theme_options' )
	) {
		define( 'IFRAME_REQUEST', true );
	}
}
