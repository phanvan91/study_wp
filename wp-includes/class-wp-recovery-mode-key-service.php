<?php
/**
 * API bảo vệ lỗi: Lớp WP_Recovery_Mode_Key_Service
 *
 * @package WordPress
 * @since 5.2.0
 */

/**
 * Lớp cốt lõi dùng để tạo và xác thực các khóa dùng để vào Chế độ Phục hồi.
 *
 * @since 5.2.0
 */
#[AllowDynamicProperties]
final class WP_Recovery_Mode_Key_Service {

	/**
	 * Tên tùy chọn dùng để lưu trữ các khóa.
	 *
	 * @since 5.2.0
	 * @var string
	 */
	private $option_name = 'recovery_keys';

	/**
	 * Tạo một mã thông báo chế độ phục hồi.
	 *
	 * @since 5.2.0
	 *
	 * @return string Một chuỗi ngẫu nhiên để xác định khóa liên quan của nó trong bộ nhớ.
	 */
	public function generate_recovery_mode_token() {
		return wp_generate_password( 22, false );
	}

	/**
	 * Tạo một khóa chế độ phục hồi.
	 *
	 * @since 5.2.0
	 * @since 6.8.0 Khóa đã lưu trữ giờ được băm bằng wp_fast_hash() thay vì phpass.
	 *
	 * @param string $token Một mã thông báo được tạo bởi {@see generate_recovery_mode_token()}.
	 * @return string Khóa chế độ phục hồi.
	 */
	public function generate_and_store_recovery_mode_key( $token ) {
		$key = wp_generate_password( 22, false );

		$records = $this->get_keys();

		$records[ $token ] = array(
			'hashed_key' => wp_fast_hash( $key ),
			'created_at' => time(),
		);

		$this->update_keys( $records );

		/**
		 * Kích hoạt khi một khóa chế độ phục hồi được tạo.
		 *
		 * @since 5.2.0
		 *
		 * @param string $token Mã thông báo dữ liệu phục hồi.
		 * @param string $key   Khóa chế độ phục hồi.
		 */
		do_action( 'generate_recovery_mode_key', $token, $key );

		return $key;
	}

	/**
	 * Xác minh xem khóa chế độ phục hồi có đúng hay không.
	 *
	 * Khóa chế độ phục hồi chỉ có thể được sử dụng một lần; khóa sẽ được sử dụng trong quá trình này.
	 *
	 * @since 5.2.0
	 *
	 * @param string $token Mã thông báo được sử dụng khi tạo khóa đã cho.
	 * @param string $key   Khóa văn bản thuần túy.
	 * @param int    $ttl   Thời gian tính bằng giây để khóa có hiệu lực.
	 * @return true|WP_Error True nếu thành công, đối tượng lỗi nếu thất bại.
	 */
	public function validate_recovery_mode_key( $token, $key, $ttl ) {
		$records = $this->get_keys();

		if ( ! isset( $records[ $token ] ) ) {
			return new WP_Error( 'token_not_found', __( 'Recovery Mode not initialized.' ) );
		}

		$record = $records[ $token ];

		$this->remove_key( $token );

		if ( ! is_array( $record ) || ! isset( $record['hashed_key'], $record['created_at'] ) ) {
			return new WP_Error( 'invalid_recovery_key_format', __( 'Invalid recovery key format.' ) );
		}

		if ( ! wp_verify_fast_hash( $key, $record['hashed_key'] ) ) {
			return new WP_Error( 'hash_mismatch', __( 'Invalid recovery key.' ) );
		}

		if ( time() > $record['created_at'] + $ttl ) {
			return new WP_Error( 'key_expired', __( 'Recovery key expired.' ) );
		}

		return true;
	}

	/**
	 * Xóa các khóa chế độ phục hồi đã hết hạn.
	 *
	 * @since 5.2.0
	 *
	 * @param int $ttl Thời gian tính bằng giây để các khóa có hiệu lực.
	 */
	public function clean_expired_keys( $ttl ) {

		$records = $this->get_keys();

		foreach ( $records as $key => $record ) {
			if ( ! isset( $record['created_at'] ) || time() > $record['created_at'] + $ttl ) {
				unset( $records[ $key ] );
			}
		}

		$this->update_keys( $records );
	}

	/**
	 * Xóa một khóa phục hồi đã sử dụng.
	 *
	 * @since 5.2.0
	 *
	 * @param string $token Mã thông báo được sử dụng khi tạo khóa chế độ phục hồi.
	 */
	private function remove_key( $token ) {

		$records = $this->get_keys();

		if ( ! isset( $records[ $token ] ) ) {
			return;
		}

		unset( $records[ $token ] );

		$this->update_keys( $records );
	}

	/**
	 * Lấy các bản ghi khóa phục hồi.
	 *
	 * @since 5.2.0
	 * @since 6.8.0 Mỗi khóa hiện được băm bằng wp_fast_hash() thay vì phpass.
	 *              Các khóa hiện có vẫn có thể được băm bằng phpass.
	 *
	 * @return array {
	 *     Mảng kết hợp của các cặp mã thông báo => dữ liệu, trong đó dữ liệu là một mảng kết hợp
	 *     thông tin về khóa.
	 *
	 *     @type array ...$0 {
	 *         Thông tin về khóa.
	 *
	 *         @type string $hashed_key Giá trị đã băm của khóa.
	 *         @type int    $created_at Dấu thời gian khi khóa được tạo.
	 *     }
	 * }
	 */
	private function get_keys() {
		return (array) get_option( $this->option_name, array() );
	}

	/**
	 * Cập nhật các bản ghi khóa phục hồi.
	 *
	 * @since 5.2.0
	 * @since 6.8.0 Mỗi khóa giờ nên được băm bằng wp_fast_hash() thay vì phpass.
	 *
	 * @param array $keys {
	 *     Mảng kết hợp của các cặp mã thông báo => dữ liệu, trong đó dữ liệu là một mảng kết hợp
	 *     thông tin về khóa.
	 *
	 *     @type array ...$0 {
	 *         Thông tin về khóa.
	 *
	 *         @type string $hashed_key Giá trị đã băm của khóa.
	 *         @type int    $created_at Dấu thời gian khi khóa được tạo.
	 *     }
	 * }
	 * @return bool True nếu thành công, false nếu thất bại.
	 */
	private function update_keys( array $keys ) {
		return update_option( $this->option_name, $keys, false );
	}
}
