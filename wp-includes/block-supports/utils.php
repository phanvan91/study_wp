<?php
/**
 * Các hàm tiện ích hỗ trợ block.
 *
 * @package WordPress
 * @subpackage Block Supports
 * @since 6.0.0
 */

/**
 * Kiểm tra xem việc tuần tự hóa các thuộc tính được hỗ trợ của block hiện tại
 * có nên xảy ra hay không.
 *
 * @since 6.0.0
 * @access private
 *
 * @param WP_Block_Type $block_type  Loại block.
 * @param string        $feature_set Tên tập tính năng hỗ trợ block.
 * @param string        $feature     Tên tùy chọn của tính năng cụ thể để kiểm tra.
 *
 * @return bool Có nên tuần tự hóa các kiểu và lớp CSS hỗ trợ block hay không.
 */
function wp_should_skip_block_supports_serialization( $block_type, $feature_set, $feature = null ) {
	if ( ! is_object( $block_type ) || ! $feature_set ) {
		return false;
	}

	$path               = array( $feature_set, '__experimentalSkipSerialization' );
	$skip_serialization = _wp_array_get( $block_type->supports, $path, false );

	if ( is_array( $skip_serialization ) ) {
		return in_array( $feature, $skip_serialization, true );
	}

	return $skip_serialization;
}
