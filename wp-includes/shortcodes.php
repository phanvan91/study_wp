<?php
/**
 * API WordPress để tạo các thẻ dạng bbcode hay còn gọi là "shortcode" trong WordPress.
 * Mã phân tích thẻ và thuộc tính hoặc biểu thức chính quy được dựa trên
 * bộ phân tích thẻ của Textpattern.
 *
 * Một vài ví dụ bên dưới:
 *
 * [shortcode /]
 * [shortcode foo="bar" baz="bing" /]
 * [shortcode foo="bar"]nội dung[/shortcode]
 *
 * Thẻ shortcode hỗ trợ thuộc tính và nội dung được bao bọc, nhưng không hoàn toàn
 * hỗ trợ shortcode nội tuyến trong các shortcode khác. Bạn sẽ cần gọi
 * bộ phân tích shortcode trong hàm của bạn để xử lý điều đó.
 *
 * {@internal
 * Xin lưu ý rằng ghi chú ở trên được viết trong giai đoạn beta của WordPress 2.6
 * và trong tương lai có thể không chính xác. Vui lòng cập nhật ghi chú khi nó không
 * còn đúng.}}
 *
 * Để áp dụng thẻ shortcode vào nội dung:
 *
 *     $out = do_shortcode( $content );
 *
 * @link https://developer.wordpress.org/plugins/shortcodes/
 *
 * @package WordPress
 * @subpackage Shortcodes
 * @since 2.5.0
 */

/**
 * Bộ chứa để lưu trữ các thẻ shortcode và hook callback tương ứng.
 *
 * @since 2.5.0
 *
 * @name $shortcode_tags
 * @var array
 * @global array $shortcode_tags
 */
$shortcode_tags = array();

/**
 * Thêm một shortcode mới.
 *
 * Cần cẩn thận thông qua tiền tố hoặc các cách khác để đảm bảo rằng
 * thẻ shortcode được thêm là duy nhất và sẽ không xung đột với các
 * thẻ shortcode đã được thêm trước đó. Trong trường hợp thẻ trùng lặp,
 * thẻ được tải sau cùng sẽ được ưu tiên.
 *
 * @since 2.5.0
 *
 * @global array $shortcode_tags
 *
 * @param string   $tag      Thẻ shortcode cần tìm kiếm trong nội dung bài viết.
 * @param callable $callback Hàm callback sẽ chạy khi tìm thấy shortcode.
 *                           Mỗi callback shortcode mặc định nhận ba tham số,
 *                           bao gồm một mảng thuộc tính (`$atts`), nội dung shortcode
 *                           hoặc null nếu không được đặt (`$content`), và cuối cùng là
 *                           chính thẻ shortcode (`$shortcode_tag`), theo thứ tự đó.
 */
function add_shortcode( $tag, $callback ) {
	global $shortcode_tags;

	if ( '' === trim( $tag ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			__( 'Invalid shortcode name: Empty name given.' ),
			'4.4.0'
		);
		return;
	}

	if ( 0 !== preg_match( '@[<>&/\[\]\x00-\x20=]@', $tag ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: Shortcode name, 2: Space-separated list of reserved characters. */
				__( 'Invalid shortcode name: %1$s. Do not use spaces or reserved characters: %2$s' ),
				$tag,
				'& / < > [ ] ='
			),
			'4.4.0'
		);
		return;
	}

	$shortcode_tags[ $tag ] = $callback;
}

/**
 * Gỡ bỏ hook cho shortcode.
 *
 * @since 2.5.0
 *
 * @global array $shortcode_tags
 *
 * @param string $tag Thẻ shortcode cần gỡ bỏ hook.
 */
function remove_shortcode( $tag ) {
	global $shortcode_tags;

	unset( $shortcode_tags[ $tag ] );
}

/**
 * Xóa tất cả shortcode.
 *
 * Hàm này xóa tất cả các thẻ shortcode bằng cách thay thế biến toàn cục shortcodes bằng
 * một mảng rỗng. Đây thực chất là một phương pháp hiệu quả để gỡ bỏ tất cả shortcode.
 *
 * @since 2.5.0
 *
 * @global array $shortcode_tags
 */
function remove_all_shortcodes() {
	global $shortcode_tags;

	$shortcode_tags = array();
}

/**
 * Xác định xem có shortcode đã đăng ký nào có tên $tag hay không.
 *
 * @since 3.6.0
 *
 * @global array $shortcode_tags Danh sách các thẻ shortcode và hook callback tương ứng.
 *
 * @param string $tag Thẻ shortcode cần kiểm tra.
 * @return bool Shortcode đã cho có tồn tại hay không.
 */
function shortcode_exists( $tag ) {
	global $shortcode_tags;
	return array_key_exists( $tag, $shortcode_tags );
}

/**
 * Xác định xem nội dung được truyền có chứa shortcode đã chỉ định hay không.
 *
 * @since 3.6.0
 *
 * @global array $shortcode_tags
 *
 * @param string $content Nội dung cần tìm kiếm shortcode.
 * @param string $tag     Thẻ shortcode cần kiểm tra.
 * @return bool Nội dung được truyền có chứa shortcode đã cho hay không.
 */
function has_shortcode( $content, $tag ) {
	if ( ! str_contains( $content, '[' ) ) {
		return false;
	}

	if ( shortcode_exists( $tag ) ) {
		preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			return false;
		}

		foreach ( $matches as $shortcode ) {
			if ( $tag === $shortcode[2] ) {
				return true;
			} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], $tag ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Trả về danh sách tên shortcode đã đăng ký được tìm thấy trong nội dung đã cho.
 *
 * Ví dụ sử dụng:
 *
 *     get_shortcode_tags_in_content( '[audio src="file.mp3"][/audio] [foo] [gallery ids="1,2,3"]' );
 *     // array( 'audio', 'gallery' )
 *
 * @since 6.3.2
 *
 * @param string $content Nội dung cần kiểm tra.
 * @return string[] Mảng tên shortcode đã đăng ký được tìm thấy trong nội dung.
 */
function get_shortcode_tags_in_content( $content ) {
	if ( false === strpos( $content, '[' ) ) {
		return array();
	}

	preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
	if ( empty( $matches ) ) {
		return array();
	}

	$tags = array();
	foreach ( $matches as $shortcode ) {
		$tags[] = $shortcode[2];

		if ( ! empty( $shortcode[5] ) ) {
			$deep_tags = get_shortcode_tags_in_content( $shortcode[5] );
			if ( ! empty( $deep_tags ) ) {
				$tags = array_merge( $tags, $deep_tags );
			}
		}
	}

	return $tags;
}

/**
 * Tìm kiếm shortcode trong nội dung và lọc shortcode qua các hook tương ứng.
 *
 * Hàm này là bí danh của do_shortcode().
 *
 * @since 5.4.0
 *
 * @see do_shortcode()
 *
 * @param string $content     Nội dung cần tìm kiếm shortcode.
 * @param bool   $ignore_html Khi true, shortcode bên trong phần tử HTML sẽ bị bỏ qua.
 *                            Mặc định false.
 * @return string Nội dung đã lọc bỏ shortcode.
 */
function apply_shortcodes( $content, $ignore_html = false ) {
	return do_shortcode( $content, $ignore_html );
}

/**
 * Tìm kiếm shortcode trong nội dung và lọc shortcode qua các hook tương ứng.
 *
 * Nếu không có thẻ shortcode nào được định nghĩa, nội dung sẽ được trả về
 * mà không có bất kỳ bộ lọc nào. Điều này có thể gây ra vấn đề khi plugin bị vô hiệu hóa nhưng
 * shortcode vẫn hiển thị trong bài viết hoặc nội dung.
 *
 * @since 2.5.0
 *
 * @global array $shortcode_tags Danh sách các thẻ shortcode và hook callback tương ứng.
 *
 * @param string $content     Nội dung cần tìm kiếm shortcode.
 * @param bool   $ignore_html Khi true, shortcode bên trong phần tử HTML sẽ bị bỏ qua.
 *                            Mặc định false.
 * @return string Nội dung đã lọc bỏ shortcode.
 */
function do_shortcode( $content, $ignore_html = false ) {
	global $shortcode_tags;

	if ( ! str_contains( $content, '[' ) ) {
		return $content;
	}

	if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
		return $content;
	}

	// Tìm tất cả tên thẻ đã đăng ký trong $content.
	preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
	$tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );

	if ( empty( $tagnames ) ) {
		return $content;
	}

	// Đảm bảo ngữ cảnh này chỉ được thêm một lần nếu shortcode lồng nhau.
	$has_filter   = has_filter( 'wp_get_attachment_image_context', '_filter_do_shortcode_context' );
	$filter_added = false;

	if ( ! $has_filter ) {
		$filter_added = add_filter( 'wp_get_attachment_image_context', '_filter_do_shortcode_context' );
	}

	$content = do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames );

	$pattern = get_shortcode_regex( $tagnames );
	$content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );

	// Luôn khôi phục dấu ngoặc vuông để không làm hỏng các thứ như <!--[if IE ]>.
	$content = unescape_invalid_shortcodes( $content );

	// Chỉ gỡ bỏ filter nếu nó được thêm trong phạm vi này.
	if ( $filter_added ) {
		remove_filter( 'wp_get_attachment_image_context', '_filter_do_shortcode_context' );
	}

	return $content;
}

/**
 * Lọc hook `wp_get_attachment_image_context` trong quá trình render shortcode.
 *
 * Khi wp_get_attachment_image() được gọi trong quá trình render shortcode, chúng ta cần làm rõ
 * rằng ngữ cảnh là shortcode chứ không phải một phần của logic render template của theme.
 *
 * @since 6.3.0
 * @access private
 *
 * @return string Giá trị ngữ cảnh đã lọc cho wp_get_attachment_images khi thực thi shortcode.
 */
function _filter_do_shortcode_context() {
	return 'do_shortcode';
}

/**
 * Lấy biểu thức chính quy shortcode để tìm kiếm.
 *
 * Biểu thức chính quy kết hợp các thẻ shortcode trong biểu thức chính quy
 * dưới dạng lớp regex.
 *
 * Biểu thức chính quy chứa 6 nhóm con khớp khác nhau để hỗ trợ phân tích.
 *
 * 1 - Dấu [ thêm để cho phép thoát shortcode bằng dấu ngoặc kép [[]]
 * 2 - Tên shortcode
 * 3 - Danh sách đối số shortcode
 * 4 - Dấu / tự đóng
 * 5 - Nội dung của shortcode khi nó bao bọc một số nội dung.
 * 6 - Dấu ] thêm để cho phép thoát shortcode bằng dấu ngoặc kép [[]]
 *
 * @since 2.5.0
 * @since 4.4.0 Thêm tham số `$tagnames`.
 *
 * @global array $shortcode_tags
 *
 * @param array $tagnames Tùy chọn. Danh sách shortcode cần tìm. Mặc định là tất cả shortcode đã đăng ký.
 * @return string Biểu thức chính quy tìm kiếm shortcode.
 */
function get_shortcode_regex( $tagnames = null ) {
	global $shortcode_tags;

	if ( empty( $tagnames ) ) {
		$tagnames = array_keys( $shortcode_tags );
	}
	$tagregexp = implode( '|', array_map( 'preg_quote', $tagnames ) );

	/*
	 * CẢNH BÁO! Không thay đổi regex này mà không thay đổi do_shortcode_tag() và strip_shortcode_tag().
	 * Đồng thời, xem shortcode_unautop() và shortcode.js.
	 */

	// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
	return '\\['                             // Dấu ngoặc mở.
		. '(\\[?)'                           // 1: Dấu ngoặc mở thứ hai tùy chọn để thoát shortcode: [[tag]].
		. "($tagregexp)"                     // 2: Tên shortcode.
		. '(?![\\w-])'                       // Không theo sau bởi ký tự từ hoặc dấu gạch ngang.
		. '('                                // 3: Mở vòng lặp: Bên trong thẻ shortcode mở.
		.     '[^\\]\\/]*'                   // Không phải dấu đóng ngoặc hoặc gạch chéo xuôi.
		.     '(?:'
		.         '\\/(?!\\])'               // Gạch chéo xuôi không theo sau bởi dấu đóng ngoặc.
		.         '[^\\]\\/]*'               // Không phải dấu đóng ngoặc hoặc gạch chéo xuôi.
		.     ')*?'
		. ')'
		. '(?:'
		.     '(\\/)'                        // 4: Thẻ tự đóng...
		.     '\\]'                          // ...và dấu đóng ngoặc.
		. '|'
		.     '\\]'                          // Dấu đóng ngoặc.
		.     '(?:'
		.         '('                        // 5: Mở vòng lặp: Tùy chọn, bất kỳ thứ gì giữa thẻ shortcode mở và đóng.
		.             '[^\\[]*+'             // Không phải dấu mở ngoặc.
		.             '(?:'
		.                 '\\[(?!\\/\\2\\])' // Dấu mở ngoặc không theo sau bởi thẻ đóng shortcode.
		.                 '[^\\[]*+'         // Không phải dấu mở ngoặc.
		.             ')*+'
		.         ')'
		.         '\\[\\/\\2\\]'             // Thẻ đóng shortcode.
		.     ')?'
		. ')'
		. '(\\]?)';                          // 6: Dấu đóng ngoặc thứ hai tùy chọn để thoát shortcode: [[tag]].
	// phpcs:enable
}

/**
 * Hàm callback biểu thức chính quy cho do_shortcode() để gọi hook shortcode.
 *
 * @see get_shortcode_regex() để biết chi tiết về nội dung mảng khớp.
 *
 * @since 2.5.0
 * @access private
 *
 * @global array $shortcode_tags
 *
 * @param array $m {
 *     Mảng kết quả khớp biểu thức chính quy.
 *
 *     @type string $0 Toàn bộ văn bản shortcode đã khớp.
 *     @type string $1 Dấu mở ngoặc thứ hai tùy chọn để thoát shortcode.
 *     @type string $2 Tên shortcode.
 *     @type string $3 Danh sách đối số shortcode.
 *     @type string $4 Dấu gạch chéo tự đóng tùy chọn.
 *     @type string $5 Nội dung của shortcode khi nó bao bọc nội dung.
 *     @type string $6 Dấu đóng ngoặc thứ hai tùy chọn để thoát shortcode.
 * }
 * @return string Kết quả xuất ra của shortcode.
 */
function do_shortcode_tag( $m ) {
	global $shortcode_tags;

	// Cho phép cú pháp [[foo]] để thoát thẻ.
	if ( '[' === $m[1] && ']' === $m[6] ) {
		return substr( $m[0], 1, -1 );
	}

	$tag  = $m[2];
	$attr = shortcode_parse_atts( $m[3] );

	if ( ! is_callable( $shortcode_tags[ $tag ] ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			/* translators: %s: Shortcode tag. */
			sprintf( __( 'Attempting to parse a shortcode without a valid callback: %s' ), $tag ),
			'4.3.0'
		);
		return $m[0];
	}

	/**
	 * Lọc xem có nên gọi callback shortcode hay không.
	 *
	 * Trả về giá trị khác false từ filter sẽ bỏ qua quá trình
	 * tạo shortcode, trả về giá trị đó thay thế.
	 *
	 * @since 4.7.0
	 * @since 6.5.0 Tham số `$attr` luôn là một mảng.
	 *
	 * @param false|string $output Giá trị trả về bỏ qua. Hoặc false hoặc giá trị thay thế cho shortcode.
	 * @param string       $tag    Tên shortcode.
	 * @param array        $attr   Mảng thuộc tính shortcode, có thể rỗng nếu chuỗi đối số gốc không thể phân tích.
	 * @param array        $m      Mảng kết quả khớp biểu thức chính quy.
	 */
	$return = apply_filters( 'pre_do_shortcode_tag', false, $tag, $attr, $m );
	if ( false !== $return ) {
		return $return;
	}

	$content = isset( $m[5] ) ? $m[5] : null;

	$output = $m[1] . call_user_func( $shortcode_tags[ $tag ], $attr, $content, $tag ) . $m[6];

	/**
	 * Lọc kết quả xuất ra được tạo bởi callback shortcode.
	 *
	 * @since 4.7.0
	 * @since 6.5.0 Tham số `$attr` luôn là một mảng.
	 *
	 * @param string $output Kết quả xuất ra của shortcode.
	 * @param string $tag    Tên shortcode.
	 * @param array  $attr   Mảng thuộc tính shortcode, có thể rỗng nếu chuỗi đối số gốc không thể phân tích.
	 * @param array  $m      Mảng kết quả khớp biểu thức chính quy.
	 */
	return apply_filters( 'do_shortcode_tag', $output, $tag, $attr, $m );
}

/**
 * Tìm kiếm chỉ bên trong các phần tử HTML để tìm shortcode và xử lý chúng.
 *
 * Bất kỳ ký tự [ hoặc ] nào còn lại bên trong phần tử sẽ được mã hóa HTML
 * để tránh can thiệp với shortcode bên ngoài phần tử.
 * Giả định $content đã được xử lý bởi KSES. Người dùng có quyền unfiltered_html
 * có thể nhận kết quả không mong muốn nếu dấu ngoặc nhọn lồng nhau trong thẻ.
 *
 * @since 4.2.3
 *
 * @param string $content     Nội dung cần tìm kiếm shortcode.
 * @param bool   $ignore_html Khi true, tất cả dấu ngoặc vuông bên trong phần tử sẽ được mã hóa.
 * @param array  $tagnames    Danh sách shortcode cần tìm.
 * @return string Nội dung đã lọc bỏ shortcode.
 */
function do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames ) {
	// Chuẩn hóa thực thể trong HTML chưa lọc trước khi thêm placeholder.
	$trans   = array(
		'&#91;' => '&#091;',
		'&#93;' => '&#093;',
	);
	$content = strtr( $content, $trans );
	$trans   = array(
		'[' => '&#91;',
		']' => '&#93;',
	);

	$pattern = get_shortcode_regex( $tagnames );
	$textarr = wp_html_split( $content );

	foreach ( $textarr as &$element ) {
		if ( '' === $element || '<' !== $element[0] ) {
			continue;
		}

		$noopen  = ! str_contains( $element, '[' );
		$noclose = ! str_contains( $element, ']' );
		if ( $noopen || $noclose ) {
			// Phần tử này không chứa shortcode.
			if ( $noopen xor $noclose ) {
				// Cần mã hóa ký tự '[' hoặc ']' lạc.
				$element = strtr( $element, $trans );
			}
			continue;
		}

		if ( $ignore_html || str_starts_with( $element, '<!--' ) || str_starts_with( $element, '<![CDATA[' ) ) {
			// Mã hóa tất cả ký tự '[' và ']'.
			$element = strtr( $element, $trans );
			continue;
		}

		$attributes = wp_kses_attr_parse( $element );
		if ( false === $attributes ) {
			// Một số plugin đang làm những thứ như [name] <[email]>.
			if ( 1 === preg_match( '%^<\s*\[\[?[^\[\]]+\]%', $element ) ) {
				$element = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $element );
			}

			// Có vẻ như chúng ta tìm thấy HTML chưa lọc không mong muốn. Bỏ qua để đảm bảo an toàn.
			$element = strtr( $element, $trans );
			continue;
		}

		// Lấy tên phần tử.
		$front   = array_shift( $attributes );
		$back    = array_pop( $attributes );
		$matches = array();
		preg_match( '%[a-zA-Z0-9]+%', $front, $matches );
		$elname = $matches[0];

		// Tìm shortcode trong từng thuộc tính riêng biệt.
		foreach ( $attributes as &$attr ) {
			$open  = strpos( $attr, '[' );
			$close = strpos( $attr, ']' );
			if ( false === $open || false === $close ) {
				continue; // Chuyển sang thuộc tính tiếp theo. Dấu ngoặc vuông sẽ được thoát ở cuối vòng lặp.
			}
			$double = strpos( $attr, '"' );
			$single = strpos( $attr, "'" );
			if ( ( false === $single || $open < $single ) && ( false === $double || $open < $double ) ) {
				/*
				 * $attr như '[shortcode]' hoặc 'name = [shortcode]' ngụ ý unfiltered_html.
				 * Trong trường hợp cụ thể này chúng ta giả định KSES không chạy vì đầu vào
				 * được viết bởi quản trị viên, vì vậy chúng ta nên tránh thay đổi đầu ra
				 * và không cần chạy KSES ở đây.
				 */
				$attr = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $attr );
			} else {
				/*
				 * $attr như 'name = "[shortcode]"' hoặc "name = '[shortcode]'".
				 * Chúng ta không biết $content có chưa được lọc không. Giả định KSES đã chạy trước shortcode.
				 */
				$count    = 0;
				$new_attr = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $attr, -1, $count );
				if ( $count > 0 ) {
					// Làm sạch đầu ra shortcode bằng KSES.
					$new_attr = wp_kses_one_attr( $new_attr, $elname );
					if ( '' !== trim( $new_attr ) ) {
						// Shortcode đã an toàn để sử dụng.
						$attr = $new_attr;
					}
				}
			}
		}
		$element = $front . implode( '', $attributes ) . $back;

		// Bây giờ mã hóa bất kỳ ký tự '[' hoặc ']' còn lại.
		$element = strtr( $element, $trans );
	}

	$content = implode( '', $textarr );

	return $content;
}

/**
 * Gỡ bỏ các placeholder được thêm bởi do_shortcodes_in_html_tags().
 *
 * @since 4.2.3
 *
 * @param string $content Nội dung cần tìm kiếm placeholder.
 * @return string Nội dung đã gỡ bỏ placeholder.
 */
function unescape_invalid_shortcodes( $content ) {
	// Dọn dẹp toàn bộ chuỗi, tránh phân tích lại HTML.
	$trans = array(
		'&#91;' => '[',
		'&#93;' => ']',
	);

	$content = strtr( $content, $trans );

	return $content;
}

/**
 * Lấy biểu thức chính quy thuộc tính shortcode.
 *
 * @since 4.4.0
 *
 * @return string Biểu thức chính quy thuộc tính shortcode.
 */
function get_shortcode_atts_regex() {
	return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';
}

/**
 * Lấy tất cả thuộc tính từ thẻ shortcode.
 *
 * Danh sách thuộc tính có tên thuộc tính làm khóa và giá trị thuộc tính
 * làm giá trị trong cặp khóa/giá trị. Điều này cho phép truy xuất thuộc tính
 * dễ dàng hơn, vì tất cả thuộc tính cần phải được biết trước.
 *
 * @since 2.5.0
 * @since 6.5.0 Hàm bây giờ luôn trả về một mảng,
 *              ngay cả khi chuỗi đối số gốc không thể phân tích hoặc rỗng.
 *
 * @param string $text Danh sách đối số shortcode.
 * @return array Mảng giá trị thuộc tính được đánh khóa bởi tên thuộc tính.
 *               Trả về mảng rỗng nếu không có thuộc tính
 *               hoặc nếu chuỗi đối số gốc không thể phân tích.
 */
function shortcode_parse_atts( $text ) {
	$atts    = array();
	$pattern = get_shortcode_atts_regex();
	$text    = preg_replace( "/[\x{00a0}\x{200b}]+/u", ' ', $text );
	if ( preg_match_all( $pattern, $text, $match, PREG_SET_ORDER ) ) {
		foreach ( $match as $m ) {
			if ( ! empty( $m[1] ) ) {
				$atts[ strtolower( $m[1] ) ] = stripcslashes( $m[2] );
			} elseif ( ! empty( $m[3] ) ) {
				$atts[ strtolower( $m[3] ) ] = stripcslashes( $m[4] );
			} elseif ( ! empty( $m[5] ) ) {
				$atts[ strtolower( $m[5] ) ] = stripcslashes( $m[6] );
			} elseif ( isset( $m[7] ) && strlen( $m[7] ) ) {
				$atts[] = stripcslashes( $m[7] );
			} elseif ( isset( $m[8] ) && strlen( $m[8] ) ) {
				$atts[] = stripcslashes( $m[8] );
			} elseif ( isset( $m[9] ) ) {
				$atts[] = stripcslashes( $m[9] );
			}
		}

		// Loại bỏ bất kỳ phần tử HTML chưa đóng nào.
		foreach ( $atts as &$value ) {
			if ( str_contains( $value, '<' ) ) {
				if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
					$value = '';
				}
			}
		}
	}

	return $atts;
}

/**
 * Kết hợp thuộc tính người dùng với thuộc tính đã biết và điền giá trị mặc định khi cần.
 *
 * Các cặp nên được coi là tất cả thuộc tính được hỗ trợ bởi
 * hàm gọi và được cung cấp dưới dạng danh sách. Thuộc tính trả về sẽ
 * chỉ chứa các thuộc tính trong danh sách $pairs.
 *
 * Nếu danh sách $atts có các thuộc tính không được hỗ trợ, chúng sẽ bị bỏ qua và
 * loại bỏ khỏi danh sách trả về cuối cùng.
 *
 * @since 2.5.0
 *
 * @param array  $pairs     Toàn bộ danh sách thuộc tính được hỗ trợ và giá trị mặc định của chúng.
 * @param array  $atts      Thuộc tính do người dùng định nghĩa trong thẻ shortcode.
 * @param string $shortcode Tùy chọn. Tên của shortcode, cung cấp ngữ cảnh để cho phép lọc.
 * @return array Danh sách thuộc tính đã kết hợp và lọc.
 */
function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$atts = (array) $atts;
	$out  = array();
	foreach ( $pairs as $name => $default ) {
		if ( array_key_exists( $name, $atts ) ) {
			$out[ $name ] = $atts[ $name ];
		} else {
			$out[ $name ] = $default;
		}
	}

	if ( $shortcode ) {
		/**
		 * Lọc thuộc tính shortcode.
		 *
		 * Nếu tham số thứ ba của hàm shortcode_atts() có mặt thì filter này khả dụng.
		 * Tham số thứ ba, $shortcode, là tên của shortcode.
		 *
		 * @since 3.6.0
		 * @since 4.4.0 Thêm tham số `$shortcode`.
		 *
		 * @param array  $out       Mảng đầu ra của thuộc tính shortcode.
		 * @param array  $pairs     Các thuộc tính được hỗ trợ và giá trị mặc định của chúng.
		 * @param array  $atts      Thuộc tính shortcode do người dùng định nghĩa.
		 * @param string $shortcode Tên shortcode.
		 */
		$out = apply_filters( "shortcode_atts_{$shortcode}", $out, $pairs, $atts, $shortcode );
	}

	return $out;
}

/**
 * Gỡ bỏ tất cả thẻ shortcode khỏi nội dung đã cho.
 *
 * @since 2.5.0
 *
 * @global array $shortcode_tags
 *
 * @param string $content Nội dung cần gỡ bỏ thẻ shortcode.
 * @return string Nội dung không có thẻ shortcode.
 */
function strip_shortcodes( $content ) {
	global $shortcode_tags;

	if ( ! str_contains( $content, '[' ) ) {
		return $content;
	}

	if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
		return $content;
	}

	// Tìm tất cả tên thẻ đã đăng ký trong $content.
	preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );

	$tags_to_remove = array_keys( $shortcode_tags );

	/**
	 * Lọc danh sách thẻ shortcode cần gỡ bỏ khỏi nội dung.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $tags_to_remove Mảng thẻ shortcode cần gỡ bỏ.
	 * @param string $content        Nội dung đang được gỡ bỏ shortcode.
	 */
	$tags_to_remove = apply_filters( 'strip_shortcodes_tagnames', $tags_to_remove, $content );

	$tagnames = array_intersect( $tags_to_remove, $matches[1] );

	if ( empty( $tagnames ) ) {
		return $content;
	}

	$content = do_shortcodes_in_html_tags( $content, true, $tagnames );

	$pattern = get_shortcode_regex( $tagnames );
	$content = preg_replace_callback( "/$pattern/", 'strip_shortcode_tag', $content );

	// Luôn khôi phục dấu ngoặc vuông để không làm hỏng các thứ như <!--[if IE ]>.
	$content = unescape_invalid_shortcodes( $content );

	return $content;
}

/**
 * Gỡ bỏ thẻ shortcode dựa trên kết quả khớp biểu thức chính quy với nội dung bài viết.
 *
 * @since 3.3.0
 *
 * @param array $m Kết quả khớp biểu thức chính quy với nội dung bài viết.
 * @return string|false Nội dung đã gỡ bỏ thẻ, nếu không trả về false.
 */
function strip_shortcode_tag( $m ) {
	// Cho phép cú pháp [[foo]] để thoát thẻ.
	if ( '[' === $m[1] && ']' === $m[6] ) {
		return substr( $m[0], 1, -1 );
	}

	return $m[1] . $m[6];
}
