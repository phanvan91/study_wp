<?php
/**
 * API WordPress Multisite
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

/**
 * Lấy số lượng site và người dùng của mạng.
 *
 * @since MU (3.0.0)
 *
 * @return int[] {
 *     Số lượng site và người dùng cho mạng.
 *
 *     @type int $blogs Số lượng site trên mạng.
 *     @type int $users Số lượng người dùng trên mạng.
 * }
 */
function get_sitestats() {
	$stats = array(
		'blogs' => get_blog_count(),
		'users' => get_user_count(),
	);

	return $stats;
}

/**
 * Lấy một trong các blog hoạt động của người dùng.
 *
 * Trả về blog chính của người dùng, nếu họ có một blog và
 * nó đang hoạt động. Nếu không hoạt động, hàm trả về một
 * blog hoạt động khác của người dùng. Nếu không tìm thấy,
 * người dùng được thêm làm Subscriber vào Dashboard Blog và blog
 * đó được trả về.
 *
 * @since MU (3.0.0)
 *
 * @param int $user_id ID duy nhất của người dùng
 * @return WP_Site|void Đối tượng blog
 */
function get_active_blog_for_user( $user_id ) {
	$blogs = get_blogs_of_user( $user_id );
	if ( empty( $blogs ) ) {
		return;
	}

	if ( ! is_multisite() ) {
		return $blogs[ get_current_blog_id() ];
	}

	$primary_blog = get_user_meta( $user_id, 'primary_blog', true );
	$first_blog   = current( $blogs );
	if ( false !== $primary_blog ) {
		if ( ! isset( $blogs[ $primary_blog ] ) ) {
			update_user_meta( $user_id, 'primary_blog', $first_blog->userblog_id );
			$primary = get_site( $first_blog->userblog_id );
		} else {
			$primary = get_site( $primary_blog );
		}
	} else {
		// TODO: Xem xét lại lời gọi add_user_to_blog này - để đến đây người dùng phải có vai trò trên blog này?
		$result = add_user_to_blog( $first_blog->userblog_id, $user_id, 'subscriber' );

		if ( ! is_wp_error( $result ) ) {
			update_user_meta( $user_id, 'primary_blog', $first_blog->userblog_id );
			$primary = $first_blog;
		}
	}

	if ( ( ! is_object( $primary ) )
		|| ( '1' === $primary->archived || '1' === $primary->spam || '1' === $primary->deleted )
	) {
		$blogs = get_blogs_of_user( $user_id, true ); // Nếu blog chính của người dùng bị đóng, kiểm tra các blog khác của họ.
		$ret   = false;

		if ( is_array( $blogs ) && count( $blogs ) > 0 ) {
			$current_network_id = get_current_network_id();

			foreach ( (array) $blogs as $blog_id => $blog ) {
				if ( $blog->site_id !== $current_network_id ) {
					continue;
				}

				$details = get_site( $blog_id );
				if ( is_object( $details )
					&& '0' === $details->archived && '0' === $details->spam && '0' === $details->deleted
				) {
					$ret = $details;
					if ( (int) get_user_meta( $user_id, 'primary_blog', true ) !== $blog_id ) {
						update_user_meta( $user_id, 'primary_blog', $blog_id );
					}
					if ( ! get_user_meta( $user_id, 'source_domain', true ) ) {
						update_user_meta( $user_id, 'source_domain', $details->domain );
					}
					break;
				}
			}
		} else {
			return;
		}

		return $ret;
	} else {
		return $primary;
	}
}

/**
 * Lấy số lượng site hoạt động trên hệ thống.
 *
 * Số lượng được cache và cập nhật hai lần mỗi ngày. Đây không phải số liệu thời gian thực.
 *
 * @since MU (3.0.0)
 * @since 3.7.0 Tham số `$network_id` đã bị deprecated.
 * @since 4.8.0 Tham số `$network_id` hiện đang được sử dụng.
 *
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 * @return int Số lượng site hoạt động trên mạng.
 */
function get_blog_count( $network_id = null ) {
	return get_network_option( $network_id, 'blog_count' );
}

/**
 * Lấy một bài viết blog từ bất kỳ site nào trên mạng.
 *
 * Hàm này tương tự get_post(), ngoại trừ việc nó có thể lấy bài viết
 * từ bất kỳ site nào trên mạng, không chỉ site hiện tại.
 *
 * @since MU (3.0.0)
 *
 * @param int $blog_id ID của blog.
 * @param int $post_id ID của bài viết cần tìm.
 * @return WP_Post|null Đối tượng WP_Post khi thành công, null khi thất bại
 */
function get_blog_post( $blog_id, $post_id ) {
	switch_to_blog( $blog_id );
	$post = get_post( $post_id );
	restore_current_blog();

	return $post;
}

/**
 * Thêm người dùng vào blog, cùng với việc chỉ định vai trò của người dùng.
 *
 * Sử dụng action {@see 'add_user_to_blog'} để kích hoạt sự kiện khi người dùng được thêm vào blog.
 *
 * @since MU (3.0.0)
 *
 * @param int    $blog_id ID của blog mà người dùng được thêm vào.
 * @param int    $user_id ID của người dùng được thêm.
 * @param string $role    Vai trò người dùng.
 * @return true|WP_Error True khi thành công hoặc đối tượng WP_Error nếu người dùng không tồn tại
 *                       hoặc không thể được thêm.
 */
function add_user_to_blog( $blog_id, $user_id, $role ) {
	switch_to_blog( $blog_id );

	$user = get_userdata( $user_id );

	if ( ! $user ) {
		restore_current_blog();
		return new WP_Error( 'user_does_not_exist', __( 'The requested user does not exist.' ) );
	}

	/**
	 * Lọc xem người dùng có nên được thêm vào site hay không.
	 *
	 * @since 4.9.0
	 *
	 * @param true|WP_Error $retval  True nếu người dùng nên được thêm vào site, đối tượng
	 *                               lỗi nếu không.
	 * @param int           $user_id ID người dùng.
	 * @param string        $role    Vai trò người dùng.
	 * @param int           $blog_id ID site.
	 */
	$can_add_user = apply_filters( 'can_add_user_to_blog', true, $user_id, $role, $blog_id );

	if ( true !== $can_add_user ) {
		restore_current_blog();

		if ( is_wp_error( $can_add_user ) ) {
			return $can_add_user;
		}

		return new WP_Error( 'user_cannot_be_added', __( 'User cannot be added to this site.' ) );
	}

	if ( ! get_user_meta( $user_id, 'primary_blog', true ) ) {
		update_user_meta( $user_id, 'primary_blog', $blog_id );
		$site = get_site( $blog_id );
		update_user_meta( $user_id, 'source_domain', $site->domain );
	}

	$user->set_role( $role );

	/**
	 * Kích hoạt ngay sau khi người dùng được thêm vào site.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param int    $user_id ID người dùng.
	 * @param string $role    Vai trò người dùng.
	 * @param int    $blog_id ID blog.
	 */
	do_action( 'add_user_to_blog', $user_id, $role, $blog_id );

	clean_user_cache( $user_id );
	wp_cache_delete( $blog_id . '_user_count', 'blog-details' );

	restore_current_blog();

	return true;
}

/**
 * Xóa người dùng khỏi blog.
 *
 * Sử dụng action {@see 'remove_user_from_blog'} để kích hoạt sự kiện khi
 * người dùng bị xóa khỏi blog.
 *
 * Chấp nhận tham số `$reassign` tùy chọn, nếu bạn muốn
 * gán lại các bài viết blog của người dùng cho người dùng khác khi xóa.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $user_id  ID của người dùng bị xóa.
 * @param int $blog_id  Tùy chọn. ID của blog mà người dùng bị xóa khỏi. Mặc định 0.
 * @param int $reassign Tùy chọn. ID của người dùng để gán lại bài viết. Mặc định 0.
 * @return true|WP_Error True khi thành công hoặc đối tượng WP_Error nếu người dùng không tồn tại.
 */
function remove_user_from_blog( $user_id, $blog_id = 0, $reassign = 0 ) {
	global $wpdb;

	$user_id = (int) $user_id;
	$blog_id = (int) $blog_id;

	switch_to_blog( $blog_id );

	/**
	 * Kích hoạt trước khi người dùng bị xóa khỏi site.
	 *
	 * @since MU (3.0.0)
	 * @since 5.4.0 Thêm tham số `$reassign`.
	 *
	 * @param int $user_id  ID của người dùng bị xóa.
	 * @param int $blog_id  ID của blog mà người dùng bị xóa khỏi.
	 * @param int $reassign ID của người dùng để gán lại bài viết.
	 */
	do_action( 'remove_user_from_blog', $user_id, $blog_id, $reassign );

	/*
	 * Nếu bị xóa khỏi blog chính, đặt blog chính mới
	 * nếu người dùng được gán cho nhiều blog.
	 */
	$primary_blog = (int) get_user_meta( $user_id, 'primary_blog', true );
	if ( $primary_blog === $blog_id ) {
		$new_id     = '';
		$new_domain = '';
		$blogs      = get_blogs_of_user( $user_id );
		foreach ( (array) $blogs as $blog ) {
			if ( $blog->userblog_id === $blog_id ) {
				continue;
			}
			$new_id     = $blog->userblog_id;
			$new_domain = $blog->domain;
			break;
		}

		update_user_meta( $user_id, 'primary_blog', $new_id );
		update_user_meta( $user_id, 'source_domain', $new_domain );
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		restore_current_blog();
		return new WP_Error( 'user_does_not_exist', __( 'That user does not exist.' ) );
	}

	$user->remove_all_caps();

	$blogs = get_blogs_of_user( $user_id );
	if ( count( $blogs ) === 0 ) {
		update_user_meta( $user_id, 'primary_blog', '' );
		update_user_meta( $user_id, 'source_domain', '' );
	}

	if ( $reassign ) {
		$reassign = (int) $reassign;
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d", $user_id ) );
		$link_ids = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM $wpdb->links WHERE link_owner = %d", $user_id ) );

		if ( ! empty( $post_ids ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_author = %d WHERE post_author = %d", $reassign, $user_id ) );
			array_walk( $post_ids, 'clean_post_cache' );
		}

		if ( ! empty( $link_ids ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->links SET link_owner = %d WHERE link_owner = %d", $reassign, $user_id ) );
			array_walk( $link_ids, 'clean_bookmark_cache' );
		}
	}

	clean_user_cache( $user_id );
	restore_current_blog();

	return true;
}

/**
 * Lấy permalink cho bài viết trên blog khác.
 *
 * @since MU (3.0.0) 1.0
 *
 * @param int $blog_id ID của blog nguồn.
 * @param int $post_id ID của bài viết cần lấy.
 * @return string Permalink của bài viết.
 */
function get_blog_permalink( $blog_id, $post_id ) {
	switch_to_blog( $blog_id );
	$link = get_permalink( $post_id );
	restore_current_blog();

	return $link;
}

/**
 * Lấy ID số của blog từ URL của nó.
 *
 * Trên cài đặt thư mục con như example.com/blog1/,
 * $domain sẽ là gốc 'example.com' và $path là
 * thư mục con '/blog1/'. Với subdomain như blog1.example.com,
 * $domain là 'blog1.example.com' và $path là '/'.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $domain Tên miền website.
 * @param string $path   Tùy chọn. Không cần thiết cho cài đặt subdomain. Mặc định '/'.
 * @return int 0 nếu không tìm thấy blog, ngược lại là ID của blog phù hợp.
 */
function get_blog_id_from_url( $domain, $path = '/' ) {
	$domain = strtolower( $domain );
	$path   = strtolower( $path );
	$id     = wp_cache_get( md5( $domain . $path ), 'blog-id-cache' );

	if ( -1 === $id ) { // Blog không tồn tại.
		return 0;
	} elseif ( $id ) {
		return (int) $id;
	}

	$args   = array(
		'domain'                 => $domain,
		'path'                   => $path,
		'fields'                 => 'ids',
		'number'                 => 1,
		'update_site_meta_cache' => false,
	);
	$result = get_sites( $args );
	$id     = array_shift( $result );

	if ( ! $id ) {
		wp_cache_set( md5( $domain . $path ), -1, 'blog-id-cache' );
		return 0;
	}

	wp_cache_set( md5( $domain . $path ), $id, 'blog-id-cache' );

	return $id;
}

//
// Các hàm quản trị.
//

/**
 * Kiểm tra địa chỉ email dựa trên danh sách tên miền bị cấm.
 *
 * Hàm này kiểm tra dựa trên danh sách Tên miền Email Bị cấm
 * tại wp-admin/network/settings.php. Kiểm tra chỉ chạy trên
 * đăng ký tự phục vụ; tạo người dùng tại wp-admin/network/users.php
 * bỏ qua kiểm tra này.
 *
 * @since MU (3.0.0)
 *
 * @param string $user_email Email được cung cấp bởi người dùng khi đăng ký.
 * @return bool True khi địa chỉ email bị cấm, false nếu không.
 */
function is_email_address_unsafe( $user_email ) {
	$banned_names = get_site_option( 'banned_email_domains' );
	if ( $banned_names && ! is_array( $banned_names ) ) {
		$banned_names = explode( "\n", $banned_names );
	}

	$is_email_address_unsafe = false;

	if ( $banned_names && is_array( $banned_names ) && false !== strpos( $user_email, '@', 1 ) ) {
		$banned_names     = array_map( 'strtolower', $banned_names );
		$normalized_email = strtolower( $user_email );

		list( $email_local_part, $email_domain ) = explode( '@', $normalized_email );

		foreach ( $banned_names as $banned_domain ) {
			if ( ! $banned_domain ) {
				continue;
			}

			if ( $email_domain === $banned_domain ) {
				$is_email_address_unsafe = true;
				break;
			}

			if ( str_ends_with( $normalized_email, ".$banned_domain" ) ) {
				$is_email_address_unsafe = true;
				break;
			}
		}
	}

	/**
	 * Lọc xem địa chỉ email có không an toàn hay không.
	 *
	 * @since 3.5.0
	 *
	 * @param bool   $is_email_address_unsafe Liệu địa chỉ email có "không an toàn" hay không. Mặc định false.
	 * @param string $user_email              Địa chỉ email người dùng.
	 */
	return apply_filters( 'is_email_address_unsafe', $is_email_address_unsafe, $user_email );
}

/**
 * Làm sạch và xác thực dữ liệu cần thiết cho đăng ký người dùng.
 *
 * Xác minh tính hợp lệ và duy nhất của tên người dùng và địa chỉ email,
 * và kiểm tra địa chỉ email dựa trên các tên miền được phép và không được phép
 * do quản trị viên cung cấp.
 *
 * Hook {@see 'wpmu_validate_user_signup'} cung cấp cách dễ dàng để sửa đổi quá trình
 * đăng ký. Giá trị $result, được truyền vào hook, chứa cả thông tin do người dùng
 * cung cấp và các thông báo lỗi được tạo bởi hàm. {@see 'wpmu_validate_user_signup'}
 * cho phép bạn xử lý dữ liệu theo bất kỳ cách nào bạn muốn, và bỏ các lỗi
 * liên quan nếu cần.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $user_name  Tên đăng nhập do người dùng cung cấp.
 * @param string $user_email Email do người dùng cung cấp.
 * @return array {
 *     Mảng tên người dùng, email, và các thông báo lỗi.
 *
 *     @type string   $user_name     Tên người dùng đã được làm sạch và duy nhất.
 *     @type string   $orig_username Tên người dùng gốc.
 *     @type string   $user_email    Địa chỉ email người dùng.
 *     @type WP_Error $errors        Đối tượng WP_Error chứa các lỗi tìm thấy.
 * }
 */
function wpmu_validate_user_signup( $user_name, $user_email ) {
	global $wpdb;

	$errors = new WP_Error();

	$orig_username = $user_name;
	$user_name     = preg_replace( '/\s+/', '', sanitize_user( $user_name, true ) );

	if ( $user_name !== $orig_username || preg_match( '/[^a-z0-9]/', $user_name ) ) {
		$errors->add( 'user_name', __( 'Usernames can only contain lowercase letters (a-z) and numbers.' ) );
		$user_name = $orig_username;
	}

	$user_email = sanitize_email( $user_email );

	if ( empty( $user_name ) ) {
		$errors->add( 'user_name', __( 'Please enter a username.' ) );
	}

	$illegal_names = get_site_option( 'illegal_names' );

	if ( ! is_array( $illegal_names ) ) {
		$illegal_names = array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' );
		add_site_option( 'illegal_names', $illegal_names );
	}

	if ( in_array( $user_name, $illegal_names, true ) ) {
		$errors->add( 'user_name', __( 'Sorry, that username is not allowed.' ) );
	}

	/** Bộ lọc này được ghi chú trong wp-includes/user.php */
	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

	if ( in_array( strtolower( $user_name ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		$errors->add( 'user_name', __( 'Sorry, that username is not allowed.' ) );
	}

	if ( ! is_email( $user_email ) ) {
		$errors->add( 'user_email', __( 'Please enter a valid email address.' ) );
	} elseif ( is_email_address_unsafe( $user_email ) ) {
		$errors->add( 'user_email', __( 'You cannot use that email address to signup. There are problems with them blocking some emails from WordPress. Please use another email provider.' ) );
	}

	if ( strlen( $user_name ) < 4 ) {
		$errors->add( 'user_name', __( 'Username must be at least 4 characters.' ) );
	}

	if ( strlen( $user_name ) > 60 ) {
		$errors->add( 'user_name', __( 'Username may not be longer than 60 characters.' ) );
	}

	// Toàn số?
	if ( preg_match( '/^[0-9]*$/', $user_name ) ) {
		$errors->add( 'user_name', __( 'Sorry, usernames must have letters too!' ) );
	}

	$limited_email_domains = get_site_option( 'limited_email_domains' );

	if ( is_array( $limited_email_domains ) && ! empty( $limited_email_domains ) ) {
		$limited_email_domains = array_map( 'strtolower', $limited_email_domains );
		$email_domain          = strtolower( substr( $user_email, 1 + strpos( $user_email, '@' ) ) );

		if ( ! in_array( $email_domain, $limited_email_domains, true ) ) {
			$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!' ) );
		}
	}

	// Kiểm tra xem tên người dùng đã được sử dụng chưa.
	if ( username_exists( $user_name ) ) {
		$errors->add( 'user_name', __( 'Sorry, that username already exists!' ) );
	}

	// Kiểm tra xem địa chỉ email đã được sử dụng chưa.
	if ( email_exists( $user_email ) ) {
		$errors->add(
			'user_email',
			sprintf(
				/* translators: %s: Link to the login page. */
				__( '<strong>Error:</strong> This email address is already registered. <a href="%s">Log in</a> with this address or choose another one.' ),
				wp_login_url()
			)
		);
	}

	// Đã có ai đăng ký tên người dùng này chưa?
	$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE user_login = %s", $user_name ) );
	if ( $signup instanceof stdClass ) {
		$registered_at = mysql2date( 'U', $signup->registered );
		$now           = time();
		$diff          = $now - $registered_at;
		// Nếu đăng ký hơn hai ngày trước, hủy đăng ký và cho phép đăng ký này đi qua.
		if ( $diff > 2 * DAY_IN_SECONDS ) {
			$wpdb->delete( $wpdb->signups, array( 'user_login' => $user_name ) );
		} else {
			$errors->add( 'user_name', __( 'That username is currently reserved but may be available in a couple of days.' ) );
		}
	}

	$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE user_email = %s", $user_email ) );
	if ( $signup instanceof stdClass ) {
		$diff = time() - mysql2date( 'U', $signup->registered );
		// Nếu đăng ký hơn hai ngày trước, hủy đăng ký và cho phép đăng ký này đi qua.
		if ( $diff > 2 * DAY_IN_SECONDS ) {
			$wpdb->delete( $wpdb->signups, array( 'user_email' => $user_email ) );
		} else {
			$errors->add( 'user_email', __( 'That email address has already been used. Please check your inbox for an activation email. It will become available in a couple of days if you do nothing.' ) );
		}
	}

	$result = array(
		'user_name'     => $user_name,
		'orig_username' => $orig_username,
		'user_email'    => $user_email,
		'errors'        => $errors,
	);

	/**
	 * Lọc chi tiết đăng ký người dùng đã được xác thực.
	 *
	 * Điều này không cho phép bạn ghi đè tên người dùng hoặc email của người dùng trong quá trình
	 * đăng ký. Các giá trị chỉ được sử dụng để xác thực và xử lý lỗi.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param array $result {
	 *     Mảng tên người dùng, email, và các thông báo lỗi.
	 *
	 *     @type string   $user_name     Tên người dùng đã được làm sạch và duy nhất.
	 *     @type string   $orig_username Tên người dùng gốc.
	 *     @type string   $user_email    Địa chỉ email người dùng.
	 *     @type WP_Error $errors        Đối tượng WP_Error chứa các lỗi tìm thấy.
	 * }
	 */
	return apply_filters( 'wpmu_validate_user_signup', $result );
}

/**
 * Xử lý đăng ký site mới.
 *
 * Kiểm tra dữ liệu do người dùng cung cấp trong quá trình đăng ký blog. Xác minh
 * tính hợp lệ và duy nhất của đường dẫn và tên miền blog.
 *
 * Hàm này ngăn người dùng hiện tại đăng ký site mới
 * với blogname tương đương với tên đăng nhập của người dùng khác. Truyền
 * tham số $user vào hàm, trong đó $user là người dùng khác, là
 * cách ghi đè giới hạn này.
 *
 * Lọc {@see 'wpmu_validate_blog_signup'} nếu bạn muốn sửa đổi
 * cách WordPress xác thực đăng ký site mới.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb   $wpdb   Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 * @global string $domain
 *
 * @param string         $blogname   Tên site do người dùng cung cấp. Phải duy nhất.
 * @param string         $blog_title Tiêu đề site do người dùng cung cấp.
 * @param WP_User|string $user       Tùy chọn. Đối tượng người dùng để kiểm tra với tên site mới.
 *                                   Mặc định chuỗi rỗng.
 * @return array {
 *     Mảng tên miền, đường dẫn, tên site, tiêu đề site, người dùng và thông báo lỗi.
 *
 *     @type string         $domain     Tên miền cho site.
 *     @type string         $path       Đường dẫn cho site. Dùng trong cài đặt thư mục con.
 *     @type string         $blogname   Tên site duy nhất (slug).
 *     @type string         $blog_title Tiêu đề blog.
 *     @type string|WP_User $user       Mặc định là chuỗi rỗng. Đối tượng người dùng nếu được cung cấp.
 *     @type WP_Error       $errors     WP_Error chứa các lỗi tìm thấy.
 * }
 */
function wpmu_validate_blog_signup( $blogname, $blog_title, $user = '' ) {
	global $wpdb, $domain;

	$current_network = get_network();
	$base            = $current_network->path;

	$blog_title = strip_tags( $blog_title );

	$errors        = new WP_Error();
	$illegal_names = get_site_option( 'illegal_names' );

	if ( ! is_array( $illegal_names ) ) {
		$illegal_names = array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' );
		add_site_option( 'illegal_names', $illegal_names );
	}

	/*
	 * Trên cài đặt thư mục con, một số tên bị cấm nghiêm ngặt đến mức chỉ bộ lọc
	 * mới có thể cho phép chúng.
	 */
	if ( ! is_subdomain_install() ) {
		$illegal_names = array_merge( $illegal_names, get_subdirectory_reserved_names() );
	}

	if ( empty( $blogname ) ) {
		$errors->add( 'blogname', __( 'Please enter a site name.' ) );
	}

	if ( preg_match( '/[^a-z0-9]+/', $blogname ) ) {
		$errors->add( 'blogname', __( 'Site names can only contain lowercase letters (a-z) and numbers.' ) );
	}

	if ( in_array( $blogname, $illegal_names, true ) ) {
		$errors->add( 'blogname', __( 'That name is not allowed.' ) );
	}

	/**
	 * Lọc độ dài tên site tối thiểu khi xác thực đăng ký site.
	 *
	 * @since 4.8.0
	 *
	 * @param int $length Độ dài tên site tối thiểu. Mặc định 4.
	 */
	$minimum_site_name_length = apply_filters( 'minimum_site_name_length', 4 );

	if ( strlen( $blogname ) < $minimum_site_name_length ) {
		/* translators: %s: Minimum site name length. */
		$errors->add( 'blogname', sprintf( _n( 'Site name must be at least %s character.', 'Site name must be at least %s characters.', $minimum_site_name_length ), number_format_i18n( $minimum_site_name_length ) ) );
	}

	// Không cho phép người dùng tạo site xung đột với trang trên blog chính.
	if ( ! is_subdomain_install() && $wpdb->get_var( $wpdb->prepare( 'SELECT post_name FROM ' . $wpdb->get_blog_prefix( $current_network->site_id ) . "posts WHERE post_type = 'page' AND post_name = %s", $blogname ) ) ) {
		$errors->add( 'blogname', __( 'Sorry, you may not use that site name.' ) );
	}

	// Toàn số?
	if ( preg_match( '/^[0-9]*$/', $blogname ) ) {
		$errors->add( 'blogname', __( 'Sorry, site names must have letters too!' ) );
	}

	/**
	 * Lọc tên site mới trong quá trình đăng ký.
	 *
	 * Tên là subdomain của site hoặc đường dẫn thư mục con
	 * của site tùy thuộc vào cài đặt mạng.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $blogname Tên site.
	 */
	$blogname = apply_filters( 'newblogname', $blogname );

	$blog_title = wp_unslash( $blog_title );

	if ( empty( $blog_title ) ) {
		$errors->add( 'blog_title', __( 'Please enter a site title.' ) );
	}

	// Kiểm tra xem tên miền/đường dẫn đã được sử dụng chưa.
	if ( is_subdomain_install() ) {
		$mydomain = $blogname . '.' . preg_replace( '|^www\.|', '', $domain );
		$path     = $base;
	} else {
		$mydomain = $domain;
		$path     = $base . $blogname . '/';
	}
	if ( domain_exists( $mydomain, $path, $current_network->id ) ) {
		$errors->add( 'blogname', __( 'Sorry, that site already exists!' ) );
	}

	/*
	 * Không cho phép người dùng tạo site trùng với tên đăng nhập của người dùng khác,
	 * trừ khi đó là tên người dùng của chính họ.
	 */
	if ( username_exists( $blogname ) ) {
		if ( ! is_object( $user ) || ( is_object( $user ) && $user->user_login !== $blogname ) ) {
			$errors->add( 'blogname', __( 'Sorry, that site is reserved!' ) );
		}
	}

	/*
	 * Đã có ai đăng ký tên miền này chưa?
	 * TODO: Kiểm tra email nữa?
	 */
	$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE domain = %s AND path = %s", $mydomain, $path ) );
	if ( $signup instanceof stdClass ) {
		$diff = time() - mysql2date( 'U', $signup->registered );
		// Nếu đăng ký hơn hai ngày trước, hủy đăng ký và cho phép đăng ký này đi qua.
		if ( $diff > 2 * DAY_IN_SECONDS ) {
			$wpdb->delete(
				$wpdb->signups,
				array(
					'domain' => $mydomain,
					'path'   => $path,
				)
			);
		} else {
			$errors->add( 'blogname', __( 'That site is currently reserved but may be available in a couple days.' ) );
		}
	}

	$result = array(
		'domain'     => $mydomain,
		'path'       => $path,
		'blogname'   => $blogname,
		'blog_title' => $blog_title,
		'user'       => $user,
		'errors'     => $errors,
	);

	/**
	 * Lọc chi tiết site và thông báo lỗi sau khi đăng ký.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param array $result {
	 *     Mảng tên miền, đường dẫn, tên site, tiêu đề site, người dùng và thông báo lỗi.
	 *
	 *     @type string         $domain     Tên miền cho site.
	 *     @type string         $path       Đường dẫn cho site. Dùng trong cài đặt thư mục con.
	 *     @type string         $blogname   Tên site duy nhất (slug).
	 *     @type string         $blog_title Tiêu đề site.
	 *     @type string|WP_User $user       Mặc định là chuỗi rỗng. Đối tượng người dùng nếu được cung cấp.
	 *     @type WP_Error       $errors     WP_Error chứa các lỗi tìm thấy.
	 * }
	 */
	return apply_filters( 'wpmu_validate_blog_signup', $result );
}

/**
 * Ghi lại thông tin đăng ký site để kích hoạt trong tương lai.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $domain     Tên miền được yêu cầu.
 * @param string $path       Đường dẫn được yêu cầu.
 * @param string $title      Tiêu đề site được yêu cầu.
 * @param string $user       Tên đăng nhập do người dùng yêu cầu.
 * @param string $user_email Địa chỉ email của người dùng.
 * @param array  $meta       Tùy chọn. Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
 */
function wpmu_signup_blog( $domain, $path, $title, $user, $user_email, $meta = array() ) {
	global $wpdb;

	$key = substr( md5( time() . wp_rand() . $domain ), 0, 16 );

	/**
	 * Lọc metadata cho đăng ký site.
	 *
	 * Metadata sẽ được serialize trước khi lưu vào cơ sở dữ liệu.
	 *
	 * @since 4.8.0
	 *
	 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định mảng rỗng.
	 * @param string $domain     Tên miền được yêu cầu.
	 * @param string $path       Đường dẫn được yêu cầu.
	 * @param string $title      Tiêu đề site được yêu cầu.
	 * @param string $user       Tên đăng nhập do người dùng yêu cầu.
	 * @param string $user_email Địa chỉ email của người dùng.
	 * @param string $key        Khóa kích hoạt của người dùng.
	 */
	$meta = apply_filters( 'signup_site_meta', $meta, $domain, $path, $title, $user, $user_email, $key );

	$wpdb->insert(
		$wpdb->signups,
		array(
			'domain'         => $domain,
			'path'           => $path,
			'title'          => $title,
			'user_login'     => $user,
			'user_email'     => $user_email,
			'registered'     => current_time( 'mysql', true ),
			'activation_key' => $key,
			'meta'           => serialize( $meta ),
		)
	);

	/**
	 * Kích hoạt sau khi thông tin đăng ký site đã được ghi vào cơ sở dữ liệu.
	 *
	 * @since 4.4.0
	 *
	 * @param string $domain     Tên miền được yêu cầu.
	 * @param string $path       Đường dẫn được yêu cầu.
	 * @param string $title      Tiêu đề site được yêu cầu.
	 * @param string $user       Tên đăng nhập do người dùng yêu cầu.
	 * @param string $user_email Địa chỉ email của người dùng.
	 * @param string $key        Khóa kích hoạt của người dùng.
	 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
	 */
	do_action( 'after_signup_site', $domain, $path, $title, $user, $user_email, $key, $meta );
}

/**
 * Ghi lại thông tin đăng ký người dùng để kích hoạt trong tương lai.
 *
 * Hàm này được sử dụng khi đăng ký người dùng mở nhưng
 * đăng ký site mới thì không.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $user       Tên đăng nhập do người dùng yêu cầu.
 * @param string $user_email Địa chỉ email của người dùng.
 * @param array  $meta       Tùy chọn. Dữ liệu meta đăng ký. Mặc định mảng rỗng.
 */
function wpmu_signup_user( $user, $user_email, $meta = array() ) {
	global $wpdb;

	// Định dạng dữ liệu.
	$user       = preg_replace( '/\s+/', '', sanitize_user( $user, true ) );
	$user_email = sanitize_email( $user_email );
	$key        = substr( md5( time() . wp_rand() . $user_email ), 0, 16 );

	/**
	 * Lọc metadata cho đăng ký người dùng.
	 *
	 * Metadata sẽ được serialize trước khi lưu vào cơ sở dữ liệu.
	 *
	 * @since 4.8.0
	 *
	 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định mảng rỗng.
	 * @param string $user       Tên đăng nhập do người dùng yêu cầu.
	 * @param string $user_email Địa chỉ email của người dùng.
	 * @param string $key        Khóa kích hoạt của người dùng.
	 */
	$meta = apply_filters( 'signup_user_meta', $meta, $user, $user_email, $key );

	$wpdb->insert(
		$wpdb->signups,
		array(
			'domain'         => '',
			'path'           => '',
			'title'          => '',
			'user_login'     => $user,
			'user_email'     => $user_email,
			'registered'     => current_time( 'mysql', true ),
			'activation_key' => $key,
			'meta'           => serialize( $meta ),
		)
	);

	/**
	 * Kích hoạt sau khi thông tin đăng ký người dùng đã được ghi vào cơ sở dữ liệu.
	 *
	 * @since 4.4.0
	 *
	 * @param string $user       Tên đăng nhập do người dùng yêu cầu.
	 * @param string $user_email Địa chỉ email của người dùng.
	 * @param string $key        Khóa kích hoạt của người dùng.
	 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định mảng rỗng.
	 */
	do_action( 'after_signup_user', $user, $user_email, $key, $meta );
}

/**
 * Gửi email yêu cầu xác nhận cho người dùng khi họ đăng ký site mới. Site mới sẽ không hoạt động
 * cho đến khi liên kết xác nhận được nhấp.
 *
 * Đây là hàm thông báo được sử dụng khi đăng ký site
 * được bật.
 *
 * Lọc {@see 'wpmu_signup_blog_notification'} để bỏ qua hàm này hoặc
 * thay thế bằng hành vi thông báo của riêng bạn.
 *
 * Lọc {@see 'wpmu_signup_blog_notification_email'} và
 * {@see 'wpmu_signup_blog_notification_subject'} để thay đổi nội dung
 * và dòng tiêu đề của email gửi cho người dùng mới đăng ký.
 *
 * @since MU (3.0.0)
 *
 * @param string $domain     Tên miền blog mới.
 * @param string $path       Đường dẫn blog mới.
 * @param string $title      Tiêu đề site.
 * @param string $user_login Tên đăng nhập của người dùng.
 * @param string $user_email Địa chỉ email của người dùng.
 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_blog().
 * @param array  $meta       Tùy chọn. Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
 * @return bool
 */
function wpmu_signup_blog_notification(
	$domain,
	$path,
	$title,
	$user_login,
	$user_email,
	#[\SensitiveParameter]
	$key,
	$meta = array()
) {
	/**
	 * Lọc xem có bỏ qua thông báo email site mới hay không.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string|false $domain     Tên miền site, hoặc false để ngăn email gửi đi.
	 * @param string       $path       Đường dẫn site.
	 * @param string       $title      Tiêu đề site.
	 * @param string       $user_login Tên đăng nhập người dùng.
	 * @param string       $user_email Địa chỉ email người dùng.
	 * @param string       $key        Khóa kích hoạt được tạo trong wpmu_signup_blog().
	 * @param array        $meta       Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
	 */
	if ( ! apply_filters( 'wpmu_signup_blog_notification', $domain, $path, $title, $user_login, $user_email, $key, $meta ) ) {
		return false;
	}

	// Gửi email với liên kết kích hoạt.
	if ( ! is_subdomain_install() || get_current_network_id() !== 1 ) {
		$activate_url = network_site_url( "wp-activate.php?key=$key" );
	} else {
		$activate_url = "http://{$domain}{$path}wp-activate.php?key=$key"; // @todo Use *_url() API.
	}

	$activate_url = esc_url( $activate_url );

	$admin_email = get_site_option( 'admin_email' );

	if ( '' === $admin_email ) {
		$admin_email = 'support@' . wp_parse_url( network_home_url(), PHP_URL_HOST );
	}

	$from_name       = ( '' !== get_site_option( 'site_name' ) ) ? esc_html( get_site_option( 'site_name' ) ) : 'WordPress';
	$message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";

	$user            = get_user_by( 'login', $user_login );
	$switched_locale = $user && switch_to_user_locale( $user->ID );

	$message = sprintf(
		/**
		 * Lọc nội dung tin nhắn của email thông báo blog mới.
		 *
		 * Nội dung nên được định dạng để truyền qua wp_mail().
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string $content    Nội dung email thông báo.
		 * @param string $domain     Tên miền site.
		 * @param string $path       Đường dẫn site.
		 * @param string $title      Tiêu đề site.
		 * @param string $user_login Tên đăng nhập người dùng.
		 * @param string $user_email Địa chỉ email người dùng.
		 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_blog().
		 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
		 */
		apply_filters(
			'wpmu_signup_blog_notification_email',
			/* translators: New site notification email. 1: Activation URL, 2: New site URL. */
			__( "To activate your site, please click the following link:\n\n%1\$s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your site here:\n\n%2\$s" ),
			$domain,
			$path,
			$title,
			$user_login,
			$user_email,
			$key,
			$meta
		),
		$activate_url,
		esc_url( "http://{$domain}{$path}" ),
		$key
	);

	$subject = sprintf(
		/**
		 * Lọc tiêu đề của email thông báo blog mới.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string $subject    Tiêu đề email thông báo.
		 * @param string $domain     Tên miền site.
		 * @param string $path       Đường dẫn site.
		 * @param string $title      Tiêu đề site.
		 * @param string $user_login Tên đăng nhập người dùng.
		 * @param string $user_email Địa chỉ email người dùng.
		 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_blog().
		 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
		 */
		apply_filters(
			'wpmu_signup_blog_notification_subject',
			/* translators: New site notification email subject. 1: Network title, 2: New site URL. */
			_x( '[%1$s] Activate %2$s', 'New site notification email subject' ),
			$domain,
			$path,
			$title,
			$user_login,
			$user_email,
			$key,
			$meta
		),
		$from_name,
		esc_url( 'http://' . $domain . $path )
	);

	wp_mail( $user_email, wp_specialchars_decode( $subject ), $message, $message_headers );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	return true;
}

/**
 * Gửi email yêu cầu xác nhận cho người dùng khi họ đăng ký tài khoản mới (không đăng ký site
 * cùng lúc). Tài khoản người dùng sẽ không hoạt động cho đến khi liên kết xác nhận được nhấp.
 *
 * Đây là hàm thông báo được sử dụng khi không có site mới
 * được yêu cầu.
 *
 * Lọc {@see 'wpmu_signup_user_notification'} để bỏ qua hàm này hoặc
 * thay thế bằng hành vi thông báo của riêng bạn.
 *
 * Lọc {@see 'wpmu_signup_user_notification_email'} và
 * {@see 'wpmu_signup_user_notification_subject'} để thay đổi nội dung
 * và dòng tiêu đề email gửi cho người dùng mới đăng ký.
 *
 * @since MU (3.0.0)
 *
 * @param string $user_login Tên đăng nhập của người dùng.
 * @param string $user_email Địa chỉ email của người dùng.
 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_user()
 * @param array  $meta       Tùy chọn. Dữ liệu meta đăng ký. Mặc định mảng rỗng.
 * @return bool
 */
function wpmu_signup_user_notification(
	$user_login,
	$user_email,
	#[\SensitiveParameter]
	$key,
	$meta = array()
) {
	/**
	 * Lọc xem có bỏ qua thông báo email cho đăng ký người dùng mới hay không.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $user_login Tên đăng nhập người dùng.
	 * @param string $user_email Địa chỉ email người dùng.
	 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_user().
	 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định mảng rỗng.
	 */
	if ( ! apply_filters( 'wpmu_signup_user_notification', $user_login, $user_email, $key, $meta ) ) {
		return false;
	}

	$user            = get_user_by( 'login', $user_login );
	$switched_locale = $user && switch_to_user_locale( $user->ID );

	// Gửi email với liên kết kích hoạt.
	$admin_email = get_site_option( 'admin_email' );

	if ( '' === $admin_email ) {
		$admin_email = 'support@' . wp_parse_url( network_home_url(), PHP_URL_HOST );
	}

	$from_name       = ( '' !== get_site_option( 'site_name' ) ) ? esc_html( get_site_option( 'site_name' ) ) : 'WordPress';
	$message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";
	$message         = sprintf(
		/**
		 * Lọc nội dung email thông báo cho đăng ký người dùng mới.
		 *
		 * Nội dung nên được định dạng để truyền qua wp_mail().
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string $content    Nội dung email thông báo.
		 * @param string $user_login Tên đăng nhập người dùng.
		 * @param string $user_email Địa chỉ email người dùng.
		 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_user().
		 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định mảng rỗng.
		 */
		apply_filters(
			'wpmu_signup_user_notification_email',
			/* translators: New user notification email. %s: Activation URL. */
			__( "To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login." ),
			$user_login,
			$user_email,
			$key,
			$meta
		),
		site_url( "wp-activate.php?key=$key" )
	);

	$subject = sprintf(
		/**
		 * Lọc tiêu đề email thông báo đăng ký người dùng mới.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string $subject    Tiêu đề email thông báo.
		 * @param string $user_login Tên đăng nhập người dùng.
		 * @param string $user_email Địa chỉ email người dùng.
		 * @param string $key        Khóa kích hoạt được tạo trong wpmu_signup_user().
		 * @param array  $meta       Dữ liệu meta đăng ký. Mặc định mảng rỗng.
		 */
		apply_filters(
			'wpmu_signup_user_notification_subject',
			/* translators: New user notification email subject. 1: Network title, 2: New user login. */
			_x( '[%1$s] Activate %2$s', 'New user notification email subject' ),
			$user_login,
			$user_email,
			$key,
			$meta
		),
		$from_name,
		$user_login
	);

	wp_mail( $user_email, wp_specialchars_decode( $subject ), $message, $message_headers );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	return true;
}

/**
 * Kích hoạt một đăng ký.
 *
 * Hook vào {@see 'wpmu_activate_user'} hoặc {@see 'wpmu_activate_blog'} cho các sự kiện
 * chỉ xảy ra khi người dùng hoặc site được tự tạo (vì
 * các action đó không được gọi khi người dùng và site được tạo
 * bởi Super Admin).
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $key Khóa kích hoạt được cung cấp cho người dùng.
 * @return array|WP_Error Mảng chứa thông tin về người dùng và/hoặc blog đã kích hoạt.
 */
function wpmu_activate_signup(
	#[\SensitiveParameter]
	$key
) {
	global $wpdb;

	$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key ) );

	if ( empty( $signup ) ) {
		return new WP_Error( 'invalid_key', __( 'Invalid activation key.' ) );
	}

	if ( $signup->active ) {
		if ( empty( $signup->domain ) ) {
			return new WP_Error( 'already_active', __( 'The user is already active.' ), $signup );
		} else {
			return new WP_Error( 'already_active', __( 'The site is already active.' ), $signup );
		}
	}

	$meta     = maybe_unserialize( $signup->meta );
	$password = wp_generate_password( 12, false );

	$user_id = username_exists( $signup->user_login );

	if ( ! $user_id ) {
		$user_id = wpmu_create_user( $signup->user_login, $password, $signup->user_email );
	} else {
		$user_already_exists = true;
	}

	if ( ! $user_id ) {
		return new WP_Error( 'create_user', __( 'Could not create user' ), $signup );
	}

	$now = current_time( 'mysql', true );

	if ( empty( $signup->domain ) ) {
		$wpdb->update(
			$wpdb->signups,
			array(
				'active'    => 1,
				'activated' => $now,
			),
			array( 'activation_key' => $key )
		);

		if ( isset( $user_already_exists ) ) {
			return new WP_Error( 'user_already_exists', __( 'That username is already activated.' ), $signup );
		}

		/**
		 * Kích hoạt ngay sau khi người dùng mới được kích hoạt.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param int    $user_id  ID người dùng.
		 * @param string $password Mật khẩu người dùng.
		 * @param array  $meta     Dữ liệu meta đăng ký.
		 */
		do_action( 'wpmu_activate_user', $user_id, $password, $meta );

		return array(
			'user_id'  => $user_id,
			'password' => $password,
			'meta'     => $meta,
		);
	}

	$blog_id = wpmu_create_blog( $signup->domain, $signup->path, $signup->title, $user_id, $meta, get_current_network_id() );

	// TODO: Phải làm gì nếu chúng ta tạo người dùng nhưng không thể tạo blog?
	if ( is_wp_error( $blog_id ) ) {
		/*
		 * Nếu blog đã bị chiếm, có nghĩa là lần kích hoạt blog trước đó
		 * đã thất bại giữa quá trình tạo blog và đặt cờ kích hoạt.
		 * Hãy đặt cờ kích hoạt và hướng dẫn người dùng đặt lại mật khẩu.
		 */
		if ( 'blog_taken' === $blog_id->get_error_code() ) {
			$blog_id->add_data( $signup );
			$wpdb->update(
				$wpdb->signups,
				array(
					'active'    => 1,
					'activated' => $now,
				),
				array( 'activation_key' => $key )
			);
		}
		return $blog_id;
	}

	$wpdb->update(
		$wpdb->signups,
		array(
			'active'    => 1,
			'activated' => $now,
		),
		array( 'activation_key' => $key )
	);

	/**
	 * Kích hoạt ngay sau khi site được kích hoạt.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param int    $blog_id       ID blog.
	 * @param int    $user_id       ID người dùng.
	 * @param string $password      Mật khẩu người dùng.
	 * @param string $signup_title  Tiêu đề site.
	 * @param array  $meta          Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
	 */
	do_action( 'wpmu_activate_blog', $blog_id, $user_id, $password, $signup->title, $meta );

	return array(
		'blog_id'  => $blog_id,
		'user_id'  => $user_id,
		'password' => $password,
		'title'    => $signup->title,
		'meta'     => $meta,
	);
}

/**
 * Xóa mục đăng ký liên quan khi người dùng bị xóa khỏi cơ sở dữ liệu.
 *
 * @since 5.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int      $id       ID của người dùng cần xóa.
 * @param int|null $reassign ID của người dùng để gán lại bài viết và liên kết.
 * @param WP_User  $user     Đối tượng người dùng.
 */
function wp_delete_signup_on_user_delete( $id, $reassign, $user ) {
	global $wpdb;

	$wpdb->delete( $wpdb->signups, array( 'user_login' => $user->user_login ) );
}

/**
 * Tạo người dùng.
 *
 * Hàm này chạy khi người dùng tự đăng ký cũng như khi
 * Super Admin tạo người dùng mới. Hook vào {@see 'wpmu_new_user'} cho các sự kiện
 * ảnh hưởng đến tất cả người dùng mới, nhưng chỉ trên Multisite (nếu không
 * hãy dùng {@see 'user_register'}).
 *
 * @since MU (3.0.0)
 *
 * @param string $user_name Tên đăng nhập của người dùng mới.
 * @param string $password  Mật khẩu của người dùng mới.
 * @param string $email     Địa chỉ email của người dùng mới.
 * @return int|false Trả về false khi thất bại, hoặc int $user_id khi thành công.
 */
function wpmu_create_user(
	$user_name,
	#[\SensitiveParameter]
	$password,
	$email
) {
	$user_name = preg_replace( '/\s+/', '', sanitize_user( $user_name, true ) );

	$user_id = wp_create_user( $user_name, $password, $email );
	if ( is_wp_error( $user_id ) ) {
		return false;
	}

	// Người dùng mới tạo không có vai trò hoặc quyền cho đến khi họ được thêm vào blog.
	delete_user_option( $user_id, 'capabilities' );
	delete_user_option( $user_id, 'user_level' );

	/**
	 * Kích hoạt ngay sau khi người dùng mới được tạo.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param int $user_id ID người dùng.
	 */
	do_action( 'wpmu_new_user', $user_id );

	return $user_id;
}

/**
 * Tạo một site.
 *
 * Hàm này chạy khi người dùng tự đăng ký site mới cũng như
 * khi Super Admin tạo site mới. Hook vào {@see 'wpmu_new_blog'}
 * cho các sự kiện ảnh hưởng đến tất cả site mới.
 *
 * Trên cài đặt thư mục con, $domain giống với tên miền của site chính,
 * và đường dẫn là tên thư mục con (ví dụ 'example.com'
 * và '/blog1/'). Trên cài đặt subdomain, $domain là subdomain mới +
 * tên miền gốc (ví dụ 'blog1.example.com'), và $path là '/'.
 *
 * @since MU (3.0.0)
 *
 * @param string $domain     Tên miền của site mới.
 * @param string $path       Đường dẫn của site mới.
 * @param string $title      Tiêu đề của site mới.
 * @param int    $user_id    ID người dùng của quản trị viên site mới.
 * @param array  $options    Tùy chọn. Mảng cặp key=>value dùng để đặt tùy chọn site ban đầu.
 *                           Nếu các key trạng thái hợp lệ được bao gồm ('public', 'archived', 'mature',
 *                           'spam', 'deleted', hoặc 'lang_id') trạng thái site tương ứng sẽ được
 *                           cập nhật. Nếu không, key và value sẽ dùng để đặt tùy chọn cho
 *                           site mới. Mặc định mảng rỗng.
 * @param int    $network_id Tùy chọn. ID mạng. Chỉ liên quan trên cài đặt đa mạng.
 *                           Mặc định 1.
 * @return int|WP_Error Trả về đối tượng WP_Error khi thất bại, ID site mới khi thành công.
 */
function wpmu_create_blog( $domain, $path, $title, $user_id, $options = array(), $network_id = 1 ) {
	$defaults = array(
		'public' => 0,
	);
	$options  = wp_parse_args( $options, $defaults );

	$title   = strip_tags( $title );
	$user_id = (int) $user_id;

	// Kiểm tra xem tên miền đã được sử dụng chưa. Chúng ta nên trả về thông báo lỗi.
	if ( domain_exists( $domain, $path, $network_id ) ) {
		return new WP_Error( 'blog_taken', __( 'Sorry, that site already exists!' ) );
	}

	if ( ! wp_installing() ) {
		wp_installing( true );
	}

	$allowed_data_fields = array( 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id' );

	$site_data = array_merge(
		array(
			'domain'     => $domain,
			'path'       => $path,
			'network_id' => $network_id,
		),
		array_intersect_key( $options, array_flip( $allowed_data_fields ) )
	);

	// Dữ liệu để truyền cho wp_initialize_site().
	$site_initialization_data = array(
		'title'   => $title,
		'user_id' => $user_id,
		'options' => array_diff_key( $options, array_flip( $allowed_data_fields ) ),
	);

	$blog_id = wp_insert_site( array_merge( $site_data, $site_initialization_data ) );

	if ( is_wp_error( $blog_id ) ) {
		return $blog_id;
	}

	wp_cache_set_sites_last_changed();

	return $blog_id;
}

/**
 * Thông báo cho quản trị viên mạng rằng site mới đã được kích hoạt.
 *
 * Lọc {@see 'newblog_notify_siteadmin'} để thay đổi nội dung
 * email thông báo.
 *
 * @since MU (3.0.0)
 * @since 5.1.0 $blog_id hiện hỗ trợ đầu vào từ action {@see 'wp_initialize_site'}.
 *
 * @param WP_Site|int $blog_id    Đối tượng hoặc ID của site mới.
 * @param string      $deprecated Không sử dụng.
 * @return bool
 */
function newblog_notify_siteadmin( $blog_id, $deprecated = '' ) {
	if ( is_object( $blog_id ) ) {
		$blog_id = $blog_id->blog_id;
	}

	if ( 'yes' !== get_site_option( 'registrationnotification' ) ) {
		return false;
	}

	$email = get_site_option( 'admin_email' );

	if ( ! is_email( $email ) ) {
		return false;
	}

	$options_site_url = esc_url( network_admin_url( 'settings.php' ) );

	switch_to_blog( $blog_id );
	$blogname = get_option( 'blogname' );
	$siteurl  = site_url();
	restore_current_blog();

	$msg = sprintf(
		/* translators: New site notification email. 1: Site URL, 2: User IP address, 3: URL to Network Settings screen. */
		__(
			'New Site: %1$s
URL: %2$s
Remote IP address: %3$s

Disable these notifications: %4$s'
		),
		$blogname,
		$siteurl,
		wp_unslash( $_SERVER['REMOTE_ADDR'] ),
		$options_site_url
	);
	/**
	 * Lọc nội dung tin nhắn của email kích hoạt site mới gửi
	 * cho quản trị viên mạng.
	 *
	 * @since MU (3.0.0)
	 * @since 5.4.0 Thêm tham số `$blog_id`.
	 *
	 * @param string     $msg     Nội dung email.
	 * @param int|string $blog_id ID của site mới dưới dạng số nguyên hoặc chuỗi số.
	 */
	$msg = apply_filters( 'newblog_notify_siteadmin', $msg, $blog_id );

	/* translators: New site notification email subject. %s: New site URL. */
	wp_mail( $email, sprintf( __( 'New Site Registration: %s' ), $siteurl ), $msg );

	return true;
}

/**
 * Thông báo cho quản trị viên mạng rằng người dùng mới đã được kích hoạt.
 *
 * Lọc {@see 'newuser_notify_siteadmin'} để thay đổi nội dung
 * email thông báo.
 *
 * @since MU (3.0.0)
 *
 * @param int $user_id ID của người dùng mới.
 * @return bool
 */
function newuser_notify_siteadmin( $user_id ) {
	if ( 'yes' !== get_site_option( 'registrationnotification' ) ) {
		return false;
	}

	$email = get_site_option( 'admin_email' );

	if ( ! is_email( $email ) ) {
		return false;
	}

	$user = get_userdata( $user_id );

	$options_site_url = esc_url( network_admin_url( 'settings.php' ) );

	$msg = sprintf(
		/* translators: New user notification email. 1: User login, 2: User IP address, 3: URL to Network Settings screen. */
		__(
			'New User: %1$s
Remote IP address: %2$s

Disable these notifications: %3$s'
		),
		$user->user_login,
		wp_unslash( $_SERVER['REMOTE_ADDR'] ),
		$options_site_url
	);

	/**
	 * Lọc nội dung tin nhắn của email kích hoạt người dùng mới gửi
	 * cho quản trị viên mạng.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string  $msg  Nội dung email.
	 * @param WP_User $user Đối tượng WP_User của người dùng mới.
	 */
	$msg = apply_filters( 'newuser_notify_siteadmin', $msg, $user );

	/* translators: New user notification email subject. %s: User login. */
	wp_mail( $email, sprintf( __( 'New User Registration: %s' ), $user->user_login ), $msg );

	return true;
}

/**
 * Kiểm tra xem tên site đã được sử dụng chưa.
 *
 * Tên là subdomain của site hoặc đường dẫn thư mục con
 * của site tùy thuộc vào cài đặt mạng.
 *
 * Được sử dụng trong quá trình đăng ký site mới để đảm bảo
 * rằng mỗi tên site là duy nhất.
 *
 * @since MU (3.0.0)
 *
 * @param string $domain     Tên miền cần kiểm tra.
 * @param string $path       Đường dẫn cần kiểm tra.
 * @param int    $network_id Tùy chọn. ID mạng. Chỉ liên quan trên cài đặt đa mạng.
 *                           Mặc định 1.
 * @return int|null ID site nếu tên site tồn tại, null nếu không.
 */
function domain_exists( $domain, $path, $network_id = 1 ) {
	$path   = trailingslashit( $path );
	$args   = array(
		'network_id'             => $network_id,
		'domain'                 => $domain,
		'path'                   => $path,
		'fields'                 => 'ids',
		'number'                 => 1,
		'update_site_meta_cache' => false,
	);
	$result = get_sites( $args );
	$result = array_shift( $result );

	/**
	 * Lọc xem tên site đã được sử dụng chưa.
	 *
	 * Tên là subdomain của site hoặc đường dẫn thư mục con
	 * của site tùy thuộc vào cài đặt mạng.
	 *
	 * @since 3.5.0
	 *
	 * @param int|null $result     ID site nếu tên site tồn tại, null nếu không.
	 * @param string   $domain     Tên miền cần kiểm tra.
	 * @param string   $path       Đường dẫn cần kiểm tra.
	 * @param int      $network_id ID mạng. Chỉ liên quan trên cài đặt đa mạng.
	 */
	return apply_filters( 'domain_exists', $result, $domain, $path, $network_id );
}

/**
 * Thông báo cho quản trị viên site rằng việc kích hoạt site của họ đã thành công.
 *
 * Lọc {@see 'wpmu_welcome_notification'} để tắt hoặc bỏ qua.
 *
 * Lọc {@see 'update_welcome_email'} và {@see 'update_welcome_subject'} để
 * sửa đổi nội dung và dòng tiêu đề của email thông báo.
 *
 * @since MU (3.0.0)
 *
 * @param int    $blog_id  ID site.
 * @param int    $user_id  ID người dùng.
 * @param string $password Mật khẩu người dùng, hoặc "N/A" nếu tài khoản người dùng không mới.
 * @param string $title    Tiêu đề site.
 * @param array  $meta     Tùy chọn. Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
 * @return bool Liệu email thông báo đã được gửi hay chưa.
 */
function wpmu_welcome_notification(
	$blog_id,
	$user_id,
	#[\SensitiveParameter]
	$password,
	$title,
	$meta = array()
) {
	$current_network = get_network();

	/**
	 * Lọc xem có bỏ qua email chào mừng gửi cho quản trị viên site sau khi kích hoạt site hay không.
	 *
	 * Trả về false để tắt email chào mừng.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param int|false $blog_id  ID site, hoặc false để ngăn email gửi đi.
	 * @param int       $user_id  ID người dùng của quản trị viên site.
	 * @param string    $password Mật khẩu người dùng, hoặc "N/A" nếu tài khoản người dùng không mới.
	 * @param string    $title    Tiêu đề site.
	 * @param array     $meta     Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
	 */
	if ( ! apply_filters( 'wpmu_welcome_notification', $blog_id, $user_id, $password, $title, $meta ) ) {
		return false;
	}

	$user = get_userdata( $user_id );

	$switched_locale = switch_to_user_locale( $user_id );

	$welcome_email = get_site_option( 'welcome_email' );

	if ( ! $welcome_email ) {
		/* translators: Do not translate USERNAME, SITE_NAME, BLOG_URL, PASSWORD: those are placeholders. */
		$welcome_email = __(
			'Howdy USERNAME,

Your new SITE_NAME site has been successfully set up at:
BLOG_URL

You can log in to the administrator account with the following information:

Username: USERNAME
Password: PASSWORD
Log in here: BLOG_URLwp-login.php

We hope you enjoy your new site. Thanks!

--The Team @ SITE_NAME'
		);
	}

	$url = get_blogaddress_by_id( $blog_id );

	$welcome_email = str_replace( 'SITE_NAME', $current_network->site_name, $welcome_email );
	$welcome_email = str_replace( 'BLOG_TITLE', $title, $welcome_email );
	$welcome_email = str_replace( 'BLOG_URL', $url, $welcome_email );
	$welcome_email = str_replace( 'USERNAME', $user->user_login, $welcome_email );
	$welcome_email = str_replace( 'PASSWORD', $password, $welcome_email );

	/**
	 * Lọc nội dung email chào mừng gửi cho quản trị viên site sau khi kích hoạt site.
	 *
	 * Nội dung nên được định dạng để truyền qua wp_mail().
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $welcome_email Nội dung tin nhắn của email.
	 * @param int    $blog_id       ID site.
	 * @param int    $user_id       ID người dùng của quản trị viên site.
	 * @param string $password      Mật khẩu người dùng, hoặc "N/A" nếu tài khoản người dùng không mới.
	 * @param string $title         Tiêu đề site.
	 * @param array  $meta          Dữ liệu meta đăng ký. Mặc định chứa cài đặt quyền riêng tư và lang_id.
	 */
	$welcome_email = apply_filters( 'update_welcome_email', $welcome_email, $blog_id, $user_id, $password, $title, $meta );

	$admin_email = get_site_option( 'admin_email' );

	if ( '' === $admin_email ) {
		$admin_email = 'support@' . wp_parse_url( network_home_url(), PHP_URL_HOST );
	}

	$from_name       = ( '' !== get_site_option( 'site_name' ) ) ? esc_html( get_site_option( 'site_name' ) ) : 'WordPress';
	$message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";
	$message         = $welcome_email;

	if ( empty( $current_network->site_name ) ) {
		$current_network->site_name = 'WordPress';
	}

	/* translators: New site notification email subject. 1: Network title, 2: New site title. */
	$subject = __( 'New %1$s Site: %2$s' );

	/**
	 * Lọc tiêu đề email chào mừng gửi cho quản trị viên site sau khi kích hoạt site.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $subject Tiêu đề của email.
	 */
	$subject = apply_filters( 'update_welcome_subject', sprintf( $subject, $current_network->site_name, wp_unslash( $title ) ) );

	wp_mail( $user->user_email, wp_specialchars_decode( $subject ), $message, $message_headers );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	return true;
}

/**
 * Thông báo cho quản trị viên mạng Multisite rằng site mới đã được tạo.
 *
 * Lọc {@see 'send_new_site_email'} để tắt hoặc bỏ qua.
 *
 * Lọc {@see 'new_site_email'} để lọc nội dung.
 *
 * @since 5.6.0
 *
 * @param int $site_id ID site của site mới.
 * @param int $user_id ID người dùng của quản trị viên site mới.
 * @return bool Liệu email thông báo đã được gửi hay chưa.
 */
function wpmu_new_site_admin_notification( $site_id, $user_id ) {
	$site  = get_site( $site_id );
	$user  = get_userdata( $user_id );
	$email = get_site_option( 'admin_email' );

	if ( ! $site || ! $user || ! $email ) {
		return false;
	}

	/**
	 * Lọc xem có gửi email cho quản trị viên mạng Multisite khi site mới được tạo hay không.
	 *
	 * Trả về false để tắt việc gửi email.
	 *
	 * @since 5.6.0
	 *
	 * @param bool    $send Có gửi email hay không.
	 * @param WP_Site $site Đối tượng site của site mới.
	 * @param WP_User $user Đối tượng người dùng của quản trị viên site mới.
	 */
	if ( ! apply_filters( 'send_new_site_email', true, $site, $user ) ) {
		return false;
	}

	$switched_locale = false;
	$network_admin   = get_user_by( 'email', $email );

	if ( $network_admin ) {
		// Nếu địa chỉ email quản trị viên mạng tương ứng với một người dùng, chuyển sang locale của họ.
		$switched_locale = switch_to_user_locale( $network_admin->ID );
	} else {
		// Nếu không, chuyển sang locale của site hiện tại.
		$switched_locale = switch_to_locale( get_locale() );
	}

	$subject = sprintf(
		/* translators: New site notification email subject. %s: Network title. */
		__( '[%s] New Site Created' ),
		get_network()->site_name
	);

	$message = sprintf(
		/* translators: New site notification email. 1: User login, 2: Site URL, 3: Site title. */
		__(
			'New site created by %1$s

Address: %2$s
Name: %3$s'
		),
		$user->user_login,
		get_site_url( $site->id ),
		get_blog_option( $site->id, 'blogname' )
	);

	$header = sprintf(
		'From: "%1$s" <%2$s>',
		_x( 'Site Admin', 'email "From" field' ),
		$email
	);

	$new_site_email = array(
		'to'      => $email,
		'subject' => $subject,
		'message' => $message,
		'headers' => $header,
	);

	/**
	 * Lọc nội dung email gửi cho quản trị viên mạng Multisite khi site mới được tạo.
	 *
	 * Nội dung nên được định dạng để truyền qua wp_mail().
	 *
	 * @since 5.6.0
	 *
	 * @param array $new_site_email {
	 *     Dùng để xây dựng wp_mail().
	 *
	 *     @type string $to      Địa chỉ email của người nhận.
	 *     @type string $subject Tiêu đề của email.
	 *     @type string $message Nội dung của email.
	 *     @type string $headers Tiêu đề email.
	 * }
	 * @param WP_Site $site         Đối tượng site của site mới.
	 * @param WP_User $user         Đối tượng người dùng của quản trị viên site mới.
	 */
	$new_site_email = apply_filters( 'new_site_email', $new_site_email, $site, $user );

	wp_mail(
		$new_site_email['to'],
		wp_specialchars_decode( $new_site_email['subject'] ),
		$new_site_email['message'],
		$new_site_email['headers']
	);

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	return true;
}

/**
 * Thông báo cho người dùng rằng việc kích hoạt tài khoản của họ đã thành công.
 *
 * Lọc {@see 'wpmu_welcome_user_notification'} để tắt hoặc bỏ qua.
 *
 * Lọc {@see 'update_welcome_user_email'} và {@see 'update_welcome_user_subject'} để
 * sửa đổi nội dung và dòng tiêu đề của email thông báo.
 *
 * @since MU (3.0.0)
 *
 * @param int    $user_id  ID người dùng.
 * @param string $password Mật khẩu người dùng.
 * @param array  $meta     Tùy chọn. Dữ liệu meta đăng ký. Mặc định mảng rỗng.
 * @return bool
 */
function wpmu_welcome_user_notification(
	$user_id,
	#[\SensitiveParameter]
	$password,
	$meta = array()
) {
	$current_network = get_network();

	/**
	 * Lọc xem có bỏ qua email chào mừng sau khi kích hoạt người dùng hay không.
	 *
	 * Trả về false để tắt email chào mừng.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param int    $user_id  ID người dùng.
	 * @param string $password Mật khẩu người dùng.
	 * @param array  $meta     Dữ liệu meta đăng ký. Mặc định mảng rỗng.
	 */
	if ( ! apply_filters( 'wpmu_welcome_user_notification', $user_id, $password, $meta ) ) {
		return false;
	}

	$welcome_email = get_site_option( 'welcome_user_email' );

	$user = get_userdata( $user_id );

	$switched_locale = switch_to_user_locale( $user_id );

	/**
	 * Lọc nội dung email chào mừng sau khi kích hoạt người dùng.
	 *
	 * Nội dung nên được định dạng để truyền qua wp_mail().
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $welcome_email Nội dung tin nhắn của email kích hoạt tài khoản thành công.
	 * @param int    $user_id       ID người dùng.
	 * @param string $password      Mật khẩu người dùng.
	 * @param array  $meta          Dữ liệu meta đăng ký. Mặc định mảng rỗng.
	 */
	$welcome_email = apply_filters( 'update_welcome_user_email', $welcome_email, $user_id, $password, $meta );
	$welcome_email = str_replace( 'SITE_NAME', $current_network->site_name, $welcome_email );
	$welcome_email = str_replace( 'USERNAME', $user->user_login, $welcome_email );
	$welcome_email = str_replace( 'PASSWORD', $password, $welcome_email );
	$welcome_email = str_replace( 'LOGINLINK', wp_login_url(), $welcome_email );

	$admin_email = get_site_option( 'admin_email' );

	if ( '' === $admin_email ) {
		$admin_email = 'support@' . wp_parse_url( network_home_url(), PHP_URL_HOST );
	}

	$from_name       = ( '' !== get_site_option( 'site_name' ) ) ? esc_html( get_site_option( 'site_name' ) ) : 'WordPress';
	$message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";
	$message         = $welcome_email;

	if ( empty( $current_network->site_name ) ) {
		$current_network->site_name = 'WordPress';
	}

	/* translators: New user notification email subject. 1: Network title, 2: New user login. */
	$subject = __( 'New %1$s User: %2$s' );

	/**
	 * Lọc tiêu đề email chào mừng sau khi kích hoạt người dùng.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param string $subject Tiêu đề của email.
	 */
	$subject = apply_filters( 'update_welcome_user_subject', sprintf( $subject, $current_network->site_name, $user->user_login ) );

	wp_mail( $user->user_email, wp_specialchars_decode( $subject ), $message, $message_headers );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	return true;
}

/**
 * Lấy mạng hiện tại.
 *
 * Trả về một đối tượng chứa các thuộc tính 'id', 'domain', 'path', và 'site_name'
 * của mạng đang được xem.
 *
 * @see wpmu_current_site()
 *
 * @since MU (3.0.0)
 *
 * @global WP_Network $current_site Mạng hiện tại.
 *
 * @return WP_Network Mạng hiện tại.
 */
function get_current_site() {
	global $current_site;
	return $current_site;
}

/**
 * Lấy bài viết gần đây nhất của người dùng.
 *
 * Duyệt qua từng blog của người dùng để tìm bài viết có
 * post_date_gmt gần nhất.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $user_id ID người dùng.
 * @return array Chứa blog_id, post_id, post_date_gmt, và post_gmt_ts.
 */
function get_most_recent_post_of_user( $user_id ) {
	global $wpdb;

	$user_blogs       = get_blogs_of_user( (int) $user_id );
	$most_recent_post = array();

	/*
	 * Duyệt qua từng blog và lấy bài viết gần nhất
	 * được xuất bản bởi $user_id.
	 */
	foreach ( (array) $user_blogs as $blog ) {
		$prefix      = $wpdb->get_blog_prefix( $blog->userblog_id );
		$recent_post = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_date_gmt FROM {$prefix}posts WHERE post_author = %d AND post_type = 'post' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1", $user_id ), ARRAY_A );

		// Đảm bảo chúng ta tìm thấy bài viết.
		if ( isset( $recent_post['ID'] ) ) {
			$post_gmt_ts = strtotime( $recent_post['post_date_gmt'] );

			/*
			 * Nếu đây là bài viết đầu tiên được kiểm tra
			 * hoặc nếu bài viết này mới hơn bài viết gần đây hiện tại,
			 * đặt nó làm bài viết gần đây nhất mới.
			 */
			if ( ! isset( $most_recent_post['post_gmt_ts'] ) || ( $post_gmt_ts > $most_recent_post['post_gmt_ts'] ) ) {
				$most_recent_post = array(
					'blog_id'       => $blog->userblog_id,
					'post_id'       => $recent_post['ID'],
					'post_date_gmt' => $recent_post['post_date_gmt'],
					'post_gmt_ts'   => $post_gmt_ts,
				);
			}
		}
	}

	return $most_recent_post;
}

//
// Các hàm linh tinh.
//

/**
 * Kiểm tra mảng các loại MIME dựa trên danh sách các loại được phép.
 *
 * WordPress được cung cấp sẵn một bộ các loại file upload được phép,
 * được định nghĩa trong wp-includes/functions.php trong
 * get_allowed_mime_types(). Hàm này được sử dụng để lọc
 * danh sách đó dựa trên các loại file được phép do Super Admin
 * Multisite cung cấp tại wp-admin/network/settings.php.
 *
 * @since MU (3.0.0)
 *
 * @param array $mimes
 * @return array
 */
function check_upload_mimes( $mimes ) {
	$site_exts  = explode( ' ', get_site_option( 'upload_filetypes', 'jpg jpeg png gif' ) );
	$site_mimes = array();
	foreach ( $site_exts as $ext ) {
		foreach ( $mimes as $ext_pattern => $mime ) {
			if ( '' !== $ext && str_contains( $ext_pattern, $ext ) ) {
				$site_mimes[ $ext_pattern ] = $mime;
			}
		}
	}
	return $site_mimes;
}

/**
 * Cập nhật số lượng bài viết của blog.
 *
 * WordPress MS lưu trữ số lượng bài viết của blog dưới dạng tùy chọn để
 * tránh các truy vấn COUNT không cần thiết khi chi tiết blog được lấy
 * bằng get_site(). Hàm này được gọi khi bài viết được xuất bản
 * hoặc hủy xuất bản để đảm bảo số đếm luôn cập nhật.
 *
 * @since MU (3.0.0)
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $deprecated Không sử dụng.
 */
function update_posts_count( $deprecated = '' ) {
	global $wpdb;
	update_option( 'post_count', (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'publish' and post_type = 'post'" ), true );
}

/**
 * Ghi lại email người dùng, IP, và ngày đăng ký của site mới.
 *
 * @since MU (3.0.0)
 * @since 5.1.0 Các tham số hiện hỗ trợ đầu vào từ action {@see 'wp_initialize_site'}.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param WP_Site|int $blog_id Đối tượng hoặc ID của site mới.
 * @param int|array   $user_id ID người dùng, hoặc mảng các tham số bao gồm 'user_id'.
 */
function wpmu_log_new_registrations( $blog_id, $user_id ) {
	global $wpdb;

	if ( is_object( $blog_id ) ) {
		$blog_id = $blog_id->blog_id;
	}

	if ( is_array( $user_id ) ) {
		$user_id = ! empty( $user_id['user_id'] ) ? $user_id['user_id'] : 0;
	}

	$user = get_userdata( (int) $user_id );
	if ( $user ) {
		$wpdb->insert(
			$wpdb->registration_log,
			array(
				'email'           => $user->user_email,
				'IP'              => preg_replace( '/[^0-9., ]/', '', wp_unslash( $_SERVER['REMOTE_ADDR'] ) ),
				'blog_id'         => $blog_id,
				'date_registered' => current_time( 'mysql' ),
			)
		);
	}
}

/**
 * Đảm bảo rằng tên miền của site hiện tại nằm trong danh sách host chuyển hướng được phép.
 *
 * @see wp_validate_redirect()
 * @since MU (3.0.0)
 *
 * @param array|string $deprecated Không sử dụng.
 * @return string[] {
 *     Mảng chứa tên miền của site hiện tại.
 *
 *     @type string $0 Tên miền của site hiện tại.
 * }
 */
function redirect_this_site( $deprecated = '' ) {
	return array( get_network()->domain );
}

/**
 * Kiểm tra xem file upload có quá lớn hay không.
 *
 * @since MU (3.0.0)
 *
 * @param array $upload Mảng thông tin về file vừa được upload.
 * @return string|array Nếu file upload dưới giới hạn kích thước, trả về $upload. Ngược lại trả về thông báo lỗi.
 */
function upload_is_file_too_big( $upload ) {
	if ( ! is_array( $upload ) || defined( 'WP_IMPORTING' ) || get_site_option( 'upload_space_check_disabled' ) ) {
		return $upload;
	}

	if ( strlen( $upload['bits'] ) > ( KB_IN_BYTES * get_site_option( 'fileupload_maxk', 1500 ) ) ) {
		/* translators: %s: Maximum allowed file size in kilobytes. */
		return sprintf( __( 'This file is too big. Files must be less than %s KB in size.' ) . '<br />', get_site_option( 'fileupload_maxk', 1500 ) );
	}

	return $upload;
}

/**
 * Thêm trường nonce vào trang đăng ký.
 *
 * @since MU (3.0.0)
 */
function signup_nonce_fields() {
	$id = mt_rand();
	echo "<input type='hidden' name='signup_form_id' value='{$id}' />";
	wp_nonce_field( 'signup_form_' . $id, '_signup_form', false );
}

/**
 * Xử lý nonce đăng ký được tạo trong signup_nonce_fields().
 *
 * @since MU (3.0.0)
 *
 * @param array $result
 * @return array
 */
function signup_nonce_check( $result ) {
	if ( ! strpos( $_SERVER['PHP_SELF'], 'wp-signup.php' ) ) {
		return $result;
	}

	if ( ! wp_verify_nonce( $_POST['_signup_form'], 'signup_form_' . $_POST['signup_form_id'] ) ) {
		$result['errors']->add( 'invalid_nonce', __( 'Unable to submit this form, please try again.' ) );
	}

	return $result;
}

/**
 * Sửa chuyển hướng 404 khi NOBLOGREDIRECT được định nghĩa.
 *
 * @since MU (3.0.0)
 */
function maybe_redirect_404() {
	if ( is_main_site() && is_404() && defined( 'NOBLOGREDIRECT' ) ) {
		/**
		 * Lọc URL chuyển hướng cho lỗi 404 trên site chính.
		 *
		 * Bộ lọc chỉ được đánh giá nếu hằng số NOBLOGREDIRECT được định nghĩa.
		 *
		 * @since 3.0.0
		 *
		 * @param string $no_blog_redirect URL chuyển hướng được định nghĩa trong NOBLOGREDIRECT.
		 */
		$destination = apply_filters( 'blog_redirect_404', NOBLOGREDIRECT );

		if ( $destination ) {
			if ( '%siteurl%' === $destination ) {
				$destination = network_home_url();
			}

			wp_redirect( $destination );
			exit;
		}
	}
}

/**
 * Thêm người dùng mới vào blog bằng cách truy cập /newbloguser/{key}/.
 *
 * Điều này chỉ hoạt động khi chi tiết người dùng được lưu dưới dạng tùy chọn
 * với khóa là 'new_user_{key}', trong đó '{key}' là hash được tạo cho người dùng
 * cần thêm, như khi người dùng được mời qua giao diện Thêm Người dùng WP thông thường.
 *
 * @since MU (3.0.0)
 */
function maybe_add_existing_user_to_blog() {
	if ( ! str_contains( $_SERVER['REQUEST_URI'], '/newbloguser/' ) ) {
		return;
	}

	$parts = explode( '/', $_SERVER['REQUEST_URI'] );
	$key   = array_pop( $parts );

	if ( '' === $key ) {
		$key = array_pop( $parts );
	}

	$details = get_option( 'new_user_' . $key );
	if ( ! empty( $details ) ) {
		delete_option( 'new_user_' . $key );
	}

	if ( empty( $details ) || is_wp_error( add_existing_user_to_blog( $details ) ) ) {
		wp_die(
			sprintf(
				/* translators: %s: Home URL. */
				__( 'An error occurred adding you to this site. Go to the <a href="%s">homepage</a>.' ),
				home_url()
			)
		);
	}

	wp_die(
		sprintf(
			/* translators: 1: Home URL, 2: Admin URL. */
			__( 'You have been added to this site. Please visit the <a href="%1$s">homepage</a> or <a href="%2$s">log in</a> using your username and password.' ),
			home_url(),
			admin_url()
		),
		__( 'WordPress &rsaquo; Success' ),
		array( 'response' => 200 )
	);
}

/**
 * Thêm người dùng vào blog dựa trên chi tiết từ maybe_add_existing_user_to_blog().
 *
 * @since MU (3.0.0)
 *
 * @param array|false $details {
 *     Chi tiết người dùng. Phải chứa ít nhất các giá trị cho các khóa liệt kê bên dưới.
 *
 *     @type int    $user_id ID của người dùng được thêm vào blog hiện tại.
 *     @type string $role    Vai trò được gán cho người dùng.
 * }
 * @return true|WP_Error|void True khi thành công hoặc đối tượng WP_Error nếu người dùng không tồn tại
 *                            hoặc không thể được thêm. Void nếu mảng $details không được cung cấp.
 */
function add_existing_user_to_blog( $details = false ) {
	if ( is_array( $details ) ) {
		$blog_id = get_current_blog_id();
		$result  = add_user_to_blog( $blog_id, $details['user_id'], $details['role'] );

		/**
		 * Kích hoạt ngay sau khi người dùng hiện tại được thêm vào site.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param int           $user_id ID người dùng.
		 * @param true|WP_Error $result  True khi thành công hoặc đối tượng WP_Error nếu người dùng không tồn tại
		 *                               hoặc không thể được thêm.
		 */
		do_action( 'added_existing_user', $details['user_id'], $result );

		return $result;
	}
}

/**
 * Thêm người dùng mới tạo vào blog phù hợp.
 *
 * Để thêm người dùng nói chung, sử dụng add_user_to_blog(). Hàm này
 * được hook cụ thể vào action {@see 'wpmu_activate_user'}.
 *
 * @since MU (3.0.0)
 *
 * @see add_user_to_blog()
 *
 * @param int    $user_id  ID người dùng.
 * @param string $password Mật khẩu người dùng. Bị bỏ qua.
 * @param array  $meta     Dữ liệu meta đăng ký.
 */
function add_new_user_to_blog(
	$user_id,
	#[\SensitiveParameter]
	$password,
	$meta
) {
	if ( ! empty( $meta['add_to_blog'] ) ) {
		$blog_id = $meta['add_to_blog'];
		$role    = $meta['new_role'];
		remove_user_from_blog( $user_id, get_network()->site_id ); // Xóa người dùng khỏi blog chính.

		$result = add_user_to_blog( $blog_id, $user_id, $role );

		if ( ! is_wp_error( $result ) ) {
			update_user_meta( $user_id, 'primary_blog', $blog_id );
		}
	}
}

/**
 * Sửa host From trên email gửi đi để khớp với tên miền site.
 *
 * @since MU (3.0.0)
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Đối tượng PHPMailer (được truyền theo tham chiếu).
 */
function fix_phpmailer_messageid( $phpmailer ) {
	$phpmailer->Hostname = get_network()->domain;
}

/**
 * Xác định xem người dùng có bị đánh dấu là spammer hay không, dựa trên tên đăng nhập.
 *
 * @since MU (3.0.0)
 *
 * @param string|WP_User $user Tùy chọn. Mặc định là người dùng hiện tại. Đối tượng WP_User,
 *                             hoặc tên đăng nhập dưới dạng chuỗi.
 * @return bool
 */
function is_user_spammy( $user = null ) {
	if ( ! ( $user instanceof WP_User ) ) {
		if ( $user ) {
			$user = get_user_by( 'login', $user );
		} else {
			$user = wp_get_current_user();
		}
	}

	return $user && isset( $user->spam ) && '1' === $user->spam;
}

/**
 * Cập nhật cài đặt 'public' của blog này trong bảng blogs toàn cục.
 *
 * Blog công khai có giá trị 1, blog riêng tư là 0.
 *
 * @since MU (3.0.0)
 *
 * @param int $old_value Giá trị public cũ.
 * @param int $value     Giá trị public mới.
 */
function update_blog_public( $old_value, $value ) {
	update_blog_status( get_current_blog_id(), 'public', (int) $value );
}

/**
 * Xác định xem người dùng có thể tự đăng ký hay không, dựa trên cài đặt Mạng.
 *
 * @since MU (3.0.0)
 *
 * @return bool
 */
function users_can_register_signup_filter() {
	$registration = get_site_option( 'registration' );
	return ( 'all' === $registration || 'user' === $registration );
}

/**
 * Đảm bảo rằng tin nhắn chào mừng không rỗng. Hiện không sử dụng.
 *
 * @since MU (3.0.0)
 *
 * @param string $text
 * @return string
 */
function welcome_user_msg_filter( $text ) {
	if ( ! $text ) {
		remove_filter( 'site_option_welcome_user_email', 'welcome_user_msg_filter' );

		/* translators: Do not translate USERNAME, PASSWORD, LOGINLINK, SITE_NAME: those are placeholders. */
		$text = __(
			'Howdy USERNAME,

Your new account is set up.

You can log in with the following information:
Username: USERNAME
Password: PASSWORD
LOGINLINK

Thanks!

--The Team @ SITE_NAME'
		);
		update_site_option( 'welcome_user_email', $text );
	}
	return $text;
}

/**
 * Xác định xem có buộc SSL trên nội dung hay không.
 *
 * @since 2.8.5
 *
 * @param bool $force
 * @return bool True nếu bị buộc, false nếu không.
 */
function force_ssl_content( $force = '' ) {
	static $forced_content = false;

	if ( ! $force ) {
		$old_forced     = $forced_content;
		$forced_content = $force;
		return $old_forced;
	}

	return $forced_content;
}

/**
 * Định dạng URL để sử dụng https.
 *
 * Hữu ích như một bộ lọc.
 *
 * @since 2.8.5
 *
 * @param string $url URL.
 * @return string URL với https làm giao thức.
 */
function filter_SSL( $url ) {  // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	if ( ! is_string( $url ) ) {
		return get_bloginfo( 'url' ); // Trả về URL trang chủ với giao thức đúng.
	}

	if ( force_ssl_content() && is_ssl() ) {
		$url = set_url_scheme( $url, 'https' );
	}

	return $url;
}

/**
 * Lên lịch cập nhật số đếm toàn mạng cho mạng hiện tại.
 *
 * @since 3.1.0
 */
function wp_schedule_update_network_counts() {
	if ( ! is_main_site() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'update_network_counts' ) && ! wp_installing() ) {
		wp_schedule_event( time(), 'twicedaily', 'update_network_counts' );
	}
}

/**
 * Cập nhật số đếm toàn mạng cho mạng hiện tại.
 *
 * @since 3.1.0
 * @since 4.8.0 Thêm tham số `$network_id`.
 *
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 */
function wp_update_network_counts( $network_id = null ) {
	wp_update_network_user_counts( $network_id );
	wp_update_network_site_counts( $network_id );
}

/**
 * Cập nhật số lượng site cho mạng hiện tại.
 *
 * Nếu được bật qua bộ lọc {@see 'enable_live_network_counts'}, cập nhật số đếm site
 * trên mạng khi site được tạo hoặc trạng thái của nó được cập nhật.
 *
 * @since 3.7.0
 * @since 4.8.0 Thêm tham số `$network_id`.
 *
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 */
function wp_maybe_update_network_site_counts( $network_id = null ) {
	$is_small_network = ! wp_is_large_network( 'sites', $network_id );

	/**
	 * Lọc xem có cập nhật số đếm site hoặc người dùng mạng khi site mới được tạo hay không.
	 *
	 * @since 3.7.0
	 *
	 * @see wp_is_large_network()
	 *
	 * @param bool   $small_network Liệu mạng có được coi là nhỏ hay không.
	 * @param string $context       Ngữ cảnh. Hoặc 'users' hoặc 'sites'.
	 */
	if ( ! apply_filters( 'enable_live_network_counts', $is_small_network, 'sites' ) ) {
		return;
	}

	wp_update_network_site_counts( $network_id );
}

/**
 * Cập nhật số lượng người dùng toàn mạng.
 *
 * Nếu được bật qua bộ lọc {@see 'enable_live_network_counts'}, cập nhật số đếm người dùng
 * trên mạng khi người dùng được tạo hoặc trạng thái của họ được cập nhật.
 *
 * @since 3.7.0
 * @since 4.8.0 Thêm tham số `$network_id`.
 *
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 */
function wp_maybe_update_network_user_counts( $network_id = null ) {
	$is_small_network = ! wp_is_large_network( 'users', $network_id );

	/** Bộ lọc này được ghi chú trong wp-includes/ms-functions.php */
	if ( ! apply_filters( 'enable_live_network_counts', $is_small_network, 'users' ) ) {
		return;
	}

	wp_update_network_user_counts( $network_id );
}

/**
 * Cập nhật số lượng site toàn mạng.
 *
 * @since 3.7.0
 * @since 4.8.0 Thêm tham số `$network_id`.
 *
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 */
function wp_update_network_site_counts( $network_id = null ) {
	$network_id = (int) $network_id;
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	$count = get_sites(
		array(
			'network_id'             => $network_id,
			'spam'                   => 0,
			'deleted'                => 0,
			'archived'               => 0,
			'count'                  => true,
			'update_site_meta_cache' => false,
		)
	);

	update_network_option( $network_id, 'blog_count', $count );
}

/**
 * Cập nhật số lượng người dùng toàn mạng.
 *
 * @since 3.7.0
 * @since 4.8.0 Thêm tham số `$network_id`.
 * @since 6.0.0 Hàm này hiện là wrapper cho wp_update_user_counts().
 *
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 */
function wp_update_network_user_counts( $network_id = null ) {
	wp_update_user_counts( $network_id );
}

/**
 * Trả về dung lượng được sử dụng bởi site hiện tại.
 *
 * @since 3.5.0
 *
 * @return int Dung lượng đã sử dụng tính bằng megabyte.
 */
function get_space_used() {
	/**
	 * Lọc dung lượng lưu trữ được sử dụng bởi site hiện tại, tính bằng megabyte.
	 *
	 * @since 3.5.0
	 *
	 * @param int|false $space_used Dung lượng đã sử dụng, tính bằng megabyte. Mặc định false.
	 */
	$space_used = apply_filters( 'pre_get_space_used', false );

	if ( false === $space_used ) {
		$upload_dir = wp_upload_dir();
		$space_used = get_dirsize( $upload_dir['basedir'] ) / MB_IN_BYTES;
	}

	return $space_used;
}

/**
 * Trả về hạn mức upload cho blog hiện tại.
 *
 * @since MU (3.0.0)
 *
 * @return int Hạn mức tính bằng megabyte.
 */
function get_space_allowed() {
	$space_allowed = get_option( 'blog_upload_space' );

	if ( ! is_numeric( $space_allowed ) ) {
		$space_allowed = get_site_option( 'blog_upload_space' );
	}

	if ( ! is_numeric( $space_allowed ) ) {
		$space_allowed = 100;
	}

	/**
	 * Lọc hạn mức upload cho site hiện tại.
	 *
	 * @since 3.7.0
	 *
	 * @param int $space_allowed Hạn mức upload tính bằng megabyte cho blog hiện tại.
	 */
	return apply_filters( 'get_space_allowed', $space_allowed );
}

/**
 * Xác định xem còn dung lượng upload nào trong hạn mức của blog hiện tại hay không.
 *
 * @since 3.0.0
 *
 * @return int Dung lượng upload còn trống tính bằng byte.
 */
function get_upload_space_available() {
	$allowed = get_space_allowed();
	if ( $allowed < 0 ) {
		$allowed = 0;
	}
	$space_allowed = $allowed * MB_IN_BYTES;
	if ( get_site_option( 'upload_space_check_disabled' ) ) {
		return $space_allowed;
	}

	$space_used = get_space_used() * MB_IN_BYTES;

	if ( ( $space_allowed - $space_used ) <= 0 ) {
		return 0;
	}

	return $space_allowed - $space_used;
}

/**
 * Xác định xem còn dung lượng upload nào trong hạn mức của blog hiện tại hay không.
 *
 * @since 3.0.0
 * @return bool True nếu còn dung lượng, false nếu không.
 */
function is_upload_space_available() {
	if ( get_site_option( 'upload_space_check_disabled' ) ) {
		return true;
	}

	return (bool) get_upload_space_available();
}

/**
 * Lọc kích thước file upload tối đa được phép, tính bằng byte.
 *
 * @since 3.0.0
 *
 * @param int $size Giới hạn kích thước upload tính bằng byte.
 * @return int Giới hạn kích thước upload tính bằng byte.
 */
function upload_size_limit_filter( $size ) {
	$fileupload_maxk         = (int) get_site_option( 'fileupload_maxk', 1500 );
	$max_fileupload_in_bytes = KB_IN_BYTES * $fileupload_maxk;

	if ( get_site_option( 'upload_space_check_disabled' ) ) {
		return min( $size, $max_fileupload_in_bytes );
	}

	return min( $size, $max_fileupload_in_bytes, get_upload_space_available() );
}

/**
 * Xác định xem chúng ta có mạng lớn hay không.
 *
 * Tiêu chí mặc định cho mạng lớn là hơn 10.000 người dùng hoặc hơn 10.000 site.
 * Plugin có thể thay đổi tiêu chí này bằng bộ lọc {@see 'wp_is_large_network'}.
 *
 * @since 3.3.0
 * @since 4.8.0 Thêm tham số `$network_id`.
 *
 * @param string   $using      'sites' hoặc 'users'. Mặc định là 'sites'.
 * @param int|null $network_id ID của mạng. Mặc định là mạng hiện tại.
 * @return bool True nếu mạng đáp ứng tiêu chí lớn. False nếu không.
 */
function wp_is_large_network( $using = 'sites', $network_id = null ) {
	$network_id = (int) $network_id;
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	if ( 'users' === $using ) {
		$count = get_user_count( $network_id );

		$is_large_network = wp_is_large_user_count( $network_id );

		/**
		 * Lọc xem mạng có được coi là lớn hay không.
		 *
		 * @since 3.3.0
		 * @since 4.8.0 Thêm tham số `$network_id`.
		 *
		 * @param bool   $is_large_network Liệu mạng có hơn 10000 người dùng hoặc site hay không.
		 * @param string $component        Thành phần cần đếm. Chấp nhận 'users', hoặc 'sites'.
		 * @param int    $count            Số lượng mục cho thành phần.
		 * @param int    $network_id       ID của mạng đang được kiểm tra.
		 */
		return apply_filters( 'wp_is_large_network', $is_large_network, 'users', $count, $network_id );
	}

	$count = get_blog_count( $network_id );

	/** Bộ lọc này được ghi chú trong wp-includes/ms-functions.php */
	return apply_filters( 'wp_is_large_network', $count > 10000, 'sites', $count, $network_id );
}

/**
 * Lấy danh sách các tên site được giữ riêng trên cài đặt Multisite thư mục con.
 *
 * @since 4.4.0
 *
 * @return string[] Mảng các tên được giữ riêng.
 */
function get_subdirectory_reserved_names() {
	$names = array(
		'page',
		'comments',
		'blog',
		'files',
		'feed',
		'wp-admin',
		'wp-content',
		'wp-includes',
		'wp-json',
		'embed',
	);

	/**
	 * Lọc các tên site được giữ riêng trên cài đặt Multisite thư mục con.
	 *
	 * @since 3.0.0
	 * @since 4.4.0 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', và 'embed' đã được thêm
	 *              vào danh sách tên được giữ riêng.
	 *
	 * @param string[] $subdirectory_reserved_names Mảng các tên được giữ riêng.
	 */
	return apply_filters( 'subdirectory_reserved_names', $names );
}

/**
 * Gửi email yêu cầu xác nhận khi có thay đổi địa chỉ email quản trị viên mạng.
 *
 * Địa chỉ email quản trị viên mạng mới sẽ không hoạt động cho đến khi được xác nhận.
 *
 * @since 4.9.0
 *
 * @param string $old_value Địa chỉ email quản trị viên mạng cũ.
 * @param string $value     Địa chỉ email quản trị viên mạng mới được đề xuất.
 */
function update_network_option_new_admin_email( $old_value, $value ) {
	if ( get_site_option( 'admin_email' ) === $value || ! is_email( $value ) ) {
		return;
	}

	$hash            = md5( $value . time() . mt_rand() );
	$new_admin_email = array(
		'hash'     => $hash,
		'newemail' => $value,
	);
	update_site_option( 'network_admin_hash', $new_admin_email );

	$switched_locale = switch_to_user_locale( get_current_user_id() );

	/* translators: Do not translate USERNAME, ADMIN_URL, EMAIL, SITENAME, SITEURL: those are placeholders. */
	$email_text = __(
		'Howdy ###USERNAME###,

You recently requested to have the network admin email address on
your network changed.

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
	 * Lọc văn bản email gửi khi có thay đổi địa chỉ email quản trị viên mạng.
	 *
	 * Các chuỗi sau có ý nghĩa đặc biệt và sẽ được thay thế động:
	 * ###USERNAME###  Tên đăng nhập của người dùng hiện tại.
	 * ###ADMIN_URL### Liên kết nhấp vào để xác nhận thay đổi email.
	 * ###EMAIL###     Địa chỉ email quản trị viên mạng mới được đề xuất.
	 * ###SITENAME###  Tên của mạng.
	 * ###SITEURL###   URL của mạng.
	 *
	 * @since 4.9.0
	 *
	 * @param string $email_text      Văn bản trong email.
	 * @param array  $new_admin_email {
	 *     Dữ liệu liên quan đến địa chỉ email quản trị viên mạng mới.
	 *
	 *     @type string $hash     Hash bảo mật được sử dụng trong URL liên kết xác nhận.
	 *     @type string $newemail Địa chỉ email quản trị viên mạng mới được đề xuất.
	 * }
	 */
	$content = apply_filters( 'new_network_admin_email_content', $email_text, $new_admin_email );

	$current_user = wp_get_current_user();
	$content      = str_replace( '###USERNAME###', $current_user->user_login, $content );
	$content      = str_replace( '###ADMIN_URL###', esc_url( network_admin_url( 'settings.php?network_admin_hash=' . $hash ) ), $content );
	$content      = str_replace( '###EMAIL###', $value, $content );
	$content      = str_replace( '###SITENAME###', wp_specialchars_decode( get_site_option( 'site_name' ), ENT_QUOTES ), $content );
	$content      = str_replace( '###SITEURL###', network_home_url(), $content );

	wp_mail(
		$value,
		sprintf(
			/* translators: Email change notification email subject. %s: Network title. */
			__( '[%s] Network Admin Email Change Request' ),
			wp_specialchars_decode( get_site_option( 'site_name' ), ENT_QUOTES )
		),
		$content
	);

	if ( $switched_locale ) {
		restore_previous_locale();
	}
}

/**
 * Gửi email đến địa chỉ email quản trị viên mạng cũ khi địa chỉ email quản trị viên mạng thay đổi.
 *
 * @since 4.9.0
 *
 * @param string $option_name Tên tùy chọn cơ sở dữ liệu liên quan.
 * @param string $new_email   Địa chỉ email quản trị viên mạng mới.
 * @param string $old_email   Địa chỉ email quản trị viên mạng cũ.
 * @param int    $network_id  ID của mạng.
 */
function wp_network_admin_email_change_notification( $option_name, $new_email, $old_email, $network_id ) {
	$send = true;

	// Không gửi thông báo đến giá trị 'admin_email' mặc định.
	if ( 'you@example.com' === $old_email ) {
		$send = false;
	}

	/**
	 * Lọc xem có gửi email thông báo thay đổi email quản trị viên mạng hay không.
	 *
	 * @since 4.9.0
	 *
	 * @param bool   $send       Có gửi email thông báo hay không.
	 * @param string $old_email  Địa chỉ email quản trị viên mạng cũ.
	 * @param string $new_email  Địa chỉ email quản trị viên mạng mới.
	 * @param int    $network_id ID của mạng.
	 */
	$send = apply_filters( 'send_network_admin_email_change_email', $send, $old_email, $new_email, $network_id );

	if ( ! $send ) {
		return;
	}

	/* translators: Do not translate OLD_EMAIL, NEW_EMAIL, SITENAME, SITEURL: those are placeholders. */
	$email_change_text = __(
		'Hi,

This notice confirms that the network admin email address was changed on ###SITENAME###.

The new network admin email address is ###NEW_EMAIL###.

This email has been sent to ###OLD_EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
	);

	$email_change_email = array(
		'to'      => $old_email,
		/* translators: Network admin email change notification email subject. %s: Network title. */
		'subject' => __( '[%s] Network Admin Email Changed' ),
		'message' => $email_change_text,
		'headers' => '',
	);
	// Lấy tên mạng.
	$network_name = wp_specialchars_decode( get_site_option( 'site_name' ), ENT_QUOTES );

	/**
	 * Lọc nội dung email thông báo gửi khi địa chỉ email quản trị viên mạng thay đổi.
	 *
	 * @since 4.9.0
	 *
	 * @param array $email_change_email {
	 *     Dùng để xây dựng wp_mail().
	 *
	 *     @type string $to      Người nhận dự kiến.
	 *     @type string $subject Tiêu đề của email.
	 *     @type string $message Nội dung của email.
	 *         Các chuỗi sau có ý nghĩa đặc biệt và sẽ được thay thế động:
	 *         - ###OLD_EMAIL### Địa chỉ email quản trị viên mạng cũ.
	 *         - ###NEW_EMAIL### Địa chỉ email quản trị viên mạng mới.
	 *         - ###SITENAME###  Tên của mạng.
	 *         - ###SITEURL###   URL của site.
	 *     @type string $headers Tiêu đề email.
	 * }
	 * @param string $old_email  Địa chỉ email quản trị viên mạng cũ.
	 * @param string $new_email  Địa chỉ email quản trị viên mạng mới.
	 * @param int    $network_id ID của mạng.
	 */
	$email_change_email = apply_filters( 'network_admin_email_change_email', $email_change_email, $old_email, $new_email, $network_id );

	$email_change_email['message'] = str_replace( '###OLD_EMAIL###', $old_email, $email_change_email['message'] );
	$email_change_email['message'] = str_replace( '###NEW_EMAIL###', $new_email, $email_change_email['message'] );
	$email_change_email['message'] = str_replace( '###SITENAME###', $network_name, $email_change_email['message'] );
	$email_change_email['message'] = str_replace( '###SITEURL###', home_url(), $email_change_email['message'] );

	wp_mail(
		$email_change_email['to'],
		sprintf(
			$email_change_email['subject'],
			$network_name
		),
		$email_change_email['message'],
		$email_change_email['headers']
	);
}
