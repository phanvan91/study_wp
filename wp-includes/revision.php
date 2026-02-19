<?php
/**
 * Các hàm phiên bản sửa đổi bài viết.
 *
 * @package WordPress
 * @subpackage Post_Revisions
 */

/**
 * Xác định các trường của bài viết sẽ được lưu trong các bản sửa đổi.
 *
 * @since 2.6.0
 * @since 4.5.0 Đối tượng `WP_Post` giờ có thể được truyền vào tham số `$post`.
 * @since 4.5.0 Tham số tùy chọn `$autosave` đã bị loại bỏ và đổi tên thành `$deprecated`.
 * @access private
 *
 * @param array|WP_Post $post       Tùy chọn. Mảng bài viết hoặc đối tượng WP_Post đang được xử lý
 *                                  để chèn dưới dạng bản sửa đổi bài viết. Mặc định mảng rỗng.
 * @param bool          $deprecated Không sử dụng.
 * @return string[] Mảng các trường có thể được lưu phiên bản.
 */
function _wp_post_revision_fields( $post = array(), $deprecated = false ) {
	static $fields = null;

	if ( ! is_array( $post ) ) {
		$post = get_post( $post, ARRAY_A );
	}

	if ( is_null( $fields ) ) {
		// Cho phép các trường này được lưu phiên bản.
		$fields = array(
			'post_title'   => __( 'Title' ),
			'post_content' => __( 'Content' ),
			'post_excerpt' => __( 'Excerpt' ),
		);
	}

	/**
	 * Lọc danh sách các trường được lưu trong các bản sửa đổi bài viết.
	 *
	 * Mặc định bao gồm: 'post_title', 'post_content' và 'post_excerpt'.
	 *
	 * Các trường không được phép: 'ID', 'post_name', 'post_parent', 'post_date',
	 * 'post_date_gmt', 'post_status', 'post_type', 'comment_count',
	 * và 'post_author'.
	 *
	 * @since 2.6.0
	 * @since 4.5.0 Tham số `$post` được thêm vào.
	 *
	 * @param string[] $fields Danh sách các trường để lưu bản sửa đổi. Mặc định chứa 'post_title',
	 *                         'post_content', và 'post_excerpt'.
	 * @param array    $post   Mảng bài viết đang được xử lý để chèn dưới dạng bản sửa đổi.
	 */
	$fields = apply_filters( '_wp_post_revision_fields', $fields, $post );

	// WP sử dụng các trường này nội bộ trong quá trình lưu phiên bản hoặc nơi khác - chúng không thể được lưu phiên bản.
	foreach ( array( 'ID', 'post_name', 'post_parent', 'post_date', 'post_date_gmt', 'post_status', 'post_type', 'comment_count', 'post_author' ) as $protect ) {
		unset( $fields[ $protect ] );
	}

	return $fields;
}

/**
 * Trả về mảng bài viết sẵn sàng để chèn vào bảng posts dưới dạng bản sửa đổi.
 *
 * @since 4.5.0
 * @access private
 *
 * @param array|WP_Post $post     Tùy chọn. Mảng bài viết hoặc đối tượng WP_Post cần được xử lý
 *                                để chèn dưới dạng bản sửa đổi bài viết. Mặc định mảng rỗng.
 * @param bool          $autosave Tùy chọn. Bản sửa đổi có phải là tự động lưu không? Mặc định false.
 * @return array Mảng bài viết sẵn sàng để chèn dưới dạng bản sửa đổi.
 */
function _wp_post_revision_data( $post = array(), $autosave = false ) {
	if ( ! is_array( $post ) ) {
		$post = get_post( $post, ARRAY_A );
	}

	$fields = _wp_post_revision_fields( $post );

	$revision_data = array();

	foreach ( array_intersect( array_keys( $post ), array_keys( $fields ) ) as $field ) {
		$revision_data[ $field ] = $post[ $field ];
	}

	$revision_data['post_parent']   = $post['ID'];
	$revision_data['post_status']   = 'inherit';
	$revision_data['post_type']     = 'revision';
	$revision_data['post_name']     = $autosave ? "$post[ID]-autosave-v1" : "$post[ID]-revision-v1"; // "1" là phiên bản hệ thống lưu bản sửa đổi.
	$revision_data['post_date']     = isset( $post['post_modified'] ) ? $post['post_modified'] : '';
	$revision_data['post_date_gmt'] = isset( $post['post_modified_gmt'] ) ? $post['post_modified_gmt'] : '';

	return $revision_data;
}

/**
 * Lưu các bản sửa đổi cho bài viết sau khi tất cả thay đổi đã được thực hiện.
 *
 * @since 6.4.0
 *
 * @param int     $post_id ID bài viết đã được chèn.
 * @param WP_Post $post    Đối tượng bài viết đã được chèn.
 * @param bool    $update  Liệu việc chèn này có phải đang cập nhật bài viết đã tồn tại hay không.
 */
function wp_save_post_revision_on_insert( $post_id, $post, $update ) {
	if ( ! $update ) {
		return;
	}

	if ( ! has_action( 'post_updated', 'wp_save_post_revision' ) ) {
		return;
	}

	wp_save_post_revision( $post_id );
}

/**
 * Tạo bản sửa đổi cho phiên bản hiện tại của bài viết.
 *
 * Thường được sử dụng ngay sau khi cập nhật bài viết, vì mỗi lần cập nhật là một bản sửa đổi,
 * và bản sửa đổi gần nhất luôn khớp với bài viết hiện tại.
 *
 * @since 2.6.0
 *
 * @param int $post_id ID của bài viết cần lưu dưới dạng bản sửa đổi.
 * @return int|WP_Error|void Void hoặc 0 nếu lỗi, ID bản sửa đổi mới nếu thành công.
 */
function wp_save_post_revision( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Ngăn lưu bản sửa đổi bài viết nếu bản sửa đổi cần được lưu tại wp_after_insert_post.
	if ( doing_action( 'post_updated' ) && has_action( 'wp_after_insert_post', 'wp_save_post_revision_on_insert' ) ) {
		return;
	}

	$post = get_post( $post_id );

	if ( ! $post ) {
		return;
	}

	if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
		return;
	}

	if ( 'auto-draft' === $post->post_status ) {
		return;
	}

	if ( ! wp_revisions_enabled( $post ) ) {
		return;
	}

	/*
	 * So sánh bản cập nhật đề xuất với bản sửa đổi được lưu cuối cùng để xác minh
	 * chúng khác nhau, trừ khi plugin yêu cầu luôn lưu bất kể.
	 * Nếu không có bản sửa đổi trước đó, lưu một bản.
	 */
	$revisions = wp_get_post_revisions( $post_id );
	if ( $revisions ) {
		// Lấy bản sửa đổi mới nhất, nhưng không phải bản tự động lưu.
		foreach ( $revisions as $revision ) {
			if ( str_contains( $revision->post_name, "{$revision->post_parent}-revision" ) ) {
				$latest_revision = $revision;
				break;
			}
		}

		/**
		 * Lọc xem bài viết có thay đổi kể từ bản sửa đổi mới nhất hay không.
		 *
		 * Mặc định, bản sửa đổi chỉ được lưu nếu một trong các trường được lưu phiên bản đã thay đổi.
		 * Bộ lọc này có thể ghi đè điều đó để bản sửa đổi được lưu ngay cả khi không có gì thay đổi.
		 *
		 * @since 3.6.0
		 *
		 * @param bool    $check_for_changes Có kiểm tra thay đổi trước khi lưu bản sửa đổi mới không.
		 *                                   Mặc định true.
		 * @param WP_Post $latest_revision   Đối tượng bài viết bản sửa đổi mới nhất.
		 * @param WP_Post $post              Đối tượng bài viết.
		 */
		if ( isset( $latest_revision ) && apply_filters( 'wp_save_post_revision_check_for_changes', true, $latest_revision, $post ) ) {
			$post_has_changed = false;

			foreach ( array_keys( _wp_post_revision_fields( $post ) ) as $field ) {
				if ( normalize_whitespace( $post->$field ) !== normalize_whitespace( $latest_revision->$field ) ) {
					$post_has_changed = true;
					break;
				}
			}

			/**
			 * Lọc xem bài viết có thay đổi hay không.
			 *
			 * Mặc định, bản sửa đổi chỉ được lưu nếu một trong các trường được lưu phiên bản đã thay đổi.
			 * Bộ lọc này cho phép kiểm tra bổ sung để xác định xem có thay đổi hay không.
			 *
			 * @since 4.1.0
			 *
			 * @param bool    $post_has_changed Bài viết có thay đổi hay không.
			 * @param WP_Post $latest_revision  Đối tượng bài viết bản sửa đổi mới nhất.
			 * @param WP_Post $post             Đối tượng bài viết.
			 */
			$post_has_changed = (bool) apply_filters( 'wp_save_post_revision_post_has_changed', $post_has_changed, $latest_revision, $post );

			// Không lưu bản sửa đổi nếu bài viết không thay đổi.
			if ( ! $post_has_changed ) {
				return;
			}
		}
	}

	$return = _wp_put_post_revision( $post );

	/*
	 * Nếu giới hạn số lượng bản sửa đổi cần giữ đã được thiết lập,
	 * xóa các bản cũ nhất.
	 */
	$revisions_to_keep = wp_revisions_to_keep( $post );

	if ( $revisions_to_keep < 0 ) {
		return $return;
	}

	$revisions = wp_get_post_revisions( $post_id, array( 'order' => 'ASC' ) );

	/**
	 * Lọc các bản sửa đổi được xem xét để xóa.
	 *
	 * @since 6.2.0
	 *
	 * @param WP_Post[] $revisions Mảng các bản sửa đổi, hoặc mảng rỗng nếu không có.
	 * @param int       $post_id   ID của bài viết cần lưu dưới dạng bản sửa đổi.
	 */
	$revisions = apply_filters(
		'wp_save_post_revision_revisions_before_deletion',
		$revisions,
		$post_id
	);

	$delete = count( $revisions ) - $revisions_to_keep;

	if ( $delete < 1 ) {
		return $return;
	}

	$revisions = array_slice( $revisions, 0, $delete );

	for ( $i = 0; isset( $revisions[ $i ] ); $i++ ) {
		if ( str_contains( $revisions[ $i ]->post_name, 'autosave' ) ) {
			continue;
		}

		wp_delete_post_revision( $revisions[ $i ]->ID );
	}

	return $return;
}

/**
 * Lấy dữ liệu tự động lưu của bài viết được chỉ định.
 *
 * Trả về đối tượng bài viết với thông tin đã được tự động lưu cho bài viết được chỉ định.
 * Nếu $user_id tùy chọn được truyền, trả về bản tự động lưu cho người dùng đó, nếu không
 * trả về bản tự động lưu mới nhất.
 *
 * @since 2.6.0
 *
 * @param int $post_id ID bài viết.
 * @param int $user_id Tùy chọn. ID tác giả bài viết. Mặc định 0.
 * @return WP_Post|false Dữ liệu tự động lưu hoặc false khi thất bại hoặc không có bản tự động lưu.
 */
function wp_get_post_autosave( $post_id, $user_id = 0 ) {
	$args = array(
		'post_type'      => 'revision',
		'post_status'    => 'inherit',
		'post_parent'    => $post_id,
		'name'           => $post_id . '-autosave-v1',
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);

	if ( 0 !== $user_id ) {
		$args['author'] = $user_id;
	}

	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		return false;
	}

	return get_post( $query->posts[0] );
}

/**
 * Xác định xem bài viết được chỉ định có phải là bản sửa đổi hay không.
 *
 * @since 2.6.0
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng bài viết.
 * @return int|false ID của bài viết cha của bản sửa đổi khi thành công, false nếu không phải bản sửa đổi.
 */
function wp_is_post_revision( $post ) {
	$post = wp_get_post_revision( $post );

	if ( ! $post ) {
		return false;
	}

	return (int) $post->post_parent;
}

/**
 * Xác định xem bài viết được chỉ định có phải là bản tự động lưu hay không.
 *
 * @since 2.6.0
 *
 * @param int|WP_Post $post ID bài viết hoặc đối tượng bài viết.
 * @return int|false ID của bài viết cha của bản tự động lưu khi thành công, false nếu không phải bản sửa đổi.
 */
function wp_is_post_autosave( $post ) {
	$post = wp_get_post_revision( $post );

	if ( ! $post ) {
		return false;
	}

	if ( str_contains( $post->post_name, "{$post->post_parent}-autosave" ) ) {
		return (int) $post->post_parent;
	}

	return false;
}

/**
 * Chèn dữ liệu bài viết vào bảng posts dưới dạng bản sửa đổi bài viết.
 *
 * @since 2.6.0
 * @access private
 *
 * @param int|WP_Post|array|null $post     ID bài viết, đối tượng bài viết HOẶC mảng bài viết.
 * @param bool                   $autosave Tùy chọn. Bản sửa đổi có phải là tự động lưu hay không.
 *                                         Mặc định false.
 * @return int|WP_Error WP_Error hoặc 0 nếu lỗi, ID bản sửa đổi mới nếu thành công.
 */
function _wp_put_post_revision( $post = null, $autosave = false ) {
	if ( is_object( $post ) ) {
		$post = get_object_vars( $post );
	} elseif ( ! is_array( $post ) ) {
		$post = get_post( $post, ARRAY_A );
	}

	if ( ! $post || empty( $post['ID'] ) ) {
		return new WP_Error( 'invalid_post', __( 'Invalid post ID.' ) );
	}

	if ( isset( $post['post_type'] ) && 'revision' === $post['post_type'] ) {
		return new WP_Error( 'post_type', __( 'Cannot create a revision of a revision' ) );
	}

	$post = _wp_post_revision_data( $post, $autosave );
	$post = wp_slash( $post ); // Vì dữ liệu từ cơ sở dữ liệu.

	$revision_id = wp_insert_post( $post, true );
	if ( is_wp_error( $revision_id ) ) {
		return $revision_id;
	}

	if ( $revision_id ) {
		/**
		 * Kích hoạt khi một bản sửa đổi đã được lưu.
		 *
		 * @since 2.6.0
		 * @since 6.4.0 Tham số post_id được thêm vào.
		 *
		 * @param int $revision_id ID bản sửa đổi bài viết.
		 * @param int $post_id     ID bài viết.
		 */
		do_action( '_wp_put_post_revision', $revision_id, $post['post_parent'] );
	}

	return $revision_id;
}


/**
 * Lưu các trường meta được lưu phiên bản.
 *
 * @since 6.4.0
 *
 * @param int $revision_id ID của bản sửa đổi để lưu meta vào.
 * @param int $post_id     ID của bài viết liên kết với bản sửa đổi.
 */
function wp_save_revisioned_meta_fields( $revision_id, $post_id ) {
	$post_type = get_post_type( $post_id );
	if ( ! $post_type ) {
		return;
	}

	foreach ( wp_post_revision_meta_keys( $post_type ) as $meta_key ) {
		if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
			_wp_copy_post_meta( $post_id, $revision_id, $meta_key );
		}
	}
}

/**
 * Lấy một bản sửa đổi bài viết.
 *
 * @since 2.6.0
 *
 * @param int|WP_Post $post   ID bài viết hoặc đối tượng bài viết.
 * @param string      $output Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                            tương ứng với đối tượng WP_Post, mảng kết hợp, hoặc mảng số.
 *                            Mặc định OBJECT.
 * @param string      $filter Bộ lọc làm sạch tùy chọn. Xem sanitize_post(). Mặc định 'raw'.
 * @return WP_Post|array|null WP_Post (hoặc mảng) khi thành công, hoặc null khi thất bại.
 */
function wp_get_post_revision( &$post, $output = OBJECT, $filter = 'raw' ) {
	$revision = get_post( $post, OBJECT, $filter );

	if ( ! $revision ) {
		return $revision;
	}

	if ( 'revision' !== $revision->post_type ) {
		return null;
	}

	if ( OBJECT === $output ) {
		return $revision;
	} elseif ( ARRAY_A === $output ) {
		$_revision = get_object_vars( $revision );
		return $_revision;
	} elseif ( ARRAY_N === $output ) {
		$_revision = array_values( get_object_vars( $revision ) );
		return $_revision;
	}

	return $revision;
}

/**
 * Khôi phục bài viết về bản sửa đổi được chỉ định.
 *
 * Có thể khôi phục bản sửa đổi trước đó sử dụng tất cả các trường của bản sửa đổi, hoặc chỉ các trường được chọn.
 *
 * @since 2.6.0
 *
 * @param int|WP_Post $revision ID bản sửa đổi hoặc đối tượng bản sửa đổi.
 * @param array       $fields   Tùy chọn. Các trường để khôi phục từ. Mặc định tất cả.
 * @return int|false|null Null nếu lỗi, false nếu không có trường để khôi phục, (int) ID bài viết nếu thành công.
 */
function wp_restore_post_revision( $revision, $fields = null ) {
	$revision = wp_get_post_revision( $revision, ARRAY_A );

	if ( ! $revision ) {
		return $revision;
	}

	if ( ! is_array( $fields ) ) {
		$fields = array_keys( _wp_post_revision_fields( $revision ) );
	}

	$update = array();
	foreach ( array_intersect( array_keys( $revision ), $fields ) as $field ) {
		$update[ $field ] = $revision[ $field ];
	}

	if ( ! $update ) {
		return false;
	}

	$update['ID'] = $revision['post_parent'];

	$update = wp_slash( $update ); // Vì dữ liệu từ cơ sở dữ liệu.

	$post_id = wp_update_post( $update );

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Cập nhật người dùng chỉnh sửa cuối cùng.
	update_post_meta( $post_id, '_edit_last', get_current_user_id() );

	/**
	 * Kích hoạt sau khi một bản sửa đổi bài viết đã được khôi phục.
	 *
	 * @since 2.6.0
	 *
	 * @param int $post_id     ID bài viết.
	 * @param int $revision_id ID bản sửa đổi bài viết.
	 */
	do_action( 'wp_restore_post_revision', $post_id, $revision['ID'] );

	return $post_id;
}

/**
 * Khôi phục các giá trị meta được lưu phiên bản cho bài viết.
 *
 * @since 6.4.0
 *
 * @param int $post_id     ID của bài viết để khôi phục meta vào.
 * @param int $revision_id ID của bản sửa đổi để khôi phục meta từ.
 */
function wp_restore_post_revision_meta( $post_id, $revision_id ) {
	$post_type = get_post_type( $post_id );
	if ( ! $post_type ) {
		return;
	}

	// Khôi phục các trường meta được lưu phiên bản.
	foreach ( wp_post_revision_meta_keys( $post_type ) as $meta_key ) {

		// Xóa mọi meta hiện có.
		delete_post_meta( $post_id, $meta_key );

		_wp_copy_post_meta( $revision_id, $post_id, $meta_key );
	}
}

/**
 * Sao chép meta bài viết cho khóa đã cho từ bài viết này sang bài viết khác.
 *
 * @since 6.4.0
 *
 * @param int    $source_post_id ID bài viết nguồn để sao chép giá trị meta từ.
 * @param int    $target_post_id ID bài viết đích để sao chép giá trị meta tới.
 * @param string $meta_key       Khóa meta cần sao chép.
 */
function _wp_copy_post_meta( $source_post_id, $target_post_id, $meta_key ) {

	foreach ( get_post_meta( $source_post_id, $meta_key ) as $meta_value ) {
		/**
		 * Chúng ta sử dụng hàm add_metadata() thay vì add_post_meta() ở đây
		 * để cho phép bài viết đích là bản sửa đổi HOẶC bài viết thông thường.
		 */
		add_metadata( 'post', $target_post_id, $meta_key, wp_slash( $meta_value ) );
	}
}

/**
 * Xác định các trường meta bài viết nào nên được lưu phiên bản.
 *
 * @since 6.4.0
 *
 * @param string $post_type Loại bài viết đang được lưu phiên bản.
 * @return array Mảng các khóa meta cần được lưu phiên bản.
 */
function wp_post_revision_meta_keys( $post_type ) {
	$registered_meta = array_merge(
		get_registered_meta_keys( 'post' ),
		get_registered_meta_keys( 'post', $post_type )
	);

	$wp_revisioned_meta_keys = array();

	foreach ( $registered_meta as $name => $args ) {
		if ( $args['revisions_enabled'] ) {
			$wp_revisioned_meta_keys[ $name ] = true;
		}
	}

	$wp_revisioned_meta_keys = array_keys( $wp_revisioned_meta_keys );

	/**
	 * Lọc danh sách các khóa meta bài viết cần được lưu phiên bản.
	 *
	 * @since 6.4.0
	 *
	 * @param array  $keys      Mảng các trường meta cần được lưu phiên bản.
	 * @param string $post_type Loại bài viết đang được lưu phiên bản.
	 */
	return apply_filters( 'wp_post_revision_meta_keys', $wp_revisioned_meta_keys, $post_type );
}

/**
 * Kiểm tra xem các trường meta bài viết được lưu phiên bản có thay đổi không.
 *
 * @since 6.4.0
 *
 * @param bool    $post_has_changed Bài viết có thay đổi hay không.
 * @param WP_Post $last_revision    Đối tượng bài viết bản sửa đổi cuối cùng.
 * @param WP_Post $post             Đối tượng bài viết.
 * @return bool Bài viết có thay đổi hay không.
 */
function wp_check_revisioned_meta_fields_have_changed( $post_has_changed, WP_Post $last_revision, WP_Post $post ) {
	foreach ( wp_post_revision_meta_keys( $post->post_type ) as $meta_key ) {
		if ( get_post_meta( $post->ID, $meta_key ) !== get_post_meta( $last_revision->ID, $meta_key ) ) {
			$post_has_changed = true;
			break;
		}
	}
	return $post_has_changed;
}

/**
 * Xóa một bản sửa đổi.
 *
 * Xóa hàng từ bảng posts tương ứng với bản sửa đổi được chỉ định.
 *
 * @since 2.6.0
 *
 * @param int|WP_Post $revision ID bản sửa đổi hoặc đối tượng bản sửa đổi.
 * @return WP_Post|false|null Null hoặc false nếu lỗi, đối tượng bài viết đã xóa nếu thành công.
 */
function wp_delete_post_revision( $revision ) {
	$revision = wp_get_post_revision( $revision );

	if ( ! $revision ) {
		return $revision;
	}

	$delete = wp_delete_post( $revision->ID );

	if ( $delete ) {
		/**
		 * Kích hoạt khi một bản sửa đổi bài viết đã bị xóa.
		 *
		 * @since 2.6.0
		 *
		 * @param int     $revision_id ID bản sửa đổi bài viết.
		 * @param WP_Post $revision    Đối tượng bản sửa đổi bài viết.
		 */
		do_action( 'wp_delete_post_revision', $revision->ID, $revision );
	}

	return $delete;
}

/**
 * Trả về tất cả các bản sửa đổi của bài viết được chỉ định.
 *
 * @since 2.6.0
 *
 * @see get_children()
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục `$post`.
 * @param array|null  $args Tùy chọn. Các tham số để lấy bản sửa đổi bài viết. Mặc định null.
 * @return WP_Post[]|int[] Mảng các đối tượng hoặc ID bản sửa đổi, hoặc mảng rỗng nếu không có.
 */
function wp_get_post_revisions( $post = 0, $args = null ) {
	$post = get_post( $post );

	if ( ! $post || empty( $post->ID ) ) {
		return array();
	}

	$defaults = array(
		'order'         => 'DESC',
		'orderby'       => 'date ID',
		'check_enabled' => true,
	);
	$args     = wp_parse_args( $args, $defaults );

	if ( $args['check_enabled'] && ! wp_revisions_enabled( $post ) ) {
		return array();
	}

	$args = array_merge(
		$args,
		array(
			'post_parent' => $post->ID,
			'post_type'   => 'revision',
			'post_status' => 'inherit',
		)
	);

	$revisions = get_children( $args );

	if ( ! $revisions ) {
		return array();
	}

	return $revisions;
}

/**
 * Trả về ID bản sửa đổi mới nhất và số lượng bản sửa đổi cho bài viết.
 *
 * @since 6.1.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục $post.
 * @return array|WP_Error {
 *     Trả về mảng kết hợp với ID bản sửa đổi mới nhất và tổng số lượng,
 *     hoặc WP_Error nếu bài viết không tồn tại hoặc bản sửa đổi không được bật.
 *
 *     @type int $latest_id ID bản sửa đổi bài viết mới nhất hoặc 0 nếu không có bản sửa đổi.
 *     @type int $count     Tổng số bản sửa đổi cho bài viết đã cho.
 * }
 */
function wp_get_latest_revision_id_and_total_count( $post = 0 ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return new WP_Error( 'invalid_post', __( 'Invalid post.' ) );
	}

	if ( ! wp_revisions_enabled( $post ) ) {
		return new WP_Error( 'revisions_not_enabled', __( 'Revisions not enabled.' ) );
	}

	$args = array(
		'post_parent'         => $post->ID,
		'fields'              => 'ids',
		'post_type'           => 'revision',
		'post_status'         => 'inherit',
		'order'               => 'DESC',
		'orderby'             => 'date ID',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
	);

	$revision_query = new WP_Query();
	$revisions      = $revision_query->query( $args );

	if ( ! $revisions ) {
		return array(
			'latest_id' => 0,
			'count'     => 0,
		);
	}

	return array(
		'latest_id' => $revisions[0],
		'count'     => $revision_query->found_posts,
	);
}

/**
 * Trả về URL để xem và có thể khôi phục các bản sửa đổi của bài viết đã cho.
 *
 * @since 5.9.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là biến toàn cục `$post`.
 * @return string|null URL để chỉnh sửa các bản sửa đổi của bài viết đã cho, hoặc null.
 */
function wp_get_post_revisions_url( $post = 0 ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	// Nếu bài viết là bản sửa đổi, trả về sớm.
	if ( 'revision' === $post->post_type ) {
		return get_edit_post_link( $post );
	}

	if ( ! wp_revisions_enabled( $post ) ) {
		return null;
	}

	$revisions = wp_get_latest_revision_id_and_total_count( $post->ID );

	if ( is_wp_error( $revisions ) || 0 === $revisions['count'] ) {
		return null;
	}

	return get_edit_post_link( $revisions['latest_id'] );
}

/**
 * Xác định xem bản sửa đổi có được bật cho bài viết đã cho hay không.
 *
 * @since 3.6.0
 *
 * @param WP_Post $post Đối tượng bài viết.
 * @return bool True nếu số bản sửa đổi cần giữ không phải 0, false nếu ngược lại.
 */
function wp_revisions_enabled( $post ) {
	return wp_revisions_to_keep( $post ) !== 0;
}

/**
 * Xác định số lượng bản sửa đổi cần giữ lại cho bài viết đã cho.
 *
 * Mặc định, số lượng bản sửa đổi được giữ là không giới hạn.
 *
 * Hằng số WP_POST_REVISIONS có thể được thiết lập trong wp-config để chỉ định
 * giới hạn bản sửa đổi cần giữ.
 *
 * @since 3.6.0
 *
 * @param WP_Post $post Đối tượng bài viết.
 * @return int Số lượng bản sửa đổi cần giữ.
 */
function wp_revisions_to_keep( $post ) {
	$num = WP_POST_REVISIONS;

	if ( true === $num ) {
		$num = -1;
	} else {
		$num = (int) $num;
	}

	if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
		$num = 0;
	}

	/**
	 * Lọc số lượng bản sửa đổi cần lưu cho bài viết đã cho.
	 *
	 * Ghi đè giá trị của WP_POST_REVISIONS.
	 *
	 * @since 3.6.0
	 *
	 * @param int     $num  Số bản sửa đổi cần lưu trữ.
	 * @param WP_Post $post Đối tượng bài viết.
	 */
	$num = apply_filters( 'wp_revisions_to_keep', $num, $post );

	/**
	 * Lọc số lượng bản sửa đổi cần lưu cho bài viết đã cho theo loại bài viết.
	 *
	 * Ghi đè cả giá trị của WP_POST_REVISIONS và bộ lọc {@see 'wp_revisions_to_keep'}.
	 *
	 * Phần động của tên hook, `$post->post_type`, tham chiếu đến
	 * slug loại bài viết.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `wp_post_revisions_to_keep`
	 *  - `wp_page_revisions_to_keep`
	 *
	 * @since 5.8.0
	 *
	 * @param int     $num  Số bản sửa đổi cần lưu trữ.
	 * @param WP_Post $post Đối tượng bài viết.
	 */
	$num = apply_filters( "wp_{$post->post_type}_revisions_to_keep", $num, $post );

	return (int) $num;
}

/**
 * Thiết lập đối tượng bài viết cho xem trước dựa trên bản tự động lưu của bài viết.
 *
 * @since 2.7.0
 * @access private
 *
 * @param WP_Post $post
 * @return WP_Post|false
 */
function _set_preview( $post ) {
	if ( ! is_object( $post ) ) {
		return $post;
	}

	$preview = wp_get_post_autosave( $post->ID );

	if ( is_object( $preview ) ) {
		$preview = sanitize_post( $preview );

		$post->post_content = $preview->post_content;
		$post->post_title   = $preview->post_title;
		$post->post_excerpt = $preview->post_excerpt;
	}

	add_filter( 'get_the_terms', '_wp_preview_terms_filter', 10, 3 );
	add_filter( 'get_post_metadata', '_wp_preview_post_thumbnail_filter', 10, 3 );
	add_filter( 'get_post_metadata', '_wp_preview_meta_filter', 10, 4 );

	return $post;
}

/**
 * Lọc nội dung mới nhất để xem trước từ bản tự động lưu của bài viết.
 *
 * @since 2.7.0
 * @access private
 */
function _show_post_preview() {
	if ( isset( $_GET['preview_id'] ) && isset( $_GET['preview_nonce'] ) ) {
		$id = (int) $_GET['preview_id'];

		if ( false === wp_verify_nonce( $_GET['preview_nonce'], 'post_preview_' . $id ) ) {
			wp_die( __( 'Sorry, you are not allowed to preview drafts.' ), 403 );
		}

		add_filter( 'the_preview', '_set_preview' );
	}
}

/**
 * Lọc tra cứu thuật ngữ để thiết lập định dạng bài viết.
 *
 * @since 3.6.0
 * @access private
 *
 * @param array  $terms
 * @param int    $post_id
 * @param string $taxonomy
 * @return array
 */
function _wp_preview_terms_filter( $terms, $post_id, $taxonomy ) {
	$post = get_post();

	if ( ! $post ) {
		return $terms;
	}

	if ( empty( $_REQUEST['post_format'] ) || $post->ID !== $post_id
		|| 'post_format' !== $taxonomy || 'revision' === $post->post_type
	) {
		return $terms;
	}

	if ( 'standard' === $_REQUEST['post_format'] ) {
		$terms = array();
	} else {
		$term = get_term_by( 'slug', 'post-format-' . sanitize_key( $_REQUEST['post_format'] ), 'post_format' );

		if ( $term ) {
			$terms = array( $term ); // Chỉ có thể có một định dạng bài viết.
		}
	}

	return $terms;
}

/**
 * Lọc tra cứu ảnh đại diện bài viết để thiết lập ảnh đại diện bài viết.
 *
 * @since 4.6.0
 * @access private
 *
 * @param null|array|string $value    Giá trị trả về - một giá trị metadata đơn, hoặc mảng giá trị.
 * @param int               $post_id  ID bài viết.
 * @param string            $meta_key Khóa meta.
 * @return null|array Giá trị trả về mặc định hoặc mảng meta ảnh đại diện bài viết.
 */
function _wp_preview_post_thumbnail_filter( $value, $post_id, $meta_key ) {
	$post = get_post();

	if ( ! $post ) {
		return $value;
	}

	if ( empty( $_REQUEST['_thumbnail_id'] ) || empty( $_REQUEST['preview_id'] )
		|| $post->ID !== $post_id || $post_id !== (int) $_REQUEST['preview_id']
		|| '_thumbnail_id' !== $meta_key || 'revision' === $post->post_type
	) {
		return $value;
	}

	$thumbnail_id = (int) $_REQUEST['_thumbnail_id'];

	if ( $thumbnail_id <= 0 ) {
		return '';
	}

	return (string) $thumbnail_id;
}

/**
 * Lấy phiên bản bản sửa đổi bài viết.
 *
 * @since 3.6.0
 * @access private
 *
 * @param WP_Post $revision
 * @return int|false
 */
function _wp_get_post_revision_version( $revision ) {
	if ( is_object( $revision ) ) {
		$revision = get_object_vars( $revision );
	} elseif ( ! is_array( $revision ) ) {
		return false;
	}

	if ( preg_match( '/^\d+-(?:autosave|revision)-v(\d+)$/', $revision['post_name'], $matches ) ) {
		return (int) $matches[1];
	}

	return 0;
}

/**
 * Nâng cấp tác giả bản sửa đổi, thêm bài viết hiện tại dưới dạng bản sửa đổi và đặt phiên bản bản sửa đổi thành 1.
 *
 * @since 3.6.0
 * @access private
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param WP_Post $post      Đối tượng bài viết.
 * @param array   $revisions Các bản sửa đổi hiện tại của bài viết.
 * @return bool true nếu các bản sửa đổi đã được nâng cấp, false nếu có vấn đề.
 */
function _wp_upgrade_revisions_of_post( $post, $revisions ) {
	global $wpdb;

	// Thêm tùy chọn bài viết độc quyền.
	$lock   = "revision-upgrade-{$post->ID}";
	$now    = time();
	$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'off') /* LOCK */", $lock, $now ) );

	if ( ! $result ) {
		// Nếu không thể lấy khóa, xem khóa trước đó đã cũ bao lâu.
		$locked = get_option( $lock );

		if ( ! $locked ) {
			/*
			 * Không thể ghi vào khóa, và không thể đọc khóa.
			 * Đã xảy ra sự cố.
			 */
			return false;
		}

		if ( $locked > $now - HOUR_IN_SECONDS ) {
			// Khóa chưa quá cũ: tiến trình khác có thể đang nâng cấp bài viết này. Thoát.
			return false;
		}

		// Khóa quá cũ - cập nhật nó (bên dưới) và tiếp tục.
	}

	// Nếu chúng ta lấy được khóa, thêm lại tùy chọn để kích hoạt tất cả bộ lọc đúng.
	update_option( $lock, $now );

	reset( $revisions );
	$add_last = true;

	do {
		$this_revision = current( $revisions );
		$prev_revision = next( $revisions );

		$this_revision_version = _wp_get_post_revision_version( $this_revision );

		// Đã xảy ra lỗi nghiêm trọng.
		if ( false === $this_revision_version ) {
			continue;
		}

		/*
		 * 1 là phiên bản bản sửa đổi mới nhất, vậy chúng ta đã cập nhật rồi.
		 * Không cần thêm bản sao của bài viết dưới dạng bản sửa đổi mới nhất.
		 */
		if ( 0 < $this_revision_version ) {
			$add_last = false;
			continue;
		}

		// Luôn cập nhật phiên bản bản sửa đổi.
		$update = array(
			'post_name' => preg_replace( '/^(\d+-(?:autosave|revision))[\d-]*$/', '$1-v1', $this_revision->post_name ),
		);

		/*
		 * Nếu bản sửa đổi này là bản sửa đổi cũ nhất của bài viết, tức là không có $prev_revision,
		 * post_author đúng có lẽ là $post->post_author, nhưng đó chỉ là phỏng đoán tốt.
		 * Chỉ cập nhật phiên bản bản sửa đổi và giữ nguyên tác giả.
		 */
		if ( $prev_revision ) {
			$prev_revision_version = _wp_get_post_revision_version( $prev_revision );

			// Nếu bản sửa đổi trước đó đã được cập nhật, nó không còn chứa thông tin chúng ta cần :(
			if ( $prev_revision_version < 1 ) {
				$update['post_author'] = $prev_revision->post_author;
			}
		}

		// Nâng cấp bản sửa đổi này.
		$result = $wpdb->update( $wpdb->posts, $update, array( 'ID' => $this_revision->ID ) );

		if ( $result ) {
			wp_cache_delete( $this_revision->ID, 'posts' );
		}
	} while ( $prev_revision );

	delete_option( $lock );

	// Thêm bản sao của bài viết dưới dạng bản sửa đổi mới nhất.
	if ( $add_last ) {
		wp_save_post_revision( $post->ID );
	}

	return true;
}

/**
 * Lọc việc lấy meta bài viết xem trước để nhận giá trị từ bản tự động lưu.
 *
 * Chỉ lọc các khóa meta được lưu phiên bản.
 *
 * @since 6.4.0
 *
 * @param mixed  $value     Giá trị meta cần lọc.
 * @param int    $object_id ID đối tượng.
 * @param string $meta_key  Khóa meta cần lọc giá trị cho.
 * @param bool   $single    Có trả về giá trị đơn hay không.
 * @return mixed Giá trị meta gốc nếu khóa meta không được lưu phiên bản, đối tượng không tồn tại,
 *               loại bài viết là bản sửa đổi hoặc ID bài viết không khớp với ID đối tượng.
 *               Nếu không, giá trị meta được lưu phiên bản sẽ được trả về cho xem trước.
 */
function _wp_preview_meta_filter( $value, $object_id, $meta_key, $single ) {
	$post = get_post();

	if ( empty( $post )
		|| $post->ID !== $object_id
		|| ! in_array( $meta_key, wp_post_revision_meta_keys( $post->post_type ), true )
		|| 'revision' === $post->post_type
	) {
		return $value;
	}

	$preview = wp_get_post_autosave( $post->ID );

	if ( false === $preview ) {
		return $value;
	}

	return get_post_meta( $preview->ID, $meta_key, $single );
}
