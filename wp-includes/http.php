<?php
/**
 * API Yêu cầu HTTP Lõi
 *
 * Chuẩn hóa các yêu cầu HTTP cho WordPress. Xử lý cookie, mã hóa và giải mã gzip, giải mã chunk,
 * nếu HTTP 1.1 và nhiều triển khai giao thức HTTP phức tạp khác.
 *
 * @package WordPress
 * @subpackage HTTP
 */

/**
 * Trả về đối tượng WP_Http đã được khởi tạo
 *
 * @since 2.7.0
 * @access private
 *
 * @return WP_Http Đối tượng HTTP Transport.
 */
function _wp_http_get_object() {
	static $http = null;

	if ( is_null( $http ) ) {
		$http = new WP_Http();
	}
	return $http;
}

/**
 * Lấy phản hồi thô từ một yêu cầu HTTP an toàn.
 *
 * Hàm này lý tưởng khi yêu cầu HTTP được thực hiện tới một URL tùy ý.
 * URL và mọi URL mà nó chuyển hướng tới đều được xác thực bằng wp_http_validate_url()
 * để tránh các cuộc tấn công Server Side Request Forgery (SSRF).
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 * @see wp_http_validate_url() Để biết thêm thông tin về cách URL được xác thực.
 *
 * @link https://owasp.org/www-community/attacks/Server_Side_Request_Forgery
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_safe_remote_request( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->request( $url, $args );
}

/**
 * Lấy phản hồi thô từ một yêu cầu HTTP an toàn sử dụng phương thức GET.
 *
 * Hàm này lý tưởng khi yêu cầu HTTP được thực hiện tới một URL tùy ý.
 * URL và mọi URL mà nó chuyển hướng tới đều được xác thực bằng wp_http_validate_url()
 * để tránh các cuộc tấn công Server Side Request Forgery (SSRF).
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 * @see wp_http_validate_url() Để biết thêm thông tin về cách URL được xác thực.
 *
 * @link https://owasp.org/www-community/attacks/Server_Side_Request_Forgery
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_safe_remote_get( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->get( $url, $args );
}

/**
 * Lấy phản hồi thô từ một yêu cầu HTTP an toàn sử dụng phương thức POST.
 *
 * Hàm này lý tưởng khi yêu cầu HTTP được thực hiện tới một URL tùy ý.
 * URL và mọi URL mà nó chuyển hướng tới đều được xác thực bằng wp_http_validate_url()
 * để tránh các cuộc tấn công Server Side Request Forgery (SSRF).
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 * @see wp_http_validate_url() Để biết thêm thông tin về cách URL được xác thực.
 *
 * @link https://owasp.org/www-community/attacks/Server_Side_Request_Forgery
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_safe_remote_post( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->post( $url, $args );
}

/**
 * Lấy phản hồi thô từ một yêu cầu HTTP an toàn sử dụng phương thức HEAD.
 *
 * Hàm này lý tưởng khi yêu cầu HTTP được thực hiện tới một URL tùy ý.
 * URL và mọi URL mà nó chuyển hướng tới đều được xác thực bằng wp_http_validate_url()
 * để tránh các cuộc tấn công Server Side Request Forgery (SSRF).
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 * @see wp_http_validate_url() Để biết thêm thông tin về cách URL được xác thực.
 *
 * @link https://owasp.org/www-community/attacks/Server_Side_Request_Forgery
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_safe_remote_head( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->head( $url, $args );
}

/**
 * Thực hiện một yêu cầu HTTP và trả về phản hồi của nó.
 *
 * Có các hàm API khác trừu tượng hóa phương thức HTTP:
 *
 *  - Mặc định 'GET'  cho wp_remote_get()
 *  - Mặc định 'POST' cho wp_remote_post()
 *  - Mặc định 'HEAD' cho wp_remote_head()
 *
 * @since 2.7.0
 *
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Mảng phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_remote_request( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->request( $url, $args );
}

/**
 * Thực hiện một yêu cầu HTTP sử dụng phương thức GET và trả về phản hồi.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_remote_get( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->get( $url, $args );
}

/**
 * Thực hiện một yêu cầu HTTP sử dụng phương thức POST và trả về phản hồi.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_remote_post( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->post( $url, $args );
}

/**
 * Thực hiện một yêu cầu HTTP sử dụng phương thức HEAD và trả về phản hồi.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() Để biết thêm thông tin về định dạng mảng phản hồi.
 * @see WP_Http::request() Để biết thông tin về các đối số mặc định.
 *
 * @param string $url  URL cần lấy.
 * @param array  $args Tùy chọn. Các đối số yêu cầu. Mặc định mảng rỗng.
 *                     Xem WP_Http::request() để biết thông tin về các đối số được chấp nhận.
 * @return array|WP_Error Phản hồi hoặc WP_Error khi thất bại.
 *                        Xem WP_Http::request() để biết thông tin về giá trị trả về.
 */
function wp_remote_head( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->head( $url, $args );
}

/**
 * Lấy chỉ các header từ phản hồi thô.
 *
 * @since 2.7.0
 * @since 4.6.0 Giá trị trả về thay đổi từ mảng sang instance WpOrg\Requests\Utility\CaseInsensitiveDictionary.
 *
 * @see \WpOrg\Requests\Utility\CaseInsensitiveDictionary
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @return \WpOrg\Requests\Utility\CaseInsensitiveDictionary|array Các header của phản hồi, hoặc mảng rỗng
 *                                                                 nếu tham số không đúng.
 */
function wp_remote_retrieve_headers( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['headers'] ) ) {
		return array();
	}

	return $response['headers'];
}

/**
 * Lấy một header đơn lẻ theo tên từ phản hồi thô.
 *
 * @since 2.7.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @param string         $header   Tên header cần lấy giá trị.
 * @return array|string Giá trị header. Mảng nếu có nhiều header cùng tên được lấy.
 *                      Chuỗi rỗng nếu tham số không đúng, hoặc header không tồn tại.
 */
function wp_remote_retrieve_header( $response, $header ) {
	if ( is_wp_error( $response ) || ! isset( $response['headers'] ) ) {
		return '';
	}

	if ( isset( $response['headers'][ $header ] ) ) {
		return $response['headers'][ $header ];
	}

	return '';
}

/**
 * Lấy chỉ mã phản hồi từ phản hồi thô.
 *
 * Sẽ trả về chuỗi rỗng nếu giá trị tham số không đúng.
 *
 * @since 2.7.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @return int|string Mã phản hồi dạng số nguyên. Chuỗi rỗng nếu tham số không đúng.
 */
function wp_remote_retrieve_response_code( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['response'] ) || ! is_array( $response['response'] ) ) {
		return '';
	}

	return $response['response']['code'];
}

/**
 * Lấy chỉ thông báo phản hồi từ phản hồi thô.
 *
 * Sẽ trả về chuỗi rỗng nếu giá trị tham số không đúng.
 *
 * @since 2.7.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @return string Thông báo phản hồi. Chuỗi rỗng nếu tham số không đúng.
 */
function wp_remote_retrieve_response_message( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['response'] ) || ! is_array( $response['response'] ) ) {
		return '';
	}

	return $response['response']['message'];
}

/**
 * Lấy chỉ phần thân từ phản hồi thô.
 *
 * @since 2.7.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @return string Phần thân của phản hồi. Chuỗi rỗng nếu không có thân hoặc tham số không đúng.
 */
function wp_remote_retrieve_body( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
		return '';
	}

	return $response['body'];
}

/**
 * Lấy chỉ các cookie từ phản hồi thô.
 *
 * @since 4.4.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @return WP_Http_Cookie[] Mảng các đối tượng `WP_Http_Cookie` từ phản hồi.
 *                          Mảng rỗng nếu không có cookie, hoặc phản hồi là WP_Error.
 */
function wp_remote_retrieve_cookies( $response ) {
	if ( is_wp_error( $response ) || empty( $response['cookies'] ) ) {
		return array();
	}

	return $response['cookies'];
}

/**
 * Lấy một cookie đơn lẻ theo tên từ phản hồi thô.
 *
 * @since 4.4.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @param string         $name     Tên cookie cần lấy.
 * @return WP_Http_Cookie|string Đối tượng `WP_Http_Cookie`, hoặc chuỗi rỗng
 *                               nếu cookie không có trong phản hồi.
 */
function wp_remote_retrieve_cookie( $response, $name ) {
	$cookies = wp_remote_retrieve_cookies( $response );

	if ( empty( $cookies ) ) {
		return '';
	}

	foreach ( $cookies as $cookie ) {
		if ( $cookie->name === $name ) {
			return $cookie;
		}
	}

	return '';
}

/**
 * Lấy giá trị của một cookie đơn lẻ theo tên từ phản hồi thô.
 *
 * @since 4.4.0
 *
 * @param array|WP_Error $response Phản hồi HTTP.
 * @param string         $name     Tên cookie cần lấy.
 * @return string Giá trị của cookie, hoặc chuỗi rỗng
 *                nếu cookie không có trong phản hồi.
 */
function wp_remote_retrieve_cookie_value( $response, $name ) {
	$cookie = wp_remote_retrieve_cookie( $response, $name );

	if ( ! ( $cookie instanceof WP_Http_Cookie ) ) {
		return '';
	}

	return $cookie->value;
}

/**
 * Xác định xem có HTTP Transport nào có thể xử lý yêu cầu này không.
 *
 * @since 3.2.0
 *
 * @param array  $capabilities Mảng các khả năng cần kiểm tra hoặc mảng $args của wp_remote_request().
 * @param string $url          Tùy chọn. Nếu được cung cấp, sẽ kiểm tra xem URL có yêu cầu SSL không và thêm
 *                             yêu cầu đó vào mảng khả năng.
 *
 * @return bool
 */
function wp_http_supports( $capabilities = array(), $url = null ) {
	$capabilities = wp_parse_args( $capabilities );

	$count = count( $capabilities );

	// Nếu có mảng $capabilities dạng số, giả lập mảng $args kết hợp của wp_remote_request().
	if ( $count && count( array_filter( array_keys( $capabilities ), 'is_numeric' ) ) === $count ) {
		$capabilities = array_combine( array_values( $capabilities ), array_fill( 0, $count, true ) );
	}

	if ( $url && ! isset( $capabilities['ssl'] ) ) {
		$scheme = parse_url( $url, PHP_URL_SCHEME );
		if ( 'https' === $scheme || 'ssl' === $scheme ) {
			$capabilities['ssl'] = true;
		}
	}

	return WpOrg\Requests\Requests::has_capabilities( $capabilities );
}

/**
 * Lấy HTTP Origin của yêu cầu hiện tại.
 *
 * @since 3.4.0
 *
 * @return string URL của origin. Chuỗi rỗng nếu không có origin.
 */
function get_http_origin() {
	$origin = '';
	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		$origin = $_SERVER['HTTP_ORIGIN'];
	}

	/**
	 * Thay đổi origin của một yêu cầu HTTP.
	 *
	 * @since 3.4.0
	 *
	 * @param string $origin Origin ban đầu cho yêu cầu.
	 */
	return apply_filters( 'http_origin', $origin );
}

/**
 * Lấy danh sách các HTTP origin được phép.
 *
 * @since 3.4.0
 *
 * @return string[] Mảng các URL origin.
 */
function get_allowed_http_origins() {
	$admin_origin = parse_url( admin_url() );
	$home_origin  = parse_url( home_url() );

	// @todo Giữ lại port?
	$allowed_origins = array_unique(
		array(
			'http://' . $admin_origin['host'],
			'https://' . $admin_origin['host'],
			'http://' . $home_origin['host'],
			'https://' . $home_origin['host'],
		)
	);

	/**
	 * Thay đổi các loại origin được phép cho yêu cầu HTTP.
	 *
	 * @since 3.4.0
	 *
	 * @param string[] $allowed_origins {
	 *     Mảng các HTTP origin được phép mặc định.
	 *
	 *     @type string $0 URL không bảo mật cho admin origin.
	 *     @type string $1 URL bảo mật cho admin origin.
	 *     @type string $2 URL không bảo mật cho home origin.
	 *     @type string $3 URL bảo mật cho home origin.
	 * }
	 */
	return apply_filters( 'allowed_http_origins', $allowed_origins );
}

/**
 * Xác định xem HTTP origin có được phép hay không.
 *
 * @since 3.4.0
 *
 * @param string|null $origin URL Origin. Nếu không cung cấp, giá trị của get_http_origin() sẽ được sử dụng.
 * @return string URL Origin nếu được phép, chuỗi rỗng nếu không.
 */
function is_allowed_http_origin( $origin = null ) {
	$origin_arg = $origin;

	if ( null === $origin ) {
		$origin = get_http_origin();
	}

	if ( $origin && ! in_array( $origin, get_allowed_http_origins(), true ) ) {
		$origin = '';
	}

	/**
	 * Thay đổi kết quả HTTP origin được phép.
	 *
	 * @since 3.4.0
	 *
	 * @param string $origin     URL Origin nếu được phép, chuỗi rỗng nếu không.
	 * @param string $origin_arg Chuỗi origin ban đầu được truyền vào hàm is_allowed_http_origin.
	 */
	return apply_filters( 'allowed_http_origin', $origin, $origin_arg );
}

/**
 * Gửi header Access-Control-Allow-Origin và các header liên quan nếu yêu cầu
 * hiện tại đến từ một origin được phép.
 *
 * Nếu yêu cầu là OPTIONS, script sẽ thoát với các header kiểm soát truy cập
 * đã gửi, hoặc phản hồi 403 nếu origin không được phép. Đối với các phương thức
 * yêu cầu khác, bạn sẽ nhận được giá trị trả về.
 *
 * @since 3.4.0
 *
 * @return string|false Trả về URL origin nếu header được gửi. Trả về false
 *                      nếu header không được gửi.
 */
function send_origin_headers() {
	$origin = get_http_origin();

	if ( is_allowed_http_origin( $origin ) ) {
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Credentials: true' );
		if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			exit;
		}
		return $origin;
	}

	if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
		status_header( 403 );
		exit;
	}

	return false;
}

/**
 * Xác thực URL để sử dụng an toàn trong HTTP API.
 *
 * Ví dụ về các URL được coi là không an toàn:
 *
 * - ftp://example.com/caniload.php - Giao thức không hợp lệ - chỉ cho phép http và https.
 * - http:///example.com/caniload.php - URL bị lỗi định dạng.
 * - http://user:pass@example.com/caniload.php - Thông tin đăng nhập.
 * - http://example.invalid/caniload.php - Hostname không hợp lệ, vì IP không thể tra cứu trong DNS.
 *
 * Ví dụ về các URL được coi là không an toàn theo mặc định:
 *
 * - http://192.168.0.1/caniload.php - IP từ mạng LAN.
 *   Có thể thay đổi bằng bộ lọc {@see 'http_request_host_is_external'}.
 * - http://198.143.164.252:81/caniload.php - Theo mặc định, chỉ cho phép port 80, 443 và 8080.
 *   Có thể thay đổi bằng bộ lọc {@see 'http_allowed_safe_ports'}.
 *
 * @since 3.5.2
 *
 * @param string $url URL yêu cầu.
 * @return string|false URL hoặc false khi thất bại.
 */
function wp_http_validate_url( $url ) {
	if ( ! is_string( $url ) || '' === $url || is_numeric( $url ) ) {
		return false;
	}

	$original_url = $url;
	$url          = wp_kses_bad_protocol( $url, array( 'http', 'https' ) );
	if ( ! $url || strtolower( $url ) !== strtolower( $original_url ) ) {
		return false;
	}

	$parsed_url = parse_url( $url );
	if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
		return false;
	}

	if ( isset( $parsed_url['user'] ) || isset( $parsed_url['pass'] ) ) {
		return false;
	}

	if ( false !== strpbrk( $parsed_url['host'], ':#?[]' ) ) {
		return false;
	}

	$parsed_home = parse_url( get_option( 'home' ) );
	$same_host   = isset( $parsed_home['host'] ) && strtolower( $parsed_home['host'] ) === strtolower( $parsed_url['host'] );
	$host        = trim( $parsed_url['host'], '.' );

	if ( ! $same_host ) {
		if ( preg_match( '#^(([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)\.){3}([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)$#', $host ) ) {
			$ip = $host;
		} else {
			$ip = gethostbyname( $host );
			if ( $ip === $host ) { // Điều kiện lỗi cho gethostbyname().
				return false;
			}
		}
		if ( $ip ) {
			$parts = array_map( 'intval', explode( '.', $ip ) );
			if ( 127 === $parts[0] || 10 === $parts[0] || 0 === $parts[0]
				|| ( 172 === $parts[0] && 16 <= $parts[1] && 31 >= $parts[1] )
				|| ( 192 === $parts[0] && 168 === $parts[1] )
			) {
				// Nếu host có vẻ là local, từ chối trừ khi được phép cụ thể.
				/**
				 * Kiểm tra xem yêu cầu HTTP có phải là bên ngoài hay không.
				 *
				 * Cho phép thay đổi và cho phép các yêu cầu bên ngoài cho yêu cầu HTTP.
				 *
				 * @since 3.6.0
				 *
				 * @param bool   $external Yêu cầu HTTP có phải là bên ngoài hay không.
				 * @param string $host     Tên host của URL được yêu cầu.
				 * @param string $url      URL được yêu cầu.
				 */
				if ( ! apply_filters( 'http_request_host_is_external', false, $host, $url ) ) {
					return false;
				}
			}
		}
	}

	if ( empty( $parsed_url['port'] ) ) {
		return $url;
	}

	$port = $parsed_url['port'];

	/**
	 * Kiểm soát danh sách các port được coi là an toàn trong HTTP API.
	 *
	 * Cho phép thay đổi và cho phép các yêu cầu bên ngoài cho yêu cầu HTTP.
	 *
	 * @since 5.9.0
	 *
	 * @param int[]  $allowed_ports Mảng số nguyên cho các port hợp lệ.
	 * @param string $host          Tên host của URL được yêu cầu.
	 * @param string $url           URL được yêu cầu.
	 */
	$allowed_ports = apply_filters( 'http_allowed_safe_ports', array( 80, 443, 8080 ), $host, $url );
	if ( is_array( $allowed_ports ) && in_array( $port, $allowed_ports, true ) ) {
		return $url;
	}

	if ( $parsed_home && $same_host && isset( $parsed_home['port'] ) && $parsed_home['port'] === $port ) {
		return $url;
	}

	return false;
}

/**
 * Đánh dấu các host chuyển hướng được phép là an toàn cho yêu cầu HTTP.
 *
 * Gắn vào bộ lọc {@see 'http_request_host_is_external'}.
 *
 * @since 3.6.0
 *
 * @param bool   $is_external
 * @param string $host
 * @return bool
 */
function allowed_http_request_hosts( $is_external, $host ) {
	if ( ! $is_external && wp_validate_redirect( 'http://' . $host ) ) {
		$is_external = true;
	}
	return $is_external;
}

/**
 * Thêm bất kỳ tên miền nào trong cài đặt multisite vào danh sách
 * được phép cho các yêu cầu HTTP an toàn.
 *
 * Gắn vào bộ lọc {@see 'http_request_host_is_external'}.
 *
 * @since 3.6.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param bool   $is_external
 * @param string $host
 * @return bool
 */
function ms_allowed_http_request_hosts( $is_external, $host ) {
	global $wpdb;
	static $queried = array();
	if ( $is_external ) {
		return $is_external;
	}
	if ( get_network()->domain === $host ) {
		return true;
	}
	if ( isset( $queried[ $host ] ) ) {
		return $queried[ $host ];
	}
	$queried[ $host ] = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT domain FROM $wpdb->blogs WHERE domain = %s LIMIT 1", $host ) );
	return $queried[ $host ];
}

/**
 * Hàm bọc cho hàm parse_url() của PHP xử lý tính nhất quán trong các giá trị
 * trả về giữa các phiên bản PHP.
 *
 * Giữa các phiên bản PHP khác nhau, các URL không có scheme chứa ":" trong truy vấn
 * được xử lý không nhất quán. Hàm này khắc phục những khác biệt đó.
 *
 * @since 4.4.0
 * @since 4.7.0 Tham số `$component` được thêm để tương đương với `parse_url()` của PHP.
 *
 * @link https://www.php.net/manual/en/function.parse-url.php
 *
 * @param string $url       URL cần phân tích.
 * @param int    $component Thành phần cụ thể cần lấy. Sử dụng một trong các hằng số
 *                          được định nghĩa sẵn của PHP để chỉ định thành phần nào.
 *                          Mặc định -1 (= trả về tất cả các phần dưới dạng mảng).
 * @return mixed False khi phân tích thất bại; Mảng các thành phần URL khi thành công;
 *               Khi một thành phần cụ thể được yêu cầu: null nếu thành phần
 *               không tồn tại trong URL; chuỗi hoặc - trong trường hợp
 *               PHP_URL_PORT - số nguyên khi nó tồn tại. Xem giá trị trả về của parse_url().
 */
function wp_parse_url( $url, $component = -1 ) {
	$to_unset = array();
	$url      = (string) $url;

	if ( str_starts_with( $url, '//' ) ) {
		$to_unset[] = 'scheme';
		$url        = 'placeholder:' . $url;
	} elseif ( str_starts_with( $url, '/' ) ) {
		$to_unset[] = 'scheme';
		$to_unset[] = 'host';
		$url        = 'placeholder://placeholder' . $url;
	}

	$parts = parse_url( $url );

	if ( false === $parts ) {
		// Phân tích thất bại.
		return $parts;
	}

	// Xóa các giá trị placeholder.
	foreach ( $to_unset as $key ) {
		unset( $parts[ $key ] );
	}

	return _get_component_from_parsed_url_array( $parts, $component );
}

/**
 * Lấy một thành phần cụ thể từ mảng URL đã phân tích.
 *
 * @internal
 *
 * @since 4.7.0
 * @access private
 *
 * @link https://www.php.net/manual/en/function.parse-url.php
 *
 * @param array|false $url_parts URL đã phân tích. Có thể là false nếu URL phân tích thất bại.
 * @param int         $component Thành phần cụ thể cần lấy. Sử dụng một trong các hằng số
 *                               được định nghĩa sẵn của PHP để chỉ định thành phần nào.
 *                               Mặc định -1 (= trả về tất cả các phần dưới dạng mảng).
 * @return mixed False khi phân tích thất bại; Mảng các thành phần URL khi thành công;
 *               Khi một thành phần cụ thể được yêu cầu: null nếu thành phần
 *               không tồn tại trong URL; chuỗi hoặc - trong trường hợp
 *               PHP_URL_PORT - số nguyên khi nó tồn tại. Xem giá trị trả về của parse_url().
 */
function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
	if ( -1 === $component ) {
		return $url_parts;
	}

	$key = _wp_translate_php_url_constant_to_key( $component );
	if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
		return $url_parts[ $key ];
	} else {
		return null;
	}
}

/**
 * Chuyển đổi hằng số PHP_URL_* sang các khóa mảng có tên mà PHP sử dụng.
 *
 * @internal
 *
 * @since 4.7.0
 * @access private
 *
 * @link https://www.php.net/manual/en/url.constants.php
 *
 * @param int $constant Hằng số PHP_URL_*.
 * @return string|false Khóa có tên hoặc false.
 */
function _wp_translate_php_url_constant_to_key( $constant ) {
	$translation = array(
		PHP_URL_SCHEME   => 'scheme',
		PHP_URL_HOST     => 'host',
		PHP_URL_PORT     => 'port',
		PHP_URL_USER     => 'user',
		PHP_URL_PASS     => 'pass',
		PHP_URL_PATH     => 'path',
		PHP_URL_QUERY    => 'query',
		PHP_URL_FRAGMENT => 'fragment',
	);

	if ( isset( $translation[ $constant ] ) ) {
		return $translation[ $constant ];
	} else {
		return false;
	}
}
