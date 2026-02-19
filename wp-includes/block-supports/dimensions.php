<?php
/**
 * Cờ hỗ trợ kích thước cho block.
 *
 * Phần này không bao gồm hỗ trợ block `spacing` mặc dù về mặt giao diện
 * nó xuất hiện dưới panel "Kích thước" trong trình soạn thảo. Nó vẫn nằm trong
 * file `spacing.php` gốc để tương thích với core.
 *
 * @package WordPress
 * @since 5.9.0
 */

/**
 * Đăng ký thuộc tính kiểu cho các loại block hỗ trợ nó.
 *
 * @since 5.9.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_dimensions_support( $block_type ) {
	// Thiết lập thuộc tính và kiểu bên trong nếu cần.
	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	// Kiểm tra định nghĩa thuộc tính style đã có sẵn, ví dụ từ block.json.
	if ( array_key_exists( 'style', $block_type->attributes ) ) {
		return;
	}

	$has_dimensions_support = block_has_support( $block_type, 'dimensions', false );

	if ( $has_dimensions_support ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}
}

/**
 * Thêm các lớp CSS cho kích thước block vào mảng thuộc tính đầu vào.
 * Điều này sẽ được áp dụng cho markup block ở giao diện người dùng.
 *
 * @since 5.9.0
 * @since 6.2.0 Thêm hỗ trợ `minHeight`.
 * @access private
 *
 * @param WP_Block_Type $block_type       Loại Block.
 * @param array         $block_attributes Thuộc tính block.
 * @return array Các lớp CSS và kiểu inline kích thước block.
 */
function wp_apply_dimensions_support( $block_type, $block_attributes ) {
	if ( wp_should_skip_block_supports_serialization( $block_type, 'dimensions' ) ) {
		return array();
	}

	$attributes = array();

	// Hỗ trợ chiều rộng sẽ được thêm trong tương lai gần.

	$has_min_height_support = block_has_support( $block_type, array( 'dimensions', 'minHeight' ), false );
	$block_styles           = isset( $block_attributes['style'] ) ? $block_attributes['style'] : null;

	if ( ! $block_styles ) {
		return $attributes;
	}

	$skip_min_height                      = wp_should_skip_block_supports_serialization( $block_type, 'dimensions', 'minHeight' );
	$dimensions_block_styles              = array();
	$dimensions_block_styles['minHeight'] = null;
	if ( $has_min_height_support && ! $skip_min_height ) {
		$dimensions_block_styles['minHeight'] = isset( $block_styles['dimensions']['minHeight'] )
			? $block_styles['dimensions']['minHeight']
			: null;
	}
	$styles = wp_style_engine_get_styles( array( 'dimensions' => $dimensions_block_styles ) );

	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}

	return $attributes;
}

/**
 * Render các kiểu kích thước phía server cho wrapper của block.
 * Hỗ trợ block này sử dụng hook `render_block` để đảm bảo rằng
 * nó cũng được áp dụng cho các block không được render phía server.
 *
 * @since 6.5.0
 * @access private
 *
 * @param  string $block_content Nội dung block đã được render.
 * @param  array  $block         Đối tượng block.
 * @return string                Nội dung block đã được lọc.
 */
function wp_render_dimensions_support( $block_content, $block ) {
	$block_type               = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	$block_attributes         = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();
	$has_aspect_ratio_support = block_has_support( $block_type, array( 'dimensions', 'aspectRatio' ), false );

	if (
		! $has_aspect_ratio_support ||
		wp_should_skip_block_supports_serialization( $block_type, 'dimensions', 'aspectRatio' )
	) {
		return $block_content;
	}

	$dimensions_block_styles                = array();
	$dimensions_block_styles['aspectRatio'] = $block_attributes['style']['dimensions']['aspectRatio'] ?? null;

	// Để đảm bảo tỷ lệ khung hình không bị ghi đè bởi `minHeight`, bỏ thiết lập bất kỳ quy tắc nào đã tồn tại.
	if (
		isset( $dimensions_block_styles['aspectRatio'] )
	) {
		$dimensions_block_styles['minHeight'] = 'unset';
	} elseif (
		isset( $block_attributes['style']['dimensions']['minHeight'] ) ||
		isset( $block_attributes['minHeight'] )
	) {
		$dimensions_block_styles['aspectRatio'] = 'unset';
	}

	$styles = wp_style_engine_get_styles( array( 'dimensions' => $dimensions_block_styles ) );

	if ( ! empty( $styles['css'] ) ) {
		// Chèn kiểu kích thước vào phần tử đầu tiên, giả sử đó là wrapper, nếu nó tồn tại.
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

			if ( ! empty( $styles['classnames'] ) ) {
				foreach ( explode( ' ', $styles['classnames'] ) as $class_name ) {
					if (
						str_contains( $class_name, 'aspect-ratio' ) &&
						! isset( $block_attributes['style']['dimensions']['aspectRatio'] )
					) {
						continue;
					}
					$tags->add_class( $class_name );
				}
			}
		}

		return $tags->get_updated_html();
	}

	return $block_content;
}

add_filter( 'render_block', 'wp_render_dimensions_support', 10, 2 );

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'dimensions',
	array(
		'register_attribute' => 'wp_register_dimensions_support',
		'apply'              => 'wp_apply_dimensions_support',
	)
);
