<?php
/**
 * Cờ hỗ trợ khoảng cách cho block.
 *
 * Để tương thích ngược, phần này được giữ riêng biệt với dimensions.php
 * mặc dù cả hai đều thuộc cùng một panel trong trình soạn thảo.
 *
 * @package WordPress
 * @since 5.8.0
 */

/**
 * Đăng ký thuộc tính kiểu cho các loại block hỗ trợ nó.
 *
 * @since 5.8.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_spacing_support( $block_type ) {
	$has_spacing_support = block_has_support( $block_type, 'spacing', false );

	// Thiết lập thuộc tính và kiểu bên trong nếu cần.
	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( $has_spacing_support && ! array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}
}

/**
 * Thêm các lớp CSS cho khoảng cách block vào mảng thuộc tính đầu vào.
 * Điều này sẽ được áp dụng cho markup block ở giao diện người dùng.
 *
 * @since 5.8.0
 * @since 6.1.0 Triển khai engine kiểu để tạo CSS và tên lớp.
 * @access private
 *
 * @param WP_Block_Type $block_type       Loại Block.
 * @param array         $block_attributes Thuộc tính block.
 * @return array Các lớp CSS và kiểu inline khoảng cách block.
 */
function wp_apply_spacing_support( $block_type, $block_attributes ) {
	if ( wp_should_skip_block_supports_serialization( $block_type, 'spacing' ) ) {
		return array();
	}

	$attributes          = array();
	$has_padding_support = block_has_support( $block_type, array( 'spacing', 'padding' ), false );
	$has_margin_support  = block_has_support( $block_type, array( 'spacing', 'margin' ), false );
	$block_styles        = isset( $block_attributes['style'] ) ? $block_attributes['style'] : null;

	if ( ! $block_styles ) {
		return $attributes;
	}

	$skip_padding         = wp_should_skip_block_supports_serialization( $block_type, 'spacing', 'padding' );
	$skip_margin          = wp_should_skip_block_supports_serialization( $block_type, 'spacing', 'margin' );
	$spacing_block_styles = array(
		'padding' => null,
		'margin'  => null,
	);
	if ( $has_padding_support && ! $skip_padding ) {
		$spacing_block_styles['padding'] = isset( $block_styles['spacing']['padding'] ) ? $block_styles['spacing']['padding'] : null;
	}
	if ( $has_margin_support && ! $skip_margin ) {
		$spacing_block_styles['margin'] = isset( $block_styles['spacing']['margin'] ) ? $block_styles['spacing']['margin'] : null;
	}
	$styles = wp_style_engine_get_styles( array( 'spacing' => $spacing_block_styles ) );

	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}

	return $attributes;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'spacing',
	array(
		'register_attribute' => 'wp_register_spacing_support',
		'apply'              => 'wp_apply_spacing_support',
	)
);
