<?php
/**
 * Các hàm đã lỗi thời từ các phiên bản WordPress trước. Bạn không nên sử dụng
 * các hàm này mà hãy tìm các hàm thay thế. Các hàm này sẽ bị
 * xóa bỏ trong phiên bản sau.
 *
 * @package WordPress
 * @subpackage Deprecated
 */

/*
 * Các hàm đã lỗi thời đến đây để chết.
 */

/**
 * Lấy tất cả dữ liệu bài viết cho một bài viết cụ thể.
 *
 * @since 0.71
 * @deprecated 1.5.1 Use get_post()
 * @see get_post()
 *
 * @param int $postid ID bài viết.
 * @return array Dữ liệu bài viết.
 */
function get_postdata($postid) {
	_deprecated_function( __FUNCTION__, '1.5.1', 'get_post()' );

	$post = get_post($postid);

	$postdata = array (
		'ID' => $post->ID,
		'Author_ID' => $post->post_author,
		'Date' => $post->post_date,
		'Content' => $post->post_content,
		'Excerpt' => $post->post_excerpt,
		'Title' => $post->post_title,
		'Category' => $post->post_category,
		'post_status' => $post->post_status,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'post_password' => $post->post_password,
		'to_ping' => $post->to_ping,
		'pinged' => $post->pinged,
		'post_type' => $post->post_type,
		'post_name' => $post->post_name
	);

	return $postdata;
}

/**
 * Thiết lập Vòng lặp WordPress.
 *
 * Sử dụng Vòng lặp thay thế.
 *
 * @link https://developer.wordpress.org/themes/basics/the-loop/
 *
 * @since 1.0.1
 * @deprecated 1.5.0
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 */
function start_wp() {
	global $wp_query;

	_deprecated_function( __FUNCTION__, '1.5.0', __('new WordPress Loop') );

	// Vì vòng lặp kiểu cũ đang được sử dụng, tăng bộ lặp truy vấn ở đây.
	$wp_query->next_post();

	setup_postdata( get_post() );
}

/**
 * Trả về hoặc in ra ID chuyên mục.
 *
 * @since 0.71
 * @deprecated 0.71 Use get_the_category()
 * @see get_the_category()
 *
 * @param bool $display Tùy chọn. Có hiển thị đầu ra hay không. Mặc định true.
 * @return int ID chuyên mục.
 */
function the_category_ID($display = true) {
	_deprecated_function( __FUNCTION__, '0.71', 'get_the_category()' );

	// Lấy chuyên mục đầu tiên trong danh sách.
	$categories = get_the_category();
	$cat = $categories[0]->term_id;

	if ( $display )
		echo $cat;

	return $cat;
}

/**
 * In ra chuyên mục với văn bản tùy chọn trước và sau.
 *
 * @since 0.71
 * @deprecated 0.71 Use get_the_category_by_ID()
 * @see get_the_category_by_ID()
 *
 * @param string $before Tùy chọn. Văn bản hiển thị trước chuyên mục. Mặc định rỗng.
 * @param string $after  Tùy chọn. Văn bản hiển thị sau chuyên mục. Mặc định rỗng.
 */
function the_category_head( $before = '', $after = '' ) {
	global $currentcat, $previouscat;

	_deprecated_function( __FUNCTION__, '0.71', 'get_the_category_by_ID()' );

	// Lấy chuyên mục đầu tiên trong danh sách.
	$categories = get_the_category();
	$currentcat = $categories[0]->category_id;
	if ( $currentcat != $previouscat ) {
		echo $before;
		echo get_the_category_by_ID($currentcat);
		echo $after;
		$previouscat = $currentcat;
	}
}

/**
 * In ra liên kết đến bài viết trước.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use previous_post_link()
 * @see previous_post_link()
 *
 * @param string $format
 * @param string $previous
 * @param string $title
 * @param string $in_same_cat
 * @param int    $limitprev
 * @param string $excluded_categories
 */
function previous_post($format='%', $previous='previous post: ', $title='yes', $in_same_cat='no', $limitprev=1, $excluded_categories='') {

	_deprecated_function( __FUNCTION__, '2.0.0', 'previous_post_link()' );

	if ( empty($in_same_cat) || 'no' == $in_same_cat )
		$in_same_cat = false;
	else
		$in_same_cat = true;

	$post = get_previous_post($in_same_cat, $excluded_categories);

	if ( !$post )
		return;

	$string = '<a href="'.get_permalink($post->ID).'">'.$previous;
	if ( 'yes' == $title )
		$string .= apply_filters('the_title', $post->post_title, $post->ID);
	$string .= '</a>';
	$format = str_replace('%', $string, $format);
	echo $format;
}

/**
 * In ra liên kết đến bài viết tiếp theo.
 *
 * @since 0.71
 * @deprecated 2.0.0 Use next_post_link()
 * @see next_post_link()
 *
 * @param string $format
 * @param string $next
 * @param string $title
 * @param string $in_same_cat
 * @param int $limitnext
 * @param string $excluded_categories
 */
function next_post($format='%', $next='next post: ', $title='yes', $in_same_cat='no', $limitnext=1, $excluded_categories='') {
	_deprecated_function( __FUNCTION__, '2.0.0', 'next_post_link()' );

	if ( empty($in_same_cat) || 'no' == $in_same_cat )
		$in_same_cat = false;
	else
		$in_same_cat = true;

	$post = get_next_post($in_same_cat, $excluded_categories);

	if ( !$post	)
		return;

	$string = '<a href="'.get_permalink($post->ID).'">'.$next;
	if ( 'yes' == $title )
		$string .= apply_filters('the_title', $post->post_title, $post->ID);
	$string .= '</a>';
	$format = str_replace('%', $string, $format);
	echo $format;
}

/**
 * Kiểm tra xem người dùng có thể tạo bài viết không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $blog_id Không sử dụng
 * @param int $category_id Không sử dụng
 * @return bool
 */
function user_can_create_post($user_id, $blog_id = 1, $category_id = 'None') {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	$author_data = get_userdata($user_id);
	return ($author_data->user_level > 1);
}

/**
 * Kiểm tra xem người dùng có thể tạo bản nháp không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $blog_id Không sử dụng
 * @param int $category_id Không sử dụng
 * @return bool
 */
function user_can_create_draft($user_id, $blog_id = 1, $category_id = 'None') {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	$author_data = get_userdata($user_id);
	return ($author_data->user_level >= 1);
}

/**
 * Kiểm tra xem người dùng có thể sửa bài viết không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $post_id
 * @param int $blog_id Không sử dụng
 * @return bool
 */
function user_can_edit_post($user_id, $post_id, $blog_id = 1) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	$author_data = get_userdata($user_id);
	$post = get_post($post_id);
	$post_author_data = get_userdata($post->post_author);

	if ( (($user_id == $post_author_data->ID) && !($post->post_status == 'publish' && $author_data->user_level < 2))
			|| ($author_data->user_level > $post_author_data->user_level)
			|| ($author_data->user_level >= 10) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Kiểm tra xem người dùng có thể xóa bài viết không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $post_id
 * @param int $blog_id Không sử dụng
 * @return bool
 */
function user_can_delete_post($user_id, $post_id, $blog_id = 1) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	// Hiện tại nếu có thể sửa thì có thể xóa.
	return user_can_edit_post($user_id, $post_id, $blog_id);
}

/**
 * Kiểm tra xem người dùng có thể đặt ngày cho bài viết mới không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $blog_id Không sử dụng
 * @param int $category_id Không sử dụng
 * @return bool
 */
function user_can_set_post_date($user_id, $blog_id = 1, $category_id = 'None') {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	$author_data = get_userdata($user_id);
	return (($author_data->user_level > 4) && user_can_create_post($user_id, $blog_id, $category_id));
}

/**
 * Kiểm tra xem người dùng có thể sửa ngày bài viết không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $post_id
 * @param int $blog_id Không sử dụng
 * @return bool Trả về true nếu $user_id có thể sửa ngày của $post_id
 */
function user_can_edit_post_date($user_id, $post_id, $blog_id = 1) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	$author_data = get_userdata($user_id);
	return (($author_data->user_level > 4) && user_can_edit_post($user_id, $post_id, $blog_id));
}

/**
 * Kiểm tra xem người dùng có thể sửa bình luận bài viết không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $post_id
 * @param int $blog_id Không sử dụng
 * @return bool Trả về true nếu $user_id có thể sửa bình luận của $post_id
 */
function user_can_edit_post_comments($user_id, $post_id, $blog_id = 1) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	// Hiện tại nếu có thể sửa bài viết thì có thể sửa bình luận trên đó.
	return user_can_edit_post($user_id, $post_id, $blog_id);
}

/**
 * Kiểm tra xem người dùng có thể xóa bình luận bài viết không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $post_id
 * @param int $blog_id Không sử dụng
 * @return bool Trả về true nếu $user_id có thể xóa bình luận của $post_id
 */
function user_can_delete_post_comments($user_id, $post_id, $blog_id = 1) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	// Hiện tại nếu có thể sửa bình luận thì có thể xóa bình luận.
	return user_can_edit_post_comments($user_id, $post_id, $blog_id);
}

/**
 * Kiểm tra xem người dùng có thể sửa thông tin người dùng khác không.
 *
 * @since 1.5.0
 * @deprecated 2.0.0 Use current_user_can()
 * @see current_user_can()
 *
 * @param int $user_id
 * @param int $other_user
 * @return bool
 */
function user_can_edit_user($user_id, $other_user) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'current_user_can()' );

	$user  = get_userdata($user_id);
	$other = get_userdata($other_user);
	if ( $user->user_level > $other->user_level || $user->user_level > 8 || $user->ID == $other->ID )
		return true;
	else
		return false;
}

/**
 * Lấy các liên kết liên quan đến chuyên mục $cat_name.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use get_bookmarks()
 * @see get_bookmarks()
 *
 * @param string $cat_name         Tùy chọn. Tên chuyên mục để sử dụng. Nếu không tìm thấy, sử dụng tất cả.
 *                                 Mặc định 'noname'.
 * @param string $before           Tùy chọn. HTML xuất ra trước liên kết. Mặc định rỗng.
 * @param string $after            Tùy chọn. HTML xuất ra sau liên kết. Mặc định '<br />'.
 * @param string $between          Tùy chọn. HTML xuất ra giữa liên kết/hình ảnh và mô tả.
 *                                 Không sử dụng nếu không có hình ảnh hoặc $show_images là true. Mặc định ' '.
 * @param bool   $show_images      Tùy chọn. Có hiển thị hình ảnh không (nếu được định nghĩa). Mặc định true.
 * @param string $orderby          Tùy chọn. Thứ tự xuất ra liên kết. Ví dụ 'id', 'name', 'url',
 *                                 'description', 'rating', hoặc 'owner'. Mặc định 'id'.
 *                                 Nếu bắt đầu tên bằng dấu gạch dưới, thứ tự sẽ bị đảo ngược.
 *                                 Chỉ định 'rand' sẽ trả về liên kết theo thứ tự ngẫu nhiên.
 * @param bool   $show_description Tùy chọn. Có hiển thị mô tả nếu show_images=false/chưa định nghĩa.
 *                                 Mặc định true.
 * @param bool   $show_rating      Tùy chọn. Hiển thị sao/ký tự xếp hạng. Mặc định false.
 * @param int    $limit            Tùy chọn. Giới hạn X mục. Nếu không chỉ định, hiển thị tất cả.
 *                                 Mặc định -1.
 * @param int    $show_updated     Tùy chọn. Có hiển thị dấu thời gian cập nhật cuối không. Mặc định 0.
 */
function get_linksbyname($cat_name = "noname", $before = '', $after = '<br />', $between = " ", $show_images = true, $orderby = 'id',
						$show_description = true, $show_rating = false,
						$limit = -1, $show_updated = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmarks()' );

	$cat_id = -1;
	$cat = get_term_by('name', $cat_name, 'link_category');
	if ( $cat )
		$cat_id = $cat->term_id;

	get_links($cat_id, $before, $after, $between, $show_images, $orderby, $show_description, $show_rating, $limit, $show_updated);
}

/**
 * Lấy các liên kết liên quan đến chuyên mục được đặt tên.
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use wp_list_bookmarks()
 * @see wp_list_bookmarks()
 *
 * @param string $category Chuyên mục để sử dụng.
 * @param string $args
 * @return string|null
 */
function wp_get_linksbyname($category, $args = '') {
	_deprecated_function(__FUNCTION__, '2.1.0', 'wp_list_bookmarks()');

	$defaults = array(
		'after' => '<br />',
		'before' => '',
		'categorize' => 0,
		'category_after' => '',
		'category_before' => '',
		'category_name' => $category,
		'show_description' => 1,
		'title_li' => '',
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	return wp_list_bookmarks($parsed_args);
}

/**
 * Lấy mảng các đối tượng liên kết liên quan đến chuyên mục $cat_name.
 *
 *     $links = get_linkobjectsbyname( 'fred' );
 *     foreach ( $links as $link ) {
 *      	echo '<li>' . $link->link_name . '</li>';
 *     }
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use get_bookmarks()
 * @see get_bookmarks()
 *
 * @param string $cat_name Tùy chọn. Tên chuyên mục để sử dụng. Nếu không tìm thấy, sử dụng tất cả.
 *                         Mặc định 'noname'.
 * @param string $orderby  Tùy chọn. Thứ tự xuất ra liên kết. Ví dụ 'id', 'name', 'url',
 *                         'description', 'rating', hoặc 'owner'. Mặc định 'name'.
 *                         Nếu bắt đầu tên bằng dấu gạch dưới, thứ tự sẽ bị đảo ngược.
 *                         Chỉ định 'rand' sẽ trả về liên kết theo thứ tự ngẫu nhiên.
 * @param int    $limit    Tùy chọn. Giới hạn X mục. Nếu không chỉ định, hiển thị tất cả.
 *                         Mặc định -1.
 * @return array
 */
function get_linkobjectsbyname($cat_name = "noname" , $orderby = 'name', $limit = -1) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmarks()' );

	$cat_id = -1;
	$cat = get_term_by('name', $cat_name, 'link_category');
	if ( $cat )
		$cat_id = $cat->term_id;

	return get_linkobjects($cat_id, $orderby, $limit);
}

/**
 * Lấy mảng các đối tượng liên kết liên quan đến chuyên mục n.
 *
 * Cách sử dụng:
 *
 *     $links = get_linkobjects(1);
 *     if ($links) {
 *     	foreach ($links as $link) {
 *     		echo '<li>'.$link->link_name.'<br />'.$link->link_description.'</li>';
 *     	}
 *     }
 *
 * Các trường:
 *
 * - link_id
 * - link_url
 * - link_name
 * - link_image
 * - link_target
 * - link_category
 * - link_description
 * - link_visible
 * - link_owner
 * - link_rating
 * - link_updated
 * - link_rel
 * - link_notes
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use get_bookmarks()
 * @see get_bookmarks()
 *
 * @param int    $category Tùy chọn. Chuyên mục để sử dụng. Nếu không cung cấp chuyên mục, sử dụng tất cả.
 *                         Mặc định 0.
 * @param string $orderby  Tùy chọn. Thứ tự xuất ra liên kết. Ví dụ 'id', 'name', 'url',
 *                         'description', 'rating', hoặc 'owner'. Mặc định 'name'.
 *                         Nếu bắt đầu tên bằng dấu gạch dưới, thứ tự sẽ bị đảo ngược.
 *                         Chỉ định 'rand' sẽ trả về liên kết theo thứ tự ngẫu nhiên.
 * @param int    $limit    Tùy chọn. Giới hạn X mục. Nếu không chỉ định, hiển thị tất cả.
 *                         Mặc định 0.
 * @return array
 */
function get_linkobjects($category = 0, $orderby = 'name', $limit = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmarks()' );

	$links = get_bookmarks( array( 'category' => $category, 'orderby' => $orderby, 'limit' => $limit ) ) ;

	$links_array = array();
	foreach ($links as $link)
		$links_array[] = $link;

	return $links_array;
}

/**
 * Lấy các liên kết liên quan đến chuyên mục 'cat_name' và hiển thị sao/ký tự xếp hạng.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use get_bookmarks()
 * @see get_bookmarks()
 *
 * @param string $cat_name         Tùy chọn. Tên chuyên mục để sử dụng. Nếu không tìm thấy, sử dụng tất cả.
 *                                 Mặc định 'noname'.
 * @param string $before           Tùy chọn. HTML xuất ra trước liên kết. Mặc định rỗng.
 * @param string $after            Tùy chọn. HTML xuất ra sau liên kết. Mặc định '<br />'.
 * @param string $between          Tùy chọn. HTML xuất ra giữa liên kết/hình ảnh và mô tả.
 *                                 Không sử dụng nếu không có hình ảnh hoặc $show_images là true. Mặc định ' '.
 * @param bool   $show_images      Tùy chọn. Có hiển thị hình ảnh không (nếu được định nghĩa). Mặc định true.
 * @param string $orderby          Tùy chọn. Thứ tự xuất ra liên kết. Ví dụ 'id', 'name', 'url',
 *                                 'description', 'rating', hoặc 'owner'. Mặc định 'id'.
 *                                 Nếu bắt đầu tên bằng dấu gạch dưới, thứ tự sẽ bị đảo ngược.
 *                                 Chỉ định 'rand' sẽ trả về liên kết theo thứ tự ngẫu nhiên.
 * @param bool   $show_description Tùy chọn. Có hiển thị mô tả nếu show_images=false/chưa định nghĩa.
 *                                 Mặc định true.
 * @param int    $limit		       Tùy chọn. Giới hạn X mục. Nếu không chỉ định, hiển thị tất cả.
 *                                 Mặc định -1.
 * @param int    $show_updated     Tùy chọn. Có hiển thị dấu thời gian cập nhật cuối không. Mặc định 0.
 */
function get_linksbyname_withrating($cat_name = "noname", $before = '', $after = '<br />', $between = " ",
									$show_images = true, $orderby = 'id', $show_description = true, $limit = -1, $show_updated = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmarks()' );

	get_linksbyname($cat_name, $before, $after, $between, $show_images, $orderby, $show_description, true, $limit, $show_updated);
}

/**
 * Lấy các liên kết liên quan đến chuyên mục n và hiển thị sao/ký tự xếp hạng.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use get_bookmarks()
 * @see get_bookmarks()
 *
 * @param int    $category         Tùy chọn. Chuyên mục để sử dụng. Nếu không cung cấp chuyên mục, sử dụng tất cả.
 *                                 Mặc định 0.
 * @param string $before           Tùy chọn. HTML xuất ra trước liên kết. Mặc định rỗng.
 * @param string $after            Tùy chọn. HTML xuất ra sau liên kết. Mặc định '<br />'.
 * @param string $between          Tùy chọn. HTML xuất ra giữa liên kết/hình ảnh và mô tả.
 *                                 Không sử dụng nếu không có hình ảnh hoặc $show_images là true. Mặc định ' '.
 * @param bool   $show_images      Tùy chọn. Có hiển thị hình ảnh không (nếu được định nghĩa). Mặc định true.
 * @param string $orderby          Tùy chọn. Thứ tự xuất ra liên kết. Ví dụ 'id', 'name', 'url',
 *                                 'description', 'rating', hoặc 'owner'. Mặc định 'id'.
 *                                 Nếu bắt đầu tên bằng dấu gạch dưới, thứ tự sẽ bị đảo ngược.
 *                                 Chỉ định 'rand' sẽ trả về liên kết theo thứ tự ngẫu nhiên.
 * @param bool   $show_description Tùy chọn. Có hiển thị mô tả nếu show_images=false/chưa định nghĩa.
 *                                 Mặc định true.
 * @param int    $limit		       Tùy chọn. Giới hạn X mục. Nếu không chỉ định, hiển thị tất cả.
 *                                 Mặc định -1.
 * @param int    $show_updated     Tùy chọn. Có hiển thị dấu thời gian cập nhật cuối không. Mặc định 0.
 */
function get_links_withrating($category = -1, $before = '', $after = '<br />', $between = " ", $show_images = true,
							$orderby = 'id', $show_description = true, $limit = -1, $show_updated = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmarks()' );

	get_links($category, $before, $after, $between, $show_images, $orderby, $show_description, true, $limit, $show_updated);
}

/**
 * Lấy cài đặt auto_toggle.
 *
 * @since 0.71
 * @deprecated 2.1.0
 *
 * @param int $id Chuyên mục cần lấy. Nếu không cung cấp chuyên mục, sử dụng 0
 * @return int Chỉ trả về 0.
 */
function get_autotoggle($id = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0' );
	return 0;
}

/**
 * Liệt kê các chuyên mục.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use wp_list_categories()
 * @see wp_list_categories()
 *
 * @param int $optionall
 * @param string $all
 * @param string $sort_column
 * @param string $sort_order
 * @param string $file
 * @param bool $list
 * @param int $optiondates
 * @param int $optioncount
 * @param int $hide_empty
 * @param int $use_desc_for_title
 * @param bool $children
 * @param int $child_of
 * @param int $categories
 * @param int $recurse
 * @param string $feed
 * @param string $feed_image
 * @param string $exclude
 * @param bool $hierarchical
 * @return null|false
 */
function list_cats($optionall = 1, $all = 'All', $sort_column = 'ID', $sort_order = 'asc', $file = '', $list = true, $optiondates = 0,
				$optioncount = 0, $hide_empty = 1, $use_desc_for_title = 1, $children=false, $child_of=0, $categories=0,
				$recurse=0, $feed = '', $feed_image = '', $exclude = '', $hierarchical=false) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_list_categories()' );

	$query = compact('optionall', 'all', 'sort_column', 'sort_order', 'file', 'list', 'optiondates', 'optioncount', 'hide_empty', 'use_desc_for_title', 'children',
		'child_of', 'categories', 'recurse', 'feed', 'feed_image', 'exclude', 'hierarchical');
	return wp_list_cats($query);
}

/**
 * Liệt kê các chuyên mục.
 *
 * @since 1.2.0
 * @deprecated 2.1.0 Use wp_list_categories()
 * @see wp_list_categories()
 *
 * @param string|array $args
 * @return null|string|false
 */
function wp_list_cats($args = '') {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_list_categories()' );

	$parsed_args = wp_parse_args( $args );

	// Ánh xạ sang tên mới.
	if ( isset($parsed_args['optionall']) && isset($parsed_args['all']))
		$parsed_args['show_option_all'] = $parsed_args['all'];
	if ( isset($parsed_args['sort_column']) )
		$parsed_args['orderby'] = $parsed_args['sort_column'];
	if ( isset($parsed_args['sort_order']) )
		$parsed_args['order'] = $parsed_args['sort_order'];
	if ( isset($parsed_args['optiondates']) )
		$parsed_args['show_last_update'] = $parsed_args['optiondates'];
	if ( isset($parsed_args['optioncount']) )
		$parsed_args['show_count'] = $parsed_args['optioncount'];
	if ( isset($parsed_args['list']) )
		$parsed_args['style'] = $parsed_args['list'] ? 'list' : 'break';
	$parsed_args['title_li'] = '';

	return wp_list_categories($parsed_args);
}

/**
 * Phương thức đã lỗi thời để tạo danh sách thả xuống chuyên mục.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use wp_dropdown_categories()
 * @see wp_dropdown_categories()
 *
 * @param int $optionall
 * @param string $all
 * @param string $orderby
 * @param string $order
 * @param int $show_last_update
 * @param int $show_count
 * @param int $hide_empty
 * @param bool $optionnone
 * @param int $selected
 * @param int $exclude
 * @return string
 */
function dropdown_cats($optionall = 1, $all = 'All', $orderby = 'ID', $order = 'asc',
		$show_last_update = 0, $show_count = 0, $hide_empty = 1, $optionnone = false,
		$selected = 0, $exclude = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_dropdown_categories()' );

	$show_option_all = '';
	if ( $optionall )
		$show_option_all = $all;

	$show_option_none = '';
	if ( $optionnone )
		$show_option_none = _x( 'None', 'Categories dropdown (show_option_none parameter)' );

	$vars = compact('show_option_all', 'show_option_none', 'orderby', 'order',
					'show_last_update', 'show_count', 'hide_empty', 'selected', 'exclude');
	$query = add_query_arg($vars, '');
	return wp_dropdown_categories($query);
}

/**
 * Liệt kê các tác giả.
 *
 * @since 1.2.0
 * @deprecated 2.1.0 Use wp_list_authors()
 * @see wp_list_authors()
 *
 * @param bool $optioncount
 * @param bool $exclude_admin
 * @param bool $show_fullname
 * @param bool $hide_empty
 * @param string $feed
 * @param string $feed_image
 * @return null|string
 */
function list_authors($optioncount = false, $exclude_admin = true, $show_fullname = false, $hide_empty = true, $feed = '', $feed_image = '') {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_list_authors()' );

	$args = compact('optioncount', 'exclude_admin', 'show_fullname', 'hide_empty', 'feed', 'feed_image');
	return wp_list_authors($args);
}

/**
 * Lấy danh sách chuyên mục bài viết.
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use wp_get_post_categories()
 * @see wp_get_post_categories()
 *
 * @param int $blogid Không sử dụng
 * @param int $post_id
 * @return array
 */
function wp_get_post_cats($blogid = '1', $post_id = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_get_post_categories()' );
	return wp_get_post_categories($post_id);
}

/**
 * Thiết lập các chuyên mục mà ID bài viết thuộc về.
 *
 * @since 1.0.1
 * @deprecated 2.1.0
 * @deprecated Use wp_set_post_categories()
 * @see wp_set_post_categories()
 *
 * @param int $blogid Không sử dụng
 * @param int $post_id
 * @param array $post_categories
 * @return bool|mixed
 */
function wp_set_post_cats($blogid = '1', $post_id = 0, $post_categories = array()) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_set_post_categories()' );
	return wp_set_post_categories($post_id, $post_categories);
}

/**
 * Lấy danh sách lưu trữ.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use wp_get_archives()
 * @see wp_get_archives()
 *
 * @param string $type
 * @param string $limit
 * @param string $format
 * @param string $before
 * @param string $after
 * @param bool $show_post_count
 * @return string|null
 */
function get_archives($type='', $limit='', $format='html', $before = '', $after = '', $show_post_count = false) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_get_archives()' );
	$args = compact('type', 'limit', 'format', 'before', 'after', 'show_post_count');
	return wp_get_archives($args);
}

/**
 * Trả về hoặc in ra liên kết đến các bài viết của tác giả.
 *
 * @since 1.2.0
 * @deprecated 2.1.0 Use get_author_posts_url()
 * @see get_author_posts_url()
 *
 * @param bool $display
 * @param int $author_id
 * @param string $author_nicename Tùy chọn.
 * @return string|null
 */
function get_author_link($display, $author_id, $author_nicename = '') {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_author_posts_url()' );

	$link = get_author_posts_url($author_id, $author_nicename);

	if ( $display )
		echo $link;
	return $link;
}

/**
 * In danh sách trang dựa trên đối số.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use wp_link_pages()
 * @see wp_link_pages()
 *
 * @param string $before
 * @param string $after
 * @param string $next_or_number
 * @param string $nextpagelink
 * @param string $previouspagelink
 * @param string $pagelink
 * @param string $more_file
 * @return string
 */
function link_pages($before='<br />', $after='<br />', $next_or_number='number', $nextpagelink='next page', $previouspagelink='previous page',
					$pagelink='%', $more_file='') {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_link_pages()' );

	$args = compact('before', 'after', 'next_or_number', 'nextpagelink', 'previouspagelink', 'pagelink', 'more_file');
	return wp_link_pages($args);
}

/**
 * Lấy giá trị dựa trên tùy chọn.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use get_option()
 * @see get_option()
 *
 * @param string $option
 * @return string
 */
function get_settings($option) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_option()' );

	return get_option($option);
}

/**
 * In đường dẫn tĩnh của bài viết hiện tại trong vòng lặp.
 *
 * @since 0.71
 * @deprecated 1.2.0 Use the_permalink()
 * @see the_permalink()
 */
function permalink_link() {
	_deprecated_function( __FUNCTION__, '1.2.0', 'the_permalink()' );
	the_permalink();
}

/**
 * In đường dẫn tĩnh đến nguồn cấp RSS.
 *
 * @since 0.71
 * @deprecated 2.3.0 Use the_permalink_rss()
 * @see the_permalink_rss()
 *
 * @param string $deprecated
 */
function permalink_single_rss($deprecated = '') {
	_deprecated_function( __FUNCTION__, '2.3.0', 'the_permalink_rss()' );
	the_permalink_rss();
}

/**
 * Lấy các liên kết liên quan đến chuyên mục.
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use wp_list_bookmarks()
 * @see wp_list_bookmarks()
 *
 * @param string $args Chuỗi truy vấn
 * @return null|string
 */
function wp_get_links($args = '') {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_list_bookmarks()' );

	if ( ! str_contains( $args, '=' ) ) {
		$cat_id = $args;
		$args = add_query_arg( 'category', $cat_id, $args );
	}

	$defaults = array(
		'after' => '<br />',
		'before' => '',
		'between' => ' ',
		'categorize' => 0,
		'category' => '',
		'echo' => true,
		'limit' => -1,
		'orderby' => 'name',
		'show_description' => true,
		'show_images' => true,
		'show_rating' => false,
		'show_updated' => true,
		'title_li' => '',
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	return wp_list_bookmarks($parsed_args);
}

/**
 * Lấy các liên kết liên quan đến chuyên mục theo ID.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use get_bookmarks()
 * @see get_bookmarks()
 *
 * @param int    $category         Tùy chọn. Chuyên mục để sử dụng. Nếu không cung cấp chuyên mục, sử dụng tất cả.
 *                                 Mặc định 0.
 * @param string $before           Tùy chọn. HTML xuất ra trước liên kết. Mặc định rỗng.
 * @param string $after            Tùy chọn. HTML xuất ra sau liên kết. Mặc định '<br />'.
 * @param string $between          Tùy chọn. HTML xuất ra giữa liên kết/hình ảnh và mô tả.
 *                                 Không sử dụng nếu không có hình ảnh hoặc $show_images là true. Mặc định ' '.
 * @param bool   $show_images      Tùy chọn. Có hiển thị hình ảnh không (nếu được định nghĩa). Mặc định true.
 * @param string $orderby          Tùy chọn. Thứ tự xuất ra liên kết. Ví dụ 'id', 'name', 'url',
 *                                 'description', 'rating', hoặc 'owner'. Mặc định 'name'.
 *                                 Nếu bắt đầu tên bằng dấu gạch dưới, thứ tự sẽ bị đảo ngược.
 *                                 Chỉ định 'rand' sẽ trả về liên kết theo thứ tự ngẫu nhiên.
 * @param bool   $show_description Tùy chọn. Có hiển thị mô tả nếu show_images=false/chưa định nghĩa.
 *                                 Mặc định true.
 * @param bool   $show_rating      Tùy chọn. Hiển thị sao/ký tự xếp hạng. Mặc định false.
 * @param int    $limit            Tùy chọn. Giới hạn X mục. Nếu không chỉ định, hiển thị tất cả.
 *                                 Mặc định -1.
 * @param int    $show_updated     Tùy chọn. Có hiển thị dấu thời gian cập nhật cuối không. Mặc định 1.
 * @param bool   $display          Có hiển thị kết quả hay trả về thay thế.
 * @return null|string
 */
function get_links($category = -1, $before = '', $after = '<br />', $between = ' ', $show_images = true, $orderby = 'name',
			$show_description = true, $show_rating = false, $limit = -1, $show_updated = 1, $display = true) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmarks()' );

	$order = 'ASC';
	if ( str_starts_with($orderby, '_') ) {
		$order = 'DESC';
		$orderby = substr($orderby, 1);
	}

	if ( $category == -1 ) // get_bookmarks() sử dụng '' để chỉ tất cả chuyên mục.
		$category = '';

	$results = get_bookmarks(array('category' => $category, 'orderby' => $orderby, 'order' => $order, 'show_updated' => $show_updated, 'limit' => $limit));

	if ( !$results )
		return;

	$output = '';

	foreach ( (array) $results as $row ) {
		if ( !isset($row->recently_updated) )
			$row->recently_updated = false;
		$output .= $before;
		if ( $show_updated && $row->recently_updated )
			$output .= get_option('links_recently_updated_prepend');
		$the_link = '#';
		if ( !empty($row->link_url) )
			$the_link = esc_url($row->link_url);
		$rel = $row->link_rel;
		if ( '' != $rel )
			$rel = ' rel="' . $rel . '"';

		$desc = esc_attr(sanitize_bookmark_field('link_description', $row->link_description, $row->link_id, 'display'));
		$name = esc_attr(sanitize_bookmark_field('link_name', $row->link_name, $row->link_id, 'display'));
		$title = $desc;

		if ( $show_updated )
			if ( !str_starts_with($row->link_updated_f, '00') )
				$title .= ' ('.__('Last updated') . ' ' . gmdate(get_option('links_updated_date_format'), $row->link_updated_f + (get_option('gmt_offset') * HOUR_IN_SECONDS)) . ')';

		if ( '' != $title )
			$title = ' title="' . $title . '"';

		$alt = ' alt="' . $name . '"';

		$target = $row->link_target;
		if ( '' != $target )
			$target = ' target="' . $target . '"';

		$output .= '<a href="' . $the_link . '"' . $rel . $title . $target. '>';

		if ( '' != $row->link_image && $show_images ) {
			if ( str_contains( $row->link_image, 'http' ) )
				$output .= '<img src="' . $row->link_image . '"' . $alt . $title . ' />';
			else // Nếu là đường dẫn tương đối.
				$output .= '<img src="' . get_option('siteurl') . $row->link_image . '"' . $alt . $title . ' />';
		} else {
			$output .= $name;
		}

		$output .= '</a>';

		if ( $show_updated && $row->recently_updated )
			$output .= get_option('links_recently_updated_append');

		if ( $show_description && '' != $desc )
			$output .= $between . $desc;

		if ($show_rating) {
			$output .= $between . get_linkrating($row);
		}

		$output .= "$after\n";
	} // Kết thúc vòng lặp.

	if ( !$display )
		return $output;
	echo $output;
}

/**
 * Xuất toàn bộ danh sách liên kết theo chuyên mục.
 *
 * Xuất danh sách tất cả liên kết, được liệt kê theo chuyên mục, sử dụng cài đặt trong
 * $wpdb->linkcategories và xuất ra dưới dạng danh sách HTML không có thứ tự lồng nhau.
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use wp_list_bookmarks()
 * @see wp_list_bookmarks()
 *
 * @param string $order Sắp xếp chuyên mục liên kết theo 'name' hoặc 'id'
 */
function get_links_list($order = 'name') {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_list_bookmarks()' );

	$order = strtolower($order);

	// Xử lý sắp xếp chuyên mục liên kết.
	$direction = 'ASC';
	if ( str_starts_with( $order, '_' ) ) {
		$direction = 'DESC';
		$order = substr($order,1);
	}

	if ( !isset($direction) )
		$direction = '';

	$cats = get_categories(array('type' => 'link', 'orderby' => $order, 'order' => $direction, 'hierarchical' => 0));

	// Hiển thị từng chuyên mục.
	if ( $cats ) {
		foreach ( (array) $cats as $cat ) {
			// Xử lý từng chuyên mục.

			// Hiển thị tên chuyên mục.
			echo '  <li id="linkcat-' . $cat->term_id . '" class="linkcat"><h2>' . apply_filters('link_category', $cat->name ) . "</h2>\n\t<ul>\n";
			// Gọi get_links() với tất cả tham số phù hợp.
			get_links($cat->term_id, '<li>', "</li>", "\n", true, 'name', false);

			// Đóng chuyên mục cuối cùng.
			echo "\n\t</ul>\n</li>\n";
		}
	}
}

/**
 * Hiển thị liên kết đến cửa sổ bật lên liên kết và số lượng liên kết.
 *
 * @since 0.71
 * @deprecated 2.1.0
 *
 * @param string $text Văn bản của liên kết
 * @param int $width Chiều rộng của cửa sổ bật lên
 * @param int $height Chiều cao của cửa sổ bật lên
 * @param string $file Trang để mở trong cửa sổ bật lên
 * @param bool $count Số lượng liên kết trong cơ sở dữ liệu
 */
function links_popup_script($text = 'Links', $width=400, $height=400, $file='links.all.php', $count = true) {
	_deprecated_function( __FUNCTION__, '2.1.0' );
}

/**
 * Hàm cũ lấy giá trị của trường link_rating của liên kết.
 *
 * @since 1.0.1
 * @deprecated 2.1.0 Use sanitize_bookmark_field()
 * @see sanitize_bookmark_field()
 *
 * @param object $link Đối tượng liên kết.
 * @return mixed Giá trị của trường 'link_rating', false nếu không có.
 */
function get_linkrating( $link ) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'sanitize_bookmark_field()' );
	return sanitize_bookmark_field('link_rating', $link->link_rating, $link->link_id, 'display');
}

/**
 * Lấy tên chuyên mục theo ID.
 *
 * @since 0.71
 * @deprecated 2.1.0 Use get_category()
 * @see get_category()
 *
 * @param int $id Chuyên mục cần lấy. Nếu không cung cấp chuyên mục, sử dụng 0
 * @return string
 */
function get_linkcatname($id = 0) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_category()' );

	$id = (int) $id;

	if ( empty($id) )
		return '';

	$cats = wp_get_link_cats($id);

	if ( empty($cats) || ! is_array($cats) )
		return '';

	$cat_id = (int) $cats[0]; // Lấy chuyên mục đầu tiên.

	$cat = get_category($cat_id);
	return $cat->name;
}

/**
 * In liên kết nguồn cấp RSS bình luận.
 *
 * @since 1.0.1
 * @deprecated 2.5.0 Use post_comments_feed_link()
 * @see post_comments_feed_link()
 *
 * @param string $link_text
 */
function comments_rss_link($link_text = 'Comments RSS') {
	_deprecated_function( __FUNCTION__, '2.5.0', 'post_comments_feed_link()' );
	post_comments_feed_link($link_text);
}

/**
 * In/Trả về liên kết đến nguồn cấp RSS2 chuyên mục.
 *
 * @since 1.2.0
 * @deprecated 2.5.0 Use get_category_feed_link()
 * @see get_category_feed_link()
 *
 * @param bool $display
 * @param int $cat_id
 * @return string
 */
function get_category_rss_link($display = false, $cat_id = 1) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'get_category_feed_link()' );

	$link = get_category_feed_link($cat_id, 'rss2');

	if ( $display )
		echo $link;
	return $link;
}

/**
 * In/Trả về liên kết đến nguồn cấp RSS tác giả.
 *
 * @since 1.2.0
 * @deprecated 2.5.0 Use get_author_feed_link()
 * @see get_author_feed_link()
 *
 * @param bool $display
 * @param int $author_id
 * @return string
 */
function get_author_rss_link($display = false, $author_id = 1) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'get_author_feed_link()' );

	$link = get_author_feed_link($author_id);
	if ( $display )
		echo $link;
	return $link;
}

/**
 * Trả về liên kết đến nguồn cấp RSS của bài viết.
 *
 * @since 1.5.0
 * @deprecated 2.2.0 Use get_post_comments_feed_link()
 * @see get_post_comments_feed_link()
 *
 * @return string
 */
function comments_rss() {
	_deprecated_function( __FUNCTION__, '2.2.0', 'get_post_comments_feed_link()' );
	return esc_url( get_post_comments_feed_link() );
}

/**
 * Bí danh của wp_create_user().
 *
 * @since 2.0.0
 * @deprecated 2.0.0 Use wp_create_user()
 * @see wp_create_user()
 *
 * @param string $username Tên đăng nhập của người dùng.
 * @param string $password Mật khẩu của người dùng.
 * @param string $email    Email của người dùng.
 * @return int ID của người dùng mới.
 */
function create_user($username, $password, $email) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'wp_create_user()' );
	return wp_create_user($username, $password, $email);
}

/**
 * Hàm không sử dụng.
 *
 * @deprecated 2.5.0
 */
function gzip_compression() {
	_deprecated_function( __FUNCTION__, '2.5.0' );
	return false;
}

/**
 * Lấy mảng dữ liệu bình luận về bình luận $comment_id.
 *
 * @since 0.71
 * @deprecated 2.7.0 Use get_comment()
 * @see get_comment()
 *
 * @param int $comment_id ID của bình luận
 * @param int $no_cache Có sử dụng bộ nhớ đệm không (ép kiểu sang bool)
 * @param bool $include_unapproved Có bao gồm bình luận chưa duyệt không
 * @return array Dữ liệu bình luận
 */
function get_commentdata( $comment_id, $no_cache = 0, $include_unapproved = false ) {
	_deprecated_function( __FUNCTION__, '2.7.0', 'get_comment()' );
	return get_comment($comment_id, ARRAY_A);
}

/**
 * Lấy tên chuyên mục theo ID chuyên mục.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use get_cat_name()
 * @see get_cat_name()
 *
 * @param int $cat_id ID chuyên mục
 * @return string Tên chuyên mục
 */
function get_catname( $cat_id ) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_cat_name()' );
	return get_cat_name( $cat_id );
}

/**
 * Lấy danh sách chuyên mục con được phân tách trước và sau các ID thuật ngữ.
 *
 * @since 1.2.0
 * @deprecated 2.8.0 Use get_term_children()
 * @see get_term_children()
 *
 * @param int    $id      ID chuyên mục để lấy các chuyên mục con.
 * @param string $before  Tùy chọn. Thêm trước ID thuật ngữ chuyên mục. Mặc định '/'.
 * @param string $after   Tùy chọn. Thêm sau ID thuật ngữ chuyên mục. Mặc định chuỗi rỗng.
 * @param array  $visited Tùy chọn. Các ID thuật ngữ chuyên mục đã được thêm rồi.
 *                        Mặc định mảng rỗng.
 * @return string
 */
function get_category_children( $id, $before = '/', $after = '', $visited = array() ) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_term_children()' );
	if ( 0 == $id )
		return '';

	$chain = '';
	/** TODO: Tham khảo phân cấp */
	$cat_ids = get_all_category_ids();
	foreach ( (array) $cat_ids as $cat_id ) {
		if ( $cat_id == $id )
			continue;

		$category = get_category( $cat_id );
		if ( is_wp_error( $category ) )
			return $category;
		if ( $category->parent == $id && !in_array( $category->term_id, $visited ) ) {
			$visited[] = $category->term_id;
			$chain .= $before.$category->term_id.$after;
			$chain .= get_category_children( $category->term_id, $before, $after );
		}
	}
	return $chain;
}

/**
 * Lấy tất cả ID chuyên mục.
 *
 * @since 2.0.0
 * @deprecated 4.0.0 Use get_terms()
 * @see get_terms()
 *
 * @link https://developer.wordpress.org/reference/functions/get_all_category_ids/
 *
 * @return int[] Danh sách tất cả ID chuyên mục.
 */
function get_all_category_ids() {
	_deprecated_function( __FUNCTION__, '4.0.0', 'get_terms()' );

	$cat_ids = get_terms(
		array(
			'taxonomy' => 'category',
			'fields'   => 'ids',
			'get'      => 'all',
		)
	);

	return $cat_ids;
}

/**
 * Lấy mô tả của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Mô tả của tác giả.
 */
function get_the_author_description() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'description\')' );
	return get_the_author_meta('description');
}

/**
 * Hiển thị mô tả của tác giả bài viết hiện tại.
 *
 * @since 1.0.0
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_description() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'description\')' );
	the_author_meta('description');
}

/**
 * Lấy tên đăng nhập của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Tên đăng nhập (username) của tác giả.
 */
function get_the_author_login() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'login\')' );
	return get_the_author_meta('login');
}

/**
 * Hiển thị tên đăng nhập của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_login() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'login\')' );
	the_author_meta('login');
}

/**
 * Lấy tên (first name) của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Tên của tác giả.
 */
function get_the_author_firstname() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'first_name\')' );
	return get_the_author_meta('first_name');
}

/**
 * Hiển thị tên (first name) của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_firstname() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'first_name\')' );
	the_author_meta('first_name');
}

/**
 * Lấy họ (last name) của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Họ của tác giả.
 */
function get_the_author_lastname() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'last_name\')' );
	return get_the_author_meta('last_name');
}

/**
 * Hiển thị họ (last name) của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_lastname() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'last_name\')' );
	the_author_meta('last_name');
}

/**
 * Lấy biệt danh của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Biệt danh của tác giả.
 */
function get_the_author_nickname() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'nickname\')' );
	return get_the_author_meta('nickname');
}

/**
 * Hiển thị biệt danh của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_nickname() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'nickname\')' );
	the_author_meta('nickname');
}

/**
 * Lấy email của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Email của tác giả.
 */
function get_the_author_email() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'email\')' );
	return get_the_author_meta('email');
}

/**
 * Hiển thị email của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_email() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'email\')' );
	the_author_meta('email');
}

/**
 * Lấy số ICQ của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Số ICQ của tác giả.
 */
function get_the_author_icq() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'icq\')' );
	return get_the_author_meta('icq');
}

/**
 * Hiển thị số ICQ của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_icq() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'icq\')' );
	the_author_meta('icq');
}

/**
 * Lấy tên Yahoo! IM của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Tên Yahoo! IM của tác giả.
 */
function get_the_author_yim() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'yim\')' );
	return get_the_author_meta('yim');
}

/**
 * Hiển thị tên Yahoo! IM của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_yim() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'yim\')' );
	the_author_meta('yim');
}

/**
 * Lấy địa chỉ MSN của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Địa chỉ MSN của tác giả.
 */
function get_the_author_msn() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'msn\')' );
	return get_the_author_meta('msn');
}

/**
 * Hiển thị địa chỉ MSN của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_msn() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'msn\')' );
	the_author_meta('msn');
}

/**
 * Lấy địa chỉ AIM của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string Địa chỉ AIM của tác giả.
 */
function get_the_author_aim() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'aim\')' );
	return get_the_author_meta('aim');
}

/**
 * Hiển thị địa chỉ AIM của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta('aim')
 * @see the_author_meta()
 */
function the_author_aim() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'aim\')' );
	the_author_meta('aim');
}

/**
 * Lấy tên hiển thị ưa thích của tác giả được chỉ định.
 *
 * @since 1.0.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @param int $auth_id ID của tác giả.
 * @return string Tên hiển thị của tác giả.
 */
function get_author_name( $auth_id = false ) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'display_name\')' );
	return get_the_author_meta('display_name', $auth_id);
}

/**
 * Lấy URL trang chủ của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string URL đến trang của tác giả.
 */
function get_the_author_url() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'url\')' );
	return get_the_author_meta('url');
}

/**
 * Hiển thị URL trang chủ của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_url() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'url\')' );
	the_author_meta('url');
}

/**
 * Lấy ID của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 * @deprecated 2.8.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @return string|int ID của tác giả.
 */
function get_the_author_ID() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'get_the_author_meta(\'ID\')' );
	return get_the_author_meta('ID');
}

/**
 * Hiển thị ID của tác giả bài viết hiện tại.
 *
 * @since 0.71
 * @deprecated 2.8.0 Use the_author_meta()
 * @see the_author_meta()
 */
function the_author_ID() {
	_deprecated_function( __FUNCTION__, '2.8.0', 'the_author_meta(\'ID\')' );
	the_author_meta('ID');
}

/**
 * Hiển thị nội dung bài viết cho nguồn cấp dữ liệu.
 *
 * Đối với mã hóa HTML hoặc tham số $encode_html, có ba giá trị có thể:
 * - '0' sẽ biến các URL thành chú thích và sử dụng make_url_footnote().
 * - '1' sẽ mã hóa các ký tự đặc biệt và tự động hiển thị toàn bộ nội dung.
 * - '2' sẽ loại bỏ tất cả thẻ HTML khỏi nội dung.
 *
 * Cũng lưu ý rằng bạn không thể đặt số lượng từ mà không đặt mã hóa HTML.
 * Nếu là trường hợp đó, thì mã hóa HTML sẽ mặc định là 2, sẽ loại bỏ
 * tất cả thẻ HTML.
 *
 * Để giới hạn số lượng từ của nội dung, bạn có thể sử dụng tham số cut.
 * Nếu nội dung ít hơn số lượng, thì sẽ không có dấu chấm nào được thêm vào cuối.
 * Nếu có nội dung còn lại, thì dấu chấm sẽ được thêm vào và phần nội dung còn lại
 * sẽ bị xóa bỏ.
 *
 * @since 0.71
 *
 * @deprecated 2.9.0 Use the_content_feed()
 * @see the_content_feed()
 *
 * @param string $more_link_text Tùy chọn. Văn bản hiển thị khi có thêm nội dung nhưng
 *                               không được hiển thị. Mặc định '(more...)'.
 * @param int    $stripteaser    Tùy chọn. Mặc định 0.
 * @param string $more_file      Tùy chọn.
 * @param int    $cut            Tùy chọn. Số lượng từ giữ lại cho nội dung.
 * @param int    $encode_html    Tùy chọn. Cách mã hóa nội dung.
 */
function the_content_rss($more_link_text='(more...)', $stripteaser=0, $more_file='', $cut = 0, $encode_html = 0) {
	_deprecated_function( __FUNCTION__, '2.9.0', 'the_content_feed()' );
	$content = get_the_content($more_link_text, $stripteaser);

	/**
	 * Lọc nội dung bài viết trong ngữ cảnh nguồn cấp RSS.
	 *
	 * @since 0.71
	 *
	 * @param string $content Nội dung của bài viết hiện tại.
	 */
	$content = apply_filters('the_content_rss', $content);
	if ( $cut && !$encode_html )
		$encode_html = 2;
	if ( 1== $encode_html ) {
		$content = esc_html($content);
		$cut = 0;
	} elseif ( 0 == $encode_html ) {
		$content = make_url_footnote($content);
	} elseif ( 2 == $encode_html ) {
		$content = strip_tags($content);
	}
	if ( $cut ) {
		$blah = explode(' ', $content);
		if ( count($blah) > $cut ) {
			$k = $cut;
			$use_dotdotdot = 1;
		} else {
			$k = count($blah);
			$use_dotdotdot = 0;
		}

		/** @todo Kiểm tra hiệu suất, có thể nhanh hơn khi sử dụng array slice thay thế. */
		for ( $i=0; $i<$k; $i++ )
			$excerpt .= $blah[$i].' ';
		$excerpt .= ($use_dotdotdot) ? '...' : '';
		$content = $excerpt;
	}
	$content = str_replace(']]>', ']]&gt;', $content);
	echo $content;
}

/**
 * Loại bỏ HTML và đặt các liên kết ở cuối nội dung đã loại bỏ.
 *
 * Tìm kiếm tất cả các liên kết, loại bỏ chúng khỏi nội dung, và đặt
 * chúng ở cuối nội dung với các số thứ tự.
 *
 * @since 0.71
 * @deprecated 2.9.0
 *
 * @param string $content Nội dung để lấy liên kết.
 * @return string HTML đã loại bỏ khỏi nội dung với các liên kết ở cuối.
 */
function make_url_footnote( $content ) {
	_deprecated_function( __FUNCTION__, '2.9.0', '' );
	preg_match_all( '/<a(.+?)href=\"(.+?)\"(.*?)>(.+?)<\/a>/', $content, $matches );
	$links_summary = "\n";
	for ( $i = 0, $c = count( $matches[0] ); $i < $c; $i++ ) {
		$link_match = $matches[0][$i];
		$link_number = '['.($i+1).']';
		$link_url = $matches[2][$i];
		$link_text = $matches[4][$i];
		$content = str_replace( $link_match, $link_text . ' ' . $link_number, $content );
		$link_url = ( ( strtolower( substr( $link_url, 0, 7 ) ) !== 'http://' ) && ( strtolower( substr( $link_url, 0, 8 ) ) !== 'https://' ) ) ? get_option( 'home' ) . $link_url : $link_url;
		$links_summary .= "\n" . $link_number . ' ' . $link_url;
	}
	$content  = strip_tags( $content );
	$content .= $links_summary;
	return $content;
}

/**
 * Lấy chuỗi đã dịch với ngữ cảnh dấu gạch đứng
 *
 * Khá nhiều lần, sẽ có xung đột với văn bản có thể dịch tương tự
 * được tìm thấy ở nhiều hơn hai nơi nhưng với ngữ cảnh dịch khác nhau.
 *
 * Để sử dụng các ngữ cảnh riêng biệt, hàm _c() được sử dụng và
 * chuỗi có thể dịch sử dụng dấu gạch đứng ('|') chứa ngữ cảnh của chuỗi.
 *
 * Khi chuỗi đã dịch được trả về, đó là mọi thứ trước dấu gạch đứng, không
 * bao gồm ký tự dấu gạch đứng. Nếu không có dấu gạch đứng trong văn bản đã dịch thì
 * mọi thứ được trả về.
 *
 * @since 2.2.0
 * @deprecated 2.9.0 Use _x()
 * @see _x()
 *
 * @param string $text Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền để lấy văn bản đã dịch.
 * @return string Chuỗi ngữ cảnh đã dịch không có dấu gạch đứng.
 */
function _c( $text, $domain = 'default' ) {
	_deprecated_function( __FUNCTION__, '2.9.0', '_x()' );
	return before_last_bar( translate( $text, $domain ) );
}

/**
 * Dịch $text như translate(), nhưng giả định rằng văn bản
 * chứa ngữ cảnh sau dấu gạch đứng cuối cùng.
 *
 * @since 2.5.0
 * @deprecated 3.0.0 Use _x()
 * @see _x()
 *
 * @param string $text Văn bản cần dịch.
 * @param string $domain Miền để lấy văn bản đã dịch.
 * @return string Văn bản đã dịch.
 */
function translate_with_context( $text, $domain = 'default' ) {
	_deprecated_function( __FUNCTION__, '2.9.0', '_x()' );
	return before_last_bar( translate( $text, $domain ) );
}

/**
 * Phiên bản cũ của _n(), hỗ trợ ngữ cảnh.
 *
 * Loại bỏ mọi thứ từ bản dịch sau dấu gạch đứng cuối cùng.
 *
 * @since 2.7.0
 * @deprecated 3.0.0 Use _nx()
 * @see _nx()
 *
 * @param string $single Văn bản được sử dụng nếu số lượng là số ít.
 * @param string $plural Văn bản được sử dụng nếu số lượng là số nhiều.
 * @param int    $number Số để so sánh để sử dụng dạng số ít hoặc số nhiều.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Dạng số ít hoặc số nhiều đã dịch.
 */
function _nc( $single, $plural, $number, $domain = 'default' ) {
	_deprecated_function( __FUNCTION__, '2.9.0', '_nx()' );
	return before_last_bar( _n( $single, $plural, $number, $domain ) );
}

/**
 * Lấy dạng số nhiều hoặc số ít dựa trên số lượng.
 *
 * @since 1.2.0
 * @deprecated 2.8.0 Use _n()
 * @see _n()
 */
function __ngettext( ...$args ) { // phpcs:ignore PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	_deprecated_function( __FUNCTION__, '2.8.0', '_n()' );
	return _n( ...$args );
}

/**
 * Đăng ký chuỗi số nhiều trong tệp POT, nhưng không dịch chúng.
 *
 * @since 2.5.0
 * @deprecated 2.8.0 Use _n_noop()
 * @see _n_noop()
 */
function __ngettext_noop( ...$args ) { // phpcs:ignore PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	_deprecated_function( __FUNCTION__, '2.8.0', '_n_noop()' );
	return _n_noop( ...$args );

}

/**
 * Lấy tất cả tùy chọn tự động tải, hoặc tất cả tùy chọn nếu không có tùy chọn tự động tải nào.
 *
 * @since 1.0.0
 * @deprecated 3.0.0 Use wp_load_alloptions())
 * @see wp_load_alloptions()
 *
 * @return array Danh sách tất cả tùy chọn.
 */
function get_alloptions() {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_load_alloptions()' );
	return wp_load_alloptions();
}

/**
 * Lấy nội dung HTML của hình ảnh đính kèm có liên kết.
 *
 * @since 2.0.0
 * @deprecated 2.5.0 Use wp_get_attachment_link()
 * @see wp_get_attachment_link()
 *
 * @param int   $id       Tùy chọn. ID bài viết.
 * @param bool  $fullsize Tùy chọn. Có sử dụng hình ảnh kích thước đầy đủ không. Mặc định false.
 * @param array $max_dims Tùy chọn. Kích thước hình ảnh tối đa.
 * @param bool $permalink Tùy chọn. Có bao gồm đường dẫn tĩnh đến hình ảnh không. Mặc định false.
 * @return string
 */
function get_the_attachment_link($id = 0, $fullsize = false, $max_dims = false, $permalink = false) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'wp_get_attachment_link()' );
	$id = (int) $id;
	$_post = get_post($id);

	if ( ('attachment' != $_post->post_type) || !$url = wp_get_attachment_url($_post->ID) )
		return __('Missing Attachment');

	if ( $permalink )
		$url = get_attachment_link($_post->ID);

	$post_title = esc_attr($_post->post_title);

	$innerHTML = get_attachment_innerHTML($_post->ID, $fullsize, $max_dims);
	return "<a href='$url' title='$post_title'>$innerHTML</a>";
}

/**
 * Lấy URL và đường dẫn biểu tượng.
 *
 * @since 2.1.0
 * @deprecated 2.5.0 Use wp_get_attachment_image_src()
 * @see wp_get_attachment_image_src()
 *
 * @param int  $id       Tùy chọn. ID bài viết.
 * @param bool $fullsize Tùy chọn. Có lấy hình ảnh kích thước đầy đủ không. Mặc định false.
 * @return array URL biểu tượng và đường dẫn đầy đủ đến tệp, tương ứng.
 */
function get_attachment_icon_src( $id = 0, $fullsize = false ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'wp_get_attachment_image_src()' );
	$id = (int) $id;
	if ( !$post = get_post($id) )
		return false;

	$file = get_attached_file( $post->ID );

	if ( !$fullsize && $src = wp_get_attachment_thumb_url( $post->ID ) ) {
		// Chúng ta có ảnh thu nhỏ mong muốn, được chỉ định và tồn tại.

		$src_file = wp_basename($src);
	} elseif ( wp_attachment_is_image( $post->ID ) ) {
		// Chúng ta có hình ảnh không có ảnh thu nhỏ.

		$src = wp_get_attachment_url( $post->ID );
		$src_file = & $file;
	} elseif ( $src = wp_mime_type_icon( $post->ID, '.svg' ) ) {
		// Không có ảnh thu nhỏ, không có hình ảnh. Chúng ta sẽ tìm biểu tượng liên quan đến mime thay thế.

		/** Bộ lọc này được ghi nhận trong wp-includes/post.php */
		$icon_dir = apply_filters( 'icon_dir', get_template_directory() . '/images' );
		$src_file = $icon_dir . '/' . wp_basename($src);
	}

	if ( !isset($src) || !$src )
		return false;

	return array($src, $src_file);
}

/**
 * Lấy nội dung HTML của phần tử hình ảnh biểu tượng đính kèm.
 *
 * @since 2.0.0
 * @deprecated 2.5.0 Use wp_get_attachment_image()
 * @see wp_get_attachment_image()
 *
 * @param int   $id       Tùy chọn. ID bài viết.
 * @param bool  $fullsize Tùy chọn. Có lấy hình ảnh kích thước đầy đủ không. Mặc định false.
 * @param array $max_dims Tùy chọn. Kích thước của hình ảnh.
 * @return string|false Nội dung HTML.
 */
function get_attachment_icon( $id = 0, $fullsize = false, $max_dims = false ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'wp_get_attachment_image()' );
	$id = (int) $id;
	if ( !$post = get_post($id) )
		return false;

	if ( !$src = get_attachment_icon_src( $post->ID, $fullsize ) )
		return false;

	list($src, $src_file) = $src;

	// Chúng ta có cần giới hạn kích thước hình ảnh không?
	if ( ($max_dims = apply_filters('attachment_max_dims', $max_dims)) && file_exists($src_file) ) {

		$imagesize = wp_getimagesize($src_file);

		if (($imagesize[0] > $max_dims[0]) || $imagesize[1] > $max_dims[1] ) {
			$actual_aspect = $imagesize[0] / $imagesize[1];
			$desired_aspect = $max_dims[0] / $max_dims[1];

			if ( $actual_aspect >= $desired_aspect ) {
				$height = $actual_aspect * $max_dims[0];
				$constraint = "width='{$max_dims[0]}' ";
				$post->iconsize = array($max_dims[0], $height);
			} else {
				$width = $max_dims[1] / $actual_aspect;
				$constraint = "height='{$max_dims[1]}' ";
				$post->iconsize = array($width, $max_dims[1]);
			}
		} else {
			$post->iconsize = array($imagesize[0], $imagesize[1]);
			$constraint = '';
		}
	} else {
		$constraint = '';
	}

	$post_title = esc_attr($post->post_title);

	$icon = "<img src='$src' title='$post_title' alt='$post_title' $constraint/>";

	return apply_filters( 'attachment_icon', $icon, $post->ID );
}

/**
 * Lấy nội dung HTML của phần tử hình ảnh.
 *
 * @since 2.0.0
 * @deprecated 2.5.0 Use wp_get_attachment_image()
 * @see wp_get_attachment_image()
 *
 * @param int   $id       Tùy chọn. ID bài viết.
 * @param bool  $fullsize Tùy chọn. Có lấy hình ảnh kích thước đầy đủ không. Mặc định false.
 * @param array $max_dims Tùy chọn. Kích thước của hình ảnh.
 * @return string|false
 */
function get_attachment_innerHTML($id = 0, $fullsize = false, $max_dims = false) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'wp_get_attachment_image()' );
	$id = (int) $id;
	if ( !$post = get_post($id) )
		return false;

	if ( $innerHTML = get_attachment_icon($post->ID, $fullsize, $max_dims))
		return $innerHTML;

	$innerHTML = esc_attr($post->post_title);

	return apply_filters('attachment_innerHTML', $innerHTML, $post->ID);
}

/**
 * Lấy dữ liệu dấu trang dựa trên ID.
 *
 * @since 2.0.0
 * @deprecated 2.1.0 Use get_bookmark()
 * @see get_bookmark()
 *
 * @param int    $bookmark_id ID của liên kết
 * @param string $output      Tùy chọn. Loại đầu ra. Chấp nhận OBJECT, ARRAY_N, hoặc ARRAY_A.
 *                            Mặc định OBJECT.
 * @param string $filter      Tùy chọn. Cách lọc liên kết cho đầu ra. Chấp nhận 'raw', 'edit',
 *                            'attribute', 'js', 'db', hoặc 'display'. Mặc định 'raw'.
 * @return object|array Đối tượng hoặc mảng dấu trang, tùy thuộc vào loại được chỉ định bởi `$output`.
 */
function get_link( $bookmark_id, $output = OBJECT, $filter = 'raw' ) {
	_deprecated_function( __FUNCTION__, '2.1.0', 'get_bookmark()' );
	return get_bookmark($bookmark_id, $output, $filter);
}

/**
 * Kiểm tra và làm sạch URL.
 *
 * Một số ký tự được xóa khỏi URL. Nếu URL dùng để hiển thị
 * (hành vi mặc định) thì các ký tự ampersand cũng được thay thế. Bộ lọc 'clean_url'
 * được áp dụng cho URL đã làm sạch được trả về.
 *
 * @since 1.2.0
 * @deprecated 3.0.0 Use esc_url()
 * @see esc_url()
 *
 * @param string $url URL cần làm sạch.
 * @param array $protocols Tùy chọn. Mảng các giao thức được chấp nhận.
 * @param string $context Tùy chọn. URL sẽ được sử dụng như thế nào. Mặc định 'display'.
 * @return string URL $url đã làm sạch sau khi bộ lọc {@see 'clean_url'} được áp dụng.
 */
function clean_url( $url, $protocols = null, $context = 'display' ) {
	if ( $context == 'db' )
		_deprecated_function( 'clean_url( $context = \'db\' )', '3.0.0', 'sanitize_url()' );
	else
		_deprecated_function( __FUNCTION__, '3.0.0', 'esc_url()' );
	return esc_url( $url, $protocols, $context );
}

/**
 * Thoát dấu nháy đơn, ký tự đặc biệt dấu nháy kép, và sửa kết thúc dòng.
 *
 * Bộ lọc {@see 'js_escape'} cũng được áp dụng bởi esc_js().
 *
 * @since 2.0.4
 * @deprecated 2.8.0 Use esc_js()
 * @see esc_js()
 *
 * @param string $text Văn bản cần thoát.
 * @return string Văn bản đã thoát.
 */
function js_escape( $text ) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'esc_js()' );
	return esc_js( $text );
}

/**
 * Thoát cũ cho các khối HTML.
 *
 * @deprecated 2.8.0 Use esc_html()
 * @see esc_html()
 *
 * @param string       $text          Văn bản cần thoát.
 * @param string       $quote_style   Không sử dụng.
 * @param false|string $charset       Không sử dụng.
 * @param false        $double_encode Có mã hóa kép không. Không sử dụng.
 * @return string `$text` đã thoát.
 */
function wp_specialchars( $text, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false ) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'esc_html()' );
	if ( func_num_args() > 1 ) { // Duy trì tương thích ngược cho những người truyền thêm đối số.
		return _wp_specialchars( $text, $quote_style, $charset, $double_encode );
	} else {
		return esc_html( $text );
	}
}

/**
 * Thoát cho thuộc tính HTML.
 *
 * @since 2.0.6
 * @deprecated 2.8.0 Use esc_attr()
 * @see esc_attr()
 *
 * @param string $text
 * @return string
 */
function attribute_escape( $text ) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'esc_attr()' );
	return esc_attr( $text );
}

/**
 * Đăng ký widget cho thanh bên với tương thích ngược.
 *
 * Cho phép $name là mảng chấp nhận ba phần tử để lấy
 * phần tử đầu tiên và phần tử thứ ba cho tên hoặc chỉ sử dụng phần tử đầu tiên của
 * mảng cho tên.
 *
 * Chuyển đến wp_register_sidebar_widget() sau khi danh sách đối số và tương thích
 * ngược hoàn tất.
 *
 * @since 2.2.0
 * @deprecated 2.8.0 Use wp_register_sidebar_widget()
 * @see wp_register_sidebar_widget()
 *
 * @param string|int $name            ID widget.
 * @param callable   $output_callback Chạy khi widget được gọi.
 * @param string     $classname       Tùy chọn. Tùy chọn classname widget. Mặc định rỗng.
 * @param mixed      ...$params       Các tham số widget.
 */
function register_sidebar_widget($name, $output_callback, $classname = '', ...$params) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'wp_register_sidebar_widget()' );
	// Tương thích.
	if ( is_array( $name ) ) {
		if ( count( $name ) === 3 ) {
			$name = sprintf( $name[0], $name[2] );
		} else {
			$name = $name[0];
		}
	}

	$id      = sanitize_title( $name );
	$options = array();
	if ( ! empty( $classname ) && is_string( $classname ) ) {
		$options['classname'] = $classname;
	}

	wp_register_sidebar_widget( $id, $name, $output_callback, $options, ...$params );
}

/**
 * Phục vụ như bí danh của wp_unregister_sidebar_widget().
 *
 * @since 2.2.0
 * @deprecated 2.8.0 Use wp_unregister_sidebar_widget()
 * @see wp_unregister_sidebar_widget()
 *
 * @param int|string $id ID widget.
 */
function unregister_sidebar_widget($id) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'wp_unregister_sidebar_widget()' );
	return wp_unregister_sidebar_widget($id);
}

/**
 * Đăng ký callback điều khiển widget để tùy chỉnh tùy chọn.
 *
 * Cho phép $name là mảng chấp nhận ba phần tử để lấy
 * phần tử đầu tiên và phần tử thứ ba cho tên hoặc chỉ sử dụng phần tử đầu tiên của
 * mảng cho tên.
 *
 * Chuyển đến wp_register_widget_control() sau khi danh sách đối số
 * được biên soạn xong.
 *
 * @since 2.2.0
 * @deprecated 2.8.0 Use wp_register_widget_control()
 * @see wp_register_widget_control()
 *
 * @param int|string $name             ID thanh bên.
 * @param callable   $control_callback Callback điều khiển widget để hiển thị và xử lý biểu mẫu.
 * @param int        $width            Chiều rộng widget.
 * @param int        $height           Chiều cao widget.
 * @param mixed      ...$params        Các tham số widget.
 */
function register_widget_control($name, $control_callback, $width = '', $height = '', ...$params) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'wp_register_widget_control()' );
	// Tương thích.
	if ( is_array( $name ) ) {
		if ( count( $name ) === 3 ) {
			$name = sprintf( $name[0], $name[2] );
		} else {
			$name = $name[0];
		}
	}

	$id      = sanitize_title( $name );
	$options = array();
	if ( ! empty( $width ) ) {
		$options['width'] = $width;
	}
	if ( ! empty( $height ) ) {
		$options['height'] = $height;
	}

	wp_register_widget_control( $id, $name, $control_callback, $options, ...$params );
}

/**
 * Bí danh của wp_unregister_widget_control().
 *
 * @since 2.2.0
 * @deprecated 2.8.0 Use wp_unregister_widget_control()
 * @see wp_unregister_widget_control()
 *
 * @param int|string $id ID widget.
 */
function unregister_widget_control($id) {
	_deprecated_function( __FUNCTION__, '2.8.0', 'wp_unregister_widget_control()' );
	return wp_unregister_widget_control($id);
}

/**
 * Xóa dữ liệu meta người dùng.
 *
 * @since 2.0.0
 * @deprecated 3.0.0 Use delete_user_meta()
 * @see delete_user_meta()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int $user_id ID người dùng.
 * @param string $meta_key Khóa metadata.
 * @param mixed $meta_value Tùy chọn. Giá trị metadata. Mặc định rỗng.
 * @return bool True nếu xóa hoàn tất và false nếu user_id không phải số.
 */
function delete_usermeta( $user_id, $meta_key, $meta_value = '' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'delete_user_meta()' );
	global $wpdb;
	if ( !is_numeric( $user_id ) )
		return false;
	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);
	$meta_value = trim( $meta_value );

	$cur = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key) );

	if ( $cur && $cur->umeta_id )
		do_action( 'delete_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value );

	if ( ! empty($meta_value) )
		$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s AND meta_value = %s", $user_id, $meta_key, $meta_value) );
	else
		$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key) );

	clean_user_cache( $user_id );
	wp_cache_delete( $user_id, 'user_meta' );

	if ( $cur && $cur->umeta_id )
		do_action( 'deleted_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value );

	return true;
}

/**
 * Lấy metadata người dùng.
 *
 * Nếu $user_id không phải là số, thì hàm sẽ trả về giá trị boolean 'false'.
 * Các giá trị trả về khác phụ thuộc vào việc chỉ có một mục để trả về,
 * sẽ là loại mục đơn đó. Nếu có nhiều hơn một giá trị metadata,
 * thì đó sẽ là danh sách các giá trị metadata.
 *
 * @since 2.0.0
 * @deprecated 3.0.0 Use get_user_meta()
 * @see get_user_meta()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int $user_id ID người dùng
 * @param string $meta_key Tùy chọn. Khóa metadata. Mặc định rỗng.
 * @return mixed
 */
function get_usermeta( $user_id, $meta_key = '' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'get_user_meta()' );
	global $wpdb;
	$user_id = (int) $user_id;

	if ( !$user_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);
		$user = wp_cache_get($user_id, 'users');
		// Kiểm tra đối tượng người dùng đã được lưu trong bộ nhớ đệm.
		if ( false !== $user && isset($user->$meta_key) )
			$metas = array($user->$meta_key);
		else
			$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key) );
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d", $user_id) );
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	$metas = array_map('maybe_unserialize', $metas);

	if ( count($metas) === 1 )
		return $metas[0];
	else
		return $metas;
}

/**
 * Cập nhật metadata của người dùng.
 *
 * Không cần serialize giá trị, chúng sẽ được serialize nếu cần thiết.
 * Khóa metadata chỉ có thể là chuỗi với dấu gạch dưới. Tất cả những thứ khác sẽ
 * bị xóa bỏ.
 *
 * Sẽ xóa metadata, nếu giá trị meta rỗng.
 *
 * @since 2.0.0
 * @deprecated 3.0.0 Use update_user_meta()
 * @see update_user_meta()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int $user_id ID người dùng
 * @param string $meta_key Khóa metadata.
 * @param mixed $meta_value Giá trị metadata.
 * @return bool True nếu cập nhật thành công, false nếu thất bại.
 */
function update_usermeta( $user_id, $meta_key, $meta_value ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'update_user_meta()' );
	global $wpdb;
	if ( !is_numeric( $user_id ) )
		return false;
	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	/** @todo Có thể cần sửa vì dữ liệu usermeta được giả định là đã được thoát */
	if ( is_string($meta_value) )
		$meta_value = stripslashes($meta_value);
	$meta_value = maybe_serialize($meta_value);

	if (empty($meta_value)) {
		return delete_usermeta($user_id, $meta_key);
	}

	$cur = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key) );

	if ( $cur )
		do_action( 'update_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value );

	if ( !$cur )
		$wpdb->insert($wpdb->usermeta, compact('user_id', 'meta_key', 'meta_value') );
	elseif ( $cur->meta_value != $meta_value )
		$wpdb->update($wpdb->usermeta, compact('meta_value'), compact('user_id', 'meta_key') );
	else
		return false;

	clean_user_cache( $user_id );
	wp_cache_delete( $user_id, 'user_meta' );

	if ( !$cur )
		do_action( 'added_usermeta', $wpdb->insert_id, $user_id, $meta_key, $meta_value );
	else
		do_action( 'updated_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value );

	return true;
}

/**
 * Lấy người dùng cho trang web.
 *
 * Dành cho các cài đặt sử dụng tính năng đa trang. Có thể sử dụng ngoài
 * tính năng đa trang.
 *
 * @since 2.2.0
 * @deprecated 3.1.0 Use get_users()
 * @see get_users()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int $id ID trang web.
 * @return array Danh sách người dùng thuộc ID trang web đó
 */
function get_users_of_blog( $id = '' ) {
	_deprecated_function( __FUNCTION__, '3.1.0', 'get_users()' );

	global $wpdb;
	if ( empty( $id ) ) {
		$id = get_current_blog_id();
	}
	$blog_prefix = $wpdb->get_blog_prefix($id);
	$users = $wpdb->get_results( "SELECT user_id, user_id AS ID, user_login, display_name, user_email, meta_value FROM $wpdb->users, $wpdb->usermeta WHERE {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY {$wpdb->usermeta}.user_id" );
	return $users;
}

/**
 * Bật/tắt xuất liên kết nguồn cấp dữ liệu chung tự động.
 *
 * @since 2.8.0
 * @deprecated 3.0.0 Use add_theme_support()
 * @see add_theme_support()
 *
 * @param bool $add Tùy chọn. Thêm hoặc xóa liên kết. Mặc định true.
 */
function automatic_feed_links( $add = true ) {
	_deprecated_function( __FUNCTION__, '3.0.0', "add_theme_support( 'automatic-feed-links' )" );

	if ( $add )
		add_theme_support( 'automatic-feed-links' );
	else
		remove_action( 'wp_head', 'feed_links_extra', 3 ); // Hãy tự làm điều này trong phiên bản 3.0+.
}

/**
 * Lấy dữ liệu người dùng dựa trên trường.
 *
 * @since 1.5.0
 * @deprecated 3.0.0 Use get_the_author_meta()
 * @see get_the_author_meta()
 *
 * @param string    $field Trường meta người dùng.
 * @param false|int $user  Tùy chọn. ID người dùng để lấy trường. Mặc định false (người dùng hiện tại).
 * @return string Trường của tác giả từ đối tượng DB của tác giả hiện tại.
 */
function get_profile( $field, $user = false ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'get_the_author_meta()' );
	if ( $user ) {
		$user = get_user_by( 'login', $user );
		$user = $user->ID;
	}
	return get_the_author_meta( $field, $user );
}

/**
 * Lấy số lượng bài viết mà người dùng đã viết.
 *
 * @since 0.71
 * @deprecated 3.0.0 Use count_user_posts()
 * @see count_user_posts()
 *
 * @param int $userid Người dùng để đếm bài viết.
 * @return int Số lượng bài viết mà người dùng đã viết.
 */
function get_usernumposts( $userid ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'count_user_posts()' );
	return count_user_posts( $userid );
}

/**
 * Callback dùng để chuyển đổi cú pháp %uXXXX sang &#YYY;
 *
 * @since 2.8.0
 * @access private
 * @deprecated 3.0.0
 *
 * @param array $matches Kết quả khớp đơn
 * @return string Một thực thể HTML
 */
function funky_javascript_callback($matches) {
	return "&#".base_convert($matches[1],16,10).";";
}

/**
 * Sửa lỗi JavaScript trong các trình duyệt.
 *
 * Chuyển đổi các ký tự unicode thành thực thể HTML có đánh số.
 *
 * @since 1.5.0
 * @deprecated 3.0.0
 *
 * @global $is_macIE
 * @global $is_winIE
 *
 * @param string $text Văn bản cần làm an toàn.
 * @return string Văn bản đã sửa.
 */
function funky_javascript_fix($text) {
	_deprecated_function( __FUNCTION__, '3.0.0' );
	// Fixes for browsers' JavaScript bugs.
	global $is_macIE, $is_winIE;

	if ( $is_winIE || $is_macIE )
		$text =  preg_replace_callback("/\%u([0-9A-F]{4,4})/",
					"funky_javascript_callback",
					$text);

	return $text;
}

/**
 * Kiểm tra tên taxonomy có tồn tại không.
 *
 * @since 2.3.0
 * @deprecated 3.0.0 Use taxonomy_exists()
 * @see taxonomy_exists()
 *
 * @param string $taxonomy Tên đối tượng taxonomy
 * @return bool Taxonomy có tồn tại hay không.
 */
function is_taxonomy( $taxonomy ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'taxonomy_exists()' );
	return taxonomy_exists( $taxonomy );
}

/**
 * Kiểm tra xem Term có tồn tại không.
 *
 * @since 2.3.0
 * @deprecated 3.0.0 Use term_exists()
 * @see term_exists()
 *
 * @param int|string $term Term cần kiểm tra
 * @param string $taxonomy Tên taxonomy cần sử dụng
 * @param int $parent ID của term cha để giới hạn phạm vi tìm kiếm.
 * @return mixed Lấy ID hoặc đối tượng term, nếu tồn tại.
 */
function is_term( $term, $taxonomy = '', $parent = 0 ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'term_exists()' );
	return term_exists( $term, $taxonomy, $parent );
}

/**
 * Xác định xem trang quản trị hiện tại có được tạo bởi plugin không.
 *
 * Sử dụng biến toàn cục $plugin_page và/hoặc các hook get_plugin_page_hookname().
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 1.5.0
 * @deprecated 3.1.0
 *
 * @global $plugin_page
 *
 * @return bool
 */
function is_plugin_page() {
	_deprecated_function( __FUNCTION__, '3.1.0' );

	global $plugin_page;

	if ( isset($plugin_page) )
		return true;

	return false;
}

/**
 * Cập nhật bộ nhớ đệm chuyên mục.
 *
 * Hàm này dường như không còn được sử dụng hoặc không cần thiết nữa.
 * Có thể là hàm cũ còn sót lại từ khi cần cập nhật
 * bộ nhớ đệm chuyên mục.
 *
 * @since 1.5.0
 * @deprecated 3.1.0
 *
 * @return bool Luôn trả về True
 */
function update_category_cache() {
	_deprecated_function( __FUNCTION__, '3.1.0' );

	return true;
}

/**
 * Kiểm tra hỗ trợ múi giờ PHP
 *
 * @since 2.9.0
 * @deprecated 3.2.0
 *
 * @return bool
 */
function wp_timezone_supported() {
	_deprecated_function( __FUNCTION__, '3.2.0' );

	return true;
}

/**
 * Hiển thị trình soạn thảo: TinyMCE, HTML, hoặc cả hai.
 *
 * @since 2.1.0
 * @deprecated 3.3.0 Use wp_editor()
 * @see wp_editor()
 *
 * @param string $content       Nội dung textarea.
 * @param string $id            Tùy chọn. Giá trị thuộc tính ID HTML. Mặc định 'content'.
 * @param string $prev_id       Tùy chọn. Không sử dụng.
 * @param bool   $media_buttons Tùy chọn. Có hiển thị các nút media không. Mặc định true.
 * @param int    $tab_index     Tùy chọn. Không sử dụng.
 * @param bool   $extended      Tùy chọn. Không sử dụng.
 */
function the_editor($content, $id = 'content', $prev_id = 'title', $media_buttons = true, $tab_index = 2, $extended = true) {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_editor()' );

	wp_editor( $content, $id, array( 'media_buttons' => $media_buttons ) );
}

/**
 * Thực hiện truy vấn để lấy mảng $metavalues cần thiết cho _fill_user và _fill_many_users
 *
 * @since 3.0.0
 * @deprecated 3.3.0
 *
 * @param array $ids Danh sách số ID người dùng.
 * @return array Mảng các mảng. Mảng được đánh chỉ mục theo user_id, chứa các mảng đối tượng $metavalues.
 */
function get_user_metavalues($ids) {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	$objects = array();

	$ids = array_map('intval', $ids);
	foreach ( $ids as $id )
		$objects[$id] = array();

	$metas = update_meta_cache('user', $ids);

	foreach ( $metas as $id => $meta ) {
		foreach ( $meta as $key => $metavalues ) {
			foreach ( $metavalues as $value ) {
				$objects[$id][] = (object)array( 'user_id' => $id, 'meta_key' => $key, 'meta_value' => $value);
			}
		}
	}

	return $objects;
}

/**
 * Làm sạch mọi trường người dùng.
 *
 * Nếu ngữ cảnh là 'raw', đối tượng hoặc mảng người dùng sẽ được làm sạch tối thiểu cho các trường int.
 *
 * @since 2.3.0
 * @deprecated 3.3.0
 *
 * @param object|array $user    Đối tượng hoặc mảng người dùng.
 * @param string       $context Tùy chọn. Cách làm sạch các trường người dùng. Mặc định 'display'.
 * @return object|array Đối tượng hoặc mảng người dùng đã được làm sạch (cùng kiểu với $user).
 */
function sanitize_user_object($user, $context = 'display') {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	if ( is_object($user) ) {
		if ( !isset($user->ID) )
			$user->ID = 0;
		if ( ! ( $user instanceof WP_User ) ) {
			$vars = get_object_vars($user);
			foreach ( array_keys($vars) as $field ) {
				if ( is_string($user->$field) || is_numeric($user->$field) )
					$user->$field = sanitize_user_field($field, $user->$field, $user->ID, $context);
			}
		}
		$user->filter = $context;
	} else {
		if ( !isset($user['ID']) )
			$user['ID'] = 0;
		foreach ( array_keys($user) as $field )
			$user[$field] = sanitize_user_field($field, $user[$field], $user['ID'], $context);
		$user['filter'] = $context;
	}

	return $user;
}

/**
 * Lấy liên kết quan hệ bài viết đầu/cuối.
 *
 * Có thể là liên kết quan hệ bài viết đầu hoặc cuối.
 *
 * @since 2.8.0
 * @deprecated 3.3.0
 *
 * @param string $title               Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 * @param bool   $in_same_cat         Tùy chọn. Liên kết có nên cùng chuyên mục không.
 *                                    Mặc định false.
 * @param string $excluded_categories Tùy chọn. ID các chuyên mục bị loại trừ. Mặc định rỗng.
 * @param bool   $start               Tùy chọn. Hiển thị liên kết đến bài viết đầu tiên hay cuối cùng.
 *                                    Mặc định true.
 * @return string
 */
function get_boundary_post_rel_link($title = '%title', $in_same_cat = false, $excluded_categories = '', $start = true) {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	$posts = get_boundary_post($in_same_cat, $excluded_categories, $start);
	// Nếu không có bài viết, dừng lại.
	if ( empty($posts) )
		return;

	// Mặc dù giới hạn get_posts() chỉ trả về 1 mục nhưng nó vẫn trả về mảng các đối tượng.
	$post = $posts[0];

	if ( empty($post->post_title) )
		$post->post_title = $start ? __('First Post') : __('Last Post');

	$date = mysql2date(get_option('date_format'), $post->post_date);

	$title = str_replace('%title', $post->post_title, $title);
	$title = str_replace('%date', $date, $title);
	$title = apply_filters('the_title', $title, $post->ID);

	$link = $start ? "<link rel='start' title='" : "<link rel='end' title='";
	$link .= esc_attr($title);
	$link .= "' href='" . get_permalink($post) . "' />\n";

	$boundary = $start ? 'start' : 'end';
	return apply_filters( "{$boundary}_post_rel_link", $link );
}

/**
 * Hiển thị liên kết quan hệ cho bài viết đầu tiên.
 *
 * @since 2.8.0
 * @deprecated 3.3.0
 *
 * @param string $title Tùy chọn. Định dạng tiêu đề liên kết.
 * @param bool $in_same_cat Tùy chọn. Liên kết có nên cùng chuyên mục không.
 * @param string $excluded_categories Tùy chọn. ID các chuyên mục bị loại trừ.
 */
function start_post_rel_link($title = '%title', $in_same_cat = false, $excluded_categories = '') {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	echo get_boundary_post_rel_link($title, $in_same_cat, $excluded_categories, true);
}

/**
 * Lấy liên kết quan hệ trang chủ.
 *
 * @since 2.8.0
 * @deprecated 3.3.0
 *
 * @return string
 */
function get_index_rel_link() {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	$link = "<link rel='index' title='" . esc_attr( get_bloginfo( 'name', 'display' ) ) . "' href='" . esc_url( user_trailingslashit( get_bloginfo( 'url', 'display' ) ) ) . "' />\n";
	return apply_filters( "index_rel_link", $link );
}

/**
 * Hiển thị liên kết quan hệ cho trang chủ.
 *
 * @since 2.8.0
 * @deprecated 3.3.0
 */
function index_rel_link() {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	echo get_index_rel_link();
}

/**
 * Lấy liên kết quan hệ bài viết cha.
 *
 * @since 2.8.0
 * @deprecated 3.3.0
 *
 * @global WP_Post $post Đối tượng bài viết toàn cục.
 *
 * @param string $title Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 * @return string
 */
function get_parent_post_rel_link( $title = '%title' ) {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	if ( ! empty( $GLOBALS['post'] ) && ! empty( $GLOBALS['post']->post_parent ) )
		$post = get_post($GLOBALS['post']->post_parent);

	if ( empty($post) )
		return;

	$date = mysql2date(get_option('date_format'), $post->post_date);

	$title = str_replace('%title', $post->post_title, $title);
	$title = str_replace('%date', $date, $title);
	$title = apply_filters('the_title', $title, $post->ID);

	$link = "<link rel='up' title='";
	$link .= esc_attr( $title );
	$link .= "' href='" . get_permalink($post) . "' />\n";

	return apply_filters( "parent_post_rel_link", $link );
}

/**
 * Hiển thị liên kết quan hệ cho mục cha
 *
 * @since 2.8.0
 * @deprecated 3.3.0
 *
 * @param string $title Tùy chọn. Định dạng tiêu đề liên kết. Mặc định '%title'.
 */
function parent_post_rel_link( $title = '%title' ) {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	echo get_parent_post_rel_link($title);
}

/**
 * Thêm menu "Bảng điều khiển"/"Xem trang web".
 *
 * @since 3.2.0
 * @deprecated 3.3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar Thể hiện WP_Admin_Bar.
 */
function wp_admin_bar_dashboard_view_site_menu( $wp_admin_bar ) {
	_deprecated_function( __FUNCTION__, '3.3.0' );

	$user_id = get_current_user_id();

	if ( 0 != $user_id ) {
		if ( is_admin() )
			$wp_admin_bar->add_menu( array( 'id' => 'view-site', 'title' => __( 'Visit Site' ), 'href' => home_url() ) );
		elseif ( is_multisite() )
			$wp_admin_bar->add_menu( array( 'id' => 'dashboard', 'title' => __( 'Dashboard' ), 'href' => get_dashboard_url( $user_id ) ) );
		else
			$wp_admin_bar->add_menu( array( 'id' => 'dashboard', 'title' => __( 'Dashboard' ), 'href' => admin_url() ) );
	}
}

/**
 * Kiểm tra xem người dùng hiện tại có thuộc trang web cho trước không.
 *
 * @since MU (3.0.0)
 * @deprecated 3.3.0 Use is_user_member_of_blog()
 * @see is_user_member_of_blog()
 *
 * @param int $blog_id ID trang web
 * @return bool True nếu người dùng hiện tại thuộc $blog_id, false nếu không.
 */
function is_blog_user( $blog_id = 0 ) {
	_deprecated_function( __FUNCTION__, '3.3.0', 'is_user_member_of_blog()' );

	return is_user_member_of_blog( get_current_user_id(), $blog_id );
}

/**
 * Mở con trỏ tệp để gỡ lỗi.
 *
 * @since 0.71
 * @deprecated 3.4.0 Use error_log()
 * @see error_log()
 *
 * @link https://www.php.net/manual/en/function.error-log.php
 *
 * @param string $filename Tên tệp.
 * @param string $mode     Loại quyền truy cập bạn cần cho luồng.
 * @return false Luôn trả về false.
 */
function debug_fopen( $filename, $mode ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'error_log()' );
	return false;
}

/**
 * Ghi nội dung vào tệp dùng để gỡ lỗi.
 *
 * @since 0.71
 * @deprecated 3.4.0 Use error_log()
 * @see error_log()
 *
 * @link https://www.php.net/manual/en/function.error-log.php
 *
 * @param mixed  $fp      Không sử dụng.
 * @param string $message Thông báo để ghi log.
 */
function debug_fwrite( $fp, $message ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'error_log()' );
	if ( ! empty( $GLOBALS['debug'] ) )
		error_log( $message );
}

/**
 * Đóng con trỏ tệp gỡ lỗi.
 *
 * @since 0.71
 * @deprecated 3.4.0 Use error_log()
 * @see error_log()
 *
 * @link https://www.php.net/manual/en/function.error-log.php
 *
 * @param mixed $fp Không sử dụng.
 */
function debug_fclose( $fp ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'error_log()' );
}

/**
 * Lấy danh sách theme với dữ liệu theme trong thư mục theme.
 *
 * Theme bị lỗi nếu nó không có theme cha và thiếu
 * style.css và/hoặc index.php. Nếu theme có theme cha thì nó
 * bị lỗi nếu thiếu style.css; index.php là tùy chọn.
 *
 * @since 1.5.0
 * @deprecated 3.4.0 Use wp_get_themes()
 * @see wp_get_themes()
 *
 * @return array Danh sách theme với dữ liệu theme.
 */
function get_themes() {
	_deprecated_function( __FUNCTION__, '3.4.0', 'wp_get_themes()' );

	global $wp_themes;
	if ( isset( $wp_themes ) )
		return $wp_themes;

	$themes = wp_get_themes();
	$wp_themes = array();

	foreach ( $themes as $theme ) {
		$name = $theme->get('Name');
		if ( isset( $wp_themes[ $name ] ) )
			$wp_themes[ $name . '/' . $theme->get_stylesheet() ] = $theme;
		else
			$wp_themes[ $name ] = $theme;
	}

	return $wp_themes;
}

/**
 * Lấy dữ liệu theme.
 *
 * @since 1.5.0
 * @deprecated 3.4.0 Use wp_get_theme()
 * @see wp_get_theme()
 *
 * @param string $theme Tên theme.
 * @return array|null Null, nếu tên theme không tồn tại. Dữ liệu theme, nếu tồn tại.
 */
function get_theme( $theme ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'wp_get_theme( $stylesheet )' );

	$themes = get_themes();
	if ( is_array( $themes ) && array_key_exists( $theme, $themes ) )
		return $themes[ $theme ];
	return null;
}

/**
 * Lấy tên theme hiện tại.
 *
 * @since 1.5.0
 * @deprecated 3.4.0 Use wp_get_theme()
 * @see wp_get_theme()
 *
 * @return string
 */
function get_current_theme() {
	_deprecated_function( __FUNCTION__, '3.4.0', 'wp_get_theme()' );

	if ( $theme = get_option( 'current_theme' ) )
		return $theme;

	return wp_get_theme()->get('Name');
}

/**
 * Nhận mảng kết quả khớp từ preg_replace_callback trong wpautop() hoặc chuỗi.
 *
 * Đảm bảo rằng nội dung của khối HTML `<pre>...</pre>` không bị
 * chuyển đổi thành đoạn văn hoặc ngắt dòng.
 *
 * @since 1.2.0
 * @deprecated 3.4.0
 *
 * @param array|string $matches Mảng hoặc chuỗi
 * @return string Khối pre không bị chuyển đổi đoạn văn/ngắt dòng.
 */
function clean_pre($matches) {
	_deprecated_function( __FUNCTION__, '3.4.0' );

	if ( is_array($matches) )
		$text = $matches[1] . $matches[2] . "</pre>";
	else
		$text = $matches;

	$text = str_replace(array('<br />', '<br/>', '<br>'), array('', '', ''), $text);
	$text = str_replace('<p>', "\n", $text);
	$text = str_replace('</p>', '', $text);

	return $text;
}


/**
 * Thêm callback hiển thị ảnh đầu trang.
 *
 * @since 2.1.0
 * @deprecated 3.4.0 Use add_theme_support()
 * @see add_theme_support()
 *
 * @param callable $wp_head_callback Gọi trên action {@see 'wp_head'}.
 * @param callable $admin_head_callback Gọi trên màn hình quản trị đầu trang tùy chỉnh.
 * @param callable $admin_preview_callback Xuất div ảnh đầu trang tùy chỉnh trên màn hình quản trị đầu trang tùy chỉnh. Tùy chọn.
 */
function add_custom_image_header( $wp_head_callback, $admin_head_callback, $admin_preview_callback = '' ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'add_theme_support( \'custom-header\', $args )' );
	$args = array(
		'wp-head-callback'    => $wp_head_callback,
		'admin-head-callback' => $admin_head_callback,
	);
	if ( $admin_preview_callback )
		$args['admin-preview-callback'] = $admin_preview_callback;
	return add_theme_support( 'custom-header', $args );
}

/**
 * Xóa hỗ trợ ảnh đầu trang.
 *
 * @since 3.1.0
 * @deprecated 3.4.0 Use remove_theme_support()
 * @see remove_theme_support()
 *
 * @return null|bool Hỗ trợ có được xóa hay không.
 */
function remove_custom_image_header() {
	_deprecated_function( __FUNCTION__, '3.4.0', 'remove_theme_support( \'custom-header\' )' );
	return remove_theme_support( 'custom-header' );
}

/**
 * Thêm callback hiển thị ảnh nền.
 *
 * @since 3.0.0
 * @deprecated 3.4.0 Use add_theme_support()
 * @see add_theme_support()
 *
 * @param callable $wp_head_callback Gọi trên action {@see 'wp_head'}.
 * @param callable $admin_head_callback Gọi trên màn hình quản trị nền tùy chỉnh.
 * @param callable $admin_preview_callback Xuất div ảnh nền tùy chỉnh trên màn hình quản trị nền tùy chỉnh. Tùy chọn.
 */
function add_custom_background( $wp_head_callback = '', $admin_head_callback = '', $admin_preview_callback = '' ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'add_theme_support( \'custom-background\', $args )' );
	$args = array();
	if ( $wp_head_callback )
		$args['wp-head-callback'] = $wp_head_callback;
	if ( $admin_head_callback )
		$args['admin-head-callback'] = $admin_head_callback;
	if ( $admin_preview_callback )
		$args['admin-preview-callback'] = $admin_preview_callback;
	return add_theme_support( 'custom-background', $args );
}

/**
 * Xóa hỗ trợ nền tùy chỉnh.
 *
 * @since 3.1.0
 * @deprecated 3.4.0 Use add_custom_background()
 * @see add_custom_background()
 *
 * @return null|bool Hỗ trợ có được xóa hay không.
 */
function remove_custom_background() {
	_deprecated_function( __FUNCTION__, '3.4.0', 'remove_theme_support( \'custom-background\' )' );
	return remove_theme_support( 'custom-background' );
}

/**
 * Lấy dữ liệu theme từ tệp theme đã phân tích.
 *
 * @since 1.5.0
 * @deprecated 3.4.0 Use wp_get_theme()
 * @see wp_get_theme()
 *
 * @param string $theme_file Đường dẫn tệp theme.
 * @return array Dữ liệu theme.
 */
function get_theme_data( $theme_file ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'wp_get_theme()' );
	$theme = new WP_Theme( wp_basename( dirname( $theme_file ) ), dirname( dirname( $theme_file ) ) );

	$theme_data = array(
		'Name' => $theme->get('Name'),
		'URI' => $theme->display('ThemeURI', true, false),
		'Description' => $theme->display('Description', true, false),
		'Author' => $theme->display('Author', true, false),
		'AuthorURI' => $theme->display('AuthorURI', true, false),
		'Version' => $theme->get('Version'),
		'Template' => $theme->get('Template'),
		'Status' => $theme->get('Status'),
		'Tags' => $theme->get('Tags'),
		'Title' => $theme->get('Name'),
		'AuthorName' => $theme->get('Author'),
	);

	foreach ( apply_filters( 'extra_theme_headers', array() ) as $extra_header ) {
		if ( ! isset( $theme_data[ $extra_header ] ) )
			$theme_data[ $extra_header ] = $theme->get( $extra_header );
	}

	return $theme_data;
}

/**
 * Bí danh của update_post_cache().
 *
 * @see update_post_cache() Bài viết và trang giống nhau, bí danh là có chủ đích
 *
 * @since 1.5.1
 * @deprecated 3.4.0 Use update_post_cache()
 * @see update_post_cache()
 *
 * @param array $pages Danh sách các đối tượng trang
 */
function update_page_cache( &$pages ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'update_post_cache()' );

	update_post_cache( $pages );
}

/**
 * Xóa trang trong bộ nhớ đệm.
 *
 * Xóa trang khỏi bộ nhớ đệm khớp với $id. Cũng sẽ xóa bộ nhớ đệm
 * liên quan đến 'all_page_ids' và 'get_pages'.
 *
 * @since 2.0.0
 * @deprecated 3.4.0 Use clean_post_cache
 * @see clean_post_cache()
 *
 * @param int $id ID trang cần xóa
 */
function clean_page_cache( $id ) {
	_deprecated_function( __FUNCTION__, '3.4.0', 'clean_post_cache()' );

	clean_post_cache( $id );
}

/**
 * Lấy thông báo "Bạn có chắc không" cho hành động nonce.
 *
 * Ngưng sử dụng từ 3.4.1 và 3.5.0. Được backport về 3.3.3.
 *
 * @since 2.0.4
 * @deprecated 3.4.1 Use wp_nonce_ays()
 * @see wp_nonce_ays()
 *
 * @param string $action Hành động nonce.
 * @return string Thông báo xác nhận.
 */
function wp_explain_nonce( $action ) {
	_deprecated_function( __FUNCTION__, '3.4.1', 'wp_nonce_ays()' );
	return __( 'Are you sure you want to do this?' );
}

/**
 * Hiển thị class CSS "sticky", nếu bài viết được ghim.
 *
 * @since 2.7.0
 * @deprecated 3.5.0 Use post_class()
 * @see post_class()
 *
 * @param int $post_id Tùy chọn. ID bài viết.
 */
function sticky_class( $post_id = null ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'post_class()' );
	if ( is_sticky( $post_id ) )
		echo ' sticky';
}

/**
 * Lấy tổ tiên bài viết.
 *
 * Hàm này không còn cần thiết vì WP_Post tải lười thuộc tính
 * ancestors bằng get_post_ancestors().
 *
 * @since 2.3.4
 * @deprecated 3.5.0 Use get_post_ancestors()
 * @see get_post_ancestors()
 *
 * @param WP_Post $post Đối tượng bài viết, truyền theo tham chiếu (không sử dụng).
 */
function _get_post_ancestors( &$post ) {
	_deprecated_function( __FUNCTION__, '3.5.0' );
}

/**
 * Tải ảnh từ chuỗi, nếu PHP hỗ trợ.
 *
 * @since 2.1.0
 * @deprecated 3.5.0 Use wp_get_image_editor()
 * @see wp_get_image_editor()
 *
 * @param string $file Tên tệp ảnh cần tải.
 * @return resource|GdImage|string Tài nguyên ảnh hoặc thể hiện GdImage khi thành công,
 *                                 chuỗi lỗi khi thất bại.
 */
function wp_load_image( $file ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_get_image_editor()' );

	if ( is_numeric( $file ) )
		$file = get_attached_file( $file );

	if ( ! is_file( $file ) ) {
		/* translators: %s: File name. */
		return sprintf( __( 'File &#8220;%s&#8221; does not exist?' ), $file );
	}

	if ( ! function_exists('imagecreatefromstring') )
		return __('The GD image library is not installed.');

	// Đặt cao giả tạo vì GD sử dụng ảnh không nén trong bộ nhớ.
	wp_raise_memory_limit( 'image' );

	$image = imagecreatefromstring( file_get_contents( $file ) );

	if ( ! is_gd_image( $image ) ) {
		/* translators: %s: File name. */
		return sprintf( __( 'File &#8220;%s&#8221; is not an image.' ), $file );
	}

	return $image;
}

/**
 * Thu nhỏ ảnh để vừa kích thước cụ thể và lưu bản sao mới của ảnh.
 *
 * Độ trong suốt PNG sẽ được bảo toàn khi sử dụng hàm này, cũng như
 * loại ảnh. Nếu tệp đầu vào là PNG, thì ảnh đã thay đổi kích thước cũng sẽ
 * là PNG. Các loại ảnh được hỗ trợ duy nhất là PNG, GIF, và JPEG.
 *
 * Một số chức năng yêu cầu API tồn tại, nên một số phiên bản PHP có thể mất
 * hỗ trợ. Đây không phải lỗi của WordPress (nơi chức năng bị
 * hạ cấp, không phải lỗi thực sự), mà là của phiên bản PHP của bạn.
 *
 * @since 2.5.0
 * @deprecated 3.5.0 Use wp_get_image_editor()
 * @see wp_get_image_editor()
 *
 * @param string $file         Đường dẫn tệp ảnh.
 * @param int    $max_w        Chiều rộng tối đa để thay đổi kích thước.
 * @param int    $max_h        Chiều cao tối đa để thay đổi kích thước.
 * @param bool   $crop         Tùy chọn. Cắt ảnh hay thay đổi kích thước. Mặc định false.
 * @param string $suffix       Tùy chọn. Hậu tố tệp. Mặc định null.
 * @param string $dest_path    Tùy chọn. Đường dẫn tệp ảnh mới. Mặc định null.
 * @param int    $jpeg_quality Tùy chọn. Phần trăm chất lượng ảnh. Mặc định 90.
 * @return mixed WP_Error khi thất bại. Chuỗi với đường dẫn đích mới.
 */
function image_resize( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_get_image_editor()' );

	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) )
		return $editor;
	$editor->set_quality( $jpeg_quality );

	$resized = $editor->resize( $max_w, $max_h, $crop );
	if ( is_wp_error( $resized ) )
		return $resized;

	$dest_file = $editor->generate_filename( $suffix, $dest_path );
	$saved = $editor->save( $dest_file );

	if ( is_wp_error( $saved ) )
		return $saved;

	return $dest_file;
}

/**
 * Lấy một bài viết duy nhất, dựa trên ID bài viết.
 *
 * Có chuyên mục trong thuộc tính hoặc khóa 'post_category'. Có thẻ trong thuộc tính
 * hoặc khóa 'tags_input'.
 *
 * @since 1.0.0
 * @deprecated 3.5.0 Use get_post()
 * @see get_post()
 *
 * @param int $postid ID bài viết.
 * @param string $mode Cách trả về kết quả, OBJECT, ARRAY_N, hoặc ARRAY_A.
 * @return WP_Post|null Đối tượng hoặc mảng bài viết chứa nội dung và thông tin bài viết
 */
function wp_get_single_post( $postid = 0, $mode = OBJECT ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'get_post()' );
	return get_post( $postid, $mode );
}

/**
 * Kiểm tra tên đăng nhập và mật khẩu người dùng có đúng không.
 *
 * @since 0.71
 * @deprecated 3.5.0 Use wp_authenticate()
 * @see wp_authenticate()
 *
 * @param string $user_login Tên người dùng.
 * @param string $user_pass Mật khẩu người dùng.
 * @return bool False nếu không xác thực được, true nếu tên người dùng và mật khẩu xác thực thành công.
 */
function user_pass_ok($user_login, $user_pass) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_authenticate()' );
	$user = wp_authenticate( $user_login, $user_pass );
	if ( is_wp_error( $user ) )
		return false;

	return true;
}

/**
 * Callback trước đây được kích hoạt trên hook save_post. Không còn cần thiết.
 *
 * @since 2.3.0
 * @deprecated 3.5.0
 */
function _save_post_hook() {}

/**
 * Kiểm tra phiên bản GD đã cài đặt có hỗ trợ loại ảnh cụ thể không
 *
 * @since 2.9.0
 * @deprecated 3.5.0 Use wp_image_editor_supports()
 * @see wp_image_editor_supports()
 *
 * @param string $mime_type
 * @return bool
 */
function gd_edit_image_support($mime_type) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_image_editor_supports()' );

	if ( function_exists('imagetypes') ) {
		switch( $mime_type ) {
			case 'image/jpeg':
				return (imagetypes() & IMG_JPG) != 0;
			case 'image/png':
				return (imagetypes() & IMG_PNG) != 0;
			case 'image/gif':
				return (imagetypes() & IMG_GIF) != 0;
			case 'image/webp':
				return (imagetypes() & IMG_WEBP) != 0;
			case 'image/avif':
				return (imagetypes() & IMG_AVIF) != 0;
			}
	} else {
		switch( $mime_type ) {
			case 'image/jpeg':
				return function_exists('imagecreatefromjpeg');
			case 'image/png':
				return function_exists('imagecreatefrompng');
			case 'image/gif':
				return function_exists('imagecreatefromgif');
			case 'image/webp':
				return function_exists('imagecreatefromwebp');
			case 'image/avif':
				return function_exists('imagecreatefromavif');
		}
	}
	return false;
}

/**
 * Chuyển đổi giá trị byte số nguyên sang giá trị byte viết tắt.
 *
 * @since 2.3.0
 * @deprecated 3.6.0 Use size_format()
 * @see size_format()
 *
 * @param int $bytes Giá trị byte số nguyên.
 * @return string Giá trị byte viết tắt.
 */
function wp_convert_bytes_to_hr( $bytes ) {
	_deprecated_function( __FUNCTION__, '3.6.0', 'size_format()' );

	$units = array( 0 => 'B', 1 => 'KB', 2 => 'MB', 3 => 'GB', 4 => 'TB' );
	$log   = log( $bytes, KB_IN_BYTES );
	$power = (int) $log;
	$size  = KB_IN_BYTES ** ( $log - $power );

	if ( ! is_nan( $size ) && array_key_exists( $power, $units ) ) {
		$unit = $units[ $power ];
	} else {
		$size = $bytes;
		$unit = $units[0];
	}

	return $size . $unit;
}

/**
 * Trước đây được sử dụng nội bộ để dọn dẹp các từ khóa tìm kiếm.
 *
 * @since 2.9.0
 * @access private
 * @deprecated 3.7.0
 *
 * @param string $t Từ khóa tìm kiếm cần "dọn dẹp", ví dụ trim.
 * @return string Từ khóa tìm kiếm đã được cắt bỏ khoảng trắng.
 */
function _search_terms_tidy( $t ) {
	_deprecated_function( __FUNCTION__, '3.7.0' );
	return trim( $t, "\"'\n\r " );
}

/**
 * Xác định xem TinyMCE có khả dụng không.
 *
 * Kiểm tra xem người dùng đã xóa các tệp tinymce để giảm bớt
 * bản cài đặt WordPress của họ hay chưa.
 *
 * @since 2.1.0
 * @deprecated 3.9.0
 *
 * @return bool TinyMCE có tồn tại hay không.
 */
function rich_edit_exists() {
	global $wp_rich_edit_exists;
	_deprecated_function( __FUNCTION__, '3.9.0' );

	if ( ! isset( $wp_rich_edit_exists ) )
		$wp_rich_edit_exists = file_exists( ABSPATH . WPINC . '/js/tinymce/tinymce.js' );

	return $wp_rich_edit_exists;
}

/**
 * Callback cũ cho tooltip liên kết thẻ.
 *
 * @since 2.7.0
 * @access private
 * @deprecated 3.9.0
 *
 * @param int $count Số lượng chủ đề.
 * @return int Số lượng chủ đề.
 */
function default_topic_count_text( $count ) {
	return $count;
}

/**
 * Trước đây dùng để thoát chuỗi trước khi chèn vào DB.
 *
 * Đã không thực hiện chức năng này trong rất nhiều năm. Sử dụng wpdb::prepare() thay thế.
 *
 * @since 0.71
 * @deprecated 3.9.0
 *
 * @param string $content Văn bản cần định dạng.
 * @return string Chính văn bản đó.
 */
function format_to_post( $content ) {
	_deprecated_function( __FUNCTION__, '3.9.0' );
	return $content;
}

/**
 * Trước đây dùng để thoát chuỗi trước khi tìm kiếm DB. Tài liệu kém và không bao giờ hoạt động như mô tả.
 *
 * @since 2.5.0
 * @deprecated 4.0.0 Use wpdb::esc_like()
 * @see wpdb::esc_like()
 *
 * @param string $text Văn bản cần thoát.
 * @return string Văn bản, an toàn để sử dụng trong truy vấn LIKE.
 */
function like_escape($text) {
	_deprecated_function( __FUNCTION__, '4.0.0', 'wpdb::esc_like()' );
	return str_replace( array( "%", "_" ), array( "\\%", "\\_" ), $text );
}

/**
 * Xác định xem URL có thể truy cập qua SSL không.
 *
 * Xác định xem URL có thể truy cập qua SSL bằng cách sử dụng WordPress HTTP API để truy cập
 * URL sử dụng https làm giao thức.
 *
 * @since 2.5.0
 * @deprecated 4.0.0
 *
 * @param string $url URL cần kiểm tra.
 * @return bool Truy cập SSL có khả dụng hay không.
 */
function url_is_accessable_via_ssl( $url ) {
	_deprecated_function( __FUNCTION__, '4.0.0' );

	$response = wp_remote_get( set_url_scheme( $url, 'https' ) );

	if ( !is_wp_error( $response ) ) {
		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 == $status || 401 == $status ) {
			return true;
		}
	}

	return false;
}

/**
 * Bắt đầu bộ đệm xuất xem trước theme.
 *
 * Chỉ thực hiện tác vụ nếu người dùng có quyền và các biến truy vấn
 * template và preview tồn tại.
 *
 * @since 2.6.0
 * @deprecated 4.3.0
 */
function preview_theme() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Hàm riêng để thay đổi template hiện tại khi xem trước theme
 *
 * @since 2.9.0
 * @deprecated 4.3.0
 * @access private
 *
 * @return string
 */
function _preview_theme_template_filter() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
	return '';
}

/**
 * Hàm riêng để thay đổi stylesheet hiện tại khi xem trước theme
 *
 * @since 2.9.0
 * @deprecated 4.3.0
 * @access private
 *
 * @return string
 */
function _preview_theme_stylesheet_filter() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
	return '';
}

/**
 * Hàm callback cho ob_start() để bắt tất cả liên kết trong theme.
 *
 * @since 2.6.0
 * @deprecated 4.3.0
 * @access private
 *
 * @param string $content
 * @return string
 */
function preview_theme_ob_filter( $content ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
	return $content;
}

/**
 * Thao tác liên kết xem trước theme để kiểm soát và duy trì vị trí.
 *
 * Hàm callback cho preg_replace_callback() để nhận và lọc kết quả khớp.
 *
 * @since 2.6.0
 * @deprecated 4.3.0
 * @access private
 *
 * @param array $matches
 * @return string
 */
function preview_theme_ob_filter_callback( $matches ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
	return '';
}

/**
 * Định dạng văn bản cho trình soạn thảo văn bản phong phú.
 *
 * Bộ lọc {@see 'richedit_pre'} được áp dụng ở đây. Nếu `$text` rỗng, bộ lọc sẽ
 * được áp dụng cho chuỗi rỗng.
 *
 * @since 2.0.0
 * @deprecated 4.3.0 Use format_for_editor()
 * @see format_for_editor()
 *
 * @param string $text Văn bản cần định dạng.
 * @return string Văn bản đã định dạng sau khi áp dụng bộ lọc.
 */
function wp_richedit_pre($text) {
	_deprecated_function( __FUNCTION__, '4.3.0', 'format_for_editor()' );

	if ( empty( $text ) ) {
		/**
		 * Lọc văn bản trả về cho trình soạn thảo văn bản phong phú.
		 *
		 * Bộ lọc này được đánh giá trước và giá trị được trả về, nếu chuỗi rỗng
		 * được truyền vào wp_richedit_pre(). Nếu chuỗi rỗng được truyền, kết quả
		 * là thẻ ngắt dòng và dòng mới.
		 *
		 * Nếu chuỗi không rỗng được truyền, bộ lọc được đánh giá trên giá trị trả về
		 * của wp_richedit_pre() sau khi được định dạng.
		 *
		 * @since 2.0.0
		 * @deprecated 4.3.0
		 *
		 * @param string $output Văn bản cho trình soạn thảo văn bản phong phú.
		 */
		return apply_filters( 'richedit_pre', '' );
	}

	$output = convert_chars($text);
	$output = wpautop($output);
	$output = htmlspecialchars($output, ENT_NOQUOTES, get_option( 'blog_charset' ) );

	/** Bộ lọc này được ghi nhận trong wp-includes/deprecated.php */
	return apply_filters( 'richedit_pre', $output );
}

/**
 * Định dạng văn bản cho trình soạn thảo HTML.
 *
 * Trừ khi $output rỗng, nó sẽ đi qua htmlspecialchars trước khi
 * bộ lọc {@see 'htmledit_pre'} được áp dụng.
 *
 * @since 2.5.0
 * @deprecated 4.3.0 Use format_for_editor()
 * @see format_for_editor()
 *
 * @param string $output Văn bản cần định dạng.
 * @return string Văn bản đã định dạng sau khi áp dụng bộ lọc.
 */
function wp_htmledit_pre($output) {
	_deprecated_function( __FUNCTION__, '4.3.0', 'format_for_editor()' );

	if ( !empty($output) )
		$output = htmlspecialchars($output, ENT_NOQUOTES, get_option( 'blog_charset' ) ); // Chỉ chuyển đổi '< > &'.

	/**
	 * Lọc văn bản trước khi được định dạng cho trình soạn thảo HTML.
	 *
	 * @since 2.5.0
	 * @deprecated 4.3.0
	 *
	 * @param string $output Văn bản đã định dạng HTML.
	 */
	return apply_filters( 'htmledit_pre', $output );
}

/**
 * Lấy đường dẫn cố định từ ID bài viết.
 *
 * @since 1.0.0
 * @deprecated 4.4.0 Use get_permalink()
 * @see get_permalink()
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là $post toàn cục.
 * @return string|false
 */
function post_permalink( $post = 0 ) {
	_deprecated_function( __FUNCTION__, '4.4.0', 'get_permalink()' );

	return get_permalink( $post );
}

/**
 * Thực hiện yêu cầu HTTP HEAD hoặc GET.
 *
 * Nếu $file_path là tên tệp có thể ghi, hàm này sẽ thực hiện yêu cầu GET và ghi
 * tệp vào đường dẫn đó.
 *
 * @since 2.5.0
 * @deprecated 4.4.0 Use WP_Http
 * @see WP_Http
 *
 * @param string      $url       URL cần tải.
 * @param string|bool $file_path Tùy chọn. Đường dẫn tệp để ghi yêu cầu. Mặc định false.
 * @param int         $red       Tùy chọn. Số lần chuyển hướng đã theo, khi đạt 5,
 *                               trả về false. Mặc định 1.
 * @return \WpOrg\Requests\Utility\CaseInsensitiveDictionary|false Headers khi thành công, false khi thất bại.
 */
function wp_get_http( $url, $file_path = false, $red = 1 ) {
	_deprecated_function( __FUNCTION__, '4.4.0', 'WP_Http' );

	// Thêm 60 giây vào thời gian chờ script để đảm bảo yêu cầu từ xa có đủ thời gian.
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 60 );
	}

	if ( $red > 5 )
		return false;

	$options = array();
	$options['redirection'] = 5;

	if ( false == $file_path )
		$options['method'] = 'HEAD';
	else
		$options['method'] = 'GET';

	$response = wp_safe_remote_request( $url, $options );

	if ( is_wp_error( $response ) )
		return false;

	$headers = wp_remote_retrieve_headers( $response );
	$headers['response'] = wp_remote_retrieve_response_code( $response );

	// WP_HTTP không còn theo chuyển hướng cho yêu cầu HEAD.
	if ( 'HEAD' == $options['method'] && in_array($headers['response'], array(301, 302)) && isset( $headers['location'] ) ) {
		return wp_get_http( $headers['location'], $file_path, ++$red );
	}

	if ( false == $file_path )
		return $headers;

	// Yêu cầu GET - ghi vào tên tệp được cung cấp.
	$out_fp = fopen($file_path, 'w');
	if ( !$out_fp )
		return $headers;

	fwrite( $out_fp,  wp_remote_retrieve_body( $response ) );
	fclose($out_fp);
	clearstatcache();

	return $headers;
}

/**
 * Có nên bắt buộc đăng nhập SSL không.
 *
 * @since 2.6.0
 * @deprecated 4.4.0 Use force_ssl_admin()
 * @see force_ssl_admin()
 *
 * @param string|bool $force Tùy chọn. Có bắt buộc đăng nhập SSL không. Mặc định null.
 * @return bool True nếu bắt buộc, false nếu không bắt buộc.
 */
function force_ssl_login( $force = null ) {
	_deprecated_function( __FUNCTION__, '4.4.0', 'force_ssl_admin()' );
	return force_ssl_admin( $force );
}

/**
 * Lấy đường dẫn template popup bình luận trong template hiện tại hoặc template cha.
 *
 * @since 1.5.0
 * @deprecated 4.5.0
 *
 * @return string Đường dẫn đầy đủ đến tệp template popup bình luận.
 */
function get_comments_popup_template() {
	_deprecated_function( __FUNCTION__, '4.5.0' );

	return '';
}

/**
 * Xác định xem URL hiện tại có nằm trong cửa sổ popup bình luận không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 1.5.0
 * @deprecated 4.5.0
 *
 * @return false Luôn trả về false.
 */
function is_comments_popup() {
	_deprecated_function( __FUNCTION__, '4.5.0' );

	return false;
}

/**
 * Hiển thị script JS popup để hiện bình luận.
 *
 * @since 0.71
 * @deprecated 4.5.0
 */
function comments_popup_script() {
	_deprecated_function( __FUNCTION__, '4.5.0' );
}

/**
 * Thêm thuộc tính phần tử để mở liên kết trong tab mới.
 *
 * @since 0.71
 * @deprecated 4.5.0
 *
 * @param string $text Nội dung cần thay thế liên kết để mở trong tab mới.
 * @return string Nội dung có các liên kết đã được lọc.
 */
function popuplinks( $text ) {
	_deprecated_function( __FUNCTION__, '4.5.0' );
	$text = preg_replace('/<a (.+?)>/i', "<a $1 target='_blank' rel='external'>", $text);
	return $text;
}

/**
 * Callback xử lý nhúng Google Video.
 *
 * Hàm ngưng sử dụng trước đây hỗ trợ chuyển đổi URL Google Video
 * thành nội dung nhúng nhưng dịch vụ đó đã ngừng hoạt động.
 *
 * @since 2.9.0
 * @deprecated 4.6.0
 *
 * @return string Chuỗi rỗng.
 */
function wp_embed_handler_googlevideo( $matches, $attr, $url, $rawattr ) {
	_deprecated_function( __FUNCTION__, '4.6.0' );

	return '';
}

/**
 * Lấy đường dẫn template phân trang trong template hiện tại hoặc template cha.
 *
 * @since 1.5.0
 * @deprecated 4.7.0 Template paged.php không còn là một phần của hệ thống phân cấp template theme.
 *
 * @return string Đường dẫn đầy đủ đến tệp template phân trang.
 */
function get_paged_template() {
	_deprecated_function( __FUNCTION__, '4.7.0' );

	return get_query_template( 'paged' );
}

/**
 * Xóa các thực thể JavaScript HTML được tìm thấy trong các phiên bản đầu tiên của Netscape 4.
 *
 * Trước đây, hàm này được lấy từ bản nhập gốc
 * của kses và xóa một lỗ hổng cụ thể chỉ
 * tồn tại trong phiên bản đầu tiên của Netscape 4. Tuy nhiên, lỗ hổng
 * này không bao giờ ảnh hưởng đến trình duyệt khác và có thể
 * được coi là an toàn cho web hiện đại.
 *
 * Biểu thức chính quy làm sạch lỗ hổng này
 * đã được loại bỏ xét đến hiệu suất và
 * yêu cầu năng lượng mà nó đặt ra, giờ chỉ đơn giản truyền
 * đầu vào sang đầu ra.
 *
 * @since 1.0.0
 * @deprecated 4.7.0 Chính thức ngừng hỗ trợ bảo mật cho Netscape 4.
 *
 * @param string $content
 * @return string
 */
function wp_kses_js_entities( $content ) {
	_deprecated_function( __FUNCTION__, '4.7.0' );

	return preg_replace( '%&\s*\{[^}]*(\}\s*;?|$)%', '', $content );
}

/**
 * Sắp xếp chuyên mục theo ID.
 *
 * Được sử dụng bởi usort() làm callback, không nên sử dụng trực tiếp. Thực tế có thể
 * được dùng để sắp xếp bất kỳ đối tượng term nào.
 *
 * @since 2.3.0
 * @deprecated 4.7.0 Use wp_list_sort()
 * @access private
 *
 * @param object $a
 * @param object $b
 * @return int
 */
function _usort_terms_by_ID( $a, $b ) {
	_deprecated_function( __FUNCTION__, '4.7.0', 'wp_list_sort()' );

	if ( $a->term_id > $b->term_id )
		return 1;
	elseif ( $a->term_id < $b->term_id )
		return -1;
	else
		return 0;
}

/**
 * Sắp xếp chuyên mục theo tên.
 *
 * Được sử dụng bởi usort() làm callback, không nên sử dụng trực tiếp. Thực tế có thể
 * được dùng để sắp xếp bất kỳ đối tượng term nào.
 *
 * @since 2.3.0
 * @deprecated 4.7.0 Use wp_list_sort()
 * @access private
 *
 * @param object $a
 * @param object $b
 * @return int
 */
function _usort_terms_by_name( $a, $b ) {
	_deprecated_function( __FUNCTION__, '4.7.0', 'wp_list_sort()' );

	return strcmp( $a->name, $b->name );
}

/**
 * Sắp xếp mục menu theo khóa mong muốn.
 *
 * @since 3.0.0
 * @deprecated 4.7.0 Use wp_list_sort()
 * @access private
 *
 * @global string $_menu_item_sort_prop
 *
 * @param object $a Đối tượng đầu tiên để so sánh
 * @param object $b Đối tượng thứ hai để so sánh
 * @return int -1, 0, hoặc 1 nếu $a được coi là nhỏ hơn, bằng, hoặc lớn hơn $b tương ứng.
 */
function _sort_nav_menu_items( $a, $b ) {
	global $_menu_item_sort_prop;

	_deprecated_function( __FUNCTION__, '4.7.0', 'wp_list_sort()' );

	if ( empty( $_menu_item_sort_prop ) )
		return 0;

	if ( ! isset( $a->$_menu_item_sort_prop ) || ! isset( $b->$_menu_item_sort_prop ) )
		return 0;

	$_a = (int) $a->$_menu_item_sort_prop;
	$_b = (int) $b->$_menu_item_sort_prop;

	if ( $a->$_menu_item_sort_prop == $b->$_menu_item_sort_prop )
		return 0;
	elseif ( $_a == $a->$_menu_item_sort_prop && $_b == $b->$_menu_item_sort_prop )
		return $_a < $_b ? -1 : 1;
	else
		return strcmp( $a->$_menu_item_sort_prop, $b->$_menu_item_sort_prop );
}

/**
 * Lấy liên kết bookmarklet Press This.
 *
 * @since 2.6.0
 * @deprecated 4.9.0
 * @return string
 */
function get_shortcut_link() {
	_deprecated_function( __FUNCTION__, '4.9.0' );

	$link = '';

	/**
	 * Lọc liên kết bookmarklet Press This.
	 *
	 * @since 2.6.0
	 * @deprecated 4.9.0
	 *
	 * @param string $link Liên kết bookmarklet Press This.
	 */
	return apply_filters( 'shortcut_link', $link );
}

/**
 * Trình xử lý Ajax để lưu bài viết từ Press This.
 *
 * @since 4.2.0
 * @deprecated 4.9.0
 */
function wp_ajax_press_this_save_post() {
	_deprecated_function( __FUNCTION__, '4.9.0' );
	if ( is_plugin_active( 'press-this/press-this-plugin.php' ) ) {
		include WP_PLUGIN_DIR . '/press-this/class-wp-press-this-plugin.php';
		$wp_press_this = new WP_Press_This_Plugin();
		$wp_press_this->save_post();
	} else {
		wp_send_json_error( array( 'errorMessage' => __( 'The Press This plugin is required.' ) ) );
	}
}

/**
 * Trình xử lý Ajax để tạo chuyên mục mới từ Press This.
 *
 * @since 4.2.0
 * @deprecated 4.9.0
 */
function wp_ajax_press_this_add_category() {
	_deprecated_function( __FUNCTION__, '4.9.0' );
	if ( is_plugin_active( 'press-this/press-this-plugin.php' ) ) {
		include WP_PLUGIN_DIR . '/press-this/class-wp-press-this-plugin.php';
		$wp_press_this = new WP_Press_This_Plugin();
		$wp_press_this->add_category();
	} else {
		wp_send_json_error( array( 'errorMessage' => __( 'The Press This plugin is required.' ) ) );
	}
}

/**
 * Trả về đối tượng yêu cầu người dùng cho ID yêu cầu được chỉ định.
 *
 * @since 4.9.6
 * @deprecated 5.4.0 Use wp_get_user_request()
 * @see wp_get_user_request()
 *
 * @param int $request_id ID của yêu cầu người dùng.
 * @return WP_User_Request|false
 */
function wp_get_user_request_data( $request_id ) {
	_deprecated_function( __FUNCTION__, '5.4.0', 'wp_get_user_request()' );
	return wp_get_user_request( $request_id );
}

/**
 * Lọc phần tử 'img' trong nội dung bài viết để thêm thuộc tính 'srcset' và 'sizes'.
 *
 * @since 4.4.0
 * @deprecated 5.5.0
 *
 * @see wp_image_add_srcset_and_sizes()
 *
 * @param string $content Nội dung bài viết thô cần lọc.
 * @return string Nội dung đã chuyển đổi với thuộc tính 'srcset' và 'sizes' được thêm vào ảnh.
 */
function wp_make_content_images_responsive( $content ) {
	_deprecated_function( __FUNCTION__, '5.5.0', 'wp_filter_content_tags()' );

	// Hàm này cũng sẽ thêm thuộc tính `loading` vào thẻ `img`, nếu được bật.
	return wp_filter_content_tags( $content );
}

/**
 * Tắt register globals.
 *
 * @since 2.1.0
 * @access private
 * @deprecated 5.5.0
 */
function wp_unregister_GLOBALS() {
	// register_globals đã ngưng sử dụng trong PHP 5.3 và bị loại bỏ hoàn toàn trong PHP 5.4.
	_deprecated_function( __FUNCTION__, '5.5.0' );
}

/**
 * Bình luận có chứa ký tự hoặc từ không được phép không.
 *
 * @since 1.5.0
 * @deprecated 5.5.0 Use wp_check_comment_disallowed_list() instead.
 *                   Please consider writing more inclusive code.
 *
 * @param string $author Tác giả bình luận
 * @param string $email Email của bình luận
 * @param string $url URL được sử dụng trong bình luận
 * @param string $comment Nội dung bình luận
 * @param string $user_ip Địa chỉ IP của tác giả bình luận
 * @param string $user_agent User agent trình duyệt của tác giả
 * @return bool True nếu bình luận chứa nội dung không được phép, false nếu không
 */
function wp_blacklist_check( $author, $email, $url, $comment, $user_ip, $user_agent ) {
	_deprecated_function( __FUNCTION__, '5.5.0', 'wp_check_comment_disallowed_list()' );

	return wp_check_comment_disallowed_list( $author, $email, $url, $comment, $user_ip, $user_agent );
}

/**
 * Lọc các tham số `register_meta()` dựa trên danh sách được phép.
 *
 * Tham số `register_meta()` có thể thay đổi theo thời gian, nên việc yêu cầu danh sách được phép
 * phải được tắt rõ ràng là một dạng đảm bảo.
 *
 * @access private
 * @since 4.6.0
 * @deprecated 5.5.0 Use _wp_register_meta_args_allowed_list() instead.
 *                   Please consider writing more inclusive code.
 *
 * @param array $args         Tham số từ `register_meta()`.
 * @param array $default_args Tham số mặc định cho `register_meta()`.
 * @return array Tham số đã lọc.
 */
function _wp_register_meta_args_whitelist( $args, $default_args ) {
	_deprecated_function( __FUNCTION__, '5.5.0', '_wp_register_meta_args_allowed_list()' );

	return _wp_register_meta_args_allowed_list( $args, $default_args );
}

/**
 * Thêm mảng tùy chọn vào danh sách tùy chọn được phép.
 *
 * @since 2.7.0
 * @deprecated 5.5.0 Use add_allowed_options() instead.
 *                   Please consider writing more inclusive code.
 *
 * @param array        $new_options
 * @param string|array $options
 * @return array
 */
function add_option_whitelist( $new_options, $options = '' ) {
	_deprecated_function( __FUNCTION__, '5.5.0', 'add_allowed_options()' );

	return add_allowed_options( $new_options, $options );
}

/**
 * Xóa danh sách tùy chọn khỏi danh sách tùy chọn được phép.
 *
 * @since 2.7.0
 * @deprecated 5.5.0 Use remove_allowed_options() instead.
 *                   Please consider writing more inclusive code.
 *
 * @param array        $del_options
 * @param string|array $options
 * @return array
 */
function remove_option_whitelist( $del_options, $options = '' ) {
	_deprecated_function( __FUNCTION__, '5.5.0', 'remove_allowed_options()' );

	return remove_allowed_options( $del_options, $options );
}

/**
 * Thêm dấu gạch chéo chỉ cho các giá trị chuỗi trong mảng giá trị.
 *
 * Nên sử dụng khi chuẩn bị dữ liệu cho API lõi yêu cầu dữ liệu có dấu gạch chéo.
 * Không nên sử dụng để thoát dữ liệu đi trực tiếp vào truy vấn SQL.
 *
 * @since 5.3.0
 * @deprecated 5.6.0 Use wp_slash()
 *
 * @see wp_slash()
 *
 * @param mixed $value Giá trị vô hướng hoặc mảng giá trị vô hướng.
 * @return mixed Giá trị $value đã thêm dấu gạch chéo
 */
function wp_slash_strings_only( $value ) {
	return map_deep( $value, 'addslashes_strings_only' );
}

/**
 * Thêm dấu gạch chéo chỉ khi giá trị được cung cấp là chuỗi.
 *
 * @since 5.3.0
 * @deprecated 5.6.0
 *
 * @see wp_slash()
 *
 * @param mixed $value
 * @return mixed
 */
function addslashes_strings_only( $value ) {
	return is_string( $value ) ? addslashes( $value ) : $value;
}

/**
 * Hiển thị thẻ meta `noindex` nếu cấu hình blog yêu cầu.
 *
 * Nếu blog được đánh dấu là không công khai thì thẻ meta `noindex` sẽ được
 * xuất ra để thông báo cho robot web không lập chỉ mục nội dung trang.
 *
 * Cách sử dụng điển hình là callback {@see 'wp_head'}:
 *
 *     add_action( 'wp_head', 'noindex' );
 *
 * @see wp_no_robots()
 *
 * @since 2.1.0
 * @deprecated 5.7.0 Use wp_robots_noindex() instead on 'wp_robots' filter.
 */
function noindex() {
	_deprecated_function( __FUNCTION__, '5.7.0', 'wp_robots_noindex()' );

	// Nếu blog không công khai, thông báo cho robot đi đi.
	if ( '0' == get_option( 'blog_public' ) ) {
		wp_no_robots();
	}
}

/**
 * Hiển thị thẻ meta `noindex`.
 *
 * Xuất thẻ meta `noindex` thông báo cho robot web không lập chỉ mục nội dung trang.
 *
 * Cách sử dụng điển hình là callback {@see 'wp_head'}:
 *
 *     add_action( 'wp_head', 'wp_no_robots' );
 *
 * @since 3.3.0
 * @since 5.3.0 Xuất `noindex,nofollow` nếu khả năng hiển thị công cụ tìm kiếm bị hạn chế.
 * @deprecated 5.7.0 Use wp_robots_no_robots() instead on 'wp_robots' filter.
 */
function wp_no_robots() {
	_deprecated_function( __FUNCTION__, '5.7.0', 'wp_robots_no_robots()' );

	if ( get_option( 'blog_public' ) ) {
		echo "<meta name='robots' content='noindex,follow' />\n";
		return;
	}

	echo "<meta name='robots' content='noindex,nofollow' />\n";
}

/**
 * Display a `noindex,noarchive` meta tag and referrer `strict-origin-when-cross-origin` meta tag.
 *
 * Outputs a `noindex,noarchive` meta tag that tells web robots not to index or cache the page content.
 * Outputs a referrer `strict-origin-when-cross-origin` meta tag that tells the browser not to send
 * the full URL as a referrer to other sites when cross-origin assets are loaded.
 *
 * Typical usage is as a {@see 'wp_head'} callback:
 *
 *     add_action( 'wp_head', 'wp_sensitive_page_meta' );
 *
 * @since 5.0.1
 * @deprecated 5.7.0 Use wp_robots_sensitive_page() instead on 'wp_robots' filter
 *                   and wp_strict_cross_origin_referrer() on 'wp_head' action.
 *
 * @see wp_robots_sensitive_page()
 */
function wp_sensitive_page_meta() {
	_deprecated_function( __FUNCTION__, '5.7.0', 'wp_robots_sensitive_page()' );

	?>
	<meta name='robots' content='noindex,noarchive' />
	<?php
	wp_strict_cross_origin_referrer();
}

/**
 * Render inner blocks from the `core/columns` block for generating an excerpt.
 *
 * @since 5.2.0
 * @access private
 * @deprecated 5.8.0 Use _excerpt_render_inner_blocks() introduced in 5.8.0.
 *
 * @see _excerpt_render_inner_blocks()
 *
 * @param array $columns        The parsed columns block.
 * @param array $allowed_blocks The list of allowed inner blocks.
 * @return string The rendered inner blocks.
 */
function _excerpt_render_inner_columns_blocks( $columns, $allowed_blocks ) {
	_deprecated_function( __FUNCTION__, '5.8.0', '_excerpt_render_inner_blocks()' );

	return _excerpt_render_inner_blocks( $columns, $allowed_blocks );
}

/**
 * Renders the duotone filter SVG and returns the CSS filter property to
 * reference the rendered SVG.
 *
 * @since 5.9.0
 * @deprecated 5.9.1 Use wp_get_duotone_filter_property() introduced in 5.9.1.
 *
 * @see wp_get_duotone_filter_property()
 *
 * @param array $preset Duotone preset value as seen in theme.json.
 * @return string Duotone CSS filter property.
 */
function wp_render_duotone_filter_preset( $preset ) {
	_deprecated_function( __FUNCTION__, '5.9.1', 'wp_get_duotone_filter_property()' );

	return wp_get_duotone_filter_property( $preset );
}

/**
 * Checks whether serialization of the current block's border properties should occur.
 *
 * @since 5.8.0
 * @access private
 * @deprecated 6.0.0 Use wp_should_skip_block_supports_serialization() introduced in 6.0.0.
 *
 * @see wp_should_skip_block_supports_serialization()
 *
 * @param WP_Block_Type $block_type Block type.
 * @return bool Whether serialization of the current block's border properties
 *              should occur.
 */
function wp_skip_border_serialization( $block_type ) {
	_deprecated_function( __FUNCTION__, '6.0.0', 'wp_should_skip_block_supports_serialization()' );

	$border_support = isset( $block_type->supports['__experimentalBorder'] )
		? $block_type->supports['__experimentalBorder']
		: false;

	return is_array( $border_support ) &&
		array_key_exists( '__experimentalSkipSerialization', $border_support ) &&
		$border_support['__experimentalSkipSerialization'];
}

/**
 * Checks whether serialization of the current block's dimensions properties should occur.
 *
 * @since 5.9.0
 * @access private
 * @deprecated 6.0.0 Use wp_should_skip_block_supports_serialization() introduced in 6.0.0.
 *
 * @see wp_should_skip_block_supports_serialization()
 *
 * @param WP_Block_type $block_type Block type.
 * @return bool Whether to serialize spacing support styles & classes.
 */
function wp_skip_dimensions_serialization( $block_type ) {
	_deprecated_function( __FUNCTION__, '6.0.0', 'wp_should_skip_block_supports_serialization()' );

	$dimensions_support = isset( $block_type->supports['__experimentalDimensions'] )
		? $block_type->supports['__experimentalDimensions']
		: false;

	return is_array( $dimensions_support ) &&
		array_key_exists( '__experimentalSkipSerialization', $dimensions_support ) &&
		$dimensions_support['__experimentalSkipSerialization'];
}

/**
 * Checks whether serialization of the current block's spacing properties should occur.
 *
 * @since 5.9.0
 * @access private
 * @deprecated 6.0.0 Use wp_should_skip_block_supports_serialization() introduced in 6.0.0.
 *
 * @see wp_should_skip_block_supports_serialization()
 *
 * @param WP_Block_Type $block_type Block type.
 * @return bool Whether to serialize spacing support styles & classes.
 */
function wp_skip_spacing_serialization( $block_type ) {
	_deprecated_function( __FUNCTION__, '6.0.0', 'wp_should_skip_block_supports_serialization()' );

	$spacing_support = isset( $block_type->supports['spacing'] )
		? $block_type->supports['spacing']
		: false;

	return is_array( $spacing_support ) &&
		array_key_exists( '__experimentalSkipSerialization', $spacing_support ) &&
		$spacing_support['__experimentalSkipSerialization'];
}

/**
 * Inject the block editor assets that need to be loaded into the editor's iframe as an inline script.
 *
 * @since 5.8.0
 * @deprecated 6.0.0
 */
function wp_add_iframed_editor_assets_html() {
	_deprecated_function( __FUNCTION__, '6.0.0' );
}

/**
 * Retrieves thumbnail for an attachment.
 * Note that this works only for the (very) old image metadata style where 'thumb' was set,
 * and the 'sizes' array did not exist. This function returns false for the newer image metadata style
 * despite that 'thumbnail' is present in the 'sizes' array.
 *
 * @since 2.1.0
 * @deprecated 6.1.0
 *
 * @param int $post_id Optional. Attachment ID. Default is the ID of the global `$post`.
 * @return string|false Thumbnail file path on success, false on failure.
 */
function wp_get_attachment_thumb_file( $post_id = 0 ) {
	_deprecated_function( __FUNCTION__, '6.1.0' );

	$post_id = (int) $post_id;
	$post    = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	// Use $post->ID rather than $post_id as get_post() may have used the global $post object.
	$imagedata = wp_get_attachment_metadata( $post->ID );

	if ( ! is_array( $imagedata ) ) {
		return false;
	}

	$file = get_attached_file( $post->ID );

	if ( ! empty( $imagedata['thumb'] ) ) {
		$thumbfile = str_replace( wp_basename( $file ), $imagedata['thumb'], $file );
		if ( file_exists( $thumbfile ) ) {
			/**
			 * Filters the attachment thumbnail file path.
			 *
			 * @since 2.1.0
			 *
			 * @param string $thumbfile File path to the attachment thumbnail.
			 * @param int    $post_id   Attachment ID.
			 */
			return apply_filters( 'wp_get_attachment_thumb_file', $thumbfile, $post->ID );
		}
	}

	return false;
}

/**
 * Gets the path to a translation file for loading a textdomain just in time.
 *
 * Caches the retrieved results internally.
 *
 * @since 4.7.0
 * @deprecated 6.1.0
 * @access private
 *
 * @see _load_textdomain_just_in_time()
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @param bool   $reset  Whether to reset the internal cache. Used by the switch to locale functionality.
 * @return string|false The path to the translation file or false if no translation file was found.
 */
function _get_path_to_translation( $domain, $reset = false ) {
	_deprecated_function( __FUNCTION__, '6.1.0', 'WP_Textdomain_Registry' );

	static $available_translations = array();

	if ( true === $reset ) {
		$available_translations = array();
	}

	if ( ! isset( $available_translations[ $domain ] ) ) {
		$available_translations[ $domain ] = _get_path_to_translation_from_lang_dir( $domain );
	}

	return $available_translations[ $domain ];
}

/**
 * Gets the path to a translation file in the languages directory for the current locale.
 *
 * Holds a cached list of available .mo files to improve performance.
 *
 * @since 4.7.0
 * @deprecated 6.1.0
 * @access private
 *
 * @see _get_path_to_translation()
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @return string|false The path to the translation file or false if no translation file was found.
 */
function _get_path_to_translation_from_lang_dir( $domain ) {
	_deprecated_function( __FUNCTION__, '6.1.0', 'WP_Textdomain_Registry' );

	static $cached_mofiles = null;

	if ( null === $cached_mofiles ) {
		$cached_mofiles = array();

		$locations = array(
			WP_LANG_DIR . '/plugins',
			WP_LANG_DIR . '/themes',
		);

		foreach ( $locations as $location ) {
			$mofiles = glob( $location . '/*.mo' );
			if ( $mofiles ) {
				$cached_mofiles = array_merge( $cached_mofiles, $mofiles );
			}
		}
	}

	$locale = determine_locale();
	$mofile = "{$domain}-{$locale}.mo";

	$path = WP_LANG_DIR . '/plugins/' . $mofile;
	if ( in_array( $path, $cached_mofiles, true ) ) {
		return $path;
	}

	$path = WP_LANG_DIR . '/themes/' . $mofile;
	if ( in_array( $path, $cached_mofiles, true ) ) {
		return $path;
	}

	return false;
}

/**
 * Allows multiple block styles.
 *
 * @since 5.9.0
 * @deprecated 6.1.0
 *
 * @param array $metadata Metadata for registering a block type.
 * @return array Metadata for registering a block type.
 */
function _wp_multiple_block_styles( $metadata ) {
	_deprecated_function( __FUNCTION__, '6.1.0' );
	return $metadata;
}

/**
 * Generates an inline style for a typography feature e.g. text decoration,
 * text transform, and font style.
 *
 * @since 5.8.0
 * @access private
 * @deprecated 6.1.0 Use wp_style_engine_get_styles() introduced in 6.1.0.
 *
 * @see wp_style_engine_get_styles()
 *
 * @param array  $attributes   Block's attributes.
 * @param string $feature      Key for the feature within the typography styles.
 * @param string $css_property Slug for the CSS property the inline style sets.
 * @return string CSS inline style.
 */
function wp_typography_get_css_variable_inline_style( $attributes, $feature, $css_property ) {
	_deprecated_function( __FUNCTION__, '6.1.0', 'wp_style_engine_get_styles()' );

	// Retrieve current attribute value or skip if not found.
	$style_value = _wp_array_get( $attributes, array( 'style', 'typography', $feature ), false );
	if ( ! $style_value ) {
		return;
	}

	// If we don't have a preset CSS variable, we'll assume it's a regular CSS value.
	if ( ! str_contains( $style_value, "var:preset|{$css_property}|" ) ) {
		return sprintf( '%s:%s;', $css_property, $style_value );
	}

	/*
	 * We have a preset CSS variable as the style.
	 * Get the style value from the string and return CSS style.
	 */
	$index_to_splice = strrpos( $style_value, '|' ) + 1;
	$slug            = substr( $style_value, $index_to_splice );

	// Return the actual CSS inline style e.g. `text-decoration:var(--wp--preset--text-decoration--underline);`.
	return sprintf( '%s:var(--wp--preset--%s--%s);', $css_property, $css_property, $slug );
}

/**
 * Determines whether global terms are enabled.
 *
 * @since 3.0.0
 * @since 6.1.0 This function now always returns false.
 * @deprecated 6.1.0
 *
 * @return bool Always returns false.
 */
function global_terms_enabled() {
	_deprecated_function( __FUNCTION__, '6.1.0' );

	return false;
}

/**
 * Filter the SQL clauses of an attachment query to include filenames.
 *
 * @since 4.7.0
 * @deprecated 6.0.3
 * @access private
 *
 * @param array $clauses An array including WHERE, GROUP BY, JOIN, ORDER BY,
 *                       DISTINCT, fields (SELECT), and LIMITS clauses.
 * @return array The unmodified clauses.
 */
function _filter_query_attachment_filenames( $clauses ) {
	_deprecated_function( __FUNCTION__, '6.0.3', 'add_filter( "wp_allow_query_attachment_by_filename", "__return_true" )' );
	remove_filter( 'posts_clauses', __FUNCTION__ );
	return $clauses;
}

/**
 * Retrieves a page given its title.
 *
 * If more than one post uses the same title, the post with the smallest ID will be returned.
 * Be careful: in case of more than one post having the same title, it will check the oldest
 * publication date, not the smallest ID.
 *
 * Because this function uses the MySQL '=' comparison, $page_title will usually be matched
 * as case-insensitive with default collation.
 *
 * @since 2.1.0
 * @since 3.0.0 The `$post_type` parameter was added.
 * @deprecated 6.2.0 Use WP_Query.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string       $page_title Page title.
 * @param string       $output     Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                                 correspond to a WP_Post object, an associative array, or a numeric array,
 *                                 respectively. Default OBJECT.
 * @param string|array $post_type  Optional. Post type or array of post types. Default 'page'.
 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
 */
function get_page_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {
	_deprecated_function( __FUNCTION__, '6.2.0', 'WP_Query' );
	global $wpdb;

	if ( is_array( $post_type ) ) {
		$post_type           = esc_sql( $post_type );
		$post_type_in_string = "'" . implode( "','", $post_type ) . "'";
		$sql                 = $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type IN ($post_type_in_string)",
			$page_title
		);
	} else {
		$sql = $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = %s",
			$page_title,
			$post_type
		);
	}

	$page = $wpdb->get_var( $sql );

	if ( $page ) {
		return get_post( $page, $output );
	}

	return null;
}

/**
 * Returns the correct template for the site's home page.
 *
 * @access private
 * @since 6.0.0
 * @deprecated 6.2.0 Site Editor's server-side redirect for missing postType and postId
 *                   query args is removed. Thus, this function is no longer used.
 *
 * @return array|null A template object, or null if none could be found.
 */
function _resolve_home_block_template() {
	_deprecated_function( __FUNCTION__, '6.2.0' );

	$show_on_front = get_option( 'show_on_front' );
	$front_page_id = get_option( 'page_on_front' );

	if ( 'page' === $show_on_front && $front_page_id ) {
		return array(
				'postType' => 'page',
				'postId'   => $front_page_id,
		);
	}

	$hierarchy = array( 'front-page', 'home', 'index' );
	$template  = resolve_block_template( 'home', $hierarchy, '' );

	if ( ! $template ) {
		return null;
	}

	return array(
			'postType' => 'wp_template',
			'postId'   => $template->id,
	);
}

/**
 * Displays the link to the Windows Live Writer manifest file.
 *
 * @link https://msdn.microsoft.com/en-us/library/bb463265.aspx
 * @since 2.3.1
 * @deprecated 6.3.0 WLW manifest is no longer in use and no longer included in core,
 *                   so the output from this function is removed.
 */
function wlwmanifest_link() {
	_deprecated_function( __FUNCTION__, '6.3.0' );
}

/**
 * Queues comments for metadata lazy-loading.
 *
 * @since 4.5.0
 * @deprecated 6.3.0 Use wp_lazyload_comment_meta() instead.
 *
 * @param WP_Comment[] $comments Array of comment objects.
 */
function wp_queue_comments_for_comment_meta_lazyload( $comments ) {
	_deprecated_function( __FUNCTION__, '6.3.0', 'wp_lazyload_comment_meta()' );
	// Don't use `wp_list_pluck()` to avoid by-reference manipulation.
	$comment_ids = array();
	if ( is_array( $comments ) ) {
		foreach ( $comments as $comment ) {
			if ( $comment instanceof WP_Comment ) {
				$comment_ids[] = $comment->comment_ID;
			}
		}
	}

	wp_lazyload_comment_meta( $comment_ids );
}

/**
 * Gets the default value to use for a `loading` attribute on an element.
 *
 * This function should only be called for a tag and context if lazy-loading is generally enabled.
 *
 * The function usually returns 'lazy', but uses certain heuristics to guess whether the current element is likely to
 * appear above the fold, in which case it returns a boolean `false`, which will lead to the `loading` attribute being
 * omitted on the element. The purpose of this refinement is to avoid lazy-loading elements that are within the initial
 * viewport, which can have a negative performance impact.
 *
 * Under the hood, the function uses {@see wp_increase_content_media_count()} every time it is called for an element
 * within the main content. If the element is the very first content element, the `loading` attribute will be omitted.
 * This default threshold of 3 content elements to omit the `loading` attribute for can be customized using the
 * {@see 'wp_omit_loading_attr_threshold'} filter.
 *
 * @since 5.9.0
 * @deprecated 6.3.0 Use wp_get_loading_optimization_attributes() instead.
 * @see wp_get_loading_optimization_attributes()
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string $context Context for the element for which the `loading` attribute value is requested.
 * @return string|bool The default `loading` attribute value. Either 'lazy', 'eager', or a boolean `false`, to indicate
 *                     that the `loading` attribute should be skipped.
 */
function wp_get_loading_attr_default( $context ) {
	_deprecated_function( __FUNCTION__, '6.3.0', 'wp_get_loading_optimization_attributes()' );
	global $wp_query;

	// Skip lazy-loading for the overall block template, as it is handled more granularly.
	if ( 'template' === $context ) {
		return false;
	}

	/*
	 * Do not lazy-load images in the header block template part, as they are likely above the fold.
	 * For classic themes, this is handled in the condition below using the 'get_header' action.
	 */
	$header_area = WP_TEMPLATE_PART_AREA_HEADER;
	if ( "template_part_{$header_area}" === $context ) {
		return false;
	}

	// Special handling for programmatically created image tags.
	if ( 'the_post_thumbnail' === $context || 'wp_get_attachment_image' === $context ) {
		/*
		 * Skip programmatically created images within post content as they need to be handled together with the other
		 * images within the post content.
		 * Without this clause, they would already be counted below which skews the number and can result in the first
		 * post content image being lazy-loaded only because there are images elsewhere in the post content.
		 */
		if ( doing_filter( 'the_content' ) ) {
			return false;
		}

		// Conditionally skip lazy-loading on images before the loop.
		if (
			// Only apply for main query but before the loop.
			$wp_query->before_loop && $wp_query->is_main_query()
			/*
			 * Any image before the loop, but after the header has started should not be lazy-loaded,
			 * except when the footer has already started which can happen when the current template
			 * does not include any loop.
			 */
			&& did_action( 'get_header' ) && ! did_action( 'get_footer' )
		) {
			return false;
		}
	}

	/*
	 * The first elements in 'the_content' or 'the_post_thumbnail' should not be lazy-loaded,
	 * as they are likely above the fold.
	 */
	if ( 'the_content' === $context || 'the_post_thumbnail' === $context ) {
		// Only elements within the main query loop have special handling.
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return 'lazy';
		}

		// Increase the counter since this is a main query content element.
		$content_media_count = wp_increase_content_media_count();

		// If the count so far is below the threshold, return `false` so that the `loading` attribute is omitted.
		if ( $content_media_count <= wp_omit_loading_attr_threshold() ) {
			return false;
		}

		// For elements after the threshold, lazy-load them as usual.
		return 'lazy';
	}

	// Lazy-load by default for any unknown context.
	return 'lazy';
}

/**
 * Adds `loading` attribute to an `img` HTML tag.
 *
 * @since 5.5.0
 * @deprecated 6.3.0 Use wp_img_tag_add_loading_optimization_attrs() instead.
 * @see wp_img_tag_add_loading_optimization_attrs()
 *
 * @param string $image   The HTML `img` tag where the attribute should be added.
 * @param string $context Additional context to pass to the filters.
 * @return string Converted `img` tag with `loading` attribute added.
 */
function wp_img_tag_add_loading_attr( $image, $context ) {
	_deprecated_function( __FUNCTION__, '6.3.0', 'wp_img_tag_add_loading_optimization_attrs()' );
	/*
	 * Get loading attribute value to use. This must occur before the conditional check below so that even images that
	 * are ineligible for being lazy-loaded are considered.
	 */
	$value = wp_get_loading_attr_default( $context );

	// Images should have source and dimension attributes for the `loading` attribute to be added.
	if ( ! str_contains( $image, ' src="' ) || ! str_contains( $image, ' width="' ) || ! str_contains( $image, ' height="' ) ) {
		return $image;
	}

	/** This filter is documented in wp-admin/includes/media.php */
	$value = apply_filters( 'wp_img_tag_add_loading_attr', $value, $image, $context );

	if ( $value ) {
		if ( ! in_array( $value, array( 'lazy', 'eager' ), true ) ) {
			$value = 'lazy';
		}

		return str_replace( '<img', '<img loading="' . esc_attr( $value ) . '"', $image );
	}

	return $image;
}

/**
 * Takes input from [0, n] and returns it as [0, 1].
 *
 * Direct port of TinyColor's function, lightly simplified to maintain
 * consistency with TinyColor.
 *
 * @link https://github.com/bgrins/TinyColor
 *
 * @since 5.8.0
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param mixed $n   Number of unknown type.
 * @param int   $max Upper value of the range to bound to.
 * @return float Value in the range [0, 1].
 */
function wp_tinycolor_bound01( $n, $max ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );
	if ( 'string' === gettype( $n ) && str_contains( $n, '.' ) && 1 === (float) $n ) {
		$n = '100%';
	}

	$n = min( $max, max( 0, (float) $n ) );

	// Automatically convert percentage into number.
	if ( 'string' === gettype( $n ) && str_contains( $n, '%' ) ) {
		$n = (int) ( $n * $max ) / 100;
	}

	// Handle floating point rounding errors.
	if ( ( abs( $n - $max ) < 0.000001 ) ) {
		return 1.0;
	}

	// Convert into [0, 1] range if it isn't already.
	return ( $n % $max ) / (float) $max;
}

/**
 * Direct port of tinycolor's boundAlpha function to maintain consistency with
 * how tinycolor works.
 *
 * @link https://github.com/bgrins/TinyColor
 *
 * @since 5.9.0
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param mixed $n Number of unknown type.
 * @return float Value in the range [0,1].
 */
function _wp_tinycolor_bound_alpha( $n ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	if ( is_numeric( $n ) ) {
		$n = (float) $n;
		if ( $n >= 0 && $n <= 1 ) {
			return $n;
		}
	}
	return 1;
}

/**
 * Rounds and converts values of an RGB object.
 *
 * Direct port of TinyColor's function, lightly simplified to maintain
 * consistency with TinyColor.
 *
 * @link https://github.com/bgrins/TinyColor
 *
 * @since 5.8.0
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param array $rgb_color RGB object.
 * @return array Rounded and converted RGB object.
 */
function wp_tinycolor_rgb_to_rgb( $rgb_color ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	return array(
		'r' => wp_tinycolor_bound01( $rgb_color['r'], 255 ) * 255,
		'g' => wp_tinycolor_bound01( $rgb_color['g'], 255 ) * 255,
		'b' => wp_tinycolor_bound01( $rgb_color['b'], 255 ) * 255,
	);
}

/**
 * Helper function for hsl to rgb conversion.
 *
 * Direct port of TinyColor's function, lightly simplified to maintain
 * consistency with TinyColor.
 *
 * @link https://github.com/bgrins/TinyColor
 *
 * @since 5.8.0
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param float $p first component.
 * @param float $q second component.
 * @param float $t third component.
 * @return float R, G, or B component.
 */
function wp_tinycolor_hue_to_rgb( $p, $q, $t ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	if ( $t < 0 ) {
		++$t;
	}
	if ( $t > 1 ) {
		--$t;
	}
	if ( $t < 1 / 6 ) {
		return $p + ( $q - $p ) * 6 * $t;
	}
	if ( $t < 1 / 2 ) {
		return $q;
	}
	if ( $t < 2 / 3 ) {
		return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
	}
	return $p;
}

/**
 * Converts an HSL object to an RGB object with converted and rounded values.
 *
 * Direct port of TinyColor's function, lightly simplified to maintain
 * consistency with TinyColor.
 *
 * @link https://github.com/bgrins/TinyColor
 *
 * @since 5.8.0
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param array $hsl_color HSL object.
 * @return array Rounded and converted RGB object.
 */
function wp_tinycolor_hsl_to_rgb( $hsl_color ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	$h = wp_tinycolor_bound01( $hsl_color['h'], 360 );
	$s = wp_tinycolor_bound01( $hsl_color['s'], 100 );
	$l = wp_tinycolor_bound01( $hsl_color['l'], 100 );

	if ( 0 === $s ) {
		// Achromatic.
		$r = $l;
		$g = $l;
		$b = $l;
	} else {
		$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
		$p = 2 * $l - $q;
		$r = wp_tinycolor_hue_to_rgb( $p, $q, $h + 1 / 3 );
		$g = wp_tinycolor_hue_to_rgb( $p, $q, $h );
		$b = wp_tinycolor_hue_to_rgb( $p, $q, $h - 1 / 3 );
	}

	return array(
		'r' => $r * 255,
		'g' => $g * 255,
		'b' => $b * 255,
	);
}

/**
 * Parses hex, hsl, and rgb CSS strings using the same regex as TinyColor v1.4.2
 * used in the JavaScript. Only colors output from react-color are implemented.
 *
 * Direct port of TinyColor's function, lightly simplified to maintain
 * consistency with TinyColor.
 *
 * @link https://github.com/bgrins/TinyColor
 * @link https://github.com/casesandberg/react-color/
 *
 * @since 5.8.0
 * @since 5.9.0 Added alpha processing.
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param string $color_str CSS color string.
 * @return array RGB object.
 */
function wp_tinycolor_string_to_rgb( $color_str ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	$color_str = strtolower( trim( $color_str ) );

	$css_integer = '[-\\+]?\\d+%?';
	$css_number  = '[-\\+]?\\d*\\.\\d+%?';

	$css_unit = '(?:' . $css_number . ')|(?:' . $css_integer . ')';

	$permissive_match3 = '[\\s|\\(]+(' . $css_unit . ')[,|\\s]+(' . $css_unit . ')[,|\\s]+(' . $css_unit . ')\\s*\\)?';
	$permissive_match4 = '[\\s|\\(]+(' . $css_unit . ')[,|\\s]+(' . $css_unit . ')[,|\\s]+(' . $css_unit . ')[,|\\s]+(' . $css_unit . ')\\s*\\)?';

	$rgb_regexp = '/^rgb' . $permissive_match3 . '$/';
	if ( preg_match( $rgb_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_rgb_to_rgb(
			array(
				'r' => $match[1],
				'g' => $match[2],
				'b' => $match[3],
			)
		);

		$rgb['a'] = 1;

		return $rgb;
	}

	$rgba_regexp = '/^rgba' . $permissive_match4 . '$/';
	if ( preg_match( $rgba_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_rgb_to_rgb(
			array(
				'r' => $match[1],
				'g' => $match[2],
				'b' => $match[3],
			)
		);

		$rgb['a'] = _wp_tinycolor_bound_alpha( $match[4] );

		return $rgb;
	}

	$hsl_regexp = '/^hsl' . $permissive_match3 . '$/';
	if ( preg_match( $hsl_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_hsl_to_rgb(
			array(
				'h' => $match[1],
				's' => $match[2],
				'l' => $match[3],
			)
		);

		$rgb['a'] = 1;

		return $rgb;
	}

	$hsla_regexp = '/^hsla' . $permissive_match4 . '$/';
	if ( preg_match( $hsla_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_hsl_to_rgb(
			array(
				'h' => $match[1],
				's' => $match[2],
				'l' => $match[3],
			)
		);

		$rgb['a'] = _wp_tinycolor_bound_alpha( $match[4] );

		return $rgb;
	}

	$hex8_regexp = '/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/';
	if ( preg_match( $hex8_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_rgb_to_rgb(
			array(
				'r' => base_convert( $match[1], 16, 10 ),
				'g' => base_convert( $match[2], 16, 10 ),
				'b' => base_convert( $match[3], 16, 10 ),
			)
		);

		$rgb['a'] = _wp_tinycolor_bound_alpha(
			base_convert( $match[4], 16, 10 ) / 255
		);

		return $rgb;
	}

	$hex6_regexp = '/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/';
	if ( preg_match( $hex6_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_rgb_to_rgb(
			array(
				'r' => base_convert( $match[1], 16, 10 ),
				'g' => base_convert( $match[2], 16, 10 ),
				'b' => base_convert( $match[3], 16, 10 ),
			)
		);

		$rgb['a'] = 1;

		return $rgb;
	}

	$hex4_regexp = '/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/';
	if ( preg_match( $hex4_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_rgb_to_rgb(
			array(
				'r' => base_convert( $match[1] . $match[1], 16, 10 ),
				'g' => base_convert( $match[2] . $match[2], 16, 10 ),
				'b' => base_convert( $match[3] . $match[3], 16, 10 ),
			)
		);

		$rgb['a'] = _wp_tinycolor_bound_alpha(
			base_convert( $match[4] . $match[4], 16, 10 ) / 255
		);

		return $rgb;
	}

	$hex3_regexp = '/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/';
	if ( preg_match( $hex3_regexp, $color_str, $match ) ) {
		$rgb = wp_tinycolor_rgb_to_rgb(
			array(
				'r' => base_convert( $match[1] . $match[1], 16, 10 ),
				'g' => base_convert( $match[2] . $match[2], 16, 10 ),
				'b' => base_convert( $match[3] . $match[3], 16, 10 ),
			)
		);

		$rgb['a'] = 1;

		return $rgb;
	}

	/*
	 * The JS color picker considers the string "transparent" to be a hex value,
	 * so we need to handle it here as a special case.
	 */
	if ( 'transparent' === $color_str ) {
		return array(
			'r' => 0,
			'g' => 0,
			'b' => 0,
			'a' => 0,
		);
	}
}

/**
 * Returns the prefixed id for the duotone filter for use as a CSS id.
 *
 * @since 5.9.1
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param array $preset Duotone preset value as seen in theme.json.
 * @return string Duotone filter CSS id.
 */
function wp_get_duotone_filter_id( $preset ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );
	return WP_Duotone::get_filter_id_from_preset( $preset );
}

/**
 * Returns the CSS filter property url to reference the rendered SVG.
 *
 * @since 5.9.0
 * @since 6.1.0 Allow unset for preset colors.
 * @deprecated 6.3.0
 *
 * @access private
 *
 * @param array $preset Duotone preset value as seen in theme.json.
 * @return string Duotone CSS filter property url value.
 */
function wp_get_duotone_filter_property( $preset ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );
	return WP_Duotone::get_filter_css_property_value_from_preset( $preset );
}

/**
 * Returns the duotone filter SVG string for the preset.
 *
 * @since 5.9.1
 * @deprecated 6.3.0 Use WP_Duotone::get_filter_svg_from_preset() instead.
 *
 * @access private
 *
 * @param array $preset Duotone preset value as seen in theme.json.
 * @return string Duotone SVG filter.
 */
function wp_get_duotone_filter_svg( $preset ) {
	_deprecated_function( __FUNCTION__, '6.3.0', 'WP_Duotone::get_filter_svg_from_preset()' );
	return WP_Duotone::get_filter_svg_from_preset( $preset );
}

/**
 * Registers the style and colors block attributes for block types that support it.
 *
 * @since 5.8.0
 * @deprecated 6.3.0 Use WP_Duotone::register_duotone_support() instead.
 *
 * @access private
 *
 * @param WP_Block_Type $block_type Block Type.
 */
function wp_register_duotone_support( $block_type ) {
	_deprecated_function( __FUNCTION__, '6.3.0', 'WP_Duotone::register_duotone_support()' );
	return WP_Duotone::register_duotone_support( $block_type );
}

/**
 * Renders out the duotone stylesheet and SVG.
 *
 * @since 5.8.0
 * @since 6.1.0 Allow unset for preset colors.
 * @deprecated 6.3.0 Use WP_Duotone::render_duotone_support() instead.
 *
 * @access private
 *
 * @param string $block_content Rendered block content.
 * @param array  $block         Block object.
 * @return string Filtered block content.
 */
function wp_render_duotone_support( $block_content, $block ) {
	_deprecated_function( __FUNCTION__, '6.3.0', 'WP_Duotone::render_duotone_support()' );
	$wp_block = new WP_Block( $block );
	return WP_Duotone::render_duotone_support( $block_content, $block, $wp_block );
}

/**
 * Returns a string containing the SVGs to be referenced as filters (duotone).
 *
 * @since 5.9.1
 * @deprecated 6.3.0 SVG generation is handled on a per-block basis in block supports.
 *
 * @return string
 */
function wp_get_global_styles_svg_filters() {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	/*
	 * Ignore cache when the development mode is set to 'theme', so it doesn't interfere with the theme
	 * developer's workflow.
	 */
	$can_use_cached = ! wp_is_development_mode( 'theme' );
	$cache_group    = 'theme_json';
	$cache_key      = 'wp_get_global_styles_svg_filters';
	if ( $can_use_cached ) {
		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( $cached ) {
			return $cached;
		}
	}

	$supports_theme_json = wp_theme_has_theme_json();

	$origins = array( 'default', 'theme', 'custom' );
	if ( ! $supports_theme_json ) {
		$origins = array( 'default' );
	}

	$tree = WP_Theme_JSON_Resolver::get_merged_data();
	$svgs = $tree->get_svg_filters( $origins );

	if ( $can_use_cached ) {
		wp_cache_set( $cache_key, $svgs, $cache_group );
	}

	return $svgs;
}

/**
 * Renders the SVG filters supplied by theme.json.
 *
 * Note that this doesn't render the per-block user-defined
 * filters which are handled by wp_render_duotone_support,
 * but it should be rendered before the filtered content
 * in the body to satisfy Safari's rendering quirks.
 *
 * @since 5.9.1
 * @deprecated 6.3.0 SVG generation is handled on a per-block basis in block supports.
 */
function wp_global_styles_render_svg_filters() {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	/*
	 * When calling via the in_admin_header action, we only want to render the
	 * SVGs on block editor pages.
	 */
	if (
		is_admin() &&
		! get_current_screen()->is_block_editor()
	) {
		return;
	}

	$filters = wp_get_global_styles_svg_filters();
	if ( ! empty( $filters ) ) {
		echo $filters;
	}
}

/**
 * Build an array with CSS classes and inline styles defining the colors
 * which will be applied to the navigation markup in the front-end.
 *
 * @since 5.9.0
 * @deprecated 6.3.0 This was removed from the Navigation Submenu block in favour of `wp_apply_colors_support()`.
 *                   `wp_apply_colors_support()` returns an array with similar class and style values,
 *                   but with different keys: `class` and `style`.
 *
 * @param  array $context     Navigation block context.
 * @param  array $attributes  Block attributes.
 * @param  bool  $is_sub_menu Whether the block is a sub-menu.
 * @return array Colors CSS classes and inline styles.
 */
function block_core_navigation_submenu_build_css_colors( $context, $attributes, $is_sub_menu = false ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );
	$colors = array(
		'css_classes'   => array(),
		'inline_styles' => '',
	);

	// Text color.
	$named_text_color  = null;
	$custom_text_color = null;

	if ( $is_sub_menu && array_key_exists( 'customOverlayTextColor', $context ) ) {
		$custom_text_color = $context['customOverlayTextColor'];
	} elseif ( $is_sub_menu && array_key_exists( 'overlayTextColor', $context ) ) {
		$named_text_color = $context['overlayTextColor'];
	} elseif ( array_key_exists( 'customTextColor', $context ) ) {
		$custom_text_color = $context['customTextColor'];
	} elseif ( array_key_exists( 'textColor', $context ) ) {
		$named_text_color = $context['textColor'];
	} elseif ( isset( $context['style']['color']['text'] ) ) {
		$custom_text_color = $context['style']['color']['text'];
	}

	// If has text color.
	if ( ! is_null( $named_text_color ) ) {
		// Add the color class.
		array_push( $colors['css_classes'], 'has-text-color', sprintf( 'has-%s-color', $named_text_color ) );
	} elseif ( ! is_null( $custom_text_color ) ) {
		// Add the custom color inline style.
		$colors['css_classes'][]  = 'has-text-color';
		$colors['inline_styles'] .= sprintf( 'color: %s;', $custom_text_color );
	}

	// Background color.
	$named_background_color  = null;
	$custom_background_color = null;

	if ( $is_sub_menu && array_key_exists( 'customOverlayBackgroundColor', $context ) ) {
		$custom_background_color = $context['customOverlayBackgroundColor'];
	} elseif ( $is_sub_menu && array_key_exists( 'overlayBackgroundColor', $context ) ) {
		$named_background_color = $context['overlayBackgroundColor'];
	} elseif ( array_key_exists( 'customBackgroundColor', $context ) ) {
		$custom_background_color = $context['customBackgroundColor'];
	} elseif ( array_key_exists( 'backgroundColor', $context ) ) {
		$named_background_color = $context['backgroundColor'];
	} elseif ( isset( $context['style']['color']['background'] ) ) {
		$custom_background_color = $context['style']['color']['background'];
	}

	// If has background color.
	if ( ! is_null( $named_background_color ) ) {
		// Add the background-color class.
		array_push( $colors['css_classes'], 'has-background', sprintf( 'has-%s-background-color', $named_background_color ) );
	} elseif ( ! is_null( $custom_background_color ) ) {
		// Add the custom background-color inline style.
		$colors['css_classes'][]  = 'has-background';
		$colors['inline_styles'] .= sprintf( 'background-color: %s;', $custom_background_color );
	}

	return $colors;
}

/**
 * Runs the theme.json webfonts handler.
 *
 * Using `WP_Theme_JSON_Resolver`, it gets the fonts defined
 * in the `theme.json` for the current selection and style
 * variations, validates the font-face properties, generates
 * the '@font-face' style declarations, and then enqueues the
 * styles for both the editor and front-end.
 *
 * Design Notes:
 * This is not a public API, but rather an internal handler.
 * A future public Webfonts API will replace this stopgap code.
 *
 * This code design is intentional.
 *    a. It hides the inner-workings.
 *    b. It does not expose API ins or outs for consumption.
 *    c. It only works with a theme's `theme.json`.
 *
 * Why?
 *    a. To avoid backwards-compatibility issues when
 *       the Webfonts API is introduced in Core.
 *    b. To make `fontFace` declarations in `theme.json` work.
 *
 * @link  https://github.com/WordPress/gutenberg/issues/40472
 *
 * @since 6.0.0
 * @deprecated 6.4.0 Use wp_print_font_faces() instead.
 * @access private
 */
function _wp_theme_json_webfonts_handler() {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_print_font_faces' );

	// Block themes are unavailable during installation.
	if ( wp_installing() ) {
		return;
	}

	if ( ! wp_theme_has_theme_json() ) {
		return;
	}

	// Webfonts to be processed.
	$registered_webfonts = array();

	/**
	 * Gets the webfonts from theme.json.
	 *
	 * @since 6.0.0
	 *
	 * @return array Array of defined webfonts.
	 */
	$fn_get_webfonts_from_theme_json = static function() {
		// Get settings from theme.json.
		$settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();

		// If in the editor, add webfonts defined in variations.
		if ( is_admin() || wp_is_rest_endpoint() ) {
			$variations = WP_Theme_JSON_Resolver::get_style_variations();
			foreach ( $variations as $variation ) {
				// Skip if fontFamilies are not defined in the variation.
				if ( empty( $variation['settings']['typography']['fontFamilies'] ) ) {
					continue;
				}

				// Initialize the array structure.
				if ( empty( $settings['typography'] ) ) {
					$settings['typography'] = array();
				}
				if ( empty( $settings['typography']['fontFamilies'] ) ) {
					$settings['typography']['fontFamilies'] = array();
				}
				if ( empty( $settings['typography']['fontFamilies']['theme'] ) ) {
					$settings['typography']['fontFamilies']['theme'] = array();
				}

				// Combine variations with settings. Remove duplicates.
				$settings['typography']['fontFamilies']['theme'] = array_merge( $settings['typography']['fontFamilies']['theme'], $variation['settings']['typography']['fontFamilies']['theme'] );
				$settings['typography']['fontFamilies']          = array_unique( $settings['typography']['fontFamilies'] );
			}
		}

		// Bail out early if there are no settings for webfonts.
		if ( empty( $settings['typography']['fontFamilies'] ) ) {
			return array();
		}

		$webfonts = array();

		// Look for fontFamilies.
		foreach ( $settings['typography']['fontFamilies'] as $font_families ) {
			foreach ( $font_families as $font_family ) {

				// Skip if fontFace is not defined.
				if ( empty( $font_family['fontFace'] ) ) {
					continue;
				}

				// Skip if fontFace is not an array of webfonts.
				if ( ! is_array( $font_family['fontFace'] ) ) {
					continue;
				}

				$webfonts = array_merge( $webfonts, $font_family['fontFace'] );
			}
		}

		return $webfonts;
	};

	/**
	 * Transforms each 'src' into an URI by replacing 'file:./'
	 * placeholder from theme.json.
	 *
	 * The absolute path to the webfont file(s) cannot be defined in
	 * theme.json. `file:./` is the placeholder which is replaced by
	 * the theme's URL path to the theme's root.
	 *
	 * @since 6.0.0
	 *
	 * @param array $src Webfont file(s) `src`.
	 * @return array Webfont's `src` in URI.
	 */
	$fn_transform_src_into_uri = static function( array $src ) {
		foreach ( $src as $key => $url ) {
			// Tweak the URL to be relative to the theme root.
			if ( ! str_starts_with( $url, 'file:./' ) ) {
				continue;
			}

			$src[ $key ] = get_theme_file_uri( str_replace( 'file:./', '', $url ) );
		}

		return $src;
	};

	/**
	 * Converts the font-face properties (i.e. keys) into kebab-case.
	 *
	 * @since 6.0.0
	 *
	 * @param array $font_face Font face to convert.
	 * @return array Font faces with each property in kebab-case format.
	 */
	$fn_convert_keys_to_kebab_case = static function( array $font_face ) {
		foreach ( $font_face as $property => $value ) {
			$kebab_case               = _wp_to_kebab_case( $property );
			$font_face[ $kebab_case ] = $value;
			if ( $kebab_case !== $property ) {
				unset( $font_face[ $property ] );
			}
		}

		return $font_face;
	};

	/**
	 * Validates a webfont.
	 *
	 * @since 6.0.0
	 *
	 * @param array $webfont The webfont arguments.
	 * @return array|false The validated webfont arguments, or false if the webfont is invalid.
	 */
	$fn_validate_webfont = static function( $webfont ) {
		$webfont = wp_parse_args(
				$webfont,
				array(
						'font-family'  => '',
						'font-style'   => 'normal',
						'font-weight'  => '400',
						'font-display' => 'fallback',
						'src'          => array(),
				)
		);

		// Check the font-family.
		if ( empty( $webfont['font-family'] ) || ! is_string( $webfont['font-family'] ) ) {
			trigger_error( __( 'Webfont font family must be a non-empty string.' ) );

			return false;
		}

		// Check that the `src` property is defined and a valid type.
		if ( empty( $webfont['src'] ) || ( ! is_string( $webfont['src'] ) && ! is_array( $webfont['src'] ) ) ) {
			trigger_error( __( 'Webfont src must be a non-empty string or an array of strings.' ) );

			return false;
		}

		// Validate the `src` property.
		foreach ( (array) $webfont['src'] as $src ) {
			if ( ! is_string( $src ) || '' === trim( $src ) ) {
				trigger_error( __( 'Each webfont src must be a non-empty string.' ) );

				return false;
			}
		}

		// Check the font-weight.
		if ( ! is_string( $webfont['font-weight'] ) && ! is_int( $webfont['font-weight'] ) ) {
			trigger_error( __( 'Webfont font weight must be a properly formatted string or integer.' ) );

			return false;
		}

		// Check the font-display.
		if ( ! in_array( $webfont['font-display'], array( 'auto', 'block', 'fallback', 'optional', 'swap' ), true ) ) {
			$webfont['font-display'] = 'fallback';
		}

		$valid_props = array(
				'ascend-override',
				'descend-override',
				'font-display',
				'font-family',
				'font-stretch',
				'font-style',
				'font-weight',
				'font-variant',
				'font-feature-settings',
				'font-variation-settings',
				'line-gap-override',
				'size-adjust',
				'src',
				'unicode-range',
		);

		foreach ( $webfont as $prop => $value ) {
			if ( ! in_array( $prop, $valid_props, true ) ) {
				unset( $webfont[ $prop ] );
			}
		}

		return $webfont;
	};

	/**
	 * Registers webfonts declared in theme.json.
	 *
	 * @since 6.0.0
	 *
	 * @uses $registered_webfonts To access and update the registered webfonts registry (passed by reference).
	 * @uses $fn_get_webfonts_from_theme_json To run the function that gets the webfonts from theme.json.
	 * @uses $fn_convert_keys_to_kebab_case To run the function that converts keys into kebab-case.
	 * @uses $fn_validate_webfont To run the function that validates each font-face (webfont) from theme.json.
	 */
	$fn_register_webfonts = static function() use ( &$registered_webfonts, $fn_get_webfonts_from_theme_json, $fn_convert_keys_to_kebab_case, $fn_validate_webfont, $fn_transform_src_into_uri ) {
		$registered_webfonts = array();

		foreach ( $fn_get_webfonts_from_theme_json() as $webfont ) {
			if ( ! is_array( $webfont ) ) {
				continue;
			}

			$webfont = $fn_convert_keys_to_kebab_case( $webfont );

			$webfont = $fn_validate_webfont( $webfont );

			$webfont['src'] = $fn_transform_src_into_uri( (array) $webfont['src'] );

			// Skip if not valid.
			if ( empty( $webfont ) ) {
				continue;
			}

			$registered_webfonts[] = $webfont;
		}
	};

	/**
	 * Orders 'src' items to optimize for browser support.
	 *
	 * @since 6.0.0
	 *
	 * @param array $webfont Webfont to process.
	 * @return array Ordered `src` items.
	 */
	$fn_order_src = static function( array $webfont ) {
		$src         = array();
		$src_ordered = array();

		foreach ( $webfont['src'] as $url ) {
			// Add data URIs first.
			if ( str_starts_with( trim( $url ), 'data:' ) ) {
				$src_ordered[] = array(
						'url'    => $url,
						'format' => 'data',
				);
				continue;
			}
			$format         = pathinfo( $url, PATHINFO_EXTENSION );
			$src[ $format ] = $url;
		}

		// Add woff2.
		if ( ! empty( $src['woff2'] ) ) {
			$src_ordered[] = array(
					'url'    => sanitize_url( $src['woff2'] ),
					'format' => 'woff2',
			);
		}

		// Add woff.
		if ( ! empty( $src['woff'] ) ) {
			$src_ordered[] = array(
					'url'    => sanitize_url( $src['woff'] ),
					'format' => 'woff',
			);
		}

		// Add ttf.
		if ( ! empty( $src['ttf'] ) ) {
			$src_ordered[] = array(
					'url'    => sanitize_url( $src['ttf'] ),
					'format' => 'truetype',
			);
		}

		// Add eot.
		if ( ! empty( $src['eot'] ) ) {
			$src_ordered[] = array(
					'url'    => sanitize_url( $src['eot'] ),
					'format' => 'embedded-opentype',
			);
		}

		// Add otf.
		if ( ! empty( $src['otf'] ) ) {
			$src_ordered[] = array(
					'url'    => sanitize_url( $src['otf'] ),
					'format' => 'opentype',
			);
		}
		$webfont['src'] = $src_ordered;

		return $webfont;
	};

	/**
	 * Compiles the 'src' into valid CSS.
	 *
	 * @since 6.0.0
	 * @since 6.2.0 Removed local() CSS.
	 *
	 * @param string $font_family Font family.
	 * @param array  $value       Value to process.
	 * @return string The CSS.
	 */
	$fn_compile_src = static function( $font_family, array $value ) {
		$src = '';

		foreach ( $value as $item ) {
			$src .= ( 'data' === $item['format'] )
					? ", url({$item['url']})"
					: ", url('{$item['url']}') format('{$item['format']}')";
		}

		$src = ltrim( $src, ', ' );

		return $src;
	};

	/**
	 * Compiles the font variation settings.
	 *
	 * @since 6.0.0
	 *
	 * @param array $font_variation_settings Array of font variation settings.
	 * @return string The CSS.
	 */
	$fn_compile_variations = static function( array $font_variation_settings ) {
		$variations = '';

		foreach ( $font_variation_settings as $key => $value ) {
			$variations .= "$key $value";
		}

		return $variations;
	};

	/**
	 * Builds the font-family's CSS.
	 *
	 * @since 6.0.0
	 *
	 * @uses $fn_compile_src To run the function that compiles the src.
	 * @uses $fn_compile_variations To run the function that compiles the variations.
	 *
	 * @param array $webfont Webfont to process.
	 * @return string This font-family's CSS.
	 */
	$fn_build_font_face_css = static function( array $webfont ) use ( $fn_compile_src, $fn_compile_variations ) {
		$css = '';

		// Wrap font-family in quotes if it contains spaces.
		if (
				str_contains( $webfont['font-family'], ' ' ) &&
				! str_contains( $webfont['font-family'], '"' ) &&
				! str_contains( $webfont['font-family'], "'" )
		) {
			$webfont['font-family'] = '"' . $webfont['font-family'] . '"';
		}

		foreach ( $webfont as $key => $value ) {
			/*
			 * Skip "provider", since it's for internal API use,
			 * and not a valid CSS property.
			 */
			if ( 'provider' === $key ) {
				continue;
			}

			// Compile the "src" parameter.
			if ( 'src' === $key ) {
				$value = $fn_compile_src( $webfont['font-family'], $value );
			}

			// If font-variation-settings is an array, convert it to a string.
			if ( 'font-variation-settings' === $key && is_array( $value ) ) {
				$value = $fn_compile_variations( $value );
			}

			if ( ! empty( $value ) ) {
				$css .= "$key:$value;";
			}
		}

		return $css;
	};

	/**
	 * Gets the '@font-face' CSS styles for locally-hosted font files.
	 *
	 * @since 6.0.0
	 *
	 * @uses $registered_webfonts To access and update the registered webfonts registry (passed by reference).
	 * @uses $fn_order_src To run the function that orders the src.
	 * @uses $fn_build_font_face_css To run the function that builds the font-face CSS.
	 *
	 * @return string The `@font-face` CSS.
	 */
	$fn_get_css = static function() use ( &$registered_webfonts, $fn_order_src, $fn_build_font_face_css ) {
		$css = '';

		foreach ( $registered_webfonts as $webfont ) {
			// Order the webfont's `src` items to optimize for browser support.
			$webfont = $fn_order_src( $webfont );

			// Build the @font-face CSS for this webfont.
			$css .= '@font-face{' . $fn_build_font_face_css( $webfont ) . '}';
		}

		return $css;
	};

	/**
	 * Generates and enqueues webfonts styles.
	 *
	 * @since 6.0.0
	 *
	 * @uses $fn_get_css To run the function that gets the CSS.
	 */
	$fn_generate_and_enqueue_styles = static function() use ( $fn_get_css ) {
		// Generate the styles.
		$styles = $fn_get_css();

		// Bail out if there are no styles to enqueue.
		if ( '' === $styles ) {
			return;
		}

		// Enqueue the stylesheet.
		wp_register_style( 'wp-webfonts', '' );
		wp_enqueue_style( 'wp-webfonts' );

		// Add the styles to the stylesheet.
		wp_add_inline_style( 'wp-webfonts', $styles );
	};

	/**
	 * Generates and enqueues editor styles.
	 *
	 * @since 6.0.0
	 *
	 * @uses $fn_get_css To run the function that gets the CSS.
	 */
	$fn_generate_and_enqueue_editor_styles = static function() use ( $fn_get_css ) {
		// Generate the styles.
		$styles = $fn_get_css();

		// Bail out if there are no styles to enqueue.
		if ( '' === $styles ) {
			return;
		}

		wp_add_inline_style( 'wp-block-library', $styles );
	};

	add_action( 'wp_loaded', $fn_register_webfonts );
	add_action( 'wp_enqueue_scripts', $fn_generate_and_enqueue_styles );
	add_action( 'admin_init', $fn_generate_and_enqueue_editor_styles );
}

/**
 * Prints the CSS in the embed iframe header.
 *
 * @since 4.4.0
 * @deprecated 6.4.0 Use wp_enqueue_embed_styles() instead.
 */
function print_embed_styles() {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_enqueue_embed_styles' );

	$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
	$suffix    = SCRIPT_DEBUG ? '' : '.min';
	?>
	<style<?php echo $type_attr; ?>>
		<?php echo file_get_contents( ABSPATH . WPINC . "/css/wp-embed-template$suffix.css" ); ?>
	</style>
	<?php
}

/**
 * Prints the important emoji-related styles.
 *
 * @since 4.2.0
 * @deprecated 6.4.0 Use wp_enqueue_emoji_styles() instead.
 */
function print_emoji_styles() {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_enqueue_emoji_styles' );
	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;

	$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
	?>
	<style<?php echo $type_attr; ?>>
	img.wp-smiley,
	img.emoji {
		display: inline !important;
		border: none !important;
		box-shadow: none !important;
		height: 1em !important;
		width: 1em !important;
		margin: 0 0.07em !important;
		vertical-align: -0.1em !important;
		background: none !important;
		padding: 0 !important;
	}
	</style>
	<?php
}

/**
 * Prints style and scripts for the admin bar.
 *
 * @since 3.1.0
 * @deprecated 6.4.0 Use wp_enqueue_admin_bar_header_styles() instead.
 */
function wp_admin_bar_header() {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_enqueue_admin_bar_header_styles' );
	$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
	?>
	<style<?php echo $type_attr; ?> media="print">#wpadminbar { display:none; }</style>
	<?php
}

/**
 * Prints default admin bar callback.
 *
 * @since 3.1.0
 * @deprecated 6.4.0 Use wp_enqueue_admin_bar_bump_styles() instead.
 */
function _admin_bar_bump_cb() {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_enqueue_admin_bar_bump_styles' );
	$type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
	?>
	<style<?php echo $type_attr; ?> media="screen">
	html { margin-top: 32px !important; }
	@media screen and ( max-width: 782px ) {
	  html { margin-top: 46px !important; }
	}
	</style>
	<?php
}

/**
 * Runs a remote HTTPS request to detect whether HTTPS supported, and stores potential errors.
 *
 * This internal function is called by a regular Cron hook to ensure HTTPS support is detected and maintained.
 *
 * @since 5.7.0
 * @deprecated 6.4.0 The `wp_update_https_detection_errors()` function is no longer used and has been replaced by
 *                   `wp_get_https_detection_errors()`. Previously the function was called by a regular Cron hook to
 *                    update the `https_detection_errors` option, but this is no longer necessary as the errors are
 *                    retrieved directly in Site Health and no longer used outside of Site Health.
 * @access private
 */
function wp_update_https_detection_errors() {
	_deprecated_function( __FUNCTION__, '6.4.0' );

	/**
	 * Short-circuits the process of detecting errors related to HTTPS support.
	 *
	 * Returning a `WP_Error` from the filter will effectively short-circuit the default logic of trying a remote
	 * request to the site over HTTPS, storing the errors array from the returned `WP_Error` instead.
	 *
	 * @since 5.7.0
	 * @deprecated 6.4.0 The `wp_update_https_detection_errors` filter is no longer used and has been replaced by `pre_wp_get_https_detection_errors`.
	 *
	 * @param null|WP_Error $pre Error object to short-circuit detection,
	 *                           or null to continue with the default behavior.
	 */
	$support_errors = apply_filters( 'pre_wp_update_https_detection_errors', null );
	if ( is_wp_error( $support_errors ) ) {
		update_option( 'https_detection_errors', $support_errors->errors, false );
		return;
	}

	$support_errors = wp_get_https_detection_errors();

	update_option( 'https_detection_errors', $support_errors );
}

/**
 * Adds `decoding` attribute to an `img` HTML tag.
 *
 * The `decoding` attribute allows developers to indicate whether the
 * browser can decode the image off the main thread (`async`), on the
 * main thread (`sync`) or as determined by the browser (`auto`).
 *
 * By default WordPress adds `decoding="async"` to images but developers
 * can use the {@see 'wp_img_tag_add_decoding_attr'} filter to modify this
 * to remove the attribute or set it to another accepted value.
 *
 * @since 6.1.0
 * @deprecated 6.4.0 Use wp_img_tag_add_loading_optimization_attrs() instead.
 * @see wp_img_tag_add_loading_optimization_attrs()
 *
 * @param string $image   The HTML `img` tag where the attribute should be added.
 * @param string $context Additional context to pass to the filters.
 * @return string Converted `img` tag with `decoding` attribute added.
 */
function wp_img_tag_add_decoding_attr( $image, $context ) {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_img_tag_add_loading_optimization_attrs()' );

	/*
	 * Only apply the decoding attribute to images that have a src attribute that
	 * starts with a double quote, ensuring escaped JSON is also excluded.
	 */
	if ( ! str_contains( $image, ' src="' ) ) {
		return $image;
	}

	/** This action is documented in wp-includes/media.php */
	$value = apply_filters( 'wp_img_tag_add_decoding_attr', 'async', $image, $context );

	if ( in_array( $value, array( 'async', 'sync', 'auto' ), true ) ) {
		$image = str_replace( '<img ', '<img decoding="' . esc_attr( $value ) . '" ', $image );
	}

	return $image;
}

/**
 * Parses wp_template content and injects the active theme's
 * stylesheet as a theme attribute into each wp_template_part
 *
 * @since 5.9.0
 * @deprecated 6.4.0 Use traverse_and_serialize_blocks( parse_blocks( $template_content ), '_inject_theme_attribute_in_template_part_block' ) instead.
 * @access private
 *
 * @param string $template_content serialized wp_template content.
 * @return string Updated 'wp_template' content.
 */
function _inject_theme_attribute_in_block_template_content( $template_content ) {
	_deprecated_function(
		__FUNCTION__,
		'6.4.0',
		'traverse_and_serialize_blocks( parse_blocks( $template_content ), "_inject_theme_attribute_in_template_part_block" )'
	);

	$has_updated_content = false;
	$new_content         = '';
	$template_blocks     = parse_blocks( $template_content );

	$blocks = _flatten_blocks( $template_blocks );
	foreach ( $blocks as &$block ) {
		if (
			'core/template-part' === $block['blockName'] &&
			! isset( $block['attrs']['theme'] )
		) {
			$block['attrs']['theme'] = get_stylesheet();
			$has_updated_content     = true;
		}
	}

	if ( $has_updated_content ) {
		foreach ( $template_blocks as &$block ) {
			$new_content .= serialize_block( $block );
		}

		return $new_content;
	}

	return $template_content;
}

/**
 * Parses a block template and removes the theme attribute from each template part.
 *
 * @since 5.9.0
 * @deprecated 6.4.0 Use traverse_and_serialize_blocks( parse_blocks( $template_content ), '_remove_theme_attribute_from_template_part_block' ) instead.
 * @access private
 *
 * @param string $template_content Serialized block template content.
 * @return string Updated block template content.
 */
function _remove_theme_attribute_in_block_template_content( $template_content ) {
	_deprecated_function(
		__FUNCTION__,
		'6.4.0',
		'traverse_and_serialize_blocks( parse_blocks( $template_content ), "_remove_theme_attribute_from_template_part_block" )'
	);

	$has_updated_content = false;
	$new_content         = '';
	$template_blocks     = parse_blocks( $template_content );

	$blocks = _flatten_blocks( $template_blocks );
	foreach ( $blocks as $key => $block ) {
		if ( 'core/template-part' === $block['blockName'] && isset( $block['attrs']['theme'] ) ) {
			unset( $blocks[ $key ]['attrs']['theme'] );
			$has_updated_content = true;
		}
	}

	if ( ! $has_updated_content ) {
		return $template_content;
	}

	foreach ( $template_blocks as $block ) {
		$new_content .= serialize_block( $block );
	}

	return $new_content;
}

/**
 * Prints the skip-link script & styles.
 *
 * @since 5.8.0
 * @access private
 * @deprecated 6.4.0 Use wp_enqueue_block_template_skip_link() instead.
 *
 * @global string $_wp_current_template_content
 */
function the_block_template_skip_link() {
	_deprecated_function( __FUNCTION__, '6.4.0', 'wp_enqueue_block_template_skip_link()' );

	global $_wp_current_template_content;

	// Early exit if not a block theme.
	if ( ! current_theme_supports( 'block-templates' ) ) {
		return;
	}

	// Early exit if not a block template.
	if ( ! $_wp_current_template_content ) {
		return;
	}
	?>

	<?php
	/**
	 * Print the skip-link styles.
	 */
	?>
	<style id="skip-link-styles">
		.skip-link.screen-reader-text {
			border: 0;
			clip-path: inset(50%);
			height: 1px;
			margin: -1px;
			overflow: hidden;
			padding: 0;
			position: absolute !important;
			width: 1px;
			word-wrap: normal !important;
		}

		.skip-link.screen-reader-text:focus {
			background-color: #eee;
			clip-path: none;
			color: #444;
			display: block;
			font-size: 1em;
			height: auto;
			left: 5px;
			line-height: normal;
			padding: 15px 23px 14px;
			text-decoration: none;
			top: 5px;
			width: auto;
			z-index: 100000;
		}
	</style>
	<?php
	/**
	 * Print the skip-link script.
	 */
	?>
	<script>
	( function() {
		var skipLinkTarget = document.querySelector( 'main' ),
			sibling,
			skipLinkTargetID,
			skipLink;

		// Early exit if a skip-link target can't be located.
		if ( ! skipLinkTarget ) {
			return;
		}

		/*
		 * Get the site wrapper.
		 * The skip-link will be injected in the beginning of it.
		 */
		sibling = document.querySelector( '.wp-site-blocks' );

		// Early exit if the root element was not found.
		if ( ! sibling ) {
			return;
		}

		// Get the skip-link target's ID, and generate one if it doesn't exist.
		skipLinkTargetID = skipLinkTarget.id;
		if ( ! skipLinkTargetID ) {
			skipLinkTargetID = 'wp--skip-link--target';
			skipLinkTarget.id = skipLinkTargetID;
		}

		// Create the skip link.
		skipLink = document.createElement( 'a' );
		skipLink.classList.add( 'skip-link', 'screen-reader-text' );
		skipLink.href = '#' + skipLinkTargetID;
		skipLink.innerHTML = '<?php /* translators: Hidden accessibility text. */ esc_html_e( 'Skip to content' ); ?>';

		// Inject the skip link.
		sibling.parentElement.insertBefore( skipLink, sibling );
	}() );
	</script>
	<?php
}

/**
 * Ensure that the view script has the `wp-interactivity` dependency.
 *
 * @since 6.4.0
 * @deprecated 6.5.0
 */
function block_core_query_ensure_interactivity_dependency() {
	_deprecated_function( __FUNCTION__, '6.5.0', 'wp_register_script_module' );
}

/**
 * Ensure that the view script has the `wp-interactivity` dependency.
 *
 * @since 6.4.0
 * @deprecated 6.5.0
 */
function block_core_file_ensure_interactivity_dependency() {
	_deprecated_function( __FUNCTION__, '6.5.0', 'wp_register_script_module' );
}

/**
 * Ensures that the view script has the `wp-interactivity` dependency.
 *
 * @since 6.4.0
 * @deprecated 6.5.0
 */
function block_core_image_ensure_interactivity_dependency() {
	_deprecated_function( __FUNCTION__, '6.5.0', 'wp_register_script_module' );
}

/**
 * Updates the block content with elements class names.
 *
 * @deprecated 6.6.0 Generation of element class name is handled via `render_block_data` filter.
 *
 * @since 5.8.0
 * @since 6.4.0 Added support for button and heading element styling.
 * @access private
 *
 * @param string $block_content Rendered block content.
 * @param array  $block         Block object.
 * @return string Filtered block content.
 */
function wp_render_elements_support( $block_content, $block ) {
	_deprecated_function( __FUNCTION__, '6.6.0', 'wp_render_elements_class_name' );
	return $block_content;
}

/**
 * Processes the directives on the rendered HTML of the interactive blocks.
 *
 * This processes only one root interactive block at a time because the
 * rendered HTML of that block contains the rendered HTML of all its inner
 * blocks, including any interactive block. It does so by ignoring all the
 * interactive inner blocks until the root interactive block is processed.
 *
 * @since 6.5.0
 * @deprecated 6.6.0
 *
 * @param array $parsed_block The parsed block.
 * @return array The same parsed block.
 */
function wp_interactivity_process_directives_of_interactive_blocks( array $parsed_block ): array {
	_deprecated_function( __FUNCTION__, '6.6.0' );
	return $parsed_block;
}

/**
 * Gets the global styles custom CSS from theme.json.
 *
 * @since 6.2.0
 * @deprecated 6.7.0 Use {@see 'wp_get_global_stylesheet'} instead for top-level custom CSS, or {@see 'WP_Theme_JSON::get_styles_for_block'} for block-level custom CSS.
 *
 * @return string The global styles custom CSS.
 */
function wp_get_global_styles_custom_css() {
	_deprecated_function( __FUNCTION__, '6.7.0', 'wp_get_global_stylesheet' );
	if ( ! wp_theme_has_theme_json() ) {
		return '';
	}
	/*
	 * Ignore cache when the development mode is set to 'theme', so it doesn't interfere with the theme
	 * developer's workflow.
	 */
	$can_use_cached = ! wp_is_development_mode( 'theme' );

	/*
	 * By using the 'theme_json' group, this data is marked to be non-persistent across requests.
	 * @see `wp_cache_add_non_persistent_groups()`.
	 *
	 * The rationale for this is to make sure derived data from theme.json
	 * is always fresh from the potential modifications done via hooks
	 * that can use dynamic data (modify the stylesheet depending on some option,
	 * settings depending on user permissions, etc.).
	 * See some of the existing hooks to modify theme.json behavior:
	 * @see https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
	 *
	 * A different alternative considered was to invalidate the cache upon certain
	 * events such as options add/update/delete, user meta, etc.
	 * It was judged not enough, hence this approach.
	 * @see https://github.com/WordPress/gutenberg/pull/45372
	 */
	$cache_key   = 'wp_get_global_styles_custom_css';
	$cache_group = 'theme_json';
	if ( $can_use_cached ) {
		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( $cached ) {
			return $cached;
		}
	}

	$tree       = WP_Theme_JSON_Resolver::get_merged_data();
	$stylesheet = $tree->get_custom_css();

	if ( $can_use_cached ) {
		wp_cache_set( $cache_key, $stylesheet, $cache_group );
	}

	return $stylesheet;
}

/**
 * Enqueues the global styles custom css defined via theme.json.
 *
 * @since 6.2.0
 * @deprecated 6.7.0 Use {@see 'wp_enqueue_global_styles'} instead.
 */
function wp_enqueue_global_styles_custom_css() {
	_deprecated_function( __FUNCTION__, '6.7.0', 'wp_enqueue_global_styles' );
	if ( ! wp_is_block_theme() ) {
		return;
	}

	// Don't enqueue Customizer's custom CSS separately.
	remove_action( 'wp_head', 'wp_custom_css_cb', 101 );

	$custom_css  = wp_get_custom_css();
	$custom_css .= wp_get_global_styles_custom_css();

	if ( ! empty( $custom_css ) ) {
		wp_add_inline_style( 'global-styles', $custom_css );
	}
}

/**
 * Generate block style variation instance name.
 *
 * @since 6.6.0
 * @deprecated 6.7.0 Use `wp_unique_id( $variation . '--' )` instead.
 *
 * @access private
 *
 * @param array  $block     Block object.
 * @param string $variation Slug for the block style variation.
 *
 * @return string The unique variation name.
 */
function wp_create_block_style_variation_instance_name( $block, $variation ) {
	_deprecated_function( __FUNCTION__, '6.7.0', 'wp_unique_id' );
	return $variation . '--' . md5( serialize( $block ) );
}

/**
 * Returns whether the current user has the specified capability for a given site.
 *
 * @since 3.0.0
 * @since 5.3.0 Formalized the existing and already documented `...$args` parameter
 *              by adding it to the function signature.
 * @since 5.8.0 Wraps current_user_can() after switching to blog.
 * @deprecated 6.7.0 Use current_user_can_for_site() instead.
 *
 * @param int    $blog_id    Site ID.
 * @param string $capability Capability name.
 * @param mixed  ...$args    Optional further parameters, typically starting with an object ID.
 * @return bool Whether the user has the given capability.
 */
function current_user_can_for_blog( $blog_id, $capability, ...$args ) {
	return current_user_can_for_site( $blog_id, $capability, ...$args );
}

/**
 * Loads classic theme styles on classic themes in the editor.
 *
 * This is used for backwards compatibility for Button and File blocks specifically.
 *
 * @since 6.1.0
 * @since 6.2.0 Added File block styles.
 * @deprecated 6.8.0 Styles are enqueued, not printed in the body element.
 *
 * @param array $editor_settings The array of editor settings.
 * @return array A filtered array of editor settings.
 */
function wp_add_editor_classic_theme_styles( $editor_settings ) {
	_deprecated_function( __FUNCTION__, '6.8.0', 'wp_enqueue_classic_theme_styles' );

	if ( wp_theme_has_theme_json() ) {
		return $editor_settings;
	}

	$suffix               = wp_scripts_get_suffix();
	$classic_theme_styles = ABSPATH . WPINC . "/css/classic-themes$suffix.css";

	/*
	 * This follows the pattern of get_block_editor_theme_styles,
	 * but we can't use get_block_editor_theme_styles directly as it
	 * only handles external files or theme files.
	 */
	$classic_theme_styles_settings = array(
		'css'            => file_get_contents( $classic_theme_styles ),
		'__unstableType' => 'core',
		'isGlobalStyles' => false,
	);

	// Add these settings to the start of the array so that themes can override them.
	array_unshift( $editor_settings['styles'], $classic_theme_styles_settings );

	return $editor_settings;
}