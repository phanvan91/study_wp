<?php
/**
 * API Người dùng: Lớp WP_Roles
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để triển khai API vai trò người dùng.
 *
 * Tùy chọn vai trò rất đơn giản, cấu trúc được tổ chức theo tên vai trò
 * lưu trữ tên trong giá trị của khóa 'name'. Các quyền hạn được lưu trữ dưới dạng mảng
 * trong giá trị của khóa 'capability'.
 *
 *     array (
 *          'rolename' => array (
 *              'name' => 'rolename',
 *              'capabilities' => array()
 *          )
 *     )
 *
 * @since 2.0.0
 */
#[AllowDynamicProperties]
class WP_Roles {
	/**
	 * Danh sách vai trò và quyền hạn.
	 *
	 * @since 2.0.0
	 * @var array[]
	 */
	public $roles;

	/**
	 * Danh sách các đối tượng vai trò.
	 *
	 * @since 2.0.0
	 * @var WP_Role[]
	 */
	public $role_objects = array();

	/**
	 * Danh sách tên vai trò.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	public $role_names = array();

	/**
	 * Tên tùy chọn để lưu trữ danh sách vai trò.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $role_key;

	/**
	 * Có sử dụng cơ sở dữ liệu để truy xuất và lưu trữ hay không.
	 *
	 * @since 2.1.0
	 * @var bool
	 */
	public $use_db = true;

	/**
	 * ID site mà các vai trò được khởi tạo cho.
	 *
	 * @since 4.9.0
	 * @var int
	 */
	protected $site_id = 0;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 2.0.0
	 * @since 4.9.0 Thêm tham số `$site_id`.
	 *
	 * @global array $wp_user_roles Dùng để thiết lập giá trị thuộc tính 'roles'.
	 *
	 * @param int $site_id ID site để khởi tạo vai trò. Mặc định là site hiện tại.
	 */
	public function __construct( $site_id = null ) {
		global $wp_user_roles;

		$this->use_db = empty( $wp_user_roles );

		$this->for_site( $site_id );
	}

	/**
	 * Cho phép đọc các phương thức private/protected cho tương thích ngược.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name      Phương thức cần gọi.
	 * @param array  $arguments Các tham số truyền vào khi gọi.
	 * @return mixed|false Giá trị trả về của callback, false nếu không.
	 */
	public function __call( $name, $arguments ) {
		if ( '_init' === $name ) {
			return $this->_init( ...$arguments );
		}
		return false;
	}

	/**
	 * Thiết lập các thuộc tính đối tượng.
	 *
	 * Khóa vai trò được đặt thành tiền tố hiện tại của đối tượng $wpdb
	 * nối thêm 'user_roles'. Nếu biến toàn cục $wp_user_roles được thiết lập, nó sẽ
	 * được sử dụng và tùy chọn vai trò sẽ không được cập nhật hoặc sử dụng.
	 *
	 * @since 2.1.0
	 * @deprecated 4.9.0 Sử dụng WP_Roles::for_site()
	 */
	protected function _init() {
		_deprecated_function( __METHOD__, '4.9.0', 'WP_Roles::for_site()' );

		$this->for_site();
	}

	/**
	 * Khởi tạo lại đối tượng.
	 *
	 * Tạo lại các đối tượng vai trò. Thường chỉ được gọi bởi switch_to_blog()
	 * sau khi chuyển wpdb sang ID site mới.
	 *
	 * @since 3.5.0
	 * @deprecated 4.7.0 Sử dụng WP_Roles::for_site()
	 */
	public function reinit() {
		_deprecated_function( __METHOD__, '4.7.0', 'WP_Roles::for_site()' );

		$this->for_site();
	}

	/**
	 * Thêm tên vai trò cùng quyền hạn vào danh sách.
	 *
	 * Cập nhật danh sách vai trò, nếu vai trò chưa tồn tại.
	 *
	 * Quyền hạn được định nghĩa theo định dạng sau: `array( 'read' => true )`.
	 * Để từ chối rõ ràng một quyền hạn cho vai trò, đặt giá trị của quyền hạn đó thành false.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role         Tên vai trò.
	 * @param string $display_name Tên hiển thị của vai trò.
	 * @param bool[] $capabilities Tùy chọn. Danh sách quyền hạn với khóa là tên quyền hạn,
	 *                             ví dụ `array( 'edit_posts' => true, 'delete_posts' => false )`.
	 *                             Mặc định mảng rỗng.
	 * @return WP_Role|void Đối tượng WP_Role, nếu vai trò được thêm.
	 */
	public function add_role( $role, $display_name, $capabilities = array() ) {
		if ( empty( $role ) || isset( $this->roles[ $role ] ) ) {
			return;
		}

		$this->roles[ $role ] = array(
			'name'         => $display_name,
			'capabilities' => $capabilities,
		);
		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles, true );
		}
		$this->role_objects[ $role ] = new WP_Role( $role, $capabilities );
		$this->role_names[ $role ]   = $display_name;
		return $this->role_objects[ $role ];
	}

	/**
	 * Xóa vai trò theo tên.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Tên vai trò.
	 */
	public function remove_role( $role ) {
		if ( ! isset( $this->role_objects[ $role ] ) ) {
			return;
		}

		unset( $this->role_objects[ $role ] );
		unset( $this->role_names[ $role ] );
		unset( $this->roles[ $role ] );

		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}

		if ( get_option( 'default_role' ) === $role ) {
			update_option( 'default_role', 'subscriber' );
		}
	}

	/**
	 * Thêm quyền hạn cho vai trò.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role  Tên vai trò.
	 * @param string $cap   Tên quyền hạn.
	 * @param bool   $grant Tùy chọn. Vai trò có khả năng thực hiện quyền hạn hay không.
	 *                      Mặc định true.
	 */
	public function add_cap( $role, $cap, $grant = true ) {
		if ( ! isset( $this->roles[ $role ] ) ) {
			return;
		}

		$this->roles[ $role ]['capabilities'][ $cap ] = $grant;
		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}
	}

	/**
	 * Xóa quyền hạn khỏi vai trò.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Tên vai trò.
	 * @param string $cap  Tên quyền hạn.
	 */
	public function remove_cap( $role, $cap ) {
		if ( ! isset( $this->roles[ $role ] ) ) {
			return;
		}

		unset( $this->roles[ $role ]['capabilities'][ $cap ] );
		if ( $this->use_db ) {
			update_option( $this->role_key, $this->roles );
		}
	}

	/**
	 * Lấy đối tượng vai trò theo tên.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Tên vai trò.
	 * @return WP_Role|null Đối tượng WP_Role nếu tìm thấy, null nếu vai trò không tồn tại.
	 */
	public function get_role( $role ) {
		if ( isset( $this->role_objects[ $role ] ) ) {
			return $this->role_objects[ $role ];
		} else {
			return null;
		}
	}

	/**
	 * Lấy danh sách tên vai trò.
	 *
	 * @since 2.0.0
	 *
	 * @return string[] Danh sách tên vai trò.
	 */
	public function get_names() {
		return $this->role_names;
	}

	/**
	 * Xác định xem tên vai trò có trong danh sách vai trò khả dụng hay không.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Tên vai trò cần tra cứu.
	 * @return bool
	 */
	public function is_role( $role ) {
		return isset( $this->role_names[ $role ] );
	}

	/**
	 * Khởi tạo tất cả các vai trò khả dụng.
	 *
	 * @since 4.9.0
	 */
	public function init_roles() {
		if ( empty( $this->roles ) ) {
			return;
		}

		$this->role_objects = array();
		$this->role_names   = array();
		foreach ( array_keys( $this->roles ) as $role ) {
			$this->role_objects[ $role ] = new WP_Role( $role, $this->roles[ $role ]['capabilities'] );
			$this->role_names[ $role ]   = $this->roles[ $role ]['name'];
		}

		/**
		 * Kích hoạt sau khi các vai trò đã được khởi tạo, cho phép plugin thêm vai trò riêng.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Roles $wp_roles Tham chiếu đến đối tượng WP_Roles.
		 */
		do_action( 'wp_roles_init', $this );
	}

	/**
	 * Thiết lập site để hoạt động. Mặc định là site hiện tại.
	 *
	 * @since 4.9.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
	 *
	 * @param int $site_id ID site để khởi tạo vai trò. Mặc định là site hiện tại.
	 */
	public function for_site( $site_id = null ) {
		global $wpdb;

		if ( ! empty( $site_id ) ) {
			$this->site_id = absint( $site_id );
		} else {
			$this->site_id = get_current_blog_id();
		}

		$this->role_key = $wpdb->get_blog_prefix( $this->site_id ) . 'user_roles';

		if ( ! empty( $this->roles ) && ! $this->use_db ) {
			return;
		}

		$this->roles = $this->get_roles_data();

		$this->init_roles();
	}

	/**
	 * Lấy ID site mà các vai trò hiện đang được khởi tạo.
	 *
	 * @since 4.9.0
	 *
	 * @return int ID site.
	 */
	public function get_site_id() {
		return $this->site_id;
	}

	/**
	 * Lấy dữ liệu vai trò khả dụng.
	 *
	 * @since 4.9.0
	 *
	 * @global array $wp_user_roles Dùng để thiết lập giá trị thuộc tính 'roles'.
	 *
	 * @return array Mảng vai trò.
	 */
	protected function get_roles_data() {
		global $wp_user_roles;

		if ( ! empty( $wp_user_roles ) ) {
			return $wp_user_roles;
		}

		if ( is_multisite() && get_current_blog_id() !== $this->site_id ) {
			remove_action( 'switch_blog', 'wp_switch_roles_and_user', 1 );

			$roles = get_blog_option( $this->site_id, $this->role_key, array() );

			add_action( 'switch_blog', 'wp_switch_roles_and_user', 1, 2 );

			return $roles;
		}

		return get_option( $this->role_key, array() );
	}
}
