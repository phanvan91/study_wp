<?php
/**
 * API Tùy biến: Lớp WP_Customize_Color_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Lớp Điều khiển Màu sắc trong Tùy biến.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Color_Control extends WP_Customize_Control {
	/**
	 * Loại.
	 *
	 * @var string
	 */
	public $type = 'color';

	/**
	 * Các trạng thái.
	 *
	 * @var array
	 */
	public $statuses;

	/**
	 * Chế độ.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $mode = 'full';

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 3.4.0
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Đối tượng khởi tạo Trình tùy biến.
	 * @param string               $id      ID của điều khiển.
	 * @param array                $args    Tùy chọn. Các tham số để ghi đè giá trị mặc định của thuộc tính lớp.
	 *                                      Xem WP_Customize_Control::__construct() để biết thông tin
	 *                                      về các tham số được chấp nhận. Mặc định là mảng rỗng.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		$this->statuses = array( '' => __( 'Default' ) );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Nạp các script/style cho bộ chọn màu.
	 *
	 * @since 3.4.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Làm mới các tham số được truyền tới JavaScript qua JSON.
	 *
	 * @since 3.4.0
	 * @uses WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['statuses']     = $this->statuses;
		$this->json['defaultValue'] = $this->setting->default;
		$this->json['mode']         = $this->mode;
	}

	/**
	 * Không hiển thị nội dung điều khiển từ PHP, vì nó được hiển thị qua JS khi tải trang.
	 *
	 * @since 3.4.0
	 */
	public function render_content() {}

	/**
	 * Hiển thị mẫu JS cho nội dung của điều khiển bộ chọn màu.
	 *
	 * @since 4.1.0
	 */
	public function content_template() {
		?>
		<# var defaultValue = '#RRGGBB', defaultValueAttr = '',
			isHueSlider = data.mode === 'hue';
		if ( data.defaultValue && _.isString( data.defaultValue ) && ! isHueSlider ) {
			if ( '#' !== data.defaultValue.substring( 0, 1 ) ) {
				defaultValue = '#' + data.defaultValue;
			} else {
				defaultValue = data.defaultValue;
			}
			defaultValueAttr = ' data-default-color=' + defaultValue; // Quotes added automatically.
		} #>
		<# if ( data.label ) { #>
			<span class="customize-control-title">{{{ data.label }}}</span>
		<# } #>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>
		<div class="customize-control-content">
			<label><span class="screen-reader-text">{{{ data.label }}}</span>
			<# if ( isHueSlider ) { #>
				<input class="color-picker-hue" type="text" data-type="hue" />
			<# } else { #>
				<input class="color-picker-hex" type="text" maxlength="7" placeholder="{{ defaultValue }}" {{ defaultValueAttr }} />
			<# } #>
			</label>
		</div>
		<?php
	}
}
