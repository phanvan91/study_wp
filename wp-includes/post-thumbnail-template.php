<?php
/**
 * Các hàm Template Ảnh đại diện Bài viết WordPress.
 *
 * Hỗ trợ ảnh đại diện bài viết.
 * Tệp functions.php của theme phải gọi add_theme_support( 'post-thumbnails' ) để sử dụng.
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Xác định xem bài viết có hình ảnh đính kèm hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Thẻ điều kiện} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.9.0
 * @since 4.4.0 `$post` có thể là ID bài viết hoặc đối tượng WP_Post.
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
 * @return bool Bài viết có hình ảnh đính kèm hay không.
 */
function has_post_thumbnail( $post = null ) {
	$thumbnail_id  = get_post_thumbnail_id( $post );
	$has_thumbnail = (bool) $thumbnail_id;

	/**
	 * Lọc kết quả kiểm tra bài viết có ảnh đại diện hay không.
	 *
	 * @since 5.1.0
	 *
	 * @param bool             $has_thumbnail true nếu bài viết có ảnh đại diện, ngược lại false.
	 * @param int|WP_Post|null $post          ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
	 * @param int|false        $thumbnail_id  ID ảnh đại diện hoặc false nếu bài viết không tồn tại.
	 */
	return (bool) apply_filters( 'has_post_thumbnail', $has_thumbnail, $post, $thumbnail_id );
}

/**
 * Lấy ID ảnh đại diện của bài viết.
 *
 * @since 2.9.0
 * @since 4.4.0 `$post` có thể là ID bài viết hoặc đối tượng WP_Post.
 * @since 5.5.0 Giá trị trả về cho bài viết không tồn tại
 *              đã được thay đổi thành false thay vì chuỗi rỗng.
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
 * @return int|false ID ảnh đại diện bài viết (có thể là 0 nếu chưa đặt ảnh đại diện),
 *                   hoặc false nếu bài viết không tồn tại.
 */
function get_post_thumbnail_id( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$thumbnail_id = (int) get_post_meta( $post->ID, '_thumbnail_id', true );

	/**
	 * Lọc ID ảnh đại diện bài viết.
	 *
	 * @since 5.9.0
	 *
	 * @param int|false        $thumbnail_id ID ảnh đại diện hoặc false nếu bài viết không tồn tại.
	 * @param int|WP_Post|null $post         ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
	 */
	return (int) apply_filters( 'post_thumbnail_id', $thumbnail_id, $post );
}

/**
 * Hiển thị ảnh đại diện bài viết.
 *
 * Khi theme thêm hỗ trợ 'post-thumbnail', một kích thước hình ảnh đặc biệt 'post-thumbnail'
 * được đăng ký, khác với kích thước hình ảnh 'thumbnail' được quản lý qua
 * màn hình Cài đặt > Phương tiện.
 *
 * Khi sử dụng the_post_thumbnail() hoặc các hàm liên quan, kích thước hình ảnh 'post-thumbnail'
 * được sử dụng mặc định, mặc dù có thể chỉ định kích thước khác nếu cần.
 *
 * @since 2.9.0
 *
 * @see get_the_post_thumbnail()
 *
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh. Chấp nhận bất kỳ tên kích thước hình ảnh đã đăng ký,
 *                           hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
 *                           Mặc định 'post-thumbnail'.
 * @param string|array $attr Tùy chọn. Chuỗi truy vấn hoặc mảng thuộc tính. Mặc định rỗng.
 */
function the_post_thumbnail( $size = 'post-thumbnail', $attr = '' ) {
	echo get_the_post_thumbnail( null, $size, $attr );
}

/**
 * Cập nhật cache cho ảnh đại diện trong vòng lặp hiện tại.
 *
 * @since 3.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param WP_Query|null $wp_query Tùy chọn. Một thể hiện WP_Query. Mặc định là biến toàn cục $wp_query.
 */
function update_post_thumbnail_cache( $wp_query = null ) {
	if ( ! $wp_query ) {
		$wp_query = $GLOBALS['wp_query'];
	}

	if ( $wp_query->thumbnails_cached ) {
		return;
	}

	$thumb_ids = array();

	/*
	 * $wp_query có thể chứa mảng đối tượng bài viết hoặc ID bài viết.
	 *
	 * Điều này đảm bảo cache được chuẩn bị cho tất cả đối tượng bài viết để tránh
	 * các lệnh gọi `get_post()` trong `get_the_post_thumbnail()` kích hoạt
	 * truy vấn cơ sở dữ liệu bổ sung cho mỗi bài viết.
	 */
	$parent_post_ids = array();
	foreach ( $wp_query->posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$parent_post_ids[] = $post->ID;
		} elseif ( is_int( $post ) ) {
			$parent_post_ids[] = $post;
		}
	}
	_prime_post_caches( $parent_post_ids, false, true );

	foreach ( $wp_query->posts as $post ) {
		$id = get_post_thumbnail_id( $post );
		if ( $id ) {
			$thumb_ids[] = $id;
		}
	}

	if ( ! empty( $thumb_ids ) ) {
		_prime_post_caches( $thumb_ids, false, true );
	}

	$wp_query->thumbnails_cached = true;
}

/**
 * Lấy ảnh đại diện bài viết.
 *
 * Khi theme thêm hỗ trợ 'post-thumbnail', một kích thước hình ảnh đặc biệt 'post-thumbnail'
 * được đăng ký, khác với kích thước hình ảnh 'thumbnail' được quản lý qua
 * màn hình Cài đặt > Phương tiện.
 *
 * Khi sử dụng the_post_thumbnail() hoặc các hàm liên quan, kích thước hình ảnh 'post-thumbnail'
 * được sử dụng mặc định, mặc dù có thể chỉ định kích thước khác nếu cần.
 *
 * @since 2.9.0
 * @since 4.4.0 `$post` có thể là ID bài viết hoặc đối tượng WP_Post.
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
 * @param string|int[]     $size Tùy chọn. Kích thước hình ảnh. Chấp nhận bất kỳ tên kích thước hình ảnh đã đăng ký,
 *                               hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
 *                               Mặc định 'post-thumbnail'.
 * @param string|array     $attr Tùy chọn. Chuỗi truy vấn hoặc mảng thuộc tính. Mặc định rỗng.
 * @return string Thẻ hình ảnh ảnh đại diện bài viết.
 */
function get_the_post_thumbnail( $post = null, $size = 'post-thumbnail', $attr = '' ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$post_thumbnail_id = get_post_thumbnail_id( $post );

	/**
	 * Lọc kích thước ảnh đại diện bài viết.
	 *
	 * @since 2.9.0
	 * @since 4.9.0 Thêm tham số `$post_id`.
	 *
	 * @param string|int[] $size    Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
	 *                              hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param int          $post_id ID bài viết.
	 */
	$size = apply_filters( 'post_thumbnail_size', $size, $post->ID );

	if ( $post_thumbnail_id ) {

		/**
		 * Kích hoạt trước khi lấy HTML ảnh đại diện bài viết.
		 *
		 * Cung cấp bộ lọc "đúng lúc" cho tất cả bộ lọc trong wp_get_attachment_image().
		 *
		 * @since 2.9.0
		 *
		 * @param int          $post_id           ID bài viết.
		 * @param int          $post_thumbnail_id ID ảnh đại diện bài viết.
		 * @param string|int[] $size              Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
		 *                                        hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
		 */
		do_action( 'begin_fetch_post_thumbnail_html', $post->ID, $post_thumbnail_id, $size );

		if ( in_the_loop() ) {
			update_post_thumbnail_cache();
		}

		$html = wp_get_attachment_image( $post_thumbnail_id, $size, false, $attr );

		/**
		 * Kích hoạt sau khi lấy HTML ảnh đại diện bài viết.
		 *
		 * @since 2.9.0
		 *
		 * @param int          $post_id           ID bài viết.
		 * @param int          $post_thumbnail_id ID ảnh đại diện bài viết.
		 * @param string|int[] $size              Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
		 *                                        hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
		 */
		do_action( 'end_fetch_post_thumbnail_html', $post->ID, $post_thumbnail_id, $size );

	} else {
		$html = '';
	}

	/**
	 * Lọc HTML ảnh đại diện bài viết.
	 *
	 * @since 2.9.0
	 *
	 * @param string       $html              HTML ảnh đại diện bài viết.
	 * @param int          $post_id           ID bài viết.
	 * @param int          $post_thumbnail_id ID ảnh đại diện bài viết, hoặc 0 nếu không có.
	 * @param string|int[] $size              Kích thước hình ảnh yêu cầu. Có thể là bất kỳ tên kích thước đã đăng ký,
	 *                                        hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
	 * @param string|array $attr              Chuỗi truy vấn hoặc mảng thuộc tính.
	 */
	return apply_filters( 'post_thumbnail_html', $html, $post->ID, $post_thumbnail_id, $size, $attr );
}

/**
 * Trả về URL ảnh đại diện bài viết.
 *
 * @since 4.4.0
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
 * @param string|int[]     $size Tùy chọn. Kích thước hình ảnh đã đăng ký để lấy nguồn hoặc mảng phẳng
 *                               giá trị chiều cao và chiều rộng. Mặc định 'post-thumbnail'.
 * @return string|false URL ảnh đại diện bài viết hoặc false nếu không có hình ảnh. Nếu `$size` không khớp
 *                      với bất kỳ kích thước hình ảnh đã đăng ký nào, URL hình ảnh gốc sẽ được trả về.
 */
function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) {
	$post_thumbnail_id = get_post_thumbnail_id( $post );

	if ( ! $post_thumbnail_id ) {
		return false;
	}

	$thumbnail_url = wp_get_attachment_image_url( $post_thumbnail_id, $size );

	/**
	 * Lọc URL ảnh đại diện bài viết.
	 *
	 * @since 5.9.0
	 *
	 * @param string|false     $thumbnail_url URL ảnh đại diện hoặc false nếu bài viết không tồn tại.
	 * @param int|WP_Post|null $post          ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
	 * @param string|int[]     $size          Kích thước hình ảnh đã đăng ký để lấy nguồn hoặc mảng phẳng
	 *                                        giá trị chiều cao và chiều rộng. Mặc định 'post-thumbnail'.
	 */
	return apply_filters( 'post_thumbnail_url', $thumbnail_url, $post, $size );
}

/**
 * Hiển thị URL ảnh đại diện bài viết.
 *
 * @since 4.4.0
 *
 * @param string|int[] $size Tùy chọn. Kích thước hình ảnh sử dụng. Chấp nhận bất kỳ kích thước hình ảnh hợp lệ,
 *                           hoặc mảng giá trị chiều rộng và chiều cao tính bằng pixel (theo thứ tự đó).
 *                           Mặc định 'post-thumbnail'.
 */
function the_post_thumbnail_url( $size = 'post-thumbnail' ) {
	$url = get_the_post_thumbnail_url( null, $size );

	if ( $url ) {
		echo esc_url( $url );
	}
}

/**
 * Trả về chú thích ảnh đại diện bài viết.
 *
 * @since 4.6.0
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
 * @return string Chú thích ảnh đại diện bài viết.
 */
function get_the_post_thumbnail_caption( $post = null ) {
	$post_thumbnail_id = get_post_thumbnail_id( $post );

	if ( ! $post_thumbnail_id ) {
		return '';
	}

	$caption = wp_get_attachment_caption( $post_thumbnail_id );

	if ( ! $caption ) {
		$caption = '';
	}

	return $caption;
}

/**
 * Hiển thị chú thích ảnh đại diện bài viết.
 *
 * @since 4.6.0
 *
 * @param int|WP_Post|null $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là `$post` toàn cục.
 */
function the_post_thumbnail_caption( $post = null ) {
	/**
	 * Lọc chú thích ảnh đại diện bài viết được hiển thị.
	 *
	 * @since 4.6.0
	 *
	 * @param string $caption Chú thích cho tệp đính kèm đã cho.
	 */
	echo apply_filters( 'the_post_thumbnail_caption', get_the_post_thumbnail_caption( $post ) );
}
