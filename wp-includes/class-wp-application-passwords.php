<?php
/**
 * Lớp WP_Application_Passwords
 *
 * @package WordPress
 * @since   5.6.0
 */

/**
 * Lớp để hiển thị, chỉnh sửa và làm sạch mật khẩu ứng dụng.
 *
 * @package WordPress
 */
#[AllowDynamicProperties]
class WP_Application_Passwords {

	/**
	 * Khóa user meta cho mật khẩu ứng dụng.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	const USERMETA_KEY_APPLICATION_PASSWORDS = '_application_passwords';

	/**
	 * Tên option dùng để lưu trữ việc mật khẩu ứng dụng có đang được sử dụng hay không.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	const OPTION_KEY_IN_USE = 'using_application_passwords';

	/**
	 * Độ dài mật khẩu ứng dụng được tạo.
	 *
	 * @since 5.6.0
	 *
	 * @var int
	 */
	const PW_LENGTH = 24;

	/**
	 * Kiểm tra xem mật khẩu ứng dụng có đang được sử dụng bởi site hay không.
	 *
	 * Trả về true nếu ít nhất một mật khẩu ứng dụng đã từng được tạo.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	public static function is_in_use() {
		$network_id = get_main_network_id();
		return (bool) get_network_option( $network_id, self::OPTION_KEY_IN_USE );
	}

	/**
	 * Tạo một mật khẩu ứng dụng mới.
	 *
	 * @since 5.6.0
	 * @since 5.7.0 Trả về WP_Error nếu tên ứng dụng đã tồn tại.
	 * @since 6.8.0 Giá trị mật khẩu đã băm giờ sử dụng wp_fast_hash() thay vì phpass.
	 *
	 * @param int   $user_id  ID người dùng.
	 * @param array $args     {
	 *     Các tham số dùng để tạo mật khẩu ứng dụng.
	 *
	 *     @type string $name   Tên của mật khẩu ứng dụng.
	 *     @type string $app_id Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
	 * }
	 * @return array|WP_Error {
	 *     Chi tiết mật khẩu ứng dụng, hoặc instance WP_Error nếu có lỗi xảy ra.
	 *
	 *     @type string $0 Mật khẩu ứng dụng được tạo dạng văn bản thuần.
	 *     @type array  $1 {
	 *         Chi tiết về mật khẩu đã tạo.
	 *
	 *         @type string $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
	 *         @type string $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
	 *         @type string $name      Tên của mật khẩu ứng dụng.
	 *         @type string $password  Giá trị băm một chiều của mật khẩu.
	 *         @type int    $created   Timestamp Unix khi mật khẩu được tạo.
	 *         @type null   $last_used Null.
	 *         @type null   $last_ip   Null.
	 *     }
	 * }
	 */
	public static function create_new_application_password( $user_id, $args = array() ) {
		if ( ! empty( $args['name'] ) ) {
			$args['name'] = sanitize_text_field( $args['name'] );
		}

		if ( empty( $args['name'] ) ) {
			return new WP_Error( 'application_password_empty_name', __( 'An application name is required to create an application password.' ), array( 'status' => 400 ) );
		}

		$new_password    = wp_generate_password( static::PW_LENGTH, false );
		$hashed_password = self::hash_password( $new_password );

		$new_item = array(
			'uuid'      => wp_generate_uuid4(),
			'app_id'    => empty( $args['app_id'] ) ? '' : $args['app_id'],
			'name'      => $args['name'],
			'password'  => $hashed_password,
			'created'   => time(),
			'last_used' => null,
			'last_ip'   => null,
		);

		$passwords   = static::get_user_application_passwords( $user_id );
		$passwords[] = $new_item;
		$saved       = static::set_user_application_passwords( $user_id, $passwords );

		if ( ! $saved ) {
			return new WP_Error( 'db_error', __( 'Could not save application password.' ) );
		}

		$network_id = get_main_network_id();
		if ( ! get_network_option( $network_id, self::OPTION_KEY_IN_USE ) ) {
			update_network_option( $network_id, self::OPTION_KEY_IN_USE, true );
		}

		/**
		 * Kích hoạt khi một mật khẩu ứng dụng được tạo.
		 *
		 * @since 5.6.0
		 * @since 6.8.0 Giá trị mật khẩu đã băm giờ sử dụng wp_fast_hash() thay vì phpass.
		 *
		 * @param int    $user_id      ID người dùng.
		 * @param array  $new_item     {
		 *     Chi tiết về mật khẩu đã tạo.
		 *
		 *     @type string $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
		 *     @type string $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
		 *     @type string $name      Tên của mật khẩu ứng dụng.
		 *     @type string $password  Giá trị băm một chiều của mật khẩu.
		 *     @type int    $created   Timestamp Unix khi mật khẩu được tạo.
		 *     @type null   $last_used Null.
		 *     @type null   $last_ip   Null.
		 * }
		 * @param string $new_password Mật khẩu ứng dụng được tạo dạng văn bản thuần.
		 * @param array  $args         {
		 *     Các tham số dùng để tạo mật khẩu ứng dụng.
		 *
		 *     @type string $name   Tên của mật khẩu ứng dụng.
		 *     @type string $app_id Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
		 * }
		 */
		do_action( 'wp_create_application_password', $user_id, $new_item, $new_password, $args );

		return array( $new_password, $new_item );
	}

	/**
	 * Lấy danh sách mật khẩu ứng dụng của người dùng.
	 *
	 * @since 5.6.0
	 *
	 * @param int $user_id ID người dùng.
	 * @return array {
	 *     Danh sách mật khẩu ứng dụng.
	 *
	 *     @type array ...$0 {
	 *         @type string      $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
	 *         @type string      $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
	 *         @type string      $name      Tên của mật khẩu ứng dụng.
	 *         @type string      $password  Giá trị băm một chiều của mật khẩu.
	 *         @type int         $created   Timestamp Unix khi mật khẩu được tạo.
	 *         @type int|null    $last_used Timestamp Unix theo GMT khi mật khẩu ứng dụng được sử dụng lần cuối.
	 *         @type string|null $last_ip   Địa chỉ IP đã sử dụng mật khẩu ứng dụng lần cuối.
	 *     }
	 * }
	 */
	public static function get_user_application_passwords( $user_id ) {
		$passwords = get_user_meta( $user_id, static::USERMETA_KEY_APPLICATION_PASSWORDS, true );

		if ( ! is_array( $passwords ) ) {
			return array();
		}

		$save = false;

		foreach ( $passwords as $i => $password ) {
			if ( ! isset( $password['uuid'] ) ) {
				$passwords[ $i ]['uuid'] = wp_generate_uuid4();
				$save                    = true;
			}
		}

		if ( $save ) {
			static::set_user_application_passwords( $user_id, $passwords );
		}

		return $passwords;
	}

	/**
	 * Lấy mật khẩu ứng dụng của người dùng theo UUID cho trước.
	 *
	 * @since 5.6.0
	 *
	 * @param int    $user_id ID người dùng.
	 * @param string $uuid    UUID của mật khẩu.
	 * @return array|null {
	 *     Mật khẩu ứng dụng nếu tìm thấy, null nếu không.
	 *
	 *     @type string      $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
	 *     @type string      $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
	 *     @type string      $name      Tên của mật khẩu ứng dụng.
	 *     @type string      $password  Giá trị băm một chiều của mật khẩu.
	 *     @type int         $created   Timestamp Unix khi mật khẩu được tạo.
	 *     @type int|null    $last_used Timestamp Unix theo GMT khi mật khẩu ứng dụng được sử dụng lần cuối.
	 *     @type string|null $last_ip   Địa chỉ IP đã sử dụng mật khẩu ứng dụng lần cuối.
	 * }
	 */
	public static function get_user_application_password( $user_id, $uuid ) {
		$passwords = static::get_user_application_passwords( $user_id );

		foreach ( $passwords as $password ) {
			if ( $password['uuid'] === $uuid ) {
				return $password;
			}
		}

		return null;
	}

	/**
	 * Kiểm tra xem mật khẩu ứng dụng với tên cho trước có tồn tại cho người dùng này không.
	 *
	 * @since 5.7.0
	 *
	 * @param int    $user_id ID người dùng.
	 * @param string $name    Tên ứng dụng.
	 * @return bool Tên ứng dụng đã cho có tồn tại hay không.
	 */
	public static function application_name_exists_for_user( $user_id, $name ) {
		$passwords = static::get_user_application_passwords( $user_id );

		foreach ( $passwords as $password ) {
			if ( strtolower( $password['name'] ) === strtolower( $name ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cập nhật mật khẩu ứng dụng.
	 *
	 * @since 5.6.0
	 * @since 6.8.0 Mật khẩu thực tế giờ nên được băm bằng wp_fast_hash().
	 *
	 * @param int    $user_id ID người dùng.
	 * @param string $uuid    UUID của mật khẩu.
	 * @param array  $update  {
	 *     Thông tin về mật khẩu ứng dụng cần cập nhật.
	 *
	 *     @type string      $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
	 *     @type string      $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
	 *     @type string      $name      Tên của mật khẩu ứng dụng.
	 *     @type string      $password  Giá trị băm một chiều của mật khẩu.
	 *     @type int         $created   Timestamp Unix khi mật khẩu được tạo.
	 *     @type int|null    $last_used Timestamp Unix theo GMT khi mật khẩu ứng dụng được sử dụng lần cuối.
	 *     @type string|null $last_ip   Địa chỉ IP đã sử dụng mật khẩu ứng dụng lần cuối.
	 * }
	 * @return true|WP_Error True nếu thành công, nếu không trả về instance WP_Error khi có lỗi.
	 */
	public static function update_application_password( $user_id, $uuid, $update = array() ) {
		$passwords = static::get_user_application_passwords( $user_id );

		foreach ( $passwords as &$item ) {
			if ( $item['uuid'] !== $uuid ) {
				continue;
			}

			if ( ! empty( $update['name'] ) ) {
				$update['name'] = sanitize_text_field( $update['name'] );
			}

			$save = false;

			if ( ! empty( $update['name'] ) && $item['name'] !== $update['name'] ) {
				$item['name'] = $update['name'];
				$save         = true;
			}

			if ( $save ) {
				$saved = static::set_user_application_passwords( $user_id, $passwords );

				if ( ! $saved ) {
					return new WP_Error( 'db_error', __( 'Could not save application password.' ) );
				}
			}

			/**
			 * Kích hoạt khi mật khẩu ứng dụng được cập nhật.
			 *
			 * @since 5.6.0
			 * @since 6.8.0 Mật khẩu giờ được băm bằng wp_fast_hash() thay vì phpass.
			 *              Các mật khẩu hiện có vẫn có thể được băm bằng phpass.
			 *
			 * @param int   $user_id ID người dùng.
			 * @param array $item    {
			 *     Chi tiết mật khẩu ứng dụng đã cập nhật.
			 *
			 *     @type string      $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
			 *     @type string      $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
			 *     @type string      $name      Tên của mật khẩu ứng dụng.
			 *     @type string      $password  Giá trị băm một chiều của mật khẩu.
			 *     @type int         $created   Timestamp Unix khi mật khẩu được tạo.
			 *     @type int|null    $last_used Timestamp Unix theo GMT khi mật khẩu ứng dụng được sử dụng lần cuối.
			 *     @type string|null $last_ip   Địa chỉ IP đã sử dụng mật khẩu ứng dụng lần cuối.
			 * }
			 * @param array $update  Thông tin cần cập nhật.
			 */
			do_action( 'wp_update_application_password', $user_id, $item, $update );

			return true;
		}

		return new WP_Error( 'application_password_not_found', __( 'Could not find an application password with that id.' ) );
	}

	/**
	 * Ghi nhận rằng một mật khẩu ứng dụng đã được sử dụng.
	 *
	 * @since 5.6.0
	 *
	 * @param int    $user_id ID người dùng.
	 * @param string $uuid    UUID của mật khẩu.
	 * @return true|WP_Error True nếu ghi nhận thành công, WP_Error nếu có lỗi xảy ra.
	 */
	public static function record_application_password_usage( $user_id, $uuid ) {
		$passwords = static::get_user_application_passwords( $user_id );

		foreach ( $passwords as &$password ) {
			if ( $password['uuid'] !== $uuid ) {
				continue;
			}

			// Chỉ ghi nhận hoạt động một lần mỗi ngày.
			if ( $password['last_used'] + DAY_IN_SECONDS > time() ) {
				return true;
			}

			$password['last_used'] = time();
			$password['last_ip']   = $_SERVER['REMOTE_ADDR'];

			$saved = static::set_user_application_passwords( $user_id, $passwords );

			if ( ! $saved ) {
				return new WP_Error( 'db_error', __( 'Could not save application password.' ) );
			}

			return true;
		}

		// Không tìm thấy mật khẩu ứng dụng được chỉ định!
		return new WP_Error( 'application_password_not_found', __( 'Could not find an application password with that id.' ) );
	}

	/**
	 * Xóa một mật khẩu ứng dụng.
	 *
	 * @since 5.6.0
	 *
	 * @param int    $user_id ID người dùng.
	 * @param string $uuid    UUID của mật khẩu.
	 * @return true|WP_Error Mật khẩu có được tìm thấy và xóa thành công hay không, nếu không trả về WP_Error.
	 */
	public static function delete_application_password( $user_id, $uuid ) {
		$passwords = static::get_user_application_passwords( $user_id );

		foreach ( $passwords as $key => $item ) {
			if ( $item['uuid'] === $uuid ) {
				unset( $passwords[ $key ] );
				$saved = static::set_user_application_passwords( $user_id, $passwords );

				if ( ! $saved ) {
					return new WP_Error( 'db_error', __( 'Could not delete application password.' ) );
				}

				/**
				 * Kích hoạt khi mật khẩu ứng dụng bị xóa.
				 *
				 * @since 5.6.0
				 *
				 * @param int   $user_id ID người dùng.
				 * @param array $item    Dữ liệu về mật khẩu ứng dụng.
				 */
				do_action( 'wp_delete_application_password', $user_id, $item );

				return true;
			}
		}

		return new WP_Error( 'application_password_not_found', __( 'Could not find an application password with that id.' ) );
	}

	/**
	 * Xóa tất cả mật khẩu ứng dụng của người dùng cho trước.
	 *
	 * @since 5.6.0
	 *
	 * @param int $user_id ID người dùng.
	 * @return int|WP_Error Số lượng mật khẩu đã xóa hoặc WP_Error khi thất bại.
	 */
	public static function delete_all_application_passwords( $user_id ) {
		$passwords = static::get_user_application_passwords( $user_id );

		if ( $passwords ) {
			$saved = static::set_user_application_passwords( $user_id, array() );

			if ( ! $saved ) {
				return new WP_Error( 'db_error', __( 'Could not delete application passwords.' ) );
			}

			foreach ( $passwords as $item ) {
				/** Action này được ghi nhận trong wp-includes/class-wp-application-passwords.php */
				do_action( 'wp_delete_application_password', $user_id, $item );
			}

			return count( $passwords );
		}

		return 0;
	}

	/**
	 * Thiết lập danh sách mật khẩu ứng dụng của người dùng.
	 *
	 * @since 5.6.0
	 *
	 * @param int   $user_id   ID người dùng.
	 * @param array $passwords {
	 *     Danh sách mật khẩu ứng dụng.
	 *
	 *     @type array ...$0 {
	 *         @type string      $uuid      Định danh duy nhất cho mật khẩu ứng dụng.
	 *         @type string      $app_id    Một UUID được cung cấp bởi ứng dụng để nhận dạng duy nhất nó.
	 *         @type string      $name      Tên của mật khẩu ứng dụng.
	 *         @type string      $password  Giá trị băm một chiều của mật khẩu.
	 *         @type int         $created   Timestamp Unix khi mật khẩu được tạo.
	 *         @type int|null    $last_used Timestamp Unix theo GMT khi mật khẩu ứng dụng được sử dụng lần cuối.
	 *         @type string|null $last_ip   Địa chỉ IP đã sử dụng mật khẩu ứng dụng lần cuối.
	 *     }
	 * }
	 * @return int|bool ID user meta nếu khóa chưa tồn tại (tức đây là lần đầu tiên mật khẩu ứng dụng
	 *                  được lưu cho người dùng), true khi cập nhật thành công, false khi thất bại hoặc nếu giá trị
	 *                  truyền vào giống với giá trị đã có trong cơ sở dữ liệu.
	 */
	protected static function set_user_application_passwords( $user_id, $passwords ) {
		return update_user_meta( $user_id, static::USERMETA_KEY_APPLICATION_PASSWORDS, $passwords );
	}

	/**
	 * Làm sạch và sau đó chia mật khẩu thành các đoạn nhỏ hơn.
	 *
	 * @since 5.6.0
	 *
	 * @param string $raw_password Mật khẩu ứng dụng thô.
	 * @return string Mật khẩu đã được chia đoạn.
	 */
	public static function chunk_password(
		#[\SensitiveParameter]
		$raw_password
	) {
		$raw_password = preg_replace( '/[^a-z\d]/i', '', $raw_password );

		return trim( chunk_split( $raw_password, 4, ' ' ) );
	}

	/**
	 * Băm mật khẩu ứng dụng dạng văn bản thuần.
	 *
	 * @since 6.8.0
	 *
	 * @param string $password Mật khẩu dạng văn bản thuần.
	 * @return string Mật khẩu đã được băm.
	 */
	public static function hash_password(
		#[\SensitiveParameter]
		string $password
	): string {
		return wp_fast_hash( $password );
	}

	/**
	 * Kiểm tra mật khẩu ứng dụng dạng văn bản thuần so với mật khẩu đã băm.
	 *
	 * @since 6.8.0
	 *
	 * @param string $password Mật khẩu dạng văn bản thuần.
	 * @param string $hash     Giá trị băm của mật khẩu để so sánh.
	 * @return bool Mật khẩu có khớp với mật khẩu đã băm hay không.
	 */
	public static function check_password(
		#[\SensitiveParameter]
		string $password,
		string $hash
	): bool {
		if ( ! str_starts_with( $hash, '$generic$' ) ) {
			/*
			 * Nếu giá trị băm không bắt đầu bằng `$generic$`, đó là giá trị băm được tạo bằng `wp_hash_password()`.
			 * Đây là trường hợp cho các mật khẩu ứng dụng được tạo trước phiên bản 6.8.0.
			 */
			return wp_check_password( $password, $hash );
		}

		return wp_verify_fast_hash( $password, $hash );
	}
}
