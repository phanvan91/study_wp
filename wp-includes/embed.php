<?php
/**
 * API oEmbed: Chức năng oEmbed cấp cao nhất
 *
 * @package WordPress
 * @subpackage oEmbed
 * @since 4.4.0
 */

/**
 * Đăng ký một trình xử lý nhúng.
 *
 * Nên chỉ được sử dụng cho các trang web không hỗ trợ oEmbed.
 *
 * @since 2.9.0
 *
 * @global WP_Embed $wp_embed Đối tượng WordPress Embed.
 *
 * @param string   $id       ID/tên nội bộ cho trình xử lý. Cần phải là duy nhất.
 * @param string   $regex    Biểu thức chính quy sẽ được sử dụng để xác định xem trình xử lý này có nên được dùng cho một URL hay không.
 * @param callable $callback Hàm callback sẽ được gọi nếu biểu thức chính quy khớp.
 * @param int      $priority Tùy chọn. Được sử dụng để chỉ định thứ tự kiểm tra các trình xử lý đã đăng ký.
 *                           Mặc định 10.
 */
function wp_embed_register_handler( $id, $regex, $callback, $priority = 10 ) {
	global $wp_embed;
	$wp_embed->register_handler( $id, $regex, $callback, $priority );
}

/**
 * Hủy đăng ký một trình xử lý nhúng đã được đăng ký trước đó.
 *
 * @since 2.9.0
 *
 * @global WP_Embed $wp_embed Đối tượng WordPress Embed.
 *
 * @param string $id       ID trình xử lý cần được gỡ bỏ.
 * @param int    $priority Tùy chọn. Mức độ ưu tiên của trình xử lý cần gỡ bỏ. Mặc định 10.
 */
function wp_embed_unregister_handler( $id, $priority = 10 ) {
	global $wp_embed;
	$wp_embed->unregister_handler( $id, $priority );
}

/**
 * Tạo mảng mặc định của các tham số nhúng.
 *
 * Chiều rộng mặc định là chiều rộng nội dung được chỉ định bởi theme. Nếu theme
 * không chỉ định chiều rộng nội dung, thì 500px sẽ được sử dụng.
 *
 * Chiều cao mặc định là 1.5 lần chiều rộng, hoặc 1000px, tùy giá trị nào nhỏ hơn.
 *
 * Bộ lọc {@see 'embed_defaults'} có thể được sử dụng để điều chỉnh một trong hai giá trị.
 *
 * @since 2.9.0
 *
 * @global int $content_width
 *
 * @param string $url Tùy chọn. URL cần được nhúng. Mặc định rỗng.
 * @return int[] {
 *     Mảng chỉ mục của chiều rộng và chiều cao nhúng tính bằng pixel.
 *
 *     @type int $0 Chiều rộng nhúng.
 *     @type int $1 Chiều cao nhúng.
 * }
 */
function wp_embed_defaults( $url = '' ) {
	if ( ! empty( $GLOBALS['content_width'] ) ) {
		$width = (int) $GLOBALS['content_width'];
	}

	if ( empty( $width ) ) {
		$width = 500;
	}

	$height = min( (int) ceil( $width * 1.5 ), 1000 );

	/**
	 * Lọc mảng mặc định của kích thước nhúng.
	 *
	 * @since 2.9.0
	 *
	 * @param int[]  $size {
	 *     Mảng chỉ mục của chiều rộng và chiều cao nhúng tính bằng pixel.
	 *
	 *     @type int $0 Chiều rộng nhúng.
	 *     @type int $1 Chiều cao nhúng.
	 * }
	 * @param string $url  URL cần được nhúng.
	 */
	return apply_filters( 'embed_defaults', compact( 'width', 'height' ), $url );
}

/**
 * Cố gắng lấy HTML nhúng cho một URL được cung cấp bằng oEmbed.
 *
 * @since 2.9.0
 *
 * @see WP_oEmbed
 *
 * @param string $url  URL cần được nhúng.
 * @param array|string $args {
 *     Tùy chọn. Các tham số bổ sung để lấy HTML nhúng. Mặc định rỗng.
 *
 *     @type int|string $width    Tùy chọn. Giá trị `maxwidth` được truyền tới URL nhà cung cấp.
 *     @type int|string $height   Tùy chọn. Giá trị `maxheight` được truyền tới URL nhà cung cấp.
 *     @type bool       $discover Tùy chọn. Xác định xem có cố gắng khám phá các thẻ link
 *                                tại URL đã cho cho một nhà cung cấp oEmbed khi URL nhà cung cấp
 *                                không được tìm thấy trong danh sách nhà cung cấp tích hợp. Mặc định true.
 * }
 * @return string|false HTML nhúng khi thành công, false khi thất bại.
 */
function wp_oembed_get( $url, $args = '' ) {
	$oembed = _wp_oembed_get_object();
	return $oembed->get_html( $url, $args );
}

/**
 * Trả về đối tượng WP_oEmbed đã được khởi tạo.
 *
 * @since 2.9.0
 * @access private
 *
 * @return WP_oEmbed Đối tượng.
 */
function _wp_oembed_get_object() {
	static $wp_oembed = null;

	if ( is_null( $wp_oembed ) ) {
		$wp_oembed = new WP_oEmbed();
	}
	return $wp_oembed;
}

/**
 * Thêm một cặp định dạng URL và URL nhà cung cấp oEmbed.
 *
 * @since 2.9.0
 *
 * @see WP_oEmbed
 *
 * @param string $format   Định dạng URL mà nhà cung cấp này có thể xử lý. Bạn có thể sử dụng dấu sao
 *                         làm ký tự đại diện.
 * @param string $provider URL tới nhà cung cấp oEmbed.
 * @param bool   $regex    Tùy chọn. Tham số `$format` có ở dạng RegEx hay không. Mặc định false.
 */
function wp_oembed_add_provider( $format, $provider, $regex = false ) {
	if ( did_action( 'plugins_loaded' ) ) {
		$oembed                       = _wp_oembed_get_object();
		$oembed->providers[ $format ] = array( $provider, $regex );
	} else {
		WP_oEmbed::_add_provider_early( $format, $provider, $regex );
	}
}

/**
 * Gỡ bỏ một nhà cung cấp oEmbed.
 *
 * @since 3.5.0
 *
 * @see WP_oEmbed
 *
 * @param string $format Định dạng URL của nhà cung cấp oEmbed cần gỡ bỏ.
 * @return bool Nhà cung cấp đã được gỡ bỏ thành công chưa?
 */
function wp_oembed_remove_provider( $format ) {
	if ( did_action( 'plugins_loaded' ) ) {
		$oembed = _wp_oembed_get_object();

		if ( isset( $oembed->providers[ $format ] ) ) {
			unset( $oembed->providers[ $format ] );
			return true;
		}
	} else {
		WP_oEmbed::_remove_provider_early( $format );
	}

	return false;
}

/**
 * Xác định xem các trình xử lý nhúng mặc định có nên được tải hay không.
 *
 * Kiểm tra để đảm bảo rằng thư viện nhúng chưa được tải. Nếu
 * chưa, thì nó sẽ tải thư viện nhúng.
 *
 * @since 2.9.0
 *
 * @see wp_embed_register_handler()
 */
function wp_maybe_load_embeds() {
	/**
	 * Lọc xem có tải các trình xử lý nhúng mặc định hay không.
	 *
	 * Trả về giá trị falsy sẽ ngăn việc tải các trình xử lý nhúng mặc định.
	 *
	 * @since 2.9.0
	 *
	 * @param bool $maybe_load_embeds Có tải thư viện nhúng hay không. Mặc định true.
	 */
	if ( ! apply_filters( 'load_default_embeds', true ) ) {
		return;
	}

	wp_embed_register_handler( 'youtube_embed_url', '#https?://(www.)?youtube\.com/(?:v|embed)/([^/]+)#i', 'wp_embed_handler_youtube' );

	/**
	 * Lọc callback trình xử lý nhúng âm thanh.
	 *
	 * @since 3.6.0
	 *
	 * @param callable $handler Hàm callback trình xử lý nhúng âm thanh.
	 */
	wp_embed_register_handler( 'audio', '#^https?://.+?\.(' . implode( '|', wp_get_audio_extensions() ) . ')$#i', apply_filters( 'wp_audio_embed_handler', 'wp_embed_handler_audio' ), 9999 );

	/**
	 * Lọc callback trình xử lý nhúng video.
	 *
	 * @since 3.6.0
	 *
	 * @param callable $handler Hàm callback trình xử lý nhúng video.
	 */
	wp_embed_register_handler( 'video', '#^https?://.+?\.(' . implode( '|', wp_get_video_extensions() ) . ')$#i', apply_filters( 'wp_video_embed_handler', 'wp_embed_handler_video' ), 9999 );
}

/**
 * Callback trình xử lý nhúng iframe YouTube.
 *
 * Bắt các URL nhúng iframe YouTube không thể phân tích bởi oEmbed nhưng có thể chuyển đổi thành URL có thể phân tích.
 *
 * @since 4.0.0
 *
 * @global WP_Embed $wp_embed Đối tượng WordPress Embed.
 *
 * @param array  $matches Các kết quả khớp RegEx từ biểu thức chính quy được cung cấp khi gọi
 *                        wp_embed_register_handler().
 * @param array  $attr    Các thuộc tính nhúng.
 * @param string $url     URL gốc được khớp bởi biểu thức chính quy.
 * @param array  $rawattr Các thuộc tính gốc chưa được sửa đổi.
 * @return string HTML nhúng.
 */
function wp_embed_handler_youtube( $matches, $attr, $url, $rawattr ) {
	global $wp_embed;
	$embed = $wp_embed->autoembed( sprintf( 'https://youtube.com/watch?v=%s', urlencode( $matches[2] ) ) );

	/**
	 * Lọc đầu ra nhúng YouTube.
	 *
	 * @since 4.0.0
	 *
	 * @see wp_embed_handler_youtube()
	 *
	 * @param string $embed   Đầu ra nhúng YouTube.
	 * @param array  $attr    Một mảng các thuộc tính nhúng.
	 * @param string $url     URL gốc được khớp bởi biểu thức chính quy.
	 * @param array  $rawattr Các thuộc tính gốc chưa được sửa đổi.
	 */
	return apply_filters( 'wp_embed_handler_youtube', $embed, $attr, $url, $rawattr );
}

/**
 * Callback trình xử lý nhúng âm thanh.
 *
 * @since 3.6.0
 *
 * @param array  $matches Các kết quả khớp RegEx từ biểu thức chính quy khi gọi wp_embed_register_handler().
 * @param array  $attr Các thuộc tính nhúng.
 * @param string $url URL gốc được khớp bởi biểu thức chính quy.
 * @param array  $rawattr Các thuộc tính gốc chưa được sửa đổi.
 * @return string HTML nhúng.
 */
function wp_embed_handler_audio( $matches, $attr, $url, $rawattr ) {
	$audio = sprintf( '[audio src="%s" /]', esc_url( $url ) );

	/**
	 * Lọc đầu ra nhúng âm thanh.
	 *
	 * @since 3.6.0
	 *
	 * @param string $audio   Đầu ra nhúng âm thanh.
	 * @param array  $attr    Một mảng các thuộc tính nhúng.
	 * @param string $url     URL gốc được khớp bởi biểu thức chính quy.
	 * @param array  $rawattr Các thuộc tính gốc chưa được sửa đổi.
	 */
	return apply_filters( 'wp_embed_handler_audio', $audio, $attr, $url, $rawattr );
}

/**
 * Callback trình xử lý nhúng video.
 *
 * @since 3.6.0
 *
 * @param array  $matches Các kết quả khớp RegEx từ biểu thức chính quy khi gọi wp_embed_register_handler().
 * @param array  $attr    Các thuộc tính nhúng.
 * @param string $url     URL gốc được khớp bởi biểu thức chính quy.
 * @param array  $rawattr Các thuộc tính gốc chưa được sửa đổi.
 * @return string HTML nhúng.
 */
function wp_embed_handler_video( $matches, $attr, $url, $rawattr ) {
	$dimensions = '';
	if ( ! empty( $rawattr['width'] ) && ! empty( $rawattr['height'] ) ) {
		$dimensions .= sprintf( 'width="%d" ', (int) $rawattr['width'] );
		$dimensions .= sprintf( 'height="%d" ', (int) $rawattr['height'] );
	}
	$video = sprintf( '[video %s src="%s" /]', $dimensions, esc_url( $url ) );

	/**
	 * Lọc đầu ra nhúng video.
	 *
	 * @since 3.6.0
	 *
	 * @param string $video   Đầu ra nhúng video.
	 * @param array  $attr    Một mảng các thuộc tính nhúng.
	 * @param string $url     URL gốc được khớp bởi biểu thức chính quy.
	 * @param array  $rawattr Các thuộc tính gốc chưa được sửa đổi.
	 */
	return apply_filters( 'wp_embed_handler_video', $video, $attr, $url, $rawattr );
}

/**
 * Đăng ký route REST API oEmbed.
 *
 * @since 4.4.0
 */
function wp_oembed_register_route() {
	$controller = new WP_oEmbed_Controller();
	$controller->register_routes();
}

/**
 * Thêm các liên kết khám phá oEmbed vào phần tử head của website.
 *
 * @since 4.4.0
 * @since 6.8.0 Đầu ra được điều chỉnh để chỉ nhúng nếu bài viết hỗ trợ.
 */
function wp_oembed_add_discovery_links() {
	$output = '';

	if ( is_singular() && is_post_embeddable() ) {
		$output .= '<link rel="alternate" title="' . _x( 'oEmbed (JSON)', 'oEmbed resource link name' ) . '" type="application/json+oembed" href="' . esc_url( get_oembed_endpoint_url( get_permalink() ) ) . '" />' . "\n";

		if ( class_exists( 'SimpleXMLElement' ) ) {
			$output .= '<link rel="alternate" title="' . _x( 'oEmbed (XML)', 'oEmbed resource link name' ) . '" type="text/xml+oembed" href="' . esc_url( get_oembed_endpoint_url( get_permalink(), 'xml' ) ) . '" />' . "\n";
		}
	}

	/**
	 * Lọc HTML các liên kết khám phá oEmbed.
	 *
	 * @since 4.4.0
	 *
	 * @param string $output HTML của các liên kết khám phá.
	 */
	echo apply_filters( 'oembed_discovery_links', $output );
}

/**
 * Thêm JavaScript cần thiết để giao tiếp với các iframe được nhúng.
 *
 * Hàm này không còn được sử dụng trực tiếp nữa. Để tương thích ngược, nó tồn tại duy nhất như một cách để chỉ ra rằng
 * JS host oEmbed _nên_ được thêm vào. Trong `default-filters.php` vẫn còn đoạn mã:
 *
 *     add_action( 'wp_head', 'wp_oembed_add_host_js' )
 *
 * Trước đây một trang web có thể vô hiệu hóa việc thêm script host oEmbed bằng cách:
 *
 *     remove_action( 'wp_head', 'wp_oembed_add_host_js' )
 *
 * Để đảm bảo rằng mã như vậy vẫn hoạt động như mong đợi, hàm này vẫn được giữ lại. Hiện có kiểm tra `has_action()`
 * trong `wp_maybe_enqueue_oembed_host_js()` để xem liệu `wp_oembed_add_host_js()` chưa bị gỡ hook khỏi
 * action `wp_head`.
 *
 * @since 4.4.0
 * @deprecated 5.9.0 Sử dụng {@see wp_maybe_enqueue_oembed_host_js()} thay thế.
 */
function wp_oembed_add_host_js() {}

/**
 * Đưa script wp-embed vào hàng đợi nếu HTML oEmbed được cung cấp chứa một bài viết nhúng.
 *
 * Để chỉ đưa script wp-embed vào hàng đợi trên các trang thực sự chứa bài viết nhúng, hàm này kiểm tra xem
 * HTML được cung cấp có chứa markup bài viết nhúng hay không và nếu có thì đưa script vào hàng đợi để được in ở footer.
 *
 * @since 5.9.0
 *
 * @param string $html Markup nhúng.
 * @return string Markup nhúng (không có sửa đổi).
 */
function wp_maybe_enqueue_oembed_host_js( $html ) {
	if (
		has_action( 'wp_head', 'wp_oembed_add_host_js' )
		&&
		preg_match( '/<blockquote\s[^>]*?wp-embedded-content/', $html )
	) {
		wp_enqueue_script( 'wp-embed' );
	}
	return $html;
}

/**
 * Lấy URL để nhúng một bài viết cụ thể trong iframe.
 *
 * @since 4.4.0
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng. Mặc định là bài viết hiện tại.
 * @return string|false URL nhúng bài viết khi thành công, false nếu bài viết không tồn tại.
 */
function get_post_embed_url( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$embed_url     = trailingslashit( get_permalink( $post ) ) . user_trailingslashit( 'embed' );
	$path_conflict = get_page_by_path( str_replace( home_url(), '', $embed_url ), OBJECT, get_post_types( array( 'public' => true ) ) );

	if ( ! get_option( 'permalink_structure' ) || $path_conflict ) {
		$embed_url = add_query_arg( array( 'embed' => 'true' ), get_permalink( $post ) );
	}

	/**
	 * Lọc URL để nhúng một bài viết cụ thể.
	 *
	 * @since 4.4.0
	 *
	 * @param string  $embed_url URL nhúng bài viết.
	 * @param WP_Post $post      Đối tượng bài viết tương ứng.
	 */
	return sanitize_url( apply_filters( 'post_embed_url', $embed_url, $post ) );
}

/**
 * Lấy URL endpoint oEmbed cho một permalink được cho.
 *
 * Truyền chuỗi rỗng làm đối số đầu tiên để lấy URL cơ sở endpoint.
 *
 * @since 4.4.0
 *
 * @param string $permalink Tùy chọn. Permalink được sử dụng cho đối số query `url`. Mặc định rỗng.
 * @param string $format    Tùy chọn. Định dạng phản hồi được yêu cầu. Mặc định 'json'.
 * @return string URL endpoint oEmbed.
 */
function get_oembed_endpoint_url( $permalink = '', $format = 'json' ) {
	$url = rest_url( 'oembed/1.0/embed' );

	if ( '' !== $permalink ) {
		$url = add_query_arg(
			array(
				'url'    => urlencode( $permalink ),
				'format' => ( 'json' !== $format ) ? $format : false,
			),
			$url
		);
	}

	/**
	 * Lọc URL endpoint oEmbed.
	 *
	 * @since 4.4.0
	 *
	 * @param string $url       URL tới endpoint oEmbed.
	 * @param string $permalink Permalink được sử dụng cho đối số query `url`.
	 * @param string $format    Định dạng phản hồi được yêu cầu.
	 */
	return apply_filters( 'oembed_endpoint_url', $url, $permalink, $format );
}

/**
 * Lấy mã nhúng cho một bài viết cụ thể.
 *
 * @since 4.4.0
 *
 * @param int         $width  Chiều rộng cho phản hồi.
 * @param int         $height Chiều cao cho phản hồi.
 * @param int|WP_Post $post   Tùy chọn. ID bài viết hoặc đối tượng. Mặc định là `$post` toàn cục.
 * @return string|false Mã nhúng khi thành công, false nếu bài viết không tồn tại.
 */
function get_post_embed_html( $width, $height, $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$embed_url = get_post_embed_url( $post );

	$secret     = wp_generate_password( 10, false );
	$embed_url .= "#?secret={$secret}";

	$output = sprintf(
		'<blockquote class="wp-embedded-content" data-secret="%1$s"><a href="%2$s">%3$s</a></blockquote>',
		esc_attr( $secret ),
		esc_url( get_permalink( $post ) ),
		get_the_title( $post )
	);

	$output .= sprintf(
		'<iframe sandbox="allow-scripts" security="restricted" src="%1$s" width="%2$d" height="%3$d" title="%4$s" data-secret="%5$s" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" class="wp-embedded-content"></iframe>',
		esc_url( $embed_url ),
		absint( $width ),
		absint( $height ),
		esc_attr(
			sprintf(
				/* translators: 1: Post title, 2: Site title. */
				__( '&#8220;%1$s&#8221; &#8212; %2$s' ),
				get_the_title( $post ),
				get_bloginfo( 'name' )
			)
		),
		esc_attr( $secret )
	);

	/*
	 * Lưu ý rằng script phải được đặt sau <blockquote> và <iframe> do lỗi phân tích regexp trong
	 * `wp_filter_oembed_result()`. Vì mẫu regex bắt đầu với `|(<blockquote>.*?</blockquote>)?.*|`
	 * trong đó <blockquote> được đánh dấu là tùy chọn, nếu nó không ở đầu chuỗi thì nhóm
	 * sẽ không khớp và mọi thứ sẽ được khớp bởi `.*` và không được bao gồm trong nhóm. Lỗi regex này
	 * có từ WordPress 4.4, vì vậy để không phá vỡ các bản cài đặt cũ, script này phải ở cuối.
	 */
	$output .= wp_get_inline_script_tag(
		file_get_contents( ABSPATH . WPINC . '/js/wp-embed' . wp_scripts_get_suffix() . '.js' )
	);

	/**
	 * Filters the embed HTML output for a given post.
	 *
	 * @since 4.4.0
	 *
	 * @param string  $output The default iframe tag to display embedded content.
	 * @param WP_Post $post   Current post object.
	 * @param int     $width  Width of the response.
	 * @param int     $height Height of the response.
	 */
	return apply_filters( 'embed_html', $output, $post, $width, $height );
}

/**
 * Retrieves the oEmbed response data for a given post.
 *
 * @since 4.4.0
 * @since 6.8.0 Output was adjusted to only embed if the post type supports it.
 *
 * @param WP_Post|int $post  Post ID or post object.
 * @param int         $width The requested width.
 * @return array|false Response data on success, false if post doesn't exist,
 *                     is not publicly viewable or post type is not embeddable.
 */
function get_oembed_response_data( $post, $width ) {
	$post  = get_post( $post );
	$width = absint( $width );

	if ( ! $post ) {
		return false;
	}

	if ( ! is_post_publicly_viewable( $post ) ) {
		return false;
	}

	if ( ! is_post_embeddable( $post ) ) {
		return false;
	}

	/**
	 * Filters the allowed minimum and maximum widths for the oEmbed response.
	 *
	 * @since 4.4.0
	 *
	 * @param array $min_max_width {
	 *     Minimum and maximum widths for the oEmbed response.
	 *
	 *     @type int $min Minimum width. Default 200.
	 *     @type int $max Maximum width. Default 600.
	 * }
	 */
	$min_max_width = apply_filters(
		'oembed_min_max_width',
		array(
			'min' => 200,
			'max' => 600,
		)
	);

	$width  = min( max( $min_max_width['min'], $width ), $min_max_width['max'] );
	$height = max( (int) ceil( $width / 16 * 9 ), 200 );

	$data = array(
		'version'       => '1.0',
		'provider_name' => get_bloginfo( 'name' ),
		'provider_url'  => get_home_url(),
		'author_name'   => get_bloginfo( 'name' ),
		'author_url'    => get_home_url(),
		'title'         => get_the_title( $post ),
		'type'          => 'link',
	);

	$author = get_userdata( $post->post_author );

	if ( $author ) {
		$data['author_name'] = $author->display_name;
		$data['author_url']  = get_author_posts_url( $author->ID );
	}

	/**
	 * Filters the oEmbed response data.
	 *
	 * @since 4.4.0
	 *
	 * @param array   $data   The response data.
	 * @param WP_Post $post   The post object.
	 * @param int     $width  The requested width.
	 * @param int     $height The calculated height.
	 */
	return apply_filters( 'oembed_response_data', $data, $post, $width, $height );
}


/**
 * Retrieves the oEmbed response data for a given URL.
 *
 * @since 5.0.0
 *
 * @param string $url  The URL that should be inspected for discovery `<link>` tags.
 * @param array  $args oEmbed remote get arguments.
 * @return object|false oEmbed response data if the URL does belong to the current site. False otherwise.
 */
function get_oembed_response_data_for_url( $url, $args ) {
	$switched_blog = false;

	if ( is_multisite() ) {
		$url_parts = wp_parse_args(
			wp_parse_url( $url ),
			array(
				'host' => '',
				'port' => null,
				'path' => '/',
			)
		);

		$qv = array(
			'domain'                 => $url_parts['host'] . ( $url_parts['port'] ? ':' . $url_parts['port'] : '' ),
			'path'                   => '/',
			'update_site_meta_cache' => false,
		);

		// In case of subdirectory configs, set the path.
		if ( ! is_subdomain_install() ) {
			$path = explode( '/', ltrim( $url_parts['path'], '/' ) );
			$path = reset( $path );

			if ( $path ) {
				$qv['path'] = get_network()->path . $path . '/';
			}
		}

		$sites = get_sites( $qv );
		$site  = reset( $sites );

		// Do not allow embeds for deleted/archived/spam sites.
		if ( ! empty( $site->deleted ) || ! empty( $site->spam ) || ! empty( $site->archived ) ) {
			return false;
		}

		if ( $site && get_current_blog_id() !== (int) $site->blog_id ) {
			switch_to_blog( $site->blog_id );
			$switched_blog = true;
		}
	}

	$post_id = url_to_postid( $url );

	/** This filter is documented in wp-includes/class-wp-oembed-controller.php */
	$post_id = apply_filters( 'oembed_request_post_id', $post_id, $url );

	if ( ! $post_id ) {
		if ( $switched_blog ) {
			restore_current_blog();
		}

		return false;
	}

	$width = isset( $args['width'] ) ? $args['width'] : 0;

	$data = get_oembed_response_data( $post_id, $width );

	if ( $switched_blog ) {
		restore_current_blog();
	}

	return $data ? (object) $data : false;
}


/**
 * Filters the oEmbed response data to return an iframe embed code.
 *
 * @since 4.4.0
 *
 * @param array   $data   The response data.
 * @param WP_Post $post   The post object.
 * @param int     $width  The requested width.
 * @param int     $height The calculated height.
 * @return array The modified response data.
 */
function get_oembed_response_data_rich( $data, $post, $width, $height ) {
	$data['width']  = absint( $width );
	$data['height'] = absint( $height );
	$data['type']   = 'rich';
	$data['html']   = get_post_embed_html( $width, $height, $post );

	// Add post thumbnail to response if available.
	$thumbnail_id = false;

	if ( has_post_thumbnail( $post->ID ) ) {
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
	}

	if ( 'attachment' === get_post_type( $post ) ) {
		if ( wp_attachment_is_image( $post ) ) {
			$thumbnail_id = $post->ID;
		} elseif ( wp_attachment_is( 'video', $post ) ) {
			$thumbnail_id = get_post_thumbnail_id( $post );
			$data['type'] = 'video';
		}
	}

	if ( $thumbnail_id ) {
		list( $thumbnail_url, $thumbnail_width, $thumbnail_height ) = wp_get_attachment_image_src( $thumbnail_id, array( $width, 0 ) );
		$data['thumbnail_url']                                      = $thumbnail_url;
		$data['thumbnail_width']                                    = $thumbnail_width;
		$data['thumbnail_height']                                   = $thumbnail_height;
	}

	return $data;
}

/**
 * Ensures that the specified format is either 'json' or 'xml'.
 *
 * @since 4.4.0
 *
 * @param string $format The oEmbed response format. Accepts 'json' or 'xml'.
 * @return string The format, either 'xml' or 'json'. Default 'json'.
 */
function wp_oembed_ensure_format( $format ) {
	if ( ! in_array( $format, array( 'json', 'xml' ), true ) ) {
		return 'json';
	}

	return $format;
}

/**
 * Hooks into the REST API output to print XML instead of JSON.
 *
 * This is only done for the oEmbed API endpoint,
 * which supports both formats.
 *
 * @access private
 * @since 4.4.0
 *
 * @param bool             $served  Whether the request has already been served.
 * @param WP_HTTP_Response $result  Result to send to the client. Usually a `WP_REST_Response`.
 * @param WP_REST_Request  $request Request used to generate the response.
 * @param WP_REST_Server   $server  Server instance.
 * @return true
 */
function _oembed_rest_pre_serve_request( $served, $result, $request, $server ) {
	$params = $request->get_params();

	if ( '/oembed/1.0/embed' !== $request->get_route() || 'GET' !== $request->get_method() ) {
		return $served;
	}

	if ( ! isset( $params['format'] ) || 'xml' !== $params['format'] ) {
		return $served;
	}

	// Embed links inside the request.
	$data = $server->response_to_data( $result, false );

	if ( ! class_exists( 'SimpleXMLElement' ) ) {
		status_header( 501 );
		die( get_status_header_desc( 501 ) );
	}

	$result = _oembed_create_xml( $data );

	// Bail if there's no XML.
	if ( ! $result ) {
		status_header( 501 );
		return get_status_header_desc( 501 );
	}

	if ( ! headers_sent() ) {
		$server->send_header( 'Content-Type', 'text/xml; charset=' . get_option( 'blog_charset' ) );
	}

	echo $result;

	return true;
}

/**
 * Creates an XML string from a given array.
 *
 * @since 4.4.0
 * @access private
 *
 * @param array            $data The original oEmbed response data.
 * @param SimpleXMLElement $node Optional. XML node to append the result to recursively.
 * @return string|false XML string on success, false on error.
 */
function _oembed_create_xml( $data, $node = null ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return false;
	}

	if ( null === $node ) {
		$node = new SimpleXMLElement( '<oembed></oembed>' );
	}

	foreach ( $data as $key => $value ) {
		if ( is_numeric( $key ) ) {
			$key = 'oembed';
		}

		if ( is_array( $value ) ) {
			$item = $node->addChild( $key );
			_oembed_create_xml( $value, $item );
		} else {
			$node->addChild( $key, esc_html( $value ) );
		}
	}

	return $node->asXML();
}

/**
 * Filters the given oEmbed HTML to make sure iframes have a title attribute.
 *
 * @since 5.2.0
 *
 * @param string $result The oEmbed HTML result.
 * @param object $data   A data object result from an oEmbed provider.
 * @param string $url    The URL of the content to be embedded.
 * @return string The filtered oEmbed result.
 */
function wp_filter_oembed_iframe_title_attribute( $result, $data, $url ) {
	if ( false === $result || ! in_array( $data->type, array( 'rich', 'video' ), true ) ) {
		return $result;
	}

	$title = ! empty( $data->title ) ? $data->title : '';

	$pattern = '`<iframe([^>]*)>`i';
	if ( preg_match( $pattern, $result, $matches ) ) {
		$attrs = wp_kses_hair( $matches[1], wp_allowed_protocols() );

		foreach ( $attrs as $attr => $item ) {
			$lower_attr = strtolower( $attr );
			if ( $lower_attr === $attr ) {
				continue;
			}
			if ( ! isset( $attrs[ $lower_attr ] ) ) {
				$attrs[ $lower_attr ] = $item;
				unset( $attrs[ $attr ] );
			}
		}
	}

	if ( ! empty( $attrs['title']['value'] ) ) {
		$title = $attrs['title']['value'];
	}

	/**
	 * Filters the title attribute of the given oEmbed HTML iframe.
	 *
	 * @since 5.2.0
	 *
	 * @param string $title  The title attribute.
	 * @param string $result The oEmbed HTML result.
	 * @param object $data   A data object result from an oEmbed provider.
	 * @param string $url    The URL of the content to be embedded.
	 */
	$title = apply_filters( 'oembed_iframe_title_attribute', $title, $result, $data, $url );

	if ( '' === $title ) {
		return $result;
	}

	if ( isset( $attrs['title'] ) ) {
		unset( $attrs['title'] );
		$attr_string = implode( ' ', wp_list_pluck( $attrs, 'whole' ) );
		$result      = str_replace( $matches[0], '<iframe ' . trim( $attr_string ) . '>', $result );
	}
	return str_ireplace( '<iframe ', sprintf( '<iframe title="%s" ', esc_attr( $title ) ), $result );
}


/**
 * Filters the given oEmbed HTML.
 *
 * If the `$url` isn't on the trusted providers list,
 * we need to filter the HTML heavily for security.
 *
 * Only filters 'rich' and 'video' response types.
 *
 * @since 4.4.0
 *
 * @param string $result The oEmbed HTML result.
 * @param object $data   A data object result from an oEmbed provider.
 * @param string $url    The URL of the content to be embedded.
 * @return string The filtered and sanitized oEmbed result.
 */
function wp_filter_oembed_result( $result, $data, $url ) {
	if ( false === $result || ! in_array( $data->type, array( 'rich', 'video' ), true ) ) {
		return $result;
	}

	$wp_oembed = _wp_oembed_get_object();

	// Don't modify the HTML for trusted providers.
	if ( false !== $wp_oembed->get_provider( $url, array( 'discover' => false ) ) ) {
		return $result;
	}

	$allowed_html = array(
		'a'          => array(
			'href' => true,
		),
		'blockquote' => array(),
		'iframe'     => array(
			'src'          => true,
			'width'        => true,
			'height'       => true,
			'frameborder'  => true,
			'marginwidth'  => true,
			'marginheight' => true,
			'scrolling'    => true,
			'title'        => true,
		),
	);

	$html = wp_kses( $result, $allowed_html );

	preg_match( '|(<blockquote>.*?</blockquote>)?.*(<iframe.*?></iframe>)|ms', $html, $content );
	// We require at least the iframe to exist.
	if ( empty( $content[2] ) ) {
		return false;
	}
	$html = $content[1] . $content[2];

	preg_match( '/ src=([\'"])(.*?)\1/', $html, $results );

	if ( ! empty( $results ) ) {
		$secret = wp_generate_password( 10, false );

		$url = esc_url( "{$results[2]}#?secret=$secret" );
		$q   = $results[1];

		$html = str_replace( $results[0], ' src=' . $q . $url . $q . ' data-secret=' . $q . $secret . $q, $html );
		$html = str_replace( '<blockquote', "<blockquote data-secret=\"$secret\"", $html );
	}

	$allowed_html['blockquote']['data-secret'] = true;
	$allowed_html['iframe']['data-secret']     = true;

	$html = wp_kses( $html, $allowed_html );

	if ( ! empty( $content[1] ) ) {
		// We have a blockquote to fall back on. Hide the iframe by default.
		$html = str_replace( '<iframe', '<iframe style="position: absolute; visibility: hidden;"', $html );
		$html = str_replace( '<blockquote', '<blockquote class="wp-embedded-content"', $html );
	}

	$html = str_ireplace( '<iframe', '<iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted"', $html );

	return $html;
}

/**
 * Filters the string in the 'more' link displayed after a trimmed excerpt.
 *
 * Replaces '[...]' (appended to automatically generated excerpts) with an
 * ellipsis and a "Continue reading" link in the embed template.
 *
 * @since 4.4.0
 *
 * @param string $more_string Default 'more' string.
 * @return string 'Continue reading' link prepended with an ellipsis.
 */
function wp_embed_excerpt_more( $more_string ) {
	if ( ! is_embed() ) {
		return $more_string;
	}

	$link = sprintf(
		'<a href="%1$s" class="wp-embed-more" target="_top">%2$s</a>',
		esc_url( get_permalink() ),
		/* translators: %s: Post title. */
		sprintf( __( 'Continue reading %s' ), '<span class="screen-reader-text">' . get_the_title() . '</span>' )
	);
	return ' &hellip; ' . $link;
}

/**
 * Displays the post excerpt for the embed template.
 *
 * Intended to be used in 'The Loop'.
 *
 * @since 4.4.0
 */
function the_excerpt_embed() {
	$output = get_the_excerpt();

	/**
	 * Filters the post excerpt for the embed template.
	 *
	 * @since 4.4.0
	 *
	 * @param string $output The current post excerpt.
	 */
	echo apply_filters( 'the_excerpt_embed', $output );
}

/**
 * Filters the post excerpt for the embed template.
 *
 * Shows players for video and audio attachments.
 *
 * @since 4.4.0
 *
 * @param string $content The current post excerpt.
 * @return string The modified post excerpt.
 */
function wp_embed_excerpt_attachment( $content ) {
	if ( is_attachment() ) {
		return prepend_attachment( '' );
	}

	return $content;
}

/**
 * Enqueues embed iframe default CSS and JS.
 *
 * Enqueue PNG fallback CSS for embed iframe for legacy versions of IE.
 *
 * Allows plugins to queue scripts for the embed iframe end using wp_enqueue_script().
 * Runs first in oembed_head().
 *
 * @since 4.4.0
 */
function enqueue_embed_scripts() {
	wp_enqueue_style( 'wp-embed-template-ie' );

	/**
	 * Fires when scripts and styles are enqueued for the embed iframe.
	 *
	 * @since 4.4.0
	 */
	do_action( 'enqueue_embed_scripts' );
}

/**
 * Enqueues the CSS in the embed iframe header.
 *
 * @since 6.4.0
 */
function wp_enqueue_embed_styles() {
	// Back-compat for plugins that disable functionality by unhooking this action.
	if ( ! has_action( 'embed_head', 'print_embed_styles' ) ) {
		return;
	}
	remove_action( 'embed_head', 'print_embed_styles' );

	$suffix = wp_scripts_get_suffix();
	$handle = 'wp-embed-template';
	wp_register_style( $handle, false );
	wp_add_inline_style( $handle, file_get_contents( ABSPATH . WPINC . "/css/wp-embed-template$suffix.css" ) );
	wp_enqueue_style( $handle );
}

/**
 * Prints the JavaScript in the embed iframe header.
 *
 * @since 4.4.0
 */
function print_embed_scripts() {
	wp_print_inline_script_tag(
		file_get_contents( ABSPATH . WPINC . '/js/wp-embed-template' . wp_scripts_get_suffix() . '.js' )
	);
}

/**
 * Prepare the oembed HTML to be displayed in an RSS feed.
 *
 * @since 4.4.0
 * @access private
 *
 * @param string $content The content to filter.
 * @return string The filtered content.
 */
function _oembed_filter_feed_content( $content ) {
	$p = new WP_HTML_Tag_Processor( $content );
	while ( $p->next_tag( array( 'tag_name' => 'iframe' ) ) ) {
		if ( $p->has_class( 'wp-embedded-content' ) ) {
			$p->remove_attribute( 'style' );
		}
	}
	return $p->get_updated_html();
}

/**
 * Prints the necessary markup for the embed comments button.
 *
 * @since 4.4.0
 */
function print_embed_comments_button() {
	if ( is_404() || ! ( get_comments_number() || comments_open() ) ) {
		return;
	}
	?>
	<div class="wp-embed-comments">
		<a href="<?php comments_link(); ?>" target="_top">
			<span class="dashicons dashicons-admin-comments"></span>
			<?php
			printf(
				/* translators: %s: Number of comments. */
				_n(
					'%s <span class="screen-reader-text">Comment</span>',
					'%s <span class="screen-reader-text">Comments</span>',
					get_comments_number()
				),
				number_format_i18n( get_comments_number() )
			);
			?>
		</a>
	</div>
	<?php
}

/**
 * Prints the necessary markup for the embed sharing button.
 *
 * @since 4.4.0
 */
function print_embed_sharing_button() {
	if ( is_404() ) {
		return;
	}
	?>
	<div class="wp-embed-share">
		<button type="button" class="wp-embed-share-dialog-open" aria-label="<?php esc_attr_e( 'Open sharing dialog' ); ?>">
			<span class="dashicons dashicons-share"></span>
		</button>
	</div>
	<?php
}

/**
 * Prints the necessary markup for the embed sharing dialog.
 *
 * @since 4.4.0
 */
function print_embed_sharing_dialog() {
	if ( is_404() ) {
		return;
	}

	$unique_suffix            = get_the_ID() . '-' . wp_rand();
	$share_tab_wordpress_id   = 'wp-embed-share-tab-wordpress-' . $unique_suffix;
	$share_tab_html_id        = 'wp-embed-share-tab-html-' . $unique_suffix;
	$description_wordpress_id = 'wp-embed-share-description-wordpress-' . $unique_suffix;
	$description_html_id      = 'wp-embed-share-description-html-' . $unique_suffix;
	?>
	<div class="wp-embed-share-dialog hidden" role="dialog" aria-label="<?php esc_attr_e( 'Sharing options' ); ?>">
		<div class="wp-embed-share-dialog-content">
			<div class="wp-embed-share-dialog-text">
				<ul class="wp-embed-share-tabs" role="tablist">
					<li class="wp-embed-share-tab-button wp-embed-share-tab-button-wordpress" role="presentation">
						<button type="button" role="tab" aria-controls="<?php echo $share_tab_wordpress_id; ?>" aria-selected="true" tabindex="0"><?php esc_html_e( 'WordPress Embed' ); ?></button>
					</li>
					<li class="wp-embed-share-tab-button wp-embed-share-tab-button-html" role="presentation">
						<button type="button" role="tab" aria-controls="<?php echo $share_tab_html_id; ?>" aria-selected="false" tabindex="-1"><?php esc_html_e( 'HTML Embed' ); ?></button>
					</li>
				</ul>
				<div id="<?php echo $share_tab_wordpress_id; ?>" class="wp-embed-share-tab" role="tabpanel" aria-hidden="false">
					<input type="text" value="<?php the_permalink(); ?>" class="wp-embed-share-input" aria-label="<?php esc_attr_e( 'URL' ); ?>" aria-describedby="<?php echo $description_wordpress_id; ?>" tabindex="0" readonly/>

					<p class="wp-embed-share-description" id="<?php echo $description_wordpress_id; ?>">
						<?php _e( 'Copy and paste this URL into your WordPress site to embed' ); ?>
					</p>
				</div>
				<div id="<?php echo $share_tab_html_id; ?>" class="wp-embed-share-tab" role="tabpanel" aria-hidden="true">
					<textarea class="wp-embed-share-input" aria-label="<?php esc_attr_e( 'HTML' ); ?>" aria-describedby="<?php echo $description_html_id; ?>" tabindex="0" readonly><?php echo esc_textarea( get_post_embed_html( 600, 400 ) ); ?></textarea>

					<p class="wp-embed-share-description" id="<?php echo $description_html_id; ?>">
						<?php _e( 'Copy and paste this code into your site to embed' ); ?>
					</p>
				</div>
			</div>

			<button type="button" class="wp-embed-share-dialog-close" aria-label="<?php esc_attr_e( 'Close sharing dialog' ); ?>">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
	</div>
	<?php
}

/**
 * Prints the necessary markup for the site title in an embed template.
 *
 * @since 4.5.0
 */
function the_embed_site_title() {
	$site_title = sprintf(
		'<a href="%s" target="_top"><img src="%s" srcset="%s 2x" width="32" height="32" alt="" class="wp-embed-site-icon" /><span>%s</span></a>',
		esc_url( home_url() ),
		esc_url( get_site_icon_url( 32, includes_url( 'images/w-logo-blue.png' ) ) ),
		esc_url( get_site_icon_url( 64, includes_url( 'images/w-logo-blue.png' ) ) ),
		esc_html( get_bloginfo( 'name' ) )
	);

	$site_title = '<div class="wp-embed-site-title">' . $site_title . '</div>';

	/**
	 * Filters the site title HTML in the embed footer.
	 *
	 * @since 4.4.0
	 *
	 * @param string $site_title The site title HTML.
	 */
	echo apply_filters( 'embed_site_title_html', $site_title );
}

/**
 * Filters the oEmbed result before any HTTP requests are made.
 *
 * If the URL belongs to the current site, the result is fetched directly instead of
 * going through the oEmbed discovery process.
 *
 * @since 4.5.3
 *
 * @param null|string $result The UNSANITIZED (and potentially unsafe) HTML that should be used to embed. Default null.
 * @param string      $url    The URL that should be inspected for discovery `<link>` tags.
 * @param array       $args   oEmbed remote get arguments.
 * @return null|string The UNSANITIZED (and potentially unsafe) HTML that should be used to embed.
 *                     Null if the URL does not belong to the current site.
 */
function wp_filter_pre_oembed_result( $result, $url, $args ) {
	$data = get_oembed_response_data_for_url( $url, $args );

	if ( $data ) {
		return _wp_oembed_get_object()->data2html( $data, $url );
	}

	return $result;
}
