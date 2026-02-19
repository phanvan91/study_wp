<?php
/**
 * API Widget lõi
 *
 * API này được sử dụng để tạo sidebar động mà không cần viết cứng chức năng
 * vào theme.
 *
 * Bao gồm cả các hàm nội bộ của WordPress và các hàm dùng trong theme.
 *
 * Chức năng này được tìm thấy trong một plugin trước bản phát hành WordPress 2.2,
 * sau đó được đưa vào lõi từ phiên bản đó trở đi.
 *
 * @link https://wordpress.org/documentation/article/manage-wordpress-widgets/
 * @link https://developer.wordpress.org/themes/functionality/widgets/
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 2.2.0
 */

//
// Biến toàn cục.
//

/** @ignore */
global $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

/**
 * Lưu trữ các sidebar, vì nhiều theme có thể có nhiều hơn một.
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 */
$wp_registered_sidebars = array();

/**
 * Lưu trữ các widget đã đăng ký.
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_widgets Các widget đã đăng ký.
 */
$wp_registered_widgets = array();

/**
 * Lưu trữ các điều khiển widget đã đăng ký (tùy chọn).
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_widget_controls Các điều khiển widget đã đăng ký.
 */
$wp_registered_widget_controls = array();

/**
 * Lưu trữ các cập nhật widget đã đăng ký.
 *
 * @since 2.8.0
 *
 * @global array $wp_registered_widget_updates Các cập nhật widget đã đăng ký.
 */
$wp_registered_widget_updates = array();

/**
 * Riêng tư
 *
 * @global array $_wp_sidebars_widgets
 */
$_wp_sidebars_widgets = array();

/**
 * Riêng tư
 *
 * @global array $_wp_deprecated_widgets_callbacks
 */
$GLOBALS['_wp_deprecated_widgets_callbacks'] = array(
	'wp_widget_pages',
	'wp_widget_pages_control',
	'wp_widget_calendar',
	'wp_widget_calendar_control',
	'wp_widget_archives',
	'wp_widget_archives_control',
	'wp_widget_links',
	'wp_widget_meta',
	'wp_widget_meta_control',
	'wp_widget_search',
	'wp_widget_recent_entries',
	'wp_widget_recent_entries_control',
	'wp_widget_tag_cloud',
	'wp_widget_tag_cloud_control',
	'wp_widget_categories',
	'wp_widget_categories_control',
	'wp_widget_text',
	'wp_widget_text_control',
	'wp_widget_rss',
	'wp_widget_rss_control',
	'wp_widget_recent_comments',
	'wp_widget_recent_comments_control',
);

//
// Thẻ mẫu & các hàm API.
//

/**
 * Đăng ký một widget.
 *
 * Đăng ký một widget WP_Widget
 *
 * @since 2.8.0
 * @since 4.6.0 Cập nhật tham số `$widget` để cũng chấp nhận đối tượng thể hiện WP_Widget
 *              thay vì chỉ tên lớp con `WP_Widget`.
 *
 * @see WP_Widget
 *
 * @global WP_Widget_Factory $wp_widget_factory
 *
 * @param string|WP_Widget $widget Tên lớp con `WP_Widget` hoặc thể hiện của lớp con `WP_Widget`.
 */
function register_widget( $widget ) {
	global $wp_widget_factory;

	$wp_widget_factory->register( $widget );
}

/**
 * Hủy đăng ký một widget.
 *
 * Hủy đăng ký một widget WP_Widget. Hữu ích để hủy đăng ký các widget mặc định.
 * Chạy trong một hàm được gắn vào action {@see 'widgets_init'}.
 *
 * @since 2.8.0
 * @since 4.6.0 Cập nhật tham số `$widget` để cũng chấp nhận đối tượng thể hiện WP_Widget
 *              thay vì chỉ tên lớp con `WP_Widget`.
 *
 * @see WP_Widget
 *
 * @global WP_Widget_Factory $wp_widget_factory
 *
 * @param string|WP_Widget $widget Tên lớp con `WP_Widget` hoặc thể hiện của lớp con `WP_Widget`.
 */
function unregister_widget( $widget ) {
	global $wp_widget_factory;

	$wp_widget_factory->unregister( $widget );
}

/**
 * Tạo nhiều sidebar.
 *
 * Nếu bạn muốn tạo nhanh nhiều sidebar cho theme hoặc nội bộ.
 * Hàm này cho phép bạn làm điều đó. Nếu bạn không truyền 'name' và/hoặc
 * 'id' trong `$args`, chúng sẽ được tự động tạo cho bạn.
 *
 * @since 2.2.0
 *
 * @see register_sidebar() Tham số thứ hai được mô tả bởi register_sidebar() và giống nhau ở đây.
 *
 * @global array $wp_registered_sidebars Các sidebar mới được lưu trong mảng này theo ID sidebar.
 *
 * @param int          $number Tùy chọn. Số lượng sidebar cần tạo. Mặc định 1.
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi tham số để xây dựng sidebar.
 *
 *     @type string $id   Chuỗi cơ sở của định danh duy nhất cho mỗi sidebar. Nếu được cung cấp, và nhiều
 *                        sidebar đang được định nghĩa, ID sẽ được thêm hậu tố "-2", v.v.
 *                        Mặc định 'sidebar-' theo sau bởi số thứ tự sidebar hiện tại.
 *     @type string $name Tên hoặc tiêu đề cho các sidebar hiển thị trong bảng quản trị. Nếu đăng ký
 *                        nhiều hơn một sidebar, hãy bao gồm '%d' trong chuỗi làm chỗ giữ cho số
 *                        được gán duy nhất cho mỗi sidebar.
 *                        Mặc định 'Sidebar' cho sidebar đầu tiên, nếu không là 'Sidebar %d'.
 * }
 */
function register_sidebars( $number = 1, $args = array() ) {
	global $wp_registered_sidebars;
	$number = (int) $number;

	if ( is_string( $args ) ) {
		parse_str( $args, $args );
	}

	for ( $i = 1; $i <= $number; $i++ ) {
		$_args = $args;

		if ( $number > 1 ) {
			if ( isset( $args['name'] ) ) {
				$_args['name'] = sprintf( $args['name'], $i );
			} else {
				/* translators: %d: Sidebar number. */
				$_args['name'] = sprintf( __( 'Sidebar %d' ), $i );
			}
		} else {
			$_args['name'] = isset( $args['name'] ) ? $args['name'] : __( 'Sidebar' );
		}

		/*
		 * Các ID được chỉ định tùy chỉnh sẽ được thêm hậu tố nếu đã tồn tại.
		 * Tên sidebar được tạo tự động cần được thêm hậu tố bắt đầu từ -0.
		 */
		if ( isset( $args['id'] ) ) {
			$_args['id'] = $args['id'];
			$n           = 2; // Bắt đầu từ -2 cho các ID tùy chỉnh bị trùng.
			while ( is_registered_sidebar( $_args['id'] ) ) {
				$_args['id'] = $args['id'] . '-' . $n++;
			}
		} else {
			$n = count( $wp_registered_sidebars );
			do {
				$_args['id'] = 'sidebar-' . ++$n;
			} while ( is_registered_sidebar( $_args['id'] ) );
		}
		register_sidebar( $_args );
	}
}

/**
 * Xây dựng định nghĩa cho một sidebar đơn và trả về ID.
 *
 * Chấp nhận chuỗi hoặc mảng rồi phân tích so với một tập hợp
 * tham số mặc định cho sidebar mới. WordPress sẽ tự động
 * tạo ID và tên sidebar dựa trên số lượng sidebar đã đăng ký hiện tại
 * nếu những tham số đó không được bao gồm.
 *
 * Khi cho phép tự động tạo tham số name và ID, hãy lưu ý
 * rằng bộ đếm tăng dần cho sidebar của bạn có thể thay đổi theo thời gian tùy thuộc
 * vào các plugin và theme khác được cài đặt.
 *
 * Nếu hỗ trợ theme cho 'widgets' chưa được thêm khi hàm này được
 * gọi, nó sẽ được tự động kích hoạt thông qua add_theme_support().
 *
 * @since 2.2.0
 * @since 5.6.0 Thêm tham số `before_sidebar` và `after_sidebar`.
 * @since 5.9.0 Thêm tham số `show_in_rest`.
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi tham số cho sidebar đang được đăng ký.
 *
 *     @type string $name           Tên hoặc tiêu đề của sidebar hiển thị trong giao diện Widget.
 *                                  Mặc định 'Sidebar $instance'.
 *     @type string $id             Định danh duy nhất mà sidebar sẽ được gọi.
 *                                  Mặc định 'sidebar-$instance'.
 *     @type string $description    Mô tả sidebar, hiển thị trong giao diện Widget.
 *                                  Mặc định chuỗi rỗng.
 *     @type string $class          Lớp CSS bổ sung gán cho sidebar trong giao diện Widget.
 *                                  Mặc định rỗng.
 *     @type string $before_widget  Nội dung HTML thêm trước đầu ra HTML của mỗi widget khi được gán
 *                                  cho sidebar này. Nhận thuộc tính ID của widget là `%1$s`
 *                                  và tên lớp là `%2$s`. Mặc định là phần tử mục danh sách mở.
 *     @type string $after_widget   Nội dung HTML thêm sau đầu ra HTML của mỗi widget khi được gán
 *                                  cho sidebar này. Mặc định là phần tử mục danh sách đóng.
 *     @type string $before_title   Nội dung HTML thêm trước tiêu đề sidebar khi hiển thị.
 *                                  Mặc định là phần tử h2 mở.
 *     @type string $after_title    Nội dung HTML thêm sau tiêu đề sidebar khi hiển thị.
 *                                  Mặc định là phần tử h2 đóng.
 *     @type string $before_sidebar Nội dung HTML thêm trước sidebar khi hiển thị.
 *                                  Nhận tham số `$id` là `%1$s` và `$class` là `%2$s`.
 *                                  Xuất sau action {@see 'dynamic_sidebar_before'}.
 *                                  Mặc định chuỗi rỗng.
 *     @type string $after_sidebar  Nội dung HTML thêm sau sidebar khi hiển thị.
 *                                  Xuất trước action {@see 'dynamic_sidebar_after'}.
 *                                  Mặc định chuỗi rỗng.
 *     @type bool $show_in_rest     Có hiển thị sidebar này công khai trong REST API hay không.
 *                                  Mặc định chỉ hiển thị sidebar cho người dùng quản trị viên.
 * }
 * @return string ID sidebar được thêm vào biến toàn cục $wp_registered_sidebars.
 */
function register_sidebar( $args = array() ) {
	global $wp_registered_sidebars;

	$i = count( $wp_registered_sidebars ) + 1;

	$id_is_empty = empty( $args['id'] );

	$defaults = array(
		/* translators: %d: Sidebar number. */
		'name'           => sprintf( __( 'Sidebar %d' ), $i ),
		'id'             => "sidebar-$i",
		'description'    => '',
		'class'          => '',
		'before_widget'  => '<li id="%1$s" class="widget %2$s">',
		'after_widget'   => "</li>\n",
		'before_title'   => '<h2 class="widgettitle">',
		'after_title'    => "</h2>\n",
		'before_sidebar' => '',
		'after_sidebar'  => '',
		'show_in_rest'   => false,
	);

	/**
	 * Lọc các tham số mặc định của sidebar.
	 *
	 * @since 5.3.0
	 *
	 * @see register_sidebar()
	 *
	 * @param array $defaults Các tham số sidebar mặc định.
	 */
	$sidebar = wp_parse_args( $args, apply_filters( 'register_sidebar_defaults', $defaults ) );

	if ( $id_is_empty ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: The 'id' argument, 2: Sidebar name, 3: Recommended 'id' value. */
				__( 'No %1$s was set in the arguments array for the "%2$s" sidebar. Defaulting to "%3$s". Manually set the %1$s to "%3$s" to silence this notice and keep existing sidebar content.' ),
				'<code>id</code>',
				$sidebar['name'],
				$sidebar['id']
			),
			'4.2.0'
		);
	}

	$wp_registered_sidebars[ $sidebar['id'] ] = $sidebar;

	add_theme_support( 'widgets' );

	/**
	 * Kích hoạt sau khi một sidebar đã được đăng ký.
	 *
	 * @since 3.0.0
	 *
	 * @param array $sidebar Tham số đã phân tích cho sidebar đã đăng ký.
	 */
	do_action( 'register_sidebar', $sidebar );

	return $sidebar['id'];
}

/**
 * Xóa một sidebar khỏi danh sách.
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param string|int $sidebar_id ID của sidebar khi nó được đăng ký.
 */
function unregister_sidebar( $sidebar_id ) {
	global $wp_registered_sidebars;

	unset( $wp_registered_sidebars[ $sidebar_id ] );
}

/**
 * Kiểm tra xem một sidebar đã được đăng ký chưa.
 *
 * @since 4.4.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param string|int $sidebar_id ID của sidebar khi nó được đăng ký.
 * @return bool True nếu sidebar đã được đăng ký, false nếu không.
 */
function is_registered_sidebar( $sidebar_id ) {
	global $wp_registered_sidebars;

	return isset( $wp_registered_sidebars[ $sidebar_id ] );
}

/**
 * Đăng ký một thể hiện của widget.
 *
 * Tùy chọn widget mặc định là 'classname' có thể được ghi đè.
 *
 * Hàm cũng có thể được sử dụng để hủy đăng ký widget khi tham số `$output_callback`
 * là chuỗi rỗng.
 *
 * @since 2.2.0
 * @since 5.3.0 Chính thức hóa tham số `...$params` đã tồn tại và được ghi nhận
 *              bằng cách thêm vào chữ ký hàm.
 * @since 5.8.0 Thêm tùy chọn show_instance_in_rest.
 *
 * @global array $wp_registered_widgets            Sử dụng các widget đã đăng ký được lưu trữ.
 * @global array $wp_registered_widget_controls    Lưu trữ các điều khiển widget đã đăng ký (tùy chọn).
 * @global array $wp_registered_widget_updates     Các cập nhật widget đã đăng ký.
 * @global array $_wp_deprecated_widgets_callbacks
 *
 * @param int|string $id              ID Widget.
 * @param string     $name            Tiêu đề hiển thị widget.
 * @param callable   $output_callback Chạy khi widget được gọi.
 * @param array      $options {
 *     Tùy chọn. Mảng tùy chọn widget bổ sung cho thể hiện.
 *
 *     @type string $classname             Tên lớp cho vùng chứa HTML của widget. Mặc định là phiên bản
 *                                         rút gọn của tên callback đầu ra.
 *     @type string $description           Mô tả widget để hiển thị trong bảng quản trị widget
 *                                         và/hoặc theme.
 *     @type bool   $show_instance_in_rest Có hiển thị cài đặt thể hiện widget trong REST API hay không.
 *                                         Chỉ khả dụng cho các widget dựa trên WP_Widget.
 * }
 * @param mixed      ...$params       Tùy chọn các tham số bổ sung để truyền cho hàm callback khi nó được gọi.
 */
function wp_register_sidebar_widget( $id, $name, $output_callback, $options = array(), ...$params ) {
	global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates, $_wp_deprecated_widgets_callbacks;

	$id = strtolower( $id );

	if ( empty( $output_callback ) ) {
		unset( $wp_registered_widgets[ $id ] );
		return;
	}

	$id_base = _get_widget_id_base( $id );
	if ( in_array( $output_callback, $_wp_deprecated_widgets_callbacks, true ) && ! is_callable( $output_callback ) ) {
		unset( $wp_registered_widget_controls[ $id ] );
		unset( $wp_registered_widget_updates[ $id_base ] );
		return;
	}

	$defaults = array( 'classname' => $output_callback );
	$options  = wp_parse_args( $options, $defaults );
	$widget   = array(
		'name'     => $name,
		'id'       => $id,
		'callback' => $output_callback,
		'params'   => $params,
	);
	$widget   = array_merge( $widget, $options );

	if ( is_callable( $output_callback ) && ( ! isset( $wp_registered_widgets[ $id ] ) || did_action( 'widgets_init' ) ) ) {

		/**
		 * Kích hoạt một lần cho mỗi widget đã đăng ký.
		 *
		 * @since 3.0.0
		 *
		 * @param array $widget Mảng tham số widget mặc định.
		 */
		do_action( 'wp_register_sidebar_widget', $widget );
		$wp_registered_widgets[ $id ] = $widget;
	}
}

/**
 * Lấy mô tả cho widget.
 *
 * Khi đăng ký widget, các tùy chọn cũng có thể bao gồm 'description'
 * mô tả widget để hiển thị trên bảng quản trị widget hoặc
 * trong theme.
 *
 * @since 2.5.0
 *
 * @global array $wp_registered_widgets Các widget đã đăng ký.
 *
 * @param int|string $id ID Widget.
 * @return string|void Mô tả widget, nếu có.
 */
function wp_widget_description( $id ) {
	if ( ! is_scalar( $id ) ) {
		return;
	}

	global $wp_registered_widgets;

	if ( isset( $wp_registered_widgets[ $id ]['description'] ) ) {
		return esc_html( $wp_registered_widgets[ $id ]['description'] );
	}
}

/**
 * Lấy mô tả cho sidebar.
 *
 * Khi đăng ký sidebar, tham số 'description' có thể được bao gồm
 * để mô tả sidebar hiển thị trên bảng quản trị widget.
 *
 * @since 2.9.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param string $id ID sidebar.
 * @return string|void Mô tả sidebar, nếu có.
 */
function wp_sidebar_description( $id ) {
	if ( ! is_scalar( $id ) ) {
		return;
	}

	global $wp_registered_sidebars;

	if ( isset( $wp_registered_sidebars[ $id ]['description'] ) ) {
		return wp_kses( $wp_registered_sidebars[ $id ]['description'], 'sidebar_description' );
	}
}

/**
 * Xóa widget khỏi sidebar.
 *
 * @since 2.2.0
 *
 * @param int|string $id ID Widget.
 */
function wp_unregister_sidebar_widget( $id ) {

	/**
	 * Kích hoạt ngay trước khi widget bị xóa khỏi sidebar.
	 *
	 * @since 3.0.0
	 *
	 * @param int|string $id ID Widget.
	 */
	do_action( 'wp_unregister_sidebar_widget', $id );

	wp_register_sidebar_widget( $id, '', '' );
	wp_unregister_widget_control( $id );
}

/**
 * Đăng ký callback điều khiển widget để tùy chỉnh tùy chọn.
 *
 * @since 2.2.0
 * @since 5.3.0 Chính thức hóa tham số `...$params` đã tồn tại và được ghi nhận
 *              bằng cách thêm vào chữ ký hàm.
 *
 * @global array $wp_registered_widget_controls Các điều khiển widget đã đăng ký.
 * @global array $wp_registered_widget_updates  Các cập nhật widget đã đăng ký.
 * @global array $wp_registered_widgets         Các widget đã đăng ký.
 * @global array $_wp_deprecated_widgets_callbacks
 *
 * @param int|string $id               ID Sidebar.
 * @param string     $name             Tên hiển thị Sidebar.
 * @param callable   $control_callback Chạy khi sidebar được hiển thị.
 * @param array      $options {
 *     Tùy chọn. Mảng hoặc chuỗi tùy chọn điều khiển. Mặc định mảng rỗng.
 *
 *     @type int        $height  Không bao giờ được sử dụng. Mặc định 200.
 *     @type int        $width   Chiều rộng của form điều khiển khi mở rộng hoàn toàn (nhưng hãy cố gắng sử dụng chiều rộng mặc định).
 *                               Mặc định 250.
 *     @type int|string $id_base Bắt buộc cho multi-widget, tức là các widget cho phép nhiều thể hiện như
 *                               widget văn bản. ID widget sẽ có dạng `{$id_base}-{$unique_number}`.
 * }
 * @param mixed      ...$params        Tùy chọn các tham số bổ sung để truyền cho hàm callback khi nó được gọi.
 */
function wp_register_widget_control( $id, $name, $control_callback, $options = array(), ...$params ) {
	global $wp_registered_widget_controls, $wp_registered_widget_updates, $wp_registered_widgets, $_wp_deprecated_widgets_callbacks;

	$id      = strtolower( $id );
	$id_base = _get_widget_id_base( $id );

	if ( empty( $control_callback ) ) {
		unset( $wp_registered_widget_controls[ $id ] );
		unset( $wp_registered_widget_updates[ $id_base ] );
		return;
	}

	if ( in_array( $control_callback, $_wp_deprecated_widgets_callbacks, true ) && ! is_callable( $control_callback ) ) {
		unset( $wp_registered_widgets[ $id ] );
		return;
	}

	if ( isset( $wp_registered_widget_controls[ $id ] ) && ! did_action( 'widgets_init' ) ) {
		return;
	}

	$defaults          = array(
		'width'  => 250,
		'height' => 200,
	); // Chiều cao không bao giờ được sử dụng.
	$options           = wp_parse_args( $options, $defaults );
	$options['width']  = (int) $options['width'];
	$options['height'] = (int) $options['height'];

	$widget = array(
		'name'     => $name,
		'id'       => $id,
		'callback' => $control_callback,
		'params'   => $params,
	);
	$widget = array_merge( $widget, $options );

	$wp_registered_widget_controls[ $id ] = $widget;

	if ( isset( $wp_registered_widget_updates[ $id_base ] ) ) {
		return;
	}

	if ( isset( $widget['params'][0]['number'] ) ) {
		$widget['params'][0]['number'] = -1;
	}

	unset( $widget['width'], $widget['height'], $widget['name'], $widget['id'] );
	$wp_registered_widget_updates[ $id_base ] = $widget;
}

/**
 * Đăng ký callback cập nhật cho widget.
 *
 * @since 2.8.0
 * @since 5.3.0 Chính thức hóa tham số `...$params` đã tồn tại và được ghi nhận
 *              bằng cách thêm vào chữ ký hàm.
 *
 * @global array $wp_registered_widget_updates Các cập nhật widget đã đăng ký.
 *
 * @param string   $id_base         ID cơ sở của widget được tạo bằng cách kế thừa WP_Widget.
 * @param callable $update_callback Phương thức callback cập nhật cho widget.
 * @param array    $options         Tùy chọn. Tùy chọn điều khiển widget. Xem wp_register_widget_control().
 *                                  Mặc định mảng rỗng.
 * @param mixed    ...$params       Tùy chọn các tham số bổ sung để truyền cho hàm callback khi nó được gọi.
 */
function _register_widget_update_callback( $id_base, $update_callback, $options = array(), ...$params ) {
	global $wp_registered_widget_updates;

	if ( isset( $wp_registered_widget_updates[ $id_base ] ) ) {
		if ( empty( $update_callback ) ) {
			unset( $wp_registered_widget_updates[ $id_base ] );
		}
		return;
	}

	$widget = array(
		'callback' => $update_callback,
		'params'   => $params,
	);

	$widget                                   = array_merge( $widget, $options );
	$wp_registered_widget_updates[ $id_base ] = $widget;
}

/**
 * Đăng ký callback form cho widget.
 *
 * @since 2.8.0
 * @since 5.3.0 Chính thức hóa tham số `...$params` đã tồn tại và được ghi nhận
 *              bằng cách thêm vào chữ ký hàm.
 *
 * @global array $wp_registered_widget_controls Các điều khiển widget đã đăng ký.
 *
 * @param int|string $id            ID Widget.
 * @param string     $name          Thuộc tính tên cho widget.
 * @param callable   $form_callback Callback form.
 * @param array      $options       Tùy chọn. Tùy chọn điều khiển widget. Xem wp_register_widget_control().
 *                                  Mặc định mảng rỗng.
 * @param mixed      ...$params     Tùy chọn các tham số bổ sung để truyền cho hàm callback khi nó được gọi.
 */

function _register_widget_form_callback( $id, $name, $form_callback, $options = array(), ...$params ) {
	global $wp_registered_widget_controls;

	$id = strtolower( $id );

	if ( empty( $form_callback ) ) {
		unset( $wp_registered_widget_controls[ $id ] );
		return;
	}

	if ( isset( $wp_registered_widget_controls[ $id ] ) && ! did_action( 'widgets_init' ) ) {
		return;
	}

	$defaults          = array(
		'width'  => 250,
		'height' => 200,
	);
	$options           = wp_parse_args( $options, $defaults );
	$options['width']  = (int) $options['width'];
	$options['height'] = (int) $options['height'];

	$widget = array(
		'name'     => $name,
		'id'       => $id,
		'callback' => $form_callback,
		'params'   => $params,
	);
	$widget = array_merge( $widget, $options );

	$wp_registered_widget_controls[ $id ] = $widget;
}

/**
 * Xóa callback điều khiển cho widget.
 *
 * @since 2.2.0
 *
 * @param int|string $id ID Widget.
 */
function wp_unregister_widget_control( $id ) {
	wp_register_widget_control( $id, '', '' );
}

/**
 * Hiển thị sidebar động.
 *
 * Theo mặc định, hiển thị sidebar mặc định hoặc 'sidebar-1'. Nếu theme của bạn chỉ định tham số 'id' hoặc
 * 'name' cho các sidebar đã đăng ký, bạn có thể truyền ID hoặc tên làm tham số $index.
 * Nếu không, bạn có thể truyền chỉ số dạng số để hiển thị sidebar tại chỉ số đó.
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 * @global array $wp_registered_widgets  Các widget đã đăng ký.
 *
 * @param int|string $index Tùy chọn. Chỉ số, tên hoặc ID của sidebar động. Mặc định 1.
 * @return bool True, nếu sidebar widget được tìm thấy và gọi. False nếu không tìm thấy hoặc không được gọi.
 */
function dynamic_sidebar( $index = 1 ) {
	global $wp_registered_sidebars, $wp_registered_widgets;

	if ( is_int( $index ) ) {
		$index = "sidebar-$index";
	} else {
		$index = sanitize_title( $index );
		foreach ( (array) $wp_registered_sidebars as $key => $value ) {
			if ( sanitize_title( $value['name'] ) === $index ) {
				$index = $key;
				break;
			}
		}
	}

	$sidebars_widgets = wp_get_sidebars_widgets();
	if ( empty( $wp_registered_sidebars[ $index ] ) || empty( $sidebars_widgets[ $index ] ) || ! is_array( $sidebars_widgets[ $index ] ) ) {
		/** Action này được ghi nhận trong wp-includes/widget.php */
		do_action( 'dynamic_sidebar_before', $index, false );
		/** Action này được ghi nhận trong wp-includes/widget.php */
		do_action( 'dynamic_sidebar_after', $index, false );
		/** Bộ lọc này được ghi nhận trong wp-includes/widget.php */
		return apply_filters( 'dynamic_sidebar_has_widgets', false, $index );
	}

	$sidebar = $wp_registered_sidebars[ $index ];

	$sidebar['before_sidebar'] = sprintf( $sidebar['before_sidebar'], $sidebar['id'], $sidebar['class'] );

	/**
	 * Kích hoạt trước khi các widget được hiển thị trong sidebar động.
	 *
	 * Lưu ý: Action cũng kích hoạt cho các sidebar rỗng, và trên cả giao diện
	 * trước và sau, bao gồm sidebar Widget không hoạt động trên màn hình Widget.
	 *
	 * @since 3.9.0
	 *
	 * @param int|string $index       Chỉ số, tên, hoặc ID của sidebar động.
	 * @param bool       $has_widgets Sidebar có được điền widget hay không.
	 *                                Mặc định true.
	 */
	do_action( 'dynamic_sidebar_before', $index, true );

	if ( ! is_admin() && ! empty( $sidebar['before_sidebar'] ) ) {
		echo $sidebar['before_sidebar'];
	}

	$did_one = false;
	foreach ( (array) $sidebars_widgets[ $index ] as $id ) {

		if ( ! isset( $wp_registered_widgets[ $id ] ) ) {
			continue;
		}

		$params = array_merge(
			array(
				array_merge(
					$sidebar,
					array(
						'widget_id'   => $id,
						'widget_name' => $wp_registered_widgets[ $id ]['name'],
					)
				),
			),
			(array) $wp_registered_widgets[ $id ]['params']
		);

		// Thay thế thuộc tính HTML `id` và `class` vào `before_widget`.
		$classname_ = '';
		foreach ( (array) $wp_registered_widgets[ $id ]['classname'] as $cn ) {
			if ( is_string( $cn ) ) {
				$classname_ .= '_' . $cn;
			} elseif ( is_object( $cn ) ) {
				$classname_ .= '_' . get_class( $cn );
			}
		}
		$classname_ = ltrim( $classname_, '_' );

		$params[0]['before_widget'] = sprintf(
			$params[0]['before_widget'],
			str_replace( '\\', '_', $id ),
			$classname_
		);

		/**
		 * Lọc các tham số được truyền cho callback hiển thị của widget.
		 *
		 * Lưu ý: Bộ lọc được đánh giá trên cả giao diện trước và sau,
		 * bao gồm sidebar Widget không hoạt động trên màn hình Widget.
		 *
		 * @since 2.5.0
		 *
		 * @see register_sidebar()
		 *
		 * @param array $params {
		 *     @type array $args  {
		 *         Mảng tham số hiển thị widget.
		 *
		 *         @type string $name          Tên của sidebar mà widget được gán.
		 *         @type string $id            ID của sidebar mà widget được gán.
		 *         @type string $description   Mô tả sidebar.
		 *         @type string $class         Lớp CSS áp dụng cho vùng chứa sidebar.
		 *         @type string $before_widget Đánh dấu HTML thêm trước mỗi widget trong sidebar.
		 *         @type string $after_widget  Đánh dấu HTML thêm sau mỗi widget trong sidebar.
		 *         @type string $before_title  Đánh dấu HTML thêm trước tiêu đề widget khi hiển thị.
		 *         @type string $after_title   Đánh dấu HTML thêm sau tiêu đề widget khi hiển thị.
		 *         @type string $widget_id     ID của widget.
		 *         @type string $widget_name   Tên của widget.
		 *     }
		 *     @type array $widget_args {
		 *         Mảng tham số multi-widget.
		 *
		 *         @type int $number Số tăng dần được sử dụng cho nhiều widget cùng loại.
		 *     }
		 * }
		 */
		$params = apply_filters( 'dynamic_sidebar_params', $params );

		$callback = $wp_registered_widgets[ $id ]['callback'];

		/**
		 * Kích hoạt trước khi callback hiển thị của widget được gọi.
		 *
		 * Lưu ý: Action kích hoạt trên cả giao diện trước và sau, bao gồm
		 * các widget trong sidebar Widget không hoạt động trên màn hình Widget.
		 *
		 * Action không kích hoạt cho các sidebar rỗng.
		 *
		 * @since 3.0.0
		 *
		 * @param array $widget {
		 *     Mảng liên kết các tham số widget.
		 *
		 *     @type string   $name        Tên của widget.
		 *     @type string   $id          ID Widget.
		 *     @type callable $callback    Khi hook được kích hoạt ở giao diện trước, `$callback` là mảng
		 *                                 chứa đối tượng widget. Kích hoạt ở giao diện sau, `$callback`
		 *                                 là 'wp_widget_control', xem `$_callback`.
		 *     @type array    $params      Mảng liên kết các tham số multi-widget.
		 *     @type string   $classname   Lớp CSS áp dụng cho vùng chứa widget.
		 *     @type string   $description Mô tả widget.
		 *     @type array    $_callback   Khi hook được kích hoạt ở giao diện sau, `$_callback` được điền
		 *                                 bằng mảng chứa đối tượng widget, xem `$callback`.
		 * }
		 */
		do_action( 'dynamic_sidebar', $wp_registered_widgets[ $id ] );

		if ( is_callable( $callback ) ) {
			call_user_func_array( $callback, $params );
			$did_one = true;
		}
	}

	if ( ! is_admin() && ! empty( $sidebar['after_sidebar'] ) ) {
		echo $sidebar['after_sidebar'];
	}

	/**
	 * Kích hoạt sau khi các widget được hiển thị trong sidebar động.
	 *
	 * Lưu ý: Action cũng kích hoạt cho các sidebar rỗng, và trên cả giao diện
	 * trước và sau, bao gồm sidebar Widget không hoạt động trên màn hình Widget.
	 *
	 * @since 3.9.0
	 *
	 * @param int|string $index       Chỉ số, tên, hoặc ID của sidebar động.
	 * @param bool       $has_widgets Sidebar có được điền widget hay không.
	 *                                Mặc định true.
	 */
	do_action( 'dynamic_sidebar_after', $index, true );

	/**
	 * Lọc xem sidebar có widget hay không.
	 *
	 * Lưu ý: Bộ lọc cũng được đánh giá cho các sidebar rỗng, và trên cả giao diện
	 * trước và sau, bao gồm sidebar Widget không hoạt động trên màn hình Widget.
	 *
	 * @since 3.9.0
	 *
	 * @param bool       $did_one Có ít nhất một widget được hiển thị trong sidebar hay không.
	 *                            Mặc định false.
	 * @param int|string $index   Chỉ số, tên, hoặc ID của sidebar động.
	 */
	return apply_filters( 'dynamic_sidebar_has_widgets', $did_one, $index );
}

/**
 * Xác định xem một widget đã cho có được hiển thị ở giao diện trước hay không.
 *
 * Có thể sử dụng $callback hoặc $id_base.
 * $id_base là tham số đầu tiên khi kế thừa lớp WP_Widget.
 * Không có tham số $widget_id tùy chọn, trả về ID của sidebar đầu tiên
 * trong đó thể hiện đầu tiên của widget với callback hoặc $id_base đã cho được tìm thấy.
 * Với tham số $widget_id, trả về ID của sidebar nơi
 * widget với callback/$id_base ĐÓ VÀ ID đó được tìm thấy.
 *
 * LƯU Ý: $widget_id và $id_base giống nhau cho các widget đơn. Để có hiệu quả,
 * hàm này phải chạy sau khi widget đã khởi tạo, tại action {@see 'init'} hoặc sau.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Thẻ điều kiện} trong Sổ tay nhà phát triển Theme.
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_widgets Các widget đã đăng ký.
 *
 * @param callable|false $callback      Tùy chọn. Callback widget để kiểm tra. Mặc định false.
 * @param string|false   $widget_id     Tùy chọn. ID Widget. Tùy chọn, nhưng cần thiết để kiểm tra.
 *                                      Mặc định false.
 * @param string|false   $id_base       Tùy chọn. ID cơ sở của widget được tạo bằng cách kế thừa WP_Widget.
 *                                      Mặc định false.
 * @param bool           $skip_inactive Tùy chọn. Có bỏ qua kiểm tra trong 'wp_inactive_widgets' hay không.
 *                                      Mặc định true.
 * @return string|false ID của sidebar mà widget đang hoạt động,
 *                      false nếu widget không hoạt động.
 */
function is_active_widget( $callback = false, $widget_id = false, $id_base = false, $skip_inactive = true ) {
	global $wp_registered_widgets;

	$sidebars_widgets = wp_get_sidebars_widgets();

	if ( is_array( $sidebars_widgets ) ) {
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( $skip_inactive && ( 'wp_inactive_widgets' === $sidebar || str_starts_with( $sidebar, 'orphaned_widgets' ) ) ) {
				continue;
			}

			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $widget ) {
					if ( ( $callback && isset( $wp_registered_widgets[ $widget ]['callback'] ) && $wp_registered_widgets[ $widget ]['callback'] === $callback ) || ( $id_base && _get_widget_id_base( $widget ) === $id_base ) ) {
						if ( ! $widget_id || $widget_id === $wp_registered_widgets[ $widget ]['id'] ) {
							return $sidebar;
						}
					}
				}
			}
		}
	}
	return false;
}

/**
 * Xác định xem sidebar động có được bật và sử dụng bởi theme hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Thẻ điều kiện} trong Sổ tay nhà phát triển Theme.
 *
 * @since 2.2.0
 *
 * @global array $wp_registered_widgets  Các widget đã đăng ký.
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @return bool True nếu đang sử dụng widget, false nếu không.
 */
function is_dynamic_sidebar() {
	global $wp_registered_widgets, $wp_registered_sidebars;

	$sidebars_widgets = get_option( 'sidebars_widgets' );

	foreach ( (array) $wp_registered_sidebars as $index => $sidebar ) {
		if ( ! empty( $sidebars_widgets[ $index ] ) ) {
			foreach ( (array) $sidebars_widgets[ $index ] as $widget ) {
				if ( array_key_exists( $widget, $wp_registered_widgets ) ) {
					return true;
				}
			}
		}
	}

	return false;
}

/**
 * Xác định xem sidebar có chứa widget hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Thẻ điều kiện} trong Sổ tay nhà phát triển Theme.
 *
 * @since 2.8.0
 *
 * @param string|int $index Tên, id hoặc số của sidebar để kiểm tra.
 * @return bool True nếu sidebar có widget, false nếu không.
 */
function is_active_sidebar( $index ) {
	$index             = ( is_int( $index ) ) ? "sidebar-$index" : sanitize_title( $index );
	$sidebars_widgets  = wp_get_sidebars_widgets();
	$is_active_sidebar = ! empty( $sidebars_widgets[ $index ] );

	/**
	 * Lọc xem sidebar động có được coi là "hoạt động" hay không.
	 *
	 * @since 3.9.0
	 *
	 * @param bool       $is_active_sidebar Sidebar có nên được coi là "hoạt động" hay không.
	 *                                      Nói cách khác, sidebar có chứa widget nào không.
	 * @param int|string $index             Chỉ số, tên, hoặc ID của sidebar động.
	 */
	return apply_filters( 'is_active_sidebar', $is_active_sidebar, $index );
}

//
// Các hàm nội bộ.
//

/**
 * Lấy danh sách đầy đủ các sidebar và ID thể hiện widget của chúng.
 *
 * Sẽ nâng cấp danh sách widget sidebar, nếu cần. Cũng sẽ lưu danh sách
 * đã cập nhật, nếu cần.
 *
 * @since 2.2.0
 * @access private
 *
 * @global array $_wp_sidebars_widgets
 * @global array $sidebars_widgets
 *
 * @param bool $deprecated Không sử dụng (tham số đã ngừng sử dụng).
 * @return array Danh sách widget đã nâng cấp lên định dạng mảng phiên bản 3 khi được gọi từ trang quản trị.
 */
function wp_get_sidebars_widgets( $deprecated = true ) {
	if ( true !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '2.8.1' );
	}

	global $_wp_sidebars_widgets, $sidebars_widgets;

	/*
	 * Nếu tải từ trang giao diện, tham chiếu $_wp_sidebars_widgets thay vì options
	 * để xem wp_convert_widget_settings() đã thực hiện thao tác trong bộ nhớ chưa.
	 */
	if ( ! is_admin() ) {
		if ( empty( $_wp_sidebars_widgets ) ) {
			$_wp_sidebars_widgets = get_option( 'sidebars_widgets', array() );
		}

		$sidebars_widgets = $_wp_sidebars_widgets;
	} else {
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
	}

	if ( is_array( $sidebars_widgets ) && isset( $sidebars_widgets['array_version'] ) ) {
		unset( $sidebars_widgets['array_version'] );
	}

	/**
	 * Lọc danh sách các sidebar và widget của chúng.
	 *
	 * @since 2.7.0
	 *
	 * @param array $sidebars_widgets Mảng liên kết các sidebar và widget của chúng.
	 */
	return apply_filters( 'sidebars_widgets', $sidebars_widgets );
}

/**
 * Lấy sidebar đã đăng ký với ID đã cho.
 *
 * @since 5.9.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param string $id ID sidebar.
 * @return array|null Sidebar được tìm thấy, hoặc null nếu nó chưa được đăng ký.
 */
function wp_get_sidebar( $id ) {
	global $wp_registered_sidebars;

	foreach ( (array) $wp_registered_sidebars as $sidebar ) {
		if ( $sidebar['id'] === $id ) {
			return $sidebar;
		}
	}

	if ( 'wp_inactive_widgets' === $id ) {
		return array(
			'id'   => 'wp_inactive_widgets',
			'name' => __( 'Inactive widgets' ),
		);
	}

	return null;
}

/**
 * Đặt tùy chọn widget sidebar để cập nhật các sidebar.
 *
 * @since 2.2.0
 * @access private
 *
 * @global array $_wp_sidebars_widgets
 * @param array $sidebars_widgets Các widget sidebar và cài đặt của chúng.
 */
function wp_set_sidebars_widgets( $sidebars_widgets ) {
	global $_wp_sidebars_widgets;

	// Xóa giá trị cache được sử dụng trong wp_get_sidebars_widgets().
	$_wp_sidebars_widgets = null;

	if ( ! isset( $sidebars_widgets['array_version'] ) ) {
		$sidebars_widgets['array_version'] = 3;
	}

	update_option( 'sidebars_widgets', $sidebars_widgets );
}

/**
 * Lấy danh sách sidebar đã đăng ký mặc định.
 *
 * @since 2.2.0
 * @access private
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @return array
 */
function wp_get_widget_defaults() {
	global $wp_registered_sidebars;

	$defaults = array();

	foreach ( (array) $wp_registered_sidebars as $index => $sidebar ) {
		$defaults[ $index ] = array();
	}

	return $defaults;
}

/**
 * Chuyển đổi cài đặt widget từ định dạng đơn sang đa widget.
 *
 * @since 2.8.0
 *
 * @global array $_wp_sidebars_widgets
 *
 * @param string $base_name   ID gốc cho tất cả widget cùng loại này.
 * @param string $option_name Tên tùy chọn cho loại widget này.
 * @param array  $settings    Mảng cài đặt thể hiện widget.
 * @return array Mảng cài đặt widget đã chuyển đổi sang định dạng đa widget.
 */
function wp_convert_widget_settings( $base_name, $option_name, $settings ) {
	// Kiểm tra này có thể cần mở rộng.
	$single  = false;
	$changed = false;

	if ( empty( $settings ) ) {
		$single = true;
	} else {
		foreach ( array_keys( $settings ) as $number ) {
			if ( 'number' === $number ) {
				continue;
			}
			if ( ! is_numeric( $number ) ) {
				$single = true;
				break;
			}
		}
	}

	if ( $single ) {
		$settings = array( 2 => $settings );

		// Nếu tải từ trang giao diện, cập nhật sidebar trong bộ nhớ nhưng không lưu vào options.
		if ( is_admin() ) {
			$sidebars_widgets = get_option( 'sidebars_widgets' );
		} else {
			if ( empty( $GLOBALS['_wp_sidebars_widgets'] ) ) {
				$GLOBALS['_wp_sidebars_widgets'] = get_option( 'sidebars_widgets', array() );
			}
			$sidebars_widgets = &$GLOBALS['_wp_sidebars_widgets'];
		}

		foreach ( (array) $sidebars_widgets as $index => $sidebar ) {
			if ( is_array( $sidebar ) ) {
				foreach ( $sidebar as $i => $name ) {
					if ( $base_name === $name ) {
						$sidebars_widgets[ $index ][ $i ] = "$name-2";
						$changed                          = true;
						break 2;
					}
				}
			}
		}

		if ( is_admin() && $changed ) {
			update_option( 'sidebars_widgets', $sidebars_widgets );
		}
	}

	$settings['_multiwidget'] = 1;
	if ( is_admin() ) {
		update_option( $option_name, $settings );
	}

	return $settings;
}

/**
 * Xuất một widget tùy ý dưới dạng thẻ mẫu.
 *
 * @since 2.8.0
 *
 * @global WP_Widget_Factory $wp_widget_factory
 *
 * @param string $widget   Tên lớp PHP của widget (xem class-wp-widget.php).
 * @param array  $instance Tùy chọn. Cài đặt thể hiện widget. Mặc định mảng rỗng.
 * @param array  $args {
 *     Tùy chọn. Mảng tham số để cấu hình hiển thị widget.
 *
 *     @type string $before_widget Nội dung HTML sẽ được thêm trước đầu ra HTML của widget.
 *                                 Mặc định `<div class="widget %s">`, trong đó `%s` là tên lớp của widget.
 *     @type string $after_widget  Nội dung HTML sẽ được thêm sau đầu ra HTML của widget.
 *                                 Mặc định `</div>`.
 *     @type string $before_title  Nội dung HTML sẽ được thêm trước tiêu đề widget khi hiển thị.
 *                                 Mặc định `<h2 class="widgettitle">`.
 *     @type string $after_title   Nội dung HTML sẽ được thêm sau tiêu đề widget khi hiển thị.
 *                                 Mặc định `</h2>`.
 * }
 */
function the_widget( $widget, $instance = array(), $args = array() ) {
	global $wp_widget_factory;

	if ( ! isset( $wp_widget_factory->widgets[ $widget ] ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: register_widget() */
				__( 'Widgets need to be registered using %s, before they can be displayed.' ),
				'<code>register_widget()</code>'
			),
			'4.9.0'
		);
		return;
	}

	$widget_obj = $wp_widget_factory->widgets[ $widget ];
	if ( ! ( $widget_obj instanceof WP_Widget ) ) {
		return;
	}

	$default_args          = array(
		'before_widget' => '<div class="widget %s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="widgettitle">',
		'after_title'   => '</h2>',
	);
	$args                  = wp_parse_args( $args, $default_args );
	$args['before_widget'] = sprintf( $args['before_widget'], $widget_obj->widget_options['classname'] );

	$instance = wp_parse_args( $instance );

	/** Bộ lọc này được ghi nhận trong wp-includes/class-wp-widget.php */
	$instance = apply_filters( 'widget_display_callback', $instance, $widget_obj, $args );

	if ( false === $instance ) {
		return;
	}

	/**
	 * Kích hoạt trước khi hiển thị widget được yêu cầu.
	 *
	 * @since 3.0.0
	 *
	 * @param string $widget   Tên lớp của widget.
	 * @param array  $instance Cài đặt thể hiện widget hiện tại.
	 * @param array  $args     Mảng tham số sidebar của widget.
	 */
	do_action( 'the_widget', $widget, $instance, $args );

	$widget_obj->_set( -1 );
	$widget_obj->widget( $args, $instance );
}

/**
 * Lấy giá trị ID cơ sở của widget.
 *
 * @since 2.8.0
 *
 * @param string $id ID Widget.
 * @return string ID cơ sở Widget.
 */
function _get_widget_id_base( $id ) {
	return preg_replace( '/-[0-9]+$/', '', $id );
}

/**
 * Xử lý cấu hình sidebar sau khi thay đổi theme.
 *
 * @access private
 * @since 3.3.0
 *
 * @global array $sidebars_widgets
 */
function _wp_sidebars_changed() {
	global $sidebars_widgets;

	if ( ! is_array( $sidebars_widgets ) ) {
		$sidebars_widgets = wp_get_sidebars_widgets();
	}

	retrieve_widgets( true );
}

/**
 * Xác thực và ánh xạ lại các widget "mồ côi" sang sidebar wp_inactive_widgets,
 * và lưu cài đặt widget. Hàm này phải chạy ít nhất mỗi lần thay đổi theme.
 *
 * Ví dụ, giả sử theme A có sidebar "footer", và theme B không có.
 * Sau khi chuyển từ theme A sang theme B, tất cả widget trước đó được gán
 * cho footer sẽ không thể truy cập. Hàm này phát hiện tình huống này, và
 * chuyển tất cả widget trước đó được gán cho footer sang wp_inactive_widgets.
 *
 * Mặc dù có từ "retrieve" trong tên, hàm này thực sự cập nhật cơ sở dữ liệu
 * và biến toàn cục `$sidebars_widgets`. Vì lý do đó không nên chạy ở giao diện trước,
 * trừ khi giá trị `$theme_changed` là 'customize' (để bỏ qua ghi vào cơ sở dữ liệu).
 *
 * @since 2.8.0
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 * @global array $sidebars_widgets
 * @global array $wp_registered_widgets  Các widget đã đăng ký.
 *
 * @param string|bool $theme_changed Theme đã thay đổi hay chưa dưới dạng boolean. Giá trị
 *                                   'customize' hoãn cập nhật cho Trình tùy chỉnh.
 * @return array Các widget sidebar đã cập nhật.
 */
function retrieve_widgets( $theme_changed = false ) {
	global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

	$registered_sidebars_keys = array_keys( $wp_registered_sidebars );
	$registered_widgets_ids   = array_keys( $wp_registered_widgets );

	if ( ! is_array( get_theme_mod( 'sidebars_widgets' ) ) ) {
		if ( empty( $sidebars_widgets ) ) {
			return array();
		}

		unset( $sidebars_widgets['array_version'] );

		$sidebars_widgets_keys = array_keys( $sidebars_widgets );
		sort( $sidebars_widgets_keys );
		sort( $registered_sidebars_keys );

		if ( $sidebars_widgets_keys === $registered_sidebars_keys ) {
			$sidebars_widgets = _wp_remove_unregistered_widgets( $sidebars_widgets, $registered_widgets_ids );

			return $sidebars_widgets;
		}
	}

	// Loại bỏ các widget không hợp lệ, thuộc theme cụ thể khỏi sidebar.
	$sidebars_widgets = _wp_remove_unregistered_widgets( $sidebars_widgets, $registered_widgets_ids );
	$sidebars_widgets = wp_map_sidebars_widgets( $sidebars_widgets );

	// Tìm các thể hiện multi-widget bị ẩn/mất.
	$shown_widgets = array_merge( ...array_values( $sidebars_widgets ) );
	$lost_widgets  = array_diff( $registered_widgets_ids, $shown_widgets );

	foreach ( $lost_widgets as $key => $widget_id ) {
		$number = preg_replace( '/.+?-([0-9]+)$/', '$1', $widget_id );

		// Chỉ giữ các widget đang hoạt động và mặc định.
		if ( is_numeric( $number ) && (int) $number < 2 ) {
			unset( $lost_widgets[ $key ] );
		}
	}
	$sidebars_widgets['wp_inactive_widgets'] = array_merge( $lost_widgets, (array) $sidebars_widgets['wp_inactive_widgets'] );

	if ( 'customize' !== $theme_changed ) {
		// Cập nhật cài đặt widget trong cơ sở dữ liệu.
		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	return $sidebars_widgets;
}

/**
 * So sánh danh sách sidebar với widget của chúng so với danh sách cho phép.
 *
 * @since 4.9.0
 * @since 4.9.2 Luôn cố gắng khôi phục gán widget từ dữ liệu trước đó, không chỉ khi sidebar cần ánh xạ.
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param array $existing_sidebars_widgets Danh sách sidebar và ID thể hiện widget của chúng.
 * @return array Các widget sidebar đã ánh xạ.
 */
function wp_map_sidebars_widgets( $existing_sidebars_widgets ) {
	global $wp_registered_sidebars;

	$new_sidebars_widgets = array(
		'wp_inactive_widgets' => array(),
	);

	// Bỏ qua nếu không có sidebar để ánh xạ.
	if ( ! is_array( $existing_sidebars_widgets ) || empty( $existing_sidebars_widgets ) ) {
		return $new_sidebars_widgets;
	}

	foreach ( $existing_sidebars_widgets as $sidebar => $widgets ) {
		if ( 'wp_inactive_widgets' === $sidebar || str_starts_with( $sidebar, 'orphaned_widgets' ) ) {
			$new_sidebars_widgets['wp_inactive_widgets'] = array_merge( $new_sidebars_widgets['wp_inactive_widgets'], (array) $widgets );
			unset( $existing_sidebars_widgets[ $sidebar ] );
		}
	}

	// Nếu theme cũ và mới chỉ có một sidebar, ánh xạ nó và xong.
	if ( 1 === count( $existing_sidebars_widgets ) && 1 === count( $wp_registered_sidebars ) ) {
		$new_sidebars_widgets[ key( $wp_registered_sidebars ) ] = array_pop( $existing_sidebars_widgets );

		return $new_sidebars_widgets;
	}

	// Ánh xạ các vị trí có cùng slug.
	$existing_sidebars = array_keys( $existing_sidebars_widgets );

	foreach ( $wp_registered_sidebars as $sidebar => $name ) {
		if ( in_array( $sidebar, $existing_sidebars, true ) ) {
			$new_sidebars_widgets[ $sidebar ] = $existing_sidebars_widgets[ $sidebar ];
			unset( $existing_sidebars_widgets[ $sidebar ] );
		} elseif ( ! array_key_exists( $sidebar, $new_sidebars_widgets ) ) {
			$new_sidebars_widgets[ $sidebar ] = array();
		}
	}

	// Nếu có thêm sidebar, thử ánh xạ chúng.
	if ( ! empty( $existing_sidebars_widgets ) ) {

		/*
		 * Nếu theme cũ và mới đều có sidebar chứa cụm từ
		 * trong cùng nhóm, đoán thông minh và ánh xạ nó.
		 */
		$common_slug_groups = array(
			array( 'sidebar', 'primary', 'main', 'right' ),
			array( 'second', 'left' ),
			array( 'sidebar-2', 'footer', 'bottom' ),
			array( 'header', 'top' ),
		);

		// Duyệt qua từng nhóm...
		foreach ( $common_slug_groups as $slug_group ) {

			// ...và xem có slug nào...
			foreach ( $slug_group as $slug ) {

				// ...và sidebar mới nào...
				foreach ( $wp_registered_sidebars as $new_sidebar => $args ) {

					// ...thực sự khớp!
					if ( false === stripos( $new_sidebar, $slug ) && false === stripos( $slug, $new_sidebar ) ) {
						continue;
					}

					// Sau đó xem sidebar hiện tại nào...
					foreach ( $existing_sidebars_widgets as $sidebar => $widgets ) {

						// ...và slug nào trong cùng nhóm...
						foreach ( $slug_group as $slug ) {

							// ... cũng có sự khớp.
							if ( false === stripos( $sidebar, $slug ) && false === stripos( $slug, $sidebar ) ) {
								continue;
							}

							// Đảm bảo sidebar này chưa được ánh xạ và xóa trước đó.
							if ( ! empty( $existing_sidebars_widgets[ $sidebar ] ) ) {

								// Chúng ta có sự khớp có thể ánh xạ!
								$new_sidebars_widgets[ $new_sidebar ] = array_merge( $new_sidebars_widgets[ $new_sidebar ], $existing_sidebars_widgets[ $sidebar ] );

								// Xóa sidebar đã ánh xạ để không bị ánh xạ lại.
								unset( $existing_sidebars_widgets[ $sidebar ] );

								// Quay lại và kiểm tra sidebar mới tiếp theo.
								continue 3;
							}
						} // Kết thúc foreach ( $slug_group as $slug ).
					} // Kết thúc foreach ( $existing_sidebars_widgets as $sidebar => $widgets ).
				} // Kết thúc foreach ( $wp_registered_sidebars as $new_sidebar => $args ).
			} // Kết thúc foreach ( $slug_group as $slug ).
		} // Kết thúc foreach ( $common_slug_groups as $slug_group ).
	}

	// Chuyển các widget còn lại sang sidebar không hoạt động.
	foreach ( $existing_sidebars_widgets as $widgets ) {
		if ( is_array( $widgets ) && ! empty( $widgets ) ) {
			$new_sidebars_widgets['wp_inactive_widgets'] = array_merge( $new_sidebars_widgets['wp_inactive_widgets'], $widgets );
		}
	}

	// Cài đặt sidebars_widgets từ khi theme này được kích hoạt trước đó.
	$old_sidebars_widgets = get_theme_mod( 'sidebars_widgets' );
	$old_sidebars_widgets = isset( $old_sidebars_widgets['data'] ) ? $old_sidebars_widgets['data'] : false;

	if ( is_array( $old_sidebars_widgets ) ) {

		// Xóa sidebar rỗng, không cần ánh xạ những cái đó.
		$old_sidebars_widgets = array_filter( $old_sidebars_widgets );

		// Chỉ kiểm tra các sidebar rỗng hoặc chưa được ánh xạ.
		foreach ( $new_sidebars_widgets as $new_sidebar => $new_widgets ) {
			if ( array_key_exists( $new_sidebar, $old_sidebars_widgets ) && ! empty( $new_widgets ) ) {
				unset( $old_sidebars_widgets[ $new_sidebar ] );
			}
		}

		// Xóa widget mồ côi, chúng ta chỉ quan tâm đến sidebar đã hoạt động trước đó.
		foreach ( $old_sidebars_widgets as $sidebar => $widgets ) {
			if ( str_starts_with( $sidebar, 'orphaned_widgets' ) ) {
				unset( $old_sidebars_widgets[ $sidebar ] );
			}
		}

		$old_sidebars_widgets = _wp_remove_unregistered_widgets( $old_sidebars_widgets );

		if ( ! empty( $old_sidebars_widgets ) ) {

			// Duyệt qua từng sidebar còn lại...
			foreach ( $old_sidebars_widgets as $old_sidebar => $old_widgets ) {

				// ...và kiểm tra mọi sidebar mới...
				foreach ( $new_sidebars_widgets as $new_sidebar => $new_widgets ) {

					// ...cho mọi widget chúng ta đang cố khôi phục.
					foreach ( $old_widgets as $key => $widget_id ) {
						$active_key = array_search( $widget_id, $new_widgets, true );

						// Nếu widget được sử dụng ở nơi khác...
						if ( false !== $active_key ) {

							// ...và nơi đó là widget không hoạt động...
							if ( 'wp_inactive_widgets' === $new_sidebar ) {

								// ...xóa nó từ đó và giữ phiên bản hoạt động...
								unset( $new_sidebars_widgets['wp_inactive_widgets'][ $active_key ] );
							} else {

								// ...nếu không thì xóa nó khỏi sidebar cũ và giữ nó trong sidebar mới.
								unset( $old_sidebars_widgets[ $old_sidebar ][ $key ] );
							}
						} // Kết thúc if ( $active_key ).
					} // Kết thúc foreach ( $old_widgets as $key => $widget_id ).
				} // Kết thúc foreach ( $new_sidebars_widgets as $new_sidebar => $new_widgets ).
			} // Kết thúc foreach ( $old_sidebars_widgets as $old_sidebar => $old_widgets ).
		} // Kết thúc if ( ! empty( $old_sidebars_widgets ) ).

		// Khôi phục cài đặt widget từ khi theme được kích hoạt trước đó.
		$new_sidebars_widgets = array_merge( $new_sidebars_widgets, $old_sidebars_widgets );
	}

	return $new_sidebars_widgets;
}

/**
 * So sánh danh sách sidebar với widget của chúng so với danh sách cho phép.
 *
 * @since 4.9.0
 *
 * @global array $wp_registered_widgets Các widget đã đăng ký.
 *
 * @param array $sidebars_widgets   Danh sách sidebar và ID thể hiện widget của chúng.
 * @param array $allowed_widget_ids Tùy chọn. Danh sách ID widget để so sánh. Mặc định: Widget đã đăng ký.
 * @return array Các sidebar với widget được cho phép.
 */
function _wp_remove_unregistered_widgets( $sidebars_widgets, $allowed_widget_ids = array() ) {
	if ( empty( $allowed_widget_ids ) ) {
		$allowed_widget_ids = array_keys( $GLOBALS['wp_registered_widgets'] );
	}

	foreach ( $sidebars_widgets as $sidebar => $widgets ) {
		if ( is_array( $widgets ) ) {
			$sidebars_widgets[ $sidebar ] = array_intersect( $widgets, $allowed_widget_ids );
		}
	}

	return $sidebars_widgets;
}

/**
 * Hiển thị các mục RSS trong danh sách.
 *
 * @since 2.5.0
 *
 * @param string|array|object $rss  URL RSS.
 * @param array               $args Tham số widget.
 */
function wp_widget_rss_output( $rss, $args = array() ) {
	if ( is_string( $rss ) ) {
		$rss = fetch_feed( $rss );
	} elseif ( is_array( $rss ) && isset( $rss['url'] ) ) {
		$args = $rss;
		$rss  = fetch_feed( $rss['url'] );
	} elseif ( ! is_object( $rss ) ) {
		return;
	}

	if ( is_wp_error( $rss ) ) {
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			echo '<p><strong>' . __( 'RSS Error:' ) . '</strong> ' . esc_html( $rss->get_error_message() ) . '</p>';
		}
		return;
	}

	$default_args = array(
		'show_author'  => 0,
		'show_date'    => 0,
		'show_summary' => 0,
		'items'        => 0,
	);
	$args         = wp_parse_args( $args, $default_args );

	$items = (int) $args['items'];
	if ( $items < 1 || 20 < $items ) {
		$items = 10;
	}
	$show_summary = (int) $args['show_summary'];
	$show_author  = (int) $args['show_author'];
	$show_date    = (int) $args['show_date'];

	if ( ! $rss->get_item_quantity() ) {
		echo '<ul><li>' . __( 'An error has occurred, which probably means the feed is down. Try again later.' ) . '</li></ul>';
		$rss->__destruct();
		unset( $rss );
		return;
	}

	echo '<ul>';
	foreach ( $rss->get_items( 0, $items ) as $item ) {
		$link = $item->get_link();
		while ( ! empty( $link ) && stristr( $link, 'http' ) !== $link ) {
			$link = substr( $link, 1 );
		}
		$link = esc_url( strip_tags( $link ) );

		$title = esc_html( trim( strip_tags( $item->get_title() ) ) );
		if ( empty( $title ) ) {
			$title = __( 'Untitled' );
		}

		$desc = html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
		$desc = esc_attr( wp_trim_words( $desc, 55, ' [&hellip;]' ) );

		$summary = '';
		if ( $show_summary ) {
			$summary = $desc;

			// Thay đổi [...] hiện tại thành [&hellip;].
			if ( str_ends_with( $summary, '[...]' ) ) {
				$summary = substr( $summary, 0, -5 ) . '[&hellip;]';
			}

			$summary = '<div class="rssSummary">' . esc_html( $summary ) . '</div>';
		}

		$date = '';
		if ( $show_date ) {
			$date = $item->get_date( 'U' );

			if ( $date ) {
				$date = ' <span class="rss-date">' . date_i18n( get_option( 'date_format' ), $date ) . '</span>';
			}
		}

		$author = '';
		if ( $show_author ) {
			$author = $item->get_author();
			if ( is_object( $author ) ) {
				$author = $author->get_name();
				$author = ' <cite>' . esc_html( strip_tags( $author ) ) . '</cite>';
			}
		}

		if ( '' === $link ) {
			echo "<li>$title{$date}{$summary}{$author}</li>";
		} elseif ( $show_summary ) {
			echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$summary}{$author}</li>";
		} else {
			echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$author}</li>";
		}
	}
	echo '</ul>';
	$rss->__destruct();
	unset( $rss );
}

/**
 * Hiển thị form tùy chọn widget RSS.
 *
 * Các tùy chọn cho trường nào được hiển thị trong form RSS đều là boolean
 * và như sau: 'url', 'title', 'items', 'show_summary', 'show_author',
 * 'show_date'.
 *
 * @since 2.5.0
 *
 * @param array|string $args   Giá trị cho các trường nhập liệu.
 * @param array        $inputs Ghi đè tùy chọn hiển thị mặc định.
 */
function wp_widget_rss_form( $args, $inputs = null ) {
	$default_inputs = array(
		'url'          => true,
		'title'        => true,
		'items'        => true,
		'show_summary' => true,
		'show_author'  => true,
		'show_date'    => true,
	);
	$inputs         = wp_parse_args( $inputs, $default_inputs );

	$args['title'] = isset( $args['title'] ) ? $args['title'] : '';
	$args['url']   = isset( $args['url'] ) ? $args['url'] : '';
	$args['items'] = isset( $args['items'] ) ? (int) $args['items'] : 0;

	if ( $args['items'] < 1 || 20 < $args['items'] ) {
		$args['items'] = 10;
	}

	$args['show_summary'] = isset( $args['show_summary'] ) ? (int) $args['show_summary'] : (int) $inputs['show_summary'];
	$args['show_author']  = isset( $args['show_author'] ) ? (int) $args['show_author'] : (int) $inputs['show_author'];
	$args['show_date']    = isset( $args['show_date'] ) ? (int) $args['show_date'] : (int) $inputs['show_date'];

	if ( ! empty( $args['error'] ) ) {
		echo '<p class="widget-error"><strong>' . __( 'RSS Error:' ) . '</strong> ' . esc_html( $args['error'] ) . '</p>';
	}

	$esc_number = esc_attr( $args['number'] );
	if ( $inputs['url'] ) :
		?>
	<p><label for="rss-url-<?php echo $esc_number; ?>"><?php _e( 'Enter the RSS feed URL here:' ); ?></label>
	<input class="widefat" id="rss-url-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][url]" type="text" value="<?php echo esc_url( $args['url'] ); ?>" /></p>
<?php endif; if ( $inputs['title'] ) : ?>
	<p><label for="rss-title-<?php echo $esc_number; ?>"><?php _e( 'Give the feed a title (optional):' ); ?></label>
	<input class="widefat" id="rss-title-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][title]" type="text" value="<?php echo esc_attr( $args['title'] ); ?>" /></p>
<?php endif; if ( $inputs['items'] ) : ?>
	<p><label for="rss-items-<?php echo $esc_number; ?>"><?php _e( 'How many items would you like to display?' ); ?></label>
	<select id="rss-items-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][items]">
	<?php
	for ( $i = 1; $i <= 20; ++$i ) {
		echo "<option value='$i' " . selected( $args['items'], $i, false ) . ">$i</option>";
	}
	?>
	</select></p>
<?php endif; if ( $inputs['show_summary'] || $inputs['show_author'] || $inputs['show_date'] ) : ?>
	<p>
	<?php if ( $inputs['show_summary'] ) : ?>
		<input id="rss-show-summary-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][show_summary]" type="checkbox" value="1" <?php checked( $args['show_summary'] ); ?> />
		<label for="rss-show-summary-<?php echo $esc_number; ?>"><?php _e( 'Display item content?' ); ?></label><br />
	<?php endif; if ( $inputs['show_author'] ) : ?>
		<input id="rss-show-author-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][show_author]" type="checkbox" value="1" <?php checked( $args['show_author'] ); ?> />
		<label for="rss-show-author-<?php echo $esc_number; ?>"><?php _e( 'Display item author if available?' ); ?></label><br />
	<?php endif; if ( $inputs['show_date'] ) : ?>
		<input id="rss-show-date-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][show_date]" type="checkbox" value="1" <?php checked( $args['show_date'] ); ?> />
		<label for="rss-show-date-<?php echo $esc_number; ?>"><?php _e( 'Display item date?' ); ?></label><br />
	<?php endif; ?>
	</p>
	<?php
	endif; // Kết thúc tùy chọn hiển thị.
foreach ( array_keys( $default_inputs ) as $input ) :
	if ( 'hidden' === $inputs[ $input ] ) :
		$id = str_replace( '_', '-', $input );
		?>
<input type="hidden" id="rss-<?php echo esc_attr( $id ); ?>-<?php echo $esc_number; ?>" name="widget-rss[<?php echo $esc_number; ?>][<?php echo esc_attr( $input ); ?>]" value="<?php echo esc_attr( $args[ $input ] ); ?>" />
		<?php
	endif;
	endforeach;
}

/**
 * Xử lý dữ liệu widget nguồn cấp RSS và tùy chọn lấy các mục nguồn cấp.
 *
 * Widget nguồn cấp không thể có nhiều hơn 20 mục nếu không sẽ đặt lại về
 * mặc định là 10.
 *
 * Mảng kết quả có tiêu đề nguồn cấp, url nguồn cấp, liên kết nguồn cấp (từ kênh),
 * các mục nguồn cấp, lỗi (nếu có), và có hiển thị tóm tắt, tác giả, và ngày hay không.
 * Tất cả tương ứng theo thứ tự phần tử mảng.
 *
 * @since 2.5.0
 *
 * @param array $widget_rss Dữ liệu nguồn cấp widget RSS. Yêu cầu dữ liệu chưa thoát.
 * @param bool  $check_feed Tùy chọn. Có kiểm tra lỗi nguồn cấp hay không. Mặc định true.
 * @return array
 */
function wp_widget_rss_process( $widget_rss, $check_feed = true ) {
	$items = (int) $widget_rss['items'];
	if ( $items < 1 || 20 < $items ) {
		$items = 10;
	}
	$url          = sanitize_url( strip_tags( $widget_rss['url'] ) );
	$title        = isset( $widget_rss['title'] ) ? trim( strip_tags( $widget_rss['title'] ) ) : '';
	$show_summary = isset( $widget_rss['show_summary'] ) ? (int) $widget_rss['show_summary'] : 0;
	$show_author  = isset( $widget_rss['show_author'] ) ? (int) $widget_rss['show_author'] : 0;
	$show_date    = isset( $widget_rss['show_date'] ) ? (int) $widget_rss['show_date'] : 0;
	$error        = false;
	$link         = '';

	if ( $check_feed ) {
		$rss = fetch_feed( $url );

		if ( is_wp_error( $rss ) ) {
			$error = $rss->get_error_message();
		} else {
			$link = esc_url( strip_tags( $rss->get_permalink() ) );
			while ( stristr( $link, 'http' ) !== $link ) {
				$link = substr( $link, 1 );
			}

			$rss->__destruct();
			unset( $rss );
		}
	}

	return compact( 'title', 'url', 'link', 'items', 'error', 'show_summary', 'show_author', 'show_date' );
}

/**
 * Đăng ký tất cả widget WordPress mặc định khi khởi động.
 *
 * Gọi action {@see 'widgets_init'} sau khi tất cả widget WordPress đã được đăng ký.
 *
 * @since 2.2.0
 */
function wp_widgets_init() {
	if ( ! is_blog_installed() ) {
		return;
	}

	register_widget( 'WP_Widget_Pages' );

	register_widget( 'WP_Widget_Calendar' );

	register_widget( 'WP_Widget_Archives' );

	if ( get_option( 'link_manager_enabled' ) ) {
		register_widget( 'WP_Widget_Links' );
	}

	register_widget( 'WP_Widget_Media_Audio' );

	register_widget( 'WP_Widget_Media_Image' );

	register_widget( 'WP_Widget_Media_Gallery' );

	register_widget( 'WP_Widget_Media_Video' );

	register_widget( 'WP_Widget_Meta' );

	register_widget( 'WP_Widget_Search' );

	register_widget( 'WP_Widget_Text' );

	register_widget( 'WP_Widget_Categories' );

	register_widget( 'WP_Widget_Recent_Posts' );

	register_widget( 'WP_Widget_Recent_Comments' );

	register_widget( 'WP_Widget_RSS' );

	register_widget( 'WP_Widget_Tag_Cloud' );

	register_widget( 'WP_Nav_Menu_Widget' );

	register_widget( 'WP_Widget_Custom_HTML' );

	register_widget( 'WP_Widget_Block' );

	/**
	 * Kích hoạt sau khi tất cả widget WordPress mặc định đã được đăng ký.
	 *
	 * @since 2.2.0
	 */
	do_action( 'widgets_init' );
}

/**
 * Kích hoạt trình soạn thảo khối widget. Được gắn vào 'after_setup_theme' để
 * trình soạn thảo khối được bật mặc định nhưng có thể bị tắt bởi theme.
 *
 * @since 5.8.0
 *
 * @access private
 */
function wp_setup_widgets_block_editor() {
	add_theme_support( 'widgets-block-editor' );
}

/**
 * Xác định có sử dụng trình soạn thảo khối để quản lý widget hay không.
 * Mặc định true trừ khi theme đã xóa hỗ trợ cho widgets-block-editor
 * hoặc plugin đã lọc giá trị trả về của hàm này.
 *
 * @since 5.8.0
 *
 * @return bool Có sử dụng trình soạn thảo khối để quản lý widget hay không.
 */
function wp_use_widgets_block_editor() {
	/**
	 * Lọc có sử dụng trình soạn thảo khối để quản lý widget hay không.
	 *
	 * @since 5.8.0
	 *
	 * @param bool $use_widgets_block_editor Có sử dụng trình soạn thảo khối để quản lý widget hay không.
	 */
	return apply_filters(
		'use_widgets_block_editor',
		get_theme_support( 'widgets-block-editor' )
	);
}

/**
 * Chuyển đổi ID widget thành các thành phần id_base và number.
 *
 * @since 5.8.0
 *
 * @param string $id ID Widget.
 * @return array Mảng chứa các thành phần id_base và number của widget.
 */
function wp_parse_widget_id( $id ) {
	$parsed = array();

	if ( preg_match( '/^(.+)-(\d+)$/', $id, $matches ) ) {
		$parsed['id_base'] = $matches[1];
		$parsed['number']  = (int) $matches[2];
	} else {
		// Có thể là widget đơn kiểu cũ.
		$parsed['id_base'] = $id;
	}

	return $parsed;
}

/**
 * Tìm sidebar mà widget đã cho thuộc về.
 *
 * @since 5.8.0
 *
 * @param string $widget_id ID widget cần tìm.
 * @return string|null ID của sidebar được tìm thấy, hoặc null nếu không tìm thấy.
 */
function wp_find_widgets_sidebar( $widget_id ) {
	foreach ( wp_get_sidebars_widgets() as $sidebar_id => $widget_ids ) {
		foreach ( $widget_ids as $maybe_widget_id ) {
			if ( $maybe_widget_id === $widget_id ) {
				return (string) $sidebar_id;
			}
		}
	}

	return null;
}

/**
 * Gán widget cho sidebar đã cho.
 *
 * @since 5.8.0
 *
 * @param string $widget_id  ID widget cần gán.
 * @param string $sidebar_id ID sidebar cần gán vào. Nếu rỗng, widget sẽ không được thêm vào sidebar nào.
 */
function wp_assign_widget_to_sidebar( $widget_id, $sidebar_id ) {
	$sidebars = wp_get_sidebars_widgets();

	foreach ( $sidebars as $maybe_sidebar_id => $widgets ) {
		foreach ( $widgets as $i => $maybe_widget_id ) {
			if ( $widget_id === $maybe_widget_id && $sidebar_id !== $maybe_sidebar_id ) {
				unset( $sidebars[ $maybe_sidebar_id ][ $i ] );
				// Về mặt kỹ thuật có thể break 2 ở đây, nhưng tiếp tục lặp trong trường hợp ID bị trùng.
				continue 2;
			}
		}
	}

	if ( $sidebar_id ) {
		$sidebars[ $sidebar_id ][] = $widget_id;
	}

	wp_set_sidebars_widgets( $sidebars );
}

/**
 * Gọi callback hiển thị của widget và trả về đầu ra.
 *
 * @since 5.8.0
 *
 * @global array $wp_registered_widgets  Các widget đã đăng ký.
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 *
 * @param string $widget_id  ID Widget.
 * @param string $sidebar_id ID Sidebar.
 * @return string
 */
function wp_render_widget( $widget_id, $sidebar_id ) {
	global $wp_registered_widgets, $wp_registered_sidebars;

	if ( ! isset( $wp_registered_widgets[ $widget_id ] ) ) {
		return '';
	}

	if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
		$sidebar = $wp_registered_sidebars[ $sidebar_id ];
	} elseif ( 'wp_inactive_widgets' === $sidebar_id ) {
		$sidebar = array();
	} else {
		return '';
	}

	$params = array_merge(
		array(
			array_merge(
				$sidebar,
				array(
					'widget_id'   => $widget_id,
					'widget_name' => $wp_registered_widgets[ $widget_id ]['name'],
				)
			),
		),
		(array) $wp_registered_widgets[ $widget_id ]['params']
	);

	// Thay thế thuộc tính HTML `id` và `class` vào `before_widget`.
	$classname_ = '';
	foreach ( (array) $wp_registered_widgets[ $widget_id ]['classname'] as $cn ) {
		if ( is_string( $cn ) ) {
			$classname_ .= '_' . $cn;
		} elseif ( is_object( $cn ) ) {
			$classname_ .= '_' . get_class( $cn );
		}
	}
	$classname_                 = ltrim( $classname_, '_' );
	$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $widget_id, $classname_ );

	/** Bộ lọc này được ghi nhận trong wp-includes/widgets.php */
	$params = apply_filters( 'dynamic_sidebar_params', $params );

	$callback = $wp_registered_widgets[ $widget_id ]['callback'];

	ob_start();

	/** Bộ lọc này được ghi nhận trong wp-includes/widgets.php */
	do_action( 'dynamic_sidebar', $wp_registered_widgets[ $widget_id ] );

	if ( is_callable( $callback ) ) {
		call_user_func_array( $callback, $params );
	}

	return ob_get_clean();
}

/**
 * Gọi callback điều khiển của widget và trả về đầu ra.
 *
 * @since 5.8.0
 *
 * @global array $wp_registered_widget_controls Các điều khiển widget đã đăng ký.
 *
 * @param string $id ID Widget.
 * @return string|null
 */
function wp_render_widget_control( $id ) {
	global $wp_registered_widget_controls;

	if ( ! isset( $wp_registered_widget_controls[ $id ]['callback'] ) ) {
		return null;
	}

	$callback = $wp_registered_widget_controls[ $id ]['callback'];
	$params   = $wp_registered_widget_controls[ $id ]['params'];

	ob_start();

	if ( is_callable( $callback ) ) {
		call_user_func_array( $callback, $params );
	}

	return ob_get_clean();
}

/**
 * Hiển thị thông báo _doing_it_wrong() cho các script trình soạn thảo widget bị xung đột.
 *
 * Module script 'wp-editor' được hiển thị dưới dạng window.wp.editor. Điều này ghi đè
 * module trình soạn thảo TinyMCE cũ cần thiết cho trình soạn thảo widget.
 * Do xung đột đó, hai module này không nên được enqueue cùng nhau.
 * Xem https://core.trac.wordpress.org/ticket/53569.
 *
 * Cũng có một xung đột khác liên quan đến style, nơi trình soạn thảo
 * widget khối bị ẩn nếu một khối enqueue stylesheet 'wp-edit-post'.
 * Xem https://core.trac.wordpress.org/ticket/53569.
 *
 * @since 5.8.0
 * @access private
 *
 * @global WP_Scripts $wp_scripts
 * @global WP_Styles  $wp_styles
 */
function wp_check_widget_editor_deps() {
	global $wp_scripts, $wp_styles;

	if (
		$wp_scripts->query( 'wp-edit-widgets', 'enqueued' ) ||
		$wp_scripts->query( 'wp-customize-widgets', 'enqueued' )
	) {
		if ( $wp_scripts->query( 'wp-editor', 'enqueued' ) ) {
			_doing_it_wrong(
				'wp_enqueue_script()',
				sprintf(
					/* translators: 1: 'wp-editor', 2: 'wp-edit-widgets', 3: 'wp-customize-widgets'. */
					__( '"%1$s" script should not be enqueued together with the new widgets editor (%2$s or %3$s).' ),
					'wp-editor',
					'wp-edit-widgets',
					'wp-customize-widgets'
				),
				'5.8.0'
			);
		}
		if ( $wp_styles->query( 'wp-edit-post', 'enqueued' ) ) {
			_doing_it_wrong(
				'wp_enqueue_style()',
				sprintf(
					/* translators: 1: 'wp-edit-post', 2: 'wp-edit-widgets', 3: 'wp-customize-widgets'. */
					__( '"%1$s" style should not be enqueued together with the new widgets editor (%2$s or %3$s).' ),
					'wp-edit-post',
					'wp-edit-widgets',
					'wp-customize-widgets'
				),
				'5.8.0'
			);
		}
	}
}

/**
 * Đăng ký các sidebar của theme trước cho các theme khối.
 *
 * @since 6.2.0
 * @access private
 *
 * @global array $wp_registered_sidebars Các sidebar đã đăng ký.
 */
function _wp_block_theme_register_classic_sidebars() {
	global $wp_registered_sidebars;

	if ( ! wp_is_block_theme() ) {
		return;
	}

	$classic_sidebars = get_theme_mod( 'wp_classic_sidebars' );
	if ( empty( $classic_sidebars ) ) {
		return;
	}

	// Không sử dụng `register_sidebar` vì nó sẽ bật hỗ trợ `widgets` cho theme.
	foreach ( $classic_sidebars as $sidebar ) {
		$wp_registered_sidebars[ $sidebar['id'] ] = $sidebar;
	}
}
