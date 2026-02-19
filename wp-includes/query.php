<?php
/**
 * API WordPress Query
 *
 * API query cố gắng xác định phần nào của WordPress mà người dùng đang truy cập. Nó
 * cũng cung cấp chức năng lấy thông tin query URL.
 *
 * @link https://developer.wordpress.org/themes/basics/the-loop/ Thêm thông tin về The Loop.
 *
 * @package WordPress
 * @subpackage Query
 */

/**
 * Lấy giá trị của một biến query trong lớp WP_Query.
 *
 * @since 1.5.0
 * @since 3.9.0 Đối số `$default_value` được giới thiệu.
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string $query_var     Khóa biến cần lấy.
 * @param mixed  $default_value Tùy chọn. Giá trị trả về nếu biến query chưa được đặt.
 *                              Mặc định chuỗi rỗng.
 * @return mixed Nội dung của biến query.
 */
function get_query_var( $query_var, $default_value = '' ) {
	global $wp_query;
	return $wp_query->get( $query_var, $default_value );
}

/**
 * Lấy đối tượng đang được truy vấn hiện tại.
 *
 * Wrapper cho WP_Query::get_queried_object().
 *
 * @since 3.1.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return WP_Term|WP_Post_Type|WP_Post|WP_User|null Đối tượng được truy vấn.
 */
function get_queried_object() {
	global $wp_query;
	return $wp_query->get_queried_object();
}

/**
 * Lấy ID của đối tượng đang được truy vấn hiện tại.
 *
 * Wrapper cho WP_Query::get_queried_object_id().
 *
 * @since 3.1.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return int ID của đối tượng được truy vấn.
 */
function get_queried_object_id() {
	global $wp_query;
	return $wp_query->get_queried_object_id();
}

/**
 * Đặt giá trị của một biến query trong lớp WP_Query.
 *
 * @since 2.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string $query_var Khóa biến query.
 * @param mixed  $value     Giá trị biến query.
 */
function set_query_var( $query_var, $value ) {
	global $wp_query;
	$wp_query->set( $query_var, $value );
}

/**
 * Thiết lập The Loop với các tham số query.
 *
 * Lưu ý: Hàm này sẽ ghi đè hoàn toàn query chính và không được thiết kế để sử dụng
 * bởi plugin hoặc theme. Cách tiếp cận quá đơn giản của nó trong việc thay đổi query chính có thể
 * gây ra vấn đề và nên tránh bất cứ khi nào có thể. Trong hầu hết trường hợp, có các cách tốt hơn
 * và hiệu quả hơn để thay đổi query chính, chẳng hạn qua action {@see 'pre_get_posts'}
 * trong WP_Query.
 *
 * Hàm này không được sử dụng bên trong WordPress Loop.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param array|string $query Mảng hoặc chuỗi các đối số WP_Query.
 * @return WP_Post[]|int[] Mảng các đối tượng bài viết hoặc ID bài viết.
 */
function query_posts( $query ) {
	$GLOBALS['wp_query'] = new WP_Query();
	return $GLOBALS['wp_query']->query( $query );
}

/**
 * Hủy query trước đó và thiết lập query mới.
 *
 * Hàm này nên được sử dụng sau query_posts() và trước một query_posts() khác.
 * Nó sẽ loại bỏ các lỗi khó phát hiện xảy ra khi đối tượng WP_Query trước đó
 * không được hủy đúng cách trước khi thiết lập một đối tượng khác.
 *
 * @since 2.3.0
 *
 * @global WP_Query $wp_query     Đối tượng WordPress Query.
 * @global WP_Query $wp_the_query Bản sao của instance WP_Query toàn cục được tạo trong wp_reset_query().
 */
function wp_reset_query() {
	$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
	wp_reset_postdata();
}

/**
 * Sau khi lặp qua một query riêng biệt, hàm này khôi phục
 * biến toàn cục $post về bài viết hiện tại trong query chính.
 *
 * @since 3.0.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 */
function wp_reset_postdata() {
	global $wp_query;

	if ( isset( $wp_query ) ) {
		$wp_query->reset_postdata();
	}
}

/*
 * Kiểm tra loại query.
 */

/**
 * Xác định xem query có phải cho một trang lưu trữ hiện có hay không.
 *
 * Các trang lưu trữ bao gồm lưu trữ theo chuyên mục, thẻ, tác giả, ngày tháng,
 * loại bài viết tùy chỉnh và taxonomy tùy chỉnh.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_category()
 * @see is_tag()
 * @see is_author()
 * @see is_date()
 * @see is_post_type_archive()
 * @see is_tax()
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một trang lưu trữ hiện có hay không.
 */
function is_archive() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_archive();
}

/**
 * Xác định xem query có phải cho một trang lưu trữ loại bài viết hiện có hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 3.1.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string|string[] $post_types Tùy chọn. Loại bài viết hoặc mảng loại bài viết
 *                                    để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang lưu trữ loại bài viết hiện có hay không.
 */
function is_post_type_archive( $post_types = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_post_type_archive( $post_types );
}

/**
 * Xác định xem query có phải cho một trang đính kèm hiện có hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 2.0.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param int|string|int[]|string[] $attachment Tùy chọn. ID đính kèm, tiêu đề, slug, hoặc mảng
 *                                              để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang đính kèm hiện có hay không.
 */
function is_attachment( $attachment = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_attachment( $attachment );
}

/**
 * Xác định xem query có phải cho một trang lưu trữ tác giả hiện có hay không.
 *
 * Nếu tham số $author được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho một trong các tác giả được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param int|string|int[]|string[] $author Tùy chọn. ID người dùng, biệt danh, nicename, hoặc mảng
 *                                          để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang lưu trữ tác giả hiện có hay không.
 */
function is_author( $author = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_author( $author );
}

/**
 * Xác định xem query có phải cho một trang lưu trữ chuyên mục hiện có hay không.
 *
 * Nếu tham số $category được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho một trong các chuyên mục được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param int|string|int[]|string[] $category Tùy chọn. ID chuyên mục, tên, slug, hoặc mảng
 *                                            để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang lưu trữ chuyên mục hiện có hay không.
 */
function is_category( $category = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_category( $category );
}

/**
 * Xác định xem query có phải cho một trang lưu trữ thẻ hiện có hay không.
 *
 * Nếu tham số $tag được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho một trong các thẻ được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 2.3.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param int|string|int[]|string[] $tag Tùy chọn. ID thẻ, tên, slug, hoặc mảng
 *                                       để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang lưu trữ thẻ hiện có hay không.
 */
function is_tag( $tag = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_tag( $tag );
}

/**
 * Xác định xem query có phải cho một trang lưu trữ taxonomy tùy chỉnh hiện có hay không.
 *
 * Nếu tham số $taxonomy được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho taxonomy cụ thể đó hay không.
 *
 * Nếu tham số $term được chỉ định thêm cùng tham số $taxonomy,
 * hàm này sẽ kiểm tra thêm xem query có phải cho một trong các term
 * được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 2.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string|string[]           $taxonomy Tùy chọn. Slug taxonomy hoặc các slug để kiểm tra.
 *                                            Mặc định rỗng.
 * @param int|string|int[]|string[] $term     Tùy chọn. ID term, tên, slug, hoặc mảng
 *                                            để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang lưu trữ taxonomy tùy chỉnh hiện có hay không.
 *              True cho các trang lưu trữ taxonomy tùy chỉnh, false cho các taxonomy tích hợp sẵn
 *              (lưu trữ chuyên mục và thẻ).
 */
function is_tax( $taxonomy = '', $term = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_tax( $taxonomy, $term );
}

/**
 * Xác định xem query có phải cho một lưu trữ theo ngày hiện có hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một lưu trữ theo ngày hiện có hay không.
 */
function is_date() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_date();
}

/**
 * Xác định xem query có phải cho một lưu trữ theo ngày cụ thể hiện có hay không.
 *
 * Kiểm tra điều kiện để xác minh xem trang có phải là trang lưu trữ theo ngày hiển thị bài viết cho ngày hiện tại hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một lưu trữ theo ngày cụ thể hiện có hay không.
 */
function is_day() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_day();
}

/**
 * Xác định xem query có phải cho một feed hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string|string[] $feeds Tùy chọn. Loại feed hoặc mảng loại feed
 *                                         để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một feed hay không.
 */
function is_feed( $feeds = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_feed( $feeds );
}

/**
 * Query có phải cho feed bình luận không?
 *
 * @since 3.0.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một feed bình luận hay không.
 */
function is_comment_feed() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_comment_feed();
}

/**
 * Xác định xem query có phải cho trang chủ của website hay không.
 *
 * Đây là nội dung được hiển thị tại URL chính của website.
 *
 * Phụ thuộc vào cài đặt Đọc "Trang chủ hiển thị" 'show_on_front' và 'page_on_front' của website.
 *
 * Nếu bạn đặt một trang tĩnh làm trang chủ, hàm này sẽ trả về
 * true khi xem trang đó.
 *
 * Nếu không thì giống như {@see is_home()}.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 2.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho trang chủ của website hay không.
 */
function is_front_page() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_front_page();
}

/**
 * Xác định xem query có phải cho trang chủ blog hay không.
 *
 * Trang chủ blog là trang hiển thị nội dung blog theo thời gian của website.
 *
 * is_home() phụ thuộc vào cài đặt Đọc "Trang chủ hiển thị" 'show_on_front'
 * và 'page_for_posts' của website.
 *
 * Nếu một trang tĩnh được đặt làm trang chủ, hàm này chỉ trả về true
 * trên trang bạn đặt làm "Trang bài viết".
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_front_page()
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho trang chủ blog hay không.
 */
function is_home() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_home();
}

/**
 * Xác định xem query có phải cho trang Chính sách bảo mật hay không.
 *
 * Trang Chính sách bảo mật là trang hiển thị nội dung Chính sách bảo mật của website.
 *
 * is_privacy_policy() phụ thuộc vào cài đặt Quyền riêng tư "Thay đổi trang Chính sách bảo mật" 'wp_page_for_privacy_policy'.
 *
 * Hàm này chỉ trả về true trên trang bạn đặt làm "Trang Chính sách bảo mật".
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 5.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho trang Chính sách bảo mật hay không.
 */
function is_privacy_policy() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_privacy_policy();
}

/**
 * Xác định xem query có phải cho một lưu trữ theo tháng hiện có hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một lưu trữ theo tháng hiện có hay không.
 */
function is_month() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_month();
}

/**
 * Xác định xem query có phải cho một trang đơn hiện có hay không.
 *
 * Nếu tham số $page được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho một trong các trang được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_single()
 * @see is_singular()
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param int|string|int[]|string[] $page Tùy chọn. ID trang, tiêu đề, slug, hoặc mảng
 *                                        để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một trang đơn hiện có hay không.
 */
function is_page( $page = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_page( $page );
}

/**
 * Xác định xem query có phải cho kết quả phân trang và không phải trang đầu tiên hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho kết quả phân trang hay không.
 */
function is_paged() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_paged();
}

/**
 * Xác định xem query có phải cho bản xem trước bài viết hoặc trang hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 2.0.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho bản xem trước bài viết hoặc trang hay không.
 */
function is_preview() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_preview();
}

/**
 * Query có phải cho file robots.txt không?
 *
 * @since 2.1.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho file robots.txt hay không.
 */
function is_robots() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_robots();
}

/**
 * Query có phải cho file favicon.ico không?
 *
 * @since 5.4.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho file favicon.ico hay không.
 */
function is_favicon() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_favicon();
}

/**
 * Xác định xem query có phải cho một tìm kiếm hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một tìm kiếm hay không.
 */
function is_search() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_search();
}

/**
 * Xác định xem query có phải cho một bài viết đơn hiện có hay không.
 *
 * Hoạt động với bất kỳ loại bài viết nào, ngoại trừ đính kèm và trang.
 *
 * Nếu tham số $post được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho một trong các bài viết được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_page()
 * @see is_singular()
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param int|string|int[]|string[] $post Tùy chọn. ID bài viết, tiêu đề, slug, hoặc mảng
 *                                        để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một bài viết đơn hiện có hay không.
 */
function is_single( $post = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_single( $post );
}

/**
 * Xác định xem query có phải cho một bài viết đơn hiện có thuộc bất kỳ loại bài viết nào
 * (post, attachment, page, loại bài viết tùy chỉnh) hay không.
 *
 * Nếu tham số $post_types được chỉ định, hàm này sẽ kiểm tra thêm
 * xem query có phải cho một trong các loại bài viết được chỉ định hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_page()
 * @see is_single()
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string|string[] $post_types Tùy chọn. Loại bài viết hoặc mảng loại bài viết
 *                                    để kiểm tra. Mặc định rỗng.
 * @return bool Liệu query có phải cho một bài viết đơn hiện có
 *              hoặc bất kỳ loại bài viết nào được cho hay không.
 */
function is_singular( $post_types = '' ) {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_singular( $post_types );
}

/**
 * Xác định xem query có phải cho một thời gian cụ thể hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một thời gian cụ thể hay không.
 */
function is_time() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_time();
}

/**
 * Xác định xem query có phải cho lời gọi endpoint trackback hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho lời gọi endpoint trackback hay không.
 */
function is_trackback() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_trackback();
}

/**
 * Xác định xem query có phải cho một lưu trữ theo năm hiện có hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một lưu trữ theo năm hiện có hay không.
 */
function is_year() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_year();
}

/**
 * Xác định xem query có dẫn đến lỗi 404 (không trả về kết quả) hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải là lỗi 404 hay không.
 */
function is_404() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_404();
}

/**
 * Query có phải cho một bài viết nhúng không?
 *
 * @since 4.4.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải cho một bài viết nhúng hay không.
 */
function is_embed() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1.0' );
		return false;
	}

	return $wp_query->is_embed();
}

/**
 * Xác định xem query có phải là query chính hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 3.3.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool Liệu query có phải là query chính hay không.
 */
function is_main_query() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '6.1.0' );
		return false;
	}

	if ( 'pre_get_posts' === current_filter() ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: pre_get_posts, 2: WP_Query->is_main_query(), 3: is_main_query(), 4: Documentation URL. */
				__( 'In %1$s, use the %2$s method, not the %3$s function. See %4$s.' ),
				'<code>pre_get_posts</code>',
				'<code>WP_Query->is_main_query()</code>',
				'<code>is_main_query()</code>',
				__( 'https://developer.wordpress.org/reference/functions/is_main_query/' )
			),
			'3.7.0'
		);
	}

	return $wp_query->is_main_query();
}

/*
 * The Loop. Điều khiển vòng lặp bài viết.
 */

/**
 * Xác định xem query WordPress hiện tại có bài viết để lặp qua hay không.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool True nếu có bài viết, false nếu đã hết vòng lặp.
 */
function have_posts() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		return false;
	}

	return $wp_query->have_posts();
}

/**
 * Xác định xem đoạn mã gọi có đang trong Loop hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Theme Developer Handbook.
 *
 * @since 2.0.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool True nếu đang ở trong vòng lặp, false nếu vòng lặp chưa bắt đầu hoặc đã kết thúc.
 */
function in_the_loop() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		return false;
	}

	return $wp_query->in_the_loop;
}

/**
 * Tua lại các bài viết trong vòng lặp.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 */
function rewind_posts() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		return;
	}

	$wp_query->rewind_posts();
}

/**
 * Lặp qua chỉ mục bài viết trong vòng lặp.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 */
function the_post() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		return;
	}

	$wp_query->the_post();
}

/*
 * Vòng lặp bình luận.
 */

/**
 * Xác định xem query WordPress hiện tại có bình luận để lặp qua hay không.
 *
 * @since 2.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @return bool True nếu có bình luận, false nếu không còn bình luận nào.
 */
function have_comments() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		return false;
	}

	return $wp_query->have_comments();
}

/**
 * Lặp qua chỉ mục bình luận trong vòng lặp bình luận.
 *
 * @since 2.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 */
function the_comment() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		return;
	}

	$wp_query->the_comment();
}

/**
 * Chuyển hướng các slug cũ đến permalink đúng.
 *
 * Cố gắng tìm slug hiện tại từ các slug trước đó.
 *
 * @since 2.1.0
 */
function wp_old_slug_redirect() {
	if ( is_404() && '' !== get_query_var( 'name' ) ) {
		// Đoán loại bài viết hiện tại dựa trên các biến query.
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		} elseif ( get_query_var( 'attachment' ) ) {
			$post_type = 'attachment';
		} elseif ( get_query_var( 'pagename' ) ) {
			$post_type = 'page';
		} else {
			$post_type = 'post';
		}

		if ( is_array( $post_type ) ) {
			if ( count( $post_type ) > 1 ) {
				return;
			}
			$post_type = reset( $post_type );
		}

		// Không cố gắng chuyển hướng cho các loại bài viết phân cấp.
		if ( is_post_type_hierarchical( $post_type ) ) {
			return;
		}

		$id = _find_post_by_old_slug( $post_type );

		if ( ! $id ) {
			$id = _find_post_by_old_date( $post_type );
		}

		/**
		 * Lọc ID bài viết chuyển hướng slug cũ.
		 *
		 * @since 4.9.3
		 *
		 * @param int $id ID bài viết chuyển hướng.
		 */
		$id = apply_filters( 'old_slug_redirect_post_id', $id );

		if ( ! $id ) {
			return;
		}

		$link = get_permalink( $id );

		if ( get_query_var( 'paged' ) > 1 ) {
			$link = user_trailingslashit( trailingslashit( $link ) . 'page/' . get_query_var( 'paged' ) );
		} elseif ( is_embed() ) {
			$link = user_trailingslashit( trailingslashit( $link ) . 'embed' );
		}

		/**
		 * Lọc URL chuyển hướng slug cũ.
		 *
		 * @since 4.4.0
		 *
		 * @param string $link URL chuyển hướng.
		 */
		$link = apply_filters( 'old_slug_redirect_url', $link );

		if ( ! $link ) {
			return;
		}

		wp_redirect( $link, 301 ); // Chuyển hướng vĩnh viễn.
		exit;
	}
}

/**
 * Tìm ID bài viết để chuyển hướng slug cũ.
 *
 * @since 4.9.3
 * @access private
 *
 * @see wp_old_slug_redirect()
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $post_type Loại bài viết hiện tại dựa trên các biến query.
 * @return int ID bài viết.
 */
function _find_post_by_old_slug( $post_type ) {
	global $wpdb;

	$query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta, $wpdb->posts WHERE ID = post_id AND post_type = %s AND meta_key = '_wp_old_slug' AND meta_value = %s", $post_type, get_query_var( 'name' ) );

	/*
	 * Nếu year, monthnum, hoặc day đã được chỉ định, làm cho query chính xác hơn
	 * phòng trường hợp có nhiều giá trị _wp_old_slug giống nhau.
	 */
	if ( get_query_var( 'year' ) ) {
		$query .= $wpdb->prepare( ' AND YEAR(post_date) = %d', get_query_var( 'year' ) );
	}
	if ( get_query_var( 'monthnum' ) ) {
		$query .= $wpdb->prepare( ' AND MONTH(post_date) = %d', get_query_var( 'monthnum' ) );
	}
	if ( get_query_var( 'day' ) ) {
		$query .= $wpdb->prepare( ' AND DAYOFMONTH(post_date) = %d', get_query_var( 'day' ) );
	}

	$key          = md5( $query );
	$last_changed = wp_cache_get_last_changed( 'posts' );
	$cache_key    = "find_post_by_old_slug:$key:$last_changed";
	$cache        = wp_cache_get( $cache_key, 'post-queries' );
	if ( false !== $cache ) {
		$id = $cache;
	} else {
		$id = (int) $wpdb->get_var( $query );
		wp_cache_set( $cache_key, $id, 'post-queries' );
	}

	return $id;
}

/**
 * Tìm ID bài viết để chuyển hướng ngày cũ.
 *
 * @since 4.9.3
 * @access private
 *
 * @see wp_old_slug_redirect()
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $post_type Loại bài viết hiện tại dựa trên các biến query.
 * @return int ID bài viết.
 */
function _find_post_by_old_date( $post_type ) {
	global $wpdb;

	$date_query = '';
	if ( get_query_var( 'year' ) ) {
		$date_query .= $wpdb->prepare( ' AND YEAR(pm_date.meta_value) = %d', get_query_var( 'year' ) );
	}
	if ( get_query_var( 'monthnum' ) ) {
		$date_query .= $wpdb->prepare( ' AND MONTH(pm_date.meta_value) = %d', get_query_var( 'monthnum' ) );
	}
	if ( get_query_var( 'day' ) ) {
		$date_query .= $wpdb->prepare( ' AND DAYOFMONTH(pm_date.meta_value) = %d', get_query_var( 'day' ) );
	}

	$id = 0;
	if ( $date_query ) {
		$query        = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta AS pm_date, $wpdb->posts WHERE ID = post_id AND post_type = %s AND meta_key = '_wp_old_date' AND post_name = %s" . $date_query, $post_type, get_query_var( 'name' ) );
		$key          = md5( $query );
		$last_changed = wp_cache_get_last_changed( 'posts' );
		$cache_key    = "find_post_by_old_date:$key:$last_changed";
		$cache        = wp_cache_get( $cache_key, 'post-queries' );
		if ( false !== $cache ) {
			$id = $cache;
		} else {
			$id = (int) $wpdb->get_var( $query );
			if ( ! $id ) {
				// Kiểm tra xem slug cũ có khớp với ngày cũ hay không.
				$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts, $wpdb->postmeta AS pm_slug, $wpdb->postmeta AS pm_date WHERE ID = pm_slug.post_id AND ID = pm_date.post_id AND post_type = %s AND pm_slug.meta_key = '_wp_old_slug' AND pm_slug.meta_value = %s AND pm_date.meta_key = '_wp_old_date'" . $date_query, $post_type, get_query_var( 'name' ) ) );
			}
			wp_cache_set( $cache_key, $id, 'post-queries' );
		}
	}

	return $id;
}

/**
 * Thiết lập dữ liệu bài viết toàn cục.
 *
 * @since 1.5.0
 * @since 4.4.0 Thêm khả năng truyền ID bài viết vào `$post`.
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param WP_Post|object|int $post Instance WP_Post hoặc ID/đối tượng bài viết.
 * @return bool True khi hoàn thành.
 */
function setup_postdata( $post ) {
	global $wp_query;

	if ( ! empty( $wp_query ) && $wp_query instanceof WP_Query ) {
		return $wp_query->setup_postdata( $post );
	}

	return false;
}

/**
 * Tạo dữ liệu bài viết.
 *
 * @since 5.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param WP_Post|object|int $post Instance WP_Post hoặc ID/đối tượng bài viết.
 * @return array|false Các phần tử của bài viết, hoặc false khi thất bại.
 */
function generate_postdata( $post ) {
	global $wp_query;

	if ( ! empty( $wp_query ) && $wp_query instanceof WP_Query ) {
		return $wp_query->generate_postdata( $post );
	}

	return false;
}
