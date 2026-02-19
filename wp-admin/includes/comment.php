<?php
/**
 * API Quản trị Bình luận WordPress.
 *
 * @package WordPress
 * @subpackage Administration
 * @since 2.3.0
 */

/**
 * Xác định xem một bình luận có tồn tại hay không dựa trên tác giả và ngày.
 *
 * Để có hiệu suất tốt nhất, sử dụng `$timezone = 'gmt'`, truy vấn trường được lập chỉ mục đúng cách.
 * Giá trị mặc định cho `$timezone` là 'blog' vì lý do tương thích ngược.
 *
 * @since 2.0.0
 * @since 4.4.0 Thêm tham số `$timezone`.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $comment_author Tác giả của bình luận.
 * @param string $comment_date   Ngày của bình luận.
 * @param string $timezone       Múi giờ. Chấp nhận 'blog' hoặc 'gmt'. Mặc định 'blog'.
 * @return string|null ID bài viết của bình luận khi thành công.
 */
function comment_exists( $comment_author, $comment_date, $timezone = 'blog' ) {
	global $wpdb;

	$date_field = 'comment_date';
	if ( 'gmt' === $timezone ) {
		$date_field = 'comment_date_gmt';
	}

	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT comment_post_ID FROM $wpdb->comments
			WHERE comment_author = %s AND $date_field = %s",
			stripslashes( $comment_author ),
			stripslashes( $comment_date )
		)
	);
}

/**
 * Cập nhật bình luận với các giá trị được cung cấp trong $_POST.
 *
 * @since 2.0.0
 * @since 5.5.0 Đã thêm giá trị trả về.
 *
 * @return int|WP_Error Giá trị 1 nếu bình luận đã được cập nhật, 0 nếu không được cập nhật.
 *                      Đối tượng WP_Error khi thất bại.
 */
function edit_comment() {
	if ( ! current_user_can( 'edit_comment', (int) $_POST['comment_ID'] ) ) {
		wp_die( __( 'Sorry, you are not allowed to edit comments on this post.' ) );
	}

	if ( isset( $_POST['newcomment_author'] ) ) {
		$_POST['comment_author'] = $_POST['newcomment_author'];
	}
	if ( isset( $_POST['newcomment_author_email'] ) ) {
		$_POST['comment_author_email'] = $_POST['newcomment_author_email'];
	}
	if ( isset( $_POST['newcomment_author_url'] ) ) {
		$_POST['comment_author_url'] = $_POST['newcomment_author_url'];
	}
	if ( isset( $_POST['comment_status'] ) ) {
		$_POST['comment_approved'] = $_POST['comment_status'];
	}
	if ( isset( $_POST['content'] ) ) {
		$_POST['comment_content'] = $_POST['content'];
	}
	if ( isset( $_POST['comment_ID'] ) ) {
		$_POST['comment_ID'] = (int) $_POST['comment_ID'];
	}

	foreach ( array( 'aa', 'mm', 'jj', 'hh', 'mn' ) as $timeunit ) {
		if ( ! empty( $_POST[ 'hidden_' . $timeunit ] ) && $_POST[ 'hidden_' . $timeunit ] !== $_POST[ $timeunit ] ) {
			$_POST['edit_date'] = '1';
			break;
		}
	}

	if ( ! empty( $_POST['edit_date'] ) ) {
		$aa = $_POST['aa'];
		$mm = $_POST['mm'];
		$jj = $_POST['jj'];
		$hh = $_POST['hh'];
		$mn = $_POST['mn'];
		$ss = $_POST['ss'];
		$jj = ( $jj > 31 ) ? 31 : $jj;
		$hh = ( $hh > 23 ) ? $hh - 24 : $hh;
		$mn = ( $mn > 59 ) ? $mn - 60 : $mn;
		$ss = ( $ss > 59 ) ? $ss - 60 : $ss;

		$_POST['comment_date'] = "$aa-$mm-$jj $hh:$mn:$ss";
	}

	return wp_update_comment( $_POST, true );
}

/**
 * Trả về đối tượng WP_Comment dựa trên ID bình luận.
 *
 * @since 2.0.0
 *
 * @param int $id ID của bình luận cần lấy.
 * @return WP_Comment|false Bình luận nếu tìm thấy. False khi thất bại.
 */
function get_comment_to_edit( $id ) {
	$comment = get_comment( $id );
	if ( ! $comment ) {
		return false;
	}

	$comment->comment_ID      = (int) $comment->comment_ID;
	$comment->comment_post_ID = (int) $comment->comment_post_ID;

	$comment->comment_content = format_to_edit( $comment->comment_content );
	/**
	 * Lọc nội dung bình luận trước khi chỉnh sửa.
	 *
	 * @since 2.0.0
	 *
	 * @param string $comment_content Nội dung bình luận.
	 */
	$comment->comment_content = apply_filters( 'comment_edit_pre', $comment->comment_content );

	$comment->comment_author       = format_to_edit( $comment->comment_author );
	$comment->comment_author_email = format_to_edit( $comment->comment_author_email );
	$comment->comment_author_url   = format_to_edit( $comment->comment_author_url );
	$comment->comment_author_url   = esc_url( $comment->comment_author_url );

	return $comment;
}

/**
 * Lấy số lượng bình luận đang chờ duyệt trên một hoặc nhiều bài viết.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|int[] $post_id Một ID bài viết đơn hoặc mảng các ID bài viết.
 * @return int|int[] Số bình luận chờ duyệt dạng int hoặc mảng int được đánh khóa theo ID bài viết.
 */
function get_pending_comments_num( $post_id ) {
	global $wpdb;

	$single = false;
	if ( ! is_array( $post_id ) ) {
		$post_id_array = (array) $post_id;
		$single        = true;
	} else {
		$post_id_array = $post_id;
	}
	$post_id_array = array_map( 'intval', $post_id_array );
	$post_id_in    = "'" . implode( "', '", $post_id_array ) . "'";

	$pending = $wpdb->get_results( "SELECT comment_post_ID, COUNT(comment_ID) as num_comments FROM $wpdb->comments WHERE comment_post_ID IN ( $post_id_in ) AND comment_approved = '0' GROUP BY comment_post_ID", ARRAY_A );

	if ( $single ) {
		if ( empty( $pending ) ) {
			return 0;
		} else {
			return absint( $pending[0]['num_comments'] );
		}
	}

	$pending_keyed = array();

	// Mặc định không có bình luận chờ duyệt cho tất cả bài viết trong yêu cầu.
	foreach ( $post_id_array as $id ) {
		$pending_keyed[ $id ] = 0;
	}

	if ( ! empty( $pending ) ) {
		foreach ( $pending as $pend ) {
			$pending_keyed[ $pend['comment_post_ID'] ] = absint( $pend['num_comments'] );
		}
	}

	return $pending_keyed;
}

/**
 * Thêm ảnh đại diện vào các vị trí liên quan trong trang quản trị.
 *
 * @since 2.5.0
 *
 * @param string $name Tên người dùng.
 * @return string Ảnh đại diện kèm tên người dùng.
 */
function floated_admin_avatar( $name ) {
	$avatar = get_avatar( get_comment(), 32, 'mystery' );
	return "$avatar $name";
}

/**
 * Thêm vào hàng đợi script jQuery phím tắt bình luận.
 *
 * @since 2.7.0
 */
function enqueue_comment_hotkeys_js() {
	if ( 'true' === get_user_option( 'comment_shortcuts' ) ) {
		wp_enqueue_script( 'jquery-table-hotkeys' );
	}
}

/**
 * Hiển thị thông báo lỗi ở cuối phần bình luận.
 *
 * @param string $msg Thông báo lỗi. Giả định chứa HTML và đã được làm sạch.
 */
function comment_footer_die( $msg ) {
	echo "<div class='wrap'><p>$msg</p></div>";
	require_once ABSPATH . 'wp-admin/admin-footer.php';
	die;
}
