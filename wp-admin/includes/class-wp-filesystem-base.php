<?php
/**
 * Lớp hệ thống tệp cơ sở của WordPress
 *
 * @package WordPress
 * @subpackage Filesystem
 */

/**
 * Lớp hệ thống tệp cơ sở của WordPress mà các triển khai Filesystem kế thừa.
 *
 * @since 2.5.0
 */
#[AllowDynamicProperties]
class WP_Filesystem_Base {

	/**
	 * Có hiển thị dữ liệu gỡ lỗi cho kết nối hay không.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	public $verbose = false;

	/**
	 * Danh sách đã lưu cache của đường dẫn tệp cục bộ ánh xạ tới đường dẫn tệp từ xa.
	 *
	 * @since 2.7.0
	 * @var array
	 */
	public $cache = array();

	/**
	 * Phương thức truy cập của kết nối hiện tại, được thiết lập tự động.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	public $method = '';

	/**
	 * @var WP_Error
	 */
	public $errors = null;

	/**
	 */
	public $options = array();

	/**
	 * Trả về đường dẫn trên hệ thống tệp từ xa của ABSPATH.
	 *
	 * @since 2.7.0
	 *
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function abspath() {
		$folder = $this->find_folder( ABSPATH );

		/*
		 * Có thể thư mục FTP được gốc tại thư mục cài đặt WordPress.
		 * Kiểm tra thư mục wp-includes trong thư mục gốc. Có thể có một số kết quả dương tính giả, nhưng hiếm.
		 */
		if ( ! $folder && $this->is_dir( '/' . WPINC ) ) {
			$folder = '/';
		}

		return $folder;
	}

	/**
	 * Trả về đường dẫn trên hệ thống tệp từ xa của WP_CONTENT_DIR.
	 *
	 * @since 2.7.0
	 *
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function wp_content_dir() {
		return $this->find_folder( WP_CONTENT_DIR );
	}

	/**
	 * Trả về đường dẫn trên hệ thống tệp từ xa của WP_PLUGIN_DIR.
	 *
	 * @since 2.7.0
	 *
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function wp_plugins_dir() {
		return $this->find_folder( WP_PLUGIN_DIR );
	}

	/**
	 * Trả về đường dẫn trên hệ thống tệp từ xa của thư mục Themes.
	 *
	 * @since 2.7.0
	 *
	 * @param string|false $theme Tùy chọn. Stylesheet hoặc template của giao diện cho thư mục.
	 *                            Mặc định false.
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function wp_themes_dir( $theme = false ) {
		$theme_root = get_theme_root( $theme );

		// Xử lý cho các thư mục gốc giao diện tương đối.
		if ( '/themes' === $theme_root || ! is_dir( $theme_root ) ) {
			$theme_root = WP_CONTENT_DIR . $theme_root;
		}

		return $this->find_folder( $theme_root );
	}

	/**
	 * Trả về đường dẫn trên hệ thống tệp từ xa của WP_LANG_DIR.
	 *
	 * @since 3.2.0
	 *
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function wp_lang_dir() {
		return $this->find_folder( WP_LANG_DIR );
	}

	/**
	 * Định vị thư mục trên hệ thống tệp từ xa.
	 *
	 * @since 2.5.0
	 * @deprecated 2.7.0 sử dụng WP_Filesystem_Base::abspath() hoặc WP_Filesystem_Base::wp_*_dir() thay thế.
	 * @see WP_Filesystem_Base::abspath()
	 * @see WP_Filesystem_Base::wp_content_dir()
	 * @see WP_Filesystem_Base::wp_plugins_dir()
	 * @see WP_Filesystem_Base::wp_themes_dir()
	 * @see WP_Filesystem_Base::wp_lang_dir()
	 *
	 * @param string $base    Tùy chọn. Thư mục bắt đầu tìm kiếm. Mặc định '.'.
	 * @param bool   $verbose Tùy chọn. True để hiển thị thông tin gỡ lỗi. Mặc định false.
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function find_base_dir( $base = '.', $verbose = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'WP_Filesystem_Base::abspath() or WP_Filesystem_Base::wp_*_dir()' );
		$this->verbose = $verbose;
		return $this->abspath();
	}

	/**
	 * Định vị thư mục trên hệ thống tệp từ xa.
	 *
	 * @since 2.5.0
	 * @deprecated 2.7.0 sử dụng WP_Filesystem_Base::abspath() hoặc các phương thức WP_Filesystem_Base::wp_*_dir() thay thế.
	 * @see WP_Filesystem_Base::abspath()
	 * @see WP_Filesystem_Base::wp_content_dir()
	 * @see WP_Filesystem_Base::wp_plugins_dir()
	 * @see WP_Filesystem_Base::wp_themes_dir()
	 * @see WP_Filesystem_Base::wp_lang_dir()
	 *
	 * @param string $base    Tùy chọn. Thư mục bắt đầu tìm kiếm. Mặc định '.'.
	 * @param bool   $verbose Tùy chọn. True để hiển thị thông tin gỡ lỗi. Mặc định false.
	 * @return string Vị trí của đường dẫn từ xa.
	 */
	public function get_base_dir( $base = '.', $verbose = false ) {
		_deprecated_function( __FUNCTION__, '2.7.0', 'WP_Filesystem_Base::abspath() or WP_Filesystem_Base::wp_*_dir()' );
		$this->verbose = $verbose;
		return $this->abspath();
	}

	/**
	 * Định vị thư mục trên hệ thống tệp từ xa.
	 *
	 * Giả định rằng trên hệ thống Windows, việc loại bỏ ký tự ổ đĩa
	 * là chấp nhận được. Chuẩn hóa \\ thành / trong đường dẫn tệp Windows.
	 *
	 * @since 2.7.0
	 *
	 * @param string $folder Thư mục cần định vị.
	 * @return string|false Vị trí của đường dẫn từ xa, false nếu thất bại.
	 */
	public function find_folder( $folder ) {
		if ( isset( $this->cache[ $folder ] ) ) {
			return $this->cache[ $folder ];
		}

		if ( stripos( $this->method, 'ftp' ) !== false ) {
			$constant_overrides = array(
				'FTP_BASE'        => ABSPATH,
				'FTP_CONTENT_DIR' => WP_CONTENT_DIR,
				'FTP_PLUGIN_DIR'  => WP_PLUGIN_DIR,
				'FTP_LANG_DIR'    => WP_LANG_DIR,
			);

			// Khớp trực tiếp ( folder = CONSTANT/ ).
			foreach ( $constant_overrides as $constant => $dir ) {
				if ( ! defined( $constant ) ) {
					continue;
				}

				if ( $folder === $dir ) {
					return trailingslashit( constant( $constant ) );
				}
			}

			// Khớp tiền tố ( folder = CONSTANT/subdir ),
			foreach ( $constant_overrides as $constant => $dir ) {
				if ( ! defined( $constant ) ) {
					continue;
				}

				if ( 0 === stripos( $folder, $dir ) ) { // $folder bắt đầu bằng $dir.
					$potential_folder = preg_replace( '#^' . preg_quote( $dir, '#' ) . '/#i', trailingslashit( constant( $constant ) ), $folder );
					$potential_folder = trailingslashit( $potential_folder );

					if ( $this->is_dir( $potential_folder ) ) {
						$this->cache[ $folder ] = $potential_folder;

						return $potential_folder;
					}
				}
			}
		} elseif ( 'direct' === $this->method ) {
			$folder = str_replace( '\\', '/', $folder ); // Chuẩn hóa đường dẫn Windows.

			return trailingslashit( $folder );
		}

		$folder = preg_replace( '|^([a-z]{1}):|i', '', $folder ); // Loại bỏ ký tự ổ đĩa Windows nếu có.
		$folder = str_replace( '\\', '/', $folder ); // Chuẩn hóa đường dẫn Windows.

		if ( isset( $this->cache[ $folder ] ) ) {
			return $this->cache[ $folder ];
		}

		if ( $this->exists( $folder ) ) { // Thư mục tồn tại tại đường dẫn tuyệt đối đó.
			$folder                 = trailingslashit( $folder );
			$this->cache[ $folder ] = $folder;

			return $folder;
		}

		$return = $this->search_for_folder( $folder );

		if ( $return ) {
			$this->cache[ $folder ] = $return;
		}

		return $return;
	}

	/**
	 * Định vị thư mục trên hệ thống tệp từ xa.
	 *
	 * Yêu cầu đường dẫn đã được chuẩn hóa theo Windows.
	 *
	 * @since 2.7.0
	 *
	 * @param string $folder Thư mục cần định vị.
	 * @param string $base   Thư mục bắt đầu tìm kiếm.
	 * @param bool   $loop   Nếu hàm đã đệ quy. Chỉ sử dụng nội bộ.
	 * @return string|false Vị trí của đường dẫn từ xa, false để dừng vòng lặp.
	 */
	public function search_for_folder( $folder, $base = '.', $loop = false ) {
		if ( empty( $base ) || '.' === $base ) {
			$base = trailingslashit( $this->cwd() );
		}

		$folder = untrailingslashit( $folder );

		if ( $this->verbose ) {
			/* translators: 1: Folder to locate, 2: Folder to start searching from. */
			printf( "\n" . __( 'Looking for %1$s in %2$s' ) . "<br />\n", $folder, $base );
		}

		$folder_parts     = explode( '/', $folder );
		$folder_part_keys = array_keys( $folder_parts );
		$last_index       = array_pop( $folder_part_keys );
		$last_path        = $folder_parts[ $last_index ];

		$files = $this->dirlist( $base );

		foreach ( $folder_parts as $index => $key ) {
			if ( $index === $last_index ) {
				continue; // Chúng ta muốn phần này được xử lý bởi khối mã tiếp theo.
			}

			/*
			 * Làm việc từ /home/ đến /user/ đến /wordpress/ xem tệp đó có tồn tại trong
			 * thư mục hiện tại không. Nếu tìm thấy, chuyển vào đó và tiếp tục tìm kiếm.
			 * Nếu không tìm thấy WordPress theo đường đó, sẽ tiếp tục đến cấp thư mục tiếp theo,
			 * và xem có khớp không, v.v. Nếu đạt đến cuối mà vẫn không tìm thấy,
			 * sẽ trả về false cho toàn bộ hàm.
			 */
			if ( isset( $files[ $key ] ) ) {

				// Hãy thử thư mục đó:
				$newdir = trailingslashit( path_join( $base, $key ) );

				if ( $this->verbose ) {
					/* translators: %s: Directory name. */
					printf( "\n" . __( 'Changing to %s' ) . "<br />\n", $newdir );
				}

				// Chỉ tìm kiếm các token đường dẫn còn lại trong thư mục, không phải toàn bộ đường dẫn.
				$newfolder = implode( '/', array_slice( $folder_parts, $index + 1 ) );
				$ret       = $this->search_for_folder( $newfolder, $newdir, $loop );

				if ( $ret ) {
					return $ret;
				}
			}
		}

		/*
		 * Chỉ kiểm tra điều này như phương án cuối cùng, để tránh định vị nhầm bản cài đặt.
		 * Tất cả các quy trình ở trên sẽ thất bại nhanh chóng nếu đây là nhánh đúng cần đi.
		 */
		if ( isset( $files[ $last_path ] ) ) {
			if ( $this->verbose ) {
				/* translators: %s: Directory name. */
				printf( "\n" . __( 'Found %s' ) . "<br />\n", $base . $last_path );
			}

			return trailingslashit( $base . $last_path );
		}

		/*
		 * Ngăn hàm này lặp lại lần nữa.
		 * Không cần tiếp tục nếu chúng ta vừa tìm kiếm trong `/`.
		 */
		if ( $loop || '/' === $base ) {
			return false;
		}

		/*
		 * Như một phương án cuối cùng bổ sung, quay lại / nếu thư mục không được tìm thấy.
		 * Điều này có hiệu lực khi CWD là /home/user/ nhưng WP ở /var/www/....
		 */
		return $this->search_for_folder( $folder, '/', true );
	}

	/**
	 * Trả về quyền tệp kiểu *nix cho một tệp.
	 *
	 * Từ trang tài liệu PHP cho fileperms().
	 *
	 * @link https://www.php.net/manual/en/function.fileperms.php
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Chuỗi tên tệp.
	 * @return string Biểu diễn quyền kiểu *nix.
	 */
	public function gethchmod( $file ) {
		$perms = intval( $this->getchmod( $file ), 8 );

		if ( ( $perms & 0xC000 ) === 0xC000 ) { // Socket.
			$info = 's';
		} elseif ( ( $perms & 0xA000 ) === 0xA000 ) { // Liên kết tượng trưng.
			$info = 'l';
		} elseif ( ( $perms & 0x8000 ) === 0x8000 ) { // Thường.
			$info = '-';
		} elseif ( ( $perms & 0x6000 ) === 0x6000 ) { // Khối đặc biệt.
			$info = 'b';
		} elseif ( ( $perms & 0x4000 ) === 0x4000 ) { // Thư mục.
			$info = 'd';
		} elseif ( ( $perms & 0x2000 ) === 0x2000 ) { // Ký tự đặc biệt.
			$info = 'c';
		} elseif ( ( $perms & 0x1000 ) === 0x1000 ) { // Ống FIFO.
			$info = 'p';
		} else { // Không xác định.
			$info = 'u';
		}

		// Chủ sở hữu.
		$info .= ( ( $perms & 0x0100 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0080 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0040 ) ?
					( ( $perms & 0x0800 ) ? 's' : 'x' ) :
					( ( $perms & 0x0800 ) ? 'S' : '-' ) );

		// Nhóm.
		$info .= ( ( $perms & 0x0020 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0010 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0008 ) ?
					( ( $perms & 0x0400 ) ? 's' : 'x' ) :
					( ( $perms & 0x0400 ) ? 'S' : '-' ) );

		// Mọi người.
		$info .= ( ( $perms & 0x0004 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0002 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0001 ) ?
					( ( $perms & 0x0200 ) ? 't' : 'x' ) :
					( ( $perms & 0x0200 ) ? 'T' : '-' ) );

		return $info;
	}

	/**
	 * Lấy quyền của tệp hoặc đường dẫn được chỉ định ở dạng bát phân.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string Chế độ của tệp (3 chữ số cuối).
	 */
	public function getchmod( $file ) {
		return '777';
	}

	/**
	 * Chuyển đổi quyền tệp kiểu *nix thành số bát phân.
	 *
	 * Chuyển đổi '-rw-r--r--' thành 0644
	 * Từ bình luận của "info at rvgate dot nl" trên tài liệu PHP cho chmod()
	 *
	 * @link https://www.php.net/manual/en/function.chmod.php#49614
	 *
	 * @since 2.5.0
	 *
	 * @param string $mode chuỗi Quyền tệp kiểu *nix.
	 * @return string Biểu diễn bát phân của quyền.
	 */
	public function getnumchmodfromh( $mode ) {
		$realmode = '';
		$legal    = array( '', 'w', 'r', 'x', '-' );
		$attarray = preg_split( '//', $mode );

		for ( $i = 0, $c = count( $attarray ); $i < $c; $i++ ) {
			$key = array_search( $attarray[ $i ], $legal, true );

			if ( $key ) {
				$realmode .= $legal[ $key ];
			}
		}

		$mode  = str_pad( $realmode, 10, '-', STR_PAD_LEFT );
		$trans = array(
			'-' => '0',
			'r' => '4',
			'w' => '2',
			'x' => '1',
		);
		$mode  = strtr( $mode, $trans );

		$newmode  = $mode[0];
		$newmode .= $mode[1] + $mode[2] + $mode[3];
		$newmode .= $mode[4] + $mode[5] + $mode[6];
		$newmode .= $mode[7] + $mode[8] + $mode[9];

		return $newmode;
	}

	/**
	 * Xác định xem chuỗi được cung cấp có chứa ký tự nhị phân hay không.
	 *
	 * @since 2.7.0
	 *
	 * @param string $text Chuỗi cần kiểm tra.
	 * @return bool True nếu chuỗi là nhị phân, false nếu không.
	 */
	public function is_binary( $text ) {
		return (bool) preg_match( '|[^\x20-\x7E]|', $text ); // chr(32)..chr(127)
	}

	/**
	 * Thay đổi chủ sở hữu của tệp hoặc thư mục.
	 *
	 * Hành vi mặc định là không làm gì, ghi đè trong lớp con của bạn nếu muốn.
	 *
	 * @since 2.5.0
	 *
	 * @param string     $file      Đường dẫn đến tệp hoặc thư mục.
	 * @param string|int $owner     Tên người dùng hoặc số.
	 * @param bool       $recursive Tùy chọn. Nếu đặt là true, thay đổi chủ sở hữu tệp đệ quy.
	 *                              Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chown( $file, $owner, $recursive = false ) {
		return false;
	}

	/**
	 * Kết nối hệ thống tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @return bool True khi thành công, false khi thất bại (luôn true cho WP_Filesystem_Direct).
	 */
	public function connect() {
		return true;
	}

	/**
	 * Đọc toàn bộ tệp vào một chuỗi.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Tên tệp cần đọc.
	 * @return string|false Dữ liệu đọc được khi thành công, false khi thất bại.
	 */
	public function get_contents( $file ) {
		return false;
	}

	/**
	 * Đọc toàn bộ tệp vào một mảng.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return array|false Nội dung tệp trong một mảng khi thành công, false khi thất bại.
	 */
	public function get_contents_array( $file ) {
		return false;
	}

	/**
	 * Ghi một chuỗi vào tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $file     Đường dẫn từ xa đến tệp cần ghi dữ liệu.
	 * @param string    $contents Dữ liệu cần ghi.
	 * @param int|false $mode     Tùy chọn. Quyền tệp dạng số bát phân, thường là 0644.
	 *                            Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function put_contents( $file, $contents, $mode = false ) {
		return false;
	}

	/**
	 * Lấy thư mục làm việc hiện tại.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @return string|false Thư mục làm việc hiện tại khi thành công, false khi thất bại.
	 */
	public function cwd() {
		return false;
	}

	/**
	 * Thay đổi thư mục hiện tại.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $dir Thư mục hiện tại mới.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chdir( $dir ) {
		return false;
	}

	/**
	 * Thay đổi nhóm của tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string     $file      Đường dẫn đến tệp.
	 * @param string|int $group     Tên hoặc số nhóm.
	 * @param bool       $recursive Tùy chọn. Nếu đặt là true, thay đổi nhóm tệp đệ quy.
	 *                              Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chgrp( $file, $group, $recursive = false ) {
		return false;
	}

	/**
	 * Thay đổi quyền hệ thống tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $file      Đường dẫn đến tệp.
	 * @param int|false $mode      Tùy chọn. Quyền dạng số bát phân, thường là 0644 cho tệp,
	 *                             0755 cho thư mục. Mặc định false.
	 * @param bool      $recursive Tùy chọn. Nếu đặt là true, thay đổi quyền tệp đệ quy.
	 *                             Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chmod( $file, $mode = false, $recursive = false ) {
		return false;
	}

	/**
	 * Gets the file owner.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to the file.
	 * @return string|false Username of the owner on success, false on failure.
	 */
	public function owner( $file ) {
		return false;
	}

	/**
	 * Gets the file's group.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to the file.
	 * @return string|false The group on success, false on failure.
	 */
	public function group( $file ) {
		return false;
	}

	/**
	 * Copies a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $source      Path to the source file.
	 * @param string    $destination Path to the destination file.
	 * @param bool      $overwrite   Optional. Whether to overwrite the destination file if it exists.
	 *                               Default false.
	 * @param int|false $mode        Optional. The permissions as octal number, usually 0644 for files,
	 *                               0755 for dirs. Default false.
	 * @return bool True on success, false on failure.
	 */
	public function copy( $source, $destination, $overwrite = false, $mode = false ) {
		return false;
	}

	/**
	 * Moves a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $source      Path to the source file.
	 * @param string $destination Path to the destination file.
	 * @param bool   $overwrite   Optional. Whether to overwrite the destination file if it exists.
	 *                            Default false.
	 * @return bool True on success, false on failure.
	 */
	public function move( $source, $destination, $overwrite = false ) {
		return false;
	}

	/**
	 * Deletes a file or directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string       $file      Path to the file or directory.
	 * @param bool         $recursive Optional. If set to true, deletes files and folders recursively.
	 *                                Default false.
	 * @param string|false $type      Type of resource. 'f' for file, 'd' for directory.
	 *                                Default false.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $file, $recursive = false, $type = false ) {
		return false;
	}

	/**
	 * Checks if a file or directory exists.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Path to file or directory.
	 * @return bool Whether $path exists or not.
	 */
	public function exists( $path ) {
		return false;
	}

	/**
	 * Checks if resource is a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file File path.
	 * @return bool Whether $file is a file.
	 */
	public function is_file( $file ) {
		return false;
	}

	/**
	 * Checks if resource is a directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Directory path.
	 * @return bool Whether $path is a directory.
	 */
	public function is_dir( $path ) {
		return false;
	}

	/**
	 * Checks if a file is readable.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return bool Whether $file is readable.
	 */
	public function is_readable( $file ) {
		return false;
	}

	/**
	 * Checks if a file or directory is writable.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Path to file or directory.
	 * @return bool Whether $path is writable.
	 */
	public function is_writable( $path ) {
		return false;
	}

	/**
	 * Gets the file's last access time.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return int|false Unix timestamp representing last access time, false on failure.
	 */
	public function atime( $file ) {
		return false;
	}

	/**
	 * Gets the file modification time.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return int|false Unix timestamp representing modification time, false on failure.
	 */
	public function mtime( $file ) {
		return false;
	}

	/**
	 * Gets the file size (in bytes).
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return int|false Size of the file in bytes on success, false on failure.
	 */
	public function size( $file ) {
		return false;
	}

	/**
	 * Sets the access and modification times of a file.
	 *
	 * Note: If $file doesn't exist, it will be created.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file  Path to file.
	 * @param int    $time  Optional. Modified time to set for file.
	 *                      Default 0.
	 * @param int    $atime Optional. Access time to set for file.
	 *                      Default 0.
	 * @return bool True on success, false on failure.
	 */
	public function touch( $file, $time = 0, $atime = 0 ) {
		return false;
	}

	/**
	 * Creates a directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string           $path  Path for new directory.
	 * @param int|false        $chmod Optional. The permissions as octal number (or false to skip chmod).
	 *                                Default false.
	 * @param string|int|false $chown Optional. A user name or number (or false to skip chown).
	 *                                Default false.
	 * @param string|int|false $chgrp Optional. A group name or number (or false to skip chgrp).
	 *                                Default false.
	 * @return bool True on success, false on failure.
	 */
	public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {
		return false;
	}

	/**
	 * Deletes a directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path      Path to directory.
	 * @param bool   $recursive Optional. Whether to recursively remove files/directories.
	 *                          Default false.
	 * @return bool True on success, false on failure.
	 */
	public function rmdir( $path, $recursive = false ) {
		return false;
	}

	/**
	 * Gets details for files in a directory or a specific file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path           Path to directory or file.
	 * @param bool   $include_hidden Optional. Whether to include details of hidden ("." prefixed) files.
	 *                               Default true.
	 * @param bool   $recursive      Optional. Whether to recursively include file details in nested directories.
	 *                               Default false.
	 * @return array|false {
	 *     Array of arrays containing file information. False if unable to list directory contents.
	 *
	 *     @type array ...$0 {
	 *         Array of file information. Note that some elements may not be available on all filesystems.
	 *
	 *         @type string           $name        Name of the file or directory.
	 *         @type string           $perms       *nix representation of permissions.
	 *         @type string           $permsn      Octal representation of permissions.
	 *         @type int|string|false $number      File number. May be a numeric string. False if not available.
	 *         @type string|false     $owner       Owner name or ID, or false if not available.
	 *         @type string|false     $group       File permissions group, or false if not available.
	 *         @type int|string|false $size        Size of file in bytes. May be a numeric string.
	 *                                             False if not available.
	 *         @type int|string|false $lastmodunix Last modified unix timestamp. May be a numeric string.
	 *                                             False if not available.
	 *         @type string|false     $lastmod     Last modified month (3 letters) and day (without leading 0), or
	 *                                             false if not available.
	 *         @type string|false     $time        Last modified time, or false if not available.
	 *         @type string           $type        Type of resource. 'f' for file, 'd' for directory, 'l' for link.
	 *         @type array|false      $files       If a directory and `$recursive` is true, contains another array of
	 *                                             files. False if unable to list directory contents.
	 *     }
	 * }
	 */
	public function dirlist( $path, $include_hidden = true, $recursive = false ) {
		return false;
	}
}
