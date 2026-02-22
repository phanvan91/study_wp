<?php
/**
 * API WordPress cho hiển thị media.
 *
 * @package WordPress
 * @subpackage Media
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Lấy các kích thước hình ảnh bổ sung.
 *
 * @since 4.7.0
 *
 * @global array $_wp_additional_image_sizes
 *
 * @return array Dữ liệu kích thước hình ảnh bổ sung.
 */
function wp_get_additional_image_sizes() {
	global $_wp_additional_image_sizes;

	if ( ! $_wp_additional_image_sizes ) {
		$_wp_additional_image_sizes = array();
	}

	return $_wp_additional_image_sizes;
}

/**
 * Thu nhỏ kích thước mặc định của hình ảnh.
 *
 * Điều này giúp hình ảnh phù hợp hơn với trình soạn thảo và theme.
 *
 * Tham số `$size` chấp nhận mảng hoặc chuỗi. Các giá trị chuỗi được hỗ trợ
 * là 'thumb' hoặc 'thumbnail' cho kích thước ảnh thu nhỏ đã cho hoặc mặc định
 * 128 chiều rộng và 96 chiều cao tính bằng pixel. Cũng hỗ trợ giá trị chuỗi
 * 'medium', 'medium_large' và 'full'. 'full' thực tế không được hỗ trợ, nhưng bất kỳ giá trị
 * nào khác ngoài các giá trị được hỗ trợ sẽ dùng kích thước content_width hoặc 500 nếu
 * chưa được thiết lập.
 *
 * Cuối cùng, có một bộ lọc tên {@see 'editor_max_image_size'}, sẽ được gọi
 * trên mảng đã tính toán cho chiều rộng và chiều cao tương ứng.
 *
 * @since 2.5.0
 *
 * @global int $content_width
 *
 * @param int          $width   Chiều rộng của hình ảnh tính bằng pixel.
 * @param int          $height  Chiều cao của hình ảnh tính bằng pixel.
 * @param string|int[] $size    Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                              giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'medium'.
 * @param string       $context Tùy chọn. Có thể là 'display' (như trong theme) hoặc 'edit'
 *                              (như chèn vào trình soạn thảo). Mặc định null.
 * @return int[] {
 *     Mảng các giá trị chiều rộng và chiều cao.
 *
 *     @type int $0 Chiều rộng tối đa tính bằng pixel.
 *     @type int $1 Chiều cao tối đa tính bằng pixel.
 * }
 */
function image_constrain_size_for_editor( $width, $height, $size = 'medium', $context = null ) {
	global $content_width;

	$_wp_additional_image_sizes = wp_get_additional_image_sizes();

	if ( ! $context ) {
		$context = is_admin() ? 'edit' : 'display';
	}

	if ( is_array( $size ) ) {
		$max_width  = $size[0];
		$max_height = $size[1];
	} elseif ( 'thumb' === $size || 'thumbnail' === $size ) {
		$max_width  = (int) get_option( 'thumbnail_size_w' );
		$max_height = (int) get_option( 'thumbnail_size_h' );
		// Kích thước ảnh thu nhỏ mặc định cơ hội cuối cùng.
		if ( ! $max_width && ! $max_height ) {
			$max_width  = 128;
			$max_height = 96;
		}
	} elseif ( 'medium' === $size ) {
		$max_width  = (int) get_option( 'medium_size_w' );
		$max_height = (int) get_option( 'medium_size_h' );

	} elseif ( 'medium_large' === $size ) {
		$max_width  = (int) get_option( 'medium_large_size_w' );
		$max_height = (int) get_option( 'medium_large_size_h' );

		if ( (int) $content_width > 0 ) {
			$max_width = min( (int) $content_width, $max_width );
		}
	} elseif ( 'large' === $size ) {
		/*
		 * Chúng ta đang chèn hình ảnh kích thước lớn vào trình soạn thảo. Nếu đó là hình ảnh
		 * thực sự lớn, chúng ta sẽ thu nhỏ nó để vừa hợp lý trong trình soạn thảo,
		 * và trong chiều rộng nội dung của theme nếu biết. Người dùng
		 * có thể thay đổi kích thước trong trình soạn thảo nếu muốn.
		 */
		$max_width  = (int) get_option( 'large_size_w' );
		$max_height = (int) get_option( 'large_size_h' );

		if ( (int) $content_width > 0 ) {
			$max_width = min( (int) $content_width, $max_width );
		}
	} elseif ( ! empty( $_wp_additional_image_sizes ) && in_array( $size, array_keys( $_wp_additional_image_sizes ), true ) ) {
		$max_width  = (int) $_wp_additional_image_sizes[ $size ]['width'];
		$max_height = (int) $_wp_additional_image_sizes[ $size ]['height'];
		// Chỉ trong admin. Giả định rằng tác giả theme biết họ đang làm gì.
		if ( (int) $content_width > 0 && 'edit' === $context ) {
			$max_width = min( (int) $content_width, $max_width );
		}
	} else { // $size === 'full' không có ràng buộc.
		$max_width  = $width;
		$max_height = $height;
	}

	/**
	 * Lọc kích thước hình ảnh tối đa cho trình soạn thảo.
	 *
	 * @since 2.5.0
	 *
	 * @param int[]        $max_image_size {
	 *     Mảng các giá trị chiều rộng và chiều cao.
	 *
	 *     @type int $0 Chiều rộng tối đa tính bằng pixel.
	 *     @type int $1 Chiều cao tối đa tính bằng pixel.
	 * }
	 * @param string|int[] $size     Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                               mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param string       $context  Ngữ cảnh mà hình ảnh đang được thay đổi kích thước.
	 *                               Các giá trị có thể là 'display' (như trong theme)
	 *                               hoặc 'edit' (như chèn vào trình soạn thảo).
	 */
	list( $max_width, $max_height ) = apply_filters( 'editor_max_image_size', array( $max_width, $max_height ), $size, $context );

	return wp_constrain_dimensions( $width, $height, $max_width, $max_height );
}

/**
 * Lấy các thuộc tính chiều rộng và chiều cao từ các giá trị chiều rộng và chiều cao đã cho.
 *
 * Cả hai thuộc tính đều bắt buộc theo nghĩa cả hai tham số phải có giá trị,
 * nhưng tùy chọn ở chỗ nếu bạn đặt chúng thành false hoặc null, thì chúng
 * sẽ không được thêm vào chuỗi trả về.
 *
 * Bạn có thể đặt giá trị bằng chuỗi, nhưng chỉ chấp nhận giá trị số.
 * Nếu bạn muốn thêm 'px' sau các số, thì nó sẽ bị loại bỏ khỏi
 * giá trị trả về.
 *
 * @since 2.5.0
 *
 * @param int|string $width  Chiều rộng hình ảnh tính bằng pixel.
 * @param int|string $height Chiều cao hình ảnh tính bằng pixel.
 * @return string Thuộc tính HTML cho chiều rộng và/hoặc chiều cao.
 */
function image_hwstring( $width, $height ) {
	$out = '';
	if ( $width ) {
		$out .= 'width="' . (int) $width . '" ';
	}
	if ( $height ) {
		$out .= 'height="' . (int) $height . '" ';
	}
	return $out;
}

/**
 * Co giãn hình ảnh để vừa với kích thước cụ thể (như 'thumb' hoặc 'medium').
 *
 * URL có thể là hình ảnh gốc, hoặc có thể là phiên bản đã thay đổi kích thước. Hàm
 * này sẽ không tạo bản sao đã thay đổi kích thước mới, mà chỉ trả về bản đã
 * thay đổi kích thước nếu nó tồn tại.
 *
 * Plugin có thể sử dụng bộ lọc {@see 'image_downsize'} để hook vào và cung cấp
 * dịch vụ thay đổi kích thước hình ảnh. Hook phải trả về mảng với các phần tử
 * giống như thường được trả về từ hàm.
 *
 * @since 2.5.0
 *
 * @param int          $id   ID đính kèm cho hình ảnh.
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'medium'.
 * @return array|false {
 *     Mảng dữ liệu hình ảnh, hoặc boolean false nếu không có hình ảnh.
 *
 *     @type string $0 URL nguồn hình ảnh.
 *     @type int    $1 Chiều rộng hình ảnh tính bằng pixel.
 *     @type int    $2 Chiều cao hình ảnh tính bằng pixel.
 *     @type bool   $3 Hình ảnh có phải là ảnh đã thay đổi kích thước hay không.
 * }
 */
function image_downsize( $id, $size = 'medium' ) {
	$is_image = wp_attachment_is_image( $id );

	/**
	 * Lọc việc có bỏ qua đầu ra của image_downsize() hay không.
	 *
	 * Trả về giá trị truthy từ bộ lọc sẽ bỏ qua việc thu nhỏ hình ảnh,
	 * trả về giá trị đó thay thế.
	 *
	 * @since 2.5.0
	 *
	 * @param bool|array   $downsize Có bỏ qua việc thu nhỏ hình ảnh hay không.
	 * @param int          $id       ID đính kèm cho hình ảnh.
	 * @param string|int[] $size     Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                               mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 */
	$out = apply_filters( 'image_downsize', false, $id, $size );

	if ( $out ) {
		return $out;
	}

	$img_url          = wp_get_attachment_url( $id );
	$meta             = wp_get_attachment_metadata( $id );
	$width            = 0;
	$height           = 0;
	$is_intermediate  = false;
	$img_url_basename = wp_basename( $img_url );

	/*
	 * Nếu tệp không phải là hình ảnh, cố gắng thay thế URL của nó bằng hình ảnh được render từ meta.
	 * Nếu không, một loại không phải hình ảnh có thể được trả về.
	 */
	if ( ! $is_image ) {
		if ( ! empty( $meta['sizes']['full'] ) ) {
			$img_url          = str_replace( $img_url_basename, $meta['sizes']['full']['file'], $img_url );
			$img_url_basename = $meta['sizes']['full']['file'];
			$width            = $meta['sizes']['full']['width'];
			$height           = $meta['sizes']['full']['height'];
		} else {
			return false;
		}
	}

	// Thử tìm kích thước trung gian kiểu mới.
	$intermediate = image_get_intermediate_size( $id, $size );

	if ( $intermediate ) {
		$img_url         = str_replace( $img_url_basename, $intermediate['file'], $img_url );
		$width           = $intermediate['width'];
		$height          = $intermediate['height'];
		$is_intermediate = true;
	} elseif ( 'thumbnail' === $size && ! empty( $meta['thumb'] ) && is_string( $meta['thumb'] ) ) {
		// Dùng phương án dự phòng với ảnh thu nhỏ cũ.
		$imagefile = get_attached_file( $id );
		$thumbfile = str_replace( wp_basename( $imagefile ), wp_basename( $meta['thumb'] ), $imagefile );

		if ( file_exists( $thumbfile ) ) {
			$info = wp_getimagesize( $thumbfile );

			if ( $info ) {
				$img_url         = str_replace( $img_url_basename, wp_basename( $thumbfile ), $img_url );
				$width           = $info[0];
				$height          = $info[1];
				$is_intermediate = true;
			}
		}
	}

	if ( ! $width && ! $height && isset( $meta['width'], $meta['height'] ) ) {
		// Bất kỳ loại nào khác: sử dụng hình ảnh thực.
		$width  = $meta['width'];
		$height = $meta['height'];
	}

	if ( $img_url ) {
		// Chúng ta có kích thước hình ảnh thực tế, nhưng có thể cần ràng buộc thêm nếu content_width hẹp hơn.
		list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

		return array( $img_url, $width, $height, $is_intermediate );
	}

	return false;
}

/**
 * Đăng ký kích thước hình ảnh mới.
 *
 * @since 2.9.0
 *
 * @global array $_wp_additional_image_sizes Mảng kết hợp các kích thước hình ảnh bổ sung.
 *
 * @param string     $name   Định danh kích thước hình ảnh.
 * @param int        $width  Tùy chọn. Chiều rộng hình ảnh tính bằng pixel. Mặc định 0.
 * @param int        $height Tùy chọn. Chiều cao hình ảnh tính bằng pixel. Mặc định 0.
 * @param bool|array $crop   {
 *     Tùy chọn. Hành vi cắt xén hình ảnh. Nếu false, hình ảnh sẽ được co giãn (mặc định).
 *     Nếu true, hình ảnh sẽ được cắt theo kích thước chỉ định sử dụng vị trí trung tâm.
 *     Nếu là mảng, hình ảnh sẽ được cắt sử dụng mảng để chỉ định vị trí cắt:
 *
 *     @type string $0 Vị trí cắt theo trục x. Chấp nhận 'left', 'center', hoặc 'right'.
 *     @type string $1 Vị trí cắt theo trục y. Chấp nhận 'top', 'center', hoặc 'bottom'.
 * }
 */
function add_image_size( $name, $width = 0, $height = 0, $crop = false ) {
	global $_wp_additional_image_sizes;

	$_wp_additional_image_sizes[ $name ] = array(
		'width'  => absint( $width ),
		'height' => absint( $height ),
		'crop'   => $crop,
	);
}

/**
 * Kiểm tra xem kích thước hình ảnh có tồn tại không.
 *
 * @since 3.9.0
 *
 * @param string $name Kích thước hình ảnh cần kiểm tra.
 * @return bool True nếu kích thước hình ảnh tồn tại, false nếu không.
 */
function has_image_size( $name ) {
	$sizes = wp_get_additional_image_sizes();
	return isset( $sizes[ $name ] );
}

/**
 * Xóa một kích thước hình ảnh.
 *
 * @since 3.9.0
 *
 * @global array $_wp_additional_image_sizes
 *
 * @param string $name Kích thước hình ảnh cần xóa.
 * @return bool True nếu kích thước hình ảnh đã được xóa thành công, false khi thất bại.
 */
function remove_image_size( $name ) {
	global $_wp_additional_image_sizes;

	if ( isset( $_wp_additional_image_sizes[ $name ] ) ) {
		unset( $_wp_additional_image_sizes[ $name ] );
		return true;
	}

	return false;
}

/**
 * Đăng ký kích thước hình ảnh cho ảnh đại diện bài viết.
 *
 * @since 2.9.0
 *
 * @see add_image_size() để biết chi tiết về hành vi cắt xén.
 *
 * @param int        $width  Chiều rộng hình ảnh tính bằng pixel.
 * @param int        $height Chiều cao hình ảnh tính bằng pixel.
 * @param bool|array $crop   {
 *     Tùy chọn. Hành vi cắt xén hình ảnh. Nếu false, hình ảnh sẽ được co giãn (mặc định).
 *     Nếu true, hình ảnh sẽ được cắt theo kích thước chỉ định sử dụng vị trí trung tâm.
 *     Nếu là mảng, hình ảnh sẽ được cắt sử dụng mảng để chỉ định vị trí cắt:
 *
 *     @type string $0 Vị trí cắt theo trục x. Chấp nhận 'left', 'center', hoặc 'right'.
 *     @type string $1 Vị trí cắt theo trục y. Chấp nhận 'top', 'center', hoặc 'bottom'.
 * }
 */
function set_post_thumbnail_size( $width = 0, $height = 0, $crop = false ) {
	add_image_size( 'post-thumbnail', $width, $height, $crop );
}

/**
 * Lấy thẻ img cho đính kèm hình ảnh, thu nhỏ nếu được yêu cầu.
 *
 * Bộ lọc {@see 'get_image_tag_class'} cho phép thay đổi tên class cho hình ảnh
 * mà không cần sử dụng biểu thức chính quy trên nội dung HTML. Các tham số là:
 * những gì WordPress sẽ sử dụng cho class, ID đính kèm,
 * giá trị căn chỉnh hình ảnh, và kích thước hình ảnh cần hiển thị.
 *
 * Bộ lọc thứ hai, {@see 'get_image_tag'}, có nội dung HTML, sau đó có thể được
 * plugin thao tác thêm để thay đổi tất cả giá trị thuộc tính và cả nội dung HTML.
 *
 * @since 2.5.0
 *
 * @param int          $id    ID đính kèm.
 * @param string       $alt   Mô tả hình ảnh cho thuộc tính alt.
 * @param string       $title Mô tả hình ảnh cho thuộc tính title.
 * @param string       $align Phần của tên class để căn chỉnh hình ảnh.
 * @param string|int[] $size  Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                            giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'medium'.
 * @return string Phần tử HTML IMG cho đính kèm hình ảnh đã cho.
 */
function get_image_tag( $id, $alt, $title, $align, $size = 'medium' ) {

	list( $img_src, $width, $height ) = image_downsize( $id, $size );
	$hwstring                         = image_hwstring( $width, $height );

	$title = $title ? 'title="' . esc_attr( $title ) . '" ' : '';

	$size_class = is_array( $size ) ? implode( 'x', $size ) : $size;
	$class      = 'align' . esc_attr( $align ) . ' size-' . esc_attr( $size_class ) . ' wp-image-' . $id;

	/**
	 * Lọc giá trị thuộc tính class của thẻ hình ảnh đính kèm.
	 *
	 * @since 2.6.0
	 *
	 * @param string       $class Tên class CSS hoặc danh sách các class phân cách bằng dấu cách.
	 * @param int          $id    ID đính kèm.
	 * @param string       $align Phần của tên class để căn chỉnh hình ảnh.
	 * @param string|int[] $size  Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                            mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 */
	$class = apply_filters( 'get_image_tag_class', $class, $id, $align, $size );

	$html = '<img src="' . esc_url( $img_src ) . '" alt="' . esc_attr( $alt ) . '" ' . $title . $hwstring . 'class="' . $class . '" />';

	/**
	 * Lọc nội dung HTML cho thẻ hình ảnh.
	 *
	 * @since 2.6.0
	 *
	 * @param string       $html  Nội dung HTML cho hình ảnh.
	 * @param int          $id    ID đính kèm.
	 * @param string       $alt   Mô tả hình ảnh cho thuộc tính alt.
	 * @param string       $title Mô tả hình ảnh cho thuộc tính title.
	 * @param string       $align Phần của tên class để căn chỉnh hình ảnh.
	 * @param string|int[] $size  Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                            mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 */
	return apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
}

/**
 * Tính toán kích thước mới cho hình ảnh đã thu nhỏ.
 *
 * Nếu chiều rộng hoặc chiều cao rỗng, không có ràng buộc nào được áp dụng
 * cho chiều đó.
 *
 * @since 2.5.0
 *
 * @param int $current_width  Chiều rộng hiện tại của hình ảnh.
 * @param int $current_height Chiều cao hiện tại của hình ảnh.
 * @param int $max_width      Tùy chọn. Chiều rộng tối đa tính bằng pixel để ràng buộc. Mặc định 0.
 * @param int $max_height     Tùy chọn. Chiều cao tối đa tính bằng pixel để ràng buộc. Mặc định 0.
 * @return int[] {
 *     Mảng các giá trị chiều rộng và chiều cao.
 *
 *     @type int $0 Chiều rộng tính bằng pixel.
 *     @type int $1 Chiều cao tính bằng pixel.
 * }
 */
function wp_constrain_dimensions( $current_width, $current_height, $max_width = 0, $max_height = 0 ) {
	if ( ! $max_width && ! $max_height ) {
		return array( $current_width, $current_height );
	}

	$width_ratio  = 1.0;
	$height_ratio = 1.0;
	$did_width    = false;
	$did_height   = false;

	if ( $max_width > 0 && $current_width > 0 && $current_width > $max_width ) {
		$width_ratio = $max_width / $current_width;
		$did_width   = true;
	}

	if ( $max_height > 0 && $current_height > 0 && $current_height > $max_height ) {
		$height_ratio = $max_height / $current_height;
		$did_height   = true;
	}

	// Tính toán tỷ lệ lớn hơn/nhỏ hơn.
	$smaller_ratio = min( $width_ratio, $height_ratio );
	$larger_ratio  = max( $width_ratio, $height_ratio );

	if ( (int) round( $current_width * $larger_ratio ) > $max_width || (int) round( $current_height * $larger_ratio ) > $max_height ) {
		// Tỷ lệ lớn hơn quá lớn. Nó sẽ gây ra tràn.
		$ratio = $smaller_ratio;
	} else {
		// Tỷ lệ lớn hơn vừa vặn, và có khả năng là phù hợp hơn.
		$ratio = $larger_ratio;
	}

	// Kích thước rất nhỏ có thể cho kết quả 0, 1 nên là giá trị tối thiểu.
	$w = max( 1, (int) round( $current_width * $ratio ) );
	$h = max( 1, (int) round( $current_height * $ratio ) );

	/*
	 * Đôi khi, do làm tròn, chúng ta sẽ nhận được kết quả như thế này:
	 * 465x700 trong hộp 177x177 là 117x176... thiếu một pixel.
	 * Chúng ta cũng gặp vấn đề với các cuộc gọi đệ quy tạo ra kết quả thay đổi liên tục.
	 * Ràng buộc theo kết quả của ràng buộc nên cho ra kết quả ban đầu.
	 * Vì vậy chúng ta tìm các kích thước thiếu một pixel so với giá trị tối đa và tăng chúng lên.
	 */

	// Lưu ý: $did_width có nghĩa là có thể $smaller_ratio == $width_ratio.
	if ( $did_width && $w === $max_width - 1 ) {
		$w = $max_width; // Làm tròn lên.
	}

	// Lưu ý: $did_height có nghĩa là có thể $smaller_ratio == $height_ratio.
	if ( $did_height && $h === $max_height - 1 ) {
		$h = $max_height; // Làm tròn lên.
	}

	/**
	 * Lọc kích thước để ràng buộc hình ảnh đã thu nhỏ.
	 *
	 * @since 4.1.0
	 *
	 * @param int[] $dimensions     {
	 *     Mảng các giá trị chiều rộng và chiều cao.
	 *
	 *     @type int $0 Chiều rộng tính bằng pixel.
	 *     @type int $1 Chiều cao tính bằng pixel.
	 * }
	 * @param int   $current_width  Chiều rộng hiện tại của hình ảnh.
	 * @param int   $current_height Chiều cao hiện tại của hình ảnh.
	 * @param int   $max_width      Chiều rộng tối đa cho phép.
	 * @param int   $max_height     Chiều cao tối đa cho phép.
	 */
	return apply_filters( 'wp_constrain_dimensions', array( $w, $h ), $current_width, $current_height, $max_width, $max_height );
}

/**
 * Lấy kích thước thay đổi đã tính toán để sử dụng trong WP_Image_Editor.
 *
 * Tính toán kích thước và tọa độ cho hình ảnh đã thay đổi kích thước
 * vừa trong chiều rộng và chiều cao chỉ định.
 *
 * @since 2.5.0
 *
 * @param int        $orig_w Chiều rộng gốc tính bằng pixel.
 * @param int        $orig_h Chiều cao gốc tính bằng pixel.
 * @param int        $dest_w Chiều rộng mới tính bằng pixel.
 * @param int        $dest_h Chiều cao mới tính bằng pixel.
 * @param bool|array $crop   {
 *     Tùy chọn. Hành vi cắt xén hình ảnh. Nếu false, hình ảnh sẽ được co giãn (mặc định).
 *     Nếu true, hình ảnh sẽ được cắt theo kích thước chỉ định sử dụng vị trí trung tâm.
 *     Nếu là mảng, hình ảnh sẽ được cắt sử dụng mảng để chỉ định vị trí cắt:
 *
 *     @type string $0 Vị trí cắt theo trục x. Chấp nhận 'left', 'center', hoặc 'right'.
 *     @type string $1 Vị trí cắt theo trục y. Chấp nhận 'top', 'center', hoặc 'bottom'.
 * }
 * @return array|false Mảng trả về khớp với các tham số cho `imagecopyresampled()`. False khi thất bại.
 */
function image_resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ) {

	if ( $orig_w <= 0 || $orig_h <= 0 ) {
		return false;
	}
	// Ít nhất một trong $dest_w hoặc $dest_h phải được chỉ định.
	if ( $dest_w <= 0 && $dest_h <= 0 ) {
		return false;
	}

	/**
	 * Lọc việc có bỏ qua tính toán kích thước thay đổi hình ảnh hay không.
	 *
	 * Trả về giá trị non-null từ bộ lọc sẽ bỏ qua
	 * image_resize_dimensions(), trả về giá trị đó thay thế.
	 *
	 * @since 3.4.0
	 *
	 * @param null|mixed $null   Có bỏ qua đầu ra kích thước thay đổi hay không.
	 * @param int        $orig_w Chiều rộng gốc tính bằng pixel.
	 * @param int        $orig_h Chiều cao gốc tính bằng pixel.
	 * @param int        $dest_w Chiều rộng mới tính bằng pixel.
	 * @param int        $dest_h Chiều cao mới tính bằng pixel.
	 * @param bool|array $crop   Có cắt xén hình ảnh theo kích thước chỉ định hay thay đổi kích thước.
	 *                           Mảng có thể chỉ định vị trí vùng cắt. Mặc định false.
	 */
	$output = apply_filters( 'image_resize_dimensions', null, $orig_w, $orig_h, $dest_w, $dest_h, $crop );

	if ( null !== $output ) {
		return $output;
	}

	// Dừng lại nếu kích thước đích lớn hơn kích thước hình ảnh gốc.
	if ( empty( $dest_h ) ) {
		if ( $orig_w < $dest_w ) {
			return false;
		}
	} elseif ( empty( $dest_w ) ) {
		if ( $orig_h < $dest_h ) {
			return false;
		}
	} else {
		if ( $orig_w < $dest_w && $orig_h < $dest_h ) {
			return false;
		}
	}

	if ( $crop ) {
		/*
		 * Cắt phần lớn nhất có thể của hình ảnh gốc mà chúng ta có thể thay đổi kích thước thành $dest_w x $dest_h.
		 * Lưu ý rằng kích thước cắt yêu cầu được sử dụng như hộp giới hạn tối đa cho hình ảnh gốc.
		 * Nếu chiều rộng hoặc chiều cao của hình ảnh gốc nhỏ hơn chiều rộng hoặc chiều cao yêu cầu
		 * thì chỉ chiều lớn hơn sẽ được cắt.
		 * Ví dụ khi hình ảnh gốc là 600x300, và kích thước cắt yêu cầu là 400x400,
		 * hình ảnh kết quả sẽ là 400x300.
		 */
		$aspect_ratio = $orig_w / $orig_h;
		$new_w        = min( $dest_w, $orig_w );
		$new_h        = min( $dest_h, $orig_h );

		if ( ! $new_w ) {
			$new_w = (int) round( $new_h * $aspect_ratio );
		}

		if ( ! $new_h ) {
			$new_h = (int) round( $new_w / $aspect_ratio );
		}

		$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );

		$crop_w = round( $new_w / $size_ratio );
		$crop_h = round( $new_h / $size_ratio );

		if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {
			$crop = array( 'center', 'center' );
		}

		list( $x, $y ) = $crop;

		if ( 'left' === $x ) {
			$s_x = 0;
		} elseif ( 'right' === $x ) {
			$s_x = $orig_w - $crop_w;
		} else {
			$s_x = floor( ( $orig_w - $crop_w ) / 2 );
		}

		if ( 'top' === $y ) {
			$s_y = 0;
		} elseif ( 'bottom' === $y ) {
			$s_y = $orig_h - $crop_h;
		} else {
			$s_y = floor( ( $orig_h - $crop_h ) / 2 );
		}
	} else {
		// Thay đổi kích thước sử dụng $dest_w x $dest_h làm hộp giới hạn tối đa.
		$crop_w = $orig_w;
		$crop_h = $orig_h;

		$s_x = 0;
		$s_y = 0;

		list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
	}

	if ( wp_fuzzy_number_match( $new_w, $orig_w ) && wp_fuzzy_number_match( $new_h, $orig_h ) ) {
		// Kích thước mới thực tế có cùng kích thước với hình ảnh gốc.

		/**
		 * Lọc việc có tiếp tục tạo kích thước phụ hình ảnh với kích thước giống hệt
		 * hình ảnh gốc/nguồn hay không. Chênh lệch 1px có thể do làm tròn và được bỏ qua.
		 *
		 * @since 5.3.0
		 *
		 * @param bool $proceed Giá trị đã lọc.
		 * @param int  $orig_w  Chiều rộng hình ảnh gốc.
		 * @param int  $orig_h  Chiều cao hình ảnh gốc.
		 */
		$proceed = (bool) apply_filters( 'wp_image_resize_identical_dimensions', false, $orig_w, $orig_h );

		if ( ! $proceed ) {
			return false;
		}
	}

	/*
	 * Mảng trả về khớp với các tham số cho imagecopyresampled().
	 * int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
	 */
	return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
}

/**
 * Thay đổi kích thước hình ảnh để tạo ảnh thu nhỏ hoặc kích thước trung gian.
 *
 * Mảng trả về có kích thước tệp, chiều rộng hình ảnh và chiều cao hình ảnh.
 * Bộ lọc {@see 'image_make_intermediate_size'} có thể được sử dụng để hook vào và thay đổi
 * giá trị của mảng trả về. Tham số duy nhất là đường dẫn tệp đã thay đổi kích thước.
 *
 * @since 2.5.0
 *
 * @param string     $file   Đường dẫn tệp.
 * @param int        $width  Chiều rộng hình ảnh.
 * @param int        $height Chiều cao hình ảnh.
 * @param bool|array $crop   {
 *     Tùy chọn. Hành vi cắt xén hình ảnh. Nếu false, hình ảnh sẽ được co giãn (mặc định).
 *     Nếu true, hình ảnh sẽ được cắt theo kích thước chỉ định sử dụng vị trí trung tâm.
 *     Nếu là mảng, hình ảnh sẽ được cắt sử dụng mảng để chỉ định vị trí cắt:
 *
 *     @type string $0 Vị trí cắt theo trục x. Chấp nhận 'left', 'center', hoặc 'right'.
 *     @type string $1 Vị trí cắt theo trục y. Chấp nhận 'top', 'center', hoặc 'bottom'.
 * }
 * @return array|false Mảng metadata khi thành công. False nếu không có hình ảnh nào được tạo.
 */
function image_make_intermediate_size( $file, $width, $height, $crop = false ) {
	if ( $width || $height ) {
		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) || is_wp_error( $editor->resize( $width, $height, $crop ) ) ) {
			return false;
		}

		$resized_file = $editor->save();

		if ( ! is_wp_error( $resized_file ) && $resized_file ) {
			unset( $resized_file['path'] );
			return $resized_file;
		}
	}
	return false;
}

/**
 * Hàm trợ giúp để kiểm tra xem tỷ lệ khung hình của hai hình ảnh có khớp không.
 *
 * @since 4.6.0
 *
 * @param int $source_width  Chiều rộng hình ảnh thứ nhất tính bằng pixel.
 * @param int $source_height Chiều cao hình ảnh thứ nhất tính bằng pixel.
 * @param int $target_width  Chiều rộng hình ảnh thứ hai tính bằng pixel.
 * @param int $target_height Chiều cao hình ảnh thứ hai tính bằng pixel.
 * @return bool True nếu tỷ lệ khung hình khớp trong phạm vi 1px. False nếu không.
 */
function wp_image_matches_ratio( $source_width, $source_height, $target_width, $target_height ) {
	/*
	 * Để kiểm tra các kiểu cắt xén khác nhau, chúng ta ràng buộc kích thước của hình ảnh lớn hơn
	 * theo kích thước của hình ảnh nhỏ hơn và xem chúng có khớp không.
	 */
	if ( $source_width > $target_width ) {
		$constrained_size = wp_constrain_dimensions( $source_width, $source_height, $target_width );
		$expected_size    = array( $target_width, $target_height );
	} else {
		$constrained_size = wp_constrain_dimensions( $target_width, $target_height, $source_width );
		$expected_size    = array( $source_width, $source_height );
	}

	// Nếu kích thước hình ảnh nằm trong phạm vi 1px so với kích thước mong đợi, chúng ta coi là khớp.
	$matched = ( wp_fuzzy_number_match( $constrained_size[0], $expected_size[0] ) && wp_fuzzy_number_match( $constrained_size[1], $expected_size[1] ) );

	return $matched;
}

/**
 * Lấy đường dẫn, chiều rộng và chiều cao kích thước trung gian (đã thay đổi kích thước) của hình ảnh.
 *
 * Tham số $size có thể là mảng với chiều rộng và chiều cao tương ứng.
 * Nếu kích thước khớp với mảng metadata 'sizes' cho chiều rộng và chiều cao, thì nó
 * sẽ được sử dụng. Nếu không có kết quả khớp trực tiếp, thì kích thước hình ảnh gần nhất
 * lớn hơn kích thước chỉ định sẽ được sử dụng. Nếu không tìm thấy gì, hàm
 * sẽ thoát ra và trả về false.
 *
 * Metadata 'sizes' được sử dụng cho các kích thước tương thích có thể được dùng cho
 * giá trị tham số $size.
 *
 * Đường dẫn URL sẽ được cung cấp khi tham số $size là chuỗi.
 *
 * Nếu bạn truyền mảng cho $size, bạn nên cân nhắc sử dụng
 * add_image_size() để phiên bản đã cắt xén được tạo ra. Nó hiệu quả hơn nhiều
 * so với việc phải tìm hình ảnh có kích thước gần nhất rồi để
 * trình duyệt thu nhỏ hình ảnh.
 *
 * @since 2.5.0
 *
 * @param int          $post_id ID đính kèm.
 * @param string|int[] $size    Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                              giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @return array|false {
 *     Mảng đường dẫn tương đối, chiều rộng và chiều cao khi thành công. Ngoài ra bao gồm
 *     đường dẫn tuyệt đối và URL nếu kích thước đã đăng ký được truyền cho tham số `$size`. False khi thất bại.
 *
 *     @type string $file   Tên tệp hình ảnh.
 *     @type int    $width  Chiều rộng hình ảnh tính bằng pixel.
 *     @type int    $height Chiều cao hình ảnh tính bằng pixel.
 *     @type string $path   Đường dẫn hình ảnh tương đối so với thư mục uploads.
 *     @type string $url    URL của hình ảnh.
 * }
 */
function image_get_intermediate_size( $post_id, $size = 'thumbnail' ) {
	$imagedata = wp_get_attachment_metadata( $post_id );

	if ( ! $size || ! is_array( $imagedata ) || empty( $imagedata['sizes'] ) ) {
		return false;
	}

	$data = array();

	// Tìm kết quả khớp tốt nhất khi '$size' là mảng.
	if ( is_array( $size ) ) {
		$candidates = array();

		if ( ! isset( $imagedata['file'] ) && isset( $imagedata['sizes']['full'] ) ) {
			$imagedata['height'] = $imagedata['sizes']['full']['height'];
			$imagedata['width']  = $imagedata['sizes']['full']['width'];
		}

		foreach ( $imagedata['sizes'] as $_size => $data ) {
			// Nếu có kết quả khớp chính xác với kích thước hình ảnh hiện có, bỏ qua.
			if ( (int) $data['width'] === (int) $size[0] && (int) $data['height'] === (int) $size[1] ) {
				$candidates[ $data['width'] * $data['height'] ] = $data;
				break;
			}

			// Nếu không khớp chính xác, xem xét các kích thước lớn hơn với cùng tỷ lệ khung hình.
			if ( $data['width'] >= $size[0] && $data['height'] >= $size[1] ) {
				// Nếu '0' được truyền cho bất kỳ kích thước nào, chúng ta kiểm tra tỷ lệ so với tệp gốc.
				if ( 0 === $size[0] || 0 === $size[1] ) {
					$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $imagedata['width'], $imagedata['height'] );
				} else {
					$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $size[0], $size[1] );
				}

				if ( $same_ratio ) {
					$candidates[ $data['width'] * $data['height'] ] = $data;
				}
			}
		}

		if ( ! empty( $candidates ) ) {
			// Sắp xếp mảng theo kích thước nếu có nhiều hơn một ứng viên.
			if ( 1 < count( $candidates ) ) {
				ksort( $candidates );
			}

			$data = array_shift( $candidates );
			/*
			* Khi kích thước yêu cầu nhỏ hơn kích thước ảnh thu nhỏ, chúng ta
			* quay lại dùng kích thước ảnh thu nhỏ để duy trì tương thích ngược với
			* các phiên bản WordPress trước 4.6.
			*/
		} elseif ( ! empty( $imagedata['sizes']['thumbnail'] ) && $imagedata['sizes']['thumbnail']['width'] >= $size[0] && $imagedata['sizes']['thumbnail']['width'] >= $size[1] ) {
			$data = $imagedata['sizes']['thumbnail'];
		} else {
			return false;
		}

		// Ràng buộc thuộc tính chiều rộng và chiều cao theo giá trị yêu cầu.
		list( $data['width'], $data['height'] ) = image_constrain_size_for_editor( $data['width'], $data['height'], $size );

	} elseif ( ! empty( $imagedata['sizes'][ $size ] ) ) {
		$data = $imagedata['sizes'][ $size ];
	}

	// Nếu tại thời điểm này vẫn không có kết quả khớp, trả về false.
	if ( empty( $data ) ) {
		return false;
	}

	// Bao gồm đường dẫn hệ thống tệp đầy đủ của tệp trung gian.
	if ( empty( $data['path'] ) && ! empty( $data['file'] ) && ! empty( $imagedata['file'] ) ) {
		$file_url     = wp_get_attachment_url( $post_id );
		$data['path'] = path_join( dirname( $imagedata['file'] ), $data['file'] );
		$data['url']  = path_join( dirname( $file_url ), $data['file'] );
	}

	/**
	 * Lọc đầu ra của image_get_intermediate_size()
	 *
	 * @since 4.4.0
	 *
	 * @see image_get_intermediate_size()
	 *
	 * @param array        $data    Mảng đường dẫn tương đối, chiều rộng và chiều cao khi thành công. Cũng có thể bao gồm
	 *                              đường dẫn tuyệt đối và URL.
	 * @param int          $post_id ID của đính kèm hình ảnh.
	 * @param string|int[] $size    Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                              mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 */
	return apply_filters( 'image_get_intermediate_size', $data, $post_id, $size );
}

/**
 * Lấy các tên kích thước hình ảnh trung gian có sẵn.
 *
 * @since 3.0.0
 *
 * @return string[] Mảng các tên kích thước hình ảnh.
 */
function get_intermediate_image_sizes() {
	$default_sizes    = array( 'thumbnail', 'medium', 'medium_large', 'large' );
	$additional_sizes = wp_get_additional_image_sizes();

	if ( ! empty( $additional_sizes ) ) {
		$default_sizes = array_merge( $default_sizes, array_keys( $additional_sizes ) );
	}

	/**
	 * Lọc danh sách các kích thước hình ảnh trung gian.
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $default_sizes Mảng các tên kích thước hình ảnh trung gian. Mặc định
	 *                                là 'thumbnail', 'medium', 'medium_large', 'large'.
	 */
	return apply_filters( 'intermediate_image_sizes', $default_sizes );
}

/**
 * Trả về danh sách đã chuẩn hóa của tất cả kích thước phụ hình ảnh đã đăng ký hiện tại.
 *
 * @since 5.3.0
 * @uses wp_get_additional_image_sizes()
 * @uses get_intermediate_image_sizes()
 *
 * @return array[] Mảng kết hợp các mảng thông tin kích thước phụ hình ảnh,
 *                 được đánh chỉ mục theo tên kích thước hình ảnh.
 */
function wp_get_registered_image_subsizes() {
	$additional_sizes = wp_get_additional_image_sizes();
	$all_sizes        = array();

	foreach ( get_intermediate_image_sizes() as $size_name ) {
		$size_data = array(
			'width'  => 0,
			'height' => 0,
			'crop'   => false,
		);

		if ( isset( $additional_sizes[ $size_name ]['width'] ) ) {
			// Cho các kích thước được thêm bởi plugin và theme.
			$size_data['width'] = (int) $additional_sizes[ $size_name ]['width'];
		} else {
			// Cho các kích thước mặc định được thiết lập trong tùy chọn.
			$size_data['width'] = (int) get_option( "{$size_name}_size_w" );
		}

		if ( isset( $additional_sizes[ $size_name ]['height'] ) ) {
			$size_data['height'] = (int) $additional_sizes[ $size_name ]['height'];
		} else {
			$size_data['height'] = (int) get_option( "{$size_name}_size_h" );
		}

		if ( empty( $size_data['width'] ) && empty( $size_data['height'] ) ) {
			// Kích thước này chưa được thiết lập.
			continue;
		}

		if ( isset( $additional_sizes[ $size_name ]['crop'] ) ) {
			$size_data['crop'] = $additional_sizes[ $size_name ]['crop'];
		} else {
			$size_data['crop'] = get_option( "{$size_name}_crop" );
		}

		if ( ! is_array( $size_data['crop'] ) || empty( $size_data['crop'] ) ) {
			$size_data['crop'] = (bool) $size_data['crop'];
		}

		$all_sizes[ $size_name ] = $size_data;
	}

	return $all_sizes;
}

/**
 * Lấy hình ảnh đại diện cho đính kèm.
 *
 * @since 2.5.0
 *
 * @param int          $attachment_id ID đính kèm hình ảnh.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param bool         $icon          Tùy chọn. Có nên dùng biểu tượng loại MIME làm phương án dự phòng hay không. Mặc định false.
 * @return array|false {
 *     Mảng dữ liệu hình ảnh, hoặc boolean false nếu không có hình ảnh.
 *
 *     @type string $0 URL nguồn hình ảnh.
 *     @type int    $1 Chiều rộng hình ảnh tính bằng pixel.
 *     @type int    $2 Chiều cao hình ảnh tính bằng pixel.
 *     @type bool   $3 Hình ảnh có phải là ảnh đã thay đổi kích thước hay không.
 * }
 */
function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = false ) {
	// Lấy ảnh thu nhỏ hoặc ảnh trung gian nếu có.
	$image = image_downsize( $attachment_id, $size );
	if ( ! $image ) {
		$src = false;

		if ( $icon ) {
			$src = wp_mime_type_icon( $attachment_id, '.svg' );

			if ( $src ) {
				/** Bộ lọc này được ghi chú trong wp-includes/post.php */
				$icon_dir = apply_filters( 'icon_dir', ABSPATH . WPINC . '/images/media' );

				$src_file = $icon_dir . '/' . wp_basename( $src );

				list( $width, $height ) = wp_getimagesize( $src_file );

				$ext = strtolower( substr( $src_file, -4 ) );

				if ( '.svg' === $ext ) {
					// SVG không có kích thước thực, nên gán chiều rộng và chiều cao trực tiếp.
					$width  = 48;
					$height = 64;
				} else {
					list( $width, $height ) = wp_getimagesize( $src_file );
				}
			}
		}

		if ( $src && $width && $height ) {
			$image = array( $src, $width, $height, false );
		}
	}
	/**
	 * Lọc kết quả nguồn hình ảnh đính kèm.
	 *
	 * @since 4.3.0
	 *
	 * @param array|false  $image         {
	 *     Mảng dữ liệu hình ảnh, hoặc boolean false nếu không có hình ảnh.
	 *
	 *     @type string $0 URL nguồn hình ảnh.
	 *     @type int    $1 Chiều rộng hình ảnh tính bằng pixel.
	 *     @type int    $2 Chiều cao hình ảnh tính bằng pixel.
	 *     @type bool   $3 Hình ảnh có phải là ảnh đã thay đổi kích thước hay không.
	 * }
	 * @param int          $attachment_id ID đính kèm hình ảnh.
	 * @param string|int[] $size          Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                                    mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param bool         $icon          Có nên xử lý hình ảnh như biểu tượng hay không.
	 */
	return apply_filters( 'wp_get_attachment_image_src', $image, $attachment_id, $size, $icon );
}

/**
 * Lấy phần tử HTML img đại diện cho đính kèm hình ảnh.
 *
 * Mặc dù `$size` chấp nhận mảng, tốt hơn nên đăng ký kích thước với
 * add_image_size() để phiên bản đã cắt xén được tạo ra. Nó hiệu quả hơn nhiều
 * so với việc phải tìm hình ảnh có kích thước gần nhất rồi để
 * trình duyệt thu nhỏ hình ảnh.
 *
 * @since 2.5.0
 * @since 4.4.0 Thuộc tính `$srcset` và `$sizes` đã được thêm.
 * @since 5.5.0 Thuộc tính `$loading` đã được thêm.
 * @since 6.1.0 Thuộc tính `$decoding` đã được thêm.
 *
 * @param int          $attachment_id ID đính kèm hình ảnh.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param bool         $icon          Tùy chọn. Có nên xử lý hình ảnh như biểu tượng hay không. Mặc định false.
 * @param string|array $attr {
 *     Tùy chọn. Các thuộc tính cho markup hình ảnh.
 *
 *     @type string       $src           URL đính kèm hình ảnh.
 *     @type string       $class         Tên class CSS hoặc danh sách các class phân cách bằng dấu cách.
 *                                       Mặc định `attachment-$size_class size-$size_class`,
 *                                       trong đó `$size_class` là kích thước hình ảnh được yêu cầu.
 *     @type string       $alt           Mô tả hình ảnh cho thuộc tính alt.
 *     @type string       $srcset        Giá trị thuộc tính 'srcset'.
 *     @type string       $sizes         Giá trị thuộc tính 'sizes'.
 *     @type string|false $loading       Giá trị thuộc tính 'loading'. Truyền giá trị false
 *                                       sẽ khiến thuộc tính bị bỏ qua cho hình ảnh.
 *                                       Mặc định được xác định bởi {@see wp_get_loading_optimization_attributes()}.
 *     @type string       $decoding      Giá trị thuộc tính 'decoding'. Các giá trị có thể là
 *                                       'async' (mặc định), 'sync', hoặc 'auto'. Truyền false hoặc chuỗi rỗng
 *                                       sẽ khiến thuộc tính bị bỏ qua.
 *     @type string       $fetchpriority Giá trị thuộc tính 'fetchpriority', là `high`, `low`, hoặc `auto`.
 *                                       Mặc định được xác định bởi {@see wp_get_loading_optimization_attributes()}.
 * }
 * @return string Phần tử HTML img hoặc chuỗi rỗng khi thất bại.
 */
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {
	$html  = '';
	$image = wp_get_attachment_image_src( $attachment_id, $size, $icon );

	if ( $image ) {
		list( $src, $width, $height ) = $image;

		$attachment = get_post( $attachment_id );
		$size_class = $size;

		if ( is_array( $size_class ) ) {
			$size_class = implode( 'x', $size_class );
		}

		$default_attr = array(
			'src'   => $src,
			'class' => "attachment-$size_class size-$size_class",
			'alt'   => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
		);

		/**
		 * Lọc ngữ cảnh mà wp_get_attachment_image() được sử dụng.
		 *
		 * @since 6.3.0
		 *
		 * @param string $context Ngữ cảnh. Mặc định 'wp_get_attachment_image'.
		 */
		$context        = apply_filters( 'wp_get_attachment_image_context', 'wp_get_attachment_image' );
		$attr           = wp_parse_args( $attr, $default_attr );
		$attr['width']  = $width;
		$attr['height'] = $height;

		$loading_optimization_attr = wp_get_loading_optimization_attributes(
			'img',
			$attr,
			$context
		);

		// Thêm thuộc tính tối ưu hóa tải nếu chưa có.
		$attr = array_merge( $attr, $loading_optimization_attr );

		// Bỏ qua thuộc tính `decoding` nếu giá trị không hợp lệ theo đặc tả.
		if ( empty( $attr['decoding'] ) || ! in_array( $attr['decoding'], array( 'async', 'sync', 'auto' ), true ) ) {
			unset( $attr['decoding'] );
		}

		/*
		 * Nếu giá trị mặc định `lazy` của thuộc tính `loading` bị ghi đè
		 * để bỏ qua thuộc tính cho hình ảnh này, đảm bảo nó không được bao gồm.
		 */
		if ( isset( $attr['loading'] ) && ! $attr['loading'] ) {
			unset( $attr['loading'] );
		}

		// Nếu thuộc tính `fetchpriority` bị ghi đè và được đặt thành false hoặc chuỗi rỗng.
		if ( isset( $attr['fetchpriority'] ) && ! $attr['fetchpriority'] ) {
			unset( $attr['fetchpriority'] );
		}

		// Tạo 'srcset' và 'sizes' nếu chưa có.
		if ( empty( $attr['srcset'] ) ) {
			$image_meta = wp_get_attachment_metadata( $attachment_id );

			if ( is_array( $image_meta ) ) {
				$size_array = array( absint( $width ), absint( $height ) );
				$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $attachment_id );
				$sizes      = wp_calculate_image_sizes( $size_array, $src, $image_meta, $attachment_id );

				if ( $srcset && ( $sizes || ! empty( $attr['sizes'] ) ) ) {
					$attr['srcset'] = $srcset;

					if ( empty( $attr['sizes'] ) ) {
						$attr['sizes'] = $sizes;
					}
				}
			}
		}

		/** Bộ lọc này được ghi chú trong wp-includes/media.php */
		$add_auto_sizes = apply_filters( 'wp_img_tag_add_auto_sizes', true );

		// Thêm 'auto' vào thuộc tính sizes nếu áp dụng được.
		if (
			$add_auto_sizes &&
			isset( $attr['loading'] ) &&
			'lazy' === $attr['loading'] &&
			isset( $attr['sizes'] ) &&
			! wp_sizes_attribute_includes_valid_auto( $attr['sizes'] )
		) {
			$attr['sizes'] = 'auto, ' . $attr['sizes'];
		}

		/**
		 * Lọc danh sách thuộc tính hình ảnh đính kèm.
		 *
		 * @since 2.8.0
		 *
		 * @param string[]     $attr       Mảng giá trị thuộc tính cho markup hình ảnh, đánh chỉ mục theo tên thuộc tính.
		 *                                 Xem wp_get_attachment_image().
		 * @param WP_Post      $attachment Bài viết đính kèm hình ảnh.
		 * @param string|int[] $size       Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
		 *                                 mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
		 */
		$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment, $size );

		if ( isset( $attr['height'] ) && is_numeric( $attr['height'] ) ) {
			$height = absint( $attr['height'] );
		}
		if ( isset( $attr['width'] ) && is_numeric( $attr['width'] ) ) {
			$width = absint( $attr['width'] );
		}
		unset( $attr['height'], $attr['width'] );
		$attr     = array_map( 'esc_attr', $attr );
		$hwstring = image_hwstring( $width, $height );
		$html     = rtrim( "<img $hwstring" );

		foreach ( $attr as $name => $value ) {
			$html .= " $name=" . '"' . $value . '"';
		}

		$html .= ' />';
	}

	/**
	 * Lọc phần tử HTML img đại diện cho đính kèm hình ảnh.
	 *
	 * @since 5.6.0
	 *
	 * @param string       $html          Phần tử HTML img hoặc chuỗi rỗng khi thất bại.
	 * @param int          $attachment_id ID đính kèm hình ảnh.
	 * @param string|int[] $size          Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                                    mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param bool         $icon          Có nên xử lý hình ảnh như biểu tượng hay không.
	 * @param string[]     $attr          Mảng giá trị thuộc tính cho markup hình ảnh, đánh chỉ mục theo tên thuộc tính.
	 *                                    Xem wp_get_attachment_image().
	 */
	return apply_filters( 'wp_get_attachment_image', $html, $attachment_id, $size, $icon, $attr );
}

/**
 * Lấy URL của đính kèm hình ảnh.
 *
 * @since 4.4.0
 *
 * @param int          $attachment_id ID đính kèm hình ảnh.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param bool         $icon          Tùy chọn. Có nên xử lý hình ảnh như biểu tượng hay không. Mặc định false.
 * @return string|false URL đính kèm hoặc false nếu không có hình ảnh. Nếu `$size` không khớp
 *                      với bất kỳ kích thước ảnh đã đăng ký nào, URL hình ảnh gốc sẽ được trả về.
 */
function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
	$image = wp_get_attachment_image_src( $attachment_id, $size, $icon );
	return isset( $image[0] ) ? $image[0] : false;
}

/**
 * Lấy đường dẫn đính kèm tương đối so với thư mục upload.
 *
 * @since 4.4.1
 * @access private
 *
 * @param string $file Tên tệp đính kèm.
 * @return string Đường dẫn đính kèm tương đối so với thư mục upload.
 */
function _wp_get_attachment_relative_path( $file ) {
	$dirname = dirname( $file );

	if ( '.' === $dirname ) {
		return '';
	}

	if ( str_contains( $dirname, 'wp-content/uploads' ) ) {
		// Lấy tên thư mục tương đối so với thư mục upload (tương thích ngược cho upload trước phiên bản 2.7).
		$dirname = substr( $dirname, strpos( $dirname, 'wp-content/uploads' ) + 18 );
		$dirname = ltrim( $dirname, '/' );
	}

	return $dirname;
}

/**
 * Lấy kích thước hình ảnh dạng mảng từ metadata.
 *
 * Được sử dụng cho hình ảnh responsive.
 *
 * @since 4.4.0
 * @access private
 *
 * @param string $size_name  Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký.
 * @param array  $image_meta Metadata hình ảnh.
 * @return array|false {
 *     Mảng chiều rộng và chiều cao hoặc false nếu kích thước không có trong metadata.
 *
 *     @type int $0 Chiều rộng hình ảnh.
 *     @type int $1 Chiều cao hình ảnh.
 * }
 */
function _wp_get_image_size_from_meta( $size_name, $image_meta ) {
	if ( 'full' === $size_name ) {
		return array(
			absint( $image_meta['width'] ),
			absint( $image_meta['height'] ),
		);
	} elseif ( ! empty( $image_meta['sizes'][ $size_name ] ) ) {
		return array(
			absint( $image_meta['sizes'][ $size_name ]['width'] ),
			absint( $image_meta['sizes'][ $size_name ]['height'] ),
		);
	}

	return false;
}

/**
 * Lấy giá trị thuộc tính 'srcset' cho đính kèm hình ảnh.
 *
 * @since 4.4.0
 *
 * @see wp_calculate_image_srcset()
 *
 * @param int          $attachment_id ID đính kèm hình ảnh.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'medium'.
 * @param array|null   $image_meta    Tùy chọn. Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
 *                                    Mặc định null.
 * @return string|false Chuỗi giá trị 'srcset' hoặc false.
 */
function wp_get_attachment_image_srcset( $attachment_id, $size = 'medium', $image_meta = null ) {
	$image = wp_get_attachment_image_src( $attachment_id, $size );

	if ( ! $image ) {
		return false;
	}

	if ( ! is_array( $image_meta ) ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
	}

	$image_src  = $image[0];
	$size_array = array(
		absint( $image[1] ),
		absint( $image[2] ),
	);

	return wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );
}

/**
 * Hàm trợ giúp để tính toán các nguồn hình ảnh bao gồm trong thuộc tính 'srcset'.
 *
 * @since 4.4.0
 *
 * @param int[]  $size_array    {
 *     Mảng giá trị chiều rộng và chiều cao.
 *
 *     @type int $0 Chiều rộng tính bằng pixel.
 *     @type int $1 Chiều cao tính bằng pixel.
 * }
 * @param string $image_src     Thuộc tính 'src' của hình ảnh.
 * @param array  $image_meta    Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Tùy chọn. ID đính kèm hình ảnh. Mặc định 0.
 * @return string|false Giá trị thuộc tính 'srcset'. False khi lỗi hoặc khi chỉ có một nguồn.
 */
function wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id = 0 ) {
	/**
	 * Tiền lọc metadata hình ảnh để có thể sửa các không nhất quán trong dữ liệu đã lưu.
	 *
	 * @since 4.5.0
	 *
	 * @param array  $image_meta    Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
	 * @param int[]  $size_array    {
	 *     Mảng giá trị chiều rộng và chiều cao yêu cầu.
	 *
	 *     @type int $0 Chiều rộng tính bằng pixel.
	 *     @type int $1 Chiều cao tính bằng pixel.
	 * }
	 * @param string $image_src     Thuộc tính 'src' của hình ảnh.
	 * @param int    $attachment_id ID đính kèm hình ảnh hoặc 0 nếu không được cung cấp.
	 */
	$image_meta = apply_filters( 'wp_calculate_image_srcset_meta', $image_meta, $size_array, $image_src, $attachment_id );

	if ( empty( $image_meta['sizes'] ) || ! isset( $image_meta['file'] ) || strlen( $image_meta['file'] ) < 4 ) {
		return false;
	}

	$image_sizes = $image_meta['sizes'];

	// Lấy chiều rộng và chiều cao của hình ảnh.
	$image_width  = (int) $size_array[0];
	$image_height = (int) $size_array[1];

	// Thoát sớm nếu lỗi/không có chiều rộng.
	if ( $image_width < 1 ) {
		return false;
	}

	$image_basename = wp_basename( $image_meta['file'] );

	/*
	 * WordPress làm phẳng GIF động thành một khung hình khi tạo kích thước trung gian.
	 * Để tránh ẩn hoạt ảnh trong nội dung người dùng, nếu src là GIF kích thước đầy đủ, thuộc tính srcset không được tạo.
	 * Nếu src là GIF kích thước trung gian, kích thước đầy đủ bị loại khỏi srcset để giữ GIF đã làm phẳng không trở thành hoạt ảnh.
	 */
	if ( ! isset( $image_sizes['thumbnail']['mime-type'] ) || 'image/gif' !== $image_sizes['thumbnail']['mime-type'] ) {
		$image_sizes[] = array(
			'width'  => $image_meta['width'],
			'height' => $image_meta['height'],
			'file'   => $image_basename,
		);
	} elseif ( str_contains( $image_src, $image_meta['file'] ) ) {
		return false;
	}

	// Lấy thư mục con uploads từ hình ảnh kích thước đầy đủ.
	$dirname = _wp_get_attachment_relative_path( $image_meta['file'] );

	if ( $dirname ) {
		$dirname = trailingslashit( $dirname );
	}

	$upload_dir    = wp_get_upload_dir();
	$image_baseurl = trailingslashit( $upload_dir['baseurl'] ) . $dirname;

	/*
	 * Nếu đang dùng HTTPS, ưu tiên URL HTTPS khi biết chúng được hỗ trợ bởi tên miền
	 * (nghĩa là khi chúng chia sẻ tên miền với yêu cầu hiện tại).
	 */
	if ( is_ssl() && ! str_starts_with( $image_baseurl, 'https' ) ) {
		/*
		 * Vì header `Host:` có thể chứa cổng, nên cần
		 * so sánh với URL hình ảnh sử dụng cùng cổng.
		 */
		$parsed = parse_url( $image_baseurl );
		$domain = isset( $parsed['host'] ) ? $parsed['host'] : '';

		if ( isset( $parsed['port'] ) ) {
			$domain .= ':' . $parsed['port'];
		}

		if ( $_SERVER['HTTP_HOST'] === $domain ) {
			$image_baseurl = set_url_scheme( $image_baseurl, 'https' );
		}
	}

	/*
	 * Hình ảnh đã được chỉnh sửa trong WordPress sau khi tải lên sẽ
	 * chứa hash duy nhất. Tìm hash đó và sử dụng sau để lọc
	 * ra các hình ảnh còn sót lại từ phiên bản trước.
	 */
	$image_edited = preg_match( '/-e[0-9]{13}/', wp_basename( $image_src ), $image_edit_hash );

	/**
	 * Lọc chiều rộng hình ảnh tối đa để bao gồm trong thuộc tính 'srcset'.
	 *
	 * @since 4.4.0
	 *
	 * @param int   $max_width  Chiều rộng hình ảnh tối đa để bao gồm trong 'srcset'. Mặc định '2048'.
	 * @param int[] $size_array {
	 *     Mảng giá trị chiều rộng và chiều cao yêu cầu.
	 *
	 *     @type int $0 Chiều rộng tính bằng pixel.
	 *     @type int $1 Chiều cao tính bằng pixel.
	 * }
	 */
	$max_srcset_image_width = apply_filters( 'max_srcset_image_width', 2048, $size_array );

	// Mảng để giữ các URL ứng viên.
	$sources = array();

	/**
	 * Để đảm bảo ID khớp với image src, chúng ta sẽ kiểm tra xem có kích thước nào trong metadata
	 * đính kèm khớp với $image_src không. Nếu không tìm thấy kết quả khớp, chúng ta không trả về srcset
	 * để tránh phục vụ hình ảnh không chính xác. Xem #35045.
	 */
	$src_matched = false;

	/*
	 * Lặp qua các hình ảnh có sẵn. Chỉ sử dụng hình ảnh là phiên bản
	 * đã thay đổi kích thước của cùng bản chỉnh sửa.
	 */
	foreach ( $image_sizes as $image ) {
		$is_src = false;

		// Kiểm tra xem metadata hình ảnh có bị hỏng không.
		if ( ! is_array( $image ) ) {
			continue;
		}

		// Nếu tên tệp là một phần của `src`, chúng ta đã xác nhận khớp.
		if ( ! $src_matched && str_contains( $image_src, $dirname . $image['file'] ) ) {
			$src_matched = true;
			$is_src      = true;
		}

		// Lọc bỏ hình ảnh từ các bản chỉnh sửa trước.
		if ( $image_edited && ! strpos( $image['file'], $image_edit_hash[0] ) ) {
			continue;
		}

		/*
		 * Lọc bỏ hình ảnh rộng hơn '$max_srcset_image_width' trừ khi
		 * tệp đó nằm trong thuộc tính 'src'.
		 */
		if ( $max_srcset_image_width && $image['width'] > $max_srcset_image_width && ! $is_src ) {
			continue;
		}

		// Nếu kích thước hình ảnh nằm trong phạm vi 1px so với kích thước mong đợi, sử dụng nó.
		if ( wp_image_matches_ratio( $image_width, $image_height, $image['width'], $image['height'] ) ) {
			// Thêm URL, bộ mô tả và giá trị vào mảng nguồn để trả về.
			$source = array(
				'url'        => $image_baseurl . $image['file'],
				'descriptor' => 'w',
				'value'      => $image['width'],
			);

			// Hình ảnh 'src' phải là đầu tiên trong 'srcset', do lỗi trong iOS8. Xem #35030.
			if ( $is_src ) {
				$sources = array( $image['width'] => $source ) + $sources;
			} else {
				$sources[ $image['width'] ] = $source;
			}
		}
	}

	/**
	 * Lọc các nguồn 'srcset' của hình ảnh.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $sources {
	 *     Một hoặc nhiều mảng dữ liệu nguồn để bao gồm trong 'srcset'.
	 *
	 *     @type array $width {
	 *         @type string $url        URL của nguồn hình ảnh.
	 *         @type string $descriptor Loại bộ mô tả được sử dụng trong chuỗi ứng viên hình ảnh,
	 *                                  'w' hoặc 'x'.
	 *         @type int    $value      Chiều rộng nguồn nếu ghép với bộ mô tả 'w', hoặc
	 *                                  giá trị mật độ pixel nếu ghép với bộ mô tả 'x'.
	 *     }
	 * }
	 * @param array $size_array     {
	 *     Mảng giá trị chiều rộng và chiều cao yêu cầu.
	 *
	 *     @type int $0 Chiều rộng tính bằng pixel.
	 *     @type int $1 Chiều cao tính bằng pixel.
	 * }
	 * @param string $image_src     Thuộc tính 'src' của hình ảnh.
	 * @param array  $image_meta    Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id ID đính kèm hình ảnh hoặc 0.
	 */
	$sources = apply_filters( 'wp_calculate_image_srcset', $sources, $size_array, $image_src, $image_meta, $attachment_id );

	// Chỉ trả về giá trị 'srcset' nếu có nhiều hơn một nguồn.
	if ( ! $src_matched || ! is_array( $sources ) || count( $sources ) < 2 ) {
		return false;
	}

	$srcset = '';

	foreach ( $sources as $source ) {
		$srcset .= str_replace( ' ', '%20', $source['url'] ) . ' ' . $source['value'] . $source['descriptor'] . ', ';
	}

	return rtrim( $srcset, ', ' );
}

/**
 * Lấy giá trị thuộc tính 'sizes' cho đính kèm hình ảnh.
 *
 * @since 4.4.0
 *
 * @see wp_calculate_image_sizes()
 *
 * @param int          $attachment_id ID đính kèm hình ảnh.
 * @param string|int[] $size          Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'medium'.
 * @param array|null   $image_meta    Tùy chọn. Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
 *                                    Mặc định null.
 * @return string|false Giá trị kích thước nguồn hợp lệ để dùng trong thuộc tính 'sizes' hoặc false.
 */
function wp_get_attachment_image_sizes( $attachment_id, $size = 'medium', $image_meta = null ) {
	$image = wp_get_attachment_image_src( $attachment_id, $size );

	if ( ! $image ) {
		return false;
	}

	if ( ! is_array( $image_meta ) ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
	}

	$image_src  = $image[0];
	$size_array = array(
		absint( $image[1] ),
		absint( $image[2] ),
	);

	return wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
}

/**
 * Tạo giá trị thuộc tính 'sizes' cho hình ảnh.
 *
 * @since 4.4.0
 *
 * @param string|int[] $size          Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
 * @param string|null  $image_src     Tùy chọn. URL đến tệp hình ảnh. Mặc định null.
 * @param array|null   $image_meta    Tùy chọn. Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
 *                                    Mặc định null.
 * @param int          $attachment_id Tùy chọn. ID đính kèm hình ảnh. Cần `$image_meta` hoặc `$attachment_id`
 *                                    khi sử dụng tên kích thước ảnh làm đối số cho `$size`. Mặc định 0.
 * @return string|false Giá trị kích thước nguồn hợp lệ để dùng trong thuộc tính 'sizes' hoặc false.
 */
function wp_calculate_image_sizes( $size, $image_src = null, $image_meta = null, $attachment_id = 0 ) {
	$width = 0;

	if ( is_array( $size ) ) {
		$width = absint( $size[0] );
	} elseif ( is_string( $size ) ) {
		if ( ! $image_meta && $attachment_id ) {
			$image_meta = wp_get_attachment_metadata( $attachment_id );
		}

		if ( is_array( $image_meta ) ) {
			$size_array = _wp_get_image_size_from_meta( $size, $image_meta );
			if ( $size_array ) {
				$width = absint( $size_array[0] );
			}
		}
	}

	if ( ! $width ) {
		return false;
	}

	// Thiết lập thuộc tính 'sizes' mặc định.
	$sizes = sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $width );

	/**
	 * Lọc đầu ra của 'wp_calculate_image_sizes()'.
	 *
	 * @since 4.4.0
	 *
	 * @param string       $sizes         Giá trị kích thước nguồn để dùng trong thuộc tính 'sizes'.
	 * @param string|int[] $size          Kích thước hình ảnh yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                                    mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param string|null  $image_src     URL đến tệp hình ảnh hoặc null.
	 * @param array|null   $image_meta    Metadata hình ảnh như được trả về bởi wp_get_attachment_metadata() hoặc null.
	 * @param int          $attachment_id ID đính kèm hình ảnh gốc hoặc 0.
	 */
	return apply_filters( 'wp_calculate_image_sizes', $sizes, $size, $image_src, $image_meta, $attachment_id );
}

/**
 * Xác định xem metadata hình ảnh có phải là của tệp nguồn hình ảnh hay không.
 *
 * Metadata hình ảnh được lấy theo ID bài viết đính kèm. Trong một số trường hợp ID bài viết có thể thay đổi.
 * Ví dụ khi website được xuất và nhập vào website khác. Khi đó
 * ID bài viết đính kèm trong post_content của website đã xuất có thể không khớp
 * với cùng đính kèm ở website mới.
 *
 * @since 5.5.0
 *
 * @param string $image_location Đường dẫn đầy đủ hoặc URI đến tệp hình ảnh.
 * @param array  $image_meta     Metadata đính kèm như được trả về bởi 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id  Tùy chọn. ID đính kèm hình ảnh. Mặc định 0.
 * @return bool Metadata hình ảnh có phải là của tệp hình ảnh này hay không.
 */
function wp_image_file_matches_image_meta( $image_location, $image_meta, $attachment_id = 0 ) {
	$match = false;

	// Đảm bảo $image_meta hợp lệ.
	if ( isset( $image_meta['file'] ) && strlen( $image_meta['file'] ) > 4 ) {
		// Xóa tham số truy vấn trong URI hình ảnh.
		list( $image_location ) = explode( '?', $image_location );

		// Kiểm tra xem đường dẫn hình ảnh tương đối từ metadata có nằm ở cuối $image_location không.
		if ( strrpos( $image_location, $image_meta['file'] ) === strlen( $image_location ) - strlen( $image_meta['file'] ) ) {
			$match = true;
		} else {
			// Lấy thư mục con uploads từ hình ảnh kích thước đầy đủ.
			$dirname = _wp_get_attachment_relative_path( $image_meta['file'] );

			if ( $dirname ) {
				$dirname = trailingslashit( $dirname );
			}

			if ( ! empty( $image_meta['original_image'] ) ) {
				$relative_path = $dirname . $image_meta['original_image'];

				if ( strrpos( $image_location, $relative_path ) === strlen( $image_location ) - strlen( $relative_path ) ) {
					$match = true;
				}
			}

			if ( ! $match && ! empty( $image_meta['sizes'] ) ) {
				foreach ( $image_meta['sizes'] as $image_size_data ) {
					$relative_path = $dirname . $image_size_data['file'];

					if ( strrpos( $image_location, $relative_path ) === strlen( $image_location ) - strlen( $relative_path ) ) {
						$match = true;
						break;
					}
				}
			}
		}
	}

	/**
	 * Lọc xem đường dẫn hoặc URI hình ảnh có khớp với metadata hình ảnh không.
	 *
	 * @since 5.5.0
	 *
	 * @param bool   $match          Đường dẫn tương đối hình ảnh từ metadata
	 *                               có khớp với cuối URI hoặc đường dẫn đến tệp hình ảnh không.
	 * @param string $image_location Đường dẫn đầy đủ hoặc URI đến tệp hình ảnh được kiểm tra.
	 * @param array  $image_meta     Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id  ID đính kèm hình ảnh hoặc 0 nếu không được cung cấp.
	 */
	return apply_filters( 'wp_image_file_matches_image_meta', $match, $image_location, $image_meta, $attachment_id );
}

/**
 * Xác định kích thước chiều rộng và chiều cao của hình ảnh dựa trên tệp nguồn.
 *
 * @since 5.5.0
 *
 * @param string $image_src     Tệp nguồn hình ảnh.
 * @param array  $image_meta    Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Tùy chọn. ID đính kèm hình ảnh. Mặc định 0.
 * @return array|false Mảng với phần tử đầu tiên là chiều rộng và phần tử thứ hai là chiều cao,
 *                     hoặc false nếu không thể xác định kích thước.
 */
function wp_image_src_get_dimensions( $image_src, $image_meta, $attachment_id = 0 ) {
	$dimensions = false;

	// Có phải hình ảnh kích thước đầy đủ không?
	if (
		isset( $image_meta['file'] ) &&
		str_contains( $image_src, wp_basename( $image_meta['file'] ) )
	) {
		$dimensions = array(
			(int) $image_meta['width'],
			(int) $image_meta['height'],
		);
	}

	if ( ! $dimensions && ! empty( $image_meta['sizes'] ) ) {
		$src_filename = wp_basename( $image_src );

		foreach ( $image_meta['sizes'] as $image_size_data ) {
			if ( $src_filename === $image_size_data['file'] ) {
				$dimensions = array(
					(int) $image_size_data['width'],
					(int) $image_size_data['height'],
				);

				break;
			}
		}
	}

	/**
	 * Lọc giá trị 'wp_image_src_get_dimensions'.
	 *
	 * @since 5.7.0
	 *
	 * @param array|false $dimensions    Mảng với phần tử đầu tiên là chiều rộng
	 *                                   và phần tử thứ hai là chiều cao, hoặc
	 *                                   false nếu không thể xác định kích thước.
	 * @param string      $image_src     Tệp nguồn hình ảnh.
	 * @param array       $image_meta    Metadata hình ảnh như được trả về bởi
	 *                                   'wp_get_attachment_metadata()'.
	 * @param int         $attachment_id ID đính kèm hình ảnh. Mặc định 0.
	 */
	return apply_filters( 'wp_image_src_get_dimensions', $dimensions, $image_src, $image_meta, $attachment_id );
}

/**
 * Thêm thuộc tính 'srcset' và 'sizes' vào phần tử 'img' hiện có.
 *
 * @since 4.4.0
 *
 * @see wp_calculate_image_srcset()
 * @see wp_calculate_image_sizes()
 *
 * @param string $image         Phần tử HTML 'img' cần được lọc.
 * @param array  $image_meta    Metadata hình ảnh như được trả về bởi 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id ID đính kèm hình ảnh.
 * @return string Phần tử 'img' đã chuyển đổi với thuộc tính 'srcset' và 'sizes' được thêm.
 */
function wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id ) {
	// Đảm bảo metadata hình ảnh tồn tại.
	if ( empty( $image_meta['sizes'] ) ) {
		return $image;
	}

	$image_src         = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';
	list( $image_src ) = explode( '?', $image_src );

	// Trả về sớm nếu không thể lấy nguồn hình ảnh.
	if ( ! $image_src ) {
		return $image;
	}

	// Thoát sớm nếu hình ảnh đã được chèn và sau đó chỉnh sửa.
	if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash )
		&& ! str_contains( wp_basename( $image_src ), $img_edit_hash[0] )
	) {
		return $image;
	}

	$width  = preg_match( '/ width="([0-9]+)"/', $image, $match_width ) ? (int) $match_width[1] : 0;
	$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height ) ? (int) $match_height[1] : 0;

	if ( $width && $height ) {
		$size_array = array( $width, $height );
	} else {
		$size_array = wp_image_src_get_dimensions( $image_src, $image_meta, $attachment_id );
		if ( ! $size_array ) {
			return $image;
		}
	}

	$srcset = wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( $srcset ) {
		// Kiểm tra xem đã có thuộc tính 'sizes' chưa.
		$sizes = strpos( $image, ' sizes=' );

		if ( ! $sizes ) {
			$sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
		}
	}

	if ( $srcset && $sizes ) {
		// Định dạng chuỗi 'srcset' và 'sizes' và thoát thuộc tính.
		$attr = sprintf( ' srcset="%s"', esc_attr( $srcset ) );

		if ( is_string( $sizes ) ) {
			$attr .= sprintf( ' sizes="%s"', esc_attr( $sizes ) );
		}

		// Thêm thuộc tính srcset và sizes vào markup hình ảnh.
		return preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image );
	}

	return $image;
}

/**
 * Xác định xem có nên thêm thuộc tính `loading` vào thẻ được chỉ định trong ngữ cảnh được chỉ định không.
 *
 * @since 5.5.0
 * @since 5.7.0 Giờ trả về `true` mặc định cho thẻ `iframe`.
 *
 * @param string $tag_name Tên thẻ.
 * @param string $context  Ngữ cảnh bổ sung, như tên bộ lọc hiện tại
 *                         hoặc tên hàm nơi được gọi.
 * @return bool Có nên thêm thuộc tính hay không.
 */
function wp_lazy_loading_enabled( $tag_name, $context ) {
	/*
	 * Mặc định thêm vào tất cả thẻ 'img' và 'iframe'.
	 * Xem https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-loading
	 * Xem https://html.spec.whatwg.org/multipage/iframe-embed-object.html#attr-iframe-loading
	 */
	$default = ( 'img' === $tag_name || 'iframe' === $tag_name );

	/**
	 * Lọc xem có nên thêm thuộc tính `loading` vào thẻ được chỉ định trong ngữ cảnh được chỉ định không.
	 *
	 * @since 5.5.0
	 *
	 * @param bool   $default  Giá trị mặc định.
	 * @param string $tag_name Tên thẻ.
	 * @param string $context  Ngữ cảnh bổ sung, như tên bộ lọc hiện tại
	 *                         hoặc tên hàm nơi được gọi.
	 */
	return (bool) apply_filters( 'wp_lazy_loading_enabled', $default, $tag_name, $context );
}

/**
 * Lọc các thẻ cụ thể trong nội dung bài viết và sửa đổi markup của chúng.
 *
 * Sửa đổi các thẻ HTML trong nội dung bài viết để bao gồm các công nghệ trình duyệt và HTML mới
 * có thể chưa tồn tại tại thời điểm tạo bài viết. Các sửa đổi hiện tại
 * bao gồm thêm thuộc tính `srcset`, `sizes` và `loading` vào thẻ HTML `img`, cũng như
 * thêm thuộc tính `loading` vào thẻ HTML `iframe`.
 * Các tối ưu hóa tương tự trong tương lai nên được thêm/mong đợi ở đây.
 *
 * @since 5.5.0
 * @since 5.7.0 Giờ hỗ trợ thêm thuộc tính `loading` vào thẻ `iframe`.
 *
 * @see wp_img_tag_add_width_and_height_attr()
 * @see wp_img_tag_add_srcset_and_sizes_attr()
 * @see wp_img_tag_add_loading_optimization_attrs()
 * @see wp_iframe_tag_add_loading_attr()
 *
 * @param string $content Nội dung HTML cần được lọc.
 * @param string $context Tùy chọn. Ngữ cảnh bổ sung để truyền cho các bộ lọc.
 *                        Mặc định là `current_filter()` khi không được đặt.
 * @return string Nội dung đã chuyển đổi với hình ảnh được sửa đổi.
 */
function wp_filter_content_tags( $content, $context = null ) {
	if ( null === $context ) {
		$context = current_filter();
	}

	$add_iframe_loading_attr = wp_lazy_loading_enabled( 'iframe', $context );

	if ( ! preg_match_all( '/<(img|iframe)\s[^>]+>/', $content, $matches, PREG_SET_ORDER ) ) {
		return $content;
	}

	// Danh sách các thẻ `img` duy nhất tìm thấy trong $content.
	$images = array();

	// Danh sách các thẻ `iframe` duy nhất tìm thấy trong $content.
	$iframes = array();

	foreach ( $matches as $match ) {
		list( $tag, $tag_name ) = $match;

		switch ( $tag_name ) {
			case 'img':
				if ( preg_match( '/wp-image-([0-9]+)/i', $tag, $class_id ) ) {
					$attachment_id = absint( $class_id[1] );

					if ( $attachment_id ) {
						/*
						 * Nếu chính xác cùng thẻ hình ảnh được sử dụng nhiều hơn một lần, ghi đè nó.
						 * Tất cả các thẻ giống hệt sẽ được thay thế sau bằng 'str_replace()'.
						 */
						$images[ $tag ] = $attachment_id;
						break;
					}
				}
				$images[ $tag ] = 0;
				break;
			case 'iframe':
				$iframes[ $tag ] = 0;
				break;
		}
	}

	// Thu gọn mảng thành các ID đính kèm duy nhất.
	$attachment_ids = array_unique( array_filter( array_values( $images ) ) );

	if ( count( $attachment_ids ) > 1 ) {
		/*
		 * Làm ấm bộ nhớ đệm đối tượng với thông tin bài viết và meta cho tất cả
		 * hình ảnh tìm thấy để tránh thực hiện các truy vấn cơ sở dữ liệu riêng lẻ.
		 */
		_prime_post_caches( $attachment_ids, false, true );
	}

	// Lặp qua các kết quả khớp theo thứ tự xuất hiện vì nó liên quan đến việc có tải lười hay không.
	foreach ( $matches as $match ) {
		// Lọc kết quả khớp hình ảnh.
		if ( isset( $images[ $match[0] ] ) ) {
			$filtered_image = $match[0];
			$attachment_id  = $images[ $match[0] ];

			// Thêm thuộc tính 'width' và 'height' nếu áp dụng được.
			if ( $attachment_id > 0 && ! str_contains( $filtered_image, ' width=' ) && ! str_contains( $filtered_image, ' height=' ) ) {
				$filtered_image = wp_img_tag_add_width_and_height_attr( $filtered_image, $context, $attachment_id );
			}

			// Thêm thuộc tính 'srcset' và 'sizes' nếu áp dụng được.
			if ( $attachment_id > 0 && ! str_contains( $filtered_image, ' srcset=' ) ) {
				$filtered_image = wp_img_tag_add_srcset_and_sizes_attr( $filtered_image, $context, $attachment_id );
			}

			// Thêm thuộc tính tối ưu hóa tải nếu áp dụng được.
			$filtered_image = wp_img_tag_add_loading_optimization_attrs( $filtered_image, $context );

			// Thêm 'auto' vào thuộc tính sizes nếu áp dụng được.
			$filtered_image = wp_img_tag_add_auto_sizes( $filtered_image );

			/**
			 * Lọc thẻ img trong nội dung cho ngữ cảnh nhất định.
			 *
			 * @since 6.0.0
			 *
			 * @param string $filtered_image Thẻ img đầy đủ với thuộc tính sẽ thay thế thẻ img nguồn.
			 * @param string $context        Ngữ cảnh bổ sung, như tên bộ lọc hiện tại hoặc tên hàm nơi được gọi.
			 * @param int    $attachment_id  ID đính kèm hình ảnh. Có thể là 0 nếu hình ảnh không phải đính kèm.
			 */
			$filtered_image = apply_filters( 'wp_content_img_tag', $filtered_image, $context, $attachment_id );

			if ( $filtered_image !== $match[0] ) {
				$content = str_replace( $match[0], $filtered_image, $content );
			}

			/*
			 * Bỏ thiết lập tra cứu hình ảnh để không chạy lại logic tương tự một cách không cần thiết nếu cùng thẻ hình ảnh
			 * được sử dụng nhiều hơn một lần trong cùng khối nội dung.
			 */
			unset( $images[ $match[0] ] );
		}

		// Lọc kết quả khớp iframe.
		if ( isset( $iframes[ $match[0] ] ) ) {
			$filtered_iframe = $match[0];

			// Thêm thuộc tính 'loading' nếu áp dụng được.
			if ( $add_iframe_loading_attr && ! str_contains( $filtered_iframe, ' loading=' ) ) {
				$filtered_iframe = wp_iframe_tag_add_loading_attr( $filtered_iframe, $context );
			}

			if ( $filtered_iframe !== $match[0] ) {
				$content = str_replace( $match[0], $filtered_iframe, $content );
			}

			/*
			 * Bỏ thiết lập tra cứu iframe để không chạy lại logic tương tự một cách không cần thiết nếu cùng thẻ iframe
			 * được sử dụng nhiều hơn một lần trong cùng khối nội dung.
			 */
			unset( $iframes[ $match[0] ] );
		}
	}

	return $content;
}

/**
 * Thêm 'auto' vào thuộc tính sizes của hình ảnh, nếu hình ảnh được tải lười và chưa bao gồm nó.
 *
 * @since 6.7.0
 *
 * @param string $image Markup thẻ hình ảnh đang được lọc.
 * @return string Markup thẻ hình ảnh đã lọc.
 */
function wp_img_tag_add_auto_sizes( string $image ): string {
	/**
	 * Lọc xem auto-sizes cho hình ảnh tải lười có được bật không.
	 *
	 * @since 6.7.1
	 *
	 * @param boolean $enabled Auto-sizes cho hình ảnh tải lười có được bật không.
	 */
	if ( ! apply_filters( 'wp_img_tag_add_auto_sizes', true ) ) {
		return $image;
	}

	$processor = new WP_HTML_Tag_Processor( $image );

	// Thoát nếu không có thẻ IMG.
	if ( ! $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
		return $image;
	}

	// Thoát sớm nếu hình ảnh không được tải lười.
	$loading = $processor->get_attribute( 'loading' );
	if ( ! is_string( $loading ) || 'lazy' !== strtolower( trim( $loading, " \t\f\r\n" ) ) ) {
		return $image;
	}

	/*
	 * Thoát sớm nếu hình ảnh không có thuộc tính width.
	 * Theo WordPress Core, hình ảnh tải lười luôn phải có thuộc tính width.
	 * Tuy nhiên, có thể tải lười được thêm bởi plugin, nơi chúng ta không có đảm bảo đó.
	 * Do đó, vẫn hợp lý để đảm bảo sự hiện diện của thuộc tính width ở đây để sử dụng `sizes=auto`.
	 */
	$width = $processor->get_attribute( 'width' );
	if ( ! is_string( $width ) || '' === $width ) {
		return $image;
	}

	$sizes = $processor->get_attribute( 'sizes' );

	// Thoát sớm nếu hình ảnh không phải responsive.
	if ( ! is_string( $sizes ) ) {
		return $image;
	}

	// Không thêm 'auto' vào thuộc tính sizes nếu nó đã tồn tại.
	if ( wp_sizes_attribute_includes_valid_auto( $sizes ) ) {
		return $image;
	}

	$processor->set_attribute( 'sizes', "auto, $sizes" );
	return $processor->get_updated_html();
}

/**
 * Kiểm tra xem thuộc tính 'sizes' đã cho có bao gồm từ khóa 'auto' là mục đầu tiên trong danh sách không.
 *
 * Theo đặc tả HTML, nếu có mặt nó phải là mục đầu tiên.
 *
 * @since 6.7.0
 *
 * @param string $sizes_attr Giá trị thuộc tính 'sizes'.
 * @return bool True nếu từ khóa 'auto' có mặt, false nếu ngược lại.
 */
function wp_sizes_attribute_includes_valid_auto( string $sizes_attr ): bool {
	list( $first_size ) = explode( ',', $sizes_attr, 2 );
	return 'auto' === strtolower( trim( $first_size, " \t\f\r\n" ) );
}

/**
 * In quy tắc CSS để sửa các vấn đề hiển thị tiềm ẩn với hình ảnh sử dụng `sizes=auto`.
 *
 * Quy tắc này ghi đè quy tắc tương tự trong stylesheet mặc định của trình duyệt, để tránh hình ảnh sử dụng ví dụ
 * `width: auto` hoặc `width: fit-content` bị hiển thị nhỏ hơn.
 *
 * @since 6.7.1
 * @see https://html.spec.whatwg.org/multipage/rendering.html#img-contain-size
 * @see https://core.trac.wordpress.org/ticket/62413
 */
function wp_print_auto_sizes_contain_css_fix() {
	/** This filter is documented in wp-includes/media.php */
	$add_auto_sizes = apply_filters( 'wp_img_tag_add_auto_sizes', true );
	if ( ! $add_auto_sizes ) {
		return;
	}

	?>
	<style>img:is([sizes="auto" i], [sizes^="auto," i]) { contain-intrinsic-size: 3000px 1500px }</style>
	<?php
}

/**
 * Thêm thuộc tính tối ưu hóa vào thẻ HTML `img`.
 *
 * @since 6.3.0
 *
 * @param string $image   Thẻ HTML `img` nơi thuộc tính cần được thêm.
 * @param string $context Ngữ cảnh bổ sung để truyền cho các bộ lọc.
 * @return string Thẻ `img` đã chuyển đổi với thuộc tính tối ưu hóa được thêm.
 */
function wp_img_tag_add_loading_optimization_attrs( $image, $context ) {
	$src               = preg_match( '/ src=["\']?([^"\']*)/i', $image, $matche_src ) ? $matche_src[1] : null;
	$width             = preg_match( '/ width=["\']([0-9]+)["\']/', $image, $match_width ) ? (int) $match_width[1] : null;
	$height            = preg_match( '/ height=["\']([0-9]+)["\']/', $image, $match_height ) ? (int) $match_height[1] : null;
	$loading_val       = preg_match( '/ loading=["\']([A-Za-z]+)["\']/', $image, $match_loading ) ? $match_loading[1] : null;
	$fetchpriority_val = preg_match( '/ fetchpriority=["\']([A-Za-z]+)["\']/', $image, $match_fetchpriority ) ? $match_fetchpriority[1] : null;
	$decoding_val      = preg_match( '/ decoding=["\']([A-Za-z]+)["\']/', $image, $match_decoding ) ? $match_decoding[1] : null;

	/*
	 * Lấy thuộc tính tối ưu hóa tải để sử dụng.
	 * Điều này phải xảy ra trước kiểm tra điều kiện bên dưới để ngay cả hình ảnh
	 * không đủ điều kiện tải lười cũng được xem xét.
	 */
	$optimization_attrs = wp_get_loading_optimization_attributes(
		'img',
		array(
			'src'           => $src,
			'width'         => $width,
			'height'        => $height,
			'loading'       => $loading_val,
			'fetchpriority' => $fetchpriority_val,
			'decoding'      => $decoding_val,
		),
		$context
	);

	// Hình ảnh phải có nguồn để thuộc tính tối ưu hóa tải được thêm.
	if ( ! str_contains( $image, ' src="' ) ) {
		return $image;
	}

	if ( empty( $decoding_val ) ) {
		/**
		 * Lọc giá trị thuộc tính `decoding` để thêm vào hình ảnh. Mặc định `async`.
		 *
		 * Trả về giá trị falsey sẽ bỏ qua thuộc tính.
		 *
		 * @since 6.1.0
		 *
		 * @param string|false|null $value      Giá trị thuộc tính `decoding`. Trả về giá trị falsey
		 *                                      sẽ khiến thuộc tính bị bỏ qua cho hình ảnh.
		 *                                      Nếu không, có thể là: 'async', 'sync', hoặc 'auto'. Mặc định false.
		 * @param string            $image      Thẻ HTML `img` cần được lọc.
		 * @param string            $context    Ngữ cảnh bổ sung về cách hàm được gọi
		 *                                      hoặc vị trí thẻ img.
		 */
		$filtered_decoding_attr = apply_filters(
			'wp_img_tag_add_decoding_attr',
			isset( $optimization_attrs['decoding'] ) ? $optimization_attrs['decoding'] : false,
			$image,
			$context
		);

		// Xác thực các giá trị sau khi lọc.
		if ( isset( $optimization_attrs['decoding'] ) && ! $filtered_decoding_attr ) {
			// Bỏ thuộc tính `decoding` nếu `$filtered_decoding_attr` được đặt thành `false`.
			unset( $optimization_attrs['decoding'] );
		} elseif ( in_array( $filtered_decoding_attr, array( 'async', 'sync', 'auto' ), true ) ) {
			$optimization_attrs['decoding'] = $filtered_decoding_attr;
		}

		if ( ! empty( $optimization_attrs['decoding'] ) ) {
			$image = str_replace( '<img', '<img decoding="' . esc_attr( $optimization_attrs['decoding'] ) . '"', $image );
		}
	}

	// Hình ảnh phải có thuộc tính kích thước để thuộc tính 'loading' và 'fetchpriority' được thêm.
	if ( ! str_contains( $image, ' width="' ) || ! str_contains( $image, ' height="' ) ) {
		return $image;
	}

	// Giữ lại để tương thích ngược.
	$loading_attrs_enabled = wp_lazy_loading_enabled( 'img', $context );

	if ( empty( $loading_val ) && $loading_attrs_enabled ) {
		/**
		 * Lọc giá trị thuộc tính `loading` để thêm vào hình ảnh. Mặc định `lazy`.
		 *
		 * Trả về `false` hoặc chuỗi rỗng sẽ không thêm thuộc tính.
		 * Trả về `true` sẽ thêm giá trị mặc định.
		 *
		 * @since 5.5.0
		 *
		 * @param string|bool $value   Giá trị thuộc tính `loading`. Trả về giá trị falsey sẽ khiến
		 *                             thuộc tính bị bỏ qua cho hình ảnh.
		 * @param string      $image   Thẻ HTML `img` cần được lọc.
		 * @param string      $context Ngữ cảnh bổ sung về cách hàm được gọi hoặc vị trí thẻ img.
		 */
		$filtered_loading_attr = apply_filters(
			'wp_img_tag_add_loading_attr',
			isset( $optimization_attrs['loading'] ) ? $optimization_attrs['loading'] : false,
			$image,
			$context
		);

		// Xác thực các giá trị sau khi lọc.
		if ( isset( $optimization_attrs['loading'] ) && ! $filtered_loading_attr ) {
			// Bỏ thuộc tính `loading` nếu `$filtered_loading_attr` được đặt thành `false`.
			unset( $optimization_attrs['loading'] );
		} elseif ( in_array( $filtered_loading_attr, array( 'lazy', 'eager' ), true ) ) {
			/*
			 * Nếu bộ lọc đã thay đổi thuộc tính loading thành "lazy" khi thuộc tính fetchpriority
			 * với giá trị "high" đã có mặt, kích hoạt cảnh báo vì hai giá trị thuộc tính đó
			 * nên loại trừ lẫn nhau.
			 *
			 * Cùng cảnh báo có trong `wp_get_loading_optimization_attributes()`, và ở đây nó
			 * chỉ dành cho kịch bản cụ thể khi bộ lọc ở trên gây ra vấn đề.
			 */
			if ( isset( $optimization_attrs['fetchpriority'] ) && 'high' === $optimization_attrs['fetchpriority'] &&
				( isset( $optimization_attrs['loading'] ) ? $optimization_attrs['loading'] : false ) !== $filtered_loading_attr &&
				'lazy' === $filtered_loading_attr
			) {
				_doing_it_wrong(
					__FUNCTION__,
					__( 'An image should not be lazy-loaded and marked as high priority at the same time.' ),
					'6.3.0'
				);
			}

			// Giá trị đã lọc vẫn được tôn trọng.
			$optimization_attrs['loading'] = $filtered_loading_attr;
		}

		if ( ! empty( $optimization_attrs['loading'] ) ) {
			$image = str_replace( '<img', '<img loading="' . esc_attr( $optimization_attrs['loading'] ) . '"', $image );
		}
	}

	if ( empty( $fetchpriority_val ) && ! empty( $optimization_attrs['fetchpriority'] ) ) {
		$image = str_replace( '<img', '<img fetchpriority="' . esc_attr( $optimization_attrs['fetchpriority'] ) . '"', $image );
	}

	return $image;
}

/**
 * Thêm thuộc tính `width` và `height` vào thẻ HTML `img`.
 *
 * @since 5.5.0
 *
 * @param string $image         Thẻ HTML `img` nơi thuộc tính cần được thêm.
 * @param string $context       Ngữ cảnh bổ sung để truyền cho các bộ lọc.
 * @param int    $attachment_id ID đính kèm hình ảnh.
 * @return string Phần tử 'img' đã chuyển đổi với thuộc tính 'width' và 'height' được thêm.
 */
function wp_img_tag_add_width_and_height_attr( $image, $context, $attachment_id ) {
	$image_src         = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';
	list( $image_src ) = explode( '?', $image_src );

	// Trả về sớm nếu không thể lấy nguồn hình ảnh.
	if ( ! $image_src ) {
		return $image;
	}

	/**
	 * Lọc xem có nên thêm thuộc tính HTML `width` và `height` còn thiếu vào thẻ img không. Mặc định `true`.
	 *
	 * Trả về bất kỳ thứ gì khác ngoài `true` sẽ không thêm thuộc tính.
	 *
	 * @since 5.5.0
	 *
	 * @param bool   $value         Giá trị đã lọc, mặc định `true`.
	 * @param string $image         Thẻ HTML `img` nơi thuộc tính cần được thêm.
	 * @param string $context       Ngữ cảnh bổ sung về cách hàm được gọi hoặc vị trí thẻ img.
	 * @param int    $attachment_id ID đính kèm hình ảnh.
	 */
	$add = apply_filters( 'wp_img_tag_add_width_and_height_attr', true, $image, $context, $attachment_id );

	if ( true === $add ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		$size_array = wp_image_src_get_dimensions( $image_src, $image_meta, $attachment_id );

		if ( $size_array && $size_array[0] && $size_array[1] ) {
			// Nếu chiều rộng được áp đặt thông qua style (ví dụ trong hình ảnh inline), tính toán thuộc tính kích thước.
			$style_width = preg_match( '/style="width:\s*(\d+)px;"/', $image, $match_width ) ? (int) $match_width[1] : 0;
			if ( $style_width ) {
				$size_array[1] = (int) round( $size_array[1] * $style_width / $size_array[0] );
				$size_array[0] = $style_width;
			}

			$hw = trim( image_hwstring( $size_array[0], $size_array[1] ) );
			return str_replace( '<img', "<img {$hw}", $image );
		}
	}

	return $image;
}

/**
 * Thêm thuộc tính `srcset` và `sizes` vào thẻ HTML `img` hiện có.
 *
 * @since 5.5.0
 *
 * @param string $image         Thẻ HTML `img` nơi thuộc tính cần được thêm.
 * @param string $context       Ngữ cảnh bổ sung để truyền cho các bộ lọc.
 * @param int    $attachment_id ID đính kèm hình ảnh.
 * @return string Phần tử 'img' đã chuyển đổi với thuộc tính 'loading' được thêm.
 */
function wp_img_tag_add_srcset_and_sizes_attr( $image, $context, $attachment_id ) {
	/**
	 * Lọc xem có nên thêm thuộc tính HTML `srcset` và `sizes` vào thẻ img không. Mặc định `true`.
	 *
	 * Trả về bất kỳ thứ gì khác ngoài `true` sẽ không thêm thuộc tính.
	 *
	 * @since 5.5.0
	 *
	 * @param bool   $value         Giá trị đã lọc, mặc định `true`.
	 * @param string $image         Thẻ HTML `img` nơi thuộc tính cần được thêm.
	 * @param string $context       Ngữ cảnh bổ sung về cách hàm được gọi hoặc vị trí thẻ img.
	 * @param int    $attachment_id ID đính kèm hình ảnh.
	 */
	$add = apply_filters( 'wp_img_tag_add_srcset_and_sizes_attr', true, $image, $context, $attachment_id );

	if ( true === $add ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		return wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id );
	}

	return $image;
}

/**
 * Thêm thuộc tính `loading` vào thẻ HTML `iframe`.
 *
 * @since 5.7.0
 *
 * @param string $iframe  Thẻ HTML `iframe` nơi thuộc tính cần được thêm.
 * @param string $context Ngữ cảnh bổ sung để truyền cho các bộ lọc.
 * @return string Thẻ `iframe` đã chuyển đổi với thuộc tính `loading` được thêm.
 */
function wp_iframe_tag_add_loading_attr( $iframe, $context ) {
	/*
	 * Lấy giá trị thuộc tính loading để sử dụng. Điều này phải xảy ra trước kiểm tra điều kiện bên dưới để ngay cả iframe
	 * không đủ điều kiện tải lười cũng được xem xét.
	 */
	$optimization_attrs = wp_get_loading_optimization_attributes(
		'iframe',
		array(
			/*
			 * Các giá trị cụ thể cho chiều rộng và chiều cao không quan trọng ở đây hiện tại
			 * vì fetchpriority chưa được hỗ trợ cho iframe.
			 * TODO: Sử dụng WP_HTML_Tag_Processor để trích xuất giá trị thực khi hỗ trợ
			 * được thêm.
			 */
			'width'   => str_contains( $iframe, ' width="' ) ? 100 : null,
			'height'  => str_contains( $iframe, ' height="' ) ? 100 : null,
			// Hàm này không bao giờ được gọi khi thuộc tính 'loading' đã có mặt.
			'loading' => null,
		),
		$context
	);

	// Iframe phải có thuộc tính nguồn và kích thước để thuộc tính `loading` được thêm.
	if ( ! str_contains( $iframe, ' src="' ) || ! str_contains( $iframe, ' width="' ) || ! str_contains( $iframe, ' height="' ) ) {
		return $iframe;
	}

	$value = isset( $optimization_attrs['loading'] ) ? $optimization_attrs['loading'] : false;

	/**
	 * Lọc giá trị thuộc tính `loading` để thêm vào iframe. Mặc định `lazy`.
	 *
	 * Trả về `false` hoặc chuỗi rỗng sẽ không thêm thuộc tính.
	 * Trả về `true` sẽ thêm giá trị mặc định.
	 *
	 * @since 5.7.0
	 *
	 * @param string|bool $value   Giá trị thuộc tính `loading`. Trả về giá trị falsey sẽ khiến
	 *                             thuộc tính bị bỏ qua cho iframe.
	 * @param string      $iframe  Thẻ HTML `iframe` cần được lọc.
	 * @param string      $context Ngữ cảnh bổ sung về cách hàm được gọi hoặc vị trí thẻ iframe.
	 */
	$value = apply_filters( 'wp_iframe_tag_add_loading_attr', $value, $iframe, $context );

	if ( $value ) {
		if ( ! in_array( $value, array( 'lazy', 'eager' ), true ) ) {
			$value = 'lazy';
		}

		return str_replace( '<iframe', '<iframe loading="' . esc_attr( $value ) . '"', $iframe );
	}

	return $iframe;
}

/**
 * Thêm class 'wp-post-image' vào ảnh thu nhỏ bài viết. Chỉ sử dụng nội bộ.
 *
 * Sử dụng hook hành động {@see 'begin_fetch_post_thumbnail_html'} và {@see 'end_fetch_post_thumbnail_html'}
 * để thêm/xóa động chính nó sao cho chỉ lọc ảnh thu nhỏ bài viết.
 *
 * @ignore
 * @since 2.9.0
 *
 * @param string[] $attr Mảng thuộc tính ảnh thu nhỏ bao gồm src, class, alt, title, được khóa theo tên thuộc tính.
 * @return string[] Mảng thuộc tính đã sửa đổi bao gồm class 'wp-post-image' mới.
 */
function _wp_post_thumbnail_class_filter( $attr ) {
	$attr['class'] .= ' wp-post-image';
	return $attr;
}

/**
 * Thêm callback '_wp_post_thumbnail_class_filter' vào hook bộ lọc 'wp_get_attachment_image_attributes'.
 * Chỉ sử dụng nội bộ.
 *
 * @ignore
 * @since 2.9.0
 *
 * @param string[] $attr Mảng thuộc tính ảnh thu nhỏ bao gồm src, class, alt, title, được khóa theo tên thuộc tính.
 */
function _wp_post_thumbnail_class_filter_add( $attr ) {
	add_filter( 'wp_get_attachment_image_attributes', '_wp_post_thumbnail_class_filter' );
}

/**
 * Xóa callback '_wp_post_thumbnail_class_filter' khỏi hook bộ lọc 'wp_get_attachment_image_attributes'.
 * Chỉ sử dụng nội bộ.
 *
 * @ignore
 * @since 2.9.0
 *
 * @param string[] $attr Mảng thuộc tính ảnh thu nhỏ bao gồm src, class, alt, title, được khóa theo tên thuộc tính.
 */
function _wp_post_thumbnail_class_filter_remove( $attr ) {
	remove_filter( 'wp_get_attachment_image_attributes', '_wp_post_thumbnail_class_filter' );
}

/**
 * Ghi đè ngữ cảnh được sử dụng trong {@see wp_get_attachment_image()}. Chỉ sử dụng nội bộ.
 *
 * Sử dụng hook hành động {@see 'begin_fetch_post_thumbnail_html'} và {@see 'end_fetch_post_thumbnail_html'}
 * để thêm/xóa động chính nó sao cho chỉ lọc ảnh thu nhỏ bài viết.
 *
 * @ignore
 * @since 6.3.0
 * @access private
 *
 * @param string $context Ngữ cảnh để hiển thị hình ảnh đính kèm.
 * @return string Ngữ cảnh đã sửa đổi được đặt thành 'the_post_thumbnail'.
 */
function _wp_post_thumbnail_context_filter( $context ) {
	return 'the_post_thumbnail';
}

/**
 * Thêm callback '_wp_post_thumbnail_context_filter' vào hook bộ lọc 'wp_get_attachment_image_context'.
 * Chỉ sử dụng nội bộ.
 *
 * @ignore
 * @since 6.3.0
 * @access private
 */
function _wp_post_thumbnail_context_filter_add() {
	add_filter( 'wp_get_attachment_image_context', '_wp_post_thumbnail_context_filter' );
}

/**
 * Xóa callback '_wp_post_thumbnail_context_filter' khỏi hook bộ lọc 'wp_get_attachment_image_context'.
 * Chỉ sử dụng nội bộ.
 *
 * @ignore
 * @since 6.3.0
 * @access private
 */
function _wp_post_thumbnail_context_filter_remove() {
	remove_filter( 'wp_get_attachment_image_context', '_wp_post_thumbnail_context_filter' );
}

add_shortcode( 'wp_caption', 'img_caption_shortcode' );
add_shortcode( 'caption', 'img_caption_shortcode' );

/**
 * Xây dựng đầu ra shortcode Caption.
 *
 * Cho phép plugin thay thế nội dung mà lẽ ra sẽ được trả về. Bộ lọc
 * là {@see 'img_caption_shortcode'} và truyền chuỗi rỗng, tham số attr
 * và giá trị tham số content.
 *
 * Các thuộc tính được hỗ trợ cho shortcode là 'id', 'caption_id', 'align',
 * 'width', 'caption', và 'class'.
 *
 * @since 2.6.0
 * @since 3.9.0 Thuộc tính `class` đã được thêm.
 * @since 5.1.0 Thuộc tính `caption_id` đã được thêm.
 * @since 5.9.0 Giá trị mặc định tham số `$content` đã thay đổi từ `null` sang `''`.
 *
 * @param array  $attr {
 *     Thuộc tính của shortcode caption.
 *
 *     @type string $id         ID của phần tử chứa hình ảnh và chú thích, tức là `<figure>` hoặc `<div>`.
 *     @type string $caption_id ID của phần tử chú thích, tức là `<figcaption>` hoặc `<p>`.
 *     @type string $align      Tên class căn chỉnh chú thích. Mặc định 'alignnone'. Chấp nhận 'alignleft',
 *                              'aligncenter', 'alignright', 'alignnone'.
 *     @type int    $width      Chiều rộng chú thích, tính bằng pixel.
 *     @type string $caption    Văn bản chú thích.
 *     @type string $class      Tên class bổ sung được thêm vào phần tử chứa chú thích.
 * }
 * @param string $content Tùy chọn. Nội dung shortcode. Mặc định chuỗi rỗng.
 * @return string Nội dung HTML để hiển thị chú thích.
 */
function img_caption_shortcode( $attr, $content = '' ) {
	// Shortcode kiểu mới với chú thích bên trong shortcode cùng với thẻ liên kết và hình ảnh.
	if ( ! isset( $attr['caption'] ) ) {
		if ( preg_match( '#((?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?)(.*)#is', $content, $matches ) ) {
			$content         = $matches[1];
			$attr['caption'] = trim( $matches[2] );
		}
	} elseif ( str_contains( $attr['caption'], '<' ) ) {
		$attr['caption'] = wp_kses( $attr['caption'], 'post' );
	}

	/**
	 * Lọc đầu ra shortcode caption mặc định.
	 *
	 * Nếu đầu ra đã lọc không rỗng, nó sẽ được sử dụng thay vì tạo
	 * mẫu caption mặc định.
	 *
	 * @since 2.6.0
	 *
	 * @see img_caption_shortcode()
	 *
	 * @param string $output  Đầu ra caption. Mặc định rỗng.
	 * @param array  $attr    Thuộc tính của shortcode caption.
	 * @param string $content Phần tử hình ảnh, có thể được bao bọc trong siêu liên kết.
	 */
	$output = apply_filters( 'img_caption_shortcode', '', $attr, $content );

	if ( ! empty( $output ) ) {
		return $output;
	}

	$atts = shortcode_atts(
		array(
			'id'         => '',
			'caption_id' => '',
			'align'      => 'alignnone',
			'width'      => '',
			'caption'    => '',
			'class'      => '',
		),
		$attr,
		'caption'
	);

	$atts['width'] = (int) $atts['width'];

	if ( $atts['width'] < 1 || empty( $atts['caption'] ) ) {
		return $content;
	}

	$id          = '';
	$caption_id  = '';
	$describedby = '';

	if ( $atts['id'] ) {
		$atts['id'] = sanitize_html_class( $atts['id'] );
		$id         = 'id="' . esc_attr( $atts['id'] ) . '" ';
	}

	if ( $atts['caption_id'] ) {
		$atts['caption_id'] = sanitize_html_class( $atts['caption_id'] );
	} elseif ( $atts['id'] ) {
		$atts['caption_id'] = 'caption-' . str_replace( '_', '-', $atts['id'] );
	}

	if ( $atts['caption_id'] ) {
		$caption_id  = 'id="' . esc_attr( $atts['caption_id'] ) . '" ';
		$describedby = 'aria-describedby="' . esc_attr( $atts['caption_id'] ) . '" ';
	}

	$class = trim( 'wp-caption ' . $atts['align'] . ' ' . $atts['class'] );

	$html5 = current_theme_supports( 'html5', 'caption' );
	// Chú thích HTML5 không bao giờ thêm 10px phụ vào chiều rộng hình ảnh.
	$width = $html5 ? $atts['width'] : ( 10 + $atts['width'] );

	/**
	 * Lọc chiều rộng chú thích của hình ảnh.
	 *
	 * Mặc định, chú thích rộng hơn 10 pixel so với chiều rộng hình ảnh,
	 * để ngăn nội dung bài viết chạy sát hình ảnh float.
	 *
	 * @since 3.7.0
	 *
	 * @see img_caption_shortcode()
	 *
	 * @param int    $width    Chiều rộng chú thích tính bằng pixel. Để xóa style inline này,
	 *                         trả về zero.
	 * @param array  $atts     Thuộc tính của shortcode caption.
	 * @param string $content  Phần tử hình ảnh, có thể được bao bọc trong siêu liên kết.
	 */
	$caption_width = apply_filters( 'img_caption_shortcode_width', $width, $atts, $content );

	$style = '';

	if ( $caption_width ) {
		$style = 'style="width: ' . (int) $caption_width . 'px" ';
	}

	if ( $html5 ) {
		$html = sprintf(
			'<figure %s%s%sclass="%s">%s%s</figure>',
			$id,
			$describedby,
			$style,
			esc_attr( $class ),
			do_shortcode( $content ),
			sprintf(
				'<figcaption %sclass="wp-caption-text">%s</figcaption>',
				$caption_id,
				$atts['caption']
			)
		);
	} else {
		$html = sprintf(
			'<div %s%sclass="%s">%s%s</div>',
			$id,
			$style,
			esc_attr( $class ),
			str_replace( '<img ', '<img ' . $describedby, do_shortcode( $content ) ),
			sprintf(
				'<p %sclass="wp-caption-text">%s</p>',
				$caption_id,
				$atts['caption']
			)
		);
	}

	return $html;
}

add_shortcode( 'gallery', 'gallery_shortcode' );

/**
 * Xây dựng đầu ra shortcode Gallery.
 *
 * Triển khai chức năng của Shortcode Gallery để hiển thị
 * hình ảnh WordPress trên bài viết.
 *
 * @since 2.5.0
 * @since 2.8.0 Thêm tham số `$attr` để đặt đầu ra shortcode. Các thuộc tính mới bao gồm
 *              `size`, `itemtag`, `icontag`, `captiontag`, và columns. Thay đổi markup từ
 *              thẻ `div` sang thẻ `dl`, `dt` và `dd`. Hỗ trợ nhiều hơn một gallery trên
 *              cùng trang.
 * @since 2.9.0 Thêm hỗ trợ `include` và `exclude` cho shortcode.
 * @since 3.5.0 Sử dụng get_post() thay vì global `$post`. Xử lý ánh xạ `ids` sang `include`
 *              và `orderby`.
 * @since 3.6.0 Thêm xác thực cho các thẻ sử dụng trong shortcode gallery. Thêm thông tin hướng cho các mục.
 * @since 3.7.0 Giới thiệu thuộc tính `link`.
 * @since 3.9.0 Hỗ trợ gallery `html5`, chấp nhận thuộc tính 'itemtag', 'icontag', và 'captiontag'.
 * @since 4.0.0 Xóa việc sử dụng `extract()`.
 * @since 4.1.0 Thêm thuộc tính cho `wp_get_attachment_link()` để xuất `aria-describedby`.
 * @since 4.2.0 Truyền ID phiên bản shortcode cho bộ lọc `post_gallery` và `post_playlist`.
 * @since 4.6.0 Chuẩn hóa tài liệu bộ lọc để khớp với tiêu chuẩn tài liệu cho PHP.
 * @since 5.1.0 Dọn dẹp code cho tiêu chuẩn mã hóa WPCS 1.0.0.
 * @since 5.3.0 Lưu tiến trình tạo hình ảnh trung gian sau khi tải lên.
 * @since 5.5.0 Đảm bảo gallery có thể được xuất dưới dạng danh sách liên kết trong feed.
 * @since 5.6.0 Thay thế hàm chuyển đổi kiểu PHP kiểu order bằng typecast. Sửa logic cho
 *              mảng kích thước hình ảnh.
 *
 * @param array $attr {
 *     Thuộc tính của shortcode gallery.
 *
 *     @type string       $order      Thứ tự hình ảnh trong gallery. Mặc định 'ASC'. Chấp nhận 'ASC', 'DESC'.
 *     @type string       $orderby    Trường để sử dụng khi sắp xếp hình ảnh. Mặc định 'menu_order ID'.
 *                                    Chấp nhận bất kỳ câu lệnh SQL ORDERBY hợp lệ nào.
 *     @type int          $id         ID bài viết.
 *     @type string       $itemtag    Thẻ HTML sử dụng cho mỗi hình ảnh trong gallery.
 *                                    Mặc định 'dl', hoặc 'figure' khi theme đăng ký hỗ trợ gallery HTML5.
 *     @type string       $icontag    Thẻ HTML sử dụng cho biểu tượng mỗi hình ảnh.
 *                                    Mặc định 'dt', hoặc 'div' khi theme đăng ký hỗ trợ gallery HTML5.
 *     @type string       $captiontag Thẻ HTML sử dụng cho chú thích mỗi hình ảnh.
 *                                    Mặc định 'dd', hoặc 'figcaption' khi theme đăng ký hỗ trợ gallery HTML5.
 *     @type int          $columns    Số cột hình ảnh để hiển thị. Mặc định 3.
 *     @type string|int[] $size       Kích thước hình ảnh để hiển thị. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                                    giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 *     @type string       $ids        Danh sách ID đính kèm phân cách bằng dấu phẩy để hiển thị. Mặc định rỗng.
 *     @type string       $include    Danh sách ID đính kèm phân cách bằng dấu phẩy để bao gồm. Mặc định rỗng.
 *     @type string       $exclude    Danh sách ID đính kèm phân cách bằng dấu phẩy để loại trừ. Mặc định rỗng.
 *     @type string       $link       Liên kết mỗi hình ảnh đến đâu. Mặc định rỗng (liên kết đến trang đính kèm).
 *                                    Chấp nhận 'file', 'none'.
 * }
 * @return string Nội dung HTML để hiển thị gallery.
 */
function gallery_shortcode( $attr ) {
	$post = get_post();

	static $instance = 0;
	++$instance;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' được sắp xếp rõ ràng, trừ khi bạn chỉ định khác.
		if ( empty( $attr['orderby'] ) ) {
			$attr['orderby'] = 'post__in';
		}
		$attr['include'] = $attr['ids'];
	}

	/**
	 * Lọc đầu ra shortcode gallery mặc định.
	 *
	 * Nếu đầu ra đã lọc không rỗng, nó sẽ được sử dụng thay vì tạo
	 * mẫu gallery mặc định.
	 *
	 * @since 2.5.0
	 * @since 4.2.0 Tham số `$instance` đã được thêm.
	 *
	 * @see gallery_shortcode()
	 *
	 * @param string $output   Đầu ra gallery. Mặc định rỗng.
	 * @param array  $attr     Thuộc tính của shortcode gallery.
	 * @param int    $instance ID số duy nhất của phiên bản shortcode gallery này.
	 */
	$output = apply_filters( 'post_gallery', '', $attr, $instance );

	if ( ! empty( $output ) ) {
		return $output;
	}

	$html5 = current_theme_supports( 'html5', 'gallery' );
	$atts  = shortcode_atts(
		array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			'itemtag'    => $html5 ? 'figure' : 'dl',
			'icontag'    => $html5 ? 'div' : 'dt',
			'captiontag' => $html5 ? 'figcaption' : 'dd',
			'columns'    => 3,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'link'       => '',
		),
		$attr,
		'gallery'
	);

	$id = (int) $atts['id'];

	if ( ! empty( $atts['include'] ) ) {
		$_attachments = get_posts(
			array(
				'include'        => $atts['include'],
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			)
		);

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[ $val->ID ] = $_attachments[ $key ];
		}
	} elseif ( ! empty( $atts['exclude'] ) ) {
		$post_parent_id = $id;
		$attachments    = get_children(
			array(
				'post_parent'    => $id,
				'exclude'        => $atts['exclude'],
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			)
		);
	} else {
		$post_parent_id = $id;
		$attachments    = get_children(
			array(
				'post_parent'    => $id,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			)
		);
	}

	if ( ! empty( $post_parent_id ) ) {
		$post_parent = get_post( $post_parent_id );

		// Kết thúc thực thi shortcode nếu người dùng không thể đọc bài viết hoặc bài viết được bảo vệ bằng mật khẩu.
		if ( ! is_post_publicly_viewable( $post_parent->ID ) && ! current_user_can( 'read_post', $post_parent->ID )
			|| post_password_required( $post_parent )
		) {
			return '';
		}
	}

	if ( empty( $attachments ) ) {
		return '';
	}

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment ) {
			if ( ! empty( $atts['link'] ) ) {
				if ( 'none' === $atts['link'] ) {
					$output .= wp_get_attachment_image( $att_id, $atts['size'], false, $attr );
				} else {
					$output .= wp_get_attachment_link( $att_id, $atts['size'], false );
				}
			} else {
				$output .= wp_get_attachment_link( $att_id, $atts['size'], true );
			}
			$output .= "\n";
		}
		return $output;
	}

	$itemtag    = tag_escape( $atts['itemtag'] );
	$captiontag = tag_escape( $atts['captiontag'] );
	$icontag    = tag_escape( $atts['icontag'] );
	$valid_tags = wp_kses_allowed_html( 'post' );
	if ( ! isset( $valid_tags[ $itemtag ] ) ) {
		$itemtag = 'dl';
	}
	if ( ! isset( $valid_tags[ $captiontag ] ) ) {
		$captiontag = 'dd';
	}
	if ( ! isset( $valid_tags[ $icontag ] ) ) {
		$icontag = 'dt';
	}

	$columns   = (int) $atts['columns'];
	$itemwidth = $columns > 0 ? floor( 100 / $columns ) : 100;
	$float     = is_rtl() ? 'right' : 'left';

	$selector = "gallery-{$instance}";

	$gallery_style = '';

	/**
	 * Lọc xem có in các style gallery mặc định không.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $print Có in các style gallery mặc định không.
	 *                    Mặc định false nếu theme hỗ trợ gallery HTML5.
	 *                    Nếu không, mặc định true.
	 */
	if ( apply_filters( 'use_default_gallery_style', ! $html5 ) ) {
		$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';

		$gallery_style = "
		<style{$type_attr}>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
			/* see gallery_shortcode() in wp-includes/media.php */
		</style>\n\t\t";
	}

	$size_class  = sanitize_html_class( is_array( $atts['size'] ) ? implode( 'x', $atts['size'] ) : $atts['size'] );
	$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";

	/**
	 * Lọc các style CSS mặc định của shortcode gallery.
	 *
	 * @since 2.5.0
	 *
	 * @param string $gallery_style Các style CSS mặc định và phần tử mở HTML div container
	 *                              cho đầu ra shortcode gallery.
	 */
	$output = apply_filters( 'gallery_style', $gallery_style . $gallery_div );

	$i = 0;

	foreach ( $attachments as $id => $attachment ) {

		$attr = ( trim( $attachment->post_excerpt ) ) ? array( 'aria-describedby' => "$selector-$id" ) : '';

		if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
			$image_output = wp_get_attachment_link( $id, $atts['size'], false, false, false, $attr );
		} elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
			$image_output = wp_get_attachment_image( $id, $atts['size'], false, $attr );
		} else {
			$image_output = wp_get_attachment_link( $id, $atts['size'], true, false, false, $attr );
		}

		$image_meta = wp_get_attachment_metadata( $id );

		$orientation = '';

		if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
			$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
		}

		$output .= "<{$itemtag} class='gallery-item'>";
		$output .= "
			<{$icontag} class='gallery-icon {$orientation}'>
				$image_output
			</{$icontag}>";

		if ( $captiontag && trim( $attachment->post_excerpt ) ) {
			$output .= "
				<{$captiontag} class='wp-caption-text gallery-caption' id='$selector-$id'>
				" . wptexturize( $attachment->post_excerpt ) . "
				</{$captiontag}>";
		}

		$output .= "</{$itemtag}>";

		if ( ! $html5 && $columns > 0 && 0 === ++$i % $columns ) {
			$output .= '<br style="clear: both" />';
		}
	}

	if ( ! $html5 && $columns > 0 && 0 !== $i % $columns ) {
		$output .= "
			<br style='clear: both' />";
	}

	$output .= "
		</div>\n";

	return $output;
}

/**
 * Xuất các mẫu template được sử dụng bởi playlist.
 *
 * @since 3.9.0
 */
function wp_underscore_playlist_templates() {
	?>
<script type="text/html" id="tmpl-wp-playlist-current-item">
	<# if ( data.thumb && data.thumb.src ) { #>
		<img src="{{ data.thumb.src }}" alt="" />
	<# } #>
	<div class="wp-playlist-caption">
		<span class="wp-playlist-item-meta wp-playlist-item-title">
			<# if ( data.meta.album || data.meta.artist ) { #>
				<?php
				/* translators: %s: Playlist item title. */
				printf( _x( '&#8220;%s&#8221;', 'playlist item title' ), '{{ data.title }}' );
				?>
			<# } else { #>
				{{ data.title }}
			<# } #>
		</span>
		<# if ( data.meta.album ) { #><span class="wp-playlist-item-meta wp-playlist-item-album">{{ data.meta.album }}</span><# } #>
		<# if ( data.meta.artist ) { #><span class="wp-playlist-item-meta wp-playlist-item-artist">{{ data.meta.artist }}</span><# } #>
	</div>
</script>
<script type="text/html" id="tmpl-wp-playlist-item">
	<div class="wp-playlist-item">
		<a class="wp-playlist-caption" href="{{ data.src }}">
			{{ data.index ? ( data.index + '. ' ) : '' }}
			<# if ( data.caption ) { #>
				{{ data.caption }}
			<# } else { #>
				<# if ( data.artists && data.meta.artist ) { #>
					<span class="wp-playlist-item-title">
						<?php
						/* translators: %s: Playlist item title. */
						printf( _x( '&#8220;%s&#8221;', 'playlist item title' ), '{{{ data.title }}}' );
						?>
					</span>
					<span class="wp-playlist-item-artist"> &mdash; {{ data.meta.artist }}</span>
				<# } else { #>
					<span class="wp-playlist-item-title">{{{ data.title }}}</span>
				<# } #>
			<# } #>
		</a>
		<# if ( data.meta.length_formatted ) { #>
		<div class="wp-playlist-item-length">{{ data.meta.length_formatted }}</div>
		<# } #>
	</div>
</script>
	<?php
}

/**
 * Xuất và đưa vào hàng đợi các script và style mặc định cho playlist.
 *
 * @since 3.9.0
 *
 * @param string $type Loại playlist. Chấp nhận 'audio' hoặc 'video'.
 */
function wp_playlist_scripts( $type ) {
	wp_enqueue_style( 'wp-mediaelement' );
	wp_enqueue_script( 'wp-playlist' );
	?>
<!--[if lt IE 9]><script>document.createElement('<?php echo esc_js( $type ); ?>');</script><![endif]-->
	<?php
	add_action( 'wp_footer', 'wp_underscore_playlist_templates', 0 );
	add_action( 'admin_footer', 'wp_underscore_playlist_templates', 0 );
}

/**
 * Xây dựng đầu ra shortcode Playlist.
 *
 * Triển khai chức năng của shortcode playlist để hiển thị
 * bộ sưu tập các tệp âm thanh hoặc video WordPress trong bài viết.
 *
 * @since 3.9.0
 *
 * @global int $content_width
 *
 * @param array $attr {
 *     Mảng thuộc tính playlist mặc định.
 *
 *     @type string  $type         Loại playlist để hiển thị. Chấp nhận 'audio' hoặc 'video'. Mặc định 'audio'.
 *     @type string  $order        Chỉ định thứ tự tăng dần hoặc giảm dần của các mục trong playlist.
 *                                 Chấp nhận 'ASC', 'DESC'. Mặc định 'ASC'.
 *     @type string  $orderby      Bất kỳ cột nào để sắp xếp playlist. Nếu $ids được
 *                                 truyền, mặc định theo thứ tự mảng $ids ('post__in').
 *                                 Nếu không, mặc định là 'menu_order ID'.
 *     @type int     $id           Nếu mảng $ids rõ ràng không có, tham số này
 *                                 sẽ xác định đính kèm nào được sử dụng cho playlist.
 *                                 Mặc định là ID bài viết hiện tại.
 *     @type array   $ids          Tạo playlist từ các ID đính kèm rõ ràng này. Nếu rỗng,
 *                                 playlist sẽ được tạo từ tất cả đính kèm $type của $id.
 *                                 Mặc định rỗng.
 *     @type array   $exclude      Danh sách ID đính kèm cụ thể để loại trừ khỏi playlist. Mặc định rỗng.
 *     @type string  $style        Kiểu playlist để sử dụng. Chấp nhận 'light' hoặc 'dark'. Mặc định 'light'.
 *     @type bool    $tracklist    Có hiển thị hay ẩn playlist không. Mặc định true.
 *     @type bool    $tracknumbers Có hiển thị hay ẩn số thứ tự bên cạnh các mục trong playlist không. Mặc định true.
 *     @type bool    $images       Hiển thị hoặc ẩn ảnh thu nhỏ video hoặc âm thanh (Ảnh đại diện/ảnh thu nhỏ
 *                                 bài viết). Mặc định true.
 *     @type bool    $artists      Có hiển thị hay ẩn tên nghệ sĩ trong playlist không. Mặc định true.
 * }
 *
 * @return string Đầu ra playlist. Chuỗi rỗng nếu loại được truyền không được hỗ trợ.
 */
function wp_playlist_shortcode( $attr ) {
	global $content_width;
	$post = get_post();

	static $instance = 0;
	++$instance;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' được sắp xếp theo thứ tự rõ ràng, trừ khi bạn chỉ định khác.
		if ( empty( $attr['orderby'] ) ) {
			$attr['orderby'] = 'post__in';
		}
		$attr['include'] = $attr['ids'];
	}

	/**
	 * Lọc đầu ra danh sách phát.
	 *
	 * Trả về giá trị không rỗng từ bộ lọc sẽ bỏ qua việc tạo
	 * đầu ra danh sách phát mặc định, trả về giá trị đã truyền thay thế.
	 *
	 * @since 3.9.0
	 * @since 4.2.0 Tham số `$instance` đã được thêm.
	 *
	 * @param string $output   Đầu ra danh sách phát. Mặc định rỗng.
	 * @param array  $attr     Mảng các thuộc tính shortcode.
	 * @param int    $instance ID số duy nhất của phiên bản shortcode danh sách phát này.
	 */
	$output = apply_filters( 'post_playlist', '', $attr, $instance );

	if ( ! empty( $output ) ) {
		return $output;
	}

	$atts = shortcode_atts(
		array(
			'type'         => 'audio',
			'order'        => 'ASC',
			'orderby'      => 'menu_order ID',
			'id'           => $post ? $post->ID : 0,
			'include'      => '',
			'exclude'      => '',
			'style'        => 'light',
			'tracklist'    => true,
			'tracknumbers' => true,
			'images'       => true,
			'artists'      => true,
		),
		$attr,
		'playlist'
	);

	$id = (int) $atts['id'];

	if ( 'audio' !== $atts['type'] ) {
		$atts['type'] = 'video';
	}

	$args = array(
		'post_status'    => 'inherit',
		'post_type'      => 'attachment',
		'post_mime_type' => $atts['type'],
		'order'          => $atts['order'],
		'orderby'        => $atts['orderby'],
	);

	if ( ! empty( $atts['include'] ) ) {
		$args['include'] = $atts['include'];
		$_attachments    = get_posts( $args );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[ $val->ID ] = $_attachments[ $key ];
		}
	} elseif ( ! empty( $atts['exclude'] ) ) {
		$args['post_parent'] = $id;
		$args['exclude']     = $atts['exclude'];
		$attachments         = get_children( $args );
	} else {
		$args['post_parent'] = $id;
		$attachments         = get_children( $args );
	}

	if ( ! empty( $args['post_parent'] ) ) {
		$post_parent = get_post( $id );

		// Kết thúc thực thi shortcode nếu người dùng không thể đọc bài viết hoặc bài viết được bảo vệ bằng mật khẩu.
		if ( ! current_user_can( 'read_post', $post_parent->ID ) || post_password_required( $post_parent ) ) {
			return '';
		}
	}

	if ( empty( $attachments ) ) {
		return '';
	}

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment ) {
			$output .= wp_get_attachment_link( $att_id ) . "\n";
		}
		return $output;
	}

	$outer = 22; // Padding và border mặc định của wrapper.

	$default_width  = 640;
	$default_height = 360;

	$theme_width  = empty( $content_width ) ? $default_width : ( $content_width - $outer );
	$theme_height = empty( $content_width ) ? $default_height : round( ( $default_height * $theme_width ) / $default_width );

	$data = array(
		'type'         => $atts['type'],
		// Không truyền chuỗi cho JSON, sẽ là truthy trong JS.
		'tracklist'    => wp_validate_boolean( $atts['tracklist'] ),
		'tracknumbers' => wp_validate_boolean( $atts['tracknumbers'] ),
		'images'       => wp_validate_boolean( $atts['images'] ),
		'artists'      => wp_validate_boolean( $atts['artists'] ),
	);

	$tracks = array();
	foreach ( $attachments as $attachment ) {
		$url   = wp_get_attachment_url( $attachment->ID );
		$ftype = wp_check_filetype( $url, wp_get_mime_types() );
		$track = array(
			'src'         => $url,
			'type'        => $ftype['type'],
			'title'       => $attachment->post_title,
			'caption'     => $attachment->post_excerpt,
			'description' => $attachment->post_content,
		);

		$track['meta'] = array();
		$meta          = wp_get_attachment_metadata( $attachment->ID );
		if ( ! empty( $meta ) ) {

			foreach ( wp_get_attachment_id3_keys( $attachment ) as $key => $label ) {
				if ( ! empty( $meta[ $key ] ) ) {
					$track['meta'][ $key ] = $meta[ $key ];
				}
			}

			if ( 'video' === $atts['type'] ) {
				if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
					$width        = $meta['width'];
					$height       = $meta['height'];
					$theme_height = round( ( $height * $theme_width ) / $width );
				} else {
					$width  = $default_width;
					$height = $default_height;
				}

				$track['dimensions'] = array(
					'original' => compact( 'width', 'height' ),
					'resized'  => array(
						'width'  => $theme_width,
						'height' => $theme_height,
					),
				);
			}
		}

		if ( $atts['images'] ) {
			$thumb_id = get_post_thumbnail_id( $attachment->ID );
			if ( ! empty( $thumb_id ) ) {
				list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'full' );
				$track['image']               = compact( 'src', 'width', 'height' );
				list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
				$track['thumb']               = compact( 'src', 'width', 'height' );
			} else {
				$src            = wp_mime_type_icon( $attachment->ID, '.svg' );
				$width          = 48;
				$height         = 64;
				$track['image'] = compact( 'src', 'width', 'height' );
				$track['thumb'] = compact( 'src', 'width', 'height' );
			}
		}

		$tracks[] = $track;
	}
	$data['tracks'] = $tracks;

	$safe_type  = esc_attr( $atts['type'] );
	$safe_style = esc_attr( $atts['style'] );

	ob_start();

	if ( 1 === $instance ) {
		/**
		 * In và đưa vào hàng đợi các script, style và mẫu JavaScript của playlist.
		 *
		 * @since 3.9.0
		 *
		 * @param string $type  Loại playlist. Giá trị có thể là 'audio' hoặc 'video'.
		 * @param string $style 'Theme' cho playlist. Lõi cung cấp 'light' và 'dark'.
		 */
		do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
	}
	?>
<div class="wp-playlist wp-<?php echo $safe_type; ?>-playlist wp-playlist-<?php echo $safe_style; ?>">
	<?php if ( 'audio' === $atts['type'] ) : ?>
		<div class="wp-playlist-current-item"></div>
	<?php endif; ?>
	<<?php echo $safe_type; ?> controls="controls" preload="none" width="<?php echo (int) $theme_width; ?>"
		<?php
		if ( 'video' === $safe_type ) {
			echo ' height="', (int) $theme_height, '"';
		}
		?>
	></<?php echo $safe_type; ?>>
	<div class="wp-playlist-next"></div>
	<div class="wp-playlist-prev"></div>
	<noscript>
	<ol>
		<?php
		foreach ( $attachments as $att_id => $attachment ) {
			printf( '<li>%s</li>', wp_get_attachment_link( $att_id ) );
		}
		?>
	</ol>
	</noscript>
	<script type="application/json" class="wp-playlist-script"><?php echo wp_json_encode( $data ); ?></script>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'playlist', 'wp_playlist_shortcode' );

/**
 * Cung cấp phương án dự phòng Flash No-JS như giải pháp cuối cùng cho audio / video.
 *
 * @since 3.6.0
 *
 * @param string $url URL phần tử media.
 * @return string HTML dự phòng.
 */
function wp_mediaelement_fallback( $url ) {
	/**
	 * Lọc đầu ra dự phòng MediaElement cho No-JS.
	 *
	 * @since 3.6.0
	 *
	 * @param string $output Đầu ra dự phòng cho No-JS.
	 * @param string $url    URL tệp media.
	 */
	return apply_filters( 'wp_mediaelement_fallback', sprintf( '<a href="%1$s">%1$s</a>', esc_url( $url ) ), $url );
}

/**
 * Trả về danh sách đã lọc các định dạng âm thanh được hỗ trợ.
 *
 * @since 3.6.0
 *
 * @return string[] Các định dạng âm thanh được hỗ trợ.
 */
function wp_get_audio_extensions() {
	/**
	 * Lọc danh sách các định dạng âm thanh được hỗ trợ.
	 *
	 * @since 3.6.0
	 *
	 * @param string[] $extensions Mảng các định dạng âm thanh được hỗ trợ. Mặc định là
	 *                            'mp3', 'ogg', 'flac', 'm4a', 'wav'.
	 */
	return apply_filters( 'wp_audio_extensions', array( 'mp3', 'ogg', 'flac', 'm4a', 'wav' ) );
}

/**
 * Trả về các khóa hữu ích để tra cứu dữ liệu từ metadata đã lưu của đính kèm.
 *
 * @since 3.9.0
 *
 * @param WP_Post $attachment Đính kèm hiện tại, được cung cấp để làm ngữ cảnh.
 * @param string  $context    Tùy chọn. Ngữ cảnh. Chấp nhận 'edit', 'display'. Mặc định 'display'.
 * @return string[] Các cặp khóa/giá trị của khóa trường đến nhãn.
 */
function wp_get_attachment_id3_keys( $attachment, $context = 'display' ) {
	$fields = array(
		'artist' => __( 'Artist' ),
		'album'  => __( 'Album' ),
	);

	if ( 'display' === $context ) {
		$fields['genre']            = __( 'Genre' );
		$fields['year']             = __( 'Year' );
		$fields['length_formatted'] = _x( 'Length', 'video or audio' );
	} elseif ( 'js' === $context ) {
		$fields['bitrate']      = __( 'Bitrate' );
		$fields['bitrate_mode'] = __( 'Bitrate Mode' );
	}

	/**
	 * Lọc danh sách có thể chỉnh sửa các khóa để tra cứu dữ liệu từ metadata của đính kèm.
	 *
	 * @since 3.9.0
	 *
	 * @param array   $fields     Các cặp khóa/giá trị của khóa trường đến nhãn.
	 * @param WP_Post $attachment Đối tượng đính kèm.
	 * @param string  $context    Ngữ cảnh. Chấp nhận 'edit', 'display'. Mặc định 'display'.
	 */
	return apply_filters( 'wp_get_attachment_id3_keys', $fields, $attachment, $context );
}
/**
 * Tạo đầu ra shortcode Audio.
 *
 * Hàm này triển khai chức năng của Shortcode Audio để hiển thị
 * các tệp mp3 WordPress trong bài viết.
 *
 * @since 3.6.0
 * @since 6.8.0 Thuộc tính 'muted' đã được thêm.
 *
 * @param array  $attr {
 *     Các thuộc tính của shortcode audio.
 *
 *     @type string $src      URL đến nguồn tệp âm thanh. Mặc định rỗng.
 *     @type string $loop     Thuộc tính 'loop' cho phần tử `<audio>`. Mặc định rỗng.
 *     @type string $autoplay Thuộc tính 'autoplay' cho phần tử `<audio>`. Mặc định rỗng.
 *     @type string $muted    Thuộc tính 'muted' cho phần tử `<audio>`. Mặc định 'false'.
 *     @type string $preload  Thuộc tính 'preload' cho phần tử `<audio>`. Mặc định 'none'.
 *     @type string $class    Thuộc tính 'class' cho phần tử `<audio>`. Mặc định 'wp-audio-shortcode'.
 *     @type string $style    Thuộc tính 'style' cho phần tử `<audio>`. Mặc định 'width: 100%;'.
 * }
 * @param string $content Nội dung shortcode.
 * @return string|void Nội dung HTML để hiển thị âm thanh.
 */
function wp_audio_shortcode( $attr, $content = '' ) {
	$post_id = get_post() ? get_the_ID() : 0;

	static $instance = 0;
	++$instance;

	/**
	 * Lọc đầu ra shortcode audio mặc định.
	 *
	 * Nếu đầu ra đã lọc không rỗng, nó sẽ được sử dụng thay vì tạo mẫu audio mặc định.
	 *
	 * @since 3.6.0
	 *
	 * @param string $html     Biến rỗng sẽ được thay thế bằng markup shortcode.
	 * @param array  $attr     Các thuộc tính của shortcode. Xem {@see wp_audio_shortcode()}.
	 * @param string $content  Nội dung shortcode.
	 * @param int    $instance ID số duy nhất của phiên bản shortcode audio này.
	 */
	$override = apply_filters( 'wp_audio_shortcode_override', '', $attr, $content, $instance );

	if ( '' !== $override ) {
		return $override;
	}

	$audio = null;

	$default_types = wp_get_audio_extensions();
	$defaults_atts = array(
		'src'      => '',
		'loop'     => '',
		'autoplay' => '',
		'muted'    => 'false',
		'preload'  => 'none',
		'class'    => 'wp-audio-shortcode',
		'style'    => 'width: 100%;',
	);
	foreach ( $default_types as $type ) {
		$defaults_atts[ $type ] = '';
	}

	$atts = shortcode_atts( $defaults_atts, $attr, 'audio' );

	$primary = false;
	if ( ! empty( $atts['src'] ) ) {
		$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );

		if ( ! in_array( strtolower( $type['ext'] ), $default_types, true ) ) {
			return sprintf( '<a class="wp-embedded-audio" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
		}

		$primary = true;
		array_unshift( $default_types, 'src' );
	} else {
		foreach ( $default_types as $ext ) {
			if ( ! empty( $atts[ $ext ] ) ) {
				$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );

				if ( strtolower( $type['ext'] ) === $ext ) {
					$primary = true;
				}
			}
		}
	}

	if ( ! $primary ) {
		$audios = get_attached_media( 'audio', $post_id );

		if ( empty( $audios ) ) {
			return;
		}

		$audio       = reset( $audios );
		$atts['src'] = wp_get_attachment_url( $audio->ID );

		if ( empty( $atts['src'] ) ) {
			return;
		}

		array_unshift( $default_types, 'src' );
	}

	/**
	 * Lọc thư viện media được sử dụng cho shortcode audio.
	 *
	 * @since 3.6.0
	 *
	 * @param string $library Thư viện media được sử dụng cho shortcode audio.
	 */
	$library = apply_filters( 'wp_audio_shortcode_library', 'mediaelement' );

	if ( 'mediaelement' === $library && did_action( 'init' ) ) {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );
	}

	/**
	 * Lọc thuộc tính class cho container đầu ra shortcode audio.
	 *
	 * @since 3.6.0
	 * @since 4.9.0 Tham số `$atts` đã được thêm.
	 *
	 * @param string $class Tên class CSS hoặc danh sách các class phân cách bằng dấu cách.
	 * @param array  $atts  Mảng các thuộc tính shortcode audio.
	 */
	$atts['class'] = apply_filters( 'wp_audio_shortcode_class', $atts['class'], $atts );

	$html_atts = array(
		'class'    => $atts['class'],
		'id'       => sprintf( 'audio-%d-%d', $post_id, $instance ),
		'loop'     => wp_validate_boolean( $atts['loop'] ),
		'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
		'muted'    => wp_validate_boolean( $atts['muted'] ),
		'preload'  => $atts['preload'],
		'style'    => $atts['style'],
	);

	// Những thuộc tính này nên được bỏ qua hoàn toàn nếu chúng rỗng.
	foreach ( array( 'loop', 'autoplay', 'preload', 'muted' ) as $a ) {
		if ( empty( $html_atts[ $a ] ) ) {
			unset( $html_atts[ $a ] );
		}
	}

	$attr_strings = array();

	foreach ( $html_atts as $attribute_name => $attribute_value ) {
		if ( in_array( $attribute_name, array( 'loop', 'autoplay', 'muted' ), true ) && true === $attribute_value ) {
			// Thêm thuộc tính boolean mà không có giá trị.
			$attr_strings[] = esc_attr( $attribute_name );
		} elseif ( 'preload' === $attribute_name && ! empty( $attribute_value ) ) {
			// Xử lý thuộc tính preload với các giá trị được phép cụ thể.
			$allowed_preload_values = array( 'none', 'metadata', 'auto' );
			if ( in_array( $attribute_value, $allowed_preload_values, true ) ) {
				$attr_strings[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
			}
		} else {
			// Đối với các thuộc tính khác, bao gồm giá trị.
			$attr_strings[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
		}
	}

	$html = '';

	if ( 'mediaelement' === $library && 1 === $instance ) {
		$html .= "<!--[if lt IE 9]><script>document.createElement('audio');</script><![endif]-->\n";
	}

	$html .= sprintf( '<audio %s controls="controls">', implode( ' ', $attr_strings ) );

	$fileurl = '';
	$source  = '<source type="%s" src="%s" />';

	foreach ( $default_types as $fallback ) {
		if ( ! empty( $atts[ $fallback ] ) ) {
			if ( empty( $fileurl ) ) {
				$fileurl = $atts[ $fallback ];
			}

			$type  = wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() );
			$url   = add_query_arg( '_', $instance, $atts[ $fallback ] );
			$html .= sprintf( $source, $type['type'], esc_url( $url ) );
		}
	}

	if ( 'mediaelement' === $library ) {
		$html .= wp_mediaelement_fallback( $fileurl );
	}

	$html .= '</audio>';

	/**
	 * Lọc đầu ra shortcode audio.
	 *
	 * @since 3.6.0
	 *
	 * @param string $html    Đầu ra HTML shortcode audio.
	 * @param array  $atts    Mảng các thuộc tính shortcode audio.
	 * @param string $audio   Tệp âm thanh.
	 * @param int    $post_id ID bài viết.
	 * @param string $library Thư viện media được sử dụng cho shortcode audio.
	 */
	return apply_filters( 'wp_audio_shortcode', $html, $atts, $audio, $post_id, $library );
}
add_shortcode( 'audio', 'wp_audio_shortcode' );

/**
 * Trả về danh sách đã lọc các định dạng video được hỗ trợ.
 *
 * @since 3.6.0
 *
 * @return string[] Danh sách các định dạng video được hỗ trợ.
 */
function wp_get_video_extensions() {
	/**
	 * Lọc danh sách các định dạng video được hỗ trợ.
	 *
	 * @since 3.6.0
	 *
	 * @param string[] $extensions Mảng các định dạng video được hỗ trợ. Mặc định là
	 *                             'mp4', 'm4v', 'webm', 'ogv', 'flv'.
	 */
	return apply_filters( 'wp_video_extensions', array( 'mp4', 'm4v', 'webm', 'ogv', 'flv' ) );
}

/**
 * Tạo đầu ra shortcode Video.
 *
 * Hàm này triển khai chức năng của Shortcode Video để hiển thị
 * các tệp mp4 WordPress trong bài viết.
 *
 * @since 3.6.0
 *
 * @global int $content_width
 *
 * @param array  $attr {
 *     Các thuộc tính của shortcode.
 *
 *     @type string $src      URL đến nguồn tệp video. Mặc định rỗng.
 *     @type int    $height   Chiều cao của video nhúng tính bằng pixel. Mặc định 360.
 *     @type int    $width    Chiều rộng của video nhúng tính bằng pixel. Mặc định $content_width hoặc 640.
 *     @type string $poster   Thuộc tính 'poster' cho phần tử `<video>`. Mặc định rỗng.
 *     @type string $loop     Thuộc tính 'loop' cho phần tử `<video>`. Mặc định rỗng.
 *     @type string $autoplay Thuộc tính 'autoplay' cho phần tử `<video>`. Mặc định rỗng.
 *     @type string $muted    Thuộc tính 'muted' cho phần tử `<video>`. Mặc định false.
 *     @type string $preload  Thuộc tính 'preload' cho phần tử `<video>`.
 *                            Mặc định 'metadata'.
 *     @type string $class    Thuộc tính 'class' cho phần tử `<video>`.
 *                            Mặc định 'wp-video-shortcode'.
 * }
 * @param string $content Nội dung shortcode.
 * @return string|void Nội dung HTML để hiển thị video.
 */
function wp_video_shortcode( $attr, $content = '' ) {
	global $content_width;
	$post_id = get_post() ? get_the_ID() : 0;

	static $instance = 0;
	++$instance;

	/**
	 * Lọc đầu ra shortcode video mặc định.
	 *
	 * Nếu đầu ra đã lọc không rỗng, nó sẽ được sử dụng thay vì tạo
	 * mẫu video mặc định.
	 *
	 * @since 3.6.0
	 *
	 * @see wp_video_shortcode()
	 *
	 * @param string $html     Biến rỗng sẽ được thay thế bằng markup shortcode.
	 * @param array  $attr     Các thuộc tính của shortcode. Xem {@see wp_video_shortcode()}.
	 * @param string $content  Nội dung shortcode video.
	 * @param int    $instance ID số duy nhất của phiên bản shortcode video này.
	 */
	$override = apply_filters( 'wp_video_shortcode_override', '', $attr, $content, $instance );

	if ( '' !== $override ) {
		return $override;
	}

	$video = null;

	$default_types = wp_get_video_extensions();
	$defaults_atts = array(
		'src'      => '',
		'poster'   => '',
		'loop'     => '',
		'autoplay' => '',
		'muted'    => 'false',
		'preload'  => 'metadata',
		'width'    => 640,
		'height'   => 360,
		'class'    => 'wp-video-shortcode',
	);

	foreach ( $default_types as $type ) {
		$defaults_atts[ $type ] = '';
	}

	$atts = shortcode_atts( $defaults_atts, $attr, 'video' );

	if ( is_admin() ) {
		// Thu nhỏ video để nó không quá lớn trong trang quản trị.
		if ( $atts['width'] > $defaults_atts['width'] ) {
			$atts['height'] = round( ( $atts['height'] * $defaults_atts['width'] ) / $atts['width'] );
			$atts['width']  = $defaults_atts['width'];
		}
	} else {
		// Nếu video lớn hơn theme.
		if ( ! empty( $content_width ) && $atts['width'] > $content_width ) {
			$atts['height'] = round( ( $atts['height'] * $content_width ) / $atts['width'] );
			$atts['width']  = $content_width;
		}
	}

	$is_vimeo      = false;
	$is_youtube    = false;
	$yt_pattern    = '#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#';
	$vimeo_pattern = '#^https?://(.+\.)?vimeo\.com/.*#';

	$primary = false;
	if ( ! empty( $atts['src'] ) ) {
		$is_vimeo   = ( preg_match( $vimeo_pattern, $atts['src'] ) );
		$is_youtube = ( preg_match( $yt_pattern, $atts['src'] ) );

		if ( ! $is_youtube && ! $is_vimeo ) {
			$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );

			if ( ! in_array( strtolower( $type['ext'] ), $default_types, true ) ) {
				return sprintf( '<a class="wp-embedded-video" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
			}
		}

		if ( $is_vimeo ) {
			wp_enqueue_script( 'mediaelement-vimeo' );
		}

		$primary = true;
		array_unshift( $default_types, 'src' );
	} else {
		foreach ( $default_types as $ext ) {
			if ( ! empty( $atts[ $ext ] ) ) {
				$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );
				if ( strtolower( $type['ext'] ) === $ext ) {
					$primary = true;
				}
			}
		}
	}

	if ( ! $primary ) {
		$videos = get_attached_media( 'video', $post_id );
		if ( empty( $videos ) ) {
			return;
		}

		$video       = reset( $videos );
		$atts['src'] = wp_get_attachment_url( $video->ID );
		if ( empty( $atts['src'] ) ) {
			return;
		}

		array_unshift( $default_types, 'src' );
	}

	/**
	 * Lọc thư viện media được sử dụng cho shortcode video.
	 *
	 * @since 3.6.0
	 *
	 * @param string $library Thư viện media được sử dụng cho shortcode video.
	 */
	$library = apply_filters( 'wp_video_shortcode_library', 'mediaelement' );
	if ( 'mediaelement' === $library && did_action( 'init' ) ) {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );
		wp_enqueue_script( 'mediaelement-vimeo' );
	}

	/*
	 * MediaElement.js gặp vấn đề với một số định dạng URL cho Vimeo và YouTube,
	 * nên cập nhật URL để tránh trình phát ME.js bị hỏng.
	 */
	if ( 'mediaelement' === $library ) {
		if ( $is_youtube ) {
			// Xóa tham số truy vấn `feature` và buộc SSL - xem #40866.
			$atts['src'] = remove_query_arg( 'feature', $atts['src'] );
			$atts['src'] = set_url_scheme( $atts['src'], 'https' );
		} elseif ( $is_vimeo ) {
			// Xóa tất cả tham số truy vấn và buộc SSL - xem #40866.
			$parsed_vimeo_url = wp_parse_url( $atts['src'] );
			$vimeo_src        = 'https://' . $parsed_vimeo_url['host'] . $parsed_vimeo_url['path'];

			// Thêm tham số loop cho lỗi mejs - xem #40977, không cần sau #39686.
			$loop        = $atts['loop'] ? '1' : '0';
			$atts['src'] = add_query_arg( 'loop', $loop, $vimeo_src );
		}
	}

	/**
	 * Lọc thuộc tính class cho vùng chứa đầu ra shortcode video.
	 *
	 * @since 3.6.0
	 * @since 4.9.0 Thêm tham số `$atts`.
	 *
	 * @param string $class Lớp CSS hoặc danh sách các lớp phân cách bằng dấu cách.
	 * @param array  $atts  Mảng các thuộc tính shortcode video.
	 */
	$atts['class'] = apply_filters( 'wp_video_shortcode_class', $atts['class'], $atts );

	$html_atts = array(
		'class'    => $atts['class'],
		'id'       => sprintf( 'video-%d-%d', $post_id, $instance ),
		'width'    => absint( $atts['width'] ),
		'height'   => absint( $atts['height'] ),
		'poster'   => esc_url( $atts['poster'] ),
		'loop'     => wp_validate_boolean( $atts['loop'] ),
		'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
		'muted'    => wp_validate_boolean( $atts['muted'] ),
		'preload'  => $atts['preload'],
	);

	// Những thuộc tính này nên được bỏ qua hoàn toàn nếu chúng rỗng.
	foreach ( array( 'poster', 'loop', 'autoplay', 'preload', 'muted' ) as $a ) {
		if ( empty( $html_atts[ $a ] ) ) {
			unset( $html_atts[ $a ] );
		}
	}

	$attr_strings = array();
	foreach ( $html_atts as $attribute_name => $attribute_value ) {
		if ( in_array( $attribute_name, array( 'loop', 'autoplay', 'muted' ), true ) && true === $attribute_value ) {
			// Thêm thuộc tính boolean mà không có giá trị cho true.
			$attr_strings[] = esc_attr( $attribute_name );
		} elseif ( 'preload' === $attribute_name && ! empty( $attribute_value ) ) {
			// Xử lý thuộc tính preload với các giá trị được phép cụ thể.
			$allowed_preload_values = array( 'none', 'metadata', 'auto' );
			if ( in_array( $attribute_value, $allowed_preload_values, true ) ) {
				$attr_strings[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
			}
		} elseif ( ! empty( $attribute_value ) ) {
			// Cho các thuộc tính không phải boolean, thêm chúng cùng với giá trị.
			$attr_strings[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
		}
	}

	$html = '';

	if ( 'mediaelement' === $library && 1 === $instance ) {
		$html .= "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->\n";
	}

	$html .= sprintf( '<video %s controls="controls">', implode( ' ', $attr_strings ) );

	$fileurl = '';
	$source  = '<source type="%s" src="%s" />';

	foreach ( $default_types as $fallback ) {
		if ( ! empty( $atts[ $fallback ] ) ) {
			if ( empty( $fileurl ) ) {
				$fileurl = $atts[ $fallback ];
			}
			if ( 'src' === $fallback && $is_youtube ) {
				$type = array( 'type' => 'video/youtube' );
			} elseif ( 'src' === $fallback && $is_vimeo ) {
				$type = array( 'type' => 'video/vimeo' );
			} else {
				$type = wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() );
			}
			$url   = add_query_arg( '_', $instance, $atts[ $fallback ] );
			$html .= sprintf( $source, $type['type'], esc_url( $url ) );
		}
	}

	if ( ! empty( $content ) ) {
		if ( str_contains( $content, "\n" ) ) {
			$content = str_replace( array( "\r\n", "\n", "\t" ), '', $content );
		}
		$html .= trim( $content );
	}

	if ( 'mediaelement' === $library ) {
		$html .= wp_mediaelement_fallback( $fileurl );
	}
	$html .= '</video>';

	$width_rule = '';
	if ( ! empty( $atts['width'] ) ) {
		$width_rule = sprintf( 'width: %dpx;', $atts['width'] );
	}
	$output = sprintf( '<div style="%s" class="wp-video">%s</div>', $width_rule, $html );

	/**
	 * Lọc đầu ra của shortcode video.
	 *
	 * @since 3.6.0
	 *
	 * @param string $output  Đầu ra HTML shortcode video.
	 * @param array  $atts    Mảng các thuộc tính shortcode video.
	 * @param string $video   Tệp video.
	 * @param int    $post_id ID bài viết.
	 * @param string $library Thư viện media được sử dụng cho shortcode video.
	 */
	return apply_filters( 'wp_video_shortcode', $output, $atts, $video, $post_id, $library );
}
add_shortcode( 'video', 'wp_video_shortcode' );

/**
 * Lấy liên kết hình ảnh trước đó có cùng bài viết cha.
 *
 * @since 5.8.0
 *
 * @see get_adjacent_image_link()
 *
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param string|false $text Tùy chọn. Văn bản liên kết. Mặc định false.
 * @return string Markup cho liên kết hình ảnh trước đó.
 */
function get_previous_image_link( $size = 'thumbnail', $text = false ) {
	return get_adjacent_image_link( true, $size, $text );
}

/**
 * Hiển thị liên kết hình ảnh trước đó có cùng bài viết cha.
 *
 * @since 2.5.0
 *
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param string|false $text Tùy chọn. Văn bản liên kết. Mặc định false.
 */
function previous_image_link( $size = 'thumbnail', $text = false ) {
	echo get_previous_image_link( $size, $text );
}

/**
 * Lấy liên kết hình ảnh tiếp theo có cùng bài viết cha.
 *
 * @since 5.8.0
 *
 * @see get_adjacent_image_link()
 *
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param string|false $text Tùy chọn. Văn bản liên kết. Mặc định false.
 * @return string Markup cho liên kết hình ảnh tiếp theo.
 */
function get_next_image_link( $size = 'thumbnail', $text = false ) {
	return get_adjacent_image_link( false, $size, $text );
}

/**
 * Hiển thị liên kết hình ảnh tiếp theo có cùng bài viết cha.
 *
 * @since 2.5.0
 *
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param string|false $text Tùy chọn. Văn bản liên kết. Mặc định false.
 */
function next_image_link( $size = 'thumbnail', $text = false ) {
	echo get_next_image_link( $size, $text );
}

/**
 * Lấy liên kết hình ảnh tiếp theo hoặc trước đó có cùng bài viết cha.
 *
 * Lấy đối tượng đính kèm hiện tại từ biến toàn cục $post.
 *
 * @since 5.8.0
 *
 * @param bool         $prev Tùy chọn. Hiển thị liên kết tiếp theo (false) hay trước đó (true). Mặc định true.
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param bool         $text Tùy chọn. Văn bản liên kết. Mặc định false.
 * @return string Markup cho liên kết hình ảnh.
 */
function get_adjacent_image_link( $prev = true, $size = 'thumbnail', $text = false ) {
	$post        = get_post();
	$attachments = array_values(
		get_children(
			array(
				'post_parent'    => $post->post_parent,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => 'ASC',
				'orderby'        => 'menu_order ID',
			)
		)
	);

	foreach ( $attachments as $k => $attachment ) {
		if ( (int) $attachment->ID === (int) $post->ID ) {
			break;
		}
	}

	$output        = '';
	$attachment_id = 0;

	if ( $attachments ) {
		$k = $prev ? $k - 1 : $k + 1;

		if ( isset( $attachments[ $k ] ) ) {
			$attachment_id = $attachments[ $k ]->ID;
			$attr          = array( 'alt' => get_the_title( $attachment_id ) );
			$output        = wp_get_attachment_link( $attachment_id, $size, true, false, $text, $attr );
		}
	}

	$adjacent = $prev ? 'previous' : 'next';

	/**
	 * Lọc liên kết hình ảnh liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại liền kề,
	 * có thể là 'next' hoặc 'previous'.
	 *
	 * Tên hook có thể bao gồm:
	 *
	 *  - `next_image_link`
	 *  - `previous_image_link`
	 *
	 * @since 3.5.0
	 *
	 * @param string $output        Markup HTML hình ảnh liền kề.
	 * @param int    $attachment_id ID đính kèm.
	 * @param string|int[] $size    Kích thước hình ảnh được yêu cầu. Có thể là tên kích thước ảnh đã đăng ký, hoặc
	 *                              mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param string $text          Văn bản liên kết.
	 */
	return apply_filters( "{$adjacent}_image_link", $output, $attachment_id, $size, $text );
}

/**
 * Hiển thị liên kết hình ảnh tiếp theo hoặc trước đó có cùng bài viết cha.
 *
 * Lấy đối tượng đính kèm hiện tại từ biến toàn cục $post.
 *
 * @since 2.5.0
 *
 * @param bool         $prev Tùy chọn. Hiển thị liên kết tiếp theo (false) hay trước đó (true). Mặc định true.
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận tên kích thước ảnh đã đăng ký, hoặc mảng
 *                           giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó). Mặc định 'thumbnail'.
 * @param bool         $text Tùy chọn. Văn bản liên kết. Mặc định false.
 */
function adjacent_image_link( $prev = true, $size = 'thumbnail', $text = false ) {
	echo get_adjacent_image_link( $prev, $size, $text );
}

/**
 * Lấy các taxonomy được gắn vào đính kèm đã cho.
 *
 * @since 2.5.0
 * @since 4.7.0 Giới thiệu tham số `$output`.
 *
 * @param int|array|object $attachment ID đính kèm, mảng dữ liệu, hoặc đối tượng dữ liệu.
 * @param string           $output     Loại đầu ra. 'names' để trả về mảng tên taxonomy,
 *                                     hoặc 'objects' để trả về mảng đối tượng taxonomy.
 *                                     Mặc định 'names'.
 * @return string[]|WP_Taxonomy[] Danh sách taxonomy hoặc tên taxonomy. Mảng rỗng khi thất bại.
 */
function get_attachment_taxonomies( $attachment, $output = 'names' ) {
	if ( is_int( $attachment ) ) {
		$attachment = get_post( $attachment );
	} elseif ( is_array( $attachment ) ) {
		$attachment = (object) $attachment;
	}

	if ( ! is_object( $attachment ) ) {
		return array();
	}

	$file     = get_attached_file( $attachment->ID );
	$filename = wp_basename( $file );

	$objects = array( 'attachment' );

	if ( str_contains( $filename, '.' ) ) {
		$objects[] = 'attachment:' . substr( $filename, strrpos( $filename, '.' ) + 1 );
	}

	if ( ! empty( $attachment->post_mime_type ) ) {
		$objects[] = 'attachment:' . $attachment->post_mime_type;

		if ( str_contains( $attachment->post_mime_type, '/' ) ) {
			foreach ( explode( '/', $attachment->post_mime_type ) as $token ) {
				if ( ! empty( $token ) ) {
					$objects[] = "attachment:$token";
				}
			}
		}
	}

	$taxonomies = array();

	foreach ( $objects as $object ) {
		$taxes = get_object_taxonomies( $object, $output );

		if ( $taxes ) {
			$taxonomies = array_merge( $taxonomies, $taxes );
		}
	}

	if ( 'names' === $output ) {
		$taxonomies = array_unique( $taxonomies );
	}

	return $taxonomies;
}

/**
 * Lấy tất cả taxonomy đã đăng ký cho đính kèm.
 *
 * Xử lý các taxonomy cụ thể theo loại mime như attachment:image và attachment:video.
 *
 * @since 3.5.0
 *
 * @see get_taxonomies()
 *
 * @param string $output Tùy chọn. Loại đầu ra taxonomy cần trả về. Chấp nhận 'names' hoặc 'objects'.
 *                       Mặc định 'names'.
 * @return string[]|WP_Taxonomy[] Mảng tên hoặc đối tượng các taxonomy đã đăng ký cho đính kèm.
 */
function get_taxonomies_for_attachments( $output = 'names' ) {
	$taxonomies = array();

	foreach ( get_taxonomies( array(), 'objects' ) as $taxonomy ) {
		foreach ( $taxonomy->object_type as $object_type ) {
			if ( 'attachment' === $object_type || str_starts_with( $object_type, 'attachment:' ) ) {
				if ( 'names' === $output ) {
					$taxonomies[] = $taxonomy->name;
				} else {
					$taxonomies[ $taxonomy->name ] = $taxonomy;
				}
				break;
			}
		}
	}

	return $taxonomies;
}

/**
 * Xác định xem giá trị có phải là kiểu được chấp nhận cho các hàm hình ảnh GD không.
 *
 * Trong PHP 8.0, phần mở rộng GD sử dụng đối tượng GdImage cho cấu trúc dữ liệu.
 * Hàm này kiểm tra xem giá trị được truyền có phải là instance đối tượng GdImage
 * hoặc tài nguyên kiểu `gd` không. Bất kỳ kiểu nào khác sẽ trả về false.
 *
 * @since 5.6.0
 *
 * @param resource|GdImage|false $image Giá trị để kiểm tra kiểu.
 * @return bool True nếu `$image` là tài nguyên hình ảnh GD hoặc instance GdImage,
 *              false trong trường hợp khác.
 */
function is_gd_image( $image ) {
	if ( $image instanceof GdImage
		|| is_resource( $image ) && 'gd' === get_resource_type( $image )
	) {
		return true;
	}

	return false;
}

/**
 * Tạo tài nguyên hình ảnh GD mới với hỗ trợ trong suốt.
 *
 * @todo Ngưng sử dụng nếu có thể.
 *
 * @since 2.9.0
 *
 * @param int $width  Chiều rộng hình ảnh tính bằng pixel.
 * @param int $height Chiều cao hình ảnh tính bằng pixel.
 * @return resource|GdImage|false Tài nguyên hình ảnh GD hoặc instance GdImage khi thành công.
 *                                False khi thất bại.
 */
function wp_imagecreatetruecolor( $width, $height ) {
	$img = imagecreatetruecolor( $width, $height );

	if ( is_gd_image( $img )
		&& function_exists( 'imagealphablending' ) && function_exists( 'imagesavealpha' )
	) {
		imagealphablending( $img, false );
		imagesavealpha( $img, true );
	}

	return $img;
}

/**
 * Dựa trên ví dụ chiều rộng/chiều cao được cung cấp, trả về kích thước lớn nhất có thể dựa trên chiều rộng/chiều cao tối đa.
 *
 * @since 2.9.0
 *
 * @see wp_constrain_dimensions()
 *
 * @param int $example_width  Chiều rộng của embed ví dụ.
 * @param int $example_height Chiều cao của embed ví dụ.
 * @param int $max_width      Chiều rộng tối đa cho phép.
 * @param int $max_height     Chiều cao tối đa cho phép.
 * @return int[] {
 *     Mảng các giá trị chiều rộng và chiều cao tối đa.
 *
 *     @type int $0 Chiều rộng tối đa tính bằng pixel.
 *     @type int $1 Chiều cao tối đa tính bằng pixel.
 * }
 */
function wp_expand_dimensions( $example_width, $example_height, $max_width, $max_height ) {
	$example_width  = (int) $example_width;
	$example_height = (int) $example_height;
	$max_width      = (int) $max_width;
	$max_height     = (int) $max_height;

	return wp_constrain_dimensions( $example_width * 1000000, $example_height * 1000000, $max_width, $max_height );
}

/**
 * Xác định kích thước tải lên tối đa được phép trong php.ini.
 *
 * @since 2.5.0
 *
 * @return int Kích thước tải lên được phép.
 */
function wp_max_upload_size() {
	$u_bytes = wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
	$p_bytes = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );

	/**
	 * Lọc kích thước tải lên tối đa được phép trong php.ini.
	 *
	 * @since 2.5.0
	 *
	 * @param int $size    Giới hạn kích thước tải lên tối đa tính bằng byte.
	 * @param int $u_bytes Kích thước tệp tải lên tối đa tính bằng byte.
	 * @param int $p_bytes Kích thước tối đa của dữ liệu POST tính bằng byte.
	 */
	return apply_filters( 'upload_size_limit', min( $u_bytes, $p_bytes ), $u_bytes, $p_bytes );
}

/**
 * Trả về instance WP_Image_Editor và tải tệp vào đó.
 *
 * @since 3.5.0
 *
 * @param string $path Đường dẫn đến tệp cần tải.
 * @param array  $args Tùy chọn. Các tham số bổ sung để lấy trình chỉnh sửa hình ảnh.
 *                     Mặc định mảng rỗng.
 * @return WP_Image_Editor|WP_Error Đối tượng WP_Image_Editor khi thành công,
 *                                  đối tượng WP_Error trong trường hợp khác.
 */
function wp_get_image_editor( $path, $args = array() ) {
	$args['path'] = $path;

	// Nếu loại mime chưa được đặt trong args, thử trích xuất và đặt nó từ tệp.
	if ( ! isset( $args['mime_type'] ) ) {
		$file_info = wp_check_filetype( $args['path'] );

		/*
		 * Nếu $file_info['type'] là false, thì chúng ta để trình chỉnh sửa cố gắng
		 * xác định loại tệp, thay vì buộc thất bại dựa trên phần mở rộng.
		 */
		if ( isset( $file_info ) && $file_info['type'] ) {
			$args['mime_type'] = $file_info['type'];
		}
	}

	// Kiểm tra và đặt loại mime đầu ra được ánh xạ từ loại đầu vào.
	if ( isset( $args['mime_type'] ) ) {
		$output_format = wp_get_image_editor_output_format( $path, $args['mime_type'] );
		if ( isset( $output_format[ $args['mime_type'] ] ) ) {
			$args['output_mime_type'] = $output_format[ $args['mime_type'] ];
		}
	}

	$implementation = _wp_image_editor_choose( $args );

	if ( $implementation ) {
		$editor = new $implementation( $path );
		$loaded = $editor->load();

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		return $editor;
	}

	return new WP_Error( 'image_no_editor', __( 'No editor could be selected.' ) );
}

/**
 * Kiểm tra xem có trình chỉnh sửa nào hỗ trợ loại mime hoặc phương thức đã cho không.
 *
 * @since 3.5.0
 *
 * @param string|array $args Tùy chọn. Mảng tham số để lấy hỗ trợ của trình chỉnh sửa hình ảnh.
 *                           Mặc định mảng rỗng.
 * @return bool True nếu tìm thấy trình chỉnh sửa đủ điều kiện; false trong trường hợp khác.
 */
function wp_image_editor_supports( $args = array() ) {
	return (bool) _wp_image_editor_choose( $args );
}

/**
 * Kiểm tra những trình chỉnh sửa nào có khả năng hỗ trợ yêu cầu.
 *
 * @ignore
 * @since 3.5.0
 *
 * @param array $args Tùy chọn. Mảng tham số để chọn trình chỉnh sửa có khả năng. Mặc định mảng rỗng.
 * @return string|false Tên lớp cho trình chỉnh sửa đầu tiên tuyên bố hỗ trợ yêu cầu.
 *                      False nếu không có trình chỉnh sửa nào tuyên bố hỗ trợ yêu cầu.
 */
function _wp_image_editor_choose( $args = array() ) {
	require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
	require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
	require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
	require_once ABSPATH . WPINC . '/class-avif-info.php';
	/**
	 * Lọc danh sách các lớp thư viện chỉnh sửa hình ảnh.
	 *
	 * @since 3.5.0
	 *
	 * @param string[] $image_editors Mảng tên lớp trình chỉnh sửa hình ảnh có sẵn. Mặc định là
	 *                                'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'.
	 */
	$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

	$editors = wp_cache_get( 'wp_image_editor_choose', 'image_editor' );

	if ( ! is_array( $editors ) ) {
		$editors = array();
	}

	// Lưu cache triển khai trình chỉnh sửa được chọn dựa trên args cụ thể và các triển khai có sẵn.
	$cache_key = md5( serialize( array( $args, $implementations ) ) );

	if ( isset( $editors[ $cache_key ] ) ) {
		return $editors[ $cache_key ];
	}

	// Giả định không hỗ trợ cho đến khi xác định được triển khai có khả năng.
	$editor = false;

	foreach ( $implementations as $implementation ) {
		if ( ! call_user_func( array( $implementation, 'test' ), $args ) ) {
			continue;
		}

		// Implementation should support the passed mime type.
		if ( isset( $args['mime_type'] ) &&
			! call_user_func(
				array( $implementation, 'supports_mime_type' ),
				$args['mime_type']
			) ) {
			continue;
		}

		// Implementation should support requested methods.
		if ( isset( $args['methods'] ) &&
			array_diff( $args['methods'], get_class_methods( $implementation ) ) ) {

			continue;
		}

		// Implementation should ideally support the output mime type as well if set and different than the passed type.
		if (
			isset( $args['mime_type'] ) &&
			isset( $args['output_mime_type'] ) &&
			$args['mime_type'] !== $args['output_mime_type'] &&
			! call_user_func( array( $implementation, 'supports_mime_type' ), $args['output_mime_type'] )
		) {
			/*
			 * This implementation supports the input type but not the output type.
			 * Keep looking to see if we can find an implementation that supports both.
			 */
			$editor = $implementation;
			continue;
		}

		// Favor the implementation that supports both input and output mime types.
		$editor = $implementation;
		break;
	}

	$editors[ $cache_key ] = $editor;

	wp_cache_set( 'wp_image_editor_choose', $editors, 'image_editor', DAY_IN_SECONDS );

	return $editor;
}

/**
 * Prints default Plupload arguments.
 *
 * @since 3.4.0
 */
function wp_plupload_default_settings() {
	$wp_scripts = wp_scripts();

	$data = $wp_scripts->get_data( 'wp-plupload', 'data' );
	if ( $data && str_contains( $data, '_wpPluploadSettings' ) ) {
		return;
	}

	$max_upload_size    = wp_max_upload_size();
	$allowed_extensions = array_keys( get_allowed_mime_types() );
	$extensions         = array();
	foreach ( $allowed_extensions as $extension ) {
		$extensions = array_merge( $extensions, explode( '|', $extension ) );
	}

	/*
	 * Since 4.9 the `runtimes` setting is hardcoded in our version of Plupload to `html5,html4`,
	 * and the `flash_swf_url` and `silverlight_xap_url` are not used.
	 */
	$defaults = array(
		'file_data_name' => 'async-upload', // Key passed to $_FILE.
		'url'            => admin_url( 'async-upload.php', 'relative' ),
		'filters'        => array(
			'max_file_size' => $max_upload_size . 'b',
			'mime_types'    => array( array( 'extensions' => implode( ',', $extensions ) ) ),
		),
	);

	/*
	 * Currently only iOS Safari supports multiple files uploading,
	 * but iOS 7.x has a bug that prevents uploading of videos when enabled.
	 * See #29602.
	 */
	if ( wp_is_mobile()
		&& str_contains( $_SERVER['HTTP_USER_AGENT'], 'OS 7_' )
		&& str_contains( $_SERVER['HTTP_USER_AGENT'], 'like Mac OS X' )
	) {
		$defaults['multi_selection'] = false;
	}

	// Check if WebP images can be edited.
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$defaults['webp_upload_error'] = true;
	}

	// Check if AVIF images can be edited.
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) ) ) {
		$defaults['avif_upload_error'] = true;
	}

	// Check if HEIC images can be edited.
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/heic' ) ) ) {
		$defaults['heic_upload_error'] = true;
	}

	/**
	 * Filters the Plupload default settings.
	 *
	 * @since 3.4.0
	 *
	 * @param array $defaults Default Plupload settings array.
	 */
	$defaults = apply_filters( 'plupload_default_settings', $defaults );

	$params = array(
		'action' => 'upload-attachment',
	);

	/**
	 * Filters the Plupload default parameters.
	 *
	 * @since 3.4.0
	 *
	 * @param array $params Default Plupload parameters array.
	 */
	$params = apply_filters( 'plupload_default_params', $params );

	$params['_wpnonce'] = wp_create_nonce( 'media-form' );

	$defaults['multipart_params'] = $params;

	$settings = array(
		'defaults'      => $defaults,
		'browser'       => array(
			'mobile'    => wp_is_mobile(),
			'supported' => _device_can_upload(),
		),
		'limitExceeded' => is_multisite() && ! is_upload_space_available(),
	);

	$script = 'var _wpPluploadSettings = ' . wp_json_encode( $settings ) . ';';

	if ( $data ) {
		$script = "$data\n$script";
	}

	$wp_scripts->add_data( 'wp-plupload', 'data', $script );
}

/**
 * Prepares an attachment post object for JS, where it is expected
 * to be JSON-encoded and fit into an Attachment model.
 *
 * @since 3.5.0
 *
 * @param int|WP_Post $attachment Attachment ID or object.
 * @return array|void {
 *     Array of attachment details, or void if the parameter does not correspond to an attachment.
 *
 *     @type string $alt                   Alt text of the attachment.
 *     @type string $author                ID of the attachment author, as a string.
 *     @type string $authorName            Name of the attachment author.
 *     @type string $caption               Caption for the attachment.
 *     @type array  $compat                Containing item and meta.
 *     @type string $context               Context, whether it's used as the site icon for example.
 *     @type int    $date                  Uploaded date, timestamp in milliseconds.
 *     @type string $dateFormatted         Formatted date (e.g. June 29, 2018).
 *     @type string $description           Description of the attachment.
 *     @type string $editLink              URL to the edit page for the attachment.
 *     @type string $filename              File name of the attachment.
 *     @type string $filesizeHumanReadable Filesize of the attachment in human readable format (e.g. 1 MB).
 *     @type int    $filesizeInBytes       Filesize of the attachment in bytes.
 *     @type int    $height                If the attachment is an image, represents the height of the image in pixels.
 *     @type string $icon                  Icon URL of the attachment (e.g. /wp-includes/images/media/archive.png).
 *     @type int    $id                    ID of the attachment.
 *     @type string $link                  URL to the attachment.
 *     @type int    $menuOrder             Menu order of the attachment post.
 *     @type array  $meta                  Meta data for the attachment.
 *     @type string $mime                  Mime type of the attachment (e.g. image/jpeg or application/zip).
 *     @type int    $modified              Last modified, timestamp in milliseconds.
 *     @type string $name                  Name, same as title of the attachment.
 *     @type array  $nonces                Nonces for update, delete and edit.
 *     @type string $orientation           If the attachment is an image, represents the image orientation
 *                                         (landscape or portrait).
 *     @type array  $sizes                 If the attachment is an image, contains an array of arrays
 *                                         for the images sizes: thumbnail, medium, large, and full.
 *     @type string $status                Post status of the attachment (usually 'inherit').
 *     @type string $subtype               Mime subtype of the attachment (usually the last part, e.g. jpeg or zip).
 *     @type string $title                 Title of the attachment (usually slugified file name without the extension).
 *     @type string $type                  Type of the attachment (usually first part of the mime type, e.g. image).
 *     @type int    $uploadedTo            Parent post to which the attachment was uploaded.
 *     @type string $uploadedToLink        URL to the edit page of the parent post of the attachment.
 *     @type string $uploadedToTitle       Post title of the parent of the attachment.
 *     @type string $url                   Direct URL to the attachment file (from wp-content).
 *     @type int    $width                 If the attachment is an image, represents the width of the image in pixels.
 * }
 */
function wp_prepare_attachment_for_js( $attachment ) {
	$attachment = get_post( $attachment );

	if ( ! $attachment ) {
		return;
	}

	if ( 'attachment' !== $attachment->post_type ) {
		return;
	}

	$meta = wp_get_attachment_metadata( $attachment->ID );
	if ( str_contains( $attachment->post_mime_type, '/' ) ) {
		list( $type, $subtype ) = explode( '/', $attachment->post_mime_type );
	} else {
		list( $type, $subtype ) = array( $attachment->post_mime_type, '' );
	}

	$attachment_url = wp_get_attachment_url( $attachment->ID );
	$base_url       = str_replace( wp_basename( $attachment_url ), '', $attachment_url );

	$response = array(
		'id'            => $attachment->ID,
		'title'         => $attachment->post_title,
		'filename'      => wp_basename( get_attached_file( $attachment->ID ) ),
		'url'           => $attachment_url,
		'link'          => get_attachment_link( $attachment->ID ),
		'alt'           => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
		'author'        => $attachment->post_author,
		'description'   => $attachment->post_content,
		'caption'       => $attachment->post_excerpt,
		'name'          => $attachment->post_name,
		'status'        => $attachment->post_status,
		'uploadedTo'    => $attachment->post_parent,
		'date'          => strtotime( $attachment->post_date_gmt ) * 1000,
		'modified'      => strtotime( $attachment->post_modified_gmt ) * 1000,
		'menuOrder'     => $attachment->menu_order,
		'mime'          => $attachment->post_mime_type,
		'type'          => $type,
		'subtype'       => $subtype,
		'icon'          => wp_mime_type_icon( $attachment->ID, '.svg' ),
		'dateFormatted' => mysql2date( __( 'F j, Y' ), $attachment->post_date ),
		'nonces'        => array(
			'update' => false,
			'delete' => false,
			'edit'   => false,
		),
		'editLink'      => false,
		'meta'          => false,
	);

	$author = new WP_User( $attachment->post_author );

	if ( $author->exists() ) {
		$author_name            = $author->display_name ? $author->display_name : $author->nickname;
		$response['authorName'] = html_entity_decode( $author_name, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$response['authorLink'] = get_edit_user_link( $author->ID );
	} else {
		$response['authorName'] = __( '(no author)' );
	}

	if ( $attachment->post_parent ) {
		$post_parent = get_post( $attachment->post_parent );
		if ( $post_parent ) {
			$response['uploadedToTitle'] = $post_parent->post_title ? $post_parent->post_title : __( '(no title)' );
			$response['uploadedToLink']  = get_edit_post_link( $attachment->post_parent, 'raw' );
		}
	}

	$attached_file = get_attached_file( $attachment->ID );

	if ( isset( $meta['filesize'] ) ) {
		$bytes = $meta['filesize'];
	} elseif ( file_exists( $attached_file ) ) {
		$bytes = wp_filesize( $attached_file );
	} else {
		$bytes = '';
	}

	if ( $bytes ) {
		$response['filesizeInBytes']       = $bytes;
		$response['filesizeHumanReadable'] = size_format( $bytes );
	}

	$context             = get_post_meta( $attachment->ID, '_wp_attachment_context', true );
	$response['context'] = ( $context ) ? $context : '';

	if ( current_user_can( 'edit_post', $attachment->ID ) ) {
		$response['nonces']['update'] = wp_create_nonce( 'update-post_' . $attachment->ID );
		$response['nonces']['edit']   = wp_create_nonce( 'image_editor-' . $attachment->ID );
		$response['editLink']         = get_edit_post_link( $attachment->ID, 'raw' );
	}

	if ( current_user_can( 'delete_post', $attachment->ID ) ) {
		$response['nonces']['delete'] = wp_create_nonce( 'delete-post_' . $attachment->ID );
	}

	if ( $meta && ( 'image' === $type || ! empty( $meta['sizes'] ) ) ) {
		$sizes = array();

		/** This filter is documented in wp-admin/includes/media.php */
		$possible_sizes = apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail' ),
				'medium'    => __( 'Medium' ),
				'large'     => __( 'Large' ),
				'full'      => __( 'Full Size' ),
			)
		);
		unset( $possible_sizes['full'] );

		/*
		 * Loop through all potential sizes that may be chosen. Try to do this with some efficiency.
		 * First: run the image_downsize filter. If it returns something, we can use its data.
		 * If the filter does not return something, then image_downsize() is just an expensive way
		 * to check the image metadata, which we do second.
		 */
		foreach ( $possible_sizes as $size => $label ) {

			/** This filter is documented in wp-includes/media.php */
			$downsize = apply_filters( 'image_downsize', false, $attachment->ID, $size );

			if ( $downsize ) {
				if ( empty( $downsize[3] ) ) {
					continue;
				}

				$sizes[ $size ] = array(
					'height'      => $downsize[2],
					'width'       => $downsize[1],
					'url'         => $downsize[0],
					'orientation' => $downsize[2] > $downsize[1] ? 'portrait' : 'landscape',
				);
			} elseif ( isset( $meta['sizes'][ $size ] ) ) {
				// Nothing from the filter, so consult image metadata if we have it.
				$size_meta = $meta['sizes'][ $size ];

				/*
				 * We have the actual image size, but might need to further constrain it if content_width is narrower.
				 * Thumbnail, medium, and full sizes are also checked against the site's height/width options.
				 */
				list( $width, $height ) = image_constrain_size_for_editor( $size_meta['width'], $size_meta['height'], $size, 'edit' );

				$sizes[ $size ] = array(
					'height'      => $height,
					'width'       => $width,
					'url'         => $base_url . $size_meta['file'],
					'orientation' => $height > $width ? 'portrait' : 'landscape',
				);
			}
		}

		if ( 'image' === $type ) {
			if ( ! empty( $meta['original_image'] ) ) {
				$response['originalImageURL']  = wp_get_original_image_url( $attachment->ID );
				$response['originalImageName'] = wp_basename( wp_get_original_image_path( $attachment->ID ) );
			}

			$sizes['full'] = array( 'url' => $attachment_url );

			if ( isset( $meta['height'], $meta['width'] ) ) {
				$sizes['full']['height']      = $meta['height'];
				$sizes['full']['width']       = $meta['width'];
				$sizes['full']['orientation'] = $meta['height'] > $meta['width'] ? 'portrait' : 'landscape';
			}

			$response = array_merge( $response, $sizes['full'] );
		} elseif ( $meta['sizes']['full']['file'] ) {
			$sizes['full'] = array(
				'url'         => $base_url . $meta['sizes']['full']['file'],
				'height'      => $meta['sizes']['full']['height'],
				'width'       => $meta['sizes']['full']['width'],
				'orientation' => $meta['sizes']['full']['height'] > $meta['sizes']['full']['width'] ? 'portrait' : 'landscape',
			);
		}

		$response = array_merge( $response, array( 'sizes' => $sizes ) );
	}

	if ( $meta && 'video' === $type ) {
		if ( isset( $meta['width'] ) ) {
			$response['width'] = (int) $meta['width'];
		}
		if ( isset( $meta['height'] ) ) {
			$response['height'] = (int) $meta['height'];
		}
	}

	if ( $meta && ( 'audio' === $type || 'video' === $type ) ) {
		if ( isset( $meta['length_formatted'] ) ) {
			$response['fileLength']              = $meta['length_formatted'];
			$response['fileLengthHumanReadable'] = human_readable_duration( $meta['length_formatted'] );
		}

		$response['meta'] = array();
		foreach ( wp_get_attachment_id3_keys( $attachment, 'js' ) as $key => $label ) {
			$response['meta'][ $key ] = false;

			if ( ! empty( $meta[ $key ] ) ) {
				$response['meta'][ $key ] = $meta[ $key ];
			}
		}

		$id = get_post_thumbnail_id( $attachment->ID );
		if ( ! empty( $id ) ) {
			list( $src, $width, $height ) = wp_get_attachment_image_src( $id, 'full' );
			$response['image']            = compact( 'src', 'width', 'height' );
			list( $src, $width, $height ) = wp_get_attachment_image_src( $id, 'thumbnail' );
			$response['thumb']            = compact( 'src', 'width', 'height' );
		} else {
			$src               = wp_mime_type_icon( $attachment->ID, '.svg' );
			$width             = 48;
			$height            = 64;
			$response['image'] = compact( 'src', 'width', 'height' );
			$response['thumb'] = compact( 'src', 'width', 'height' );
		}
	}

	if ( function_exists( 'get_compat_media_markup' ) ) {
		$response['compat'] = get_compat_media_markup( $attachment->ID, array( 'in_modal' => true ) );
	}

	if ( function_exists( 'get_media_states' ) ) {
		$media_states = get_media_states( $attachment );
		if ( ! empty( $media_states ) ) {
			$response['mediaStates'] = implode( ', ', $media_states );
		}
	}

	/**
	 * Filters the attachment data prepared for JavaScript.
	 *
	 * @since 3.5.0
	 *
	 * @param array       $response   Array of prepared attachment data. See {@see wp_prepare_attachment_for_js()}.
	 * @param WP_Post     $attachment Attachment object.
	 * @param array|false $meta       Array of attachment meta data, or false if there is none.
	 */
	return apply_filters( 'wp_prepare_attachment_for_js', $response, $attachment, $meta );
}

/**
 * Enqueues all scripts, styles, settings, and templates necessary to use
 * all media JS APIs.
 *
 * @since 3.5.0
 *
 * @global int       $content_width
 * @global wpdb      $wpdb          WordPress database abstraction object.
 * @global WP_Locale $wp_locale     WordPress date and time locale object.
 *
 * @param array $args {
 *     Arguments for enqueuing media scripts.
 *
 *     @type int|WP_Post $post Post ID or post object.
 * }
 */
function wp_enqueue_media( $args = array() ) {
	// Enqueue me just once per page, please.
	if ( did_action( 'wp_enqueue_media' ) ) {
		return;
	}

	global $content_width, $wpdb, $wp_locale;

	$defaults = array(
		'post' => null,
	);
	$args     = wp_parse_args( $args, $defaults );

	/*
	 * We're going to pass the old thickbox media tabs to `media_upload_tabs`
	 * to ensure plugins will work. We will then unset those tabs.
	 */
	$tabs = array(
		// handler action suffix => tab label
		'type'     => '',
		'type_url' => '',
		'gallery'  => '',
		'library'  => '',
	);

	/** This filter is documented in wp-admin/includes/media.php */
	$tabs = apply_filters( 'media_upload_tabs', $tabs );
	unset( $tabs['type'], $tabs['type_url'], $tabs['gallery'], $tabs['library'] );

	$props = array(
		'link'  => get_option( 'image_default_link_type' ), // DB default is 'file'.
		'align' => get_option( 'image_default_align' ),     // Empty default.
		'size'  => get_option( 'image_default_size' ),      // Empty default.
	);

	$exts      = array_merge( wp_get_audio_extensions(), wp_get_video_extensions() );
	$mimes     = get_allowed_mime_types();
	$ext_mimes = array();
	foreach ( $exts as $ext ) {
		foreach ( $mimes as $ext_preg => $mime_match ) {
			if ( preg_match( '#' . $ext . '#i', $ext_preg ) ) {
				$ext_mimes[ $ext ] = $mime_match;
				break;
			}
		}
	}

	/**
	 * Allows showing or hiding the "Create Audio Playlist" button in the media library.
	 *
	 * By default, the "Create Audio Playlist" button will always be shown in
	 * the media library.  If this filter returns `null`, a query will be run
	 * to determine whether the media library contains any audio items.  This
	 * was the default behavior prior to version 4.8.0, but this query is
	 * expensive for large media libraries.
	 *
	 * @since 4.7.4
	 * @since 4.8.0 The filter's default value is `true` rather than `null`.
	 *
	 * @link https://core.trac.wordpress.org/ticket/31071
	 *
	 * @param bool|null $show Whether to show the button, or `null` to decide based
	 *                        on whether any audio files exist in the media library.
	 */
	$show_audio_playlist = apply_filters( 'media_library_show_audio_playlist', true );
	if ( null === $show_audio_playlist ) {
		$show_audio_playlist = $wpdb->get_var(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'audio%'
			LIMIT 1"
		);
	}

	/**
	 * Allows showing or hiding the "Create Video Playlist" button in the media library.
	 *
	 * By default, the "Create Video Playlist" button will always be shown in
	 * the media library.  If this filter returns `null`, a query will be run
	 * to determine whether the media library contains any video items.  This
	 * was the default behavior prior to version 4.8.0, but this query is
	 * expensive for large media libraries.
	 *
	 * @since 4.7.4
	 * @since 4.8.0 The filter's default value is `true` rather than `null`.
	 *
	 * @link https://core.trac.wordpress.org/ticket/31071
	 *
	 * @param bool|null $show Whether to show the button, or `null` to decide based
	 *                        on whether any video files exist in the media library.
	 */
	$show_video_playlist = apply_filters( 'media_library_show_video_playlist', true );
	if ( null === $show_video_playlist ) {
		$show_video_playlist = $wpdb->get_var(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'video%'
			LIMIT 1"
		);
	}

	/**
	 * Allows overriding the list of months displayed in the media library.
	 *
	 * By default (if this filter does not return an array), a query will be
	 * run to determine the months that have media items.  This query can be
	 * expensive for large media libraries, so it may be desirable for sites to
	 * override this behavior.
	 *
	 * @since 4.7.4
	 *
	 * @link https://core.trac.wordpress.org/ticket/31071
	 *
	 * @param stdClass[]|null $months An array of objects with `month` and `year`
	 *                                properties, or `null` for default behavior.
	 */
	$months = apply_filters( 'media_library_months_with_files', null );
	if ( ! is_array( $months ) ) {
		$months = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
				FROM $wpdb->posts
				WHERE post_type = %s
				ORDER BY post_date DESC",
				'attachment'
			)
		);
	}
	foreach ( $months as $month_year ) {
		$month_year->text = sprintf(
			/* translators: 1: Month, 2: Year. */
			__( '%1$s %2$d' ),
			$wp_locale->get_month( $month_year->month ),
			$month_year->year
		);
	}

	/**
	 * Filters whether the Media Library grid has infinite scrolling. Default `false`.
	 *
	 * @since 5.8.0
	 *
	 * @param bool $infinite Whether the Media Library grid has infinite scrolling.
	 */
	$infinite_scrolling = apply_filters( 'media_library_infinite_scrolling', false );

	$settings = array(
		'tabs'              => $tabs,
		'tabUrl'            => add_query_arg( array( 'chromeless' => true ), admin_url( 'media-upload.php' ) ),
		'mimeTypes'         => wp_list_pluck( get_post_mime_types(), 0 ),
		/** This filter is documented in wp-admin/includes/media.php */
		'captions'          => ! apply_filters( 'disable_captions', '' ),
		'nonce'             => array(
			'sendToEditor'           => wp_create_nonce( 'media-send-to-editor' ),
			'setAttachmentThumbnail' => wp_create_nonce( 'set-attachment-thumbnail' ),
		),
		'post'              => array(
			'id' => 0,
		),
		'defaultProps'      => $props,
		'attachmentCounts'  => array(
			'audio' => ( $show_audio_playlist ) ? 1 : 0,
			'video' => ( $show_video_playlist ) ? 1 : 0,
		),
		'oEmbedProxyUrl'    => rest_url( 'oembed/1.0/proxy' ),
		'embedExts'         => $exts,
		'embedMimes'        => $ext_mimes,
		'contentWidth'      => $content_width,
		'months'            => $months,
		'mediaTrash'        => MEDIA_TRASH ? 1 : 0,
		'infiniteScrolling' => ( $infinite_scrolling ) ? 1 : 0,
	);

	$post = null;
	if ( isset( $args['post'] ) ) {
		$post             = get_post( $args['post'] );
		$settings['post'] = array(
			'id'    => $post->ID,
			'nonce' => wp_create_nonce( 'update-post_' . $post->ID ),
		);

		$thumbnail_support = current_theme_supports( 'post-thumbnails', $post->post_type ) && post_type_supports( $post->post_type, 'thumbnail' );
		if ( ! $thumbnail_support && 'attachment' === $post->post_type && $post->post_mime_type ) {
			if ( wp_attachment_is( 'audio', $post ) ) {
				$thumbnail_support = post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
			} elseif ( wp_attachment_is( 'video', $post ) ) {
				$thumbnail_support = post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
			}
		}

		if ( $thumbnail_support ) {
			$featured_image_id                   = get_post_meta( $post->ID, '_thumbnail_id', true );
			$settings['post']['featuredImageId'] = $featured_image_id ? $featured_image_id : -1;
		}
	}

	if ( $post ) {
		$post_type_object = get_post_type_object( $post->post_type );
	} else {
		$post_type_object = get_post_type_object( 'post' );
	}

	$strings = array(
		// Generic.
		'mediaFrameDefaultTitle'      => __( 'Media' ),
		'url'                         => __( 'URL' ),
		'addMedia'                    => __( 'Add media' ),
		'search'                      => __( 'Search' ),
		'select'                      => __( 'Select' ),
		'cancel'                      => __( 'Cancel' ),
		'update'                      => __( 'Update' ),
		'replace'                     => __( 'Replace' ),
		'remove'                      => __( 'Remove' ),
		'back'                        => __( 'Back' ),
		/*
		 * translators: This is a would-be plural string used in the media manager.
		 * If there is not a word you can use in your language to avoid issues with the
		 * lack of plural support here, turn it into "selected: %d" then translate it.
		 */
		'selected'                    => __( '%d selected' ),
		'dragInfo'                    => __( 'Drag and drop to reorder media files.' ),

		// Upload.
		'uploadFilesTitle'            => __( 'Upload files' ),
		'uploadImagesTitle'           => __( 'Upload images' ),

		// Library.
		'mediaLibraryTitle'           => __( 'Media Library' ),
		'insertMediaTitle'            => __( 'Add media' ),
		'createNewGallery'            => __( 'Create a new gallery' ),
		'createNewPlaylist'           => __( 'Create a new playlist' ),
		'createNewVideoPlaylist'      => __( 'Create a new video playlist' ),
		'returnToLibrary'             => __( '&#8592; Go to library' ),
		'allMediaItems'               => __( 'All media items' ),
		'allDates'                    => __( 'All dates' ),
		'noItemsFound'                => __( 'No items found.' ),
		'insertIntoPost'              => $post_type_object->labels->insert_into_item,
		'unattached'                  => _x( 'Unattached', 'media items' ),
		'mine'                        => _x( 'Mine', 'media items' ),
		'trash'                       => _x( 'Trash', 'noun' ),
		'uploadedToThisPost'          => $post_type_object->labels->uploaded_to_this_item,
		'warnDelete'                  => __( "You are about to permanently delete this item from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete." ),
		'warnBulkDelete'              => __( "You are about to permanently delete these items from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete." ),
		'warnBulkTrash'               => __( "You are about to trash these items.\n  'Cancel' to stop, 'OK' to delete." ),
		'bulkSelect'                  => __( 'Bulk select' ),
		'trashSelected'               => __( 'Move to Trash' ),
		'restoreSelected'             => __( 'Restore from Trash' ),
		'deletePermanently'           => __( 'Delete permanently' ),
		'errorDeleting'               => __( 'Error in deleting the attachment.' ),
		'apply'                       => __( 'Apply' ),
		'filterByDate'                => __( 'Filter by date' ),
		'filterByType'                => __( 'Filter by type' ),
		'searchLabel'                 => __( 'Search media' ),
		'searchMediaLabel'            => __( 'Search media' ),          // Backward compatibility pre-5.3.
		'searchMediaPlaceholder'      => __( 'Search media items...' ), // Placeholder (no ellipsis), backward compatibility pre-5.3.
		/* translators: %d: Number of attachments found in a search. */
		'mediaFound'                  => __( 'Number of media items found: %d' ),
		'noMedia'                     => __( 'No media items found.' ),
		'noMediaTryNewSearch'         => __( 'No media items found. Try a different search.' ),

		// Library Details.
		'attachmentDetails'           => __( 'Attachment details' ),

		// From URL.
		'insertFromUrlTitle'          => __( 'Insert from URL' ),

		// Featured Images.
		'setFeaturedImageTitle'       => $post_type_object->labels->featured_image,
		'setFeaturedImage'            => $post_type_object->labels->set_featured_image,

		// Gallery.
		'createGalleryTitle'          => __( 'Create gallery' ),
		'editGalleryTitle'            => __( 'Edit gallery' ),
		'cancelGalleryTitle'          => __( '&#8592; Cancel gallery' ),
		'insertGallery'               => __( 'Insert gallery' ),
		'updateGallery'               => __( 'Update gallery' ),
		'addToGallery'                => __( 'Add to gallery' ),
		'addToGalleryTitle'           => __( 'Add to gallery' ),
		'reverseOrder'                => __( 'Reverse order' ),

		// Edit Image.
		'imageDetailsTitle'           => __( 'Image details' ),
		'imageReplaceTitle'           => __( 'Replace image' ),
		'imageDetailsCancel'          => __( 'Cancel edit' ),
		'editImage'                   => __( 'Edit image' ),

		// Crop Image.
		'chooseImage'                 => __( 'Choose image' ),
		'selectAndCrop'               => __( 'Select and crop' ),
		'skipCropping'                => __( 'Skip cropping' ),
		'cropImage'                   => __( 'Crop image' ),
		'cropYourImage'               => __( 'Crop your image' ),
		'cropping'                    => __( 'Cropping&hellip;' ),
		/* translators: 1: Suggested width number, 2: Suggested height number. */
		'suggestedDimensions'         => __( 'Suggested image dimensions: %1$s by %2$s pixels.' ),
		'cropError'                   => __( 'There has been an error cropping your image.' ),

		// Edit Audio.
		'audioDetailsTitle'           => __( 'Audio details' ),
		'audioReplaceTitle'           => __( 'Replace audio' ),
		'audioAddSourceTitle'         => __( 'Add audio source' ),
		'audioDetailsCancel'          => __( 'Cancel edit' ),

		// Edit Video.
		'videoDetailsTitle'           => __( 'Video details' ),
		'videoReplaceTitle'           => __( 'Replace video' ),
		'videoAddSourceTitle'         => __( 'Add video source' ),
		'videoDetailsCancel'          => __( 'Cancel edit' ),
		'videoSelectPosterImageTitle' => __( 'Select poster image' ),
		'videoAddTrackTitle'          => __( 'Add subtitles' ),

		// Playlist.
		'playlistDragInfo'            => __( 'Drag and drop to reorder tracks.' ),
		'createPlaylistTitle'         => __( 'Create audio playlist' ),
		'editPlaylistTitle'           => __( 'Edit audio playlist' ),
		'cancelPlaylistTitle'         => __( '&#8592; Cancel audio playlist' ),
		'insertPlaylist'              => __( 'Insert audio playlist' ),
		'updatePlaylist'              => __( 'Update audio playlist' ),
		'addToPlaylist'               => __( 'Add to audio playlist' ),
		'addToPlaylistTitle'          => __( 'Add to Audio Playlist' ),

		// Video Playlist.
		'videoPlaylistDragInfo'       => __( 'Drag and drop to reorder videos.' ),
		'createVideoPlaylistTitle'    => __( 'Create video playlist' ),
		'editVideoPlaylistTitle'      => __( 'Edit video playlist' ),
		'cancelVideoPlaylistTitle'    => __( '&#8592; Cancel video playlist' ),
		'insertVideoPlaylist'         => __( 'Insert video playlist' ),
		'updateVideoPlaylist'         => __( 'Update video playlist' ),
		'addToVideoPlaylist'          => __( 'Add to video playlist' ),
		'addToVideoPlaylistTitle'     => __( 'Add to video Playlist' ),

		// Headings.
		'filterAttachments'           => __( 'Filter media' ),
		'attachmentsList'             => __( 'Media list' ),
	);

	/**
	 * Filters the media view settings.
	 *
	 * @since 3.5.0
	 *
	 * @param array   $settings List of media view settings.
	 * @param WP_Post $post     Post object.
	 */
	$settings = apply_filters( 'media_view_settings', $settings, $post );

	/**
	 * Filters the media view strings.
	 *
	 * @since 3.5.0
	 *
	 * @param string[] $strings Array of media view strings keyed by the name they'll be referenced by in JavaScript.
	 * @param WP_Post  $post    Post object.
	 */
	$strings = apply_filters( 'media_view_strings', $strings, $post );

	$strings['settings'] = $settings;

	/*
	 * Ensure we enqueue media-editor first, that way media-views
	 * is registered internally before we try to localize it. See #24724.
	 */
	wp_enqueue_script( 'media-editor' );
	wp_localize_script( 'media-views', '_wpMediaViewsL10n', $strings );

	wp_enqueue_script( 'media-audiovideo' );
	wp_enqueue_style( 'media-views' );
	if ( is_admin() ) {
		wp_enqueue_script( 'mce-view' );
		wp_enqueue_script( 'image-edit' );
	}
	wp_enqueue_style( 'imgareaselect' );
	wp_plupload_default_settings();

	require_once ABSPATH . WPINC . '/media-template.php';
	add_action( 'admin_footer', 'wp_print_media_templates' );
	add_action( 'wp_footer', 'wp_print_media_templates' );
	add_action( 'customize_controls_print_footer_scripts', 'wp_print_media_templates' );

	/**
	 * Fires at the conclusion of wp_enqueue_media().
	 *
	 * @since 3.5.0
	 */
	do_action( 'wp_enqueue_media' );
}

/**
 * Retrieves media attached to the passed post.
 *
 * @since 3.6.0
 *
 * @param string      $type Mime type.
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return WP_Post[] Array of media attached to the given post.
 */
function get_attached_media( $type, $post = 0 ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	$args = array(
		'post_parent'    => $post->ID,
		'post_type'      => 'attachment',
		'post_mime_type' => $type,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);

	/**
	 * Filters arguments used to retrieve media attached to the given post.
	 *
	 * @since 3.6.0
	 *
	 * @param array   $args Post query arguments.
	 * @param string  $type Mime type of the desired media.
	 * @param WP_Post $post Post object.
	 */
	$args = apply_filters( 'get_attached_media_args', $args, $type, $post );

	$children = get_children( $args );

	/**
	 * Filters the list of media attached to the given post.
	 *
	 * @since 3.6.0
	 *
	 * @param WP_Post[] $children Array of media attached to the given post.
	 * @param string    $type     Mime type of the media desired.
	 * @param WP_Post   $post     Post object.
	 */
	return (array) apply_filters( 'get_attached_media', $children, $type, $post );
}

/**
 * Checks the HTML content for an audio, video, object, embed, or iframe tags.
 *
 * @since 3.6.0
 *
 * @param string   $content A string of HTML which might contain media elements.
 * @param string[] $types   An array of media types: 'audio', 'video', 'object', 'embed', or 'iframe'.
 * @return string[] Array of found HTML media elements.
 */
function get_media_embedded_in_content( $content, $types = null ) {
	$html = array();

	/**
	 * Filters the embedded media types that are allowed to be returned from the content blob.
	 *
	 * @since 4.2.0
	 *
	 * @param string[] $allowed_media_types An array of allowed media types. Default media types are
	 *                                      'audio', 'video', 'object', 'embed', and 'iframe'.
	 */
	$allowed_media_types = apply_filters( 'media_embedded_in_content_allowed_types', array( 'audio', 'video', 'object', 'embed', 'iframe' ) );

	if ( ! empty( $types ) ) {
		if ( ! is_array( $types ) ) {
			$types = array( $types );
		}

		$allowed_media_types = array_intersect( $allowed_media_types, $types );
	}

	$tags = implode( '|', $allowed_media_types );

	if ( preg_match_all( '#<(?P<tag>' . $tags . ')[^<]*?(?:>[\s\S]*?<\/(?P=tag)>|\s*\/>)#', $content, $matches ) ) {
		foreach ( $matches[0] as $match ) {
			$html[] = $match;
		}
	}

	return $html;
}

/**
 * Retrieves galleries from the passed post's content.
 *
 * @since 3.6.0
 *
 * @param int|WP_Post $post Post ID or object.
 * @param bool        $html Optional. Whether to return HTML or data in the array. Default true.
 * @return array A list of arrays, each containing gallery data and srcs parsed
 *               from the expanded shortcode.
 */
function get_post_galleries( $post, $html = true ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	if ( ! has_shortcode( $post->post_content, 'gallery' ) && ! has_block( 'gallery', $post->post_content ) ) {
		return array();
	}

	$galleries = array();
	if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $shortcode ) {
			if ( 'gallery' === $shortcode[2] ) {
				$srcs = array();

				$shortcode_attrs = shortcode_parse_atts( $shortcode[3] );

				// Specify the post ID of the gallery we're viewing if the shortcode doesn't reference another post already.
				if ( ! isset( $shortcode_attrs['id'] ) ) {
					$shortcode[3] .= ' id="' . (int) $post->ID . '"';
				}

				$gallery = do_shortcode_tag( $shortcode );
				if ( $html ) {
					$galleries[] = $gallery;
				} else {
					preg_match_all( '#src=([\'"])(.+?)\1#is', $gallery, $src, PREG_SET_ORDER );
					if ( ! empty( $src ) ) {
						foreach ( $src as $s ) {
							$srcs[] = $s[2];
						}
					}

					$galleries[] = array_merge(
						$shortcode_attrs,
						array(
							'src' => array_values( array_unique( $srcs ) ),
						)
					);
				}
			}
		}
	}

	if ( has_block( 'gallery', $post->post_content ) ) {
		$post_blocks = parse_blocks( $post->post_content );

		while ( $block = array_shift( $post_blocks ) ) {
			$has_inner_blocks = ! empty( $block['innerBlocks'] );

			// Skip blocks with no blockName and no innerHTML.
			if ( ! $block['blockName'] ) {
				continue;
			}

			// Skip non-Gallery blocks.
			if ( 'core/gallery' !== $block['blockName'] ) {
				// Move inner blocks into the root array before skipping.
				if ( $has_inner_blocks ) {
					array_push( $post_blocks, ...$block['innerBlocks'] );
				}
				continue;
			}

			// New Gallery block format as HTML.
			if ( $has_inner_blocks && $html ) {
				$block_html  = wp_list_pluck( $block['innerBlocks'], 'innerHTML' );
				$galleries[] = '<figure>' . implode( ' ', $block_html ) . '</figure>';
				continue;
			}

			$srcs = array();

			// New Gallery block format as an array.
			if ( $has_inner_blocks ) {
				$attrs = wp_list_pluck( $block['innerBlocks'], 'attrs' );
				$ids   = wp_list_pluck( $attrs, 'id' );

				foreach ( $ids as $id ) {
					$url = wp_get_attachment_url( $id );

					if ( is_string( $url ) && ! in_array( $url, $srcs, true ) ) {
						$srcs[] = $url;
					}
				}

				$galleries[] = array(
					'ids' => implode( ',', $ids ),
					'src' => $srcs,
				);

				continue;
			}

			// Old Gallery block format as HTML.
			if ( $html ) {
				$galleries[] = $block['innerHTML'];
				continue;
			}

			// Old Gallery block format as an array.
			$ids = ! empty( $block['attrs']['ids'] ) ? $block['attrs']['ids'] : array();

			// If present, use the image IDs from the JSON blob as canonical.
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					$url = wp_get_attachment_url( $id );

					if ( is_string( $url ) && ! in_array( $url, $srcs, true ) ) {
						$srcs[] = $url;
					}
				}

				$galleries[] = array(
					'ids' => implode( ',', $ids ),
					'src' => $srcs,
				);

				continue;
			}

			// Otherwise, extract srcs from the innerHTML.
			preg_match_all( '#src=([\'"])(.+?)\1#is', $block['innerHTML'], $found_srcs, PREG_SET_ORDER );

			if ( ! empty( $found_srcs[0] ) ) {
				foreach ( $found_srcs as $src ) {
					if ( isset( $src[2] ) && ! in_array( $src[2], $srcs, true ) ) {
						$srcs[] = $src[2];
					}
				}
			}

			$galleries[] = array( 'src' => $srcs );
		}
	}

	/**
	 * Filters the list of all found galleries in the given post.
	 *
	 * @since 3.6.0
	 *
	 * @param array   $galleries Associative array of all found post galleries.
	 * @param WP_Post $post      Post object.
	 */
	return apply_filters( 'get_post_galleries', $galleries, $post );
}

/**
 * Checks a specified post's content for gallery and, if present, return the first
 *
 * @since 3.6.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param bool        $html Optional. Whether to return HTML or data. Default is true.
 * @return string|array Gallery data and srcs parsed from the expanded shortcode.
 */
function get_post_gallery( $post = 0, $html = true ) {
	$galleries = get_post_galleries( $post, $html );
	$gallery   = reset( $galleries );

	/**
	 * Filters the first-found post gallery.
	 *
	 * @since 3.6.0
	 *
	 * @param array       $gallery   The first-found post gallery.
	 * @param int|WP_Post $post      Post ID or object.
	 * @param array       $galleries Associative array of all found post galleries.
	 */
	return apply_filters( 'get_post_gallery', $gallery, $post, $galleries );
}

/**
 * Retrieves the image srcs from galleries from a post's content, if present.
 *
 * @since 3.6.0
 *
 * @see get_post_galleries()
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
 * @return array A list of lists, each containing image srcs parsed.
 *               from an expanded shortcode
 */
function get_post_galleries_images( $post = 0 ) {
	$galleries = get_post_galleries( $post, false );
	return wp_list_pluck( $galleries, 'src' );
}

/**
 * Checks a post's content for galleries and return the image srcs for the first found gallery.
 *
 * @since 3.6.0
 *
 * @see get_post_gallery()
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
 * @return string[] A list of a gallery's image srcs in order.
 */
function get_post_gallery_images( $post = 0 ) {
	$gallery = get_post_gallery( $post, false );
	return empty( $gallery['src'] ) ? array() : $gallery['src'];
}

/**
 * Maybe attempts to generate attachment metadata, if missing.
 *
 * @since 3.9.0
 *
 * @param WP_Post $attachment Attachment object.
 */
function wp_maybe_generate_attachment_metadata( $attachment ) {
	if ( empty( $attachment ) || empty( $attachment->ID ) ) {
		return;
	}

	$attachment_id = (int) $attachment->ID;
	$file          = get_attached_file( $attachment_id );
	$meta          = wp_get_attachment_metadata( $attachment_id );

	if ( empty( $meta ) && file_exists( $file ) ) {
		$_meta = get_post_meta( $attachment_id );
		$_lock = 'wp_generating_att_' . $attachment_id;

		if ( ! array_key_exists( '_wp_attachment_metadata', $_meta ) && ! get_transient( $_lock ) ) {
			set_transient( $_lock, $file );
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );
			delete_transient( $_lock );
		}
	}
}

/**
 * Tries to convert an attachment URL into a post ID.
 *
 * @since 4.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $url The URL to resolve.
 * @return int The found post ID, or 0 on failure.
 */
function attachment_url_to_postid( $url ) {
	global $wpdb;

	/**
	 * Filters the attachment ID to allow short-circuit the function.
	 *
	 * Allows plugins to short-circuit attachment ID lookups. Plugins making
	 * use of this function should return:
	 *
	 * - 0 (integer) to indicate the attachment is not found,
	 * - attachment ID (integer) to indicate the attachment ID found,
	 * - null to indicate WordPress should proceed with the lookup.
	 *
	 * Warning: The post ID may be null or zero, both of which cast to a
	 * boolean false. For information about casting to booleans see the
	 * {@link https://www.php.net/manual/en/language.types.boolean.php PHP documentation}.
	 * Use the === operator for testing the post ID when developing filters using
	 * this hook.
	 *
	 * @since 6.7.0
	 *
	 * @param int|null $post_id The result of the post ID lookup. Null to indicate
	 *                          no lookup has been attempted. Default null.
	 * @param string   $url     The URL being looked up.
	 */
	$post_id = apply_filters( 'pre_attachment_url_to_postid', null, $url );
	if ( null !== $post_id ) {
		return (int) $post_id;
	}

	$dir  = wp_get_upload_dir();
	$path = $url;

	$site_url   = parse_url( $dir['url'] );
	$image_path = parse_url( $path );

	// Force the protocols to match if needed.
	if ( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
		$path = str_replace( $image_path['scheme'], $site_url['scheme'], $path );
	}

	if ( str_starts_with( $path, $dir['baseurl'] . '/' ) ) {
		$path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
	}

	$sql = $wpdb->prepare(
		"SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
		$path
	);

	$results = $wpdb->get_results( $sql );
	$post_id = null;

	if ( $results ) {
		// Use the first available result, but prefer a case-sensitive match, if exists.
		$post_id = reset( $results )->post_id;

		if ( count( $results ) > 1 ) {
			foreach ( $results as $result ) {
				if ( $path === $result->meta_value ) {
					$post_id = $result->post_id;
					break;
				}
			}
		}
	}

	/**
	 * Filters an attachment ID found by URL.
	 *
	 * @since 4.2.0
	 *
	 * @param int|null $post_id The post_id (if any) found by the function.
	 * @param string   $url     The URL being looked up.
	 */
	return (int) apply_filters( 'attachment_url_to_postid', $post_id, $url );
}

/**
 * Returns the URLs for CSS files used in an iframe-sandbox'd TinyMCE media view.
 *
 * @since 4.0.0
 *
 * @return string[] The relevant CSS file URLs.
 */
function wpview_media_sandbox_styles() {
	$version        = 'ver=' . get_bloginfo( 'version' );
	$mediaelement   = includes_url( "js/mediaelement/mediaelementplayer-legacy.min.css?$version" );
	$wpmediaelement = includes_url( "js/mediaelement/wp-mediaelement.css?$version" );

	return array( $mediaelement, $wpmediaelement );
}

/**
 * Registers the personal data exporter for media.
 *
 * @param array[] $exporters An array of personal data exporters, keyed by their ID.
 * @return array[] Updated array of personal data exporters.
 */
function wp_register_media_personal_data_exporter( $exporters ) {
	$exporters['wordpress-media'] = array(
		'exporter_friendly_name' => __( 'WordPress Media' ),
		'callback'               => 'wp_media_personal_data_exporter',
	);

	return $exporters;
}

/**
 * Finds and exports attachments associated with an email address.
 *
 * @since 4.9.6
 *
 * @param string $email_address The attachment owner email address.
 * @param int    $page          Attachment page number.
 * @return array {
 *     An array of personal data.
 *
 *     @type array[] $data An array of personal data arrays.
 *     @type bool    $done Whether the exporter is finished.
 * }
 */
function wp_media_personal_data_exporter( $email_address, $page = 1 ) {
	// Limit us to 50 attachments at a time to avoid timing out.
	$number = 50;
	$page   = (int) $page;

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );
	if ( false === $user ) {
		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	$post_query = new WP_Query(
		array(
			'author'         => $user->ID,
			'posts_per_page' => $number,
			'paged'          => $page,
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	foreach ( (array) $post_query->posts as $post ) {
		$attachment_url = wp_get_attachment_url( $post->ID );

		if ( $attachment_url ) {
			$post_data_to_export = array(
				array(
					'name'  => __( 'URL' ),
					'value' => $attachment_url,
				),
			);

			$data_to_export[] = array(
				'group_id'          => 'media',
				'group_label'       => __( 'Media' ),
				'group_description' => __( 'User&#8217;s media data.' ),
				'item_id'           => "post-{$post->ID}",
				'data'              => $post_data_to_export,
			);
		}
	}

	$done = $post_query->max_num_pages <= $page;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Adds additional default image sub-sizes.
 *
 * These sizes are meant to enhance the way WordPress displays images on the front-end on larger,
 * high-density devices. They make it possible to generate more suitable `srcset` and `sizes` attributes
 * when the users upload large images.
 *
 * The sizes can be changed or removed by themes and plugins but that is not recommended.
 * The size "names" reflect the image dimensions, so changing the sizes would be quite misleading.
 *
 * @since 5.3.0
 * @access private
 */
function _wp_add_additional_image_sizes() {
	// 2x medium_large size.
	add_image_size( '1536x1536', 1536, 1536 );
	// 2x large size.
	add_image_size( '2048x2048', 2048, 2048 );
}

/**
 * Callback to enable showing of the user error when uploading .heic images.
 *
 * @since 5.5.0
 * @since 6.7.0 The default behavior is to enable heic uploads as long as the server
 *              supports the format. The uploads are converted to JPEG's by default.
 *
 * @param array[] $plupload_settings The settings for Plupload.js.
 * @return array[] Modified settings for Plupload.js.
 */
function wp_show_heic_upload_error( $plupload_settings ) {
	// Check if HEIC images can be edited.
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/heic' ) ) ) {
		$plupload_init['heic_upload_error'] = true;
	}
	return $plupload_settings;
}

/**
 * Allows PHP's getimagesize() to be debuggable when necessary.
 *
 * @since 5.7.0
 * @since 5.8.0 Added support for WebP images.
 * @since 6.5.0 Added support for AVIF images.
 *
 * @param string $filename   The file path.
 * @param array  $image_info Optional. Extended image information (passed by reference).
 * @return array|false Array of image information or false on failure.
 */
function wp_getimagesize( $filename, ?array &$image_info = null ) {
	// Don't silence errors when in debug mode, unless running unit tests.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! defined( 'WP_RUN_CORE_TESTS' ) ) {
		if ( 2 === func_num_args() ) {
			$info = getimagesize( $filename, $image_info );
		} else {
			$info = getimagesize( $filename );
		}
	} else {
		/*
		 * Silencing notice and warning is intentional.
		 *
		 * getimagesize() has a tendency to generate errors, such as
		 * "corrupt JPEG data: 7191 extraneous bytes before marker",
		 * even when it's able to provide image size information.
		 *
		 * See https://core.trac.wordpress.org/ticket/42480
		 */
		if ( 2 === func_num_args() ) {
			$info = @getimagesize( $filename, $image_info );
		} else {
			$info = @getimagesize( $filename );
		}
	}

	if (
		! empty( $info ) &&
		// Some PHP versions return 0x0 sizes from `getimagesize` for unrecognized image formats, including AVIFs.
		! ( empty( $info[0] ) && empty( $info[1] ) )
	) {
		return $info;
	}

	$image_mime_type = wp_get_image_mime( $filename );

	// Not an image?
	if ( false === $image_mime_type ) {
		return false;
	}

	/*
	 * For PHP versions that don't support WebP images,
	 * extract the image size info from the file headers.
	 */
	if ( 'image/webp' === $image_mime_type ) {
		$webp_info = wp_get_webp_info( $filename );
		$width     = $webp_info['width'];
		$height    = $webp_info['height'];

		// Mimic the native return format.
		if ( $width && $height ) {
			return array(
				$width,
				$height,
				IMAGETYPE_WEBP,
				sprintf(
					'width="%d" height="%d"',
					$width,
					$height
				),
				'mime' => 'image/webp',
			);
		}
	}

	// For PHP versions that don't support AVIF images, extract the image size info from the file headers.
	if ( 'image/avif' === $image_mime_type ) {
		$avif_info = wp_get_avif_info( $filename );

		$width  = $avif_info['width'];
		$height = $avif_info['height'];

		// Mimic the native return format.
		if ( $width && $height ) {
			return array(
				$width,
				$height,
				IMAGETYPE_AVIF,
				sprintf(
					'width="%d" height="%d"',
					$width,
					$height
				),
				'mime' => 'image/avif',
			);
		}
	}

	// For PHP versions that don't support HEIC images, extract the size info using Imagick when available.
	if ( wp_is_heic_image_mime_type( $image_mime_type ) ) {
		$editor = wp_get_image_editor( $filename );

		if ( is_wp_error( $editor ) ) {
			return false;
		}

		// If the editor for HEICs is Imagick, use it to get the image size.
		if ( $editor instanceof WP_Image_Editor_Imagick ) {
			$size = $editor->get_size();
			return array(
				$size['width'],
				$size['height'],
				IMAGETYPE_HEIC,
				sprintf(
					'width="%d" height="%d"',
					$size['width'],
					$size['height']
				),
				'mime' => 'image/heic',
			);
		}
	}

	// The image could not be parsed.
	return false;
}

/**
 * Extracts meta information about an AVIF file: width, height, bit depth, and number of channels.
 *
 * @since 6.5.0
 *
 * @param string $filename Path to an AVIF file.
 * @return array {
 *     An array of AVIF image information.
 *
 *     @type int|false $width        Image width on success, false on failure.
 *     @type int|false $height       Image height on success, false on failure.
 *     @type int|false $bit_depth    Image bit depth on success, false on failure.
 *     @type int|false $num_channels Image number of channels on success, false on failure.
 * }
 */
function wp_get_avif_info( $filename ) {
	$results = array(
		'width'        => false,
		'height'       => false,
		'bit_depth'    => false,
		'num_channels' => false,
	);

	if ( 'image/avif' !== wp_get_image_mime( $filename ) ) {
		return $results;
	}

	// Parse the file using libavifinfo's PHP implementation.
	require_once ABSPATH . WPINC . '/class-avif-info.php';

	$handle = fopen( $filename, 'rb' );
	if ( $handle ) {
		$parser  = new Avifinfo\Parser( $handle );
		$success = $parser->parse_ftyp() && $parser->parse_file();
		fclose( $handle );
		if ( $success ) {
			$results = $parser->features->primary_item_features;
		}
	}
	return $results;
}

/**
 * Extracts meta information about a WebP file: width, height, and type.
 *
 * @since 5.8.0
 *
 * @param string $filename Path to a WebP file.
 * @return array {
 *     An array of WebP image information.
 *
 *     @type int|false    $width  Image width on success, false on failure.
 *     @type int|false    $height Image height on success, false on failure.
 *     @type string|false $type   The WebP type: one of 'lossy', 'lossless' or 'animated-alpha'.
 *                                False on failure.
 * }
 */
function wp_get_webp_info( $filename ) {
	$width  = false;
	$height = false;
	$type   = false;

	if ( 'image/webp' !== wp_get_image_mime( $filename ) ) {
		return compact( 'width', 'height', 'type' );
	}

	$magic = file_get_contents( $filename, false, null, 0, 40 );

	if ( false === $magic ) {
		return compact( 'width', 'height', 'type' );
	}

	// Make sure we got enough bytes.
	if ( strlen( $magic ) < 40 ) {
		return compact( 'width', 'height', 'type' );
	}

	/*
	 * The headers are a little different for each of the three formats.
	 * Header values based on WebP docs, see https://developers.google.com/speed/webp/docs/riff_container.
	 */
	switch ( substr( $magic, 12, 4 ) ) {
		// Lossy WebP.
		case 'VP8 ':
			$parts  = unpack( 'v2', substr( $magic, 26, 4 ) );
			$width  = (int) ( $parts[1] & 0x3FFF );
			$height = (int) ( $parts[2] & 0x3FFF );
			$type   = 'lossy';
			break;
		// Lossless WebP.
		case 'VP8L':
			$parts  = unpack( 'C4', substr( $magic, 21, 4 ) );
			$width  = (int) ( $parts[1] | ( ( $parts[2] & 0x3F ) << 8 ) ) + 1;
			$height = (int) ( ( ( $parts[2] & 0xC0 ) >> 6 ) | ( $parts[3] << 2 ) | ( ( $parts[4] & 0x03 ) << 10 ) ) + 1;
			$type   = 'lossless';
			break;
		// Animated/alpha WebP.
		case 'VP8X':
			// Pad 24-bit int.
			$width = unpack( 'V', substr( $magic, 24, 3 ) . "\x00" );
			$width = (int) ( $width[1] & 0xFFFFFF ) + 1;
			// Pad 24-bit int.
			$height = unpack( 'V', substr( $magic, 27, 3 ) . "\x00" );
			$height = (int) ( $height[1] & 0xFFFFFF ) + 1;
			$type   = 'animated-alpha';
			break;
	}

	return compact( 'width', 'height', 'type' );
}

/**
 * Gets loading optimization attributes.
 *
 * This function returns an array of attributes that should be merged into the given attributes array to optimize
 * loading performance. Potential attributes returned by this function are:
 * - `loading` attribute with a value of "lazy"
 * - `fetchpriority` attribute with a value of "high"
 * - `decoding` attribute with a value of "async"
 *
 * If any of these attributes are already present in the given attributes, they will not be modified. Note that no
 * element should have both `loading="lazy"` and `fetchpriority="high"`, so the function will trigger a warning in case
 * both attributes are present with those values.
 *
 * @since 6.3.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string $tag_name The tag name.
 * @param array  $attr     Array of the attributes for the tag.
 * @param string $context  Context for the element for which the loading optimization attribute is requested.
 * @return array Loading optimization attributes.
 */
function wp_get_loading_optimization_attributes( $tag_name, $attr, $context ) {
	global $wp_query;

	/**
	 * Filters whether to short-circuit loading optimization attributes.
	 *
	 * Returning an array from the filter will effectively short-circuit the loading of optimization attributes,
	 * returning that value instead.
	 *
	 * @since 6.4.0
	 *
	 * @param array|false $loading_attrs False by default, or array of loading optimization attributes to short-circuit.
	 * @param string      $tag_name      The tag name.
	 * @param array       $attr          Array of the attributes for the tag.
	 * @param string      $context       Context for the element for which the loading optimization attribute is requested.
	 */
	$loading_attrs = apply_filters( 'pre_wp_get_loading_optimization_attributes', false, $tag_name, $attr, $context );

	if ( is_array( $loading_attrs ) ) {
		return $loading_attrs;
	}

	$loading_attrs = array();

	/*
	 * Skip lazy-loading for the overall block template, as it is handled more granularly.
	 * The skip is also applicable for `fetchpriority`.
	 */
	if ( 'template' === $context ) {
		/** This filter is documented in wp-includes/media.php */
		return apply_filters( 'wp_get_loading_optimization_attributes', $loading_attrs, $tag_name, $attr, $context );
	}

	// For now this function only supports images and iframes.
	if ( 'img' !== $tag_name && 'iframe' !== $tag_name ) {
		/** This filter is documented in wp-includes/media.php */
		return apply_filters( 'wp_get_loading_optimization_attributes', $loading_attrs, $tag_name, $attr, $context );
	}

	/*
	 * Skip programmatically created images within content blobs as they need to be handled together with the other
	 * images within the post content or widget content.
	 * Without this clause, they would already be considered within their own context which skews the image count and
	 * can result in the first post content image being lazy-loaded or an image further down the page being marked as a
	 * high priority.
	 */
	if (
		'the_content' !== $context && doing_filter( 'the_content' ) ||
		'widget_text_content' !== $context && doing_filter( 'widget_text_content' ) ||
		'widget_block_content' !== $context && doing_filter( 'widget_block_content' )
	) {
		/** This filter is documented in wp-includes/media.php */
		return apply_filters( 'wp_get_loading_optimization_attributes', $loading_attrs, $tag_name, $attr, $context );

	}

	/*
	 * Add `decoding` with a value of "async" for every image unless it has a
	 * conflicting `decoding` attribute already present.
	 */
	if ( 'img' === $tag_name ) {
		if ( isset( $attr['decoding'] ) ) {
			$loading_attrs['decoding'] = $attr['decoding'];
		} else {
			$loading_attrs['decoding'] = 'async';
		}
	}

	// For any resources, width and height must be provided, to avoid layout shifts.
	if ( ! isset( $attr['width'], $attr['height'] ) ) {
		/** This filter is documented in wp-includes/media.php */
		return apply_filters( 'wp_get_loading_optimization_attributes', $loading_attrs, $tag_name, $attr, $context );
	}

	/*
	 * The key function logic starts here.
	 */
	$maybe_in_viewport    = null;
	$increase_count       = false;
	$maybe_increase_count = false;

	// Logic to handle a `loading` attribute that is already provided.
	if ( isset( $attr['loading'] ) ) {
		/*
		 * Interpret "lazy" as not in viewport. Any other value can be
		 * interpreted as in viewport (realistically only "eager" or `false`
		 * to force-omit the attribute are other potential values).
		 */
		if ( 'lazy' === $attr['loading'] ) {
			$maybe_in_viewport = false;
		} else {
			$maybe_in_viewport = true;
		}
	}

	// Logic to handle a `fetchpriority` attribute that is already provided.
	if ( isset( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
		/*
		 * If the image was already determined to not be in the viewport (e.g.
		 * from an already provided `loading` attribute), trigger a warning.
		 * Otherwise, the value can be interpreted as in viewport, since only
		 * the most important in-viewport image should have `fetchpriority` set
		 * to "high".
		 */
		if ( false === $maybe_in_viewport ) {
			_doing_it_wrong(
				__FUNCTION__,
				__( 'An image should not be lazy-loaded and marked as high priority at the same time.' ),
				'6.3.0'
			);
			/*
			 * Set `fetchpriority` here for backward-compatibility as we should
			 * not override what a developer decided, even though it seems
			 * incorrect.
			 */
			$loading_attrs['fetchpriority'] = 'high';
		} else {
			$maybe_in_viewport = true;
		}
	}

	if ( null === $maybe_in_viewport ) {
		$header_enforced_contexts = array(
			'template_part_' . WP_TEMPLATE_PART_AREA_HEADER => true,
			'get_header_image_tag' => true,
		);

		/**
		 * Filters the header-specific contexts.
		 *
		 * @since 6.4.0
		 *
		 * @param array $default_header_enforced_contexts Map of contexts for which elements should be considered
		 *                                                in the header of the page, as $context => $enabled
		 *                                                pairs. The $enabled should always be true.
		 */
		$header_enforced_contexts = apply_filters( 'wp_loading_optimization_force_header_contexts', $header_enforced_contexts );

		// Consider elements with these header-specific contexts to be in viewport.
		if ( isset( $header_enforced_contexts[ $context ] ) ) {
			$maybe_in_viewport    = true;
			$maybe_increase_count = true;
		} elseif ( ! is_admin() && in_the_loop() && is_main_query() ) {
			/*
			 * Get the content media count, since this is a main query
			 * content element. This is accomplished by "increasing"
			 * the count by zero, as the only way to get the count is
			 * to call this function.
			 * The actual count increase happens further below, based
			 * on the `$increase_count` flag set here.
			 */
			$content_media_count = wp_increase_content_media_count( 0 );
			$increase_count      = true;

			// If the count so far is below the threshold, `loading` attribute is omitted.
			if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
				$maybe_in_viewport = true;
			} else {
				$maybe_in_viewport = false;
			}
		} elseif (
			// Only apply for main query but before the loop.
			$wp_query->before_loop && $wp_query->is_main_query()
			/*
			 * Any image before the loop, but after the header has started should not be lazy-loaded,
			 * except when the footer has already started which can happen when the current template
			 * does not include any loop.
			 */
			&& did_action( 'get_header' ) && ! did_action( 'get_footer' )
			) {
			$maybe_in_viewport    = true;
			$maybe_increase_count = true;
		}
	}

	/*
	 * If the element is in the viewport (`true`), potentially add
	 * `fetchpriority` with a value of "high". Otherwise, i.e. if the element
	 * is not not in the viewport (`false`) or it is unknown (`null`), add
	 * `loading` with a value of "lazy".
	 */
	if ( $maybe_in_viewport ) {
		$loading_attrs = wp_maybe_add_fetchpriority_high_attr( $loading_attrs, $tag_name, $attr );
	} else {
		// Only add `loading="lazy"` if the feature is enabled.
		if ( wp_lazy_loading_enabled( $tag_name, $context ) ) {
			$loading_attrs['loading'] = 'lazy';
		}
	}

	/*
	 * If flag was set based on contextual logic above, increase the content
	 * media count, either unconditionally, or based on whether the image size
	 * is larger than the threshold.
	 */
	if ( $increase_count ) {
		wp_increase_content_media_count();
	} elseif ( $maybe_increase_count ) {
		/** This filter is documented in wp-includes/media.php */
		$wp_min_priority_img_pixels = apply_filters( 'wp_min_priority_img_pixels', 50000 );

		if ( $wp_min_priority_img_pixels <= $attr['width'] * $attr['height'] ) {
			wp_increase_content_media_count();
		}
	}

	/**
	 * Filters the loading optimization attributes.
	 *
	 * @since 6.4.0
	 *
	 * @param array  $loading_attrs The loading optimization attributes.
	 * @param string $tag_name      The tag name.
	 * @param array  $attr          Array of the attributes for the tag.
	 * @param string $context       Context for the element for which the loading optimization attribute is requested.
	 */
	return apply_filters( 'wp_get_loading_optimization_attributes', $loading_attrs, $tag_name, $attr, $context );
}

/**
 * Gets the threshold for how many of the first content media elements to not lazy-load.
 *
 * This function runs the {@see 'wp_omit_loading_attr_threshold'} filter, which uses a default threshold value of 3.
 * The filter is only run once per page load, unless the `$force` parameter is used.
 *
 * @since 5.9.0
 *
 * @param bool $force Optional. If set to true, the filter will be (re-)applied even if it already has been before.
 *                    Default false.
 * @return int The number of content media elements to not lazy-load.
 */
function wp_omit_loading_attr_threshold( $force = false ) {
	static $omit_threshold;

	// This function may be called multiple times. Run the filter only once per page load.
	if ( ! isset( $omit_threshold ) || $force ) {
		/**
		 * Filters the threshold for how many of the first content media elements to not lazy-load.
		 *
		 * For these first content media elements, the `loading` attribute will be omitted. By default, this is the case
		 * for only the very first content media element.
		 *
		 * @since 5.9.0
		 * @since 6.3.0 The default threshold was changed from 1 to 3.
		 *
		 * @param int $omit_threshold The number of media elements where the `loading` attribute will not be added. Default 3.
		 */
		$omit_threshold = apply_filters( 'wp_omit_loading_attr_threshold', 3 );
	}

	return $omit_threshold;
}

/**
 * Increases an internal content media count variable.
 *
 * @since 5.9.0
 * @access private
 *
 * @param int $amount Optional. Amount to increase by. Default 1.
 * @return int The latest content media count, after the increase.
 */
function wp_increase_content_media_count( $amount = 1 ) {
	static $content_media_count = 0;

	$content_media_count += $amount;

	return $content_media_count;
}

/**
 * Determines whether to add `fetchpriority='high'` to loading attributes.
 *
 * @since 6.3.0
 * @access private
 *
 * @param array  $loading_attrs Array of the loading optimization attributes for the element.
 * @param string $tag_name      The tag name.
 * @param array  $attr          Array of the attributes for the element.
 * @return array Updated loading optimization attributes for the element.
 */
function wp_maybe_add_fetchpriority_high_attr( $loading_attrs, $tag_name, $attr ) {
	// For now, adding `fetchpriority="high"` is only supported for images.
	if ( 'img' !== $tag_name ) {
		return $loading_attrs;
	}

	if ( isset( $attr['fetchpriority'] ) ) {
		/*
		 * While any `fetchpriority` value could be set in `$loading_attrs`,
		 * for consistency we only do it for `fetchpriority="high"` since that
		 * is the only possible value that WordPress core would apply on its
		 * own.
		 */
		if ( 'high' === $attr['fetchpriority'] ) {
			$loading_attrs['fetchpriority'] = 'high';
			wp_high_priority_element_flag( false );
		}

		return $loading_attrs;
	}

	// Lazy-loading and `fetchpriority="high"` are mutually exclusive.
	if ( isset( $loading_attrs['loading'] ) && 'lazy' === $loading_attrs['loading'] ) {
		return $loading_attrs;
	}

	if ( ! wp_high_priority_element_flag() ) {
		return $loading_attrs;
	}

	/**
	 * Filters the minimum square-pixels threshold for an image to be eligible as the high-priority image.
	 *
	 * @since 6.3.0
	 *
	 * @param int $threshold Minimum square-pixels threshold. Default 50000.
	 */
	$wp_min_priority_img_pixels = apply_filters( 'wp_min_priority_img_pixels', 50000 );

	if ( $wp_min_priority_img_pixels <= $attr['width'] * $attr['height'] ) {
		$loading_attrs['fetchpriority'] = 'high';
		wp_high_priority_element_flag( false );
	}

	return $loading_attrs;
}

/**
 * Accesses a flag that indicates if an element is a possible candidate for `fetchpriority='high'`.
 *
 * @since 6.3.0
 * @access private
 *
 * @param bool $value Optional. Used to change the static variable. Default null.
 * @return bool Returns true if high-priority element was marked already, otherwise false.
 */
function wp_high_priority_element_flag( $value = null ) {
	static $high_priority_element = true;

	if ( is_bool( $value ) ) {
		$high_priority_element = $value;
	}

	return $high_priority_element;
}

/**
 * Determines the output format for the image editor.
 *
 * @since 6.7.0
 * @access private
 *
 * @param string $filename  Path to the image.
 * @param string $mime_type The source image mime type.
 * @return string[] An array of mime type mappings.
 */
function wp_get_image_editor_output_format( $filename, $mime_type ) {
	$output_format = array(
		'image/heic'          => 'image/jpeg',
		'image/heif'          => 'image/jpeg',
		'image/heic-sequence' => 'image/jpeg',
		'image/heif-sequence' => 'image/jpeg',
	);

	/**
	 * Filters the image editor output format mapping.
	 *
	 * Enables filtering the mime type used to save images. By default HEIC/HEIF images
	 * are converted to JPEGs.
	 *
	 * @see WP_Image_Editor::get_output_format()
	 *
	 * @since 5.8.0
	 * @since 6.7.0 The default was changed from an empty array to an array
	 *              containing the HEIC/HEIF images mime types.
	 *
	 * @param string[] $output_format {
	 *     An array of mime type mappings. Maps a source mime type to a new
	 *     destination mime type. By default maps HEIC/HEIF input to JPEG output.
	 *
	 *     @type string ...$0 The new mime type.
	 * }
	 * @param string $filename  Path to the image.
	 * @param string $mime_type The source image mime type.
	 */
	return apply_filters( 'image_editor_output_format', $output_format, $filename, $mime_type );
}
