<?php
/**
 * Các hàm REST API.
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.4.0
 */

/**
 * Số phiên bản cho API của chúng ta.
 *
 * @var string
 */
define( 'REST_API_VERSION', '2.0' );

/**
 * Đăng ký một route REST API.
 *
 * Lưu ý: Không sử dụng trước hook {@see 'rest_api_init'}.
 *
 * @since 4.4.0
 * @since 5.1.0 Thêm thông báo `_doing_it_wrong()` khi không được gọi tại hoặc sau hook `rest_api_init`.
 * @since 5.5.0 Thêm thông báo `_doing_it_wrong()` khi tham số bắt buộc `permission_callback` không được đặt.
 *
 * @param string $route_namespace Phân đoạn URL đầu tiên sau tiền tố core. Nên là duy nhất cho gói/plugin của bạn.
 * @param string $route           URL cơ sở cho route bạn đang thêm.
 * @param array  $args            Tùy chọn. Mảng các tùy chọn cho endpoint, hoặc mảng các mảng cho
 *                                nhiều phương thức. Mặc định mảng rỗng.
 * @param bool   $override        Tùy chọn. Nếu route đã tồn tại, có nên ghi đè không? True ghi đè,
 *                                false hợp nhất (cái mới sẽ ghi đè nếu trùng key). Mặc định false.
 * @return bool True khi thành công, false khi có lỗi.
 */
function register_rest_route( $route_namespace, $route, $args = array(), $override = false ) {
	if ( empty( $route_namespace ) ) {
		/*
		 * Các route không có namespace không được phép, ngoại trừ chỉ mục chính
		 * và chỉ mục namespace. Nếu bạn thực sự cần đăng ký một
		 * route không có namespace, hãy gọi trực tiếp `WP_REST_Server::register_route`.
		 */
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: string value of the namespace, 2: string value of the route. */
				__( 'Routes must be namespaced with plugin or theme name and version. Instead there seems to be an empty namespace \'%1$s\' for route \'%2$s\'.' ),
				'<code>' . $route_namespace . '</code>',
				'<code>' . $route . '</code>'
			),
			'4.4.0'
		);
		return false;
	} elseif ( empty( $route ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: string value of the namespace, 2: string value of the route. */
				__( 'Route must be specified. Instead within the namespace \'%1$s\', there seems to be an empty route \'%2$s\'.' ),
				'<code>' . $route_namespace . '</code>',
				'<code>' . $route . '</code>'
			),
			'4.4.0'
		);
		return false;
	}

	$clean_namespace = trim( $route_namespace, '/' );

	if ( $clean_namespace !== $route_namespace ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: string value of the namespace, 2: string value of the route. */
				__( 'Namespace must not start or end with a slash. Instead namespace \'%1$s\' for route \'%2$s\' seems to contain a slash.' ),
				'<code>' . $route_namespace . '</code>',
				'<code>' . $route . '</code>'
			),
			'5.4.2'
		);
	}

	if ( ! did_action( 'rest_api_init' ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: rest_api_init, 2: string value of the route, 3: string value of the namespace. */
				__( 'REST API routes must be registered on the %1$s action. Instead route \'%2$s\' with namespace \'%3$s\' was not registered on this action.' ),
				'<code>rest_api_init</code>',
				'<code>' . $route . '</code>',
				'<code>' . $route_namespace . '</code>'
			),
			'5.1.0'
		);
	}

	if ( isset( $args['args'] ) ) {
		$common_args = $args['args'];
		unset( $args['args'] );
	} else {
		$common_args = array();
	}

	if ( isset( $args['callback'] ) ) {
		// Nâng cấp một tập đơn thành nhiều tập.
		$args = array( $args );
	}

	$defaults = array(
		'methods'  => 'GET',
		'callback' => null,
		'args'     => array(),
	);

	foreach ( $args as $key => &$arg_group ) {
		if ( ! is_numeric( $key ) ) {
			// Tùy chọn route, bỏ qua ở đây.
			continue;
		}

		$arg_group         = array_merge( $defaults, $arg_group );
		$arg_group['args'] = array_merge( $common_args, $arg_group['args'] );

		if ( ! isset( $arg_group['permission_callback'] ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: 1: The REST API route being registered, 2: The argument name, 3: The suggested function name. */
					__( 'The REST API route definition for %1$s is missing the required %2$s argument. For REST API routes that are intended to be public, use %3$s as the permission callback.' ),
					'<code>' . $clean_namespace . '/' . trim( $route, '/' ) . '</code>',
					'<code>permission_callback</code>',
					'<code>__return_true</code>'
				),
				'5.5.0'
			);
		}

		foreach ( $arg_group['args'] as $arg ) {
			if ( ! is_array( $arg ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: 1: $args, 2: The REST API route being registered. */
						__( 'REST API %1$s should be an array of arrays. Non-array value detected for %2$s.' ),
						'<code>$args</code>',
						'<code>' . $clean_namespace . '/' . trim( $route, '/' ) . '</code>'
					),
					'6.1.0'
				);
				break; // Thoát vòng lặp foreach khi tìm thấy tham số không phải mảng.
			}
		}
	}

	$full_route = '/' . $clean_namespace . '/' . trim( $route, '/' );
	rest_get_server()->register_route( $clean_namespace, $full_route, $args, $override );
	return true;
}

/**
 * Đăng ký một trường mới trên loại đối tượng WordPress hiện có.
 *
 * @since 4.7.0
 *
 * @global array $wp_rest_additional_fields Chứa các trường đã đăng ký, được tổ chức
 *                                          theo loại đối tượng.
 *
 * @param string|array $object_type Đối tượng mà trường đang được đăng ký,
 *                                  "post"|"term"|"comment" v.v.
 * @param string       $attribute   Tên thuộc tính.
 * @param array        $args {
 *     Tùy chọn. Mảng các tham số dùng để xử lý trường đã đăng ký.
 *
 *     @type callable|null $get_callback    Tùy chọn. Hàm callback dùng để lấy giá trị trường. Mặc định là
 *                                          'null', trường sẽ không được trả về trong phản hồi. Hàm sẽ
 *                                          được truyền dữ liệu đối tượng đã chuẩn bị.
 *     @type callable|null $update_callback Tùy chọn. Hàm callback dùng để đặt và cập nhật giá trị trường. Mặc định
 *                                          là 'null', giá trị không thể được đặt hoặc cập nhật. Hàm sẽ được truyền
 *                                          đối tượng model, như WP_Post.
 *     @type array|null $schema             Tùy chọn. Schema cho trường này.
 *                                          Mặc định là 'null', không có mục schema nào được trả về.
 * }
 */
function register_rest_field( $object_type, $attribute, $args = array() ) {
	global $wp_rest_additional_fields;

	$defaults = array(
		'get_callback'    => null,
		'update_callback' => null,
		'schema'          => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$object_types = (array) $object_type;

	foreach ( $object_types as $object_type ) {
		$wp_rest_additional_fields[ $object_type ][ $attribute ] = $args;
	}
}

/**
 * Đăng ký các quy tắc rewrite cho REST API.
 *
 * @since 4.4.0
 *
 * @see rest_api_register_rewrites()
 * @global WP $wp Thực thể môi trường WordPress hiện tại.
 */
function rest_api_init() {
	rest_api_register_rewrites();

	global $wp;
	$wp->add_query_var( 'rest_route' );
}

/**
 * Thêm các quy tắc rewrite REST.
 *
 * @since 4.4.0
 *
 * @see add_rewrite_rule()
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 */
function rest_api_register_rewrites() {
	global $wp_rewrite;

	add_rewrite_rule( '^' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
	add_rewrite_rule( '^' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
	add_rewrite_rule( '^' . $wp_rewrite->index . '/' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
	add_rewrite_rule( '^' . $wp_rewrite->index . '/' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
}

/**
 * Đăng ký các bộ lọc REST API mặc định.
 *
 * Được gắn vào action {@see 'rest_api_init'}
 * để việc kiểm thử và tắt các bộ lọc này dễ dàng hơn.
 *
 * @since 4.4.0
 */
function rest_api_default_filters() {
	if ( wp_is_serving_rest_request() ) {
		// Báo cáo deprecated.
		add_action( 'deprecated_function_run', 'rest_handle_deprecated_function', 10, 3 );
		add_filter( 'deprecated_function_trigger_error', '__return_false' );
		add_action( 'deprecated_argument_run', 'rest_handle_deprecated_argument', 10, 3 );
		add_filter( 'deprecated_argument_trigger_error', '__return_false' );
		add_action( 'doing_it_wrong_run', 'rest_handle_doing_it_wrong', 10, 3 );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	// Phục vụ mặc định.
	add_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_post_dispatch', 'rest_send_allow_header', 10, 3 );
	add_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );

	add_filter( 'rest_pre_dispatch', 'rest_handle_options_request', 10, 3 );
	add_filter( 'rest_index', 'rest_add_application_passwords_to_index' );
}

/**
 * Đăng ký các route REST API mặc định.
 *
 * @since 4.7.0
 */
function create_initial_rest_routes() {
	foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
		$controller = $post_type->get_rest_controller();

		if ( ! $controller ) {
			continue;
		}

		if ( ! $post_type->late_route_registration ) {
			$controller->register_routes();
		}

		$revisions_controller = $post_type->get_revisions_rest_controller();
		if ( $revisions_controller ) {
			$revisions_controller->register_routes();
		}

		$autosaves_controller = $post_type->get_autosave_rest_controller();
		if ( $autosaves_controller ) {
			$autosaves_controller->register_routes();
		}

		if ( $post_type->late_route_registration ) {
			$controller->register_routes();
		}
	}

	// Loại bài viết.
	$controller = new WP_REST_Post_Types_Controller();
	$controller->register_routes();

	// Trạng thái bài viết.
	$controller = new WP_REST_Post_Statuses_Controller();
	$controller->register_routes();

	// Phân loại.
	$controller = new WP_REST_Taxonomies_Controller();
	$controller->register_routes();

	// Thuật ngữ.
	foreach ( get_taxonomies( array( 'show_in_rest' => true ), 'object' ) as $taxonomy ) {
		$controller = $taxonomy->get_rest_controller();

		if ( ! $controller ) {
			continue;
		}

		$controller->register_routes();
	}

	// Người dùng.
	$controller = new WP_REST_Users_Controller();
	$controller->register_routes();

	// Mật khẩu ứng dụng.
	$controller = new WP_REST_Application_Passwords_Controller();
	$controller->register_routes();

	// Bình luận.
	$controller = new WP_REST_Comments_Controller();
	$controller->register_routes();

	$search_handlers = array(
		new WP_REST_Post_Search_Handler(),
		new WP_REST_Term_Search_Handler(),
		new WP_REST_Post_Format_Search_Handler(),
	);

	/**
	 * Lọc các trình xử lý tìm kiếm để sử dụng trong bộ điều khiển tìm kiếm REST.
	 *
	 * @since 5.0.0
	 *
	 * @param array $search_handlers Danh sách các trình xử lý tìm kiếm để sử dụng trong bộ điều khiển. Mỗi thực thể
	 *                               trình xử lý tìm kiếm phải mở rộng lớp `WP_REST_Search_Handler`.
	 *                               Mặc định chỉ có trình xử lý cho bài viết.
	 */
	$search_handlers = apply_filters( 'wp_rest_search_handlers', $search_handlers );

	$controller = new WP_REST_Search_Controller( $search_handlers );
	$controller->register_routes();

	// Bộ render khối.
	$controller = new WP_REST_Block_Renderer_Controller();
	$controller->register_routes();

	// Loại khối.
	$controller = new WP_REST_Block_Types_Controller();
	$controller->register_routes();

	// Cài đặt.
	$controller = new WP_REST_Settings_Controller();
	$controller->register_routes();

	// Giao diện.
	$controller = new WP_REST_Themes_Controller();
	$controller->register_routes();

	// Plugin.
	$controller = new WP_REST_Plugins_Controller();
	$controller->register_routes();

	// Thanh bên.
	$controller = new WP_REST_Sidebars_Controller();
	$controller->register_routes();

	// Loại widget.
	$controller = new WP_REST_Widget_Types_Controller();
	$controller->register_routes();

	// Widget.
	$controller = new WP_REST_Widgets_Controller();
	$controller->register_routes();

	// Thư mục khối.
	$controller = new WP_REST_Block_Directory_Controller();
	$controller->register_routes();

	// Thư mục mẫu.
	$controller = new WP_REST_Pattern_Directory_Controller();
	$controller->register_routes();

	// Mẫu khối.
	$controller = new WP_REST_Block_Patterns_Controller();
	$controller->register_routes();

	// Danh mục mẫu khối.
	$controller = new WP_REST_Block_Pattern_Categories_Controller();
	$controller->register_routes();

	// Sức khỏe trang web.
	$site_health = WP_Site_Health::get_instance();
	$controller  = new WP_REST_Site_Health_Controller( $site_health );
	$controller->register_routes();

	// Chi tiết URL.
	$controller = new WP_REST_URL_Details_Controller();
	$controller->register_routes();

	// Vị trí menu.
	$controller = new WP_REST_Menu_Locations_Controller();
	$controller->register_routes();

	// Xuất trình soạn thảo trang web.
	$controller = new WP_REST_Edit_Site_Export_Controller();
	$controller->register_routes();

	// Điều hướng dự phòng.
	$controller = new WP_REST_Navigation_Fallback_Controller();
	$controller->register_routes();

	// Bộ sưu tập phông chữ.
	$font_collections_controller = new WP_REST_Font_Collections_Controller();
	$font_collections_controller->register_routes();
}

/**
 * Tải REST API.
 *
 * @since 4.4.0
 *
 * @global WP $wp Thực thể môi trường WordPress hiện tại.
 */
function rest_api_loaded() {
	if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		return;
	}

	// Trả về thông báo lỗi nếu query_var không phải chuỗi.
	if ( ! is_string( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		$rest_type_error = new WP_Error(
			'rest_path_invalid_type',
			__( 'The REST route parameter must be a string.' ),
			array( 'status' => 400 )
		);
		wp_die( $rest_type_error );
	}

	/**
	 * Đây có phải là yêu cầu REST không.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	define( 'REST_REQUEST', true );

	// Khởi tạo server.
	$server = rest_get_server();

	// Thực thi yêu cầu.
	$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
	if ( empty( $route ) ) {
		$route = '/';
	}
	$server->serve_request( $route );

	// Hoàn tất.
	die();
}

/**
 * Lấy tiền tố URL cho bất kỳ tài nguyên API nào.
 *
 * @since 4.4.0
 *
 * @return string Tiền tố.
 */
function rest_get_url_prefix() {
	/**
	 * Lọc tiền tố URL REST.
	 *
	 * @since 4.4.0
	 *
	 * @param string $prefix Tiền tố URL. Mặc định 'wp-json'.
	 */
	return apply_filters( 'rest_url_prefix', 'wp-json' );
}

/**
 * Lấy URL đến endpoint REST trên một trang web.
 *
 * Lưu ý: URL trả về KHÔNG được escape.
 *
 * @since 4.4.0
 *
 * @todo Kiểm tra xem điều này có thực sự cần thiết không
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 *
 * @param int|null $blog_id Tùy chọn. ID blog. Mặc định null trả về URL cho blog hiện tại.
 * @param string   $path    Tùy chọn. Route REST. Mặc định '/'.
 * @param string   $scheme  Tùy chọn. Kiểu làm sạch. Mặc định 'rest'.
 * @return string URL đầy đủ đến endpoint.
 */
function get_rest_url( $blog_id = null, $path = '/', $scheme = 'rest' ) {
	if ( empty( $path ) ) {
		$path = '/';
	}

	$path = '/' . ltrim( $path, '/' );

	if ( is_multisite() && get_blog_option( $blog_id, 'permalink_structure' ) || get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;

		if ( $wp_rewrite->using_index_permalinks() ) {
			$url = get_home_url( $blog_id, $wp_rewrite->index . '/' . rest_get_url_prefix(), $scheme );
		} else {
			$url = get_home_url( $blog_id, rest_get_url_prefix(), $scheme );
		}

		$url .= $path;
	} else {
		$url = trailingslashit( get_home_url( $blog_id, '', $scheme ) );
		/*
		 * nginx chỉ cho phép các phương thức HTTP/1.0 khi chuyển hướng từ / sang /index.php.
		 * Để giải quyết vấn đề này, chúng ta thêm index.php vào URL thủ công, tránh chuyển hướng.
		 */
		if ( ! str_ends_with( $url, 'index.php' ) ) {
			$url .= 'index.php';
		}

		$url = add_query_arg( 'rest_route', $path, $url );
	}

	if ( is_ssl() && isset( $_SERVER['SERVER_NAME'] ) ) {
		// Nếu host hiện tại giống với host URL REST, buộc kiểu URL REST sang HTTPS.
		if ( parse_url( get_home_url( $blog_id ), PHP_URL_HOST ) === $_SERVER['SERVER_NAME'] ) {
			$url = set_url_scheme( $url, 'https' );
		}
	}

	if ( is_admin() && force_ssl_admin() ) {
		/*
		 * Trong trường hợp này URL trang chủ có thể là http:, và `is_ssl()` có thể là false,
		 * nhưng trang quản trị được phục vụ qua https: (bằng cách này hay cách khác), nên việc sử dụng REST API
		 * sẽ bị trình duyệt chặn trừ khi nó cũng được phục vụ qua HTTPS.
		 */
		$url = set_url_scheme( $url, 'https' );
	}

	/**
	 * Lọc URL REST.
	 *
	 * Sử dụng bộ lọc này để điều chỉnh url trả về bởi hàm get_rest_url().
	 *
	 * @since 4.4.0
	 *
	 * @param string   $url     URL REST.
	 * @param string   $path    Route REST.
	 * @param int|null $blog_id ID blog.
	 * @param string   $scheme  Kiểu làm sạch.
	 */
	return apply_filters( 'rest_url', $url, $path, $blog_id, $scheme );
}

/**
 * Lấy URL đến endpoint REST.
 *
 * Lưu ý: URL trả về KHÔNG được escape.
 *
 * @since 4.4.0
 *
 * @param string $path   Tùy chọn. Route REST. Mặc định rỗng.
 * @param string $scheme Tùy chọn. Kiểu làm sạch. Mặc định 'rest'.
 * @return string URL đầy đủ đến endpoint.
 */
function rest_url( $path = '', $scheme = 'rest' ) {
	return get_rest_url( null, $path, $scheme );
}

/**
 * Thực hiện một yêu cầu REST.
 *
 * Chủ yếu được sử dụng để định tuyến các yêu cầu nội bộ qua WP_REST_Server.
 *
 * @since 4.4.0
 *
 * @param WP_REST_Request|string $request Yêu cầu.
 * @return WP_REST_Response Phản hồi REST.
 */
function rest_do_request( $request ) {
	$request = rest_ensure_request( $request );
	return rest_get_server()->dispatch( $request );
}

/**
 * Lấy thực thể máy chủ REST hiện tại.
 *
 * Khởi tạo thực thể mới nếu chưa tồn tại.
 *
 * @since 4.5.0
 *
 * @global WP_REST_Server $wp_rest_server Thực thể máy chủ REST.
 *
 * @return WP_REST_Server Thực thể máy chủ REST.
 */
function rest_get_server() {
	/* @var WP_REST_Server $wp_rest_server */
	global $wp_rest_server;

	if ( empty( $wp_rest_server ) ) {
		/**
		 * Lọc lớp máy chủ REST.
		 *
		 * Bộ lọc này cho phép bạn thay đổi lớp máy chủ được sử dụng bởi REST API, sử dụng một
		 * lớp khác để xử lý các yêu cầu.
		 *
		 * @since 4.4.0
		 *
		 * @param string $class_name Tên lớp máy chủ. Mặc định 'WP_REST_Server'.
		 */
		$wp_rest_server_class = apply_filters( 'wp_rest_server_class', 'WP_REST_Server' );
		$wp_rest_server       = new $wp_rest_server_class();

		/**
		 * Kích hoạt khi chuẩn bị phục vụ yêu cầu REST API.
		 *
		 * Các đối tượng endpoint nên được tạo và đăng ký hook của chúng trên action này thay vì
		 * action khác để đảm bảo chúng chỉ được tải khi cần thiết.
		 *
		 * @since 4.4.0
		 *
		 * @param WP_REST_Server $wp_rest_server Đối tượng máy chủ.
		 */
		do_action( 'rest_api_init', $wp_rest_server );
	}

	return $wp_rest_server;
}

/**
 * Đảm bảo các tham số yêu cầu là một đối tượng yêu cầu (để nhất quán).
 *
 * @since 4.4.0
 * @since 5.3.0 Chấp nhận tham số chuỗi cho đường dẫn yêu cầu.
 *
 * @param array|string|WP_REST_Request $request Yêu cầu cần kiểm tra.
 * @return WP_REST_Request Thực thể yêu cầu REST.
 */
function rest_ensure_request( $request ) {
	if ( $request instanceof WP_REST_Request ) {
		return $request;
	}

	if ( is_string( $request ) ) {
		return new WP_REST_Request( 'GET', $request );
	}

	return new WP_REST_Request( 'GET', '', $request );
}

/**
 * Đảm bảo phản hồi REST là một đối tượng phản hồi (để nhất quán).
 *
 * Hàm này triển khai WP_REST_Response, cho phép sử dụng `set_status`/`header`/v.v.
 * mà không cần kiểm tra lại đối tượng. Cũng cho phép WP_Error chỉ ra phản hồi lỗi,
 * nên người dùng cần kiểm tra giá trị này ngay lập tức.
 *
 * @since 4.4.0
 *
 * @param WP_REST_Response|WP_Error|WP_HTTP_Response|mixed $response Phản hồi cần kiểm tra.
 * @return WP_REST_Response|WP_Error Nếu phản hồi tạo ra lỗi, WP_Error, nếu phản hồi
 *                                   đã là thực thể, WP_REST_Response, ngược lại
 *                                   trả về thực thể WP_REST_Response mới.
 */
function rest_ensure_response( $response ) {
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( $response instanceof WP_REST_Response ) {
		return $response;
	}

	/*
	 * Mặc dù WP_HTTP_Response là lớp cơ sở của WP_REST_Response, nó không cung cấp
	 * tất cả các phương thức cần thiết được sử dụng trong WP_REST_Server::dispatch().
	 */
	if ( $response instanceof WP_HTTP_Response ) {
		return new WP_REST_Response(
			$response->get_data(),
			$response->get_status(),
			$response->get_headers()
		);
	}

	return new WP_REST_Response( $response );
}

/**
 * Xử lý các lỗi _deprecated_function().
 *
 * @since 4.4.0
 *
 * @param string $function_name Hàm đã được gọi.
 * @param string $replacement   Hàm nên được gọi thay thế.
 * @param string $version       Phiên bản.
 */
function rest_handle_deprecated_function( $function_name, $replacement, $version ) {
	if ( ! WP_DEBUG || headers_sent() ) {
		return;
	}
	if ( ! empty( $replacement ) ) {
		/* translators: 1: Function name, 2: WordPress version number, 3: New function name. */
		$string = sprintf( __( '%1$s (since %2$s; use %3$s instead)' ), $function_name, $version, $replacement );
	} else {
		/* translators: 1: Function name, 2: WordPress version number. */
		$string = sprintf( __( '%1$s (since %2$s; no alternative available)' ), $function_name, $version );
	}

	header( sprintf( 'X-WP-DeprecatedFunction: %s', $string ) );
}

/**
 * Xử lý các lỗi _deprecated_argument().
 *
 * @since 4.4.0
 *
 * @param string $function_name Hàm đã được gọi.
 * @param string $message       Thông điệp về sự thay đổi.
 * @param string $version       Phiên bản.
 */
function rest_handle_deprecated_argument( $function_name, $message, $version ) {
	if ( ! WP_DEBUG || headers_sent() ) {
		return;
	}
	if ( $message ) {
		/* translators: 1: Function name, 2: WordPress version number, 3: Error message. */
		$string = sprintf( __( '%1$s (since %2$s; %3$s)' ), $function_name, $version, $message );
	} else {
		/* translators: 1: Function name, 2: WordPress version number. */
		$string = sprintf( __( '%1$s (since %2$s; no alternative available)' ), $function_name, $version );
	}

	header( sprintf( 'X-WP-DeprecatedParam: %s', $string ) );
}

/**
 * Xử lý các lỗi _doing_it_wrong.
 *
 * @since 5.5.0
 *
 * @param string      $function_name Hàm đã được gọi.
 * @param string      $message       Thông điệp giải thích những gì đã được thực hiện không đúng.
 * @param string|null $version       Phiên bản WordPress khi thông điệp được thêm vào.
 */
function rest_handle_doing_it_wrong( $function_name, $message, $version ) {
	if ( ! WP_DEBUG || headers_sent() ) {
		return;
	}

	if ( $version ) {
		/* translators: Developer debugging message. 1: PHP function name, 2: WordPress version number, 3: Explanatory message. */
		$string = __( '%1$s (since %2$s; %3$s)' );
		$string = sprintf( $string, $function_name, $version, $message );
	} else {
		/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message. */
		$string = __( '%1$s (%2$s)' );
		$string = sprintf( $string, $function_name, $message );
	}

	header( sprintf( 'X-WP-DoingItWrong: %s', $string ) );
}

/**
 * Gửi header Chia sẻ Tài nguyên Đa Nguồn gốc (CORS) cùng với các yêu cầu API.
 *
 * @since 4.4.0
 *
 * @param mixed $value Dữ liệu phản hồi.
 * @return mixed Dữ liệu phản hồi.
 */
function rest_send_cors_headers( $value ) {
	$origin = get_http_origin();

	if ( $origin ) {
		// Các yêu cầu từ URL file:// và data: gửi "Origin: null".
		if ( 'null' !== $origin ) {
			$origin = sanitize_url( $origin );
		}
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Vary: Origin', false );
	} elseif ( ! headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && ! is_user_logged_in() ) {
		header( 'Vary: Origin', false );
	}

	return $value;
}

/**
 * Xử lý các yêu cầu OPTIONS cho máy chủ.
 *
 * Điều này được xử lý bên ngoài mã máy chủ, vì nó không tuân theo ánh xạ
 * route thông thường.
 *
 * @since 4.4.0
 *
 * @param mixed           $response Phản hồi hiện tại, có thể là phản hồi hoặc `null` để cho biết chuyển tiếp.
 * @param WP_REST_Server  $handler  Thể hiện ResponseHandler (thường là WP_REST_Server).
 * @param WP_REST_Request $request  Yêu cầu đã được sử dụng để tạo phản hồi hiện tại.
 * @return WP_REST_Response Phản hồi đã sửa đổi, có thể là phản hồi hoặc `null` để cho biết chuyển tiếp.
 */
function rest_handle_options_request( $response, $handler, $request ) {
	if ( ! empty( $response ) || $request->get_method() !== 'OPTIONS' ) {
		return $response;
	}

	$response = new WP_REST_Response();
	$data     = array();

	foreach ( $handler->get_routes() as $route => $endpoints ) {
		$match = preg_match( '@^' . $route . '$@i', $request->get_route(), $matches );

		if ( ! $match ) {
			continue;
		}

		$args = array();
		foreach ( $matches as $param => $value ) {
			if ( ! is_int( $param ) ) {
				$args[ $param ] = $value;
			}
		}

		foreach ( $endpoints as $endpoint ) {
			$request->set_url_params( $args );
			$request->set_attributes( $endpoint );
		}

		$data = $handler->get_data_for_route( $route, $endpoints, 'help' );
		$response->set_matched_route( $route );
		break;
	}

	$response->set_data( $data );
	return $response;
}

/**
 * Gửi header "Allow" để nêu tất cả các phương thức có thể gửi đến route hiện tại.
 *
 * @since 4.4.0
 *
 * @param WP_REST_Response $response Phản hồi hiện tại đang được phục vụ.
 * @param WP_REST_Server   $server   Thể hiện ResponseHandler (thường là WP_REST_Server).
 * @param WP_REST_Request  $request  Yêu cầu đã được sử dụng để tạo phản hồi hiện tại.
 * @return WP_REST_Response Phản hồi sẽ được phục vụ, với header "Allow" nếu route có các phương thức được phép.
 */
function rest_send_allow_header( $response, $server, $request ) {
	$matched_route = $response->get_matched_route();

	if ( ! $matched_route ) {
		return $response;
	}

	$routes = $server->get_routes();

	$allowed_methods = array();

	// Lấy các phương thức được phép trên tất cả các route.
	foreach ( $routes[ $matched_route ] as $_handler ) {
		foreach ( $_handler['methods'] as $handler_method => $value ) {

			if ( ! empty( $_handler['permission_callback'] ) ) {

				$permission = call_user_func( $_handler['permission_callback'], $request );

				$allowed_methods[ $handler_method ] = true === $permission;
			} else {
				$allowed_methods[ $handler_method ] = true;
			}
		}
	}

	// Loại bỏ tất cả các phương thức không được phép (giá trị false).
	$allowed_methods = array_filter( $allowed_methods );

	if ( $allowed_methods ) {
		$response->header( 'Allow', implode( ', ', array_map( 'strtoupper', array_keys( $allowed_methods ) ) ) );
	}

	return $response;
}

/**
 * Tính toán đệ quy phần giao của các mảng sử dụng khóa để so sánh.
 *
 * @since 5.3.0
 *
 * @param array $array1 Mảng với các khóa chính để kiểm tra.
 * @param array $array2 Mảng để so sánh khóa.
 * @return array Mảng kết hợp chứa tất cả các mục của array1 có khóa
 *               xuất hiện trong tất cả các tham số.
 */
function _rest_array_intersect_key_recursive( $array1, $array2 ) {
	$array1 = array_intersect_key( $array1, $array2 );
	foreach ( $array1 as $key => $value ) {
		if ( is_array( $value ) && is_array( $array2[ $key ] ) ) {
			$array1[ $key ] = _rest_array_intersect_key_recursive( $value, $array2[ $key ] );
		}
	}
	return $array1;
}

/**
 * Lọc phản hồi REST API để chỉ bao gồm một tập hợp các trường đối tượng phản hồi trong danh sách cho phép.
 *
 * @since 4.8.0
 *
 * @param WP_REST_Response $response Phản hồi hiện tại đang được phục vụ.
 * @param WP_REST_Server   $server   Thể hiện ResponseHandler (thường là WP_REST_Server).
 * @param WP_REST_Request  $request  Yêu cầu đã được sử dụng để tạo phản hồi hiện tại.
 * @return WP_REST_Response Phản hồi sẽ được phục vụ, đã được cắt giảm chỉ chứa một tập con các trường.
 */
function rest_filter_response_fields( $response, $server, $request ) {
	if ( ! isset( $request['_fields'] ) || $response->is_error() ) {
		return $response;
	}

	$data = $response->get_data();

	$fields = wp_parse_list( $request['_fields'] );

	if ( 0 === count( $fields ) ) {
		return $response;
	}

	// Loại bỏ khoảng trắng bên ngoài từ danh sách phân cách bằng dấu phẩy.
	$fields = array_map( 'trim', $fields );

	// Tạo mảng lồng nhau của hệ thống phân cấp trường được chấp nhận.
	$fields_as_keyed = array();
	foreach ( $fields as $field ) {
		$parts = explode( '.', $field );
		$ref   = &$fields_as_keyed;
		while ( count( $parts ) > 1 ) {
			$next = array_shift( $parts );
			if ( isset( $ref[ $next ] ) && true === $ref[ $next ] ) {
				// Bỏ qua các thuộc tính con nếu thuộc tính cha đã được đánh dấu để bao gồm.
				break 2;
			}
			$ref[ $next ] = isset( $ref[ $next ] ) ? $ref[ $next ] : array();
			$ref          = &$ref[ $next ];
		}
		$last         = array_shift( $parts );
		$ref[ $last ] = true;
	}

	if ( wp_is_numeric_array( $data ) ) {
		$new_data = array();
		foreach ( $data as $item ) {
			$new_data[] = _rest_array_intersect_key_recursive( $item, $fields_as_keyed );
		}
	} else {
		$new_data = _rest_array_intersect_key_recursive( $data, $fields_as_keyed );
	}

	$response->set_data( $new_data );

	return $response;
}

/**
 * Với một mảng các trường cần bao gồm trong phản hồi, một số trong đó có thể là
 * `trường.lồng_nhau`, xác định xem trường được cung cấp có nên được bao gồm
 * trong phần thân phản hồi hay không.
 *
 * Nếu một trường cha được truyền vào, sự hiện diện của bất kỳ trường lồng nhau nào trong
 * trường cha đó sẽ khiến phương thức trả về `true`. Ví dụ "title"
 * sẽ trả về true nếu bất kỳ `title`, `title.raw` hoặc `title.rendered` nào
 * được cung cấp.
 *
 * @since 5.3.0
 *
 * @param string $field  Một trường để kiểm tra có bao gồm trong phần thân phản hồi hay không.
 * @param array  $fields Mảng các trường chuỗi được hỗ trợ bởi endpoint.
 * @return bool Có bao gồm trường hay không.
 */
function rest_is_field_included( $field, $fields ) {
	if ( in_array( $field, $fields, true ) ) {
		return true;
	}

	foreach ( $fields as $accepted_field ) {
		/*
		 * Kiểm tra xem $field có phải là trường cha của bất kỳ mục nào trong $fields không.
		 * Trường "parent" nên được chấp nhận nếu "parent.child" được chấp nhận.
		 */
		if ( str_starts_with( $accepted_field, "$field." ) ) {
			return true;
		}
		/*
		 * Ngược lại, nếu "parent" được chấp nhận, tất cả các trường "parent.child"
		 * cũng nên được chấp nhận.
		 */
		if ( str_starts_with( $field, "$accepted_field." ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Thêm URL REST API vào endpoint WP RSD.
 *
 * @since 4.4.0
 *
 * @see get_rest_url()
 */
function rest_output_rsd() {
	$api_root = get_rest_url();

	if ( empty( $api_root ) ) {
		return;
	}
	?>
	<api name="WP-API" blogID="1" preferred="false" apiLink="<?php echo esc_url( $api_root ); ?>" />
	<?php
}

/**
 * Xuất thẻ liên kết REST API vào header trang.
 *
 * @since 4.4.0
 *
 * @see get_rest_url()
 */
function rest_output_link_wp_head() {
	$api_root = get_rest_url();

	if ( empty( $api_root ) ) {
		return;
	}

	printf( '<link rel="https://api.w.org/" href="%s" />', esc_url( $api_root ) );

	$resource = rest_get_queried_resource_route();

	if ( $resource ) {
		printf(
			'<link rel="alternate" title="%1$s" type="application/json" href="%2$s" />',
			_x( 'JSON', 'REST API resource link name' ),
			esc_url( rest_url( $resource ) )
		);
	}
}

/**
 * Gửi header Link cho REST API.
 *
 * @since 4.4.0
 */
function rest_output_link_header() {
	if ( headers_sent() ) {
		return;
	}

	$api_root = get_rest_url();

	if ( empty( $api_root ) ) {
		return;
	}

	header( sprintf( 'Link: <%s>; rel="https://api.w.org/"', sanitize_url( $api_root ) ), false );

	$resource = rest_get_queried_resource_route();

	if ( $resource ) {
		header(
			sprintf(
				'Link: <%1$s>; rel="alternate"; title="%2$s"; type="application/json"',
				sanitize_url( rest_url( $resource ) ),
				_x( 'JSON', 'REST API resource link name' )
			),
			false
		);
	}
}

/**
 * Kiểm tra lỗi khi sử dụng xác thực dựa trên cookie.
 *
 * Xác thực cookie tích hợp của WordPress luôn hoạt động
 * cho người dùng đã đăng nhập. Tuy nhiên, API phải kiểm tra nonce
 * cho mỗi yêu cầu để đảm bảo người dùng không bị tấn công CSRF.
 *
 * @since 4.4.0
 *
 * @global mixed          $wp_rest_auth_cookie
 *
 * @param WP_Error|mixed $result Lỗi từ trình xử lý xác thực khác,
 *                               null nếu chúng ta nên xử lý, hoặc giá trị khác nếu không.
 * @return WP_Error|mixed|bool WP_Error nếu cookie không hợp lệ, $result, hoặc true.
 */
function rest_cookie_check_errors( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	global $wp_rest_auth_cookie;

	/*
	 * Xác thực cookie có đang được sử dụng không? (Nếu chúng ta nhận được lỗi
	 * xác thực, nhưng vẫn đang đăng nhập, thì một phương thức xác thực
	 * khác phải đã được sử dụng).
	 */
	if ( true !== $wp_rest_auth_cookie && is_user_logged_in() ) {
		return $result;
	}

	// Xác định xem có nonce hay không.
	$nonce = null;

	if ( isset( $_REQUEST['_wpnonce'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
	} elseif ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
		$nonce = $_SERVER['HTTP_X_WP_NONCE'];
	}

	if ( null === $nonce ) {
		// Hoàn toàn không có nonce, nên xử lý như một yêu cầu chưa xác thực.
		wp_set_current_user( 0 );
		return true;
	}

	// Kiểm tra nonce.
	$result = wp_verify_nonce( $nonce, 'wp_rest' );

	if ( ! $result ) {
		add_filter( 'rest_send_nocache_headers', '__return_true', 20 );
		return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Cookie check failed' ), array( 'status' => 403 ) );
	}

	// Gửi nonce đã làm mới trong header.
	rest_get_server()->send_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

	return true;
}

/**
 * Thu thập trạng thái xác thực cookie.
 *
 * Thu thập các lỗi từ wp_validate_auth_cookie để sử dụng bởi rest_cookie_check_errors.
 *
 * @since 4.4.0
 *
 * @see current_action()
 * @global mixed $wp_rest_auth_cookie
 */
function rest_cookie_collect_status() {
	global $wp_rest_auth_cookie;

	$status_type = current_action();

	if ( 'auth_cookie_valid' !== $status_type ) {
		$wp_rest_auth_cookie = substr( $status_type, 12 );
		return;
	}

	$wp_rest_auth_cookie = true;
}

/**
 * Thu thập trạng thái xác thực bằng mật khẩu ứng dụng.
 *
 * @since 5.6.0
 * @since 5.7.0 Thêm tham số `$app_password`.
 *
 * @global WP_User|WP_Error|null $wp_rest_application_password_status
 * @global string|null $wp_rest_application_password_uuid
 *
 * @param WP_Error $user_or_error Người dùng đã xác thực hoặc thể hiện lỗi.
 * @param array    $app_password  Mật khẩu Ứng dụng được sử dụng để xác thực.
 */
function rest_application_password_collect_status( $user_or_error, $app_password = array() ) {
	global $wp_rest_application_password_status, $wp_rest_application_password_uuid;

	$wp_rest_application_password_status = $user_or_error;

	if ( empty( $app_password['uuid'] ) ) {
		$wp_rest_application_password_uuid = null;
	} else {
		$wp_rest_application_password_uuid = $app_password['uuid'];
	}
}

/**
 * Lấy Mật khẩu Ứng dụng được sử dụng để xác thực yêu cầu.
 *
 * @since 5.7.0
 *
 * @global string|null $wp_rest_application_password_uuid
 *
 * @return string|null UUID của Mật khẩu Ứng dụng, hoặc null nếu Mật khẩu Ứng dụng không được sử dụng.
 */
function rest_get_authenticated_app_password() {
	global $wp_rest_application_password_uuid;

	return $wp_rest_application_password_uuid;
}

/**
 * Kiểm tra lỗi khi sử dụng xác thực dựa trên mật khẩu ứng dụng.
 *
 * @since 5.6.0
 *
 * @global WP_User|WP_Error|null $wp_rest_application_password_status
 *
 * @param WP_Error|null|true $result Lỗi từ trình xử lý xác thực khác,
 *                                   null nếu chúng ta nên xử lý, hoặc giá trị khác nếu không.
 * @return WP_Error|null|true WP_Error nếu mật khẩu ứng dụng không hợp lệ, $result, hoặc true.
 */
function rest_application_password_check_errors( $result ) {
	global $wp_rest_application_password_status;

	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( is_wp_error( $wp_rest_application_password_status ) ) {
		$data = $wp_rest_application_password_status->get_error_data();

		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 401;
		}

		$wp_rest_application_password_status->add_data( $data );

		return $wp_rest_application_password_status;
	}

	if ( $wp_rest_application_password_status instanceof WP_User ) {
		return true;
	}

	return $result;
}

/**
 * Thêm thông tin Mật khẩu Ứng dụng vào chỉ mục REST API.
 *
 * @since 5.6.0
 *
 * @param WP_REST_Response $response Đối tượng phản hồi chỉ mục.
 * @return WP_REST_Response
 */
function rest_add_application_passwords_to_index( $response ) {
	if ( ! wp_is_application_passwords_available() ) {
		return $response;
	}

	$response->data['authentication']['application-passwords'] = array(
		'endpoints' => array(
			'authorization' => admin_url( 'authorize-application.php' ),
		),
	);

	return $response;
}

/**
 * Lấy các URL ảnh đại diện ở nhiều kích thước khác nhau.
 *
 * @since 4.7.0
 *
 * @see get_avatar_url()
 *
 * @param mixed $id_or_email Ảnh đại diện cần lấy URL. Chấp nhận ID người dùng, mã MD5 Gravatar,
 *                           email người dùng, đối tượng WP_User, đối tượng WP_Post, hoặc đối tượng WP_Comment.
 * @return (string|false)[] Các URL ảnh đại diện được đánh khóa theo kích thước. Mỗi giá trị có thể là chuỗi URL hoặc boolean false.
 */
function rest_get_avatar_urls( $id_or_email ) {
	$avatar_sizes = rest_get_avatar_sizes();

	$urls = array();
	foreach ( $avatar_sizes as $size ) {
		$urls[ $size ] = get_avatar_url( $id_or_email, array( 'size' => $size ) );
	}

	return $urls;
}

/**
 * Lấy các kích thước pixel cho ảnh đại diện.
 *
 * @since 4.7.0
 *
 * @return int[] Danh sách kích thước pixel cho ảnh đại diện. Mặc định `[ 24, 48, 96 ]`.
 */
function rest_get_avatar_sizes() {
	/**
	 * Lọc các kích thước ảnh đại diện REST.
	 *
	 * Sử dụng bộ lọc này để điều chỉnh mảng kích thước được trả về bởi
	 * hàm `rest_get_avatar_sizes`.
	 *
	 * @since 4.4.0
	 *
	 * @param int[] $sizes Mảng các giá trị int là kích thước pixel cho ảnh đại diện.
	 *                     Mặc định `[ 24, 48, 96 ]`.
	 */
	return apply_filters( 'rest_avatar_sizes', array( 24, 48, 96 ) );
}

/**
 * Phân tích thời gian RFC3339 thành dấu thời gian Unix.
 *
 * Kiểm tra rõ ràng `false` để phát hiện lỗi, vì zero là giá trị trả về
 * hợp lệ khi thành công.
 *
 * @since 4.4.0
 *
 * @param string $date      Dấu thời gian RFC3339.
 * @param bool   $force_utc Tùy chọn. Có buộc sử dụng múi giờ UTC thay vì sử dụng
 *                          múi giờ của dấu thời gian hay không. Mặc định false.
 * @return int|false Dấu thời gian Unix khi thành công, false khi thất bại.
 */
function rest_parse_date( $date, $force_utc = false ) {
	if ( $force_utc ) {
		$date = preg_replace( '/[+-]\d+:?\d+$/', '+00:00', $date );
	}

	$regex = '#^\d{4}-\d{2}-\d{2}[Tt ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}(?::\d{2})?)?$#';

	if ( ! preg_match( $regex, $date, $matches ) ) {
		return false;
	}

	return strtotime( $date );
}

/**
 * Phân tích mã màu hex 3 hoặc 6 ký tự (có #).
 *
 * @since 5.4.0
 *
 * @param string $color Mã màu hex 3 hoặc 6 ký tự (có #).
 * @return string|false Giá trị màu khi thành công, false khi thất bại.
 */
function rest_parse_hex_color( $color ) {
	$regex = '|^#([A-Fa-f0-9]{3}){1,2}$|';
	if ( ! preg_match( $regex, $color, $matches ) ) {
		return false;
	}

	return $color;
}

/**
 * Phân tích ngày tháng thành cả dạng nội địa và UTC tương đương, theo định dạng datetime MySQL.
 *
 * @since 4.4.0
 *
 * @see rest_parse_date()
 *
 * @param string $date   Dấu thời gian RFC3339.
 * @param bool   $is_utc Có nên diễn giải ngày được cung cấp là UTC hay không. Mặc định false.
 * @return array|null {
 *     Các chuỗi datetime nội địa và UTC, theo định dạng datetime MySQL (Y-m-d H:i:s),
 *     null khi thất bại.
 *
 *     @type string $0 Chuỗi datetime nội địa.
 *     @type string $1 Chuỗi datetime UTC.
 * }
 */
function rest_get_date_with_gmt( $date, $is_utc = false ) {
	/*
	 * Việc ngày gốc có thực sự chứa chuỗi múi giờ hay không
	 * sẽ thay đổi cách chúng ta cần thực hiện chuyển đổi múi giờ.
	 * Lưu thông tin này trước khi phân tích ngày, và sử dụng sau.
	 */
	$has_timezone = preg_match( '#(Z|[+-]\d{2}(:\d{2})?)$#', $date );

	$date = rest_parse_date( $date );

	if ( false === $date ) {
		return null;
	}

	/*
	 * Tại thời điểm này $date có thể là ngày nội địa (nếu chúng ta được truyền
	 * một ngày *nội địa* không có độ lệch múi giờ) hoặc ngày UTC (trường hợp khác).
	 * Chuyển đổi múi giờ cần được xử lý khác nhau giữa hai trường hợp này.
	 */
	if ( ! $is_utc && ! $has_timezone ) {
		$local = gmdate( 'Y-m-d H:i:s', $date );
		$utc   = get_gmt_from_date( $local );
	} else {
		$utc   = gmdate( 'Y-m-d H:i:s', $date );
		$local = get_date_from_gmt( $utc );
	}

	return array( $local, $utc );
}

/**
 * Trả về mã lỗi HTTP theo ngữ cảnh cho lỗi ủy quyền.
 *
 * @since 4.7.0
 *
 * @return int 401 nếu người dùng chưa đăng nhập, 403 nếu người dùng đã đăng nhập.
 */
function rest_authorization_required_code() {
	return is_user_logged_in() ? 403 : 401;
}

/**
 * Xác thực một tham số yêu cầu dựa trên chi tiết đã đăng ký cho route.
 *
 * @since 4.7.0
 *
 * @param mixed           $value
 * @param WP_REST_Request $request
 * @param string          $param
 * @return true|WP_Error
 */
function rest_validate_request_arg( $value, $request, $param ) {
	$attributes = $request->get_attributes();
	if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
		return true;
	}
	$args = $attributes['args'][ $param ];

	return rest_validate_value_from_schema( $value, $args, $param );
}

/**
 * Làm sạch một tham số yêu cầu dựa trên chi tiết đã đăng ký cho route.
 *
 * @since 4.7.0
 *
 * @param mixed           $value
 * @param WP_REST_Request $request
 * @param string          $param
 * @return mixed
 */
function rest_sanitize_request_arg( $value, $request, $param ) {
	$attributes = $request->get_attributes();
	if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
		return $value;
	}
	$args = $attributes['args'][ $param ];

	return rest_sanitize_value_from_schema( $value, $args, $param );
}

/**
 * Phân tích một tham số yêu cầu dựa trên chi tiết đã đăng ký cho route.
 *
 * Chạy kiểm tra xác thực và làm sạch giá trị, chủ yếu được sử dụng thông qua
 * tham số `sanitize_callback` trong đăng ký args của endpoint.
 *
 * @since 4.7.0
 *
 * @param mixed           $value
 * @param WP_REST_Request $request
 * @param string          $param
 * @return mixed
 */
function rest_parse_request_arg( $value, $request, $param ) {
	$is_valid = rest_validate_request_arg( $value, $request, $param );

	if ( is_wp_error( $is_valid ) ) {
		return $is_valid;
	}

	$value = rest_sanitize_request_arg( $value, $request, $param );

	return $value;
}

/**
 * Xác định xem một địa chỉ IP có hợp lệ hay không.
 *
 * Xử lý cả địa chỉ IPv4 và IPv6.
 *
 * @since 4.7.0
 *
 * @param string $ip Địa chỉ IP.
 * @return string|false Địa chỉ IP hợp lệ, ngược lại false.
 */
function rest_is_ip_address( $ip ) {
	$ipv4_pattern = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';

	if ( ! preg_match( $ipv4_pattern, $ip ) && ! WpOrg\Requests\Ipv6::check_ipv6( $ip ) ) {
		return false;
	}

	return $ip;
}

/**
 * Chuyển đổi giá trị giống boolean thành giá trị boolean đúng.
 *
 * @since 4.7.0
 *
 * @param bool|string|int $value Giá trị đang được đánh giá.
 * @return bool Trả về giá trị boolean tương ứng đúng.
 */
function rest_sanitize_boolean( $value ) {
	// Giá trị chuỗi được chuyển thành `true`; đảm bảo 'false' là false.
	if ( is_string( $value ) ) {
		$value = strtolower( $value );
		if ( in_array( $value, array( 'false', '0' ), true ) ) {
			$value = false;
		}
	}

	// Mọi thứ khác sẽ ánh xạ tốt sang boolean.
	return (bool) $value;
}

/**
 * Xác định xem một giá trị cho trước có giống boolean hay không.
 *
 * @since 4.7.0
 *
 * @param bool|string $maybe_bool Giá trị đang được đánh giá.
 * @return bool True nếu là boolean, ngược lại false.
 */
function rest_is_boolean( $maybe_bool ) {
	if ( is_bool( $maybe_bool ) ) {
		return true;
	}

	if ( is_string( $maybe_bool ) ) {
		$maybe_bool = strtolower( $maybe_bool );

		$valid_boolean_values = array(
			'false',
			'true',
			'0',
			'1',
		);

		return in_array( $maybe_bool, $valid_boolean_values, true );
	}

	if ( is_int( $maybe_bool ) ) {
		return in_array( $maybe_bool, array( 0, 1 ), true );
	}

	return false;
}

/**
 * Xác định xem một giá trị cho trước có giống số nguyên hay không.
 *
 * @since 5.5.0
 *
 * @param mixed $maybe_integer Giá trị đang được đánh giá.
 * @return bool True nếu là số nguyên, ngược lại false.
 */
function rest_is_integer( $maybe_integer ) {
	return is_numeric( $maybe_integer ) && round( (float) $maybe_integer ) === (float) $maybe_integer;
}

/**
 * Xác định xem một giá trị cho trước có giống mảng hay không.
 *
 * @since 5.5.0
 *
 * @param mixed $maybe_array Giá trị đang được đánh giá.
 * @return bool
 */
function rest_is_array( $maybe_array ) {
	if ( is_scalar( $maybe_array ) ) {
		$maybe_array = wp_parse_list( $maybe_array );
	}

	return wp_is_numeric_array( $maybe_array );
}

/**
 * Chuyển đổi giá trị giống mảng thành mảng.
 *
 * @since 5.5.0
 *
 * @param mixed $maybe_array Giá trị đang được đánh giá.
 * @return array Trả về mảng được trích xuất từ giá trị.
 */
function rest_sanitize_array( $maybe_array ) {
	if ( is_scalar( $maybe_array ) ) {
		return wp_parse_list( $maybe_array );
	}

	if ( ! is_array( $maybe_array ) ) {
		return array();
	}

	// Chuẩn hóa thành mảng số để không có gì không mong muốn trong các khóa.
	return array_values( $maybe_array );
}

/**
 * Xác định xem một giá trị cho trước có giống đối tượng hay không.
 *
 * @since 5.5.0
 *
 * @param mixed $maybe_object Giá trị đang được đánh giá.
 * @return bool True nếu giống đối tượng, ngược lại false.
 */
function rest_is_object( $maybe_object ) {
	if ( '' === $maybe_object ) {
		return true;
	}

	if ( $maybe_object instanceof stdClass ) {
		return true;
	}

	if ( $maybe_object instanceof JsonSerializable ) {
		$maybe_object = $maybe_object->jsonSerialize();
	}

	return is_array( $maybe_object );
}

/**
 * Chuyển đổi giá trị giống đối tượng thành mảng.
 *
 * @since 5.5.0
 *
 * @param mixed $maybe_object Giá trị đang được đánh giá.
 * @return array Trả về đối tượng được trích xuất từ giá trị dưới dạng mảng kết hợp.
 */
function rest_sanitize_object( $maybe_object ) {
	if ( '' === $maybe_object ) {
		return array();
	}

	if ( $maybe_object instanceof stdClass ) {
		return (array) $maybe_object;
	}

	if ( $maybe_object instanceof JsonSerializable ) {
		$maybe_object = $maybe_object->jsonSerialize();
	}

	if ( ! is_array( $maybe_object ) ) {
		return array();
	}

	return $maybe_object;
}

/**
 * Lấy kiểu tốt nhất cho một giá trị.
 *
 * @since 5.5.0
 *
 * @param mixed    $value Giá trị cần kiểm tra.
 * @param string[] $types Danh sách các kiểu có thể.
 * @return string Kiểu khớp tốt nhất, chuỗi rỗng nếu không có kiểu nào khớp.
 */
function rest_get_best_type_for_value( $value, $types ) {
	static $checks = array(
		'array'   => 'rest_is_array',
		'object'  => 'rest_is_object',
		'integer' => 'rest_is_integer',
		'number'  => 'is_numeric',
		'boolean' => 'rest_is_boolean',
		'string'  => 'is_string',
		'null'    => 'is_null',
	);

	/*
	 * Cả mảng và đối tượng đều cho phép chuỗi rỗng được chuyển đổi sang kiểu của chúng.
	 * Nhưng câu trả lời tốt nhất cho kiểu này là chuỗi.
	 */
	if ( '' === $value && in_array( 'string', $types, true ) ) {
		return 'string';
	}

	foreach ( $types as $type ) {
		if ( isset( $checks[ $type ] ) && $checks[ $type ]( $value ) ) {
			return $type;
		}
	}

	return '';
}

/**
 * Xử lý việc lấy kiểu tốt nhất cho schema đa kiểu.
 *
 * Đây là bao bọc cho {@see rest_get_best_type_for_value()} xử lý
 * tương thích ngược cho các schema sử dụng kiểu không hợp lệ.
 *
 * @since 5.5.0
 *
 * @param mixed  $value Giá trị cần kiểm tra.
 * @param array  $args  Mảng schema để sử dụng.
 * @param string $param Tên tham số, được sử dụng trong thông báo lỗi.
 * @return string
 */
function rest_handle_multi_type_schema( $value, $args, $param = '' ) {
	$allowed_types = array( 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' );
	$invalid_types = array_diff( $args['type'], $allowed_types );

	if ( $invalid_types ) {
		_doing_it_wrong(
			__FUNCTION__,
			/* translators: 1: Parameter, 2: List of allowed types. */
			wp_sprintf( __( 'The "type" schema keyword for %1$s can only contain the built-in types: %2$l.' ), $param, $allowed_types ),
			'5.5.0'
		);
	}

	$best_type = rest_get_best_type_for_value( $value, $args['type'] );

	if ( ! $best_type ) {
		if ( ! $invalid_types ) {
			return '';
		}

		// Tương thích ngược với hành vi trước đó cho phép giá trị nếu kiểu không hợp lệ được sử dụng.
		$best_type = reset( $invalid_types );
	}

	return $best_type;
}

/**
 * Kiểm tra xem một mảng có chứa các phần tử duy nhất hay không.
 *
 * @since 5.5.0
 *
 * @param array $input_array Mảng cần kiểm tra.
 * @return bool True nếu mảng chứa các phần tử duy nhất, ngược lại false.
 */
function rest_validate_array_contains_unique_items( $input_array ) {
	$seen = array();

	foreach ( $input_array as $item ) {
		$stabilized = rest_stabilize_value( $item );
		$key        = serialize( $stabilized );

		if ( ! isset( $seen[ $key ] ) ) {
			$seen[ $key ] = true;

			continue;
		}

		return false;
	}

	return true;
}

/**
 * Ổn định một giá trị theo ngữ nghĩa JSON Schema.
 *
 * Với danh sách, thứ tự được giữ nguyên. Với đối tượng, các thuộc tính được sắp xếp lại theo thứ tự bảng chữ cái.
 *
 * @since 5.5.0
 *
 * @param mixed $value Giá trị cần ổn định. Phải đã được làm sạch. Đối tượng nên đã được chuyển đổi thành mảng.
 * @return mixed Giá trị đã được ổn định.
 */
function rest_stabilize_value( $value ) {
	if ( is_scalar( $value ) || is_null( $value ) ) {
		return $value;
	}

	if ( is_object( $value ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Cannot stabilize objects. Convert the object to an array first.' ), '5.5.0' );

		return $value;
	}

	ksort( $value );

	foreach ( $value as $k => $v ) {
		$value[ $k ] = rest_stabilize_value( $v );
	}

	return $value;
}

/**
 * Xác thực xem mẫu JSON Schema có khớp với một giá trị hay không.
 *
 * @since 5.6.0
 *
 * @param string $pattern Mẫu để so khớp.
 * @param string $value   Giá trị cần kiểm tra.
 * @return bool           True nếu mẫu khớp với giá trị cho trước, ngược lại false.
 */
function rest_validate_json_schema_pattern( $pattern, $value ) {
	$escaped_pattern = str_replace( '#', '\\#', $pattern );

	return 1 === preg_match( '#' . $escaped_pattern . '#u', $value );
}

/**
 * Tìm schema cho một thuộc tính sử dụng từ khóa patternProperties.
 *
 * @since 5.6.0
 *
 * @param string $property Tên thuộc tính cần kiểm tra.
 * @param array  $args     Mảng schema để sử dụng.
 * @return array|null      Schema của thuộc tính mẫu khớp, hoặc null nếu không có mẫu nào khớp.
 */
function rest_find_matching_pattern_property_schema( $property, $args ) {
	if ( isset( $args['patternProperties'] ) ) {
		foreach ( $args['patternProperties'] as $pattern => $child_schema ) {
			if ( rest_validate_json_schema_pattern( $pattern, $property ) ) {
				return $child_schema;
			}
		}
	}

	return null;
}

/**
 * Formats a combining operation error into a WP_Error object.
 *
 * @since 5.6.0
 *
 * @param string $param The parameter name.
 * @param array $error  The error details.
 * @return WP_Error
 */
function rest_format_combining_operation_error( $param, $error ) {
	$position = $error['index'];
	$reason   = $error['error_object']->get_error_message();

	if ( isset( $error['schema']['title'] ) ) {
		$title = $error['schema']['title'];

		return new WP_Error(
			'rest_no_matching_schema',
			/* translators: 1: Parameter, 2: Schema title, 3: Reason. */
			sprintf( __( '%1$s is not a valid %2$s. Reason: %3$s' ), $param, $title, $reason ),
			array( 'position' => $position )
		);
	}

	return new WP_Error(
		'rest_no_matching_schema',
		/* translators: 1: Parameter, 2: Reason. */
		sprintf( __( '%1$s does not match the expected format. Reason: %2$s' ), $param, $reason ),
		array( 'position' => $position )
	);
}

/**
 * Gets the error of combining operation.
 *
 * @since 5.6.0
 *
 * @param array  $value  The value to validate.
 * @param string $param  The parameter name, used in error messages.
 * @param array  $errors The errors array, to search for possible error.
 * @return WP_Error      The combining operation error.
 */
function rest_get_combining_operation_error( $value, $param, $errors ) {
	// If there is only one error, simply return it.
	if ( 1 === count( $errors ) ) {
		return rest_format_combining_operation_error( $param, $errors[0] );
	}

	// Filter out all errors related to type validation.
	$filtered_errors = array();
	foreach ( $errors as $error ) {
		$error_code = $error['error_object']->get_error_code();
		$error_data = $error['error_object']->get_error_data();

		if ( 'rest_invalid_type' !== $error_code || ( isset( $error_data['param'] ) && $param !== $error_data['param'] ) ) {
			$filtered_errors[] = $error;
		}
	}

	// If there is only one error left, simply return it.
	if ( 1 === count( $filtered_errors ) ) {
		return rest_format_combining_operation_error( $param, $filtered_errors[0] );
	}

	// If there are only errors related to object validation, try choosing the most appropriate one.
	if ( count( $filtered_errors ) > 1 && 'object' === $filtered_errors[0]['schema']['type'] ) {
		$result = null;
		$number = 0;

		foreach ( $filtered_errors as $error ) {
			if ( isset( $error['schema']['properties'] ) ) {
				$n = count( array_intersect_key( $error['schema']['properties'], $value ) );
				if ( $n > $number ) {
					$result = $error;
					$number = $n;
				}
			}
		}

		if ( null !== $result ) {
			return rest_format_combining_operation_error( $param, $result );
		}
	}

	// If each schema has a title, include those titles in the error message.
	$schema_titles = array();
	foreach ( $errors as $error ) {
		if ( isset( $error['schema']['title'] ) ) {
			$schema_titles[] = $error['schema']['title'];
		}
	}

	if ( count( $schema_titles ) === count( $errors ) ) {
		/* translators: 1: Parameter, 2: Schema titles. */
		return new WP_Error( 'rest_no_matching_schema', wp_sprintf( __( '%1$s is not a valid %2$l.' ), $param, $schema_titles ) );
	}

	/* translators: %s: Parameter. */
	return new WP_Error( 'rest_no_matching_schema', sprintf( __( '%s does not match any of the expected formats.' ), $param ) );
}

/**
 * Finds the matching schema among the "anyOf" schemas.
 *
 * @since 5.6.0
 *
 * @param mixed  $value   The value to validate.
 * @param array  $args    The schema array to use.
 * @param string $param   The parameter name, used in error messages.
 * @return array|WP_Error The matching schema or WP_Error instance if all schemas do not match.
 */
function rest_find_any_matching_schema( $value, $args, $param ) {
	$errors = array();

	foreach ( $args['anyOf'] as $index => $schema ) {
		if ( ! isset( $schema['type'] ) && isset( $args['type'] ) ) {
			$schema['type'] = $args['type'];
		}

		$is_valid = rest_validate_value_from_schema( $value, $schema, $param );
		if ( ! is_wp_error( $is_valid ) ) {
			return $schema;
		}

		$errors[] = array(
			'error_object' => $is_valid,
			'schema'       => $schema,
			'index'        => $index,
		);
	}

	return rest_get_combining_operation_error( $value, $param, $errors );
}

/**
 * Finds the matching schema among the "oneOf" schemas.
 *
 * @since 5.6.0
 *
 * @param mixed  $value                  The value to validate.
 * @param array  $args                   The schema array to use.
 * @param string $param                  The parameter name, used in error messages.
 * @param bool   $stop_after_first_match Optional. Whether the process should stop after the first successful match.
 * @return array|WP_Error                The matching schema or WP_Error instance if the number of matching schemas is not equal to one.
 */
function rest_find_one_matching_schema( $value, $args, $param, $stop_after_first_match = false ) {
	$matching_schemas = array();
	$errors           = array();

	foreach ( $args['oneOf'] as $index => $schema ) {
		if ( ! isset( $schema['type'] ) && isset( $args['type'] ) ) {
			$schema['type'] = $args['type'];
		}

		$is_valid = rest_validate_value_from_schema( $value, $schema, $param );
		if ( ! is_wp_error( $is_valid ) ) {
			if ( $stop_after_first_match ) {
				return $schema;
			}

			$matching_schemas[] = array(
				'schema_object' => $schema,
				'index'         => $index,
			);
		} else {
			$errors[] = array(
				'error_object' => $is_valid,
				'schema'       => $schema,
				'index'        => $index,
			);
		}
	}

	if ( ! $matching_schemas ) {
		return rest_get_combining_operation_error( $value, $param, $errors );
	}

	if ( count( $matching_schemas ) > 1 ) {
		$schema_positions = array();
		$schema_titles    = array();

		foreach ( $matching_schemas as $schema ) {
			$schema_positions[] = $schema['index'];

			if ( isset( $schema['schema_object']['title'] ) ) {
				$schema_titles[] = $schema['schema_object']['title'];
			}
		}

		// If each schema has a title, include those titles in the error message.
		if ( count( $schema_titles ) === count( $matching_schemas ) ) {
			return new WP_Error(
				'rest_one_of_multiple_matches',
				/* translators: 1: Parameter, 2: Schema titles. */
				wp_sprintf( __( '%1$s matches %2$l, but should match only one.' ), $param, $schema_titles ),
				array( 'positions' => $schema_positions )
			);
		}

		return new WP_Error(
			'rest_one_of_multiple_matches',
			/* translators: %s: Parameter. */
			sprintf( __( '%s matches more than one of the expected formats.' ), $param ),
			array( 'positions' => $schema_positions )
		);
	}

	return $matching_schemas[0]['schema_object'];
}

/**
 * Checks the equality of two values, following JSON Schema semantics.
 *
 * Property order is ignored for objects.
 *
 * Values must have been previously sanitized/coerced to their native types.
 *
 * @since 5.7.0
 *
 * @param mixed $value1 The first value to check.
 * @param mixed $value2 The second value to check.
 * @return bool True if the values are equal or false otherwise.
 */
function rest_are_values_equal( $value1, $value2 ) {
	if ( is_array( $value1 ) && is_array( $value2 ) ) {
		if ( count( $value1 ) !== count( $value2 ) ) {
			return false;
		}

		foreach ( $value1 as $index => $value ) {
			if ( ! array_key_exists( $index, $value2 ) || ! rest_are_values_equal( $value, $value2[ $index ] ) ) {
				return false;
			}
		}

		return true;
	}

	if ( is_int( $value1 ) && is_float( $value2 )
		|| is_float( $value1 ) && is_int( $value2 )
	) {
		return (float) $value1 === (float) $value2;
	}

	return $value1 === $value2;
}

/**
 * Validates that the given value is a member of the JSON Schema "enum".
 *
 * @since 5.7.0
 *
 * @param mixed  $value  The value to validate.
 * @param array  $args   The schema array to use.
 * @param string $param  The parameter name, used in error messages.
 * @return true|WP_Error True if the "enum" contains the value or a WP_Error instance otherwise.
 */
function rest_validate_enum( $value, $args, $param ) {
	$sanitized_value = rest_sanitize_value_from_schema( $value, $args, $param );
	if ( is_wp_error( $sanitized_value ) ) {
		return $sanitized_value;
	}

	foreach ( $args['enum'] as $enum_value ) {
		if ( rest_are_values_equal( $sanitized_value, $enum_value ) ) {
			return true;
		}
	}

	$encoded_enum_values = array();
	foreach ( $args['enum'] as $enum_value ) {
		$encoded_enum_values[] = is_scalar( $enum_value ) ? $enum_value : wp_json_encode( $enum_value );
	}

	if ( count( $encoded_enum_values ) === 1 ) {
		/* translators: 1: Parameter, 2: Valid values. */
		return new WP_Error( 'rest_not_in_enum', wp_sprintf( __( '%1$s is not %2$s.' ), $param, $encoded_enum_values[0] ) );
	}

	/* translators: 1: Parameter, 2: List of valid values. */
	return new WP_Error( 'rest_not_in_enum', wp_sprintf( __( '%1$s is not one of %2$l.' ), $param, $encoded_enum_values ) );
}

/**
 * Get all valid JSON schema properties.
 *
 * @since 5.6.0
 *
 * @return string[] All valid JSON schema properties.
 */
function rest_get_allowed_schema_keywords() {
	return array(
		'title',
		'description',
		'default',
		'type',
		'format',
		'enum',
		'items',
		'properties',
		'additionalProperties',
		'patternProperties',
		'minProperties',
		'maxProperties',
		'minimum',
		'maximum',
		'exclusiveMinimum',
		'exclusiveMaximum',
		'multipleOf',
		'minLength',
		'maxLength',
		'pattern',
		'minItems',
		'maxItems',
		'uniqueItems',
		'anyOf',
		'oneOf',
	);
}

/**
 * Validate a value based on a schema.
 *
 * @since 4.7.0
 * @since 4.9.0 Support the "object" type.
 * @since 5.2.0 Support validating "additionalProperties" against a schema.
 * @since 5.3.0 Support multiple types.
 * @since 5.4.0 Convert an empty string to an empty object.
 * @since 5.5.0 Add the "uuid" and "hex-color" formats.
 *              Support the "minLength", "maxLength" and "pattern" keywords for strings.
 *              Support the "minItems", "maxItems" and "uniqueItems" keywords for arrays.
 *              Validate required properties.
 * @since 5.6.0 Support the "minProperties" and "maxProperties" keywords for objects.
 *              Support the "multipleOf" keyword for numbers and integers.
 *              Support the "patternProperties" keyword for objects.
 *              Support the "anyOf" and "oneOf" keywords.
 *
 * @param mixed  $value The value to validate.
 * @param array  $args  Schema array to use for validation.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_value_from_schema( $value, $args, $param = '' ) {
	if ( isset( $args['anyOf'] ) ) {
		$matching_schema = rest_find_any_matching_schema( $value, $args, $param );
		if ( is_wp_error( $matching_schema ) ) {
			return $matching_schema;
		}

		if ( ! isset( $args['type'] ) && isset( $matching_schema['type'] ) ) {
			$args['type'] = $matching_schema['type'];
		}
	}

	if ( isset( $args['oneOf'] ) ) {
		$matching_schema = rest_find_one_matching_schema( $value, $args, $param );
		if ( is_wp_error( $matching_schema ) ) {
			return $matching_schema;
		}

		if ( ! isset( $args['type'] ) && isset( $matching_schema['type'] ) ) {
			$args['type'] = $matching_schema['type'];
		}
	}

	$allowed_types = array( 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' );

	if ( ! isset( $args['type'] ) ) {
		/* translators: %s: Parameter. */
		_doing_it_wrong( __FUNCTION__, sprintf( __( 'The "type" schema keyword for %s is required.' ), $param ), '5.5.0' );
	}

	if ( is_array( $args['type'] ) ) {
		$best_type = rest_handle_multi_type_schema( $value, $args, $param );

		if ( ! $best_type ) {
			return new WP_Error(
				'rest_invalid_type',
				/* translators: 1: Parameter, 2: List of types. */
				sprintf( __( '%1$s is not of type %2$s.' ), $param, implode( ',', $args['type'] ) ),
				array( 'param' => $param )
			);
		}

		$args['type'] = $best_type;
	}

	if ( ! in_array( $args['type'], $allowed_types, true ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			/* translators: 1: Parameter, 2: The list of allowed types. */
			wp_sprintf( __( 'The "type" schema keyword for %1$s can only be one of the built-in types: %2$l.' ), $param, $allowed_types ),
			'5.5.0'
		);
	}

	switch ( $args['type'] ) {
		case 'null':
			$is_valid = rest_validate_null_value_from_schema( $value, $param );
			break;
		case 'boolean':
			$is_valid = rest_validate_boolean_value_from_schema( $value, $param );
			break;
		case 'object':
			$is_valid = rest_validate_object_value_from_schema( $value, $args, $param );
			break;
		case 'array':
			$is_valid = rest_validate_array_value_from_schema( $value, $args, $param );
			break;
		case 'number':
			$is_valid = rest_validate_number_value_from_schema( $value, $args, $param );
			break;
		case 'string':
			$is_valid = rest_validate_string_value_from_schema( $value, $args, $param );
			break;
		case 'integer':
			$is_valid = rest_validate_integer_value_from_schema( $value, $args, $param );
			break;
		default:
			$is_valid = true;
			break;
	}

	if ( is_wp_error( $is_valid ) ) {
		return $is_valid;
	}

	if ( ! empty( $args['enum'] ) ) {
		$enum_contains_value = rest_validate_enum( $value, $args, $param );
		if ( is_wp_error( $enum_contains_value ) ) {
			return $enum_contains_value;
		}
	}

	/*
	 * The "format" keyword should only be applied to strings. However, for backward compatibility,
	 * we allow the "format" keyword if the type keyword was not specified, or was set to an invalid value.
	 */
	if ( isset( $args['format'] )
		&& ( ! isset( $args['type'] ) || 'string' === $args['type'] || ! in_array( $args['type'], $allowed_types, true ) )
	) {
		switch ( $args['format'] ) {
			case 'hex-color':
				if ( ! rest_parse_hex_color( $value ) ) {
					return new WP_Error( 'rest_invalid_hex_color', __( 'Invalid hex color.' ) );
				}
				break;

			case 'date-time':
				if ( false === rest_parse_date( $value ) ) {
					return new WP_Error( 'rest_invalid_date', __( 'Invalid date.' ) );
				}
				break;

			case 'email':
				if ( ! is_email( $value ) ) {
					return new WP_Error( 'rest_invalid_email', __( 'Invalid email address.' ) );
				}
				break;
			case 'ip':
				if ( ! rest_is_ip_address( $value ) ) {
					/* translators: %s: IP address. */
					return new WP_Error( 'rest_invalid_ip', sprintf( __( '%s is not a valid IP address.' ), $param ) );
				}
				break;
			case 'uuid':
				if ( ! wp_is_uuid( $value ) ) {
					/* translators: %s: The name of a JSON field expecting a valid UUID. */
					return new WP_Error( 'rest_invalid_uuid', sprintf( __( '%s is not a valid UUID.' ), $param ) );
				}
				break;
		}
	}

	return true;
}

/**
 * Validates a null value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_null_value_from_schema( $value, $param ) {
	if ( null !== $value ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, 'null' ),
			array( 'param' => $param )
		);
	}

	return true;
}

/**
 * Validates a boolean value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_boolean_value_from_schema( $value, $param ) {
	if ( ! rest_is_boolean( $value ) ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, 'boolean' ),
			array( 'param' => $param )
		);
	}

	return true;
}

/**
 * Validates an object value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param array  $args  Schema array to use for validation.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_object_value_from_schema( $value, $args, $param ) {
	if ( ! rest_is_object( $value ) ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, 'object' ),
			array( 'param' => $param )
		);
	}

	$value = rest_sanitize_object( $value );

	if ( isset( $args['required'] ) && is_array( $args['required'] ) ) { // schema version 4
		foreach ( $args['required'] as $name ) {
			if ( ! array_key_exists( $name, $value ) ) {
				return new WP_Error(
					'rest_property_required',
					/* translators: 1: Property of an object, 2: Parameter. */
					sprintf( __( '%1$s is a required property of %2$s.' ), $name, $param )
				);
			}
		}
	} elseif ( isset( $args['properties'] ) ) { // schema version 3
		foreach ( $args['properties'] as $name => $property ) {
			if ( isset( $property['required'] ) && true === $property['required'] && ! array_key_exists( $name, $value ) ) {
				return new WP_Error(
					'rest_property_required',
					/* translators: 1: Property of an object, 2: Parameter. */
					sprintf( __( '%1$s is a required property of %2$s.' ), $name, $param )
				);
			}
		}
	}

	foreach ( $value as $property => $v ) {
		if ( isset( $args['properties'][ $property ] ) ) {
			$is_valid = rest_validate_value_from_schema( $v, $args['properties'][ $property ], $param . '[' . $property . ']' );
			if ( is_wp_error( $is_valid ) ) {
				return $is_valid;
			}
			continue;
		}

		$pattern_property_schema = rest_find_matching_pattern_property_schema( $property, $args );
		if ( null !== $pattern_property_schema ) {
			$is_valid = rest_validate_value_from_schema( $v, $pattern_property_schema, $param . '[' . $property . ']' );
			if ( is_wp_error( $is_valid ) ) {
				return $is_valid;
			}
			continue;
		}

		if ( isset( $args['additionalProperties'] ) ) {
			if ( false === $args['additionalProperties'] ) {
				return new WP_Error(
					'rest_additional_properties_forbidden',
					/* translators: %s: Property of an object. */
					sprintf( __( '%1$s is not a valid property of Object.' ), $property )
				);
			}

			if ( is_array( $args['additionalProperties'] ) ) {
				$is_valid = rest_validate_value_from_schema( $v, $args['additionalProperties'], $param . '[' . $property . ']' );
				if ( is_wp_error( $is_valid ) ) {
					return $is_valid;
				}
			}
		}
	}

	if ( isset( $args['minProperties'] ) && count( $value ) < $args['minProperties'] ) {
		return new WP_Error(
			'rest_too_few_properties',
			sprintf(
				/* translators: 1: Parameter, 2: Number. */
				_n(
					'%1$s must contain at least %2$s property.',
					'%1$s must contain at least %2$s properties.',
					$args['minProperties']
				),
				$param,
				number_format_i18n( $args['minProperties'] )
			)
		);
	}

	if ( isset( $args['maxProperties'] ) && count( $value ) > $args['maxProperties'] ) {
		return new WP_Error(
			'rest_too_many_properties',
			sprintf(
				/* translators: 1: Parameter, 2: Number. */
				_n(
					'%1$s must contain at most %2$s property.',
					'%1$s must contain at most %2$s properties.',
					$args['maxProperties']
				),
				$param,
				number_format_i18n( $args['maxProperties'] )
			)
		);
	}

	return true;
}

/**
 * Validates an array value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param array  $args  Schema array to use for validation.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_array_value_from_schema( $value, $args, $param ) {
	if ( ! rest_is_array( $value ) ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, 'array' ),
			array( 'param' => $param )
		);
	}

	$value = rest_sanitize_array( $value );

	if ( isset( $args['items'] ) ) {
		foreach ( $value as $index => $v ) {
			$is_valid = rest_validate_value_from_schema( $v, $args['items'], $param . '[' . $index . ']' );
			if ( is_wp_error( $is_valid ) ) {
				return $is_valid;
			}
		}
	}

	if ( isset( $args['minItems'] ) && count( $value ) < $args['minItems'] ) {
		return new WP_Error(
			'rest_too_few_items',
			sprintf(
				/* translators: 1: Parameter, 2: Number. */
				_n(
					'%1$s must contain at least %2$s item.',
					'%1$s must contain at least %2$s items.',
					$args['minItems']
				),
				$param,
				number_format_i18n( $args['minItems'] )
			)
		);
	}

	if ( isset( $args['maxItems'] ) && count( $value ) > $args['maxItems'] ) {
		return new WP_Error(
			'rest_too_many_items',
			sprintf(
				/* translators: 1: Parameter, 2: Number. */
				_n(
					'%1$s must contain at most %2$s item.',
					'%1$s must contain at most %2$s items.',
					$args['maxItems']
				),
				$param,
				number_format_i18n( $args['maxItems'] )
			)
		);
	}

	if ( ! empty( $args['uniqueItems'] ) && ! rest_validate_array_contains_unique_items( $value ) ) {
		/* translators: %s: Parameter. */
		return new WP_Error( 'rest_duplicate_items', sprintf( __( '%s has duplicate items.' ), $param ) );
	}

	return true;
}

/**
 * Validates a number value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param array  $args  Schema array to use for validation.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_number_value_from_schema( $value, $args, $param ) {
	if ( ! is_numeric( $value ) ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, $args['type'] ),
			array( 'param' => $param )
		);
	}

	if ( isset( $args['multipleOf'] ) && fmod( $value, $args['multipleOf'] ) !== 0.0 ) {
		return new WP_Error(
			'rest_invalid_multiple',
			/* translators: 1: Parameter, 2: Multiplier. */
			sprintf( __( '%1$s must be a multiple of %2$s.' ), $param, $args['multipleOf'] )
		);
	}

	if ( isset( $args['minimum'] ) && ! isset( $args['maximum'] ) ) {
		if ( ! empty( $args['exclusiveMinimum'] ) && $value <= $args['minimum'] ) {
			return new WP_Error(
				'rest_out_of_bounds',
				/* translators: 1: Parameter, 2: Minimum number. */
				sprintf( __( '%1$s must be greater than %2$d' ), $param, $args['minimum'] )
			);
		}

		if ( empty( $args['exclusiveMinimum'] ) && $value < $args['minimum'] ) {
			return new WP_Error(
				'rest_out_of_bounds',
				/* translators: 1: Parameter, 2: Minimum number. */
				sprintf( __( '%1$s must be greater than or equal to %2$d' ), $param, $args['minimum'] )
			);
		}
	}

	if ( isset( $args['maximum'] ) && ! isset( $args['minimum'] ) ) {
		if ( ! empty( $args['exclusiveMaximum'] ) && $value >= $args['maximum'] ) {
			return new WP_Error(
				'rest_out_of_bounds',
				/* translators: 1: Parameter, 2: Maximum number. */
				sprintf( __( '%1$s must be less than %2$d' ), $param, $args['maximum'] )
			);
		}

		if ( empty( $args['exclusiveMaximum'] ) && $value > $args['maximum'] ) {
			return new WP_Error(
				'rest_out_of_bounds',
				/* translators: 1: Parameter, 2: Maximum number. */
				sprintf( __( '%1$s must be less than or equal to %2$d' ), $param, $args['maximum'] )
			);
		}
	}

	if ( isset( $args['minimum'], $args['maximum'] ) ) {
		if ( ! empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
			if ( $value >= $args['maximum'] || $value <= $args['minimum'] ) {
				return new WP_Error(
					'rest_out_of_bounds',
					sprintf(
						/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */
						__( '%1$s must be between %2$d (exclusive) and %3$d (exclusive)' ),
						$param,
						$args['minimum'],
						$args['maximum']
					)
				);
			}
		}

		if ( ! empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
			if ( $value > $args['maximum'] || $value <= $args['minimum'] ) {
				return new WP_Error(
					'rest_out_of_bounds',
					sprintf(
						/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */
						__( '%1$s must be between %2$d (exclusive) and %3$d (inclusive)' ),
						$param,
						$args['minimum'],
						$args['maximum']
					)
				);
			}
		}

		if ( ! empty( $args['exclusiveMaximum'] ) && empty( $args['exclusiveMinimum'] ) ) {
			if ( $value >= $args['maximum'] || $value < $args['minimum'] ) {
				return new WP_Error(
					'rest_out_of_bounds',
					sprintf(
						/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */
						__( '%1$s must be between %2$d (inclusive) and %3$d (exclusive)' ),
						$param,
						$args['minimum'],
						$args['maximum']
					)
				);
			}
		}

		if ( empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
			if ( $value > $args['maximum'] || $value < $args['minimum'] ) {
				return new WP_Error(
					'rest_out_of_bounds',
					sprintf(
						/* translators: 1: Parameter, 2: Minimum number, 3: Maximum number. */
						__( '%1$s must be between %2$d (inclusive) and %3$d (inclusive)' ),
						$param,
						$args['minimum'],
						$args['maximum']
					)
				);
			}
		}
	}

	return true;
}

/**
 * Validates a string value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param array  $args  Schema array to use for validation.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_string_value_from_schema( $value, $args, $param ) {
	if ( ! is_string( $value ) ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, 'string' ),
			array( 'param' => $param )
		);
	}

	if ( isset( $args['minLength'] ) && mb_strlen( $value ) < $args['minLength'] ) {
		return new WP_Error(
			'rest_too_short',
			sprintf(
				/* translators: 1: Parameter, 2: Number of characters. */
				_n(
					'%1$s must be at least %2$s character long.',
					'%1$s must be at least %2$s characters long.',
					$args['minLength']
				),
				$param,
				number_format_i18n( $args['minLength'] )
			)
		);
	}

	if ( isset( $args['maxLength'] ) && mb_strlen( $value ) > $args['maxLength'] ) {
		return new WP_Error(
			'rest_too_long',
			sprintf(
				/* translators: 1: Parameter, 2: Number of characters. */
				_n(
					'%1$s must be at most %2$s character long.',
					'%1$s must be at most %2$s characters long.',
					$args['maxLength']
				),
				$param,
				number_format_i18n( $args['maxLength'] )
			)
		);
	}

	if ( isset( $args['pattern'] ) && ! rest_validate_json_schema_pattern( $args['pattern'], $value ) ) {
		return new WP_Error(
			'rest_invalid_pattern',
			/* translators: 1: Parameter, 2: Pattern. */
			sprintf( __( '%1$s does not match pattern %2$s.' ), $param, $args['pattern'] )
		);
	}

	return true;
}

/**
 * Validates an integer value based on a schema.
 *
 * @since 5.7.0
 *
 * @param mixed  $value The value to validate.
 * @param array  $args  Schema array to use for validation.
 * @param string $param The parameter name, used in error messages.
 * @return true|WP_Error
 */
function rest_validate_integer_value_from_schema( $value, $args, $param ) {
	$is_valid_number = rest_validate_number_value_from_schema( $value, $args, $param );
	if ( is_wp_error( $is_valid_number ) ) {
		return $is_valid_number;
	}

	if ( ! rest_is_integer( $value ) ) {
		return new WP_Error(
			'rest_invalid_type',
			/* translators: 1: Parameter, 2: Type name. */
			sprintf( __( '%1$s is not of type %2$s.' ), $param, 'integer' ),
			array( 'param' => $param )
		);
	}

	return true;
}

/**
 * Sanitize a value based on a schema.
 *
 * @since 4.7.0
 * @since 5.5.0 Added the `$param` parameter.
 * @since 5.6.0 Support the "anyOf" and "oneOf" keywords.
 * @since 5.9.0 Added `text-field` and `textarea-field` formats.
 *
 * @param mixed  $value The value to sanitize.
 * @param array  $args  Schema array to use for sanitization.
 * @param string $param The parameter name, used in error messages.
 * @return mixed|WP_Error The sanitized value or a WP_Error instance if the value cannot be safely sanitized.
 */
function rest_sanitize_value_from_schema( $value, $args, $param = '' ) {
	if ( isset( $args['anyOf'] ) ) {
		$matching_schema = rest_find_any_matching_schema( $value, $args, $param );
		if ( is_wp_error( $matching_schema ) ) {
			return $matching_schema;
		}

		if ( ! isset( $args['type'] ) ) {
			$args['type'] = $matching_schema['type'];
		}

		$value = rest_sanitize_value_from_schema( $value, $matching_schema, $param );
	}

	if ( isset( $args['oneOf'] ) ) {
		$matching_schema = rest_find_one_matching_schema( $value, $args, $param );
		if ( is_wp_error( $matching_schema ) ) {
			return $matching_schema;
		}

		if ( ! isset( $args['type'] ) ) {
			$args['type'] = $matching_schema['type'];
		}

		$value = rest_sanitize_value_from_schema( $value, $matching_schema, $param );
	}

	$allowed_types = array( 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' );

	if ( ! isset( $args['type'] ) ) {
		/* translators: %s: Parameter. */
		_doing_it_wrong( __FUNCTION__, sprintf( __( 'The "type" schema keyword for %s is required.' ), $param ), '5.5.0' );
	}

	if ( is_array( $args['type'] ) ) {
		$best_type = rest_handle_multi_type_schema( $value, $args, $param );

		if ( ! $best_type ) {
			return null;
		}

		$args['type'] = $best_type;
	}

	if ( ! in_array( $args['type'], $allowed_types, true ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			/* translators: 1: Parameter, 2: The list of allowed types. */
			wp_sprintf( __( 'The "type" schema keyword for %1$s can only be one of the built-in types: %2$l.' ), $param, $allowed_types ),
			'5.5.0'
		);
	}

	if ( 'array' === $args['type'] ) {
		$value = rest_sanitize_array( $value );

		if ( ! empty( $args['items'] ) ) {
			foreach ( $value as $index => $v ) {
				$value[ $index ] = rest_sanitize_value_from_schema( $v, $args['items'], $param . '[' . $index . ']' );
			}
		}

		if ( ! empty( $args['uniqueItems'] ) && ! rest_validate_array_contains_unique_items( $value ) ) {
			/* translators: %s: Parameter. */
			return new WP_Error( 'rest_duplicate_items', sprintf( __( '%s has duplicate items.' ), $param ) );
		}

		return $value;
	}

	if ( 'object' === $args['type'] ) {
		$value = rest_sanitize_object( $value );

		foreach ( $value as $property => $v ) {
			if ( isset( $args['properties'][ $property ] ) ) {
				$value[ $property ] = rest_sanitize_value_from_schema( $v, $args['properties'][ $property ], $param . '[' . $property . ']' );
				continue;
			}

			$pattern_property_schema = rest_find_matching_pattern_property_schema( $property, $args );
			if ( null !== $pattern_property_schema ) {
				$value[ $property ] = rest_sanitize_value_from_schema( $v, $pattern_property_schema, $param . '[' . $property . ']' );
				continue;
			}

			if ( isset( $args['additionalProperties'] ) ) {
				if ( false === $args['additionalProperties'] ) {
					unset( $value[ $property ] );
				} elseif ( is_array( $args['additionalProperties'] ) ) {
					$value[ $property ] = rest_sanitize_value_from_schema( $v, $args['additionalProperties'], $param . '[' . $property . ']' );
				}
			}
		}

		return $value;
	}

	if ( 'null' === $args['type'] ) {
		return null;
	}

	if ( 'integer' === $args['type'] ) {
		return (int) $value;
	}

	if ( 'number' === $args['type'] ) {
		return (float) $value;
	}

	if ( 'boolean' === $args['type'] ) {
		return rest_sanitize_boolean( $value );
	}

	// This behavior matches rest_validate_value_from_schema().
	if ( isset( $args['format'] )
		&& ( ! isset( $args['type'] ) || 'string' === $args['type'] || ! in_array( $args['type'], $allowed_types, true ) )
	) {
		switch ( $args['format'] ) {
			case 'hex-color':
				return (string) sanitize_hex_color( $value );

			case 'date-time':
				return sanitize_text_field( $value );

			case 'email':
				// sanitize_email() validates, which would be unexpected.
				return sanitize_text_field( $value );

			case 'uri':
				return sanitize_url( $value );

			case 'ip':
				return sanitize_text_field( $value );

			case 'uuid':
				return sanitize_text_field( $value );

			case 'text-field':
				return sanitize_text_field( $value );

			case 'textarea-field':
				return sanitize_textarea_field( $value );
		}
	}

	if ( 'string' === $args['type'] ) {
		return (string) $value;
	}

	return $value;
}

/**
 * Append result of internal request to REST API for purpose of preloading data to be attached to a page.
 * Expected to be called in the context of `array_reduce`.
 *
 * @since 5.0.0
 *
 * @param array  $memo Reduce accumulator.
 * @param string $path REST API path to preload.
 * @return array Modified reduce accumulator.
 */
function rest_preload_api_request( $memo, $path ) {
	/*
	 * array_reduce() doesn't support passing an array in PHP 5.2,
	 * so we need to make sure we start with one.
	 */
	if ( ! is_array( $memo ) ) {
		$memo = array();
	}

	if ( empty( $path ) ) {
		return $memo;
	}

	$method = 'GET';
	if ( is_array( $path ) && 2 === count( $path ) ) {
		$method = end( $path );
		$path   = reset( $path );

		if ( ! in_array( $method, array( 'GET', 'OPTIONS' ), true ) ) {
			$method = 'GET';
		}
	}

	// Remove trailing slashes at the end of the REST API path (query part).
	$path = untrailingslashit( $path );
	if ( empty( $path ) ) {
		$path = '/';
	}

	$path_parts = parse_url( $path );
	if ( false === $path_parts ) {
		return $memo;
	}

	if ( isset( $path_parts['path'] ) && '/' !== $path_parts['path'] ) {
		// Remove trailing slashes from the "path" part of the REST API path.
		$path_parts['path'] = untrailingslashit( $path_parts['path'] );
		$path               = str_contains( $path, '?' ) ?
			$path_parts['path'] . '?' . ( $path_parts['query'] ?? '' ) :
			$path_parts['path'];
	}

	$request = new WP_REST_Request( $method, $path_parts['path'] );
	if ( ! empty( $path_parts['query'] ) ) {
		parse_str( $path_parts['query'], $query_params );
		$request->set_query_params( $query_params );
	}

	$response = rest_do_request( $request );
	if ( 200 === $response->status ) {
		$server = rest_get_server();
		/** This filter is documented in wp-includes/rest-api/class-wp-rest-server.php */
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $server, $request );
		$embed    = $request->has_param( '_embed' ) ? rest_parse_embed_param( $request['_embed'] ) : false;
		$data     = (array) $server->response_to_data( $response, $embed );

		if ( 'OPTIONS' === $method ) {
			$memo[ $method ][ $path ] = array(
				'body'    => $data,
				'headers' => $response->headers,
			);
		} else {
			$memo[ $path ] = array(
				'body'    => $data,
				'headers' => $response->headers,
			);
		}
	}

	return $memo;
}

/**
 * Parses the "_embed" parameter into the list of resources to embed.
 *
 * @since 5.4.0
 *
 * @param string|array $embed Raw "_embed" parameter value.
 * @return true|string[] Either true to embed all embeds, or a list of relations to embed.
 */
function rest_parse_embed_param( $embed ) {
	if ( ! $embed || 'true' === $embed || '1' === $embed ) {
		return true;
	}

	$rels = wp_parse_list( $embed );

	if ( ! $rels ) {
		return true;
	}

	return $rels;
}

/**
 * Filters the response to remove any fields not available in the given context.
 *
 * @since 5.5.0
 * @since 5.6.0 Support the "patternProperties" keyword for objects.
 *              Support the "anyOf" and "oneOf" keywords.
 *
 * @param array|object $response_data The response data to modify.
 * @param array        $schema        The schema for the endpoint used to filter the response.
 * @param string       $context       The requested context.
 * @return array|object The filtered response data.
 */
function rest_filter_response_by_context( $response_data, $schema, $context ) {
	if ( isset( $schema['anyOf'] ) ) {
		$matching_schema = rest_find_any_matching_schema( $response_data, $schema, '' );
		if ( ! is_wp_error( $matching_schema ) ) {
			if ( ! isset( $schema['type'] ) ) {
				$schema['type'] = $matching_schema['type'];
			}

			$response_data = rest_filter_response_by_context( $response_data, $matching_schema, $context );
		}
	}

	if ( isset( $schema['oneOf'] ) ) {
		$matching_schema = rest_find_one_matching_schema( $response_data, $schema, '', true );
		if ( ! is_wp_error( $matching_schema ) ) {
			if ( ! isset( $schema['type'] ) ) {
				$schema['type'] = $matching_schema['type'];
			}

			$response_data = rest_filter_response_by_context( $response_data, $matching_schema, $context );
		}
	}

	if ( ! is_array( $response_data ) && ! is_object( $response_data ) ) {
		return $response_data;
	}

	if ( isset( $schema['type'] ) ) {
		$type = $schema['type'];
	} elseif ( isset( $schema['properties'] ) ) {
		$type = 'object'; // Back compat if a developer accidentally omitted the type.
	} else {
		return $response_data;
	}

	$is_array_type  = 'array' === $type || ( is_array( $type ) && in_array( 'array', $type, true ) );
	$is_object_type = 'object' === $type || ( is_array( $type ) && in_array( 'object', $type, true ) );

	if ( $is_array_type && $is_object_type ) {
		if ( rest_is_array( $response_data ) ) {
			$is_object_type = false;
		} else {
			$is_array_type = false;
		}
	}

	$has_additional_properties = $is_object_type && isset( $schema['additionalProperties'] ) && is_array( $schema['additionalProperties'] );

	foreach ( $response_data as $key => $value ) {
		$check = array();

		if ( $is_array_type ) {
			$check = isset( $schema['items'] ) ? $schema['items'] : array();
		} elseif ( $is_object_type ) {
			if ( isset( $schema['properties'][ $key ] ) ) {
				$check = $schema['properties'][ $key ];
			} else {
				$pattern_property_schema = rest_find_matching_pattern_property_schema( $key, $schema );
				if ( null !== $pattern_property_schema ) {
					$check = $pattern_property_schema;
				} elseif ( $has_additional_properties ) {
					$check = $schema['additionalProperties'];
				}
			}
		}

		if ( ! isset( $check['context'] ) ) {
			continue;
		}

		if ( ! in_array( $context, $check['context'], true ) ) {
			if ( $is_array_type ) {
				// All array items share schema, so there's no need to check each one.
				$response_data = array();
				break;
			}

			if ( is_object( $response_data ) ) {
				unset( $response_data->$key );
			} else {
				unset( $response_data[ $key ] );
			}
		} elseif ( is_array( $value ) || is_object( $value ) ) {
			$new_value = rest_filter_response_by_context( $value, $check, $context );

			if ( is_object( $response_data ) ) {
				$response_data->$key = $new_value;
			} else {
				$response_data[ $key ] = $new_value;
			}
		}
	}

	return $response_data;
}

/**
 * Sets the "additionalProperties" to false by default for all object definitions in the schema.
 *
 * @since 5.5.0
 * @since 5.6.0 Support the "patternProperties" keyword.
 *
 * @param array $schema The schema to modify.
 * @return array The modified schema.
 */
function rest_default_additional_properties_to_false( $schema ) {
	$type = (array) $schema['type'];

	if ( in_array( 'object', $type, true ) ) {
		if ( isset( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $key => $child_schema ) {
				$schema['properties'][ $key ] = rest_default_additional_properties_to_false( $child_schema );
			}
		}

		if ( isset( $schema['patternProperties'] ) ) {
			foreach ( $schema['patternProperties'] as $key => $child_schema ) {
				$schema['patternProperties'][ $key ] = rest_default_additional_properties_to_false( $child_schema );
			}
		}

		if ( ! isset( $schema['additionalProperties'] ) ) {
			$schema['additionalProperties'] = false;
		}
	}

	if ( in_array( 'array', $type, true ) ) {
		if ( isset( $schema['items'] ) ) {
			$schema['items'] = rest_default_additional_properties_to_false( $schema['items'] );
		}
	}

	return $schema;
}

/**
 * Gets the REST API route for a post.
 *
 * @since 5.5.0
 *
 * @param int|WP_Post $post Post ID or post object.
 * @return string The route path with a leading slash for the given post,
 *                or an empty string if there is not a route.
 */
function rest_get_route_for_post( $post ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$post_type_route = rest_get_route_for_post_type_items( $post->post_type );
	if ( ! $post_type_route ) {
		return '';
	}

	$route = sprintf( '%s/%d', $post_type_route, $post->ID );

	/**
	 * Filters the REST API route for a post.
	 *
	 * @since 5.5.0
	 *
	 * @param string  $route The route path.
	 * @param WP_Post $post  The post object.
	 */
	return apply_filters( 'rest_route_for_post', $route, $post );
}

/**
 * Gets the REST API route for a post type.
 *
 * @since 5.9.0
 *
 * @param string $post_type The name of a registered post type.
 * @return string The route path with a leading slash for the given post type,
 *                or an empty string if there is not a route.
 */
function rest_get_route_for_post_type_items( $post_type ) {
	$post_type = get_post_type_object( $post_type );
	if ( ! $post_type ) {
		return '';
	}

	if ( ! $post_type->show_in_rest ) {
		return '';
	}

	$namespace = ! empty( $post_type->rest_namespace ) ? $post_type->rest_namespace : 'wp/v2';
	$rest_base = ! empty( $post_type->rest_base ) ? $post_type->rest_base : $post_type->name;
	$route     = sprintf( '/%s/%s', $namespace, $rest_base );

	/**
	 * Filters the REST API route for a post type.
	 *
	 * @since 5.9.0
	 *
	 * @param string       $route      The route path.
	 * @param WP_Post_Type $post_type  The post type object.
	 */
	return apply_filters( 'rest_route_for_post_type_items', $route, $post_type );
}

/**
 * Gets the REST API route for a term.
 *
 * @since 5.5.0
 *
 * @param int|WP_Term $term Term ID or term object.
 * @return string The route path with a leading slash for the given term,
 *                or an empty string if there is not a route.
 */
function rest_get_route_for_term( $term ) {
	$term = get_term( $term );

	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$taxonomy_route = rest_get_route_for_taxonomy_items( $term->taxonomy );
	if ( ! $taxonomy_route ) {
		return '';
	}

	$route = sprintf( '%s/%d', $taxonomy_route, $term->term_id );

	/**
	 * Filters the REST API route for a term.
	 *
	 * @since 5.5.0
	 *
	 * @param string  $route The route path.
	 * @param WP_Term $term  The term object.
	 */
	return apply_filters( 'rest_route_for_term', $route, $term );
}

/**
 * Gets the REST API route for a taxonomy.
 *
 * @since 5.9.0
 *
 * @param string $taxonomy Name of taxonomy.
 * @return string The route path with a leading slash for the given taxonomy.
 */
function rest_get_route_for_taxonomy_items( $taxonomy ) {
	$taxonomy = get_taxonomy( $taxonomy );
	if ( ! $taxonomy ) {
		return '';
	}

	if ( ! $taxonomy->show_in_rest ) {
		return '';
	}

	$namespace = ! empty( $taxonomy->rest_namespace ) ? $taxonomy->rest_namespace : 'wp/v2';
	$rest_base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
	$route     = sprintf( '/%s/%s', $namespace, $rest_base );

	/**
	 * Filters the REST API route for a taxonomy.
	 *
	 * @since 5.9.0
	 *
	 * @param string      $route    The route path.
	 * @param WP_Taxonomy $taxonomy The taxonomy object.
	 */
	return apply_filters( 'rest_route_for_taxonomy_items', $route, $taxonomy );
}

/**
 * Gets the REST route for the currently queried object.
 *
 * @since 5.5.0
 *
 * @return string The REST route of the resource, or an empty string if no resource identified.
 */
function rest_get_queried_resource_route() {
	if ( is_singular() ) {
		$route = rest_get_route_for_post( get_queried_object() );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$route = rest_get_route_for_term( get_queried_object() );
	} elseif ( is_author() ) {
		$route = '/wp/v2/users/' . get_queried_object_id();
	} else {
		$route = '';
	}

	/**
	 * Filters the REST route for the currently queried object.
	 *
	 * @since 5.5.0
	 *
	 * @param string $link The route with a leading slash, or an empty string.
	 */
	return apply_filters( 'rest_queried_resource_route', $route );
}

/**
 * Retrieves an array of endpoint arguments from the item schema and endpoint method.
 *
 * @since 5.6.0
 *
 * @param array  $schema The full JSON schema for the endpoint.
 * @param string $method Optional. HTTP method of the endpoint. The arguments for `CREATABLE` endpoints are
 *                       checked for required values and may fall-back to a given default, this is not done
 *                       on `EDITABLE` endpoints. Default WP_REST_Server::CREATABLE.
 * @return array The endpoint arguments.
 */
function rest_get_endpoint_args_for_schema( $schema, $method = WP_REST_Server::CREATABLE ) {

	$schema_properties       = ! empty( $schema['properties'] ) ? $schema['properties'] : array();
	$endpoint_args           = array();
	$valid_schema_properties = rest_get_allowed_schema_keywords();
	$valid_schema_properties = array_diff( $valid_schema_properties, array( 'default', 'required' ) );

	foreach ( $schema_properties as $field_id => $params ) {

		// Arguments specified as `readonly` are not allowed to be set.
		if ( ! empty( $params['readonly'] ) ) {
			continue;
		}

		$endpoint_args[ $field_id ] = array(
			'validate_callback' => 'rest_validate_request_arg',
			'sanitize_callback' => 'rest_sanitize_request_arg',
		);

		if ( WP_REST_Server::CREATABLE === $method && isset( $params['default'] ) ) {
			$endpoint_args[ $field_id ]['default'] = $params['default'];
		}

		if ( WP_REST_Server::CREATABLE === $method && ! empty( $params['required'] ) ) {
			$endpoint_args[ $field_id ]['required'] = true;
		}

		foreach ( $valid_schema_properties as $schema_prop ) {
			if ( isset( $params[ $schema_prop ] ) ) {
				$endpoint_args[ $field_id ][ $schema_prop ] = $params[ $schema_prop ];
			}
		}

		// Merge in any options provided by the schema property.
		if ( isset( $params['arg_options'] ) ) {

			// Only use required / default from arg_options on CREATABLE endpoints.
			if ( WP_REST_Server::CREATABLE !== $method ) {
				$params['arg_options'] = array_diff_key(
					$params['arg_options'],
					array(
						'required' => '',
						'default'  => '',
					)
				);
			}

			$endpoint_args[ $field_id ] = array_merge( $endpoint_args[ $field_id ], $params['arg_options'] );
		}
	}

	return $endpoint_args;
}


/**
 * Converts an error to a response object.
 *
 * This iterates over all error codes and messages to change it into a flat
 * array. This enables simpler client behavior, as it is represented as a
 * list in JSON rather than an object/map.
 *
 * @since 5.7.0
 *
 * @param WP_Error $error WP_Error instance.
 *
 * @return WP_REST_Response List of associative arrays with code and message keys.
 */
function rest_convert_error_to_response( $error ) {
	$status = array_reduce(
		$error->get_all_error_data(),
		static function ( $status, $error_data ) {
			return is_array( $error_data ) && isset( $error_data['status'] ) ? $error_data['status'] : $status;
		},
		500
	);

	$errors = array();

	foreach ( (array) $error->errors as $code => $messages ) {
		$all_data  = $error->get_all_error_data( $code );
		$last_data = array_pop( $all_data );

		foreach ( (array) $messages as $message ) {
			$formatted = array(
				'code'    => $code,
				'message' => $message,
				'data'    => $last_data,
			);

			if ( $all_data ) {
				$formatted['additional_data'] = $all_data;
			}

			$errors[] = $formatted;
		}
	}

	$data = $errors[0];
	if ( count( $errors ) > 1 ) {
		// Remove the primary error.
		array_shift( $errors );
		$data['additional_errors'] = $errors;
	}

	return new WP_REST_Response( $data, $status );
}

/**
 * Checks whether a REST API endpoint request is currently being handled.
 *
 * This may be a standalone REST API request, or an internal request dispatched from within a regular page load.
 *
 * @since 6.5.0
 *
 * @global WP_REST_Server $wp_rest_server REST server instance.
 *
 * @return bool True if a REST endpoint request is currently being handled, false otherwise.
 */
function wp_is_rest_endpoint() {
	/* @var WP_REST_Server $wp_rest_server */
	global $wp_rest_server;

	// Check whether this is a standalone REST request.
	$is_rest_endpoint = wp_is_serving_rest_request();
	if ( ! $is_rest_endpoint ) {
		// Otherwise, check whether an internal REST request is currently being handled.
		$is_rest_endpoint = isset( $wp_rest_server )
			&& $wp_rest_server->is_dispatching();
	}

	/**
	 * Filters whether a REST endpoint request is currently being handled.
	 *
	 * This may be a standalone REST API request, or an internal request dispatched from within a regular page load.
	 *
	 * @since 6.5.0
	 *
	 * @param bool $is_request_endpoint Whether a REST endpoint request is currently being handled.
	 */
	return (bool) apply_filters( 'wp_is_rest_endpoint', $is_rest_endpoint );
}
