<?php
/**
 * REST API: Lớp WP_REST_Posts_Controller
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Lớp cốt lõi để truy cập bài viết qua REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_Posts_Controller extends WP_REST_Controller {
	/**
	 * Loại bài viết.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $post_type;

	/**
	 * Thể hiện của đối tượng trường meta bài viết.
	 *
	 * @since 4.7.0
	 * @var WP_REST_Post_Meta_Fields
	 */
	protected $meta;

	/**
	 * Cho phép truy cập bài viết không cần mật khẩu.
	 *
	 * @since 5.7.1
	 * @var int[]
	 */
	protected $password_check_passed = array();

	/**
	 * Liệu controller có hỗ trợ xử lý theo lô hay không.
	 *
	 * @since 5.9.0
	 * @var array
	 */
	protected $allow_batch = array( 'v1' => true );

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 4.7.0
	 *
	 * @param string $post_type Loại bài viết.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
		$obj             = get_post_type_object( $post_type );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
		$this->namespace = ! empty( $obj->rest_namespace ) ? $obj->rest_namespace : 'wp/v2';

		$this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );
	}

	/**
	 * Đăng ký các route cho bài viết.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		if ( isset( $schema['properties']['excerpt'] ) ) {
			$get_item_args['excerpt_length'] = array(
				'description' => __( 'Override the default excerpt length.' ),
				'type'        => 'integer',
			);
		}
		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => __( 'The password for the post if it is password protected.' ),
				'type'        => 'string',
			);
		}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'        => array(
					'id' => array(
						'description' => __( 'Unique identifier for the post.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass Trash and force deletion.' ),
						),
					),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Kiểm tra xem yêu cầu đã cho có quyền đọc bài viết hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Thông tin đầy đủ về yêu cầu.
	 * @return true|WP_Error True nếu yêu cầu có quyền đọc, đối tượng WP_Error nếu không.
	 */
	public function get_items_permissions_check( $request ) {

		$post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit posts in this post type.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Ghi đè kết quả kiểm tra mật khẩu bài viết cho các bài viết được yêu cầu qua REST.
	 *
	 * Cho phép người dùng đọc nội dung của bài viết được bảo vệ bằng mật khẩu nếu họ đã
	 * vượt qua kiểm tra quyền trước đó hoặc nếu họ có quyền `edit_post`
	 * cho bài viết đang được kiểm tra.
	 *
	 * @since 5.7.1
	 *
	 * @param bool    $required Liệu bài viết có yêu cầu kiểm tra mật khẩu hay không.
	 * @param WP_Post $post     Bài viết đang được kiểm tra mật khẩu.
	 * @return bool Kết quả kiểm tra mật khẩu có tính đến các cân nhắc REST API.
	 */
	public function check_password_required( $required, $post ) {
		if ( ! $required ) {
			return $required;
		}

		$post = get_post( $post );

		if ( ! $post ) {
			return $required;
		}

		if ( ! empty( $this->password_check_passed[ $post->ID ] ) ) {
			// Mật khẩu đã được kiểm tra và phê duyệt trước đó.
			return false;
		}

		return ! current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Lấy một tập hợp bài viết.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Thông tin đầy đủ về yêu cầu.
	 * @return WP_REST_Response|WP_Error Đối tượng phản hồi khi thành công, hoặc đối tượng WP_Error khi thất bại.
	 */
	public function get_items( $request ) {

		// Đảm bảo chuỗi tìm kiếm được thiết lập trong trường hợp orderby được đặt thành 'relevance'.
		if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {
			return new WP_Error(
				'rest_no_search_term_defined',
				__( 'You need to define a search term to order by relevance.' ),
				array( 'status' => 400 )
			);
		}

		// Đảm bảo tham số include được thiết lập trong trường hợp orderby được đặt thành 'include'.
		if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {
			return new WP_Error(
				'rest_orderby_include_missing_include',
				__( 'You need to define an include parameter to order by include.' ),
				array( 'status' => 400 )
			);
		}

		// Lấy danh sách các tham số truy vấn tập hợp đã đăng ký.
		$registered = $this->get_collection_params();
		$args       = array();

		/*
		 * Mảng này định nghĩa ánh xạ giữa các tham số truy vấn API công khai mà
		 * giá trị được chấp nhận nguyên trạng, và tên tham số WP_Query nội bộ
		 * tương ứng (một số giống nhau). Chỉ các giá trị cũng
		 * có trong $registered mới được thiết lập.
		 */
		$parameter_mappings = array(
			'author'         => 'author__in',
			'author_exclude' => 'author__not_in',
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'ignore_sticky'  => 'ignore_sticky_posts',
			'menu_order'     => 'menu_order',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'parent'         => 'post_parent__in',
			'parent_exclude' => 'post_parent__not_in',
			'search'         => 's',
			'search_columns' => 'search_columns',
			'slug'           => 'post_name__in',
			'status'         => 'post_status',
		);

		/*
		 * Với mỗi tham số đã biết vừa được đăng ký vừa có trong yêu cầu,
		 * thiết lập giá trị của tham số trên $args truy vấn.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Kiểm tra và gán các tham số cần xử lý hoặc thiết lập đặc biệt.
		$args['date_query'] = array();

		if ( isset( $registered['before'], $request['before'] ) ) {
			$args['date_query'][] = array(
				'before' => $request['before'],
				'column' => 'post_date',
			);
		}

		if ( isset( $registered['modified_before'], $request['modified_before'] ) ) {
			$args['date_query'][] = array(
				'before' => $request['modified_before'],
				'column' => 'post_modified',
			);
		}

		if ( isset( $registered['after'], $request['after'] ) ) {
			$args['date_query'][] = array(
				'after'  => $request['after'],
				'column' => 'post_date',
			);
		}

		if ( isset( $registered['modified_after'], $request['modified_after'] ) ) {
			$args['date_query'][] = array(
				'after'  => $request['modified_after'],
				'column' => 'post_modified',
			);
		}

		// Đảm bảo tham số per_page của chúng ta ghi đè bất kỳ bộ lọc posts_per_page nào được cung cấp.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

		if ( isset( $registered['sticky'], $request['sticky'] ) ) {
			$sticky_posts = get_option( 'sticky_posts', array() );
			if ( ! is_array( $sticky_posts ) ) {
				$sticky_posts = array();
			}
			if ( $request['sticky'] ) {
				/*
				 * Vì post__in sẽ được sử dụng để chỉ lấy bài viết ghim,
				 * chúng ta phải hỗ trợ trường hợp post__in đã được
				 * chỉ định trước đó.
				 */
				$args['post__in'] = $args['post__in'] ? array_intersect( $sticky_posts, $args['post__in'] ) : $sticky_posts;

				/*
				 * Nếu chúng ta đã giao nhau, nhưng không có ID bài viết chung nào,
				 * WP_Query sẽ không trả về "không có bài viết" cho post__in = array()
				 * vì vậy chúng ta phải giả lập điều đó.
				 */
				if ( ! $args['post__in'] ) {
					$args['post__in'] = array( 0 );
				}
			} elseif ( $sticky_posts ) {
				/*
				 * Vì post___not_in sẽ được sử dụng để chỉ lấy bài viết
				 * không ghim, chúng ta phải hỗ trợ trường hợp post__not_in
				 * đã được chỉ định trước đó.
				 */
				$args['post__not_in'] = array_merge( $args['post__not_in'], $sticky_posts );
			}
		}

		/*
		 * Tôn trọng hành vi `post__in` gốc của REST API. Không thêm bài viết ghim
		 * vào đầu khi `post__in` đã được chỉ định.
		 */
		if ( ! empty( $args['post__in'] ) ) {
			unset( $args['ignore_sticky_posts'] );
		}

		if (
			isset( $registered['search_semantics'], $request['search_semantics'] )
			&& 'exact' === $request['search_semantics']
		) {
			$args['exact'] = true;
		}

		$args = $this->prepare_tax_query( $args, $request );

		if ( isset( $registered['format'], $request['format'] ) ) {
			$formats = $request['format'];
			/*
			 * Quan hệ cần được đặt thành `OR` vì yêu cầu có thể chứa
			 * hai điều kiện riêng biệt. Người dùng có thể đang truy vấn các mục có
			 * định dạng `standard` hoặc một định dạng cụ thể.
			 */
			$formats_query = array( 'relation' => 'OR' );

			/*
			 * Định dạng bài viết mặc định, `standard`, không được lưu trong cơ sở dữ liệu.
			 * Nếu `standard` là một phần của yêu cầu, truy vấn cần loại trừ tất cả các mục bài viết
			 * đã được gán định dạng.
			 */
			if ( in_array( 'standard', $formats, true ) ) {
				$formats_query[] = array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'operator' => 'NOT EXISTS',
				);
				// Xóa định dạng `standard`, vì nó không thể truy vấn được.
				unset( $formats[ array_search( 'standard', $formats, true ) ] );
			}

			// Thêm các định dạng còn lại vào truy vấn định dạng.
			if ( ! empty( $formats ) ) {
				// Thêm tiền tố `post-format-`.
				$terms = array_map(
					static function ( $format ) {
						return "post-format-$format";
					},
					$formats
				);

				$formats_query[] = array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => $terms,
					'operator' => 'IN',
				);
			}

			// Cho phép lọc theo cả định dạng bài viết và các taxonomy khác bằng cách kết hợp chúng với `AND`.
			if ( isset( $args['tax_query'] ) ) {
				$args['tax_query'][] = array(
					'relation' => 'AND',
					$formats_query,
				);
			} else {
				$args['tax_query'] = $formats_query;
			}
		}

		// Buộc đối số post_type, vì nó không phải là biến đầu vào của người dùng.
		$args['post_type'] = $this->post_type;

		$is_head_request = $request->is_method( 'HEAD' );
		if ( $is_head_request ) {
			// Buộc đối số 'fields'. Đối với yêu cầu HEAD, chỉ cần ID bài viết để tính toán phân trang.
			$args['fields'] = 'ids';
			// Tắt nạp trước meta bài viết cho yêu cầu HEAD để cải thiện hiệu năng.
			$args['update_post_term_cache'] = false;
			$args['update_post_meta_cache'] = false;
		}

		/**
		 * Lọc các đối số WP_Query khi truy vấn bài viết qua REST API.
		 *
		 * Phần động của tên hook, `$this->post_type`, tham chiếu đến slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `rest_post_query`
		 *  - `rest_page_query`
		 *  - `rest_attachment_query`
		 *
		 * Cho phép thêm các đối số bổ sung hoặc thiết lập giá trị mặc định cho yêu cầu tập hợp bài viết.
		 *
		 * @since 4.7.0
		 * @since 5.7.0 Được chuyển sau khi đối số truy vấn `tax_query` được tạo.
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_query/
		 *
		 * @param array           $args    Mảng các đối số cho WP_Query.
		 * @param WP_REST_Request $request Yêu cầu REST API.
		 */
		$args       = apply_filters( "rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$posts_query  = new WP_Query();
		$query_result = $posts_query->query( $query_args );

		// Cho phép truy cập tất cả bài viết được bảo vệ bằng mật khẩu nếu ngữ cảnh là edit.
		if ( 'edit' === $request['context'] ) {
			add_filter( 'post_password_required', array( $this, 'check_password_required' ), 10, 2 );
		}

		if ( ! $is_head_request ) {
			$posts = array();

			update_post_author_caches( $query_result );
			update_post_parent_caches( $query_result );

			if ( post_type_supports( $this->post_type, 'thumbnail' ) ) {
				update_post_thumbnail_cache( $posts_query );
			}

			foreach ( $query_result as $post ) {
				if ( 'edit' === $request['context'] ) {
					$permission = $this->check_update_permission( $post );
				} else {
					$permission = $this->check_read_permission( $post );
				}

				if ( ! $permission ) {
					continue;
				}

				$data    = $this->prepare_item_for_response( $post, $request );
				$posts[] = $this->prepare_response_for_collection( $data );
			}
		}

		// Đặt lại bộ lọc.
		if ( 'edit' === $request['context'] ) {
			remove_filter( 'post_password_required', array( $this, 'check_password_required' ) );
		}

		$page        = isset( $query_args['paged'] ) ? (int) $query_args['paged'] : 0;
		$total_posts = $posts_query->found_posts;

		if ( $total_posts < 1 && $page > 1 ) {
			// Vượt quá giới hạn, chạy lại truy vấn không có LIMIT để đếm tổng số.
			unset( $query_args['paged'] );

			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		$max_pages = (int) ceil( $total_posts / (int) $posts_query->query_vars['posts_per_page'] );

		if ( $page > $max_pages && $total_posts > 0 ) {
			return new WP_Error(
				'rest_post_invalid_page_number',
				__( 'The page number requested is larger than the number of pages available.' ),
				array( 'status' => 400 )
			);
		}

		$response = $is_head_request ? new WP_REST_Response( array() ) : rest_ensure_response( $posts );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$collection_url = rest_url( rest_get_route_for_post_type_items( $this->post_type ) );
		$base           = add_query_arg( urlencode_deep( $request_params ), $collection_url );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Lấy bài viết, nếu ID hợp lệ.
	 *
	 * @since 4.7.2
	 *
	 * @param int $id ID được cung cấp.
	 * @return WP_Post|WP_Error Đối tượng bài viết nếu ID hợp lệ, WP_Error nếu không.
	 */
	protected function get_post( $id ) {
		$error = new WP_Error(
			'rest_post_invalid_id',
			__( 'Invalid post ID.' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );
		if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return $error;
		}

		return $post;
	}

	/**
	 * Kiểm tra xem yêu cầu đã cho có quyền đọc một bài viết hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return bool|WP_Error True nếu yêu cầu có quyền đọc mục, đối tượng WP_Error hoặc false nếu không.
	 */
	public function get_item_permissions_check( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( 'edit' === $request['context'] && $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( $post && ! empty( $request->get_query_params()['password'] ) ) {
			// Kiểm tra mật khẩu bài viết, và trả về lỗi nếu không hợp lệ.
			if ( ! hash_equals( $post->post_password, $request->get_query_params()['password'] ) ) {
				return new WP_Error(
					'rest_post_incorrect_password',
					__( 'Incorrect post password.' ),
					array( 'status' => 403 )
				);
			}
		}

		// Cho phép truy cập tất cả bài viết được bảo vệ bằng mật khẩu nếu ngữ cảnh là edit.
		if ( 'edit' === $request['context'] ) {
			add_filter( 'post_password_required', array( $this, 'check_password_required' ), 10, 2 );
		}

		if ( $post ) {
			return $this->check_read_permission( $post );
		}

		return true;
	}

	/**
	 * Kiểm tra xem người dùng có thể truy cập nội dung được bảo vệ bằng mật khẩu hay không.
	 *
	 * Phương thức này xác định xem chúng ta có cần ghi đè kiểm tra mật khẩu
	 * thông thường trong core bằng bộ lọc hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post         $post    Bài viết cần kiểm tra.
	 * @param WP_REST_Request $request Dữ liệu yêu cầu cần kiểm tra.
	 * @return bool True nếu người dùng có thể truy cập nội dung được bảo vệ bằng mật khẩu, ngược lại false.
	 */
	public function can_access_password_content( $post, $request ) {
		if ( empty( $post->post_password ) ) {
			// Không cần bộ lọc.
			return false;
		}

		/*
		 * Người dùng luôn có quyền truy cập nội dung được bảo vệ bằng mật khẩu trong ngữ cảnh
		 * edit nếu họ có meta capability `edit_post`.
		 */
		if (
			'edit' === $request['context'] &&
			current_user_can( 'edit_post', $post->ID )
		) {
			return true;
		}

		// Không có mật khẩu, không cần xác thực.
		if ( empty( $request['password'] ) ) {
			return false;
		}

		// Kiểm tra lại mật khẩu yêu cầu.
		return hash_equals( $post->post_password, $request['password'] );
	}

	/**
	 * Lấy một bài viết đơn lẻ.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return WP_REST_Response|WP_Error Đối tượng phản hồi khi thành công, hoặc đối tượng WP_Error khi thất bại.
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		if ( is_post_type_viewable( get_post_type_object( $post->post_type ) ) ) {
			$response->link_header( 'alternate', get_permalink( $post->ID ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Kiểm tra xem yêu cầu đã cho có quyền tạo bài viết hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return true|WP_Error True nếu yêu cầu có quyền tạo mục, đối tượng WP_Error nếu không.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'rest_post_exists',
				__( 'Cannot create existing post.' ),
				array( 'status' => 400 )
			);
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! empty( $request['author'] ) && get_current_user_id() !== $request['author'] && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to create posts as this user.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! empty( $request['sticky'] ) && ! current_user_can( $post_type->cap->edit_others_posts ) && ! current_user_can( $post_type->cap->publish_posts ) ) {
			return new WP_Error(
				'rest_cannot_assign_sticky',
				__( 'Sorry, you are not allowed to make posts sticky.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create posts as this user.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! $this->check_assign_terms_permission( $request ) ) {
			return new WP_Error(
				'rest_cannot_assign_term',
				__( 'Sorry, you are not allowed to assign the provided terms.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Tạo một bài viết đơn lẻ.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return WP_REST_Response|WP_Error Đối tượng phản hồi khi thành công, hoặc đối tượng WP_Error khi thất bại.
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'rest_post_exists',
				__( 'Cannot create existing post.' ),
				array( 'status' => 400 )
			);
		}

		$prepared_post = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$prepared_post->post_type = $this->post_type;

		if ( ! empty( $prepared_post->post_name )
			&& ! empty( $prepared_post->post_status )
			&& in_array( $prepared_post->post_status, array( 'draft', 'pending' ), true )
		) {
			/*
			 * `wp_unique_post_slug()` trả về cùng một slug cho bài viết 'draft' hoặc 'pending'.
			 *
			 * Để đảm bảo tạo ra slug duy nhất, truyền dữ liệu bài viết với trạng thái 'publish'.
			 */
			$prepared_post->post_name = wp_unique_post_slug(
				$prepared_post->post_name,
				$prepared_post->id,
				'publish',
				$prepared_post->post_type,
				$prepared_post->post_parent
			);
		}

		$post_id = wp_insert_post( wp_slash( (array) $prepared_post ), true, false );

		if ( is_wp_error( $post_id ) ) {

			if ( 'db_insert_error' === $post_id->get_error_code() ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}

			return $post_id;
		}

		$post = get_post( $post_id );

		/**
		 * Kích hoạt sau khi một bài viết đơn lẻ được tạo hoặc cập nhật qua REST API.
		 *
		 * Phần động của tên hook, `$this->post_type`, tham chiếu đến slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `rest_insert_post`
		 *  - `rest_insert_page`
		 *  - `rest_insert_attachment`
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Post         $post     Đối tượng bài viết đã chèn hoặc cập nhật.
		 * @param WP_REST_Request $request  Đối tượng yêu cầu.
		 * @param bool            $creating True khi tạo bài viết, false khi cập nhật.
		 */
		do_action( "rest_insert_{$this->post_type}", $post, $request, true );

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['sticky'] ) ) {
			if ( ! empty( $request['sticky'] ) ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}

		if ( ! empty( $schema['properties']['featured_media'] ) && isset( $request['featured_media'] ) ) {
			$this->handle_featured_media( $request['featured_media'], $post_id );
		}

		if ( ! empty( $schema['properties']['format'] ) && ! empty( $request['format'] ) ) {
			set_post_format( $post, $request['format'] );
		}

		if ( ! empty( $schema['properties']['template'] ) && isset( $request['template'] ) ) {
			$this->handle_template( $request['template'], $post_id, true );
		}

		$terms_update = $this->handle_terms( $post_id, $request );

		if ( is_wp_error( $terms_update ) ) {
			return $terms_update;
		}

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $post_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$post          = get_post( $post_id );
		$fields_update = $this->update_additional_fields_for_object( $post, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/**
		 * Kích hoạt sau khi một bài viết đơn lẻ được tạo hoặc cập nhật hoàn toàn qua REST API.
		 *
		 * Phần động của tên hook, `$this->post_type`, tham chiếu đến slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `rest_after_insert_post`
		 *  - `rest_after_insert_page`
		 *  - `rest_after_insert_attachment`
		 *
		 * @since 5.0.0
		 *
		 * @param WP_Post         $post     Đối tượng bài viết đã chèn hoặc cập nhật.
		 * @param WP_REST_Request $request  Đối tượng yêu cầu.
		 * @param bool            $creating True khi tạo bài viết, false khi cập nhật.
		 */
		do_action( "rest_after_insert_{$this->post_type}", $post, $request, true );

		wp_after_insert_post( $post, false, null );

		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( rest_get_route_for_post( $post ) ) );

		return $response;
	}

	/**
	 * Kiểm tra xem yêu cầu đã cho có quyền cập nhật bài viết hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return true|WP_Error True nếu yêu cầu có quyền cập nhật mục, đối tượng WP_Error nếu không.
	 */
	public function update_item_permissions_check( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! empty( $request['author'] ) && get_current_user_id() !== $request['author'] && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to update posts as this user.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! empty( $request['sticky'] ) && ! current_user_can( $post_type->cap->edit_others_posts ) && ! current_user_can( $post_type->cap->publish_posts ) ) {
			return new WP_Error(
				'rest_cannot_assign_sticky',
				__( 'Sorry, you are not allowed to make posts sticky.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! $this->check_assign_terms_permission( $request ) ) {
			return new WP_Error(
				'rest_cannot_assign_term',
				__( 'Sorry, you are not allowed to assign the provided terms.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Cập nhật một bài viết đơn lẻ.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return WP_REST_Response|WP_Error Đối tượng phản hồi khi thành công, hoặc đối tượng WP_Error khi thất bại.
	 */
	public function update_item( $request ) {
		$valid_check = $this->get_post( $request['id'] );
		if ( is_wp_error( $valid_check ) ) {
			return $valid_check;
		}

		$post_before = get_post( $request['id'] );
		$post        = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! empty( $post->post_status ) ) {
			$post_status = $post->post_status;
		} else {
			$post_status = $post_before->post_status;
		}

		/*
		 * `wp_unique_post_slug()` trả về cùng một slug cho bài viết 'draft' hoặc 'pending'.
		 *
		 * Để đảm bảo tạo ra slug duy nhất, truyền dữ liệu bài viết với trạng thái 'publish'.
		 */
		if ( ! empty( $post->post_name ) && in_array( $post_status, array( 'draft', 'pending' ), true ) ) {
			$post_parent     = ! empty( $post->post_parent ) ? $post->post_parent : 0;
			$post->post_name = wp_unique_post_slug(
				$post->post_name,
				$post->ID,
				'publish',
				$post->post_type,
				$post_parent
			);
		}

		// Chuyển đổi đối tượng bài viết thành mảng, nếu không wp_update_post() sẽ yêu cầu dữ liệu đầu vào chưa được escape.
		$post_id = wp_update_post( wp_slash( (array) $post ), true, false );

		if ( is_wp_error( $post_id ) ) {
			if ( 'db_update_error' === $post_id->get_error_code() ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}

		$post = get_post( $post_id );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php */
		do_action( "rest_insert_{$this->post_type}", $post, $request, false );

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['format'] ) && ! empty( $request['format'] ) ) {
			set_post_format( $post, $request['format'] );
		}

		if ( ! empty( $schema['properties']['featured_media'] ) && isset( $request['featured_media'] ) ) {
			$this->handle_featured_media( $request['featured_media'], $post_id );
		}

		if ( ! empty( $schema['properties']['sticky'] ) && isset( $request['sticky'] ) ) {
			if ( ! empty( $request['sticky'] ) ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}

		if ( ! empty( $schema['properties']['template'] ) && isset( $request['template'] ) ) {
			$this->handle_template( $request['template'], $post->ID );
		}

		$terms_update = $this->handle_terms( $post->ID, $request );

		if ( is_wp_error( $terms_update ) ) {
			return $terms_update;
		}

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $post->ID );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$post          = get_post( $post_id );
		$fields_update = $this->update_additional_fields_for_object( $post, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		// Bộ lọc được kích hoạt trong lớp con WP_REST_Attachments_Controller.
		if ( 'attachment' === $this->post_type ) {
			$response = $this->prepare_item_for_response( $post, $request );
			return rest_ensure_response( $response );
		}

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php */
		do_action( "rest_after_insert_{$this->post_type}", $post, $request, false );

		wp_after_insert_post( $post, true, $post_before );

		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Kiểm tra xem yêu cầu đã cho có quyền xóa bài viết hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return true|WP_Error True nếu yêu cầu có quyền xóa mục, đối tượng WP_Error nếu không.
	 */
	public function delete_item_permissions_check( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( $post && ! $this->check_delete_permission( $post ) ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Xóa một bài viết đơn lẻ.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Chi tiết đầy đủ về yêu cầu.
	 * @return WP_REST_Response|WP_Error Đối tượng phản hồi khi thành công, hoặc đối tượng WP_Error khi thất bại.
	 */
	public function delete_item( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$id    = $post->ID;
		$force = (bool) $request['force'];

		$supports_trash = ( EMPTY_TRASH_DAYS > 0 );

		if ( 'attachment' === $post->post_type ) {
			$supports_trash = $supports_trash && MEDIA_TRASH;
		}

		/**
		 * Lọc xem bài viết có thể cho vào thùng rác hay không.
		 *
		 * Phần động của tên hook, `$this->post_type`, tham chiếu đến slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `rest_post_trashable`
		 *  - `rest_page_trashable`
		 *  - `rest_attachment_trashable`
		 *
		 * Trả về false để tắt hỗ trợ Thùng rác cho bài viết.
		 *
		 * @since 4.7.0
		 *
		 * @param bool    $supports_trash Liệu loại bài viết có hỗ trợ cho vào thùng rác hay không.
		 * @param WP_Post $post           Đối tượng Bài viết đang được xem xét hỗ trợ thùng rác.
		 */
		$supports_trash = apply_filters( "rest_{$this->post_type}_trashable", $supports_trash, $post );

		if ( ! $this->check_delete_permission( $post ) ) {
			return new WP_Error(
				'rest_user_cannot_delete_post',
				__( 'Sorry, you are not allowed to delete this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$request->set_param( 'context', 'edit' );

		// Nếu chúng ta đang buộc xóa, thì xóa vĩnh viễn.
		if ( $force ) {
			$previous = $this->prepare_item_for_response( $post, $request );
			$result   = wp_delete_post( $id, true );
			$response = new WP_REST_Response();
			$response->set_data(
				array(
					'deleted'  => true,
					'previous' => $previous->get_data(),
				)
			);
		} else {
			// Nếu chúng ta không hỗ trợ thùng rác cho loại này, trả về lỗi.
			if ( ! $supports_trash ) {
				return new WP_Error(
					'rest_trash_not_supported',
					/* translators: %s: force=true */
					sprintf( __( "The post does not support trashing. Set '%s' to delete." ), 'force=true' ),
					array( 'status' => 501 )
				);
			}

			// Nếu không, chỉ cho vào thùng rác nếu chưa làm trước đó.
			if ( 'trash' === $post->post_status ) {
				return new WP_Error(
					'rest_already_trashed',
					__( 'The post has already been deleted.' ),
					array( 'status' => 410 )
				);
			}

			/*
			 * (Lưu ý rằng nội bộ sẽ chuyển sang `wp_delete_post()`
			 * nếu Thùng rác bị tắt.)
			 */
			$result   = wp_trash_post( $id );
			$post     = get_post( $id );
			$response = $this->prepare_item_for_response( $post, $request );
		}

		if ( ! $result ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'The post cannot be deleted.' ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Kích hoạt ngay sau khi một bài viết đơn lẻ bị xóa hoặc cho vào thùng rác qua REST API.
		 *
		 * Phần động của tên hook, `$this->post_type`, tham chiếu đến slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `rest_delete_post`
		 *  - `rest_delete_page`
		 *  - `rest_delete_attachment`
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Post          $post     Bài viết đã bị xóa hoặc cho vào thùng rác.
		 * @param WP_REST_Response $response Dữ liệu phản hồi.
		 * @param WP_REST_Request  $request  Yêu cầu được gửi đến API.
		 */
		do_action( "rest_delete_{$this->post_type}", $post, $response, $request );

		return $response;
	}

	/**
	 * Xác định các query_vars được phép cho phản hồi get_items() và chuẩn bị
	 * chúng cho WP_Query.
	 *
	 * @since 4.7.0
	 *
	 * @param array           $prepared_args Tùy chọn. Các đối số WP_Query đã chuẩn bị. Mặc định mảng rỗng.
	 * @param WP_REST_Request $request       Tùy chọn. Chi tiết đầy đủ về yêu cầu.
	 * @return array Các đối số truy vấn mục.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			/**
			 * Lọc các query_vars được sử dụng trong get_items() cho truy vấn đã xây dựng.
			 *
			 * Phần động của tên hook, `$key`, tham chiếu đến khóa query_var.
			 *
			 * @since 4.7.0
			 *
			 * @param string $value Giá trị query_var.
			 */
			$query_args[ $key ] = apply_filters( "rest_query_var-{$key}", $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		if ( 'post' !== $this->post_type || ! isset( $query_args['ignore_sticky_posts'] ) ) {
			$query_args['ignore_sticky_posts'] = true;
		}

		// Ánh xạ sang tham số orderby đúng của WP_Query.
		if ( isset( $query_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'id'            => 'ID',
				'include'       => 'post__in',
				'slug'          => 'post_name',
				'include_slugs' => 'post_name__in',
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$query_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		return $query_args;
	}

	/**
	 * Kiểm tra post_date_gmt hoặc modified_gmt và chuẩn bị bất kỳ ngày đăng hoặc
	 * ngày chỉnh sửa nào cho đầu ra bài viết đơn lẻ.
	 *
	 * @since 4.7.0
	 *
	 * @param string      $date_gmt Thời gian xuất bản theo GMT.
	 * @param string|null $date     Tùy chọn. Thời gian xuất bản theo giờ địa phương. Mặc định null.
	 * @return string|null Ngày giờ được định dạng ISO8601/RFC3339.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Sử dụng ngày nếu được truyền vào.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date );
		}

		// Trả về null nếu $date_gmt rỗng hoặc toàn số không.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Trả về ngày giờ đã được định dạng.
		return mysql_to_rfc3339( $date_gmt );
	}

	/**
	 * Chuẩn bị một bài viết đơn lẻ để tạo hoặc cập nhật.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Đối tượng yêu cầu.
	 * @return stdClass|WP_Error Đối tượng bài viết hoặc WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_post  = new stdClass();
		$current_status = '';

		// ID bài viết.
		if ( isset( $request['id'] ) ) {
			$existing_post = $this->get_post( $request['id'] );
			if ( is_wp_error( $existing_post ) ) {
				return $existing_post;
			}

			$prepared_post->ID = $existing_post->ID;
			$current_status    = $existing_post->post_status;
		}

		$schema = $this->get_item_schema();

		// Tiêu đề bài viết.
		if ( ! empty( $schema['properties']['title'] ) && isset( $request['title'] ) ) {
			if ( is_string( $request['title'] ) ) {
				$prepared_post->post_title = $request['title'];
			} elseif ( ! empty( $request['title']['raw'] ) ) {
				$prepared_post->post_title = $request['title']['raw'];
			}
		}

		// Nội dung bài viết.
		if ( ! empty( $schema['properties']['content'] ) && isset( $request['content'] ) ) {
			if ( is_string( $request['content'] ) ) {
				$prepared_post->post_content = $request['content'];
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_post->post_content = $request['content']['raw'];
			}
		}

		// Tóm tắt bài viết.
		if ( ! empty( $schema['properties']['excerpt'] ) && isset( $request['excerpt'] ) ) {
			if ( is_string( $request['excerpt'] ) ) {
				$prepared_post->post_excerpt = $request['excerpt'];
			} elseif ( isset( $request['excerpt']['raw'] ) ) {
				$prepared_post->post_excerpt = $request['excerpt']['raw'];
			}
		}

		// Loại bài viết.
		if ( empty( $request['id'] ) ) {
			// Tạo bài viết mới, sử dụng loại mặc định cho controller.
			$prepared_post->post_type = $this->post_type;
		} else {
			// Cập nhật bài viết, sử dụng loại trước đó.
			$prepared_post->post_type = get_post_type( $request['id'] );
		}

		$post_type = get_post_type_object( $prepared_post->post_type );

		// Trạng thái bài viết.
		if (
			! empty( $schema['properties']['status'] ) &&
			isset( $request['status'] ) &&
			( ! $current_status || $current_status !== $request['status'] )
		) {
			$status = $this->handle_status_param( $request['status'], $post_type );

			if ( is_wp_error( $status ) ) {
				return $status;
			}

			$prepared_post->post_status = $status;
		}

		// Ngày đăng bài viết.
		if ( ! empty( $schema['properties']['date'] ) && ! empty( $request['date'] ) ) {
			$current_date = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID )->post_date : false;
			$date_data    = rest_get_date_with_gmt( $request['date'] );

			if ( ! empty( $date_data ) && $current_date !== $date_data[0] ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
				$prepared_post->edit_date                                        = true;
			}
		} elseif ( ! empty( $schema['properties']['date_gmt'] ) && ! empty( $request['date_gmt'] ) ) {
			$current_date = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID )->post_date_gmt : false;
			$date_data    = rest_get_date_with_gmt( $request['date_gmt'], true );

			if ( ! empty( $date_data ) && $current_date !== $date_data[1] ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
				$prepared_post->edit_date                                        = true;
			}
		}

		/*
		 * Gửi giá trị null cho date hoặc date_gmt sẽ đặt lại date và date_gmt về
		 * giá trị mặc định (`0000-00-00 00:00:00`).
		 */
		if (
			( ! empty( $schema['properties']['date_gmt'] ) && $request->has_param( 'date_gmt' ) && null === $request['date_gmt'] ) ||
			( ! empty( $schema['properties']['date'] ) && $request->has_param( 'date' ) && null === $request['date'] )
		) {
			$prepared_post->post_date_gmt = null;
			$prepared_post->post_date     = null;
		}

		// Slug bài viết.
		if ( ! empty( $schema['properties']['slug'] ) && isset( $request['slug'] ) ) {
			$prepared_post->post_name = $request['slug'];
		}

		// Tác giả.
		if ( ! empty( $schema['properties']['author'] ) && ! empty( $request['author'] ) ) {
			$post_author = (int) $request['author'];

			if ( get_current_user_id() !== $post_author ) {
				$user_obj = get_userdata( $post_author );

				if ( ! $user_obj ) {
					return new WP_Error(
						'rest_invalid_author',
						__( 'Invalid author ID.' ),
						array( 'status' => 400 )
					);
				}
			}

			$prepared_post->post_author = $post_author;
		}

		// Mật khẩu bài viết.
		if ( ! empty( $schema['properties']['password'] ) && isset( $request['password'] ) ) {
			$prepared_post->post_password = $request['password'];

			if ( '' !== $request['password'] ) {
				if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
					return new WP_Error(
						'rest_invalid_field',
						__( 'A post can not be sticky and have a password.' ),
						array( 'status' => 400 )
					);
				}

				if ( ! empty( $prepared_post->ID ) && is_sticky( $prepared_post->ID ) ) {
					return new WP_Error(
						'rest_invalid_field',
						__( 'A sticky post can not be password protected.' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
			if ( ! empty( $prepared_post->ID ) && post_password_required( $prepared_post->ID ) ) {
				return new WP_Error(
					'rest_invalid_field',
					__( 'A password protected post can not be set to sticky.' ),
					array( 'status' => 400 )
				);
			}
		}

		// Bài viết cha.
		if ( ! empty( $schema['properties']['parent'] ) && isset( $request['parent'] ) ) {
			if ( 0 === (int) $request['parent'] ) {
				$prepared_post->post_parent = 0;
			} else {
				$parent = get_post( (int) $request['parent'] );

				if ( empty( $parent ) ) {
					return new WP_Error(
						'rest_post_invalid_id',
						__( 'Invalid post parent ID.' ),
						array( 'status' => 400 )
					);
				}

				$prepared_post->post_parent = (int) $parent->ID;
			}
		}

		// Thứ tự menu.
		if ( ! empty( $schema['properties']['menu_order'] ) && isset( $request['menu_order'] ) ) {
			$prepared_post->menu_order = (int) $request['menu_order'];
		}

		// Trạng thái bình luận.
		if ( ! empty( $schema['properties']['comment_status'] ) && ! empty( $request['comment_status'] ) ) {
			$prepared_post->comment_status = $request['comment_status'];
		}

		// Trạng thái ping.
		if ( ! empty( $schema['properties']['ping_status'] ) && ! empty( $request['ping_status'] ) ) {
			$prepared_post->ping_status = $request['ping_status'];
		}

		if ( ! empty( $schema['properties']['template'] ) ) {
			// Buộc template về null để nó có thể được xử lý riêng bởi REST controller.
			$prepared_post->page_template = null;
		}

		/**
		 * Lọc bài viết trước khi nó được chèn qua REST API.
		 *
		 * Phần động của tên hook, `$this->post_type`, tham chiếu đến slug loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `rest_pre_insert_post`
		 *  - `rest_pre_insert_page`
		 *  - `rest_pre_insert_attachment`
		 *
		 * @since 4.7.0
		 *
		 * @param stdClass        $prepared_post Đối tượng đại diện cho một bài viết đơn lẻ đã chuẩn bị
		 *                                       để chèn hoặc cập nhật cơ sở dữ liệu.
		 * @param WP_REST_Request $request       Đối tượng yêu cầu.
		 */
		return apply_filters( "rest_pre_insert_{$this->post_type}", $prepared_post, $request );
	}

	/**
	 * Kiểm tra xem trạng thái có hợp lệ cho bài viết đã cho hay không.
	 *
	 * Cho phép gửi yêu cầu cập nhật với trạng thái hiện tại, ngay cả khi trạng thái đó thường không được chấp nhận.
	 *
	 * @since 5.6.0
	 *
	 * @param string          $status  Trạng thái được cung cấp.
	 * @param WP_REST_Request $request Đối tượng yêu cầu.
	 * @param string          $param   Tên tham số.
	 * @return true|WP_Error True nếu trạng thái hợp lệ, hoặc WP_Error nếu không.
	 */
	public function check_status( $status, $request, $param ) {
		if ( $request['id'] ) {
			$post = $this->get_post( $request['id'] );

			if ( ! is_wp_error( $post ) && $post->post_status === $status ) {
				return true;
			}
		}

		$args = $request->get_attributes()['args'][ $param ];

		return rest_validate_value_from_schema( $status, $args, $param );
	}

	/**
	 * Xác định tính hợp lệ và chuẩn hóa tham số trạng thái đã cho.
	 *
	 * @since 4.7.0
	 *
	 * @param string       $post_status Trạng thái bài viết.
	 * @param WP_Post_Type $post_type   Loại bài viết.
	 * @return string|WP_Error Trạng thái bài viết hoặc WP_Error nếu thiếu quyền phù hợp.
	 */
	protected function handle_status_param( $post_status, $post_type ) {

		switch ( $post_status ) {
			case 'draft':
			case 'pending':
				break;
			case 'private':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new WP_Error(
						'rest_cannot_publish',
						__( 'Sorry, you are not allowed to create private posts in this post type.' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				break;
			case 'publish':
			case 'future':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new WP_Error(
						'rest_cannot_publish',
						__( 'Sorry, you are not allowed to publish posts in this post type.' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				break;
			default:
				if ( ! get_post_status_object( $post_status ) ) {
					$post_status = 'draft';
				}
				break;
		}

		return $post_status;
	}

	/**
	 * Xác định media nổi bật dựa trên tham số yêu cầu.
	 *
	 * @since 4.7.0
	 *
	 * @param int $featured_media ID media nổi bật.
	 * @param int $post_id        ID bài viết.
	 * @return bool|WP_Error Liệu ảnh đại diện bài viết có được xóa thành công hay không, nếu không là WP_Error.
	 */
	protected function handle_featured_media( $featured_media, $post_id ) {

		$featured_media = (int) $featured_media;
		if ( $featured_media ) {
			$result = set_post_thumbnail( $post_id, $featured_media );
			if ( $result ) {
				return true;
			} else {
				return new WP_Error(
					'rest_invalid_featured_media',
					__( 'Invalid featured media ID.' ),
					array( 'status' => 400 )
				);
			}
		} else {
			return delete_post_thumbnail( $post_id );
		}
	}

	/**
	 * Kiểm tra xem template có hợp lệ cho bài viết đã cho hay không.
	 *
	 * @since 4.9.0
	 *
	 * @param string          $template Tên tệp template trang.
	 * @param WP_REST_Request $request  Yêu cầu.
	 * @return true|WP_Error True nếu template vẫn hợp lệ hoặc giống giá trị hiện tại, hoặc WP_Error nếu template không được hỗ trợ.
	 */
	public function check_template( $template, $request ) {

		if ( ! $template ) {
			return true;
		}

		if ( $request['id'] ) {
			$post             = get_post( $request['id'] );
			$current_template = get_page_template_slug( $request['id'] );
		} else {
			$post             = null;
			$current_template = '';
		}

		// Luôn cho phép cập nhật bài viết với cùng template, ngay cả khi template đó không còn được hỗ trợ.
		if ( $template === $current_template ) {
			return true;
		}

		// Nếu đây là yêu cầu tạo mới, get_post() sẽ trả về null và giao diện wp sẽ dùng loại bài viết được truyền vào.
		$allowed_templates = wp_get_theme()->get_page_templates( $post, $this->post_type );

		if ( isset( $allowed_templates[ $template ] ) ) {
			return true;
		}

		return new WP_Error(
			'rest_invalid_param',
			/* translators: 1: Parameter, 2: List of valid values. */
			sprintf( __( '%1$s is not one of %2$s.' ), 'template', implode( ', ', array_keys( $allowed_templates ) ) )
		);
	}

	/**
	 * Đặt template cho bài viết.
	 *
	 * @since 4.7.0
	 * @since 4.9.0 Thêm tham số `$validate`.
	 *
	 * @param string $template Tên tệp template trang.
	 * @param int    $post_id  ID bài viết.
	 * @param bool   $validate Có kiểm tra tính hợp lệ của template được chọn hay không.
	 */
	public function handle_template( $template, $post_id, $validate = false ) {

		if ( $validate && ! array_key_exists( $template, wp_get_theme()->get_page_templates( get_post( $post_id ) ) ) ) {
			$template = '';
		}

		update_post_meta( $post_id, '_wp_page_template', $template );
	}

	/**
	 * Cập nhật các term của bài viết từ yêu cầu REST.
	 *
	 * @since 4.7.0
	 *
	 * @param int             $post_id ID bài viết cần cập nhật form các term.
	 * @param WP_REST_Request $request Đối tượng yêu cầu với dữ liệu bài viết và term.
	 * @return null|WP_Error WP_Error khi có lỗi gán bất kỳ term nào, ngược lại null.
	 */
	protected function handle_terms( $post_id, $request ) {
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			$result = wp_set_object_terms( $post_id, $request[ $base ], $taxonomy->name );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * Kiểm tra xem người dùng hiện tại có thể gán tất cả các term được gửi cùng yêu cầu hay không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Đối tượng yêu cầu với dữ liệu bài viết và term.
	 * @return bool Liệu người dùng hiện tại có thể gán các term được cung cấp hay không.
	 */
	protected function check_assign_terms_permission( $request ) {
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			foreach ( (array) $request[ $base ] as $term_id ) {
				// Các term không hợp lệ sẽ bị từ chối sau.
				if ( ! get_term( $term_id, $taxonomy->name ) ) {
					continue;
				}

				if ( ! current_user_can( 'assign_term', (int) $term_id ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Kiểm tra xem loại bài viết đã cho có thể xem hoặc quản lý được không.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post_Type|string $post_type Tên hoặc đối tượng loại bài viết.
	 * @return bool Liệu loại bài viết có được phép trong REST hay không.
	 */
	protected function check_is_post_type_allowed( $post_type ) {
		if ( ! is_object( $post_type ) ) {
			$post_type = get_post_type_object( $post_type );
		}

		if ( ! empty( $post_type ) && ! empty( $post_type->show_in_rest ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Kiểm tra xem bài viết có thể đọc được không.
	 *
	 * Xử lý chính xác các bài viết có trạng thái inherit.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Đối tượng bài viết.
	 * @return bool Liệu bài viết có thể đọc được hay không.
	 */
	public function check_read_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );
		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		// Is the post readable?
		if ( 'publish' === $post->post_status || current_user_can( 'read_post', $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );
			if ( $parent ) {
				return $this->check_read_permission( $parent );
			}
		}

		/*
		 * If there isn't a parent, but the status is set to inherit, assume
		 * it's published (as per get_post_status()).
		 */
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a post can be edited.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be edited.
	 */
	protected function check_update_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Checks if a post can be created.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be created.
	 */
	protected function check_create_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		return current_user_can( $post_type->cap->create_posts );
	}

	/**
	 * Checks if a post can be deleted.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be deleted.
	 */
	protected function check_delete_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		return current_user_can( 'delete_post', $post->ID );
	}

	/**
	 * Prepares a single post output for response.
	 *
	 * @since 4.7.0
	 * @since 5.9.0 Renamed `$post` to `$item` to match parent class for PHP 8 named parameter support.
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @param WP_Post         $item    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		// Restores the more descriptive, specific name for use within this method.
		$post = $item;

		$GLOBALS['post'] = $post;

		setup_postdata( $post );

		// Don't prepare the response body for HEAD requests.
		if ( $request->is_method( 'HEAD' ) ) {
			/** This filter is documented in wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php */
			return apply_filters( "rest_prepare_{$this->post_type}", new WP_REST_Response( array() ), $post, $request );
		}

		$fields = $this->get_fields_for_response( $request );

		// Base fields for every post.
		$data = array();

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $post->ID;
		}

		if ( rest_is_field_included( 'date', $fields ) ) {
			$data['date'] = $this->prepare_date_response( $post->post_date_gmt, $post->post_date );
		}

		if ( rest_is_field_included( 'date_gmt', $fields ) ) {
			/*
			 * For drafts, `post_date_gmt` may not be set, indicating that the date
			 * of the draft should be updated each time it is saved (see #38883).
			 * In this case, shim the value based on the `post_date` field
			 * with the site's timezone offset applied.
			 */
			if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
				$post_date_gmt = get_gmt_from_date( $post->post_date );
			} else {
				$post_date_gmt = $post->post_date_gmt;
			}
			$data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );
		}

		if ( rest_is_field_included( 'guid', $fields ) ) {
			$data['guid'] = array(
				/** This filter is documented in wp-includes/post-template.php */
				'rendered' => apply_filters( 'get_the_guid', $post->guid, $post->ID ),
				'raw'      => $post->guid,
			);
		}

		if ( rest_is_field_included( 'modified', $fields ) ) {
			$data['modified'] = $this->prepare_date_response( $post->post_modified_gmt, $post->post_modified );
		}

		if ( rest_is_field_included( 'modified_gmt', $fields ) ) {
			/*
			 * For drafts, `post_modified_gmt` may not be set (see `post_date_gmt` comments
			 * above). In this case, shim the value based on the `post_modified` field
			 * with the site's timezone offset applied.
			 */
			if ( '0000-00-00 00:00:00' === $post->post_modified_gmt ) {
				$post_modified_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified ) - (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
			} else {
				$post_modified_gmt = $post->post_modified_gmt;
			}
			$data['modified_gmt'] = $this->prepare_date_response( $post_modified_gmt );
		}

		if ( rest_is_field_included( 'password', $fields ) ) {
			$data['password'] = $post->post_password;
		}

		if ( rest_is_field_included( 'slug', $fields ) ) {
			$data['slug'] = $post->post_name;
		}

		if ( rest_is_field_included( 'status', $fields ) ) {
			$data['status'] = $post->post_status;
		}

		if ( rest_is_field_included( 'type', $fields ) ) {
			$data['type'] = $post->post_type;
		}

		if ( rest_is_field_included( 'link', $fields ) ) {
			$data['link'] = get_permalink( $post->ID );
		}

		if ( rest_is_field_included( 'title', $fields ) ) {
			$data['title'] = array();
		}
		if ( rest_is_field_included( 'title.raw', $fields ) ) {
			$data['title']['raw'] = $post->post_title;
		}
		if ( rest_is_field_included( 'title.rendered', $fields ) ) {
			add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
			add_filter( 'private_title_format', array( $this, 'protected_title_format' ) );

			$data['title']['rendered'] = get_the_title( $post->ID );

			remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
			remove_filter( 'private_title_format', array( $this, 'protected_title_format' ) );
		}

		$has_password_filter = false;

		if ( $this->can_access_password_content( $post, $request ) ) {
			$this->password_check_passed[ $post->ID ] = true;
			// Allow access to the post, permissions already checked before.
			add_filter( 'post_password_required', array( $this, 'check_password_required' ), 10, 2 );

			$has_password_filter = true;
		}

		if ( rest_is_field_included( 'content', $fields ) ) {
			$data['content'] = array();
		}
		if ( rest_is_field_included( 'content.raw', $fields ) ) {
			$data['content']['raw'] = $post->post_content;
		}
		if ( rest_is_field_included( 'content.rendered', $fields ) ) {
			/** This filter is documented in wp-includes/post-template.php */
			$data['content']['rendered'] = post_password_required( $post ) ? '' : apply_filters( 'the_content', $post->post_content );
		}
		if ( rest_is_field_included( 'content.protected', $fields ) ) {
			$data['content']['protected'] = (bool) $post->post_password;
		}
		if ( rest_is_field_included( 'content.block_version', $fields ) ) {
			$data['content']['block_version'] = block_version( $post->post_content );
		}

		if ( rest_is_field_included( 'excerpt', $fields ) ) {
			if ( isset( $request['excerpt_length'] ) ) {
				$excerpt_length          = $request['excerpt_length'];
				$override_excerpt_length = static function () use ( $excerpt_length ) {
					return $excerpt_length;
				};

				add_filter(
					'excerpt_length',
					$override_excerpt_length,
					20
				);
			}

			/** This filter is documented in wp-includes/post-template.php */
			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );

			/** This filter is documented in wp-includes/post-template.php */
			$excerpt = apply_filters( 'the_excerpt', $excerpt );

			$data['excerpt'] = array(
				'raw'       => $post->post_excerpt,
				'rendered'  => post_password_required( $post ) ? '' : $excerpt,
				'protected' => (bool) $post->post_password,
			);

			if ( isset( $override_excerpt_length ) ) {
				remove_filter(
					'excerpt_length',
					$override_excerpt_length,
					20
				);
			}
		}

		if ( $has_password_filter ) {
			// Reset filter.
			remove_filter( 'post_password_required', array( $this, 'check_password_required' ) );
		}

		if ( rest_is_field_included( 'author', $fields ) ) {
			$data['author'] = (int) $post->post_author;
		}

		if ( rest_is_field_included( 'featured_media', $fields ) ) {
			$data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
		}

		if ( rest_is_field_included( 'parent', $fields ) ) {
			$data['parent'] = (int) $post->post_parent;
		}

		if ( rest_is_field_included( 'menu_order', $fields ) ) {
			$data['menu_order'] = (int) $post->menu_order;
		}

		if ( rest_is_field_included( 'comment_status', $fields ) ) {
			$data['comment_status'] = $post->comment_status;
		}

		if ( rest_is_field_included( 'ping_status', $fields ) ) {
			$data['ping_status'] = $post->ping_status;
		}

		if ( rest_is_field_included( 'sticky', $fields ) ) {
			$data['sticky'] = is_sticky( $post->ID );
		}

		if ( rest_is_field_included( 'template', $fields ) ) {
			$template = get_page_template_slug( $post->ID );
			if ( $template ) {
				$data['template'] = $template;
			} else {
				$data['template'] = '';
			}
		}

		if ( rest_is_field_included( 'format', $fields ) ) {
			$data['format'] = get_post_format( $post->ID );

			// Fill in blank post format.
			if ( empty( $data['format'] ) ) {
				$data['format'] = 'standard';
			}
		}

		if ( rest_is_field_included( 'meta', $fields ) ) {
			$data['meta'] = $this->meta->get_value( $post->ID, $request );
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( rest_is_field_included( $base, $fields ) ) {
				$terms         = get_the_terms( $post, $taxonomy->name );
				$data[ $base ] = $terms ? array_values( wp_list_pluck( $terms, 'term_id' ) ) : array();
			}
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( is_post_type_viewable( $post_type_obj ) && $post_type_obj->public ) {
			$permalink_template_requested = rest_is_field_included( 'permalink_template', $fields );
			$generated_slug_requested     = rest_is_field_included( 'generated_slug', $fields );

			if ( $permalink_template_requested || $generated_slug_requested ) {
				if ( ! function_exists( 'get_sample_permalink' ) ) {
					require_once ABSPATH . 'wp-admin/includes/post.php';
				}

				$sample_permalink = get_sample_permalink( $post->ID, $post->post_title, '' );

				if ( $permalink_template_requested ) {
					$data['permalink_template'] = $sample_permalink[0];
				}

				if ( $generated_slug_requested ) {
					$data['generated_slug'] = $sample_permalink[1];
				}
			}

			if ( rest_is_field_included( 'class_list', $fields ) ) {
				$data['class_list'] = get_post_class( array(), $post->ID );
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		if ( rest_is_field_included( '_links', $fields ) || rest_is_field_included( '_embedded', $fields ) ) {
			$links = $this->prepare_links( $post );
			$response->add_links( $links );

			if ( ! empty( $links['self']['href'] ) ) {
				$actions = $this->get_available_actions( $post, $request );

				$self = $links['self']['href'];

				foreach ( $actions as $rel ) {
					$response->add_link( $rel, $self );
				}
			}
		}

		/**
		 * Filters the post data for a REST API response.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * Possible hook names include:
		 *
		 *  - `rest_prepare_post`
		 *  - `rest_prepare_page`
		 *  - `rest_prepare_attachment`
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $post     Post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Overwrites the default protected and private title format.
	 *
	 * By default, WordPress will show password protected or private posts with a title of
	 * "Protected: %s" or "Private: %s", as the REST API communicates the status of a post
	 * in a machine-readable format, we remove the prefix.
	 *
	 * @since 4.7.0
	 *
	 * @return string Title format.
	 */
	public function protected_title_format() {
		return '%s';
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( rest_get_route_for_post( $post->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( rest_get_route_for_post_type_items( $this->post_type ) ),
			),
			'about'      => array(
				'href' => rest_url( 'wp/v2/types/' . $this->post_type ),
			),
		);

		if ( ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'author' ) )
			&& ! empty( $post->post_author ) ) {
			$links['author'] = array(
				'href'       => rest_url( 'wp/v2/users/' . $post->post_author ),
				'embeddable' => true,
			);
		}

		if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'comments' ) ) {
			$replies_url = rest_url( 'wp/v2/comments' );
			$replies_url = add_query_arg( 'post', $post->ID, $replies_url );

			$links['replies'] = array(
				'href'       => $replies_url,
				'embeddable' => true,
			);
		}

		if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'revisions' ) ) {
			$revisions       = wp_get_latest_revision_id_and_total_count( $post->ID );
			$revisions_count = ! is_wp_error( $revisions ) ? $revisions['count'] : 0;
			$revisions_base  = sprintf( '/%s/%s/%d/revisions', $this->namespace, $this->rest_base, $post->ID );

			$links['version-history'] = array(
				'href'  => rest_url( $revisions_base ),
				'count' => $revisions_count,
			);

			if ( $revisions_count > 0 ) {
				$links['predecessor-version'] = array(
					'href' => rest_url( $revisions_base . '/' . $revisions['latest_id'] ),
					'id'   => $revisions['latest_id'],
				);
			}
		}

		$post_type_obj = get_post_type_object( $post->post_type );

		if ( $post_type_obj->hierarchical && ! empty( $post->post_parent ) ) {
			$links['up'] = array(
				'href'       => rest_url( rest_get_route_for_post( $post->post_parent ) ),
				'embeddable' => true,
			);
		}

		// If we have a featured media, add that.
		$featured_media = get_post_thumbnail_id( $post->ID );
		if ( $featured_media ) {
			$image_url = rest_url( rest_get_route_for_post( $featured_media ) );

			$links['https://api.w.org/featuredmedia'] = array(
				'href'       => $image_url,
				'embeddable' => true,
			);
		}

		if ( ! in_array( $post->post_type, array( 'attachment', 'nav_menu_item', 'revision' ), true ) ) {
			$attachments_url = rest_url( rest_get_route_for_post_type_items( 'attachment' ) );
			$attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );

			$links['https://api.w.org/attachment'] = array(
				'href' => $attachments_url,
			);
		}

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_route = rest_get_route_for_taxonomy_items( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_route ) ) {
					continue;
				}
				$terms_url = add_query_arg(
					'post',
					$post->ID,
					rest_url( $taxonomy_route )
				);

				$links['https://api.w.org/term'][] = array(
					'href'       => $terms_url,
					'taxonomy'   => $tax,
					'embeddable' => true,
				);
			}
		}

		return $links;
	}

	/**
	 * Gets the link relations available for the post and current user.
	 *
	 * @since 4.9.8
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $post, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		$post_type = get_post_type_object( $post->post_type );

		if ( 'attachment' !== $this->post_type && current_user_can( $post_type->cap->publish_posts ) ) {
			$rels[] = 'https://api.w.org/action-publish';
		}

		if ( current_user_can( 'unfiltered_html' ) ) {
			$rels[] = 'https://api.w.org/action-unfiltered-html';
		}

		if ( 'post' === $post_type->name ) {
			if ( current_user_can( $post_type->cap->edit_others_posts ) && current_user_can( $post_type->cap->publish_posts ) ) {
				$rels[] = 'https://api.w.org/action-sticky';
			}
		}

		if ( post_type_supports( $post_type->name, 'author' ) ) {
			if ( current_user_can( $post_type->cap->edit_others_posts ) ) {
				$rels[] = 'https://api.w.org/action-assign-author';
			}
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $tax ) {
			$tax_base   = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;
			$create_cap = is_taxonomy_hierarchical( $tax->name ) ? $tax->cap->edit_terms : $tax->cap->assign_terms;

			if ( current_user_can( $create_cap ) ) {
				$rels[] = 'https://api.w.org/action-create-' . $tax_base;
			}

			if ( current_user_can( $tax->cap->assign_terms ) ) {
				$rels[] = 'https://api.w.org/action-assign-' . $tax_base;
			}
		}

		return $rels;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			// Base properties for every Post.
			'properties' => array(
				'date'         => array(
					'description' => __( "The date the post was published, in the site's timezone." ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'date_gmt'     => array(
					'description' => __( 'The date the post was published, as GMT.' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'guid'         => array(
					'description' => __( 'The globally unique identifier for the post.' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the post, as it exists in the database.' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
							'readonly'    => true,
						),
						'rendered' => array(
							'description' => __( 'GUID for the post, transformed for display.' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'id'           => array(
					'description' => __( 'Unique identifier for the post.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'link'         => array(
					'description' => __( 'URL to the post.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'modified'     => array(
					'description' => __( "The date the post was last modified, in the site's timezone." ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt' => array(
					'description' => __( 'The date the post was last modified, as GMT.' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'         => array(
					'description' => __( 'An alphanumeric identifier for the post unique to its type.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'status'       => array(
					'description' => __( 'A named status for the post.' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'validate_callback' => array( $this, 'check_status' ),
					),
				),
				'type'         => array(
					'description' => __( 'Type of post.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'password'     => array(
					'description' => __( 'A password to protect access to the content and excerpt.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
			),
		);

		$post_type_obj = get_post_type_object( $this->post_type );
		if ( is_post_type_viewable( $post_type_obj ) && $post_type_obj->public ) {
			$schema['properties']['permalink_template'] = array(
				'description' => __( 'Permalink template for the post.' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['generated_slug'] = array(
				'description' => __( 'Slug automatically generated from the post title.' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['class_list'] = array(
				'description' => __( 'An array of the class names for the post container element.' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type' => 'string',
				),
			);
		}

		if ( $post_type_obj->hierarchical ) {
			$schema['properties']['parent'] = array(
				'description' => __( 'The ID for the parent of the post.' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$post_type_attributes = array(
			'title',
			'editor',
			'author',
			'excerpt',
			'thumbnail',
			'comments',
			'revisions',
			'page-attributes',
			'post-formats',
			'custom-fields',
		);
		$fixed_schemas        = array(
			'post'       => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'thumbnail',
				'comments',
				'revisions',
				'post-formats',
				'custom-fields',
			),
			'page'       => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'thumbnail',
				'comments',
				'revisions',
				'page-attributes',
				'custom-fields',
			),
			'attachment' => array(
				'title',
				'author',
				'comments',
				'revisions',
				'custom-fields',
				'thumbnail',
			),
		);

		foreach ( $post_type_attributes as $attribute ) {
			if ( isset( $fixed_schemas[ $this->post_type ] ) && ! in_array( $attribute, $fixed_schemas[ $this->post_type ], true ) ) {
				continue;
			} elseif ( ! isset( $fixed_schemas[ $this->post_type ] ) && ! post_type_supports( $this->post_type, $attribute ) ) {
				continue;
			}

			switch ( $attribute ) {

				case 'title':
					$schema['properties']['title'] = array(
						'description' => __( 'The title for the post.' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit', 'embed' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Title for the post, as it exists in the database.' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML title for the post, transformed for display.' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'editor':
					$schema['properties']['content'] = array(
						'description' => __( 'The content for the post.' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'           => array(
								'description' => __( 'Content for the post, as it exists in the database.' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered'      => array(
								'description' => __( 'HTML content for the post, transformed for display.' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'block_version' => array(
								'description' => __( 'Version of the content block format used by the post.' ),
								'type'        => 'integer',
								'context'     => array( 'edit' ),
								'readonly'    => true,
							),
							'protected'     => array(
								'description' => __( 'Whether the content is protected with a password.' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'author':
					$schema['properties']['author'] = array(
						'description' => __( 'The ID for the author of the post.' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
					);
					break;

				case 'excerpt':
					$schema['properties']['excerpt'] = array(
						'description' => __( 'The excerpt for the post.' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit', 'embed' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'       => array(
								'description' => __( 'Excerpt for the post, as it exists in the database.' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered'  => array(
								'description' => __( 'HTML excerpt for the post, transformed for display.' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
							'protected' => array(
								'description' => __( 'Whether the excerpt is protected with a password.' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'thumbnail':
					$schema['properties']['featured_media'] = array(
						'description' => __( 'The ID of the featured media for the post.' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
					);
					break;

				case 'comments':
					$schema['properties']['comment_status'] = array(
						'description' => __( 'Whether or not comments are open on the post.' ),
						'type'        => 'string',
						'enum'        => array( 'open', 'closed' ),
						'context'     => array( 'view', 'edit' ),
					);
					$schema['properties']['ping_status']    = array(
						'description' => __( 'Whether or not the post can be pinged.' ),
						'type'        => 'string',
						'enum'        => array( 'open', 'closed' ),
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'page-attributes':
					$schema['properties']['menu_order'] = array(
						'description' => __( 'The order of the post in relation to other posts.' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'post-formats':
					// Get the native post formats and remove the array keys.
					$formats = array_values( get_post_format_slugs() );

					$schema['properties']['format'] = array(
						'description' => __( 'The format for the post.' ),
						'type'        => 'string',
						'enum'        => $formats,
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'custom-fields':
					$schema['properties']['meta'] = $this->meta->get_field_schema();
					break;

			}
		}

		if ( 'post' === $this->post_type ) {
			$schema['properties']['sticky'] = array(
				'description' => __( 'Whether or not the post should be treated as sticky.' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$schema['properties']['template'] = array(
			'description' => __( 'The theme file to use to display the post.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'validate_callback' => array( $this, 'check_template' ),
			),
		);

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( array_key_exists( $base, $schema['properties'] ) ) {
				$taxonomy_field_name_with_conflict = ! empty( $taxonomy->rest_base ) ? 'rest_base' : 'name';
				_doing_it_wrong(
					'register_taxonomy',
					sprintf(
						/* translators: 1: The taxonomy name, 2: The property name, either 'rest_base' or 'name', 3: The conflicting value. */
						__( 'The "%1$s" taxonomy "%2$s" property (%3$s) conflicts with an existing property on the REST API Posts Controller. Specify a custom "rest_base" when registering the taxonomy to avoid this error.' ),
						$taxonomy->name,
						$taxonomy_field_name_with_conflict,
						$base
					),
					'5.4.0'
				);
			}

			$schema['properties'][ $base ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( __( 'The terms assigned to the post in the %s taxonomy.' ), $taxonomy->name ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			);
		}

		$schema_links = $this->get_schema_links();

		if ( $schema_links ) {
			$schema['links'] = $schema_links;
		}

		// Take a snapshot of which fields are in the schema pre-filtering.
		$schema_fields = array_keys( $schema['properties'] );

		/**
		 * Filters the post's schema.
		 *
		 * The dynamic portion of the filter, `$this->post_type`, refers to the
		 * post type slug for the controller.
		 *
		 * Possible hook names include:
		 *
		 *  - `rest_post_item_schema`
		 *  - `rest_page_item_schema`
		 *  - `rest_attachment_item_schema`
		 *
		 * @since 5.4.0
		 *
		 * @param array $schema Item schema data.
		 */
		$schema = apply_filters( "rest_{$this->post_type}_item_schema", $schema );

		// Emit a _doing_it_wrong warning if user tries to add new properties using this filter.
		$new_fields = array_diff( array_keys( $schema['properties'] ), $schema_fields );
		if ( count( $new_fields ) > 0 ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: register_rest_field */
					__( 'Please use %s to add new schema properties.' ),
					'register_rest_field'
				),
				'5.4.0'
			);
		}

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Retrieves Link Description Objects that should be added to the Schema for the posts collection.
	 *
	 * @since 4.9.8
	 *
	 * @return array
	 */
	protected function get_schema_links() {

		$href = rest_url( "{$this->namespace}/{$this->rest_base}/{id}" );

		$links = array();

		if ( 'attachment' !== $this->post_type ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-publish',
				'title'        => __( 'The current user can publish this post.' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type' => 'string',
							'enum' => array( 'publish', 'future' ),
						),
					),
				),
			);
		}

		$links[] = array(
			'rel'          => 'https://api.w.org/action-unfiltered-html',
			'title'        => __( 'The current user can post unfiltered HTML markup and JavaScript.' ),
			'href'         => $href,
			'targetSchema' => array(
				'type'       => 'object',
				'properties' => array(
					'content' => array(
						'raw' => array(
							'type' => 'string',
						),
					),
				),
			),
		);

		if ( 'post' === $this->post_type ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-sticky',
				'title'        => __( 'The current user can sticky this post.' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'sticky' => array(
							'type' => 'boolean',
						),
					),
				),
			);
		}

		if ( post_type_supports( $this->post_type, 'author' ) ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-assign-author',
				'title'        => __( 'The current user can change the author on this post.' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'author' => array(
							'type' => 'integer',
						),
					),
				),
			);
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $tax ) {
			$tax_base = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;

			/* translators: %s: Taxonomy name. */
			$assign_title = sprintf( __( 'The current user can assign terms in the %s taxonomy.' ), $tax->name );
			/* translators: %s: Taxonomy name. */
			$create_title = sprintf( __( 'The current user can create terms in the %s taxonomy.' ), $tax->name );

			$links[] = array(
				'rel'          => 'https://api.w.org/action-assign-' . $tax_base,
				'title'        => $assign_title,
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						$tax_base => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'integer',
							),
						),
					),
				),
			);

			$links[] = array(
				'rel'          => 'https://api.w.org/action-create-' . $tax_base,
				'title'        => $create_title,
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						$tax_base => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'integer',
							),
						),
					),
				),
			);
		}

		return $links;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 * @since 5.4.0 The `tax_relation` query parameter was added.
	 * @since 5.7.0 The `modified_after` and `modified_before` query parameters were added.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['after'] = array(
			'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['modified_after'] = array(
			'description' => __( 'Limit response to posts modified after a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		if ( post_type_supports( $this->post_type, 'author' ) ) {
			$query_params['author']         = array(
				'description' => __( 'Limit result set to posts assigned to specific authors.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
			$query_params['author_exclude'] = array(
				'description' => __( 'Ensure result set excludes posts assigned to specific authors.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
		}

		$query_params['before'] = array(
			'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['modified_before'] = array(
			'description' => __( 'Limit response to posts modified before a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['exclude'] = array(
			'description' => __( 'Ensure result set excludes specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['include'] = array(
			'description' => __( 'Limit result set to specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		if ( 'page' === $this->post_type || post_type_supports( $this->post_type, 'page-attributes' ) ) {
			$query_params['menu_order'] = array(
				'description' => __( 'Limit result set to posts with a specific menu_order value.' ),
				'type'        => 'integer',
			);
		}

		$query_params['search_semantics'] = array(
			'description' => __( 'How to interpret the search input.' ),
			'type'        => 'string',
			'enum'        => array( 'exact' ),
		);

		$query_params['offset'] = array(
			'description' => __( 'Offset the result set by a specific number of items.' ),
			'type'        => 'integer',
		);

		$query_params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by post attribute.' ),
			'type'        => 'string',
			'default'     => 'date',
			'enum'        => array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'include_slugs',
				'title',
			),
		);

		if ( 'page' === $this->post_type || post_type_supports( $this->post_type, 'page-attributes' ) ) {
			$query_params['orderby']['enum'][] = 'menu_order';
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( $post_type->hierarchical || 'attachment' === $this->post_type ) {
			$query_params['parent']         = array(
				'description' => __( 'Limit result set to items with particular parent IDs.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
			$query_params['parent_exclude'] = array(
				'description' => __( 'Limit result set to all items except those of a particular parent ID.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
		}

		$query_params['search_columns'] = array(
			'default'     => array(),
			'description' => __( 'Array of column names to be searched.' ),
			'type'        => 'array',
			'items'       => array(
				'enum' => array( 'post_title', 'post_content', 'post_excerpt' ),
				'type' => 'string',
			),
		);

		$query_params['slug'] = array(
			'description' => __( 'Limit result set to posts with one or more specific slugs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		$query_params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to posts assigned one or more statuses.' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array_merge( array_keys( get_post_stati() ), array( 'any' ) ),
				'type' => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
		);

		$query_params = $this->prepare_taxonomy_limit_schema( $query_params );

		if ( 'post' === $this->post_type ) {
			$query_params['sticky'] = array(
				'description' => __( 'Limit result set to items that are sticky.' ),
				'type'        => 'boolean',
			);

			$query_params['ignore_sticky'] = array(
				'description' => __( 'Whether to ignore sticky posts or not.' ),
				'type'        => 'boolean',
				'default'     => true,
			);
		}

		if ( post_type_supports( $this->post_type, 'post-formats' ) ) {
			$query_params['format'] = array(
				'description' => __( 'Limit result set to items assigned one or more given formats.' ),
				'type'        => 'array',
				'uniqueItems' => true,
				'items'       => array(
					'enum' => array_values( get_post_format_slugs() ),
					'type' => 'string',
				),
			);
		}

		/**
		 * Filters collection parameters for the posts controller.
		 *
		 * The dynamic part of the filter `$this->post_type` refers to the post
		 * type slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter. Use the
		 * `rest_{$this->post_type}_query` filter to set WP_Query parameters.
		 *
		 * @since 4.7.0
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 * @param WP_Post_Type $post_type    Post type object.
		 */
		return apply_filters( "rest_{$this->post_type}_collection_params", $query_params, $post_type );
	}

	/**
	 * Sanitizes and validates the list of post statuses, including whether the
	 * user can query private statuses.
	 *
	 * @since 4.7.0
	 *
	 * @param string|array    $statuses  One or more post statuses.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Additional parameter to pass to validation.
	 * @return array|WP_Error A list of valid statuses, otherwise WP_Error object.
	 */
	public function sanitize_post_statuses( $statuses, $request, $parameter ) {
		$statuses = wp_parse_slug_list( $statuses );

		// The default status is different in WP_REST_Attachments_Controller.
		$attributes     = $request->get_attributes();
		$default_status = $attributes['args']['status']['default'];

		foreach ( $statuses as $status ) {
			if ( $status === $default_status ) {
				continue;
			}

			$post_type_obj = get_post_type_object( $this->post_type );

			if ( current_user_can( $post_type_obj->cap->edit_posts ) || 'private' === $status && current_user_can( $post_type_obj->cap->read_private_posts ) ) {
				$result = rest_validate_request_arg( $status, $request, $parameter );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			} else {
				return new WP_Error(
					'rest_forbidden_status',
					__( 'Status is forbidden.' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
		}

		return $statuses;
	}

	/**
	 * Prepares the 'tax_query' for a collection of posts.
	 *
	 * @since 5.7.0
	 *
	 * @param array           $args    WP_Query arguments.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Updated query arguments.
	 */
	private function prepare_tax_query( array $args, WP_REST_Request $request ) {
		$relation = $request['tax_relation'];

		if ( $relation ) {
			$args['tax_query'] = array( 'relation' => $relation );
		}

		$taxonomies = wp_list_filter(
			get_object_taxonomies( $this->post_type, 'objects' ),
			array( 'show_in_rest' => true )
		);

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			$tax_include = $request[ $base ];
			$tax_exclude = $request[ $base . '_exclude' ];

			if ( $tax_include ) {
				$terms            = array();
				$include_children = false;
				$operator         = 'IN';

				if ( rest_is_array( $tax_include ) ) {
					$terms = $tax_include;
				} elseif ( rest_is_object( $tax_include ) ) {
					$terms            = empty( $tax_include['terms'] ) ? array() : $tax_include['terms'];
					$include_children = ! empty( $tax_include['include_children'] );

					if ( isset( $tax_include['operator'] ) && 'AND' === $tax_include['operator'] ) {
						$operator = 'AND';
					}
				}

				if ( $terms ) {
					$args['tax_query'][] = array(
						'taxonomy'         => $taxonomy->name,
						'field'            => 'term_id',
						'terms'            => $terms,
						'include_children' => $include_children,
						'operator'         => $operator,
					);
				}
			}

			if ( $tax_exclude ) {
				$terms            = array();
				$include_children = false;

				if ( rest_is_array( $tax_exclude ) ) {
					$terms = $tax_exclude;
				} elseif ( rest_is_object( $tax_exclude ) ) {
					$terms            = empty( $tax_exclude['terms'] ) ? array() : $tax_exclude['terms'];
					$include_children = ! empty( $tax_exclude['include_children'] );
				}

				if ( $terms ) {
					$args['tax_query'][] = array(
						'taxonomy'         => $taxonomy->name,
						'field'            => 'term_id',
						'terms'            => $terms,
						'include_children' => $include_children,
						'operator'         => 'NOT IN',
					);
				}
			}
		}

		return $args;
	}

	/**
	 * Prepares the collection schema for including and excluding items by terms.
	 *
	 * @since 5.7.0
	 *
	 * @param array $query_params Collection schema.
	 * @return array Updated schema.
	 */
	private function prepare_taxonomy_limit_schema( array $query_params ) {
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		if ( ! $taxonomies ) {
			return $query_params;
		}

		$query_params['tax_relation'] = array(
			'description' => __( 'Limit result set based on relationship between multiple taxonomies.' ),
			'type'        => 'string',
			'enum'        => array( 'AND', 'OR' ),
		);

		$limit_schema = array(
			'type'  => array( 'object', 'array' ),
			'oneOf' => array(
				array(
					'title'       => __( 'Term ID List' ),
					'description' => __( 'Match terms with the listed IDs.' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				array(
					'title'                => __( 'Term ID Taxonomy Query' ),
					'description'          => __( 'Perform an advanced term query.' ),
					'type'                 => 'object',
					'properties'           => array(
						'terms'            => array(
							'description' => __( 'Term IDs.' ),
							'type'        => 'array',
							'items'       => array(
								'type' => 'integer',
							),
							'default'     => array(),
						),
						'include_children' => array(
							'description' => __( 'Whether to include child terms in the terms limiting the result set.' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
					'additionalProperties' => false,
				),
			),
		);

		$include_schema = array_merge(
			array(
				/* translators: %s: Taxonomy name. */
				'description' => __( 'Limit result set to items with specific terms assigned in the %s taxonomy.' ),
			),
			$limit_schema
		);
		// 'operator' is supported only for 'include' queries.
		$include_schema['oneOf'][1]['properties']['operator'] = array(
			'description' => __( 'Whether items must be assigned all or any of the specified terms.' ),
			'type'        => 'string',
			'enum'        => array( 'AND', 'OR' ),
			'default'     => 'OR',
		);

		$exclude_schema = array_merge(
			array(
				/* translators: %s: Taxonomy name. */
				'description' => __( 'Limit result set to items except those with specific terms assigned in the %s taxonomy.' ),
			),
			$limit_schema
		);

		foreach ( $taxonomies as $taxonomy ) {
			$base         = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
			$base_exclude = $base . '_exclude';

			$query_params[ $base ]                = $include_schema;
			$query_params[ $base ]['description'] = sprintf( $query_params[ $base ]['description'], $base );

			$query_params[ $base_exclude ]                = $exclude_schema;
			$query_params[ $base_exclude ]['description'] = sprintf( $query_params[ $base_exclude ]['description'], $base );

			if ( ! $taxonomy->hierarchical ) {
				unset( $query_params[ $base ]['oneOf'][1]['properties']['include_children'] );
				unset( $query_params[ $base_exclude ]['oneOf'][1]['properties']['include_children'] );
			}
		}

		return $query_params;
	}
}
