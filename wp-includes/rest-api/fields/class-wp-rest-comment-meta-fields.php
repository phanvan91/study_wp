<?php
/**
 * REST API: Lớp WP_REST_Comment_Meta_Fields
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Lớp cốt lõi để quản lý meta bình luận thông qua REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Meta_Fields
 */
class WP_REST_Comment_Meta_Fields extends WP_REST_Meta_Fields {

	/**
	 * Lấy loại bình luận cho meta bình luận.
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại meta.
	 */
	protected function get_meta_type() {
		return 'comment';
	}

	/**
	 * Lấy loại phụ meta bình luận.
	 *
	 * @since 4.9.8
	 *
	 * @return string 'comment' Không có loại phụ.
	 */
	protected function get_meta_subtype() {
		return 'comment';
	}

	/**
	 * Lấy loại cho register_rest_field() trong ngữ cảnh bình luận.
	 *
	 * @since 4.7.0
	 *
	 * @return string Loại trường REST.
	 */
	public function get_rest_field_type() {
		return 'comment';
	}
}
