<?php
/**
 * REST API: Lớp WP_REST_Request
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.4.0
 */

/**
 * Lớp cốt lõi dùng để triển khai đối tượng request REST.
 *
 * Chứa dữ liệu từ request, để truyền cho callback.
 *
 * Lưu ý: Lớp này triển khai ArrayAccess, và hoạt động như một mảng tham số khi
 * được sử dụng theo cách đó. Nó không sử dụng ArrayObject (vì không thể dựa vào SPL),
 * nên hãy lưu ý rằng nó có thể có hành vi khác mảng trong một số trường hợp.
 *
 * Lưu ý: Khi sử dụng các tính năng cung cấp bởi ArrayAccess, hãy lưu ý rằng WordPress cố ý
 * không phân biệt giữa các tham số cùng tên cho các phương thức request khác nhau.
 * Ví dụ, trong request có `GET id=1` và `POST id=2`, `$request['id']` sẽ bằng
 * 2 (`POST`) chứ không phải 1 (`GET`). Để chính xác hơn giữa các phương thức request, hãy dùng
 * WP_REST_Request::get_body_params(), WP_REST_Request::get_url_params(), v.v.
 *
 * @since 4.4.0
 *
 * @link https://www.php.net/manual/en/class.arrayaccess.php
 */
#[AllowDynamicProperties]
class WP_REST_Request implements ArrayAccess {

	/**
	 * Phương thức HTTP.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $method = '';

	/**
	 * Các tham số được truyền cho request.
	 *
	 * Thường được lấy từ các biến siêu toàn cục `$_GET`, `$_POST` và `$_FILES`
	 * khi được tạo từ phạm vi toàn cục.
	 *
	 * @since 4.4.0
	 * @var array Chứa các khóa GET, POST và FILES ánh xạ đến các mảng dữ liệu.
	 */
	protected $params;

	/**
	 * Các header HTTP cho request.
	 *
	 * @since 4.4.0
	 * @var array Bản đồ khóa đến giá trị. Khóa luôn là chữ thường, theo đặc tả HTTP.
	 */
	protected $headers = array();

	/**
	 * Dữ liệu body.
	 *
	 * @since 4.4.0
	 * @var string Dữ liệu nhị phân từ request.
	 */
	protected $body = null;

	/**
	 * Route đã khớp cho request.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $route;

	/**
	 * Các thuộc tính (tùy chọn) cho route đã khớp.
	 *
	 * Đây là mảng tùy chọn được sử dụng khi route được đăng ký, thường
	 * chứa callback cũng như các phương thức hợp lệ cho route.
	 *
	 * @since 4.4.0
	 * @var array Các thuộc tính cho request.
	 */
	protected $attributes = array();

	/**
	 * Dùng để xác định xem dữ liệu JSON đã được phân tích chưa.
	 *
	 * Cho phép phân tích JSON lười biếng khi có thể.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $parsed_json = false;

	/**
	 * Dùng để xác định xem dữ liệu body đã được phân tích chưa.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $parsed_body = false;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 4.4.0
	 *
	 * @param string $method     Tùy chọn. Phương thức request. Mặc định chuỗi rỗng.
	 * @param string $route      Tùy chọn. Route request. Mặc định chuỗi rỗng.
	 * @param array  $attributes Tùy chọn. Thuộc tính request. Mặc định mảng rỗng.
	 */
	public function __construct( $method = '', $route = '', $attributes = array() ) {
		$this->params = array(
			'URL'      => array(),
			'GET'      => array(),
			'POST'     => array(),
			'FILES'    => array(),

			// Xem parse_json_params.
			'JSON'     => null,

			'defaults' => array(),
		);

		$this->set_method( $method );
		$this->set_route( $route );
		$this->set_attributes( $attributes );
	}

	/**
	 * Lấy phương thức HTTP cho request.
	 *
	 * @since 4.4.0
	 *
	 * @return string Phương thức HTTP.
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Thiết lập phương thức HTTP cho request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $method Phương thức HTTP.
	 */
	public function set_method( $method ) {
		$this->method = strtoupper( $method );
	}

	/**
	 * Lấy tất cả header từ request.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ khóa đến giá trị. Khóa luôn là chữ thường, theo đặc tả HTTP.
	 */
	public function get_headers() {
		return $this->headers;
	}

	/**
	 * Xác định xem request có phải là phương thức đã cho không.
	 *
	 * @since 6.8.0
	 *
	 * @param string $method Phương thức HTTP.
	 * @return bool Request có phải là phương thức đã cho hay không.
	 */
	public function is_method( $method ) {
		return $this->get_method() === strtoupper( $method );
	}

	/**
	 * Chuẩn hóa tên header.
	 *
	 * Đảm bảo rằng tên header luôn được xử lý giống nhau bất kể
	 * nguồn gốc. Tên header luôn không phân biệt hoa thường.
	 *
	 * Lưu ý rằng chúng ta xử lý `-` (dấu gạch ngang) và `_` (dấu gạch dưới) như cùng
	 * một ký tự, theo quy tắc phân tích header trong cả Apache và nginx.
	 *
	 * @link https://stackoverflow.com/q/18185366
	 * @link https://www.nginx.com/resources/wiki/start/topics/tutorials/config_pitfalls/#missing-disappearing-http-headers
	 * @link https://nginx.org/en/docs/http/ngx_http_core_module.html#underscores_in_headers
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Tên header.
	 * @return string Tên đã chuẩn hóa.
	 */
	public static function canonicalize_header_name( $key ) {
		$key = strtolower( $key );
		$key = str_replace( '-', '_', $key );

		return $key;
	}

	/**
	 * Lấy header đã cho từ request.
	 *
	 * Nếu header có nhiều giá trị, chúng sẽ được nối bằng dấu phẩy
	 * theo đặc tả HTTP. Lưu ý rằng một số header không tuân thủ
	 * (đặc biệt header cookie) không thể nối theo cách này.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Tên header, sẽ được chuẩn hóa sang chữ thường.
	 * @return string|null Giá trị chuỗi nếu được thiết lập, null nếu không.
	 */
	public function get_header( $key ) {
		$key = $this->canonicalize_header_name( $key );

		if ( ! isset( $this->headers[ $key ] ) ) {
			return null;
		}

		return implode( ',', $this->headers[ $key ] );
	}

	/**
	 * Lấy các giá trị header từ request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Tên header, sẽ được chuẩn hóa sang chữ thường.
	 * @return array|null Danh sách giá trị chuỗi nếu được thiết lập, null nếu không.
	 */
	public function get_header_as_array( $key ) {
		$key = $this->canonicalize_header_name( $key );

		if ( ! isset( $this->headers[ $key ] ) ) {
			return null;
		}

		return $this->headers[ $key ];
	}

	/**
	 * Thiết lập header trên request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key   Tên header.
	 * @param string $value Giá trị header, hoặc danh sách giá trị.
	 */
	public function set_header( $key, $value ) {
		$key   = $this->canonicalize_header_name( $key );
		$value = (array) $value;

		$this->headers[ $key ] = $value;
	}

	/**
	 * Thêm giá trị header cho header đã cho.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key   Tên header.
	 * @param string $value Giá trị header, hoặc danh sách giá trị.
	 */
	public function add_header( $key, $value ) {
		$key   = $this->canonicalize_header_name( $key );
		$value = (array) $value;

		if ( ! isset( $this->headers[ $key ] ) ) {
			$this->headers[ $key ] = array();
		}

		$this->headers[ $key ] = array_merge( $this->headers[ $key ], $value );
	}

	/**
	 * Xóa tất cả giá trị cho một header.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Tên header.
	 */
	public function remove_header( $key ) {
		$key = $this->canonicalize_header_name( $key );
		unset( $this->headers[ $key ] );
	}

	/**
	 * Thiết lập các header trên request.
	 *
	 * @since 4.4.0
	 *
	 * @param array $headers  Bản đồ tên header đến giá trị.
	 * @param bool  $override Nếu true, thay thế các header của request. Ngược lại, gộp với các header hiện có.
	 */
	public function set_headers( $headers, $override = true ) {
		if ( true === $override ) {
			$this->headers = array();
		}

		foreach ( $headers as $key => $value ) {
			$this->set_header( $key, $value );
		}
	}

	/**
	 * Lấy Content-Type của request.
	 *
	 * @since 4.4.0
	 *
	 * @return array|null Bản đồ chứa các khóa 'value' và 'parameters'
	 *                    hoặc null khi không có header Content-Type hợp lệ.
	 */
	public function get_content_type() {
		$value = $this->get_header( 'Content-Type' );
		if ( empty( $value ) ) {
			return null;
		}

		$parameters = '';
		if ( strpos( $value, ';' ) ) {
			list( $value, $parameters ) = explode( ';', $value, 2 );
		}

		$value = strtolower( $value );
		if ( ! str_contains( $value, '/' ) ) {
			return null;
		}

		// Phân tích type và subtype.
		list( $type, $subtype ) = explode( '/', $value, 2 );

		$data = compact( 'value', 'type', 'subtype', 'parameters' );
		$data = array_map( 'trim', $data );

		return $data;
	}

	/**
	 * Kiểm tra xem request có chỉ định Content-Type là JSON không.
	 *
	 * @since 5.6.0
	 *
	 * @return bool True nếu header Content-Type là JSON.
	 */
	public function is_json_content_type() {
		$content_type = $this->get_content_type();

		return isset( $content_type['value'] ) && wp_is_json_media_type( $content_type['value'] );
	}

	/**
	 * Lấy thứ tự ưu tiên tham số.
	 *
	 * Được sử dụng khi kiểm tra tham số trong WP_REST_Request::get_param().
	 *
	 * @since 4.4.0
	 *
	 * @return string[] Mảng các loại cần kiểm tra, theo thứ tự ưu tiên.
	 */
	protected function get_parameter_order() {
		$order = array();

		if ( $this->is_json_content_type() ) {
			$order[] = 'JSON';
		}

		$this->parse_json_params();

		// Đảm bảo chúng ta phân tích dữ liệu body.
		$body = $this->get_body();

		if ( 'POST' !== $this->method && ! empty( $body ) ) {
			$this->parse_body_params();
		}

		$accepts_body_data = array( 'POST', 'PUT', 'PATCH', 'DELETE' );
		if ( in_array( $this->method, $accepts_body_data, true ) ) {
			$order[] = 'POST';
		}

		$order[] = 'GET';
		$order[] = 'URL';
		$order[] = 'defaults';

		/**
		 * Lọc thứ tự ưu tiên tham số cho request REST API.
		 *
		 * Thứ tự ảnh hưởng đến tham số nào được kiểm tra khi sử dụng WP_REST_Request::get_param()
		 * và các hàm liên quan. Điều này hoạt động tương tự như cài đặt `request_order` của PHP.
		 *
		 * @since 4.4.0
		 *
		 * @param string[]        $order   Mảng các loại cần kiểm tra, theo thứ tự ưu tiên.
		 * @param WP_REST_Request $request Đối tượng request.
		 */
		return apply_filters( 'rest_request_parameter_order', $order, $this );
	}

	/**
	 * Lấy một tham số từ request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Tên tham số.
	 * @return mixed|null Giá trị nếu được thiết lập, null nếu không.
	 */
	public function get_param( $key ) {
		$order = $this->get_parameter_order();

		foreach ( $order as $type ) {
			// Xác định xem chúng ta có tham số cho loại này không.
			if ( isset( $this->params[ $type ][ $key ] ) ) {
				return $this->params[ $type ][ $key ];
			}
		}

		return null;
	}

	/**
	 * Kiểm tra xem tham số có tồn tại trong request không.
	 *
	 * Điều này cho phép phân biệt giữa tham số bị bỏ qua
	 * và tham số được thiết lập cụ thể thành null.
	 *
	 * @since 5.3.0
	 *
	 * @param string $key Tên tham số.
	 * @return bool True nếu tham số tồn tại cho khóa đã cho.
	 */
	public function has_param( $key ) {
		$order = $this->get_parameter_order();

		foreach ( $order as $type ) {
			if ( is_array( $this->params[ $type ] ) && array_key_exists( $key, $this->params[ $type ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Thiết lập tham số trên request.
	 *
	 * Nếu khóa tham số đã cho tồn tại trong bất kỳ loại tham số nào, sẽ thực hiện cập nhật,
	 * ngược lại tham số mới sẽ được tạo trong loại tham số đầu tiên (tôn trọng
	 * get_parameter_order()).
	 *
	 * @since 4.4.0
	 *
	 * @param string $key   Tên tham số.
	 * @param mixed  $value Giá trị tham số.
	 */
	public function set_param( $key, $value ) {
		$order     = $this->get_parameter_order();
		$found_key = false;

		foreach ( $order as $type ) {
			if ( 'defaults' !== $type && is_array( $this->params[ $type ] ) && array_key_exists( $key, $this->params[ $type ] ) ) {
				$this->params[ $type ][ $key ] = $value;
				$found_key                     = true;
			}
		}

		if ( ! $found_key ) {
			$this->params[ $order[0] ][ $key ] = $value;
		}
	}

	/**
	 * Lấy các tham số đã gộp từ request.
	 *
	 * Tương đương với get_param(), nhưng trả về tất cả tham số cho request.
	 * Xử lý việc gộp tất cả các giá trị có sẵn vào một mảng duy nhất.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ khóa đến giá trị.
	 */
	public function get_params() {
		$order = $this->get_parameter_order();
		$order = array_reverse( $order, true );

		$params = array();
		foreach ( $order as $type ) {
			/*
			 * array_merge() / toán tử "+" sẽ làm lộn xộn
			 * khóa số, nên thay vào đó dùng foreach thủ công.
			 */
			foreach ( (array) $this->params[ $type ] as $key => $value ) {
				$params[ $key ] = $value;
			}
		}

		// Loại trừ rest_route nếu permalink đẹp không được bật.
		if ( ! get_option( 'permalink_structure' ) ) {
			unset( $params['rest_route'] );
		}

		return $params;
	}

	/**
	 * Lấy tham số từ chính route.
	 *
	 * Các tham số này được phân tích từ URL bằng regex.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ tham số khóa đến giá trị.
	 */
	public function get_url_params() {
		return $this->params['URL'];
	}

	/**
	 * Thiết lập tham số từ route.
	 *
	 * Thường được thiết lập sau khi phân tích URL.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Bản đồ tham số khóa đến giá trị.
	 */
	public function set_url_params( $params ) {
		$this->params['URL'] = $params;
	}

	/**
	 * Lấy tham số từ chuỗi truy vấn.
	 *
	 * Đây là các tham số bạn thường tìm thấy trong `$_GET`.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ tham số khóa đến giá trị
	 */
	public function get_query_params() {
		return $this->params['GET'];
	}

	/**
	 * Thiết lập tham số từ chuỗi truy vấn.
	 *
	 * Thường được thiết lập từ `$_GET`.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Bản đồ tham số khóa đến giá trị.
	 */
	public function set_query_params( $params ) {
		$this->params['GET'] = $params;
	}

	/**
	 * Lấy tham số từ body.
	 *
	 * Đây là các tham số bạn thường tìm thấy trong `$_POST`.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ tham số khóa đến giá trị.
	 */
	public function get_body_params() {
		return $this->params['POST'];
	}

	/**
	 * Thiết lập tham số từ body.
	 *
	 * Thường được thiết lập từ `$_POST`.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Bản đồ tham số khóa đến giá trị.
	 */
	public function set_body_params( $params ) {
		$this->params['POST'] = $params;
	}

	/**
	 * Lấy tham số file multipart từ body.
	 *
	 * Đây là các tham số bạn thường tìm thấy trong `$_FILES`.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ tham số khóa đến giá trị
	 */
	public function get_file_params() {
		return $this->params['FILES'];
	}

	/**
	 * Thiết lập tham số file multipart từ body.
	 *
	 * Thường được thiết lập từ `$_FILES`.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Bản đồ tham số khóa đến giá trị.
	 */
	public function set_file_params( $params ) {
		$this->params['FILES'] = $params;
	}

	/**
	 * Lấy các tham số mặc định.
	 *
	 * Đây là các tham số được thiết lập khi đăng ký route.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ tham số khóa đến giá trị
	 */
	public function get_default_params() {
		return $this->params['defaults'];
	}

	/**
	 * Thiết lập tham số mặc định.
	 *
	 * Đây là các tham số được thiết lập khi đăng ký route.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Bản đồ tham số khóa đến giá trị.
	 */
	public function set_default_params( $params ) {
		$this->params['defaults'] = $params;
	}

	/**
	 * Lấy nội dung body của request.
	 *
	 * @since 4.4.0
	 *
	 * @return string Dữ liệu nhị phân từ body request.
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Thiết lập nội dung body.
	 *
	 * @since 4.4.0
	 *
	 * @param string $data Dữ liệu nhị phân từ body request.
	 */
	public function set_body( $data ) {
		$this->body = $data;

		// Cho phép phân tích lười biếng.
		$this->parsed_json    = false;
		$this->parsed_body    = false;
		$this->params['JSON'] = null;
	}

	/**
	 * Lấy các tham số từ body định dạng JSON.
	 *
	 * @since 4.4.0
	 *
	 * @return array Bản đồ tham số khóa đến giá trị.
	 */
	public function get_json_params() {
		// Đảm bảo các tham số đã được phân tích.
		$this->parse_json_params();

		return $this->params['JSON'];
	}

	/**
	 * Phân tích các tham số JSON.
	 *
	 * Tránh phân tích dữ liệu JSON cho đến khi chúng ta cần truy cập.
	 *
	 * @since 4.4.0
	 * @since 4.7.0 Trả về instance lỗi nếu giá trị không thể giải mã.
	 * @return true|WP_Error True nếu dữ liệu JSON đã được truyền hoặc không có dữ liệu JSON, WP_Error nếu JSON không hợp lệ.
	 */
	protected function parse_json_params() {
		if ( $this->parsed_json ) {
			return true;
		}

		$this->parsed_json = true;

		// Kiểm tra xem chúng ta thực sự nhận được JSON không.
		if ( ! $this->is_json_content_type() ) {
			return true;
		}

		$body = $this->get_body();
		if ( empty( $body ) ) {
			return true;
		}

		$params = json_decode( $body, true );

		/*
		 * Kiểm tra lỗi phân tích.
		 */
		if ( null === $params && JSON_ERROR_NONE !== json_last_error() ) {
			// Đảm bảo các lần gọi tiếp theo nhận được instance lỗi.
			$this->parsed_json = false;

			$error_data = array(
				'status'             => WP_Http::BAD_REQUEST,
				'json_error_code'    => json_last_error(),
				'json_error_message' => json_last_error_msg(),
			);

			return new WP_Error( 'rest_invalid_json', __( 'Invalid JSON body passed.' ), $error_data );
		}

		$this->params['JSON'] = $params;

		return true;
	}

	/**
	 * Phân tích các tham số body của request.
	 *
	 * Phân tích body mã hóa URL cho các phương thức request không được
	 * PHP hỗ trợ tự nhiên.
	 *
	 * @since 4.4.0
	 */
	protected function parse_body_params() {
		if ( $this->parsed_body ) {
			return;
		}

		$this->parsed_body = true;

		/*
		 * Kiểm tra xem chúng ta nhận được URL-encoded không. Xử lý Content-Type bị thiếu như
		 * URL-encoded để tương thích tối đa.
		 */
		$content_type = $this->get_content_type();

		if ( ! empty( $content_type ) && 'application/x-www-form-urlencoded' !== $content_type['value'] ) {
			return;
		}

		parse_str( $this->get_body(), $params );

		/*
		 * Thêm vào tham số POST được lưu trữ nội bộ. Nếu người dùng đã
		 * thiết lập thủ công (qua `set_body_params`), không ghi đè.
		 */
		$this->params['POST'] = array_merge( $params, $this->params['POST'] );
	}

	/**
	 * Lấy route đã khớp với request.
	 *
	 * @since 4.4.0
	 *
	 * @return string Regex khớp route.
	 */
	public function get_route() {
		return $this->route;
	}

	/**
	 * Thiết lập route đã khớp với request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $route Regex khớp route.
	 */
	public function set_route( $route ) {
		$this->route = $route;
	}

	/**
	 * Lấy các thuộc tính cho request.
	 *
	 * Đây là các tùy chọn cho route đã khớp.
	 *
	 * @since 4.4.0
	 *
	 * @return array Các thuộc tính cho request.
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Thiết lập các thuộc tính cho request.
	 *
	 * @since 4.4.0
	 *
	 * @param array $attributes Các thuộc tính cho request.
	 */
	public function set_attributes( $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Làm sạch (khi có thể) các tham số trên request.
	 *
	 * Chủ yếu dựa trên sanitize_callback của mỗi tham số
	 * đã đăng ký.
	 *
	 * @since 4.4.0
	 *
	 * @return true|WP_Error True nếu tham số đã được làm sạch, WP_Error nếu có lỗi xảy ra trong quá trình làm sạch.
	 */
	public function sanitize_params() {
		$attributes = $this->get_attributes();

		// Không có tham số nào được thiết lập, bỏ qua làm sạch.
		if ( empty( $attributes['args'] ) ) {
			return true;
		}

		$order = $this->get_parameter_order();

		$invalid_params  = array();
		$invalid_details = array();

		foreach ( $order as $type ) {
			if ( empty( $this->params[ $type ] ) ) {
				continue;
			}

			foreach ( $this->params[ $type ] as $key => $value ) {
				if ( ! isset( $attributes['args'][ $key ] ) ) {
					continue;
				}

				$param_args = $attributes['args'][ $key ];

				// Nếu tham số có type nhưng không có thuộc tính sanitize_callback, mặc định dùng rest_parse_request_arg.
				if ( ! array_key_exists( 'sanitize_callback', $param_args ) && ! empty( $param_args['type'] ) ) {
					$param_args['sanitize_callback'] = 'rest_parse_request_arg';
				}
				// Nếu vẫn không có sanitize_callback, không có gì để làm ở đây.
				if ( empty( $param_args['sanitize_callback'] ) ) {
					continue;
				}

				/** @var mixed|WP_Error $sanitized_value */
				$sanitized_value = call_user_func( $param_args['sanitize_callback'], $value, $this, $key );

				if ( is_wp_error( $sanitized_value ) ) {
					$invalid_params[ $key ]  = implode( ' ', $sanitized_value->get_error_messages() );
					$invalid_details[ $key ] = rest_convert_error_to_response( $sanitized_value )->get_data();
				} else {
					$this->params[ $type ][ $key ] = $sanitized_value;
				}
			}
		}

		if ( $invalid_params ) {
			return new WP_Error(
				'rest_invalid_param',
				/* translators: %s: List of invalid parameters. */
				sprintf( __( 'Invalid parameter(s): %s' ), implode( ', ', array_keys( $invalid_params ) ) ),
				array(
					'status'  => 400,
					'params'  => $invalid_params,
					'details' => $invalid_details,
				)
			);
		}

		return true;
	}

	/**
	 * Kiểm tra xem request này có hợp lệ theo các thuộc tính của nó không.
	 *
	 * @since 4.4.0
	 *
	 * @return true|WP_Error True nếu không có tham số cần xác thực hoặc tất cả đều vượt qua xác thực,
	 *                       WP_Error nếu thiếu tham số bắt buộc.
	 */
	public function has_valid_params() {
		// Nếu dữ liệu JSON được truyền, kiểm tra lỗi.
		$json_error = $this->parse_json_params();
		if ( is_wp_error( $json_error ) ) {
			return $json_error;
		}

		$attributes = $this->get_attributes();
		$required   = array();

		$args = empty( $attributes['args'] ) ? array() : $attributes['args'];

		foreach ( $args as $key => $arg ) {
			$param = $this->get_param( $key );
			if ( isset( $arg['required'] ) && true === $arg['required'] && null === $param ) {
				$required[] = $key;
			}
		}

		if ( ! empty( $required ) ) {
			return new WP_Error(
				'rest_missing_callback_param',
				/* translators: %s: List of required parameters. */
				sprintf( __( 'Missing parameter(s): %s' ), implode( ', ', $required ) ),
				array(
					'status' => 400,
					'params' => $required,
				)
			);
		}

		/*
		 * Kiểm tra các callback xác thực cho mỗi tham số đã đăng ký.
		 *
		 * Điều này được thực hiện sau kiểm tra bắt buộc vì kiểm tra bắt buộc ít tốn kém hơn.
		 */
		$invalid_params  = array();
		$invalid_details = array();

		foreach ( $args as $key => $arg ) {

			$param = $this->get_param( $key );

			if ( null !== $param && ! empty( $arg['validate_callback'] ) ) {
				/** @var bool|\WP_Error $valid_check */
				$valid_check = call_user_func( $arg['validate_callback'], $param, $this, $key );

				if ( false === $valid_check ) {
					$invalid_params[ $key ] = __( 'Invalid parameter.' );
				}

				if ( is_wp_error( $valid_check ) ) {
					$invalid_params[ $key ]  = implode( ' ', $valid_check->get_error_messages() );
					$invalid_details[ $key ] = rest_convert_error_to_response( $valid_check )->get_data();
				}
			}
		}

		if ( $invalid_params ) {
			return new WP_Error(
				'rest_invalid_param',
				/* translators: %s: List of invalid parameters. */
				sprintf( __( 'Invalid parameter(s): %s' ), implode( ', ', array_keys( $invalid_params ) ) ),
				array(
					'status'  => 400,
					'params'  => $invalid_params,
					'details' => $invalid_details,
				)
			);
		}

		if ( isset( $attributes['validate_callback'] ) ) {
			$valid_check = call_user_func( $attributes['validate_callback'], $this );

			if ( is_wp_error( $valid_check ) ) {
				return $valid_check;
			}

			if ( false === $valid_check ) {
				// Ưu tiên instance WP_Error, nhưng false được hỗ trợ để tương đồng với validate_callback của từng tham số.
				return new WP_Error( 'rest_invalid_params', __( 'Invalid parameters.' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * Kiểm tra xem tham số có được thiết lập không.
	 *
	 * @since 4.4.0
	 *
	 * @param string $offset Tên tham số.
	 * @return bool Tham số có được thiết lập hay không.
	 */
	#[ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$order = $this->get_parameter_order();

		foreach ( $order as $type ) {
			if ( isset( $this->params[ $type ][ $offset ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Lấy một tham số từ request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $offset Tên tham số.
	 * @return mixed|null Giá trị nếu được thiết lập, null nếu không.
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->get_param( $offset );
	}

	/**
	 * Thiết lập tham số trên request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $offset Tên tham số.
	 * @param mixed  $value  Giá trị tham số.
	 */
	#[ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->set_param( $offset, $value );
	}

	/**
	 * Xóa một tham số khỏi request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $offset Tên tham số.
	 */
	#[ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$order = $this->get_parameter_order();

		// Xóa offset khỏi mọi nhóm.
		foreach ( $order as $type ) {
			unset( $this->params[ $type ][ $offset ] );
		}
	}

	/**
	 * Lấy đối tượng WP_REST_Request từ URL đầy đủ.
	 *
	 * @since 4.5.0
	 *
	 * @param string $url URL với giao thức, tên miền, đường dẫn và tham số truy vấn.
	 * @return WP_REST_Request|false Đối tượng WP_REST_Request nếu thành công, false nếu thất bại.
	 */
	public static function from_url( $url ) {
		$bits         = parse_url( $url );
		$query_params = array();

		if ( ! empty( $bits['query'] ) ) {
			wp_parse_str( $bits['query'], $query_params );
		}

		$api_root = rest_url();
		if ( get_option( 'permalink_structure' ) && str_starts_with( $url, $api_root ) ) {
			// Permalink đẹp được bật, và URL nằm dưới gốc API.
			$api_url_part = substr( $url, strlen( untrailingslashit( $api_root ) ) );
			$route        = parse_url( $api_url_part, PHP_URL_PATH );
		} elseif ( ! empty( $query_params['rest_route'] ) ) {
			// ?rest_route=... được thiết lập trực tiếp.
			$route = $query_params['rest_route'];
			unset( $query_params['rest_route'] );
		}

		$request = false;
		if ( ! empty( $route ) ) {
			$request = new WP_REST_Request( 'GET', $route );
			$request->set_query_params( $query_params );
		}

		/**
		 * Lọc request REST API được tạo từ URL.
		 *
		 * @since 4.5.0
		 *
		 * @param WP_REST_Request|false $request Đối tượng request đã tạo, hoặc false nếu
		 *                                       URL không thể phân tích.
		 * @param string                $url     URL mà request được tạo từ đó.
		 */
		return apply_filters( 'rest_request_from_url', $request, $url );
	}
}
