<?php
/**
 * Taxonomy API: Các thẻ template chuyên biệt cho chuyên mục
 *
 * @package WordPress
 * @subpackage Template
 * @since 1.2.0
 */

/**
 * Lấy URL liên kết chuyên mục.
 *
 * @since 1.0.0
 *
 * @see get_term_link()
 *
 * @param int|object $category ID chuyên mục hoặc đối tượng.
 * @return string Liên kết nếu thành công, chuỗi rỗng nếu chuyên mục không tồn tại.
 */
function get_category_link( $category ) {
	if ( ! is_object( $category ) ) {
		$category = (int) $category;
	}

	$category = get_term_link( $category );

	if ( is_wp_error( $category ) ) {
		return '';
	}

	return $category;
}

/**
 * Lấy các chuyên mục cha với ký tự phân cách.
 *
 * @since 1.2.0
 * @since 4.8.0 Tham số `$visited` đã ngừng sử dụng và đổi tên thành `$deprecated`.
 *
 * @param int    $category_id ID chuyên mục.
 * @param bool   $link        Tùy chọn. Có định dạng với liên kết hay không. Mặc định false.
 * @param string $separator   Tùy chọn. Cách phân cách các chuyên mục. Mặc định '/'.
 * @param bool   $nicename    Tùy chọn. Có sử dụng tên đẹp để hiển thị hay không. Mặc định false.
 * @param array  $deprecated  Không sử dụng.
 * @return string|WP_Error Danh sách các chuyên mục cha nếu thành công, WP_Error nếu thất bại.
 */
function get_category_parents( $category_id, $link = false, $separator = '/', $nicename = false, $deprecated = array() ) {

	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '4.8.0' );
	}

	$format = $nicename ? 'slug' : 'name';

	$args = array(
		'separator' => $separator,
		'link'      => $link,
		'format'    => $format,
	);

	return get_term_parents_list( $category_id, 'category', $args );
}

/**
 * Lấy các chuyên mục của bài viết.
 *
 * Thẻ này có thể được sử dụng bên ngoài Vòng lặp bằng cách truyền ID bài viết làm tham số.
 *
 * Lưu ý: Hàm này chỉ trả về kết quả từ taxonomy "category" mặc định.
 * Đối với taxonomy tùy chỉnh, sử dụng get_the_terms().
 *
 * @since 0.71
 *
 * @param int $post_id Tùy chọn. ID bài viết. Mặc định là ID bài viết hiện tại.
 * @return WP_Term[] Mảng các đối tượng WP_Term, mỗi đối tượng cho một chuyên mục được gán cho bài viết.
 */
function get_the_category( $post_id = false ) {
	$categories = get_the_terms( $post_id, 'category' );
	if ( ! $categories || is_wp_error( $categories ) ) {
		$categories = array();
	}

	$categories = array_values( $categories );

	foreach ( array_keys( $categories ) as $key ) {
		_make_cat_compat( $categories[ $key ] );
	}

	/**
	 * Lọc mảng các chuyên mục trả về cho một bài viết.
	 *
	 * @since 3.1.0
	 * @since 4.4.0 Thêm tham số `$post_id`.
	 *
	 * @param WP_Term[] $categories Mảng các chuyên mục trả về cho bài viết.
	 * @param int|false $post_id    ID bài viết.
	 */
	return apply_filters( 'get_the_categories', $categories, $post_id );
}

/**
 * Lấy tên chuyên mục dựa trên ID chuyên mục.
 *
 * @since 0.71
 *
 * @param int $cat_id ID chuyên mục.
 * @return string|WP_Error Tên chuyên mục nếu thành công, WP_Error nếu thất bại.
 */
function get_the_category_by_ID( $cat_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$cat_id   = (int) $cat_id;
	$category = get_term( $cat_id );

	if ( is_wp_error( $category ) ) {
		return $category;
	}

	return ( $category ) ? $category->name : '';
}

/**
 * Lấy danh sách chuyên mục cho bài viết dưới dạng danh sách HTML hoặc định dạng tùy chỉnh.
 *
 * Thường được sử dụng cho các danh sách chuyên mục nhanh, có phân cách (ví dụ: phân cách bằng dấu phẩy),
 * như một phần của meta entry bài viết.
 *
 * Để có hàm mạnh mẽ hơn dựa trên danh sách, xem wp_list_categories().
 *
 * @since 1.5.1
 *
 * @see wp_list_categories()
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $separator Tùy chọn. Ký tự phân cách giữa các chuyên mục. Mặc định, các liên kết được đặt
 *                          trong danh sách không có thứ tự. Chuỗi rỗng sẽ sử dụng hành vi mặc định.
 * @param string $parents   Tùy chọn. Cách hiển thị các chuyên mục cha. Chấp nhận 'multiple', 'single', hoặc rỗng.
 *                          Mặc định chuỗi rỗng.
 * @param int    $post_id   Tùy chọn. ID bài viết để lấy chuyên mục. Mặc định là bài viết hiện tại.
 * @return string Danh sách chuyên mục cho bài viết.
 */
function get_the_category_list( $separator = '', $parents = '', $post_id = false ) {
	global $wp_rewrite;

	if ( ! is_object_in_taxonomy( get_post_type( $post_id ), 'category' ) ) {
		/** This filter is documented in wp-includes/category-template.php */
		return apply_filters( 'the_category', '', $separator, $parents );
	}

	/**
	 * Lọc các chuyên mục trước khi xây dựng danh sách chuyên mục.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Term[] $categories Mảng các chuyên mục của bài viết.
	 * @param int|false $post_id    ID bài viết để lấy chuyên mục.
	 *                              Khi `false`, mặc định là bài viết hiện tại trong vòng lặp.
	 */
	$categories = apply_filters( 'the_category_list', get_the_category( $post_id ), $post_id );

	if ( empty( $categories ) ) {
		/** This filter is documented in wp-includes/category-template.php */
		return apply_filters( 'the_category', __( 'Uncategorized' ), $separator, $parents );
	}

	$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? 'rel="category tag"' : 'rel="category"';

	$thelist = '';
	if ( '' === $separator ) {
		$thelist .= '<ul class="post-categories">';
		foreach ( $categories as $category ) {
			$thelist .= "\n\t<li>";
			switch ( strtolower( $parents ) ) {
				case 'multiple':
					if ( $category->parent ) {
						$thelist .= get_category_parents( $category->parent, true, $separator );
					}
					$thelist .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" ' . $rel . '>' . $category->name . '</a></li>';
					break;
				case 'single':
					$thelist .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '"  ' . $rel . '>';
					if ( $category->parent ) {
						$thelist .= get_category_parents( $category->parent, false, $separator );
					}
					$thelist .= $category->name . '</a></li>';
					break;
				case '':
				default:
					$thelist .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" ' . $rel . '>' . $category->name . '</a></li>';
			}
		}
		$thelist .= '</ul>';
	} else {
		$i = 0;
		foreach ( $categories as $category ) {
			if ( 0 < $i ) {
				$thelist .= $separator;
			}
			switch ( strtolower( $parents ) ) {
				case 'multiple':
					if ( $category->parent ) {
						$thelist .= get_category_parents( $category->parent, true, $separator );
					}
					$thelist .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" ' . $rel . '>' . $category->name . '</a>';
					break;
				case 'single':
					$thelist .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" ' . $rel . '>';
					if ( $category->parent ) {
						$thelist .= get_category_parents( $category->parent, false, $separator );
					}
					$thelist .= "$category->name</a>";
					break;
				case '':
				default:
					$thelist .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" ' . $rel . '>' . $category->name . '</a>';
			}
			++$i;
		}
	}

	/**
	 * Lọc chuyên mục hoặc danh sách các chuyên mục.
	 *
	 * @since 1.2.0
	 *
	 * @param string $thelist   Danh sách chuyên mục cho bài viết hiện tại.
	 * @param string $separator Ký tự phân cách được sử dụng giữa các chuyên mục.
	 * @param string $parents   Cách hiển thị các chuyên mục cha. Chấp nhận 'multiple',
	 *                          'single', hoặc rỗng.
	 */
	return apply_filters( 'the_category', $thelist, $separator, $parents );
}

/**
 * Kiểm tra xem bài viết hiện tại có thuộc bất kỳ chuyên mục nào trong các chuyên mục đã cho hay không.
 *
 * Các chuyên mục đã cho được kiểm tra dựa trên term_ids, tên và slug của các chuyên mục bài viết.
 * Chuyên mục được truyền dưới dạng số nguyên chỉ kiểm tra dựa trên term_ids của các chuyên mục bài viết.
 *
 * Trước phiên bản 2.5 của WordPress, tên chuyên mục chưa được hỗ trợ.
 * Trước phiên bản 2.7, slug chuyên mục chưa được hỗ trợ.
 * Trước phiên bản 2.7, chỉ có thể so sánh một chuyên mục: in_category( $single_category ).
 * Trước phiên bản 2.7, hàm này chỉ có thể sử dụng trong Vòng lặp WordPress.
 * Từ phiên bản 2.7, hàm có thể sử dụng ở bất kỳ đâu nếu được cung cấp ID bài viết hoặc đối tượng bài viết.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 1.2.0
 * @since 2.7.0 Thêm tham số `$post`.
 *
 * @param int|string|int[]|string[] $category ID chuyên mục, tên, slug, hoặc mảng các giá trị đó
 *                                            để kiểm tra.
 * @param int|WP_Post               $post     Tùy chọn. Bài viết để kiểm tra. Mặc định là bài viết hiện tại.
 * @return bool True nếu bài viết hiện tại thuộc bất kỳ chuyên mục nào đã cho.
 */
function in_category( $category, $post = null ) {
	if ( empty( $category ) ) {
		return false;
	}

	return has_category( $category, $post );
}

/**
 * Hiển thị danh sách chuyên mục cho bài viết dưới dạng danh sách HTML hoặc định dạng tùy chỉnh.
 *
 * @since 0.71
 *
 * @param string $separator Tùy chọn. Ký tự phân cách giữa các chuyên mục. Mặc định, các liên kết được đặt
 *                          trong danh sách không có thứ tự. Chuỗi rỗng sẽ sử dụng hành vi mặc định.
 * @param string $parents   Tùy chọn. Cách hiển thị các chuyên mục cha. Chấp nhận 'multiple', 'single', hoặc rỗng.
 *                          Mặc định chuỗi rỗng.
 * @param int    $post_id   Tùy chọn. ID bài viết để lấy chuyên mục. Mặc định là bài viết hiện tại.
 */
function the_category( $separator = '', $parents = '', $post_id = false ) {
	echo get_the_category_list( $separator, $parents, $post_id );
}

/**
 * Lấy mô tả chuyên mục.
 *
 * @since 1.0.0
 *
 * @param int $category Tùy chọn. ID chuyên mục. Mặc định là ID chuyên mục hiện tại.
 * @return string Mô tả chuyên mục, nếu có.
 */
function category_description( $category = 0 ) {
	return term_description( $category );
}

/**
 * Hiển thị hoặc lấy danh sách dropdown HTML của các chuyên mục.
 *
 * Đối số 'hierarchical', bị tắt theo mặc định, sẽ ghi đè đối số
 * depth, trừ khi nó là true. Khi đối số là false, nó sẽ
 * hiển thị tất cả các chuyên mục. Khi được bật, nó sẽ sử dụng giá trị trong
 * đối số 'depth'.
 *
 * @since 2.1.0
 * @since 4.2.0 Giới thiệu đối số `value_field`.
 * @since 4.6.0 Giới thiệu đối số `required`.
 * @since 6.1.0 Giới thiệu đối số `aria_describedby`.
 *
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số để tạo phần tử dropdown chuyên mục. Xem WP_Term_Query::__construct()
 *     để biết thông tin về các đối số bổ sung được chấp nhận.
 *
 *     @type string       $show_option_all   Văn bản hiển thị cho tùy chọn tất cả chuyên mục. Mặc định rỗng.
 *     @type string       $show_option_none  Văn bản hiển thị cho tùy chọn không có chuyên mục. Mặc định rỗng.
 *     @type string       $option_none_value Giá trị sử dụng khi không có chuyên mục nào được chọn. Mặc định rỗng.
 *     @type string       $orderby           Cột dùng để sắp xếp chuyên mục. Xem get_terms() để biết danh sách
 *                                           các giá trị được chấp nhận. Mặc định 'id' (term_id).
 *     @type bool         $pad_counts        Xem get_terms() để biết mô tả đối số. Mặc định false.
 *     @type bool|int     $show_count        Có bao gồm số lượng bài viết hay không. Chấp nhận 0, 1, hoặc tương đương bool.
 *                                           Mặc định 0.
 *     @type bool|int     $echo              Có echo hay trả về markup được tạo. Chấp nhận 0, 1, hoặc tương đương bool.
 *                                           Mặc định 1.
 *     @type bool|int     $hierarchical      Có duyệt theo phân cấp taxonomy hay không. Chấp nhận 0, 1, hoặc tương đương bool.
 *                                           Mặc định 0.
 *     @type int          $depth             Độ sâu tối đa. Mặc định 0.
 *     @type int          $tab_index         Chỉ mục tab cho phần tử select. Mặc định 0 (không có tabindex).
 *     @type string       $name              Giá trị cho thuộc tính 'name' của phần tử select. Mặc định 'cat'.
 *     @type string       $id                Giá trị cho thuộc tính 'id' của phần tử select. Mặc định là giá trị
 *                                           của `$name`.
 *     @type string       $class             Giá trị cho thuộc tính 'class' của phần tử select. Mặc định 'postform'.
 *     @type int|string   $selected          Giá trị của tùy chọn cần được chọn. Mặc định 0.
 *     @type string       $value_field       Trường term dùng để điền thuộc tính 'value'
 *                                           của các phần tử option. Chấp nhận bất kỳ trường term hợp lệ: 'term_id', 'name',
 *                                           'slug', 'term_group', 'term_taxonomy_id', 'taxonomy', 'description',
 *                                           'parent', 'count'. Mặc định 'term_id'.
 *     @type string|array $taxonomy          Tên taxonomy hoặc các taxonomy cần lấy. Mặc định 'category'.
 *     @type bool         $hide_if_empty     True để bỏ qua tạo markup nếu không tìm thấy chuyên mục.
 *                                           Mặc định false (tạo phần tử select ngay cả khi không tìm thấy chuyên mục).
 *     @type bool         $required          Phần tử `<select>` có nên có thuộc tính HTML5 'required' hay không.
 *                                           Mặc định false.
 *     @type Walker       $walker            Đối tượng Walker dùng để xây dựng đầu ra. Mặc định rỗng sẽ sử dụng
 *                                           instance Walker_CategoryDropdown.
 *     @type string       $aria_describedby  'id' của phần tử chứa văn bản mô tả cho select.
 *                                           Mặc định chuỗi rỗng.
 * }
 * @return string Danh sách dropdown HTML của các chuyên mục.
 */
function wp_dropdown_categories( $args = '' ) {
	$defaults = array(
		'show_option_all'   => '',
		'show_option_none'  => '',
		'orderby'           => 'id',
		'order'             => 'ASC',
		'show_count'        => 0,
		'hide_empty'        => 1,
		'child_of'          => 0,
		'exclude'           => '',
		'echo'              => 1,
		'selected'          => 0,
		'hierarchical'      => 0,
		'name'              => 'cat',
		'id'                => '',
		'class'             => 'postform',
		'depth'             => 0,
		'tab_index'         => 0,
		'taxonomy'          => 'category',
		'hide_if_empty'     => false,
		'option_none_value' => -1,
		'value_field'       => 'term_id',
		'required'          => false,
		'aria_describedby'  => '',
	);

	$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

	// Tương thích ngược.
	if ( isset( $args['type'] ) && 'link' === $args['type'] ) {
		_deprecated_argument(
			__FUNCTION__,
			'3.0.0',
			sprintf(
				/* translators: 1: "type => link", 2: "taxonomy => link_category" */
				__( '%1$s is deprecated. Use %2$s instead.' ),
				'<code>type => link</code>',
				'<code>taxonomy => link_category</code>'
			)
		);
		$args['taxonomy'] = 'link_category';
	}

	// Phân tích $args đầu vào thành mảng và hợp nhất với $defaults.
	$parsed_args = wp_parse_args( $args, $defaults );

	$option_none_value = $parsed_args['option_none_value'];

	if ( ! isset( $parsed_args['pad_counts'] ) && $parsed_args['show_count'] && $parsed_args['hierarchical'] ) {
		$parsed_args['pad_counts'] = true;
	}

	$tab_index = $parsed_args['tab_index'];

	$tab_index_attribute = '';
	if ( (int) $tab_index > 0 ) {
		$tab_index_attribute = " tabindex=\"$tab_index\"";
	}

	// Tránh xung đột với tham số 'name' của get_terms().
	$get_terms_args = $parsed_args;
	unset( $get_terms_args['name'] );
	$categories = get_terms( $get_terms_args );

	$name     = esc_attr( $parsed_args['name'] );
	$class    = esc_attr( $parsed_args['class'] );
	$id       = $parsed_args['id'] ? esc_attr( $parsed_args['id'] ) : $name;
	$required = $parsed_args['required'] ? 'required' : '';

	$aria_describedby_attribute = $parsed_args['aria_describedby'] ? ' aria-describedby="' . esc_attr( $parsed_args['aria_describedby'] ) . '"' : '';

	if ( ! $parsed_args['hide_if_empty'] || ! empty( $categories ) ) {
		$output = "<select $required name='$name' id='$id' class='$class'$tab_index_attribute$aria_describedby_attribute>\n";
	} else {
		$output = '';
	}
	if ( empty( $categories ) && ! $parsed_args['hide_if_empty'] && ! empty( $parsed_args['show_option_none'] ) ) {

		/**
		 * Lọc phần tử hiển thị dropdown taxonomy.
		 *
		 * Nhiều phần tử hiển thị dropdown taxonomy có thể được chỉnh sửa
		 * ngay trước khi hiển thị thông qua bộ lọc này. Các đối số có thể lọc bao gồm
		 * 'show_option_none', 'show_option_all', và các dạng khác nhau của
		 * tên term.
		 *
		 * @since 1.2.0
		 *
		 * @see wp_dropdown_categories()
		 *
		 * @param string       $element  Tên chuyên mục.
		 * @param WP_Term|null $category Đối tượng chuyên mục, hoặc null nếu không có chuyên mục tương ứng.
		 */
		$show_option_none = apply_filters( 'list_cats', $parsed_args['show_option_none'], null );
		$output          .= "\t<option value='" . esc_attr( $option_none_value ) . "' selected='selected'>$show_option_none</option>\n";
	}

	if ( ! empty( $categories ) ) {

		if ( $parsed_args['show_option_all'] ) {

			/** This filter is documented in wp-includes/category-template.php */
			$show_option_all = apply_filters( 'list_cats', $parsed_args['show_option_all'], null );
			$selected        = ( '0' === (string) $parsed_args['selected'] ) ? " selected='selected'" : '';
			$output         .= "\t<option value='0'$selected>$show_option_all</option>\n";
		}

		if ( $parsed_args['show_option_none'] ) {

			/** This filter is documented in wp-includes/category-template.php */
			$show_option_none = apply_filters( 'list_cats', $parsed_args['show_option_none'], null );
			$selected         = selected( $option_none_value, $parsed_args['selected'], false );
			$output          .= "\t<option value='" . esc_attr( $option_none_value ) . "'$selected>$show_option_none</option>\n";
		}

		if ( $parsed_args['hierarchical'] ) {
			$depth = $parsed_args['depth'];  // Walk the full depth.
		} else {
			$depth = -1; // Flat.
		}
		$output .= walk_category_dropdown_tree( $categories, $depth, $parsed_args );
	}

	if ( ! $parsed_args['hide_if_empty'] || ! empty( $categories ) ) {
		$output .= "</select>\n";
	}

	/**
	 * Lọc đầu ra dropdown taxonomy.
	 *
	 * @since 2.1.0
	 *
	 * @param string $output      Đầu ra HTML.
	 * @param array  $parsed_args Các đối số được sử dụng để xây dựng dropdown.
	 */
	$output = apply_filters( 'wp_dropdown_cats', $output, $parsed_args );

	if ( $parsed_args['echo'] ) {
		echo $output;
	}

	return $output;
}

/**
 * Hiển thị hoặc lấy danh sách HTML các chuyên mục.
 *
 * @since 2.1.0
 * @since 4.4.0 Giới thiệu đối số `hide_title_if_empty` và `separator`.
 * @since 4.4.0 Đối số `current_category` được chỉnh sửa để tùy chọn chấp nhận mảng giá trị.
 * @since 6.1.0 Giá trị mặc định của đối số 'use_desc_for_title' được thay đổi từ 1 sang 0.
 *
 * @param array|string $args {
 *     Mảng các đối số tùy chọn. Xem get_categories(), get_terms(), và WP_Term_Query::__construct()
 *     để biết thông tin về các đối số bổ sung được chấp nhận.
 *
 *     @type int|int[]    $current_category      ID chuyên mục, hoặc mảng các ID chuyên mục, sẽ nhận
 *                                               class 'current-cat'. Mặc định 0.
 *     @type int          $depth                 Độ sâu chuyên mục. Dùng cho thụt lề tab. Mặc định 0.
 *     @type bool|int     $echo                  Có echo hay trả về markup được tạo. Chấp nhận 0, 1, hoặc
 *                                               tương đương bool. Mặc định 1.
 *     @type int[]|string $exclude               Mảng hoặc chuỗi phân cách bằng dấu phẩy/khoảng trắng các ID term cần loại trừ.
 *                                               Nếu `$hierarchical` là true, các term con của `$exclude` cũng sẽ
 *                                               bị loại trừ; xem `$exclude_tree`. Xem get_terms().
 *                                               Mặc định chuỗi rỗng.
 *     @type int[]|string $exclude_tree          Mảng hoặc chuỗi phân cách bằng dấu phẩy/khoảng trắng các ID term cần loại trừ,
 *                                               cùng với các term con của chúng. Xem get_terms(). Mặc định chuỗi rỗng.
 *     @type string       $feed                  Văn bản sử dụng cho liên kết feed. Mặc định 'Feed for all posts filed
 *                                               under [cat name]'.
 *     @type string       $feed_image            URL hình ảnh sử dụng cho liên kết feed. Mặc định chuỗi rỗng.
 *     @type string       $feed_type             Loại feed. Dùng để xây dựng liên kết feed. Xem get_term_feed_link().
 *                                               Mặc định chuỗi rỗng (feed mặc định).
 *     @type bool         $hide_title_if_empty   Có ẩn phần tử `$title_li` nếu không có term nào trong
 *                                               danh sách. Mặc định false (tiêu đề luôn được hiển thị).
 *     @type string       $separator             Ký tự phân cách giữa các liên kết. Mặc định '<br />'.
 *     @type bool|int     $show_count            Có bao gồm số lượng bài viết hay không. Chấp nhận 0, 1, hoặc tương đương bool.
 *                                               Mặc định 0.
 *     @type string       $show_option_all       Văn bản hiển thị cho tùy chọn tất cả chuyên mục. Mặc định chuỗi rỗng.
 *     @type string       $show_option_none      Văn bản hiển thị cho tùy chọn 'không có chuyên mục'.
 *                                               Mặc định 'No categories'.
 *     @type string       $style                 Kiểu hiển thị danh sách chuyên mục. Nếu 'list', chuyên mục
 *                                               sẽ xuất ra dưới dạng danh sách không có thứ tự. Nếu rỗng hoặc giá trị khác,
 *                                               chuyên mục sẽ xuất ra phân cách bằng thẻ `<br>`. Mặc định 'list'.
 *     @type string       $taxonomy              Tên taxonomy cần lấy. Mặc định 'category'.
 *     @type string       $title_li              Văn bản sử dụng cho phần tử `<li>` tiêu đề danh sách. Truyền chuỗi rỗng
 *                                               để tắt. Mặc định 'Categories'.
 *     @type bool|int     $use_desc_for_title    Có sử dụng mô tả chuyên mục làm thuộc tính title hay không.
 *                                               Chấp nhận 0, 1, hoặc tương đương bool. Mặc định 0.
 *     @type Walker       $walker                Đối tượng Walker dùng để xây dựng đầu ra. Mặc định rỗng sẽ sử dụng
 *                                               instance Walker_Category.
 * }
 * @return void|string|false Void nếu đối số 'echo' là true, danh sách HTML các chuyên mục nếu 'echo' là false.
 *                           False nếu taxonomy không tồn tại.
 */
function wp_list_categories( $args = '' ) {
	$defaults = array(
		'child_of'            => 0,
		'current_category'    => 0,
		'depth'               => 0,
		'echo'                => 1,
		'exclude'             => '',
		'exclude_tree'        => '',
		'feed'                => '',
		'feed_image'          => '',
		'feed_type'           => '',
		'hide_empty'          => 1,
		'hide_title_if_empty' => false,
		'hierarchical'        => true,
		'order'               => 'ASC',
		'orderby'             => 'name',
		'separator'           => '<br />',
		'show_count'          => 0,
		'show_option_all'     => '',
		'show_option_none'    => __( 'No categories' ),
		'style'               => 'list',
		'taxonomy'            => 'category',
		'title_li'            => __( 'Categories' ),
		'use_desc_for_title'  => 0,
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	if ( ! isset( $parsed_args['pad_counts'] ) && $parsed_args['show_count'] && $parsed_args['hierarchical'] ) {
		$parsed_args['pad_counts'] = true;
	}

	// Các term con của các term bị loại trừ cũng nên bị loại trừ.
	if ( $parsed_args['hierarchical'] ) {
		$exclude_tree = array();

		if ( $parsed_args['exclude_tree'] ) {
			$exclude_tree = array_merge( $exclude_tree, wp_parse_id_list( $parsed_args['exclude_tree'] ) );
		}

		if ( $parsed_args['exclude'] ) {
			$exclude_tree = array_merge( $exclude_tree, wp_parse_id_list( $parsed_args['exclude'] ) );
		}

		$parsed_args['exclude_tree'] = $exclude_tree;
		$parsed_args['exclude']      = '';
	}

	if ( ! isset( $parsed_args['class'] ) ) {
		$parsed_args['class'] = ( 'category' === $parsed_args['taxonomy'] ) ? 'categories' : $parsed_args['taxonomy'];
	}

	if ( ! taxonomy_exists( $parsed_args['taxonomy'] ) ) {
		return false;
	}

	$show_option_all  = $parsed_args['show_option_all'];
	$show_option_none = $parsed_args['show_option_none'];

	$categories = get_categories( $parsed_args );

	$output = '';

	if ( $parsed_args['title_li'] && 'list' === $parsed_args['style']
		&& ( ! empty( $categories ) || ! $parsed_args['hide_title_if_empty'] )
	) {
		$output = '<li class="' . esc_attr( $parsed_args['class'] ) . '">' . $parsed_args['title_li'] . '<ul>';
	}

	if ( empty( $categories ) ) {
		if ( ! empty( $show_option_none ) ) {
			if ( 'list' === $parsed_args['style'] ) {
				$output .= '<li class="cat-item-none">' . $show_option_none . '</li>';
			} else {
				$output .= $show_option_none;
			}
		}
	} else {
		if ( ! empty( $show_option_all ) ) {

			$posts_page = '';

			// Đối với taxonomy chỉ thuộc về các loại bài viết tùy chỉnh, trỏ đến một trang lưu trữ hợp lệ.
			$taxonomy_object = get_taxonomy( $parsed_args['taxonomy'] );
			if ( ! in_array( 'post', $taxonomy_object->object_type, true ) && ! in_array( 'page', $taxonomy_object->object_type, true ) ) {
				foreach ( $taxonomy_object->object_type as $object_type ) {
					$_object_type = get_post_type_object( $object_type );

					// Lấy cái đầu tiên.
					if ( ! empty( $_object_type->has_archive ) ) {
						$posts_page = get_post_type_archive_link( $object_type );
						break;
					}
				}
			}

			// Dự phòng cho liên kết 'Tất cả' là trang bài viết.
			if ( ! $posts_page ) {
				if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) ) {
					$posts_page = get_permalink( get_option( 'page_for_posts' ) );
				} else {
					$posts_page = home_url( '/' );
				}
			}

			$posts_page = esc_url( $posts_page );
			if ( 'list' === $parsed_args['style'] ) {
				$output .= "<li class='cat-item-all'><a href='$posts_page'>$show_option_all</a></li>";
			} else {
				$output .= "<a href='$posts_page'>$show_option_all</a>";
			}
		}

		if ( empty( $parsed_args['current_category'] ) && ( is_category() || is_tax() || is_tag() ) ) {
			$current_term_object = get_queried_object();
			if ( $current_term_object && $parsed_args['taxonomy'] === $current_term_object->taxonomy ) {
				$parsed_args['current_category'] = get_queried_object_id();
			}
		}

		if ( $parsed_args['hierarchical'] ) {
			$depth = $parsed_args['depth'];
		} else {
			$depth = -1; // Flat.
		}
		$output .= walk_category_tree( $categories, $depth, $parsed_args );
	}

	if ( $parsed_args['title_li'] && 'list' === $parsed_args['style']
		&& ( ! empty( $categories ) || ! $parsed_args['hide_title_if_empty'] )
	) {
		$output .= '</ul></li>';
	}

	/**
	 * Lọc đầu ra HTML của danh sách taxonomy.
	 *
	 * @since 2.1.0
	 *
	 * @param string       $output Đầu ra HTML.
	 * @param array|string $args   Mảng hoặc chuỗi truy vấn các đối số liệt kê taxonomy. Xem
	 *                             wp_list_categories() để biết thông tin về các đối số được chấp nhận.
	 */
	$html = apply_filters( 'wp_list_categories', $output, $args );

	if ( $parsed_args['echo'] ) {
		echo $html;
	} else {
		return $html;
	}
}

/**
 * Hiển thị đám mây thẻ.
 *
 * Xuất danh sách các thẻ dưới dạng 'đám mây thẻ', trong đó kích thước của mỗi thẻ
 * được xác định bởi số lần thẻ đó được gán cho bài viết.
 *
 * @since 2.3.0
 * @since 2.8.0 Thêm đối số `taxonomy`.
 * @since 4.8.0 Thêm đối số `show_count`.
 *
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số để hiển thị đám mây thẻ. Xem wp_generate_tag_cloud()
 *     và get_terms() để biết danh sách đầy đủ các đối số có thể truyền trong `$args`.
 *
 *     @type int    $number    Số lượng thẻ hiển thị. Chấp nhận bất kỳ số nguyên dương nào
 *                             hoặc 0 để trả về tất cả. Mặc định 45.
 *     @type string $link      Hiển thị liên kết chỉnh sửa term hay liên kết cố định term.
 *                             Chấp nhận 'edit' và 'view'. Mặc định 'view'.
 *     @type string $post_type Loại bài viết. Dùng để tô sáng menu loại bài viết phù hợp
 *                             trên trang chỉnh sửa được liên kết. Mặc định là loại bài viết đầu tiên
 *                             liên kết với taxonomy.
 *     @type bool   $echo      Có echo giá trị trả về hay không. Mặc định true.
 * }
 * @return void|string|string[] Void nếu đối số 'echo' là true, hoặc khi thất bại. Nếu không, đám mây thẻ
 *                              dưới dạng chuỗi hoặc mảng, tùy thuộc vào đối số 'format'.
 */
function wp_tag_cloud( $args = '' ) {
	$defaults = array(
		'smallest'   => 8,
		'largest'    => 22,
		'unit'       => 'pt',
		'number'     => 45,
		'format'     => 'flat',
		'separator'  => "\n",
		'orderby'    => 'name',
		'order'      => 'ASC',
		'exclude'    => '',
		'include'    => '',
		'link'       => 'view',
		'taxonomy'   => 'post_tag',
		'post_type'  => '',
		'echo'       => true,
		'show_count' => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$tags = get_terms(
		array_merge(
			$args,
			array(
				'orderby' => 'count',
				'order'   => 'DESC',
			)
		)
	); // Always query top tags.

	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return;
	}

	foreach ( $tags as $key => $tag ) {
		if ( 'edit' === $args['link'] ) {
			$link = get_edit_term_link( $tag, $tag->taxonomy, $args['post_type'] );
		} else {
			$link = get_term_link( $tag, $tag->taxonomy );
		}

		if ( is_wp_error( $link ) ) {
			return;
		}

		$tags[ $key ]->link = $link;
		$tags[ $key ]->id   = $tag->term_id;
	}

	// Đây là nơi các thẻ hàng đầu được sắp xếp theo $args.
	$return = wp_generate_tag_cloud( $tags, $args );

	/**
	 * Lọc đầu ra đám mây thẻ.
	 *
	 * @since 2.3.0
	 *
	 * @param string|string[] $return Đám mây thẻ dưới dạng chuỗi hoặc mảng, tùy thuộc vào đối số 'format'.
	 * @param array           $args   Mảng các đối số đám mây thẻ. Xem wp_tag_cloud()
	 *                                để biết thông tin về các đối số được chấp nhận.
	 */
	$return = apply_filters( 'wp_tag_cloud', $return, $args );

	if ( 'array' === $args['format'] || empty( $args['echo'] ) ) {
		return $return;
	}

	echo $return;
}

/**
 * Tỷ lệ đếm chủ đề mặc định cho liên kết thẻ.
 *
 * @since 2.9.0
 *
 * @param int $count Số lượng bài viết có thẻ đó.
 * @return int Số đếm đã được chia tỷ lệ.
 */
function default_topic_count_scale( $count ) {
	return round( log10( $count + 1 ) * 100 );
}

/**
 * Tạo đám mây thẻ (heatmap) từ dữ liệu được cung cấp.
 *
 * @todo Hoàn thiện chức năng.
 * @since 2.3.0
 * @since 4.8.0 Thêm đối số `show_count`.
 *
 * @param WP_Term[]    $tags Mảng các đối tượng WP_Term để tạo đám mây thẻ.
 * @param string|array $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số để tạo đám mây thẻ.
 *
 *     @type int      $smallest                   Kích thước font nhỏ nhất dùng để hiển thị thẻ. Kết hợp
 *                                                với giá trị `$unit`, để xác định đơn vị kích thước
 *                                                chữ CSS. Mặc định 8 (pt).
 *     @type int      $largest                    Kích thước font lớn nhất dùng để hiển thị thẻ. Kết hợp
 *                                                với giá trị `$unit`, để xác định đơn vị kích thước
 *                                                chữ CSS. Mặc định 22 (pt).
 *     @type string   $unit                       Đơn vị kích thước chữ CSS sử dụng với giá trị `$smallest`
 *                                                và `$largest`. Chấp nhận bất kỳ đơn vị kích thước
 *                                                chữ CSS hợp lệ. Mặc định 'pt'.
 *     @type int      $number                     Số lượng thẻ trả về. Chấp nhận bất kỳ
 *                                                số nguyên dương hoặc 0 để trả về tất cả.
 *                                                Mặc định 0.
 *     @type string   $format                     Định dạng hiển thị đám mây thẻ. Chấp nhận 'flat'
 *                                                (thẻ phân cách bằng khoảng trắng), 'list' (thẻ hiển thị
 *                                                trong danh sách không có thứ tự), hoặc 'array' (trả về mảng).
 *                                                Mặc định 'flat'.
 *     @type string   $separator                  HTML hoặc văn bản phân cách các thẻ. Mặc định "\n" (dòng mới).
 *     @type string   $orderby                    Giá trị để sắp xếp thẻ. Chấp nhận 'name' hoặc 'count'.
 *                                                Mặc định 'name'. Bộ lọc {@see 'tag_cloud_sort'}
 *                                                cũng có thể ảnh hưởng đến cách thẻ được sắp xếp.
 *     @type string   $order                      Cách sắp xếp các thẻ. Chấp nhận 'ASC' (tăng dần),
 *                                                'DESC' (giảm dần), hoặc 'RAND' (ngẫu nhiên). Mặc định 'ASC'.
 *     @type int|bool $filter                     Có bật lọc đầu ra cuối cùng hay không
 *                                                thông qua {@see 'wp_generate_tag_cloud'}. Mặc định 1.
 *     @type array    $topic_count_text           Văn bản số nhiều nooped từ _n_noop() cung cấp cho
 *                                                số đếm thẻ. Mặc định null.
 *     @type callable $topic_count_text_callback  Callback dùng để tạo văn bản số nhiều nooped cho
 *                                                số đếm thẻ dựa trên số đếm. Mặc định null.
 *     @type callable $topic_count_scale_callback Callback dùng để xác định giá trị chia tỷ lệ
 *                                                số đếm thẻ. Mặc định default_topic_count_scale().
 *     @type bool|int $show_count                 Có hiển thị số đếm thẻ hay không. Mặc định 0. Chấp nhận
 *                                                0, 1, hoặc tương đương bool.
 * }
 * @return string|string[] Đám mây thẻ dưới dạng chuỗi hoặc mảng, tùy thuộc vào đối số 'format'.
 */
function wp_generate_tag_cloud( $tags, $args = '' ) {
	$defaults = array(
		'smallest'                   => 8,
		'largest'                    => 22,
		'unit'                       => 'pt',
		'number'                     => 0,
		'format'                     => 'flat',
		'separator'                  => "\n",
		'orderby'                    => 'name',
		'order'                      => 'ASC',
		'topic_count_text'           => null,
		'topic_count_text_callback'  => null,
		'topic_count_scale_callback' => 'default_topic_count_scale',
		'filter'                     => 1,
		'show_count'                 => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$return = ( 'array' === $args['format'] ) ? array() : '';

	if ( empty( $tags ) ) {
		return $return;
	}

	// Xử lý số đếm chủ đề.
	if ( isset( $args['topic_count_text'] ) ) {
		// Trước tiên tìm hỗ trợ số nhiều nooped thông qua topic_count_text.
		$translate_nooped_plural = $args['topic_count_text'];
	} elseif ( ! empty( $args['topic_count_text_callback'] ) ) {
		// Tìm kiểu callback thay thế. Bỏ qua giá trị mặc định trước đó.
		if ( 'default_topic_count_text' === $args['topic_count_text_callback'] ) {
			/* translators: %s: Number of items (tags). */
			$translate_nooped_plural = _n_noop( '%s item', '%s items' );
		} else {
			$translate_nooped_plural = false;
		}
	} elseif ( isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
		// Nếu không có callback, tìm các đối số kiểu cũ single_text và multiple_text.
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular,WordPress.WP.I18n.NonSingularStringLiteralPlural
		$translate_nooped_plural = _n_noop( $args['single_text'], $args['multiple_text'] );
	} else {
		// Đây là giá trị mặc định khi không có callback, số nhiều, hoặc đối số nào được truyền vào.
		/* translators: %s: Number of items (tags). */
		$translate_nooped_plural = _n_noop( '%s item', '%s items' );
	}

	/**
	 * Lọc cách các mục trong đám mây thẻ được sắp xếp.
	 *
	 * @since 2.8.0
	 *
	 * @param WP_Term[] $tags Mảng các term đã được sắp xếp.
	 * @param array     $args Mảng các đối số đám mây thẻ.
	 */
	$tags_sorted = apply_filters( 'tag_cloud_sort', $tags, $args );
	if ( empty( $tags_sorted ) ) {
		return $return;
	}

	if ( $tags_sorted !== $tags ) {
		$tags = $tags_sorted;
		unset( $tags_sorted );
	} else {
		if ( 'RAND' === $args['order'] ) {
			shuffle( $tags );
		} else {
			// SQL không thể giúp bạn ở đây; đây là lần sắp xếp thứ hai (có thể khác) trên một tập con dữ liệu.
			if ( 'name' === $args['orderby'] ) {
				uasort( $tags, '_wp_object_name_sort_cb' );
			} else {
				uasort( $tags, '_wp_object_count_sort_cb' );
			}

			if ( 'DESC' === $args['order'] ) {
				$tags = array_reverse( $tags, true );
			}
		}
	}

	if ( $args['number'] > 0 ) {
		$tags = array_slice( $tags, 0, $args['number'] );
	}

	$counts      = array();
	$real_counts = array(); // Cho thẻ alt.
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag->count;
		$counts[ $key ]      = call_user_func( $args['topic_count_scale_callback'], $tag->count );
	}

	$min_count = min( $counts );
	$spread    = max( $counts ) - $min_count;
	if ( $spread <= 0 ) {
		$spread = 1;
	}
	$font_spread = $args['largest'] - $args['smallest'];
	if ( $font_spread < 0 ) {
		$font_spread = 1;
	}
	$font_step = $font_spread / $spread;

	$aria_label = false;
	/*
	 * Xác định có xuất thuộc tính 'aria-label' với tên thẻ và số đếm hay không.
	 * Khi các thẻ có kích thước font khác nhau, chúng truyền đạt thông tin quan trọng
	 * bằng hình ảnh mà cũng cần có sẵn cho các công nghệ hỗ trợ. Mặt khác, đôi khi
	 * các theme thiết lập Đám mây thẻ hiển thị tất cả thẻ với cùng kích thước font (đặt
	 * đối số 'smallest' và 'largest' cùng giá trị).
	 * Để luôn cung cấp cùng nội dung cho tất cả người dùng, 'aria-label' được xuất ra:
	 * - khi các thẻ có kích thước khác nhau
	 * - khi số đếm thẻ được hiển thị (ví dụ khi người dùng đánh dấu checkbox trong
	 *   widget Đám mây thẻ), bất kể kích thước font của thẻ
	 */
	if ( $args['show_count'] || 0 !== $font_spread ) {
		$aria_label = true;
	}

	// Tập hợp dữ liệu sẽ được sử dụng để tạo markup đám mây thẻ.
	$tags_data = array();
	foreach ( $tags as $key => $tag ) {
		$tag_id = isset( $tag->id ) ? $tag->id : $key;

		$count      = $counts[ $key ];
		$real_count = $real_counts[ $key ];

		if ( $translate_nooped_plural ) {
			$formatted_count = sprintf( translate_nooped_plural( $translate_nooped_plural, $real_count ), number_format_i18n( $real_count ) );
		} else {
			$formatted_count = call_user_func( $args['topic_count_text_callback'], $real_count, $tag, $args );
		}

		$tags_data[] = array(
			'id'              => $tag_id,
			'url'             => ( '#' !== $tag->link ) ? $tag->link : '#',
			'role'            => ( '#' !== $tag->link ) ? '' : ' role="button"',
			'name'            => $tag->name,
			'formatted_count' => $formatted_count,
			'slug'            => $tag->slug,
			'real_count'      => $real_count,
			'class'           => 'tag-cloud-link tag-link-' . $tag_id,
			'font_size'       => $args['smallest'] + ( $count - $min_count ) * $font_step,
			'aria_label'      => $aria_label ? sprintf( ' aria-label="%1$s (%2$s)"', esc_attr( $tag->name ), esc_attr( $formatted_count ) ) : '',
			'show_count'      => $args['show_count'] ? '<span class="tag-link-count"> (' . $real_count . ')</span>' : '',
		);
	}

	/**
	 * Lọc dữ liệu được sử dụng để tạo đám mây thẻ.
	 *
	 * @since 4.3.0
	 *
	 * @param array[] $tags_data Mảng các mảng dữ liệu term cho các term được sử dụng để tạo đám mây thẻ.
	 */
	$tags_data = apply_filters( 'wp_generate_tag_cloud_data', $tags_data );

	$a = array();

	// Tạo mảng liên kết đầu ra.
	foreach ( $tags_data as $key => $tag_data ) {
		$class = $tag_data['class'] . ' tag-link-position-' . ( $key + 1 );
		$a[]   = sprintf(
			'<a href="%1$s"%2$s class="%3$s" style="font-size: %4$s;"%5$s>%6$s%7$s</a>',
			esc_url( $tag_data['url'] ),
			$tag_data['role'],
			esc_attr( $class ),
			esc_attr( str_replace( ',', '.', $tag_data['font_size'] ) . $args['unit'] ),
			$tag_data['aria_label'],
			esc_html( $tag_data['name'] ),
			$tag_data['show_count']
		);
	}

	switch ( $args['format'] ) {
		case 'array':
			$return =& $a;
			break;
		case 'list':
			/*
			 * Ép buộc role="list", vì một số trình duyệt (đặc biệt: Safari 10) không cung cấp cho
			 * các công nghệ hỗ trợ vai trò mặc định khi danh sách được tạo kiểu với `list-style: none`.
			 * Lưu ý: điều này dư thừa nhưng không gây hại.
			 */
			$return  = "<ul class='wp-tag-cloud' role='list'>\n\t<li>";
			$return .= implode( "</li>\n\t<li>", $a );
			$return .= "</li>\n</ul>\n";
			break;
		default:
			$return = implode( $args['separator'], $a );
			break;
	}

	if ( $args['filter'] ) {
		/**
		 * Lọc đầu ra đã tạo của đám mây thẻ.
		 *
		 * Bộ lọc chỉ được đánh giá nếu giá trị true được truyền
		 * cho đối số $filter trong wp_generate_tag_cloud().
		 *
		 * @since 2.3.0
		 *
		 * @see wp_generate_tag_cloud()
		 *
		 * @param string[]|string $return Chuỗi chứa đầu ra HTML đám mây thẻ đã tạo
		 *                                hoặc mảng liên kết thẻ nếu đối số 'format'
		 *                                bằng 'array'.
		 * @param WP_Term[]       $tags   Mảng các term được sử dụng trong đám mây thẻ.
		 * @param array           $args   Mảng các đối số wp_generate_tag_cloud().
		 */
		return apply_filters( 'wp_generate_tag_cloud', $return, $tags, $args );
	} else {
		return $return;
	}
}

/**
 * Dùng làm callback để so sánh các đối tượng dựa trên tên.
 *
 * Sử dụng với `uasort()`.
 *
 * @since 3.1.0
 * @access private
 *
 * @param object $a Đối tượng thứ nhất để so sánh.
 * @param object $b Đối tượng thứ hai để so sánh.
 * @return int Số âm nếu `$a->name` nhỏ hơn `$b->name`, 0 nếu bằng nhau,
 *             hoặc lớn hơn 0 nếu `$a->name` lớn hơn `$b->name`.
 */
function _wp_object_name_sort_cb( $a, $b ) {
	return strnatcasecmp( $a->name, $b->name );
}

/**
 * Serves as a callback for comparing objects based on count.
 *
 * Used with `uasort()`.
 *
 * @since 3.1.0
 * @access private
 *
 * @param object $a The first object to compare.
 * @param object $b The second object to compare.
 * @return int Negative number if `$a->count` is less than `$b->count`, zero if they are equal,
 *             or greater than zero if `$a->count` is greater than `$b->count`.
 */
function _wp_object_count_sort_cb( $a, $b ) {
	return ( $a->count - $b->count );
}

//
// Helper functions.
//

/**
 * Retrieves HTML list content for category list.
 *
 * @since 2.1.0
 * @since 5.3.0 Formalized the existing `...$args` parameter by adding it
 *              to the function signature.
 *
 * @uses Walker_Category to create HTML list content.
 * @see Walker::walk() for parameters and return description.
 *
 * @param mixed ...$args Elements array, maximum hierarchical depth and optional additional arguments.
 * @return string
 */
function walk_category_tree( ...$args ) {
	// The user's options are the third parameter.
	if ( empty( $args[2]['walker'] ) || ! ( $args[2]['walker'] instanceof Walker ) ) {
		$walker = new Walker_Category();
	} else {
		/**
		 * @var Walker $walker
		 */
		$walker = $args[2]['walker'];
	}
	return $walker->walk( ...$args );
}

/**
 * Retrieves HTML dropdown (select) content for category list.
 *
 * @since 2.1.0
 * @since 5.3.0 Formalized the existing `...$args` parameter by adding it
 *              to the function signature.
 *
 * @uses Walker_CategoryDropdown to create HTML dropdown content.
 * @see Walker::walk() for parameters and return description.
 *
 * @param mixed ...$args Elements array, maximum hierarchical depth and optional additional arguments.
 * @return string
 */
function walk_category_dropdown_tree( ...$args ) {
	// The user's options are the third parameter.
	if ( empty( $args[2]['walker'] ) || ! ( $args[2]['walker'] instanceof Walker ) ) {
		$walker = new Walker_CategoryDropdown();
	} else {
		/**
		 * @var Walker $walker
		 */
		$walker = $args[2]['walker'];
	}
	return $walker->walk( ...$args );
}

//
// Tags.
//

/**
 * Retrieves the link to the tag.
 *
 * @since 2.3.0
 *
 * @see get_term_link()
 *
 * @param int|object $tag Tag ID or object.
 * @return string Link on success, empty string if tag does not exist.
 */
function get_tag_link( $tag ) {
	return get_category_link( $tag );
}

/**
 * Retrieves the tags for a post.
 *
 * @since 2.3.0
 *
 * @param int|WP_Post $post Post ID or object.
 * @return WP_Term[]|false|WP_Error Array of WP_Term objects on success, false if there are no terms
 *                                  or the post does not exist, WP_Error on failure.
 */
function get_the_tags( $post = 0 ) {
	$terms = get_the_terms( $post, 'post_tag' );

	/**
	 * Filters the array of tags for the given post.
	 *
	 * @since 2.3.0
	 *
	 * @see get_the_terms()
	 *
	 * @param WP_Term[]|false|WP_Error $terms Array of WP_Term objects on success, false if there are no terms
	 *                                        or the post does not exist, WP_Error on failure.
	 */
	return apply_filters( 'get_the_tags', $terms );
}

/**
 * Retrieves the tags for a post formatted as a string.
 *
 * @since 2.3.0
 *
 * @param string $before  Optional. String to use before the tags. Default empty.
 * @param string $sep     Optional. String to use between the tags. Default empty.
 * @param string $after   Optional. String to use after the tags. Default empty.
 * @param int    $post_id Optional. Post ID. Defaults to the current post ID.
 * @return string|false|WP_Error A list of tags on success, false if there are no terms,
 *                               WP_Error on failure.
 */
function get_the_tag_list( $before = '', $sep = '', $after = '', $post_id = 0 ) {
	$tag_list = get_the_term_list( $post_id, 'post_tag', $before, $sep, $after );

	/**
	 * Filters the tags list for a given post.
	 *
	 * @since 2.3.0
	 *
	 * @param string $tag_list List of tags.
	 * @param string $before   String to use before the tags.
	 * @param string $sep      String to use between the tags.
	 * @param string $after    String to use after the tags.
	 * @param int    $post_id  Post ID.
	 */
	return apply_filters( 'the_tags', $tag_list, $before, $sep, $after, $post_id );
}

/**
 * Displays the tags for a post.
 *
 * @since 2.3.0
 *
 * @param string $before Optional. String to use before the tags. Defaults to 'Tags:'.
 * @param string $sep    Optional. String to use between the tags. Default ', '.
 * @param string $after  Optional. String to use after the tags. Default empty.
 */
function the_tags( $before = null, $sep = ', ', $after = '' ) {
	if ( null === $before ) {
		$before = __( 'Tags: ' );
	}

	$the_tags = get_the_tag_list( $before, $sep, $after );

	if ( ! is_wp_error( $the_tags ) ) {
		echo $the_tags;
	}
}

/**
 * Retrieves tag description.
 *
 * @since 2.8.0
 *
 * @param int $tag Optional. Tag ID. Defaults to the current tag ID.
 * @return string Tag description, if available.
 */
function tag_description( $tag = 0 ) {
	return term_description( $tag );
}

/**
 * Retrieves term description.
 *
 * @since 2.8.0
 * @since 4.9.2 The `$taxonomy` parameter was deprecated.
 *
 * @param int  $term       Optional. Term ID. Defaults to the current term ID.
 * @param null $deprecated Deprecated. Not used.
 * @return string Term description, if available.
 */
function term_description( $term = 0, $deprecated = null ) {
	if ( ! $term && ( is_tax() || is_tag() || is_category() ) ) {
		$term = get_queried_object();
		if ( $term ) {
			$term = $term->term_id;
		}
	}

	$description = get_term_field( 'description', $term );

	return is_wp_error( $description ) ? '' : $description;
}

/**
 * Retrieves the terms of the taxonomy that are attached to the post.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post     Post ID or object.
 * @param string      $taxonomy Taxonomy name.
 * @return WP_Term[]|false|WP_Error Array of WP_Term objects on success, false if there are no terms
 *                                  or the post does not exist, WP_Error on failure.
 */
function get_the_terms( $post, $taxonomy ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$terms = get_object_term_cache( $post->ID, $taxonomy );

	if ( false === $terms ) {
		$terms = wp_get_object_terms( $post->ID, $taxonomy );
		if ( ! is_wp_error( $terms ) ) {
			$term_ids = wp_list_pluck( $terms, 'term_id' );
			wp_cache_add( $post->ID, $term_ids, $taxonomy . '_relationships' );
		}
	}

	/**
	 * Filters the list of terms attached to the given post.
	 *
	 * @since 3.1.0
	 *
	 * @param WP_Term[]|WP_Error $terms    Array of attached terms, or WP_Error on failure.
	 * @param int                $post_id  Post ID.
	 * @param string             $taxonomy Name of the taxonomy.
	 */
	$terms = apply_filters( 'get_the_terms', $terms, $post->ID, $taxonomy );

	if ( empty( $terms ) ) {
		return false;
	}

	return $terms;
}

/**
 * Retrieves a post's terms as a list with specified format.
 *
 * Terms are linked to their respective term listing pages.
 *
 * @since 2.5.0
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy name.
 * @param string $before   Optional. String to use before the terms. Default empty.
 * @param string $sep      Optional. String to use between the terms. Default empty.
 * @param string $after    Optional. String to use after the terms. Default empty.
 * @return string|false|WP_Error A list of terms on success, false if there are no terms,
 *                               WP_Error on failure.
 */
function get_the_term_list( $post_id, $taxonomy, $before = '', $sep = '', $after = '' ) {
	$terms = get_the_terms( $post_id, $taxonomy );

	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	if ( empty( $terms ) ) {
		return false;
	}

	$links = array();

	foreach ( $terms as $term ) {
		$link = get_term_link( $term, $taxonomy );
		if ( is_wp_error( $link ) ) {
			return $link;
		}
		$links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . $term->name . '</a>';
	}

	/**
	 * Filters the term links for a given taxonomy.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers
	 * to the taxonomy slug.
	 *
	 * Possible hook names include:
	 *
	 *  - `term_links-category`
	 *  - `term_links-post_tag`
	 *  - `term_links-post_format`
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $links An array of term links.
	 */
	$term_links = apply_filters( "term_links-{$taxonomy}", $links );  // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

	return $before . implode( $sep, $term_links ) . $after;
}

/**
 * Retrieves term parents with separator.
 *
 * @since 4.8.0
 *
 * @param int          $term_id  Term ID.
 * @param string       $taxonomy Taxonomy name.
 * @param string|array $args {
 *     Array of optional arguments.
 *
 *     @type string $format    Use term names or slugs for display. Accepts 'name' or 'slug'.
 *                             Default 'name'.
 *     @type string $separator Separator for between the terms. Default '/'.
 *     @type bool   $link      Whether to format as a link. Default true.
 *     @type bool   $inclusive Include the term to get the parents for. Default true.
 * }
 * @return string|WP_Error A list of term parents on success, WP_Error or empty string on failure.
 */
function get_term_parents_list( $term_id, $taxonomy, $args = array() ) {
	$list = '';
	$term = get_term( $term_id, $taxonomy );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( ! $term ) {
		return $list;
	}

	$term_id = $term->term_id;

	$defaults = array(
		'format'    => 'name',
		'separator' => '/',
		'link'      => true,
		'inclusive' => true,
	);

	$args = wp_parse_args( $args, $defaults );

	foreach ( array( 'link', 'inclusive' ) as $bool ) {
		$args[ $bool ] = wp_validate_boolean( $args[ $bool ] );
	}

	$parents = get_ancestors( $term_id, $taxonomy, 'taxonomy' );

	if ( $args['inclusive'] ) {
		array_unshift( $parents, $term_id );
	}

	foreach ( array_reverse( $parents ) as $term_id ) {
		$parent = get_term( $term_id, $taxonomy );
		$name   = ( 'slug' === $args['format'] ) ? $parent->slug : $parent->name;

		if ( $args['link'] ) {
			$list .= '<a href="' . esc_url( get_term_link( $parent->term_id, $taxonomy ) ) . '">' . $name . '</a>' . $args['separator'];
		} else {
			$list .= $name . $args['separator'];
		}
	}

	return $list;
}

/**
 * Displays the terms for a post in a list.
 *
 * @since 2.5.0
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy name.
 * @param string $before   Optional. String to use before the terms. Default empty.
 * @param string $sep      Optional. String to use between the terms. Default ', '.
 * @param string $after    Optional. String to use after the terms. Default empty.
 * @return void|false Void on success, false on failure.
 */
function the_terms( $post_id, $taxonomy, $before = '', $sep = ', ', $after = '' ) {
	$term_list = get_the_term_list( $post_id, $taxonomy, $before, $sep, $after );

	if ( is_wp_error( $term_list ) ) {
		return false;
	}

	/**
	 * Filters the list of terms to display.
	 *
	 * @since 2.9.0
	 *
	 * @param string $term_list List of terms to display.
	 * @param string $taxonomy  The taxonomy name.
	 * @param string $before    String to use before the terms.
	 * @param string $sep       String to use between the terms.
	 * @param string $after     String to use after the terms.
	 */
	echo apply_filters( 'the_terms', $term_list, $taxonomy, $before, $sep, $after );
}

/**
 * Checks if the current post has any of given category.
 *
 * The given categories are checked against the post's categories' term_ids, names and slugs.
 * Categories given as integers will only be checked against the post's categories' term_ids.
 *
 * If no categories are given, determines if post has any categories.
 *
 * @since 3.1.0
 *
 * @param string|int|array $category Optional. The category name/term_id/slug,
 *                                   or an array of them to check for. Default empty.
 * @param int|WP_Post      $post     Optional. Post to check. Defaults to the current post.
 * @return bool True if the current post has any of the given categories
 *              (or any category, if no category specified). False otherwise.
 */
function has_category( $category = '', $post = null ) {
	return has_term( $category, 'category', $post );
}

/**
 * Checks if the current post has any of given tags.
 *
 * The given tags are checked against the post's tags' term_ids, names and slugs.
 * Tags given as integers will only be checked against the post's tags' term_ids.
 *
 * If no tags are given, determines if post has any tags.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.6.0
 * @since 2.7.0 Tags given as integers are only checked against
 *              the post's tags' term_ids, not names or slugs.
 * @since 2.7.0 Can be used outside of the WordPress Loop if `$post` is provided.
 *
 * @param string|int|array $tag  Optional. The tag name/term_id/slug,
 *                               or an array of them to check for. Default empty.
 * @param int|WP_Post      $post Optional. Post to check. Defaults to the current post.
 * @return bool True if the current post has any of the given tags
 *              (or any tag, if no tag specified). False otherwise.
 */
function has_tag( $tag = '', $post = null ) {
	return has_term( $tag, 'post_tag', $post );
}

/**
 * Checks if the current post has any of given terms.
 *
 * The given terms are checked against the post's terms' term_ids, names and slugs.
 * Terms given as integers will only be checked against the post's terms' term_ids.
 *
 * If no terms are given, determines if post has any terms.
 *
 * @since 3.1.0
 *
 * @param string|int|array $term     Optional. The term name/term_id/slug,
 *                                   or an array of them to check for. Default empty.
 * @param string           $taxonomy Optional. Taxonomy name. Default empty.
 * @param int|WP_Post      $post     Optional. Post to check. Defaults to the current post.
 * @return bool True if the current post has any of the given terms
 *              (or any term, if no term specified). False otherwise.
 */
function has_term( $term = '', $taxonomy = '', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$r = is_object_in_term( $post->ID, $taxonomy, $term );
	if ( is_wp_error( $r ) ) {
		return false;
	}

	return $r;
}
