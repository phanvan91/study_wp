<?php
/**
 * API Plugin: Lớp WP_Hook
 *
 * @package WordPress
 * @subpackage Plugin
 * @since 4.7.0
 */

/**
 * Lớp lõi dùng để triển khai chức năng hook action và filter.
 *
 * @since 4.7.0
 *
 * @see Iterator
 * @see ArrayAccess
 */
#[AllowDynamicProperties]
final class WP_Hook implements Iterator, ArrayAccess {

	/**
	 * Các callback của hook.
	 *
	 * @since 4.7.0
	 * @var array
	 */
	public $callbacks = array();

	/**
	 * Danh sách các mức ưu tiên.
	 *
	 * @since 6.4.0
	 * @var array
	 */
	protected $priorities = array();

	/**
	 * Các khóa ưu tiên của các vòng lặp đang chạy của hook.
	 *
	 * @since 4.7.0
	 * @var array
	 */
	private $iterations = array();

	/**
	 * Mức ưu tiên hiện tại của các vòng lặp đang chạy của hook.
	 *
	 * @since 4.7.0
	 * @var array
	 */
	private $current_priority = array();

	/**
	 * Số cấp độ mà hook này có thể được gọi đệ quy.
	 *
	 * @since 4.7.0
	 * @var int
	 */
	private $nesting_level = 0;

	/**
	 * Cờ cho biết chúng ta đang thực thi action hay filter.
	 *
	 * @since 4.7.0
	 * @var bool
	 */
	private $doing_action = false;

	/**
	 * Thêm một hàm callback vào hook filter.
	 *
	 * @since 4.7.0
	 *
	 * @param string   $hook_name     Tên của filter để thêm callback vào.
	 * @param callable $callback      Callback sẽ được chạy khi filter được áp dụng.
	 * @param int      $priority      Thứ tự thực thi các hàm gắn với một filter cụ thể.
	 *                                Số nhỏ hơn tương ứng với thực thi sớm hơn,
	 *                                và các hàm có cùng mức ưu tiên sẽ được thực thi theo thứ tự
	 *                                chúng được thêm vào filter.
	 * @param int      $accepted_args Số lượng tham số mà hàm chấp nhận.
	 */
	public function add_filter( $hook_name, $callback, $priority, $accepted_args ) {
		$idx = _wp_filter_build_unique_id( $hook_name, $callback, $priority );

		$priority_existed = isset( $this->callbacks[ $priority ] );

		$this->callbacks[ $priority ][ $idx ] = array(
			'function'      => $callback,
			'accepted_args' => (int) $accepted_args,
		);

		// Nếu đang thêm mức ưu tiên mới vào danh sách, sắp xếp lại theo thứ tự.
		if ( ! $priority_existed && count( $this->callbacks ) > 1 ) {
			ksort( $this->callbacks, SORT_NUMERIC );
		}

		$this->priorities = array_keys( $this->callbacks );

		if ( $this->nesting_level > 0 ) {
			$this->resort_active_iterations( $priority, $priority_existed );
		}
	}

	/**
	 * Xử lý việc đặt lại khóa ưu tiên callback trong khi đang lặp.
	 *
	 * @since 4.7.0
	 *
	 * @param false|int $new_priority     Tùy chọn. Mức ưu tiên của filter mới đang được thêm. Mặc định false,
	 *                                    nghĩa là không có mức ưu tiên nào được thêm.
	 * @param bool      $priority_existed Tùy chọn. Cờ cho biết mức ưu tiên đã tồn tại trước khi
	 *                                    filter mới được thêm hay chưa. Mặc định false.
	 */
	private function resort_active_iterations( $new_priority = false, $priority_existed = false ) {
		$new_priorities = $this->priorities;

		// Nếu không còn hook nào, xóa tất cả các vòng lặp đang chạy.
		if ( ! $new_priorities ) {
			foreach ( $this->iterations as $index => $iteration ) {
				$this->iterations[ $index ] = $new_priorities;
			}

			return;
		}

		$min = min( $new_priorities );

		foreach ( $this->iterations as $index => &$iteration ) {
			$current = current( $iteration );

			// Nếu đã ở cuối vòng lặp này, giữ nguyên con trỏ mảng.
			if ( false === $current ) {
				continue;
			}

			$iteration = $new_priorities;

			if ( $current < $min ) {
				array_unshift( $iteration, $current );
				continue;
			}

			while ( current( $iteration ) < $current ) {
				if ( false === next( $iteration ) ) {
					break;
				}
			}

			// Nếu có mức ưu tiên mới chưa tồn tại, nhưng ::apply_filters() hoặc ::do_action() cho rằng đó là mức ưu tiên hiện tại...
			if ( $new_priority === $this->current_priority[ $index ] && ! $priority_existed ) {
				/*
				 * ...và mức ưu tiên mới giống với mức ưu tiên trước đó mà $this->iterations cho rằng,
				 * chúng ta cần quay lại mức đó.
				 */

				if ( false === current( $iteration ) ) {
					// Nếu đã di chuyển qua cuối mảng, quay lại phần tử cuối cùng.
					$prev = end( $iteration );
				} else {
					// Nếu không, quay lại phần tử trước đó.
					$prev = prev( $iteration );
				}

				if ( false === $prev ) {
					// Đầu mảng. Đặt lại và tiếp tục.
					reset( $iteration );
				} elseif ( $new_priority !== $prev ) {
					// Phần tử trước không giống. Di chuyển tiến lên lại.
					next( $iteration );
				}
			}
		}

		unset( $iteration );
	}

	/**
	 * Xóa một hàm callback khỏi hook filter.
	 *
	 * @since 4.7.0
	 *
	 * @param string                $hook_name Hook filter mà hàm cần xóa đang được gắn vào.
	 * @param callable|string|array $callback  Callback cần được xóa khỏi việc chạy khi filter được áp dụng.
	 *                                         Phương thức này có thể được gọi không điều kiện để xóa
	 *                                         một callback có thể tồn tại hoặc không.
	 * @param int                   $priority  Mức ưu tiên chính xác được sử dụng khi thêm callback filter ban đầu.
	 * @return bool Callback có tồn tại trước khi bị xóa hay không.
	 */
	public function remove_filter( $hook_name, $callback, $priority ) {
		$function_key = _wp_filter_build_unique_id( $hook_name, $callback, $priority );

		$exists = isset( $this->callbacks[ $priority ][ $function_key ] );

		if ( $exists ) {
			unset( $this->callbacks[ $priority ][ $function_key ] );

			if ( ! $this->callbacks[ $priority ] ) {
				unset( $this->callbacks[ $priority ] );

				$this->priorities = array_keys( $this->callbacks );

				if ( $this->nesting_level > 0 ) {
					$this->resort_active_iterations();
				}
			}
		}

		return $exists;
	}

	/**
	 * Kiểm tra xem một callback cụ thể đã được đăng ký cho hook này chưa.
	 *
	 * Khi sử dụng tham số `$callback`, hàm này có thể trả về giá trị không phải boolean
	 * mà đánh giá là false (ví dụ: 0), vì vậy hãy sử dụng toán tử `===` để kiểm tra giá trị trả về.
	 *
	 * @since 4.7.0
	 *
	 * @param string                      $hook_name Tùy chọn. Tên của hook filter. Mặc định rỗng.
	 * @param callable|string|array|false $callback  Tùy chọn. Callback cần kiểm tra.
	 *                                               Phương thức này có thể được gọi không điều kiện để kiểm tra
	 *                                               một callback có thể tồn tại hoặc không. Mặc định false.
	 * @return bool|int Nếu `$callback` bị bỏ qua, trả về boolean cho biết hook có
	 *                  đăng ký gì không. Khi kiểm tra một hàm cụ thể, mức ưu tiên
	 *                  của hook đó được trả về, hoặc false nếu hàm không được gắn.
	 */
	public function has_filter( $hook_name = '', $callback = false ) {
		if ( false === $callback ) {
			return $this->has_filters();
		}

		$function_key = _wp_filter_build_unique_id( $hook_name, $callback, false );

		if ( ! $function_key ) {
			return false;
		}

		foreach ( $this->callbacks as $priority => $callbacks ) {
			if ( isset( $callbacks[ $function_key ] ) ) {
				return $priority;
			}
		}

		return false;
	}

	/**
	 * Kiểm tra xem có callback nào đã được đăng ký cho hook này không.
	 *
	 * @since 4.7.0
	 *
	 * @return bool True nếu có callback đã được đăng ký cho hook hiện tại, ngược lại false.
	 */
	public function has_filters() {
		foreach ( $this->callbacks as $callbacks ) {
			if ( $callbacks ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Xóa tất cả callback khỏi filter hiện tại.
	 *
	 * @since 4.7.0
	 *
	 * @param int|false $priority Tùy chọn. Số mức ưu tiên cần xóa. Mặc định false.
	 */
	public function remove_all_filters( $priority = false ) {
		if ( ! $this->callbacks ) {
			return;
		}

		if ( false === $priority ) {
			$this->callbacks  = array();
			$this->priorities = array();
		} elseif ( isset( $this->callbacks[ $priority ] ) ) {
			unset( $this->callbacks[ $priority ] );
			$this->priorities = array_keys( $this->callbacks );
		}

		if ( $this->nesting_level > 0 ) {
			$this->resort_active_iterations();
		}
	}

	/**
	 * Gọi các hàm callback đã được thêm vào hook filter.
	 *
	 * @since 4.7.0
	 *
	 * @param mixed $value Giá trị cần lọc.
	 * @param array $args  Các tham số bổ sung để truyền cho các hàm callback.
	 *                     Mảng này được kỳ vọng chứa $value tại chỉ mục 0.
	 * @return mixed Giá trị đã được lọc sau khi tất cả các hàm đã gắn được áp dụng.
	 */
	public function apply_filters( $value, $args ) {
		if ( ! $this->callbacks ) {
			return $value;
		}

		$nesting_level = $this->nesting_level++;

		$this->iterations[ $nesting_level ] = $this->priorities;

		$num_args = count( $args );

		do {
			$this->current_priority[ $nesting_level ] = current( $this->iterations[ $nesting_level ] );

			$priority = $this->current_priority[ $nesting_level ];

			foreach ( $this->callbacks[ $priority ] as $the_ ) {
				if ( ! $this->doing_action ) {
					$args[0] = $value;
				}

				// Tránh dùng array_slice() nếu có thể.
				if ( 0 === $the_['accepted_args'] ) {
					$value = call_user_func( $the_['function'] );
				} elseif ( $the_['accepted_args'] >= $num_args ) {
					$value = call_user_func_array( $the_['function'], $args );
				} else {
					$value = call_user_func_array( $the_['function'], array_slice( $args, 0, $the_['accepted_args'] ) );
				}
			}
		} while ( false !== next( $this->iterations[ $nesting_level ] ) );

		unset( $this->iterations[ $nesting_level ] );
		unset( $this->current_priority[ $nesting_level ] );

		--$this->nesting_level;

		return $value;
	}

	/**
	 * Gọi các hàm callback đã được thêm vào hook action.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args Các tham số truyền cho các hàm callback.
	 */
	public function do_action( $args ) {
		$this->doing_action = true;
		$this->apply_filters( '', $args );

		// Nếu có các lời gọi đệ quy đến action hiện tại, chưa hoàn tất cho đến khi đến lời gọi cuối cùng.
		if ( ! $this->nesting_level ) {
			$this->doing_action = false;
		}
	}

	/**
	 * Xử lý các hàm được gắn vào hook 'all'.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args Các tham số truyền cho các callback của hook. Truyền theo tham chiếu.
	 */
	public function do_all_hook( &$args ) {
		$nesting_level                      = $this->nesting_level++;
		$this->iterations[ $nesting_level ] = $this->priorities;

		do {
			$priority = current( $this->iterations[ $nesting_level ] );

			foreach ( $this->callbacks[ $priority ] as $the_ ) {
				call_user_func_array( $the_['function'], $args );
			}
		} while ( false !== next( $this->iterations[ $nesting_level ] ) );

		unset( $this->iterations[ $nesting_level ] );
		--$this->nesting_level;
	}

	/**
	 * Trả về mức ưu tiên hiện tại của vòng lặp đang chạy của hook.
	 *
	 * @since 4.7.0
	 *
	 * @return int|false Nếu hook đang chạy, trả về mức ưu tiên hiện tại.
	 *                   Nếu không chạy, trả về false.
	 */
	public function current_priority() {
		if ( false === current( $this->iterations ) ) {
			return false;
		}

		return current( current( $this->iterations ) );
	}

	/**
	 * Chuẩn hóa các filter được thiết lập trước khi WordPress khởi tạo thành đối tượng WP_Hook.
	 *
	 * Tham số `$filters` phải là một mảng có khóa là tên hook, với giá trị
	 * chứa một trong hai:
	 *
	 *  - Một thể hiện `WP_Hook`
	 *  - Một mảng các callback có khóa theo mức ưu tiên
	 *
	 * Ví dụ:
	 *
	 *     $filters = array(
	 *         'wp_fatal_error_handler_enabled' => array(
	 *             10 => array(
	 *                 array(
	 *                     'accepted_args' => 0,
	 *                     'function'      => function() {
	 *                         return false;
	 *                     },
	 *                 ),
	 *             ),
	 *         ),
	 *     );
	 *
	 * @since 4.7.0
	 *
	 * @param array $filters Các filter cần chuẩn hóa. Xem tài liệu phía trên để biết chi tiết.
	 * @return WP_Hook[] Mảng các filter đã được chuẩn hóa.
	 */
	public static function build_preinitialized_hooks( $filters ) {
		/** @var WP_Hook[] $normalized */
		$normalized = array();

		foreach ( $filters as $hook_name => $callback_groups ) {
			if ( $callback_groups instanceof WP_Hook ) {
				$normalized[ $hook_name ] = $callback_groups;
				continue;
			}

			$hook = new WP_Hook();

			// Lặp qua các nhóm callback.
			foreach ( $callback_groups as $priority => $callbacks ) {

				// Lặp qua các callback.
				foreach ( $callbacks as $cb ) {
					$hook->add_filter( $hook_name, $cb['function'], $priority, $cb['accepted_args'] );
				}
			}

			$normalized[ $hook_name ] = $hook;
		}

		return $normalized;
	}

	/**
	 * Xác định xem một giá trị offset có tồn tại hay không.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset Một offset cần kiểm tra.
	 * @return bool True nếu offset tồn tại, false nếu không.
	 */
	#[ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->callbacks[ $offset ] );
	}

	/**
	 * Lấy giá trị tại một offset được chỉ định.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset Offset cần lấy.
	 * @return mixed Nếu được thiết lập, giá trị tại offset được chỉ định, ngược lại null.
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return isset( $this->callbacks[ $offset ] ) ? $this->callbacks[ $offset ] : null;
	}

	/**
	 * Thiết lập giá trị tại một offset được chỉ định.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset Offset để gán giá trị.
	 * @param mixed $value Giá trị cần thiết lập.
	 */
	#[ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->callbacks[] = $value;
		} else {
			$this->callbacks[ $offset ] = $value;
		}

		$this->priorities = array_keys( $this->callbacks );
	}

	/**
	 * Hủy thiết lập một offset được chỉ định.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset Offset cần hủy thiết lập.
	 */
	#[ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->callbacks[ $offset ] );
		$this->priorities = array_keys( $this->callbacks );
	}

	/**
	 * Trả về phần tử hiện tại.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/iterator.current.php
	 *
	 * @return array Các callback tại mức ưu tiên hiện tại.
	 */
	#[ReturnTypeWillChange]
	public function current() {
		return current( $this->callbacks );
	}

	/**
	 * Di chuyển tiến đến phần tử tiếp theo.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/iterator.next.php
	 *
	 * @return array Các callback tại mức ưu tiên tiếp theo.
	 */
	#[ReturnTypeWillChange]
	public function next() {
		return next( $this->callbacks );
	}

	/**
	 * Trả về khóa của phần tử hiện tại.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/iterator.key.php
	 *
	 * @return mixed Trả về mức ưu tiên hiện tại nếu thành công, hoặc NULL nếu thất bại.
	 */
	#[ReturnTypeWillChange]
	public function key() {
		return key( $this->callbacks );
	}

	/**
	 * Kiểm tra xem vị trí hiện tại có hợp lệ hay không.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/iterator.valid.php
	 *
	 * @return bool Vị trí hiện tại có hợp lệ hay không.
	 */
	#[ReturnTypeWillChange]
	public function valid() {
		return key( $this->callbacks ) !== null;
	}

	/**
	 * Tua lại Iterator về phần tử đầu tiên.
	 *
	 * @since 4.7.0
	 *
	 * @link https://www.php.net/manual/en/iterator.rewind.php
	 */
	#[ReturnTypeWillChange]
	public function rewind() {
		reset( $this->callbacks );
	}
}
