<?php
/**
 * Các thẻ template chung có thể sử dụng ở bất kỳ đâu trong template.
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Tải template header.
 *
 * Include template header cho theme hoặc nếu chỉ định tên thì sẽ include
 * một header chuyên biệt.
 *
 * Đối với tham số, nếu file có tên "header-special.php" thì chỉ định
 * "special".
 *
 * @since 1.5.0
 * @since 5.5.0 Đã thêm giá trị trả về.
 * @since 5.5.0 Đã thêm tham số `$args`.
 *
 * @param string|null $name Tên của header chuyên biệt. Mặc định null.
 * @param array       $args Tùy chọn. Các đối số bổ sung truyền vào template header.
 *                          Mặc định mảng rỗng.
 * @return void|false Void khi thành công, false nếu template không tồn tại.
 */
function get_header( $name = null, $args = array() ) {
	/**
	 * Kích hoạt trước khi file template header được tải.
	 *
	 * @since 2.1.0
	 * @since 2.8.0 Đã thêm tham số `$name`.
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string|null $name Tên file header cụ thể để sử dụng. Null cho header mặc định.
	 * @param array       $args Các đối số bổ sung truyền vào template header.
	 */
	do_action( 'get_header', $name, $args );

	$templates = array();
	$name      = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "header-{$name}.php";
	}

	$templates[] = 'header.php';

	if ( ! locate_template( $templates, true, true, $args ) ) {
		return false;
	}
}

/**
 * Tải template footer.
 *
 * Include template footer cho theme hoặc nếu chỉ định tên thì sẽ include
 * một footer chuyên biệt.
 *
 * Đối với tham số, nếu file có tên "footer-special.php" thì chỉ định
 * "special".
 *
 * @since 1.5.0
 * @since 5.5.0 Đã thêm giá trị trả về.
 * @since 5.5.0 Đã thêm tham số `$args`.
 *
 * @param string|null $name Tên của footer chuyên biệt. Mặc định null.
 * @param array       $args Tùy chọn. Các đối số bổ sung truyền vào template footer.
 *                          Mặc định mảng rỗng.
 * @return void|false Void khi thành công, false nếu template không tồn tại.
 */
function get_footer( $name = null, $args = array() ) {
	/**
	 * Kích hoạt trước khi file template footer được tải.
	 *
	 * @since 2.1.0
	 * @since 2.8.0 Đã thêm tham số `$name`.
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string|null $name Tên file footer cụ thể để sử dụng. Null cho footer mặc định.
	 * @param array       $args Các đối số bổ sung truyền vào template footer.
	 */
	do_action( 'get_footer', $name, $args );

	$templates = array();
	$name      = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "footer-{$name}.php";
	}

	$templates[] = 'footer.php';

	if ( ! locate_template( $templates, true, true, $args ) ) {
		return false;
	}
}

/**
 * Tải template sidebar.
 *
 * Include template sidebar cho theme hoặc nếu chỉ định tên thì sẽ include
 * một sidebar chuyên biệt.
 *
 * Đối với tham số, nếu file có tên "sidebar-special.php" thì chỉ định
 * "special".
 *
 * @since 1.5.0
 * @since 5.5.0 Đã thêm giá trị trả về.
 * @since 5.5.0 Đã thêm tham số `$args`.
 *
 * @param string|null $name Tên của sidebar chuyên biệt. Mặc định null.
 * @param array       $args Tùy chọn. Các đối số bổ sung truyền vào template sidebar.
 *                          Mặc định mảng rỗng.
 * @return void|false Void khi thành công, false nếu template không tồn tại.
 */
function get_sidebar( $name = null, $args = array() ) {
	/**
	 * Kích hoạt trước khi file template sidebar được tải.
	 *
	 * @since 2.2.0
	 * @since 2.8.0 Đã thêm tham số `$name`.
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string|null $name Tên file sidebar cụ thể để sử dụng. Null cho sidebar mặc định.
	 * @param array       $args Các đối số bổ sung truyền vào template sidebar.
	 */
	do_action( 'get_sidebar', $name, $args );

	$templates = array();
	$name      = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "sidebar-{$name}.php";
	}

	$templates[] = 'sidebar.php';

	if ( ! locate_template( $templates, true, true, $args ) ) {
		return false;
	}
}

/**
 * Tải một phần template vào template.
 *
 * Cung cấp cơ chế đơn giản cho child theme ghi đè các phần mã có thể tái sử dụng
 * trong theme.
 *
 * Include phần template được đặt tên cho theme hoặc nếu chỉ định tên thì sẽ include
 * một phần chuyên biệt. Nếu theme không chứa file {slug}.php
 * thì sẽ không có template nào được include.
 *
 * Template được include bằng require, không phải require_once, vì vậy bạn có thể include
 * cùng một phần template nhiều lần.
 *
 * Đối với tham số $name, nếu file có tên "{slug}-special.php" thì chỉ định
 * "special".
 *
 * @since 3.0.0
 * @since 5.5.0 Đã thêm giá trị trả về.
 * @since 5.5.0 Đã thêm tham số `$args`.
 *
 * @param string      $slug Tên slug cho template chung.
 * @param string|null $name Tùy chọn. Tên của template chuyên biệt. Mặc định null.
 * @param array       $args Tùy chọn. Các đối số bổ sung truyền vào template.
 *                          Mặc định mảng rỗng.
 * @return void|false Void khi thành công, false nếu template không tồn tại.
 */
function get_template_part( $slug, $name = null, $args = array() ) {
	/**
	 * Kích hoạt trước khi file phần template được chỉ định được tải.
	 *
	 * Phần động của tên hook, `$slug`, tham chiếu đến tên slug
	 * cho phần template chung.
	 *
	 * @since 3.0.0
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string      $slug Tên slug cho template chung.
	 * @param string|null $name Tên của template chuyên biệt
	 *                          hoặc null nếu không có.
	 * @param array       $args Các đối số bổ sung truyền vào template.
	 */
	do_action( "get_template_part_{$slug}", $slug, $name, $args );

	$templates = array();
	$name      = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	/**
	 * Kích hoạt trước khi thực hiện nỗ lực tìm và tải một phần template.
	 *
	 * @since 5.2.0
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string   $slug      Tên slug cho template chung.
	 * @param string   $name      Tên của template chuyên biệt
	 *                            hoặc chuỗi rỗng nếu không có.
	 * @param string[] $templates Mảng các file template để tìm kiếm, theo thứ tự.
	 * @param array    $args      Các đối số bổ sung truyền vào template.
	 */
	do_action( 'get_template_part', $slug, $name, $templates, $args );

	if ( ! locate_template( $templates, true, false, $args ) ) {
		return false;
	}
}

/**
 * Hiển thị biểu mẫu tìm kiếm.
 *
 * Trước tiên sẽ cố gắng tìm file searchform.php trong child theme hoặc
 * theme cha, sau đó tải nó. Nếu không tồn tại, biểu mẫu tìm kiếm mặc định
 * sẽ được hiển thị. Biểu mẫu tìm kiếm mặc định là HTML.
 * Có một bộ lọc được áp dụng cho HTML biểu mẫu tìm kiếm để chỉnh sửa hoặc thay thế
 * nó. Bộ lọc là {@see 'get_search_form'}.
 *
 * Hàm này chủ yếu được sử dụng bởi các theme muốn đặt cố định biểu mẫu tìm kiếm
 * vào sidebar và cũng bởi widget tìm kiếm trong WordPress.
 *
 * Ngoài ra còn có một action được gọi mỗi khi hàm chạy,
 * {@see 'pre_get_search_form'}. Điều này hữu ích cho việc xuất JavaScript mà
 * tìm kiếm cần hoặc các định dạng áp dụng cho phần đầu của
 * tìm kiếm.
 *
 * @since 2.7.0
 * @since 5.2.0 Tham số mảng `$args` được thêm thay thế cho cờ boolean `$echo`.
 *
 * @param array $args {
 *     Tùy chọn. Mảng các đối số hiển thị.
 *
 *     @type bool   $echo       Có echo hay trả về biểu mẫu không. Mặc định true.
 *     @type string $aria_label Nhãn ARIA cho biểu mẫu tìm kiếm. Hữu ích để phân biệt
 *                              nhiều biểu mẫu tìm kiếm trên cùng một trang và cải thiện
 *                              khả năng truy cập. Mặc định rỗng.
 * }
 * @return void|string Void nếu đối số 'echo' là true, HTML biểu mẫu tìm kiếm nếu 'echo' là false.
 */
function get_search_form( $args = array() ) {
	/**
	 * Kích hoạt trước khi biểu mẫu tìm kiếm được lấy, ở đầu get_search_form().
	 *
	 * @since 2.7.0 dưới dạng action 'get_search_form'.
	 * @since 3.6.0
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @link https://core.trac.wordpress.org/ticket/19321
	 *
	 * @param array $args Mảng các đối số để xây dựng biểu mẫu tìm kiếm.
	 *                    Xem get_search_form() để biết thông tin về các đối số được chấp nhận.
	 */
	do_action( 'pre_get_search_form', $args );

	$echo = true;

	if ( ! is_array( $args ) ) {
		/*
		 * Tương thích ngược: để đảm bảo các lần sử dụng trước của get_search_form() tiếp tục
		 * hoạt động như mong đợi, chúng ta xử lý giá trị cho tham số boolean $echo đã bị loại bỏ
		 * trong 5.2.0. Sau đó xử lý mảng $args và ép kiểu các giá trị mặc định.
		 */
		$echo = (bool) $args;

		// Đặt mảng rỗng và cho phép các đối số mặc định tiếp quản.
		$args = array();
	}

	// Mặc định là echo và không xuất nhãn tùy chỉnh trên biểu mẫu.
	$defaults = array(
		'echo'       => $echo,
		'aria_label' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * Lọc mảng các đối số được sử dụng khi tạo biểu mẫu tìm kiếm.
	 *
	 * @since 5.2.0
	 *
	 * @param array $args Mảng các đối số để xây dựng biểu mẫu tìm kiếm.
	 *                    Xem get_search_form() để biết thông tin về các đối số được chấp nhận.
	 */
	$args = apply_filters( 'search_form_args', $args );

	// Đảm bảo rằng các đối số đã lọc chứa tất cả các giá trị mặc định cần thiết.
	$args = array_merge( $defaults, $args );

	$format = current_theme_supports( 'html5', 'search-form' ) ? 'html5' : 'xhtml';

	/**
	 * Lọc định dạng HTML của biểu mẫu tìm kiếm.
	 *
	 * @since 3.6.0
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string $format Loại đánh dấu sử dụng trong biểu mẫu tìm kiếm.
	 *                       Chấp nhận 'html5', 'xhtml'.
	 * @param array  $args   Mảng các đối số để xây dựng biểu mẫu tìm kiếm.
	 *                       Xem get_search_form() để biết thông tin về các đối số được chấp nhận.
	 */
	$format = apply_filters( 'search_form_format', $format, $args );

	$search_form_template = locate_template( 'searchform.php' );

	if ( '' !== $search_form_template ) {
		ob_start();
		require $search_form_template;
		$form = ob_get_clean();
	} else {
		// Xây dựng chuỗi chứa aria-label để sử dụng cho biểu mẫu tìm kiếm.
		if ( $args['aria_label'] ) {
			$aria_label = 'aria-label="' . esc_attr( $args['aria_label'] ) . '" ';
		} else {
			/*
			 * Nếu không có aria-label tùy chỉnh, chúng ta có thể đặt giá trị mặc định ở đây. Hiện tại
			 * nó rỗng vì chưa chắc chắn giá trị mặc định nên là gì.
			 */
			$aria_label = '';
		}

		if ( 'html5' === $format ) {
			$form = '<form role="search" ' . $aria_label . 'method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">
				<label>
					<span class="screen-reader-text">' .
					/* translators: Hidden accessibility text. */
					_x( 'Search for:', 'label' ) .
					'</span>
					<input type="search" class="search-field" placeholder="' . esc_attr_x( 'Search &hellip;', 'placeholder' ) . '" value="' . get_search_query() . '" name="s" />
				</label>
				<input type="submit" class="search-submit" value="' . esc_attr_x( 'Search', 'submit button' ) . '" />
			</form>';
		} else {
			$form = '<form role="search" ' . $aria_label . 'method="get" id="searchform" class="searchform" action="' . esc_url( home_url( '/' ) ) . '">
				<div>
					<label class="screen-reader-text" for="s">' .
					/* translators: Hidden accessibility text. */
					_x( 'Search for:', 'label' ) .
					'</label>
					<input type="text" value="' . get_search_query() . '" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="' . esc_attr_x( 'Search', 'submit button' ) . '" />
				</div>
			</form>';
		}
	}

	/**
	 * Lọc đầu ra HTML của biểu mẫu tìm kiếm.
	 *
	 * @since 2.7.0
	 * @since 5.5.0 Đã thêm tham số `$args`.
	 *
	 * @param string $form Đầu ra HTML của biểu mẫu tìm kiếm.
	 * @param array  $args Mảng các đối số để xây dựng biểu mẫu tìm kiếm.
	 *                     Xem get_search_form() để biết thông tin về các đối số được chấp nhận.
	 */
	$result = apply_filters( 'get_search_form', $form, $args );

	if ( null === $result ) {
		$result = $form;
	}

	if ( $args['echo'] ) {
		echo $result;
	} else {
		return $result;
	}
}

/**
 * Hiển thị liên kết Đăng nhập/Đăng xuất.
 *
 * Hiển thị một liên kết cho phép người dùng điều hướng đến trang Đăng nhập để đăng nhập
 * hoặc đăng xuất tùy thuộc vào việc họ hiện đang đăng nhập hay không.
 *
 * @since 1.5.0
 *
 * @param string $redirect Tùy chọn. Đường dẫn để chuyển hướng khi đăng nhập/đăng xuất.
 * @param bool   $display  Mặc định echo và không trả về liên kết.
 * @return void|string Void nếu đối số `$display` là true, liên kết đăng nhập/đăng xuất nếu `$display` là false.
 */
function wp_loginout( $redirect = '', $display = true ) {
	if ( ! is_user_logged_in() ) {
		$link = '<a href="' . esc_url( wp_login_url( $redirect ) ) . '">' . __( 'Log in' ) . '</a>';
	} else {
		$link = '<a href="' . esc_url( wp_logout_url( $redirect ) ) . '">' . __( 'Log out' ) . '</a>';
	}

	if ( $display ) {
		/**
		 * Lọc đầu ra HTML cho liên kết Đăng nhập/Đăng xuất.
		 *
		 * @since 1.5.0
		 *
		 * @param string $link Nội dung liên kết HTML.
		 */
		echo apply_filters( 'loginout', $link );
	} else {
		/** Bộ lọc này được ghi lại trong wp-includes/general-template.php */
		return apply_filters( 'loginout', $link );
	}
}

/**
 * Lấy URL đăng xuất.
 *
 * Trả về URL cho phép người dùng đăng xuất khỏi trang web.
 *
 * @since 2.7.0
 *
 * @param string $redirect Đường dẫn để chuyển hướng khi đăng xuất.
 * @return string URL đăng xuất. Lưu ý: Được mã hóa HTML qua esc_html() trong wp_nonce_url().
 */
function wp_logout_url( $redirect = '' ) {
	$args = array();
	if ( ! empty( $redirect ) ) {
		$args['redirect_to'] = urlencode( $redirect );
	}

	$logout_url = add_query_arg( $args, site_url( 'wp-login.php?action=logout', 'login' ) );
	$logout_url = wp_nonce_url( $logout_url, 'log-out' );

	/**
	 * Lọc URL đăng xuất.
	 *
	 * @since 2.8.0
	 *
	 * @param string $logout_url URL đăng xuất đã được mã hóa HTML.
	 * @param string $redirect   Đường dẫn để chuyển hướng khi đăng xuất.
	 */
	return apply_filters( 'logout_url', $logout_url, $redirect );
}

/**
 * Lấy URL đăng nhập.
 *
 * @since 2.7.0
 *
 * @param string $redirect     Đường dẫn để chuyển hướng khi đăng nhập.
 * @param bool   $force_reauth Có buộc xác thực lại hay không, ngay cả khi cookie đã tồn tại.
 *                             Mặc định false.
 * @return string URL đăng nhập. Không được mã hóa HTML.
 */
function wp_login_url( $redirect = '', $force_reauth = false ) {
	$login_url = site_url( 'wp-login.php', 'login' );

	if ( ! empty( $redirect ) ) {
		$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
	}

	if ( $force_reauth ) {
		$login_url = add_query_arg( 'reauth', '1', $login_url );
	}

	/**
	 * Lọc URL đăng nhập.
	 *
	 * @since 2.8.0
	 * @since 4.2.0 Đã thêm tham số `$force_reauth`.
	 *
	 * @param string $login_url    URL đăng nhập. Không được mã hóa HTML.
	 * @param string $redirect     Đường dẫn để chuyển hướng khi đăng nhập, nếu được cung cấp.
	 * @param bool   $force_reauth Có buộc xác thực lại hay không, ngay cả khi cookie đã tồn tại.
	 */
	return apply_filters( 'login_url', $login_url, $redirect, $force_reauth );
}

/**
 * Trả về URL cho phép người dùng đăng ký trên trang web.
 *
 * @since 3.6.0
 *
 * @return string URL đăng ký người dùng.
 */
function wp_registration_url() {
	/**
	 * Lọc URL đăng ký người dùng.
	 *
	 * @since 3.6.0
	 *
	 * @param string $register URL đăng ký người dùng.
	 */
	return apply_filters( 'register_url', site_url( 'wp-login.php?action=register', 'login' ) );
}

/**
 * Cung cấp biểu mẫu đăng nhập đơn giản để sử dụng ở bất kỳ đâu trong WordPress.
 *
 * HTML biểu mẫu đăng nhập được echo theo mặc định. Truyền giá trị false cho `$echo` để trả về thay thế.
 *
 * @since 3.0.0
 * @since 6.6.0 Đã thêm đối số `required_username` và `required_password`.
 *
 * @param array $args {
 *     Tùy chọn. Mảng các tùy chọn để điều khiển đầu ra biểu mẫu. Mặc định mảng rỗng.
 *
 *     @type bool   $echo              Có hiển thị biểu mẫu đăng nhập hay trả về mã HTML. Mặc định true (echo).
 *     @type string $redirect          URL để chuyển hướng. Phải là đường dẫn tuyệt đối, ví dụ "https://example.com/mypage/".
 *                                     Mặc định chuyển hướng về URI yêu cầu.
 *     @type string $form_id           Giá trị thuộc tính ID cho biểu mẫu. Mặc định 'loginform'.
 *     @type string $label_username    Nhãn cho trường tên người dùng hoặc địa chỉ email. Mặc định 'Username or Email Address'.
 *     @type string $label_password    Nhãn cho trường mật khẩu. Mặc định 'Password'.
 *     @type string $label_remember    Nhãn cho trường ghi nhớ. Mặc định 'Remember Me'.
 *     @type string $label_log_in      Nhãn cho nút gửi. Mặc định 'Log In'.
 *     @type string $id_username       Giá trị thuộc tính ID cho trường tên người dùng. Mặc định 'user_login'.
 *     @type string $id_password       Giá trị thuộc tính ID cho trường mật khẩu. Mặc định 'user_pass'.
 *     @type string $id_remember       Giá trị thuộc tính ID cho trường ghi nhớ. Mặc định 'rememberme'.
 *     @type string $id_submit         Giá trị thuộc tính ID cho nút gửi. Mặc định 'wp-submit'.
 *     @type bool   $remember          Có hiển thị hộp kiểm "rememberme" trong biểu mẫu hay không.
 *     @type string $value_username    Giá trị mặc định cho trường tên người dùng. Mặc định rỗng.
 *     @type bool   $value_remember    Có đánh dấu hộp kiểm "Remember Me" theo mặc định hay không.
 *                                     Mặc định false (không đánh dấu).
 *     @type bool   $required_username Trường tên người dùng có thuộc tính 'required' hay không.
 *                                     Mặc định false.
 *     @type bool   $required_password Trường mật khẩu có thuộc tính 'required' hay không.
 *                                     Mặc định false.
 *
 * }
 * @return void|string Void nếu đối số 'echo' là true, HTML biểu mẫu đăng nhập nếu 'echo' là false.
 */
function wp_login_form( $args = array() ) {
	$defaults = array(
		'echo'              => true,
		// Giá trị mặc định 'redirect' đưa người dùng quay lại URI yêu cầu.
		'redirect'          => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'form_id'           => 'loginform',
		'label_username'    => __( 'Username or Email Address' ),
		'label_password'    => __( 'Password' ),
		'label_remember'    => __( 'Remember Me' ),
		'label_log_in'      => __( 'Log In' ),
		'id_username'       => 'user_login',
		'id_password'       => 'user_pass',
		'id_remember'       => 'rememberme',
		'id_submit'         => 'wp-submit',
		'remember'          => true,
		'value_username'    => '',
		// Đặt 'value_remember' thành true để mặc định hộp kiểm "Remember me" được đánh dấu.
		'value_remember'    => false,
		// Đặt 'required_username' thành true để thêm thuộc tính required vào trường tên người dùng.
		'required_username' => false,
		// Đặt 'required_password' thành true để thêm thuộc tính required vào trường mật khẩu.
		'required_password' => false,
	);

	/**
	 * Lọc các đối số đầu ra mặc định của biểu mẫu đăng nhập.
	 *
	 * @since 3.0.0
	 *
	 * @see wp_login_form()
	 *
	 * @param array $defaults Mảng các đối số mặc định của biểu mẫu đăng nhập.
	 */
	$args = wp_parse_args( $args, apply_filters( 'login_form_defaults', $defaults ) );

	/**
	 * Lọc nội dung hiển thị ở đầu biểu mẫu đăng nhập.
	 *
	 * Bộ lọc được đánh giá ngay sau thẻ mở của phần tử form.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content Nội dung để hiển thị. Mặc định rỗng.
	 * @param array  $args    Mảng các đối số biểu mẫu đăng nhập.
	 */
	$login_form_top = apply_filters( 'login_form_top', '', $args );

	/**
	 * Lọc nội dung hiển thị ở giữa biểu mẫu đăng nhập.
	 *
	 * Bộ lọc được đánh giá ngay sau vị trí hiển thị trường 'login-password'.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content Nội dung để hiển thị. Mặc định rỗng.
	 * @param array  $args    Mảng các đối số biểu mẫu đăng nhập.
	 */
	$login_form_middle = apply_filters( 'login_form_middle', '', $args );

	/**
	 * Lọc nội dung hiển thị ở cuối biểu mẫu đăng nhập.
	 *
	 * Bộ lọc được đánh giá ngay trước thẻ đóng của phần tử form.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content Nội dung để hiển thị. Mặc định rỗng.
	 * @param array  $args    Mảng các đối số biểu mẫu đăng nhập.
	 */
	$login_form_bottom = apply_filters( 'login_form_bottom', '', $args );

	$form =
		sprintf(
			'<form name="%1$s" id="%1$s" action="%2$s" method="post">',
			esc_attr( $args['form_id'] ),
			esc_url( site_url( 'wp-login.php', 'login_post' ) )
		) .
		$login_form_top .
		sprintf(
			'<p class="login-username">
				<label for="%1$s">%2$s</label>
				<input type="text" name="log" id="%1$s" autocomplete="username" class="input" value="%3$s" size="20"%4$s />
			</p>',
			esc_attr( $args['id_username'] ),
			esc_html( $args['label_username'] ),
			esc_attr( $args['value_username'] ),
			( $args['required_username'] ? ' required="required"' : '' )
		) .
		sprintf(
			'<p class="login-password">
				<label for="%1$s">%2$s</label>
				<input type="password" name="pwd" id="%1$s" autocomplete="current-password" spellcheck="false" class="input" value="" size="20"%3$s />
			</p>',
			esc_attr( $args['id_password'] ),
			esc_html( $args['label_password'] ),
			( $args['required_password'] ? ' required="required"' : '' )
		) .
		$login_form_middle .
		( $args['remember'] ?
			sprintf(
				'<p class="login-remember"><label><input name="rememberme" type="checkbox" id="%1$s" value="forever"%2$s /> %3$s</label></p>',
				esc_attr( $args['id_remember'] ),
				( $args['value_remember'] ? ' checked="checked"' : '' ),
				esc_html( $args['label_remember'] )
			) : ''
		) .
		sprintf(
			'<p class="login-submit">
				<input type="submit" name="wp-submit" id="%1$s" class="button button-primary" value="%2$s" />
				<input type="hidden" name="redirect_to" value="%3$s" />
			</p>',
			esc_attr( $args['id_submit'] ),
			esc_attr( $args['label_log_in'] ),
			esc_url( $args['redirect'] )
		) .
		$login_form_bottom .
		'</form>';

	if ( $args['echo'] ) {
		echo $form;
	} else {
		return $form;
	}
}

/**
 * Trả về URL cho phép người dùng đặt lại mật khẩu đã mất.
 *
 * @since 2.8.0
 *
 * @param string $redirect Đường dẫn để chuyển hướng khi đăng nhập.
 * @return string URL quên mật khẩu.
 */
function wp_lostpassword_url( $redirect = '' ) {
	$args = array(
		'action' => 'lostpassword',
	);

	if ( ! empty( $redirect ) ) {
		$args['redirect_to'] = urlencode( $redirect );
	}

	if ( is_multisite() ) {
		$blog_details  = get_site();
		$wp_login_path = $blog_details->path . 'wp-login.php';
	} else {
		$wp_login_path = 'wp-login.php';
	}

	$lostpassword_url = add_query_arg( $args, network_site_url( $wp_login_path, 'login' ) );

	/**
	 * Lọc URL quên mật khẩu.
	 *
	 * @since 2.8.0
	 *
	 * @param string $lostpassword_url URL trang quên mật khẩu.
	 * @param string $redirect         Đường dẫn để chuyển hướng khi đăng nhập.
	 */
	return apply_filters( 'lostpassword_url', $lostpassword_url, $redirect );
}

/**
 * Hiển thị liên kết Đăng ký hoặc Quản trị.
 *
 * Hiển thị một liên kết cho phép người dùng điều hướng đến trang đăng ký nếu
 * chưa đăng nhập và đăng ký được bật hoặc đến bảng điều khiển nếu đã đăng nhập.
 *
 * @since 1.5.0
 *
 * @param string $before  Văn bản xuất trước liên kết. Mặc định `<li>`.
 * @param string $after   Văn bản xuất sau liên kết. Mặc định `</li>`.
 * @param bool   $display Mặc định echo và không trả về liên kết.
 * @return void|string Void nếu đối số `$display` là true, liên kết đăng ký hoặc quản trị
 *                     nếu `$display` là false.
 */
function wp_register( $before = '<li>', $after = '</li>', $display = true ) {
	if ( ! is_user_logged_in() ) {
		if ( get_option( 'users_can_register' ) ) {
			$link = $before . '<a href="' . esc_url( wp_registration_url() ) . '">' . __( 'Register' ) . '</a>' . $after;
		} else {
			$link = '';
		}
	} elseif ( current_user_can( 'read' ) ) {
		$link = $before . '<a href="' . admin_url() . '">' . __( 'Site Admin' ) . '</a>' . $after;
	} else {
		$link = '';
	}

	/**
	 * Lọc liên kết HTML đến trang Đăng ký hoặc Quản trị.
	 *
	 * Người dùng được chuyển đến trang quản trị nếu đã đăng nhập, hoặc trang đăng ký
	 * nếu được bật và chưa đăng nhập.
	 *
	 * @since 1.5.0
	 *
	 * @param string $link Mã HTML cho liên kết đến trang Đăng ký hoặc Quản trị.
	 */
	$link = apply_filters( 'register', $link );

	if ( $display ) {
		echo $link;
	} else {
		return $link;
	}
}

/**
 * Hàm chứa của theme cho action 'wp_meta'.
 *
 * Action {@see 'wp_meta'} có thể có nhiều mục đích, tùy thuộc vào cách bạn sử dụng,
 * nhưng một mục đích có thể là cho phép chuyển đổi theme.
 *
 * @since 1.5.0
 *
 * @link https://core.trac.wordpress.org/ticket/1458 Giải thích action 'wp_meta'.
 */
function wp_meta() {
	/**
	 * Kích hoạt trước khi hiển thị nội dung echo trong sidebar.
	 *
	 * @since 1.5.0
	 */
	do_action( 'wp_meta' );
}

/**
 * Hiển thị thông tin về trang web hiện tại.
 *
 * @since 0.71
 *
 * @see get_bloginfo() Để biết các giá trị `$show` có thể sử dụng
 *
 * @param string $show Tùy chọn. Thông tin trang web để hiển thị. Mặc định rỗng.
 */
function bloginfo( $show = '' ) {
	echo get_bloginfo( $show, 'display' );
}

/**
 * Lấy thông tin về trang web hiện tại.
 *
 * Các giá trị có thể sử dụng cho `$show` bao gồm:
 *
 * - 'name' - Tiêu đề trang web (đặt trong Cài đặt > Tổng quan)
 * - 'description' - Khẩu hiệu trang web (đặt trong Cài đặt > Tổng quan)
 * - 'wpurl' - Địa chỉ WordPress (URL) (đặt trong Cài đặt > Tổng quan)
 * - 'url' - Địa chỉ Trang web (URL) (đặt trong Cài đặt > Tổng quan)
 * - 'admin_email' - Email quản trị (đặt trong Cài đặt > Tổng quan)
 * - 'charset' - "Mã hóa cho trang và feed" (đặt trong Cài đặt > Đọc)
 * - 'version' - Phiên bản WordPress hiện tại
 * - 'html_type' - Content-Type (mặc định: "text/html"). Theme và plugin
 *   có thể ghi đè giá trị mặc định bằng bộ lọc {@see 'pre_option_html_type'}
 * - 'text_direction' - Hướng văn bản được xác định bởi ngôn ngữ trang web. Nên sử dụng
 *   is_rtl() thay thế
 * - 'language' - Mã ngôn ngữ cho trang web hiện tại
 * - 'stylesheet_url' - URL đến stylesheet cho theme đang hoạt động. Child theme đang hoạt động
 *   sẽ được ưu tiên hơn giá trị này
 * - 'stylesheet_directory' - Đường dẫn thư mục cho theme đang hoạt động. Child theme đang hoạt động
 *   sẽ được ưu tiên hơn giá trị này
 * - 'template_url' / 'template_directory' - URL thư mục của theme đang hoạt động. Child theme đang hoạt động
 *   sẽ KHÔNG được ưu tiên hơn giá trị này
 * - 'pingback_url' - URL file pingback XML-RPC (xmlrpc.php)
 * - 'atom_url' - URL feed Atom (/feed/atom)
 * - 'rdf_url' - URL feed RDF/RSS 1.0 (/feed/rdf)
 * - 'rss_url' - URL feed RSS 0.92 (/feed/rss)
 * - 'rss2_url' - URL feed RSS 2.0 (/feed)
 * - 'comments_atom_url' - URL feed Atom bình luận (/comments/feed)
 * - 'comments_rss2_url' - URL feed RSS 2.0 bình luận (/comments/feed)
 *
 * Một số giá trị `$show` đã lỗi thời và sẽ bị loại bỏ trong các phiên bản tương lai.
 * Các tùy chọn này sẽ kích hoạt hàm _deprecated_argument().
 *
 * Các đối số đã lỗi thời bao gồm:
 *
 * - 'siteurl' - Sử dụng 'url' thay thế
 * - 'home' - Sử dụng 'url' thay thế
 *
 * @since 0.71
 *
 * @global string $wp_version Chuỗi phiên bản WordPress.
 *
 * @param string $show   Tùy chọn. Thông tin trang web cần lấy. Mặc định rỗng (tên trang web).
 * @param string $filter Tùy chọn. Cách lọc nội dung được lấy. Mặc định 'raw'.
 * @return string Chủ yếu là giá trị chuỗi, có thể rỗng.
 */
function get_bloginfo( $show = '', $filter = 'raw' ) {
	switch ( $show ) {
		case 'home':    // Đã lỗi thời.
		case 'siteurl': // Đã lỗi thời.
			_deprecated_argument(
				__FUNCTION__,
				'2.2.0',
				sprintf(
					/* translators: 1: 'siteurl'/'home' argument, 2: bloginfo() function name, 3: 'url' argument. */
					__( 'The %1$s option is deprecated for the family of %2$s functions. Use the %3$s option instead.' ),
					'<code>' . $show . '</code>',
					'<code>bloginfo()</code>',
					'<code>url</code>'
				)
			);
			// Cố ý fall-through để được xử lý bởi case 'url'.
		case 'url':
			$output = home_url();
			break;
		case 'wpurl':
			$output = site_url();
			break;
		case 'description':
			$output = get_option( 'blogdescription' );
			break;
		case 'rdf_url':
			$output = get_feed_link( 'rdf' );
			break;
		case 'rss_url':
			$output = get_feed_link( 'rss' );
			break;
		case 'rss2_url':
			$output = get_feed_link( 'rss2' );
			break;
		case 'atom_url':
			$output = get_feed_link( 'atom' );
			break;
		case 'comments_atom_url':
			$output = get_feed_link( 'comments_atom' );
			break;
		case 'comments_rss2_url':
			$output = get_feed_link( 'comments_rss2' );
			break;
		case 'pingback_url':
			$output = site_url( 'xmlrpc.php' );
			break;
		case 'stylesheet_url':
			$output = get_stylesheet_uri();
			break;
		case 'stylesheet_directory':
			$output = get_stylesheet_directory_uri();
			break;
		case 'template_directory':
		case 'template_url':
			$output = get_template_directory_uri();
			break;
		case 'admin_email':
			$output = get_option( 'admin_email' );
			break;
		case 'charset':
			$output = get_option( 'blog_charset' );
			if ( '' === $output ) {
				$output = 'UTF-8';
			}
			break;
		case 'html_type':
			$output = get_option( 'html_type' );
			break;
		case 'version':
			global $wp_version;
			$output = $wp_version;
			break;
		case 'language':
			/*
			 * translators: Translate this to the correct language tag for your locale,
			 * see https://www.w3.org/International/articles/language-tags/ for reference.
			 * Do not translate into your own language.
			 */
			$output = __( 'html_lang_attribute' );
			if ( 'html_lang_attribute' === $output || preg_match( '/[^a-zA-Z0-9-]/', $output ) ) {
				$output = determine_locale();
				$output = str_replace( '_', '-', $output );
			}
			break;
		case 'text_direction':
			_deprecated_argument(
				__FUNCTION__,
				'2.2.0',
				sprintf(
					/* translators: 1: 'text_direction' argument, 2: bloginfo() function name, 3: is_rtl() function name. */
					__( 'The %1$s option is deprecated for the family of %2$s functions. Use the %3$s function instead.' ),
					'<code>' . $show . '</code>',
					'<code>bloginfo()</code>',
					'<code>is_rtl()</code>'
				)
			);
			if ( function_exists( 'is_rtl' ) ) {
				$output = is_rtl() ? 'rtl' : 'ltr';
			} else {
				$output = 'ltr';
			}
			break;
		case 'name':
		default:
			$output = get_option( 'blogname' );
			break;
	}

	if ( 'display' === $filter ) {
		if (
			str_contains( $show, 'url' )
			|| str_contains( $show, 'directory' )
			|| str_contains( $show, 'home' )
		) {
			/**
			 * Lọc URL được trả về bởi get_bloginfo().
			 *
			 * @since 2.0.5
			 *
			 * @param string $output URL được trả về bởi bloginfo().
			 * @param string $show   Loại thông tin được yêu cầu.
			 */
			$output = apply_filters( 'bloginfo_url', $output, $show );
		} else {
			/**
			 * Lọc thông tin trang web được trả về bởi get_bloginfo().
			 *
			 * @since 0.71
			 *
			 * @param mixed  $output Thông tin trang web không phải URL được yêu cầu.
			 * @param string $show   Loại thông tin được yêu cầu.
			 */
			$output = apply_filters( 'bloginfo', $output, $show );
		}
	}

	return $output;
}

/**
 * Trả về URL biểu tượng trang web.
 *
 * @since 4.3.0
 *
 * @param int    $size    Tùy chọn. Kích thước biểu tượng trang web. Mặc định 512 (pixel).
 * @param string $url     Tùy chọn. URL dự phòng nếu không tìm thấy biểu tượng trang web. Mặc định rỗng.
 * @param int    $blog_id Tùy chọn. ID của blog để lấy biểu tượng trang web. Mặc định blog hiện tại.
 * @return string URL biểu tượng trang web.
 */
function get_site_icon_url( $size = 512, $url = '', $blog_id = 0 ) {
	$switched_blog = false;

	if ( is_multisite() && ! empty( $blog_id ) && get_current_blog_id() !== (int) $blog_id ) {
		switch_to_blog( $blog_id );
		$switched_blog = true;
	}

	$site_icon_id = (int) get_option( 'site_icon' );

	if ( $site_icon_id ) {
		if ( $size >= 512 ) {
			$size_data = 'full';
		} else {
			$size_data = array( $size, $size );
		}
		$url = wp_get_attachment_image_url( $site_icon_id, $size_data );
	}

	if ( $switched_blog ) {
		restore_current_blog();
	}

	/**
	 * Lọc URL biểu tượng trang web.
	 *
	 * @since 4.4.0
	 *
	 * @param string $url     URL biểu tượng trang web.
	 * @param int    $size    Kích thước biểu tượng trang web.
	 * @param int    $blog_id ID của blog để lấy biểu tượng trang web.
	 */
	return apply_filters( 'get_site_icon_url', $url, $size, $blog_id );
}

/**
 * Hiển thị URL biểu tượng trang web.
 *
 * @since 4.3.0
 *
 * @param int    $size    Tùy chọn. Kích thước biểu tượng trang web. Mặc định 512 (pixel).
 * @param string $url     Tùy chọn. URL dự phòng nếu không tìm thấy biểu tượng trang web. Mặc định rỗng.
 * @param int    $blog_id Tùy chọn. ID của blog để lấy biểu tượng trang web. Mặc định blog hiện tại.
 */
function site_icon_url( $size = 512, $url = '', $blog_id = 0 ) {
	echo esc_url( get_site_icon_url( $size, $url, $blog_id ) );
}

/**
 * Xác định xem trang web có biểu tượng trang web hay không.
 *
 * @since 4.3.0
 *
 * @param int $blog_id Tùy chọn. ID của blog được hỏi. Mặc định blog hiện tại.
 * @return bool Trang web có biểu tượng trang web hay không.
 */
function has_site_icon( $blog_id = 0 ) {
	return (bool) get_site_icon_url( 512, '', $blog_id );
}

/**
 * Xác định xem trang web có logo tùy chỉnh hay không.
 *
 * @since 4.5.0
 *
 * @param int $blog_id Tùy chọn. ID của blog được hỏi. Mặc định là ID của blog hiện tại.
 * @return bool Trang web có logo tùy chỉnh hay không.
 */
function has_custom_logo( $blog_id = 0 ) {
	$switched_blog = false;

	if ( is_multisite() && ! empty( $blog_id ) && get_current_blog_id() !== (int) $blog_id ) {
		switch_to_blog( $blog_id );
		$switched_blog = true;
	}

	$custom_logo_id = get_theme_mod( 'custom_logo' );
	$is_image       = ( $custom_logo_id ) ? wp_attachment_is_image( $custom_logo_id ) : false;

	if ( $switched_blog ) {
		restore_current_blog();
	}

	return $is_image;
}

/**
 * Trả về logo tùy chỉnh, liên kết đến trang chủ trừ khi theme hỗ trợ xóa liên kết trên trang chủ.
 *
 * @since 4.5.0
 * @since 5.5.0 Đã thêm tùy chọn xóa liên kết trên trang chủ với hỗ trợ theme `unlink-homepage-logo`
 *              cho tính năng theme `custom-logo`.
 * @since 5.5.1 Đã tắt lazy-loading theo mặc định.
 *
 * @param int $blog_id Tùy chọn. ID của blog được hỏi. Mặc định là ID của blog hiện tại.
 * @return string Đánh dấu logo tùy chỉnh.
 */
function get_custom_logo( $blog_id = 0 ) {
	$html          = '';
	$switched_blog = false;

	if ( is_multisite() && ! empty( $blog_id ) && get_current_blog_id() !== (int) $blog_id ) {
		switch_to_blog( $blog_id );
		$switched_blog = true;
	}

	// Chúng ta có logo. Sẵn sàng sử dụng.
	if ( has_custom_logo() ) {
		$custom_logo_id   = get_theme_mod( 'custom_logo' );
		$custom_logo_attr = array(
			'class'   => 'custom-logo',
			'loading' => false,
		);

		$unlink_homepage_logo = (bool) get_theme_support( 'custom-logo', 'unlink-homepage-logo' );

		if ( $unlink_homepage_logo && is_front_page() && ! is_paged() ) {
			/*
			 * Nếu ở trang chủ, đặt thuộc tính alt của logo thành chuỗi rỗng,
			 * vì hình ảnh mang tính trang trí và không cần mô tả mục đích của nó.
			 */
			$custom_logo_attr['alt'] = '';
		} else {
			/*
			 * Nếu thuộc tính alt của logo rỗng, lấy tiêu đề trang web và truyền trực tiếp
			 * vào các thuộc tính được sử dụng bởi wp_get_attachment_image().
			 */
			$image_alt = get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true );
			if ( empty( $image_alt ) ) {
				$custom_logo_attr['alt'] = get_bloginfo( 'name', 'display' );
			}
		}

		/**
		 * Lọc danh sách các thuộc tính hình ảnh logo tùy chỉnh.
		 *
		 * @since 5.5.0
		 *
		 * @param array $custom_logo_attr Các thuộc tính hình ảnh logo tùy chỉnh.
		 * @param int   $custom_logo_id   ID đính kèm của logo tùy chỉnh.
		 * @param int   $blog_id          ID của blog cần lấy logo tùy chỉnh.
		 */
		$custom_logo_attr = apply_filters( 'get_custom_logo_image_attributes', $custom_logo_attr, $custom_logo_id, $blog_id );

		/*
		 * Nếu thuộc tính alt không rỗng, không cần truyền nó một cách tường minh
		 * vì wp_get_attachment_image() đã tự động thêm thuộc tính alt.
		 */
		$image = wp_get_attachment_image( $custom_logo_id, 'full', false, $custom_logo_attr );

		// Kiểm tra xem chúng ta có phần tử HTML img hợp lệ hay không.
		if ( $image ) {

			if ( $unlink_homepage_logo && is_front_page() && ! is_paged() ) {
				// Nếu ở trang chủ, không liên kết logo đến trang chủ.
				$html = sprintf(
					'<span class="custom-logo-link">%1$s</span>',
					$image
				);
			} else {
				$aria_current = ! is_paged() && ( is_front_page() || is_home() && ( (int) get_option( 'page_for_posts' ) !== get_queried_object_id() ) ) ? ' aria-current="page"' : '';

				$html = sprintf(
					'<a href="%1$s" class="custom-logo-link" rel="home"%2$s>%3$s</a>',
					esc_url( home_url( '/' ) ),
					$aria_current,
					$image
				);
			}
		}
	} elseif ( is_customize_preview() ) {
		// Nếu chưa đặt logo nhưng đang ở trong Trình tùy chỉnh, để lại placeholder (cần cho xem trước trực tiếp).
		$html = sprintf(
			'<a href="%1$s" class="custom-logo-link" style="display:none;"><img class="custom-logo" alt="" /></a>',
			esc_url( home_url( '/' ) )
		);
	}

	if ( $switched_blog ) {
		restore_current_blog();
	}

	/**
	 * Lọc đầu ra logo tùy chỉnh.
	 *
	 * @since 4.5.0
	 * @since 4.6.0 Đã thêm tham số `$blog_id`.
	 *
	 * @param string $html    Đầu ra HTML của logo tùy chỉnh.
	 * @param int    $blog_id ID của blog cần lấy logo tùy chỉnh.
	 */
	return apply_filters( 'get_custom_logo', $html, $blog_id );
}

/**
 * Hiển thị logo tùy chỉnh, liên kết đến trang chủ trừ khi theme hỗ trợ xóa liên kết trên trang chủ.
 *
 * @since 4.5.0
 *
 * @param int $blog_id Tùy chọn. ID của blog được hỏi. Mặc định là ID của blog hiện tại.
 */
function the_custom_logo( $blog_id = 0 ) {
	echo get_custom_logo( $blog_id );
}

/**
 * Trả về tiêu đề tài liệu cho trang hiện tại.
 *
 * @since 4.4.0
 *
 * @global int $page  Số trang của một bài viết đơn.
 * @global int $paged Số trang của danh sách bài viết.
 *
 * @return string Thẻ chứa tiêu đề tài liệu.
 */
function wp_get_document_title() {

	/**
	 * Lọc tiêu đề tài liệu trước khi được tạo.
	 *
	 * Truyền giá trị không rỗng sẽ bỏ qua wp_get_document_title(),
	 * trả về giá trị đó thay thế.
	 *
	 * @since 4.4.0
	 *
	 * @param string $title Tiêu đề tài liệu. Mặc định chuỗi rỗng.
	 */
	$title = apply_filters( 'pre_get_document_title', '' );
	if ( ! empty( $title ) ) {
		return $title;
	}

	global $page, $paged;

	$title = array(
		'title' => '',
	);

	// Nếu là trang 404, sử dụng tiêu đề "Không tìm thấy trang".
	if ( is_404() ) {
		$title['title'] = __( 'Page not found' );

		// Nếu là tìm kiếm, sử dụng tiêu đề kết quả tìm kiếm động.
	} elseif ( is_search() ) {
		/* translators: %s: Search query. */
		$title['title'] = sprintf( __( 'Search Results for &#8220;%s&#8221;' ), get_search_query() );

		// Nếu ở trang chủ, sử dụng tiêu đề trang web.
	} elseif ( is_front_page() ) {
		$title['title'] = get_bloginfo( 'name', 'display' );

		// Nếu ở trang lưu trữ loại bài viết, sử dụng tiêu đề lưu trữ loại bài viết.
	} elseif ( is_post_type_archive() ) {
		$title['title'] = post_type_archive_title( '', false );

		// Nếu ở trang lưu trữ taxonomy, sử dụng tiêu đề term.
	} elseif ( is_tax() ) {
		$title['title'] = single_term_title( '', false );

		/*
		* Nếu chúng ta ở trang blog mà không phải trang chủ
		* hoặc một bài viết đơn của bất kỳ loại bài viết nào, sử dụng tiêu đề bài viết.
		*/
	} elseif ( is_home() || is_singular() ) {
		$title['title'] = single_post_title( '', false );

		// Nếu ở trang lưu trữ chuyên mục hoặc thẻ, sử dụng tiêu đề term.
	} elseif ( is_category() || is_tag() ) {
		$title['title'] = single_term_title( '', false );

		// Nếu ở trang lưu trữ tác giả, sử dụng tên hiển thị của tác giả.
	} elseif ( is_author() && get_queried_object() ) {
		$author         = get_queried_object();
		$title['title'] = $author->display_name;

		// Nếu là trang lưu trữ theo ngày, sử dụng ngày làm tiêu đề.
	} elseif ( is_year() ) {
		$title['title'] = get_the_date( _x( 'Y', 'yearly archives date format' ) );

	} elseif ( is_month() ) {
		$title['title'] = get_the_date( _x( 'F Y', 'monthly archives date format' ) );

	} elseif ( is_day() ) {
		$title['title'] = get_the_date();
	}

	// Thêm số trang nếu cần thiết.
	if ( ( $paged >= 2 || $page >= 2 ) && ! is_404() ) {
		/* translators: %s: Page number. */
		$title['page'] = sprintf( __( 'Page %s' ), max( $paged, $page ) );
	}

	// Thêm mô tả hoặc tiêu đề trang web để cung cấp ngữ cảnh.
	if ( is_front_page() ) {
		$title['tagline'] = get_bloginfo( 'description', 'display' );
	} else {
		$title['site'] = get_bloginfo( 'name', 'display' );
	}

	/**
	 * Lọc ký tự phân cách cho tiêu đề tài liệu.
	 *
	 * @since 4.4.0
	 *
	 * @param string $sep Ký tự phân cách tiêu đề tài liệu. Mặc định '-'.
	 */
	$sep = apply_filters( 'document_title_separator', '-' );

	/**
	 * Lọc các phần của tiêu đề tài liệu.
	 *
	 * @since 4.4.0
	 *
	 * @param array $title {
	 *     Các phần của tiêu đề tài liệu.
	 *
	 *     @type string $title   Tiêu đề của trang đang xem.
	 *     @type string $page    Tùy chọn. Số trang nếu có phân trang.
	 *     @type string $tagline Tùy chọn. Mô tả trang web khi ở trang chủ.
	 *     @type string $site    Tùy chọn. Tiêu đề trang web khi không ở trang chủ.
	 * }
	 */
	$title = apply_filters( 'document_title_parts', $title );

	$title = implode( " $sep ", array_filter( $title ) );

	/**
	 * Lọc tiêu đề tài liệu.
	 *
	 * @since 5.8.0
	 *
	 * @param string $title Tiêu đề tài liệu.
	 */
	$title = apply_filters( 'document_title', $title );

	return $title;
}

/**
 * Hiển thị thẻ title với nội dung.
 *
 * @since 4.1.0
 * @since 4.4.0 Đầu ra tiêu đề cải tiến thay thế `wp_title()`.
 * @access private
 */
function _wp_render_title_tag() {
	if ( ! current_theme_supports( 'title-tag' ) ) {
		return;
	}

	echo '<title>' . wp_get_document_title() . '</title>' . "\n";
}

/**
 * Hiển thị hoặc lấy tiêu đề trang cho tất cả các khu vực của blog.
 *
 * Theo mặc định, tiêu đề trang sẽ hiển thị ký tự phân cách trước tiêu đề trang,
 * để tiêu đề blog sẽ nằm trước tiêu đề trang. Điều này không tốt cho
 * việc hiển thị tiêu đề, vì tiêu đề blog hiển thị trên hầu hết các tab chứ không phải
 * nội dung quan trọng, đó là trang mà người dùng đang xem.
 *
 * Ngoài ra còn có lợi ích SEO khi đặt tiêu đề blog sau hoặc ở bên 'phải'
 * của tiêu đề trang. Tuy nhiên, chủ yếu là hợp lý khi đặt tiêu đề blog
 * ở bên phải với hầu hết trình duyệt hỗ trợ tab. Bạn có thể đạt được điều này bằng
 * cách sử dụng tham số seplocation và đặt giá trị thành 'right'. Thay đổi này
 * được giới thiệu từ khoảng 2.5.0, trong trường hợp tương thích ngược của theme
 * là quan trọng.
 *
 * @since 1.0.0
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ WordPress.
 *
 * @param string $sep         Tùy chọn. Cách phân cách các mục khác nhau trong tiêu đề trang.
 *                            Mặc định '&raquo;'.
 * @param bool   $display     Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @param string $seplocation Tùy chọn. Vị trí của ký tự phân cách ('left' hoặc 'right').
 * @return string|void Chuỗi khi `$display` là false, không có gì nếu ngược lại.
 */
function wp_title( $sep = '&raquo;', $display = true, $seplocation = '' ) {
	global $wp_locale;

	$m        = get_query_var( 'm' );
	$year     = get_query_var( 'year' );
	$monthnum = get_query_var( 'monthnum' );
	$day      = get_query_var( 'day' );
	$search   = get_query_var( 's' );
	$title    = '';

	$t_sep = '%WP_TITLE_SEP%'; // Ký tự phân cách tạm thời, để đảo ngược chính xác, nếu cần.

	// Nếu có bài viết.
	if ( is_single() || ( is_home() && ! is_front_page() ) || ( is_page() && ! is_front_page() ) ) {
		$title = single_post_title( '', false );
	}

	// Nếu có lưu trữ loại bài viết.
	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object->has_archive ) {
			$title = post_type_archive_title( '', false );
		}
	}

	// Nếu có chuyên mục hoặc thẻ.
	if ( is_category() || is_tag() ) {
		$title = single_term_title( '', false );
	}

	// Nếu có taxonomy.
	if ( is_tax() ) {
		$term = get_queried_object();
		if ( $term ) {
			$tax   = get_taxonomy( $term->taxonomy );
			$title = single_term_title( $tax->labels->name . $t_sep, false );
		}
	}

	// Nếu có tác giả.
	if ( is_author() && ! is_post_type_archive() ) {
		$author = get_queried_object();
		if ( $author ) {
			$title = $author->display_name;
		}
	}

	// Lưu trữ loại bài viết có has_archive nên ghi đè terms.
	if ( is_post_type_archive() && $post_type_object->has_archive ) {
		$title = post_type_archive_title( '', false );
	}

	// Nếu có tháng.
	if ( is_archive() && ! empty( $m ) ) {
		$my_year  = substr( $m, 0, 4 );
		$my_month = substr( $m, 4, 2 );
		$my_day   = (int) substr( $m, 6, 2 );
		$title    = $my_year .
			( $my_month ? $t_sep . $wp_locale->get_month( $my_month ) : '' ) .
			( $my_day ? $t_sep . $my_day : '' );
	}

	// Nếu có năm.
	if ( is_archive() && ! empty( $year ) ) {
		$title = $year;
		if ( ! empty( $monthnum ) ) {
			$title .= $t_sep . $wp_locale->get_month( $monthnum );
		}
		if ( ! empty( $day ) ) {
			$title .= $t_sep . zeroise( $day, 2 );
		}
	}

	// Nếu là tìm kiếm.
	if ( is_search() ) {
		/* translators: 1: Separator, 2: Search query. */
		$title = sprintf( __( 'Search Results %1$s %2$s' ), $t_sep, strip_tags( $search ) );
	}

	// Nếu là trang 404.
	if ( is_404() ) {
		$title = __( 'Page not found' );
	}

	$prefix = '';
	if ( ! empty( $title ) ) {
		$prefix = " $sep ";
	}

	/**
	 * Lọc các phần của tiêu đề trang.
	 *
	 * @since 4.0.0
	 *
	 * @param string[] $title_array Mảng các phần của tiêu đề trang.
	 */
	$title_array = apply_filters( 'wp_title_parts', explode( $t_sep, $title ) );

	// Xác định vị trí ký tự phân cách và hướng của breadcrumb.
	if ( 'right' === $seplocation ) { // Ký tự phân cách bên phải, nên đảo ngược thứ tự.
		$title_array = array_reverse( $title_array );
		$title       = implode( " $sep ", $title_array ) . $prefix;
	} else {
		$title = $prefix . implode( " $sep ", $title_array );
	}

	/**
	 * Lọc văn bản tiêu đề trang.
	 *
	 * @since 2.0.0
	 *
	 * @param string $title       Tiêu đề trang.
	 * @param string $sep         Ký tự phân cách tiêu đề.
	 * @param string $seplocation Vị trí của ký tự phân cách ('left' hoặc 'right').
	 */
	$title = apply_filters( 'wp_title', $title, $sep, $seplocation );

	// Xuất kết quả.
	if ( $display ) {
		echo $title;
	} else {
		return $title;
	}
}

/**
 * Hiển thị hoặc lấy tiêu đề trang cho bài viết.
 *
 * Hàm này được tối ưu cho file template single.php để hiển thị tiêu đề bài viết.
 *
 * Không hỗ trợ đặt ký tự phân cách sau tiêu đề, nhưng bằng cách để trống
 * tham số prefix, bạn có thể đặt ký tự phân cách tiêu đề thủ công. Prefix
 * không tự động đặt khoảng trắng giữa prefix và tiêu đề, nên nếu cần
 * khoảng trắng, giá trị tham số cần có khoảng trắng ở cuối.
 *
 * @since 0.71
 *
 * @param string $prefix  Tùy chọn. Nội dung hiển thị trước tiêu đề.
 * @param bool   $display Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @return string|void Tiêu đề khi lấy.
 */
function single_post_title( $prefix = '', $display = true ) {
	$_post = get_queried_object();

	if ( ! isset( $_post->post_title ) ) {
		return;
	}

	/**
	 * Lọc tiêu đề trang cho bài viết đơn.
	 *
	 * @since 0.71
	 *
	 * @param string  $_post_title Tiêu đề trang bài viết đơn.
	 * @param WP_Post $_post       Bài viết hiện tại.
	 */
	$title = apply_filters( 'single_post_title', $_post->post_title, $_post );
	if ( $display ) {
		echo $prefix . $title;
	} else {
		return $prefix . $title;
	}
}

/**
 * Hiển thị hoặc lấy tiêu đề cho lưu trữ loại bài viết.
 *
 * Hàm này được tối ưu cho file template archive.php và archive-{$post_type}.php
 * để hiển thị tiêu đề của loại bài viết.
 *
 * @since 3.1.0
 *
 * @param string $prefix  Tùy chọn. Nội dung hiển thị trước tiêu đề.
 * @param bool   $display Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @return string|void Tiêu đề khi lấy, null khi hiển thị hoặc thất bại.
 */
function post_type_archive_title( $prefix = '', $display = true ) {
	if ( ! is_post_type_archive() ) {
		return;
	}

	$post_type = get_query_var( 'post_type' );
	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}

	$post_type_obj = get_post_type_object( $post_type );

	/**
	 * Lọc tiêu đề lưu trữ loại bài viết.
	 *
	 * @since 3.1.0
	 *
	 * @param string $post_type_name Nhãn 'name' của loại bài viết.
	 * @param string $post_type      Loại bài viết.
	 */
	$title = apply_filters( 'post_type_archive_title', $post_type_obj->labels->name, $post_type );

	if ( $display ) {
		echo $prefix . $title;
	} else {
		return $prefix . $title;
	}
}

/**
 * Hiển thị hoặc lấy tiêu đề trang cho lưu trữ chuyên mục.
 *
 * Hữu ích cho file template chuyên mục để hiển thị tiêu đề trang chuyên mục.
 * Prefix không tự động đặt khoảng trắng giữa prefix và tiêu đề, nên nếu
 * cần khoảng trắng, giá trị tham số cần có khoảng trắng ở cuối.
 *
 * @since 0.71
 *
 * @param string $prefix  Tùy chọn. Nội dung hiển thị trước tiêu đề.
 * @param bool   $display Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @return string|void Tiêu đề khi lấy.
 */
function single_cat_title( $prefix = '', $display = true ) {
	return single_term_title( $prefix, $display );
}

/**
 * Hiển thị hoặc lấy tiêu đề trang cho lưu trữ thẻ bài viết.
 *
 * Hữu ích cho file template thẻ để hiển thị tiêu đề trang thẻ. Prefix
 * không tự động đặt khoảng trắng giữa prefix và tiêu đề, nên nếu cần
 * khoảng trắng, giá trị tham số cần có khoảng trắng ở cuối.
 *
 * @since 2.3.0
 *
 * @param string $prefix  Tùy chọn. Nội dung hiển thị trước tiêu đề.
 * @param bool   $display Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @return string|void Tiêu đề khi lấy.
 */
function single_tag_title( $prefix = '', $display = true ) {
	return single_term_title( $prefix, $display );
}

/**
 * Hiển thị hoặc lấy tiêu đề trang cho lưu trữ term taxonomy.
 *
 * Hữu ích cho file template term taxonomy để hiển thị tiêu đề trang term taxonomy.
 * Prefix không tự động đặt khoảng trắng giữa prefix và tiêu đề, nên nếu cần
 * khoảng trắng, giá trị tham số cần có khoảng trắng ở cuối.
 *
 * @since 3.1.0
 *
 * @param string $prefix  Tùy chọn. Nội dung hiển thị trước tiêu đề.
 * @param bool   $display Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @return string|void Tiêu đề khi lấy.
 */
function single_term_title( $prefix = '', $display = true ) {
	$term = get_queried_object();

	if ( ! $term ) {
		return;
	}

	if ( is_category() ) {
		/**
		 * Lọc tiêu đề trang lưu trữ chuyên mục.
		 *
		 * @since 2.0.10
		 *
		 * @param string $term_name Tên chuyên mục cho lưu trữ đang hiển thị.
		 */
		$term_name = apply_filters( 'single_cat_title', $term->name );
	} elseif ( is_tag() ) {
		/**
		 * Lọc tiêu đề trang lưu trữ thẻ.
		 *
		 * @since 2.3.0
		 *
		 * @param string $term_name Tên thẻ cho lưu trữ đang hiển thị.
		 */
		$term_name = apply_filters( 'single_tag_title', $term->name );
	} elseif ( is_tax() ) {
		/**
		 * Lọc tiêu đề trang lưu trữ taxonomy tùy chỉnh.
		 *
		 * @since 3.1.0
		 *
		 * @param string $term_name Tên term cho lưu trữ đang hiển thị.
		 */
		$term_name = apply_filters( 'single_term_title', $term->name );
	} else {
		return;
	}

	if ( empty( $term_name ) ) {
		return;
	}

	if ( $display ) {
		echo $prefix . $term_name;
	} else {
		return $prefix . $term_name;
	}
}

/**
 * Hiển thị hoặc lấy tiêu đề trang cho lưu trữ bài viết theo ngày.
 *
 * Hữu ích khi template chỉ cần hiển thị tháng và năm,
 * nếu có sẵn. Prefix không tự động đặt khoảng trắng
 * giữa prefix và tiêu đề, nên nếu cần khoảng trắng, giá trị tham số
 * cần có khoảng trắng ở cuối.
 *
 * @since 0.71
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ WordPress.
 *
 * @param string $prefix  Tùy chọn. Nội dung hiển thị trước tiêu đề.
 * @param bool   $display Tùy chọn. Có hiển thị hay lấy tiêu đề. Mặc định true.
 * @return string|false|void False nếu không có tiêu đề hợp lệ cho tháng. Tiêu đề khi lấy.
 */
function single_month_title( $prefix = '', $display = true ) {
	global $wp_locale;

	$m        = get_query_var( 'm' );
	$year     = get_query_var( 'year' );
	$monthnum = get_query_var( 'monthnum' );

	if ( ! empty( $monthnum ) && ! empty( $year ) ) {
		$my_year  = $year;
		$my_month = $wp_locale->get_month( $monthnum );
	} elseif ( ! empty( $m ) ) {
		$my_year  = substr( $m, 0, 4 );
		$my_month = $wp_locale->get_month( substr( $m, 4, 2 ) );
	}

	if ( empty( $my_month ) ) {
		return false;
	}

	$result = $prefix . $my_month . $prefix . $my_year;

	if ( ! $display ) {
		return $result;
	}
	echo $result;
}

/**
 * Hiển thị tiêu đề lưu trữ dựa trên đối tượng được truy vấn.
 *
 * @since 4.1.0
 *
 * @see get_the_archive_title()
 *
 * @param string $before Tùy chọn. Nội dung thêm trước tiêu đề. Mặc định rỗng.
 * @param string $after  Tùy chọn. Nội dung thêm sau tiêu đề. Mặc định rỗng.
 */
function the_archive_title( $before = '', $after = '' ) {
	$title = get_the_archive_title();

	if ( ! empty( $title ) ) {
		echo $before . $title . $after;
	}
}

/**
 * Lấy tiêu đề lưu trữ dựa trên đối tượng được truy vấn.
 *
 * @since 4.1.0
 * @since 5.5.0 Phần tiêu đề được bao bọc trong phần tử `<span>`.
 *
 * @return string Tiêu đề lưu trữ.
 */
function get_the_archive_title() {
	$title  = __( 'Archives' );
	$prefix = '';

	if ( is_category() ) {
		$title  = single_cat_title( '', false );
		$prefix = _x( 'Category:', 'category archive title prefix' );
	} elseif ( is_tag() ) {
		$title  = single_tag_title( '', false );
		$prefix = _x( 'Tag:', 'tag archive title prefix' );
	} elseif ( is_author() ) {
		$title  = get_the_author();
		$prefix = _x( 'Author:', 'author archive title prefix' );
	} elseif ( is_year() ) {
		/* translators: See https://www.php.net/manual/datetime.format.php */
		$title  = get_the_date( _x( 'Y', 'yearly archives date format' ) );
		$prefix = _x( 'Year:', 'date archive title prefix' );
	} elseif ( is_month() ) {
		/* translators: See https://www.php.net/manual/datetime.format.php */
		$title  = get_the_date( _x( 'F Y', 'monthly archives date format' ) );
		$prefix = _x( 'Month:', 'date archive title prefix' );
	} elseif ( is_day() ) {
		/* translators: See https://www.php.net/manual/datetime.format.php */
		$title  = get_the_date( _x( 'F j, Y', 'daily archives date format' ) );
		$prefix = _x( 'Day:', 'date archive title prefix' );
	} elseif ( is_tax( 'post_format' ) ) {
		if ( is_tax( 'post_format', 'post-format-aside' ) ) {
			$title = _x( 'Asides', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
			$title = _x( 'Galleries', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
			$title = _x( 'Images', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
			$title = _x( 'Videos', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
			$title = _x( 'Quotes', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
			$title = _x( 'Links', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
			$title = _x( 'Statuses', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
			$title = _x( 'Audio', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
			$title = _x( 'Chats', 'post format archive title' );
		}
	} elseif ( is_post_type_archive() ) {
		$title  = post_type_archive_title( '', false );
		$prefix = _x( 'Archives:', 'post type archive title prefix' );
	} elseif ( is_tax() ) {
		$queried_object = get_queried_object();
		if ( $queried_object ) {
			$tax    = get_taxonomy( $queried_object->taxonomy );
			$title  = single_term_title( '', false );
			$prefix = sprintf(
				/* translators: %s: Taxonomy singular name. */
				_x( '%s:', 'taxonomy term archive title prefix' ),
				$tax->labels->singular_name
			);
		}
	}

	$original_title = $title;

	/**
	 * Lọc tiền tố tiêu đề lưu trữ.
	 *
	 * @since 5.5.0
	 *
	 * @param string $prefix Tiền tố tiêu đề lưu trữ.
	 */
	$prefix = apply_filters( 'get_the_archive_title_prefix', $prefix );
	if ( $prefix ) {
		$title = sprintf(
			/* translators: 1: Title prefix. 2: Title. */
			_x( '%1$s %2$s', 'archive title' ),
			$prefix,
			'<span>' . $title . '</span>'
		);
	}

	/**
	 * Lọc tiêu đề lưu trữ.
	 *
	 * @since 4.1.0
	 * @since 5.5.0 Đã thêm tham số `$prefix` và `$original_title`.
	 *
	 * @param string $title          Tiêu đề lưu trữ sẽ được hiển thị.
	 * @param string $original_title Tiêu đề lưu trữ không có tiền tố.
	 * @param string $prefix         Tiền tố tiêu đề lưu trữ.
	 */
	return apply_filters( 'get_the_archive_title', $title, $original_title, $prefix );
}

/**
 * Hiển thị mô tả chuyên mục, thẻ, term hoặc tác giả.
 *
 * @since 4.1.0
 *
 * @see get_the_archive_description()
 *
 * @param string $before Tùy chọn. Nội dung thêm trước mô tả. Mặc định rỗng.
 * @param string $after  Tùy chọn. Nội dung thêm sau mô tả. Mặc định rỗng.
 */
function the_archive_description( $before = '', $after = '' ) {
	$description = get_the_archive_description();
	if ( $description ) {
		echo $before . $description . $after;
	}
}

/**
 * Lấy mô tả cho lưu trữ tác giả, loại bài viết hoặc term.
 *
 * @since 4.1.0
 * @since 4.7.0 Đã thêm hỗ trợ cho lưu trữ tác giả.
 * @since 4.9.0 Đã thêm hỗ trợ cho lưu trữ loại bài viết.
 *
 * @see term_description()
 *
 * @return string Mô tả lưu trữ.
 */
function get_the_archive_description() {
	if ( is_author() ) {
		$description = get_the_author_meta( 'description' );
	} elseif ( is_post_type_archive() ) {
		$description = get_the_post_type_description();
	} else {
		$description = term_description();
	}

	/**
	 * Lọc mô tả lưu trữ.
	 *
	 * @since 4.1.0
	 *
	 * @param string $description Mô tả lưu trữ sẽ được hiển thị.
	 */
	return apply_filters( 'get_the_archive_description', $description );
}

/**
 * Lấy mô tả cho lưu trữ loại bài viết.
 *
 * @since 4.9.0
 *
 * @return string Mô tả loại bài viết.
 */
function get_the_post_type_description() {
	$post_type = get_query_var( 'post_type' );

	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}

	$post_type_obj = get_post_type_object( $post_type );

	// Kiểm tra xem mô tả có được đặt hay không.
	if ( isset( $post_type_obj->description ) ) {
		$description = $post_type_obj->description;
	} else {
		$description = '';
	}

	/**
	 * Lọc mô tả cho lưu trữ loại bài viết.
	 *
	 * @since 4.9.0
	 *
	 * @param string       $description   Mô tả loại bài viết.
	 * @param WP_Post_Type $post_type_obj Đối tượng loại bài viết.
	 */
	return apply_filters( 'get_the_post_type_description', $description, $post_type_obj );
}

/**
 * Lấy nội dung liên kết lưu trữ dựa trên mã được định nghĩa trước hoặc tùy chỉnh.
 *
 * Định dạng có thể là một trong bốn kiểu. 'link' cho phần tử head, 'option'
 * để sử dụng trong phần tử select, 'html' để sử dụng trong danh sách (phần tử HTML ol hoặc ul).
 * Nội dung tùy chỉnh cũng được hỗ trợ sử dụng các tham số before và after.
 *
 * Định dạng 'link' sử dụng phần tử HTML `<link>` với quan hệ **archives**.
 * Các tham số before và after không được sử dụng. Tham số text
 * được sử dụng để mô tả liên kết.
 *
 * Định dạng 'option' sử dụng phần tử HTML option để sử dụng trong phần tử select.
 * Giá trị là tham số url và các tham số before và after được sử dụng
 * giữa mô tả văn bản.
 *
 * Định dạng 'html', là mặc định, sử dụng phần tử HTML li để sử dụng trong
 * các phần tử HTML danh sách. Tham số before nằm trước liên kết và tham số after
 * nằm sau thẻ đóng liên kết.
 *
 * Định dạng tùy chỉnh sử dụng tham số before trước liên kết (phần tử HTML 'a')
 * và tham số after sau thẻ đóng liên kết. Nếu ba giá trị trên cho định dạng
 * không được sử dụng, thì định dạng tùy chỉnh sẽ được áp dụng.
 *
 * @since 1.0.0
 * @since 5.2.0 Đã thêm tham số `$selected`.
 *
 * @param string $url      URL đến lưu trữ.
 * @param string $text     Mô tả văn bản lưu trữ.
 * @param string $format   Tùy chọn. Có thể là 'link', 'option', 'html', hoặc tùy chỉnh. Mặc định 'html'.
 * @param string $before   Tùy chọn. Nội dung thêm trước mô tả. Mặc định rỗng.
 * @param string $after    Tùy chọn. Nội dung thêm sau mô tả. Mặc định rỗng.
 * @param bool   $selected Tùy chọn. Đặt true nếu trang hiện tại là trang lưu trữ được chọn. Mặc định false.
 * @return string Nội dung liên kết HTML cho lưu trữ.
 */
function get_archives_link( $url, $text, $format = 'html', $before = '', $after = '', $selected = false ) {
	$text         = wptexturize( $text );
	$url          = esc_url( $url );
	$aria_current = $selected ? ' aria-current="page"' : '';

	if ( 'link' === $format ) {
		$link_html = "\t<link rel='archives' title='" . esc_attr( $text ) . "' href='$url' />\n";
	} elseif ( 'option' === $format ) {
		$selected_attr = $selected ? " selected='selected'" : '';
		$link_html     = "\t<option value='$url'$selected_attr>$before $text $after</option>\n";
	} elseif ( 'html' === $format ) {
		$link_html = "\t<li>$before<a href='$url'$aria_current>$text</a>$after</li>\n";
	} else { // Tùy chỉnh.
		$link_html = "\t$before<a href='$url'$aria_current>$text</a>$after\n";
	}

	/**
	 * Lọc nội dung liên kết lưu trữ.
	 *
	 * @since 2.6.0
	 * @since 4.5.0 Đã thêm các tham số `$url`, `$text`, `$format`, `$before`, và `$after`.
	 * @since 5.2.0 Đã thêm tham số `$selected`.
	 *
	 * @param string $link_html Nội dung HTML liên kết lưu trữ.
	 * @param string $url       URL đến lưu trữ.
	 * @param string $text      Mô tả văn bản lưu trữ.
	 * @param string $format    Định dạng liên kết. Có thể là 'link', 'option', 'html', hoặc tùy chỉnh.
	 * @param string $before    Nội dung thêm trước mô tả.
	 * @param string $after     Nội dung thêm sau mô tả.
	 * @param bool   $selected  True nếu trang hiện tại là lưu trữ được chọn.
	 */
	return apply_filters( 'get_archives_link', $link_html, $url, $text, $format, $before, $after, $selected );
}

/**
 * Hiển thị các liên kết lưu trữ dựa trên loại và định dạng.
 *
 * @since 1.2.0
 * @since 4.4.0 Đã thêm đối số `$post_type`.
 * @since 5.2.0 Đã thêm các đối số `$year`, `$monthnum`, `$day`, và `$w`.
 *
 * @see get_archives_link()
 *
 * @global wpdb      $wpdb      Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ WordPress.
 *
 * @param string|array $args {
 *     Các đối số liên kết lưu trữ mặc định. Tùy chọn.
 *
 *     @type string     $type            Loại lưu trữ cần lấy. Chấp nhận 'daily', 'weekly', 'monthly',
 *                                       'yearly', 'postbypost', hoặc 'alpha'. Cả 'postbypost' và 'alpha'
 *                                       hiển thị cùng danh sách liên kết lưu trữ cũng như tiêu đề bài viết thay
 *                                       vì hiển thị ngày tháng. Sự khác biệt giữa hai loại là 'alpha'
 *                                       sẽ sắp xếp theo tiêu đề bài viết và 'postbypost' sẽ sắp xếp theo ngày đăng.
 *                                       Mặc định 'monthly'.
 *     @type string|int $limit           Số liên kết để giới hạn truy vấn. Mặc định rỗng (không giới hạn).
 *     @type string     $format          Định dạng mỗi liên kết sử dụng các đối số $before và $after.
 *                                       Chấp nhận 'link' (thẻ `<link>`), 'option' (thẻ `<option>`), 'html'
 *                                       (thẻ `<li>`), hoặc định dạng tùy chỉnh, tạo liên kết anchor
 *                                       với $before đứng trước và $after đứng sau. Mặc định 'html'.
 *     @type string     $before          Đánh dấu thêm vào đầu mỗi liên kết. Mặc định rỗng.
 *     @type string     $after           Đánh dấu thêm vào cuối mỗi liên kết. Mặc định rỗng.
 *     @type bool       $show_post_count Có hiển thị số bài viết bên cạnh liên kết hay không. Mặc định false.
 *     @type bool|int   $echo            Có echo hay trả về danh sách liên kết. Mặc định 1|true để echo.
 *     @type string     $order           Sử dụng thứ tự tăng dần hay giảm dần. Chấp nhận 'ASC', hoặc 'DESC'.
 *                                       Mặc định 'DESC'.
 *     @type string     $post_type       Loại bài viết. Mặc định 'post'.
 *     @type string     $year            Năm. Mặc định năm hiện tại.
 *     @type string     $monthnum        Số tháng. Mặc định số tháng hiện tại.
 *     @type string     $day             Ngày. Mặc định ngày hiện tại.
 *     @type string     $w               Tuần. Mặc định tuần hiện tại.
 * }
 * @return void|string Void nếu đối số 'echo' là true, các liên kết lưu trữ nếu 'echo' là false.
 */
function wp_get_archives( $args = '' ) {
	global $wpdb, $wp_locale;

	$defaults = array(
		'type'            => 'monthly',
		'limit'           => '',
		'format'          => 'html',
		'before'          => '',
		'after'           => '',
		'show_post_count' => false,
		'echo'            => 1,
		'order'           => 'DESC',
		'post_type'       => 'post',
		'year'            => get_query_var( 'year' ),
		'monthnum'        => get_query_var( 'monthnum' ),
		'day'             => get_query_var( 'day' ),
		'w'               => get_query_var( 'w' ),
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	$post_type_object = get_post_type_object( $parsed_args['post_type'] );
	if ( ! is_post_type_viewable( $post_type_object ) ) {
		return;
	}

	$parsed_args['post_type'] = $post_type_object->name;

	if ( '' === $parsed_args['type'] ) {
		$parsed_args['type'] = 'monthly';
	}

	if ( ! empty( $parsed_args['limit'] ) ) {
		$parsed_args['limit'] = absint( $parsed_args['limit'] );
		$parsed_args['limit'] = ' LIMIT ' . $parsed_args['limit'];
	}

	$order = strtoupper( $parsed_args['order'] );
	if ( 'ASC' !== $order ) {
		$order = 'DESC';
	}

	// Đây là ký tự phân cách ngày trên các liên kết lưu trữ hàng tuần.
	$archive_week_separator = '&#8211;';

	$sql_where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish'", $parsed_args['post_type'] );

	/**
	 * Lọc mệnh đề SQL WHERE để lấy lưu trữ.
	 *
	 * @since 2.2.0
	 *
	 * @param string $sql_where   Phần truy vấn SQL chứa mệnh đề WHERE.
	 * @param array  $parsed_args Mảng các đối số mặc định.
	 */
	$where = apply_filters( 'getarchives_where', $sql_where, $parsed_args );

	/**
	 * Lọc mệnh đề SQL JOIN để lấy lưu trữ.
	 *
	 * @since 2.2.0
	 *
	 * @param string $sql_join    Phần truy vấn SQL chứa mệnh đề JOIN.
	 * @param array  $parsed_args Mảng các đối số mặc định.
	 */
	$join = apply_filters( 'getarchives_join', '', $parsed_args );

	$output = '';

	$last_changed = wp_cache_get_last_changed( 'posts' );

	$limit = $parsed_args['limit'];

	if ( 'monthly' === $parsed_args['type'] ) {
		$query   = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'post-queries' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'post-queries' );
		}
		if ( $results ) {
			$after = $parsed_args['after'];
			foreach ( (array) $results as $result ) {
				$url = get_month_link( $result->year, $result->month );
				if ( 'post' !== $parsed_args['post_type'] ) {
					$url = add_query_arg( 'post_type', $parsed_args['post_type'], $url );
				}
				/* translators: 1: Month name, 2: 4-digit year. */
				$text = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $result->month ), $result->year );
				if ( $parsed_args['show_post_count'] ) {
					$parsed_args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$selected = is_archive() && (string) $parsed_args['year'] === $result->year && (string) $parsed_args['monthnum'] === $result->month;
				$output  .= get_archives_link( $url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected );
			}
		}
	} elseif ( 'yearly' === $parsed_args['type'] ) {
		$query   = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'post-queries' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'post-queries' );
		}
		if ( $results ) {
			$after = $parsed_args['after'];
			foreach ( (array) $results as $result ) {
				$url = get_year_link( $result->year );
				if ( 'post' !== $parsed_args['post_type'] ) {
					$url = add_query_arg( 'post_type', $parsed_args['post_type'], $url );
				}
				$text = sprintf( '%d', $result->year );
				if ( $parsed_args['show_post_count'] ) {
					$parsed_args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$selected = is_archive() && (string) $parsed_args['year'] === $result->year;
				$output  .= get_archives_link( $url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected );
			}
		}
	} elseif ( 'daily' === $parsed_args['type'] ) {
		$query   = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'post-queries' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'post-queries' );
		}
		if ( $results ) {
			$after = $parsed_args['after'];
			foreach ( (array) $results as $result ) {
				$url = get_day_link( $result->year, $result->month, $result->dayofmonth );
				if ( 'post' !== $parsed_args['post_type'] ) {
					$url = add_query_arg( 'post_type', $parsed_args['post_type'], $url );
				}
				$date = sprintf( '%1$d-%2$02d-%3$02d 00:00:00', $result->year, $result->month, $result->dayofmonth );
				$text = mysql2date( get_option( 'date_format' ), $date );
				if ( $parsed_args['show_post_count'] ) {
					$parsed_args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$selected = is_archive() && (string) $parsed_args['year'] === $result->year && (string) $parsed_args['monthnum'] === $result->month && (string) $parsed_args['day'] === $result->dayofmonth;
				$output  .= get_archives_link( $url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected );
			}
		}
	} elseif ( 'weekly' === $parsed_args['type'] ) {
		$week    = _wp_mysql_week( '`post_date`' );
		$query   = "SELECT DISTINCT $week AS `week`, YEAR( `post_date` ) AS `yr`, DATE_FORMAT( `post_date`, '%Y-%m-%d' ) AS `yyyymmdd`, count( `ID` ) AS `posts` FROM `$wpdb->posts` $join $where GROUP BY $week, YEAR( `post_date` ) ORDER BY `post_date` $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'post-queries' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'post-queries' );
		}
		$arc_w_last = '';
		if ( $results ) {
			$after = $parsed_args['after'];
			foreach ( (array) $results as $result ) {
				if ( $result->week !== $arc_w_last ) {
					$arc_year       = $result->yr;
					$arc_w_last     = $result->week;
					$arc_week       = get_weekstartend( $result->yyyymmdd, get_option( 'start_of_week' ) );
					$arc_week_start = date_i18n( get_option( 'date_format' ), $arc_week['start'] );
					$arc_week_end   = date_i18n( get_option( 'date_format' ), $arc_week['end'] );
					$url            = add_query_arg(
						array(
							'm' => $arc_year,
							'w' => $result->week,
						),
						home_url( '/' )
					);
					if ( 'post' !== $parsed_args['post_type'] ) {
						$url = add_query_arg( 'post_type', $parsed_args['post_type'], $url );
					}
					$text = $arc_week_start . $archive_week_separator . $arc_week_end;
					if ( $parsed_args['show_post_count'] ) {
						$parsed_args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
					}
					$selected = is_archive() && (string) $parsed_args['year'] === $result->yr && (string) $parsed_args['w'] === $result->week;
					$output  .= get_archives_link( $url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected );
				}
			}
		}
	} elseif ( ( 'postbypost' === $parsed_args['type'] ) || ( 'alpha' === $parsed_args['type'] ) ) {
		$orderby = ( 'alpha' === $parsed_args['type'] ) ? 'post_title ASC ' : 'post_date DESC, ID DESC ';
		$query   = "SELECT * FROM $wpdb->posts $join $where ORDER BY $orderby $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'post-queries' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'post-queries' );
		}
		if ( $results ) {
			foreach ( (array) $results as $result ) {
				if ( '0000-00-00 00:00:00' !== $result->post_date ) {
					$url = get_permalink( $result );
					if ( $result->post_title ) {
						/** This filter is documented in wp-includes/post-template.php */
						$text = strip_tags( apply_filters( 'the_title', $result->post_title, $result->ID ) );
					} else {
						$text = $result->ID;
					}
					$selected = get_the_ID() === $result->ID;
					$output  .= get_archives_link( $url, $text, $parsed_args['format'], $parsed_args['before'], $parsed_args['after'], $selected );
				}
			}
		}
	}

	if ( $parsed_args['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Lấy số ngày kể từ đầu tuần.
 *
 * @since 1.5.0
 *
 * @param int $num Số thứ tự ngày.
 * @return float Số ngày kể từ đầu tuần.
 */
function calendar_week_mod( $num ) {
	$base = 7;
	return ( $num - $base * floor( $num / $base ) );
}

/**
 * Hiển thị lịch với các ngày có bài viết dưới dạng liên kết.
 *
 * Lịch được lưu vào bộ nhớ đệm và sẽ được lấy lại nếu đã tồn tại. Nếu không có
 * bài viết nào trong tháng thì sẽ không hiển thị.
 *
 * @since 1.0.0
 * @since 6.8.0 Đã thêm tham số `$args`, với khả năng tương thích ngược
 *              cho các tham số `$initial` và `$display` đã được thay thế.
 *
 * @global wpdb      $wpdb      Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @global int       $m
 * @global int       $monthnum
 * @global int       $year
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ WordPress.
 * @global array     $posts
 *
 * @param array $args {
 *     Tùy chọn. Các đối số cho hàm `get_calendar`.
 *
 *     @type bool   $initial   Có sử dụng tên viết tắt lịch hay không. Mặc định true.
 *     @type bool   $display   Có hiển thị đầu ra lịch hay không. Mặc định true.
 *     @type string $post_type Tùy chọn. Loại bài viết. Mặc định 'post'.
 * }
 * @return void|string Void nếu đối số `$display` là true, HTML lịch nếu `$display` là false.
 */
function get_calendar( $args = array() ) {
	global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

	$defaults = array(
		'initial'   => true,
		'display'   => true,
		'post_type' => 'post',
	);

	$original_args = func_get_args();
	$args          = array();

	if ( ! empty( $original_args ) ) {
		if ( ! is_array( $original_args[0] ) ) {
			if ( isset( $original_args[0] ) && is_bool( $original_args[0] ) ) {
				$defaults['initial'] = $original_args[0];
			}
			if ( isset( $original_args[1] ) && is_bool( $original_args[1] ) ) {
				$defaults['display'] = $original_args[1];
			}
		} else {
			$args = $original_args[0];
		}
	}

	/**
	 * Lọc các đối số hàm `get_calendar` trước khi chúng được sử dụng.
	 *
	 * @since 6.8.0
	 *
	 * @param array $args {
	 *     Tùy chọn. Các đối số cho hàm `get_calendar`.
	 *
	 *     @type bool   $initial   Có sử dụng tên viết tắt lịch hay không. Mặc định true.
	 *     @type bool   $display   Có hiển thị đầu ra lịch hay không. Mặc định true.
	 *     @type string $post_type Tùy chọn. Loại bài viết. Mặc định 'post'.
	 * }
	 * @return array Các đối số cho hàm `get_calendar`.
	 */
	$args = apply_filters( 'get_calendar_args', wp_parse_args( $args, $defaults ) );

	if ( ! post_type_exists( $args['post_type'] ) ) {
		$args['post_type'] = 'post';
	}

	$w = 0;
	if ( isset( $_GET['w'] ) ) {
		$w = (int) $_GET['w'];
	}

	/*
	 * Chuẩn hóa khóa bộ nhớ đệm.
	 *
	 * Đoạn mã sau đảm bảo cùng một khóa bộ nhớ đệm được sử dụng cho cùng tham số
	 * và các tham số tương đương. Điều này ngăn `post_type > post, initial > true`
	 * tạo ra khóa khác với cùng giá trị theo thứ tự ngược lại.
	 *
	 * `display` được loại trừ khỏi khóa bộ nhớ đệm vì bộ nhớ đệm chứa cùng
	 * HTML bất kể hàm này cần echo hay trả về đầu ra.
	 *
	 * Các giá trị toàn cục chứa dữ liệu được tạo từ biến chuỗi truy vấn URL.
	 */
	$cache_args = $args;
	unset( $cache_args['display'] );

	$cache_args['globals'] = array(
		'm'        => $m,
		'monthnum' => $monthnum,
		'year'     => $year,
		'week'     => $w,
	);

	wp_recursive_ksort( $cache_args );
	$key   = md5( serialize( $cache_args ) );
	$cache = wp_cache_get( 'get_calendar', 'calendar' );

	if ( $cache && is_array( $cache ) && isset( $cache[ $key ] ) ) {
		/** This filter is documented in wp-includes/general-template.php */
		$output = apply_filters( 'get_calendar', $cache[ $key ], $args );

		if ( $args['display'] ) {
			echo $output;
			return;
		}

		return $output;
	}

	if ( ! is_array( $cache ) ) {
		$cache = array();
	}

	$post_type = $args['post_type'];

	// Kiểm tra nhanh. Nếu không có bài viết nào, dừng lại!
	if ( ! $posts ) {
		$gotsome = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 as test
				FROM $wpdb->posts
				WHERE post_type = %s
				AND post_status = 'publish'
				LIMIT 1",
				$post_type
			)
		);

		if ( ! $gotsome ) {
			$cache[ $key ] = '';
			wp_cache_set( 'get_calendar', $cache, 'calendar' );
			return;
		}
	}

	// week_begins = 0 tương ứng với Chủ nhật.
	$week_begins = (int) get_option( 'start_of_week' );

	// Hãy xác định chúng ta đang ở thời điểm nào.
	if ( ! empty( $monthnum ) && ! empty( $year ) ) {
		$thismonth = (int) $monthnum;
		$thisyear  = (int) $year;
	} elseif ( ! empty( $w ) ) {
		// Chúng ta cần lấy tháng từ MySQL.
		$thisyear = (int) substr( $m, 0, 4 );
		// Có vẻ các tuần của MySQL không khớp với PHP.
		$d         = ( ( $w - 1 ) * 7 ) + 6;
		$thismonth = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT DATE_FORMAT((DATE_ADD('%d0101', INTERVAL %d DAY) ), '%%m')",
				$thisyear,
				$d
			)
		);
	} elseif ( ! empty( $m ) ) {
		$thisyear = (int) substr( $m, 0, 4 );
		if ( strlen( $m ) < 6 ) {
			$thismonth = 1;
		} else {
			$thismonth = (int) substr( $m, 4, 2 );
		}
	} else {
		$thisyear  = (int) current_time( 'Y' );
		$thismonth = (int) current_time( 'm' );
	}

	$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
	$last_day  = gmdate( 't', $unixmonth );

	// Lấy tháng và năm trước và sau có ít nhất một bài viết.
	$previous = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
			FROM $wpdb->posts
			WHERE post_date < '%d-%d-01'
			AND post_type = %s AND post_status = 'publish'
			ORDER BY post_date DESC
			LIMIT 1",
			$thisyear,
			zeroise( $thismonth, 2 ),
			$post_type
		)
	);

	$next = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
			FROM $wpdb->posts
			WHERE post_date > '%d-%d-%d 23:59:59'
			AND post_type = %s AND post_status = 'publish'
			ORDER BY post_date ASC
			LIMIT 1",
			$thisyear,
			zeroise( $thismonth, 2 ),
			$last_day,
			$post_type
		)
	);

	/* translators: Calendar caption: 1: Month name, 2: 4-digit year. */
	$calendar_caption = _x( '%1$s %2$s', 'calendar caption' );
	$calendar_output  = '<table id="wp-calendar" class="wp-calendar-table">
	<caption>' . sprintf(
		$calendar_caption,
		$wp_locale->get_month( $thismonth ),
		gmdate( 'Y', $unixmonth )
	) . '</caption>
	<thead>
	<tr>';

	$myweek = array();

	for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
	}

	foreach ( $myweek as $wd ) {
		$day_name         = $args['initial'] ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
		$wd               = esc_attr( $wd );
		$calendar_output .= "\n\t\t<th scope=\"col\" aria-label=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>
	<tbody>
	<tr>';

	$daywithpost = array();

	// Lấy các ngày có bài viết.
	$dayswithposts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT DAYOFMONTH(post_date)
			FROM $wpdb->posts WHERE post_date >= '%d-%d-01 00:00:00'
			AND post_type = %s AND post_status = 'publish'
			AND post_date <= '%d-%d-%d 23:59:59'",
			$thisyear,
			zeroise( $thismonth, 2 ),
			$post_type,
			$thisyear,
			zeroise( $thismonth, 2 ),
			$last_day
		),
		ARRAY_N
	);

	if ( $dayswithposts ) {
		foreach ( (array) $dayswithposts as $daywith ) {
			$daywithpost[] = (int) $daywith[0];
		}
	}

	// Xem cần đệm bao nhiêu ở đầu.
	$pad = calendar_week_mod( (int) gmdate( 'w', $unixmonth ) - $week_begins );
	if ( $pad > 0 ) {
		$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
	}

	$newrow      = false;
	$daysinmonth = (int) gmdate( 't', $unixmonth );

	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset( $newrow ) && $newrow ) {
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		}

		$newrow = false;

		if ( (int) current_time( 'j' ) === $day
			&& (int) current_time( 'm' ) === $thismonth
			&& (int) current_time( 'Y' ) === $thisyear
		) {
			$calendar_output .= '<td id="today">';
		} else {
			$calendar_output .= '<td>';
		}

		if ( in_array( $day, $daywithpost, true ) ) {
			// Có bài viết nào hôm nay không?
			$date_format = gmdate( _x( 'F j, Y', 'daily archives date format' ), strtotime( "{$thisyear}-{$thismonth}-{$day}" ) );
			/* translators: Post calendar label. %s: Date. */
			$label            = sprintf( __( 'Posts published on %s' ), $date_format );
			$calendar_output .= sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				get_day_link( $thisyear, $thismonth, $day ),
				esc_attr( $label ),
				$day
			);
		} else {
			$calendar_output .= $day;
		}

		$calendar_output .= '</td>';

		if ( 6 === (int) calendar_week_mod( (int) gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
			$newrow = true;
		}
	}

	$pad = 7 - calendar_week_mod( (int) gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
	if ( 0 < $pad && $pad < 7 ) {
		$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr( $pad ) . '">&nbsp;</td>';
	}

	$calendar_output .= "\n\t</tr>\n\t</tbody>";

	$calendar_output .= "\n\t</table>";

	$calendar_output .= '<nav aria-label="' . __( 'Previous and next months' ) . '" class="wp-calendar-nav">';

	if ( $previous ) {
		$calendar_output .= "\n\t\t" . sprintf(
			'<span class="wp-calendar-nav-prev"><a href="%1$s">&laquo; %2$s</a></span>',
			get_month_link( $previous->year, $previous->month ),
			$wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) )
		);
	} else {
		$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-prev">&nbsp;</span>';
	}

	$calendar_output .= "\n\t\t" . '<span class="pad">&nbsp;</span>';

	if ( $next ) {
		$calendar_output .= "\n\t\t" . sprintf(
			'<span class="wp-calendar-nav-next"><a href="%1$s">%2$s &raquo;</a></span>',
			get_month_link( $next->year, $next->month ),
			$wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) )
		);
	} else {
		$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-next">&nbsp;</span>';
	}

	$calendar_output .= '
	</nav>';

	$cache[ $key ] = $calendar_output;
	wp_cache_set( 'get_calendar', $cache, 'calendar' );

	/**
	 * Lọc đầu ra HTML lịch.
	 *
	 * @since 3.0.0
	 * @since 6.8.0 Đã thêm tham số `$args`.
	 *
	 * @param string $calendar_output Đầu ra HTML của lịch.
	 * @param array  $args {
	 *     Tùy chọn. Mảng các đối số hiển thị.
	 *
	 *     @type bool   $initial   Có sử dụng tên viết tắt lịch hay không. Mặc định true.
	 *     @type bool   $display   Có hiển thị đầu ra lịch hay không. Mặc định true.
	 *     @type string $post_type Tùy chọn. Loại bài viết. Mặc định 'post'.
	 * }
	 */
	$calendar_output = apply_filters( 'get_calendar', $calendar_output, $args );

	if ( $args['display'] ) {
		echo $calendar_output;
		return;
	}

	return $calendar_output;
}

/**
 * Xóa kết quả bộ nhớ đệm của get_calendar.
 *
 * @see get_calendar()
 * @since 2.1.0
 */
function delete_get_calendar_cache() {
	wp_cache_delete( 'get_calendar', 'calendar' );
}

/**
 * Hiển thị tất cả các thẻ được phép ở định dạng HTML với các thuộc tính.
 *
 * Điều này hữu ích để hiển thị trong khu vực bình luận, cho biết phần tử và
 * thuộc tính nào được hỗ trợ. Cũng như cho bất kỳ plugin nào muốn hiển thị nó.
 *
 * @since 1.0.1
 * @since 4.4.0 Không còn được sử dụng trong core.
 *
 * @global array $allowedtags
 *
 * @return string Các thẻ HTML được phép đã mã hóa entity.
 */
function allowed_tags() {
	global $allowedtags;
	$allowed = '';
	foreach ( (array) $allowedtags as $tag => $attributes ) {
		$allowed .= '<' . $tag;
		if ( 0 < count( $attributes ) ) {
			foreach ( $attributes as $attribute => $limits ) {
				$allowed .= ' ' . $attribute . '=""';
			}
		}
		$allowed .= '> ';
	}
	return htmlentities( $allowed );
}

/***** Các thẻ Ngày/Giờ */

/**
 * Xuất ngày ở định dạng iso8601 cho các file xml.
 *
 * @since 1.0.0
 */
function the_date_xml() {
	echo mysql2date( 'Y-m-d', get_post()->post_date, false );
}

/**
 * Hiển thị hoặc lấy ngày của bài viết (mỗi ngày chỉ một lần).
 *
 * Chỉ xuất ngày nếu ngày của bài viết hiện tại khác với
 * ngày đã xuất trước đó.
 *
 * Nghĩa là chỉ hiển thị một lần cho mỗi ngày trong vòng lặp, ngay cả khi
 * hàm được gọi nhiều lần cho mỗi bài viết.
 *
 * Đầu ra HTML có thể được lọc bằng {@see 'the_date'}.
 * Chuỗi ngày có thể được lọc bằng {@see 'get_the_date'}.
 *
 * @since 0.71
 *
 * @global string $currentday  Ngày của bài viết hiện tại trong vòng lặp.
 * @global string $previousday Ngày của bài viết trước đó trong vòng lặp.
 *
 * @param string $format  Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'date_format'.
 * @param string $before  Tùy chọn. Nội dung xuất trước ngày. Mặc định rỗng.
 * @param string $after   Tùy chọn. Nội dung xuất sau ngày. Mặc định rỗng.
 * @param bool   $display Tùy chọn. Có echo ngày hay trả về nó. Mặc định true.
 * @return string|void Chuỗi nếu lấy giá trị.
 */
function the_date( $format = '', $before = '', $after = '', $display = true ) {
	global $currentday, $previousday;

	$the_date = '';

	if ( is_new_day() ) {
		$the_date    = $before . get_the_date( $format ) . $after;
		$previousday = $currentday;
	}

	/**
	 * Lọc ngày của bài viết để hiển thị.
	 *
	 * @since 0.71
	 *
	 * @param string $the_date Chuỗi ngày đã định dạng.
	 * @param string $format   Định dạng ngày PHP.
	 * @param string $before   Đầu ra HTML trước ngày.
	 * @param string $after    Đầu ra HTML sau ngày.
	 */
	$the_date = apply_filters( 'the_date', $the_date, $format, $before, $after );

	if ( $display ) {
		echo $the_date;
	} else {
		return $the_date;
	}
}

/**
 * Lấy ngày của bài viết.
 *
 * Khác với the_date(), hàm này luôn trả về ngày.
 * Chỉnh sửa đầu ra bằng bộ lọc {@see 'get_the_date'}.
 *
 * @since 3.0.0
 *
 * @param string      $format Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'date_format'.
 * @param int|WP_Post $post   Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @return string|int|false Ngày bài viết hiện tại được viết. False khi thất bại.
 */
function get_the_date( $format = '', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$_format = ! empty( $format ) ? $format : get_option( 'date_format' );

	$the_date = get_post_time( $_format, false, $post, true );

	/**
	 * Lọc ngày của bài viết.
	 *
	 * @since 3.0.0
	 *
	 * @param string|int $the_date Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
	 * @param string     $format   Định dạng ngày PHP.
	 * @param WP_Post    $post     Đối tượng bài viết.
	 */
	return apply_filters( 'get_the_date', $the_date, $format, $post );
}

/**
 * Hiển thị ngày bài viết được chỉnh sửa lần cuối.
 *
 * @since 2.1.0
 *
 * @param string $format  Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'date_format'.
 * @param string $before  Tùy chọn. Nội dung xuất trước ngày. Mặc định rỗng.
 * @param string $after   Tùy chọn. Nội dung xuất sau ngày. Mặc định rỗng.
 * @param bool   $display Tùy chọn. Có echo ngày hay trả về nó. Mặc định true.
 * @return string|void Chuỗi nếu lấy giá trị.
 */
function the_modified_date( $format = '', $before = '', $after = '', $display = true ) {
	$the_modified_date = $before . get_the_modified_date( $format ) . $after;

	/**
	 * Lọc ngày chỉnh sửa cuối cùng của bài viết để hiển thị.
	 *
	 * @since 2.1.0
	 *
	 * @param string $the_modified_date Ngày chỉnh sửa cuối cùng.
	 * @param string $format            Định dạng ngày PHP.
	 * @param string $before            Đầu ra HTML trước ngày.
	 * @param string $after             Đầu ra HTML sau ngày.
	 */
	$the_modified_date = apply_filters( 'the_modified_date', $the_modified_date, $format, $before, $after );

	if ( $display ) {
		echo $the_modified_date;
	} else {
		return $the_modified_date;
	}
}

/**
 * Lấy ngày bài viết được chỉnh sửa lần cuối.
 *
 * @since 2.1.0
 * @since 4.6.0 Đã thêm tham số `$post`.
 *
 * @param string      $format Tùy chọn. Định dạng ngày PHP. Mặc định theo tùy chọn 'date_format'.
 * @param int|WP_Post $post   Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @return string|int|false Ngày bài viết hiện tại được chỉnh sửa. False khi thất bại.
 */
function get_the_modified_date( $format = '', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		// Để tương thích ngược, các lỗi vẫn đi qua bộ lọc bên dưới.
		$the_time = false;
	} else {
		$_format = ! empty( $format ) ? $format : get_option( 'date_format' );

		$the_time = get_post_modified_time( $_format, false, $post, true );
	}

	/**
	 * Lọc ngày chỉnh sửa cuối cùng của bài viết.
	 *
	 * @since 2.1.0
	 * @since 4.6.0 Đã thêm tham số `$post`.
	 *
	 * @param string|int|false $the_time Ngày đã định dạng hoặc false nếu không tìm thấy bài viết.
	 * @param string           $format   Định dạng ngày PHP.
	 * @param WP_Post|null     $post     Đối tượng WP_Post hoặc null nếu không tìm thấy bài viết.
	 */
	return apply_filters( 'get_the_modified_date', $the_time, $format, $post );
}

/**
 * Hiển thị giờ của bài viết.
 *
 * @since 0.71
 *
 * @param string $format Tùy chọn. Định dạng để lấy giờ bài viết
 *                       được viết. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
 *                       Mặc định theo tùy chọn 'time_format'.
 */
function the_time( $format = '' ) {
	/**
	 * Lọc giờ của bài viết để hiển thị.
	 *
	 * @since 0.71
	 *
	 * @param string $get_the_time Giờ đã định dạng.
	 * @param string $format       Định dạng để lấy giờ bài viết
	 *                             được viết. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
	 */
	echo apply_filters( 'the_time', get_the_time( $format ), $format );
}

/**
 * Lấy giờ của bài viết.
 *
 * @since 1.5.0
 *
 * @param string      $format Tùy chọn. Định dạng để lấy giờ bài viết
 *                            được viết. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
 *                            Mặc định theo tùy chọn 'time_format'.
 * @param int|WP_Post $post   ID bài viết hoặc đối tượng bài viết. Mặc định là đối tượng `$post` toàn cục.
 * @return string|int|false Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
 *                          False khi thất bại.
 */
function get_the_time( $format = '', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$_format = ! empty( $format ) ? $format : get_option( 'time_format' );

	$the_time = get_post_time( $_format, false, $post, true );

	/**
	 * Lọc giờ của bài viết.
	 *
	 * @since 1.5.0
	 *
	 * @param string|int $the_time Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
	 * @param string     $format   Định dạng để lấy giờ bài viết
	 *                             được viết. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
	 * @param WP_Post    $post     Đối tượng bài viết.
	 */
	return apply_filters( 'get_the_time', $the_time, $format, $post );
}

/**
 * Lấy giờ đã bản địa hóa của bài viết.
 *
 * @since 2.0.0
 *
 * @param string      $format    Tùy chọn. Định dạng để lấy giờ bài viết
 *                               được viết. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP. Mặc định 'U'.
 * @param bool        $gmt       Tùy chọn. Có lấy giờ GMT hay không. Mặc định false.
 * @param int|WP_Post $post      ID bài viết hoặc đối tượng bài viết. Mặc định là đối tượng `$post` toàn cục.
 * @param bool        $translate Có dịch chuỗi thời gian hay không. Mặc định false.
 * @return string|int|false Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
 *                          False khi thất bại.
 */
function get_post_time( $format = 'U', $gmt = false, $post = null, $translate = false ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$source   = ( $gmt ) ? 'gmt' : 'local';
	$datetime = get_post_datetime( $post, 'date', $source );

	if ( false === $datetime ) {
		return false;
	}

	if ( 'U' === $format || 'G' === $format ) {
		$time = $datetime->getTimestamp();

		// Trả về tổng của timestamp với offset múi giờ. Lý tưởng không nên sử dụng.
		if ( ! $gmt ) {
			$time += $datetime->getOffset();
		}
	} elseif ( $translate ) {
		$time = wp_date( $format, $datetime->getTimestamp(), $gmt ? new DateTimeZone( 'UTC' ) : null );
	} else {
		if ( $gmt ) {
			$datetime = $datetime->setTimezone( new DateTimeZone( 'UTC' ) );
		}

		$time = $datetime->format( $format );
	}

	/**
	 * Lọc giờ đã bản địa hóa của bài viết.
	 *
	 * @since 2.6.0
	 *
	 * @param string|int $time   Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
	 * @param string     $format Định dạng để lấy ngày của bài viết.
	 *                           Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
	 * @param bool       $gmt    Có lấy giờ GMT hay không.
	 */
	return apply_filters( 'get_post_time', $time, $format, $gmt );
}

/**
 * Lấy thời gian đăng hoặc chỉnh sửa bài viết dưới dạng đối tượng `DateTimeImmutable`.
 *
 * Đối tượng sẽ được đặt theo múi giờ từ cài đặt WordPress.
 *
 * Vì lý do kế thừa, hàm này cho phép chọn khởi tạo từ giờ địa phương hoặc UTC trong cơ sở dữ liệu.
 * Thông thường điều này không tạo ra sự khác biệt về kết quả. Tuy nhiên, các giá trị có thể bị lệch
 * trong cơ sở dữ liệu, thường là do thay đổi cài đặt múi giờ. Tham số đảm bảo khả năng tái tạo
 * các hành vi tương thích ngược trong những trường hợp như vậy.
 *
 * @since 5.3.0
 *
 * @param int|WP_Post $post   Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là đối tượng `$post` toàn cục.
 * @param string      $field  Tùy chọn. Thời gian đăng hoặc chỉnh sửa từ cơ sở dữ liệu. Chấp nhận 'date' hoặc 'modified'.
 *                            Mặc định 'date'.
 * @param string      $source Tùy chọn. Giờ địa phương hoặc UTC từ cơ sở dữ liệu. Chấp nhận 'local' hoặc 'gmt'.
 *                            Mặc định 'local'.
 * @return DateTimeImmutable|false Đối tượng thời gian khi thành công, false khi thất bại.
 */
function get_post_datetime( $post = null, $field = 'date', $source = 'local' ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$wp_timezone = wp_timezone();

	if ( 'gmt' === $source ) {
		$time     = ( 'modified' === $field ) ? $post->post_modified_gmt : $post->post_date_gmt;
		$timezone = new DateTimeZone( 'UTC' );
	} else {
		$time     = ( 'modified' === $field ) ? $post->post_modified : $post->post_date;
		$timezone = $wp_timezone;
	}

	if ( empty( $time ) || '0000-00-00 00:00:00' === $time ) {
		return false;
	}

	$datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', $time, $timezone );

	if ( false === $datetime ) {
		return false;
	}

	return $datetime->setTimezone( $wp_timezone );
}

/**
 * Lấy thời gian đăng hoặc chỉnh sửa bài viết dưới dạng timestamp Unix.
 *
 * Lưu ý rằng hàm này trả về timestamp Unix thực sự, không cộng thêm offset múi giờ
 * như các hàm WP cũ.
 *
 * @since 5.3.0
 *
 * @param int|WP_Post $post  Tùy chọn. ID bài viết hoặc đối tượng bài viết. Mặc định là đối tượng `$post` toàn cục.
 * @param string      $field Tùy chọn. Thời gian đăng hoặc chỉnh sửa từ cơ sở dữ liệu. Chấp nhận 'date' hoặc 'modified'.
 *                           Mặc định 'date'.
 * @return int|false Timestamp Unix khi thành công, false khi thất bại.
 */
function get_post_timestamp( $post = null, $field = 'date' ) {
	$datetime = get_post_datetime( $post, $field );

	if ( false === $datetime ) {
		return false;
	}

	return $datetime->getTimestamp();
}

/**
 * Hiển thị giờ bài viết được chỉnh sửa lần cuối.
 *
 * @since 2.0.0
 *
 * @param string $format Tùy chọn. Định dạng để lấy giờ bài viết
 *                       được chỉnh sửa. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
 *                       Mặc định theo tùy chọn 'time_format'.
 */
function the_modified_time( $format = '' ) {
	/**
	 * Lọc giờ bản địa hóa của lần chỉnh sửa cuối cùng để hiển thị.
	 *
	 * @since 2.0.0
	 *
	 * @param string|false $get_the_modified_time Giờ đã định dạng hoặc false nếu không tìm thấy bài viết.
	 * @param string       $format                Định dạng để lấy giờ bài viết
	 *                                            được chỉnh sửa. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
	 */
	echo apply_filters( 'the_modified_time', get_the_modified_time( $format ), $format );
}

/**
 * Lấy giờ bài viết được chỉnh sửa lần cuối.
 *
 * @since 2.0.0
 * @since 4.6.0 Đã thêm tham số `$post`.
 *
 * @param string      $format Tùy chọn. Định dạng để lấy giờ bài viết
 *                            được chỉnh sửa. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
 *                            Mặc định theo tùy chọn 'time_format'.
 * @param int|WP_Post $post   Tùy chọn. ID bài viết hoặc đối tượng WP_Post. Mặc định bài viết hiện tại.
 * @return string|int|false Chuỗi ngày đã định dạng hoặc timestamp Unix. False khi thất bại.
 */
function get_the_modified_time( $format = '', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		// Để tương thích ngược, các lỗi vẫn đi qua bộ lọc bên dưới.
		$the_time = false;
	} else {
		$_format = ! empty( $format ) ? $format : get_option( 'time_format' );

		$the_time = get_post_modified_time( $_format, false, $post, true );
	}

	/**
	 * Lọc giờ bản địa hóa của lần chỉnh sửa cuối cùng.
	 *
	 * @since 2.0.0
	 * @since 4.6.0 Đã thêm tham số `$post`.
	 *
	 * @param string|int|false $the_time Giờ đã định dạng hoặc false nếu không tìm thấy bài viết.
	 * @param string           $format   Định dạng để lấy giờ bài viết
	 *                                   được chỉnh sửa. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP.
	 * @param WP_Post|null     $post     Đối tượng WP_Post hoặc null nếu không tìm thấy bài viết.
	 */
	return apply_filters( 'get_the_modified_time', $the_time, $format, $post );
}

/**
 * Lấy giờ bài viết được chỉnh sửa lần cuối.
 *
 * @since 2.0.0
 *
 * @param string      $format    Tùy chọn. Định dạng để lấy giờ bài viết
 *                               được chỉnh sửa. Chấp nhận 'G', 'U', hoặc định dạng ngày PHP. Mặc định 'U'.
 * @param bool        $gmt       Tùy chọn. Có lấy giờ GMT hay không. Mặc định false.
 * @param int|WP_Post $post      ID bài viết hoặc đối tượng bài viết. Mặc định là đối tượng `$post` toàn cục.
 * @param bool        $translate Có dịch chuỗi thời gian hay không. Mặc định false.
 * @return string|int|false Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
 *                          False khi thất bại.
 */
function get_post_modified_time( $format = 'U', $gmt = false, $post = null, $translate = false ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$source   = ( $gmt ) ? 'gmt' : 'local';
	$datetime = get_post_datetime( $post, 'modified', $source );

	if ( false === $datetime ) {
		return false;
	}

	if ( 'U' === $format || 'G' === $format ) {
		$time = $datetime->getTimestamp();

		// Trả về tổng của timestamp với offset múi giờ. Lý tưởng không nên sử dụng.
		if ( ! $gmt ) {
			$time += $datetime->getOffset();
		}
	} elseif ( $translate ) {
		$time = wp_date( $format, $datetime->getTimestamp(), $gmt ? new DateTimeZone( 'UTC' ) : null );
	} else {
		if ( $gmt ) {
			$datetime = $datetime->setTimezone( new DateTimeZone( 'UTC' ) );
		}

		$time = $datetime->format( $format );
	}

	/**
	 * Lọc giờ bản địa hóa của lần chỉnh sửa cuối cùng.
	 *
	 * @since 2.8.0
	 *
	 * @param string|int $time   Chuỗi ngày đã định dạng hoặc timestamp Unix nếu `$format` là 'U' hoặc 'G'.
	 * @param string     $format Định dạng để lấy giờ bài viết được chỉnh sửa.
	 *                           Chấp nhận 'G', 'U', hoặc định dạng ngày PHP. Mặc định 'U'.
	 * @param bool       $gmt    Có lấy giờ GMT hay không. Mặc định false.
	 */
	return apply_filters( 'get_post_modified_time', $time, $format, $gmt );
}

/**
 * Hiển thị ngày trong tuần đã bản địa hóa của bài viết.
 *
 * @since 0.71
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ WordPress.
 */
function the_weekday() {
	global $wp_locale;

	$post = get_post();

	if ( ! $post ) {
		return;
	}

	$the_weekday = $wp_locale->get_weekday( get_post_time( 'w', false, $post ) );

	/**
	 * Lọc ngày trong tuần đã bản địa hóa của bài viết để hiển thị.
	 *
	 * @since 0.71
	 *
	 * @param string $the_weekday
	 */
	echo apply_filters( 'the_weekday', $the_weekday );
}

/**
 * Hiển thị ngày trong tuần đã bản địa hóa của bài viết.
 *
 * Chỉ xuất ngày trong tuần nếu ngày trong tuần của bài viết hiện tại khác với
 * ngày đã xuất trước đó.
 *
 * @since 0.71
 *
 * @global WP_Locale $wp_locale       Đối tượng locale ngày và giờ WordPress.
 * @global string    $currentday      Ngày của bài viết hiện tại trong vòng lặp.
 * @global string    $previousweekday Ngày của bài viết trước đó trong vòng lặp.
 *
 * @param string $before Tùy chọn. Nội dung xuất trước ngày. Mặc định rỗng.
 * @param string $after  Tùy chọn. Nội dung xuất sau ngày. Mặc định rỗng.
 */
function the_weekday_date( $before = '', $after = '' ) {
	global $wp_locale, $currentday, $previousweekday;

	$post = get_post();

	if ( ! $post ) {
		return;
	}

	$the_weekday_date = '';

	if ( $currentday !== $previousweekday ) {
		$the_weekday_date .= $before;
		$the_weekday_date .= $wp_locale->get_weekday( get_post_time( 'w', false, $post ) );
		$the_weekday_date .= $after;
		$previousweekday   = $currentday;
	}

	/**
	 * Lọc ngày trong tuần đã bản địa hóa của bài viết để hiển thị.
	 *
	 * @since 0.71
	 *
	 * @param string $the_weekday_date Ngày trong tuần mà bài viết được viết.
	 * @param string $before           HTML xuất trước ngày.
	 * @param string $after            HTML xuất sau ngày.
	 */
	echo apply_filters( 'the_weekday_date', $the_weekday_date, $before, $after );
}

/**
 * Kích hoạt action wp_head.
 *
 * Xem {@see 'wp_head'}.
 *
 * @since 1.2.0
 */
function wp_head() {
	/**
	 * In script hoặc dữ liệu trong thẻ head ở giao diện người dùng.
	 *
	 * @since 1.5.0
	 */
	do_action( 'wp_head' );
}

/**
 * Kích hoạt action wp_footer.
 *
 * Xem {@see 'wp_footer'}.
 *
 * @since 1.5.1
 */
function wp_footer() {
	/**
	 * In script hoặc dữ liệu trước thẻ đóng body ở giao diện người dùng.
	 *
	 * @since 1.5.1
	 */
	do_action( 'wp_footer' );
}

/**
 * Kích hoạt action wp_body_open.
 *
 * Xem {@see 'wp_body_open'}.
 *
 * @since 5.2.0
 */
function wp_body_open() {
	/**
	 * Được kích hoạt sau thẻ mở body.
	 *
	 * @since 5.2.0
	 */
	do_action( 'wp_body_open' );
}

/**
 * Hiển thị các liên kết đến nguồn cấp dữ liệu chung.
 *
 * @since 2.8.0
 *
 * @param array $args Các đối số tùy chọn.
 */
function feed_links( $args = array() ) {
	if ( ! current_theme_supports( 'automatic-feed-links' ) ) {
		return;
	}

	$defaults = array(
		/* translators: Separator between site name and feed type in feed links. */
		'separator' => _x( '&raquo;', 'feed link' ),
		/* translators: 1: Site title, 2: Separator (raquo). */
		'feedtitle' => __( '%1$s %2$s Feed' ),
		/* translators: 1: Site title, 2: Separator (raquo). */
		'comstitle' => __( '%1$s %2$s Comments Feed' ),
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * Lọc các đối số liên kết nguồn cấp dữ liệu.
	 *
	 * @since 6.7.0
	 *
	 * @param array $args Mảng các đối số liên kết nguồn cấp dữ liệu.
	 */
	$args = apply_filters( 'feed_links_args', $args );

	/**
	 * Lọc có hiển thị liên kết nguồn cấp bài viết hay không.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $show Có hiển thị liên kết nguồn cấp bài viết hay không. Mặc định true.
	 */
	if ( apply_filters( 'feed_links_show_posts_feed', true ) ) {
		printf(
			'<link rel="alternate" type="%s" title="%s" href="%s" />' . "\n",
			feed_content_type(),
			esc_attr( sprintf( $args['feedtitle'], get_bloginfo( 'name' ), $args['separator'] ) ),
			esc_url( get_feed_link() )
		);
	}

	/**
	 * Lọc có hiển thị liên kết nguồn cấp bình luận hay không.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $show Có hiển thị liên kết nguồn cấp bình luận hay không. Mặc định true.
	 */
	if ( apply_filters( 'feed_links_show_comments_feed', true ) ) {
		printf(
			'<link rel="alternate" type="%s" title="%s" href="%s" />' . "\n",
			feed_content_type(),
			esc_attr( sprintf( $args['comstitle'], get_bloginfo( 'name' ), $args['separator'] ) ),
			esc_url( get_feed_link( 'comments_' . get_default_feed() ) )
		);
	}
}

/**
 * Hiển thị các liên kết đến nguồn cấp bổ sung như nguồn cấp chuyên mục.
 *
 * @since 2.8.0
 *
 * @param array $args Các đối số tùy chọn.
 */
function feed_links_extra( $args = array() ) {
	$defaults = array(
		/* translators: Separator between site name and feed type in feed links. */
		'separator'     => _x( '&raquo;', 'feed link' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Post title. */
		'singletitle'   => __( '%1$s %2$s %3$s Comments Feed' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Category name. */
		'cattitle'      => __( '%1$s %2$s %3$s Category Feed' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Tag name. */
		'tagtitle'      => __( '%1$s %2$s %3$s Tag Feed' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Term name, 4: Taxonomy singular name. */
		'taxtitle'      => __( '%1$s %2$s %3$s %4$s Feed' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Author name. */
		'authortitle'   => __( '%1$s %2$s Posts by %3$s Feed' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Search query. */
		'searchtitle'   => __( '%1$s %2$s Search Results for &#8220;%3$s&#8221; Feed' ),
		/* translators: 1: Site name, 2: Separator (raquo), 3: Post type name. */
		'posttypetitle' => __( '%1$s %2$s %3$s Feed' ),
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * Lọc các đối số liên kết nguồn cấp bổ sung.
	 *
	 * @since 6.7.0
	 *
	 * @param array $args Mảng các đối số liên kết nguồn cấp bổ sung.
	 */
	$args = apply_filters( 'feed_links_extra_args', $args );

	if ( is_singular() ) {
		$id   = 0;
		$post = get_post( $id );

		/** This filter is documented in wp-includes/general-template.php */
		$show_comments_feed = apply_filters( 'feed_links_show_comments_feed', true );

		/**
		 * Lọc có hiển thị liên kết nguồn cấp bình luận bài viết hay không.
		 *
		 * Bộ lọc này cho phép bật hoặc tắt liên kết nguồn cấp cho bài viết đơn
		 * một cách độc lập với {@see 'feed_links_show_comments_feed'}
		 * (điều khiển nguồn cấp bình luận toàn cục). Kết quả của bộ lọc đó
		 * được chấp nhận làm tham số.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show_comments_feed Có hiển thị liên kết nguồn cấp bình luận bài viết hay không. Mặc định là
		 *                                 kết quả bộ lọc {@see 'feed_links_show_comments_feed'}.
		 */
		$show_post_comments_feed = apply_filters( 'feed_links_extra_show_post_comments_feed', $show_comments_feed );

		if ( $show_post_comments_feed && ( comments_open() || pings_open() || $post->comment_count > 0 ) ) {
			$title = sprintf(
				$args['singletitle'],
				get_bloginfo( 'name' ),
				$args['separator'],
				the_title_attribute( array( 'echo' => false ) )
			);

			$feed_link = get_post_comments_feed_link( $post->ID );

			if ( $feed_link ) {
				$href = $feed_link;
			}
		}
	} elseif ( is_post_type_archive() ) {
		/**
		 * Lọc có hiển thị liên kết nguồn cấp lưu trữ loại bài viết hay không.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show Có hiển thị liên kết nguồn cấp lưu trữ loại bài viết hay không. Mặc định true.
		 */
		$show_post_type_archive_feed = apply_filters( 'feed_links_extra_show_post_type_archive_feed', true );

		if ( $show_post_type_archive_feed ) {
			$post_type = get_query_var( 'post_type' );

			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}

			$post_type_obj = get_post_type_object( $post_type );

			$title = sprintf(
				$args['posttypetitle'],
				get_bloginfo( 'name' ),
				$args['separator'],
				$post_type_obj->labels->name
			);

			$href = get_post_type_archive_feed_link( $post_type_obj->name );
		}
	} elseif ( is_category() ) {
		/**
		 * Lọc có hiển thị liên kết nguồn cấp chuyên mục hay không.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show Có hiển thị liên kết nguồn cấp chuyên mục hay không. Mặc định true.
		 */
		$show_category_feed = apply_filters( 'feed_links_extra_show_category_feed', true );

		if ( $show_category_feed ) {
			$term = get_queried_object();

			if ( $term ) {
				$title = sprintf(
					$args['cattitle'],
					get_bloginfo( 'name' ),
					$args['separator'],
					$term->name
				);

				$href = get_category_feed_link( $term->term_id );
			}
		}
	} elseif ( is_tag() ) {
		/**
		 * Lọc có hiển thị liên kết nguồn cấp thẻ hay không.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show Có hiển thị liên kết nguồn cấp thẻ hay không. Mặc định true.
		 */
		$show_tag_feed = apply_filters( 'feed_links_extra_show_tag_feed', true );

		if ( $show_tag_feed ) {
			$term = get_queried_object();

			if ( $term ) {
				$title = sprintf(
					$args['tagtitle'],
					get_bloginfo( 'name' ),
					$args['separator'],
					$term->name
				);

				$href = get_tag_feed_link( $term->term_id );
			}
		}
	} elseif ( is_tax() ) {
		/**
		 * Lọc có hiển thị liên kết nguồn cấp taxonomy tùy chỉnh hay không.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show Có hiển thị liên kết nguồn cấp taxonomy tùy chỉnh hay không. Mặc định true.
		 */
		$show_tax_feed = apply_filters( 'feed_links_extra_show_tax_feed', true );

		if ( $show_tax_feed ) {
			$term = get_queried_object();

			if ( $term ) {
				$tax = get_taxonomy( $term->taxonomy );

				$title = sprintf(
					$args['taxtitle'],
					get_bloginfo( 'name' ),
					$args['separator'],
					$term->name,
					$tax->labels->singular_name
				);

				$href = get_term_feed_link( $term->term_id, $term->taxonomy );
			}
		}
	} elseif ( is_author() ) {
		/**
		 * Lọc có hiển thị liên kết nguồn cấp tác giả hay không.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show Có hiển thị liên kết nguồn cấp tác giả hay không. Mặc định true.
		 */
		$show_author_feed = apply_filters( 'feed_links_extra_show_author_feed', true );

		if ( $show_author_feed ) {
			$author_id = (int) get_query_var( 'author' );

			$title = sprintf(
				$args['authortitle'],
				get_bloginfo( 'name' ),
				$args['separator'],
				get_the_author_meta( 'display_name', $author_id )
			);

			$href = get_author_feed_link( $author_id );
		}
	} elseif ( is_search() ) {
		/**
		 * Lọc có hiển thị liên kết nguồn cấp kết quả tìm kiếm hay không.
		 *
		 * @since 6.1.0
		 *
		 * @param bool $show Có hiển thị liên kết nguồn cấp kết quả tìm kiếm hay không. Mặc định true.
		 */
		$show_search_feed = apply_filters( 'feed_links_extra_show_search_feed', true );

		if ( $show_search_feed ) {
			$title = sprintf(
				$args['searchtitle'],
				get_bloginfo( 'name' ),
				$args['separator'],
				get_search_query( false )
			);

			$href = get_search_feed_link();
		}
	}

	if ( isset( $title ) && isset( $href ) ) {
		printf(
			'<link rel="alternate" type="%s" title="%s" href="%s" />' . "\n",
			feed_content_type(),
			esc_attr( $title ),
			esc_url( $href )
		);
	}
}

/**
 * Hiển thị liên kết đến điểm cuối dịch vụ Really Simple Discovery.
 *
 * @link http://archipelago.phrasewise.com/rsd
 * @since 2.0.0
 */
function rsd_link() {
	printf(
		'<link rel="EditURI" type="application/rsd+xml" title="RSD" href="%s" />' . "\n",
		esc_url( site_url( 'xmlrpc.php?rsd', 'rpc' ) )
	);
}

/**
 * Hiển thị thẻ meta referrer `strict-origin-when-cross-origin`.
 *
 * Xuất thẻ meta referrer `strict-origin-when-cross-origin` yêu cầu trình duyệt không gửi
 * URL đầy đủ làm referrer đến các trang web khác khi tải tài nguyên cross-origin.
 *
 * Cách sử dụng thông thường là làm callback {@see 'wp_head'}:
 *
 *     add_action( 'wp_head', 'wp_strict_cross_origin_referrer' );
 *
 * @since 5.7.0
 */
function wp_strict_cross_origin_referrer() {
	?>
	<meta name='referrer' content='strict-origin-when-cross-origin' />
	<?php
}

/**
 * Hiển thị các thẻ meta biểu tượng trang web.
 *
 * @since 4.3.0
 *
 * @link https://www.whatwg.org/specs/web-apps/current-work/multipage/links.html#rel-icon Biểu tượng liên kết đặc tả HTML5.
 */
function wp_site_icon() {
	if ( ! has_site_icon() && ! is_customize_preview() ) {
		return;
	}

	$meta_tags = array();
	$icon_32   = get_site_icon_url( 32 );
	if ( empty( $icon_32 ) && is_customize_preview() ) {
		$icon_32 = '/favicon.ico'; // Phục vụ URL favicon mặc định trong trình tùy chỉnh để phần tử có thể được cập nhật cho xem trước.
	}
	if ( $icon_32 ) {
		$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( $icon_32 ) );
	}
	$icon_192 = get_site_icon_url( 192 );
	if ( $icon_192 ) {
		$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( $icon_192 ) );
	}
	$icon_180 = get_site_icon_url( 180 );
	if ( $icon_180 ) {
		$meta_tags[] = sprintf( '<link rel="apple-touch-icon" href="%s" />', esc_url( $icon_180 ) );
	}
	$icon_270 = get_site_icon_url( 270 );
	if ( $icon_270 ) {
		$meta_tags[] = sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( $icon_270 ) );
	}

	/**
	 * Lọc các thẻ meta biểu tượng trang web để plugin có thể thêm thẻ riêng.
	 *
	 * @since 4.3.0
	 *
	 * @param string[] $meta_tags Mảng các thẻ meta biểu tượng trang web.
	 */
	$meta_tags = apply_filters( 'site_icon_meta_tags', $meta_tags );
	$meta_tags = array_filter( $meta_tags );

	foreach ( $meta_tags as $meta_tag ) {
		echo "$meta_tag\n";
	}
}

/**
 * In gợi ý tài nguyên cho trình duyệt để tải trước, kết xuất trước
 * và kết nối trước đến các trang web.
 *
 * Cung cấp gợi ý cho trình duyệt để tải trước các trang cụ thể hoặc kết xuất chúng
 * trong nền, thực hiện tra cứu DNS hoặc bắt đầu quá trình bắt tay kết nối
 * (DNS, TCP, TLS) trong nền.
 *
 * Các chỉ báo cải thiện hiệu suất này hoạt động bằng cách sử dụng `<link rel"...">`.
 *
 * @since 4.6.0
 */
function wp_resource_hints() {
	$hints = array(
		'dns-prefetch' => wp_dependencies_unique_hosts(),
		'preconnect'   => array(),
		'prefetch'     => array(),
		'prerender'    => array(),
	);

	foreach ( $hints as $relation_type => $urls ) {
		$unique_urls = array();

		/**
		 * Lọc tên miền và URL cho gợi ý tài nguyên của loại quan hệ đã cho.
		 *
		 * @since 4.6.0
		 * @since 4.7.0 Tham số `$urls` chấp nhận mảng các thuộc tính HTML cụ thể
		 *              làm phần tử con.
		 *
		 * @param array  $urls {
		 *     Mảng các tài nguyên và thuộc tính của chúng, hoặc URL để in cho gợi ý tài nguyên.
		 *
		 *     @type array|string ...$0 {
		 *         Mảng các thuộc tính tài nguyên, hoặc chuỗi URL.
		 *
		 *         @type string $href        URL để bao gồm trong gợi ý tài nguyên. Bắt buộc.
		 *         @type string $as          Cách trình duyệt nên xử lý tài nguyên
		 *                                   (`script`, `style`, `image`, `document`, v.v.).
		 *         @type string $crossorigin Chỉ định chính sách CORS của tài nguyên được chỉ định.
		 *         @type float  $pr          Xác suất dự kiến gợi ý tài nguyên sẽ được sử dụng.
		 *         @type string $type        Loại tài nguyên (`text/html`, `text/css`, v.v.).
		 *     }
		 * }
		 * @param string $relation_type Loại quan hệ mà URL được in cho. Một trong
		 *                              'dns-prefetch', 'preconnect', 'prefetch', hoặc 'prerender'.
		 */
		$urls = apply_filters( 'wp_resource_hints', $urls, $relation_type );

		foreach ( $urls as $key => $url ) {
			$atts = array();

			if ( is_array( $url ) ) {
				if ( isset( $url['href'] ) ) {
					$atts = $url;
					$url  = $url['href'];
				} else {
					continue;
				}
			}

			$url = esc_url( $url, array( 'http', 'https' ) );

			if ( ! $url ) {
				continue;
			}

			if ( isset( $unique_urls[ $url ] ) ) {
				continue;
			}

			if ( in_array( $relation_type, array( 'preconnect', 'dns-prefetch' ), true ) ) {
				$parsed = wp_parse_url( $url );

				if ( empty( $parsed['host'] ) ) {
					continue;
				}

				if ( 'preconnect' === $relation_type && ! empty( $parsed['scheme'] ) ) {
					$url = $parsed['scheme'] . '://' . $parsed['host'];
				} else {
					// Sử dụng URL tương đối giao thức cho dns-prefetch hoặc nếu thiếu scheme.
					$url = '//' . $parsed['host'];
				}
			}

			$atts['rel']  = $relation_type;
			$atts['href'] = $url;

			$unique_urls[ $url ] = $atts;
		}

		foreach ( $unique_urls as $atts ) {
			$html = '';

			foreach ( $atts as $attr => $value ) {
				if ( ! is_scalar( $value )
					|| ( ! in_array( $attr, array( 'as', 'crossorigin', 'href', 'pr', 'rel', 'type' ), true ) && ! is_numeric( $attr ) )
				) {

					continue;
				}

				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );

				if ( ! is_string( $attr ) ) {
					$html .= " $value";
				} else {
					$html .= " $attr='$value'";
				}
			}

			$html = trim( $html );

			echo "<link $html />\n";
		}
	}
}

/**
 * In chỉ thị tải trước tài nguyên cho trình duyệt.
 *
 * Cung cấp chỉ thị cho trình duyệt để tải trước các tài nguyên cụ thể mà trang web sẽ
 * cần rất sớm, điều này đảm bảo chúng có sẵn sớm hơn và ít
 * có khả năng chặn kết xuất trang. Chỉ thị tải trước không nên sử dụng cho
 * các phần tử không chặn kết xuất, vì khi đó chúng sẽ cạnh tranh với
 * các phần tử chặn kết xuất, làm chậm quá trình kết xuất.
 *
 * Các chỉ báo cải thiện hiệu suất này hoạt động bằng cách sử dụng `<link rel="preload">`.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types/preload
 * @link https://web.dev/preload-responsive-images/
 *
 * @since 6.1.0
 */
function wp_preload_resources() {
	/**
	 * Lọc tên miền và URL cho tải trước tài nguyên.
	 *
	 * @since 6.1.0
	 * @since 6.6.0 Đã thêm thuộc tính `$fetchpriority`.
	 *
	 * @param array  $preload_resources {
	 *     Mảng các tài nguyên và thuộc tính của chúng, hoặc URL để in cho tải trước tài nguyên.
	 *
	 *     @type array ...$0 {
	 *         Mảng các thuộc tính tài nguyên.
	 *
	 *         @type string $href          URL để bao gồm trong tải trước tài nguyên. Bắt buộc.
	 *         @type string $as            Cách trình duyệt nên xử lý tài nguyên
	 *                                     (`script`, `style`, `image`, `document`, v.v.).
	 *         @type string $crossorigin   Chỉ định chính sách CORS của tài nguyên được chỉ định.
	 *         @type string $type          Loại tài nguyên (`text/html`, `text/css`, v.v.).
	 *         @type string $media         Chấp nhận loại media hoặc truy vấn media. Cho phép tải trước responsive.
	 *         @type string $imagesizes    Kích thước nguồn responsive cho tập nguồn.
	 *         @type string $imagesrcset   Nguồn hình ảnh responsive cho tập nguồn.
	 *         @type string $fetchpriority Giá trị fetchpriority cho tài nguyên.
	 *     }
	 * }
	 */
	$preload_resources = apply_filters( 'wp_preload_resources', array() );

	if ( ! is_array( $preload_resources ) ) {
		return;
	}

	$unique_resources = array();

	// Parse the complete resource list and extract unique resources.
	foreach ( $preload_resources as $resource ) {
		if ( ! is_array( $resource ) ) {
			continue;
		}

		$attributes = $resource;
		if ( isset( $resource['href'] ) ) {
			$href = $resource['href'];
			if ( isset( $unique_resources[ $href ] ) ) {
				continue;
			}
			$unique_resources[ $href ] = $attributes;
			// Media can use imagesrcset and not href.
		} elseif ( ( 'image' === $resource['as'] ) &&
			( isset( $resource['imagesrcset'] ) || isset( $resource['imagesizes'] ) )
		) {
			if ( isset( $unique_resources[ $resource['imagesrcset'] ] ) ) {
				continue;
			}
			$unique_resources[ $resource['imagesrcset'] ] = $attributes;
		} else {
			continue;
		}
	}

	// Build and output the HTML for each unique resource.
	foreach ( $unique_resources as $unique_resource ) {
		$html = '';

		foreach ( $unique_resource as $resource_key => $resource_value ) {
			if ( ! is_scalar( $resource_value ) ) {
				continue;
			}

			// Ignore non-supported attributes.
			$non_supported_attributes = array( 'as', 'crossorigin', 'href', 'imagesrcset', 'imagesizes', 'type', 'media', 'fetchpriority' );
			if ( ! in_array( $resource_key, $non_supported_attributes, true ) && ! is_numeric( $resource_key ) ) {
				continue;
			}

			// imagesrcset only usable when preloading image, ignore otherwise.
			if ( ( 'imagesrcset' === $resource_key ) && ( ! isset( $unique_resource['as'] ) || ( 'image' !== $unique_resource['as'] ) ) ) {
				continue;
			}

			// imagesizes only usable when preloading image and imagesrcset present, ignore otherwise.
			if ( ( 'imagesizes' === $resource_key ) &&
				( ! isset( $unique_resource['as'] ) || ( 'image' !== $unique_resource['as'] ) || ! isset( $unique_resource['imagesrcset'] ) )
			) {
				continue;
			}

			$resource_value = ( 'href' === $resource_key ) ? esc_url( $resource_value, array( 'http', 'https' ) ) : esc_attr( $resource_value );

			if ( ! is_string( $resource_key ) ) {
				$html .= " $resource_value";
			} else {
				$html .= " $resource_key='$resource_value'";
			}
		}
		$html = trim( $html );

		printf( "<link rel='preload' %s />\n", $html );
	}
}

/**
 * Retrieves a list of unique hosts of all enqueued scripts and styles.
 *
 * @since 4.6.0
 *
 * @global WP_Scripts $wp_scripts The WP_Scripts object for printing scripts.
 * @global WP_Styles  $wp_styles  The WP_Styles object for printing styles.
 *
 * @return string[] A list of unique hosts of enqueued scripts and styles.
 */
function wp_dependencies_unique_hosts() {
	global $wp_scripts, $wp_styles;

	$unique_hosts = array();

	foreach ( array( $wp_scripts, $wp_styles ) as $dependencies ) {
		if ( $dependencies instanceof WP_Dependencies && ! empty( $dependencies->queue ) ) {
			foreach ( $dependencies->queue as $handle ) {
				if ( ! isset( $dependencies->registered[ $handle ] ) ) {
					continue;
				}

				/* @var _WP_Dependency $dependency */
				$dependency = $dependencies->registered[ $handle ];
				$parsed     = wp_parse_url( $dependency->src );

				if ( ! empty( $parsed['host'] )
					&& ! in_array( $parsed['host'], $unique_hosts, true ) && $parsed['host'] !== $_SERVER['SERVER_NAME']
				) {
					$unique_hosts[] = $parsed['host'];
				}
			}
		}
	}

	return $unique_hosts;
}

/**
 * Determines whether the user can access the visual editor.
 *
 * Checks if the user can access the visual editor and that it's supported by the user's browser.
 *
 * @since 2.0.0
 *
 * @global bool $wp_rich_edit Whether the user can access the visual editor.
 * @global bool $is_gecko     Whether the browser is Gecko-based.
 * @global bool $is_opera     Whether the browser is Opera.
 * @global bool $is_safari    Whether the browser is Safari.
 * @global bool $is_chrome    Whether the browser is Chrome.
 * @global bool $is_IE        Whether the browser is Internet Explorer.
 * @global bool $is_edge      Whether the browser is Microsoft Edge.
 *
 * @return bool True if the user can access the visual editor, false otherwise.
 */
function user_can_richedit() {
	global $wp_rich_edit, $is_gecko, $is_opera, $is_safari, $is_chrome, $is_IE, $is_edge;

	if ( ! isset( $wp_rich_edit ) ) {
		$wp_rich_edit = false;

		if ( 'true' === get_user_option( 'rich_editing' ) || ! is_user_logged_in() ) { // Default to 'true' for logged out users.
			if ( $is_safari ) {
				$wp_rich_edit = ! wp_is_mobile() || ( preg_match( '!AppleWebKit/(\d+)!', $_SERVER['HTTP_USER_AGENT'], $match ) && (int) $match[1] >= 534 );
			} elseif ( $is_IE ) {
				$wp_rich_edit = str_contains( $_SERVER['HTTP_USER_AGENT'], 'Trident/7.0;' );
			} elseif ( $is_gecko || $is_chrome || $is_edge || ( $is_opera && ! wp_is_mobile() ) ) {
				$wp_rich_edit = true;
			}
		}
	}

	/**
	 * Filters whether the user can access the visual editor.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $wp_rich_edit Whether the user can access the visual editor.
	 */
	return apply_filters( 'user_can_richedit', $wp_rich_edit );
}

/**
 * Finds out which editor should be displayed by default.
 *
 * Works out which of the editors to display as the current editor for a
 * user. The 'html' setting is for the "Code" editor tab.
 *
 * @since 2.5.0
 *
 * @return string Either 'tinymce', 'html', or 'test'
 */
function wp_default_editor() {
	$r = user_can_richedit() ? 'tinymce' : 'html'; // Defaults.
	if ( wp_get_current_user() ) { // Look for cookie.
		$ed = get_user_setting( 'editor', 'tinymce' );
		$r  = ( in_array( $ed, array( 'tinymce', 'html', 'test' ), true ) ) ? $ed : $r;
	}

	/**
	 * Filters which editor should be displayed by default.
	 *
	 * @since 2.5.0
	 *
	 * @param string $r Which editor should be displayed by default. Either 'tinymce', 'html', or 'test'.
	 */
	return apply_filters( 'wp_default_editor', $r );
}

/**
 * Renders an editor.
 *
 * Using this function is the proper way to output all needed components for both TinyMCE and Quicktags.
 * _WP_Editors should not be used directly. See https://core.trac.wordpress.org/ticket/17144.
 *
 * NOTE: Once initialized the TinyMCE editor cannot be safely moved in the DOM. For that reason
 * running wp_editor() inside of a meta box is not a good idea unless only Quicktags is used.
 * On the post edit screen several actions can be used to include additional editors
 * containing TinyMCE: 'edit_page_form', 'edit_form_advanced' and 'dbx_post_sidebar'.
 * See https://core.trac.wordpress.org/ticket/19173 for more information.
 *
 * @see _WP_Editors::editor()
 * @see _WP_Editors::parse_settings()
 * @since 3.3.0
 *
 * @param string $content   Initial content for the editor.
 * @param string $editor_id HTML ID attribute value for the textarea and TinyMCE.
 *                          Should not contain square brackets.
 * @param array  $settings  See _WP_Editors::parse_settings() for description.
 */
function wp_editor( $content, $editor_id, $settings = array() ) {
	if ( ! class_exists( '_WP_Editors', false ) ) {
		require ABSPATH . WPINC . '/class-wp-editor.php';
	}
	_WP_Editors::editor( $content, $editor_id, $settings );
}

/**
 * Outputs the editor scripts, stylesheets, and default settings.
 *
 * The editor can be initialized when needed after page load.
 * See wp.editor.initialize() in wp-admin/js/editor.js for initialization options.
 *
 * @uses _WP_Editors
 * @since 4.8.0
 */
function wp_enqueue_editor() {
	if ( ! class_exists( '_WP_Editors', false ) ) {
		require ABSPATH . WPINC . '/class-wp-editor.php';
	}

	_WP_Editors::enqueue_default_editor();
}

/**
 * Enqueues assets needed by the code editor for the given settings.
 *
 * @since 4.9.0
 *
 * @see wp_enqueue_editor()
 * @see wp_get_code_editor_settings();
 * @see _WP_Editors::parse_settings()
 *
 * @param array $args {
 *     Args.
 *
 *     @type string   $type       The MIME type of the file to be edited.
 *     @type string   $file       Filename to be edited. Extension is used to sniff the type. Can be supplied as alternative to `$type` param.
 *     @type WP_Theme $theme      Theme being edited when on the theme file editor.
 *     @type string   $plugin     Plugin being edited when on the plugin file editor.
 *     @type array    $codemirror Additional CodeMirror setting overrides.
 *     @type array    $csslint    CSSLint rule overrides.
 *     @type array    $jshint     JSHint rule overrides.
 *     @type array    $htmlhint   HTMLHint rule overrides.
 * }
 * @return array|false Settings for the enqueued code editor, or false if the editor was not enqueued.
 */
function wp_enqueue_code_editor( $args ) {
	if ( is_user_logged_in() && 'false' === wp_get_current_user()->syntax_highlighting ) {
		return false;
	}

	$settings = wp_get_code_editor_settings( $args );

	if ( empty( $settings ) || empty( $settings['codemirror'] ) ) {
		return false;
	}

	wp_enqueue_script( 'code-editor' );
	wp_enqueue_style( 'code-editor' );

	if ( isset( $settings['codemirror']['mode'] ) ) {
		$mode = $settings['codemirror']['mode'];
		if ( is_string( $mode ) ) {
			$mode = array(
				'name' => $mode,
			);
		}

		if ( ! empty( $settings['codemirror']['lint'] ) ) {
			switch ( $mode['name'] ) {
				case 'css':
				case 'text/css':
				case 'text/x-scss':
				case 'text/x-less':
					wp_enqueue_script( 'csslint' );
					break;
				case 'htmlmixed':
				case 'text/html':
				case 'php':
				case 'application/x-httpd-php':
				case 'text/x-php':
					wp_enqueue_script( 'htmlhint' );
					wp_enqueue_script( 'csslint' );
					wp_enqueue_script( 'jshint' );
					if ( ! current_user_can( 'unfiltered_html' ) ) {
						wp_enqueue_script( 'htmlhint-kses' );
					}
					break;
				case 'javascript':
				case 'application/ecmascript':
				case 'application/json':
				case 'application/javascript':
				case 'application/ld+json':
				case 'text/typescript':
				case 'application/typescript':
					wp_enqueue_script( 'jshint' );
					wp_enqueue_script( 'jsonlint' );
					break;
			}
		}
	}

	wp_add_inline_script( 'code-editor', sprintf( 'jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode( $settings ) ) );

	/**
	 * Fires when scripts and styles are enqueued for the code editor.
	 *
	 * @since 4.9.0
	 *
	 * @param array $settings Settings for the enqueued code editor.
	 */
	do_action( 'wp_enqueue_code_editor', $settings );

	return $settings;
}

/**
 * Generates and returns code editor settings.
 *
 * @since 5.0.0
 *
 * @see wp_enqueue_code_editor()
 *
 * @param array $args {
 *     Args.
 *
 *     @type string   $type       The MIME type of the file to be edited.
 *     @type string   $file       Filename to be edited. Extension is used to sniff the type. Can be supplied as alternative to `$type` param.
 *     @type WP_Theme $theme      Theme being edited when on the theme file editor.
 *     @type string   $plugin     Plugin being edited when on the plugin file editor.
 *     @type array    $codemirror Additional CodeMirror setting overrides.
 *     @type array    $csslint    CSSLint rule overrides.
 *     @type array    $jshint     JSHint rule overrides.
 *     @type array    $htmlhint   HTMLHint rule overrides.
 * }
 * @return array|false Settings for the code editor.
 */
function wp_get_code_editor_settings( $args ) {
	$settings = array(
		'codemirror' => array(
			'indentUnit'       => 4,
			'indentWithTabs'   => true,
			'inputStyle'       => 'contenteditable',
			'lineNumbers'      => true,
			'lineWrapping'     => true,
			'styleActiveLine'  => true,
			'continueComments' => true,
			'extraKeys'        => array(
				'Ctrl-Space' => 'autocomplete',
				'Ctrl-/'     => 'toggleComment',
				'Cmd-/'      => 'toggleComment',
				'Alt-F'      => 'findPersistent',
				'Ctrl-F'     => 'findPersistent',
				'Cmd-F'      => 'findPersistent',
			),
			'direction'        => 'ltr', // Code is shown in LTR even in RTL languages.
			'gutters'          => array(),
		),
		'csslint'    => array(
			'errors'                    => true, // Parsing errors.
			'box-model'                 => true,
			'display-property-grouping' => true,
			'duplicate-properties'      => true,
			'known-properties'          => true,
			'outline-none'              => true,
		),
		'jshint'     => array(
			// The following are copied from <https://github.com/WordPress/wordpress-develop/blob/4.8.1/.jshintrc>.
			'boss'     => true,
			'curly'    => true,
			'eqeqeq'   => true,
			'eqnull'   => true,
			'es3'      => true,
			'expr'     => true,
			'immed'    => true,
			'noarg'    => true,
			'nonbsp'   => true,
			'onevar'   => true,
			'quotmark' => 'single',
			'trailing' => true,
			'undef'    => true,
			'unused'   => true,

			'browser'  => true,

			'globals'  => array(
				'_'        => false,
				'Backbone' => false,
				'jQuery'   => false,
				'JSON'     => false,
				'wp'       => false,
			),
		),
		'htmlhint'   => array(
			'tagname-lowercase'        => true,
			'attr-lowercase'           => true,
			'attr-value-double-quotes' => false,
			'doctype-first'            => false,
			'tag-pair'                 => true,
			'spec-char-escape'         => true,
			'id-unique'                => true,
			'src-not-empty'            => true,
			'attr-no-duplication'      => true,
			'alt-require'              => true,
			'space-tab-mixed-disabled' => 'tab',
			'attr-unsafe-chars'        => true,
		),
	);

	$type = '';
	if ( isset( $args['type'] ) ) {
		$type = $args['type'];

		// Remap MIME types to ones that CodeMirror modes will recognize.
		if ( 'application/x-patch' === $type || 'text/x-patch' === $type ) {
			$type = 'text/x-diff';
		}
	} elseif ( isset( $args['file'] ) && str_contains( basename( $args['file'] ), '.' ) ) {
		$extension = strtolower( pathinfo( $args['file'], PATHINFO_EXTENSION ) );
		foreach ( wp_get_mime_types() as $exts => $mime ) {
			if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
				$type = $mime;
				break;
			}
		}

		// Supply any types that are not matched by wp_get_mime_types().
		if ( empty( $type ) ) {
			switch ( $extension ) {
				case 'conf':
					$type = 'text/nginx';
					break;
				case 'css':
					$type = 'text/css';
					break;
				case 'diff':
				case 'patch':
					$type = 'text/x-diff';
					break;
				case 'html':
				case 'htm':
					$type = 'text/html';
					break;
				case 'http':
					$type = 'message/http';
					break;
				case 'js':
					$type = 'text/javascript';
					break;
				case 'json':
					$type = 'application/json';
					break;
				case 'jsx':
					$type = 'text/jsx';
					break;
				case 'less':
					$type = 'text/x-less';
					break;
				case 'md':
					$type = 'text/x-gfm';
					break;
				case 'php':
				case 'phtml':
				case 'php3':
				case 'php4':
				case 'php5':
				case 'php7':
				case 'phps':
					$type = 'application/x-httpd-php';
					break;
				case 'scss':
					$type = 'text/x-scss';
					break;
				case 'sass':
					$type = 'text/x-sass';
					break;
				case 'sh':
				case 'bash':
					$type = 'text/x-sh';
					break;
				case 'sql':
					$type = 'text/x-sql';
					break;
				case 'svg':
					$type = 'application/svg+xml';
					break;
				case 'xml':
					$type = 'text/xml';
					break;
				case 'yml':
				case 'yaml':
					$type = 'text/x-yaml';
					break;
				case 'txt':
				default:
					$type = 'text/plain';
					break;
			}
		}
	}

	if ( in_array( $type, array( 'text/css', 'text/x-scss', 'text/x-less', 'text/x-sass' ), true ) ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => $type,
				'lint'              => false,
				'autoCloseBrackets' => true,
				'matchBrackets'     => true,
			)
		);
	} elseif ( 'text/x-diff' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode' => 'diff',
			)
		);
	} elseif ( 'text/html' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => 'htmlmixed',
				'lint'              => true,
				'autoCloseBrackets' => true,
				'autoCloseTags'     => true,
				'matchTags'         => array(
					'bothTags' => true,
				),
			)
		);

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$settings['htmlhint']['kses'] = wp_kses_allowed_html( 'post' );
		}
	} elseif ( 'text/x-gfm' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'                => 'gfm',
				'highlightFormatting' => true,
			)
		);
	} elseif ( 'application/javascript' === $type || 'text/javascript' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => 'javascript',
				'lint'              => true,
				'autoCloseBrackets' => true,
				'matchBrackets'     => true,
			)
		);
	} elseif ( str_contains( $type, 'json' ) ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => array(
					'name' => 'javascript',
				),
				'lint'              => true,
				'autoCloseBrackets' => true,
				'matchBrackets'     => true,
			)
		);
		if ( 'application/ld+json' === $type ) {
			$settings['codemirror']['mode']['jsonld'] = true;
		} else {
			$settings['codemirror']['mode']['json'] = true;
		}
	} elseif ( str_contains( $type, 'jsx' ) ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => 'jsx',
				'autoCloseBrackets' => true,
				'matchBrackets'     => true,
			)
		);
	} elseif ( 'text/x-markdown' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'                => 'markdown',
				'highlightFormatting' => true,
			)
		);
	} elseif ( 'text/nginx' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode' => 'nginx',
			)
		);
	} elseif ( 'application/x-httpd-php' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => 'php',
				'autoCloseBrackets' => true,
				'autoCloseTags'     => true,
				'matchBrackets'     => true,
				'matchTags'         => array(
					'bothTags' => true,
				),
			)
		);
	} elseif ( 'text/x-sql' === $type || 'text/x-mysql' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => 'sql',
				'autoCloseBrackets' => true,
				'matchBrackets'     => true,
			)
		);
	} elseif ( str_contains( $type, 'xml' ) ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode'              => 'xml',
				'autoCloseBrackets' => true,
				'autoCloseTags'     => true,
				'matchTags'         => array(
					'bothTags' => true,
				),
			)
		);
	} elseif ( 'text/x-yaml' === $type ) {
		$settings['codemirror'] = array_merge(
			$settings['codemirror'],
			array(
				'mode' => 'yaml',
			)
		);
	} else {
		$settings['codemirror']['mode'] = $type;
	}

	if ( ! empty( $settings['codemirror']['lint'] ) ) {
		$settings['codemirror']['gutters'][] = 'CodeMirror-lint-markers';
	}

	// Let settings supplied via args override any defaults.
	foreach ( wp_array_slice_assoc( $args, array( 'codemirror', 'csslint', 'jshint', 'htmlhint' ) ) as $key => $value ) {
		$settings[ $key ] = array_merge(
			$settings[ $key ],
			$value
		);
	}

	/**
	 * Filters settings that are passed into the code editor.
	 *
	 * Returning a falsey value will disable the syntax-highlighting code editor.
	 *
	 * @since 4.9.0
	 *
	 * @param array $settings The array of settings passed to the code editor.
	 *                        A falsey value disables the editor.
	 * @param array $args {
	 *     Args passed when calling `get_code_editor_settings()`.
	 *
	 *     @type string   $type       The MIME type of the file to be edited.
	 *     @type string   $file       Filename being edited.
	 *     @type WP_Theme $theme      Theme being edited when on the theme file editor.
	 *     @type string   $plugin     Plugin being edited when on the plugin file editor.
	 *     @type array    $codemirror Additional CodeMirror setting overrides.
	 *     @type array    $csslint    CSSLint rule overrides.
	 *     @type array    $jshint     JSHint rule overrides.
	 *     @type array    $htmlhint   HTMLHint rule overrides.
	 * }
	 */
	return apply_filters( 'wp_code_editor_settings', $settings, $args );
}

/**
 * Retrieves the contents of the search WordPress query variable.
 *
 * The search query string is passed through esc_attr() to ensure that it is safe
 * for placing in an HTML attribute.
 *
 * @since 2.3.0
 *
 * @param bool $escaped Whether the result is escaped. Default true.
 *                      Only use when you are later escaping it. Do not use unescaped.
 * @return string
 */
function get_search_query( $escaped = true ) {
	/**
	 * Filters the contents of the search query variable.
	 *
	 * @since 2.3.0
	 *
	 * @param mixed $search Contents of the search query variable.
	 */
	$query = apply_filters( 'get_search_query', get_query_var( 's' ) );

	if ( $escaped ) {
		$query = esc_attr( $query );
	}
	return $query;
}

/**
 * Displays the contents of the search query variable.
 *
 * The search query string is passed through esc_attr() to ensure that it is safe
 * for placing in an HTML attribute.
 *
 * @since 2.1.0
 */
function the_search_query() {
	/**
	 * Filters the contents of the search query variable, for display.
	 *
	 * @since 2.3.0
	 *
	 * @param mixed $search Contents of the search query variable.
	 */
	echo esc_attr( apply_filters( 'the_search_query', get_search_query( false ) ) );
}

/**
 * Gets the language attributes for the 'html' tag.
 *
 * Builds up a set of HTML attributes containing the text direction and language
 * information for the page.
 *
 * @since 4.3.0
 *
 * @param string $doctype Optional. The type of HTML document. Accepts 'xhtml' or 'html'. Default 'html'.
 * @return string A space-separated list of language attributes.
 */
function get_language_attributes( $doctype = 'html' ) {
	$attributes = array();

	if ( function_exists( 'is_rtl' ) && is_rtl() ) {
		$attributes[] = 'dir="rtl"';
	}

	$lang = get_bloginfo( 'language' );
	if ( $lang ) {
		if ( 'text/html' === get_option( 'html_type' ) || 'html' === $doctype ) {
			$attributes[] = 'lang="' . esc_attr( $lang ) . '"';
		}

		if ( 'text/html' !== get_option( 'html_type' ) || 'xhtml' === $doctype ) {
			$attributes[] = 'xml:lang="' . esc_attr( $lang ) . '"';
		}
	}

	$output = implode( ' ', $attributes );

	/**
	 * Filters the language attributes for display in the 'html' tag.
	 *
	 * @since 2.5.0
	 * @since 4.3.0 Added the `$doctype` parameter.
	 *
	 * @param string $output A space-separated list of language attributes.
	 * @param string $doctype The type of HTML document (xhtml|html).
	 */
	return apply_filters( 'language_attributes', $output, $doctype );
}

/**
 * Displays the language attributes for the 'html' tag.
 *
 * Builds up a set of HTML attributes containing the text direction and language
 * information for the page.
 *
 * @since 2.1.0
 * @since 4.3.0 Converted into a wrapper for get_language_attributes().
 *
 * @param string $doctype Optional. The type of HTML document. Accepts 'xhtml' or 'html'. Default 'html'.
 */
function language_attributes( $doctype = 'html' ) {
	echo get_language_attributes( $doctype );
}

/**
 * Retrieves paginated links for archive post pages.
 *
 * Technically, the function can be used to create paginated link list for any
 * area. The 'base' argument is used to reference the url, which will be used to
 * create the paginated links. The 'format' argument is then used for replacing
 * the page number. It is however, most likely and by default, to be used on the
 * archive post pages.
 *
 * The 'type' argument controls format of the returned value. The default is
 * 'plain', which is just a string with the links separated by a newline
 * character. The other possible values are either 'array' or 'list'. The
 * 'array' value will return an array of the paginated link list to offer full
 * control of display. The 'list' value will place all of the paginated links in
 * an unordered HTML list.
 *
 * The 'total' argument is the total amount of pages and is an integer. The
 * 'current' argument is the current page number and is also an integer.
 *
 * An example of the 'base' argument is "http://example.com/all_posts.php%_%"
 * and the '%_%' is required. The '%_%' will be replaced by the contents of in
 * the 'format' argument. An example for the 'format' argument is "?page=%#%"
 * and the '%#%' is also required. The '%#%' will be replaced with the page
 * number.
 *
 * You can include the previous and next links in the list by setting the
 * 'prev_next' argument to true, which it is by default. You can set the
 * previous text, by using the 'prev_text' argument. You can set the next text
 * by setting the 'next_text' argument.
 *
 * If the 'show_all' argument is set to true, then it will show all of the pages
 * instead of a short list of the pages near the current page. By default, the
 * 'show_all' is set to false and controlled by the 'end_size' and 'mid_size'
 * arguments. The 'end_size' argument is how many numbers on either the start
 * and the end list edges, by default is 1. The 'mid_size' argument is how many
 * numbers to either side of current page, but not including current page.
 *
 * It is possible to add query vars to the link by using the 'add_args' argument
 * and see add_query_arg() for more information.
 *
 * The 'before_page_number' and 'after_page_number' arguments allow users to
 * augment the links themselves. Typically this might be to add context to the
 * numbered links so that screen reader users understand what the links are for.
 * The text strings are added before and after the page number - within the
 * anchor tag.
 *
 * @since 2.1.0
 * @since 4.9.0 Added the `aria_current` argument.
 *
 * @global WP_Query   $wp_query   WordPress Query object.
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param string|array $args {
 *     Optional. Array or string of arguments for generating paginated links for archives.
 *
 *     @type string $base               Base of the paginated url. Default empty.
 *     @type string $format             Format for the pagination structure. Default empty.
 *     @type int    $total              The total amount of pages. Default is the value WP_Query's
 *                                      `max_num_pages` or 1.
 *     @type int    $current            The current page number. Default is 'paged' query var or 1.
 *     @type string $aria_current       The value for the aria-current attribute. Possible values are 'page',
 *                                      'step', 'location', 'date', 'time', 'true', 'false'. Default is 'page'.
 *     @type bool   $show_all           Whether to show all pages. Default false.
 *     @type int    $end_size           How many numbers on either the start and the end list edges.
 *                                      Default 1.
 *     @type int    $mid_size           How many numbers to either side of the current pages. Default 2.
 *     @type bool   $prev_next          Whether to include the previous and next links in the list. Default true.
 *     @type string $prev_text          The previous page text. Default '&laquo; Previous'.
 *     @type string $next_text          The next page text. Default 'Next &raquo;'.
 *     @type string $type               Controls format of the returned value. Possible values are 'plain',
 *                                      'array' and 'list'. Default is 'plain'.
 *     @type array  $add_args           An array of query args to add. Default false.
 *     @type string $add_fragment       A string to append to each link. Default empty.
 *     @type string $before_page_number A string to appear before the page number. Default empty.
 *     @type string $after_page_number  A string to append after the page number. Default empty.
 * }
 * @return string|string[]|void String of page links or array of page links, depending on 'type' argument.
 *                              Void if total number of pages is less than 2.
 */
function paginate_links( $args = '' ) {
	global $wp_query, $wp_rewrite;

	// Setting up default values based on the current URL.
	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$url_parts    = explode( '?', $pagenum_link );

	// Get max pages and current page out of the current query, if available.
	$total   = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
	$current = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;

	// Append the format placeholder to the base URL.
	$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

	// URL base depends on permalink settings.
	$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

	$defaults = array(
		'base'               => $pagenum_link, // http://example.com/all_posts.php%_% : %_% is replaced by format (below).
		'format'             => $format, // ?page=%#% : %#% is replaced by the page number.
		'total'              => $total,
		'current'            => $current,
		'aria_current'       => 'page',
		'show_all'           => false,
		'prev_next'          => true,
		'prev_text'          => __( '&laquo; Previous' ),
		'next_text'          => __( 'Next &raquo;' ),
		'end_size'           => 1,
		'mid_size'           => 2,
		'type'               => 'plain',
		'add_args'           => array(), // Array of query args to add.
		'add_fragment'       => '',
		'before_page_number' => '',
		'after_page_number'  => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! is_array( $args['add_args'] ) ) {
		$args['add_args'] = array();
	}

	// Merge additional query vars found in the original URL into 'add_args' array.
	if ( isset( $url_parts[1] ) ) {
		// Find the format argument.
		$format       = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
		$format_query = isset( $format[1] ) ? $format[1] : '';
		wp_parse_str( $format_query, $format_args );

		// Find the query args of the requested URL.
		wp_parse_str( $url_parts[1], $url_query_args );

		// Remove the format argument from the array of query arguments, to avoid overwriting custom format.
		foreach ( $format_args as $format_arg => $format_arg_value ) {
			unset( $url_query_args[ $format_arg ] );
		}

		$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
	}

	// Who knows what else people pass in $args.
	$total = (int) $args['total'];
	if ( $total < 2 ) {
		return;
	}
	$current  = (int) $args['current'];
	$end_size = (int) $args['end_size']; // Out of bounds? Make it the default.
	if ( $end_size < 1 ) {
		$end_size = 1;
	}
	$mid_size = (int) $args['mid_size'];
	if ( $mid_size < 0 ) {
		$mid_size = 2;
	}

	$add_args   = $args['add_args'];
	$r          = '';
	$page_links = array();
	$dots       = false;

	if ( $args['prev_next'] && $current && 1 < $current ) :
		$link = str_replace( '%_%', 2 === $current ? '' : $args['format'], $args['base'] );
		$link = str_replace( '%#%', $current - 1, $link );
		if ( $add_args ) {
			$link = add_query_arg( $add_args, $link );
		}
		$link .= $args['add_fragment'];

		$page_links[] = sprintf(
			'<a class="prev page-numbers" href="%s">%s</a>',
			/**
			 * Filters the paginated links for the given archive pages.
			 *
			 * @since 3.0.0
			 *
			 * @param string $link The paginated link URL.
			 */
			esc_url( apply_filters( 'paginate_links', $link ) ),
			$args['prev_text']
		);
	endif;

	for ( $n = 1; $n <= $total; $n++ ) :
		if ( $n === $current ) :
			$page_links[] = sprintf(
				'<span aria-current="%s" class="page-numbers current">%s</span>',
				esc_attr( $args['aria_current'] ),
				$args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']
			);

			$dots = true;
		else :
			if ( $args['show_all'] || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
				$link = str_replace( '%_%', 1 === $n ? '' : $args['format'], $args['base'] );
				$link = str_replace( '%#%', $n, $link );
				if ( $add_args ) {
					$link = add_query_arg( $add_args, $link );
				}
				$link .= $args['add_fragment'];

				$page_links[] = sprintf(
					'<a class="page-numbers" href="%s">%s</a>',
					/** This filter is documented in wp-includes/general-template.php */
					esc_url( apply_filters( 'paginate_links', $link ) ),
					$args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']
				);

				$dots = true;
			elseif ( $dots && ! $args['show_all'] ) :
				$page_links[] = '<span class="page-numbers dots">' . __( '&hellip;' ) . '</span>';

				$dots = false;
			endif;
		endif;
	endfor;

	if ( $args['prev_next'] && $current && $current < $total ) :
		$link = str_replace( '%_%', $args['format'], $args['base'] );
		$link = str_replace( '%#%', $current + 1, $link );
		if ( $add_args ) {
			$link = add_query_arg( $add_args, $link );
		}
		$link .= $args['add_fragment'];

		$page_links[] = sprintf(
			'<a class="next page-numbers" href="%s">%s</a>',
			/** This filter is documented in wp-includes/general-template.php */
			esc_url( apply_filters( 'paginate_links', $link ) ),
			$args['next_text']
		);
	endif;

	switch ( $args['type'] ) {
		case 'array':
			return $page_links;

		case 'list':
			$r .= "<ul class='page-numbers'>\n\t<li>";
			$r .= implode( "</li>\n\t<li>", $page_links );
			$r .= "</li>\n</ul>\n";
			break;

		default:
			$r = implode( "\n", $page_links );
			break;
	}

	/**
	 * Filters the HTML output of paginated links for archives.
	 *
	 * @since 5.7.0
	 *
	 * @param string $r    HTML output.
	 * @param array  $args An array of arguments. See paginate_links()
	 *                     for information on accepted arguments.
	 */
	$r = apply_filters( 'paginate_links_output', $r, $args );

	return $r;
}

/**
 * Registers an admin color scheme css file.
 *
 * Allows a plugin to register a new admin color scheme. For example:
 *
 *     wp_admin_css_color( 'classic', __( 'Classic' ), admin_url( "css/colors-classic.css" ), array(
 *         '#07273E', '#14568A', '#D54E21', '#2683AE'
 *     ) );
 *
 * @since 2.5.0
 *
 * @global array $_wp_admin_css_colors
 *
 * @param string $key    The unique key for this theme.
 * @param string $name   The name of the theme.
 * @param string $url    The URL of the CSS file containing the color scheme.
 * @param array  $colors Optional. An array of CSS color definition strings which are used
 *                       to give the user a feel for the theme.
 * @param array  $icons {
 *     Optional. CSS color definitions used to color any SVG icons.
 *
 *     @type string $base    SVG icon base color.
 *     @type string $focus   SVG icon color on focus.
 *     @type string $current SVG icon color of current admin menu link.
 * }
 */
function wp_admin_css_color( $key, $name, $url, $colors = array(), $icons = array() ) {
	global $_wp_admin_css_colors;

	if ( ! isset( $_wp_admin_css_colors ) ) {
		$_wp_admin_css_colors = array();
	}

	$_wp_admin_css_colors[ $key ] = (object) array(
		'name'        => $name,
		'url'         => $url,
		'colors'      => $colors,
		'icon_colors' => $icons,
	);
}

/**
 * Registers the default admin color schemes.
 *
 * Registers the initial set of eight color schemes in the Profile section
 * of the dashboard which allows for styling the admin menu and toolbar.
 *
 * @see wp_admin_css_color()
 *
 * @since 3.0.0
 */
function register_admin_color_schemes() {
	$suffix  = is_rtl() ? '-rtl' : '';
	$suffix .= SCRIPT_DEBUG ? '' : '.min';

	wp_admin_css_color(
		'fresh',
		_x( 'Default', 'admin color scheme' ),
		false,
		array( '#1d2327', '#2c3338', '#2271b1', '#72aee6' ),
		array(
			'base'    => '#a7aaad',
			'focus'   => '#72aee6',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'light',
		_x( 'Light', 'admin color scheme' ),
		admin_url( "css/colors/light/colors$suffix.css" ),
		array( '#e5e5e5', '#999', '#d64e07', '#04a4cc' ),
		array(
			'base'    => '#999',
			'focus'   => '#ccc',
			'current' => '#ccc',
		)
	);

	wp_admin_css_color(
		'modern',
		_x( 'Modern', 'admin color scheme' ),
		admin_url( "css/colors/modern/colors$suffix.css" ),
		array( '#1e1e1e', '#3858e9', '#7b90ff' ),
		array(
			'base'    => '#f3f1f1',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'blue',
		_x( 'Blue', 'admin color scheme' ),
		admin_url( "css/colors/blue/colors$suffix.css" ),
		array( '#096484', '#4796b3', '#52accc', '#74B6CE' ),
		array(
			'base'    => '#e5f8ff',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'midnight',
		_x( 'Midnight', 'admin color scheme' ),
		admin_url( "css/colors/midnight/colors$suffix.css" ),
		array( '#25282b', '#363b3f', '#69a8bb', '#e14d43' ),
		array(
			'base'    => '#f1f2f3',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'sunrise',
		_x( 'Sunrise', 'admin color scheme' ),
		admin_url( "css/colors/sunrise/colors$suffix.css" ),
		array( '#b43c38', '#cf4944', '#dd823b', '#ccaf0b' ),
		array(
			'base'    => '#f3f1f1',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'ectoplasm',
		_x( 'Ectoplasm', 'admin color scheme' ),
		admin_url( "css/colors/ectoplasm/colors$suffix.css" ),
		array( '#413256', '#523f6d', '#a3b745', '#d46f15' ),
		array(
			'base'    => '#ece6f6',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'ocean',
		_x( 'Ocean', 'admin color scheme' ),
		admin_url( "css/colors/ocean/colors$suffix.css" ),
		array( '#627c83', '#738e96', '#9ebaa0', '#aa9d88' ),
		array(
			'base'    => '#f2fcff',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);

	wp_admin_css_color(
		'coffee',
		_x( 'Coffee', 'admin color scheme' ),
		admin_url( "css/colors/coffee/colors$suffix.css" ),
		array( '#46403c', '#59524c', '#c7a589', '#9ea476' ),
		array(
			'base'    => '#f3f2f1',
			'focus'   => '#fff',
			'current' => '#fff',
		)
	);
}

/**
 * Displays the URL of a WordPress admin CSS file.
 *
 * @see WP_Styles::_css_href() and its {@see 'style_loader_src'} filter.
 *
 * @since 2.3.0
 *
 * @param string $file file relative to wp-admin/ without its ".css" extension.
 * @return string
 */
function wp_admin_css_uri( $file = 'wp-admin' ) {
	if ( defined( 'WP_INSTALLING' ) ) {
		$_file = "./$file.css";
	} else {
		$_file = admin_url( "$file.css" );
	}
	$_file = add_query_arg( 'version', get_bloginfo( 'version' ), $_file );

	/**
	 * Filters the URI of a WordPress admin CSS file.
	 *
	 * @since 2.3.0
	 *
	 * @param string $_file Relative path to the file with query arguments attached.
	 * @param string $file  Relative path to the file, minus its ".css" extension.
	 */
	return apply_filters( 'wp_admin_css_uri', $_file, $file );
}

/**
 * Enqueues or directly prints a stylesheet link to the specified CSS file.
 *
 * "Intelligently" decides to enqueue or to print the CSS file. If the
 * {@see 'wp_print_styles'} action has *not* yet been called, the CSS file will be
 * enqueued. If the {@see 'wp_print_styles'} action has been called, the CSS link will
 * be printed. Printing may be forced by passing true as the $force_echo
 * (second) parameter.
 *
 * For backward compatibility with WordPress 2.3 calling method: If the $file
 * (first) parameter does not correspond to a registered CSS file, we assume
 * $file is a file relative to wp-admin/ without its ".css" extension. A
 * stylesheet link to that generated URL is printed.
 *
 * @since 2.3.0
 *
 * @param string $file       Optional. Style handle name or file name (without ".css" extension) relative
 *                           to wp-admin/. Defaults to 'wp-admin'.
 * @param bool   $force_echo Optional. Force the stylesheet link to be printed rather than enqueued.
 */
function wp_admin_css( $file = 'wp-admin', $force_echo = false ) {
	// For backward compatibility.
	$handle = str_starts_with( $file, 'css/' ) ? substr( $file, 4 ) : $file;

	if ( wp_styles()->query( $handle ) ) {
		if ( $force_echo || did_action( 'wp_print_styles' ) ) {
			// We already printed the style queue. Print this one immediately.
			wp_print_styles( $handle );
		} else {
			// Add to style queue.
			wp_enqueue_style( $handle );
		}
		return;
	}

	$stylesheet_link = sprintf(
		"<link rel='stylesheet' href='%s' type='text/css' />\n",
		esc_url( wp_admin_css_uri( $file ) )
	);

	/**
	 * Filters the stylesheet link to the specified CSS file.
	 *
	 * If the site is set to display right-to-left, the RTL stylesheet link
	 * will be used instead.
	 *
	 * @since 2.3.0
	 * @param string $stylesheet_link HTML link element for the stylesheet.
	 * @param string $file            Style handle name or filename (without ".css" extension)
	 *                                relative to wp-admin/. Defaults to 'wp-admin'.
	 */
	echo apply_filters( 'wp_admin_css', $stylesheet_link, $file );

	if ( function_exists( 'is_rtl' ) && is_rtl() ) {
		$rtl_stylesheet_link = sprintf(
			"<link rel='stylesheet' href='%s' type='text/css' />\n",
			esc_url( wp_admin_css_uri( "$file-rtl" ) )
		);

		/** This filter is documented in wp-includes/general-template.php */
		echo apply_filters( 'wp_admin_css', $rtl_stylesheet_link, "$file-rtl" );
	}
}

/**
 * Enqueues the default ThickBox js and css.
 *
 * If any of the settings need to be changed, this can be done with another js
 * file similar to media-upload.js. That file should
 * require array('thickbox') to ensure it is loaded after.
 *
 * @since 2.5.0
 */
function add_thickbox() {
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );

	if ( is_network_admin() ) {
		add_action( 'admin_head', '_thickbox_path_admin_subfolder' );
	}
}

/**
 * Displays the XHTML generator that is generated on the wp_head hook.
 *
 * See {@see 'wp_head'}.
 *
 * @since 2.5.0
 */
function wp_generator() {
	/**
	 * Filters the output of the XHTML generator tag.
	 *
	 * @since 2.5.0
	 *
	 * @param string $generator_type The XHTML generator.
	 */
	the_generator( apply_filters( 'wp_generator_type', 'xhtml' ) );
}

/**
 * Displays the generator XML or Comment for RSS, ATOM, etc.
 *
 * Returns the correct generator type for the requested output format. Allows
 * for a plugin to filter generators overall the {@see 'the_generator'} filter.
 *
 * @since 2.5.0
 *
 * @param string $type The type of generator to output - (html|xhtml|atom|rss2|rdf|comment|export).
 */
function the_generator( $type ) {
	/**
	 * Filters the output of the XHTML generator tag, for display.
	 *
	 * @since 2.5.0
	 *
	 * @param string $generator_type The generator output.
	 * @param string $type           The type of generator to output. Accepts 'html',
	 *                               'xhtml', 'atom', 'rss2', 'rdf', 'comment', 'export'.
	 */
	echo apply_filters( 'the_generator', get_the_generator( $type ), $type ) . "\n";
}

/**
 * Creates the generator XML or Comment for RSS, ATOM, etc.
 *
 * Returns the correct generator type for the requested output format. Allows
 * for a plugin to filter generators on an individual basis using the
 * {@see 'get_the_generator_$type'} filter.
 *
 * @since 2.5.0
 *
 * @param string $type The type of generator to return - (html|xhtml|atom|rss2|rdf|comment|export).
 * @return string|void The HTML content for the generator.
 */
function get_the_generator( $type = '' ) {
	if ( empty( $type ) ) {

		$current_filter = current_filter();
		if ( empty( $current_filter ) ) {
			return;
		}

		switch ( $current_filter ) {
			case 'rss2_head':
			case 'commentsrss2_head':
				$type = 'rss2';
				break;
			case 'rss_head':
			case 'opml_head':
				$type = 'comment';
				break;
			case 'rdf_header':
				$type = 'rdf';
				break;
			case 'atom_head':
			case 'comments_atom_head':
			case 'app_head':
				$type = 'atom';
				break;
		}
	}

	switch ( $type ) {
		case 'html':
			$gen = '<meta name="generator" content="WordPress ' . esc_attr( get_bloginfo( 'version' ) ) . '">';
			break;
		case 'xhtml':
			$gen = '<meta name="generator" content="WordPress ' . esc_attr( get_bloginfo( 'version' ) ) . '" />';
			break;
		case 'atom':
			$gen = '<generator uri="https://wordpress.org/" version="' . esc_attr( get_bloginfo_rss( 'version' ) ) . '">WordPress</generator>';
			break;
		case 'rss2':
			$gen = '<generator>' . sanitize_url( 'https://wordpress.org/?v=' . get_bloginfo_rss( 'version' ) ) . '</generator>';
			break;
		case 'rdf':
			$gen = '<admin:generatorAgent rdf:resource="' . sanitize_url( 'https://wordpress.org/?v=' . get_bloginfo_rss( 'version' ) ) . '" />';
			break;
		case 'comment':
			$gen = '<!-- generator="WordPress/' . esc_attr( get_bloginfo( 'version' ) ) . '" -->';
			break;
		case 'export':
			$gen = '<!-- generator="WordPress/' . esc_attr( get_bloginfo_rss( 'version' ) ) . '" created="' . gmdate( 'Y-m-d H:i' ) . '" -->';
			break;
	}

	/**
	 * Filters the HTML for the retrieved generator type.
	 *
	 * The dynamic portion of the hook name, `$type`, refers to the generator type.
	 *
	 * Possible hook names include:
	 *
	 *  - `get_the_generator_atom`
	 *  - `get_the_generator_comment`
	 *  - `get_the_generator_export`
	 *  - `get_the_generator_html`
	 *  - `get_the_generator_rdf`
	 *  - `get_the_generator_rss2`
	 *  - `get_the_generator_xhtml`
	 *
	 * @since 2.5.0
	 *
	 * @param string $gen  The HTML markup output to wp_head().
	 * @param string $type The type of generator. Accepts 'html', 'xhtml', 'atom',
	 *                     'rss2', 'rdf', 'comment', 'export'.
	 */
	return apply_filters( "get_the_generator_{$type}", $gen, $type );
}

/**
 * Outputs the HTML checked attribute.
 *
 * Compares the first two arguments and if identical marks as checked.
 *
 * @since 1.0.0
 *
 * @param mixed $checked One of the values to compare.
 * @param mixed $current Optional. The other value to compare if not just true.
 *                       Default true.
 * @param bool  $display Optional. Whether to echo or just return the string.
 *                       Default true.
 * @return string HTML attribute or empty string.
 */
function checked( $checked, $current = true, $display = true ) {
	return __checked_selected_helper( $checked, $current, $display, 'checked' );
}

/**
 * Outputs the HTML selected attribute.
 *
 * Compares the first two arguments and if identical marks as selected.
 *
 * @since 1.0.0
 *
 * @param mixed $selected One of the values to compare.
 * @param mixed $current  Optional. The other value to compare if not just true.
 *                        Default true.
 * @param bool  $display  Optional. Whether to echo or just return the string.
 *                        Default true.
 * @return string HTML attribute or empty string.
 */
function selected( $selected, $current = true, $display = true ) {
	return __checked_selected_helper( $selected, $current, $display, 'selected' );
}

/**
 * Outputs the HTML disabled attribute.
 *
 * Compares the first two arguments and if identical marks as disabled.
 *
 * @since 3.0.0
 *
 * @param mixed $disabled One of the values to compare.
 * @param mixed $current  Optional. The other value to compare if not just true.
 *                        Default true.
 * @param bool  $display  Optional. Whether to echo or just return the string.
 *                        Default true.
 * @return string HTML attribute or empty string.
 */
function disabled( $disabled, $current = true, $display = true ) {
	return __checked_selected_helper( $disabled, $current, $display, 'disabled' );
}

/**
 * Outputs the HTML readonly attribute.
 *
 * Compares the first two arguments and if identical marks as readonly.
 *
 * @since 5.9.0
 *
 * @param mixed $readonly_value One of the values to compare.
 * @param mixed $current        Optional. The other value to compare if not just true.
 *                              Default true.
 * @param bool  $display        Optional. Whether to echo or just return the string.
 *                              Default true.
 * @return string HTML attribute or empty string.
 */
function wp_readonly( $readonly_value, $current = true, $display = true ) {
	return __checked_selected_helper( $readonly_value, $current, $display, 'readonly' );
}

/*
 * Include a compat `readonly()` function on PHP < 8.1. Since PHP 8.1,
 * `readonly` is a reserved keyword and cannot be used as a function name.
 * In order to avoid PHP parser errors, this function was extracted
 * to a separate file and is only included conditionally on PHP < 8.1.
 */
if ( PHP_VERSION_ID < 80100 ) {
	require_once __DIR__ . '/php-compat/readonly.php';
}

/**
 * Private helper function for checked, selected, disabled and readonly.
 *
 * Compares the first two arguments and if identical marks as `$type`.
 *
 * @since 2.8.0
 * @access private
 *
 * @param mixed  $helper  One of the values to compare.
 * @param mixed  $current The other value to compare if not just true.
 * @param bool   $display Whether to echo or just return the string.
 * @param string $type    The type of checked|selected|disabled|readonly we are doing.
 * @return string HTML attribute or empty string.
 */
function __checked_selected_helper( $helper, $current, $display, $type ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	if ( (string) $helper === (string) $current ) {
		$result = " $type='$type'";
	} else {
		$result = '';
	}

	if ( $display ) {
		echo $result;
	}

	return $result;
}

/**
 * Assigns a visual indicator for required form fields.
 *
 * @since 6.1.0
 *
 * @return string Indicator glyph wrapped in a `span` tag.
 */
function wp_required_field_indicator() {
	/* translators: Character to identify required form fields. */
	$glyph     = __( '*' );
	$indicator = '<span class="required">' . esc_html( $glyph ) . '</span>';

	/**
	 * Filters the markup for a visual indicator of required form fields.
	 *
	 * @since 6.1.0
	 *
	 * @param string $indicator Markup for the indicator element.
	 */
	return apply_filters( 'wp_required_field_indicator', $indicator );
}

/**
 * Creates a message to explain required form fields.
 *
 * @since 6.1.0
 *
 * @return string Message text and glyph wrapped in a `span` tag.
 */
function wp_required_field_message() {
	$message = sprintf(
		'<span class="required-field-message">%s</span>',
		/* translators: %s: Asterisk symbol (*). */
		sprintf( __( 'Required fields are marked %s' ), wp_required_field_indicator() )
	);

	/**
	 * Filters the message to explain required form fields.
	 *
	 * @since 6.1.0
	 *
	 * @param string $message Message text and glyph wrapped in a `span` tag.
	 */
	return apply_filters( 'wp_required_field_message', $message );
}

/**
 * Default settings for heartbeat.
 *
 * Outputs the nonce used in the heartbeat XHR.
 *
 * @since 3.6.0
 *
 * @param array $settings
 * @return array Heartbeat settings.
 */
function wp_heartbeat_settings( $settings ) {
	if ( ! is_admin() ) {
		$settings['ajaxurl'] = admin_url( 'admin-ajax.php', 'relative' );
	}

	if ( is_user_logged_in() ) {
		$settings['nonce'] = wp_create_nonce( 'heartbeat-nonce' );
	}

	return $settings;
}
