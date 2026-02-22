<?php
/**
 * API Feed của WordPress
 *
 * Nhiều hàm được sử dụng ở đây thuộc về Vòng lặp (The Loop), hoặc Vòng lặp
 * dành cho các Feed.
 *
 * @package WordPress
 * @subpackage Feed
 * @since 2.1.0
 */

/**
 * Lấy container RSS cho hàm bloginfo.
 *
 * Bạn có thể lấy bất kỳ thông tin nào mà bạn có thể sử dụng với hàm get_bloginfo().
 * Tất cả sẽ được loại bỏ thẻ và chuyển đổi ký tự, khi các giá trị
 * được lấy để sử dụng trong các feed.
 *
 * @since 1.5.1
 *
 * @see get_bloginfo() Để xem danh sách các giá trị có thể hiển thị.
 *
 * @param string $show Xem get_bloginfo() để biết các giá trị có thể dùng.
 * @return string
 */
function get_bloginfo_rss( $show = '' ) {
	$info = strip_tags( get_bloginfo( $show ) );
	/**
	 * Lọc thông tin blog để sử dụng trong các feed RSS.
	 *
	 * @since 2.2.0
	 *
	 * @see convert_chars()
	 * @see get_bloginfo()
	 *
	 * @param string $info Giá trị chuỗi đã chuyển đổi của thông tin blog.
	 * @param string $show Loại thông tin blog cần lấy.
	 */
	return apply_filters( 'get_bloginfo_rss', convert_chars( $info ), $show );
}

/**
 * Hiển thị container RSS cho hàm bloginfo.
 *
 * Bạn có thể lấy bất kỳ thông tin nào mà bạn có thể sử dụng với hàm get_bloginfo().
 * Tất cả sẽ được loại bỏ thẻ và chuyển đổi ký tự, khi các giá trị
 * được lấy để sử dụng trong các feed.
 *
 * @since 0.71
 *
 * @see get_bloginfo() Để xem danh sách các giá trị có thể hiển thị.
 *
 * @param string $show Xem get_bloginfo() để biết các giá trị có thể dùng.
 */
function bloginfo_rss( $show = '' ) {
	/**
	 * Lọc thông tin blog để hiển thị trong các feed RSS.
	 *
	 * @since 2.1.0
	 *
	 * @see get_bloginfo()
	 *
	 * @param string $rss_container Container RSS cho thông tin blog.
	 * @param string $show          Loại thông tin blog cần lấy.
	 */
	echo apply_filters( 'bloginfo_rss', get_bloginfo_rss( $show ), $show );
}

/**
 * Lấy feed mặc định.
 *
 * Feed mặc định là 'rss2', trừ khi một plugin thay đổi nó thông qua
 * bộ lọc {@see 'default_feed'}.
 *
 * @since 2.5.0
 *
 * @return string Feed mặc định, ví dụ 'rss2', 'atom', v.v.
 */
function get_default_feed() {
	/**
	 * Lọc loại feed mặc định.
	 *
	 * @since 2.5.0
	 *
	 * @param string $feed_type Loại feed mặc định. Các giá trị có thể bao gồm 'rss2', 'atom'.
	 *                          Mặc định 'rss2'.
	 */
	$default_feed = apply_filters( 'default_feed', 'rss2' );

	return ( 'rss' === $default_feed ) ? 'rss2' : $default_feed;
}

/**
 * Lấy tiêu đề blog cho tiêu đề feed.
 *
 * @since 2.2.0
 * @since 4.4.0 Tham số tùy chọn `$sep` đã bị loại bỏ và đổi tên thành `$deprecated`.
 *
 * @param string $deprecated Không sử dụng.
 * @return string Tiêu đề tài liệu.
 */
function get_wp_title_rss( $deprecated = '&#8211;' ) {
	if ( '&#8211;' !== $deprecated ) {
		/* translators: %s: 'document_title_separator' filter name. */
		_deprecated_argument( __FUNCTION__, '4.4.0', sprintf( __( 'Use the %s filter instead.' ), '<code>document_title_separator</code>' ) );
	}

	/**
	 * Lọc tiêu đề blog để sử dụng làm tiêu đề feed.
	 *
	 * @since 2.2.0
	 * @since 4.4.0 Tham số `$sep` đã bị loại bỏ và đổi tên thành `$deprecated`.
	 *
	 * @param string $title      Tiêu đề blog hiện tại.
	 * @param string $deprecated Không sử dụng.
	 */
	return apply_filters( 'get_wp_title_rss', wp_get_document_title(), $deprecated );
}

/**
 * Hiển thị tiêu đề blog cho việc hiển thị tiêu đề feed.
 *
 * @since 2.2.0
 * @since 4.4.0 Tham số tùy chọn `$sep` đã bị loại bỏ và đổi tên thành `$deprecated`.
 *
 * @param string $deprecated Không sử dụng.
 */
function wp_title_rss( $deprecated = '&#8211;' ) {
	if ( '&#8211;' !== $deprecated ) {
		/* translators: %s: 'document_title_separator' filter name. */
		_deprecated_argument( __FUNCTION__, '4.4.0', sprintf( __( 'Use the %s filter instead.' ), '<code>document_title_separator</code>' ) );
	}

	/**
	 * Lọc tiêu đề blog để hiển thị tiêu đề feed.
	 *
	 * @since 2.2.0
	 * @since 4.4.0 Tham số `$sep` đã bị loại bỏ và đổi tên thành `$deprecated`.
	 *
	 * @see get_wp_title_rss()
	 *
	 * @param string $wp_title_rss Tiêu đề blog hiện tại.
	 * @param string $deprecated   Không sử dụng.
	 */
	echo apply_filters( 'wp_title_rss', get_wp_title_rss(), $deprecated );
}

/**
 * Lấy tiêu đề bài viết hiện tại cho feed.
 *
 * @since 2.0.0
 * @since 6.6.0 Thêm tham số `$post`.
 *
 * @param int|WP_Post $post Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định là $post toàn cục.
 * @return string Tiêu đề bài viết hiện tại.
 */
function get_the_title_rss( $post = 0 ) {
	$title = get_the_title( $post );

	/**
	 * Lọc tiêu đề bài viết để sử dụng trong feed.
	 *
	 * @since 1.2.0
	 *
	 * @param string $title Tiêu đề bài viết hiện tại.
	 */
	return apply_filters( 'the_title_rss', $title );
}

/**
 * Hiển thị tiêu đề bài viết trong feed.
 *
 * @since 0.71
 */
function the_title_rss() {
	echo get_the_title_rss();
}

/**
 * Lấy nội dung bài viết cho các feed.
 *
 * @since 2.9.0
 *
 * @see get_the_content()
 *
 * @param string $feed_type Loại feed. rss2 | atom | rss | rdf
 * @return string Nội dung đã được lọc.
 */
function get_the_content_feed( $feed_type = null ) {
	if ( ! $feed_type ) {
		$feed_type = get_default_feed();
	}

	/** Bộ lọc này được ghi chú trong wp-includes/post-template.php */
	$content = apply_filters( 'the_content', get_the_content() );
	$content = str_replace( ']]>', ']]&gt;', $content );

	/**
	 * Lọc nội dung bài viết để sử dụng trong các feed.
	 *
	 * @since 2.9.0
	 *
	 * @param string $content   Nội dung bài viết hiện tại.
	 * @param string $feed_type Loại feed. Các giá trị có thể bao gồm 'rss2', 'atom'.
	 *                          Mặc định 'rss2'.
	 */
	return apply_filters( 'the_content_feed', $content, $feed_type );
}

/**
 * Hiển thị nội dung bài viết cho các feed.
 *
 * @since 2.9.0
 *
 * @param string $feed_type Loại feed. rss2 | atom | rss | rdf
 */
function the_content_feed( $feed_type = null ) {
	echo get_the_content_feed( $feed_type );
}

/**
 * Hiển thị trích đoạn bài viết cho feed.
 *
 * @since 0.71
 */
function the_excerpt_rss() {
	$output = get_the_excerpt();
	/**
	 * Lọc trích đoạn bài viết cho feed.
	 *
	 * @since 1.2.0
	 *
	 * @param string $output Trích đoạn bài viết hiện tại.
	 */
	echo apply_filters( 'the_excerpt_rss', $output );
}

/**
 * Hiển thị đường dẫn cố định đến bài viết để sử dụng trong các feed.
 *
 * @since 2.3.0
 */
function the_permalink_rss() {
	/**
	 * Lọc đường dẫn cố định đến bài viết để sử dụng trong các feed.
	 *
	 * @since 2.3.0
	 *
	 * @param string $post_permalink Đường dẫn cố định của bài viết hiện tại.
	 */
	echo esc_url( apply_filters( 'the_permalink_rss', get_permalink() ) );
}

/**
 * Xuất liên kết đến phần bình luận của bài viết hiện tại theo cách an toàn cho XML.
 *
 * @since 3.0.0
 */
function comments_link_feed() {
	/**
	 * Lọc đường dẫn cố định bình luận cho bài viết hiện tại.
	 *
	 * @since 3.6.0
	 *
	 * @param string $comment_permalink Đường dẫn cố định bình luận hiện tại với
	 *                                  '#comments' được nối thêm.
	 */
	echo esc_url( apply_filters( 'comments_link_feed', get_comments_link() ) );
}

/**
 * Hiển thị GUID feed cho bình luận hiện tại.
 *
 * @since 2.5.0
 *
 * @param int|WP_Comment $comment_id Đối tượng hoặc ID bình luận tùy chọn. Mặc định là đối tượng bình luận toàn cục.
 */
function comment_guid( $comment_id = null ) {
	echo esc_url( get_comment_guid( $comment_id ) );
}

/**
 * Lấy GUID feed cho bình luận hiện tại.
 *
 * @since 2.5.0
 *
 * @param int|WP_Comment $comment_id Đối tượng hoặc ID bình luận tùy chọn. Mặc định là đối tượng bình luận toàn cục.
 * @return string|false GUID cho bình luận khi thành công, false khi thất bại.
 */
function get_comment_guid( $comment_id = null ) {
	$comment = get_comment( $comment_id );

	if ( ! is_object( $comment ) ) {
		return false;
	}

	return get_the_guid( $comment->comment_post_ID ) . '#comment-' . $comment->comment_ID;
}

/**
 * Hiển thị liên kết đến phần bình luận.
 *
 * @since 1.5.0
 * @since 4.4.0 Giới thiệu tham số `$comment`.
 *
 * @param int|WP_Comment $comment Tùy chọn. Đối tượng hoặc ID bình luận. Mặc định là đối tượng bình luận toàn cục.
 */
function comment_link( $comment = null ) {
	/**
	 * Lọc đường dẫn cố định của bình luận hiện tại.
	 *
	 * @since 3.6.0
	 *
	 * @see get_comment_link()
	 *
	 * @param string $comment_permalink Đường dẫn cố định bình luận hiện tại.
	 */
	echo esc_url( apply_filters( 'comment_link', get_comment_link( $comment ) ) );
}

/**
 * Lấy tác giả bình luận hiện tại để sử dụng trong các feed.
 *
 * @since 2.0.0
 *
 * @return string Tác giả bình luận.
 */
function get_comment_author_rss() {
	/**
	 * Lọc tác giả bình luận hiện tại để sử dụng trong feed.
	 *
	 * @since 1.5.0
	 *
	 * @see get_comment_author()
	 *
	 * @param string $comment_author Tác giả bình luận hiện tại.
	 */
	return apply_filters( 'comment_author_rss', get_comment_author() );
}

/**
 * Hiển thị tác giả bình luận hiện tại trong feed.
 *
 * @since 1.0.0
 */
function comment_author_rss() {
	echo get_comment_author_rss();
}

/**
 * Hiển thị nội dung bình luận hiện tại để sử dụng trong các feed.
 *
 * @since 1.0.0
 */
function comment_text_rss() {
	$comment_text = get_comment_text();
	/**
	 * Lọc nội dung bình luận hiện tại để sử dụng trong feed.
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_text Nội dung của bình luận hiện tại.
	 */
	$comment_text = apply_filters( 'comment_text_rss', $comment_text );
	echo $comment_text;
}

/**
 * Lấy tất cả chuyên mục của bài viết, được định dạng để sử dụng trong các feed.
 *
 * Tất cả chuyên mục của bài viết hiện tại trong vòng lặp feed sẽ được
 * lấy và thêm markup feed, để chúng có thể dễ dàng được thêm vào
 * các feed RSS2, Atom, hoặc RSS1 và RSS0.91 RDF.
 *
 * @since 2.1.0
 *
 * @param string $type Tùy chọn, mặc định là loại được trả về bởi get_default_feed().
 * @return string Tất cả chuyên mục bài viết để hiển thị trong feed.
 */
function get_the_category_rss( $type = null ) {
	if ( empty( $type ) ) {
		$type = get_default_feed();
	}
	$categories = get_the_category();
	$tags       = get_the_tags();
	$the_list   = '';
	$cat_names  = array();

	$filter = 'rss';
	if ( 'atom' === $type ) {
		$filter = 'raw';
	}

	if ( ! empty( $categories ) ) {
		foreach ( (array) $categories as $category ) {
			$cat_names[] = sanitize_term_field( 'name', $category->name, $category->term_id, 'category', $filter );
		}
	}

	if ( ! empty( $tags ) ) {
		foreach ( (array) $tags as $tag ) {
			$cat_names[] = sanitize_term_field( 'name', $tag->name, $tag->term_id, 'post_tag', $filter );
		}
	}

	$cat_names = array_unique( $cat_names );

	foreach ( $cat_names as $cat_name ) {
		if ( 'rdf' === $type ) {
			$the_list .= "\t\t<dc:subject><![CDATA[$cat_name]]></dc:subject>\n";
		} elseif ( 'atom' === $type ) {
			$the_list .= sprintf( '<category scheme="%1$s" term="%2$s" />', esc_attr( get_bloginfo_rss( 'url' ) ), esc_attr( $cat_name ) );
		} else {
			$the_list .= "\t\t<category><![CDATA[" . html_entity_decode( $cat_name, ENT_COMPAT, get_option( 'blog_charset' ) ) . "]]></category>\n";
		}
	}

	/**
	 * Lọc tất cả chuyên mục bài viết để hiển thị trong feed.
	 *
	 * @since 1.2.0
	 *
	 * @param string $the_list Tất cả chuyên mục bài viết RSS.
	 * @param string $type     Loại feed. Các giá trị có thể bao gồm 'rss2', 'atom'.
	 *                         Mặc định 'rss2'.
	 */
	return apply_filters( 'the_category_rss', $the_list, $type );
}

/**
 * Hiển thị chuyên mục bài viết trong feed.
 *
 * @since 0.71
 *
 * @see get_the_category_rss() Để xem giải thích chi tiết hơn.
 *
 * @param string $type Tùy chọn, mặc định là loại được trả về bởi get_default_feed().
 */
function the_category_rss( $type = null ) {
	echo get_the_category_rss( $type );
}

/**
 * Hiển thị loại HTML dựa trên cài đặt blog.
 *
 * Hai giá trị có thể là 'xhtml' hoặc 'html'.
 *
 * @since 2.2.0
 */
function html_type_rss() {
	$type = get_bloginfo( 'html_type' );
	if ( str_contains( $type, 'xhtml' ) ) {
		$type = 'xhtml';
	} else {
		$type = 'html';
	}
	echo $type;
}

/**
 * Hiển thị enclosure RSS cho bài viết hiện tại.
 *
 * Sử dụng biến toàn cục $post để kiểm tra xem bài viết có yêu cầu mật khẩu hay không
 * và liệu người dùng có mật khẩu cho bài viết hay không. Nếu không thì sẽ trả về
 * trước khi hiển thị.
 *
 * Cũng sử dụng hàm get_post_custom() để lấy trường metadata 'enclosure'
 * của bài viết và phân tích giá trị để hiển thị (các) enclosure. Các
 * enclosure bao gồm (các) thẻ HTML enclosure với URI và các thuộc tính khác.
 *
 * @since 1.5.0
 */
function rss_enclosure() {
	if ( post_password_required() ) {
		return;
	}

	foreach ( (array) get_post_custom() as $key => $val ) {
		if ( 'enclosure' === $key ) {
			foreach ( (array) $val as $enc ) {
				$enclosure = explode( "\n", $enc );

				if ( count( $enclosure ) < 3 ) {
					continue;
				}

				// Chỉ lấy phần tử đầu tiên, ví dụ 'audio/mpeg' từ 'audio/mpeg mpga mp2 mp3'.
				$t    = preg_split( '/[ \t]/', trim( $enclosure[2] ) );
				$type = $t[0];

				/**
				 * Lọc thẻ liên kết HTML enclosure RSS cho bài viết hiện tại.
				 *
				 * @since 2.2.0
				 *
				 * @param string $html_link_tag Thẻ liên kết HTML với URI và các thuộc tính khác.
				 */
				echo apply_filters( 'rss_enclosure', '<enclosure url="' . esc_url( trim( $enclosure[0] ) ) . '" length="' . absint( trim( $enclosure[1] ) ) . '" type="' . esc_attr( $type ) . '" />' . "\n" );
			}
		}
	}
}

/**
 * Hiển thị enclosure atom cho bài viết hiện tại.
 *
 * Sử dụng biến toàn cục $post để kiểm tra xem bài viết có yêu cầu mật khẩu hay không
 * và liệu người dùng có mật khẩu cho bài viết hay không. Nếu không thì sẽ trả về
 * trước khi hiển thị.
 *
 * Cũng sử dụng hàm get_post_custom() để lấy trường metadata 'enclosure'
 * của bài viết và phân tích giá trị để hiển thị (các) enclosure. Các
 * enclosure bao gồm (các) thẻ HTML link với URI và các thuộc tính khác.
 *
 * @since 2.2.0
 */
function atom_enclosure() {
	if ( post_password_required() ) {
		return;
	}

	foreach ( (array) get_post_custom() as $key => $val ) {
		if ( 'enclosure' === $key ) {
			foreach ( (array) $val as $enc ) {
				$enclosure = explode( "\n", $enc );

				$url    = '';
				$type   = '';
				$length = 0;

				$mimes = get_allowed_mime_types();

				// Phân tích URL.
				if ( isset( $enclosure[0] ) && is_string( $enclosure[0] ) ) {
					$url = trim( $enclosure[0] );
				}

				// Phân tích độ dài và loại.
				for ( $i = 1; $i <= 2; $i++ ) {
					if ( isset( $enclosure[ $i ] ) ) {
						if ( is_numeric( $enclosure[ $i ] ) ) {
							$length = trim( $enclosure[ $i ] );
						} elseif ( in_array( $enclosure[ $i ], $mimes, true ) ) {
							$type = trim( $enclosure[ $i ] );
						}
					}
				}

				$html_link_tag = sprintf(
					"<link href=\"%s\" rel=\"enclosure\" length=\"%d\" type=\"%s\" />\n",
					esc_url( $url ),
					esc_attr( $length ),
					esc_attr( $type )
				);

				/**
				 * Lọc thẻ liên kết HTML enclosure atom cho bài viết hiện tại.
				 *
				 * @since 2.2.0
				 *
				 * @param string $html_link_tag Thẻ liên kết HTML với URI và các thuộc tính khác.
				 */
				echo apply_filters( 'atom_enclosure', $html_link_tag );
			}
		}
	}
}

/**
 * Xác định loại của một chuỗi dữ liệu với dữ liệu đã được định dạng.
 *
 * Cho biết loại là text, HTML, hay XHTML, theo RFC 4287 mục 3.1.
 *
 * Trong trường hợp của WordPress, text được định nghĩa là không chứa markup,
 * XHTML được định nghĩa là "đúng chuẩn" (well formed), và HTML là tag soup (tức là phần còn lại).
 *
 * Các thẻ div container được thêm vào các giá trị XHTML, theo mục 3.1.1.3.
 *
 * @link http://www.atomenabled.org/developers/syndication/atom-format-spec.php#rfc.section.3.1
 *
 * @since 2.5.0
 *
 * @param string $data Chuỗi đầu vào.
 * @return array array(loại, giá trị)
 */
function prep_atom_text_construct( $data ) {
	if ( ! str_contains( $data, '<' ) && ! str_contains( $data, '&' ) ) {
		return array( 'text', $data );
	}

	if ( ! function_exists( 'xml_parser_create' ) ) {
		wp_trigger_error( '', __( "PHP's XML extension is not available. Please contact your hosting provider to enable PHP's XML extension." ) );

		return array( 'html', "<![CDATA[$data]]>" );
	}

	$parser = xml_parser_create();
	xml_parse( $parser, '<div>' . $data . '</div>', true );
	$code = xml_get_error_code( $parser );
	xml_parser_free( $parser );
	unset( $parser );

	if ( ! $code ) {
		if ( ! str_contains( $data, '<' ) ) {
			return array( 'text', $data );
		} else {
			$data = "<div xmlns='http://www.w3.org/1999/xhtml'>$data</div>";
			return array( 'xhtml', $data );
		}
	}

	if ( ! str_contains( $data, ']]>' ) ) {
		return array( 'html', "<![CDATA[$data]]>" );
	} else {
		return array( 'html', htmlspecialchars( $data ) );
	}
}

/**
 * Hiển thị Biểu tượng Trang trong các feed atom.
 *
 * @since 4.3.0
 *
 * @see get_site_icon_url()
 */
function atom_site_icon() {
	$url = get_site_icon_url( 32 );
	if ( $url ) {
		echo '<icon>' . convert_chars( $url ) . "</icon>\n";
	}
}

/**
 * Hiển thị Biểu tượng Trang trong RSS2.
 *
 * @since 4.3.0
 */
function rss2_site_icon() {
	$rss_title = get_wp_title_rss();
	if ( empty( $rss_title ) ) {
		$rss_title = get_bloginfo_rss( 'name' );
	}

	$url = get_site_icon_url( 32 );
	if ( $url ) {
		echo '
<image>
	<url>' . convert_chars( $url ) . '</url>
	<title>' . $rss_title . '</title>
	<link>' . get_bloginfo_rss( 'url' ) . '</link>
	<width>32</width>
	<height>32</height>
</image> ' . "\n";
	}
}

/**
 * Trả về liên kết cho feed hiện đang được hiển thị.
 *
 * @since 5.3.0
 *
 * @return string Liên kết chính xác cho phần tử atom:self.
 */
function get_self_link() {
	$parsed = parse_url( home_url() );

	$domain = $parsed['host'];
	if ( isset( $parsed['port'] ) ) {
		$domain .= ':' . $parsed['port'];
	}

	return set_url_scheme( 'http://' . $domain . wp_unslash( $_SERVER['REQUEST_URI'] ) );
}

/**
 * Hiển thị liên kết cho feed hiện đang được hiển thị theo cách an toàn XSS.
 *
 * Tạo liên kết chính xác cho phần tử atom:self.
 *
 * @since 2.5.0
 */
function self_link() {
	/**
	 * Lọc URL feed hiện tại.
	 *
	 * @since 3.6.0
	 *
	 * @see set_url_scheme()
	 * @see wp_unslash()
	 *
	 * @param string $feed_link Liên kết cho feed với scheme URL đã được thiết lập.
	 */
	echo esc_url( apply_filters( 'self_link', get_self_link() ) );
}

/**
 * Lấy thời gian UTC của bài viết được chỉnh sửa gần đây nhất từ WP_Query.
 *
 * Nếu đang xem feed bình luận, thời gian của bình luận được chỉnh sửa
 * gần đây nhất sẽ được trả về.
 *
 * @since 5.2.0
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string $format Chuỗi định dạng ngày để trả về thời gian.
 * @return string|false Thời gian theo định dạng yêu cầu, hoặc false khi thất bại.
 */
function get_feed_build_date( $format ) {
	global $wp_query;

	$datetime          = false;
	$max_modified_time = false;
	$utc               = new DateTimeZone( 'UTC' );

	if ( ! empty( $wp_query ) && $wp_query->have_posts() ) {
		// Trích xuất thời gian chỉnh sửa bài viết từ các bài viết.
		$modified_times = wp_list_pluck( $wp_query->posts, 'post_modified_gmt' );

		// Nếu đây là feed bình luận, kiểm tra cả các đối tượng đó.
		if ( $wp_query->is_comment_feed() && $wp_query->comment_count ) {
			// Trích xuất thời gian chỉnh sửa bình luận từ các bình luận.
			$comment_times = wp_list_pluck( $wp_query->comments, 'comment_date_gmt' );

			// Thêm thời gian bình luận vào thời gian bài viết để so sánh.
			$modified_times = array_merge( $modified_times, $comment_times );
		}

		// Xác định thời gian chỉnh sửa tối đa.
		$datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', max( $modified_times ), $utc );
	}

	if ( false === $datetime ) {
		// Dự phòng về thời gian cuối cùng bất kỳ bài viết nào được chỉnh sửa hoặc xuất bản.
		$datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', get_lastpostmodified( 'GMT' ), $utc );
	}

	if ( false !== $datetime ) {
		$max_modified_time = $datetime->format( $format );
	}

	/**
	 * Lọc ngày mà bài viết hoặc bình luận cuối cùng trong truy vấn được chỉnh sửa.
	 *
	 * @since 5.2.0
	 *
	 * @param string|false $max_modified_time Ngày bài viết hoặc bình luận cuối cùng được chỉnh sửa trong truy vấn, theo UTC.
	 *                                        False khi thất bại.
	 * @param string       $format            Định dạng ngày được yêu cầu trong get_feed_build_date().
	 */
	return apply_filters( 'get_feed_build_date', $max_modified_time, $format );
}

/**
 * Trả về loại nội dung cho loại feed được chỉ định.
 *
 * @since 2.8.0
 *
 * @param string $type Loại feed. Các giá trị có thể bao gồm 'rss', 'rss2', 'atom', và 'rdf'.
 * @return string Loại nội dung cho loại feed được chỉ định.
 */
function feed_content_type( $type = '' ) {
	if ( empty( $type ) ) {
		$type = get_default_feed();
	}

	$types = array(
		'rss'      => 'application/rss+xml',
		'rss2'     => 'application/rss+xml',
		'rss-http' => 'text/xml',
		'atom'     => 'application/atom+xml',
		'rdf'      => 'application/rdf+xml',
	);

	$content_type = ( ! empty( $types[ $type ] ) ) ? $types[ $type ] : 'application/octet-stream';

	/**
	 * Lọc loại nội dung cho một loại feed cụ thể.
	 *
	 * @since 2.8.0
	 *
	 * @param string $content_type Loại nội dung cho biết loại dữ liệu mà feed chứa.
	 * @param string $type         Loại feed. Các giá trị có thể bao gồm 'rss', 'rss2', 'atom', và 'rdf'.
	 */
	return apply_filters( 'feed_content_type', $content_type, $type );
}

/**
 * Xây dựng đối tượng SimplePie dựa trên feed RSS hoặc Atom từ URL.
 *
 * @since 2.8.0
 *
 * @param string|string[] $url URL của feed cần lấy. Nếu là mảng URL, các feed sẽ được gộp
 *                             sử dụng tính năng multifeed của SimplePie.
 *                             Xem thêm {@link http://simplepie.org/wiki/faq/typical_multifeed_gotchas}
 * @return SimplePie\SimplePie|WP_Error Đối tượng SimplePie khi thành công hoặc đối tượng WP_Error khi thất bại.
 */
function fetch_feed( $url ) {
	if ( ! class_exists( 'SimplePie\SimplePie', false ) ) {
		require_once ABSPATH . WPINC . '/class-simplepie.php';
	}

	require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
	require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
	require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';

	$feed = new SimplePie\SimplePie();

	$feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );
	/*
	 * Chúng ta phải ghi đè thủ công $feed->sanitize vì hàm khởi tạo của SimplePie
	 * thiết lập nó trước khi chúng ta có cơ hội thiết lập lớp sanitization.
	 */
	$feed->sanitize = new WP_SimplePie_Sanitize_KSES();

	// Đăng ký trình xử lý cache bằng phương thức được khuyến nghị cho SimplePie 1.3 trở lên.
	if ( method_exists( 'SimplePie_Cache', 'register' ) ) {
		SimplePie_Cache::register( 'wp_transient', 'WP_Feed_Cache_Transient' );
		$feed->set_cache_location( 'wp_transient' );
	} else {
		// Tương thích ngược cho SimplePie 1.2.x.
		require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
		$feed->set_cache_class( 'WP_Feed_Cache' );
	}

	$feed->set_file_class( 'WP_SimplePie_File' );

	$feed->set_feed_url( $url );
	/** Bộ lọc này được ghi chú trong wp-includes/class-wp-feed-cache-transient.php */
	$feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, $url ) );

	/**
	 * Kích hoạt ngay trước khi xử lý đối tượng feed SimplePie.
	 *
	 * @since 3.0.0
	 *
	 * @param SimplePie\SimplePie $feed Đối tượng feed SimplePie (truyền bằng tham chiếu).
	 * @param string|string[]     $url  URL của feed hoặc mảng URL của các feed cần lấy.
	 */
	do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );

	$feed->init();
	$feed->set_output_encoding( get_bloginfo( 'charset' ) );

	if ( $feed->error() ) {
		return new WP_Error( 'simplepie-error', $feed->error() );
	}

	return $feed;
}
