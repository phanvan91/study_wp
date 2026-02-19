<?php
/**
 * Các hàm template bình luận
 *
 * Các hàm này được thiết kế để sử dụng bên trong vòng lặp WordPress.
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Lấy tên tác giả của bình luận hiện tại.
 *
 * Nếu bình luận có trường comment_author trống, thì sẽ giả định là người 'Ẩn danh'.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy tên tác giả.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Tên tác giả bình luận
 */
function get_comment_author( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	if ( ! empty( $comment->comment_ID ) ) {
		$comment_id = $comment->comment_ID;
	} elseif ( is_scalar( $comment_id ) ) {
		$comment_id = (string) $comment_id;
	} else {
		$comment_id = '0';
	}

	if ( empty( $comment->comment_author ) ) {
		$user = ! empty( $comment->user_id ) ? get_userdata( $comment->user_id ) : false;
		if ( $user ) {
			$comment_author = $user->display_name;
		} else {
			$comment_author = __( 'Anonymous' );
		}
	} else {
		$comment_author = $comment->comment_author;
	}

	/**
	 * Lọc tên tác giả bình luận được trả về.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_id` và `$comment`.
	 *
	 * @param string     $comment_author Tên người dùng của tác giả bình luận.
	 * @param string     $comment_id     ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment        Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_author', $comment_author, $comment_id, $comment );
}

/**
 * Hiển thị tên tác giả của bình luận hiện tại.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in tên tác giả.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_author( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	$comment_author = get_comment_author( $comment );

	/**
	 * Lọc tên tác giả bình luận để hiển thị.
	 *
	 * @since 1.2.0
	 * @since 4.1.0 Thêm tham số `$comment_id`.
	 *
	 * @param string $comment_author Tên người dùng của tác giả bình luận.
	 * @param string $comment_id     ID bình luận dưới dạng chuỗi số.
	 */
	echo apply_filters( 'comment_author', $comment_author, $comment->comment_ID );
}

/**
 * Lấy email của tác giả bình luận hiện tại.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy email tác giả.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Email của tác giả bình luận hiện tại
 */
function get_comment_author_email( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	/**
	 * Lọc địa chỉ email được trả về của tác giả bình luận.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_id` và `$comment`.
	 *
	 * @param string     $comment_author_email Địa chỉ email của tác giả bình luận.
	 * @param string     $comment_id           ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment              Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_author_email', $comment->comment_author_email, $comment->comment_ID, $comment );
}

/**
 * Hiển thị email của tác giả bình luận trong biến toàn cục $comment.
 *
 * Cần cẩn thận để bảo vệ địa chỉ email và đảm bảo rằng các chương trình
 * thu thập email không nắm bắt được địa chỉ email của người bình luận. Hầu hết
 * đều giả định rằng địa chỉ email của họ sẽ không xuất hiện ở dạng thô trên trang web.
 * Làm như vậy sẽ cho phép bất kỳ ai, kể cả những người mà người dùng không muốn,
 * lấy được địa chỉ email và sử dụng cho mục đích riêng, tốt hay xấu.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in email tác giả.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_author_email( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	$comment_author_email = get_comment_author_email( $comment );

	/**
	 * Lọc email của tác giả bình luận để hiển thị.
	 *
	 * @since 1.2.0
	 * @since 4.1.0 Thêm tham số `$comment_id`.
	 *
	 * @param string $comment_author_email Địa chỉ email của tác giả bình luận.
	 * @param string $comment_id           ID bình luận dưới dạng chuỗi số.
	 */
	echo apply_filters( 'author_email', $comment_author_email, $comment->comment_ID );
}

/**
 * Hiển thị liên kết email HTML đến tác giả của bình luận hiện tại.
 *
 * Cần cẩn thận để bảo vệ địa chỉ email và đảm bảo rằng các chương trình
 * thu thập email không nắm bắt được địa chỉ email của người bình luận. Hầu hết
 * đều giả định rằng địa chỉ email của họ sẽ không xuất hiện ở dạng thô trên trang web.
 * Làm như vậy sẽ cho phép bất kỳ ai, kể cả những người mà người dùng không muốn,
 * lấy được địa chỉ email và sử dụng cho mục đích riêng, tốt hay xấu.
 *
 * @since 0.71
 * @since 4.6.0 Thêm tham số `$comment`.
 *
 * @param string         $link_text Tùy chọn. Văn bản hiển thị thay vì địa chỉ email của tác giả bình luận.
 *                                  Mặc định rỗng.
 * @param string         $before    Tùy chọn. Văn bản hoặc HTML hiển thị trước liên kết email. Mặc định rỗng.
 * @param string         $after     Tùy chọn. Văn bản hoặc HTML hiển thị sau liên kết email. Mặc định rỗng.
 * @param int|WP_Comment $comment   Tùy chọn. ID bình luận hoặc đối tượng WP_Comment. Mặc định là bình luận hiện tại.
 */
function comment_author_email_link( $link_text = '', $before = '', $after = '', $comment = null ) {
	$link = get_comment_author_email_link( $link_text, $before, $after, $comment );
	if ( $link ) {
		echo $link;
	}
}

/**
 * Trả về liên kết email HTML đến tác giả của bình luận hiện tại.
 *
 * Cần cẩn thận để bảo vệ địa chỉ email và đảm bảo rằng các chương trình
 * thu thập email không nắm bắt được địa chỉ email của người bình luận. Hầu hết
 * đều giả định rằng địa chỉ email của họ sẽ không xuất hiện ở dạng thô trên trang web.
 * Làm như vậy sẽ cho phép bất kỳ ai, kể cả những người mà người dùng không muốn,
 * lấy được địa chỉ email và sử dụng cho mục đích riêng, tốt hay xấu.
 *
 * @since 2.7.0
 * @since 4.6.0 Thêm tham số `$comment`.
 *
 * @param string         $link_text Tùy chọn. Văn bản hiển thị thay vì địa chỉ email của tác giả bình luận.
 *                                  Mặc định rỗng.
 * @param string         $before    Tùy chọn. Văn bản hoặc HTML hiển thị trước liên kết email. Mặc định rỗng.
 * @param string         $after     Tùy chọn. Văn bản hoặc HTML hiển thị sau liên kết email. Mặc định rỗng.
 * @param int|WP_Comment $comment   Tùy chọn. ID bình luận hoặc đối tượng WP_Comment. Mặc định là bình luận hiện tại.
 * @return string Markup HTML cho liên kết email tác giả bình luận. Mặc định, địa chỉ email được
 *                làm rối qua bộ lọc {@see 'comment_email'} với antispambot().
 */
function get_comment_author_email_link( $link_text = '', $before = '', $after = '', $comment = null ) {
	$comment = get_comment( $comment );

	/**
	 * Lọc email của tác giả bình luận để hiển thị.
	 *
	 * Cần cẩn thận để bảo vệ địa chỉ email và đảm bảo rằng các chương trình
	 * thu thập email không nắm bắt được địa chỉ email của người bình luận.
	 *
	 * @since 1.2.0
	 * @since 4.1.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_author_email Địa chỉ email của tác giả bình luận.
	 * @param WP_Comment $comment              Đối tượng bình luận.
	 */
	$comment_author_email = apply_filters( 'comment_email', $comment->comment_author_email, $comment );

	if ( ( ! empty( $comment_author_email ) ) && ( '@' !== $comment_author_email ) ) {
		$display = ( '' !== $link_text ) ? $link_text : $comment_author_email;

		$comment_author_email_link = $before . sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( 'mailto:' . $comment_author_email ),
			esc_html( $display )
		) . $after;

		return $comment_author_email_link;
	} else {
		return '';
	}
}

/**
 * Lấy liên kết HTML đến URL của tác giả bình luận hiện tại.
 *
 * Cả get_comment_author_url() và get_comment_author() đều dựa vào get_comment(),
 * sẽ quay lại sử dụng biến bình luận toàn cục nếu tham số $comment_id trống.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy liên kết tác giả.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Tên tác giả bình luận hoặc liên kết HTML đến URL của tác giả.
 */
function get_comment_author_link( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	if ( ! empty( $comment->comment_ID ) ) {
		$comment_id = $comment->comment_ID;
	} elseif ( is_scalar( $comment_id ) ) {
		$comment_id = (string) $comment_id;
	} else {
		$comment_id = '0';
	}

	$comment_author_url = get_comment_author_url( $comment );
	$comment_author     = get_comment_author( $comment );

	if ( empty( $comment_author_url ) || 'http://' === $comment_author_url ) {
		$comment_author_link = $comment_author;
	} else {
		$rel_parts = array( 'ugc' );
		if ( ! wp_is_internal_link( $comment_author_url ) ) {
			$rel_parts = array_merge(
				$rel_parts,
				array( 'external', 'nofollow' )
			);
		}

		/**
		 * Lọc các thuộc tính rel của liên kết tác giả bình luận.
		 *
		 * @since 6.2.0
		 *
		 * @param string[]   $rel_parts Mảng các chuỗi đại diện cho các thẻ rel
		 *                              sẽ được nối thành thuộc tính rel của thẻ anchor.
		 * @param WP_Comment $comment   Đối tượng bình luận.
		 */
		$rel_parts = apply_filters( 'comment_author_link_rel', $rel_parts, $comment );

		$rel = implode( ' ', $rel_parts );
		$rel = esc_attr( $rel );
		// Khoảng trắng trước 'rel' là cần thiết cho sprintf() phía sau.
		$rel = ! empty( $rel ) ? sprintf( ' rel="%s"', $rel ) : '';

		$comment_author_link = sprintf(
			'<a href="%1$s" class="url"%2$s>%3$s</a>',
			$comment_author_url,
			$rel,
			$comment_author
		);
	}

	/**
	 * Lọc liên kết tác giả bình luận để hiển thị.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_author` và `$comment_id`.
	 *
	 * @param string $comment_author_link Liên kết tác giả bình luận được định dạng HTML.
	 *                                    Rỗng nếu URL không hợp lệ.
	 * @param string $comment_author      Tên người dùng của tác giả bình luận.
	 * @param string $comment_id          ID bình luận dưới dạng chuỗi số.
	 */
	return apply_filters( 'get_comment_author_link', $comment_author_link, $comment_author, $comment_id );
}

/**
 * Hiển thị liên kết HTML đến URL của tác giả bình luận hiện tại.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in liên kết tác giả.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_author_link( $comment_id = 0 ) {
	echo get_comment_author_link( $comment_id );
}

/**
 * Lấy địa chỉ IP của tác giả bình luận hiện tại.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy địa chỉ IP tác giả.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Địa chỉ IP của tác giả bình luận, hoặc chuỗi rỗng nếu không có sẵn.
 */
function get_comment_author_IP( $comment_id = 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$comment = get_comment( $comment_id );

	/**
	 * Lọc địa chỉ IP được trả về của tác giả bình luận.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_id` và `$comment`.
	 *
	 * @param string     $comment_author_ip Địa chỉ IP của tác giả bình luận, hoặc chuỗi rỗng nếu không có sẵn.
	 * @param string     $comment_id        ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment           Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_author_IP', $comment->comment_author_IP, $comment->comment_ID, $comment );  // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
}

/**
 * Hiển thị địa chỉ IP của tác giả bình luận hiện tại.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in địa chỉ IP tác giả.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_author_IP( $comment_id = 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	echo esc_html( get_comment_author_IP( $comment_id ) );
}

/**
 * Lấy URL của tác giả bình luận hiện tại, không có liên kết.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy URL tác giả.
 *                                   Mặc định là bình luận hiện tại.
 * @return string URL tác giả bình luận, nếu được cung cấp, ngược lại là chuỗi rỗng.
 */
function get_comment_author_url( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	$comment_author_url = '';
	$comment_id         = 0;

	if ( ! empty( $comment ) ) {
		$comment_author_url = ( 'http://' === $comment->comment_author_url ) ? '' : $comment->comment_author_url;
		$comment_author_url = esc_url( $comment_author_url, array( 'http', 'https' ) );

		$comment_id = $comment->comment_ID;
	}

	/**
	 * Lọc URL của tác giả bình luận.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_id` và `$comment`.
	 *
	 * @param string          $comment_author_url URL của tác giả bình luận, hoặc chuỗi rỗng.
	 * @param string|int      $comment_id         ID bình luận dưới dạng chuỗi số, hoặc 0 nếu không tìm thấy.
	 * @param WP_Comment|null $comment            Đối tượng bình luận, hoặc null nếu không tìm thấy.
	 */
	return apply_filters( 'get_comment_author_url', $comment_author_url, $comment_id, $comment );
}

/**
 * Hiển thị URL của tác giả bình luận hiện tại, không có liên kết.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in URL tác giả.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_author_url( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	$comment_author_url = get_comment_author_url( $comment );

	/**
	 * Lọc URL của tác giả bình luận để hiển thị.
	 *
	 * @since 1.2.0
	 * @since 4.1.0 Thêm tham số `$comment_id`.
	 *
	 * @param string $comment_author_url URL của tác giả bình luận.
	 * @param string $comment_id         ID bình luận dưới dạng chuỗi số.
	 */
	echo apply_filters( 'comment_url', $comment_author_url, $comment->comment_ID );
}

/**
 * Lấy liên kết HTML của URL tác giả bình luận hiện tại.
 *
 * Tham số $link_text chỉ được sử dụng nếu URL không tồn tại cho tác giả
 * bình luận. Nếu URL tồn tại thì URL sẽ được sử dụng và $link_text
 * sẽ bị bỏ qua.
 *
 * Đóng gói liên kết HTML giữa $before và $after. Vì vậy nó sẽ xuất hiện
 * theo thứ tự $before, liên kết, và cuối cùng $after.
 *
 * @since 1.5.0
 * @since 4.6.0 Thêm tham số `$comment`.
 *
 * @param string         $link_text Tùy chọn. Văn bản hiển thị thay vì địa chỉ email
 *                                  của tác giả bình luận. Mặc định rỗng.
 * @param string         $before    Tùy chọn. Văn bản hoặc HTML hiển thị trước liên kết email.
 *                                  Mặc định rỗng.
 * @param string         $after     Tùy chọn. Văn bản hoặc HTML hiển thị sau liên kết email.
 *                                  Mặc định rỗng.
 * @param int|WP_Comment $comment   Tùy chọn. ID bình luận hoặc đối tượng WP_Comment.
 *                                  Mặc định là bình luận hiện tại.
 * @return string Liên kết HTML nằm giữa các tham số $before và $after.
 */
function get_comment_author_url_link( $link_text = '', $before = '', $after = '', $comment = 0 ) {
	$comment_author_url = get_comment_author_url( $comment );

	$display = ( '' !== $link_text ) ? $link_text : $comment_author_url;
	$display = str_replace( 'http://www.', '', $display );
	$display = str_replace( 'http://', '', $display );

	if ( str_ends_with( $display, '/' ) ) {
		$display = substr( $display, 0, -1 );
	}

	$comment_author_url_link = $before . sprintf(
		'<a href="%1$s" rel="external">%2$s</a>',
		$comment_author_url,
		$display
	) . $after;

	/**
	 * Lọc liên kết URL được trả về của tác giả bình luận.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_author_url_link Liên kết URL tác giả bình luận được định dạng HTML.
	 */
	return apply_filters( 'get_comment_author_url_link', $comment_author_url_link );
}

/**
 * Hiển thị liên kết HTML của URL tác giả bình luận hiện tại.
 *
 * @since 0.71
 * @since 4.6.0 Thêm tham số `$comment`.
 *
 * @param string         $link_text Tùy chọn. Văn bản hiển thị thay vì địa chỉ email
 *                                  của tác giả bình luận. Mặc định rỗng.
 * @param string         $before    Tùy chọn. Văn bản hoặc HTML hiển thị trước liên kết email.
 *                                  Mặc định rỗng.
 * @param string         $after     Tùy chọn. Văn bản hoặc HTML hiển thị sau liên kết email.
 *                                  Mặc định rỗng.
 * @param int|WP_Comment $comment   Tùy chọn. ID bình luận hoặc đối tượng WP_Comment.
 *                                  Mặc định là bình luận hiện tại.
 */
function comment_author_url_link( $link_text = '', $before = '', $after = '', $comment = 0 ) {
	echo get_comment_author_url_link( $link_text, $before, $after, $comment );
}

/**
 * Tạo các class ngữ nghĩa cho mỗi phần tử bình luận.
 *
 * @since 2.7.0
 * @since 4.4.0 Thêm khả năng cho `$comment` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param string|string[] $css_class Tùy chọn. Một hoặc nhiều class để thêm vào danh sách class.
 *                                   Mặc định rỗng.
 * @param int|WP_Comment  $comment   Tùy chọn. ID bình luận hoặc đối tượng WP_Comment. Mặc định bình luận hiện tại.
 * @param int|WP_Post     $post      Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @param bool            $display   Tùy chọn. In hay trả về kết quả đầu ra.
 *                                   Mặc định true.
 * @return void|string Void nếu tham số `$display` là true, các class bình luận nếu `$display` là false.
 */
function comment_class( $css_class = '', $comment = null, $post = null, $display = true ) {
	// Phân cách các class bằng một khoảng trắng, gom các class cho DIV bình luận.
	$css_class = 'class="' . implode( ' ', get_comment_class( $css_class, $comment, $post ) ) . '"';

	if ( $display ) {
		echo $css_class;
	} else {
		return $css_class;
	}
}

/**
 * Trả về các class cho div bình luận dưới dạng mảng.
 *
 * @since 2.7.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @global int $comment_alt
 * @global int $comment_depth
 * @global int $comment_thread_alt
 *
 * @param string|string[] $css_class  Tùy chọn. Một hoặc nhiều class để thêm vào danh sách class.
 *                                    Mặc định rỗng.
 * @param int|WP_Comment  $comment_id Tùy chọn. ID bình luận hoặc đối tượng WP_Comment. Mặc định bình luận hiện tại.
 * @param int|WP_Post     $post       Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @return string[] Mảng các class.
 */
function get_comment_class( $css_class = '', $comment_id = null, $post = null ) {
	global $comment_alt, $comment_depth, $comment_thread_alt;

	$classes = array();

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return $classes;
	}

	// Lấy loại bình luận (comment, trackback).
	$classes[] = ( empty( $comment->comment_type ) ) ? 'comment' : $comment->comment_type;

	// Thêm class cho tác giả bình luận là người dùng đã đăng ký.
	$user = $comment->user_id ? get_userdata( $comment->user_id ) : false;
	if ( $user ) {
		$classes[] = 'byuser';
		$classes[] = 'comment-author-' . sanitize_html_class( $user->user_nicename, $comment->user_id );
		// Cho tác giả bình luận cũng là tác giả của bài viết.
		$_post = get_post( $post );
		if ( $_post ) {
			if ( $comment->user_id === $_post->post_author ) {
				$classes[] = 'bypostauthor';
			}
		}
	}

	if ( empty( $comment_alt ) ) {
		$comment_alt = 0;
	}
	if ( empty( $comment_depth ) ) {
		$comment_depth = 1;
	}
	if ( empty( $comment_thread_alt ) ) {
		$comment_thread_alt = 0;
	}

	if ( $comment_alt % 2 ) {
		$classes[] = 'odd';
		$classes[] = 'alt';
	} else {
		$classes[] = 'even';
	}

	++$comment_alt;

	// Alt cho bình luận cấp cao nhất.
	if ( 1 === $comment_depth ) {
		if ( $comment_thread_alt % 2 ) {
			$classes[] = 'thread-odd';
			$classes[] = 'thread-alt';
		} else {
			$classes[] = 'thread-even';
		}
		++$comment_thread_alt;
	}

	$classes[] = "depth-$comment_depth";

	if ( ! empty( $css_class ) ) {
		if ( ! is_array( $css_class ) ) {
			$css_class = preg_split( '#\s+#', $css_class );
		}
		$classes = array_merge( $classes, $css_class );
	}

	$classes = array_map( 'esc_attr', $classes );

	/**
	 * Lọc các class CSS được trả về cho bình luận hiện tại.
	 *
	 * @since 2.7.0
	 *
	 * @param string[]    $classes    Mảng các class bình luận.
	 * @param string[]    $css_class  Mảng các class bổ sung được thêm vào danh sách.
	 * @param string      $comment_id ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment  $comment    Đối tượng bình luận.
	 * @param int|WP_Post $post       ID bài viết hoặc đối tượng WP_Post.
	 */
	return apply_filters( 'comment_class', $classes, $css_class, $comment->comment_ID, $comment, $post );
}

/**
 * Lấy ngày bình luận của bình luận hiện tại.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param string         $format     Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'date_format'.
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy ngày.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Ngày của bình luận.
 */
function get_comment_date( $format = '', $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	$_format = ! empty( $format ) ? $format : get_option( 'date_format' );

	$comment_date = mysql2date( $_format, $comment->comment_date );

	/**
	 * Lọc ngày bình luận được trả về.
	 *
	 * @since 1.5.0
	 *
	 * @param string|int $comment_date Chuỗi ngày đã định dạng hoặc timestamp Unix.
	 * @param string     $format       Định dạng ngày PHP.
	 * @param WP_Comment $comment      Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_date', $comment_date, $format, $comment );
}

/**
 * Hiển thị ngày bình luận của bình luận hiện tại.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param string         $format     Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'date_format'.
 * @param int|WP_Comment $comment_id WP_Comment hoặc ID của bình luận cần in ngày.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_date( $format = '', $comment_id = 0 ) {
	echo get_comment_date( $format, $comment_id );
}

/**
 * Lấy đoạn trích của bình luận đã cho.
 *
 * Trả về tối đa 20 từ với dấu ba chấm được thêm vào nếu cần.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy đoạn trích.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Đoạn trích bình luận có thể bị cắt ngắn.
 */
function get_comment_excerpt( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	if ( ! post_password_required( $comment->comment_post_ID ) ) {
		$comment_text = strip_tags( str_replace( array( "\n", "\r" ), ' ', $comment->comment_content ) );
	} else {
		$comment_text = __( 'Password protected' );
	}

	/* translators: Maximum number of words used in a comment excerpt. */
	$comment_excerpt_length = (int) _x( '20', 'comment_excerpt_length' );

	/**
	 * Lọc số từ tối đa được sử dụng trong đoạn trích bình luận.
	 *
	 * @since 4.4.0
	 *
	 * @param int $comment_excerpt_length Số từ bạn muốn hiển thị trong đoạn trích bình luận.
	 */
	$comment_excerpt_length = apply_filters( 'comment_excerpt_length', $comment_excerpt_length );

	$comment_excerpt = wp_trim_words( $comment_text, $comment_excerpt_length, '&hellip;' );

	/**
	 * Lọc đoạn trích bình luận được trả về.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_id` và `$comment`.
	 *
	 * @param string     $comment_excerpt Văn bản đoạn trích bình luận.
	 * @param string     $comment_id      ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment         Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_excerpt', $comment_excerpt, $comment->comment_ID, $comment );
}

/**
 * Hiển thị đoạn trích của bình luận hiện tại.
 *
 * @since 1.2.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in đoạn trích.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_excerpt( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	$comment_excerpt = get_comment_excerpt( $comment );

	/**
	 * Lọc đoạn trích bình luận để hiển thị.
	 *
	 * @since 1.2.0
	 * @since 4.1.0 Thêm tham số `$comment_id`.
	 *
	 * @param string $comment_excerpt Văn bản đoạn trích bình luận.
	 * @param string $comment_id      ID bình luận dưới dạng chuỗi số.
	 */
	echo apply_filters( 'comment_excerpt', $comment_excerpt, $comment->comment_ID );
}

/**
 * Lấy ID bình luận của bình luận hiện tại.
 *
 * @since 1.5.0
 *
 * @return string ID bình luận dưới dạng chuỗi số.
 */
function get_comment_ID() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$comment = get_comment();

	$comment_id = ! empty( $comment->comment_ID ) ? $comment->comment_ID : '0';

	/**
	 * Lọc ID bình luận được trả về.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment`.
	 *
	 * @param string     $comment_id ID bình luận hiện tại dưới dạng chuỗi số.
	 * @param WP_Comment $comment    Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_ID', $comment_id, $comment );  // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
}

/**
 * Hiển thị ID bình luận của bình luận hiện tại.
 *
 * @since 0.71
 */
function comment_ID() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	echo get_comment_ID();
}

/**
 * Lấy liên kết đến bình luận đã cho.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment` cũng chấp nhận đối tượng WP_Comment. Thêm tham số `$cpage`.
 *
 * @see get_page_of_comment()
 *
 * @global WP_Rewrite $wp_rewrite      Thành phần rewrite của WordPress.
 * @global bool       $in_comment_loop
 *
 * @param WP_Comment|int|null $comment Tùy chọn. Bình luận cần lấy. Mặc định là bình luận hiện tại.
 * @param array               $args {
 *     Mảng các tham số tùy chọn để ghi đè giá trị mặc định.
 *
 *     @type string     $type      Truyền cho get_page_of_comment().
 *     @type int        $page      Trang bình luận hiện tại, để tính phân trang bình luận.
 *     @type int        $per_page  Giá trị số bình luận mỗi trang cho phân trang.
 *     @type int        $max_depth Truyền cho get_page_of_comment().
 *     @type int|string $cpage     Giá trị sử dụng cho "comment-page" hoặc "cpage" của bình luận.
 *                                 Nếu được cung cấp, giá trị này sẽ ghi đè bất kỳ giá trị nào
 *                                 được tính từ `$page` và `$per_page`.
 * }
 * @return string Đường dẫn cố định đến bình luận đã cho.
 */
function get_comment_link( $comment = null, $args = array() ) {
	global $wp_rewrite, $in_comment_loop;

	$comment = get_comment( $comment );

	// Tương thích ngược.
	if ( ! is_array( $args ) ) {
		$args = array( 'page' => $args );
	}

	$defaults = array(
		'type'      => 'all',
		'page'      => '',
		'per_page'  => '',
		'max_depth' => '',
		'cpage'     => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$comment_link = get_permalink( $comment->comment_post_ID );

	// Tham số 'cpage' được ưu tiên.
	if ( ! is_null( $args['cpage'] ) ) {
		$cpage = $args['cpage'];

		// Không có 'cpage' được cung cấp, nên chúng ta tính toán một giá trị.
	} else {
		if ( '' === $args['per_page'] && get_option( 'page_comments' ) ) {
			$args['per_page'] = get_option( 'comments_per_page' );
		}

		if ( empty( $args['per_page'] ) ) {
			$args['per_page'] = 0;
			$args['page']     = 0;
		}

		$cpage = $args['page'];

		if ( '' === $cpage ) {
			if ( ! empty( $in_comment_loop ) ) {
				$cpage = (int) get_query_var( 'cpage' );
			} else {
				// Cần truy vấn cơ sở dữ liệu, nên chỉ thực hiện khi không thể xác định từ ngữ cảnh.
				$cpage = get_page_of_comment( $comment->comment_ID, $args );
			}
		}

		/*
		 * Nếu trang mặc định hiển thị các bình luận cũ nhất, đường dẫn cố định cho các bình luận
		 * trên trang mặc định không cần biến truy vấn 'cpage'.
		 */
		if ( 'oldest' === get_option( 'default_comments_page' ) && 1 === $cpage ) {
			$cpage = '';
		}
	}

	if ( $cpage && get_option( 'page_comments' ) ) {
		if ( $wp_rewrite->using_permalinks() ) {
			if ( $cpage ) {
				$comment_link = trailingslashit( $comment_link ) . $wp_rewrite->comments_pagination_base . '-' . $cpage;
			}

			$comment_link = user_trailingslashit( $comment_link, 'comment' );
		} elseif ( $cpage ) {
			$comment_link = add_query_arg( 'cpage', $cpage, $comment_link );
		}
	}

	if ( $wp_rewrite->using_permalinks() ) {
		$comment_link = user_trailingslashit( $comment_link, 'comment' );
	}

	$comment_link = $comment_link . '#comment-' . $comment->comment_ID;

	/**
	 * Lọc đường dẫn cố định của bình luận đơn lẻ được trả về.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Thêm tham số `$cpage`.
	 *
	 * @see get_page_of_comment()
	 *
	 * @param string     $comment_link Đường dẫn cố định bình luận với '#comment-$id' được nối thêm.
	 * @param WP_Comment $comment      Đối tượng bình luận hiện tại.
	 * @param array      $args         Mảng các tham số để ghi đè giá trị mặc định.
	 * @param int        $cpage        Giá trị 'cpage' đã tính toán.
	 */
	return apply_filters( 'get_comment_link', $comment_link, $comment, $args, $cpage );
}

/**
 * Lấy liên kết đến các bình luận của bài viết hiện tại.
 *
 * @since 1.5.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục $post.
 * @return string Liên kết đến các bình luận.
 */
function get_comments_link( $post = 0 ) {
	$hash          = get_comments_number( $post ) ? '#comments' : '#respond';
	$comments_link = get_permalink( $post ) . $hash;

	/**
	 * Lọc đường dẫn cố định của bình luận bài viết được trả về.
	 *
	 * @since 3.6.0
	 *
	 * @param string      $comments_link Đường dẫn cố định bình luận bài viết với '#comments' được nối thêm.
	 * @param int|WP_Post $post          ID bài viết hoặc đối tượng WP_Post.
	 */
	return apply_filters( 'get_comments_link', $comments_link, $post );
}

/**
 * Hiển thị liên kết đến các bình luận của bài viết hiện tại.
 *
 * @since 0.71
 *
 * @param string $deprecated   Không sử dụng.
 * @param string $deprecated_2 Không sử dụng.
 */
function comments_link( $deprecated = '', $deprecated_2 = '' ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '0.72' );
	}
	if ( ! empty( $deprecated_2 ) ) {
		_deprecated_argument( __FUNCTION__, '1.3.0' );
	}
	echo esc_url( get_comments_link() );
}

/**
 * Lấy số lượng bình luận mà một bài viết có.
 *
 * @since 1.5.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục `$post`.
 * @return string|int Nếu bài viết tồn tại, chuỗi số đại diện cho số bình luận
 *                    mà bài viết có, ngược lại là 0.
 */
function get_comments_number( $post = 0 ) {
	$post = get_post( $post );

	$comments_number = $post ? $post->comment_count : 0;
	$post_id         = $post ? $post->ID : 0;

	/**
	 * Lọc số bình luận được trả về cho một bài viết.
	 *
	 * @since 1.5.0
	 *
	 * @param string|int $comments_number Chuỗi đại diện cho số bình luận mà bài viết có, ngược lại là 0.
	 * @param int        $post_id ID bài viết.
	 */
	return apply_filters( 'get_comments_number', $comments_number, $post_id );
}

/**
 * Hiển thị chuỗi ngôn ngữ cho số bình luận mà bài viết hiện tại có.
 *
 * @since 0.71
 * @since 5.4.0 Tham số `$deprecated` được đổi thành `$post`.
 *
 * @param string|false $zero Tùy chọn. Văn bản khi không có bình luận. Mặc định false.
 * @param string|false $one  Tùy chọn. Văn bản khi có một bình luận. Mặc định false.
 * @param string|false $more Tùy chọn. Văn bản khi có nhiều hơn một bình luận. Mặc định false.
 * @param int|WP_Post  $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục `$post`.
 */
function comments_number( $zero = false, $one = false, $more = false, $post = 0 ) {
	echo get_comments_number_text( $zero, $one, $more, $post );
}

/**
 * Hiển thị chuỗi ngôn ngữ cho số bình luận mà bài viết hiện tại có.
 *
 * @since 4.0.0
 * @since 5.4.0 Thêm tham số `$post` để cho phép sử dụng hàm ngoài vòng lặp.
 *
 * @param string      $zero Tùy chọn. Văn bản khi không có bình luận. Mặc định false.
 * @param string      $one  Tùy chọn. Văn bản khi có một bình luận. Mặc định false.
 * @param string      $more Tùy chọn. Văn bản khi có nhiều hơn một bình luận. Mặc định false.
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục `$post`.
 * @return string Chuỗi ngôn ngữ cho số bình luận mà bài viết có.
 */
function get_comments_number_text( $zero = false, $one = false, $more = false, $post = 0 ) {
	$comments_number = (int) get_comments_number( $post );

	if ( $comments_number > 1 ) {
		if ( false === $more ) {
			$comments_number_text = sprintf(
				/* translators: %s: Number of comments. */
				_n( '%s Comment', '%s Comments', $comments_number ),
				number_format_i18n( $comments_number )
			);
		} else {
			// % Comments
			/*
			 * translators: If comment number in your language requires declension,
			 * translate this to 'on'. Do not translate into your own language.
			 */
			if ( 'on' === _x( 'off', 'Comment number declension: on or off' ) ) {
				$text = preg_replace( '#<span class="screen-reader-text">.+?</span>#', '', $more );
				$text = preg_replace( '/&.+?;/', '', $text ); // Remove HTML entities.
				$text = trim( strip_tags( $text ), '% ' );

				// Replace '% Comments' with a proper plural form.
				if ( $text && ! preg_match( '/[0-9]+/', $text ) && str_contains( $more, '%' ) ) {
					/* translators: %s: Number of comments. */
					$new_text = _n( '%s Comment', '%s Comments', $comments_number );
					$new_text = trim( sprintf( $new_text, '' ) );

					$more = str_replace( $text, $new_text, $more );
					if ( ! str_contains( $more, '%' ) ) {
						$more = '% ' . $more;
					}
				}
			}

			$comments_number_text = str_replace( '%', number_format_i18n( $comments_number ), $more );
		}
	} elseif ( 0 === $comments_number ) {
		$comments_number_text = ( false === $zero ) ? __( 'No Comments' ) : $zero;
	} else { // Phải là một.
		$comments_number_text = ( false === $one ) ? __( '1 Comment' ) : $one;
	}

	/**
	 * Lọc số bình luận để hiển thị.
	 *
	 * @since 1.5.0
	 *
	 * @see _n()
	 *
	 * @param string $comments_number_text Chuỗi có thể dịch được, được định dạng dựa trên số lượng
	 *                                     bằng 0, 1, hoặc hơn 1.
	 * @param int    $comments_number      Số bình luận của bài viết.
	 */
	return apply_filters( 'comments_number', $comments_number_text, $comments_number );
}

/**
 * Lấy nội dung văn bản của bình luận hiện tại.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 * @since 5.4.0 Thêm tiền tố 'Trả lời %s.' cho bình luận con trong feed bình luận.
 *
 * @see Walker_Comment::comment()
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy nội dung.
 *                                   Mặc định là bình luận hiện tại.
 * @param array          $args       Tùy chọn. Mảng các tham số. Mặc định mảng rỗng.
 * @return string Nội dung bình luận.
 */
function get_comment_text( $comment_id = 0, $args = array() ) {
	$comment = get_comment( $comment_id );

	$comment_text = $comment->comment_content;

	if ( is_comment_feed() && $comment->comment_parent ) {
		$parent = get_comment( $comment->comment_parent );
		if ( $parent ) {
			$parent_link = esc_url( get_comment_link( $parent ) );
			$name        = get_comment_author( $parent );

			$comment_text = sprintf(
				/* translators: %s: Comment link. */
				ent2ncr( __( 'In reply to %s.' ) ),
				'<a href="' . $parent_link . '">' . $name . '</a>'
			) . "\n\n" . $comment_text;
		}
	}

	/**
	 * Lọc nội dung văn bản của bình luận.
	 *
	 * @since 1.5.0
	 *
	 * @see Walker_Comment::comment()
	 *
	 * @param string     $comment_text Nội dung văn bản của bình luận.
	 * @param WP_Comment $comment      Đối tượng bình luận.
	 * @param array      $args         Mảng các tham số.
	 */
	return apply_filters( 'get_comment_text', $comment_text, $comment, $args );
}

/**
 * Hiển thị nội dung văn bản của bình luận hiện tại.
 *
 * @since 0.71
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @see Walker_Comment::comment()
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in nội dung.
 *                                   Mặc định là bình luận hiện tại.
 * @param array          $args       Tùy chọn. Mảng các tham số. Mặc định mảng rỗng.
 */
function comment_text( $comment_id = 0, $args = array() ) {
	$comment = get_comment( $comment_id );

	$comment_text = get_comment_text( $comment, $args );

	/**
	 * Lọc nội dung văn bản của bình luận để hiển thị.
	 *
	 * @since 1.2.0
	 *
	 * @see Walker_Comment::comment()
	 *
	 * @param string          $comment_text Nội dung văn bản của bình luận.
	 * @param WP_Comment|null $comment      Đối tượng bình luận. Null nếu không tìm thấy.
	 * @param array           $args         Mảng các tham số.
	 */
	echo apply_filters( 'comment_text', $comment_text, $comment, $args );
}

/**
 * Lấy thời gian bình luận của bình luận hiện tại.
 *
 * @since 1.5.0
 * @since 6.2.0 Thêm tham số `$comment_id`.
 *
 * @param string         $format     Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'time_format'.
 * @param bool           $gmt        Tùy chọn. Có sử dụng ngày GMT hay không. Mặc định false.
 * @param bool           $translate  Tùy chọn. Có dịch thời gian hay không (để sử dụng trong feed).
 *                                   Mặc định true.
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy thời gian.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Thời gian đã được định dạng.
 */
function get_comment_time( $format = '', $gmt = false, $translate = true, $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	if ( null === $comment ) {
		return '';
	}

	$comment_date = $gmt ? $comment->comment_date_gmt : $comment->comment_date;

	$_format = ! empty( $format ) ? $format : get_option( 'time_format' );

	$comment_time = mysql2date( $_format, $comment_date, $translate );

	/**
	 * Lọc thời gian bình luận được trả về.
	 *
	 * @since 1.5.0
	 *
	 * @param string|int $comment_time Thời gian bình luận, được định dạng dưới dạng chuỗi ngày hoặc timestamp Unix.
	 * @param string     $format       Định dạng ngày PHP.
	 * @param bool       $gmt          Có đang sử dụng ngày GMT hay không.
	 * @param bool       $translate    Có dịch thời gian hay không.
	 * @param WP_Comment $comment      Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_time', $comment_time, $format, $gmt, $translate, $comment );
}

/**
 * Hiển thị thời gian bình luận của bình luận hiện tại.
 *
 * @since 0.71
 * @since 6.2.0 Thêm tham số `$comment_id`.
 *
 * @param string         $format     Tùy chọn. Định dạng thời gian PHP. Mặc định theo tùy chọn 'time_format'.
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần in thời gian.
 *                                   Mặc định là bình luận hiện tại.
 */
function comment_time( $format = '', $comment_id = 0 ) {
	echo get_comment_time( $format, false, true, $comment_id );
}

/**
 * Lấy loại bình luận của bình luận hiện tại.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng cho `$comment_id` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param int|WP_Comment $comment_id Tùy chọn. WP_Comment hoặc ID của bình luận cần lấy loại.
 *                                   Mặc định là bình luận hiện tại.
 * @return string Loại bình luận.
 */
function get_comment_type( $comment_id = 0 ) {
	$comment = get_comment( $comment_id );

	if ( '' === $comment->comment_type ) {
		$comment->comment_type = 'comment';
	}

	/**
	 * Lọc loại bình luận được trả về.
	 *
	 * @since 1.5.0
	 * @since 4.1.0 Thêm tham số `$comment_id` và `$comment`.
	 *
	 * @param string     $comment_type Loại bình luận, chẳng hạn 'comment', 'pingback', hoặc 'trackback'.
	 * @param string     $comment_id   ID bình luận dưới dạng chuỗi số.
	 * @param WP_Comment $comment      Đối tượng bình luận.
	 */
	return apply_filters( 'get_comment_type', $comment->comment_type, $comment->comment_ID, $comment );
}

/**
 * Hiển thị loại bình luận của bình luận hiện tại.
 *
 * @since 0.71
 *
 * @param string|false $commenttxt   Tùy chọn. Chuỗi hiển thị cho loại bình luận. Mặc định false.
 * @param string|false $trackbacktxt Tùy chọn. Chuỗi hiển thị cho loại trackback. Mặc định false.
 * @param string|false $pingbacktxt  Tùy chọn. Chuỗi hiển thị cho loại pingback. Mặc định false.
 */
function comment_type( $commenttxt = false, $trackbacktxt = false, $pingbacktxt = false ) {
	if ( false === $commenttxt ) {
		$commenttxt = _x( 'Comment', 'noun' );
	}
	if ( false === $trackbacktxt ) {
		$trackbacktxt = __( 'Trackback' );
	}
	if ( false === $pingbacktxt ) {
		$pingbacktxt = __( 'Pingback' );
	}
	$type = get_comment_type();
	switch ( $type ) {
		case 'trackback':
			echo $trackbacktxt;
			break;
		case 'pingback':
			echo $pingbacktxt;
			break;
		default:
			echo $commenttxt;
	}
}

/**
 * Lấy URL trackback của bài viết hiện tại.
 *
 * Có kiểm tra xem permalink đã được bật chưa, nếu có sẽ lấy
 * đường dẫn đẹp. Nếu permalink chưa được bật, ID của bài viết
 * hiện tại sẽ được sử dụng và nối vào trang chính xác cần đến.
 *
 * @since 1.5.0
 *
 * @return string URL trackback sau khi được lọc.
 */
function get_trackback_url() {
	if ( get_option( 'permalink_structure' ) ) {
		$trackback_url = trailingslashit( get_permalink() ) . user_trailingslashit( 'trackback', 'single_trackback' );
	} else {
		$trackback_url = get_option( 'siteurl' ) . '/wp-trackback.php?p=' . get_the_ID();
	}

	/**
	 * Lọc URL trackback được trả về.
	 *
	 * @since 2.2.0
	 *
	 * @param string $trackback_url URL trackback.
	 */
	return apply_filters( 'trackback_url', $trackback_url );
}

/**
 * Hiển thị URL trackback của bài viết hiện tại.
 *
 * @since 0.71
 *
 * @param bool $deprecated_echo Không sử dụng.
 * @return void|string Chỉ nên sử dụng để echo URL trackback, dùng get_trackback_url()
 *                     để lấy kết quả thay thế.
 */
function trackback_url( $deprecated_echo = true ) {
	if ( true !== $deprecated_echo ) {
		_deprecated_argument(
			__FUNCTION__,
			'2.5.0',
			sprintf(
				/* translators: %s: get_trackback_url() */
				__( 'Use %s instead if you do not want the value echoed.' ),
				'<code>get_trackback_url()</code>'
			)
		);
	}

	if ( $deprecated_echo ) {
		echo get_trackback_url();
	} else {
		return get_trackback_url();
	}
}

/**
 * Tạo và hiển thị RDF cho thông tin trackback của bài viết hiện tại.
 *
 * Đã ngừng sử dụng trong 3.0.0, và được khôi phục trong 3.0.1.
 *
 * @since 0.71
 *
 * @param int|string $deprecated Không sử dụng (Trước đây là $timezone = 0).
 */
function trackback_rdf( $deprecated = '' ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.5.0' );
	}

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && false !== stripos( $_SERVER['HTTP_USER_AGENT'], 'W3C_Validator' ) ) {
		return;
	}

	echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
		<rdf:Description rdf:about="';
	the_permalink();
	echo '"' . "\n";
	echo '    dc:identifier="';
	the_permalink();
	echo '"' . "\n";
	echo '    dc:title="' . str_replace( '--', '&#x2d;&#x2d;', wptexturize( strip_tags( get_the_title() ) ) ) . '"' . "\n";
	echo '    trackback:ping="' . get_trackback_url() . '"' . " />\n";
	echo '</rdf:RDF>';
}

/**
 * Xác định xem bài viết hiện tại có đang mở cho bình luận hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 1.5.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @return bool True nếu bình luận đang mở.
 */
function comments_open( $post = null ) {
	$_post = get_post( $post );

	$post_id       = $_post ? $_post->ID : 0;
	$comments_open = ( $_post && ( 'open' === $_post->comment_status ) );

	/**
	 * Lọc xem bài viết hiện tại có đang mở cho bình luận hay không.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $comments_open Bài viết hiện tại có đang mở cho bình luận hay không.
	 * @param int  $post_id       ID bài viết.
	 */
	return apply_filters( 'comments_open', $comments_open, $post_id );
}

/**
 * Xác định xem bài viết hiện tại có đang mở cho ping hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 1.5.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @return bool True nếu ping được chấp nhận.
 */
function pings_open( $post = null ) {
	$_post = get_post( $post );

	$post_id    = $_post ? $_post->ID : 0;
	$pings_open = ( $_post && ( 'open' === $_post->ping_status ) );

	/**
	 * Lọc xem bài viết hiện tại có đang mở cho ping hay không.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $pings_open Bài viết hiện tại có đang mở cho ping hay không.
	 * @param int  $post_id    ID bài viết.
	 */
	return apply_filters( 'pings_open', $pings_open, $post_id );
}

/**
 * Hiển thị token biểu mẫu cho bình luận không được lọc.
 *
 * Chỉ hiển thị token nonce nếu người dùng hiện tại có quyền
 * HTML không được lọc. Không hiển thị token cho người dùng khác.
 *
 * Hàm này được backport vào 2.0.10 và được thêm vào phiên bản 2.1.3 và
 * cao hơn. Không tồn tại trong các phiên bản trước 2.0.10 ở nhánh 2.0 và trong
 * nhánh 2.1, trước 2.1.3. Về mặt kỹ thuật được thêm vào 2.2.0.
 *
 * Backport vào 2.0.10.
 *
 * @since 2.1.3
 */
function wp_comment_form_unfiltered_html_nonce() {
	$post    = get_post();
	$post_id = $post ? $post->ID : 0;

	if ( current_user_can( 'unfiltered_html' ) ) {
		wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_disabled', false );
		wp_print_inline_script_tag( "(function(){if(window===window.parent){document.getElementById('_wp_unfiltered_html_comment_disabled').name='_wp_unfiltered_html_comment';}})();" );
	}
}

/**
 * Tải template bình luận được chỉ định trong $file.
 *
 * Sẽ không hiển thị template bình luận nếu không ở trang bài viết đơn hoặc trang, hoặc nếu
 * bài viết không có bình luận.
 *
 * Sử dụng đối tượng cơ sở dữ liệu WordPress để truy vấn bình luận. Các bình luận
 * được truyền qua hook lọc {@see 'comments_array'} với danh sách bình luận
 * và ID bài viết tương ứng.
 *
 * Đường dẫn `$file` được truyền qua hook lọc gọi là {@see 'comments_template'},
 * bao gồm thư mục template và $file kết hợp. Thử đường dẫn $filtered
 * trước và nếu thất bại sẽ yêu cầu template bình luận mặc định từ
 * theme mặc định. Nếu một trong hai không tồn tại, tiến trình WordPress sẽ bị
 * dừng. Vì lý do đó, nên không xóa theme mặc định.
 *
 * Sẽ không cố gắng lấy bình luận nếu bài viết không có.
 *
 * @since 1.5.0
 *
 * @global WP_Query   $wp_query           Đối tượng truy vấn WordPress.
 * @global WP_Post    $post               Đối tượng bài viết toàn cục.
 * @global wpdb       $wpdb               Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 * @global int        $id
 * @global WP_Comment $comment            Đối tượng bình luận toàn cục.
 * @global string     $user_login
 * @global string     $user_identity
 * @global bool       $overridden_cpage
 * @global bool       $withcomments
 * @global string     $wp_stylesheet_path Đường dẫn đến thư mục stylesheet của theme hiện tại.
 * @global string     $wp_template_path   Đường dẫn đến thư mục template của theme hiện tại.
 *
 * @param string $file              Tùy chọn. File cần tải. Mặc định '/comments.php'.
 * @param bool   $separate_comments Tùy chọn. Có phân tách bình luận theo loại hay không.
 *                                  Mặc định false.
 */
function comments_template( $file = '/comments.php', $separate_comments = false ) {
	global $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_identity, $overridden_cpage, $wp_stylesheet_path, $wp_template_path;

	if ( ! ( is_single() || is_page() || $withcomments ) || empty( $post ) ) {
		return;
	}

	if ( empty( $file ) ) {
		$file = '/comments.php';
	}

	$req = get_option( 'require_name_email' );

	/*
	 * Thông tin tác giả bình luận được lấy từ cookie bình luận.
	 */
	$commenter = wp_get_current_commenter();

	/*
	 * Tên của tác giả bình luận hiện tại được escape để sử dụng trong thuộc tính.
	 * Được escape bởi sanitize_comment_cookies().
	 */
	$comment_author = $commenter['comment_author'];

	/*
	 * Địa chỉ email của tác giả bình luận hiện tại được escape để sử dụng trong thuộc tính.
	 * Được escape bởi sanitize_comment_cookies().
	 */
	$comment_author_email = $commenter['comment_author_email'];

	/*
	 * URL của tác giả bình luận hiện tại được escape để sử dụng trong thuộc tính.
	 */
	$comment_author_url = esc_url( $commenter['comment_author_url'] );

	$comment_args = array(
		'orderby'       => 'comment_date_gmt',
		'order'         => 'ASC',
		'status'        => 'approve',
		'post_id'       => $post->ID,
		'no_found_rows' => false,
	);

	if ( get_option( 'thread_comments' ) ) {
		$comment_args['hierarchical'] = 'threaded';
	} else {
		$comment_args['hierarchical'] = false;
	}

	if ( is_user_logged_in() ) {
		$comment_args['include_unapproved'] = array( get_current_user_id() );
	} else {
		$unapproved_email = wp_get_unapproved_comment_author_email();

		if ( $unapproved_email ) {
			$comment_args['include_unapproved'] = array( $unapproved_email );
		}
	}

	$per_page = 0;
	if ( get_option( 'page_comments' ) ) {
		$per_page = (int) get_query_var( 'comments_per_page' );
		if ( 0 === $per_page ) {
			$per_page = (int) get_option( 'comments_per_page' );
		}

		$comment_args['number'] = $per_page;
		$page                   = (int) get_query_var( 'cpage' );

		if ( $page ) {
			$comment_args['offset'] = ( $page - 1 ) * $per_page;
		} elseif ( 'oldest' === get_option( 'default_comments_page' ) ) {
			$comment_args['offset'] = 0;
		} else {
			// Nếu lấy trang đầu tiên của 'newest', chúng ta cần số bình luận cấp cao nhất.
			$top_level_query = new WP_Comment_Query();
			$top_level_args  = array(
				'count'   => true,
				'orderby' => false,
				'post_id' => $post->ID,
				'status'  => 'approve',
			);

			if ( $comment_args['hierarchical'] ) {
				$top_level_args['parent'] = 0;
			}

			if ( isset( $comment_args['include_unapproved'] ) ) {
				$top_level_args['include_unapproved'] = $comment_args['include_unapproved'];
			}

			/**
			 * Lọc các tham số được sử dụng trong truy vấn bình luận cấp cao nhất.
			 *
			 * @since 5.6.0
			 *
			 * @see WP_Comment_Query::__construct()
			 *
			 * @param array $top_level_args {
			 *     Các tham số truy vấn cấp cao nhất cho template bình luận.
			 *
			 *     @type bool         $count   Có trả về số bình luận hay không.
			 *     @type string|array $orderby (Các) trường để sắp xếp theo.
			 *     @type int          $post_id ID bài viết.
			 *     @type string|array $status  Trạng thái bình luận để giới hạn kết quả.
			 * }
			 */
			$top_level_args = apply_filters( 'comments_template_top_level_query_args', $top_level_args );

			$top_level_count = $top_level_query->query( $top_level_args );

			$comment_args['offset'] = ( (int) ceil( $top_level_count / $per_page ) - 1 ) * $per_page;
		}
	}

	/**
	 * Lọc các tham số được sử dụng để truy vấn bình luận trong comments_template().
	 *
	 * @since 4.5.0
	 *
	 * @see WP_Comment_Query::__construct()
	 *
	 * @param array $comment_args {
	 *     Mảng các tham số WP_Comment_Query.
	 *
	 *     @type string|array $orderby                   (Các) trường để sắp xếp theo.
	 *     @type string       $order                     Thứ tự kết quả. Chấp nhận 'ASC' hoặc 'DESC'.
	 *     @type string       $status                    Trạng thái bình luận.
	 *     @type array        $include_unapproved        Mảng các ID hoặc địa chỉ email có bình luận chưa duyệt
	 *                                                   sẽ được bao gồm trong kết quả.
	 *     @type int          $post_id                   ID của bài viết.
	 *     @type bool         $no_found_rows             Có bỏ qua truy vấn số dòng tìm thấy hay không.
	 *     @type bool         $update_comment_meta_cache Có tải trước cache cho comment meta hay không.
	 *     @type bool|string  $hierarchical              Có truy vấn bình luận theo phân cấp hay không.
	 *     @type int          $offset                    Vị trí bắt đầu bình luận.
	 *     @type int          $number                    Số bình luận cần lấy.
	 * }
	 */
	$comment_args = apply_filters( 'comments_template_query_args', $comment_args );

	$comment_query = new WP_Comment_Query( $comment_args );
	$_comments     = $comment_query->comments;

	// Cây phải được làm phẳng trước khi truyền cho walker.
	if ( $comment_args['hierarchical'] ) {
		$comments_flat = array();
		foreach ( $_comments as $_comment ) {
			$comments_flat[]  = $_comment;
			$comment_children = $_comment->get_children(
				array(
					'format'  => 'flat',
					'status'  => $comment_args['status'],
					'orderby' => $comment_args['orderby'],
				)
			);

			foreach ( $comment_children as $comment_child ) {
				$comments_flat[] = $comment_child;
			}
		}
	} else {
		$comments_flat = $_comments;
	}

	/**
	 * Lọc mảng bình luận.
	 *
	 * @since 2.1.0
	 *
	 * @param array $comments Mảng các bình luận được cung cấp cho template bình luận.
	 * @param int   $post_id  ID bài viết.
	 */
	$wp_query->comments = apply_filters( 'comments_array', $comments_flat, $post->ID );

	$comments                        = &$wp_query->comments;
	$wp_query->comment_count         = count( $wp_query->comments );
	$wp_query->max_num_comment_pages = $comment_query->max_num_pages;

	if ( $separate_comments ) {
		$wp_query->comments_by_type = separate_comments( $comments );
		$comments_by_type           = &$wp_query->comments_by_type;
	} else {
		$wp_query->comments_by_type = array();
	}

	$overridden_cpage = false;

	if ( '' === get_query_var( 'cpage' ) && $wp_query->max_num_comment_pages > 1 ) {
		set_query_var( 'cpage', 'newest' === get_option( 'default_comments_page' ) ? get_comment_pages_count() : 1 );
		$overridden_cpage = true;
	}

	if ( ! defined( 'COMMENTS_TEMPLATE' ) ) {
		define( 'COMMENTS_TEMPLATE', true );
	}

	$theme_template = trailingslashit( $wp_stylesheet_path ) . $file;

	/**
	 * Lọc đường dẫn đến file template theme được sử dụng cho template bình luận.
	 *
	 * @since 1.5.1
	 *
	 * @param string $theme_template Đường dẫn đến file template theme.
	 */
	$include = apply_filters( 'comments_template', $theme_template );

	if ( file_exists( $include ) ) {
		require $include;
	} elseif ( file_exists( trailingslashit( $wp_template_path ) . $file ) ) {
		require trailingslashit( $wp_template_path ) . $file;
	} else { // Mã tương thích ngược sẽ được loại bỏ trong phiên bản tương lai.
		require ABSPATH . WPINC . '/theme-compat/comments.php';
	}
}

/**
 * Hiển thị liên kết đến bình luận cho ID bài viết hiện tại.
 *
 * @since 0.71
 *
 * @param false|string $zero      Tùy chọn. Chuỗi hiển thị khi không có bình luận. Mặc định false.
 * @param false|string $one       Tùy chọn. Chuỗi hiển thị khi chỉ có một bình luận. Mặc định false.
 * @param false|string $more      Tùy chọn. Chuỗi hiển thị khi có nhiều hơn một bình luận. Mặc định false.
 * @param string       $css_class Tùy chọn. Class CSS sử dụng cho bình luận. Mặc định rỗng.
 * @param false|string $none      Tùy chọn. Chuỗi hiển thị khi bình luận đã bị tắt. Mặc định false.
 */
function comments_popup_link( $zero = false, $one = false, $more = false, $css_class = '', $none = false ) {
	$post_id         = get_the_ID();
	$post_title      = get_the_title();
	$comments_number = (int) get_comments_number( $post_id );

	if ( false === $zero ) {
		/* translators: %s: Post title. */
		$zero = sprintf( __( 'No Comments<span class="screen-reader-text"> on %s</span>' ), $post_title );
	}

	if ( false === $one ) {
		/* translators: %s: Post title. */
		$one = sprintf( __( '1 Comment<span class="screen-reader-text"> on %s</span>' ), $post_title );
	}

	if ( false === $more ) {
		/* translators: 1: Number of comments, 2: Post title. */
		$more = _n(
			'%1$s Comment<span class="screen-reader-text"> on %2$s</span>',
			'%1$s Comments<span class="screen-reader-text"> on %2$s</span>',
			$comments_number
		);
		$more = sprintf( $more, number_format_i18n( $comments_number ), $post_title );
	}

	if ( false === $none ) {
		/* translators: %s: Post title. */
		$none = sprintf( __( 'Comments Off<span class="screen-reader-text"> on %s</span>' ), $post_title );
	}

	if ( 0 === $comments_number && ! comments_open() && ! pings_open() ) {
		printf(
			'<span%1$s>%2$s</span>',
			! empty( $css_class ) ? ' class="' . esc_attr( $css_class ) . '"' : '',
			$none
		);
		return;
	}

	if ( post_password_required() ) {
		_e( 'Enter your password to view comments.' );
		return;
	}

	if ( 0 === $comments_number ) {
		$respond_link = get_permalink() . '#respond';
		/**
		 * Lọc liên kết phản hồi khi bài viết không có bình luận.
		 *
		 * @since 4.4.0
		 *
		 * @param string $respond_link Liên kết phản hồi mặc định.
		 * @param int    $post_id      ID bài viết.
		 */
		$comments_link = apply_filters( 'respond_link', $respond_link, $post_id );
	} else {
		$comments_link = get_comments_link();
	}

	$link_attributes = '';

	/**
	 * Lọc các thuộc tính liên kết bình luận để hiển thị.
	 *
	 * @since 2.5.0
	 *
	 * @param string $link_attributes Các thuộc tính liên kết bình luận. Mặc định rỗng.
	 */
	$link_attributes = apply_filters( 'comments_popup_link_attributes', $link_attributes );

	printf(
		'<a href="%1$s"%2$s%3$s>%4$s</a>',
		esc_url( $comments_link ),
		! empty( $css_class ) ? ' class="' . $css_class . '" ' : '',
		$link_attributes,
		get_comments_number_text( $zero, $one, $more )
	);
}

/**
 * Lấy nội dung HTML cho liên kết trả lời bình luận.
 *
 * @since 2.7.0
 * @since 4.4.0 Thêm khả năng cho `$comment` cũng chấp nhận đối tượng WP_Comment.
 *
 * @param array          $args {
 *     Tùy chọn. Ghi đè các tham số mặc định.
 *
 *     @type string $add_below          Phần đầu tiên của bộ chọn dùng để xác định bình luận cần trả lời phía dưới.
 *                                      Giá trị kết quả được truyền như tham số đầu tiên cho addComment.moveForm(),
 *                                      được nối thành $add_below-$comment->comment_ID. Mặc định 'comment'.
 *     @type string $respond_id         Bộ chọn xác định bình luận đang phản hồi. Được truyền như tham số thứ ba
 *                                      cho addComment.moveForm(), và được nối vào URL liên kết dưới dạng giá trị hash.
 *                                      Mặc định 'respond'.
 *     @type string $reply_text         Văn bản hiển thị của liên kết Trả lời. Mặc định 'Reply'.
 *     @type string $reply_to_text      Tên truy cập được của liên kết Trả lời, sử dụng `%s` làm chỗ giữ
 *                                      cho tên tác giả bình luận. Mặc định 'Reply to %s'.
 *                                      Nên bắt đầu với giá trị `reply_text` hiển thị.
 *     @type bool   $show_reply_to_text Có sử dụng `reply_to_text` làm văn bản liên kết hiển thị hay không. Mặc định false.
 *     @type string $login_text         Văn bản của liên kết trả lời nếu chưa đăng nhập. Mặc định 'Log in to Reply'.
 *     @type int    $max_depth          Độ sâu tối đa của cây bình luận. Mặc định 0.
 *     @type int    $depth              Độ sâu của bình luận mới. Phải lớn hơn 0 và nhỏ hơn giá trị
 *                                      của tùy chọn 'thread_comments_depth' trong Cài đặt > Thảo luận. Mặc định 0.
 *     @type string $before             Văn bản hoặc HTML thêm trước liên kết trả lời. Mặc định rỗng.
 *     @type string $after              Văn bản hoặc HTML thêm sau liên kết trả lời. Mặc định rỗng.
 * }
 * @param int|WP_Comment $comment Tùy chọn. Bình luận đang được trả lời. Mặc định bình luận hiện tại.
 * @param int|WP_Post    $post    Tùy chọn. ID bài viết hoặc đối tượng WP_Post mà bình luận sẽ được hiển thị.
 *                                Mặc định bài viết hiện tại.
 * @return string|false|null Liên kết hiển thị biểu mẫu bình luận nếu thành công. False nếu bình luận đã đóng.
 */
function get_comment_reply_link( $args = array(), $comment = null, $post = null ) {
	$defaults = array(
		'add_below'          => 'comment',
		'respond_id'         => 'respond',
		'reply_text'         => __( 'Reply' ),
		/* translators: Comment reply button text. %s: Comment author name. */
		'reply_to_text'      => __( 'Reply to %s' ),
		'login_text'         => __( 'Log in to Reply' ),
		'max_depth'          => 0,
		'depth'              => 0,
		'before'             => '',
		'after'              => '',
		'show_reply_to_text' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$args['max_depth'] = (int) $args['max_depth'];
	$args['depth']     = (int) $args['depth'];

	if ( 0 === $args['depth'] || $args['max_depth'] <= $args['depth'] ) {
		return;
	}

	$comment = get_comment( $comment );

	if ( empty( $comment ) ) {
		return;
	}

	if ( empty( $post ) ) {
		$post = $comment->comment_post_ID;
	}

	$post = get_post( $post );

	if ( ! comments_open( $post->ID ) ) {
		return false;
	}

	if ( get_option( 'page_comments' ) ) {
		$permalink = str_replace( '#comment-' . $comment->comment_ID, '', get_comment_link( $comment ) );
	} else {
		$permalink = get_permalink( $post->ID );
	}

	/**
	 * Lọc các tham số liên kết trả lời bình luận.
	 *
	 * @since 4.1.0
	 *
	 * @param array      $args    Các tham số liên kết trả lời bình luận. Xem get_comment_reply_link()
	 *                            để biết thêm thông tin về các tham số được chấp nhận.
	 * @param WP_Comment $comment Đối tượng của bình luận đang được trả lời.
	 * @param WP_Post    $post    Đối tượng WP_Post.
	 */
	$args = apply_filters( 'comment_reply_link_args', $args, $comment, $post );

	if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
		$link = sprintf(
			'<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>',
			esc_url( wp_login_url( get_permalink() ) ),
			$args['login_text']
		);
	} else {
		$data_attributes = array(
			'commentid'      => $comment->comment_ID,
			'postid'         => $post->ID,
			'belowelement'   => $args['add_below'] . '-' . $comment->comment_ID,
			'respondelement' => $args['respond_id'],
			'replyto'        => sprintf( $args['reply_to_text'], get_comment_author( $comment ) ),
		);

		$data_attribute_string = '';

		foreach ( $data_attributes as $name => $value ) {
			$data_attribute_string .= " data-{$name}=\"" . esc_attr( $value ) . '"';
		}

		$data_attribute_string = trim( $data_attribute_string );

		$reply_text = $args['show_reply_to_text']
			? sprintf( $args['reply_to_text'], get_comment_author( $comment ) )
			: $args['reply_text'];

		$aria_label = $args['show_reply_to_text'] ? '' : sprintf( $args['reply_to_text'], get_comment_author( $comment ) );

		$link = sprintf(
			'<a rel="nofollow" class="comment-reply-link" href="%s" %s%s>%s</a>',
			esc_url(
				add_query_arg(
					array(
						'replytocom'      => $comment->comment_ID,
						'unapproved'      => false,
						'moderation-hash' => false,
					),
					$permalink
				)
			) . '#' . $args['respond_id'],
			$data_attribute_string,
			$aria_label ? ' aria-label="' . esc_attr( $aria_label ) . '"' : '',
			$reply_text
		);
	}

	$comment_reply_link = $args['before'] . $link . $args['after'];

	/**
	 * Lọc liên kết trả lời bình luận.
	 *
	 * @since 2.7.0
	 *
	 * @param string     $comment_reply_link Markup HTML cho liên kết trả lời bình luận.
	 * @param array      $args               Mảng các tham số ghi đè giá trị mặc định.
	 * @param WP_Comment $comment            Đối tượng của bình luận đang được trả lời.
	 * @param WP_Post    $post               Đối tượng WP_Post.
	 */
	return apply_filters( 'comment_reply_link', $comment_reply_link, $args, $comment, $post );
}

/**
 * Hiển thị nội dung HTML cho liên kết trả lời bình luận.
 *
 * @since 2.7.0
 *
 * @see get_comment_reply_link()
 *
 * @param array          $args    Tùy chọn. Ghi đè các tùy chọn mặc định. Mặc định mảng rỗng.
 * @param int|WP_Comment $comment Tùy chọn. Bình luận đang được trả lời. Mặc định bình luận hiện tại.
 * @param int|WP_Post    $post    Tùy chọn. ID bài viết hoặc đối tượng WP_Post mà bình luận sẽ được hiển thị.
 *                                Mặc định bài viết hiện tại.
 */
function comment_reply_link( $args = array(), $comment = null, $post = null ) {
	echo get_comment_reply_link( $args, $comment, $post );
}

/**
 * Lấy nội dung HTML cho liên kết trả lời bài viết.
 *
 * @since 2.7.0
 *
 * @param array       $args {
 *     Tùy chọn. Ghi đè các tham số mặc định.
 *
 *     @type string $add_below  Phần đầu tiên của bộ chọn dùng để xác định bình luận cần trả lời phía dưới.
 *                              Giá trị kết quả được truyền như tham số đầu tiên cho addComment.moveForm(),
 *                              được nối thành $add_below-$comment->comment_ID. Mặc định là 'post'.
 *     @type string $respond_id Bộ chọn xác định bình luận đang phản hồi. Được truyền như tham số thứ ba
 *                              cho addComment.moveForm(), và được nối vào URL liên kết dưới dạng giá trị hash.
 *                              Mặc định 'respond'.
 *     @type string $reply_text Văn bản của liên kết Trả lời. Mặc định là 'Leave a Comment'.
 *     @type string $login_text Văn bản của liên kết trả lời nếu chưa đăng nhập. Mặc định là 'Log in to leave a Comment'.
 *     @type string $before     Văn bản hoặc HTML thêm trước liên kết trả lời. Mặc định rỗng.
 *     @type string $after      Văn bản hoặc HTML thêm sau liên kết trả lời. Mặc định rỗng.
 * }
 * @param int|WP_Post $post    Tùy chọn. ID bài viết hoặc đối tượng WP_Post mà bình luận sẽ được hiển thị.
 *                             Mặc định bài viết hiện tại.
 * @return string|false|null Liên kết hiển thị biểu mẫu bình luận nếu thành công. False nếu bình luận đã đóng.
 */
function get_post_reply_link( $args = array(), $post = null ) {
	$defaults = array(
		'add_below'  => 'post',
		'respond_id' => 'respond',
		'reply_text' => __( 'Leave a Comment' ),
		'login_text' => __( 'Log in to leave a Comment' ),
		'before'     => '',
		'after'      => '',
	);

	$args = wp_parse_args( $args, $defaults );

	$post = get_post( $post );

	if ( ! comments_open( $post->ID ) ) {
		return false;
	}

	if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
		$link = sprintf(
			'<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>',
			wp_login_url( get_permalink() ),
			$args['login_text']
		);
	} else {
		$onclick = sprintf(
			'return addComment.moveForm( "%1$s-%2$s", "0", "%3$s", "%2$s" )',
			$args['add_below'],
			$post->ID,
			$args['respond_id']
		);

		$link = sprintf(
			"<a rel='nofollow' class='comment-reply-link' href='%s' onclick='%s'>%s</a>",
			get_permalink( $post->ID ) . '#' . $args['respond_id'],
			$onclick,
			$args['reply_text']
		);
	}

	$post_reply_link = $args['before'] . $link . $args['after'];

	/**
	 * Lọc HTML liên kết bình luận bài viết được định dạng.
	 *
	 * @since 2.7.0
	 *
	 * @param string      $post_reply_link Liên kết bình luận bài viết được định dạng HTML.
	 * @param int|WP_Post $post            ID bài viết hoặc đối tượng WP_Post.
	 */
	return apply_filters( 'post_comments_link', $post_reply_link, $post );
}

/**
 * Hiển thị nội dung HTML cho liên kết trả lời bài viết.
 *
 * @since 2.7.0
 *
 * @see get_post_reply_link()
 *
 * @param array       $args Tùy chọn. Ghi đè các tùy chọn mặc định. Mặc định mảng rỗng.
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post mà bình luận sẽ được hiển thị.
 *                          Mặc định bài viết hiện tại.
 */
function post_reply_link( $args = array(), $post = null ) {
	echo get_post_reply_link( $args, $post );
}

/**
 * Lấy nội dung HTML cho liên kết hủy trả lời bình luận.
 *
 * @since 2.7.0
 * @since 6.2.0 Thêm tham số `$post`.
 *
 * @param string           $link_text Tùy chọn. Văn bản hiển thị cho liên kết hủy trả lời. Nếu rỗng,
 *                                    mặc định là 'Click here to cancel reply'. Mặc định rỗng.
 * @param int|WP_Post|null $post      Tùy chọn. Bài viết mà chuỗi bình luận đang được
 *                                    hiển thị. Mặc định là bài viết toàn cục hiện tại.
 * @return string
 */
function get_cancel_comment_reply_link( $link_text = '', $post = null ) {
	if ( empty( $link_text ) ) {
		$link_text = __( 'Click here to cancel reply.' );
	}

	$post        = get_post( $post );
	$reply_to_id = $post ? _get_comment_reply_id( $post->ID ) : 0;
	$link_style  = 0 !== $reply_to_id ? '' : ' style="display:none;"';
	$link_url    = esc_url( remove_query_arg( array( 'replytocom', 'unapproved', 'moderation-hash' ) ) ) . '#respond';

	$cancel_comment_reply_link = sprintf(
		'<a rel="nofollow" id="cancel-comment-reply-link" href="%1$s"%2$s>%3$s</a>',
		$link_url,
		$link_style,
		$link_text
	);

	/**
	 * Lọc HTML liên kết hủy trả lời bình luận.
	 *
	 * @since 2.7.0
	 *
	 * @param string $cancel_comment_reply_link Liên kết hủy trả lời bình luận được định dạng HTML.
	 * @param string $link_url                  URL liên kết hủy trả lời bình luận.
	 * @param string $link_text                 Văn bản liên kết hủy trả lời bình luận.
	 */
	return apply_filters( 'cancel_comment_reply_link', $cancel_comment_reply_link, $link_url, $link_text );
}

/**
 * Hiển thị nội dung HTML cho liên kết hủy trả lời bình luận.
 *
 * @since 2.7.0
 *
 * @param string $link_text Tùy chọn. Văn bản hiển thị cho liên kết hủy trả lời. Nếu rỗng,
 *                     mặc định là 'Click here to cancel reply'. Mặc định rỗng.
 */
function cancel_comment_reply_link( $link_text = '' ) {
	echo get_cancel_comment_reply_link( $link_text );
}

/**
 * Lấy HTML input ẩn để trả lời bình luận.
 *
 * @since 3.0.0
 * @since 6.2.0 Đổi tên `$post_id` thành `$post` và thêm hỗ trợ WP_Post.
 *
 * @param int|WP_Post|null $post Tùy chọn. Bài viết mà bình luận đang được hiển thị.
 *                               Mặc định là bài viết toàn cục hiện tại.
 * @return string HTML input ẩn để trả lời bình luận.
 */
function get_comment_id_fields( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$post_id     = $post->ID;
	$reply_to_id = _get_comment_reply_id( $post_id );

	$comment_id_fields  = "<input type='hidden' name='comment_post_ID' value='$post_id' id='comment_post_ID' />\n";
	$comment_id_fields .= "<input type='hidden' name='comment_parent' id='comment_parent' value='$reply_to_id' />\n";

	/**
	 * Lọc các trường ID bình luận được trả về.
	 *
	 * @since 3.0.0
	 *
	 * @param string $comment_id_fields Các phần tử trường ID ẩn bình luận được định dạng HTML.
	 * @param int    $post_id           ID bài viết.
	 * @param int    $reply_to_id       ID của bình luận đang được trả lời.
	 */
	return apply_filters( 'comment_id_fields', $comment_id_fields, $post_id, $reply_to_id );
}

/**
 * Xuất HTML input ẩn để trả lời bình luận.
 *
 * Thêm hai input ẩn vào biểu mẫu bình luận để xác định các giá trị `comment_post_ID`
 * và `comment_parent` cho bình luận phân luồng.
 *
 * Thẻ này phải nằm trong phần `<form>` của template `comments.php`.
 *
 * @since 2.7.0
 * @since 6.2.0 Đổi tên `$post_id` thành `$post` và thêm hỗ trợ WP_Post.
 *
 * @see get_comment_id_fields()
 *
 * @param int|WP_Post|null $post Tùy chọn. Bài viết mà bình luận đang được hiển thị.
 *                               Mặc định là bài viết toàn cục hiện tại.
 */
function comment_id_fields( $post = null ) {
	echo get_comment_id_fields( $post );
}

/**
 * Hiển thị văn bản dựa trên trạng thái trả lời bình luận.
 *
 * Chỉ ảnh hưởng đến người dùng có JavaScript bị tắt.
 *
 * @internal Biến toàn cục $comment phải có mặt để cho phép các thẻ template truy cập
 *           bình luận hiện tại. Xem https://core.trac.wordpress.org/changeset/36512.
 *
 * @since 2.7.0
 * @since 6.2.0 Thêm tham số `$post`.
 *
 * @global WP_Comment $comment Đối tượng bình luận toàn cục.
 *
 * @param string|false     $no_reply_text  Tùy chọn. Văn bản hiển thị khi không trả lời bình luận.
 *                                         Mặc định false.
 * @param string|false     $reply_text     Tùy chọn. Văn bản hiển thị khi trả lời bình luận.
 *                                         Mặc định false. Chấp nhận "%s" cho tên tác giả bình luận
 *                                         đang được trả lời.
 * @param bool             $link_to_parent Tùy chọn. Boolean để kiểm soát việc tạo liên kết tên tác giả
 *                                         đến bình luận của họ. Mặc định true.
 * @param int|WP_Post|null $post           Tùy chọn. Bài viết mà biểu mẫu bình luận đang được hiển thị.
 *                                         Mặc định là bài viết toàn cục hiện tại.
 */
function comment_form_title( $no_reply_text = false, $reply_text = false, $link_to_parent = true, $post = null ) {
	global $comment;

	if ( false === $no_reply_text ) {
		$no_reply_text = __( 'Leave a Reply' );
	}

	if ( false === $reply_text ) {
		/* translators: %s: Author of the comment being replied to. */
		$reply_text = __( 'Leave a Reply to %s' );
	}

	$post = get_post( $post );
	if ( ! $post ) {
		echo $no_reply_text;
		return;
	}

	$reply_to_id = _get_comment_reply_id( $post->ID );

	if ( 0 === $reply_to_id ) {
		echo $no_reply_text;
		return;
	}

	// Đặt biến toàn cục để các thẻ template có thể được sử dụng trong biểu mẫu bình luận.
	$comment = get_comment( $reply_to_id );

	if ( $link_to_parent ) {
		$comment_author = sprintf(
			'<a href="#comment-%1$s">%2$s</a>',
			get_comment_ID(),
			get_comment_author( $reply_to_id )
		);
	} else {
		$comment_author = get_comment_author( $reply_to_id );
	}

	printf( $reply_text, $comment_author );
}

/**
 * Lấy ID trả lời của bình luận từ $_GET['replytocom'].
 *
 * @since 6.2.0
 *
 * @access private
 *
 * @param int|WP_Post $post Bài viết mà bình luận đang được hiển thị.
 *                          Mặc định là bài viết toàn cục hiện tại.
 * @return int ID trả lời của bình luận.
 */
function _get_comment_reply_id( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || ! isset( $_GET['replytocom'] ) || ! is_numeric( $_GET['replytocom'] ) ) {
		return 0;
	}

	$reply_to_id = (int) $_GET['replytocom'];

	/*
	 * Xác thực bình luận.
	 * Thoát ra nếu bình luận không tồn tại, chưa được duyệt, hoặc
	 * `comment_post_ID` của nó không khớp với ID bài viết đã cho.
	 */
	$comment = get_comment( $reply_to_id );

	if (
		! $comment instanceof WP_Comment ||
		0 === (int) $comment->comment_approved ||
		$post->ID !== (int) $comment->comment_post_ID
	) {
		return 0;
	}

	return $reply_to_id;
}

/**
 * Hiển thị danh sách các bình luận.
 *
 * Được sử dụng trong template comments.php để liệt kê bình luận cho bài viết cụ thể.
 *
 * @since 2.7.0
 *
 * @see WP_Query::$comments
 *
 * @global WP_Query $wp_query           Đối tượng truy vấn WordPress.
 * @global int      $comment_alt
 * @global int      $comment_depth
 * @global int      $comment_thread_alt
 * @global bool     $overridden_cpage
 * @global bool     $in_comment_loop
 *
 * @param string|array $args {
 *     Tùy chọn. Các tùy chọn định dạng.
 *
 *     @type object   $walker            Đối tượng của class Walker để liệt kê bình luận. Mặc định null.
 *     @type int      $max_depth         Độ sâu tối đa của bình luận. Mặc định rỗng.
 *     @type string   $style             Kiểu sắp xếp danh sách. Chấp nhận 'ul', 'ol', hoặc 'div'.
 *                                       'div' sẽ không tạo thêm markup danh sách. Mặc định 'ul'.
 *     @type callable $callback          Hàm callback sử dụng. Mặc định null.
 *     @type callable $end-callback      Hàm callback sử dụng ở cuối. Mặc định null.
 *     @type string   $type              Loại bình luận cần liệt kê. Chấp nhận 'all', 'comment',
 *                                       'pingback', 'trackback', 'pings'. Mặc định 'all'.
 *     @type int      $page              ID trang để liệt kê bình luận. Mặc định rỗng.
 *     @type int      $per_page          Số bình luận liệt kê mỗi trang. Mặc định rỗng.
 *     @type int      $avatar_size       Kích thước chiều cao và chiều rộng của avatar. Mặc định 32.
 *     @type bool     $reverse_top_level Thứ tự sắp xếp bình luận. Nếu true, sẽ hiển thị
 *                                       bình luận mới nhất trước. Mặc định null.
 *     @type bool     $reverse_children  Có đảo ngược bình luận con trong danh sách hay không. Mặc định null.
 *     @type string   $format            Cách định dạng danh sách bình luận. Chấp nhận 'html5', 'xhtml'.
 *                                       Mặc định 'html5' nếu theme hỗ trợ.
 *     @type bool     $short_ping        Có xuất ping ngắn hay không. Mặc định false.
 *     @type bool     $echo              Có echo đầu ra hay trả về nó. Mặc định true.
 * }
 * @param WP_Comment[] $comments Tùy chọn. Mảng các đối tượng WP_Comment. Mặc định null.
 * @return void|string Void nếu tham số 'echo' là true, hoặc không có bình luận để liệt kê.
 *                     Ngược lại, danh sách HTML các bình luận.
 */
function wp_list_comments( $args = array(), $comments = null ) {
	global $wp_query, $comment_alt, $comment_depth, $comment_thread_alt, $overridden_cpage, $in_comment_loop;

	$in_comment_loop = true;

	$comment_alt        = 0;
	$comment_thread_alt = 0;
	$comment_depth      = 1;

	$defaults = array(
		'walker'            => null,
		'max_depth'         => '',
		'style'             => 'ul',
		'callback'          => null,
		'end-callback'      => null,
		'type'              => 'all',
		'page'              => '',
		'per_page'          => '',
		'avatar_size'       => 32,
		'reverse_top_level' => null,
		'reverse_children'  => '',
		'format'            => current_theme_supports( 'html5', 'comment-list' ) ? 'html5' : 'xhtml',
		'short_ping'        => false,
		'echo'              => true,
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	/**
	 * Lọc các tham số được sử dụng khi lấy danh sách bình luận.
	 *
	 * @since 4.0.0
	 *
	 * @see wp_list_comments()
	 *
	 * @param array $parsed_args Mảng các tham số để hiển thị bình luận.
	 */
	$parsed_args = apply_filters( 'wp_list_comments_args', $parsed_args );

	// Xác định bình luận nào chúng ta sẽ lặp qua ($_comments).
	if ( null !== $comments ) {
		$comments = (array) $comments;
		if ( empty( $comments ) ) {
			return;
		}
		if ( 'all' !== $parsed_args['type'] ) {
			$comments_by_type = separate_comments( $comments );
			if ( empty( $comments_by_type[ $parsed_args['type'] ] ) ) {
				return;
			}
			$_comments = $comments_by_type[ $parsed_args['type'] ];
		} else {
			$_comments = $comments;
		}
	} else {
		/*
		 * Nếu 'page' hoặc 'per_page' được truyền, và không khớp với giá trị trong $wp_query,
		 * thực hiện truy vấn bình luận riêng và cho phép Walker_Comment phân trang.
		 */
		if ( $parsed_args['page'] || $parsed_args['per_page'] ) {
			$current_cpage = (int) get_query_var( 'cpage' );
			if ( ! $current_cpage ) {
				$current_cpage = 'newest' === get_option( 'default_comments_page' ) ? 1 : $wp_query->max_num_comment_pages;
			}

			$current_per_page = (int) get_query_var( 'comments_per_page' );
			if ( (int) $parsed_args['page'] !== $current_cpage || (int) $parsed_args['per_page'] !== $current_per_page ) {
				$comment_args = array(
					'post_id' => get_the_ID(),
					'orderby' => 'comment_date_gmt',
					'order'   => 'ASC',
					'status'  => 'approve',
				);

				if ( is_user_logged_in() ) {
					$comment_args['include_unapproved'] = array( get_current_user_id() );
				} else {
					$unapproved_email = wp_get_unapproved_comment_author_email();

					if ( $unapproved_email ) {
						$comment_args['include_unapproved'] = array( $unapproved_email );
					}
				}

				$comments = get_comments( $comment_args );

				if ( 'all' !== $parsed_args['type'] ) {
					$comments_by_type = separate_comments( $comments );
					if ( empty( $comments_by_type[ $parsed_args['type'] ] ) ) {
						return;
					}

					$_comments = $comments_by_type[ $parsed_args['type'] ];
				} else {
					$_comments = $comments;
				}
			}

			// Nếu không, quay lại sử dụng bình luận từ `$wp_query->comments`.
		} else {
			if ( empty( $wp_query->comments ) ) {
				return;
			}
			if ( 'all' !== $parsed_args['type'] ) {
				if ( empty( $wp_query->comments_by_type ) ) {
					$wp_query->comments_by_type = separate_comments( $wp_query->comments );
				}
				if ( empty( $wp_query->comments_by_type[ $parsed_args['type'] ] ) ) {
					return;
				}
				$_comments = $wp_query->comments_by_type[ $parsed_args['type'] ];
			} else {
				$_comments = $wp_query->comments;
			}

			if ( $wp_query->max_num_comment_pages ) {
				$default_comments_page = get_option( 'default_comments_page' );
				$cpage                 = (int) get_query_var( 'cpage' );

				if ( 'newest' === $default_comments_page ) {
					$parsed_args['cpage'] = $cpage;
				} elseif ( 1 === $cpage ) {
					/*
					 * Khi trang đầu tiên hiển thị các bình luận cũ nhất,
					 * permalink bài viết giống với permalink bình luận.
					 */
					$parsed_args['cpage'] = '';
				} else {
					$parsed_args['cpage'] = $cpage;
				}

				$parsed_args['page']     = 0;
				$parsed_args['per_page'] = 0;
			}
		}
	}

	if ( '' === $parsed_args['per_page'] && get_option( 'page_comments' ) ) {
		$parsed_args['per_page'] = get_query_var( 'comments_per_page' );
	}

	if ( empty( $parsed_args['per_page'] ) ) {
		$parsed_args['per_page'] = 0;
		$parsed_args['page']     = 0;
	}

	if ( '' === $parsed_args['max_depth'] ) {
		if ( get_option( 'thread_comments' ) ) {
			$parsed_args['max_depth'] = get_option( 'thread_comments_depth' );
		} else {
			$parsed_args['max_depth'] = -1;
		}
	}

	if ( '' === $parsed_args['page'] ) {
		if ( empty( $overridden_cpage ) ) {
			$parsed_args['page'] = get_query_var( 'cpage' );
		} else {
			$threaded            = ( -1 !== (int) $parsed_args['max_depth'] );
			$parsed_args['page'] = ( 'newest' === get_option( 'default_comments_page' ) ) ? get_comment_pages_count( $_comments, $parsed_args['per_page'], $threaded ) : 1;
			set_query_var( 'cpage', $parsed_args['page'] );
		}
	}

	// Kiểm tra xác thực.
	$parsed_args['page']     = (int) $parsed_args['page'];
	$parsed_args['per_page'] = (int) $parsed_args['per_page'];
	if ( 0 === $parsed_args['page'] && 0 !== $parsed_args['per_page'] ) {
		$parsed_args['page'] = 1;
	}

	if ( null === $parsed_args['reverse_top_level'] ) {
		$parsed_args['reverse_top_level'] = ( 'desc' === get_option( 'comment_order' ) );
	}

	if ( empty( $parsed_args['walker'] ) ) {
		$walker = new Walker_Comment();
	} else {
		$walker = $parsed_args['walker'];
	}

	$output = $walker->paged_walk( $_comments, $parsed_args['max_depth'], $parsed_args['page'], $parsed_args['per_page'], $parsed_args );

	$in_comment_loop = false;

	if ( $parsed_args['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Outputs a complete commenting form for use within a template.
 *
 * Most strings and form fields may be controlled through the `$args` array passed
 * into the function, while you may also choose to use the {@see 'comment_form_default_fields'}
 * filter to modify the array of default fields if you'd just like to add a new
 * one or remove a single field. All fields are also individually passed through
 * a filter of the {@see 'comment_form_field_$name'} where `$name` is the key used
 * in the array of fields.
 *
 * @since 3.0.0
 * @since 4.1.0 Introduced the 'class_submit' argument.
 * @since 4.2.0 Introduced the 'submit_button' and 'submit_fields' arguments.
 * @since 4.4.0 Introduced the 'class_form', 'title_reply_before', 'title_reply_after',
 *              'cancel_reply_before', and 'cancel_reply_after' arguments.
 * @since 4.5.0 The 'author', 'email', and 'url' form fields are limited to 245, 100,
 *              and 200 characters, respectively.
 * @since 4.6.0 Introduced the 'action' argument.
 * @since 4.9.6 Introduced the 'cookies' default comment field.
 * @since 5.5.0 Introduced the 'class_container' argument.
 * @since 6.8.2 Introduced the 'novalidate' argument.
 *
 * @param array       $args {
 *     Optional. Default arguments and form fields to override.
 *
 *     @type array $fields {
 *         Default comment fields, filterable by default via the {@see 'comment_form_default_fields'} hook.
 *
 *         @type string $author  Comment author field HTML.
 *         @type string $email   Comment author email field HTML.
 *         @type string $url     Comment author URL field HTML.
 *         @type string $cookies Comment cookie opt-in field HTML.
 *     }
 *     @type string $comment_field        The comment textarea field HTML.
 *     @type string $must_log_in          HTML element for a 'must be logged in to comment' message.
 *     @type string $logged_in_as         The HTML for the 'logged in as [user]' message, the Edit profile link,
 *                                        and the Log out link.
 *     @type string $comment_notes_before HTML element for a message displayed before the comment fields
 *                                        if the user is not logged in.
 *                                        Default 'Your email address will not be published.'.
 *     @type string $comment_notes_after  HTML element for a message displayed after the textarea field.
 *     @type string $action               The comment form element action attribute. Default '/wp-comments-post.php'.
 *     @type bool   $novalidate           Whether the novalidate attribute is added to the comment form. Default false.
 *     @type string $id_form              The comment form element id attribute. Default 'commentform'.
 *     @type string $id_submit            The comment submit element id attribute. Default 'submit'.
 *     @type string $class_container      The comment form container class attribute. Default 'comment-respond'.
 *     @type string $class_form           The comment form element class attribute. Default 'comment-form'.
 *     @type string $class_submit         The comment submit element class attribute. Default 'submit'.
 *     @type string $name_submit          The comment submit element name attribute. Default 'submit'.
 *     @type string $title_reply          The translatable 'reply' button label. Default 'Leave a Reply'.
 *     @type string $title_reply_to       The translatable 'reply-to' button label. Default 'Leave a Reply to %s',
 *                                        where %s is the author of the comment being replied to.
 *     @type string $title_reply_before   HTML displayed before the comment form title.
 *                                        Default: '<h3 id="reply-title" class="comment-reply-title">'.
 *     @type string $title_reply_after    HTML displayed after the comment form title.
 *                                        Default: '</h3>'.
 *     @type string $cancel_reply_before  HTML displayed before the cancel reply link.
 *     @type string $cancel_reply_after   HTML displayed after the cancel reply link.
 *     @type string $cancel_reply_link    The translatable 'cancel reply' button label. Default 'Cancel reply'.
 *     @type string $label_submit         The translatable 'submit' button label. Default 'Post a comment'.
 *     @type string $submit_button        HTML format for the Submit button.
 *                                        Default: '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />'.
 *     @type string $submit_field         HTML format for the markup surrounding the Submit button and comment hidden
 *                                        fields. Default: '<p class="form-submit">%1$s %2$s</p>', where %1$s is the
 *                                        submit button markup and %2$s is the comment hidden fields.
 *     @type string $format               The comment form format. Default 'xhtml'. Accepts 'xhtml', 'html5'.
 * }
 * @param int|WP_Post $post Optional. Post ID or WP_Post object to generate the form for. Default current post.
 */
function comment_form( $args = array(), $post = null ) {
	$post = get_post( $post );

	// Exit the function if the post is invalid or comments are closed.
	if ( ! $post || ! comments_open( $post ) ) {
		/**
		 * Fires after the comment form if comments are closed.
		 *
		 * For backward compatibility, this action also fires if comment_form()
		 * is called with an invalid post object or ID.
		 *
		 * @since 3.0.0
		 */
		do_action( 'comment_form_comments_closed' );

		return;
	}

	$post_id       = $post->ID;
	$commenter     = wp_get_current_commenter();
	$user          = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : '';

	$args = wp_parse_args( $args );
	if ( ! isset( $args['format'] ) ) {
		$args['format'] = current_theme_supports( 'html5', 'comment-form' ) ? 'html5' : 'xhtml';
	}

	$req   = get_option( 'require_name_email' );
	$html5 = 'html5' === $args['format'];

	// Define attributes in HTML5 or XHTML syntax.
	$required_attribute = ( $html5 ? ' required' : ' required="required"' );
	$checked_attribute  = ( $html5 ? ' checked' : ' checked="checked"' );

	// Identify required fields visually and create a message about the indicator.
	$required_indicator = ' ' . wp_required_field_indicator();
	$required_text      = ' ' . wp_required_field_message();

	$fields = array(
		'author' => sprintf(
			'<p class="comment-form-author">%s %s</p>',
			sprintf(
				'<label for="author">%s%s</label>',
				__( 'Name' ),
				( $req ? $required_indicator : '' )
			),
			sprintf(
				'<input id="author" name="author" type="text" value="%s" size="30" maxlength="245" autocomplete="name"%s />',
				esc_attr( $commenter['comment_author'] ),
				( $req ? $required_attribute : '' )
			)
		),
		'email'  => sprintf(
			'<p class="comment-form-email">%s %s</p>',
			sprintf(
				'<label for="email">%s%s</label>',
				__( 'Email' ),
				( $req ? $required_indicator : '' )
			),
			sprintf(
				'<input id="email" name="email" %s value="%s" size="30" maxlength="100" aria-describedby="email-notes" autocomplete="email"%s />',
				( $html5 ? 'type="email"' : 'type="text"' ),
				esc_attr( $commenter['comment_author_email'] ),
				( $req ? $required_attribute : '' )
			)
		),
		'url'    => sprintf(
			'<p class="comment-form-url">%s %s</p>',
			sprintf(
				'<label for="url">%s</label>',
				__( 'Website' )
			),
			sprintf(
				'<input id="url" name="url" %s value="%s" size="30" maxlength="200" autocomplete="url" />',
				( $html5 ? 'type="url"' : 'type="text"' ),
				esc_attr( $commenter['comment_author_url'] )
			)
		),
	);

	if ( has_action( 'set_comment_cookies', 'wp_set_comment_cookies' ) && get_option( 'show_comments_cookies_opt_in' ) ) {
		$consent = empty( $commenter['comment_author_email'] ) ? '' : $checked_attribute;

		$fields['cookies'] = sprintf(
			'<p class="comment-form-cookies-consent">%s %s</p>',
			sprintf(
				'<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s />',
				$consent
			),
			sprintf(
				'<label for="wp-comment-cookies-consent">%s</label>',
				__( 'Save my name, email, and website in this browser for the next time I comment.' )
			)
		);

		// Ensure that the passed fields include cookies consent.
		if ( isset( $args['fields'] ) && ! isset( $args['fields']['cookies'] ) ) {
			$args['fields']['cookies'] = $fields['cookies'];
		}
	}

	/**
	 * Filters the default comment form fields.
	 *
	 * @since 3.0.0
	 *
	 * @param string[] $fields Array of the default comment fields.
	 */
	$fields = apply_filters( 'comment_form_default_fields', $fields );

	$defaults = array(
		'fields'               => $fields,
		'comment_field'        => sprintf(
			'<p class="comment-form-comment">%s %s</p>',
			sprintf(
				'<label for="comment">%s%s</label>',
				_x( 'Comment', 'noun' ),
				$required_indicator
			),
			'<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525"' . $required_attribute . '></textarea>'
		),
		'must_log_in'          => sprintf(
			'<p class="must-log-in">%s</p>',
			sprintf(
				/* translators: %s: Login URL. */
				__( 'You must be <a href="%s">logged in</a> to post a comment.' ),
				/** This filter is documented in wp-includes/link-template.php */
				wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ), $post_id ) )
			)
		),
		'logged_in_as'         => sprintf(
			'<p class="logged-in-as">%s%s</p>',
			sprintf(
				/* translators: 1: User name, 2: Edit user link, 3: Logout URL. */
				__( 'Logged in as %1$s. <a href="%2$s">Edit your profile</a>. <a href="%3$s">Log out?</a>' ),
				$user_identity,
				get_edit_user_link(),
				/** This filter is documented in wp-includes/link-template.php */
				wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ), $post_id ) )
			),
			$required_text
		),
		'comment_notes_before' => sprintf(
			'<p class="comment-notes">%s%s</p>',
			sprintf(
				'<span id="email-notes">%s</span>',
				__( 'Your email address will not be published.' )
			),
			$required_text
		),
		'comment_notes_after'  => '',
		'action'               => site_url( '/wp-comments-post.php' ),
		'novalidate'           => false,
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'class_container'      => 'comment-respond',
		'class_form'           => 'comment-form',
		'class_submit'         => 'submit',
		'name_submit'          => 'submit',
		'title_reply'          => __( 'Leave a Reply' ),
		/* translators: %s: Author of the comment being replied to. */
		'title_reply_to'       => __( 'Leave a Reply to %s' ),
		'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title">',
		'title_reply_after'    => '</h3>',
		'cancel_reply_before'  => ' <small>',
		'cancel_reply_after'   => '</small>',
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => __( 'Post Comment' ),
		'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
		'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
		'format'               => 'xhtml',
	);

	/**
	 * Filters the comment form default arguments.
	 *
	 * Use {@see 'comment_form_default_fields'} to filter the comment fields.
	 *
	 * @since 3.0.0
	 *
	 * @param array $defaults The default comment form arguments.
	 */
	$args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );

	// Ensure that the filtered arguments contain all required default values.
	$args = array_merge( $defaults, $args );

	// Remove `aria-describedby` from the email field if there's no associated description.
	if ( isset( $args['fields']['email'] ) && ! str_contains( $args['comment_notes_before'], 'id="email-notes"' ) ) {
		$args['fields']['email'] = str_replace(
			' aria-describedby="email-notes"',
			'',
			$args['fields']['email']
		);
	}

	/**
	 * Fires before the comment form.
	 *
	 * @since 3.0.0
	 */
	do_action( 'comment_form_before' );
	?>
	<div id="respond" class="<?php echo esc_attr( $args['class_container'] ); ?>">
		<?php
		echo $args['title_reply_before'];

		comment_form_title( $args['title_reply'], $args['title_reply_to'], true, $post_id );

		if ( get_option( 'thread_comments' ) ) {
			echo $args['cancel_reply_before'];

			cancel_comment_reply_link( $args['cancel_reply_link'] );

			echo $args['cancel_reply_after'];
		}

		echo $args['title_reply_after'];

		if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) :

			echo $args['must_log_in'];
			/**
			 * Fires after the HTML-formatted 'must log in after' message in the comment form.
			 *
			 * @since 3.0.0
			 */
			do_action( 'comment_form_must_log_in_after' );

		else :

			printf(
				'<form action="%s" method="post" id="%s" class="%s"%s>',
				esc_url( $args['action'] ),
				esc_attr( $args['id_form'] ),
				esc_attr( $args['class_form'] ),
				( $args['novalidate'] ? ' novalidate' : '' )
			);

			/**
			 * Fires at the top of the comment form, inside the form tag.
			 *
			 * @since 3.0.0
			 */
			do_action( 'comment_form_top' );

			if ( is_user_logged_in() ) :

				/**
				 * Filters the 'logged in' message for the comment form for display.
				 *
				 * @since 3.0.0
				 *
				 * @param string $args_logged_in The HTML for the 'logged in as [user]' message,
				 *                               the Edit profile link, and the Log out link.
				 * @param array  $commenter      An array containing the comment author's
				 *                               username, email, and URL.
				 * @param string $user_identity  If the commenter is a registered user,
				 *                               the display name, blank otherwise.
				 */
				echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity );

				/**
				 * Fires after the is_user_logged_in() check in the comment form.
				 *
				 * @since 3.0.0
				 *
				 * @param array  $commenter     An array containing the comment author's
				 *                              username, email, and URL.
				 * @param string $user_identity If the commenter is a registered user,
				 *                              the display name, blank otherwise.
				 */
				do_action( 'comment_form_logged_in_after', $commenter, $user_identity );

			else :

				echo $args['comment_notes_before'];

			endif;

			// Prepare an array of all fields, including the textarea.
			$comment_fields = array( 'comment' => $args['comment_field'] ) + (array) $args['fields'];

			/**
			 * Filters the comment form fields, including the textarea.
			 *
			 * @since 4.4.0
			 *
			 * @param array $comment_fields The comment fields.
			 */
			$comment_fields = apply_filters( 'comment_form_fields', $comment_fields );

			// Get an array of field names, excluding the textarea.
			$comment_field_keys = array_diff( array_keys( $comment_fields ), array( 'comment' ) );

			// Get the first and the last field name, excluding the textarea.
			$first_field = reset( $comment_field_keys );
			$last_field  = end( $comment_field_keys );

			foreach ( $comment_fields as $name => $field ) {

				if ( 'comment' === $name ) {

					/**
					 * Filters the content of the comment textarea field for display.
					 *
					 * @since 3.0.0
					 *
					 * @param string $args_comment_field The content of the comment textarea field.
					 */
					echo apply_filters( 'comment_form_field_comment', $field );

					echo $args['comment_notes_after'];

				} elseif ( ! is_user_logged_in() ) {

					if ( $first_field === $name ) {
						/**
						 * Fires before the comment fields in the comment form, excluding the textarea.
						 *
						 * @since 3.0.0
						 */
						do_action( 'comment_form_before_fields' );
					}

					/**
					 * Filters a comment form field for display.
					 *
					 * The dynamic portion of the hook name, `$name`, refers to the name
					 * of the comment form field.
					 *
					 * Possible hook names include:
					 *
					 *  - `comment_form_field_comment`
					 *  - `comment_form_field_author`
					 *  - `comment_form_field_email`
					 *  - `comment_form_field_url`
					 *  - `comment_form_field_cookies`
					 *
					 * @since 3.0.0
					 *
					 * @param string $field The HTML-formatted output of the comment form field.
					 */
					echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";

					if ( $last_field === $name ) {
						/**
						 * Fires after the comment fields in the comment form, excluding the textarea.
						 *
						 * @since 3.0.0
						 */
						do_action( 'comment_form_after_fields' );
					}
				}
			}

			$submit_button = sprintf(
				$args['submit_button'],
				esc_attr( $args['name_submit'] ),
				esc_attr( $args['id_submit'] ),
				esc_attr( $args['class_submit'] ),
				esc_attr( $args['label_submit'] )
			);

			/**
			 * Filters the submit button for the comment form to display.
			 *
			 * @since 4.2.0
			 *
			 * @param string $submit_button HTML markup for the submit button.
			 * @param array  $args          Arguments passed to comment_form().
			 */
			$submit_button = apply_filters( 'comment_form_submit_button', $submit_button, $args );

			$submit_field = sprintf(
				$args['submit_field'],
				$submit_button,
				get_comment_id_fields( $post_id )
			);

			/**
			 * Filters the submit field for the comment form to display.
			 *
			 * The submit field includes the submit button, hidden fields for the
			 * comment form, and any wrapper markup.
			 *
			 * @since 4.2.0
			 *
			 * @param string $submit_field HTML markup for the submit field.
			 * @param array  $args         Arguments passed to comment_form().
			 */
			echo apply_filters( 'comment_form_submit_field', $submit_field, $args );

			/**
			 * Fires at the bottom of the comment form, inside the closing form tag.
			 *
			 * @since 1.5.0
			 *
			 * @param int $post_id The post ID.
			 */
			do_action( 'comment_form', $post_id );

			echo '</form>';

		endif;
		?>
	</div><!-- #respond -->
	<?php

	/**
	 * Fires after the comment form.
	 *
	 * @since 3.0.0
	 */
	do_action( 'comment_form_after' );
}
