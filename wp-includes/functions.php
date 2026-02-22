<?php
/**
 * API chính của WordPress
 *
 * @package WordPress
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require ABSPATH . WPINC . '/option.php';

/**
 * Chuyển đổi chuỗi ngày MySQL sang định dạng khác.
 *
 *  - `$format` phải là chuỗi định dạng ngày PHP.
 *  - Định dạng 'U' và 'G' sẽ trả về tổng số nguyên của timestamp với độ lệch múi giờ.
 *  - `$date` được mong đợi là giờ địa phương theo định dạng MySQL (`Y-m-d H:i:s`).
 *
 * Trước đây, giờ UTC có thể được truyền vào hàm để tạo Unix timestamp.
 *
 * Nếu `$translate` là true thì ngày và chuỗi định dạng đã cho sẽ
 * được truyền tới `wp_date()` để dịch.
 *
 * @since 0.71
 *
 * @param string $format    Định dạng của ngày trả về.
 * @param string $date      Chuỗi ngày cần chuyển đổi.
 * @param bool   $translate Có nên dịch ngày trả về hay không. Mặc định true.
 * @return string|int|false Số nguyên nếu `$format` là 'U' hoặc 'G', chuỗi trong các trường hợp khác.
 *                          False khi thất bại.
 */
function mysql2date( $format, $date, $translate = true ) {
	if ( empty( $date ) ) {
		return false;
	}

	$timezone = wp_timezone();
	$datetime = date_create( $date, $timezone );

	if ( false === $datetime ) {
		return false;
	}

	// Trả về tổng của timestamp với độ lệch múi giờ. Lý tưởng là không nên sử dụng.
	if ( 'G' === $format || 'U' === $format ) {
		return $datetime->getTimestamp() + $datetime->getOffset();
	}

	if ( $translate ) {
		return wp_date( $format, $datetime->getTimestamp(), $timezone );
	}

	return $datetime->format( $format );
}

/**
 * Lấy thời gian hiện tại dựa trên loại được chỉ định.
 *
 *  - Loại 'mysql' sẽ trả về thời gian theo định dạng cho trường MySQL DATETIME.
 *  - Loại 'timestamp' hoặc 'U' sẽ trả về timestamp hiện tại hoặc tổng của timestamp
 *    và độ lệch múi giờ, tùy thuộc vào `$gmt`.
 *  - Các chuỗi khác sẽ được hiểu là định dạng ngày PHP (ví dụ: 'Y-m-d').
 *
 * Nếu `$gmt` là giá trị truthy thì cả hai loại sẽ sử dụng giờ GMT, nếu không
 * đầu ra sẽ được điều chỉnh với độ lệch GMT của trang web.
 *
 * @since 1.0.0
 * @since 5.3.0 Bây giờ trả về số nguyên nếu `$type` là 'U'. Trước đây trả về chuỗi.
 *
 * @param string   $type Loại thời gian cần lấy. Chấp nhận 'mysql', 'timestamp', 'U',
 *                       hoặc chuỗi định dạng ngày PHP (ví dụ: 'Y-m-d').
 * @param int|bool $gmt  Tùy chọn. Có sử dụng múi giờ GMT hay không. Mặc định false.
 * @return int|string Số nguyên nếu `$type` là 'timestamp' hoặc 'U', chuỗi trong các trường hợp khác.
 */
function current_time( $type, $gmt = 0 ) {
	// Không sử dụng timestamp không phải GMT, trừ khi bạn hiểu sự khác biệt và thực sự cần.
	if ( 'timestamp' === $type || 'U' === $type ) {
		return $gmt ? time() : time() + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}

	if ( 'mysql' === $type ) {
		$type = 'Y-m-d H:i:s';
	}

	$timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();
	$datetime = new DateTime( 'now', $timezone );

	return $datetime->format( $type );
}

/**
 * Lấy thời gian hiện tại dưới dạng đối tượng sử dụng múi giờ của trang web.
 *
 * @since 5.3.0
 *
 * @return DateTimeImmutable Đối tượng ngày và giờ.
 */
function current_datetime() {
	return new DateTimeImmutable( 'now', wp_timezone() );
}

/**
 * Lấy múi giờ của trang web dưới dạng chuỗi.
 *
 * Sử dụng tùy chọn `timezone_string` để lấy tên múi giờ phù hợp nếu có,
 * nếu không sẽ dùng độ lệch UTC ± thủ công.
 *
 * Ví dụ giá trị trả về:
 *
 *  - 'Europe/Rome'
 *  - 'America/North_Dakota/New_Salem'
 *  - 'UTC'
 *  - '-06:30'
 *  - '+00:00'
 *  - '+08:45'
 *
 * @since 5.3.0
 *
 * @return string Tên múi giờ PHP hoặc độ lệch ±HH:MM.
 */
function wp_timezone_string() {
	$timezone_string = get_option( 'timezone_string' );

	if ( $timezone_string ) {
		return $timezone_string;
	}

	$offset  = (float) get_option( 'gmt_offset' );
	$hours   = (int) $offset;
	$minutes = ( $offset - $hours );

	$sign      = ( $offset < 0 ) ? '-' : '+';
	$abs_hour  = abs( $hours );
	$abs_mins  = abs( $minutes * 60 );
	$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

	return $tz_offset;
}

/**
 * Lấy múi giờ của trang web dưới dạng đối tượng `DateTimeZone`.
 *
 * Múi giờ có thể dựa trên chuỗi múi giờ PHP hoặc độ lệch ±HH:MM.
 *
 * @since 5.3.0
 *
 * @return DateTimeZone Đối tượng múi giờ.
 */
function wp_timezone() {
	return new DateTimeZone( wp_timezone_string() );
}

/**
 * Lấy ngày theo định dạng bản địa hóa, dựa trên tổng của Unix timestamp và
 * độ lệch múi giờ tính bằng giây.
 *
 * Nếu locale chỉ định tháng và ngày trong tuần theo locale, thì locale sẽ
 * đảm nhận định dạng cho ngày. Nếu không, chuỗi định dạng ngày
 * sẽ được sử dụng thay thế.
 *
 * Lưu ý rằng do cách WP thường tạo tổng của timestamp và độ lệch
 * bằng `strtotime()`, nó ngụ ý độ lệch được thêm vào thời điểm _hiện tại_, không phải thời điểm
 * mà timestamp đại diện. Lưu trữ các timestamp như vậy hoặc tính toán chúng khác đi
 * sẽ dẫn đến đầu ra không hợp lệ.
 *
 * @since 0.71
 * @since 5.3.0 Được chuyển thành wrapper cho wp_date().
 *
 * @param string   $format                Định dạng hiển thị ngày.
 * @param int|bool $timestamp_with_offset Tùy chọn. Tổng của Unix timestamp và độ lệch múi giờ
 *                                        tính bằng giây. Mặc định false.
 * @param bool     $gmt                   Tùy chọn. Có sử dụng múi giờ GMT hay không. Chỉ áp dụng
 *                                        nếu timestamp không được cung cấp. Mặc định false.
 * @return string Ngày, được dịch nếu locale chỉ định.
 */
function date_i18n( $format, $timestamp_with_offset = false, $gmt = false ) {
	$timestamp = $timestamp_with_offset;

	// Nếu timestamp bị bỏ qua thì nó phải là thời gian hiện tại (cộng với độ lệch, trừ khi `$gmt` là true).
	if ( ! is_numeric( $timestamp ) ) {
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$timestamp = current_time( 'timestamp', $gmt );
	}

	/*
	 * Đây là một đặc điểm triển khai kế thừa rằng timestamp trả về cũng bao gồm độ lệch.
	 * Lý tưởng thì hàm này không bao giờ nên được sử dụng để tạo timestamp.
	 */
	if ( 'U' === $format ) {
		$date = $timestamp;
	} elseif ( $gmt && false === $timestamp_with_offset ) { // Thời gian hiện tại theo UTC.
		$date = wp_date( $format, null, new DateTimeZone( 'UTC' ) );
	} elseif ( false === $timestamp_with_offset ) { // Thời gian hiện tại theo múi giờ của trang web.
		$date = wp_date( $format );
	} else {
		/*
		 * Timestamp có độ lệch thường được tạo bởi lệnh gọi `strtotime()` UTC trên đầu vào không có múi giờ.
		 * Đây là nỗ lực tốt nhất để đảo ngược thao tác đó thành giờ địa phương để sử dụng.
		 */
		$local_time = gmdate( 'Y-m-d H:i:s', $timestamp );
		$timezone   = wp_timezone();
		$datetime   = date_create( $local_time, $timezone );
		$date       = wp_date( $format, $datetime->getTimestamp(), $timezone );
	}

	/**
	 * Lọc ngày được định dạng dựa trên locale.
	 *
	 * @since 2.8.0
	 *
	 * @param string $date      Chuỗi ngày đã được định dạng.
	 * @param string $format    Định dạng hiển thị ngày.
	 * @param int    $timestamp Tổng của Unix timestamp và độ lệch múi giờ tính bằng giây.
	 *                          Có thể không có độ lệch nếu đầu vào bỏ qua timestamp nhưng yêu cầu GMT.
	 * @param bool   $gmt       Có sử dụng múi giờ GMT hay không. Chỉ áp dụng nếu timestamp không được cung cấp.
	 *                          Mặc định false.
	 */
	$date = apply_filters( 'date_i18n', $date, $format, $timestamp, $gmt );

	return $date;
}

/**
 * Lấy ngày theo định dạng bản địa hóa.
 *
 * Đây là hàm mới hơn, nhằm thay thế `date_i18n()` mà không có các đặc điểm kế thừa.
 *
 * Lưu ý rằng, khác với `date_i18n()`, hàm này chấp nhận Unix timestamp thực sự, không cộng
 * với độ lệch múi giờ.
 *
 * @since 5.3.0
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ của WordPress.
 *
 * @param string       $format    Định dạng ngày PHP.
 * @param int          $timestamp Tùy chọn. Unix timestamp. Mặc định là thời gian hiện tại.
 * @param DateTimeZone $timezone  Tùy chọn. Múi giờ để xuất kết quả. Mặc định là múi giờ
 *                                từ cài đặt trang web.
 * @return string|false Ngày, được dịch nếu locale chỉ định. False nếu đầu vào timestamp không hợp lệ.
 */
function wp_date( $format, $timestamp = null, $timezone = null ) {
	global $wp_locale;

	if ( null === $timestamp ) {
		$timestamp = time();
	} elseif ( ! is_numeric( $timestamp ) ) {
		return false;
	}

	if ( ! $timezone ) {
		$timezone = wp_timezone();
	}

	$datetime = date_create( '@' . $timestamp );
	$datetime->setTimezone( $timezone );

	if ( empty( $wp_locale->month ) || empty( $wp_locale->weekday ) ) {
		$date = $datetime->format( $format );
	} else {
		// Chúng ta cần giải nén định dạng viết tắt `r` vì nó có các phần có thể được bản địa hóa.
		$format = preg_replace( '/(?<!\\\\)r/', DATE_RFC2822, $format );

		$new_format    = '';
		$format_length = strlen( $format );
		$month         = $wp_locale->get_month( $datetime->format( 'm' ) );
		$weekday       = $wp_locale->get_weekday( $datetime->format( 'w' ) );

		for ( $i = 0; $i < $format_length; $i++ ) {
			switch ( $format[ $i ] ) {
				case 'D':
					$new_format .= addcslashes( $wp_locale->get_weekday_abbrev( $weekday ), '\\A..Za..z' );
					break;
				case 'F':
					$new_format .= addcslashes( $month, '\\A..Za..z' );
					break;
				case 'l':
					$new_format .= addcslashes( $weekday, '\\A..Za..z' );
					break;
				case 'M':
					$new_format .= addcslashes( $wp_locale->get_month_abbrev( $month ), '\\A..Za..z' );
					break;
				case 'a':
					$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'a' ) ), '\\A..Za..z' );
					break;
				case 'A':
					$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'A' ) ), '\\A..Za..z' );
					break;
				case '\\':
					$new_format .= $format[ $i ];

					// Nếu ký tự theo sau dấu gạch chéo, chúng ta thêm nó mà không dịch.
					if ( $i < $format_length ) {
						$new_format .= $format[ ++$i ];
					}
					break;
				default:
					$new_format .= $format[ $i ];
					break;
			}
		}

		$date = $datetime->format( $new_format );
		$date = wp_maybe_decline_date( $date, $format );
	}

	/**
	 * Lọc ngày được định dạng dựa trên locale.
	 *
	 * @since 5.3.0
	 *
	 * @param string       $date      Chuỗi ngày đã được định dạng.
	 * @param string       $format    Định dạng hiển thị ngày.
	 * @param int          $timestamp Unix timestamp.
	 * @param DateTimeZone $timezone  Múi giờ.
	 */
	$date = apply_filters( 'wp_date', $date, $format, $timestamp, $timezone );

	return $date;
}

/**
 * Xác định xem ngày có nên được chia theo cách (biến cách) hay không.
 *
 * Nếu locale chỉ định rằng tên tháng yêu cầu cách sở hữu trong một số
 * định dạng nhất định (như 'j F Y'), tên tháng sẽ được thay thế bằng dạng đúng.
 *
 * @since 4.4.0
 * @since 5.4.0 Tham số `$format` được thêm vào.
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ của WordPress.
 *
 * @param string $date   Chuỗi ngày đã được định dạng.
 * @param string $format Tùy chọn. Định dạng ngày để kiểm tra. Mặc định chuỗi rỗng.
 * @return string Ngày, được biến cách nếu locale chỉ định.
 */
function wp_maybe_decline_date( $date, $format = '' ) {
	global $wp_locale;

	// Các hàm i18n không khả dụng trong chế độ SHORTINIT.
	if ( ! function_exists( '_x' ) ) {
		return $date;
	}

	/*
	 * translators: If months in your language require a genitive case,
	 * translate this to 'on'. Do not translate into your own language.
	 */
	if ( 'on' === _x( 'off', 'decline months names: on or off' ) ) {

		$months          = $wp_locale->month;
		$months_genitive = $wp_locale->month_genitive;

		/*
		 * Khớp định dạng như 'j F Y' hoặc 'j. F' (ngày trong tháng, theo sau là tên tháng)
		 * và biến cách tên tháng.
		 */
		if ( $format ) {
			$decline = preg_match( '#[dj]\.? F#', $format );
		} else {
			// Nếu định dạng không được truyền, thử đoán từ chuỗi ngày.
			$decline = preg_match( '#\b\d{1,2}\.? [^\d ]+\b#u', $date );
		}

		if ( $decline ) {
			foreach ( $months as $key => $month ) {
				$months[ $key ] = '# ' . preg_quote( $month, '#' ) . '\b#u';
			}

			foreach ( $months_genitive as $key => $month ) {
				$months_genitive[ $key ] = ' ' . $month;
			}

			$date = preg_replace( $months, $months_genitive, $date );
		}

		/*
		 * Khớp định dạng như 'F jS' hoặc 'F j' (tên tháng, theo sau là ngày với hậu tố thứ tự tùy chọn)
		 * và đổi thành dạng biến cách 'j F'.
		 */
		if ( $format ) {
			$decline = preg_match( '#F [dj]#', $format );
		} else {
			// Nếu định dạng không được truyền, thử đoán từ chuỗi ngày.
			$decline = preg_match( '#\b[^\d ]+ \d{1,2}(st|nd|rd|th)?\b#u', trim( $date ) );
		}

		if ( $decline ) {
			foreach ( $months as $key => $month ) {
				$months[ $key ] = '#\b' . preg_quote( $month, '#' ) . ' (\d{1,2})(st|nd|rd|th)?([-–]\d{1,2})?(st|nd|rd|th)?\b#u';
			}

			foreach ( $months_genitive as $key => $month ) {
				$months_genitive[ $key ] = '$1$3 ' . $month;
			}

			$date = preg_replace( $months, $months_genitive, $date );
		}
	}

	// Được sử dụng cho các quy tắc dành riêng cho locale.
	$locale = get_locale();

	if ( 'ca' === $locale ) {
		// " de abril| de agost| de octubre..." -> " d'abril| d'agost| d'octubre..."
		$date = preg_replace( '# de ([ao])#i', " d'\\1", $date );
	}

	return $date;
}

/**
 * Chuyển đổi số thực sang định dạng dựa trên locale.
 *
 * @since 2.3.0
 *
 * @global WP_Locale $wp_locale Đối tượng locale ngày và giờ của WordPress.
 *
 * @param float $number   Số cần chuyển đổi dựa trên locale.
 * @param int   $decimals Tùy chọn. Độ chính xác của số chữ số thập phân. Mặc định 0.
 * @return string Số đã chuyển đổi ở định dạng chuỗi.
 */
function number_format_i18n( $number, $decimals = 0 ) {
	global $wp_locale;

	if ( isset( $wp_locale ) ) {
		$formatted = number_format( $number, absint( $decimals ), $wp_locale->number_format['decimal_point'], $wp_locale->number_format['thousands_sep'] );
	} else {
		$formatted = number_format( $number, absint( $decimals ) );
	}

	/**
	 * Lọc số được định dạng dựa trên locale.
	 *
	 * @since 2.8.0
	 * @since 4.9.0 Các tham số `$number` và `$decimals` được thêm vào.
	 *
	 * @param string $formatted Số đã chuyển đổi ở định dạng chuỗi.
	 * @param float  $number    Số cần chuyển đổi dựa trên locale.
	 * @param int    $decimals  Độ chính xác của số chữ số thập phân.
	 */
	return apply_filters( 'number_format_i18n', $formatted, $number, $decimals );
}

/**
 * Chuyển đổi số byte sang đơn vị lớn nhất mà số byte có thể vừa.
 *
 * Đọc 1 KB dễ hơn 1024 byte và 1 MB dễ hơn 1048576 byte. Chuyển đổi
 * số byte sang số đọc được bằng cách lấy số đơn vị mà byte
 * có thể chứa vừa. Hỗ trợ giá trị YB.
 *
 * Xin lưu ý rằng số nguyên trong PHP bị giới hạn ở 32 bit, trừ khi chúng ở
 * kiến trúc 64 bit, thì chúng có kích thước 64 bit. Nếu bạn cần đặt
 * kích thước lớn hơn những gì kiểu số nguyên PHP có thể chứa, hãy sử dụng chuỗi. Nó sẽ
 * được chuyển đổi sang double, luôn có độ dài 64 bit.
 *
 * Về mặt kỹ thuật, tên đơn vị đúng cho lũy thừa của 1024 là KiB, MiB v.v.
 *
 * @since 2.3.0
 * @since 6.0.0 Hỗ trợ cho PB, EB, ZB và YB đã được thêm.
 *
 * @param int|string $bytes    Số byte. Lưu ý kích thước số nguyên tối đa cho số nguyên.
 * @param int        $decimals Tùy chọn. Độ chính xác của số chữ số thập phân. Mặc định 0.
 * @return string|false Chuỗi số khi thành công, false khi thất bại.
 */
function size_format( $bytes, $decimals = 0 ) {
	$quant = array(
		/* translators: Unit symbol for yottabyte. */
		_x( 'YB', 'unit symbol' ) => YB_IN_BYTES,
		/* translators: Unit symbol for zettabyte. */
		_x( 'ZB', 'unit symbol' ) => ZB_IN_BYTES,
		/* translators: Unit symbol for exabyte. */
		_x( 'EB', 'unit symbol' ) => EB_IN_BYTES,
		/* translators: Unit symbol for petabyte. */
		_x( 'PB', 'unit symbol' ) => PB_IN_BYTES,
		/* translators: Unit symbol for terabyte. */
		_x( 'TB', 'unit symbol' ) => TB_IN_BYTES,
		/* translators: Unit symbol for gigabyte. */
		_x( 'GB', 'unit symbol' ) => GB_IN_BYTES,
		/* translators: Unit symbol for megabyte. */
		_x( 'MB', 'unit symbol' ) => MB_IN_BYTES,
		/* translators: Unit symbol for kilobyte. */
		_x( 'KB', 'unit symbol' ) => KB_IN_BYTES,
		/* translators: Unit symbol for byte. */
		_x( 'B', 'unit symbol' )  => 1,
	);

	if ( 0 === $bytes ) {
		/* translators: Unit symbol for byte. */
		return number_format_i18n( 0, $decimals ) . ' ' . _x( 'B', 'unit symbol' );
	}

	foreach ( $quant as $unit => $mag ) {
		if ( (float) $bytes >= $mag ) {
			return number_format_i18n( $bytes / $mag, $decimals ) . ' ' . $unit;
		}
	}

	return false;
}

/**
 * Chuyển đổi khoảng thời gian sang định dạng đọc được.
 *
 * @since 5.1.0
 *
 * @param string $duration Khoảng thời gian sẽ ở định dạng chuỗi (HH:ii:ss) HOẶC (ii:ss),
 *                         với dấu âm (-) có thể đứng trước.
 * @return string|false Chuỗi khoảng thời gian đọc được, false khi thất bại.
 */
function human_readable_duration( $duration = '' ) {
	if ( ( empty( $duration ) || ! is_string( $duration ) ) ) {
		return false;
	}

	$duration = trim( $duration );

	// Xóa dấu âm đứng trước.
	if ( str_starts_with( $duration, '-' ) ) {
		$duration = substr( $duration, 1 );
	}

	// Trích xuất các phần của khoảng thời gian.
	$duration_parts = array_reverse( explode( ':', $duration ) );
	$duration_count = count( $duration_parts );

	$hour   = null;
	$minute = null;
	$second = null;

	if ( 3 === $duration_count ) {
		// Xác thực định dạng khoảng thời gian HH:ii:ss.
		if ( ! ( (bool) preg_match( '/^([0-9]+):([0-5]?[0-9]):([0-5]?[0-9])$/', $duration ) ) ) {
			return false;
		}
		// Ba phần: giờ, phút và giây.
		list( $second, $minute, $hour ) = $duration_parts;
	} elseif ( 2 === $duration_count ) {
		// Xác thực định dạng khoảng thời gian ii:ss.
		if ( ! ( (bool) preg_match( '/^([0-5]?[0-9]):([0-5]?[0-9])$/', $duration ) ) ) {
			return false;
		}
		// Hai phần: phút và giây.
		list( $second, $minute ) = $duration_parts;
	} else {
		return false;
	}

	$human_readable_duration = array();

	// Thêm phần giờ vào chuỗi.
	if ( is_numeric( $hour ) ) {
		/* translators: %s: Time duration in hour or hours. */
		$human_readable_duration[] = sprintf( _n( '%s hour', '%s hours', $hour ), (int) $hour );
	}

	// Thêm phần phút vào chuỗi.
	if ( is_numeric( $minute ) ) {
		/* translators: %s: Time duration in minute or minutes. */
		$human_readable_duration[] = sprintf( _n( '%s minute', '%s minutes', $minute ), (int) $minute );
	}

	// Thêm phần giây vào chuỗi.
	if ( is_numeric( $second ) ) {
		/* translators: %s: Time duration in second or seconds. */
		$human_readable_duration[] = sprintf( _n( '%s second', '%s seconds', $second ), (int) $second );
	}

	return implode( ', ', $human_readable_duration );
}

/**
 * Lấy ngày bắt đầu và kết thúc tuần từ chuỗi datetime hoặc date của MySQL.
 *
 * @since 0.71
 *
 * @param string     $mysqlstring   Kiểu trường date hoặc datetime từ MySQL.
 * @param int|string $start_of_week Tùy chọn. Ngày bắt đầu tuần dưới dạng số nguyên. Mặc định chuỗi rỗng.
 * @return int[] {
 *     Ngày bắt đầu và kết thúc tuần dưới dạng Unix timestamp.
 *
 *     @type int $start Ngày bắt đầu tuần dưới dạng Unix timestamp.
 *     @type int $end   Ngày kết thúc tuần dưới dạng Unix timestamp.
 * }
 */
function get_weekstartend( $mysqlstring, $start_of_week = '' ) {
	// Năm từ chuỗi MySQL.
	$my = substr( $mysqlstring, 0, 4 );

	// Tháng từ chuỗi MySQL.
	$mm = substr( $mysqlstring, 8, 2 );

	// Ngày từ chuỗi MySQL.
	$md = substr( $mysqlstring, 5, 2 );

	// Timestamp cho ngày từ chuỗi MySQL.
	$day = mktime( 0, 0, 0, $md, $mm, $my );

	// Ngày trong tuần từ timestamp.
	$weekday = (int) gmdate( 'w', $day );

	if ( ! is_numeric( $start_of_week ) ) {
		$start_of_week = (int) get_option( 'start_of_week' );
	}

	if ( $weekday < $start_of_week ) {
		$weekday += 7;
	}

	// Ngày bắt đầu tuần gần nhất vào hoặc trước $day.
	$start = $day - DAY_IN_SECONDS * ( $weekday - $start_of_week );

	// $start + 1 tuần - 1 giây.
	$end = $start + WEEK_IN_SECONDS - 1;

	return compact( 'start', 'end' );
}

/**
 * Tuần tự hóa dữ liệu, nếu cần.
 *
 * @since 2.0.5
 *
 * @param string|array|object $data Dữ liệu có thể cần được tuần tự hóa.
 * @return mixed Dữ liệu vô hướng.
 */
function maybe_serialize( $data ) {
	if ( is_array( $data ) || is_object( $data ) ) {
		return serialize( $data );
	}

	/*
	 * Tuần tự hóa kép là bắt buộc để tương thích ngược.
	 * Xem https://core.trac.wordpress.org/ticket/12930
	 * Và thế giới sẽ kết thúc. Xem WP 3.6.1.
	 */
	if ( is_serialized( $data, false ) ) {
		return serialize( $data );
	}

	return $data;
}

/**
 * Giải tuần tự hóa dữ liệu chỉ khi nó đã được tuần tự hóa.
 *
 * @since 2.0.0
 *
 * @param string $data Dữ liệu có thể cần được giải tuần tự hóa.
 * @return mixed Dữ liệu đã giải tuần tự hóa có thể là bất kỳ kiểu nào.
 */
function maybe_unserialize( $data ) {
	if ( is_serialized( $data ) ) { // Không cố giải tuần tự hóa dữ liệu chưa được tuần tự hóa.
		return @unserialize( trim( $data ) );
	}

	return $data;
}

/**
 * Kiểm tra giá trị để xác định xem nó đã được tuần tự hóa hay chưa.
 *
 * Nếu $data không phải là chuỗi, giá trị trả về luôn là false.
 * Dữ liệu tuần tự hóa luôn là một chuỗi.
 *
 * @since 2.0.5
 * @since 6.1.0 Thêm hỗ trợ Enum.
 *
 * @param string $data   Giá trị cần kiểm tra xem đã được tuần tự hóa chưa.
 * @param bool   $strict Tùy chọn. Có nghiêm ngặt về cuối chuỗi hay không. Mặc định true.
 * @return bool False nếu chưa được tuần tự hóa và true nếu đã được.
 */
function is_serialized( $data, $strict = true ) {
	// Nếu không phải chuỗi, nó không được tuần tự hóa.
	if ( ! is_string( $data ) ) {
		return false;
	}
	$data = trim( $data );
	if ( 'N;' === $data ) {
		return true;
	}
	if ( strlen( $data ) < 4 ) {
		return false;
	}
	if ( ':' !== $data[1] ) {
		return false;
	}
	if ( $strict ) {
		$lastc = substr( $data, -1 );
		if ( ';' !== $lastc && '}' !== $lastc ) {
			return false;
		}
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );
		// Phải tồn tại ; hoặc }.
		if ( false === $semicolon && false === $brace ) {
			return false;
		}
		// Nhưng không được ở trong X ký tự đầu tiên.
		if ( false !== $semicolon && $semicolon < 3 ) {
			return false;
		}
		if ( false !== $brace && $brace < 4 ) {
			return false;
		}
	}
	$token = $data[0];
	switch ( $token ) {
		case 's':
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return false;
				}
			} elseif ( ! str_contains( $data, '"' ) ) {
				return false;
			}
			// Hoặc rơi xuống case tiếp theo.
		case 'a':
		case 'O':
		case 'E':
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
	}
	return false;
}

/**
 * Kiểm tra xem dữ liệu tuần tự hóa có phải kiểu chuỗi hay không.
 *
 * @since 2.0.5
 *
 * @param string $data Dữ liệu đã tuần tự hóa.
 * @return bool False nếu không phải chuỗi tuần tự hóa, true nếu đúng.
 */
function is_serialized_string( $data ) {
	// Nếu không phải chuỗi, nó không phải chuỗi tuần tự hóa.
	if ( ! is_string( $data ) ) {
		return false;
	}
	$data = trim( $data );
	if ( strlen( $data ) < 4 ) {
		return false;
	} elseif ( ':' !== $data[1] ) {
		return false;
	} elseif ( ! str_ends_with( $data, ';' ) ) {
		return false;
	} elseif ( 's' !== $data[0] ) {
		return false;
	} elseif ( '"' !== substr( $data, -2, 1 ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Lấy tiêu đề bài viết từ XML XMLRPC.
 *
 * Nếu phần tử title không có trong XML, tiêu đề bài viết mặc định từ
 * $post_default_title sẽ được sử dụng thay thế.
 *
 * @since 0.71
 *
 * @global string $post_default_title Tiêu đề bài viết XML-RPC mặc định.
 *
 * @param string $content Nội dung yêu cầu XML XMLRPC.
 * @return string Tiêu đề bài viết.
 */
function xmlrpc_getposttitle( $content ) {
	global $post_default_title;
	if ( preg_match( '/<title>(.+?)<\/title>/is', $content, $matchtitle ) ) {
		$post_title = $matchtitle[1];
	} else {
		$post_title = $post_default_title;
	}
	return $post_title;
}

/**
 * Lấy chuyên mục hoặc các chuyên mục bài viết từ XML XMLRPC.
 *
 * Nếu phần tử category không được tìm thấy, chuyên mục bài viết mặc định sẽ được
 * sử dụng. Kiểu trả về sẽ là giá trị của $post_default_category. Nếu chuyên mục
 * được tìm thấy, nó sẽ luôn là một mảng.
 *
 * @since 0.71
 *
 * @global string $post_default_category Chuyên mục bài viết XML-RPC mặc định.
 *
 * @param string $content Nội dung yêu cầu XML XMLRPC.
 * @return string|array Danh sách chuyên mục hoặc tên chuyên mục.
 */
function xmlrpc_getpostcategory( $content ) {
	global $post_default_category;
	if ( preg_match( '/<category>(.+?)<\/category>/is', $content, $matchcat ) ) {
		$post_category = trim( $matchcat[1], ',' );
		$post_category = explode( ',', $post_category );
	} else {
		$post_category = $post_default_category;
	}
	return $post_category;
}

/**
 * Nội dung XML XMLRPC không có phần tử title và category.
 *
 * @since 0.71
 *
 * @param string $content Nội dung yêu cầu XML XML-RPC.
 * @return string Nội dung yêu cầu XML XMLRPC không có phần tử title và category.
 */
function xmlrpc_removepostdata( $content ) {
	$content = preg_replace( '/<title>(.+?)<\/title>/si', '', $content );
	$content = preg_replace( '/<category>(.+?)<\/category>/si', '', $content );
	$content = trim( $content );
	return $content;
}

/**
 * Sử dụng RegEx để trích xuất URL từ nội dung bất kỳ.
 *
 * @since 3.7.0
 * @since 6.0.0 Sửa hỗ trợ cho thực thể HTML (Trac 30580).
 *
 * @param string $content Nội dung để trích xuất URL.
 * @return string[] Mảng các URL tìm thấy trong chuỗi đã truyền.
 */
function wp_extract_urls( $content ) {
	preg_match_all(
		"#([\"']?)("
			. '(?:([\w-]+:)?//?)'
			. '[^\s()<>]+'
			. '[.]'
			. '(?:'
				. '\([\w\d]+\)|'
				. '(?:'
					. "[^`!()\[\]{}:'\".,<>«»“”‘’\s]|"
					. '(?:[:]\d+)?/?'
				. ')+'
			. ')'
		. ")\\1#",
		$content,
		$post_links
	);

	$post_links = array_unique(
		array_map(
			static function ( $link ) {
				// Giải mã để thay thế các thực thể hợp lệ, như &amp;.
				$link = html_entity_decode( $link );
				// Duy trì tương thích ngược bằng cách xóa dấu chấm phẩy thừa (`;`).
				return str_replace( ';', '', $link );
			},
			$post_links[2]
		)
	);

	return array_values( $post_links );
}

/**
 * Kiểm tra nội dung để tìm liên kết video và audio để thêm dưới dạng enclosure.
 *
 * Sẽ không thêm enclosure đã được thêm trước đó và sẽ
 * xóa enclosure không còn trong bài viết. Được gọi như
 * pingback và trackback.
 *
 * @since 1.5.0
 * @since 5.3.0 Tham số `$content` được đặt thành tùy chọn, và tham số `$post` được
 *              cập nhật để chấp nhận ID bài viết hoặc đối tượng WP_Post.
 * @since 5.6.0 Tham số `$content` không còn là tùy chọn, nhưng truyền `null` để bỏ qua
 *              vẫn được hỗ trợ.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string|null $content Nội dung bài viết. Nếu `null`, trường `post_content` từ `$post` được sử dụng.
 * @param int|WP_Post $post    ID bài viết hoặc đối tượng bài viết.
 * @return void|false Void khi thành công, false nếu không tìm thấy bài viết.
 */
function do_enclose( $content, $post ) {
	global $wpdb;

	// @todo Dọn dẹp đoạn mã này và làm cho mã debug trở thành tùy chọn.
	require_once ABSPATH . WPINC . '/class-IXR.php';

	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}

	if ( null === $content ) {
		$content = $post->post_content;
	}

	$post_links = array();

	$pung = get_enclosed( $post->ID );

	$post_links_temp = wp_extract_urls( $content );

	foreach ( $pung as $link_test ) {
		// Liên kết không còn trong bài viết.
		if ( ! in_array( $link_test, $post_links_temp, true ) ) {
			$mids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE %s", $post->ID, $wpdb->esc_like( $link_test ) . '%' ) );
			foreach ( $mids as $mid ) {
				delete_metadata_by_mid( 'post', $mid );
			}
		}
	}

	foreach ( (array) $post_links_temp as $link_test ) {
		// Nếu chúng ta chưa ping nó.
		if ( ! in_array( $link_test, $pung, true ) ) {
			$test = parse_url( $link_test );
			if ( false === $test ) {
				continue;
			}
			if ( isset( $test['query'] ) ) {
				$post_links[] = $link_test;
			} elseif ( isset( $test['path'] ) && ( '/' !== $test['path'] ) && ( '' !== $test['path'] ) ) {
				$post_links[] = $link_test;
			}
		}
	}

	/**
	 * Lọc danh sách liên kết enclosure trước khi truy vấn cơ sở dữ liệu.
	 *
	 * Cho phép thêm và/hoặc xóa các enclosure tiềm năng để lưu
	 * vào postmeta trước khi kiểm tra cơ sở dữ liệu cho các enclosure hiện có.
	 *
	 * @since 4.4.0
	 *
	 * @param string[] $post_links Mảng các liên kết enclosure.
	 * @param int      $post_id    ID bài viết.
	 */
	$post_links = apply_filters( 'enclosure_links', $post_links, $post->ID );

	foreach ( (array) $post_links as $url ) {
		$url = strip_fragment_from_url( $url );

		if ( '' !== $url && ! $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE %s", $post->ID, $wpdb->esc_like( $url ) . '%' ) ) ) {

			$headers = wp_get_http_headers( $url );
			if ( $headers ) {
				$len           = isset( $headers['Content-Length'] ) ? (int) $headers['Content-Length'] : 0;
				$type          = isset( $headers['Content-Type'] ) ? $headers['Content-Type'] : '';
				$allowed_types = array( 'video', 'audio' );

				// Kiểm tra xem có thể xác định kiểu mime từ phần mở rộng hay không.
				$url_parts = parse_url( $url );
				if ( false !== $url_parts && ! empty( $url_parts['path'] ) ) {
					$extension = pathinfo( $url_parts['path'], PATHINFO_EXTENSION );
					if ( ! empty( $extension ) ) {
						foreach ( wp_get_mime_types() as $exts => $mime ) {
							if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
								$type = $mime;
								break;
							}
						}
					}
				}

				if ( in_array( substr( $type, 0, strpos( $type, '/' ) ), $allowed_types, true ) ) {
					add_post_meta( $post->ID, 'enclosure', "$url\n$len\n$mime\n" );
				}
			}
		}
	}
}

/**
 * Lấy các Header HTTP từ URL.
 *
 * @since 1.5.1
 *
 * @param string $url        URL để lấy các header HTTP.
 * @param bool   $deprecated Không sử dụng.
 * @return \WpOrg\Requests\Utility\CaseInsensitiveDictionary|false Các header khi thành công, false khi thất bại.
 */
function wp_get_http_headers( $url, $deprecated = false ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.7.0' );
	}

	$response = wp_safe_remote_head( $url );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	return wp_remote_retrieve_headers( $response );
}

/**
 * Xác định xem ngày đăng của bài viết hiện tại trong vòng lặp có khác
 * với ngày đăng của bài viết trước đó trong vòng lặp hay không.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 0.71
 *
 * @global string $currentday  Ngày của bài viết hiện tại trong vòng lặp.
 * @global string $previousday Ngày của bài viết trước đó trong vòng lặp.
 *
 * @return int 1 khi là ngày mới, 0 nếu không phải ngày mới.
 */
function is_new_day() {
	global $currentday, $previousday;

	if ( $currentday !== $previousday ) {
		return 1;
	} else {
		return 0;
	}
}

/**
 * Tạo chuỗi truy vấn URL dựa trên mảng liên kết và/hoặc mảng có chỉ mục.
 *
 * Đây là hàm tiện lợi để dễ dàng tạo chuỗi truy vấn URL. Nó đặt
 * dấu phân cách thành '&' và sử dụng hàm _http_build_query().
 *
 * @since 2.3.0
 *
 * @see _http_build_query() Được sử dụng để tạo chuỗi truy vấn
 * @link https://www.php.net/manual/en/function.http-build-query.php để biết thêm về
 *       chức năng của http_build_query().
 *
 * @param array $data Các cặp khóa/giá trị được mã hóa URL.
 * @return string Chuỗi đã được mã hóa URL.
 */
function build_query( $data ) {
	return _http_build_query( $data, null, '&', '', false );
}

/**
 * Từ php.net (được Mark Jaquith chỉnh sửa để hoạt động giống hàm PHP5 gốc).
 *
 * @since 3.2.0
 * @access private
 *
 * @see https://www.php.net/manual/en/function.http-build-query.php
 *
 * @param array|object $data      Mảng hoặc đối tượng dữ liệu. Được chuyển đổi thành mảng.
 * @param string       $prefix    Tùy chọn. Chỉ mục số. Nếu được đặt, bắt đầu đánh số tham số từ giá trị này.
 *                                Mặc định null.
 * @param string       $sep       Tùy chọn. Dấu phân cách đối số; mặc định là 'arg_separator.output'.
 *                                Mặc định null.
 * @param string       $key       Tùy chọn. Được sử dụng để thêm tiền tố cho tên khóa. Mặc định chuỗi rỗng.
 * @param bool         $urlencode Tùy chọn. Có sử dụng urlencode() trong kết quả hay không. Mặc định true.
 * @return string Chuỗi truy vấn.
 */
function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
	$ret = array();

	foreach ( (array) $data as $k => $v ) {
		if ( $urlencode ) {
			$k = urlencode( $k );
		}

		if ( is_int( $k ) && null !== $prefix ) {
			$k = $prefix . $k;
		}

		if ( ! empty( $key ) ) {
			$k = $key . '%5B' . $k . '%5D';
		}

		if ( null === $v ) {
			continue;
		} elseif ( false === $v ) {
			$v = '0';
		}

		if ( is_array( $v ) || is_object( $v ) ) {
			array_push( $ret, _http_build_query( $v, '', $sep, $k, $urlencode ) );
		} elseif ( $urlencode ) {
			array_push( $ret, $k . '=' . urlencode( $v ) );
		} else {
			array_push( $ret, $k . '=' . $v );
		}
	}

	if ( null === $sep ) {
		$sep = ini_get( 'arg_separator.output' );
	}

	return implode( $sep, $ret );
}

/**
 * Lấy chuỗi truy vấn URL đã được chỉnh sửa.
 *
 * Bạn có thể xây dựng lại URL và thêm biến truy vấn vào chuỗi truy vấn URL bằng hàm này.
 * Có hai cách sử dụng hàm này: hoặc một khóa và giá trị đơn lẻ, hoặc một mảng liên kết.
 *
 * Sử dụng một khóa và giá trị đơn lẻ:
 *
 *     add_query_arg( 'key', 'value', 'http://example.com' );
 *
 * Sử dụng mảng liên kết:
 *
 *     add_query_arg( array(
 *         'key1' => 'value1',
 *         'key2' => 'value2',
 *     ), 'http://example.com' );
 *
 * Bỏ qua URL trong cả hai cách sẽ sử dụng URL hiện tại
 * (giá trị của `$_SERVER['REQUEST_URI']`).
 *
 * Các giá trị được mong đợi đã được mã hóa phù hợp bằng urlencode() hoặc rawurlencode().
 *
 * Đặt giá trị của bất kỳ biến truy vấn nào thành boolean false sẽ xóa khóa đó (xem remove_query_arg()).
 *
 * Quan trọng: Giá trị trả về của add_query_arg() không được escape mặc định. Đầu ra nên được
 * escape muộn bằng esc_url() hoặc tương tự để giúp ngăn chặn lỗ hổng cross-site scripting
 * (XSS).
 *
 * @since 1.5.0
 * @since 5.3.0 Chính thức hóa các tham số đã tồn tại và đã được tài liệu hóa
 *              bằng cách thêm `...$args` vào chữ ký hàm.
 *
 * @param string|array $key   Khóa biến truy vấn, hoặc mảng liên kết các biến truy vấn.
 * @param string       $value Tùy chọn. Giá trị biến truy vấn, hoặc URL để thao tác.
 * @param string       $url   Tùy chọn. URL để thao tác.
 * @return string Chuỗi truy vấn URL mới (chưa được escape).
 */
function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		if ( count( $args ) < 2 || false === $args[1] ) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = $args[1];
		}
	} else {
		if ( count( $args ) < 3 || false === $args[2] ) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = $args[2];
		}
	}

	$frag = strstr( $uri, '#' );
	if ( $frag ) {
		$uri = substr( $uri, 0, -strlen( $frag ) );
	} else {
		$frag = '';
	}

	if ( 0 === stripos( $uri, 'http://' ) ) {
		$protocol = 'http://';
		$uri      = substr( $uri, 7 );
	} elseif ( 0 === stripos( $uri, 'https://' ) ) {
		$protocol = 'https://';
		$uri      = substr( $uri, 8 );
	} else {
		$protocol = '';
	}

	if ( str_contains( $uri, '?' ) ) {
		list( $base, $query ) = explode( '?', $uri, 2 );
		$base                .= '?';
	} elseif ( $protocol || ! str_contains( $uri, '=' ) ) {
		$base  = $uri . '?';
		$query = '';
	} else {
		$base  = '';
		$query = $uri;
	}

	wp_parse_str( $query, $qs );
	$qs = urlencode_deep( $qs ); // Điều này mã hóa lại URL cho những thứ đã có trong chuỗi truy vấn.
	if ( is_array( $args[0] ) ) {
		foreach ( $args[0] as $k => $v ) {
			$qs[ $k ] = $v;
		}
	} else {
		$qs[ $args[0] ] = $args[1];
	}

	foreach ( $qs as $k => $v ) {
		if ( false === $v ) {
			unset( $qs[ $k ] );
		}
	}

	$ret = build_query( $qs );
	$ret = trim( $ret, '?' );
	$ret = preg_replace( '#=(&|$)#', '$1', $ret );
	$ret = $protocol . $base . $ret . $frag;
	$ret = rtrim( $ret, '?' );
	$ret = str_replace( '?#', '#', $ret );
	return $ret;
}

/**
 * Xóa một mục hoặc nhiều mục khỏi chuỗi truy vấn.
 *
 * Quan trọng: Giá trị trả về của remove_query_arg() không được escape mặc định. Đầu ra nên được
 * escape muộn bằng esc_url() hoặc tương tự để giúp ngăn chặn lỗ hổng cross-site scripting
 * (XSS).
 *
 * @since 1.5.0
 *
 * @param string|string[] $key   Khóa truy vấn hoặc các khóa cần xóa.
 * @param false|string    $query Tùy chọn. Khi false sử dụng URL hiện tại. Mặc định false.
 * @return string Chuỗi truy vấn URL mới.
 */
function remove_query_arg( $key, $query = false ) {
	if ( is_array( $key ) ) { // Xóa nhiều khóa.
		foreach ( $key as $k ) {
			$query = add_query_arg( $k, false, $query );
		}
		return $query;
	}
	return add_query_arg( $key, false, $query );
}

/**
 * Trả về mảng các tên biến truy vấn dùng một lần có thể được xóa khỏi URL.
 *
 * @since 4.4.0
 *
 * @return string[] Mảng các tên biến truy vấn cần xóa khỏi URL.
 */
function wp_removable_query_args() {
	$removable_query_args = array(
		'activate',
		'activated',
		'admin_email_remind_later',
		'approved',
		'core-major-auto-updates-saved',
		'deactivate',
		'delete_count',
		'deleted',
		'disabled',
		'doing_wp_cron',
		'enabled',
		'error',
		'hotkeys_highlight_first',
		'hotkeys_highlight_last',
		'ids',
		'locked',
		'message',
		'same',
		'saved',
		'settings-updated',
		'skipped',
		'spammed',
		'trashed',
		'unspammed',
		'untrashed',
		'update',
		'updated',
		'wp-post-new-reload',
	);

	/**
	 * Lọc danh sách các tên biến truy vấn cần xóa.
	 *
	 * @since 4.2.0
	 *
	 * @param string[] $removable_query_args Mảng các tên biến truy vấn cần xóa khỏi URL.
	 */
	return apply_filters( 'removable_query_args', $removable_query_args );
}

/**
 * Duyệt mảng và làm sạch nội dung.
 *
 * @since 0.71
 * @since 5.5.0 Các giá trị không phải chuỗi được giữ nguyên.
 *
 * @param array $input_array Mảng cần duyệt và làm sạch nội dung.
 * @return array Mảng $input_array đã được làm sạch.
 */
function add_magic_quotes( $input_array ) {
	foreach ( (array) $input_array as $k => $v ) {
		if ( is_array( $v ) ) {
			$input_array[ $k ] = add_magic_quotes( $v );
		} elseif ( is_string( $v ) ) {
			$input_array[ $k ] = addslashes( $v );
		}
	}

	return $input_array;
}

/**
 * Yêu cầu HTTP cho URI để lấy nội dung.
 *
 * @since 1.5.1
 *
 * @see wp_safe_remote_get()
 *
 * @param string $uri URI/URL của trang web cần lấy.
 * @return string|false Nội dung HTTP. False khi thất bại.
 */
function wp_remote_fopen( $uri ) {
	$parsed_url = parse_url( $uri );

	if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
		return false;
	}

	$options            = array();
	$options['timeout'] = 10;

	$response = wp_safe_remote_get( $uri, $options );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	return wp_remote_retrieve_body( $response );
}

/**
 * Thiết lập truy vấn WordPress.
 *
 * @since 2.0.0
 *
 * @global WP       $wp           Thể hiện môi trường WordPress hiện tại.
 * @global WP_Query $wp_query     Đối tượng truy vấn WordPress.
 * @global WP_Query $wp_the_query Bản sao của đối tượng truy vấn WordPress.
 *
 * @param string|array $query_vars Các đối số mặc định của WP_Query.
 */
function wp( $query_vars = '' ) {
	global $wp, $wp_query, $wp_the_query;

	$wp->main( $query_vars );

	if ( ! isset( $wp_the_query ) ) {
		$wp_the_query = $wp_query;
	}
}

/**
 * Lấy mô tả cho mã trạng thái HTTP.
 *
 * @since 2.3.0
 * @since 3.9.0 Thêm mã trạng thái 418, 428, 429, 431 và 511.
 * @since 4.5.0 Thêm mã trạng thái 308, 421 và 451.
 * @since 5.1.0 Thêm mã trạng thái 103.
 * @since 6.6.0 Thêm mã trạng thái 425.
 *
 * @global array $wp_header_to_desc
 *
 * @param int $code Mã trạng thái HTTP.
 * @return string Mô tả trạng thái nếu tìm thấy, chuỗi rỗng nếu không.
 */
function get_status_header_desc( $code ) {
	global $wp_header_to_desc;

	$code = absint( $code );

	if ( ! isset( $wp_header_to_desc ) ) {
		$wp_header_to_desc = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			103 => 'Early Hints',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			421 => 'Misdirected Request',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			425 => 'Too Early',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			451 => 'Unavailable For Legal Reasons',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended',
			511 => 'Network Authentication Required',
		);
	}

	if ( isset( $wp_header_to_desc[ $code ] ) ) {
		return $wp_header_to_desc[ $code ];
	} else {
		return '';
	}
}

/**
 * Đặt header trạng thái HTTP.
 *
 * @since 2.0.0
 * @since 4.4.0 Thêm tham số `$description`.
 *
 * @see get_status_header_desc()
 *
 * @param int    $code        Mã trạng thái HTTP.
 * @param string $description Tùy chọn. Mô tả tùy chỉnh cho trạng thái HTTP.
 *                            Mặc định là kết quả của get_status_header_desc() cho mã được cung cấp.
 */
function status_header( $code, $description = '' ) {
	if ( ! $description ) {
		$description = get_status_header_desc( $code );
	}

	if ( empty( $description ) ) {
		return;
	}

	$protocol      = wp_get_server_protocol();
	$status_header = "$protocol $code $description";
	if ( function_exists( 'apply_filters' ) ) {

		/**
		 * Lọc header trạng thái HTTP.
		 *
		 * @since 2.2.0
		 *
		 * @param string $status_header Header trạng thái HTTP.
		 * @param int    $code          Mã trạng thái HTTP.
		 * @param string $description   Mô tả cho mã trạng thái.
		 * @param string $protocol      Giao thức máy chủ.
		 */
		$status_header = apply_filters( 'status_header', $status_header, $code, $description, $protocol );
	}

	if ( ! headers_sent() ) {
		header( $status_header, true, $code );
	}
}

/**
 * Lấy thông tin header HTTP để ngăn chặn bộ nhớ đệm.
 *
 * Các header khác nhau bao phủ các cách khác nhau mà việc ngăn chặn bộ nhớ đệm
 * được xử lý bởi các trình duyệt khác nhau hoặc các bộ nhớ đệm trung gian như máy chủ proxy.
 *
 * @since 2.8.0
 * @since 6.3.0 Header `Cache-Control` cho người dùng đã đăng nhập giờ bao gồm
 *              các chỉ thị `no-store` và `private`.
 * @since 6.8.0 Header `Cache-Control` giờ bao gồm các chỉ thị `no-store` và `private`
 *              bất kể người dùng có đăng nhập hay không.
 *
 * @return array Mảng liên kết của tên header và giá trị trường.
 */
function wp_get_nocache_headers() {
	$cache_control = 'no-cache, must-revalidate, max-age=0, no-store, private';

	$headers = array(
		'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
		'Cache-Control' => $cache_control,
	);

	if ( function_exists( 'apply_filters' ) ) {
		/**
		 * Lọc các header HTTP kiểm soát bộ nhớ đệm được sử dụng để ngăn chặn bộ nhớ đệm.
		 *
		 * @since 2.8.0
		 *
		 * @see wp_get_nocache_headers()
		 *
		 * @param array $headers Tên header và giá trị trường.
		 */
		$headers = (array) apply_filters( 'nocache_headers', $headers );
	}
	$headers['Last-Modified'] = false;
	return $headers;
}

/**
 * Đặt các header HTTP để ngăn chặn bộ nhớ đệm cho các trình duyệt khác nhau.
 *
 * Các trình duyệt khác nhau hỗ trợ các header không cache khác nhau, vì vậy nhiều
 * header phải được gửi để tất cả đều hiểu rằng không nên
 * lưu bộ nhớ đệm.
 *
 * @since 2.0.0
 *
 * @see wp_get_nocache_headers()
 */
function nocache_headers() {
	if ( headers_sent() ) {
		return;
	}

	$headers = wp_get_nocache_headers();

	unset( $headers['Last-Modified'] );

	header_remove( 'Last-Modified' );

	foreach ( $headers as $name => $field_value ) {
		header( "{$name}: {$field_value}" );
	}
}

/**
 * Đặt các header HTTP cho bộ nhớ đệm trong 10 ngày với kiểu nội dung JavaScript.
 *
 * @since 2.1.0
 */
function cache_javascript_headers() {
	$expires_offset = 10 * DAY_IN_SECONDS;

	header( 'Content-Type: text/javascript; charset=' . get_bloginfo( 'charset' ) );
	header( 'Vary: Accept-Encoding' ); // Xử lý proxy.
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
}

/**
 * Lấy số lượng truy vấn cơ sở dữ liệu trong quá trình thực thi WordPress.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @return int Số lượng truy vấn cơ sở dữ liệu.
 */
function get_num_queries() {
	global $wpdb;
	return $wpdb->num_queries;
}

/**
 * Xác định xem đầu vào là có hay không.
 *
 * Phải là 'y' để là true.
 *
 * @since 1.0.0
 *
 * @param string $yn Chuỗi ký tự chứa 'y' (có) hoặc 'n' (không).
 * @return bool True nếu là 'y', false với mọi giá trị khác.
 */
function bool_from_yn( $yn ) {
	return ( 'y' === strtolower( $yn ) );
}

/**
 * Tải template feed bằng cách sử dụng action hook.
 *
 * Nếu action feed không có hook, thì hàm sẽ dừng với thông báo
 * cho khách truy cập rằng feed không hợp lệ.
 *
 * Tốt hơn là chỉ có một hook cho mỗi feed.
 *
 * @since 2.1.0
 *
 * @global WP_Query $wp_query Đối tượng truy vấn WordPress.
 */
function do_feed() {
	global $wp_query;

	$feed = get_query_var( 'feed' );

	// Xóa phần đệm, nếu có.
	$feed = preg_replace( '/^_+/', '', $feed );

	if ( '' === $feed || 'feed' === $feed ) {
		$feed = get_default_feed();
	}

	if ( ! has_action( "do_feed_{$feed}" ) ) {
		wp_die( __( '<strong>Error:</strong> This is not a valid feed template.' ), '', array( 'response' => 404 ) );
	}

	/**
	 * Kích hoạt khi feed đã cho được tải.
	 *
	 * Phần động của tên hook, `$feed`, tham chiếu đến tên template feed.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `do_feed_atom`
	 *  - `do_feed_rdf`
	 *  - `do_feed_rss`
	 *  - `do_feed_rss2`
	 *
	 * @since 2.1.0
	 * @since 4.4.0 Tham số `$feed` được thêm vào.
	 *
	 * @param bool   $is_comment_feed Liệu feed có phải là feed bình luận hay không.
	 * @param string $feed            Tên feed.
	 */
	do_action( "do_feed_{$feed}", $wp_query->is_comment_feed, $feed );
}

/**
 * Tải template Feed RDF RSS 0.91.
 *
 * @since 2.1.0
 *
 * @see load_template()
 */
function do_feed_rdf() {
	load_template( ABSPATH . WPINC . '/feed-rdf.php' );
}

/**
 * Tải template Feed RSS 1.0.
 *
 * @since 2.1.0
 *
 * @see load_template()
 */
function do_feed_rss() {
	load_template( ABSPATH . WPINC . '/feed-rss.php' );
}

/**
 * Tải feed bình luận RSS2 hoặc feed bài viết RSS2.
 *
 * @since 2.1.0
 *
 * @see load_template()
 *
 * @param bool $for_comments True cho feed bình luận, false cho feed bình thường.
 */
function do_feed_rss2( $for_comments ) {
	if ( $for_comments ) {
		load_template( ABSPATH . WPINC . '/feed-rss2-comments.php' );
	} else {
		load_template( ABSPATH . WPINC . '/feed-rss2.php' );
	}
}

/**
 * Tải feed bình luận Atom hoặc feed bài viết Atom.
 *
 * @since 2.1.0
 *
 * @see load_template()
 *
 * @param bool $for_comments True cho feed bình luận, false cho feed bình thường.
 */
function do_feed_atom( $for_comments ) {
	if ( $for_comments ) {
		load_template( ABSPATH . WPINC . '/feed-atom-comments.php' );
	} else {
		load_template( ABSPATH . WPINC . '/feed-atom.php' );
	}
}

/**
 * Hiển thị nội dung file robots.txt mặc định.
 *
 * @since 2.1.0
 * @since 5.3.0 Xóa đầu ra "Disallow: /" nếu khả năng hiển thị công cụ tìm kiếm
 *              bị tắt để ưu tiên thẻ meta HTML robots thông qua callback bộ lọc
 *              wp_robots_no_robots().
 */
function do_robots() {
	header( 'Content-Type: text/plain; charset=utf-8' );

	/**
	 * Kích hoạt khi hiển thị file robots.txt.
	 *
	 * @since 2.1.0
	 */
	do_action( 'do_robotstxt' );

	$output = "User-agent: *\n";
	$public = (bool) get_option( 'blog_public' );

	$site_url = parse_url( site_url() );
	$path     = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';
	$output  .= "Disallow: $path/wp-admin/\n";
	$output  .= "Allow: $path/wp-admin/admin-ajax.php\n";

	/**
	 * Lọc đầu ra robots.txt.
	 *
	 * @since 3.0.0
	 *
	 * @param string $output Đầu ra robots.txt.
	 * @param bool   $public Liệu trang web có được coi là "công khai" hay không.
	 */
	echo apply_filters( 'robots_txt', $output, $public );
}

/**
 * Hiển thị nội dung file favicon.ico.
 *
 * @since 5.4.0
 */
function do_favicon() {
	/**
	 * Kích hoạt khi phục vụ file favicon.ico.
	 *
	 * @since 5.4.0
	 */
	do_action( 'do_faviconico' );

	wp_redirect( get_site_icon_url( 32, includes_url( 'images/w-logo-blue-white-bg.png' ) ) );
	exit;
}

/**
 * Xác định xem WordPress đã được cài đặt hay chưa.
 *
 * Bộ nhớ đệm sẽ được kiểm tra trước. Nếu bạn có plugin cache lưu trữ
 * các giá trị cache, thì điều này sẽ hoạt động. Nếu bạn sử dụng bộ nhớ đệm
 * mặc định của WordPress và cơ sở dữ liệu mất kết nối, bạn có thể gặp vấn đề.
 *
 * Kiểm tra tùy chọn 'siteurl' để xác định WordPress đã được cài đặt chưa.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay Nhà phát triển Theme.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @return bool Liệu trang web đã được cài đặt hay chưa.
 */
function is_blog_installed() {
	global $wpdb;

	/*
	 * Kiểm tra bộ nhớ đệm trước. Nếu bảng options mất đi và chúng ta có giá trị
	 * true trong cache, thì cũng chịu.
	 */
	if ( wp_cache_get( 'is_blog_installed' ) ) {
		return true;
	}

	$suppress = $wpdb->suppress_errors();

	if ( ! wp_installing() ) {
		$alloptions = wp_load_alloptions();
	}

	// Nếu siteurl không được đặt thành autoload, kiểm tra cụ thể.
	if ( ! isset( $alloptions['siteurl'] ) ) {
		$installed = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'siteurl'" );
	} else {
		$installed = $alloptions['siteurl'];
	}

	$wpdb->suppress_errors( $suppress );

	$installed = ! empty( $installed );
	wp_cache_set( 'is_blog_installed', $installed );

	if ( $installed ) {
		return true;
	}

	// Nếu đang truy cập repair.php, trả về true và để nó xử lý.
	if ( defined( 'WP_REPAIRING' ) ) {
		return true;
	}

	$suppress = $wpdb->suppress_errors();

	/*
	 * Duyệt qua các bảng WP. Nếu không bảng nào tồn tại, thì cho phép cài đặt mới.
	 * Nếu một hoặc nhiều bảng tồn tại, đề xuất sửa chữa bảng vì chúng ta đến đây
	 * do bảng options không thể truy cập được.
	 */
	$wp_tables = $wpdb->tables();
	foreach ( $wp_tables as $table ) {
		// Sự tồn tại của các bảng người dùng tùy chỉnh không nên gợi ý trạng thái không phù hợp hoặc ngăn cài đặt mới.
		if ( defined( 'CUSTOM_USER_TABLE' ) && CUSTOM_USER_TABLE === $table ) {
			continue;
		}

		if ( defined( 'CUSTOM_USER_META_TABLE' ) && CUSTOM_USER_META_TABLE === $table ) {
			continue;
		}

		$described_table = $wpdb->get_results( "DESCRIBE $table;" );
		if (
			( ! $described_table && empty( $wpdb->last_error ) ) ||
			( is_array( $described_table ) && 0 === count( $described_table ) )
		) {
			continue;
		}

		// Một hoặc nhiều bảng tồn tại. Điều này không tốt.

		wp_load_translations_early();

		// Dừng với lỗi cơ sở dữ liệu.
		$wpdb->error = sprintf(
			/* translators: %s: Database repair URL. */
			__( 'One or more database tables are unavailable. The database may need to be <a href="%s">repaired</a>.' ),
			'maint/repair.php?referrer=is_blog_installed'
		);

		dead_db();
	}

	$wpdb->suppress_errors( $suppress );

	wp_cache_set( 'is_blog_installed', false );

	return false;
}

/**
 * Lấy URL với nonce được thêm vào chuỗi truy vấn URL.
 *
 * @since 2.0.4
 *
 * @param string     $actionurl URL để thêm action nonce.
 * @param int|string $action    Tùy chọn. Tên action nonce. Mặc định -1.
 * @param string     $name      Tùy chọn. Tên nonce. Mặc định '_wpnonce'.
 * @return string URL đã được escape với action nonce được thêm vào.
 */
function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
	$actionurl = str_replace( '&amp;', '&', $actionurl );
	return esc_html( add_query_arg( $name, wp_create_nonce( $action ), $actionurl ) );
}

/**
 * Lấy hoặc hiển thị trường ẩn nonce cho biểu mẫu.
 *
 * Trường nonce được sử dụng để xác thực rằng nội dung biểu mẫu đến từ
 * vị trí trên trang web hiện tại và không phải từ nơi khác. Nonce không
 * cung cấp bảo vệ tuyệt đối, nhưng sẽ bảo vệ trong hầu hết các trường hợp. Rất
 * quan trọng để sử dụng trường nonce trong biểu mẫu.
 *
 * $action và $name là tùy chọn, nhưng nếu bạn muốn có bảo mật tốt hơn,
 * rất khuyến khích đặt hai tham số đó. Dễ hơn là chỉ gọi hàm mà không có
 * tham số nào, vì việc xác thực nonce không yêu cầu bất kỳ tham số nào,
 * nhưng vì kẻ tấn công biết giá trị mặc định là gì nên sẽ không khó để
 * họ tìm cách vượt qua nonce của bạn và gây thiệt hại.
 *
 * Tên input sẽ là bất kỳ giá trị $name nào bạn cung cấp. Giá trị input sẽ là
 * giá trị tạo nonce.
 *
 * @since 2.0.4
 *
 * @param int|string $action  Tùy chọn. Tên action. Mặc định -1.
 * @param string     $name    Tùy chọn. Tên nonce. Mặc định '_wpnonce'.
 * @param bool       $referer Tùy chọn. Có đặt trường referer để xác thực hay không. Mặc định true.
 * @param bool       $display Tùy chọn. Có hiển thị hoặc trả về trường ẩn biểu mẫu hay không. Mặc định true.
 * @return string Mã HTML trường nonce.
 */
function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
	$name        = esc_attr( $name );
	$nonce_field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . wp_create_nonce( $action ) . '" />';

	if ( $referer ) {
		$nonce_field .= wp_referer_field( false );
	}

	if ( $display ) {
		echo $nonce_field;
	}

	return $nonce_field;
}

/**
 * Lấy hoặc hiển thị trường ẩn referer cho biểu mẫu.
 *
 * Liên kết referer là URI yêu cầu hiện tại từ biến super global của máy chủ.
 * Tên input là '_wp_http_referer', trong trường hợp bạn muốn kiểm tra thủ công.
 *
 * @since 2.0.4
 *
 * @param bool $display Tùy chọn. Có echo hoặc trả về trường referer hay không. Mặc định true.
 * @return string Mã HTML trường referer.
 */
function wp_referer_field( $display = true ) {
	$request_url   = remove_query_arg( '_wp_http_referer' );
	$referer_field = '<input type="hidden" name="_wp_http_referer" value="' . esc_url( $request_url ) . '" />';

	if ( $display ) {
		echo $referer_field;
	}

	return $referer_field;
}

/**
 * Lấy hoặc hiển thị trường ẩn referer gốc cho biểu mẫu.
 *
 * Tên input là '_wp_original_http_referer' và sẽ là cùng giá trị
 * của wp_referer_field(), nếu nó đã được gửi trước đó hoặc sẽ là
 * trang hiện tại, nếu nó không tồn tại.
 *
 * @since 2.0.4
 *
 * @param bool   $display      Tùy chọn. Có echo referer http gốc hay không. Mặc định true.
 * @param string $jump_back_to Tùy chọn. Có thể là 'previous' hoặc trang bạn muốn quay lại.
 *                             Mặc định 'current'.
 * @return string Trường referer gốc.
 */
function wp_original_referer_field( $display = true, $jump_back_to = 'current' ) {
	$ref = wp_get_original_referer();

	if ( ! $ref ) {
		$ref = ( 'previous' === $jump_back_to ) ? wp_get_referer() : wp_unslash( $_SERVER['REQUEST_URI'] );
	}

	$orig_referer_field = '<input type="hidden" name="_wp_original_http_referer" value="' . esc_attr( $ref ) . '" />';

	if ( $display ) {
		echo $orig_referer_field;
	}

	return $orig_referer_field;
}

/**
 * Lấy referer từ '_wp_http_referer' hoặc HTTP referer.
 *
 * Nếu nó giống với URL yêu cầu hiện tại, sẽ trả về false.
 *
 * @since 2.0.4
 *
 * @return string|false URL referer khi thành công, false khi thất bại.
 */
function wp_get_referer() {
	// Trả về sớm nếu được gọi trước khi wp_validate_redirect() được định nghĩa.
	if ( ! function_exists( 'wp_validate_redirect' ) ) {
		return false;
	}

	$ref = wp_get_raw_referer();

	if ( $ref && wp_unslash( $_SERVER['REQUEST_URI'] ) !== $ref
		&& home_url() . wp_unslash( $_SERVER['REQUEST_URI'] ) !== $ref
	) {
		return wp_validate_redirect( $ref, false );
	}

	return false;
}

/**
 * Lấy referer chưa được xác thực từ biến truy vấn URL '_wp_http_referer' hoặc HTTP referer.
 *
 * Nếu giá trị của biến truy vấn URL '_wp_http_referer' không phải là chuỗi thì nó sẽ bị bỏ qua.
 *
 * Không sử dụng cho chuyển hướng, hãy sử dụng wp_get_referer() thay thế.
 *
 * @since 4.5.0
 *
 * @return string|false URL referer khi thành công, false khi thất bại.
 */
function wp_get_raw_referer() {
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) && is_string( $_REQUEST['_wp_http_referer'] ) ) {
		return wp_unslash( $_REQUEST['_wp_http_referer'] );
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		return wp_unslash( $_SERVER['HTTP_REFERER'] );
	}

	return false;
}

/**
 * Lấy referer gốc đã được gửi, nếu tồn tại.
 *
 * @since 2.0.4
 *
 * @return string|false URL referer gốc khi thành công, false khi thất bại.
 */
function wp_get_original_referer() {
	// Trả về sớm nếu được gọi trước khi wp_validate_redirect() được định nghĩa.
	if ( ! function_exists( 'wp_validate_redirect' ) ) {
		return false;
	}

	if ( ! empty( $_REQUEST['_wp_original_http_referer'] ) ) {
		return wp_validate_redirect( wp_unslash( $_REQUEST['_wp_original_http_referer'] ), false );
	}

	return false;
}

/**
 * Tạo thư mục đệ quy dựa trên đường dẫn đầy đủ.
 *
 * Sẽ cố gắng đặt quyền truy cập trên các thư mục.
 *
 * @since 2.0.1
 *
 * @param string $target Đường dẫn đầy đủ cần tạo.
 * @return bool Liệu đường dẫn đã được tạo hay chưa. True nếu đường dẫn đã tồn tại.
 */
function wp_mkdir_p( $target ) {
	$wrapper = null;

	// Loại bỏ giao thức.
	if ( wp_is_stream( $target ) ) {
		list( $wrapper, $target ) = explode( '://', $target, 2 );
	}

	// Từ ghi chú đóng góp người dùng php.net/mkdir.
	$target = str_replace( '//', '/', $target );

	// Đặt lại wrapper vào đường dẫn đích.
	if ( null !== $wrapper ) {
		$target = $wrapper . '://' . $target;
	}

	/*
	 * Chế độ an toàn thất bại với dấu gạch chéo cuối trong một số phiên bản PHP nhất định.
	 * Sử dụng rtrim() thay vì untrailingslashit để tránh phụ thuộc formatting.php.
	 */
	$target = rtrim( $target, '/' );
	if ( empty( $target ) ) {
		$target = '/';
	}

	if ( file_exists( $target ) ) {
		return @is_dir( $target );
	}

	// Không cho phép duyệt đường dẫn.
	if ( str_contains( $target, '../' ) || str_contains( $target, '..' . DIRECTORY_SEPARATOR ) ) {
		return false;
	}

	// Chúng ta cần tìm quyền truy cập của thư mục cha tồn tại và kế thừa quyền đó.
	$target_parent = dirname( $target );
	while ( '.' !== $target_parent && ! is_dir( $target_parent ) && dirname( $target_parent ) !== $target_parent ) {
		$target_parent = dirname( $target_parent );
	}

	// Lấy các bit quyền truy cập.
	$stat = @stat( $target_parent );
	if ( $stat ) {
		$dir_perms = $stat['mode'] & 0007777;
	} else {
		$dir_perms = 0777;
	}

	if ( @mkdir( $target, $dir_perms, true ) ) {

		/*
		 * Nếu umask được đặt làm thay đổi $dir_perms, chúng ta sẽ phải đặt lại
		 * $dir_perms chính xác bằng chmod()
		 */
		if ( ( $dir_perms & ~umask() ) !== $dir_perms ) {
			$folder_parts = explode( '/', substr( $target, strlen( $target_parent ) + 1 ) );
			for ( $i = 1, $c = count( $folder_parts ); $i <= $c; $i++ ) {
				chmod( $target_parent . '/' . implode( '/', array_slice( $folder_parts, 0, $i ) ), $dir_perms );
			}
		}

		return true;
	}

	return false;
}

/**
 * Kiểm tra xem đường dẫn hệ thống file đã cho có phải là tuyệt đối hay không.
 *
 * Ví dụ, '/foo/bar', hoặc 'c:\windows'.
 *
 * @since 2.5.0
 *
 * @param string $path Đường dẫn file.
 * @return bool True nếu đường dẫn là tuyệt đối, false nếu không phải tuyệt đối.
 */
function path_is_absolute( $path ) {
	/*
	 * Kiểm tra xem đường dẫn có phải là stream hay không và kiểm tra xem nó có phải là
	 * đường dẫn hoặc file thực tế hay không vì realpath() không hỗ trợ stream wrapper.
	 */
	if ( wp_is_stream( $path ) && ( is_dir( $path ) || is_file( $path ) ) ) {
		return true;
	}

	/*
	 * Kết quả này là chính xác nếu true nhưng thất bại nếu $path không tồn tại hoặc chứa
	 * liên kết tượng trưng.
	 */
	if ( realpath( $path ) === $path ) {
		return true;
	}

	if ( strlen( $path ) === 0 || '.' === $path[0] ) {
		return false;
	}

	// Windows cho phép đường dẫn tuyệt đối như thế này.
	if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) ) {
		return true;
	}

	// Đường dẫn bắt đầu bằng / hoặc \ là tuyệt đối; mọi thứ khác là tương đối.
	return ( '/' === $path[0] || '\\' === $path[0] );
}

/**
 * Nối hai đường dẫn hệ thống file lại với nhau.
 *
 * Ví dụ, 'cho tôi $path tương đối với $base'. Nếu $path là tuyệt đối,
 * thì đường dẫn đầy đủ sẽ được trả về.
 *
 * @since 2.5.0
 *
 * @param string $base Đường dẫn cơ sở.
 * @param string $path Đường dẫn tương đối với $base.
 * @return string Đường dẫn với cơ sở hoặc đường dẫn tuyệt đối.
 */
function path_join( $base, $path ) {
	if ( path_is_absolute( $path ) ) {
		return $path;
	}

	return rtrim( $base, '/' ) . '/' . $path;
}

/**
 * Chuẩn hóa đường dẫn hệ thống file.
 *
 * Trên hệ thống Windows, thay thế dấu gạch chéo ngược bằng dấu gạch chéo xuôi
 * và ép chữ ổ đĩa thành chữ hoa.
 * Cho phép hai dấu gạch chéo đầu cho chia sẻ mạng Windows, nhưng
 * đảm bảo rằng tất cả các dấu gạch chéo trùng lặp khác được giảm xuống còn một.
 *
 * @since 3.9.0
 * @since 4.4.0 Đảm bảo chữ ổ đĩa viết hoa trên hệ thống Windows.
 * @since 4.5.0 Cho phép chia sẻ mạng Windows.
 * @since 4.9.7 Cho phép PHP file wrapper.
 *
 * @param string $path Đường dẫn cần chuẩn hóa.
 * @return string Đường dẫn đã chuẩn hóa.
 */
function wp_normalize_path( $path ) {
	$wrapper = '';

	if ( wp_is_stream( $path ) ) {
		list( $wrapper, $path ) = explode( '://', $path, 2 );

		$wrapper .= '://';
	}

	// Chuẩn hóa tất cả đường dẫn để sử dụng '/'.
	$path = str_replace( '\\', '/', $path );

	// Thay thế nhiều dấu gạch chéo xuống còn một, cho phép chia sẻ mạng có hai dấu gạch chéo.
	$path = preg_replace( '|(?<=.)/+|', '/', $path );

	// Đường dẫn Windows nên viết hoa chữ ổ đĩa.
	if ( ':' === substr( $path, 1, 1 ) ) {
		$path = ucfirst( $path );
	}

	return $wrapper . $path;
}

/**
 * Xác định thư mục có thể ghi cho các file tạm thời.
 *
 * Ưu tiên của hàm là giá trị trả về của sys_get_temp_dir(),
 * tiếp theo là thư mục tải lên tạm thời PHP của bạn, tiếp theo là WP_CONTENT_DIR,
 * cuối cùng mặc định là /tmp/
 *
 * Trong trường hợp hàm này không tìm thấy vị trí có thể ghi,
 * nó có thể được ghi đè bởi hằng số WP_TEMP_DIR trong file wp-config.php của bạn.
 *
 * @since 2.5.0
 *
 * @return string Thư mục tạm thời có thể ghi.
 */
function get_temp_dir() {
	static $temp = '';
	if ( defined( 'WP_TEMP_DIR' ) ) {
		return trailingslashit( WP_TEMP_DIR );
	}

	if ( $temp ) {
		return trailingslashit( $temp );
	}

	if ( function_exists( 'sys_get_temp_dir' ) ) {
		$temp = sys_get_temp_dir();
		if ( @is_dir( $temp ) && wp_is_writable( $temp ) ) {
			return trailingslashit( $temp );
		}
	}

	$temp = ini_get( 'upload_tmp_dir' );
	if ( @is_dir( $temp ) && wp_is_writable( $temp ) ) {
		return trailingslashit( $temp );
	}

	$temp = WP_CONTENT_DIR . '/';
	if ( is_dir( $temp ) && wp_is_writable( $temp ) ) {
		return $temp;
	}

	return '/tmp/';
}

/**
 * Xác định xem thư mục có thể ghi hay không.
 *
 * Hàm này được sử dụng để giải quyết một số vấn đề ACL nhất định trong PHP chủ yếu
 * ảnh hưởng đến máy chủ Windows.
 *
 * @since 3.6.0
 *
 * @see win_is_writable()
 *
 * @param string $path Đường dẫn cần kiểm tra khả năng ghi.
 * @return bool Liệu đường dẫn có thể ghi hay không.
 */
function wp_is_writable( $path ) {
	if ( 'Windows' === PHP_OS_FAMILY ) {
		return win_is_writable( $path );
	}

	return @is_writable( $path );
}

/**
 * Giải pháp thay thế cho lỗi Windows trong hàm is_writable()
 *
 * PHP có vấn đề với ACL của Windows trong việc xác định xem
 * thư mục có thể ghi hay không, giải pháp này vượt qua bằng cách
 * kiểm tra khả năng mở file thay vì dựa vào
 * PHP để giải thích ACL của hệ điều hành.
 *
 * @since 2.8.0
 *
 * @see https://bugs.php.net/bug.php?id=27609
 * @see https://bugs.php.net/bug.php?id=30931
 *
 * @param string $path Đường dẫn Windows cần kiểm tra khả năng ghi.
 * @return bool Liệu đường dẫn có thể ghi hay không.
 */
function win_is_writable( $path ) {
	if ( '/' === $path[ strlen( $path ) - 1 ] ) {
		// Nếu trông giống thư mục, kiểm tra một file ngẫu nhiên trong thư mục.
		return win_is_writable( $path . uniqid( mt_rand() ) . '.tmp' );
	} elseif ( is_dir( $path ) ) {
		// Nếu là thư mục (không phải file), kiểm tra một file ngẫu nhiên trong thư mục.
		return win_is_writable( $path . '/' . uniqid( mt_rand() ) . '.tmp' );
	}

	// Kiểm tra file tạm thời để xác định khả năng đọc/ghi.
	$should_delete_tmp_file = ! file_exists( $path );

	$f = @fopen( $path, 'a' );
	if ( false === $f ) {
		return false;
	}
	fclose( $f );

	if ( $should_delete_tmp_file ) {
		unlink( $path );
	}

	return true;
}

/**
 * Lấy thông tin thư mục tải lên.
 *
 * Giống như wp_upload_dir() nhưng "nhẹ hơn" vì không cố tạo thư mục tải lên.
 * Dành cho sử dụng trong theme, khi chỉ cần 'basedir' và 'baseurl', thường trong mọi trường hợp
 * khi không tải file lên.
 *
 * @since 4.5.0
 *
 * @see wp_upload_dir()
 *
 * @return array Xem wp_upload_dir() để biết mô tả.
 */
function wp_get_upload_dir() {
	return wp_upload_dir( null, false );
}

/**
 * Trả về mảng chứa đường dẫn và URL của thư mục tải lên hiện tại.
 *
 * Kiểm tra tùy chọn 'upload_path', nên từ thư mục gốc web,
 * và nếu không rỗng thì sẽ được sử dụng. Nếu rỗng, đường dẫn sẽ là
 * 'WP_CONTENT_DIR/uploads'. Nếu hằng số 'UPLOADS' được định nghĩa, nó sẽ
 * ghi đè tùy chọn 'upload_path' và đường dẫn 'WP_CONTENT_DIR/uploads'.
 *
 * Đường dẫn URL tải lên được đặt bởi tùy chọn 'upload_url_path' hoặc bằng cách sử dụng
 * hằng số 'WP_CONTENT_URL' và thêm '/uploads' vào đường dẫn.
 *
 * Nếu 'uploads_use_yearmonth_folders' được đặt thành true (checkbox được chọn trong
 * bảng cài đặt quản trị), thì thời gian sẽ được sử dụng. Định dạng
 * sẽ là năm trước rồi tháng sau.
 *
 * Nếu đường dẫn không thể tạo được, lỗi sẽ được trả về với khóa
 * 'error' chứa thông báo lỗi. Lỗi gợi ý rằng thư mục cha
 * không có quyền ghi bởi máy chủ.
 *
 * @since 2.0.0
 * @uses _wp_upload_dir()
 *
 * @param string|null $time          Tùy chọn. Thời gian theo định dạng 'yyyy/mm'. Mặc định null.
 * @param bool        $create_dir    Tùy chọn. Có kiểm tra và tạo thư mục tải lên hay không.
 *                                   Mặc định true để tương thích ngược.
 * @param bool        $refresh_cache Tùy chọn. Có làm mới bộ nhớ đệm hay không. Mặc định false.
 * @return array {
 *     Mảng thông tin về thư mục tải lên.
 *
 *     @type string       $path    Thư mục cơ sở và thư mục con hoặc đường dẫn đầy đủ đến thư mục tải lên.
 *     @type string       $url     URL cơ sở và thư mục con hoặc URL tuyệt đối đến thư mục tải lên.
 *     @type string       $subdir  Thư mục con nếu tùy chọn sử dụng thư mục năm/tháng được bật.
 *     @type string       $basedir Đường dẫn không có thư mục con.
 *     @type string       $baseurl Đường dẫn URL không có thư mục con.
 *     @type string|false $error   False hoặc thông báo lỗi.
 * }
 */
function wp_upload_dir( $time = null, $create_dir = true, $refresh_cache = false ) {
	static $cache = array(), $tested_paths = array();

	$key = sprintf( '%d-%s', get_current_blog_id(), (string) $time );

	if ( $refresh_cache || empty( $cache[ $key ] ) ) {
		$cache[ $key ] = _wp_upload_dir( $time );
	}

	/**
	 * Lọc dữ liệu thư mục tải lên.
	 *
	 * @since 2.0.0
	 *
	 * @param array $uploads {
	 *     Mảng thông tin về thư mục tải lên.
	 *
	 *     @type string       $path    Thư mục cơ sở và thư mục con hoặc đường dẫn đầy đủ đến thư mục tải lên.
	 *     @type string       $url     URL cơ sở và thư mục con hoặc URL tuyệt đối đến thư mục tải lên.
	 *     @type string       $subdir  Thư mục con nếu tùy chọn sử dụng thư mục năm/tháng được bật.
	 *     @type string       $basedir Đường dẫn không có thư mục con.
	 *     @type string       $baseurl Đường dẫn URL không có thư mục con.
	 *     @type string|false $error   False hoặc thông báo lỗi.
	 * }
	 */
	$uploads = apply_filters( 'upload_dir', $cache[ $key ] );

	if ( $create_dir ) {
		$path = $uploads['path'];

		if ( array_key_exists( $path, $tested_paths ) ) {
			$uploads['error'] = $tested_paths[ $path ];
		} else {
			if ( ! wp_mkdir_p( $path ) ) {
				if ( str_starts_with( $uploads['basedir'], ABSPATH ) ) {
					$error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
				} else {
					$error_path = wp_basename( $uploads['basedir'] ) . $uploads['subdir'];
				}

				$uploads['error'] = sprintf(
					/* translators: %s: Directory path. */
					__( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
					esc_html( $error_path )
				);
			}

			$tested_paths[ $path ] = $uploads['error'];
		}
	}

	return $uploads;
}

/**
 * Phiên bản không lọc, không cache của wp_upload_dir() mà không kiểm tra đường dẫn.
 *
 * @since 4.5.0
 * @access private
 *
 * @param string|null $time Tùy chọn. Thời gian theo định dạng 'yyyy/mm'. Mặc định null.
 * @return array Xem wp_upload_dir()
 */
function _wp_upload_dir( $time = null ) {
	$siteurl     = get_option( 'siteurl' );
	$upload_path = trim( get_option( 'upload_path' ) );

	if ( empty( $upload_path ) || 'wp-content/uploads' === $upload_path ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} elseif ( ! str_starts_with( $upload_path, ABSPATH ) ) {
		// $dir là tuyệt đối, $upload_path có thể là tương đối với ABSPATH.
		$dir = path_join( ABSPATH, $upload_path );
	} else {
		$dir = $upload_path;
	}

	$url = get_option( 'upload_url_path' );
	if ( ! $url ) {
		if ( empty( $upload_path ) || ( 'wp-content/uploads' === $upload_path ) || ( $upload_path === $dir ) ) {
			$url = WP_CONTENT_URL . '/uploads';
		} else {
			$url = trailingslashit( $siteurl ) . $upload_path;
		}
	}

	/*
	 * Tôn trọng giá trị của UPLOADS. Điều này xảy ra miễn là ghi lại ms-files bị tắt.
	 * Chúng ta cũng đôi khi tuân theo UPLOADS khi ghi lại được bật -- xem khối tiếp theo.
	 */
	if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	// Nếu multisite (và nếu không phải trang chính trong mạng post-MU).
	if ( is_multisite() && ! ( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {

		if ( ! get_site_option( 'ms_files_rewriting' ) ) {
			/*
			 * Nếu ghi lại ms-files bị tắt (mạng được tạo sau 3.5), khá đơn giản:
			 * Thêm sites/%d nếu chúng ta không ở trang chính (cho mạng post-MU).
			 * (Thư mục bổ sung ngăn ID bốn chữ số xung đột với thư mục dựa trên năm
			 * cho trang chính. Nhưng nếu mạng thời MU đã tắt ghi lại ms-files thủ công,
			 * họ không cần thư mục bổ sung, vì họ chưa bao giờ có wp-content/uploads
			 * cho trang chính.)
			 */

			if ( defined( 'MULTISITE' ) ) {
				$ms_dir = '/sites/' . get_current_blog_id();
			} else {
				$ms_dir = '/' . get_current_blog_id();
			}

			$dir .= $ms_dir;
			$url .= $ms_dir;

		} elseif ( defined( 'UPLOADS' ) && ! ms_is_switched() ) {
			/*
			 * Xử lý ghi lại ms-files.php dạng cũ nếu mạng vẫn bật tính năng đó.
			 * Khi ghi lại ms-files được bật, chúng ta chỉ lắng nghe UPLOADS khi:
			 * 1) Chúng ta không ở trang chính trong mạng post-MU, vì wp-content/uploads được sử dụng
			 *    ở đó, và
			 * 2) Chúng ta không bị chuyển đổi, vì ms_upload_constants() mã hóa cứng các hằng số này
			 *    để phản ánh ID blog gốc.
			 *
			 * Thay vì UPLOADS, chúng ta thực sự sử dụng BLOGUPLOADDIR nếu nó được đặt, vì nó là tuyệt đối.
			 * (Và nó sẽ được đặt, xem ms_upload_constants().) Nếu không, UPLOADS có thể được sử dụng,
			 * vì nó tương đối với ABSPATH. Phần cuối cùng: khi UPLOADS được sử dụng với ghi lại
			 * ms-files trong multisite, URL kết quả là /files. (#WP22702 để tham khảo.)
			 */

			if ( defined( 'BLOGUPLOADDIR' ) ) {
				$dir = untrailingslashit( BLOGUPLOADDIR );
			} else {
				$dir = ABSPATH . UPLOADS;
			}
			$url = trailingslashit( $siteurl ) . 'files';
		}
	}

	$basedir = $dir;
	$baseurl = $url;

	$subdir = '';
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		// Tạo thư mục theo năm và tháng.
		if ( ! $time ) {
			$time = current_time( 'mysql' );
		}
		$y      = substr( $time, 0, 4 );
		$m      = substr( $time, 5, 2 );
		$subdir = "/$y/$m";
	}

	$dir .= $subdir;
	$url .= $subdir;

	return array(
		'path'    => $dir,
		'url'     => $url,
		'subdir'  => $subdir,
		'basedir' => $basedir,
		'baseurl' => $baseurl,
		'error'   => false,
	);
}

/**
 * Lấy tên file đã được làm sạch và duy nhất cho thư mục đã cho.
 *
 * Nếu tên file không duy nhất, một số sẽ được thêm vào tên file
 * trước phần mở rộng, và sẽ tiếp tục thêm số cho đến khi tên file
 * trở thành duy nhất.
 *
 * Hàm callback cho phép người gọi sử dụng phương thức riêng để tạo
 * tên file duy nhất. Nếu được định nghĩa, callback nên nhận ba đối số:
 * - thư mục, tên file cơ sở, và phần mở rộng - và trả về tên file duy nhất.
 *
 * @since 2.5.0
 *
 * @param string   $dir                      Thư mục.
 * @param string   $filename                 Tên file.
 * @param callable $unique_filename_callback Callback. Mặc định null.
 * @return string Tên file mới, nếu tên đã cho không duy nhất.
 */
function wp_unique_filename( $dir, $filename, $unique_filename_callback = null ) {
	// Làm sạch tên file trước khi bắt đầu xử lý.
	$filename = sanitize_file_name( $filename );
	$ext2     = null;

	// Khởi tạo các biến sử dụng trong bộ lọc wp_unique_filename.
	$number        = '';
	$alt_filenames = array();

	// Tách tên file thành tên và phần mở rộng.
	$ext  = pathinfo( $filename, PATHINFO_EXTENSION );
	$name = pathinfo( $filename, PATHINFO_BASENAME );

	if ( $ext ) {
		$ext = '.' . $ext;
	}

	// Trường hợp đặc biệt: nếu file có tên '.ext', coi như tên rỗng.
	if ( $name === $ext ) {
		$name = '';
	}

	/*
	 * Tăng số thứ tự file cho đến khi có file duy nhất để lưu trong $dir.
	 * Sử dụng callback nếu được cung cấp.
	 */
	if ( $unique_filename_callback && is_callable( $unique_filename_callback ) ) {
		$filename = call_user_func( $unique_filename_callback, $dir, $name, $ext );
	} else {
		$fname = pathinfo( $filename, PATHINFO_FILENAME );

		// Luôn thêm số vào tên file có thể khớp với tên file kích thước phụ của ảnh.
		if ( $fname && preg_match( '/-(?:\d+x\d+|scaled|rotated)$/', $fname ) ) {
			$number = 1;

			// Tại thời điểm này tên file có thể chưa duy nhất. Điều này được kiểm tra bên dưới và $number sẽ tăng.
			$filename = str_replace( "{$fname}{$ext}", "{$fname}-{$number}{$ext}", $filename );
		}

		/*
		 * Lấy loại mime. Các file đã tải lên đã được kiểm tra bằng wp_check_filetype_and_ext()
		 * trong _wp_handle_upload(). Sử dụng wp_check_filetype() là đủ ở đây.
		 */
		$file_type = wp_check_filetype( $filename );
		$mime_type = $file_type['type'];

		$is_image    = ( ! empty( $mime_type ) && str_starts_with( $mime_type, 'image/' ) );
		$upload_dir  = wp_get_upload_dir();
		$lc_filename = null;

		$lc_ext = strtolower( $ext );
		$_dir   = trailingslashit( $dir );

		/*
		 * Nếu phần mở rộng viết hoa, thêm tên file thay thế với phần mở rộng viết thường.
		 * Cả hai cần được kiểm tra tính duy nhất vì phần mở rộng sẽ được chuyển sang chữ thường
		 * để tương thích tốt hơn với các hệ thống file khác nhau. Sửa lỗi không nhất quán trong WP < 2.9
		 * nơi phần mở rộng viết hoa được cho phép nhưng kích thước phụ của ảnh được tạo với
		 * phần mở rộng viết thường.
		 */
		if ( $ext && $lc_ext !== $ext ) {
			$lc_filename = preg_replace( '|' . preg_quote( $ext ) . '$|', $lc_ext, $filename );
		}

		/*
		 * Tăng số được thêm vào tên file nếu có bất kỳ file nào trong $dir
		 * có tên khớp với một trong các biến thể tên có thể có.
		 */
		while ( file_exists( $_dir . $filename ) || ( $lc_filename && file_exists( $_dir . $lc_filename ) ) ) {
			$new_number = (int) $number + 1;

			if ( $lc_filename ) {
				$lc_filename = str_replace(
					array( "-{$number}{$lc_ext}", "{$number}{$lc_ext}" ),
					"-{$new_number}{$lc_ext}",
					$lc_filename
				);
			}

			if ( '' === "{$number}{$ext}" ) {
				$filename = "{$filename}-{$new_number}";
			} else {
				$filename = str_replace(
					array( "-{$number}{$ext}", "{$number}{$ext}" ),
					"-{$new_number}{$ext}",
					$filename
				);
			}

			$number = $new_number;
		}

		// Chuyển phần mở rộng sang chữ thường nếu cần.
		if ( $lc_filename ) {
			$filename = $lc_filename;
		}

		/*
		 * Ngăn xung đột với tên file hiện có chứa chuỗi giống kích thước
		 * (dù chúng là kích thước phụ hay bản gốc được tải lên trước #42437).
		 */

		$files = array();
		$count = 10000;

		// Các file ảnh (đã thay đổi kích thước) sẽ có tên và phần mở rộng, và nằm trong thư mục uploads.
		if ( $name && $ext && @is_dir( $dir ) && str_contains( $dir, $upload_dir['basedir'] ) ) {
			/**
			 * Lọc danh sách file dùng để tính toán tên file duy nhất cho file mới được thêm.
			 *
			 * Trả về mảng từ bộ lọc sẽ bỏ qua việc lấy danh sách
			 * từ hệ thống file và trả về giá trị đã truyền thay thế.
			 *
			 * @since 5.5.0
			 *
			 * @param array|null $files    Danh sách file để so sánh tên file.
			 *                             Mặc định null (để lấy danh sách từ hệ thống file).
			 * @param string     $dir      Thư mục cho file mới.
			 * @param string     $filename Tên file đề xuất cho file mới.
			 */
			$files = apply_filters( 'pre_wp_unique_filename_file_list', null, $dir, $filename );

			if ( null === $files ) {
				// Danh sách tất cả file và thư mục chứa trong $dir.
				$files = @scandir( $dir );
			}

			if ( ! empty( $files ) ) {
				// Xóa các thư mục "dấu chấm".
				$files = array_diff( $files, array( '.', '..' ) );
			}

			if ( ! empty( $files ) ) {
				$count = count( $files );

				/*
				 * Đảm bảo không bao giờ rơi vào vòng lặp vô hạn vì nó sử dụng pathinfo() và regex để kiểm tra,
				 * nhưng thay thế chuỗi cho các thay đổi.
				 */
				$i = 0;

				while ( $i <= $count && _wp_check_existing_file_names( $filename, $files ) ) {
					$new_number = (int) $number + 1;

					// Nếu $ext viết hoa, nó đã được thay bằng phiên bản viết thường sau vòng lặp trước.
					$filename = str_replace(
						array( "-{$number}{$lc_ext}", "{$number}{$lc_ext}" ),
						"-{$new_number}{$lc_ext}",
						$filename
					);

					$number = $new_number;
					++$i;
				}
			}
		}

		/*
		 * Kiểm tra xem ảnh có bị chuyển đổi sau khi tải lên hay một số tên file kích thước phụ hiện có có thể xung đột
		 * khi được tạo lại. Nếu có, đảm bảo tên file mới sẽ duy nhất và tạo ra kích thước phụ duy nhất.
		 */
		if ( $is_image ) {
			$output_formats = wp_get_image_editor_output_format( $_dir . $filename, $mime_type );
			$alt_types      = array();

			if ( ! empty( $output_formats[ $mime_type ] ) ) {
				// Ảnh sẽ được chuyển đổi sang định dạng/loại mime này.
				$alt_mime_type = $output_formats[ $mime_type ];

				// Các loại ảnh khác có tên có thể xung đột nếu kích thước phụ của chúng được tạo lại.
				$alt_types   = array_keys( array_intersect( $output_formats, array( $mime_type, $alt_mime_type ) ) );
				$alt_types[] = $alt_mime_type;
			} elseif ( ! empty( $output_formats ) ) {
				$alt_types = array_keys( array_intersect( $output_formats, array( $mime_type ) ) );
			}

			// Xóa trùng lặp và loại mime gốc. Nó sẽ được thêm lại sau nếu cần.
			$alt_types = array_unique( array_diff( $alt_types, array( $mime_type ) ) );

			foreach ( $alt_types as $alt_type ) {
				$alt_ext = wp_get_default_extension_for_mime_type( $alt_type );

				if ( ! $alt_ext ) {
					continue;
				}

				$alt_ext      = ".{$alt_ext}";
				$alt_filename = preg_replace( '|' . preg_quote( $lc_ext ) . '$|', $alt_ext, $filename );

				$alt_filenames[ $alt_ext ] = $alt_filename;
			}

			if ( ! empty( $alt_filenames ) ) {
				/*
				 * Thêm tên file gốc. Nó cần được kiểm tra lại
				 * cùng với các tên file thay thế khi $number tăng.
				 */
				$alt_filenames[ $lc_ext ] = $filename;

				// Đảm bảo không có vòng lặp vô hạn.
				$i = 0;

				while ( $i <= $count && _wp_check_alternate_file_names( $alt_filenames, $_dir, $files ) ) {
					$new_number = (int) $number + 1;

					foreach ( $alt_filenames as $alt_ext => $alt_filename ) {
						$alt_filenames[ $alt_ext ] = str_replace(
							array( "-{$number}{$alt_ext}", "{$number}{$alt_ext}" ),
							"-{$new_number}{$alt_ext}",
							$alt_filename
						);
					}

					/*
					 * Cũng cập nhật $number trong (đầu ra) $filename.
					 * Nếu phần mở rộng viết hoa, nó đã được thay bằng phiên bản viết thường.
					 */
					$filename = str_replace(
						array( "-{$number}{$lc_ext}", "{$number}{$lc_ext}" ),
						"-{$new_number}{$lc_ext}",
						$filename
					);

					$number = $new_number;
					++$i;
				}
			}
		}
	}

	/**
	 * Lọc kết quả khi tạo tên file duy nhất.
	 *
	 * @since 4.5.0
	 * @since 5.8.1 Các tham số `$alt_filenames` và `$number` được thêm vào.
	 *
	 * @param string        $filename                 Tên file duy nhất.
	 * @param string        $ext                      Phần mở rộng file. Ví dụ: ".png".
	 * @param string        $dir                      Đường dẫn thư mục.
	 * @param callable|null $unique_filename_callback Hàm callback tạo tên file duy nhất.
	 * @param string[]      $alt_filenames            Mảng tên file thay thế đã được kiểm tra xung đột.
	 * @param int|string    $number                   Số cao nhất được sử dụng để làm tên file duy nhất
	 *                                                hoặc chuỗi rỗng nếu không sử dụng.
	 */
	return apply_filters( 'wp_unique_filename', $filename, $ext, $dir, $unique_filename_callback, $alt_filenames, $number );
}

/**
 * Hàm hỗ trợ để kiểm tra xem mỗi tên file trong mảng có thể xung đột với các file hiện có hay không.
 *
 * @since 5.8.1
 * @access private
 *
 * @param string[] $filenames Mảng tên file cần kiểm tra.
 * @param string   $dir       Thư mục chứa các file.
 * @param array    $files     Mảng các file hiện có trong thư mục. Có thể rỗng.
 * @return bool True nếu tên file được kiểm tra có thể khớp với file hiện có, false nếu không.
 */
function _wp_check_alternate_file_names( $filenames, $dir, $files ) {
	foreach ( $filenames as $filename ) {
		if ( file_exists( $dir . $filename ) ) {
			return true;
		}

		if ( ! empty( $files ) && _wp_check_existing_file_names( $filename, $files ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Hàm hỗ trợ để kiểm tra xem tên file có thể khớp với tên file kích thước phụ của ảnh hiện có hay không.
 *
 * @since 5.3.1
 * @access private
 *
 * @param string $filename Tên file cần kiểm tra.
 * @param array  $files    Mảng các file hiện có trong thư mục.
 * @return bool True nếu tên file được kiểm tra có thể khớp với file hiện có, false nếu không.
 */
function _wp_check_existing_file_names( $filename, $files ) {
	$fname = pathinfo( $filename, PATHINFO_FILENAME );
	$ext   = pathinfo( $filename, PATHINFO_EXTENSION );

	// Trường hợp đặc biệt, tên file như `.ext`.
	if ( empty( $fname ) ) {
		return false;
	}

	if ( $ext ) {
		$ext = ".$ext";
	}

	$regex = '/^' . preg_quote( $fname ) . '-(?:\d+x\d+|scaled|rotated)' . preg_quote( $ext ) . '$/i';

	foreach ( $files as $file ) {
		if ( preg_match( $regex, $file ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Tạo file trong thư mục tải lên với nội dung đã cho.
 *
 * Nếu có lỗi, khóa 'error' sẽ tồn tại với thông báo lỗi.
 * Nếu thành công, khóa 'file' sẽ có đường dẫn file duy nhất, khóa 'url'
 * sẽ có liên kết đến file mới, và khóa 'error' sẽ được đặt thành false.
 *
 * Hàm này sẽ không di chuyển file đã tải lên đến thư mục tải lên. Nó sẽ
 * tạo file mới với nội dung trong tham số $bits. Nếu bạn di chuyển file
 * tải lên, đọc nội dung của file đã tải lên, sau đó bạn có thể truyền
 * tên file và nội dung cho hàm này, và nó sẽ thêm vào thư mục tải lên.
 *
 * Quyền truy cập sẽ được đặt tự động cho file mới bởi hàm này.
 *
 * @since 2.0.0
 *
 * @param string      $name       Tên file.
 * @param null|string $deprecated Không sử dụng. Đặt thành null.
 * @param string      $bits       Nội dung file.
 * @param string|null $time       Tùy chọn. Thời gian theo định dạng 'yyyy/mm'. Mặc định null.
 * @return array {
 *     Thông tin về file vừa được tải lên.
 *
 *     @type string       $file  Tên file của file vừa được tải lên.
 *     @type string       $url   URL của file đã tải lên.
 *     @type string       $type  Loại file.
 *     @type string|false $error Thông báo lỗi, nếu có lỗi xảy ra.
 * }
 */
function wp_upload_bits( $name, $deprecated, $bits, $time = null ) {
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.0.0' );
	}

	if ( empty( $name ) ) {
		return array( 'error' => __( 'Empty filename' ) );
	}

	$wp_filetype = wp_check_filetype( $name );
	if ( ! $wp_filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) ) {
		return array( 'error' => __( 'Sorry, you are not allowed to upload this file type.' ) );
	}

	$upload = wp_upload_dir( $time );

	if ( false !== $upload['error'] ) {
		return $upload;
	}

	/**
	 * Lọc xem có nên coi các bit tải lên là lỗi hay không.
	 *
	 * Trả về giá trị không phải mảng từ bộ lọc sẽ bỏ qua việc chuẩn bị các bit tải lên
	 * và trả về giá trị đó thay thế. Thông báo lỗi nên được trả về dưới dạng chuỗi.
	 *
	 * @since 3.0.0
	 *
	 * @param array|string $upload_bits_error Mảng dữ liệu bit tải lên, hoặc thông báo lỗi để trả về.
	 */
	$upload_bits_error = apply_filters(
		'wp_upload_bits',
		array(
			'name' => $name,
			'bits' => $bits,
			'time' => $time,
		)
	);
	if ( ! is_array( $upload_bits_error ) ) {
		$upload['error'] = $upload_bits_error;
		return $upload;
	}

	$filename = wp_unique_filename( $upload['path'], $name );

	$new_file = $upload['path'] . "/$filename";
	if ( ! wp_mkdir_p( dirname( $new_file ) ) ) {
		if ( str_starts_with( $upload['basedir'], ABSPATH ) ) {
			$error_path = str_replace( ABSPATH, '', $upload['basedir'] ) . $upload['subdir'];
		} else {
			$error_path = wp_basename( $upload['basedir'] ) . $upload['subdir'];
		}

		$message = sprintf(
			/* translators: %s: Directory path. */
			__( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
			$error_path
		);
		return array( 'error' => $message );
	}

	$ifp = @fopen( $new_file, 'wb' );
	if ( ! $ifp ) {
		return array(
			/* translators: %s: File name. */
			'error' => sprintf( __( 'Could not write file %s' ), $new_file ),
		);
	}

	fwrite( $ifp, $bits );
	fclose( $ifp );
	clearstatcache();

	// Đặt quyền truy cập file chính xác.
	$stat  = @ stat( dirname( $new_file ) );
	$perms = $stat['mode'] & 0007777;
	$perms = $perms & 0000666;
	chmod( $new_file, $perms );
	clearstatcache();

	// Tính toán URL.
	$url = $upload['url'] . "/$filename";

	if ( is_multisite() ) {
		clean_dirsize_cache( $new_file );
	}

	/** Bộ lọc này được ghi nhận trong wp-admin/includes/file.php */
	return apply_filters(
		'wp_handle_upload',
		array(
			'file'  => $new_file,
			'url'   => $url,
			'type'  => $wp_filetype['type'],
			'error' => false,
		),
		'sideload'
	);
}

/**
 * Lấy loại file dựa trên tên phần mở rộng.
 *
 * @since 2.5.0
 *
 * @param string $ext Phần mở rộng cần tìm.
 * @return string|void Loại file, ví dụ: audio, video, document, spreadsheet, v.v.
 */
function wp_ext2type( $ext ) {
	$ext = strtolower( $ext );

	$ext2type = wp_get_ext_types();
	foreach ( $ext2type as $type => $exts ) {
		if ( in_array( $ext, $exts, true ) ) {
			return $type;
		}
	}
}

/**
 * Trả về phần mở rộng đầu tiên khớp với loại mime,
 * được ánh xạ từ wp_get_mime_types().
 *
 * @since 5.8.1
 *
 * @param string $mime_type
 *
 * @return string|false
 */
function wp_get_default_extension_for_mime_type( $mime_type ) {
	$extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );

	if ( empty( $extensions[0] ) ) {
		return false;
	}

	return $extensions[0];
}

/**
 * Lấy loại file từ tên file.
 *
 * Bạn có thể tùy chọn định nghĩa mảng mime, nếu cần.
 *
 * @since 2.0.4
 *
 * @param string        $filename Tên file hoặc đường dẫn.
 * @param string[]|null $mimes    Tùy chọn. Mảng các loại mime được phép được đánh khóa bởi regex phần mở rộng file.
 *                                Mặc định là kết quả của get_allowed_mime_types().
 * @return array {
 *     Giá trị cho phần mở rộng và loại mime.
 *
 *     @type string|false $ext  Phần mở rộng file, hoặc false nếu file không khớp loại mime.
 *     @type string|false $type Loại mime file, hoặc false nếu file không khớp loại mime.
 * }
 */
function wp_check_filetype( $filename, $mimes = null ) {
	if ( empty( $mimes ) ) {
		$mimes = get_allowed_mime_types();
	}
	$type = false;
	$ext  = false;

	foreach ( $mimes as $ext_preg => $mime_match ) {
		$ext_preg = '!\.(' . $ext_preg . ')$!i';
		if ( preg_match( $ext_preg, $filename, $ext_matches ) ) {
			$type = $mime_match;
			$ext  = $ext_matches[1];
			break;
		}
	}

	return compact( 'ext', 'type' );
}

/**
 * Cố gắng xác định loại file thực của một file.
 *
 * Nếu không thể, phần mở rộng tên file sẽ được sử dụng để xác định loại.
 *
 * Nếu xác định được rằng phần mở rộng không khớp với loại thực của file,
 * thì giá trị "proper_filename" sẽ được đặt với tên file và phần mở rộng đúng.
 *
 * Hiện tại hàm này chỉ hỗ trợ đổi tên ảnh được xác thực qua wp_get_image_mime().
 *
 * @since 3.0.0
 *
 * @param string        $file     Đường dẫn đầy đủ đến file.
 * @param string        $filename Tên file (có thể khác với $file do $file nằm
 *                                trong thư mục tạm).
 * @param string[]|null $mimes    Tùy chọn. Mảng các loại mime được phép được đánh khóa bởi regex phần mở rộng file.
 *                                Mặc định là kết quả của get_allowed_mime_types().
 * @return array {
 *     Giá trị cho phần mở rộng, loại mime, và tên file đã sửa.
 *
 *     @type string|false $ext             Phần mở rộng file, hoặc false nếu file không khớp loại mime.
 *     @type string|false $type            Loại mime file, hoặc false nếu file không khớp loại mime.
 *     @type string|false $proper_filename Tên file với phần mở rộng đúng, hoặc false nếu không thể xác định.
 * }
 */
function wp_check_filetype_and_ext( $file, $filename, $mimes = null ) {
	$proper_filename = false;

	// Thực hiện xác thực phần mở rộng cơ bản và ánh xạ MIME.
	$wp_filetype = wp_check_filetype( $filename, $mimes );
	$ext         = $wp_filetype['ext'];
	$type        = $wp_filetype['type'];

	// Chúng ta không thể thực hiện xác thực thêm nếu không có file để làm việc.
	if ( ! file_exists( $file ) ) {
		return compact( 'ext', 'type', 'proper_filename' );
	}

	$real_mime = false;

	// Xác thực loại ảnh.
	if ( $type && str_starts_with( $type, 'image/' ) ) {

		// Cố gắng xác định loại ảnh thực sự là gì.
		$real_mime = wp_get_image_mime( $file );

		$heic_images_extensions = array(
			'heif',
			'heics',
			'heifs',
		);

		if ( $real_mime && ( $real_mime !== $type || in_array( $ext, $heic_images_extensions, true ) ) ) {
			/**
			 * Lọc danh sách ánh xạ loại mime ảnh với phần mở rộng tương ứng.
			 *
			 * @since 3.0.0
			 *
			 * @param array $mime_to_ext Mảng loại mime ảnh và phần mở rộng tương ứng.
			 */
			$mime_to_ext = apply_filters(
				'getimagesize_mimes_to_exts',
				array(
					'image/jpeg'          => 'jpg',
					'image/png'           => 'png',
					'image/gif'           => 'gif',
					'image/bmp'           => 'bmp',
					'image/tiff'          => 'tif',
					'image/webp'          => 'webp',
					'image/avif'          => 'avif',

					/*
					 * Về lý thuyết có/nên có phần mở rộng file tương ứng với các loại
					 * mime: .heif, .heics và .heifs. Tuy nhiên có vẻ như ảnh HEIC
					 * với bất kỳ loại mime nào thường có phần mở rộng file .heic.
					 * Giữ nguyên hiện trạng ở đây có vẻ tốt nhất cho tương thích.
					 */
					'image/heic'          => 'heic',
					'image/heif'          => 'heic',
					'image/heic-sequence' => 'heic',
					'image/heif-sequence' => 'heic',
				)
			);

			// Thay thế phần sau dấu chấm cuối cùng trong tên file bằng phần mở rộng đúng.
			if ( ! empty( $mime_to_ext[ $real_mime ] ) ) {
				$filename_parts = explode( '.', $filename );

				array_pop( $filename_parts );
				$filename_parts[] = $mime_to_ext[ $real_mime ];
				$new_filename     = implode( '.', $filename_parts );

				if ( $new_filename !== $filename ) {
					$proper_filename = $new_filename; // Đánh dấu rằng nó đã thay đổi.
				}

				// Định nghĩa lại phần mở rộng / MIME.
				$wp_filetype = wp_check_filetype( $new_filename, $mimes );
				$ext         = $wp_filetype['ext'];
				$type        = $wp_filetype['type'];
			} else {
				// Đặt lại $real_mime và thử xác thực lại.
				$real_mime = false;
			}
		}
	}

	// Xác thực các file chưa được xác thực trong các lần kiểm tra trước.
	if ( $type && ! $real_mime && extension_loaded( 'fileinfo' ) ) {
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $file );
		finfo_close( $finfo );

		$google_docs_types = array(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		);

		foreach ( $google_docs_types as $google_docs_type ) {
			/*
			 * finfo_file() có thể trả về kiểu mime trùng lặp cho Google docs,
			 * điều kiện này giảm nó xuống còn một thực thể duy nhất.
			 *
			 * @see https://bugs.php.net/bug.php?id=77784
			 * @see https://core.trac.wordpress.org/ticket/57898
			 */
			if ( 2 === substr_count( $real_mime, $google_docs_type ) ) {
				$real_mime = $google_docs_type;
			}
		}

		// fileinfo thường nhận dạng sai các tệp ít phổ biến thành một trong các kiểu này.
		$nonspecific_types = array(
			'application/octet-stream',
			'application/encrypted',
			'application/CDFV2-encrypted',
			'application/zip',
		);

		/*
		 * Nếu $real_mime không khớp với loại nội dung mà chúng ta mong đợi từ phần mở rộng của file,
		 * chúng ta cần thực hiện thêm một số kiểm tra. Các loại media và những loại được liệt kê trong $nonspecific_types
		 * được cho phép một số linh hoạt, nhưng mọi thứ khác phải khớp chính xác với loại nội dung thực.
		 */
		if ( in_array( $real_mime, $nonspecific_types, true ) ) {
			// File là loại nhị phân không cụ thể. Điều đó chấp nhận được nếu là loại thường có xu hướng là nhị phân.
			if ( ! in_array( substr( $type, 0, strcspn( $type, '/' ) ), array( 'application', 'video', 'audio' ), true ) ) {
				$type = false;
				$ext  = false;
			}
		} elseif ( str_starts_with( $real_mime, 'video/' ) || str_starts_with( $real_mime, 'audio/' ) ) {
			/*
			 * Đối với các loại này, chỉ cần loại chính phải khớp với giá trị thực.
			 * Điều này có nghĩa là các sai lệch phổ biến được bỏ qua: application/vnd.apple.numbers thường bị nhận dạng sai thành application/zip,
			 * và một số file media thường được đặt tên với phần mở rộng sai (.mov thay vì .mp4)
			 */
			if ( substr( $real_mime, 0, strcspn( $real_mime, '/' ) ) !== substr( $type, 0, strcspn( $type, '/' ) ) ) {
				$type = false;
				$ext  = false;
			}
		} elseif ( 'text/plain' === $real_mime ) {
			// Một vài loại file phổ biến đôi khi bị phát hiện là text/plain; cho phép những loại đó.
			if ( ! in_array(
				$type,
				array(
					'text/plain',
					'text/csv',
					'application/csv',
					'text/richtext',
					'text/tsv',
					'text/vtt',
				),
				true
			)
			) {
				$type = false;
				$ext  = false;
			}
		} elseif ( 'application/csv' === $real_mime ) {
			// Xử lý đặc biệt cho file CSV.
			if ( ! in_array(
				$type,
				array(
					'text/csv',
					'text/plain',
					'application/csv',
				),
				true
			)
			) {
				$type = false;
				$ext  = false;
			}
		} elseif ( 'text/rtf' === $real_mime ) {
			// Xử lý đặc biệt cho file RTF.
			if ( ! in_array(
				$type,
				array(
					'text/rtf',
					'text/plain',
					'application/rtf',
				),
				true
			)
			) {
				$type = false;
				$ext  = false;
			}
		} else {
			if ( $type !== $real_mime ) {
				/*
				 * Mọi thứ khác bao gồm image/* và application/*:
				 * Nếu loại nội dung thực không khớp với phần mở rộng file, coi đó là nguy hiểm.
				 */
				$type = false;
				$ext  = false;
			}
		}
	}

	// Loại mime phải được cho phép.
	if ( $type ) {
		$allowed = get_allowed_mime_types();

		if ( ! in_array( $type, $allowed, true ) ) {
			$type = false;
			$ext  = false;
		}
	}

	/**
	 * Lọc loại file "thực" của file đã cho.
	 *
	 * @since 3.0.0
	 * @since 5.1.0 Tham số $real_mime được thêm vào.
	 *
	 * @param array         $wp_check_filetype_and_ext {
	 *     Các giá trị cho phần mở rộng, loại mime và tên file đã sửa.
	 *
	 *     @type string|false $ext             Phần mở rộng file, hoặc false nếu file không khớp loại mime.
	 *     @type string|false $type            Loại mime của file, hoặc false nếu file không khớp loại mime.
	 *     @type string|false $proper_filename Tên file với phần mở rộng đúng, hoặc false nếu không thể xác định.
	 * }
	 * @param string        $file                      Đường dẫn đầy đủ đến file.
	 * @param string        $filename                  Tên của file (có thể khác với $file do
	 *                                                 $file nằm trong thư mục tạm).
	 * @param string[]|null $mimes                     Mảng các loại mime được khóa bởi regex phần mở rộng file, hoặc null nếu
	 *                                                 không có loại nào được cung cấp.
	 * @param string|false  $real_mime                 Loại mime thực tế hoặc false nếu không thể xác định loại.
	 */
	return apply_filters( 'wp_check_filetype_and_ext', compact( 'ext', 'type', 'proper_filename' ), $file, $filename, $mimes, $real_mime );
}

/**
 * Trả về loại mime thực của file hình ảnh.
 *
 * Hàm này phụ thuộc vào exif_imagetype() hoặc getimagesize() để xác định loại mime thực.
 *
 * @since 4.7.1
 * @since 5.8.0 Thêm hỗ trợ cho ảnh WebP.
 * @since 6.5.0 Thêm hỗ trợ cho ảnh AVIF.
 * @since 6.7.0 Thêm hỗ trợ cho ảnh HEIC.
 *
 * @param string $file Đường dẫn đầy đủ đến file.
 * @return string|false Loại mime thực tế hoặc false nếu không thể xác định loại.
 */
function wp_get_image_mime( $file ) {
	/*
	 * Sử dụng exif_imagetype() để kiểm tra mimetype nếu có sẵn hoặc dùng
	 * getimagesize() nếu exif không khả dụng. Nếu một trong hai hàm ném ra Exception
	 * chúng ta giả định rằng file không thể được xác thực.
	 */
	try {
		if ( is_callable( 'exif_imagetype' ) ) {
			$imagetype = exif_imagetype( $file );
			$mime      = ( $imagetype ) ? image_type_to_mime_type( $imagetype ) : false;
		} elseif ( function_exists( 'getimagesize' ) ) {
			// Không ẩn lỗi khi ở chế độ debug, trừ khi đang chạy unit test.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! defined( 'WP_RUN_CORE_TESTS' ) ) {
				// Không sử dụng wp_getimagesize() ở đây để tránh vòng lặp vô hạn.
				$imagesize = getimagesize( $file );
			} else {
				$imagesize = @getimagesize( $file );
			}

			$mime = ( isset( $imagesize['mime'] ) ) ? $imagesize['mime'] : false;
		} else {
			$mime = false;
		}

		if ( false !== $mime ) {
			return $mime;
		}

		$magic = file_get_contents( $file, false, null, 0, 12 );

		if ( false === $magic ) {
			return false;
		}

		/*
		 * Thêm phát hiện dự phòng WebP khi thư viện ảnh không hỗ trợ WebP.
		 * Lưu ý: giá trị phát hiện lấy từ LibWebP, xem
		 * https://github.com/webmproject/libwebp/blob/master/imageio/image_dec.c#L30
		 */
		$magic = bin2hex( $magic );
		if (
			// RIFF.
			( str_starts_with( $magic, '52494646' ) ) &&
			// WEBP.
			( 16 === strpos( $magic, '57454250' ) )
		) {
			$mime = 'image/webp';
		}

		/**
		 * Thêm phát hiện dự phòng AVIF khi thư viện ảnh không hỗ trợ AVIF.
		 *
		 * Phát hiện dựa trên mục 4.3.1 Định nghĩa hộp loại file của đặc tả ISO/IEC 14496-12
		 * và đặc tả AV1-AVIF, xem https://aomediacodec.github.io/av1-avif/v1.1.0.html#brands.
		 */

		// Chia chuỗi header thành các nhóm 4 byte.
		$magic = str_split( $magic, 8 );

		if ( isset( $magic[1] ) && isset( $magic[2] ) && 'ftyp' === hex2bin( $magic[1] ) ) {
			if ( 'avif' === hex2bin( $magic[2] ) || 'avis' === hex2bin( $magic[2] ) ) {
				$mime = 'image/avif';
			} elseif ( 'heic' === hex2bin( $magic[2] ) ) {
				$mime = 'image/heic';
			} elseif ( 'heif' === hex2bin( $magic[2] ) ) {
				$mime = 'image/heif';
			} else {
				/*
				 * Ảnh HEIC/HEIF và chuỗi ảnh/hoạt ảnh có thể có các chuỗi khác ở đây
				 * như mif1, msf1, v.v. Hiện tại dùng finfo_file() để phát hiện những loại này.
				 */
				if ( extension_loaded( 'fileinfo' ) ) {
					$fileinfo  = finfo_open( FILEINFO_MIME_TYPE );
					$mime_type = finfo_file( $fileinfo, $file );
					finfo_close( $fileinfo );

					if ( wp_is_heic_image_mime_type( $mime_type ) ) {
						$mime = $mime_type;
					}
				}
			}
		}
	} catch ( Exception $e ) {
		$mime = false;
	}

	return $mime;
}

/**
 * Lấy danh sách các loại mime và phần mở rộng file.
 *
 * @since 3.5.0
 * @since 4.2.0 Thêm hỗ trợ cho file GIMP (.xcf).
 * @since 4.9.2 Thêm hỗ trợ cho file Flac (.flac).
 * @since 4.9.6 Thêm hỗ trợ cho file AAC (.aac).
 * @since 6.8.0 Thêm hỗ trợ cho `audio/x-wav`.
 *
 * @return string[] Mảng các loại mime được khóa bởi regex phần mở rộng file tương ứng với các loại đó.
 */
function wp_get_mime_types() {
	/**
	 * Lọc danh sách các loại mime và phần mở rộng file.
	 *
	 * Bộ lọc này nên được sử dụng để thêm, không phải xóa, các loại mime. Để xóa
	 * các loại mime, sử dụng bộ lọc {@see 'upload_mimes'}.
	 *
	 * @since 3.5.0
	 *
	 * @param string[] $wp_get_mime_types Các loại mime được khóa bởi regex phần mở rộng file
	 *                                    tương ứng với các loại đó.
	 */
	return apply_filters(
		'mime_types',
		array(
			// Định dạng ảnh.
			'jpg|jpeg|jpe'                 => 'image/jpeg',
			'gif'                          => 'image/gif',
			'png'                          => 'image/png',
			'bmp'                          => 'image/bmp',
			'tiff|tif'                     => 'image/tiff',
			'webp'                         => 'image/webp',
			'avif'                         => 'image/avif',
			'ico'                          => 'image/x-icon',

			// TODO: Cần cải thiện. Tất cả ảnh với các loại mime sau dường như có phần mở rộng file .heic.
			'heic'                         => 'image/heic',
			'heif'                         => 'image/heif',
			'heics'                        => 'image/heic-sequence',
			'heifs'                        => 'image/heif-sequence',

			// Định dạng video.
			'asf|asx'                      => 'video/x-ms-asf',
			'wmv'                          => 'video/x-ms-wmv',
			'wmx'                          => 'video/x-ms-wmx',
			'wm'                           => 'video/x-ms-wm',
			'avi'                          => 'video/avi',
			'divx'                         => 'video/divx',
			'flv'                          => 'video/x-flv',
			'mov|qt'                       => 'video/quicktime',
			'mpeg|mpg|mpe'                 => 'video/mpeg',
			'mp4|m4v'                      => 'video/mp4',
			'ogv'                          => 'video/ogg',
			'webm'                         => 'video/webm',
			'mkv'                          => 'video/x-matroska',
			'3gp|3gpp'                     => 'video/3gpp',  // Cũng có thể là audio.
			'3g2|3gp2'                     => 'video/3gpp2', // Cũng có thể là audio.
			// Định dạng văn bản.
			'txt|asc|c|cc|h|srt'           => 'text/plain',
			'csv'                          => 'text/csv',
			'tsv'                          => 'text/tab-separated-values',
			'ics'                          => 'text/calendar',
			'rtx'                          => 'text/richtext',
			'css'                          => 'text/css',
			'htm|html'                     => 'text/html',
			'vtt'                          => 'text/vtt',
			'dfxp'                         => 'application/ttaf+xml',
			// Định dạng audio.
			'mp3|m4a|m4b'                  => 'audio/mpeg',
			'aac'                          => 'audio/aac',
			'ra|ram'                       => 'audio/x-realaudio',
			'wav|x-wav'                    => 'audio/wav',
			'ogg|oga'                      => 'audio/ogg',
			'flac'                         => 'audio/flac',
			'mid|midi'                     => 'audio/midi',
			'wma'                          => 'audio/x-ms-wma',
			'wax'                          => 'audio/x-ms-wax',
			'mka'                          => 'audio/x-matroska',
			// Định dạng ứng dụng khác.
			'rtf'                          => 'application/rtf',
			'js'                           => 'application/javascript',
			'pdf'                          => 'application/pdf',
			'swf'                          => 'application/x-shockwave-flash',
			'class'                        => 'application/java',
			'tar'                          => 'application/x-tar',
			'zip'                          => 'application/zip',
			'gz|gzip'                      => 'application/x-gzip',
			'rar'                          => 'application/rar',
			'7z'                           => 'application/x-7z-compressed',
			'exe'                          => 'application/x-msdownload',
			'psd'                          => 'application/octet-stream',
			'xcf'                          => 'application/octet-stream',
			// Định dạng MS Office.
			'doc'                          => 'application/msword',
			'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
			'wri'                          => 'application/vnd.ms-write',
			'xla|xls|xlt|xlw'              => 'application/vnd.ms-excel',
			'mdb'                          => 'application/vnd.ms-access',
			'mpp'                          => 'application/vnd.ms-project',
			'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
			'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',
			'xlsx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xlsm'                         => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsb'                         => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xltx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'xltm'                         => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xlam'                         => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'pptx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'pptm'                         => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'ppsx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'ppsm'                         => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'potx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'potm'                         => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'ppam'                         => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'sldx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'sldm'                         => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
			'oxps'                         => 'application/oxps',
			'xps'                          => 'application/vnd.ms-xpsdocument',
			// Định dạng OpenOffice.
			'odt'                          => 'application/vnd.oasis.opendocument.text',
			'odp'                          => 'application/vnd.oasis.opendocument.presentation',
			'ods'                          => 'application/vnd.oasis.opendocument.spreadsheet',
			'odg'                          => 'application/vnd.oasis.opendocument.graphics',
			'odc'                          => 'application/vnd.oasis.opendocument.chart',
			'odb'                          => 'application/vnd.oasis.opendocument.database',
			'odf'                          => 'application/vnd.oasis.opendocument.formula',
			// Định dạng WordPerfect.
			'wp|wpd'                       => 'application/wordperfect',
			// Định dạng iWork.
			'key'                          => 'application/vnd.apple.keynote',
			'numbers'                      => 'application/vnd.apple.numbers',
			'pages'                        => 'application/vnd.apple.pages',
		)
	);
}

/**
 * Lấy danh sách các phần mở rộng file phổ biến và loại của chúng.
 *
 * @since 4.6.0
 *
 * @return array[] Mảng đa chiều các loại phần mở rộng file được khóa bởi loại file.
 */
function wp_get_ext_types() {

	/**
	 * Lọc loại file dựa trên tên phần mở rộng.
	 *
	 * @since 2.5.0
	 *
	 * @see wp_ext2type()
	 *
	 * @param array[] $ext2type Mảng đa chiều các loại phần mở rộng file được khóa bởi loại file.
	 */
	return apply_filters(
		'ext2type',
		array(
			'image'       => array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'heif', 'webp', 'avif' ),
			'audio'       => array( 'aac', 'ac3', 'aif', 'aiff', 'flac', 'm3a', 'm4a', 'm4b', 'mka', 'mp1', 'mp2', 'mp3', 'ogg', 'oga', 'ram', 'wav', 'wma' ),
			'video'       => array( '3g2', '3gp', '3gpp', 'asf', 'avi', 'divx', 'dv', 'flv', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt', 'rm', 'vob', 'wmv' ),
			'document'    => array( 'doc', 'docx', 'docm', 'dotm', 'odt', 'pages', 'pdf', 'xps', 'oxps', 'rtf', 'wp', 'wpd', 'psd', 'xcf' ),
			'spreadsheet' => array( 'numbers', 'ods', 'xls', 'xlsx', 'xlsm', 'xlsb' ),
			'interactive' => array( 'swf', 'key', 'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'ppsm', 'sldx', 'sldm', 'odp' ),
			'text'        => array( 'asc', 'csv', 'tsv', 'txt' ),
			'archive'     => array( 'bz2', 'cab', 'dmg', 'gz', 'rar', 'sea', 'sit', 'sqx', 'tar', 'tgz', 'zip', '7z' ),
			'code'        => array( 'css', 'htm', 'html', 'php', 'js' ),
		)
	);
}

/**
 * Wrapper cho PHP filesize với bộ lọc và ép kiểu kết quả thành số nguyên.
 *
 * @since 6.0.0
 *
 * @link https://www.php.net/manual/en/function.filesize.php
 *
 * @param string $path Đường dẫn đến file.
 * @return int Kích thước file tính bằng byte, hoặc 0 trong trường hợp có lỗi.
 */
function wp_filesize( $path ) {
	/**
	 * Lọc kết quả của wp_filesize trước khi hàm PHP được chạy.
	 *
	 * @since 6.0.0
	 *
	 * @param null|int $size Giá trị chưa lọc. Trả về int từ callback sẽ bỏ qua lời gọi filesize.
	 * @param string   $path Đường dẫn đến file.
	 */
	$size = apply_filters( 'pre_wp_filesize', null, $path );

	if ( is_int( $size ) ) {
		return $size;
	}

	$size = file_exists( $path ) ? (int) filesize( $path ) : 0;

	/**
	 * Lọc kích thước của file.
	 *
	 * @since 6.0.0
	 *
	 * @param int    $size Kết quả của PHP filesize trên file.
	 * @param string $path Đường dẫn đến file.
	 */
	return (int) apply_filters( 'wp_filesize', $size, $path );
}

/**
 * Lấy danh sách các loại mime và phần mở rộng file được phép.
 *
 * @since 2.8.6
 *
 * @param int|WP_User $user Tùy chọn. Người dùng cần kiểm tra. Mặc định là người dùng hiện tại.
 * @return string[] Mảng các loại mime được khóa bởi regex phần mở rộng file tương ứng
 *                  với các loại đó.
 */
function get_allowed_mime_types( $user = null ) {
	$t = wp_get_mime_types();

	unset( $t['swf'], $t['exe'] );
	if ( function_exists( 'current_user_can' ) ) {
		$unfiltered = $user ? user_can( $user, 'unfiltered_html' ) : current_user_can( 'unfiltered_html' );
	}

	if ( empty( $unfiltered ) ) {
		unset( $t['htm|html'], $t['js'] );
	}

	/**
	 * Lọc danh sách các loại mime và phần mở rộng file được phép.
	 *
	 * @since 2.0.0
	 *
	 * @param array            $t    Các loại mime được khóa bởi regex phần mở rộng file tương ứng với các loại đó.
	 * @param int|WP_User|null $user ID người dùng, đối tượng User hoặc null nếu không được cung cấp (chỉ người dùng hiện tại).
	 */
	return apply_filters( 'upload_mimes', $t, $user );
}

/**
 * Hiển thị thông báo "Bạn Có Chắc Không" để xác nhận hành động đang thực hiện.
 *
 * Nếu hành động có thông báo giải thích nonce, thì nó sẽ được hiển thị
 * cùng với thông báo "Bạn có chắc không?".
 *
 * @since 2.0.4
 *
 * @param string $action Hành động nonce.
 */
function wp_nonce_ays( $action ) {
	// Tiêu đề và mã phản hồi mặc định.
	$title         = __( 'An error occurred.' );
	$response_code = 403;

	if ( 'log-out' === $action ) {
		$title = sprintf(
			/* translators: %s: Site title. */
			__( 'You are attempting to log out of %s' ),
			get_bloginfo( 'name' )
		);

		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';

		$html  = $title;
		$html .= '</p><p>';
		$html .= sprintf(
			/* translators: %s: Logout URL. */
			__( 'Do you really want to <a href="%s">log out</a>?' ),
			wp_logout_url( $redirect_to )
		);
	} else {
		$html = __( 'The link you followed has expired.' );

		if ( wp_get_referer() ) {
			$wp_http_referer = remove_query_arg( 'updated', wp_get_referer() );
			$wp_http_referer = wp_validate_redirect( sanitize_url( $wp_http_referer ) );

			$html .= '</p><p>';
			$html .= sprintf(
				'<a href="%s">%s</a>',
				esc_url( $wp_http_referer ),
				__( 'Please try again.' )
			);
		}
	}

	wp_die( $html, $title, $response_code );
}

/**
 * Dừng thực thi WordPress và hiển thị trang HTML với thông báo lỗi.
 *
 * Hàm này bổ sung cho hàm `die()` của PHP. Điểm khác biệt là
 * HTML sẽ được hiển thị cho người dùng. Khuyến nghị sử dụng hàm này
 * chỉ khi quá trình thực thi không nên tiếp tục nữa. Không khuyến nghị
 * gọi hàm này quá thường xuyên, và nên cố xử lý càng nhiều lỗi càng tốt
 * một cách im lặng hoặc nhẹ nhàng hơn.
 *
 * Như một cách viết tắt, mã phản hồi HTTP mong muốn có thể được truyền dưới dạng số nguyên vào
 * tham số `$title` (tiêu đề mặc định sẽ được áp dụng) hoặc tham số `$args`.
 *
 * @since 2.0.4
 * @since 4.1.0 Các tham số `$title` và `$args` được thay đổi để tùy chọn chấp nhận
 *              một số nguyên để sử dụng làm mã phản hồi.
 * @since 5.1.0 Các tham số `$link_url`, `$link_text` và `$exit` được thêm vào.
 * @since 5.3.0 Tham số `$charset` được thêm vào.
 * @since 5.5.0 Tham số `$text_direction` có ưu tiên cao hơn get_language_attributes()
 *              trong handler mặc định.
 *
 * @global WP_Query $wp_query Đối tượng WordPress Query.
 *
 * @param string|WP_Error  $message Tùy chọn. Thông báo lỗi. Nếu đây là đối tượng WP_Error,
 *                                  và không phải là yêu cầu Ajax hoặc XML-RPC, các thông báo lỗi sẽ được sử dụng.
 *                                  Mặc định chuỗi rỗng.
 * @param string|int       $title   Tùy chọn. Tiêu đề lỗi. Nếu `$message` là đối tượng `WP_Error`,
 *                                  dữ liệu lỗi với khóa 'title' có thể được sử dụng để chỉ định tiêu đề.
 *                                  Nếu `$title` là số nguyên, thì nó được coi là mã phản hồi.
 *                                  Mặc định chuỗi rỗng.
 * @param string|array|int $args {
 *     Tùy chọn. Các tham số để điều khiển hành vi. Nếu `$args` là số nguyên, thì nó được coi
 *     là mã phản hồi. Mặc định mảng rỗng.
 *
 *     @type int    $response       Mã phản hồi HTTP. Mặc định 200 cho yêu cầu Ajax, 500 cho các trường hợp khác.
 *     @type string $link_url       URL để chèn liên kết đến. Chỉ hoạt động kết hợp với $link_text.
 *                                  Mặc định chuỗi rỗng.
 *     @type string $link_text      Nhãn cho liên kết cần chèn. Chỉ hoạt động kết hợp với $link_url.
 *                                  Mặc định chuỗi rỗng.
 *     @type bool   $back_link      Có chèn liên kết quay lại hay không. Mặc định false.
 *     @type string $text_direction Hướng văn bản. Chỉ hữu ích nội bộ, khi WordPress vẫn
 *                                  đang tải và locale của trang chưa được thiết lập. Chấp nhận 'rtl' và 'ltr'.
 *                                  Mặc định là giá trị của is_rtl().
 *     @type string $charset        Bộ ký tự của đầu ra HTML. Mặc định 'utf-8'.
 *     @type string $code           Mã lỗi để sử dụng. Mặc định là 'wp_die', hoặc mã lỗi chính nếu $message
 *                                  là WP_Error.
 *     @type bool   $exit           Có thoát tiến trình sau khi hoàn thành hay không. Mặc định true.
 * }
 */
function wp_die( $message = '', $title = '', $args = array() ) {
	global $wp_query;

	if ( is_int( $args ) ) {
		$args = array( 'response' => $args );
	} elseif ( is_int( $title ) ) {
		$args  = array( 'response' => $title );
		$title = '';
	}

	if ( wp_doing_ajax() ) {
		/**
		 * Lọc callback để dừng thực thi WordPress cho các yêu cầu Ajax.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $callback Tên hàm callback.
		 */
		$callback = apply_filters( 'wp_die_ajax_handler', '_ajax_wp_die_handler' );
	} elseif ( wp_is_json_request() ) {
		/**
		 * Lọc callback để dừng thực thi WordPress cho các yêu cầu JSON.
		 *
		 * @since 5.1.0
		 *
		 * @param callable $callback Tên hàm callback.
		 */
		$callback = apply_filters( 'wp_die_json_handler', '_json_wp_die_handler' );
	} elseif ( wp_is_serving_rest_request() && wp_is_jsonp_request() ) {
		/**
		 * Lọc callback để dừng thực thi WordPress cho các yêu cầu JSONP REST.
		 *
		 * @since 5.2.0
		 *
		 * @param callable $callback Tên hàm callback.
		 */
		$callback = apply_filters( 'wp_die_jsonp_handler', '_jsonp_wp_die_handler' );
	} elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		/**
		 * Lọc callback để dừng thực thi WordPress cho các yêu cầu XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $callback Tên hàm callback.
		 */
		$callback = apply_filters( 'wp_die_xmlrpc_handler', '_xmlrpc_wp_die_handler' );
	} elseif ( wp_is_xml_request()
		|| isset( $wp_query ) &&
			( function_exists( 'is_feed' ) && is_feed()
			|| function_exists( 'is_comment_feed' ) && is_comment_feed()
			|| function_exists( 'is_trackback' ) && is_trackback() ) ) {
		/**
		 * Lọc callback để dừng thực thi WordPress cho các yêu cầu XML.
		 *
		 * @since 5.2.0
		 *
		 * @param callable $callback Tên hàm callback.
		 */
		$callback = apply_filters( 'wp_die_xml_handler', '_xml_wp_die_handler' );
	} else {
		/**
		 * Lọc callback để dừng thực thi WordPress cho tất cả yêu cầu không phải Ajax, không phải JSON, không phải XML.
		 *
		 * @since 3.0.0
		 *
		 * @param callable $callback Tên hàm callback.
		 */
		$callback = apply_filters( 'wp_die_handler', '_default_wp_die_handler' );
	}

	call_user_func( $callback, $message, $title, $args );
}

/**
 * Dừng thực thi WordPress và hiển thị trang HTML với thông báo lỗi.
 *
 * Đây là handler mặc định cho wp_die(). Nếu bạn muốn tùy chỉnh,
 * bạn có thể ghi đè bằng bộ lọc {@see 'wp_die_handler'} trong wp_die().
 *
 * @since 3.0.0
 * @access private
 *
 * @param string|WP_Error $message Thông báo lỗi hoặc đối tượng WP_Error.
 * @param string          $title   Tùy chọn. Tiêu đề lỗi. Mặc định chuỗi rỗng.
 * @param string|array    $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _default_wp_die_handler( $message, $title = '', $args = array() ) {
	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	if ( is_string( $message ) ) {
		if ( ! empty( $parsed_args['additional_errors'] ) ) {
			$message = array_merge(
				array( $message ),
				wp_list_pluck( $parsed_args['additional_errors'], 'message' )
			);
			$message = "<ul>\n\t\t<li>" . implode( "</li>\n\t\t<li>", $message ) . "</li>\n\t</ul>";
		}

		$message = sprintf(
			'<div class="wp-die-message">%s</div>',
			$message
		);
	}

	$have_gettext = function_exists( '__' );

	if ( ! empty( $parsed_args['link_url'] ) && ! empty( $parsed_args['link_text'] ) ) {
		$link_url = $parsed_args['link_url'];
		if ( function_exists( 'esc_url' ) ) {
			$link_url = esc_url( $link_url );
		}
		$link_text = $parsed_args['link_text'];
		$message  .= "\n<p><a href='{$link_url}'>{$link_text}</a></p>";
	}

	if ( isset( $parsed_args['back_link'] ) && $parsed_args['back_link'] ) {
		$back_text = $have_gettext ? __( '&laquo; Back' ) : '&laquo; Back';
		$message  .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
	}

	if ( ! did_action( 'admin_head' ) ) :
		if ( ! headers_sent() ) {
			header( "Content-Type: text/html; charset={$parsed_args['charset']}" );
			status_header( $parsed_args['response'] );
			nocache_headers();
		}

		$text_direction = $parsed_args['text_direction'];
		$dir_attr       = "dir='$text_direction'";

		/*
		 * Nếu `text_direction` không được truyền một cách rõ ràng,
		 * sử dụng get_language_attributes() nếu có sẵn.
		 */
		if ( empty( $args['text_direction'] )
			&& function_exists( 'language_attributes' ) && function_exists( 'is_rtl' )
		) {
			$dir_attr = get_language_attributes();
		}
		?>
<!DOCTYPE html>
<html <?php echo $dir_attr; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $parsed_args['charset']; ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php
		if ( function_exists( 'wp_robots' ) && function_exists( 'wp_robots_no_robots' ) && function_exists( 'add_filter' ) ) {
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
			// Ngăn cảnh báo do $wp_query không tồn tại.
			remove_filter( 'wp_robots', 'wp_robots_noindex_embeds' );
			remove_filter( 'wp_robots', 'wp_robots_noindex_search' );
			wp_robots();
		}
		?>
	<title><?php echo $title; ?></title>
	<style type="text/css">
		html {
			background: #f1f1f1;
		}
		body {
			background: #fff;
			border: 1px solid #ccd0d4;
			color: #444;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 2em auto;
			padding: 1em 2em;
			max-width: 700px;
			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
		h1 {
			border-bottom: 1px solid #dadada;
			clear: both;
			color: #666;
			font-size: 24px;
			margin: 30px 0 0 0;
			padding: 0;
			padding-bottom: 7px;
		}
		#error-page {
			margin-top: 50px;
		}
		#error-page p,
		#error-page .wp-die-message {
			font-size: 14px;
			line-height: 1.5;
			margin: 25px 0 20px;
		}
		#error-page code {
			font-family: Consolas, Monaco, monospace;
		}
		ul li {
			margin-bottom: 10px;
			font-size: 14px ;
		}
		a {
			color: #2271b1;
		}
		a:hover,
		a:active {
			color: #135e96;
		}
		a:focus {
			color: #043959;
			box-shadow: 0 0 0 2px #2271b1;
			outline: 2px solid transparent;
		}
		.button {
			background: #f3f5f6;
			border: 1px solid #016087;
			color: #016087;
			display: inline-block;
			text-decoration: none;
			font-size: 13px;
			line-height: 2;
			height: 28px;
			margin: 0;
			padding: 0 10px 1px;
			cursor: pointer;
			-webkit-border-radius: 3px;
			-webkit-appearance: none;
			border-radius: 3px;
			white-space: nowrap;
			-webkit-box-sizing: border-box;
			-moz-box-sizing:    border-box;
			box-sizing:         border-box;

			vertical-align: top;
		}

		.button.button-large {
			line-height: 2.30769231;
			min-height: 32px;
			padding: 0 12px;
		}

		.button:hover,
		.button:focus {
			background: #f1f1f1;
		}

		.button:focus {
			background: #f3f5f6;
			border-color: #007cba;
			-webkit-box-shadow: 0 0 0 1px #007cba;
			box-shadow: 0 0 0 1px #007cba;
			color: #016087;
			outline: 2px solid transparent;
			outline-offset: 0;
		}

		.button:active {
			background: #f3f5f6;
			border-color: #7e8993;
			-webkit-box-shadow: none;
			box-shadow: none;
		}

		<?php
		if ( 'rtl' === $text_direction ) {
			echo 'body { font-family: Tahoma, Arial; }';
		}
		?>
	</style>
</head>
<body id="error-page">
<?php endif; // ! did_action( 'admin_head' ) ?>
	<?php echo $message; ?>
</body>
</html>
	<?php
	if ( $parsed_args['exit'] ) {
		die();
	}
}

/**
 * Dừng thực thi WordPress và hiển thị phản hồi Ajax với thông báo lỗi.
 *
 * Đây là handler cho wp_die() khi xử lý các yêu cầu Ajax.
 *
 * @since 3.4.0
 * @access private
 *
 * @param string       $message Thông báo lỗi.
 * @param string       $title   Tùy chọn. Tiêu đề lỗi (không sử dụng). Mặc định chuỗi rỗng.
 * @param string|array $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _ajax_wp_die_handler( $message, $title = '', $args = array() ) {
	// Đặt 'response' mặc định là 200 cho các yêu cầu Ajax.
	$args = wp_parse_args(
		$args,
		array( 'response' => 200 )
	);

	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	if ( ! headers_sent() ) {
		// Điều này có chủ ý. Để tương thích ngược, hỗ trợ truyền null ở đây.
		if ( null !== $args['response'] ) {
			status_header( $parsed_args['response'] );
		}
		nocache_headers();
	}

	if ( is_scalar( $message ) ) {
		$message = (string) $message;
	} else {
		$message = '0';
	}

	if ( $parsed_args['exit'] ) {
		die( $message );
	}

	echo $message;
}

/**
 * Dừng thực thi WordPress và hiển thị phản hồi JSON với thông báo lỗi.
 *
 * Đây là handler cho wp_die() khi xử lý các yêu cầu JSON.
 *
 * @since 5.1.0
 * @access private
 *
 * @param string       $message Thông báo lỗi.
 * @param string       $title   Tùy chọn. Tiêu đề lỗi. Mặc định chuỗi rỗng.
 * @param string|array $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _json_wp_die_handler( $message, $title = '', $args = array() ) {
	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	$data = array(
		'code'              => $parsed_args['code'],
		'message'           => $message,
		'data'              => array(
			'status' => $parsed_args['response'],
		),
		'additional_errors' => $parsed_args['additional_errors'],
	);

	if ( isset( $parsed_args['error_data'] ) ) {
		$data['data']['error'] = $parsed_args['error_data'];
	}

	if ( ! headers_sent() ) {
		header( "Content-Type: application/json; charset={$parsed_args['charset']}" );
		if ( null !== $parsed_args['response'] ) {
			status_header( $parsed_args['response'] );
		}
		nocache_headers();
	}

	echo wp_json_encode( $data );
	if ( $parsed_args['exit'] ) {
		die();
	}
}

/**
 * Dừng thực thi WordPress và hiển thị phản hồi JSONP với thông báo lỗi.
 *
 * Đây là handler cho wp_die() khi xử lý các yêu cầu JSONP.
 *
 * @since 5.2.0
 * @access private
 *
 * @param string       $message Thông báo lỗi.
 * @param string       $title   Tùy chọn. Tiêu đề lỗi. Mặc định chuỗi rỗng.
 * @param string|array $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _jsonp_wp_die_handler( $message, $title = '', $args = array() ) {
	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	$data = array(
		'code'              => $parsed_args['code'],
		'message'           => $message,
		'data'              => array(
			'status' => $parsed_args['response'],
		),
		'additional_errors' => $parsed_args['additional_errors'],
	);

	if ( isset( $parsed_args['error_data'] ) ) {
		$data['data']['error'] = $parsed_args['error_data'];
	}

	if ( ! headers_sent() ) {
		header( "Content-Type: application/javascript; charset={$parsed_args['charset']}" );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );
		if ( null !== $parsed_args['response'] ) {
			status_header( $parsed_args['response'] );
		}
		nocache_headers();
	}

	$result         = wp_json_encode( $data );
	$jsonp_callback = $_GET['_jsonp'];
	echo '/**/' . $jsonp_callback . '(' . $result . ')';
	if ( $parsed_args['exit'] ) {
		die();
	}
}

/**
 * Dừng thực thi WordPress và hiển thị phản hồi XML với thông báo lỗi.
 *
 * Đây là handler cho wp_die() khi xử lý các yêu cầu XMLRPC.
 *
 * @since 3.2.0
 * @access private
 *
 * @global wp_xmlrpc_server $wp_xmlrpc_server
 *
 * @param string       $message Thông báo lỗi.
 * @param string       $title   Tùy chọn. Tiêu đề lỗi. Mặc định chuỗi rỗng.
 * @param string|array $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _xmlrpc_wp_die_handler( $message, $title = '', $args = array() ) {
	global $wp_xmlrpc_server;

	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	if ( ! headers_sent() ) {
		nocache_headers();
	}

	if ( $wp_xmlrpc_server ) {
		$error = new IXR_Error( $parsed_args['response'], $message );
		$wp_xmlrpc_server->output( $error->getXml() );
	}
	if ( $parsed_args['exit'] ) {
		die();
	}
}

/**
 * Dừng thực thi WordPress và hiển thị phản hồi XML với thông báo lỗi.
 *
 * Đây là handler cho wp_die() khi xử lý các yêu cầu XML.
 *
 * @since 5.2.0
 * @access private
 *
 * @param string       $message Thông báo lỗi.
 * @param string       $title   Tùy chọn. Tiêu đề lỗi. Mặc định chuỗi rỗng.
 * @param string|array $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _xml_wp_die_handler( $message, $title = '', $args = array() ) {
	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	$message = htmlspecialchars( $message );
	$title   = htmlspecialchars( $title );

	$xml = <<<EOD
<error>
    <code>{$parsed_args['code']}</code>
    <title><![CDATA[{$title}]]></title>
    <message><![CDATA[{$message}]]></message>
    <data>
        <status>{$parsed_args['response']}</status>
    </data>
</error>

EOD;

	if ( ! headers_sent() ) {
		header( "Content-Type: text/xml; charset={$parsed_args['charset']}" );
		if ( null !== $parsed_args['response'] ) {
			status_header( $parsed_args['response'] );
		}
		nocache_headers();
	}

	echo $xml;
	if ( $parsed_args['exit'] ) {
		die();
	}
}

/**
 * Dừng thực thi WordPress và hiển thị thông báo lỗi.
 *
 * Đây là handler cho wp_die() khi xử lý các yêu cầu APP.
 *
 * @since 3.4.0
 * @since 5.1.0 Thêm các tham số $title và $args.
 * @access private
 *
 * @param string       $message Tùy chọn. Phản hồi để in ra. Mặc định chuỗi rỗng.
 * @param string       $title   Tùy chọn. Tiêu đề lỗi (không sử dụng). Mặc định chuỗi rỗng.
 * @param string|array $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 */
function _scalar_wp_die_handler( $message = '', $title = '', $args = array() ) {
	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	if ( $parsed_args['exit'] ) {
		if ( is_scalar( $message ) ) {
			die( (string) $message );
		}
		die();
	}

	if ( is_scalar( $message ) ) {
		echo (string) $message;
	}
}

/**
 * Xử lý các tham số được truyền cho wp_die() một cách nhất quán cho các handler của nó.
 *
 * @since 5.1.0
 * @access private
 *
 * @param string|WP_Error $message Thông báo lỗi hoặc đối tượng WP_Error.
 * @param string          $title   Tùy chọn. Tiêu đề lỗi. Mặc định chuỗi rỗng.
 * @param string|array    $args    Tùy chọn. Các tham số để điều khiển hành vi. Mặc định mảng rỗng.
 * @return array {
 *     Các tham số đã xử lý.
 *
 *     @type string $0 Thông báo lỗi.
 *     @type string $1 Tiêu đề lỗi.
 *     @type array  $2 Các tham số để điều khiển hành vi.
 * }
 */
function _wp_die_process_input( $message, $title = '', $args = array() ) {
	$defaults = array(
		'response'          => 0,
		'code'              => '',
		'exit'              => true,
		'back_link'         => false,
		'link_url'          => '',
		'link_text'         => '',
		'text_direction'    => '',
		'charset'           => 'utf-8',
		'additional_errors' => array(),
	);

	$args = wp_parse_args( $args, $defaults );

	if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
		if ( ! empty( $message->errors ) ) {
			$errors = array();
			foreach ( (array) $message->errors as $error_code => $error_messages ) {
				foreach ( (array) $error_messages as $error_message ) {
					$errors[] = array(
						'code'    => $error_code,
						'message' => $error_message,
						'data'    => $message->get_error_data( $error_code ),
					);
				}
			}

			$message = $errors[0]['message'];
			if ( empty( $args['code'] ) ) {
				$args['code'] = $errors[0]['code'];
			}
			if ( empty( $args['response'] ) && is_array( $errors[0]['data'] ) && ! empty( $errors[0]['data']['status'] ) ) {
				$args['response'] = $errors[0]['data']['status'];
			}
			if ( empty( $title ) && is_array( $errors[0]['data'] ) && ! empty( $errors[0]['data']['title'] ) ) {
				$title = $errors[0]['data']['title'];
			}
			if ( WP_DEBUG_DISPLAY && is_array( $errors[0]['data'] ) && ! empty( $errors[0]['data']['error'] ) ) {
				$args['error_data'] = $errors[0]['data']['error'];
			}

			unset( $errors[0] );
			$args['additional_errors'] = array_values( $errors );
		} else {
			$message = '';
		}
	}

	$have_gettext = function_exists( '__' );

	// $title và các $args cụ thể này phải luôn có giá trị không rỗng.
	if ( empty( $args['code'] ) ) {
		$args['code'] = 'wp_die';
	}
	if ( empty( $args['response'] ) ) {
		$args['response'] = 500;
	}
	if ( empty( $title ) ) {
		$title = $have_gettext ? __( 'WordPress &rsaquo; Error' ) : 'WordPress &rsaquo; Error';
	}
	if ( empty( $args['text_direction'] ) || ! in_array( $args['text_direction'], array( 'ltr', 'rtl' ), true ) ) {
		$args['text_direction'] = 'ltr';
		if ( function_exists( 'is_rtl' ) && is_rtl() ) {
			$args['text_direction'] = 'rtl';
		}
	}

	if ( ! empty( $args['charset'] ) ) {
		$args['charset'] = _canonical_charset( $args['charset'] );
	}

	return array( $message, $title, $args );
}

/**
 * Mã hóa một biến thành JSON, với một số kiểm tra độ tin cậy.
 *
 * @since 4.1.0
 * @since 5.3.0 Không còn xử lý hỗ trợ cho PHP < 5.6.
 * @since 6.5.0 Tham số `$data` được đổi tên thành `$value` và
 *              tham số `$options` thành `$flags` để tương đồng với PHP.
 *
 * @param mixed $value Biến (thường là mảng hoặc đối tượng) để mã hóa thành JSON.
 * @param int   $flags Tùy chọn. Các tùy chọn để truyền cho json_encode(). Mặc định 0.
 * @param int   $depth Tùy chọn. Độ sâu tối đa để duyệt qua $value. Phải
 *                     lớn hơn 0. Mặc định 512.
 * @return string|false Chuỗi đã mã hóa JSON, hoặc false nếu không thể mã hóa.
 */
function wp_json_encode( $value, $flags = 0, $depth = 512 ) {
	$json = json_encode( $value, $flags, $depth );

	// Nếu json_encode() thành công, không cần kiểm tra độ tin cậy thêm.
	if ( false !== $json ) {
		return $json;
	}

	try {
		$value = _wp_json_sanity_check( $value, $depth );
	} catch ( Exception $e ) {
		return false;
	}

	return json_encode( $value, $flags, $depth );
}

/**
 * Thực hiện kiểm tra độ tin cậy trên dữ liệu sẽ được mã hóa thành JSON.
 *
 * @ignore
 * @since 4.1.0
 * @access private
 *
 * @see wp_json_encode()
 *
 * @throws Exception Nếu đạt đến giới hạn độ sâu.
 *
 * @param mixed $value Biến (thường là mảng hoặc đối tượng) để mã hóa thành JSON.
 * @param int   $depth Độ sâu tối đa để duyệt qua $value. Phải lớn hơn 0.
 * @return mixed Dữ liệu đã làm sạch sẽ được mã hóa thành JSON.
 */
function _wp_json_sanity_check( $value, $depth ) {
	if ( $depth < 0 ) {
		throw new Exception( 'Reached depth limit' );
	}

	if ( is_array( $value ) ) {
		$output = array();
		foreach ( $value as $id => $el ) {
			// Đừng quên làm sạch ID!
			if ( is_string( $id ) ) {
				$clean_id = _wp_json_convert_string( $id );
			} else {
				$clean_id = $id;
			}

			// Kiểm tra loại phần tử, để chỉ đệ quy khi thực sự cần thiết.
			if ( is_array( $el ) || is_object( $el ) ) {
				$output[ $clean_id ] = _wp_json_sanity_check( $el, $depth - 1 );
			} elseif ( is_string( $el ) ) {
				$output[ $clean_id ] = _wp_json_convert_string( $el );
			} else {
				$output[ $clean_id ] = $el;
			}
		}
	} elseif ( is_object( $value ) ) {
		$output = new stdClass();
		foreach ( $value as $id => $el ) {
			if ( is_string( $id ) ) {
				$clean_id = _wp_json_convert_string( $id );
			} else {
				$clean_id = $id;
			}

			if ( is_array( $el ) || is_object( $el ) ) {
				$output->$clean_id = _wp_json_sanity_check( $el, $depth - 1 );
			} elseif ( is_string( $el ) ) {
				$output->$clean_id = _wp_json_convert_string( $el );
			} else {
				$output->$clean_id = $el;
			}
		}
	} elseif ( is_string( $value ) ) {
		return _wp_json_convert_string( $value );
	} else {
		return $value;
	}

	return $output;
}

/**
 * Chuyển đổi chuỗi sang UTF-8, để có thể mã hóa an toàn thành JSON.
 *
 * @ignore
 * @since 4.1.0
 * @access private
 *
 * @see _wp_json_sanity_check()
 *
 * @param string $input_string Chuỗi cần được chuyển đổi.
 * @return string Chuỗi đã được kiểm tra.
 */
function _wp_json_convert_string( $input_string ) {
	static $use_mb = null;
	if ( is_null( $use_mb ) ) {
		$use_mb = function_exists( 'mb_convert_encoding' );
	}

	if ( $use_mb ) {
		$encoding = mb_detect_encoding( $input_string, mb_detect_order(), true );
		if ( $encoding ) {
			return mb_convert_encoding( $input_string, 'UTF-8', $encoding );
		} else {
			return mb_convert_encoding( $input_string, 'UTF-8', 'UTF-8' );
		}
	} else {
		return wp_check_invalid_utf8( $input_string, true );
	}
}

/**
 * Chuẩn bị dữ liệu phản hồi để tuần tự hóa thành JSON.
 *
 * Hàm này cũng hỗ trợ giao diện JsonSerializable cho PHP 5.2-5.3.
 *
 * @ignore
 * @since 4.4.0
 * @deprecated 5.3.0 Hàm này không còn cần thiết vì đã ngừng hỗ trợ PHP 5.2-5.3.
 * @access private
 *
 * @param mixed $value Biểu diễn gốc.
 * @return bool|int|float|null|string|array Dữ liệu sẵn sàng cho `json_encode()`.
 */
function _wp_json_prepare_data( $value ) {
	_deprecated_function( __FUNCTION__, '5.3.0' );
	return $value;
}

/**
 * Gửi phản hồi JSON trở lại cho yêu cầu Ajax.
 *
 * @since 3.5.0
 * @since 4.7.0 Tham số `$status_code` được thêm vào.
 * @since 5.6.0 Tham số `$flags` được thêm vào.
 *
 * @param mixed $response    Biến (thường là mảng hoặc đối tượng) để mã hóa thành JSON,
 *                           sau đó in ra và dừng.
 * @param int   $status_code Tùy chọn. Mã trạng thái HTTP để xuất ra. Mặc định null.
 * @param int   $flags       Tùy chọn. Các tùy chọn để truyền cho json_encode(). Mặc định 0.
 */
function wp_send_json( $response, $status_code = null, $flags = 0 ) {
	if ( wp_is_serving_rest_request() ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: WP_REST_Response, 2: WP_Error */
				__( 'Return a %1$s or %2$s object from your callback when using the REST API.' ),
				'WP_REST_Response',
				'WP_Error'
			),
			'5.5.0'
		);
	}

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		if ( null !== $status_code ) {
			status_header( $status_code );
		}
	}

	echo wp_json_encode( $response, $flags );

	if ( wp_doing_ajax() ) {
		wp_die(
			'',
			'',
			array(
				'response' => null,
			)
		);
	} else {
		die;
	}
}

/**
 * Gửi phản hồi JSON trở lại cho yêu cầu Ajax, biểu thị thành công.
 *
 * @since 3.5.0
 * @since 4.7.0 Tham số `$status_code` được thêm vào.
 * @since 5.6.0 Tham số `$flags` được thêm vào.
 *
 * @param mixed $value       Tùy chọn. Dữ liệu để mã hóa thành JSON, sau đó in ra và dừng. Mặc định null.
 * @param int   $status_code Tùy chọn. Mã trạng thái HTTP để xuất ra. Mặc định null.
 * @param int   $flags       Tùy chọn. Các tùy chọn để truyền cho json_encode(). Mặc định 0.
 */
function wp_send_json_success( $value = null, $status_code = null, $flags = 0 ) {
	$response = array( 'success' => true );

	if ( isset( $value ) ) {
		$response['data'] = $value;
	}

	wp_send_json( $response, $status_code, $flags );
}

/**
 * Gửi phản hồi JSON trở lại cho yêu cầu Ajax, biểu thị thất bại.
 *
 * Nếu tham số `$value` là đối tượng WP_Error, các lỗi
 * trong đối tượng sẽ được xử lý và xuất ra dưới dạng mảng mã lỗi
 * và các thông báo tương ứng. Tất cả các loại khác được xuất ra
 * mà không cần xử lý thêm.
 *
 * @since 3.5.0
 * @since 4.1.0 Tham số `$value` bây giờ được xử lý nếu một đối tượng WP_Error được truyền vào.
 * @since 4.7.0 Tham số `$status_code` được thêm vào.
 * @since 5.6.0 Tham số `$flags` được thêm vào.
 *
 * @param mixed $value       Tùy chọn. Dữ liệu để mã hóa thành JSON, sau đó in ra và dừng. Mặc định null.
 * @param int   $status_code Tùy chọn. Mã trạng thái HTTP để xuất ra. Mặc định null.
 * @param int   $flags       Tùy chọn. Các tùy chọn để truyền cho json_encode(). Mặc định 0.
 */
function wp_send_json_error( $value = null, $status_code = null, $flags = 0 ) {
	$response = array( 'success' => false );

	if ( isset( $value ) ) {
		if ( is_wp_error( $value ) ) {
			$result = array();
			foreach ( $value->errors as $code => $messages ) {
				foreach ( $messages as $message ) {
					$result[] = array(
						'code'    => $code,
						'message' => $message,
					);
				}
			}

			$response['data'] = $result;
		} else {
			$response['data'] = $value;
		}
	}

	wp_send_json( $response, $status_code, $flags );
}

/**
 * Kiểm tra rằng callback JSONP là tên hàm callback JavaScript hợp lệ.
 *
 * Chỉ cho phép ký tự chữ-số và ký tự dấu chấm trong tên hàm callback.
 * Điều này giúp giảm thiểu các cuộc tấn công XSS gây ra bởi việc
 * xuất trực tiếp đầu vào của người dùng.
 *
 * @since 4.6.0
 *
 * @param string $callback Tên hàm callback JSONP được cung cấp.
 * @return bool Liệu tên hàm callback có hợp lệ hay không.
 */
function wp_check_jsonp_callback( $callback ) {
	if ( ! is_string( $callback ) ) {
		return false;
	}

	preg_replace( '/[^\w\.]/', '', $callback, -1, $illegal_char_count );

	return 0 === $illegal_char_count;
}

/**
 * Đọc và giải mã một file JSON.
 *
 * @since 5.9.0
 *
 * @param string $filename Đường dẫn đến file JSON.
 * @param array  $options  {
 *     Tùy chọn. Các tùy chọn để sử dụng với `json_decode()`.
 *
 *     @type bool $associative Tùy chọn. Khi `true`, các đối tượng JSON sẽ được trả về dưới dạng mảng liên kết.
 *                             Khi `false`, các đối tượng JSON sẽ được trả về dưới dạng đối tượng. Mặc định false.
 * }
 *
 * @return mixed Trả về giá trị được mã hóa trong JSON dưới kiểu PHP phù hợp.
 *               `null` được trả về nếu file không tìm thấy, hoặc nội dung không thể giải mã.
 */
function wp_json_file_decode( $filename, $options = array() ) {
	$result   = null;
	$filename = wp_normalize_path( realpath( $filename ) );

	if ( ! $filename ) {
		wp_trigger_error(
			__FUNCTION__,
			sprintf(
				/* translators: %s: Path to the JSON file. */
				__( "File %s doesn't exist!" ),
				$filename
			)
		);
		return $result;
	}

	$options      = wp_parse_args( $options, array( 'associative' => false ) );
	$decoded_file = json_decode( file_get_contents( $filename ), $options['associative'] );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		wp_trigger_error(
			__FUNCTION__,
			sprintf(
				/* translators: 1: Path to the JSON file, 2: Error message. */
				__( 'Error when decoding a JSON file at path %1$s: %2$s' ),
				$filename,
				json_last_error_msg()
			)
		);
		return $result;
	}

	return $decoded_file;
}

/**
 * Lấy URL trang chủ WordPress.
 *
 * Nếu hằng số có tên 'WP_HOME' tồn tại, thì nó sẽ được sử dụng và trả về
 * bởi hàm. Điều này có thể được sử dụng để chống lại việc chuyển hướng trên
 * môi trường phát triển cục bộ của bạn.
 *
 * @since 2.2.0
 * @access private
 *
 * @see WP_HOME
 *
 * @param string $url URL cho vị trí trang chủ.
 * @return string Vị trí trang chủ.
 */
function _config_wp_home( $url = '' ) {
	if ( defined( 'WP_HOME' ) ) {
		return untrailingslashit( WP_HOME );
	}
	return $url;
}

/**
 * Lấy URL trang web WordPress.
 *
 * Nếu hằng số có tên 'WP_SITEURL' được định nghĩa, thì giá trị trong hằng số
 * đó sẽ luôn được trả về. Điều này có thể được sử dụng để debug trang web
 * trên localhost mà không cần thay đổi cơ sở dữ liệu thành URL của bạn.
 *
 * @since 2.2.0
 * @access private
 *
 * @see WP_SITEURL
 *
 * @param string $url URL để đặt vị trí trang web WordPress.
 * @return string URL trang web WordPress.
 */
function _config_wp_siteurl( $url = '' ) {
	if ( defined( 'WP_SITEURL' ) ) {
		return untrailingslashit( WP_SITEURL );
	}
	return $url;
}

/**
 * Xóa tùy chọn trang web mới.
 *
 * @since 4.7.0
 * @access private
 */
function _delete_option_fresh_site() {
	update_option( 'fresh_site', '0', false );
}

/**
 * Đặt hướng được bản địa hóa cho plugin MCE.
 *
 * Chỉ đặt hướng thành 'rtl', nếu locale WordPress có
 * hướng văn bản được đặt thành 'rtl'.
 *
 * Điền thiết lập 'directionality', kích hoạt plugin 'directionality',
 * và thêm nút 'ltr' vào 'toolbar1', trước đây là
 * các khóa mảng 'theme_advanced_buttons1'. Các khóa này sau đó được trả về
 * trong mảng $mce_init (cài đặt TinyMCE).
 *
 * @since 2.1.0
 * @access private
 *
 * @param array $mce_init Mảng cài đặt MCE.
 * @return array Hướng được đặt cho 'rtl', nếu cần bởi locale.
 */
function _mce_set_direction( $mce_init ) {
	if ( is_rtl() ) {
		$mce_init['directionality'] = 'rtl';
		$mce_init['rtl_ui']         = true;

		if ( ! empty( $mce_init['plugins'] ) && ! str_contains( $mce_init['plugins'], 'directionality' ) ) {
			$mce_init['plugins'] .= ',directionality';
		}

		if ( ! empty( $mce_init['toolbar1'] ) && ! preg_match( '/\bltr\b/', $mce_init['toolbar1'] ) ) {
			$mce_init['toolbar1'] .= ',ltr';
		}
	}

	return $mce_init;
}

/**
 * Xác định liệu WordPress có đang phục vụ một yêu cầu REST API hay không.
 *
 * Hàm này dựa vào biến toàn cục 'REST_REQUEST'. Do đó, nó chỉ trả về true khi một _yêu cầu_ REST thực tế
 * đang được thực hiện. Nó không trả về true khi một endpoint REST được gọi như một phần của yêu cầu khác, ví dụ để tải trước
 * phản hồi REST. Xem {@see wp_is_rest_endpoint()} cho mục đích đó.
 *
 * Hàm này không nên được gọi cho đến action {@see 'parse_request'}, vì hằng số chỉ được định nghĩa vào lúc đó,
 * ngay cả đối với một yêu cầu REST thực tế.
 *
 * @since 6.5.0
 *
 * @return bool True nếu là yêu cầu WordPress REST API, false nếu ngược lại.
 */
function wp_is_serving_rest_request() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * Chuyển đổi mã biểu tượng cảm xúc thành file đồ họa biểu tượng tương ứng.
 *
 * Bạn có thể tắt biểu tượng cảm xúc, bằng cách vào màn hình cài đặt viết bài và bỏ chọn
 * hộp kiểm, hoặc bằng cách đặt tùy chọn 'use_smilies' thành false hoặc xóa tùy chọn.
 *
 * Plugin có thể ghi đè danh sách biểu tượng cảm xúc mặc định bằng cách đặt $wpsmiliestrans
 * thành một mảng, với khóa là mã mà người viết blog nhập vào và giá trị là
 * file hình ảnh.
 *
 * Biến toàn cục $wp_smiliessearch dành cho biểu thức chính quy và được đặt mỗi
 * lần hàm được gọi.
 *
 * Danh sách đầy đủ các biểu tượng cảm xúc có thể tìm thấy trong hàm và sẽ không được liệt kê trong
 * mô tả. Có lẽ nên tạo một trang Codex cho nó, để nó có thể sử dụng được.
 *
 * @since 2.2.0
 *
 * @global array $wpsmiliestrans
 * @global array $wp_smiliessearch
 */
function smilies_init() {
	global $wpsmiliestrans, $wp_smiliessearch;

	// Không cần thiết lập biểu tượng cảm xúc nếu chúng bị tắt.
	if ( ! get_option( 'use_smilies' ) ) {
		return;
	}

	if ( ! isset( $wpsmiliestrans ) ) {
		$wpsmiliestrans = array(
			':mrgreen:' => 'mrgreen.png',
			':neutral:' => "\xf0\x9f\x98\x90",
			':twisted:' => "\xf0\x9f\x98\x88",
			':arrow:'   => "\xe2\x9e\xa1",
			':shock:'   => "\xf0\x9f\x98\xaf",
			':smile:'   => "\xf0\x9f\x99\x82",
			':???:'     => "\xf0\x9f\x98\x95",
			':cool:'    => "\xf0\x9f\x98\x8e",
			':evil:'    => "\xf0\x9f\x91\xbf",
			':grin:'    => "\xf0\x9f\x98\x80",
			':idea:'    => "\xf0\x9f\x92\xa1",
			':oops:'    => "\xf0\x9f\x98\xb3",
			':razz:'    => "\xf0\x9f\x98\x9b",
			':roll:'    => "\xf0\x9f\x99\x84",
			':wink:'    => "\xf0\x9f\x98\x89",
			':cry:'     => "\xf0\x9f\x98\xa5",
			':eek:'     => "\xf0\x9f\x98\xae",
			':lol:'     => "\xf0\x9f\x98\x86",
			':mad:'     => "\xf0\x9f\x98\xa1",
			':sad:'     => "\xf0\x9f\x99\x81",
			'8-)'       => "\xf0\x9f\x98\x8e",
			'8-O'       => "\xf0\x9f\x98\xaf",
			':-('       => "\xf0\x9f\x99\x81",
			':-)'       => "\xf0\x9f\x99\x82",
			':-?'       => "\xf0\x9f\x98\x95",
			':-D'       => "\xf0\x9f\x98\x80",
			':-P'       => "\xf0\x9f\x98\x9b",
			':-o'       => "\xf0\x9f\x98\xae",
			':-x'       => "\xf0\x9f\x98\xa1",
			':-|'       => "\xf0\x9f\x98\x90",
			';-)'       => "\xf0\x9f\x98\x89",
			// Phép chuyển đổi này thường xuyên làm hỏng văn bản thông thường.
			//     '8)' => "\xf0\x9f\x98\x8e",
			'8O'        => "\xf0\x9f\x98\xaf",
			':('        => "\xf0\x9f\x99\x81",
			':)'        => "\xf0\x9f\x99\x82",
			':?'        => "\xf0\x9f\x98\x95",
			':D'        => "\xf0\x9f\x98\x80",
			':P'        => "\xf0\x9f\x98\x9b",
			':o'        => "\xf0\x9f\x98\xae",
			':x'        => "\xf0\x9f\x98\xa1",
			':|'        => "\xf0\x9f\x98\x90",
			';)'        => "\xf0\x9f\x98\x89",
			':!:'       => "\xe2\x9d\x97",
			':?:'       => "\xe2\x9d\x93",
		);
	}

	/**
	 * Lọc tất cả các biểu tượng cảm xúc.
	 *
	 * Bộ lọc này phải được thêm trước khi `smilies_init` được chạy, vì
	 * nó thường chỉ được chạy một lần để thiết lập regex biểu tượng cảm xúc.
	 *
	 * @since 4.7.0
	 *
	 * @param string[] $wpsmiliestrans Danh sách biểu diễn thập lục phân của biểu tượng cảm xúc, được khóa bởi mã biểu tượng.
	 */
	$wpsmiliestrans = apply_filters( 'smilies', $wpsmiliestrans );

	if ( count( $wpsmiliestrans ) === 0 ) {
		return;
	}

	/*
	 * LƯU Ý: chúng ta sắp xếp biểu tượng cảm xúc theo thứ tự khóa ngược. Điều này để đảm bảo
	 * chúng ta khớp biểu tượng cảm xúc dài nhất có thể (:???: vs :?) vì biểu thức
	 * chính quy được sử dụng bên dưới là khớp-đầu-tiên
	 */
	krsort( $wpsmiliestrans );

	$spaces = wp_spaces_regexp();

	// Bắt đầu "mẫu con" đầu tiên.
	$wp_smiliessearch = '/(?<=' . $spaces . '|^)';

	$subchar = '';
	foreach ( (array) $wpsmiliestrans as $smiley => $img ) {
		$firstchar = substr( $smiley, 0, 1 );
		$rest      = substr( $smiley, 1 );

		// Mẫu con mới?
		if ( $firstchar !== $subchar ) {
			if ( '' !== $subchar ) {
				$wp_smiliessearch .= ')(?=' . $spaces . '|$)';  // Kết thúc "mẫu con" trước.
				$wp_smiliessearch .= '|(?<=' . $spaces . '|^)'; // Bắt đầu "mẫu con" khác.
			}

			$subchar           = $firstchar;
			$wp_smiliessearch .= preg_quote( $firstchar, '/' ) . '(?:';
		} else {
			$wp_smiliessearch .= '|';
		}

		$wp_smiliessearch .= preg_quote( $rest, '/' );
	}

	$wp_smiliessearch .= ')(?=' . $spaces . '|$)/m';
}

/**
 * Hợp nhất các tham số do người dùng định nghĩa vào mảng mặc định.
 *
 * Hàm này được sử dụng xuyên suốt WordPress để cho phép cả chuỗi hoặc mảng
 * được hợp nhất vào một mảng khác.
 *
 * @since 2.2.0
 * @since 2.3.0 `$args` bây giờ cũng có thể là đối tượng.
 *
 * @param string|array|object $args     Giá trị để hợp nhất với $defaults.
 * @param array               $defaults Tùy chọn. Mảng đóng vai trò làm giá trị mặc định.
 *                                      Mặc định mảng rỗng.
 * @return array Giá trị do người dùng định nghĩa đã hợp nhất với giá trị mặc định.
 */
function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$parsed_args = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$parsed_args =& $args;
	} else {
		wp_parse_str( $args, $parsed_args );
	}

	if ( is_array( $defaults ) && $defaults ) {
		return array_merge( $defaults, $parsed_args );
	}
	return $parsed_args;
}

/**
 * Chuyển đổi danh sách giá trị vô hướng phân cách bằng dấu phẩy hoặc khoảng trắng thành mảng.
 *
 * @since 5.1.0
 *
 * @param array|string $input_list Danh sách các giá trị.
 * @return array Mảng các giá trị.
 */
function wp_parse_list( $input_list ) {
	if ( ! is_array( $input_list ) ) {
		return preg_split( '/[\s,]+/', $input_list, -1, PREG_SPLIT_NO_EMPTY );
	}

	// Xác thực tất cả các mục trong danh sách là giá trị vô hướng.
	$input_list = array_filter( $input_list, 'is_scalar' );

	return $input_list;
}

/**
 * Dọn dẹp một mảng, danh sách ID phân cách bằng dấu phẩy hoặc khoảng trắng.
 *
 * @since 3.0.0
 * @since 5.1.0 Tái cấu trúc để sử dụng wp_parse_list().
 *
 * @param array|string $input_list Danh sách các ID.
 * @return int[] Mảng ID đã được làm sạch.
 */
function wp_parse_id_list( $input_list ) {
	$input_list = wp_parse_list( $input_list );

	return array_unique( array_map( 'absint', $input_list ) );
}

/**
 * Dọn dẹp một mảng, danh sách slug phân cách bằng dấu phẩy hoặc khoảng trắng.
 *
 * @since 4.7.0
 * @since 5.1.0 Tái cấu trúc để sử dụng wp_parse_list().
 *
 * @param array|string $input_list Danh sách các slug.
 * @return string[] Mảng slug đã được làm sạch.
 */
function wp_parse_slug_list( $input_list ) {
	$input_list = wp_parse_list( $input_list );

	return array_unique( array_map( 'sanitize_title', $input_list ) );
}

/**
 * Trích xuất một phần của mảng, cho trước một danh sách khóa.
 *
 * @since 3.1.0
 *
 * @param array $input_array Mảng gốc.
 * @param array $keys        Danh sách các khóa.
 * @return array Phần mảng đã trích xuất.
 */
function wp_array_slice_assoc( $input_array, $keys ) {
	$slice = array();

	foreach ( $keys as $key ) {
		if ( isset( $input_array[ $key ] ) ) {
			$slice[ $key ] = $input_array[ $key ];
		}
	}

	return $slice;
}

/**
 * Sắp xếp các khóa của mảng theo thứ tự bảng chữ cái.
 *
 * Mảng được truyền theo tham chiếu nên nó không được trả về,
 * mô phỏng hành vi của `ksort()`.
 *
 * @since 6.0.0
 *
 * @param array $input_array Mảng cần sắp xếp, được truyền theo tham chiếu.
 */
function wp_recursive_ksort( &$input_array ) {
	foreach ( $input_array as &$value ) {
		if ( is_array( $value ) ) {
			wp_recursive_ksort( $value );
		}
	}

	ksort( $input_array );
}

/**
 * Truy cập mảng theo chiều sâu dựa trên đường dẫn các khóa.
 *
 * Đây là phiên bản PHP tương đương của `lodash.get()` trong JavaScript và việc phản chiếu nó có thể giúp các thành phần khác
 * giữ được sự đối xứng giữa các triển khai phía client và server.
 *
 * Ví dụ sử dụng:
 *
 *     $input_array = array(
 *         'a' => array(
 *             'b' => array(
 *                 'c' => 1,
 *             ),
 *         ),
 *     );
 *     _wp_array_get( $input_array, array( 'a', 'b', 'c' ) );
 *
 * @internal
 *
 * @since 5.6.0
 * @access private
 *
 * @param array $input_array   Mảng mà chúng ta muốn lấy thông tin từ đó.
 * @param array $path          Mảng các khóa mô tả đường dẫn để lấy thông tin.
 * @param mixed $default_value Tùy chọn. Giá trị trả về nếu đường dẫn không tồn tại trong mảng,
 *                             hoặc nếu `$input_array` hoặc `$path` không phải là mảng. Mặc định null.
 * @return mixed Giá trị từ đường dẫn được chỉ định.
 */
function _wp_array_get( $input_array, $path, $default_value = null ) {
	// Xác nhận $path hợp lệ.
	if ( ! is_array( $path ) || 0 === count( $path ) ) {
		return $default_value;
	}

	foreach ( $path as $path_element ) {
		if ( ! is_array( $input_array ) ) {
			return $default_value;
		}

		if ( is_string( $path_element )
			|| is_integer( $path_element )
			|| null === $path_element
		) {
			/*
			 * Kiểm tra xem phần tử đường dẫn có tồn tại trong mảng đầu vào không.
			 * Chúng ta kiểm tra bằng `isset()` trước, vì nó nhanh hơn nhiều
			 * so với `array_key_exists()`.
			 */
			if ( isset( $input_array[ $path_element ] ) ) {
				$input_array = $input_array[ $path_element ];
				continue;
			}

			/*
			 * Nếu `isset()` trả về false, chúng ta kiểm tra bằng `array_key_exists()`,
			 * hàm cũng kiểm tra các giá trị `null`.
			 */
			if ( array_key_exists( $path_element, $input_array ) ) {
				$input_array = $input_array[ $path_element ];
				continue;
			}
		}

		return $default_value;
	}

	return $input_array;
}

/**
 * Đặt giá trị vào mảng theo chiều sâu dựa trên đường dẫn các khóa.
 *
 * Đây là phiên bản PHP tương đương của `lodash.set()` trong JavaScript và việc phản chiếu nó có thể giúp các thành phần khác
 * giữ được sự đối xứng giữa các triển khai phía client và server.
 *
 * Ví dụ sử dụng:
 *
 *     $input_array = array();
 *     _wp_array_set( $input_array, array( 'a', 'b', 'c', 1 ) );
 *
 *     $input_array trở thành:
 *     array(
 *         'a' => array(
 *             'b' => array(
 *                 'c' => 1,
 *             ),
 *         ),
 *     );
 *
 * @internal
 *
 * @since 5.8.0
 * @access private
 *
 * @param array $input_array Mảng mà chúng ta muốn thay đổi để bao gồm một giá trị cụ thể trong đường dẫn.
 * @param array $path        Mảng các khóa mô tả đường dẫn mà chúng ta muốn thay đổi.
 * @param mixed $value       Giá trị sẽ được đặt.
 */
function _wp_array_set( &$input_array, $path, $value = null ) {
	// Xác nhận $input_array hợp lệ.
	if ( ! is_array( $input_array ) ) {
		return;
	}

	// Xác nhận $path hợp lệ.
	if ( ! is_array( $path ) ) {
		return;
	}

	$path_length = count( $path );

	if ( 0 === $path_length ) {
		return;
	}

	foreach ( $path as $path_element ) {
		if (
			! is_string( $path_element ) && ! is_integer( $path_element ) &&
			! is_null( $path_element )
		) {
			return;
		}
	}

	for ( $i = 0; $i < $path_length - 1; ++$i ) {
		$path_element = $path[ $i ];
		if (
			! array_key_exists( $path_element, $input_array ) ||
			! is_array( $input_array[ $path_element ] )
		) {
			$input_array[ $path_element ] = array();
		}
		$input_array = &$input_array[ $path_element ];
	}

	$input_array[ $path[ $i ] ] = $value;
}

/**
 * Hàm này cố gắng tái tạo những gì
 * kebabCase của lodash (thư viện JS) thực hiện ở phía client.
 *
 * Lý do chúng ta cần hàm này là vì chúng ta xử lý
 * ở cả phía client và server (ví dụ: chúng ta tạo
 * các lớp preset từ slug preset) cần
 * tạo ra cùng một đầu ra.
 *
 * Chúng ta không thể xóa hoặc cập nhật thư viện phía client vì tương thích ngược
 * (một số đầu ra của kebabCase trong lodash được lưu trong nội dung bài viết).
 * Chúng ta phải làm cho server hoạt động giống client.
 *
 * Các thay đổi cho hàm này nên theo dõi các cập nhật ở phía client
 * với cùng logic.
 *
 * @link https://github.com/lodash/lodash/blob/4.17/dist/lodash.js#L14369
 * @link https://github.com/lodash/lodash/blob/4.17/dist/lodash.js#L278
 * @link https://github.com/lodash-php/lodash-php/blob/master/src/String/kebabCase.php
 * @link https://github.com/lodash-php/lodash-php/blob/master/src/internal/unicodeWords.php
 *
 * @param string $input_string Chuỗi để chuyển thành kebab-case.
 *
 * @return string Chuỗi đã chuyển thành kebab-case.
 */
function _wp_to_kebab_case( $input_string ) {
	// Bỏ qua quy tắc đặt tên camelCase cho biến để tên giống lodash, giúp so sánh và chuyển đổi các thay đổi mới dễ hơn.
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

	/*
	 * Một số điều đáng chú ý mà chúng ta đã loại bỏ so với phiên bản lodash:
	 *
	 * - các ký tự không phải chữ-số: rsAstralRange, rsEmoji, v.v.
	 * - các nhóm xử lý dấu nháy đơn, vì nó được xóa trước khi truyền chuỗi tới preg_match: rsApos, rsOptContrLower, và rsOptContrUpper
	 *
	 */

	/** Dùng để tạo các lớp ký tự unicode. */
	$rsLowerRange       = 'a-z\\xdf-\\xf6\\xf8-\\xff';
	$rsNonCharRange     = '\\x00-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\xbf';
	$rsPunctuationRange = '\\x{2000}-\\x{206f}';
	$rsSpaceRange       = ' \\t\\x0b\\f\\xa0\\x{feff}\\n\\r\\x{2028}\\x{2029}\\x{1680}\\x{180e}\\x{2000}\\x{2001}\\x{2002}\\x{2003}\\x{2004}\\x{2005}\\x{2006}\\x{2007}\\x{2008}\\x{2009}\\x{200a}\\x{202f}\\x{205f}\\x{3000}';
	$rsUpperRange       = 'A-Z\\xc0-\\xd6\\xd8-\\xde';
	$rsBreakRange       = $rsNonCharRange . $rsPunctuationRange . $rsSpaceRange;

	/** Dùng để tạo các nhóm bắt unicode. */
	$rsBreak  = '[' . $rsBreakRange . ']';
	$rsDigits = '\\d+'; // The last lodash version in GitHub uses a single digit here and expands it when in use.
	$rsLower  = '[' . $rsLowerRange . ']';
	$rsMisc   = '[^' . $rsBreakRange . $rsDigits . $rsLowerRange . $rsUpperRange . ']';
	$rsUpper  = '[' . $rsUpperRange . ']';

	/** Dùng để tạo các biểu thức chính quy unicode. */
	$rsMiscLower = '(?:' . $rsLower . '|' . $rsMisc . ')';
	$rsMiscUpper = '(?:' . $rsUpper . '|' . $rsMisc . ')';
	$rsOrdLower  = '\\d*(?:1st|2nd|3rd|(?![123])\\dth)(?=\\b|[A-Z_])';
	$rsOrdUpper  = '\\d*(?:1ST|2ND|3RD|(?![123])\\dTH)(?=\\b|[a-z_])';

	$regexp = '/' . implode(
		'|',
		array(
			$rsUpper . '?' . $rsLower . '+' . '(?=' . implode( '|', array( $rsBreak, $rsUpper, '$' ) ) . ')',
			$rsMiscUpper . '+' . '(?=' . implode( '|', array( $rsBreak, $rsUpper . $rsMiscLower, '$' ) ) . ')',
			$rsUpper . '?' . $rsMiscLower . '+',
			$rsUpper . '+',
			$rsOrdUpper,
			$rsOrdLower,
			$rsDigits,
		)
	) . '/u';

	preg_match_all( $regexp, str_replace( "'", '', $input_string ), $matches );
	return strtolower( implode( '-', $matches[0] ) );
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
}

/**
 * Xác định xem biến có phải là mảng có chỉ mục số hay không.
 *
 * @since 4.4.0
 *
 * @param mixed $data Biến cần kiểm tra.
 * @return bool Liệu biến có phải là danh sách hay không.
 */
function wp_is_numeric_array( $data ) {
	if ( ! is_array( $data ) ) {
		return false;
	}

	$keys        = array_keys( $data );
	$string_keys = array_filter( $keys, 'is_string' );

	return count( $string_keys ) === 0;
}

/**
 * Lọc danh sách đối tượng, dựa trên tập hợp các đối số key => value.
 *
 * Lấy các đối tượng từ danh sách khớp với các đối số đã cho.
 * Key đại diện cho tên thuộc tính, và value đại diện cho giá trị thuộc tính.
 *
 * Nếu một đối tượng có nhiều thuộc tính hơn những thuộc tính được chỉ định trong đối số,
 * điều đó sẽ không loại trừ nó. Khi sử dụng toán tử 'AND',
 * bất kỳ thuộc tính thiếu nào sẽ loại trừ nó.
 *
 * Khi sử dụng đối số `$field`, hàm này cũng có thể lấy
 * một trường cụ thể từ tất cả các đối tượng khớp, trong khi wp_list_filter()
 * chỉ thực hiện việc lọc.
 *
 * @since 3.0.0
 * @since 4.7.0 Sử dụng lớp `WP_List_Util`.
 *
 * @param array       $input_list Mảng các đối tượng cần lọc.
 * @param array       $args       Tùy chọn. Mảng các đối số key => value để khớp
 *                                với mỗi đối tượng. Mặc định mảng rỗng.
 * @param string      $operator   Tùy chọn. Phép toán logic cần thực hiện. 'AND' nghĩa là
 *                                tất cả phần tử từ mảng phải khớp. 'OR' nghĩa là chỉ
 *                                một phần tử cần khớp. 'NOT' nghĩa là không phần tử nào
 *                                được khớp. Mặc định 'AND'.
 * @param bool|string $field      Tùy chọn. Trường từ đối tượng để đặt thay vì
 *                                toàn bộ đối tượng. Mặc định false.
 * @return array Danh sách các đối tượng hoặc các trường đối tượng.
 */
function wp_filter_object_list( $input_list, $args = array(), $operator = 'and', $field = false ) {
	if ( ! is_array( $input_list ) ) {
		return array();
	}

	$util = new WP_List_Util( $input_list );

	$util->filter( $args, $operator );

	if ( $field ) {
		$util->pluck( $field );
	}

	return $util->get_output();
}

/**
 * Lọc danh sách đối tượng, dựa trên tập hợp các đối số key => value.
 *
 * Lấy các đối tượng từ danh sách khớp với các đối số đã cho.
 * Key đại diện cho tên thuộc tính, và value đại diện cho giá trị thuộc tính.
 *
 * Nếu một đối tượng có nhiều thuộc tính hơn những thuộc tính được chỉ định trong đối số,
 * điều đó sẽ không loại trừ nó. Khi sử dụng toán tử 'AND',
 * bất kỳ thuộc tính thiếu nào sẽ loại trừ nó.
 *
 * Nếu bạn muốn lấy một trường cụ thể từ tất cả các đối tượng khớp,
 * hãy sử dụng wp_filter_object_list() thay thế.
 *
 * @since 3.1.0
 * @since 4.7.0 Sử dụng lớp `WP_List_Util`.
 * @since 5.9.0 Được chuyển thành wrapper cho `wp_filter_object_list()`.
 *
 * @param array  $input_list Mảng các đối tượng cần lọc.
 * @param array  $args       Tùy chọn. Mảng các đối số key => value để khớp
 *                           với mỗi đối tượng. Mặc định mảng rỗng.
 * @param string $operator   Tùy chọn. Phép toán logic cần thực hiện. 'AND' nghĩa là
 *                           tất cả phần tử từ mảng phải khớp. 'OR' nghĩa là chỉ
 *                           một phần tử cần khớp. 'NOT' nghĩa là không phần tử nào
 *                           được khớp. Mặc định 'AND'.
 * @return array Mảng các giá trị tìm thấy.
 */
function wp_list_filter( $input_list, $args = array(), $operator = 'AND' ) {
	return wp_filter_object_list( $input_list, $args, $operator );
}

/**
 * Trích xuất một trường cụ thể từ mỗi đối tượng hoặc mảng trong một mảng.
 *
 * Hàm này có cùng chức năng và nguyên mẫu như
 * array_column() (PHP 5.5) nhưng cũng hỗ trợ đối tượng.
 *
 * @since 3.1.0
 * @since 4.0.0 Thêm tham số $index_key.
 * @since 4.7.0 Sử dụng lớp `WP_List_Util`.
 *
 * @param array      $input_list Danh sách các đối tượng hoặc mảng.
 * @param int|string $field      Trường từ đối tượng để đặt thay vì toàn bộ đối tượng.
 * @param int|string $index_key  Tùy chọn. Trường từ đối tượng để sử dụng làm khóa cho mảng mới.
 *                               Mặc định null.
 * @return array Mảng các giá trị tìm thấy. Nếu `$index_key` được đặt, mảng các giá trị tìm thấy với khóa
 *               tương ứng với `$index_key`. Nếu `$index_key` là null, các khóa mảng từ
 *               `$input_list` gốc sẽ được bảo toàn trong kết quả.
 */
function wp_list_pluck( $input_list, $field, $index_key = null ) {
	if ( ! is_array( $input_list ) ) {
		return array();
	}

	$util = new WP_List_Util( $input_list );

	return $util->pluck( $field, $index_key );
}

/**
 * Sắp xếp mảng đối tượng hoặc mảng dựa trên một hoặc nhiều đối số orderby.
 *
 * @since 4.7.0
 *
 * @param array        $input_list    Mảng các đối tượng hoặc mảng cần sắp xếp.
 * @param string|array $orderby       Tùy chọn. Tên trường để sắp xếp hoặc mảng
 *                                    của nhiều trường orderby dưới dạng `$orderby => $order`.
 *                                    Mặc định mảng rỗng.
 * @param string       $order         Tùy chọn. 'ASC' hoặc 'DESC'. Chỉ được sử dụng nếu `$orderby`
 *                                    là chuỗi. Mặc định 'ASC'.
 * @param bool         $preserve_keys Tùy chọn. Có bảo toàn khóa hay không. Mặc định false.
 * @return array Mảng đã sắp xếp.
 */
function wp_list_sort( $input_list, $orderby = array(), $order = 'ASC', $preserve_keys = false ) {
	if ( ! is_array( $input_list ) ) {
		return array();
	}

	$util = new WP_List_Util( $input_list );

	return $util->sort( $orderby, $order, $preserve_keys );
}

/**
 * Xác định xem thư viện Widget có nên được tải hay không.
 *
 * Kiểm tra để đảm bảo thư viện widget chưa được tải.
 * Nếu chưa, thì nó sẽ tải thư viện widget và chạy một action hook.
 *
 * @since 2.2.0
 */
function wp_maybe_load_widgets() {
	/**
	 * Lọc xem có nên tải thư viện Widget hay không.
	 *
	 * Trả về giá trị falsey từ bộ lọc sẽ ngắn mạch hiệu quả
	 * việc tải thư viện Widget.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $wp_maybe_load_widgets Có nên tải thư viện Widget hay không.
	 *                                    Mặc định true.
	 */
	if ( ! apply_filters( 'load_default_widgets', true ) ) {
		return;
	}

	require_once ABSPATH . WPINC . '/default-widgets.php';

	add_action( '_admin_menu', 'wp_widgets_add_menu' );
}

/**
 * Thêm menu Widget vào menu chính của giao diện.
 *
 * @since 2.2.0
 * @since 5.9.3 Không chỉ định thứ tự menu khi giao diện đang hoạt động là block theme.
 *
 * @global array $submenu
 */
function wp_widgets_add_menu() {
	global $submenu;

	if ( ! current_theme_supports( 'widgets' ) ) {
		return;
	}

	$menu_name = __( 'Widgets' );
	if ( wp_is_block_theme() ) {
		$submenu['themes.php'][] = array( $menu_name, 'edit_theme_options', 'widgets.php' );
	} else {
		$submenu['themes.php'][8] = array( $menu_name, 'edit_theme_options', 'widgets.php' );
	}

	ksort( $submenu['themes.php'], SORT_NUMERIC );
}

/**
 * Xả tất cả bộ đệm đầu ra cho PHP 5.2.
 *
 * Đảm bảo tất cả bộ đệm đầu ra được xả trước khi các singleton của chúng ta bị hủy.
 *
 * @since 2.2.0
 */
function wp_ob_end_flush_all() {
	$levels = ob_get_level();
	for ( $i = 0; $i < $levels; $i++ ) {
		ob_end_flush();
	}
}

/**
 * Tải lỗi DB tùy chỉnh hoặc hiển thị lỗi DB WordPress.
 *
 * Nếu một tệp tồn tại trong thư mục wp-content có tên db-error.php, thì nó sẽ
 * được tải thay vì hiển thị lỗi DB WordPress. Nếu không tìm thấy,
 * thì lỗi DB WordPress sẽ được hiển thị thay thế.
 *
 * Lỗi DB WordPress đặt header trạng thái HTTP thành 500 để cố ngăn
 * các công cụ tìm kiếm lưu đệm thông báo. Thông báo DB tùy chỉnh nên làm
 * tương tự.
 *
 * Hàm này được backport về WordPress 2.3.2, nhưng ban đầu được thêm
 * trong WordPress 2.5.0.
 *
 * @since 2.3.2
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 */
function dead_db() {
	global $wpdb;

	wp_load_translations_early();

	// Tải template lỗi DB tùy chỉnh, nếu có.
	if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
		require_once WP_CONTENT_DIR . '/db-error.php';
		die();
	}

	// Nếu đang cài đặt hoặc trong trang quản trị, cung cấp thông báo chi tiết.
	if ( wp_installing() || defined( 'WP_ADMIN' ) ) {
		wp_die( $wpdb->error );
	}

	// Nếu không, hiển thị ngắn gọn.
	wp_die( '<h1>' . __( 'Error establishing a database connection' ) . '</h1>', __( 'Database Error' ) );
}

/**
 * Đánh dấu một hàm là không còn được khuyến nghị và thông báo khi nó được sử dụng.
 *
 * Có một hook {@see 'deprecated_function_run'} sẽ được gọi và có thể được sử dụng
 * để lấy backtrace đến tệp và hàm nào đã gọi hàm không còn được khuyến nghị.
 *
 * Hành vi hiện tại là kích hoạt lỗi người dùng nếu `WP_DEBUG` là true.
 *
 * Hàm này được sử dụng trong mọi hàm không còn được khuyến nghị.
 *
 * @since 2.5.0
 * @since 5.4.0 Hàm này không còn được đánh dấu là "private".
 * @since 5.4.0 Loại lỗi hiện được phân loại là E_USER_DEPRECATED (trước đây mặc định là E_USER_NOTICE).
 *
 * @param string $function_name Hàm đã được gọi.
 * @param string $version       Phiên bản WordPress đã loại bỏ hàm.
 * @param string $replacement   Tùy chọn. Hàm lẽ ra nên được gọi. Mặc định chuỗi rỗng.
 */
function _deprecated_function( $function_name, $version, $replacement = '' ) {

	/**
	 * Kích hoạt khi một hàm không còn được khuyến nghị được gọi.
	 *
	 * @since 2.5.0
	 *
	 * @param string $function_name Hàm đã được gọi.
	 * @param string $replacement   Hàm lẽ ra nên được gọi.
	 * @param string $version       Phiên bản WordPress đã loại bỏ hàm.
	 */
	do_action( 'deprecated_function_run', $function_name, $replacement, $version );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho các hàm không còn được khuyến nghị hay không.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $trigger Có nên kích hoạt lỗi cho các hàm không còn được khuyến nghị hay không. Mặc định true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_function_trigger_error', true ) ) {
		if ( function_exists( '__' ) ) {
			if ( $replacement ) {
				$message = sprintf(
					/* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
					__( 'Function %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
					$function_name,
					$version,
					$replacement
				);
			} else {
				$message = sprintf(
					/* translators: 1: PHP function name, 2: Version number. */
					__( 'Function %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
					$function_name,
					$version
				);
			}
		} else {
			if ( $replacement ) {
				$message = sprintf(
					'Function %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
					$function_name,
					$version,
					$replacement
				);
			} else {
				$message = sprintf(
					'Function %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
					$function_name,
					$version
				);
			}
		}

		wp_trigger_error( '', $message, E_USER_DEPRECATED );
	}
}

/**
 * Đánh dấu một constructor là không còn được khuyến nghị và thông báo khi nó được sử dụng.
 *
 * Tương tự _deprecated_function(), nhưng với chuỗi khác. Được sử dụng để
 * loại bỏ các constructor kiểu PHP4.
 *
 * Hành vi hiện tại là kích hoạt lỗi người dùng nếu `WP_DEBUG` là true.
 *
 * Hàm này được sử dụng trong mọi phương thức constructor kiểu PHP4 không còn được khuyến nghị.
 *
 * @since 4.3.0
 * @since 4.5.0 Thêm tham số `$parent_class`.
 * @since 5.4.0 Hàm này không còn được đánh dấu là "private".
 * @since 5.4.0 Loại lỗi hiện được phân loại là E_USER_DEPRECATED (trước đây mặc định là E_USER_NOTICE).
 *
 * @param string $class_name   Lớp chứa constructor không còn được khuyến nghị.
 * @param string $version      Phiên bản WordPress đã loại bỏ hàm.
 * @param string $parent_class Tùy chọn. Lớp cha gọi constructor không còn được khuyến nghị.
 *                             Mặc định chuỗi rỗng.
 */
function _deprecated_constructor( $class_name, $version, $parent_class = '' ) {

	/**
	 * Kích hoạt khi một constructor không còn được khuyến nghị được gọi.
	 *
	 * @since 4.3.0
	 * @since 4.5.0 Thêm tham số `$parent_class`.
	 *
	 * @param string $class_name   Lớp chứa constructor không còn được khuyến nghị.
	 * @param string $version      Phiên bản WordPress đã loại bỏ hàm.
	 * @param string $parent_class Lớp cha gọi constructor không còn được khuyến nghị.
	 */
	do_action( 'deprecated_constructor_run', $class_name, $version, $parent_class );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho các hàm không còn được khuyến nghị hay không.
	 *
	 * `WP_DEBUG` phải được đặt thành true ngoài việc bộ lọc đánh giá là true.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $trigger Có nên kích hoạt lỗi cho các hàm không còn được khuyến nghị hay không. Mặc định true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_constructor_trigger_error', true ) ) {
		if ( function_exists( '__' ) ) {
			if ( $parent_class ) {
				$message = sprintf(
					/* translators: 1: PHP class name, 2: PHP parent class name, 3: Version number, 4: __construct() method. */
					__( 'The called constructor method for %1$s class in %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.' ),
					$class_name,
					$parent_class,
					$version,
					'<code>__construct()</code>'
				);
			} else {
				$message = sprintf(
					/* translators: 1: PHP class name, 2: Version number, 3: __construct() method. */
					__( 'The called constructor method for %1$s class is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
					$class_name,
					$version,
					'<code>__construct()</code>'
				);
			}
		} else {
			if ( $parent_class ) {
				$message = sprintf(
					'The called constructor method for %1$s class in %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.',
					$class_name,
					$parent_class,
					$version,
					'<code>__construct()</code>'
				);
			} else {
				$message = sprintf(
					'The called constructor method for %1$s class is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
					$class_name,
					$version,
					'<code>__construct()</code>'
				);
			}
		}

		wp_trigger_error( '', $message, E_USER_DEPRECATED );
	}
}

/**
 * Đánh dấu một lớp là không còn được khuyến nghị và thông báo khi nó được sử dụng.
 *
 * Có một hook {@see 'deprecated_class_run'} sẽ được gọi và có thể được sử dụng
 * để lấy backtrace đến tệp và hàm nào đã gọi lớp không còn được khuyến nghị.
 *
 * Hành vi hiện tại là kích hoạt lỗi người dùng nếu `WP_DEBUG` là true.
 *
 * Hàm này được sử dụng trong constructor của mọi lớp không còn được khuyến nghị.
 * Xem {@see _deprecated_constructor()} để loại bỏ các constructor kiểu PHP4.
 *
 * @since 6.4.0
 *
 * @param string $class_name  Tên của lớp đang được khởi tạo.
 * @param string $version     Phiên bản WordPress đã loại bỏ lớp.
 * @param string $replacement Tùy chọn. Lớp hoặc hàm lẽ ra nên được gọi.
 *                            Mặc định chuỗi rỗng.
 */
function _deprecated_class( $class_name, $version, $replacement = '' ) {

	/**
	 * Kích hoạt khi một lớp không còn được khuyến nghị được gọi.
	 *
	 * @since 6.4.0
	 *
	 * @param string $class_name  Tên của lớp đang được khởi tạo.
	 * @param string $replacement Lớp hoặc hàm lẽ ra nên được gọi.
	 * @param string $version     Phiên bản WordPress đã loại bỏ lớp.
	 */
	do_action( 'deprecated_class_run', $class_name, $replacement, $version );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho lớp không còn được khuyến nghị hay không.
	 *
	 * @since 6.4.0
	 *
	 * @param bool $trigger Có nên kích hoạt lỗi cho lớp không còn được khuyến nghị hay không. Mặc định true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_class_trigger_error', true ) ) {
		if ( function_exists( '__' ) ) {
			if ( $replacement ) {
				$message = sprintf(
					/* translators: 1: PHP class name, 2: Version number, 3: Alternative class or function name. */
					__( 'Class %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
					$class_name,
					$version,
					$replacement
				);
			} else {
				$message = sprintf(
					/* translators: 1: PHP class name, 2: Version number. */
					__( 'Class %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
					$class_name,
					$version
				);
			}
		} else {
			if ( $replacement ) {
				$message = sprintf(
					'Class %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
					$class_name,
					$version,
					$replacement
				);
			} else {
				$message = sprintf(
					'Class %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
					$class_name,
					$version
				);
			}
		}

		wp_trigger_error( '', $message, E_USER_DEPRECATED );
	}
}

/**
 * Đánh dấu một tệp là không còn được khuyến nghị và thông báo khi nó được sử dụng.
 *
 * Có một hook {@see 'deprecated_file_included'} sẽ được gọi và có thể được sử dụng
 * để lấy backtrace đến tệp và hàm nào đã include tệp không còn được khuyến nghị.
 *
 * Hành vi hiện tại là kích hoạt lỗi người dùng nếu `WP_DEBUG` là true.
 *
 * Hàm này được sử dụng trong mọi tệp không còn được khuyến nghị.
 *
 * @since 2.5.0
 * @since 5.4.0 Hàm này không còn được đánh dấu là "private".
 * @since 5.4.0 Loại lỗi hiện được phân loại là E_USER_DEPRECATED (trước đây mặc định là E_USER_NOTICE).
 *
 * @param string $file        Tệp đã được include.
 * @param string $version     Phiên bản WordPress đã loại bỏ tệp.
 * @param string $replacement Tùy chọn. Tệp lẽ ra nên được include dựa trên ABSPATH.
 *                            Mặc định chuỗi rỗng.
 * @param string $message     Tùy chọn. Thông báo liên quan đến thay đổi. Mặc định chuỗi rỗng.
 */
function _deprecated_file( $file, $version, $replacement = '', $message = '' ) {

	/**
	 * Kích hoạt khi một tệp không còn được khuyến nghị được gọi.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file        Tệp đã được gọi.
	 * @param string $replacement Tệp lẽ ra nên được include dựa trên ABSPATH.
	 * @param string $version     Phiên bản WordPress đã loại bỏ tệp.
	 * @param string $message     Thông báo liên quan đến thay đổi.
	 */
	do_action( 'deprecated_file_included', $file, $replacement, $version, $message );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho các tệp không còn được khuyến nghị hay không.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $trigger Có nên kích hoạt lỗi cho các tệp không còn được khuyến nghị hay không. Mặc định true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_file_trigger_error', true ) ) {
		$message = empty( $message ) ? '' : ' ' . $message;

		if ( function_exists( '__' ) ) {
			if ( $replacement ) {
				$message = sprintf(
					/* translators: 1: PHP file name, 2: Version number, 3: Alternative file name. */
					__( 'File %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
					$file,
					$version,
					$replacement
				) . $message;
			} else {
				$message = sprintf(
					/* translators: 1: PHP file name, 2: Version number. */
					__( 'File %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
					$file,
					$version
				) . $message;
			}
		} else {
			if ( $replacement ) {
				$message = sprintf(
					'File %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
					$file,
					$version,
					$replacement
				);
			} else {
				$message = sprintf(
					'File %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
					$file,
					$version
				) . $message;
			}
		}

		wp_trigger_error( '', $message, E_USER_DEPRECATED );
	}
}
/**
 * Đánh dấu một tham số hàm là không còn được khuyến nghị và thông báo khi nó được sử dụng.
 *
 * Hàm này được sử dụng bất cứ khi nào một tham số hàm không còn được khuyến nghị được sử dụng.
 * Trước khi hàm này được gọi, tham số phải được kiểm tra xem nó đã được sử dụng hay chưa
 * bằng cách so sánh với giá trị mặc định hoặc đánh giá xem nó có rỗng hay không.
 *
 * Ví dụ:
 *
 *     if ( ! empty( $deprecated ) ) {
 *         _deprecated_argument( __FUNCTION__, '3.0.0' );
 *     }
 *
 * Có một hook {@see 'deprecated_argument_run'} sẽ được gọi và có thể được sử dụng
 * để lấy backtrace đến tệp và hàm nào đã sử dụng tham số không còn được khuyến nghị.
 *
 * Hành vi hiện tại là kích hoạt lỗi người dùng nếu WP_DEBUG là true.
 *
 * @since 3.0.0
 * @since 5.4.0 Hàm này không còn được đánh dấu là "private".
 * @since 5.4.0 Loại lỗi hiện được phân loại là E_USER_DEPRECATED (trước đây mặc định là E_USER_NOTICE).
 *
 * @param string $function_name Hàm đã được gọi.
 * @param string $version       Phiên bản WordPress đã loại bỏ tham số được sử dụng.
 * @param string $message       Tùy chọn. Thông báo liên quan đến thay đổi. Mặc định chuỗi rỗng.
 */
function _deprecated_argument( $function_name, $version, $message = '' ) {

	/**
	 * Kích hoạt khi một tham số không còn được khuyến nghị được gọi.
	 *
	 * @since 3.0.0
	 *
	 * @param string $function_name Hàm đã được gọi.
	 * @param string $message       Thông báo liên quan đến thay đổi.
	 * @param string $version       Phiên bản WordPress đã loại bỏ tham số được sử dụng.
	 */
	do_action( 'deprecated_argument_run', $function_name, $message, $version );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho các tham số không còn được khuyến nghị hay không.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $trigger Có nên kích hoạt lỗi cho các tham số không còn được khuyến nghị hay không. Mặc định true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_argument_trigger_error', true ) ) {
		if ( function_exists( '__' ) ) {
			if ( $message ) {
				$message = sprintf(
					/* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
					__( 'Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s' ),
					$function_name,
					$version,
					$message
				);
			} else {
				$message = sprintf(
					/* translators: 1: PHP function name, 2: Version number. */
					__( 'Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
					$function_name,
					$version
				);
			}
		} else {
			if ( $message ) {
				$message = sprintf(
					'Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s',
					$function_name,
					$version,
					$message
				);
			} else {
				$message = sprintf(
					'Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.',
					$function_name,
					$version
				);
			}
		}

		wp_trigger_error( '', $message, E_USER_DEPRECATED );
	}
}

/**
 * Đánh dấu một action hoặc filter hook không còn được khuyến nghị và ném ra thông báo.
 *
 * Sử dụng action {@see 'deprecated_hook_run'} để lấy backtrace mô tả nơi
 * hook không còn được khuyến nghị được gọi.
 *
 * Hành vi mặc định là kích hoạt lỗi người dùng nếu `WP_DEBUG` là true.
 *
 * Hàm này được gọi bởi các hàm do_action_deprecated() và apply_filters_deprecated(),
 * và do đó thường không cần được gọi trực tiếp.
 *
 * @since 4.6.0
 * @since 5.4.0 Loại lỗi hiện được phân loại là E_USER_DEPRECATED (trước đây mặc định là E_USER_NOTICE).
 * @access private
 *
 * @param string $hook        Hook đã được sử dụng.
 * @param string $version     Phiên bản WordPress đã loại bỏ hook.
 * @param string $replacement Tùy chọn. Hook lẽ ra nên được sử dụng. Mặc định chuỗi rỗng.
 * @param string $message     Tùy chọn. Thông báo liên quan đến thay đổi. Mặc định chuỗi rỗng.
 */
function _deprecated_hook( $hook, $version, $replacement = '', $message = '' ) {
	/**
	 * Kích hoạt khi một hook không còn được khuyến nghị được gọi.
	 *
	 * @since 4.6.0
	 *
	 * @param string $hook        Hook đã được gọi.
	 * @param string $replacement Hook lẽ ra nên được sử dụng thay thế.
	 * @param string $version     Phiên bản WordPress đã loại bỏ tham số được sử dụng.
	 * @param string $message     Thông báo liên quan đến thay đổi.
	 */
	do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho hook không còn được khuyến nghị hay không.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $trigger Có nên kích hoạt lỗi cho hook không còn được khuyến nghị hay không. Yêu cầu
	 *                      `WP_DEBUG` được định nghĩa là true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_hook_trigger_error', true ) ) {
		$message = empty( $message ) ? '' : ' ' . $message;

		if ( $replacement ) {
			$message = sprintf(
				/* translators: 1: WordPress hook name, 2: Version number, 3: Alternative hook name. */
				__( 'Hook %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
				$hook,
				$version,
				$replacement
			) . $message;
		} else {
			$message = sprintf(
				/* translators: 1: WordPress hook name, 2: Version number. */
				__( 'Hook %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
				$hook,
				$version
			) . $message;
		}

		wp_trigger_error( '', $message, E_USER_DEPRECATED );
	}
}

/**
 * Đánh dấu một thứ gì đó được gọi không đúng cách.
 *
 * Có một hook {@see 'doing_it_wrong_run'} sẽ được gọi và có thể được sử dụng
 * để lấy backtrace đến tệp và hàm nào đã gọi hàm không đúng cách.
 *
 * Hành vi hiện tại là kích hoạt lỗi người dùng nếu `WP_DEBUG` là true.
 *
 * @since 3.1.0
 * @since 5.4.0 Hàm này không còn được đánh dấu là "private".
 *
 * @param string $function_name Hàm đã được gọi.
 * @param string $message       Thông báo giải thích điều gì đã được thực hiện không đúng cách.
 * @param string $version       Phiên bản WordPress nơi thông báo được thêm vào.
 */
function _doing_it_wrong( $function_name, $message, $version ) {

	/**
	 * Kích hoạt khi hàm đã cho đang được sử dụng không đúng cách.
	 *
	 * @since 3.1.0
	 *
	 * @param string $function_name Hàm đã được gọi.
	 * @param string $message       Thông báo giải thích điều gì đã được thực hiện không đúng cách.
	 * @param string $version       Phiên bản WordPress nơi thông báo được thêm vào.
	 */
	do_action( 'doing_it_wrong_run', $function_name, $message, $version );

	/**
	 * Lọc xem có nên kích hoạt lỗi cho các lời gọi _doing_it_wrong() hay không.
	 *
	 * @since 3.1.0
	 * @since 5.1.0 Thêm các tham số $function_name, $message và $version.
	 *
	 * @param bool   $trigger       Có nên kích hoạt lỗi cho các lời gọi _doing_it_wrong() hay không. Mặc định true.
	 * @param string $function_name Hàm đã được gọi.
	 * @param string $message       Thông báo giải thích điều gì đã được thực hiện không đúng cách.
	 * @param string $version       Phiên bản WordPress nơi thông báo được thêm vào.
	 */
	if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', true, $function_name, $message, $version ) ) {
		if ( function_exists( '__' ) ) {
			if ( $version ) {
				/* translators: %s: Version number. */
				$version = sprintf( __( '(This message was added in version %s.)' ), $version );
			}

			$message .= ' ' . sprintf(
				/* translators: %s: Documentation URL. */
				__( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ),
				__( 'https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/' )
			);

			$message = sprintf(
				/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: WordPress version number. */
				__( 'Function %1$s was called <strong>incorrectly</strong>. %2$s %3$s' ),
				$function_name,
				$message,
				$version
			);
		} else {
			if ( $version ) {
				$version = sprintf( '(This message was added in version %s.)', $version );
			}

			$message .= sprintf(
				' Please see <a href="%s">Debugging in WordPress</a> for more information.',
				'https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/'
			);

			$message = sprintf(
				'Function %1$s was called <strong>incorrectly</strong>. %2$s %3$s',
				$function_name,
				$message,
				$version
			);
		}

		wp_trigger_error( '', $message );
	}
}

/**
 * Tạo thông báo lỗi/cảnh báo/chú ý/loại bỏ ở cấp người dùng.
 *
 * Tạo thông báo khi `WP_DEBUG` là true.
 *
 * @since 6.4.0
 *
 * @param string $function_name Hàm đã kích hoạt lỗi.
 * @param string $message       Thông báo giải thích lỗi.
 *                              Thông báo có thể chứa các thẻ HTML được phép 'a' (với href), 'code',
 *                              'br', 'em', và 'strong' và các giao thức http hoặc https.
 *                              Nếu chứa các thẻ HTML hoặc giao thức khác, thông báo nên được escape
 *                              trước khi truyền vào hàm này để tránh bị loại bỏ {@see wp_kses()}.
 * @param int    $error_level   Tùy chọn. Loại lỗi được chỉ định cho lỗi này.
 *                              Chỉ hoạt động với họ hằng số E_USER. Mặc định E_USER_NOTICE.
 */
function wp_trigger_error( $function_name, $message, $error_level = E_USER_NOTICE ) {

	// Thoát sớm nếu WP_DEBUG không được bật.
	if ( ! WP_DEBUG ) {
		return;
	}

	/**
	 * Kích hoạt khi hàm đã cho kích hoạt thông báo lỗi/cảnh báo/chú ý/loại bỏ ở cấp người dùng.
	 *
	 * Có thể được sử dụng để theo dõi ngược debug.
	 *
	 * @since 6.4.0
	 *
	 * @param string $function_name Hàm đã được gọi.
	 * @param string $message       Thông báo giải thích điều gì đã được thực hiện không đúng cách.
	 * @param int    $error_level   Loại lỗi được chỉ định cho lỗi này.
	 */
	do_action( 'wp_trigger_error_run', $function_name, $message, $error_level );

	if ( ! empty( $function_name ) ) {
		$message = sprintf( '%s(): %s', $function_name, $message );
	}

	$message = wp_kses(
		$message,
		array(
			'a'      => array( 'href' => true ),
			'br'     => array(),
			'code'   => array(),
			'em'     => array(),
			'strong' => array(),
		),
		array( 'http', 'https' )
	);

	if ( E_USER_ERROR === $error_level ) {
		throw new WP_Exception( $message );
	}

	trigger_error( $message, $error_level );
}

/**
 * Xác định xem máy chủ có đang chạy phiên bản lighttpd trước 1.5.0 hay không.
 *
 * @since 2.5.0
 *
 * @return bool Liệu máy chủ có đang chạy lighttpd < 1.5.0 hay không.
 */
function is_lighttpd_before_150() {
	$server_parts    = explode( '/', isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '' );
	$server_parts[1] = isset( $server_parts[1] ) ? $server_parts[1] : '';

	return ( 'lighttpd' === $server_parts[0] && -1 === version_compare( $server_parts[1], '1.5.0' ) );
}

/**
 * Xác định xem module được chỉ định có tồn tại trong cấu hình Apache hay không.
 *
 * @since 2.5.0
 *
 * @global bool $is_apache
 *
 * @param string $mod           Module, ví dụ mod_rewrite.
 * @param bool   $default_value Tùy chọn. Giá trị trả về mặc định nếu module không được tìm thấy. Mặc định false.
 * @return bool Liệu module được chỉ định có được tải hay không.
 */
function apache_mod_loaded( $mod, $default_value = false ) {
	global $is_apache;

	if ( ! $is_apache ) {
		return false;
	}

	$loaded_mods = array();

	if ( function_exists( 'apache_get_modules' ) ) {
		$loaded_mods = apache_get_modules();

		if ( in_array( $mod, $loaded_mods, true ) ) {
			return true;
		}
	}

	if ( empty( $loaded_mods )
		&& function_exists( 'phpinfo' )
		&& ! str_contains( ini_get( 'disable_functions' ), 'phpinfo' )
	) {
		ob_start();
		phpinfo( INFO_MODULES );
		$phpinfo = ob_get_clean();

		if ( str_contains( $phpinfo, $mod ) ) {
			return true;
		}
	}

	return $default_value;
}

/**
 * Kiểm tra xem IIS 7+ có hỗ trợ permalink đẹp hay không.
 *
 * @since 2.8.0
 *
 * @global bool $is_iis7
 *
 * @return bool Liệu IIS7 có hỗ trợ permalink hay không.
 */
function iis7_supports_permalinks() {
	global $is_iis7;

	$supports_permalinks = false;
	if ( $is_iis7 ) {
		/* Đầu tiên chúng ta kiểm tra xem lớp DOMDocument có tồn tại hay không. Nếu nó không tồn tại, thì chúng ta không thể
		 * dễ dàng cập nhật tệp cấu hình xml, do đó chúng ta chỉ thoát ra và thông báo cho người dùng rằng
		 * permalink đẹp không thể được sử dụng.
		 *
		 * Tiếp theo chúng ta kiểm tra xem Module URL Rewrite 1.1 có được tải và bật cho trang web hay không. Khi
		 * URL Rewrite 1.1 được tải, nó luôn đặt một biến máy chủ có tên 'IIS_UrlRewriteModule'.
		 * Cuối cùng chúng ta đảm bảo rằng PHP đang chạy qua FastCGI. Điều này quan trọng vì nếu nó chạy
		 * qua ISAPI thì permalink đẹp sẽ không hoạt động.
		 */
		$supports_permalinks = class_exists( 'DOMDocument', false ) && isset( $_SERVER['IIS_UrlRewriteModule'] ) && ( 'cgi-fcgi' === PHP_SAPI );
	}

	/**
	 * Lọc xem IIS 7+ có hỗ trợ permalink đẹp hay không.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $supports_permalinks Liệu IIS7 có hỗ trợ permalink hay không. Mặc định false.
	 */
	return apply_filters( 'iis7_supports_permalinks', $supports_permalinks );
}

/**
 * Xác thực tên file và đường dẫn dựa trên tập hợp quy tắc được phép.
 *
 * Giá trị trả về `1` nghĩa là đường dẫn file chứa duyệt thư mục.
 *
 * Giá trị trả về `2` nghĩa là đường dẫn file chứa đường dẫn ổ đĩa Windows.
 *
 * Giá trị trả về `3` nghĩa là file không nằm trong danh sách file được phép.
 *
 * @since 1.2.0
 *
 * @param string   $file          Đường dẫn file.
 * @param string[] $allowed_files Tùy chọn. Mảng các file được phép. Mặc định mảng rỗng.
 * @return int 0 nghĩa là không có vấn đề, lớn hơn 0 nghĩa là có vấn đề.
 */
function validate_file( $file, $allowed_files = array() ) {
	if ( ! is_scalar( $file ) || '' === $file ) {
		return 0;
	}

	// Chuẩn hóa đường dẫn cho máy chủ Windows.
	$file = wp_normalize_path( $file );
	// Chuẩn hóa đường dẫn cho $allowed_files để so sánh nhất quán.
	$allowed_files = array_map( 'wp_normalize_path', $allowed_files );

	// `../` đơn lẻ không được phép:
	if ( '../' === $file ) {
		return 1;
	}

	// Nhiều hơn một lần xuất hiện `../` không được phép:
	if ( preg_match_all( '#\.\./#', $file, $matches, PREG_SET_ORDER ) && ( count( $matches ) > 1 ) ) {
		return 1;
	}

	// `../` không xuất hiện ở cuối đường dẫn thì không được phép:
	if ( str_contains( $file, '../' ) && '../' !== mb_substr( $file, -3, 3 ) ) {
		return 1;
	}

	// Các file không nằm trong danh sách file được phép thì không được phép:
	if ( ! empty( $allowed_files ) && ! in_array( $file, $allowed_files, true ) ) {
		return 3;
	}

	// Đường dẫn ổ đĩa Windows tuyệt đối không được phép:
	if ( ':' === substr( $file, 1, 1 ) ) {
		return 2;
	}

	return 0;
}

/**
 * Xác định xem có bắt buộc sử dụng SSL cho Màn hình Quản trị hay không.
 *
 * @since 2.6.0
 *
 * @param string|bool|null $force Tùy chọn. Có bắt buộc SSL trong màn hình quản trị hay không. Mặc định null.
 * @return bool True nếu bắt buộc, false nếu không bắt buộc.
 */
function force_ssl_admin( $force = null ) {
	static $forced = false;

	if ( ! is_null( $force ) ) {
		$old_forced = $forced;
		$forced     = (bool) $force;
		return $old_forced;
	}

	return $forced;
}

/**
 * Đoán URL cho trang web.
 *
 * Sẽ xóa các liên kết wp-admin để chỉ trả về các URL không nằm trong
 * thư mục wp-admin.
 *
 * @since 2.6.0
 *
 * @return string URL đã đoán.
 */
function wp_guess_url() {
	if ( defined( 'WP_SITEURL' ) && '' !== WP_SITEURL ) {
		$url = WP_SITEURL;
	} else {
		$abspath_fix         = str_replace( '\\', '/', ABSPATH );
		$script_filename_dir = dirname( $_SERVER['SCRIPT_FILENAME'] );

		// Yêu cầu dành cho trang quản trị.
		if ( str_contains( $_SERVER['REQUEST_URI'], 'wp-admin' ) || str_contains( $_SERVER['REQUEST_URI'], 'wp-login.php' ) ) {
			$path = preg_replace( '#/(wp-admin/?.*|wp-login\.php.*)#i', '', $_SERVER['REQUEST_URI'] );

			// Yêu cầu dành cho một file trong ABSPATH.
		} elseif ( $script_filename_dir . '/' === $abspath_fix ) {
			// Loại bỏ file/tham số truy vấn trong đường dẫn.
			$path = preg_replace( '#/[^/]*$#i', '', $_SERVER['PHP_SELF'] );

		} else {
			if ( str_contains( $_SERVER['SCRIPT_FILENAME'], $abspath_fix ) ) {
				// Yêu cầu đang truy cập một file bên trong ABSPATH.
				$directory = str_replace( ABSPATH, '', $script_filename_dir );
				// Loại bỏ thư mục con, và file/tham số truy vấn.
				$path = preg_replace( '#/' . preg_quote( $directory, '#' ) . '/[^/]*$#i', '', $_SERVER['REQUEST_URI'] );
			} elseif ( str_contains( $abspath_fix, $script_filename_dir ) ) {
				// Yêu cầu đang truy cập một file phía trên ABSPATH.
				$subdirectory = substr( $abspath_fix, strpos( $abspath_fix, $script_filename_dir ) + strlen( $script_filename_dir ) );
				// Loại bỏ file/tham số truy vấn từ đường dẫn, thêm thư mục con vào cài đặt.
				$path = preg_replace( '#/[^/]*$#i', '', $_SERVER['REQUEST_URI'] ) . $subdirectory;
			} else {
				$path = $_SERVER['REQUEST_URI'];
			}
		}

		$schema = is_ssl() ? 'https://' : 'http://'; // set_url_scheme() is not defined yet.
		$url    = $schema . $_SERVER['HTTP_HOST'] . $path;
	}

	return rtrim( $url, '/' );
}

/**
 * Tạm thời dừng việc thêm vào bộ nhớ đệm.
 *
 * Ngăn thêm dữ liệu mới vào bộ nhớ đệm, nhưng vẫn cho phép truy xuất bộ nhớ đệm.
 * Điều này hữu ích cho các thao tác như nhập dữ liệu, khi nhiều dữ liệu
 * sẽ được thêm vào bộ nhớ đệm mà hầu như không có ích.
 *
 * Việc tạm dừng kéo dài tối đa trong một lần tải trang. Nhớ gọi lại hàm này
 * nếu bạn muốn bật lại việc thêm bộ nhớ đệm sớm hơn.
 *
 * @since 3.3.0
 *
 * @param bool $suspend Tùy chọn. Tạm dừng thêm nếu true, bật lại nếu false.
 *                      Mặc định là không thay đổi cài đặt hiện tại.
 * @return bool Cài đặt tạm dừng hiện tại.
 */
function wp_suspend_cache_addition( $suspend = null ) {
	static $_suspend = false;

	if ( is_bool( $suspend ) ) {
		$_suspend = $suspend;
	}

	return $_suspend;
}

/**
 * Tạm dừng việc vô hiệu hóa bộ nhớ đệm.
 *
 * Bật và tắt việc vô hiệu hóa bộ nhớ đệm. Hữu ích trong quá trình nhập dữ liệu khi bạn không muốn
 * vô hiệu hóa mỗi khi một bài viết được chèn. Người gọi phải chắc chắn rằng những gì họ
 * đang làm sẽ không dẫn đến bộ nhớ đệm không nhất quán khi việc vô hiệu hóa bị tạm dừng.
 *
 * @since 2.7.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param bool $suspend Tùy chọn. Có tạm dừng hoặc bật lại việc vô hiệu hóa bộ nhớ đệm hay không. Mặc định true.
 * @return bool Cài đặt tạm dừng hiện tại.
 */
function wp_suspend_cache_invalidation( $suspend = true ) {
	global $_wp_suspend_cache_invalidation;

	$current_suspend                = $_wp_suspend_cache_invalidation;
	$_wp_suspend_cache_invalidation = $suspend;
	return $current_suspend;
}

/**
 * Xác định xem một trang web có phải là trang chính của mạng hiện tại hay không.
 *
 * @since 3.0.0
 * @since 4.9.0 Tham số `$network_id` được thêm vào.
 *
 * @param int $site_id    Tùy chọn. ID trang web cần kiểm tra. Mặc định là trang web hiện tại.
 * @param int $network_id Tùy chọn. ID mạng của mạng cần kiểm tra.
 *                        Mặc định là mạng hiện tại.
 * @return bool True nếu $site_id là trang chính của mạng, hoặc nếu không
 *              chạy Multisite.
 */
function is_main_site( $site_id = null, $network_id = null ) {
	if ( ! is_multisite() ) {
		return true;
	}

	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	$site_id = (int) $site_id;

	return get_main_site_id( $network_id ) === $site_id;
}

/**
 * Lấy ID trang chính.
 *
 * @since 4.9.0
 *
 * @param int $network_id Tùy chọn. ID của mạng cần lấy trang chính.
 *                        Mặc định là mạng hiện tại.
 * @return int ID của trang chính.
 */
function get_main_site_id( $network_id = null ) {
	if ( ! is_multisite() ) {
		return get_current_blog_id();
	}

	$network = get_network( $network_id );
	if ( ! $network ) {
		return 0;
	}

	return $network->site_id;
}

/**
 * Xác định xem một mạng có phải là mạng chính của cài đặt Multisite hay không.
 *
 * @since 3.7.0
 *
 * @param int $network_id Tùy chọn. ID mạng cần kiểm tra. Mặc định là mạng hiện tại.
 * @return bool True nếu $network_id là mạng chính, hoặc nếu không chạy Multisite.
 */
function is_main_network( $network_id = null ) {
	if ( ! is_multisite() ) {
		return true;
	}

	if ( null === $network_id ) {
		$network_id = get_current_network_id();
	}

	$network_id = (int) $network_id;

	return ( get_main_network_id() === $network_id );
}

/**
 * Lấy ID mạng chính.
 *
 * @since 4.3.0
 *
 * @return int ID của mạng chính.
 */
function get_main_network_id() {
	if ( ! is_multisite() ) {
		return 1;
	}

	$current_network = get_network();

	if ( defined( 'PRIMARY_NETWORK_ID' ) ) {
		$main_network_id = PRIMARY_NETWORK_ID;
	} elseif ( isset( $current_network->id ) && 1 === (int) $current_network->id ) {
		// Nếu mạng hiện tại có ID là 1, giả định nó là mạng chính.
		$main_network_id = 1;
	} else {
		$_networks       = get_networks(
			array(
				'fields' => 'ids',
				'number' => 1,
			)
		);
		$main_network_id = array_shift( $_networks );
	}

	/**
	 * Lọc ID mạng chính.
	 *
	 * @since 4.3.0
	 *
	 * @param int $main_network_id ID của mạng chính.
	 */
	return (int) apply_filters( 'get_main_network_id', $main_network_id );
}

/**
 * Xác định xem meta trang web có được bật hay không.
 *
 * Hàm này kiểm tra xem bảng cơ sở dữ liệu 'blogmeta' có tồn tại hay không. Kết quả được lưu
 * như một cài đặt cho mạng chính, về cơ bản là một cài đặt toàn cục. Các yêu cầu tiếp theo
 * sẽ tham chiếu đến cài đặt này thay vì chạy truy vấn.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @return bool True nếu meta trang web được hỗ trợ, false nếu ngược lại.
 */
function is_site_meta_supported() {
	global $wpdb;

	if ( ! is_multisite() ) {
		return false;
	}

	$network_id = get_main_network_id();

	$supported = get_network_option( $network_id, 'site_meta_supported', false );
	if ( false === $supported ) {
		$supported = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->blogmeta}'" ) ? 1 : 0;

		update_network_option( $network_id, 'site_meta_supported', $supported );
	}

	return (bool) $supported;
}

/**
 * Chỉnh sửa gmt_offset để xử lý múi giờ thông minh.
 *
 * Ghi đè tùy chọn gmt_offset nếu có timezone_string khả dụng.
 *
 * @since 2.8.0
 *
 * @return float|false Độ lệch GMT của múi giờ, false nếu ngược lại.
 */
function wp_timezone_override_offset() {
	$timezone_string = get_option( 'timezone_string' );
	if ( ! $timezone_string ) {
		return false;
	}

	$timezone_object = timezone_open( $timezone_string );
	$datetime_object = date_create();
	if ( false === $timezone_object || false === $datetime_object ) {
		return false;
	}

	return round( timezone_offset_get( $timezone_object, $datetime_object ) / HOUR_IN_SECONDS, 2 );
}

/**
 * Hàm hỗ trợ sắp xếp múi giờ.
 *
 * @since 2.9.0
 * @access private
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function _wp_timezone_choice_usort_callback( $a, $b ) {
	// Không sử dụng phiên bản đã dịch của Etc.
	if ( 'Etc' === $a['continent'] && 'Etc' === $b['continent'] ) {
		// Sắp xếp theo thứ tự giống danh sách thả xuống cũ hơn.
		if ( str_starts_with( $a['city'], 'GMT+' ) && str_starts_with( $b['city'], 'GMT+' ) ) {
			return -1 * ( strnatcasecmp( $a['city'], $b['city'] ) );
		}

		if ( 'UTC' === $a['city'] ) {
			if ( str_starts_with( $b['city'], 'GMT+' ) ) {
				return 1;
			}

			return -1;
		}

		if ( 'UTC' === $b['city'] ) {
			if ( str_starts_with( $a['city'], 'GMT+' ) ) {
				return -1;
			}

			return 1;
		}

		return strnatcasecmp( $a['city'], $b['city'] );
	}

	if ( $a['t_continent'] === $b['t_continent'] ) {
		if ( $a['t_city'] === $b['t_city'] ) {
			return strnatcasecmp( $a['t_subcity'], $b['t_subcity'] );
		}

		return strnatcasecmp( $a['t_city'], $b['t_city'] );
	} else {
		// Đẩy Etc xuống cuối danh sách.
		if ( 'Etc' === $a['continent'] ) {
			return 1;
		}

		if ( 'Etc' === $b['continent'] ) {
			return -1;
		}

		return strnatcasecmp( $a['t_continent'], $b['t_continent'] );
	}
}

/**
 * Trả về danh sách chuỗi múi giờ được định dạng đẹp.
 *
 * @since 2.9.0
 * @since 4.7.0 Thêm tham số `$locale`.
 *
 * @param string $selected_zone Múi giờ đã chọn.
 * @param string $locale        Tùy chọn. Locale để tải múi giờ. Mặc định locale hiện tại của trang web.
 * @return string
 */
function wp_timezone_choice( $selected_zone, $locale = null ) {
	static $mo_loaded = false, $locale_loaded = null;

	$continents = array( 'Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific' );

	// Tải bản dịch cho các lục địa và thành phố.
	if ( ! $mo_loaded || $locale !== $locale_loaded ) {
		$locale_loaded = $locale ? $locale : get_locale();
		$mofile        = WP_LANG_DIR . '/continents-cities-' . $locale_loaded . '.mo';
		unload_textdomain( 'continents-cities', true );
		load_textdomain( 'continents-cities', $mofile, $locale_loaded );
		$mo_loaded = true;
	}

	$tz_identifiers = timezone_identifiers_list();
	$zonen          = array();

	foreach ( $tz_identifiers as $zone ) {
		$zone = explode( '/', $zone );
		if ( ! in_array( $zone[0], $continents, true ) ) {
			continue;
		}

		// Điều này xác định những gì được đặt và dịch - chúng ta không dịch các chuỗi Etc/* ở đây, chúng được xử lý sau.
		$exists    = array(
			0 => ( isset( $zone[0] ) && $zone[0] ),
			1 => ( isset( $zone[1] ) && $zone[1] ),
			2 => ( isset( $zone[2] ) && $zone[2] ),
		);
		$exists[3] = ( $exists[0] && 'Etc' !== $zone[0] );
		$exists[4] = ( $exists[1] && $exists[3] );
		$exists[5] = ( $exists[2] && $exists[3] );

		// phpcs:disable WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
		$zonen[] = array(
			'continent'   => ( $exists[0] ? $zone[0] : '' ),
			'city'        => ( $exists[1] ? $zone[1] : '' ),
			'subcity'     => ( $exists[2] ? $zone[2] : '' ),
			't_continent' => ( $exists[3] ? translate( str_replace( '_', ' ', $zone[0] ), 'continents-cities' ) : '' ),
			't_city'      => ( $exists[4] ? translate( str_replace( '_', ' ', $zone[1] ), 'continents-cities' ) : '' ),
			't_subcity'   => ( $exists[5] ? translate( str_replace( '_', ' ', $zone[2] ), 'continents-cities' ) : '' ),
		);
		// phpcs:enable
	}
	usort( $zonen, '_wp_timezone_choice_usort_callback' );

	$structure = array();

	if ( empty( $selected_zone ) ) {
		$structure[] = '<option selected="selected" value="">' . __( 'Select a city' ) . '</option>';
	}

	// Nếu đây là chuỗi múi giờ đã lỗi thời nhưng vẫn hợp lệ, hiển thị nó ở đầu danh sách nguyên trạng.
	if ( in_array( $selected_zone, $tz_identifiers, true ) === false
		&& in_array( $selected_zone, timezone_identifiers_list( DateTimeZone::ALL_WITH_BC ), true )
	) {
		$structure[] = '<option selected="selected" value="' . esc_attr( $selected_zone ) . '">' . esc_html( $selected_zone ) . '</option>';
	}

	foreach ( $zonen as $key => $zone ) {
		// Xây dựng giá trị trong một mảng để nối sau.
		$value = array( $zone['continent'] );

		if ( empty( $zone['city'] ) ) {
			// Nó ở cấp lục địa (thường sẽ không xảy ra).
			$display = $zone['t_continent'];
		} else {
			// Nằm trong một nhóm lục địa.

			// Optgroup lục địa.
			if ( ! isset( $zonen[ $key - 1 ] ) || $zonen[ $key - 1 ]['continent'] !== $zone['continent'] ) {
				$label       = $zone['t_continent'];
				$structure[] = '<optgroup label="' . esc_attr( $label ) . '">';
			}

			// Thêm thành phố vào giá trị.
			$value[] = $zone['city'];

			$display = $zone['t_city'];
			if ( ! empty( $zone['subcity'] ) ) {
				// Thêm thành phố phụ vào giá trị.
				$value[]  = $zone['subcity'];
				$display .= ' - ' . $zone['t_subcity'];
			}
		}

		// Xây dựng giá trị.
		$value    = implode( '/', $value );
		$selected = '';
		if ( $value === $selected_zone ) {
			$selected = 'selected="selected" ';
		}
		$structure[] = '<option ' . $selected . 'value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';

		// Đóng optgroup lục địa.
		if ( ! empty( $zone['city'] ) && ( ! isset( $zonen[ $key + 1 ] ) || ( isset( $zonen[ $key + 1 ] ) && $zonen[ $key + 1 ]['continent'] !== $zone['continent'] ) ) ) {
			$structure[] = '</optgroup>';
		}
	}

	// Xử lý UTC.
	$structure[] = '<optgroup label="' . esc_attr__( 'UTC' ) . '">';
	$selected    = '';
	if ( 'UTC' === $selected_zone ) {
		$selected = 'selected="selected" ';
	}
	$structure[] = '<option ' . $selected . 'value="' . esc_attr( 'UTC' ) . '">' . __( 'UTC' ) . '</option>';
	$structure[] = '</optgroup>';

	// Xử lý độ lệch UTC thủ công.
	$structure[]  = '<optgroup label="' . esc_attr__( 'Manual Offsets' ) . '">';
	$offset_range = array(
		-12,
		-11.5,
		-11,
		-10.5,
		-10,
		-9.5,
		-9,
		-8.5,
		-8,
		-7.5,
		-7,
		-6.5,
		-6,
		-5.5,
		-5,
		-4.5,
		-4,
		-3.5,
		-3,
		-2.5,
		-2,
		-1.5,
		-1,
		-0.5,
		0,
		0.5,
		1,
		1.5,
		2,
		2.5,
		3,
		3.5,
		4,
		4.5,
		5,
		5.5,
		5.75,
		6,
		6.5,
		7,
		7.5,
		8,
		8.5,
		8.75,
		9,
		9.5,
		10,
		10.5,
		11,
		11.5,
		12,
		12.75,
		13,
		13.75,
		14,
	);
	foreach ( $offset_range as $offset ) {
		if ( 0 <= $offset ) {
			$offset_name = '+' . $offset;
		} else {
			$offset_name = (string) $offset;
		}

		$offset_value = $offset_name;
		$offset_name  = str_replace( array( '.25', '.5', '.75' ), array( ':15', ':30', ':45' ), $offset_name );
		$offset_name  = 'UTC' . $offset_name;
		$offset_value = 'UTC' . $offset_value;
		$selected     = '';
		if ( $offset_value === $selected_zone ) {
			$selected = 'selected="selected" ';
		}
		$structure[] = '<option ' . $selected . 'value="' . esc_attr( $offset_value ) . '">' . esc_html( $offset_name ) . '</option>';

	}
	$structure[] = '</optgroup>';

	return implode( "\n", $structure );
}

/**
 * Loại bỏ thẻ đóng comment và thẻ đóng PHP từ header file được WP sử dụng.
 *
 * @since 2.8.0
 * @access private
 *
 * @see https://core.trac.wordpress.org/ticket/8497
 *
 * @param string $str Comment header cần dọn dẹp.
 * @return string
 */
function _cleanup_header_comment( $str ) {
	return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $str ) );
}

/**
 * Xóa vĩnh viễn các bình luận hoặc bài viết thuộc bất kỳ loại nào đã giữ trạng thái
 * 'trash' trong số ngày được định nghĩa trong EMPTY_TRASH_DAYS.
 *
 * Giá trị mặc định của `EMPTY_TRASH_DAYS` là 30 (ngày).
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 */
function wp_scheduled_delete() {
	global $wpdb;

	$delete_timestamp = time() - ( DAY_IN_SECONDS * EMPTY_TRASH_DAYS );

	$posts_to_delete = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d", $delete_timestamp ), ARRAY_A );

	foreach ( (array) $posts_to_delete as $post ) {
		$post_id = (int) $post['post_id'];
		if ( ! $post_id ) {
			continue;
		}

		$del_post = get_post( $post_id );

		if ( ! $del_post || 'trash' !== $del_post->post_status ) {
			delete_post_meta( $post_id, '_wp_trash_meta_status' );
			delete_post_meta( $post_id, '_wp_trash_meta_time' );
		} else {
			wp_delete_post( $post_id );
		}
	}

	$comments_to_delete = $wpdb->get_results( $wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d", $delete_timestamp ), ARRAY_A );

	foreach ( (array) $comments_to_delete as $comment ) {
		$comment_id = (int) $comment['comment_id'];
		if ( ! $comment_id ) {
			continue;
		}

		$del_comment = get_comment( $comment_id );

		if ( ! $del_comment || 'trash' !== $del_comment->comment_approved ) {
			delete_comment_meta( $comment_id, '_wp_trash_meta_time' );
			delete_comment_meta( $comment_id, '_wp_trash_meta_status' );
		} else {
			wp_delete_comment( $del_comment );
		}
	}
}

/**
 * Lấy metadata từ file.
 *
 * Tìm kiếm metadata trong 8 KB đầu tiên của file, chẳng hạn như plugin hoặc theme.
 * Mỗi phần metadata phải nằm trên dòng riêng. Các trường không thể kéo dài nhiều
 * dòng, giá trị sẽ bị cắt ở cuối dòng đầu tiên.
 *
 * Nếu dữ liệu file không nằm trong 8 KB đầu tiên đó, tác giả nên sửa lại
 * file plugin và di chuyển header dữ liệu lên đầu.
 *
 * @link https://codex.wordpress.org/File_Header
 *
 * @since 2.9.0
 *
 * @param string $file            Đường dẫn tuyệt đối đến file.
 * @param array  $default_headers Danh sách header, theo định dạng `array( 'HeaderKey' => 'Header Name' )`.
 * @param string $context         Tùy chọn. Nếu được chỉ định, thêm hook bộ lọc {@see 'extra_$context_headers'}.
 *                                Mặc định chuỗi rỗng.
 * @return string[] Mảng giá trị header file được khóa bởi tên header.
 */
function get_file_data( $file, $default_headers, $context = '' ) {
	// Chỉ lấy 8 KB đầu tiên của file.
	$file_data = file_get_contents( $file, false, null, 0, 8 * KB_IN_BYTES );

	if ( false === $file_data ) {
		$file_data = '';
	}

	// Đảm bảo bắt được các dòng kết thúc chỉ bằng CR.
	$file_data = str_replace( "\r", "\n", $file_data );

	/**
	 * Lọc các header file bổ sung theo ngữ cảnh.
	 *
	 * Phần động của tên hook, `$context`, tham chiếu đến
	 * ngữ cảnh nơi các header bổ sung có thể được tải.
	 *
	 * @since 2.9.0
	 *
	 * @param array $extra_context_headers Mảng rỗng theo mặc định.
	 */
	$extra_headers = $context ? apply_filters( "extra_{$context}_headers", array() ) : array();
	if ( $extra_headers ) {
		$extra_headers = array_combine( $extra_headers, $extra_headers ); // Khóa bằng giá trị.
		$all_headers   = array_merge( $extra_headers, (array) $default_headers );
	} else {
		$all_headers = $default_headers;
	}

	foreach ( $all_headers as $field => $regex ) {
		if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$all_headers[ $field ] = _cleanup_header_comment( $match[1] );
		} else {
			$all_headers[ $field ] = '';
		}
	}

	return $all_headers;
}

/**
 * Trả về true.
 *
 * Hữu ích để dễ dàng trả về true cho các bộ lọc.
 *
 * @since 3.0.0
 *
 * @see __return_false()
 *
 * @return true True.
 */
function __return_true() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	return true;
}

/**
 * Trả về false.
 *
 * Hữu ích để dễ dàng trả về false cho các bộ lọc.
 *
 * @since 3.0.0
 *
 * @see __return_true()
 *
 * @return false False.
 */
function __return_false() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	return false;
}

/**
 * Trả về 0.
 *
 * Hữu ích để dễ dàng trả về 0 cho các bộ lọc.
 *
 * @since 3.0.0
 *
 * @return int 0.
 */
function __return_zero() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	return 0;
}

/**
 * Trả về mảng rỗng.
 *
 * Hữu ích để dễ dàng trả về mảng rỗng cho các bộ lọc.
 *
 * @since 3.0.0
 *
 * @return array Mảng rỗng.
 */
function __return_empty_array() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	return array();
}

/**
 * Trả về null.
 *
 * Hữu ích để dễ dàng trả về null cho các bộ lọc.
 *
 * @since 3.4.0
 *
 * @return null Giá trị null.
 */
function __return_null() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	return null;
}

/**
 * Trả về chuỗi rỗng.
 *
 * Hữu ích để dễ dàng trả về chuỗi rỗng cho các bộ lọc.
 *
 * @since 3.7.0
 *
 * @see __return_null()
 *
 * @return string Chuỗi rỗng.
 */
function __return_empty_string() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	return '';
}

/**
 * Gửi header HTTP để vô hiệu hóa việc phát hiện kiểu nội dung trong các trình duyệt hỗ trợ.
 *
 * @since 3.0.0
 *
 * @see https://blogs.msdn.com/ie/archive/2008/07/02/ie8-security-part-v-comprehensive-protection.aspx
 * @see https://src.chromium.org/viewvc/chrome?view=rev&revision=6985
 */
function send_nosniff_header() {
	header( 'X-Content-Type-Options: nosniff' );
}

/**
 * Trả về biểu thức MySQL để chọn số tuần dựa trên tùy chọn start_of_week.
 *
 * @ignore
 * @since 3.0.0
 *
 * @param string $column Cột cơ sở dữ liệu.
 * @return string Mệnh đề SQL.
 */
function _wp_mysql_week( $column ) {
	$start_of_week = (int) get_option( 'start_of_week' );
	switch ( $start_of_week ) {
		case 1:
			return "WEEK( $column, 1 )";
		case 2:
		case 3:
		case 4:
		case 5:
		case 6:
			return "WEEK( DATE_SUB( $column, INTERVAL $start_of_week DAY ), 0 )";
		case 0:
		default:
			return "WEEK( $column, 0 )";
	}
}

/**
 * Tìm vòng lặp phân cấp sử dụng hàm callback ánh xạ ID đối tượng sang ID cha.
 *
 * @since 3.1.0
 * @access private
 *
 * @param callable $callback      Hàm nhận ( ID, $callback_args ) và xuất ra parent_ID.
 * @param int      $start         ID để bắt đầu kiểm tra vòng lặp.
 * @param int      $start_parent  Parent_ID của $start để sử dụng thay vì gọi $callback( $start ).
 *                                Sử dụng null để luôn dùng $callback.
 * @param array    $callback_args Tùy chọn. Các đối số bổ sung gửi đến $callback. Mặc định mảng rỗng.
 * @return array ID của tất cả các thành viên trong vòng lặp.
 */
function wp_find_hierarchy_loop( $callback, $start, $start_parent, $callback_args = array() ) {
	$override = is_null( $start_parent ) ? array() : array( $start => $start_parent );

	$arbitrary_loop_member = wp_find_hierarchy_loop_tortoise_hare( $callback, $start, $override, $callback_args );
	if ( ! $arbitrary_loop_member ) {
		return array();
	}

	return wp_find_hierarchy_loop_tortoise_hare( $callback, $arbitrary_loop_member, $override, $callback_args, true );
}

/**
 * Sử dụng thuật toán "Rùa và Thỏ" để phát hiện vòng lặp.
 *
 * Mỗi bước của thuật toán, thỏ đi hai bước và rùa đi một bước.
 * Nếu thỏ bắt kịp rùa, phải có vòng lặp.
 *
 * @since 3.1.0
 * @access private
 *
 * @param callable $callback      Hàm nhận ( ID, callback_arg, ... ) và xuất ra parent_ID.
 * @param int      $start         ID để bắt đầu kiểm tra vòng lặp.
 * @param array    $override      Tùy chọn. Mảng ( ID => parent_ID, ... ) để sử dụng thay cho $callback.
 *                                Mặc định mảng rỗng.
 * @param array    $callback_args Tùy chọn. Các đối số bổ sung gửi đến $callback. Mặc định mảng rỗng.
 * @param bool     $_return_loop  Tùy chọn. Trả về các thành viên vòng lặp hay chỉ phát hiện sự tồn tại?
 *                                Chỉ đặt true nếu bạn đã biết $start là phần của vòng lặp (nếu không
 *                                mảng trả về có thể bao gồm các nhánh). Mặc định false.
 * @return mixed ID vô hướng của một thành viên bất kỳ trong vòng lặp, hoặc mảng ID của tất cả thành viên
 *               nếu $_return_loop
 */
function wp_find_hierarchy_loop_tortoise_hare( $callback, $start, $override = array(), $callback_args = array(), $_return_loop = false ) {
	$tortoise        = $start;
	$hare            = $start;
	$evanescent_hare = $start;
	$return          = array();

	// Đặt evanescent_hare đi trước hare một bước. Tăng hare hai bước.
	while (
		$tortoise
	&&
		( $evanescent_hare = isset( $override[ $hare ] ) ? $override[ $hare ] : call_user_func_array( $callback, array_merge( array( $hare ), $callback_args ) ) )
	&&
		( $hare = isset( $override[ $evanescent_hare ] ) ? $override[ $evanescent_hare ] : call_user_func_array( $callback, array_merge( array( $evanescent_hare ), $callback_args ) ) )
	) {
		if ( $_return_loop ) {
			$return[ $tortoise ]        = true;
			$return[ $evanescent_hare ] = true;
			$return[ $hare ]            = true;
		}

		// Rùa bị bắt kịp - phải có vòng lặp.
		if ( $tortoise === $evanescent_hare || $tortoise === $hare ) {
			return $_return_loop ? $return : $tortoise;
		}

		// Tăng rùa một bước.
		$tortoise = isset( $override[ $tortoise ] ) ? $override[ $tortoise ] : call_user_func_array( $callback, array_merge( array( $tortoise ), $callback_args ) );
	}

	return false;
}

/**
 * Gửi header HTTP để giới hạn việc hiển thị trang trong iframe cùng nguồn gốc.
 *
 * @since 3.1.3
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
 */
function send_frame_options_header() {
	header( 'X-Frame-Options: SAMEORIGIN' );
}

/**
 * Gửi header chính sách referrer để referrer không được gửi ra bên ngoài từ màn hình quản trị.
 *
 * @since 4.9.0
 * @since 6.8.0 Hàm này được chuyển từ `wp-admin/includes/misc.php` sang `wp-includes/functions.php`.
 */
function wp_admin_headers() {
	$policy = 'strict-origin-when-cross-origin';

	/**
	 * Lọc giá trị header chính sách referrer của quản trị.
	 *
	 * @since 4.9.0
	 * @since 4.9.5 Giá trị mặc định được thay đổi thành 'strict-origin-when-cross-origin'.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy
	 *
	 * @param string $policy Giá trị header chính sách referrer quản trị. Mặc định 'strict-origin-when-cross-origin'.
	 */
	$policy = apply_filters( 'admin_referrer_policy', $policy );

	header( sprintf( 'Referrer-Policy: %s', $policy ) );
}

/**
 * Lấy danh sách các giao thức được phép trong thuộc tính HTML.
 *
 * @since 3.3.0
 * @since 4.3.0 Thêm 'webcal' vào mảng giao thức.
 * @since 4.7.0 Thêm 'urn' vào mảng giao thức.
 * @since 5.3.0 Thêm 'sms' vào mảng giao thức.
 * @since 5.6.0 Thêm 'irc6' và 'ircs' vào mảng giao thức.
 *
 * @see wp_kses()
 * @see esc_url()
 *
 * @return string[] Mảng các giao thức được phép. Mặc định là mảng chứa 'http', 'https',
 *                  'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed',
 *                  'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal' và 'urn'.
 *                  Bao phủ tất cả giao thức liên kết phổ biến, trừ 'javascript' không nên
 *                  cho phép đối với người dùng không tin cậy.
 */
function wp_allowed_protocols() {
	static $protocols = array();

	if ( empty( $protocols ) ) {
		$protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
	}

	if ( ! did_action( 'wp_loaded' ) ) {
		/**
		 * Lọc danh sách các giao thức được phép trong thuộc tính HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $protocols Mảng các giao thức được phép, ví dụ: 'http', 'ftp', 'tel', v.v.
		 */
		$protocols = array_unique( (array) apply_filters( 'kses_allowed_protocols', $protocols ) );
	}

	return $protocols;
}

/**
 * Trả về chuỗi phân cách bằng dấu phẩy hoặc mảng các hàm đã được gọi để đến
 * điểm hiện tại trong mã.
 *
 * @since 3.4.0
 *
 * @see https://core.trac.wordpress.org/ticket/19589
 *
 * @param string $ignore_class Tùy chọn. Lớp để bỏ qua tất cả lời gọi hàm bên trong - hữu ích
 *                             khi bạn chỉ muốn cung cấp thông tin về hàm được gọi. Mặc định null.
 * @param int    $skip_frames  Tùy chọn. Số khung ngăn xếp cần bỏ qua - hữu ích để quay lại
 *                             nguồn gốc vấn đề. Mặc định 0.
 * @param bool   $pretty       Tùy chọn. Có muốn chuỗi phân cách bằng dấu phẩy thay vì
 *                             mảng thô hay không. Mặc định true.
 * @return string|array Chuỗi chứa trace đảo ngược phân cách bằng dấu phẩy hoặc mảng
 *                      các lời gọi riêng lẻ.
 */
function wp_debug_backtrace_summary( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
	static $truncate_paths;

	$trace       = debug_backtrace( false );
	$caller      = array();
	$check_class = ! is_null( $ignore_class );
	++$skip_frames; // Bỏ qua hàm này.

	if ( ! isset( $truncate_paths ) ) {
		$truncate_paths = array(
			wp_normalize_path( WP_CONTENT_DIR ),
			wp_normalize_path( ABSPATH ),
		);
	}

	foreach ( $trace as $call ) {
		if ( $skip_frames > 0 ) {
			--$skip_frames;
		} elseif ( isset( $call['class'] ) ) {
			if ( $check_class && $ignore_class === $call['class'] ) {
				continue; // Lọc bỏ các lời gọi.
			}

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
		} else {
			if ( in_array( $call['function'], array( 'do_action', 'apply_filters', 'do_action_ref_array', 'apply_filters_ref_array' ), true ) ) {
				$caller[] = "{$call['function']}('{$call['args'][0]}')";
			} elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ), true ) ) {
				$filename = isset( $call['args'][0] ) ? $call['args'][0] : '';
				$caller[] = $call['function'] . "('" . str_replace( $truncate_paths, '', wp_normalize_path( $filename ) ) . "')";
			} else {
				$caller[] = $call['function'];
			}
		}
	}
	if ( $pretty ) {
		return implode( ', ', array_reverse( $caller ) );
	} else {
		return $caller;
	}
}

/**
 * Lấy các ID chưa có trong bộ nhớ đệm.
 *
 * @since 3.4.0
 * @since 6.1.0 Hàm này không còn được đánh dấu là "private".
 *
 * @param int[]  $object_ids  Mảng các ID.
 * @param string $cache_group Nhóm bộ nhớ đệm cần kiểm tra.
 * @return int[] Mảng các ID không có trong bộ nhớ đệm.
 */
function _get_non_cached_ids( $object_ids, $cache_group ) {
	$object_ids = array_filter( $object_ids, '_validate_cache_id' );
	$object_ids = array_unique( array_map( 'intval', $object_ids ), SORT_NUMERIC );

	if ( empty( $object_ids ) ) {
		return array();
	}

	$non_cached_ids = array();
	$cache_values   = wp_cache_get_multiple( $object_ids, $cache_group );

	foreach ( $cache_values as $id => $value ) {
		if ( false === $value ) {
			$non_cached_ids[] = (int) $id;
		}
	}

	return $non_cached_ids;
}

/**
 * Kiểm tra xem ID bộ nhớ đệm đã cho có phải là số nguyên hoặc chuỗi giống số nguyên hay không.
 *
 * Cả `16` và `"16"` đều được coi là hợp lệ, các kiểu số và chuỗi số khác
 * (`16.3` và `"16.3"`) được coi là không hợp lệ.
 *
 * @since 6.3.0
 *
 * @param mixed $object_id ID bộ nhớ đệm cần xác thực.
 * @return bool Liệu $object_id đã cho có phải là ID bộ nhớ đệm hợp lệ hay không.
 */
function _validate_cache_id( $object_id ) {
	/*
	 * filter_var() có thể được sử dụng ở đây, nhưng phần mở rộng PHP `filter`
	 * được coi là tùy chọn và có thể không khả dụng.
	 */
	if ( is_int( $object_id )
		|| ( is_string( $object_id ) && (string) (int) $object_id === $object_id ) ) {
		return true;
	}

	/* translators: %s: The type of the given object ID. */
	$message = sprintf( __( 'Object ID must be an integer, %s given.' ), gettype( $object_id ) );
	_doing_it_wrong( '_get_non_cached_ids', $message, '6.3.0' );

	return false;
}

/**
 * Kiểm tra xem thiết bị hiện tại có khả năng tải file lên hay không.
 *
 * @since 3.4.0
 * @access private
 *
 * @return bool Liệu thiết bị có thể tải file lên hay không.
 */
function _device_can_upload() {
	if ( ! wp_is_mobile() ) {
		return true;
	}

	$ua = $_SERVER['HTTP_USER_AGENT'];

	if ( str_contains( $ua, 'iPhone' )
		|| str_contains( $ua, 'iPad' )
		|| str_contains( $ua, 'iPod' ) ) {
			return preg_match( '#OS ([\d_]+) like Mac OS X#', $ua, $version ) && version_compare( $version[1], '6', '>=' );
	}

	return true;
}

/**
 * Kiểm tra xem đường dẫn đã cho có phải là URL stream hay không.
 *
 * @since 3.5.0
 *
 * @param string $path Đường dẫn tài nguyên hoặc URL.
 * @return bool True nếu đường dẫn là URL stream.
 */
function wp_is_stream( $path ) {
	$scheme_separator = strpos( $path, '://' );

	if ( false === $scheme_separator ) {
		// $path không phải là stream.
		return false;
	}

	$stream = substr( $path, 0, $scheme_separator );

	return in_array( $stream, stream_get_wrappers(), true );
}

/**
 * Kiểm tra xem ngày cung cấp có hợp lệ cho lịch Gregory hay không.
 *
 * @since 3.5.0
 *
 * @link https://www.php.net/manual/en/function.checkdate.php
 *
 * @param int    $month       Số tháng.
 * @param int    $day         Số ngày.
 * @param int    $year        Số năm.
 * @param string $source_date Ngày cần lọc.
 * @return bool True nếu ngày hợp lệ, false nếu không hợp lệ.
 */
function wp_checkdate( $month, $day, $year, $source_date ) {
	/**
	 * Lọc xem ngày đã cho có hợp lệ cho lịch Gregory hay không.
	 *
	 * @since 3.5.0
	 *
	 * @param bool   $checkdate   Liệu ngày đã cho có hợp lệ hay không.
	 * @param string $source_date Ngày cần kiểm tra.
	 */
	return apply_filters( 'wp_checkdate', checkdate( $month, $day, $year ), $source_date );
}

/**
 * Tải kiểm tra xác thực để theo dõi xem người dùng còn đăng nhập hay không.
 *
 * Có thể vô hiệu hóa bằng remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
 *
 * Tính năng này bị tắt cho một số màn hình nhất định nơi màn hình đăng nhập có thể gây
 * gián đoạn bất tiện. Bộ lọc {@see 'wp_auth_check_load'} có thể được sử dụng
 * để kiểm soát chi tiết.
 *
 * @since 3.6.0
 */
function wp_auth_check_load() {
	if ( ! is_admin() && ! is_user_logged_in() ) {
		return;
	}

	if ( defined( 'IFRAME_REQUEST' ) ) {
		return;
	}

	$screen = get_current_screen();
	$hidden = array( 'update', 'update-network', 'update-core', 'update-core-network', 'upgrade', 'upgrade-network', 'network' );
	$show   = ! in_array( $screen->id, $hidden, true );

	/**
	 * Lọc xem có tải kiểm tra xác thực hay không.
	 *
	 * Trả về giá trị falsy từ bộ lọc sẽ bỏ qua
	 * việc tải kiểm tra xác thực.
	 *
	 * @since 3.6.0
	 *
	 * @param bool      $show   Có tải kiểm tra xác thực hay không.
	 * @param WP_Screen $screen Đối tượng màn hình hiện tại.
	 */
	if ( apply_filters( 'wp_auth_check_load', $show, $screen ) ) {
		wp_enqueue_style( 'wp-auth-check' );
		wp_enqueue_script( 'wp-auth-check' );

		add_action( 'admin_print_footer_scripts', 'wp_auth_check_html', 5 );
		add_action( 'wp_print_footer_scripts', 'wp_auth_check_html', 5 );
	}
}

/**
 * Xuất HTML hiển thị hộp thoại wp-login khi người dùng không còn đăng nhập.
 *
 * @since 3.6.0
 */
function wp_auth_check_html() {
	$login_url      = wp_login_url();
	$current_domain = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
	$same_domain    = str_starts_with( $login_url, $current_domain );

	/**
	 * Lọc xem kiểm tra xác thực có bắt nguồn từ cùng tên miền hay không.
	 *
	 * @since 3.6.0
	 *
	 * @param bool $same_domain Liệu kiểm tra xác thực có bắt nguồn từ cùng tên miền hay không.
	 */
	$same_domain = apply_filters( 'wp_auth_check_same_domain', $same_domain );
	$wrap_class  = $same_domain ? 'hidden' : 'hidden fallback';

	?>
	<div id="wp-auth-check-wrap" class="<?php echo $wrap_class; ?>">
	<div id="wp-auth-check-bg"></div>
	<div id="wp-auth-check">
	<button type="button" class="wp-auth-check-close button-link"><span class="screen-reader-text">
		<?php
		/* translators: Hidden accessibility text. */
		_e( 'Close dialog' );
		?>
	</span></button>
	<?php

	if ( $same_domain ) {
		$login_src = add_query_arg(
			array(
				'interim-login' => '1',
				'wp_lang'       => get_user_locale(),
			),
			$login_url
		);
		?>
		<div id="wp-auth-check-form" class="loading" data-src="<?php echo esc_url( $login_src ); ?>"></div>
		<?php
	}

	?>
	<div class="wp-auth-fallback">
		<p><b class="wp-auth-fallback-expired" tabindex="0"><?php _e( 'Session expired' ); ?></b></p>
		<p><a href="<?php echo esc_url( $login_url ); ?>" target="_blank"><?php _e( 'Please log in again.' ); ?></a>
		<?php _e( 'The login page will open in a new tab. After logging in you can close it and return to this page.' ); ?></p>
	</div>
	</div>
	</div>
	<?php
}

/**
 * Kiểm tra xem người dùng còn đăng nhập hay không, cho heartbeat.
 *
 * Gửi kết quả hiển thị hộp đăng nhập nếu người dùng không còn đăng nhập,
 * hoặc nếu cookie của họ đang trong thời gian gia hạn.
 *
 * @since 3.6.0
 *
 * @global int $login_grace_period
 *
 * @param array $response  Phản hồi Heartbeat.
 * @return array Phản hồi Heartbeat với giá trị 'wp-auth-check' được đặt.
 */
function wp_auth_check( $response ) {
	$response['wp-auth-check'] = is_user_logged_in() && empty( $GLOBALS['login_grace_period'] );
	return $response;
}

/**
 * Trả về phần thân RegEx để khớp linh hoạt thẻ HTML mở.
 *
 * Khớp thẻ HTML mở mà:
 * 1. Tự đóng hoặc
 * 2. Không có nội dung nhưng có thẻ đóng cùng tên hoặc
 * 3. Chứa nội dung và thẻ đóng cùng tên
 *
 * Lưu ý: RegEx này không cân bằng thẻ bên trong và không cố gắng
 * tạo ra HTML hợp lệ.
 *
 * @since 3.6.0
 *
 * @param string $tag Tên thẻ HTML. Ví dụ: 'video'.
 * @return string RegEx thẻ.
 */
function get_tag_regex( $tag ) {
	if ( empty( $tag ) ) {
		return '';
	}
	return sprintf( '<%1$s[^<]*(?:>[\s\S]*<\/%1$s>|\s*\/>)', tag_escape( $tag ) );
}

/**
 * Xác định xem slug bộ ký tự đã cho có đại diện cho mã hóa văn bản UTF-8 hay không.
 * Nếu không được cung cấp, kiểm tra bộ ký tự của blog hiện tại.
 *
 * Bộ ký tự được coi là đại diện cho UTF-8 nếu nó khớp không phân biệt hoa thường
 * với "UTF-8" có hoặc không có dấu gạch nối.
 *
 * Ví dụ:
 *
 *     true  === is_utf8_charset( 'UTF-8' );
 *     true  === is_utf8_charset( 'utf8' );
 *     false === is_utf8_charset( 'latin1' );
 *     false === is_utf8_charset( 'UTF 8' );
 *
 *     // Chỉ chuỗi mới khớp.
 *     false === is_utf8_charset( [ 'charset' => 'utf-8' ] );
 *
 *     // Không có bộ ký tự đã cho, phụ thuộc vào tùy chọn trang "blog_charset".
 *     $is_utf8 = is_utf8_charset();
 *
 * @since 6.6.0
 * @since 6.6.1 Trở thành wrapper cho _is_utf8_charset
 *
 * @see _is_utf8_charset
 *
 * @param string|null $blog_charset Tùy chọn. Slug đại diện cho mã hóa ký tự văn bản, hoặc "charset".
 *                                  Ví dụ: "UTF-8", "Windows-1252", "ISO-8859-1", "SJIS".
 *                                  Giá trị mặc định được suy ra từ tùy chọn "blog_charset".
 * @return bool Liệu slug có đại diện cho mã hóa UTF-8 hay không.
 */
function is_utf8_charset( $blog_charset = null ) {
	return _is_utf8_charset( $blog_charset ?? get_option( 'blog_charset' ) );
}

/**
 * Lấy dạng chuẩn của bộ ký tự được cung cấp phù hợp để truyền cho các hàm PHP
 * như htmlspecialchars() và thuộc tính charset HTML.
 *
 * @since 3.6.0
 * @access private
 *
 * @see https://core.trac.wordpress.org/ticket/23688
 *
 * @param string $charset Tên bộ ký tự, ví dụ: "UTF-8", "Windows-1252", "SJIS".
 * @return string Dạng chuẩn của bộ ký tự.
 */
function _canonical_charset( $charset ) {
	if ( is_utf8_charset( $charset ) ) {
		return 'UTF-8';
	}

	/*
	 * Chuẩn hóa họ ngôn ngữ ISO-8859-1.
	 *
	 * Điều này không bắt buộc cho htmlspecialchars(), vì nó nhận dạng đúng tất cả
	 * các bộ ký tự đầu vào mà ở đây được chuyển đổi thành "ISO-8859-1".
	 *
	 * @todo Liệu toàn bộ kiểm tra này có nên được xóa vì không cần thiết cho mục đích đã nêu?
	 * @todo Liệu WordPress có nên chuyển đổi các bộ ký tự tương đương tiềm năng khác, như "latin1"?
	 */
	if (
		( 0 === strcasecmp( 'iso-8859-1', $charset ) ) ||
		( 0 === strcasecmp( 'iso8859-1', $charset ) )
	) {
		return 'ISO-8859-1';
	}

	return $charset;
}

/**
 * Đặt mã hóa nội bộ mbstring thành mã hóa an toàn nhị phân khi func_overload
 * được bật.
 *
 * Khi mbstring.func_overload được sử dụng cho mã hóa nhiều byte, kết quả từ
 * strlen() và các hàm tương tự tôn trọng các ký tự utf8, khiến dữ liệu nhị phân
 * trả về độ dài không chính xác.
 *
 * Hàm này ghi đè mã hóa mbstring thành mã hóa an toàn nhị phân, và
 * đặt lại thành mã hóa mong đợi của người dùng sau đó thông qua
 * hàm `reset_mbstring_encoding`.
 *
 * Gọi đệ quy hàm này là an toàn, tuy nhiên mỗi lời gọi
 * `mbstring_binary_safe_encoding()` phải được theo sau bởi số lượng tương ứng
 * các lời gọi `reset_mbstring_encoding()`.
 *
 * @since 3.7.0
 *
 * @see reset_mbstring_encoding()
 *
 * @param bool $reset Tùy chọn. Có đặt lại mã hóa về mã hóa đã đặt trước đó hay không.
 *                    Mặc định false.
 */
function mbstring_binary_safe_encoding( $reset = false ) {
	static $encodings  = array();
	static $overloaded = null;

	if ( is_null( $overloaded ) ) {
		if ( function_exists( 'mb_internal_encoding' )
			&& ( (int) ini_get( 'mbstring.func_overload' ) & 2 ) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
		) {
			$overloaded = true;
		} else {
			$overloaded = false;
		}
	}

	if ( false === $overloaded ) {
		return;
	}

	if ( ! $reset ) {
		$encoding = mb_internal_encoding();
		array_push( $encodings, $encoding );
		mb_internal_encoding( 'ISO-8859-1' );
	}

	if ( $reset && $encodings ) {
		$encoding = array_pop( $encodings );
		mb_internal_encoding( $encoding );
	}
}

/**
 * Đặt lại mã hóa nội bộ mbstring về mã hóa đã đặt trước đó của người dùng.
 *
 * @see mbstring_binary_safe_encoding()
 *
 * @since 3.7.0
 */
function reset_mbstring_encoding() {
	mbstring_binary_safe_encoding( true );
}

/**
 * Lọc/xác thực biến dưới dạng boolean.
 *
 * Thay thế cho `filter_var( $value, FILTER_VALIDATE_BOOLEAN )`.
 *
 * @since 4.0.0
 *
 * @param mixed $value Giá trị boolean cần xác thực.
 * @return bool Liệu giá trị có được xác thực hay không.
 */
function wp_validate_boolean( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( is_string( $value ) && 'false' === strtolower( $value ) ) {
		return false;
	}

	return (bool) $value;
}

/**
 * Xóa file.
 *
 * @since 4.2.0
 * @since 6.7.0 Giá trị trả về được thêm vào.
 *
 * @param string $file Đường dẫn đến file cần xóa.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_delete_file( $file ) {
	/**
	 * Lọc đường dẫn file cần xóa.
	 *
	 * @since 2.1.0
	 *
	 * @param string $file Đường dẫn đến file cần xóa.
	 */
	$delete = apply_filters( 'wp_delete_file', $file );

	if ( ! empty( $delete ) ) {
		return @unlink( $delete );
	}

	return false;
}

/**
 * Xóa file nếu đường dẫn của nó nằm trong thư mục đã cho.
 *
 * @since 4.9.7
 *
 * @param string $file      Đường dẫn tuyệt đối đến file cần xóa.
 * @param string $directory Đường dẫn tuyệt đối đến thư mục.
 * @return bool True khi thành công, false khi thất bại.
 */
function wp_delete_file_from_directory( $file, $directory ) {
	if ( wp_is_stream( $file ) ) {
		$real_file      = $file;
		$real_directory = $directory;
	} else {
		$real_file      = realpath( wp_normalize_path( $file ) );
		$real_directory = realpath( wp_normalize_path( $directory ) );
	}

	if ( false !== $real_file ) {
		$real_file = wp_normalize_path( $real_file );
	}

	if ( false !== $real_directory ) {
		$real_directory = wp_normalize_path( $real_directory );
	}

	if ( false === $real_file || false === $real_directory || ! str_starts_with( $real_file, trailingslashit( $real_directory ) ) ) {
		return false;
	}

	return wp_delete_file( $file );
}

/**
 * Xuất đoạn JS nhỏ trên các tab/cửa sổ xem trước để xóa `window.name` khi người dùng điều hướng đến trang khác.
 *
 * Điều này ngăn việc sử dụng lại cùng tab cho xem trước khi người dùng đã điều hướng đi.
 *
 * @since 4.3.0
 *
 * @global WP_Post $post Đối tượng bài viết toàn cục.
 */
function wp_post_preview_js() {
	global $post;

	if ( ! is_preview() || empty( $post ) ) {
		return;
	}

	// Phải khớp với tên cửa sổ được sử dụng trong post_submit_meta_box().
	$name = 'wp-preview-' . (int) $post->ID;

	ob_start();
	?>
	<script>
	( function() {
		var query = document.location.search;

		if ( query && query.indexOf( 'preview=true' ) !== -1 ) {
			window.name = '<?php echo $name; ?>';
		}

		if ( window.addEventListener ) {
			window.addEventListener( 'pagehide', function() { window.name = ''; } );
		}
	}());
	</script>
	<?php
	wp_print_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
}

/**
 * Phân tích và định dạng datetime MySQL (Y-m-d H:i:s) cho ISO8601 (Y-m-d\TH:i:s).
 *
 * Loại bỏ rõ ràng múi giờ, vì datetime không được lưu với bất kỳ thông tin
 * múi giờ nào. Bao gồm bất kỳ thông tin nào về độ lệch có thể gây hiểu lầm.
 *
 * Mặc dù tên hàm lịch sử, đầu ra không tuân theo định dạng RFC3339,
 * vốn phải chứa múi giờ.
 *
 * @since 4.4.0
 *
 * @param string $date_string Chuỗi ngày cần phân tích và định dạng.
 * @return string Ngày được định dạng cho ISO8601 không có múi giờ.
 */
function mysql_to_rfc3339( $date_string ) {
	return mysql2date( 'Y-m-d\TH:i:s', $date_string, false );
}

/**
 * Cố gắng nâng giới hạn bộ nhớ PHP cho các tiến trình sử dụng nhiều bộ nhớ.
 *
 * Chỉ cho phép nâng giới hạn hiện tại và ngăn việc hạ thấp nó.
 *
 * @since 4.6.0
 *
 * @param string $context Tùy chọn. Ngữ cảnh gọi hàm. Chấp nhận 'admin',
 *                        'image', 'cron', hoặc ngữ cảnh tùy ý khác. Nếu ngữ cảnh tùy ý được truyền,
 *                        bộ lọc tùy ý tương ứng {@see '$context_memory_limit'} sẽ được
 *                        gọi. Mặc định 'admin'.
 * @return int|string|false Giới hạn đã được đặt hoặc false khi thất bại.
 */
function wp_raise_memory_limit( $context = 'admin' ) {
	// Thoát sớm nếu giới hạn không thể thay đổi.
	if ( false === wp_is_ini_value_changeable( 'memory_limit' ) ) {
		return false;
	}

	$current_limit     = ini_get( 'memory_limit' );
	$current_limit_int = wp_convert_hr_to_bytes( $current_limit );

	if ( -1 === $current_limit_int ) {
		return false;
	}

	$wp_max_limit     = WP_MAX_MEMORY_LIMIT;
	$wp_max_limit_int = wp_convert_hr_to_bytes( $wp_max_limit );
	$filtered_limit   = $wp_max_limit;

	switch ( $context ) {
		case 'admin':
			/**
			 * Lọc giới hạn bộ nhớ tối đa khả dụng cho màn hình quản trị.
			 *
			 * Chỉ áp dụng cho quản trị viên, người có thể cần nhiều bộ nhớ hơn cho các tác vụ
			 * như cập nhật. Giới hạn bộ nhớ khi xử lý ảnh (được tải lên hoặc chỉnh sửa bởi
			 * người dùng bất kỳ vai trò nào) được xử lý riêng biệt.
			 *
			 * Hằng số `WP_MAX_MEMORY_LIMIT` định nghĩa cụ thể giới hạn bộ nhớ tối đa
			 * khả dụng khi ở trang quản trị. Mặc định là 256M
			 * (256 megabyte bộ nhớ) hoặc giá trị `memory_limit` php.ini ban đầu nếu
			 * cao hơn.
			 *
			 * @since 3.0.0
			 * @since 4.6.0 The default now takes the original `memory_limit` into account.
			 *
			 * @param int|string $filtered_limit The maximum WordPress memory limit. Accepts an integer
			 *                                   (bytes), or a shorthand string notation, such as '256M'.
			 */
			$filtered_limit = apply_filters( 'admin_memory_limit', $filtered_limit );
			break;

		case 'image':
			/**
			 * Filters the memory limit allocated for image manipulation.
			 *
			 * @since 3.5.0
			 * @since 4.6.0 The default now takes the original `memory_limit` into account.
			 *
			 * @param int|string $filtered_limit Maximum memory limit to allocate for image processing.
			 *                                   Default `WP_MAX_MEMORY_LIMIT` or the original
			 *                                   php.ini `memory_limit`, whichever is higher.
			 *                                   Accepts an integer (bytes), or a shorthand string
			 *                                   notation, such as '256M'.
			 */
			$filtered_limit = apply_filters( 'image_memory_limit', $filtered_limit );
			break;

		case 'cron':
			/**
			 * Filters the memory limit allocated for WP-Cron event processing.
			 *
			 * @since 6.3.0
			 *
			 * @param int|string $filtered_limit Maximum memory limit to allocate for WP-Cron.
			 *                                   Default `WP_MAX_MEMORY_LIMIT` or the original
			 *                                   php.ini `memory_limit`, whichever is higher.
			 *                                   Accepts an integer (bytes), or a shorthand string
			 *                                   notation, such as '256M'.
			 */
			$filtered_limit = apply_filters( 'cron_memory_limit', $filtered_limit );
			break;

		default:
			/**
			 * Filters the memory limit allocated for an arbitrary context.
			 *
			 * The dynamic portion of the hook name, `$context`, refers to an arbitrary
			 * context passed on calling the function. This allows for plugins to define
			 * their own contexts for raising the memory limit.
			 *
			 * @since 4.6.0
			 *
			 * @param int|string $filtered_limit Maximum memory limit to allocate for this context.
			 *                                   Default WP_MAX_MEMORY_LIMIT` or the original php.ini `memory_limit`,
			 *                                   whichever is higher. Accepts an integer (bytes), or a
			 *                                   shorthand string notation, such as '256M'.
			 */
			$filtered_limit = apply_filters( "{$context}_memory_limit", $filtered_limit );
			break;
	}

	$filtered_limit_int = wp_convert_hr_to_bytes( $filtered_limit );

	if ( -1 === $filtered_limit_int || ( $filtered_limit_int > $wp_max_limit_int && $filtered_limit_int > $current_limit_int ) ) {
		if ( false !== ini_set( 'memory_limit', $filtered_limit ) ) {
			return $filtered_limit;
		} else {
			return false;
		}
	} elseif ( -1 === $wp_max_limit_int || $wp_max_limit_int > $current_limit_int ) {
		if ( false !== ini_set( 'memory_limit', $wp_max_limit ) ) {
			return $wp_max_limit;
		} else {
			return false;
		}
	}

	return false;
}

/**
 * Generates a random UUID (version 4).
 *
 * @since 4.7.0
 *
 * @return string UUID.
 */
function wp_generate_uuid4() {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0fff ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff )
	);
}

/**
 * Validates that a UUID is valid.
 *
 * @since 4.9.0
 *
 * @param mixed $uuid    UUID to check.
 * @param int   $version Specify which version of UUID to check against. Default is none,
 *                       to accept any UUID version. Otherwise, only version allowed is `4`.
 * @return bool The string is a valid UUID or false on failure.
 */
function wp_is_uuid( $uuid, $version = null ) {

	if ( ! is_string( $uuid ) ) {
		return false;
	}

	if ( is_numeric( $version ) ) {
		if ( 4 !== (int) $version ) {
			_doing_it_wrong( __FUNCTION__, __( 'Only UUID V4 is supported at this time.' ), '4.9.0' );
			return false;
		}
		$regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
	} else {
		$regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
	}

	return (bool) preg_match( $regex, $uuid );
}

/**
 * Gets unique ID.
 *
 * This is a PHP implementation of Underscore's uniqueId method. A static variable
 * contains an integer that is incremented with each call. This number is returned
 * with the optional prefix. As such the returned value is not universally unique,
 * but it is unique across the life of the PHP process.
 *
 * @since 5.0.3
 *
 * @param string $prefix Prefix for the returned ID.
 * @return string Unique ID.
 */
function wp_unique_id( $prefix = '' ) {
	static $id_counter = 0;
	return $prefix . (string) ++$id_counter;
}

/**
 * Generates an incremental ID that is independent per each different prefix.
 *
 * It is similar to `wp_unique_id`, but each prefix has its own internal ID
 * counter to make each prefix independent from each other. The ID starts at 1
 * and increments on each call. The returned value is not universally unique,
 * but it is unique across the life of the PHP process and it's stable per
 * prefix.
 *
 * @since 6.4.0
 *
 * @param string $prefix Optional. Prefix for the returned ID. Default empty string.
 * @return string Incremental ID per prefix.
 */
function wp_unique_prefixed_id( $prefix = '' ) {
	static $id_counters = array();

	if ( ! is_string( $prefix ) ) {
		wp_trigger_error(
			__FUNCTION__,
			sprintf( 'The prefix must be a string. "%s" data type given.', gettype( $prefix ) )
		);
		$prefix = '';
	}

	if ( ! isset( $id_counters[ $prefix ] ) ) {
		$id_counters[ $prefix ] = 0;
	}

	$id = ++$id_counters[ $prefix ];

	return $prefix . (string) $id;
}

/**
 * Gets last changed date for the specified cache group.
 *
 * @since 4.7.0
 *
 * @param string $group Where the cache contents are grouped.
 * @return string UNIX timestamp with microseconds representing when the group was last changed.
 */
function wp_cache_get_last_changed( $group ) {
	$last_changed = wp_cache_get( 'last_changed', $group );

	if ( $last_changed ) {
		return $last_changed;
	}

	return wp_cache_set_last_changed( $group );
}

/**
 * Sets last changed date for the specified cache group to now.
 *
 * @since 6.3.0
 *
 * @param string $group Where the cache contents are grouped.
 * @return string UNIX timestamp when the group was last changed.
 */
function wp_cache_set_last_changed( $group ) {
	$previous_time = wp_cache_get( 'last_changed', $group );

	$time = microtime();

	wp_cache_set( 'last_changed', $time, $group );

	/**
	 * Fires after a cache group `last_changed` time is updated.
	 * This may occur multiple times per page load and registered
	 * actions must be performant.
	 *
	 * @since 6.3.0
	 *
	 * @param string       $group         The cache group name.
	 * @param string       $time          The new last changed time (msec sec).
	 * @param string|false $previous_time The previous last changed time. False if not previously set.
	 */
	do_action( 'wp_cache_set_last_changed', $group, $time, $previous_time );

	return $time;
}

/**
 * Sends an email to the old site admin email address when the site admin email address changes.
 *
 * @since 4.9.0
 *
 * @param string $old_email   The old site admin email address.
 * @param string $new_email   The new site admin email address.
 * @param string $option_name The relevant database option name.
 */
function wp_site_admin_email_change_notification( $old_email, $new_email, $option_name ) {
	$send = true;

	// Don't send the notification to the default 'admin_email' value.
	if ( 'you@example.com' === $old_email ) {
		$send = false;
	}

	/**
	 * Filters whether to send the site admin email change notification email.
	 *
	 * @since 4.9.0
	 *
	 * @param bool   $send      Whether to send the email notification.
	 * @param string $old_email The old site admin email address.
	 * @param string $new_email The new site admin email address.
	 */
	$send = apply_filters( 'send_site_admin_email_change_email', $send, $old_email, $new_email );

	if ( ! $send ) {
		return;
	}

	/* translators: Do not translate OLD_EMAIL, NEW_EMAIL, SITENAME, SITEURL: those are placeholders. */
	$email_change_text = __(
		'Hi,

This notice confirms that the admin email address was changed on ###SITENAME###.

The new admin email address is ###NEW_EMAIL###.

This email has been sent to ###OLD_EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
	);

	$email_change_email = array(
		'to'      => $old_email,
		/* translators: Site admin email change notification email subject. %s: Site title. */
		'subject' => __( '[%s] Admin Email Changed' ),
		'message' => $email_change_text,
		'headers' => '',
	);

	// Get site name.
	$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	/**
	 * Filters the contents of the email notification sent when the site admin email address is changed.
	 *
	 * @since 4.9.0
	 *
	 * @param array $email_change_email {
	 *     Used to build wp_mail().
	 *
	 *     @type string $to      The intended recipient.
	 *     @type string $subject The subject of the email.
	 *     @type string $message The content of the email.
	 *         The following strings have a special meaning and will get replaced dynamically:
	 *         - ###OLD_EMAIL### The old site admin email address.
	 *         - ###NEW_EMAIL### The new site admin email address.
	 *         - ###SITENAME###  The name of the site.
	 *         - ###SITEURL###   The URL to the site.
	 *     @type string $headers Headers.
	 * }
	 * @param string $old_email The old site admin email address.
	 * @param string $new_email The new site admin email address.
	 */
	$email_change_email = apply_filters( 'site_admin_email_change_email', $email_change_email, $old_email, $new_email );

	$email_change_email['message'] = str_replace( '###OLD_EMAIL###', $old_email, $email_change_email['message'] );
	$email_change_email['message'] = str_replace( '###NEW_EMAIL###', $new_email, $email_change_email['message'] );
	$email_change_email['message'] = str_replace( '###SITENAME###', $site_name, $email_change_email['message'] );
	$email_change_email['message'] = str_replace( '###SITEURL###', home_url(), $email_change_email['message'] );

	wp_mail(
		$email_change_email['to'],
		sprintf(
			$email_change_email['subject'],
			$site_name
		),
		$email_change_email['message'],
		$email_change_email['headers']
	);
}

/**
 * Returns an anonymized IPv4 or IPv6 address.
 *
 * @since 4.9.6 Abstracted from `WP_Community_Events::get_unsafe_client_ip()`.
 *
 * @param string $ip_addr       The IPv4 or IPv6 address to be anonymized.
 * @param bool   $ipv6_fallback Optional. Whether to return the original IPv6 address if the needed functions
 *                              to anonymize it are not present. Default false, return `::` (unspecified address).
 * @return string  The anonymized IP address.
 */
function wp_privacy_anonymize_ip( $ip_addr, $ipv6_fallback = false ) {
	if ( empty( $ip_addr ) ) {
		return '0.0.0.0';
	}

	// Detect what kind of IP address this is.
	$ip_prefix = '';
	$is_ipv6   = substr_count( $ip_addr, ':' ) > 1;
	$is_ipv4   = ( 3 === substr_count( $ip_addr, '.' ) );

	if ( $is_ipv6 && $is_ipv4 ) {
		// IPv6 compatibility mode, temporarily strip the IPv6 part, and treat it like IPv4.
		$ip_prefix = '::ffff:';
		$ip_addr   = preg_replace( '/^\[?[0-9a-f:]*:/i', '', $ip_addr );
		$ip_addr   = str_replace( ']', '', $ip_addr );
		$is_ipv6   = false;
	}

	if ( $is_ipv6 ) {
		// IPv6 addresses will always be enclosed in [] if there's a port.
		$left_bracket  = strpos( $ip_addr, '[' );
		$right_bracket = strpos( $ip_addr, ']' );
		$percent       = strpos( $ip_addr, '%' );
		$netmask       = 'ffff:ffff:ffff:ffff:0000:0000:0000:0000';

		// Strip the port (and [] from IPv6 addresses), if they exist.
		if ( false !== $left_bracket && false !== $right_bracket ) {
			$ip_addr = substr( $ip_addr, $left_bracket + 1, $right_bracket - $left_bracket - 1 );
		} elseif ( false !== $left_bracket || false !== $right_bracket ) {
			// The IP has one bracket, but not both, so it's malformed.
			return '::';
		}

		// Strip the reachability scope.
		if ( false !== $percent ) {
			$ip_addr = substr( $ip_addr, 0, $percent );
		}

		// No invalid characters should be left.
		if ( preg_match( '/[^0-9a-f:]/i', $ip_addr ) ) {
			return '::';
		}

		// Partially anonymize the IP by reducing it to the corresponding network ID.
		if ( function_exists( 'inet_pton' ) && function_exists( 'inet_ntop' ) ) {
			$ip_addr = inet_ntop( inet_pton( $ip_addr ) & inet_pton( $netmask ) );
			if ( false === $ip_addr ) {
				return '::';
			}
		} elseif ( ! $ipv6_fallback ) {
			return '::';
		}
	} elseif ( $is_ipv4 ) {
		// Strip any port and partially anonymize the IP.
		$last_octet_position = strrpos( $ip_addr, '.' );
		$ip_addr             = substr( $ip_addr, 0, $last_octet_position ) . '.0';
	} else {
		return '0.0.0.0';
	}

	// Restore the IPv6 prefix to compatibility mode addresses.
	return $ip_prefix . $ip_addr;
}

/**
 * Returns uniform "anonymous" data by type.
 *
 * @since 4.9.6
 *
 * @param string $type The type of data to be anonymized.
 * @param string $data Optional. The data to be anonymized. Default empty string.
 * @return string The anonymous data for the requested type.
 */
function wp_privacy_anonymize_data( $type, $data = '' ) {

	switch ( $type ) {
		case 'email':
			$anonymous = 'deleted@site.invalid';
			break;
		case 'url':
			$anonymous = 'https://site.invalid';
			break;
		case 'ip':
			$anonymous = wp_privacy_anonymize_ip( $data );
			break;
		case 'date':
			$anonymous = '0000-00-00 00:00:00';
			break;
		case 'text':
			/* translators: Deleted text. */
			$anonymous = __( '[deleted]' );
			break;
		case 'longtext':
			/* translators: Deleted long text. */
			$anonymous = __( 'This content was deleted by the author.' );
			break;
		default:
			$anonymous = '';
			break;
	}

	/**
	 * Filters the anonymous data for each type.
	 *
	 * @since 4.9.6
	 *
	 * @param string $anonymous Anonymized data.
	 * @param string $type      Type of the data.
	 * @param string $data      Original data.
	 */
	return apply_filters( 'wp_privacy_anonymize_data', $anonymous, $type, $data );
}

/**
 * Returns the directory used to store personal data export files.
 *
 * @since 4.9.6
 *
 * @see wp_privacy_exports_url
 *
 * @return string Exports directory.
 */
function wp_privacy_exports_dir() {
	$upload_dir  = wp_upload_dir();
	$exports_dir = trailingslashit( $upload_dir['basedir'] ) . 'wp-personal-data-exports/';

	/**
	 * Filters the directory used to store personal data export files.
	 *
	 * @since 4.9.6
	 * @since 5.5.0 Exports now use relative paths, so changes to the directory
	 *              via this filter should be reflected on the server.
	 *
	 * @param string $exports_dir Exports directory.
	 */
	return apply_filters( 'wp_privacy_exports_dir', $exports_dir );
}

/**
 * Returns the URL of the directory used to store personal data export files.
 *
 * @since 4.9.6
 *
 * @see wp_privacy_exports_dir
 *
 * @return string Exports directory URL.
 */
function wp_privacy_exports_url() {
	$upload_dir  = wp_upload_dir();
	$exports_url = trailingslashit( $upload_dir['baseurl'] ) . 'wp-personal-data-exports/';

	/**
	 * Filters the URL of the directory used to store personal data export files.
	 *
	 * @since 4.9.6
	 * @since 5.5.0 Exports now use relative paths, so changes to the directory URL
	 *              via this filter should be reflected on the server.
	 *
	 * @param string $exports_url Exports directory URL.
	 */
	return apply_filters( 'wp_privacy_exports_url', $exports_url );
}

/**
 * Schedules a `WP_Cron` job to delete expired export files.
 *
 * @since 4.9.6
 */
function wp_schedule_delete_old_privacy_export_files() {
	if ( wp_installing() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'wp_privacy_delete_old_export_files' ) ) {
		wp_schedule_event( time(), 'hourly', 'wp_privacy_delete_old_export_files' );
	}
}

/**
 * Cleans up export files older than three days old.
 *
 * The export files are stored in `wp-content/uploads`, and are therefore publicly
 * accessible. A CSPRN is appended to the filename to mitigate the risk of an
 * unauthorized person downloading the file, but it is still possible. Deleting
 * the file after the data subject has had a chance to delete it adds an additional
 * layer of protection.
 *
 * @since 4.9.6
 */
function wp_privacy_delete_old_export_files() {
	$exports_dir = wp_privacy_exports_dir();
	if ( ! is_dir( $exports_dir ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	$export_files = list_files( $exports_dir, 100, array( 'index.php' ) );

	/**
	 * Filters the lifetime, in seconds, of a personal data export file.
	 *
	 * By default, the lifetime is 3 days. Once the file reaches that age, it will automatically
	 * be deleted by a cron job.
	 *
	 * @since 4.9.6
	 *
	 * @param int $expiration The expiration age of the export, in seconds.
	 */
	$expiration = apply_filters( 'wp_privacy_export_expiration', 3 * DAY_IN_SECONDS );

	foreach ( (array) $export_files as $export_file ) {
		$file_age_in_seconds = time() - filemtime( $export_file );

		if ( $expiration < $file_age_in_seconds ) {
			unlink( $export_file );
		}
	}
}

/**
 * Gets the URL to learn more about updating the PHP version the site is running on.
 *
 * This URL can be overridden by specifying an environment variable `WP_UPDATE_PHP_URL` or by using the
 * {@see 'wp_update_php_url'} filter. Providing an empty string is not allowed and will result in the
 * default URL being used. Furthermore the page the URL links to should preferably be localized in the
 * site language.
 *
 * @since 5.1.0
 *
 * @return string URL to learn more about updating PHP.
 */
function wp_get_update_php_url() {
	$default_url = wp_get_default_update_php_url();

	$update_url = $default_url;
	if ( false !== getenv( 'WP_UPDATE_PHP_URL' ) ) {
		$update_url = getenv( 'WP_UPDATE_PHP_URL' );
	}

	/**
	 * Filters the URL to learn more about updating the PHP version the site is running on.
	 *
	 * Providing an empty string is not allowed and will result in the default URL being used. Furthermore
	 * the page the URL links to should preferably be localized in the site language.
	 *
	 * @since 5.1.0
	 *
	 * @param string $update_url URL to learn more about updating PHP.
	 */
	$update_url = apply_filters( 'wp_update_php_url', $update_url );

	if ( empty( $update_url ) ) {
		$update_url = $default_url;
	}

	return $update_url;
}

/**
 * Gets the default URL to learn more about updating the PHP version the site is running on.
 *
 * Do not use this function to retrieve this URL. Instead, use {@see wp_get_update_php_url()} when relying on the URL.
 * This function does not allow modifying the returned URL, and is only used to compare the actually used URL with the
 * default one.
 *
 * @since 5.1.0
 * @access private
 *
 * @return string Default URL to learn more about updating PHP.
 */
function wp_get_default_update_php_url() {
	return _x( 'https://wordpress.org/support/update-php/', 'localized PHP upgrade information page' );
}

/**
 * Prints the default annotation for the web host altering the "Update PHP" page URL.
 *
 * This function is to be used after {@see wp_get_update_php_url()} to display a consistent
 * annotation if the web host has altered the default "Update PHP" page URL.
 *
 * @since 5.1.0
 * @since 5.2.0 Added the `$before` and `$after` parameters.
 * @since 6.4.0 Added the `$display` parameter.
 *
 * @param string $before  Markup to output before the annotation. Default `<p class="description">`.
 * @param string $after   Markup to output after the annotation. Default `</p>`.
 * @param bool   $display Whether to echo or return the markup. Default `true` for echo.
 *
 * @return string|void
 */
function wp_update_php_annotation( $before = '<p class="description">', $after = '</p>', $display = true ) {
	$annotation = wp_get_update_php_annotation();

	if ( $annotation ) {
		if ( $display ) {
			echo $before . $annotation . $after;
		} else {
			return $before . $annotation . $after;
		}
	}
}

/**
 * Returns the default annotation for the web hosting altering the "Update PHP" page URL.
 *
 * This function is to be used after {@see wp_get_update_php_url()} to return a consistent
 * annotation if the web host has altered the default "Update PHP" page URL.
 *
 * @since 5.2.0
 *
 * @return string Update PHP page annotation. An empty string if no custom URLs are provided.
 */
function wp_get_update_php_annotation() {
	$update_url  = wp_get_update_php_url();
	$default_url = wp_get_default_update_php_url();

	if ( $update_url === $default_url ) {
		return '';
	}

	$annotation = sprintf(
		/* translators: %s: Default Update PHP page URL. */
		__( 'This resource is provided by your web host, and is specific to your site. For more information, <a href="%s" target="_blank">see the official WordPress documentation</a>.' ),
		esc_url( $default_url )
	);

	return $annotation;
}

/**
 * Gets the URL for directly updating the PHP version the site is running on.
 *
 * A URL will only be returned if the `WP_DIRECT_UPDATE_PHP_URL` environment variable is specified or
 * by using the {@see 'wp_direct_php_update_url'} filter. This allows hosts to send users directly to
 * the page where they can update PHP to a newer version.
 *
 * @since 5.1.1
 *
 * @return string URL for directly updating PHP or empty string.
 */
function wp_get_direct_php_update_url() {
	$direct_update_url = '';

	if ( false !== getenv( 'WP_DIRECT_UPDATE_PHP_URL' ) ) {
		$direct_update_url = getenv( 'WP_DIRECT_UPDATE_PHP_URL' );
	}

	/**
	 * Filters the URL for directly updating the PHP version the site is running on from the host.
	 *
	 * @since 5.1.1
	 *
	 * @param string $direct_update_url URL for directly updating PHP.
	 */
	$direct_update_url = apply_filters( 'wp_direct_php_update_url', $direct_update_url );

	return $direct_update_url;
}

/**
 * Displays a button directly linking to a PHP update process.
 *
 * This provides hosts with a way for users to be sent directly to their PHP update process.
 *
 * The button is only displayed if a URL is returned by `wp_get_direct_php_update_url()`.
 *
 * @since 5.1.1
 */
function wp_direct_php_update_button() {
	$direct_update_url = wp_get_direct_php_update_url();

	if ( empty( $direct_update_url ) ) {
		return;
	}

	echo '<p class="button-container">';
	printf(
		'<a class="button button-primary" href="%1$s" target="_blank">%2$s<span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
		esc_url( $direct_update_url ),
		__( 'Update PHP' ),
		/* translators: Hidden accessibility text. */
		__( '(opens in a new tab)' )
	);
	echo '</p>';
}

/**
 * Gets the URL to learn more about updating the site to use HTTPS.
 *
 * This URL can be overridden by specifying an environment variable `WP_UPDATE_HTTPS_URL` or by using the
 * {@see 'wp_update_https_url'} filter. Providing an empty string is not allowed and will result in the
 * default URL being used. Furthermore the page the URL links to should preferably be localized in the
 * site language.
 *
 * @since 5.7.0
 *
 * @return string URL to learn more about updating to HTTPS.
 */
function wp_get_update_https_url() {
	$default_url = wp_get_default_update_https_url();

	$update_url = $default_url;
	if ( false !== getenv( 'WP_UPDATE_HTTPS_URL' ) ) {
		$update_url = getenv( 'WP_UPDATE_HTTPS_URL' );
	}

	/**
	 * Filters the URL to learn more about updating the HTTPS version the site is running on.
	 *
	 * Providing an empty string is not allowed and will result in the default URL being used. Furthermore
	 * the page the URL links to should preferably be localized in the site language.
	 *
	 * @since 5.7.0
	 *
	 * @param string $update_url URL to learn more about updating HTTPS.
	 */
	$update_url = apply_filters( 'wp_update_https_url', $update_url );
	if ( empty( $update_url ) ) {
		$update_url = $default_url;
	}

	return $update_url;
}

/**
 * Gets the default URL to learn more about updating the site to use HTTPS.
 *
 * Do not use this function to retrieve this URL. Instead, use {@see wp_get_update_https_url()} when relying on the URL.
 * This function does not allow modifying the returned URL, and is only used to compare the actually used URL with the
 * default one.
 *
 * @since 5.7.0
 * @access private
 *
 * @return string Default URL to learn more about updating to HTTPS.
 */
function wp_get_default_update_https_url() {
	/* translators: Documentation explaining HTTPS and why it should be used. */
	return __( 'https://developer.wordpress.org/advanced-administration/security/https/' );
}

/**
 * Gets the URL for directly updating the site to use HTTPS.
 *
 * A URL will only be returned if the `WP_DIRECT_UPDATE_HTTPS_URL` environment variable is specified or
 * by using the {@see 'wp_direct_update_https_url'} filter. This allows hosts to send users directly to
 * the page where they can update their site to use HTTPS.
 *
 * @since 5.7.0
 *
 * @return string URL for directly updating to HTTPS or empty string.
 */
function wp_get_direct_update_https_url() {
	$direct_update_url = '';

	if ( false !== getenv( 'WP_DIRECT_UPDATE_HTTPS_URL' ) ) {
		$direct_update_url = getenv( 'WP_DIRECT_UPDATE_HTTPS_URL' );
	}

	/**
	 * Filters the URL for directly updating the PHP version the site is running on from the host.
	 *
	 * @since 5.7.0
	 *
	 * @param string $direct_update_url URL for directly updating PHP.
	 */
	$direct_update_url = apply_filters( 'wp_direct_update_https_url', $direct_update_url );

	return $direct_update_url;
}

/**
 * Gets the size of a directory.
 *
 * A helper function that is used primarily to check whether
 * a blog has exceeded its allowed upload space.
 *
 * @since MU (3.0.0)
 * @since 5.2.0 $max_execution_time parameter added.
 *
 * @param string $directory Full path of a directory.
 * @param int    $max_execution_time Maximum time to run before giving up. In seconds.
 *                                   The timeout is global and is measured from the moment WordPress started to load.
 * @return int|false|null Size in bytes if a valid directory. False if not. Null if timeout.
 */
function get_dirsize( $directory, $max_execution_time = null ) {

	/*
	 * Exclude individual site directories from the total when checking the main site of a network,
	 * as they are subdirectories and should not be counted.
	 */
	if ( is_multisite() && is_main_site() ) {
		$size = recurse_dirsize( $directory, $directory . '/sites', $max_execution_time );
	} else {
		$size = recurse_dirsize( $directory, null, $max_execution_time );
	}

	return $size;
}

/**
 * Gets the size of a directory recursively.
 *
 * Used by get_dirsize() to get a directory size when it contains other directories.
 *
 * @since MU (3.0.0)
 * @since 4.3.0 The `$exclude` parameter was added.
 * @since 5.2.0 The `$max_execution_time` parameter was added.
 * @since 5.6.0 The `$directory_cache` parameter was added.
 *
 * @param string          $directory          Full path of a directory.
 * @param string|string[] $exclude            Optional. Full path of a subdirectory to exclude from the total,
 *                                            or array of paths. Expected without trailing slash(es).
 *                                            Default null.
 * @param int             $max_execution_time Optional. Maximum time to run before giving up. In seconds.
 *                                            The timeout is global and is measured from the moment
 *                                            WordPress started to load. Defaults to the value of
 *                                            `max_execution_time` PHP setting.
 * @param array           $directory_cache    Optional. Array of cached directory paths.
 *                                            Defaults to the value of `dirsize_cache` transient.
 * @return int|false|null Size in bytes if a valid directory. False if not. Null if timeout.
 */
function recurse_dirsize( $directory, $exclude = null, $max_execution_time = null, &$directory_cache = null ) {
	$directory  = untrailingslashit( $directory );
	$save_cache = false;

	if ( ! isset( $directory_cache ) ) {
		$directory_cache = get_transient( 'dirsize_cache' );
		$save_cache      = true;
	}

	if ( isset( $directory_cache[ $directory ] ) && is_int( $directory_cache[ $directory ] ) ) {
		return $directory_cache[ $directory ];
	}

	if ( ! file_exists( $directory ) || ! is_dir( $directory ) || ! is_readable( $directory ) ) {
		return false;
	}

	if (
		( is_string( $exclude ) && $directory === $exclude ) ||
		( is_array( $exclude ) && in_array( $directory, $exclude, true ) )
	) {
		return false;
	}

	if ( null === $max_execution_time ) {
		// Keep the previous behavior but attempt to prevent fatal errors from timeout if possible.
		if ( function_exists( 'ini_get' ) ) {
			$max_execution_time = ini_get( 'max_execution_time' );
		} else {
			// Disable...
			$max_execution_time = 0;
		}

		// Leave 1 second "buffer" for other operations if $max_execution_time has reasonable value.
		if ( $max_execution_time > 10 ) {
			$max_execution_time -= 1;
		}
	}

	/**
	 * Filters the amount of storage space used by one directory and all its children, in megabytes.
	 *
	 * Return the actual used space to short-circuit the recursive PHP file size calculation
	 * and use something else, like a CDN API or native operating system tools for better performance.
	 *
	 * @since 5.6.0
	 *
	 * @param int|false            $space_used         The amount of used space, in bytes. Default false.
	 * @param string               $directory          Full path of a directory.
	 * @param string|string[]|null $exclude            Full path of a subdirectory to exclude from the total,
	 *                                                 or array of paths.
	 * @param int                  $max_execution_time Maximum time to run before giving up. In seconds.
	 * @param array                $directory_cache    Array of cached directory paths.
	 */
	$size = apply_filters( 'pre_recurse_dirsize', false, $directory, $exclude, $max_execution_time, $directory_cache );

	if ( false === $size ) {
		$size = 0;

		$handle = opendir( $directory );
		if ( $handle ) {
			while ( ( $file = readdir( $handle ) ) !== false ) {
				$path = $directory . '/' . $file;
				if ( '.' !== $file && '..' !== $file ) {
					if ( is_file( $path ) ) {
						$size += filesize( $path );
					} elseif ( is_dir( $path ) ) {
						$handlesize = recurse_dirsize( $path, $exclude, $max_execution_time, $directory_cache );
						if ( $handlesize > 0 ) {
							$size += $handlesize;
						}
					}

					if ( $max_execution_time > 0 &&
						( microtime( true ) - WP_START_TIMESTAMP ) > $max_execution_time
					) {
						// Time exceeded. Give up instead of risking a fatal timeout.
						$size = null;
						break;
					}
				}
			}
			closedir( $handle );
		}
	}

	if ( ! is_array( $directory_cache ) ) {
		$directory_cache = array();
	}

	$directory_cache[ $directory ] = $size;

	// Only write the transient on the top level call and not on recursive calls.
	if ( $save_cache ) {
		$expiration = ( wp_using_ext_object_cache() ) ? 0 : 10 * YEAR_IN_SECONDS;
		set_transient( 'dirsize_cache', $directory_cache, $expiration );
	}

	return $size;
}

/**
 * Cleans directory size cache used by recurse_dirsize().
 *
 * Removes the current directory and all parent directories from the `dirsize_cache` transient.
 *
 * @since 5.6.0
 * @since 5.9.0 Added input validation with a notice for invalid input.
 *
 * @param string $path Full path of a directory or file.
 */
function clean_dirsize_cache( $path ) {
	if ( ! is_string( $path ) || empty( $path ) ) {
		wp_trigger_error(
			'',
			sprintf(
				/* translators: 1: Function name, 2: A variable type, like "boolean" or "integer". */
				__( '%1$s only accepts a non-empty path string, received %2$s.' ),
				'<code>clean_dirsize_cache()</code>',
				'<code>' . gettype( $path ) . '</code>'
			)
		);
		return;
	}

	$directory_cache = get_transient( 'dirsize_cache' );

	if ( empty( $directory_cache ) ) {
		return;
	}

	$expiration = ( wp_using_ext_object_cache() ) ? 0 : 10 * YEAR_IN_SECONDS;
	if (
		! str_contains( $path, '/' ) &&
		! str_contains( $path, '\\' )
	) {
		unset( $directory_cache[ $path ] );
		set_transient( 'dirsize_cache', $directory_cache, $expiration );
		return;
	}

	$last_path = null;
	$path      = untrailingslashit( $path );
	unset( $directory_cache[ $path ] );

	while (
		$last_path !== $path &&
		DIRECTORY_SEPARATOR !== $path &&
		'.' !== $path &&
		'..' !== $path
	) {
		$last_path = $path;
		$path      = dirname( $path );
		unset( $directory_cache[ $path ] );
	}

	set_transient( 'dirsize_cache', $directory_cache, $expiration );
}

/**
 * Returns the current WordPress version.
 *
 * Returns an unmodified value of `$wp_version`. Some plugins modify the global
 * in an attempt to improve security through obscurity. This practice can cause
 * errors in WordPress, so the ability to get an unmodified version is needed.
 *
 * @since 6.7.0
 *
 * @return string The current WordPress version.
 */
function wp_get_wp_version() {
	static $wp_version;

	if ( ! isset( $wp_version ) ) {
		require ABSPATH . WPINC . '/version.php';
	}

	return $wp_version;
}

/**
 * Checks compatibility with the current WordPress version.
 *
 * @since 5.2.0
 *
 * @global string $_wp_tests_wp_version The WordPress version string. Used only in Core tests.
 *
 * @param string $required Minimum required WordPress version.
 * @return bool True if required version is compatible or empty, false if not.
 */
function is_wp_version_compatible( $required ) {
	if (
		defined( 'WP_RUN_CORE_TESTS' )
		&& WP_RUN_CORE_TESTS
		&& isset( $GLOBALS['_wp_tests_wp_version'] )
	) {
		$wp_version = $GLOBALS['_wp_tests_wp_version'];
	} else {
		$wp_version = wp_get_wp_version();
	}

	// Strip off any -alpha, -RC, -beta, -src suffixes.
	list( $version ) = explode( '-', $wp_version );

	if ( is_string( $required ) ) {
		$trimmed = trim( $required );

		if ( substr_count( $trimmed, '.' ) > 1 && str_ends_with( $trimmed, '.0' ) ) {
			$required = substr( $trimmed, 0, -2 );
		}
	}

	return empty( $required ) || version_compare( $version, $required, '>=' );
}

/**
 * Checks compatibility with the current PHP version.
 *
 * @since 5.2.0
 *
 * @param string $required Minimum required PHP version.
 * @return bool True if required version is compatible or empty, false if not.
 */
function is_php_version_compatible( $required ) {
	return empty( $required ) || version_compare( PHP_VERSION, $required, '>=' );
}

/**
 * Checks if two numbers are nearly the same.
 *
 * This is similar to using `round()` but the precision is more fine-grained.
 *
 * @since 5.3.0
 *
 * @param int|float $expected  The expected value.
 * @param int|float $actual    The actual number.
 * @param int|float $precision Optional. The allowed variation. Default 1.
 * @return bool Whether the numbers match within the specified precision.
 */
function wp_fuzzy_number_match( $expected, $actual, $precision = 1 ) {
	return abs( (float) $expected - (float) $actual ) <= $precision;
}

/**
 * Creates and returns the markup for an admin notice.
 *
 * @since 6.4.0
 *
 * @param string $message The message.
 * @param array  $args {
 *     Optional. An array of arguments for the admin notice. Default empty array.
 *
 *     @type string   $type               Optional. The type of admin notice.
 *                                        For example, 'error', 'success', 'warning', 'info'.
 *                                        Default empty string.
 *     @type bool     $dismissible        Optional. Whether the admin notice is dismissible. Default false.
 *     @type string   $id                 Optional. The value of the admin notice's ID attribute. Default empty string.
 *     @type string[] $additional_classes Optional. A string array of class names. Default empty array.
 *     @type string[] $attributes         Optional. Additional attributes for the notice div. Default empty array.
 *     @type bool     $paragraph_wrap     Optional. Whether to wrap the message in paragraph tags. Default true.
 * }
 * @return string The markup for an admin notice.
 */
function wp_get_admin_notice( $message, $args = array() ) {
	$defaults = array(
		'type'               => '',
		'dismissible'        => false,
		'id'                 => '',
		'additional_classes' => array(),
		'attributes'         => array(),
		'paragraph_wrap'     => true,
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * Filters the arguments for an admin notice.
	 *
	 * @since 6.4.0
	 *
	 * @param array  $args    The arguments for the admin notice.
	 * @param string $message The message for the admin notice.
	 */
	$args       = apply_filters( 'wp_admin_notice_args', $args, $message );
	$id         = '';
	$classes    = 'notice';
	$attributes = '';

	if ( is_string( $args['id'] ) ) {
		$trimmed_id = trim( $args['id'] );

		if ( '' !== $trimmed_id ) {
			$id = 'id="' . $trimmed_id . '" ';
		}
	}

	if ( is_string( $args['type'] ) ) {
		$type = trim( $args['type'] );

		if ( str_contains( $type, ' ' ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: The "type" key. */
					__( 'The %s key must be a string without spaces.' ),
					'<code>type</code>'
				),
				'6.4.0'
			);
		}

		if ( '' !== $type ) {
			$classes .= ' notice-' . $type;
		}
	}

	if ( true === $args['dismissible'] ) {
		$classes .= ' is-dismissible';
	}

	if ( is_array( $args['additional_classes'] ) && ! empty( $args['additional_classes'] ) ) {
		$classes .= ' ' . implode( ' ', $args['additional_classes'] );
	}

	if ( is_array( $args['attributes'] ) && ! empty( $args['attributes'] ) ) {
		$attributes = '';
		foreach ( $args['attributes'] as $attr => $val ) {
			if ( is_bool( $val ) ) {
				$attributes .= $val ? ' ' . $attr : '';
			} elseif ( is_int( $attr ) ) {
				$attributes .= ' ' . esc_attr( trim( $val ) );
			} elseif ( $val ) {
				$attributes .= ' ' . $attr . '="' . esc_attr( trim( $val ) ) . '"';
			}
		}
	}

	if ( false !== $args['paragraph_wrap'] ) {
		$message = "<p>$message</p>";
	}

	$markup = sprintf( '<div %1$sclass="%2$s"%3$s>%4$s</div>', $id, $classes, $attributes, $message );

	/**
	 * Filters the markup for an admin notice.
	 *
	 * @since 6.4.0
	 *
	 * @param string $markup  The HTML markup for the admin notice.
	 * @param string $message The message for the admin notice.
	 * @param array  $args    The arguments for the admin notice.
	 */
	return apply_filters( 'wp_admin_notice_markup', $markup, $message, $args );
}

/**
 * Outputs an admin notice.
 *
 * @since 6.4.0
 *
 * @param string $message The message to output.
 * @param array  $args {
 *     Optional. An array of arguments for the admin notice. Default empty array.
 *
 *     @type string   $type               Optional. The type of admin notice.
 *                                        For example, 'error', 'success', 'warning', 'info'.
 *                                        Default empty string.
 *     @type bool     $dismissible        Optional. Whether the admin notice is dismissible. Default false.
 *     @type string   $id                 Optional. The value of the admin notice's ID attribute. Default empty string.
 *     @type string[] $additional_classes Optional. A string array of class names. Default empty array.
 *     @type string[] $attributes         Optional. Additional attributes for the notice div. Default empty array.
 *     @type bool     $paragraph_wrap     Optional. Whether to wrap the message in paragraph tags. Default true.
 * }
 */
function wp_admin_notice( $message, $args = array() ) {
	/**
	 * Fires before an admin notice is output.
	 *
	 * @since 6.4.0
	 *
	 * @param string $message The message for the admin notice.
	 * @param array  $args    The arguments for the admin notice.
	 */
	do_action( 'wp_admin_notice', $message, $args );

	echo wp_kses_post( wp_get_admin_notice( $message, $args ) );
}

/**
 * Checks if a mime type is for a HEIC/HEIF image.
 *
 * @since 6.7.0
 *
 * @param string $mime_type The mime type to check.
 * @return bool Whether the mime type is for a HEIC/HEIF image.
 */
function wp_is_heic_image_mime_type( $mime_type ) {
	$heic_mime_types = array(
		'image/heic',
		'image/heif',
		'image/heic-sequence',
		'image/heif-sequence',
	);

	return in_array( $mime_type, $heic_mime_types, true );
}

/**
 * Returns a cryptographically secure hash of a message using a fast generic hash function.
 *
 * Use the wp_verify_fast_hash() function to verify the hash.
 *
 * This function does not salt the value prior to being hashed, therefore input to this function must originate from
 * a random generator with sufficiently high entropy, preferably greater than 128 bits. This function is used internally
 * in WordPress to hash security keys and application passwords which are generated with high entropy.
 *
 * Important:
 *
 *  - This function must not be used for hashing user-generated passwords. Use wp_hash_password() for that.
 *  - This function must not be used for hashing other low-entropy input. Use wp_hash() for that.
 *
 * The BLAKE2b algorithm is used by Sodium to hash the message.
 *
 * @since 6.8.0
 *
 * @throws TypeError Thrown by Sodium if the message is not a string.
 *
 * @param string $message The message to hash.
 * @return string The hash of the message.
 */
function wp_fast_hash(
	#[\SensitiveParameter]
	string $message
): string {
	$hashed = sodium_crypto_generichash( $message, 'wp_fast_hash_6.8+', 30 );
	return '$generic$' . sodium_bin2base64( $hashed, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
}

/**
 * Checks whether a plaintext message matches the hashed value. Used to verify values hashed via wp_fast_hash().
 *
 * The function uses Sodium to hash the message and compare it to the hashed value. If the hash is not a generic hash,
 * the hash is treated as a phpass portable hash in order to provide backward compatibility for passwords and security
 * keys which were hashed using phpass prior to WordPress 6.8.0.
 *
 * @since 6.8.0
 *
 * @throws TypeError Thrown by Sodium if the message is not a string.
 *
 * @param string $message The plaintext message.
 * @param string $hash    Hash of the message to check against.
 * @return bool Whether the message matches the hashed message.
 */
function wp_verify_fast_hash(
	#[\SensitiveParameter]
	string $message,
	string $hash
): bool {
	if ( ! str_starts_with( $hash, '$generic$' ) ) {
		// Back-compat for old phpass hashes.
		require_once ABSPATH . WPINC . '/class-phpass.php';
		return ( new PasswordHash( 8, true ) )->CheckPassword( $message, $hash );
	}

	return hash_equals( $hash, wp_fast_hash( $message ) );
}

/**
 * Generates a unique ID based on the structure and values of a given array.
 *
 * This function serializes the array into a JSON string and generates a hash
 * that serves as a unique identifier. Optionally, a prefix can be added to
 * the generated ID for context or categorization.
 *
 * @since 6.8.0
 *
 * @param array  $data   The input array to generate an ID from.
 * @param string $prefix Optional. A prefix to prepend to the generated ID. Default ''.
 *
 * @return string The generated unique ID for the array.
 */
function wp_unique_id_from_values( array $data, string $prefix = '' ): string {
	if ( empty( $data ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: parameter name. */
				__( 'The %s argument must not be empty.' ),
				'$data'
			),
			'6.8.0'
		);
	}
	$serialized = wp_json_encode( $data );
	$hash       = substr( md5( $serialized ), 0, 8 );
	return $prefix . $hash;
}
