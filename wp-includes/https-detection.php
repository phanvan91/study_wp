<?php
/**
 * Các hàm phát hiện HTTPS.
 *
 * @package WordPress
 * @since 5.7.0
 */

/**
 * Kiểm tra xem website có đang sử dụng HTTPS hay không.
 *
 * Điều này dựa trên việc cả URL trang chủ và URL site đều sử dụng HTTPS.
 *
 * @since 5.7.0
 * @see wp_is_home_url_using_https()
 * @see wp_is_site_url_using_https()
 *
 * @return bool True nếu đang sử dụng HTTPS, false nếu không.
 */
function wp_is_using_https() {
	if ( ! wp_is_home_url_using_https() ) {
		return false;
	}

	return wp_is_site_url_using_https();
}

/**
 * Kiểm tra xem URL trang chủ hiện tại có đang sử dụng HTTPS hay không.
 *
 * @since 5.7.0
 * @see home_url()
 *
 * @return bool True nếu đang sử dụng HTTPS, false nếu không.
 */
function wp_is_home_url_using_https() {
	return 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
}

/**
 * Kiểm tra xem URL nơi WordPress được lưu trữ có đang sử dụng HTTPS hay không.
 *
 * Hàm này kiểm tra URL nơi các file ứng dụng WordPress (ví dụ: wp-blog-header.php hoặc thư mục wp-admin/)
 * có thể truy cập được.
 *
 * @since 5.7.0
 * @see site_url()
 *
 * @return bool True nếu đang sử dụng HTTPS, false nếu không.
 */
function wp_is_site_url_using_https() {
	/*
	 * Sử dụng truy cập trực tiếp option cho 'siteurl' và chạy thủ công bộ lọc 'site_url'
	 * vì `site_url()` sẽ điều chỉnh scheme dựa trên yêu cầu hiện tại đang sử dụng.
	 */
	/** Bộ lọc này được ghi nhận trong wp-includes/link-template.php */
	$site_url = apply_filters( 'site_url', get_option( 'siteurl' ), '', null, null );

	return 'https' === wp_parse_url( $site_url, PHP_URL_SCHEME );
}

/**
 * Kiểm tra xem HTTPS có được hỗ trợ cho máy chủ và tên miền hay không.
 *
 * Hàm này thực hiện yêu cầu HTTP thông qua `wp_get_https_detection_errors()`
 * để kiểm tra hỗ trợ HTTPS. Vì quá trình này có thể tốn nhiều tài nguyên,
 * nên sử dụng cẩn thận, đặc biệt trong môi trường nhạy cảm về hiệu suất,
 * để tránh các vấn đề về độ trễ tiềm ẩn.
 *
 * @since 5.7.0
 *
 * @return bool True nếu HTTPS được hỗ trợ, false nếu không.
 */
function wp_is_https_supported() {
	$https_detection_errors = wp_get_https_detection_errors();

	// Nếu có lỗi, HTTPS không được hỗ trợ.
	return empty( $https_detection_errors );
}

/**
 * Chạy yêu cầu HTTPS từ xa để phát hiện HTTPS có được hỗ trợ hay không, và lưu trữ các lỗi tiềm ẩn.
 *
 * Hàm này kiểm tra hỗ trợ HTTPS bằng cách thực hiện yêu cầu HTTP. Vì quá trình này có thể tốn nhiều tài nguyên,
 * nên sử dụng cẩn thận, đặc biệt trong môi trường nhạy cảm về hiệu suất.
 * Nó được gọi khi cần xác nhận hỗ trợ HTTPS.
 *
 * @since 6.4.0
 * @access private
 *
 * @return array Mảng chứa các lỗi phát hiện tiềm ẩn liên quan đến HTTPS, hoặc mảng rỗng nếu không tìm thấy lỗi.
 */
function wp_get_https_detection_errors() {
	/**
	 * Bỏ qua quá trình phát hiện lỗi liên quan đến hỗ trợ HTTPS.
	 *
	 * Trả về `WP_Error` từ bộ lọc sẽ bỏ qua logic mặc định của việc thử yêu cầu từ xa
	 * đến site qua HTTPS, lưu trữ mảng lỗi từ `WP_Error` được trả về thay thế.
	 *
	 * @since 6.4.0
	 *
	 * @param null|WP_Error $pre Đối tượng lỗi để bỏ qua phát hiện,
	 *                           hoặc null để tiếp tục với hành vi mặc định.
	 */
	$support_errors = apply_filters( 'pre_wp_get_https_detection_errors', null );
	if ( is_wp_error( $support_errors ) ) {
		return $support_errors->errors;
	}

	$support_errors = new WP_Error();

	$response = wp_remote_request(
		home_url( '/', 'https' ),
		array(
			'headers'   => array(
				'Cache-Control' => 'no-cache',
			),
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		$unverified_response = wp_remote_request(
			home_url( '/', 'https' ),
			array(
				'headers'   => array(
					'Cache-Control' => 'no-cache',
				),
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $unverified_response ) ) {
			$support_errors->add(
				'https_request_failed',
				__( 'HTTPS request failed.' )
			);
		} else {
			$support_errors->add(
				'ssl_verification_failed',
				__( 'SSL verification failed.' )
			);
		}

		$response = $unverified_response;
	}

	if ( ! is_wp_error( $response ) ) {
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$support_errors->add( 'bad_response_code', wp_remote_retrieve_response_message( $response ) );
		} elseif ( false === wp_is_local_html_output( wp_remote_retrieve_body( $response ) ) ) {
			$support_errors->add( 'bad_response_source', __( 'It looks like the response did not come from this site.' ) );
		}
	}

	return $support_errors->errors;
}

/**
 * Kiểm tra xem một chuỗi HTML có phải là đầu ra từ website WordPress này hay không.
 *
 * Hàm này cố gắng kiểm tra các mẫu WordPress phổ biến khác nhau xem chúng có được bao gồm trong chuỗi HTML hay không.
 * Vì bất kỳ hành động nào cũng có thể bị vô hiệu hóa thông qua mã bên thứ ba, hàm này cũng có thể trả về null
 * để chỉ ra rằng không thể xác định quyền sở hữu.
 *
 * @since 5.7.0
 * @access private
 *
 * @param string $html Chuỗi đầu ra HTML đầy đủ, ví dụ: từ phản hồi HTTP.
 * @return bool|null True/false cho việc HTML có được tạo bởi site này hay không, null nếu không thể xác định.
 */
function wp_is_local_html_output( $html ) {
	// 1. Kiểm tra xem HTML có chứa liên kết Really Simple Discovery của site hay không.
	if ( has_action( 'wp_head', 'rsd_link' ) ) {
		$pattern = preg_replace( '#^https?:(?=//)#', '', esc_url( site_url( 'xmlrpc.php?rsd', 'rpc' ) ) ); // Xem rsd_link().
		return str_contains( $html, $pattern );
	}

	// 2. Kiểm tra xem HTML có chứa liên kết REST API của site hay không.
	if ( has_action( 'wp_head', 'rest_output_link_wp_head' ) ) {
		// Thử cả HTTPS và HTTP vì URL phụ thuộc vào ngữ cảnh.
		$pattern = preg_replace( '#^https?:(?=//)#', '', esc_url( get_rest_url() ) ); // Xem rest_output_link_wp_head().
		return str_contains( $html, $pattern );
	}

	// Nếu không thì không thể xác định kết quả.
	return null;
}
