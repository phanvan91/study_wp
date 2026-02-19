<?php
/**
 * Các hàm API Object Cache bị thiếu từ object cache của bên thứ ba.
 *
 * @link https://developer.wordpress.org/reference/classes/wp_object_cache/
 *
 * @package WordPress
 * @subpackage Cache
 */

if ( ! function_exists( 'wp_cache_add_multiple' ) ) :
	/**
	 * Thêm nhiều giá trị vào bộ nhớ đệm trong một lần gọi, nếu các khóa cache chưa tồn tại.
	 *
	 * Hàm tương thích để mô phỏng wp_cache_add_multiple().
	 *
	 * @ignore
	 * @since 6.0.0
	 *
	 * @see wp_cache_add_multiple()
	 *
	 * @param array  $data   Mảng các khóa và giá trị cần thêm.
	 * @param string $group  Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
	 * @param int    $expire Tùy chọn. Thời gian hết hạn nội dung cache, tính bằng giây.
	 *                       Mặc định 0 (không hết hạn).
	 * @return bool[] Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
	 *                true nếu thành công, hoặc false nếu khóa và nhóm cache đã tồn tại.
	 */
	function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = wp_cache_add( $key, $value, $group, $expire );
		}

		return $values;
	}
endif;

if ( ! function_exists( 'wp_cache_set_multiple' ) ) :
	/**
	 * Thiết lập nhiều giá trị vào bộ nhớ đệm trong một lần gọi.
	 *
	 * Khác với wp_cache_add_multiple() ở chỗ nó luôn ghi dữ liệu.
	 *
	 * Hàm tương thích để mô phỏng wp_cache_set_multiple().
	 *
	 * @ignore
	 * @since 6.0.0
	 *
	 * @see wp_cache_set_multiple()
	 *
	 * @param array  $data   Mảng các khóa và giá trị cần thiết lập.
	 * @param string $group  Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
	 * @param int    $expire Tùy chọn. Thời gian hết hạn nội dung cache, tính bằng giây.
	 *                       Mặc định 0 (không hết hạn).
	 * @return bool[] Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
	 *                true nếu thành công, hoặc false nếu thất bại.
	 */
	function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = wp_cache_set( $key, $value, $group, $expire );
		}

		return $values;
	}
endif;

if ( ! function_exists( 'wp_cache_get_multiple' ) ) :
	/**
	 * Lấy nhiều giá trị từ bộ nhớ đệm trong một lần gọi.
	 *
	 * Hàm tương thích để mô phỏng wp_cache_get_multiple().
	 *
	 * @ignore
	 * @since 5.5.0
	 *
	 * @see wp_cache_get_multiple()
	 *
	 * @param array  $keys  Mảng các khóa lưu trữ nội dung cache.
	 * @param string $group Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
	 * @param bool   $force Tùy chọn. Có buộc cập nhật cache cục bộ
	 *                      từ cache bền vững hay không. Mặc định false.
	 * @return array Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
	 *               nội dung cache nếu thành công, hoặc false nếu thất bại.
	 */
	function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
		$values = array();

		foreach ( $keys as $key ) {
			$values[ $key ] = wp_cache_get( $key, $group, $force );
		}

		return $values;
	}
endif;

if ( ! function_exists( 'wp_cache_delete_multiple' ) ) :
	/**
	 * Xóa nhiều giá trị khỏi bộ nhớ đệm trong một lần gọi.
	 *
	 * Hàm tương thích để mô phỏng wp_cache_delete_multiple().
	 *
	 * @ignore
	 * @since 6.0.0
	 *
	 * @see wp_cache_delete_multiple()
	 *
	 * @param array  $keys  Mảng các khóa cache cần xóa.
	 * @param string $group Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
	 * @return bool[] Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
	 *                true nếu thành công, hoặc false nếu nội dung không được xóa.
	 */
	function wp_cache_delete_multiple( array $keys, $group = '' ) {
		$values = array();

		foreach ( $keys as $key ) {
			$values[ $key ] = wp_cache_delete( $key, $group );
		}

		return $values;
	}
endif;

if ( ! function_exists( 'wp_cache_flush_runtime' ) ) :
	/**
	 * Xóa tất cả mục cache khỏi bộ nhớ đệm runtime trong bộ nhớ.
	 *
	 * Hàm tương thích để mô phỏng wp_cache_flush_runtime().
	 *
	 * @ignore
	 * @since 6.0.0
	 *
	 * @see wp_cache_flush_runtime()
	 *
	 * @return bool True nếu thành công, false nếu thất bại.
	 */
	function wp_cache_flush_runtime() {
		if ( ! wp_cache_supports( 'flush_runtime' ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				__( 'Your object cache implementation does not support flushing the in-memory runtime cache.' ),
				'6.1.0'
			);

			return false;
		}

		return wp_cache_flush();
	}
endif;

if ( ! function_exists( 'wp_cache_flush_group' ) ) :
	/**
	 * Xóa tất cả mục cache trong một nhóm, nếu triển khai object cache hỗ trợ.
	 *
	 * Trước khi gọi hàm này, luôn kiểm tra hỗ trợ xóa nhóm bằng
	 * hàm `wp_cache_supports( 'flush_group' )`.
	 *
	 * @since 6.1.0
	 *
	 * @see WP_Object_Cache::flush_group()
	 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
	 *
	 * @param string $group Tên nhóm cần xóa khỏi cache.
	 * @return bool True nếu nhóm đã được xóa, false nếu không.
	 */
	function wp_cache_flush_group( $group ) {
		global $wp_object_cache;

		if ( ! wp_cache_supports( 'flush_group' ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				__( 'Your object cache implementation does not support flushing individual groups.' ),
				'6.1.0'
			);

			return false;
		}

		return $wp_object_cache->flush_group( $group );
	}
endif;

if ( ! function_exists( 'wp_cache_supports' ) ) :
	/**
	 * Xác định xem triển khai object cache có hỗ trợ một tính năng cụ thể hay không.
	 *
	 * @since 6.1.0
	 *
	 * @param string $feature Tên tính năng cần kiểm tra. Các giá trị có thể bao gồm:
	 *                        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple',
	 *                        'flush_runtime', 'flush_group'.
	 * @return bool True nếu tính năng được hỗ trợ, false nếu không.
	 */
	function wp_cache_supports( $feature ) {
		return false;
	}
endif;
