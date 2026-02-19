<?php
/**
 * API Plugin nằm trong file này, cho phép tạo các action
 * và filter, đồng thời hook các hàm và phương thức. Các hàm hoặc phương thức
 * sẽ được thực thi khi action hoặc filter được gọi.
 *
 * Các ví dụ callback API tham chiếu đến hàm, nhưng có thể là phương thức của lớp.
 * Để hook phương thức, bạn cần truyền một mảng theo một trong hai cách.
 *
 * Bất kỳ cú pháp nào được giải thích trong tài liệu PHP cho kiểu
 * {@link https://www.php.net/manual/en/language.pseudo-types.php#language.types.callback 'callback'}
 * đều hợp lệ.
 *
 * Xem thêm {@link https://developer.wordpress.org/plugins/ Plugin API} để biết
 * thêm thông tin và ví dụ về cách sử dụng các hàm này.
 *
 * File này không nên có bất kỳ phụ thuộc bên ngoài nào.
 *
 * @package WordPress
 * @subpackage Plugin
 * @since 1.5.0
 */

// Khởi tạo các biến toàn cục filter.
require __DIR__ . '/class-wp-hook.php';

/** @var WP_Hook[] $wp_filter */
global $wp_filter;

/** @var int[] $wp_actions */
global $wp_actions;

/** @var int[] $wp_filters */
global $wp_filters;

/** @var string[] $wp_current_filter */
global $wp_current_filter;

if ( $wp_filter ) {
	$wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
} else {
	$wp_filter = array();
}

if ( ! isset( $wp_actions ) ) {
	$wp_actions = array();
}

if ( ! isset( $wp_filters ) ) {
	$wp_filters = array();
}

if ( ! isset( $wp_current_filter ) ) {
	$wp_current_filter = array();
}

/**
 * Thêm một hàm callback vào hook filter.
 *
 * WordPress cung cấp các hook filter để cho phép plugin thay đổi
 * nhiều loại dữ liệu nội bộ khác nhau tại thời điểm chạy.
 *
 * Plugin có thể thay đổi dữ liệu bằng cách gắn callback vào hook filter. Khi filter
 * được áp dụng sau đó, mỗi callback đã gắn sẽ chạy theo thứ tự ưu tiên, và được
 * cơ hội thay đổi giá trị bằng cách trả về giá trị mới.
 *
 * Ví dụ sau cho thấy cách gắn hàm callback vào hook filter.
 *
 * Lưu ý rằng `$example` được truyền vào callback, (có thể) được thay đổi, sau đó được trả về:
 *
 *     function example_callback( $example ) {
 *         // Có thể thay đổi $example theo cách nào đó.
 *         return $example;
 *     }
 *     add_filter( 'example_filter', 'example_callback' );
 *
 * Các callback đã gắn có thể nhận từ không đến tổng số đối số được truyền dưới dạng tham số
 * trong lời gọi apply_filters() tương ứng.
 *
 * Nói cách khác, nếu lời gọi apply_filters() truyền bốn đối số, các callback gắn vào
 * nó có thể nhận không (tương đương với 1) hoặc tối đa bốn đối số. Điều quan trọng là
 * giá trị `$accepted_args` phải phản ánh số đối số mà callback *thực sự*
 * chọn nhận. Nếu không có đối số nào được callback nhận thì được coi là
 * tương đương với nhận 1 đối số. Ví dụ:
 *
 *     // Lời gọi filter.
 *     $value = apply_filters( 'hook', $value, $arg2, $arg3 );
 *
 *     // Nhận không/một đối số.
 *     function example_callback() {
 *         ...
 *         return 'some value';
 *     }
 *     add_filter( 'hook', 'example_callback' ); // Trong đó $priority mặc định 10, $accepted_args mặc định 1.
 *
 *     // Nhận hai đối số (có thể ba).
 *     function example_callback( $value, $arg2 ) {
 *         ...
 *         return $maybe_modified_value;
 *     }
 *     add_filter( 'hook', 'example_callback', 10, 2 ); // Trong đó $priority là 10, $accepted_args là 2.
 *
 * *Lưu ý:* Hàm sẽ trả về true bất kể callback có hợp lệ hay không.
 * Bạn tự chịu trách nhiệm kiểm tra. Điều này được thực hiện vì mục đích tối ưu hóa,
 * để mọi thứ nhanh nhất có thể.
 *
 * @since 0.71
 *
 * @global WP_Hook[] $wp_filter Mảng đa chiều chứa tất cả hook và các callback được gắn vào chúng.
 *
 * @param string   $hook_name     Tên của filter để thêm callback vào.
 * @param callable $callback      Callback sẽ được chạy khi filter được áp dụng.
 * @param int      $priority      Tùy chọn. Dùng để chỉ định thứ tự thực thi các hàm
 *                                liên kết với một filter cụ thể.
 *                                Số thấp hơn tương ứng với thực thi sớm hơn,
 *                                và các hàm cùng mức ưu tiên được thực thi
 *                                theo thứ tự chúng được thêm vào filter. Mặc định 10.
 * @param int      $accepted_args Tùy chọn. Số đối số mà hàm chấp nhận. Mặc định 1.
 * @return true Luôn trả về true.
 */
function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		$wp_filter[ $hook_name ] = new WP_Hook();
	}

	$wp_filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, $accepted_args );

	return true;
}

/**
 * Gọi các hàm callback đã được thêm vào hook filter.
 *
 * Hàm này gọi tất cả các hàm đã gắn vào hook filter `$hook_name`.
 * Có thể tạo hook filter mới chỉ bằng cách gọi hàm này,
 * chỉ định tên hook mới bằng tham số `$hook_name`.
 *
 * Hàm cũng cho phép truyền nhiều đối số bổ sung cho các hook.
 *
 * Ví dụ sử dụng:
 *
 *     // Hàm callback filter.
 *     function example_callback( $string, $arg1, $arg2 ) {
 *         // (có thể) thay đổi $string.
 *         return $string;
 *     }
 *     add_filter( 'example_filter', 'example_callback', 10, 3 );
 *
 *     /*
 *      * Áp dụng các filter bằng cách gọi hàm 'example_callback()'
 *      * đã được hook vào `example_filter` ở trên.
 *      *
 *      * - 'example_filter' là hook filter.
 *      * - 'filter me' là giá trị đang được lọc.
 *      * - $arg1 và $arg2 là các đối số bổ sung truyền cho callback.
 *     $value = apply_filters( 'example_filter', 'filter me', $arg1, $arg2 );
 *
 * @since 0.71
 * @since 6.0.0 Chính thức hóa tham số `...$args` đã tồn tại và đã được tài liệu hóa
 *              bằng cách thêm vào chữ ký hàm.
 *
 * @global WP_Hook[] $wp_filter         Lưu trữ tất cả filter và action.
 * @global int[]     $wp_filters        Lưu trữ số lần mỗi filter được kích hoạt.
 * @global string[]  $wp_current_filter Lưu trữ danh sách filter hiện tại với filter đang chạy ở cuối.
 *
 * @param string $hook_name Tên của hook filter.
 * @param mixed  $value     Giá trị cần lọc.
 * @param mixed  ...$args   Tùy chọn. Các tham số bổ sung truyền cho hàm callback.
 * @return mixed Giá trị đã lọc sau khi tất cả hàm đã hook được áp dụng.
 */
function apply_filters( $hook_name, $value, ...$args ) {
	global $wp_filter, $wp_filters, $wp_current_filter;

	if ( ! isset( $wp_filters[ $hook_name ] ) ) {
		$wp_filters[ $hook_name ] = 1;
	} else {
		++$wp_filters[ $hook_name ];
	}

	// Thực hiện tất cả action 'all' trước.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;

		$all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		_wp_call_all_hook( $all_args );
	}

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		if ( isset( $wp_filter['all'] ) ) {
			array_pop( $wp_current_filter );
		}

		return $value;
	}

	if ( ! isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
	}

	// Truyền giá trị cho WP_Hook.
	array_unshift( $args, $value );

	$filtered = $wp_filter[ $hook_name ]->apply_filters( $value, $args );

	array_pop( $wp_current_filter );

	return $filtered;
}

/**
 * Gọi các hàm callback đã được thêm vào hook filter, chỉ định đối số trong mảng.
 *
 * @since 3.0.0
 *
 * @see apply_filters() Hàm này giống hệt, nhưng các đối số truyền cho
 *                      các hàm đã hook vào `$hook_name` được cung cấp bằng mảng.
 *
 * @global WP_Hook[] $wp_filter         Lưu trữ tất cả filter và action.
 * @global int[]     $wp_filters        Lưu trữ số lần mỗi filter được kích hoạt.
 * @global string[]  $wp_current_filter Lưu trữ danh sách filter hiện tại với filter đang chạy ở cuối.
 *
 * @param string $hook_name Tên của hook filter.
 * @param array  $args      Các đối số cung cấp cho các hàm đã hook vào `$hook_name`.
 * @return mixed Giá trị đã lọc sau khi tất cả hàm đã hook được áp dụng.
 */
function apply_filters_ref_array( $hook_name, $args ) {
	global $wp_filter, $wp_filters, $wp_current_filter;

	if ( ! isset( $wp_filters[ $hook_name ] ) ) {
		$wp_filters[ $hook_name ] = 1;
	} else {
		++$wp_filters[ $hook_name ];
	}

	// Thực hiện tất cả action 'all' trước.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
		$all_args            = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		_wp_call_all_hook( $all_args );
	}

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		if ( isset( $wp_filter['all'] ) ) {
			array_pop( $wp_current_filter );
		}

		return $args[0];
	}

	if ( ! isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
	}

	$filtered = $wp_filter[ $hook_name ]->apply_filters( $args[0], $args );

	array_pop( $wp_current_filter );

	return $filtered;
}

/**
 * Kiểm tra xem có filter nào đã được đăng ký cho một hook hay không.
 *
 * Khi sử dụng đối số `$callback`, hàm này có thể trả về giá trị không phải boolean
 * nhưng đánh giá là false (ví dụ: 0), vì vậy hãy dùng toán tử `===` để kiểm tra giá trị trả về.
 *
 * @since 2.5.0
 *
 * @global WP_Hook[] $wp_filter Lưu trữ tất cả filter và action.
 *
 * @param string                      $hook_name Tên của hook filter.
 * @param callable|string|array|false $callback  Tùy chọn. Callback cần kiểm tra.
 *                                               Hàm này có thể được gọi không điều kiện để kiểm tra
 *                                               một callback có thể có hoặc không tồn tại. Mặc định false.
 * @return bool|int Nếu bỏ qua `$callback`, trả về boolean cho biết hook có
 *                  bất kỳ đăng ký nào không. Khi kiểm tra một hàm cụ thể, trả về mức ưu tiên
 *                  của hook đó, hoặc false nếu hàm không được gắn.
 */
function has_filter( $hook_name, $callback = false ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		return false;
	}

	return $wp_filter[ $hook_name ]->has_filter( $hook_name, $callback );
}

/**
 * Gỡ bỏ một hàm callback khỏi hook filter.
 *
 * Có thể dùng để gỡ bỏ các hàm mặc định được gắn vào hook filter cụ thể
 * và có thể thay thế chúng bằng hàm thay thế.
 *
 * Để gỡ hook, các đối số `$callback` và `$priority` phải khớp
 * với khi hook được thêm vào. Điều này áp dụng cho cả filter và action. Sẽ không có
 * cảnh báo nào khi gỡ bỏ thất bại.
 *
 * @since 1.2.0
 *
 * @global WP_Hook[] $wp_filter Lưu trữ tất cả filter và action.
 *
 * @param string                $hook_name Hook filter mà hàm cần gỡ đang được hook vào.
 * @param callable|string|array $callback  Callback cần gỡ khỏi việc chạy khi filter được áp dụng.
 *                                         Hàm này có thể được gọi không điều kiện để gỡ bỏ
 *                                         một callback có thể có hoặc không tồn tại.
 * @param int                   $priority  Tùy chọn. Mức ưu tiên chính xác khi thêm
 *                                         callback filter ban đầu. Mặc định 10.
 * @return bool Hàm có tồn tại trước khi bị gỡ hay không.
 */
function remove_filter( $hook_name, $callback, $priority = 10 ) {
	global $wp_filter;

	$r = false;

	if ( isset( $wp_filter[ $hook_name ] ) ) {
		$r = $wp_filter[ $hook_name ]->remove_filter( $hook_name, $callback, $priority );

		if ( ! $wp_filter[ $hook_name ]->callbacks ) {
			unset( $wp_filter[ $hook_name ] );
		}
	}

	return $r;
}

/**
 * Gỡ bỏ tất cả hàm callback khỏi hook filter.
 *
 * @since 2.7.0
 *
 * @global WP_Hook[] $wp_filter Lưu trữ tất cả filter và action.
 *
 * @param string    $hook_name Filter cần gỡ bỏ callback.
 * @param int|false $priority  Tùy chọn. Số mức ưu tiên cần gỡ bỏ.
 *                             Mặc định false.
 * @return true Luôn trả về true.
 */
function remove_all_filters( $hook_name, $priority = false ) {
	global $wp_filter;

	if ( isset( $wp_filter[ $hook_name ] ) ) {
		$wp_filter[ $hook_name ]->remove_all_filters( $priority );

		if ( ! $wp_filter[ $hook_name ]->has_filters() ) {
			unset( $wp_filter[ $hook_name ] );
		}
	}

	return true;
}

/**
 * Lấy tên của hook filter hiện tại.
 *
 * @since 2.5.0
 *
 * @global string[] $wp_current_filter Lưu trữ danh sách filter hiện tại với filter đang chạy ở cuối
 *
 * @return string Tên hook của filter hiện tại.
 */
function current_filter() {
	global $wp_current_filter;

	return end( $wp_current_filter );
}

/**
 * Trả về hook filter có đang được xử lý hay không.
 *
 * Hàm current_filter() chỉ trả về filter gần nhất đang được thực thi.
 * did_filter() trả về số lần filter đã được áp dụng trong
 * request hiện tại.
 *
 * Hàm này cho phép phát hiện bất kỳ filter nào đang được thực thi
 * (bất kể nó có phải là filter gần nhất được kích hoạt hay không, trong trường hợp
 * hook được gọi từ callback của hook) để xác minh.
 *
 * @since 3.9.0
 *
 * @see current_filter()
 * @see did_filter()
 * @global string[] $wp_current_filter Filter hiện tại.
 *
 * @param string|null $hook_name Tùy chọn. Hook filter cần kiểm tra. Mặc định null,
 *                               kiểm tra xem có filter nào đang chạy không.
 * @return bool Filter có đang trong ngăn xếp hay không.
 */
function doing_filter( $hook_name = null ) {
	global $wp_current_filter;

	if ( null === $hook_name ) {
		return ! empty( $wp_current_filter );
	}

	return in_array( $hook_name, $wp_current_filter, true );
}

/**
 * Lấy số lần filter đã được áp dụng trong request hiện tại.
 *
 * @since 6.1.0
 *
 * @global int[] $wp_filters Lưu trữ số lần mỗi filter được kích hoạt.
 *
 * @param string $hook_name Tên của hook filter.
 * @return int Số lần hook filter đã được áp dụng.
 */
function did_filter( $hook_name ) {
	global $wp_filters;

	if ( ! isset( $wp_filters[ $hook_name ] ) ) {
		return 0;
	}

	return $wp_filters[ $hook_name ];
}

/**
 * Thêm một hàm callback vào hook action.
 *
 * Action là các hook mà WordPress core khởi chạy tại các điểm cụ thể
 * trong quá trình thực thi, hoặc khi các sự kiện cụ thể xảy ra. Plugin có thể chỉ định
 * một hoặc nhiều hàm PHP của nó được thực thi tại các điểm này, sử dụng
 * Action API.
 *
 * @since 1.2.0
 *
 * @param string   $hook_name       Tên của action để thêm callback vào.
 * @param callable $callback        Callback sẽ được chạy khi action được gọi.
 * @param int      $priority        Tùy chọn. Dùng để chỉ định thứ tự thực thi các hàm
 *                                  liên kết với một action cụ thể.
 *                                  Số thấp hơn tương ứng với thực thi sớm hơn,
 *                                  và các hàm cùng mức ưu tiên được thực thi
 *                                  theo thứ tự chúng được thêm vào action. Mặc định 10.
 * @param int      $accepted_args   Tùy chọn. Số đối số mà hàm chấp nhận. Mặc định 1.
 * @return true Luôn trả về true.
 */
function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $hook_name, $callback, $priority, $accepted_args );
}

/**
 * Gọi các hàm callback đã được thêm vào hook action.
 *
 * Hàm này gọi tất cả các hàm đã gắn vào hook action `$hook_name`.
 * Có thể tạo hook action mới chỉ bằng cách gọi hàm này,
 * chỉ định tên hook mới bằng tham số `$hook_name`.
 *
 * Bạn có thể truyền thêm đối số cho các hook, tương tự như với `apply_filters()`.
 *
 * Ví dụ sử dụng:
 *
 *     // Hàm callback action.
 *     function example_callback( $arg1, $arg2 ) {
 *         // (có thể) thực hiện gì đó với các đối số.
 *     }
 *     add_action( 'example_action', 'example_callback', 10, 2 );
 *
 *     /*
 *      * Kích hoạt các action bằng cách gọi hàm 'example_callback()'
 *      * đã được hook vào `example_action` ở trên.
 *      *
 *      * - 'example_action' là hook action.
 *      * - $arg1 và $arg2 là các đối số bổ sung truyền cho callback.
 *     do_action( 'example_action', $arg1, $arg2 );
 *
 * @since 1.2.0
 * @since 5.3.0 Chính thức hóa tham số `...$arg` đã tồn tại và đã được tài liệu hóa
 *              bằng cách thêm vào chữ ký hàm.
 *
 * @global WP_Hook[] $wp_filter         Lưu trữ tất cả filter và action.
 * @global int[]     $wp_actions        Lưu trữ số lần mỗi action được kích hoạt.
 * @global string[]  $wp_current_filter Lưu trữ danh sách filter hiện tại với filter đang chạy ở cuối.
 *
 * @param string $hook_name Tên của action cần thực thi.
 * @param mixed  ...$arg    Tùy chọn. Các đối số bổ sung được truyền cho
 *                          các hàm đã hook vào action. Mặc định rỗng.
 */
function do_action( $hook_name, ...$arg ) {
	global $wp_filter, $wp_actions, $wp_current_filter;

	if ( ! isset( $wp_actions[ $hook_name ] ) ) {
		$wp_actions[ $hook_name ] = 1;
	} else {
		++$wp_actions[ $hook_name ];
	}

	// Thực hiện tất cả action 'all' trước.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
		$all_args            = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		_wp_call_all_hook( $all_args );
	}

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		if ( isset( $wp_filter['all'] ) ) {
			array_pop( $wp_current_filter );
		}

		return;
	}

	if ( ! isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
	}

	if ( empty( $arg ) ) {
		$arg[] = '';
	} elseif ( is_array( $arg[0] ) && 1 === count( $arg[0] ) && isset( $arg[0][0] ) && is_object( $arg[0][0] ) ) {
		// Tương thích ngược cho kiểu truyền PHP4 `array( &$this )` làm `$arg` của action.
		$arg[0] = $arg[0][0];
	}

	$wp_filter[ $hook_name ]->do_action( $arg );

	array_pop( $wp_current_filter );
}

/**
 * Gọi các hàm callback đã được thêm vào hook action, chỉ định đối số trong mảng.
 *
 * @since 2.1.0
 *
 * @see do_action() Hàm này giống hệt, nhưng các đối số truyền cho
 *                  các hàm đã hook vào `$hook_name` được cung cấp bằng mảng.
 *
 * @global WP_Hook[] $wp_filter         Lưu trữ tất cả filter và action.
 * @global int[]     $wp_actions        Lưu trữ số lần mỗi action được kích hoạt.
 * @global string[]  $wp_current_filter Lưu trữ danh sách filter hiện tại với filter đang chạy ở cuối.
 *
 * @param string $hook_name Tên của action cần thực thi.
 * @param array  $args      Các đối số cung cấp cho các hàm đã hook vào `$hook_name`.
 */
function do_action_ref_array( $hook_name, $args ) {
	global $wp_filter, $wp_actions, $wp_current_filter;

	if ( ! isset( $wp_actions[ $hook_name ] ) ) {
		$wp_actions[ $hook_name ] = 1;
	} else {
		++$wp_actions[ $hook_name ];
	}

	// Thực hiện tất cả action 'all' trước.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
		$all_args            = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		_wp_call_all_hook( $all_args );
	}

	if ( ! isset( $wp_filter[ $hook_name ] ) ) {
		if ( isset( $wp_filter['all'] ) ) {
			array_pop( $wp_current_filter );
		}

		return;
	}

	if ( ! isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $hook_name;
	}

	$wp_filter[ $hook_name ]->do_action( $args );

	array_pop( $wp_current_filter );
}

/**
 * Kiểm tra xem có action nào đã được đăng ký cho một hook hay không.
 *
 * Khi sử dụng đối số `$callback`, hàm này có thể trả về giá trị không phải boolean
 * nhưng đánh giá là false (ví dụ: 0), vì vậy hãy dùng toán tử `===` để kiểm tra giá trị trả về.
 *
 * @since 2.5.0
 *
 * @see has_filter() Hàm này là bí danh của has_filter().
 *
 * @param string                      $hook_name Tên của hook action.
 * @param callable|string|array|false $callback  Tùy chọn. Callback cần kiểm tra.
 *                                               Hàm này có thể được gọi không điều kiện để kiểm tra
 *                                               một callback có thể có hoặc không tồn tại. Mặc định false.
 * @return bool|int Nếu bỏ qua `$callback`, trả về boolean cho biết hook có
 *                  bất kỳ đăng ký nào không. Khi kiểm tra một hàm cụ thể, trả về mức ưu tiên
 *                  của hook đó, hoặc false nếu hàm không được gắn.
 */
function has_action( $hook_name, $callback = false ) {
	return has_filter( $hook_name, $callback );
}

/**
 * Gỡ bỏ một hàm callback khỏi hook action.
 *
 * Có thể dùng để gỡ bỏ các hàm mặc định được gắn vào hook action cụ thể
 * và có thể thay thế chúng bằng hàm thay thế.
 *
 * Để gỡ hook, các đối số `$callback` và `$priority` phải khớp
 * với khi hook được thêm vào. Điều này áp dụng cho cả filter và action. Sẽ không có
 * cảnh báo nào khi gỡ bỏ thất bại.
 *
 * @since 1.2.0
 *
 * @param string                $hook_name Hook action mà hàm cần gỡ đang được hook vào.
 * @param callable|string|array $callback  Tên hàm cần được gỡ bỏ.
 *                                         Hàm này có thể được gọi không điều kiện để gỡ bỏ
 *                                         một callback có thể có hoặc không tồn tại.
 * @param int                   $priority  Tùy chọn. Mức ưu tiên chính xác khi thêm
 *                                         callback action ban đầu. Mặc định 10.
 * @return bool Hàm có được gỡ bỏ hay không.
 */
function remove_action( $hook_name, $callback, $priority = 10 ) {
	return remove_filter( $hook_name, $callback, $priority );
}

/**
 * Gỡ bỏ tất cả hàm callback khỏi hook action.
 *
 * @since 2.7.0
 *
 * @param string    $hook_name Action cần gỡ bỏ callback.
 * @param int|false $priority  Tùy chọn. Số mức ưu tiên cần gỡ bỏ.
 *                             Mặc định false.
 * @return true Luôn trả về true.
 */
function remove_all_actions( $hook_name, $priority = false ) {
	return remove_all_filters( $hook_name, $priority );
}

/**
 * Lấy tên của hook action hiện tại.
 *
 * @since 3.9.0
 *
 * @return string Tên hook của action hiện tại.
 */
function current_action() {
	return current_filter();
}

/**
 * Trả về hook action có đang được xử lý hay không.
 *
 * Hàm current_action() chỉ trả về action gần nhất đang được thực thi.
 * did_action() trả về số lần action đã được kích hoạt trong
 * request hiện tại.
 *
 * Hàm này cho phép phát hiện bất kỳ action nào đang được thực thi
 * (bất kể nó có phải là action gần nhất được kích hoạt hay không, trong trường hợp
 * hook được gọi từ callback của hook) để xác minh.
 *
 * @since 3.9.0
 *
 * @see current_action()
 * @see did_action()
 *
 * @param string|null $hook_name Tùy chọn. Hook action cần kiểm tra. Mặc định null,
 *                               kiểm tra xem có action nào đang chạy không.
 * @return bool Action có đang trong ngăn xếp hay không.
 */
function doing_action( $hook_name = null ) {
	return doing_filter( $hook_name );
}

/**
 * Lấy số lần action đã được kích hoạt trong request hiện tại.
 *
 * @since 2.1.0
 *
 * @global int[] $wp_actions Lưu trữ số lần mỗi action được kích hoạt.
 *
 * @param string $hook_name Tên của hook action.
 * @return int Số lần hook action đã được kích hoạt.
 */
function did_action( $hook_name ) {
	global $wp_actions;

	if ( ! isset( $wp_actions[ $hook_name ] ) ) {
		return 0;
	}

	return $wp_actions[ $hook_name ];
}

/**
 * Kích hoạt các hàm đã gắn vào hook filter đã ngừng sử dụng.
 *
 * Khi hook filter bị ngừng sử dụng, lời gọi apply_filters() được thay thế bằng
 * apply_filters_deprecated(), kích hoạt thông báo ngừng sử dụng và sau đó kích hoạt
 * hook filter gốc.
 *
 * Lưu ý: giá trị và đối số bổ sung truyền cho lời gọi apply_filters() gốc
 * phải được truyền ở đây cho `$args` dưới dạng mảng. Ví dụ:
 *
 *     // Filter cũ.
 *     return apply_filters( 'wpdocs_filter', $value, $extra_arg );
 *
 *     // Đã ngừng sử dụng.
 *     return apply_filters_deprecated( 'wpdocs_filter', array( $value, $extra_arg ), '4.9.0', 'wpdocs_new_filter' );
 *
 * @since 4.6.0
 *
 * @see _deprecated_hook()
 *
 * @param string $hook_name   Tên của hook filter.
 * @param array  $args        Mảng đối số bổ sung truyền cho apply_filters().
 * @param string $version     Phiên bản WordPress đã ngừng sử dụng hook.
 * @param string $replacement Tùy chọn. Hook nên được sử dụng thay thế. Mặc định rỗng.
 * @param string $message     Tùy chọn. Thông báo về sự thay đổi. Mặc định rỗng.
 * @return mixed Giá trị đã lọc sau khi tất cả hàm đã hook được áp dụng.
 */
function apply_filters_deprecated( $hook_name, $args, $version, $replacement = '', $message = '' ) {
	if ( ! has_filter( $hook_name ) ) {
		return $args[0];
	}

	_deprecated_hook( $hook_name, $version, $replacement, $message );

	return apply_filters_ref_array( $hook_name, $args );
}

/**
 * Kích hoạt các hàm đã gắn vào hook action đã ngừng sử dụng.
 *
 * Khi hook action bị ngừng sử dụng, lời gọi do_action() được thay thế bằng
 * do_action_deprecated(), kích hoạt thông báo ngừng sử dụng và sau đó kích hoạt
 * hook gốc.
 *
 * @since 4.6.0
 *
 * @see _deprecated_hook()
 *
 * @param string $hook_name   Tên của hook action.
 * @param array  $args        Mảng đối số bổ sung truyền cho do_action().
 * @param string $version     Phiên bản WordPress đã ngừng sử dụng hook.
 * @param string $replacement Tùy chọn. Hook nên được sử dụng thay thế. Mặc định rỗng.
 * @param string $message     Tùy chọn. Thông báo về sự thay đổi. Mặc định rỗng.
 */
function do_action_deprecated( $hook_name, $args, $version, $replacement = '', $message = '' ) {
	if ( ! has_action( $hook_name ) ) {
		return;
	}

	_deprecated_hook( $hook_name, $version, $replacement, $message );

	do_action_ref_array( $hook_name, $args );
}

//
// Các hàm xử lý plugin.
//

/**
 * Lấy tên cơ sở (basename) của plugin.
 *
 * Phương thức này trích xuất tên plugin từ tên file của nó.
 *
 * @since 1.5.0
 *
 * @global array $wp_plugin_paths
 *
 * @param string $file Tên file của plugin.
 * @return string Tên của plugin.
 */
function plugin_basename( $file ) {
	global $wp_plugin_paths;

	// $wp_plugin_paths chứa các đường dẫn đã chuẩn hóa.
	$file = wp_normalize_path( $file );

	arsort( $wp_plugin_paths );

	foreach ( $wp_plugin_paths as $dir => $realdir ) {
		if ( str_starts_with( $file, $realdir ) ) {
			$file = $dir . substr( $file, strlen( $realdir ) );
		}
	}

	$plugin_dir    = wp_normalize_path( WP_PLUGIN_DIR );
	$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

	// Lấy đường dẫn tương đối từ thư mục plugin.
	$file = preg_replace( '#^' . preg_quote( $plugin_dir, '#' ) . '/|^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', '', $file );
	$file = trim( $file, '/' );
	return $file;
}

/**
 * Đăng ký đường dẫn thực của plugin.
 *
 * Được sử dụng trong plugin_basename() để giải quyết các đường dẫn symlink.
 *
 * @since 3.9.0
 *
 * @see wp_normalize_path()
 *
 * @global array $wp_plugin_paths
 *
 * @param string $file Đường dẫn đã biết đến file.
 * @return bool Đường dẫn có thể đăng ký được hay không.
 */
function wp_register_plugin_realpath( $file ) {
	global $wp_plugin_paths;

	// Chuẩn hóa, nhưng lưu dưới dạng static để tránh tính toán lại giá trị hằng số.
	static $wp_plugin_path = null, $wpmu_plugin_path = null;

	if ( ! isset( $wp_plugin_path ) ) {
		$wp_plugin_path   = wp_normalize_path( WP_PLUGIN_DIR );
		$wpmu_plugin_path = wp_normalize_path( WPMU_PLUGIN_DIR );
	}

	$plugin_path     = wp_normalize_path( dirname( $file ) );
	$plugin_realpath = wp_normalize_path( dirname( realpath( $file ) ) );

	if ( $plugin_path === $wp_plugin_path || $plugin_path === $wpmu_plugin_path ) {
		return false;
	}

	if ( $plugin_path !== $plugin_realpath ) {
		$wp_plugin_paths[ $plugin_path ] = $plugin_realpath;
	}

	return true;
}

/**
 * Lấy đường dẫn thư mục hệ thống tệp (có dấu gạch chéo cuối) cho __FILE__ của plugin được truyền vào.
 *
 * @since 2.8.0
 *
 * @param string $file Tên file của plugin (__FILE__).
 * @return string Đường dẫn hệ thống tệp của thư mục chứa plugin.
 */
function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}

/**
 * Lấy đường dẫn URL thư mục (có dấu gạch chéo cuối) cho __FILE__ của plugin được truyền vào.
 *
 * @since 2.8.0
 *
 * @param string $file Tên file của plugin (__FILE__).
 * @return string Đường dẫn URL của thư mục chứa plugin.
 */
function plugin_dir_url( $file ) {
	return trailingslashit( plugins_url( '', $file ) );
}

/**
 * Đặt hook kích hoạt cho plugin.
 *
 * Khi plugin được kích hoạt, hook action 'activate_PLUGINNAME' sẽ
 * được gọi. Trong tên hook này, PLUGINNAME được thay thế bằng tên
 * plugin, bao gồm cả thư mục con tùy chọn. Ví dụ, khi plugin
 * nằm tại wp-content/plugins/sampleplugin/sample.php, thì
 * tên hook sẽ là 'activate_sampleplugin/sample.php'.
 *
 * Khi plugin chỉ gồm một file và (theo mặc định) nằm tại
 * wp-content/plugins/sample.php thì tên hook sẽ là
 * 'activate_sample.php'.
 *
 * @since 2.0.0
 *
 * @param string   $file     Tên file plugin bao gồm đường dẫn.
 * @param callable $callback Hàm được hook vào action 'activate_PLUGIN'.
 */
function register_activation_hook( $file, $callback ) {
	$file = plugin_basename( $file );
	add_action( 'activate_' . $file, $callback );
}

/**
 * Đặt hook vô hiệu hóa cho plugin.
 *
 * Khi plugin bị vô hiệu hóa, hook action 'deactivate_PLUGINNAME' sẽ
 * được gọi. Trong tên hook này, PLUGINNAME được thay thế bằng tên
 * plugin, bao gồm cả thư mục con tùy chọn. Ví dụ, khi plugin
 * nằm tại wp-content/plugins/sampleplugin/sample.php, thì
 * tên hook sẽ là 'deactivate_sampleplugin/sample.php'.
 *
 * Khi plugin chỉ gồm một file và (theo mặc định) nằm tại
 * wp-content/plugins/sample.php thì tên hook sẽ là
 * 'deactivate_sample.php'.
 *
 * @since 2.0.0
 *
 * @param string   $file     Tên file plugin bao gồm đường dẫn.
 * @param callable $callback Hàm được hook vào action 'deactivate_PLUGIN'.
 */
function register_deactivation_hook( $file, $callback ) {
	$file = plugin_basename( $file );
	add_action( 'deactivate_' . $file, $callback );
}

/**
 * Đặt hook gỡ cài đặt cho plugin.
 *
 * Đăng ký hook gỡ cài đặt sẽ được gọi khi người dùng nhấp vào
 * liên kết gỡ cài đặt để plugin tự gỡ bỏ. Liên kết sẽ không
 * hoạt động trừ khi plugin hook vào action này.
 *
 * Plugin không nên chạy mã tùy ý bên ngoài các hàm, khi
 * đăng ký hook gỡ cài đặt. Để chạy bằng hook, plugin
 * sẽ phải được include, nghĩa là bất kỳ mã nào nằm ngoài
 * hàm sẽ chạy trong quá trình gỡ cài đặt. Plugin không nên
 * cản trở quá trình gỡ cài đặt.
 *
 * Nếu plugin không thể viết mà không chạy mã bên trong plugin, thì
 * plugin nên tạo file tên 'uninstall.php' trong thư mục gốc plugin.
 * File này sẽ được gọi, nếu tồn tại, trong quá trình gỡ cài đặt
 * bỏ qua hook gỡ cài đặt. Plugin, khi sử dụng 'uninstall.php'
 * nên luôn kiểm tra hằng số 'WP_UNINSTALL_PLUGIN' trước
 * khi thực thi.
 *
 * @since 2.7.0
 *
 * @param string   $file     File plugin.
 * @param callable $callback Callback sẽ chạy khi hook được gọi. Phải là
 *                           phương thức static hoặc hàm.
 */
function register_uninstall_hook( $file, $callback ) {
	if ( is_array( $callback ) && is_object( $callback[0] ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Only a static class method or function can be used in an uninstall hook.' ), '3.1.0' );
		return;
	}

	/*
	 * Tùy chọn không nên được tự động tải, vì nó không cần thiết trong hầu hết
	 * các trường hợp. Nên ưu tiên sử dụng cách gỡ cài đặt bằng 'uninstall.php'.
	 */
	$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );
	$plugin_basename       = plugin_basename( $file );

	if ( ! isset( $uninstallable_plugins[ $plugin_basename ] ) || $uninstallable_plugins[ $plugin_basename ] !== $callback ) {
		$uninstallable_plugins[ $plugin_basename ] = $callback;
		update_option( 'uninstall_plugins', $uninstallable_plugins );
	}
}

/**
 * Gọi hook 'all', sẽ xử lý các hàm đã hook vào nó.
 *
 * Hook 'all' truyền tất cả đối số hoặc tham số đã được sử dụng cho
 * hook mà hàm này được gọi.
 *
 * Hàm này được sử dụng nội bộ cho apply_filters(), do_action(), và
 * do_action_ref_array() và không nên được sử dụng từ bên ngoài các
 * hàm đó. Hàm này không kiểm tra sự tồn tại của hook all, nên
 * nó sẽ thất bại trừ khi hook all tồn tại trước lời gọi hàm này.
 *
 * @since 2.5.0
 * @access private
 *
 * @global WP_Hook[] $wp_filter Lưu trữ tất cả filter và action.
 *
 * @param array $args Các tham số thu thập từ hook đã được gọi.
 */
function _wp_call_all_hook( $args ) {
	global $wp_filter;

	$wp_filter['all']->do_all_hook( $args );
}

/**
 * Tạo chuỗi ID duy nhất cho hàm callback của hook.
 *
 * Các hàm và callback phương thức static chỉ được trả về dưới dạng chuỗi và
 * không nên có bất kỳ hao tổn hiệu suất nào.
 *
 * @link https://core.trac.wordpress.org/ticket/3875
 *
 * @since 2.2.3
 * @since 5.3.0 Đã gỡ bỏ các giải pháp tạm cho spl_object_hash().
 *              `$hook_name` và `$priority` không còn được sử dụng,
 *              và hàm luôn trả về chuỗi.
 *
 * @access private
 *
 * @param string                $hook_name Không sử dụng. Tên filter để tạo ID.
 * @param callable|string|array $callback  Callback để tạo ID. Callback có thể
 *                                         có hoặc không tồn tại.
 * @param int                   $priority  Không sử dụng. Thứ tự thực thi các hàm
 *                                         liên kết với action cụ thể.
 * @return string ID hàm duy nhất để dùng làm khóa mảng.
 */
function _wp_filter_build_unique_id( $hook_name, $callback, $priority ) {
	if ( is_string( $callback ) ) {
		return $callback;
	}

	if ( is_object( $callback ) ) {
		// Closure hiện tại được triển khai dưới dạng đối tượng.
		$callback = array( $callback, '' );
	} else {
		$callback = (array) $callback;
	}

	if ( is_object( $callback[0] ) ) {
		// Gọi từ lớp đối tượng.
		return spl_object_hash( $callback[0] ) . $callback[1];
	} elseif ( is_string( $callback[0] ) ) {
		// Gọi static.
		return $callback[0] . '::' . $callback[1];
	}
}
