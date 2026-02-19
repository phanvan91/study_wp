<?php
/**
 * API Dịch thuật Lõi
 *
 * @package WordPress
 * @subpackage i18n
 * @since 1.2.0
 */

/**
 * Lấy ngôn ngữ (locale) hiện tại.
 *
 * Nếu ngôn ngữ đã được thiết lập, nó sẽ lọc ngôn ngữ qua hook bộ lọc {@see 'locale'}
 * và trả về giá trị.
 *
 * Nếu ngôn ngữ chưa được thiết lập, hằng số WPLANG sẽ được sử dụng nếu nó
 * đã được định nghĩa. Sau đó nó được lọc qua hook bộ lọc {@see 'locale'} và
 * giá trị cho biến toàn cục locale được thiết lập và ngôn ngữ được trả về.
 *
 * Quá trình lấy ngôn ngữ chỉ nên thực hiện một lần, nhưng ngôn ngữ sẽ
 * luôn được lọc qua hook {@see 'locale'}.
 *
 * @since 1.5.0
 *
 * @global string $locale           Ngôn ngữ hiện tại.
 * @global string $wp_local_package Mã ngôn ngữ của gói.
 *
 * @return string Ngôn ngữ của blog hoặc từ hook {@see 'locale'}.
 */
function get_locale() {
	global $locale, $wp_local_package;

	if ( isset( $locale ) ) {
		/** This filter is documented in wp-includes/l10n.php */
		return apply_filters( 'locale', $locale );
	}

	if ( isset( $wp_local_package ) ) {
		$locale = $wp_local_package;
	}

	// WPLANG đã được định nghĩa trong wp-config.
	if ( defined( 'WPLANG' ) ) {
		$locale = WPLANG;
	}

	// Nếu là multisite, kiểm tra các tùy chọn.
	if ( is_multisite() ) {
		// Không kiểm tra tùy chọn blog khi đang cài đặt.
		if ( wp_installing() ) {
			$ms_locale = get_site_option( 'WPLANG' );
		} else {
			$ms_locale = get_option( 'WPLANG' );
			if ( false === $ms_locale ) {
				$ms_locale = get_site_option( 'WPLANG' );
			}
		}

		if ( false !== $ms_locale ) {
			$locale = $ms_locale;
		}
	} else {
		$db_locale = get_option( 'WPLANG' );
		if ( false !== $db_locale ) {
			$locale = $db_locale;
		}
	}

	if ( empty( $locale ) ) {
		$locale = 'en_US';
	}

	/**
	 * Lọc ID ngôn ngữ của bản cài đặt WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @param string $locale ID ngôn ngữ.
	 */
	return apply_filters( 'locale', $locale );
}

/**
 * Lấy ngôn ngữ của người dùng.
 *
 * Nếu người dùng có ngôn ngữ được thiết lập thành chuỗi không rỗng thì nó sẽ
 * được trả về. Ngược lại, trả về ngôn ngữ từ get_locale().
 *
 * @since 4.7.0
 *
 * @param int|WP_User $user ID người dùng hoặc đối tượng WP_User. Mặc định là người dùng hiện tại.
 * @return string Ngôn ngữ của người dùng.
 */
function get_user_locale( $user = 0 ) {
	$user_object = false;

	if ( 0 === $user && function_exists( 'wp_get_current_user' ) ) {
		$user_object = wp_get_current_user();
	} elseif ( $user instanceof WP_User ) {
		$user_object = $user;
	} elseif ( $user && is_numeric( $user ) ) {
		$user_object = get_user_by( 'id', $user );
	}

	if ( ! $user_object ) {
		return get_locale();
	}

	$locale = $user_object->locale;

	return $locale ? $locale : get_locale();
}

/**
 * Xác định ngôn ngữ hiện tại mong muốn cho yêu cầu.
 *
 * @since 5.0.0
 *
 * @global string $pagenow          Tên tệp của màn hình hiện tại.
 * @global string $wp_local_package Mã ngôn ngữ của gói.
 *
 * @return string Ngôn ngữ đã được xác định.
 */
function determine_locale() {
	/**
	 * Lọc ngôn ngữ cho yêu cầu hiện tại trước quá trình xác định mặc định.
	 *
	 * Sử dụng bộ lọc này cho phép ghi đè logic mặc định, bỏ qua hoàn toàn hàm.
	 *
	 * @since 5.0.0
	 *
	 * @param string|null $locale Ngôn ngữ để trả về và bỏ qua. Mặc định null.
	 */
	$determined_locale = apply_filters( 'pre_determine_locale', null );

	if ( $determined_locale && is_string( $determined_locale ) ) {
		return $determined_locale;
	}

	if (
		isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] &&
		( ! empty( $_GET['wp_lang'] ) || ! empty( $_COOKIE['wp_lang'] ) )
	) {
		if ( ! empty( $_GET['wp_lang'] ) ) {
			$determined_locale = sanitize_locale_name( $_GET['wp_lang'] );
		} else {
			$determined_locale = sanitize_locale_name( $_COOKIE['wp_lang'] );
		}
	} elseif (
		is_admin() ||
		( isset( $_GET['_locale'] ) && 'user' === $_GET['_locale'] && wp_is_json_request() )
	) {
		$determined_locale = get_user_locale();
	} elseif (
		( ! empty( $_REQUEST['language'] ) || isset( $GLOBALS['wp_local_package'] ) )
		&& wp_installing()
	) {
		if ( ! empty( $_REQUEST['language'] ) ) {
			$determined_locale = sanitize_locale_name( $_REQUEST['language'] );
		} else {
			$determined_locale = $GLOBALS['wp_local_package'];
		}
	}

	if ( ! $determined_locale ) {
		$determined_locale = get_locale();
	}

	/**
	 * Lọc ngôn ngữ cho yêu cầu hiện tại.
	 *
	 * @since 5.0.0
	 *
	 * @param string $determined_locale Ngôn ngữ.
	 */
	return apply_filters( 'determine_locale', $determined_locale );
}

/**
 * Lấy bản dịch của $text.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc sẽ được trả về.
 *
 * *Lưu ý:* Không sử dụng translate() trực tiếp, hãy dùng __() hoặc các hàm liên quan.
 *
 * @since 2.2.0
 * @since 5.5.0 Giới thiệu bộ lọc `gettext-{$domain}`.
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Văn bản đã dịch.
 */
function translate( $text, $domain = 'default' ) {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate( $text );

	/**
	 * Lọc văn bản cùng với bản dịch của nó.
	 *
	 * @since 2.0.11
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $text        Văn bản cần dịch.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( 'gettext', $translation, $text, $domain );

	/**
	 * Lọc văn bản cùng với bản dịch của nó cho một miền.
	 *
	 * Phần động của tên hook, `$domain`, tham chiếu đến miền văn bản.
	 *
	 * @since 5.5.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $text        Văn bản cần dịch.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( "gettext_{$domain}", $translation, $text, $domain );

	return $translation;
}

/**
 * Xóa mục cuối cùng trên chuỗi phân tách bằng dấu gạch đứng.
 *
 * Dùng để xóa mục cuối cùng trong chuỗi, chẳng hạn 'Role name|User role'. Chuỗi
 * gốc sẽ được trả về nếu không tìm thấy ký tự dấu gạch đứng '|' trong chuỗi.
 *
 * @since 2.8.0
 *
 * @param string $text Chuỗi phân tách bằng dấu gạch đứng.
 * @return string $text hoặc mọi thứ trước dấu gạch đứng cuối cùng.
 */
function before_last_bar( $text ) {
	$last_bar = strrpos( $text, '|' );
	if ( false === $last_bar ) {
		return $text;
	} else {
		return substr( $text, 0, $last_bar );
	}
}

/**
 * Lấy bản dịch của $text trong ngữ cảnh được định nghĩa trong $context.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc sẽ được trả về.
 *
 * *Lưu ý:* Không sử dụng translate_with_gettext_context() trực tiếp, hãy dùng _x() hoặc các hàm liên quan.
 *
 * @since 2.8.0
 * @since 5.5.0 Giới thiệu bộ lọc `gettext_with_context-{$domain}`.
 *
 * @param string $text    Văn bản cần dịch.
 * @param string $context Thông tin ngữ cảnh cho người dịch.
 * @param string $domain  Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                        Mặc định 'default'.
 * @return string Văn bản đã dịch nếu thành công, văn bản gốc nếu thất bại.
 */
function translate_with_gettext_context( $text, $context, $domain = 'default' ) {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate( $text, $context );

	/**
	 * Lọc văn bản cùng bản dịch dựa trên thông tin ngữ cảnh.
	 *
	 * @since 2.8.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $text        Văn bản cần dịch.
	 * @param string $context     Thông tin ngữ cảnh cho người dịch.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( 'gettext_with_context', $translation, $text, $context, $domain );

	/**
	 * Lọc văn bản cùng bản dịch dựa trên thông tin ngữ cảnh cho một miền.
	 *
	 * Phần động của tên hook, `$domain`, tham chiếu đến miền văn bản.
	 *
	 * @since 5.5.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $text        Văn bản cần dịch.
	 * @param string $context     Thông tin ngữ cảnh cho người dịch.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( "gettext_with_context_{$domain}", $translation, $text, $context, $domain );

	return $translation;
}

/**
 * Lấy bản dịch của $text.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc sẽ được trả về.
 *
 * @since 2.1.0
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Văn bản đã dịch.
 */
function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}

/**
 * Lấy bản dịch của $text và thoát ký tự để sử dụng an toàn trong thuộc tính.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc sẽ được trả về.
 *
 * @since 2.8.0
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Văn bản đã dịch nếu thành công, văn bản gốc nếu thất bại.
 */
function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( translate( $text, $domain ) );
}

/**
 * Lấy bản dịch của $text và thoát ký tự để sử dụng an toàn trong đầu ra HTML.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc
 * sẽ được thoát ký tự và trả về.
 *
 * @since 2.8.0
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Văn bản đã dịch.
 */
function esc_html__( $text, $domain = 'default' ) {
	return esc_html( translate( $text, $domain ) );
}

/**
 * Hiển thị văn bản đã dịch.
 *
 * @since 1.2.0
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 */
function _e( $text, $domain = 'default' ) {
	echo translate( $text, $domain );
}

/**
 * Hiển thị văn bản đã dịch đã được thoát ký tự để sử dụng an toàn trong thuộc tính.
 *
 * Mã hóa `< > & " '` (nhỏ hơn, lớn hơn, dấu và, ngoặc kép, ngoặc đơn).
 * Sẽ không bao giờ mã hóa kép các thực thể.
 *
 * Nếu bạn cần giá trị để sử dụng trong PHP, hãy dùng esc_attr__().
 *
 * @since 2.8.0
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 */
function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( translate( $text, $domain ) );
}

/**
 * Hiển thị văn bản đã dịch đã được thoát ký tự để sử dụng an toàn trong đầu ra HTML.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc
 * sẽ được thoát ký tự và hiển thị.
 *
 * Nếu bạn cần giá trị để sử dụng trong PHP, hãy dùng esc_html__().
 *
 * @since 2.8.0
 *
 * @param string $text   Văn bản cần dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 */
function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( translate( $text, $domain ) );
}

/**
 * Lấy chuỗi đã dịch với ngữ cảnh gettext.
 *
 * Khá nhiều lần, sẽ có xung đột với văn bản có thể dịch tương tự
 * được tìm thấy ở nhiều hơn hai nơi, nhưng với ngữ cảnh dịch khác nhau.
 *
 * Bằng cách bao gồm ngữ cảnh trong tệp pot, người dịch có thể dịch hai
 * chuỗi khác nhau.
 *
 * @since 2.8.0
 *
 * @param string $text    Văn bản cần dịch.
 * @param string $context Thông tin ngữ cảnh cho người dịch.
 * @param string $domain  Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                        Mặc định 'default'.
 * @return string Chuỗi ngữ cảnh đã dịch không có dấu gạch đứng.
 */
function _x( $text, $context, $domain = 'default' ) {
	return translate_with_gettext_context( $text, $context, $domain );
}

/**
 * Hiển thị chuỗi đã dịch với ngữ cảnh gettext.
 *
 * @since 3.0.0
 *
 * @param string $text    Văn bản cần dịch.
 * @param string $context Thông tin ngữ cảnh cho người dịch.
 * @param string $domain  Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                        Mặc định 'default'.
 */
function _ex( $text, $context, $domain = 'default' ) {
	echo _x( $text, $context, $domain );
}

/**
 * Dịch chuỗi với ngữ cảnh gettext, và thoát ký tự để sử dụng an toàn trong thuộc tính.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc
 * sẽ được thoát ký tự và trả về.
 *
 * @since 2.8.0
 *
 * @param string $text    Văn bản cần dịch.
 * @param string $context Thông tin ngữ cảnh cho người dịch.
 * @param string $domain  Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                        Mặc định 'default'.
 * @return string Văn bản đã dịch.
 */
function esc_attr_x( $text, $context, $domain = 'default' ) {
	return esc_attr( translate_with_gettext_context( $text, $context, $domain ) );
}

/**
 * Dịch chuỗi với ngữ cảnh gettext, và thoát ký tự để sử dụng an toàn trong đầu ra HTML.
 *
 * Nếu không có bản dịch, hoặc miền văn bản chưa được tải, văn bản gốc
 * sẽ được thoát ký tự và trả về.
 *
 * @since 2.9.0
 *
 * @param string $text    Văn bản cần dịch.
 * @param string $context Thông tin ngữ cảnh cho người dịch.
 * @param string $domain  Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                        Mặc định 'default'.
 * @return string Văn bản đã dịch.
 */
function esc_html_x( $text, $context, $domain = 'default' ) {
	return esc_html( translate_with_gettext_context( $text, $context, $domain ) );
}

/**
 * Dịch và lấy dạng số ít hoặc số nhiều dựa trên số được cung cấp.
 *
 * Sử dụng khi bạn muốn dùng dạng phù hợp của chuỗi dựa trên việc
 * số là số ít hay số nhiều.
 *
 * Ví dụ:
 *
 *     printf( _n( '%s person', '%s people', $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.8.0
 * @since 5.5.0 Giới thiệu bộ lọc `ngettext-{$domain}`.
 *
 * @param string $single Văn bản được sử dụng nếu số là số ít.
 * @param string $plural Văn bản được sử dụng nếu số là số nhiều.
 * @param int    $number Số để so sánh nhằm sử dụng dạng số ít hoặc số nhiều.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Dạng số ít hoặc số nhiều đã dịch.
 */
function _n( $single, $plural, $number, $domain = 'default' ) {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate_plural( $single, $plural, $number );

	/**
	 * Lọc dạng số ít hoặc số nhiều của chuỗi.
	 *
	 * @since 2.2.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $single      Văn bản được sử dụng nếu số là số ít.
	 * @param string $plural      Văn bản được sử dụng nếu số là số nhiều.
	 * @param int    $number      Số để so sánh nhằm sử dụng dạng số ít hoặc số nhiều.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( 'ngettext', $translation, $single, $plural, $number, $domain );

	/**
	 * Lọc dạng số ít hoặc số nhiều của chuỗi cho một miền.
	 *
	 * Phần động của tên hook, `$domain`, tham chiếu đến miền văn bản.
	 *
	 * @since 5.5.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $single      Văn bản được sử dụng nếu số là số ít.
	 * @param string $plural      Văn bản được sử dụng nếu số là số nhiều.
	 * @param int    $number      Số để so sánh nhằm sử dụng dạng số ít hoặc số nhiều.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( "ngettext_{$domain}", $translation, $single, $plural, $number, $domain );

	return $translation;
}

/**
 * Dịch và lấy dạng số ít hoặc số nhiều dựa trên số được cung cấp, với ngữ cảnh gettext.
 *
 * Đây là kết hợp của _n() và _x(). Nó hỗ trợ ngữ cảnh và số nhiều.
 *
 * Sử dụng khi bạn muốn dùng dạng phù hợp của chuỗi với ngữ cảnh dựa trên việc
 * số là số ít hay số nhiều.
 *
 * Ví dụ về cụm từ chung được phân biệt qua tham số ngữ cảnh:
 *
 *     printf( _nx( '%s group', '%s groups', $people, 'group of people', 'text-domain' ), number_format_i18n( $people ) );
 *     printf( _nx( '%s group', '%s groups', $animals, 'group of animals', 'text-domain' ), number_format_i18n( $animals ) );
 *
 * @since 2.8.0
 * @since 5.5.0 Giới thiệu bộ lọc `ngettext_with_context-{$domain}`.
 *
 * @param string $single  Văn bản được sử dụng nếu số là số ít.
 * @param string $plural  Văn bản được sử dụng nếu số là số nhiều.
 * @param int    $number  Số để so sánh nhằm sử dụng dạng số ít hoặc số nhiều.
 * @param string $context Thông tin ngữ cảnh cho người dịch.
 * @param string $domain  Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                        Mặc định 'default'.
 * @return string Dạng số ít hoặc số nhiều đã dịch.
 */
function _nx( $single, $plural, $number, $context, $domain = 'default' ) {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate_plural( $single, $plural, $number, $context );

	/**
	 * Lọc dạng số ít hoặc số nhiều của chuỗi với ngữ cảnh gettext.
	 *
	 * @since 2.8.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $single      Văn bản được sử dụng nếu số là số ít.
	 * @param string $plural      Văn bản được sử dụng nếu số là số nhiều.
	 * @param int    $number      Số để so sánh nhằm sử dụng dạng số ít hoặc số nhiều.
	 * @param string $context     Thông tin ngữ cảnh cho người dịch.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( 'ngettext_with_context', $translation, $single, $plural, $number, $context, $domain );

	/**
	 * Lọc dạng số ít hoặc số nhiều của chuỗi với ngữ cảnh gettext cho một miền.
	 *
	 * Phần động của tên hook, `$domain`, tham chiếu đến miền văn bản.
	 *
	 * @since 5.5.0
	 *
	 * @param string $translation Văn bản đã dịch.
	 * @param string $single      Văn bản được sử dụng nếu số là số ít.
	 * @param string $plural      Văn bản được sử dụng nếu số là số nhiều.
	 * @param int    $number      Số để so sánh nhằm sử dụng dạng số ít hoặc số nhiều.
	 * @param string $context     Thông tin ngữ cảnh cho người dịch.
	 * @param string $domain      Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$translation = apply_filters( "ngettext_with_context_{$domain}", $translation, $single, $plural, $number, $context, $domain );

	return $translation;
}

/**
 * Đăng ký các chuỗi số nhiều trong tệp POT, nhưng không dịch chúng.
 *
 * Sử dụng khi bạn muốn giữ các cấu trúc với chuỗi số nhiều có thể dịch
 * và sử dụng chúng sau khi biết số lượng.
 *
 * Ví dụ:
 *
 *     $message = _n_noop( '%s post', '%s posts', 'text-domain' );
 *     ...
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.5.0
 *
 * @param string $singular Dạng số ít cần bản địa hóa.
 * @param string $plural   Dạng số nhiều cần bản địa hóa.
 * @param string $domain   Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                         Mặc định null.
 * @return array {
 *     Mảng thông tin dịch cho các chuỗi.
 *
 *     @type string      $0        Dạng số ít cần bản địa hóa. Không còn sử dụng.
 *     @type string      $1        Dạng số nhiều cần bản địa hóa. Không còn sử dụng.
 *     @type string      $singular Dạng số ít cần bản địa hóa.
 *     @type string      $plural   Dạng số nhiều cần bản địa hóa.
 *     @type null        $context  Thông tin ngữ cảnh cho người dịch.
 *     @type string|null $domain   Miền văn bản.
 * }
 */
function _n_noop( $singular, $plural, $domain = null ) {
	return array(
		0          => $singular,
		1          => $plural,
		'singular' => $singular,
		'plural'   => $plural,
		'context'  => null,
		'domain'   => $domain,
	);
}

/**
 * Đăng ký các chuỗi số nhiều với ngữ cảnh gettext trong tệp POT, nhưng không dịch chúng.
 *
 * Sử dụng khi bạn muốn giữ các cấu trúc với chuỗi số nhiều có thể dịch
 * và sử dụng chúng sau khi biết số lượng.
 *
 * Ví dụ về cụm từ chung được phân biệt qua tham số ngữ cảnh:
 *
 *     $messages = array(
 *          'people'  => _nx_noop( '%s group', '%s groups', 'people', 'text-domain' ),
 *          'animals' => _nx_noop( '%s group', '%s groups', 'animals', 'text-domain' ),
 *     );
 *     ...
 *     $message = $messages[ $type ];
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.8.0
 *
 * @param string $singular Dạng số ít cần bản địa hóa.
 * @param string $plural   Dạng số nhiều cần bản địa hóa.
 * @param string $context  Thông tin ngữ cảnh cho người dịch.
 * @param string $domain   Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                         Mặc định null.
 * @return array {
 *     Mảng thông tin dịch cho các chuỗi.
 *
 *     @type string      $0        Dạng số ít cần bản địa hóa. Không còn sử dụng.
 *     @type string      $1        Dạng số nhiều cần bản địa hóa. Không còn sử dụng.
 *     @type string      $2        Thông tin ngữ cảnh cho người dịch. Không còn sử dụng.
 *     @type string      $singular Dạng số ít cần bản địa hóa.
 *     @type string      $plural   Dạng số nhiều cần bản địa hóa.
 *     @type string      $context  Thông tin ngữ cảnh cho người dịch.
 *     @type string|null $domain   Miền văn bản.
 * }
 */
function _nx_noop( $singular, $plural, $context, $domain = null ) {
	return array(
		0          => $singular,
		1          => $plural,
		2          => $context,
		'singular' => $singular,
		'plural'   => $plural,
		'context'  => $context,
		'domain'   => $domain,
	);
}

/**
 * Dịch và trả về dạng số ít hoặc số nhiều của chuỗi đã được đăng ký
 * với _n_noop() hoặc _nx_noop().
 *
 * Sử dụng khi bạn muốn dùng chuỗi số nhiều có thể dịch khi đã biết số lượng.
 *
 * Ví dụ:
 *
 *     $message = _n_noop( '%s post', '%s posts', 'text-domain' );
 *     ...
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 3.1.0
 *
 * @param array  $nooped_plural {
 *     Mảng thường là giá trị trả về từ _n_noop() hoặc _nx_noop().
 *
 *     @type string      $singular Dạng số ít cần bản địa hóa.
 *     @type string      $plural   Dạng số nhiều cần bản địa hóa.
 *     @type string|null $context  Thông tin ngữ cảnh cho người dịch.
 *     @type string|null $domain   Miền văn bản.
 * }
 * @param int    $count         Số lượng đối tượng.
 * @param string $domain        Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch. Nếu $nooped_plural chứa
 *                              miền văn bản được truyền cho _n_noop() hoặc _nx_noop(), nó sẽ ghi đè giá trị này. Mặc định 'default'.
 * @return string Văn bản đã dịch dạng $singular hoặc $plural.
 */
function translate_nooped_plural( $nooped_plural, $count, $domain = 'default' ) {
	if ( $nooped_plural['domain'] ) {
		$domain = $nooped_plural['domain'];
	}

	if ( $nooped_plural['context'] ) {
		return _nx( $nooped_plural['singular'], $nooped_plural['plural'], $count, $nooped_plural['context'], $domain );
	} else {
		return _n( $nooped_plural['singular'], $nooped_plural['plural'], $count, $domain );
	}
}

/**
 * Tải tệp .mo vào miền văn bản $domain.
 *
 * Nếu miền văn bản đã tồn tại, các bản dịch sẽ được gộp lại. Nếu cả hai
 * bộ có cùng chuỗi, bản dịch từ giá trị gốc sẽ được lấy.
 *
 * Khi thành công, tệp .mo sẽ được đặt trong biến toàn cục $l10n theo $domain
 * và sẽ là đối tượng MO.
 *
 * @since 1.5.0
 * @since 6.1.0 Thêm tham số `$locale`.
 *
 * @global MO[]                   $l10n                   Mảng tất cả các miền văn bản đã tải hiện tại.
 * @global MO[]                   $l10n_unloaded          Mảng tất cả các miền văn bản đã được gỡ tải.
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 *
 * @param string $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @param string $mofile Đường dẫn đến tệp .mo.
 * @param string $locale Tùy chọn. Ngôn ngữ. Mặc định là ngôn ngữ hiện tại.
 * @return bool True nếu thành công, false nếu thất bại.
 */
function load_textdomain( $domain, $mofile, $locale = null ) {
	/** @var WP_Textdomain_Registry $wp_textdomain_registry */
	global $l10n, $l10n_unloaded, $wp_textdomain_registry;

	$l10n_unloaded = (array) $l10n_unloaded;

	if ( ! is_string( $domain ) ) {
		return false;
	}

	/**
	 * Lọc xem có bỏ qua việc tải tệp .mo hay không.
	 *
	 * Trả về giá trị khác null từ bộ lọc sẽ bỏ qua hoàn toàn
	 * việc tải, trả về giá trị được truyền vào thay thế.
	 *
	 * @since 6.3.0
	 *
	 * @param bool|null   $loaded Kết quả tải tệp .mo. Mặc định null.
	 * @param string      $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 * @param string      $mofile Đường dẫn đến tệp MO.
	 * @param string|null $locale Ngôn ngữ.
	 */
	$loaded = apply_filters( 'pre_load_textdomain', null, $domain, $mofile, $locale );
	if ( null !== $loaded ) {
		if ( true === $loaded ) {
			unset( $l10n_unloaded[ $domain ] );
		}

		return $loaded;
	}

	/**
	 * Lọc xem có ghi đè việc tải tệp .mo hay không.
	 *
	 * @since 2.9.0
	 * @since 6.2.0 Thêm tham số `$locale`.
	 *
	 * @param bool        $override Có ghi đè việc tải tệp .mo hay không. Mặc định false.
	 * @param string      $domain   Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 * @param string      $mofile   Đường dẫn đến tệp MO.
	 * @param string|null $locale   Ngôn ngữ.
	 */
	$plugin_override = apply_filters( 'override_load_textdomain', false, $domain, $mofile, $locale );

	if ( true === (bool) $plugin_override ) {
		unset( $l10n_unloaded[ $domain ] );

		return true;
	}

	/**
	 * Kích hoạt trước khi tệp dịch MO được tải.
	 *
	 * @since 2.9.0
	 *
	 * @param string $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 * @param string $mofile Đường dẫn đến tệp .mo.
	 */
	do_action( 'load_textdomain', $domain, $mofile );

	/**
	 * Lọc đường dẫn tệp MO để tải bản dịch cho miền văn bản cụ thể.
	 *
	 * @since 2.9.0
	 *
	 * @param string $mofile Đường dẫn đến tệp MO.
	 * @param string $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 */
	$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

	if ( ! $locale ) {
		$locale = determine_locale();
	}

	$i18n_controller = WP_Translation_Controller::get_instance();

	// Đảm bảo ngôn ngữ đúng được thiết lập là ngôn ngữ hiện tại, trong trường hợp nó đã bị lọc.
	$i18n_controller->set_locale( $locale );

	/**
	 * Lọc định dạng tệp ưu tiên cho các tệp dịch.
	 *
	 * Có thể được sử dụng để vô hiệu hóa việc dùng tệp PHP cho bản dịch.
	 *
	 * @since 6.5.0
	 *
	 * @param string $preferred_format Định dạng tệp ưu tiên. Giá trị có thể: 'php', 'mo'. Mặc định: 'php'.
	 * @param string $domain           Miền văn bản.
	 */
	$preferred_format = apply_filters( 'translation_file_format', 'php', $domain );
	if ( ! in_array( $preferred_format, array( 'php', 'mo' ), true ) ) {
		$preferred_format = 'php';
	}

	$translation_files = array();

	if ( 'mo' !== $preferred_format ) {
		$translation_files[] = substr_replace( $mofile, ".l10n.$preferred_format", - strlen( '.mo' ) );
	}

	$translation_files[] = $mofile;

	foreach ( $translation_files as $file ) {
		/**
		 * Lọc đường dẫn tệp để tải bản dịch cho miền văn bản đã cho.
		 *
		 * Tương tự bộ lọc {@see 'load_textdomain_mofile'} với sự khác biệt là
		 * đường dẫn tệp có thể là tệp MO hoặc PHP.
		 *
		 * @since 6.5.0
		 * @since 6.6.0 Thêm tham số `$locale`.
		 *
		 * @param string $file   Đường dẫn đến tệp dịch cần tải.
		 * @param string $domain Miền văn bản.
		 * @param string $locale Ngôn ngữ.
		 */
		$file = (string) apply_filters( 'load_translation_file', $file, $domain, $locale );

		$success = $i18n_controller->load_file( $file, $domain, $locale );

		if ( $success ) {
			if ( isset( $l10n[ $domain ] ) && $l10n[ $domain ] instanceof MO ) {
				$i18n_controller->load_file( $l10n[ $domain ]->get_filename(), $domain, $locale );
			}

			// Hủy tham chiếu NOOP_Translations trong get_translations_for_domain().
			unset( $l10n[ $domain ] );

			$l10n[ $domain ] = new WP_Translations( $i18n_controller, $domain );

			$wp_textdomain_registry->set( $domain, $locale, dirname( $file ) );

			return true;
		}
	}

	return false;
}

/**
 * Gỡ tải bản dịch cho một miền văn bản.
 *
 * @since 3.0.0
 * @since 6.1.0 Thêm tham số `$reloadable`.
 *
 * @global MO[] $l10n          Mảng tất cả các miền văn bản đã tải hiện tại.
 * @global MO[] $l10n_unloaded Mảng tất cả các miền văn bản đã được gỡ tải.
 *
 * @param string $domain     Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @param bool   $reloadable Liệu miền văn bản có thể được tải lại kịp thời hay không.
 * @return bool Liệu miền văn bản đã được gỡ tải hay chưa.
 */
function unload_textdomain( $domain, $reloadable = false ) {
	global $l10n, $l10n_unloaded;

	$l10n_unloaded = (array) $l10n_unloaded;

	/**
	 * Lọc xem có ghi đè việc gỡ tải miền văn bản hay không.
	 *
	 * @since 3.0.0
	 * @since 6.1.0 Thêm tham số `$reloadable`.
	 *
	 * @param bool   $override   Có ghi đè việc gỡ tải miền văn bản hay không. Mặc định false.
	 * @param string $domain     Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 * @param bool   $reloadable Liệu miền văn bản có thể được tải lại kịp thời hay không.
	 */
	$plugin_override = apply_filters( 'override_unload_textdomain', false, $domain, $reloadable );

	if ( $plugin_override ) {
		if ( ! $reloadable ) {
			$l10n_unloaded[ $domain ] = true;
		}

		return true;
	}

	/**
	 * Kích hoạt trước khi miền văn bản được gỡ tải.
	 *
	 * @since 3.0.0
	 * @since 6.1.0 Thêm tham số `$reloadable`.
	 *
	 * @param string $domain     Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
	 * @param bool   $reloadable Liệu miền văn bản có thể được tải lại kịp thời hay không.
	 */
	do_action( 'unload_textdomain', $domain, $reloadable );

	// Vì nhiều ngôn ngữ được hỗ trợ, các miền văn bản có thể tải lại không thực sự cần được gỡ tải.
	if ( ! $reloadable ) {
		WP_Translation_Controller::get_instance()->unload_textdomain( $domain );
	}

	if ( isset( $l10n[ $domain ] ) ) {
		if ( $l10n[ $domain ] instanceof NOOP_Translations ) {
			unset( $l10n[ $domain ] );

			return false;
		}

		unset( $l10n[ $domain ] );

		if ( ! $reloadable ) {
			$l10n_unloaded[ $domain ] = true;
		}

		return true;
	}

	return false;
}

/**
 * Tải các chuỗi dịch mặc định dựa trên ngôn ngữ.
 *
 * Tải tệp .mo trong đường dẫn hằng số WP_LANG_DIR từ thư mục gốc WordPress.
 * Tệp đã dịch (.mo) được đặt tên dựa trên ngôn ngữ.
 *
 * @see load_textdomain()
 *
 * @since 1.5.0
 *
 * @param string $locale Tùy chọn. Ngôn ngữ cần tải. Mặc định là giá trị của get_locale().
 * @return bool Liệu miền văn bản đã được tải hay chưa.
 */
function load_default_textdomain( $locale = null ) {
	if ( null === $locale ) {
		$locale = determine_locale();
	}

	// Gỡ tải các chuỗi đã tải trước đó để có thể chuyển đổi bản dịch.
	unload_textdomain( 'default', true );

	$return = load_textdomain( 'default', WP_LANG_DIR . "/$locale.mo", $locale );

	if ( ( is_multisite() || ( defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK ) ) && ! file_exists( WP_LANG_DIR . "/admin-$locale.mo" ) ) {
		load_textdomain( 'default', WP_LANG_DIR . "/ms-$locale.mo", $locale );
		return $return;
	}

	if ( is_admin() || wp_installing() || ( defined( 'WP_REPAIRING' ) && WP_REPAIRING ) || doing_action( 'wp_maybe_auto_update' ) ) {
		load_textdomain( 'default', WP_LANG_DIR . "/admin-$locale.mo", $locale );
	}

	if ( is_network_admin() || ( defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK ) ) {
		load_textdomain( 'default', WP_LANG_DIR . "/admin-network-$locale.mo", $locale );
	}

	return $return;
}

/**
 * Tải các chuỗi đã dịch của plugin.
 *
 * Nếu đường dẫn không được cung cấp thì sẽ là thư mục gốc của plugin.
 *
 * Tệp .mo nên được đặt tên dựa trên miền văn bản với dấu gạch ngang, rồi đến ngôn ngữ chính xác.
 *
 * @since 1.5.0
 * @since 4.6.0 Hàm giờ đây thử tải tệp .mo từ thư mục ngôn ngữ trước.
 * @since 6.7.0 Bản dịch không còn được tải ngay lập tức, mà được chuyển cho cơ chế tải kịp thời.
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 * @global array<string, WP_Translations|NOOP_Translations> $l10n Mảng tất cả các miền văn bản đã tải hiện tại.
 *
 * @param string       $domain          Định danh duy nhất để lấy các chuỗi đã dịch.
 * @param string|false $deprecated      Tùy chọn. Đã lỗi thời. Sử dụng tham số $plugin_rel_path thay thế.
 *                                      Mặc định false.
 * @param string|false $plugin_rel_path Tùy chọn. Đường dẫn tương đối đến WP_PLUGIN_DIR nơi tệp .mo nằm.
 *                                      Mặc định false.
 * @return bool True khi miền văn bản được tải thành công, false ngược lại.
 */
function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
	/** @var WP_Textdomain_Registry $wp_textdomain_registry */
	/** @var array<string, WP_Translations|NOOP_Translations> $l10n */
	global $wp_textdomain_registry, $l10n;

	if ( ! is_string( $domain ) ) {
		return false;
	}

	if ( false !== $plugin_rel_path ) {
		$path = WP_PLUGIN_DIR . '/' . trim( $plugin_rel_path, '/' );
	} elseif ( false !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '2.7.0' );
		$path = ABSPATH . trim( $deprecated, '/' );
	} else {
		$path = WP_PLUGIN_DIR;
	}

	$wp_textdomain_registry->set_custom_path( $domain, $path );

	// Nếu tải kịp thời đã được kích hoạt trước đó, đặt lại mục để có thể thử lại.
	if ( isset( $l10n[ $domain ] ) && $l10n[ $domain ] instanceof NOOP_Translations ) {
		unset( $l10n[ $domain ] );
	}

	return true;
}

/**
 * Tải các chuỗi đã dịch cho plugin nằm trong thư mục mu-plugins.
 *
 * @since 3.0.0
 * @since 4.6.0 Hàm giờ đây thử tải tệp .mo từ thư mục ngôn ngữ trước.
 * @since 6.7.0 Bản dịch không còn được tải ngay lập tức, mà được chuyển cho cơ chế tải kịp thời.
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 * @global array<string, WP_Translations|NOOP_Translations> $l10n Mảng tất cả các miền văn bản đã tải hiện tại.
 *
 * @param string $domain             Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @param string $mu_plugin_rel_path Tùy chọn. Tương đối với thư mục `WPMU_PLUGIN_DIR` nơi tệp .mo
 *                                   nằm. Mặc định chuỗi rỗng.
 * @return bool True khi miền văn bản được tải thành công, false ngược lại.
 */
function load_muplugin_textdomain( $domain, $mu_plugin_rel_path = '' ) {
	/** @var WP_Textdomain_Registry $wp_textdomain_registry */
	/** @var array<string, WP_Translations|NOOP_Translations> $l10n */
	global $wp_textdomain_registry, $l10n;

	if ( ! is_string( $domain ) ) {
		return false;
	}

	$path = WPMU_PLUGIN_DIR . '/' . ltrim( $mu_plugin_rel_path, '/' );

	$wp_textdomain_registry->set_custom_path( $domain, $path );

	// Nếu tải kịp thời đã được kích hoạt trước đó, đặt lại mục để có thể thử lại.
	if ( isset( $l10n[ $domain ] ) && $l10n[ $domain ] instanceof NOOP_Translations ) {
		unset( $l10n[ $domain ] );
	}

	return true;
}

/**
 * Tải các chuỗi đã dịch của giao diện.
 *
 * Nếu ngôn ngữ hiện tại tồn tại dưới dạng tệp .mo trong thư mục gốc của giao diện,
 * nó sẽ được bao gồm trong các chuỗi đã dịch theo $domain.
 *
 * Các tệp .mo phải được đặt tên chính xác theo ngôn ngữ.
 *
 * @since 1.5.0
 * @since 4.6.0 Hàm giờ đây thử tải tệp .mo từ thư mục ngôn ngữ trước.
 * @since 6.7.0 Bản dịch không còn được tải ngay lập tức, mà được chuyển cho cơ chế tải kịp thời.
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 * @global array<string, WP_Translations|NOOP_Translations> $l10n Mảng tất cả các miền văn bản đã tải hiện tại.
 *
 * @param string       $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @param string|false $path   Tùy chọn. Đường dẫn đến thư mục chứa tệp .mo.
 *                             Mặc định false.
 * @return bool True khi miền văn bản được tải thành công, false ngược lại.
 */
function load_theme_textdomain( $domain, $path = false ) {
	/** @var WP_Textdomain_Registry $wp_textdomain_registry */
	/** @var array<string, WP_Translations|NOOP_Translations> $l10n */
	global $wp_textdomain_registry, $l10n;

	if ( ! is_string( $domain ) ) {
		return false;
	}

	if ( ! $path ) {
		$path = get_template_directory();
	}

	$wp_textdomain_registry->set_custom_path( $domain, $path );

	// Nếu tải kịp thời đã được kích hoạt trước đó, đặt lại mục để có thể thử lại.
	if ( isset( $l10n[ $domain ] ) && $l10n[ $domain ] instanceof NOOP_Translations ) {
		unset( $l10n[ $domain ] );
	}

	return true;
}

/**
 * Tải các chuỗi đã dịch của giao diện con.
 *
 * Nếu ngôn ngữ hiện tại tồn tại dưới dạng tệp .mo trong thư mục gốc
 * của giao diện con, nó sẽ được bao gồm trong các chuỗi đã dịch theo $domain.
 *
 * Các tệp .mo phải được đặt tên chính xác theo ngôn ngữ.
 *
 * @since 2.9.0
 *
 * @param string       $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @param string|false $path   Tùy chọn. Đường dẫn đến thư mục chứa tệp .mo.
 *                             Mặc định false.
 * @return bool True khi miền văn bản của giao diện được tải thành công, false ngược lại.
 */
function load_child_theme_textdomain( $domain, $path = false ) {
	if ( ! $path ) {
		$path = get_stylesheet_directory();
	}
	return load_theme_textdomain( $domain, $path );
}

/**
 * Tải các chuỗi đã dịch của script.
 *
 * @since 5.0.0
 * @since 5.0.2 Sử dụng load_script_translations() để tải dữ liệu dịch.
 * @since 5.1.0 Tham số `$domain` được đặt là tùy chọn.
 *
 * @see WP_Scripts::set_translations()
 *
 * @param string $handle Tên script để đăng ký miền văn bản dịch.
 * @param string $domain Tùy chọn. Miền văn bản. Mặc định 'default'.
 * @param string $path   Tùy chọn. Đường dẫn đầy đủ đến thư mục chứa tệp dịch.
 * @return string|false Các chuỗi đã dịch ở dạng mã hóa JSON khi thành công,
 *                      false nếu miền văn bản của script không thể được tải.
 */
function load_script_textdomain( $handle, $domain = 'default', $path = '' ) {
	$wp_scripts = wp_scripts();

	if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
		return false;
	}

	$path   = untrailingslashit( $path );
	$locale = determine_locale();

	// Nếu đường dẫn đã được cung cấp và tệp handle tồn tại thì trả về ngay.
	$file_base       = 'default' === $domain ? $locale : $domain . '-' . $locale;
	$handle_filename = $file_base . '-' . $handle . '.json';

	if ( $path ) {
		$translations = load_script_translations( $path . '/' . $handle_filename, $handle, $domain );

		if ( $translations ) {
			return $translations;
		}
	}

	$src = $wp_scripts->registered[ $handle ]->src;

	if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $wp_scripts->content_url && str_starts_with( $src, $wp_scripts->content_url ) ) ) {
		$src = $wp_scripts->base_url . $src;
	}

	$relative       = false;
	$languages_path = WP_LANG_DIR;

	$src_url     = wp_parse_url( $src );
	$content_url = wp_parse_url( content_url() );
	$plugins_url = wp_parse_url( plugins_url() );
	$site_url    = wp_parse_url( site_url() );
	$theme_root  = get_theme_root();

	// Nếu host giống nhau hoặc là URL tương đối.
	if (
		( ! isset( $content_url['path'] ) || str_starts_with( $src_url['path'], $content_url['path'] ) ) &&
		( ! isset( $src_url['host'] ) || ! isset( $content_url['host'] ) || $src_url['host'] === $content_url['host'] )
	) {
		// Làm cho src tương đối với plugin hoặc giao diện cụ thể.
		if ( isset( $content_url['path'] ) ) {
			$relative = substr( $src_url['path'], strlen( $content_url['path'] ) );
		} else {
			$relative = $src_url['path'];
		}
		$relative = trim( $relative, '/' );
		$relative = explode( '/', $relative );

		/*
		 * Đảm bảo đường dẫn ngôn ngữ đúng khi sử dụng cấu hình `WP_PLUGIN_DIR` / `WP_PLUGIN_URL` tùy chỉnh,
		 * thư mục gốc giao diện tùy chỉnh, và/hoặc sử dụng Multisite với thư mục con.
		 * Xem https://core.trac.wordpress.org/ticket/60891 và https://core.trac.wordpress.org/ticket/62016.
		 */

		$theme_dir = array_slice( explode( '/', $theme_root ), -1 );
		$dirname   = $theme_dir[0] === $relative[0] ? 'themes' : 'plugins';

		$languages_path = WP_LANG_DIR . '/' . $dirname;

		$relative = array_slice( $relative, 2 ); // Xóa plugins/<tên plugin> hoặc themes/<tên giao diện>.
		$relative = implode( '/', $relative );
	} elseif (
		( ! isset( $plugins_url['path'] ) || str_starts_with( $src_url['path'], $plugins_url['path'] ) ) &&
		( ! isset( $src_url['host'] ) || ! isset( $plugins_url['host'] ) || $src_url['host'] === $plugins_url['host'] )
	) {
		// Làm cho src tương đối với plugin cụ thể.
		if ( isset( $plugins_url['path'] ) ) {
			$relative = substr( $src_url['path'], strlen( $plugins_url['path'] ) );
		} else {
			$relative = $src_url['path'];
		}
		$relative = trim( $relative, '/' );
		$relative = explode( '/', $relative );

		$languages_path = WP_LANG_DIR . '/plugins';

		$relative = array_slice( $relative, 1 ); // Xóa <tên plugin>.
		$relative = implode( '/', $relative );
	} elseif ( ! isset( $src_url['host'] ) || ! isset( $site_url['host'] ) || $src_url['host'] === $site_url['host'] ) {
		if ( ! isset( $site_url['path'] ) ) {
			$relative = trim( $src_url['path'], '/' );
		} elseif ( str_starts_with( $src_url['path'], trailingslashit( $site_url['path'] ) ) ) {
			// Làm cho src tương đối với thư mục gốc WP.
			$relative = substr( $src_url['path'], strlen( $site_url['path'] ) );
			$relative = trim( $relative, '/' );
		}
	}

	/**
	 * Lọc đường dẫn tương đối của script dùng để tìm tệp dịch.
	 *
	 * @since 5.0.2
	 *
	 * @param string|false $relative Đường dẫn tương đối của script. False nếu không thể xác định.
	 * @param string       $src      URL nguồn đầy đủ của script.
	 */
	$relative = apply_filters( 'load_script_textdomain_relative_path', $relative, $src );

	// Nếu nguồn không đến từ WP.
	if ( false === $relative ) {
		return load_script_translations( false, $handle, $domain );
	}

	// Bản dịch luôn dựa trên tên tệp chưa nén.
	if ( str_ends_with( $relative, '.min.js' ) ) {
		$relative = substr( $relative, 0, -7 ) . '.js';
	}

	$md5_filename = $file_base . '-' . md5( $relative ) . '.json';

	if ( $path ) {
		$translations = load_script_translations( $path . '/' . $md5_filename, $handle, $domain );

		if ( $translations ) {
			return $translations;
		}
	}

	$translations = load_script_translations( $languages_path . '/' . $md5_filename, $handle, $domain );

	if ( $translations ) {
		return $translations;
	}

	return load_script_translations( false, $handle, $domain );
}

/**
 * Tải dữ liệu dịch cho handle script và miền văn bản đã cho.
 *
 * @since 5.0.2
 *
 * @param string|false $file   Đường dẫn đến tệp dịch cần tải. False nếu không có.
 * @param string       $handle Tên script để đăng ký miền văn bản dịch.
 * @param string       $domain Miền văn bản.
 * @return string|false Các chuỗi đã dịch mã hóa JSON cho handle script và miền văn bản đã cho.
 *                      False nếu không có.
 */
function load_script_translations( $file, $handle, $domain ) {
	/**
	 * Lọc trước bản dịch script cho tệp, handle script và miền văn bản đã cho.
	 *
	 * Trả về giá trị khác null cho phép ghi đè logic mặc định, bỏ qua hoàn toàn hàm.
	 *
	 * @since 5.0.2
	 *
	 * @param string|false|null $translations Dữ liệu dịch mã hóa JSON. Mặc định null.
	 * @param string|false      $file         Đường dẫn đến tệp dịch cần tải. False nếu không có.
	 * @param string            $handle       Tên script để đăng ký miền văn bản dịch.
	 * @param string            $domain       Miền văn bản.
	 */
	$translations = apply_filters( 'pre_load_script_translations', null, $file, $handle, $domain );

	if ( null !== $translations ) {
		return $translations;
	}

	/**
	 * Lọc đường dẫn tệp để tải bản dịch script cho handle script và miền văn bản đã cho.
	 *
	 * @since 5.0.2
	 *
	 * @param string|false $file   Đường dẫn đến tệp dịch cần tải. False nếu không có.
	 * @param string       $handle Tên script để đăng ký miền văn bản dịch.
	 * @param string       $domain Miền văn bản.
	 */
	$file = apply_filters( 'load_script_translation_file', $file, $handle, $domain );

	if ( ! $file || ! is_readable( $file ) ) {
		return false;
	}

	$translations = file_get_contents( $file );

	/**
	 * Lọc bản dịch script cho tệp, handle script và miền văn bản đã cho.
	 *
	 * @since 5.0.2
	 *
	 * @param string $translations Dữ liệu dịch mã hóa JSON.
	 * @param string $file         Đường dẫn đến tệp dịch đã được tải.
	 * @param string $handle       Tên script để đăng ký miền văn bản dịch.
	 * @param string $domain       Miền văn bản.
	 */
	return apply_filters( 'load_script_translations', $translations, $file, $handle, $domain );
}

/**
 * Tải miền văn bản của plugin và giao diện theo cơ chế kịp thời.
 *
 * Khi một miền văn bản được gặp lần đầu tiên, chúng ta thử tải
 * tệp dịch từ `wp-content/languages`, loại bỏ nhu cầu
 * gọi load_plugin_textdomain() hoặc load_theme_textdomain().
 *
 * @since 4.6.0
 * @access private
 *
 * @global MO[]                   $l10n_unloaded          Mảng tất cả các miền văn bản đã được gỡ tải.
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 *
 * @param string $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @return bool True khi miền văn bản được tải thành công, false ngược lại.
 */
function _load_textdomain_just_in_time( $domain ) {
	/** @var WP_Textdomain_Registry $wp_textdomain_registry */
	global $l10n_unloaded, $wp_textdomain_registry;

	$l10n_unloaded = (array) $l10n_unloaded;

	// Bỏ qua nếu domain là 'default' vì nó được dành riêng cho lõi.
	if ( 'default' === $domain || isset( $l10n_unloaded[ $domain ] ) ) {
		return false;
	}

	if ( ! $wp_textdomain_registry->has( $domain ) ) {
		return false;
	}

	$locale = determine_locale();
	$path   = $wp_textdomain_registry->get( $domain, $locale );
	if ( ! $path ) {
		return false;
	}

	if ( ! doing_action( 'after_setup_theme' ) && ! did_action( 'after_setup_theme' ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: The text domain. 2: 'init'. */
				__( 'Translation loading for the %1$s domain was triggered too early. This is usually an indicator for some code in the plugin or theme running too early. Translations should be loaded at the %2$s action or later.' ),
				'<code>' . $domain . '</code>',
				'<code>init</code>'
			),
			'6.7.0'
		);
	}

	// Các giao diện có thư mục ngôn ngữ ngoài WP_LANG_DIR có tên tệp khác.
	$template_directory   = trailingslashit( get_template_directory() );
	$stylesheet_directory = trailingslashit( get_stylesheet_directory() );
	if ( str_starts_with( $path, $template_directory ) || str_starts_with( $path, $stylesheet_directory ) ) {
		$mofile = "{$path}{$locale}.mo";
	} else {
		$mofile = "{$path}{$domain}-{$locale}.mo";
	}

	return load_textdomain( $domain, $mofile, $locale );
}

/**
 * Trả về thể hiện Translations cho một miền văn bản.
 *
 * Nếu không có, trả về thể hiện Translations rỗng.
 *
 * @since 2.8.0
 *
 * @global MO[] $l10n Mảng tất cả các miền văn bản đã tải hiện tại.
 *
 * @param string $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @return Translations|NOOP_Translations Thể hiện Translations.
 */
function get_translations_for_domain( $domain ) {
	global $l10n;
	if ( isset( $l10n[ $domain ] ) || ( _load_textdomain_just_in_time( $domain ) && isset( $l10n[ $domain ] ) ) ) {
		return $l10n[ $domain ];
	}

	static $noop_translations = null;
	if ( null === $noop_translations ) {
		$noop_translations = new NOOP_Translations();
	}

	$l10n[ $domain ] = &$noop_translations;

	return $noop_translations;
}

/**
 * Xác định xem có bản dịch cho miền văn bản hay không.
 *
 * @since 3.0.0
 *
 * @global MO[] $l10n Mảng tất cả các miền văn bản đã tải hiện tại.
 *
 * @param string $domain Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 * @return bool Liệu có bản dịch hay không.
 */
function is_textdomain_loaded( $domain ) {
	global $l10n;
	return isset( $l10n[ $domain ] ) && ! $l10n[ $domain ] instanceof NOOP_Translations;
}

/**
 * Dịch tên vai trò.
 *
 * Vì tên vai trò nằm trong cơ sở dữ liệu và không nằm trong mã nguồn nên
 * có các lệnh gọi gettext giả để đưa chúng vào tệp POT và hàm này
 * dịch chúng trở lại đúng cách.
 *
 * Lệnh gọi before_last_bar() cần thiết vì các cài đặt cũ giữ vai trò
 * sử dụng định dạng ngữ cảnh cũ: 'Role name|User role' và việc bỏ qua
 * nội dung sau dấu gạch đứng cuối cùng dễ hơn sửa chúng trong DB. Các cài đặt mới
 * sẽ không gặp vấn đề đó.
 *
 * @since 2.8.0
 * @since 5.2.0 Thêm tham số `$domain`.
 *
 * @param string $name   Tên vai trò.
 * @param string $domain Tùy chọn. Miền văn bản. Định danh duy nhất để lấy các chuỗi đã dịch.
 *                       Mặc định 'default'.
 * @return string Tên vai trò đã dịch khi thành công, tên gốc khi thất bại.
 */
function translate_user_role( $name, $domain = 'default' ) {
	return translate_with_gettext_context( before_last_bar( $name ), 'User role', $domain );
}

/**
 * Lấy tất cả các ngôn ngữ có sẵn dựa trên sự hiện diện của tệp *.mo và *.l10n.php trong thư mục đã cho.
 *
 * Thư mục mặc định là WP_LANG_DIR.
 *
 * @since 3.0.0
 * @since 4.7.0 Kết quả giờ có thể lọc bằng bộ lọc {@see 'get_available_languages'}.
 * @since 6.5.0 Danh sách tệp ban đầu giờ được lưu cache và cũng tính đến tệp *.l10n.php.
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 *
 * @param string $dir Thư mục để tìm kiếm tệp ngôn ngữ.
 *                    Mặc định WP_LANG_DIR.
 * @return string[] Mảng các mã ngôn ngữ hoặc mảng rỗng nếu không có ngôn ngữ nào.
 *                  Mã ngôn ngữ được tạo bằng cách loại bỏ phần mở rộng tệp từ tên tệp ngôn ngữ.
 */
function get_available_languages( $dir = null ) {
	global $wp_textdomain_registry;

	$languages = array();

	$path       = is_null( $dir ) ? WP_LANG_DIR : $dir;
	$lang_files = $wp_textdomain_registry->get_language_files_from_path( $path );

	if ( $lang_files ) {
		foreach ( $lang_files as $lang_file ) {
			$lang_file = basename( $lang_file, '.mo' );
			$lang_file = basename( $lang_file, '.l10n.php' );

			if ( ! str_starts_with( $lang_file, 'continents-cities' ) && ! str_starts_with( $lang_file, 'ms-' ) &&
				! str_starts_with( $lang_file, 'admin-' ) ) {
				$languages[] = $lang_file;
			}
		}
	}

	/**
	 * Lọc danh sách các mã ngôn ngữ có sẵn.
	 *
	 * @since 4.7.0
	 *
	 * @param string[] $languages Mảng các mã ngôn ngữ có sẵn.
	 * @param string   $dir       Thư mục nơi tìm thấy tệp ngôn ngữ.
	 */
	return apply_filters( 'get_available_languages', array_unique( $languages ), $dir );
}

/**
 * Lấy các bản dịch đã cài đặt.
 *
 * Tìm trong thư mục wp-content/languages các bản dịch của
 * plugin hoặc giao diện.
 *
 * @since 3.7.0
 *
 * @global WP_Textdomain_Registry $wp_textdomain_registry Registry Miền văn bản WordPress.
 *
 * @param string $type Loại cần tìm. Chấp nhận 'plugins', 'themes', 'core'.
 * @return array Mảng dữ liệu ngôn ngữ.
 */
function wp_get_installed_translations( $type ) {
	global $wp_textdomain_registry;

	if ( 'themes' !== $type && 'plugins' !== $type && 'core' !== $type ) {
		return array();
	}

	$dir = 'core' === $type ? WP_LANG_DIR : WP_LANG_DIR . "/$type";

	if ( ! is_dir( $dir ) ) {
		return array();
	}

	$files = $wp_textdomain_registry->get_language_files_from_path( $dir );
	if ( ! $files ) {
		return array();
	}

	$language_data = array();

	foreach ( $files as $file ) {
		if ( ! preg_match( '/(?:(.+)-)?([a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?)\.(?:mo|l10n\.php)/', basename( $file ), $match ) ) {
			continue;
		}

		list( , $textdomain, $language ) = $match;
		if ( '' === $textdomain ) {
			$textdomain = 'default';
		}

		if ( str_ends_with( $file, '.mo' ) ) {
			$pofile = substr_replace( $file, '.po', - strlen( '.mo' ) );

			if ( ! file_exists( $pofile ) ) {
				continue;
			}

			$language_data[ $textdomain ][ $language ] = wp_get_pomo_file_data( $pofile );
		} else {
			$pofile = substr_replace( $file, '.po', - strlen( '.l10n.php' ) );

			// Nếu cả tệp PO và PHP đều tồn tại, ưu tiên tệp PO.
			if ( file_exists( $pofile ) ) {
				continue;
			}

			$language_data[ $textdomain ][ $language ] = wp_get_l10n_php_file_data( $file );
		}
	}
	return $language_data;
}

/**
 * Trích xuất tiêu đề từ tệp PO.
 *
 * @since 3.7.0
 *
 * @param string $po_file Đường dẫn đến tệp PO.
 * @return string[] Mảng giá trị tiêu đề tệp PO được đánh khóa theo tên tiêu đề.
 */
function wp_get_pomo_file_data( $po_file ) {
	$headers = get_file_data(
		$po_file,
		array(
			'POT-Creation-Date'  => '"POT-Creation-Date',
			'PO-Revision-Date'   => '"PO-Revision-Date',
			'Project-Id-Version' => '"Project-Id-Version',
			'X-Generator'        => '"X-Generator',
		)
	);
	foreach ( $headers as $header => $value ) {
		// Xóa ký tự '\n' theo ngữ cảnh và dấu ngoặc kép đóng.
		$headers[ $header ] = preg_replace( '~(\\\n)?"$~', '', $value );
	}
	return $headers;
}

/**
 * Trích xuất tiêu đề từ tệp dịch PHP.
 *
 * @since 6.6.0
 *
 * @param string $php_file Đường dẫn đến tệp `.l10n.php`.
 * @return string[] Mảng giá trị tiêu đề tệp được đánh khóa theo tên tiêu đề.
 */
function wp_get_l10n_php_file_data( $php_file ) {
	$data = (array) include $php_file;

	unset( $data['messages'] );
	$headers = array(
		'POT-Creation-Date'  => 'pot-creation-date',
		'PO-Revision-Date'   => 'po-revision-date',
		'Project-Id-Version' => 'project-id-version',
		'X-Generator'        => 'x-generator',
	);

	$result = array(
		'POT-Creation-Date'  => '',
		'PO-Revision-Date'   => '',
		'Project-Id-Version' => '',
		'X-Generator'        => '',
	);

	foreach ( $headers as $po_header => $php_header ) {
		if ( isset( $data[ $php_header ] ) ) {
			$result[ $po_header ] = $data[ $php_header ];
		}
	}

	return $result;
}

/**
 * Hiển thị hoặc trả về bộ chọn Ngôn ngữ.
 *
 * @since 4.0.0
 * @since 4.3.0 Giới thiệu tham số `echo`.
 * @since 4.7.0 Giới thiệu tham số `show_option_site_default`.
 * @since 5.1.0 Giới thiệu tham số `show_option_en_us`.
 * @since 5.9.0 Giới thiệu tham số `explicit_option_en_us`.
 *
 * @see get_available_languages()
 * @see wp_get_available_translations()
 *
 * @param string|array $args {
 *     Tùy chọn. Mảng hoặc chuỗi các tham số để xuất bộ chọn ngôn ngữ.
 *
 *     @type string   $id                           Thuộc tính ID của phần tử select. Mặc định 'locale'.
 *     @type string   $name                         Thuộc tính name của phần tử select. Mặc định 'locale'.
 *     @type string[] $languages                    Danh sách ngôn ngữ đã cài đặt, chỉ chứa các mã ngôn ngữ.
 *                                                  Mặc định mảng rỗng.
 *     @type array    $translations                 Danh sách các bản dịch có sẵn. Mặc định kết quả của
 *                                                  wp_get_available_translations().
 *     @type string   $selected                     Ngôn ngữ nên được chọn. Mặc định rỗng.
 *     @type bool|int $echo                         Có xuất mã HTML đã tạo hay không. Chấp nhận 0, 1, hoặc
 *                                                  tương đương boolean. Mặc định 1.
 *     @type bool     $show_available_translations  Có hiển thị các bản dịch có sẵn hay không. Mặc định true.
 *     @type bool     $show_option_site_default     Có hiển thị tùy chọn để dùng ngôn ngữ mặc định của trang hay không. Mặc định false.
 *     @type bool     $show_option_en_us            Có hiển thị tùy chọn Tiếng Anh (Hoa Kỳ) hay không. Mặc định true.
 *     @type bool     $explicit_option_en_us        Tùy chọn Tiếng Anh (Hoa Kỳ) có sử dụng giá trị rõ ràng en_US
 *                                                  thay vì giá trị rỗng hay không. Mặc định false.
 * }
 * @return string Danh sách dropdown HTML các ngôn ngữ.
 */
function wp_dropdown_languages( $args = array() ) {

	$parsed_args = wp_parse_args(
		$args,
		array(
			'id'                          => 'locale',
			'name'                        => 'locale',
			'languages'                   => array(),
			'translations'                => array(),
			'selected'                    => '',
			'echo'                        => 1,
			'show_available_translations' => true,
			'show_option_site_default'    => false,
			'show_option_en_us'           => true,
			'explicit_option_en_us'       => false,
		)
	);

	// Thoát nếu không có ID hoặc không có name.
	if ( ! $parsed_args['id'] || ! $parsed_args['name'] ) {
		return;
	}

	// Tiếng Anh (Hoa Kỳ) sử dụng chuỗi rỗng cho thuộc tính value.
	if ( 'en_US' === $parsed_args['selected'] && ! $parsed_args['explicit_option_en_us'] ) {
		$parsed_args['selected'] = '';
	}

	$translations = $parsed_args['translations'];
	if ( empty( $translations ) ) {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$translations = wp_get_available_translations();
	}

	/*
	 * $parsed_args['languages'] chỉ nên chứa các mã ngôn ngữ. Tìm mã ngôn ngữ trong
	 * $translations để lấy tên bản địa. Quay về mã ngôn ngữ nếu không tìm thấy.
	 */
	$languages = array();
	foreach ( $parsed_args['languages'] as $locale ) {
		if ( isset( $translations[ $locale ] ) ) {
			$translation = $translations[ $locale ];
			$languages[] = array(
				'language'    => $translation['language'],
				'native_name' => $translation['native_name'],
				'lang'        => current( $translation['iso'] ),
			);

			// Xóa ngôn ngữ đã cài đặt khỏi các bản dịch có sẵn.
			unset( $translations[ $locale ] );
		} else {
			$languages[] = array(
				'language'    => $locale,
				'native_name' => $locale,
				'lang'        => '',
			);
		}
	}

	$translations_available = ( ! empty( $translations ) && $parsed_args['show_available_translations'] );

	// Giữ mã HTML.
	$structure = array();

	// Liệt kê các ngôn ngữ đã cài đặt.
	if ( $translations_available ) {
		$structure[] = '<optgroup label="' . esc_attr_x( 'Installed', 'translations' ) . '">';
	}

	// Mặc định của trang.
	if ( $parsed_args['show_option_site_default'] ) {
		$structure[] = sprintf(
			'<option value="site-default" data-installed="1"%s>%s</option>',
			selected( 'site-default', $parsed_args['selected'], false ),
			_x( 'Site Default', 'default site language' )
		);
	}

	if ( $parsed_args['show_option_en_us'] ) {
		$value       = ( $parsed_args['explicit_option_en_us'] ) ? 'en_US' : '';
		$structure[] = sprintf(
			'<option value="%s" lang="en" data-installed="1"%s>English (United States)</option>',
			esc_attr( $value ),
			selected( '', $parsed_args['selected'], false )
		);
	}

	// Liệt kê các ngôn ngữ đã cài đặt.
	foreach ( $languages as $language ) {
		$structure[] = sprintf(
			'<option value="%s" lang="%s"%s data-installed="1">%s</option>',
			esc_attr( $language['language'] ),
			esc_attr( $language['lang'] ),
			selected( $language['language'], $parsed_args['selected'], false ),
			esc_html( $language['native_name'] )
		);
	}
	if ( $translations_available ) {
		$structure[] = '</optgroup>';
	}

	// Liệt kê các bản dịch có sẵn.
	if ( $translations_available ) {
		$structure[] = '<optgroup label="' . esc_attr_x( 'Available', 'translations' ) . '">';
		foreach ( $translations as $translation ) {
			$structure[] = sprintf(
				'<option value="%s" lang="%s"%s>%s</option>',
				esc_attr( $translation['language'] ),
				esc_attr( current( $translation['iso'] ) ),
				selected( $translation['language'], $parsed_args['selected'], false ),
				esc_html( $translation['native_name'] )
			);
		}
		$structure[] = '</optgroup>';
	}

	// Kết hợp chuỗi đầu ra.
	$output  = sprintf( '<select name="%s" id="%s">', esc_attr( $parsed_args['name'] ), esc_attr( $parsed_args['id'] ) );
	$output .= implode( "\n", $structure );
	$output .= '</select>';

	if ( $parsed_args['echo'] ) {
		echo $output;
	}

	return $output;
}

/**
 * Xác định xem ngôn ngữ hiện tại có phải viết từ phải sang trái (RTL) hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm giao diện tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Giao diện.
 *
 * @since 3.0.0
 *
 * @global WP_Locale $wp_locale Đối tượng ngôn ngữ ngày giờ WordPress.
 *
 * @return bool Liệu ngôn ngữ có phải RTL hay không.
 */
function is_rtl() {
	global $wp_locale;
	if ( ! ( $wp_locale instanceof WP_Locale ) ) {
		return false;
	}
	return $wp_locale->is_rtl();
}

/**
 * Chuyển đổi bản dịch theo ngôn ngữ đã cho.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher Đối tượng chuyển đổi ngôn ngữ WordPress.
 *
 * @param string $locale Ngôn ngữ.
 * @return bool True khi thành công, false khi thất bại.
 */
function switch_to_locale( $locale ) {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	if ( ! $wp_locale_switcher ) {
		return false;
	}

	return $wp_locale_switcher->switch_to_locale( $locale );
}

/**
 * Chuyển đổi bản dịch theo ngôn ngữ của người dùng đã cho.
 *
 * @since 6.2.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher Đối tượng chuyển đổi ngôn ngữ WordPress.
 *
 * @param int $user_id ID người dùng.
 * @return bool True khi thành công, false khi thất bại.
 */
function switch_to_user_locale( $user_id ) {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	if ( ! $wp_locale_switcher ) {
		return false;
	}

	return $wp_locale_switcher->switch_to_user_locale( $user_id );
}

/**
 * Khôi phục bản dịch theo ngôn ngữ trước đó.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher Đối tượng chuyển đổi ngôn ngữ WordPress.
 *
 * @return string|false Ngôn ngữ khi thành công, false khi lỗi.
 */
function restore_previous_locale() {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	if ( ! $wp_locale_switcher ) {
		return false;
	}

	return $wp_locale_switcher->restore_previous_locale();
}

/**
 * Khôi phục bản dịch theo ngôn ngữ gốc.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher Đối tượng chuyển đổi ngôn ngữ WordPress.
 *
 * @return string|false Ngôn ngữ khi thành công, false khi lỗi.
 */
function restore_current_locale() {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	if ( ! $wp_locale_switcher ) {
		return false;
	}

	return $wp_locale_switcher->restore_current_locale();
}

/**
 * Xác định xem switch_to_locale() có đang có hiệu lực hay không.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher Đối tượng chuyển đổi ngôn ngữ WordPress.
 *
 * @return bool True nếu ngôn ngữ đã được chuyển đổi, false ngược lại.
 */
function is_locale_switched() {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	return $wp_locale_switcher->is_switched();
}

/**
 * Dịch giá trị cài đặt đã cung cấp bằng schema i18n của nó.
 *
 * @since 5.9.0
 * @access private
 *
 * @param string|string[]|array[]|object $i18n_schema Schema i18n cho cài đặt.
 * @param string|string[]|array[]        $settings    Giá trị cho các cài đặt.
 * @param string                         $textdomain  Miền văn bản để sử dụng với bản dịch.
 *
 * @return string|string[]|array[] Cài đặt đã dịch.
 */
function translate_settings_using_i18n_schema( $i18n_schema, $settings, $textdomain ) {
	if ( empty( $i18n_schema ) || empty( $settings ) || empty( $textdomain ) ) {
		return $settings;
	}

	if ( is_string( $i18n_schema ) && is_string( $settings ) ) {
		return translate_with_gettext_context( $settings, $i18n_schema, $textdomain );
	}
	if ( is_array( $i18n_schema ) && is_array( $settings ) ) {
		$translated_settings = array();
		foreach ( $settings as $value ) {
			$translated_settings[] = translate_settings_using_i18n_schema( $i18n_schema[0], $value, $textdomain );
		}
		return $translated_settings;
	}
	if ( is_object( $i18n_schema ) && is_array( $settings ) ) {
		$group_key           = '*';
		$translated_settings = array();
		foreach ( $settings as $key => $value ) {
			if ( isset( $i18n_schema->$key ) ) {
				$translated_settings[ $key ] = translate_settings_using_i18n_schema( $i18n_schema->$key, $value, $textdomain );
			} elseif ( isset( $i18n_schema->$group_key ) ) {
				$translated_settings[ $key ] = translate_settings_using_i18n_schema( $i18n_schema->$group_key, $value, $textdomain );
			} else {
				$translated_settings[ $key ] = $value;
			}
		}
		return $translated_settings;
	}
	return $settings;
}

/**
 * Lấy ký tự phân tách mục danh sách dựa trên ngôn ngữ.
 *
 * @since 6.0.0
 *
 * @global WP_Locale $wp_locale Đối tượng ngôn ngữ ngày giờ WordPress.
 *
 * @return string Ký tự phân tách mục danh sách theo ngôn ngữ.
 */
function wp_get_list_item_separator() {
	global $wp_locale;

	if ( ! ( $wp_locale instanceof WP_Locale ) ) {
		// Giá trị mặc định của WP_Locale::get_list_item_separator().
		/* translators: Used between list items, there is a space after the comma. */
		return __( ', ' );
	}

	return $wp_locale->get_list_item_separator();
}

/**
 * Lấy kiểu đếm từ dựa trên ngôn ngữ.
 *
 * @since 6.2.0
 *
 * @global WP_Locale $wp_locale Đối tượng ngôn ngữ ngày giờ WordPress.
 *
 * @return string Kiểu đếm từ theo ngôn ngữ. Giá trị có thể là `characters_excluding_spaces`,
 *                `characters_including_spaces`, hoặc `words`. Mặc định `words`.
 */
function wp_get_word_count_type() {
	global $wp_locale;

	if ( ! ( $wp_locale instanceof WP_Locale ) ) {
		// Giá trị mặc định của WP_Locale::get_word_count_type().
		return 'words';
	}

	return $wp_locale->get_word_count_type();
}

/**
 * Trả về giá trị boolean để cho biết liệu bản dịch có tồn tại cho chuỗi đã cho với miền văn bản và ngôn ngữ tùy chọn.
 *
 * @since 6.7.0
 *
 * @param string  $singular   Bản dịch số ít cần kiểm tra.
 * @param string  $textdomain Tùy chọn. Miền văn bản. Mặc định 'default'.
 * @param ?string $locale     Tùy chọn. Ngôn ngữ. Mặc định ngôn ngữ hiện tại.
 * @return bool  True nếu bản dịch tồn tại, false ngược lại.
 */
function has_translation( string $singular, string $textdomain = 'default', ?string $locale = null ): bool {
	return WP_Translation_Controller::get_instance()->has_translation( $singular, $textdomain, $locale );
}
