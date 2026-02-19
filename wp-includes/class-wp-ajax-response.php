<?php
/**
 * Gửi phản hồi XML về cho yêu cầu Ajax.
 *
 * @package WordPress
 * @since 2.1.0
 */
#[AllowDynamicProperties]
class WP_Ajax_Response {
	/**
	 * Lưu trữ các phản hồi XML để gửi.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public $responses = array();

	/**
	 * Hàm khởi tạo - Truyền tham số cho WP_Ajax_Response::add().
	 *
	 * @since 2.1.0
	 *
	 * @see WP_Ajax_Response::add()
	 *
	 * @param string|array $args Tùy chọn. Sẽ được truyền cho phương thức add().
	 */
	public function __construct( $args = '' ) {
		if ( ! empty( $args ) ) {
			$this->add( $args );
		}
	}

	/**
	 * Thêm dữ liệu vào phản hồi XML dựa trên các tham số đã cho.
	 *
	 * Với các giá trị mặc định của `$args`, đầu ra dữ liệu bổ sung sẽ là:
	 *
	 *     <response action='{$action}_$id'>
	 *      <$what id='$id' position='$position'>
	 *          <response_data><![CDATA[$data]]></response_data>
	 *      </$what>
	 *     </response>
	 *
	 * @since 2.1.0
	 *
	 * @param string|array $args {
	 *     Tùy chọn. Mảng hoặc chuỗi các tham số phản hồi XML.
	 *
	 *     @type string          $what         Loại phản hồi XML-RPC. Được sử dụng làm phần tử con của `<response>`.
	 *                                         Mặc định 'object' (`<object>`).
	 *     @type string|false    $action       Giá trị sử dụng cho thuộc tính `action` trong `<response>`. Sẽ được
	 *                                         nối thêm `_$id` khi xuất ra. Nếu false, `$action` sẽ mặc định
	 *                                         theo giá trị của `$_POST['action']`. Mặc định false.
	 *     @type int|WP_Error    $id           ID phản hồi, được sử dụng làm thuộc tính `id` của loại phản hồi. Cũng
	 *                                         chấp nhận đối tượng `WP_Error` nếu ID không tồn tại. Mặc định 0.
	 *     @type int|false       $old_id       ID phản hồi trước đó. Được sử dụng làm giá trị cho thuộc tính
	 *                                         `old_id` của loại phản hồi. False sẽ ẩn thuộc tính. Mặc định false.
	 *     @type string          $position     Giá trị thuộc tính `position` của loại phản hồi. Chấp nhận 1 (cuối),
	 *                                         -1 (đầu), HTML ID (sau), hoặc -HTML ID (trước). Mặc định 1 (cuối).
	 *     @type string|WP_Error $data         Nội dung/thông điệp phản hồi. Cũng chấp nhận đối tượng WP_Error nếu
	 *                                         ID không tồn tại. Mặc định rỗng.
	 *     @type array           $supplemental Mảng các chuỗi bổ sung sẽ được xuất ra bên trong phần tử `<supplemental>`
	 *                                         dưới dạng CDATA. Mặc định mảng rỗng.
	 * }
	 * @return string Phản hồi XML.
	 */
	public function add( $args = '' ) {
		$defaults = array(
			'what'         => 'object',
			'action'       => false,
			'id'           => '0',
			'old_id'       => false,
			'position'     => 1,
			'data'         => '',
			'supplemental' => array(),
		);

		$parsed_args = wp_parse_args( $args, $defaults );

		$position = preg_replace( '/[^a-z0-9:_-]/i', '', $parsed_args['position'] );
		$id       = $parsed_args['id'];
		$what     = $parsed_args['what'];
		$action   = $parsed_args['action'];
		$old_id   = $parsed_args['old_id'];
		$data     = $parsed_args['data'];

		if ( is_wp_error( $id ) ) {
			$data = $id;
			$id   = 0;
		}

		$response = '';
		if ( is_wp_error( $data ) ) {
			foreach ( (array) $data->get_error_codes() as $code ) {
				$response  .= "<wp_error code='$code'><![CDATA[" . $data->get_error_message( $code ) . ']]></wp_error>';
				$error_data = $data->get_error_data( $code );
				if ( ! $error_data ) {
					continue;
				}
				$class = '';
				if ( is_object( $error_data ) ) {
					$class      = ' class="' . get_class( $error_data ) . '"';
					$error_data = get_object_vars( $error_data );
				}

				$response .= "<wp_error_data code='$code'$class>";

				if ( is_scalar( $error_data ) ) {
					$response .= "<![CDATA[$error_data]]>";
				} elseif ( is_array( $error_data ) ) {
					foreach ( $error_data as $k => $v ) {
						$response .= "<$k><![CDATA[$v]]></$k>";
					}
				}

				$response .= '</wp_error_data>';
			}
		} else {
			$response = "<response_data><![CDATA[$data]]></response_data>";
		}

		$s = '';
		if ( is_array( $parsed_args['supplemental'] ) ) {
			foreach ( $parsed_args['supplemental'] as $k => $v ) {
				$s .= "<$k><![CDATA[$v]]></$k>";
			}
			$s = "<supplemental>$s</supplemental>";
		}

		if ( false === $action ) {
			$action = $_POST['action'];
		}
		$x  = '';
		$x .= "<response action='{$action}_$id'>"; // Thuộc tính action trong đầu ra xml được định dạng giống như một nonce action.
		$x .= "<$what id='$id' " . ( false === $old_id ? '' : "old_id='$old_id' " ) . "position='$position'>";
		$x .= $response;
		$x .= $s;
		$x .= "</$what>";
		$x .= '</response>';

		$this->responses[] = $x;
		return $x;
	}

	/**
	 * Hiển thị các phản hồi định dạng XML.
	 *
	 * Thiết lập header content type thành text/xml.
	 *
	 * @since 2.1.0
	 */
	public function send() {
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );
		echo "<?xml version='1.0' encoding='" . get_option( 'blog_charset' ) . "' standalone='yes'?><wp_ajax>";
		foreach ( (array) $this->responses as $response ) {
			echo $response;
		}
		echo '</wp_ajax>';
		if ( wp_doing_ajax() ) {
			wp_die();
		} else {
			die();
		}
	}
}
