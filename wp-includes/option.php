<?php
/**
 * API Tùy chọn (Option)
 *
 * @package WordPress
 * @subpackage Option
 */

/**
 * Lấy giá trị tùy chọn dựa trên tên tùy chọn.
 *
 * Nếu tùy chọn không tồn tại và không cung cấp giá trị mặc định,
 * boolean false sẽ được trả về. Điều này có thể được dùng để kiểm tra xem bạn có cần
 * khởi tạo tùy chọn trong quá trình cài đặt plugin hay không, tuy nhiên
 * có thể làm tốt hơn bằng cách dùng add_option() vì nó sẽ không ghi đè
 * các tùy chọn đã tồn tại.
 *
 * Không khởi tạo tùy chọn và sử dụng boolean `false` làm giá trị trả về
 * là một thực hành xấu vì nó kích hoạt thêm một truy vấn cơ sở dữ liệu.
 *
 * Kiểu dữ liệu của giá trị trả về có thể khác với kiểu được truyền vào
 * khi lưu hoặc cập nhật tùy chọn. Nếu giá trị tùy chọn đã được serialize,
 * thì nó sẽ được unserialize khi trả về. Trong trường hợp này kiểu dữ liệu sẽ
 * giống nhau. Ví dụ, lưu một giá trị không phải scalar như mảng sẽ
 * trả về cùng mảng đó.
 *
 * Trong hầu hết các trường hợp, các giá trị scalar không phải string và null sẽ được chuyển đổi
 * và trả về dưới dạng chuỗi tương đương.
 *
 * Ngoại lệ:
 *
 * 1. Khi tùy chọn chưa được lưu trong cơ sở dữ liệu, giá trị `$default_value`
 *    sẽ được trả về nếu được cung cấp. Nếu không, boolean `false` được trả về.
 * 2. Khi một trong các bộ lọc Options API được sử dụng: {@see 'pre_option_$option'},
 *    {@see 'default_option_$option'}, hoặc {@see 'option_$option'}, giá trị trả về
 *    có thể không khớp với kiểu dữ liệu mong đợi.
 * 3. Khi tùy chọn vừa được lưu vào cơ sở dữ liệu, và get_option()
 *    được sử dụng ngay sau đó, các giá trị scalar không phải string và null sẽ không được
 *    chuyển đổi thành chuỗi tương đương và kiểu gốc sẽ được trả về.
 *
 * Ví dụ:
 *
 * Khi thêm tùy chọn như sau: `add_option( 'my_option_name', 'value' )`
 * và sau đó lấy chúng với `get_option( 'my_option_name' )`, các giá trị trả về
 * sẽ là:
 *
 *   - `false` trả về `string(0) ""`
 *   - `true`  trả về `string(1) "1"`
 *   - `0`     trả về `string(1) "0"`
 *   - `1`     trả về `string(1) "1"`
 *   - `'0'`   trả về `string(1) "0"`
 *   - `'1'`   trả về `string(1) "1"`
 *   - `null`  trả về `string(0) ""`
 *
 * Khi thêm tùy chọn với giá trị không phải scalar như
 * `add_option( 'my_array', array( false, 'str', null ) )`, giá trị trả về
 * sẽ giống hệt giá trị gốc vì nó được serialize trước khi lưu
 * vào cơ sở dữ liệu:
 *
 *     array(3) {
 *         [0] => bool(false)
 *         [1] => string(3) "str"
 *         [2] => NULL
 *     }
 *
 * @since 1.5.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $option        Tên tùy chọn cần lấy. Không cần SQL-escape.
 * @param mixed  $default_value Tùy chọn. Giá trị mặc định trả về nếu tùy chọn không tồn tại.
 * @return mixed Giá trị của tùy chọn. Có thể trả về giá trị thuộc bất kỳ kiểu nào, bao gồm
 *               scalar (string, boolean, float, integer), null, array, object.
 *               Các giá trị scalar và null sẽ được trả về dưới dạng chuỗi miễn là chúng
 *               có nguồn gốc từ giá trị tùy chọn lưu trong cơ sở dữ liệu. Nếu không có tùy chọn
 *               trong cơ sở dữ liệu, boolean `false` được trả về.
 */
function get_option( $option, $default_value = false ) {
	global $wpdb;

	if ( is_scalar( $option ) ) {
		$option = trim( $option );
	}

	if ( empty( $option ) ) {
		return false;
	}

	/*
	 * Cho đến khi có hàm _deprecated_option() phù hợp,
	 * chuyển hướng các yêu cầu đến khóa đã lỗi thời sang khóa mới, đúng.
	 */
	$deprecated_keys = array(
		'blacklist_keys'    => 'disallowed_keys',
		'comment_whitelist' => 'comment_previously_approved',
	);

	if ( isset( $deprecated_keys[ $option ] ) && ! wp_installing() ) {
		_deprecated_argument(
			__FUNCTION__,
			'5.5.0',
			sprintf(
				/* translators: 1: Khóa tùy chọn đã lỗi thời, 2: Khóa tùy chọn mới. */
				__( 'The "%1$s" option key has been renamed to "%2$s".' ),
				$option,
				$deprecated_keys[ $option ]
			)
		);
		return get_option( $deprecated_keys[ $option ], $default_value );
	}

	/**
	 * Lọc giá trị của một tùy chọn hiện có trước khi nó được lấy ra.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * Trả về một giá trị khác false từ bộ lọc sẽ bỏ qua việc truy xuất
	 * và trả về giá trị đó thay thế.
	 *
	 * @since 1.5.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 * @since 4.9.0 Tham số `$default_value` được thêm vào.
	 *
	 * @param mixed  $pre_option    Giá trị trả về thay vì giá trị tùy chọn. Điều này khác với
	 *                              `$default_value`, được dùng làm giá trị dự phòng trong trường hợp
	 *                              tùy chọn không tồn tại ở nơi khác trong get_option().
	 *                              Mặc định false (để bỏ qua short-circuit).
	 * @param string $option        Tên tùy chọn.
	 * @param mixed  $default_value Giá trị dự phòng trả về nếu tùy chọn không tồn tại.
	 *                              Mặc định false.
	 */
	$pre = apply_filters( "pre_option_{$option}", false, $option, $default_value );

	/**
	 * Lọc giá trị của tất cả các tùy chọn hiện có trước khi chúng được lấy ra.
	 *
	 * Trả về giá trị truthy từ bộ lọc sẽ bỏ qua việc truy xuất
	 * và trả về giá trị được truyền vào thay thế.
	 *
	 * @since 6.1.0
	 *
	 * @param mixed  $pre_option    Giá trị trả về thay vì giá trị tùy chọn. Điều này khác với
	 *                              `$default_value`, được dùng làm giá trị dự phòng trong trường hợp
	 *                              tùy chọn không tồn tại ở nơi khác trong get_option().
	 *                              Mặc định false (để bỏ qua short-circuit).
	 * @param string $option        Tên tùy chọn.
	 * @param mixed  $default_value Giá trị dự phòng trả về nếu tùy chọn không tồn tại.
	 *                              Mặc định false.
	 */
	$pre = apply_filters( 'pre_option', $pre, $option, $default_value );

	if ( false !== $pre ) {
		return $pre;
	}

	if ( defined( 'WP_SETUP_CONFIG' ) ) {
		return false;
	}

	// Phân biệt giữa `false` là giá trị mặc định, và không truyền giá trị mặc định.
	$passed_default = func_num_args() > 1;

	if ( ! wp_installing() ) {
		$alloptions = wp_load_alloptions();
		/*
		 * Khi lấy giá trị tùy chọn, chúng ta kiểm tra theo thứ tự sau để tối ưu hiệu suất:
		 *
		 * 1. Kiểm tra bộ nhớ đệm 'alloptions' trước để ưu tiên các tùy chọn đã tải.
		 * 2. Kiểm tra bộ nhớ đệm 'notoptions' trước khi tra cứu cache hoặc truy vấn DB.
		 * 3. Kiểm tra bộ nhớ đệm 'options' trước khi truy vấn DB.
		 * 4. Kiểm tra DB cho tùy chọn và lưu vào bộ nhớ đệm 'options' hoặc 'notoptions'.
		 */
		if ( isset( $alloptions[ $option ] ) ) {
			$value = $alloptions[ $option ];
		} else {
			// Kiểm tra các tùy chọn không tồn tại trước để tránh tra cứu object cache và truy vấn DB không cần thiết.
			$notoptions = wp_cache_get( 'notoptions', 'options' );

			if ( ! is_array( $notoptions ) ) {
				$notoptions = array();
				wp_cache_set( 'notoptions', $notoptions, 'options' );
			}

			if ( isset( $notoptions[ $option ] ) ) {
				/**
				 * Lọc giá trị mặc định cho một tùy chọn.
				 *
				 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
				 *
				 * @since 3.4.0
				 * @since 4.4.0 Tham số `$option` được thêm vào.
				 * @since 4.7.0 Tham số `$passed_default` được thêm vào để phân biệt giữa giá trị `false` và giá trị tham số mặc định.
				 *
				 * @param mixed  $default_value  Giá trị mặc định trả về nếu tùy chọn không tồn tại
				 *                               trong cơ sở dữ liệu.
				 * @param string $option         Tên tùy chọn.
				 * @param bool   $passed_default Hàm `get_option()` có được truyền giá trị mặc định không?
				 */
				return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
			}

			$value = wp_cache_get( $option, 'options' );

			if ( false === $value ) {

				$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

				// Phải dùng get_row() thay vì get_var() vì sự bất thường với các giá trị 0, false, null.
				if ( is_object( $row ) ) {
					$value = $row->option_value;
					wp_cache_add( $option, $value, 'options' );
				} else { // Tùy chọn không tồn tại, vì vậy chúng ta phải lưu cache sự không tồn tại của nó.
					$notoptions[ $option ] = true;
					wp_cache_set( 'notoptions', $notoptions, 'options' );

					/** This filter is documented in wp-includes/option.php */
					return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
				}
			}
		}
	} else {
		$suppress = $wpdb->suppress_errors();
		$row      = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
		$wpdb->suppress_errors( $suppress );

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			/** This filter is documented in wp-includes/option.php */
			return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
		}
	}

	// Nếu home chưa được thiết lập, sử dụng siteurl.
	if ( 'home' === $option && '' === $value ) {
		return get_option( 'siteurl' );
	}

	if ( in_array( $option, array( 'siteurl', 'home', 'category_base', 'tag_base' ), true ) ) {
		$value = untrailingslashit( $value );
	}

	/**
	 * Lọc giá trị của một tùy chọn hiện có.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 1.5.0 Với tên 'option_' . $setting
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 *
	 * @param mixed  $value  Giá trị của tùy chọn. Nếu đã được serialize khi lưu,
	 *                       nó sẽ được unserialize trước khi trả về.
	 * @param string $option Tên tùy chọn.
	 */
	return apply_filters( "option_{$option}", maybe_unserialize( $value ), $option );
}

/**
 * Nạp sẵn các tùy chọn cụ thể vào cache bằng một truy vấn cơ sở dữ liệu duy nhất.
 *
 * Chỉ những tùy chọn chưa tồn tại trong cache mới được tải.
 *
 * @since 6.4.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string[] $options Mảng các tên tùy chọn cần tải.
 */
function wp_prime_option_caches( $options ) {
	global $wpdb;

	$alloptions     = wp_load_alloptions();
	$cached_options = wp_cache_get_multiple( $options, 'options' );
	$notoptions     = wp_cache_get( 'notoptions', 'options' );
	if ( ! is_array( $notoptions ) ) {
		$notoptions = array();
	}

	// Lọc các tùy chọn không có trong cache.
	$options_to_prime = array();
	foreach ( $options as $option ) {
		if (
			( ! isset( $cached_options[ $option ] ) || false === $cached_options[ $option ] )
			&& ! isset( $alloptions[ $option ] )
			&& ! isset( $notoptions[ $option ] )
		) {
			$options_to_prime[] = $option;
		}
	}

	// Thoát sớm nếu không có tùy chọn nào cần tải.
	if ( empty( $options_to_prime ) ) {
		return;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			sprintf(
				"SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN (%s)",
				implode( ',', array_fill( 0, count( $options_to_prime ), '%s' ) )
			),
			$options_to_prime
		)
	);

	$options_found = array();
	foreach ( $results as $result ) {
		/*
		 * Cache được nạp sẵn với giá trị thô (tức là chưa được maybe_unserialize).
		 *
		 * `get_option()` sẽ xử lý unserialize giá trị khi cần thiết.
		 */
		$options_found[ $result->option_name ] = $result->option_value;
	}
	wp_cache_set_multiple( $options_found, 'options' );

	// Nếu tất cả tùy chọn đã được tìm thấy, không cần cập nhật cache `notoptions`.
	if ( count( $options_found ) === count( $options_to_prime ) ) {
		return;
	}

	$options_not_found = array_diff( $options_to_prime, array_keys( $options_found ) );

	// Thêm các tùy chọn không tìm thấy vào cache.
	$update_notoptions = false;
	foreach ( $options_not_found as $option_name ) {
		if ( ! isset( $notoptions[ $option_name ] ) ) {
			$notoptions[ $option_name ] = true;
			$update_notoptions          = true;
		}
	}

	// Chỉ cập nhật cache nếu nó đã được thay đổi.
	if ( $update_notoptions ) {
		wp_cache_set( 'notoptions', $notoptions, 'options' );
	}
}

/**
 * Nạp sẵn cache của tất cả tùy chọn đã đăng ký với một nhóm tùy chọn cụ thể.
 *
 * @since 6.4.0
 *
 * @global array $new_allowed_options
 *
 * @param string $option_group Nhóm tùy chọn cần tải.
 */
function wp_prime_option_caches_by_group( $option_group ) {
	global $new_allowed_options;

	if ( isset( $new_allowed_options[ $option_group ] ) ) {
		wp_prime_option_caches( $new_allowed_options[ $option_group ] );
	}
}

/**
 * Lấy nhiều tùy chọn cùng lúc.
 *
 * Các tùy chọn được tải khi cần thiết để sử dụng tối đa một truy vấn cơ sở dữ liệu.
 *
 * @since 6.4.0
 *
 * @param string[] $options Mảng các tên tùy chọn cần lấy.
 * @return array Mảng các cặp key-value cho các tùy chọn được yêu cầu.
 */
function get_options( $options ) {
	wp_prime_option_caches( $options );

	$result = array();
	foreach ( $options as $option ) {
		$result[ $option ] = get_option( $option );
	}

	return $result;
}

/**
 * Thiết lập giá trị autoload cho nhiều tùy chọn trong cơ sở dữ liệu.
 *
 * Tự động tải quá nhiều tùy chọn có thể dẫn đến vấn đề hiệu suất, đặc biệt nếu các tùy chọn không được sử dụng thường xuyên.
 * Hàm này cho phép thay đổi giá trị autoload cho nhiều tùy chọn mà không thay đổi giá trị thực tế của tùy chọn.
 * Điều này được khuyến nghị cho các hook kích hoạt và hủy kích hoạt plugin, để đảm bảo các tùy chọn chỉ được sử dụng
 * bởi plugin mà thường được tự động tải có thể được thiết lập không tự động tải khi plugin không hoạt động.
 *
 * @since 6.4.0
 * @since 6.7.0 Các giá trị autoload 'yes' và 'no' đã bị deprecated.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param array $options Mảng kết hợp gồm tên tùy chọn và giá trị autoload cần thiết lập. Tên tùy chọn
 *                       không cần SQL-escape. Giá trị autoload nên là boolean. Để tương thích ngược,
 *                       'yes' và 'no' cũng được chấp nhận, mặc dù việc sử dụng các giá trị này đã bị deprecated.
 * @return array Mảng kết hợp của tất cả $options được cung cấp làm key và giá trị boolean cho biết giá trị autoload
 *               có được cập nhật hay không.
 */
function wp_set_option_autoload_values( array $options ) {
	global $wpdb;

	if ( ! $options ) {
		return array();
	}

	$grouped_options = array(
		'on'  => array(),
		'off' => array(),
	);
	$results         = array();
	foreach ( $options as $option => $autoload ) {
		wp_protect_special_option( $option ); // Đảm bảo chỉ các tùy chọn hợp lệ mới có thể được truyền vào.

		/*
		 * Làm sạch giá trị autoload và phân loại tương ứng.
		 * Các giá trị 'yes', 'no', 'on', và 'off' được hỗ trợ để tương thích ngược.
		 */
		if ( 'off' === $autoload || 'no' === $autoload || false === $autoload ) {
			$grouped_options['off'][] = $option;
		} else {
			$grouped_options['on'][] = $option;
		}
		$results[ $option ] = false; // Khởi tạo giá trị kết quả.
	}

	$where      = array();
	$where_args = array();
	foreach ( $grouped_options as $autoload => $options ) {
		if ( ! $options ) {
			continue;
		}
		$placeholders = implode( ',', array_fill( 0, count( $options ), '%s' ) );
		$where[]      = "autoload != '%s' AND option_name IN ($placeholders)";
		$where_args[] = $autoload;
		foreach ( $options as $option ) {
			$where_args[] = $option;
		}
	}
	$where = 'WHERE ' . implode( ' OR ', $where );

	/*
	 * Xác định các tùy chọn liên quan chưa sử dụng giá trị autoload đã cho.
	 * Nếu không có tùy chọn nào được trả về, không cần cập nhật.
	 */
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$options_to_update = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options $where", $where_args ) );
	if ( ! $options_to_update ) {
		return $results;
	}

	// Chạy các truy vấn UPDATE khi cần (tối đa 2) để cập nhật giá trị autoload của các tùy chọn liên quan thành 'yes' hoặc 'no'.
	foreach ( $grouped_options as $autoload => $options ) {
		if ( ! $options ) {
			continue;
		}
		$options                      = array_intersect( $options, $options_to_update );
		$grouped_options[ $autoload ] = $options;
		if ( ! $grouped_options[ $autoload ] ) {
			continue;
		}

		// Chạy truy vấn để cập nhật giá trị autoload cho tất cả các tùy chọn cần thiết.
		$success = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->options SET autoload = %s WHERE option_name IN (" . implode( ',', array_fill( 0, count( $grouped_options[ $autoload ] ), '%s' ) ) . ')',
				array_merge(
					array( $autoload ),
					$grouped_options[ $autoload ]
				)
			)
		);
		if ( ! $success ) {
			// Đặt danh sách tùy chọn thành mảng rỗng để chỉ ra rằng không có tùy chọn nào được cập nhật.
			$grouped_options[ $autoload ] = array();
			continue;
		}

		// Giả định rằng khi thành công tất cả tùy chọn đã được cập nhật, điều này đúng vì chỉ có giá trị mới được gửi.
		foreach ( $grouped_options[ $autoload ] as $option ) {
			$results[ $option ] = true;
		}
	}

	/*
	 * Nếu có tùy chọn nào được chuyển sang 'on', xóa cache riêng lẻ của chúng, và xóa cache 'alloptions' để nó
	 * được làm mới khi cần.
	 * Nếu không có tùy chọn nào được chuyển sang 'on' nhưng có tùy chọn được chuyển sang 'no', xóa chúng khỏi cache
	 * 'alloptions'. Điều này không cần thiết khi tùy chọn được chuyển sang 'on', vì trong trường hợp đó toàn bộ cache
	 * đã bị xóa rồi.
	 */
	if ( $grouped_options['on'] ) {
		wp_cache_delete_multiple( $grouped_options['on'], 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	} elseif ( $grouped_options['off'] ) {
		$alloptions = wp_load_alloptions( true );

		foreach ( $grouped_options['off'] as $option ) {
			if ( isset( $alloptions[ $option ] ) ) {
				unset( $alloptions[ $option ] );
			}
		}

		wp_cache_set( 'alloptions', $alloptions, 'options' );
	}

	return $results;
}

/**
 * Thiết lập giá trị autoload cho nhiều tùy chọn trong cơ sở dữ liệu.
 *
 * Đây là wrapper cho {@see wp_set_option_autoload_values()}, có thể được dùng để thiết lập các giá trị autoload khác nhau cho
 * mỗi tùy chọn cùng lúc.
 *
 * @since 6.4.0
 * @since 6.7.0 Các giá trị autoload 'yes' và 'no' đã bị deprecated.
 *
 * @see wp_set_option_autoload_values()
 *
 * @param string[] $options  Danh sách tên tùy chọn. Không cần SQL-escape.
 * @param bool     $autoload Giá trị autoload để kiểm soát việc tải tùy chọn khi WordPress khởi động.
 *                           Để tương thích ngược, 'yes' và 'no' cũng được chấp nhận, mặc dù việc sử dụng các giá trị này
 *                           đã bị deprecated.
 * @return array Mảng kết hợp của tất cả $options được cung cấp làm key và giá trị boolean cho biết giá trị autoload
 *               có được cập nhật hay không.
 */
function wp_set_options_autoload( array $options, $autoload ) {
	return wp_set_option_autoload_values(
		array_fill_keys( $options, $autoload )
	);
}

/**
 * Thiết lập giá trị autoload cho một tùy chọn trong cơ sở dữ liệu.
 *
 * Đây là wrapper cho {@see wp_set_option_autoload_values()}, có thể được dùng để thiết lập giá trị autoload cho
 * nhiều tùy chọn cùng lúc.
 *
 * @since 6.4.0
 * @since 6.7.0 Các giá trị autoload 'yes' và 'no' đã bị deprecated.
 *
 * @see wp_set_option_autoload_values()
 *
 * @param string $option   Tên tùy chọn. Không cần SQL-escape.
 * @param bool   $autoload Giá trị autoload để kiểm soát việc tải tùy chọn khi WordPress khởi động.
 *                         Để tương thích ngược, 'yes' và 'no' cũng được chấp nhận, mặc dù việc sử dụng các giá trị này
 *                         đã bị deprecated.
 * @return bool True nếu giá trị autoload đã được thay đổi, false nếu không.
 */
function wp_set_option_autoload( $option, $autoload ) {
	$result = wp_set_option_autoload_values( array( $option => $autoload ) );
	if ( isset( $result[ $option ] ) ) {
		return $result[ $option ];
	}
	return false;
}

/**
 * Bảo vệ tùy chọn đặc biệt của WordPress khỏi bị chỉnh sửa.
 *
 * Sẽ dừng chương trình nếu $option nằm trong danh sách được bảo vệ. Các tùy chọn được bảo vệ
 * là 'alloptions' và 'notoptions'.
 *
 * @since 2.2.0
 *
 * @param string $option Tên tùy chọn.
 */
function wp_protect_special_option( $option ) {
	if ( 'alloptions' === $option || 'notoptions' === $option ) {
		wp_die(
			sprintf(
				/* translators: %s: Option name. */
				__( '%s is a protected WP option and may not be modified' ),
				esc_html( $option )
			)
		);
	}
}

/**
 * In giá trị tùy chọn sau khi làm sạch để dùng trong form.
 *
 * @since 1.5.0
 *
 * @param string $option Tên tùy chọn.
 */
function form_option( $option ) {
	echo esc_attr( get_option( $option ) );
}

/**
 * Tải và lưu cache tất cả tùy chọn tự động tải, hoặc tất cả tùy chọn nếu có.
 *
 * @since 2.2.0
 * @since 5.3.1 Tham số `$force_cache` được thêm vào.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param bool $force_cache Tùy chọn. Có buộc cập nhật cache cục bộ
 *                          từ cache persistent hay không. Mặc định false.
 * @return array Danh sách tất cả tùy chọn.
 */
function wp_load_alloptions( $force_cache = false ) {
	global $wpdb;

	/**
	 * Lọc mảng alloptions trước khi nó được điền dữ liệu.
	 *
	 * Trả về mảng từ bộ lọc sẽ bỏ qua hàm
	 * wp_load_alloptions(), trả về giá trị đó thay thế.
	 *
	 * @since 6.2.0
	 *
	 * @param array|null $alloptions  Mảng alloptions. Mặc định null.
	 * @param bool       $force_cache Có buộc cập nhật cache cục bộ từ cache persistent hay không. Mặc định false.
	 */
	$alloptions = apply_filters( 'pre_wp_load_alloptions', null, $force_cache );
	if ( is_array( $alloptions ) ) {
		return $alloptions;
	}

	if ( ! wp_installing() || ! is_multisite() ) {
		$alloptions = wp_cache_get( 'alloptions', 'options', $force_cache );
	} else {
		$alloptions = false;
	}

	if ( ! $alloptions ) {
		$suppress      = $wpdb->suppress_errors();
		$alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload IN ( '" . implode( "', '", esc_sql( wp_autoload_values_to_autoload() ) ) . "' )" );

		if ( ! $alloptions_db ) {
			$alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
		}
		$wpdb->suppress_errors( $suppress );

		$alloptions = array();
		foreach ( (array) $alloptions_db as $o ) {
			$alloptions[ $o->option_name ] = $o->option_value;
		}

		if ( ! wp_installing() || ! is_multisite() ) {
			/**
			 * Lọc tất cả tùy chọn trước khi lưu cache.
			 *
			 * @since 4.9.0
			 *
			 * @param array $alloptions Mảng chứa tất cả tùy chọn.
			 */
			$alloptions = apply_filters( 'pre_cache_alloptions', $alloptions );

			wp_cache_add( 'alloptions', $alloptions, 'options' );
		}
	}

	/**
	 * Lọc tất cả tùy chọn sau khi lấy ra.
	 *
	 * @since 4.9.0
	 *
	 * @param array $alloptions Mảng chứa tất cả tùy chọn.
	 */
	return apply_filters( 'alloptions', $alloptions );
}

/**
 * Nạp sẵn các tùy chọn mạng cụ thể cho mạng hiện tại vào cache bằng một truy vấn cơ sở dữ liệu duy nhất.
 *
 * Chỉ những tùy chọn mạng chưa tồn tại trong cache mới được tải.
 *
 * Nếu site không phải multisite, sẽ gọi wp_prime_option_caches().
 *
 * @since 6.6.0
 *
 * @see wp_prime_network_option_caches()
 *
 * @param string[] $options Mảng các tên tùy chọn cần tải.
 */
function wp_prime_site_option_caches( array $options ) {
	wp_prime_network_option_caches( null, $options );
}

/**
 * Nạp sẵn các tùy chọn mạng cụ thể vào cache bằng một truy vấn cơ sở dữ liệu duy nhất.
 *
 * Chỉ những tùy chọn mạng chưa tồn tại trong cache mới được tải.
 *
 * Nếu site không phải multisite, sẽ gọi wp_prime_option_caches().
 *
 * @since 6.6.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|null $network_id ID của mạng. Có thể là null để mặc định sử dụng ID mạng hiện tại.
 * @param string[] $options    Mảng các tên tùy chọn cần tải.
 */
function wp_prime_network_option_caches( $network_id, array $options ) {
	global $wpdb;

	if ( wp_installing() ) {
		return;
	}

	if ( ! is_multisite() ) {
		wp_prime_option_caches( $options );
		return;
	}

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return;
	}

	$network_id = (int) $network_id;

	// Dự phòng sử dụng mạng hiện tại nếu không chỉ định ID mạng.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	$cache_keys = array();
	foreach ( $options as $option ) {
		$cache_keys[ $option ] = "{$network_id}:{$option}";
	}

	$cache_group    = 'site-options';
	$cached_options = wp_cache_get_multiple( array_values( $cache_keys ), $cache_group );

	$notoptions_key = "$network_id:notoptions";
	$notoptions     = wp_cache_get( $notoptions_key, $cache_group );

	if ( ! is_array( $notoptions ) ) {
		$notoptions = array();
	}

	// Lọc các tùy chọn không có trong cache.
	$options_to_prime = array();
	foreach ( $cache_keys as $option => $cache_key ) {
		if (
			( ! isset( $cached_options[ $cache_key ] ) || false === $cached_options[ $cache_key ] )
			&& ! isset( $notoptions[ $option ] )
		) {
			$options_to_prime[] = $option;
		}
	}

	// Thoát sớm nếu không có tùy chọn nào cần tải.
	if ( empty( $options_to_prime ) ) {
		return;
	}

	$query_args   = $options_to_prime;
	$query_args[] = $network_id;
	$results      = $wpdb->get_results(
		$wpdb->prepare(
			sprintf(
				"SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE meta_key IN (%s) AND site_id = %s",
				implode( ',', array_fill( 0, count( $options_to_prime ), '%s' ) ),
				'%d'
			),
			$query_args
		)
	);

	$data          = array();
	$options_found = array();
	foreach ( $results as $result ) {
		$key                = $result->meta_key;
		$cache_key          = $cache_keys[ $key ];
		$data[ $cache_key ] = maybe_unserialize( $result->meta_value );
		$options_found[]    = $key;
	}
	wp_cache_set_multiple( $data, $cache_group );
	// Nếu tất cả tùy chọn đã được tìm thấy, không cần cập nhật cache `notoptions`.
	if ( count( $options_found ) === count( $options_to_prime ) ) {
		return;
	}

	$options_not_found = array_diff( $options_to_prime, $options_found );

	// Thêm các tùy chọn không tìm thấy vào cache.
	$update_notoptions = false;
	foreach ( $options_not_found as $option_name ) {
		if ( ! isset( $notoptions[ $option_name ] ) ) {
			$notoptions[ $option_name ] = true;
			$update_notoptions          = true;
		}
	}

	// Chỉ cập nhật cache nếu nó đã được thay đổi.
	if ( $update_notoptions ) {
		wp_cache_set( $notoptions_key, $notoptions, $cache_group );
	}
}

/**
 * Tải và nạp sẵn cache cho một số tùy chọn mạng thường xuyên được yêu cầu nếu là is_multisite().
 *
 * @since 3.0.0
 * @since 6.3.0 Cũng nạp sẵn cache cho tùy chọn mạng khi object cache persistent được bật.
 * @since 6.6.0 Sử dụng wp_prime_network_option_caches().
 *
 * @param int $network_id Tùy chọn. ID mạng cần nạp sẵn cache tùy chọn. Mặc định là mạng hiện tại.
 */
function wp_load_core_site_options( $network_id = null ) {
	if ( ! is_multisite() || wp_installing() ) {
		return;
	}
	$core_options = array( 'site_name', 'siteurl', 'active_sitewide_plugins', '_site_transient_timeout_theme_roots', '_site_transient_theme_roots', 'site_admins', 'can_compress_scripts', 'global_terms_enabled', 'ms_files_rewriting', 'WPLANG' );

	wp_prime_network_option_caches( $network_id, $core_options );
}

/**
 * Cập nhật giá trị của một tùy chọn đã được thêm trước đó.
 *
 * Bạn không cần serialize giá trị. Nếu giá trị cần được serialize,
 * nó sẽ được serialize trước khi chèn vào cơ sở dữ liệu.
 * Lưu ý, resource không thể serialize hoặc thêm làm tùy chọn.
 *
 * Nếu tùy chọn không tồn tại, nó sẽ được tạo mới.

 * Hàm này được thiết kế để hoạt động có hoặc không có người dùng đã đăng nhập. Về mặt bảo mật,
 * các nhà phát triển plugin nên kiểm tra quyền hạn của người dùng hiện tại trước khi cập nhật bất kỳ tùy chọn nào.
 *
 * @since 1.0.0
 * @since 4.2.0 Tham số `$autoload` được thêm vào.
 * @since 6.7.0 Các giá trị autoload 'yes' và 'no' đã bị deprecated.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string    $option   Tên tùy chọn cần cập nhật. Không cần SQL-escape.
 * @param mixed     $value    Giá trị tùy chọn. Phải serializable nếu không phải scalar. Không cần SQL-escape.
 * @param bool|null $autoload Tùy chọn. Có tải tùy chọn khi WordPress khởi động hay không.
 *                            Chấp nhận boolean, hoặc `null` để giữ nguyên giá trị ban đầu hoặc, nếu không có giá trị ban đầu,
 *                            để WordPress quyết định dựa trên heuristic mặc định.
 *                            Với các tùy chọn hiện có, `$autoload` chỉ có thể cập nhật qua `update_option()` nếu `$value`
 *                            cũng được thay đổi.
 *                            Để tương thích ngược, 'yes' và 'no' cũng được chấp nhận, mặc dù việc sử dụng các giá trị này
 *                            đã bị deprecated.
 *                            Tự động tải quá nhiều tùy chọn có thể dẫn đến vấn đề hiệu suất, đặc biệt nếu
 *                            các tùy chọn không được sử dụng thường xuyên. Với các tùy chọn được truy cập ở nhiều nơi
 *                            trên frontend, nên tự động tải chúng bằng cách sử dụng true.
 *                            Với các tùy chọn chỉ được truy cập trên một vài URL cụ thể, nên
 *                            không tự động tải chúng bằng cách sử dụng false.
 *                            Với tùy chọn không tồn tại, mặc định là null, nghĩa là WordPress sẽ xác định
 *                            giá trị autoload.
 * @return bool True nếu giá trị đã được cập nhật, false nếu không.
 */
function update_option( $option, $value, $autoload = null ) {
	global $wpdb;

	if ( is_scalar( $option ) ) {
		$option = trim( $option );
	}

	if ( empty( $option ) ) {
		return false;
	}

	/*
	 * Cho đến khi có hàm _deprecated_option() phù hợp,
	 * chuyển hướng các yêu cầu đến khóa đã lỗi thời sang khóa mới, đúng.
	 */
	$deprecated_keys = array(
		'blacklist_keys'    => 'disallowed_keys',
		'comment_whitelist' => 'comment_previously_approved',
	);

	if ( isset( $deprecated_keys[ $option ] ) && ! wp_installing() ) {
		_deprecated_argument(
			__FUNCTION__,
			'5.5.0',
			sprintf(
				/* translators: 1: Khóa tùy chọn đã lỗi thời, 2: Khóa tùy chọn mới. */
				__( 'The "%1$s" option key has been renamed to "%2$s".' ),
				$option,
				$deprecated_keys[ $option ]
			)
		);
		return update_option( $deprecated_keys[ $option ], $value, $autoload );
	}

	wp_protect_special_option( $option );

	if ( is_object( $value ) ) {
		$value = clone $value;
	}

	$value     = sanitize_option( $option, $value );
	$old_value = get_option( $option );

	/**
	 * Lọc một tùy chọn cụ thể trước khi giá trị của nó được (có thể) serialize và cập nhật.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 2.6.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 *
	 * @param mixed  $value     Giá trị tùy chọn mới, chưa serialize.
	 * @param mixed  $old_value Giá trị tùy chọn cũ.
	 * @param string $option    Tên tùy chọn.
	 */
	$value = apply_filters( "pre_update_option_{$option}", $value, $old_value, $option );

	/**
	 * Lọc một tùy chọn trước khi giá trị của nó được (có thể) serialize và cập nhật.
	 *
	 * @since 3.9.0
	 *
	 * @param mixed  $value     Giá trị tùy chọn mới, chưa serialize.
	 * @param string $option    Tên tùy chọn.
	 * @param mixed  $old_value Giá trị tùy chọn cũ.
	 */
	$value = apply_filters( 'pre_update_option', $value, $option, $old_value );

	/*
	 * Nếu giá trị mới và cũ giống nhau, không cần cập nhật.
	 *
	 * Các giá trị chưa serialize sẽ đủ trong hầu hết trường hợp. Nếu dữ liệu chưa serialize
	 * khác nhau, dữ liệu (có thể) đã serialize sẽ được kiểm tra để tránh
	 * các truy vấn cơ sở dữ liệu không cần thiết cho các instance đối tượng giống hệt nhau.
	 *
	 * Xem https://core.trac.wordpress.org/ticket/38903
	 */
	if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
		return false;
	}

	/** This filter is documented in wp-includes/option.php */
	if ( apply_filters( "default_option_{$option}", false, $option, false ) === $old_value ) {
		return add_option( $option, $value, '', $autoload );
	}

	$serialized_value = maybe_serialize( $value );

	/**
	 * Kích hoạt ngay trước khi giá trị tùy chọn được cập nhật.
	 *
	 * @since 2.9.0
	 *
	 * @param string $option    Tên tùy chọn cần cập nhật.
	 * @param mixed  $old_value Giá trị tùy chọn cũ.
	 * @param mixed  $value     Giá trị tùy chọn mới.
	 */
	do_action( 'update_option', $option, $old_value, $value );

	$update_args = array(
		'option_value' => $serialized_value,
	);

	if ( null !== $autoload ) {
		$update_args['autoload'] = wp_determine_option_autoload_value( $option, $value, $serialized_value, $autoload );
	} else {
		// Lấy giá trị autoload hiện tại để đánh giá lại trong trường hợp nó được thiết lập tự động.
		$raw_autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
		$allow_values = array( 'auto-on', 'auto-off', 'auto' );
		if ( in_array( $raw_autoload, $allow_values, true ) ) {
			$autoload = wp_determine_option_autoload_value( $option, $value, $serialized_value, $autoload );
			if ( $autoload !== $raw_autoload ) {
				$update_args['autoload'] = $autoload;
			}
		}
	}

	$result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );
	if ( ! $result ) {
		return false;
	}

	$notoptions = wp_cache_get( 'notoptions', 'options' );

	if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
		unset( $notoptions[ $option ] );
		wp_cache_set( 'notoptions', $notoptions, 'options' );
	}

	if ( ! wp_installing() ) {
		if ( ! isset( $update_args['autoload'] ) ) {
			// Cập nhật giá trị cache dựa trên vị trí nó hiện đang được cache.
			$alloptions = wp_load_alloptions( true );

			if ( isset( $alloptions[ $option ] ) ) {
				$alloptions[ $option ] = $serialized_value;
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			} else {
				wp_cache_set( $option, $serialized_value, 'options' );
			}
		} elseif ( in_array( $update_args['autoload'], wp_autoload_values_to_autoload(), true ) ) {
			// Xóa cache riêng lẻ, sau đó thiết lập trong cache alloptions.
			wp_cache_delete( $option, 'options' );

			$alloptions = wp_load_alloptions( true );

			$alloptions[ $option ] = $serialized_value;
			wp_cache_set( 'alloptions', $alloptions, 'options' );
		} else {
			// Xóa cache alloptions, sau đó thiết lập cache riêng lẻ.
			$alloptions = wp_load_alloptions( true );

			if ( isset( $alloptions[ $option ] ) ) {
				unset( $alloptions[ $option ] );
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			}

			wp_cache_set( $option, $serialized_value, 'options' );
		}
	}

	/**
	 * Kích hoạt sau khi giá trị của một tùy chọn cụ thể đã được cập nhật thành công.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 2.0.1
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 *
	 * @param mixed  $old_value Giá trị tùy chọn cũ.
	 * @param mixed  $value     Giá trị tùy chọn mới.
	 * @param string $option    Tên tùy chọn.
	 */
	do_action( "update_option_{$option}", $old_value, $value, $option );

	/**
	 * Kích hoạt sau khi giá trị của một tùy chọn đã được cập nhật thành công.
	 *
	 * @since 2.9.0
	 *
	 * @param string $option    Tên tùy chọn đã cập nhật.
	 * @param mixed  $old_value Giá trị tùy chọn cũ.
	 * @param mixed  $value     Giá trị tùy chọn mới.
	 */
	do_action( 'updated_option', $option, $old_value, $value );

	return true;
}

/**
 * Thêm một tùy chọn mới.
 *
 * Bạn không cần serialize giá trị. Nếu giá trị cần được serialize,
 * nó sẽ được serialize trước khi chèn vào cơ sở dữ liệu.
 * Lưu ý, resource không thể serialize hoặc thêm làm tùy chọn.
 *
 * Bạn có thể tạo tùy chọn mà không có giá trị và cập nhật giá trị sau.
 * Các tùy chọn hiện có sẽ không bị cập nhật và các kiểm tra được thực hiện để đảm bảo rằng bạn
 * không thêm tùy chọn WordPress được bảo vệ. Cần cẩn thận không đặt tên
 * tùy chọn trùng với những tùy chọn được bảo vệ.
 *
 * @since 1.0.0
 * @since 6.6.0 Giá trị mặc định của tham số $autoload được thay đổi thành null.
 * @since 6.7.0 Các giá trị autoload 'yes' và 'no' đã bị deprecated.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string    $option     Tên tùy chọn cần thêm. Không cần SQL-escape.
 * @param mixed     $value      Tùy chọn. Giá trị tùy chọn. Phải serializable nếu không phải scalar.
 *                              Không cần SQL-escape.
 * @param string    $deprecated Tùy chọn. Mô tả. Không còn được sử dụng.
 * @param bool|null $autoload   Tùy chọn. Có tải tùy chọn khi WordPress khởi động hay không.
 *                              Chấp nhận boolean, hoặc `null` để WordPress quyết định dựa trên heuristic mặc định.
 *                              Để tương thích ngược, 'yes' và 'no' cũng được chấp nhận, mặc dù việc sử dụng
 *                              các giá trị này đã bị deprecated.
 *                              Tự động tải quá nhiều tùy chọn có thể dẫn đến vấn đề hiệu suất, đặc biệt nếu
 *                              các tùy chọn không được sử dụng thường xuyên. Với các tùy chọn được truy cập ở nhiều nơi
 *                              trên frontend, nên tự động tải chúng bằng cách sử dụng true.
 *                              Với các tùy chọn chỉ được truy cập trên một vài URL cụ thể, nên
 *                              không tự động tải chúng bằng cách sử dụng false.
 *                              Mặc định là null, nghĩa là WordPress sẽ xác định giá trị autoload.
 * @return bool True nếu tùy chọn đã được thêm, false nếu không.
 */
function add_option( $option, $value = '', $deprecated = '', $autoload = null ) {
	global $wpdb;

	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '2.3.0' );
	}

	if ( is_scalar( $option ) ) {
		$option = trim( $option );
	}

	if ( empty( $option ) ) {
		return false;
	}

	/*
	 * Cho đến khi có hàm _deprecated_option() phù hợp,
	 * chuyển hướng các yêu cầu đến khóa đã lỗi thời sang khóa mới, đúng.
	 */
	$deprecated_keys = array(
		'blacklist_keys'    => 'disallowed_keys',
		'comment_whitelist' => 'comment_previously_approved',
	);

	if ( isset( $deprecated_keys[ $option ] ) && ! wp_installing() ) {
		_deprecated_argument(
			__FUNCTION__,
			'5.5.0',
			sprintf(
				/* translators: 1: Khóa tùy chọn đã lỗi thời, 2: Khóa tùy chọn mới. */
				__( 'The "%1$s" option key has been renamed to "%2$s".' ),
				$option,
				$deprecated_keys[ $option ]
			)
		);
		return add_option( $deprecated_keys[ $option ], $value, $deprecated, $autoload );
	}

	wp_protect_special_option( $option );

	if ( is_object( $value ) ) {
		$value = clone $value;
	}

	$value = sanitize_option( $option, $value );

	/*
	 * Đảm bảo tùy chọn chưa tồn tại.
	 * Chúng ta có thể kiểm tra cache 'notoptions' trước khi thực hiện truy vấn DB.
	 */
	$notoptions = wp_cache_get( 'notoptions', 'options' );

	if ( ! is_array( $notoptions ) || ! isset( $notoptions[ $option ] ) ) {
		/** This filter is documented in wp-includes/option.php */
		if ( apply_filters( "default_option_{$option}", false, $option, false ) !== get_option( $option ) ) {
			return false;
		}
	}

	$serialized_value = maybe_serialize( $value );

	$autoload = wp_determine_option_autoload_value( $option, $value, $serialized_value, $autoload );

	/**
	 * Kích hoạt trước khi một tùy chọn được thêm.
	 *
	 * @since 2.9.0
	 *
	 * @param string $option Tên tùy chọn cần thêm.
	 * @param mixed  $value  Giá trị của tùy chọn.
	 */
	do_action( 'add_option', $option, $value );

	$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );
	if ( ! $result ) {
		return false;
	}

	if ( ! wp_installing() ) {
		if ( in_array( $autoload, wp_autoload_values_to_autoload(), true ) ) {
			$alloptions            = wp_load_alloptions( true );
			$alloptions[ $option ] = $serialized_value;
			wp_cache_set( 'alloptions', $alloptions, 'options' );
		} else {
			wp_cache_set( $option, $serialized_value, 'options' );
		}
	}

	// Tùy chọn này đã tồn tại.
	$notoptions = wp_cache_get( 'notoptions', 'options' ); // Vâng, lại lần nữa... chúng ta cần nó được cập nhật mới.

	if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
		unset( $notoptions[ $option ] );
		wp_cache_set( 'notoptions', $notoptions, 'options' );
	}

	/**
	 * Kích hoạt sau khi một tùy chọn cụ thể đã được thêm.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 2.5.0 Với tên `add_option_{$name}`
	 * @since 3.0.0
	 *
	 * @param string $option Tên tùy chọn cần thêm.
	 * @param mixed  $value  Giá trị của tùy chọn.
	 */
	do_action( "add_option_{$option}", $option, $value );

	/**
	 * Kích hoạt sau khi một tùy chọn đã được thêm.
	 *
	 * @since 2.9.0
	 *
	 * @param string $option Tên tùy chọn đã thêm.
	 * @param mixed  $value  Giá trị của tùy chọn.
	 */
	do_action( 'added_option', $option, $value );

	return true;
}

/**
 * Xóa một tùy chọn theo tên. Ngăn không cho xóa các tùy chọn WordPress được bảo vệ.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param string $option Tên tùy chọn cần xóa. Không cần SQL-escape.
 * @return bool True nếu tùy chọn đã được xóa, false nếu không.
 */
function delete_option( $option ) {
	global $wpdb;

	if ( is_scalar( $option ) ) {
		$option = trim( $option );
	}

	if ( empty( $option ) ) {
		return false;
	}

	wp_protect_special_option( $option );

	// Lấy ID, nếu không có ID thì trả về.
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
	if ( is_null( $row ) ) {
		return false;
	}

	/**
	 * Kích hoạt ngay trước khi một tùy chọn bị xóa.
	 *
	 * @since 2.9.0
	 *
	 * @param string $option Tên tùy chọn cần xóa.
	 */
	do_action( 'delete_option', $option );

	$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );

	if ( ! wp_installing() ) {
		if ( in_array( $row->autoload, wp_autoload_values_to_autoload(), true ) ) {
			$alloptions = wp_load_alloptions( true );

			if ( is_array( $alloptions ) && isset( $alloptions[ $option ] ) ) {
				unset( $alloptions[ $option ] );
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			}
		} else {
			wp_cache_delete( $option, 'options' );
		}

		$notoptions = wp_cache_get( 'notoptions', 'options' );

		if ( ! is_array( $notoptions ) ) {
			$notoptions = array();
		}
		$notoptions[ $option ] = true;

		wp_cache_set( 'notoptions', $notoptions, 'options' );
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi một tùy chọn cụ thể đã bị xóa.
		 *
		 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
		 *
		 * @since 3.0.0
		 *
		 * @param string $option Tên tùy chọn đã bị xóa.
		 */
		do_action( "delete_option_{$option}", $option );

		/**
		 * Kích hoạt sau khi một tùy chọn đã bị xóa.
		 *
		 * @since 2.9.0
		 *
		 * @param string $option Tên tùy chọn đã bị xóa.
		 */
		do_action( 'deleted_option', $option );

		return true;
	}

	return false;
}

/**
 * Xác định giá trị autoload phù hợp cho một tùy chọn dựa trên đầu vào.
 *
 * Hàm này kiểm tra giá trị autoload được cung cấp và trả về giá trị đã được chuẩn hóa
 * ('on', 'off', 'auto-on', 'auto-off', hoặc 'auto') dựa trên các điều kiện cụ thể.
 *
 * Nếu không có giá trị autoload rõ ràng được cung cấp, hàm sẽ kiểm tra các heuristic nhất định xung quanh tùy chọn đã cho.
 * Nó sẽ trả về `auto-on` để chỉ ra tự động tải, `auto-off` để chỉ ra không tự động tải, hoặc `auto` nếu không thể
 * đưa ra quyết định rõ ràng.
 *
 * @since 6.6.0
 * @access private
 *
 * @param string    $option           Tên tùy chọn.
 * @param mixed     $value            Giá trị của tùy chọn để kiểm tra giá trị autoload.
 * @param mixed     $serialized_value Giá trị đã serialize của tùy chọn để kiểm tra giá trị autoload.
 * @param bool|null $autoload         Giá trị autoload cần kiểm tra.
 *                                    Chấp nhận 'on'|true để bật hoặc 'off'|false để tắt, hoặc
 *                                    'auto-on', 'auto-off', hoặc 'auto' cho mục đích nội bộ.
 *                                    Bất kỳ giá trị autoload nào khác sẽ bị ép thành 'auto-on',
 *                                    'auto-off', hoặc 'auto'.
 *                                    'yes' và 'no' được hỗ trợ để tương thích ngược.
 * @return string Trả về giá trị $autoload gốc nếu rõ ràng, hoặc 'auto-on', 'auto-off',
 *                hoặc 'auto' tùy thuộc vào heuristic mặc định.
 */
function wp_determine_option_autoload_value( $option, $value, $serialized_value, $autoload ) {

	// Kiểm tra xem autoload có phải boolean không.
	if ( is_bool( $autoload ) ) {
		return $autoload ? 'on' : 'off';
	}

	switch ( $autoload ) {
		case 'on':
		case 'yes':
			return 'on';
		case 'off':
		case 'no':
			return 'off';
	}

	/**
	 * Cho phép xác định giá trị autoload mặc định cho tùy chọn khi không có giá trị rõ ràng được truyền.
	 *
	 * @since 6.6.0
	 *
	 * @param bool|null $autoload Giá trị autoload mặc định cần thiết lập. Trả về true sẽ được lưu là 'auto-on' trong
	 *                            cơ sở dữ liệu, false sẽ được lưu là 'auto-off', và null sẽ được lưu là 'auto'.
	 * @param string    $option   Tên tùy chọn được truyền vào.
	 * @param mixed     $value    Giá trị tùy chọn được truyền vào để lưu.
	 */
	$autoload = apply_filters( 'wp_default_autoload_value', null, $option, $value, $serialized_value );
	if ( is_bool( $autoload ) ) {
		return $autoload ? 'auto-on' : 'auto-off';
	}

	return 'auto';
}

/**
 * Lọc giá trị autoload mặc định để tắt tự động tải nếu giá trị tùy chọn quá lớn.
 *
 * @since 6.6.0
 * @access private
 *
 * @param bool|null $autoload         Giá trị autoload mặc định cần thiết lập.
 * @param string    $option           Tên tùy chọn được truyền vào.
 * @param mixed     $value            Giá trị tùy chọn được truyền vào để lưu.
 * @param mixed     $serialized_value Giá trị tùy chọn được truyền vào để lưu, ở dạng đã serialize.
 * @return bool|null Giá trị $default có thể đã được sửa đổi.
 */
function wp_filter_default_autoload_value_via_option_size( $autoload, $option, $value, $serialized_value ) {
	/**
	 * Lọc kích thước tối đa của giá trị tùy chọn tính bằng byte.
	 *
	 * @since 6.6.0
	 *
	 * @param int    $max_option_size Ngưỡng kích thước tùy chọn, tính bằng byte. Mặc định 150000.
	 * @param string $option          Tên tùy chọn.
	 */
	$max_option_size = (int) apply_filters( 'wp_max_autoloaded_option_size', 150000, $option );
	$size            = ! empty( $serialized_value ) ? strlen( $serialized_value ) : 0;

	if ( $size > $max_option_size ) {
		return false;
	}

	return $autoload;
}

/**
 * Xóa một transient.
 *
 * @since 2.8.0
 *
 * @param string $transient Tên transient. Không cần SQL-escape.
 * @return bool True nếu transient đã được xóa, false nếu không.
 */
function delete_transient( $transient ) {

	/**
	 * Kích hoạt ngay trước khi một transient cụ thể bị xóa.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 3.0.0
	 *
	 * @param string $transient Tên transient.
	 */
	do_action( "delete_transient_{$transient}", $transient );

	if ( wp_using_ext_object_cache() || wp_installing() ) {
		$result = wp_cache_delete( $transient, 'transient' );
	} else {
		$option_timeout = '_transient_timeout_' . $transient;
		$option         = '_transient_' . $transient;
		$result         = delete_option( $option );

		if ( $result ) {
			delete_option( $option_timeout );
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi một transient bị xóa.
		 *
		 * @since 3.0.0
		 *
		 * @param string $transient Tên transient đã bị xóa.
		 */
		do_action( 'deleted_transient', $transient );
	}

	return $result;
}

/**
 * Lấy giá trị của một transient.
 *
 * Nếu transient không tồn tại, không có giá trị, hoặc đã hết hạn,
 * thì giá trị trả về sẽ là false.
 *
 * @since 2.8.0
 *
 * @param string $transient Tên transient. Không cần SQL-escape.
 * @return mixed Giá trị của transient.
 */
function get_transient( $transient ) {

	/**
	 * Lọc giá trị của một transient hiện có trước khi nó được lấy ra.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * Trả về giá trị khác false từ bộ lọc sẽ bỏ qua việc truy xuất
	 * và trả về giá trị đó thay thế.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Tham số `$transient` được thêm vào.
	 *
	 * @param mixed  $pre_transient Giá trị mặc định trả về nếu transient không tồn tại.
	 *                              Bất kỳ giá trị nào khác false sẽ bỏ qua việc truy xuất
	 *                              transient và trả về giá trị đó.
	 * @param string $transient     Tên transient.
	 */
	$pre = apply_filters( "pre_transient_{$transient}", false, $transient );

	if ( false !== $pre ) {
		return $pre;
	}

	if ( wp_using_ext_object_cache() || wp_installing() ) {
		$value = wp_cache_get( $transient, 'transient' );
	} else {
		$transient_option = '_transient_' . $transient;
		if ( ! wp_installing() ) {
			// Nếu tùy chọn không có trong alloptions, nó không được tự động tải và do đó có thời gian hết hạn.
			$alloptions = wp_load_alloptions();

			if ( ! isset( $alloptions[ $transient_option ] ) ) {
				$transient_timeout = '_transient_timeout_' . $transient;
				wp_prime_option_caches( array( $transient_option, $transient_timeout ) );
				$timeout = get_option( $transient_timeout );
				if ( false !== $timeout && $timeout < time() ) {
					delete_option( $transient_option );
					delete_option( $transient_timeout );
					$value = false;
				}
			}
		}

		if ( ! isset( $value ) ) {
			$value = get_option( $transient_option );
		}
	}

	/**
	 * Lọc giá trị của một transient hiện có.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Tham số `$transient` được thêm vào.
	 *
	 * @param mixed  $value     Giá trị của transient.
	 * @param string $transient Tên transient.
	 */
	return apply_filters( "transient_{$transient}", $value, $transient );
}

/**
 * Thiết lập/cập nhật giá trị của một transient.
 *
 * Bạn không cần serialize giá trị. Nếu giá trị cần được serialize,
 * nó sẽ được serialize trước khi thiết lập.
 *
 * @since 2.8.0
 *
 * @param string $transient  Tên transient. Không cần SQL-escape.
 *                           Phải có 172 ký tự hoặc ít hơn.
 * @param mixed  $value      Giá trị transient. Phải serializable nếu không phải scalar.
 *                           Không cần SQL-escape.
 * @param int    $expiration Tùy chọn. Thời gian cho đến khi hết hạn tính bằng giây. Mặc định 0 (không hết hạn).
 * @return bool True nếu giá trị đã được thiết lập, false nếu không.
 */
function set_transient( $transient, $value, $expiration = 0 ) {

	$expiration = (int) $expiration;

	/**
	 * Lọc một transient cụ thể trước khi giá trị của nó được thiết lập.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 3.0.0
	 * @since 4.2.0 Tham số `$expiration` được thêm vào.
	 * @since 4.4.0 Tham số `$transient` được thêm vào.
	 *
	 * @param mixed  $value      Giá trị mới của transient.
	 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
	 * @param string $transient  Tên transient.
	 */
	$value = apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

	/**
	 * Lọc thời gian hết hạn cho một transient trước khi giá trị của nó được thiết lập.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 4.4.0
	 *
	 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây. Dùng 0 để không hết hạn.
	 * @param mixed  $value      Giá trị mới của transient.
	 * @param string $transient  Tên transient.
	 */
	$expiration = apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

	if ( wp_using_ext_object_cache() || wp_installing() ) {
		$result = wp_cache_set( $transient, $value, 'transient', $expiration );
	} else {
		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option  = '_transient_' . $transient;
		wp_prime_option_caches( array( $transient_option, $transient_timeout ) );

		if ( false === get_option( $transient_option ) ) {
			$autoload = true;
			if ( $expiration ) {
				$autoload = false;
				add_option( $transient_timeout, time() + $expiration, '', false );
			}
			$result = add_option( $transient_option, $value, '', $autoload );
		} else {
			/*
			 * Nếu yêu cầu hết hạn, nhưng transient không có tùy chọn timeout,
			 * xóa rồi tạo lại transient thay vì cập nhật.
			 */
			$update = true;

			if ( $expiration ) {
				if ( false === get_option( $transient_timeout ) ) {
					delete_option( $transient_option );
					add_option( $transient_timeout, time() + $expiration, '', false );
					$result = add_option( $transient_option, $value, '', false );
					$update = false;
				} else {
					update_option( $transient_timeout, time() + $expiration );
				}
			}

			if ( $update ) {
				$result = update_option( $transient_option, $value );
			}
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi giá trị của một transient cụ thể đã được thiết lập.
		 *
		 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
		 *
		 * @since 3.0.0
		 * @since 3.6.0 Tham số `$value` và `$expiration` được thêm vào.
		 * @since 4.4.0 Tham số `$transient` được thêm vào.
		 *
		 * @param mixed  $value      Giá trị transient.
		 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
		 * @param string $transient  Tên transient.
		 */
		do_action( "set_transient_{$transient}", $value, $expiration, $transient );

		/**
		 * Kích hoạt sau khi giá trị của một transient đã được thiết lập.
		 *
		 * @since 6.8.0
		 *
		 * @param string $transient  Tên transient.
		 * @param mixed  $value      Giá trị transient.
		 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
		 */
		do_action( 'set_transient', $transient, $value, $expiration );

		/**
		 * Kích hoạt sau khi transient được thiết lập.
		 *
		 * @since 3.0.0
		 * @since 3.6.0 Tham số `$value` và `$expiration` được thêm vào.
		 * @deprecated 6.8.0 Sử dụng {@see 'set_transient'} thay thế.
		 *
		 * @param string $transient  Tên transient.
		 * @param mixed  $value      Giá trị transient.
		 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
		 */
		do_action_deprecated( 'setted_transient', array( $transient, $value, $expiration ), '6.8.0', 'set_transient' );
	}

	return $result;
}

/**
 * Xóa tất cả transient đã hết hạn.
 *
 * Lưu ý rằng hàm này sẽ không làm gì nếu đang sử dụng object cache bên ngoài.
 *
 * Cú pháp xóa đa bảng được dùng để xóa bản ghi transient
 * từ bảng a, và bản ghi transient_timeout tương ứng từ bảng b.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @since 4.9.0
 *
 * @param bool $force_db Tùy chọn. Buộc dọn dẹp chạy trên cơ sở dữ liệu ngay cả khi đang sử dụng object cache bên ngoài.
 */
function delete_expired_transients( $force_db = false ) {
	global $wpdb;

	if ( ! $force_db && wp_using_ext_object_cache() ) {
		return;
	}

	$wpdb->query(
		$wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d",
			$wpdb->esc_like( '_transient_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_' ) . '%',
			time()
		)
	);

	if ( ! is_multisite() ) {
		// Site đơn lưu trữ site transient trong bảng options.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
				AND b.option_value < %d",
				$wpdb->esc_like( '_site_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
				time()
			)
		);
	} elseif ( is_multisite() && is_main_site() && is_main_network() ) {
		// Multisite lưu trữ site transient trong bảng sitemeta.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
				WHERE a.meta_key LIKE %s
				AND a.meta_key NOT LIKE %s
				AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
				AND b.meta_value < %d",
				$wpdb->esc_like( '_site_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
				time()
			)
		);
	}
}

/**
 * Lưu và khôi phục các cài đặt giao diện người dùng được lưu trong cookie.
 *
 * Kiểm tra xem cookie cài đặt người dùng hiện tại có được cập nhật không và lưu nó. Khi không có
 * cookie nào tồn tại (sử dụng trình duyệt khác), thêm cookie đã lưu cuối cùng để khôi phục
 * các cài đặt.
 *
 * @since 2.7.0
 */
function wp_user_settings() {

	if ( ! is_admin() || wp_doing_ajax() ) {
		return;
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	if ( ! is_user_member_of_blog() ) {
		return;
	}

	$settings = (string) get_user_option( 'user-settings', $user_id );

	if ( isset( $_COOKIE[ 'wp-settings-' . $user_id ] ) ) {
		$cookie = preg_replace( '/[^A-Za-z0-9=&_]/', '', $_COOKIE[ 'wp-settings-' . $user_id ] );

		// Không thay đổi hoặc cả hai đều rỗng.
		if ( $cookie === $settings ) {
			return;
		}

		$last_saved = (int) get_user_option( 'user-settings-time', $user_id );
		$current    = 0;

		if ( isset( $_COOKIE[ 'wp-settings-time-' . $user_id ] ) ) {
			$current = (int) preg_replace( '/[^0-9]/', '', $_COOKIE[ 'wp-settings-time-' . $user_id ] );
		}

		// Cookie mới hơn giá trị đã lưu. Cập nhật user_option và giữ nguyên cookie.
		if ( $current > $last_saved ) {
			update_user_option( $user_id, 'user-settings', $cookie, false );
			update_user_option( $user_id, 'user-settings-time', time() - 5, false );
			return;
		}
	}

	// Cookie chưa được thiết lập trong trình duyệt hiện tại hoặc giá trị đã lưu mới hơn.
	$secure = ( 'https' === parse_url( admin_url(), PHP_URL_SCHEME ) );
	setcookie( 'wp-settings-' . $user_id, $settings, time() + YEAR_IN_SECONDS, SITECOOKIEPATH, '', $secure );
	setcookie( 'wp-settings-time-' . $user_id, time(), time() + YEAR_IN_SECONDS, SITECOOKIEPATH, '', $secure );
	$_COOKIE[ 'wp-settings-' . $user_id ] = $settings;
}

/**
 * Lấy giá trị cài đặt giao diện người dùng dựa trên tên cài đặt.
 *
 * @since 2.7.0
 *
 * @param string       $name          Tên cài đặt.
 * @param string|false $default_value Tùy chọn. Giá trị mặc định trả về khi $name chưa được thiết lập. Mặc định false.
 * @return mixed Cài đặt người dùng đã lưu cuối cùng hoặc giá trị mặc định/false nếu không tồn tại.
 */
function get_user_setting( $name, $default_value = false ) {
	$all_user_settings = get_all_user_settings();

	return isset( $all_user_settings[ $name ] ) ? $all_user_settings[ $name ] : $default_value;
}

/**
 * Thêm hoặc cập nhật cài đặt giao diện người dùng.
 *
 * Cả `$name` và `$value` chỉ có thể chứa các chữ cái ASCII, số, dấu gạch ngang và dấu gạch dưới.
 *
 * Hàm này phải được sử dụng trước khi bất kỳ output nào được bắt đầu vì nó gọi `setcookie()`.
 *
 * @since 2.8.0
 *
 * @param string $name  Tên cài đặt.
 * @param string $value Giá trị cho cài đặt.
 * @return bool|null True nếu thiết lập thành công, false nếu không.
 *                   Null nếu người dùng hiện tại không phải thành viên của site.
 */
function set_user_setting( $name, $value ) {
	if ( headers_sent() ) {
		return false;
	}

	$all_user_settings          = get_all_user_settings();
	$all_user_settings[ $name ] = $value;

	return wp_set_all_user_settings( $all_user_settings );
}

/**
 * Xóa các cài đặt giao diện người dùng.
 *
 * Xóa cài đặt sẽ đặt lại chúng về giá trị mặc định.
 *
 * Hàm này phải được sử dụng trước khi bất kỳ output nào được bắt đầu vì nó gọi `setcookie()`.
 *
 * @since 2.7.0
 *
 * @param string $names Tên hoặc mảng tên của cài đặt cần xóa.
 * @return bool|null True nếu xóa thành công, false nếu không.
 *                   Null nếu người dùng hiện tại không phải thành viên của site.
 */
function delete_user_setting( $names ) {
	if ( headers_sent() ) {
		return false;
	}

	$all_user_settings = get_all_user_settings();
	$names             = (array) $names;
	$deleted           = false;

	foreach ( $names as $name ) {
		if ( isset( $all_user_settings[ $name ] ) ) {
			unset( $all_user_settings[ $name ] );
			$deleted = true;
		}
	}

	if ( $deleted ) {
		return wp_set_all_user_settings( $all_user_settings );
	}

	return false;
}

/**
 * Lấy tất cả cài đặt giao diện người dùng.
 *
 * @since 2.7.0
 *
 * @global array $_updated_user_settings
 *
 * @return array Các cài đặt người dùng đã lưu cuối cùng hoặc mảng rỗng.
 */
function get_all_user_settings() {
	global $_updated_user_settings;

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}

	if ( isset( $_updated_user_settings ) && is_array( $_updated_user_settings ) ) {
		return $_updated_user_settings;
	}

	$user_settings = array();

	if ( isset( $_COOKIE[ 'wp-settings-' . $user_id ] ) ) {
		$cookie = preg_replace( '/[^A-Za-z0-9=&_-]/', '', $_COOKIE[ 'wp-settings-' . $user_id ] );

		if ( strpos( $cookie, '=' ) ) { // '=' cannot be 1st char.
			parse_str( $cookie, $user_settings );
		}
	} else {
		$option = get_user_option( 'user-settings', $user_id );

		if ( $option && is_string( $option ) ) {
			parse_str( $option, $user_settings );
		}
	}

	$_updated_user_settings = $user_settings;
	return $user_settings;
}

/**
 * Riêng tư. Thiết lập tất cả cài đặt giao diện người dùng.
 *
 * @since 2.8.0
 * @access private
 *
 * @global array $_updated_user_settings
 *
 * @param array $user_settings Cài đặt người dùng.
 * @return bool|null True nếu thiết lập thành công, false nếu không tìm thấy người dùng hiện tại.
 *                   Null nếu người dùng hiện tại không phải thành viên của site.
 */
function wp_set_all_user_settings( $user_settings ) {
	global $_updated_user_settings;

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}

	if ( ! is_user_member_of_blog() ) {
		return;
	}

	$settings = '';
	foreach ( $user_settings as $name => $value ) {
		$_name  = preg_replace( '/[^A-Za-z0-9_-]+/', '', $name );
		$_value = preg_replace( '/[^A-Za-z0-9_-]+/', '', $value );

		if ( ! empty( $_name ) ) {
			$settings .= $_name . '=' . $_value . '&';
		}
	}

	$settings = rtrim( $settings, '&' );
	parse_str( $settings, $_updated_user_settings );

	update_user_option( $user_id, 'user-settings', $settings, false );
	update_user_option( $user_id, 'user-settings-time', time(), false );

	return true;
}

/**
 * Xóa cài đặt người dùng của người dùng hiện tại.
 *
 * @since 2.7.0
 */
function delete_all_user_settings() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	update_user_option( $user_id, 'user-settings', '', false );
	setcookie( 'wp-settings-' . $user_id, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH );
}

/**
 * Lấy giá trị tùy chọn cho mạng hiện tại dựa trên tên tùy chọn.
 *
 * @since 2.8.0
 * @since 4.4.0 Tham số `$use_cache` đã bị deprecated.
 * @since 4.4.0 Được sửa thành wrapper cho get_network_option().
 *
 * @see get_network_option()
 *
 * @param string $option        Tên tùy chọn cần lấy. Không cần SQL-escape.
 * @param mixed  $default_value Tùy chọn. Giá trị trả về nếu tùy chọn không tồn tại. Mặc định false.
 * @param bool   $deprecated    Có sử dụng cache không. Chỉ Multisite. Luôn được thiết lập là true.
 * @return mixed Giá trị được thiết lập cho tùy chọn.
 */
function get_site_option( $option, $default_value = false, $deprecated = true ) {
	return get_network_option( null, $option, $default_value );
}

/**
 * Thêm một tùy chọn mới cho mạng hiện tại.
 *
 * Các tùy chọn hiện có sẽ không bị cập nhật. Lưu ý rằng trước phiên bản 3.3 thì không phải vậy.
 *
 * @since 2.8.0
 * @since 4.4.0 Được sửa thành wrapper cho add_network_option().
 *
 * @see add_network_option()
 *
 * @param string $option Tên tùy chọn cần thêm. Không cần SQL-escape.
 * @param mixed  $value  Giá trị tùy chọn, có thể là bất kỳ kiểu nào. Không cần SQL-escape.
 * @return bool True nếu tùy chọn đã được thêm, false nếu không.
 */
function add_site_option( $option, $value ) {
	return add_network_option( null, $option, $value );
}

/**
 * Xóa một tùy chọn theo tên cho mạng hiện tại.
 *
 * @since 2.8.0
 * @since 4.4.0 Được sửa thành wrapper cho delete_network_option().
 *
 * @see delete_network_option()
 *
 * @param string $option Tên tùy chọn cần xóa. Không cần SQL-escape.
 * @return bool True nếu tùy chọn đã được xóa, false nếu không.
 */
function delete_site_option( $option ) {
	return delete_network_option( null, $option );
}

/**
 * Cập nhật giá trị của một tùy chọn đã được thêm trước đó cho mạng hiện tại.
 *
 * @since 2.8.0
 * @since 4.4.0 Được sửa thành wrapper cho update_network_option().
 *
 * @see update_network_option()
 *
 * @param string $option Tên tùy chọn. Không cần SQL-escape.
 * @param mixed  $value  Giá trị tùy chọn. Không cần SQL-escape.
 * @return bool True nếu giá trị đã được cập nhật, false nếu không.
 */
function update_site_option( $option, $value ) {
	return update_network_option( null, $option, $value );
}

/**
 * Lấy giá trị tùy chọn của mạng dựa trên tên tùy chọn.
 *
 * @since 4.4.0
 *
 * @see get_option()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|null $network_id    ID của mạng. Có thể là null để mặc định sử dụng ID mạng hiện tại.
 * @param string   $option        Tên tùy chọn cần lấy. Không cần SQL-escape.
 * @param mixed    $default_value Tùy chọn. Giá trị trả về nếu tùy chọn không tồn tại. Mặc định false.
 * @return mixed Giá trị được thiết lập cho tùy chọn.
 */
function get_network_option( $network_id, $option, $default_value = false ) {
	global $wpdb;

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return false;
	}

	$network_id = (int) $network_id;

	// Dự phòng sử dụng mạng hiện tại nếu không chỉ định ID mạng.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	/**
	 * Lọc giá trị của một tùy chọn mạng hiện có trước khi nó được lấy ra.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * Trả về giá trị khác false từ bộ lọc sẽ bỏ qua việc truy xuất
	 * và trả về giá trị đó thay thế.
	 *
	 * @since 2.9.0 Với tên 'pre_site_option_' . $key
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 * @since 4.7.0 Tham số `$network_id` được thêm vào.
	 * @since 4.9.0 Tham số `$default_value` được thêm vào.
	 *
	 * @param mixed  $pre_site_option Giá trị trả về thay vì giá trị tùy chọn. Điều này khác với
	 *                                `$default_value`, được dùng làm giá trị dự phòng trong trường hợp
	 *                                tùy chọn không tồn tại ở nơi khác trong get_network_option().
	 *                                Mặc định false (để bỏ qua short-circuit).
	 * @param string $option          Tên tùy chọn.
	 * @param int    $network_id      ID của mạng.
	 * @param mixed  $default_value   Giá trị dự phòng trả về nếu tùy chọn không tồn tại.
	 *                                Mặc định false.
	 */
	$pre = apply_filters( "pre_site_option_{$option}", false, $option, $network_id, $default_value );

	if ( false !== $pre ) {
		return $pre;
	}

	// Ngăn các tùy chọn không tồn tại kích hoạt nhiều truy vấn.
	$notoptions_key = "$network_id:notoptions";
	$notoptions     = wp_cache_get( $notoptions_key, 'site-options' );

	if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {

		/**
		 * Lọc giá trị mặc định của một tùy chọn mạng cụ thể.
		 *
		 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
		 *
		 * @since 3.4.0
		 * @since 4.4.0 Tham số `$option` được thêm vào.
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param mixed  $default_value Giá trị trả về nếu tùy chọn site không tồn tại
		 *                              trong cơ sở dữ liệu.
		 * @param string $option        Tên tùy chọn.
		 * @param int    $network_id    ID của mạng.
		 */
		return apply_filters( "default_site_option_{$option}", $default_value, $option, $network_id );
	}

	if ( ! is_multisite() ) {
		/** This filter is documented in wp-includes/option.php */
		$default_value = apply_filters( 'default_site_option_' . $option, $default_value, $option, $network_id );
		$value         = get_option( $option, $default_value );
	} else {
		$cache_key = "$network_id:$option";
		$value     = wp_cache_get( $cache_key, 'site-options' );

		if ( ! isset( $value ) || false === $value ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );

			// Phải dùng get_row() thay vì get_var() vì sự bất thường với các giá trị 0, false, null.
			if ( is_object( $row ) ) {
				$value = $row->meta_value;
				$value = maybe_unserialize( $value );
				wp_cache_set( $cache_key, $value, 'site-options' );
			} else {
				if ( ! is_array( $notoptions ) ) {
					$notoptions = array();
				}

				$notoptions[ $option ] = true;
				wp_cache_set( $notoptions_key, $notoptions, 'site-options' );

				/** This filter is documented in wp-includes/option.php */
				$value = apply_filters( 'default_site_option_' . $option, $default_value, $option, $network_id );
			}
		}
	}

	if ( ! is_array( $notoptions ) ) {
		$notoptions = array();
		wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
	}

	/**
	 * Lọc giá trị của một tùy chọn mạng hiện có.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 2.9.0 Với tên 'site_option_' . $key
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 * @since 4.7.0 Tham số `$network_id` được thêm vào.
	 *
	 * @param mixed  $value      Giá trị tùy chọn mạng.
	 * @param string $option     Tên tùy chọn.
	 * @param int    $network_id ID của mạng.
	 */
	return apply_filters( "site_option_{$option}", $value, $option, $network_id );
}

/**
 * Thêm một tùy chọn mạng mới.
 *
 * Các tùy chọn hiện có sẽ không bị cập nhật.
 *
 * @since 4.4.0
 *
 * @see add_option()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|null $network_id ID của mạng. Có thể là null để mặc định sử dụng ID mạng hiện tại.
 * @param string   $option     Tên tùy chọn cần thêm. Không cần SQL-escape.
 * @param mixed    $value      Giá trị tùy chọn, có thể là bất kỳ kiểu nào. Không cần SQL-escape.
 * @return bool True nếu tùy chọn đã được thêm, false nếu không.
 */
function add_network_option( $network_id, $option, $value ) {
	global $wpdb;

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return false;
	}

	$network_id = (int) $network_id;

	// Dự phòng sử dụng mạng hiện tại nếu không chỉ định ID mạng.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	wp_protect_special_option( $option );

	/**
	 * Lọc giá trị của một tùy chọn mạng cụ thể trước khi nó được thêm.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 2.9.0 Với tên 'pre_add_site_option_' . $key
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 * @since 4.7.0 Tham số `$network_id` được thêm vào.
	 *
	 * @param mixed  $value      Giá trị tùy chọn mạng.
	 * @param string $option     Tên tùy chọn.
	 * @param int    $network_id ID của mạng.
	 */
	$value = apply_filters( "pre_add_site_option_{$option}", $value, $option, $network_id );

	$notoptions_key = "$network_id:notoptions";

	if ( ! is_multisite() ) {
		$result = add_option( $option, $value, '', false );
	} else {
		$cache_key = "$network_id:$option";

		/*
		 * Đảm bảo tùy chọn chưa tồn tại.
		 * Chúng ta có thể kiểm tra cache 'notoptions' trước khi thực hiện truy vấn DB.
		 */
		$notoptions = wp_cache_get( $notoptions_key, 'site-options' );

		if ( ! is_array( $notoptions ) || ! isset( $notoptions[ $option ] ) ) {
			if ( false !== get_network_option( $network_id, $option, false ) ) {
				return false;
			}
		}

		$value = sanitize_option( $option, $value );

		$serialized_value = maybe_serialize( $value );
		$result           = $wpdb->insert(
			$wpdb->sitemeta,
			array(
				'site_id'    => $network_id,
				'meta_key'   => $option,
				'meta_value' => $serialized_value,
			)
		);

		if ( ! $result ) {
			return false;
		}

		wp_cache_set( $cache_key, $value, 'site-options' );

		// Tùy chọn này đã tồn tại.
		$notoptions = wp_cache_get( $notoptions_key, 'site-options' ); // Vâng, lại lần nữa... chúng ta cần nó được cập nhật mới.

		if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
			unset( $notoptions[ $option ] );
			wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi một tùy chọn mạng cụ thể đã được thêm thành công.
		 *
		 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
		 *
		 * @since 2.9.0 Với tên "add_site_option_{$key}"
		 * @since 3.0.0
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param string $option     Tên tùy chọn mạng.
		 * @param mixed  $value      Giá trị tùy chọn mạng.
		 * @param int    $network_id ID của mạng.
		 */
		do_action( "add_site_option_{$option}", $option, $value, $network_id );

		/**
		 * Kích hoạt sau khi một tùy chọn mạng đã được thêm thành công.
		 *
		 * @since 3.0.0
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param string $option     Tên tùy chọn mạng.
		 * @param mixed  $value      Giá trị tùy chọn mạng.
		 * @param int    $network_id ID của mạng.
		 */
		do_action( 'add_site_option', $option, $value, $network_id );

		return true;
	}

	return false;
}

/**
 * Xóa một tùy chọn mạng theo tên.
 *
 * @since 4.4.0
 *
 * @see delete_option()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|null $network_id ID của mạng. Có thể là null để mặc định sử dụng ID mạng hiện tại.
 * @param string   $option     Tên tùy chọn cần xóa. Không cần SQL-escape.
 * @return bool True nếu tùy chọn đã được xóa, false nếu không.
 */
function delete_network_option( $network_id, $option ) {
	global $wpdb;

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return false;
	}

	$network_id = (int) $network_id;

	// Dự phòng sử dụng mạng hiện tại nếu không chỉ định ID mạng.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	/**
	 * Kích hoạt ngay trước khi một tùy chọn mạng cụ thể bị xóa.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 * @since 4.7.0 Tham số `$network_id` được thêm vào.
	 *
	 * @param string $option     Tên tùy chọn.
	 * @param int    $network_id ID của mạng.
	 */
	do_action( "pre_delete_site_option_{$option}", $option, $network_id );

	if ( ! is_multisite() ) {
		$result = delete_option( $option );
	} else {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->sitemeta} WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );
		if ( is_null( $row ) || ! $row->meta_id ) {
			return false;
		}
		$cache_key = "$network_id:$option";
		wp_cache_delete( $cache_key, 'site-options' );

		$result = $wpdb->delete(
			$wpdb->sitemeta,
			array(
				'meta_key' => $option,
				'site_id'  => $network_id,
			)
		);

		if ( $result ) {
			$notoptions_key = "$network_id:notoptions";
			$notoptions     = wp_cache_get( $notoptions_key, 'site-options' );

			if ( ! is_array( $notoptions ) ) {
				$notoptions = array();
			}
			$notoptions[ $option ] = true;
			wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi một tùy chọn mạng cụ thể đã bị xóa.
		 *
		 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
		 *
		 * @since 2.9.0 Với tên "delete_site_option_{$key}"
		 * @since 3.0.0
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param string $option     Tên tùy chọn mạng.
		 * @param int    $network_id ID của mạng.
		 */
		do_action( "delete_site_option_{$option}", $option, $network_id );

		/**
		 * Kích hoạt sau khi một tùy chọn mạng đã bị xóa.
		 *
		 * @since 3.0.0
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param string $option     Tên tùy chọn mạng.
		 * @param int    $network_id ID của mạng.
		 */
		do_action( 'delete_site_option', $option, $network_id );

		return true;
	}

	return false;
}

/**
 * Cập nhật giá trị của một tùy chọn mạng đã được thêm trước đó.
 *
 * @since 4.4.0
 *
 * @see update_option()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|null $network_id ID của mạng. Có thể là null để mặc định sử dụng ID mạng hiện tại.
 * @param string   $option     Tên tùy chọn. Không cần SQL-escape.
 * @param mixed    $value      Giá trị tùy chọn. Không cần SQL-escape.
 * @return bool True nếu giá trị đã được cập nhật, false nếu không.
 */
function update_network_option( $network_id, $option, $value ) {
	global $wpdb;

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return false;
	}

	$network_id = (int) $network_id;

	// Dự phòng sử dụng mạng hiện tại nếu không chỉ định ID mạng.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	wp_protect_special_option( $option );

	$old_value = get_network_option( $network_id, $option );

	/**
	 * Lọc một tùy chọn mạng cụ thể trước khi giá trị của nó được cập nhật.
	 *
	 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
	 *
	 * @since 2.9.0 Với tên 'pre_update_site_option_' . $key
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$option` được thêm vào.
	 * @since 4.7.0 Tham số `$network_id` được thêm vào.
	 *
	 * @param mixed  $value      Giá trị mới của tùy chọn mạng.
	 * @param mixed  $old_value  Giá trị cũ của tùy chọn mạng.
	 * @param string $option     Tên tùy chọn.
	 * @param int    $network_id ID của mạng.
	 */
	$value = apply_filters( "pre_update_site_option_{$option}", $value, $old_value, $option, $network_id );

	/*
	 * Nếu giá trị mới và cũ giống nhau, không cần cập nhật.
	 *
	 * Các giá trị chưa serialize sẽ đủ trong hầu hết trường hợp. Nếu dữ liệu chưa serialize
	 * khác nhau, dữ liệu (có thể) đã serialize sẽ được kiểm tra để tránh
	 * các truy vấn cơ sở dữ liệu không cần thiết cho các instance đối tượng giống hệt nhau.
	 *
	 * Xem https://core.trac.wordpress.org/ticket/44956
	 */
	if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
		return false;
	}

	if ( false === $old_value ) {
		return add_network_option( $network_id, $option, $value );
	}

	$notoptions_key = "$network_id:notoptions";
	$notoptions     = wp_cache_get( $notoptions_key, 'site-options' );

	if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
		unset( $notoptions[ $option ] );
		wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
	}

	if ( ! is_multisite() ) {
		$result = update_option( $option, $value, false );
	} else {
		$value = sanitize_option( $option, $value );

		$serialized_value = maybe_serialize( $value );
		$result           = $wpdb->update(
			$wpdb->sitemeta,
			array( 'meta_value' => $serialized_value ),
			array(
				'site_id'  => $network_id,
				'meta_key' => $option,
			)
		);

		if ( $result ) {
			$cache_key = "$network_id:$option";
			wp_cache_set( $cache_key, $value, 'site-options' );
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi giá trị của một tùy chọn mạng cụ thể đã được cập nhật thành công.
		 *
		 * Phần động của tên hook, `$option`, đề cập đến tên tùy chọn.
		 *
		 * @since 2.9.0 Với tên "update_site_option_{$key}"
		 * @since 3.0.0
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param string $option     Tên tùy chọn mạng.
		 * @param mixed  $value      Giá trị hiện tại của tùy chọn mạng.
		 * @param mixed  $old_value  Giá trị cũ của tùy chọn mạng.
		 * @param int    $network_id ID của mạng.
		 */
		do_action( "update_site_option_{$option}", $option, $value, $old_value, $network_id );

		/**
		 * Kích hoạt sau khi giá trị của một tùy chọn mạng đã được cập nhật thành công.
		 *
		 * @since 3.0.0
		 * @since 4.7.0 Tham số `$network_id` được thêm vào.
		 *
		 * @param string $option     Tên tùy chọn mạng.
		 * @param mixed  $value      Giá trị hiện tại của tùy chọn mạng.
		 * @param mixed  $old_value  Giá trị cũ của tùy chọn mạng.
		 * @param int    $network_id ID của mạng.
		 */
		do_action( 'update_site_option', $option, $value, $old_value, $network_id );

		return true;
	}

	return false;
}

/**
 * Xóa một site transient.
 *
 * @since 2.9.0
 *
 * @param string $transient Tên transient. Không cần SQL-escape.
 * @return bool True nếu transient đã được xóa, false nếu không.
 */
function delete_site_transient( $transient ) {

	/**
	 * Kích hoạt ngay trước khi một site transient cụ thể bị xóa.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 3.0.0
	 *
	 * @param string $transient Tên transient.
	 */
	do_action( "delete_site_transient_{$transient}", $transient );

	if ( wp_using_ext_object_cache() || wp_installing() ) {
		$result = wp_cache_delete( $transient, 'site-transient' );
	} else {
		$option_timeout = '_site_transient_timeout_' . $transient;
		$option         = '_site_transient_' . $transient;
		$result         = delete_site_option( $option );

		if ( $result ) {
			delete_site_option( $option_timeout );
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi một site transient bị xóa.
		 *
		 * @since 3.0.0
		 *
		 * @param string $transient Tên transient đã bị xóa.
		 */
		do_action( 'deleted_site_transient', $transient );
	}

	return $result;
}

/**
 * Lấy giá trị của một site transient.
 *
 * Nếu transient không tồn tại, không có giá trị, hoặc đã hết hạn,
 * thì giá trị trả về sẽ là false.
 *
 * @since 2.9.0
 *
 * @see get_transient()
 *
 * @param string $transient Tên transient. Không cần SQL-escape.
 * @return mixed Giá trị của transient.
 */
function get_site_transient( $transient ) {

	/**
	 * Lọc giá trị của một site transient hiện có trước khi nó được lấy ra.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * Trả về giá trị khác boolean false sẽ bỏ qua việc truy xuất và
	 * trả về giá trị đó thay thế.
	 *
	 * @since 2.9.0
	 * @since 4.4.0 Tham số `$transient` được thêm vào.
	 *
	 * @param mixed  $pre_site_transient Giá trị mặc định trả về nếu site transient không tồn tại.
	 *                                   Bất kỳ giá trị nào khác false sẽ bỏ qua việc truy xuất
	 *                                   transient và trả về giá trị đó.
	 * @param string $transient          Tên transient.
	 */
	$pre = apply_filters( "pre_site_transient_{$transient}", false, $transient );

	if ( false !== $pre ) {
		return $pre;
	}

	if ( wp_using_ext_object_cache() || wp_installing() ) {
		$value = wp_cache_get( $transient, 'site-transient' );
	} else {
		// Các transient lõi không có thời gian hết hạn. Liệt kê ở đây để có thể tránh truy vấn timeout.
		$no_timeout       = array( 'update_core', 'update_plugins', 'update_themes' );
		$transient_option = '_site_transient_' . $transient;
		if ( ! in_array( $transient, $no_timeout, true ) ) {
			$transient_timeout = '_site_transient_timeout_' . $transient;
			wp_prime_site_option_caches( array( $transient_option, $transient_timeout ) );

			$timeout = get_site_option( $transient_timeout );
			if ( false !== $timeout && $timeout < time() ) {
				delete_site_option( $transient_option );
				delete_site_option( $transient_timeout );
				$value = false;
			}
		}

		if ( ! isset( $value ) ) {
			$value = get_site_option( $transient_option );
		}
	}

	/**
	 * Lọc giá trị của một site transient hiện có.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 2.9.0
	 * @since 4.4.0 Tham số `$transient` được thêm vào.
	 *
	 * @param mixed  $value     Giá trị site transient.
	 * @param string $transient Tên transient.
	 */
	return apply_filters( "site_transient_{$transient}", $value, $transient );
}

/**
 * Thiết lập/cập nhật giá trị của một site transient.
 *
 * Bạn không cần serialize giá trị. Nếu giá trị cần được serialize,
 * nó sẽ được serialize trước khi thiết lập.
 *
 * @since 2.9.0
 *
 * @see set_transient()
 *
 * @param string $transient  Tên transient. Không cần SQL-escape. Phải có
 *                           167 ký tự hoặc ít hơn.
 * @param mixed  $value      Giá trị transient. Không cần SQL-escape.
 * @param int    $expiration Tùy chọn. Thời gian cho đến khi hết hạn tính bằng giây. Mặc định 0 (không hết hạn).
 * @return bool True nếu giá trị đã được thiết lập, false nếu không.
 */
function set_site_transient( $transient, $value, $expiration = 0 ) {

	/**
	 * Lọc giá trị của một site transient cụ thể trước khi nó được thiết lập.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 3.0.0
	 * @since 4.4.0 Tham số `$transient` được thêm vào.
	 *
	 * @param mixed  $value     Giá trị mới của site transient.
	 * @param string $transient Tên transient.
	 */
	$value = apply_filters( "pre_set_site_transient_{$transient}", $value, $transient );

	$expiration = (int) $expiration;

	/**
	 * Lọc thời gian hết hạn cho một site transient trước khi giá trị của nó được thiết lập.
	 *
	 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
	 *
	 * @since 4.4.0
	 *
	 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây. Dùng 0 để không hết hạn.
	 * @param mixed  $value      Giá trị mới của site transient.
	 * @param string $transient  Tên transient.
	 */
	$expiration = apply_filters( "expiration_of_site_transient_{$transient}", $expiration, $value, $transient );

	if ( wp_using_ext_object_cache() || wp_installing() ) {
		$result = wp_cache_set( $transient, $value, 'site-transient', $expiration );
	} else {
		$transient_timeout = '_site_transient_timeout_' . $transient;
		$option            = '_site_transient_' . $transient;
		wp_prime_site_option_caches( array( $option, $transient_timeout ) );

		if ( false === get_site_option( $option ) ) {
			if ( $expiration ) {
				add_site_option( $transient_timeout, time() + $expiration );
			}
			$result = add_site_option( $option, $value );
		} else {
			if ( $expiration ) {
				update_site_option( $transient_timeout, time() + $expiration );
			}
			$result = update_site_option( $option, $value );
		}
	}

	if ( $result ) {

		/**
		 * Kích hoạt sau khi giá trị của một site transient cụ thể đã được thiết lập.
		 *
		 * Phần động của tên hook, `$transient`, đề cập đến tên transient.
		 *
		 * @since 3.0.0
		 * @since 4.4.0 Tham số `$transient` được thêm vào.
		 *
		 * @param mixed  $value      Giá trị site transient.
		 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
		 * @param string $transient  Tên transient.
		 */
		do_action( "set_site_transient_{$transient}", $value, $expiration, $transient );

		/**
		 * Kích hoạt sau khi giá trị của một site transient đã được thiết lập.
		 *
		 * @since 6.8.0
		 *
		 * @param string $transient  Tên site transient.
		 * @param mixed  $value      Giá trị site transient.
		 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
		 */
		do_action( 'set_site_transient', $transient, $value, $expiration );

		/**
		 * Kích hoạt sau khi giá trị của một site transient đã được thiết lập.
		 *
		 * @since 3.0.0
		 * @deprecated 6.8.0 Sử dụng {@see 'set_site_transient'} thay thế.
		 *
		 * @param string $transient  Tên site transient.
		 * @param mixed  $value      Giá trị site transient.
		 * @param int    $expiration Thời gian cho đến khi hết hạn tính bằng giây.
		 */
		do_action_deprecated( 'setted_site_transient', array( $transient, $value, $expiration ), '6.8.0', 'set_site_transient' );
	}

	return $result;
}

/**
 * Đăng ký các cài đặt mặc định có sẵn trong WordPress.
 *
 * Các cài đặt được đăng ký ở đây chủ yếu hữu ích cho REST API, vì vậy
 * không bao gồm tất cả các cài đặt có sẵn trong WordPress.
 *
 * @since 4.7.0
 * @since 6.0.1 Các tùy chọn `show_on_front`, `page_on_front`, và `page_for_posts` được thêm vào.
 */
function register_initial_settings() {
	register_setting(
		'general',
		'blogname',
		array(
			'show_in_rest' => array(
				'name' => 'title',
			),
			'type'         => 'string',
			'label'        => __( 'Title' ),
			'description'  => __( 'Site title.' ),
		)
	);

	register_setting(
		'general',
		'blogdescription',
		array(
			'show_in_rest' => array(
				'name' => 'description',
			),
			'type'         => 'string',
			'label'        => __( 'Tagline' ),
			'description'  => __( 'Site tagline.' ),
		)
	);

	if ( ! is_multisite() ) {
		register_setting(
			'general',
			'siteurl',
			array(
				'show_in_rest' => array(
					'name'   => 'url',
					'schema' => array(
						'format' => 'uri',
					),
				),
				'type'         => 'string',
				'description'  => __( 'Site URL.' ),
			)
		);
	}

	if ( ! is_multisite() ) {
		register_setting(
			'general',
			'admin_email',
			array(
				'show_in_rest' => array(
					'name'   => 'email',
					'schema' => array(
						'format' => 'email',
					),
				),
				'type'         => 'string',
				'description'  => __( 'This address is used for admin purposes, like new user notification.' ),
			)
		);
	}

	register_setting(
		'general',
		'timezone_string',
		array(
			'show_in_rest' => array(
				'name' => 'timezone',
			),
			'type'         => 'string',
			'description'  => __( 'A city in the same timezone as you.' ),
		)
	);

	register_setting(
		'general',
		'date_format',
		array(
			'show_in_rest' => true,
			'type'         => 'string',
			'description'  => __( 'A date format for all date strings.' ),
		)
	);

	register_setting(
		'general',
		'time_format',
		array(
			'show_in_rest' => true,
			'type'         => 'string',
			'description'  => __( 'A time format for all time strings.' ),
		)
	);

	register_setting(
		'general',
		'start_of_week',
		array(
			'show_in_rest' => true,
			'type'         => 'integer',
			'description'  => __( 'A day number of the week that the week should start on.' ),
		)
	);

	register_setting(
		'general',
		'WPLANG',
		array(
			'show_in_rest' => array(
				'name' => 'language',
			),
			'type'         => 'string',
			'description'  => __( 'WordPress locale code.' ),
			'default'      => 'en_US',
		)
	);

	register_setting(
		'writing',
		'use_smilies',
		array(
			'show_in_rest' => true,
			'type'         => 'boolean',
			'description'  => __( 'Convert emoticons like :-) and :-P to graphics on display.' ),
			'default'      => true,
		)
	);

	register_setting(
		'writing',
		'default_category',
		array(
			'show_in_rest' => true,
			'type'         => 'integer',
			'description'  => __( 'Default post category.' ),
		)
	);

	register_setting(
		'writing',
		'default_post_format',
		array(
			'show_in_rest' => true,
			'type'         => 'string',
			'description'  => __( 'Default post format.' ),
		)
	);

	register_setting(
		'reading',
		'posts_per_page',
		array(
			'show_in_rest' => true,
			'type'         => 'integer',
			'label'        => __( 'Maximum posts per page' ),
			'description'  => __( 'Blog pages show at most.' ),
			'default'      => 10,
		)
	);

	register_setting(
		'reading',
		'show_on_front',
		array(
			'show_in_rest' => true,
			'type'         => 'string',
			'label'        => __( 'Show on front' ),
			'description'  => __( 'What to show on the front page' ),
		)
	);

	register_setting(
		'reading',
		'page_on_front',
		array(
			'show_in_rest' => true,
			'type'         => 'integer',
			'label'        => __( 'Page on front' ),
			'description'  => __( 'The ID of the page that should be displayed on the front page' ),
		)
	);

	register_setting(
		'reading',
		'page_for_posts',
		array(
			'show_in_rest' => true,
			'type'         => 'integer',
			'description'  => __( 'The ID of the page that should display the latest posts' ),
		)
	);

	register_setting(
		'discussion',
		'default_ping_status',
		array(
			'show_in_rest' => array(
				'schema' => array(
					'enum' => array( 'open', 'closed' ),
				),
			),
			'type'         => 'string',
			'description'  => __( 'Allow link notifications from other blogs (pingbacks and trackbacks) on new articles.' ),
		)
	);

	register_setting(
		'discussion',
		'default_comment_status',
		array(
			'show_in_rest' => array(
				'schema' => array(
					'enum' => array( 'open', 'closed' ),
				),
			),
			'type'         => 'string',
			'label'        => __( 'Allow comments on new posts' ),
			'description'  => __( 'Allow people to submit comments on new posts.' ),
		)
	);
}

/**
 * Đăng ký một cài đặt và dữ liệu của nó.
 *
 * @since 2.7.0
 * @since 3.0.0 Nhóm tùy chọn `misc` đã bị deprecated.
 * @since 3.5.0 Nhóm tùy chọn `privacy` đã bị deprecated.
 * @since 4.7.0 `$args` có thể được truyền để thiết lập cờ cho cài đặt, tương tự như `register_meta()`.
 * @since 5.5.0 `$new_whitelist_options` được đổi tên thành `$new_allowed_options`.
 *              Hãy cân nhắc viết code bao dung hơn.
 * @since 6.6.0 Thêm tham số `label`.
 *
 * @global array $new_allowed_options
 * @global array $wp_registered_settings
 *
 * @param string $option_group Tên nhóm cài đặt. Nên tương ứng với tên khóa tùy chọn được phép.
 *                             Các tên khóa tùy chọn được phép mặc định bao gồm 'general', 'discussion', 'media',
 *                             'reading', 'writing', và 'options'.
 * @param string $option_name Tên tùy chọn cần làm sạch và lưu.
 * @param array  $args {
 *     Dữ liệu dùng để mô tả cài đặt khi đăng ký.
 *
 *     @type string     $type              Kiểu dữ liệu liên kết với cài đặt này.
 *                                         Các giá trị hợp lệ là 'string', 'boolean', 'integer', 'number', 'array', và 'object'.
 *     @type string     $label             Nhãn của dữ liệu gắn với cài đặt này.
 *     @type string     $description       Mô tả của dữ liệu gắn với cài đặt này.
 *     @type callable   $sanitize_callback Hàm callback để làm sạch giá trị tùy chọn.
 *     @type bool|array $show_in_rest      Dữ liệu liên kết với cài đặt này có nên được bao gồm trong REST API không.
 *                                         Khi đăng ký cài đặt phức tạp, tham số này có thể tùy chọn là
 *                                         mảng với khóa 'schema'.
 *     @type mixed      $default           Giá trị mặc định khi gọi `get_option()`.
 * }
 */
function register_setting( $option_group, $option_name, $args = array() ) {
	global $new_allowed_options, $wp_registered_settings;

	/*
	 * In 5.5.0, the `$new_whitelist_options` global variable was renamed to `$new_allowed_options`.
	 * Please consider writing more inclusive code.
	 */
	$GLOBALS['new_whitelist_options'] = &$new_allowed_options;

	$defaults = array(
		'type'              => 'string',
		'group'             => $option_group,
		'label'             => '',
		'description'       => '',
		'sanitize_callback' => null,
		'show_in_rest'      => false,
	);

	// Tương thích ngược: callback sanitize cũ được thêm vào.
	if ( is_callable( $args ) ) {
		$args = array(
			'sanitize_callback' => $args,
		);
	}

	/**
	 * Lọc các tham số đăng ký khi đăng ký một cài đặt.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $args         Mảng các tham số đăng ký cài đặt.
	 * @param array  $defaults     Mảng các tham số mặc định.
	 * @param string $option_group Nhóm cài đặt.
	 * @param string $option_name  Tên cài đặt.
	 */
	$args = apply_filters( 'register_setting_args', $args, $defaults, $option_group, $option_name );

	$args = wp_parse_args( $args, $defaults );

	// Yêu cầu schema cho phần tử khi đăng ký cài đặt với kiểu mảng.
	if ( false !== $args['show_in_rest'] && 'array' === $args['type'] && ( ! is_array( $args['show_in_rest'] ) || ! isset( $args['show_in_rest']['schema']['items'] ) ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'When registering an "array" setting to show in the REST API, you must specify the schema for each array item in "show_in_rest.schema.items".' ), '5.4.0' );
	}

	if ( ! is_array( $wp_registered_settings ) ) {
		$wp_registered_settings = array();
	}

	if ( 'misc' === $option_group ) {
		_deprecated_argument(
			__FUNCTION__,
			'3.0.0',
			sprintf(
				/* translators: %s: misc */
				__( 'The "%s" options group has been removed. Use another settings group.' ),
				'misc'
			)
		);
		$option_group = 'general';
	}

	if ( 'privacy' === $option_group ) {
		_deprecated_argument(
			__FUNCTION__,
			'3.5.0',
			sprintf(
				/* translators: %s: privacy */
				__( 'The "%s" options group has been removed. Use another settings group.' ),
				'privacy'
			)
		);
		$option_group = 'reading';
	}

	$new_allowed_options[ $option_group ][] = $option_name;

	if ( ! empty( $args['sanitize_callback'] ) ) {
		add_filter( "sanitize_option_{$option_name}", $args['sanitize_callback'] );
	}
	if ( array_key_exists( 'default', $args ) ) {
		add_filter( "default_option_{$option_name}", 'filter_default_option', 10, 3 );
	}

	/**
	 * Fires immediately before the setting is registered but after its filters are in place.
	 *
	 * @since 5.5.0
	 *
	 * @param string $option_group Setting group.
	 * @param string $option_name  Setting name.
	 * @param array  $args         Array of setting registration arguments.
	 */
	do_action( 'register_setting', $option_group, $option_name, $args );

	$wp_registered_settings[ $option_name ] = $args;
}

/**
 * Unregisters a setting.
 *
 * @since 2.7.0
 * @since 4.7.0 `$sanitize_callback` was deprecated. The callback from `register_setting()` is now used instead.
 * @since 5.5.0 `$new_whitelist_options` was renamed to `$new_allowed_options`.
 *              Please consider writing more inclusive code.
 *
 * @global array $new_allowed_options
 * @global array $wp_registered_settings
 *
 * @param string   $option_group The settings group name used during registration.
 * @param string   $option_name  The name of the option to unregister.
 * @param callable $deprecated   Optional. Deprecated.
 */
function unregister_setting( $option_group, $option_name, $deprecated = '' ) {
	global $new_allowed_options, $wp_registered_settings;

	/*
	 * In 5.5.0, the `$new_whitelist_options` global variable was renamed to `$new_allowed_options`.
	 * Please consider writing more inclusive code.
	 */
	$GLOBALS['new_whitelist_options'] = &$new_allowed_options;

	if ( 'misc' === $option_group ) {
		_deprecated_argument(
			__FUNCTION__,
			'3.0.0',
			sprintf(
				/* translators: %s: misc */
				__( 'The "%s" options group has been removed. Use another settings group.' ),
				'misc'
			)
		);
		$option_group = 'general';
	}

	if ( 'privacy' === $option_group ) {
		_deprecated_argument(
			__FUNCTION__,
			'3.5.0',
			sprintf(
				/* translators: %s: privacy */
				__( 'The "%s" options group has been removed. Use another settings group.' ),
				'privacy'
			)
		);
		$option_group = 'reading';
	}

	$pos = false;
	if ( isset( $new_allowed_options[ $option_group ] ) ) {
		$pos = array_search( $option_name, (array) $new_allowed_options[ $option_group ], true );
	}

	if ( false !== $pos ) {
		unset( $new_allowed_options[ $option_group ][ $pos ] );
	}

	if ( '' !== $deprecated ) {
		_deprecated_argument(
			__FUNCTION__,
			'4.7.0',
			sprintf(
				/* translators: 1: $sanitize_callback, 2: register_setting() */
				__( '%1$s is deprecated. The callback from %2$s is used instead.' ),
				'<code>$sanitize_callback</code>',
				'<code>register_setting()</code>'
			)
		);
		remove_filter( "sanitize_option_{$option_name}", $deprecated );
	}

	if ( isset( $wp_registered_settings[ $option_name ] ) ) {
		// Remove the sanitize callback if one was set during registration.
		if ( ! empty( $wp_registered_settings[ $option_name ]['sanitize_callback'] ) ) {
			remove_filter( "sanitize_option_{$option_name}", $wp_registered_settings[ $option_name ]['sanitize_callback'] );
		}

		// Remove the default filter if a default was provided during registration.
		if ( array_key_exists( 'default', $wp_registered_settings[ $option_name ] ) ) {
			remove_filter( "default_option_{$option_name}", 'filter_default_option', 10 );
		}

		/**
		 * Fires immediately before the setting is unregistered and after its filters have been removed.
		 *
		 * @since 5.5.0
		 *
		 * @param string $option_group Setting group.
		 * @param string $option_name  Setting name.
		 */
		do_action( 'unregister_setting', $option_group, $option_name );

		unset( $wp_registered_settings[ $option_name ] );
	}
}

/**
 * Retrieves an array of registered settings.
 *
 * @since 4.7.0
 *
 * @global array $wp_registered_settings
 *
 * @return array List of registered settings, keyed by option name.
 */
function get_registered_settings() {
	global $wp_registered_settings;

	if ( ! is_array( $wp_registered_settings ) ) {
		return array();
	}

	return $wp_registered_settings;
}

/**
 * Filters the default value for the option.
 *
 * For settings which register a default setting in `register_setting()`, this
 * function is added as a filter to `default_option_{$option}`.
 *
 * @since 4.7.0
 *
 * @param mixed  $default_value  Existing default value to return.
 * @param string $option         Option name.
 * @param bool   $passed_default Was `get_option()` passed a default value?
 * @return mixed Filtered default value.
 */
function filter_default_option( $default_value, $option, $passed_default ) {
	if ( $passed_default ) {
		return $default_value;
	}

	$registered = get_registered_settings();
	if ( empty( $registered[ $option ] ) ) {
		return $default_value;
	}

	return $registered[ $option ]['default'];
}

/**
 * Returns the values that trigger autoloading from the options table.
 *
 * @since 6.6.0
 *
 * @return string[] The values that trigger autoloading.
 */
function wp_autoload_values_to_autoload() {
	$autoload_values = array( 'yes', 'on', 'auto-on', 'auto' );

	/**
	 * Filters the autoload values that should be considered for autoloading from the options table.
	 *
	 * The filter can only be used to remove autoload values from the default list.
	 *
	 * @since 6.6.0
	 *
	 * @param string[] $autoload_values Autoload values used to autoload option.
	 *                               Default list contains 'yes', 'on', 'auto-on', and 'auto'.
	 */
	$filtered_values = apply_filters( 'wp_autoload_values_to_autoload', $autoload_values );

	return array_intersect( $filtered_values, $autoload_values );
}
