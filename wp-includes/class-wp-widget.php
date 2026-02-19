<?php
/**
 * API Widget: Lớp cơ sở WP_Widget
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.4.0
 */

/**
 * Lớp cơ sở lõi được mở rộng để đăng ký widget.
 *
 * Lớp này phải được mở rộng cho mỗi widget, và WP_Widget::widget() phải được ghi đè.
 *
 * Nếu thêm tùy chọn widget, WP_Widget::update() và WP_Widget::form() cũng nên được ghi đè.
 *
 * @since 2.8.0
 * @since 4.4.0 Chuyển sang file riêng từ wp-includes/widgets.php
 */
#[AllowDynamicProperties]
class WP_Widget {

	/**
	 * ID gốc cho tất cả widget thuộc loại này.
	 *
	 * @since 2.8.0
	 * @var mixed|string
	 */
	public $id_base;

	/**
	 * Tên cho loại widget này.
	 *
	 * @since 2.8.0
	 * @var string
	 */
	public $name;

	/**
	 * Tên tùy chọn cho loại widget này.
	 *
	 * @since 2.8.0
	 * @var string
	 */
	public $option_name;

	/**
	 * Tên tùy chọn thay thế cho loại widget này.
	 *
	 * @since 2.8.0
	 * @var string
	 */
	public $alt_option_name;

	/**
	 * Mảng tùy chọn truyền cho wp_register_sidebar_widget().
	 *
	 * @since 2.8.0
	 * @var array
	 */
	public $widget_options;

	/**
	 * Mảng tùy chọn truyền cho wp_register_widget_control().
	 *
	 * @since 2.8.0
	 * @var array
	 */
	public $control_options;

	/**
	 * Số ID duy nhất của thể hiện hiện tại.
	 *
	 * @since 2.8.0
	 * @var bool|int
	 */
	public $number = false;

	/**
	 * Chuỗi ID duy nhất của thể hiện hiện tại (id_base-number).
	 *
	 * @since 2.8.0
	 * @var bool|string
	 */
	public $id = false;

	/**
	 * Dữ liệu widget đã được cập nhật hay chưa.
	 *
	 * Được đặt thành true khi dữ liệu được cập nhật sau khi POST gửi đi - đảm bảo
	 * không xảy ra hai lần.
	 *
	 * @since 2.8.0
	 * @var bool
	 */
	public $updated = false;

	//
	// Các hàm thành viên phải được ghi đè bởi lớp con.
	//

	/**
	 * Xuất nội dung widget.
	 *
	 * Các lớp con nên ghi đè hàm này để tạo mã widget của chúng.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args     Các tham số hiển thị bao gồm 'before_title', 'after_title',
	 *                        'before_widget', và 'after_widget'.
	 * @param array $instance Các cài đặt cho thể hiện cụ thể của widget.
	 */
	public function widget( $args, $instance ) {
		die( 'function WP_Widget::widget() must be overridden in a subclass.' );
	}

	/**
	 * Cập nhật một thể hiện cụ thể của widget.
	 *
	 * Hàm này nên kiểm tra rằng `$new_instance` được thiết lập đúng. Giá trị
	 * mới tính toán được của `$instance` sẽ được trả về. Nếu trả về false, thể hiện sẽ không
	 * được lưu/cập nhật.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance Cài đặt mới cho thể hiện này do người dùng nhập qua
	 *                            WP_Widget::form().
	 * @param array $old_instance Cài đặt cũ cho thể hiện này.
	 * @return array Cài đặt cần lưu hoặc bool false để hủy lưu.
	 */
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	/**
	 * Xuất biểu mẫu cập nhật cài đặt.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Các cài đặt cho thể hiện cụ thể của widget.
	 * @return string|void Giá trị trả về mặc định là 'noform'.
	 */
	public function form( $instance ) {
		echo '<p class="no-options-widget">' . __( 'There are no options for this widget.' ) . '</p>';
		return 'noform';
	}

	// Các hàm bạn sẽ cần gọi.

	/**
	 * Hàm khởi tạo PHP5.
	 *
	 * @since 2.8.0
	 *
	 * @param string $id_base         ID cơ sở cho widget, viết thường và duy nhất. Nếu để trống,
	 *                                một phần tên lớp PHP của widget sẽ được sử dụng. Phải duy nhất.
	 * @param string $name            Tên widget hiển thị trên trang cấu hình.
	 * @param array  $widget_options  Tùy chọn. Tùy chọn widget. Xem wp_register_sidebar_widget() để biết
	 *                                thông tin về các tham số chấp nhận. Mặc định mảng rỗng.
	 * @param array  $control_options Tùy chọn. Tùy chọn điều khiển widget. Xem wp_register_widget_control() để biết
	 *                                thông tin về các tham số chấp nhận. Mặc định mảng rỗng.
	 */
	public function __construct( $id_base, $name, $widget_options = array(), $control_options = array() ) {
		if ( ! empty( $id_base ) ) {
			$id_base = strtolower( $id_base );
		} else {
			$id_base = preg_replace( '/(wp_)?widget_/', '', strtolower( get_class( $this ) ) );
		}

		$this->id_base         = $id_base;
		$this->name            = $name;
		$this->option_name     = 'widget_' . $this->id_base;
		$this->widget_options  = wp_parse_args(
			$widget_options,
			array(
				'classname'                   => str_replace( '\\', '_', $this->option_name ),
				'customize_selective_refresh' => false,
			)
		);
		$this->control_options = wp_parse_args( $control_options, array( 'id_base' => $this->id_base ) );
	}

	/**
	 * Hàm khởi tạo PHP4.
	 *
	 * @since 2.8.0
	 * @deprecated 4.3.0 Sử dụng __construct() thay thế.
	 *
	 * @see WP_Widget::__construct()
	 *
	 * @param string $id_base         ID cơ sở cho widget, viết thường và duy nhất. Nếu để trống,
	 *                                một phần tên lớp PHP của widget sẽ được sử dụng. Phải duy nhất.
	 * @param string $name            Tên widget hiển thị trên trang cấu hình.
	 * @param array  $widget_options  Tùy chọn. Tùy chọn widget. Xem wp_register_sidebar_widget() để biết
	 *                                thông tin về các tham số chấp nhận. Mặc định mảng rỗng.
	 * @param array  $control_options Tùy chọn. Tùy chọn điều khiển widget. Xem wp_register_widget_control() để biết
	 *                                thông tin về các tham số chấp nhận. Mặc định mảng rỗng.
	 */
	public function WP_Widget( $id_base, $name, $widget_options = array(), $control_options = array() ) {
		_deprecated_constructor( 'WP_Widget', '4.3.0', get_class( $this ) );
		WP_Widget::__construct( $id_base, $name, $widget_options, $control_options );
	}

	/**
	 * Tạo thuộc tính name để sử dụng trong các trường của form().
	 *
	 * Hàm này nên được sử dụng trong các phương thức form() để tạo thuộc tính name cho các trường
	 * sẽ được lưu bởi update().
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Tên trường dạng mảng giờ đã được hỗ trợ.
	 *
	 * @param string $field_name Tên trường.
	 * @return string Thuộc tính name cho `$field_name`.
	 */
	public function get_field_name( $field_name ) {
		$pos = strpos( $field_name, '[' );

		if ( false !== $pos ) {
			// Thay thế lần xuất hiện đầu tiên của '[' bằng ']['.
			$field_name = '[' . substr_replace( $field_name, '][', $pos, strlen( '[' ) );
		} else {
			$field_name = '[' . $field_name . ']';
		}

		return 'widget-' . $this->id_base . '[' . $this->number . ']' . $field_name;
	}

	/**
	 * Tạo thuộc tính id để sử dụng trong các trường của WP_Widget::form().
	 *
	 * Hàm này nên được sử dụng trong các phương thức form() để tạo thuộc tính id
	 * cho các trường sẽ được lưu bởi WP_Widget::update().
	 *
	 * @since 2.8.0
	 * @since 4.4.0 ID trường dạng mảng giờ đã được hỗ trợ.
	 *
	 * @param string $field_name Tên trường.
	 * @return string Thuộc tính ID cho `$field_name`.
	 */
	public function get_field_id( $field_name ) {
		$field_name = str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name );
		$field_name = trim( $field_name, '-' );

		return 'widget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
	}

	/**
	 * Đăng ký tất cả thể hiện widget của lớp widget này.
	 *
	 * @since 2.8.0
	 */
	public function _register() {
		$settings = $this->get_settings();
		$empty    = true;

		// Khi $settings là đối tượng dạng mảng, lấy mảng nội tại để sử dụng với array_keys().
		if ( $settings instanceof ArrayObject || $settings instanceof ArrayIterator ) {
			$settings = $settings->getArrayCopy();
		}

		if ( is_array( $settings ) ) {
			foreach ( array_keys( $settings ) as $number ) {
				if ( is_numeric( $number ) ) {
					$this->_set( $number );
					$this->_register_one( $number );
					$empty = false;
				}
			}
		}

		if ( $empty ) {
			// Nếu không có thể hiện nào, đăng ký sự tồn tại của widget với mẫu chung.
			$this->_set( 1 );
			$this->_register_one();
		}
	}

	/**
	 * Thiết lập số thứ tự nội bộ cho thể hiện widget.
	 *
	 * @since 2.8.0
	 *
	 * @param int $number Số thứ tự duy nhất của thể hiện widget này so với các
	 *                    thể hiện khác của cùng lớp.
	 */
	public function _set( $number ) {
		$this->number = $number;
		$this->id     = $this->id_base . '-' . $number;
	}

	/**
	 * Lấy callback hiển thị widget.
	 *
	 * @since 2.8.0
	 *
	 * @return callable Callback hiển thị.
	 */
	public function _get_display_callback() {
		return array( $this, 'display_callback' );
	}

	/**
	 * Lấy callback cập nhật widget.
	 *
	 * @since 2.8.0
	 *
	 * @return callable Callback cập nhật.
	 */
	public function _get_update_callback() {
		return array( $this, 'update_callback' );
	}

	/**
	 * Lấy callback biểu mẫu.
	 *
	 * @since 2.8.0
	 *
	 * @return callable Callback biểu mẫu.
	 */
	public function _get_form_callback() {
		return array( $this, 'form_callback' );
	}

	/**
	 * Xác định xem yêu cầu hiện tại có đang trong bản xem trước Customizer hay không.
	 *
	 * Nếu true -- yêu cầu hiện tại nằm trong bản xem trước Customizer, thì
	 * bộ nhớ đệm đối tượng bị tạm dừng và widget nên kiểm tra điều này để quyết định
	 * liệu có nên lưu trữ bất cứ gì vĩnh viễn vào bộ nhớ đệm đối tượng,
	 * transients, hoặc bất kỳ nơi nào khác hay không.
	 *
	 * @since 3.9.0
	 *
	 * @global WP_Customize_Manager $wp_customize
	 *
	 * @return bool True nếu đang trong bản xem trước Customizer, false nếu không.
	 */
	public function is_preview() {
		global $wp_customize;
		return ( isset( $wp_customize ) && $wp_customize->is_preview() );
	}

	/**
	 * Tạo nội dung widget thực tế (KHÔNG ghi đè).
	 *
	 * Tìm thể hiện và gọi WP_Widget::widget().
	 *
	 * @since 2.8.0
	 *
	 * @param array     $args        Các tham số hiển thị. Xem WP_Widget::widget() để biết thông tin
	 *                               về các tham số chấp nhận.
	 * @param int|array $widget_args {
	 *     Tùy chọn. Số thứ tự nội bộ của thể hiện widget, hoặc mảng tham số đa widget.
	 *     Mặc định 1.
	 *
	 *     @type int $number Số tăng dần dùng cho nhiều widget cùng loại.
	 * }
	 */
	public function display_callback( $args, $widget_args = 1 ) {
		if ( is_numeric( $widget_args ) ) {
			$widget_args = array( 'number' => $widget_args );
		}

		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		$this->_set( $widget_args['number'] );
		$instances = $this->get_settings();

		if ( isset( $instances[ $this->number ] ) ) {
			$instance = $instances[ $this->number ];

			/**
			 * Lọc cài đặt cho một thể hiện widget cụ thể.
			 *
			 * Trả về false sẽ hiệu quả ngắn mạch việc hiển thị widget.
			 *
			 * @since 2.8.0
			 *
			 * @param array     $instance Cài đặt của thể hiện widget hiện tại.
			 * @param WP_Widget $widget   Thể hiện widget hiện tại.
			 * @param array     $args     Mảng các tham số widget mặc định.
			 */
			$instance = apply_filters( 'widget_display_callback', $instance, $this, $args );

			if ( false === $instance ) {
				return;
			}

			$was_cache_addition_suspended = wp_suspend_cache_addition();
			if ( $this->is_preview() && ! $was_cache_addition_suspended ) {
				wp_suspend_cache_addition( true );
			}

			$this->widget( $args, $instance );

			if ( $this->is_preview() ) {
				wp_suspend_cache_addition( $was_cache_addition_suspended );
			}
		}
	}

	/**
	 * Xử lý cài đặt thay đổi (KHÔNG ghi đè).
	 *
	 * @since 2.8.0
	 *
	 * @global array $wp_registered_widgets
	 *
	 * @param int $deprecated Không sử dụng.
	 */
	public function update_callback( $deprecated = 1 ) {
		global $wp_registered_widgets;

		$all_instances = $this->get_settings();

		// Chúng ta cần cập nhật dữ liệu.
		if ( $this->updated ) {
			return;
		}

		if ( isset( $_POST['delete_widget'] ) && $_POST['delete_widget'] ) {
			// Xóa cài đặt cho thể hiện widget này.
			if ( isset( $_POST['the-widget-id'] ) ) {
				$del_id = $_POST['the-widget-id'];
			} else {
				return;
			}

			if ( isset( $wp_registered_widgets[ $del_id ]['params'][0]['number'] ) ) {
				$number = $wp_registered_widgets[ $del_id ]['params'][0]['number'];

				if ( $this->id_base . '-' . $number === $del_id ) {
					unset( $all_instances[ $number ] );
				}
			}
		} else {
			if ( isset( $_POST[ 'widget-' . $this->id_base ] ) && is_array( $_POST[ 'widget-' . $this->id_base ] ) ) {
				$settings = $_POST[ 'widget-' . $this->id_base ];
			} elseif ( isset( $_POST['id_base'] ) && $_POST['id_base'] === $this->id_base ) {
				$num      = $_POST['multi_number'] ? (int) $_POST['multi_number'] : (int) $_POST['widget_number'];
				$settings = array( $num => array() );
			} else {
				return;
			}

			foreach ( $settings as $number => $new_instance ) {
				$new_instance = stripslashes_deep( $new_instance );
				$this->_set( $number );

				$old_instance = isset( $all_instances[ $number ] ) ? $all_instances[ $number ] : array();

				$was_cache_addition_suspended = wp_suspend_cache_addition();
				if ( $this->is_preview() && ! $was_cache_addition_suspended ) {
					wp_suspend_cache_addition( true );
				}

				$instance = $this->update( $new_instance, $old_instance );

				if ( $this->is_preview() ) {
					wp_suspend_cache_addition( $was_cache_addition_suspended );
				}

				/**
				 * Lọc cài đặt widget trước khi lưu.
				 *
				 * Trả về false sẽ hiệu quả ngắn mạch khả năng cập nhật cài đặt
				 * của widget.
				 *
				 * @since 2.8.0
				 *
				 * @param array     $instance     Cài đặt của thể hiện widget hiện tại.
				 * @param array     $new_instance Mảng cài đặt widget mới.
				 * @param array     $old_instance Mảng cài đặt widget cũ.
				 * @param WP_Widget $widget       Thể hiện widget hiện tại.
				 */
				$instance = apply_filters( 'widget_update_callback', $instance, $new_instance, $old_instance, $this );

				if ( false !== $instance ) {
					$all_instances[ $number ] = $instance;
				}

				break; // Chỉ chạy một lần.
			}
		}

		$this->save_settings( $all_instances );
		$this->updated = true;
	}

	/**
	 * Tạo biểu mẫu điều khiển widget (KHÔNG ghi đè).
	 *
	 * @since 2.8.0
	 *
	 * @param int|array $widget_args {
	 *     Tùy chọn. Số thứ tự nội bộ của thể hiện widget, hoặc mảng tham số đa widget.
	 *     Mặc định 1.
	 *
	 *     @type int $number Số tăng dần dùng cho nhiều widget cùng loại.
	 * }
	 * @return string|null
	 */
	public function form_callback( $widget_args = 1 ) {
		if ( is_numeric( $widget_args ) ) {
			$widget_args = array( 'number' => $widget_args );
		}

		$widget_args   = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		$all_instances = $this->get_settings();

		if ( -1 === $widget_args['number'] ) {
			// Xuất biểu mẫu nơi 'number' có thể được thiết lập sau.
			$this->_set( '__i__' );
			$instance = array();
		} else {
			$this->_set( $widget_args['number'] );
			$instance = $all_instances[ $widget_args['number'] ];
		}

		/**
		 * Lọc cài đặt thể hiện widget trước khi hiển thị biểu mẫu điều khiển.
		 *
		 * Trả về false sẽ hiệu quả ngắn mạch việc hiển thị biểu mẫu điều khiển.
		 *
		 * @since 2.8.0
		 *
		 * @param array     $instance Cài đặt của thể hiện widget hiện tại.
		 * @param WP_Widget $widget   Thể hiện widget hiện tại.
		 */
		$instance = apply_filters( 'widget_form_callback', $instance, $this );

		$return = null;

		if ( false !== $instance ) {
			$return = $this->form( $instance );

			/**
			 * Kích hoạt ở cuối biểu mẫu điều khiển widget.
			 *
			 * Sử dụng hook này để thêm trường bổ sung vào biểu mẫu widget. Hook
			 * chỉ được kích hoạt nếu giá trị truyền cho hook 'widget_form_callback'
			 * không phải false.
			 *
			 * Lưu ý: Nếu widget không có biểu mẫu, văn bản được xuất từ phương thức
			 * biểu mẫu mặc định có thể được ẩn bằng CSS.
			 *
			 * @since 2.8.0
			 *
			 * @param WP_Widget $widget   Thể hiện widget (truyền theo tham chiếu).
			 * @param null      $return   Trả về null nếu có trường mới được thêm.
			 * @param array     $instance Mảng cài đặt của widget.
			 */
			do_action_ref_array( 'in_widget_form', array( &$this, &$return, $instance ) );
		}

		return $return;
	}

	/**
	 * Đăng ký một thể hiện của lớp widget.
	 *
	 * @since 2.8.0
	 *
	 * @param int $number Tùy chọn. Số thứ tự duy nhất của thể hiện widget này
	 *                    so với các thể hiện khác của cùng lớp. Mặc định -1.
	 */
	public function _register_one( $number = -1 ) {
		wp_register_sidebar_widget(
			$this->id,
			$this->name,
			$this->_get_display_callback(),
			$this->widget_options,
			array( 'number' => $number )
		);

		_register_widget_update_callback(
			$this->id_base,
			$this->_get_update_callback(),
			$this->control_options,
			array( 'number' => -1 )
		);

		_register_widget_form_callback(
			$this->id,
			$this->name,
			$this->_get_form_callback(),
			$this->control_options,
			array( 'number' => $number )
		);
	}

	/**
	 * Lưu cài đặt cho tất cả thể hiện của lớp widget.
	 *
	 * @since 2.8.0
	 *
	 * @param array $settings Mảng đa chiều cài đặt thể hiện widget.
	 */
	public function save_settings( $settings ) {
		$settings['_multiwidget'] = 1;
		update_option( $this->option_name, $settings );
	}

	/**
	 * Lấy cài đặt cho tất cả thể hiện của lớp widget.
	 *
	 * @since 2.8.0
	 *
	 * @return array Mảng đa chiều cài đặt thể hiện widget.
	 */
	public function get_settings() {

		$settings = get_option( $this->option_name );

		if ( false === $settings ) {
			$settings = array();
			if ( isset( $this->alt_option_name ) ) {
				// Lấy cài đặt từ tùy chọn thay thế (cũ).
				$settings = get_option( $this->alt_option_name, array() );

				// Xóa tùy chọn thay thế (cũ) vì tùy chọn mới sẽ được tạo bằng `$this->option_name`.
				delete_option( $this->alt_option_name );
			}
			// Lưu tùy chọn để có thể tự động tải lần sau.
			$this->save_settings( $settings );
		}

		if ( ! is_array( $settings ) && ! ( $settings instanceof ArrayObject || $settings instanceof ArrayIterator ) ) {
			$settings = array();
		}

		if ( ! empty( $settings ) && ! isset( $settings['_multiwidget'] ) ) {
			// Định dạng cũ, chuyển đổi nếu là widget đơn.
			$settings = wp_convert_widget_settings( $this->id_base, $this->option_name, $settings );
		}

		unset( $settings['_multiwidget'], $settings['__i__'] );

		return $settings;
	}
}
