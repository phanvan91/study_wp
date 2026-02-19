<?php
/**
 * Cờ hỗ trợ bóng đổ cho block.
 *
 * @package WordPress
 * @since 6.3.0
 */

/**
 * Đăng ký thuộc tính kiểu và bóng đổ cho các loại block hỗ trợ nó.
 *
 * @since 6.3.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_shadow_support( $block_type ) {
	$has_shadow_support = block_has_support( $block_type, 'shadow', false );

	if ( ! $has_shadow_support ) {
		return;
	}

	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}

	if ( array_key_exists( 'shadow', $block_type->attributes ) ) {
		$block_type->attributes['shadow'] = array(
			'type' => 'string',
		);
	}
}

/**
 * Thêm các lớp CSS và kiểu inline cho tính năng bóng đổ vào mảng thuộc tính đầu vào.
 * Điều này sẽ được áp dụng cho markup block ở giao diện người dùng.
 *
 * @since 6.3.0
 * @since 6.6.0 Trả về sớm nếu __experimentalSkipSerialization là true.
 * @access private
 *
 * @param  WP_Block_Type $block_type       Loại block.
 * @param  array         $block_attributes Thuộc tính block.
 * @return array Các lớp CSS và kiểu inline bóng đổ.
 */
function wp_apply_shadow_support( $block_type, $block_attributes ) {
	$has_shadow_support = block_has_support( $block_type, 'shadow', false );

	if (
		! $has_shadow_support ||
		wp_should_skip_block_supports_serialization( $block_type, 'shadow' )
	) {
		return array();
	}

	$shadow_block_styles = array();

	$custom_shadow                 = $block_attributes['style']['shadow'] ?? null;
	$shadow_block_styles['shadow'] = $custom_shadow;

	$attributes = array();
	$styles     = wp_style_engine_get_styles( $shadow_block_styles );

	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}

	return $attributes;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'shadow',
	array(
		'register_attribute' => 'wp_register_shadow_support',
		'apply'              => 'wp_apply_shadow_support',
	)
);
