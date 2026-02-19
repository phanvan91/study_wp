<?php
/**
 * API Nâng cấp: Lớp File_Upload_Upgrader
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Lớp lõi dùng để xử lý tải tệp lên.
 *
 * Lớp này xử lý quá trình tải lên và truyền nó như thể là một tệp cục bộ
 * cho các hàm Nâng cấp/Cài đặt.
 *
 * @since 2.8.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader.php.
 */
#[AllowDynamicProperties]
class File_Upload_Upgrader {

	/**
	 * Đường dẫn đầy đủ tới gói tệp.
	 *
	 * @since 2.8.0
	 * @var string $package
	 */
	public $package;

	/**
	 * Tên của tệp.
	 *
	 * @since 2.8.0
	 * @var string $filename
	 */
	public $filename;

	/**
	 * ID bài viết đính kèm cho tệp này.
	 *
	 * @since 3.3.0
	 * @var int $id
	 */
	public $id = 0;

	/**
	 * Khởi tạo trình nâng cấp cho một biểu mẫu.
	 *
	 * @since 2.8.0
	 *
	 * @param string $form      Tên biểu mẫu mà tệp được tải lên từ đó.
	 * @param string $urlholder Tên tham số `GET` chứa tên tệp.
	 */
	public function __construct( $form, $urlholder ) {

		if ( empty( $_FILES[ $form ]['name'] ) && empty( $_GET[ $urlholder ] ) ) {
			wp_die( __( 'Please select a file' ) );
		}

		// Xử lý tệp mới tải lên. Nếu không, giả sử tệp đã được tải lên trước đó.
		if ( ! empty( $_FILES ) ) {
			$overrides = array(
				'test_form' => false,
				'test_type' => false,
			);
			$file      = wp_handle_upload( $_FILES[ $form ], $overrides );

			if ( isset( $file['error'] ) ) {
				wp_die( $file['error'] );
			}

			if ( 'pluginzip' === $form || 'themezip' === $form ) {
				if ( ! wp_zip_file_is_valid( $file['file'] ) ) {
					wp_delete_file( $file['file'] );

					if ( 'pluginzip' === $form ) {
						$plugins_page = sprintf(
							'<a href="%s">%s</a>',
							self_admin_url( 'plugin-install.php' ),
							__( 'Return to the Plugin Installer' )
						);
						wp_die( __( 'Incompatible Archive.' ) . '<br />' . $plugins_page );
					}

					if ( 'themezip' === $form ) {
						$themes_page = sprintf(
							'<a href="%s" target="_parent">%s</a>',
							self_admin_url( 'theme-install.php' ),
							__( 'Return to the Theme Installer' )
						);
						wp_die( __( 'Incompatible Archive.' ) . '<br />' . $themes_page );
					}
				}
			}

			$this->filename = $_FILES[ $form ]['name'];
			$this->package  = $file['file'];

			// Xây dựng mảng đính kèm.
			$attachment = array(
				'post_title'     => $this->filename,
				'post_content'   => $file['url'],
				'post_mime_type' => $file['type'],
				'guid'           => $file['url'],
				'context'        => 'upgrader',
				'post_status'    => 'private',
			);

			// Lưu dữ liệu.
			$this->id = wp_insert_attachment( $attachment, $file['file'] );

			// Lên lịch dọn dẹp sau 2 giờ trong trường hợp cài đặt thất bại.
			wp_schedule_single_event( time() + 2 * HOUR_IN_SECONDS, 'upgrader_scheduled_cleanup', array( $this->id ) );

		} elseif ( is_numeric( $_GET[ $urlholder ] ) ) {
			// Gói số = tệp đã tải lên trước đó, xem ở trên.
			$this->id   = (int) $_GET[ $urlholder ];
			$attachment = get_post( $this->id );
			if ( empty( $attachment ) ) {
				wp_die( __( 'Please select a file' ) );
			}

			$this->filename = $attachment->post_title;
			$this->package  = get_attached_file( $attachment->ID );
		} else {
			// Nếu không, giá trị đã được đặt sẵn, tương thích ngược cho plugin sử dụng trình xử lý File_Uploader cũ (trước 3.3).
			$uploads = wp_upload_dir();
			if ( ! ( $uploads && false === $uploads['error'] ) ) {
				wp_die( $uploads['error'] );
			}

			$this->filename = sanitize_file_name( $_GET[ $urlholder ] );
			$this->package  = $uploads['basedir'] . '/' . $this->filename;

			if ( ! str_starts_with( realpath( $this->package ), realpath( $uploads['basedir'] ) ) ) {
				wp_die( __( 'Please select a file' ) );
			}
		}
	}

	/**
	 * Xóa đính kèm/tệp đã tải lên.
	 *
	 * @since 3.2.2
	 *
	 * @return bool Dọn dẹp có thành công hay không.
	 */
	public function cleanup() {
		if ( $this->id ) {
			wp_delete_attachment( $this->id );

		} elseif ( file_exists( $this->package ) ) {
			return @unlink( $this->package );
		}

		return true;
	}
}
