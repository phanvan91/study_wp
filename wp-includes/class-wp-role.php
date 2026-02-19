<?php
/**
 * API Người dùng: Lớp WP_Role
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Lớp lõi dùng để mở rộng API vai trò người dùng.
 *
 * @since 2.0.0
 */
#[AllowDynamicProperties]
class WP_Role {
	/**
	 * Tên vai trò.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $name;

	/**
	 * Danh sách quyền hạn mà vai trò chứa.
	 *
	 * @since 2.0.0
	 * @var bool[] Mảng cặp khóa/giá trị trong đó khóa là tên quyền hạn và giá trị boolean
	 *             cho biết vai trò có quyền hạn đó hay không.
	 */
	public $capabilities;

	/**
	 * Hàm khởi tạo - Thiết lập các thuộc tính đối tượng.
	 *
	 * Danh sách quyền hạn phải có khóa là tên quyền hạn
	 * và giá trị là boolean cho biết có được cấp cho vai trò hay không.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role         Tên vai trò.
	 * @param bool[] $capabilities Mảng cặp khóa/giá trị trong đó khóa là tên quyền hạn và giá trị boolean
	 *                             cho biết vai trò có quyền hạn đó hay không.
	 */
	public function __construct( $role, $capabilities ) {
		$this->name         = $role;
		$this->capabilities = $capabilities;
	}

	/**
	 * Gán quyền hạn cho vai trò.
	 *
	 * @since 2.0.0
	 *
	 * @param string $cap   Tên quyền hạn.
	 * @param bool   $grant Vai trò có được đặc quyền này hay không.
	 */
	public function add_cap( $cap, $grant = true ) {
		$this->capabilities[ $cap ] = $grant;
		wp_roles()->add_cap( $this->name, $cap, $grant );
	}

	/**
	 * Xóa quyền hạn khỏi vai trò.
	 *
	 * @since 2.0.0
	 *
	 * @param string $cap Tên quyền hạn.
	 */
	public function remove_cap( $cap ) {
		unset( $this->capabilities[ $cap ] );
		wp_roles()->remove_cap( $this->name, $cap );
	}

	/**
	 * Xác định xem vai trò có quyền hạn đã cho hay không.
	 *
	 * @since 2.0.0
	 *
	 * @param string $cap Tên quyền hạn.
	 * @return bool Vai trò có quyền hạn đã cho hay không.
	 */
	public function has_cap( $cap ) {
		/**
		 * Lọc các quyền hạn mà một vai trò có.
		 *
		 * @since 2.0.0
		 *
		 * @param bool[] $capabilities Mảng cặp khóa/giá trị trong đó khóa là tên quyền hạn và giá trị boolean
		 *                             cho biết vai trò có quyền hạn đó hay không.
		 * @param string $cap          Tên quyền hạn.
		 * @param string $name         Tên vai trò.
		 */
		$capabilities = apply_filters( 'role_has_cap', $this->capabilities, $cap, $this->name );

		if ( ! empty( $capabilities[ $cap ] ) ) {
			return $capabilities[ $cap ];
		} else {
			return false;
		}
	}
}
