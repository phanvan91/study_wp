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
	 * Lấy chủ sở hữu tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string|false Tên người dùng của chủ sở hữu khi thành công, false khi thất bại.
	 */
	public function owner( $file ) {
		return false;
	}

	/**
	 * Lấy nhóm của tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string|false Nhóm khi thành công, false khi thất bại.
	 */
	public function group( $file ) {
		return false;
	}

	/**
	 * Sao chép một tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $source      Đường dẫn đến tệp nguồn.
	 * @param string    $destination Đường dẫn đến tệp đích.
	 * @param bool      $overwrite   Tùy chọn. Có ghi đè tệp đích nếu nó tồn tại hay không.
	 *                               Mặc định false.
	 * @param int|false $mode        Tùy chọn. Quyền dạng số bát phân, thường là 0644 cho tệp,
	 *                               0755 cho thư mục. Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function copy( $source, $destination, $overwrite = false, $mode = false ) {
		return false;
	}

	/**
	 * Di chuyển một tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $source      Đường dẫn đến tệp nguồn.
	 * @param string $destination Đường dẫn đến tệp đích.
	 * @param bool   $overwrite   Tùy chọn. Có ghi đè tệp đích nếu nó tồn tại hay không.
	 *                            Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function move( $source, $destination, $overwrite = false ) {
		return false;
	}

	/**
	 * Xóa một tệp hoặc thư mục.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string       $file      Đường dẫn đến tệp hoặc thư mục.
	 * @param bool         $recursive Tùy chọn. Nếu đặt là true, xóa tệp và thư mục đệ quy.
	 *                                Mặc định false.
	 * @param string|false $type      Loại tài nguyên. 'f' cho tệp, 'd' cho thư mục.
	 *                                Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function delete( $file, $recursive = false, $type = false ) {
		return false;
	}

	/**
	 * Kiểm tra xem tệp hoặc thư mục có tồn tại hay không.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Đường dẫn đến tệp hoặc thư mục.
	 * @return bool Liệu $path có tồn tại hay không.
	 */
	public function exists( $path ) {
		return false;
	}

	/**
	 * Kiểm tra xem tài nguyên có phải là tệp hay không.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn tệp.
	 * @return bool Liệu $file có phải là tệp hay không.
	 */
	public function is_file( $file ) {
		return false;
	}

	/**
	 * Kiểm tra xem tài nguyên có phải là thư mục hay không.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Đường dẫn thư mục.
	 * @return bool Liệu $path có phải là thư mục hay không.
	 */
	public function is_dir( $path ) {
		return false;
	}

	/**
	 * Kiểm tra xem tệp có thể đọc được hay không.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return bool Liệu $file có thể đọc được hay không.
	 */
	public function is_readable( $file ) {
		return false;
	}

	/**
	 * Kiểm tra xem tệp hoặc thư mục có thể ghi được hay không.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Đường dẫn đến tệp hoặc thư mục.
	 * @return bool Liệu $path có thể ghi được hay không.
	 */
	public function is_writable( $path ) {
		return false;
	}

	/**
	 * Lấy thời gian truy cập cuối cùng của tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return int|false Dấu thời gian Unix đại diện cho thời gian truy cập cuối cùng, false khi thất bại.
	 */
	public function atime( $file ) {
		return false;
	}

	/**
	 * Lấy thời gian chỉnh sửa tệp.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return int|false Dấu thời gian Unix đại diện cho thời gian chỉnh sửa, false khi thất bại.
	 */
	public function mtime( $file ) {
		return false;
	}

	/**
	 * Lấy kích thước tệp (tính bằng byte).
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return int|false Kích thước tệp tính bằng byte khi thành công, false khi thất bại.
	 */
	public function size( $file ) {
		return false;
	}

	/**
	 * Đặt thời gian truy cập và chỉnh sửa của tệp.
	 *
	 * Lưu ý: Nếu $file không tồn tại, nó sẽ được tạo.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file  Đường dẫn đến tệp.
	 * @param int    $time  Tùy chọn. Thời gian chỉnh sửa cần đặt cho tệp.
	 *                      Mặc định 0.
	 * @param int    $atime Tùy chọn. Thời gian truy cập cần đặt cho tệp.
	 *                      Mặc định 0.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function touch( $file, $time = 0, $atime = 0 ) {
		return false;
	}

	/**
	 * Tạo một thư mục.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string           $path  Đường dẫn cho thư mục mới.
	 * @param int|false        $chmod Tùy chọn. Quyền dạng số bát phân (hoặc false để bỏ qua chmod).
	 *                                Mặc định false.
	 * @param string|int|false $chown Tùy chọn. Tên người dùng hoặc số (hoặc false để bỏ qua chown).
	 *                                Mặc định false.
	 * @param string|int|false $chgrp Tùy chọn. Tên nhóm hoặc số (hoặc false để bỏ qua chgrp).
	 *                                Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {
		return false;
	}

	/**
	 * Xóa một thư mục.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path      Đường dẫn đến thư mục.
	 * @param bool   $recursive Tùy chọn. Có xóa đệ quy tệp/thư mục hay không.
	 *                          Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function rmdir( $path, $recursive = false ) {
		return false;
	}

	/**
	 * Lấy chi tiết cho các tệp trong thư mục hoặc một tệp cụ thể.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path           Đường dẫn đến thư mục hoặc tệp.
	 * @param bool   $include_hidden Tùy chọn. Có bao gồm chi tiết của các tệp ẩn (có tiền tố ".") hay không.
	 *                               Mặc định true.
	 * @param bool   $recursive      Tùy chọn. Có bao gồm đệ quy chi tiết tệp trong các thư mục lồng nhau hay không.
	 *                               Mặc định false.
	 * @return array|false {
	 *     Mảng các mảng chứa thông tin tệp. False nếu không thể liệt kê nội dung thư mục.
	 *
	 *     @type array ...$0 {
	 *         Mảng thông tin tệp. Lưu ý rằng một số phần tử có thể không khả dụng trên tất cả hệ thống tệp.
	 *
	 *         @type string           $name        Tên tệp hoặc thư mục.
	 *         @type string           $perms       Biểu diễn quyền kiểu *nix.
	 *         @type string           $permsn      Biểu diễn quyền dạng bát phân.
	 *         @type int|string|false $number      Số tệp. Có thể là chuỗi số. False nếu không khả dụng.
	 *         @type string|false     $owner       Tên hoặc ID chủ sở hữu, hoặc false nếu không khả dụng.
	 *         @type string|false     $group       Nhóm quyền tệp, hoặc false nếu không khả dụng.
	 *         @type int|string|false $size        Kích thước tệp tính bằng byte. Có thể là chuỗi số.
	 *                                             False nếu không khả dụng.
	 *         @type int|string|false $lastmodunix Dấu thời gian unix chỉnh sửa cuối cùng. Có thể là chuỗi số.
	 *                                             False nếu không khả dụng.
	 *         @type string|false     $lastmod     Tháng chỉnh sửa cuối cùng (3 chữ cái) và ngày (không có số 0 đứng đầu), hoặc
	 *                                             false nếu không khả dụng.
	 *         @type string|false     $time        Thời gian chỉnh sửa cuối cùng, hoặc false nếu không khả dụng.
	 *         @type string           $type        Loại tài nguyên. 'f' cho tệp, 'd' cho thư mục, 'l' cho liên kết.
	 *         @type array|false      $files       Nếu là thư mục và `$recursive` là true, chứa mảng khác của
	 *                                             các tệp. False nếu không thể liệt kê nội dung thư mục.
	 *     }
	 * }
	 */
	public function dirlist( $path, $include_hidden = true, $recursive = false ) {
		return false;
	}
}
