<?php
/**
 * API Taxonomy: Chức năng cốt lõi dành riêng cho chuyên mục
 *
 * @package WordPress
 * @subpackage Taxonomy
 */

/**
 * Lấy danh sách các đối tượng chuyên mục.
 *
 * Nếu bạn đặt tham số 'taxonomy' thành 'link_category', các chuyên mục liên kết
 * sẽ được trả về thay thế.
 *
 * @since 2.1.0
 *
 * @see get_terms() Loại tham số có thể thay đổi.
 *
 * @param string|array $args {
 *     Tùy chọn. Các tham số để lấy chuyên mục. Xem get_terms() để biết thêm tùy chọn.
 *
 *     @type string $taxonomy Taxonomy để lấy các term. Mặc định 'category'.
 * }
 * @return array Danh sách các đối tượng chuyên mục.
 */
function get_categories( $args = '' ) {
	$defaults = array( 'taxonomy' => 'category' );
	$args     = wp_parse_args( $args, $defaults );

	/**
	 * Lọc taxonomy được sử dụng để lấy các term khi gọi get_categories().
	 *
	 * @since 2.7.0
	 *
	 * @param string $taxonomy Taxonomy để lấy các term.
	 * @param array  $args     Mảng các tham số. Xem get_terms().
	 */
	$args['taxonomy'] = apply_filters( 'get_categories_taxonomy', $args['taxonomy'], $args );

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

	$categories = get_terms( $args );

	if ( is_wp_error( $categories ) ) {
		$categories = array();
	} else {
		$categories = (array) $categories;
		foreach ( array_keys( $categories ) as $k ) {
			_make_cat_compat( $categories[ $k ] );
		}
	}

	return $categories;
}

/**
 * Lấy dữ liệu chuyên mục dựa trên ID chuyên mục hoặc đối tượng chuyên mục.
 *
 * Nếu bạn truyền tham số $category một đối tượng, nó được giả định là đối tượng
 * hàng chuyên mục lấy từ cơ sở dữ liệu. Nó sẽ cache dữ liệu chuyên mục.
 *
 * Nếu bạn truyền $category một số nguyên của ID chuyên mục, thì chuyên mục đó sẽ
 * được lấy từ cơ sở dữ liệu, nếu nó chưa được cache, và trả về.
 *
 * Nếu bạn xem get_term(), thì cả hai loại sẽ được truyền qua nhiều bộ lọc
 * và cuối cùng được làm sạch dựa trên giá trị tham số $filter.
 *
 * @since 1.5.1
 *
 * @param int|object $category ID chuyên mục hoặc đối tượng hàng chuyên mục.
 * @param string     $output   Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                             tương ứng với đối tượng WP_Term, mảng liên kết, hoặc mảng số.
 *                             Mặc định OBJECT.
 * @param string     $filter   Tùy chọn. Cách làm sạch các trường chuyên mục. Mặc định 'raw'.
 * @return WP_Term|array|WP_Error|null Dữ liệu chuyên mục theo kiểu được định nghĩa bởi tham số $output.
 *                                     Trả về đối tượng WP_Term với các bí danh thuộc tính tương thích ngược được điền.
 *                                     WP_Error nếu $category rỗng, null nếu không tồn tại.
 */
function get_category( $category, $output = OBJECT, $filter = 'raw' ) {
	$category = get_term( $category, 'category', $output, $filter );

	if ( is_wp_error( $category ) ) {
		return $category;
	}

	_make_cat_compat( $category );

	return $category;
}

/**
 * Lấy chuyên mục dựa trên URL chứa slug chuyên mục.
 *
 * Tách tham số $category_path để lấy slug chuyên mục.
 *
 * Cố gắng tìm đường dẫn con và sẽ trả về nó. Nếu không tìm thấy kết quả
 * khớp, thì nó sẽ trả về chuyên mục đầu tiên khớp slug, nếu $full_match
 * được đặt thành false. Nếu không, thì nó sẽ trả về null.
 *
 * Cũng có thể nó sẽ trả về đối tượng WP_Error khi thất bại. Hãy kiểm tra
 * khi sử dụng hàm này.
 *
 * @since 2.1.0
 *
 * @param string $category_path URL chứa các slug chuyên mục.
 * @param bool   $full_match    Tùy chọn. Có nên khớp đường dẫn đầy đủ hay không.
 * @param string $output        Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                              tương ứng với đối tượng WP_Term, mảng liên kết, hoặc mảng số.
 *                              Mặc định OBJECT.
 * @return WP_Term|array|WP_Error|null Kiểu dựa trên giá trị $output.
 */
function get_category_by_path( $category_path, $full_match = true, $output = OBJECT ) {
	$category_path  = rawurlencode( urldecode( $category_path ) );
	$category_path  = str_replace( '%2F', '/', $category_path );
	$category_path  = str_replace( '%20', ' ', $category_path );
	$category_paths = '/' . trim( $category_path, '/' );
	$leaf_path      = sanitize_title( basename( $category_paths ) );
	$category_paths = explode( '/', $category_paths );
	$full_path      = '';

	foreach ( (array) $category_paths as $pathdir ) {
		$full_path .= ( '' !== $pathdir ? '/' : '' ) . sanitize_title( $pathdir );
	}

	$categories = get_terms(
		array(
			'taxonomy' => 'category',
			'get'      => 'all',
			'slug'     => $leaf_path,
		)
	);

	if ( empty( $categories ) ) {
		return;
	}

	foreach ( $categories as $category ) {
		$path        = '/' . $leaf_path;
		$curcategory = $category;

		while ( ( 0 !== $curcategory->parent ) && ( $curcategory->parent !== $curcategory->term_id ) ) {
			$curcategory = get_term( $curcategory->parent, 'category' );

			if ( is_wp_error( $curcategory ) ) {
				return $curcategory;
			}

			$path = '/' . $curcategory->slug . $path;
		}

		if ( $path === $full_path ) {
			$category = get_term( $category->term_id, 'category', $output );
			_make_cat_compat( $category );

			return $category;
		}
	}

	// Nếu không yêu cầu khớp đầy đủ, trả về chuyên mục đầu tiên khớp với nút lá.
	if ( ! $full_match ) {
		$category = get_term( reset( $categories )->term_id, 'category', $output );
		_make_cat_compat( $category );

		return $category;
	}
}

/**
 * Lấy đối tượng chuyên mục theo slug chuyên mục.
 *
 * @since 2.3.0
 *
 * @param string $slug Slug của chuyên mục.
 * @return object|false Đối tượng dữ liệu chuyên mục khi thành công, false nếu không tìm thấy.
 */
function get_category_by_slug( $slug ) {
	$category = get_term_by( 'slug', $slug, 'category' );

	if ( $category ) {
		_make_cat_compat( $category );
	}

	return $category;
}

/**
 * Lấy ID của chuyên mục từ tên của nó.
 *
 * @since 1.0.0
 *
 * @param string $cat_name Tên chuyên mục.
 * @return int ID chuyên mục khi thành công, 0 nếu chuyên mục không tồn tại.
 */
function get_cat_ID( $cat_name ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$cat = get_term_by( 'name', $cat_name, 'category' );

	if ( $cat ) {
		return $cat->term_id;
	}

	return 0;
}

/**
 * Lấy tên của chuyên mục từ ID của nó.
 *
 * @since 1.0.0
 *
 * @param int $cat_id ID chuyên mục.
 * @return string Tên chuyên mục, hoặc chuỗi rỗng nếu chuyên mục không tồn tại.
 */
function get_cat_name( $cat_id ) {
	$cat_id   = (int) $cat_id;
	$category = get_term( $cat_id, 'category' );

	if ( ! $category || is_wp_error( $category ) ) {
		return '';
	}

	return $category->name;
}

/**
 * Kiểm tra xem một chuyên mục có phải là tổ tiên của chuyên mục khác không.
 *
 * Bạn có thể sử dụng ID hoặc đối tượng chuyên mục cho cả hai tham số.
 * Nếu bạn sử dụng số nguyên, chuyên mục sẽ được lấy ra.
 *
 * @since 2.1.0
 *
 * @param int|object $cat1 ID hoặc đối tượng để kiểm tra xem đây có phải là chuyên mục cha.
 * @param int|object $cat2 Chuyên mục con.
 * @return bool Liệu $cat2 có phải là con của $cat1 hay không.
 */
function cat_is_ancestor_of( $cat1, $cat2 ) {
	return term_is_ancestor_of( $cat1, $cat2, 'category' );
}

/**
 * Làm sạch dữ liệu chuyên mục dựa trên ngữ cảnh.
 *
 * @since 2.3.0
 *
 * @param object|array $category Dữ liệu chuyên mục.
 * @param string       $context  Tùy chọn. Mặc định 'display'.
 * @return object|array Cùng kiểu với $category với dữ liệu đã được làm sạch để sử dụng an toàn.
 */
function sanitize_category( $category, $context = 'display' ) {
	return sanitize_term( $category, 'category', $context );
}

/**
 * Làm sạch dữ liệu trong trường khóa đơn lẻ của chuyên mục.
 *
 * @since 2.3.0
 *
 * @param string $field   Khóa chuyên mục cần làm sạch.
 * @param mixed  $value   Giá trị chuyên mục cần làm sạch.
 * @param int    $cat_id  ID chuyên mục.
 * @param string $context Bộ lọc nào sẽ sử dụng, 'raw', 'display', v.v.
 * @return mixed Giá trị sau khi $value đã được làm sạch.
 */
function sanitize_category_field( $field, $value, $cat_id, $context ) {
	return sanitize_term_field( $field, $value, $cat_id, 'category', $context );
}

/* Thẻ */

/**
 * Lấy tất cả thẻ bài viết.
 *
 * @since 2.3.0
 *
 * @param string|array $args {
 *     Tùy chọn. Các tham số để lấy thẻ. Xem get_terms() để biết thêm tùy chọn.
 *
 *     @type string $taxonomy Taxonomy để lấy các term. Mặc định 'post_tag'.
 * }
 * @return WP_Term[]|int|WP_Error Mảng các đối tượng term 'post_tag', số lượng của chúng,
 *                                hoặc WP_Error nếu bất kỳ taxonomy nào không tồn tại.
 */
function get_tags( $args = '' ) {
	$defaults = array( 'taxonomy' => 'post_tag' );
	$args     = wp_parse_args( $args, $defaults );

	$tags = get_terms( $args );

	if ( empty( $tags ) ) {
		$tags = array();
	} else {
		/**
		 * Lọc mảng các đối tượng term được trả về cho taxonomy 'post_tag'.
		 *
		 * @since 2.3.0
		 *
		 * @param WP_Term[]|int|WP_Error $tags Mảng các đối tượng term 'post_tag', số lượng của chúng,
		 *                                     hoặc WP_Error nếu bất kỳ taxonomy nào không tồn tại.
		 * @param array                  $args Mảng các tham số. Xem {@see get_terms()}.
		 */
		$tags = apply_filters( 'get_tags', $tags, $args );
	}

	return $tags;
}

/**
 * Lấy thẻ bài viết theo ID thẻ hoặc đối tượng thẻ.
 *
 * Nếu bạn truyền tham số $tag một đối tượng, nó được giả định là đối tượng hàng thẻ
 * được lấy từ cơ sở dữ liệu, nó sẽ cache dữ liệu thẻ.
 *
 * Nếu bạn truyền $tag một số nguyên của ID thẻ, thì thẻ đó sẽ được lấy
 * từ cơ sở dữ liệu, nếu nó chưa được cache, và trả về.
 *
 * Nếu bạn xem get_term(), cả hai loại sẽ được truyền qua nhiều bộ lọc
 * và cuối cùng được làm sạch dựa trên giá trị tham số $filter.
 *
 * @since 2.3.0
 *
 * @param int|WP_Term|object $tag    ID thẻ hoặc đối tượng thẻ.
 * @param string             $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                                   tương ứng với đối tượng WP_Term, mảng liên kết, hoặc mảng số.
 *                                   Mặc định OBJECT.
 * @param string             $filter Tùy chọn. Cách làm sạch các trường thẻ. Mặc định 'raw'.
 * @return WP_Term|array|WP_Error|null Dữ liệu thẻ theo kiểu được định nghĩa bởi tham số $output.
 *                                     WP_Error nếu $tag rỗng, null nếu không tồn tại.
 */
function get_tag( $tag, $output = OBJECT, $filter = 'raw' ) {
	return get_term( $tag, 'post_tag', $output, $filter );
}

/* Bộ nhớ đệm */

/**
 * Xóa dữ liệu cache chuyên mục dựa trên ID.
 *
 * @since 2.1.0
 *
 * @param int $id ID chuyên mục
 */
function clean_category_cache( $id ) {
	clean_term_cache( $id, 'category' );
}

/**
 * Cập nhật cấu trúc chuyên mục sang cấu trúc cũ trước phiên bản 2.3 từ cấu trúc taxonomy mới.
 *
 * Hàm này được thêm vào để hỗ trợ taxonomy cập nhật cấu trúc chuyên mục mới
 * với cấu trúc chuyên mục cũ. Điều này sẽ duy trì tương thích với
 * các plugin và theme phụ thuộc vào tên khóa hoặc thuộc tính cũ.
 *
 * Tham số chỉ nên được truyền một biến và không nên tạo mảng hoặc đối tượng
 * trực tiếp trong tham số. Lý do là tham số được truyền theo tham chiếu
 * và PHP sẽ thất bại trừ khi nó có biến.
 *
 * Không có giá trị trả về, vì mọi thứ được cập nhật trên biến bạn
 * truyền vào. Đây là một trong những tính năng khi sử dụng truyền theo tham chiếu trong PHP.
 *
 * @since 2.3.0
 * @since 4.4.0 Tham số `$category` giờ cũng chấp nhận đối tượng WP_Term.
 * @access private
 *
 * @param array|object|WP_Term $category Đối tượng hàng chuyên mục hoặc mảng.
 */
function _make_cat_compat( &$category ) {
	if ( is_object( $category ) && ! is_wp_error( $category ) ) {
		$category->cat_ID               = $category->term_id;
		$category->category_count       = $category->count;
		$category->category_description = $category->description;
		$category->cat_name             = $category->name;
		$category->category_nicename    = $category->slug;
		$category->category_parent      = $category->parent;
	} elseif ( is_array( $category ) && isset( $category['term_id'] ) ) {
		$category['cat_ID']               = &$category['term_id'];
		$category['category_count']       = &$category['count'];
		$category['category_description'] = &$category['description'];
		$category['cat_name']             = &$category['name'];
		$category['category_nicename']    = &$category['slug'];
		$category['category_parent']      = &$category['parent'];
	}
}
