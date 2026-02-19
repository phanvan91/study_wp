<?php
/**
 * REST API: Lớp WP_REST_Term_Meta_Fields
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Lớp cốt lõi dùng để quản lý giá trị meta cho taxonomy thông qua REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Meta_Fields
 */
class WP_REST_Term_Meta_Fields extends WP_REST_Meta_Fields {

	/**
	 * Taxonomy để đăng ký trường.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 4.7.0
	 *
	 * @param string $taxonomy Taxonomy để đăng ký trường.
	 */
	public function __construct( $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Lấy loại meta taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại meta.
	 */
	protected function get_meta_type() {
		return 'term';
	}

	/**
	 * Lấy loại phụ meta taxonomy.
	 *
	 * @since 4.9.8
	 *
	 * @return string Loại phụ cho loại meta, hoặc chuỗi rỗng nếu không có loại phụ cụ thể.
	 */
	protected function get_meta_subtype() {
		return $this->taxonomy;
	}

	/**
	 * Lấy loại cho register_rest_field().
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại trường REST.
	 */
	public function get_rest_field_type() {
		return 'post_tag' === $this->taxonomy ? 'tag' : $this->taxonomy;
	}
}
