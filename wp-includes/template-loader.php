<?php
/**
 * Tải template chính xác dựa trên URL của người truy cập
 *
 * @package WordPress
 */
if ( wp_using_themes() ) {
	/**
	 * Kích hoạt trước khi xác định template nào sẽ được tải.
	 *
	 * @since 1.5.0
	 */
	do_action( 'template_redirect' );
}

/**
 * Lọc việc cho phép các yêu cầu 'HEAD' tạo nội dung.
 *
 * Cung cấp cải thiện hiệu suất đáng kể bằng cách thoát trước khi nội dung
 * trang được tải cho các yêu cầu 'HEAD'. Xem #14348.
 *
 * @since 3.5.0
 *
 * @param bool $exit Có thoát mà không tạo nội dung nào cho các yêu cầu 'HEAD' hay không. Mặc định true.
 */
if ( 'HEAD' === $_SERVER['REQUEST_METHOD'] && apply_filters( 'exit_on_http_head', true ) ) {
	exit;
}

// Xử lý feed và trackback ngay cả khi không sử dụng theme.
if ( is_robots() ) {
	/**
	 * Kích hoạt khi bộ tải template xác định đây là yêu cầu robots.txt.
	 *
	 * @since 2.1.0
	 */
	do_action( 'do_robots' );
	return;
} elseif ( is_favicon() ) {
	/**
	 * Kích hoạt khi bộ tải template xác định đây là yêu cầu favicon.ico.
	 *
	 * @since 5.4.0
	 */
	do_action( 'do_favicon' );
	return;
} elseif ( is_feed() ) {
	do_feed();
	return;
} elseif ( is_trackback() ) {
	require ABSPATH . 'wp-trackback.php';
	return;
}

if ( wp_using_themes() ) {

	$tag_templates = array(
		'is_embed'             => 'get_embed_template',
		'is_404'               => 'get_404_template',
		'is_search'            => 'get_search_template',
		'is_front_page'        => 'get_front_page_template',
		'is_home'              => 'get_home_template',
		'is_privacy_policy'    => 'get_privacy_policy_template',
		'is_post_type_archive' => 'get_post_type_archive_template',
		'is_tax'               => 'get_taxonomy_template',
		'is_attachment'        => 'get_attachment_template',
		'is_single'            => 'get_single_template',
		'is_page'              => 'get_page_template',
		'is_singular'          => 'get_singular_template',
		'is_category'          => 'get_category_template',
		'is_tag'               => 'get_tag_template',
		'is_author'            => 'get_author_template',
		'is_date'              => 'get_date_template',
		'is_archive'           => 'get_archive_template',
	);
	$template      = false;

	// Lặp qua từng điều kiện template, và tìm file template phù hợp.
	foreach ( $tag_templates as $tag => $template_getter ) {
		if ( call_user_func( $tag ) ) {
			$template = call_user_func( $template_getter );
		}

		if ( $template ) {
			if ( 'is_attachment' === $tag ) {
				remove_filter( 'the_content', 'prepend_attachment' );
			}

			break;
		}
	}

	if ( ! $template ) {
		$template = get_index_template();
	}

	/**
	 * Lọc đường dẫn của template hiện tại trước khi include nó.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template Đường dẫn của template cần include.
	 */
	$template = apply_filters( 'template_include', $template );
	if ( $template ) {
		include $template;
	} elseif ( current_user_can( 'switch_themes' ) ) {
		$theme = wp_get_theme();
		if ( $theme->errors() ) {
			wp_die( $theme->errors() );
		}
	}
	return;
}
