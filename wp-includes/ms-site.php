<?php
/**
 * API Site
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 5.1.0
 */

/**
 * Chèn một site mới vào cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $data {
 *     Dữ liệu cho site mới cần được chèn.
 *
 *     @type string $domain       Tên miền site. Mặc định chuỗi rỗng.
 *     @type string $path         Đường dẫn site. Mặc định '/'.
 *     @type int    $network_id   ID mạng của site. Mặc định là ID mạng hiện tại.
 *     @type string $registered   Thời điểm site được đăng ký, theo định dạng datetime SQL. Mặc định là
 *                                thời gian hiện tại.
 *     @type string $last_updated Thời điểm site được cập nhật lần cuối, theo định dạng datetime SQL. Mặc định là
 *                                giá trị của $registered.
 *     @type int    $public       Site có công khai hay không. Mặc định 1.
 *     @type int    $archived     Site có bị lưu trữ hay không. Mặc định 0.
 *     @type int    $mature       Site có đánh dấu trưởng thành hay không. Mặc định 0.
 *     @type int    $spam         Site có phải spam hay không. Mặc định 0.
 *     @type int    $deleted      Site có bị xóa hay không. Mặc định 0.
 *     @type int    $lang_id      ID ngôn ngữ của site. Hiện chưa sử dụng. Mặc định 0.
 *     @type int    $user_id      ID người dùng cho quản trị viên site. Được truyền tới
 *                                hook `wp_initialize_site`.
 *     @type string $title        Tiêu đề site. Mặc định là 'Site %d' trong đó %d là ID site. Được truyền
 *                                tới hook `wp_initialize_site`.
 *     @type array  $options      Các cặp tùy chọn tùy chỉnh $key => $value để sử dụng. Mặc định mảng rỗng. Được truyền
 *                                tới hook `wp_initialize_site`.
 *     @type array  $meta         Các cặp metadata tùy chỉnh $key => $value cho site. Mặc định mảng rỗng.
 *                                Được truyền tới hook `wp_initialize_site`.
 * }
 * @return int|WP_Error ID của site mới khi thành công, hoặc đối tượng lỗi khi thất bại.
 */
function wp_insert_site( array $data ) {
	global $wpdb;

	$now = current_time( 'mysql', true );

	$defaults = array(
		'domain'       => '',
		'path'         => '/',
		'network_id'   => get_current_network_id(),
		'registered'   => $now,
		'last_updated' => $now,
		'public'       => 1,
		'archived'     => 0,
		'mature'       => 0,
		'spam'         => 0,
		'deleted'      => 0,
		'lang_id'      => 0,
	);

	$prepared_data = wp_prepare_site_data( $data, $defaults );
	if ( is_wp_error( $prepared_data ) ) {
		return $prepared_data;
	}

	if ( false === $wpdb->insert( $wpdb->blogs, $prepared_data ) ) {
		return new WP_Error( 'db_insert_error', __( 'Could not insert site into the database.' ), $wpdb->last_error );
	}

	$site_id = (int) $wpdb->insert_id;

	clean_blog_cache( $site_id );

	$new_site = get_site( $site_id );

	if ( ! $new_site ) {
		return new WP_Error( 'get_site_error', __( 'Could not retrieve site data.' ) );
	}

	/**
	 * Kích hoạt khi một site đã được chèn vào cơ sở dữ liệu.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Site $new_site Đối tượng site mới.
	 */
	do_action( 'wp_insert_site', $new_site );

	// Trích xuất các tham số được truyền có thể liên quan đến việc khởi tạo site.
	$args = array_diff_key( $data, $defaults );
	if ( isset( $args['site_id'] ) ) {
		unset( $args['site_id'] );
	}

	/**
	 * Kích hoạt khi quy trình khởi tạo site cần được thực thi.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Site $new_site Đối tượng site mới.
	 * @param array   $args     Các tham số cho việc khởi tạo.
	 */
	do_action( 'wp_initialize_site', $new_site, $args );

	// Chỉ tính toán các tham số hook bổ sung nếu hook đã lỗi thời thực sự đang được sử dụng.
	if ( has_action( 'wpmu_new_blog' ) ) {
		$user_id = ! empty( $args['user_id'] ) ? $args['user_id'] : 0;
		$meta    = ! empty( $args['options'] ) ? $args['options'] : array();

		// WPLANG đã được truyền với `$meta` tới hook `wpmu_new_blog` trước phiên bản 5.1.0.
		if ( ! array_key_exists( 'WPLANG', $meta ) ) {
			$meta['WPLANG'] = get_network_option( $new_site->network_id, 'WPLANG' );
		}

		/*
		 * Xây dựng lại dữ liệu được mong đợi bởi hook `wpmu_new_blog` trước phiên bản 5.1.0 sử dụng các khóa được phép.
		 * `$allowed_data_fields` khớp với cái được sử dụng trong `wpmu_create_blog()`.
		 */
		$allowed_data_fields = array( 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id' );
		$meta                = array_merge( array_intersect_key( $data, array_flip( $allowed_data_fields ) ), $meta );

		/**
		 * Kích hoạt ngay sau khi một site mới được tạo.
		 *
		 * @since MU (3.0.0)
		 * @deprecated 5.1.0 Sử dụng {@see 'wp_initialize_site'} thay thế.
		 *
		 * @param int    $site_id    ID site.
		 * @param int    $user_id    ID người dùng.
		 * @param string $domain     Tên miền site.
		 * @param string $path       Đường dẫn site.
		 * @param int    $network_id ID mạng. Chỉ liên quan trên các cài đặt đa mạng.
		 * @param array  $meta       Dữ liệu meta. Được sử dụng để thiết lập các tùy chọn site ban đầu.
		 */
		do_action_deprecated(
			'wpmu_new_blog',
			array( $new_site->id, $user_id, $new_site->domain, $new_site->path, $new_site->network_id, $meta ),
			'5.1.0',
			'wp_initialize_site'
		);
	}

	return (int) $new_site->id;
}

/**
 * Cập nhật một site trong cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int   $site_id ID của site cần được cập nhật.
 * @param array $data    Dữ liệu site cần cập nhật. Xem {@see wp_insert_site()} để biết danh sách các khóa được hỗ trợ.
 * @return int|WP_Error ID của site đã cập nhật khi thành công, hoặc đối tượng lỗi khi thất bại.
 */
function wp_update_site( $site_id, array $data ) {
	global $wpdb;

	if ( empty( $site_id ) ) {
		return new WP_Error( 'site_empty_id', __( 'Site ID must not be empty.' ) );
	}

	$old_site = get_site( $site_id );
	if ( ! $old_site ) {
		return new WP_Error( 'site_not_exist', __( 'Site does not exist.' ) );
	}

	$defaults                 = $old_site->to_array();
	$defaults['network_id']   = (int) $defaults['site_id'];
	$defaults['last_updated'] = current_time( 'mysql', true );
	unset( $defaults['blog_id'], $defaults['site_id'] );

	$data = wp_prepare_site_data( $data, $defaults, $old_site );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( false === $wpdb->update( $wpdb->blogs, $data, array( 'blog_id' => $old_site->id ) ) ) {
		return new WP_Error( 'db_update_error', __( 'Could not update site in the database.' ), $wpdb->last_error );
	}

	clean_blog_cache( $old_site );

	$new_site = get_site( $old_site->id );

	/**
	 * Kích hoạt khi một site đã được cập nhật trong cơ sở dữ liệu.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Site $new_site Đối tượng site mới.
	 * @param WP_Site $old_site Đối tượng site cũ.
	 */
	do_action( 'wp_update_site', $new_site, $old_site );

	return (int) $new_site->id;
}

/**
 * Xóa một site khỏi cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $site_id ID của site cần được xóa.
 * @return WP_Site|WP_Error Đối tượng site đã xóa khi thành công, hoặc đối tượng lỗi khi thất bại.
 */
function wp_delete_site( $site_id ) {
	global $wpdb;

	if ( empty( $site_id ) ) {
		return new WP_Error( 'site_empty_id', __( 'Site ID must not be empty.' ) );
	}

	$old_site = get_site( $site_id );
	if ( ! $old_site ) {
		return new WP_Error( 'site_not_exist', __( 'Site does not exist.' ) );
	}

	$errors = new WP_Error();

	/**
	 * Kích hoạt trước khi một site bị xóa khỏi cơ sở dữ liệu.
	 *
	 * Plugin nên bổ sung vào đối tượng `$errors` thông qua phương thức `WP_Error::add()`. Nếu có bất kỳ
	 * lỗi nào, site sẽ không bị xóa.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Error $errors   Đối tượng lỗi để thêm các lỗi xác thực.
	 * @param WP_Site  $old_site Đối tượng site sẽ bị xóa.
	 */
	do_action( 'wp_validate_site_deletion', $errors, $old_site );

	if ( ! empty( $errors->errors ) ) {
		return $errors;
	}

	/**
	 * Kích hoạt trước khi một site bị xóa.
	 *
	 * @since MU (3.0.0)
	 * @deprecated 5.1.0
	 *
	 * @param int  $site_id ID site.
	 * @param bool $drop    True nếu bảng của site nên bị xóa. Mặc định false.
	 */
	do_action_deprecated( 'delete_blog', array( $old_site->id, true ), '5.1.0' );

	/**
	 * Kích hoạt khi quy trình hủy khởi tạo site cần được thực thi.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Site $old_site Đối tượng site đã xóa.
	 */
	do_action( 'wp_uninitialize_site', $old_site );

	if ( is_site_meta_supported() ) {
		$blog_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->blogmeta WHERE blog_id = %d ", $old_site->id ) );
		foreach ( $blog_meta_ids as $mid ) {
			delete_metadata_by_mid( 'blog', $mid );
		}
	}

	if ( false === $wpdb->delete( $wpdb->blogs, array( 'blog_id' => $old_site->id ) ) ) {
		return new WP_Error( 'db_delete_error', __( 'Could not delete site from the database.' ), $wpdb->last_error );
	}

	clean_blog_cache( $old_site );

	/**
	 * Kích hoạt khi một site đã bị xóa khỏi cơ sở dữ liệu.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Site $old_site Đối tượng site đã xóa.
	 */
	do_action( 'wp_delete_site', $old_site );

	/**
	 * Kích hoạt sau khi site bị xóa khỏi mạng.
	 *
	 * @since 4.8.0
	 * @deprecated 5.1.0
	 *
	 * @param int  $site_id ID site.
	 * @param bool $drop    True nếu các bảng của site nên bị xóa. Mặc định false.
	 */
	do_action_deprecated( 'deleted_blog', array( $old_site->id, true ), '5.1.0' );

	return $old_site;
}

/**
 * Lấy dữ liệu site dựa trên ID site hoặc đối tượng site.
 *
 * Dữ liệu site sẽ được lưu cache và trả về sau khi được truyền qua bộ lọc.
 * Nếu site được cung cấp rỗng, biến toàn cục site hiện tại sẽ được sử dụng.
 *
 * @since 4.6.0
 *
 * @param WP_Site|int|null $site Tùy chọn. Site cần lấy. Mặc định là site hiện tại.
 * @return WP_Site|null Đối tượng site hoặc null nếu không tìm thấy.
 */
function get_site( $site = null ) {
	if ( empty( $site ) ) {
		$site = get_current_blog_id();
	}

	if ( $site instanceof WP_Site ) {
		$_site = $site;
	} elseif ( is_object( $site ) ) {
		$_site = new WP_Site( $site );
	} else {
		$_site = WP_Site::get_instance( $site );
	}

	if ( ! $_site ) {
		return null;
	}

	/**
	 * Kích hoạt sau khi một site được lấy.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Site $_site Dữ liệu site.
	 */
	$_site = apply_filters( 'get_site', $_site );

	return $_site;
}

/**
 * Thêm các site từ danh sách ID đã cho vào cache nếu chưa tồn tại trong cache.
 *
 * @since 4.6.0
 * @since 5.1.0 Thêm tham số `$update_meta_cache`.
 * @since 6.1.0 Hàm này không còn được đánh dấu là "private".
 * @since 6.3.0 Sử dụng wp_lazyload_site_meta() để lazy-loading metadata site.
 *
 * @see update_site_cache()
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $ids               Danh sách ID.
 * @param bool  $update_meta_cache Tùy chọn. Có cập nhật cache meta hay không. Mặc định true.
 */
function _prime_site_caches( $ids, $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'sites' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_sites = $wpdb->get_results( sprintf( "SELECT * FROM $wpdb->blogs WHERE blog_id IN (%s)", implode( ',', array_map( 'intval', $non_cached_ids ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		update_site_cache( $fresh_sites, false );
	}

	if ( $update_meta_cache ) {
		wp_lazyload_site_meta( $ids );
	}
}

/**
 * Đưa metadata site vào hàng đợi để lazy-loading.
 *
 * @since 6.3.0
 *
 * @param array $site_ids Danh sách ID site.
 */
function wp_lazyload_site_meta( array $site_ids ) {
	if ( empty( $site_ids ) ) {
		return;
	}
	$lazyloader = wp_metadata_lazyloader();
	$lazyloader->queue_objects( 'blog', $site_ids );
}

/**
 * Cập nhật các site trong cache.
 *
 * @since 4.6.0
 * @since 5.1.0 Thêm tham số `$update_meta_cache`.
 *
 * @param array $sites             Mảng các đối tượng site.
 * @param bool  $update_meta_cache Có cập nhật cache metadata site hay không. Mặc định true.
 */
function update_site_cache( $sites, $update_meta_cache = true ) {
	if ( ! $sites ) {
		return;
	}
	$site_ids          = array();
	$site_data         = array();
	$blog_details_data = array();
	foreach ( $sites as $site ) {
		$site_ids[]                                    = $site->blog_id;
		$site_data[ $site->blog_id ]                   = $site;
		$blog_details_data[ $site->blog_id . 'short' ] = $site;

	}
	wp_cache_add_multiple( $site_data, 'sites' );
	wp_cache_add_multiple( $blog_details_data, 'blog-details' );

	if ( $update_meta_cache ) {
		update_sitemeta_cache( $site_ids );
	}
}

/**
 * Cập nhật cache metadata cho danh sách ID site.
 *
 * Thực hiện truy vấn SQL để lấy tất cả metadata cho các site khớp với `$site_ids` và lưu vào cache.
 * Các lần gọi tiếp theo tới `get_site_meta()` sẽ không cần truy vấn cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @param array $site_ids Danh sách ID site.
 * @return array|false Một mảng metadata khi thành công, false nếu không có gì để cập nhật.
 */
function update_sitemeta_cache( $site_ids ) {
	// Đảm bảo bộ lọc này được hook ngay cả khi hàm được gọi sớm.
	if ( ! has_filter( 'update_blog_metadata_cache', 'wp_check_site_meta_support_prefilter' ) ) {
		add_filter( 'update_blog_metadata_cache', 'wp_check_site_meta_support_prefilter' );
	}
	return update_meta_cache( 'blog', $site_ids );
}

/**
 * Lấy danh sách các site khớp với các tham số được yêu cầu.
 *
 * @since 4.6.0
 * @since 4.8.0 Thêm các tham số 'lang_id', 'lang__in', và 'lang__not_in'.
 *
 * @see WP_Site_Query::parse_query()
 *
 * @param string|array $args Tùy chọn. Mảng hoặc chuỗi các tham số. Xem WP_Site_Query::__construct()
 *                           để biết thông tin về các tham số được chấp nhận. Mặc định mảng rỗng.
 * @return WP_Site[]|int[]|int Danh sách đối tượng WP_Site, danh sách ID site khi 'fields' được đặt thành 'ids',
 *                             hoặc số lượng site khi 'count' được truyền như biến truy vấn.
 */
function get_sites( $args = array() ) {
	$query = new WP_Site_Query();

	return $query->query( $args );
}

/**
 * Chuẩn bị dữ liệu site để chèn hoặc cập nhật trong cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @param array        $data     Mảng liên kết dữ liệu site được truyền tới hàm tương ứng.
 *                               Xem {@see wp_insert_site()} để biết dữ liệu có thể bao gồm.
 * @param array        $defaults Giá trị mặc định dữ liệu site để phân tích $data.
 * @param WP_Site|null $old_site Tùy chọn. Đối tượng site cũ nếu là cập nhật, hoặc null nếu là chèn mới.
 *                               Mặc định null.
 * @return array|WP_Error Dữ liệu site sẵn sàng cho giao dịch cơ sở dữ liệu, hoặc WP_Error trong trường hợp
 *                        xảy ra lỗi xác thực.
 */
function wp_prepare_site_data( $data, $defaults, $old_site = null ) {

	// Duy trì tương thích ngược với `$site_id` như ID mạng.
	if ( isset( $data['site_id'] ) ) {
		if ( ! empty( $data['site_id'] ) && empty( $data['network_id'] ) ) {
			$data['network_id'] = $data['site_id'];
		}
		unset( $data['site_id'] );
	}

	/**
	 * Lọc dữ liệu site được truyền để chuẩn hóa nó.
	 *
	 * @since 5.1.0
	 *
	 * @param array $data Mảng liên kết dữ liệu site được truyền tới hàm tương ứng.
	 *                    Xem {@see wp_insert_site()} để biết dữ liệu có thể bao gồm.
	 */
	$data = apply_filters( 'wp_normalize_site_data', $data );

	$allowed_data_fields = array( 'domain', 'path', 'network_id', 'registered', 'last_updated', 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id' );
	$data                = array_intersect_key( wp_parse_args( $data, $defaults ), array_flip( $allowed_data_fields ) );

	$errors = new WP_Error();

	/**
	 * Kích hoạt khi dữ liệu cần được xác thực cho một site trước khi chèn hoặc cập nhật trong cơ sở dữ liệu.
	 *
	 * Plugin nên bổ sung vào đối tượng `$errors` thông qua phương thức `WP_Error::add()`.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Error     $errors   Đối tượng lỗi để thêm các lỗi xác thực.
	 * @param array        $data     Mảng liên kết dữ liệu site đầy đủ. Xem {@see wp_insert_site()}
	 *                               để biết dữ liệu bao gồm.
	 * @param WP_Site|null $old_site Đối tượng site cũ nếu dữ liệu thuộc về site đang được cập nhật,
	 *                               hoặc null nếu là site mới đang được chèn.
	 */
	do_action( 'wp_validate_site_data', $errors, $data, $old_site );

	if ( ! empty( $errors->errors ) ) {
		return $errors;
	}

	// Chuẩn bị cho cơ sở dữ liệu.
	$data['site_id'] = $data['network_id'];
	unset( $data['network_id'] );

	return $data;
}

/**
 * Chuẩn hóa dữ liệu cho một site trước khi chèn hoặc cập nhật trong cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @param array $data Mảng liên kết dữ liệu site được truyền tới hàm tương ứng.
 *                    Xem {@see wp_insert_site()} để biết dữ liệu có thể bao gồm.
 * @return array Dữ liệu site đã được chuẩn hóa.
 */
function wp_normalize_site_data( $data ) {
	// Làm sạch tên miền nếu được truyền.
	if ( array_key_exists( 'domain', $data ) ) {
		$data['domain'] = preg_replace( '/[^a-z0-9\-.:]+/i', '', $data['domain'] );
	}

	// Làm sạch đường dẫn nếu được truyền.
	if ( array_key_exists( 'path', $data ) ) {
		$data['path'] = trailingslashit( '/' . trim( $data['path'], '/' ) );
	}

	// Làm sạch ID mạng nếu được truyền.
	if ( array_key_exists( 'network_id', $data ) ) {
		$data['network_id'] = (int) $data['network_id'];
	}

	// Làm sạch các trường trạng thái nếu được truyền.
	$status_fields = array( 'public', 'archived', 'mature', 'spam', 'deleted' );
	foreach ( $status_fields as $status_field ) {
		if ( array_key_exists( $status_field, $data ) ) {
			$data[ $status_field ] = (int) $data[ $status_field ];
		}
	}

	// Loại bỏ các trường ngày nếu rỗng.
	$date_fields = array( 'registered', 'last_updated' );
	foreach ( $date_fields as $date_field ) {
		if ( ! array_key_exists( $date_field, $data ) ) {
			continue;
		}

		if ( empty( $data[ $date_field ] ) || '0000-00-00 00:00:00' === $data[ $date_field ] ) {
			unset( $data[ $date_field ] );
		}
	}

	return $data;
}

/**
 * Xác thực dữ liệu cho một site trước khi chèn hoặc cập nhật trong cơ sở dữ liệu.
 *
 * @since 5.1.0
 *
 * @param WP_Error     $errors   Đối tượng lỗi, được truyền theo tham chiếu. Sẽ chứa lỗi xác thực nếu
 *                               có bất kỳ lỗi nào xảy ra.
 * @param array        $data     Mảng liên kết dữ liệu site đầy đủ. Xem {@see wp_insert_site()}
 *                               để biết dữ liệu bao gồm.
 * @param WP_Site|null $old_site Đối tượng site cũ nếu dữ liệu thuộc về site đang được cập nhật,
 *                               hoặc null nếu là site mới đang được chèn.
 */
function wp_validate_site_data( $errors, $data, $old_site = null ) {
	// Tên miền phải luôn được cung cấp.
	if ( empty( $data['domain'] ) ) {
		$errors->add( 'site_empty_domain', __( 'Site domain must not be empty.' ) );
	}

	// Đường dẫn phải luôn được cung cấp.
	if ( empty( $data['path'] ) ) {
		$errors->add( 'site_empty_path', __( 'Site path must not be empty.' ) );
	}

	// ID mạng phải luôn được cung cấp.
	if ( empty( $data['network_id'] ) ) {
		$errors->add( 'site_empty_network_id', __( 'Site network ID must be provided.' ) );
	}

	// Cả ngày đăng ký và ngày cập nhật lần cuối phải luôn được cung cấp và hợp lệ.
	$date_fields = array( 'registered', 'last_updated' );
	foreach ( $date_fields as $date_field ) {
		if ( empty( $data[ $date_field ] ) ) {
			$errors->add( 'site_empty_' . $date_field, __( 'Both registration and last updated dates must be provided.' ) );
			break;
		}

		// Cho phép '0000-00-00 00:00:00', mặc dù nó sẽ bị loại bỏ tại thời điểm này.
		if ( '0000-00-00 00:00:00' !== $data[ $date_field ] ) {
			$month      = substr( $data[ $date_field ], 5, 2 );
			$day        = substr( $data[ $date_field ], 8, 2 );
			$year       = substr( $data[ $date_field ], 0, 4 );
			$valid_date = wp_checkdate( $month, $day, $year, $data[ $date_field ] );
			if ( ! $valid_date ) {
				$errors->add( 'site_invalid_' . $date_field, __( 'Both registration and last updated dates must be valid dates.' ) );
				break;
			}
		}
	}

	if ( ! empty( $errors->errors ) ) {
		return;
	}

	// Nếu là site mới, hoặc tên miền/đường dẫn/ID mạng đã thay đổi, đảm bảo tính duy nhất.
	if ( ! $old_site
		|| $data['domain'] !== $old_site->domain
		|| $data['path'] !== $old_site->path
		|| $data['network_id'] !== $old_site->network_id
	) {
		if ( domain_exists( $data['domain'], $data['path'], $data['network_id'] ) ) {
			$errors->add( 'site_taken', __( 'Sorry, that site already exists!' ) );
		}
	}
}

/**
 * Chạy quy trình khởi tạo cho một site đã cho.
 *
 * Quy trình này bao gồm tạo các bảng cơ sở dữ liệu của site và
 * điền dữ liệu mặc định vào chúng.
 *
 * @since 5.1.0
 *
 * @global wpdb     $wpdb     Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @global WP_Roles $wp_roles Đối tượng quản lý vai trò WordPress.
 *
 * @param int|WP_Site $site_id ID site hoặc đối tượng.
 * @param array       $args    {
 *     Tùy chọn. Các tham số để thay đổi hành vi khởi tạo.
 *
 *     @type int    $user_id Bắt buộc. ID người dùng cho quản trị viên site.
 *     @type string $title   Tiêu đề site. Mặc định là 'Site %d' trong đó %d là
 *                           ID site.
 *     @type array  $options Các cặp tùy chọn tùy chỉnh $key => $value để sử dụng. Mặc định
 *                           mảng rỗng.
 *     @type array  $meta    Các cặp metadata tùy chỉnh $key => $value cho site. Mặc định
 *                           mảng rỗng.
 * }
 * @return true|WP_Error True khi thành công, hoặc đối tượng lỗi khi thất bại.
 */
function wp_initialize_site( $site_id, array $args = array() ) {
	global $wpdb, $wp_roles;

	if ( empty( $site_id ) ) {
		return new WP_Error( 'site_empty_id', __( 'Site ID must not be empty.' ) );
	}

	$site = get_site( $site_id );
	if ( ! $site ) {
		return new WP_Error( 'site_invalid_id', __( 'Site with the ID does not exist.' ) );
	}

	if ( wp_is_site_initialized( $site ) ) {
		return new WP_Error( 'site_already_initialized', __( 'The site appears to be already initialized.' ) );
	}

	$network = get_network( $site->network_id );
	if ( ! $network ) {
		$network = get_network();
	}

	$args = wp_parse_args(
		$args,
		array(
			'user_id' => 0,
			/* translators: %d: ID Site. */
			'title'   => sprintf( __( 'Site %d' ), $site->id ),
			'options' => array(),
			'meta'    => array(),
		)
	);

	/**
	 * Lọc các tham số để khởi tạo một site.
	 *
	 * @since 5.1.0
	 *
	 * @param array      $args    Các tham số để thay đổi hành vi khởi tạo.
	 * @param WP_Site    $site    Site đang được khởi tạo.
	 * @param WP_Network $network Mạng mà site thuộc về.
	 */
	$args = apply_filters( 'wp_initialize_site_args', $args, $site, $network );

	$orig_installing = wp_installing();
	if ( ! $orig_installing ) {
		wp_installing( true );
	}

	$switch = false;
	if ( get_current_blog_id() !== $site->id ) {
		$switch = true;
		switch_to_blog( $site->id );
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Thiết lập các bảng cơ sở dữ liệu.
	make_db_current_silent( 'blog' );

	$home_scheme    = 'http';
	$siteurl_scheme = 'http';
	if ( ! is_subdomain_install() ) {
		if ( 'https' === parse_url( get_home_url( $network->site_id ), PHP_URL_SCHEME ) ) {
			$home_scheme = 'https';
		}
		if ( 'https' === parse_url( get_network_option( $network->id, 'siteurl' ), PHP_URL_SCHEME ) ) {
			$siteurl_scheme = 'https';
		}
	}

	// Điền các tùy chọn cho site.
	populate_options(
		array_merge(
			array(
				'home'        => untrailingslashit( $home_scheme . '://' . $site->domain . $site->path ),
				'siteurl'     => untrailingslashit( $siteurl_scheme . '://' . $site->domain . $site->path ),
				'blogname'    => wp_unslash( $args['title'] ),
				'admin_email' => '',
				'upload_path' => get_network_option( $network->id, 'ms_files_rewriting' ) ? UPLOADBLOGSDIR . "/{$site->id}/files" : get_blog_option( $network->site_id, 'upload_path' ),
				'blog_public' => (int) $site->public,
				'WPLANG'      => get_network_option( $network->id, 'WPLANG' ),
			),
			$args['options']
		)
	);

	// Xóa cache blog sau khi điền tùy chọn.
	clean_blog_cache( $site );

	// Điền các vai trò cho site.
	populate_roles();
	$wp_roles = new WP_Roles();

	// Điền metadata cho site.
	populate_site_meta( $site->id, $args['meta'] );

	// Xóa tất cả quyền có thể tồn tại cho site.
	$table_prefix = $wpdb->get_blog_prefix();
	delete_metadata( 'user', 0, $table_prefix . 'user_level', null, true );   // Xóa tất cả.
	delete_metadata( 'user', 0, $table_prefix . 'capabilities', null, true ); // Xóa tất cả.

	// Cài đặt nội dung mặc định cho site.
	wp_install_defaults( $args['user_id'] );

	// Thiết lập quản trị viên site.
	add_user_to_blog( $site->id, $args['user_id'], 'administrator' );
	if ( ! user_can( $args['user_id'], 'manage_network' ) && ! get_user_meta( $args['user_id'], 'primary_blog', true ) ) {
		update_user_meta( $args['user_id'], 'primary_blog', $site->id );
	}

	if ( $switch ) {
		restore_current_blog();
	}

	wp_installing( $orig_installing );

	return true;
}

/**
 * Chạy quy trình hủy khởi tạo cho một site đã cho.
 *
 * Quy trình này bao gồm xóa các bảng cơ sở dữ liệu của site và xóa thư mục tải lên.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Site $site_id ID site hoặc đối tượng.
 * @return true|WP_Error True khi thành công, hoặc đối tượng lỗi khi thất bại.
 */
function wp_uninitialize_site( $site_id ) {
	global $wpdb;

	if ( empty( $site_id ) ) {
		return new WP_Error( 'site_empty_id', __( 'Site ID must not be empty.' ) );
	}

	$site = get_site( $site_id );
	if ( ! $site ) {
		return new WP_Error( 'site_invalid_id', __( 'Site with the ID does not exist.' ) );
	}

	if ( ! wp_is_site_initialized( $site ) ) {
		return new WP_Error( 'site_already_uninitialized', __( 'The site appears to be already uninitialized.' ) );
	}

	$users = get_users(
		array(
			'blog_id' => $site->id,
			'fields'  => 'ids',
		)
	);

	// Xóa người dùng khỏi site.
	if ( ! empty( $users ) ) {
		foreach ( $users as $user_id ) {
			remove_user_from_blog( $user_id, $site->id );
		}
	}

	$switch = false;
	if ( get_current_blog_id() !== $site->id ) {
		$switch = true;
		switch_to_blog( $site->id );
	}

	$uploads = wp_get_upload_dir();

	$tables = $wpdb->tables( 'blog' );

	/**
	 * Lọc các bảng cần xóa khi site bị xóa.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string[] $tables  Mảng tên các bảng site sẽ bị xóa.
	 * @param int      $site_id ID của site cần xóa bảng.
	 */
	$drop_tables = apply_filters( 'wpmu_drop_tables', $tables, $site->id );

	foreach ( (array) $drop_tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Lọc thư mục cơ sở tải lên cần xóa khi site bị xóa.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $basedir Đường dẫn tải lên không có thư mục con. Xem {@see wp_upload_dir()}.
	 * @param int    $site_id ID site.
	 */
	$dir     = apply_filters( 'wpmu_delete_blog_upload_dir', $uploads['basedir'], $site->id );
	$dir     = rtrim( $dir, DIRECTORY_SEPARATOR );
	$top_dir = $dir;
	$stack   = array( $dir );
	$index   = 0;

	while ( $index < count( $stack ) ) {
		// Lấy thư mục được đánh chỉ mục từ ngăn xếp.
		$dir = $stack[ $index ];

		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$dh = @opendir( $dir );
		if ( $dh ) {
			$file = @readdir( $dh );
			while ( false !== $file ) {
				if ( '.' === $file || '..' === $file ) {
					$file = @readdir( $dh );
					continue;
				}

				if ( @is_dir( $dir . DIRECTORY_SEPARATOR . $file ) ) {
					$stack[] = $dir . DIRECTORY_SEPARATOR . $file;
				} elseif ( @is_file( $dir . DIRECTORY_SEPARATOR . $file ) ) {
					@unlink( $dir . DIRECTORY_SEPARATOR . $file );
				}

				$file = @readdir( $dh );
			}
			@closedir( $dh );
		}
		++$index;
	}

	$stack = array_reverse( $stack ); // Các thư mục được thêm sau cùng là sâu nhất.
	foreach ( (array) $stack as $dir ) {
		if ( $dir !== $top_dir ) {
			@rmdir( $dir );
		}
	}

	// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged
	if ( $switch ) {
		restore_current_blog();
	}

	return true;
}

/**
 * Kiểm tra xem một site đã được khởi tạo hay chưa.
 *
 * Một site được coi là đã khởi tạo khi các bảng cơ sở dữ liệu của nó hiện diện.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|WP_Site $site_id ID site hoặc đối tượng.
 * @return bool True nếu site đã được khởi tạo, false nếu ngược lại.
 */
function wp_is_site_initialized( $site_id ) {
	global $wpdb;

	if ( is_object( $site_id ) ) {
		$site_id = $site_id->blog_id;
	}
	$site_id = (int) $site_id;

	/**
	 * Lọc việc kiểm tra xem một site đã được khởi tạo hay chưa trước khi truy cập cơ sở dữ liệu.
	 *
	 * Trả về giá trị không phải null sẽ có hiệu quả ngắt mạch hàm, trả về
	 * giá trị đó thay thế.
	 *
	 * @since 5.1.0
	 *
	 * @param bool|null $pre     Giá trị trả về thay thế. Mặc định null
	 *                           để tiếp tục kiểm tra.
	 * @param int       $site_id ID site đang được kiểm tra.
	 */
	$pre = apply_filters( 'pre_wp_is_site_initialized', null, $site_id );
	if ( null !== $pre ) {
		return (bool) $pre;
	}

	$switch = false;
	if ( get_current_blog_id() !== $site_id ) {
		$switch = true;
		remove_action( 'switch_blog', 'wp_switch_roles_and_user', 1 );
		switch_to_blog( $site_id );
	}

	$suppress = $wpdb->suppress_errors();
	$result   = (bool) $wpdb->get_results( "DESCRIBE {$wpdb->posts}" );
	$wpdb->suppress_errors( $suppress );

	if ( $switch ) {
		restore_current_blog();
		add_action( 'switch_blog', 'wp_switch_roles_and_user', 1, 2 );
	}

	return $result;
}

/**
 * Xóa cache blog
 *
 * @since 3.5.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param WP_Site|int $blog Đối tượng site hoặc ID cần xóa khỏi cache.
 */
function clean_blog_cache( $blog ) {
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	if ( empty( $blog ) ) {
		return;
	}

	$blog_id = $blog;
	$blog    = get_site( $blog_id );
	if ( ! $blog ) {
		if ( ! is_numeric( $blog_id ) ) {
			return;
		}

		// Đảm bảo đối tượng WP_Site tồn tại ngay cả khi site đã bị xóa.
		$blog = new WP_Site(
			(object) array(
				'blog_id' => $blog_id,
				'domain'  => null,
				'path'    => null,
			)
		);
	}

	$blog_id         = $blog->blog_id;
	$domain_path_key = md5( $blog->domain . $blog->path );

	wp_cache_delete( $blog_id, 'sites' );
	wp_cache_delete( $blog_id, 'site-details' );
	wp_cache_delete( $blog_id, 'blog-details' );
	wp_cache_delete( $blog_id . 'short', 'blog-details' );
	wp_cache_delete( $domain_path_key, 'blog-lookup' );
	wp_cache_delete( $domain_path_key, 'blog-id-cache' );
	wp_cache_delete( $blog_id, 'blog_meta' );

	/**
	 * Kích hoạt ngay sau khi một site đã được xóa khỏi cache đối tượng.
	 *
	 * @since 4.6.0
	 *
	 * @param string  $id              ID site dưới dạng chuỗi số.
	 * @param WP_Site $blog            Đối tượng site.
	 * @param string  $domain_path_key Giá trị hash md5 của tên miền và đường dẫn.
	 */
	do_action( 'clean_site_cache', $blog_id, $blog, $domain_path_key );

	wp_cache_set_sites_last_changed();

	/**
	 * Kích hoạt sau khi cache chi tiết blog được xóa.
	 *
	 * @since 3.4.0
	 * @deprecated 4.9.0 Sử dụng {@see 'clean_site_cache'} thay thế.
	 *
	 * @param int $blog_id ID blog.
	 */
	do_action_deprecated( 'refresh_blog_details', array( $blog_id ), '4.9.0', 'clean_site_cache' );
}

/**
 * Thêm metadata cho một site.
 *
 * @since 5.1.0
 *
 * @param int    $site_id    ID site.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Giá trị metadata. Mảng và đối tượng được lưu dưới dạng dữ liệu đã serialize và
 *                           sẽ được trả về cùng kiểu khi lấy ra. Các kiểu dữ liệu khác sẽ
 *                           được lưu dưới dạng chuỗi trong cơ sở dữ liệu:
 *                           - false được lưu và trả về dưới dạng chuỗi rỗng ('')
 *                           - true được lưu và trả về dưới dạng '1'
 *                           - số (cả integer và float) được lưu và trả về dưới dạng chuỗi
 *                           Phải có thể serialize nếu không phải scalar.
 * @param bool   $unique     Tùy chọn. Có nên không thêm cùng khóa hay không.
 *                           Mặc định false.
 * @return int|false ID meta khi thành công, false khi thất bại.
 */
function add_site_meta( $site_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'blog', $site_id, $meta_key, $meta_value, $unique );
}

/**
 * Xóa metadata khớp tiêu chí từ một site.
 *
 * Bạn có thể khớp dựa trên khóa, hoặc khóa và giá trị. Xóa dựa trên khóa và
 * giá trị sẽ tránh xóa metadata trùng lặp có cùng khóa. Nó cũng
 * cho phép xóa tất cả metadata khớp khóa, nếu cần.
 *
 * @since 5.1.0
 *
 * @param int    $site_id    ID site.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Tùy chọn. Giá trị metadata. Nếu được cung cấp,
 *                           chỉ các hàng khớp giá trị mới bị xóa.
 *                           Phải có thể serialize nếu không phải scalar. Mặc định rỗng.
 * @return bool True khi thành công, false khi thất bại.
 */
function delete_site_meta( $site_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'blog', $site_id, $meta_key, $meta_value );
}

/**
 * Lấy metadata cho một site.
 *
 * @since 5.1.0
 *
 * @param int    $site_id ID site.
 * @param string $key     Tùy chọn. Khóa meta cần lấy. Mặc định
 *                        trả về dữ liệu cho tất cả các khóa. Mặc định rỗng.
 * @param bool   $single  Tùy chọn. Có trả về một giá trị duy nhất hay không.
 *                        Tham số này không có tác dụng nếu `$key` không được chỉ định.
 *                        Mặc định false.
 * @return mixed Một mảng giá trị nếu `$single` là false.
 *               Giá trị của trường dữ liệu meta nếu `$single` là true.
 *               False cho `$site_id` không hợp lệ (không phải số, bằng 0, hoặc giá trị âm).
 *               Mảng rỗng nếu ID site hợp lệ nhưng không tồn tại được truyền và `$single` là false.
 *               Chuỗi rỗng nếu ID site hợp lệ nhưng không tồn tại được truyền và `$single` là true.
 *               Lưu ý: Giá trị không serialize được trả về dưới dạng chuỗi:
 *               - Giá trị false được trả về dưới dạng chuỗi rỗng ('')
 *               - Giá trị true được trả về dưới dạng '1'
 *               - Số (cả integer và float) được trả về dưới dạng chuỗi
 *               Mảng và đối tượng giữ nguyên kiểu ban đầu.
 */
function get_site_meta( $site_id, $key = '', $single = false ) {
	return get_metadata( 'blog', $site_id, $key, $single );
}

/**
 * Cập nhật metadata cho một site.
 *
 * Sử dụng tham số $prev_value để phân biệt giữa các trường meta có cùng
 * khóa và ID site.
 *
 * Nếu trường meta cho site không tồn tại, nó sẽ được thêm mới.
 *
 * @since 5.1.0
 *
 * @param int    $site_id    ID site.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Giá trị metadata. Phải có thể serialize nếu không phải scalar.
 * @param mixed  $prev_value Tùy chọn. Giá trị trước đó để kiểm tra trước khi cập nhật.
 *                           Nếu được chỉ định, chỉ cập nhật các mục metadata hiện có với
 *                           giá trị này. Nếu không, cập nhật tất cả các mục. Mặc định rỗng.
 * @return int|bool ID meta nếu khóa không tồn tại, true khi cập nhật thành công,
 *                  false khi thất bại hoặc nếu giá trị được truyền tới hàm
 *                  giống với giá trị đã có trong cơ sở dữ liệu.
 */
function update_site_meta( $site_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'blog', $site_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Xóa tất cả từ metadata site khớp với khóa meta.
 *
 * @since 5.1.0
 *
 * @param string $meta_key Khóa metadata để tìm kiếm khi xóa.
 * @return bool Khóa meta site đã được xóa khỏi cơ sở dữ liệu hay chưa.
 */
function delete_site_meta_by_key( $meta_key ) {
	return delete_metadata( 'blog', null, $meta_key, '', true );
}

/**
 * Cập nhật số lượng site cho một mạng dựa trên site đã thay đổi.
 *
 * @since 5.1.0
 *
 * @param WP_Site      $new_site Đối tượng site đã được chèn, cập nhật hoặc xóa.
 * @param WP_Site|null $old_site Tùy chọn. Nếu $new_site đã được cập nhật, đây phải là trạng thái trước
 *                               của site đó. Mặc định null.
 */
function wp_maybe_update_network_site_counts_on_update( $new_site, $old_site = null ) {
	if ( null === $old_site ) {
		wp_maybe_update_network_site_counts( $new_site->network_id );
		return;
	}

	if ( $new_site->network_id !== $old_site->network_id ) {
		wp_maybe_update_network_site_counts( $new_site->network_id );
		wp_maybe_update_network_site_counts( $old_site->network_id );
	}
}

/**
 * Kích hoạt các action khi trạng thái site được cập nhật.
 *
 * @since 5.1.0
 *
 * @param WP_Site      $new_site Đối tượng site sau khi cập nhật.
 * @param WP_Site|null $old_site Tùy chọn. Nếu $new_site đã được cập nhật, đây phải là trạng thái trước
 *                               của site đó. Mặc định null.
 */
function wp_maybe_transition_site_statuses_on_update( $new_site, $old_site = null ) {
	$site_id = $new_site->id;

	// Sử dụng giá trị mặc định cho site nếu không có trạng thái trước đó.
	if ( ! $old_site ) {
		$old_site = new WP_Site( new stdClass() );
	}

	if ( $new_site->spam !== $old_site->spam ) {
		if ( '1' === $new_site->spam ) {

			/**
			 * Kích hoạt khi trạng thái 'spam' được thêm vào site.
			 *
			 * @since MU (3.0.0)
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'make_spam_blog', $site_id );
		} else {

			/**
			 * Kích hoạt khi trạng thái 'spam' được gỡ bỏ khỏi site.
			 *
			 * @since MU (3.0.0)
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'make_ham_blog', $site_id );
		}
	}

	if ( $new_site->mature !== $old_site->mature ) {
		if ( '1' === $new_site->mature ) {

			/**
			 * Kích hoạt khi trạng thái 'mature' được thêm vào site.
			 *
			 * @since 3.1.0
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'mature_blog', $site_id );
		} else {

			/**
			 * Kích hoạt khi trạng thái 'mature' được gỡ bỏ khỏi site.
			 *
			 * @since 3.1.0
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'unmature_blog', $site_id );
		}
	}

	if ( $new_site->archived !== $old_site->archived ) {
		if ( '1' === $new_site->archived ) {

			/**
			 * Kích hoạt khi trạng thái 'archived' được thêm vào site.
			 *
			 * @since MU (3.0.0)
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'archive_blog', $site_id );
		} else {

			/**
			 * Kích hoạt khi trạng thái 'archived' được gỡ bỏ khỏi site.
			 *
			 * @since MU (3.0.0)
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'unarchive_blog', $site_id );
		}
	}

	if ( $new_site->deleted !== $old_site->deleted ) {
		if ( '1' === $new_site->deleted ) {

			/**
			 * Kích hoạt khi trạng thái 'deleted' được thêm vào site.
			 *
			 * @since 3.5.0
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'make_delete_blog', $site_id );
		} else {

			/**
			 * Kích hoạt khi trạng thái 'deleted' được gỡ bỏ khỏi site.
			 *
			 * @since 3.5.0
			 *
			 * @param int $site_id ID site.
			 */
			do_action( 'make_undelete_blog', $site_id );
		}
	}

	if ( $new_site->public !== $old_site->public ) {

		/**
		 * Kích hoạt sau khi thiết lập 'public' của blog hiện tại được cập nhật.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param int    $site_id   ID site.
		 * @param string $is_public Site có công khai hay không. Chuỗi số,
		 *                          vì lý do tương thích. Chấp nhận '1' hoặc '0'.
		 */
		do_action( 'update_blog_public', $site_id, $new_site->public );
	}
}

/**
 * Xóa các cache cần thiết sau khi dữ liệu site cụ thể đã được cập nhật.
 *
 * @since 5.1.0
 *
 * @param WP_Site $new_site Đối tượng site sau khi cập nhật.
 * @param WP_Site $old_site Đối tượng site trước khi cập nhật.
 */
function wp_maybe_clean_new_site_cache_on_update( $new_site, $old_site ) {
	if ( $old_site->domain !== $new_site->domain || $old_site->path !== $new_site->path ) {
		clean_blog_cache( $new_site );
	}
}

/**
 * Cập nhật tùy chọn `blog_public` cho một ID site đã cho.
 *
 * @since 5.1.0
 *
 * @param int    $site_id   ID site.
 * @param string $is_public Site có công khai hay không. Chuỗi số,
 *                          vì lý do tương thích. Chấp nhận '1' hoặc '0'.
 */
function wp_update_blog_public_option_on_site_update( $site_id, $is_public ) {

	// Thoát sớm nếu các bảng cơ sở dữ liệu của site chưa tồn tại.
	if ( ! wp_is_site_initialized( $site_id ) ) {
		return;
	}

	update_blog_option( $site_id, 'blog_public', $is_public );
}

/**
 * Thiết lập thời gian thay đổi lần cuối cho nhóm cache 'sites'.
 *
 * @since 5.1.0
 */
function wp_cache_set_sites_last_changed() {
	wp_cache_set_last_changed( 'sites' );
}

/**
 * Hủy bỏ các lần gọi tới metadata site nếu nó không được hỗ trợ.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param mixed $check Giá trị bỏ qua cho việc có tiếp tục thực thi hàm metadata site hay không.
 * @return mixed Giá trị gốc của $check, hoặc false nếu metadata site không được hỗ trợ.
 */
function wp_check_site_meta_support_prefilter( $check ) {
	if ( ! is_site_meta_supported() ) {
		/* translators: %s: Tên bảng cơ sở dữ liệu. */
		_doing_it_wrong( __FUNCTION__, sprintf( __( 'The %s table is not installed. Please run the network database upgrade.' ), $GLOBALS['wpdb']->blogmeta ), '5.1.0' );
		return false;
	}

	return $check;
}
