<?php
/**
 * Các hàm Template Tác giả dùng trong theme.
 *
 * Các hàm này phải được sử dụng trong Vòng lặp WordPress.
 *
 * @link https://codex.wordpress.org/Author_Templates
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Lấy tên tác giả của bài viết hiện tại.
 *
 * @since 1.5.0
 * @since 6.3.0 Trả về chuỗi rỗng nếu tên hiển thị của tác giả không xác định.
 *
 * @global WP_User $authordata Dữ liệu của tác giả hiện tại.
 *
 * @param string $deprecated Đã lỗi thời.
 * @return string Tên hiển thị của tác giả, chuỗi rỗng nếu không xác định.
 */
function get_the_author( $deprecated = '' ) {
	global $authordata;

	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.1.0' );
	}

	/**
	 * Lọc tên hiển thị của tác giả bài viết hiện tại.
	 *
	 * @since 2.9.0
	 *
	 * @param string $display_name Tên hiển thị của tác giả.
	 */
	return apply_filters( 'the_author', is_object( $authordata ) ? $authordata->display_name : '' );
}

/**
 * Hiển thị tên tác giả của bài viết hiện tại.
 *
 * Hành vi của hàm này dựa trên chức năng cũ trước khi có
 * get_the_author(). Hàm này không bị lỗi thời, nhưng được thiết kế để echo
 * giá trị từ get_the_author() và do đó bất kỳ theme cũ nào vẫn
 * sử dụng hành vi cũ cũng sẽ truyền giá trị từ get_the_author().
 *
 * Hành vi bình thường, mong đợi của hàm này là echo tên tác giả và không
 * trả về nó. Tuy nhiên, tương thích ngược cần được duy trì.
 *
 * @since 0.71
 *
 * @see get_the_author()
 * @link https://developer.wordpress.org/reference/functions/the_author/
 *
 * @param string $deprecated      Đã lỗi thời.
 * @param bool   $deprecated_echo Đã lỗi thời. Sử dụng get_the_author(). Echo chuỗi hoặc trả về nó.
 * @return string Tên hiển thị của tác giả, từ get_the_author().
 */
function the_author( $deprecated = '', $deprecated_echo = true ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.1.0' );
	}

	if ( true !== $deprecated_echo ) {
		_deprecated_argument(
			__FUNCTION__,
			'1.5.0',
			sprintf(
				/* translators: %s: get_the_author() */
				__( 'Use %s instead if you do not want the value echoed.' ),
				'<code>get_the_author()</code>'
			)
		);
	}

	if ( $deprecated_echo ) {
		echo get_the_author();
	}

	return get_the_author();
}

/**
 * Lấy tên tác giả đã chỉnh sửa cuối cùng bài viết hiện tại.
 *
 * @since 2.8.0
 *
 * @return string|void Tên hiển thị của tác giả, chuỗi rỗng nếu không xác định.
 */
function get_the_modified_author() {
	$last_id = get_post_meta( get_post()->ID, '_edit_last', true );

	if ( $last_id ) {
		$last_user = get_userdata( $last_id );

		/**
		 * Lọc tên hiển thị của tác giả đã chỉnh sửa cuối cùng bài viết hiện tại.
		 *
		 * @since 2.8.0
		 *
		 * @param string $display_name Tên hiển thị của tác giả, chuỗi rỗng nếu không xác định.
		 */
		return apply_filters( 'the_modified_author', $last_user ? $last_user->display_name : '' );
	}
}

/**
 * Hiển thị tên tác giả đã chỉnh sửa cuối cùng bài viết hiện tại,
 * nếu ID của tác giả có sẵn.
 *
 * @since 2.8.0
 *
 * @see get_the_author()
 */
function the_modified_author() {
	echo get_the_modified_author();
}

/**
 * Lấy dữ liệu được yêu cầu của tác giả bài viết hiện tại.
 *
 * Các giá trị hợp lệ cho tham số `$field` bao gồm:
 *
 * - admin_color
 * - aim
 * - comment_shortcuts
 * - description
 * - display_name
 * - first_name
 * - ID
 * - jabber
 * - last_name
 * - nickname
 * - plugins_last_view
 * - plugins_per_page
 * - rich_editing
 * - syntax_highlighting
 * - user_activation_key
 * - user_description
 * - user_email
 * - user_firstname
 * - user_lastname
 * - user_level
 * - user_login
 * - user_nicename
 * - user_pass
 * - user_registered
 * - user_status
 * - user_url
 * - yim
 *
 * @since 2.8.0
 *
 * @global WP_User $authordata Dữ liệu của tác giả hiện tại.
 *
 * @param string    $field   Tùy chọn. Trường người dùng cần lấy. Mặc định rỗng.
 * @param int|false $user_id Tùy chọn. ID người dùng. Mặc định là tác giả bài viết hiện tại.
 * @return string Trường của tác giả từ đối tượng DB của tác giả hiện tại, ngược lại trả về chuỗi rỗng.
 */
function get_the_author_meta( $field = '', $user_id = false ) {
	$original_user_id = $user_id;

	if ( ! $user_id ) {
		global $authordata;
		$user_id = isset( $authordata->ID ) ? $authordata->ID : 0;
	} else {
		$authordata = get_userdata( $user_id );
	}

	if ( in_array( $field, array( 'login', 'pass', 'nicename', 'email', 'url', 'registered', 'activation_key', 'status' ), true ) ) {
		$field = 'user_' . $field;
	}

	$value = isset( $authordata->$field ) ? $authordata->$field : '';

	/**
	 * Lọc giá trị của metadata người dùng được yêu cầu.
	 *
	 * Tên bộ lọc là động và phụ thuộc vào tham số $field của hàm.
	 *
	 * @since 2.8.0
	 * @since 4.3.0 Tham số `$original_user_id` đã được thêm vào.
	 *
	 * @param string    $value            Giá trị của metadata.
	 * @param int       $user_id          ID người dùng cho giá trị.
	 * @param int|false $original_user_id ID người dùng gốc, như được truyền vào hàm.
	 */
	return apply_filters( "get_the_author_{$field}", $value, $user_id, $original_user_id );
}

/**
 * Xuất trường từ đối tượng DB của người dùng. Mặc định là tác giả bài viết hiện tại.
 *
 * @since 2.8.0
 *
 * @param string    $field   Chọn trường từ bản ghi người dùng. Xem get_the_author_meta()
 *                           để biết danh sách các trường có thể sử dụng.
 * @param int|false $user_id Tùy chọn. ID người dùng. Mặc định là tác giả bài viết hiện tại.
 *
 * @see get_the_author_meta()
 */
function the_author_meta( $field = '', $user_id = false ) {
	$author_meta = get_the_author_meta( $field, $user_id );

	/**
	 * Lọc giá trị của metadata người dùng được yêu cầu.
	 *
	 * Tên bộ lọc là động và phụ thuộc vào tham số $field của hàm.
	 *
	 * @since 2.8.0
	 *
	 * @param string    $author_meta Giá trị của metadata.
	 * @param int|false $user_id     ID người dùng.
	 */
	echo apply_filters( "the_author_{$field}", $author_meta, $user_id );
}

/**
 * Lấy liên kết hoặc tên của tác giả.
 *
 * Nếu tác giả có trang chủ được thiết lập, trả về liên kết HTML, ngược lại chỉ trả về
 * tên tác giả.
 *
 * @since 3.0.0
 *
 * @global WP_User $authordata Dữ liệu của tác giả hiện tại.
 *
 * @return string Liên kết HTML nếu URL của tác giả tồn tại trong user meta,
 *                ngược lại trả về kết quả của get_the_author().
 */
function get_the_author_link() {
	if ( get_the_author_meta( 'url' ) ) {
		global $authordata;

		$author_url          = get_the_author_meta( 'url' );
		$author_display_name = get_the_author();

		$link = sprintf(
			'<a href="%1$s" title="%2$s" rel="author external">%3$s</a>',
			esc_url( $author_url ),
			/* translators: %s: Author's display name. */
			esc_attr( sprintf( __( 'Visit %s&#8217;s website' ), $author_display_name ) ),
			$author_display_name
		);

		/**
		 * Lọc HTML liên kết URL của tác giả.
		 *
		 * @since 6.0.0
		 *
		 * @param string  $link       HTML liên kết tác giả được render mặc định.
		 * @param string  $author_url URL của tác giả.
		 * @param WP_User $authordata Dữ liệu người dùng tác giả.
		 */
		return apply_filters( 'the_author_link', $link, $author_url, $authordata );
	} else {
		return get_the_author();
	}
}

/**
 * Hiển thị liên kết hoặc tên của tác giả.
 *
 * Nếu tác giả có trang chủ được thiết lập, echo liên kết HTML, ngược lại chỉ echo
 * tên tác giả.
 *
 * @link https://developer.wordpress.org/reference/functions/the_author_link/
 *
 * @since 2.1.0
 */
function the_author_link() {
	echo get_the_author_link();
}

/**
 * Lấy số lượng bài viết của tác giả bài viết hiện tại.
 *
 * @since 1.5.0
 *
 * @return int Số lượng bài viết của tác giả.
 */
function get_the_author_posts() {
	$post = get_post();
	if ( ! $post ) {
		return 0;
	}
	return count_user_posts( $post->post_author, $post->post_type );
}

/**
 * Hiển thị số lượng bài viết của tác giả bài viết hiện tại.
 *
 * @link https://developer.wordpress.org/reference/functions/the_author_posts/
 * @since 0.71
 */
function the_author_posts() {
	echo get_the_author_posts();
}

/**
 * Lấy liên kết HTML đến trang tác giả của tác giả bài viết hiện tại.
 *
 * Trả về liên kết định dạng HTML sử dụng get_author_posts_url().
 *
 * @since 4.4.0
 *
 * @global WP_User $authordata Dữ liệu của tác giả hiện tại.
 *
 * @return string Liên kết HTML đến trang tác giả, hoặc chuỗi rỗng nếu $authordata chưa được thiết lập.
 */
function get_the_author_posts_link() {
	global $authordata;

	if ( ! is_object( $authordata ) ) {
		return '';
	}

	$link = sprintf(
		'<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
		esc_url( get_author_posts_url( $authordata->ID, $authordata->user_nicename ) ),
		/* translators: %s: Author's display name. */
		esc_attr( sprintf( __( 'Posts by %s' ), get_the_author() ) ),
		get_the_author()
	);

	/**
	 * Lọc liên kết đến trang tác giả của tác giả bài viết hiện tại.
	 *
	 * @since 2.9.0
	 *
	 * @param string $link Liên kết HTML.
	 */
	return apply_filters( 'the_author_posts_link', $link );
}

/**
 * Hiển thị liên kết HTML đến trang tác giả của tác giả bài viết hiện tại.
 *
 * @since 1.2.0
 * @since 4.4.0 Được chuyển đổi thành hàm bọc cho get_the_author_posts_link()
 *
 * @param string $deprecated Không sử dụng.
 */
function the_author_posts_link( $deprecated = '' ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.1.0' );
	}
	echo get_the_author_posts_link();
}

/**
 * Lấy URL đến trang tác giả cho người dùng với ID được cung cấp.
 *
 * @since 2.1.0
 *
 * @global WP_Rewrite $wp_rewrite Thành phần viết lại WordPress.
 *
 * @param int    $author_id       ID tác giả.
 * @param string $author_nicename Tùy chọn. Nicename (slug) của tác giả. Mặc định rỗng.
 * @return string URL đến trang tác giả.
 */
function get_author_posts_url( $author_id, $author_nicename = '' ) {
	global $wp_rewrite;

	$author_id = (int) $author_id;
	$link      = $wp_rewrite->get_author_permastruct();

	if ( empty( $link ) ) {
		$file = home_url( '/' );
		$link = $file . '?author=' . $author_id;
	} else {
		if ( '' === $author_nicename ) {
			$user = get_userdata( $author_id );
			if ( ! empty( $user->user_nicename ) ) {
				$author_nicename = $user->user_nicename;
			}
		}
		$link = str_replace( '%author%', $author_nicename, $link );
		$link = home_url( user_trailingslashit( $link ) );
	}

	/**
	 * Lọc URL đến trang tác giả.
	 *
	 * @since 2.1.0
	 *
	 * @param string $link            URL đến trang tác giả.
	 * @param int    $author_id       ID của tác giả.
	 * @param string $author_nicename Nicename của tác giả.
	 */
	$link = apply_filters( 'author_link', $link, $author_id, $author_nicename );

	return $link;
}

/**
 * Liệt kê tất cả các tác giả của trang web, với nhiều tùy chọn có sẵn.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_list_authors/
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string|array $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số mặc định.
 *
 *     @type string       $orderby       Cách sắp xếp tác giả. Chấp nhận 'nicename', 'email', 'url', 'registered',
 *                                       'user_nicename', 'user_email', 'user_url', 'user_registered', 'name',
 *                                       'display_name', 'post_count', 'ID', 'meta_value', 'user_login'. Mặc định 'name'.
 *     @type string       $order         Hướng sắp xếp cho $orderby. Chấp nhận 'ASC', 'DESC'. Mặc định 'ASC'.
 *     @type int          $number        Số lượng tác giả tối đa để trả về hoặc hiển thị. Mặc định rỗng (tất cả tác giả).
 *     @type bool         $optioncount   Hiển thị số lượng trong ngoặc đơn bên cạnh tên tác giả. Mặc định false.
 *     @type bool         $exclude_admin Có loại trừ tài khoản 'admin' hay không, nếu nó tồn tại. Mặc định true.
 *     @type bool         $show_fullname Có hiển thị tên đầy đủ của tác giả hay không. Mặc định false.
 *     @type bool         $hide_empty    Có ẩn các tác giả không có bài viết hay không. Mặc định true.
 *     @type string       $feed          Nếu không rỗng, hiển thị liên kết đến feed của tác giả và sử dụng văn bản này làm
 *                                       tham số alt của liên kết. Mặc định rỗng.
 *     @type string       $feed_image    Nếu không rỗng, hiển thị liên kết đến feed của tác giả và sử dụng URL hình ảnh này
 *                                       làm anchor có thể nhấp. Mặc định rỗng.
 *     @type string       $feed_type     Loại feed để liên kết. Các giá trị có thể bao gồm 'rss2', 'atom'.
 *                                       Mặc định là giá trị của get_default_feed().
 *     @type bool         $echo          Có xuất kết quả hay trả về nó. Mặc định true.
 *     @type string       $style         Nếu 'list', mỗi tác giả được bọc trong phần tử `<li>`, ngược lại các tác giả
 *                                       sẽ được phân tách bằng dấu phẩy.
 *     @type bool         $html          Có liệt kê các mục ở dạng HTML hay văn bản thuần. Mặc định true.
 *     @type int[]|string $exclude       Mảng hoặc danh sách phân tách bằng dấu phẩy/khoảng trắng các ID tác giả cần loại trừ. Mặc định rỗng.
 *     @type int[]|string $include       Mảng hoặc danh sách phân tách bằng dấu phẩy/khoảng trắng các ID tác giả cần bao gồm. Mặc định rỗng.
 * }
 * @return void|string Void nếu đối số 'echo' là true, danh sách tác giả nếu 'echo' là false.
 */
function wp_list_authors( $args = '' ) {
	global $wpdb;

	$defaults = array(
		'orderby'       => 'name',
		'order'         => 'ASC',
		'number'        => '',
		'optioncount'   => false,
		'exclude_admin' => true,
		'show_fullname' => false,
		'hide_empty'    => true,
		'feed'          => '',
		'feed_image'    => '',
		'feed_type'     => '',
		'echo'          => true,
		'style'         => 'list',
		'html'          => true,
		'exclude'       => '',
		'include'       => '',
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	$return = '';

	$query_args           = wp_array_slice_assoc( $parsed_args, array( 'orderby', 'order', 'number', 'exclude', 'include' ) );
	$query_args['fields'] = 'ids';

	/**
	 * Lọc các đối số truy vấn cho danh sách tất cả tác giả của trang web.
	 *
	 * @since 6.1.0
	 *
	 * @param array $query_args  Các đối số truy vấn cho get_users().
	 * @param array $parsed_args Các đối số được truyền vào wp_list_authors() kết hợp với giá trị mặc định.
	 */
	$query_args = apply_filters( 'wp_list_authors_args', $query_args, $parsed_args );

	$authors     = get_users( $query_args );
	$post_counts = array();

	/**
	 * Lọc xem có bỏ qua việc thực hiện truy vấn đếm bài viết của tác giả hay không.
	 *
	 * @since 6.1.0
	 *
	 * @param int[]|false $post_counts Mảng số lượng bài viết, theo khóa ID tác giả.
	 * @param array       $parsed_args Các đối số được truyền vào wp_list_authors() kết hợp với giá trị mặc định.
	 */
	$post_counts = apply_filters( 'pre_wp_list_authors_post_counts_query', false, $parsed_args );

	if ( ! is_array( $post_counts ) ) {
		$post_counts       = array();
		$post_counts_query = $wpdb->get_results(
			"SELECT DISTINCT post_author, COUNT(ID) AS count
			FROM $wpdb->posts
			WHERE " . get_private_posts_cap_sql( 'post' ) . '
			GROUP BY post_author'
		);

		foreach ( (array) $post_counts_query as $row ) {
			$post_counts[ $row->post_author ] = $row->count;
		}
	}

	foreach ( $authors as $author_id ) {
		$posts = isset( $post_counts[ $author_id ] ) ? $post_counts[ $author_id ] : 0;

		if ( ! $posts && $parsed_args['hide_empty'] ) {
			continue;
		}

		$author = get_userdata( $author_id );

		if ( $parsed_args['exclude_admin'] && 'admin' === $author->display_name ) {
			continue;
		}

		if ( $parsed_args['show_fullname'] && $author->first_name && $author->last_name ) {
			$name = sprintf(
				/* translators: 1: User's first name, 2: Last name. */
				_x( '%1$s %2$s', 'Display name based on first name and last name' ),
				$author->first_name,
				$author->last_name
			);
		} else {
			$name = $author->display_name;
		}

		if ( ! $parsed_args['html'] ) {
			$return .= $name . ', ';

			continue; // Không cần xử lý thêm HTML.
		}

		if ( 'list' === $parsed_args['style'] ) {
			$return .= '<li>';
		}

		$link = sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			esc_url( get_author_posts_url( $author->ID, $author->user_nicename ) ),
			/* translators: %s: Author's display name. */
			esc_attr( sprintf( __( 'Posts by %s' ), $author->display_name ) ),
			$name
		);

		if ( ! empty( $parsed_args['feed_image'] ) || ! empty( $parsed_args['feed'] ) ) {
			$link .= ' ';
			if ( empty( $parsed_args['feed_image'] ) ) {
				$link .= '(';
			}

			$link .= '<a href="' . get_author_feed_link( $author->ID, $parsed_args['feed_type'] ) . '"';

			$alt = '';
			if ( ! empty( $parsed_args['feed'] ) ) {
				$alt  = ' alt="' . esc_attr( $parsed_args['feed'] ) . '"';
				$name = $parsed_args['feed'];
			}

			$link .= '>';

			if ( ! empty( $parsed_args['feed_image'] ) ) {
				$link .= '<img src="' . esc_url( $parsed_args['feed_image'] ) . '" style="border: none;"' . $alt . ' />';
			} else {
				$link .= $name;
			}

			$link .= '</a>';

			if ( empty( $parsed_args['feed_image'] ) ) {
				$link .= ')';
			}
		}

		if ( $parsed_args['optioncount'] ) {
			$link .= ' (' . $posts . ')';
		}

		$return .= $link;
		$return .= ( 'list' === $parsed_args['style'] ) ? '</li>' : ', ';
	}

	$return = rtrim( $return, ', ' );

	if ( $parsed_args['echo'] ) {
		echo $return;
	} else {
		return $return;
	}
}

/**
 * Xác định xem trang web này có nhiều hơn một tác giả hay không.
 *
 * Kiểm tra xem có nhiều hơn một tác giả đã xuất bản bài viết hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Thẻ điều kiện} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 3.2.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @return bool Trang web có nhiều hơn một tác giả hay không.
 */
function is_multi_author() {
	global $wpdb;

	$is_multi_author = get_transient( 'is_multi_author' );
	if ( false === $is_multi_author ) {
		$rows            = (array) $wpdb->get_col( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 2" );
		$is_multi_author = 1 < count( $rows ) ? 1 : 0;
		set_transient( 'is_multi_author', $is_multi_author );
	}

	/**
	 * Lọc xem trang web có nhiều hơn một tác giả đã xuất bản bài viết hay không.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $is_multi_author Giá trị $is_multi_author có nên được đánh giá là true hay không.
	 */
	return apply_filters( 'is_multi_author', (bool) $is_multi_author );
}

/**
 * Hàm trợ giúp để xóa bộ nhớ đệm cho số lượng tác giả.
 *
 * @since 3.2.0
 * @access private
 */
function __clear_multi_author_cache() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	delete_transient( 'is_multi_author' );
}
