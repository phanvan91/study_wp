<?php
/**
 * Các hàm mẫu liên kết WordPress.
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Hiển thị đường dẫn tĩnh cho bài viết hiện tại.
 *
 * @since 1.2.0
 * @since 4.4.0 Thêm tham số `$post`.
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 */
function the_permalink( $post = 0 ) {
	/**
	 * Lọc hiển thị đường dẫn tĩnh cho bài viết hiện tại.
	 *
	 * @since 1.5.0
	 * @since 4.4.0 Thêm tham số `$post`.
	 *
	 * @param string      $permalink Đường dẫn tĩnh cho bài viết hiện tại.
	 * @param int|WP_Post $post      ID bài viết, đối tượng WP_Post, hoặc 0. Mặc định 0.
	 */
	echo esc_url( apply_filters( 'the_permalink', get_permalink( $post ), $post ) );
}

/**
 * Lấy chuỗi có dấu gạch chéo cuối nếu trang web được thiết lập thêm dấu gạch chéo cuối.
 *
 * Thêm dấu gạch chéo cuối có điều kiện nếu cấu trúc đường dẫn tĩnh có dấu gạch chéo cuối,
 * loại bỏ dấu gạch chéo cuối nếu không. Chuỗi được truyền qua bộ lọc
 * {@see 'user_trailingslashit'}. Sẽ xóa dấu gạch chéo cuối khỏi chuỗi nếu
 * trang web không được thiết lập có chúng.
 *
 * @since 2.2.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $url         URL có hoặc không có dấu gạch chéo cuối.
 * @param string $type_of_url Tùy chọn. Loại URL đang được xem xét (ví dụ: single, category, v.v.)
 *                            để sử dụng trong bộ lọc. Mặc định chuỗi rỗng.
 * @return string URL với dấu gạch chéo cuối được thêm hoặc loại bỏ.
 */
function user_trailingslashit( $url, $type_of_url = '' ) {
	global $wp_rewrite;
	if ( $wp_rewrite->use_trailing_slashes ) {
		$url = trailingslashit( $url );
	} else {
		$url = untrailingslashit( $url );
	}

	/**
	 * Lọc chuỗi có dấu gạch chéo cuối, tùy thuộc vào việc trang web có được thiết lập sử dụng dấu gạch chéo cuối hay không.
	 *
	 * @since 2.2.0
	 *
	 * @param string $url         URL có hoặc không có dấu gạch chéo cuối.
	 * @param string $type_of_url Loại URL đang được xem xét. Chấp nhận 'single', 'single_trackback',
	 *                            'single_feed', 'single_paged', 'commentpaged', 'paged', 'home', 'feed',
	 *                            'category', 'page', 'year', 'month', 'day', 'post_type_archive'.
	 */
	return apply_filters( 'user_trailingslashit', $url, $type_of_url );
}

/**
 * Hiển thị neo đường dẫn tĩnh cho bài viết hiện tại.
 *
 * Chế độ title sẽ sử dụng tiêu đề bài viết cho thuộc tính 'id' của phần tử 'a'.
 * Chế độ id sử dụng 'post-' kèm ID bài viết cho thuộc tính 'id'.
 *
 * @since 0.71
 *
 * @param string $mode Tùy chọn. Chế độ đường dẫn tĩnh. Chấp nhận 'title' hoặc 'id'. Mặc định 'id'.
 */
function permalink_anchor( $mode = 'id' ) {
	$post = get_post();
	switch ( strtolower( $mode ) ) {
		case 'title':
			$title = sanitize_title( $post->post_title ) . '-' . $post->ID;
			echo '<a id="' . $title . '"></a>';
			break;
		case 'id':
		default:
			echo '<a id="post-' . $post->ID . '"></a>';
			break;
	}
}

/**
 * Xác định xem bài viết có nên luôn sử dụng cấu trúc đường dẫn tĩnh đơn giản hay không.
 *
 * @since 5.7.0
 *
 * @param WP_Post|int|null $post   Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục $post.
 * @param bool|null        $sample Tùy chọn. Có buộc xem xét dựa trên liên kết mẫu hay không.
 *                                 Nếu bỏ qua, liên kết mẫu được tạo nếu đối tượng bài viết được truyền
 *                                 với thuộc tính filter được đặt thành 'sample'.
 * @return bool Có sử dụng cấu trúc đường dẫn tĩnh đơn giản hay không.
 */
function wp_force_plain_post_permalink( $post = null, $sample = null ) {
	if (
		null === $sample &&
		is_object( $post ) &&
		isset( $post->filter ) &&
		'sample' === $post->filter
	) {
		$sample = true;
	} else {
		$post   = get_post( $post );
		$sample = null !== $sample ? $sample : false;
	}

	if ( ! $post ) {
		return true;
	}

	$post_status_obj = get_post_status_object( get_post_status( $post ) );
	$post_type_obj   = get_post_type_object( get_post_type( $post ) );

	if ( ! $post_status_obj || ! $post_type_obj ) {
		return true;
	}

	if (
		// Các liên kết xem công khai không bao giờ có đường dẫn tĩnh đơn giản.
		is_post_status_viewable( $post_status_obj ) ||
		(
			// Bài viết riêng tư không có đường dẫn tĩnh đơn giản nếu người dùng có thể đọc chúng.
			$post_status_obj->private &&
			current_user_can( 'read_post', $post->ID )
		) ||
		// Bài viết được bảo vệ không có liên kết đơn giản nếu đang lấy URL mẫu.
		( $post_status_obj->protected && $sample )
	) {
		return false;
	}

	return true;
}

/**
 * Lấy đường dẫn tĩnh đầy đủ cho bài viết hiện tại hoặc ID bài viết.
 *
 * Hàm này là bí danh cho get_permalink().
 *
 * @since 3.9.0
 *
 * @see get_permalink()
 *
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 * @param bool        $leavename Tùy chọn. Có giữ tên bài viết hoặc tên trang hay không. Mặc định false.
 * @return string|false URL đường dẫn tĩnh. False nếu bài viết không tồn tại.
 */
function get_the_permalink( $post = 0, $leavename = false ) {
	return get_permalink( $post, $leavename );
}

/**
 * Lấy đường dẫn tĩnh đầy đủ cho bài viết hiện tại hoặc ID bài viết.
 *
 * @since 1.0.0
 *
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 * @param bool        $leavename Tùy chọn. Có giữ tên bài viết hoặc tên trang hay không. Mặc định false.
 * @return string|false URL đường dẫn tĩnh. False nếu bài viết không tồn tại.
 */
function get_permalink( $post = 0, $leavename = false ) {
	$rewritecode = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		$leavename ? '' : '%postname%',
		'%post_id%',
		'%category%',
		'%author%',
		$leavename ? '' : '%pagename%',
	);

	if ( is_object( $post ) && isset( $post->filter ) && 'sample' === $post->filter ) {
		$sample = true;
	} else {
		$post   = get_post( $post );
		$sample = false;
	}

	if ( empty( $post->ID ) ) {
		return false;
	}

	if ( 'page' === $post->post_type ) {
		return get_page_link( $post, $leavename, $sample );
	} elseif ( 'attachment' === $post->post_type ) {
		return get_attachment_link( $post, $leavename );
	} elseif ( in_array( $post->post_type, get_post_types( array( '_builtin' => false ) ), true ) ) {
		return get_post_permalink( $post, $leavename, $sample );
	}

	$permalink = get_option( 'permalink_structure' );

	/**
	 * Lọc cấu trúc đường dẫn tĩnh cho bài viết trước khi thay thế token xảy ra.
	 *
	 * Chỉ áp dụng cho bài viết có post_type là 'post'.
	 *
	 * @since 3.0.0
	 *
	 * @param string  $permalink Cấu trúc đường dẫn tĩnh của trang web.
	 * @param WP_Post $post      Bài viết đang xét.
	 * @param bool    $leavename Có giữ tên bài viết hay không.
	 */
	$permalink = apply_filters( 'pre_post_link', $permalink, $post, $leavename );

	if (
		$permalink &&
		! wp_force_plain_post_permalink( $post )
	) {

		$category = '';
		if ( str_contains( $permalink, '%category%' ) ) {
			$cats = get_the_category( $post->ID );
			if ( $cats ) {
				$cats = wp_list_sort(
					$cats,
					array(
						'term_id' => 'ASC',
					)
				);

				/**
				 * Lọc chuyên mục được sử dụng trong token đường dẫn tĩnh %category%.
				 *
				 * @since 3.5.0
				 *
				 * @param WP_Term  $cat  Chuyên mục để sử dụng trong đường dẫn tĩnh.
				 * @param array    $cats Mảng tất cả chuyên mục (đối tượng WP_Term) liên kết với bài viết.
				 * @param WP_Post  $post Bài viết đang xét.
				 */
				$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );

				$category_object = get_term( $category_object, 'category' );
				$category        = $category_object->slug;
				if ( $category_object->parent ) {
					$category = get_category_parents( $category_object->parent, false, '/', true ) . $category;
				}
			}
			/*
			 * Hiển thị chuyên mục mặc định trong đường dẫn tĩnh,
			 * mà không cần phải gán nó một cách rõ ràng.
			 */
			if ( empty( $category ) ) {
				$default_category = get_term( get_option( 'default_category' ), 'category' );
				if ( $default_category && ! is_wp_error( $default_category ) ) {
					$category = $default_category->slug;
				}
			}
		}

		$author = '';
		if ( str_contains( $permalink, '%author%' ) ) {
			$authordata = get_userdata( $post->post_author );
			$author     = $authordata->user_nicename;
		}

		/*
		 * Đây không phải là lệnh gọi API vì đường dẫn tĩnh dựa trên giá trị post_date được lưu trữ,
		 * cần được phân tích cú pháp theo giờ địa phương bất kể múi giờ PHP mặc định.
		 */
		$date = explode( ' ', str_replace( array( '-', ':' ), ' ', $post->post_date ) );

		$rewritereplace = array(
			$date[0],
			$date[1],
			$date[2],
			$date[3],
			$date[4],
			$date[5],
			$post->post_name,
			$post->ID,
			$category,
			$author,
			$post->post_name,
		);

		$permalink = home_url( str_replace( $rewritecode, $rewritereplace, $permalink ) );
		$permalink = user_trailingslashit( $permalink, 'single' );

	} else { // Nếu họ không sử dụng tùy chọn đường dẫn tĩnh nâng cao.
		$permalink = home_url( '?p=' . $post->ID );
	}

	/**
	 * Lọc đường dẫn tĩnh cho bài viết.
	 *
	 * Chỉ áp dụng cho bài viết có post_type là 'post'.
	 *
	 * @since 1.5.0
	 *
	 * @param string  $permalink Đường dẫn tĩnh của bài viết.
	 * @param WP_Post $post      Bài viết đang xét.
	 * @param bool    $leavename Có giữ tên bài viết hay không.
	 */
	return apply_filters( 'post_link', $permalink, $post, $leavename );
}

/**
 * Lấy đường dẫn tĩnh cho bài viết thuộc loại bài viết tùy chỉnh.
 *
 * @since 3.0.0
 * @since 6.1.0 Trả về false nếu bài viết không tồn tại.
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 * @param bool        $leavename Tùy chọn. Có giữ tên bài viết hay không. Mặc định false.
 * @param bool        $sample    Tùy chọn. Có phải đường dẫn tĩnh mẫu hay không. Mặc định false.
 * @return string|false URL đường dẫn tĩnh của bài viết. False nếu bài viết không tồn tại.
 */
function get_post_permalink( $post = 0, $leavename = false, $sample = false ) {
	global $wp_rewrite;

	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$post_link = $wp_rewrite->get_extra_permastruct( $post->post_type );

	$slug = $post->post_name;

	$force_plain_link = wp_force_plain_post_permalink( $post );

	$post_type = get_post_type_object( $post->post_type );

	if ( $post_type->hierarchical ) {
		$slug = get_page_uri( $post );
	}

	if ( ! empty( $post_link ) && ( ! $force_plain_link || $sample ) ) {
		if ( ! $leavename ) {
			$post_link = str_replace( "%$post->post_type%", $slug, $post_link );
		}
		$post_link = home_url( user_trailingslashit( $post_link ) );
	} else {
		if ( $post_type->query_var && ( isset( $post->post_status ) && ! $force_plain_link ) ) {
			$post_link = add_query_arg( $post_type->query_var, $slug, '' );
		} else {
			$post_link = add_query_arg(
				array(
					'post_type' => $post->post_type,
					'p'         => $post->ID,
				),
				''
			);
		}
		$post_link = home_url( $post_link );
	}

	/**
	 * Lọc đường dẫn tĩnh cho bài viết thuộc loại bài viết tùy chỉnh.
	 *
	 * @since 3.0.0
	 *
	 * @param string  $post_link Đường dẫn tĩnh của bài viết.
	 * @param WP_Post $post      Bài viết đang xét.
	 * @param bool    $leavename Có giữ tên bài viết hay không.
	 * @param bool    $sample    Có phải đường dẫn tĩnh mẫu hay không.
	 */
	return apply_filters( 'post_type_link', $post_link, $post, $leavename, $sample );
}

/**
 * Lấy đường dẫn tĩnh cho trang hiện tại hoặc ID trang.
 *
 * Tôn trọng page_on_front. Sử dụng hàm này.
 *
 * @since 1.5.0
 *
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng. Mặc định sử dụng biến toàn cục `$post`.
 * @param bool        $leavename Tùy chọn. Có giữ tên trang hay không. Mặc định false.
 * @param bool        $sample    Tùy chọn. Có nên được xử lý như đường dẫn tĩnh mẫu hay không.
 *                               Mặc định false.
 * @return string Đường dẫn tĩnh của trang.
 */
function get_page_link( $post = false, $leavename = false, $sample = false ) {
	$post = get_post( $post );

	if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post->ID ) {
		$link = home_url( '/' );
	} else {
		$link = _get_page_link( $post, $leavename, $sample );
	}

	/**
	 * Lọc đường dẫn tĩnh cho trang.
	 *
	 * @since 1.5.0
	 *
	 * @param string $link    Đường dẫn tĩnh của trang.
	 * @param int    $post_id ID của trang.
	 * @param bool   $sample  Có phải đường dẫn tĩnh mẫu hay không.
	 */
	return apply_filters( 'page_link', $link, $post->ID, $sample );
}

/**
 * Lấy đường dẫn tĩnh của trang.
 *
 * Bỏ qua page_on_front. Chỉ sử dụng nội bộ.
 *
 * @since 2.1.0
 * @access private
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng. Mặc định sử dụng biến toàn cục `$post`.
 * @param bool        $leavename Tùy chọn. Có giữ tên trang hay không. Mặc định false.
 * @param bool        $sample    Tùy chọn. Có nên được xử lý như đường dẫn tĩnh mẫu hay không.
 *                               Mặc định false.
 * @return string Đường dẫn tĩnh của trang.
 */
function _get_page_link( $post = false, $leavename = false, $sample = false ) {
	global $wp_rewrite;

	$post = get_post( $post );

	$force_plain_link = wp_force_plain_post_permalink( $post );

	$link = $wp_rewrite->get_page_permastruct();

	if ( ! empty( $link ) && ( ( isset( $post->post_status ) && ! $force_plain_link ) || $sample ) ) {
		if ( ! $leavename ) {
			$link = str_replace( '%pagename%', get_page_uri( $post ), $link );
		}

		$link = home_url( $link );
		$link = user_trailingslashit( $link, 'page' );
	} else {
		$link = home_url( '?page_id=' . $post->ID );
	}

	/**
	 * Lọc đường dẫn tĩnh cho trang không phải page_on_front.
	 *
	 * @since 2.1.0
	 *
	 * @param string $link    Đường dẫn tĩnh của trang.
	 * @param int    $post_id ID của trang.
	 */
	return apply_filters( '_get_page_link', $link, $post->ID );
}

/**
 * Lấy đường dẫn tĩnh cho tệp đính kèm.
 *
 * Có thể được sử dụng trong Vòng lặp WordPress hoặc bên ngoài.
 *
 * @since 2.0.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng. Mặc định sử dụng biến toàn cục `$post`.
 * @param bool        $leavename Tùy chọn. Có giữ tên trang hay không. Mặc định false.
 * @return string Đường dẫn tĩnh của tệp đính kèm.
 */
function get_attachment_link( $post = null, $leavename = false ) {
	global $wp_rewrite;

	$link = false;

	$post             = get_post( $post );
	$force_plain_link = wp_force_plain_post_permalink( $post );
	$parent_id        = $post->post_parent;
	$parent           = $parent_id ? get_post( $parent_id ) : false;
	$parent_valid     = true; // Mặc định khi không có bài viết cha.
	if (
		$parent_id &&
		(
			$post->post_parent === $post->ID ||
			! $parent ||
			! is_post_type_viewable( get_post_type( $parent ) )
		)
	) {
		// Bài viết là cha của chính nó hoặc bài viết cha không khả dụng.
		$parent_valid = false;
	}

	if ( $force_plain_link || ! $parent_valid ) {
		$link = false;
	} elseif ( $wp_rewrite->using_permalinks() && $parent ) {
		if ( 'page' === $parent->post_type ) {
			$parentlink = _get_page_link( $post->post_parent ); // Bỏ qua page_on_front.
		} else {
			$parentlink = get_permalink( $post->post_parent );
		}

		if ( is_numeric( $post->post_name ) || str_contains( get_option( 'permalink_structure' ), '%category%' ) ) {
			$name = 'attachment/' . $post->post_name; // <permalink>/<int>/ là phân trang nên chúng ta sử dụng đánh dấu tệp đính kèm rõ ràng.
		} else {
			$name = $post->post_name;
		}

		if ( ! str_contains( $parentlink, '?' ) ) {
			$link = user_trailingslashit( trailingslashit( $parentlink ) . '%postname%' );
		}

		if ( ! $leavename ) {
			$link = str_replace( '%postname%', $name, $link );
		}
	} elseif ( $wp_rewrite->using_permalinks() && ! $leavename ) {
		$link = home_url( user_trailingslashit( $post->post_name ) );
	}

	if ( ! $link ) {
		$link = home_url( '/?attachment_id=' . $post->ID );
	}

	/**
	 * Lọc đường dẫn tĩnh cho tệp đính kèm.
	 *
	 * @since 2.0.0
	 * @since 5.6.0 Cung cấp chuỗi rỗng sẽ vô hiệu hóa
	 *              liên kết xem trang tệp đính kèm trên modal phương tiện.
	 *
	 * @param string $link    Đường dẫn tĩnh của tệp đính kèm.
	 * @param int    $post_id ID tệp đính kèm.
	 */
	return apply_filters( 'attachment_link', $link, $post->ID );
}

/**
 * Lấy đường dẫn tĩnh cho lưu trữ theo năm.
 *
 * @since 1.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|false $year Số nguyên của năm. False cho năm hiện tại.
 * @return string Đường dẫn tĩnh cho lưu trữ của năm được chỉ định.
 */
function get_year_link( $year ) {
	global $wp_rewrite;
	if ( ! $year ) {
		$year = current_time( 'Y' );
	}
	$yearlink = $wp_rewrite->get_year_permastruct();
	if ( ! empty( $yearlink ) ) {
		$yearlink = str_replace( '%year%', $year, $yearlink );
		$yearlink = home_url( user_trailingslashit( $yearlink, 'year' ) );
	} else {
		$yearlink = home_url( '?m=' . $year );
	}

	/**
	 * Lọc đường dẫn tĩnh lưu trữ theo năm.
	 *
	 * @since 1.5.0
	 *
	 * @param string $yearlink Đường dẫn tĩnh cho lưu trữ theo năm.
	 * @param int    $year     Năm cho lưu trữ.
	 */
	return apply_filters( 'year_link', $yearlink, $year );
}

/**
 * Lấy đường dẫn tĩnh cho lưu trữ theo tháng kèm năm.
 *
 * @since 1.0.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|false $year  Số nguyên của năm. False cho năm hiện tại.
 * @param int|false $month Số nguyên của tháng. False cho tháng hiện tại.
 * @return string Đường dẫn tĩnh cho lưu trữ của tháng và năm được chỉ định.
 */
function get_month_link( $year, $month ) {
	global $wp_rewrite;
	if ( ! $year ) {
		$year = current_time( 'Y' );
	}
	if ( ! $month ) {
		$month = current_time( 'm' );
	}
	$monthlink = $wp_rewrite->get_month_permastruct();
	if ( ! empty( $monthlink ) ) {
		$monthlink = str_replace( '%year%', $year, $monthlink );
		$monthlink = str_replace( '%monthnum%', zeroise( (int) $month, 2 ), $monthlink );
		$monthlink = home_url( user_trailingslashit( $monthlink, 'month' ) );
	} else {
		$monthlink = home_url( '?m=' . $year . zeroise( $month, 2 ) );
	}

	/**
	 * Lọc đường dẫn tĩnh lưu trữ theo tháng.
	 *
	 * @since 1.5.0
	 *
	 * @param string $monthlink Đường dẫn tĩnh cho lưu trữ theo tháng.
	 * @param int    $year      Năm cho lưu trữ.
	 * @param int    $month     Tháng cho lưu trữ.
	 */
	return apply_filters( 'month_link', $monthlink, $year, $month );
}

/**
 * Lấy đường dẫn tĩnh cho lưu trữ theo ngày kèm năm và tháng.
 *
 * @since 1.0.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|false $year  Số nguyên của năm. False cho năm hiện tại.
 * @param int|false $month Số nguyên của tháng. False cho tháng hiện tại.
 * @param int|false $day   Số nguyên của ngày. False cho ngày hiện tại.
 * @return string Đường dẫn tĩnh cho lưu trữ của ngày, tháng và năm được chỉ định.
 */
function get_day_link( $year, $month, $day ) {
	global $wp_rewrite;
	if ( ! $year ) {
		$year = current_time( 'Y' );
	}
	if ( ! $month ) {
		$month = current_time( 'm' );
	}
	if ( ! $day ) {
		$day = current_time( 'j' );
	}

	$daylink = $wp_rewrite->get_day_permastruct();
	if ( ! empty( $daylink ) ) {
		$daylink = str_replace( '%year%', $year, $daylink );
		$daylink = str_replace( '%monthnum%', zeroise( (int) $month, 2 ), $daylink );
		$daylink = str_replace( '%day%', zeroise( (int) $day, 2 ), $daylink );
		$daylink = home_url( user_trailingslashit( $daylink, 'day' ) );
	} else {
		$daylink = home_url( '?m=' . $year . zeroise( $month, 2 ) . zeroise( $day, 2 ) );
	}

	/**
	 * Lọc đường dẫn tĩnh lưu trữ theo ngày.
	 *
	 * @since 1.5.0
	 *
	 * @param string $daylink Đường dẫn tĩnh cho lưu trữ theo ngày.
	 * @param int    $year    Năm cho lưu trữ.
	 * @param int    $month   Tháng cho lưu trữ.
	 * @param int    $day     Ngày cho lưu trữ.
	 */
	return apply_filters( 'day_link', $daylink, $year, $month, $day );
}

/**
 * Hiển thị đường dẫn tĩnh cho loại nguồn cấp dữ liệu.
 *
 * @since 3.0.0
 *
 * @param string $anchor Văn bản neo của liên kết.
 * @param string $feed   Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                       Mặc định là giá trị của get_default_feed().
 */
function the_feed_link( $anchor, $feed = '' ) {
	$link = '<a href="' . esc_url( get_feed_link( $feed ) ) . '">' . $anchor . '</a>';

	/**
	 * Lọc thẻ neo liên kết nguồn cấp dữ liệu.
	 *
	 * @since 3.0.0
	 *
	 * @param string $link Thẻ neo hoàn chỉnh cho liên kết nguồn cấp dữ liệu.
	 * @param string $feed Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom',
	 *                     hoặc chuỗi rỗng cho loại nguồn cấp dữ liệu mặc định.
	 */
	echo apply_filters( 'the_feed_link', $link, $feed );
}

/**
 * Lấy đường dẫn tĩnh cho loại nguồn cấp dữ liệu.
 *
 * @since 1.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $feed Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                     Mặc định là giá trị của get_default_feed().
 * @return string Đường dẫn tĩnh nguồn cấp dữ liệu.
 */
function get_feed_link( $feed = '' ) {
	global $wp_rewrite;

	$permalink = $wp_rewrite->get_feed_permastruct();

	if ( $permalink ) {
		if ( str_contains( $feed, 'comments_' ) ) {
			$feed      = str_replace( 'comments_', '', $feed );
			$permalink = $wp_rewrite->get_comment_feed_permastruct();
		}

		if ( get_default_feed() === $feed ) {
			$feed = '';
		}

		$permalink = str_replace( '%feed%', $feed, $permalink );
		$permalink = preg_replace( '#/+#', '/', "/$permalink" );
		$output    = home_url( user_trailingslashit( $permalink, 'feed' ) );
	} else {
		if ( empty( $feed ) ) {
			$feed = get_default_feed();
		}

		if ( str_contains( $feed, 'comments_' ) ) {
			$feed = str_replace( 'comments_', 'comments-', $feed );
		}

		$output = home_url( "?feed={$feed}" );
	}

	/**
	 * Lọc đường dẫn tĩnh loại nguồn cấp dữ liệu.
	 *
	 * @since 1.5.0
	 *
	 * @param string $output Đường dẫn tĩnh nguồn cấp dữ liệu.
	 * @param string $feed   Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom',
	 *                       hoặc chuỗi rỗng cho loại nguồn cấp dữ liệu mặc định.
	 */
	return apply_filters( 'feed_link', $output, $feed );
}

/**
 * Lấy đường dẫn tĩnh cho nguồn cấp bình luận của bài viết.
 *
 * @since 2.2.0
 *
 * @param int    $post_id Tùy chọn. ID bài viết. Mặc định là ID của biến toàn cục `$post`.
 * @param string $feed    Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                        Mặc định là giá trị của get_default_feed().
 * @return string Đường dẫn tĩnh cho nguồn cấp bình luận của bài viết khi thành công, chuỗi rỗng khi thất bại.
 */
function get_post_comments_feed_link( $post_id = 0, $feed = '' ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	$post = get_post( $post_id );

	// Thoát nếu bài viết không tồn tại.
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$unattached = 'attachment' === $post->post_type && 0 === (int) $post->post_parent;

	if ( get_option( 'permalink_structure' ) ) {
		if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post_id ) {
			$url = _get_page_link( $post_id );
		} else {
			$url = get_permalink( $post_id );
		}

		if ( $unattached ) {
			$url = home_url( '/feed/' );
			if ( get_default_feed() !== $feed ) {
				$url .= "$feed/";
			}
			$url = add_query_arg( 'attachment_id', $post_id, $url );
		} else {
			$url = trailingslashit( $url ) . 'feed';
			if ( get_default_feed() !== $feed ) {
				$url .= "/$feed";
			}
			$url = user_trailingslashit( $url, 'single_feed' );
		}
	} else {
		if ( $unattached ) {
			$url = add_query_arg(
				array(
					'feed'          => $feed,
					'attachment_id' => $post_id,
				),
				home_url( '/' )
			);
		} elseif ( 'page' === $post->post_type ) {
			$url = add_query_arg(
				array(
					'feed'    => $feed,
					'page_id' => $post_id,
				),
				home_url( '/' )
			);
		} else {
			$url = add_query_arg(
				array(
					'feed' => $feed,
					'p'    => $post_id,
				),
				home_url( '/' )
			);
		}
	}

	/**
	 * Lọc đường dẫn tĩnh nguồn cấp bình luận bài viết.
	 *
	 * @since 1.5.1
	 *
	 * @param string $url Đường dẫn tĩnh nguồn cấp bình luận bài viết.
	 */
	return apply_filters( 'post_comments_feed_link', $url );
}

/**
 * Hiển thị liên kết nguồn cấp bình luận cho bài viết.
 *
 * In ra liên kết nguồn cấp bình luận cho bài viết. Văn bản liên kết được đặt trong
 * thẻ neo. Nếu không có văn bản liên kết nào được chỉ định, văn bản mặc định sẽ được sử dụng.
 * Nếu không có ID bài viết nào được chỉ định, bài viết hiện tại sẽ được sử dụng.
 *
 * @since 2.5.0
 *
 * @param string $link_text Tùy chọn. Văn bản mô tả liên kết. Mặc định 'Comments Feed'.
 * @param int    $post_id   Tùy chọn. ID bài viết. Mặc định là ID của biến toàn cục `$post`.
 * @param string $feed      Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                          Mặc định là giá trị của get_default_feed().
 */
function post_comments_feed_link( $link_text = '', $post_id = '', $feed = '' ) {
	$url = get_post_comments_feed_link( $post_id, $feed );
	if ( empty( $link_text ) ) {
		$link_text = __( 'Comments Feed' );
	}

	$link = '<a href="' . esc_url( $url ) . '">' . $link_text . '</a>';
	/**
	 * Lọc thẻ neo liên kết nguồn cấp bình luận bài viết.
	 *
	 * @since 2.8.0
	 *
	 * @param string $link    Thẻ neo hoàn chỉnh cho liên kết nguồn cấp bình luận.
	 * @param int    $post_id ID bài viết.
	 * @param string $feed    Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom',
	 *                        hoặc chuỗi rỗng cho loại nguồn cấp dữ liệu mặc định.
	 */
	echo apply_filters( 'post_comments_feed_link_html', $link, $post_id, $feed );
}

/**
 * Lấy liên kết nguồn cấp dữ liệu cho tác giả được chỉ định.
 *
 * Trả về liên kết đến nguồn cấp cho tất cả bài viết của một tác giả. Có thể yêu cầu
 * một loại nguồn cấp cụ thể hoặc để trống để lấy nguồn cấp mặc định.
 *
 * @since 2.5.0
 *
 * @param int    $author_id ID tác giả.
 * @param string $feed      Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                          Mặc định là giá trị của get_default_feed().
 * @return string Liên kết đến nguồn cấp cho tác giả được chỉ định bởi $author_id.
 */
function get_author_feed_link( $author_id, $feed = '' ) {
	$author_id           = (int) $author_id;
	$permalink_structure = get_option( 'permalink_structure' );

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	if ( ! $permalink_structure ) {
		$link = home_url( "?feed=$feed&amp;author=" . $author_id );
	} else {
		$link = get_author_posts_url( $author_id );
		if ( get_default_feed() === $feed ) {
			$feed_link = 'feed';
		} else {
			$feed_link = "feed/$feed";
		}

		$link = trailingslashit( $link ) . user_trailingslashit( $feed_link, 'feed' );
	}

	/**
	 * Lọc liên kết nguồn cấp dữ liệu cho tác giả.
	 *
	 * @since 1.5.1
	 *
	 * @param string $link Liên kết nguồn cấp của tác giả.
	 * @param string $feed Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
	 */
	$link = apply_filters( 'author_feed_link', $link, $feed );

	return $link;
}

/**
 * Lấy liên kết nguồn cấp dữ liệu cho chuyên mục.
 *
 * Trả về liên kết đến nguồn cấp cho tất cả bài viết trong một chuyên mục. Có thể yêu cầu
 * một loại nguồn cấp cụ thể hoặc để trống để lấy nguồn cấp mặc định.
 *
 * @since 2.5.0
 *
 * @param int|WP_Term|object $cat  ID hoặc đối tượng chuyên mục cần lấy liên kết nguồn cấp.
 * @param string             $feed Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                                 Mặc định là giá trị của get_default_feed().
 * @return string Liên kết đến nguồn cấp cho chuyên mục được chỉ định bởi `$cat`.
 */
function get_category_feed_link( $cat, $feed = '' ) {
	return get_term_feed_link( $cat, 'category', $feed );
}

/**
 * Lấy liên kết nguồn cấp dữ liệu cho thuật ngữ phân loại.
 *
 * Trả về liên kết đến nguồn cấp cho tất cả bài viết trong một thuật ngữ. Có thể yêu cầu
 * một loại nguồn cấp cụ thể hoặc để trống để lấy nguồn cấp mặc định.
 *
 * @since 3.0.0
 *
 * @param int|WP_Term|object $term     ID hoặc đối tượng thuật ngữ cần lấy liên kết nguồn cấp.
 * @param string             $taxonomy Tùy chọn. Phân loại của `$term_id`.
 * @param string             $feed     Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                                     Mặc định là giá trị của get_default_feed().
 * @return string|false Liên kết đến nguồn cấp cho thuật ngữ được chỉ định bởi `$term` và `$taxonomy`.
 */
function get_term_feed_link( $term, $taxonomy = '', $feed = '' ) {
	if ( ! is_object( $term ) ) {
		$term = (int) $term;
	}

	$term = get_term( $term, $taxonomy );

	if ( empty( $term ) || is_wp_error( $term ) ) {
		return false;
	}

	$taxonomy = $term->taxonomy;

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	$permalink_structure = get_option( 'permalink_structure' );

	if ( ! $permalink_structure ) {
		if ( 'category' === $taxonomy ) {
			$link = home_url( "?feed=$feed&amp;cat=$term->term_id" );
		} elseif ( 'post_tag' === $taxonomy ) {
			$link = home_url( "?feed=$feed&amp;tag=$term->slug" );
		} else {
			$t    = get_taxonomy( $taxonomy );
			$link = home_url( "?feed=$feed&amp;$t->query_var=$term->slug" );
		}
	} else {
		$link = get_term_link( $term, $term->taxonomy );
		if ( get_default_feed() === $feed ) {
			$feed_link = 'feed';
		} else {
			$feed_link = "feed/$feed";
		}

		$link = trailingslashit( $link ) . user_trailingslashit( $feed_link, 'feed' );
	}

	if ( 'category' === $taxonomy ) {
		/**
		 * Lọc liên kết nguồn cấp chuyên mục.
		 *
		 * @since 1.5.1
		 *
		 * @param string $link Liên kết nguồn cấp chuyên mục.
		 * @param string $feed Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
		 */
		$link = apply_filters( 'category_feed_link', $link, $feed );
	} elseif ( 'post_tag' === $taxonomy ) {
		/**
		 * Lọc liên kết nguồn cấp thẻ bài viết.
		 *
		 * @since 2.3.0
		 *
		 * @param string $link Liên kết nguồn cấp thẻ.
		 * @param string $feed Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
		 */
		$link = apply_filters( 'tag_feed_link', $link, $feed );
	} else {
		/**
		 * Lọc liên kết nguồn cấp cho phân loại khác 'category' hoặc 'post_tag'.
		 *
		 * @since 3.0.0
		 *
		 * @param string $link     Liên kết nguồn cấp phân loại.
		 * @param string $feed     Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
		 * @param string $taxonomy Tên phân loại.
		 */
		$link = apply_filters( 'taxonomy_feed_link', $link, $feed, $taxonomy );
	}

	return $link;
}

/**
 * Lấy đường dẫn tĩnh cho nguồn cấp của thẻ.
 *
 * @since 2.3.0
 *
 * @param int|WP_Term|object $tag  ID hoặc đối tượng thuật ngữ cần lấy liên kết nguồn cấp.
 * @param string             $feed Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                                 Mặc định là giá trị của get_default_feed().
 * @return string                  Đường dẫn tĩnh nguồn cấp cho thẻ được chỉ định.
 */
function get_tag_feed_link( $tag, $feed = '' ) {
	return get_term_feed_link( $tag, 'post_tag', $feed );
}

/**
 * Lấy liên kết chỉnh sửa cho thẻ.
 *
 * @since 2.7.0
 *
 * @param int|WP_Term|object $tag      ID hoặc đối tượng thuật ngữ cần lấy liên kết chỉnh sửa.
 * @param string             $taxonomy Tùy chọn. Slug phân loại. Mặc định 'post_tag'.
 * @return string URL liên kết chỉnh sửa thẻ cho thẻ được chỉ định.
 */
function get_edit_tag_link( $tag, $taxonomy = 'post_tag' ) {
	/**
	 * Lọc liên kết chỉnh sửa cho thẻ (hoặc thuật ngữ trong phân loại khác).
	 *
	 * @since 2.7.0
	 *
	 * @param string $link Liên kết chỉnh sửa thuật ngữ.
	 */
	return apply_filters( 'get_edit_tag_link', get_edit_term_link( $tag, $taxonomy ) );
}

/**
 * Hiển thị hoặc lấy liên kết chỉnh sửa thẻ với định dạng.
 *
 * @since 2.7.0
 *
 * @param string  $link   Tùy chọn. Văn bản neo. Nếu rỗng, mặc định là 'Edit This'. Mặc định rỗng.
 * @param string  $before Tùy chọn. Hiển thị trước liên kết chỉnh sửa. Mặc định rỗng.
 * @param string  $after  Tùy chọn. Hiển thị sau liên kết chỉnh sửa. Mặc định rỗng.
 * @param WP_Term $tag    Tùy chọn. Đối tượng thuật ngữ. Nếu null, đối tượng được truy vấn sẽ được kiểm tra.
 *                        Mặc định null.
 */
function edit_tag_link( $link = '', $before = '', $after = '', $tag = null ) {
	$link = edit_term_link( $link, '', '', $tag, false );

	/**
	 * Lọc thẻ neo cho liên kết chỉnh sửa thẻ (hoặc thuật ngữ trong phân loại khác).
	 *
	 * @since 2.7.0
	 *
	 * @param string $link Thẻ neo cho liên kết chỉnh sửa.
	 */
	echo $before . apply_filters( 'edit_tag_link', $link ) . $after;
}

/**
 * Lấy URL chỉnh sửa cho thuật ngữ được chỉ định.
 *
 * @since 3.1.0
 * @since 4.5.0 Tham số `$taxonomy` trở thành tùy chọn.
 *
 * @param int|WP_Term|object $term        ID hoặc đối tượng thuật ngữ cần lấy liên kết chỉnh sửa.
 * @param string             $taxonomy    Tùy chọn. Phân loại. Mặc định là phân loại của thuật ngữ
 *                                        được xác định bởi `$term`.
 * @param string             $object_type Tùy chọn. Loại đối tượng. Dùng để đánh dấu menu loại bài viết
 *                                        phù hợp trên trang liên kết. Mặc định là object_type đầu tiên
 *                                        liên kết với phân loại.
 * @return string|null URL liên kết chỉnh sửa thuật ngữ cho thuật ngữ được chỉ định, hoặc null khi thất bại.
 */
function get_edit_term_link( $term, $taxonomy = '', $object_type = '' ) {
	$term = get_term( $term, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}

	$tax     = get_taxonomy( $term->taxonomy );
	$term_id = $term->term_id;
	if ( ! $tax || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	$args = array(
		'taxonomy' => $tax->name,
		'tag_ID'   => $term_id,
	);

	if ( $object_type ) {
		$args['post_type'] = $object_type;
	} elseif ( ! empty( $tax->object_type ) ) {
		$args['post_type'] = reset( $tax->object_type );
	}

	if ( $tax->show_ui ) {
		$location = add_query_arg( $args, admin_url( 'term.php' ) );
	} else {
		$location = '';
	}

	/**
	 * Lọc liên kết chỉnh sửa cho thuật ngữ.
	 *
	 * @since 3.1.0
	 *
	 * @param string $location    Liên kết chỉnh sửa.
	 * @param int    $term_id     ID thuật ngữ.
	 * @param string $taxonomy    Tên phân loại.
	 * @param string $object_type Loại đối tượng.
	 */
	return apply_filters( 'get_edit_term_link', $location, $term_id, $taxonomy, $object_type );
}

/**
 * Hiển thị hoặc lấy liên kết chỉnh sửa thuật ngữ với định dạng.
 *
 * @since 3.1.0
 *
 * @param string           $link    Tùy chọn. Văn bản neo. Nếu rỗng, mặc định là 'Edit This'. Mặc định rỗng.
 * @param string           $before  Tùy chọn. Hiển thị trước liên kết chỉnh sửa. Mặc định rỗng.
 * @param string           $after   Tùy chọn. Hiển thị sau liên kết chỉnh sửa. Mặc định rỗng.
 * @param int|WP_Term|null $term    Tùy chọn. ID hoặc đối tượng thuật ngữ. Nếu null, đối tượng truy vấn sẽ được kiểm tra. Mặc định null.
 * @param bool             $display Tùy chọn. Có echo kết quả trả về hay không. Mặc định true.
 * @return string|void Nội dung HTML.
 */
function edit_term_link( $link = '', $before = '', $after = '', $term = null, $display = true ) {
	if ( is_null( $term ) ) {
		$term = get_queried_object();
	} else {
		$term = get_term( $term );
	}

	if ( ! $term ) {
		return;
	}

	$tax = get_taxonomy( $term->taxonomy );
	if ( ! current_user_can( 'edit_term', $term->term_id ) ) {
		return;
	}

	if ( empty( $link ) ) {
		$link = __( 'Edit This' );
	}

	$link = '<a href="' . get_edit_term_link( $term->term_id, $term->taxonomy ) . '">' . $link . '</a>';

	/**
	 * Lọc thẻ neo cho liên kết chỉnh sửa thuật ngữ.
	 *
	 * @since 3.1.0
	 *
	 * @param string $link    Thẻ neo cho liên kết chỉnh sửa.
	 * @param int    $term_id ID thuật ngữ.
	 */
	$link = $before . apply_filters( 'edit_term_link', $link, $term->term_id ) . $after;

	if ( $display ) {
		echo $link;
	} else {
		return $link;
	}
}

/**
 * Lấy đường dẫn tĩnh cho tìm kiếm.
 *
 * @since 3.0.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $query Tùy chọn. Chuỗi truy vấn để sử dụng. Nếu rỗng, truy vấn hiện tại sẽ được dùng. Mặc định rỗng.
 * @return string Đường dẫn tĩnh tìm kiếm.
 */
function get_search_link( $query = '' ) {
	global $wp_rewrite;

	if ( empty( $query ) ) {
		$search = get_search_query( false );
	} else {
		$search = stripslashes( $query );
	}

	$permastruct = $wp_rewrite->get_search_permastruct();

	if ( empty( $permastruct ) ) {
		$link = home_url( '?s=' . urlencode( $search ) );
	} else {
		$search = urlencode( $search );
		$search = str_replace( '%2F', '/', $search ); // %2F(/) không hợp lệ trong URL, gửi không mã hóa.
		$link   = str_replace( '%search%', $search, $permastruct );
		$link   = home_url( user_trailingslashit( $link, 'search' ) );
	}

	/**
	 * Lọc đường dẫn tĩnh tìm kiếm.
	 *
	 * @since 3.0.0
	 *
	 * @param string $link   Đường dẫn tĩnh tìm kiếm.
	 * @param string $search Cụm từ tìm kiếm đã mã hóa URL.
	 */
	return apply_filters( 'search_link', $link, $search );
}

/**
 * Lấy đường dẫn tĩnh cho nguồn cấp kết quả tìm kiếm.
 *
 * @since 2.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $search_query Tùy chọn. Truy vấn tìm kiếm. Mặc định rỗng.
 * @param string $feed         Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                             Mặc định là giá trị của get_default_feed().
 * @return string Đường dẫn tĩnh nguồn cấp kết quả tìm kiếm.
 */
function get_search_feed_link( $search_query = '', $feed = '' ) {
	global $wp_rewrite;
	$link = get_search_link( $search_query );

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	$permastruct = $wp_rewrite->get_search_permastruct();

	if ( empty( $permastruct ) ) {
		$link = add_query_arg( 'feed', $feed, $link );
	} else {
		$link  = trailingslashit( $link );
		$link .= "feed/$feed/";
	}

	/**
	 * Lọc liên kết nguồn cấp tìm kiếm.
	 *
	 * @since 2.5.0
	 *
	 * @param string $link Liên kết nguồn cấp tìm kiếm.
	 * @param string $feed Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
	 * @param string $type Loại tìm kiếm. Một trong 'posts' hoặc 'comments'.
	 */
	return apply_filters( 'search_feed_link', $link, $feed, 'posts' );
}

/**
 * Lấy đường dẫn tĩnh cho nguồn cấp bình luận kết quả tìm kiếm.
 *
 * @since 2.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $search_query Tùy chọn. Truy vấn tìm kiếm. Mặc định rỗng.
 * @param string $feed         Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                             Mặc định là giá trị của get_default_feed().
 * @return string Đường dẫn tĩnh nguồn cấp bình luận kết quả tìm kiếm.
 */
function get_search_comments_feed_link( $search_query = '', $feed = '' ) {
	global $wp_rewrite;

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	$link = get_search_feed_link( $search_query, $feed );

	$permastruct = $wp_rewrite->get_search_permastruct();

	if ( empty( $permastruct ) ) {
		$link = add_query_arg( 'feed', 'comments-' . $feed, $link );
	} else {
		$link = add_query_arg( 'withcomments', 1, $link );
	}

	/** Bộ lọc này được ghi nhận trong wp-includes/link-template.php */
	return apply_filters( 'search_feed_link', $link, $feed, 'comments' );
}

/**
 * Lấy đường dẫn tĩnh cho lưu trữ loại bài viết.
 *
 * @since 3.1.0
 * @since 4.5.0 Thêm hỗ trợ cho bài viết.
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string $post_type Loại bài viết.
 * @return string|false Đường dẫn tĩnh lưu trữ loại bài viết. False nếu loại bài viết
 *                      không tồn tại hoặc không có lưu trữ.
 */
function get_post_type_archive_link( $post_type ) {
	global $wp_rewrite;

	$post_type_obj = get_post_type_object( $post_type );

	if ( ! $post_type_obj ) {
		return false;
	}

	if ( 'post' === $post_type ) {
		$show_on_front  = get_option( 'show_on_front' );
		$page_for_posts = get_option( 'page_for_posts' );

		if ( 'page' === $show_on_front && $page_for_posts ) {
			$link = get_permalink( $page_for_posts );
		} else {
			$link = get_home_url();
		}
		/** Bộ lọc này được ghi nhận trong wp-includes/link-template.php */
		return apply_filters( 'post_type_archive_link', $link, $post_type );
	}

	if ( ! $post_type_obj->has_archive ) {
		return false;
	}

	if ( get_option( 'permalink_structure' ) && is_array( $post_type_obj->rewrite ) ) {
		$struct = ( true === $post_type_obj->has_archive ) ? $post_type_obj->rewrite['slug'] : $post_type_obj->has_archive;
		if ( $post_type_obj->rewrite['with_front'] ) {
			$struct = $wp_rewrite->front . $struct;
		} else {
			$struct = $wp_rewrite->root . $struct;
		}
		$link = home_url( user_trailingslashit( $struct, 'post_type_archive' ) );
	} else {
		$link = home_url( '?post_type=' . $post_type );
	}

	/**
	 * Lọc đường dẫn tĩnh lưu trữ loại bài viết.
	 *
	 * @since 3.1.0
	 *
	 * @param string $link      Đường dẫn tĩnh lưu trữ loại bài viết.
	 * @param string $post_type Tên loại bài viết.
	 */
	return apply_filters( 'post_type_archive_link', $link, $post_type );
}

/**
 * Lấy đường dẫn tĩnh cho nguồn cấp lưu trữ loại bài viết.
 *
 * @since 3.1.0
 *
 * @param string $post_type Loại bài viết.
 * @param string $feed      Tùy chọn. Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                          Mặc định là giá trị của get_default_feed().
 * @return string|false Đường dẫn tĩnh nguồn cấp loại bài viết. False nếu loại bài viết
 *                      không tồn tại hoặc không có lưu trữ.
 */
function get_post_type_archive_feed_link( $post_type, $feed = '' ) {
	$default_feed = get_default_feed();
	if ( empty( $feed ) ) {
		$feed = $default_feed;
	}

	$link = get_post_type_archive_link( $post_type );
	if ( ! $link ) {
		return false;
	}

	$post_type_obj = get_post_type_object( $post_type );
	if ( get_option( 'permalink_structure' ) && is_array( $post_type_obj->rewrite ) && $post_type_obj->rewrite['feeds'] ) {
		$link  = trailingslashit( $link );
		$link .= 'feed/';
		if ( $feed !== $default_feed ) {
			$link .= "$feed/";
		}
	} else {
		$link = add_query_arg( 'feed', $feed, $link );
	}

	/**
	 * Lọc liên kết nguồn cấp lưu trữ loại bài viết.
	 *
	 * @since 3.1.0
	 *
	 * @param string $link Liên kết nguồn cấp lưu trữ loại bài viết.
	 * @param string $feed Loại nguồn cấp dữ liệu. Các giá trị có thể bao gồm 'rss2', 'atom'.
	 */
	return apply_filters( 'post_type_archive_feed_link', $link, $feed );
}

/**
 * Lấy URL được sử dụng để xem trước bài viết.
 *
 * Cho phép thêm các tham số truy vấn bổ sung.
 *
 * @since 4.4.0
 *
 * @param int|WP_Post $post         Tùy chọn. ID bài viết hoặc đối tượng `WP_Post`. Mặc định là biến toàn cục `$post`.
 * @param array       $query_args   Tùy chọn. Mảng các tham số truy vấn bổ sung được thêm vào liên kết.
 *                                  Mặc định mảng rỗng.
 * @param string      $preview_link Tùy chọn. Liên kết xem trước cơ sở được sử dụng nếu nó khác với
 *                                  đường dẫn tĩnh của bài viết. Mặc định rỗng.
 * @return string|null URL được sử dụng để xem trước bài viết, hoặc null nếu bài viết không tồn tại.
 */
function get_preview_post_link( $post = null, $query_args = array(), $preview_link = '' ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_type_object = get_post_type_object( $post->post_type );
	if ( is_post_type_viewable( $post_type_object ) ) {
		if ( ! $preview_link ) {
			$preview_link = set_url_scheme( get_permalink( $post ) );
		}

		$query_args['preview'] = 'true';
		$preview_link          = add_query_arg( $query_args, $preview_link );
	}

	/**
	 * Lọc URL được sử dụng để xem trước bài viết.
	 *
	 * @since 2.0.5
	 * @since 4.0.0 Thêm tham số `$post`.
	 *
	 * @param string  $preview_link URL được sử dụng để xem trước bài viết.
	 * @param WP_Post $post         Đối tượng bài viết.
	 */
	return apply_filters( 'preview_post_link', $preview_link, $post );
}

/**
 * Lấy liên kết chỉnh sửa bài viết.
 *
 * Có thể được sử dụng trong Vòng lặp WordPress hoặc bên ngoài. Có thể sử dụng với
 * trang, bài viết, tệp đính kèm, bản sửa đổi, kiểu toàn cục, mẫu, và phần mẫu.
 *
 * @since 2.3.0
 * @since 6.3.0 Thêm liên kết tùy chỉnh cho loại bài viết wp_navigation.
 *              Thêm liên kết tùy chỉnh cho loại bài viết wp_template_part và wp_template.
 *
 * @param int|WP_Post $post    Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 * @param string      $context Tùy chọn. Cách xuất ký tự '&'. Mặc định '&amp;'.
 * @return string|null Liên kết chỉnh sửa bài viết cho bài viết được chỉ định. Null nếu loại bài viết không tồn tại
 *                     hoặc không cho phép giao diện chỉnh sửa.
 */
function get_edit_post_link( $post = 0, $context = 'display' ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	if ( 'revision' === $post->post_type ) {
		$action = '';
	} elseif ( 'display' === $context ) {
		$action = '&amp;action=edit';
	} else {
		$action = '&action=edit';
	}

	$post_type_object = get_post_type_object( $post->post_type );

	if ( ! $post_type_object ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}

	$link = '';

	if ( 'wp_template' === $post->post_type || 'wp_template_part' === $post->post_type ) {
		$slug = urlencode( get_stylesheet() . '//' . $post->post_name );
		$link = admin_url( sprintf( $post_type_object->_edit_link, $post->post_type, $slug ) );
	} elseif ( 'wp_navigation' === $post->post_type ) {
		$link = admin_url( sprintf( $post_type_object->_edit_link, (string) $post->ID ) );
	} elseif ( $post_type_object->_edit_link ) {
		$link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
	}

	/**
	 * Lọc liên kết chỉnh sửa bài viết.
	 *
	 * @since 2.3.0
	 *
	 * @param string $link    Liên kết chỉnh sửa.
	 * @param int    $post_id ID bài viết.
	 * @param string $context Ngữ cảnh liên kết. Nếu đặt là 'display' thì các dấu & sẽ được mã hóa.
	 */
	return apply_filters( 'get_edit_post_link', $link, $post->ID, $context );
}

/**
 * Hiển thị liên kết chỉnh sửa bài viết.
 *
 * @since 1.0.0
 * @since 4.4.0 Thêm tham số `$css_class`.
 *
 * @param string      $text      Tùy chọn. Văn bản neo. Nếu null, mặc định là 'Edit This'. Mặc định null.
 * @param string      $before    Tùy chọn. Hiển thị trước liên kết chỉnh sửa. Mặc định rỗng.
 * @param string      $after     Tùy chọn. Hiển thị sau liên kết chỉnh sửa. Mặc định rỗng.
 * @param int|WP_Post $post      Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 * @param string      $css_class Tùy chọn. Thêm class tùy chỉnh cho liên kết. Mặc định 'post-edit-link'.
 */
function edit_post_link( $text = null, $before = '', $after = '', $post = 0, $css_class = 'post-edit-link' ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$url = get_edit_post_link( $post->ID );

	if ( ! $url ) {
		return;
	}

	if ( null === $text ) {
		$text = __( 'Edit This' );
	}

	$link = '<a class="' . esc_attr( $css_class ) . '" href="' . esc_url( $url ) . '">' . $text . '</a>';

	/**
	 * Lọc thẻ neo liên kết chỉnh sửa bài viết.
	 *
	 * @since 2.3.0
	 *
	 * @param string $link    Thẻ neo cho liên kết chỉnh sửa.
	 * @param int    $post_id ID bài viết.
	 * @param string $text    Văn bản neo.
	 */
	echo $before . apply_filters( 'edit_post_link', $link, $post->ID, $text ) . $after;
}

/**
 * Lấy liên kết xóa bài viết.
 *
 * Có thể được sử dụng trong Vòng lặp WordPress hoặc bên ngoài, với bất kỳ loại bài viết nào.
 *
 * @since 2.9.0
 *
 * @param int|WP_Post $post         Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là biến toàn cục `$post`.
 * @param string      $deprecated   Không sử dụng.
 * @param bool        $force_delete Tùy chọn. Có bỏ qua Thùng rác và buộc xóa hay không. Mặc định false.
 * @return string|void URL liên kết xóa bài viết cho bài viết được chỉ định.
 */
function get_delete_post_link( $post = 0, $deprecated = '', $force_delete = false ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '3.0.0' );
	}

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_type_object = get_post_type_object( $post->post_type );

	if ( ! $post_type_object ) {
		return;
	}

	if ( ! current_user_can( 'delete_post', $post->ID ) ) {
		return;
	}

	$action = ( $force_delete || ! EMPTY_TRASH_DAYS ) ? 'delete' : 'trash';

	$delete_link = add_query_arg( 'action', $action, admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) );

	/**
	 * Lọc liên kết xóa bài viết.
	 *
	 * @since 2.9.0
	 *
	 * @param string $link         Liên kết xóa.
	 * @param int    $post_id      ID bài viết.
	 * @param bool   $force_delete Có bỏ qua Thùng rác và buộc xóa hay không. Mặc định false.
	 */
	return apply_filters( 'get_delete_post_link', wp_nonce_url( $delete_link, "$action-post_{$post->ID}" ), $post->ID, $force_delete );
}

/**
 * Lấy liên kết chỉnh sửa bình luận.
 *
 * @since 2.3.0
 * @since 6.7.0 Thêm tham số $context.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. ID bình luận hoặc đối tượng WP_Comment.
 * @param string         $context    Tùy chọn. Ngữ cảnh sử dụng URL. Có thể là 'display',
 *                                   để bao gồm thực thể HTML, hoặc 'url'. Mặc định 'display'.
 * @return string|void URL liên kết chỉnh sửa bình luận cho bình luận được chỉ định, hoặc void nếu ID bình luận không tồn tại hoặc
 *                     người dùng hiện tại không được phép chỉnh sửa.
 */
function get_edit_comment_link( $comment_id = 0, $context = 'display' ) {
	$comment = get_comment( $comment_id );

	if ( ! is_object( $comment ) || ! current_user_can( 'edit_comment', $comment->comment_ID ) ) {
		return;
	}

	if ( 'display' === $context ) {
		$action = 'comment.php?action=editcomment&amp;c=';
	} else {
		$action = 'comment.php?action=editcomment&c=';
	}

	$location = admin_url( $action ) . $comment->comment_ID;

	// Đảm bảo biến $comment_id được truyền vào bộ lọc luôn là ID.
	$comment_id = (int) $comment->comment_ID;

	/**
	 * Lọc liên kết chỉnh sửa bình luận.
	 *
	 * @since 2.3.0
	 * @since 6.7.0 Các tham số $comment_id và $context giờ đây được truyền vào bộ lọc.
	 *
	 * @param string $location   Liên kết chỉnh sửa.
	 * @param int    $comment_id ID duy nhất của bình luận để tạo liên kết chỉnh sửa.
	 * @param string $context    Ngữ cảnh để bao gồm thực thể HTML trong liên kết. Mặc định 'display'.
	 */
	return apply_filters( 'get_edit_comment_link', $location, $comment_id, $context );
}

/**
 * Hiển thị liên kết chỉnh sửa bình luận với định dạng.
 *
 * @since 1.0.0
 *
 * @param string $text   Tùy chọn. Văn bản neo. Nếu null, mặc định là 'Edit This'. Mặc định null.
 * @param string $before Tùy chọn. Hiển thị trước liên kết chỉnh sửa. Mặc định rỗng.
 * @param string $after  Tùy chọn. Hiển thị sau liên kết chỉnh sửa. Mặc định rỗng.
 */
function edit_comment_link( $text = null, $before = '', $after = '' ) {
	$comment = get_comment();

	if ( ! current_user_can( 'edit_comment', $comment->comment_ID ) ) {
		return;
	}

	if ( null === $text ) {
		$text = __( 'Edit This' );
	}

	$link = '<a class="comment-edit-link" href="' . esc_url( get_edit_comment_link( $comment ) ) . '">' . $text . '</a>';

	/**
	 * Lọc thẻ neo liên kết chỉnh sửa bình luận.
	 *
	 * @since 2.3.0
	 *
	 * @param string $link       Thẻ neo cho liên kết chỉnh sửa.
	 * @param string $comment_id ID bình luận dạng chuỗi số.
	 * @param string $text       Văn bản neo.
	 */
	echo $before . apply_filters( 'edit_comment_link', $link, $comment->comment_ID, $text ) . $after;
}

/**
 * Hiển thị liên kết chỉnh sửa dấu trang.
 *
 * @since 2.7.0
 *
 * @param int|stdClass $link Tùy chọn. ID dấu trang. Mặc định là ID của dấu trang hiện tại.
 * @return string|void URL liên kết chỉnh sửa dấu trang.
 */
function get_edit_bookmark_link( $link = 0 ) {
	$link = get_bookmark( $link );

	if ( ! current_user_can( 'manage_links' ) ) {
		return;
	}

	$location = admin_url( 'link.php?action=edit&amp;link_id=' ) . $link->link_id;

	/**
	 * Lọc liên kết chỉnh sửa dấu trang.
	 *
	 * @since 2.7.0
	 *
	 * @param string $location Liên kết chỉnh sửa.
	 * @param int    $link_id  ID dấu trang.
	 */
	return apply_filters( 'get_edit_bookmark_link', $location, $link->link_id );
}

/**
 * Hiển thị nội dung neo liên kết chỉnh sửa dấu trang.
 *
 * @since 2.7.0
 *
 * @param string $link     Tùy chọn. Văn bản neo. Nếu rỗng, mặc định là 'Edit This'. Mặc định rỗng.
 * @param string $before   Tùy chọn. Hiển thị trước liên kết chỉnh sửa. Mặc định rỗng.
 * @param string $after    Tùy chọn. Hiển thị sau liên kết chỉnh sửa. Mặc định rỗng.
 * @param int    $bookmark Tùy chọn. ID dấu trang. Mặc định là dấu trang hiện tại.
 */
function edit_bookmark_link( $link = '', $before = '', $after = '', $bookmark = null ) {
	$bookmark = get_bookmark( $bookmark );

	if ( ! current_user_can( 'manage_links' ) ) {
		return;
	}

	if ( empty( $link ) ) {
		$link = __( 'Edit This' );
	}

	$link = '<a href="' . esc_url( get_edit_bookmark_link( $bookmark ) ) . '">' . $link . '</a>';

	/**
	 * Lọc thẻ neo liên kết chỉnh sửa dấu trang.
	 *
	 * @since 2.7.0
	 *
	 * @param string $link    Thẻ neo cho liên kết chỉnh sửa.
	 * @param int    $link_id ID dấu trang.
	 */
	echo $before . apply_filters( 'edit_bookmark_link', $link, $bookmark->link_id ) . $after;
}

/**
 * Lấy liên kết chỉnh sửa người dùng.
 *
 * @since 3.5.0
 *
 * @param int $user_id Tùy chọn. ID người dùng. Mặc định là người dùng hiện tại.
 * @return string URL đến trang chỉnh sửa người dùng hoặc chuỗi rỗng.
 */
function get_edit_user_link( $user_id = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return '';
	}

	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return '';
	}

	if ( get_current_user_id() === $user->ID ) {
		$link = get_edit_profile_url( $user->ID );
	} else {
		$link = add_query_arg( 'user_id', $user->ID, self_admin_url( 'user-edit.php' ) );
	}

	/**
	 * Lọc liên kết chỉnh sửa người dùng.
	 *
	 * @since 3.5.0
	 *
	 * @param string $link    Liên kết chỉnh sửa.
	 * @param int    $user_id ID người dùng.
	 */
	return apply_filters( 'get_edit_user_link', $link, $user->ID );
}

//
// Liên kết điều hướng.
//

/**
 * Lấy bài viết trước đó liền kề với bài viết hiện tại.
 *
 * @since 1.5.0
 *
 * @param bool         $in_same_term   Tùy chọn. Bài viết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return WP_Post|null|string Đối tượng bài viết nếu thành công. Null nếu biến toàn cục `$post` không được đặt.
 *                             Chuỗi rỗng nếu không có bài viết tương ứng.
 */
function get_previous_post( $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	return get_adjacent_post( $in_same_term, $excluded_terms, true, $taxonomy );
}

/**
 * Lấy bài viết tiếp theo liền kề với bài viết hiện tại.
 *
 * @since 1.5.0
 *
 * @param bool         $in_same_term   Tùy chọn. Bài viết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return WP_Post|null|string Đối tượng bài viết nếu thành công. Null nếu biến toàn cục `$post` không được đặt.
 *                             Chuỗi rỗng nếu không có bài viết tương ứng.
 */
function get_next_post( $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	return get_adjacent_post( $in_same_term, $excluded_terms, false, $taxonomy );
}

/**
 * Lấy bài viết liền kề.
 *
 * Có thể là bài viết tiếp theo hoặc bài viết trước đó.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param bool         $in_same_term   Tùy chọn. Bài viết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định chuỗi rỗng.
 * @param bool         $previous       Tùy chọn. Có lấy bài viết trước đó hay không.
 *                                     Mặc định true.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return WP_Post|null|string Đối tượng bài viết nếu thành công. Null nếu biến toàn cục `$post` không được đặt.
 *                             Chuỗi rỗng nếu không có bài viết tương ứng.
 */
function get_adjacent_post( $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
	global $wpdb;

	$post = get_post();

	if ( ! $post || ! taxonomy_exists( $taxonomy ) ) {
		return null;
	}

	$current_post_date = $post->post_date;

	$join     = '';
	$where    = '';
	$adjacent = $previous ? 'previous' : 'next';

	if ( ! empty( $excluded_terms ) && ! is_array( $excluded_terms ) ) {
		// Tương thích ngược, $excluded_terms trước đây là $excluded_categories với các ID phân cách bằng " and ".
		if ( str_contains( $excluded_terms, ' and ' ) ) {
			_deprecated_argument(
				__FUNCTION__,
				'3.3.0',
				sprintf(
					/* translators: %s: The word 'and'. */
					__( 'Use commas instead of %s to separate excluded terms.' ),
					"'and'"
				)
			);
			$excluded_terms = explode( ' and ', $excluded_terms );
		} else {
			$excluded_terms = explode( ',', $excluded_terms );
		}

		$excluded_terms = array_map( 'intval', $excluded_terms );
	}

	/**
	 * Lọc các ID thuật ngữ bị loại trừ khỏi truy vấn bài viết liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại
	 * liền kề, 'next' hoặc 'previous'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_next_post_excluded_terms`
	 *  - `get_previous_post_excluded_terms`
	 *
	 * @since 4.4.0
	 *
	 * @param int[]|string $excluded_terms Mảng các ID thuật ngữ bị loại trừ. Chuỗi rỗng nếu không có giá trị nào được cung cấp.
	 */
	$excluded_terms = apply_filters( "get_{$adjacent}_post_excluded_terms", $excluded_terms );

	if ( $in_same_term || ! empty( $excluded_terms ) ) {
		if ( $in_same_term ) {
			$join  .= " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
			$where .= $wpdb->prepare( 'AND tt.taxonomy = %s', $taxonomy );

			if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
				return '';
			}
			$term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

			// Loại bỏ bất kỳ mục loại trừ nào khỏi mảng thuật ngữ cần bao gồm.
			$term_array = array_diff( $term_array, (array) $excluded_terms );
			$term_array = array_map( 'intval', $term_array );

			if ( ! $term_array || is_wp_error( $term_array ) ) {
				return '';
			}

			$where .= ' AND tt.term_id IN (' . implode( ',', $term_array ) . ')';
		}

		if ( ! empty( $excluded_terms ) ) {
			$where .= " AND p.ID NOT IN ( SELECT tr.object_id FROM $wpdb->term_relationships tr LEFT JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE tt.term_id IN (" . implode( ',', array_map( 'intval', $excluded_terms ) ) . ') )';
		}
	}

	// Mệnh đề 'post_status' phụ thuộc vào người dùng hiện tại.
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();

		$post_type_object = get_post_type_object( $post->post_type );
		if ( empty( $post_type_object ) ) {
			$post_type_cap    = $post->post_type;
			$read_private_cap = 'read_private_' . $post_type_cap . 's';
		} else {
			$read_private_cap = $post_type_object->cap->read_private_posts;
		}

		/*
		 * Kết quả nên bao gồm bài viết riêng tư thuộc về người dùng hiện tại, hoặc bài viết riêng tư mà
		 * người dùng hiện tại có quyền 'read_private_posts'.
		 */
		$private_states = get_post_stati( array( 'private' => true ) );
		$where         .= " AND ( p.post_status = 'publish'";
		foreach ( $private_states as $state ) {
			if ( current_user_can( $read_private_cap ) ) {
				$where .= $wpdb->prepare( ' OR p.post_status = %s', $state );
			} else {
				$where .= $wpdb->prepare( ' OR (p.post_author = %d AND p.post_status = %s)', $user_id, $state );
			}
		}
		$where .= ' )';
	} else {
		$where .= " AND p.post_status = 'publish'";
	}

	$op    = $previous ? '<' : '>';
	$order = $previous ? 'DESC' : 'ASC';

	/**
	 * Lọc mệnh đề JOIN trong SQL cho truy vấn bài viết liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại
	 * liền kề, 'next' hoặc 'previous'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_next_post_join`
	 *  - `get_previous_post_join`
	 *
	 * @since 2.5.0
	 * @since 4.4.0 Thêm các tham số `$taxonomy` và `$post`.
	 *
	 * @param string       $join           Mệnh đề JOIN trong SQL.
	 * @param bool         $in_same_term   Bài viết có nên thuộc cùng thuật ngữ phân loại hay không.
	 * @param int[]|string $excluded_terms Mảng các ID thuật ngữ bị loại trừ. Chuỗi rỗng nếu không có giá trị nào được cung cấp.
	 * @param string       $taxonomy       Phân loại. Dùng để xác định thuật ngữ được sử dụng khi `$in_same_term` là true.
	 * @param WP_Post      $post           Đối tượng WP_Post.
	 */
	$join = apply_filters( "get_{$adjacent}_post_join", $join, $in_same_term, $excluded_terms, $taxonomy, $post );

	/**
	 * Lọc mệnh đề WHERE trong SQL cho truy vấn bài viết liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại
	 * liền kề, 'next' hoặc 'previous'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_next_post_where`
	 *  - `get_previous_post_where`
	 *
	 * @since 2.5.0
	 * @since 4.4.0 Thêm các tham số `$taxonomy` và `$post`.
	 *
	 * @param string       $where          Mệnh đề `WHERE` trong SQL.
	 * @param bool         $in_same_term   Bài viết có nên thuộc cùng thuật ngữ phân loại hay không.
	 * @param int[]|string $excluded_terms Mảng các ID thuật ngữ bị loại trừ. Chuỗi rỗng nếu không có giá trị nào được cung cấp.
	 * @param string       $taxonomy       Phân loại. Dùng để xác định thuật ngữ được sử dụng khi `$in_same_term` là true.
	 * @param WP_Post      $post           Đối tượng WP_Post.
	 */
	$where = apply_filters( "get_{$adjacent}_post_where", $wpdb->prepare( "WHERE p.post_date $op %s AND p.post_type = %s $where", $current_post_date, $post->post_type ), $in_same_term, $excluded_terms, $taxonomy, $post );

	/**
	 * Lọc mệnh đề ORDER BY trong SQL cho truy vấn bài viết liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại
	 * liền kề, 'next' hoặc 'previous'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_next_post_sort`
	 *  - `get_previous_post_sort`
	 *
	 * @since 2.5.0
	 * @since 4.4.0 Thêm tham số `$post`.
	 * @since 4.9.0 Thêm tham số `$order`.
	 *
	 * @param string $order_by Mệnh đề `ORDER BY` trong SQL.
	 * @param WP_Post $post    Đối tượng WP_Post.
	 * @param string  $order   Thứ tự sắp xếp. 'DESC' cho bài viết trước, 'ASC' cho bài viết tiếp theo.
	 */
	$sort = apply_filters( "get_{$adjacent}_post_sort", "ORDER BY p.post_date $order LIMIT 1", $post, $order );

	$query        = "SELECT p.ID FROM $wpdb->posts AS p $join $where $sort";
	$key          = md5( $query );
	$last_changed = wp_cache_get_last_changed( 'posts' );
	if ( $in_same_term || ! empty( $excluded_terms ) ) {
		$last_changed .= wp_cache_get_last_changed( 'terms' );
	}
	$cache_key = "adjacent_post:$key:$last_changed";

	$result = wp_cache_get( $cache_key, 'post-queries' );
	if ( false !== $result ) {
		if ( $result ) {
			$result = get_post( $result );
		}
		return $result;
	}

	$result = $wpdb->get_var( $query );
	if ( null === $result ) {
		$result = '';
	}

	wp_cache_set( $cache_key, $result, 'post-queries' );

	if ( $result ) {
		$result = get_post( $result );
	}

	return $result;
}

/**
 * Lấy liên kết quan hệ bài viết liền kề.
 *
 * Có thể là liên kết quan hệ bài viết tiếp theo hoặc trước đó.
 *
 * @since 2.8.0
 *
 * @param string       $title          Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param bool         $previous       Tùy chọn. Có hiển thị liên kết đến bài viết trước hay tiếp theo.
 *                                     Mặc định true.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return string|void URL liên kết quan hệ bài viết liền kề.
 */
function get_adjacent_post_rel_link( $title = '%title', $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
	$post = get_post();
	if ( $previous && is_attachment() && $post ) {
		$post = get_post( $post->post_parent );
	} else {
		$post = get_adjacent_post( $in_same_term, $excluded_terms, $previous, $taxonomy );
	}

	if ( empty( $post ) ) {
		return;
	}

	$post_title = the_title_attribute(
		array(
			'echo' => false,
			'post' => $post,
		)
	);

	if ( empty( $post_title ) ) {
		$post_title = $previous ? __( 'Previous Post' ) : __( 'Next Post' );
	}

	$date = mysql2date( get_option( 'date_format' ), $post->post_date );

	$title = str_replace( '%title', $post_title, $title );
	$title = str_replace( '%date', $date, $title );

	$link  = $previous ? "<link rel='prev' title='" : "<link rel='next' title='";
	$link .= esc_attr( $title );
	$link .= "' href='" . get_permalink( $post ) . "' />\n";

	$adjacent = $previous ? 'previous' : 'next';

	/**
	 * Lọc liên kết quan hệ bài viết liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại
	 * liền kề, 'next' hoặc 'previous'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `next_post_rel_link`
	 *  - `previous_post_rel_link`
	 *
	 * @since 2.8.0
	 *
	 * @param string $link Liên kết quan hệ.
	 */
	return apply_filters( "{$adjacent}_post_rel_link", $link );
}

/**
 * Hiển thị các liên kết quan hệ cho bài viết liền kề với bài viết hiện tại.
 *
 * @since 2.8.0
 *
 * @param string       $title          Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 */
function adjacent_posts_rel_link( $title = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	echo get_adjacent_post_rel_link( $title, $in_same_term, $excluded_terms, true, $taxonomy );
	echo get_adjacent_post_rel_link( $title, $in_same_term, $excluded_terms, false, $taxonomy );
}

/**
 * Hiển thị liên kết quan hệ cho bài viết liền kề với bài viết hiện tại trên trang bài viết đơn.
 *
 * Hàm này được thiết kế để gắn vào các action như 'wp_head'. Không gọi trực tiếp trong plugin
 * hoặc mẫu theme.
 *
 * @since 3.0.0
 * @since 5.6.0 Không còn được sử dụng trong lõi.
 *
 * @see adjacent_posts_rel_link()
 */
function adjacent_posts_rel_link_wp_head() {
	if ( ! is_single() || is_attachment() ) {
		return;
	}
	adjacent_posts_rel_link();
}

/**
 * Hiển thị liên kết quan hệ cho bài viết tiếp theo liền kề với bài viết hiện tại.
 *
 * @since 2.8.0
 *
 * @see get_adjacent_post_rel_link()
 *
 * @param string       $title          Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 */
function next_post_rel_link( $title = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	echo get_adjacent_post_rel_link( $title, $in_same_term, $excluded_terms, false, $taxonomy );
}

/**
 * Hiển thị liên kết quan hệ cho bài viết trước đó liền kề với bài viết hiện tại.
 *
 * @since 2.8.0
 *
 * @see get_adjacent_post_rel_link()
 *
 * @param string       $title          Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định true.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 */
function prev_post_rel_link( $title = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	echo get_adjacent_post_rel_link( $title, $in_same_term, $excluded_terms, true, $taxonomy );
}

/**
 * Lấy bài viết ở ranh giới.
 *
 * Ranh giới là bài viết đầu tiên hoặc cuối cùng theo ngày xuất bản trong phạm vi ràng buộc được chỉ định
 * bởi `$in_same_term` hoặc `$excluded_terms`.
 *
 * @since 2.8.0
 *
 * @param bool         $in_same_term   Tùy chọn. Bài viết trả về có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param bool         $start          Tùy chọn. Có lấy bài viết đầu tiên hay cuối cùng.
 *                                     Mặc định true.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return array|null Mảng chứa đối tượng bài viết ranh giới nếu thành công, null nếu ngược lại.
 */
function get_boundary_post( $in_same_term = false, $excluded_terms = '', $start = true, $taxonomy = 'category' ) {
	$post = get_post();

	if ( ! $post || ! is_single() || is_attachment() || ! taxonomy_exists( $taxonomy ) ) {
		return null;
	}

	$query_args = array(
		'posts_per_page'         => 1,
		'order'                  => $start ? 'ASC' : 'DESC',
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	$term_array = array();

	if ( ! is_array( $excluded_terms ) ) {
		if ( ! empty( $excluded_terms ) ) {
			$excluded_terms = explode( ',', $excluded_terms );
		} else {
			$excluded_terms = array();
		}
	}

	if ( $in_same_term || ! empty( $excluded_terms ) ) {
		if ( $in_same_term ) {
			$term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
		}

		if ( ! empty( $excluded_terms ) ) {
			$excluded_terms = array_map( 'intval', $excluded_terms );
			$excluded_terms = array_diff( $excluded_terms, $term_array );

			$inverse_terms = array();
			foreach ( $excluded_terms as $excluded_term ) {
				$inverse_terms[] = $excluded_term * -1;
			}
			$excluded_terms = $inverse_terms;
		}

		$query_args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'terms'    => array_merge( $term_array, $excluded_terms ),
			),
		);
	}

	return get_posts( $query_args );
}

/**
 * Lấy liên kết bài viết trước đó liền kề với bài viết hiện tại.
 *
 * @since 3.7.0
 *
 * @param string       $format         Tùy chọn. Định dạng neo liên kết. Mặc định '&laquo; %link'.
 * @param string       $link           Tùy chọn. Định dạng đường dẫn tĩnh liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return string URL liên kết của bài viết trước đó so với bài viết hiện tại.
 */
function get_previous_post_link( $format = '&laquo; %link', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	return get_adjacent_post_link( $format, $link, $in_same_term, $excluded_terms, true, $taxonomy );
}

/**
 * Hiển thị liên kết bài viết trước đó liền kề với bài viết hiện tại.
 *
 * @since 1.5.0
 *
 * @see get_previous_post_link()
 *
 * @param string       $format         Tùy chọn. Định dạng neo liên kết. Mặc định '&laquo; %link'.
 * @param string       $link           Tùy chọn. Định dạng đường dẫn tĩnh liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 */
function previous_post_link( $format = '&laquo; %link', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	echo get_previous_post_link( $format, $link, $in_same_term, $excluded_terms, $taxonomy );
}

/**
 * Lấy liên kết bài viết tiếp theo liền kề với bài viết hiện tại.
 *
 * @since 3.7.0
 *
 * @param string       $format         Tùy chọn. Định dạng neo liên kết. Mặc định '&laquo; %link'.
 * @param string       $link           Tùy chọn. Định dạng đường dẫn tĩnh liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return string URL liên kết của bài viết tiếp theo so với bài viết hiện tại.
 */
function get_next_post_link( $format = '%link &raquo;', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	return get_adjacent_post_link( $format, $link, $in_same_term, $excluded_terms, false, $taxonomy );
}

/**
 * Hiển thị liên kết bài viết tiếp theo liền kề với bài viết hiện tại.
 *
 * @since 1.5.0
 *
 * @see get_next_post_link()
 *
 * @param string       $format         Tùy chọn. Định dạng neo liên kết. Mặc định '&laquo; %link'.
 * @param string       $link           Tùy chọn. Định dạng đường dẫn tĩnh liên kết. Mặc định '%title'.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 */
function next_post_link( $format = '%link &raquo;', $link = '%title', $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	echo get_next_post_link( $format, $link, $in_same_term, $excluded_terms, $taxonomy );
}

/**
 * Lấy liên kết bài viết liền kề.
 *
 * Có thể là liên kết bài viết tiếp theo hoặc trước đó.
 *
 * @since 3.7.0
 *
 * @param string       $format         Định dạng neo liên kết.
 * @param string       $link           Định dạng đường dẫn tĩnh liên kết.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                     Mặc định rỗng.
 * @param bool         $previous       Tùy chọn. Có hiển thị liên kết đến bài viết trước hay tiếp theo.
 *                                     Mặc định true.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 * @return string URL liên kết của bài viết trước hoặc tiếp theo so với bài viết hiện tại.
 */
function get_adjacent_post_link( $format, $link, $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
	if ( $previous && is_attachment() ) {
		$post = get_post( get_post()->post_parent );
	} else {
		$post = get_adjacent_post( $in_same_term, $excluded_terms, $previous, $taxonomy );
	}

	if ( ! $post ) {
		$output = '';
	} else {
		$title = $post->post_title;

		if ( empty( $post->post_title ) ) {
			$title = $previous ? __( 'Previous Post' ) : __( 'Next Post' );
		}

		/** Bộ lọc này được ghi nhận trong wp-includes/post-template.php */
		$title = apply_filters( 'the_title', $title, $post->ID );

		$date = mysql2date( get_option( 'date_format' ), $post->post_date );
		$rel  = $previous ? 'prev' : 'next';

		$string = '<a href="' . get_permalink( $post ) . '" rel="' . $rel . '">';
		$inlink = str_replace( '%title', $title, $link );
		$inlink = str_replace( '%date', $date, $inlink );
		$inlink = $string . $inlink . '</a>';

		$output = str_replace( '%link', $inlink, $format );
	}

	$adjacent = $previous ? 'previous' : 'next';

	/**
	 * Lọc liên kết bài viết liền kề.
	 *
	 * Phần động của tên hook, `$adjacent`, tham chiếu đến loại
	 * liền kề, 'next' hoặc 'previous'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `next_post_link`
	 *  - `previous_post_link`
	 *
	 * @since 2.6.0
	 * @since 4.2.0 Thêm tham số `$adjacent`.
	 *
	 * @param string         $output   Liên kết bài viết liền kề.
	 * @param string         $format   Định dạng neo liên kết.
	 * @param string         $link     Định dạng đường dẫn tĩnh liên kết.
	 * @param WP_Post|string $post     Bài viết liền kề. Chuỗi rỗng nếu không có bài viết tương ứng.
	 * @param string         $adjacent Bài viết là trước hay tiếp theo.
	 */
	return apply_filters( "{$adjacent}_post_link", $output, $format, $link, $post, $adjacent );
}

/**
 * Hiển thị liên kết bài viết liền kề.
 *
 * Có thể là liên kết bài viết tiếp theo hoặc trước đó.
 *
 * @since 2.5.0
 *
 * @param string       $format         Định dạng neo liên kết.
 * @param string       $link           Định dạng đường dẫn tĩnh liên kết.
 * @param bool         $in_same_term   Tùy chọn. Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                     Mặc định false.
 * @param int[]|string $excluded_terms Tùy chọn. Mảng hoặc danh sách phân cách bằng dấu phẩy các ID chuyên mục bị loại trừ.
 *                                     Mặc định rỗng.
 * @param bool         $previous       Tùy chọn. Có hiển thị liên kết đến bài viết trước hay tiếp theo.
 *                                     Mặc định true.
 * @param string       $taxonomy       Tùy chọn. Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 */
function adjacent_post_link( $format, $link, $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
	echo get_adjacent_post_link( $format, $link, $in_same_term, $excluded_terms, $previous, $taxonomy );
}

/**
 * Lấy liên kết cho số trang.
 *
 * @since 1.5.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int  $pagenum Tùy chọn. Số trang. Mặc định 1.
 * @param bool $escape  Tùy chọn. Có thoát URL để hiển thị bằng esc_url() hay không.
 *                      Nếu đặt là false, chuẩn bị URL bằng sanitize_url(). Mặc định true.
 * @return string URL liên kết cho số trang được chỉ định.
 */
function get_pagenum_link( $pagenum = 1, $escape = true ) {
	global $wp_rewrite;

	$pagenum = (int) $pagenum;

	$request = remove_query_arg( 'paged' );

	$home_root = parse_url( home_url() );
	$home_root = ( isset( $home_root['path'] ) ) ? $home_root['path'] : '';
	$home_root = preg_quote( $home_root, '|' );

	$request = preg_replace( '|^' . $home_root . '|i', '', $request );
	$request = preg_replace( '|^/+|', '', $request );

	if ( ! $wp_rewrite->using_permalinks() || is_admin() ) {
		$base = trailingslashit( get_bloginfo( 'url' ) );

		if ( $pagenum > 1 ) {
			$result = add_query_arg( 'paged', $pagenum, $base . $request );
		} else {
			$result = $base . $request;
		}
	} else {
		$qs_regex = '|\?.*?$|';
		preg_match( $qs_regex, $request, $qs_match );

		$parts   = array();
		$parts[] = untrailingslashit( get_bloginfo( 'url' ) );

		if ( ! empty( $qs_match[0] ) ) {
			$query_string = $qs_match[0];
			$request      = preg_replace( $qs_regex, '', $request );
		} else {
			$query_string = '';
		}

		$request = preg_replace( "|$wp_rewrite->pagination_base/\d+/?$|", '', $request );
		$request = preg_replace( '|^' . preg_quote( $wp_rewrite->index, '|' ) . '|i', '', $request );
		$request = ltrim( $request, '/' );

		if ( $wp_rewrite->using_index_permalinks() && ( $pagenum > 1 || '' !== $request ) ) {
			$parts[] = $wp_rewrite->index;
		}

		$parts[] = untrailingslashit( $request );

		if ( $pagenum > 1 ) {
			$parts[] = $wp_rewrite->pagination_base;
			$parts[] = $pagenum;
		}

		$result = user_trailingslashit( implode( '/', array_filter( $parts ) ), 'paged' );
		if ( ! empty( $query_string ) ) {
			$result .= $query_string;
		}
	}

	/**
	 * Lọc liên kết số trang cho yêu cầu hiện tại.
	 *
	 * @since 2.5.0
	 * @since 5.2.0 Thêm tham số `$pagenum`.
	 *
	 * @param string $result  Liên kết số trang.
	 * @param int    $pagenum Số trang.
	 */
	$result = apply_filters( 'get_pagenum_link', $result, $pagenum );

	if ( $escape ) {
		return esc_url( $result );
	} else {
		return sanitize_url( $result );
	}
}

/**
 * Lấy liên kết trang bài viết tiếp theo.
 *
 * Được chuyển ngược từ 2.1.3 về 2.0.10.
 *
 * @since 2.0.10
 *
 * @global int $paged
 *
 * @param int $max_page Tùy chọn. Số trang tối đa. Mặc định 0.
 * @return string|void URL liên kết cho trang bài viết tiếp theo.
 */
function get_next_posts_page_link( $max_page = 0 ) {
	global $paged;

	if ( ! is_single() ) {
		if ( ! $paged ) {
			$paged = 1;
		}

		$next_page = (int) $paged + 1;

		if ( ! $max_page || $max_page >= $next_page ) {
			return get_pagenum_link( $next_page );
		}
	}
}

/**
 * Hiển thị hoặc lấy liên kết trang bài viết tiếp theo.
 *
 * @since 0.71
 *
 * @param int  $max_page Tùy chọn. Số trang tối đa. Mặc định 0.
 * @param bool $display  Tùy chọn. Có echo liên kết hay không. Mặc định true.
 * @return string|void URL liên kết cho trang bài viết tiếp theo nếu `$display = false`.
 */
function next_posts( $max_page = 0, $display = true ) {
	$link   = get_next_posts_page_link( $max_page );
	$output = $link ? esc_url( $link ) : '';

	if ( $display ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Lấy liên kết trang bài viết tiếp theo.
 *
 * @since 2.7.0
 *
 * @global int      $paged
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 *
 * @param string $label    Nội dung cho văn bản liên kết.
 * @param int    $max_page Tùy chọn. Số trang tối đa. Mặc định 0.
 * @return string|void Liên kết trang bài viết tiếp theo được định dạng HTML.
 */
function get_next_posts_link( $label = null, $max_page = 0 ) {
	global $paged, $wp_query;

	if ( ! $max_page ) {
		$max_page = $wp_query->max_num_pages;
	}

	if ( ! $paged ) {
		$paged = 1;
	}

	$next_page = (int) $paged + 1;

	if ( null === $label ) {
		$label = __( 'Next Page &raquo;' );
	}

	if ( ! is_single() && ( $next_page <= $max_page ) ) {
		/**
		 * Lọc thuộc tính thẻ neo cho liên kết trang bài viết tiếp theo.
		 *
		 * @since 2.7.0
		 *
		 * @param string $attributes Thuộc tính cho thẻ neo.
		 */
		$attr = apply_filters( 'next_posts_link_attributes', '' );

		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			next_posts( $max_page, false ),
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
	}
}

/**
 * Hiển thị liên kết trang bài viết tiếp theo.
 *
 * @since 0.71
 *
 * @param string $label    Nội dung cho văn bản liên kết.
 * @param int    $max_page Tùy chọn. Số trang tối đa. Mặc định 0.
 */
function next_posts_link( $label = null, $max_page = 0 ) {
	echo get_next_posts_link( $label, $max_page );
}

/**
 * Lấy liên kết trang bài viết trước đó.
 *
 * Chỉ trả về chuỗi nếu không phải trên trang đơn hoặc bài viết đơn.
 *
 * Được chuyển ngược về 2.0.10 từ 2.1.3.
 *
 * @since 2.0.10
 *
 * @global int $paged
 *
 * @return string|void Liên kết cho trang bài viết trước đó.
 */
function get_previous_posts_page_link() {
	global $paged;

	if ( ! is_single() ) {
		$previous_page = (int) $paged - 1;

		if ( $previous_page < 1 ) {
			$previous_page = 1;
		}

		return get_pagenum_link( $previous_page );
	}
}

/**
 * Hiển thị hoặc lấy liên kết trang bài viết trước đó.
 *
 * @since 0.71
 *
 * @param bool $display Tùy chọn. Có echo liên kết hay không. Mặc định true.
 * @return string|void Liên kết trang bài viết trước đó nếu `$display = false`.
 */
function previous_posts( $display = true ) {
	$output = esc_url( get_previous_posts_page_link() );

	if ( $display ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Lấy liên kết trang bài viết trước đó.
 *
 * @since 2.7.0
 *
 * @global int $paged
 *
 * @param string $label Tùy chọn. Văn bản liên kết trang trước.
 * @return string|void Liên kết trang trước được định dạng HTML.
 */
function get_previous_posts_link( $label = null ) {
	global $paged;

	if ( null === $label ) {
		$label = __( '&laquo; Previous Page' );
	}

	if ( ! is_single() && $paged > 1 ) {
		/**
		 * Lọc thuộc tính thẻ neo cho liên kết trang bài viết trước đó.
		 *
		 * @since 2.7.0
		 *
		 * @param string $attributes Thuộc tính cho thẻ neo.
		 */
		$attr = apply_filters( 'previous_posts_link_attributes', '' );

		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			previous_posts( false ),
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
	}
}

/**
 * Hiển thị liên kết trang bài viết trước đó.
 *
 * @since 0.71
 *
 * @param string $label Tùy chọn. Văn bản liên kết trang trước.
 */
function previous_posts_link( $label = null ) {
	echo get_previous_posts_link( $label );
}

/**
 * Lấy điều hướng liên kết trang bài viết cho trang trước và trang tiếp theo.
 *
 * @since 2.8.0
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 *
 * @param string|array $args {
 *     Tùy chọn. Các tham số để xây dựng điều hướng liên kết trang bài viết.
 *
 *     @type string $sep      Ký tự phân cách. Mặc định '&#8212;'.
 *     @type string $prelabel Văn bản liên kết hiển thị cho liên kết trang trước.
 *                            Mặc định '&laquo; Previous Page'.
 *     @type string $nxtlabel Văn bản liên kết hiển thị cho liên kết trang tiếp theo.
 *                            Mặc định 'Next Page &raquo;'.
 * }
 * @return string Điều hướng liên kết bài viết.
 */
function get_posts_nav_link( $args = array() ) {
	global $wp_query;

	$return = '';

	if ( ! is_singular() ) {
		$defaults = array(
			'sep'      => ' &#8212; ',
			'prelabel' => __( '&laquo; Previous Page' ),
			'nxtlabel' => __( 'Next Page &raquo;' ),
		);
		$args     = wp_parse_args( $args, $defaults );

		$max_num_pages = $wp_query->max_num_pages;
		$paged         = get_query_var( 'paged' );

		// Chỉ có dấu phân cách nếu có cả kết quả trước và tiếp theo.
		if ( $paged < 2 || $paged >= $max_num_pages ) {
			$args['sep'] = '';
		}

		if ( $max_num_pages > 1 ) {
			$return  = get_previous_posts_link( $args['prelabel'] );
			$return .= preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $args['sep'] );
			$return .= get_next_posts_link( $args['nxtlabel'] );
		}
	}
	return $return;
}

/**
 * Hiển thị điều hướng liên kết trang bài viết cho trang trước và trang tiếp theo.
 *
 * @since 0.71
 *
 * @param string $sep      Tùy chọn. Dấu phân cách cho liên kết điều hướng bài viết. Mặc định rỗng.
 * @param string $prelabel Tùy chọn. Nhãn cho trang trước. Mặc định rỗng.
 * @param string $nxtlabel Tùy chọn. Nhãn cho trang tiếp theo. Mặc định rỗng.
 */
function posts_nav_link( $sep = '', $prelabel = '', $nxtlabel = '' ) {
	$args = array_filter( compact( 'sep', 'prelabel', 'nxtlabel' ) );
	echo get_posts_nav_link( $args );
}

/**
 * Lấy điều hướng đến bài viết tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.1.0
 * @since 4.4.0 Giới thiệu các tham số `in_same_term`, `excluded_terms`, và `taxonomy`.
 * @since 5.3.0 Thêm tham số `aria_label`.
 * @since 5.5.0 Thêm tham số `class`.
 *
 * @param array $args {
 *     Tùy chọn. Các tham số điều hướng bài viết mặc định. Mặc định mảng rỗng.
 *
 *     @type string       $prev_text          Văn bản neo hiển thị trong liên kết bài viết trước.
 *                                            Mặc định '%title'.
 *     @type string       $next_text          Văn bản neo hiển thị trong liên kết bài viết tiếp theo.
 *                                            Mặc định '%title'.
 *     @type bool         $in_same_term       Liên kết có nên thuộc cùng thuật ngữ phân loại hay không.
 *                                            Mặc định false.
 *     @type int[]|string $excluded_terms     Mảng hoặc danh sách phân cách bằng dấu phẩy các ID thuật ngữ bị loại trừ.
 *                                            Mặc định rỗng.
 *     @type string       $taxonomy           Phân loại, nếu `$in_same_term` là true. Mặc định 'category'.
 *     @type string       $screen_reader_text Văn bản đọc màn hình cho phần tử nav.
 *                                            Mặc định 'Post navigation'.
 *     @type string       $aria_label         Văn bản nhãn ARIA cho phần tử nav. Mặc định 'Posts'.
 *     @type string       $class              Class tùy chỉnh cho phần tử nav. Mặc định 'post-navigation'.
 * }
 * @return string Markup cho liên kết bài viết.
 */
function get_the_post_navigation( $args = array() ) {
	// Đảm bảo phần tử nav có thuộc tính aria-label: dự phòng bằng văn bản đọc màn hình.
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}

	$args = wp_parse_args(
		$args,
		array(
			'prev_text'          => '%title',
			'next_text'          => '%title',
			'in_same_term'       => false,
			'excluded_terms'     => '',
			'taxonomy'           => 'category',
			'screen_reader_text' => __( 'Post navigation' ),
			'aria_label'         => __( 'Posts' ),
			'class'              => 'post-navigation',
		)
	);

	$navigation = '';

	$previous = get_previous_post_link(
		'<div class="nav-previous">%link</div>',
		$args['prev_text'],
		$args['in_same_term'],
		$args['excluded_terms'],
		$args['taxonomy']
	);

	$next = get_next_post_link(
		'<div class="nav-next">%link</div>',
		$args['next_text'],
		$args['in_same_term'],
		$args['excluded_terms'],
		$args['taxonomy']
	);

	// Chỉ thêm markup nếu có nơi để điều hướng đến.
	if ( $previous || $next ) {
		$navigation = _navigation_markup( $previous . $next, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	}

	return $navigation;
}

/**
 * Hiển thị điều hướng đến bài viết tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.1.0
 *
 * @param array $args Tùy chọn. Xem get_the_post_navigation() để biết các tham số có sẵn.
 *                    Mặc định mảng rỗng.
 */
function the_post_navigation( $args = array() ) {
	echo get_the_post_navigation( $args );
}

/**
 * Trả về điều hướng đến tập bài viết tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.1.0
 * @since 5.3.0 Thêm tham số `aria_label`.
 * @since 5.5.0 Thêm tham số `class`.
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 *
 * @param array $args {
 *     Tùy chọn. Các tham số điều hướng bài viết mặc định. Mặc định mảng rỗng.
 *
 *     @type string $prev_text          Văn bản neo hiển thị trong liên kết trang bài viết trước.
 *                                      Mặc định 'Older posts'.
 *     @type string $next_text          Văn bản neo hiển thị trong liên kết trang bài viết tiếp theo.
 *                                      Mặc định 'Newer posts'.
 *     @type string $screen_reader_text Văn bản đọc màn hình cho phần tử nav.
 *                                      Mặc định 'Posts navigation'.
 *     @type string $aria_label         Văn bản nhãn ARIA cho phần tử nav. Mặc định 'Posts'.
 *     @type string $class              Class tùy chỉnh cho phần tử nav. Mặc định 'posts-navigation'.
 * }
 * @return string Markup cho liên kết bài viết.
 */
function get_the_posts_navigation( $args = array() ) {
	global $wp_query;

	$navigation = '';

	// Không in markup rỗng nếu chỉ có một trang.
	if ( $wp_query->max_num_pages > 1 ) {
		// Đảm bảo phần tử nav có thuộc tính aria-label: dự phòng bằng văn bản đọc màn hình.
		if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
			$args['aria_label'] = $args['screen_reader_text'];
		}

		$args = wp_parse_args(
			$args,
			array(
				'prev_text'          => __( 'Older posts' ),
				'next_text'          => __( 'Newer posts' ),
				'screen_reader_text' => __( 'Posts navigation' ),
				'aria_label'         => __( 'Posts' ),
				'class'              => 'posts-navigation',
			)
		);

		$next_link = get_previous_posts_link( $args['next_text'] );
		$prev_link = get_next_posts_link( $args['prev_text'] );

		if ( $prev_link ) {
			$navigation .= '<div class="nav-previous">' . $prev_link . '</div>';
		}

		if ( $next_link ) {
			$navigation .= '<div class="nav-next">' . $next_link . '</div>';
		}

		$navigation = _navigation_markup( $navigation, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	}

	return $navigation;
}

/**
 * Hiển thị điều hướng đến tập bài viết tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.1.0
 *
 * @param array $args Tùy chọn. Xem get_the_posts_navigation() để biết các tham số có sẵn.
 *                    Mặc định mảng rỗng.
 */
function the_posts_navigation( $args = array() ) {
	echo get_the_posts_navigation( $args );
}

/**
 * Lấy điều hướng phân trang đến tập bài viết tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.1.0
 * @since 5.3.0 Thêm tham số `aria_label`.
 * @since 5.5.0 Thêm tham số `class`.
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 *
 * @param array $args {
 *     Tùy chọn. Các tham số phân trang mặc định, xem paginate_links().
 *
 *     @type string $screen_reader_text Văn bản đọc màn hình cho phần tử điều hướng.
 *                                      Mặc định 'Posts pagination'.
 *     @type string $aria_label         Văn bản nhãn ARIA cho phần tử nav. Mặc định 'Posts pagination'.
 *     @type string $class              Class tùy chỉnh cho phần tử nav. Mặc định 'pagination'.
 * }
 * @return string Markup cho liên kết phân trang.
 */
function get_the_posts_pagination( $args = array() ) {
	global $wp_query;

	$navigation = '';

	// Không in markup rỗng nếu chỉ có một trang.
	if ( $wp_query->max_num_pages > 1 ) {
		// Đảm bảo phần tử nav có thuộc tính aria-label: dự phòng bằng văn bản đọc màn hình.
		if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
			$args['aria_label'] = $args['screen_reader_text'];
		}

		$args = wp_parse_args(
			$args,
			array(
				'mid_size'           => 1,
				'prev_text'          => _x( 'Previous', 'previous set of posts' ),
				'next_text'          => _x( 'Next', 'next set of posts' ),
				'screen_reader_text' => __( 'Posts pagination' ),
				'aria_label'         => __( 'Posts pagination' ),
				'class'              => 'pagination',
			)
		);

		/**
		 * Lọc các tham số cho liên kết phân trang bài viết.
		 *
		 * @since 6.1.0
		 *
		 * @param array $args {
		 *     Tùy chọn. Các tham số phân trang mặc định, xem paginate_links().
		 *
		 *     @type string $screen_reader_text Văn bản đọc màn hình cho phần tử điều hướng.
		 *                                      Mặc định 'Posts navigation'.
		 *     @type string $aria_label         Văn bản nhãn ARIA cho phần tử nav. Mặc định 'Posts'.
		 *     @type string $class              Class tùy chỉnh cho phần tử nav. Mặc định 'pagination'.
		 * }
		 */
		$args = apply_filters( 'the_posts_pagination_args', $args );

		// Đảm bảo nhận lại chuỗi. Plain là lựa chọn tốt nhất tiếp theo.
		if ( isset( $args['type'] ) && 'array' === $args['type'] ) {
			$args['type'] = 'plain';
		}

		// Thiết lập liên kết phân trang.
		$links = paginate_links( $args );

		if ( $links ) {
			$navigation = _navigation_markup( $links, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
		}
	}

	return $navigation;
}

/**
 * Hiển thị điều hướng phân trang đến tập bài viết tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.1.0
 *
 * @param array $args Tùy chọn. Xem get_the_posts_pagination() để biết các tham số có sẵn.
 *                    Mặc định mảng rỗng.
 */
function the_posts_pagination( $args = array() ) {
	echo get_the_posts_pagination( $args );
}

/**
 * Bọc các liên kết được truyền vào trong markup điều hướng.
 *
 * @since 4.1.0
 * @since 5.3.0 Thêm tham số `aria_label`.
 * @access private
 *
 * @param string $links              Các liên kết điều hướng.
 * @param string $css_class          Tùy chọn. Class tùy chỉnh cho phần tử nav.
 *                                   Mặc định 'posts-navigation'.
 * @param string $screen_reader_text Tùy chọn. Văn bản đọc màn hình cho phần tử nav.
 *                                   Mặc định 'Posts navigation'.
 * @param string $aria_label         Tùy chọn. Nhãn ARIA cho phần tử nav.
 *                                   Mặc định là giá trị của `$screen_reader_text`.
 * @return string Thẻ mẫu điều hướng.
 */
function _navigation_markup( $links, $css_class = 'posts-navigation', $screen_reader_text = '', $aria_label = '' ) {
	if ( empty( $screen_reader_text ) ) {
		$screen_reader_text = /* translators: Hidden accessibility text. */ __( 'Posts navigation' );
	}
	if ( empty( $aria_label ) ) {
		$aria_label = $screen_reader_text;
	}

	$template = '
	<nav class="navigation %1$s" aria-label="%4$s">
		<h2 class="screen-reader-text">%2$s</h2>
		<div class="nav-links">%3$s</div>
	</nav>';

	/**
	 * Lọc mẫu markup điều hướng.
	 *
	 * Lưu ý: HTML mẫu được lọc phải chứa các chỉ định cho class điều hướng (%1$s),
	 * giá trị screen-reader-text (%2$s), vị trí đặt liên kết điều hướng (%3$s),
	 * và văn bản nhãn ARIA nếu screen-reader-text không phù hợp (%4$s):
	 *
	 *     <nav class="navigation %1$s" aria-label="%4$s">
	 *         <h2 class="screen-reader-text">%2$s</h2>
	 *         <div class="nav-links">%3$s</div>
	 *     </nav>
	 *
	 * @since 4.4.0
	 *
	 * @param string $template  Mẫu mặc định.
	 * @param string $css_class Class được truyền bởi hàm gọi.
	 */
	$template = apply_filters( 'navigation_markup_template', $template, $css_class );

	return sprintf( $template, sanitize_html_class( $css_class ), esc_html( $screen_reader_text ), $links, esc_attr( $aria_label ) );
}

/**
 * Lấy liên kết số trang bình luận.
 *
 * @since 2.7.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int $pagenum  Tùy chọn. Số trang. Mặc định 1.
 * @param int $max_page Tùy chọn. Số trang bình luận tối đa. Mặc định 0.
 * @return string URL liên kết số trang bình luận.
 */
function get_comments_pagenum_link( $pagenum = 1, $max_page = 0 ) {
	global $wp_rewrite;

	$pagenum  = (int) $pagenum;
	$max_page = (int) $max_page;

	$result = get_permalink();

	if ( 'newest' === get_option( 'default_comments_page' ) ) {
		if ( $pagenum !== $max_page ) {
			if ( $wp_rewrite->using_permalinks() ) {
				$result = user_trailingslashit( trailingslashit( $result ) . $wp_rewrite->comments_pagination_base . '-' . $pagenum, 'commentpaged' );
			} else {
				$result = add_query_arg( 'cpage', $pagenum, $result );
			}
		}
	} elseif ( $pagenum > 1 ) {
		if ( $wp_rewrite->using_permalinks() ) {
			$result = user_trailingslashit( trailingslashit( $result ) . $wp_rewrite->comments_pagination_base . '-' . $pagenum, 'commentpaged' );
		} else {
			$result = add_query_arg( 'cpage', $pagenum, $result );
		}
	}

	$result .= '#comments';

	/**
	 * Lọc liên kết số trang bình luận cho yêu cầu hiện tại.
	 *
	 * @since 2.7.0
	 *
	 * @param string $result Liên kết số trang bình luận.
	 */
	return apply_filters( 'get_comments_pagenum_link', $result );
}

/**
 * Lấy liên kết đến trang bình luận tiếp theo.
 *
 * @since 2.7.1
 * @since 6.7.0 Thêm tham số `page`.
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 *
 * @param string   $label    Tùy chọn. Nhãn cho văn bản liên kết. Mặc định rỗng.
 * @param int      $max_page Tùy chọn. Trang tối đa. Mặc định 0.
 * @param int|null $page     Tùy chọn. Số trang. Mặc định null.
 * @return string|void Liên kết định dạng HTML cho trang bình luận tiếp theo.
 */
function get_next_comments_link( $label = '', $max_page = 0, $page = null ) {
	global $wp_query;

	if ( ! is_singular() ) {
		return;
	}

	if ( is_null( $page ) ) {
		$page = get_query_var( 'cpage' );
	}

	if ( ! $page ) {
		$page = 1;
	}

	$next_page = (int) $page + 1;

	if ( empty( $max_page ) ) {
		$max_page = $wp_query->max_num_comment_pages;
	}

	if ( empty( $max_page ) ) {
		$max_page = get_comment_pages_count();
	}

	if ( $next_page > $max_page ) {
		return;
	}

	if ( empty( $label ) ) {
		$label = __( 'Newer Comments &raquo;' );
	}

	/**
	 * Lọc thuộc tính thẻ neo cho liên kết trang bình luận tiếp theo.
	 *
	 * @since 2.7.0
	 *
	 * @param string $attributes Thuộc tính cho thẻ neo.
	 */
	$attr = apply_filters( 'next_comments_link_attributes', '' );

	return sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( get_comments_pagenum_link( $next_page, $max_page ) ),
		$attr,
		preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
	);
}

/**
 * Hiển thị liên kết đến trang bình luận tiếp theo.
 *
 * @since 2.7.0
 *
 * @param string $label    Tùy chọn. Nhãn cho văn bản liên kết. Mặc định rỗng.
 * @param int    $max_page Tùy chọn. Trang tối đa. Mặc định 0.
 */
function next_comments_link( $label = '', $max_page = 0 ) {
	echo get_next_comments_link( $label, $max_page );
}

/**
 * Lấy liên kết đến trang bình luận trước đó.
 *
 * @since 2.7.1
 * @since 6.7.0 Thêm tham số `page`.
 *
 * @param string   $label Tùy chọn. Nhãn cho văn bản liên kết bình luận. Mặc định rỗng.
 * @param int|null $page  Tùy chọn. Số trang. Mặc định null.
 * @return string|void Liên kết định dạng HTML cho trang bình luận trước đó.
 */
function get_previous_comments_link( $label = '', $page = null ) {
	if ( ! is_singular() ) {
		return;
	}

	if ( is_null( $page ) ) {
		$page = get_query_var( 'cpage' );
	}

	if ( (int) $page <= 1 ) {
		return;
	}

	$previous_page = (int) $page - 1;

	if ( empty( $label ) ) {
		$label = __( '&laquo; Older Comments' );
	}

	/**
	 * Lọc thuộc tính thẻ neo cho liên kết trang bình luận trước đó.
	 *
	 * @since 2.7.0
	 *
	 * @param string $attributes Thuộc tính cho thẻ neo.
	 */
	$attr = apply_filters( 'previous_comments_link_attributes', '' );

	return sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( get_comments_pagenum_link( $previous_page ) ),
		$attr,
		preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
	);
}

/**
 * Hiển thị liên kết đến trang bình luận trước đó.
 *
 * @since 2.7.0
 *
 * @param string $label Tùy chọn. Nhãn cho văn bản liên kết bình luận. Mặc định rỗng.
 */
function previous_comments_link( $label = '' ) {
	echo get_previous_comments_link( $label );
}

/**
 * Hiển thị hoặc lấy liên kết phân trang cho bình luận trên bài viết hiện tại.
 *
 * @see paginate_links()
 * @since 2.7.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param string|array $args Tham số tùy chọn. Xem paginate_links(). Mặc định mảng rỗng.
 * @return void|string|array Void nếu tham số 'echo' là true và 'type' không phải mảng,
 *                           hoặc nếu truy vấn không phải cho bài viết đơn hiện có thuộc bất kỳ loại bài viết nào.
 *                           Nếu không, markup cho liên kết trang bình luận hoặc mảng liên kết trang bình luận,
 *                           tùy thuộc vào tham số 'type'.
 */
function paginate_comments_links( $args = array() ) {
	global $wp_rewrite;

	if ( ! is_singular() ) {
		return;
	}

	$page = get_query_var( 'cpage' );
	if ( ! $page ) {
		$page = 1;
	}
	$max_page = get_comment_pages_count();
	$defaults = array(
		'base'         => add_query_arg( 'cpage', '%#%' ),
		'format'       => '',
		'total'        => $max_page,
		'current'      => $page,
		'echo'         => true,
		'type'         => 'plain',
		'add_fragment' => '#comments',
	);
	if ( $wp_rewrite->using_permalinks() ) {
		$defaults['base'] = user_trailingslashit( trailingslashit( get_permalink() ) . $wp_rewrite->comments_pagination_base . '-%#%', 'commentpaged' );
	}

	$args       = wp_parse_args( $args, $defaults );
	$page_links = paginate_links( $args );

	if ( $args['echo'] && 'array' !== $args['type'] ) {
		echo $page_links;
	} else {
		return $page_links;
	}
}

/**
 * Lấy điều hướng đến tập bình luận tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.4.0
 * @since 5.3.0 Thêm tham số `aria_label`.
 * @since 5.5.0 Thêm tham số `class`.
 *
 * @param array $args {
 *     Tùy chọn. Các tham số điều hướng bình luận mặc định.
 *
 *     @type string $prev_text          Văn bản neo hiển thị trong liên kết bình luận trước.
 *                                      Mặc định 'Older comments'.
 *     @type string $next_text          Văn bản neo hiển thị trong liên kết bình luận tiếp theo.
 *                                      Mặc định 'Newer comments'.
 *     @type string $screen_reader_text Văn bản đọc màn hình cho phần tử nav. Mặc định 'Comments navigation'.
 *     @type string $aria_label         Văn bản nhãn ARIA cho phần tử nav. Mặc định 'Comments'.
 *     @type string $class              Class tùy chỉnh cho phần tử nav. Mặc định 'comment-navigation'.
 * }
 * @return string Markup cho liên kết bình luận.
 */
function get_the_comments_navigation( $args = array() ) {
	$navigation = '';

	// Có bình luận để điều hướng qua không?
	if ( get_comment_pages_count() > 1 ) {
		// Đảm bảo phần tử nav có thuộc tính aria-label: dự phòng bằng văn bản đọc màn hình.
		if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
			$args['aria_label'] = $args['screen_reader_text'];
		}

		$args = wp_parse_args(
			$args,
			array(
				'prev_text'          => __( 'Older comments' ),
				'next_text'          => __( 'Newer comments' ),
				'screen_reader_text' => __( 'Comments navigation' ),
				'aria_label'         => __( 'Comments' ),
				'class'              => 'comment-navigation',
			)
		);

		$prev_link = get_previous_comments_link( $args['prev_text'] );
		$next_link = get_next_comments_link( $args['next_text'] );

		if ( $prev_link ) {
			$navigation .= '<div class="nav-previous">' . $prev_link . '</div>';
		}

		if ( $next_link ) {
			$navigation .= '<div class="nav-next">' . $next_link . '</div>';
		}

		$navigation = _navigation_markup( $navigation, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	}

	return $navigation;
}

/**
 * Hiển thị điều hướng đến tập bình luận tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.4.0
 *
 * @param array $args Xem get_the_comments_navigation() để biết các tham số có sẵn. Mặc định mảng rỗng.
 */
function the_comments_navigation( $args = array() ) {
	echo get_the_comments_navigation( $args );
}

/**
 * Lấy điều hướng phân trang đến tập bình luận tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.4.0
 * @since 5.3.0 Thêm tham số `aria_label`.
 * @since 5.5.0 Thêm tham số `class`.
 *
 * @see paginate_comments_links()
 *
 * @param array $args {
 *     Tùy chọn. Các tham số phân trang mặc định.
 *
 *     @type string $screen_reader_text Văn bản đọc màn hình cho phần tử nav. Mặc định 'Comments pagination'.
 *     @type string $aria_label         Văn bản nhãn ARIA cho phần tử nav. Mặc định 'Comments pagination'.
 *     @type string $class              Class tùy chỉnh cho phần tử nav. Mặc định 'comments-pagination'.
 * }
 * @return string Markup cho liên kết phân trang.
 */
function get_the_comments_pagination( $args = array() ) {
	$navigation = '';

	// Đảm bảo phần tử nav có thuộc tính aria-label: dự phòng bằng văn bản đọc màn hình.
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}

	$args         = wp_parse_args(
		$args,
		array(
			'screen_reader_text' => __( 'Comments pagination' ),
			'aria_label'         => __( 'Comments pagination' ),
			'class'              => 'comments-pagination',
		)
	);
	$args['echo'] = false;

	// Đảm bảo nhận lại chuỗi. Plain là lựa chọn tốt nhất tiếp theo.
	if ( isset( $args['type'] ) && 'array' === $args['type'] ) {
		$args['type'] = 'plain';
	}

	$links = paginate_comments_links( $args );

	if ( $links ) {
		$navigation = _navigation_markup( $links, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	}

	return $navigation;
}

/**
 * Hiển thị điều hướng phân trang đến tập bình luận tiếp theo/trước đó, khi áp dụng được.
 *
 * @since 4.4.0
 *
 * @param array $args Xem get_the_comments_pagination() để biết các tham số có sẵn. Mặc định mảng rỗng.
 */
function the_comments_pagination( $args = array() ) {
	echo get_the_comments_pagination( $args );
}

/**
 * Lấy URL cho trang web hiện tại nơi giao diện công khai có thể truy cập.
 *
 * Trả về tùy chọn 'home' với giao thức phù hợp. Giao thức sẽ là 'https'
 * nếu is_ssl() trả về true; nếu không, sẽ giống như tùy chọn 'home'.
 * Nếu `$scheme` là 'http' hoặc 'https', is_ssl() sẽ bị ghi đè.
 *
 * @since 3.0.0
 *
 * @param string      $path   Tùy chọn. Đường dẫn tương đối so với URL trang chủ. Mặc định rỗng.
 * @param string|null $scheme Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL trang chủ. Chấp nhận
 *                            'http', 'https', 'relative', 'rest', hoặc null. Mặc định null.
 * @return string Liên kết URL trang chủ với đường dẫn tùy chọn được nối thêm.
 */
function home_url( $path = '', $scheme = null ) {
	return get_home_url( null, $path, $scheme );
}

/**
 * Lấy URL cho trang web được chỉ định nơi giao diện công khai có thể truy cập.
 *
 * Trả về tùy chọn 'home' với giao thức phù hợp. Giao thức sẽ là 'https'
 * nếu is_ssl() trả về true; nếu không, sẽ giống như tùy chọn 'home'.
 * Nếu `$scheme` là 'http' hoặc 'https', is_ssl() sẽ bị ghi đè.
 *
 * @since 3.0.0
 *
 * @param int|null    $blog_id Tùy chọn. ID trang web. Mặc định null (trang web hiện tại).
 * @param string      $path    Tùy chọn. Đường dẫn tương đối so với URL trang chủ. Mặc định rỗng.
 * @param string|null $scheme  Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL trang chủ. Chấp nhận
 *                             'http', 'https', 'relative', 'rest', hoặc null. Mặc định null.
 * @return string Liên kết URL trang chủ với đường dẫn tùy chọn được nối thêm.
 */
function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
	$orig_scheme = $scheme;

	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'home' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'home' );
		restore_current_blog();
	}

	if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {
		if ( is_ssl() ) {
			$scheme = 'https';
		} else {
			$scheme = parse_url( $url, PHP_URL_SCHEME );
		}
	}

	$url = set_url_scheme( $url, $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Lọc URL trang chủ.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $url         URL trang chủ đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path        Đường dẫn tương đối so với URL trang chủ. Chuỗi rỗng nếu không có đường dẫn.
	 * @param string|null $orig_scheme Giao thức để cung cấp ngữ cảnh cho URL trang chủ. Chấp nhận 'http', 'https',
	 *                                 'relative', 'rest', hoặc null.
	 * @param int|null    $blog_id     ID trang web, hoặc null cho trang web hiện tại.
	 */
	return apply_filters( 'home_url', $url, $path, $orig_scheme, $blog_id );
}

/**
 * Lấy URL cho trang web hiện tại nơi các tệp ứng dụng WordPress
 * (ví dụ: wp-blog-header.php hoặc thư mục wp-admin/) có thể truy cập.
 *
 * Trả về tùy chọn 'site_url' với giao thức phù hợp, 'https' nếu
 * is_ssl() và 'http' nếu không. Nếu $scheme là 'http' hoặc 'https', is_ssl() sẽ bị ghi đè.
 *
 * @since 3.0.0
 *
 * @param string      $path   Tùy chọn. Đường dẫn tương đối so với URL trang web. Mặc định rỗng.
 * @param string|null $scheme Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL trang web. Xem set_url_scheme().
 * @return string Liên kết URL trang web với đường dẫn tùy chọn được nối thêm.
 */
function site_url( $path = '', $scheme = null ) {
	return get_site_url( null, $path, $scheme );
}

/**
 * Lấy URL cho trang web được chỉ định nơi các tệp ứng dụng WordPress
 * (ví dụ: wp-blog-header.php hoặc thư mục wp-admin/) có thể truy cập.
 *
 * Trả về tùy chọn 'site_url' với giao thức phù hợp, 'https' nếu
 * is_ssl() và 'http' nếu không. Nếu `$scheme` là 'http' hoặc 'https',
 * `is_ssl()` sẽ bị ghi đè.
 *
 * @since 3.0.0
 *
 * @param int|null    $blog_id Tùy chọn. ID trang web. Mặc định null (trang web hiện tại).
 * @param string      $path    Tùy chọn. Đường dẫn tương đối so với URL trang web. Mặc định rỗng.
 * @param string|null $scheme  Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL trang web. Chấp nhận
 *                             'http', 'https', 'login', 'login_post', 'admin', hoặc
 *                             'relative'. Mặc định null.
 * @return string Liên kết URL trang web với đường dẫn tùy chọn được nối thêm.
 */
function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'siteurl' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'siteurl' );
		restore_current_blog();
	}

	$url = set_url_scheme( $url, $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Lọc URL trang web.
	 *
	 * @since 2.7.0
	 *
	 * @param string      $url     URL trang web đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path    Đường dẫn tương đối so với URL trang web. Chuỗi rỗng nếu không có đường dẫn.
	 * @param string|null $scheme  Giao thức để cung cấp ngữ cảnh cho URL trang web. Chấp nhận 'http', 'https', 'login',
	 *                             'login_post', 'admin', 'relative' hoặc null.
	 * @param int|null    $blog_id ID trang web, hoặc null cho trang web hiện tại.
	 */
	return apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
}

/**
 * Lấy URL đến khu vực quản trị cho trang web hiện tại.
 *
 * @since 2.6.0
 *
 * @param string $path   Tùy chọn. Đường dẫn tương đối so với URL quản trị. Mặc định rỗng.
 * @param string $scheme Giao thức sử dụng. Mặc định là 'admin', tuân theo force_ssl_admin() và is_ssl().
 *                       Có thể truyền 'http' hoặc 'https' để buộc các giao thức đó.
 * @return string Liên kết URL quản trị với đường dẫn tùy chọn được nối thêm.
 */
function admin_url( $path = '', $scheme = 'admin' ) {
	return get_admin_url( null, $path, $scheme );
}

/**
 * Lấy URL đến khu vực quản trị cho trang web được chỉ định.
 *
 * @since 3.0.0
 *
 * @param int|null $blog_id Tùy chọn. ID trang web. Mặc định null (trang web hiện tại).
 * @param string   $path    Tùy chọn. Đường dẫn tương đối so với URL quản trị. Mặc định rỗng.
 * @param string   $scheme  Tùy chọn. Giao thức sử dụng. Chấp nhận 'http' hoặc 'https',
 *                          để buộc các giao thức đó. Mặc định 'admin', tuân theo
 *                          force_ssl_admin() và is_ssl().
 * @return string Liên kết URL quản trị với đường dẫn tùy chọn được nối thêm.
 */
function get_admin_url( $blog_id = null, $path = '', $scheme = 'admin' ) {
	$url = get_site_url( $blog_id, 'wp-admin/', $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	/**
	 * Lọc URL khu vực quản trị.
	 *
	 * @since 2.8.0
	 * @since 5.8.0 Thêm tham số `$scheme`.
	 *
	 * @param string      $url     URL khu vực quản trị đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path    Đường dẫn tương đối so với URL khu vực quản trị. Chuỗi rỗng nếu không có đường dẫn.
	 * @param int|null    $blog_id ID trang web, hoặc null cho trang web hiện tại.
	 * @param string|null $scheme  Giao thức sử dụng. Chấp nhận 'http', 'https',
	 *                             'admin', hoặc null. Mặc định 'admin', tuân theo force_ssl_admin() và is_ssl().
	 */
	return apply_filters( 'admin_url', $url, $path, $blog_id, $scheme );
}

/**
 * Lấy URL đến thư mục includes.
 *
 * @since 2.6.0
 *
 * @param string      $path   Tùy chọn. Đường dẫn tương đối so với URL includes. Mặc định rỗng.
 * @param string|null $scheme Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL includes. Chấp nhận
 *                            'http', 'https', hoặc 'relative'. Mặc định null.
 * @return string Liên kết URL includes với đường dẫn tùy chọn được nối thêm.
 */
function includes_url( $path = '', $scheme = null ) {
	$url = site_url( '/' . WPINC . '/', $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	/**
	 * Lọc URL đến thư mục includes.
	 *
	 * @since 2.8.0
	 * @since 5.8.0 Thêm tham số `$scheme`.
	 *
	 * @param string      $url    URL đầy đủ đến thư mục includes bao gồm giao thức và đường dẫn.
	 * @param string      $path   Đường dẫn tương đối so với URL thư mục wp-includes. Chuỗi rỗng
	 *                            nếu không có đường dẫn.
	 * @param string|null $scheme Giao thức để cung cấp ngữ cảnh cho URL includes. Chấp nhận
	 *                            'http', 'https', 'relative', hoặc null. Mặc định null.
	 */
	return apply_filters( 'includes_url', $url, $path, $scheme );
}

/**
 * Lấy URL đến thư mục nội dung.
 *
 * @since 2.6.0
 *
 * @param string $path Tùy chọn. Đường dẫn tương đối so với URL nội dung. Mặc định rỗng.
 * @return string Liên kết URL nội dung với đường dẫn tùy chọn được nối thêm.
 */
function content_url( $path = '' ) {
	$url = set_url_scheme( WP_CONTENT_URL );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Lọc URL đến thư mục nội dung.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url  URL đầy đủ đến thư mục nội dung bao gồm giao thức và đường dẫn.
	 * @param string $path Đường dẫn tương đối so với URL thư mục nội dung. Chuỗi rỗng
	 *                     nếu không có đường dẫn.
	 */
	return apply_filters( 'content_url', $url, $path );
}

/**
 * Lấy URL trong thư mục plugins hoặc mu-plugins.
 *
 * Mặc định là URL thư mục plugins nếu không có tham số nào được cung cấp.
 *
 * @since 2.6.0
 *
 * @param string $path   Tùy chọn. Đường dẫn bổ sung được nối vào cuối URL, bao gồm
 *                       thư mục tương đối nếu $plugin được cung cấp. Mặc định rỗng.
 * @param string $plugin Tùy chọn. Đường dẫn đầy đủ đến tệp bên trong plugin hoặc mu-plugin.
 *                       URL sẽ tương đối so với thư mục của nó. Mặc định rỗng.
 *                       Thường được thực hiện bằng cách truyền `__FILE__` làm tham số.
 * @return string Liên kết URL plugins với đường dẫn tùy chọn được nối thêm.
 */
function plugins_url( $path = '', $plugin = '' ) {

	$path          = wp_normalize_path( $path );
	$plugin        = wp_normalize_path( $plugin );
	$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

	if ( ! empty( $plugin ) && str_starts_with( $plugin, $mu_plugin_dir ) ) {
		$url = WPMU_PLUGIN_URL;
	} else {
		$url = WP_PLUGIN_URL;
	}

	$url = set_url_scheme( $url );

	if ( ! empty( $plugin ) && is_string( $plugin ) ) {
		$folder = dirname( plugin_basename( $plugin ) );
		if ( '.' !== $folder ) {
			$url .= '/' . ltrim( $folder, '/' );
		}
	}

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Lọc URL đến thư mục plugins.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url    URL đầy đủ đến thư mục plugins bao gồm giao thức và đường dẫn.
	 * @param string $path   Đường dẫn tương đối so với URL thư mục plugins. Chuỗi rỗng
	 *                       nếu không có đường dẫn.
	 * @param string $plugin Đường dẫn tệp plugin để tương đối. Chuỗi rỗng nếu không có plugin
	 *                       nào được chỉ định.
	 */
	return apply_filters( 'plugins_url', $url, $path, $plugin );
}

/**
 * Lấy URL trang web cho mạng lưới hiện tại.
 *
 * Trả về URL trang web với giao thức phù hợp, 'https' nếu
 * is_ssl() và 'http' nếu không. Nếu $scheme là 'http' hoặc 'https', is_ssl() sẽ bị ghi đè.
 *
 * @since 3.0.0
 *
 * @see set_url_scheme()
 *
 * @param string      $path   Tùy chọn. Đường dẫn tương đối so với URL trang web. Mặc định rỗng.
 * @param string|null $scheme Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL trang web. Chấp nhận
 *                            'http', 'https', hoặc 'relative'. Mặc định null.
 * @return string Liên kết URL trang web với đường dẫn tùy chọn được nối thêm.
 */
function network_site_url( $path = '', $scheme = null ) {
	if ( ! is_multisite() ) {
		return site_url( $path, $scheme );
	}

	$current_network = get_network();

	if ( 'relative' === $scheme ) {
		$url = $current_network->path;
	} else {
		$url = set_url_scheme( 'http://' . $current_network->domain . $current_network->path, $scheme );
	}

	if ( $path && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	/**
	 * Lọc URL trang web mạng lưới.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $url    URL trang web mạng lưới đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path   Đường dẫn tương đối so với URL trang web mạng lưới. Chuỗi rỗng nếu
	 *                            không có đường dẫn.
	 * @param string|null $scheme Giao thức để cung cấp ngữ cảnh cho URL. Chấp nhận 'http', 'https',
	 *                            'relative' hoặc null.
	 */
	return apply_filters( 'network_site_url', $url, $path, $scheme );
}

/**
 * Lấy URL trang chủ cho mạng lưới hiện tại.
 *
 * Trả về URL trang chủ với giao thức phù hợp, 'https' nếu is_ssl()
 * và 'http' nếu không. Nếu `$scheme` là 'http' hoặc 'https', `is_ssl()` sẽ bị ghi đè.
 *
 * @since 3.0.0
 *
 * @param string      $path   Tùy chọn. Đường dẫn tương đối so với URL trang chủ. Mặc định rỗng.
 * @param string|null $scheme Tùy chọn. Giao thức để cung cấp ngữ cảnh cho URL trang chủ. Chấp nhận
 *                            'http', 'https', hoặc 'relative'. Mặc định null.
 * @return string Liên kết URL trang chủ với đường dẫn tùy chọn được nối thêm.
 */
function network_home_url( $path = '', $scheme = null ) {
	if ( ! is_multisite() ) {
		return home_url( $path, $scheme );
	}

	$current_network = get_network();
	$orig_scheme     = $scheme;

	if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {
		$scheme = is_ssl() ? 'https' : 'http';
	}

	if ( 'relative' === $scheme ) {
		$url = $current_network->path;
	} else {
		$url = set_url_scheme( 'http://' . $current_network->domain . $current_network->path, $scheme );
	}

	if ( $path && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	/**
	 * Lọc URL trang chủ mạng lưới.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $url         URL trang chủ mạng lưới đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path        Đường dẫn tương đối so với URL trang chủ mạng lưới. Chuỗi rỗng
	 *                                 nếu không có đường dẫn.
	 * @param string|null $orig_scheme Giao thức để cung cấp ngữ cảnh cho URL. Chấp nhận 'http', 'https',
	 *                                 'relative' hoặc null.
	 */
	return apply_filters( 'network_home_url', $url, $path, $orig_scheme );
}

/**
 * Lấy URL đến khu vực quản trị cho mạng lưới.
 *
 * @since 3.0.0
 *
 * @param string $path   Tùy chọn. Đường dẫn tương đối so với URL quản trị. Mặc định rỗng.
 * @param string $scheme Tùy chọn. Giao thức sử dụng. Mặc định là 'admin', tuân theo force_ssl_admin()
 *                       và is_ssl(). Có thể truyền 'http' hoặc 'https' để buộc các giao thức đó.
 * @return string Liên kết URL quản trị với đường dẫn tùy chọn được nối thêm.
 */
function network_admin_url( $path = '', $scheme = 'admin' ) {
	if ( ! is_multisite() ) {
		return admin_url( $path, $scheme );
	}

	$url = network_site_url( 'wp-admin/network/', $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	/**
	 * Lọc URL quản trị mạng lưới.
	 *
	 * @since 3.0.0
	 * @since 5.8.0 Thêm tham số `$scheme`.
	 *
	 * @param string      $url    URL quản trị mạng lưới đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path   Đường dẫn tương đối so với URL quản trị mạng lưới. Chuỗi rỗng nếu
	 *                            không có đường dẫn.
	 * @param string|null $scheme Giao thức sử dụng. Chấp nhận 'http', 'https',
	 *                            'admin', hoặc null. Mặc định là 'admin', tuân theo force_ssl_admin() và is_ssl().
	 */
	return apply_filters( 'network_admin_url', $url, $path, $scheme );
}

/**
 * Lấy URL đến khu vực quản trị cho người dùng hiện tại.
 *
 * @since 3.0.0
 *
 * @param string $path   Tùy chọn. Đường dẫn tương đối so với URL quản trị. Mặc định rỗng.
 * @param string $scheme Tùy chọn. Giao thức sử dụng. Mặc định là 'admin', tuân theo force_ssl_admin()
 *                       và is_ssl(). Có thể truyền 'http' hoặc 'https' để buộc các giao thức đó.
 * @return string Liên kết URL quản trị với đường dẫn tùy chọn được nối thêm.
 */
function user_admin_url( $path = '', $scheme = 'admin' ) {
	$url = network_site_url( 'wp-admin/user/', $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	/**
	 * Lọc URL quản trị người dùng cho người dùng hiện tại.
	 *
	 * @since 3.1.0
	 * @since 5.8.0 Thêm tham số `$scheme`.
	 *
	 * @param string      $url    URL đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $path   Đường dẫn tương đối so với URL. Chuỗi rỗng nếu
	 *                            không có đường dẫn.
	 * @param string|null $scheme Giao thức sử dụng. Chấp nhận 'http', 'https',
	 *                            'admin', hoặc null. Mặc định là 'admin', tuân theo force_ssl_admin() và is_ssl().
	 */
	return apply_filters( 'user_admin_url', $url, $path, $scheme );
}

/**
 * Lấy URL đến khu vực quản trị cho trang web hiện tại hoặc mạng lưới tùy thuộc vào ngữ cảnh.
 *
 * @since 3.1.0
 *
 * @param string $path   Tùy chọn. Đường dẫn tương đối so với URL quản trị. Mặc định rỗng.
 * @param string $scheme Tùy chọn. Giao thức sử dụng. Mặc định là 'admin', tuân theo force_ssl_admin()
 *                       và is_ssl(). Có thể truyền 'http' hoặc 'https' để buộc các giao thức đó.
 * @return string Liên kết URL quản trị với đường dẫn tùy chọn được nối thêm.
 */
function self_admin_url( $path = '', $scheme = 'admin' ) {
	if ( is_network_admin() ) {
		$url = network_admin_url( $path, $scheme );
	} elseif ( is_user_admin() ) {
		$url = user_admin_url( $path, $scheme );
	} else {
		$url = admin_url( $path, $scheme );
	}

	/**
	 * Lọc URL quản trị cho trang web hiện tại hoặc mạng lưới tùy thuộc vào ngữ cảnh.
	 *
	 * @since 4.9.0
	 *
	 * @param string $url    URL đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string $path   Đường dẫn tương đối so với URL. Chuỗi rỗng nếu không có đường dẫn.
	 * @param string $scheme Giao thức sử dụng.
	 */
	return apply_filters( 'self_admin_url', $url, $path, $scheme );
}

/**
 * Đặt giao thức cho URL.
 *
 * @since 3.4.0
 * @since 4.4.0 Thêm giao thức 'rest'.
 *
 * @param string      $url    URL tuyệt đối bao gồm giao thức.
 * @param string|null $scheme Tùy chọn. Giao thức cho $url. Hiện tại là 'http', 'https', 'login',
 *                            'login_post', 'admin', 'relative', 'rest', 'rpc', hoặc null. Mặc định null.
 * @return string URL với giao thức đã chọn.
 */
function set_url_scheme( $url, $scheme = null ) {
	$orig_scheme = $scheme;

	if ( ! $scheme ) {
		$scheme = is_ssl() ? 'https' : 'http';
	} elseif ( 'admin' === $scheme || 'login' === $scheme || 'login_post' === $scheme || 'rpc' === $scheme ) {
		$scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
	} elseif ( 'http' !== $scheme && 'https' !== $scheme && 'relative' !== $scheme ) {
		$scheme = is_ssl() ? 'https' : 'http';
	}

	$url = trim( $url );
	if ( str_starts_with( $url, '//' ) ) {
		$url = 'http:' . $url;
	}

	if ( 'relative' === $scheme ) {
		$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
		if ( '' !== $url && '/' === $url[0] ) {
			$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
		}
	} else {
		$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
	}

	/**
	 * Lọc URL kết quả sau khi đặt giao thức.
	 *
	 * @since 3.4.0
	 *
	 * @param string      $url         URL đầy đủ bao gồm giao thức và đường dẫn.
	 * @param string      $scheme      Giao thức được áp dụng cho URL. Một trong 'http', 'https', hoặc 'relative'.
	 * @param string|null $orig_scheme Giao thức được yêu cầu cho URL. Một trong 'http', 'https', 'login',
	 *                                 'login_post', 'admin', 'relative', 'rest', 'rpc', hoặc null.
	 */
	return apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
}

/**
 * Lấy URL đến bảng điều khiển của người dùng.
 *
 * Nếu người dùng không thuộc bất kỳ trang web nào, bảng điều khiển người dùng toàn cục sẽ được sử dụng.
 * Nếu người dùng thuộc trang web hiện tại, bảng điều khiển cho trang web hiện tại sẽ được trả về.
 * Nếu người dùng không thể chỉnh sửa trang web hiện tại, bảng điều khiển đến trang web chính của
 * người dùng sẽ được trả về.
 *
 * @since 3.1.0
 *
 * @param int    $user_id Tùy chọn. ID người dùng. Mặc định là người dùng hiện tại.
 * @param string $path    Tùy chọn. Đường dẫn tương đối so với bảng điều khiển. Chỉ sử dụng đường dẫn
 *                        được biết bởi cả quản trị viên trang web và người dùng. Mặc định rỗng.
 * @param string $scheme  Giao thức sử dụng. Mặc định là 'admin', tuân theo force_ssl_admin()
 *                        và is_ssl(). Có thể truyền 'http' hoặc 'https' để buộc các giao thức đó.
 * @return string Liên kết URL bảng điều khiển với đường dẫn tùy chọn được nối thêm.
 */
function get_dashboard_url( $user_id = 0, $path = '', $scheme = 'admin' ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	$blogs = get_blogs_of_user( $user_id );

	if ( is_multisite() && ! user_can( $user_id, 'manage_network' ) && empty( $blogs ) ) {
		$url = user_admin_url( $path, $scheme );
	} elseif ( ! is_multisite() ) {
		$url = admin_url( $path, $scheme );
	} else {
		$current_blog = get_current_blog_id();

		if ( $current_blog && ( user_can( $user_id, 'manage_network' ) || in_array( $current_blog, array_keys( $blogs ), true ) ) ) {
			$url = admin_url( $path, $scheme );
		} else {
			$active = get_active_blog_for_user( $user_id );
			if ( $active ) {
				$url = get_admin_url( $active->blog_id, $path, $scheme );
			} else {
				$url = user_admin_url( $path, $scheme );
			}
		}
	}

	/**
	 * Lọc URL bảng điều khiển cho người dùng.
	 *
	 * @since 3.1.0
	 *
	 * @param string $url     URL đầy đủ bao gồm giao thức và đường dẫn.
	 * @param int    $user_id ID người dùng.
	 * @param string $path    Đường dẫn tương đối so với URL. Chuỗi rỗng nếu không có đường dẫn.
	 * @param string $scheme  Giao thức để cung cấp ngữ cảnh cho URL. Chấp nhận 'http', 'https', 'login',
	 *                        'login_post', 'admin', 'relative' hoặc null.
	 */
	return apply_filters( 'user_dashboard_url', $url, $user_id, $path, $scheme );
}

/**
 * Lấy URL đến trình chỉnh sửa hồ sơ của người dùng.
 *
 * @since 3.1.0
 *
 * @param int    $user_id Tùy chọn. ID người dùng. Mặc định là người dùng hiện tại.
 * @param string $scheme  Tùy chọn. Giao thức sử dụng. Mặc định là 'admin', tuân theo force_ssl_admin()
 *                        và is_ssl(). Có thể truyền 'http' hoặc 'https' để buộc các giao thức đó.
 * @return string Liên kết URL bảng điều khiển với đường dẫn tùy chọn được nối thêm.
 */
function get_edit_profile_url( $user_id = 0, $scheme = 'admin' ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( is_user_admin() ) {
		$url = user_admin_url( 'profile.php', $scheme );
	} elseif ( is_network_admin() ) {
		$url = network_admin_url( 'profile.php', $scheme );
	} else {
		$url = get_dashboard_url( $user_id, 'profile.php', $scheme );
	}

	/**
	 * Lọc URL cho trình chỉnh sửa hồ sơ người dùng.
	 *
	 * @since 3.1.0
	 *
	 * @param string $url     URL đầy đủ bao gồm giao thức và đường dẫn.
	 * @param int    $user_id ID người dùng.
	 * @param string $scheme  Giao thức để cung cấp ngữ cảnh cho URL. Chấp nhận 'http', 'https', 'login',
	 *                        'login_post', 'admin', 'relative' hoặc null.
	 */
	return apply_filters( 'edit_profile_url', $url, $user_id, $scheme );
}

/**
 * Trả về URL chuẩn cho bài viết.
 *
 * Khi bài viết giống với trang được yêu cầu hiện tại, hàm cũng sẽ xử lý
 * các tham số phân trang.
 *
 * @since 4.6.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng. Mặc định là biến toàn cục `$post`.
 * @return string|false URL chuẩn. False nếu bài viết không tồn tại
 *                      hoặc chưa được xuất bản.
 */
function wp_get_canonical_url( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	if ( 'publish' !== $post->post_status ) {
		return false;
	}

	$canonical_url = get_permalink( $post );

	// Nếu URL chuẩn đang được tạo cho trang hiện tại, đảm bảo nó có phân trang nếu cần.
	if ( get_queried_object_id() === $post->ID ) {
		$page = get_query_var( 'page', 0 );
		if ( $page >= 2 ) {
			if ( ! get_option( 'permalink_structure' ) ) {
				$canonical_url = add_query_arg( 'page', $page, $canonical_url );
			} else {
				$canonical_url = trailingslashit( $canonical_url ) . user_trailingslashit( $page, 'single_paged' );
			}
		}

		$cpage = get_query_var( 'cpage', 0 );
		if ( $cpage ) {
			$canonical_url = get_comments_pagenum_link( $cpage );
		}
	}

	/**
	 * Lọc URL chuẩn cho bài viết.
	 *
	 * @since 4.6.0
	 *
	 * @param string  $canonical_url URL chuẩn của bài viết.
	 * @param WP_Post $post          Đối tượng bài viết.
	 */
	return apply_filters( 'get_canonical_url', $canonical_url, $post );
}

/**
 * Xuất rel=canonical cho các truy vấn bài viết đơn.
 *
 * @since 2.9.0
 * @since 4.6.0 Điều chỉnh để sử dụng `wp_get_canonical_url()`.
 */
function rel_canonical() {
	if ( ! is_singular() ) {
		return;
	}

	$id = get_queried_object_id();

	if ( 0 === $id ) {
		return;
	}

	$url = wp_get_canonical_url( $id );

	if ( ! empty( $url ) ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
	}
}

/**
 * Trả về liên kết rút gọn cho bài viết, trang, tệp đính kèm, hoặc trang web.
 *
 * Hàm này tồn tại để cung cấp thẻ liên kết rút gọn mà tất cả theme và plugin có thể nhắm đến.
 * Một plugin phải hook vào để cung cấp liên kết rút gọn thực tế. Hỗ trợ liên kết rút gọn mặc định
 * giới hạn ở việc cung cấp liên kết kiểu ?p= cho bài viết. Plugin có thể bỏ qua hàm này
 * thông qua bộ lọc {@see 'pre_get_shortlink'} hoặc lọc đầu ra thông qua bộ lọc {@see 'get_shortlink'}.
 *
 * @since 3.0.0
 *
 * @param int    $id          Tùy chọn. ID bài viết hoặc trang web. Mặc định là 0, nghĩa là bài viết hoặc trang web hiện tại.
 * @param string $context     Tùy chọn. ID là ID 'site', ID 'post', hay ID 'media'. Nếu 'post',
 *                            post_type của bài viết sẽ được tham chiếu. Nếu 'query', truy vấn hiện tại sẽ được
 *                            tham chiếu để xác định ID và ngữ cảnh. Mặc định 'post'.
 * @param bool   $allow_slugs Tùy chọn. Có cho phép slug bài viết trong liên kết rút gọn hay không. Tùy plugin
 *                            quyết định cách và có tôn trọng điều này hay không. Mặc định true.
 * @return string Liên kết rút gọn hoặc chuỗi rỗng nếu không có liên kết rút gọn cho tài nguyên được yêu cầu
 *                hoặc nếu liên kết rút gọn không được bật.
 */
function wp_get_shortlink( $id = 0, $context = 'post', $allow_slugs = true ) {
	/**
	 * Lọc có nên bỏ qua việc tạo liên kết rút gọn cho bài viết được chỉ định hay không.
	 *
	 * Trả về giá trị khác false từ bộ lọc sẽ bỏ qua quá trình tạo liên kết rút gọn,
	 * trả về giá trị đó thay thế.
	 *
	 * @since 3.0.0
	 *
	 * @param false|string $return      Giá trị trả về bỏ qua. False hoặc chuỗi URL.
	 * @param int          $id          ID bài viết, hoặc 0 cho bài viết hiện tại.
	 * @param string       $context     Ngữ cảnh cho liên kết. Một trong 'post' hoặc 'query'.
	 * @param bool         $allow_slugs Có cho phép slug bài viết trong liên kết rút gọn hay không.
	 */
	$shortlink = apply_filters( 'pre_get_shortlink', false, $id, $context, $allow_slugs );

	if ( false !== $shortlink ) {
		return $shortlink;
	}

	$post_id = 0;
	if ( 'query' === $context && is_singular() ) {
		$post_id = get_queried_object_id();
		$post    = get_post( $post_id );
	} elseif ( 'post' === $context ) {
		$post = get_post( $id );
		if ( ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		}
	}

	$shortlink = '';

	// Trả về liên kết `?p=` cho tất cả loại bài viết công khai.
	if ( ! empty( $post_id ) ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( 'page' === $post->post_type
			&& 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post->ID
		) {
			$shortlink = home_url( '/' );
		} elseif ( $post_type && $post_type->public ) {
			$shortlink = home_url( '?p=' . $post_id );
		}
	}

	/**
	 * Lọc liên kết rút gọn cho bài viết.
	 *
	 * @since 3.0.0
	 *
	 * @param string $shortlink   URL liên kết rút gọn.
	 * @param int    $id          ID bài viết, hoặc 0 cho bài viết hiện tại.
	 * @param string $context     Ngữ cảnh cho liên kết. Một trong 'post' hoặc 'query'.
	 * @param bool   $allow_slugs Có cho phép slug bài viết trong liên kết rút gọn hay không. Không được sử dụng theo mặc định.
	 */
	return apply_filters( 'get_shortlink', $shortlink, $id, $context, $allow_slugs );
}

/**
 * Chèn rel=shortlink vào phần head nếu liên kết rút gọn được định nghĩa cho trang hiện tại.
 *
 * Được gắn vào hành động {@see 'wp_head'}.
 *
 * @since 3.0.0
 */
function wp_shortlink_wp_head() {
	$shortlink = wp_get_shortlink( 0, 'query' );

	if ( empty( $shortlink ) ) {
		return;
	}

	echo "<link rel='shortlink' href='" . esc_url( $shortlink ) . "' />\n";
}

/**
 * Gửi header Link: rel=shortlink nếu liên kết rút gọn được định nghĩa cho trang hiện tại.
 *
 * Được gắn vào hành động {@see 'wp'}.
 *
 * @since 3.0.0
 */
function wp_shortlink_header() {
	if ( headers_sent() ) {
		return;
	}

	$shortlink = wp_get_shortlink( 0, 'query' );

	if ( empty( $shortlink ) ) {
		return;
	}

	header( 'Link: <' . $shortlink . '>; rel=shortlink', false );
}

/**
 * Hiển thị liên kết rút gọn cho bài viết.
 *
 * Phải được gọi từ bên trong "Vòng lặp"
 *
 * Gọi như the_shortlink( __( 'Shortlinkage FTW' ) )
 *
 * @since 3.0.0
 * @since 6.8.0 Loại bỏ thuộc tính title.
 *
 * @param string $text   Tùy chọn. Văn bản liên kết hoặc HTML sẽ được hiển thị. Mặc định 'This is the short link.'
 * @param string $title  Không sử dụng.
 * @param string $before Tùy chọn. HTML hiển thị trước liên kết. Mặc định rỗng.
 * @param string $after  Tùy chọn. HTML hiển thị sau liên kết. Mặc định rỗng.
 */
function the_shortlink( $text = '', $title = '', $before = '', $after = '' ) {
	$post = get_post();

	if ( empty( $text ) ) {
		$text = __( 'This is the short link.' );
	}

	$shortlink = wp_get_shortlink( $post->ID );

	if ( ! empty( $shortlink ) ) {
		$link = '<a rel="shortlink" href="' . esc_url( $shortlink ) . '">' . $text . '</a>';

		/**
		 * Lọc thẻ neo liên kết rút gọn cho bài viết.
		 *
		 * @since 3.0.0
		 *
		 * @param string $link      Thẻ neo liên kết rút gọn.
		 * @param string $shortlink URL liên kết rút gọn.
		 * @param string $text      Văn bản liên kết rút gọn.
		 * @param string $title     Thuộc tính title của liên kết rút gọn. Không sử dụng.
		 */
		$link = apply_filters( 'the_shortlink', $link, $shortlink, $text, $title );
		echo $before, $link, $after;
	}
}

/**
 * Lấy URL ảnh đại diện.
 *
 * @since 4.2.0
 *
 * @param mixed $id_or_email Ảnh đại diện cần lấy URL. Chấp nhận ID người dùng, hash SHA-256 hoặc MD5 của Gravatar,
 *                           email người dùng, đối tượng WP_User, đối tượng WP_Post, hoặc đối tượng WP_Comment.
 * @param array $args {
 *     Tùy chọn. Các tham số sử dụng thay cho tham số mặc định.
 *
 *     @type int    $size           Chiều cao và chiều rộng của ảnh đại diện tính bằng pixel. Mặc định 96.
 *     @type string $default        URL cho ảnh mặc định hoặc loại mặc định. Chấp nhận:
 *                                  - '404' (trả về 404 thay vì ảnh mặc định)
 *                                  - 'retro' (khuôn mặt pixel kiểu arcade 8-bit)
 *                                  - 'robohash' (một robot)
 *                                  - 'monsterid' (một quái vật)
 *                                  - 'wavatar' (khuôn mặt hoạt hình)
 *                                  - 'identicon' ("tấm chăn", một hoa văn hình học)
 *                                  - 'mystery', 'mm', hoặc 'mysteryman' (The Oyster Man)
 *                                  - 'blank' (GIF trong suốt)
 *                                  - 'gravatar_default' (logo Gravatar)
 *                                  Mặc định là giá trị của tùy chọn 'avatar_default',
 *                                  với dự phòng là 'mystery'.
 *     @type bool   $force_default  Có luôn hiển thị ảnh mặc định thay vì Gravatar hay không.
 *                                  Mặc định false.
 *     @type string $rating         Hiển thị ảnh đại diện đến mức xếp hạng nào. Chấp nhận:
 *                                  - 'G' (phù hợp với mọi đối tượng)
 *                                  - 'PG' (có thể gây khó chịu, thường cho đối tượng từ 13 tuổi trở lên)
 *                                  - 'R' (dành cho đối tượng người lớn trên 17 tuổi)
 *                                  - 'X' (nội dung người lớn hơn mức trên)
 *                                  Mặc định là giá trị của tùy chọn 'avatar_rating'.
 *     @type string $scheme         Giao thức URL sử dụng. Xem set_url_scheme() để biết các giá trị được chấp nhận.
 *                                  Mặc định null.
 *     @type array  $processed_args Khi hàm trả về, giá trị sẽ là $args đã được xử lý/làm sạch
 *                                  cộng thêm dự đoán "found_avatar". Truyền dưới dạng tham chiếu. Mặc định null.
 * }
 * @return string|false URL của ảnh đại diện khi thành công, false khi thất bại.
 */
function get_avatar_url( $id_or_email, $args = null ) {
	$args = get_avatar_data( $id_or_email, $args );
	return $args['url'];
}

/**
 * Kiểm tra xem loại bình luận này có cho phép lấy ảnh đại diện hay không.
 *
 * @since 5.1.0
 *
 * @param string $comment_type Loại bình luận cần kiểm tra.
 * @return bool Loại bình luận có được phép lấy ảnh đại diện hay không.
 */
function is_avatar_comment_type( $comment_type ) {
	/**
	 * Lọc danh sách các loại bình luận được phép lấy ảnh đại diện.
	 *
	 * @since 3.0.0
	 *
	 * @param array $types Mảng các loại nội dung. Mặc định chỉ chứa 'comment'.
	 */
	$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );

	return in_array( $comment_type, (array) $allowed_comment_types, true );
}

/**
 * Lấy dữ liệu mặc định về ảnh đại diện.
 *
 * @since 4.2.0
 * @since 6.7.0 URL Gravatar luôn sử dụng HTTPS.
 * @since 6.8.0 URL Gravatar sử dụng thuật toán băm SHA-256.
 *
 * @param mixed $id_or_email Ảnh đại diện cần lấy. Chấp nhận ID người dùng, hash SHA-256 hoặc MD5 của Gravatar,
 *                           email người dùng, đối tượng WP_User, đối tượng WP_Post, hoặc đối tượng WP_Comment.
 * @param array $args {
 *     Tùy chọn. Các tham số sử dụng thay cho tham số mặc định.
 *
 *     @type int    $size           Chiều cao và chiều rộng của ảnh đại diện tính bằng pixel. Mặc định 96.
 *     @type int    $height         Chiều cao hiển thị của ảnh đại diện tính bằng pixel. Mặc định là $size.
 *     @type int    $width          Chiều rộng hiển thị của ảnh đại diện tính bằng pixel. Mặc định là $size.
 *     @type string $default        URL cho ảnh mặc định hoặc loại mặc định. Chấp nhận:
 *                                  - '404' (trả về 404 thay vì ảnh mặc định)
 *                                  - 'retro' (khuôn mặt pixel kiểu arcade 8-bit)
 *                                  - 'robohash' (một robot)
 *                                  - 'monsterid' (một quái vật)
 *                                  - 'wavatar' (khuôn mặt hoạt hình)
 *                                  - 'identicon' ("tấm chăn", một hoa văn hình học)
 *                                  - 'mystery', 'mm', hoặc 'mysteryman' (The Oyster Man)
 *                                  - 'blank' (GIF trong suốt)
 *                                  - 'gravatar_default' (logo Gravatar)
 *                                  Mặc định là giá trị của tùy chọn 'avatar_default',
 *                                  với dự phòng là 'mystery'.
 *     @type bool   $force_default  Có luôn hiển thị ảnh mặc định thay vì Gravatar hay không.
 *                                  Mặc định false.
 *     @type string $rating         Hiển thị ảnh đại diện đến mức xếp hạng nào. Chấp nhận:
 *                                  - 'G' (phù hợp với mọi đối tượng)
 *                                  - 'PG' (có thể gây khó chịu, thường cho đối tượng từ 13 tuổi trở lên)
 *                                  - 'R' (dành cho đối tượng người lớn trên 17 tuổi)
 *                                  - 'X' (nội dung người lớn hơn mức trên)
 *                                  Mặc định là giá trị của tùy chọn 'avatar_rating'.
 *     @type string $scheme         Giao thức URL sử dụng. Xem set_url_scheme() để biết các giá trị được chấp nhận.
 *                                  Đối với Gravatar, thiết lập này bị bỏ qua và HTTPS được sử dụng để tránh
 *                                  chuyển hướng không cần thiết. Thiết lập được giữ lại cho các hệ thống sử dụng
 *                                  bộ lọc {@see 'pre_get_avatar_data'} để tùy chỉnh ảnh đại diện.
 *                                  Mặc định null.
 *     @type array  $processed_args Khi hàm trả về, giá trị sẽ là $args đã được xử lý/làm sạch
 *                                  cộng thêm dự đoán "found_avatar". Truyền dưới dạng tham chiếu. Mặc định null.
 *     @type string $extra_attr     Các thuộc tính HTML để chèn vào phần tử IMG. Không được làm sạch.
 *                                  Mặc định rỗng.
 * }
 * @return array {
 *     Cùng với các tham số được truyền trong `$args`, mảng này sẽ chứa một vài tham số bổ sung.
 *
 *     @type bool         $found_avatar True nếu tìm thấy ảnh đại diện cho người dùng này,
 *                                      false hoặc không được đặt nếu không tìm thấy.
 *     @type string|false $url          URL của ảnh đại diện được tìm thấy, hoặc false.
 * }
 */
function get_avatar_data( $id_or_email, $args = null ) {
	$args = wp_parse_args(
		$args,
		array(
			'size'           => 96,
			'height'         => null,
			'width'          => null,
			'default'        => get_option( 'avatar_default', 'mystery' ),
			'force_default'  => false,
			'rating'         => get_option( 'avatar_rating' ),
			'scheme'         => null,
			'processed_args' => null, // Nếu được sử dụng, nên là tham chiếu.
			'extra_attr'     => '',
		)
	);

	if ( is_numeric( $args['size'] ) ) {
		$args['size'] = absint( $args['size'] );
		if ( ! $args['size'] ) {
			$args['size'] = 96;
		}
	} else {
		$args['size'] = 96;
	}

	if ( is_numeric( $args['height'] ) ) {
		$args['height'] = absint( $args['height'] );
		if ( ! $args['height'] ) {
			$args['height'] = $args['size'];
		}
	} else {
		$args['height'] = $args['size'];
	}

	if ( is_numeric( $args['width'] ) ) {
		$args['width'] = absint( $args['width'] );
		if ( ! $args['width'] ) {
			$args['width'] = $args['size'];
		}
	} else {
		$args['width'] = $args['size'];
	}

	if ( empty( $args['default'] ) ) {
		$args['default'] = get_option( 'avatar_default', 'mystery' );
	}

	switch ( $args['default'] ) {
		case 'mm':
		case 'mystery':
		case 'mysteryman':
			$args['default'] = 'mm';
			break;
		case 'gravatar_default':
			$args['default'] = false;
			break;
	}

	$args['force_default'] = (bool) $args['force_default'];

	$args['rating'] = strtolower( $args['rating'] );

	$args['found_avatar'] = false;

	/**
	 * Lọc có nên lấy URL ảnh đại diện sớm hay không.
	 *
	 * Truyền giá trị không null trong thành viên 'url' của mảng trả về sẽ
	 * bỏ qua get_avatar_data(), truyền giá trị qua
	 * bộ lọc {@see 'get_avatar_data'} và trả về sớm.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args        Các tham số được truyền vào get_avatar_data(), sau khi xử lý.
	 * @param mixed $id_or_email Ảnh đại diện cần lấy. Chấp nhận ID người dùng, hash SHA-256 hoặc MD5 của Gravatar,
	 *                           email người dùng, đối tượng WP_User, đối tượng WP_Post, hoặc đối tượng WP_Comment.
	 */
	$args = apply_filters( 'pre_get_avatar_data', $args, $id_or_email );

	if ( isset( $args['url'] ) ) {
		/** This filter is documented in wp-includes/link-template.php */
		return apply_filters( 'get_avatar_data', $args, $id_or_email );
	}

	$email_hash = '';
	$user       = false;
	$email      = false;

	if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
		$id_or_email = get_comment( $id_or_email );
	}

	// Xử lý mã định danh người dùng.
	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', absint( $id_or_email ) );
	} elseif ( is_string( $id_or_email ) ) {
		if ( str_contains( $id_or_email, '@sha256.gravatar.com' ) ) {
			// Hash SHA-256.
			list( $email_hash ) = explode( '@', $id_or_email );
		} elseif ( str_contains( $id_or_email, '@md5.gravatar.com' ) ) {
			// Hash MD5.
			list( $email_hash ) = explode( '@', $id_or_email );
		} else {
			// Địa chỉ email.
			$email = $id_or_email;
		}
	} elseif ( $id_or_email instanceof WP_User ) {
		// Đối tượng người dùng.
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		// Đối tượng bài viết.
		$user = get_user_by( 'id', (int) $id_or_email->post_author );
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( ! is_avatar_comment_type( get_comment_type( $id_or_email ) ) ) {
			$args['url'] = false;
			/** This filter is documented in wp-includes/link-template.php */
			return apply_filters( 'get_avatar_data', $args, $id_or_email );
		}

		if ( ! empty( $id_or_email->user_id ) ) {
			$user = get_user_by( 'id', (int) $id_or_email->user_id );
		}
		if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
			$email = $id_or_email->comment_author_email;
		}
	}

	if ( ! $email_hash ) {
		if ( $user ) {
			$email = $user->user_email;
		}

		if ( $email ) {
			$email_hash = hash( 'sha256', strtolower( trim( $email ) ) );
		}
	}

	if ( $email_hash ) {
		$args['found_avatar'] = true;
	}

	$url_args = array(
		's' => $args['size'],
		'd' => $args['default'],
		'f' => $args['force_default'] ? 'y' : false,
		'r' => $args['rating'],
	);

	/*
	 * Gravatar luôn được phục vụ qua HTTPS.
	 *
	 * Trang web Gravatar chuyển hướng các yêu cầu HTTP sang URL HTTPS nên luôn
	 * sử dụng giao thức HTTPS để tránh chuyển hướng không cần thiết.
	 */
	$url = 'https://secure.gravatar.com/avatar/' . $email_hash;

	$url = add_query_arg(
		rawurlencode_deep( array_filter( $url_args ) ),
		$url
	);

	/**
	 * Lọc URL ảnh đại diện.
	 *
	 * @since 4.2.0
	 *
	 * @param string $url         URL của ảnh đại diện.
	 * @param mixed  $id_or_email Ảnh đại diện cần lấy. Chấp nhận ID người dùng, hash SHA-256 hoặc MD5 của Gravatar,
	 *                            email người dùng, đối tượng WP_User, đối tượng WP_Post, hoặc đối tượng WP_Comment.
	 * @param array  $args        Các tham số được truyền vào get_avatar_data(), sau khi xử lý.
	 */
	$args['url'] = apply_filters( 'get_avatar_url', $url, $id_or_email, $args );

	/**
	 * Lọc dữ liệu ảnh đại diện.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args        Các tham số được truyền vào get_avatar_data(), sau khi xử lý.
	 * @param mixed $id_or_email Ảnh đại diện cần lấy. Chấp nhận ID người dùng, hash SHA-256 hoặc MD5 của Gravatar,
	 *                           email người dùng, đối tượng WP_User, đối tượng WP_Post, hoặc đối tượng WP_Comment.
	 */
	return apply_filters( 'get_avatar_data', $args, $id_or_email );
}

/**
 * Lấy URL của một tệp trong theme.
 *
 * Tìm kiếm trong thư mục stylesheet trước thư mục template để các theme
 * kế thừa từ theme cha chỉ cần ghi đè một tệp.
 *
 * @since 4.7.0
 *
 * @param string $file Tùy chọn. Tệp cần tìm kiếm trong thư mục stylesheet.
 * @return string URL của tệp.
 */
function get_theme_file_uri( $file = '' ) {
	$file = ltrim( $file, '/' );

	$stylesheet_directory = get_stylesheet_directory();

	if ( empty( $file ) ) {
		$url = get_stylesheet_directory_uri();
	} elseif ( get_template_directory() !== $stylesheet_directory && file_exists( $stylesheet_directory . '/' . $file ) ) {
		$url = get_stylesheet_directory_uri() . '/' . $file;
	} else {
		$url = get_template_directory_uri() . '/' . $file;
	}

	/**
	 * Lọc URL đến một tệp trong theme.
	 *
	 * @since 4.7.0
	 *
	 * @param string $url  URL của tệp.
	 * @param string $file Tệp được yêu cầu tìm kiếm.
	 */
	return apply_filters( 'theme_file_uri', $url, $file );
}

/**
 * Lấy URL của một tệp trong theme cha.
 *
 * @since 4.7.0
 *
 * @param string $file Tùy chọn. Tệp cần trả về URL trong thư mục template.
 * @return string URL của tệp.
 */
function get_parent_theme_file_uri( $file = '' ) {
	$file = ltrim( $file, '/' );

	if ( empty( $file ) ) {
		$url = get_template_directory_uri();
	} else {
		$url = get_template_directory_uri() . '/' . $file;
	}

	/**
	 * Lọc URL đến một tệp trong theme cha.
	 *
	 * @since 4.7.0
	 *
	 * @param string $url  URL của tệp.
	 * @param string $file Tệp được yêu cầu tìm kiếm.
	 */
	return apply_filters( 'parent_theme_file_uri', $url, $file );
}

/**
 * Lấy đường dẫn của một tệp trong theme.
 *
 * Tìm kiếm trong thư mục stylesheet trước thư mục template để các theme
 * kế thừa từ theme cha chỉ cần ghi đè một tệp.
 *
 * @since 4.7.0
 *
 * @param string $file Tùy chọn. Tệp cần tìm kiếm trong thư mục stylesheet.
 * @return string Đường dẫn của tệp.
 */
function get_theme_file_path( $file = '' ) {
	$file = ltrim( $file, '/' );

	$stylesheet_directory = get_stylesheet_directory();
	$template_directory   = get_template_directory();

	if ( empty( $file ) ) {
		$path = $stylesheet_directory;
	} elseif ( $stylesheet_directory !== $template_directory && file_exists( $stylesheet_directory . '/' . $file ) ) {
		$path = $stylesheet_directory . '/' . $file;
	} else {
		$path = $template_directory . '/' . $file;
	}

	/**
	 * Lọc đường dẫn đến một tệp trong theme.
	 *
	 * @since 4.7.0
	 *
	 * @param string $path Đường dẫn của tệp.
	 * @param string $file Tệp được yêu cầu tìm kiếm.
	 */
	return apply_filters( 'theme_file_path', $path, $file );
}

/**
 * Lấy đường dẫn của một tệp trong theme cha.
 *
 * @since 4.7.0
 *
 * @param string $file Tùy chọn. Tệp cần trả về đường dẫn trong thư mục template.
 * @return string Đường dẫn của tệp.
 */
function get_parent_theme_file_path( $file = '' ) {
	$file = ltrim( $file, '/' );

	if ( empty( $file ) ) {
		$path = get_template_directory();
	} else {
		$path = get_template_directory() . '/' . $file;
	}

	/**
	 * Lọc đường dẫn đến một tệp trong theme cha.
	 *
	 * @since 4.7.0
	 *
	 * @param string $path Đường dẫn của tệp.
	 * @param string $file Tệp được yêu cầu tìm kiếm.
	 */
	return apply_filters( 'parent_theme_file_path', $path, $file );
}

/**
 * Lấy URL đến trang chính sách bảo mật.
 *
 * @since 4.9.6
 *
 * @return string URL đến trang chính sách bảo mật. Chuỗi rỗng nếu không tồn tại.
 */
function get_privacy_policy_url() {
	$url            = '';
	$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

	if ( ! empty( $policy_page_id ) && get_post_status( $policy_page_id ) === 'publish' ) {
		$url = (string) get_permalink( $policy_page_id );
	}

	/**
	 * Lọc URL của trang chính sách bảo mật.
	 *
	 * @since 4.9.6
	 *
	 * @param string $url            URL đến trang chính sách bảo mật. Chuỗi rỗng
	 *                               nếu không tồn tại.
	 * @param int    $policy_page_id ID của trang chính sách bảo mật.
	 */
	return apply_filters( 'privacy_policy_url', $url, $policy_page_id );
}

/**
 * Hiển thị liên kết chính sách bảo mật với định dạng, khi có thể áp dụng.
 *
 * @since 4.9.6
 *
 * @param string $before Tùy chọn. Hiển thị trước liên kết chính sách bảo mật. Mặc định rỗng.
 * @param string $after  Tùy chọn. Hiển thị sau liên kết chính sách bảo mật. Mặc định rỗng.
 */
function the_privacy_policy_link( $before = '', $after = '' ) {
	echo get_the_privacy_policy_link( $before, $after );
}

/**
 * Trả về liên kết chính sách bảo mật với định dạng, khi có thể áp dụng.
 *
 * @since 4.9.6
 * @since 6.2.0 Thêm thuộc tính rel 'privacy-policy'.
 *
 * @param string $before Tùy chọn. Hiển thị trước liên kết chính sách bảo mật. Mặc định rỗng.
 * @param string $after  Tùy chọn. Hiển thị sau liên kết chính sách bảo mật. Mặc định rỗng.
 * @return string Markup cho liên kết và các phần tử xung quanh. Chuỗi rỗng nếu
 *                không tồn tại.
 */
function get_the_privacy_policy_link( $before = '', $after = '' ) {
	$link               = '';
	$privacy_policy_url = get_privacy_policy_url();
	$policy_page_id     = (int) get_option( 'wp_page_for_privacy_policy' );
	$page_title         = ( $policy_page_id ) ? get_the_title( $policy_page_id ) : '';

	if ( $privacy_policy_url && $page_title ) {
		$link = sprintf(
			'<a class="privacy-policy-link" href="%s" rel="privacy-policy">%s</a>',
			esc_url( $privacy_policy_url ),
			esc_html( $page_title )
		);
	}

	/**
	 * Lọc liên kết chính sách bảo mật.
	 *
	 * @since 4.9.6
	 *
	 * @param string $link               Liên kết chính sách bảo mật. Chuỗi rỗng nếu
	 *                                   không tồn tại.
	 * @param string $privacy_policy_url URL của chính sách bảo mật. Chuỗi rỗng
	 *                                   nếu không tồn tại.
	 */
	$link = apply_filters( 'the_privacy_policy_link', $link, $privacy_policy_url );

	if ( $link ) {
		return $before . $link . $after;
	}

	return '';
}

/**
 * Trả về mảng các host URL được coi là host nội bộ.
 *
 * Theo mặc định, danh sách các host nội bộ bao gồm tên host của
 * home_url() của trang web (được phân tích bởi wp_parse_url()).
 *
 * Danh sách này được sử dụng khi xác định xem một URL được chỉ định là liên kết đến trang
 * trên chính trang web hay liên kết ra bên ngoài (đến host bên ngoài). Điều này được sử dụng, ví dụ,
 * khi xác định xem thuộc tính "nofollow" có nên được áp dụng cho một
 * liên kết hay không.
 *
 * @see wp_is_internal_link
 *
 * @since 6.2.0
 *
 * @return string[] Mảng các host URL.
 */
function wp_internal_hosts() {
	static $internal_hosts;

	if ( empty( $internal_hosts ) ) {
		/**
		 * Lọc mảng các host URL được coi là nội bộ.
		 *
		 * @since 6.2.0
		 *
		 * @param string[] $internal_hosts Mảng các tên host URL nội bộ.
		 */
		$internal_hosts = apply_filters(
			'wp_internal_hosts',
			array(
				wp_parse_url( home_url(), PHP_URL_HOST ),
			)
		);
		$internal_hosts = array_unique(
			array_map( 'strtolower', (array) $internal_hosts )
		);
	}

	return $internal_hosts;
}

/**
 * Xác định xem URL được chỉ định có thuộc host nằm trong danh sách host nội bộ hay không.
 *
 * @see wp_internal_hosts()
 *
 * @since 6.2.0
 *
 * @param string $link URL cần kiểm tra.
 * @return bool Trả về true cho URL nội bộ và false cho tất cả URL khác.
 */
function wp_is_internal_link( $link ) {
	$link = strtolower( $link );
	if ( in_array( wp_parse_url( $link, PHP_URL_SCHEME ), wp_allowed_protocols(), true ) ) {
		return in_array( wp_parse_url( $link, PHP_URL_HOST ), wp_internal_hosts(), true );
	}
	return false;
}
