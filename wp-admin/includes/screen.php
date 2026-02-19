<?php
/**
 * API Màn hình Quản trị WordPress.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Lấy tiêu đề cột cho một màn hình.
 *
 * @since 2.7.0
 *
 * @param string|WP_Screen $screen Màn hình bạn muốn lấy tiêu đề.
 * @return string[] Nhãn tiêu đề cột được đánh khóa theo ID cột.
 */
function get_column_headers( $screen ) {
	static $column_headers = array();

	if ( is_string( $screen ) ) {
		$screen = convert_to_screen( $screen );
	}

	if ( ! isset( $column_headers[ $screen->id ] ) ) {
		/**
		 * Lọc tiêu đề cột cho bảng danh sách trên một màn hình cụ thể.
		 *
		 * Phần động của tên hook, `$screen->id`, tham chiếu đến
		 * ID của một màn hình cụ thể. Ví dụ, ID màn hình cho bảng
		 * danh sách Bài viết là edit-post, nên bộ lọc cho màn hình đó sẽ là
		 * manage_edit-post_columns.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $columns Nhãn tiêu đề cột được đánh khóa theo ID cột.
		 */
		$column_headers[ $screen->id ] = apply_filters( "manage_{$screen->id}_columns", array() );
	}

	return $column_headers[ $screen->id ];
}

/**
 * Lấy danh sách các cột bị ẩn.
 *
 * @since 2.7.0
 *
 * @param string|WP_Screen $screen Màn hình bạn muốn lấy các cột bị ẩn.
 * @return string[] Mảng ID của các cột bị ẩn.
 */
function get_hidden_columns( $screen ) {
	if ( is_string( $screen ) ) {
		$screen = convert_to_screen( $screen );
	}

	$hidden = get_user_option( 'manage' . $screen->id . 'columnshidden' );

	$use_defaults = ! is_array( $hidden );

	if ( $use_defaults ) {
		$hidden = array();

		/**
		 * Lọc danh sách mặc định các cột bị ẩn.
		 *
		 * @since 4.4.0
		 *
		 * @param string[]  $hidden Mảng ID của các cột bị ẩn mặc định.
		 * @param WP_Screen $screen Đối tượng WP_Screen của màn hình hiện tại.
		 */
		$hidden = apply_filters( 'default_hidden_columns', $hidden, $screen );
	}

	/**
	 * Lọc danh sách các cột bị ẩn.
	 *
	 * @since 4.4.0
	 * @since 4.4.1 Thêm tham số `use_defaults`.
	 *
	 * @param string[]  $hidden       Mảng ID của các cột bị ẩn.
	 * @param WP_Screen $screen       Đối tượng WP_Screen của màn hình hiện tại.
	 * @param bool      $use_defaults Có hiển thị các cột mặc định hay không.
	 */
	return apply_filters( 'hidden_columns', $hidden, $screen, $use_defaults );
}

/**
 * In tùy chọn meta box cho phần meta màn hình.
 *
 * @since 2.7.0
 *
 * @global array $wp_meta_boxes Trạng thái meta box toàn cục.
 *
 * @param WP_Screen $screen
 */
function meta_box_prefs( $screen ) {
	global $wp_meta_boxes;

	if ( is_string( $screen ) ) {
		$screen = convert_to_screen( $screen );
	}

	if ( empty( $wp_meta_boxes[ $screen->id ] ) ) {
		return;
	}

	$hidden = get_hidden_meta_boxes( $screen );

	foreach ( array_keys( $wp_meta_boxes[ $screen->id ] ) as $context ) {
		foreach ( array( 'high', 'core', 'default', 'low' ) as $priority ) {
			if ( ! isset( $wp_meta_boxes[ $screen->id ][ $context ][ $priority ] ) ) {
				continue;
			}

			foreach ( $wp_meta_boxes[ $screen->id ][ $context ][ $priority ] as $box ) {
				if ( false === $box || ! $box['title'] ) {
					continue;
				}

				// Hộp gửi bài không thể bị ẩn.
				if ( 'submitdiv' === $box['id'] || 'linksubmitdiv' === $box['id'] ) {
					continue;
				}

				$widget_title = $box['title'];

				if ( is_array( $box['args'] ) && isset( $box['args']['__widget_basename'] ) ) {
					$widget_title = $box['args']['__widget_basename'];
				}

				$is_hidden = in_array( $box['id'], $hidden, true );

				printf(
					'<label for="%1$s-hide"><input class="hide-postbox-tog" name="%1$s-hide" type="checkbox" id="%1$s-hide" value="%1$s" %2$s />%3$s</label>',
					esc_attr( $box['id'] ),
					checked( $is_hidden, false, false ),
					$widget_title
				);
			}
		}
	}
}

/**
 * Lấy mảng ID của các meta box bị ẩn.
 *
 * @since 2.7.0
 *
 * @param string|WP_Screen $screen Định danh màn hình.
 * @return string[] ID của các meta box bị ẩn.
 */
function get_hidden_meta_boxes( $screen ) {
	if ( is_string( $screen ) ) {
		$screen = convert_to_screen( $screen );
	}

	$hidden = get_user_option( "metaboxhidden_{$screen->id}" );

	$use_defaults = ! is_array( $hidden );

	// Ẩn hộp slug theo mặc định.
	if ( $use_defaults ) {
		$hidden = array();

		if ( 'post' === $screen->base ) {
			if ( in_array( $screen->post_type, array( 'post', 'page', 'attachment' ), true ) ) {
				$hidden = array( 'slugdiv', 'trackbacksdiv', 'postcustom', 'postexcerpt', 'commentstatusdiv', 'commentsdiv', 'authordiv', 'revisionsdiv' );
			} else {
				$hidden = array( 'slugdiv' );
			}
		}

		/**
		 * Lọc danh sách mặc định các meta box bị ẩn.
		 *
		 * @since 3.1.0
		 *
		 * @param string[]  $hidden Mảng ID của các meta box bị ẩn mặc định.
		 * @param WP_Screen $screen Đối tượng WP_Screen của màn hình hiện tại.
		 */
		$hidden = apply_filters( 'default_hidden_meta_boxes', $hidden, $screen );
	}

	/**
	 * Lọc danh sách các meta box bị ẩn.
	 *
	 * @since 3.3.0
	 *
	 * @param string[]  $hidden       Mảng ID của các meta box bị ẩn.
	 * @param WP_Screen $screen       Đối tượng WP_Screen của màn hình hiện tại.
	 * @param bool      $use_defaults Có hiển thị các meta box mặc định hay không.
	 *                                Mặc định true.
	 */
	return apply_filters( 'hidden_meta_boxes', $hidden, $screen, $use_defaults );
}

/**
 * Đăng ký và cấu hình tùy chọn màn hình quản trị.
 *
 * @since 3.1.0
 *
 * @param string $option Tên tùy chọn.
 * @param mixed  $args   Các đối số phụ thuộc vào tùy chọn.
 */
function add_screen_option( $option, $args = array() ) {
	$current_screen = get_current_screen();

	if ( ! $current_screen ) {
		return;
	}

	$current_screen->add_option( $option, $args );
}

/**
 * Lấy đối tượng màn hình hiện tại.
 *
 * @since 3.1.0
 *
 * @global WP_Screen $current_screen Đối tượng màn hình hiện tại của WordPress.
 *
 * @return WP_Screen|null Đối tượng màn hình hiện tại hoặc null khi màn hình chưa được định nghĩa.
 */
function get_current_screen() {
	global $current_screen;

	if ( ! isset( $current_screen ) ) {
		return null;
	}

	return $current_screen;
}

/**
 * Đặt đối tượng màn hình hiện tại.
 *
 * @since 3.0.0
 *
 * @param string|WP_Screen $hook_name Tùy chọn. Tên hook (còn gọi là hậu tố hook) dùng để xác định màn hình,
 *                                    hoặc một đối tượng màn hình đã tồn tại.
 */
function set_current_screen( $hook_name = '' ) {
	WP_Screen::get( $hook_name )->set_current_screen();
}
