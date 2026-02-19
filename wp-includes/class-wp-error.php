<?php
/**
 * API Lỗi WordPress.
 *
 * @package WordPress
 */

/**
 * Lớp Lỗi WordPress.
 *
 * Vùng chứa để kiểm tra lỗi và thông báo lỗi WordPress. Trả về
 * WP_Error và sử dụng is_wp_error() để kiểm tra xem lớp này có được trả về không. Nhiều
 * hàm lõi WordPress truyền lớp này khi có lỗi và
 * nếu không xử lý đúng cách sẽ dẫn đến lỗi code.
 *
 * @since 2.1.0
 */
#[AllowDynamicProperties]
class WP_Error {
	/**
	 * Lưu trữ danh sách các lỗi.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public $errors = array();

	/**
	 * Lưu trữ dữ liệu được thêm gần nhất cho mỗi mã lỗi.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public $error_data = array();

	/**
	 * Lưu trữ dữ liệu đã thêm trước đó cho các mã lỗi, từ cũ nhất đến mới nhất theo mã.
	 *
	 * @since 5.6.0
	 * @var array[]
	 */
	protected $additional_data = array();

	/**
	 * Khởi tạo lỗi.
	 *
	 * Nếu `$code` rỗng, các tham số khác sẽ bị bỏ qua.
	 * Khi `$code` không rỗng, `$message` sẽ được sử dụng ngay cả khi
	 * nó rỗng. Tham số `$data` chỉ được sử dụng khi nó
	 * không rỗng.
	 *
	 * Mặc dù lớp được khởi tạo với một mã lỗi và
	 * thông báo đơn lẻ, nhiều mã có thể được thêm bằng phương thức `add()`.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code    Mã lỗi.
	 * @param string     $message Thông báo lỗi.
	 * @param mixed      $data    Tùy chọn. Dữ liệu lỗi. Mặc định chuỗi rỗng.
	 */
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( empty( $code ) ) {
			return;
		}

		$this->add( $code, $message, $data );
	}

	/**
	 * Lấy tất cả các mã lỗi.
	 *
	 * @since 2.1.0
	 *
	 * @return array Danh sách mã lỗi, nếu có.
	 */
	public function get_error_codes() {
		if ( ! $this->has_errors() ) {
			return array();
		}

		return array_keys( $this->errors );
	}

	/**
	 * Lấy mã lỗi đầu tiên có sẵn.
	 *
	 * @since 2.1.0
	 *
	 * @return string|int Chuỗi rỗng, nếu không có mã lỗi.
	 */
	public function get_error_code() {
		$codes = $this->get_error_codes();

		if ( empty( $codes ) ) {
			return '';
		}

		return $codes[0];
	}

	/**
	 * Lấy tất cả thông báo lỗi, hoặc thông báo lỗi cho mã lỗi đã cho.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Tùy chọn. Mã lỗi để lấy thông báo.
	 *                         Mặc định chuỗi rỗng.
	 * @return string[] Chuỗi lỗi nếu thành công, hoặc mảng rỗng nếu không có.
	 */
	public function get_error_messages( $code = '' ) {
		// Trả về tất cả thông báo nếu không chỉ định mã.
		if ( empty( $code ) ) {
			$all_messages = array();
			foreach ( (array) $this->errors as $code => $messages ) {
				$all_messages = array_merge( $all_messages, $messages );
			}

			return $all_messages;
		}

		if ( isset( $this->errors[ $code ] ) ) {
			return $this->errors[ $code ];
		} else {
			return array();
		}
	}

	/**
	 * Lấy một thông báo lỗi đơn lẻ.
	 *
	 * Lấy thông báo đầu tiên có sẵn cho mã. Nếu không có mã
	 * được cung cấp thì mã đầu tiên có sẵn sẽ được sử dụng.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Tùy chọn. Mã lỗi để lấy thông báo.
	 *                         Mặc định chuỗi rỗng.
	 * @return string Thông báo lỗi.
	 */
	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}
		$messages = $this->get_error_messages( $code );
		if ( empty( $messages ) ) {
			return '';
		}
		return $messages[0];
	}

	/**
	 * Lấy dữ liệu lỗi được thêm gần nhất cho một mã lỗi.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Tùy chọn. Mã lỗi. Mặc định chuỗi rỗng.
	 * @return mixed Dữ liệu lỗi, nếu tồn tại.
	 */
	public function get_error_data( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		if ( isset( $this->error_data[ $code ] ) ) {
			return $this->error_data[ $code ];
		}
	}

	/**
	 * Kiểm tra xem instance có chứa lỗi không.
	 *
	 * @since 5.1.0
	 *
	 * @return bool Nếu instance có chứa lỗi.
	 */
	public function has_errors() {
		if ( ! empty( $this->errors ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Thêm lỗi hoặc bổ sung thêm thông báo vào lỗi đã có.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code    Mã lỗi.
	 * @param string     $message Thông báo lỗi.
	 * @param mixed      $data    Tùy chọn. Dữ liệu lỗi. Mặc định chuỗi rỗng.
	 */
	public function add( $code, $message, $data = '' ) {
		$this->errors[ $code ][] = $message;

		if ( ! empty( $data ) ) {
			$this->add_data( $data, $code );
		}

		/**
		 * Kích hoạt khi một lỗi được thêm vào đối tượng WP_Error.
		 *
		 * @since 5.6.0
		 *
		 * @param string|int $code     Mã lỗi.
		 * @param string     $message  Thông báo lỗi.
		 * @param mixed      $data     Dữ liệu lỗi. Có thể rỗng.
		 * @param WP_Error   $wp_error Đối tượng WP_Error.
		 */
		do_action( 'wp_error_added', $code, $message, $data, $this );
	}

	/**
	 * Thêm dữ liệu vào lỗi với mã đã cho.
	 *
	 * @since 2.1.0
	 * @since 5.6.0 Lỗi giờ có thể chứa nhiều hơn một mục dữ liệu lỗi. {@see WP_Error::$additional_data}.
	 *
	 * @param mixed      $data Dữ liệu lỗi.
	 * @param string|int $code Mã lỗi.
	 */
	public function add_data( $data, $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		if ( isset( $this->error_data[ $code ] ) ) {
			$this->additional_data[ $code ][] = $this->error_data[ $code ];
		}

		$this->error_data[ $code ] = $data;
	}

	/**
	 * Lấy tất cả dữ liệu lỗi cho một mã lỗi theo thứ tự dữ liệu được thêm vào.
	 *
	 * @since 5.6.0
	 *
	 * @param string|int $code Mã lỗi.
	 * @return mixed[] Mảng dữ liệu lỗi, nếu tồn tại.
	 */
	public function get_all_error_data( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		$data = array();

		if ( isset( $this->additional_data[ $code ] ) ) {
			$data = $this->additional_data[ $code ];
		}

		if ( isset( $this->error_data[ $code ] ) ) {
			$data[] = $this->error_data[ $code ];
		}

		return $data;
	}

	/**
	 * Xóa lỗi đã chỉ định.
	 *
	 * Hàm này xóa tất cả thông báo lỗi liên kết với mã lỗi
	 * đã chỉ định, cùng với mọi dữ liệu lỗi cho mã đó.
	 *
	 * @since 4.1.0
	 *
	 * @param string|int $code Mã lỗi.
	 */
	public function remove( $code ) {
		unset( $this->errors[ $code ] );
		unset( $this->error_data[ $code ] );
		unset( $this->additional_data[ $code ] );
	}

	/**
	 * Gộp các lỗi từ đối tượng lỗi đã cho vào đối tượng này.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_Error $error Đối tượng lỗi cần gộp.
	 */
	public function merge_from( WP_Error $error ) {
		static::copy_errors( $error, $this );
	}

	/**
	 * Xuất các lỗi trong đối tượng này sang đối tượng đã cho.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_Error $error Đối tượng lỗi đích để xuất vào.
	 */
	public function export_to( WP_Error $error ) {
		static::copy_errors( $this, $error );
	}

	/**
	 * Sao chép lỗi từ một instance WP_Error sang instance khác.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_Error $from WP_Error nguồn để sao chép từ.
	 * @param WP_Error $to   WP_Error đích để sao chép tới.
	 */
	protected static function copy_errors( WP_Error $from, WP_Error $to ) {
		foreach ( $from->get_error_codes() as $code ) {
			foreach ( $from->get_error_messages( $code ) as $error_message ) {
				$to->add( $code, $error_message );
			}

			foreach ( $from->get_all_error_data( $code ) as $data ) {
				$to->add_data( $data, $code );
			}
		}
	}
}
