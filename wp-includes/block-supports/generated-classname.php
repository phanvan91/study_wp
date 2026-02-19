<?php
/**
 * Cờ hỗ trợ tên lớp được tạo tự động cho block.
 *
 * @package WordPress
 * @since 5.6.0
 */

/**
 * Lấy tên lớp được tạo tự động từ tên block cho trước.
 *
 * @since 5.6.0
 *
 * @access private
 *
 * @param string $block_name Tên Block.
 * @return string Tên lớp được tạo tự động.
 */
function wp_get_block_default_classname( $block_name ) {
	// Các lớp HTML được tạo cho block tuân theo quy tắc đặt tên `wp-block-{name}`.
	// Các block do WordPress cung cấp sẽ bỏ tiền tố 'core/' hoặc 'core-' (được sử dụng trước đây trong 'core-embed/').
	$classname = 'wp-block-' . preg_replace(
		'/^core-/',
		'',
		str_replace( '/', '-', $block_name )
	);

	/**
	 * Lọc tên lớp mặc định cho các block được render phía server.
	 *
	 * @since 5.6.0
	 *
	 * @param string $class_name Tên lớp hiện đang được áp dụng.
	 * @param string $block_name Tên block.
	 */
	$classname = apply_filters( 'block_default_classname', $classname, $block_name );

	return $classname;
}

/**
 * Thêm các tên lớp được tạo tự động vào đầu ra.
 *
 * @since 5.6.0
 *
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 * @return array Các lớp CSS và kiểu inline của block.
 */
function wp_apply_generated_classname_support( $block_type ) {
	$attributes                      = array();
	$has_generated_classname_support = block_has_support( $block_type, 'className', true );
	if ( $has_generated_classname_support ) {
		$block_classname = wp_get_block_default_classname( $block_type->name );

		if ( $block_classname ) {
			$attributes['class'] = $block_classname;
		}
	}

	return $attributes;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'generated-classname',
	array(
		'apply' => 'wp_apply_generated_classname_support',
	)
);
