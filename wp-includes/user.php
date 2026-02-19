<?php
/**
 * API Người dùng Cốt lõi
 *
 * @package WordPress
 * @subpackage Users
 */

/**
 * Xác thực và đăng nhập người dùng với khả năng 'remember' (ghi nhớ).
 *
 * Thông tin đăng nhập là một mảng có các chỉ mục 'user_login', 'user_password', và
 * 'remember'. Nếu thông tin đăng nhập không được cung cấp, thì form đăng nhập
 * sẽ được giả định và sử dụng nếu đã được thiết lập.
 *
 * Các cookie xác thực khác nhau sẽ được thiết lập bởi hàm này và sẽ được
 * thiết lập trong thời gian dài hơn tùy thuộc vào việc thông tin 'remember' có được
 * đặt thành true hay không.
 *
 * Lưu ý: wp_signon() không xử lý việc thiết lập người dùng hiện tại. Điều này có nghĩa là nếu
 * hàm được gọi trước khi hook {@see 'init'} được kích hoạt, is_user_logged_in() sẽ
 * trả về false cho đến thời điểm đó. Nếu cần is_user_logged_in() kết hợp
 * với wp_signon(), wp_set_current_user() nên được gọi một cách tường minh.
 *
 * @since 2.5.0
 *
 * @global string $auth_secure_cookie
 * @global wpdb   $wpdb               Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array       $credentials {
 *     Tùy chọn. Thông tin người dùng để đăng nhập.
 *
 *     @type string $user_login    Tên đăng nhập.
 *     @type string $user_password Mật khẩu người dùng.
 *     @type bool   $remember      Có 'ghi nhớ' người dùng hay không. Tăng thời gian
 *                                 cookie được giữ lại. Mặc định false.
 * }
 * @param string|bool $secure_cookie Tùy chọn. Có sử dụng cookie bảo mật hay không.
 * @return WP_User|WP_Error WP_User khi thành công, WP_Error khi thất bại.
 */
function wp_signon( $credentials = array(), $secure_cookie = '' ) {
	global $auth_secure_cookie, $wpdb;

	if ( empty( $credentials ) ) {
		$credentials = array(
			'user_login'    => '',
			'user_password' => '',
			'remember'      => false,
		);

		if ( ! empty( $_POST['log'] ) && is_string( $_POST['log'] ) ) {
			$credentials['user_login'] = wp_unslash( $_POST['log'] );
		}
		if ( ! empty( $_POST['pwd'] ) && is_string( $_POST['pwd'] ) ) {
			$credentials['user_password'] = $_POST['pwd'];
		}
		if ( ! empty( $_POST['rememberme'] ) ) {
			$credentials['remember'] = $_POST['rememberme'];
		}
	}

	if ( ! empty( $credentials['remember'] ) ) {
		$credentials['remember'] = true;
	} else {
		$credentials['remember'] = false;
	}

	/**
	 * Kích hoạt trước khi người dùng được xác thực.
	 *
	 * Các biến được truyền vào callback được truyền bằng tham chiếu,
	 * và có thể được sửa đổi bởi các hàm callback.
	 *
	 * @since 1.5.1
	 *
	 * @todo Quyết định có nên đánh dấu lỗi thời action wp_authenticate hay không.
	 *
	 * @param string $user_login    Tên đăng nhập (truyền bằng tham chiếu).
	 * @param string $user_password Mật khẩu người dùng (truyền bằng tham chiếu).
	 */
	do_action_ref_array( 'wp_authenticate', array( &$credentials['user_login'], &$credentials['user_password'] ) );

	if ( '' === $secure_cookie ) {
		$secure_cookie = is_ssl();
	}

	/**
	 * Lọc xem có sử dụng cookie đăng nhập bảo mật hay không.
	 *
	 * @since 3.1.0
	 *
	 * @param bool  $secure_cookie Có sử dụng cookie đăng nhập bảo mật hay không.
	 * @param array $credentials {
	 *     Mảng dữ liệu đăng nhập đã nhập.
	 *
	 *     @type string $user_login    Tên đăng nhập.
	 *     @type string $user_password Mật khẩu đã nhập.
	 *     @type bool   $remember      Có 'ghi nhớ' người dùng hay không. Tăng thời gian
	 *                                 cookie được giữ lại. Mặc định false.
	 * }
	 */
	$secure_cookie = apply_filters( 'secure_signon_cookie', $secure_cookie, $credentials );

	// XXX hack xấu để truyền giá trị này vào wp_authenticate_cookie().
	$auth_secure_cookie = $secure_cookie;

	add_filter( 'authenticate', 'wp_authenticate_cookie', 30, 3 );

	$user = wp_authenticate( $credentials['user_login'], $credentials['user_password'] );

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	wp_set_auth_cookie( $user->ID, $credentials['remember'], $secure_cookie );

	// Xóa `user_activation_key` sau khi đăng nhập thành công.
	if ( ! empty( $user->user_activation_key ) ) {
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => '',
			),
			array( 'ID' => $user->ID )
		);

		$user->user_activation_key = '';
	}

	/**
	 * Kích hoạt sau khi người dùng đã đăng nhập thành công.
	 *
	 * @since 1.5.0
	 *
	 * @param string  $user_login Tên đăng nhập.
	 * @param WP_User $user       Đối tượng WP_User của người dùng đã đăng nhập.
	 */
	do_action( 'wp_login', $user->user_login, $user );

	return $user;
}

/**
 * Xác thực người dùng, xác nhận tên đăng nhập và mật khẩu là hợp lệ.
 *
 * @since 2.8.0
 *
 * @param WP_User|WP_Error|null $user     Đối tượng WP_User hoặc WP_Error từ callback trước đó. Mặc định null.
 * @param string                $username Tên đăng nhập để xác thực.
 * @param string                $password Mật khẩu để xác thực.
 * @return WP_User|WP_Error WP_User khi thành công, WP_Error khi thất bại.
 */
function wp_authenticate_username_password(
	$user,
	$username,
	#[\SensitiveParameter]
	$password
) {
	if ( $user instanceof WP_User ) {
		return $user;
	}

	if ( empty( $username ) || empty( $password ) ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$error = new WP_Error();

		if ( empty( $username ) ) {
			$error->add( 'empty_username', __( '<strong>Error:</strong> The username field is empty.' ) );
		}

		if ( empty( $password ) ) {
			$error->add( 'empty_password', __( '<strong>Error:</strong> The password field is empty.' ) );
		}

		return $error;
	}

	$user = get_user_by( 'login', $username );

	if ( ! $user ) {
		return new WP_Error(
			'invalid_username',
			sprintf(
				/* translators: %s: User name. */
				__( '<strong>Error:</strong> The username <strong>%s</strong> is not registered on this site. If you are unsure of your username, try your email address instead.' ),
				$username
			)
		);
	}

	/**
	 * Lọc xem người dùng có thể được xác thực với mật khẩu đã cung cấp hay không.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_User|WP_Error $user     Đối tượng WP_User hoặc WP_Error nếu callback
	 *                                   trước đó xác thực thất bại.
	 * @param string           $password Mật khẩu để kiểm tra với người dùng.
	 */
	$user = apply_filters( 'wp_authenticate_user', $user, $password );
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$valid = wp_check_password( $password, $user->user_pass, $user->ID );

	if ( ! $valid ) {
		return new WP_Error(
			'incorrect_password',
			sprintf(
				/* translators: %s: User name. */
				__( '<strong>Error:</strong> The password you entered for the username %s is incorrect.' ),
				'<strong>' . $username . '</strong>'
			) .
			' <a href="' . wp_lostpassword_url() . '">' .
			__( 'Lost your password?' ) .
			'</a>'
		);
	}

	if ( wp_password_needs_rehash( $user->user_pass, $user->ID ) ) {
		wp_set_password( $password, $user->ID );
	}

	return $user;
}

/**
 * Xác thực người dùng sử dụng email và mật khẩu.
 *
 * @since 4.5.0
 *
 * @param WP_User|WP_Error|null $user     Đối tượng WP_User hoặc WP_Error nếu callback
 *                                        trước đó xác thực thất bại.
 * @param string                $email    Địa chỉ email để xác thực.
 * @param string                $password Mật khẩu để xác thực.
 * @return WP_User|WP_Error WP_User khi thành công, WP_Error khi thất bại.
 */
function wp_authenticate_email_password(
	$user,
	$email,
	#[\SensitiveParameter]
	$password
) {
	if ( $user instanceof WP_User ) {
		return $user;
	}

	if ( empty( $email ) || empty( $password ) ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$error = new WP_Error();

		if ( empty( $email ) ) {
			// Sử dụng 'empty_username' để tương thích ngược với wp_signon().
			$error->add( 'empty_username', __( '<strong>Error:</strong> The email field is empty.' ) );
		}

		if ( empty( $password ) ) {
			$error->add( 'empty_password', __( '<strong>Error:</strong> The password field is empty.' ) );
		}

		return $error;
	}

	if ( ! is_email( $email ) ) {
		return $user;
	}

	$user = get_user_by( 'email', $email );

	if ( ! $user ) {
		return new WP_Error(
			'invalid_email',
			__( 'Unknown email address. Check again or try your username.' )
		);
	}

	/** Bộ lọc này được ghi chú trong wp-includes/user.php */
	$user = apply_filters( 'wp_authenticate_user', $user, $password );

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	$valid = wp_check_password( $password, $user->user_pass, $user->ID );

	if ( ! $valid ) {
		return new WP_Error(
			'incorrect_password',
			sprintf(
				/* translators: %s: Email address. */
				__( '<strong>Error:</strong> The password you entered for the email address %s is incorrect.' ),
				'<strong>' . $email . '</strong>'
			) .
			' <a href="' . wp_lostpassword_url() . '">' .
			__( 'Lost your password?' ) .
			'</a>'
		);
	}

	if ( wp_password_needs_rehash( $user->user_pass, $user->ID ) ) {
		wp_set_password( $password, $user->ID );
	}

	return $user;
}

/**
 * Xác thực người dùng sử dụng cookie xác thực WordPress.
 *
 * @since 2.8.0
 *
 * @global string $auth_secure_cookie
 *
 * @param WP_User|WP_Error|null $user     Đối tượng WP_User hoặc WP_Error từ callback trước đó. Mặc định null.
 * @param string                $username Tên đăng nhập. Nếu không rỗng, hủy xác thực bằng cookie.
 * @param string                $password Mật khẩu. Nếu không rỗng, hủy xác thực bằng cookie.
 * @return WP_User|WP_Error WP_User khi thành công, WP_Error khi thất bại.
 */
function wp_authenticate_cookie(
	$user,
	$username,
	#[\SensitiveParameter]
	$password
) {
	global $auth_secure_cookie;

	if ( $user instanceof WP_User ) {
		return $user;
	}

	if ( empty( $username ) && empty( $password ) ) {
		$user_id = wp_validate_auth_cookie();
		if ( $user_id ) {
			return new WP_User( $user_id );
		}

		if ( $auth_secure_cookie ) {
			$auth_cookie = SECURE_AUTH_COOKIE;
		} else {
			$auth_cookie = AUTH_COOKIE;
		}

		if ( ! empty( $_COOKIE[ $auth_cookie ] ) ) {
			return new WP_Error( 'expired_session', __( 'Please log in again.' ) );
		}

		// Nếu cookie chưa được thiết lập, im lặng.
	}

	return $user;
}

/**
 * Xác thực người dùng sử dụng mật khẩu ứng dụng.
 *
 * @since 5.6.0
 *
 * @param WP_User|WP_Error|null $input_user Đối tượng WP_User hoặc WP_Error nếu callback
 *                                          trước đó xác thực thất bại.
 * @param string                $username   Tên đăng nhập để xác thực.
 * @param string                $password   Mật khẩu để xác thực.
 * @return WP_User|WP_Error|null WP_User khi thành công, WP_Error khi thất bại, null nếu
 *                               null được truyền vào và đây không phải là yêu cầu API.
 */
function wp_authenticate_application_password(
	$input_user,
	$username,
	#[\SensitiveParameter]
	$password
) {
	if ( $input_user instanceof WP_User ) {
		return $input_user;
	}

	if ( ! WP_Application_Passwords::is_in_use() ) {
		return $input_user;
	}

	// Việc kiểm tra 'REST_REQUEST' ở đây có thể xảy ra quá sớm để hằng số khả dụng.
	$is_api_request = ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );

	/**
	 * Lọc xem đây có phải là yêu cầu API mà Mật khẩu Ứng dụng có thể được sử dụng hay không.
	 *
	 * Theo mặc định, Mật khẩu Ứng dụng khả dụng cho REST API và XML-RPC.
	 *
	 * @since 5.6.0
	 *
	 * @param bool $is_api_request Nếu đây là yêu cầu API được chấp nhận.
	 */
	$is_api_request = apply_filters( 'application_password_is_api_request', $is_api_request );

	if ( ! $is_api_request ) {
		return $input_user;
	}

	$error = null;
	$user  = get_user_by( 'login', $username );

	if ( ! $user && is_email( $username ) ) {
		$user = get_user_by( 'email', $username );
	}

	// Nếu tên đăng nhập không hợp lệ, dừng ngay.
	if ( ! $user ) {
		if ( is_email( $username ) ) {
			$error = new WP_Error(
				'invalid_email',
				__( '<strong>Error:</strong> Unknown email address. Check again or try your username.' )
			);
		} else {
			$error = new WP_Error(
				'invalid_username',
				__( '<strong>Error:</strong> Unknown username. Check again or try your email address.' )
			);
		}
	} elseif ( ! wp_is_application_passwords_available() ) {
		$error = new WP_Error(
			'application_passwords_disabled',
			__( 'Application passwords are not available.' )
		);
	} elseif ( ! wp_is_application_passwords_available_for_user( $user ) ) {
		$error = new WP_Error(
			'application_passwords_disabled_for_user',
			__( 'Application passwords are not available for your account. Please contact the site administrator for assistance.' )
		);
	}

	if ( $error ) {
		/**
		 * Kích hoạt khi mật khẩu ứng dụng không xác thực được người dùng.
		 *
		 * @since 5.6.0
		 *
		 * @param WP_Error $error Lỗi xác thực.
		 */
		do_action( 'application_password_failed_authentication', $error );

		return $error;
	}

	/*
	 * Loại bỏ mọi ký tự không phải chữ-số. Điều này để mật khẩu có thể được sử dụng
	 * có hoặc không có khoảng trắng để chỉ ra các nhóm cho dễ đọc.
	 *
	 * Mật khẩu ứng dụng được tạo ra chỉ gồm các ký tự chữ-số.
	 */
	$password = preg_replace( '/[^a-z\d]/i', '', $password );

	$hashed_passwords = WP_Application_Passwords::get_user_application_passwords( $user->ID );

	foreach ( $hashed_passwords as $key => $item ) {
		if ( ! WP_Application_Passwords::check_password( $password, $item['password'] ) ) {
			continue;
		}

		$error = new WP_Error();

		/**
		 * Kích hoạt khi mật khẩu ứng dụng đã được kiểm tra thành công là hợp lệ.
		 *
		 * Điều này cho phép các plugin thêm các ràng buộc bổ sung để ngăn mật khẩu ứng dụng được sử dụng.
		 *
		 * @since 5.6.0
		 *
		 * @param WP_Error $error    Đối tượng lỗi.
		 * @param WP_User  $user     Người dùng đang xác thực.
		 * @param array    $item     Chi tiết về mật khẩu ứng dụng.
		 * @param string   $password Mật khẩu thô được cung cấp.
		 */
		do_action( 'wp_authenticate_application_password_errors', $error, $user, $item, $password );

		if ( is_wp_error( $error ) && $error->has_errors() ) {
			/** Action này được ghi chú trong wp-includes/user.php */
			do_action( 'application_password_failed_authentication', $error );

			return $error;
		}

		WP_Application_Passwords::record_application_password_usage( $user->ID, $item['uuid'] );

		/**
		 * Kích hoạt sau khi mật khẩu ứng dụng được sử dụng để xác thực.
		 *
		 * @since 5.6.0
		 *
		 * @param WP_User $user Người dùng đã được xác thực.
		 * @param array   $item Mật khẩu ứng dụng đã sử dụng.
		 */
		do_action( 'application_password_did_authenticate', $user, $item );

		return $user;
	}

	$error = new WP_Error(
		'incorrect_password',
		__( 'The provided password is an invalid application password.' )
	);

	/** Action này được ghi chú trong wp-includes/user.php */
	do_action( 'application_password_failed_authentication', $error );

	return $error;
}

/**
 * Xác thực thông tin mật khẩu ứng dụng được truyền qua Basic Authentication.
 *
 * @since 5.6.0
 *
 * @param int|false $input_user ID người dùng nếu đã được xác định, false nếu không.
 * @return int|false ID người dùng đã xác thực nếu thành công, false nếu không.
 */
function wp_validate_application_password( $input_user ) {
	// Không xác thực hai lần.
	if ( ! empty( $input_user ) ) {
		return $input_user;
	}

	if ( ! wp_is_application_passwords_available() ) {
		return $input_user;
	}

	// Cả $_SERVER['PHP_AUTH_USER'] và $_SERVER['PHP_AUTH_PW'] đều phải được thiết lập để thử xác thực.
	if ( ! isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
		return $input_user;
	}

	$authenticated = wp_authenticate_application_password( null, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );

	if ( $authenticated instanceof WP_User ) {
		return $authenticated->ID;
	}

	// Nếu kết quả trả về không phải là người dùng, chỉ cần truyền tiếp giá trị đã nhận ban đầu.
	return $input_user;
}

/**
 * Đối với blog Multisite, kiểm tra xem người dùng đã xác thực có bị đánh dấu là
 * spammer hay không, hoặc blog chính của người dùng có bị đánh dấu là spam hay không.
 *
 * @since 3.7.0
 *
 * @param WP_User|WP_Error|null $user Đối tượng WP_User hoặc WP_Error từ callback trước đó. Mặc định null.
 * @return WP_User|WP_Error WP_User khi thành công, WP_Error nếu người dùng bị coi là spammer.
 */
function wp_authenticate_spam_check( $user ) {
	if ( $user instanceof WP_User && is_multisite() ) {
		/**
		 * Lọc xem người dùng có bị đánh dấu là spammer hay không.
		 *
		 * @since 3.7.0
		 *
		 * @param bool    $spammed Người dùng có bị coi là spammer hay không.
		 * @param WP_User $user    Người dùng cần kiểm tra.
		 */
		$spammed = apply_filters( 'check_is_user_spammed', is_user_spammy( $user ), $user );

		if ( $spammed ) {
			return new WP_Error( 'spammer_account', __( '<strong>Error:</strong> Your account has been marked as a spammer.' ) );
		}
	}
	return $user;
}

/**
 * Xác thực cookie đã đăng nhập.
 *
 * Kiểm tra cookie đã đăng nhập nếu cookie xác thực trước đó không thể
 * được xác thực và phân tích.
 *
 * Đây là callback cho bộ lọc {@see 'determine_current_user'}, không phải API.
 *
 * @since 3.9.0
 *
 * @param int|false $user_id ID người dùng (hoặc false) nhận được từ
 *                           bộ lọc `determine_current_user`.
 * @return int|false ID người dùng nếu hợp lệ, false nếu không. Nếu ID người dùng từ
 *                   callback bộ lọc trước đó được nhận, giá trị đó sẽ được trả về.
 */
function wp_validate_logged_in_cookie( $user_id ) {
	if ( $user_id ) {
		return $user_id;
	}

	if ( is_blog_admin() || is_network_admin() || empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
		return false;
	}

	return wp_validate_auth_cookie( $_COOKIE[ LOGGED_IN_COOKIE ], 'logged_in' );
}

/**
 * Lấy số lượng bài viết mà người dùng đã viết.
 *
 * @since 3.0.0
 * @since 4.1.0 Thêm tham số `$post_type`.
 * @since 4.3.0 Thêm tham số `$public_only`. Thêm khả năng truyền mảng
 *              các loại bài viết vào `$post_type`.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int          $userid      ID người dùng.
 * @param array|string $post_type   Tùy chọn. Loại bài viết đơn hoặc mảng các loại bài viết để đếm số bài viết. Mặc định 'post'.
 * @param bool         $public_only Tùy chọn. Chỉ trả về số đếm cho các bài viết công khai hay không. Mặc định false.
 * @return string Số lượng bài viết người dùng đã viết trong loại bài viết này.
 */
function count_user_posts( $userid, $post_type = 'post', $public_only = false ) {
	global $wpdb;

	$post_type = array_unique( (array) $post_type );
	sort( $post_type );

	$where = get_posts_by_author_sql( $post_type, true, $userid, $public_only );
	$query = "SELECT COUNT(*) FROM $wpdb->posts $where";

	$last_changed = wp_cache_get_last_changed( 'posts' );
	$cache_key    = 'count_user_posts:' . md5( $query ) . ':' . $last_changed;
	$count        = wp_cache_get( $cache_key, 'post-queries' );
	if ( false === $count ) {
		$count = $wpdb->get_var( $query );
		wp_cache_set( $cache_key, $count, 'post-queries' );
	}

	/**
	 * Lọc số lượng bài viết mà người dùng đã viết.
	 *
	 * @since 2.7.0
	 * @since 4.1.0 Thêm tham số `$post_type`.
	 * @since 4.3.1 Thêm tham số `$public_only`.
	 *
	 * @param int          $count       Số bài viết của người dùng.
	 * @param int          $userid      ID người dùng.
	 * @param string|array $post_type   Loại bài viết đơn hoặc mảng các loại bài viết để đếm số bài viết.
	 * @param bool         $public_only Có giới hạn các bài viết được đếm chỉ cho bài viết công khai hay không.
	 */
	return apply_filters( 'get_usernumposts', $count, $userid, $post_type, $public_only );
}

/**
 * Lấy số lượng bài viết được viết bởi danh sách người dùng.
 *
 * @since 3.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int[]           $users       Mảng ID người dùng.
 * @param string|string[] $post_type   Tùy chọn. Loại bài viết đơn hoặc mảng các loại bài viết để kiểm tra. Mặc định 'post'.
 * @param bool            $public_only Tùy chọn. Chỉ trả về số đếm cho các bài viết công khai. Mặc định false.
 * @return string[] Số lượng bài viết mỗi người dùng đã viết, dưới dạng chuỗi, theo khóa ID người dùng.
 */
function count_many_users_posts( $users, $post_type = 'post', $public_only = false ) {
	global $wpdb;

	if ( empty( $users ) || ! is_array( $users ) ) {
		return array();
	}

	/**
	 * Lọc xem có nên bỏ qua việc thực hiện đếm bài viết hay không.
	 *
	 * Khi lọc, trả về một mảng số đếm bài viết dưới dạng chuỗi, theo khóa
	 * ID người dùng.
	 *
	 * @since 6.8.0
	 *
	 * @param string[]|null   $count       Số đếm bài viết. Trả về giá trị không null để bỏ qua.
	 * @param int[]           $users       Mảng ID người dùng.
	 * @param string|string[] $post_type   Loại bài viết đơn hoặc mảng các loại bài viết để kiểm tra.
	 * @param bool            $public_only Chỉ trả về số đếm cho các bài viết công khai hay không.
	 */
	$pre = apply_filters( 'pre_count_many_users_posts', null, $users, $post_type, $public_only );
	if ( null !== $pre ) {
		return $pre;
	}

	$userlist = implode( ',', array_map( 'absint', $users ) );
	$where    = get_posts_by_author_sql( $post_type, true, null, $public_only );

	$result = $wpdb->get_results( "SELECT post_author, COUNT(*) FROM $wpdb->posts $where AND post_author IN ($userlist) GROUP BY post_author", ARRAY_N );

	$count = array_fill_keys( $users, 0 );
	foreach ( $result as $row ) {
		$count[ $row[0] ] = $row[1];
	}

	return $count;
}

//
// Các hàm tùy chọn người dùng.
//

/**
 * Lấy ID của người dùng hiện tại.
 *
 * @since MU (3.0.0)
 *
 * @return int ID của người dùng hiện tại, hoặc 0 nếu không có người dùng nào đăng nhập.
 */
function get_current_user_id() {
	if ( ! function_exists( 'wp_get_current_user' ) ) {
		return 0;
	}
	$user = wp_get_current_user();
	return ( isset( $user->ID ) ? (int) $user->ID : 0 );
}

/**
 * Lấy tùy chọn người dùng có thể là theo Site hoặc theo Network.
 *
 * Nếu ID người dùng không được cung cấp, thì người dùng hiện tại sẽ được sử dụng thay thế. Nếu
 * ID người dùng được cung cấp, thì dữ liệu người dùng sẽ được lấy. Bộ lọc cho
 * kết quả, cũng sẽ truyền tên tùy chọn gốc và cuối cùng là đối tượng dữ liệu
 * người dùng làm tham số thứ ba.
 *
 * Tùy chọn sẽ kiểm tra tên theo site trước rồi mới đến tên theo Network.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $option     Tên tùy chọn người dùng.
 * @param int    $user       Tùy chọn. ID người dùng.
 * @param string $deprecated Sử dụng get_option() để kiểm tra tùy chọn trong bảng options.
 * @return mixed Giá trị tùy chọn người dùng khi thành công, false khi thất bại.
 */
function get_user_option( $option, $user = 0, $deprecated = '' ) {
	global $wpdb;

	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '3.0.0' );
	}

	if ( empty( $user ) ) {
		$user = get_current_user_id();
	}

	$user = get_userdata( $user );
	if ( ! $user ) {
		return false;
	}

	$prefix = $wpdb->get_blog_prefix();
	if ( $user->has_prop( $prefix . $option ) ) { // Blog-specific.
		$result = $user->get( $prefix . $option );
	} elseif ( $user->has_prop( $option ) ) { // User-specific and cross-blog.
		$result = $user->get( $option );
	} else {
		$result = false;
	}

	/**
	 * Lọc giá trị tùy chọn người dùng cụ thể.
	 *
	 * Phần động của tên hook, `$option`, tham chiếu đến tên tùy chọn người dùng.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed   $result Giá trị của tùy chọn người dùng.
	 * @param string  $option Tên tùy chọn đang được lấy.
	 * @param WP_User $user   Đối tượng WP_User của người dùng có tùy chọn đang được lấy.
	 */
	return apply_filters( "get_user_option_{$option}", $result, $option, $user );
}

/**
 * Cập nhật tùy chọn người dùng với khả năng blog toàn cục.
 *
 * Tùy chọn người dùng giống như metadata người dùng ngoại trừ việc chúng hỗ trợ
 * tùy chọn blog toàn cục. Nếu tham số 'is_global' là false, mặc định là như vậy,
 * nó sẽ thêm tiền tố bảng WordPress vào trước tên tùy chọn.
 *
 * Xóa tùy chọn người dùng nếu $newvalue rỗng.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int    $user_id     ID người dùng.
 * @param string $option_name Tên tùy chọn người dùng.
 * @param mixed  $newvalue    Giá trị tùy chọn người dùng.
 * @param bool   $is_global   Tùy chọn. Tên tùy chọn là toàn cục hay theo blog cụ thể.
 *                            Mặc định false (theo blog cụ thể).
 * @return int|bool ID meta người dùng nếu tùy chọn chưa tồn tại, true khi cập nhật thành công,
 *                  false khi thất bại.
 */
function update_user_option( $user_id, $option_name, $newvalue, $is_global = false ) {
	global $wpdb;

	if ( ! $is_global ) {
		$option_name = $wpdb->get_blog_prefix() . $option_name;
	}

	return update_user_meta( $user_id, $option_name, $newvalue );
}

/**
 * Xóa tùy chọn người dùng với khả năng blog toàn cục.
 *
 * Tùy chọn người dùng giống như metadata người dùng ngoại trừ việc chúng hỗ trợ
 * tùy chọn blog toàn cục. Nếu tham số 'is_global' là false, mặc định là như vậy,
 * nó sẽ thêm tiền tố bảng WordPress vào trước tên tùy chọn.
 *
 * @since 3.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int    $user_id     ID người dùng.
 * @param string $option_name Tên tùy chọn người dùng.
 * @param bool   $is_global   Tùy chọn. Tên tùy chọn là toàn cục hay theo blog cụ thể.
 *                            Mặc định false (theo blog cụ thể).
 * @return bool True khi thành công, false khi thất bại.
 */
function delete_user_option( $user_id, $option_name, $is_global = false ) {
	global $wpdb;

	if ( ! $is_global ) {
		$option_name = $wpdb->get_blog_prefix() . $option_name;
	}

	return delete_user_meta( $user_id, $option_name );
}

/**
 * Lấy thông tin người dùng theo ID người dùng.
 *
 * @since 6.7.0
 *
 * @param int $user_id ID người dùng.
 *
 * @return WP_User|false Đối tượng WP_User khi thành công, false khi thất bại.
 */
function get_user( $user_id ) {
	return get_user_by( 'id', $user_id );
}

/**
 * Lấy danh sách người dùng khớp với tiêu chí.
 *
 * @since 3.1.0
 *
 * @see WP_User_Query
 *
 * @param array $args Tùy chọn. Đối số để lấy người dùng. Xem WP_User_Query::prepare_query()
 *                    để biết thêm thông tin về các đối số được chấp nhận.
 * @return array Danh sách người dùng.
 */
function get_users( $args = array() ) {

	$args                = wp_parse_args( $args );
	$args['count_total'] = false;

	$user_search = new WP_User_Query( $args );

	return (array) $user_search->get_results();
}

/**
 * Liệt kê tất cả người dùng của site, với nhiều tùy chọn khả dụng.
 *
 * @since 5.9.0
 *
 * @param string|array $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số mặc định.
 *
 *     @type string $orderby       Cách sắp xếp người dùng. Chấp nhận 'nicename', 'email', 'url', 'registered',
 *                                 'user_nicename', 'user_email', 'user_url', 'user_registered', 'name',
 *                                 'display_name', 'post_count', 'ID', 'meta_value', 'user_login'. Mặc định 'name'.
 *     @type string $order         Hướng sắp xếp cho $orderby. Chấp nhận 'ASC', 'DESC'. Mặc định 'ASC'.
 *     @type int    $number        Số người dùng tối đa để trả về hoặc hiển thị. Mặc định rỗng (tất cả người dùng).
 *     @type bool   $exclude_admin Có loại trừ tài khoản 'admin' hay không, nếu nó tồn tại. Mặc định false.
 *     @type bool   $show_fullname Có hiển thị tên đầy đủ của người dùng hay không. Mặc định false.
 *     @type string $feed          Nếu không rỗng, hiển thị liên kết đến feed của người dùng và sử dụng văn bản này làm
 *                                 tham số alt của liên kết. Mặc định rỗng.
 *     @type string $feed_image    Nếu không rỗng, hiển thị liên kết đến feed của người dùng và sử dụng URL hình ảnh này làm
 *                                 anchor có thể nhấp. Mặc định rỗng.
 *     @type string $feed_type     Loại feed để liên kết đến, ví dụ 'rss2'. Mặc định là loại feed mặc định.
 *     @type bool   $echo          Có xuất kết quả hay trả về nó. Mặc định true.
 *     @type string $style         Nếu 'list', mỗi người dùng được bọc trong phần tử `<li>`, nếu không người dùng
 *                                 sẽ được phân cách bằng dấu phẩy.
 *     @type bool   $html          Có liệt kê các mục dưới dạng HTML hay văn bản thuần. Mặc định true.
 *     @type string $exclude       Mảng, danh sách ID người dùng phân cách bằng dấu phẩy hoặc khoảng trắng để loại trừ. Mặc định rỗng.
 *     @type string $include       Mảng, danh sách ID người dùng phân cách bằng dấu phẩy hoặc khoảng trắng để bao gồm. Mặc định rỗng.
 * }
 * @return string|null Kết quả đầu ra nếu echo là false. Nếu không thì null.
 */
function wp_list_users( $args = array() ) {
	$defaults = array(
		'orderby'       => 'name',
		'order'         => 'ASC',
		'number'        => '',
		'exclude_admin' => true,
		'show_fullname' => false,
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
	 * Lọc các đối số truy vấn cho danh sách tất cả người dùng của site.
	 *
	 * @since 6.1.0
	 *
	 * @param array $query_args  Đối số truy vấn cho get_users().
	 * @param array $parsed_args Đối số được truyền vào wp_list_users() kết hợp với giá trị mặc định.
	 */
	$query_args = apply_filters( 'wp_list_users_args', $query_args, $parsed_args );

	$users = get_users( $query_args );

	foreach ( $users as $user_id ) {
		$user = get_userdata( $user_id );

		if ( $parsed_args['exclude_admin'] && 'admin' === $user->display_name ) {
			continue;
		}

		if ( $parsed_args['show_fullname'] && '' !== $user->first_name && '' !== $user->last_name ) {
			$name = sprintf(
				/* translators: 1: User's first name, 2: Last name. */
				_x( '%1$s %2$s', 'Display name based on first name and last name' ),
				$user->first_name,
				$user->last_name
			);
		} else {
			$name = $user->display_name;
		}

		if ( ! $parsed_args['html'] ) {
			$return .= $name . ', ';

			continue; // Không cần tiếp tục xử lý HTML.
		}

		if ( 'list' === $parsed_args['style'] ) {
			$return .= '<li>';
		}

		$row = $name;

		if ( ! empty( $parsed_args['feed_image'] ) || ! empty( $parsed_args['feed'] ) ) {
			$row .= ' ';
			if ( empty( $parsed_args['feed_image'] ) ) {
				$row .= '(';
			}

			$row .= '<a href="' . get_author_feed_link( $user->ID, $parsed_args['feed_type'] ) . '"';

			$alt = '';
			if ( ! empty( $parsed_args['feed'] ) ) {
				$alt  = ' alt="' . esc_attr( $parsed_args['feed'] ) . '"';
				$name = $parsed_args['feed'];
			}

			$row .= '>';

			if ( ! empty( $parsed_args['feed_image'] ) ) {
				$row .= '<img src="' . esc_url( $parsed_args['feed_image'] ) . '" style="border: none;"' . $alt . ' />';
			} else {
				$row .= $name;
			}

			$row .= '</a>';

			if ( empty( $parsed_args['feed_image'] ) ) {
				$row .= ')';
			}
		}

		$return .= $row;
		$return .= ( 'list' === $parsed_args['style'] ) ? '</li>' : ', ';
	}

	$return = rtrim( $return, ', ' );

	if ( ! $parsed_args['echo'] ) {
		return $return;
	}
	echo $return;
}

/**
 * Lấy danh sách các site mà người dùng thuộc về.
 *
 * @since 3.0.0
 * @since 4.7.0 Chuyển đổi sang sử dụng `get_sites()`.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int  $user_id ID người dùng.
 * @param bool $all     Có lấy tất cả các site hay chỉ các site không bị
 *                      đánh dấu là đã xóa, lưu trữ, hoặc spam.
 * @return object[] Danh sách các site của người dùng. Mảng rỗng nếu người dùng không tồn tại
 *                  hoặc không thuộc site nào.
 */
function get_blogs_of_user( $user_id, $all = false ) {
	global $wpdb;

	$user_id = (int) $user_id;

	// Người dùng chưa đăng nhập không thể có site.
	if ( empty( $user_id ) ) {
		return array();
	}

	/**
	 * Lọc danh sách các site của người dùng trước khi được điền dữ liệu.
	 *
	 * Trả về giá trị không null từ bộ lọc sẽ ngắn mạch hiệu quả
	 * get_blogs_of_user(), trả về giá trị đó thay thế.
	 *
	 * @since 4.6.0
	 *
	 * @param null|object[] $sites   Mảng các đối tượng site mà người dùng là thành viên.
	 * @param int           $user_id ID người dùng.
	 * @param bool          $all     Có nên trả về mảng chứa tất cả các site hay không, bao gồm
	 *                               những site được đánh dấu 'deleted', 'archived', hoặc 'spam'. Mặc định false.
	 */
	$sites = apply_filters( 'pre_get_blogs_of_user', null, $user_id, $all );

	if ( null !== $sites ) {
		return $sites;
	}

	$keys = get_user_meta( $user_id );
	if ( empty( $keys ) ) {
		return array();
	}

	if ( ! is_multisite() ) {
		$site_id                        = get_current_blog_id();
		$sites                          = array( $site_id => new stdClass() );
		$sites[ $site_id ]->userblog_id = $site_id;
		$sites[ $site_id ]->blogname    = get_option( 'blogname' );
		$sites[ $site_id ]->domain      = '';
		$sites[ $site_id ]->path        = '';
		$sites[ $site_id ]->site_id     = 1;
		$sites[ $site_id ]->siteurl     = get_option( 'siteurl' );
		$sites[ $site_id ]->archived    = 0;
		$sites[ $site_id ]->spam        = 0;
		$sites[ $site_id ]->deleted     = 0;
		return $sites;
	}

	$site_ids = array();

	if ( isset( $keys[ $wpdb->base_prefix . 'capabilities' ] ) && defined( 'MULTISITE' ) ) {
		$site_ids[] = 1;
		unset( $keys[ $wpdb->base_prefix . 'capabilities' ] );
	}

	$keys = array_keys( $keys );

	foreach ( $keys as $key ) {
		if ( ! str_ends_with( $key, 'capabilities' ) ) {
			continue;
		}
		if ( $wpdb->base_prefix && ! str_starts_with( $key, $wpdb->base_prefix ) ) {
			continue;
		}
		$site_id = str_replace( array( $wpdb->base_prefix, '_capabilities' ), '', $key );
		if ( ! is_numeric( $site_id ) ) {
			continue;
		}

		$site_ids[] = (int) $site_id;
	}

	$sites = array();

	if ( ! empty( $site_ids ) ) {
		$args = array(
			'number'   => '',
			'site__in' => $site_ids,
		);
		if ( ! $all ) {
			$args['archived'] = 0;
			$args['spam']     = 0;
			$args['deleted']  = 0;
		}

		$_sites = get_sites( $args );

		foreach ( $_sites as $site ) {
			$sites[ $site->id ] = (object) array(
				'userblog_id' => $site->id,
				'blogname'    => $site->blogname,
				'domain'      => $site->domain,
				'path'        => $site->path,
				'site_id'     => $site->network_id,
				'siteurl'     => $site->siteurl,
				'archived'    => $site->archived,
				'mature'      => $site->mature,
				'spam'        => $site->spam,
				'deleted'     => $site->deleted,
			);
		}
	}

	/**
	 * Lọc danh sách các site mà người dùng thuộc về.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param object[] $sites   Mảng các đối tượng site thuộc về người dùng.
	 * @param int      $user_id ID người dùng.
	 * @param bool     $all     Có nên trả về mảng chứa tất cả các site hay không, bao gồm
	 *                          những site được đánh dấu 'deleted', 'archived', hoặc 'spam'. Mặc định false.
	 */
	return apply_filters( 'get_blogs_of_user', $sites, $user_id, $all );
}

/**
 * Kiểm tra xem người dùng có phải là thành viên của một blog nhất định hay không.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $user_id Tùy chọn. ID duy nhất của người dùng. Mặc định là người dùng hiện tại.
 * @param int $blog_id Tùy chọn. ID của blog để kiểm tra. Mặc định là site hiện tại.
 * @return bool
 */
function is_user_member_of_blog( $user_id = 0, $blog_id = 0 ) {
	global $wpdb;

	$user_id = (int) $user_id;
	$blog_id = (int) $blog_id;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	/*
	 * Về mặt kỹ thuật không cần thiết, nhưng tiết kiệm các lệnh gọi đến get_site() và get_user_meta()
	 * trong trường hợp hàm được gọi khi người dùng chưa đăng nhập.
	 */
	if ( empty( $user_id ) ) {
		return false;
	} else {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return false;
		}
	}

	if ( ! is_multisite() ) {
		return true;
	}

	if ( empty( $blog_id ) ) {
		$blog_id = get_current_blog_id();
	}

	$blog = get_site( $blog_id );

	if ( ! $blog || ! isset( $blog->domain ) || $blog->archived || $blog->spam || $blog->deleted ) {
		return false;
	}

	$keys = get_user_meta( $user_id );
	if ( empty( $keys ) ) {
		return false;
	}

	// Không có dấu gạch dưới trước capabilities trong $base_capabilities_key.
	$base_capabilities_key = $wpdb->base_prefix . 'capabilities';
	$site_capabilities_key = $wpdb->base_prefix . $blog_id . '_capabilities';

	if ( isset( $keys[ $base_capabilities_key ] ) && 1 === $blog_id ) {
		return true;
	}

	if ( isset( $keys[ $site_capabilities_key ] ) ) {
		return true;
	}

	return false;
}

/**
 * Thêm dữ liệu meta cho người dùng.
 *
 * @since 3.0.0
 *
 * @param int    $user_id    ID người dùng.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Giá trị metadata. Mảng và đối tượng được lưu dưới dạng dữ liệu đã
 *                           serialize và sẽ được trả về cùng kiểu khi lấy ra. Các kiểu dữ liệu khác
 *                           sẽ được lưu dưới dạng chuỗi trong cơ sở dữ liệu:
 *                           - false được lưu và lấy ra dưới dạng chuỗi rỗng ('')
 *                           - true được lưu và lấy ra dưới dạng '1'
 *                           - số (cả integer và float) được lưu và lấy ra dưới dạng chuỗi
 *                           Phải có thể serialize nếu không phải kiểu vô hướng.
 * @param bool   $unique     Tùy chọn. Có ngăn thêm khóa trùng lặp hay không.
 *                           Mặc định false.
 * @return int|false ID meta khi thành công, false khi thất bại.
 */
function add_user_meta( $user_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'user', $user_id, $meta_key, $meta_value, $unique );
}

/**
 * Xóa metadata khớp tiêu chí từ người dùng.
 *
 * Bạn có thể khớp dựa trên khóa, hoặc khóa và giá trị. Xóa dựa trên khóa và
 * giá trị sẽ ngăn việc xóa metadata trùng lặp có cùng khóa. Nó cũng
 * cho phép xóa tất cả metadata khớp khóa, nếu cần.
 *
 * @since 3.0.0
 *
 * @link https://developer.wordpress.org/reference/functions/delete_user_meta/
 *
 * @param int    $user_id    ID người dùng.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Tùy chọn. Giá trị metadata. Nếu được cung cấp,
 *                           chỉ các hàng khớp giá trị mới bị xóa.
 *                           Phải có thể serialize nếu không phải kiểu vô hướng. Mặc định rỗng.
 * @return bool True khi thành công, false khi thất bại.
 */
function delete_user_meta( $user_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'user', $user_id, $meta_key, $meta_value );
}

/**
 * Lấy trường meta người dùng cho một người dùng.
 *
 * @since 3.0.0
 *
 * @link https://developer.wordpress.org/reference/functions/get_user_meta/
 *
 * @param int    $user_id ID người dùng.
 * @param string $key     Tùy chọn. Khóa meta cần lấy. Mặc định,
 *                        trả về dữ liệu cho tất cả các khóa.
 * @param bool   $single  Tùy chọn. Có trả về giá trị đơn hay không.
 *                        Tham số này không có tác dụng nếu `$key` không được chỉ định.
 *                        Mặc định false.
 * @return mixed Mảng các giá trị nếu `$single` là false.
 *               Giá trị của trường dữ liệu meta nếu `$single` là true.
 *               False cho `$user_id` không hợp lệ (không phải số, bằng 0, hoặc giá trị âm).
 *               Mảng rỗng nếu ID người dùng hợp lệ nhưng không tồn tại và `$single` là false.
 *               Chuỗi rỗng nếu ID người dùng hợp lệ nhưng không tồn tại và `$single` là true.
 *               Lưu ý: Các giá trị chưa serialize được trả về dưới dạng chuỗi:
 *               - giá trị false được trả về dưới dạng chuỗi rỗng ('')
 *               - giá trị true được trả về dưới dạng '1'
 *               - số (cả integer và float) được trả về dưới dạng chuỗi
 *               Mảng và đối tượng giữ nguyên kiểu ban đầu.
 */
function get_user_meta( $user_id, $key = '', $single = false ) {
	return get_metadata( 'user', $user_id, $key, $single );
}

/**
 * Cập nhật trường meta người dùng dựa trên ID người dùng.
 *
 * Sử dụng tham số $prev_value để phân biệt giữa các trường meta có cùng
 * khóa và ID người dùng.
 *
 * Nếu trường meta cho người dùng không tồn tại, nó sẽ được thêm mới.
 *
 * @since 3.0.0
 *
 * @link https://developer.wordpress.org/reference/functions/update_user_meta/
 *
 * @param int    $user_id    ID người dùng.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Giá trị metadata. Phải có thể serialize nếu không phải kiểu vô hướng.
 * @param mixed  $prev_value Tùy chọn. Giá trị trước đó để kiểm tra trước khi cập nhật.
 *                           Nếu được chỉ định, chỉ cập nhật các mục metadata hiện có với
 *                           giá trị này. Nếu không, cập nhật tất cả các mục. Mặc định rỗng.
 * @return int|bool ID meta nếu khóa chưa tồn tại, true khi cập nhật thành công,
 *                  false khi thất bại hoặc nếu giá trị truyền vào hàm
 *                  giống với giá trị đã có trong cơ sở dữ liệu.
 */
function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'user', $user_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Đếm số lượng người dùng có từng vai trò.
 *
 * Giả định rằng không có meta_values capabilities trùng lặp hoặc mồ côi.
 * Giả định tên vai trò là các cụm từ duy nhất. Cùng giả định được WP_User_Query::prepare_query() đưa ra.
 * Sử dụng $strategy = 'time' thì tốn nhiều CPU và nên xử lý được khoảng 10^7 người dùng.
 * Sử dụng $strategy = 'memory' thì tốn nhiều bộ nhớ và nên xử lý được khoảng 10^5 người dùng, nhưng xem WP Bug #12257.
 *
 * @since 3.0.0
 * @since 4.4.0 Số lượng người dùng không có vai trò giờ được bao gồm trong phần tử `none`.
 * @since 4.9.0 Thêm tham số `$site_id` để hỗ trợ multisite.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string   $strategy Tùy chọn. Chiến lược tính toán để sử dụng khi đếm người dùng.
 *                           Chấp nhận 'time' hoặc 'memory'. Mặc định 'time'.
 * @param int|null $site_id  Tùy chọn. ID site để đếm người dùng. Mặc định là site hiện tại.
 * @return array {
 *     Số đếm người dùng.
 *
 *     @type int   $total_users Tổng số người dùng trên site.
 *     @type int[] $avail_roles Mảng số đếm người dùng theo khóa vai trò.
 * }
 */
function count_users( $strategy = 'time', $site_id = null ) {
	global $wpdb;

	// Khởi tạo.
	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	/**
	 * Lọc số đếm người dùng trước khi các truy vấn được thực thi.
	 *
	 * Trả về giá trị không null để khiến count_users() trả về sớm.
	 *
	 * @since 5.1.0
	 *
	 * @param null|array $result   Giá trị để trả về thay thế. Mặc định null để tiếp tục truy vấn.
	 * @param string     $strategy Tùy chọn. Chiến lược tính toán để sử dụng khi đếm người dùng.
	 *                             Chấp nhận 'time' hoặc 'memory'. Mặc định 'time'.
	 * @param int        $site_id  ID site để đếm người dùng.
	 */
	$pre = apply_filters( 'pre_count_users', null, $strategy, $site_id );

	if ( null !== $pre ) {
		return $pre;
	}

	$blog_prefix = $wpdb->get_blog_prefix( $site_id );
	$result      = array();

	if ( 'time' === $strategy ) {
		if ( is_multisite() && get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			$avail_roles = wp_roles()->get_names();
			restore_current_blog();
		} else {
			$avail_roles = wp_roles()->get_names();
		}

		// Xây dựng truy vấn tốn CPU nhưng trả về thông tin ngắn gọn.
		$select_count = array();
		foreach ( $avail_roles as $this_role => $name ) {
			$select_count[] = $wpdb->prepare( 'COUNT(NULLIF(`meta_value` LIKE %s, false))', '%' . $wpdb->esc_like( '"' . $this_role . '"' ) . '%' );
		}
		$select_count[] = "COUNT(NULLIF(`meta_value` = 'a:0:{}', false))";
		$select_count   = implode( ', ', $select_count );

		// Thêm chỉ mục meta_value vào danh sách chọn, sau đó chạy truy vấn.
		$row = $wpdb->get_row(
			"
			SELECT {$select_count}, COUNT(*)
			FROM {$wpdb->usermeta}
			INNER JOIN {$wpdb->users} ON user_id = ID
			WHERE meta_key = '{$blog_prefix}capabilities'
		",
			ARRAY_N
		);

		// Chạy lại vòng lặp trước đó để liên kết kết quả với tên vai trò.
		$col         = 0;
		$role_counts = array();
		foreach ( $avail_roles as $this_role => $name ) {
			$count = (int) $row[ $col++ ];
			if ( $count > 0 ) {
				$role_counts[ $this_role ] = $count;
			}
		}

		$role_counts['none'] = (int) $row[ $col++ ];

		// Lấy chỉ mục meta_value từ cuối tập kết quả.
		$total_users = (int) $row[ $col ];

		$result['total_users'] = $total_users;
		$result['avail_roles'] =& $role_counts;
	} else {
		$avail_roles = array(
			'none' => 0,
		);

		$users_of_blog = $wpdb->get_col(
			"
			SELECT meta_value
			FROM {$wpdb->usermeta}
			INNER JOIN {$wpdb->users} ON user_id = ID
			WHERE meta_key = '{$blog_prefix}capabilities'
		"
		);

		foreach ( $users_of_blog as $caps_meta ) {
			$b_roles = maybe_unserialize( $caps_meta );
			if ( ! is_array( $b_roles ) ) {
				continue;
			}
			if ( empty( $b_roles ) ) {
				++$avail_roles['none'];
			}
			foreach ( $b_roles as $b_role => $val ) {
				if ( isset( $avail_roles[ $b_role ] ) ) {
					++$avail_roles[ $b_role ];
				} else {
					$avail_roles[ $b_role ] = 1;
				}
			}
		}

		$result['total_users'] = count( $users_of_blog );
		$result['avail_roles'] =& $avail_roles;
	}

	return $result;
}

/**
 * Trả về số lượng người dùng đang hoạt động trong cài đặt của bạn.
 *
 * Lưu ý rằng trên site lớn, số đếm có thể được cache và chỉ cập nhật hai lần mỗi ngày.
 *
 * @since MU (3.0.0)
 * @since 4.8.0 Thêm tham số `$network_id`.
 * @since 6.0.0 Chuyển sang wp-includes/user.php.
 *
 * @param int|null $network_id ID của mạng lưới. Mặc định là mạng lưới hiện tại.
 * @return int Số lượng người dùng đang hoạt động trên mạng lưới.
 */
function get_user_count( $network_id = null ) {
	if ( ! is_multisite() && null !== $network_id ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: $network_id */
				__( 'Unable to pass %s if not using multisite.' ),
				'<code>$network_id</code>'
			),
			'6.0.0'
		);
	}

	return (int) get_network_option( $network_id, 'user_count', -1 );
}

/**
 * Cập nhật tổng số người dùng trên site nếu đếm người dùng trực tiếp được bật.
 *
 * @since 6.0.0
 *
 * @param int|null $network_id ID của mạng lưới. Mặc định là mạng lưới hiện tại.
 * @return bool Cập nhật có thành công hay không.
 */
function wp_maybe_update_user_counts( $network_id = null ) {
	if ( ! is_multisite() && null !== $network_id ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: $network_id */
				__( 'Unable to pass %s if not using multisite.' ),
				'<code>$network_id</code>'
			),
			'6.0.0'
		);
	}

	$is_small_network = ! wp_is_large_user_count( $network_id );
	/** Bộ lọc này được ghi chú trong wp-includes/ms-functions.php */
	if ( ! apply_filters( 'enable_live_network_counts', $is_small_network, 'users' ) ) {
		return false;
	}

	return wp_update_user_counts( $network_id );
}

/**
 * Cập nhật tổng số người dùng trên site.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @since 6.0.0
 *
 * @param int|null $network_id ID của mạng lưới. Mặc định là mạng lưới hiện tại.
 * @return bool Cập nhật có thành công hay không.
 */
function wp_update_user_counts( $network_id = null ) {
	global $wpdb;

	if ( ! is_multisite() && null !== $network_id ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: $network_id */
				__( 'Unable to pass %s if not using multisite.' ),
				'<code>$network_id</code>'
			),
			'6.0.0'
		);
	}

	$query = "SELECT COUNT(ID) as c FROM $wpdb->users";
	if ( is_multisite() ) {
		$query .= " WHERE spam = '0' AND deleted = '0'";
	}

	$count = $wpdb->get_var( $query );

	return update_network_option( $network_id, 'user_count', $count );
}

/**
 * Lên lịch tính toán lại định kỳ tổng số người dùng.
 *
 * @since 6.0.0
 */
function wp_schedule_update_user_counts() {
	if ( ! is_main_site() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'wp_update_user_counts' ) && ! wp_installing() ) {
		wp_schedule_event( time(), 'twicedaily', 'wp_update_user_counts' );
	}
}

/**
 * Xác định xem site có số lượng người dùng lớn hay không.
 *
 * Tiêu chí mặc định cho site lớn là hơn 10.000 người dùng.
 *
 * @since 6.0.0
 *
 * @param int|null $network_id ID của mạng lưới. Mặc định là mạng lưới hiện tại.
 * @return bool Site có số lượng người dùng lớn hay không.
 */
function wp_is_large_user_count( $network_id = null ) {
	if ( ! is_multisite() && null !== $network_id ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: $network_id */
				__( 'Unable to pass %s if not using multisite.' ),
				'<code>$network_id</code>'
			),
			'6.0.0'
		);
	}

	$count = get_user_count( $network_id );

	/**
	 * Lọc xem site có được coi là lớn hay không, dựa trên số lượng người dùng.
	 *
	 * @since 6.0.0
	 *
	 * @param bool     $is_large_user_count Site có số lượng người dùng lớn hay không.
	 * @param int      $count               Tổng số người dùng.
	 * @param int|null $network_id          ID của mạng lưới. `null` đại diện cho mạng lưới hiện tại.
	 */
	return apply_filters( 'wp_is_large_user_count', $count > 10000, $count, $network_id );
}

//
// Các hàm trợ giúp riêng tư.
//

/**
 * Thiết lập các biến toàn cục người dùng.
 *
 * Được sử dụng bởi wp_set_current_user() để tương thích ngược. Có thể bị đánh dấu lỗi thời trong tương lai.
 *
 * @since 2.0.4
 *
 * @global string  $user_login    Tên đăng nhập của người dùng.
 * @global WP_User $userdata      Dữ liệu người dùng.
 * @global int     $user_level    Cấp độ của người dùng.
 * @global int     $user_ID       ID của người dùng.
 * @global string  $user_email    Địa chỉ email của người dùng.
 * @global string  $user_url      URL trong hồ sơ người dùng.
 * @global string  $user_identity Tên hiển thị của người dùng.
 *
 * @param int $for_user_id Tùy chọn. ID người dùng để thiết lập dữ liệu toàn cục. Mặc định 0.
 */
function setup_userdata( $for_user_id = 0 ) {
	global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_identity;

	if ( ! $for_user_id ) {
		$for_user_id = get_current_user_id();
	}
	$user = get_userdata( $for_user_id );

	if ( ! $user ) {
		$user_ID       = 0;
		$user_level    = 0;
		$userdata      = null;
		$user_login    = '';
		$user_email    = '';
		$user_url      = '';
		$user_identity = '';
		return;
	}

	$user_ID       = (int) $user->ID;
	$user_level    = (int) $user->user_level;
	$userdata      = $user;
	$user_login    = $user->user_login;
	$user_email    = $user->user_email;
	$user_url      = $user->user_url;
	$user_identity = $user->display_name;
}

/**
 * Tạo nội dung HTML dropdown danh sách người dùng.
 *
 * Nội dung có thể được hiển thị (mặc định) hoặc lấy ra bằng cách đặt đối số
 * 'echo' thành false. Các đối số 'include' và 'exclude' là tùy chọn; nếu không được
 * chỉ định, tất cả người dùng sẽ được hiển thị. Chỉ một trong hai có thể được sử dụng
 * trong một lần gọi, 'include' hoặc 'exclude', nhưng không phải cả hai.
 *
 * @since 2.3.0
 * @since 4.5.0 Thêm giá trị 'display_name_with_login' cho 'show'.
 * @since 4.7.0 Thêm các tham số 'role', 'role__in', và 'role__not_in'.
 * @since 5.9.0 Thêm các tham số 'capability', 'capability__in', và 'capability__not_in'.
 *              Đánh dấu lỗi thời tham số 'who'.
 *
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi các đối số để tạo dropdown người dùng.
 *     Xem WP_User_Query::prepare_query() để biết thêm các đối số khả dụng.
 *
 *     @type string          $show_option_all         Văn bản hiển thị làm mặc định dropdown (tất cả).
 *                                                    Mặc định rỗng.
 *     @type string          $show_option_none        Văn bản hiển thị làm mặc định dropdown khi không
 *                                                    tìm thấy người dùng. Mặc định rỗng.
 *     @type int|string      $option_none_value       Giá trị sử dụng cho `$show_option_none` khi không tìm thấy
 *                                                    người dùng. Mặc định -1.
 *     @type string          $hide_if_only_one_author Có bỏ qua tạo dropdown hay không
 *                                                    nếu chỉ tìm thấy một người dùng. Mặc định rỗng.
 *     @type string          $orderby                 Trường để sắp xếp người dùng. Chấp nhận các trường người dùng.
 *                                                    Mặc định 'display_name'.
 *     @type string          $order                   Sắp xếp người dùng tăng dần hay giảm dần.
 *                                                    Chấp nhận 'ASC' (tăng dần) hoặc 'DESC' (giảm dần).
 *                                                    Mặc định 'ASC'.
 *     @type int[]|string    $include                 Mảng hoặc danh sách ID người dùng phân cách bằng dấu phẩy để bao gồm.
 *                                                    Mặc định rỗng.
 *     @type int[]|string    $exclude                 Mảng hoặc danh sách ID người dùng phân cách bằng dấu phẩy để loại trừ.
 *                                                    Mặc định rỗng.
 *     @type bool|int        $multi                   Có bỏ qua thuộc tính ID trên phần tử 'select' hay không.
 *                                                    Chấp nhận 1|true hoặc 0|false. Mặc định 0|false.
 *     @type string          $show                    Dữ liệu người dùng để hiển thị. Nếu mục được chọn rỗng
 *                                                    thì 'user_login' sẽ được hiển thị trong ngoặc đơn.
 *                                                    Chấp nhận bất kỳ trường người dùng nào, hoặc 'display_name_with_login' để hiển thị
 *                                                    tên hiển thị với user_login trong ngoặc đơn.
 *                                                    Mặc định 'display_name'.
 *     @type int|bool        $echo                    Có echo hay trả về dropdown. Chấp nhận 1|true (echo)
 *                                                    hoặc 0|false (trả về). Mặc định 1|true.
 *     @type int             $selected                ID người dùng nào nên được chọn. Mặc định 0.
 *     @type bool            $include_selected        Có luôn bao gồm ID người dùng đã chọn trong dropdown hay không.
 *                                                    Mặc định false.
 *     @type string          $name                    Thuộc tính name của phần tử select. Mặc định 'user'.
 *     @type string          $id                      Thuộc tính ID của phần tử select. Mặc định là giá trị của `$name`.
 *     @type string          $class                   Thuộc tính class của phần tử select. Mặc định rỗng.
 *     @type int             $blog_id                 ID của blog (chỉ Multisite). Mặc định là ID của blog hiện tại.
 *     @type string          $who                     Đã lỗi thời, sử dụng `$capability` thay thế.
 *                                                    Loại người dùng để truy vấn. Chỉ chấp nhận chuỗi rỗng hoặc
 *                                                    'authors'. Mặc định rỗng (tất cả người dùng).
 *     @type string|string[] $role                    Mảng hoặc danh sách tên vai trò phân cách bằng dấu phẩy mà người dùng
 *                                                    phải khớp để được bao gồm trong kết quả. Lưu ý đây là
 *                                                    danh sách bao hàm: người dùng phải khớp *mỗi* vai trò. Mặc định rỗng.
 *     @type string[]        $role__in                Mảng tên vai trò. Người dùng khớp phải có ít nhất một
 *                                                    trong các vai trò này. Mặc định mảng rỗng.
 *     @type string[]        $role__not_in            Mảng tên vai trò để loại trừ. Người dùng khớp một hoặc nhiều
 *                                                    vai trò này sẽ không được bao gồm trong kết quả. Mặc định mảng rỗng.
 *     @type string|string[] $capability              Mảng hoặc danh sách tên quyền phân cách bằng dấu phẩy mà người dùng
 *                                                    phải khớp để được bao gồm trong kết quả. Lưu ý đây là
 *                                                    danh sách bao hàm: người dùng phải khớp *mỗi* quyền.
 *                                                    KHÔNG hoạt động cho các quyền không có trong cơ sở dữ liệu hoặc được lọc
 *                                                    qua {@see 'map_meta_cap'}. Mặc định rỗng.
 *     @type string[]        $capability__in          Mảng tên quyền. Người dùng khớp phải có ít nhất một
 *                                                    trong các quyền này.
 *                                                    KHÔNG hoạt động cho các quyền không có trong cơ sở dữ liệu hoặc được lọc
 *                                                    qua {@see 'map_meta_cap'}. Mặc định mảng rỗng.
 *     @type string[]        $capability__not_in      Mảng tên quyền để loại trừ. Người dùng khớp một hoặc nhiều
 *                                                    quyền này sẽ không được bao gồm trong kết quả.
 *                                                    KHÔNG hoạt động cho các quyền không có trong cơ sở dữ liệu hoặc được lọc
 *                                                    qua {@see 'map_meta_cap'}. Mặc định mảng rỗng.
 * }
 * @return string Danh sách dropdown HTML của người dùng.
 */
function wp_dropdown_users( $args = '' ) {
	$defaults = array(
		'show_option_all'         => '',
		'show_option_none'        => '',
		'hide_if_only_one_author' => '',
		'orderby'                 => 'display_name',
		'order'                   => 'ASC',
		'include'                 => '',
		'exclude'                 => '',
		'multi'                   => 0,
		'show'                    => 'display_name',
		'echo'                    => 1,
		'selected'                => 0,
		'name'                    => 'user',
		'class'                   => '',
		'id'                      => '',
		'blog_id'                 => get_current_blog_id(),
		'who'                     => '',
		'include_selected'        => false,
		'option_none_value'       => -1,
		'role'                    => '',
		'role__in'                => array(),
		'role__not_in'            => array(),
		'capability'              => '',
		'capability__in'          => array(),
		'capability__not_in'      => array(),
	);

	$defaults['selected'] = is_author() ? get_query_var( 'author' ) : 0;

	$parsed_args = wp_parse_args( $args, $defaults );

	$query_args = wp_array_slice_assoc(
		$parsed_args,
		array(
			'blog_id',
			'include',
			'exclude',
			'orderby',
			'order',
			'who',
			'role',
			'role__in',
			'role__not_in',
			'capability',
			'capability__in',
			'capability__not_in',
		)
	);

	$fields = array( 'ID', 'user_login' );

	$show = ! empty( $parsed_args['show'] ) ? $parsed_args['show'] : 'display_name';
	if ( 'display_name_with_login' === $show ) {
		$fields[] = 'display_name';
	} else {
		$fields[] = $show;
	}

	$query_args['fields'] = $fields;

	$show_option_all   = $parsed_args['show_option_all'];
	$show_option_none  = $parsed_args['show_option_none'];
	$option_none_value = $parsed_args['option_none_value'];

	/**
	 * Lọc các đối số truy vấn cho danh sách người dùng trong dropdown.
	 *
	 * @since 4.4.0
	 *
	 * @param array $query_args  Các đối số truy vấn cho get_users().
	 * @param array $parsed_args Các đối số được truyền vào wp_dropdown_users() kết hợp với giá trị mặc định.
	 */
	$query_args = apply_filters( 'wp_dropdown_users_args', $query_args, $parsed_args );

	$users = get_users( $query_args );

	$output = '';
	if ( ! empty( $users ) && ( empty( $parsed_args['hide_if_only_one_author'] ) || count( $users ) > 1 ) ) {
		$name = esc_attr( $parsed_args['name'] );
		if ( $parsed_args['multi'] && ! $parsed_args['id'] ) {
			$id = '';
		} else {
			$id = $parsed_args['id'] ? " id='" . esc_attr( $parsed_args['id'] ) . "'" : " id='$name'";
		}
		$output = "<select name='{$name}'{$id} class='" . $parsed_args['class'] . "'>\n";

		if ( $show_option_all ) {
			$output .= "\t<option value='0'>$show_option_all</option>\n";
		}

		if ( $show_option_none ) {
			$_selected = selected( $option_none_value, $parsed_args['selected'], false );
			$output   .= "\t<option value='" . esc_attr( $option_none_value ) . "'$_selected>$show_option_none</option>\n";
		}

		if ( $parsed_args['include_selected'] && ( $parsed_args['selected'] > 0 ) ) {
			$found_selected          = false;
			$parsed_args['selected'] = (int) $parsed_args['selected'];

			foreach ( (array) $users as $user ) {
				$user->ID = (int) $user->ID;
				if ( $user->ID === $parsed_args['selected'] ) {
					$found_selected = true;
				}
			}

			if ( ! $found_selected ) {
				$selected_user = get_userdata( $parsed_args['selected'] );
				if ( $selected_user ) {
					$users[] = $selected_user;
				}
			}
		}

		foreach ( (array) $users as $user ) {
			if ( 'display_name_with_login' === $show ) {
				/* translators: 1: User's display name, 2: User login. */
				$display = sprintf( _x( '%1$s (%2$s)', 'user dropdown' ), $user->display_name, $user->user_login );
			} elseif ( ! empty( $user->$show ) ) {
				$display = $user->$show;
			} else {
				$display = '(' . $user->user_login . ')';
			}

			$_selected = selected( $user->ID, $parsed_args['selected'], false );
			$output   .= "\t<option value='$user->ID'$_selected>" . esc_html( $display ) . "</option>\n";
		}

		$output .= '</select>';
	}

	/**
	 * Lọc đầu ra HTML của wp_dropdown_users().
	 *
	 * @since 2.3.0
	 *
	 * @param string $output Đầu ra HTML được tạo bởi wp_dropdown_users().
	 */
	$html = apply_filters( 'wp_dropdown_users', $output );

	if ( $parsed_args['echo'] ) {
		echo $html;
	}
	return $html;
}

/**
 * Làm sạch trường người dùng dựa trên ngữ cảnh.
 *
 * Các giá trị ngữ cảnh có thể là: 'raw', 'edit', 'db', 'display', 'attribute' và 'js'.
 * Ngữ cảnh 'display' được sử dụng theo mặc định. Ngữ cảnh 'attribute' và 'js' được xử lý
 * giống 'display' khi gọi bộ lọc.
 *
 * @since 2.3.0
 *
 * @param string $field   Tên trường của đối tượng người dùng.
 * @param mixed  $value   Giá trị của đối tượng người dùng.
 * @param int    $user_id ID người dùng.
 * @param string $context Cách làm sạch các trường người dùng. Tìm 'raw', 'edit', 'db', 'display',
 *                        'attribute' và 'js'.
 * @return mixed Giá trị đã được làm sạch.
 */
function sanitize_user_field( $field, $value, $user_id, $context ) {
	$int_fields = array( 'ID' );
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}

	if ( 'raw' === $context ) {
		return $value;
	}

	if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
		return $value;
	}

	$prefixed = str_contains( $field, 'user_' );

	if ( 'edit' === $context ) {
		if ( $prefixed ) {

			/** Bộ lọc này được ghi chú trong wp-includes/post.php */
			$value = apply_filters( "edit_{$field}", $value, $user_id );
		} else {

			/**
			 * Lọc giá trị trường người dùng trong ngữ cảnh 'edit'.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến trường người dùng có tiền tố
			 * đang được lọc, như 'user_login', 'user_email', 'first_name', v.v.
			 *
			 * @since 2.9.0
			 *
			 * @param mixed $value   Giá trị của trường người dùng có tiền tố.
			 * @param int   $user_id ID người dùng.
			 */
			$value = apply_filters( "edit_user_{$field}", $value, $user_id );
		}

		if ( 'description' === $field ) {
			$value = esc_html( $value ); // textarea_escaped?
		} else {
			$value = esc_attr( $value );
		}
	} elseif ( 'db' === $context ) {
		if ( $prefixed ) {
			/** Bộ lọc này được ghi chú trong wp-includes/post.php */
			$value = apply_filters( "pre_{$field}", $value );
		} else {

			/**
			 * Lọc giá trị trường người dùng trong ngữ cảnh 'db'.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến trường người dùng có tiền tố
			 * đang được lọc, như 'user_login', 'user_email', 'first_name', v.v.
			 *
			 * @since 2.9.0
			 *
			 * @param mixed $value Giá trị của trường người dùng có tiền tố.
			 */
			$value = apply_filters( "pre_user_{$field}", $value );
		}
	} else {
		// Sử dụng bộ lọc hiển thị theo mặc định.
		if ( $prefixed ) {

			/** Bộ lọc này được ghi chú trong wp-includes/post.php */
			$value = apply_filters( "{$field}", $value, $user_id, $context );
		} else {

			/**
			 * Lọc giá trị trường người dùng trong ngữ cảnh tiêu chuẩn.
			 *
			 * Phần động của tên hook, `$field`, tham chiếu đến trường người dùng có tiền tố
			 * đang được lọc, như 'user_login', 'user_email', 'first_name', v.v.
			 *
			 * @since 2.9.0
			 *
			 * @param mixed  $value   Giá trị đối tượng người dùng cần làm sạch.
			 * @param int    $user_id ID người dùng.
			 * @param string $context Ngữ cảnh để lọc trong đó.
			 */
			$value = apply_filters( "user_{$field}", $value, $user_id, $context );
		}
	}

	if ( 'user_url' === $field ) {
		$value = esc_url( $value );
	}

	if ( 'attribute' === $context ) {
		$value = esc_attr( $value );
	} elseif ( 'js' === $context ) {
		$value = esc_js( $value );
	}

	// Khôi phục kiểu dữ liệu cho các trường integer sau esc_attr().
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}

	return $value;
}

/**
 * Cập nhật tất cả cache người dùng.
 *
 * @since 3.0.0
 *
 * @param object|WP_User $user Đối tượng người dùng hoặc hàng cơ sở dữ liệu cần cache.
 * @return void|false Void khi thành công, false khi thất bại.
 */
function update_user_caches( $user ) {
	if ( $user instanceof WP_User ) {
		if ( ! $user->exists() ) {
			return false;
		}

		$user = $user->data;
	}

	wp_cache_add( $user->ID, $user, 'users' );
	wp_cache_add( $user->user_login, $user->ID, 'userlogins' );
	wp_cache_add( $user->user_nicename, $user->ID, 'userslugs' );

	if ( ! empty( $user->user_email ) ) {
		wp_cache_add( $user->user_email, $user->ID, 'useremail' );
	}
}

/**
 * Xóa tất cả cache người dùng.
 *
 * @since 3.0.0
 * @since 4.4.0 Thêm action 'clean_user_cache'.
 * @since 6.2.0 Cache metadata người dùng giờ cũng được xóa.
 *
 * @param WP_User|int $user Đối tượng người dùng hoặc ID cần xóa khỏi cache.
 */
function clean_user_cache( $user ) {
	if ( is_numeric( $user ) ) {
		$user = new WP_User( $user );
	}

	if ( ! $user->exists() ) {
		return;
	}

	wp_cache_delete( $user->ID, 'users' );
	wp_cache_delete( $user->user_login, 'userlogins' );
	wp_cache_delete( $user->user_nicename, 'userslugs' );

	if ( ! empty( $user->user_email ) ) {
		wp_cache_delete( $user->user_email, 'useremail' );
	}

	wp_cache_delete( $user->ID, 'user_meta' );
	wp_cache_set_users_last_changed();

	/**
	 * Kích hoạt ngay sau khi cache của người dùng được xóa.
	 *
	 * @since 4.4.0
	 *
	 * @param int     $user_id ID người dùng.
	 * @param WP_User $user    Đối tượng người dùng.
	 */
	do_action( 'clean_user_cache', $user->ID, $user );
}

/**
 * Xác định xem tên đăng nhập đã cho có tồn tại hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.0.0
 *
 * @param string $username Tên đăng nhập cần kiểm tra sự tồn tại.
 * @return int|false ID người dùng khi thành công, false khi thất bại.
 */
function username_exists( $username ) {
	$user = get_user_by( 'login', $username );
	if ( $user ) {
		$user_id = $user->ID;
	} else {
		$user_id = false;
	}

	/**
	 * Lọc xem tên đăng nhập đã cho có tồn tại hay không.
	 *
	 * @since 4.9.0
	 *
	 * @param int|false $user_id  ID người dùng liên kết với tên đăng nhập,
	 *                            hoặc false nếu tên đăng nhập không tồn tại.
	 * @param string    $username Tên đăng nhập cần kiểm tra sự tồn tại.
	 */
	return apply_filters( 'username_exists', $user_id, $username );
}

/**
 * Xác định xem email đã cho có tồn tại hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.1.0
 *
 * @param string $email Email cần kiểm tra sự tồn tại.
 * @return int|false ID người dùng khi thành công, false khi thất bại.
 */
function email_exists( $email ) {
	$user = get_user_by( 'email', $email );
	if ( $user ) {
		$user_id = $user->ID;
	} else {
		$user_id = false;
	}

	/**
	 * Lọc xem email đã cho có tồn tại hay không.
	 *
	 * @since 5.6.0
	 *
	 * @param int|false $user_id ID người dùng liên kết với email,
	 *                           hoặc false nếu email không tồn tại.
	 * @param string    $email   Email cần kiểm tra sự tồn tại.
	 */
	return apply_filters( 'email_exists', $user_id, $email );
}

/**
 * Kiểm tra xem tên đăng nhập có hợp lệ hay không.
 *
 * @since 2.0.1
 * @since 4.4.0 Tên đăng nhập đã làm sạch rỗng giờ được coi là không hợp lệ.
 *
 * @param string $username Tên đăng nhập.
 * @return bool Tên đăng nhập đã cho có hợp lệ hay không.
 */
function validate_username( $username ) {
	$sanitized = sanitize_user( $username, true );
	$valid     = ( $sanitized === $username && ! empty( $sanitized ) );

	/**
	 * Lọc xem tên đăng nhập được cung cấp có hợp lệ hay không.
	 *
	 * @since 2.0.1
	 *
	 * @param bool   $valid    Tên đăng nhập đã cho có hợp lệ hay không.
	 * @param string $username Tên đăng nhập cần kiểm tra.
	 */
	return apply_filters( 'validate_username', $valid, $username );
}

/**
 * Chèn người dùng vào cơ sở dữ liệu.
 *
 * Hầu hết các trường của mảng `$userdata` đều có bộ lọc liên kết với giá trị. Ngoại lệ là
 * 'ID', 'rich_editing', 'syntax_highlighting', 'comment_shortcuts', 'admin_color', 'use_ssl',
 * 'user_registered', 'user_activation_key', 'spam', và 'role'. Các bộ lọc có tiền tố
 * 'pre_user_' theo sau bởi tên trường. Ví dụ với 'description' sẽ có bộ lọc
 * tên 'pre_user_description' có thể được hook vào.
 *
 * @since 2.0.0
 * @since 3.6.0 Các trường `aim`, `jabber`, và `yim` đã bị xóa khỏi phương thức liên hệ
 *              người dùng mặc định cho cài đặt mới. Xem wp_get_user_contact_methods().
 * @since 4.7.0 Trường `locale` có thể được truyền vào `$userdata`.
 * @since 5.3.0 Trường `user_activation_key` có thể được truyền vào `$userdata`.
 * @since 5.3.0 Trường `spam` có thể được truyền vào `$userdata` (chỉ Multisite).
 * @since 5.9.0 Trường `meta_input` có thể được truyền vào `$userdata` để cho phép thêm dữ liệu meta người dùng.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array|object|WP_User $userdata {
 *     Mảng, đối tượng, hoặc đối tượng WP_User chứa các đối số dữ liệu người dùng.
 *
 *     @type int    $ID                   ID người dùng. Nếu được cung cấp, người dùng sẽ được cập nhật.
 *     @type string $user_pass            Mật khẩu văn bản thuần cho người dùng mới.
 *                                        Mật khẩu đã hash cho người dùng hiện có.
 *     @type string $user_login           Tên đăng nhập của người dùng.
 *     @type string $user_nicename        Tên người dùng thân thiện với URL.
 *     @type string $user_url             URL người dùng.
 *     @type string $user_email           Địa chỉ email người dùng.
 *     @type string $display_name         Tên hiển thị của người dùng.
 *                                        Mặc định là tên đăng nhập của người dùng.
 *     @type string $nickname             Biệt danh của người dùng.
 *                                        Mặc định là tên đăng nhập của người dùng.
 *     @type string $first_name           Tên của người dùng. Đối với người dùng mới, sẽ được sử dụng
 *                                        để xây dựng phần đầu của tên hiển thị
 *                                        nếu `$display_name` không được chỉ định.
 *     @type string $last_name            Họ của người dùng. Đối với người dùng mới, sẽ được sử dụng
 *                                        để xây dựng phần thứ hai của tên hiển thị
 *                                        nếu `$display_name` không được chỉ định.
 *     @type string $description          Mô tả tiểu sử người dùng.
 *     @type string $rich_editing         Có bật trình soạn thảo giàu cho người dùng hay không.
 *                                        Chấp nhận 'true' hoặc 'false' dưới dạng chuỗi,
 *                                        không phải boolean. Mặc định 'true'.
 *     @type string $syntax_highlighting  Có bật trình soạn thảo code giàu cho người dùng hay không.
 *                                        Chấp nhận 'true' hoặc 'false' dưới dạng chuỗi,
 *                                        không phải boolean. Mặc định 'true'.
 *     @type string $comment_shortcuts    Có bật phím tắt kiểm duyệt bình luận
 *                                        cho người dùng hay không. Chấp nhận 'true' hoặc 'false'
 *                                        dưới dạng chuỗi, không phải boolean. Mặc định 'false'.
 *     @type string $admin_color          Bảng màu admin cho người dùng. Mặc định 'fresh'.
 *     @type bool   $use_ssl              Có nên luôn truy cập admin qua
 *                                        https hay không. Mặc định false.
 *     @type string $user_registered      Ngày người dùng đăng ký theo UTC. Định dạng 'Y-m-d H:i:s'.
 *     @type string $user_activation_key  Khóa đặt lại mật khẩu. Mặc định rỗng.
 *     @type bool   $spam                 Chỉ Multisite. Người dùng có bị đánh dấu là spam hay không.
 *                                        Mặc định false.
 *     @type string $show_admin_bar_front Có hiển thị Admin Bar cho người dùng
 *                                        ở giao diện front-end hay không. Chấp nhận 'true' hoặc 'false'
 *                                        dưới dạng chuỗi, không phải boolean. Mặc định 'true'.
 *     @type string $role                 Vai trò người dùng.
 *     @type string $locale               Ngôn ngữ người dùng. Mặc định rỗng.
 *     @type array  $meta_input           Mảng giá trị meta người dùng tùy chỉnh theo khóa meta.
 *                                        Mặc định rỗng.
 * }
 * @return int|WP_Error ID của người dùng mới được tạo hoặc đối tượng WP_Error nếu người dùng không thể
 *                      được tạo.
 */
function wp_insert_user( $userdata ) {
	global $wpdb;

	if ( $userdata instanceof stdClass ) {
		$userdata = get_object_vars( $userdata );
	} elseif ( $userdata instanceof WP_User ) {
		$userdata = $userdata->to_array();
	}

	// Chúng ta đang cập nhật hay tạo mới?
	if ( ! empty( $userdata['ID'] ) ) {
		$user_id       = (int) $userdata['ID'];
		$update        = true;
		$old_user_data = get_userdata( $user_id );

		if ( ! $old_user_data ) {
			return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
		}

		// Thêm slash cho email người dùng hiện tại để so sánh sau với email người dùng mới đã slash.
		$old_user_data->user_email = wp_slash( $old_user_data->user_email );

		// Đã hash trong wp_update_user(), văn bản thuần nếu gọi trực tiếp.
		$user_pass = ! empty( $userdata['user_pass'] ) ? $userdata['user_pass'] : $old_user_data->user_pass;
	} else {
		$update = false;
		// Hash mật khẩu.
		$user_pass = wp_hash_password( $userdata['user_pass'] );
	}

	$sanitized_user_login = sanitize_user( $userdata['user_login'], true );

	/**
	 * Lọc tên đăng nhập sau khi đã được làm sạch.
	 *
	 * Bộ lọc này được gọi trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $sanitized_user_login Tên đăng nhập sau khi đã được làm sạch.
	 */
	$pre_user_login = apply_filters( 'pre_user_login', $sanitized_user_login );

	// Xóa mọi ký tự không in được khỏi chuỗi đăng nhập để xem tên đăng nhập có bị rỗng hay không.
	$user_login = trim( $pre_user_login );

	// user_login phải từ 0 đến 60 ký tự.
	if ( empty( $user_login ) ) {
		return new WP_Error( 'empty_user_login', __( 'Cannot create a user with an empty login name.' ) );
	} elseif ( mb_strlen( $user_login ) > 60 ) {
		return new WP_Error( 'user_login_too_long', __( 'Username may not be longer than 60 characters.' ) );
	}

	if ( ! $update && username_exists( $user_login ) ) {
		return new WP_Error( 'existing_user_login', __( 'Sorry, that username already exists!' ) );
	}

	/**
	 * Lọc danh sách các tên đăng nhập không được phép.
	 *
	 * @since 4.4.0
	 *
	 * @param array $usernames Mảng các tên đăng nhập không được phép.
	 */
	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

	if ( in_array( strtolower( $user_login ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		return new WP_Error( 'invalid_username', __( 'Sorry, that username is not allowed.' ) );
	}

	/*
	 * Nếu nicename được cung cấp, xóa các ký tự người dùng không an toàn trước khi sử dụng.
	 * Nếu không, xây dựng nicename từ user_login.
	 */
	if ( ! empty( $userdata['user_nicename'] ) ) {
		$user_nicename = sanitize_user( $userdata['user_nicename'], true );
	} else {
		$user_nicename = mb_substr( $user_login, 0, 50 );
	}

	$user_nicename = sanitize_title( $user_nicename );

	/**
	 * Lọc nicename của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $user_nicename Nicename của người dùng.
	 */
	$user_nicename = apply_filters( 'pre_user_nicename', $user_nicename );

	if ( mb_strlen( $user_nicename ) > 50 ) {
		return new WP_Error( 'user_nicename_too_long', __( 'Nicename may not be longer than 50 characters.' ) );
	}

	$user_nicename_check = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1", $user_nicename, $user_login ) );

	if ( $user_nicename_check ) {
		$suffix = 2;
		while ( $user_nicename_check ) {
			// user_nicename cho phép 50 ký tự. Trừ một cho dấu gạch nối, cộng độ dài của hậu tố.
			$base_length         = 49 - mb_strlen( $suffix );
			$alt_user_nicename   = mb_substr( $user_nicename, 0, $base_length ) . "-$suffix";
			$user_nicename_check = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1", $alt_user_nicename, $user_login ) );
			++$suffix;
		}
		$user_nicename = $alt_user_nicename;
	}

	$raw_user_email = empty( $userdata['user_email'] ) ? '' : $userdata['user_email'];

	/**
	 * Lọc email của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $raw_user_email Email của người dùng.
	 */
	$user_email = apply_filters( 'pre_user_email', $raw_user_email );

	/*
	 * Nếu không có cập nhật, chỉ cần kiểm tra `email_exists`. Nếu có cập nhật,
	 * kiểm tra xem email hiện tại và email mới có giống nhau không, và kiểm tra
	 * `email_exists` tương ứng.
	 */
	if ( ( ! $update || ( ! empty( $old_user_data ) && 0 !== strcasecmp( $user_email, $old_user_data->user_email ) ) )
		&& ! defined( 'WP_IMPORTING' )
		&& email_exists( $user_email )
	) {
		return new WP_Error( 'existing_user_email', __( 'Sorry, that email address is already used!' ) );
	}

	$raw_user_url = empty( $userdata['user_url'] ) ? '' : $userdata['user_url'];

	/**
	 * Lọc URL của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $raw_user_url URL của người dùng.
	 */
	$user_url = apply_filters( 'pre_user_url', $raw_user_url );

	if ( mb_strlen( $user_url ) > 100 ) {
		return new WP_Error( 'user_url_too_long', __( 'User URL may not be longer than 100 characters.' ) );
	}

	$user_registered = empty( $userdata['user_registered'] ) ? gmdate( 'Y-m-d H:i:s' ) : $userdata['user_registered'];

	$user_activation_key = empty( $userdata['user_activation_key'] ) ? '' : $userdata['user_activation_key'];

	if ( ! empty( $userdata['spam'] ) && ! is_multisite() ) {
		return new WP_Error( 'no_spam', __( 'Sorry, marking a user as spam is only supported on Multisite.' ) );
	}

	$spam = empty( $userdata['spam'] ) ? 0 : (bool) $userdata['spam'];

	// Lưu trữ các giá trị để lưu vào user meta.
	$meta = array();

	$nickname = empty( $userdata['nickname'] ) ? $user_login : $userdata['nickname'];

	/**
	 * Lọc biệt danh của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $nickname Biệt danh của người dùng.
	 */
	$meta['nickname'] = apply_filters( 'pre_user_nickname', $nickname );

	$first_name = empty( $userdata['first_name'] ) ? '' : $userdata['first_name'];

	/**
	 * Lọc tên của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $first_name Tên của người dùng.
	 */
	$meta['first_name'] = apply_filters( 'pre_user_first_name', $first_name );

	$last_name = empty( $userdata['last_name'] ) ? '' : $userdata['last_name'];

	/**
	 * Lọc họ của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $last_name Họ của người dùng.
	 */
	$meta['last_name'] = apply_filters( 'pre_user_last_name', $last_name );

	if ( empty( $userdata['display_name'] ) ) {
		if ( $update ) {
			$display_name = $user_login;
		} elseif ( $meta['first_name'] && $meta['last_name'] ) {
			$display_name = sprintf(
				/* translators: 1: User's first name, 2: Last name. */
				_x( '%1$s %2$s', 'Display name based on first name and last name' ),
				$meta['first_name'],
				$meta['last_name']
			);
		} elseif ( $meta['first_name'] ) {
			$display_name = $meta['first_name'];
		} elseif ( $meta['last_name'] ) {
			$display_name = $meta['last_name'];
		} else {
			$display_name = $user_login;
		}
	} else {
		$display_name = $userdata['display_name'];
	}

	/**
	 * Lọc tên hiển thị của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $display_name Tên hiển thị của người dùng.
	 */
	$display_name = apply_filters( 'pre_user_display_name', $display_name );

	$description = empty( $userdata['description'] ) ? '' : $userdata['description'];

	/**
	 * Lọc mô tả của người dùng trước khi người dùng được tạo hoặc cập nhật.
	 *
	 * @since 2.0.3
	 *
	 * @param string $description Mô tả của người dùng.
	 */
	$meta['description'] = apply_filters( 'pre_user_description', $description );

	$meta['rich_editing'] = empty( $userdata['rich_editing'] ) ? 'true' : $userdata['rich_editing'];

	$meta['syntax_highlighting'] = empty( $userdata['syntax_highlighting'] ) ? 'true' : $userdata['syntax_highlighting'];

	$meta['comment_shortcuts'] = empty( $userdata['comment_shortcuts'] ) || 'false' === $userdata['comment_shortcuts'] ? 'false' : 'true';

	$admin_color         = empty( $userdata['admin_color'] ) ? 'fresh' : $userdata['admin_color'];
	$meta['admin_color'] = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $admin_color );

	$meta['use_ssl'] = empty( $userdata['use_ssl'] ) ? '0' : '1';

	$meta['show_admin_bar_front'] = empty( $userdata['show_admin_bar_front'] ) ? 'true' : $userdata['show_admin_bar_front'];

	$meta['locale'] = isset( $userdata['locale'] ) ? $userdata['locale'] : '';

	$compacted = compact( 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'display_name' );
	$data      = wp_unslash( $compacted );

	if ( ! $update ) {
		$data = $data + compact( 'user_login' );
	}

	if ( is_multisite() ) {
		$data = $data + compact( 'spam' );
	}

	/**
	 * Lọc dữ liệu người dùng trước khi bản ghi được tạo hoặc cập nhật.
	 *
	 * Chỉ bao gồm dữ liệu trong bảng users, không bao gồm bất kỳ metadata người dùng nào.
	 *
	 * @since 4.9.0
	 * @since 5.8.0 Thêm tham số `$userdata`.
	 * @since 6.8.0 Mật khẩu người dùng giờ được hash bằng bcrypt theo mặc định thay vì phpass.
	 *
	 * @param array    $data {
	 *     Các giá trị và khóa cho người dùng.
	 *
	 *     @type string $user_login      Tên đăng nhập của người dùng. Chỉ bao gồm nếu $update == false.
	 *     @type string $user_pass       Mật khẩu của người dùng.
	 *     @type string $user_email      Email của người dùng.
	 *     @type string $user_url        URL của người dùng.
	 *     @type string $user_nicename   Nicename của người dùng. Mặc định là phiên bản an toàn URL của tên đăng nhập.
	 *     @type string $display_name    Tên hiển thị của người dùng.
	 *     @type string $user_registered Dấu thời gian MySQL mô tả thời điểm người dùng đăng ký. Mặc định là
	 *                                   dấu thời gian UTC hiện tại.
	 * }
	 * @param bool     $update   Có đang cập nhật người dùng thay vì tạo mới hay không.
	 * @param int|null $user_id  ID người dùng cần cập nhật, hoặc NULL nếu người dùng đang được tạo.
	 * @param array    $userdata Mảng dữ liệu thô được truyền vào wp_insert_user().
	 */
	$data = apply_filters( 'wp_pre_insert_user_data', $data, $update, ( $update ? $user_id : null ), $userdata );

	if ( empty( $data ) || ! is_array( $data ) ) {
		return new WP_Error( 'empty_data', __( 'Not enough data to create this user.' ) );
	}

	if ( $update ) {
		if ( $user_email !== $old_user_data->user_email || $user_pass !== $old_user_data->user_pass ) {
			$data['user_activation_key'] = '';
		}
		$wpdb->update( $wpdb->users, $data, array( 'ID' => $user_id ) );
	} else {
		$wpdb->insert( $wpdb->users, $data );
		$user_id = (int) $wpdb->insert_id;
	}

	$user = new WP_User( $user_id );

	/**
	 * Lọc các giá trị và khóa meta của người dùng ngay sau khi người dùng được tạo hoặc cập nhật
	 * và trước khi bất kỳ meta người dùng nào được chèn hoặc cập nhật.
	 *
	 * Không bao gồm các phương thức liên hệ. Chúng được thêm bằng `wp_get_user_contact_methods( $user )`.
	 *
	 * Đối với các trường meta tùy chỉnh, xem bộ lọc {@see 'insert_custom_user_meta'}.
	 *
	 * @since 4.4.0
	 * @since 5.8.0 Tham số `$userdata` đã được thêm vào.
	 *
	 * @param array $meta {
	 *     Các giá trị và khóa meta mặc định cho người dùng.
	 *
	 *     @type string   $nickname             Biệt danh của người dùng. Mặc định là tên đăng nhập.
	 *     @type string   $first_name           Tên của người dùng.
	 *     @type string   $last_name            Họ của người dùng.
	 *     @type string   $description          Mô tả của người dùng.
	 *     @type string   $rich_editing         Có bật trình soạn thảo rich-editor cho người dùng không. Mặc định 'true'.
	 *     @type string   $syntax_highlighting  Có bật trình soạn thảo mã nâng cao cho người dùng không. Mặc định 'true'.
	 *     @type string   $comment_shortcuts    Có bật phím tắt bàn phím cho người dùng không. Mặc định 'false'.
	 *     @type string   $admin_color          Bảng màu cho màn hình quản trị của người dùng. Mặc định 'fresh'.
	 *     @type int|bool $use_ssl              Có bắt buộc SSL trên khu vực quản trị của người dùng không. 0|false nếu SSL
	 *                                          không được bắt buộc.
	 *     @type string   $show_admin_bar_front Có hiển thị thanh quản trị ở giao diện người dùng không.
	 *                                          Mặc định 'true'.
	 *     @type string   $locale               Ngôn ngữ của người dùng. Mặc định trống.
	 * }
	 * @param WP_User $user     Đối tượng người dùng.
	 * @param bool    $update   Người dùng đang được cập nhật hay tạo mới.
	 * @param array   $userdata Mảng dữ liệu thô được truyền vào wp_insert_user().
	 */
	$meta = apply_filters( 'insert_user_meta', $meta, $user, $update, $userdata );

	$custom_meta = array();
	if ( array_key_exists( 'meta_input', $userdata ) && is_array( $userdata['meta_input'] ) && ! empty( $userdata['meta_input'] ) ) {
		$custom_meta = $userdata['meta_input'];
	}

	/**
	 * Lọc các giá trị và khóa meta tùy chỉnh của người dùng ngay sau khi người dùng được tạo hoặc cập nhật
	 * và trước khi bất kỳ meta người dùng nào được chèn hoặc cập nhật.
	 *
	 * Đối với các trường meta không tùy chỉnh, xem bộ lọc {@see 'insert_user_meta'}.
	 *
	 * @since 5.9.0
	 *
	 * @param array   $custom_meta Mảng các giá trị meta tùy chỉnh của người dùng theo khóa meta.
	 * @param WP_User $user        Đối tượng người dùng.
	 * @param bool    $update      Người dùng đang được cập nhật hay tạo mới.
	 * @param array   $userdata    Mảng dữ liệu thô được truyền vào wp_insert_user().
	 */
	$custom_meta = apply_filters( 'insert_custom_user_meta', $custom_meta, $user, $update, $userdata );

	$meta = array_merge( $meta, $custom_meta );

	if ( $update ) {
		// Cập nhật meta người dùng.
		foreach ( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}
	} else {
		// Thêm meta người dùng.
		foreach ( $meta as $key => $value ) {
			add_user_meta( $user_id, $key, $value );
		}
	}

	foreach ( wp_get_user_contact_methods( $user ) as $key => $value ) {
		if ( isset( $userdata[ $key ] ) ) {
			update_user_meta( $user_id, $key, $userdata[ $key ] );
		}
	}

	if ( isset( $userdata['role'] ) ) {
		$user->set_role( $userdata['role'] );
	} elseif ( ! $update ) {
		$user->set_role( get_option( 'default_role' ) );
	}

	clean_user_cache( $user_id );

	if ( $update ) {
		/**
		 * Kích hoạt ngay sau khi người dùng hiện có được cập nhật.
		 *
		 * @since 2.0.0
		 * @since 5.8.0 Tham số `$userdata` đã được thêm vào.
		 *
		 * @param int     $user_id       ID người dùng.
		 * @param WP_User $old_user_data Đối tượng chứa dữ liệu người dùng trước khi cập nhật.
		 * @param array   $userdata      Mảng dữ liệu thô được truyền vào wp_insert_user().
		 */
		do_action( 'profile_update', $user_id, $old_user_data, $userdata );

		if ( isset( $userdata['spam'] ) && $userdata['spam'] !== $old_user_data->spam ) {
			if ( '1' === $userdata['spam'] ) {
				/**
				 * Kích hoạt sau khi người dùng được đánh dấu là SPAM.
				 *
				 * @since 3.0.0
				 *
				 * @param int $user_id ID của người dùng được đánh dấu là SPAM.
				 */
				do_action( 'make_spam_user', $user_id );
			} else {
				/**
				 * Kích hoạt sau khi người dùng được đánh dấu là HAM. Ngược lại với SPAM.
				 *
				 * @since 3.0.0
				 *
				 * @param int $user_id ID của người dùng được đánh dấu là HAM.
				 */
				do_action( 'make_ham_user', $user_id );
			}
		}
	} else {
		/**
		 * Kích hoạt ngay sau khi người dùng mới được đăng ký.
		 *
		 * @since 1.5.0
		 * @since 5.8.0 Tham số `$userdata` đã được thêm vào.
		 *
		 * @param int   $user_id  ID người dùng.
		 * @param array $userdata Mảng dữ liệu thô được truyền vào wp_insert_user().
		 */
		do_action( 'user_register', $user_id, $userdata );
	}

	return $user_id;
}

/**
 * Cập nhật người dùng trong cơ sở dữ liệu.
 *
 * Có thể cập nhật mật khẩu của người dùng bằng cách chỉ định giá trị 'user_pass'
 * trong mảng tham số $userdata.
 *
 * Nếu mật khẩu của người dùng hiện tại đang được cập nhật, thì cookie sẽ được
 * xóa.
 *
 * @since 2.0.0
 *
 * @see wp_insert_user() Để biết các trường có thể thiết lập trong $userdata.
 *
 * @param array|object|WP_User $userdata Mảng dữ liệu người dùng hoặc đối tượng người dùng kiểu stdClass hoặc WP_User.
 * @return int|WP_Error ID của người dùng đã cập nhật hoặc đối tượng WP_Error nếu không thể cập nhật người dùng.
 */
function wp_update_user( $userdata ) {
	if ( $userdata instanceof stdClass ) {
		$userdata = get_object_vars( $userdata );
	} elseif ( $userdata instanceof WP_User ) {
		$userdata = $userdata->to_array();
	}

	$userdata_raw = $userdata;

	$user_id = isset( $userdata['ID'] ) ? (int) $userdata['ID'] : 0;
	if ( ! $user_id ) {
		return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
	}

	// Đầu tiên, lấy tất cả các trường gốc.
	$user_obj = get_userdata( $user_id );
	if ( ! $user_obj ) {
		return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
	}

	$user = $user_obj->to_array();

	// Thêm các trường tùy chỉnh bổ sung.
	foreach ( _get_additional_user_keys( $user_obj ) as $key ) {
		$user[ $key ] = get_user_meta( $user_id, $key, true );
	}

	// Escape dữ liệu lấy từ cơ sở dữ liệu.
	$user = add_magic_quotes( $user );

	if ( ! empty( $userdata['user_pass'] ) && $userdata['user_pass'] !== $user_obj->user_pass ) {
		// Nếu mật khẩu đang thay đổi, hash ngay bây giờ.
		$plaintext_pass        = $userdata['user_pass'];
		$userdata['user_pass'] = wp_hash_password( $userdata['user_pass'] );

		/**
		 * Lọc xem có gửi email thông báo thay đổi mật khẩu hay không.
		 *
		 * @since 4.3.0
		 *
		 * @see wp_insert_user() Để biết các trường `$user` và `$userdata`.
		 *
		 * @param bool  $send     Có gửi email hay không.
		 * @param array $user     Mảng người dùng gốc.
		 * @param array $userdata Mảng người dùng đã cập nhật.
		 */
		$send_password_change_email = apply_filters( 'send_password_change_email', true, $user, $userdata );
	}

	if ( isset( $userdata['user_email'] ) && $user['user_email'] !== $userdata['user_email'] ) {
		/**
		 * Lọc xem có gửi email thông báo thay đổi email hay không.
		 *
		 * @since 4.3.0
		 *
		 * @see wp_insert_user() Để biết các trường `$user` và `$userdata`.
		 *
		 * @param bool  $send     Có gửi email hay không.
		 * @param array $user     Mảng người dùng gốc.
		 * @param array $userdata Mảng người dùng đã cập nhật.
		 */
		$send_email_change_email = apply_filters( 'send_email_change_email', true, $user, $userdata );
	}

	clean_user_cache( $user_obj );

	// Gộp các trường cũ và mới, trường mới sẽ ghi đè trường cũ.
	$userdata = array_merge( $user, $userdata );
	$user_id  = wp_insert_user( $userdata );

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	$blog_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	$switched_locale = false;
	if ( ! empty( $send_password_change_email ) || ! empty( $send_email_change_email ) ) {
		$switched_locale = switch_to_user_locale( $user_id );
	}

	if ( ! empty( $send_password_change_email ) ) {
		/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$pass_change_text = __(
			'Hi ###USERNAME###,

This notice confirms that your password was changed on ###SITENAME###.

If you did not change your password, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
		);

		$pass_change_email = array(
			'to'      => $user['user_email'],
			/* translators: Password change notification email subject. %s: Site title. */
			'subject' => __( '[%s] Password Changed' ),
			'message' => $pass_change_text,
			'headers' => '',
		);

		/**
		 * Lọc nội dung email được gửi khi mật khẩu người dùng bị thay đổi.
		 *
		 * @since 4.3.0
		 *
		 * @param array $pass_change_email {
		 *     Được sử dụng để xây dựng wp_mail().
		 *
		 *     @type string $to      Người nhận dự kiến. Thêm email bằng chuỗi phân cách bởi dấu phẩy.
		 *     @type string $subject Chủ đề của email.
		 *     @type string $message Nội dung của email.
		 *         Các chuỗi sau có ý nghĩa đặc biệt và sẽ được thay thế động:
		 *         - ###USERNAME###    Tên đăng nhập của người dùng hiện tại.
		 *         - ###ADMIN_EMAIL### Email quản trị viên trong trường hợp không mong đợi.
		 *         - ###EMAIL###       Địa chỉ email của người dùng.
		 *         - ###SITENAME###    Tên của site.
		 *         - ###SITEURL###     URL đến site.
		 *     @type string $headers Tiêu đề. Thêm tiêu đề bằng chuỗi phân cách bởi dòng mới (\r\n).
		 * }
		 * @param array $user     Mảng người dùng gốc.
		 * @param array $userdata Mảng người dùng đã cập nhật.
		 */
		$pass_change_email = apply_filters( 'password_change_email', $pass_change_email, $user, $userdata );

		$pass_change_email['message'] = str_replace( '###USERNAME###', $user['user_login'], $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###ADMIN_EMAIL###', get_option( 'admin_email' ), $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###EMAIL###', $user['user_email'], $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###SITENAME###', $blog_name, $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###SITEURL###', home_url(), $pass_change_email['message'] );

		wp_mail( $pass_change_email['to'], sprintf( $pass_change_email['subject'], $blog_name ), $pass_change_email['message'], $pass_change_email['headers'] );
	}

	if ( ! empty( $send_email_change_email ) ) {
		/* translators: Do not translate USERNAME, ADMIN_EMAIL, NEW_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_change_text = __(
			'Hi ###USERNAME###,

This notice confirms that your email address on ###SITENAME### was changed to ###NEW_EMAIL###.

If you did not change your email, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
		);

		$email_change_email = array(
			'to'      => $user['user_email'],
			/* translators: Email change notification email subject. %s: Site title. */
			'subject' => __( '[%s] Email Changed' ),
			'message' => $email_change_text,
			'headers' => '',
		);

		/**
		 * Lọc nội dung email được gửi khi email người dùng bị thay đổi.
		 *
		 * @since 4.3.0
		 *
		 * @param array $email_change_email {
		 *     Được sử dụng để xây dựng wp_mail().
		 *
		 *     @type string $to      Người nhận dự kiến.
		 *     @type string $subject Chủ đề của email.
		 *     @type string $message Nội dung của email.
		 *         Các chuỗi sau có ý nghĩa đặc biệt và sẽ được thay thế động:
		 *         - ###USERNAME###    Tên đăng nhập của người dùng hiện tại.
		 *         - ###ADMIN_EMAIL### Email quản trị viên trong trường hợp không mong đợi.
		 *         - ###NEW_EMAIL###   Địa chỉ email mới.
		 *         - ###EMAIL###       Địa chỉ email cũ.
		 *         - ###SITENAME###    Tên của site.
		 *         - ###SITEURL###     URL đến site.
		 *     @type string $headers Tiêu đề.
		 * }
		 * @param array $user     Mảng người dùng gốc.
		 * @param array $userdata Mảng người dùng đã cập nhật.
		 */
		$email_change_email = apply_filters( 'email_change_email', $email_change_email, $user, $userdata );

		$email_change_email['message'] = str_replace( '###USERNAME###', $user['user_login'], $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###ADMIN_EMAIL###', get_option( 'admin_email' ), $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###NEW_EMAIL###', $userdata['user_email'], $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###EMAIL###', $user['user_email'], $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###SITENAME###', $blog_name, $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###SITEURL###', home_url(), $email_change_email['message'] );

		wp_mail( $email_change_email['to'], sprintf( $email_change_email['subject'], $blog_name ), $email_change_email['message'], $email_change_email['headers'] );
	}

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	// Cập nhật cookie nếu mật khẩu đã thay đổi.
	$current_user = wp_get_current_user();
	if ( $current_user->ID === $user_id ) {
		if ( isset( $plaintext_pass ) ) {
			/*
			 * Ở đây chúng ta tính thời hạn hết hạn của cookie xác thực hiện tại và so sánh với thời hạn mặc định.
			 * Nếu lớn hơn, thì chúng ta biết người dùng đã chọn 'Ghi nhớ tôi' khi đăng nhập.
			 */
			$logged_in_cookie = wp_parse_auth_cookie( '', 'logged_in' );
			/** Bộ lọc này được ghi chú trong wp-includes/pluggable.php */
			$default_cookie_life = apply_filters( 'auth_cookie_expiration', ( 2 * DAY_IN_SECONDS ), $user_id, false );

			wp_clear_auth_cookie();

			$remember = false;
			$token    = '';

			if ( false !== $logged_in_cookie ) {
				$token = $logged_in_cookie['token'];
			}

			if ( false !== $logged_in_cookie && ( (int) $logged_in_cookie['expiration'] - time() ) > $default_cookie_life ) {
				$remember = true;
			}

			wp_set_auth_cookie( $user_id, $remember, '', $token );
		}
	}

	/**
	 * Kích hoạt sau khi người dùng đã được cập nhật và email đã được gửi.
	 *
	 * @since 6.3.0
	 *
	 * @param int   $user_id      ID của người dùng vừa được cập nhật.
	 * @param array $userdata     Mảng dữ liệu người dùng đã được cập nhật.
	 * @param array $userdata_raw Mảng dữ liệu người dùng chưa chỉnh sửa đã được cập nhật.
	 */
	do_action( 'wp_update_user', $user_id, $userdata, $userdata_raw );

	return $user_id;
}

/**
 * Cung cấp cách đơn giản hơn để chèn người dùng vào cơ sở dữ liệu.
 *
 * Tạo người dùng mới chỉ với tên đăng nhập, mật khẩu và email. Để tạo
 * người dùng phức tạp hơn, sử dụng wp_insert_user() để chỉ định thêm thông tin.
 *
 * @since 2.0.0
 *
 * @see wp_insert_user() Cách hoàn chỉnh hơn để tạo người dùng mới.
 *
 * @param string $username Tên đăng nhập của người dùng.
 * @param string $password Mật khẩu của người dùng.
 * @param string $email    Tùy chọn. Email của người dùng. Mặc định trống.
 * @return int|WP_Error ID của người dùng mới tạo hoặc đối tượng WP_Error nếu không thể
 *                      tạo người dùng.
 */
function wp_create_user(
	$username,
	#[\SensitiveParameter]
	$password,
	$email = ''
) {
	$user_login = wp_slash( $username );
	$user_email = wp_slash( $email );
	$user_pass  = $password;

	$userdata = compact( 'user_login', 'user_email', 'user_pass' );
	return wp_insert_user( $userdata );
}

/**
 * Trả về danh sách các khóa meta sẽ (có thể) được điền trong wp_update_user().
 *
 * Danh sách các khóa được trả về qua hàm này phụ thuộc vào sự hiện diện
 * của các khóa đó trong dữ liệu meta người dùng sẽ được thiết lập.
 *
 * @since 3.3.0
 * @access private
 *
 * @param WP_User $user Đối tượng WP_User.
 * @return string[] Danh sách các khóa người dùng sẽ được điền trong wp_update_user().
 */
function _get_additional_user_keys( $user ) {
	$keys = array( 'first_name', 'last_name', 'nickname', 'description', 'rich_editing', 'syntax_highlighting', 'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front', 'locale' );
	return array_merge( $keys, array_keys( wp_get_user_contact_methods( $user ) ) );
}

/**
 * Thiết lập các phương thức liên hệ của người dùng.
 *
 * Các phương thức liên hệ mặc định đã bị loại bỏ từ phiên bản 3.6. Một bộ lọc quyết định các phương thức liên hệ.
 *
 * @since 3.7.0
 *
 * @param WP_User|null $user Tùy chọn. Đối tượng WP_User.
 * @return string[] Mảng nhãn phương thức liên hệ theo khóa phương thức liên hệ.
 */
function wp_get_user_contact_methods( $user = null ) {
	$methods = array();
	if ( get_site_option( 'initial_db_version' ) < 23588 ) {
		$methods = array(
			'aim'    => __( 'AIM' ),
			'yim'    => __( 'Yahoo IM' ),
			'jabber' => __( 'Jabber / Google Talk' ),
		);
	}

	/**
	 * Lọc các phương thức liên hệ của người dùng.
	 *
	 * @since 2.9.0
	 *
	 * @param string[]     $methods Mảng nhãn phương thức liên hệ theo khóa phương thức liên hệ.
	 * @param WP_User|null $user    Đối tượng WP_User hoặc null nếu không được cung cấp.
	 */
	return apply_filters( 'user_contactmethods', $methods, $user );
}

/**
 * Hàm private cũ để thiết lập các phương thức liên hệ của người dùng.
 *
 * Sử dụng wp_get_user_contact_methods() thay thế.
 *
 * @since 2.9.0
 * @access private
 *
 * @param WP_User|null $user Tùy chọn. Đối tượng WP_User. Mặc định null.
 * @return string[] Mảng nhãn phương thức liên hệ theo khóa phương thức liên hệ.
 */
function _wp_get_user_contactmethods( $user = null ) {
	return wp_get_user_contact_methods( $user );
}

/**
 * Lấy văn bản gợi ý cách tạo mật khẩu mạnh.
 *
 * @since 4.1.0
 *
 * @return string Văn bản gợi ý mật khẩu.
 */
function wp_get_password_hint() {
	$hint = __( 'Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).' );

	/**
	 * Lọc văn bản mô tả chính sách độ phức tạp mật khẩu của site.
	 *
	 * @since 4.1.0
	 *
	 * @param string $hint Văn bản gợi ý mật khẩu.
	 */
	return apply_filters( 'password_hint', $hint );
}

/**
 * Tạo, lưu trữ, sau đó trả về khóa đặt lại mật khẩu cho người dùng.
 *
 * @since 4.4.0
 *
 * @param WP_User $user Người dùng cần lấy khóa đặt lại mật khẩu.
 * @return string|WP_Error Khóa đặt lại mật khẩu khi thành công. WP_Error khi lỗi.
 */
function get_password_reset_key( $user ) {
	if ( ! ( $user instanceof WP_User ) ) {
		return new WP_Error( 'invalidcombo', __( '<strong>Error:</strong> There is no account with that username or email address.' ) );
	}

	/**
	 * Kích hoạt trước khi mật khẩu mới được truy xuất.
	 *
	 * Sử dụng hook {@see 'retrieve_password'} thay thế.
	 *
	 * @since 1.5.0
	 * @deprecated 1.5.1 Viết sai chính tả. Sử dụng hook {@see 'retrieve_password'} thay thế.
	 *
	 * @param string $user_login Tên đăng nhập của người dùng.
	 */
	do_action_deprecated( 'retreive_password', array( $user->user_login ), '1.5.1', 'retrieve_password' );

	/**
	 * Kích hoạt trước khi mật khẩu mới được truy xuất.
	 *
	 * @since 1.5.1
	 *
	 * @param string $user_login Tên đăng nhập của người dùng.
	 */
	do_action( 'retrieve_password', $user->user_login );

	$password_reset_allowed = wp_is_password_reset_allowed_for_user( $user );
	if ( ! $password_reset_allowed ) {
		return new WP_Error( 'no_password_reset', __( 'Password reset is not allowed for this user' ) );
	} elseif ( is_wp_error( $password_reset_allowed ) ) {
		return $password_reset_allowed;
	}

	// Tạo giá trị ngẫu nhiên cho khóa đặt lại mật khẩu.
	$key = wp_generate_password( 20, false );

	/**
	 * Kích hoạt khi khóa đặt lại mật khẩu được tạo.
	 *
	 * @since 2.5.0
	 *
	 * @param string $user_login Tên đăng nhập của người dùng.
	 * @param string $key        Khóa đặt lại mật khẩu đã tạo.
	 */
	do_action( 'retrieve_password_key', $user->user_login, $key );

	$hashed = time() . ':' . wp_fast_hash( $key );

	$key_saved = wp_update_user(
		array(
			'ID'                  => $user->ID,
			'user_activation_key' => $hashed,
		)
	);

	if ( is_wp_error( $key_saved ) ) {
		return $key_saved;
	}

	return $key;
}

/**
 * Truy xuất một dòng người dùng dựa trên khóa đặt lại mật khẩu và tên đăng nhập.
 *
 * Một khóa được coi là 'hết hạn' nếu nó khớp chính xác với giá trị của
 * trường user_activation_key, thay vì được so khớp sau khi đi qua quá trình
 * hash. Trường này hiện đã được hash; các giá trị cũ không còn được chấp nhận
 * nhưng có mã WP_Error khác để có thể cung cấp phản hồi tốt cho người dùng.
 *
 * @since 3.1.0
 *
 * @param string $key       Khóa đặt lại mật khẩu.
 * @param string $login     Tên đăng nhập của người dùng.
 * @return WP_User|WP_Error Đối tượng WP_User khi thành công, đối tượng WP_Error cho khóa không hợp lệ hoặc hết hạn.
 */
function check_password_reset_key(
	#[\SensitiveParameter]
	$key,
	$login
) {
	$key = preg_replace( '/[^a-z0-9]/i', '', $key );

	if ( empty( $key ) || ! is_string( $key ) ) {
		return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
	}

	if ( empty( $login ) || ! is_string( $login ) ) {
		return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
	}

	$user = get_user_by( 'login', $login );

	if ( ! $user ) {
		return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
	}

	/**
	 * Lọc thời gian hết hạn của khóa đặt lại mật khẩu.
	 *
	 * @since 4.3.0
	 *
	 * @param int $expiration Thời gian hết hạn tính bằng giây.
	 */
	$expiration_duration = apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );

	if ( str_contains( $user->user_activation_key, ':' ) ) {
		list( $pass_request_time, $pass_key ) = explode( ':', $user->user_activation_key, 2 );
		$expiration_time                      = $pass_request_time + $expiration_duration;
	} else {
		$pass_key        = $user->user_activation_key;
		$expiration_time = false;
	}

	if ( ! $pass_key ) {
		return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
	}

	$hash_is_correct = wp_verify_fast_hash( $key, $pass_key );

	if ( $hash_is_correct && $expiration_time && time() < $expiration_time ) {
		return $user;
	} elseif ( $hash_is_correct && $expiration_time ) {
		// Khóa có thời gian hết hạn đã qua.
		return new WP_Error( 'expired_key', __( 'Invalid key.' ) );
	}

	if ( hash_equals( $user->user_activation_key, $key ) || ( $hash_is_correct && ! $expiration_time ) ) {
		$return  = new WP_Error( 'expired_key', __( 'Invalid key.' ) );
		$user_id = $user->ID;

		/**
		 * Lọc giá trị trả về của check_password_reset_key() khi sử dụng
		 * khóa kiểu cũ hoặc khóa đã hết hạn.
		 *
		 * @since 3.7.0 Trước đây khóa dạng văn bản thuần được lưu trong cơ sở dữ liệu.
		 * @since 4.3.0 Trước đây hash khóa được lưu mà không có thời gian hết hạn.
		 *
		 * @param WP_Error $return  Đối tượng WP_Error biểu thị khóa đã hết hạn.
		 *                          Trả về đối tượng WP_User để xác thực khóa.
		 * @param int      $user_id ID người dùng khớp.
		 */
		return apply_filters( 'password_reset_key_expired', $return, $user_id );
	}

	return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
}

/**
 * Xử lý việc gửi email truy xuất mật khẩu cho người dùng.
 *
 * @since 2.5.0
 * @since 5.7.0 Thêm tham số `$user_login`.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $user_login Tùy chọn. Tên đăng nhập để gửi email truy xuất mật khẩu.
 *                           Mặc định là `$_POST['user_login']` nếu không được thiết lập.
 * @return true|WP_Error True khi hoàn tất, đối tượng WP_Error khi lỗi.
 */
function retrieve_password( $user_login = '' ) {
	$errors    = new WP_Error();
	$user_data = false;

	// Sử dụng $user_login được truyền nếu có, nếu không sử dụng $_POST['user_login'].
	if ( ! $user_login && ! empty( $_POST['user_login'] ) ) {
		$user_login = $_POST['user_login'];
	}

	$user_login = trim( wp_unslash( $user_login ) );

	if ( empty( $user_login ) ) {
		$errors->add( 'empty_username', __( '<strong>Error:</strong> Please enter a username or email address.' ) );
	} elseif ( strpos( $user_login, '@' ) ) {
		$user_data = get_user_by( 'email', $user_login );

		if ( empty( $user_data ) ) {
			$user_data = get_user_by( 'login', $user_login );
		}

		if ( empty( $user_data ) ) {
			$errors->add( 'invalid_email', __( '<strong>Error:</strong> There is no account with that username or email address.' ) );
		}
	} else {
		$user_data = get_user_by( 'login', $user_login );
	}

	/**
	 * Lọc dữ liệu người dùng trong quá trình yêu cầu đặt lại mật khẩu.
	 *
	 * Cho phép, ví dụ, xác thực tùy chỉnh sử dụng dữ liệu khác ngoài tên đăng nhập hoặc địa chỉ email.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_User|false $user_data Đối tượng WP_User nếu tìm thấy, false nếu người dùng không tồn tại.
	 * @param WP_Error      $errors    Đối tượng WP_Error chứa các lỗi được tạo ra
	 *                                 khi sử dụng thông tin xác thực không hợp lệ.
	 */
	$user_data = apply_filters( 'lostpassword_user_data', $user_data, $errors );

	/**
	 * Kích hoạt trước khi các lỗi được trả về từ yêu cầu đặt lại mật khẩu.
	 *
	 * @since 2.1.0
	 * @since 4.4.0 Thêm tham số `$errors`.
	 * @since 5.4.0 Thêm tham số `$user_data`.
	 *
	 * @param WP_Error      $errors    Đối tượng WP_Error chứa các lỗi được tạo ra
	 *                                 khi sử dụng thông tin xác thực không hợp lệ.
	 * @param WP_User|false $user_data Đối tượng WP_User nếu tìm thấy, false nếu người dùng không tồn tại.
	 */
	do_action( 'lostpassword_post', $errors, $user_data );

	/**
	 * Lọc các lỗi gặp phải trong yêu cầu đặt lại mật khẩu.
	 *
	 * Đối tượng WP_Error đã lọc có thể, ví dụ, chứa lỗi cho tên đăng nhập
	 * hoặc địa chỉ email không hợp lệ. Đối tượng WP_Error nên luôn được trả về,
	 * nhưng có thể có hoặc không chứa lỗi.
	 *
	 * Nếu có bất kỳ lỗi nào trong $errors, yêu cầu đặt lại mật khẩu sẽ bị hủy.
	 *
	 * @since 5.5.0
	 *
	 * @param WP_Error      $errors    Đối tượng WP_Error chứa các lỗi được tạo ra
	 *                                 khi sử dụng thông tin xác thực không hợp lệ.
	 * @param WP_User|false $user_data Đối tượng WP_User nếu tìm thấy, false nếu người dùng không tồn tại.
	 */
	$errors = apply_filters( 'lostpassword_errors', $errors, $user_data );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	if ( ! $user_data ) {
		$errors->add( 'invalidcombo', __( '<strong>Error:</strong> There is no account with that username or email address.' ) );
		return $errors;
	}

	/**
	 * Lọc xem có gửi email truy xuất mật khẩu hay không.
	 *
	 * Trả về false để vô hiệu hóa việc gửi email.
	 *
	 * @since 6.0.0
	 *
	 * @param bool    $send       Có gửi email hay không.
	 * @param string  $user_login Tên đăng nhập của người dùng.
	 * @param WP_User $user_data  Đối tượng WP_User.
	 */
	if ( ! apply_filters( 'send_retrieve_password_email', true, $user_login, $user_data ) ) {
		return true;
	}

	// Định nghĩa lại user_login đảm bảo chúng ta trả về đúng chữ hoa/thường trong email.
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
	$key        = get_password_reset_key( $user_data );

	if ( is_wp_error( $key ) ) {
		return $key;
	}

	// Bản địa hóa nội dung tin nhắn đặt lại mật khẩu cho người dùng.
	$locale = get_user_locale( $user_data );

	$switched_locale = switch_to_user_locale( $user_data->ID );

	if ( is_multisite() ) {
		$site_name = get_network()->site_name;
	} else {
		/*
		 * Tùy chọn blogname được escape bằng esc_html khi đưa vào cơ sở dữ liệu
		 * trong sanitize_option. Chúng ta muốn đảo ngược điều này cho phần văn bản thuần của email.
		 */
		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	$message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
	/* translators: %s: Site name. */
	$message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
	/* translators: %s: User login. */
	$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, ignore this email and nothing will happen.' ) . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";

	/*
	 * Vì một số tên đăng nhập kết thúc bằng dấu chấm, điều này có thể tạo ra URL mơ hồ
	 * kết thúc bằng dấu chấm. Để tránh sự mơ hồ, đảm bảo rằng tên đăng nhập không phải là tham số
	 * truy vấn cuối cùng trong URL. Nếu di chuyển nó đến cuối, dấu chấm cuối cần được escape.
	 *
	 * @see https://core.trac.wordpress.org/tickets/42957
	 */
	$message .= network_site_url( 'wp-login.php?login=' . rawurlencode( $user_login ) . "&key=$key&action=rp", 'login' ) . '&wp_lang=' . $locale . "\r\n\r\n";

	if ( ! is_user_logged_in() ) {
		$requester_ip = $_SERVER['REMOTE_ADDR'];
		if ( $requester_ip ) {
			$message .= sprintf(
				/* translators: %s: IP address of password reset requester. */
				__( 'This password reset request originated from the IP address %s.' ),
				$requester_ip
			) . "\r\n";
		}
	}

	/* translators: Password reset notification email subject. %s: Site title. */
	$title = sprintf( __( '[%s] Password Reset' ), $site_name );

	/**
	 * Lọc chủ đề email đặt lại mật khẩu.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Thêm tham số `$user_login` và `$user_data`.
	 *
	 * @param string  $title      Chủ đề email.
	 * @param string  $user_login Tên đăng nhập của người dùng.
	 * @param WP_User $user_data  Đối tượng WP_User.
	 */
	$title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

	/**
	 * Lọc nội dung thân email đặt lại mật khẩu.
	 *
	 * Nếu nội dung đã lọc trống, email đặt lại mật khẩu sẽ không được gửi.
	 *
	 * @since 2.8.0
	 * @since 4.1.0 Thêm tham số `$user_login` và `$user_data`.
	 *
	 * @param string  $message    Nội dung email.
	 * @param string  $key        Khóa kích hoạt.
	 * @param string  $user_login Tên đăng nhập của người dùng.
	 * @param WP_User $user_data  Đối tượng WP_User.
	 */
	$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

	// Ngắn mạch khi giá trị $message là falsey để tương thích ngược.
	if ( ! $message ) {
		return true;
	}

	/*
	 * Gói các tham số email thông báo đơn lẻ vào một mảng
	 * để truyền chúng vào bộ lọc retrieve_password_notification_email.
	 */
	$defaults = array(
		'to'      => $user_email,
		'subject' => $title,
		'message' => $message,
		'headers' => '',
	);

	/**
	 * Lọc nội dung email thông báo đặt lại mật khẩu được gửi cho người dùng.
	 *
	 * @since 6.0.0
	 *
	 * @param array $defaults {
	 *     Các tham số email thông báo mặc định. Được sử dụng để xây dựng wp_mail().
	 *
	 *     @type string $to      Người nhận dự kiến - địa chỉ email người dùng.
	 *     @type string $subject Chủ đề của email.
	 *     @type string $message Nội dung của email.
	 *     @type string $headers Tiêu đề của email.
	 * }
	 * @param string  $key        Khóa kích hoạt.
	 * @param string  $user_login Tên đăng nhập của người dùng.
	 * @param WP_User $user_data  Đối tượng WP_User.
	 */
	$notification_email = apply_filters( 'retrieve_password_notification_email', $defaults, $key, $user_login, $user_data );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	if ( is_array( $notification_email ) ) {
		// Bắt buộc thứ tự khóa và gộp giá trị mặc định trong trường hợp có giá trị bị thiếu trong mảng đã lọc.
		$notification_email = array_merge( $defaults, $notification_email );
	} else {
		$notification_email = $defaults;
	}

	list( $to, $subject, $message, $headers ) = array_values( $notification_email );

	$subject = wp_specialchars_decode( $subject );

	if ( ! wp_mail( $to, $subject, $message, $headers ) ) {
		$errors->add(
			'retrieve_password_email_failure',
			sprintf(
				/* translators: %s: Documentation URL. */
				__( '<strong>Error:</strong> The email could not be sent. Your site may not be correctly configured to send emails. <a href="%s">Get support for resetting your password</a>.' ),
				esc_url( __( 'https://wordpress.org/documentation/article/reset-your-password/' ) )
			)
		);
		return $errors;
	}

	return true;
}

/**
 * Xử lý việc đặt lại mật khẩu của người dùng.
 *
 * @since 2.5.0
 *
 * @param WP_User $user     Người dùng.
 * @param string  $new_pass Mật khẩu mới cho người dùng dạng văn bản thuần.
 */
function reset_password(
	$user,
	#[\SensitiveParameter]
	$new_pass
) {
	/**
	 * Kích hoạt trước khi mật khẩu người dùng được đặt lại.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_User $user     Người dùng.
	 * @param string  $new_pass Mật khẩu mới của người dùng.
	 */
	do_action( 'password_reset', $user, $new_pass );

	wp_set_password( $new_pass, $user->ID );
	update_user_meta( $user->ID, 'default_password_nag', false );

	/**
	 * Kích hoạt sau khi mật khẩu người dùng được đặt lại.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_User $user     Người dùng.
	 * @param string  $new_pass Mật khẩu mới của người dùng.
	 */
	do_action( 'after_password_reset', $user, $new_pass );
}

/**
 * Xử lý việc đăng ký người dùng mới.
 *
 * @since 2.5.0
 *
 * @param string $user_login Tên đăng nhập của người dùng.
 * @param string $user_email Địa chỉ email của người dùng để gửi mật khẩu và thêm.
 * @return int|WP_Error ID người dùng hoặc lỗi khi thất bại.
 */
function register_new_user( $user_login, $user_email ) {
	$errors = new WP_Error();

	$sanitized_user_login = sanitize_user( $user_login );
	/**
	 * Lọc địa chỉ email của người dùng đang được đăng ký.
	 *
	 * @since 2.1.0
	 *
	 * @param string $user_email Địa chỉ email của người dùng mới.
	 */
	$user_email = apply_filters( 'user_registration_email', $user_email );

	// Kiểm tra tên đăng nhập.
	if ( '' === $sanitized_user_login ) {
		$errors->add( 'empty_username', __( '<strong>Error:</strong> Please enter a username.' ) );
	} elseif ( ! validate_username( $user_login ) ) {
		$errors->add( 'invalid_username', __( '<strong>Error:</strong> This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
		$sanitized_user_login = '';
	} elseif ( username_exists( $sanitized_user_login ) ) {
		$errors->add( 'username_exists', __( '<strong>Error:</strong> This username is already registered. Please choose another one.' ) );
	} else {
		/** Bộ lọc này được ghi chú trong wp-includes/user.php */
		$illegal_user_logins = (array) apply_filters( 'illegal_user_logins', array() );
		if ( in_array( strtolower( $sanitized_user_login ), array_map( 'strtolower', $illegal_user_logins ), true ) ) {
			$errors->add( 'invalid_username', __( '<strong>Error:</strong> Sorry, that username is not allowed.' ) );
		}
	}

	// Kiểm tra địa chỉ email.
	if ( '' === $user_email ) {
		$errors->add( 'empty_email', __( '<strong>Error:</strong> Please type your email address.' ) );
	} elseif ( ! is_email( $user_email ) ) {
		$errors->add( 'invalid_email', __( '<strong>Error:</strong> The email address is not correct.' ) );
		$user_email = '';
	} elseif ( email_exists( $user_email ) ) {
		$errors->add(
			'email_exists',
			sprintf(
				/* translators: %s: Link to the login page. */
				__( '<strong>Error:</strong> This email address is already registered. <a href="%s">Log in</a> with this address or choose another one.' ),
				wp_login_url()
			)
		);
	}

	/**
	 * Kích hoạt khi gửi dữ liệu form đăng ký, trước khi người dùng được tạo.
	 *
	 * @since 2.1.0
	 *
	 * @param string   $sanitized_user_login Tên đăng nhập đã gửi sau khi được làm sạch.
	 * @param string   $user_email           Email đã gửi.
	 * @param WP_Error $errors               Chứa các lỗi với tên đăng nhập và email đã gửi,
	 *                                       ví dụ, trường trống, tên đăng nhập hoặc email không hợp lệ,
	 *                                       hoặc tên đăng nhập hoặc email đã tồn tại.
	 */
	do_action( 'register_post', $sanitized_user_login, $user_email, $errors );

	/**
	 * Lọc các lỗi gặp phải khi người dùng mới đang được đăng ký.
	 *
	 * Đối tượng WP_Error đã lọc có thể, ví dụ, chứa lỗi cho tên đăng nhập
	 * hoặc địa chỉ email không hợp lệ hoặc đã tồn tại. Đối tượng WP_Error nên luôn được trả về,
	 * nhưng có thể có hoặc không chứa lỗi.
	 *
	 * Nếu có bất kỳ lỗi nào trong $errors, việc đăng ký người dùng sẽ bị hủy.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Error $errors               Đối tượng WP_Error chứa các lỗi gặp phải
	 *                                       trong quá trình đăng ký.
	 * @param string   $sanitized_user_login Tên đăng nhập của người dùng sau khi được làm sạch.
	 * @param string   $user_email           Email của người dùng.
	 */
	$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	$user_pass = wp_generate_password( 12, false );
	$user_id   = wp_create_user( $sanitized_user_login, $user_pass, $user_email );
	if ( ! $user_id || is_wp_error( $user_id ) ) {
		$errors->add(
			'registerfail',
			sprintf(
				/* translators: %s: Admin email address. */
				__( '<strong>Error:</strong> Could not register you&hellip; please contact the <a href="mailto:%s">site admin</a>!' ),
				get_option( 'admin_email' )
			)
		);
		return $errors;
	}

	update_user_meta( $user_id, 'default_password_nag', true ); // Thiết lập thông báo nhắc thay đổi mật khẩu.

	if ( ! empty( $_COOKIE['wp_lang'] ) ) {
		$wp_lang = sanitize_text_field( $_COOKIE['wp_lang'] );
		if ( in_array( $wp_lang, get_available_languages(), true ) ) {
			update_user_meta( $user_id, 'locale', $wp_lang ); // Thiết lập ngôn ngữ người dùng nếu được định nghĩa khi đăng ký.
		}
	}

	/**
	 * Kích hoạt sau khi đăng ký người dùng mới đã được ghi nhận.
	 *
	 * @since 4.4.0
	 *
	 * @param int $user_id ID của người dùng mới đăng ký.
	 */
	do_action( 'register_new_user', $user_id );

	return $user_id;
}

/**
 * Khởi tạo thông báo email liên quan đến việc tạo người dùng mới.
 *
 * Thông báo được gửi cho cả quản trị viên site và người dùng mới được tạo.
 *
 * @since 4.4.0
 * @since 4.6.0 Chuyển đổi tham số `$notify` để chấp nhận 'user' cho việc gửi
 *              thông báo chỉ cho người dùng được tạo.
 *
 * @param int    $user_id ID của người dùng mới được tạo.
 * @param string $notify  Tùy chọn. Loại thông báo sẽ xảy ra. Chấp nhận 'admin'
 *                        hoặc chuỗi trống (chỉ quản trị viên), 'user', hoặc 'both' (quản trị viên và người dùng).
 *                        Mặc định 'both'.
 */
function wp_send_new_user_notifications( $user_id, $notify = 'both' ) {
	wp_new_user_notification( $user_id, null, $notify );
}

/**
 * Truy xuất mã thông báo phiên hiện tại từ cookie logged_in.
 *
 * @since 4.0.0
 *
 * @return string Mã thông báo.
 */
function wp_get_session_token() {
	$cookie = wp_parse_auth_cookie( '', 'logged_in' );
	return ! empty( $cookie['token'] ) ? $cookie['token'] : '';
}

/**
 * Truy xuất danh sách các phiên cho người dùng hiện tại.
 *
 * @since 4.0.0
 *
 * @return array Mảng các phiên.
 */
function wp_get_all_sessions() {
	$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
	return $manager->get_all();
}

/**
 * Xóa mã thông báo phiên hiện tại khỏi cơ sở dữ liệu.
 *
 * @since 4.0.0
 */
function wp_destroy_current_session() {
	$token = wp_get_session_token();
	if ( $token ) {
		$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
		$manager->destroy( $token );
	}
}

/**
 * Xóa tất cả mã thông báo phiên trừ phiên hiện tại của người dùng hiện tại khỏi cơ sở dữ liệu.
 *
 * @since 4.0.0
 */
function wp_destroy_other_sessions() {
	$token = wp_get_session_token();
	if ( $token ) {
		$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
		$manager->destroy_others( $token );
	}
}

/**
 * Xóa tất cả mã thông báo phiên của người dùng hiện tại khỏi cơ sở dữ liệu.
 *
 * @since 4.0.0
 */
function wp_destroy_all_sessions() {
	$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
	$manager->destroy_all();
}

/**
 * Lấy ID của tất cả người dùng không có vai trò trên site này.
 *
 * @since 4.4.0
 * @since 4.9.0 Tham số `$site_id` đã được thêm để hỗ trợ multisite.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|null $site_id Tùy chọn. ID site để lấy người dùng không có vai trò. Mặc định là site hiện tại.
 * @return string[] Mảng ID người dùng dạng chuỗi.
 */
function wp_get_users_with_no_role( $site_id = null ) {
	global $wpdb;

	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	$prefix = $wpdb->get_blog_prefix( $site_id );

	if ( is_multisite() && get_current_blog_id() !== $site_id ) {
		switch_to_blog( $site_id );
		$role_names = wp_roles()->get_names();
		restore_current_blog();
	} else {
		$role_names = wp_roles()->get_names();
	}

	$regex = implode( '|', array_keys( $role_names ) );
	$regex = preg_replace( '/[^a-zA-Z_\|-]/', '', $regex );
	$users = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id
			FROM $wpdb->usermeta
			WHERE meta_key = '{$prefix}capabilities'
			AND meta_value NOT REGEXP %s",
			$regex
		)
	);

	return $users;
}

/**
 * Truy xuất đối tượng người dùng hiện tại.
 *
 * Sẽ thiết lập người dùng hiện tại, nếu người dùng hiện tại chưa được thiết lập. Người dùng hiện tại
 * sẽ được thiết lập là người đã đăng nhập. Nếu không có người dùng nào đăng nhập, thì nó sẽ
 * thiết lập người dùng hiện tại thành 0, không hợp lệ và không có bất kỳ quyền nào.
 *
 * Hàm này được sử dụng bởi các hàm pluggable wp_get_current_user() và
 * get_currentuserinfo(), hàm sau đã lỗi thời nhưng được sử dụng để tương thích ngược.
 *
 * @since 4.5.0
 * @access private
 *
 * @see wp_get_current_user()
 * @global WP_User $current_user Kiểm tra xem người dùng hiện tại đã được thiết lập chưa.
 *
 * @return WP_User Đối tượng WP_User hiện tại.
 */
function _wp_get_current_user() {
	global $current_user;

	if ( ! empty( $current_user ) ) {
		if ( $current_user instanceof WP_User ) {
			return $current_user;
		}

		// Nâng cấp stdClass lên WP_User.
		if ( is_object( $current_user ) && isset( $current_user->ID ) ) {
			$cur_id       = $current_user->ID;
			$current_user = null;
			wp_set_current_user( $cur_id );
			return $current_user;
		}

		// $current_user có giá trị rác. Bắt buộc thành WP_User với ID 0.
		$current_user = null;
		wp_set_current_user( 0 );
		return $current_user;
	}

	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		wp_set_current_user( 0 );
		return $current_user;
	}

	/**
	 * Lọc người dùng hiện tại.
	 *
	 * Các bộ lọc mặc định sử dụng điều này để xác định người dùng hiện tại từ
	 * cookie của yêu cầu, nếu có.
	 *
	 * Trả về giá trị false sẽ ngắn mạch hiệu quả việc thiết lập
	 * người dùng hiện tại.
	 *
	 * @since 3.9.0
	 *
	 * @param int|false $user_id ID người dùng nếu đã được xác định, false nếu không.
	 */
	$user_id = apply_filters( 'determine_current_user', false );
	if ( ! $user_id ) {
		wp_set_current_user( 0 );
		return $current_user;
	}

	wp_set_current_user( $user_id );

	return $current_user;
}

/**
 * Gửi email yêu cầu xác nhận khi có nỗ lực thay đổi địa chỉ email người dùng.
 *
 * @since 3.0.0
 * @since 4.9.0 Hàm này được di chuyển từ wp-admin/includes/ms.php nên không còn dành riêng cho Multisite.
 *
 * @global WP_Error $errors Đối tượng WP_Error.
 */
function send_confirmation_on_profile_email() {
	global $errors;

	$current_user = wp_get_current_user();
	if ( ! is_object( $errors ) ) {
		$errors = new WP_Error();
	}

	if ( $current_user->ID !== (int) $_POST['user_id'] ) {
		return false;
	}

	if ( $current_user->user_email !== $_POST['email'] ) {
		if ( ! is_email( $_POST['email'] ) ) {
			$errors->add(
				'user_email',
				__( '<strong>Error:</strong> The email address is not correct.' ),
				array(
					'form-field' => 'email',
				)
			);

			return;
		}

		if ( email_exists( $_POST['email'] ) ) {
			$errors->add(
				'user_email',
				__( '<strong>Error:</strong> The email address is already used.' ),
				array(
					'form-field' => 'email',
				)
			);
			delete_user_meta( $current_user->ID, '_new_email' );

			return;
		}

		$hash           = md5( $_POST['email'] . time() . wp_rand() );
		$new_user_email = array(
			'hash'     => $hash,
			'newemail' => $_POST['email'],
		);
		update_user_meta( $current_user->ID, '_new_email', $new_user_email );

		$sitename = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		/* translators: Do not translate USERNAME, ADMIN_URL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_text = __(
			'Howdy ###USERNAME###,

You recently requested to have the email address on your account changed.

If this is correct, please click on the following link to change it:
###ADMIN_URL###

You can safely ignore and delete this email if you do not want to
take this action.

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
		);

		/**
		 * Lọc nội dung email được gửi khi có nỗ lực thay đổi địa chỉ email người dùng.
		 *
		 * Các chuỗi sau có ý nghĩa đặc biệt và sẽ được thay thế động:
		 * - ###USERNAME###  Tên đăng nhập của người dùng hiện tại.
		 * - ###ADMIN_URL### Liên kết để nhấp vào xác nhận thay đổi email.
		 * - ###EMAIL###     Email mới.
		 * - ###SITENAME###  Tên của site.
		 * - ###SITEURL###   URL đến site.
		 *
		 * @since MU (3.0.0)
		 * @since 4.9.0 Bộ lọc này không còn dành riêng cho Multisite.
		 *
		 * @param string $email_text     Nội dung trong email.
		 * @param array  $new_user_email {
		 *     Dữ liệu liên quan đến địa chỉ email mới của người dùng.
		 *
		 *     @type string $hash     Hash bảo mật được sử dụng trong URL liên kết xác nhận.
		 *     @type string $newemail Địa chỉ email mới được đề xuất.
		 * }
		 */
		$content = apply_filters( 'new_user_email_content', $email_text, $new_user_email );

		$content = str_replace( '###USERNAME###', $current_user->user_login, $content );
		$content = str_replace( '###ADMIN_URL###', esc_url( self_admin_url( 'profile.php?newuseremail=' . $hash ) ), $content );
		$content = str_replace( '###EMAIL###', $_POST['email'], $content );
		$content = str_replace( '###SITENAME###', $sitename, $content );
		$content = str_replace( '###SITEURL###', home_url(), $content );

		/* translators: New email address notification email subject. %s: Site title. */
		wp_mail( $_POST['email'], sprintf( __( '[%s] Email Change Request' ), $sitename ), $content );

		$_POST['email'] = $current_user->user_email;
	}
}

/**
 * Thêm thông báo quản trị cảnh báo người dùng kiểm tra email yêu cầu xác nhận
 * sau khi thay đổi địa chỉ email.
 *
 * @since 3.0.0
 * @since 4.9.0 Hàm này được di chuyển từ wp-admin/includes/ms.php nên không còn dành riêng cho Multisite.
 *
 * @global string $pagenow Tên file của màn hình hiện tại.
 */
function new_user_email_admin_notice() {
	global $pagenow;

	if ( 'profile.php' === $pagenow && isset( $_GET['updated'] ) ) {
		$email = get_user_meta( get_current_user_id(), '_new_email', true );
		if ( $email ) {
			$message = sprintf(
				/* translators: %s: New email address. */
				__( 'Your email address has not been updated yet. Please check your inbox at %s for a confirmation email.' ),
				'<code>' . esc_html( $email['newemail'] ) . '</code>'
			);
			wp_admin_notice( $message, array( 'type' => 'info' ) );
		}
	}
}

/**
 * Lấy tất cả các loại yêu cầu dữ liệu cá nhân.
 *
 * @since 4.9.6
 * @access private
 *
 * @return string[] Danh sách các loại hành động quyền riêng tư cốt lõi.
 */
function _wp_privacy_action_request_types() {
	return array(
		'export_personal_data',
		'remove_personal_data',
	);
}

/**
 * Registers the personal data exporter for users.
 *
 * @since 4.9.6
 *
 * @param array[] $exporters An array of personal data exporters.
 * @return array[] An array of personal data exporters.
 */
function wp_register_user_personal_data_exporter( $exporters ) {
	$exporters['wordpress-user'] = array(
		'exporter_friendly_name' => __( 'WordPress User' ),
		'callback'               => 'wp_user_personal_data_exporter',
	);

	return $exporters;
}

/**
 * Finds and exports personal data associated with an email address from the user and user_meta table.
 *
 * @since 4.9.6
 * @since 5.4.0 Added 'Community Events Location' group to the export data.
 * @since 5.4.0 Added 'Session Tokens' group to the export data.
 *
 * @param string $email_address  The user's email address.
 * @return array {
 *     An array of personal data.
 *
 *     @type array[] $data An array of personal data arrays.
 *     @type bool    $done Whether the exporter is finished.
 * }
 */
function wp_user_personal_data_exporter( $email_address ) {
	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$user_meta = get_user_meta( $user->ID );

	$user_props_to_export = array(
		'ID'              => __( 'User ID' ),
		'user_login'      => __( 'User Login Name' ),
		'user_nicename'   => __( 'User Nice Name' ),
		'user_email'      => __( 'User Email' ),
		'user_url'        => __( 'User URL' ),
		'user_registered' => __( 'User Registration Date' ),
		'display_name'    => __( 'User Display Name' ),
		'nickname'        => __( 'User Nickname' ),
		'first_name'      => __( 'User First Name' ),
		'last_name'       => __( 'User Last Name' ),
		'description'     => __( 'User Description' ),
	);

	$user_data_to_export = array();

	foreach ( $user_props_to_export as $key => $name ) {
		$value = '';

		switch ( $key ) {
			case 'ID':
			case 'user_login':
			case 'user_nicename':
			case 'user_email':
			case 'user_url':
			case 'user_registered':
			case 'display_name':
				$value = $user->data->$key;
				break;
			case 'nickname':
			case 'first_name':
			case 'last_name':
			case 'description':
				$value = $user_meta[ $key ][0];
				break;
		}

		if ( ! empty( $value ) ) {
			$user_data_to_export[] = array(
				'name'  => $name,
				'value' => $value,
			);
		}
	}

	// Get the list of reserved names.
	$reserved_names = array_values( $user_props_to_export );

	/**
	 * Filters the user's profile data for the privacy exporter.
	 *
	 * @since 5.4.0
	 *
	 * @param array    $additional_user_profile_data {
	 *     An array of name-value pairs of additional user data items. Default empty array.
	 *
	 *     @type string $name  The user-facing name of an item name-value pair,e.g. 'IP Address'.
	 *     @type string $value The user-facing value of an item data pair, e.g. '50.60.70.0'.
	 * }
	 * @param WP_User  $user           The user whose data is being exported.
	 * @param string[] $reserved_names An array of reserved names. Any item in `$additional_user_data`
	 *                                 that uses one of these for its `name` will not be included in the export.
	 */
	$_extra_data = apply_filters( 'wp_privacy_additional_user_profile_data', array(), $user, $reserved_names );

	if ( is_array( $_extra_data ) && ! empty( $_extra_data ) ) {
		// Remove items that use reserved names.
		$extra_data = array_filter(
			$_extra_data,
			static function ( $item ) use ( $reserved_names ) {
				return ! in_array( $item['name'], $reserved_names, true );
			}
		);

		if ( count( $extra_data ) !== count( $_extra_data ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: wp_privacy_additional_user_profile_data */
					__( 'Filter %s returned items with reserved names.' ),
					'<code>wp_privacy_additional_user_profile_data</code>'
				),
				'5.4.0'
			);
		}

		if ( ! empty( $extra_data ) ) {
			$user_data_to_export = array_merge( $user_data_to_export, $extra_data );
		}
	}

	$data_to_export[] = array(
		'group_id'          => 'user',
		'group_label'       => __( 'User' ),
		'group_description' => __( 'User&#8217;s profile data.' ),
		'item_id'           => "user-{$user->ID}",
		'data'              => $user_data_to_export,
	);

	if ( isset( $user_meta['community-events-location'] ) ) {
		$location = maybe_unserialize( $user_meta['community-events-location'][0] );

		$location_props_to_export = array(
			'description' => __( 'City' ),
			'country'     => __( 'Country' ),
			'latitude'    => __( 'Latitude' ),
			'longitude'   => __( 'Longitude' ),
			'ip'          => __( 'IP' ),
		);

		$location_data_to_export = array();

		foreach ( $location_props_to_export as $key => $name ) {
			if ( ! empty( $location[ $key ] ) ) {
				$location_data_to_export[] = array(
					'name'  => $name,
					'value' => $location[ $key ],
				);
			}
		}

		$data_to_export[] = array(
			'group_id'          => 'community-events-location',
			'group_label'       => __( 'Community Events Location' ),
			'group_description' => __( 'User&#8217;s location data used for the Community Events in the WordPress Events and News dashboard widget.' ),
			'item_id'           => "community-events-location-{$user->ID}",
			'data'              => $location_data_to_export,
		);
	}

	if ( isset( $user_meta['session_tokens'] ) ) {
		$session_tokens = maybe_unserialize( $user_meta['session_tokens'][0] );

		$session_tokens_props_to_export = array(
			'expiration' => __( 'Expiration' ),
			'ip'         => __( 'IP' ),
			'ua'         => __( 'User Agent' ),
			'login'      => __( 'Last Login' ),
		);

		foreach ( $session_tokens as $token_key => $session_token ) {
			$session_tokens_data_to_export = array();

			foreach ( $session_tokens_props_to_export as $key => $name ) {
				if ( ! empty( $session_token[ $key ] ) ) {
					$value = $session_token[ $key ];
					if ( in_array( $key, array( 'expiration', 'login' ), true ) ) {
						$value = date_i18n( 'F d, Y H:i A', $value );
					}
					$session_tokens_data_to_export[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}

			$data_to_export[] = array(
				'group_id'          => 'session-tokens',
				'group_label'       => __( 'Session Tokens' ),
				'group_description' => __( 'User&#8217;s Session Tokens data.' ),
				'item_id'           => "session-tokens-{$user->ID}-{$token_key}",
				'data'              => $session_tokens_data_to_export,
			);
		}
	}

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}

/**
 * Updates log when privacy request is confirmed.
 *
 * @since 4.9.6
 * @access private
 *
 * @param int $request_id ID of the request.
 */
function _wp_privacy_account_request_confirmed( $request_id ) {
	$request = wp_get_user_request( $request_id );

	if ( ! $request ) {
		return;
	}

	if ( ! in_array( $request->status, array( 'request-pending', 'request-failed' ), true ) ) {
		return;
	}

	update_post_meta( $request_id, '_wp_user_request_confirmed_timestamp', time() );
	wp_update_post(
		array(
			'ID'          => $request_id,
			'post_status' => 'request-confirmed',
		)
	);
}

/**
 * Notifies the site administrator via email when a request is confirmed.
 *
 * Without this, the admin would have to manually check the site to see if any
 * action was needed on their part yet.
 *
 * @since 4.9.6
 *
 * @param int $request_id The ID of the request.
 */
function _wp_privacy_send_request_confirmation_notification( $request_id ) {
	$request = wp_get_user_request( $request_id );

	if ( ! ( $request instanceof WP_User_Request ) || 'request-confirmed' !== $request->status ) {
		return;
	}

	$already_notified = (bool) get_post_meta( $request_id, '_wp_admin_notified', true );

	if ( $already_notified ) {
		return;
	}

	if ( 'export_personal_data' === $request->action_name ) {
		$manage_url = admin_url( 'export-personal-data.php' );
	} elseif ( 'remove_personal_data' === $request->action_name ) {
		$manage_url = admin_url( 'erase-personal-data.php' );
	}
	$action_description = wp_user_request_action_description( $request->action_name );

	/**
	 * Filters the recipient of the data request confirmation notification.
	 *
	 * In a Multisite environment, this will default to the email address of the
	 * network admin because, by default, single site admins do not have the
	 * capabilities required to process requests. Some networks may wish to
	 * delegate those capabilities to a single-site admin, or a dedicated person
	 * responsible for managing privacy requests.
	 *
	 * @since 4.9.6
	 *
	 * @param string          $admin_email The email address of the notification recipient.
	 * @param WP_User_Request $request     The request that is initiating the notification.
	 */
	$admin_email = apply_filters( 'user_request_confirmed_email_to', get_site_option( 'admin_email' ), $request );

	$email_data = array(
		'request'     => $request,
		'user_email'  => $request->email,
		'description' => $action_description,
		'manage_url'  => $manage_url,
		'sitename'    => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
		'siteurl'     => home_url(),
		'admin_email' => $admin_email,
	);

	$subject = sprintf(
		/* translators: Privacy data request confirmed notification email subject. 1: Site title, 2: Name of the confirmed action. */
		__( '[%1$s] Action Confirmed: %2$s' ),
		$email_data['sitename'],
		$action_description
	);

	/**
	 * Filters the subject of the user request confirmation email.
	 *
	 * @since 4.9.8
	 *
	 * @param string $subject    The email subject.
	 * @param string $sitename   The name of the site.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $user_email  The email address confirming a request.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $manage_url  The link to click manage privacy requests of this type.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 *     @type string          $admin_email The administrator email receiving the mail.
	 * }
	 */
	$subject = apply_filters( 'user_request_confirmed_email_subject', $subject, $email_data['sitename'], $email_data );

	/* translators: Do not translate SITENAME, USER_EMAIL, DESCRIPTION, MANAGE_URL, SITEURL; those are placeholders. */
	$content = __(
		'Howdy,

A user data privacy request has been confirmed on ###SITENAME###:

User: ###USER_EMAIL###
Request: ###DESCRIPTION###

You can view and manage these data privacy requests here:

###MANAGE_URL###

Regards,
All at ###SITENAME###
###SITEURL###'
	);

	/**
	 * Filters the body of the user request confirmation email.
	 *
	 * The email is sent to an administrator when a user request is confirmed.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###SITENAME###    The name of the site.
	 * ###USER_EMAIL###  The user email for the request.
	 * ###DESCRIPTION### Description of the action being performed so the user knows what the email is for.
	 * ###MANAGE_URL###  The URL to manage requests.
	 * ###SITEURL###     The URL to the site.
	 *
	 * @since 4.9.6
	 * @deprecated 5.8.0 Use {@see 'user_request_confirmed_email_content'} instead.
	 *                   For user erasure fulfillment email content
	 *                   use {@see 'user_erasure_fulfillment_email_content'} instead.
	 *
	 * @param string $content    The email content.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $user_email  The email address confirming a request.
	 *     @type string          $description Description of the action being performed
	 *                                        so the user knows what the email is for.
	 *     @type string          $manage_url  The link to click manage privacy requests of this type.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 *     @type string          $admin_email The administrator email receiving the mail.
	 * }
	 */
	$content = apply_filters_deprecated(
		'user_confirmed_action_email_content',
		array( $content, $email_data ),
		'5.8.0',
		sprintf(
			/* translators: 1 & 2: Deprecation replacement options. */
			__( '%1$s or %2$s' ),
			'user_request_confirmed_email_content',
			'user_erasure_fulfillment_email_content'
		)
	);

	/**
	 * Filters the body of the user request confirmation email.
	 *
	 * The email is sent to an administrator when a user request is confirmed.
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###SITENAME###    The name of the site.
	 * ###USER_EMAIL###  The user email for the request.
	 * ###DESCRIPTION### Description of the action being performed so the user knows what the email is for.
	 * ###MANAGE_URL###  The URL to manage requests.
	 * ###SITEURL###     The URL to the site.
	 *
	 * @since 5.8.0
	 *
	 * @param string $content    The email content.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $user_email  The email address confirming a request.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $manage_url  The link to click manage privacy requests of this type.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 *     @type string          $admin_email The administrator email receiving the mail.
	 * }
	 */
	$content = apply_filters( 'user_request_confirmed_email_content', $content, $email_data );

	$content = str_replace( '###SITENAME###', $email_data['sitename'], $content );
	$content = str_replace( '###USER_EMAIL###', $email_data['user_email'], $content );
	$content = str_replace( '###DESCRIPTION###', $email_data['description'], $content );
	$content = str_replace( '###MANAGE_URL###', sanitize_url( $email_data['manage_url'] ), $content );
	$content = str_replace( '###SITEURL###', sanitize_url( $email_data['siteurl'] ), $content );

	$headers = '';

	/**
	 * Filters the headers of the user request confirmation email.
	 *
	 * @since 5.4.0
	 *
	 * @param string|array $headers    The email headers.
	 * @param string       $subject    The email subject.
	 * @param string       $content    The email content.
	 * @param int          $request_id The request ID.
	 * @param array        $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $user_email  The email address confirming a request.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $manage_url  The link to click manage privacy requests of this type.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 *     @type string          $admin_email The administrator email receiving the mail.
	 * }
	 */
	$headers = apply_filters( 'user_request_confirmed_email_headers', $headers, $subject, $content, $request_id, $email_data );

	$email_sent = wp_mail( $email_data['admin_email'], $subject, $content, $headers );

	if ( $email_sent ) {
		update_post_meta( $request_id, '_wp_admin_notified', true );
	}
}

/**
 * Notifies the user when their erasure request is fulfilled.
 *
 * Without this, the user would never know if their data was actually erased.
 *
 * @since 4.9.6
 *
 * @param int $request_id The privacy request post ID associated with this request.
 */
function _wp_privacy_send_erasure_fulfillment_notification( $request_id ) {
	$request = wp_get_user_request( $request_id );

	if ( ! ( $request instanceof WP_User_Request ) || 'request-completed' !== $request->status ) {
		return;
	}

	$already_notified = (bool) get_post_meta( $request_id, '_wp_user_notified', true );

	if ( $already_notified ) {
		return;
	}

	// Localize message content for user; fallback to site default for visitors.
	if ( ! empty( $request->user_id ) ) {
		$switched_locale = switch_to_user_locale( $request->user_id );
	} else {
		$switched_locale = switch_to_locale( get_locale() );
	}

	/**
	 * Filters the recipient of the data erasure fulfillment notification.
	 *
	 * @since 4.9.6
	 *
	 * @param string          $user_email The email address of the notification recipient.
	 * @param WP_User_Request $request    The request that is initiating the notification.
	 */
	$user_email = apply_filters( 'user_erasure_fulfillment_email_to', $request->email, $request );

	$email_data = array(
		'request'            => $request,
		'message_recipient'  => $user_email,
		'privacy_policy_url' => get_privacy_policy_url(),
		'sitename'           => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
		'siteurl'            => home_url(),
	);

	$subject = sprintf(
		/* translators: Erasure request fulfilled notification email subject. %s: Site title. */
		__( '[%s] Erasure Request Fulfilled' ),
		$email_data['sitename']
	);

	/**
	 * Filters the subject of the email sent when an erasure request is completed.
	 *
	 * @since 4.9.8
	 * @deprecated 5.8.0 Use {@see 'user_erasure_fulfillment_email_subject'} instead.
	 *
	 * @param string $subject    The email subject.
	 * @param string $sitename   The name of the site.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request            User request object.
	 *     @type string          $message_recipient  The address that the email will be sent to. Defaults
	 *                                               to the value of `$request->email`, but can be changed
	 *                                               by the `user_erasure_fulfillment_email_to` filter.
	 *     @type string          $privacy_policy_url Privacy policy URL.
	 *     @type string          $sitename           The site name sending the mail.
	 *     @type string          $siteurl            The site URL sending the mail.
	 * }
	 */
	$subject = apply_filters_deprecated(
		'user_erasure_complete_email_subject',
		array( $subject, $email_data['sitename'], $email_data ),
		'5.8.0',
		'user_erasure_fulfillment_email_subject'
	);

	/**
	 * Filters the subject of the email sent when an erasure request is completed.
	 *
	 * @since 5.8.0
	 *
	 * @param string $subject    The email subject.
	 * @param string $sitename   The name of the site.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request            User request object.
	 *     @type string          $message_recipient  The address that the email will be sent to. Defaults
	 *                                               to the value of `$request->email`, but can be changed
	 *                                               by the `user_erasure_fulfillment_email_to` filter.
	 *     @type string          $privacy_policy_url Privacy policy URL.
	 *     @type string          $sitename           The site name sending the mail.
	 *     @type string          $siteurl            The site URL sending the mail.
	 * }
	 */
	$subject = apply_filters( 'user_erasure_fulfillment_email_subject', $subject, $email_data['sitename'], $email_data );

	/* translators: Do not translate SITENAME, SITEURL; those are placeholders. */
	$content = __(
		'Howdy,

Your request to erase your personal data on ###SITENAME### has been completed.

If you have any follow-up questions or concerns, please contact the site administrator.

Regards,
All at ###SITENAME###
###SITEURL###'
	);

	if ( ! empty( $email_data['privacy_policy_url'] ) ) {
		/* translators: Do not translate SITENAME, SITEURL, PRIVACY_POLICY_URL; those are placeholders. */
		$content = __(
			'Howdy,

Your request to erase your personal data on ###SITENAME### has been completed.

If you have any follow-up questions or concerns, please contact the site administrator.

For more information, you can also read our privacy policy: ###PRIVACY_POLICY_URL###

Regards,
All at ###SITENAME###
###SITEURL###'
		);
	}

	/**
	 * Filters the body of the data erasure fulfillment notification.
	 *
	 * The email is sent to a user when their data erasure request is fulfilled
	 * by an administrator.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###SITENAME###           The name of the site.
	 * ###PRIVACY_POLICY_URL### Privacy policy page URL.
	 * ###SITEURL###            The URL to the site.
	 *
	 * @since 4.9.6
	 * @deprecated 5.8.0 Use {@see 'user_erasure_fulfillment_email_content'} instead.
	 *                   For user request confirmation email content
	 *                   use {@see 'user_request_confirmed_email_content'} instead.
	 *
	 * @param string $content The email content.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request            User request object.
	 *     @type string          $message_recipient  The address that the email will be sent to. Defaults
	 *                                               to the value of `$request->email`, but can be changed
	 *                                               by the `user_erasure_fulfillment_email_to` filter.
	 *     @type string          $privacy_policy_url Privacy policy URL.
	 *     @type string          $sitename           The site name sending the mail.
	 *     @type string          $siteurl            The site URL sending the mail.
	 * }
	 */
	$content = apply_filters_deprecated(
		'user_confirmed_action_email_content',
		array( $content, $email_data ),
		'5.8.0',
		sprintf(
			/* translators: 1 & 2: Deprecation replacement options. */
			__( '%1$s or %2$s' ),
			'user_erasure_fulfillment_email_content',
			'user_request_confirmed_email_content'
		)
	);

	/**
	 * Filters the body of the data erasure fulfillment notification.
	 *
	 * The email is sent to a user when their data erasure request is fulfilled
	 * by an administrator.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###SITENAME###           The name of the site.
	 * ###PRIVACY_POLICY_URL### Privacy policy page URL.
	 * ###SITEURL###            The URL to the site.
	 *
	 * @since 5.8.0
	 *
	 * @param string $content The email content.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request            User request object.
	 *     @type string          $message_recipient  The address that the email will be sent to. Defaults
	 *                                               to the value of `$request->email`, but can be changed
	 *                                               by the `user_erasure_fulfillment_email_to` filter.
	 *     @type string          $privacy_policy_url Privacy policy URL.
	 *     @type string          $sitename           The site name sending the mail.
	 *     @type string          $siteurl            The site URL sending the mail.
	 * }
	 */
	$content = apply_filters( 'user_erasure_fulfillment_email_content', $content, $email_data );

	$content = str_replace( '###SITENAME###', $email_data['sitename'], $content );
	$content = str_replace( '###PRIVACY_POLICY_URL###', $email_data['privacy_policy_url'], $content );
	$content = str_replace( '###SITEURL###', sanitize_url( $email_data['siteurl'] ), $content );

	$headers = '';

	/**
	 * Filters the headers of the data erasure fulfillment notification.
	 *
	 * @since 5.4.0
	 * @deprecated 5.8.0 Use {@see 'user_erasure_fulfillment_email_headers'} instead.
	 *
	 * @param string|array $headers    The email headers.
	 * @param string       $subject    The email subject.
	 * @param string       $content    The email content.
	 * @param int          $request_id The request ID.
	 * @param array        $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request            User request object.
	 *     @type string          $message_recipient  The address that the email will be sent to. Defaults
	 *                                               to the value of `$request->email`, but can be changed
	 *                                               by the `user_erasure_fulfillment_email_to` filter.
	 *     @type string          $privacy_policy_url Privacy policy URL.
	 *     @type string          $sitename           The site name sending the mail.
	 *     @type string          $siteurl            The site URL sending the mail.
	 * }
	 */
	$headers = apply_filters_deprecated(
		'user_erasure_complete_email_headers',
		array( $headers, $subject, $content, $request_id, $email_data ),
		'5.8.0',
		'user_erasure_fulfillment_email_headers'
	);

	/**
	 * Filters the headers of the data erasure fulfillment notification.
	 *
	 * @since 5.8.0
	 *
	 * @param string|array $headers    The email headers.
	 * @param string       $subject    The email subject.
	 * @param string       $content    The email content.
	 * @param int          $request_id The request ID.
	 * @param array        $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request            User request object.
	 *     @type string          $message_recipient  The address that the email will be sent to. Defaults
	 *                                               to the value of `$request->email`, but can be changed
	 *                                               by the `user_erasure_fulfillment_email_to` filter.
	 *     @type string          $privacy_policy_url Privacy policy URL.
	 *     @type string          $sitename           The site name sending the mail.
	 *     @type string          $siteurl            The site URL sending the mail.
	 * }
	 */
	$headers = apply_filters( 'user_erasure_fulfillment_email_headers', $headers, $subject, $content, $request_id, $email_data );

	$email_sent = wp_mail( $user_email, $subject, $content, $headers );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	if ( $email_sent ) {
		update_post_meta( $request_id, '_wp_user_notified', true );
	}
}

/**
 * Returns request confirmation message HTML.
 *
 * @since 4.9.6
 * @access private
 *
 * @param int $request_id The request ID being confirmed.
 * @return string The confirmation message.
 */
function _wp_privacy_account_request_confirmed_message( $request_id ) {
	$request = wp_get_user_request( $request_id );

	$message  = '<p class="success">' . __( 'Action has been confirmed.' ) . '</p>';
	$message .= '<p>' . __( 'The site administrator has been notified and will fulfill your request as soon as possible.' ) . '</p>';

	if ( $request && in_array( $request->action_name, _wp_privacy_action_request_types(), true ) ) {
		if ( 'export_personal_data' === $request->action_name ) {
			$message  = '<p class="success">' . __( 'Thanks for confirming your export request.' ) . '</p>';
			$message .= '<p>' . __( 'The site administrator has been notified. You will receive a link to download your export via email when they fulfill your request.' ) . '</p>';
		} elseif ( 'remove_personal_data' === $request->action_name ) {
			$message  = '<p class="success">' . __( 'Thanks for confirming your erasure request.' ) . '</p>';
			$message .= '<p>' . __( 'The site administrator has been notified. You will receive an email confirmation when they erase your data.' ) . '</p>';
		}
	}

	/**
	 * Filters the message displayed to a user when they confirm a data request.
	 *
	 * @since 4.9.6
	 *
	 * @param string $message    The message to the user.
	 * @param int    $request_id The ID of the request being confirmed.
	 */
	$message = apply_filters( 'user_request_action_confirmed_message', $message, $request_id );

	return $message;
}

/**
 * Creates and logs a user request to perform a specific action.
 *
 * Requests are stored inside a post type named `user_request` since they can apply to both
 * users on the site, or guests without a user account.
 *
 * @since 4.9.6
 * @since 5.7.0 Added the `$status` parameter.
 *
 * @param string $email_address           User email address. This can be the address of a registered
 *                                        or non-registered user.
 * @param string $action_name             Name of the action that is being confirmed. Required.
 * @param array  $request_data            Misc data you want to send with the verification request and pass
 *                                        to the actions once the request is confirmed.
 * @param string $status                  Optional request status (pending or confirmed). Default 'pending'.
 * @return int|WP_Error                   Returns the request ID if successful, or a WP_Error object on failure.
 */
function wp_create_user_request( $email_address = '', $action_name = '', $request_data = array(), $status = 'pending' ) {
	$email_address = sanitize_email( $email_address );
	$action_name   = sanitize_key( $action_name );

	if ( ! is_email( $email_address ) ) {
		return new WP_Error( 'invalid_email', __( 'Invalid email address.' ) );
	}

	if ( ! in_array( $action_name, _wp_privacy_action_request_types(), true ) ) {
		return new WP_Error( 'invalid_action', __( 'Invalid action name.' ) );
	}

	if ( ! in_array( $status, array( 'pending', 'confirmed' ), true ) ) {
		return new WP_Error( 'invalid_status', __( 'Invalid request status.' ) );
	}

	$user    = get_user_by( 'email', $email_address );
	$user_id = $user && ! is_wp_error( $user ) ? $user->ID : 0;

	// Check for duplicates.
	$requests_query = new WP_Query(
		array(
			'post_type'     => 'user_request',
			'post_name__in' => array( $action_name ), // Action name stored in post_name column.
			'title'         => $email_address,        // Email address stored in post_title column.
			'post_status'   => array(
				'request-pending',
				'request-confirmed',
			),
			'fields'        => 'ids',
		)
	);

	if ( $requests_query->found_posts ) {
		return new WP_Error( 'duplicate_request', __( 'An incomplete personal data request for this email address already exists.' ) );
	}

	$request_id = wp_insert_post(
		array(
			'post_author'   => $user_id,
			'post_name'     => $action_name,
			'post_title'    => $email_address,
			'post_content'  => wp_json_encode( $request_data ),
			'post_status'   => 'request-' . $status,
			'post_type'     => 'user_request',
			'post_date'     => current_time( 'mysql', false ),
			'post_date_gmt' => current_time( 'mysql', true ),
		),
		true
	);

	return $request_id;
}

/**
 * Gets action description from the name and return a string.
 *
 * @since 4.9.6
 *
 * @param string $action_name Action name of the request.
 * @return string Human readable action name.
 */
function wp_user_request_action_description( $action_name ) {
	switch ( $action_name ) {
		case 'export_personal_data':
			$description = __( 'Export Personal Data' );
			break;
		case 'remove_personal_data':
			$description = __( 'Erase Personal Data' );
			break;
		default:
			/* translators: %s: Action name. */
			$description = sprintf( __( 'Confirm the "%s" action' ), $action_name );
			break;
	}

	/**
	 * Filters the user action description.
	 *
	 * @since 4.9.6
	 *
	 * @param string $description The default description.
	 * @param string $action_name The name of the request.
	 */
	return apply_filters( 'user_request_action_description', $description, $action_name );
}

/**
 * Send a confirmation request email to confirm an action.
 *
 * If the request is not already pending, it will be updated.
 *
 * @since 4.9.6
 *
 * @param string $request_id ID of the request created via wp_create_user_request().
 * @return true|WP_Error True on success, `WP_Error` on failure.
 */
function wp_send_user_request( $request_id ) {
	$request_id = absint( $request_id );
	$request    = wp_get_user_request( $request_id );

	if ( ! $request ) {
		return new WP_Error( 'invalid_request', __( 'Invalid personal data request.' ) );
	}

	// Localize message content for user; fallback to site default for visitors.
	if ( ! empty( $request->user_id ) ) {
		$switched_locale = switch_to_user_locale( $request->user_id );
	} else {
		$switched_locale = switch_to_locale( get_locale() );
	}

	$email_data = array(
		'request'     => $request,
		'email'       => $request->email,
		'description' => wp_user_request_action_description( $request->action_name ),
		'confirm_url' => add_query_arg(
			array(
				'action'      => 'confirmaction',
				'request_id'  => $request_id,
				'confirm_key' => wp_generate_user_request_key( $request_id ),
			),
			wp_login_url()
		),
		'sitename'    => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
		'siteurl'     => home_url(),
	);

	/* translators: Confirm privacy data request notification email subject. 1: Site title, 2: Name of the action. */
	$subject = sprintf( __( '[%1$s] Confirm Action: %2$s' ), $email_data['sitename'], $email_data['description'] );

	/**
	 * Filters the subject of the email sent when an account action is attempted.
	 *
	 * @since 4.9.6
	 *
	 * @param string $subject    The email subject.
	 * @param string $sitename   The name of the site.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $email       The email address this is being sent to.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $confirm_url The link to click on to confirm the account action.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 * }
	 */
	$subject = apply_filters( 'user_request_action_email_subject', $subject, $email_data['sitename'], $email_data );

	/* translators: Do not translate DESCRIPTION, CONFIRM_URL, SITENAME, SITEURL: those are placeholders. */
	$content = __(
		'Howdy,

A request has been made to perform the following action on your account:

     ###DESCRIPTION###

To confirm this, please click on the following link:
###CONFIRM_URL###

You can safely ignore and delete this email if you do not want to
take this action.

Regards,
All at ###SITENAME###
###SITEURL###'
	);

	/**
	 * Filters the text of the email sent when an account action is attempted.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###DESCRIPTION### Description of the action being performed so the user knows what the email is for.
	 * ###CONFIRM_URL### The link to click on to confirm the account action.
	 * ###SITENAME###    The name of the site.
	 * ###SITEURL###     The URL to the site.
	 *
	 * @since 4.9.6
	 *
	 * @param string $content Text in the email.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $email       The email address this is being sent to.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $confirm_url The link to click on to confirm the account action.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 * }
	 */
	$content = apply_filters( 'user_request_action_email_content', $content, $email_data );

	$content = str_replace( '###DESCRIPTION###', $email_data['description'], $content );
	$content = str_replace( '###CONFIRM_URL###', sanitize_url( $email_data['confirm_url'] ), $content );
	$content = str_replace( '###EMAIL###', $email_data['email'], $content );
	$content = str_replace( '###SITENAME###', $email_data['sitename'], $content );
	$content = str_replace( '###SITEURL###', sanitize_url( $email_data['siteurl'] ), $content );

	$headers = '';

	/**
	 * Filters the headers of the email sent when an account action is attempted.
	 *
	 * @since 5.4.0
	 *
	 * @param string|array $headers    The email headers.
	 * @param string       $subject    The email subject.
	 * @param string       $content    The email content.
	 * @param int          $request_id The request ID.
	 * @param array        $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $email       The email address this is being sent to.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $confirm_url The link to click on to confirm the account action.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 * }
	 */
	$headers = apply_filters( 'user_request_action_email_headers', $headers, $subject, $content, $request_id, $email_data );

	$email_sent = wp_mail( $email_data['email'], $subject, $content, $headers );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	if ( ! $email_sent ) {
		return new WP_Error( 'privacy_email_error', __( 'Unable to send personal data export confirmation email.' ) );
	}

	return true;
}

/**
 * Returns a confirmation key for a user action and stores the hashed version for future comparison.
 *
 * @since 4.9.6
 *
 * @param int $request_id Request ID.
 * @return string Confirmation key.
 */
function wp_generate_user_request_key( $request_id ) {
	// Generate something random for a confirmation key.
	$key = wp_generate_password( 20, false );

	// Save the key, hashed.
	wp_update_post(
		array(
			'ID'            => $request_id,
			'post_status'   => 'request-pending',
			'post_password' => wp_fast_hash( $key ),
		)
	);

	return $key;
}

/**
 * Validates a user request by comparing the key with the request's key.
 *
 * @since 4.9.6
 *
 * @param string $request_id ID of the request being confirmed.
 * @param string $key        Provided key to validate.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function wp_validate_user_request_key(
	$request_id,
	#[\SensitiveParameter]
	$key
) {
	$request_id       = absint( $request_id );
	$request          = wp_get_user_request( $request_id );
	$saved_key        = $request->confirm_key;
	$key_request_time = $request->modified_timestamp;

	if ( ! $request || ! $saved_key || ! $key_request_time ) {
		return new WP_Error( 'invalid_request', __( 'Invalid personal data request.' ) );
	}

	if ( ! in_array( $request->status, array( 'request-pending', 'request-failed' ), true ) ) {
		return new WP_Error( 'expired_request', __( 'This personal data request has expired.' ) );
	}

	if ( empty( $key ) ) {
		return new WP_Error( 'missing_key', __( 'The confirmation key is missing from this personal data request.' ) );
	}

	/**
	 * Filters the expiration time of confirm keys.
	 *
	 * @since 4.9.6
	 *
	 * @param int $expiration The expiration time in seconds.
	 */
	$expiration_duration = (int) apply_filters( 'user_request_key_expiration', DAY_IN_SECONDS );
	$expiration_time     = $key_request_time + $expiration_duration;

	if ( ! wp_verify_fast_hash( $key, $saved_key ) ) {
		return new WP_Error( 'invalid_key', __( 'The confirmation key is invalid for this personal data request.' ) );
	}

	if ( ! $expiration_time || time() > $expiration_time ) {
		return new WP_Error( 'expired_key', __( 'The confirmation key has expired for this personal data request.' ) );
	}

	return true;
}

/**
 * Returns the user request object for the specified request ID.
 *
 * @since 4.9.6
 *
 * @param int $request_id The ID of the user request.
 * @return WP_User_Request|false
 */
function wp_get_user_request( $request_id ) {
	$request_id = absint( $request_id );
	$post       = get_post( $request_id );

	if ( ! $post || 'user_request' !== $post->post_type ) {
		return false;
	}

	return new WP_User_Request( $post );
}

/**
 * Checks if Application Passwords is supported.
 *
 * Application Passwords is supported only by sites using SSL or local environments
 * but may be made available using the {@see 'wp_is_application_passwords_available'} filter.
 *
 * @since 5.9.0
 *
 * @return bool
 */
function wp_is_application_passwords_supported() {
	return is_ssl() || 'local' === wp_get_environment_type();
}

/**
 * Checks if Application Passwords is globally available.
 *
 * By default, Application Passwords is available to all sites using SSL or to local environments.
 * Use the {@see 'wp_is_application_passwords_available'} filter to adjust its availability.
 *
 * @since 5.6.0
 *
 * @return bool
 */
function wp_is_application_passwords_available() {
	/**
	 * Filters whether Application Passwords is available.
	 *
	 * @since 5.6.0
	 *
	 * @param bool $available True if available, false otherwise.
	 */
	return apply_filters( 'wp_is_application_passwords_available', wp_is_application_passwords_supported() );
}

/**
 * Checks if Application Passwords is available for a specific user.
 *
 * By default all users can use Application Passwords. Use {@see 'wp_is_application_passwords_available_for_user'}
 * to restrict availability to certain users.
 *
 * @since 5.6.0
 *
 * @param int|WP_User $user The user to check.
 * @return bool
 */
function wp_is_application_passwords_available_for_user( $user ) {
	if ( ! wp_is_application_passwords_available() ) {
		return false;
	}

	if ( ! is_object( $user ) ) {
		$user = get_userdata( $user );
	}

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	/**
	 * Filters whether Application Passwords is available for a specific user.
	 *
	 * @since 5.6.0
	 *
	 * @param bool    $available True if available, false otherwise.
	 * @param WP_User $user      The user to check.
	 */
	return apply_filters( 'wp_is_application_passwords_available_for_user', true, $user );
}

/**
 * Registers the user meta property for persisted preferences.
 *
 * This property is used to store user preferences across page reloads and is
 * currently used by the block editor for preferences like 'fullscreenMode' and
 * 'fixedToolbar'.
 *
 * @since 6.1.0
 * @access private
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function wp_register_persisted_preferences_meta() {
	/*
	 * Create a meta key that incorporates the blog prefix so that each site
	 * on a multisite can have distinct user preferences.
	 */
	global $wpdb;
	$meta_key = $wpdb->get_blog_prefix() . 'persisted_preferences';

	register_meta(
		'user',
		$meta_key,
		array(
			'type'         => 'object',
			'single'       => true,
			'show_in_rest' => array(
				'name'   => 'persisted_preferences',
				'type'   => 'object',
				'schema' => array(
					'type'                 => 'object',
					'context'              => array( 'edit' ),
					'properties'           => array(
						'_modified' => array(
							'description' => __( 'The date and time the preferences were updated.' ),
							'type'        => 'string',
							'format'      => 'date-time',
							'readonly'    => false,
						),
					),
					'additionalProperties' => true,
				),
			),
		)
	);
}

/**
 * Sets the last changed time for the 'users' cache group.
 *
 * @since 6.3.0
 */
function wp_cache_set_users_last_changed() {
	wp_cache_set_last_changed( 'users' );
}

/**
 * Checks if password reset is allowed for a specific user.
 *
 * @since 6.3.0
 *
 * @param int|WP_User $user The user to check.
 * @return bool|WP_Error True if allowed, false or WP_Error otherwise.
 */
function wp_is_password_reset_allowed_for_user( $user ) {
	if ( ! is_object( $user ) ) {
		$user = get_userdata( $user );
	}

	if ( ! $user || ! $user->exists() ) {
		return false;
	}
	$allow = true;
	if ( is_multisite() && is_user_spammy( $user ) ) {
		$allow = false;
	}

	/**
	 * Filters whether to allow a password to be reset.
	 *
	 * @since 2.7.0
	 *
	 * @param bool $allow   Whether to allow the password to be reset. Default true.
	 * @param int  $user_id The ID of the user attempting to reset a password.
	 */
	return apply_filters( 'allow_password_reset', $allow, $user->ID );
}
