<?php
/**
 * Một pseudo-cron daemon để lên lịch các tác vụ WordPress.
 *
 * WP-Cron được kích hoạt khi site nhận được một lượt truy cập. Trong trường hợp
 * site không nhận đủ lượt truy cập để thực thi các tác vụ đã lên lịch
 * kịp thời, file này có thể được gọi trực tiếp hoặc qua server
 * cron daemon với số lần X.
 *
 * Định nghĩa DISABLE_WP_CRON là true và gọi file này trực tiếp là
 * loại trừ lẫn nhau và cách sau không phụ thuộc vào cách trước để hoạt động.
 *
 * HTTP request đến file này sẽ không làm chậm người truy cập tình cờ
 * vào khi một cron event đã lên lịch đang chạy.
 *
 * @package WordPress
 */

ignore_user_abort( true );

if ( ! headers_sent() ) {
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
}

// Không chạy cron cho đến khi request kết thúc, nếu có thể.
if ( function_exists( 'fastcgi_finish_request' ) ) {
	fastcgi_finish_request();
} elseif ( function_exists( 'litespeed_finish_request' ) ) {
	litespeed_finish_request();
}

if ( ! empty( $_POST ) || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
	die();
}

/**
 * Báo cho WordPress biết tác vụ cron đang chạy.
 *
 * @var bool
 */
define( 'DOING_CRON', true );

if ( ! defined( 'ABSPATH' ) ) {
	/** Thiết lập môi trường WordPress */
	require_once __DIR__ . '/wp-load.php';
}

// Cố gắng tăng giới hạn bộ nhớ PHP cho xử lý sự kiện cron.
wp_raise_memory_limit( 'cron' );

/**
 * Lấy khóa cron.
 *
 * Trả về transient `doing_cron` không được cache.
 *
 * @ignore
 * @since 3.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @return string|int|false Giá trị của transient `doing_cron`, 0|false nếu ngược lại.
 */
function _get_cron_lock() {
	global $wpdb;

	$value = 0;
	if ( wp_using_ext_object_cache() ) {
		/*
		 * Skip local cache and force re-fetch of doing_cron transient
		 * in case another process updated the cache.
		 *
		 * Bỏ qua local cache và buộc fetch lại transient doing_cron
		 * trong trường hợp một process khác đã cập nhật cache.
		 */
		$value = wp_cache_get( 'doing_cron', 'transient', true );
	} else {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", '_transient_doing_cron' ) );
		if ( is_object( $row ) ) {
			$value = $row->option_value;
		}
	}

	return $value;
}

$crons = wp_get_ready_cron_jobs();
if ( empty( $crons ) ) {
	die();
}

$gmt_time = microtime( true );

// Khóa cron: một unix timestamp từ khi cron được khởi tạo.
$doing_cron_transient = get_transient( 'doing_cron' );

// Sử dụng khóa global $doing_wp_cron, nếu không thì sử dụng khóa GET. Nếu không có khóa, thử lấy khóa mới.
if ( empty( $doing_wp_cron ) ) {
	if ( empty( $_GET['doing_wp_cron'] ) ) {
		// Được gọi từ script/tác vụ bên ngoài. Thử thiết lập khóa.
		if ( $doing_cron_transient && ( $doing_cron_transient + WP_CRON_LOCK_TIMEOUT > $gmt_time ) ) {
			return;
		}
		$doing_wp_cron        = sprintf( '%.22F', microtime( true ) );
		$doing_cron_transient = $doing_wp_cron;
		set_transient( 'doing_cron', $doing_wp_cron );
	} else {
		$doing_wp_cron = $_GET['doing_wp_cron'];
	}
}

/*
 * Khóa cron (một unix timestamp được thiết lập khi cron được khởi tạo),
 * phải khớp với $doing_wp_cron ("chìa khóa").
 */
if ( $doing_cron_transient !== $doing_wp_cron ) {
	return;
}

foreach ( $crons as $timestamp => $cronhooks ) {
	if ( $timestamp > $gmt_time ) {
		break;
	}

	foreach ( $cronhooks as $hook => $keys ) {

		foreach ( $keys as $k => $v ) {

			$schedule = $v['schedule'];

			if ( $schedule ) {
				$result = wp_reschedule_event( $timestamp, $schedule, $hook, $v['args'], true );

				if ( is_wp_error( $result ) ) {
					error_log(
						sprintf(
							/* translators: 1: Hook name, 2: Error code, 3: Error message, 4: Event data. */
							__( 'Cron reschedule event error for hook: %1$s, Error code: %2$s, Error message: %3$s, Data: %4$s' ),
							$hook,
							$result->get_error_code(),
							$result->get_error_message(),
							wp_json_encode( $v )
						)
					);

					/**
					 * Kích hoạt nếu có lỗi khi lên lịch lại sự kiện cron.
					 *
					 * @since 6.1.0
					 *
					 * @param WP_Error $result Đối tượng WP_Error.
					 * @param string   $hook   Action hook để thực thi khi sự kiện chạy.
					 * @param array    $v      Dữ liệu sự kiện.
					 */
					do_action( 'cron_reschedule_event_error', $result, $hook, $v );
				}
			}

			$result = wp_unschedule_event( $timestamp, $hook, $v['args'], true );

			if ( is_wp_error( $result ) ) {
				error_log(
					sprintf(
						/* translators: 1: Hook name, 2: Error code, 3: Error message, 4: Event data. */
						__( 'Cron unschedule event error for hook: %1$s, Error code: %2$s, Error message: %3$s, Data: %4$s' ),
						$hook,
						$result->get_error_code(),
						$result->get_error_message(),
						wp_json_encode( $v )
					)
				);

				/**
				 * Kích hoạt nếu có lỗi khi hủy lịch sự kiện cron.
				 *
				 * @since 6.1.0
				 *
				 * @param WP_Error $result Đối tượng WP_Error.
				 * @param string   $hook   Action hook để thực thi khi sự kiện chạy.
				 * @param array    $v      Dữ liệu sự kiện.
				 */
				do_action( 'cron_unschedule_event_error', $result, $hook, $v );
			}

			/**
			 * Kích hoạt các sự kiện đã lên lịch.
			 *
			 * @ignore
			 * @since 2.1.0
			 *
			 * @param string $hook Tên hook được lên lịch để kích hoạt.
			 * @param array  $args Các tham số được truyền vào hook.
			 */
			do_action_ref_array( $hook, $v['args'] );

			// Nếu hook chạy quá lâu và một tiến trình cron khác đã lấy khóa, thoát.
			if ( _get_cron_lock() !== $doing_wp_cron ) {
				return;
			}
		}
	}
}

if ( _get_cron_lock() === $doing_wp_cron ) {
	delete_transient( 'doing_cron' );
}

die();
