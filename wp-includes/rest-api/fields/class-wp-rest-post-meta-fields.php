<?php
/**
 * REST API: Lớp WP_REST_Post_Meta_Fields
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Lớp cốt lõi dùng để quản lý giá trị meta cho bài viết thông qua REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Meta_Fields
 */
class WP_REST_Post_Meta_Fields extends WP_REST_Meta_Fields {

	/**
	 * Loại bài viết để đăng ký trường.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $post_type;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 4.7.0
	 *
	 * @param string $post_type Loại bài viết để đăng ký trường.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
	}

	/**
	 * Lấy loại meta bài viết.
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại meta.
	 */
	protected function get_meta_type() {
		return 'post';
	}

	/**
	 * Lấy loại phụ meta bài viết.
	 *
	 * @since 4.9.8
	 *
	 * @return string Loại phụ cho loại meta, hoặc chuỗi rỗng nếu không có loại phụ cụ thể.
	 */
	protected function get_meta_subtype() {
		return $this->post_type;
	}

	/**
	 * Lấy loại cho register_rest_field().
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_field()
	 *
	 * @return string Loại trường REST.
	 */
	public function get_rest_field_type() {
		return $this->post_type;
	}
}
