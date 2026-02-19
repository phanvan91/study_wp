<?php
/**
 * Cờ hỗ trợ aria-label cho block.
 *
 * @package WordPress
 * @since 6.8.0
 */

/**
 * Đăng ký thuộc tính aria-label cho các loại block hỗ trợ nó.
 *
 * @since 6.8.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_aria_label_support( $block_type ) {
	$has_aria_label_support = block_has_support( $block_type, array( 'ariaLabel' ), false );

	if ( ! $has_aria_label_support ) {
		return;
	}

	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( ! array_key_exists( 'ariaLabel', $block_type->attributes ) ) {
		$block_type->attributes['ariaLabel'] = array(
			'type' => 'string',
		);
	}
}

/**
 * Thêm aria-label vào đầu ra.
 *
 * @since 6.8.0
 * @access private
 *
 * @param WP_Block_Type $block_type       Loại Block.
 * @param array         $block_attributes Thuộc tính block.
 *
 * @return array Aria-label của block.
 */
function wp_apply_aria_label_support( $block_type, $block_attributes ) {
	if ( ! $block_attributes ) {
		return array();
	}

	$has_aria_label_support = block_has_support( $block_type, array( 'ariaLabel' ), false );
	if ( ! $has_aria_label_support ) {
		return array();
	}

	$has_aria_label = array_key_exists( 'ariaLabel', $block_attributes );
	if ( ! $has_aria_label ) {
		return array();
	}
	return array( 'aria-label' => $block_attributes['ariaLabel'] );
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'aria-label',
	array(
		'register_attribute' => 'wp_register_aria_label_support',
		'apply'              => 'wp_apply_aria_label_support',
	)
);
