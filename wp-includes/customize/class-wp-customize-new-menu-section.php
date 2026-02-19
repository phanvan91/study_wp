<?php
/**
 * API Tùy biến: Lớp WP_Customize_New_Menu_Section
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 * @deprecated 4.9.0 File này không còn được sử dụng kể từ giao diện tạo menu được giới thiệu trong #40104.
 */

_deprecated_file( basename( __FILE__ ), '4.9.0' );

/**
 * Lớp Phần Menu Tùy biến
 *
 * @since 4.3.0
 * @deprecated 4.9.0 Lớp này không còn được sử dụng kể từ giao diện tạo menu được giới thiệu trong #40104.
 *
 * @see WP_Customize_Section
 */
class WP_Customize_New_Menu_Section extends WP_Customize_Section {

	/**
	 * Loại điều khiển.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'new_menu';

	/**
	 * Hàm khởi tạo.
	 *
	 * Bất kỳ $args nào được cung cấp sẽ ghi đè giá trị mặc định của thuộc tính lớp.
	 *
	 * @since 4.9.0
	 * @deprecated 4.9.0
	 *
	 * @param WP_Customize_Manager $manager Đối tượng khởi tạo Trình tùy biến.
	 * @param string               $id      ID cụ thể của phần.
	 * @param array                $args    Các tham số của phần.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		_deprecated_function( __METHOD__, '4.9.0' );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Hiển thị phần và các điều khiển đã được thêm vào.
	 *
	 * @since 4.3.0
	 * @deprecated 4.9.0
	 */
	protected function render() {
		_deprecated_function( __METHOD__, '4.9.0' );
		?>
		<li id="accordion-section-<?php echo esc_attr( $this->id ); ?>" class="accordion-section-new-menu">
			<button type="button" class="button add-new-menu-item add-menu-toggle" aria-expanded="false">
				<?php echo esc_html( $this->title ); ?>
			</button>
			<ul class="new-menu-section-content"></ul>
		</li>
		<?php
	}
}
