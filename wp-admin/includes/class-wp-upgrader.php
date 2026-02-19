<?php
/**
 * API Nâng cấp: Lớp WP_Upgrader
 *
 * Yêu cầu các lớp skin và các lớp con WP_Upgrader để tương thích ngược.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */

/** Lớp WP_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

/** Lớp Plugin_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader-skin.php';

/** Lớp Theme_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader-skin.php';

/** Lớp Bulk_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-bulk-upgrader-skin.php';

/** Lớp Bulk_Plugin_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-bulk-plugin-upgrader-skin.php';

/** Lớp Bulk_Theme_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-bulk-theme-upgrader-skin.php';

/** Lớp Plugin_Installer_Skin */
require_once ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';

/** Lớp Theme_Installer_Skin */
require_once ABSPATH . 'wp-admin/includes/class-theme-installer-skin.php';

/** Lớp Language_Pack_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-language-pack-upgrader-skin.php';

/** Lớp Automatic_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

/** Lớp WP_Ajax_Upgrader_Skin */
require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

/**
 * Lớp lõi dùng để nâng cấp/cài đặt một tập hợp tệp cục bộ thông qua
 * các lớp trừu tượng hệ thống tệp từ tệp Zip.
 *
 * @since 2.8.0
 */
#[AllowDynamicProperties]
class WP_Upgrader {

	/**
	 * Các chuỗi lỗi/thông báo dùng để cập nhật tiến trình cho người dùng.
	 *
	 * @since 2.8.0
	 * @var array $strings
	 */
	public $strings = array();

	/**
	 * Skin nâng cấp đang được sử dụng.
	 *
	 * @since 2.8.0
	 * @var Automatic_Upgrader_Skin|WP_Upgrader_Skin $skin
	 */
	public $skin = null;

	/**
	 * Kết quả của quá trình cài đặt.
	 *
	 * Giá trị này được thiết lập bởi WP_Upgrader::install_package(), chỉ khi gói được cài đặt
	 * thành công. Khi đó nó sẽ là một mảng, trừ khi WP_Error được trả về bởi bộ lọc
	 * {@see 'upgrader_post_install'}. Trong trường hợp đó, WP_Error sẽ được gán cho nó.
	 *
	 * @since 2.8.0
	 *
	 * @var array|WP_Error $result {
	 *     @type string $source             Đường dẫn đầy đủ tới nguồn mà các tệp được cài đặt từ đó.
	 *     @type string $source_files       Danh sách tất cả các tệp trong thư mục nguồn.
	 *     @type string $destination        Đường dẫn đầy đủ tới thư mục đích cài đặt.
	 *     @type string $destination_name   Tên thư mục đích, hoặc rỗng nếu `$destination`
	 *                                      và `$local_destination` giống nhau.
	 *     @type string $local_destination  Đường dẫn cục bộ đầy đủ tới thư mục đích. Thường
	 *                                      giống với `$destination`.
	 *     @type string $remote_destination Đường dẫn từ xa đầy đủ tới thư mục đích
	 *                                      (tức là từ `$wp_filesystem`).
	 *     @type bool   $clear_destination  Liệu thư mục đích có được xóa hay không.
	 * }
	 */
	public $result = array();

	/**
	 * Tổng số bản cập nhật đang được thực hiện.
	 *
	 * Được thiết lập bởi các phương thức cập nhật hàng loạt.
	 *
	 * @since 3.0.0
	 * @var int $update_count
	 */
	public $update_count = 0;

	/**
	 * Bản cập nhật hiện tại nếu đang thực hiện nhiều bản cập nhật.
	 *
	 * Được sử dụng bởi các phương thức cập nhật hàng loạt, và được tăng dần cho mỗi bản cập nhật.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $update_current = 0;

	/**
	 * Lưu trữ danh sách plugin hoặc giao diện đã được thêm vào thư mục sao lưu tạm thời.
	 *
	 * Được sử dụng bởi các hàm khôi phục.
	 *
	 * @since 6.3.0
	 * @var array
	 */
	private $temp_backups = array();

	/**
	 * Lưu trữ danh sách plugin hoặc giao diện cần được khôi phục từ thư mục sao lưu tạm thời.
	 *
	 * Được sử dụng bởi các hàm khôi phục.
	 *
	 * @since 6.3.0
	 * @var array
	 */
	private $temp_restores = array();

	/**
	 * Khởi tạo trình nâng cấp với một skin.
	 *
	 * @since 2.8.0
	 *
	 * @param WP_Upgrader_Skin $skin Skin nâng cấp để sử dụng. Mặc định là một thực thể
	 *                               WP_Upgrader_Skin.
	 */
	public function __construct( $skin = null ) {
		if ( null === $skin ) {
			$this->skin = new WP_Upgrader_Skin();
		} else {
			$this->skin = $skin;
		}
	}

	/**
	 * Khởi tạo trình nâng cấp.
	 *
	 * Thiết lập mối quan hệ giữa skin đang sử dụng và trình nâng cấp này,
	 * đồng thời thêm các chuỗi chung vào `WP_Upgrader::$strings`.
	 *
	 * Ngoài ra, nó sẽ lên lịch tác vụ hàng tuần để dọn dẹp thư mục sao lưu tạm thời.
	 *
	 * @since 2.8.0
	 * @since 6.3.0 Thêm tác vụ `schedule_temp_backup_cleanup()`.
	 */
	public function init() {
		$this->skin->set_upgrader( $this );
		$this->generic_strings();

		if ( ! wp_installing() ) {
			$this->schedule_temp_backup_cleanup();
		}
	}

	/**
	 * Lên lịch dọn dẹp thư mục sao lưu tạm thời.
	 *
	 * @since 6.3.0
	 */
	protected function schedule_temp_backup_cleanup() {
		if ( false === wp_next_scheduled( 'wp_delete_temp_updater_backups' ) ) {
			wp_schedule_event( time(), 'weekly', 'wp_delete_temp_updater_backups' );
		}
	}

	/**
	 * Thêm các chuỗi chung vào WP_Upgrader::$strings.
	 *
	 * @since 2.8.0
	 */
	public function generic_strings() {
		$this->strings['bad_request']    = __( 'Invalid data provided.' );
		$this->strings['fs_unavailable'] = __( 'Could not access filesystem.' );
		$this->strings['fs_error']       = __( 'Filesystem error.' );
		$this->strings['fs_no_root_dir'] = __( 'Unable to locate WordPress root directory.' );
		/* translators: %s: Tên thư mục. */
		$this->strings['fs_no_content_dir'] = sprintf( __( 'Unable to locate WordPress content directory (%s).' ), 'wp-content' );
		$this->strings['fs_no_plugins_dir'] = __( 'Unable to locate WordPress plugin directory.' );
		$this->strings['fs_no_themes_dir']  = __( 'Unable to locate WordPress theme directory.' );
		/* translators: %s: Tên thư mục. */
		$this->strings['fs_no_folder'] = __( 'Unable to locate needed folder (%s).' );

		$this->strings['no_package']           = __( 'Package not available.' );
		$this->strings['download_failed']      = __( 'Download failed.' );
		$this->strings['installing_package']   = __( 'Installing the latest version&#8230;' );
		$this->strings['no_files']             = __( 'The package contains no files.' );
		$this->strings['folder_exists']        = __( 'Destination folder already exists.' );
		$this->strings['mkdir_failed']         = __( 'Could not create directory.' );
		$this->strings['incompatible_archive'] = __( 'The package could not be installed.' );
		$this->strings['files_not_writable']   = __( 'The update cannot be installed because some files could not be copied. This is usually due to inconsistent file permissions.' );
		$this->strings['dir_not_readable']     = __( 'A directory could not be read.' );

		$this->strings['maintenance_start'] = __( 'Enabling Maintenance mode&#8230;' );
		$this->strings['maintenance_end']   = __( 'Disabling Maintenance mode&#8230;' );

		/* translators: %s: upgrade-temp-backup */
		$this->strings['temp_backup_mkdir_failed'] = sprintf( __( 'Could not create the %s directory.' ), 'upgrade-temp-backup' );
		/* translators: %s: upgrade-temp-backup */
		$this->strings['temp_backup_move_failed'] = sprintf( __( 'Could not move the old version to the %s directory.' ), 'upgrade-temp-backup' );
		/* translators: %s: Slug của plugin hoặc giao diện. */
		$this->strings['temp_backup_restore_failed'] = __( 'Could not restore the original version of %s.' );
		/* translators: %s: Slug của plugin hoặc giao diện. */
		$this->strings['temp_backup_delete_failed'] = __( 'Could not delete the temporary backup directory for %s.' );
	}

	/**
	 * Kết nối tới hệ thống tệp.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param string[] $directories                  Tùy chọn. Mảng các thư mục. Nếu bất kỳ thư mục nào
	 *                                               không tồn tại, đối tượng WP_Error sẽ được trả về.
	 *                                               Mặc định mảng rỗng.
	 * @param bool     $allow_relaxed_file_ownership Cho phép quyền sở hữu tệp linh hoạt hay không.
	 *                                               Mặc định false.
	 * @return bool|WP_Error True nếu có thể kết nối, false hoặc WP_Error nếu không.
	 */
	public function fs_connect( $directories = array(), $allow_relaxed_file_ownership = false ) {
		global $wp_filesystem;

		$credentials = $this->skin->request_filesystem_credentials( false, $directories[0], $allow_relaxed_file_ownership );
		if ( false === $credentials ) {
			return false;
		}

		if ( ! WP_Filesystem( $credentials, $directories[0], $allow_relaxed_file_ownership ) ) {
			$error = true;
			if ( is_object( $wp_filesystem ) && $wp_filesystem->errors->has_errors() ) {
				$error = $wp_filesystem->errors;
			}
			// Kết nối thất bại. Thông báo lỗi và yêu cầu lại.
			$this->skin->request_filesystem_credentials( $error, $directories[0], $allow_relaxed_file_ownership );
			return false;
		}

		if ( ! is_object( $wp_filesystem ) ) {
			return new WP_Error( 'fs_unavailable', $this->strings['fs_unavailable'] );
		}

		if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
			return new WP_Error( 'fs_error', $this->strings['fs_error'], $wp_filesystem->errors );
		}

		foreach ( (array) $directories as $dir ) {
			switch ( $dir ) {
				case ABSPATH:
					if ( ! $wp_filesystem->abspath() ) {
						return new WP_Error( 'fs_no_root_dir', $this->strings['fs_no_root_dir'] );
					}
					break;
				case WP_CONTENT_DIR:
					if ( ! $wp_filesystem->wp_content_dir() ) {
						return new WP_Error( 'fs_no_content_dir', $this->strings['fs_no_content_dir'] );
					}
					break;
				case WP_PLUGIN_DIR:
					if ( ! $wp_filesystem->wp_plugins_dir() ) {
						return new WP_Error( 'fs_no_plugins_dir', $this->strings['fs_no_plugins_dir'] );
					}
					break;
				case get_theme_root():
					if ( ! $wp_filesystem->wp_themes_dir() ) {
						return new WP_Error( 'fs_no_themes_dir', $this->strings['fs_no_themes_dir'] );
					}
					break;
				default:
					if ( ! $wp_filesystem->find_folder( $dir ) ) {
						return new WP_Error( 'fs_no_folder', sprintf( $this->strings['fs_no_folder'], esc_html( basename( $dir ) ) ) );
					}
					break;
			}
		}
		return true;
	}

	/**
	 * Tải xuống một gói.
	 *
	 * @since 2.8.0
	 * @since 5.2.0 Thêm tham số `$check_signatures`.
	 * @since 5.5.0 Thêm tham số `$hook_extra`.
	 *
	 * @param string $package          URI của gói. Nếu đây là đường dẫn đầy đủ tới
	 *                                 một tệp cục bộ hiện có, nó sẽ được trả về nguyên trạng.
	 * @param bool   $check_signatures Kiểm tra chữ ký tệp hay không. Mặc định false.
	 * @param array  $hook_extra       Các tham số bổ sung để truyền vào bộ lọc hook. Mặc định mảng rỗng.
	 * @return string|WP_Error Đường dẫn đầy đủ tới tệp gói đã tải xuống, hoặc đối tượng WP_Error.
	 */
	public function download_package( $package, $check_signatures = false, $hook_extra = array() ) {
		/**
		 * Lọc việc có trả về gói hay không.
		 *
		 * @since 3.7.0
		 * @since 5.5.0 Thêm tham số `$hook_extra`.
		 *
		 * @param bool        $reply      Có bỏ qua mà không trả về gói hay không.
		 *                                Mặc định false.
		 * @param string      $package    Tên tệp gói.
		 * @param WP_Upgrader $upgrader   Thực thể WP_Upgrader.
		 * @param array       $hook_extra Các tham số bổ sung được truyền vào bộ lọc hook.
		 */
		$reply = apply_filters( 'upgrader_pre_download', false, $package, $this, $hook_extra );
		if ( false !== $reply ) {
			return $reply;
		}

		if ( ! preg_match( '!^(http|https|ftp)://!i', $package ) && file_exists( $package ) ) { // Tệp cục bộ hay từ xa?
			return $package; // Phải là tệp cục bộ.
		}

		if ( empty( $package ) ) {
			return new WP_Error( 'no_package', $this->strings['no_package'] );
		}

		$this->skin->feedback( 'downloading_package', $package );

		$download_file = download_url( $package, 300, $check_signatures );

		if ( is_wp_error( $download_file ) && ! $download_file->get_error_data( 'softfail-filename' ) ) {
			return new WP_Error( 'download_failed', $this->strings['download_failed'], $download_file->get_error_message() );
		}

		return $download_file;
	}

	/**
	 * Giải nén tệp gói nén.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param string $package        Đường dẫn đầy đủ tới tệp gói.
	 * @param bool   $delete_package Tùy chọn. Có xóa tệp gói sau khi cố giải nén hay không.
	 *                               Mặc định true.
	 * @return string|WP_Error Đường dẫn tới nội dung đã giải nén, hoặc WP_Error khi thất bại.
	 */
	public function unpack_package( $package, $delete_package = true ) {
		global $wp_filesystem;

		$this->skin->feedback( 'unpack_package' );

		if ( ! $wp_filesystem->wp_content_dir() ) {
			return new WP_Error( 'fs_no_content_dir', $this->strings['fs_no_content_dir'] );
		}

		$upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/';

		// Dọn dẹp nội dung thư mục nâng cấp trước đó.
		$upgrade_files = $wp_filesystem->dirlist( $upgrade_folder );
		if ( ! empty( $upgrade_files ) ) {
			foreach ( $upgrade_files as $file ) {
				$wp_filesystem->delete( $upgrade_folder . $file['name'], true );
			}
		}

		// Cần một thư mục làm việc - loại bỏ phần đuôi .tmp hoặc .zip.
		$working_dir = $upgrade_folder . basename( basename( $package, '.tmp' ), '.zip' );

		// Dọn dẹp thư mục làm việc.
		if ( $wp_filesystem->is_dir( $working_dir ) ) {
			$wp_filesystem->delete( $working_dir, true );
		}

		// Giải nén gói vào thư mục làm việc.
		$result = unzip_file( $package, $working_dir );

		// Sau khi giải nén, xóa gói nếu cần.
		if ( $delete_package ) {
			unlink( $package );
		}

		if ( is_wp_error( $result ) ) {
			$wp_filesystem->delete( $working_dir, true );
			if ( 'incompatible_archive' === $result->get_error_code() ) {
				return new WP_Error( 'incompatible_archive', $this->strings['incompatible_archive'], $result->get_error_data() );
			}
			return $result;
		}

		return $working_dir;
	}

	/**
	 * Làm phẳng kết quả từ WP_Filesystem_Base::dirlist() để duyệt qua.
	 *
	 * @since 4.9.0
	 * @access protected
	 *
	 * @param array  $nested_files Mảng tệp được trả về bởi WP_Filesystem_Base::dirlist().
	 * @param string $path         Đường dẫn tương đối để thêm vào trước các nút con. Tùy chọn.
	 * @return array Mảng đã được làm phẳng của $nested_files đã chỉ định.
	 */
	protected function flatten_dirlist( $nested_files, $path = '' ) {
		$files = array();

		foreach ( $nested_files as $name => $details ) {
			$files[ $path . $name ] = $details;

			// Thêm các phần tử con theo đệ quy.
			if ( ! empty( $details['files'] ) ) {
				$children = $this->flatten_dirlist( $details['files'], $path . $name . '/' );

				// Gộp giữ nguyên các khóa số, vì array_merge() sẽ đánh lại chỉ mục từ 0..n.
				$files = $files + $children;
			}
		}

		return $files;
	}

	/**
	 * Xóa sạch thư mục nơi mục này sẽ được cài đặt vào.
	 *
	 * @since 4.3.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param string $remote_destination Vị trí trên hệ thống tệp từ xa cần được xóa.
	 * @return true|WP_Error True khi thành công, WP_Error khi thất bại.
	 */
	public function clear_destination( $remote_destination ) {
		global $wp_filesystem;

		$files = $wp_filesystem->dirlist( $remote_destination, true, true );

		// False nghĩa là $remote_destination không tồn tại.
		if ( false === $files ) {
			return true;
		}

		// Làm phẳng danh sách tệp để duyệt qua.
		$files = $this->flatten_dirlist( $files );

		// Kiểm tra tất cả tệp có thể ghi trước khi cố xóa thư mục đích.
		$unwritable_files = array();

		// Kiểm tra quyền ghi.
		foreach ( $files as $filename => $file_details ) {
			if ( ! $wp_filesystem->is_writable( $remote_destination . $filename ) ) {
				// Cố thay đổi quyền để cho phép ghi và thử lại.
				$wp_filesystem->chmod( $remote_destination . $filename, ( 'd' === $file_details['type'] ? FS_CHMOD_DIR : FS_CHMOD_FILE ) );
				if ( ! $wp_filesystem->is_writable( $remote_destination . $filename ) ) {
					$unwritable_files[] = $filename;
				}
			}
		}

		if ( ! empty( $unwritable_files ) ) {
			return new WP_Error( 'files_not_writable', $this->strings['files_not_writable'], implode( ', ', $unwritable_files ) );
		}

		if ( ! $wp_filesystem->delete( $remote_destination, true ) ) {
			return new WP_Error( 'remove_old_failed', $this->strings['remove_old_failed'] );
		}

		return true;
	}

	/**
	 * Cài đặt một gói.
	 *
	 * Sao chép nội dung gói từ thư mục nguồn và cài đặt vào thư mục đích.
	 * Tùy chọn xóa nguồn. Cũng có thể tùy chọn xóa sạch thư mục đích
	 * nếu nó đã tồn tại.
	 *
	 * @since 2.8.0
	 * @since 6.2.0 Sử dụng move_dir() thay vì copy_dir() khi có thể.
	 *
	 * @global WP_Filesystem_Base $wp_filesystem        Lớp con hệ thống tệp WordPress.
	 * @global string[]           $wp_theme_directories
	 *
	 * @param array|string $args {
	 *     Tùy chọn. Mảng hoặc chuỗi các tham số để cài đặt gói. Mặc định mảng rỗng.
	 *
	 *     @type string $source                      Đường dẫn bắt buộc tới nguồn gói. Mặc định rỗng.
	 *     @type string $destination                 Đường dẫn bắt buộc tới thư mục cài đặt gói.
	 *                                               Mặc định rỗng.
	 *     @type bool   $clear_destination           Có xóa các tệp đã có trong thư mục đích hay không.
	 *                                               Mặc định false.
	 *     @type bool   $clear_working               Có xóa các tệp từ thư mục làm việc sau khi sao chép
	 *                                               tới đích hay không. Mặc định false.
	 *     @type bool   $abort_if_destination_exists Có hủy cài đặt nếu thư mục đích đã tồn tại hay không.
	 *                                               Mặc định true.
	 *     @type array  $hook_extra                  Các tham số bổ sung để truyền vào bộ lọc hook
	 *                                               được gọi bởi WP_Upgrader::install_package(). Mặc định mảng rỗng.
	 * }
	 *
	 * @return array|WP_Error Kết quả (cũng được lưu trong `WP_Upgrader::$result`), hoặc WP_Error khi thất bại.
	 */
	public function install_package( $args = array() ) {
		global $wp_filesystem, $wp_theme_directories;

		$defaults = array(
			'source'                      => '', // Xin hãy luôn truyền giá trị này.
			'destination'                 => '', // ...và giá trị này.
			'clear_destination'           => false,
			'clear_working'               => false,
			'abort_if_destination_exists' => true,
			'hook_extra'                  => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		// Các biến này trước đây được extract().
		$source            = $args['source'];
		$destination       = $args['destination'];
		$clear_destination = $args['clear_destination'];

		/*
		 * Cho quá trình nâng cấp thêm 300 giây (5 phút) để đảm bảo việc cài đặt
		 * không bị hết thời gian sớm do đã sử dụng hết thời gian thực thi script tối đa
		 * khi giải nén và tải xuống trong WP_Upgrader->run().
		 */
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		if (
			( ! is_string( $source ) || '' === $source || trim( $source ) !== $source ) ||
			( ! is_string( $destination ) || '' === $destination || trim( $destination ) !== $destination )
		) {
			return new WP_Error( 'bad_request', $this->strings['bad_request'] );
		}
		$this->skin->feedback( 'installing_package' );

		/**
		 * Lọc phản hồi cài đặt trước khi quá trình cài đặt bắt đầu.
		 *
		 * Trả về giá trị có thể được đánh giá là `WP_Error` sẽ thực tế
		 * làm ngắn mạch quá trình cài đặt, trả về giá trị đó thay thế.
		 *
		 * @since 2.8.0
		 *
		 * @param bool|WP_Error $response   Phản hồi cài đặt.
		 * @param array         $hook_extra Các tham số bổ sung được truyền vào bộ lọc hook.
		 */
		$res = apply_filters( 'upgrader_pre_install', true, $args['hook_extra'] );

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		// Giữ lại nguồn và đích gốc.
		$remote_source     = $args['source'];
		$local_destination = $destination;

		$dirlist = $wp_filesystem->dirlist( $remote_source );

		if ( false === $dirlist ) {
			return new WP_Error( 'source_read_failed', $this->strings['fs_error'], $this->strings['dir_not_readable'] );
		}

		$source_files       = array_keys( $dirlist );
		$remote_destination = $wp_filesystem->find_folder( $local_destination );

		// Xác định thư mục nào cần sao chép vào thư mục mới. Dựa trên thư mục thực tế chứa các tệp.
		if ( 1 === count( $source_files ) && $wp_filesystem->is_dir( trailingslashit( $args['source'] ) . $source_files[0] . '/' ) ) {
			// Chỉ có một thư mục? Thì ta cần nội dung bên trong nó.
			$source = trailingslashit( $args['source'] ) . trailingslashit( $source_files[0] );
		} elseif ( 0 === count( $source_files ) ) {
			// Không có tệp nào?
			return new WP_Error( 'incompatible_archive_empty', $this->strings['incompatible_archive'], $this->strings['no_files'] );
		} else {
			/*
			 * Chỉ có một tệp duy nhất, trình nâng cấp sẽ sử dụng tên thư mục của tệp này làm thư mục đích.
			 * Tên thư mục dựa trên tên tệp zip.
			 */
			$source = trailingslashit( $args['source'] );
		}

		/**
		 * Lọc vị trí tệp nguồn cho gói nâng cấp.
		 *
		 * @since 2.8.0
		 * @since 4.4.0 Tham số $hook_extra đã có sẵn.
		 *
		 * @param string      $source        Vị trí tệp nguồn.
		 * @param string      $remote_source Vị trí tệp nguồn từ xa.
		 * @param WP_Upgrader $upgrader      Thực thể WP_Upgrader.
		 * @param array       $hook_extra    Các tham số bổ sung được truyền vào bộ lọc hook.
		 */
		$source = apply_filters( 'upgrader_source_selection', $source, $remote_source, $this, $args['hook_extra'] );

		if ( is_wp_error( $source ) ) {
			return $source;
		}

		if ( ! empty( $args['hook_extra']['temp_backup'] ) ) {
			$temp_backup = $this->move_to_temp_backup_dir( $args['hook_extra']['temp_backup'] );

			if ( is_wp_error( $temp_backup ) ) {
				return $temp_backup;
			}

			$this->temp_backups[] = $args['hook_extra']['temp_backup'];
		}

		// Vị trí nguồn đã thay đổi chưa? Nếu có, cần danh sách source_files mới.
		if ( $source !== $remote_source ) {
			$dirlist = $wp_filesystem->dirlist( $source );

			if ( false === $dirlist ) {
				return new WP_Error( 'new_source_read_failed', $this->strings['fs_error'], $this->strings['dir_not_readable'] );
			}

			$source_files = array_keys( $dirlist );
		}

		/*
		 * Bảo vệ chống xóa tệp trong các thư mục gốc quan trọng.
		 * Theme_Upgrader & Plugin_Upgrader cũng kích hoạt điều này, khi chúng truyền
		 * thư mục đích (WP_PLUGIN_DIR / wp-content/themes) với ý định sao chép
		 * thư mục vào thư mục, trong khi chúng truyền nguồn là các tệp thực tế cần sao chép.
		 */
		$protected_directories = array( ABSPATH, WP_CONTENT_DIR, WP_PLUGIN_DIR, WP_CONTENT_DIR . '/themes' );

		if ( is_array( $wp_theme_directories ) ) {
			$protected_directories = array_merge( $protected_directories, $wp_theme_directories );
		}

		if ( in_array( $destination, $protected_directories, true ) ) {
			$remote_destination = trailingslashit( $remote_destination ) . trailingslashit( basename( $source ) );
			$destination        = trailingslashit( $destination ) . trailingslashit( basename( $source ) );
		}

		if ( $clear_destination ) {
			// Sẽ xóa sạch đích nếu có gì đó ở đó.
			$this->skin->feedback( 'remove_old' );

			$removed = $this->clear_destination( $remote_destination );

			/**
			 * Lọc xem trình nâng cấp đã xóa sạch đích hay chưa.
			 *
			 * @since 2.8.0
			 *
			 * @param true|WP_Error $removed            Đích đã được xóa hay chưa.
			 *                                          True khi thành công, WP_Error khi thất bại.
			 * @param string        $local_destination  Đích gói cục bộ.
			 * @param string        $remote_destination Đích gói từ xa.
			 * @param array         $hook_extra         Các tham số bổ sung được truyền vào bộ lọc hook.
			 */
			$removed = apply_filters( 'upgrader_clear_destination', $removed, $local_destination, $remote_destination, $args['hook_extra'] );

			if ( is_wp_error( $removed ) ) {
				return $removed;
			}
		} elseif ( $args['abort_if_destination_exists'] && $wp_filesystem->exists( $remote_destination ) ) {
			/*
			 * Nếu không xóa sạch thư mục đích và đã có gì đó ở đó, hủy bỏ.
			 * Nhưng trước tiên kiểm tra xem thực sự có tệp nào trong thư mục không.
			 */
			$_files = $wp_filesystem->dirlist( $remote_destination );
			if ( ! empty( $_files ) ) {
				$wp_filesystem->delete( $remote_source, true ); // Xóa sạch các tệp nguồn.
				return new WP_Error( 'folder_exists', $this->strings['folder_exists'], $remote_destination );
			}
		}

		/*
		 * Nếu 'clear_working' là false, nguồn không nên bị xóa, nên dùng copy_dir() thay thế.
		 *
		 * Các bản cập nhật từng phần, như gói ngôn ngữ, có thể muốn giữ lại đích.
		 * Nếu đích tồn tại hoặc có nội dung, đây có thể là bản cập nhật từng phần,
		 * và đích không nên bị xóa, nên dùng copy_dir() thay thế.
		 */
		if ( $args['clear_working']
			&& (
				// Đích không tồn tại hoặc không có nội dung.
				! $wp_filesystem->exists( $remote_destination )
				|| empty( $wp_filesystem->dirlist( $remote_destination ) )
			)
		) {
			$result = move_dir( $source, $remote_destination, true );
		} else {
			// Tạo đích nếu cần.
			if ( ! $wp_filesystem->exists( $remote_destination ) ) {
				if ( ! $wp_filesystem->mkdir( $remote_destination, FS_CHMOD_DIR ) ) {
					return new WP_Error( 'mkdir_failed_destination', $this->strings['mkdir_failed'], $remote_destination );
				}
			}
			$result = copy_dir( $source, $remote_destination );
		}

		// Xóa thư mục làm việc?
		if ( $args['clear_working'] ) {
			$wp_filesystem->delete( $remote_source, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$destination_name = basename( str_replace( $local_destination, '', $destination ) );
		if ( '.' === $destination_name ) {
			$destination_name = '';
		}

		$this->result = compact( 'source', 'source_files', 'destination', 'destination_name', 'local_destination', 'remote_destination', 'clear_destination' );

		/**
		 * Lọc phản hồi cài đặt sau khi quá trình cài đặt hoàn tất.
		 *
		 * @since 2.8.0
		 *
		 * @param bool  $response   Phản hồi cài đặt.
		 * @param array $hook_extra Các tham số bổ sung được truyền vào bộ lọc hook.
		 * @param array $result     Dữ liệu kết quả cài đặt.
		 */
		$res = apply_filters( 'upgrader_post_install', true, $args['hook_extra'], $this->result );

		if ( is_wp_error( $res ) ) {
			$this->result = $res;
			return $res;
		}

		// Trả về cho hàm gọi tất cả thông tin vừa được sử dụng.
		return $this->result;
	}

	/**
	 * Chạy quá trình nâng cấp/cài đặt.
	 *
	 * Cố tải xuống gói (nếu không phải tệp cục bộ), giải nén nó,
	 * và cài đặt vào thư mục đích.
	 *
	 * @since 2.8.0
	 *
	 * @param array $options {
	 *     Mảng hoặc chuỗi các tham số để nâng cấp/cài đặt gói.
	 *
	 *     @type string $package                     Đường dẫn đầy đủ hoặc URI của gói cần cài đặt.
	 *                                               Mặc định rỗng.
	 *     @type string $destination                 Đường dẫn đầy đủ tới thư mục đích.
	 *                                               Mặc định rỗng.
	 *     @type bool   $clear_destination           Có xóa các tệp đã có trong thư mục đích hay không.
	 *                                               Mặc định false.
	 *     @type bool   $clear_working               Có xóa các tệp từ thư mục làm việc sau khi
	 *                                               sao chép tới đích hay không. Mặc định true.
	 *     @type bool   $abort_if_destination_exists Có hủy cài đặt nếu thư mục đích đã tồn tại hay không.
	 *                                               Khi true, `$clear_destination` nên là false.
	 *                                               Mặc định true.
	 *     @type bool   $is_multi                    Có phải lần chạy này là một trong nhiều hành động
	 *                                               nâng cấp/cài đặt đang được thực hiện hàng loạt hay không.
	 *                                               Khi true, skin WP_Upgrader::header() và
	 *                                               WP_Upgrader::footer() không được gọi. Mặc định false.
	 *     @type array  $hook_extra                  Các tham số bổ sung để truyền vào bộ lọc hook
	 *                                               được gọi bởi WP_Upgrader::run().
	 * }
	 * @return array|false|WP_Error Kết quả từ self::install_package() khi thành công, ngược lại WP_Error,
	 *                              hoặc false nếu không thể kết nối tới hệ thống tệp.
	 */
	public function run( $options ) {

		$defaults = array(
			'package'                     => '', // Xin hãy luôn truyền giá trị này.
			'destination'                 => '', // ...và giá trị này.
			'clear_destination'           => false,
			'clear_working'               => true,
			'abort_if_destination_exists' => true, // Hủy nếu thư mục đích tồn tại. Xin hãy truyền clear_destination là false.
			'is_multi'                    => false,
			'hook_extra'                  => array(), // Truyền bất kỳ tham số $hook_extra bổ sung nào ở đây, sẽ được truyền vào các bộ lọc hook.
		);

		$options = wp_parse_args( $options, $defaults );

		/**
		 * Lọc các tùy chọn gói trước khi chạy bản cập nhật.
		 *
		 * Xem thêm {@see 'upgrader_process_complete'}.
		 *
		 * @since 4.3.0
		 *
		 * @param array $options {
		 *     Các tùy chọn được sử dụng bởi trình nâng cấp.
		 *
		 *     @type string $package                     Gói cần cập nhật.
		 *     @type string $destination                 Vị trí cập nhật.
		 *     @type bool   $clear_destination           Xóa sạch tài nguyên đích.
		 *     @type bool   $clear_working               Xóa sạch tài nguyên làm việc.
		 *     @type bool   $abort_if_destination_exists Hủy nếu thư mục đích tồn tại.
		 *     @type bool   $is_multi                    Trình nâng cấp có đang chạy nhiều lần hay không.
		 *     @type array  $hook_extra {
		 *         Các tham số hook bổ sung.
		 *
		 *         @type string $action               Loại hành động. Mặc định 'update'.
		 *         @type string $type                 Loại quá trình cập nhật. Chấp nhận 'plugin', 'theme', hoặc 'core'.
		 *         @type bool   $bulk                 Quá trình cập nhật có phải hàng loạt không. Mặc định true.
		 *         @type string $plugin               Đường dẫn tệp plugin tương đối so với thư mục plugin.
		 *         @type string $theme                Tên stylesheet hoặc template của giao diện.
		 *         @type string $language_update_type Loại cập nhật gói ngôn ngữ. Chấp nhận 'plugin', 'theme',
		 *                                            hoặc 'core'.
		 *         @type object $language_update      Bản cập nhật gói ngôn ngữ được đề xuất.
		 *     }
		 * }
		 */
		$options = apply_filters( 'upgrader_package_options', $options );

		if ( ! $options['is_multi'] ) { // Gọi $this->header riêng nếu chạy nhiều lần.
			$this->skin->header();
		}

		// Kết nối tới hệ thống tệp trước.
		$res = $this->fs_connect( array( WP_CONTENT_DIR, $options['destination'] ) );
		// Chủ yếu cho hệ thống tệp chưa kết nối.
		if ( ! $res ) {
			if ( ! $options['is_multi'] ) {
				$this->skin->footer();
			}
			return false;
		}

		$this->skin->before();

		if ( is_wp_error( $res ) ) {
			$this->skin->error( $res );
			$this->skin->after();
			if ( ! $options['is_multi'] ) {
				$this->skin->footer();
			}
			return $res;
		}

		/*
		 * Tải xuống gói. Lưu ý: Nếu gói là đường dẫn đầy đủ
		 * tới tệp cục bộ hiện có, nó sẽ được trả về nguyên trạng.
		 */
		$download = $this->download_package( $options['package'], false, $options['hook_extra'] );

		/*
		 * Cho phép chữ ký lỗi mềm.
		 * CẢNH BÁO: Điều này có thể bị loại bỏ trong tương lai.
		 */
		if ( is_wp_error( $download ) && $download->get_error_data( 'softfail-filename' ) ) {

			// Tạm thời không xuất thông báo lỗi 'không tìm thấy chữ ký'.
			if ( 'signature_verification_no_signature' !== $download->get_error_code() || WP_DEBUG ) {
				// Xuất lỗi thất bại dưới dạng phản hồi bình thường, không phải lỗi.
				$this->skin->feedback( $download->get_error_message() );

				// Báo cáo lỗi này về WordPress.org cho mục đích gỡ lỗi.
				wp_version_check(
					array(
						'signature_failure_code' => $download->get_error_code(),
						'signature_failure_data' => $download->get_error_data(),
					)
				);
			}

			// Giả vờ lỗi này không xảy ra.
			$download = $download->get_error_data( 'softfail-filename' );
		}

		if ( is_wp_error( $download ) ) {
			$this->skin->error( $download );
			$this->skin->after();
			if ( ! $options['is_multi'] ) {
				$this->skin->footer();
			}
			return $download;
		}

		$delete_package = ( $download !== $options['package'] ); // Không xóa tệp "cục bộ".

		// Giải nén tệp vào thư mục tạm thời.
		$working_dir = $this->unpack_package( $download, $delete_package );
		if ( is_wp_error( $working_dir ) ) {
			$this->skin->error( $working_dir );
			$this->skin->after();
			if ( ! $options['is_multi'] ) {
				$this->skin->footer();
			}
			return $working_dir;
		}

		// Với các tùy chọn đã cho, cài đặt gói vào thư mục đích.
		$result = $this->install_package(
			array(
				'source'                      => $working_dir,
				'destination'                 => $options['destination'],
				'clear_destination'           => $options['clear_destination'],
				'abort_if_destination_exists' => $options['abort_if_destination_exists'],
				'clear_working'               => $options['clear_working'],
				'hook_extra'                  => $options['hook_extra'],
			)
		);

		/**
		 * Lọc kết quả từ WP_Upgrader::install_package().
		 *
		 * @since 5.7.0
		 *
		 * @param array|WP_Error $result     Kết quả từ WP_Upgrader::install_package().
		 * @param array          $hook_extra Các tham số bổ sung được truyền vào bộ lọc hook.
		 */
		$result = apply_filters( 'upgrader_install_package_result', $result, $options['hook_extra'] );

		$this->skin->set_result( $result );

		if ( is_wp_error( $result ) ) {
			// Bản cập nhật plugin tự động sẽ đã thực hiện khôi phục.
			if ( ! empty( $options['hook_extra']['temp_backup'] ) ) {
				$this->temp_restores[] = $options['hook_extra']['temp_backup'];

				/*
				 * Khôi phục bản sao lưu khi tắt máy.
				 * Các hành động chạy trên `shutdown` miễn nhiễm với hết giờ PHP,
				 * nên trong trường hợp thất bại do hết giờ PHP,
				 * nó vẫn có thể khôi phục đúng phiên bản trước đó.
				 *
				 * Không chấp nhận tham số vì đôi khi một chuỗi có thể được truyền
				 * nội bộ trong các hành động, gây lỗi vì
				 * `WP_Upgrader::restore_temp_backup()` yêu cầu một mảng.
				 */
				add_action( 'shutdown', array( $this, 'restore_temp_backup' ), 10, 0 );
			}
			$this->skin->error( $result );

			if ( ! method_exists( $this->skin, 'hide_process_failed' ) || ! $this->skin->hide_process_failed( $result ) ) {
				$this->skin->feedback( 'process_failed' );
			}
		} else {
			// Cài đặt thành công.
			$this->skin->feedback( 'process_success' );
		}

		$this->skin->after();

		// Dọn dẹp bản sao lưu được giữ trong thư mục sao lưu tạm thời.
		if ( ! empty( $options['hook_extra']['temp_backup'] ) ) {
			// Xóa bản sao lưu khi `shutdown` để tránh hết giờ PHP.
			add_action( 'shutdown', array( $this, 'delete_temp_backup' ), 100, 0 );
		}

		if ( ! $options['is_multi'] ) {

			/**
			 * Kích hoạt khi quá trình nâng cấp hoàn tất.
			 *
			 * Xem thêm {@see 'upgrader_package_options'}.
			 *
			 * @since 3.6.0
			 * @since 3.7.0 Thêm vào WP_Upgrader::run().
			 * @since 4.6.0 `$translations` được thêm làm tham số có thể cho `$hook_extra`.
			 *
			 * @param WP_Upgrader $upgrader   Thực thể WP_Upgrader. Trong ngữ cảnh khác có thể là
			 *                                Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, hoặc Language_Pack_Upgrader.
			 * @param array       $hook_extra {
			 *     Mảng dữ liệu cập nhật mục hàng loạt.
			 *
			 *     @type string $action       Loại hành động. Mặc định 'update'.
			 *     @type string $type         Loại quá trình cập nhật. Chấp nhận 'plugin', 'theme', 'translation', hoặc 'core'.
			 *     @type bool   $bulk         Quá trình cập nhật có phải hàng loạt không. Mặc định true.
			 *     @type array  $plugins      Mảng đường dẫn gốc của tệp chính các plugin.
			 *     @type array  $themes       Các slug giao diện.
			 *     @type array  $translations {
			 *         Mảng dữ liệu cập nhật bản dịch.
			 *
			 *         @type string $language Ngôn ngữ mà bản dịch dành cho.
			 *         @type string $type     Loại bản dịch. Chấp nhận 'plugin', 'theme', hoặc 'core'.
			 *         @type string $slug     Text domain mà bản dịch dành cho. Slug của giao diện/plugin hoặc
			 *                                'default' cho bản dịch lõi.
			 *         @type string $version  Phiên bản của giao diện, plugin, hoặc lõi.
			 *     }
			 * }
			 */
			do_action( 'upgrader_process_complete', $this, $options['hook_extra'] );

			$this->skin->footer();
		}

		return $result;
	}

	/**
	 * Bật/tắt chế độ bảo trì cho trang web.
	 *
	 * Tạo/xóa tệp bảo trì để bật/tắt chế độ bảo trì.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param bool $enable True để bật chế độ bảo trì, false để tắt.
	 */
	public function maintenance_mode( $enable = false ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			ob_start();
			$credentials = request_filesystem_credentials( '' );
			ob_end_clean();

			if ( false === $credentials || ! WP_Filesystem( $credentials ) ) {
				wp_trigger_error( __FUNCTION__, __( 'Could not access filesystem.' ) );
				return;
			}
		}

		$file = $wp_filesystem->abspath() . '.maintenance';
		if ( $enable ) {
			if ( ! wp_doing_cron() ) {
				$this->skin->feedback( 'maintenance_start' );
			}
			// Tạo tệp bảo trì để báo hiệu rằng chúng ta đang nâng cấp.
			$maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
			$wp_filesystem->delete( $file );
			$wp_filesystem->put_contents( $file, $maintenance_string, FS_CHMOD_FILE );
		} elseif ( ! $enable && $wp_filesystem->exists( $file ) ) {
			if ( ! wp_doing_cron() ) {
				$this->skin->feedback( 'maintenance_end' );
			}
			$wp_filesystem->delete( $file );
		}
	}

	/**
	 * Tạo khóa sử dụng tùy chọn WordPress.
	 *
	 * @since 4.5.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
	 *
	 * @param string $lock_name       Tên của khóa duy nhất này.
	 * @param int    $release_timeout Tùy chọn. Thời gian tính bằng giây để tôn trọng khóa hiện có.
	 *                                Mặc định: 1 giờ.
	 * @return bool False nếu không thể tạo khóa hoặc khóa vẫn còn hiệu lực. True nếu ngược lại.
	 */
	public static function create_lock( $lock_name, $release_timeout = null ) {
		global $wpdb;
		if ( ! $release_timeout ) {
			$release_timeout = HOUR_IN_SECONDS;
		}
		$lock_option = $lock_name . '.lock';

		// Cố tạo khóa.
		$lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'off') /* LOCK */", $lock_option, time() ) );

		if ( ! $lock_result ) {
			$lock_result = get_option( $lock_option );

			// Nếu không thể tạo khóa, và không có khóa, thoát.
			if ( ! $lock_result ) {
				return false;
			}

			// Kiểm tra xem khóa có còn hiệu lực không. Nếu có, thoát.
			if ( $lock_result > ( time() - $release_timeout ) ) {
				return false;
			}

			// Phải có khóa đã hết hạn, xóa nó và lấy lại.
			WP_Upgrader::release_lock( $lock_name );

			return WP_Upgrader::create_lock( $lock_name, $release_timeout );
		}

		// Cập nhật khóa, vì tại thời điểm này chắc chắn đã có khóa, chỉ cần kích hoạt các hành động.
		update_option( $lock_option, time(), false );

		return true;
	}

	/**
	 * Giải phóng khóa nâng cấp.
	 *
	 * @since 4.5.0
	 *
	 * @see WP_Upgrader::create_lock()
	 *
	 * @param string $lock_name Tên của khóa duy nhất này.
	 * @return bool True nếu khóa được giải phóng thành công. False khi thất bại.
	 */
	public static function release_lock( $lock_name ) {
		return delete_option( $lock_name . '.lock' );
	}

	/**
	 * Di chuyển plugin hoặc giao diện đang được cập nhật vào thư mục sao lưu tạm thời.
	 *
	 * @since 6.3.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param string[] $args {
	 *     Mảng dữ liệu cho bản sao lưu tạm thời.
	 *
	 *     @type string $slug Slug của plugin hoặc giao diện.
	 *     @type string $src  Đường dẫn tới thư mục gốc cho plugin hoặc giao diện.
	 *     @type string $dir  Tên thư mục con đích. Chấp nhận 'plugins' hoặc 'themes'.
	 * }
	 *
	 * @return bool|WP_Error True khi thành công, false khi thoát sớm, ngược lại WP_Error.
	 */
	public function move_to_temp_backup_dir( $args ) {
		global $wp_filesystem;

		if ( empty( $args['slug'] ) || empty( $args['src'] ) || empty( $args['dir'] ) ) {
			return false;
		}

		/*
		 * Bỏ qua bất kỳ plugin nào có slug là ".".
		 * Slug "." sẽ dẫn đến giá trị `$src` kết thúc bằng dấu chấm.
		 *
		 * Trên Windows, điều này sẽ khiến thư mục 'plugins' bị di chuyển,
		 * và sẽ gây lỗi khi cố gọi `mkdir()`.
		 */
		if ( '.' === $args['slug'] ) {
			return false;
		}

		if ( ! $wp_filesystem->wp_content_dir() ) {
			return new WP_Error( 'fs_no_content_dir', $this->strings['fs_no_content_dir'] );
		}

		$dest_dir = $wp_filesystem->wp_content_dir() . 'upgrade-temp-backup/';
		$sub_dir  = $dest_dir . $args['dir'] . '/';

		// Tạo thư mục sao lưu tạm thời nếu nó chưa tồn tại.
		if ( ! $wp_filesystem->is_dir( $sub_dir ) ) {
			if ( ! $wp_filesystem->is_dir( $dest_dir ) ) {
				$wp_filesystem->mkdir( $dest_dir, FS_CHMOD_DIR );
			}

			if ( ! $wp_filesystem->mkdir( $sub_dir, FS_CHMOD_DIR ) ) {
				// Không thể tạo thư mục sao lưu.
				return new WP_Error( 'fs_temp_backup_mkdir', $this->strings['temp_backup_mkdir_failed'] );
			}
		}

		$src_dir = $wp_filesystem->find_folder( $args['src'] );
		$src     = trailingslashit( $src_dir ) . $args['slug'];
		$dest    = $dest_dir . trailingslashit( $args['dir'] ) . $args['slug'];

		// Xóa thư mục sao lưu tạm thời nếu nó đã tồn tại.
		if ( $wp_filesystem->is_dir( $dest ) ) {
			$wp_filesystem->delete( $dest, true );
		}

		// Di chuyển vào thư mục sao lưu tạm thời.
		$result = move_dir( $src, $dest, true );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'fs_temp_backup_move', $this->strings['temp_backup_move_failed'] );
		}

		return true;
	}

	/**
	 * Khôi phục plugin hoặc giao diện từ bản sao lưu tạm thời.
	 *
	 * @since 6.3.0
	 * @since 6.6.0 Thêm tham số `$temp_backups`.
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param array[] $temp_backups {
	 *     Tùy chọn. Mảng các bản sao lưu tạm thời.
	 *
	 *     @type array ...$0 {
	 *         Thông tin về bản sao lưu.
	 *
	 *         @type string $dir  Vị trí sao lưu tạm thời trong thư mục upgrade-temp-backup.
	 *         @type string $slug Slug của mục.
	 *         @type string $src  Thư mục lưu trữ bản gốc. Ví dụ: `WP_PLUGIN_DIR`.
	 *     }
	 * }
	 * @return bool|WP_Error True khi thành công, false khi thoát sớm, ngược lại WP_Error.
	 */
	public function restore_temp_backup( array $temp_backups = array() ) {
		global $wp_filesystem;

		$errors = new WP_Error();

		if ( empty( $temp_backups ) ) {
			$temp_backups = $this->temp_restores;
		}

		foreach ( $temp_backups as $args ) {
			if ( empty( $args['slug'] ) || empty( $args['src'] ) || empty( $args['dir'] ) ) {
				return false;
			}

			if ( ! $wp_filesystem->wp_content_dir() ) {
				$errors->add( 'fs_no_content_dir', $this->strings['fs_no_content_dir'] );
				return $errors;
			}

			$src      = $wp_filesystem->wp_content_dir() . 'upgrade-temp-backup/' . $args['dir'] . '/' . $args['slug'];
			$dest_dir = $wp_filesystem->find_folder( $args['src'] );
			$dest     = trailingslashit( $dest_dir ) . $args['slug'];

			if ( $wp_filesystem->is_dir( $src ) ) {
				// Dọn dẹp.
				if ( $wp_filesystem->is_dir( $dest ) && ! $wp_filesystem->delete( $dest, true ) ) {
					$errors->add(
						'fs_temp_backup_delete',
						sprintf( $this->strings['temp_backup_restore_failed'], $args['slug'] )
					);
					continue;
				}

				// Di chuyển.
				$result = move_dir( $src, $dest, true );
				if ( is_wp_error( $result ) ) {
					$errors->add(
						'fs_temp_backup_delete',
						sprintf( $this->strings['temp_backup_restore_failed'], $args['slug'] )
					);
					continue;
				}
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Xóa bản sao lưu tạm thời.
	 *
	 * @since 6.3.0
	 * @since 6.6.0 Thêm tham số `$temp_backups`.
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param array[] $temp_backups {
	 *     Tùy chọn. Mảng các bản sao lưu tạm thời.
	 *
	 *     @type array ...$0 {
	 *         Thông tin về bản sao lưu.
	 *
	 *         @type string $dir  Vị trí sao lưu tạm thời trong thư mục upgrade-temp-backup.
	 *         @type string $slug Slug của mục.
	 *         @type string $src  Thư mục lưu trữ bản gốc. Ví dụ: `WP_PLUGIN_DIR`.
	 *     }
	 * }
	 * @return bool|WP_Error True khi thành công, false khi thoát sớm, ngược lại WP_Error.
	 */
	public function delete_temp_backup( array $temp_backups = array() ) {
		global $wp_filesystem;

		$errors = new WP_Error();

		if ( empty( $temp_backups ) ) {
			$temp_backups = $this->temp_backups;
		}

		foreach ( $temp_backups as $args ) {
			if ( empty( $args['slug'] ) || empty( $args['dir'] ) ) {
				return false;
			}

			if ( ! $wp_filesystem->wp_content_dir() ) {
				$errors->add( 'fs_no_content_dir', $this->strings['fs_no_content_dir'] );
				return $errors;
			}

			$temp_backup_dir = $wp_filesystem->wp_content_dir() . "upgrade-temp-backup/{$args['dir']}/{$args['slug']}";

			if ( ! $wp_filesystem->delete( $temp_backup_dir, true ) ) {
				$errors->add(
					'temp_backup_delete_failed',
					sprintf( $this->strings['temp_backup_delete_failed'], $args['slug'] )
				);
				continue;
			}
		}

		return $errors->has_errors() ? $errors : true;
	}
}

/** Lớp Plugin_Upgrader */
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

/** Lớp Theme_Upgrader */
require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader.php';

/** Lớp Language_Pack_Upgrader */
require_once ABSPATH . 'wp-admin/includes/class-language-pack-upgrader.php';

/** Lớp Core_Upgrader */
require_once ABSPATH . 'wp-admin/includes/class-core-upgrader.php';

/** Lớp File_Upload_Upgrader */
require_once ABSPATH . 'wp-admin/includes/class-file-upload-upgrader.php';

/** Lớp WP_Automatic_Updater */
require_once ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php';
