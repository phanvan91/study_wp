<?php
/**
 * HTTP API: Lớp WP_Http
 *
 * @package WordPress
 * @subpackage HTTP
 * @since 2.7.0
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'WpOrg\Requests\Autoload' ) ) {
	require ABSPATH . WPINC . '/Requests/src/Autoload.php';

	WpOrg\Requests\Autoload::register();
	WpOrg\Requests\Requests::set_certificate_path( ABSPATH . WPINC . '/certificates/ca-bundle.crt' );
}

/**
 * Lớp cốt lõi dùng để quản lý các phương thức truyền tải HTTP và thực hiện các yêu cầu HTTP.
 *
 * Lớp này được sử dụng để giúp các nhà phát triển dễ dàng thực hiện các yêu cầu HTTP đi ra
 * một cách nhất quán trong khi vẫn tương thích với nhiều cấu hình PHP khác nhau
 * mà WordPress chạy trên đó.
 *
 * Gỡ lỗi bao gồm nhiều action, truyền các biến khác nhau để gỡ lỗi HTTP API.
 *
 * @since 2.7.0
 */
#[AllowDynamicProperties]
class WP_Http {

	// Bí danh cho các mã phản hồi HTTP.
	const HTTP_CONTINUE       = 100;
	const SWITCHING_PROTOCOLS = 101;
	const PROCESSING          = 102;
	const EARLY_HINTS         = 103;

	const OK                            = 200;
	const CREATED                       = 201;
	const ACCEPTED                      = 202;
	const NON_AUTHORITATIVE_INFORMATION = 203;
	const NO_CONTENT                    = 204;
	const RESET_CONTENT                 = 205;
	const PARTIAL_CONTENT               = 206;
	const MULTI_STATUS                  = 207;
	const IM_USED                       = 226;

	const MULTIPLE_CHOICES   = 300;
	const MOVED_PERMANENTLY  = 301;
	const FOUND              = 302;
	const SEE_OTHER          = 303;
	const NOT_MODIFIED       = 304;
	const USE_PROXY          = 305;
	const RESERVED           = 306;
	const TEMPORARY_REDIRECT = 307;
	const PERMANENT_REDIRECT = 308;

	const BAD_REQUEST                     = 400;
	const UNAUTHORIZED                    = 401;
	const PAYMENT_REQUIRED                = 402;
	const FORBIDDEN                       = 403;
	const NOT_FOUND                       = 404;
	const METHOD_NOT_ALLOWED              = 405;
	const NOT_ACCEPTABLE                  = 406;
	const PROXY_AUTHENTICATION_REQUIRED   = 407;
	const REQUEST_TIMEOUT                 = 408;
	const CONFLICT                        = 409;
	const GONE                            = 410;
	const LENGTH_REQUIRED                 = 411;
	const PRECONDITION_FAILED             = 412;
	const REQUEST_ENTITY_TOO_LARGE        = 413;
	const REQUEST_URI_TOO_LONG            = 414;
	const UNSUPPORTED_MEDIA_TYPE          = 415;
	const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const EXPECTATION_FAILED              = 417;
	const IM_A_TEAPOT                     = 418;
	const MISDIRECTED_REQUEST             = 421;
	const UNPROCESSABLE_ENTITY            = 422;
	const LOCKED                          = 423;
	const FAILED_DEPENDENCY               = 424;
	const TOO_EARLY                       = 425;
	const UPGRADE_REQUIRED                = 426;
	const PRECONDITION_REQUIRED           = 428;
	const TOO_MANY_REQUESTS               = 429;
	const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	const UNAVAILABLE_FOR_LEGAL_REASONS   = 451;

	const INTERNAL_SERVER_ERROR           = 500;
	const NOT_IMPLEMENTED                 = 501;
	const BAD_GATEWAY                     = 502;
	const SERVICE_UNAVAILABLE             = 503;
	const GATEWAY_TIMEOUT                 = 504;
	const HTTP_VERSION_NOT_SUPPORTED      = 505;
	const VARIANT_ALSO_NEGOTIATES         = 506;
	const INSUFFICIENT_STORAGE            = 507;
	const NOT_EXTENDED                    = 510;
	const NETWORK_AUTHENTICATION_REQUIRED = 511;

	/**
	 * Gửi một yêu cầu HTTP đến URI.
	 *
	 * Xin lưu ý: Các URI duy nhất được hỗ trợ trong triển khai HTTP Transport
	 * là giao thức HTTP và HTTPS.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  URL yêu cầu.
	 * @param string|array $args {
	 *     Tùy chọn. Mảng hoặc chuỗi các đối số yêu cầu HTTP.
	 *
	 *     @type string       $method              Phương thức yêu cầu. Chấp nhận 'GET', 'POST', 'HEAD', 'PUT', 'DELETE',
	 *                                             'TRACE', 'OPTIONS', hoặc 'PATCH'.
	 *                                             Một số phương thức truyền tải cho phép các giá trị khác, nhưng không nên
	 *                                             giả định điều đó. Mặc định 'GET'.
	 *     @type float        $timeout             Thời gian kết nối nên giữ mở tính bằng giây. Mặc định 5.
	 *     @type int          $redirection         Số lần chuyển hướng được phép. Không được hỗ trợ bởi tất cả
	 *                                             phương thức truyền tải. Mặc định 5.
	 *     @type string       $httpversion         Phiên bản giao thức HTTP sử dụng. Chấp nhận '1.0' và '1.1'.
	 *                                             Mặc định '1.0'.
	 *     @type string       $user-agent          Giá trị user-agent được gửi.
	 *                                             Mặc định 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ).
	 *     @type bool         $reject_unsafe_urls  Có truyền URL qua wp_http_validate_url() hay không.
	 *                                             Mặc định false.
	 *     @type bool         $blocking            Có yêu cầu mã gọi cần kết quả của yêu cầu hay không.
	 *                                             Nếu đặt thành false, yêu cầu sẽ được gửi đến máy chủ từ xa,
	 *                                             và xử lý được trả về mã gọi ngay lập tức, người gọi
	 *                                             sẽ biết yêu cầu thành công hay thất bại, nhưng sẽ không nhận
	 *                                             bất kỳ phản hồi nào từ máy chủ từ xa. Mặc định true.
	 *     @type string|array $headers             Mảng hoặc chuỗi các header gửi kèm yêu cầu.
	 *                                             Mặc định mảng rỗng.
	 *     @type array        $cookies             Danh sách cookie gửi kèm yêu cầu. Mặc định mảng rỗng.
	 *     @type string|array $body                Nội dung gửi kèm yêu cầu. Mặc định null.
	 *     @type bool         $compress            Có nén $body khi gửi yêu cầu hay không.
	 *                                             Mặc định false.
	 *     @type bool         $decompress          Có giải nén phản hồi đã nén hay không. Nếu đặt thành false và
	 *                                             nội dung nén vẫn được trả về trong phản hồi, nó sẽ
	 *                                             cần được giải nén riêng. Mặc định true.
	 *     @type bool         $sslverify           Có xác minh SSL cho yêu cầu hay không. Mặc định true.
	 *     @type string       $sslcertificates     Đường dẫn tuyệt đối đến tệp chứng chỉ SSL .crt.
	 *                                             Mặc định ABSPATH . WPINC . '/certificates/ca-bundle.crt'.
	 *     @type bool         $stream              Có truyền trực tuyến vào tệp hay không. Nếu đặt thành true và không có
	 *                                             tên tệp nào được cung cấp, nó sẽ được lưu trong thư mục tạm WP và tên
	 *                                             sẽ được đặt theo tên cơ sở của URL. Mặc định false.
	 *     @type string       $filename            Tên tệp để ghi vào khi truyền trực tuyến. $stream phải được
	 *                                             đặt thành true. Mặc định null.
	 *     @type int          $limit_response_size Kích thước tính bằng byte để giới hạn phản hồi. Mặc định null.
	 *
	 * }
	 * @return array|WP_Error {
	 *     Mảng dữ liệu phản hồi, hoặc thể hiện WP_Error khi có lỗi.
	 *
	 *     @type \WpOrg\Requests\Utility\CaseInsensitiveDictionary $headers       Các header phản hồi được đánh khóa theo tên.
	 *     @type string                                            $body          Nội dung phản hồi.
	 *     @type array                                             $response      {
	 *         Mảng dữ liệu phản hồi HTTP.
	 *
	 *         @type int|false    $code    Mã trạng thái phản hồi HTTP.
	 *         @type string|false $message Thông điệp phản hồi HTTP.
	 *     }
	 *     @type WP_HTTP_Cookie[]                                  $cookies       Mảng cookie được thiết lập bởi máy chủ.
	 *     @type string|null                                       $filename      Tùy chọn. Tên tệp của phản hồi.
	 *     @type WP_HTTP_Requests_Response|null                    $http_response Đối tượng phản hồi.
	 * }
	 */
	public function request( $url, $args = array() ) {
		$defaults = array(
			'method'              => 'GET',
			/**
			 * Lọc giá trị thời gian chờ cho yêu cầu HTTP.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 Tham số `$url` được thêm vào.
			 *
			 * @param float  $timeout_value Thời gian tính bằng giây cho đến khi yêu cầu hết hạn. Mặc định 5.
			 * @param string $url           URL yêu cầu.
			 */
			'timeout'             => apply_filters( 'http_request_timeout', 5, $url ),
			/**
			 * Lọc số lần chuyển hướng được phép trong yêu cầu HTTP.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 Tham số `$url` được thêm vào.
			 *
			 * @param int    $redirect_count Số lần chuyển hướng được phép. Mặc định 5.
			 * @param string $url            URL yêu cầu.
			 */
			'redirection'         => apply_filters( 'http_request_redirection_count', 5, $url ),
			/**
			 * Lọc phiên bản giao thức HTTP được sử dụng trong yêu cầu.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 Tham số `$url` được thêm vào.
			 *
			 * @param string $version Phiên bản HTTP được sử dụng. Chấp nhận '1.0' và '1.1'. Mặc định '1.0'.
			 * @param string $url     URL yêu cầu.
			 */
			'httpversion'         => apply_filters( 'http_request_version', '1.0', $url ),
			/**
			 * Lọc giá trị user agent được gửi kèm yêu cầu HTTP.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 Tham số `$url` được thêm vào.
			 *
			 * @param string $user_agent Chuỗi user agent của WordPress.
			 * @param string $url        URL yêu cầu.
			 */
			'user-agent'          => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ), $url ),
			/**
			 * Lọc có truyền URL qua wp_http_validate_url() trong yêu cầu HTTP hay không.
			 *
			 * @since 3.6.0
			 * @since 5.1.0 Tham số `$url` được thêm vào.
			 *
			 * @param bool   $pass_url Có truyền URL qua wp_http_validate_url() hay không. Mặc định false.
			 * @param string $url      URL yêu cầu.
			 */
			'reject_unsafe_urls'  => apply_filters( 'http_request_reject_unsafe_urls', false, $url ),
			'blocking'            => true,
			'headers'             => array(),
			'cookies'             => array(),
			'body'                => null,
			'compress'            => false,
			'decompress'          => true,
			'sslverify'           => true,
			'sslcertificates'     => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			'stream'              => false,
			'filename'            => null,
			'limit_response_size' => null,
		);

		// Phân tích trước cho các kiểm tra HEAD.
		$args = wp_parse_args( $args );

		// Mặc định, các yêu cầu HEAD không gây ra chuyển hướng.
		if ( isset( $args['method'] ) && 'HEAD' === $args['method'] ) {
			$defaults['redirection'] = 0;
		}

		$parsed_args = wp_parse_args( $args, $defaults );
		/**
		 * Lọc các đối số được sử dụng trong yêu cầu HTTP.
		 *
		 * @since 2.7.0
		 *
		 * @param array  $parsed_args Mảng các đối số yêu cầu HTTP.
		 * @param string $url         URL yêu cầu.
		 */
		$parsed_args = apply_filters( 'http_request_args', $parsed_args, $url );

		// Các phương thức truyền tải giảm giá trị này, lưu bản sao của giá trị gốc cho mục đích vòng lặp.
		if ( ! isset( $parsed_args['_redirection'] ) ) {
			$parsed_args['_redirection'] = $parsed_args['redirection'];
		}

		/**
		 * Lọc giá trị trả về ưu tiên của yêu cầu HTTP.
		 *
		 * Trả về giá trị không phải false từ bộ lọc sẽ rút ngắn yêu cầu HTTP và trả về
		 * sớm với giá trị đó. Bộ lọc nên trả về một trong:
		 *
		 *  - Mảng chứa các phần tử 'headers', 'body', 'response', 'cookies', và 'filename'
		 *  - Thể hiện WP_Error
		 *  - boolean false để tránh rút ngắn phản hồi
		 *
		 * Trả về bất kỳ giá trị nào khác có thể dẫn đến hành vi không mong muốn.
		 *
		 * @since 2.9.0
		 *
		 * @param false|array|WP_Error $response    Giá trị trả về ưu tiên của yêu cầu HTTP. Mặc định false.
		 * @param array                $parsed_args Các đối số yêu cầu HTTP.
		 * @param string               $url         URL yêu cầu.
		 */
		$pre = apply_filters( 'pre_http_request', false, $parsed_args, $url );

		if ( false !== $pre ) {
			return $pre;
		}

		if ( function_exists( 'wp_kses_bad_protocol' ) ) {
			if ( $parsed_args['reject_unsafe_urls'] ) {
				$url = wp_http_validate_url( $url );
			}
			if ( $url ) {
				$url = wp_kses_bad_protocol( $url, array( 'http', 'https', 'ssl' ) );
			}
		}

		$parsed_url = parse_url( $url );

		if ( empty( $url ) || empty( $parsed_url['scheme'] ) ) {
			$response = new WP_Error( 'http_request_failed', __( 'A valid URL was not provided.' ) );
			/** Action này được ghi chú trong wp-includes/class-wp-http.php */
			do_action( 'http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url );
			return $response;
		}

		if ( $this->block_request( $url ) ) {
			$response = new WP_Error( 'http_request_not_executed', __( 'User has blocked requests through HTTP.' ) );
			/** Action này được ghi chú trong wp-includes/class-wp-http.php */
			do_action( 'http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url );
			return $response;
		}

		// Nếu chúng ta đang truyền trực tuyến vào tệp nhưng không có tên tệp nào được cung cấp, lưu vào thư mục tạm WP
		// và đặt tên bằng tên cơ sở của $url.
		if ( $parsed_args['stream'] ) {
			if ( empty( $parsed_args['filename'] ) ) {
				$parsed_args['filename'] = get_temp_dir() . basename( $url );
			}

			// Buộc một số thiết lập nếu đang truyền trực tuyến vào tệp và kiểm tra sự tồn tại
			// và quyền truy cập của thư mục đích.
			$parsed_args['blocking'] = true;
			if ( ! wp_is_writable( dirname( $parsed_args['filename'] ) ) ) {
				$response = new WP_Error( 'http_request_failed', __( 'Destination directory for file streaming does not exist or is not writable.' ) );
				/** Action này được ghi chú trong wp-includes/class-wp-http.php */
				do_action( 'http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url );
				return $response;
			}
		}

		if ( is_null( $parsed_args['headers'] ) ) {
			$parsed_args['headers'] = array();
		}

		// WP cho phép truyền header dưới dạng chuỗi, điều kỳ lạ.
		if ( ! is_array( $parsed_args['headers'] ) ) {
			$processed_headers      = WP_Http::processHeaders( $parsed_args['headers'] );
			$parsed_args['headers'] = $processed_headers['headers'];
		}

		// Thiết lập các đối số.
		$headers = $parsed_args['headers'];
		$data    = $parsed_args['body'];
		$type    = $parsed_args['method'];
		$options = array(
			'timeout'   => $parsed_args['timeout'],
			'useragent' => $parsed_args['user-agent'],
			'blocking'  => $parsed_args['blocking'],
			'hooks'     => new WP_HTTP_Requests_Hooks( $url, $parsed_args ),
		);

		// Đảm bảo chuyển hướng theo hành vi trình duyệt.
		$options['hooks']->register( 'requests.before_redirect', array( static::class, 'browser_redirect_compatibility' ) );

		// Xác thực các URL đã chuyển hướng.
		if ( function_exists( 'wp_kses_bad_protocol' ) && $parsed_args['reject_unsafe_urls'] ) {
			$options['hooks']->register( 'requests.before_redirect', array( static::class, 'validate_redirects' ) );
		}

		if ( $parsed_args['stream'] ) {
			$options['filename'] = $parsed_args['filename'];
		}
		if ( empty( $parsed_args['redirection'] ) ) {
			$options['follow_redirects'] = false;
		} else {
			$options['redirects'] = $parsed_args['redirection'];
		}

		// Sử dụng giới hạn byte, nếu có thể.
		if ( isset( $parsed_args['limit_response_size'] ) ) {
			$options['max_bytes'] = $parsed_args['limit_response_size'];
		}

		// Nếu có cookie, sử dụng và chuyển đổi chúng sang WpOrg\Requests\Cookie.
		if ( ! empty( $parsed_args['cookies'] ) ) {
			$options['cookies'] = WP_Http::normalize_cookies( $parsed_args['cookies'] );
		}

		// Xử lý chứng chỉ SSL.
		if ( ! $parsed_args['sslverify'] ) {
			$options['verify']     = false;
			$options['verifyname'] = false;
		} else {
			$options['verify'] = $parsed_args['sslcertificates'];
		}

		// Tất cả các yêu cầu không phải GET/HEAD nên đặt đối số trong nội dung form.
		if ( 'HEAD' !== $type && 'GET' !== $type ) {
			$options['data_format'] = 'body';
		}

		/**
		 * Lọc có nên xác minh SSL cho các yêu cầu không phải cục bộ hay không.
		 *
		 * @since 2.8.0
		 * @since 5.1.0 Tham số `$url` được thêm vào.
		 *
		 * @param bool|string $ssl_verify Boolean để kiểm soát có xác minh kết nối SSL hay không
		 *                                hoặc đường dẫn đến chứng chỉ SSL.
		 * @param string      $url        URL yêu cầu.
		 */
		$options['verify'] = apply_filters( 'https_ssl_verify', $options['verify'], $url );

		// Kiểm tra proxy.
		$proxy = new WP_HTTP_Proxy();
		if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) {
			$options['proxy'] = new WpOrg\Requests\Proxy\Http( $proxy->host() . ':' . $proxy->port() );

			if ( $proxy->use_authentication() ) {
				$options['proxy']->use_authentication = true;
				$options['proxy']->user               = $proxy->username();
				$options['proxy']->pass               = $proxy->password();
			}
		}

		// Tránh các vấn đề khi mbstring.func_overload được bật.
		mbstring_binary_safe_encoding();

		try {
			$requests_response = WpOrg\Requests\Requests::request( $url, $headers, $data, $type, $options );

			// Chuyển đổi phản hồi thành mảng.
			$http_response = new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] );
			$response      = $http_response->to_array();

			// Thêm đối tượng gốc vào mảng.
			$response['http_response'] = $http_response;
		} catch ( WpOrg\Requests\Exception $e ) {
			$response = new WP_Error( 'http_request_failed', $e->getMessage() );
		}

		reset_mbstring_encoding();

		/**
		 * Kích hoạt sau khi nhận phản hồi HTTP API và trước khi phản hồi được trả về.
		 *
		 * @since 2.8.0
		 *
		 * @param array|WP_Error $response    Phản hồi HTTP hoặc đối tượng WP_Error.
		 * @param string         $context     Ngữ cảnh mà hook được kích hoạt.
		 * @param string         $class       Phương thức truyền tải HTTP được sử dụng.
		 * @param array          $parsed_args Các đối số yêu cầu HTTP.
		 * @param string         $url         URL yêu cầu.
		 */
		do_action( 'http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $parsed_args, $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $parsed_args['blocking'] ) {
			return array(
				'headers'       => array(),
				'body'          => '',
				'response'      => array(
					'code'    => false,
					'message' => false,
				),
				'cookies'       => array(),
				'http_response' => null,
			);
		}

		/**
		 * Lọc phản hồi HTTP API thành công ngay trước khi phản hồi được trả về.
		 *
		 * @since 2.9.0
		 *
		 * @param array  $response    Phản hồi HTTP.
		 * @param array  $parsed_args Các đối số yêu cầu HTTP.
		 * @param string $url         URL yêu cầu.
		 */
		return apply_filters( 'http_response', $response, $parsed_args, $url );
	}

	/**
	 * Chuẩn hóa cookie để sử dụng trong Requests.
	 *
	 * @since 4.6.0
	 *
	 * @param array $cookies Mảng cookie gửi kèm yêu cầu.
	 * @return WpOrg\Requests\Cookie\Jar Đối tượng chứa cookie.
	 */
	public static function normalize_cookies( $cookies ) {
		$cookie_jar = new WpOrg\Requests\Cookie\Jar();

		foreach ( $cookies as $name => $value ) {
			if ( $value instanceof WP_Http_Cookie ) {
				$attributes                 = array_filter(
					$value->get_attributes(),
					static function ( $attr ) {
						return null !== $attr;
					}
				);
				$cookie_jar[ $value->name ] = new WpOrg\Requests\Cookie( (string) $value->name, $value->value, $attributes, array( 'host-only' => $value->host_only ) );
			} elseif ( is_scalar( $value ) ) {
				$cookie_jar[ $name ] = new WpOrg\Requests\Cookie( (string) $name, (string) $value );
			}
		}

		return $cookie_jar;
	}

	/**
	 * Khớp hành vi chuyển hướng với cách xử lý của trình duyệt.
	 *
	 * Thay đổi chuyển hướng 302 từ POST sang GET để khớp với cách xử lý trình duyệt. Theo
	 * RFC 7231, các user agent có thể lệch khỏi cách đọc nghiêm ngặt của
	 * đặc tả vì mục đích tương thích.
	 *
	 * @since 4.6.0
	 *
	 * @param string                  $location URL để chuyển hướng đến.
	 * @param array                   $headers  Các header cho chuyển hướng.
	 * @param string|array            $data     Nội dung gửi kèm yêu cầu.
	 * @param array                   $options  Các tùy chọn yêu cầu chuyển hướng.
	 * @param WpOrg\Requests\Response $original Đối tượng phản hồi.
	 */
	public static function browser_redirect_compatibility( $location, $headers, $data, &$options, $original ) {
		// Tương thích trình duyệt.
		if ( 302 === $original->status_code ) {
			$options['type'] = WpOrg\Requests\Requests::GET;
		}
	}

	/**
	 * Xác thực các URL đã chuyển hướng.
	 *
	 * @since 4.7.5
	 *
	 * @throws WpOrg\Requests\Exception Khi xác thực URL không thành công.
	 * @param string $location URL để chuyển hướng đến.
	 */
	public static function validate_redirects( $location ) {
		if ( ! wp_http_validate_url( $location ) ) {
			throw new WpOrg\Requests\Exception( __( 'A valid URL was not provided.' ), 'wp_http.redirect_failed_validation' );
		}
	}

	/**
	 * Kiểm tra phương thức truyền tải nào có khả năng hỗ trợ yêu cầu.
	 *
	 * @since 3.2.0
	 * @deprecated 6.4.0 Sử dụng WpOrg\Requests\Requests::get_transport_class()
	 * @see WpOrg\Requests\Requests::get_transport_class()
	 *
	 * @param array  $args Các đối số yêu cầu.
	 * @param string $url  URL cần yêu cầu.
	 * @return string|false Tên lớp của phương thức truyền tải đầu tiên tuyên bố hỗ trợ yêu cầu.
	 *                      False nếu không có phương thức nào tuyên bố hỗ trợ.
	 */
	public function _get_first_available_transport( $args, $url = null ) {
		$transports = array( 'curl', 'streams' );

		/**
		 * Lọc các phương thức truyền tải HTTP nào khả dụng và theo thứ tự nào.
		 *
		 * @since 3.7.0
		 * @deprecated 6.4.0 Sử dụng WpOrg\Requests\Requests::get_transport_class()
		 *
		 * @param string[] $transports Mảng các phương thức truyền tải HTTP cần kiểm tra. Mảng mặc định chứa
		 *                             'curl' và 'streams', theo thứ tự đó.
		 * @param array    $args       Các đối số yêu cầu HTTP.
		 * @param string   $url        URL cần yêu cầu.
		 */
		$request_order = apply_filters_deprecated( 'http_api_transports', array( $transports, $args, $url ), '6.4.0' );

		// Duyệt qua từng phương thức truyền tải cho mỗi yêu cầu HTTP để tìm phương thức phục vụ nhu cầu của yêu cầu.
		foreach ( $request_order as $transport ) {
			if ( in_array( $transport, $transports, true ) ) {
				$transport = ucfirst( $transport );
			}
			$class = 'WP_Http_' . $transport;

			// Kiểm tra xem phương thức truyền tải này có khả thi không, gọi phương thức truyền tải tĩnh.
			if ( ! call_user_func( array( $class, 'test' ), $args, $url ) ) {
				continue;
			}

			return $class;
		}

		return false;
	}

	/**
	 * Điều phối yêu cầu HTTP đến phương thức truyền tải hỗ trợ.
	 *
	 * Kiểm tra từng phương thức truyền tải để tìm phương thức khớp với đối số yêu cầu.
	 * Cũng lưu đệm thể hiện phương thức truyền tải để sử dụng sau.
	 *
	 * Thứ tự cho các yêu cầu là cURL, sau đó là PHP Streams.
	 *
	 * @since 3.2.0
	 * @deprecated 5.1.0 Sử dụng WP_Http::request()
	 * @see WP_Http::request()
	 *
	 * @param string $url  URL cần yêu cầu.
	 * @param array  $args Các đối số yêu cầu.
	 * @return array|WP_Error Mảng chứa 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        Thể hiện WP_Error khi có lỗi.
	 */
	private function _dispatch_request( $url, $args ) {
		static $transports = array();

		$class = $this->_get_first_available_transport( $args, $url );
		if ( ! $class ) {
			return new WP_Error( 'http_failure', __( 'There are no HTTP transports available which can complete the requested request.' ) );
		}

		// Phương thức truyền tải tuyên bố hỗ trợ yêu cầu, khởi tạo và thử nghiệm nó.
		if ( empty( $transports[ $class ] ) ) {
			$transports[ $class ] = new $class();
		}

		$response = $transports[ $class ]->request( $url, $args );

		/** Action này được ghi chú trong wp-includes/class-wp-http.php */
		do_action( 'http_api_debug', $response, 'response', $class, $args, $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		/** Bộ lọc này được ghi chú trong wp-includes/class-wp-http.php */
		return apply_filters( 'http_response', $response, $args, $url );
	}

	/**
	 * Sử dụng phương thức HTTP POST.
	 *
	 * Dùng để gửi dữ liệu dự kiến nằm trong nội dung.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  URL yêu cầu.
	 * @param string|array $args Tùy chọn. Ghi đè các giá trị mặc định.
	 * @return array|WP_Error Mảng chứa 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        Thể hiện WP_Error khi có lỗi. Xem WP_Http::response() để biết chi tiết.
	 */
	public function post( $url, $args = array() ) {
		$defaults    = array( 'method' => 'POST' );
		$parsed_args = wp_parse_args( $args, $defaults );
		return $this->request( $url, $parsed_args );
	}

	/**
	 * Sử dụng phương thức HTTP GET.
	 *
	 * Dùng để gửi dữ liệu dự kiến nằm trong nội dung.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  URL yêu cầu.
	 * @param string|array $args Tùy chọn. Ghi đè các giá trị mặc định.
	 * @return array|WP_Error Mảng chứa 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        Thể hiện WP_Error khi có lỗi. Xem WP_Http::response() để biết chi tiết.
	 */
	public function get( $url, $args = array() ) {
		$defaults    = array( 'method' => 'GET' );
		$parsed_args = wp_parse_args( $args, $defaults );
		return $this->request( $url, $parsed_args );
	}

	/**
	 * Sử dụng phương thức HTTP HEAD.
	 *
	 * Dùng để gửi dữ liệu dự kiến nằm trong nội dung.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  URL yêu cầu.
	 * @param string|array $args Tùy chọn. Ghi đè các giá trị mặc định.
	 * @return array|WP_Error Mảng chứa 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        Thể hiện WP_Error khi có lỗi. Xem WP_Http::response() để biết chi tiết.
	 */
	public function head( $url, $args = array() ) {
		$defaults    = array( 'method' => 'HEAD' );
		$parsed_args = wp_parse_args( $args, $defaults );
		return $this->request( $url, $parsed_args );
	}

	/**
	 * Phân tích các phản hồi và tách các phần thành header và nội dung.
	 *
	 * @since 2.7.0
	 *
	 * @param string $response Chuỗi phản hồi đầy đủ.
	 * @return array {
	 *     Mảng chứa header và nội dung phản hồi.
	 *
	 *     @type string $headers Các header phản hồi HTTP.
	 *     @type string $body    Nội dung phản hồi HTTP.
	 * }
	 */
	public static function processResponse( $response ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$response = explode( "\r\n\r\n", $response, 2 );

		return array(
			'headers' => $response[0],
			'body'    => isset( $response[1] ) ? $response[1] : '',
		);
	}

	/**
	 * Chuyển đổi chuỗi header thành mảng.
	 *
	 * @since 2.7.0
	 *
	 * @param string|array $headers Các header gốc. Nếu truyền chuỗi, nó sẽ được chuyển đổi
	 *                              thành mảng. Nếu truyền mảng, giả định đó là dữ liệu header
	 *                              thô với khóa số và header là giá trị.
	 *                              Không được truyền header đã xử lý.
	 * @param string       $url     Tùy chọn. URL đã được yêu cầu. Mặc định rỗng.
	 * @return array {
	 *     Các header chuỗi đã xử lý. Nếu gặp header trùng lặp,
	 *     mảng có số thứ tự sẽ được trả về làm giá trị của khóa header đó.
	 *
	 *     @type array            $response {
	 *         @type int    $code    Mã trạng thái phản hồi. Mặc định 0.
	 *         @type string $message Thông điệp phản hồi. Mặc định rỗng.
	 *     }
	 *     @type array            $newheaders Dữ liệu header đã xử lý dưới dạng mảng đa chiều.
	 *     @type WP_Http_Cookie[] $cookies    Nếu header gốc chứa khóa 'Set-Cookie',
	 *                                        mảng chứa các đối tượng `WP_Http_Cookie` sẽ được trả về.
	 * }
	 */
	public static function processHeaders( $headers, $url = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		// Tách header, mỗi phần tử mảng một header.
		if ( is_string( $headers ) ) {
			// Tolerate line terminator: CRLF = LF (RFC 2616 19.3).
			$headers = str_replace( "\r\n", "\n", $headers );
			/*
			 * Unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>,
			 * <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2).
			 */
			$headers = preg_replace( '/\n[ \t]/', ' ', $headers );
			// Create the headers array.
			$headers = explode( "\n", $headers );
		}

		$response = array(
			'code'    => 0,
			'message' => '',
		);

		/*
		 * If a redirection has taken place, The headers for each page request may have been passed.
		 * In this case, determine the final HTTP header and parse from there.
		 */
		for ( $i = count( $headers ) - 1; $i >= 0; $i-- ) {
			if ( ! empty( $headers[ $i ] ) && ! str_contains( $headers[ $i ], ':' ) ) {
				$headers = array_splice( $headers, $i );
				break;
			}
		}

		$cookies    = array();
		$newheaders = array();
		foreach ( (array) $headers as $tempheader ) {
			if ( empty( $tempheader ) ) {
				continue;
			}

			if ( ! str_contains( $tempheader, ':' ) ) {
				$stack   = explode( ' ', $tempheader, 3 );
				$stack[] = '';
				list( , $response['code'], $response['message']) = $stack;
				continue;
			}

			list($key, $value) = explode( ':', $tempheader, 2 );

			$key   = strtolower( $key );
			$value = trim( $value );

			if ( isset( $newheaders[ $key ] ) ) {
				if ( ! is_array( $newheaders[ $key ] ) ) {
					$newheaders[ $key ] = array( $newheaders[ $key ] );
				}
				$newheaders[ $key ][] = $value;
			} else {
				$newheaders[ $key ] = $value;
			}
			if ( 'set-cookie' === $key ) {
				$cookies[] = new WP_Http_Cookie( $value, $url );
			}
		}

		// Cast the Response Code to an int.
		$response['code'] = (int) $response['code'];

		return array(
			'response' => $response,
			'headers'  => $newheaders,
			'cookies'  => $cookies,
		);
	}

	/**
	 * Takes the arguments for a ::request() and checks for the cookie array.
	 *
	 * If it's found, then it upgrades any basic name => value pairs to WP_Http_Cookie instances,
	 * which are each parsed into strings and added to the Cookie: header (within the arguments array).
	 * Edits the array by reference.
	 *
	 * @since 2.8.0
	 *
	 * @param array $r Full array of args passed into ::request()
	 */
	public static function buildCookieHeader( &$r ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		if ( ! empty( $r['cookies'] ) ) {
			// Upgrade any name => value cookie pairs to WP_HTTP_Cookie instances.
			foreach ( $r['cookies'] as $name => $value ) {
				if ( ! is_object( $value ) ) {
					$r['cookies'][ $name ] = new WP_Http_Cookie(
						array(
							'name'  => $name,
							'value' => $value,
						)
					);
				}
			}

			$cookies_header = '';
			foreach ( (array) $r['cookies'] as $cookie ) {
				$cookies_header .= $cookie->getHeaderValue() . '; ';
			}

			$cookies_header         = substr( $cookies_header, 0, -2 );
			$r['headers']['cookie'] = $cookies_header;
		}
	}

	/**
	 * Decodes chunk transfer-encoding, based off the HTTP 1.1 specification.
	 *
	 * Based off the HTTP http_encoding_dechunk function.
	 *
	 * @link https://tools.ietf.org/html/rfc2616#section-19.4.6 Process for chunked decoding.
	 *
	 * @since 2.7.0
	 *
	 * @param string $body Body content.
	 * @return string Chunked decoded body on success or raw body on failure.
	 */
	public static function chunkTransferDecode( $body ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		// The body is not chunked encoded or is malformed.
		if ( ! preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i', trim( $body ) ) ) {
			return $body;
		}

		$parsed_body = '';

		// We'll be altering $body, so need a backup in case of error.
		$body_original = $body;

		while ( true ) {
			$has_chunk = (bool) preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i', $body, $match );
			if ( ! $has_chunk || empty( $match[1] ) ) {
				return $body_original;
			}

			$length       = hexdec( $match[1] );
			$chunk_length = strlen( $match[0] );

			// Parse out the chunk of data.
			$parsed_body .= substr( $body, $chunk_length, $length );

			// Remove the chunk from the raw data.
			$body = substr( $body, $length + $chunk_length );

			// End of the document.
			if ( '0' === trim( $body ) ) {
				return $parsed_body;
			}
		}
	}

	/**
	 * Determines whether an HTTP API request to the given URL should be blocked.
	 *
	 * Those who are behind a proxy and want to prevent access to certain hosts may do so. This will
	 * prevent plugins from working and core functionality, if you don't include `api.wordpress.org`.
	 *
	 * You block external URL requests by defining `WP_HTTP_BLOCK_EXTERNAL` as true in your `wp-config.php`
	 * file and this will only allow localhost and your site to make requests. The constant
	 * `WP_ACCESSIBLE_HOSTS` will allow additional hosts to go through for requests. The format of the
	 * `WP_ACCESSIBLE_HOSTS` constant is a comma separated list of hostnames to allow, wildcard domains
	 * are supported, eg `*.wordpress.org` will allow for all subdomains of `wordpress.org` to be contacted.
	 *
	 * @since 2.8.0
	 *
	 * @link https://core.trac.wordpress.org/ticket/8927 Allow preventing external requests.
	 * @link https://core.trac.wordpress.org/ticket/14636 Allow wildcard domains in WP_ACCESSIBLE_HOSTS
	 *
	 * @param string $uri URI of url.
	 * @return bool True to block, false to allow.
	 */
	public function block_request( $uri ) {
		// We don't need to block requests, because nothing is blocked.
		if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) {
			return false;
		}

		$check = parse_url( $uri );
		if ( ! $check ) {
			return true;
		}

		$home = parse_url( get_option( 'siteurl' ) );

		// Don't block requests back to ourselves by default.
		if ( 'localhost' === $check['host'] || ( isset( $home['host'] ) && $home['host'] === $check['host'] ) ) {
			/**
			 * Filters whether to block local HTTP API requests.
			 *
			 * A local request is one to `localhost` or to the same host as the site itself.
			 *
			 * @since 2.8.0
			 *
			 * @param bool $block Whether to block local requests. Default false.
			 */
			return apply_filters( 'block_local_requests', false );
		}

		if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
			return true;
		}

		static $accessible_hosts = null;
		static $wildcard_regex   = array();
		if ( null === $accessible_hosts ) {
			$accessible_hosts = preg_split( '|,\s*|', WP_ACCESSIBLE_HOSTS );

			if ( str_contains( WP_ACCESSIBLE_HOSTS, '*' ) ) {
				$wildcard_regex = array();
				foreach ( $accessible_hosts as $host ) {
					$wildcard_regex[] = str_replace( '\*', '.+', preg_quote( $host, '/' ) );
				}
				$wildcard_regex = '/^(' . implode( '|', $wildcard_regex ) . ')$/i';
			}
		}

		if ( ! empty( $wildcard_regex ) ) {
			return ! preg_match( $wildcard_regex, $check['host'] );
		} else {
			return ! in_array( $check['host'], $accessible_hosts, true ); // Inverse logic, if it's in the array, then don't block it.
		}
	}

	/**
	 * Used as a wrapper for PHP's parse_url() function that handles edgecases in < PHP 5.4.7.
	 *
	 * @deprecated 4.4.0 Use wp_parse_url()
	 * @see wp_parse_url()
	 *
	 * @param string $url The URL to parse.
	 * @return bool|array False on failure; Array of URL components on success;
	 *                    See parse_url()'s return values.
	 */
	protected static function parse_url( $url ) {
		_deprecated_function( __METHOD__, '4.4.0', 'wp_parse_url()' );
		return wp_parse_url( $url );
	}

	/**
	 * Converts a relative URL to an absolute URL relative to a given URL.
	 *
	 * If an Absolute URL is provided, no processing of that URL is done.
	 *
	 * @since 3.4.0
	 *
	 * @param string $maybe_relative_path The URL which might be relative.
	 * @param string $url                 The URL which $maybe_relative_path is relative to.
	 * @return string An Absolute URL, in a failure condition where the URL cannot be parsed, the relative URL will be returned.
	 */
	public static function make_absolute_url( $maybe_relative_path, $url ) {
		if ( empty( $url ) ) {
			return $maybe_relative_path;
		}

		$url_parts = wp_parse_url( $url );
		if ( ! $url_parts ) {
			return $maybe_relative_path;
		}

		$relative_url_parts = wp_parse_url( $maybe_relative_path );
		if ( ! $relative_url_parts ) {
			return $maybe_relative_path;
		}

		// Check for a scheme on the 'relative' URL.
		if ( ! empty( $relative_url_parts['scheme'] ) ) {
			return $maybe_relative_path;
		}

		$absolute_path = $url_parts['scheme'] . '://';

		// Schemeless URLs will make it this far, so we check for a host in the relative URL
		// and convert it to a protocol-URL.
		if ( isset( $relative_url_parts['host'] ) ) {
			$absolute_path .= $relative_url_parts['host'];
			if ( isset( $relative_url_parts['port'] ) ) {
				$absolute_path .= ':' . $relative_url_parts['port'];
			}
		} else {
			$absolute_path .= $url_parts['host'];
			if ( isset( $url_parts['port'] ) ) {
				$absolute_path .= ':' . $url_parts['port'];
			}
		}

		// Start off with the absolute URL path.
		$path = ! empty( $url_parts['path'] ) ? $url_parts['path'] : '/';

		// If it's a root-relative path, then great.
		if ( ! empty( $relative_url_parts['path'] ) && '/' === $relative_url_parts['path'][0] ) {
			$path = $relative_url_parts['path'];

			// Else it's a relative path.
		} elseif ( ! empty( $relative_url_parts['path'] ) ) {
			// Strip off any file components from the absolute path.
			$path = substr( $path, 0, strrpos( $path, '/' ) + 1 );

			// Build the new path.
			$path .= $relative_url_parts['path'];

			// Strip all /path/../ out of the path.
			while ( strpos( $path, '../' ) > 1 ) {
				$path = preg_replace( '![^/]+/\.\./!', '', $path );
			}

			// Strip any final leading ../ from the path.
			$path = preg_replace( '!^/(\.\./)+!', '', $path );
		}

		// Add the query string.
		if ( ! empty( $relative_url_parts['query'] ) ) {
			$path .= '?' . $relative_url_parts['query'];
		}

		// Add the fragment.
		if ( ! empty( $relative_url_parts['fragment'] ) ) {
			$path .= '#' . $relative_url_parts['fragment'];
		}

		return $absolute_path . '/' . ltrim( $path, '/' );
	}

	/**
	 * Handles an HTTP redirect and follows it if appropriate.
	 *
	 * @since 3.7.0
	 *
	 * @param string $url      The URL which was requested.
	 * @param array  $args     The arguments which were used to make the request.
	 * @param array  $response The response of the HTTP request.
	 * @return array|false|WP_Error An HTTP API response array if the redirect is successfully followed,
	 *                              false if no redirect is present, or a WP_Error object if there's an error.
	 */
	public static function handle_redirects( $url, $args, $response ) {
		// If no redirects are present, or, redirects were not requested, perform no action.
		if ( ! isset( $response['headers']['location'] ) || 0 === $args['_redirection'] ) {
			return false;
		}

		// Only perform redirections on redirection http codes.
		if ( $response['response']['code'] > 399 || $response['response']['code'] < 300 ) {
			return false;
		}

		// Don't redirect if we've run out of redirects.
		if ( $args['redirection']-- <= 0 ) {
			return new WP_Error( 'http_request_failed', __( 'Too many redirects.' ) );
		}

		$redirect_location = $response['headers']['location'];

		// If there were multiple Location headers, use the last header specified.
		if ( is_array( $redirect_location ) ) {
			$redirect_location = array_pop( $redirect_location );
		}

		$redirect_location = WP_Http::make_absolute_url( $redirect_location, $url );

		// POST requests should not POST to a redirected location.
		if ( 'POST' === $args['method'] ) {
			if ( in_array( $response['response']['code'], array( 302, 303 ), true ) ) {
				$args['method'] = 'GET';
			}
		}

		// Include valid cookies in the redirect process.
		if ( ! empty( $response['cookies'] ) ) {
			foreach ( $response['cookies'] as $cookie ) {
				if ( $cookie->test( $redirect_location ) ) {
					$args['cookies'][] = $cookie;
				}
			}
		}

		return wp_remote_request( $redirect_location, $args );
	}

	/**
	 * Determines if a specified string represents an IP address or not.
	 *
	 * This function also detects the type of the IP address, returning either
	 * '4' or '6' to represent an IPv4 and IPv6 address respectively.
	 * This does not verify if the IP is a valid IP, only that it appears to be
	 * an IP address.
	 *
	 * @link http://home.deds.nl/~aeron/regex/ for IPv6 regex.
	 *
	 * @since 3.7.0
	 *
	 * @param string $maybe_ip A suspected IP address.
	 * @return int|false Upon success, '4' or '6' to represent an IPv4 or IPv6 address, false upon failure.
	 */
	public static function is_ip_address( $maybe_ip ) {
		if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $maybe_ip ) ) {
			return 4;
		}

		if ( str_contains( $maybe_ip, ':' ) && preg_match( '/^(((?=.*(::))(?!.*\3.+\3))\3?|([\dA-F]{1,4}(\3|:\b|$)|\2))(?4){5}((?4){2}|(((2[0-4]|1\d|[1-9])?\d|25[0-5])\.?\b){4})$/i', trim( $maybe_ip, ' []' ) ) ) {
			return 6;
		}

		return false;
	}
}
