<?php
/**
 * Cờ hỗ trợ căn chỉnh block.
 *
 * @package WordPress
 * @since 5.6.0
 */

/**
 * Đăng ký thuộc tính căn chỉnh block cho các loại block hỗ trợ nó.
 *
 * @since 5.6.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_alignment_support( $block_type ) {
	$has_align_support = block_has_support( $block_type, 'align', false );
	if ( $has_align_support ) {
		if ( ! $block_type->attributes ) {
			$block_type->attributes = array();
		}

		if ( ! array_key_exists( 'align', $block_type->attributes ) ) {
			$block_type->attributes['align'] = array(
				'type' => 'string',
				'enum' => array( 'left', 'center', 'right', 'wide', 'full', '' ),
			);
		}
	}
}

/**
 * Thêm các lớp CSS căn chỉnh block vào mảng thuộc tính đầu vào.
 * Điều này sẽ được áp dụng cho markup block ở giao diện người dùng.
 *
 * @since 5.6.0
 * @access private
 *
 * @param WP_Block_Type $block_type       Loại Block.
 * @param array         $block_attributes Thuộc tính block.
 * @return array Các lớp CSS căn chỉnh block và kiểu inline.
 */
function wp_apply_alignment_support( $block_type, $block_attributes ) {
	$attributes        = array();
	$has_align_support = block_has_support( $block_type, 'align', false );
	if ( $has_align_support ) {
		$has_block_alignment = array_key_exists( 'align', $block_attributes );

		if ( $has_block_alignment ) {
			$attributes['class'] = sprintf( 'align%s', $block_attributes['align'] );
		}
	}

	return $attributes;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'align',
	array(
		'register_attribute' => 'wp_register_alignment_support',
		'apply'              => 'wp_apply_alignment_support',
	)
);
