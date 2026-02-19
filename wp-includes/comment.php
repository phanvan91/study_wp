<?php
/**
 * API bình luận lõi
 *
 * @package WordPress
 * @subpackage Comment
 */

/**
 * Kiểm tra xem bình luận có vượt qua các kiểm tra nội bộ để được phép thêm hay không.
 *
 * Nếu kiểm duyệt bình luận thủ công được bật trong trang quản trị, thì tất cả các kiểm tra,
 * bất kể loại và nội dung, sẽ thất bại và hàm sẽ trả về false.
 *
 * Nếu số lượng liên kết vượt quá giới hạn trong trang quản trị, thì kiểm tra
 * thất bại. Nếu bất kỳ nội dung tham số nào chứa từ không được phép,
 * thì kiểm tra thất bại.
 *
 * Nếu tác giả bình luận đã được phê duyệt trước đó, thì bình luận sẽ tự động
 * được phê duyệt.
 *
 * Nếu tất cả kiểm tra đều đạt, hàm sẽ trả về true.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $author       Tên tác giả bình luận.
 * @param string $email        Email tác giả bình luận.
 * @param string $url          URL tác giả bình luận.
 * @param string $comment      Nội dung bình luận.
 * @param string $user_ip      Địa chỉ IP tác giả bình luận.
 * @param string $user_agent   User-Agent của tác giả bình luận.
 * @param string $comment_type Loại bình luận, có thể là bình luận do người dùng gửi,
 *                             trackback, hoặc pingback.
 * @return bool Nếu tất cả kiểm tra đều đạt thì true, ngược lại false.
 */
function check_comment( $author, $email, $url, $comment, $user_ip, $user_agent, $comment_type ) {
	global $wpdb;

	// Nếu kiểm duyệt thủ công được bật, bỏ qua tất cả kiểm tra và trả về false.
	if ( '1' === get_option( 'comment_moderation' ) ) {
		return false;
	}

	/** Bộ lọc này được ghi tài liệu trong wp-includes/comment-template.php */
	$comment = apply_filters( 'comment_text', $comment, null, array() );

	// Kiểm tra số lượng liên kết bên ngoài nếu đã thiết lập số lượng tối đa cho phép.
	$max_links = get_option( 'comment_max_links' );
	if ( $max_links ) {
		$num_links = preg_match_all( '/<a [^>]*href/i', $comment, $out );

		/**
		 * Lọc số lượng liên kết tìm thấy trong bình luận.
		 *
		 * @since 3.0.0
		 * @since 4.7.0 Thêm tham số `$comment`.
		 *
		 * @param int    $num_links Số lượng liên kết tìm thấy.
		 * @param string $url       URL của tác giả bình luận. Được tính trong tổng liên kết cho phép.
		 * @param string $comment   Nội dung bình luận.
		 */
		$num_links = apply_filters( 'comment_max_links_url', $num_links, $url, $comment );

		/*
		 * Nếu số lượng liên kết trong bình luận vượt quá giới hạn cho phép,
		 * kiểm tra thất bại bằng cách trả về false.
		 */
		if ( $num_links >= $max_links ) {
			return false;
		}
	}

	$mod_keys = trim( get_option( 'moderation_keys' ) );

	// Nếu các 'key' kiểm duyệt (từ khóa) đã được thiết lập, xử lý chúng.
	if ( ! empty( $mod_keys ) ) {
		$words = explode( "\n", $mod_keys );

		foreach ( (array) $words as $word ) {
			$word = trim( $word );

			// Bỏ qua các dòng trống.
			if ( empty( $word ) ) {
				continue;
			}

			/*
			 * Thực hiện một số xử lý escape để ký tự '#' (ký hiệu số) trong
			 * các từ spam không gây lỗi:
			 */
			$word = preg_quote( $word, '#' );

			/*
			 * Kiểm tra các trường bình luận với từ khóa kiểm duyệt. Nếu tìm thấy bất kỳ từ khóa nào,
			 * kiểm tra thất bại cho trường đó bằng cách trả về false.
			 */
			$pattern = "#$word#iu";
			if ( preg_match( $pattern, $author ) ) {
				return false;
			}
			if ( preg_match( $pattern, $email ) ) {
				return false;
			}
			if ( preg_match( $pattern, $url ) ) {
				return false;
			}
			if ( preg_match( $pattern, $comment ) ) {
				return false;
			}
			if ( preg_match( $pattern, $user_ip ) ) {
				return false;
			}
			if ( preg_match( $pattern, $user_agent ) ) {
				return false;
			}
		}
	}

	/*
	 * Kiểm tra xem tùy chọn phê duyệt bình luận từ tác giả đã được phê duyệt trước đó có được bật không.
	 *
	 * Nếu được bật, kiểm tra xem tác giả bình luận có bình luận đã được phê duyệt trước đó hay không,
	 * cũng như kiểm tra xem có từ khóa kiểm duyệt nào (nếu được thiết lập) có mặt trong địa chỉ
	 * email tác giả hay không. Nếu cả hai kiểm tra đều đạt, trả về true. Ngược lại, trả về false.
	 */
	if ( '1' === get_option( 'comment_previously_approved' ) ) {
		if ( 'trackback' !== $comment_type && 'pingback' !== $comment_type && '' !== $author && '' !== $email ) {
			$comment_user = get_user_by( 'email', wp_unslash( $email ) );
			if ( ! empty( $comment_user->ID ) ) {
				$ok_to_comment = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT comment_approved
						FROM $wpdb->comments
						WHERE user_id = %d
						AND comment_approved = '1'
						LIMIT 1",
						$comment_user->ID
					)
				);
			} else {
				// dự kiến đã có dấu gạch chéo ($author, $email)
				$ok_to_comment = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT comment_approved
						FROM $wpdb->comments
						WHERE comment_author = %s
						AND comment_author_email = %s
						AND comment_approved = '1'
						LIMIT 1",
						$author,
						$email
					)
				);
			}

			if ( '1' === $ok_to_comment && ( empty( $mod_keys ) || ! str_contains( $email, $mod_keys ) ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	return true;
}

/**
 * Lấy các bình luận đã được phê duyệt cho một bài viết.
 *
 * @since 2.0.0
 * @since 4.1.0 Tái cấu trúc để sử dụng WP_Comment_Query thay vì truy vấn trực tiếp.
 *
 * @param int   $post_id ID của bài viết.
 * @param array $args    {
 *     Tùy chọn. Xem WP_Comment_Query::__construct() để biết thông tin về các tham số được chấp nhận.
 *
 *     @type int    $status  Trạng thái bình luận để giới hạn kết quả. Mặc định là bình luận đã phê duyệt.
 *     @type int    $post_id Giới hạn kết quả cho bài viết có ID nhất định.
 *     @type string $order   Cách sắp xếp các bình luận. Mặc định 'ASC'.
 * }
 * @return WP_Comment[]|int[]|int Các bình luận đã phê duyệt, hoặc số lượng bình luận nếu tham số `$count` là true.
 */
function get_approved_comments( $post_id, $args = array() ) {
	if ( ! $post_id ) {
		return array();
	}

	$defaults    = array(
		'status'  => 1,
		'post_id' => $post_id,
		'order'   => 'ASC',
	);
	$parsed_args = wp_parse_args( $args, $defaults );

	$query = new WP_Comment_Query();
	return $query->query( $parsed_args );
}

/**
 * Lấy dữ liệu bình luận dựa trên ID hoặc đối tượng bình luận.
 *
 * Nếu một đối tượng được truyền vào thì dữ liệu bình luận sẽ được lưu cache và sau đó trả về
 * sau khi đi qua bộ lọc. Nếu bình luận trống, thì biến bình luận toàn cục
 * sẽ được sử dụng, nếu nó đã được thiết lập.
 *
 * @since 2.0.0
 *
 * @global WP_Comment $comment Đối tượng bình luận toàn cục.
 *
 * @param WP_Comment|string|int $comment Bình luận cần lấy.
 * @param string                $output  Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                                       tương ứng với đối tượng WP_Comment, mảng liên kết, hoặc mảng số.
 *                                       Mặc định OBJECT.
 * @return WP_Comment|array|null Phụ thuộc vào giá trị $output.
 */
function get_comment( $comment = null, $output = OBJECT ) {
	if ( empty( $comment ) && isset( $GLOBALS['comment'] ) ) {
		$comment = $GLOBALS['comment'];
	}

	if ( $comment instanceof WP_Comment ) {
		$_comment = $comment;
	} elseif ( is_object( $comment ) ) {
		$_comment = new WP_Comment( $comment );
	} else {
		$_comment = WP_Comment::get_instance( $comment );
	}

	if ( ! $_comment ) {
		return null;
	}

	/**
	 * Kích hoạt sau khi lấy được một bình luận.
	 *
	 * @since 2.3.0
	 *
	 * @param WP_Comment $_comment Dữ liệu bình luận.
	 */
	$_comment = apply_filters( 'get_comment', $_comment );

	if ( OBJECT === $output ) {
		return $_comment;
	} elseif ( ARRAY_A === $output ) {
		return $_comment->to_array();
	} elseif ( ARRAY_N === $output ) {
		return array_values( $_comment->to_array() );
	}
	return $_comment;
}

/**
 * Lấy danh sách bình luận.
 *
 * Danh sách bình luận có thể cho toàn bộ blog hoặc cho một bài viết riêng lẻ.
 *
 * @since 2.7.0
 *
 * @param string|array $args Tùy chọn. Mảng hoặc chuỗi tham số. Xem WP_Comment_Query::__construct()
 *                           để biết thông tin về các tham số được chấp nhận. Mặc định chuỗi rỗng.
 * @return WP_Comment[]|int[]|int Danh sách bình luận hoặc số lượng bình luận tìm thấy nếu tham số `$count` là true.
 */
function get_comments( $args = '' ) {
	$query = new WP_Comment_Query();
	return $query->query( $args );
}

/**
 * Lấy tất cả các trạng thái bình luận được WordPress hỗ trợ.
 *
 * Bình luận có một tập hợp giới hạn các giá trị trạng thái hợp lệ, hàm này cung cấp
 * các giá trị và mô tả trạng thái bình luận.
 *
 * @since 2.7.0
 *
 * @return string[] Danh sách nhãn trạng thái bình luận được đánh chỉ mục theo trạng thái.
 */
function get_comment_statuses() {
	$status = array(
		'hold'    => __( 'Unapproved' ),
		'approve' => _x( 'Approved', 'comment status' ),
		'spam'    => _x( 'Spam', 'comment status' ),
		'trash'   => _x( 'Trash', 'comment status' ),
	);

	return $status;
}

/**
 * Lấy trạng thái bình luận mặc định cho một loại bài viết.
 *
 * @since 4.3.0
 *
 * @param string $post_type    Tùy chọn. Loại bài viết. Mặc định 'post'.
 * @param string $comment_type Tùy chọn. Loại bình luận. Mặc định 'comment'.
 * @return string 'open' hoặc 'closed'.
 */
function get_default_comment_status( $post_type = 'post', $comment_type = 'comment' ) {
	switch ( $comment_type ) {
		case 'pingback':
		case 'trackback':
			$supports = 'trackbacks';
			$option   = 'ping';
			break;
		default:
			$supports = 'comments';
			$option   = 'comment';
			break;
	}

	// Thiết lập trạng thái.
	if ( 'page' === $post_type ) {
		$status = 'closed';
	} elseif ( post_type_supports( $post_type, $supports ) ) {
		$status = get_option( "default_{$option}_status" );
	} else {
		$status = 'closed';
	}

	/**
	 * Lọc trạng thái bình luận mặc định cho loại bài viết nhất định.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status       Trạng thái mặc định cho loại bài viết nhất định,
	 *                             'open' hoặc 'closed'.
	 * @param string $post_type    Loại bài viết. Mặc định là `post`.
	 * @param string $comment_type Loại bình luận. Mặc định là `comment`.
	 */
	return apply_filters( 'get_default_comment_status', $status, $post_type, $comment_type );
}

/**
 * Lấy ngày bình luận cuối cùng được chỉnh sửa.
 *
 * @since 1.5.0
 * @since 4.7.0 Thay thế việc lưu cache ngày chỉnh sửa trong biến tĩnh cục bộ
 *              bằng API Object Cache.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $timezone Múi giờ nào sẽ sử dụng, tham chiếu đến 'gmt', 'blog', hoặc 'server'.
 * @return string|false Ngày chỉnh sửa bình luận cuối cùng khi thành công, false khi thất bại.
 */
function get_lastcommentmodified( $timezone = 'server' ) {
	global $wpdb;

	$timezone = strtolower( $timezone );
	$key      = "lastcommentmodified:$timezone";

	$comment_modified_date = wp_cache_get( $key, 'timeinfo' );
	if ( false !== $comment_modified_date ) {
		return $comment_modified_date;
	}

	switch ( $timezone ) {
		case 'gmt':
			$comment_modified_date = $wpdb->get_var( "SELECT comment_date_gmt FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 1" );
			break;
		case 'blog':
			$comment_modified_date = $wpdb->get_var( "SELECT comment_date FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 1" );
			break;
		case 'server':
			$add_seconds_server = gmdate( 'Z' );

			$comment_modified_date = $wpdb->get_var( $wpdb->prepare( "SELECT DATE_ADD(comment_date_gmt, INTERVAL %s SECOND) FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 1", $add_seconds_server ) );
			break;
	}

	if ( $comment_modified_date ) {
		wp_cache_set( $key, $comment_modified_date, 'timeinfo' );

		return $comment_modified_date;
	}

	return false;
}

/**
 * Lấy tổng số lượng bình luận cho toàn bộ trang web hoặc một bài viết.
 *
 * @since 2.0.0
 *
 * @param int $post_id Tùy chọn. Giới hạn số lượng bình luận cho bài viết nhất định. Mặc định 0, nghĩa là
 *                     sẽ lấy số lượng bình luận cho toàn bộ trang web.
 * @return int[] {
 *     Số lượng bình luận được đánh chỉ mục theo trạng thái.
 *
 *     @type int $approved            Số lượng bình luận đã phê duyệt.
 *     @type int $awaiting_moderation Số lượng bình luận đang chờ kiểm duyệt (tức là đang chờ xử lý).
 *     @type int $spam                Số lượng bình luận spam.
 *     @type int $trash               Số lượng bình luận đã bị xóa.
 *     @type int $post-trashed        Số lượng bình luận cho các bài viết đã bị xóa.
 *     @type int $total_comments      Tổng số bình luận chưa bị xóa, bao gồm spam.
 *     @type int $all                 Tổng số bình luận đang chờ hoặc đã phê duyệt.
 * }
 */
function get_comment_count( $post_id = 0 ) {
	$post_id = (int) $post_id;

	$comment_count = array(
		'approved'            => 0,
		'awaiting_moderation' => 0,
		'spam'                => 0,
		'trash'               => 0,
		'post-trashed'        => 0,
		'total_comments'      => 0,
		'all'                 => 0,
	);

	$args = array(
		'count'                     => true,
		'update_comment_meta_cache' => false,
		'orderby'                   => 'none',
	);
	if ( $post_id > 0 ) {
		$args['post_id'] = $post_id;
	}
	$mapping       = array(
		'approved'            => 'approve',
		'awaiting_moderation' => 'hold',
		'spam'                => 'spam',
		'trash'               => 'trash',
		'post-trashed'        => 'post-trashed',
	);
	$comment_count = array();
	foreach ( $mapping as $key => $value ) {
		$comment_count[ $key ] = get_comments( array_merge( $args, array( 'status' => $value ) ) );
	}

	$comment_count['all']            = $comment_count['approved'] + $comment_count['awaiting_moderation'];
	$comment_count['total_comments'] = $comment_count['all'] + $comment_count['spam'];

	return array_map( 'intval', $comment_count );
}

//
// Các hàm meta bình luận.
//

/**
 * Thêm trường dữ liệu meta cho bình luận.
 *
 * @since 2.9.0
 *
 * @link https://developer.wordpress.org/reference/functions/add_comment_meta/
 *
 * @param int    $comment_id ID bình luận.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Giá trị metadata. Mảng và đối tượng được lưu dưới dạng dữ liệu đã serialize và
 *                           sẽ được trả về cùng kiểu khi lấy ra. Các kiểu dữ liệu khác sẽ
 *                           được lưu dưới dạng chuỗi trong cơ sở dữ liệu:
 *                           - false được lưu và trả về dưới dạng chuỗi rỗng ('')
 *                           - true được lưu và trả về dưới dạng '1'
 *                           - số (cả số nguyên và số thực) được lưu và trả về dưới dạng chuỗi
 *                           Phải có khả năng serialize nếu không phải kiểu vô hướng.
 * @param bool   $unique     Tùy chọn. Liệu cùng một key có nên không được thêm.
 *                           Mặc định false.
 * @return int|false ID meta khi thành công, false khi thất bại.
 */
function add_comment_meta( $comment_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'comment', $comment_id, $meta_key, $meta_value, $unique );
}

/**
 * Xóa metadata phù hợp với tiêu chí từ một bình luận.
 *
 * Bạn có thể khớp dựa trên key, hoặc key và giá trị. Xóa dựa trên key và
 * giá trị sẽ tránh việc xóa metadata trùng lặp có cùng key. Nó cũng
 * cho phép xóa tất cả metadata khớp với key, nếu cần.
 *
 * @since 2.9.0
 *
 * @link https://developer.wordpress.org/reference/functions/delete_comment_meta/
 *
 * @param int    $comment_id ID bình luận.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Tùy chọn. Giá trị metadata. Nếu được cung cấp,
 *                           chỉ các hàng khớp với giá trị mới bị xóa.
 *                           Phải có khả năng serialize nếu không phải kiểu vô hướng. Mặc định chuỗi rỗng.
 * @return bool True khi thành công, false khi thất bại.
 */
function delete_comment_meta( $comment_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'comment', $comment_id, $meta_key, $meta_value );
}

/**
 * Lấy trường meta của bình luận.
 *
 * @since 2.9.0
 *
 * @link https://developer.wordpress.org/reference/functions/get_comment_meta/
 *
 * @param int    $comment_id ID bình luận.
 * @param string $key        Tùy chọn. Key meta cần lấy. Mặc định,
 *                           trả về dữ liệu cho tất cả các key. Mặc định chuỗi rỗng.
 * @param bool   $single     Tùy chọn. Có trả về một giá trị duy nhất hay không.
 *                           Tham số này không có tác dụng nếu `$key` không được chỉ định.
 *                           Mặc định false.
 * @return mixed Mảng các giá trị nếu `$single` là false.
 *               Giá trị của trường meta nếu `$single` là true.
 *               False cho `$comment_id` không hợp lệ (không phải số, bằng 0, hoặc giá trị âm).
 *               Mảng rỗng nếu truyền ID bình luận hợp lệ nhưng không tồn tại và `$single` là false.
 *               Chuỗi rỗng nếu truyền ID bình luận hợp lệ nhưng không tồn tại và `$single` là true.
 *               Lưu ý: Các giá trị chưa serialize được trả về dưới dạng chuỗi:
 *               - giá trị false được trả về dưới dạng chuỗi rỗng ('')
 *               - giá trị true được trả về dưới dạng '1'
 *               - số được trả về dưới dạng chuỗi
 *               Mảng và đối tượng giữ nguyên kiểu dữ liệu gốc.
 */
function get_comment_meta( $comment_id, $key = '', $single = false ) {
	return get_metadata( 'comment', $comment_id, $key, $single );
}

/**
 * Xếp hàng meta bình luận để tải lười (lazy-loading).
 *
 * @since 6.3.0
 *
 * @param array $comment_ids Danh sách ID bình luận.
 */
function wp_lazyload_comment_meta( array $comment_ids ) {
	if ( empty( $comment_ids ) ) {
		return;
	}
	$lazyloader = wp_metadata_lazyloader();
	$lazyloader->queue_objects( 'comment', $comment_ids );
}

/**
 * Cập nhật trường meta bình luận dựa trên ID bình luận.
 *
 * Sử dụng tham số $prev_value để phân biệt giữa các trường meta có cùng
 * key và ID bình luận.
 *
 * Nếu trường meta cho bình luận không tồn tại, nó sẽ được thêm mới.
 *
 * @since 2.9.0
 *
 * @link https://developer.wordpress.org/reference/functions/update_comment_meta/
 *
 * @param int    $comment_id ID bình luận.
 * @param string $meta_key   Key metadata.
 * @param mixed  $meta_value Giá trị metadata. Phải có khả năng serialize nếu không phải kiểu vô hướng.
 * @param mixed  $prev_value Tùy chọn. Giá trị trước đó để kiểm tra trước khi cập nhật.
 *                           Nếu được chỉ định, chỉ cập nhật các mục metadata hiện có với
 *                           giá trị này. Ngược lại, cập nhật tất cả các mục. Mặc định chuỗi rỗng.
 * @return int|bool ID meta nếu key chưa tồn tại, true khi cập nhật thành công,
 *                  false khi thất bại hoặc nếu giá trị truyền vào hàm
 *                  giống với giá trị đã có trong cơ sở dữ liệu.
 */
function update_comment_meta( $comment_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'comment', $comment_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Thiết lập cookie dùng để lưu danh tính người bình luận chưa xác thực. Thường được sử dụng
 * để nhớ lại các bình luận trước đó của người bình luận này vẫn đang chờ kiểm duyệt.
 *
 * @since 3.4.0
 * @since 4.9.6 Thêm tham số `$cookies_consent`.
 *
 * @param WP_Comment $comment         Đối tượng bình luận.
 * @param WP_User    $user            Đối tượng người dùng của tác giả bình luận. Người dùng có thể không tồn tại.
 * @param bool       $cookies_consent Tùy chọn. Sự đồng ý của tác giả bình luận để lưu cookie. Mặc định true.
 */
function wp_set_comment_cookies( $comment, $user, $cookies_consent = true ) {
	// Nếu người dùng đã tồn tại, hoặc người dùng từ chối cookie, không thiết lập cookie.
	if ( $user->exists() ) {
		return;
	}

	if ( false === $cookies_consent ) {
		// Xóa bất kỳ cookie hiện có nào.
		$past = time() - YEAR_IN_SECONDS;
		setcookie( 'comment_author_' . COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'comment_author_email_' . COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'comment_author_url_' . COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN );

		return;
	}

	/**
	 * Lọc thời gian sống của cookie bình luận tính bằng giây.
	 *
	 * @since 2.8.0
	 * @since 6.6.0 Giá trị mặc định $seconds thay đổi từ 30000000 sang YEAR_IN_SECONDS.
	 *
	 * @param int $seconds Thời gian sống cookie bình luận. Mặc định YEAR_IN_SECONDS.
	 */
	$comment_cookie_lifetime = time() + apply_filters( 'comment_cookie_lifetime', YEAR_IN_SECONDS );

	$secure = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );

	setcookie( 'comment_author_' . COOKIEHASH, $comment->comment_author, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	setcookie( 'comment_author_email_' . COOKIEHASH, $comment->comment_author_email, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	setcookie( 'comment_author_url_' . COOKIEHASH, esc_url( $comment->comment_author_url ), $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
}

/**
 * Làm sạch các cookie đã được gửi đến người dùng.
 *
 * Chỉ thực hiện nếu cookie đã được tạo cho người dùng.
 * Chủ yếu được sử dụng sau khi cookie đã được gửi để sử dụng ở nơi khác.
 *
 * @since 2.0.4
 */
function sanitize_comment_cookies() {
	if ( isset( $_COOKIE[ 'comment_author_' . COOKIEHASH ] ) ) {
		/**
		 * Lọc cookie tên tác giả bình luận trước khi được thiết lập.
		 *
		 * Khi hook bộ lọc này được đánh giá trong wp_filter_comment(),
		 * chuỗi tên tác giả bình luận được truyền vào.
		 *
		 * @since 1.5.0
		 *
		 * @param string $author_cookie Cookie tên tác giả bình luận.
		 */
		$comment_author = apply_filters( 'pre_comment_author_name', $_COOKIE[ 'comment_author_' . COOKIEHASH ] );
		$comment_author = wp_unslash( $comment_author );
		$comment_author = esc_attr( $comment_author );

		$_COOKIE[ 'comment_author_' . COOKIEHASH ] = $comment_author;
	}

	if ( isset( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) ) {
		/**
		 * Lọc cookie email tác giả bình luận trước khi được thiết lập.
		 *
		 * Khi hook bộ lọc này được đánh giá trong wp_filter_comment(),
		 * chuỗi email tác giả bình luận được truyền vào.
		 *
		 * @since 1.5.0
		 *
		 * @param string $author_email_cookie Cookie email tác giả bình luận.
		 */
		$comment_author_email = apply_filters( 'pre_comment_author_email', $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] );
		$comment_author_email = wp_unslash( $comment_author_email );
		$comment_author_email = esc_attr( $comment_author_email );

		$_COOKIE[ 'comment_author_email_' . COOKIEHASH ] = $comment_author_email;
	}

	if ( isset( $_COOKIE[ 'comment_author_url_' . COOKIEHASH ] ) ) {
		/**
		 * Lọc cookie URL tác giả bình luận trước khi được thiết lập.
		 *
		 * Khi hook bộ lọc này được đánh giá trong wp_filter_comment(),
		 * chuỗi URL tác giả bình luận được truyền vào.
		 *
		 * @since 1.5.0
		 *
		 * @param string $author_url_cookie Cookie URL tác giả bình luận.
		 */
		$comment_author_url = apply_filters( 'pre_comment_author_url', $_COOKIE[ 'comment_author_url_' . COOKIEHASH ] );
		$comment_author_url = wp_unslash( $comment_author_url );

		$_COOKIE[ 'comment_author_url_' . COOKIEHASH ] = $comment_author_url;
	}
}

/**
 * Xác thực liệu bình luận này có được phép đăng hay không.
 *
 * @since 2.0.0
 * @since 4.7.0 Thêm tham số `$avoid_die`, cho phép hàm
 *              trả về đối tượng WP_Error thay vì dừng thực thi.
 * @since 5.5.0 Tham số `$avoid_die` được đổi tên thành `$wp_error`.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $commentdata Chứa thông tin về bình luận.
 * @param bool  $wp_error    Khi true, bình luận không được phép sẽ khiến hàm
 *                           trả về đối tượng WP_Error, thay vì thực thi wp_die().
 *                           Mặc định false.
 * @return int|string|WP_Error Bình luận được phép trả về trạng thái phê duyệt (0|1|'spam'|'trash').
 *                             Nếu `$wp_error` là true, bình luận không được phép trả về WP_Error.
 */
function wp_allow_comment( $commentdata, $wp_error = false ) {
	global $wpdb;

	/*
	 * Kiểm tra trùng lặp đơn giản.
	 * dự kiến đã có dấu gạch chéo ($comment_post_ID, $comment_author, $comment_author_email, $comment_content)
	 */
	$dupe = $wpdb->prepare(
		"SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_parent = %s AND comment_approved != 'trash' AND ( comment_author = %s ",
		wp_unslash( $commentdata['comment_post_ID'] ),
		wp_unslash( $commentdata['comment_parent'] ),
		wp_unslash( $commentdata['comment_author'] )
	);
	if ( $commentdata['comment_author_email'] ) {
		$dupe .= $wpdb->prepare(
			'AND comment_author_email = %s ',
			wp_unslash( $commentdata['comment_author_email'] )
		);
	}
	$dupe .= $wpdb->prepare(
		') AND comment_content = %s LIMIT 1',
		wp_unslash( $commentdata['comment_content'] )
	);

	$dupe_id = $wpdb->get_var( $dupe );

	/**
	 * Lọc ID (nếu có) của bình luận trùng lặp được tìm thấy khi tạo bình luận mới.
	 *
	 * Trả về giá trị rỗng từ bộ lọc này để cho phép bình luận mà WP coi là trùng lặp.
	 *
	 * @since 4.4.0
	 *
	 * @param int   $dupe_id     ID của bình luận được xác định là trùng lặp.
	 * @param array $commentdata Dữ liệu cho bình luận đang được tạo.
	 */
	$dupe_id = apply_filters( 'duplicate_comment_id', $dupe_id, $commentdata );

	if ( $dupe_id ) {
		/**
		 * Kích hoạt ngay sau khi phát hiện bình luận trùng lặp.
		 *
		 * @since 3.0.0
		 *
		 * @param array $commentdata Dữ liệu bình luận.
		 */
		do_action( 'comment_duplicate_trigger', $commentdata );

		/**
		 * Lọc thông báo lỗi bình luận trùng lặp.
		 *
		 * @since 5.2.0
		 *
		 * @param string $comment_duplicate_message Thông báo lỗi bình luận trùng lặp.
		 */
		$comment_duplicate_message = apply_filters( 'comment_duplicate_message', __( 'Duplicate comment detected; it looks as though you&#8217;ve already said that!' ) );

		if ( $wp_error ) {
			return new WP_Error( 'comment_duplicate', $comment_duplicate_message, 409 );
		} else {
			if ( wp_doing_ajax() ) {
				die( $comment_duplicate_message );
			}

			wp_die( $comment_duplicate_message, 409 );
		}
	}

	/**
	 * Kích hoạt ngay trước khi bình luận được đánh dấu phê duyệt.
	 *
	 * Cho phép kiểm tra spam bình luận hàng loạt (flood).
	 *
	 * @since 2.3.0
	 * @since 4.7.0 Thêm tham số `$avoid_die`.
	 * @since 5.5.0 Tham số `$avoid_die` được đổi tên thành `$wp_error`.
	 *
	 * @param string $comment_author_ip    Địa chỉ IP tác giả bình luận.
	 * @param string $comment_author_email Email tác giả bình luận.
	 * @param string $comment_date_gmt     Ngày GMT bình luận được đăng.
	 * @param bool   $wp_error             Có trả về đối tượng WP_Error thay vì thực thi
	 *                                     wp_die() hoặc die() khi xảy ra flood bình luận hay không.
	 */
	do_action(
		'check_comment_flood',
		$commentdata['comment_author_IP'],
		$commentdata['comment_author_email'],
		$commentdata['comment_date_gmt'],
		$wp_error
	);

	/**
	 * Lọc xem bình luận có phải là một phần của flood bình luận hay không.
	 *
	 * Kiểm tra mặc định là wp_check_comment_flood(). Xem check_comment_flood_db().
	 *
	 * @since 4.7.0
	 * @since 5.5.0 Tham số `$avoid_die` được đổi tên thành `$wp_error`.
	 *
	 * @param bool   $is_flood             Có đang xảy ra flood bình luận không? Mặc định false.
	 * @param string $comment_author_ip    Địa chỉ IP tác giả bình luận.
	 * @param string $comment_author_email Email tác giả bình luận.
	 * @param string $comment_date_gmt     Ngày GMT bình luận được đăng.
	 * @param bool   $wp_error             Có trả về đối tượng WP_Error thay vì thực thi
	 *                                     wp_die() hoặc die() khi xảy ra flood bình luận hay không.
	 */
	$is_flood = apply_filters(
		'wp_is_comment_flood',
		false,
		$commentdata['comment_author_IP'],
		$commentdata['comment_author_email'],
		$commentdata['comment_date_gmt'],
		$wp_error
	);

	if ( $is_flood ) {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/comment-template.php */
		$comment_flood_message = apply_filters( 'comment_flood_message', __( 'You are posting comments too quickly. Slow down.' ) );

		return new WP_Error( 'comment_flood', $comment_flood_message, 429 );
	}

	return wp_check_comment_data( $commentdata );
}

/**
 * Gắn hook kiểm tra flood bình luận dựa trên cơ sở dữ liệu gốc của WP.
 *
 * Hàm bọc này duy trì tương thích ngược với các plugin mong đợi có thể
 * gỡ hook hàm check_comment_flood_db() cũ khỏi
 * 'check_comment_flood' bằng remove_action().
 *
 * @since 2.3.0
 * @since 4.7.0 Chuyển đổi thành hàm bọc add_filter().
 */
function check_comment_flood_db() {
	add_filter( 'wp_is_comment_flood', 'wp_check_comment_flood', 10, 5 );
}

/**
 * Kiểm tra xem có đang xảy ra flood bình luận hay không.
 *
 * Sẽ không chạy nếu người dùng hiện tại có quyền quản lý tùy chọn, để không
 * chặn quản trị viên.
 *
 * @since 4.7.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param bool   $is_flood  Có đang xảy ra flood bình luận không?
 * @param string $ip        Địa chỉ IP tác giả bình luận.
 * @param string $email     Địa chỉ email tác giả bình luận.
 * @param string $date      Chuỗi thời gian MySQL.
 * @param bool   $avoid_die Khi true, bình luận không được phép sẽ khiến hàm
 *                          trả về mà không thực thi wp_die() hoặc die(). Mặc định false.
 * @return bool Có đang xảy ra flood bình luận hay không.
 */
function wp_check_comment_flood( $is_flood, $ip, $email, $date, $avoid_die = false ) {
	global $wpdb;

	// Một callback khác đã khai báo flood. Tin tưởng nó.
	if ( true === $is_flood ) {
		return $is_flood;
	}

	// Không giới hạn tốc độ cho quản trị viên hoặc người kiểm duyệt.
	if ( current_user_can( 'manage_options' ) || current_user_can( 'moderate_comments' ) ) {
		return false;
	}

	$hour_ago = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

	if ( is_user_logged_in() ) {
		$user         = get_current_user_id();
		$check_column = '`user_id`';
	} else {
		$user         = $ip;
		$check_column = '`comment_author_IP`';
	}

	$sql = $wpdb->prepare(
		"SELECT `comment_date_gmt` FROM `$wpdb->comments` WHERE `comment_date_gmt` >= %s AND ( $check_column = %s OR `comment_author_email` = %s ) ORDER BY `comment_date_gmt` DESC LIMIT 1",
		$hour_ago,
		$user,
		$email
	);

	$lasttime = $wpdb->get_var( $sql );

	if ( $lasttime ) {
		$time_lastcomment = mysql2date( 'U', $lasttime, false );
		$time_newcomment  = mysql2date( 'U', $date, false );

		/**
		 * Lọc trạng thái flood bình luận.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $bool             Có đang xảy ra flood bình luận không. Mặc định false.
		 * @param int  $time_lastcomment Dấu thời gian khi bình luận cuối cùng được đăng.
		 * @param int  $time_newcomment  Dấu thời gian khi bình luận mới được đăng.
		 */
		$flood_die = apply_filters( 'comment_flood_filter', false, $time_lastcomment, $time_newcomment );

		if ( $flood_die ) {
			/**
			 * Kích hoạt trước khi thông báo flood bình luận được kích hoạt.
			 *
			 * @since 1.5.0
			 *
			 * @param int $time_lastcomment Dấu thời gian khi bình luận cuối cùng được đăng.
			 * @param int $time_newcomment  Dấu thời gian khi bình luận mới được đăng.
			 */
			do_action( 'comment_flood_trigger', $time_lastcomment, $time_newcomment );

			if ( $avoid_die ) {
				return true;
			} else {
				/**
				 * Lọc thông báo lỗi flood bình luận.
				 *
				 * @since 5.2.0
				 *
				 * @param string $comment_flood_message Thông báo lỗi flood bình luận.
				 */
				$comment_flood_message = apply_filters( 'comment_flood_message', __( 'You are posting comments too quickly. Slow down.' ) );

				if ( wp_doing_ajax() ) {
					die( $comment_flood_message );
				}

				wp_die( $comment_flood_message, 429 );
			}
		}
	}

	return false;
}

/**
 * Phân tách mảng bình luận thành mảng được đánh chỉ mục theo comment_type.
 *
 * @since 2.7.0
 *
 * @param WP_Comment[] $comments Mảng bình luận
 * @return WP_Comment[] Mảng bình luận được đánh chỉ mục theo comment_type.
 */
function separate_comments( &$comments ) {
	$comments_by_type = array(
		'comment'   => array(),
		'trackback' => array(),
		'pingback'  => array(),
		'pings'     => array(),
	);

	$count = count( $comments );

	for ( $i = 0; $i < $count; $i++ ) {
		$type = $comments[ $i ]->comment_type;

		if ( empty( $type ) ) {
			$type = 'comment';
		}

		$comments_by_type[ $type ][] = &$comments[ $i ];

		if ( 'trackback' === $type || 'pingback' === $type ) {
			$comments_by_type['pings'][] = &$comments[ $i ];
		}
	}

	return $comments_by_type;
}

/**
 * Tính tổng số trang bình luận.
 *
 * @since 2.7.0
 *
 * @uses Walker_Comment
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 *
 * @param WP_Comment[] $comments Tùy chọn. Mảng đối tượng WP_Comment. Mặc định là `$wp_query->comments`.
 * @param int          $per_page Tùy chọn. Số bình luận mỗi trang. Mặc định là giá trị của biến truy vấn
 *                               `comments_per_page`, tùy chọn cùng tên, hoặc 1 (theo thứ tự đó).
 * @param bool         $threaded Tùy chọn. Kiểm soát bình luận phẳng hoặc theo chuỗi. Mặc định là giá trị
 *                               của tùy chọn `thread_comments`.
 * @return int Số trang bình luận.
 */
function get_comment_pages_count( $comments = null, $per_page = null, $threaded = null ) {
	global $wp_query;

	if ( null === $comments && null === $per_page && null === $threaded && ! empty( $wp_query->max_num_comment_pages ) ) {
		return $wp_query->max_num_comment_pages;
	}

	if ( ( ! $comments || ! is_array( $comments ) ) && ! empty( $wp_query->comments ) ) {
		$comments = $wp_query->comments;
	}

	if ( empty( $comments ) ) {
		return 0;
	}

	if ( ! get_option( 'page_comments' ) ) {
		return 1;
	}

	if ( ! isset( $per_page ) ) {
		$per_page = (int) get_query_var( 'comments_per_page' );
	}
	if ( 0 === $per_page ) {
		$per_page = (int) get_option( 'comments_per_page' );
	}
	if ( 0 === $per_page ) {
		return 1;
	}

	if ( ! isset( $threaded ) ) {
		$threaded = get_option( 'thread_comments' );
	}

	if ( $threaded ) {
		$walker = new Walker_Comment();
		$count  = ceil( $walker->get_number_of_root_elements( $comments ) / $per_page );
	} else {
		$count = ceil( count( $comments ) / $per_page );
	}

	return (int) $count;
}

/**
 * Tính số trang mà một bình luận sẽ xuất hiện trong phân trang bình luận.
 *
 * @since 2.7.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int   $comment_id ID bình luận.
 * @param array $args {
 *     Mảng các tham số tùy chọn.
 *
 *     @type string     $type      Giới hạn bình luận phân trang cho những bình luận khớp với loại nhất định.
 *                                 Chấp nhận 'comment', 'trackback', 'pingback', 'pings'
 *                                 (trackbacks và pingbacks), hoặc 'all'. Mặc định 'all'.
 *     @type int        $per_page  Số lượng mỗi trang khi tính phân trang.
 *                                 Mặc định là giá trị của tùy chọn 'comments_per_page'.
 *     @type int|string $max_depth Nếu lớn hơn 1, trang bình luận sẽ được xác định
 *                                 cho bình luận cha cấp cao nhất `$comment_id`.
 *                                 Mặc định là giá trị của tùy chọn 'thread_comments_depth'.
 * }
 * @return int|null Số trang bình luận hoặc null khi lỗi.
 */
function get_page_of_comment( $comment_id, $args = array() ) {
	global $wpdb;

	$page = null;

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}

	$defaults      = array(
		'type'      => 'all',
		'page'      => '',
		'per_page'  => '',
		'max_depth' => '',
	);
	$args          = wp_parse_args( $args, $defaults );
	$original_args = $args;

	// Thứ tự ưu tiên: 1. `$args['per_page']`, 2. biến truy vấn 'comments_per_page', 3. tùy chọn 'comments_per_page'.
	if ( get_option( 'page_comments' ) ) {
		if ( '' === $args['per_page'] ) {
			$args['per_page'] = get_query_var( 'comments_per_page' );
		}

		if ( '' === $args['per_page'] ) {
			$args['per_page'] = get_option( 'comments_per_page' );
		}
	}

	if ( empty( $args['per_page'] ) ) {
		$args['per_page'] = 0;
		$args['page']     = 0;
	}

	if ( $args['per_page'] < 1 ) {
		$page = 1;
	}

	if ( null === $page ) {
		if ( '' === $args['max_depth'] ) {
			if ( get_option( 'thread_comments' ) ) {
				$args['max_depth'] = get_option( 'thread_comments_depth' );
			} else {
				$args['max_depth'] = -1;
			}
		}

		// Tìm bình luận cha cấp cao nhất của bình luận này nếu chuỗi bình luận được bật.
		if ( $args['max_depth'] > 1 && '0' !== $comment->comment_parent ) {
			return get_page_of_comment( $comment->comment_parent, $args );
		}

		$comment_args = array(
			'type'       => $args['type'],
			'post_id'    => $comment->comment_post_ID,
			'fields'     => 'ids',
			'count'      => true,
			'status'     => 'approve',
			'orderby'    => 'none',
			'parent'     => 0,
			'date_query' => array(
				array(
					'column' => "$wpdb->comments.comment_date_gmt",
					'before' => $comment->comment_date_gmt,
				),
			),
		);

		if ( is_user_logged_in() ) {
			$comment_args['include_unapproved'] = array( get_current_user_id() );
		} else {
			$unapproved_email = wp_get_unapproved_comment_author_email();

			if ( $unapproved_email ) {
				$comment_args['include_unapproved'] = array( $unapproved_email );
			}
		}

		/**
		 * Lọc các tham số dùng để truy vấn bình luận trong get_page_of_comment().
		 *
		 * @since 5.5.0
		 *
		 * @see WP_Comment_Query::__construct()
		 *
		 * @param array $comment_args {
		 *     Mảng các tham số WP_Comment_Query.
		 *
		 *     @type string $type               Giới hạn bình luận phân trang cho loại nhất định.
		 *                                      Chấp nhận 'comment', 'trackback', 'pingback', 'pings'
		 *                                      (trackbacks và pingbacks), hoặc 'all'. Mặc định 'all'.
		 *     @type int    $post_id            ID của bài viết.
		 *     @type string $fields             Các trường bình luận cần trả về.
		 *     @type bool   $count              Trả về số lượng bình luận (true) hay mảng
		 *                                      các đối tượng bình luận (false).
		 *     @type string $status             Trạng thái bình luận.
		 *     @type int    $parent             ID cha của bình luận để lấy các bình luận con.
		 *     @type array  $date_query         Mệnh đề truy vấn ngày để giới hạn bình luận. Xem WP_Date_Query.
		 *     @type array  $include_unapproved Mảng ID hoặc địa chỉ email có bình luận chưa phê duyệt
		 *                                      sẽ được bao gồm trong bình luận phân trang.
		 * }
		 */
		$comment_args = apply_filters( 'get_page_of_comment_query_args', $comment_args );

		$comment_query       = new WP_Comment_Query();
		$older_comment_count = $comment_query->query( $comment_args );

		// Không có bình luận cũ hơn? Vậy thì đó là trang #1.
		if ( 0 === $older_comment_count ) {
			$page = 1;

			// Chia số bình luận cũ hơn bình luận này cho số bình luận mỗi trang để lấy số trang của bình luận này.
		} else {
			$page = (int) ceil( ( $older_comment_count + 1 ) / $args['per_page'] );
		}
	}

	/**
	 * Lọc trang đã tính toán mà bình luận xuất hiện.
	 *
	 * @since 4.4.0
	 * @since 4.7.0 Giới thiệu tham số `$comment_id`.
	 *
	 * @param int   $page          Trang bình luận.
	 * @param array $args {
	 *     Các tham số dùng để tính phân trang. Bao gồm các tham số được tự động phát hiện bởi hàm,
	 *     dựa trên biến truy vấn, cài đặt hệ thống, v.v. Để xem các tham số gốc truyền vào hàm,
	 *     xem `$original_args`.
	 *
	 *     @type string $type      Loại bình luận cần đếm.
	 *     @type int    $page      Trang hiện tại đã tính.
	 *     @type int    $per_page  Số bình luận mỗi trang đã tính.
	 *     @type int    $max_depth Độ sâu tối đa cho chuỗi bình luận được phép.
	 * }
	 * @param array $original_args {
	 *     Mảng các tham số truyền vào hàm. Một số hoặc tất cả có thể chưa được thiết lập.
	 *
	 *     @type string $type      Loại bình luận cần đếm.
	 *     @type int    $page      Trang bình luận hiện tại.
	 *     @type int    $per_page  Số bình luận mỗi trang.
	 *     @type int    $max_depth Độ sâu tối đa cho chuỗi bình luận được phép.
	 * }
	 * @param int $comment_id ID của bình luận.
	 */
	return apply_filters( 'get_page_of_comment', (int) $page, $args, $original_args, $comment_id );
}

/**
 * Lấy độ dài ký tự tối đa cho các trường biểu mẫu bình luận.
 *
 * @since 4.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @return int[] Mảng độ dài tối đa được đánh chỉ mục theo tên trường.
 */
function wp_get_comment_fields_max_lengths() {
	global $wpdb;

	$lengths = array(
		'comment_author'       => 245,
		'comment_author_email' => 100,
		'comment_author_url'   => 200,
		'comment_content'      => 65525,
	);

	if ( $wpdb->is_mysql ) {
		foreach ( $lengths as $column => $length ) {
			$col_length = $wpdb->get_col_length( $wpdb->comments, $column );
			$max_length = 0;

			// Không có ý nghĩa nếu không lấy được độ dài cột DB.
			if ( is_wp_error( $col_length ) ) {
				break;
			}

			if ( ! is_array( $col_length ) && (int) $col_length > 0 ) {
				$max_length = (int) $col_length;
			} elseif ( is_array( $col_length ) && isset( $col_length['length'] ) && (int) $col_length['length'] > 0 ) {
				$max_length = (int) $col_length['length'];

				if ( ! empty( $col_length['type'] ) && 'byte' === $col_length['type'] ) {
					$max_length = $max_length - 10;
				}
			}

			if ( $max_length > 0 ) {
				$lengths[ $column ] = $max_length;
			}
		}
	}

	/**
	 * Lọc độ dài cho các trường biểu mẫu bình luận.
	 *
	 * @since 4.5.0
	 *
	 * @param int[] $lengths Mảng độ dài tối đa được đánh chỉ mục theo tên trường.
	 */
	return apply_filters( 'wp_get_comment_fields_max_lengths', $lengths );
}

/**
 * So sánh độ dài dữ liệu bình luận với giới hạn ký tự tối đa.
 *
 * @since 4.7.0
 *
 * @param array $comment_data Mảng các tham số để chèn bình luận.
 * @return WP_Error|true WP_Error khi trường bình luận vượt quá giới hạn,
 *                       ngược lại true.
 */
function wp_check_comment_data_max_lengths( $comment_data ) {
	$max_lengths = wp_get_comment_fields_max_lengths();

	if ( isset( $comment_data['comment_author'] ) && mb_strlen( $comment_data['comment_author'], '8bit' ) > $max_lengths['comment_author'] ) {
		return new WP_Error( 'comment_author_column_length', __( '<strong>Error:</strong> Your name is too long.' ), 200 );
	}

	if ( isset( $comment_data['comment_author_email'] ) && strlen( $comment_data['comment_author_email'] ) > $max_lengths['comment_author_email'] ) {
		return new WP_Error( 'comment_author_email_column_length', __( '<strong>Error:</strong> Your email address is too long.' ), 200 );
	}

	if ( isset( $comment_data['comment_author_url'] ) && strlen( $comment_data['comment_author_url'] ) > $max_lengths['comment_author_url'] ) {
		return new WP_Error( 'comment_author_url_column_length', __( '<strong>Error:</strong> Your URL is too long.' ), 200 );
	}

	if ( isset( $comment_data['comment_content'] ) && mb_strlen( $comment_data['comment_content'], '8bit' ) > $max_lengths['comment_content'] ) {
		return new WP_Error( 'comment_content_column_length', __( '<strong>Error:</strong> Your comment is too long.' ), 200 );
	}

	return true;
}

/**
 * Kiểm tra xem dữ liệu bình luận có vượt qua các kiểm tra nội bộ hay có nội dung không được phép.
 *
 * @since 6.7.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $comment_data Mảng các tham số để chèn bình luận.
 * @return int|string|WP_Error Trạng thái phê duyệt khi thành công (0|1|'spam'|'trash'),
 *                             WP_Error trong trường hợp khác.
 */
function wp_check_comment_data( $comment_data ) {
	global $wpdb;

	if ( ! empty( $comment_data['user_id'] ) ) {
		$user        = get_userdata( $comment_data['user_id'] );
		$post_author = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_author FROM $wpdb->posts WHERE ID = %d LIMIT 1",
				$comment_data['comment_post_ID']
			)
		);
	}

	if ( isset( $user ) && ( $comment_data['user_id'] === $post_author || $user->has_cap( 'moderate_comments' ) ) ) {
		// Tác giả và quản trị viên được ưu tiên.
		$approved = 1;
	} else {
		// Bình luận của tất cả người khác sẽ được kiểm tra.
		if ( check_comment(
			$comment_data['comment_author'],
			$comment_data['comment_author_email'],
			$comment_data['comment_author_url'],
			$comment_data['comment_content'],
			$comment_data['comment_author_IP'],
			$comment_data['comment_agent'],
			$comment_data['comment_type']
		) ) {
			$approved = 1;
		} else {
			$approved = 0;
		}

		if ( wp_check_comment_disallowed_list(
			$comment_data['comment_author'],
			$comment_data['comment_author_email'],
			$comment_data['comment_author_url'],
			$comment_data['comment_content'],
			$comment_data['comment_author_IP'],
			$comment_data['comment_agent']
		) ) {
			$approved = EMPTY_TRASH_DAYS ? 'trash' : 'spam';
		}
	}

	/**
	 * Lọc trạng thái phê duyệt bình luận trước khi được thiết lập.
	 *
	 * @since 2.1.0
	 * @since 4.9.0 Trả về giá trị WP_Error từ bộ lọc sẽ bỏ qua việc chèn bình luận
	 *              và cho phép bỏ qua xử lý tiếp theo.
	 *
	 * @param int|string|WP_Error $approved    Trạng thái phê duyệt. Chấp nhận 1, 0, 'spam', 'trash',
	 *                                         hoặc WP_Error.
	 * @param array               $commentdata Dữ liệu bình luận.
	 */
	return apply_filters( 'pre_comment_approved', $approved, $comment_data );
}

/**
 * Kiểm tra xem bình luận có chứa ký tự hoặc từ không được phép hay không.
 *
 * @since 5.5.0
 *
 * @param string $author     Tác giả bình luận.
 * @param string $email      Email của bình luận.
 * @param string $url        URL được sử dụng trong bình luận.
 * @param string $comment    Nội dung bình luận.
 * @param string $user_ip    Địa chỉ IP tác giả bình luận.
 * @param string $user_agent User-Agent trình duyệt của tác giả.
 * @return bool True nếu bình luận chứa nội dung không được phép, false trong trường hợp ngược lại.
 */
function wp_check_comment_disallowed_list( $author, $email, $url, $comment, $user_ip, $user_agent ) {
	/**
	 * Kích hoạt trước khi bình luận được kiểm tra ký tự hoặc từ không được phép.
	 *
	 * @since 1.5.0
	 * @deprecated 5.5.0 Sử dụng {@see 'wp_check_comment_disallowed_list'} thay thế.
	 *
	 * @param string $author     Tác giả bình luận.
	 * @param string $email      Email tác giả bình luận.
	 * @param string $url        URL tác giả bình luận.
	 * @param string $comment    Nội dung bình luận.
	 * @param string $user_ip    Địa chỉ IP tác giả bình luận.
	 * @param string $user_agent User-Agent trình duyệt tác giả bình luận.
	 */
	do_action_deprecated(
		'wp_blacklist_check',
		array( $author, $email, $url, $comment, $user_ip, $user_agent ),
		'5.5.0',
		'wp_check_comment_disallowed_list',
		__( 'Please consider writing more inclusive code.' )
	);

	/**
	 * Kích hoạt trước khi bình luận được kiểm tra ký tự hoặc từ không được phép.
	 *
	 * @since 5.5.0
	 *
	 * @param string $author     Tác giả bình luận.
	 * @param string $email      Email tác giả bình luận.
	 * @param string $url        URL tác giả bình luận.
	 * @param string $comment    Nội dung bình luận.
	 * @param string $user_ip    Địa chỉ IP tác giả bình luận.
	 * @param string $user_agent User-Agent trình duyệt tác giả bình luận.
	 */
	do_action( 'wp_check_comment_disallowed_list', $author, $email, $url, $comment, $user_ip, $user_agent );

	$mod_keys = trim( get_option( 'disallowed_keys' ) );
	if ( '' === $mod_keys ) {
		return false; // Nếu các key kiểm duyệt trống.
	}

	// Đảm bảo thẻ HTML không được sử dụng để vượt qua danh sách ký tự và từ không được phép.
	$comment_without_html = wp_strip_all_tags( $comment );

	$words = explode( "\n", $mod_keys );

	foreach ( (array) $words as $word ) {
		$word = trim( $word );

		// Bỏ qua các dòng trống.
		if ( empty( $word ) ) {
			continue; }

		// Thực hiện một số xử lý escape để ký tự '#' trong các từ spam không gây lỗi:
		$word = preg_quote( $word, '#' );

		$pattern = "#$word#iu";
		if ( preg_match( $pattern, $author )
			|| preg_match( $pattern, $email )
			|| preg_match( $pattern, $url )
			|| preg_match( $pattern, $comment )
			|| preg_match( $pattern, $comment_without_html )
			|| preg_match( $pattern, $user_ip )
			|| preg_match( $pattern, $user_agent )
		) {
			return true;
		}
	}
	return false;
}

/**
 * Lấy tổng số lượng bình luận cho toàn bộ trang web hoặc một bài viết.
 *
 * Thống kê bình luận được lưu cache và sau đó được lấy ra, nếu chúng đã tồn tại trong
 * cache.
 *
 * @see get_comment_count() Hàm xử lý việc lấy số lượng bình luận trực tiếp.
 *
 * @since 2.5.0
 *
 * @param int $post_id Tùy chọn. Giới hạn số lượng bình luận cho bài viết nhất định. Mặc định 0, nghĩa là
 *                     sẽ lấy số lượng bình luận cho toàn bộ trang web.
 * @return stdClass {
 *     Số lượng bình luận được đánh chỉ mục theo trạng thái.
 *
 *     @type int $approved       Số lượng bình luận đã phê duyệt.
 *     @type int $moderated      Số lượng bình luận đang chờ kiểm duyệt (tức là đang chờ xử lý).
 *     @type int $spam           Số lượng bình luận spam.
 *     @type int $trash          Số lượng bình luận đã bị xóa.
 *     @type int $post-trashed   Số lượng bình luận cho các bài viết đã bị xóa.
 *     @type int $total_comments Tổng số bình luận chưa bị xóa, bao gồm spam.
 *     @type int $all            Tổng số bình luận đang chờ hoặc đã phê duyệt.
 * }
 */
function wp_count_comments( $post_id = 0 ) {
	$post_id = (int) $post_id;

	/**
	 * Lọc số lượng bình luận cho một bài viết nhất định hoặc toàn bộ trang web.
	 *
	 * @since 2.7.0
	 *
	 * @param array|stdClass $count   Mảng rỗng hoặc đối tượng chứa số lượng bình luận.
	 * @param int            $post_id ID bài viết. Có thể là 0 để đại diện cho toàn bộ trang web.
	 */
	$filtered = apply_filters( 'wp_count_comments', array(), $post_id );
	if ( ! empty( $filtered ) ) {
		return $filtered;
	}

	$count = wp_cache_get( "comments-{$post_id}", 'counts' );
	if ( false !== $count ) {
		return $count;
	}

	$stats              = get_comment_count( $post_id );
	$stats['moderated'] = $stats['awaiting_moderation'];
	unset( $stats['awaiting_moderation'] );

	$stats_object = (object) $stats;
	wp_cache_set( "comments-{$post_id}", $stats_object, 'counts' );

	return $stats_object;
}

/**
 * Chuyển vào thùng rác hoặc xóa một bình luận.
 *
 * Bình luận được chuyển vào Thùng rác thay vì xóa vĩnh viễn trừ khi Thùng rác
 * bị vô hiệu hóa, mục đã ở trong Thùng rác, hoặc $force_delete là true.
 *
 * Số lượng bình luận bài viết sẽ được cập nhật nếu bình luận đã được phê duyệt và có
 * ID bài viết.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Comment $comment_id   ID bình luận hoặc đối tượng WP_Comment.
 * @param bool           $force_delete Có bỏ qua Thùng rác và buộc xóa hay không. Mặc định false.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_delete_comment( $comment_id, $force_delete = false ) {
	global $wpdb;

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}

	if ( ! $force_delete && EMPTY_TRASH_DAYS && ! in_array( wp_get_comment_status( $comment ), array( 'trash', 'spam' ), true ) ) {
		return wp_trash_comment( $comment_id );
	}

	/**
	 * Kích hoạt ngay trước khi bình luận bị xóa khỏi cơ sở dữ liệu.
	 *
	 * @since 1.2.0
	 * @since 4.9.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Bình luận sẽ bị xóa.
	 */
	do_action( 'delete_comment', $comment->comment_ID, $comment );

	// Di chuyển bình luận con lên một cấp.
	$children = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_parent = %d", $comment->comment_ID ) );
	if ( ! empty( $children ) ) {
		$wpdb->update( $wpdb->comments, array( 'comment_parent' => $comment->comment_parent ), array( 'comment_parent' => $comment->comment_ID ) );
		clean_comment_cache( $children );
	}

	// Xóa metadata.
	$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->commentmeta WHERE comment_id = %d", $comment->comment_ID ) );
	foreach ( $meta_ids as $mid ) {
		delete_metadata_by_mid( 'comment', $mid );
	}

	if ( ! $wpdb->delete( $wpdb->comments, array( 'comment_ID' => $comment->comment_ID ) ) ) {
		return false;
	}

	/**
	 * Kích hoạt ngay sau khi bình luận bị xóa khỏi cơ sở dữ liệu.
	 *
	 * @since 2.9.0
	 * @since 4.9.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Bình luận đã bị xóa.
	 */
	do_action( 'deleted_comment', $comment->comment_ID, $comment );

	$post_id = $comment->comment_post_ID;
	if ( $post_id && '1' === $comment->comment_approved ) {
		wp_update_comment_count( $post_id );
	}

	clean_comment_cache( $comment->comment_ID );

	/** Hành động này được ghi tài liệu trong wp-includes/comment.php */
	do_action( 'wp_set_comment_status', $comment->comment_ID, 'delete' );

	wp_transition_comment_status( 'delete', $comment->comment_approved, $comment );

	return true;
}

/**
 * Chuyển bình luận vào Thùng rác.
 *
 * Nếu Thùng rác bị vô hiệu hóa, bình luận sẽ bị xóa vĩnh viễn.
 *
 * @since 2.9.0
 *
 * @param int|WP_Comment $comment_id ID bình luận hoặc đối tượng WP_Comment.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_trash_comment( $comment_id ) {
	if ( ! EMPTY_TRASH_DAYS ) {
		return wp_delete_comment( $comment_id, true );
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}

	/**
	 * Kích hoạt ngay trước khi bình luận được chuyển vào Thùng rác.
	 *
	 * @since 2.9.0
	 * @since 4.9.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Bình luận sẽ được chuyển vào thùng rác.
	 */
	do_action( 'trash_comment', $comment->comment_ID, $comment );

	if ( wp_set_comment_status( $comment, 'trash' ) ) {
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_status' );
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_time' );
		add_comment_meta( $comment->comment_ID, '_wp_trash_meta_status', $comment->comment_approved );
		add_comment_meta( $comment->comment_ID, '_wp_trash_meta_time', time() );

		/**
		 * Kích hoạt ngay sau khi bình luận được chuyển vào Thùng rác.
		 *
		 * @since 2.9.0
		 * @since 4.9.0 Thêm tham số `$comment`.
		 *
		 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
		 * @param WP_Comment $comment    Bình luận đã chuyển vào thùng rác.
		 */
		do_action( 'trashed_comment', $comment->comment_ID, $comment );

		return true;
	}

	return false;
}

/**
 * Khôi phục bình luận từ Thùng rác.
 *
 * @since 2.9.0
 *
 * @param int|WP_Comment $comment_id ID bình luận hoặc đối tượng WP_Comment.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_untrash_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}

	/**
	 * Kích hoạt ngay trước khi bình luận được khôi phục từ Thùng rác.
	 *
	 * @since 2.9.0
	 * @since 4.9.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Bình luận sẽ được khôi phục từ thùng rác.
	 */
	do_action( 'untrash_comment', $comment->comment_ID, $comment );

	$status = (string) get_comment_meta( $comment->comment_ID, '_wp_trash_meta_status', true );
	if ( empty( $status ) ) {
		$status = '0';
	}

	if ( wp_set_comment_status( $comment, $status ) ) {
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_time' );
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_status' );

		/**
		 * Kích hoạt ngay sau khi bình luận được khôi phục từ Thùng rác.
		 *
		 * @since 2.9.0
		 * @since 4.9.0 Thêm tham số `$comment`.
		 *
		 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
		 * @param WP_Comment $comment    Bình luận đã được khôi phục từ thùng rác.
		 */
		do_action( 'untrashed_comment', $comment->comment_ID, $comment );

		return true;
	}

	return false;
}

/**
 * Đánh dấu bình luận là Spam.
 *
 * @since 2.9.0
 *
 * @param int|WP_Comment $comment_id ID bình luận hoặc đối tượng WP_Comment.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_spam_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}

	/**
	 * Kích hoạt ngay trước khi bình luận được đánh dấu là Spam.
	 *
	 * @since 2.9.0
	 * @since 4.9.0 Thêm tham số `$comment`.
	 *
	 * @param int        $comment_id ID bình luận.
	 * @param WP_Comment $comment    Bình luận sẽ được đánh dấu là spam.
	 */
	do_action( 'spam_comment', $comment->comment_ID, $comment );

	if ( wp_set_comment_status( $comment, 'spam' ) ) {
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_status' );
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_time' );
		add_comment_meta( $comment->comment_ID, '_wp_trash_meta_status', $comment->comment_approved );
		add_comment_meta( $comment->comment_ID, '_wp_trash_meta_time', time() );

		/**
		 * Kích hoạt ngay sau khi bình luận được đánh dấu là Spam.
		 *
		 * @since 2.9.0
		 * @since 4.9.0 Thêm tham số `$comment`.
		 *
		 * @param int        $comment_id ID bình luận.
		 * @param WP_Comment $comment    Bình luận đã được đánh dấu là spam.
		 */
		do_action( 'spammed_comment', $comment->comment_ID, $comment );

		return true;
	}

	return false;
}

/**
 * Bỏ đánh dấu spam cho bình luận.
 *
 * @since 2.9.0
 *
 * @param int|WP_Comment $comment_id ID bình luận hoặc đối tượng WP_Comment.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_unspam_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}

	/**
	 * Kích hoạt ngay trước khi bình luận được bỏ đánh dấu Spam.
	 *
	 * @since 2.9.0
	 * @since 4.9.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Bình luận sẽ được bỏ đánh dấu spam.
	 */
	do_action( 'unspam_comment', $comment->comment_ID, $comment );

	$status = (string) get_comment_meta( $comment->comment_ID, '_wp_trash_meta_status', true );
	if ( empty( $status ) ) {
		$status = '0';
	}

	if ( wp_set_comment_status( $comment, $status ) ) {
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_status' );
		delete_comment_meta( $comment->comment_ID, '_wp_trash_meta_time' );

		/**
		 * Kích hoạt ngay sau khi bình luận được bỏ đánh dấu Spam.
		 *
		 * @since 2.9.0
		 * @since 4.9.0 Thêm tham số `$comment`.
		 *
		 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
		 * @param WP_Comment $comment    Bình luận đã được bỏ đánh dấu spam.
		 */
		do_action( 'unspammed_comment', $comment->comment_ID, $comment );

		return true;
	}

	return false;
}

/**
 * Lấy trạng thái của bình luận theo ID bình luận.
 *
 * @since 1.0.0
 *
 * @param int|WP_Comment $comment_id ID bình luận hoặc đối tượng WP_Comment.
 * @return string|false Trạng thái có thể là 'trash', 'approved', 'unapproved', 'spam'. False khi thất bại.
 */
function wp_get_comment_status( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return false;
	}

	$approved = $comment->comment_approved;

	if ( null === $approved ) {
		return false;
	} elseif ( '1' === $approved ) {
		return 'approved';
	} elseif ( '0' === $approved ) {
		return 'unapproved';
	} elseif ( 'spam' === $approved ) {
		return 'spam';
	} elseif ( 'trash' === $approved ) {
		return 'trash';
	} else {
		return false;
	}
}

/**
 * Gọi các hook khi xảy ra chuyển đổi trạng thái bình luận.
 *
 * Gọi các hook cho chuyển đổi trạng thái bình luận. Nếu trạng thái bình luận mới không giống
 * với trạng thái bình luận trước đó, thì hai hook sẽ được chạy, đầu tiên là
 * {@see 'transition_comment_status'} với trạng thái mới, trạng thái cũ, và dữ liệu bình luận.
 * Hành động tiếp theo được gọi là {@see 'comment_$old_status_to_$new_status'}. Nó có
 * dữ liệu bình luận.
 *
 * Hành động cuối cùng sẽ chạy bất kể trạng thái bình luận có giống nhau hay không.
 * Hành động có tên {@see 'comment_$new_status_$comment->comment_type'}.
 *
 * @since 2.7.0
 *
 * @param string     $new_status Trạng thái bình luận mới.
 * @param string     $old_status Trạng thái bình luận trước đó.
 * @param WP_Comment $comment    Đối tượng bình luận.
 */
function wp_transition_comment_status( $new_status, $old_status, $comment ) {
	/*
	 * Chuyển đổi trạng thái thô sang định dạng dễ đọc cho các hook.
	 * Đây không phải là danh sách đầy đủ các trạng thái bình luận, chỉ là những trạng thái
	 * cần được đổi tên.
	 */
	$comment_statuses = array(
		0         => 'unapproved',
		'hold'    => 'unapproved', // wp_set_comment_status() sử dụng "hold".
		1         => 'approved',
		'approve' => 'approved',   // wp_set_comment_status() sử dụng "approve".
	);
	if ( isset( $comment_statuses[ $new_status ] ) ) {
		$new_status = $comment_statuses[ $new_status ];
	}
	if ( isset( $comment_statuses[ $old_status ] ) ) {
		$old_status = $comment_statuses[ $old_status ];
	}

	// Gọi các hook.
	if ( $new_status !== $old_status ) {
		/**
		 * Kích hoạt khi trạng thái bình luận đang chuyển đổi.
		 *
		 * @since 2.7.0
		 *
		 * @param string     $new_status Trạng thái bình luận mới.
		 * @param string     $old_status Trạng thái bình luận cũ.
		 * @param WP_Comment $comment    Đối tượng bình luận.
		 */
		do_action( 'transition_comment_status', $new_status, $old_status, $comment );

		/**
		 * Kích hoạt khi trạng thái bình luận chuyển đổi từ một trạng thái cụ thể sang trạng thái khác.
		 *
		 * Các phần động của tên hook, `$old_status` và `$new_status`,
		 * tham chiếu đến trạng thái bình luận cũ và mới tương ứng.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `comment_unapproved_to_approved`
		 *  - `comment_spam_to_approved`
		 *  - `comment_approved_to_unapproved`
		 *  - `comment_spam_to_unapproved`
		 *  - `comment_unapproved_to_spam`
		 *  - `comment_approved_to_spam`
		 *
		 * @since 2.7.0
		 *
		 * @param WP_Comment $comment Đối tượng bình luận.
		 */
		do_action( "comment_{$old_status}_to_{$new_status}", $comment );
	}
	/**
	 * Kích hoạt khi trạng thái của một loại bình luận cụ thể đang chuyển đổi.
	 *
	 * Các phần động của tên hook, `$new_status` và `$comment->comment_type`,
	 * tham chiếu đến trạng thái bình luận mới và loại bình luận tương ứng.
	 *
	 * Các loại bình luận thông thường bao gồm 'comment', 'pingback', hoặc 'trackback'.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `comment_approved_comment`
	 *  - `comment_approved_pingback`
	 *  - `comment_approved_trackback`
	 *  - `comment_unapproved_comment`
	 *  - `comment_unapproved_pingback`
	 *  - `comment_unapproved_trackback`
	 *  - `comment_spam_comment`
	 *  - `comment_spam_pingback`
	 *  - `comment_spam_trackback`
	 *
	 * @since 2.7.0
	 *
	 * @param string     $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Đối tượng bình luận.
	 */
	do_action( "comment_{$new_status}_{$comment->comment_type}", $comment->comment_ID, $comment );
}

/**
 * Xóa giá trị cache lastcommentmodified khi trạng thái bình luận thay đổi.
 *
 * Xóa key cache lastcommentmodified khi bình luận vào hoặc rời khỏi
 * trạng thái 'approved'.
 *
 * @since 4.7.0
 * @access private
 *
 * @param string $new_status Trạng thái bình luận mới.
 * @param string $old_status Trạng thái bình luận cũ.
 */
function _clear_modified_cache_on_transition_comment_status( $new_status, $old_status ) {
	if ( 'approved' === $new_status || 'approved' === $old_status ) {
		$data = array();
		foreach ( array( 'server', 'gmt', 'blog' ) as $timezone ) {
			$data[] = "lastcommentmodified:$timezone";
		}
		wp_cache_delete_multiple( $data, 'timeinfo' );
	}
}

/**
 * Lấy tên, email và URL của người bình luận hiện tại.
 *
 * Mong đợi nội dung cookie đã được làm sạch. Người sử dụng hàm này có thể
 * muốn kiểm tra lại mảng trả về để đảm bảo tính hợp lệ.
 *
 * @see sanitize_comment_cookies() Sử dụng để làm sạch cookie.
 *
 * @since 2.0.4
 *
 * @return array {
 *     Mảng các biến của người bình luận hiện tại.
 *
 *     @type string $comment_author       Tên của người bình luận hiện tại, hoặc chuỗi rỗng.
 *     @type string $comment_author_email Địa chỉ email của người bình luận hiện tại, hoặc chuỗi rỗng.
 *     @type string $comment_author_url   Địa chỉ URL của người bình luận hiện tại, hoặc chuỗi rỗng.
 * }
 */
function wp_get_current_commenter() {
	// Cookie đã được làm sạch rồi.

	$comment_author = '';
	if ( isset( $_COOKIE[ 'comment_author_' . COOKIEHASH ] ) ) {
		$comment_author = $_COOKIE[ 'comment_author_' . COOKIEHASH ];
	}

	$comment_author_email = '';
	if ( isset( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) ) {
		$comment_author_email = $_COOKIE[ 'comment_author_email_' . COOKIEHASH ];
	}

	$comment_author_url = '';
	if ( isset( $_COOKIE[ 'comment_author_url_' . COOKIEHASH ] ) ) {
		$comment_author_url = $_COOKIE[ 'comment_author_url_' . COOKIEHASH ];
	}

	/**
	 * Lọc tên, email và URL của người bình luận hiện tại.
	 *
	 * @since 3.1.0
	 *
	 * @param array $comment_author_data {
	 *     Mảng các biến của người bình luận hiện tại.
	 *
	 *     @type string $comment_author       Tên của người bình luận hiện tại, hoặc chuỗi rỗng.
	 *     @type string $comment_author_email Địa chỉ email của người bình luận hiện tại, hoặc chuỗi rỗng.
	 *     @type string $comment_author_url   Địa chỉ URL của người bình luận hiện tại, hoặc chuỗi rỗng.
	 * }
	 */
	return apply_filters( 'wp_get_current_commenter', compact( 'comment_author', 'comment_author_email', 'comment_author_url' ) );
}

/**
 * Lấy email của tác giả bình luận chưa được phê duyệt.
 *
 * Được sử dụng để cho phép người bình luận xem bình luận đang chờ duyệt của họ.
 *
 * @since 5.1.0
 * @since 5.7.0 Khoảng thời gian mà email tác giả cho bình luận chưa phê duyệt
 *              có thể được lấy đã được mở rộng lên 10 phút.
 *
 * @return string Email tác giả bình luận chưa phê duyệt (khi được cung cấp).
 */
function wp_get_unapproved_comment_author_email() {
	$commenter_email = '';

	if ( ! empty( $_GET['unapproved'] ) && ! empty( $_GET['moderation-hash'] ) ) {
		$comment_id = (int) $_GET['unapproved'];
		$comment    = get_comment( $comment_id );

		if ( $comment && hash_equals( $_GET['moderation-hash'], wp_hash( $comment->comment_date_gmt ) ) ) {
			// Bình luận chỉ có thể được xem bởi tác giả bình luận trong 10 phút.
			$comment_preview_expires = strtotime( $comment->comment_date_gmt . '+10 minutes' );

			if ( time() < $comment_preview_expires ) {
				$commenter_email = $comment->comment_author_email;
			}
		}
	}

	if ( ! $commenter_email ) {
		$commenter       = wp_get_current_commenter();
		$commenter_email = $commenter['comment_author_email'];
	}

	return $commenter_email;
}

/**
 * Chèn bình luận vào cơ sở dữ liệu.
 *
 * @since 2.0.0
 * @since 4.4.0 Giới thiệu tham số `$comment_meta`.
 * @since 5.5.0 Giá trị mặc định cho tham số `$comment_type` thay đổi thành `comment`.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $commentdata {
 *     Mảng các tham số để chèn bình luận mới.
 *
 *     @type string     $comment_agent        User-Agent HTTP của `$comment_author` khi
 *                                            bình luận được gửi. Mặc định rỗng.
 *     @type int|string $comment_approved     Bình luận đã được phê duyệt chưa. Mặc định 1.
 *     @type string     $comment_author       Tên tác giả bình luận. Mặc định rỗng.
 *     @type string     $comment_author_email Địa chỉ email của `$comment_author`. Mặc định rỗng.
 *     @type string     $comment_author_IP    Địa chỉ IP của `$comment_author`. Mặc định rỗng.
 *     @type string     $comment_author_url   Địa chỉ URL của `$comment_author`. Mặc định rỗng.
 *     @type string     $comment_content      Nội dung bình luận. Mặc định rỗng.
 *     @type string     $comment_date         Ngày bình luận được gửi. Để thiết lập ngày
 *                                            thủ công, `$comment_date_gmt` cũng phải được chỉ định.
 *                                            Mặc định là thời gian hiện tại.
 *     @type string     $comment_date_gmt     Ngày bình luận được gửi theo múi giờ GMT.
 *                                            Mặc định là `$comment_date` theo múi giờ GMT của trang web.
 *     @type int        $comment_karma        Karma của bình luận. Mặc định 0.
 *     @type int        $comment_parent       ID của bình luận cha, nếu có. Mặc định 0.
 *     @type int        $comment_post_ID      ID của bài viết liên quan đến bình luận, nếu có.
 *                                            Mặc định 0.
 *     @type string     $comment_type         Loại bình luận. Mặc định 'comment'.
 *     @type array      $comment_meta         Tùy chọn. Mảng các cặp key/value để lưu trong commentmeta cho
 *                                            bình luận mới.
 *     @type int        $user_id              ID người dùng đã gửi bình luận. Mặc định 0.
 * }
 * @return int|false ID bình luận mới khi thành công, false khi thất bại.
 */
function wp_insert_comment( $commentdata ) {
	global $wpdb;

	$data = wp_unslash( $commentdata );

	$comment_author       = ! isset( $data['comment_author'] ) ? '' : $data['comment_author'];
	$comment_author_email = ! isset( $data['comment_author_email'] ) ? '' : $data['comment_author_email'];
	$comment_author_url   = ! isset( $data['comment_author_url'] ) ? '' : $data['comment_author_url'];
	$comment_author_ip    = ! isset( $data['comment_author_IP'] ) ? '' : $data['comment_author_IP'];

	$comment_date     = ! isset( $data['comment_date'] ) ? current_time( 'mysql' ) : $data['comment_date'];
	$comment_date_gmt = ! isset( $data['comment_date_gmt'] ) ? get_gmt_from_date( $comment_date ) : $data['comment_date_gmt'];

	$comment_post_id  = ! isset( $data['comment_post_ID'] ) ? 0 : $data['comment_post_ID'];
	$comment_content  = ! isset( $data['comment_content'] ) ? '' : $data['comment_content'];
	$comment_karma    = ! isset( $data['comment_karma'] ) ? 0 : $data['comment_karma'];
	$comment_approved = ! isset( $data['comment_approved'] ) ? 1 : $data['comment_approved'];
	$comment_agent    = ! isset( $data['comment_agent'] ) ? '' : $data['comment_agent'];
	$comment_type     = empty( $data['comment_type'] ) ? 'comment' : $data['comment_type'];
	$comment_parent   = ! isset( $data['comment_parent'] ) ? 0 : $data['comment_parent'];

	$user_id = ! isset( $data['user_id'] ) ? 0 : $data['user_id'];

	$compacted = array(
		'comment_post_ID'   => $comment_post_id,
		'comment_author_IP' => $comment_author_ip,
	);

	$compacted += compact(
		'comment_author',
		'comment_author_email',
		'comment_author_url',
		'comment_date',
		'comment_date_gmt',
		'comment_content',
		'comment_karma',
		'comment_approved',
		'comment_agent',
		'comment_type',
		'comment_parent',
		'user_id'
	);

	if ( ! $wpdb->insert( $wpdb->comments, $compacted ) ) {
		return false;
	}

	$id = (int) $wpdb->insert_id;

	if ( 1 === (int) $comment_approved ) {
		wp_update_comment_count( $comment_post_id );

		$data = array();
		foreach ( array( 'server', 'gmt', 'blog' ) as $timezone ) {
			$data[] = "lastcommentmodified:$timezone";
		}
		wp_cache_delete_multiple( $data, 'timeinfo' );
	}

	clean_comment_cache( $id );

	$comment = get_comment( $id );

	// Nếu metadata được cung cấp, lưu trữ nó.
	if ( isset( $commentdata['comment_meta'] ) && is_array( $commentdata['comment_meta'] ) ) {
		foreach ( $commentdata['comment_meta'] as $meta_key => $meta_value ) {
			add_comment_meta( $comment->comment_ID, $meta_key, $meta_value, true );
		}
	}

	/**
	 * Kích hoạt ngay sau khi bình luận được chèn vào cơ sở dữ liệu.
	 *
	 * @since 2.8.0
	 *
	 * @param int        $id      ID bình luận.
	 * @param WP_Comment $comment Đối tượng bình luận.
	 */
	do_action( 'wp_insert_comment', $id, $comment );

	return $id;
}

/**
 * Lọc và làm sạch dữ liệu bình luận.
 *
 * Thiết lập trường 'filtered' của dữ liệu bình luận thành true khi hoàn tất. Điều này có thể
 * được kiểm tra để xác định liệu bình luận có nên được lọc hay không và tránh
 * lọc cùng một bình luận nhiều lần.
 *
 * @since 2.0.0
 *
 * @param array $commentdata Chứa thông tin về bình luận.
 * @return array Thông tin bình luận đã được phân tích.
 */
function wp_filter_comment( $commentdata ) {
	if ( isset( $commentdata['user_ID'] ) ) {
		/**
		 * Lọc ID người dùng của tác giả bình luận trước khi được thiết lập.
		 *
		 * Lần đầu tiên bộ lọc này được đánh giá, `user_ID` được kiểm tra
		 * (để tương thích ngược), tiếp theo là giá trị `user_id` tiêu chuẩn.
		 *
		 * @since 1.5.0
		 *
		 * @param int $user_id ID người dùng của tác giả bình luận.
		 */
		$commentdata['user_id'] = apply_filters( 'pre_user_id', $commentdata['user_ID'] );
	} elseif ( isset( $commentdata['user_id'] ) ) {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/comment.php */
		$commentdata['user_id'] = apply_filters( 'pre_user_id', $commentdata['user_id'] );
	}

	/**
	 * Lọc User-Agent trình duyệt của tác giả bình luận trước khi được thiết lập.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_agent User-Agent trình duyệt của tác giả bình luận.
	 */
	$commentdata['comment_agent'] = apply_filters( 'pre_comment_user_agent', ( isset( $commentdata['comment_agent'] ) ? $commentdata['comment_agent'] : '' ) );
	/** Bộ lọc này được ghi tài liệu trong wp-includes/comment.php */
	$commentdata['comment_author'] = apply_filters( 'pre_comment_author_name', $commentdata['comment_author'] );
	/**
	 * Lọc nội dung bình luận trước khi được thiết lập.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_content Nội dung bình luận.
	 */
	$commentdata['comment_content'] = apply_filters( 'pre_comment_content', $commentdata['comment_content'] );
	/**
	 * Lọc địa chỉ IP của tác giả bình luận trước khi được thiết lập.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_author_ip Địa chỉ IP của tác giả bình luận.
	 */
	$commentdata['comment_author_IP'] = apply_filters( 'pre_comment_user_ip', $commentdata['comment_author_IP'] );
	/** Bộ lọc này được ghi tài liệu trong wp-includes/comment.php */
	$commentdata['comment_author_url'] = apply_filters( 'pre_comment_author_url', $commentdata['comment_author_url'] );
	/** Bộ lọc này được ghi tài liệu trong wp-includes/comment.php */
	$commentdata['comment_author_email'] = apply_filters( 'pre_comment_author_email', $commentdata['comment_author_email'] );

	$commentdata['filtered'] = true;

	return $commentdata;
}

/**
 * Xác định liệu bình luận có nên bị chặn vì flood bình luận hay không.
 *
 * @since 2.1.0
 *
 * @param bool $block            Plugin đã chặn bình luận chưa.
 * @param int  $time_lastcomment Dấu thời gian của bình luận cuối cùng.
 * @param int  $time_newcomment  Dấu thời gian của bình luận mới.
 * @return bool Bình luận có nên bị chặn hay không.
 */
function wp_throttle_comment_flood( $block, $time_lastcomment, $time_newcomment ) {
	if ( $block ) { // Một plugin đã chặn rồi... chúng ta sẽ giữ nguyên quyết định đó.
		return $block;
	}
	if ( ( $time_newcomment - $time_lastcomment ) < 15 ) {
		return true;
	}
	return false;
}

/**
 * Thêm bình luận mới vào cơ sở dữ liệu.
 *
 * Lọc bình luận mới để đảm bảo các trường được làm sạch và hợp lệ trước khi
 * chèn bình luận vào cơ sở dữ liệu. Gọi hành động {@see 'comment_post'} với ID bình luận
 * và xác định bình luận có được WordPress phê duyệt hay không. Cũng có bộ lọc {@see 'preprocess_comment'}
 * để xử lý dữ liệu bình luận trước khi hàm xử lý nó.
 *
 * Chúng tôi sử dụng `REMOTE_ADDR` trực tiếp ở đây. Nếu bạn đang đứng sau proxy, bạn nên đảm bảo
 * rằng nó được thiết lập đúng, chẳng hạn trong wp-config.php, cho môi trường của bạn.
 *
 * Xem {@link https://core.trac.wordpress.org/ticket/9235}
 *
 * @since 1.5.0
 * @since 4.3.0 Giới thiệu tham số `comment_agent` và `comment_author_IP`.
 * @since 4.7.0 Thêm tham số `$avoid_die`, cho phép hàm
 *              trả về đối tượng WP_Error thay vì dừng thực thi.
 * @since 5.5.0 Tham số `$avoid_die` được đổi tên thành `$wp_error`.
 * @since 5.5.0 Giới thiệu tham số `comment_type`.
 *
 * @see wp_insert_comment()
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $commentdata {
 *     Dữ liệu bình luận.
 *
 *     @type string $comment_author       Tên tác giả bình luận.
 *     @type string $comment_author_email Địa chỉ email tác giả bình luận.
 *     @type string $comment_author_url   URL tác giả bình luận.
 *     @type string $comment_content      Nội dung bình luận.
 *     @type string $comment_date         Ngày bình luận được gửi. Mặc định là thời gian hiện tại.
 *     @type string $comment_date_gmt     Ngày bình luận được gửi theo múi giờ GMT.
 *                                        Mặc định là `$comment_date` theo múi giờ GMT.
 *     @type string $comment_type         Loại bình luận. Mặc định 'comment'.
 *     @type int    $comment_parent       ID bình luận cha, nếu có. Mặc định 0.
 *     @type int    $comment_post_ID      ID bài viết liên quan đến bình luận.
 *     @type int    $user_id              ID người dùng đã gửi bình luận. Mặc định 0.
 *     @type int    $user_ID              Giữ lại để tương thích ngược. Sử dụng `$user_id` thay thế.
 *     @type string $comment_agent        User-Agent của tác giả bình luận. Mặc định là giá trị của 'HTTP_USER_AGENT'
 *                                        trong biến toàn cục `$_SERVER` được gửi trong yêu cầu gốc.
 *     @type string $comment_author_IP    Địa chỉ IP tác giả bình luận định dạng IPv4. Mặc định là giá trị của
 *                                        'REMOTE_ADDR' trong biến toàn cục `$_SERVER` được gửi trong yêu cầu gốc.
 * }
 * @param bool  $wp_error Lỗi có nên được trả về dưới dạng đối tượng WP_Error thay vì
 *                        thực thi wp_die() hay không? Mặc định false.
 * @return int|false|WP_Error ID bình luận khi thành công, false hoặc WP_Error khi thất bại.
 */
function wp_new_comment( $commentdata, $wp_error = false ) {
	global $wpdb;

	/*
	 * Chuẩn hóa `user_ID` thành `user_id`, nhưng truyền key cũ
	 * vào bộ lọc `preprocess_comment` để tương thích ngược.
	 */
	if ( isset( $commentdata['user_ID'] ) ) {
		$commentdata['user_ID'] = (int) $commentdata['user_ID'];
		$commentdata['user_id'] = $commentdata['user_ID'];
	} elseif ( isset( $commentdata['user_id'] ) ) {
		$commentdata['user_id'] = (int) $commentdata['user_id'];
		$commentdata['user_ID'] = $commentdata['user_id'];
	}

	$prefiltered_user_id = ( isset( $commentdata['user_id'] ) ) ? (int) $commentdata['user_id'] : 0;

	if ( ! isset( $commentdata['comment_author_IP'] ) ) {
		$commentdata['comment_author_IP'] = $_SERVER['REMOTE_ADDR'];
	}

	if ( ! isset( $commentdata['comment_agent'] ) ) {
		$commentdata['comment_agent'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}

	/**
	 * Lọc dữ liệu bình luận trước khi được làm sạch và chèn vào cơ sở dữ liệu.
	 *
	 * @since 1.5.0
	 * @since 5.6.0 Dữ liệu bình luận bao gồm các giá trị `comment_agent` và `comment_author_IP`.
	 *
	 * @param array $commentdata Dữ liệu bình luận.
	 */
	$commentdata = apply_filters( 'preprocess_comment', $commentdata );

	$commentdata['comment_post_ID'] = (int) $commentdata['comment_post_ID'];

	// Chuẩn hóa `user_ID` thành `user_id` lần nữa, sau bộ lọc.
	if ( isset( $commentdata['user_ID'] ) && $prefiltered_user_id !== (int) $commentdata['user_ID'] ) {
		$commentdata['user_ID'] = (int) $commentdata['user_ID'];
		$commentdata['user_id'] = $commentdata['user_ID'];
	} elseif ( isset( $commentdata['user_id'] ) ) {
		$commentdata['user_id'] = (int) $commentdata['user_id'];
		$commentdata['user_ID'] = $commentdata['user_id'];
	}

	$commentdata['comment_parent'] = isset( $commentdata['comment_parent'] ) ? absint( $commentdata['comment_parent'] ) : 0;

	$parent_status = ( $commentdata['comment_parent'] > 0 ) ? wp_get_comment_status( $commentdata['comment_parent'] ) : '';

	$commentdata['comment_parent'] = ( 'approved' === $parent_status || 'unapproved' === $parent_status ) ? $commentdata['comment_parent'] : 0;

	$commentdata['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '', $commentdata['comment_author_IP'] );

	$commentdata['comment_agent'] = substr( $commentdata['comment_agent'], 0, 254 );

	if ( empty( $commentdata['comment_date'] ) ) {
		$commentdata['comment_date'] = current_time( 'mysql' );
	}

	if ( empty( $commentdata['comment_date_gmt'] ) ) {
		$commentdata['comment_date_gmt'] = current_time( 'mysql', 1 );
	}

	if ( empty( $commentdata['comment_type'] ) ) {
		$commentdata['comment_type'] = 'comment';
	}

	$commentdata['comment_approved'] = wp_allow_comment( $commentdata, $wp_error );

	if ( is_wp_error( $commentdata['comment_approved'] ) ) {
		return $commentdata['comment_approved'];
	}

	$commentdata = wp_filter_comment( $commentdata );

	if ( ! in_array( $commentdata['comment_approved'], array( 'trash', 'spam' ), true ) ) {
		// Xác thực bình luận lần nữa sau khi các bộ lọc được áp dụng cho dữ liệu bình luận.
		$commentdata['comment_approved'] = wp_check_comment_data( $commentdata );
	}

	if ( is_wp_error( $commentdata['comment_approved'] ) ) {
		return $commentdata['comment_approved'];
	}

	$comment_id = wp_insert_comment( $commentdata );

	if ( ! $comment_id ) {
		$fields = array( 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content' );

		foreach ( $fields as $field ) {
			if ( isset( $commentdata[ $field ] ) ) {
				$commentdata[ $field ] = $wpdb->strip_invalid_text_for_column( $wpdb->comments, $field, $commentdata[ $field ] );
			}
		}

		$commentdata = wp_filter_comment( $commentdata );

		$commentdata['comment_approved'] = wp_allow_comment( $commentdata, $wp_error );
		if ( is_wp_error( $commentdata['comment_approved'] ) ) {
			return $commentdata['comment_approved'];
		}

		$comment_id = wp_insert_comment( $commentdata );
		if ( ! $comment_id ) {
			return false;
		}
	}

	/**
	 * Kích hoạt ngay sau khi bình luận được chèn vào cơ sở dữ liệu.
	 *
	 * @since 1.2.0
	 * @since 4.5.0 Thêm tham số `$commentdata`.
	 *
	 * @param int        $comment_id       ID bình luận.
	 * @param int|string $comment_approved 1 nếu bình luận được phê duyệt, 0 nếu không, 'spam' nếu là spam.
	 * @param array      $commentdata      Dữ liệu bình luận.
	 */
	do_action( 'comment_post', $comment_id, $commentdata['comment_approved'], $commentdata );

	return $comment_id;
}

/**
 * Gửi thông báo kiểm duyệt bình luận đến người kiểm duyệt.
 *
 * @since 4.4.0
 *
 * @param int $comment_id ID bình luận.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_new_comment_notify_moderator( $comment_id ) {
	$comment = get_comment( $comment_id );

	// Chỉ gửi thông báo cho bình luận đang chờ duyệt.
	$maybe_notify = ( '0' === $comment->comment_approved );

	/** Bộ lọc này được ghi tài liệu trong wp-includes/pluggable.php */
	$maybe_notify = apply_filters( 'notify_moderator', $maybe_notify, $comment_id );

	if ( ! $maybe_notify ) {
		return false;
	}

	return wp_notify_moderator( $comment_id );
}

/**
 * Gửi thông báo về bình luận mới đến tác giả bài viết.
 *
 * @since 4.4.0
 *
 * Sử dụng bộ lọc {@see 'notify_post_author'} để xác định liệu tác giả bài viết
 * có nên được thông báo khi bình luận mới được thêm hay không, ghi đè cài đặt trang web.
 *
 * @param int $comment_id ID bình luận.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_new_comment_notify_postauthor( $comment_id ) {
	$comment = get_comment( $comment_id );

	$maybe_notify = get_option( 'comments_notify' );

	/**
	 * Lọc có gửi email thông báo bình luận mới cho tác giả bài viết hay không,
	 * ghi đè cài đặt trang web.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $maybe_notify Có thông báo cho tác giả bài viết về bình luận mới hay không.
	 * @param int  $comment_id   ID bình luận cho thông báo.
	 */
	$maybe_notify = apply_filters( 'notify_post_author', $maybe_notify, $comment_id );

	/*
	 * wp_notify_postauthor() kiểm tra xem có thông báo cho tác giả về bình luận của chính họ không.
	 * Mặc định sẽ không thông báo, nhưng các bộ lọc có thể ghi đè điều này.
	 */
	if ( ! $maybe_notify ) {
		return false;
	}

	// Chỉ gửi thông báo cho bình luận đã được phê duyệt.
	if ( ! isset( $comment->comment_approved ) || '1' !== $comment->comment_approved ) {
		return false;
	}

	return wp_notify_postauthor( $comment_id );
}

/**
 * Thiết lập trạng thái của bình luận.
 *
 * Hành động {@see 'wp_set_comment_status'} được gọi sau khi bình luận được xử lý.
 * Nếu trạng thái bình luận không có trong danh sách, thì false được trả về.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Comment $comment_id     ID bình luận hoặc đối tượng WP_Comment.
 * @param string         $comment_status Trạng thái bình luận mới, 'hold', 'approve', 'spam', hoặc 'trash'.
 * @param bool           $wp_error       Có trả về đối tượng WP_Error nếu thất bại hay không. Mặc định false.
 * @return bool|WP_Error True khi thành công, false hoặc WP_Error khi thất bại.
 */
function wp_set_comment_status( $comment_id, $comment_status, $wp_error = false ) {
	global $wpdb;

	switch ( $comment_status ) {
		case 'hold':
		case '0':
			$status = '0';
			break;
		case 'approve':
		case '1':
			$status = '1';
			add_action( 'wp_set_comment_status', 'wp_new_comment_notify_postauthor' );
			break;
		case 'spam':
			$status = 'spam';
			break;
		case 'trash':
			$status = 'trash';
			break;
		default:
			return false;
	}

	$comment_old = clone get_comment( $comment_id );

	if ( ! $wpdb->update( $wpdb->comments, array( 'comment_approved' => $status ), array( 'comment_ID' => $comment_old->comment_ID ) ) ) {
		if ( $wp_error ) {
			return new WP_Error( 'db_update_error', __( 'Could not update comment status.' ), $wpdb->last_error );
		} else {
			return false;
		}
	}

	clean_comment_cache( $comment_old->comment_ID );

	$comment = get_comment( $comment_old->comment_ID );

	/**
	 * Kích hoạt ngay sau khi chuyển đổi trạng thái bình luận trong cơ sở dữ liệu
	 * và xóa bình luận khỏi cache đối tượng, nhưng trước tất cả các hook chuyển đổi trạng thái.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_id     ID bình luận dưới dạng chuỗi số.
	 * @param string $comment_status Trạng thái bình luận hiện tại. Các giá trị có thể bao gồm
	 *                               'hold', '0', 'approve', '1', 'spam', và 'trash'.
	 */
	do_action( 'wp_set_comment_status', $comment->comment_ID, $comment_status );

	wp_transition_comment_status( $comment_status, $comment_old->comment_approved, $comment );

	wp_update_comment_count( $comment->comment_post_ID );

	return true;
}

/**
 * Cập nhật bình luận hiện có trong cơ sở dữ liệu.
 *
 * Lọc bình luận và đảm bảo một số trường hợp lệ trước khi cập nhật.
 *
 * @since 2.0.0
 * @since 4.9.0 Thêm cập nhật meta bình luận trong quá trình cập nhật bình luận.
 * @since 5.5.0 Thêm tham số `$wp_error`.
 * @since 5.5.0 Giá trị trả về cho ID bình luận hoặc bài viết không hợp lệ
 *              đã được thay đổi thành false thay vì 0.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $commentarr Chứa thông tin về bình luận.
 * @param bool  $wp_error   Tùy chọn. Có trả về WP_Error khi thất bại hay không. Mặc định false.
 * @return int|false|WP_Error Giá trị 1 nếu bình luận được cập nhật, 0 nếu không cập nhật.
 *                            False hoặc đối tượng WP_Error khi thất bại.
 */
function wp_update_comment( $commentarr, $wp_error = false ) {
	global $wpdb;

	// Đầu tiên, lấy tất cả các trường gốc.
	$comment = get_comment( $commentarr['comment_ID'], ARRAY_A );

	if ( empty( $comment ) ) {
		if ( $wp_error ) {
			return new WP_Error( 'invalid_comment_id', __( 'Invalid comment ID.' ) );
		} else {
			return false;
		}
	}

	// Đảm bảo rằng ID bài viết của bình luận hợp lệ (nếu được chỉ định).
	if ( ! empty( $commentarr['comment_post_ID'] ) && ! get_post( $commentarr['comment_post_ID'] ) ) {
		if ( $wp_error ) {
			return new WP_Error( 'invalid_post_id', __( 'Invalid post ID.' ) );
		} else {
			return false;
		}
	}

	$filter_comment = false;
	if ( ! has_filter( 'pre_comment_content', 'wp_filter_kses' ) ) {
		$filter_comment = ! user_can( isset( $comment['user_id'] ) ? $comment['user_id'] : 0, 'unfiltered_html' );
	}

	if ( $filter_comment ) {
		add_filter( 'pre_comment_content', 'wp_filter_kses' );
	}

	// Escape dữ liệu lấy từ cơ sở dữ liệu.
	$comment = wp_slash( $comment );

	$old_status = $comment['comment_approved'];

	// Gộp các trường cũ và mới, trường mới ghi đè trường cũ.
	$commentarr = array_merge( $comment, $commentarr );

	$commentarr = wp_filter_comment( $commentarr );

	if ( $filter_comment ) {
		remove_filter( 'pre_comment_content', 'wp_filter_kses' );
	}

	// Bây giờ trích xuất mảng đã gộp.
	$data = wp_unslash( $commentarr );

	/**
	 * Lọc nội dung bình luận trước khi được cập nhật trong cơ sở dữ liệu.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_content Dữ liệu bình luận.
	 */
	$data['comment_content'] = apply_filters( 'comment_save_pre', $data['comment_content'] );

	$data['comment_date_gmt'] = get_gmt_from_date( $data['comment_date'] );

	if ( ! isset( $data['comment_approved'] ) ) {
		$data['comment_approved'] = 1;
	} elseif ( 'hold' === $data['comment_approved'] ) {
		$data['comment_approved'] = 0;
	} elseif ( 'approve' === $data['comment_approved'] ) {
		$data['comment_approved'] = 1;
	}

	$comment_id      = $data['comment_ID'];
	$comment_post_id = $data['comment_post_ID'];

	/**
	 * Lọc dữ liệu bình luận ngay trước khi được cập nhật trong cơ sở dữ liệu.
	 *
	 * Lưu ý: dữ liệu được truyền vào bộ lọc đã được unslash.
	 *
	 * @since 4.7.0
	 * @since 5.5.0 Trả về giá trị WP_Error từ bộ lọc sẽ bỏ qua cập nhật bình luận
	 *              và cho phép bỏ qua xử lý tiếp theo.
	 *
	 * @param array|WP_Error $data       Dữ liệu bình luận mới đã xử lý, hoặc WP_Error.
	 * @param array          $comment    Dữ liệu bình luận cũ chưa slash.
	 * @param array          $commentarr Dữ liệu bình luận mới thô.
	 */
	$data = apply_filters( 'wp_update_comment_data', $data, $comment, $commentarr );

	// Không tiếp tục khi thất bại.
	if ( is_wp_error( $data ) ) {
		if ( $wp_error ) {
			return $data;
		} else {
			return false;
		}
	}

	$keys = array(
		'comment_post_ID',
		'comment_author',
		'comment_author_email',
		'comment_author_url',
		'comment_author_IP',
		'comment_date',
		'comment_date_gmt',
		'comment_content',
		'comment_karma',
		'comment_approved',
		'comment_agent',
		'comment_type',
		'comment_parent',
		'user_id',
	);

	$data = wp_array_slice_assoc( $data, $keys );

	$result = $wpdb->update( $wpdb->comments, $data, array( 'comment_ID' => $comment_id ) );

	if ( false === $result ) {
		if ( $wp_error ) {
			return new WP_Error( 'db_update_error', __( 'Could not update comment in the database.' ), $wpdb->last_error );
		} else {
			return false;
		}
	}

	// Nếu metadata được cung cấp, lưu trữ nó.
	if ( isset( $commentarr['comment_meta'] ) && is_array( $commentarr['comment_meta'] ) ) {
		foreach ( $commentarr['comment_meta'] as $meta_key => $meta_value ) {
			update_comment_meta( $comment_id, $meta_key, $meta_value );
		}
	}

	clean_comment_cache( $comment_id );
	wp_update_comment_count( $comment_post_id );

	/**
	 * Kích hoạt ngay sau khi bình luận được cập nhật trong cơ sở dữ liệu.
	 *
	 * Hook cũng kích hoạt ngay trước khi các hook chuyển đổi trạng thái bình luận được kích hoạt.
	 *
	 * @since 1.2.0
	 * @since 4.6.0 Thêm tham số `$data`.
	 *
	 * @param int   $comment_id ID bình luận.
	 * @param array $data       Dữ liệu bình luận.
	 */
	do_action( 'edit_comment', $comment_id, $data );

	$comment = get_comment( $comment_id );

	wp_transition_comment_status( $comment->comment_approved, $old_status, $comment );

	return $result;
}

/**
 * Xác định có hoãn đếm bình luận hay không.
 *
 * Khi thiết lập $defer thành true, tất cả số lượng bình luận bài viết sẽ không được cập nhật
 * cho đến khi $defer được thiết lập thành false. Khi $defer được thiết lập thành false, thì tất cả
 * các số lượng bình luận đã hoãn trước đó sẽ được tự động cập nhật
 * mà không cần gọi wp_update_comment_count() sau đó.
 *
 * @since 2.5.0
 *
 * @param bool $defer
 * @return bool
 */
function wp_defer_comment_counting( $defer = null ) {
	static $_defer = false;

	if ( is_bool( $defer ) ) {
		$_defer = $defer;
		// Xả tất cả các số lượng đã hoãn.
		if ( ! $defer ) {
			wp_update_comment_count( null, true );
		}
	}

	return $_defer;
}

/**
 * Cập nhật số lượng bình luận cho bài viết.
 *
 * Khi $do_deferred là false (mặc định) và bình luận đã được thiết lập để
 * hoãn, post_id sẽ được thêm vào hàng đợi, sẽ được cập nhật sau
 * và chỉ cập nhật một lần cho mỗi ID bài viết.
 *
 * Nếu bình luận không được thiết lập hoãn, thì bài viết sẽ được
 * cập nhật. Khi $do_deferred được thiết lập thành true, thì tất cả các ID bài viết
 * đã hoãn trước đó sẽ được cập nhật cùng với $post_id hiện tại.
 *
 * @since 2.1.0
 *
 * @see wp_update_comment_count_now() Để biết nguyên nhân có thể gây ra giá trị trả về false
 *
 * @param int|null $post_id     ID bài viết.
 * @param bool     $do_deferred Tùy chọn. Có xử lý số lượng bình luận bài viết đã hoãn trước đó hay không.
 *                              Mặc định false.
 * @return bool|void True khi thành công, false khi thất bại hoặc nếu bài viết có ID đó
 *                   không tồn tại.
 */
function wp_update_comment_count( $post_id, $do_deferred = false ) {
	static $_deferred = array();

	if ( empty( $post_id ) && ! $do_deferred ) {
		return false;
	}

	if ( $do_deferred ) {
		$_deferred = array_unique( $_deferred );
		foreach ( $_deferred as $i => $_post_id ) {
			wp_update_comment_count_now( $_post_id );
			unset( $_deferred[ $i ] );
			/** @todo Di chuyển phần này ra ngoài vòng lặp foreach và đặt lại $_deferred thành mảng thay thế */
		}
	}

	if ( wp_defer_comment_counting() ) {
		$_deferred[] = $post_id;
		return true;
	} elseif ( $post_id ) {
		return wp_update_comment_count_now( $post_id );
	}
}

/**
 * Cập nhật số lượng bình luận cho bài viết.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $post_id ID bài viết.
 * @return bool True khi thành công, false nếu bài viết không tồn tại.
 */
function wp_update_comment_count_now( $post_id ) {
	global $wpdb;

	$post_id = (int) $post_id;

	if ( ! $post_id ) {
		return false;
	}

	wp_cache_delete( 'comments-0', 'counts' );
	wp_cache_delete( "comments-{$post_id}", 'counts' );

	$post = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	$old = (int) $post->comment_count;

	/**
	 * Lọc số lượng bình luận của bài viết trước khi được cập nhật trong cơ sở dữ liệu.
	 *
	 * @since 4.5.0
	 *
	 * @param int|null $new     Số lượng bình luận mới. Mặc định null.
	 * @param int      $old     Số lượng bình luận cũ.
	 * @param int      $post_id ID bài viết.
	 */
	$new = apply_filters( 'pre_wp_update_comment_count_now', null, $old, $post_id );

	if ( is_null( $new ) ) {
		$new = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1'", $post_id ) );
	} else {
		$new = (int) $new;
	}

	$wpdb->update( $wpdb->posts, array( 'comment_count' => $new ), array( 'ID' => $post_id ) );

	clean_post_cache( $post );

	/**
	 * Kích hoạt ngay sau khi số lượng bình luận của bài viết được cập nhật trong cơ sở dữ liệu.
	 *
	 * @since 2.3.0
	 *
	 * @param int $post_id ID bài viết.
	 * @param int $new     Số lượng bình luận mới.
	 * @param int $old     Số lượng bình luận cũ.
	 */
	do_action( 'wp_update_comment_count', $post_id, $new, $old );

	/** Hành động này được ghi tài liệu trong wp-includes/post.php */
	do_action( "edit_post_{$post->post_type}", $post_id, $post );

	/** Hành động này được ghi tài liệu trong wp-includes/post.php */
	do_action( 'edit_post', $post_id, $post );

	return true;
}

//
// Các hàm Ping và Trackback.
//

/**
 * Tìm URI máy chủ pingback dựa trên URL đã cho.
 *
 * Kiểm tra HTML để tìm liên kết rel="pingback" và header X-Pingback. Hàm kiểm tra
 * header X-Pingback trước và trả về nó nếu có sẵn.
 * Việc kiểm tra rel="pingback" tốn nhiều tài nguyên hơn so với chỉ kiểm tra header.
 *
 * @since 1.5.0
 *
 * @param string $url        URL cần ping.
 * @param string $deprecated Không sử dụng.
 * @return string|false Chuỗi chứa URI khi thành công, false khi thất bại.
 */
function discover_pingback_server_uri( $url, $deprecated = '' ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.7.0' );
	}

	$pingback_str_dquote = 'rel="pingback"';
	$pingback_str_squote = 'rel=\'pingback\'';

	/** @todo Nên sử dụng Filter Extension hoặc preg_match tùy chỉnh thay thế. */
	$parsed_url = parse_url( $url );

	if ( ! isset( $parsed_url['host'] ) ) { // Không phải URL. Điều này không bao giờ nên xảy ra.
		return false;
	}

	// Không tìm kiếm máy chủ pingback trên các tệp tải lên của chúng ta.
	$uploads_dir = wp_get_upload_dir();
	if ( str_starts_with( $url, $uploads_dir['baseurl'] ) ) {
		return false;
	}

	$response = wp_safe_remote_head(
		$url,
		array(
			'timeout'     => 2,
			'httpversion' => '1.0',
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	if ( wp_remote_retrieve_header( $response, 'X-Pingback' ) ) {
		return wp_remote_retrieve_header( $response, 'X-Pingback' );
	}

	// Không phải trang (x)html, sgml, hoặc xml, không cần tiếp tục.
	if ( preg_match( '#(image|audio|video|model)/#is', wp_remote_retrieve_header( $response, 'Content-Type' ) ) ) {
		return false;
	}

	// Bây giờ thực hiện GET vì chúng ta sẽ tìm trong các header HTML (và chắc chắn không phải file nhị phân).
	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout'     => 2,
			'httpversion' => '1.0',
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$contents = wp_remote_retrieve_body( $response );

	$pingback_link_offset_dquote = strpos( $contents, $pingback_str_dquote );
	$pingback_link_offset_squote = strpos( $contents, $pingback_str_squote );

	if ( $pingback_link_offset_dquote || $pingback_link_offset_squote ) {
		$quote                   = ( $pingback_link_offset_dquote ) ? '"' : '\'';
		$pingback_link_offset    = ( '"' === $quote ) ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
		$pingback_href_pos       = strpos( $contents, 'href=', $pingback_link_offset );
		$pingback_href_start     = $pingback_href_pos + 6;
		$pingback_href_end       = strpos( $contents, $quote, $pingback_href_start );
		$pingback_server_url_len = $pingback_href_end - $pingback_href_start;
		$pingback_server_url     = substr( $contents, $pingback_href_start, $pingback_server_url_len );

		// Chúng ta có thể tìm thấy rel="pingback" nhưng URL pingback không đầy đủ.
		if ( $pingback_server_url_len > 0 ) { // Đã tìm thấy!
			return $pingback_server_url;
		}
	}

	return false;
}

/**
 * Thực hiện tất cả pingback, enclosure, trackback, và gửi đến các dịch vụ pingback.
 *
 * @since 2.1.0
 * @since 5.6.0 Giới thiệu hook hành động `do_all_pings` cho từng dịch vụ riêng lẻ.
 */
function do_all_pings() {
	/**
	 * Kích hoạt ngay sau sự kiện `do_pings` để gắn hook từng dịch vụ riêng lẻ.
	 *
	 * @since 5.6.0
	 */
	do_action( 'do_all_pings' );
}

/**
 * Thực hiện tất cả pingback.
 *
 * @since 5.6.0
 */
function do_all_pingbacks() {
	$pings = get_posts(
		array(
			'post_type'        => get_post_types(),
			'suppress_filters' => false,
			'nopaging'         => true,
			'meta_key'         => '_pingme',
			'fields'           => 'ids',
		)
	);

	foreach ( $pings as $ping ) {
		delete_post_meta( $ping, '_pingme' );
		pingback( null, $ping );
	}
}

/**
 * Thực hiện tất cả enclosure.
 *
 * @since 5.6.0
 */
function do_all_enclosures() {
	$enclosures = get_posts(
		array(
			'post_type'        => get_post_types(),
			'suppress_filters' => false,
			'nopaging'         => true,
			'meta_key'         => '_encloseme',
			'fields'           => 'ids',
		)
	);

	foreach ( $enclosures as $enclosure ) {
		delete_post_meta( $enclosure, '_encloseme' );
		do_enclose( null, $enclosure );
	}
}

/**
 * Thực hiện tất cả trackback.
 *
 * @since 5.6.0
 */
function do_all_trackbacks() {
	$trackbacks = get_posts(
		array(
			'post_type'        => get_post_types(),
			'suppress_filters' => false,
			'nopaging'         => true,
			'meta_key'         => '_trackbackme',
			'fields'           => 'ids',
		)
	);

	foreach ( $trackbacks as $trackback ) {
		delete_post_meta( $trackback, '_trackbackme' );
		do_trackbacks( $trackback );
	}
}

/**
 * Thực hiện trackback.
 *
 * @since 1.5.0
 * @since 4.7.0 `$post` có thể là đối tượng WP_Post.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng để thực hiện trackback.
 * @return void|false Trả về false khi thất bại.
 */
function do_trackbacks( $post ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$to_ping = get_to_ping( $post );
	$pinged  = get_pung( $post );

	if ( empty( $to_ping ) ) {
		$wpdb->update( $wpdb->posts, array( 'to_ping' => '' ), array( 'ID' => $post->ID ) );
		return;
	}

	if ( empty( $post->post_excerpt ) ) {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/post-template.php */
		$excerpt = apply_filters( 'the_content', $post->post_content, $post->ID );
	} else {
		/** Bộ lọc này được ghi tài liệu trong wp-includes/post-template.php */
		$excerpt = apply_filters( 'the_excerpt', $post->post_excerpt );
	}

	$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
	$excerpt = wp_html_excerpt( $excerpt, 252, '&#8230;' );

	/** Bộ lọc này được ghi tài liệu trong wp-includes/post-template.php */
	$post_title = apply_filters( 'the_title', $post->post_title, $post->ID );
	$post_title = strip_tags( $post_title );

	if ( $to_ping ) {
		foreach ( (array) $to_ping as $tb_ping ) {
			$tb_ping = trim( $tb_ping );
			if ( ! in_array( $tb_ping, $pinged, true ) ) {
				trackback( $tb_ping, $post_title, $excerpt, $post->ID );
				$pinged[] = $tb_ping;
			} else {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->posts SET to_ping = TRIM(REPLACE(to_ping, %s,
					'')) WHERE ID = %d",
						$tb_ping,
						$post->ID
					)
				);
			}
		}
	}
}

/**
 * Gửi ping đến tất cả các dịch vụ trang ping.
 *
 * @since 1.2.0
 *
 * @param int $post_id ID bài viết.
 * @return int Cùng ID bài viết đã cung cấp.
 */
function generic_ping( $post_id = 0 ) {
	$services = get_option( 'ping_sites' );

	$services = explode( "\n", $services );
	foreach ( (array) $services as $service ) {
		$service = trim( $service );
		if ( '' !== $service ) {
			weblog_ping( $service );
		}
	}

	return $post_id;
}

/**
 * Gửi pingback cho các liên kết tìm thấy trong bài viết.
 *
 * @since 0.71
 * @since 4.7.0 `$post` có thể là đối tượng WP_Post.
 * @since 6.8.0 Trả về mảng trạng thái pingback được đánh chỉ mục theo liên kết.
 *
 * @param string      $content Nội dung bài viết để kiểm tra liên kết. Nếu rỗng sẽ lấy từ bài viết.
 * @param int|WP_Post $post    ID bài viết hoặc đối tượng.
 * @return array<string, bool> Mảng trạng thái pingback được đánh chỉ mục theo liên kết.
 */
function pingback( $content, $post ) {
	require_once ABSPATH . WPINC . '/class-IXR.php';
	require_once ABSPATH . WPINC . '/class-wp-http-ixr-client.php';

	// Mã gốc bởi Mort (http://mort.mine.nu:8080).
	$post_links = array();

	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	$pung = get_pung( $post );

	if ( empty( $content ) ) {
		$content = $post->post_content;
	}

	/*
	 * Bước 1.
	 * Phân tích bài viết, các liên kết ngoài (nếu có) được lưu trong mảng $post_links.
	 */
	$post_links_temp = wp_extract_urls( $content );

	$ping_status = array();
	/*
	 * Bước 2.
	 * Duyệt qua mảng liên kết.
	 * Đầu tiên loại bỏ các liên kết trỏ đến trang web, không phải đến các tệp cụ thể.
	 * Ví dụ:
	 * http://dummy-weblog.org
	 * http://dummy-weblog.org/
	 * http://dummy-weblog.org/post.php
	 * Chúng ta không muốn ping loại đầu tiên và thứ hai, ngay cả khi chúng có <link/> hợp lệ.
	 */
	foreach ( (array) $post_links_temp as $link_test ) {
		// Nếu chúng ta chưa ping nó và không phải liên kết đến chính nó.
		if ( ! in_array( $link_test, $pung, true ) && ( url_to_postid( $link_test ) !== $post->ID )
			// Ngoài ra, không bao giờ ping các tệp đính kèm cục bộ.
			&& ! is_local_attachment( $link_test )
		) {
			$test = parse_url( $link_test );
			if ( $test ) {
				if ( isset( $test['query'] ) ) {
					$post_links[] = $link_test;
				} elseif ( isset( $test['path'] ) && ( '/' !== $test['path'] ) && ( '' !== $test['path'] ) ) {
					$post_links[] = $link_test;
				}
			}
		}
	}

	$post_links = array_unique( $post_links );

	/**
	 * Kích hoạt ngay trước khi gửi pingback cho các liên kết tìm thấy trong bài viết.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $post_links Mảng URL liên kết cần kiểm tra (truyền tham chiếu).
	 * @param string[] $pung       Mảng URL liên kết đã được ping (truyền tham chiếu).
	 * @param int      $post_id    ID bài viết.
	 */
	do_action_ref_array( 'pre_ping', array( &$post_links, &$pung, $post->ID ) );

	foreach ( (array) $post_links as $pagelinkedto ) {
		$pingback_server_url = discover_pingback_server_uri( $pagelinkedto );

		if ( $pingback_server_url ) {
			// Cho phép thêm 60 giây cho mỗi pingback để hoàn thành.
			if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 60 );
			}

			// Bây giờ, gọi RPC.
			$pagelinkedfrom = get_permalink( $post );

			// Sử dụng thời gian chờ 3 giây đủ để xử lý các máy chủ chậm.
			$client          = new WP_HTTP_IXR_Client( $pingback_server_url );
			$client->timeout = 3;
			/**
			 * Lọc user agent được gửi khi thực hiện pingback đến một URL.
			 *
			 * @since 2.9.0
			 *
			 * @param string $concat_useragent    User agent được nối với ' -- WordPress/'
			 *                                    và phiên bản WordPress.
			 * @param string $useragent           User agent.
			 * @param string $pingback_server_url URL máy chủ được liên kết đến.
			 * @param string $pagelinkedto        URL trang được liên kết đến.
			 * @param string $pagelinkedfrom      URL trang liên kết từ.
			 */
			$client->useragent = apply_filters( 'pingback_useragent', $client->useragent . ' -- WordPress/' . get_bloginfo( 'version' ), $client->useragent, $pingback_server_url, $pagelinkedto, $pagelinkedfrom );
			// Khi thiết lập thành true, sẽ tự xuất các thông báo debug.
			$client->debug = false;

			$status = $client->query( 'pingback.ping', $pagelinkedfrom, $pagelinkedto );

			if ( $status // Ping đã đăng ký.
				|| ( isset( $client->error->code ) && 48 === $client->error->code ) // Đã đăng ký trước đó.
			) {
				add_ping( $post, $pagelinkedto );
			}
			$ping_status[ $pagelinkedto ] = $status;
		}
	}

	return $ping_status;
}

/**
 * Kiểm tra xem blog có công khai hay không trước khi trả về các trang web.
 *
 * @since 2.1.0
 *
 * @param mixed $sites Sẽ trả về nếu blog là công khai, sẽ không trả về nếu không công khai.
 * @return mixed Chuỗi rỗng nếu blog không công khai, trả về $sites nếu trang web công khai.
 */
function privacy_ping_filter( $sites ) {
	if ( '0' !== get_option( 'blog_public' ) ) {
		return $sites;
	} else {
		return '';
	}
}

/**
 * Gửi Trackback.
 *
 * Cập nhật cơ sở dữ liệu khi gửi trackback để tránh trùng lặp.
 *
 * @since 0.71
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $trackback_url URL để gửi trackback.
 * @param string $title         Tiêu đề bài viết.
 * @param string $excerpt       Tóm tắt bài viết.
 * @param int    $post_id       ID bài viết.
 * @return int|false|void Truy vấn cơ sở dữ liệu từ cập nhật.
 */
function trackback( $trackback_url, $title, $excerpt, $post_id ) {
	global $wpdb;

	if ( empty( $trackback_url ) ) {
		return;
	}

	$options            = array();
	$options['timeout'] = 10;
	$options['body']    = array(
		'title'     => $title,
		'url'       => get_permalink( $post_id ),
		'blog_name' => get_option( 'blogname' ),
		'excerpt'   => $excerpt,
	);

	$response = wp_safe_remote_post( $trackback_url, $options );

	if ( is_wp_error( $response ) ) {
		return;
	}

	$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET pinged = CONCAT(pinged, '\n', %s) WHERE ID = %d", $trackback_url, $post_id ) );
	return $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET to_ping = TRIM(REPLACE(to_ping, %s, '')) WHERE ID = %d", $trackback_url, $post_id ) );
}

/**
 * Gửi một pingback.
 *
 * @since 1.2.0
 *
 * @param string $server Host của blog để kết nối đến.
 * @param string $path Đường dẫn để gửi ping.
 */
function weblog_ping( $server = '', $path = '' ) {
	require_once ABSPATH . WPINC . '/class-IXR.php';
	require_once ABSPATH . WPINC . '/class-wp-http-ixr-client.php';

	// Sử dụng thời gian chờ 3 giây đủ để xử lý các máy chủ chậm.
	$client             = new WP_HTTP_IXR_Client( $server, ( ( ! strlen( trim( $path ) ) || ( '/' === $path ) ) ? false : $path ) );
	$client->timeout    = 3;
	$client->useragent .= ' -- WordPress/' . get_bloginfo( 'version' );

	// Khi thiết lập thành true, sẽ tự xuất các thông báo debug.
	$client->debug = false;
	$home          = trailingslashit( home_url() );
	if ( ! $client->query( 'weblogUpdates.extendedPing', get_option( 'blogname' ), $home, get_bloginfo( 'rss2_url' ) ) ) { // Thử ping bình thường.
		$client->query( 'weblogUpdates.ping', get_option( 'blogname' ), $home );
	}
}

/**
 * Bộ lọc mặc định gắn vào pingback_ping_source_uri để xác thực URI nguồn của pingback.
 *
 * @since 3.5.1
 *
 * @see wp_http_validate_url()
 *
 * @param string $source_uri URI nguồn của pingback.
 * @return string URI nguồn đã xác thực.
 */
function pingback_ping_source_uri( $source_uri ) {
	return (string) wp_http_validate_url( $source_uri );
}

/**
 * Bộ lọc mặc định gắn vào xmlrpc_pingback_error.
 *
 * Trả về mã lỗi pingback chung trừ khi mã lỗi là 48,
 * nghĩa là pingback đã được đăng ký.
 *
 * @since 3.5.1
 *
 * @link https://www.hixie.ch/specs/pingback/pingback#TOC3
 *
 * @param IXR_Error $ixr_error Đối tượng lỗi IXR.
 * @return IXR_Error Đối tượng lỗi IXR.
 */
function xmlrpc_pingback_error( $ixr_error ) {
	if ( 48 === $ixr_error->code ) {
		return $ixr_error;
	}
	return new IXR_Error( 0, '' );
}

//
// Bộ nhớ đệm.
//

/**
 * Xóa bình luận khỏi bộ nhớ đệm đối tượng.
 *
 * @since 2.3.0
 *
 * @param int|array $ids ID bình luận hoặc mảng ID bình luận cần xóa khỏi bộ nhớ đệm.
 */
function clean_comment_cache( $ids ) {
	$comment_ids = (array) $ids;
	wp_cache_delete_multiple( $comment_ids, 'comment' );
	foreach ( $comment_ids as $id ) {
		/**
		 * Kích hoạt ngay sau khi bình luận bị xóa khỏi bộ nhớ đệm đối tượng.
		 *
		 * @since 4.5.0
		 *
		 * @param int $id ID bình luận.
		 */
		do_action( 'clean_comment_cache', $id );
	}

	wp_cache_set_comments_last_changed();
}

/**
 * Cập nhật bộ nhớ đệm bình luận cho các bình luận đã cho.
 *
 * Sẽ thêm các bình luận trong $comments vào bộ nhớ đệm. Nếu ID bình luận đã tồn tại
 * trong bộ nhớ đệm bình luận thì sẽ không được cập nhật. Bình luận được thêm vào
 * bộ nhớ đệm sử dụng nhóm comment với key là ID của bình luận.
 *
 * @since 2.3.0
 * @since 4.4.0 Giới thiệu tham số `$update_meta_cache`.
 *
 * @param WP_Comment[] $comments          Mảng đối tượng bình luận.
 * @param bool         $update_meta_cache Có cập nhật bộ nhớ đệm commentmeta hay không. Mặc định true.
 */
function update_comment_cache( $comments, $update_meta_cache = true ) {
	$data = array();
	foreach ( (array) $comments as $comment ) {
		$data[ $comment->comment_ID ] = $comment;
	}
	wp_cache_add_multiple( $data, 'comment' );

	if ( $update_meta_cache ) {
		// Tránh `wp_list_pluck()` trong trường hợp `$comments` được truyền tham chiếu.
		$comment_ids = array();
		foreach ( $comments as $comment ) {
			$comment_ids[] = $comment->comment_ID;
		}
		update_meta_cache( 'comment', $comment_ids );
	}
}

/**
 * Thêm bất kỳ bình luận nào từ các ID đã cho vào bộ nhớ đệm nếu chưa tồn tại trong bộ nhớ đệm.
 *
 * @since 4.4.0
 * @since 6.1.0 Hàm này không còn được đánh dấu là "private".
 * @since 6.3.0 Sử dụng wp_lazyload_comment_meta() để tải lười meta bình luận.
 *
 * @see update_comment_cache()
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int[] $comment_ids       Mảng ID bình luận.
 * @param bool  $update_meta_cache Tùy chọn. Có cập nhật bộ nhớ đệm meta hay không. Mặc định true.
 */
function _prime_comment_caches( $comment_ids, $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $comment_ids, 'comment' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_comments = $wpdb->get_results( sprintf( "SELECT $wpdb->comments.* FROM $wpdb->comments WHERE comment_ID IN (%s)", implode( ',', array_map( 'intval', $non_cached_ids ) ) ) );

		update_comment_cache( $fresh_comments, false );
	}

	if ( $update_meta_cache ) {
		wp_lazyload_comment_meta( $comment_ids );
	}
}

//
// Nội bộ.
//

/**
 * Đóng bình luận trên các bài viết cũ ngay lập tức, không cần truy vấn DB bổ sung. Gắn hook vào the_posts.
 *
 * @since 2.7.0
 * @access private
 *
 * @param WP_Post  $posts Đối tượng dữ liệu bài viết.
 * @param WP_Query $query Đối tượng truy vấn.
 * @return array
 */
function _close_comments_for_old_posts( $posts, $query ) {
	if ( empty( $posts ) || ! $query->is_singular() || ! get_option( 'close_comments_for_old_posts' ) ) {
		return $posts;
	}

	/**
	 * Lọc danh sách loại bài viết để tự động đóng bình luận.
	 *
	 * @since 3.2.0
	 *
	 * @param string[] $post_types Mảng tên loại bài viết.
	 */
	$post_types = apply_filters( 'close_comments_for_post_types', array( 'post' ) );
	if ( ! in_array( $posts[0]->post_type, $post_types, true ) ) {
		return $posts;
	}

	$days_old = (int) get_option( 'close_comments_days_old' );
	if ( ! $days_old ) {
		return $posts;
	}

	if ( time() - strtotime( $posts[0]->post_date_gmt ) > ( $days_old * DAY_IN_SECONDS ) ) {
		$posts[0]->comment_status = 'closed';
		$posts[0]->ping_status    = 'closed';
	}

	return $posts;
}

/**
 * Đóng bình luận trên bài viết cũ. Gắn hook vào comments_open và pings_open.
 *
 * @since 2.7.0
 * @access private
 *
 * @param bool $open    Bình luận mở hoặc đóng.
 * @param int  $post_id ID bài viết.
 * @return bool $open
 */
function _close_comments_for_old_post( $open, $post_id ) {
	if ( ! $open ) {
		return $open;
	}

	if ( ! get_option( 'close_comments_for_old_posts' ) ) {
		return $open;
	}

	$days_old = (int) get_option( 'close_comments_days_old' );
	if ( ! $days_old ) {
		return $open;
	}

	$post = get_post( $post_id );

	/** Bộ lọc này được ghi tài liệu trong wp-includes/comment.php */
	$post_types = apply_filters( 'close_comments_for_post_types', array( 'post' ) );
	if ( ! in_array( $post->post_type, $post_types, true ) ) {
		return $open;
	}

	// Bản nháp chưa đặt ngày không nên hiển thị là đã đóng bình luận.
	if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
		return $open;
	}

	if ( time() - strtotime( $post->post_date_gmt ) > ( $days_old * DAY_IN_SECONDS ) ) {
		return false;
	}

	return $open;
}

/**
 * Xử lý việc gửi bình luận, thường được đăng đến wp-comments-post.php qua biểu mẫu bình luận.
 *
 * Hàm này mong đợi dữ liệu chưa slash, khác với các hàm như `wp_new_comment()`
 * mong đợi dữ liệu đã slash.
 *
 * @since 4.4.0
 *
 * @param array $comment_data {
 *     Dữ liệu bình luận.
 *
 *     @type string|int $comment_post_ID             ID bài viết liên quan đến bình luận.
 *     @type string     $author                      Tên tác giả bình luận.
 *     @type string     $email                       Địa chỉ email tác giả bình luận.
 *     @type string     $url                         URL tác giả bình luận.
 *     @type string     $comment                     Nội dung bình luận.
 *     @type string|int $comment_parent              ID bình luận cha, nếu có. Mặc định 0.
 *     @type string     $_wp_unfiltered_html_comment Giá trị nonce để cho phép HTML chưa lọc.
 * }
 * @return WP_Comment|WP_Error Đối tượng WP_Comment khi thành công, đối tượng WP_Error khi thất bại.
 */
function wp_handle_comment_submission( $comment_data ) {
	$comment_post_id      = 0;
	$comment_author       = '';
	$comment_author_email = '';
	$comment_author_url   = '';
	$comment_content      = '';
	$comment_parent       = 0;
	$user_id              = 0;

	if ( isset( $comment_data['comment_post_ID'] ) ) {
		$comment_post_id = (int) $comment_data['comment_post_ID'];
	}
	if ( isset( $comment_data['author'] ) && is_string( $comment_data['author'] ) ) {
		$comment_author = trim( strip_tags( $comment_data['author'] ) );
	}
	if ( isset( $comment_data['email'] ) && is_string( $comment_data['email'] ) ) {
		$comment_author_email = trim( $comment_data['email'] );
	}
	if ( isset( $comment_data['url'] ) && is_string( $comment_data['url'] ) ) {
		$comment_author_url = trim( $comment_data['url'] );
	}
	if ( isset( $comment_data['comment'] ) && is_string( $comment_data['comment'] ) ) {
		$comment_content = trim( $comment_data['comment'] );
	}
	if ( isset( $comment_data['comment_parent'] ) ) {
		$comment_parent        = absint( $comment_data['comment_parent'] );
		$comment_parent_object = get_comment( $comment_parent );

		if (
			0 !== $comment_parent &&
			(
				! $comment_parent_object instanceof WP_Comment ||
				0 === (int) $comment_parent_object->comment_approved
			)
		) {
			/**
			 * Kích hoạt khi có nỗ lực trả lời bình luận chưa được phê duyệt.
			 *
			 * @since 6.2.0
			 *
			 * @param int $comment_post_id ID bài viết.
			 * @param int $comment_parent  ID bình luận cha.
			 */
			do_action( 'comment_reply_to_unapproved_comment', $comment_post_id, $comment_parent );

			return new WP_Error( 'comment_reply_to_unapproved_comment', __( 'Sorry, replies to unapproved comments are not allowed.' ), 403 );
		}
	}

	$post = get_post( $comment_post_id );

	if ( empty( $post->comment_status ) ) {

		/**
		 * Kích hoạt khi có nỗ lực bình luận trên bài viết không tồn tại.
		 *
		 * @since 1.5.0
		 *
		 * @param int $comment_post_id ID bài viết.
		 */
		do_action( 'comment_id_not_found', $comment_post_id );

		return new WP_Error( 'comment_id_not_found' );

	}

	// get_post_status() sẽ lấy trạng thái cha cho tệp đính kèm.
	$status = get_post_status( $post );

	if ( ( 'private' === $status ) && ! current_user_can( 'read_post', $comment_post_id ) ) {
		return new WP_Error( 'comment_id_not_found' );
	}

	$status_obj = get_post_status_object( $status );

	if ( ! comments_open( $comment_post_id ) ) {

		/**
		 * Kích hoạt khi có nỗ lực bình luận trên bài viết đã đóng bình luận.
		 *
		 * @since 1.5.0
		 *
		 * @param int $comment_post_id ID bài viết.
		 */
		do_action( 'comment_closed', $comment_post_id );

		return new WP_Error( 'comment_closed', __( 'Sorry, comments are closed for this item.' ), 403 );

	} elseif ( 'trash' === $status ) {

		/**
		 * Kích hoạt khi có nỗ lực bình luận trên bài viết đã bị xóa.
		 *
		 * @since 2.9.0
		 *
		 * @param int $comment_post_id ID bài viết.
		 */
		do_action( 'comment_on_trash', $comment_post_id );

		return new WP_Error( 'comment_on_trash' );

	} elseif ( ! $status_obj->public && ! $status_obj->private ) {

		/**
		 * Kích hoạt khi có nỗ lực bình luận trên bài viết đang ở chế độ bản nháp.
		 *
		 * @since 1.5.1
		 *
		 * @param int $comment_post_id ID bài viết.
		 */
		do_action( 'comment_on_draft', $comment_post_id );

		if ( current_user_can( 'read_post', $comment_post_id ) ) {
			return new WP_Error( 'comment_on_draft', __( 'Sorry, comments are not allowed for this item.' ), 403 );
		} else {
			return new WP_Error( 'comment_on_draft' );
		}
	} elseif ( post_password_required( $comment_post_id ) ) {

		/**
		 * Kích hoạt khi có nỗ lực bình luận trên bài viết được bảo vệ bằng mật khẩu.
		 *
		 * @since 2.9.0
		 *
		 * @param int $comment_post_id ID bài viết.
		 */
		do_action( 'comment_on_password_protected', $comment_post_id );

		return new WP_Error( 'comment_on_password_protected' );

	} else {
		/**
		 * Kích hoạt trước khi bình luận được đăng.
		 *
		 * @since 2.8.0
		 *
		 * @param int $comment_post_id ID bài viết.
		 */
		do_action( 'pre_comment_on_post', $comment_post_id );
	}

	// Nếu người dùng đã đăng nhập.
	$user = wp_get_current_user();
	if ( $user->exists() ) {
		if ( empty( $user->display_name ) ) {
			$user->display_name = $user->user_login;
		}

		$comment_author       = $user->display_name;
		$comment_author_email = $user->user_email;
		$comment_author_url   = $user->user_url;
		$user_id              = $user->ID;

		if ( current_user_can( 'unfiltered_html' ) ) {
			if ( ! isset( $comment_data['_wp_unfiltered_html_comment'] )
				|| ! wp_verify_nonce( $comment_data['_wp_unfiltered_html_comment'], 'unfiltered-html-comment_' . $comment_post_id )
			) {
				kses_remove_filters(); // Bắt đầu với trạng thái sạch.
				kses_init_filters();   // Thiết lập các bộ lọc.
				remove_filter( 'pre_comment_content', 'wp_filter_post_kses' );
				add_filter( 'pre_comment_content', 'wp_filter_kses' );
			}
		}
	} else {
		if ( get_option( 'comment_registration' ) ) {
			return new WP_Error( 'not_logged_in', __( 'Sorry, you must be logged in to comment.' ), 403 );
		}
	}

	$comment_type = 'comment';

	if ( get_option( 'require_name_email' ) && ! $user->exists() ) {
		if ( '' === $comment_author_email || '' === $comment_author ) {
			return new WP_Error( 'require_name_email', __( '<strong>Error:</strong> Please fill the required fields.' ), 200 );
		} elseif ( ! is_email( $comment_author_email ) ) {
			return new WP_Error( 'require_valid_email', __( '<strong>Error:</strong> Please enter a valid email address.' ), 200 );
		}
	}

	$commentdata = array(
		'comment_post_ID' => $comment_post_id,
	);

	$commentdata += compact(
		'comment_author',
		'comment_author_email',
		'comment_author_url',
		'comment_content',
		'comment_type',
		'comment_parent',
		'user_id'
	);

	/**
	 * Lọc liệu bình luận trống có nên được phép hay không.
	 *
	 * @since 5.1.0
	 *
	 * @param bool  $allow_empty_comment Có cho phép bình luận trống hay không. Mặc định false.
	 * @param array $commentdata         Mảng dữ liệu bình luận sẽ được gửi đến wp_insert_comment().
	 */
	$allow_empty_comment = apply_filters( 'allow_empty_comment', false, $commentdata );
	if ( '' === $comment_content && ! $allow_empty_comment ) {
		return new WP_Error( 'require_valid_comment', __( '<strong>Error:</strong> Please type your comment text.' ), 200 );
	}

	$check_max_lengths = wp_check_comment_data_max_lengths( $commentdata );
	if ( is_wp_error( $check_max_lengths ) ) {
		return $check_max_lengths;
	}

	$comment_id = wp_new_comment( wp_slash( $commentdata ), true );
	if ( is_wp_error( $comment_id ) ) {
		return $comment_id;
	}

	if ( ! $comment_id ) {
		return new WP_Error( 'comment_save_error', __( '<strong>Error:</strong> The comment could not be saved. Please try again later.' ), 500 );
	}

	return get_comment( $comment_id );
}

/**
 * Đăng ký trình xuất dữ liệu cá nhân cho bình luận.
 *
 * @since 4.9.6
 *
 * @param array[] $exporters Mảng các trình xuất dữ liệu cá nhân.
 * @return array[] Mảng các trình xuất dữ liệu cá nhân.
 */
function wp_register_comment_personal_data_exporter( $exporters ) {
	$exporters['wordpress-comments'] = array(
		'exporter_friendly_name' => __( 'WordPress Comments' ),
		'callback'               => 'wp_comments_personal_data_exporter',
	);

	return $exporters;
}

/**
 * Tìm và xuất dữ liệu cá nhân liên quan đến địa chỉ email từ bảng bình luận.
 *
 * @since 4.9.6
 *
 * @param string $email_address Địa chỉ email tác giả bình luận.
 * @param int    $page          Số trang bình luận.
 * @return array {
 *     Mảng dữ liệu cá nhân.
 *
 *     @type array[] $data Mảng các mảng dữ liệu cá nhân.
 *     @type bool    $done Liệu trình xuất đã hoàn thành hay chưa.
 * }
 */
function wp_comments_personal_data_exporter( $email_address, $page = 1 ) {
	// Giới hạn 500 bình luận mỗi lần để tránh hết thời gian chờ.
	$number = 500;
	$page   = (int) $page;

	$data_to_export = array();

	$comments = get_comments(
		array(
			'author_email'              => $email_address,
			'number'                    => $number,
			'paged'                     => $page,
			'orderby'                   => 'comment_ID',
			'order'                     => 'ASC',
			'update_comment_meta_cache' => false,
		)
	);

	$comment_prop_to_export = array(
		'comment_author'       => __( 'Comment Author' ),
		'comment_author_email' => __( 'Comment Author Email' ),
		'comment_author_url'   => __( 'Comment Author URL' ),
		'comment_author_IP'    => __( 'Comment Author IP' ),
		'comment_agent'        => __( 'Comment Author User Agent' ),
		'comment_date'         => __( 'Comment Date' ),
		'comment_content'      => __( 'Comment Content' ),
		'comment_link'         => __( 'Comment URL' ),
	);

	foreach ( (array) $comments as $comment ) {
		$comment_data_to_export = array();

		foreach ( $comment_prop_to_export as $key => $name ) {
			$value = '';

			switch ( $key ) {
				case 'comment_author':
				case 'comment_author_email':
				case 'comment_author_url':
				case 'comment_author_IP':
				case 'comment_agent':
				case 'comment_date':
					$value = $comment->{$key};
					break;

				case 'comment_content':
					$value = get_comment_text( $comment->comment_ID );
					break;

				case 'comment_link':
					$value = get_comment_link( $comment->comment_ID );
					$value = sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( $value ),
						esc_html( $value )
					);
					break;
			}

			if ( ! empty( $value ) ) {
				$comment_data_to_export[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		$data_to_export[] = array(
			'group_id'          => 'comments',
			'group_label'       => __( 'Comments' ),
			'group_description' => __( 'User&#8217;s comment data.' ),
			'item_id'           => "comment-{$comment->comment_ID}",
			'data'              => $comment_data_to_export,
		);
	}

	$done = count( $comments ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Đăng ký trình xóa dữ liệu cá nhân cho bình luận.
 *
 * @since 4.9.6
 *
 * @param array $erasers Mảng các trình xóa dữ liệu cá nhân.
 * @return array Mảng các trình xóa dữ liệu cá nhân.
 */
function wp_register_comment_personal_data_eraser( $erasers ) {
	$erasers['wordpress-comments'] = array(
		'eraser_friendly_name' => __( 'WordPress Comments' ),
		'callback'             => 'wp_comments_personal_data_eraser',
	);

	return $erasers;
}

/**
 * Xóa dữ liệu cá nhân liên quan đến địa chỉ email từ bảng bình luận.
 *
 * @since 4.9.6
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $email_address Địa chỉ email tác giả bình luận.
 * @param int    $page          Số trang bình luận.
 * @return array {
 *     Kết quả xóa dữ liệu.
 *
 *     @type bool     $items_removed  Liệu các mục đã thực sự được xóa hay chưa.
 *     @type bool     $items_retained Liệu các mục đã được giữ lại hay chưa.
 *     @type string[] $messages       Mảng thông báo để thêm vào tệp xuất dữ liệu cá nhân.
 *     @type bool     $done           Liệu trình xóa đã hoàn thành hay chưa.
 * }
 */
function wp_comments_personal_data_eraser( $email_address, $page = 1 ) {
	global $wpdb;

	if ( empty( $email_address ) ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	// Giới hạn 500 bình luận mỗi lần để tránh hết thời gian chờ.
	$number         = 500;
	$page           = (int) $page;
	$items_removed  = false;
	$items_retained = false;

	$comments = get_comments(
		array(
			'author_email'       => $email_address,
			'number'             => $number,
			'paged'              => $page,
			'orderby'            => 'comment_ID',
			'order'              => 'ASC',
			'include_unapproved' => true,
		)
	);

	/* translators: Name of a comment's author after being anonymized. */
	$anon_author = __( 'Anonymous' );
	$messages    = array();

	foreach ( (array) $comments as $comment ) {
		$anonymized_comment                         = array();
		$anonymized_comment['comment_agent']        = '';
		$anonymized_comment['comment_author']       = $anon_author;
		$anonymized_comment['comment_author_email'] = '';
		$anonymized_comment['comment_author_IP']    = wp_privacy_anonymize_data( 'ip', $comment->comment_author_IP );
		$anonymized_comment['comment_author_url']   = '';
		$anonymized_comment['user_id']              = 0;

		$comment_id = (int) $comment->comment_ID;

		/**
		 * Lọc liệu có ẩn danh bình luận hay không.
		 *
		 * @since 4.9.6
		 *
		 * @param bool|string $anon_message       Có áp dụng ẩn danh bình luận (bool) hay thông báo
		 *                                        tùy chỉnh (string). Mặc định true.
		 * @param WP_Comment  $comment            Đối tượng WP_Comment.
		 * @param array       $anonymized_comment Dữ liệu bình luận đã ẩn danh.
		 */
		$anon_message = apply_filters( 'wp_anonymize_comment', true, $comment, $anonymized_comment );

		if ( true !== $anon_message ) {
			if ( $anon_message && is_string( $anon_message ) ) {
				$messages[] = esc_html( $anon_message );
			} else {
				/* translators: %d: Comment ID. */
				$messages[] = sprintf( __( 'Comment %d contains personal data but could not be anonymized.' ), $comment_id );
			}

			$items_retained = true;

			continue;
		}

		$args = array(
			'comment_ID' => $comment_id,
		);

		$updated = $wpdb->update( $wpdb->comments, $anonymized_comment, $args );

		if ( $updated ) {
			$items_removed = true;
			clean_comment_cache( $comment_id );
		} else {
			$items_retained = true;
		}
	}

	$done = count( $comments ) < $number;

	return array(
		'items_removed'  => $items_removed,
		'items_retained' => $items_retained,
		'messages'       => $messages,
		'done'           => $done,
	);
}

/**
 * Thiết lập thời gian thay đổi cuối cùng cho nhóm bộ nhớ đệm 'comment'.
 *
 * @since 5.0.0
 */
function wp_cache_set_comments_last_changed() {
	wp_cache_set_last_changed( 'comment' );
}

/**
 * Cập nhật loại bình luận cho một loạt bình luận.
 *
 * @since 5.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 */
function _wp_batch_update_comment_type() {
	global $wpdb;

	$lock_name = 'update_comment_type.lock';

	// Thử khóa.
	$lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_name, time() ) );

	if ( ! $lock_result ) {
		$lock_result = get_option( $lock_name );

		// Thoát nếu không thể tạo khóa, hoặc nếu khóa hiện tại vẫn còn hiệu lực.
		if ( ! $lock_result || ( $lock_result > ( time() - HOUR_IN_SECONDS ) ) ) {
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'wp_update_comment_type_batch' );
			return;
		}
	}

	// Cập nhật khóa, vì đến thời điểm này chúng ta chắc chắn đã có khóa, chỉ cần kích hoạt các hành động.
	update_option( $lock_name, time() );

	// Kiểm tra xem vẫn còn loại bình luận trống hay không.
	$empty_comment_type = $wpdb->get_var(
		"SELECT comment_ID FROM $wpdb->comments
		WHERE comment_type = ''
		LIMIT 1"
	);

	// Không còn loại bình luận trống, đã hoàn thành.
	if ( ! $empty_comment_type ) {
		update_option( 'finished_updating_comment_type', true );
		delete_option( $lock_name );
		return;
	}

	// Tìm thấy loại bình luận trống? Chúng ta sẽ cần chạy lại script này.
	wp_schedule_single_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'wp_update_comment_type_batch' );

	/**
	 * Lọc kích thước lô bình luận để cập nhật loại bình luận.
	 *
	 * @since 5.5.0
	 *
	 * @param int $comment_batch_size Kích thước lô bình luận. Mặc định 100.
	 */
	$comment_batch_size = (int) apply_filters( 'wp_update_comment_type_batch_size', 100 );

	// Lấy ID các bình luận cần cập nhật.
	$comment_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT comment_ID
			FROM {$wpdb->comments}
			WHERE comment_type = ''
			ORDER BY comment_ID DESC
			LIMIT %d",
			$comment_batch_size
		)
	);

	if ( $comment_ids ) {
		$comment_id_list = implode( ',', $comment_ids );

		// Cập nhật giá trị trường `comment_type` thành `comment` cho loạt bình luận tiếp theo.
		$wpdb->query(
			"UPDATE {$wpdb->comments}
			SET comment_type = 'comment'
			WHERE comment_type = ''
			AND comment_ID IN ({$comment_id_list})" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		// Đảm bảo xóa bộ nhớ đệm bình luận.
		clean_comment_cache( $comment_ids );
	}

	delete_option( $lock_name );
}

/**
 * Để tránh công việc _wp_batch_update_comment_type() bị xóa nhầm,
 * kiểm tra rằng nó vẫn được lên lịch trong khi chúng ta chưa hoàn thành cập nhật loại bình luận.
 *
 * @ignore
 * @since 5.5.0
 */
function _wp_check_for_scheduled_update_comment_type() {
	if ( ! get_option( 'finished_updating_comment_type' ) && ! wp_next_scheduled( 'wp_update_comment_type_batch' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wp_update_comment_type_batch' );
	}
}
