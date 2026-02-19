<?php
/**
 * Hỗ trợ thiết lập sẵn cấp block.
 *
 * @package WordPress
 * @since 6.2.0
 */

/**
 * Lấy tên lớp CSS được sử dụng cho thiết lập sẵn cấp block.
 *
 * @internal
 *
 * @since 6.2.0
 * @access private
 *
 * @param array $block Đối tượng block.
 * @return string      Tên lớp CSS duy nhất.
 */
function _wp_get_presets_class_name( $block ) {
	return 'wp-settings-' . md5( serialize( $block ) );
}

/**
 * Cập nhật nội dung block với tên lớp CSS thiết lập sẵn cấp block.
 *
 * @internal
 *
 * @since 6.2.0
 * @access private
 *
 * @param  string $block_content Nội dung block đã được render.
 * @param  array  $block         Đối tượng block.
 * @return string                Nội dung block đã được lọc.
 */
function _wp_add_block_level_presets_class( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	// Trả về sớm nếu block không hỗ trợ thiết lập.
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	if ( ! block_has_support( $block_type, '__experimentalSettings', false ) ) {
		return $block_content;
	}

	// Trả về sớm nếu không tìm thấy thiết lập nào trong thuộc tính block.
	$block_settings = isset( $block['attrs']['settings'] ) ? $block['attrs']['settings'] : null;
	if ( empty( $block_settings ) ) {
		return $block_content;
	}

	// Giống hook layout, giả sử hook chỉ áp dụng cho block có một wrapper duy nhất.
	// Thêm tên lớp vào phần tử đầu tiên, giả sử đó là wrapper, nếu nó tồn tại.
	$tags = new WP_HTML_Tag_Processor( $block_content );
	if ( $tags->next_tag() ) {
		$tags->add_class( _wp_get_presets_class_name( $block ) );
	}

	return $tags->get_updated_html();
}

/**
 * Render bảng kiểu CSS thiết lập sẵn cấp block.
 *
 * @internal
 *
 * @since 6.2.0
 * @since 6.3.0 Cập nhật kiểu thiết lập sẵn để sử dụng Selectors API.
 * @access private
 *
 * @param string|null $pre_render Nội dung đã render trước. Mặc định null.
 * @param array       $block      Block đang được render.
 *
 * @return null
 */
function _wp_add_block_level_preset_styles( $pre_render, $block ) {
	// Trả về sớm nếu block không hỗ trợ kiểu block con cháu.
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	if ( ! block_has_support( $block_type, '__experimentalSettings', false ) ) {
		return null;
	}

	// Trả về sớm nếu không tìm thấy thiết lập nào trong thuộc tính block.
	$block_settings = isset( $block['attrs']['settings'] ) ? $block['attrs']['settings'] : null;
	if ( empty( $block_settings ) ) {
		return null;
	}

	$class_name = '.' . _wp_get_presets_class_name( $block );

	// Selector gốc cho biến thiết lập sẵn cần nhắm vào mọi selector block có thể
	// để thiết lập chung ghi đè bất kỳ thiết lập cụ thể nào của block cha hoặc
	// gốc trang web.
	$variables_root_selector = '*,[class*="wp-block"]';
	$registry                = WP_Block_Type_Registry::get_instance();
	$blocks                  = $registry->get_all_registered();
	foreach ( $blocks as $block_type ) {
		/*
		 * Chúng ta chỉ muốn thêm selector cho các block sử dụng selector tùy chỉnh
		 * tức là không phải `wp-block-<name>`.
		 */
		$has_custom_selector =
			( isset( $block_type->supports['__experimentalSelector'] ) && is_string( $block_type->supports['__experimentalSelector'] ) ) ||
			( isset( $block_type->selectors['root'] ) && is_string( $block_type->selectors['root'] ) );

		if ( $has_custom_selector ) {
			$variables_root_selector .= ',' . wp_get_block_css_selector( $block_type );
		}
	}
	$variables_root_selector = WP_Theme_JSON::scope_selector( $class_name, $variables_root_selector );

	// Loại bỏ bất kỳ kiểu nào có thể không an toàn.
	$theme_json_shape  = WP_Theme_JSON::remove_insecure_properties(
		array(
			'version'  => WP_Theme_JSON::LATEST_SCHEMA,
			'settings' => $block_settings,
		)
	);
	$theme_json_object = new WP_Theme_JSON( $theme_json_shape );

	$styles = '';

	// Bao gồm khai báo biến CSS thiết lập sẵn trên bảng kiểu.
	$styles .= $theme_json_object->get_stylesheet(
		array( 'variables' ),
		null,
		array(
			'root_selector' => $variables_root_selector,
			'scope'         => $class_name,
		)
	);

	// Bao gồm các lớp CSS thiết lập sẵn trên bảng kiểu.
	$styles .= $theme_json_object->get_stylesheet(
		array( 'presets' ),
		null,
		array(
			'root_selector' => $class_name . ',' . $class_name . ' *',
			'scope'         => $class_name,
		)
	);

	if ( ! empty( $styles ) ) {
		wp_enqueue_block_support_styles( $styles );
	}

	return null;
}

add_filter( 'render_block', '_wp_add_block_level_presets_class', 10, 2 );
add_filter( 'pre_render_block', '_wp_add_block_level_preset_styles', 10, 2 );
