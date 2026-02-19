<?php
/**
 * Các lớp Panel trong WordPress Customize
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.0.0
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Lớp Customize Panel.
 *
 * Một container giao diện cho các section, được quản lý bởi WP_Customize_Manager.
 *
 * @since 4.0.0
 *
 * @see WP_Customize_Manager
 */
#[AllowDynamicProperties]
class WP_Customize_Panel {

	/**
	 * Tăng dần với mỗi lần khởi tạo lớp mới, sau đó được lưu trong $instance_number.
	 *
	 * Được sử dụng khi sắp xếp hai thực thể có mức ưu tiên bằng nhau.
	 *
	 * @since 4.1.0
	 * @var int
	 */
	protected static $instance_count = 0;

	/**
	 * Thứ tự mà thực thể này được tạo so với các thực thể khác.
	 *
	 * @since 4.1.0
	 * @var int
	 */
	public $instance_number;

	/**
	 * Thực thể WP_Customize_Manager.
	 *
	 * @since 4.0.0
	 * @var WP_Customize_Manager
	 */
	public $manager;

	/**
	 * Định danh duy nhất.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	public $id;

	/**
	 * Mức ưu tiên của panel, xác định thứ tự hiển thị của các panel và section.
	 *
	 * @since 4.0.0
	 * @var int
	 */
	public $priority = 160;

	/**
	 * Quyền cần thiết cho panel.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	public $capability = 'edit_theme_options';

	/**
	 * Các tính năng theme cần thiết để hỗ trợ panel.
	 *
	 * @since 4.0.0
	 * @var mixed[]
	 */
	public $theme_supports = '';

	/**
	 * Tiêu đề của panel hiển thị trong giao diện.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	public $title = '';

	/**
	 * Mô tả hiển thị trong giao diện.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	public $description = '';

	/**
	 * Tự động mở rộng section trong panel khi panel được mở rộng và panel chỉ có một section duy nhất.
	 *
	 * @since 4.7.4
	 * @var bool
	 */
	public $auto_expand_sole_section = false;

	/**
	 * Các section Customizer cho panel này.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $sections;

	/**
	 * Loại của panel này.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $type = 'default';

	/**
	 * Callback kích hoạt.
	 *
	 * @since 4.1.0
	 *
	 * @see WP_Customize_Section::active()
	 *
	 * @var callable Callback được gọi với một tham số, thực thể của
	 *               WP_Customize_Section, và trả về bool để chỉ ra liệu
	 *               section có đang hoạt động hay không (liên quan đến URL đang
	 *               được xem trước).
	 */
	public $active_callback = '';

	/**
	 * Hàm khởi tạo.
	 *
	 * Bất kỳ $args nào được cung cấp sẽ ghi đè các giá trị mặc định của thuộc tính lớp.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Customize_Manager $manager Thực thể khởi tạo Customizer.
	 * @param string               $id      ID cụ thể cho panel.
	 * @param array                $args    {
	 *     Tùy chọn. Mảng các thuộc tính cho đối tượng Panel mới. Mặc định mảng rỗng.
	 *
	 *     @type int             $priority        Mức ưu tiên của panel, xác định thứ tự hiển thị
	 *                                            của các panel và section. Mặc định 160.
	 *     @type string          $capability      Quyền cần thiết cho panel.
	 *                                            Mặc định `edit_theme_options`.
	 *     @type mixed[]         $theme_supports  Các tính năng theme cần thiết để hỗ trợ panel.
	 *     @type string          $title           Tiêu đề của panel hiển thị trong giao diện.
	 *     @type string          $description     Mô tả hiển thị trong giao diện.
	 *     @type string          $type            Loại của panel.
	 *     @type callable        $active_callback Callback kích hoạt.
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
		if ( empty( $this->active_callback ) ) {
			$this->active_callback = array( $this, 'active_callback' );
		}
		self::$instance_count += 1;
		$this->instance_number = self::$instance_count;

		$this->sections = array(); // Người dùng không thể tùy chỉnh mảng $sections.
	}

	/**
	 * Kiểm tra xem panel có đang hoạt động với bản xem trước Customizer hiện tại không.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Liệu panel có đang hoạt động với bản xem trước hiện tại hay không.
	 */
	final public function active() {
		$panel  = $this;
		$active = call_user_func( $this->active_callback, $this );

		/**
		 * Lọc phản hồi của WP_Customize_Panel::active().
		 *
		 * @since 4.1.0
		 *
		 * @param bool               $active Liệu panel Customizer có đang hoạt động hay không.
		 * @param WP_Customize_Panel $panel  Thực thể WP_Customize_Panel.
		 */
		$active = apply_filters( 'customize_panel_active', $active, $panel );

		return $active;
	}

	/**
	 * Callback mặc định được sử dụng khi gọi WP_Customize_Panel::active().
	 *
	 * Các lớp con có thể ghi đè với logic cụ thể của chúng, hoặc có thể
	 * cung cấp tham số 'active_callback' cho hàm khởi tạo.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Luôn trả về true.
	 */
	public function active_callback() {
		return true;
	}

	/**
	 * Thu thập các tham số được truyền cho JavaScript phía client qua JSON.
	 *
	 * @since 4.1.0
	 *
	 * @return array Mảng được xuất ra cho client dưới dạng JSON.
	 */
	public function json() {
		$array                          = wp_array_slice_assoc( (array) $this, array( 'id', 'description', 'priority', 'type' ) );
		$array['title']                 = html_entity_decode( $this->title, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$array['content']               = $this->get_content();
		$array['active']                = $this->active();
		$array['instanceNumber']        = $this->instance_number;
		$array['autoExpandSoleSection'] = $this->auto_expand_sole_section;
		return $array;
	}

	/**
	 * Kiểm tra quyền người dùng cần thiết và liệu theme có hỗ trợ
	 * tính năng mà panel yêu cầu hay không.
	 *
	 * @since 4.0.0
	 * @since 5.9.0 Phương thức được đánh dấu không phải final.
	 *
	 * @return bool False nếu theme không hỗ trợ panel hoặc người dùng không có quyền cần thiết.
	 */
	public function check_capabilities() {
		if ( $this->capability && ! current_user_can( $this->capability ) ) {
			return false;
		}

		if ( $this->theme_supports && ! current_theme_supports( ...(array) $this->theme_supports ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Lấy template nội dung của panel để chèn vào khung Customizer.
	 *
	 * @since 4.1.0
	 *
	 * @return string Nội dung cho panel.
	 */
	final public function get_content() {
		ob_start();
		$this->maybe_render();
		return trim( ob_get_clean() );
	}

	/**
	 * Kiểm tra quyền và hiển thị panel.
	 *
	 * @since 4.0.0
	 */
	final public function maybe_render() {
		if ( ! $this->check_capabilities() ) {
			return;
		}

		/**
		 * Kích hoạt trước khi hiển thị một panel Customizer.
		 *
		 * @since 4.0.0
		 *
		 * @param WP_Customize_Panel $panel Thực thể WP_Customize_Panel.
		 */
		do_action( 'customize_render_panel', $this );

		/**
		 * Kích hoạt trước khi hiển thị một panel Customizer cụ thể.
		 *
		 * Phần động của tên hook, `$this->id`, tham chiếu đến
		 * ID của panel Customizer cụ thể sẽ được hiển thị.
		 *
		 * @since 4.0.0
		 */
		do_action( "customize_render_panel_{$this->id}" );

		$this->render();
	}

	/**
	 * Hiển thị container của panel, sau đó nội dung của nó (qua `this->render_content()`) trong lớp con.
	 *
	 * Container panel giờ được hiển thị bằng JS theo mặc định, xem WP_Customize_Panel::print_template().
	 *
	 * @since 4.0.0
	 */
	protected function render() {}

	/**
	 * Hiển thị giao diện panel trong lớp con.
	 *
	 * Nội dung panel giờ được hiển thị bằng JS theo mặc định, xem WP_Customize_Panel::print_template().
	 *
	 * @since 4.1.0
	 */
	protected function render_content() {}

	/**
	 * Hiển thị các template JS của panel.
	 *
	 * Hàm này chỉ chạy cho các loại panel đã được đăng ký với
	 * WP_Customize_Manager::register_panel_type().
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Manager::register_panel_type()
	 */
	public function print_template() {
		?>
		<script type="text/html" id="tmpl-customize-panel-<?php echo esc_attr( $this->type ); ?>-content">
			<?php $this->content_template(); ?>
		</script>
		<script type="text/html" id="tmpl-customize-panel-<?php echo esc_attr( $this->type ); ?>">
			<?php $this->render_template(); ?>
		</script>
		<?php
	}

	/**
	 * Template Underscore (JS) để hiển thị container của panel này.
	 *
	 * Các biến lớp cho lớp panel này có sẵn trong đối tượng JS `data`;
	 * xuất các biến tùy chỉnh bằng cách ghi đè WP_Customize_Panel::json().
	 *
	 * @see WP_Customize_Panel::print_template()
	 *
	 * @since 4.3.0
	 */
	protected function render_template() {
		?>
		<li id="accordion-panel-{{ data.id }}" class="accordion-section control-section control-panel control-panel-{{ data.type }}">
			<h3 class="accordion-section-title">
				<button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="{{ data.id }}-content">
					{{ data.title }}
				</button>
			</h3>
			<ul class="accordion-sub-container control-panel-content" id="{{ data.id }}-content"></ul>
		</li>
		<?php
	}

	/**
	 * Template Underscore (JS) cho nội dung của panel này (nhưng không phải container).
	 *
	 * Các biến lớp cho lớp panel này có sẵn trong đối tượng JS `data`;
	 * xuất các biến tùy chỉnh bằng cách ghi đè WP_Customize_Panel::json().
	 *
	 * @see WP_Customize_Panel::print_template()
	 *
	 * @since 4.3.0
	 */
	protected function content_template() {
		?>
		<li class="panel-meta customize-info accordion-section <# if ( ! data.description ) { #> cannot-expand<# } #>">
			<button class="customize-panel-back" tabindex="-1"><span class="screen-reader-text">
				<?php
				/* translators: Hidden accessibility text. */
				_e( 'Back' );
				?>
			</span></button>
			<div class="accordion-section-title">
				<span class="preview-notice">
				<?php
					/* translators: %s: The site/panel title in the Customizer. */
					printf( __( 'You are customizing %s' ), '<strong class="panel-title">{{ data.title }}</strong>' );
				?>
				</span>
				<# if ( data.description ) { #>
					<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false"><span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						_e( 'Help' );
						?>
					</span></button>
				<# } #>
			</div>
			<# if ( data.description ) { #>
				<div class="description customize-panel-description">
					{{{ data.description }}}
				</div>
			<# } #>

			<div class="customize-control-notifications-container"></div>
		</li>
		<?php
	}
}

/** Lớp WP_Customize_Nav_Menus_Panel */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menus-panel.php';
