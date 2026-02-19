<?php
/**
 * API Liên kết/Dấu trang
 *
 * @package WordPress
 * @subpackage Bookmark
 */

/**
 * Lấy dữ liệu dấu trang.
 *
 * @since 2.1.0
 *
 * @global object $link Đối tượng liên kết hiện tại.
 * @global wpdb   $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int|stdClass $bookmark
 * @param string       $output   Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                               tương ứng với đối tượng stdClass, mảng kết hợp, hoặc mảng số.
 *                               Mặc định OBJECT.
 * @param string       $filter   Tùy chọn. Cách làm sạch các trường dấu trang. Mặc định 'raw'.
 * @return array|object|null Kiểu trả về phụ thuộc vào giá trị $output.
 */
function get_bookmark( $bookmark, $output = OBJECT, $filter = 'raw' ) {
	global $wpdb;

	if ( empty( $bookmark ) ) {
		if ( isset( $GLOBALS['link'] ) ) {
			$_bookmark = & $GLOBALS['link'];
		} else {
			$_bookmark = null;
		}
	} elseif ( is_object( $bookmark ) ) {
		wp_cache_add( $bookmark->link_id, $bookmark, 'bookmark' );
		$_bookmark = $bookmark;
	} else {
		if ( isset( $GLOBALS['link'] ) && ( $GLOBALS['link']->link_id === $bookmark ) ) {
			$_bookmark = & $GLOBALS['link'];
		} else {
			$_bookmark = wp_cache_get( $bookmark, 'bookmark' );
			if ( ! $_bookmark ) {
				$_bookmark = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->links WHERE link_id = %d LIMIT 1", $bookmark ) );
				if ( $_bookmark ) {
					$_bookmark->link_category = array_unique( wp_get_object_terms( $_bookmark->link_id, 'link_category', array( 'fields' => 'ids' ) ) );
					wp_cache_add( $_bookmark->link_id, $_bookmark, 'bookmark' );
				}
			}
		}
	}

	if ( ! $_bookmark ) {
		return $_bookmark;
	}

	$_bookmark = sanitize_bookmark( $_bookmark, $filter );

	if ( OBJECT === $output ) {
		return $_bookmark;
	} elseif ( ARRAY_A === $output ) {
		return get_object_vars( $_bookmark );
	} elseif ( ARRAY_N === $output ) {
		return array_values( get_object_vars( $_bookmark ) );
	} else {
		return $_bookmark;
	}
}

/**
 * Lấy một mục dữ liệu hoặc trường đơn lẻ của dấu trang.
 *
 * @since 2.3.0
 *
 * @param string $field    Tên của trường dữ liệu cần trả về.
 * @param int    $bookmark ID dấu trang cần lấy trường.
 * @param string $context  Tùy chọn. Ngữ cảnh sử dụng trường. Mặc định 'display'.
 * @return string|WP_Error
 */
function get_bookmark_field( $field, $bookmark, $context = 'display' ) {
	$bookmark = (int) $bookmark;
	$bookmark = get_bookmark( $bookmark );

	if ( is_wp_error( $bookmark ) ) {
		return $bookmark;
	}

	if ( ! is_object( $bookmark ) ) {
		return '';
	}

	if ( ! isset( $bookmark->$field ) ) {
		return '';
	}

	return sanitize_bookmark_field( $field, $bookmark->$field, $bookmark->link_id, $context );
}

/**
 * Lấy danh sách dấu trang.
 *
 * Cố gắng lấy từ bộ nhớ đệm trước dựa trên mã băm MD5 của tham số. Nếu
 * thất bại, truy vấn sẽ được xây dựng từ các tham số và thực thi. Kết quả
 * sẽ được lưu vào bộ nhớ đệm.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string|array $args {
 *     Tùy chọn. Chuỗi hoặc mảng tham số để lấy dấu trang.
 *
 *     @type string   $orderby        Cách sắp xếp liên kết. Chấp nhận 'id', 'link_id', 'name', 'link_name',
 *                                    'url', 'link_url', 'visible', 'link_visible', 'rating', 'link_rating',
 *                                    'owner', 'link_owner', 'updated', 'link_updated', 'notes', 'link_notes',
 *                                    'description', 'link_description', 'length' và 'rand'.
 *                                    Khi `$orderby` là 'length', sắp xếp theo độ dài ký tự của
 *                                    'link_name'. Mặc định 'name'.
 *     @type string   $order          Sắp xếp dấu trang theo thứ tự tăng dần hay giảm dần.
 *                                    Chấp nhận 'ASC' (tăng dần) hoặc 'DESC' (giảm dần). Mặc định 'ASC'.
 *     @type int      $limit          Số lượng dấu trang hiển thị. Chấp nhận bất kỳ số dương nào hoặc
 *                                    -1 cho tất cả. Mặc định -1.
 *     @type string   $category       Danh sách ID chuyên mục phân cách bằng dấu phẩy để bao gồm liên kết.
 *                                    Mặc định rỗng.
 *     @type string   $category_name  Tên chuyên mục để lấy liên kết. Mặc định rỗng.
 *     @type int|bool $hide_invisible Hiện hay ẩn các liên kết được đánh dấu 'invisible'. Chấp nhận
 *                                    1|true hoặc 0|false. Mặc định 1|true.
 *     @type int|bool $show_updated   Hiển thị thời gian dấu trang được cập nhật lần cuối hay không.
 *                                    Chấp nhận 1|true hoặc 0|false. Mặc định 0|false.
 *     @type string   $include        Danh sách ID dấu trang phân cách bằng dấu phẩy để bao gồm. Mặc định rỗng.
 *     @type string   $exclude        Danh sách ID dấu trang phân cách bằng dấu phẩy để loại trừ. Mặc định rỗng.
 *     @type string   $search         Từ khóa tìm kiếm. Sẽ được định dạng SQL với ký tự đại diện trước và sau
 *                                    và tìm trong 'link_url', 'link_name' và 'link_description'.
 *                                    Mặc định rỗng.
 * }
 * @return object[] Danh sách các đối tượng hàng dấu trang.
 */
function get_bookmarks( $args = '' ) {
	global $wpdb;

	$defaults = array(
		'orderby'        => 'name',
		'order'          => 'ASC',
		'limit'          => -1,
		'category'       => '',
		'category_name'  => '',
		'hide_invisible' => 1,
		'show_updated'   => 0,
		'include'        => '',
		'exclude'        => '',
		'search'         => '',
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	$key   = md5( serialize( $parsed_args ) );
	$cache = wp_cache_get( 'get_bookmarks', 'bookmark' );

	if ( 'rand' !== $parsed_args['orderby'] && $cache ) {
		if ( is_array( $cache ) && isset( $cache[ $key ] ) ) {
			$bookmarks = $cache[ $key ];
			/**
			 * Lọc danh sách dấu trang trả về.
			 *
			 * Lần đầu hook được đánh giá trong file này, nó trả về danh sách
			 * dấu trang từ bộ nhớ đệm. Lần đánh giá thứ hai trả về danh sách dấu trang
			 * từ bộ nhớ đệm nếu chuyên mục liên kết được truyền nhưng không tồn tại.
			 * Lần đánh giá thứ ba trả về toàn bộ kết quả từ bộ nhớ đệm.
			 *
			 * @since 2.1.0
			 *
			 * @see get_bookmarks()
			 *
			 * @param array $bookmarks   Danh sách dấu trang từ bộ nhớ đệm.
			 * @param array $parsed_args Mảng tham số truy vấn dấu trang.
			 */
			return apply_filters( 'get_bookmarks', $bookmarks, $parsed_args );
		}
	}

	if ( ! is_array( $cache ) ) {
		$cache = array();
	}

	$inclusions = '';
	if ( ! empty( $parsed_args['include'] ) ) {
		$parsed_args['exclude']       = '';  // Bỏ qua tham số exclude, category, và category_name nếu sử dụng include.
		$parsed_args['category']      = '';
		$parsed_args['category_name'] = '';

		$inclinks = wp_parse_id_list( $parsed_args['include'] );
		if ( count( $inclinks ) ) {
			foreach ( $inclinks as $inclink ) {
				if ( empty( $inclusions ) ) {
					$inclusions = ' AND ( link_id = ' . $inclink . ' ';
				} else {
					$inclusions .= ' OR link_id = ' . $inclink . ' ';
				}
			}
		}
	}
	if ( ! empty( $inclusions ) ) {
		$inclusions .= ')';
	}

	$exclusions = '';
	if ( ! empty( $parsed_args['exclude'] ) ) {
		$exlinks = wp_parse_id_list( $parsed_args['exclude'] );
		if ( count( $exlinks ) ) {
			foreach ( $exlinks as $exlink ) {
				if ( empty( $exclusions ) ) {
					$exclusions = ' AND ( link_id <> ' . $exlink . ' ';
				} else {
					$exclusions .= ' AND link_id <> ' . $exlink . ' ';
				}
			}
		}
	}
	if ( ! empty( $exclusions ) ) {
		$exclusions .= ')';
	}

	if ( ! empty( $parsed_args['category_name'] ) ) {
		$parsed_args['category'] = get_term_by( 'name', $parsed_args['category_name'], 'link_category' );
		if ( $parsed_args['category'] ) {
			$parsed_args['category'] = $parsed_args['category']->term_id;
		} else {
			$cache[ $key ] = array();
			wp_cache_set( 'get_bookmarks', $cache, 'bookmark' );
			/** Bộ lọc này được ghi tài liệu trong wp-includes/bookmark.php */
			return apply_filters( 'get_bookmarks', array(), $parsed_args );
		}
	}

	$search = '';
	if ( ! empty( $parsed_args['search'] ) ) {
		$like   = '%' . $wpdb->esc_like( $parsed_args['search'] ) . '%';
		$search = $wpdb->prepare( ' AND ( (link_url LIKE %s) OR (link_name LIKE %s) OR (link_description LIKE %s) ) ', $like, $like, $like );
	}

	$category_query = '';
	$join           = '';
	if ( ! empty( $parsed_args['category'] ) ) {
		$incategories = wp_parse_id_list( $parsed_args['category'] );
		if ( count( $incategories ) ) {
			foreach ( $incategories as $incat ) {
				if ( empty( $category_query ) ) {
					$category_query = ' AND ( tt.term_id = ' . $incat . ' ';
				} else {
					$category_query .= ' OR tt.term_id = ' . $incat . ' ';
				}
			}
		}
	}
	if ( ! empty( $category_query ) ) {
		$category_query .= ") AND taxonomy = 'link_category'";
		$join            = " INNER JOIN $wpdb->term_relationships AS tr ON ($wpdb->links.link_id = tr.object_id) INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
	}

	if ( $parsed_args['show_updated'] ) {
		$recently_updated_test = ', IF (DATE_ADD(link_updated, INTERVAL 120 MINUTE) >= NOW(), 1,0) as recently_updated ';
	} else {
		$recently_updated_test = '';
	}

	$get_updated = ( $parsed_args['show_updated'] ) ? ', UNIX_TIMESTAMP(link_updated) AS link_updated_f ' : '';

	$orderby = strtolower( $parsed_args['orderby'] );
	$length  = '';
	switch ( $orderby ) {
		case 'length':
			$length = ', CHAR_LENGTH(link_name) AS length';
			break;
		case 'rand':
			$orderby = 'rand()';
			break;
		case 'link_id':
			$orderby = "$wpdb->links.link_id";
			break;
		default:
			$orderparams = array();
			$keys        = array( 'link_id', 'link_name', 'link_url', 'link_visible', 'link_rating', 'link_owner', 'link_updated', 'link_notes', 'link_description' );
			foreach ( explode( ',', $orderby ) as $ordparam ) {
				$ordparam = trim( $ordparam );

				if ( in_array( 'link_' . $ordparam, $keys, true ) ) {
					$orderparams[] = 'link_' . $ordparam;
				} elseif ( in_array( $ordparam, $keys, true ) ) {
					$orderparams[] = $ordparam;
				}
			}
			$orderby = implode( ',', $orderparams );
	}

	if ( empty( $orderby ) ) {
		$orderby = 'link_name';
	}

	$order = strtoupper( $parsed_args['order'] );
	if ( '' !== $order && ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
		$order = 'ASC';
	}

	$visible = '';
	if ( $parsed_args['hide_invisible'] ) {
		$visible = "AND link_visible = 'Y'";
	}

	$query  = "SELECT * $length $recently_updated_test $get_updated FROM $wpdb->links $join WHERE 1=1 $visible $category_query";
	$query .= " $exclusions $inclusions $search";
	$query .= " ORDER BY $orderby $order";
	if ( -1 !== $parsed_args['limit'] ) {
		$query .= ' LIMIT ' . absint( $parsed_args['limit'] );
	}

	$results = $wpdb->get_results( $query );

	if ( 'rand()' !== $orderby ) {
		$cache[ $key ] = $results;
		wp_cache_set( 'get_bookmarks', $cache, 'bookmark' );
	}

	/** Bộ lọc này được ghi tài liệu trong wp-includes/bookmark.php */
	return apply_filters( 'get_bookmarks', $results, $parsed_args );
}

/**
 * Làm sạch tất cả các trường dấu trang.
 *
 * @since 2.3.0
 *
 * @param stdClass|array $bookmark Hàng dấu trang.
 * @param string         $context  Tùy chọn. Cách lọc các trường. Mặc định 'display'.
 * @return stdClass|array Cùng kiểu với $bookmark nhưng với các trường đã được làm sạch.
 */
function sanitize_bookmark( $bookmark, $context = 'display' ) {
	$fields = array(
		'link_id',
		'link_url',
		'link_name',
		'link_image',
		'link_target',
		'link_category',
		'link_description',
		'link_visible',
		'link_owner',
		'link_rating',
		'link_updated',
		'link_rel',
		'link_notes',
		'link_rss',
	);

	if ( is_object( $bookmark ) ) {
		$do_object = true;
		$link_id   = $bookmark->link_id;
	} else {
		$do_object = false;
		$link_id   = $bookmark['link_id'];
	}

	foreach ( $fields as $field ) {
		if ( $do_object ) {
			if ( isset( $bookmark->$field ) ) {
				$bookmark->$field = sanitize_bookmark_field( $field, $bookmark->$field, $link_id, $context );
			}
		} else {
			if ( isset( $bookmark[ $field ] ) ) {
				$bookmark[ $field ] = sanitize_bookmark_field( $field, $bookmark[ $field ], $link_id, $context );
			}
		}
	}

	return $bookmark;
}

/**
 * Làm sạch một trường dấu trang.
 *
 * Làm sạch các trường dấu trang dựa trên tên trường. Nếu trường
 * có tập giá trị nghiêm ngặt, nó sẽ được kiểm tra cho giá trị đó, nếu không thì
 * áp dụng lọc tổng quát hơn. Sau khi bộ lọc nghiêm ngặt hơn được áp dụng, nếu `$context`
 * là 'raw' thì giá trị được trả về ngay lập tức.
 *
 * Hook tồn tại cho các trường hợp tổng quát hơn. Với ngữ cảnh 'edit', bộ lọc {@see 'edit_$field'}
 * sẽ được gọi và truyền `$value` và `$bookmark_id` tương ứng.
 *
 * Với ngữ cảnh 'db', bộ lọc {@see 'pre_$field'} được gọi và truyền giá trị.
 * Ngữ cảnh 'display' là ngữ cảnh cuối cùng và có `$field` làm tên bộ lọc
 * và được truyền `$value`, `$bookmark_id`, và `$context` tương ứng.
 *
 * @since 2.3.0
 *
 * @param string $field       Trường dấu trang.
 * @param mixed  $value       Giá trị trường dấu trang.
 * @param int    $bookmark_id ID dấu trang.
 * @param string $context     Cách lọc giá trị trường. Chấp nhận 'raw', 'edit', 'db',
 *                            'display', 'attribute', hoặc 'js'. Mặc định 'display'.
 * @return mixed Giá trị đã được lọc.
 */
function sanitize_bookmark_field( $field, $value, $bookmark_id, $context ) {
	$int_fields = array( 'link_id', 'link_rating' );
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}

	switch ( $field ) {
		case 'link_category': // mảng( số nguyên )
			$value = array_map( 'absint', (array) $value );
			/*
			 * Chúng ta trả về ở đây để các chuyên mục không bị lọc.
			 * Bộ lọc 'link_category' dành cho tên chuyên mục liên kết, không phải mảng chuyên mục liên kết của một liên kết.
			 */
			return $value;

		case 'link_visible': // boolean lưu dạng Y|N
			$value = preg_replace( '/[^YNyn]/', '', $value );
			break;
		case 'link_target': // "enum"
			$targets = array( '_top', '_blank' );
			if ( ! in_array( $value, $targets, true ) ) {
				$value = '';
			}
			break;
	}

	if ( 'raw' === $context ) {
		return $value;
	}

	if ( 'edit' === $context ) {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/post.php */
		$value = apply_filters( "edit_{$field}", $value, $bookmark_id );

		if ( 'link_notes' === $field ) {
			$value = esc_html( $value ); // textarea_escaped
		} else {
			$value = esc_attr( $value );
		}
	} elseif ( 'db' === $context ) {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/post.php */
		$value = apply_filters( "pre_{$field}", $value );
	} else {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/post.php */
		$value = apply_filters( "{$field}", $value, $bookmark_id, $context );

		if ( 'attribute' === $context ) {
			$value = esc_attr( $value );
		} elseif ( 'js' === $context ) {
			$value = esc_js( $value );
		}
	}

	// Khôi phục kiểu dữ liệu cho các trường số nguyên sau esc_attr().
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}

	return $value;
}

/**
 * Xóa bộ nhớ đệm dấu trang.
 *
 * @since 2.7.0
 *
 * @param int $bookmark_id ID dấu trang.
 */
function clean_bookmark_cache( $bookmark_id ) {
	wp_cache_delete( $bookmark_id, 'bookmark' );
	wp_cache_delete( 'get_bookmarks', 'bookmark' );
	clean_object_term_cache( $bookmark_id, 'link' );
}
