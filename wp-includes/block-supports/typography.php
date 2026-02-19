<?php
/**
 * Cờ hỗ trợ kiểu chữ cho block.
 *
 * @package WordPress
 * @since 5.6.0
 */

/**
 * Đăng ký thuộc tính kiểu và kiểu chữ cho các loại block hỗ trợ nó.
 *
 * @since 5.6.0
 * @since 6.3.0 Thêm hỗ trợ text-columns.
 * @access private
 *
 * @param WP_Block_Type $block_type Loại Block.
 */
function wp_register_typography_support( $block_type ) {
	if ( ! ( $block_type instanceof WP_Block_Type ) ) {
		return;
	}

	$typography_supports = isset( $block_type->supports['typography'] ) ? $block_type->supports['typography'] : false;
	if ( ! $typography_supports ) {
		return;
	}

	$has_font_family_support     = isset( $typography_supports['__experimentalFontFamily'] ) ? $typography_supports['__experimentalFontFamily'] : false;
	$has_font_size_support       = isset( $typography_supports['fontSize'] ) ? $typography_supports['fontSize'] : false;
	$has_font_style_support      = isset( $typography_supports['__experimentalFontStyle'] ) ? $typography_supports['__experimentalFontStyle'] : false;
	$has_font_weight_support     = isset( $typography_supports['__experimentalFontWeight'] ) ? $typography_supports['__experimentalFontWeight'] : false;
	$has_letter_spacing_support  = isset( $typography_supports['__experimentalLetterSpacing'] ) ? $typography_supports['__experimentalLetterSpacing'] : false;
	$has_line_height_support     = isset( $typography_supports['lineHeight'] ) ? $typography_supports['lineHeight'] : false;
	$has_text_align_support      = isset( $typography_supports['textAlign'] ) ? $typography_supports['textAlign'] : false;
	$has_text_columns_support    = isset( $typography_supports['textColumns'] ) ? $typography_supports['textColumns'] : false;
	$has_text_decoration_support = isset( $typography_supports['__experimentalTextDecoration'] ) ? $typography_supports['__experimentalTextDecoration'] : false;
	$has_text_transform_support  = isset( $typography_supports['__experimentalTextTransform'] ) ? $typography_supports['__experimentalTextTransform'] : false;
	$has_writing_mode_support    = isset( $typography_supports['__experimentalWritingMode'] ) ? $typography_supports['__experimentalWritingMode'] : false;

	$has_typography_support = $has_font_family_support
		|| $has_font_size_support
		|| $has_font_style_support
		|| $has_font_weight_support
		|| $has_letter_spacing_support
		|| $has_line_height_support
		|| $has_text_align_support
		|| $has_text_columns_support
		|| $has_text_decoration_support
		|| $has_text_transform_support
		|| $has_writing_mode_support;

	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( $has_typography_support && ! array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}

	if ( $has_font_size_support && ! array_key_exists( 'fontSize', $block_type->attributes ) ) {
		$block_type->attributes['fontSize'] = array(
			'type' => 'string',
		);
	}

	if ( $has_font_family_support && ! array_key_exists( 'fontFamily', $block_type->attributes ) ) {
		$block_type->attributes['fontFamily'] = array(
			'type' => 'string',
		);
	}
}

/**
 * Thêm các lớp CSS và kiểu inline cho các tính năng kiểu chữ như cỡ chữ
 * vào mảng thuộc tính đầu vào. Điều này sẽ được áp dụng cho markup block
 * ở giao diện người dùng.
 *
 * @since 5.6.0
 * @since 6.1.0 Sử dụng engine kiểu để tạo CSS và tên lớp.
 * @since 6.3.0 Thêm hỗ trợ text-columns.
 * @access private
 *
 * @param WP_Block_Type $block_type       Loại block.
 * @param array         $block_attributes Thuộc tính block.
 * @return array Các lớp CSS và kiểu inline kiểu chữ.
 */
function wp_apply_typography_support( $block_type, $block_attributes ) {
	if ( ! ( $block_type instanceof WP_Block_Type ) ) {
		return array();
	}

	$typography_supports = isset( $block_type->supports['typography'] )
		? $block_type->supports['typography']
		: false;
	if ( ! $typography_supports ) {
		return array();
	}

	if ( wp_should_skip_block_supports_serialization( $block_type, 'typography' ) ) {
		return array();
	}

	$has_font_family_support     = isset( $typography_supports['__experimentalFontFamily'] ) ? $typography_supports['__experimentalFontFamily'] : false;
	$has_font_size_support       = isset( $typography_supports['fontSize'] ) ? $typography_supports['fontSize'] : false;
	$has_font_style_support      = isset( $typography_supports['__experimentalFontStyle'] ) ? $typography_supports['__experimentalFontStyle'] : false;
	$has_font_weight_support     = isset( $typography_supports['__experimentalFontWeight'] ) ? $typography_supports['__experimentalFontWeight'] : false;
	$has_letter_spacing_support  = isset( $typography_supports['__experimentalLetterSpacing'] ) ? $typography_supports['__experimentalLetterSpacing'] : false;
	$has_line_height_support     = isset( $typography_supports['lineHeight'] ) ? $typography_supports['lineHeight'] : false;
	$has_text_align_support      = isset( $typography_supports['textAlign'] ) ? $typography_supports['textAlign'] : false;
	$has_text_columns_support    = isset( $typography_supports['textColumns'] ) ? $typography_supports['textColumns'] : false;
	$has_text_decoration_support = isset( $typography_supports['__experimentalTextDecoration'] ) ? $typography_supports['__experimentalTextDecoration'] : false;
	$has_text_transform_support  = isset( $typography_supports['__experimentalTextTransform'] ) ? $typography_supports['__experimentalTextTransform'] : false;
	$has_writing_mode_support    = isset( $typography_supports['__experimentalWritingMode'] ) ? $typography_supports['__experimentalWritingMode'] : false;

	// Có bỏ qua các tính năng hỗ trợ block riêng lẻ hay không.
	$should_skip_font_size       = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontSize' );
	$should_skip_font_family     = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontFamily' );
	$should_skip_font_style      = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontStyle' );
	$should_skip_font_weight     = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontWeight' );
	$should_skip_line_height     = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'lineHeight' );
	$should_skip_text_align      = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'textAlign' );
	$should_skip_text_columns    = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'textColumns' );
	$should_skip_text_decoration = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'textDecoration' );
	$should_skip_text_transform  = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'textTransform' );
	$should_skip_letter_spacing  = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'letterSpacing' );
	$should_skip_writing_mode    = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'writingMode' );

	$typography_block_styles = array();
	if ( $has_font_size_support && ! $should_skip_font_size ) {
		$preset_font_size                    = array_key_exists( 'fontSize', $block_attributes )
			? "var:preset|font-size|{$block_attributes['fontSize']}"
			: null;
		$custom_font_size                    = isset( $block_attributes['style']['typography']['fontSize'] )
			? $block_attributes['style']['typography']['fontSize']
			: null;
		$typography_block_styles['fontSize'] = $preset_font_size ? $preset_font_size : wp_get_typography_font_size_value(
			array(
				'size' => $custom_font_size,
			)
		);
	}

	if ( $has_font_family_support && ! $should_skip_font_family ) {
		$preset_font_family                    = array_key_exists( 'fontFamily', $block_attributes )
			? "var:preset|font-family|{$block_attributes['fontFamily']}"
			: null;
		$custom_font_family                    = isset( $block_attributes['style']['typography']['fontFamily'] )
			? wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['fontFamily'], 'font-family' )
			: null;
		$typography_block_styles['fontFamily'] = $preset_font_family ? $preset_font_family : $custom_font_family;
	}

	if (
		$has_font_style_support &&
		! $should_skip_font_style &&
		isset( $block_attributes['style']['typography']['fontStyle'] )
	) {
		$typography_block_styles['fontStyle'] = wp_typography_get_preset_inline_style_value(
			$block_attributes['style']['typography']['fontStyle'],
			'font-style'
		);
	}

	if (
		$has_font_weight_support &&
		! $should_skip_font_weight &&
		isset( $block_attributes['style']['typography']['fontWeight'] )
	) {
		$typography_block_styles['fontWeight'] = wp_typography_get_preset_inline_style_value(
			$block_attributes['style']['typography']['fontWeight'],
			'font-weight'
		);
	}

	if ( $has_line_height_support && ! $should_skip_line_height ) {
		$typography_block_styles['lineHeight'] = isset( $block_attributes['style']['typography']['lineHeight'] )
			? $block_attributes['style']['typography']['lineHeight']
			: null;
	}

	if ( $has_text_align_support && ! $should_skip_text_align ) {
		$typography_block_styles['textAlign'] = isset( $block_attributes['style']['typography']['textAlign'] )
			? $block_attributes['style']['typography']['textAlign']
			: null;
	}

	if ( $has_text_columns_support && ! $should_skip_text_columns && isset( $block_attributes['style']['typography']['textColumns'] ) ) {
		$typography_block_styles['textColumns'] = isset( $block_attributes['style']['typography']['textColumns'] )
			? $block_attributes['style']['typography']['textColumns']
			: null;
	}

	if (
		$has_text_decoration_support &&
		! $should_skip_text_decoration &&
		isset( $block_attributes['style']['typography']['textDecoration'] )
	) {
		$typography_block_styles['textDecoration'] = wp_typography_get_preset_inline_style_value(
			$block_attributes['style']['typography']['textDecoration'],
			'text-decoration'
		);
	}

	if (
		$has_text_transform_support &&
		! $should_skip_text_transform &&
		isset( $block_attributes['style']['typography']['textTransform'] )
	) {
		$typography_block_styles['textTransform'] = wp_typography_get_preset_inline_style_value(
			$block_attributes['style']['typography']['textTransform'],
			'text-transform'
		);
	}

	if (
		$has_letter_spacing_support &&
		! $should_skip_letter_spacing &&
		isset( $block_attributes['style']['typography']['letterSpacing'] )
	) {
		$typography_block_styles['letterSpacing'] = wp_typography_get_preset_inline_style_value(
			$block_attributes['style']['typography']['letterSpacing'],
			'letter-spacing'
		);
	}

	if ( $has_writing_mode_support &&
		! $should_skip_writing_mode &&
		isset( $block_attributes['style']['typography']['writingMode'] )
	) {
		$typography_block_styles['writingMode'] = isset( $block_attributes['style']['typography']['writingMode'] )
			? $block_attributes['style']['typography']['writingMode']
			: null;
	}

	$attributes = array();
	$classnames = array();
	$styles     = wp_style_engine_get_styles(
		array( 'typography' => $typography_block_styles ),
		array( 'convert_vars_to_classnames' => true )
	);

	if ( ! empty( $styles['classnames'] ) ) {
		$classnames[] = $styles['classnames'];
	}

	if ( $has_text_align_support && ! $should_skip_text_align && isset( $block_attributes['style']['typography']['textAlign'] ) ) {
		$classnames[] = 'has-text-align-' . $block_attributes['style']['typography']['textAlign'];
	}

	if ( ! empty( $classnames ) ) {
		$attributes['class'] = implode( ' ', $classnames );
	}

	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}

	return $attributes;
}

/**
 * Tạo giá trị kiểu inline cho một tính năng kiểu chữ, ví dụ trang trí văn bản,
 * biến đổi văn bản, và kiểu chữ.
 *
 * Lưu ý: Hàm này dùng để tương thích ngược.
 * * Cần thiết để phân tích các block cũ có kiểu chữ chứa thiết lập sẵn.
 * * Chủ yếu thay thế hàm đã lỗi thời `wp_typography_get_css_variable_inline_style()`,
 *   nhưng bỏ qua việc biên dịch khai báo CSS vì engine kiểu đảm nhận vai trò này.
 * @link https://github.com/wordpress/gutenberg/pull/27555
 *
 * @since 6.1.0
 *
 * @param string $style_value  Giá trị kiểu thô cho một tính năng kiểu chữ đơn từ thuộc tính style của block.
 * @param string $css_property Slug cho thuộc tính CSS mà kiểu inline thiết lập.
 * @return string Giá trị kiểu inline CSS.
 */
function wp_typography_get_preset_inline_style_value( $style_value, $css_property ) {
	// Nếu giá trị kiểu không phải biến CSS thiết lập sẵn thì không xử lý tiếp.
	if ( empty( $style_value ) || ! str_contains( $style_value, "var:preset|{$css_property}|" ) ) {
		return $style_value;
	}

	/*
	 * Để tương thích ngược.
	 * Thiết lập sẵn đã bị loại bỏ trong WordPress/gutenberg#27555.
	 * Biến CSS thiết lập sẵn chính là kiểu.
	 * Lấy giá trị kiểu từ chuỗi và trả về kiểu CSS.
	 */
	$index_to_splice = strrpos( $style_value, '|' ) + 1;
	$slug            = _wp_to_kebab_case( substr( $style_value, $index_to_splice ) );

	// Trả về giá trị kiểu inline CSS thực tế,
	// ví dụ `var(--wp--preset--text-decoration--underline);`.
	return sprintf( 'var(--wp--preset--%s--%s);', $css_property, $slug );
}

/**
 * Render các kiểu/nội dung kiểu chữ cho wrapper của block.
 *
 * @since 6.1.0
 *
 * @param string $block_content Nội dung block đã được render.
 * @param array  $block         Đối tượng block.
 * @return string Nội dung block đã được lọc.
 */
function wp_render_typography_support( $block_content, $block ) {
	if ( ! isset( $block['attrs']['style']['typography']['fontSize'] ) ) {
		return $block_content;
	}

	$custom_font_size = $block['attrs']['style']['typography']['fontSize'];
	$fluid_font_size  = wp_get_typography_font_size_value( array( 'size' => $custom_font_size ) );

	/*
	 * Kiểm tra xem $fluid_font_size không khớp với $custom_font_size,
	 * có nghĩa là nó đã bị thay đổi bởi các hàm cỡ chữ linh hoạt.
	 */
	if ( ! empty( $fluid_font_size ) && $fluid_font_size !== $custom_font_size ) {
		// Thay thế phiên bản đầu tiên của `font-size:$custom_font_size` bằng `font-size:$fluid_font_size`.
		return preg_replace( '/font-size\s*:\s*' . preg_quote( $custom_font_size, '/' ) . '\s*;?/', 'font-size:' . esc_attr( $fluid_font_size ) . ';', $block_content, 1 );
	}

	return $block_content;
}

/**
 * Kiểm tra chuỗi để tìm đơn vị và giá trị, trả về mảng
 * gồm `'value'` và `'unit'`, ví dụ array( '42', 'rem' ).
 *
 * @since 6.1.0
 *
 * @param string|int|float $raw_value Giá trị kích thước thô từ theme.json.
 * @param array            $options   {
 *     Tùy chọn. Mảng liên kết các tùy chọn. Mặc định là mảng rỗng.
 *
 *     @type string   $coerce_to        Ép kiểu giá trị sang rem hoặc px. Mặc định `'rem'`.
 *     @type int      $root_size_value  Giá trị cỡ chữ gốc cho chuyển đổi rem|em <-> px. Mặc định `16`.
 *     @type string[] $acceptable_units Mảng các đơn vị cỡ chữ. Mặc định `array( 'rem', 'px', 'em' )`;
 * }
 * @return array|null Mảng gồm thuộc tính `'value'` và `'unit'` khi thành công.
 *                    `null` khi thất bại.
 */
function wp_get_typography_value_and_unit( $raw_value, $options = array() ) {
	if ( ! is_string( $raw_value ) && ! is_int( $raw_value ) && ! is_float( $raw_value ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			__( 'Raw size value must be a string, integer, or float.' ),
			'6.1.0'
		);
		return null;
	}

	if ( empty( $raw_value ) ) {
		return null;
	}

	// Mặc định chuyển đổi số sang giá trị pixel.
	if ( is_numeric( $raw_value ) ) {
		$raw_value = $raw_value . 'px';
	}

	$defaults = array(
		'coerce_to'        => '',
		'root_size_value'  => 16,
		'acceptable_units' => array( 'rem', 'px', 'em' ),
	);

	$options = wp_parse_args( $options, $defaults );

	$acceptable_units_group = implode( '|', $options['acceptable_units'] );
	$pattern                = '/^(\d*\.?\d+)(' . $acceptable_units_group . '){1,1}$/';

	preg_match( $pattern, $raw_value, $matches );

	// Thoát nếu không phải giá trị số và đơn vị px hoặc rem.
	if ( ! isset( $matches[1] ) || ! isset( $matches[2] ) ) {
		return null;
	}

	$value = $matches[1];
	$unit  = $matches[2];

	/*
	 * Cỡ chữ mặc định của trình duyệt. Sau này, có thể chèn JS để
	 * tính toán `getComputedStyle( document.querySelector( "html" ) ).fontSize`.
	 */
	if ( 'px' === $options['coerce_to'] && ( 'em' === $unit || 'rem' === $unit ) ) {
		$value = $value * $options['root_size_value'];
		$unit  = $options['coerce_to'];
	}

	if ( 'px' === $unit && ( 'em' === $options['coerce_to'] || 'rem' === $options['coerce_to'] ) ) {
		$value = $value / $options['root_size_value'];
		$unit  = $options['coerce_to'];
	}

	/*
	 * Chưa cần tính toán nếu chuyển đổi giữa em và rem,
	 * vì chúng ta giả sử giá trị kích thước gốc. Sau này có thể muốn phân biệt
	 * giữa cỡ chữ :root (rem) và cỡ chữ phần tử cha (em).
	 */
	if ( ( 'em' === $options['coerce_to'] || 'rem' === $options['coerce_to'] ) && ( 'em' === $unit || 'rem' === $unit ) ) {
		$unit = $options['coerce_to'];
	}

	return array(
		'value' => round( $value, 3 ),
		'unit'  => $unit,
	);
}

/**
 * Triển khai nội bộ CSS clamp() dựa trên chiều rộng viewport tối thiểu/tối đa
 * có sẵn và cỡ chữ tối thiểu/tối đa.
 *
 * @since 6.1.0
 * @since 6.3.0 Kiểm tra giá trị viewport tối thiểu/tối đa không được hỗ trợ gây ra giá trị clamp không hợp lệ.
 * @since 6.5.0 Trả về sớm khi phép trừ viewport tối thiểu và tối đa bằng không để tránh chia cho không.
 * @access private
 *
 * @param array $args {
 *     Tùy chọn. Mảng liên kết các giá trị để tính công thức linh hoạt
 *     cho cỡ chữ. Mặc định là mảng rỗng.
 *
 *     @type string $maximum_viewport_width Kích thước tối đa mà kiểu chữ sẽ có tính linh hoạt.
 *     @type string $minimum_viewport_width Kích thước viewport tối thiểu mà kiểu chữ sẽ có tính linh hoạt.
 *     @type string $maximum_font_size      Cỡ chữ tối đa cho bất kỳ phép tính clamp() nào.
 *     @type string $minimum_font_size      Cỡ chữ tối thiểu cho bất kỳ phép tính clamp() nào.
 *     @type int    $scale_factor           Hệ số tỷ lệ để xác định tốc độ chữ thay đổi kích thước trong giới hạn.
 * }
 * @return string|null Giá trị cỡ chữ sử dụng clamp() khi thành công, ngược lại null.
 */
function wp_get_computed_fluid_typography_value( $args = array() ) {
	$maximum_viewport_width_raw = isset( $args['maximum_viewport_width'] ) ? $args['maximum_viewport_width'] : null;
	$minimum_viewport_width_raw = isset( $args['minimum_viewport_width'] ) ? $args['minimum_viewport_width'] : null;
	$maximum_font_size_raw      = isset( $args['maximum_font_size'] ) ? $args['maximum_font_size'] : null;
	$minimum_font_size_raw      = isset( $args['minimum_font_size'] ) ? $args['minimum_font_size'] : null;
	$scale_factor               = isset( $args['scale_factor'] ) ? $args['scale_factor'] : null;

	// Chuẩn hóa cỡ chữ tối thiểu để sử dụng giá trị cho các phép tính.
	$minimum_font_size = wp_get_typography_value_and_unit( $minimum_font_size_raw );

	/*
	 * Lấy đơn vị 'ưu tiên' để giữ đơn vị nhất quán khi tính toán,
	 * nếu không kết quả sẽ không chính xác.
	 */
	$font_size_unit = isset( $minimum_font_size['unit'] ) ? $minimum_font_size['unit'] : 'rem';

	// Chuẩn hóa cỡ chữ tối đa để sử dụng giá trị cho các phép tính.
	$maximum_font_size = wp_get_typography_value_and_unit(
		$maximum_font_size_raw,
		array(
			'coerce_to' => $font_size_unit,
		)
	);

	// Kiểm tra kích thước tối thiểu và tối đa bắt buộc, và bảo vệ chống lại đơn vị không được hỗ trợ.
	if ( ! $maximum_font_size || ! $minimum_font_size ) {
		return null;
	}

	// Sử dụng rem cho khả năng co giãn cỡ chữ mục tiêu linh hoạt dễ tiếp cận.
	$minimum_font_size_rem = wp_get_typography_value_and_unit(
		$minimum_font_size_raw,
		array(
			'coerce_to' => 'rem',
		)
	);

	// Chiều rộng viewport được định nghĩa cho kiểu chữ linh hoạt. Chuẩn hóa đơn vị.
	$maximum_viewport_width = wp_get_typography_value_and_unit(
		$maximum_viewport_width_raw,
		array(
			'coerce_to' => $font_size_unit,
		)
	);
	$minimum_viewport_width = wp_get_typography_value_and_unit(
		$minimum_viewport_width_raw,
		array(
			'coerce_to' => $font_size_unit,
		)
	);

	// Bảo vệ chống lại đơn vị không được hỗ trợ trong chiều rộng viewport tối thiểu và tối đa.
	if ( ! $minimum_viewport_width || ! $maximum_viewport_width ) {
		return null;
	}

	// Tính mẫu số hệ số tuyến tính. Nếu bằng 0, không thể tính giá trị linh hoạt.
	$linear_factor_denominator = $maximum_viewport_width['value'] - $minimum_viewport_width['value'];
	if ( empty( $linear_factor_denominator ) ) {
		return null;
	}

	/*
	 * Xây dựng quy tắc CSS.
	 * Tham khảo từ https://websemantics.uk/tools/responsive-font-calculator/.
	 */
	$view_port_width_offset = round( $minimum_viewport_width['value'] / 100, 3 ) . $font_size_unit;
	$linear_factor          = 100 * ( ( $maximum_font_size['value'] - $minimum_font_size['value'] ) / ( $linear_factor_denominator ) );
	$linear_factor_scaled   = round( $linear_factor * $scale_factor, 3 );
	$linear_factor_scaled   = empty( $linear_factor_scaled ) ? 1 : $linear_factor_scaled;
	$fluid_target_font_size = implode( '', $minimum_font_size_rem ) . " + ((1vw - $view_port_width_offset) * $linear_factor_scaled)";

	return "clamp($minimum_font_size_raw, $fluid_target_font_size, $maximum_font_size_raw)";
}

/**
 * Trả về giá trị cỡ chữ dựa trên thiết lập sẵn cỡ chữ cho trước.
 * Tính đến các tham số kiểu chữ linh hoạt và cố gắng trả về công thức CSS
 * dựa trên các giá trị khả dụng, hợp lệ.
 *
 * @since 6.1.0
 * @since 6.1.1 Điều chỉnh quy tắc cho cỡ chữ tối thiểu và tối đa.
 * @since 6.2.0 Thêm hỗ trợ 'settings.typography.fluid.minFontSize'.
 * @since 6.3.0 Sử dụng layout.wideSize làm chiều rộng viewport tối đa, và hệ số tỷ lệ logarit để tính tỷ lệ chữ tối thiểu.
 * @since 6.4.0 Thêm giá trị chiều rộng viewport tối thiểu và tối đa có thể cấu hình vào schema typography.fluid của theme.json.
 * @since 6.6.0 Đánh dấu lỗi thời tham số bool $should_use_fluid_typography.
 * @since 6.7.0 Các thiết lập sẵn cỡ chữ có thể bật kiểu chữ linh hoạt riêng lẻ, ngay cả khi nó bị tắt toàn cục.
 *
 * @param array      $preset   {
 *     Bắt buộc. Giá trị thiết lập sẵn fontSizes như trong theme.json.
 *
 *     @type string           $name Tên của thiết lập sẵn cỡ chữ.
 *     @type string           $slug Định danh duy nhất dạng kebab-case cho thiết lập sẵn cỡ chữ.
 *     @type string|int|float $size Giá trị cỡ chữ CSS, bao gồm đơn vị nếu có.
 * }
 * @param bool|array $settings Tùy chọn. Mảng cài đặt Theme JSON ghi đè bất kỳ cài đặt theme toàn cục nào.
 *                             Mặc định là false.
 * @return string|null Giá trị cỡ chữ hoặc null nếu kích thước không được truyền trong $preset.
 */


function wp_get_typography_font_size_value( $preset, $settings = array() ) {
	if ( ! isset( $preset['size'] ) ) {
		return null;
	}

	/*
	 * Bắt các giá trị falsy và 0/'0'. Không thể thực hiện phép tính linh hoạt trên `0`.
	 * Cũng trả về sớm khi thiết lập sẵn cỡ chữ vô hiệu hóa rõ ràng kiểu chữ linh hoạt bằng `false`.
	 */
	$fluid_font_size_settings = $preset['fluid'] ?? null;
	if ( false === $fluid_font_size_settings || empty( $preset['size'] ) ) {
		return $preset['size'];
	}

	/*
	 * Dưới dạng boolean (đã lỗi thời từ 6.6), $settings hoạt động như ghi đè để bật (`true`) hoặc tắt (`false`) kiểu chữ linh hoạt.
	 */
	if ( is_bool( $settings ) ) {
		_deprecated_argument( __FUNCTION__, '6.6.0', __( '`boolean` type for second argument `$settings` is deprecated. Use `array()` instead.' ) );
		$settings = array(
			'typography' => array(
				'fluid' => $settings,
			),
		);
	}

	// Dự phòng về cài đặt toàn cục làm mặc định.
	$global_settings = wp_get_global_settings();
	$settings        = wp_parse_args(
		$settings,
		$global_settings
	);

	$typography_settings = $settings['typography'] ?? array();

	/*
	 * Trả về sớm khi kiểu chữ linh hoạt bị tắt trong cài đặt, và không có
	 * cài đặt cục bộ nào để bật nó cho thiết lập sẵn riêng lẻ.
	 *
	 * Nếu điều kiện này không được đáp ứng, nghĩa là cài đặt hoặc cài đặt
	 * thiết lập sẵn riêng lẻ đã bật kiểu chữ linh hoạt.
	 */
	if ( empty( $typography_settings['fluid'] ) && empty( $fluid_font_size_settings ) ) {
		return $preset['size'];
	}

	$fluid_settings  = isset( $typography_settings['fluid'] ) ? $typography_settings['fluid'] : array();
	$layout_settings = isset( $settings['layout'] ) ? $settings['layout'] : array();

	// Giá trị mặc định.
	$default_maximum_viewport_width       = '1600px';
	$default_minimum_viewport_width       = '320px';
	$default_minimum_font_size_factor_max = 0.75;
	$default_minimum_font_size_factor_min = 0.25;
	$default_scale_factor                 = 1;
	$default_minimum_font_size_limit      = '14px';

	// Ghi đè giá trị mặc định.
	$minimum_viewport_width = isset( $fluid_settings['minViewportWidth'] ) ? $fluid_settings['minViewportWidth'] : $default_minimum_viewport_width;
	$maximum_viewport_width = isset( $layout_settings['wideSize'] ) && ! empty( wp_get_typography_value_and_unit( $layout_settings['wideSize'] ) ) ? $layout_settings['wideSize'] : $default_maximum_viewport_width;
	if ( isset( $fluid_settings['maxViewportWidth'] ) ) {
		$maximum_viewport_width = $fluid_settings['maxViewportWidth'];
	}
	$has_min_font_size       = isset( $fluid_settings['minFontSize'] ) && ! empty( wp_get_typography_value_and_unit( $fluid_settings['minFontSize'] ) );
	$minimum_font_size_limit = $has_min_font_size ? $fluid_settings['minFontSize'] : $default_minimum_font_size_limit;

	// Cố gắng lấy cỡ chữ linh hoạt tối thiểu và tối đa rõ ràng.
	$minimum_font_size_raw = isset( $fluid_font_size_settings['min'] ) ? $fluid_font_size_settings['min'] : null;
	$maximum_font_size_raw = isset( $fluid_font_size_settings['max'] ) ? $fluid_font_size_settings['max'] : null;

	// Cỡ chữ.
	$preferred_size = wp_get_typography_value_and_unit( $preset['size'] );

	// Bảo vệ chống lại các đơn vị không được hỗ trợ.
	if ( empty( $preferred_size['unit'] ) ) {
		return $preset['size'];
	}

	/*
	 * Chuẩn hóa giới hạn cỡ chữ tối thiểu theo đơn vị đầu vào,
	 * để thực hiện các kiểm tra so sánh.
	 */
	$minimum_font_size_limit = wp_get_typography_value_and_unit(
		$minimum_font_size_limit,
		array(
			'coerce_to' => $preferred_size['unit'],
		)
	);

	// Không ép buộc cỡ chữ tối thiểu nếu cỡ chữ đã đặt rõ ràng giá trị tối thiểu và tối đa.
	if ( ! empty( $minimum_font_size_limit ) && ( ! $minimum_font_size_raw && ! $maximum_font_size_raw ) ) {
		/*
		 * Nếu kích thước tối thiểu không được truyền vào hàm này
		 * và cỡ chữ do người dùng định nghĩa nhỏ hơn $minimum_font_size_limit,
		 * không tính giá trị linh hoạt.
		 */
		if ( $preferred_size['value'] <= $minimum_font_size_limit['value'] ) {
			return $preset['size'];
		}
	}

	// Nếu không có cỡ chữ linh hoạt tối đa, sử dụng giá trị đầu vào.
	if ( ! $maximum_font_size_raw ) {
		$maximum_font_size_raw = $preferred_size['value'] . $preferred_size['unit'];
	}

	/*
	 * Nếu không có minimumFontSize được cung cấp, tạo một giá trị sử dụng
	 * cỡ chữ cho trước nhân với hệ số tỷ lệ cỡ chữ tối thiểu.
	 */
	if ( ! $minimum_font_size_raw ) {
		$preferred_font_size_in_px = 'px' === $preferred_size['unit'] ? $preferred_size['value'] : $preferred_size['value'] * 16;

		/*
		 * Hệ số tỷ lệ là một bội số ảnh hưởng đến tốc độ đường cong tiến về giá trị tối thiểu,
		 * tức là tốc độ hệ số kích thước đạt 0 khi giá trị cỡ chữ tăng.
		 * Với a - b * log2(), giá trị b thấp hơn sẽ làm đường cong tiến về tối thiểu nhanh hơn.
		 * Hệ số tỷ lệ bị giới hạn giữa giá trị tối thiểu và tối đa.
		 */
		$minimum_font_size_factor     = min( max( 1 - 0.075 * log( $preferred_font_size_in_px, 2 ), $default_minimum_font_size_factor_min ), $default_minimum_font_size_factor_max );
		$calculated_minimum_font_size = round( $preferred_size['value'] * $minimum_font_size_factor, 3 );

		// Chỉ sử dụng cỡ chữ tối thiểu đã tính nếu nó > giá trị $minimum_font_size_limit.
		if ( ! empty( $minimum_font_size_limit ) && $calculated_minimum_font_size <= $minimum_font_size_limit['value'] ) {
			$minimum_font_size_raw = $minimum_font_size_limit['value'] . $minimum_font_size_limit['unit'];
		} else {
			$minimum_font_size_raw = $calculated_minimum_font_size . $preferred_size['unit'];
		}
	}

	$fluid_font_size_value = wp_get_computed_fluid_typography_value(
		array(
			'minimum_viewport_width' => $minimum_viewport_width,
			'maximum_viewport_width' => $maximum_viewport_width,
			'minimum_font_size'      => $minimum_font_size_raw,
			'maximum_font_size'      => $maximum_font_size_raw,
			'scale_factor'           => $default_scale_factor,
		)
	);

	if ( ! empty( $fluid_font_size_value ) ) {
		return $fluid_font_size_value;
	}

	return $preset['size'];
}

// Đăng ký hỗ trợ block.
WP_Block_Supports::get_instance()->register(
	'typography',
	array(
		'register_attribute' => 'wp_register_typography_support',
		'apply'              => 'wp_apply_typography_support',
	)
);
