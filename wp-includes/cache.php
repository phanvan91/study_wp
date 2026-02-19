<?php
/**
 * API Object Cache
 *
 * @link https://developer.wordpress.org/reference/classes/wp_object_cache/
 *
 * @package WordPress
 * @subpackage Cache
 */

/** Lớp WP_Object_Cache */
require_once ABSPATH . WPINC . '/class-wp-object-cache.php';

/**
 * Thiết lập biến toàn cục Object Cache và gán nó.
 *
 * @since 2.0.0
 *
 * @global WP_Object_Cache $wp_object_cache
 */
function wp_cache_init() {
	$GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

/**
 * Thêm dữ liệu vào cache, nếu khóa cache chưa tồn tại.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::add()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key    Khóa cache để sử dụng cho việc truy xuất sau.
 * @param mixed      $data   Dữ liệu cần thêm vào cache.
 * @param string     $group  Tùy chọn. Nhóm để thêm cache vào. Cho phép cùng khóa
 *                           được sử dụng trên các nhóm khác nhau. Mặc định rỗng.
 * @param int        $expire Tùy chọn. Thời gian hết hạn dữ liệu cache, tính bằng giây.
 *                           Mặc định 0 (không hết hạn).
 * @return bool True nếu thành công, false nếu khóa và nhóm cache đã tồn tại.
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add( $key, $data, $group, (int) $expire );
}

/**
 * Thêm nhiều giá trị vào cache trong một lần gọi.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::add_multiple()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param array  $data   Mảng các khóa và giá trị cần thiết lập.
 * @param string $group  Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
 * @param int    $expire Tùy chọn. Thời gian hết hạn nội dung cache, tính bằng giây.
 *                       Mặc định 0 (không hết hạn).
 * @return bool[] Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
 *                true nếu thành công, hoặc false nếu khóa và nhóm cache đã tồn tại.
 */
function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add_multiple( $data, $group, $expire );
}

/**
 * Thay thế nội dung cache bằng dữ liệu mới.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::replace()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key    Khóa cho dữ liệu cache cần thay thế.
 * @param mixed      $data   Dữ liệu mới để lưu vào cache.
 * @param string     $group  Tùy chọn. Nhóm cho dữ liệu cache cần thay thế.
 *                           Mặc định rỗng.
 * @param int        $expire Tùy chọn. Thời gian hết hạn nội dung cache, tính bằng giây.
 *                           Mặc định 0 (không hết hạn).
 * @return bool True nếu nội dung được thay thế, false nếu giá trị gốc không tồn tại.
 */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
}

/**
 * Lưu dữ liệu vào cache.
 *
 * Khác với wp_cache_add() và wp_cache_replace() ở chỗ nó luôn ghi dữ liệu.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::set()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key    Khóa cache để sử dụng cho việc truy xuất sau.
 * @param mixed      $data   Nội dung cần lưu vào cache.
 * @param string     $group  Tùy chọn. Nơi nhóm nội dung cache. Cho phép cùng khóa
 *                           được sử dụng trên các nhóm khác nhau. Mặc định rỗng.
 * @param int        $expire Tùy chọn. Thời gian hết hạn nội dung cache, tính bằng giây.
 *                           Mặc định 0 (không hết hạn).
 * @return bool True nếu thành công, false nếu thất bại.
 */
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set( $key, $data, $group, (int) $expire );
}

/**
 * Thiết lập nhiều giá trị vào cache trong một lần gọi.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::set_multiple()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param array  $data   Mảng các khóa và giá trị cần thiết lập.
 * @param string $group  Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
 * @param int    $expire Tùy chọn. Thời gian hết hạn nội dung cache, tính bằng giây.
 *                       Mặc định 0 (không hết hạn).
 * @return bool[] Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
 *                true nếu thành công, hoặc false nếu thất bại.
 */
function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set_multiple( $data, $group, $expire );
}

/**
 * Lấy nội dung cache theo khóa và nhóm.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::get()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key   Khóa lưu trữ nội dung cache.
 * @param string     $group Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
 * @param bool       $force Tùy chọn. Có buộc cập nhật cache cục bộ
 *                          từ cache bền vững hay không. Mặc định false.
 * @param bool       $found Tùy chọn. Khóa có được tìm thấy trong cache không (truyền theo tham chiếu).
 *                          Phân biệt kết quả trả về false, một giá trị có thể lưu trữ. Mặc định null.
 * @return mixed|false Nội dung cache nếu thành công, false nếu không lấy được nội dung.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;

	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * Lấy nhiều giá trị từ cache trong một lần gọi.
 *
 * @since 5.5.0
 *
 * @see WP_Object_Cache::get_multiple()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param array  $keys  Mảng các khóa lưu trữ nội dung cache.
 * @param string $group Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
 * @param bool   $force Tùy chọn. Có buộc cập nhật cache cục bộ
 *                      từ cache bền vững hay không. Mặc định false.
 * @return array Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
 *               nội dung cache nếu thành công, hoặc false nếu thất bại.
 */
function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
	global $wp_object_cache;

	return $wp_object_cache->get_multiple( $keys, $group, $force );
}

/**
 * Xóa nội dung cache khớp với khóa và nhóm.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::delete()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key   Tên gọi của nội dung trong cache.
 * @param string     $group Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
 * @return bool True nếu xóa thành công, false nếu thất bại.
 */
function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete( $key, $group );
}

/**
 * Xóa nhiều giá trị khỏi cache trong một lần gọi.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::delete_multiple()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param array  $keys  Mảng các khóa cache cần xóa.
 * @param string $group Tùy chọn. Nơi nhóm nội dung cache. Mặc định rỗng.
 * @return bool[] Mảng giá trị trả về, nhóm theo khóa. Mỗi giá trị là
 *                true nếu thành công, hoặc false nếu nội dung không được xóa.
 */
function wp_cache_delete_multiple( array $keys, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete_multiple( $keys, $group );
}

/**
 * Tăng giá trị số của mục cache.
 *
 * @since 3.3.0
 *
 * @see WP_Object_Cache::incr()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key    Khóa cho nội dung cache cần tăng.
 * @param int        $offset Tùy chọn. Giá trị tăng thêm.
 *                           Mặc định 1.
 * @param string     $group  Tùy chọn. Nhóm chứa khóa. Mặc định rỗng.
 * @return int|false Giá trị mới của mục nếu thành công, false nếu thất bại.
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->incr( $key, $offset, $group );
}

/**
 * Giảm giá trị số của mục cache.
 *
 * @since 3.3.0
 *
 * @see WP_Object_Cache::decr()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int|string $key    Khóa cache cần giảm.
 * @param int        $offset Tùy chọn. Giá trị giảm đi.
 *                           Mặc định 1.
 * @param string     $group  Tùy chọn. Nhóm chứa khóa. Mặc định rỗng.
 * @return int|false Giá trị mới của mục nếu thành công, false nếu thất bại.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->decr( $key, $offset, $group );
}

/**
 * Xóa tất cả mục cache.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::flush()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @return bool True nếu thành công, false nếu thất bại.
 */
function wp_cache_flush() {
	global $wp_object_cache;

	return $wp_object_cache->flush();
}

/**
 * Xóa tất cả mục cache khỏi bộ nhớ đệm runtime trong bộ nhớ.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::flush()
 *
 * @return bool True nếu thành công, false nếu thất bại.
 */
function wp_cache_flush_runtime() {
	return wp_cache_flush();
}

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

	return $wp_object_cache->flush_group( $group );
}

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
	switch ( $feature ) {
		case 'add_multiple':
		case 'set_multiple':
		case 'get_multiple':
		case 'delete_multiple':
		case 'flush_runtime':
		case 'flush_group':
			return true;

		default:
			return false;
	}
}

/**
 * Đóng cache.
 *
 * Hàm này đã ngừng hoạt động kể từ WordPress 2.5. Chức năng
 * đã bị loại bỏ cùng với phần còn lại của cache bền vững.
 *
 * Điều này không có nghĩa là plugin không thể triển khai hàm này khi cần
 * đảm bảo cache được dọn dẹp sau khi WordPress không còn cần nó nữa.
 *
 * @since 2.0.0
 *
 * @return true Luôn trả về true.
 */
function wp_cache_close() {
	return true;
}

/**
 * Thêm một nhóm hoặc tập hợp nhóm vào danh sách nhóm toàn cục.
 *
 * @since 2.6.0
 *
 * @see WP_Object_Cache::add_global_groups()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param string|string[] $groups Một nhóm hoặc mảng nhóm cần thêm.
 */
function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_global_groups( $groups );
}

/**
 * Thêm một nhóm hoặc tập hợp nhóm vào danh sách nhóm không bền vững.
 *
 * @since 2.6.0
 *
 * @param string|string[] $groups Một nhóm hoặc mảng nhóm cần thêm.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	// Cache mặc định không bền vững nên không cần làm gì ở đây.
}

/**
 * Chuyển đổi ID blog nội bộ.
 *
 * Thay đổi ID blog được sử dụng để tạo khóa trong các nhóm dành riêng cho blog.
 *
 * @since 3.5.0
 *
 * @see WP_Object_Cache::switch_to_blog()
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 *
 * @param int $blog_id ID trang web.
 */
function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;

	$wp_object_cache->switch_to_blog( $blog_id );
}

/**
 * Đặt lại khóa và cấu trúc cache nội bộ.
 *
 * Nếu backend cache sử dụng ID blog hoặc trang web toàn cục như một phần của khóa cache,
 * hàm này hướng dẫn backend đặt lại các khóa đó và thực hiện dọn dẹp
 * vì ID blog hoặc trang web đã thay đổi kể từ khi khởi tạo cache.
 *
 * Hàm này đã ngừng sử dụng. Sử dụng wp_cache_switch_to_blog() thay thế khi
 * chuẩn bị cache cho việc chuyển blog. Để xóa cache
 * trong unit test, hãy cân nhắc sử dụng wp_cache_init(). wp_cache_init() không
 * được khuyến nghị ngoài unit test vì hiệu suất bị ảnh hưởng khi sử dụng.
 *
 * @since 3.0.0
 * @deprecated 3.5.0 Sử dụng wp_cache_switch_to_blog()
 * @see WP_Object_Cache::reset()
 *
 * @global WP_Object_Cache $wp_object_cache Đối tượng cache toàn cục.
 */
function wp_cache_reset() {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_cache_switch_to_blog()' );

	global $wp_object_cache;

	$wp_object_cache->reset();
}
