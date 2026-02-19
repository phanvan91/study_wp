<?php
/**
 * Các lớp Cài đặt Tùy biến WordPress.
 *
 * @package WordPress
 * @subpackage Customize
 * @since 3.4.0
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Lớp Cài đặt Tùy biến.
 *
 * Xử lý việc lưu và làm sạch các cài đặt.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Manager
 * @link https://developer.wordpress.org/themes/customize-api
 */
#[AllowDynamicProperties]
class WP_Customize_Setting {
	/**
	 * Đối tượng khởi tạo Customizer.
	 *
	 * @since 3.4.0
	 * @var WP_Customize_Manager
	 */
	public $manager;

	/**
	 * Chuỗi định danh duy nhất cho cài đặt.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $id;

	/**
	 * Loại cài đặt tùy biến.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $type = 'theme_mod';

	/**
	 * Quyền cần thiết để chỉnh sửa cài đặt này.
	 *
	 * @since 3.4.0
	 * @var string|array
	 */
	public $capability = 'edit_theme_options';

	/**
	 * Các tính năng giao diện cần thiết để hỗ trợ cài đặt.
	 *
	 * @since 3.4.0
	 * @var string|string[]
	 */
	public $theme_supports = '';

	/**
	 * Giá trị mặc định cho cài đặt.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $default = '';

	/**
	 * Tùy chọn cho việc hiển thị xem trước trực tiếp các thay đổi trong Customizer.
	 *
	 * Đặt giá trị này thành 'postMessage' để kích hoạt trình xử lý JavaScript tùy chỉnh
	 * hiển thị các thay đổi cho cài đặt này thay vì tải lại toàn bộ trang.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $transport = 'refresh';

	/**
	 * Callback xác thực phía máy chủ cho giá trị của cài đặt.
	 *
	 * @since 4.6.0
	 * @var callable
	 */
	public $validate_callback = '';

	/**
	 * Callback để lọc giá trị cài đặt Tùy biến ở dạng chưa thêm dấu gạch chéo.
	 *
	 * @since 3.4.0
	 * @var callable
	 */
	public $sanitize_callback = '';

	/**
	 * Callback để chuyển đổi giá trị cài đặt PHP Tùy biến thành giá trị có thể tuần tự hóa JSON.
	 *
	 * @since 3.4.0
	 * @var callable
	 */
	public $sanitize_js_callback = '';

	/**
	 * Cài đặt có bị thay đổi (dirty) khi được tạo hay không.
	 *
	 * Điều này được sử dụng để đảm bảo rằng cài đặt sẽ được gửi từ bảng điều khiển
	 * đến phần xem trước khi tải Customizer. Thông thường cài đặt chỉ được đồng bộ
	 * với phần xem trước nếu nó đã được thay đổi. Điều này cho phép cài đặt được
	 * gửi ngay từ đầu.
	 *
	 * @since 4.2.0
	 * @var bool
	 */
	public $dirty = false;

	/**
	 * Dữ liệu ID.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	protected $id_data = array();

	/**
	 * preview() đã được gọi hay chưa.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $is_previewed = false;

	/**
	 * Bộ nhớ đệm các giá trị đa chiều để cải thiện hiệu suất.
	 *
	 * @since 4.4.0
	 * @var array
	 */
	protected static $aggregated_multidimensionals = array();

	/**
	 * Cài đặt đa chiều có được tổng hợp hay không.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $is_multidimensional_aggregated = false;

	/**
	 * Hàm khởi tạo.
	 *
	 * Bất kỳ $args nào được cung cấp sẽ ghi đè các giá trị mặc định của thuộc tính lớp.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Customize_Manager $manager Đối tượng khởi tạo Customizer.
	 * @param string               $id      ID cụ thể của cài đặt.
	 *                                      Có thể là tên theme mod hoặc option.
	 * @param array                $args    {
	 *     Tùy chọn. Mảng các thuộc tính cho đối tượng Setting mới. Mặc định là mảng rỗng.
	 *
	 *     @type string          $type                 Loại cài đặt. Mặc định 'theme_mod'.
	 *     @type string          $capability           Quyền cần thiết cho cài đặt. Mặc định 'edit_theme_options'.
	 *     @type string|string[] $theme_supports       Các tính năng giao diện cần thiết để hỗ trợ bảng điều khiển. Mặc định không có.
	 *     @type string          $default              Giá trị mặc định cho cài đặt. Mặc định là chuỗi rỗng.
	 *     @type string          $transport            Tùy chọn hiển thị xem trước trực tiếp các thay đổi trong Customizer.
	 *                                                 Sử dụng 'refresh' làm thay đổi hiển thị bằng cách tải lại toàn bộ xem trước.
	 *                                                 Sử dụng 'postMessage' cho phép JavaScript tùy chỉnh xử lý thay đổi trực tiếp.
	 *                                                 Mặc định là 'refresh'.
	 *     @type callable        $validate_callback    Callback xác thực phía máy chủ cho giá trị cài đặt.
	 *     @type callable        $sanitize_callback    Callback để lọc giá trị cài đặt Tùy biến ở dạng chưa thêm dấu gạch chéo.
	 *     @type callable        $sanitize_js_callback Callback để chuyển đổi giá trị cài đặt PHP Tùy biến thành giá trị
	 *                                                 có thể tuần tự hóa JSON.
	 *     @type bool            $dirty                Cài đặt có bị thay đổi (dirty) khi được tạo hay không.
	 * }
	 */
	public function __construct( $manager, $id, $args = array() ) {
		$keys = array_keys( get_object_vars( $this ) );
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$this->$key = $args[ $key ];
			}
		}

		$this->manager = $manager;
		$this->id      = $id;

		// Phân tích ID để lấy các khóa mảng.
		$this->id_data['keys'] = preg_split( '/\[/', str_replace( ']', '', $this->id ) );
		$this->id_data['base'] = array_shift( $this->id_data['keys'] );

		// Xây dựng lại ID.
		$this->id = $this->id_data['base'];
		if ( ! empty( $this->id_data['keys'] ) ) {
			$this->id .= '[' . implode( '][', $this->id_data['keys'] ) . ']';
		}

		if ( $this->validate_callback ) {
			add_filter( "customize_validate_{$this->id}", $this->validate_callback, 10, 3 );
		}
		if ( $this->sanitize_callback ) {
			add_filter( "customize_sanitize_{$this->id}", $this->sanitize_callback, 10, 2 );
		}
		if ( $this->sanitize_js_callback ) {
			add_filter( "customize_sanitize_js_{$this->id}", $this->sanitize_js_callback, 10, 2 );
		}

		if ( 'option' === $this->type || 'theme_mod' === $this->type ) {
			// Các loại cài đặt khác có thể chọn tham gia tổng hợp đa chiều một cách rõ ràng.
			$this->aggregate_multidimensional();

			// Cho phép các cài đặt option chỉ định liệu chúng có nên được tự động tải hay không.
			if ( 'option' === $this->type && isset( $args['autoload'] ) ) {
				self::$aggregated_multidimensionals[ $this->type ][ $this->id_data['base'] ]['autoload'] = $args['autoload'];
			}
		}
	}

	/**
	 * Lấy dữ liệu ID đã phân tích cho cài đặt đa chiều.
	 *
	 * @since 4.4.0
	 *
	 * @return array {
	 *     Dữ liệu ID cho cài đặt đa chiều.
	 *
	 *     @type string $base ID gốc.
	 *     @type array  $keys Các khóa cho mảng đa chiều.
	 * }
	 */
	final public function id_data() {
		return $this->id_data;
	}

	/**
	 * Thiết lập cài đặt cho các giá trị đa chiều tổng hợp.
	 *
	 * Khi một cài đặt đa chiều được tổng hợp, tất cả các lệnh gọi xem trước và cập nhật
	 * của nó được kết hợp thành một lệnh gọi duy nhất, cải thiện đáng kể hiệu suất.
	 *
	 * @since 4.4.0
	 */
	protected function aggregate_multidimensional() {
		$id_base = $this->id_data['base'];
		if ( ! isset( self::$aggregated_multidimensionals[ $this->type ] ) ) {
			self::$aggregated_multidimensionals[ $this->type ] = array();
		}
		if ( ! isset( self::$aggregated_multidimensionals[ $this->type ][ $id_base ] ) ) {
			self::$aggregated_multidimensionals[ $this->type ][ $id_base ] = array(
				'previewed_instances'       => array(), // Gọi preview() sẽ thêm $setting vào mảng.
				'preview_applied_instances' => array(), // Cờ đánh dấu các cài đặt đã được áp dụng giá trị.
				'root_value'                => $this->get_root_value( array() ), // Giá trị gốc cho trạng thái ban đầu, được thao tác bởi các lệnh gọi preview và update.
			);
		}

		if ( ! empty( $this->id_data['keys'] ) ) {
			// Lưu ý cờ preview-applied được xóa ở priority 9 để đảm bảo nó được xóa trước khi một deferred-preview chạy.
			add_action( "customize_post_value_set_{$this->id}", array( $this, '_clear_aggregated_multidimensional_preview_applied_flag' ), 9 );
			$this->is_multidimensional_aggregated = true;
		}
	}

	/**
	 * Đặt lại biến tĩnh `$aggregated_multidimensionals`.
	 *
	 * Chỉ dành cho việc sử dụng bởi các bài kiểm tra đơn vị (unit tests).
	 *
	 * @since 4.5.0
	 * @ignore
	 */
	public static function reset_aggregated_multidimensionals() {
		self::$aggregated_multidimensionals = array();
	}

	/**
	 * ID của trang web hiện tại khi phương thức preview() được gọi.
	 *
	 * @since 4.2.0
	 * @var int
	 */
	protected $_previewed_blog_id;

	/**
	 * Trả về true nếu trang web hiện tại không giống với trang web đang xem trước.
	 *
	 * @since 4.2.0
	 *
	 * @return bool Liệu preview() đã được gọi hay chưa.
	 */
	public function is_current_blog_previewed() {
		if ( ! isset( $this->_previewed_blog_id ) ) {
			return false;
		}
		return ( get_current_blog_id() === $this->_previewed_blog_id );
	}

	/**
	 * Giá trị gốc chưa xem trước được lưu bởi phương thức preview.
	 *
	 * @see WP_Customize_Setting::preview()
	 * @since 4.1.1
	 * @var mixed
	 */
	protected $_original_value;

	/**
	 * Thêm các bộ lọc để cung cấp giá trị của cài đặt khi được truy cập.
	 *
	 * Nếu cài đặt đã có giá trị sẵn và không có giá trị post đến cho cài đặt,
	 * thì phương thức này sẽ bỏ qua vì không có thay đổi nào cần xem trước.
	 *
	 * @since 3.4.0
	 * @since 4.4.0 Thêm giá trị trả về boolean.
	 *
	 * @return bool False khi xem trước bị bỏ qua vì không có thay đổi nào cần xem trước.
	 */
	public function preview() {
		if ( ! isset( $this->_previewed_blog_id ) ) {
			$this->_previewed_blog_id = get_current_blog_id();
		}

		// Ngăn việc xem trước lại một cài đặt đã được xem trước.
		if ( $this->is_previewed ) {
			return true;
		}

		$id_base                 = $this->id_data['base'];
		$is_multidimensional     = ! empty( $this->id_data['keys'] );
		$multidimensional_filter = array( $this, '_multidimensional_preview_filter' );

		/*
		 * Kiểm tra xem cài đặt có giá trị sẵn hay không (kiểm tra isset),
		 * và liệu có giá trị post đến hay không. Nếu cả hai kiểm tra đều đúng,
		 * thì xem trước sẽ bị bỏ qua vì không có gì cần xem trước.
		 */
		$undefined     = new stdClass();
		$needs_preview = ( $undefined !== $this->post_value( $undefined ) );
		$value         = null;

		// Vì không có giá trị post nào được định nghĩa, kiểm tra xem có giá trị ban đầu được đặt hay không.
		if ( ! $needs_preview ) {
			if ( $this->is_multidimensional_aggregated ) {
				$root  = self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['root_value'];
				$value = $this->multidimensional_get( $root, $this->id_data['keys'], $undefined );
			} else {
				$default       = $this->default;
				$this->default = $undefined; // Tạm thời đặt mặc định thành undefined để phát hiện nếu giá trị hiện có được đặt.
				$value         = $this->value();
				$this->default = $default;
			}
			$needs_preview = ( $undefined === $value ); // Vì giá trị mặc định cần được cung cấp.
		}

		// Nếu cài đặt không cần xem trước ngay bây giờ, hoãn lại đến khi nó có giá trị để xem trước.
		if ( ! $needs_preview ) {
			if ( ! has_action( "customize_post_value_set_{$this->id}", array( $this, 'preview' ) ) ) {
				add_action( "customize_post_value_set_{$this->id}", array( $this, 'preview' ) );
			}
			return false;
		}

		switch ( $this->type ) {
			case 'theme_mod':
				if ( ! $is_multidimensional ) {
					add_filter( "theme_mod_{$id_base}", array( $this, '_preview_filter' ) );
				} else {
					if ( empty( self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['previewed_instances'] ) ) {
						// Chỉ thêm bộ lọc này một lần cho ID gốc này.
						add_filter( "theme_mod_{$id_base}", $multidimensional_filter );
					}
					self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['previewed_instances'][ $this->id ] = $this;
				}
				break;
			case 'option':
				if ( ! $is_multidimensional ) {
					add_filter( "pre_option_{$id_base}", array( $this, '_preview_filter' ) );
				} else {
					if ( empty( self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['previewed_instances'] ) ) {
						// Chỉ thêm các bộ lọc này một lần cho ID gốc này.
						add_filter( "option_{$id_base}", $multidimensional_filter );
						add_filter( "default_option_{$id_base}", $multidimensional_filter );
					}
					self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['previewed_instances'][ $this->id ] = $this;
				}
				break;
			default:
				/**
				 * Kích hoạt khi phương thức WP_Customize_Setting::preview() được gọi cho các cài đặt
				 * không được xử lý dưới dạng theme_mods hoặc options.
				 *
				 * Phần động của tên hook, `$this->id`, tham chiếu đến ID cài đặt.
				 *
				 * @since 3.4.0
				 *
				 * @param WP_Customize_Setting $setting Đối tượng WP_Customize_Setting.
				 */
				do_action( "customize_preview_{$this->id}", $this );

				/**
				 * Kích hoạt khi phương thức WP_Customize_Setting::preview() được gọi cho các cài đặt
				 * không được xử lý dưới dạng theme_mods hoặc options.
				 *
				 * Phần động của tên hook, `$this->type`, tham chiếu đến loại cài đặt.
				 *
				 * @since 4.1.0
				 *
				 * @param WP_Customize_Setting $setting Đối tượng WP_Customize_Setting.
				 */
				do_action( "customize_preview_{$this->type}", $this );
		}

		$this->is_previewed = true;

		return true;
	}

	/**
	 * Xóa cờ đã áp dụng xem trước cho giá trị tổng hợp đa chiều mỗi khi giá trị post của nó được cập nhật.
	 *
	 * Điều này đảm bảo rằng giá trị mới sẽ được làm sạch và sử dụng vào lần tiếp theo
	 * mà `WP_Customize_Setting::_multidimensional_preview_filter()`
	 * được gọi cho cài đặt này.
	 *
	 * @since 4.4.0
	 *
	 * @see WP_Customize_Manager::set_post_value()
	 * @see WP_Customize_Setting::_multidimensional_preview_filter()
	 */
	final public function _clear_aggregated_multidimensional_preview_applied_flag() {
		unset( self::$aggregated_multidimensionals[ $this->type ][ $this->id_data['base'] ]['preview_applied_instances'][ $this->id ] );
	}

	/**
	 * Hàm callback để lọc các theme mods và options không phải đa chiều.
	 *
	 * Nếu switch_to_blog() được gọi sau phương thức preview(), và trang web hiện tại
	 * không còn là cùng một trang web, thì phương thức này không thực hiện gì và trả về
	 * giá trị gốc.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $original Giá trị cũ.
	 * @return mixed Giá trị mới hoặc cũ.
	 */
	public function _preview_filter( $original ) {
		if ( ! $this->is_current_blog_previewed() ) {
			return $original;
		}

		$undefined  = new stdClass(); // Mẹo dùng symbol.
		$post_value = $this->post_value( $undefined );
		if ( $undefined !== $post_value ) {
			$value = $post_value;
		} else {
			/*
			 * Lưu ý rằng chúng ta không dùng $original ở đây vì preview() sẽ
			 * không thêm bộ lọc ngay từ đầu nếu nó có giá trị ban đầu
			 * và không có giá trị post.
			 */
			$value = $this->default;
		}
		return $value;
	}

	/**
	 * Hàm callback để lọc các theme mods và options đa chiều.
	 *
	 * Đối với tất cả các cài đặt đa chiều cùng loại, bộ lọc xem trước của
	 * cài đặt đầu tiên được xem trước sẽ được sử dụng để áp dụng giá trị cho các cài đặt khác.
	 *
	 * @since 4.4.0
	 *
	 * @see WP_Customize_Setting::$aggregated_multidimensionals
	 * @param mixed $original Giá trị gốc ban đầu.
	 * @return mixed Giá trị mới hoặc cũ.
	 */
	final public function _multidimensional_preview_filter( $original ) {
		if ( ! $this->is_current_blog_previewed() ) {
			return $original;
		}

		$id_base = $this->id_data['base'];

		// Nếu chưa có cài đặt nào được xem trước (điều này không nên xảy ra vì $this đã có), chỉ cần truyền qua giá trị gốc.
		if ( empty( self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['previewed_instances'] ) ) {
			return $original;
		}

		foreach ( self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['previewed_instances'] as $previewed_setting ) {
			// Bỏ qua việc áp dụng giá trị xem trước cho bất kỳ cài đặt nào đã được áp dụng.
			if ( ! empty( self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['preview_applied_instances'][ $previewed_setting->id ] ) ) {
				continue;
			}

			// Thực hiện thay thế giá trị con đã đăng/mặc định vào giá trị gốc.
			$value = $previewed_setting->post_value( $previewed_setting->default );
			$root  = self::$aggregated_multidimensionals[ $previewed_setting->type ][ $id_base ]['root_value'];
			$root  = $previewed_setting->multidimensional_replace( $root, $previewed_setting->id_data['keys'], $value );
			self::$aggregated_multidimensionals[ $previewed_setting->type ][ $id_base ]['root_value'] = $root;

			// Mark this setting having been applied so that it will be skipped when the filter is called again.
			self::$aggregated_multidimensionals[ $previewed_setting->type ][ $id_base ]['preview_applied_instances'][ $previewed_setting->id ] = true;
		}

		return self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['root_value'];
	}

	/**
	 * Checks user capabilities and theme supports, and then saves
	 * the value of the setting.
	 *
	 * @since 3.4.0
	 *
	 * @return void|false Void on success, false if cap check fails
	 *                    or value isn't set or is invalid.
	 */
	final public function save() {
		$value = $this->post_value();

		if ( ! $this->check_capabilities() || ! isset( $value ) ) {
			return false;
		}

		$id_base = $this->id_data['base'];

		/**
		 * Fires when the WP_Customize_Setting::save() method is called.
		 *
		 * The dynamic portion of the hook name, `$id_base` refers to
		 * the base slug of the setting name.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
		 */
		do_action( "customize_save_{$id_base}", $this );

		$this->update( $value );
	}

	/**
	 * Fetch and sanitize the $_POST value for the setting.
	 *
	 * During a save request prior to save, post_value() provides the new value while value() does not.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $default_value A default value which is used as a fallback. Default null.
	 * @return mixed The default value on failure, otherwise the sanitized and validated value.
	 */
	final public function post_value( $default_value = null ) {
		return $this->manager->post_value( $this, $default_value );
	}

	/**
	 * Sanitize an input.
	 *
	 * @since 3.4.0
	 *
	 * @param string|array $value The value to sanitize.
	 * @return string|array|null|WP_Error Sanitized value, or `null`/`WP_Error` if invalid.
	 */
	public function sanitize( $value ) {

		/**
		 * Filters a Customize setting value in un-slashed form.
		 *
		 * @since 3.4.0
		 *
		 * @param mixed                $value   Value of the setting.
		 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
		 */
		return apply_filters( "customize_sanitize_{$this->id}", $value, $this );
	}

	/**
	 * Validates an input.
	 *
	 * @since 4.6.0
	 *
	 * @see WP_REST_Request::has_valid_params()
	 *
	 * @param mixed $value Value to validate.
	 * @return true|WP_Error True if the input was validated, otherwise WP_Error.
	 */
	public function validate( $value ) {
		if ( is_wp_error( $value ) ) {
			return $value;
		}
		if ( is_null( $value ) ) {
			return new WP_Error( 'invalid_value', __( 'Invalid value.' ) );
		}

		$validity = new WP_Error();

		/**
		 * Validates a Customize setting value.
		 *
		 * Plugins should amend the `$validity` object via its `WP_Error::add()` method.
		 *
		 * The dynamic portion of the hook name, `$this->ID`, refers to the setting ID.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Error             $validity Filtered from `true` to `WP_Error` when invalid.
		 * @param mixed                $value    Value of the setting.
		 * @param WP_Customize_Setting $setting  WP_Customize_Setting instance.
		 */
		$validity = apply_filters( "customize_validate_{$this->id}", $validity, $value, $this );

		if ( is_wp_error( $validity ) && ! $validity->has_errors() ) {
			$validity = true;
		}
		return $validity;
	}

	/**
	 * Get the root value for a setting, especially for multidimensional ones.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $default_value Value to return if root does not exist.
	 * @return mixed
	 */
	protected function get_root_value( $default_value = null ) {
		$id_base = $this->id_data['base'];
		if ( 'option' === $this->type ) {
			return get_option( $id_base, $default_value );
		} elseif ( 'theme_mod' === $this->type ) {
			return get_theme_mod( $id_base, $default_value );
		} else {
			/*
			 * Any WP_Customize_Setting subclass implementing aggregate multidimensional
			 * will need to override this method to obtain the data from the appropriate
			 * location.
			 */
			return $default_value;
		}
	}

	/**
	 * Set the root value for a setting, especially for multidimensional ones.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $value Value to set as root of multidimensional setting.
	 * @return bool Whether the multidimensional root was updated successfully.
	 */
	protected function set_root_value( $value ) {
		$id_base = $this->id_data['base'];
		if ( 'option' === $this->type ) {
			$autoload = true;
			if ( isset( self::$aggregated_multidimensionals[ $this->type ][ $this->id_data['base'] ]['autoload'] ) ) {
				$autoload = self::$aggregated_multidimensionals[ $this->type ][ $this->id_data['base'] ]['autoload'];
			}
			return update_option( $id_base, $value, $autoload );
		} elseif ( 'theme_mod' === $this->type ) {
			set_theme_mod( $id_base, $value );
			return true;
		} else {
			/*
			 * Any WP_Customize_Setting subclass implementing aggregate multidimensional
			 * will need to override this method to obtain the data from the appropriate
			 * location.
			 */
			return false;
		}
	}

	/**
	 * Save the value of the setting, using the related API.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $value The value to update.
	 * @return bool The result of saving the value.
	 */
	protected function update( $value ) {
		$id_base = $this->id_data['base'];
		if ( 'option' === $this->type || 'theme_mod' === $this->type ) {
			if ( ! $this->is_multidimensional_aggregated ) {
				return $this->set_root_value( $value );
			} else {
				$root = self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['root_value'];
				$root = $this->multidimensional_replace( $root, $this->id_data['keys'], $value );
				self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['root_value'] = $root;
				return $this->set_root_value( $root );
			}
		} else {
			/**
			 * Fires when the WP_Customize_Setting::update() method is called for settings
			 * not handled as theme_mods or options.
			 *
			 * The dynamic portion of the hook name, `$this->type`, refers to the type of setting.
			 *
			 * @since 3.4.0
			 *
			 * @param mixed                $value   Value of the setting.
			 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
			 */
			do_action( "customize_update_{$this->type}", $value, $this );

			return has_action( "customize_update_{$this->type}" );
		}
	}

	/**
	 * Deprecated method.
	 *
	 * @since 3.4.0
	 * @deprecated 4.4.0 Deprecated in favor of update() method.
	 */
	protected function _update_theme_mod() {
		_deprecated_function( __METHOD__, '4.4.0', __CLASS__ . '::update()' );
	}

	/**
	 * Deprecated method.
	 *
	 * @since 3.4.0
	 * @deprecated 4.4.0 Deprecated in favor of update() method.
	 */
	protected function _update_option() {
		_deprecated_function( __METHOD__, '4.4.0', __CLASS__ . '::update()' );
	}

	/**
	 * Fetch the value of the setting.
	 *
	 * @since 3.4.0
	 *
	 * @return mixed The value.
	 */
	public function value() {
		$id_base      = $this->id_data['base'];
		$is_core_type = ( 'option' === $this->type || 'theme_mod' === $this->type );

		if ( ! $is_core_type && ! $this->is_multidimensional_aggregated ) {

			// Use post value if previewed and a post value is present.
			if ( $this->is_previewed ) {
				$value = $this->post_value( null );
				if ( null !== $value ) {
					return $value;
				}
			}

			$value = $this->get_root_value( $this->default );

			/**
			 * Filters a Customize setting value not handled as a theme_mod or option.
			 *
			 * The dynamic portion of the hook name, `$id_base`, refers to
			 * the base slug of the setting name, initialized from `$this->id_data['base']`.
			 *
			 * For settings handled as theme_mods or options, see those corresponding
			 * functions for available hooks.
			 *
			 * @since 3.4.0
			 * @since 4.6.0 Added the `$this` setting instance as the second parameter.
			 *
			 * @param mixed                $default_value The setting default value. Default empty.
			 * @param WP_Customize_Setting $setting       The setting instance.
			 */
			$value = apply_filters( "customize_value_{$id_base}", $value, $this );
		} elseif ( $this->is_multidimensional_aggregated ) {
			$root_value = self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['root_value'];
			$value      = $this->multidimensional_get( $root_value, $this->id_data['keys'], $this->default );

			// Ensure that the post value is used if the setting is previewed, since preview filters aren't applying on cached $root_value.
			if ( $this->is_previewed ) {
				$value = $this->post_value( $value );
			}
		} else {
			$value = $this->get_root_value( $this->default );
		}
		return $value;
	}

	/**
	 * Sanitize the setting's value for use in JavaScript.
	 *
	 * @since 3.4.0
	 *
	 * @return mixed The requested escaped value.
	 */
	public function js_value() {

		/**
		 * Filters a Customize setting value for use in JavaScript.
		 *
		 * The dynamic portion of the hook name, `$this->id`, refers to the setting ID.
		 *
		 * @since 3.4.0
		 *
		 * @param mixed                $value   The setting value.
		 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
		 */
		$value = apply_filters( "customize_sanitize_js_{$this->id}", $this->value(), $this );

		if ( is_string( $value ) ) {
			return html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
		}

		return $value;
	}

	/**
	 * Retrieves the data to export to the client via JSON.
	 *
	 * @since 4.6.0
	 *
	 * @return array Array of parameters passed to JavaScript.
	 */
	public function json() {
		return array(
			'value'     => $this->js_value(),
			'transport' => $this->transport,
			'dirty'     => $this->dirty,
			'type'      => $this->type,
		);
	}

	/**
	 * Validate user capabilities whether the theme supports the setting.
	 *
	 * @since 3.4.0
	 *
	 * @return bool False if theme doesn't support the setting or user can't change setting, otherwise true.
	 */
	final public function check_capabilities() {
		if ( $this->capability && ! current_user_can( $this->capability ) ) {
			return false;
		}

		if ( $this->theme_supports && ! current_theme_supports( ...(array) $this->theme_supports ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Multidimensional helper function.
	 *
	 * @since 3.4.0
	 *
	 * @param array $root
	 * @param array $keys
	 * @param bool  $create Default false.
	 * @return array|void Keys are 'root', 'node', and 'key'.
	 */
	final protected function multidimensional( &$root, $keys, $create = false ) {
		if ( $create && empty( $root ) ) {
			$root = array();
		}

		if ( ! isset( $root ) || empty( $keys ) ) {
			return;
		}

		$last = array_pop( $keys );
		$node = &$root;

		foreach ( $keys as $key ) {
			if ( $create && ! isset( $node[ $key ] ) ) {
				$node[ $key ] = array();
			}

			if ( ! is_array( $node ) || ! isset( $node[ $key ] ) ) {
				return;
			}

			$node = &$node[ $key ];
		}

		if ( $create ) {
			if ( ! is_array( $node ) ) {
				// Account for an array overriding a string or object value.
				$node = array();
			}
			if ( ! isset( $node[ $last ] ) ) {
				$node[ $last ] = array();
			}
		}

		if ( ! isset( $node[ $last ] ) ) {
			return;
		}

		return array(
			'root' => &$root,
			'node' => &$node,
			'key'  => $last,
		);
	}

	/**
	 * Will attempt to replace a specific value in a multidimensional array.
	 *
	 * @since 3.4.0
	 *
	 * @param array $root
	 * @param array $keys
	 * @param mixed $value The value to update.
	 * @return mixed
	 */
	final protected function multidimensional_replace( $root, $keys, $value ) {
		if ( ! isset( $value ) ) {
			return $root;
		} elseif ( empty( $keys ) ) { // If there are no keys, we're replacing the root.
			return $value;
		}

		$result = $this->multidimensional( $root, $keys, true );

		if ( isset( $result ) ) {
			$result['node'][ $result['key'] ] = $value;
		}

		return $root;
	}

	/**
	 * Will attempt to fetch a specific value from a multidimensional array.
	 *
	 * @since 3.4.0
	 *
	 * @param array $root
	 * @param array $keys
	 * @param mixed $default_value A default value which is used as a fallback. Default null.
	 * @return mixed The requested value or the default value.
	 */
	final protected function multidimensional_get( $root, $keys, $default_value = null ) {
		if ( empty( $keys ) ) { // If there are no keys, test the root.
			return isset( $root ) ? $root : $default_value;
		}

		$result = $this->multidimensional( $root, $keys );
		return isset( $result ) ? $result['node'][ $result['key'] ] : $default_value;
	}

	/**
	 * Will attempt to check if a specific value in a multidimensional array is set.
	 *
	 * @since 3.4.0
	 *
	 * @param array $root
	 * @param array $keys
	 * @return bool True if value is set, false if not.
	 */
	final protected function multidimensional_isset( $root, $keys ) {
		$result = $this->multidimensional_get( $root, $keys );
		return isset( $result );
	}
}

/**
 * WP_Customize_Filter_Setting class.
 */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-filter-setting.php';

/**
 * WP_Customize_Header_Image_Setting class.
 */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-header-image-setting.php';

/**
 * WP_Customize_Background_Image_Setting class.
 */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-background-image-setting.php';

/**
 * WP_Customize_Nav_Menu_Item_Setting class.
 */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-item-setting.php';

/**
 * WP_Customize_Nav_Menu_Setting class.
 */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-setting.php';
