<?php
/**
 * Các lớp Section trong WordPress Customize
 *
 * @package WordPress
 * @subpackage Customize
 * @since 3.4.0
 */

/**
 * Lớp Customize Section.
 *
 * Một container giao diện cho các control, được quản lý bởi lớp WP_Customize_Manager.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Manager
 */
#[AllowDynamicProperties]
class WP_Customize_Section {

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
	 * @since 3.4.0
	 * @var WP_Customize_Manager
	 */
	public $manager;

	/**
	 * Định danh duy nhất.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $id;

	/**
	 * Mức ưu tiên của section xác định thứ tự tải của các section.
	 *
	 * @since 3.4.0
	 * @var int
	 */
	public $priority = 160;

	/**
	 * Panel chứa section, biến nó thành một section con.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	public $panel = '';

	/**
	 * Quyền cần thiết cho section.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $capability = 'edit_theme_options';

	/**
	 * Các tính năng theme cần thiết để hỗ trợ section.
	 *
	 * @since 3.4.0
	 * @var string|string[]
	 */
	public $theme_supports = '';

	/**
	 * Tiêu đề của section hiển thị trong giao diện.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $title = '';

	/**
	 * Mô tả hiển thị trong giao diện.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $description = '';

	/**
	 * Các control Customizer cho section này.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	public $controls;

	/**
	 * Loại của section này.
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
	 * Hiển thị mô tả hoặc ẩn nó sau biểu tượng trợ giúp.
	 *
	 * @since 4.7.0
	 *
	 * @var bool Chỉ ra liệu mô tả của Section có nên được
	 *           ẩn sau biểu tượng trợ giúp ("?") trong tiêu đề Section hay không,
	 *           tương tự như cách biểu tượng trợ giúp được hiển thị trên các Panel.
	 */
	public $description_hidden = false;

	/**
	 * Hàm khởi tạo.
	 *
	 * Bất kỳ $args nào được cung cấp sẽ ghi đè các giá trị mặc định của thuộc tính lớp.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Customize_Manager $manager Thực thể khởi tạo Customizer.
	 * @param string               $id      ID cụ thể của section.
	 * @param array                $args    {
	 *     Tùy chọn. Mảng các thuộc tính cho đối tượng Section mới. Mặc định mảng rỗng.
	 *
	 *     @type int             $priority           Mức ưu tiên của section, xác định thứ tự hiển thị
	 *                                               của các panel và section. Mặc định 160.
	 *     @type string          $panel              Panel mà section này thuộc về (nếu có).
	 *                                               Mặc định rỗng.
	 *     @type string          $capability         Quyền cần thiết cho section.
	 *                                               Mặc định 'edit_theme_options'
	 *     @type string|string[] $theme_supports     Các tính năng theme cần thiết để hỗ trợ section.
	 *     @type string          $title              Tiêu đề của section hiển thị trong giao diện.
	 *     @type string          $description        Mô tả hiển thị trong giao diện.
	 *     @type string          $type               Loại của section.
	 *     @type callable        $active_callback    Callback kích hoạt.
	 *     @type bool            $description_hidden Ẩn mô tả sau biểu tượng trợ giúp,
	 *                                               thay vì hiển thị nội tuyến phía trên control đầu tiên.
	 *                                               Mặc định false.
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

		$this->controls = array(); // Người dùng không thể tùy chỉnh mảng $controls.
	}

	/**
	 * Kiểm tra xem section có đang hoạt động với bản xem trước Customizer hiện tại không.
	 *
	 * @since 4.1.0
	 *
	 * @return bool Liệu section có đang hoạt động với bản xem trước hiện tại hay không.
	 */
	final public function active() {
		$section = $this;
		$active  = call_user_func( $this->active_callback, $this );

		/**
		 * Lọc phản hồi của WP_Customize_Section::active().
		 *
		 * @since 4.1.0
		 *
		 * @param bool                 $active  Liệu section Customizer có đang hoạt động hay không.
		 * @param WP_Customize_Section $section Thực thể WP_Customize_Section.
		 */
		$active = apply_filters( 'customize_section_active', $active, $section );

		return $active;
	}

	/**
	 * Callback mặc định được sử dụng khi gọi WP_Customize_Section::active().
	 *
	 * Các lớp con có thể ghi đè với logic cụ thể của chúng, hoặc có thể cung cấp
	 * tham số 'active_callback' cho hàm khởi tạo.
	 *
	 * @since 4.1.0
	 *
	 * @return true Luôn trả về true.
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
		$array                   = wp_array_slice_assoc( (array) $this, array( 'id', 'description', 'priority', 'panel', 'type', 'description_hidden' ) );
		$array['title']          = html_entity_decode( $this->title, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$array['content']        = $this->get_content();
		$array['active']         = $this->active();
		$array['instanceNumber'] = $this->instance_number;

		if ( $this->panel ) {
			/* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
			$array['customizeAction'] = sprintf( __( 'Customizing &#9656; %s' ), esc_html( $this->manager->get_panel( $this->panel )->title ) );
		} else {
			$array['customizeAction'] = __( 'Customizing' );
		}

		return $array;
	}

	/**
	 * Kiểm tra quyền người dùng cần thiết và liệu theme có hỗ trợ
	 * tính năng mà section yêu cầu hay không.
	 *
	 * @since 3.4.0
	 *
	 * @return bool False nếu theme không hỗ trợ section hoặc người dùng không có quyền cần thiết.
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
	 * Lấy nội dung của section để chèn vào khung Customizer.
	 *
	 * @since 4.1.0
	 *
	 * @return string Nội dung của section.
	 */
	final public function get_content() {
		ob_start();
		$this->maybe_render();
		return trim( ob_get_clean() );
	}

	/**
	 * Kiểm tra quyền và hiển thị section.
	 *
	 * @since 3.4.0
	 */
	final public function maybe_render() {
		if ( ! $this->check_capabilities() ) {
			return;
		}

		/**
		 * Kích hoạt trước khi hiển thị một section Customizer.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Section $section Thực thể WP_Customize_Section.
		 */
		do_action( 'customize_render_section', $this );
		/**
		 * Kích hoạt trước khi hiển thị một section Customizer cụ thể.
		 *
		 * Phần động của tên hook, `$this->id`, tham chiếu đến ID
		 * của section Customizer cụ thể sẽ được hiển thị.
		 *
		 * @since 3.4.0
		 */
		do_action( "customize_render_section_{$this->id}" );

		$this->render();
	}

	/**
	 * Hiển thị giao diện section trong lớp con.
	 *
	 * Các section giờ được hiển thị bằng JS theo mặc định, xem WP_Customize_Section::print_template().
	 *
	 * @since 3.4.0
	 */
	protected function render() {}

	/**
	 * Hiển thị template JS của section.
	 *
	 * Hàm này chỉ chạy cho các loại section đã được đăng ký với
	 * WP_Customize_Manager::register_section_type().
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Manager::render_template()
	 */
	public function print_template() {
		?>
		<script type="text/html" id="tmpl-customize-section-<?php echo $this->type; ?>">
			<?php $this->render_template(); ?>
		</script>
		<?php
	}

	/**
	 * Template Underscore (JS) để hiển thị section này.
	 *
	 * Các biến lớp cho lớp section này có sẵn trong đối tượng JS `data`;
	 * xuất các biến tùy chỉnh bằng cách ghi đè WP_Customize_Section::json().
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Section::print_template()
	 */
	protected function render_template() {
		?>
		<li id="accordion-section-{{ data.id }}" class="accordion-section control-section control-section-{{ data.type }}">
			<h3 class="accordion-section-title">
				<button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="{{ data.id }}-content">
					{{ data.title }}
				</button>
			</h3>
			<ul class="accordion-section-content" id="{{ data.id }}-content">
				<li class="customize-section-description-container section-meta <# if ( data.description_hidden ) { #>customize-info<# } #>">
					<div class="customize-section-title">
						<button class="customize-section-back" tabindex="-1">
							<span class="screen-reader-text">
								<?php
								/* translators: Hidden accessibility text. */
								_e( 'Back' );
								?>
							</span>
						</button>
						<h3>
							<span class="customize-action">
								{{{ data.customizeAction }}}
							</span>
							{{ data.title }}
						</h3>
						<# if ( data.description && data.description_hidden ) { #>
							<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false"><span class="screen-reader-text">
								<?php
								/* translators: Hidden accessibility text. */
								_e( 'Help' );
								?>
							</span></button>
							<div class="description customize-section-description">
								{{{ data.description }}}
							</div>
						<# } #>

						<div class="customize-control-notifications-container"></div>
					</div>

					<# if ( data.description && ! data.description_hidden ) { #>
						<div class="description customize-section-description">
							{{{ data.description }}}
						</div>
					<# } #>
				</li>
			</ul>
		</li>
		<?php
	}
}

/** Lớp WP_Customize_Themes_Section */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-themes-section.php';

/** Lớp WP_Customize_Sidebar_Section */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-sidebar-section.php';

/** Lớp WP_Customize_Nav_Menu_Section */
require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-section.php';
