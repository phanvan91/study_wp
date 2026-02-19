<?php
/**
 * Cờ hỗ trợ tên lớp tùy chỉnh cho block.
 *
 * @package WordPress
 * @since 5.6.0
 */

/**
 * Đăng ký thuộc tính tên lớp tùy chỉnh cho các loại block hỗ trợ nó.
 *
 * @since 5.6.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_custom_classname_support( $block_type ) {
	$has_custom_classname_support = block_has_support( $block_type, 'customClassName', true );

	if ( $has_custom_classname_support ) {
		if ( ! $block_type->attributes ) {
			$block_type->attributes = array();
		}

		if ( ! array_key_exists( 'className', $block_type->attributes ) ) {
			$block_type->attributes['className'] = array(
				'type' => 'string',
			);
		}
	}
}

/**
 * Thêm các tên lớp tùy chỉnh vào đầu ra.
 *
 * @since 5.6.0
 * @access private
 *
 * @param  WP_Block_Type $block_type       Loại Block.
 * @param  array         $block_attributes Thuộc tính block.
 *
 * @return array Các lớp CSS và kiểu inline của block.
 */
function wp_apply_custom_classname_support( $block_type, $block_attributes ) {
	$has_custom_classname_support = block_has_support( $block_type, 'customClassName', true );
	$attributes                   = array();
	if ( $has_custom_classname_support ) {
		$has_custom_classnames = array_key_exists( 'className', $block_attributes );

		if ( $has_custom_classnames ) {
			$attributes['class'] = $block_attributes['className'];
		}
	}

	return $attributes;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'custom-classname',
	array(
		'register_attribute' => 'wp_register_custom_classname_support',
		'apply'              => 'wp_apply_custom_classname_support',
	)
);
