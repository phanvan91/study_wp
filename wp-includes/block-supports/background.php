<?php
/**
 * Cờ hỗ trợ nền cho block.
 *
 * @package WordPress
 * @since 6.4.0
 */

/**
 * Đăng ký thuộc tính kiểu cho các loại block hỗ trợ nó.
 *
 * @since 6.4.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_background_support( $block_type ) {
	// Thiết lập thuộc tính và kiểu bên trong nếu cần.
	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	// Kiểm tra định nghĩa thuộc tính style đã có sẵn, ví dụ từ block.json.
	if ( array_key_exists( 'style', $block_type->attributes ) ) {
		return;
	}

	$has_background_support = block_has_support( $block_type, array( 'background' ), false );

	if ( $has_background_support ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}
}

/**
 * Render các kiểu nền cho wrapper của block.
 * Hỗ trợ block này sử dụng hook `render_block` để đảm bảo rằng
 * nó cũng được áp dụng cho các block không được render phía server.
 *
 * @since 6.4.0
 * @since 6.5.0 Thêm hỗ trợ xuất `backgroundPosition` và `backgroundRepeat`.
 * @since 6.6.0 Bỏ yêu cầu `backgroundImage.source`. File/url là mặc định.
 * @since 6.7.0 Thêm hỗ trợ xuất `backgroundAttachment`.
 *
 * @access private
 *
 * @param  string $block_content Nội dung block đã được render.
 * @param  array  $block         Đối tượng block.
 * @return string Nội dung block đã được lọc.
 */
function wp_render_background_support( $block_content, $block ) {
	$block_type                   = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	$block_attributes             = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();
	$has_background_image_support = block_has_support( $block_type, array( 'background', 'backgroundImage' ), false );

	if (
		! $has_background_image_support ||
		wp_should_skip_block_supports_serialization( $block_type, 'background', 'backgroundImage' ) ||
		! isset( $block_attributes['style']['background'] )
	) {
		return $block_content;
	}

	$background_styles                         = array();
	$background_styles['backgroundImage']      = $block_attributes['style']['background']['backgroundImage'] ?? null;
	$background_styles['backgroundSize']       = $block_attributes['style']['background']['backgroundSize'] ?? null;
	$background_styles['backgroundPosition']   = $block_attributes['style']['background']['backgroundPosition'] ?? null;
	$background_styles['backgroundRepeat']     = $block_attributes['style']['background']['backgroundRepeat'] ?? null;
	$background_styles['backgroundAttachment'] = $block_attributes['style']['background']['backgroundAttachment'] ?? null;

	if ( ! empty( $background_styles['backgroundImage'] ) ) {
		$background_styles['backgroundSize'] = $background_styles['backgroundSize'] ?? 'cover';

		// Nếu kích thước nền được đặt là `contain` và không có vị trí nào được đặt, đặt vị trí thành `center`.
		if ( 'contain' === $background_styles['backgroundSize'] && ! $background_styles['backgroundPosition'] ) {
			$background_styles['backgroundPosition'] = '50% 50%';
		}
	}

	$styles = wp_style_engine_get_styles( array( 'background' => $background_styles ) );

	if ( ! empty( $styles['css'] ) ) {
		// Chèn kiểu nền vào phần tử đầu tiên, giả sử đó là wrapper, nếu nó tồn tại.
		$tags = new WP_HTML_Tag_Processor( $block_content );

		if ( $tags->next_tag() ) {
			$existing_style = $tags->get_attribute( 'style' );
			$updated_style  = '';

			if ( ! empty( $existing_style ) ) {
				$updated_style = $existing_style;
				if ( ! str_ends_with( $existing_style, ';' ) ) {
					$updated_style .= ';';
				}
			}

			$updated_style .= $styles['css'];
			$tags->set_attribute( 'style', $updated_style );
			$tags->add_class( 'has-background' );
		}

		return $tags->get_updated_html();
	}

	return $block_content;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'background',
	array(
		'register_attribute' => 'wp_register_background_support',
	)
);

add_filter( 'render_block', 'wp_render_background_support', 10, 2 );
