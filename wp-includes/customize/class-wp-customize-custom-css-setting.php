<?php
/**
 * API Tùy biến: Lớp WP_Customize_Custom_CSS_Setting
 *
 * Xử lý việc xác thực, làm sạch và lưu giá trị.
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.7.0
 */

/**
 * Cài đặt tùy chỉnh để xử lý CSS Tùy chỉnh của WP.
 *
 * @since 4.7.0
 *
 * @see WP_Customize_Setting
 */
final class WP_Customize_Custom_CSS_Setting extends WP_Customize_Setting {

	/**
	 * Loại cài đặt.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $type = 'custom_css';

	/**
	 * Phương thức truyền tải cài đặt.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $transport = 'postMessage';

	/**
	 * Quyền hạn cần thiết để chỉnh sửa cài đặt này.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $capability = 'edit_css';

	/**
	 * Bảng kiểu.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $stylesheet = '';

	/**
	 * Hàm khởi tạo WP_Customize_Custom_CSS_Setting.
	 *
	 * @since 4.7.0
	 *
	 * @throws Exception Nếu ID cài đặt không khớp với mẫu `custom_css[$stylesheet]`.
	 *
	 * @param WP_Customize_Manager $manager Đối tượng khởi tạo Trình tùy biến.
	 * @param string               $id      ID cụ thể của cài đặt.
	 *                                      Có thể là tên tùy chỉnh giao diện hoặc tùy chọn.
	 * @param array                $args    Các tham số cài đặt.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		if ( 'custom_css' !== $this->id_data['base'] ) {
			throw new Exception( 'Expected custom_css id_base.' );
		}
		if ( 1 !== count( $this->id_data['keys'] ) || empty( $this->id_data['keys'][0] ) ) {
			throw new Exception( 'Expected single stylesheet key.' );
		}
		$this->stylesheet = $this->id_data['keys'][0];
	}

	/**
	 * Thêm bộ lọc để xem trước giá trị bài viết.
	 *
	 * @since 4.7.9
	 *
	 * @return bool False khi xem trước bị bỏ qua do không có thay đổi nào cần xem trước.
	 */
	public function preview() {
		if ( $this->is_previewed ) {
			return false;
		}
		$this->is_previewed = true;
		add_filter( 'wp_get_custom_css', array( $this, 'filter_previewed_wp_get_custom_css' ), 9, 2 );
		return true;
	}

	/**
	 * Lọc `wp_get_custom_css` để áp dụng giá trị đã tùy biến.
	 *
	 * Được sử dụng trong chế độ xem trước khi `wp_get_custom_css()` được gọi để hiển thị kiểu dáng.
	 *
	 * @since 4.7.0
	 *
	 * @see wp_get_custom_css()
	 *
	 * @param string $css        CSS gốc.
	 * @param string $stylesheet Bảng kiểu hiện tại.
	 * @return string CSS.
	 */
	public function filter_previewed_wp_get_custom_css( $css, $stylesheet ) {
		if ( $stylesheet === $this->stylesheet ) {
			$customized_value = $this->post_value( null );
			if ( ! is_null( $customized_value ) ) {
				$css = $customized_value;
			}
		}
		return $css;
	}

	/**
	 * Lấy giá trị của cài đặt. Sẽ trả về giá trị xem trước khi `preview()` được gọi.
	 *
	 * @since 4.7.0
	 *
	 * @see WP_Customize_Setting::value()
	 *
	 * @return string
	 */
	public function value() {
		if ( $this->is_previewed ) {
			$post_value = $this->post_value( null );
			if ( null !== $post_value ) {
				return $post_value;
			}
		}
		$id_base = $this->id_data['base'];
		$value   = '';
		$post    = wp_get_custom_css_post( $this->stylesheet );
		if ( $post ) {
			$value = $post->post_content;
		}
		if ( empty( $value ) ) {
			$value = $this->default;
		}

		/** Bộ lọc này được ghi nhận trong wp-includes/class-wp-customize-setting.php */
		$value = apply_filters( "customize_value_{$id_base}", $value, $this );

		return $value;
	}

	/**
	 * Xác thực giá trị nhận được có phải CSS hợp lệ hay không.
	 *
	 * Kiểm tra dấu ngoặc nhọn, ngoặc vuông và chú thích không cân bằng.
	 * Thông báo được hiển thị khi trạng thái trình tùy biến được lưu.
	 *
	 * @since 4.7.0
	 * @since 4.9.0 Việc kiểm tra ký tự cân bằng đã được chuyển sang phía máy khách thông qua kiểm tra lỗi trong trình soạn thảo mã.
	 * @since 5.9.0 Đổi tên `$css` thành `$value` để hỗ trợ tham số có tên trong PHP 8.
	 *
	 * @param string $value CSS cần xác thực.
	 * @return true|WP_Error True nếu đầu vào đã được xác thực, ngược lại trả về WP_Error.
	 */
	public function validate( $value ) {
		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$css = $value;

		$validity = new WP_Error();

		if ( preg_match( '#</?\w+#', $css ) ) {
			$validity->add( 'illegal_markup', __( 'Markup is not allowed in CSS.' ) );
		}

		if ( ! $validity->has_errors() ) {
			$validity = parent::validate( $css );
		}
		return $validity;
	}

	/**
	 * Lưu giá trị cài đặt CSS vào loại bài viết tùy chỉnh custom_css cho bảng kiểu.
	 *
	 * @since 4.7.0
	 * @since 5.9.0 Đổi tên `$css` thành `$value` để hỗ trợ tham số có tên trong PHP 8.
	 *
	 * @param string $value CSS cần cập nhật.
	 * @return int|false ID bài viết hoặc false nếu không thể lưu giá trị.
	 */
	public function update( $value ) {
		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$css = $value;

		if ( empty( $css ) ) {
			$css = '';
		}

		$r = wp_update_custom_css_post(
			$css,
			array(
				'stylesheet' => $this->stylesheet,
			)
		);

		if ( $r instanceof WP_Error ) {
			return false;
		}
		$post_id = $r->ID;

		// Lưu đệm ID bài viết trong tùy chỉnh giao diện để cải thiện hiệu suất, tránh truy vấn DB bổ sung.
		if ( $this->manager->get_stylesheet() === $this->stylesheet ) {
			set_theme_mod( 'custom_css_post_id', $post_id );
		}

		return $post_id;
	}
}
