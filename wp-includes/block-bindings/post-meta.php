<?php
/**
 * Nguồn Post Meta cho liên kết khối (block bindings).
 *
 * @since 6.5.0
 * @package WordPress
 * @subpackage Block Bindings
 */

/**
 * Lấy giá trị cho nguồn Post Meta.
 *
 * @since 6.5.0
 * @access private
 *
 * @param array    $source_args    Mảng chứa các tham số nguồn dùng để tra cứu giá trị ghi đè.
 *                                 Ví dụ: array( "key" => "foo" ).
 * @param WP_Block $block_instance Thể hiện của khối.
 * @return mixed Giá trị được tính toán cho nguồn.
 */
function _block_bindings_post_meta_get_value( array $source_args, $block_instance ) {
	if ( empty( $source_args['key'] ) ) {
		return null;
	}

	if ( empty( $block_instance->context['postId'] ) ) {
		return null;
	}
	$post_id = $block_instance->context['postId'];

	// Nếu bài viết không công khai, cần ngăn người dùng không được phép truy cập post meta.
	$post = get_post( $post_id );
	if ( ( ! is_post_publicly_viewable( $post ) && ! current_user_can( 'read_post', $post_id ) ) || post_password_required( $post ) ) {
		return null;
	}

	// Kiểm tra xem trường meta có được bảo vệ không.
	if ( is_protected_meta( $source_args['key'], 'post' ) ) {
		return null;
	}

	// Kiểm tra xem trường meta có được đăng ký hiển thị trong REST không.
	$meta_keys = get_registered_meta_keys( 'post', $block_instance->context['postType'] );
	// Thêm các trường được đăng ký cho tất cả các kiểu phụ.
	$meta_keys = array_merge( $meta_keys, get_registered_meta_keys( 'post', '' ) );
	if ( empty( $meta_keys[ $source_args['key'] ]['show_in_rest'] ) ) {
		return null;
	}

	return get_post_meta( $post_id, $source_args['key'], true );
}

/**
 * Đăng ký nguồn Post Meta trong registry liên kết khối.
 *
 * @since 6.5.0
 * @access private
 */
function _register_block_bindings_post_meta_source() {
	register_block_bindings_source(
		'core/post-meta',
		array(
			'label'              => _x( 'Post Meta', 'block bindings source' ),
			'get_value_callback' => '_block_bindings_post_meta_get_value',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
}

add_action( 'init', '_register_block_bindings_post_meta_source' );
