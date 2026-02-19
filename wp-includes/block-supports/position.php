<?php
/**
 * Cờ hỗ trợ vị trí cho block.
 *
 * @package WordPress
 * @since 6.2.0
 */

/**
 * Đăng ký thuộc tính kiểu cho các loại block hỗ trợ nó.
 *
 * @since 6.2.0
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_position_support( $block_type ) {
	$has_position_support = block_has_support( $block_type, 'position', false );

	// Thiết lập thuộc tính và kiểu bên trong nếu cần.
	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( $has_position_support && ! array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}
}

/**
 * Render các kiểu vị trí cho wrapper của block.
 *
 * @since 6.2.0
 * @access private
 *
 * @param  string $block_content Nội dung block đã được render.
 * @param  array  $block         Đối tượng block.
 * @return string                Nội dung block đã được lọc.
 */
function wp_render_position_support( $block_content, $block ) {
	$block_type           = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	$has_position_support = block_has_support( $block_type, 'position', false );

	if (
		! $has_position_support ||
		empty( $block['attrs']['style']['position'] )
	) {
		return $block_content;
	}

	$global_settings          = wp_get_global_settings();
	$theme_has_sticky_support = isset( $global_settings['position']['sticky'] ) ? $global_settings['position']['sticky'] : false;
	$theme_has_fixed_support  = isset( $global_settings['position']['fixed'] ) ? $global_settings['position']['fixed'] : false;

	// Chỉ cho phép xuất các loại vị trí mà theme hỗ trợ.
	$allowed_position_types = array();
	if ( true === $theme_has_sticky_support ) {
		$allowed_position_types[] = 'sticky';
	}
	if ( true === $theme_has_fixed_support ) {
		$allowed_position_types[] = 'fixed';
	}

	$style_attribute = isset( $block['attrs']['style'] ) ? $block['attrs']['style'] : null;
	$class_name      = wp_unique_id( 'wp-container-' );
	$selector        = ".$class_name";
	$position_styles = array();
	$position_type   = isset( $style_attribute['position']['type'] ) ? $style_attribute['position']['type'] : '';
	$wrapper_classes = array();

	if (
		in_array( $position_type, $allowed_position_types, true )
	) {
		$wrapper_classes[] = $class_name;
		$wrapper_classes[] = 'is-position-' . $position_type;
		$sides             = array( 'top', 'right', 'bottom', 'left' );

		foreach ( $sides as $side ) {
			$side_value = isset( $style_attribute['position'][ $side ] ) ? $style_attribute['position'][ $side ] : null;
			if ( null !== $side_value ) {
				/*
				 * Đối với vị trí top cố định hoặc dính,
				 * đảm bảo giá trị bao gồm khoảng bù cho thanh quản trị khi đã đăng nhập.
				 */
				if (
					'top' === $side &&
					( 'fixed' === $position_type || 'sticky' === $position_type )
				) {
					// Đảm bảo giá trị 0 có thể được sử dụng trong các phép tính `calc()`.
					if ( '0' === $side_value || 0 === $side_value ) {
						$side_value = '0px';
					}

					// Đảm bảo giá trị cạnh hiện tại cũng tính đến chiều cao của thanh quản trị khi đã đăng nhập.
					$side_value = "calc($side_value + var(--wp-admin--admin-bar--position-offset, 0px))";
				}

				$position_styles[] =
					array(
						'selector'     => $selector,
						'declarations' => array(
							$side => $side_value,
						),
					);
			}
		}

		$position_styles[] =
			array(
				'selector'     => $selector,
				'declarations' => array(
					'position' => $position_type,
					'z-index'  => '10',
				),
			);
	}

	if ( ! empty( $position_styles ) ) {
		/*
		 * Thêm vào kho engine kiểu để nạp và render các kiểu vị trí.
		 */
		wp_style_engine_get_stylesheet_from_css_rules(
			$position_styles,
			array(
				'context'  => 'block-supports',
				'prettify' => false,
			)
		);

		// Chèn tên lớp vào markup container block.
		$content = new WP_HTML_Tag_Processor( $block_content );
		$content->next_tag();
		foreach ( $wrapper_classes as $class ) {
			$content->add_class( $class );
		}
		return (string) $content;
	}

	return $block_content;
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'position',
	array(
		'register_attribute' => 'wp_register_position_support',
	)
);
add_filter( 'render_block', 'wp_render_position_support', 10, 2 );
