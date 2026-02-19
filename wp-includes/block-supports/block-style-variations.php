<?php
/**
 * Hỗ trợ block để bật kiểu dáng theo từng phần của các loại block
 * thông qua biến thể kiểu block.
 *
 * @package WordPress
 * @since 6.6.0
 */

/**
 * Xác định tên biến thể kiểu block trong một chuỗi lớp CSS.
 *
 * @since 6.6.0
 *
 * @param string $class_string Chuỗi lớp CSS để tìm biến thể.
 *
 * @return array|null Tên biến thể kiểu block nếu tìm thấy.
 */
function wp_get_block_style_variation_name_from_class( $class_string ) {
	if ( ! is_string( $class_string ) ) {
		return null;
	}

	preg_match_all( '/\bis-style-(?!default)(\S+)\b/', $class_string, $matches );
	return $matches[1] ?? null;
}

/**
 * Phân giải đệ quy mọi giá trị `ref` trong dữ liệu biến thể kiểu block.
 *
 * @since 6.6.0
 * @access private
 *
 * @param array $variation_data Tham chiếu đến dữ liệu biến thể đang được xử lý.
 * @param array $theme_json     Dữ liệu Theme.json để lấy các giá trị được tham chiếu.
 */
function wp_resolve_block_style_variation_ref_values( &$variation_data, $theme_json ) {
	foreach ( $variation_data as $key => &$value ) {
		// Chỉ cần xử lý tiềm năng các mảng.
		if ( is_array( $value ) ) {
			// Nếu giá trị ref được đặt, cố gắng tìm giá trị khớp và cập nhật nó.
			if ( array_key_exists( 'ref', $value ) ) {
				// Dọn dẹp giá trị ref không hợp lệ.
				if ( empty( $value['ref'] ) || ! is_string( $value['ref'] ) ) {
					unset( $variation_data[ $key ] );
				}

				$value_path = explode( '.', $value['ref'] ?? '' );
				$ref_value  = _wp_array_get( $theme_json, $value_path );

				// Chỉ cập nhật giá trị hiện tại nếu đường dẫn tham chiếu khớp với một giá trị.
				if ( null === $ref_value ) {
					unset( $variation_data[ $key ] );
				} else {
					$value = $ref_value;
				}
			} else {
				// Tìm kiếm đệ quy các thể hiện ref.
				wp_resolve_block_style_variation_ref_values( $value, $theme_json );
			}
		}
	}
}
/**
 * Render các kiểu của biến thể kiểu block.
 *
 * Trong trường hợp các block lồng nhau có biến thể được áp dụng, chúng ta muốn
 * kiểu của biến thể cha được render trước các con cháu. Điều này giải quyết
 * vấn đề một loại block được định kiểu ở cả cha và con cháu: chúng ta muốn
 * kiểu con cháu được ưu tiên, và điều này được thực hiện bằng cách tải nó sau,
 * theo thứ tự DOM. Đây là lý do tại sao việc tạo bảng kiểu biến thể nằm trong
 * một bộ lọc khác.
 *
 * @since 6.6.0
 * @access private
 *
 * @param array $parsed_block Block đã được phân tích.
 *
 * @return array Block đã phân tích với tên lớp CSS biến thể kiểu block được thêm.
 */
function wp_render_block_style_variation_support_styles( $parsed_block ) {
	$classes    = $parsed_block['attrs']['className'] ?? null;
	$variations = wp_get_block_style_variation_name_from_class( $classes );

	if ( ! $variations ) {
		return $parsed_block;
	}

	$tree       = WP_Theme_JSON_Resolver::get_merged_data();
	$theme_json = $tree->get_raw_data();

	// Chỉ biến thể kiểu block đầu tiên có dữ liệu được hỗ trợ.
	$variation_data = array();
	foreach ( $variations as $variation ) {
		$variation_data = $theme_json['styles']['blocks'][ $parsed_block['blockName'] ]['variations'][ $variation ] ?? array();

		if ( ! empty( $variation_data ) ) {
			break;
		}
	}

	if ( empty( $variation_data ) ) {
		return $parsed_block;
	}

	/*
	 * Phân giải đệ quy mọi giá trị ref với giá trị phù hợp trong
	 * dữ liệu theme_json.
	 */
	wp_resolve_block_style_variation_ref_values( $variation_data, $theme_json );

	$variation_instance = wp_unique_id( $variation . '--' );
	$class_name         = "is-style-$variation_instance";
	$updated_class_name = $parsed_block['attrs']['className'] . " $class_name";

	/*
	 * Mặc dù biến thể kiểu block thực chất là các phần theme.json,
	 * chúng không thể được xử lý hoàn toàn như vậy.
	 *
	 * Kiểu block hỗ trợ các selector tùy chỉnh để hướng các loại kiểu cụ thể
	 * đến các phần tử bên trong. Ví dụ, viền trên block Hình ảnh được áp dụng cho
	 * phần tử `img` bên trong thay vì `figure` bao bọc.
	 *
	 * Đoạn mã sau di chuyển các kiểu biến thể kiểu block "gốc" sang
	 * dưới thuộc tính blocks phù hợp để tận dụng cơ chế tạo kiểu
	 * đã có sẵn cho các biến thể kiểu block đơn giản. Bằng cách này chúng
	 * nhận được các selector tùy chỉnh cần thiết.
	 *
	 * Các phần tử bên trong và kiểu block cho bản thân biến thể
	 * vẫn được bao gồm ở cấp cao nhất nhưng được giới hạn bởi selector
	 * của biến thể khi bảng kiểu được tạo.
	 */
	$elements_data = $variation_data['elements'] ?? array();
	$blocks_data   = $variation_data['blocks'] ?? array();
	unset( $variation_data['elements'] );
	unset( $variation_data['blocks'] );

	_wp_array_set(
		$blocks_data,
		array( $parsed_block['blockName'], 'variations', $variation_instance ),
		$variation_data
	);

	$config = array(
		'version' => WP_Theme_JSON::LATEST_SCHEMA,
		'styles'  => array(
			'elements' => $elements_data,
			'blocks'   => $blocks_data,
		),
	);

	// Tắt bộ lọc loại trừ các nút block. Chúng cần thiết ở đây cho các loại block bên trong của biến thể.
	if ( ! is_admin() ) {
		remove_filter( 'wp_theme_json_get_style_nodes', 'wp_filter_out_block_nodes' );
	}

	// Tạm thời ngăn thể hiện biến thể bị làm sạch trong khi xử lý theme.json.
	$styles_registry = WP_Block_Styles_Registry::get_instance();
	$styles_registry->register( $parsed_block['blockName'], array( 'name' => $variation_instance ) );

	$variation_theme_json = new WP_Theme_JSON( $config, 'blocks' );
	$variation_styles     = $variation_theme_json->get_stylesheet(
		array( 'styles' ),
		array( 'custom' ),
		array(
			'include_block_style_variations' => true,
			'skip_root_layout_styles'        => true,
			'scope'                          => ".$class_name",
		)
	);

	// Dọn dẹp kiểu block tạm thời sau khi các kiểu thể hiện đã được xử lý.
	$styles_registry->unregister( $parsed_block['blockName'], $variation_instance );

	// Khôi phục bộ lọc loại trừ các nút block.
	if ( ! is_admin() ) {
		add_filter( 'wp_theme_json_get_style_nodes', 'wp_filter_out_block_nodes' );
	}

	if ( empty( $variation_styles ) ) {
		return $parsed_block;
	}

	wp_register_style( 'block-style-variation-styles', false, array( 'wp-block-library', 'global-styles' ) );
	wp_add_inline_style( 'block-style-variation-styles', $variation_styles );

	/*
	 * Thêm tên lớp CSS thể hiện biến thể vào chuỗi className của block để có thể
	 * được áp dụng trong markup block qua bộ lọc render_block.
	 */
	_wp_array_set( $parsed_block, array( 'attrs', 'className' ), $updated_class_name );

	return $parsed_block;
}

/**
 * Đảm bảo tên lớp CSS hỗ trợ block biến thể được tạo và thêm vào
 * thuộc tính block trong bộ lọc `render_block_data` được áp dụng vào
 * markup của block.
 *
 * @since 6.6.0
 * @access private
 *
 * @see wp_render_block_style_variation_support_styles
 *
 * @param  string $block_content Nội dung block đã được render.
 * @param  array  $block         Đối tượng block.
 *
 * @return string                Nội dung block đã được lọc.
 */
function wp_render_block_style_variation_class_name( $block_content, $block ) {
	if ( ! $block_content || empty( $block['attrs']['className'] ) ) {
		return $block_content;
	}

	/*
	 * Khớp một lớp CSS có tiền tố `is-style`, theo sau là
	 * slug biến thể, rồi `--`, và cuối cùng là số thể hiện.
	 */
	preg_match( '/\bis-style-(\S+?--\d+)\b/', $block['attrs']['className'], $matches );

	if ( empty( $matches ) ) {
		return $block_content;
	}

	$tags = new WP_HTML_Tag_Processor( $block_content );

	if ( $tags->next_tag() ) {
		/*
		 * Đảm bảo tên lớp CSS thể hiện biến thể được đặt trong
		 * bộ lọc `render_block_data` được áp dụng trong markup.
		 * Xem `wp_render_block_style_variation_support_styles`.
		 */
		$tags->add_class( $matches[0] );
	}

	return $tags->get_updated_html();
}

/**
 * Nạp các kiểu cho biến thể kiểu block.
 *
 * @since 6.6.0
 * @access private
 */
function wp_enqueue_block_style_variation_styles() {
	wp_enqueue_style( 'block-style-variation-styles' );
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register( 'block-style-variation', array() );

add_filter( 'render_block_data', 'wp_render_block_style_variation_support_styles', 10, 2 );
add_filter( 'render_block', 'wp_render_block_style_variation_class_name', 10, 2 );
add_action( 'wp_enqueue_scripts', 'wp_enqueue_block_style_variation_styles', 1 );

/**
 * Đăng ký các biến thể kiểu block được đọc từ các phần theme.json.
 *
 * @since 6.6.0
 * @access private
 *
 * @param array $variations Các biến thể kiểu block chia sẻ.
 */
function wp_register_block_style_variations_from_theme_json_partials( $variations ) {
	if ( empty( $variations ) ) {
		return;
	}

	$registry = WP_Block_Styles_Registry::get_instance();

	foreach ( $variations as $variation ) {
		if ( empty( $variation['blockTypes'] ) || empty( $variation['styles'] ) ) {
			continue;
		}

		$variation_name  = $variation['slug'] ?? _wp_to_kebab_case( $variation['title'] );
		$variation_label = $variation['title'] ?? $variation_name;

		foreach ( $variation['blockTypes'] as $block_type ) {
			$registered_styles = $registry->get_registered_styles_for_block( $block_type );

			// Đăng ký biến thể kiểu block nếu chưa được đăng ký.
			if ( ! array_key_exists( $variation_name, $registered_styles ) ) {
				register_block_style(
					$block_type,
					array(
						'name'  => $variation_name,
						'label' => $variation_label,
					)
				);
			}
		}
	}
}
