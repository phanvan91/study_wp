<?php
/**
 * REST API: Lớp WP_REST_Server
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để triển khai máy chủ REST API của WordPress.
 *
 * @since 4.4.0
 */
#[AllowDynamicProperties]
class WP_REST_Server {

	/**
	 * Bí danh cho phương thức truyền tải GET.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	const READABLE = 'GET';

	/**
	 * Bí danh cho phương thức truyền tải POST.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	const CREATABLE = 'POST';

	/**
	 * Bí danh cho các phương thức truyền tải POST, PUT, PATCH kết hợp.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	const EDITABLE = 'POST, PUT, PATCH';

	/**
	 * Bí danh cho phương thức truyền tải DELETE.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	const DELETABLE = 'DELETE';

	/**
	 * Bí danh cho các phương thức truyền tải GET, POST, PUT, PATCH & DELETE kết hợp.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';

	/**
	 * Các namespace đã đăng ký với máy chủ.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected $namespaces = array();

	/**
	 * Các endpoint đã đăng ký với máy chủ.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected $endpoints = array();

	/**
	 * Các tùy chọn được định nghĩa cho các route.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected $route_options = array();

	/**
	 * Bộ nhớ đệm cho các yêu cầu nhúng.
	 *
	 * @since 5.4.0
	 * @var array
	 */
	protected $embed_cache = array();

	/**
	 * Lưu trữ các đối tượng yêu cầu đang được xử lý.
	 *
	 * @since 6.5.0
	 * @var array
	 */
	protected $dispatching_requests = array();

	/**
	 * Khởi tạo máy chủ REST.
	 *
	 * @since 4.4.0
	 */
	public function __construct() {
		$this->endpoints = array(
			// Các endpoint meta.
			'/'         => array(
				'callback' => array( $this, 'get_index' ),
				'methods'  => 'GET',
				'args'     => array(
					'context' => array(
						'default' => 'view',
					),
				),
			),
			'/batch/v1' => array(
				'callback' => array( $this, 'serve_batch_request_v1' ),
				'methods'  => 'POST',
				'args'     => array(
					'validation' => array(
						'type'    => 'string',
						'enum'    => array( 'require-all-validate', 'normal' ),
						'default' => 'normal',
					),
					'requests'   => array(
						'required' => true,
						'type'     => 'array',
						'maxItems' => $this->get_max_batch_size(),
						'items'    => array(
							'type'       => 'object',
							'properties' => array(
								'method'  => array(
									'type'    => 'string',
									'enum'    => array( 'POST', 'PUT', 'PATCH', 'DELETE' ),
									'default' => 'POST',
								),
								'path'    => array(
									'type'     => 'string',
									'required' => true,
								),
								'body'    => array(
									'type'                 => 'object',
									'properties'           => array(),
									'additionalProperties' => true,
								),
								'headers' => array(
									'type'                 => 'object',
									'properties'           => array(),
									'additionalProperties' => array(
										'type'  => array( 'string', 'array' ),
										'items' => array(
											'type' => 'string',
										),
									),
								),
							),
						),
					),
				),
			),
		);
	}


	/**
	 * Kiểm tra các header xác thực nếu được cung cấp.
	 *
	 * @since 4.4.0
	 *
	 * @return WP_Error|null|true WP_Error cho biết đăng nhập không thành công, null cho biết thành công
	 *                            hoặc không có xác thực nào được cung cấp
	 */
	public function check_authentication() {
		/**
		 * Lọc các lỗi xác thực REST API.
		 *
		 * Được sử dụng để truyền WP_Error từ phương thức xác thực trở lại API.
		 *
		 * Các phương thức xác thực nên kiểm tra trước xem chúng có đang được sử dụng không,
		 * vì nhiều phương thức xác thực có thể được kích hoạt trên một trang web (cookies,
		 * HTTP basic auth, OAuth). Nếu phương thức xác thực được hook vào thực tế không
		 * đang được thử, nên trả về null để chỉ ra rằng phương thức xác thực khác
		 * nên kiểm tra thay thế. Tương tự, các callback nên đảm bảo giá trị là `null`
		 * trước khi kiểm tra lỗi.
		 *
		 * Một instance WP_Error có thể được trả về nếu có lỗi xảy ra, và nó nên
		 * phù hợp với định dạng được sử dụng bên trong các phương thức API (tức là,
		 * dữ liệu `status` nên được sử dụng). Một callback có thể trả về `true` để chỉ ra rằng
		 * phương thức xác thực đã được sử dụng và thành công.
		 *
		 * @since 4.4.0
		 *
		 * @param WP_Error|null|true $errors WP_Error nếu lỗi xác thực, null nếu phương thức
		 *                                   xác thực không được sử dụng, true nếu xác thực thành công.
		 */
		return apply_filters( 'rest_authentication_errors', null );
	}

	/**
	 * Chuyển đổi lỗi thành đối tượng phản hồi.
	 *
	 * Phương thức này lặp qua tất cả mã lỗi và thông báo để chuyển thành mảng
	 * phẳng. Điều này cho phép hành vi phía client đơn giản hơn, vì nó được biểu diễn
	 * dưới dạng danh sách trong JSON thay vì object/map.
	 *
	 * @since 4.4.0
	 * @since 5.7.0 Đã chuyển thành wrapper của {@see rest_convert_error_to_response()}.
	 *
	 * @param WP_Error $error Instance WP_Error.
	 * @return WP_REST_Response Danh sách các mảng kết hợp với khóa code và message.
	 */
	protected function error_to_response( $error ) {
		return rest_convert_error_to_response( $error );
	}

	/**
	 * Lấy biểu diễn lỗi phù hợp dạng JSON.
	 *
	 * Lưu ý: Chỉ nên sử dụng phương thức này trong WP_REST_Server::serve_request(), vì nó
	 * không thể xử lý WP_Error nội bộ. Tất cả callback và phương thức nội bộ khác
	 * nên trả về WP_Error với dữ liệu được thiết lập thành mảng bao gồm
	 * khóa 'status', với giá trị là mã trạng thái HTTP cần gửi.
	 *
	 * @since 4.4.0
	 *
	 * @param string $code    Mã kiểu WP_Error.
	 * @param string $message Thông báo con người đọc được.
	 * @param int    $status  Tùy chọn. Mã trạng thái HTTP cần gửi. Mặc định null.
	 * @return string Biểu diễn JSON của lỗi
	 */
	protected function json_error( $code, $message, $status = null ) {
		if ( $status ) {
			$this->set_status( $status );
		}

		$error = compact( 'code', 'message' );

		return wp_json_encode( $error );
	}

	/**
	 * Lấy các tùy chọn mã hóa được truyền cho {@see wp_json_encode}.
	 *
	 * @since 6.1.0
	 *
	 * @param \WP_REST_Request $request Đối tượng yêu cầu hiện tại.
	 *
	 * @return int Các tùy chọn mã hóa JSON.
	 */
	protected function get_json_encode_options( WP_REST_Request $request ) {
		$options = 0;

		if ( $request->has_param( '_pretty' ) ) {
			$options |= JSON_PRETTY_PRINT;
		}

		/**
		 * Lọc các tùy chọn mã hóa JSON dùng để gửi phản hồi REST API.
		 *
		 * @since 6.1.0
		 *
		 * @param int $options             Các tùy chọn mã hóa JSON {@see json_encode()}.
		 * @param WP_REST_Request $request Đối tượng yêu cầu hiện tại.
		 */
		return apply_filters( 'rest_json_encode_options', $options, $request );
	}

	/**
	 * Xử lý phục vụ một yêu cầu REST API.
	 *
	 * Khớp URI máy chủ hiện tại với một route và chạy callback khớp đầu tiên
	 * sau đó xuất biểu diễn JSON của giá trị trả về.
	 *
	 * @since 4.4.0
	 *
	 * @see WP_REST_Server::dispatch()
	 *
	 * @global WP_User $current_user Người dùng đang được xác thực.
	 *
	 * @param string $path Tùy chọn. Route yêu cầu. Nếu không thiết lập, `$_SERVER['PATH_INFO']` sẽ được sử dụng.
	 *                     Mặc định null.
	 * @return null|false Null nếu không phục vụ và là yêu cầu HEAD, false trong trường hợp khác.
	 */
	public function serve_request( $path = null ) {
		/* @var WP_User|null $current_user Người dùng hiện tại */
		global $current_user;

		if ( $current_user instanceof WP_User && ! $current_user->exists() ) {
			/*
			 * Nếu không có người dùng hiện tại được xác thực qua phương thức khác,
			 * xóa bộ nhớ đệm thiếu người dùng, để kiểm tra xác thực có thể thiết lập
			 * đúng cách.
			 *
			 * Điều này được thực hiện vì đối với các phương thức xác thực như Application
			 * Passwords, chúng ta không muốn chấp nhận trừ khi yêu cầu HTTP hiện tại
			 * là yêu cầu REST API, điều mà không phải lúc nào cũng có thể xác định đủ sớm
			 * trong quá trình đánh giá.
			 */
			$current_user = null;
		}

		/**
		 * Lọc xem JSONP có được bật cho REST API hay không.
		 *
		 * @since 4.4.0
		 *
		 * @param bool $jsonp_enabled JSONP có được bật hay không. Mặc định true.
		 */
		$jsonp_enabled = apply_filters( 'rest_jsonp_enabled', true );

		$jsonp_callback = false;
		if ( isset( $_GET['_jsonp'] ) ) {
			$jsonp_callback = $_GET['_jsonp'];
		}

		$content_type = ( $jsonp_callback && $jsonp_enabled ) ? 'application/javascript' : 'application/json';
		$this->send_header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) );
		$this->send_header( 'X-Robots-Tag', 'noindex' );

		$api_root = get_rest_url();
		if ( ! empty( $api_root ) ) {
			$this->send_header( 'Link', '<' . sanitize_url( $api_root ) . '>; rel="https://api.w.org/"' );
		}

		/*
		 * Giảm thiểu các cuộc tấn công JSONP Flash có thể xảy ra.
		 *
		 * https://miki.it/blog/2014/7/8/abusing-jsonp-with-rosetta-flash/
		 */
		$this->send_header( 'X-Content-Type-Options', 'nosniff' );

		/**
		 * Lọc xem REST API có được bật hay không.
		 *
		 * @since 4.4.0
		 * @deprecated 4.7.0 Sử dụng bộ lọc {@see 'rest_authentication_errors'} để
		 *                   hạn chế quyền truy cập REST API.
		 *
		 * @param bool $rest_enabled REST API có được bật hay không. Mặc định true.
		 */
		apply_filters_deprecated(
			'rest_enabled',
			array( true ),
			'4.7.0',
			'rest_authentication_errors',
			sprintf(
				/* translators: %s: rest_authentication_errors */
				__( 'The REST API can no longer be completely disabled, the %s filter can be used to restrict access to the API, instead.' ),
				'rest_authentication_errors'
			)
		);

		if ( $jsonp_callback ) {
			if ( ! $jsonp_enabled ) {
				echo $this->json_error( 'rest_callback_disabled', __( 'JSONP support is disabled on this site.' ), 400 );
				return false;
			}

			if ( ! wp_check_jsonp_callback( $jsonp_callback ) ) {
				echo $this->json_error( 'rest_callback_invalid', __( 'Invalid JSONP callback function.' ), 400 );
				return false;
			}
		}

		if ( empty( $path ) ) {
			if ( isset( $_SERVER['PATH_INFO'] ) ) {
				$path = $_SERVER['PATH_INFO'];
			} else {
				$path = '/';
			}
		}

		$request = new WP_REST_Request( $_SERVER['REQUEST_METHOD'], $path );

		$request->set_query_params( wp_unslash( $_GET ) );
		$request->set_body_params( wp_unslash( $_POST ) );
		$request->set_file_params( $_FILES );
		$request->set_headers( $this->get_headers( wp_unslash( $_SERVER ) ) );
		$request->set_body( self::get_raw_data() );

		/*
		 * Ghi đè phương thức HTTP cho các client không thể sử dụng PUT/PATCH/DELETE. Đầu tiên,
		 * chúng ta kiểm tra $_GET['_method']. Nếu không được thiết lập, chúng ta kiểm tra header
		 * HTTP_X_HTTP_METHOD_OVERRIDE.
		 */
		$method_overridden = false;
		if ( isset( $_GET['_method'] ) ) {
			$request->set_method( $_GET['_method'] );
		} elseif ( isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ) {
			$request->set_method( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] );
			$method_overridden = true;
		}

		$expose_headers = array( 'X-WP-Total', 'X-WP-TotalPages', 'Link' );

		/**
		 * Lọc danh sách các header phản hồi được hiển thị cho các yêu cầu CORS của REST API.
		 *
		 * @since 5.5.0
		 * @since 6.3.0 Đã thêm tham số `$request`.
		 *
		 * @param string[]        $expose_headers Danh sách các header phản hồi cần hiển thị.
		 * @param WP_REST_Request $request        Yêu cầu trong ngữ cảnh.
		 */
		$expose_headers = apply_filters( 'rest_exposed_cors_headers', $expose_headers, $request );

		$this->send_header( 'Access-Control-Expose-Headers', implode( ', ', $expose_headers ) );

		$allow_headers = array(
			'Authorization',
			'X-WP-Nonce',
			'Content-Disposition',
			'Content-MD5',
			'Content-Type',
		);

		/**
		 * Lọc danh sách các header yêu cầu được phép cho các yêu cầu CORS của REST API.
		 *
		 * Các header được phép được truyền tới trình duyệt để chỉ định header nào
		 * có thể được truyền tới REST API. Theo mặc định, chúng ta cho phép các header
		 * Content-* cần thiết để tải lên tệp tới các endpoint media.
		 * Cũng như các header Authorization và Nonce để cho phép xác thực.
		 *
		 * @since 5.5.0
		 * @since 6.3.0 Đã thêm tham số `$request`.
		 *
		 * @param string[]        $allow_headers Danh sách các header yêu cầu được phép.
		 * @param WP_REST_Request $request       Yêu cầu trong ngữ cảnh.
		 */
		$allow_headers = apply_filters( 'rest_allowed_cors_headers', $allow_headers, $request );

		$this->send_header( 'Access-Control-Allow-Headers', implode( ', ', $allow_headers ) );

		$result = $this->check_authentication();

		if ( ! is_wp_error( $result ) ) {
			$result = $this->dispatch( $request );
		}

		// Chuẩn hóa thành WP_Error hoặc WP_REST_Response...
		$result = rest_ensure_response( $result );

		// ...sau đó chuyển đổi WP_Error.
		if ( is_wp_error( $result ) ) {
			$result = $this->error_to_response( $result );
		}

		/**
		 * Lọc phản hồi REST API.
		 *
		 * Cho phép chỉnh sửa phản hồi trước khi trả về.
		 *
		 * @since 4.4.0
		 * @since 4.5.0 Áp dụng cho các phản hồi nhúng.
		 *
		 * @param WP_HTTP_Response $result  Kết quả gửi tới client. Thường là `WP_REST_Response`.
		 * @param WP_REST_Server   $server  Instance máy chủ.
		 * @param WP_REST_Request  $request Yêu cầu dùng để tạo phản hồi.
		 */
		$result = apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $this, $request );

		// Bọc phản hồi trong envelope nếu được yêu cầu.
		if ( isset( $_GET['_envelope'] ) ) {
			$embed  = isset( $_GET['_embed'] ) ? rest_parse_embed_param( $_GET['_embed'] ) : false;
			$result = $this->envelope_response( $result, $embed );
		}

		// Gửi dữ liệu bổ sung từ các đối tượng phản hồi.
		$headers = $result->get_headers();
		$this->send_headers( $headers );

		$code = $result->get_status();
		$this->set_status( $code );

		/**
		 * Lọc xem có nên gửi header không-lưu-đệm trong yêu cầu REST API hay không.
		 *
		 * @since 4.4.0
		 * @since 6.3.2 Đã chuyển khối để bắt bộ lọc được thêm vào rest_cookie_check_errors() từ wp-includes/rest-api.php.
		 *
		 * @param bool $rest_send_nocache_headers Có gửi header không-lưu-đệm hay không.
		 */
		$send_no_cache_headers = apply_filters( 'rest_send_nocache_headers', is_user_logged_in() );

		/*
		 * Gửi header không-lưu-đệm nếu $send_no_cache_headers là true,
		 * HOẶC nếu HTTP_X_HTTP_METHOD_OVERRIDE được sử dụng nhưng cho kết quả mã phản hồi 4xx.
		 */
		if ( $send_no_cache_headers || ( true === $method_overridden && str_starts_with( $code, '4' ) ) ) {
			foreach ( wp_get_nocache_headers() as $header => $header_value ) {
				if ( empty( $header_value ) ) {
					$this->remove_header( $header );
				} else {
					$this->send_header( $header, $header_value );
				}
			}
		}

		/**
		 * Lọc xem yêu cầu REST API đã được phục vụ chưa.
		 *
		 * Cho phép gửi yêu cầu thủ công - bằng cách trả về true, kết quả API
		 * sẽ không được gửi tới client.
		 *
		 * @since 4.4.0
		 *
		 * @param bool             $served  Yêu cầu đã được phục vụ chưa.
		 *                                           Mặc định false.
		 * @param WP_HTTP_Response $result  Kết quả gửi tới client. Thường là `WP_REST_Response`.
		 * @param WP_REST_Request  $request Yêu cầu dùng để tạo phản hồi.
		 * @param WP_REST_Server   $server  Instance máy chủ.
		 */
		$served = apply_filters( 'rest_pre_serve_request', false, $result, $request, $this );

		if ( ! $served ) {
			if ( 'HEAD' === $request->get_method() ) {
				return null;
			}

			// Nhúng các liên kết vào bên trong yêu cầu.
			$embed  = isset( $_GET['_embed'] ) ? rest_parse_embed_param( $_GET['_embed'] ) : false;
			$result = $this->response_to_data( $result, $embed );

			/**
			 * Lọc phản hồi REST API.
			 *
			 * Cho phép chỉnh sửa dữ liệu phản hồi sau khi chèn dữ liệu nhúng
			 * (nếu có) và trước khi xuất dữ liệu phản hồi.
			 *
			 * @since 4.8.1
			 *
			 * @param array            $result  Dữ liệu phản hồi gửi tới client.
			 * @param WP_REST_Server   $server  Instance máy chủ.
			 * @param WP_REST_Request  $request Yêu cầu dùng để tạo phản hồi.
			 */
			$result = apply_filters( 'rest_pre_echo_response', $result, $this, $request );

			// Phản hồi 204 không nên có body.
			if ( 204 === $code || null === $result ) {
				return null;
			}

			$result = wp_json_encode( $result, $this->get_json_encode_options( $request ) );

			$json_error_message = $this->get_json_last_error();

			if ( $json_error_message ) {
				$this->set_status( 500 );
				$json_error_obj = new WP_Error(
					'rest_encode_error',
					$json_error_message,
					array( 'status' => 500 )
				);

				$result = $this->error_to_response( $json_error_obj );
				$result = wp_json_encode( $result->data, $this->get_json_encode_options( $request ) );
			}

			if ( $jsonp_callback ) {
				// Thêm '/**/' vào đầu để giảm thiểu các cuộc tấn công JSONP Flash có thể xảy ra.
				// https://miki.it/blog/2014/7/8/abusing-jsonp-with-rosetta-flash/
				echo '/**/' . $jsonp_callback . '(' . $result . ')';
			} else {
				echo $result;
			}
		}

		return null;
	}

	/**
	 * Chuyển đổi phản hồi thành dữ liệu để gửi.
	 *
	 * @since 4.4.0
	 * @since 5.4.0 Tham số `$embed` giờ có thể chứa danh sách các quan hệ liên kết cần bao gồm.
	 *
	 * @param WP_REST_Response $response Đối tượng phản hồi.
	 * @param bool|string[]    $embed    Có nhúng tất cả liên kết, danh sách quan hệ liên kết đã lọc, hay không có liên kết nào.
	 * @return array {
	 *     Dữ liệu với các yêu cầu con được nhúng.
	 *
	 *     @type array $_links    Các liên kết.
	 *     @type array $_embedded Các đối tượng được nhúng.
	 * }
	 */
	public function response_to_data( $response, $embed ) {
		$data  = $response->get_data();
		$links = self::get_compact_response_links( $response );

		if ( ! empty( $links ) ) {
			// Chuyển đổi các liên kết thành một phần của dữ liệu.
			$data['_links'] = $links;
		}

		if ( $embed ) {
			$this->embed_cache = array();
			// Xác định xem đây có phải là mảng số hay không.
			if ( wp_is_numeric_array( $data ) ) {
				foreach ( $data as $key => $item ) {
					$data[ $key ] = $this->embed_links( $item, $embed );
				}
			} else {
				$data = $this->embed_links( $data, $embed );
			}
			$this->embed_cache = array();
		}

		return $data;
	}

	/**
	 * Lấy các liên kết từ phản hồi.
	 *
	 * Trích xuất các liên kết từ phản hồi thành hash có cấu trúc, phù hợp cho
	 * xuất trực tiếp.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Response $response Phản hồi để trích xuất liên kết.
	 * @return array Ánh xạ quan hệ liên kết tới danh sách các hash liên kết.
	 */
	public static function get_response_links( $response ) {
		$links = $response->get_links();

		if ( empty( $links ) ) {
			return array();
		}

		// Chuyển đổi các liên kết thành một phần của dữ liệu.
		$data = array();
		foreach ( $links as $rel => $items ) {
			$data[ $rel ] = array();

			foreach ( $items as $item ) {
				$attributes         = $item['attributes'];
				$attributes['href'] = $item['href'];

				if ( 'self' !== $rel ) {
					$data[ $rel ][] = $attributes;
					continue;
				}

				$target_hints = self::get_target_hints_for_link( $attributes );
				if ( $target_hints ) {
					$attributes['targetHints'] = $target_hints;
				}

				$data[ $rel ][] = $attributes;
			}
		}

		return $data;
	}

	/**
	 * Lấy các liên kết đích cho một Liên kết REST API.
	 *
	 * @since 6.7.0
	 *
	 * @param array $link Liên kết.
	 *
	 * @return array|null
	 */
	protected static function get_target_hints_for_link( $link ) {
		// Ưu tiên các targetHints được chỉ định cụ thể bởi nhà phát triển.
		if ( isset( $link['targetHints']['allow'] ) ) {
			return null;
		}

		$request = WP_REST_Request::from_url( $link['href'] );
		if ( ! $request ) {
			return null;
		}

		$server = rest_get_server();
		$match  = $server->match_request_to_handler( $request );

		if ( is_wp_error( $match ) ) {
			return null;
		}

		if ( is_wp_error( $request->has_valid_params() ) ) {
			return null;
		}

		if ( is_wp_error( $request->sanitize_params() ) ) {
			return null;
		}

		$target_hints = array();

		$response = new WP_REST_Response();
		$response->set_matched_route( $match[0] );
		$response->set_matched_handler( $match[1] );
		$headers = rest_send_allow_header( $response, $server, $request )->get_headers();

		foreach ( $headers as $name => $value ) {
			$name = WP_REST_Request::canonicalize_header_name( $name );

			$target_hints[ $name ] = array_map( 'trim', explode( ',', $value ) );
		}

		return $target_hints;
	}

	/**
	 * Lấy các CURIE (URI rút gọn) được sử dụng cho các quan hệ.
	 *
	 * Trích xuất các liên kết từ phản hồi thành hash có cấu trúc, phù hợp cho
	 * xuất trực tiếp.
	 *
	 * @since 4.5.0
	 *
	 * @param WP_REST_Response $response Phản hồi để trích xuất liên kết.
	 * @return array Ánh xạ quan hệ liên kết tới danh sách các hash liên kết.
	 */
	public static function get_compact_response_links( $response ) {
		$links = self::get_response_links( $response );

		if ( empty( $links ) ) {
			return array();
		}

		$curies      = $response->get_curies();
		$used_curies = array();

		foreach ( $links as $rel => $items ) {

			// Chuyển đổi URI $rel thành phiên bản rút gọn nếu tồn tại.
			foreach ( $curies as $curie ) {
				$href_prefix = substr( $curie['href'], 0, strpos( $curie['href'], '{rel}' ) );
				if ( ! str_starts_with( $rel, $href_prefix ) ) {
					continue;
				}

				// Quan hệ giờ thay đổi từ '$uri' thành '$curie:$relation'.
				$rel_regex = str_replace( '\{rel\}', '(.+)', preg_quote( $curie['href'], '!' ) );
				preg_match( '!' . $rel_regex . '!', $rel, $matches );
				if ( $matches ) {
					$new_rel                       = $curie['name'] . ':' . $matches[1];
					$used_curies[ $curie['name'] ] = $curie;
					$links[ $new_rel ]             = $items;
					unset( $links[ $rel ] );
					break;
				}
			}
		}

		// Đẩy các curie vào đầu mảng liên kết.
		if ( $used_curies ) {
			$links['curies'] = array_values( $used_curies );
		}

		return $links;
	}

	/**
	 * Nhúng các liên kết từ dữ liệu vào yêu cầu.
	 *
	 * @since 4.4.0
	 * @since 5.4.0 Tham số `$embed` giờ có thể chứa danh sách các quan hệ liên kết cần bao gồm.
	 *
	 * @param array         $data  Dữ liệu từ yêu cầu.
	 * @param bool|string[] $embed Có nhúng tất cả liên kết hay danh sách quan hệ liên kết đã lọc.
	 * @return array {
	 *     Dữ liệu với các yêu cầu con được nhúng.
	 *
	 *     @type array $_links    Các liên kết.
	 *     @type array $_embedded Các đối tượng được nhúng.
	 * }
	 */
	protected function embed_links( $data, $embed = true ) {
		if ( empty( $data['_links'] ) ) {
			return $data;
		}

		$embedded = array();

		foreach ( $data['_links'] as $rel => $links ) {
			/*
			 * Nếu một danh sách quan hệ được chỉ định, và quan hệ liên kết
			 * không nằm trong danh sách các quan hệ được phép, không xử lý liên kết.
			 */
			if ( is_array( $embed ) && ! in_array( $rel, $embed, true ) ) {
				continue;
			}

			$embeds = array();

			foreach ( $links as $item ) {
				// Xác định xem liên kết có thể nhúng được không.
				if ( empty( $item['embeddable'] ) ) {
					// Đảm bảo giữ nguyên thứ tự.
					$embeds[] = array();
					continue;
				}

				if ( ! array_key_exists( $item['href'], $this->embed_cache ) ) {
					// Chạy qua hệ thống định tuyến nội bộ và phục vụ.
					$request = WP_REST_Request::from_url( $item['href'] );
					if ( ! $request ) {
						$embeds[] = array();
						continue;
					}

					// Các tài nguyên nhúng được truyền context=embed.
					if ( empty( $request['context'] ) ) {
						$request['context'] = 'embed';
					}

					if ( empty( $request['per_page'] ) ) {
						$matched = $this->match_request_to_handler( $request );
						if ( ! is_wp_error( $matched ) && isset( $matched[1]['args']['per_page']['maximum'] ) ) {
							$request['per_page'] = (int) $matched[1]['args']['per_page']['maximum'];
						}
					}

					$response = $this->dispatch( $request );

					/** This filter is documented in wp-includes/rest-api/class-wp-rest-server.php */
					$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this, $request );

					$this->embed_cache[ $item['href'] ] = $this->response_to_data( $response, false );
				}

				$embeds[] = $this->embed_cache[ $item['href'] ];
			}

			// Xác định xem có liên kết thực sự nào được tìm thấy không.
			$has_links = count( array_filter( $embeds ) );

			if ( $has_links ) {
				$embedded[ $rel ] = $embeds;
			}
		}

		if ( ! empty( $embedded ) ) {
			$data['_embedded'] = $embedded;
		}

		return $data;
	}

	/**
	 * Bọc phản hồi trong envelope.
	 *
	 * Kỹ thuật envelope được sử dụng để giải quyết các vấn đề tương thích
	 * trình duyệt/client. Về cơ bản, nó chuyển đổi toàn bộ phản hồi HTTP
	 * thành dữ liệu.
	 *
	 * @since 4.4.0
	 * @since 6.0.0 Tham số `$embed` giờ có thể chứa danh sách các quan hệ liên kết cần bao gồm.
	 *
	 * @param WP_REST_Response $response Đối tượng phản hồi.
	 * @param bool|string[]    $embed    Có nhúng tất cả liên kết, danh sách quan hệ liên kết đã lọc, hay không có liên kết nào.
	 * @return WP_REST_Response Phản hồi mới với dữ liệu đã bọc.
	 */
	public function envelope_response( $response, $embed ) {
		$envelope = array(
			'body'    => $this->response_to_data( $response, $embed ),
			'status'  => $response->get_status(),
			'headers' => $response->get_headers(),
		);

		/**
		 * Lọc dạng envelope của phản hồi REST API.
		 *
		 * @since 4.4.0
		 *
		 * @param array            $envelope {
		 *     Dữ liệu envelope.
		 *
		 *     @type array $body    Dữ liệu phản hồi.
		 *     @type int   $status  Mã trạng thái HTTP 3 chữ số.
		 *     @type array $headers Ánh xạ tên header tới giá trị header.
		 * }
		 * @param WP_REST_Response $response Dữ liệu phản hồi gốc.
		 */
		$envelope = apply_filters( 'rest_envelope_response', $envelope, $response );

		// Đảm bảo nó vẫn là phản hồi và trả về.
		return rest_ensure_response( $envelope );
	}

	/**
	 * Đăng ký một route tới máy chủ.
	 *
	 * @since 4.4.0
	 *
	 * @param string $route_namespace Namespace.
	 * @param string $route           Route REST.
	 * @param array  $route_args      Các đối số route.
	 * @param bool   $override        Tùy chọn. Có ghi đè route nếu nó đã tồn tại hay không.
	 *                                Mặc định false.
	 */
	public function register_route( $route_namespace, $route, $route_args, $override = false ) {
		if ( ! isset( $this->namespaces[ $route_namespace ] ) ) {
			$this->namespaces[ $route_namespace ] = array();

			$this->register_route(
				$route_namespace,
				'/' . $route_namespace,
				array(
					array(
						'methods'  => self::READABLE,
						'callback' => array( $this, 'get_namespace_index' ),
						'args'     => array(
							'namespace' => array(
								'default' => $route_namespace,
							),
							'context'   => array(
								'default' => 'view',
							),
						),
					),
				)
			);
		}

		// Dùng mảng kết hợp để tránh đăng ký trùng lặp.
		$this->namespaces[ $route_namespace ][ $route ] = true;

		$route_args['namespace'] = $route_namespace;

		if ( $override || empty( $this->endpoints[ $route ] ) ) {
			$this->endpoints[ $route ] = $route_args;
		} else {
			$this->endpoints[ $route ] = array_merge( $this->endpoints[ $route ], $route_args );
		}
	}

	/**
	 * Lấy bản đồ route.
	 *
	 * Bản đồ route là mảng kết hợp với biểu thức chính quy đường dẫn làm khóa. Giá trị
	 * là mảng chỉ mục với hàm/phương thức callback là phần tử đầu tiên, và bitmask
	 * của các phương thức HTTP là phần tử thứ hai (xem các hằng số của lớp).
	 *
	 * Mỗi route có thể được ánh xạ tới nhiều hơn một callback bằng cách sử dụng mảng
	 * các mảng chỉ mục. Điều này cho phép ánh xạ ví dụ yêu cầu GET tới một callback
	 * và yêu cầu POST tới callback khác.
	 *
	 * Lưu ý rằng biểu thức chính quy đường dẫn (khóa mảng) phải thoát ký tự @, vì nó
	 * được sử dụng làm dấu phân cách với preg_match()
	 *
	 * @since 4.4.0
	 * @since 5.4.0 Đã thêm tham số `$route_namespace`.
	 *
	 * @param string $route_namespace Tùy chọn, chỉ trả về các route trong namespace đã cho.
	 * @return array `'/path/regex' => array( $callback, $bitmask )` hoặc
	 *               `'/path/regex' => array( array( $callback, $bitmask ), ...)`.
	 */
	public function get_routes( $route_namespace = '' ) {
		$endpoints = $this->endpoints;

		if ( $route_namespace ) {
			$endpoints = wp_list_filter( $endpoints, array( 'namespace' => $route_namespace ) );
		}

		/**
		 * Lọc mảng các endpoint REST API có sẵn.
		 *
		 * @since 4.4.0
		 *
		 * @param array $endpoints Các endpoint có sẵn. Mảng các mẫu regex khớp, mỗi mẫu được ánh xạ
		 *                         tới mảng các callback cho endpoint. Chúng có định dạng
		 *                         `'/path/regex' => array( $callback, $bitmask )` hoặc
		 *                         `'/path/regex' => array( array( $callback, $bitmask ).
		 */
		$endpoints = apply_filters( 'rest_endpoints', $endpoints );

		// Chuẩn hóa các endpoint.
		$defaults = array(
			'methods'       => '',
			'accept_json'   => false,
			'accept_raw'    => false,
			'show_in_index' => true,
			'args'          => array(),
		);

		foreach ( $endpoints as $route => &$handlers ) {

			if ( isset( $handlers['callback'] ) ) {
				// Endpoint đơn, thêm một tầng sâu hơn.
				$handlers = array( $handlers );
			}

			if ( ! isset( $this->route_options[ $route ] ) ) {
				$this->route_options[ $route ] = array();
			}

			foreach ( $handlers as $key => &$handler ) {

				if ( ! is_numeric( $key ) ) {
					// Tùy chọn route, di chuyển nó sang các tùy chọn.
					$this->route_options[ $route ][ $key ] = $handler;
					unset( $handlers[ $key ] );
					continue;
				}

				$handler = wp_parse_args( $handler, $defaults );

				// Cho phép các phương thức HTTP phân tách bằng dấu phẩy.
				if ( is_string( $handler['methods'] ) ) {
					$methods = explode( ',', $handler['methods'] );
				} elseif ( is_array( $handler['methods'] ) ) {
					$methods = $handler['methods'];
				} else {
					$methods = array();
				}

				$handler['methods'] = array();

				foreach ( $methods as $method ) {
					$method                        = strtoupper( trim( $method ) );
					$handler['methods'][ $method ] = true;
				}
			}
		}

		return $endpoints;
	}

	/**
	 * Lấy các namespace đã đăng ký trên máy chủ.
	 *
	 * @since 4.4.0
	 *
	 * @return string[] Danh sách các namespace đã đăng ký.
	 */
	public function get_namespaces() {
		return array_keys( $this->namespaces );
	}

	/**
	 * Lấy các tùy chọn được chỉ định cho một route.
	 *
	 * @since 4.4.0
	 *
	 * @param string $route Mẫu route để lấy tùy chọn.
	 * @return array|null Dữ liệu dưới dạng mảng kết hợp nếu tìm thấy, hoặc null nếu không tìm thấy.
	 */
	public function get_route_options( $route ) {
		if ( ! isset( $this->route_options[ $route ] ) ) {
			return null;
		}

		return $this->route_options[ $route ];
	}

	/**
	 * Khớp yêu cầu với callback và gọi nó.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request Yêu cầu cần thử điều phối.
	 * @return WP_REST_Response Phản hồi được trả về bởi callback.
	 */
	public function dispatch( $request ) {
		$this->dispatching_requests[] = $request;

		/**
		 * Filters the pre-calculated result of a REST API dispatch request.
		 *
		 * Allow hijacking the request before dispatching by returning a non-empty. The returned value
		 * will be used to serve the request instead.
		 *
		 * @since 4.4.0
		 *
		 * @param mixed           $result  Response to replace the requested version with. Can be anything
		 *                                 a normal endpoint can return, or null to not hijack the request.
		 * @param WP_REST_Server  $server  Server instance.
		 * @param WP_REST_Request $request Request used to generate the response.
		 */
		$result = apply_filters( 'rest_pre_dispatch', null, $this, $request );

		if ( ! empty( $result ) ) {

			// Normalize to either WP_Error or WP_REST_Response...
			$result = rest_ensure_response( $result );

			// ...then convert WP_Error across.
			if ( is_wp_error( $result ) ) {
				$result = $this->error_to_response( $result );
			}

			array_pop( $this->dispatching_requests );
			return $result;
		}

		$error   = null;
		$matched = $this->match_request_to_handler( $request );

		if ( is_wp_error( $matched ) ) {
			$response = $this->error_to_response( $matched );
			array_pop( $this->dispatching_requests );
			return $response;
		}

		list( $route, $handler ) = $matched;

		if ( ! is_callable( $handler['callback'] ) ) {
			$error = new WP_Error(
				'rest_invalid_handler',
				__( 'The handler for the route is invalid.' ),
				array( 'status' => 500 )
			);
		}

		if ( ! is_wp_error( $error ) ) {
			$check_required = $request->has_valid_params();
			if ( is_wp_error( $check_required ) ) {
				$error = $check_required;
			} else {
				$check_sanitized = $request->sanitize_params();
				if ( is_wp_error( $check_sanitized ) ) {
					$error = $check_sanitized;
				}
			}
		}

		$response = $this->respond_to_request( $request, $route, $handler, $error );
		array_pop( $this->dispatching_requests );
		return $response;
	}

	/**
	 * Returns whether the REST server is currently dispatching / responding to a request.
	 *
	 * This may be a standalone REST API request, or an internal request dispatched from within a regular page load.
	 *
	 * @since 6.5.0
	 *
	 * @return bool Whether the REST server is currently handling a request.
	 */
	public function is_dispatching() {
		return (bool) $this->dispatching_requests;
	}

	/**
	 * Matches a request object to its handler.
	 *
	 * @access private
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array|WP_Error The route and request handler on success or a WP_Error instance if no handler was found.
	 */
	protected function match_request_to_handler( $request ) {
		$method = $request->get_method();
		$path   = $request->get_route();

		$with_namespace = array();

		foreach ( $this->get_namespaces() as $namespace ) {
			if ( str_starts_with( trailingslashit( ltrim( $path, '/' ) ), $namespace ) ) {
				$with_namespace[] = $this->get_routes( $namespace );
			}
		}

		if ( $with_namespace ) {
			$routes = array_merge( ...$with_namespace );
		} else {
			$routes = $this->get_routes();
		}

		foreach ( $routes as $route => $handlers ) {
			$match = preg_match( '@^' . $route . '$@i', $path, $matches );

			if ( ! $match ) {
				continue;
			}

			$args = array();

			foreach ( $matches as $param => $value ) {
				if ( ! is_int( $param ) ) {
					$args[ $param ] = $value;
				}
			}

			foreach ( $handlers as $handler ) {
				$callback = $handler['callback'];

				// Fallback to GET method if no HEAD method is registered.
				$checked_method = $method;
				if ( 'HEAD' === $method && empty( $handler['methods']['HEAD'] ) ) {
					$checked_method = 'GET';
				}
				if ( empty( $handler['methods'][ $checked_method ] ) ) {
					continue;
				}

				if ( ! is_callable( $callback ) ) {
					return array( $route, $handler );
				}

				$request->set_url_params( $args );
				$request->set_attributes( $handler );

				$defaults = array();

				foreach ( $handler['args'] as $arg => $options ) {
					if ( isset( $options['default'] ) ) {
						$defaults[ $arg ] = $options['default'];
					}
				}

				$request->set_default_params( $defaults );

				return array( $route, $handler );
			}
		}

		return new WP_Error(
			'rest_no_route',
			__( 'No route was found matching the URL and request method.' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Dispatches the request to the callback handler.
	 *
	 * @access private
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request  The request object.
	 * @param string          $route    The matched route regex.
	 * @param array           $handler  The matched route handler.
	 * @param WP_Error|null   $response The current error object if any.
	 * @return WP_REST_Response
	 */
	protected function respond_to_request( $request, $route, $handler, $response ) {
		/**
		 * Filters the response before executing any REST API callbacks.
		 *
		 * Allows plugins to perform additional validation after a
		 * request is initialized and matched to a registered route,
		 * but before it is executed.
		 *
		 * Note that this filter will not be called for requests that
		 * fail to authenticate or match to a registered route.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
		 *                                                                   Usually a WP_REST_Response or WP_Error.
		 * @param array                                            $handler  Route handler used for the request.
		 * @param WP_REST_Request                                  $request  Request used to generate the response.
		 */
		$response = apply_filters( 'rest_request_before_callbacks', $response, $handler, $request );

		// Check permission specified on the route.
		if ( ! is_wp_error( $response ) && ! empty( $handler['permission_callback'] ) ) {
			$permission = call_user_func( $handler['permission_callback'], $request );

			if ( is_wp_error( $permission ) ) {
				$response = $permission;
			} elseif ( false === $permission || null === $permission ) {
				$response = new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to do that.' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
		}

		if ( ! is_wp_error( $response ) ) {
			/**
			 * Filters the REST API dispatch request result.
			 *
			 * Allow plugins to override dispatching the request.
			 *
			 * @since 4.4.0
			 * @since 4.5.0 Added `$route` and `$handler` parameters.
			 *
			 * @param mixed           $dispatch_result Dispatch result, will be used if not empty.
			 * @param WP_REST_Request $request         Request used to generate the response.
			 * @param string          $route           Route matched for the request.
			 * @param array           $handler         Route handler used for the request.
			 */
			$dispatch_result = apply_filters( 'rest_dispatch_request', null, $request, $route, $handler );

			// Allow plugins to halt the request via this filter.
			if ( null !== $dispatch_result ) {
				$response = $dispatch_result;
			} else {
				$response = call_user_func( $handler['callback'], $request );
			}
		}

		/**
		 * Filters the response immediately after executing any REST API
		 * callbacks.
		 *
		 * Allows plugins to perform any needed cleanup, for example,
		 * to undo changes made during the {@see 'rest_request_before_callbacks'}
		 * filter.
		 *
		 * Note that this filter will not be called for requests that
		 * fail to authenticate or match to a registered route.
		 *
		 * Note that an endpoint's `permission_callback` can still be
		 * called after this filter - see `rest_send_allow_header()`.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
		 *                                                                   Usually a WP_REST_Response or WP_Error.
		 * @param array                                            $handler  Route handler used for the request.
		 * @param WP_REST_Request                                  $request  Request used to generate the response.
		 */
		$response = apply_filters( 'rest_request_after_callbacks', $response, $handler, $request );

		if ( is_wp_error( $response ) ) {
			$response = $this->error_to_response( $response );
		} else {
			$response = rest_ensure_response( $response );
		}

		$response->set_matched_route( $route );
		$response->set_matched_handler( $handler );

		return $response;
	}

	/**
	 * Returns if an error occurred during most recent JSON encode/decode.
	 *
	 * Strings to be translated will be in format like
	 * "Encoding error: Maximum stack depth exceeded".
	 *
	 * @since 4.4.0
	 *
	 * @return false|string Boolean false or string error message.
	 */
	protected function get_json_last_error() {
		$last_error_code = json_last_error();

		if ( JSON_ERROR_NONE === $last_error_code || empty( $last_error_code ) ) {
			return false;
		}

		return json_last_error_msg();
	}

	/**
	 * Retrieves the site index.
	 *
	 * This endpoint describes the capabilities of the site.
	 *
	 * @since 4.4.0
	 *
	 * @param array $request {
	 *     Request.
	 *
	 *     @type string $context Context.
	 * }
	 * @return WP_REST_Response The API root index data.
	 */
	public function get_index( $request ) {
		// General site data.
		$available = array(
			'name'            => get_option( 'blogname' ),
			'description'     => get_option( 'blogdescription' ),
			'url'             => get_option( 'siteurl' ),
			'home'            => home_url(),
			'gmt_offset'      => get_option( 'gmt_offset' ),
			'timezone_string' => get_option( 'timezone_string' ),
			'page_for_posts'  => (int) get_option( 'page_for_posts' ),
			'page_on_front'   => (int) get_option( 'page_on_front' ),
			'show_on_front'   => get_option( 'show_on_front' ),
			'namespaces'      => array_keys( $this->namespaces ),
			'authentication'  => array(),
			'routes'          => $this->get_data_for_routes( $this->get_routes(), $request['context'] ),
		);

		$response = new WP_REST_Response( $available );

		$fields = isset( $request['_fields'] ) ? $request['_fields'] : '';
		$fields = wp_parse_list( $fields );
		if ( empty( $fields ) ) {
			$fields[] = '_links';
		}

		if ( $request->has_param( '_embed' ) ) {
			$fields[] = '_embedded';
		}

		if ( rest_is_field_included( '_links', $fields ) || rest_is_field_included( '_embedded', $fields ) ) {
			$response->add_link( 'help', 'https://developer.wordpress.org/rest-api/' );
			$this->add_active_theme_link_to_index( $response );
			$this->add_site_logo_to_index( $response );
			$this->add_site_icon_to_index( $response );
		} else {
			if ( rest_is_field_included( 'site_logo', $fields ) ) {
				$this->add_site_logo_to_index( $response );
			}
			if ( rest_is_field_included( 'site_icon', $fields ) || rest_is_field_included( 'site_icon_url', $fields ) ) {
				$this->add_site_icon_to_index( $response );
			}
		}

		/**
		 * Filters the REST API root index data.
		 *
		 * This contains the data describing the API. This includes information
		 * about supported authentication schemes, supported namespaces, routes
		 * available on the API, and a small amount of data about the site.
		 *
		 * @since 4.4.0
		 * @since 6.0.0 Added `$request` parameter.
		 *
		 * @param WP_REST_Response $response Response data.
		 * @param WP_REST_Request  $request  Request data.
		 */
		return apply_filters( 'rest_index', $response, $request );
	}

	/**
	 * Adds a link to the active theme for users who have proper permissions.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Response $response REST API response.
	 */
	protected function add_active_theme_link_to_index( WP_REST_Response $response ) {
		$should_add = current_user_can( 'switch_themes' ) || current_user_can( 'manage_network_themes' );

		if ( ! $should_add && current_user_can( 'edit_posts' ) ) {
			$should_add = true;
		}

		if ( ! $should_add ) {
			foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
				if ( current_user_can( $post_type->cap->edit_posts ) ) {
					$should_add = true;
					break;
				}
			}
		}

		if ( $should_add ) {
			$theme = wp_get_theme();
			$response->add_link( 'https://api.w.org/active-theme', rest_url( 'wp/v2/themes/' . $theme->get_stylesheet() ) );
		}
	}

	/**
	 * Exposes the site logo through the WordPress REST API.
	 *
	 * This is used for fetching this information when user has no rights
	 * to update settings.
	 *
	 * @since 5.8.0
	 *
	 * @param WP_REST_Response $response REST API response.
	 */
	protected function add_site_logo_to_index( WP_REST_Response $response ) {
		$site_logo_id = get_theme_mod( 'custom_logo', 0 );

		$this->add_image_to_index( $response, $site_logo_id, 'site_logo' );
	}

	/**
	 * Exposes the site icon through the WordPress REST API.
	 *
	 * This is used for fetching this information when user has no rights
	 * to update settings.
	 *
	 * @since 5.9.0
	 *
	 * @param WP_REST_Response $response REST API response.
	 */
	protected function add_site_icon_to_index( WP_REST_Response $response ) {
		$site_icon_id = get_option( 'site_icon', 0 );

		$this->add_image_to_index( $response, $site_icon_id, 'site_icon' );

		$response->data['site_icon_url'] = get_site_icon_url();
	}

	/**
	 * Exposes an image through the WordPress REST API.
	 * This is used for fetching this information when user has no rights
	 * to update settings.
	 *
	 * @since 5.9.0
	 *
	 * @param WP_REST_Response $response REST API response.
	 * @param int              $image_id Image attachment ID.
	 * @param string           $type     Type of Image.
	 */
	protected function add_image_to_index( WP_REST_Response $response, $image_id, $type ) {
		$response->data[ $type ] = (int) $image_id;
		if ( $image_id ) {
			$response->add_link(
				'https://api.w.org/featuredmedia',
				rest_url( rest_get_route_for_post( $image_id ) ),
				array(
					'embeddable' => true,
					'type'       => $type,
				)
			);
		}
	}

	/**
	 * Retrieves the index for a namespace.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request REST request instance.
	 * @return WP_REST_Response|WP_Error WP_REST_Response instance if the index was found,
	 *                                   WP_Error if the namespace isn't set.
	 */
	public function get_namespace_index( $request ) {
		$namespace = $request['namespace'];

		if ( ! isset( $this->namespaces[ $namespace ] ) ) {
			return new WP_Error(
				'rest_invalid_namespace',
				__( 'The specified namespace could not be found.' ),
				array( 'status' => 404 )
			);
		}

		$routes    = $this->namespaces[ $namespace ];
		$endpoints = array_intersect_key( $this->get_routes(), $routes );

		$data     = array(
			'namespace' => $namespace,
			'routes'    => $this->get_data_for_routes( $endpoints, $request['context'] ),
		);
		$response = rest_ensure_response( $data );

		// Link to the root index.
		$response->add_link( 'up', rest_url( '/' ) );

		/**
		 * Filters the REST API namespace index data.
		 *
		 * This typically is just the route data for the namespace, but you can
		 * add any data you'd like here.
		 *
		 * @since 4.4.0
		 *
		 * @param WP_REST_Response $response Response data.
		 * @param WP_REST_Request  $request  Request data. The namespace is passed as the 'namespace' parameter.
		 */
		return apply_filters( 'rest_namespace_index', $response, $request );
	}

	/**
	 * Retrieves the publicly-visible data for routes.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $routes  Routes to get data for.
	 * @param string $context Optional. Context for data. Accepts 'view' or 'help'. Default 'view'.
	 * @return array[] Route data to expose in indexes, keyed by route.
	 */
	public function get_data_for_routes( $routes, $context = 'view' ) {
		$available = array();

		// Find the available routes.
		foreach ( $routes as $route => $callbacks ) {
			$data = $this->get_data_for_route( $route, $callbacks, $context );
			if ( empty( $data ) ) {
				continue;
			}

			/**
			 * Filters the publicly-visible data for a single REST API route.
			 *
			 * @since 4.4.0
			 *
			 * @param array $data Publicly-visible data for the route.
			 */
			$available[ $route ] = apply_filters( 'rest_endpoints_description', $data );
		}

		/**
		 * Filters the publicly-visible data for REST API routes.
		 *
		 * This data is exposed on indexes and can be used by clients or
		 * developers to investigate the site and find out how to use it. It
		 * acts as a form of self-documentation.
		 *
		 * @since 4.4.0
		 *
		 * @param array[] $available Route data to expose in indexes, keyed by route.
		 * @param array   $routes    Internal route data as an associative array.
		 */
		return apply_filters( 'rest_route_data', $available, $routes );
	}

	/**
	 * Retrieves publicly-visible data for the route.
	 *
	 * @since 4.4.0
	 *
	 * @param string $route     Route to get data for.
	 * @param array  $callbacks Callbacks to convert to data.
	 * @param string $context   Optional. Context for the data. Accepts 'view' or 'help'. Default 'view'.
	 * @return array|null Data for the route, or null if no publicly-visible data.
	 */
	public function get_data_for_route( $route, $callbacks, $context = 'view' ) {
		$data = array(
			'namespace' => '',
			'methods'   => array(),
			'endpoints' => array(),
		);

		$allow_batch = false;

		if ( isset( $this->route_options[ $route ] ) ) {
			$options = $this->route_options[ $route ];

			if ( isset( $options['namespace'] ) ) {
				$data['namespace'] = $options['namespace'];
			}

			$allow_batch = isset( $options['allow_batch'] ) ? $options['allow_batch'] : false;

			if ( isset( $options['schema'] ) && 'help' === $context ) {
				$data['schema'] = call_user_func( $options['schema'] );
			}
		}

		$allowed_schema_keywords = array_flip( rest_get_allowed_schema_keywords() );

		$route = preg_replace( '#\(\?P<(\w+?)>.*?\)#', '{$1}', $route );

		foreach ( $callbacks as $callback ) {
			// Skip to the next route if any callback is hidden.
			if ( empty( $callback['show_in_index'] ) ) {
				continue;
			}

			$data['methods'] = array_merge( $data['methods'], array_keys( $callback['methods'] ) );
			$endpoint_data   = array(
				'methods' => array_keys( $callback['methods'] ),
			);

			$callback_batch = isset( $callback['allow_batch'] ) ? $callback['allow_batch'] : $allow_batch;

			if ( $callback_batch ) {
				$endpoint_data['allow_batch'] = $callback_batch;
			}

			if ( isset( $callback['args'] ) ) {
				$endpoint_data['args'] = array();

				foreach ( $callback['args'] as $key => $opts ) {
					if ( is_string( $opts ) ) {
						$opts = array( $opts => 0 );
					} elseif ( ! is_array( $opts ) ) {
						$opts = array();
					}
					$arg_data             = array_intersect_key( $opts, $allowed_schema_keywords );
					$arg_data['required'] = ! empty( $opts['required'] );

					$endpoint_data['args'][ $key ] = $arg_data;
				}
			}

			$data['endpoints'][] = $endpoint_data;

			// For non-variable routes, generate links.
			if ( ! str_contains( $route, '{' ) ) {
				$data['_links'] = array(
					'self' => array(
						array(
							'href' => rest_url( $route ),
						),
					),
				);
			}
		}

		if ( empty( $data['methods'] ) ) {
			// No methods supported, hide the route.
			return null;
		}

		return $data;
	}

	/**
	 * Gets the maximum number of requests that can be included in a batch.
	 *
	 * @since 5.6.0
	 *
	 * @return int The maximum requests.
	 */
	protected function get_max_batch_size() {
		/**
		 * Filters the maximum number of REST API requests that can be included in a batch.
		 *
		 * @since 5.6.0
		 *
		 * @param int $max_size The maximum size.
		 */
		return apply_filters( 'rest_get_max_batch_size', 25 );
	}

	/**
	 * Serves the batch/v1 request.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $batch_request The batch request object.
	 * @return WP_REST_Response The generated response object.
	 */
	public function serve_batch_request_v1( WP_REST_Request $batch_request ) {
		$requests = array();

		foreach ( $batch_request['requests'] as $args ) {
			$parsed_url = wp_parse_url( $args['path'] );

			if ( false === $parsed_url ) {
				$requests[] = new WP_Error( 'parse_path_failed', __( 'Could not parse the path.' ), array( 'status' => 400 ) );

				continue;
			}

			$single_request = new WP_REST_Request( isset( $args['method'] ) ? $args['method'] : 'POST', $parsed_url['path'] );

			if ( ! empty( $parsed_url['query'] ) ) {
				$query_args = array();
				wp_parse_str( $parsed_url['query'], $query_args );
				$single_request->set_query_params( $query_args );
			}

			if ( ! empty( $args['body'] ) ) {
				$single_request->set_body_params( $args['body'] );
			}

			if ( ! empty( $args['headers'] ) ) {
				$single_request->set_headers( $args['headers'] );
			}

			$requests[] = $single_request;
		}

		$matches    = array();
		$validation = array();
		$has_error  = false;

		foreach ( $requests as $single_request ) {
			$match     = $this->match_request_to_handler( $single_request );
			$matches[] = $match;
			$error     = null;

			if ( is_wp_error( $match ) ) {
				$error = $match;
			}

			if ( ! $error ) {
				list( $route, $handler ) = $match;

				if ( isset( $handler['allow_batch'] ) ) {
					$allow_batch = $handler['allow_batch'];
				} else {
					$route_options = $this->get_route_options( $route );
					$allow_batch   = isset( $route_options['allow_batch'] ) ? $route_options['allow_batch'] : false;
				}

				if ( ! is_array( $allow_batch ) || empty( $allow_batch['v1'] ) ) {
					$error = new WP_Error(
						'rest_batch_not_allowed',
						__( 'The requested route does not support batch requests.' ),
						array( 'status' => 400 )
					);
				}
			}

			if ( ! $error ) {
				$check_required = $single_request->has_valid_params();
				if ( is_wp_error( $check_required ) ) {
					$error = $check_required;
				}
			}

			if ( ! $error ) {
				$check_sanitized = $single_request->sanitize_params();
				if ( is_wp_error( $check_sanitized ) ) {
					$error = $check_sanitized;
				}
			}

			if ( $error ) {
				$has_error    = true;
				$validation[] = $error;
			} else {
				$validation[] = true;
			}
		}

		$responses = array();

		if ( $has_error && 'require-all-validate' === $batch_request['validation'] ) {
			foreach ( $validation as $valid ) {
				if ( is_wp_error( $valid ) ) {
					$responses[] = $this->envelope_response( $this->error_to_response( $valid ), false )->get_data();
				} else {
					$responses[] = null;
				}
			}

			return new WP_REST_Response(
				array(
					'failed'    => 'validation',
					'responses' => $responses,
				),
				WP_Http::MULTI_STATUS
			);
		}

		foreach ( $requests as $i => $single_request ) {
			$clean_request = clone $single_request;
			$clean_request->set_url_params( array() );
			$clean_request->set_attributes( array() );
			$clean_request->set_default_params( array() );

			/** This filter is documented in wp-includes/rest-api/class-wp-rest-server.php */
			$result = apply_filters( 'rest_pre_dispatch', null, $this, $clean_request );

			if ( empty( $result ) ) {
				$match = $matches[ $i ];
				$error = null;

				if ( is_wp_error( $validation[ $i ] ) ) {
					$error = $validation[ $i ];
				}

				if ( is_wp_error( $match ) ) {
					$result = $this->error_to_response( $match );
				} else {
					list( $route, $handler ) = $match;

					if ( ! $error && ! is_callable( $handler['callback'] ) ) {
						$error = new WP_Error(
							'rest_invalid_handler',
							__( 'The handler for the route is invalid' ),
							array( 'status' => 500 )
						);
					}

					$result = $this->respond_to_request( $single_request, $route, $handler, $error );
				}
			}

			/** This filter is documented in wp-includes/rest-api/class-wp-rest-server.php */
			$result = apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $this, $single_request );

			$responses[] = $this->envelope_response( $result, false )->get_data();
		}

		return new WP_REST_Response( array( 'responses' => $responses ), WP_Http::MULTI_STATUS );
	}

	/**
	 * Sends an HTTP status code.
	 *
	 * @since 4.4.0
	 *
	 * @param int $code HTTP status.
	 */
	protected function set_status( $code ) {
		status_header( $code );
	}

	/**
	 * Sends an HTTP header.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Header key.
	 * @param string $value Header value.
	 */
	public function send_header( $key, $value ) {
		/*
		 * Sanitize as per RFC2616 (Section 4.2):
		 *
		 * Any LWS that occurs between field-content MAY be replaced with a
		 * single SP before interpreting the field value or forwarding the
		 * message downstream.
		 */
		$value = preg_replace( '/\s+/', ' ', $value );
		header( sprintf( '%s: %s', $key, $value ) );
	}

	/**
	 * Sends multiple HTTP headers.
	 *
	 * @since 4.4.0
	 *
	 * @param array $headers Map of header name to header value.
	 */
	public function send_headers( $headers ) {
		foreach ( $headers as $key => $value ) {
			$this->send_header( $key, $value );
		}
	}

	/**
	 * Removes an HTTP header from the current response.
	 *
	 * @since 4.8.0
	 *
	 * @param string $key Header key.
	 */
	public function remove_header( $key ) {
		header_remove( $key );
	}

	/**
	 * Retrieves the raw request entity (body).
	 *
	 * @since 4.4.0
	 *
	 * @global string $HTTP_RAW_POST_DATA Raw post data.
	 *
	 * @return string Raw request data.
	 */
	public static function get_raw_data() {
		// phpcs:disable PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
		global $HTTP_RAW_POST_DATA;

		// $HTTP_RAW_POST_DATA was deprecated in PHP 5.6 and removed in PHP 7.0.
		if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		return $HTTP_RAW_POST_DATA;
		// phpcs:enable
	}

	/**
	 * Extracts headers from a PHP-style $_SERVER array.
	 *
	 * @since 4.4.0
	 *
	 * @param array $server Associative array similar to `$_SERVER`.
	 * @return array Headers extracted from the input.
	 */
	public function get_headers( $server ) {
		$headers = array();

		// CONTENT_* headers are not prefixed with HTTP_.
		$additional = array(
			'CONTENT_LENGTH' => true,
			'CONTENT_MD5'    => true,
			'CONTENT_TYPE'   => true,
		);

		foreach ( $server as $key => $value ) {
			if ( str_starts_with( $key, 'HTTP_' ) ) {
				$headers[ substr( $key, 5 ) ] = $value;
			} elseif ( 'REDIRECT_HTTP_AUTHORIZATION' === $key && empty( $server['HTTP_AUTHORIZATION'] ) ) {
				/*
				 * In some server configurations, the authorization header is passed in this alternate location.
				 * Since it would not be passed in in both places we do not check for both headers and resolve.
				 */
				$headers['AUTHORIZATION'] = $value;
			} elseif ( isset( $additional[ $key ] ) ) {
				$headers[ $key ] = $value;
			}
		}

		return $headers;
	}
}
