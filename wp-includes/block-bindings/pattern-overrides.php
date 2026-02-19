<?php
/**
 * Nguồn ghi đè mẫu (Pattern Overrides) cho liên kết khối (Block Bindings).
 *
 * @since 6.5.0
 * @package WordPress
 * @subpackage Block Bindings
 */

/**
 * Lấy giá trị cho nguồn ghi đè mẫu (Pattern Overrides).
 *
 * @since 6.5.0
 * @access private
 *
 * @param array    $source_args    Mảng chứa các tham số nguồn dùng để tra cứu giá trị ghi đè.
 *                                 Ví dụ: array( "key" => "foo" ).
 * @param WP_Block $block_instance Thể hiện của khối.
 * @param string   $attribute_name Tên của thuộc tính đích.
 * @return mixed Giá trị được tính toán cho nguồn.
 */
function _block_bindings_pattern_overrides_get_value( array $source_args, $block_instance, string $attribute_name ) {
	if ( empty( $block_instance->attributes['metadata']['name'] ) ) {
		return null;
	}
	$metadata_name = $block_instance->attributes['metadata']['name'];
	return _wp_array_get( $block_instance->context, array( 'pattern/overrides', $metadata_name, $attribute_name ), null );
}

/**
 * Đăng ký nguồn ghi đè mẫu (Pattern Overrides) trong registry liên kết khối.
 *
 * @since 6.5.0
 * @access private
 */
function _register_block_bindings_pattern_overrides_source() {
	register_block_bindings_source(
		'core/pattern-overrides',
		array(
			'label'              => _x( 'Pattern Overrides', 'block bindings source' ),
			'get_value_callback' => '_block_bindings_pattern_overrides_get_value',
			'uses_context'       => array( 'pattern/overrides' ),
		)
	);
}

add_action( 'init', '_register_block_bindings_pattern_overrides_source' );
