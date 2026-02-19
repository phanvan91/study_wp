<?php
/**
 * API Tùy biến: Lớp WP_Customize_New_Menu_Control
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 * @deprecated 4.9.0 File này không còn được sử dụng kể từ giao diện tạo menu được giới thiệu trong #40104.
 */

_deprecated_file( basename( __FILE__ ), '4.9.0' );

/**
 * Lớp điều khiển tùy biến cho các menu mới.
 *
 * @since 4.3.0
 * @deprecated 4.9.0 Lớp này không còn được sử dụng kể từ giao diện tạo menu được giới thiệu trong #40104.
 *
 * @see WP_Customize_Control
 */
class WP_Customize_New_Menu_Control extends WP_Customize_Control {

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
	 * @since 4.9.0
	 * @deprecated 4.9.0
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Thể hiện khởi tạo của Trình tùy biến.
	 * @param string               $id      ID điều khiển.
	 * @param array                $args    Tùy chọn. Các đối số để ghi đè giá trị mặc định của thuộc tính lớp.
	 *                                      Xem WP_Customize_Control::__construct() để biết thông tin
	 *                                      về các đối số được chấp nhận. Mặc định mảng rỗng.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		_deprecated_function( __METHOD__, '4.9.0' );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Hiển thị nội dung của điều khiển.
	 *
	 * @since 4.3.0
	 * @deprecated 4.9.0
	 */
	public function render_content() {
		_deprecated_function( __METHOD__, '4.9.0' );
		?>
		<button type="button" class="button button-primary" id="create-new-menu-submit"><?php _e( 'Create Menu' ); ?></button>
		<span class="spinner"></span>
		<?php
	}
}
