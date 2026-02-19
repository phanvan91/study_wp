<?php
/**
 * REST API: Lớp WP_REST_User_Meta_Fields
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Lớp cốt lõi dùng để quản lý giá trị meta cho người dùng thông qua REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Meta_Fields
 */
class WP_REST_User_Meta_Fields extends WP_REST_Meta_Fields {

	/**
	 * Lấy loại meta người dùng.
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại meta người dùng.
	 */
	protected function get_meta_type() {
		return 'user';
	}

	/**
	 * Lấy loại phụ meta người dùng.
	 *
	 * @since 4.9.8
	 *
	 * @return string 'user' Không có loại phụ.
	 */
	protected function get_meta_subtype() {
		return 'user';
	}

	/**
	 * Lấy loại cho register_rest_field().
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại trường REST người dùng.
	 */
	public function get_rest_field_type() {
		return 'user';
	}
}
