<?php
/**
 * Trang đăng ký WordPress.
 *
 * Xử lý quá trình đăng ký người dùng và tạo site cho các cài đặt multisite.
 *
 * @package WordPress
 */

/** Thiết lập môi trường WordPress. */
require __DIR__ . '/wp-load.php';

add_filter( 'wp_robots', 'wp_robots_no_robots' );

require __DIR__ . '/wp-blog-header.php';

nocache_headers();

if ( is_array( get_site_option( 'illegal_names' ) ) && isset( $_GET['new'] ) && in_array( $_GET['new'], get_site_option( 'illegal_names' ), true ) ) {
	wp_redirect( network_home_url() );
	die();
}

/**
 * In signup_header qua wp_head.
 *
 * @since MU (3.0.0)
 */
function do_signup_header() {
	/**
	 * Kích hoạt trong phần head của màn hình đăng ký site.
	 *
	 * @since 3.0.0
	 */
	do_action( 'signup_header' );
}
add_action( 'wp_head', 'do_signup_header' );

if ( ! is_multisite() ) {
	wp_redirect( wp_registration_url() );
	die();
}

if ( ! is_main_site() ) {
	wp_redirect( network_site_url( 'wp-signup.php' ) );
	die();
}

// Sửa cho tiêu đề trang.
$wp_query->is_404 = false;

/**
 * Kích hoạt trước khi trang Đăng ký Site được tải.
 *
 * @since 4.4.0
 */
do_action( 'before_signup_header' );

/**
 * In các style cho các trang đăng ký Multisite ở front-end.
 *
 * @since MU (3.0.0)
 */
function wpmu_signup_stylesheet() {
	?>
	<style type="text/css">
		.mu_register { width: 90%; margin: 0 auto; }
		.mu_register form { margin-top: 2em; }
		.mu_register fieldset,
			.mu_register legend { margin: 0; padding: 0; border: none; }
		.mu_register .error { padding: 10px; color: #333; background: #ffebe8; border: 1px solid #c00; }
		.mu_register input[type="submit"],
			.mu_register #blog_title,
			.mu_register #user_email,
			.mu_register #blogname,
			.mu_register #user_name { width: 100%; font-size: 24px; margin: 5px 0; box-sizing: border-box; }
		.mu_register #site-language { display: block; }
		.mu_register .prefix_address,
			.mu_register .suffix_address { font-size: 18px; display: inline-block; direction: ltr; }
		.mu_register label,
			.mu_register legend,
			.mu_register .label-heading { font-weight: 600; font-size: 15px; display: block; margin: 10px 0; }
		.mu_register legend + p,
			.mu_register input + p { margin-top: 0; }
		.mu_register label.checkbox { display: inline; }
		.mu_register .mu_alert { font-weight: 600; padding: 10px; color: #333; background: #ffffe0; border: 1px solid #e6db55; }
		.mu_register .mu_alert a { color: inherit; text-decoration: underline; }
		.mu_register .signup-options .wp-signup-radio-button { display: block; }
		.mu_register .privacy-intro .wp-signup-radio-button { margin-right: 0.5em; }
		.rtl .mu_register .wp-signup-blogname { direction: ltr; text-align: right; }
	</style>
	<?php
}
add_action( 'wp_head', 'wpmu_signup_stylesheet' );

get_header( 'wp-signup' );

/**
 * Kích hoạt trước biểu mẫu đăng ký site.
 *
 * @since 3.0.0
 */
do_action( 'before_signup_form' );
?>
<div id="signup-content" class="widecolumn">
<div class="mu_register wp-signup-container" role="main">
<?php
/**
 * Tạo và hiển thị các biểu mẫu Đăng ký và Tạo Site.
 *
 * @since MU (3.0.0)
 *
 * @param string          $blogname   Tên site mới.
 * @param string          $blog_title Tiêu đề site mới.
 * @param WP_Error|string $errors     Đối tượng WP_Error chứa các lỗi hiện có. Mặc định là chuỗi rỗng.
 */
function show_blog_form( $blogname = '', $blog_title = '', $errors = '' ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$current_network = get_network();
	// Tên site.
	if ( ! is_subdomain_install() ) {
		echo '<label for="blogname">' . __( 'Site Name (subdirectory only):' ) . '</label>';
	} else {
		echo '<label for="blogname">' . __( 'Site Domain (subdomain only):' ) . '</label>';
	}

	$errmsg_blogname      = $errors->get_error_message( 'blogname' );
	$errmsg_blogname_aria = '';
	if ( $errmsg_blogname ) {
		$errmsg_blogname_aria = 'wp-signup-blogname-error ';
		echo '<p class="error" id="wp-signup-blogname-error">' . $errmsg_blogname . '</p>';
	}

	if ( ! is_subdomain_install() ) {
		echo '<div class="wp-signup-blogname"><span class="prefix_address" id="prefix-address">' . $current_network->domain . $current_network->path . '</span><input name="blogname" type="text" id="blogname" value="' . esc_attr( $blogname ) . '" maxlength="60" autocomplete="off" required="required" aria-describedby="' . $errmsg_blogname_aria . 'prefix-address" /></div>';
	} else {
		$site_domain = preg_replace( '|^www\.|', '', $current_network->domain );
		echo '<div class="wp-signup-blogname"><input name="blogname" type="text" id="blogname" value="' . esc_attr( $blogname ) . '" maxlength="60" autocomplete="off" required="required" aria-describedby="' . $errmsg_blogname_aria . 'suffix-address" /><span class="suffix_address" id="suffix-address">.' . esc_html( $site_domain ) . '</span></div>';
	}

	if ( ! is_user_logged_in() ) {
		if ( ! is_subdomain_install() ) {
			$site = $current_network->domain . $current_network->path . __( 'sitename' );
		} else {
			$site = __( 'domain' ) . '.' . $site_domain . $current_network->path;
		}

		printf(
			'<p>(<strong>%s</strong>) %s</p>',
			/* translators: %s: Site address. */
			sprintf( __( 'Your address will be %s.' ), $site ),
			__( 'Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!' )
		);
	}

	// Tiêu đề site.
	?>
	<label for="blog_title"><?php _e( 'Site Title:' ); ?></label>
	<?php
	$errmsg_blog_title      = $errors->get_error_message( 'blog_title' );
	$errmsg_blog_title_aria = '';
	if ( $errmsg_blog_title ) {
		$errmsg_blog_title_aria = ' aria-describedby="wp-signup-blog-title-error"';
		echo '<p class="error" id="wp-signup-blog-title-error">' . $errmsg_blog_title . '</p>';
	}
	echo '<input name="blog_title" type="text" id="blog_title" value="' . esc_attr( $blog_title ) . '" required="required" autocomplete="off"' . $errmsg_blog_title_aria . ' />';
	?>

	<?php
	// Ngôn ngữ site.
	$languages = signup_get_available_languages();

	if ( ! empty( $languages ) ) :
		?>
		<p>
			<label for="site-language"><?php _e( 'Site Language:' ); ?></label>
			<?php
			// Mặc định của mạng.
			$lang = get_site_option( 'WPLANG' );

			if ( isset( $_POST['WPLANG'] ) ) {
				$lang = $_POST['WPLANG'];
			}

			// Sử dụng tiếng Anh Mỹ nếu ngôn ngữ mặc định không khả dụng.
			if ( ! in_array( $lang, $languages, true ) ) {
				$lang = '';
			}

			wp_dropdown_languages(
				array(
					'name'                        => 'WPLANG',
					'id'                          => 'site-language',
					'selected'                    => $lang,
					'languages'                   => $languages,
					'show_available_translations' => false,
				)
			);
			?>
		</p>
		<?php
		endif; // Ngôn ngữ.

		$blog_public_on_checked  = '';
		$blog_public_off_checked = '';
	if ( isset( $_POST['blog_public'] ) && '0' === $_POST['blog_public'] ) {
		$blog_public_off_checked = 'checked="checked"';
	} else {
		$blog_public_on_checked = 'checked="checked"';
	}
	?>

	<div id="privacy">
		<fieldset class="privacy-intro">
			<legend>
				<span class="label-heading"><?php _e( 'Privacy:' ); ?></span>
				<?php _e( 'Allow search engines to index this site.' ); ?>
			</legend>
			<p class="wp-signup-radio-buttons">
				<span class="wp-signup-radio-button">
					<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php echo $blog_public_on_checked; ?> />
					<label class="checkbox" for="blog_public_on"><?php _e( 'Yes' ); ?></label>
				</span>
				<span class="wp-signup-radio-button">
					<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php echo $blog_public_off_checked; ?> />
					<label class="checkbox" for="blog_public_off"><?php _e( 'No' ); ?></label>
				</span>
			</p>
		</fieldset>
	</div>

	<?php
	/**
	 * Kích hoạt sau biểu mẫu đăng ký site.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Error $errors Đối tượng WP_Error có thể chứa lỗi 'blogname' hoặc 'blog_title'.
	 */
	do_action( 'signup_blogform', $errors );
}

/**
 * Xác thực đăng ký site mới.
 *
 * @since MU (3.0.0)
 *
 * @return array Chứa dữ liệu site mới và các thông báo lỗi.
 *               Xem wpmu_validate_blog_signup() để biết chi tiết.
 */
function validate_blog_form() {
	$user = '';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
	}

	return wpmu_validate_blog_signup( $_POST['blogname'], $_POST['blog_title'], $user );
}

/**
 * Hiển thị các trường cho biểu mẫu đăng ký tài khoản người dùng mới.
 *
 * @since MU (3.0.0)
 *
 * @param string          $user_name  Tên người dùng đã nhập.
 * @param string          $user_email Địa chỉ email đã nhập.
 * @param WP_Error|string $errors     Đối tượng WP_Error chứa các lỗi hiện có. Mặc định là chuỗi rỗng.
 */
function show_user_form( $user_name = '', $user_email = '', $errors = '' ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	// Tên người dùng.
	echo '<label for="user_name">' . __( 'Username:' ) . '</label>';
	$errmsg_username      = $errors->get_error_message( 'user_name' );
	$errmsg_username_aria = '';
	if ( $errmsg_username ) {
		$errmsg_username_aria = 'wp-signup-username-error ';
		echo '<p class="error" id="wp-signup-username-error">' . $errmsg_username . '</p>';
	}
	?>
	<input name="user_name" type="text" id="user_name" value="<?php echo esc_attr( $user_name ); ?>" autocapitalize="none" autocorrect="off" maxlength="60" autocomplete="username" required="required" aria-describedby="<?php echo $errmsg_username_aria; ?>wp-signup-username-description" />
	<p id="wp-signup-username-description"><?php _e( '(Must be at least 4 characters, lowercase letters and numbers only.)' ); ?></p>

	<?php
	// Địa chỉ email.
	echo '<label for="user_email">' . __( 'Email&nbsp;Address:' ) . '</label>';
	$errmsg_email      = $errors->get_error_message( 'user_email' );
	$errmsg_email_aria = '';
	if ( $errmsg_email ) {
		$errmsg_email_aria = 'wp-signup-email-error ';
		echo '<p class="error" id="wp-signup-email-error">' . $errmsg_email . '</p>';
	}
	?>
	<input name="user_email" type="email" id="user_email" value="<?php echo esc_attr( $user_email ); ?>" maxlength="200" autocomplete="email" required="required" aria-describedby="<?php echo $errmsg_email_aria; ?>wp-signup-email-description" />
	<p id="wp-signup-email-description"><?php _e( 'Your registration email is sent to this address. (Double-check your email address before continuing.)' ); ?></p>

	<?php
	// Các trường bổ sung.
	$errmsg_generic = $errors->get_error_message( 'generic' );
	if ( $errmsg_generic ) {
		echo '<p class="error" id="wp-signup-generic-error">' . $errmsg_generic . '</p>';
	}
	/**
	 * Kích hoạt ở cuối biểu mẫu đăng ký tài khoản người dùng mới.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Error $errors Đối tượng WP_Error chứa lỗi 'user_name' hoặc 'user_email'.
	 */
	do_action( 'signup_extra_fields', $errors );
}

/**
 * Xác thực tên đăng ký và email của người dùng.
 *
 * @since MU (3.0.0)
 *
 * @return array Chứa tên người dùng, email và các thông báo lỗi.
 *               Xem wpmu_validate_user_signup() để biết chi tiết.
 */
function validate_user_form() {
	return wpmu_validate_user_signup( $_POST['user_name'], $_POST['user_email'] );
}

/**
 * Hiển thị biểu mẫu cho người dùng quay lại để đăng ký thêm site khác.
 *
 * @since MU (3.0.0)
 *
 * @param string          $blogname   Tên site mới.
 * @param string          $blog_title Tiêu đề site mới.
 * @param WP_Error|string $errors     Đối tượng WP_Error chứa các lỗi hiện có. Mặc định là chuỗi rỗng.
 */
function signup_another_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	$current_user = wp_get_current_user();

	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$signup_defaults = array(
		'blogname'   => $blogname,
		'blog_title' => $blog_title,
		'errors'     => $errors,
	);

	/**
	 * Lọc các biến mặc định của đăng ký site.
	 *
	 * @since 3.0.0
	 *
	 * @param array $signup_defaults {
	 *     Mảng các biến mặc định của đăng ký site.
	 *
	 *     @type string   $blogname   Tên blog của site.
	 *     @type string   $blog_title Tiêu đề site.
	 *     @type WP_Error $errors     Đối tượng WP_Error có thể chứa lỗi 'blogname' hoặc 'blog_title'.
	 * }
	 */
	$filtered_results = apply_filters( 'signup_another_blog_init', $signup_defaults );

	$blogname   = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors     = $filtered_results['errors'];

	/* translators: %s: Network title. */
	echo '<h2>' . sprintf( __( 'Get <em>another</em> %s site in seconds' ), get_network()->site_name ) . '</h2>';

	if ( $errors->has_errors() ) {
		echo '<p>' . __( 'There was a problem, please correct the form below and try again.' ) . '</p>';
	}
	?>
	<p>
		<?php
		printf(
			/* translators: %s: Current user's display name. */
			__( 'Welcome back, %s. By filling out the form below, you can <strong>add another site to your account</strong>. There is no limit to the number of sites you can have, so create to your heart&#8217;s content, but write responsibly!' ),
			$current_user->display_name
		);
		?>
	</p>

	<?php
	$blogs = get_blogs_of_user( $current_user->ID );
	if ( ! empty( $blogs ) ) {
		?>

			<p><?php _e( 'Sites you are already a member of:' ); ?></p>
			<ul>
				<?php
				foreach ( $blogs as $blog ) {
					$home_url = get_home_url( $blog->userblog_id );
					echo '<li><a href="' . esc_url( $home_url ) . '">' . $home_url . '</a></li>';
				}
				?>
			</ul>
	<?php } ?>

	<p><?php _e( 'If you are not going to use a great site domain, leave it for a new user. Now have at it!' ); ?></p>
	<form id="setupform" method="post" action="wp-signup.php">
		<input type="hidden" name="stage" value="gimmeanotherblog" />
		<?php
		/**
		 * Kích hoạt khi xuất các trường ẩn của biểu mẫu đăng ký khi tạo site hoặc người dùng khác.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string $context Chuỗi mô tả các bước của quá trình đăng ký. Giá trị có thể là
		 *                        'create-another-site', 'validate-user', hoặc 'validate-site'.
		 */
		do_action( 'signup_hidden_fields', 'create-another-site' );
		?>
		<?php show_blog_form( $blogname, $blog_title, $errors ); ?>
		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Create Site' ); ?>" /></p>
	</form>
	<?php
}

/**
 * Xác thực đăng ký site mới cho người dùng hiện có.
 *
 * @since MU (3.0.0)
 *
 * @global string   $blogname   Tên thư mục con hoặc subdomain của site mới.
 * @global string   $blog_title Tiêu đề của site mới.
 * @global WP_Error $errors     Các lỗi hiện có trong phạm vi toàn cục.
 * @global string   $domain     Tên miền của site mới.
 * @global string   $path       Đường dẫn của site mới.
 *
 * @return null|bool True nếu đăng ký site được xác thực, false nếu có lỗi.
 *                   Hàm dừng toàn bộ thực thi nếu người dùng chưa đăng nhập.
 */
function validate_another_blog_signup() {
	global $blogname, $blog_title, $errors, $domain, $path;
	$current_user = wp_get_current_user();
	if ( ! is_user_logged_in() ) {
		die();
	}

	$result = validate_blog_form();

	// Các giá trị được trích xuất sẽ thiết lập/ghi đè các biến toàn cục.
	$domain     = $result['domain'];
	$path       = $result['path'];
	$blogname   = $result['blogname'];
	$blog_title = $result['blog_title'];
	$errors     = $result['errors'];

	if ( $errors->has_errors() ) {
		signup_another_blog( $blogname, $blog_title, $errors );
		return false;
	}

	$public = (int) $_POST['blog_public'];

	$blog_meta_defaults = array(
		'lang_id' => 1,
		'public'  => $public,
	);

	// Xử lý cài đặt ngôn ngữ cho site mới.
	if ( ! empty( $_POST['WPLANG'] ) ) {

		$languages = signup_get_available_languages();

		if ( in_array( $_POST['WPLANG'], $languages, true ) ) {
			$language = wp_unslash( sanitize_text_field( $_POST['WPLANG'] ) );

			if ( $language ) {
				$blog_meta_defaults['WPLANG'] = $language;
			}
		}
	}

	/**
	 * Lọc các biến meta của site mới.
	 *
	 * Sử dụng bộ lọc {@see 'add_signup_meta'} thay thế.
	 *
	 * @since MU (3.0.0)
	 * @deprecated 3.0.0 Sử dụng bộ lọc {@see 'add_signup_meta'} thay thế.
	 *
	 * @param array $blog_meta_defaults Mảng các biến meta mặc định của blog.
	 */
	$meta_defaults = apply_filters_deprecated( 'signup_create_blog_meta', array( $blog_meta_defaults ), '3.0.0', 'add_signup_meta' );

	/**
	 * Lọc các biến meta mặc định của site mới.
	 *
	 * @since 3.0.0
	 *
	 * @param array $meta {
	 *     Mảng các biến meta mặc định của site.
	 *
	 *     @type int $lang_id     ID ngôn ngữ.
	 *     @type int $blog_public Có nên ngăn công cụ tìm kiếm lập chỉ mục site hay không. 1 là có, 0 là không.
	 * }
	 */
	$meta = apply_filters( 'add_signup_meta', $meta_defaults );

	$blog_id = wpmu_create_blog( $domain, $path, $blog_title, $current_user->ID, $meta, get_current_network_id() );

	if ( is_wp_error( $blog_id ) ) {
		return false;
	}

	confirm_another_blog_signup( $domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta, $blog_id );
	return true;
}

/**
 * Hiển thị thông báo xác nhận rằng site mới đã được tạo.
 *
 * @since MU (3.0.0)
 * @since 4.4.0 Thêm tham số `$blog_id`.
 *
 * @param string $domain     URL tên miền.
 * @param string $path       Đường dẫn gốc của site.
 * @param string $blog_title Tiêu đề site.
 * @param string $user_name  Tên người dùng.
 * @param string $user_email Địa chỉ email của người dùng.
 * @param array  $meta       Bất kỳ meta bổ sung nào từ bộ lọc {@see 'add_signup_meta'} trong validate_blog_signup().
 * @param int    $blog_id    ID của site.
 */
function confirm_another_blog_signup( $domain, $path, $blog_title, $user_name, $user_email = '', $meta = array(), $blog_id = 0 ) {

	if ( $blog_id ) {
		switch_to_blog( $blog_id );
		$home_url  = home_url( '/' );
		$login_url = wp_login_url();
		restore_current_blog();
	} else {
		$home_url  = 'http://' . $domain . $path;
		$login_url = 'http://' . $domain . $path . 'wp-login.php';
	}

	$site = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( $home_url ),
		$blog_title
	);

	?>
	<h2>
	<?php
		/* translators: %s: Site title. */
		printf( __( 'The site %s is yours.' ), $site );
	?>
	</h2>
	<p>
		<?php
		printf(
			/* translators: 1: Link to new site, 2: Login URL, 3: Username. */
			__( '%1$s is your new site. <a href="%2$s">Log in</a> as &#8220;%3$s&#8221; using your existing password.' ),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( $home_url ),
				untrailingslashit( $domain . $path )
			),
			esc_url( $login_url ),
			$user_name
		);
		?>
	</p>
	<?php
	/**
	 * Kích hoạt khi quá trình đăng ký site hoặc người dùng hoàn tất.
	 *
	 * @since 3.0.0
	 */
	do_action( 'signup_finished' );
}

/**
 * Hiển thị biểu mẫu cho khách truy cập đăng ký tài khoản người dùng mới.
 *
 * @since MU (3.0.0)
 *
 * @global string $active_signup Chuỗi trả về loại đăng ký. Giá trị có thể là
 *                               'all', 'none', 'blog', hoặc 'user'.
 *
 * @param string          $user_name  Tên người dùng.
 * @param string          $user_email Email của người dùng.
 * @param WP_Error|string $errors     Đối tượng WP_Error chứa các lỗi hiện có. Mặc định là chuỗi rỗng.
 */
function signup_user( $user_name = '', $user_email = '', $errors = '' ) {
	global $active_signup;

	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$signup_for = isset( $_POST['signup_for'] ) ? esc_html( $_POST['signup_for'] ) : 'blog';

	$signup_user_defaults = array(
		'user_name'  => $user_name,
		'user_email' => $user_email,
		'errors'     => $errors,
	);

	/**
	 * Lọc các biến người dùng mặc định được sử dụng trên biểu mẫu đăng ký người dùng.
	 *
	 * @since 3.0.0
	 *
	 * @param array $signup_user_defaults {
	 *     Mảng các biến người dùng mặc định.
	 *
	 *     @type string   $user_name  Tên người dùng.
	 *     @type string   $user_email Địa chỉ email người dùng.
	 *     @type WP_Error $errors     Đối tượng WP_Error với các lỗi có thể liên quan đến người dùng đăng ký.
	 * }
	 */
	$filtered_results = apply_filters( 'signup_user_init', $signup_user_defaults );
	$user_name        = $filtered_results['user_name'];
	$user_email       = $filtered_results['user_email'];
	$errors           = $filtered_results['errors'];

	?>

	<h2>
	<?php
		/* translators: %s: Name of the network. */
		printf( __( 'Get your own %s account in seconds' ), get_network()->site_name );
	?>
	</h2>
	<form id="setupform" method="post" action="wp-signup.php" novalidate="novalidate">
		<input type="hidden" name="stage" value="validate-user-signup" />
		<?php
		/** Hành động này được ghi nhận trong wp-signup.php */
		do_action( 'signup_hidden_fields', 'validate-user' );
		?>
		<?php show_user_form( $user_name, $user_email, $errors ); ?>

		<?php if ( 'blog' === $active_signup ) : ?>
			<input id="signupblog" type="hidden" name="signup_for" value="blog" />
		<?php elseif ( 'user' === $active_signup ) : ?>
			<input id="signupblog" type="hidden" name="signup_for" value="user" />
		<?php else : ?>
			<fieldset class="signup-options">
				<legend><?php _e( 'Create a site or only a username:' ); ?></legend>
				<p class="wp-signup-radio-buttons">
					<span class="wp-signup-radio-button">
						<input id="signupblog" type="radio" name="signup_for" value="blog" <?php checked( $signup_for, 'blog' ); ?> />
						<label class="checkbox" for="signupblog"><?php _e( 'Gimme a site!' ); ?></label>
					</span>
					<span class="wp-signup-radio-button">
						<input id="signupuser" type="radio" name="signup_for" value="user" <?php checked( $signup_for, 'user' ); ?> />
						<label class="checkbox" for="signupuser"><?php _e( 'Just a username, please.' ); ?></label>
					</span>
				</p>
			</fieldset>
		<?php endif; ?>

		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Next' ); ?>" /></p>
	</form>
	<?php
}

/**
 * Xác thực đăng ký người dùng mới.
 *
 * @since MU (3.0.0)
 *
 * @return bool True nếu đăng ký người dùng mới được xác thực, false nếu có lỗi.
 */
function validate_user_signup() {
	$result     = validate_user_form();
	$user_name  = $result['user_name'];
	$user_email = $result['user_email'];
	$errors     = $result['errors'];

	if ( $errors->has_errors() ) {
		signup_user( $user_name, $user_email, $errors );
		return false;
	}

	if ( 'blog' === $_POST['signup_for'] ) {
		signup_blog( $user_name, $user_email );
		return false;
	}

	/** Bộ lọc này được ghi nhận trong wp-signup.php */
	wpmu_signup_user( $user_name, $user_email, apply_filters( 'add_signup_meta', array() ) );

	confirm_user_signup( $user_name, $user_email );
	return true;
}

/**
 * Hiển thị thông báo xác nhận rằng người dùng mới đã được đăng ký và đang chờ kích hoạt.
 *
 * @since MU (3.0.0)
 *
 * @param string $user_name  Tên người dùng.
 * @param string $user_email Địa chỉ email của người dùng.
 */
function confirm_user_signup( $user_name, $user_email ) {
	?>
	<h2>
	<?php
	/* translators: %s: Username. */
	printf( __( '%s is your new username' ), $user_name )
	?>
	</h2>
	<p><?php _e( 'But, before you can start using your new username, <strong>you must activate it</strong>.' ); ?></p>
	<p>
	<?php
	/* translators: %s: The user email address. */
	printf( __( 'Check your inbox at %s and click on the given link.' ), '<strong>' . $user_email . '</strong>' );
	?>
	</p>
	<p><?php _e( 'If you do not activate your username within two days, you will have to sign up again.' ); ?></p>
	<?php
	/** Hành động này được ghi nhận trong wp-signup.php */
	do_action( 'signup_finished' );
}

/**
 * Hiển thị biểu mẫu cho người dùng hoặc khách truy cập đăng ký site mới.
 *
 * @since MU (3.0.0)
 *
 * @param string          $user_name  Tên người dùng.
 * @param string          $user_email Địa chỉ email của người dùng.
 * @param string          $blogname   Tên site.
 * @param string          $blog_title Tiêu đề site.
 * @param WP_Error|string $errors     Đối tượng WP_Error chứa các lỗi hiện có. Mặc định là chuỗi rỗng.
 */
function signup_blog( $user_name = '', $user_email = '', $blogname = '', $blog_title = '', $errors = '' ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$signup_blog_defaults = array(
		'user_name'  => $user_name,
		'user_email' => $user_email,
		'blogname'   => $blogname,
		'blog_title' => $blog_title,
		'errors'     => $errors,
	);

	/**
	 * Lọc các biến mặc định tạo site cho biểu mẫu đăng ký site.
	 *
	 * @since 3.0.0
	 *
	 * @param array $signup_blog_defaults {
	 *     Mảng các biến mặc định tạo site.
	 *
	 *     @type string   $user_name  Tên người dùng.
	 *     @type string   $user_email Địa chỉ email người dùng.
	 *     @type string   $blogname   Tên blog.
	 *     @type string   $blog_title Tiêu đề của site.
	 *     @type WP_Error $errors     Đối tượng WP_Error với các lỗi có thể liên quan đến các biến tạo site mới.
	 * }
	 */
	$filtered_results = apply_filters( 'signup_blog_init', $signup_blog_defaults );

	$user_name  = $filtered_results['user_name'];
	$user_email = $filtered_results['user_email'];
	$blogname   = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors     = $filtered_results['errors'];

	if ( empty( $blogname ) ) {
		$blogname = $user_name;
	}
	?>
	<form id="setupform" method="post" action="wp-signup.php">
		<input type="hidden" name="stage" value="validate-blog-signup" />
		<input type="hidden" name="user_name" value="<?php echo esc_attr( $user_name ); ?>" />
		<input type="hidden" name="user_email" value="<?php echo esc_attr( $user_email ); ?>" />
		<?php
		/** Hành động này được ghi nhận trong wp-signup.php */
		do_action( 'signup_hidden_fields', 'validate-site' );
		?>
		<?php show_blog_form( $blogname, $blog_title, $errors ); ?>
		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Sign up' ); ?>" /></p>
	</form>
	<?php
}

/**
 * Xác thực đăng ký site mới.
 *
 * @since MU (3.0.0)
 *
 * @return bool True nếu đăng ký site được xác thực, false nếu có lỗi.
 */
function validate_blog_signup() {
	// Xác thực lại thông tin người dùng.
	$user_result = wpmu_validate_user_signup( $_POST['user_name'], $_POST['user_email'] );
	$user_name   = $user_result['user_name'];
	$user_email  = $user_result['user_email'];
	$user_errors = $user_result['errors'];

	if ( $user_errors->has_errors() ) {
		signup_user( $user_name, $user_email, $user_errors );
		return false;
	}

	$result     = wpmu_validate_blog_signup( $_POST['blogname'], $_POST['blog_title'] );
	$domain     = $result['domain'];
	$path       = $result['path'];
	$blogname   = $result['blogname'];
	$blog_title = $result['blog_title'];
	$errors     = $result['errors'];

	if ( $errors->has_errors() ) {
		signup_blog( $user_name, $user_email, $blogname, $blog_title, $errors );
		return false;
	}

	$public      = (int) $_POST['blog_public'];
	$signup_meta = array(
		'lang_id' => 1,
		'public'  => $public,
	);

	// Xử lý cài đặt ngôn ngữ cho site mới.
	if ( ! empty( $_POST['WPLANG'] ) ) {

		$languages = signup_get_available_languages();

		if ( in_array( $_POST['WPLANG'], $languages, true ) ) {
			$language = wp_unslash( sanitize_text_field( $_POST['WPLANG'] ) );

			if ( $language ) {
				$signup_meta['WPLANG'] = $language;
			}
		}
	}

	/** Bộ lọc này được ghi nhận trong wp-signup.php */
	$meta = apply_filters( 'add_signup_meta', $signup_meta );

	wpmu_signup_blog( $domain, $path, $blog_title, $user_name, $user_email, $meta );
	confirm_blog_signup( $domain, $path, $blog_title, $user_name, $user_email, $meta );
	return true;
}

/**
 * Hiển thị thông báo xác nhận rằng site mới đã được đăng ký và đang chờ kích hoạt.
 *
 * @since MU (3.0.0)
 *
 * @param string $domain     Tên miền hoặc subdomain của site.
 * @param string $path       Đường dẫn của site.
 * @param string $blog_title Tiêu đề của site mới.
 * @param string $user_name  Tên người dùng.
 * @param string $user_email Địa chỉ email của người dùng.
 * @param array  $meta       Bất kỳ meta bổ sung nào từ bộ lọc {@see 'add_signup_meta'} trong validate_blog_signup().
 */
function confirm_blog_signup( $domain, $path, $blog_title, $user_name = '', $user_email = '', $meta = array() ) {
	?>
	<h2>
	<?php
	/* translators: %s: Site address. */
	printf( __( 'Congratulations! Your new site, %s, is almost ready.' ), "<a href='http://{$domain}{$path}'>{$blog_title}</a>" )
	?>
	</h2>

	<p><?php _e( 'But, before you can start using your site, <strong>you must activate it</strong>.' ); ?></p>
	<p>
	<?php
	/* translators: %s: The user email address. */
	printf( __( 'Check your inbox at %s and click on the given link.' ), '<strong>' . $user_email . '</strong>' );
	?>
	</p>
	<p><?php _e( 'If you do not activate your site within two days, you will have to sign up again.' ); ?></p>
	<h2><?php _e( 'Still waiting for your email?' ); ?></h2>
	<p><?php _e( 'If you have not received your email yet, there are a number of things you can do:' ); ?></p>
	<ul id="noemail-tips">
		<li><p><strong><?php _e( 'Wait a little longer. Sometimes delivery of email can be delayed by processes outside of our control.' ); ?></strong></p></li>
		<li><p><?php _e( 'Check the junk or spam folder of your email client. Sometime emails wind up there by mistake.' ); ?></p></li>
		<li>
		<?php
			/* translators: %s: Email address. */
			printf( __( 'Have you entered your email correctly? You have entered %s, if it&#8217;s incorrect, you will not receive your email.' ), $user_email );
		?>
		</li>
	</ul>
	<?php
	/** Hành động này được ghi nhận trong wp-signup.php */
	do_action( 'signup_finished' );
}

/**
 * Lấy các ngôn ngữ khả dụng trong quá trình đăng ký site/người dùng.
 *
 * @since 4.4.0
 *
 * @see get_available_languages()
 *
 * @return string[] Mảng các mã ngôn ngữ khả dụng. Mã ngôn ngữ được tạo bằng cách
 *                  loại bỏ phần mở rộng .mo khỏi tên file ngôn ngữ.
 */
function signup_get_available_languages() {
	/**
	 * Lọc danh sách các ngôn ngữ khả dụng cho đăng ký site ở front-end.
	 *
	 * Truyền một mảng rỗng vào hook này sẽ vô hiệu hóa hiển thị cài đặt trên
	 * biểu mẫu đăng ký, và ngôn ngữ mặc định sẽ được sử dụng khi tạo site.
	 *
	 * Các ngôn ngữ chưa được cài đặt sẽ bị loại bỏ.
	 *
	 * @since 4.4.0
	 *
	 * @param string[] $languages Mảng các mã ngôn ngữ khả dụng. Mã ngôn ngữ được tạo bằng cách
	 *                            loại bỏ phần mở rộng .mo khỏi tên file ngôn ngữ.
	 */
	$languages = (array) apply_filters( 'signup_get_available_languages', get_available_languages() );

	/*
	 * Loại bỏ các ngôn ngữ chưa được cài đặt và trả về.
	 *
	 * Gọi lại get_available_languages() ở đây trong trường hợp gói ngôn ngữ đã được cài đặt
	 * trong callback được gắn vào bộ lọc 'signup_get_available_languages' trước thời điểm này.
	 */
	return array_intersect_assoc( $languages, get_available_languages() );
}

// Chính.
$active_signup = get_site_option( 'registration', 'none' );

/**
 * Lọc loại đăng ký site.
 *
 * @since 3.0.0
 *
 * @param string $active_signup Chuỗi trả về loại đăng ký. Giá trị có thể là
 *                              'all', 'none', 'blog', hoặc 'user'.
 */
$active_signup = apply_filters( 'wpmu_active_signup', $active_signup );

if ( current_user_can( 'manage_network' ) ) {
	echo '<div class="mu_alert">';
	_e( 'Greetings Network Administrator!' );
	echo ' ';

	switch ( $active_signup ) {
		case 'none':
			_e( 'The network currently disallows registrations.' );
			break;
		case 'blog':
			_e( 'The network currently allows site registrations.' );
			break;
		case 'user':
			_e( 'The network currently allows user registrations.' );
			break;
		default:
			_e( 'The network currently allows both site and user registrations.' );
			break;
	}

	echo ' ';

	/* translators: %s: URL to Network Settings screen. */
	printf( __( 'To change or disable registration go to your <a href="%s">Options page</a>.' ), esc_url( network_admin_url( 'settings.php' ) ) );
	echo '</div>';
}

$newblogname = isset( $_GET['new'] ) ? strtolower( preg_replace( '/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'] ) ) : null;

$current_user = wp_get_current_user();
if ( 'none' === $active_signup ) {
	_e( 'Registration has been disabled.' );
} elseif ( 'blog' === $active_signup && ! is_user_logged_in() ) {
	$login_url = wp_login_url( network_site_url( 'wp-signup.php' ) );
	/* translators: %s: Login URL. */
	printf( __( 'You must first <a href="%s">log in</a>, and then you can create a new site.' ), $login_url );
} else {
	$stage = isset( $_POST['stage'] ) ? $_POST['stage'] : 'default';
	switch ( $stage ) {
		case 'validate-user-signup':
			if ( 'all' === $active_signup
				|| ( 'blog' === $_POST['signup_for'] && 'blog' === $active_signup )
				|| ( 'user' === $_POST['signup_for'] && 'user' === $active_signup )
			) {
				validate_user_signup();
			} else {
				_e( 'User registration has been disabled.' );
			}
			break;
		case 'validate-blog-signup':
			if ( 'all' === $active_signup || 'blog' === $active_signup ) {
				validate_blog_signup();
			} else {
				_e( 'Site registration has been disabled.' );
			}
			break;
		case 'gimmeanotherblog':
			validate_another_blog_signup();
			break;
		case 'default':
		default:
			$user_email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : '';
			/**
			 * Kích hoạt khi biểu mẫu đăng ký site được gửi.
			 *
			 * @since 3.0.0
			 */
			do_action( 'preprocess_signup_form' );
			if ( is_user_logged_in() && ( 'all' === $active_signup || 'blog' === $active_signup ) ) {
				signup_another_blog( $newblogname );
			} elseif ( ! is_user_logged_in() && ( 'all' === $active_signup || 'user' === $active_signup ) ) {
				signup_user( $newblogname, $user_email );
			} elseif ( ! is_user_logged_in() && ( 'blog' === $active_signup ) ) {
				_e( 'Sorry, new registrations are not allowed at this time.' );
			} else {
				_e( 'You are logged in already. No need to register again!' );
			}

			if ( $newblogname ) {
				$newblog = get_blogaddress_by_name( $newblogname );

				if ( 'blog' === $active_signup || 'all' === $active_signup ) {
					printf(
						/* translators: %s: Site address. */
						'<p>' . __( 'The site you were looking for, %s, does not exist, but you can create it now!' ) . '</p>',
						'<strong>' . $newblog . '</strong>'
					);
				} else {
					printf(
						/* translators: %s: Site address. */
						'<p>' . __( 'The site you were looking for, %s, does not exist.' ) . '</p>',
						'<strong>' . $newblog . '</strong>'
					);
				}
			}
			break;
	}
}
?>
</div>
</div>
<?php
/**
 * Kích hoạt sau các biểu mẫu đăng ký, trước wp_footer.
 *
 * @since 3.0.0
 */
do_action( 'after_signup_form' );
?>

<?php
get_footer( 'wp-signup' );
