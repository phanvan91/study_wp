<?php
/**
 * Lấy tin nhắn email từ mailbox của người dùng để thêm như
 * một WordPress post. Thông tin kết nối mailbox phải được
 * cấu hình trong Settings > Writing
 *
 * @package WordPress
 */

/** Đảm bảo WordPress bootstrap đã chạy trước khi tiếp tục. */
require __DIR__ . '/wp-load.php';

/** Filter này được ghi chép trong wp-admin/options.php */
if ( ! apply_filters( 'enable_post_by_email_configuration', true ) ) {
	wp_die( __( 'This action has been disabled by the administrator.' ), 403 );
}

$mailserver_url = get_option( 'mailserver_url' );

if ( 'mail.example.com' === $mailserver_url || empty( $mailserver_url ) ) {
	wp_die( __( 'This action has been disabled by the administrator.' ), 403 );
}

/**
 * Kích hoạt để cho phép plugin thực hiện việc tiếp quản hoàn toàn Post by Email.
 *
 * @since 2.9.0
 */
do_action( 'wp-mail.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/** Lấy class POP3 để truy cập mailbox. */
require_once ABSPATH . WPINC . '/class-pop3.php';

/** Chỉ kiểm tra ở khoảng thời gian này cho tin nhắn mới. */
if ( ! defined( 'WP_MAIL_INTERVAL' ) ) {
	define( 'WP_MAIL_INTERVAL', 5 * MINUTE_IN_SECONDS );
}

$last_checked = get_transient( 'mailserver_last_checked' );

if ( $last_checked ) {
	wp_die(
		sprintf(
			// translators: %s giới hạn tốc độ dạng dễ đọc.
			__( 'Email checks are rate limited to once every %s.' ),
			human_time_diff( time() - WP_MAIL_INTERVAL, time() )
		),
		__( 'Slow down, no need to check for new mails so often!' ),
		429
	);
}

set_transient( 'mailserver_last_checked', true, WP_MAIL_INTERVAL );

$time_difference = (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

$phone_delim = '::';

$pop3 = new POP3();

if ( ! $pop3->connect( get_option( 'mailserver_url' ), get_option( 'mailserver_port' ) ) || ! $pop3->user( get_option( 'mailserver_login' ) ) ) {
	wp_die( esc_html( $pop3->ERROR ) );
}

$count = $pop3->pass( get_option( 'mailserver_pass' ) );

if ( false === $count ) {
	wp_die( esc_html( $pop3->ERROR ) );
}

if ( 0 === $count ) {
	$pop3->quit();
	wp_die( __( 'There does not seem to be any new mail.' ) );
}

// Luôn chạy như một user chưa xác thực.
wp_set_current_user( 0 );

for ( $i = 1; $i <= $count; $i++ ) {

	$message = $pop3->get( $i );

	$bodysignal                = false;
	$boundary                  = '';
	$charset                   = '';
	$content                   = '';
	$content_type              = '';
	$content_transfer_encoding = '';
	$post_author               = 1;
	$author_found              = false;
	$post_date                 = null;
	$post_date_gmt             = null;

	foreach ( $message as $line ) {
		// Tín hiệu body.
		if ( strlen( $line ) < 3 ) {
			$bodysignal = true;
		}
		if ( $bodysignal ) {
			$content .= $line;
		} else {
			if ( preg_match( '/Content-Type: /i', $line ) ) {
				$content_type = trim( $line );
				$content_type = substr( $content_type, 14, strlen( $content_type ) - 14 );
				$content_type = explode( ';', $content_type );
				if ( ! empty( $content_type[1] ) ) {
					$charset = explode( '=', $content_type[1] );
					$charset = ( ! empty( $charset[1] ) ) ? trim( $charset[1] ) : '';
				}
				$content_type = $content_type[0];
			}
			if ( preg_match( '/Content-Transfer-Encoding: /i', $line ) ) {
				$content_transfer_encoding = trim( $line );
				$content_transfer_encoding = substr( $content_transfer_encoding, 27, strlen( $content_transfer_encoding ) - 27 );
				$content_transfer_encoding = explode( ';', $content_transfer_encoding );
				$content_transfer_encoding = $content_transfer_encoding[0];
			}
			if ( 'multipart/alternative' === $content_type && str_contains( $line, 'boundary="' ) && '' === $boundary ) {
				$boundary = trim( $line );
				$boundary = explode( '"', $boundary );
				$boundary = $boundary[1];
			}
			if ( preg_match( '/Subject: /i', $line ) ) {
				$subject = trim( $line );
				$subject = substr( $subject, 9, strlen( $subject ) - 9 );
				// Bắt bất kỳ text nào trong subject trước $phone_delim như subject.
				if ( function_exists( 'iconv_mime_decode' ) ) {
					$subject = iconv_mime_decode( $subject, 2, get_option( 'blog_charset' ) );
				} else {
					$subject = wp_iso_descrambler( $subject );
				}
				$subject = explode( $phone_delim, $subject );
				$subject = $subject[0];
			}

			/*
			 * Thiết lập author sử dụng địa chỉ email (From hoặc Reply-To, cái được sử dụng cuối)
			 * nếu không thì sử dụng site admin.
			 */
			if ( ! $author_found && preg_match( '/^(From|Reply-To): /', $line ) ) {
				if ( preg_match( '|[a-z0-9_.-]+@[a-z0-9_.-]+(?!.*<)|i', $line, $matches ) ) {
					$author = $matches[0];
				} else {
					$author = trim( $line );
				}
				$author = sanitize_email( $author );
				if ( is_email( $author ) ) {
					$userdata = get_user_by( 'email', $author );
					if ( ! empty( $userdata ) ) {
						$post_author  = $userdata->ID;
						$author_found = true;
					}
				}
			}

			if ( preg_match( '/Date: /i', $line ) ) { // Có dạng '20 Mar 2002 20:32:37 +0100'.
				$ddate = str_replace( 'Date: ', '', trim( $line ) );
				// Xóa chuỗi timezone trong ngoặc đơn nếu tồn tại, vì điều này làm strtotime() bối rối.
				$ddate           = preg_replace( '!\s*\(.+\)\s*$!', '', $ddate );
				$ddate_timestamp = strtotime( $ddate );
				$post_date       = gmdate( 'Y-m-d H:i:s', $ddate_timestamp + $time_difference );
				$post_date_gmt   = gmdate( 'Y-m-d H:i:s', $ddate_timestamp );
			}
		}
	}

	// Thiết lập $post_status dựa trên $author_found và khả năng publish_posts của author.
	if ( $author_found ) {
		$user        = new WP_User( $post_author );
		$post_status = ( $user->has_cap( 'publish_posts' ) ) ? 'publish' : 'pending';
	} else {
		// Author không tìm thấy trong DB, thiết lập status thành pending. Author đã được thiết lập thành admin.
		$post_status = 'pending';
	}

	$subject = trim( $subject );

	if ( 'multipart/alternative' === $content_type ) {
		$content = explode( '--' . $boundary, $content );
		$content = $content[2];

		// Khớp Content-Transfer-Encoding không phân biệt hoa thường.
		if ( preg_match( '/Content-Transfer-Encoding: quoted-printable/i', $content, $delim ) ) {
			$content = explode( $delim[0], $content );
			$content = $content[1];
		}
		$content = strip_tags( $content, '<img><p><br><i><b><u><em><strong><strike><font><span><div>' );
	}
	$content = trim( $content );

	/**
	 * Filter nội dung gốc của email.
	 *
	 * Cung cấp cho các plugin mở rộng Post-By-Email quyền truy cập đầy đủ vào nội dung, hoặc
	 * nội dung thô, hoặc nội dung của phần quoted-printable cuối cùng.
	 *
	 * @since 2.8.0
	 *
	 * @param string $content Nội dung email gốc.
	 */
	$content = apply_filters( 'wp_mail_original_content', $content );

	if ( false !== stripos( $content_transfer_encoding, 'quoted-printable' ) ) {
		$content = quoted_printable_decode( $content );
	}

	if ( function_exists( 'iconv' ) && ! empty( $charset ) ) {
		$content = iconv( $charset, get_option( 'blog_charset' ), $content );
	}

	// Bắt bất kỳ text nào trong body sau $phone_delim như body.
	$content = explode( $phone_delim, $content );
	$content = empty( $content[1] ) ? $content[0] : $content[1];

	$content = trim( $content );

	/**
	 * Filter nội dung của post được gửi qua email trước khi lưu.
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Nội dung email.
	 */
	$post_content = apply_filters( 'phone_content', $content );

	$post_title = xmlrpc_getposttitle( $content );

	if ( '' === trim( $post_title ) ) {
		$post_title = $subject;
	}

	$post_category = array( get_option( 'default_email_category' ) );

	$post_data = compact( 'post_content', 'post_title', 'post_date', 'post_date_gmt', 'post_author', 'post_category', 'post_status' );
	$post_data = wp_slash( $post_data );

	$post_ID = wp_insert_post( $post_data );
	if ( is_wp_error( $post_ID ) ) {
		echo "\n" . $post_ID->get_error_message();
	}

	// Post không được chèn hoặc cập nhật, vì bất kỳ lý do gì. Tốt hơn là chuyển sang email tiếp theo.
	if ( empty( $post_ID ) ) {
		continue;
	}

	/**
	 * Kích hoạt sau khi post được gửi qua email được publish.
	 *
	 * @since 1.2.0
	 *
	 * @param int $post_ID ID của bài viết.
	 */
	do_action( 'publish_phone', $post_ID );

	echo "\n<p><strong>" . __( 'Author:' ) . '</strong> ' . esc_html( $post_author ) . '</p>';
	echo "\n<p><strong>" . __( 'Posted title:' ) . '</strong> ' . esc_html( $post_title ) . '</p>';

	if ( ! $pop3->delete( $i ) ) {
		echo '<p>' . sprintf(
			/* translators: %s: Lỗi POP3. */
			__( 'Oops: %s' ),
			esc_html( $pop3->ERROR )
		) . '</p>';
		$pop3->reset();
		exit;
	} else {
		echo '<p>' . sprintf(
			/* translators: %s: ID của tin nhắn. */
			__( 'Mission complete. Message %s deleted.' ),
			'<strong>' . $i . '</strong>'
		) . '</p>';
	}
}

$pop3->quit();
