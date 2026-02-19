<?php
/**
 * REST API: Lớp WP_REST_Response
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.4.0
 */

/**
 * Lớp cốt lõi dùng để triển khai đối tượng phản hồi REST.
 *
 * @since 4.4.0
 *
 * @see WP_HTTP_Response
 */
class WP_REST_Response extends WP_HTTP_Response {

	/**
	 * Các liên kết liên quan đến phản hồi.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected $links = array();

	/**
	 * Route đã tạo ra phản hồi.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $matched_route = '';

	/**
	 * Handler đã được sử dụng để tạo phản hồi.
	 *
	 * @since 4.4.0
	 * @var null|array
	 */
	protected $matched_handler = null;

	/**
	 * Thêm một liên kết vào phản hồi.
	 *
	 * @internal Tham số $rel được đặt trước vì trông đẹp hơn khi gửi nhiều liên kết.
	 *
	 * @since 4.4.0
	 *
	 * @link https://tools.ietf.org/html/rfc5988
	 * @link https://www.iana.org/assignments/link-relations/link-relations.xml
	 *
	 * @param string $rel        Quan hệ liên kết. Có thể là loại đã đăng ký IANA,
	 *                           hoặc một URL tuyệt đối.
	 * @param string $href       URI đích cho liên kết.
	 * @param array  $attributes Tùy chọn. Các tham số liên kết gửi kèm URL. Mặc định mảng rỗng.
	 */
	public function add_link( $rel, $href, $attributes = array() ) {
		if ( empty( $this->links[ $rel ] ) ) {
			$this->links[ $rel ] = array();
		}

		if ( isset( $attributes['href'] ) ) {
			// Xóa thuộc tính href vì nó được dùng cho URL chính.
			unset( $attributes['href'] );
		}

		$this->links[ $rel ][] = array(
			'href'       => $href,
			'attributes' => $attributes,
		);
	}

	/**
	 * Xóa một liên kết khỏi phản hồi.
	 *
	 * @since 4.4.0
	 *
	 * @param string $rel  Quan hệ liên kết. Có thể là loại đã đăng ký IANA, hoặc một URL tuyệt đối.
	 * @param string $href Tùy chọn. Chỉ xóa các liên kết có quan hệ khớp với href đã cho.
	 *                     Mặc định null.
	 */
	public function remove_link( $rel, $href = null ) {
		if ( ! isset( $this->links[ $rel ] ) ) {
			return;
		}

		if ( $href ) {
			$this->links[ $rel ] = wp_list_filter( $this->links[ $rel ], array( 'href' => $href ), 'NOT' );
		} else {
			$this->links[ $rel ] = array();
		}

		if ( ! $this->links[ $rel ] ) {
			unset( $this->links[ $rel ] );
		}
	}

	/**
	 * Thêm nhiều liên kết vào phản hồi.
	 *
	 * Dữ liệu liên kết phải là mảng kết hợp với quan hệ liên kết làm khóa.
	 * Giá trị có thể là mảng kết hợp chứa thuộc tính liên kết
	 * (bao gồm `href` với URL cho phản hồi), hoặc danh sách các mảng
	 * kết hợp này.
	 *
	 * @since 4.4.0
	 *
	 * @param array $links Bản đồ quan hệ liên kết đến danh sách liên kết.
	 */
	public function add_links( $links ) {
		foreach ( $links as $rel => $set ) {
			// Nếu là liên kết đơn, bọc trong mảng để xử lý nhất quán.
			if ( isset( $set['href'] ) ) {
				$set = array( $set );
			}

			foreach ( $set as $attributes ) {
				$this->add_link( $rel, $attributes['href'], $attributes );
			}
		}
	}

	/**
	 * Lấy các liên kết của phản hồi.
	 *
	 * @since 4.4.0
	 *
	 * @return array Danh sách các liên kết.
	 */
	public function get_links() {
		return $this->links;
	}

	/**
	 * Thiết lập một header liên kết đơn.
	 *
	 * @internal Tham số $rel được đặt trước vì trông đẹp hơn khi gửi nhiều liên kết.
	 *
	 * @since 4.4.0
	 *
	 * @link https://tools.ietf.org/html/rfc5988
	 * @link https://www.iana.org/assignments/link-relations/link-relations.xml
	 *
	 * @param string $rel   Quan hệ liên kết. Có thể là loại đã đăng ký IANA, hoặc một URL tuyệt đối.
	 * @param string $link  IRI đích cho liên kết.
	 * @param array  $other Tùy chọn. Các tham số khác để gửi, dạng mảng kết hợp.
	 *                      Mặc định mảng rỗng.
	 */
	public function link_header( $rel, $link, $other = array() ) {
		$header = '<' . $link . '>; rel="' . $rel . '"';

		foreach ( $other as $key => $value ) {
			if ( 'title' === $key ) {
				$value = '"' . $value . '"';
			}

			$header .= '; ' . $key . '=' . $value;
		}
		$this->header( 'Link', $header, false );
	}

	/**
	 * Lấy route đã được sử dụng.
	 *
	 * @since 4.4.0
	 *
	 * @return string Route đã khớp.
	 */
	public function get_matched_route() {
		return $this->matched_route;
	}

	/**
	 * Thiết lập route (regex cho đường dẫn) đã tạo ra phản hồi.
	 *
	 * @since 4.4.0
	 *
	 * @param string $route Tên route.
	 */
	public function set_matched_route( $route ) {
		$this->matched_route = $route;
	}

	/**
	 * Lấy handler đã được sử dụng để tạo phản hồi.
	 *
	 * @since 4.4.0
	 *
	 * @return null|array Handler đã được sử dụng để tạo phản hồi.
	 */
	public function get_matched_handler() {
		return $this->matched_handler;
	}

	/**
	 * Thiết lập handler chịu trách nhiệm tạo phản hồi.
	 *
	 * @since 4.4.0
	 *
	 * @param array $handler Handler đã khớp.
	 */
	public function set_matched_handler( $handler ) {
		$this->matched_handler = $handler;
	}

	/**
	 * Kiểm tra xem phản hồi có phải lỗi không, tức là mã phản hồi >= 400.
	 *
	 * @since 4.4.0
	 *
	 * @return bool Phản hồi có phải lỗi hay không.
	 */
	public function is_error() {
		return $this->get_status() >= 400;
	}

	/**
	 * Lấy đối tượng WP_Error từ phản hồi.
	 *
	 * @since 4.4.0
	 *
	 * @return WP_Error|null WP_Error hoặc null nếu phản hồi không phải lỗi.
	 */
	public function as_error() {
		if ( ! $this->is_error() ) {
			return null;
		}

		$error = new WP_Error();

		if ( is_array( $this->get_data() ) ) {
			$data = $this->get_data();
			$error->add( $data['code'], $data['message'], $data['data'] );

			if ( ! empty( $data['additional_errors'] ) ) {
				foreach ( $data['additional_errors'] as $err ) {
					$error->add( $err['code'], $err['message'], $err['data'] );
				}
			}
		} else {
			$error->add( $this->get_status(), '', array( 'status' => $this->get_status() ) );
		}

		return $error;
	}

	/**
	 * Lấy các CURIE (URI rút gọn) dùng cho quan hệ.
	 *
	 * @since 4.5.0
	 *
	 * @return array Các URI rút gọn.
	 */
	public function get_curies() {
		$curies = array(
			array(
				'name'      => 'wp',
				'href'      => 'https://api.w.org/{rel}',
				'templated' => true,
			),
		);

		/**
		 * Lọc các CURIE bổ sung có sẵn trên phản hồi REST API.
		 *
		 * CURIE cho phép phiên bản rút gọn của quan hệ URI. Điều này cho phép
		 * dạng dễ sử dụng hơn cho quan hệ tùy chỉnh so với việc dùng URI đầy đủ.
		 * Chúng hoạt động tương tự như cách namespace XML hoạt động.
		 *
		 * Các CURIE đã đăng ký cần chỉ định tên và mẫu URI. Điều này sẽ
		 * tự động chuyển đổi quan hệ URI thành phiên bản rút gọn.
		 * Quan hệ rút gọn theo định dạng `{name}:{rel}`. `{rel}` trong
		 * mẫu URI sẽ được thay thế bằng phần `{rel}` của
		 * quan hệ rút gọn.
		 *
		 * Ví dụ, một CURIE có tên `example` và mẫu URI
		 * `http://w.org/{rel}` sẽ chuyển đổi quan hệ `http://w.org/term`
		 * thành `example:term`.
		 *
		 * Các client hoạt động đúng cách nên mở rộng và chuẩn hóa lại
		 * thành quan hệ URI đầy đủ, tuy nhiên một số client đơn giản có thể không
		 * xử lý đúng, nên việc thêm CURIE mới có thể phá vỡ tính tương thích ngược.
		 *
		 * @since 4.5.0
		 *
		 * @param array $additional Các CURIE bổ sung để đăng ký với REST API.
		 */
		$additional = apply_filters( 'rest_response_link_curies', array() );

		return array_merge( $curies, $additional );
	}
}
