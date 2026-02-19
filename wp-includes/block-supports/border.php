<?php
/**
 * Cờ hỗ trợ viền cho block.
 *
 * @package WordPress
 * @since 5.8.0
 */

/**
 * Đăng ký thuộc tính kiểu được sử dụng bởi tính năng viền nếu cần cho
 * các loại block hỗ trợ viền.
 *
 * @since 5.8.0
 * @since 6.1.0 Cải thiện tối ưu hóa block có điều kiện.
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_border_support( $block_type ) {
	// Thiết lập thuộc tính và kiểu bên trong nếu cần.
	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( block_has_support( $block_type, '__experimentalBorder' ) && ! array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}

	if ( wp_has_border_feature_support( $block_type, 'color' ) && ! array_key_exists( 'borderColor', $block_type->attributes ) ) {
		$block_type->attributes['borderColor'] = array(
			'type' => 'string',
		);
	}
}

/**
 * Thêm các lớp CSS và kiểu inline cho kiểu viền vào mảng thuộc tính đầu vào.
 * Điều này sẽ được áp dụng cho markup block ở giao diện người dùng.
 *
 * @since 5.8.0
 * @since 6.1.0 Triển khai engine kiểu để tạo CSS và tên lớp.
 * @access private
 *
 * @param WP_Block_Type $block_type       Loại block.
 * @param array         $block_attributes Thuộc tính block.
 * @return array Các lớp CSS và kiểu inline viền.
 */
function wp_apply_border_support( $block_type, $block_attributes ) {
	if ( wp_should_skip_block_supports_serialization( $block_type, 'border' ) ) {
		return array();
	}

	$border_block_styles      = array();
	$has_border_color_support = wp_has_border_feature_support( $block_type, 'color' );
	$has_border_width_support = wp_has_border_feature_support( $block_type, 'width' );

	// Bán kính viền.
	if (
		wp_has_border_feature_support( $block_type, 'radius' ) &&
		isset( $block_attributes['style']['border']['radius'] ) &&
		! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'radius' )
	) {
		$border_radius = $block_attributes['style']['border']['radius'];

		if ( is_numeric( $border_radius ) ) {
			$border_radius .= 'px';
		}

		$border_block_styles['radius'] = $border_radius;
	}

	// Kiểu viền.
	if (
		wp_has_border_feature_support( $block_type, 'style' ) &&
		isset( $block_attributes['style']['border']['style'] ) &&
		! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'style' )
	) {
		$border_block_styles['style'] = $block_attributes['style']['border']['style'];
	}

	// Độ rộng viền.
	if (
		$has_border_width_support &&
		isset( $block_attributes['style']['border']['width'] ) &&
		! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'width' )
	) {
		$border_width = $block_attributes['style']['border']['width'];

		// Kiểm tra này xử lý triển khai không đơn vị ban đầu.
		if ( is_numeric( $border_width ) ) {
			$border_width .= 'px';
		}

		$border_block_styles['width'] = $border_width;
	}

	// Màu viền.
	if (
		$has_border_color_support &&
		! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'color' )
	) {
		$preset_border_color          = array_key_exists( 'borderColor', $block_attributes ) ? "var:preset|color|{$block_attributes['borderColor']}" : null;
		$custom_border_color          = isset( $block_attributes['style']['border']['color'] ) ? $block_attributes['style']['border']['color'] : null;
		$border_block_styles['color'] = $preset_border_color ? $preset_border_color : $custom_border_color;
	}

	// Tạo kiểu cho từng cạnh viền riêng lẻ.
	if ( $has_border_color_support || $has_border_width_support ) {
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$border                       = isset( $block_attributes['style']['border'][ $side ] ) ? $block_attributes['style']['border'][ $side ] : null;
			$border_side_values           = array(
				'width' => isset( $border['width'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'width' ) ? $border['width'] : null,
				'color' => isset( $border['color'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'color' ) ? $border['color'] : null,
				'style' => isset( $border['style'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'style' ) ? $border['style'] : null,
			);
			$border_block_styles[ $side ] = $border_side_values;
		}
	}

	// Thu thập các lớp CSS và kiểu.
	$attributes = array();
	$styles     = wp_style_engine_get_styles( array( 'border' => $border_block_styles ) );

	if ( ! empty( $styles['classnames'] ) ) {
		$attributes['class'] = $styles['classnames'];
	}

	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}

	return $attributes;
}

/**
 * Kiểm tra xem loại block hiện tại có hỗ trợ tính năng viền được yêu cầu hay không.
 *
 * Nếu cờ hỗ trợ `__experimentalBorder` là boolean `true` thì tất cả các tính năng
 * hỗ trợ viền đều khả dụng. Nếu không, cờ hỗ trợ cụ thể của tính năng nằm bên
 * trong `experimentalBorder` phải được bật để tính năng đó được kích hoạt.
 *
 * @since 5.8.0
 * @access private
 *
 * @param WP_Block_Type $block_type    Loại block để kiểm tra hỗ trợ.
 * @param string        $feature       Tên tính năng để kiểm tra hỗ trợ.
 * @param mixed         $default_value Giá trị dự phòng cho hỗ trợ tính năng, mặc định là false.
 * @return bool Tính năng có được hỗ trợ hay không.
 */
function wp_has_border_feature_support( $block_type, $feature, $default_value = false ) {
	// Kiểm tra xem tất cả tính năng hỗ trợ viền đã được kích hoạt qua `"__experimentalBorder": true` chưa.
	if ( $block_type instanceof WP_Block_Type ) {
		$block_type_supports_border = isset( $block_type->supports['__experimentalBorder'] )
			? $block_type->supports['__experimentalBorder']
			: $default_value;
		if ( true === $block_type_supports_border ) {
			return true;
		}
	}

	// Kiểm tra xem tính năng cụ thể đã được kích hoạt riêng lẻ
	// qua cờ lồng nhau trong `__experimentalBorder` chưa.
	return block_has_support( $block_type, array( '__experimentalBorder', $feature ), $default_value );
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'border',
	array(
		'register_attribute' => 'wp_register_border_support',
		'apply'              => 'wp_apply_border_support',
	)
);
