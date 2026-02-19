<?php
/**
 * API Quản trị Bookmark WordPress
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Thêm một liên kết sử dụng các giá trị từ $_POST.
 *
 * @since 2.0.0
 *
 * @return int|WP_Error Giá trị 0 hoặc WP_Error khi thất bại. ID liên kết khi thành công.
 */
function add_link() {
	return edit_link();
}

/**
 * Cập nhật hoặc chèn một liên kết sử dụng các giá trị từ $_POST.
 *
 * @since 2.0.0
 *
 * @param int $link_id Tùy chọn. ID của liên kết cần chỉnh sửa. Mặc định 0.
 * @return int|WP_Error Giá trị 0 hoặc WP_Error khi thất bại. ID liên kết khi thành công.
 */
function edit_link( $link_id = 0 ) {
	if ( ! current_user_can( 'manage_links' ) ) {
		wp_die(
			'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
			'<p>' . __( 'Sorry, you are not allowed to edit the links for this site.' ) . '</p>',
			403
		);
	}

	$_POST['link_url']   = esc_url( $_POST['link_url'] );
	$_POST['link_name']  = esc_html( $_POST['link_name'] );
	$_POST['link_image'] = esc_html( $_POST['link_image'] );
	$_POST['link_rss']   = esc_url( $_POST['link_rss'] );
	if ( ! isset( $_POST['link_visible'] ) || 'N' !== $_POST['link_visible'] ) {
		$_POST['link_visible'] = 'Y';
	}

	if ( ! empty( $link_id ) ) {
		$_POST['link_id'] = $link_id;
		return wp_update_link( $_POST );
	} else {
		return wp_insert_link( $_POST );
	}
}

/**
 * Lấy liên kết mặc định để chỉnh sửa.
 *
 * @since 2.0.0
 *
 * @return stdClass Đối tượng liên kết mặc định.
 */
function get_default_link_to_edit() {
	$link = new stdClass();
	if ( isset( $_GET['linkurl'] ) ) {
		$link->link_url = esc_url( wp_unslash( $_GET['linkurl'] ) );
	} else {
		$link->link_url = '';
	}

	if ( isset( $_GET['name'] ) ) {
		$link->link_name = esc_attr( wp_unslash( $_GET['name'] ) );
	} else {
		$link->link_name = '';
	}

	$link->link_visible = 'Y';

	return $link;
}

/**
 * Xóa một liên kết được chỉ định khỏi cơ sở dữ liệu.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int $link_id ID của liên kết cần xóa.
 * @return true Luôn trả về true.
 */
function wp_delete_link( $link_id ) {
	global $wpdb;
	/**
	 * Kích hoạt trước khi một liên kết bị xóa.
	 *
	 * @since 2.0.0
	 *
	 * @param int $link_id ID của liên kết cần xóa.
	 */
	do_action( 'delete_link', $link_id );

	wp_delete_object_term_relationships( $link_id, 'link_category' );

	$wpdb->delete( $wpdb->links, array( 'link_id' => $link_id ) );

	/**
	 * Kích hoạt sau khi một liên kết đã bị xóa.
	 *
	 * @since 2.2.0
	 *
	 * @param int $link_id ID của liên kết đã xóa.
	 */
	do_action( 'deleted_link', $link_id );

	clean_bookmark_cache( $link_id );

	return true;
}

/**
 * Lấy các ID danh mục liên kết được liên kết với liên kết được chỉ định.
 *
 * @since 2.1.0
 *
 * @param int $link_id ID liên kết cần tra cứu.
 * @return int[] Các ID danh mục của liên kết được yêu cầu.
 */
function wp_get_link_cats( $link_id = 0 ) {
	$cats = wp_get_object_terms( $link_id, 'link_category', array( 'fields' => 'ids' ) );
	return array_unique( $cats );
}

/**
 * Lấy dữ liệu liên kết dựa trên ID của nó.
 *
 * @since 2.0.0
 *
 * @param int|stdClass $link ID liên kết hoặc đối tượng cần lấy.
 * @return object Đối tượng liên kết để chỉnh sửa.
 */
function get_link_to_edit( $link ) {
	return get_bookmark( $link, OBJECT, 'edit' );
}

/**
 * Chèn một liên kết vào cơ sở dữ liệu, hoặc cập nhật liên kết hiện có.
 *
 * Chạy tất cả các bước làm sạch dữ liệu cần thiết, cung cấp giá trị mặc định nếu thiếu tham số,
 * và cuối cùng lưu liên kết.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param array $linkdata {
 *     Các phần tử tạo nên liên kết cần chèn.
 *
 *     @type int    $link_id          Tùy chọn. ID của liên kết hiện có nếu đang cập nhật.
 *     @type string $link_url         URL mà liên kết trỏ đến.
 *     @type string $link_name        Tiêu đề của liên kết.
 *     @type string $link_image       Tùy chọn. URL của một hình ảnh.
 *     @type string $link_target      Tùy chọn. Phần tử target cho thẻ anchor.
 *     @type string $link_description Tùy chọn. Mô tả ngắn của liên kết.
 *     @type string $link_visible     Tùy chọn. 'Y' nghĩa là hiển thị, giá trị khác nghĩa là không.
 *     @type int    $link_owner       Tùy chọn. ID người dùng.
 *     @type int    $link_rating      Tùy chọn. Đánh giá cho liên kết.
 *     @type string $link_rel         Tùy chọn. Mối quan hệ của liên kết với bạn.
 *     @type string $link_notes       Tùy chọn. Mô tả mở rộng hoặc ghi chú về liên kết.
 *     @type string $link_rss         Tùy chọn. URL của nguồn cấp RSS liên quan.
 *     @type int    $link_category    Tùy chọn. ID thuật ngữ của danh mục liên kết.
 *                                    Nếu trống, sử dụng danh mục liên kết mặc định.
 * }
 * @param bool  $wp_error Tùy chọn. Có trả về đối tượng WP_Error khi thất bại không. Mặc định false.
 * @return int|WP_Error Giá trị 0 hoặc WP_Error khi thất bại. ID liên kết khi thành công.
 */
function wp_insert_link( $linkdata, $wp_error = false ) {
	global $wpdb;

	$defaults = array(
		'link_id'     => 0,
		'link_name'   => '',
		'link_url'    => '',
		'link_rating' => 0,
	);

	$parsed_args = wp_parse_args( $linkdata, $defaults );
	$parsed_args = wp_unslash( sanitize_bookmark( $parsed_args, 'db' ) );

	$link_id   = $parsed_args['link_id'];
	$link_name = $parsed_args['link_name'];
	$link_url  = $parsed_args['link_url'];

	$update = false;
	if ( ! empty( $link_id ) ) {
		$update = true;
	}

	if ( '' === trim( $link_name ) ) {
		if ( '' !== trim( $link_url ) ) {
			$link_name = $link_url;
		} else {
			return 0;
		}
	}

	if ( '' === trim( $link_url ) ) {
		return 0;
	}

	$link_rating      = ( ! empty( $parsed_args['link_rating'] ) ) ? $parsed_args['link_rating'] : 0;
	$link_image       = ( ! empty( $parsed_args['link_image'] ) ) ? $parsed_args['link_image'] : '';
	$link_target      = ( ! empty( $parsed_args['link_target'] ) ) ? $parsed_args['link_target'] : '';
	$link_visible     = ( ! empty( $parsed_args['link_visible'] ) ) ? $parsed_args['link_visible'] : 'Y';
	$link_owner       = ( ! empty( $parsed_args['link_owner'] ) ) ? $parsed_args['link_owner'] : get_current_user_id();
	$link_notes       = ( ! empty( $parsed_args['link_notes'] ) ) ? $parsed_args['link_notes'] : '';
	$link_description = ( ! empty( $parsed_args['link_description'] ) ) ? $parsed_args['link_description'] : '';
	$link_rss         = ( ! empty( $parsed_args['link_rss'] ) ) ? $parsed_args['link_rss'] : '';
	$link_rel         = ( ! empty( $parsed_args['link_rel'] ) ) ? $parsed_args['link_rel'] : '';
	$link_category    = ( ! empty( $parsed_args['link_category'] ) ) ? $parsed_args['link_category'] : array();
	$link_updated     = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) );

	// Đảm bảo chúng ta đặt một danh mục hợp lệ.
	if ( ! is_array( $link_category ) || 0 === count( $link_category ) ) {
		$link_category = array( get_option( 'default_link_category' ) );
	}

	if ( $update ) {
		if ( false === $wpdb->update( $wpdb->links, compact( 'link_url', 'link_name', 'link_image', 'link_target', 'link_description', 'link_visible', 'link_owner', 'link_rating', 'link_rel', 'link_notes', 'link_rss', 'link_updated' ), compact( 'link_id' ) ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'db_update_error', __( 'Could not update link in the database.' ), $wpdb->last_error );
			} else {
				return 0;
			}
		}
	} else {
		if ( false === $wpdb->insert( $wpdb->links, compact( 'link_url', 'link_name', 'link_image', 'link_target', 'link_description', 'link_visible', 'link_owner', 'link_rating', 'link_rel', 'link_notes', 'link_rss', 'link_updated' ) ) ) {
			if ( $wp_error ) {
				return new WP_Error( 'db_insert_error', __( 'Could not insert link into the database.' ), $wpdb->last_error );
			} else {
				return 0;
			}
		}
		$link_id = (int) $wpdb->insert_id;
	}

	wp_set_link_cats( $link_id, $link_category );

	if ( $update ) {
		/**
		 * Kích hoạt sau khi một liên kết được cập nhật trong cơ sở dữ liệu.
		 *
		 * @since 2.0.0
		 *
		 * @param int $link_id ID của liên kết đã được cập nhật.
		 */
		do_action( 'edit_link', $link_id );
	} else {
		/**
		 * Kích hoạt sau khi một liên kết được thêm vào cơ sở dữ liệu.
		 *
		 * @since 2.0.0
		 *
		 * @param int $link_id ID của liên kết đã được thêm.
		 */
		do_action( 'add_link', $link_id );
	}
	clean_bookmark_cache( $link_id );

	return $link_id;
}

/**
 * Cập nhật liên kết với các danh mục liên kết được chỉ định.
 *
 * @since 2.1.0
 *
 * @param int   $link_id         ID của liên kết cần cập nhật.
 * @param int[] $link_categories Mảng các ID danh mục liên kết để thêm liên kết vào.
 */
function wp_set_link_cats( $link_id = 0, $link_categories = array() ) {
	// Nếu $link_categories chưa phải là mảng, chuyển nó thành mảng:
	if ( ! is_array( $link_categories ) || 0 === count( $link_categories ) ) {
		$link_categories = array( get_option( 'default_link_category' ) );
	}

	$link_categories = array_map( 'intval', $link_categories );
	$link_categories = array_unique( $link_categories );

	wp_set_object_terms( $link_id, $link_categories, 'link_category' );

	clean_bookmark_cache( $link_id );
}

/**
 * Cập nhật một liên kết trong cơ sở dữ liệu.
 *
 * @since 2.0.0
 *
 * @param array $linkdata Dữ liệu liên kết cần cập nhật. Xem wp_insert_link() để biết các tham số được chấp nhận.
 * @return int|WP_Error Giá trị 0 hoặc WP_Error khi thất bại. ID liên kết đã cập nhật khi thành công.
 */
function wp_update_link( $linkdata ) {
	$link_id = (int) $linkdata['link_id'];

	$link = get_bookmark( $link_id, ARRAY_A );

	// Escape dữ liệu lấy từ DB.
	$link = wp_slash( $link );

	// Danh sách danh mục liên kết được truyền vào sẽ ghi đè danh sách danh mục hiện có nếu không rỗng.
	if ( isset( $linkdata['link_category'] ) && is_array( $linkdata['link_category'] )
		&& count( $linkdata['link_category'] ) > 0
	) {
		$link_cats = $linkdata['link_category'];
	} else {
		$link_cats = $link['link_category'];
	}

	// Hợp nhất các trường cũ và mới, trường mới ghi đè trường cũ.
	$linkdata                  = array_merge( $link, $linkdata );
	$linkdata['link_category'] = $link_cats;

	return wp_insert_link( $linkdata );
}

/**
 * Hiển thị thông báo 'bị vô hiệu hóa' cho Trình quản lý Liên kết WordPress.
 *
 * @since 3.5.0
 * @access private
 *
 * @global string $pagenow Tên tệp của màn hình hiện tại.
 */
function wp_link_manager_disabled_message() {
	global $pagenow;

	if ( ! in_array( $pagenow, array( 'link-manager.php', 'link-add.php', 'link.php' ), true ) ) {
		return;
	}

	add_filter( 'pre_option_link_manager_enabled', '__return_true', 100 );
	$really_can_manage_links = current_user_can( 'manage_links' );
	remove_filter( 'pre_option_link_manager_enabled', '__return_true', 100 );

	if ( $really_can_manage_links ) {
		$plugins = get_plugins();

		if ( empty( $plugins['link-manager/link-manager.php'] ) ) {
			if ( current_user_can( 'install_plugins' ) ) {
				$install_url = wp_nonce_url(
					self_admin_url( 'update.php?action=install-plugin&plugin=link-manager' ),
					'install-plugin_link-manager'
				);

				wp_die(
					sprintf(
						/* translators: %s: A link to install the Link Manager plugin. */
						__( 'If you are looking to use the link manager, please install the <a href="%s">Link Manager plugin</a>.' ),
						esc_url( $install_url )
					)
				);
			}
		} elseif ( is_plugin_inactive( 'link-manager/link-manager.php' ) ) {
			if ( current_user_can( 'activate_plugins' ) ) {
				$activate_url = wp_nonce_url(
					self_admin_url( 'plugins.php?action=activate&plugin=link-manager/link-manager.php' ),
					'activate-plugin_link-manager/link-manager.php'
				);

				wp_die(
					sprintf(
						/* translators: %s: A link to activate the Link Manager plugin. */
						__( 'Please activate the <a href="%s">Link Manager plugin</a> to use the link manager.' ),
						esc_url( $activate_url )
					)
				);
			}
		}
	}

	wp_die( __( 'Sorry, you are not allowed to edit the links for this site.' ) );
}
