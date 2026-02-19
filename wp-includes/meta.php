<?php
/**
 * API Metadata cốt lõi
 *
 * Các hàm để lấy và thao tác metadata của các loại đối tượng WordPress khác nhau. Metadata
 * cho một đối tượng được biểu diễn bằng một cặp khóa-giá trị đơn giản. Các đối tượng có thể chứa nhiều
 * mục metadata chia sẻ cùng khóa và chỉ khác nhau về giá trị.
 *
 * @package WordPress
 * @subpackage Meta
 */

require ABSPATH . WPINC . '/class-wp-metadata-lazyloader.php';

/**
 * Thêm metadata cho đối tượng được chỉ định.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $meta_type  Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                           hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id  ID của đối tượng mà metadata dành cho.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Giá trị metadata. Mảng và đối tượng được lưu dưới dạng dữ liệu serialized và
 *                           sẽ được trả về cùng kiểu khi lấy ra. Các kiểu dữ liệu khác sẽ
 *                           được lưu dưới dạng chuỗi trong cơ sở dữ liệu:
 *                           - false được lưu và trả về dưới dạng chuỗi rỗng ('')
 *                           - true được lưu và trả về dưới dạng '1'
 *                           - số (cả integer và float) được lưu và trả về dưới dạng chuỗi
 *                           Phải có thể serialize nếu không phải scalar.
 * @param bool   $unique     Tùy chọn. Liệu khóa metadata được chỉ định có nên là duy nhất cho đối tượng.
 *                           Nếu true, và đối tượng đã có giá trị cho khóa metadata được chỉ định,
 *                           sẽ không có thay đổi nào. Mặc định false.
 * @return int|false ID meta khi thành công, false khi thất bại.
 */
function add_metadata( $meta_type, $object_id, $meta_key, $meta_value, $unique = false ) {
	global $wpdb;

	if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$meta_subtype = get_object_subtype( $meta_type, $object_id );

	$column = sanitize_key( $meta_type . '_id' );

	// expected_slashed ($meta_key)
	$meta_key   = wp_unslash( $meta_key );
	$meta_value = wp_unslash( $meta_value );
	$meta_value = sanitize_meta( $meta_key, $meta_value, $meta_type, $meta_subtype );

	/**
	 * Bỏ qua (short-circuit) việc thêm metadata của một loại cụ thể.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `add_post_metadata`
	 *  - `add_comment_metadata`
	 *  - `add_term_metadata`
	 *  - `add_user_metadata`
	 *
	 * @since 3.1.0
	 *
	 * @param null|bool $check      Có cho phép thêm metadata cho loại đã cho hay không.
	 * @param int       $object_id  ID của đối tượng mà metadata dành cho.
	 * @param string    $meta_key   Khóa metadata.
	 * @param mixed     $meta_value Giá trị metadata. Phải có thể serialize nếu không phải scalar.
	 * @param bool      $unique     Liệu khóa meta đã chỉ định có nên là duy nhất cho đối tượng.
	 */
	$check = apply_filters( "add_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $unique );
	if ( null !== $check ) {
		return $check;
	}

	if ( $unique && $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d",
			$meta_key,
			$object_id
		)
	) ) {
		return false;
	}

	$_meta_value = $meta_value;
	$meta_value  = maybe_serialize( $meta_value );

	/**
	 * Kích hoạt ngay trước khi meta của một loại cụ thể được thêm.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `add_post_meta`
	 *  - `add_comment_meta`
	 *  - `add_term_meta`
	 *  - `add_user_meta`
	 *
	 * @since 3.1.0
	 *
	 * @param int    $object_id   ID của đối tượng mà metadata dành cho.
	 * @param string $meta_key    Khóa metadata.
	 * @param mixed  $_meta_value Giá trị metadata.
	 */
	do_action( "add_{$meta_type}_meta", $object_id, $meta_key, $_meta_value );

	$result = $wpdb->insert(
		$table,
		array(
			$column      => $object_id,
			'meta_key'   => $meta_key,
			'meta_value' => $meta_value,
		)
	);

	if ( ! $result ) {
		return false;
	}

	$mid = (int) $wpdb->insert_id;

	wp_cache_delete( $object_id, $meta_type . '_meta' );

	/**
	 * Kích hoạt ngay sau khi meta của một loại cụ thể được thêm.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `added_post_meta`
	 *  - `added_comment_meta`
	 *  - `added_term_meta`
	 *  - `added_user_meta`
	 *
	 * @since 2.9.0
	 *
	 * @param int    $mid         ID meta sau khi cập nhật thành công.
	 * @param int    $object_id   ID của đối tượng mà metadata dành cho.
	 * @param string $meta_key    Khóa metadata.
	 * @param mixed  $_meta_value Giá trị metadata.
	 */
	do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value );

	return $mid;
}

/**
 * Cập nhật metadata cho đối tượng được chỉ định. Nếu chưa có giá trị nào tồn tại cho
 * ID đối tượng và khóa metadata được chỉ định, metadata sẽ được thêm mới.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $meta_type  Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                           hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id  ID của đối tượng mà metadata dành cho.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Giá trị metadata. Phải có thể serialize nếu không phải scalar.
 * @param mixed  $prev_value Tùy chọn. Giá trị trước đó để kiểm tra trước khi cập nhật.
 *                           Nếu được chỉ định, chỉ cập nhật các mục metadata hiện có với
 *                           giá trị này. Nếu không, cập nhật tất cả các mục. Mặc định chuỗi rỗng.
 * @return int|bool ID trường meta mới nếu trường với khóa đã cho không tồn tại
 *                  và do đó được thêm mới, true khi cập nhật thành công,
 *                  false khi thất bại hoặc nếu giá trị truyền vào hàm
 *                  giống với giá trị đã có trong cơ sở dữ liệu.
 */
function update_metadata( $meta_type, $object_id, $meta_key, $meta_value, $prev_value = '' ) {
	global $wpdb;

	if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$meta_subtype = get_object_subtype( $meta_type, $object_id );

	$column    = sanitize_key( $meta_type . '_id' );
	$id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

	// expected_slashed ($meta_key)
	$raw_meta_key = $meta_key;
	$meta_key     = wp_unslash( $meta_key );
	$passed_value = $meta_value;
	$meta_value   = wp_unslash( $meta_value );
	$meta_value   = sanitize_meta( $meta_key, $meta_value, $meta_type, $meta_subtype );

	/**
	 * Bỏ qua (short-circuit) việc cập nhật metadata của một loại cụ thể.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `update_post_metadata`
	 *  - `update_comment_metadata`
	 *  - `update_term_metadata`
	 *  - `update_user_metadata`
	 *
	 * @since 3.1.0
	 *
	 * @param null|bool $check      Có cho phép cập nhật metadata cho loại đã cho hay không.
	 * @param int       $object_id  ID của đối tượng mà metadata dành cho.
	 * @param string    $meta_key   Khóa metadata.
	 * @param mixed     $meta_value Giá trị metadata. Phải có thể serialize nếu không phải scalar.
	 * @param mixed     $prev_value Tùy chọn. Giá trị trước đó để kiểm tra trước khi cập nhật.
	 *                              Nếu được chỉ định, chỉ cập nhật các mục metadata hiện có với
	 *                              giá trị này. Nếu không, cập nhật tất cả các mục.
	 */
	$check = apply_filters( "update_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $prev_value );
	if ( null !== $check ) {
		return (bool) $check;
	}

	// So sánh giá trị hiện có với giá trị mới nếu không có giá trị trước đó và khóa chỉ tồn tại một lần.
	if ( empty( $prev_value ) ) {
		$old_value = get_metadata_raw( $meta_type, $object_id, $meta_key );
		if ( is_countable( $old_value ) && count( $old_value ) === 1 ) {
			if ( $old_value[0] === $meta_value ) {
				return false;
			}
		}
	}

	$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id ) );
	if ( empty( $meta_ids ) ) {
		return add_metadata( $meta_type, $object_id, $raw_meta_key, $passed_value );
	}

	$_meta_value = $meta_value;
	$meta_value  = maybe_serialize( $meta_value );

	$data  = compact( 'meta_value' );
	$where = array(
		$column    => $object_id,
		'meta_key' => $meta_key,
	);

	if ( ! empty( $prev_value ) ) {
		$prev_value          = maybe_serialize( $prev_value );
		$where['meta_value'] = $prev_value;
	}

	foreach ( $meta_ids as $meta_id ) {
		/**
		 * Kích hoạt ngay trước khi cập nhật metadata của một loại cụ thể.
		 *
		 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
		 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `update_post_meta`
		 *  - `update_comment_meta`
		 *  - `update_term_meta`
		 *  - `update_user_meta`
		 *
		 * @since 2.9.0
		 *
		 * @param int    $meta_id     ID của mục metadata cần cập nhật.
		 * @param int    $object_id   ID của đối tượng mà metadata dành cho.
		 * @param string $meta_key    Khóa metadata.
		 * @param mixed  $_meta_value Giá trị metadata.
		 */
		do_action( "update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		if ( 'post' === $meta_type ) {
			/**
			 * Kích hoạt ngay trước khi cập nhật metadata của bài viết.
			 *
			 * @since 2.9.0
			 *
			 * @param int    $meta_id    ID của mục metadata cần cập nhật.
			 * @param int    $object_id  ID bài viết.
			 * @param string $meta_key   Khóa metadata.
			 * @param mixed  $meta_value Giá trị metadata. Đây sẽ là chuỗi đại diện PHP-serialized của giá trị
			 *                           nếu giá trị là mảng, đối tượng, hoặc chính nó là chuỗi PHP-serialized.
			 */
			do_action( 'update_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
		}
	}

	$result = $wpdb->update( $table, $data, $where );
	if ( ! $result ) {
		return false;
	}

	wp_cache_delete( $object_id, $meta_type . '_meta' );

	foreach ( $meta_ids as $meta_id ) {
		/**
		 * Kích hoạt ngay sau khi cập nhật metadata của một loại cụ thể.
		 *
		 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
		 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `updated_post_meta`
		 *  - `updated_comment_meta`
		 *  - `updated_term_meta`
		 *  - `updated_user_meta`
		 *
		 * @since 2.9.0
		 *
		 * @param int    $meta_id     ID của mục metadata đã cập nhật.
		 * @param int    $object_id   ID của đối tượng mà metadata dành cho.
		 * @param string $meta_key    Khóa metadata.
		 * @param mixed  $_meta_value Giá trị metadata.
		 */
		do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		if ( 'post' === $meta_type ) {
			/**
			 * Kích hoạt ngay sau khi cập nhật metadata của bài viết.
			 *
			 * @since 2.9.0
			 *
			 * @param int    $meta_id    ID của mục metadata đã cập nhật.
			 * @param int    $object_id  ID bài viết.
			 * @param string $meta_key   Khóa metadata.
			 * @param mixed  $meta_value Giá trị metadata. Đây sẽ là chuỗi đại diện PHP-serialized của giá trị
			 *                           nếu giá trị là mảng, đối tượng, hoặc chính nó là chuỗi PHP-serialized.
			 */
			do_action( 'updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
		}
	}

	return true;
}

/**
 * Xóa metadata cho đối tượng được chỉ định.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $meta_type  Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                           hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id  ID của đối tượng mà metadata dành cho.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Tùy chọn. Giá trị metadata. Phải có thể serialize nếu không phải scalar.
 *                           Nếu được chỉ định, chỉ xóa các mục metadata có giá trị này.
 *                           Nếu không, xóa tất cả các mục với meta_key đã chỉ định.
 *                           Truyền `null`, `false`, hoặc chuỗi rỗng để bỏ qua kiểm tra này.
 *                           (Để tương thích ngược, không thể truyền chuỗi rỗng
 *                           để xóa các mục có giá trị là chuỗi rỗng.)
 *                           Mặc định chuỗi rỗng.
 * @param bool   $delete_all Tùy chọn. Nếu true, xóa các mục metadata khớp cho tất cả đối tượng,
 *                           bỏ qua object_id đã chỉ định. Nếu không, chỉ xóa
 *                           các mục metadata khớp cho object_id đã chỉ định. Mặc định false.
 * @return bool True khi xóa thành công, false khi thất bại.
 */
function delete_metadata( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
	global $wpdb;

	if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) && ! $delete_all ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id && ! $delete_all ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$type_column = sanitize_key( $meta_type . '_id' );
	$id_column   = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

	// expected_slashed ($meta_key)
	$meta_key   = wp_unslash( $meta_key );
	$meta_value = wp_unslash( $meta_value );

	/**
	 * Bỏ qua (short-circuit) việc xóa metadata của một loại cụ thể.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `delete_post_metadata`
	 *  - `delete_comment_metadata`
	 *  - `delete_term_metadata`
	 *  - `delete_user_metadata`
	 *
	 * @since 3.1.0
	 *
	 * @param null|bool $delete     Có cho phép xóa metadata của loại đã cho hay không.
	 * @param int       $object_id  ID của đối tượng mà metadata dành cho.
	 * @param string    $meta_key   Khóa metadata.
	 * @param mixed     $meta_value Giá trị metadata. Phải có thể serialize nếu không phải scalar.
	 * @param bool      $delete_all Có xóa các mục metadata khớp cho tất cả đối tượng hay không,
	 *                              bỏ qua $object_id đã chỉ định.
	 *                              Mặc định false.
	 */
	$check = apply_filters( "delete_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $delete_all );
	if ( null !== $check ) {
		return (bool) $check;
	}

	$_meta_value = $meta_value;
	$meta_value  = maybe_serialize( $meta_value );

	$query = $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key );

	if ( ! $delete_all ) {
		$query .= $wpdb->prepare( " AND $type_column = %d", $object_id );
	}

	if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
		$query .= $wpdb->prepare( ' AND meta_value = %s', $meta_value );
	}

	$meta_ids = $wpdb->get_col( $query );
	if ( ! count( $meta_ids ) ) {
		return false;
	}

	if ( $delete_all ) {
		if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
			$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );
		} else {
			$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key ) );
		}
	}

	/**
	 * Kích hoạt ngay trước khi xóa metadata của một loại cụ thể.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `delete_post_meta`
	 *  - `delete_comment_meta`
	 *  - `delete_term_meta`
	 *  - `delete_user_meta`
	 *
	 * @since 3.1.0
	 *
	 * @param string[] $meta_ids    Mảng các ID mục metadata cần xóa.
	 * @param int      $object_id   ID của đối tượng mà metadata dành cho.
	 * @param string   $meta_key    Khóa metadata.
	 * @param mixed    $_meta_value Giá trị metadata.
	 */
	do_action( "delete_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

	// Action kiểu cũ.
	if ( 'post' === $meta_type ) {
		/**
		 * Kích hoạt ngay trước khi xóa metadata cho bài viết.
		 *
		 * @since 2.9.0
		 *
		 * @param string[] $meta_ids Mảng các ID mục metadata cần xóa.
		 */
		do_action( 'delete_postmeta', $meta_ids );
	}

	$query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . ' )';

	$count = $wpdb->query( $query );

	if ( ! $count ) {
		return false;
	}

	if ( $delete_all ) {
		$data = (array) $object_ids;
	} else {
		$data = array( $object_id );
	}
	wp_cache_delete_multiple( $data, $meta_type . '_meta' );

	/**
	 * Kích hoạt ngay sau khi xóa metadata của một loại cụ thể.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `deleted_post_meta`
	 *  - `deleted_comment_meta`
	 *  - `deleted_term_meta`
	 *  - `deleted_user_meta`
	 *
	 * @since 2.9.0
	 *
	 * @param string[] $meta_ids    Mảng các ID mục metadata đã xóa.
	 * @param int      $object_id   ID của đối tượng mà metadata dành cho.
	 * @param string   $meta_key    Khóa metadata.
	 * @param mixed    $_meta_value Giá trị metadata.
	 */
	do_action( "deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

	// Action kiểu cũ.
	if ( 'post' === $meta_type ) {
		/**
		 * Kích hoạt ngay sau khi xóa metadata cho bài viết.
		 *
		 * @since 2.9.0
		 *
		 * @param string[] $meta_ids Mảng các ID mục metadata đã xóa.
		 */
		do_action( 'deleted_postmeta', $meta_ids );
	}

	return true;
}

/**
 * Lấy giá trị của trường metadata cho loại đối tượng và ID được chỉ định.
 *
 * Nếu trường meta tồn tại, một giá trị đơn được trả về nếu `$single` là true,
 * hoặc mảng các giá trị nếu là false.
 *
 * Nếu trường meta không tồn tại, kết quả phụ thuộc vào get_metadata_default().
 * Mặc định, chuỗi rỗng được trả về nếu `$single` là true, hoặc mảng rỗng
 * nếu là false.
 *
 * @since 2.9.0
 *
 * @see get_metadata_raw()
 * @see get_metadata_default()
 *
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id ID của đối tượng mà metadata dành cho.
 * @param string $meta_key  Tùy chọn. Khóa metadata. Nếu không chỉ định, lấy tất cả metadata cho
 *                          đối tượng được chỉ định. Mặc định chuỗi rỗng.
 * @param bool   $single    Tùy chọn. Nếu true, chỉ trả về giá trị đầu tiên của `$meta_key` được chỉ định.
 *                          Tham số này không có hiệu lực nếu `$meta_key` không được chỉ định. Mặc định false.
 * @return mixed Mảng các giá trị nếu `$single` là false.
 *               Giá trị của trường meta nếu `$single` là true.
 *               False cho `$object_id` không hợp lệ (không phải số, bằng không, hoặc giá trị âm),
 *               hoặc nếu `$meta_type` không được chỉ định.
 *               Mảng rỗng nếu ID đối tượng hợp lệ nhưng không tồn tại và `$single` là false.
 *               Chuỗi rỗng nếu ID đối tượng hợp lệ nhưng không tồn tại và `$single` là true.
 *               Lưu ý: Các giá trị không serialize được trả về dưới dạng chuỗi:
 *               - giá trị false được trả về dưới dạng chuỗi rỗng ('')
 *               - giá trị true được trả về dưới dạng '1'
 *               - số (cả integer và float) được trả về dưới dạng chuỗi
 *               Mảng và đối tượng giữ nguyên kiểu ban đầu.
 */
function get_metadata( $meta_type, $object_id, $meta_key = '', $single = false ) {
	$value = get_metadata_raw( $meta_type, $object_id, $meta_key, $single );
	if ( ! is_null( $value ) ) {
		return $value;
	}

	return get_metadata_default( $meta_type, $object_id, $meta_key, $single );
}

/**
 * Lấy giá trị metadata thô cho đối tượng được chỉ định.
 *
 * @since 5.5.0
 *
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id ID của đối tượng mà metadata dành cho.
 * @param string $meta_key  Tùy chọn. Khóa metadata. Nếu không chỉ định, lấy tất cả metadata cho
 *                          đối tượng được chỉ định. Mặc định chuỗi rỗng.
 * @param bool   $single    Tùy chọn. Nếu true, chỉ trả về giá trị đầu tiên của `$meta_key` được chỉ định.
 *                          Tham số này không có hiệu lực nếu `$meta_key` không được chỉ định. Mặc định false.
 * @return mixed Mảng các giá trị nếu `$single` là false.
 *               Giá trị của trường meta nếu `$single` là true.
 *               False cho `$object_id` không hợp lệ (không phải số, bằng không, hoặc giá trị âm),
 *               hoặc nếu `$meta_type` không được chỉ định.
 *               Null nếu giá trị không tồn tại.
 */
function get_metadata_raw( $meta_type, $object_id, $meta_key = '', $single = false ) {
	if ( ! $meta_type || ! is_numeric( $object_id ) ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	/**
	 * Bỏ qua (short-circuit) giá trị trả về của trường meta.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên filter có thể bao gồm:
	 *
	 *  - `get_post_metadata`
	 *  - `get_comment_metadata`
	 *  - `get_term_metadata`
	 *  - `get_user_metadata`
	 *
	 * @since 3.1.0
	 * @since 5.5.0 Thêm tham số `$meta_type`.
	 *
	 * @param mixed  $value     Giá trị để trả về, có thể là giá trị metadata đơn hoặc mảng
	 *                          các giá trị tùy thuộc vào giá trị của `$single`. Mặc định null.
	 * @param int    $object_id ID của đối tượng mà metadata dành cho.
	 * @param string $meta_key  Khóa metadata.
	 * @param bool   $single    Có chỉ trả về giá trị đầu tiên của `$meta_key` được chỉ định hay không.
	 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
	 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
	 */
	$check = apply_filters( "get_{$meta_type}_metadata", null, $object_id, $meta_key, $single, $meta_type );
	if ( null !== $check ) {
		if ( $single && is_array( $check ) ) {
			return $check[0];
		} else {
			return $check;
		}
	}

	$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

	if ( ! $meta_cache ) {
		$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
		if ( isset( $meta_cache[ $object_id ] ) ) {
			$meta_cache = $meta_cache[ $object_id ];
		} else {
			$meta_cache = null;
		}
	}

	if ( ! $meta_key ) {
		return $meta_cache;
	}

	if ( isset( $meta_cache[ $meta_key ] ) ) {
		if ( $single ) {
			return maybe_unserialize( $meta_cache[ $meta_key ][0] );
		} else {
			return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
		}
	}

	return null;
}

/**
 * Lấy giá trị metadata mặc định cho khóa meta và đối tượng được chỉ định.
 *
 * Mặc định, chuỗi rỗng được trả về nếu `$single` là true, hoặc mảng rỗng
 * nếu là false.
 *
 * @since 5.5.0
 *
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id ID của đối tượng mà metadata dành cho.
 * @param string $meta_key  Khóa metadata.
 * @param bool   $single    Tùy chọn. Nếu true, chỉ trả về giá trị đầu tiên của `$meta_key` được chỉ định.
 *                          Tham số này không có hiệu lực nếu `$meta_key` không được chỉ định. Mặc định false.
 * @return mixed Mảng các giá trị mặc định nếu `$single` là false.
 *               Giá trị mặc định của trường meta nếu `$single` là true.
 */
function get_metadata_default( $meta_type, $object_id, $meta_key, $single = false ) {
	if ( $single ) {
		$value = '';
	} else {
		$value = array();
	}

	/**
	 * Lọc giá trị metadata mặc định cho khóa meta và đối tượng được chỉ định.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 *
	 * Các tên filter có thể bao gồm:
	 *
	 *  - `default_post_metadata`
	 *  - `default_comment_metadata`
	 *  - `default_term_metadata`
	 *  - `default_user_metadata`
	 *
	 * @since 5.5.0
	 *
	 * @param mixed  $value     Giá trị để trả về, có thể là giá trị metadata đơn hoặc mảng
	 *                          các giá trị tùy thuộc vào giá trị của `$single`.
	 * @param int    $object_id ID của đối tượng mà metadata dành cho.
	 * @param string $meta_key  Khóa metadata.
	 * @param bool   $single    Có chỉ trả về giá trị đầu tiên của `$meta_key` được chỉ định hay không.
	 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
	 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
	 */
	$value = apply_filters( "default_{$meta_type}_metadata", $value, $object_id, $meta_key, $single, $meta_type );

	if ( ! $single && ! wp_is_numeric_array( $value ) ) {
		$value = array( $value );
	}

	return $value;
}

/**
 * Xác định xem trường meta với khóa đã cho có tồn tại cho ID đối tượng đã cho hay không.
 *
 * @since 3.3.0
 *
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id ID của đối tượng mà metadata dành cho.
 * @param string $meta_key  Khóa metadata.
 * @return bool Liệu trường meta với khóa đã cho có tồn tại hay không.
 */
function metadata_exists( $meta_type, $object_id, $meta_key ) {
	if ( ! $meta_type || ! is_numeric( $object_id ) ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	/** Filter này được tài liệu hóa tại wp-includes/meta.php */
	$check = apply_filters( "get_{$meta_type}_metadata", null, $object_id, $meta_key, true, $meta_type );
	if ( null !== $check ) {
		return (bool) $check;
	}

	$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

	if ( ! $meta_cache ) {
		$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
		$meta_cache = $meta_cache[ $object_id ];
	}

	if ( isset( $meta_cache[ $meta_key ] ) ) {
		return true;
	}

	return false;
}

/**
 * Lấy metadata theo ID meta.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $meta_id   ID cho một hàng meta cụ thể.
 * @return stdClass|false {
 *     Đối tượng metadata, hoặc boolean `false` nếu metadata không tồn tại.
 *
 *     @type string $meta_key   Khóa meta.
 *     @type mixed  $meta_value Giá trị meta đã unserialize.
 *     @type string $meta_id    Tùy chọn. ID meta khi loại meta là bất kỳ giá trị nào ngoại trừ 'user'.
 *     @type string $umeta_id   Tùy chọn. ID meta khi loại meta là 'user'.
 *     @type string $post_id    Tùy chọn. ID đối tượng khi loại meta là 'post'.
 *     @type string $comment_id Tùy chọn. ID đối tượng khi loại meta là 'comment'.
 *     @type string $term_id    Tùy chọn. ID đối tượng khi loại meta là 'term'.
 *     @type string $user_id    Tùy chọn. ID đối tượng khi loại meta là 'user'.
 * }
 */
function get_metadata_by_mid( $meta_type, $meta_id ) {
	global $wpdb;

	if ( ! $meta_type || ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
		return false;
	}

	$meta_id = (int) $meta_id;
	if ( $meta_id <= 0 ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	/**
	 * Bỏ qua (short-circuit) giá trị trả về khi lấy trường meta theo ID meta.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_post_metadata_by_mid`
	 *  - `get_comment_metadata_by_mid`
	 *  - `get_term_metadata_by_mid`
	 *  - `get_user_metadata_by_mid`
	 *
	 * @since 5.0.0
	 *
	 * @param stdClass|null $value   Giá trị để trả về.
	 * @param int           $meta_id ID meta.
	 */
	$check = apply_filters( "get_{$meta_type}_metadata_by_mid", null, $meta_id );
	if ( null !== $check ) {
		return $check;
	}

	$id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

	$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE $id_column = %d", $meta_id ) );

	if ( empty( $meta ) ) {
		return false;
	}

	if ( isset( $meta->meta_value ) ) {
		$meta->meta_value = maybe_unserialize( $meta->meta_value );
	}

	return $meta;
}

/**
 * Cập nhật metadata theo ID meta.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string       $meta_type  Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                                 hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int          $meta_id    ID cho một hàng meta cụ thể.
 * @param string       $meta_value Giá trị metadata. Phải có thể serialize nếu không phải scalar.
 * @param string|false $meta_key   Tùy chọn. Bạn có thể cung cấp khóa meta để cập nhật nó. Mặc định false.
 * @return bool True khi cập nhật thành công, false khi thất bại.
 */
function update_metadata_by_mid( $meta_type, $meta_id, $meta_value, $meta_key = false ) {
	global $wpdb;

	// Đảm bảo mọi thứ đều hợp lệ.
	if ( ! $meta_type || ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
		return false;
	}

	$meta_id = (int) $meta_id;
	if ( $meta_id <= 0 ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$column    = sanitize_key( $meta_type . '_id' );
	$id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

	/**
	 * Bỏ qua (short-circuit) việc cập nhật metadata của một loại cụ thể theo ID meta.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `update_post_metadata_by_mid`
	 *  - `update_comment_metadata_by_mid`
	 *  - `update_term_metadata_by_mid`
	 *  - `update_user_metadata_by_mid`
	 *
	 * @since 5.0.0
	 *
	 * @param null|bool    $check      Có cho phép cập nhật metadata cho loại đã cho hay không.
	 * @param int          $meta_id    ID meta.
	 * @param mixed        $meta_value Giá trị meta. Phải có thể serialize nếu không phải scalar.
	 * @param string|false $meta_key   Khóa meta, nếu được cung cấp.
	 */
	$check = apply_filters( "update_{$meta_type}_metadata_by_mid", null, $meta_id, $meta_value, $meta_key );
	if ( null !== $check ) {
		return (bool) $check;
	}

	// Lấy meta và tiếp tục nếu tìm thấy.
	$meta = get_metadata_by_mid( $meta_type, $meta_id );
	if ( $meta ) {
		$original_key = $meta->meta_key;
		$object_id    = $meta->{$column};

		/*
		 * Nếu meta_key mới (tham số cuối) được chỉ định, thay đổi khóa meta,
		 * nếu không sử dụng khóa gốc trong câu lệnh cập nhật.
		 */
		if ( false === $meta_key ) {
			$meta_key = $original_key;
		} elseif ( ! is_string( $meta_key ) ) {
			return false;
		}

		$meta_subtype = get_object_subtype( $meta_type, $object_id );

		// Làm sạch meta.
		$_meta_value = $meta_value;
		$meta_value  = sanitize_meta( $meta_key, $meta_value, $meta_type, $meta_subtype );
		$meta_value  = maybe_serialize( $meta_value );

		// Định dạng các đối số truy vấn dữ liệu.
		$data = array(
			'meta_key'   => $meta_key,
			'meta_value' => $meta_value,
		);

		// Định dạng các đối số truy vấn where.
		$where               = array();
		$where[ $id_column ] = $meta_id;

		/** Action này được tài liệu hóa tại wp-includes/meta.php */
		do_action( "update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		if ( 'post' === $meta_type ) {
			/** Action này được tài liệu hóa tại wp-includes/meta.php */
			do_action( 'update_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
		}

		// Chạy truy vấn cập nhật, tất cả các trường trong $data là %s, $where là %d.
		$result = $wpdb->update( $table, $data, $where, '%s', '%d' );
		if ( ! $result ) {
			return false;
		}

		// Xóa bộ nhớ đệm.
		wp_cache_delete( $object_id, $meta_type . '_meta' );

		/** Action này được tài liệu hóa tại wp-includes/meta.php */
		do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		if ( 'post' === $meta_type ) {
			/** Action này được tài liệu hóa tại wp-includes/meta.php */
			do_action( 'updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
		}

		return true;
	}

	// Và nếu meta không được tìm thấy.
	return false;
}

/**
 * Xóa metadata theo ID meta.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $meta_id   ID cho một hàng meta cụ thể.
 * @return bool True khi xóa thành công, false khi thất bại.
 */
function delete_metadata_by_mid( $meta_type, $meta_id ) {
	global $wpdb;

	// Đảm bảo mọi thứ đều hợp lệ.
	if ( ! $meta_type || ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
		return false;
	}

	$meta_id = (int) $meta_id;
	if ( $meta_id <= 0 ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	// Các cột đối tượng và ID.
	$column    = sanitize_key( $meta_type . '_id' );
	$id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

	/**
	 * Bỏ qua (short-circuit) việc xóa metadata của một loại cụ thể theo ID meta.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `delete_post_metadata_by_mid`
	 *  - `delete_comment_metadata_by_mid`
	 *  - `delete_term_metadata_by_mid`
	 *  - `delete_user_metadata_by_mid`
	 *
	 * @since 5.0.0
	 *
	 * @param null|bool $delete  Có cho phép xóa metadata của loại đã cho hay không.
	 * @param int       $meta_id ID meta.
	 */
	$check = apply_filters( "delete_{$meta_type}_metadata_by_mid", null, $meta_id );
	if ( null !== $check ) {
		return (bool) $check;
	}

	// Lấy meta và tiếp tục nếu tìm thấy.
	$meta = get_metadata_by_mid( $meta_type, $meta_id );
	if ( $meta ) {
		$object_id = (int) $meta->{$column};

		/** Action này được tài liệu hóa tại wp-includes/meta.php */
		do_action( "delete_{$meta_type}_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value );

		// Action kiểu cũ.
		if ( 'post' === $meta_type || 'comment' === $meta_type ) {
			/**
			 * Kích hoạt ngay trước khi xóa metadata bài viết hoặc bình luận của một loại cụ thể.
			 *
			 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại
			 * đối tượng meta (post hoặc comment).
			 *
			 * Các tên hook có thể bao gồm:
			 *
			 *  - `delete_postmeta`
			 *  - `delete_commentmeta`
			 *  - `delete_termmeta`
			 *  - `delete_usermeta`
			 *
			 * @since 3.4.0
			 *
			 * @param int $meta_id ID của mục metadata cần xóa.
			 */
			do_action( "delete_{$meta_type}meta", $meta_id );
		}

		// Chạy truy vấn, sẽ trả về true nếu xóa thành công, false nếu không.
		$result = (bool) $wpdb->delete( $table, array( $id_column => $meta_id ) );

		// Xóa bộ nhớ đệm.
		wp_cache_delete( $object_id, $meta_type . '_meta' );

		/** Action này được tài liệu hóa tại wp-includes/meta.php */
		do_action( "deleted_{$meta_type}_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value );

		// Action kiểu cũ.
		if ( 'post' === $meta_type || 'comment' === $meta_type ) {
			/**
			 * Kích hoạt ngay sau khi xóa metadata bài viết hoặc bình luận của một loại cụ thể.
			 *
			 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại
			 * đối tượng meta (post hoặc comment).
			 *
			 * Các tên hook có thể bao gồm:
			 *
			 *  - `deleted_postmeta`
			 *  - `deleted_commentmeta`
			 *  - `deleted_termmeta`
			 *  - `deleted_usermeta`
			 *
			 * @since 3.4.0
			 *
			 * @param int $meta_id ID mục metadata đã xóa.
			 */
			do_action( "deleted_{$meta_type}meta", $meta_id );
		}

		return $result;

	}

	// Không tìm thấy ID meta.
	return false;
}

/**
 * Cập nhật bộ nhớ đệm metadata cho các đối tượng được chỉ định.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string       $meta_type  Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                                 hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param string|int[] $object_ids Mảng hoặc danh sách phân tách bằng dấu phẩy các ID đối tượng để cập nhật bộ nhớ đệm.
 * @return array|false Bộ nhớ đệm metadata cho các đối tượng được chỉ định, hoặc false khi thất bại.
 */
function update_meta_cache( $meta_type, $object_ids ) {
	global $wpdb;

	if ( ! $meta_type || ! $object_ids ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$column = sanitize_key( $meta_type . '_id' );

	if ( ! is_array( $object_ids ) ) {
		$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
		$object_ids = explode( ',', $object_ids );
	}

	$object_ids = array_map( 'intval', $object_ids );

	/**
	 * Bỏ qua (short-circuit) việc cập nhật bộ nhớ đệm metadata của một loại cụ thể.
	 *
	 * Phần động của tên hook, `$meta_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 * Trả về giá trị không null sẽ bỏ qua hàm một cách hiệu quả.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `update_post_metadata_cache`
	 *  - `update_comment_metadata_cache`
	 *  - `update_term_metadata_cache`
	 *  - `update_user_metadata_cache`
	 *
	 * @since 5.0.0
	 *
	 * @param mixed $check      Có cho phép cập nhật bộ nhớ đệm meta của loại đã cho hay không.
	 * @param int[] $object_ids Mảng các ID đối tượng để cập nhật bộ nhớ đệm meta.
	 */
	$check = apply_filters( "update_{$meta_type}_metadata_cache", null, $object_ids );
	if ( null !== $check ) {
		return (bool) $check;
	}

	$cache_group    = $meta_type . '_meta';
	$non_cached_ids = array();
	$cache          = array();
	$cache_values   = wp_cache_get_multiple( $object_ids, $cache_group );

	foreach ( $cache_values as $id => $cached_object ) {
		if ( false === $cached_object ) {
			$non_cached_ids[] = $id;
		} else {
			$cache[ $id ] = $cached_object;
		}
	}

	if ( empty( $non_cached_ids ) ) {
		return $cache;
	}

	// Lấy thông tin meta.
	$id_list   = implode( ',', $non_cached_ids );
	$id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

	$meta_list = $wpdb->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );

	if ( ! empty( $meta_list ) ) {
		foreach ( $meta_list as $metarow ) {
			$mpid = (int) $metarow[ $column ];
			$mkey = $metarow['meta_key'];
			$mval = $metarow['meta_value'];

			// Buộc các khóa con phải là kiểu mảng.
			if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
				$cache[ $mpid ] = array();
			}
			if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
				$cache[ $mpid ][ $mkey ] = array();
			}

			// Thêm giá trị vào pid/key hiện tại.
			$cache[ $mpid ][ $mkey ][] = $mval;
		}
	}

	$data = array();
	foreach ( $non_cached_ids as $id ) {
		if ( ! isset( $cache[ $id ] ) ) {
			$cache[ $id ] = array();
		}
		$data[ $id ] = $cache[ $id ];
	}
	wp_cache_add_multiple( $data, $cache_group );

	return $cache;
}

/**
 * Lấy hàng đợi để tải lười (lazy-load) metadata.
 *
 * @since 4.5.0
 *
 * @return WP_Metadata_Lazyloader Hàng đợi tải lười metadata.
 */
function wp_metadata_lazyloader() {
	static $wp_metadata_lazyloader;

	if ( null === $wp_metadata_lazyloader ) {
		$wp_metadata_lazyloader = new WP_Metadata_Lazyloader();
	}

	return $wp_metadata_lazyloader;
}

/**
 * Cho một truy vấn meta, tạo các mệnh đề SQL để nối vào truy vấn chính.
 *
 * @since 3.2.0
 *
 * @see WP_Meta_Query
 *
 * @param array  $meta_query        Một truy vấn meta.
 * @param string $type              Loại meta.
 * @param string $primary_table     Tên bảng cơ sở dữ liệu chính.
 * @param string $primary_id_column Tên cột ID chính.
 * @param object $context           Tùy chọn. Đối tượng truy vấn chính. Mặc định null.
 * @return string[]|false {
 *     Mảng chứa các mệnh đề SQL JOIN và WHERE để nối vào truy vấn chính,
 *     hoặc false nếu không có bảng nào tồn tại cho loại meta được yêu cầu.
 *
 *     @type string $join  Đoạn SQL để nối vào mệnh đề JOIN chính.
 *     @type string $where Đoạn SQL để nối vào mệnh đề WHERE chính.
 * }
 */
function get_meta_sql( $meta_query, $type, $primary_table, $primary_id_column, $context = null ) {
	$meta_query_obj = new WP_Meta_Query( $meta_query );
	return $meta_query_obj->get_sql( $type, $primary_table, $primary_id_column, $context );
}

/**
 * Lấy tên bảng metadata cho loại đối tượng được chỉ định.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                     hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @return string|false Tên bảng metadata, hoặc false nếu không có bảng metadata nào tồn tại.
 */
function _get_meta_table( $type ) {
	global $wpdb;

	$table_name = $type . 'meta';

	if ( empty( $wpdb->$table_name ) ) {
		return false;
	}

	return $wpdb->$table_name;
}

/**
 * Xác định xem khóa meta có được coi là được bảo vệ hay không.
 *
 * @since 3.1.3
 *
 * @param string $meta_key  Khóa metadata.
 * @param string $meta_type Tùy chọn. Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết. Mặc định chuỗi rỗng.
 * @return bool Liệu khóa meta có được coi là được bảo vệ hay không.
 */
function is_protected_meta( $meta_key, $meta_type = '' ) {
	$sanitized_key = preg_replace( "/[^\x20-\x7E\p{L}]/", '', $meta_key );
	$protected     = strlen( $sanitized_key ) > 0 && ( '_' === $sanitized_key[0] );

	/**
	 * Lọc xem khóa meta có được coi là được bảo vệ hay không.
	 *
	 * @since 3.2.0
	 *
	 * @param bool   $protected Liệu khóa có được coi là được bảo vệ hay không.
	 * @param string $meta_key  Khóa metadata.
	 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
	 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
	 */
	return apply_filters( 'is_protected_meta', $protected, $meta_key, $meta_type );
}

/**
 * Làm sạch giá trị meta.
 *
 * @since 3.1.3
 * @since 4.9.8 Thêm tham số `$object_subtype`.
 *
 * @param string $meta_key       Khóa metadata.
 * @param mixed  $meta_value     Giá trị metadata cần làm sạch.
 * @param string $object_type    Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                               hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param string $object_subtype Tùy chọn. Kiểu con của loại đối tượng. Mặc định chuỗi rỗng.
 * @return mixed Giá trị $meta_value đã được làm sạch.
 */
function sanitize_meta( $meta_key, $meta_value, $object_type, $object_subtype = '' ) {
	if ( ! empty( $object_subtype ) && has_filter( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}" ) ) {

		/**
		 * Lọc việc làm sạch khóa meta cụ thể của loại meta và kiểu con cụ thể.
		 *
		 * Các phần động của tên hook, `$object_type`, `$meta_key`,
		 * và `$object_subtype`, tham chiếu đến loại đối tượng metadata (comment, post, term, hoặc user),
		 * giá trị khóa meta, và kiểu con đối tượng tương ứng.
		 *
		 * @since 4.9.8
		 *
		 * @param mixed  $meta_value     Giá trị metadata cần làm sạch.
		 * @param string $meta_key       Khóa metadata.
		 * @param string $object_type    Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
		 *                               hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
		 * @param string $object_subtype Kiểu con đối tượng.
		 */
		return apply_filters( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $meta_value, $meta_key, $object_type, $object_subtype );
	}

	/**
	 * Lọc việc làm sạch của một khóa meta cụ thể thuộc một loại meta cụ thể.
	 *
	 * Các phần động của tên hook, `$meta_type` và `$meta_key`,
	 * tham chiếu đến loại đối tượng metadata (comment, post, term, hoặc user) và giá trị
	 * khóa meta, tương ứng.
	 *
	 * @since 3.3.0
	 *
	 * @param mixed  $meta_value  Giá trị metadata cần làm sạch.
	 * @param string $meta_key    Khóa metadata.
	 * @param string $object_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
	 *                            hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
	 */
	return apply_filters( "sanitize_{$object_type}_meta_{$meta_key}", $meta_value, $meta_key, $object_type );
}

/**
 * Đăng ký một khóa meta.
 *
 * Khuyến nghị đăng ký khóa meta cho một tổ hợp cụ thể của loại đối tượng và kiểu con đối tượng. Nếu bỏ qua
 * việc truyền kiểu con đối tượng, khóa meta sẽ được đăng ký cho toàn bộ loại đối tượng, tuy nhiên nó có thể bị
 * ghi đè một phần nếu có khóa meta cụ thể hơn cùng tên tồn tại cho cùng loại đối tượng và một kiểu con.
 *
 * Nếu một loại đối tượng không hỗ trợ bất kỳ kiểu con nào, chẳng hạn như users hoặc comments, bạn thường nên gọi hàm này
 * mà không truyền kiểu con.
 *
 * @since 3.3.0
 * @since 4.6.0 {@link https://core.trac.wordpress.org/ticket/35658 Được sửa đổi
 *              để hỗ trợ mảng dữ liệu đính kèm vào khóa meta đã đăng ký}. Các tham số trước đó cho
 *              `$sanitize_callback` và `$auth_callback` đã được gộp vào mảng này.
 * @since 4.9.8 Tham số `$object_subtype` được thêm vào mảng tham số.
 * @since 5.3.0 Các loại meta hợp lệ được mở rộng bao gồm "array" và "object".
 * @since 5.5.0 Tham số `$default` được thêm vào mảng tham số.
 * @since 6.4.0 Tham số `$revisions_enabled` được thêm vào mảng tham số.
 * @since 6.7.0 Tham số `label` được thêm vào mảng tham số.
 *
 * @param string       $object_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                                  hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param string       $meta_key    Khóa meta cần đăng ký.
 * @param array        $args {
 *     Dữ liệu dùng để mô tả khóa meta khi đăng ký.
 *
 *     @type string     $object_subtype    Kiểu con; ví dụ nếu loại đối tượng là "post", thì là loại bài viết. Nếu để trống,
 *                                         khóa meta sẽ được đăng ký trên toàn bộ loại đối tượng. Mặc định rỗng.
 *     @type string     $type              Kiểu dữ liệu liên kết với khóa meta này.
 *                                         Các giá trị hợp lệ là 'string', 'boolean', 'integer', 'number', 'array', và 'object'.
 *     @type string     $label             Nhãn mô tả dễ đọc cho dữ liệu đính kèm khóa meta này.
 *     @type string     $description       Mô tả dữ liệu đính kèm khóa meta này.
 *     @type bool       $single            Khóa meta có một giá trị cho mỗi đối tượng, hay một mảng giá trị cho mỗi đối tượng.
 *     @type mixed      $default           Giá trị mặc định trả về từ get_metadata() nếu chưa có giá trị nào được đặt.
 *                                         Khi sử dụng khóa meta không đơn lẻ, giá trị mặc định dành cho mục đầu tiên.
 *                                         Nói cách khác, khi gọi get_metadata() với `$single` đặt là `false`,
 *                                         giá trị mặc định ở đây sẽ được bọc trong một mảng.
 *     @type callable   $sanitize_callback Hàm hoặc phương thức gọi khi làm sạch dữ liệu `$meta_key`.
 *     @type callable   $auth_callback     Tùy chọn. Hàm hoặc phương thức gọi khi thực hiện kiểm tra quyền
 *                                         edit_post_meta, add_post_meta, và delete_post_meta.
 *     @type bool|array $show_in_rest      Dữ liệu liên kết với khóa meta này có thể được coi là công khai và
 *                                         có thể truy cập qua REST API hay không. Loại bài viết tùy chỉnh cũng phải khai báo
 *                                         hỗ trợ trường tùy chỉnh để meta đã đăng ký có thể truy cập qua REST.
 *                                         Khi đăng ký giá trị meta phức tạp, tham số này có thể tùy chọn là một
 *                                         mảng với khóa 'schema' hoặc 'prepare_callback' thay vì boolean.
 *     @type bool       $revisions_enabled Có bật hỗ trợ phiên bản cho meta_key này hay không. Chỉ có thể sử dụng khi
 *                                         loại đối tượng là 'post'.
 * }
 * @param string|array $deprecated Đã lỗi thời. Sử dụng `$args` thay thế.
 * @return bool True nếu khóa meta được đăng ký thành công trong mảng toàn cục, false nếu không.
 *              Đăng ký khóa meta với callback làm sạch và xác thực riêng biệt sẽ kích hoạt các callback đó,
 *              nhưng sẽ không thêm vào registry toàn cục.
 */
function register_meta( $object_type, $meta_key, $args, $deprecated = null ) {
	global $wp_meta_keys;

	if ( ! is_array( $wp_meta_keys ) ) {
		$wp_meta_keys = array();
	}

	$defaults = array(
		'object_subtype'    => '',
		'type'              => 'string',
		'label'             => '',
		'description'       => '',
		'default'           => '',
		'single'            => false,
		'sanitize_callback' => null,
		'auth_callback'     => null,
		'show_in_rest'      => false,
		'revisions_enabled' => false,
	);

	// Trước đây có các tham số riêng lẻ cho callback làm sạch và xác thực.
	$has_old_sanitize_cb = false;
	$has_old_auth_cb     = false;

	if ( is_callable( $args ) ) {
		$args = array(
			'sanitize_callback' => $args,
		);

		$has_old_sanitize_cb = true;
	} else {
		$args = (array) $args;
	}

	if ( is_callable( $deprecated ) ) {
		$args['auth_callback'] = $deprecated;
		$has_old_auth_cb       = true;
	}

	/**
	 * Lọc các tham số đăng ký khi đăng ký meta.
	 *
	 * @since 4.6.0
	 *
	 * @param array  $args        Mảng các tham số đăng ký meta.
	 * @param array  $defaults    Mảng các tham số mặc định.
	 * @param string $object_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
	 *                            hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
	 * @param string $meta_key    Khóa meta.
	 */
	$args = apply_filters( 'register_meta_args', $args, $defaults, $object_type, $meta_key );
	unset( $defaults['default'] );
	$args = wp_parse_args( $args, $defaults );

	// Yêu cầu schema cho mục khi đăng ký meta kiểu mảng.
	if ( false !== $args['show_in_rest'] && 'array' === $args['type'] ) {
		if ( ! is_array( $args['show_in_rest'] ) || ! isset( $args['show_in_rest']['schema']['items'] ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'When registering an "array" meta type to show in the REST API, you must specify the schema for each array item in "show_in_rest.schema.items".' ), '5.3.0' );

			return false;
		}
	}

	$object_subtype = ! empty( $args['object_subtype'] ) ? $args['object_subtype'] : '';
	if ( $args['revisions_enabled'] ) {
		if ( 'post' !== $object_type ) {
			_doing_it_wrong( __FUNCTION__, __( 'Meta keys cannot enable revisions support unless the object type supports revisions.' ), '6.4.0' );

			return false;
		} elseif ( ! empty( $object_subtype ) && ! post_type_supports( $object_subtype, 'revisions' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Meta keys cannot enable revisions support unless the object subtype supports revisions.' ), '6.4.0' );

			return false;
		}
	}

	// Nếu `auth_callback` không được cung cấp, sử dụng `is_protected_meta()` thay thế.
	if ( empty( $args['auth_callback'] ) ) {
		if ( is_protected_meta( $meta_key, $object_type ) ) {
			$args['auth_callback'] = '__return_false';
		} else {
			$args['auth_callback'] = '__return_true';
		}
	}

	// Tương thích ngược: callback làm sạch và xác thực cũ được áp dụng cho toàn bộ loại đối tượng.
	if ( is_callable( $args['sanitize_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			add_filter( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['sanitize_callback'], 10, 4 );
		} else {
			add_filter( "sanitize_{$object_type}_meta_{$meta_key}", $args['sanitize_callback'], 10, 3 );
		}
	}

	if ( is_callable( $args['auth_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			add_filter( "auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['auth_callback'], 10, 6 );
		} else {
			add_filter( "auth_{$object_type}_meta_{$meta_key}", $args['auth_callback'], 10, 6 );
		}
	}

	if ( array_key_exists( 'default', $args ) ) {
		$schema = $args;
		if ( is_array( $args['show_in_rest'] ) && isset( $args['show_in_rest']['schema'] ) ) {
			$schema = array_merge( $schema, $args['show_in_rest']['schema'] );
		}

		$check = rest_validate_value_from_schema( $args['default'], $schema );
		if ( is_wp_error( $check ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'When registering a default meta value the data must match the type provided.' ), '5.5.0' );

			return false;
		}

		if ( ! has_filter( "default_{$object_type}_metadata", 'filter_default_metadata' ) ) {
			add_filter( "default_{$object_type}_metadata", 'filter_default_metadata', 10, 5 );
		}
	}

	// Registry toàn cục chỉ chứa các khóa meta được đăng ký với mảng tham số được thêm trong 4.6.0.
	if ( ! $has_old_auth_cb && ! $has_old_sanitize_cb ) {
		unset( $args['object_subtype'] );

		$wp_meta_keys[ $object_type ][ $object_subtype ][ $meta_key ] = $args;

		return true;
	}

	return false;
}

/**
 * Lọc vào default_{$object_type}_metadata và thêm giá trị mặc định.
 *
 * @since 5.5.0
 *
 * @param mixed  $value     Giá trị hiện tại được truyền vào bộ lọc.
 * @param int    $object_id ID của đối tượng mà metadata dành cho.
 * @param string $meta_key  Khóa metadata.
 * @param bool   $single    Nếu true, chỉ trả về giá trị đầu tiên của `$meta_key` được chỉ định.
 *                          Tham số này không có hiệu lực nếu `$meta_key` không được chỉ định.
 * @param string $meta_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                          hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @return mixed Mảng các giá trị mặc định nếu `$single` là false.
 *               Giá trị mặc định của trường meta nếu `$single` là true.
 */
function filter_default_metadata( $value, $object_id, $meta_key, $single, $meta_type ) {
	global $wp_meta_keys;

	if ( wp_installing() ) {
		return $value;
	}

	if ( ! is_array( $wp_meta_keys ) || ! isset( $wp_meta_keys[ $meta_type ] ) ) {
		return $value;
	}

	$defaults = array();
	foreach ( $wp_meta_keys[ $meta_type ] as $sub_type => $meta_data ) {
		foreach ( $meta_data as $_meta_key => $args ) {
			if ( $_meta_key === $meta_key && array_key_exists( 'default', $args ) ) {
				$defaults[ $sub_type ] = $args;
			}
		}
	}

	if ( ! $defaults ) {
		return $value;
	}

	// Nếu loại meta này không có kiểu con, thì giá trị mặc định được khóa bằng chuỗi rỗng.
	if ( isset( $defaults[''] ) ) {
		$metadata = $defaults[''];
	} else {
		$sub_type = get_object_subtype( $meta_type, $object_id );
		if ( ! isset( $defaults[ $sub_type ] ) ) {
			return $value;
		}
		$metadata = $defaults[ $sub_type ];
	}

	if ( $single ) {
		$value = $metadata['default'];
	} else {
		$value = array( $metadata['default'] );
	}

	return $value;
}

/**
 * Kiểm tra xem một khóa meta đã được đăng ký hay chưa.
 *
 * @since 4.6.0
 * @since 4.9.8 Tham số `$object_subtype` được thêm.
 *
 * @param string $object_type    Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                               hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param string $meta_key       Khóa metadata.
 * @param string $object_subtype Tùy chọn. Kiểu con của loại đối tượng. Mặc định chuỗi rỗng.
 * @return bool True nếu khóa meta được đăng ký cho loại đối tượng và, nếu được cung cấp,
 *              kiểu con đối tượng. False nếu không.
 */
function registered_meta_key_exists( $object_type, $meta_key, $object_subtype = '' ) {
	$meta_keys = get_registered_meta_keys( $object_type, $object_subtype );

	return isset( $meta_keys[ $meta_key ] );
}

/**
 * Hủy đăng ký một khóa meta khỏi danh sách các khóa đã đăng ký.
 *
 * @since 4.6.0
 * @since 4.9.8 Tham số `$object_subtype` được thêm.
 *
 * @param string $object_type    Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                               hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param string $meta_key       Khóa metadata.
 * @param string $object_subtype Tùy chọn. Kiểu con của loại đối tượng. Mặc định chuỗi rỗng.
 * @return bool True nếu thành công. False nếu khóa meta chưa được đăng ký.
 */
function unregister_meta_key( $object_type, $meta_key, $object_subtype = '' ) {
	global $wp_meta_keys;

	if ( ! registered_meta_key_exists( $object_type, $meta_key, $object_subtype ) ) {
		return false;
	}

	$args = $wp_meta_keys[ $object_type ][ $object_subtype ][ $meta_key ];

	if ( isset( $args['sanitize_callback'] ) && is_callable( $args['sanitize_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			remove_filter( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['sanitize_callback'] );
		} else {
			remove_filter( "sanitize_{$object_type}_meta_{$meta_key}", $args['sanitize_callback'] );
		}
	}

	if ( isset( $args['auth_callback'] ) && is_callable( $args['auth_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			remove_filter( "auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['auth_callback'] );
		} else {
			remove_filter( "auth_{$object_type}_meta_{$meta_key}", $args['auth_callback'] );
		}
	}

	unset( $wp_meta_keys[ $object_type ][ $object_subtype ][ $meta_key ] );

	// Dọn dẹp.
	if ( empty( $wp_meta_keys[ $object_type ][ $object_subtype ] ) ) {
		unset( $wp_meta_keys[ $object_type ][ $object_subtype ] );
	}
	if ( empty( $wp_meta_keys[ $object_type ] ) ) {
		unset( $wp_meta_keys[ $object_type ] );
	}

	return true;
}

/**
 * Lấy danh sách các tham số metadata đã đăng ký cho một loại đối tượng, được khóa bởi các khóa meta.
 *
 * @since 4.6.0
 * @since 4.9.8 Tham số `$object_subtype` được thêm.
 *
 * @param string $object_type    Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                               hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param string $object_subtype Tùy chọn. Kiểu con của loại đối tượng. Mặc định chuỗi rỗng.
 * @return array[] Danh sách các tham số metadata đã đăng ký, được khóa bởi các khóa meta.
 */
function get_registered_meta_keys( $object_type, $object_subtype = '' ) {
	global $wp_meta_keys;

	if ( ! is_array( $wp_meta_keys ) || ! isset( $wp_meta_keys[ $object_type ] ) || ! isset( $wp_meta_keys[ $object_type ][ $object_subtype ] ) ) {
		return array();
	}

	return $wp_meta_keys[ $object_type ][ $object_subtype ];
}

/**
 * Lấy metadata đã đăng ký cho một đối tượng được chỉ định.
 *
 * Kết quả bao gồm cả meta được đăng ký cụ thể cho kiểu con
 * của đối tượng và meta được đăng ký cho toàn bộ loại đối tượng.
 *
 * @since 4.6.0
 *
 * @param string $object_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                            hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id   ID của đối tượng mà metadata dành cho.
 * @param string $meta_key    Tùy chọn. Khóa metadata đã đăng ký. Nếu không chỉ định, lấy tất cả metadata
 *                            đã đăng ký cho đối tượng được chỉ định.
 * @return mixed Giá trị đơn hoặc mảng giá trị cho một khóa nếu được chỉ định. Mảng tất cả khóa và
 *               giá trị đã đăng ký cho ID đối tượng nếu không. False nếu $meta_key đã cho chưa được đăng ký.
 */
function get_registered_metadata( $object_type, $object_id, $meta_key = '' ) {
	$object_subtype = get_object_subtype( $object_type, $object_id );

	if ( ! empty( $meta_key ) ) {
		if ( ! empty( $object_subtype ) && ! registered_meta_key_exists( $object_type, $meta_key, $object_subtype ) ) {
			$object_subtype = '';
		}

		if ( ! registered_meta_key_exists( $object_type, $meta_key, $object_subtype ) ) {
			return false;
		}

		$meta_keys     = get_registered_meta_keys( $object_type, $object_subtype );
		$meta_key_data = $meta_keys[ $meta_key ];

		$data = get_metadata( $object_type, $object_id, $meta_key, $meta_key_data['single'] );

		return $data;
	}

	$data = get_metadata( $object_type, $object_id );
	if ( ! $data ) {
		return array();
	}

	$meta_keys = get_registered_meta_keys( $object_type );
	if ( ! empty( $object_subtype ) ) {
		$meta_keys = array_merge( $meta_keys, get_registered_meta_keys( $object_type, $object_subtype ) );
	}

	return array_intersect_key( $data, $meta_keys );
}

/**
 * Lọc bỏ các tham số `register_meta()` dựa trên danh sách cho phép.
 *
 * Các tham số `register_meta()` có thể thay đổi theo thời gian, vì vậy việc yêu cầu danh sách
 * cho phép phải được tắt rõ ràng là một dạng đảm bảo.
 *
 * @access private
 * @since 5.5.0
 *
 * @param array $args         Các tham số từ `register_meta()`.
 * @param array $default_args Các tham số mặc định cho `register_meta()`.
 * @return array Các tham số đã lọc.
 */
function _wp_register_meta_args_allowed_list( $args, $default_args ) {
	return array_intersect_key( $args, $default_args );
}

/**
 * Trả về kiểu con đối tượng cho một ID đối tượng cụ thể thuộc một loại nhất định.
 *
 * @since 4.9.8
 *
 * @param string $object_type Loại đối tượng mà metadata dành cho. Chấp nhận 'post', 'comment', 'term', 'user',
 *                            hoặc bất kỳ loại đối tượng nào khác có bảng meta liên kết.
 * @param int    $object_id   ID của đối tượng cần lấy kiểu con.
 * @return string Kiểu con đối tượng hoặc chuỗi rỗng nếu kiểu con không được chỉ định.
 */
function get_object_subtype( $object_type, $object_id ) {
	$object_id      = (int) $object_id;
	$object_subtype = '';

	switch ( $object_type ) {
		case 'post':
			$post_type = get_post_type( $object_id );

			if ( ! empty( $post_type ) ) {
				$object_subtype = $post_type;
			}
			break;

		case 'term':
			$term = get_term( $object_id );
			if ( ! $term instanceof WP_Term ) {
				break;
			}

			$object_subtype = $term->taxonomy;
			break;

		case 'comment':
			$comment = get_comment( $object_id );
			if ( ! $comment ) {
				break;
			}

			$object_subtype = 'comment';
			break;

		case 'user':
			$user = get_user_by( 'id', $object_id );
			if ( ! $user ) {
				break;
			}

			$object_subtype = 'user';
			break;
	}

	/**
	 * Lọc định danh kiểu con đối tượng cho loại đối tượng không chuẩn.
	 *
	 * Phần động của tên hook, `$object_type`, tham chiếu đến loại đối tượng meta
	 * (post, comment, term, user, hoặc bất kỳ loại nào khác có bảng meta liên kết).
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_object_subtype_post`
	 *  - `get_object_subtype_comment`
	 *  - `get_object_subtype_term`
	 *  - `get_object_subtype_user`
	 *
	 * @since 4.9.8
	 *
	 * @param string $object_subtype Chuỗi rỗng để ghi đè.
	 * @param int    $object_id      ID của đối tượng cần lấy kiểu con.
	 */
	return apply_filters( "get_object_subtype_{$object_type}", $object_subtype, $object_id );
}
