<?php
/**
 * API Quản trị Taxonomy WordPress.
 *
 * @package WordPress
 * @subpackage Administration
 */

//
// Chuyên mục.
//

/**
 * Kiểm tra xem một chuyên mục có tồn tại hay không.
 *
 * @since 2.0.0
 *
 * @see term_exists()
 *
 * @param int|string $cat_name        Tên chuyên mục.
 * @param int        $category_parent Tùy chọn. ID của chuyên mục cha.
 * @return string|null Trả về ID chuyên mục dưới dạng chuỗi số nếu cặp tồn tại, null nếu không.
 */
function category_exists( $cat_name, $category_parent = null ) {
	$id = term_exists( $cat_name, 'category', $category_parent );
	if ( is_array( $id ) ) {
		$id = $id['term_id'];
	}
	return $id;
}

/**
 * Lấy đối tượng chuyên mục theo ID và ngữ cảnh bộ lọc 'edit'.
 *
 * @since 2.0.0
 *
 * @param int $id
 * @return object
 */
function get_category_to_edit( $id ) {
	$category = get_term( $id, 'category', OBJECT, 'edit' );
	_make_cat_compat( $category );
	return $category;
}

/**
 * Thêm chuyên mục mới vào cơ sở dữ liệu nếu chưa tồn tại.
 *
 * @since 2.0.0
 *
 * @param int|string $cat_name        Tên chuyên mục.
 * @param int        $category_parent Tùy chọn. ID của chuyên mục cha.
 * @return int|WP_Error
 */
function wp_create_category( $cat_name, $category_parent = 0 ) {
	$id = category_exists( $cat_name, $category_parent );
	if ( $id ) {
		return $id;
	}

	return wp_insert_category(
		array(
			'cat_name'        => $cat_name,
			'category_parent' => $category_parent,
		)
	);
}

/**
 * Tạo các chuyên mục cho bài viết đã cho.
 *
 * @since 2.0.0
 *
 * @param string[] $categories Mảng tên chuyên mục cần tạo.
 * @param int      $post_id    Tùy chọn. ID bài viết. Mặc định rỗng.
 * @return int[] Mảng các ID chuyên mục được gán cho bài viết đã cho.
 */
function wp_create_categories( $categories, $post_id = '' ) {
	$cat_ids = array();
	foreach ( $categories as $category ) {
		$id = category_exists( $category );
		if ( $id ) {
			$cat_ids[] = $id;
		} else {
			$id = wp_create_category( $category );
			if ( $id ) {
				$cat_ids[] = $id;
			}
		}
	}

	if ( $post_id ) {
		wp_set_post_categories( $post_id, $cat_ids );
	}

	return $cat_ids;
}

/**
 * Cập nhật một Chuyên mục đã tồn tại hoặc tạo Chuyên mục mới.
 *
 * @since 2.0.0
 * @since 2.5.0 Thêm tham số $wp_error.
 * @since 3.0.0 Thêm đối số 'taxonomy'.
 *
 * @param array $catarr {
 *     Mảng đối số để thêm chuyên mục mới.
 *
 *     @type int        $cat_ID               ID chuyên mục. Giá trị khác 0 sẽ cập nhật chuyên mục đã tồn tại.
 *                                            Mặc định 0.
 *     @type string     $taxonomy             Slug taxonomy. Mặc định 'category'.
 *     @type string     $cat_name             Tên chuyên mục. Mặc định rỗng.
 *     @type string     $category_description Mô tả chuyên mục. Mặc định rỗng.
 *     @type string     $category_nicename    Tên hiển thị đẹp của chuyên mục. Mặc định rỗng.
 *     @type int|string $category_parent      ID chuyên mục cha. Mặc định rỗng.
 * }
 * @param bool  $wp_error Tùy chọn. Mặc định false.
 * @return int|WP_Error Số ID của Chuyên mục mới hoặc đã cập nhật khi thành công. 0 hoặc WP_Error khi thất bại,
 *                      tùy thuộc vào tham số `$wp_error`.
 */
function wp_insert_category( $catarr, $wp_error = false ) {
	$cat_defaults = array(
		'cat_ID'               => 0,
		'taxonomy'             => 'category',
		'cat_name'             => '',
		'category_description' => '',
		'category_nicename'    => '',
		'category_parent'      => '',
	);
	$catarr       = wp_parse_args( $catarr, $cat_defaults );

	if ( '' === trim( $catarr['cat_name'] ) ) {
		if ( ! $wp_error ) {
			return 0;
		} else {
			return new WP_Error( 'cat_name', __( 'You did not enter a category name.' ) );
		}
	}

	$catarr['cat_ID'] = (int) $catarr['cat_ID'];

	// Đang cập nhật hay tạo mới?
	$update = ! empty( $catarr['cat_ID'] );

	$name        = $catarr['cat_name'];
	$description = $catarr['category_description'];
	$slug        = $catarr['category_nicename'];
	$parent      = (int) $catarr['category_parent'];
	if ( $parent < 0 ) {
		$parent = 0;
	}

	if ( empty( $parent )
		|| ! term_exists( $parent, $catarr['taxonomy'] )
		|| ( $catarr['cat_ID'] && term_is_ancestor_of( $catarr['cat_ID'], $parent, $catarr['taxonomy'] ) ) ) {
		$parent = 0;
	}

	$args = compact( 'name', 'slug', 'parent', 'description' );

	if ( $update ) {
		$catarr['cat_ID'] = wp_update_term( $catarr['cat_ID'], $catarr['taxonomy'], $args );
	} else {
		$catarr['cat_ID'] = wp_insert_term( $catarr['cat_name'], $catarr['taxonomy'], $args );
	}

	if ( is_wp_error( $catarr['cat_ID'] ) ) {
		if ( $wp_error ) {
			return $catarr['cat_ID'];
		} else {
			return 0;
		}
	}
	return $catarr['cat_ID']['term_id'];
}

/**
 * Alias của wp_insert_category() với đối số tối thiểu.
 *
 * Nếu bạn muốn chỉ cập nhật một số trường của chuyên mục đã tồn tại, gọi hàm này
 * chỉ với các giá trị mới được đặt bên trong $catarr.
 *
 * @since 2.0.0
 *
 * @param array $catarr Giá trị 'cat_ID' là bắt buộc. Tất cả các khóa khác là tùy chọn.
 * @return int|false Số ID của Chuyên mục mới hoặc đã cập nhật khi thành công. 0 hoặc FALSE khi thất bại.
 */
function wp_update_category( $catarr ) {
	$cat_id = (int) $catarr['cat_ID'];

	if ( isset( $catarr['category_parent'] ) && ( $cat_id === (int) $catarr['category_parent'] ) ) {
		return false;
	}

	// Đầu tiên, lấy tất cả các trường gốc.
	$category = get_term( $cat_id, 'category', ARRAY_A );
	_make_cat_compat( $category );

	// Escape dữ liệu lấy từ DB.
	$category = wp_slash( $category );

	// Gộp các trường cũ và mới, trường mới ghi đè trường cũ.
	$catarr = array_merge( $category, $catarr );

	return wp_insert_category( $catarr );
}

//
// Thẻ.
//

/**
 * Kiểm tra xem thẻ bài viết với tên đã cho có tồn tại hay không.
 *
 * @since 2.3.0
 *
 * @param int|string $tag_name
 * @return mixed Trả về null nếu term không tồn tại.
 *               Trả về mảng gồm term ID và term taxonomy ID nếu cặp tồn tại.
 *               Trả về 0 nếu term ID 0 được truyền vào hàm.
 */
function tag_exists( $tag_name ) {
	return term_exists( $tag_name, 'post_tag' );
}

/**
 * Thêm thẻ mới vào cơ sở dữ liệu nếu chưa tồn tại.
 *
 * @since 2.3.0
 *
 * @param int|string $tag_name
 * @return array|WP_Error
 */
function wp_create_tag( $tag_name ) {
	return wp_create_term( $tag_name, 'post_tag' );
}

/**
 * Lấy danh sách thẻ phân cách bằng dấu phẩy có sẵn để chỉnh sửa.
 *
 * @since 2.3.0
 *
 * @param int    $post_id
 * @param string $taxonomy Tùy chọn. Taxonomy để lấy các term. Mặc định 'post_tag'.
 * @return string|false|WP_Error
 */
function get_tags_to_edit( $post_id, $taxonomy = 'post_tag' ) {
	return get_terms_to_edit( $post_id, $taxonomy );
}

/**
 * Lấy danh sách term phân cách bằng dấu phẩy có sẵn để chỉnh sửa cho ID bài viết đã cho.
 *
 * @since 2.8.0
 *
 * @param int    $post_id
 * @param string $taxonomy Tùy chọn. Taxonomy để lấy các term. Mặc định 'post_tag'.
 * @return string|false|WP_Error
 */
function get_terms_to_edit( $post_id, $taxonomy = 'post_tag' ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return false;
	}

	$terms = get_object_term_cache( $post_id, $taxonomy );
	if ( false === $terms ) {
		$terms = wp_get_object_terms( $post_id, $taxonomy );
		wp_cache_add( $post_id, wp_list_pluck( $terms, 'term_id' ), $taxonomy . '_relationships' );
	}

	if ( ! $terms ) {
		return false;
	}
	if ( is_wp_error( $terms ) ) {
		return $terms;
	}
	$term_names = array();
	foreach ( $terms as $term ) {
		$term_names[] = $term->name;
	}

	$terms_to_edit = esc_attr( implode( ',', $term_names ) );

	/**
	 * Lọc danh sách term phân cách bằng dấu phẩy có sẵn để chỉnh sửa.
	 *
	 * @since 2.8.0
	 *
	 * @see get_terms_to_edit()
	 *
	 * @param string $terms_to_edit Danh sách tên term phân cách bằng dấu phẩy.
	 * @param string $taxonomy      Tên taxonomy để lấy các term.
	 */
	$terms_to_edit = apply_filters( 'terms_to_edit', $terms_to_edit, $taxonomy );

	return $terms_to_edit;
}

/**
 * Thêm term mới vào cơ sở dữ liệu nếu chưa tồn tại.
 *
 * @since 2.8.0
 *
 * @param string $tag_name Tên term.
 * @param string $taxonomy Tùy chọn. Taxonomy để tạo term trong đó. Mặc định 'post_tag'.
 * @return array|WP_Error
 */
function wp_create_term( $tag_name, $taxonomy = 'post_tag' ) {
	$id = term_exists( $tag_name, $taxonomy );
	if ( $id ) {
		return $id;
	}

	return wp_insert_term( $tag_name, $taxonomy );
}
