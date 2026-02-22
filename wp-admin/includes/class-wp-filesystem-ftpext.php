<?php
/**
 * Hệ thống tệp FTP của WordPress.
 *
 * @package WordPress
 * @subpackage Filesystem
 */

/**
 * Lớp hệ thống tệp WordPress triển khai FTP.
 *
 * @since 2.5.0
 *
 * @see WP_Filesystem_Base
 */
class WP_Filesystem_FTPext extends WP_Filesystem_Base {

	/**
	 * @since 2.5.0
	 * @var resource
	 */
	public $link;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 2.5.0
	 *
	 * @param array $opt
	 */
	public function __construct( $opt = '' ) {
		$this->method = 'ftpext';
		$this->errors = new WP_Error();

		// Kiểm tra xem có thể sử dụng các hàm ftp không.
		if ( ! extension_loaded( 'ftp' ) ) {
			$this->errors->add( 'no_ftp_ext', __( 'The ftp PHP extension is not available' ) );
			return;
		}

		// Lớp này sử dụng thời gian chờ trên cơ sở từng kết nối, các lớp khác sử dụng trên cơ sở từng hành động.
		if ( ! defined( 'FS_TIMEOUT' ) ) {
			define( 'FS_TIMEOUT', 4 * MINUTE_IN_SECONDS );
		}

		if ( empty( $opt['port'] ) ) {
			$this->options['port'] = 21;
		} else {
			$this->options['port'] = $opt['port'];
		}

		if ( empty( $opt['hostname'] ) ) {
			$this->errors->add( 'empty_hostname', __( 'FTP hostname is required' ) );
		} else {
			$this->options['hostname'] = $opt['hostname'];
		}

		// Kiểm tra các tùy chọn được cung cấp có hợp lệ không.
		if ( empty( $opt['username'] ) ) {
			$this->errors->add( 'empty_username', __( 'FTP username is required' ) );
		} else {
			$this->options['username'] = $opt['username'];
		}

		if ( empty( $opt['password'] ) ) {
			$this->errors->add( 'empty_password', __( 'FTP password is required' ) );
		} else {
			$this->options['password'] = $opt['password'];
		}

		$this->options['ssl'] = false;

		if ( isset( $opt['connection_type'] ) && 'ftps' === $opt['connection_type'] ) {
			$this->options['ssl'] = true;
		}
	}

	/**
	 * Kết nối hệ thống tệp.
	 *
	 * @since 2.5.0
	 *
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function connect() {
		if ( isset( $this->options['ssl'] ) && $this->options['ssl'] && function_exists( 'ftp_ssl_connect' ) ) {
			$this->link = @ftp_ssl_connect( $this->options['hostname'], $this->options['port'], FS_CONNECT_TIMEOUT );
		} else {
			$this->link = @ftp_connect( $this->options['hostname'], $this->options['port'], FS_CONNECT_TIMEOUT );
		}

		if ( ! $this->link ) {
			$this->errors->add(
				'connect',
				sprintf(
					/* translators: %s: hostname:port */
					__( 'Failed to connect to FTP Server %s' ),
					$this->options['hostname'] . ':' . $this->options['port']
				)
			);

			return false;
		}

		if ( ! @ftp_login( $this->link, $this->options['username'], $this->options['password'] ) ) {
			$this->errors->add(
				'auth',
				sprintf(
					/* translators: %s: Username. */
					__( 'Username/Password incorrect for %s' ),
					$this->options['username']
				)
			);

			return false;
		}

		// Đặt kết nối sử dụng FTP thụ động.
		ftp_pasv( $this->link, true );

		if ( @ftp_get_option( $this->link, FTP_TIMEOUT_SEC ) < FS_TIMEOUT ) {
			@ftp_set_option( $this->link, FTP_TIMEOUT_SEC, FS_TIMEOUT );
		}

		return true;
	}

	/**
	 * Đọc toàn bộ tệp vào một chuỗi.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Tên tệp cần đọc.
	 * @return string|false Dữ liệu đọc được khi thành công, false nếu không thể mở tệp tạm,
	 *                      hoặc nếu không thể lấy tệp.
	 */
	public function get_contents( $file ) {
		$tempfile   = wp_tempnam( $file );
		$temphandle = fopen( $tempfile, 'w+' );

		if ( ! $temphandle ) {
			unlink( $tempfile );
			return false;
		}

		if ( ! ftp_fget( $this->link, $temphandle, $file, FTP_BINARY ) ) {
			fclose( $temphandle );
			unlink( $tempfile );
			return false;
		}

		fseek( $temphandle, 0 ); // Quay lại đầu tệp đang được ghi.
		$contents = '';

		while ( ! feof( $temphandle ) ) {
			$contents .= fread( $temphandle, 8 * KB_IN_BYTES );
		}

		fclose( $temphandle );
		unlink( $tempfile );

		return $contents;
	}

	/**
	 * Đọc toàn bộ tệp vào một mảng.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return array|false Nội dung tệp trong một mảng khi thành công, false khi thất bại.
	 */
	public function get_contents_array( $file ) {
		return explode( "\n", $this->get_contents( $file ) );
	}

	/**
	 * Ghi một chuỗi vào tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string    $file     Đường dẫn từ xa đến tệp cần ghi dữ liệu.
	 * @param string    $contents Dữ liệu cần ghi.
	 * @param int|false $mode     Tùy chọn. Quyền tệp dạng số bát phân, thường là 0644.
	 *                            Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function put_contents( $file, $contents, $mode = false ) {
		$tempfile   = wp_tempnam( $file );
		$temphandle = fopen( $tempfile, 'wb+' );

		if ( ! $temphandle ) {
			unlink( $tempfile );
			return false;
		}

		mbstring_binary_safe_encoding();

		$data_length   = strlen( $contents );
		$bytes_written = fwrite( $temphandle, $contents );

		reset_mbstring_encoding();

		if ( $data_length !== $bytes_written ) {
			fclose( $temphandle );
			unlink( $tempfile );
			return false;
		}

		fseek( $temphandle, 0 ); // Quay lại đầu tệp đang được ghi.

		$ret = ftp_fput( $this->link, $file, $temphandle, FTP_BINARY );

		fclose( $temphandle );
		unlink( $tempfile );

		$this->chmod( $file, $mode );

		return $ret;
	}

	/**
	 * Lấy thư mục làm việc hiện tại.
	 *
	 * @since 2.5.0
	 *
	 * @return string|false Thư mục làm việc hiện tại khi thành công, false khi thất bại.
	 */
	public function cwd() {
		$cwd = ftp_pwd( $this->link );

		if ( $cwd ) {
			$cwd = trailingslashit( $cwd );
		}

		return $cwd;
	}

	/**
	 * Thay đổi thư mục hiện tại.
	 *
	 * @since 2.5.0
	 *
	 * @param string $dir Thư mục hiện tại mới.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chdir( $dir ) {
		return @ftp_chdir( $this->link, $dir );
	}

	/**
	 * Thay đổi quyền hệ thống tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string    $file      Đường dẫn đến tệp.
	 * @param int|false $mode      Tùy chọn. Quyền dạng số bát phân, thường là 0644 cho tệp,
	 *                             0755 cho thư mục. Mặc định false.
	 * @param bool      $recursive Tùy chọn. Nếu đặt true, thay đổi quyền tệp đệ quy.
	 *                             Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chmod( $file, $mode = false, $recursive = false ) {
		if ( ! $mode ) {
			if ( $this->is_file( $file ) ) {
				$mode = FS_CHMOD_FILE;
			} elseif ( $this->is_dir( $file ) ) {
				$mode = FS_CHMOD_DIR;
			} else {
				return false;
			}
		}

		// Chmod các đối tượng con nếu đệ quy.
		if ( $recursive && $this->is_dir( $file ) ) {
			$filelist = $this->dirlist( $file );

			foreach ( (array) $filelist as $filename => $filemeta ) {
				$this->chmod( $file . '/' . $filename, $mode, $recursive );
			}
		}

		// Chmod tệp hoặc thư mục.
		if ( ! function_exists( 'ftp_chmod' ) ) {
			return (bool) ftp_site( $this->link, sprintf( 'CHMOD %o %s', $mode, $file ) );
		}

		return (bool) ftp_chmod( $this->link, $mode, $file );
	}

	/**
	 * Lấy chủ sở hữu tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string|false Tên người dùng chủ sở hữu khi thành công, false khi thất bại.
	 */
	public function owner( $file ) {
		$dir = $this->dirlist( $file );

		return $dir[ $file ]['owner'];
	}

	/**
	 * Lấy quyền của tệp hoặc đường dẫn tệp được chỉ định dưới dạng bát phân.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string Chế độ của tệp (3 chữ số cuối).
	 */
	public function getchmod( $file ) {
		$dir = $this->dirlist( $file );

		return $dir[ $file ]['permsn'];
	}

	/**
	 * Lấy nhóm của tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string|false Nhóm khi thành công, false khi thất bại.
	 */
	public function group( $file ) {
		$dir = $this->dirlist( $file );

		return $dir[ $file ]['group'];
	}

	/**
	 * Sao chép một tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string    $source      Đường dẫn đến tệp nguồn.
	 * @param string    $destination Đường dẫn đến tệp đích.
	 * @param bool      $overwrite   Tùy chọn. Có ghi đè tệp đích nếu đã tồn tại hay không.
	 *                               Mặc định false.
	 * @param int|false $mode        Tùy chọn. Quyền dạng số bát phân, thường là 0644 cho tệp,
	 *                               0755 cho thư mục. Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function copy( $source, $destination, $overwrite = false, $mode = false ) {
		if ( ! $overwrite && $this->exists( $destination ) ) {
			return false;
		}

		$content = $this->get_contents( $source );

		if ( false === $content ) {
			return false;
		}

		return $this->put_contents( $destination, $content, $mode );
	}

	/**
	 * Di chuyển một tệp hoặc thư mục.
	 *
	 * Sau khi di chuyển tệp hoặc thư mục, OPcache sẽ cần được vô hiệu hóa.
	 *
	 * Nếu việc di chuyển thư mục thất bại, `copy_dir()` có thể được sử dụng để sao chép đệ quy.
	 *
	 * Sử dụng `move_dir()` để di chuyển thư mục với vô hiệu hóa OPcache và
	 * dự phòng sang `copy_dir()`.
	 *
	 * @since 2.5.0
	 *
	 * @param string $source      Đường dẫn đến tệp hoặc thư mục nguồn.
	 * @param string $destination Đường dẫn đến tệp hoặc thư mục đích.
	 * @param bool   $overwrite   Tùy chọn. Có ghi đè đích nếu đã tồn tại hay không.
	 *                            Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function move( $source, $destination, $overwrite = false ) {
		return ftp_rename( $this->link, $source, $destination );
	}

	/**
	 * Xóa một tệp hoặc thư mục.
	 *
	 * @since 2.5.0
	 *
	 * @param string       $file      Đường dẫn đến tệp hoặc thư mục.
	 * @param bool         $recursive Tùy chọn. Nếu đặt true, xóa tệp và thư mục đệ quy.
	 *                                Mặc định false.
	 * @param string|false $type      Loại tài nguyên. 'f' cho tệp, 'd' cho thư mục.
	 *                                Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function delete( $file, $recursive = false, $type = false ) {
		if ( empty( $file ) ) {
			return false;
		}

		if ( 'f' === $type || $this->is_file( $file ) ) {
			return ftp_delete( $this->link, $file );
		}

		if ( ! $recursive ) {
			return ftp_rmdir( $this->link, $file );
		}

		$filelist = $this->dirlist( trailingslashit( $file ) );

		if ( ! empty( $filelist ) ) {
			foreach ( $filelist as $delete_file ) {
				$this->delete( trailingslashit( $file ) . $delete_file['name'], $recursive, $delete_file['type'] );
			}
		}

		return ftp_rmdir( $this->link, $file );
	}

	/**
	 * Kiểm tra xem tệp hoặc thư mục có tồn tại không.
	 *
	 * @since 2.5.0
	 * @since 6.3.0 Trả về false cho đường dẫn trống.
	 *
	 * @param string $path Đường dẫn đến tệp hoặc thư mục.
	 * @return bool $path có tồn tại hay không.
	 */
	public function exists( $path ) {
		/*
		 * Kiểm tra đường dẫn trống. Nếu ftp_nlist() nhận đường dẫn trống,
		 * nó sẽ kiểm tra thư mục làm việc hiện tại và có thể trả về true.
		 *
		 * Xem https://core.trac.wordpress.org/ticket/33058.
		 */
		if ( '' === $path ) {
			return false;
		}

		$list = ftp_nlist( $this->link, $path );

		if ( empty( $list ) && $this->is_dir( $path ) ) {
			return true; // Tệp là một thư mục trống.
		}

		return ! empty( $list ); // Danh sách trống = không có tệp, nên đảo ngược.
	}

	/**
	 * Kiểm tra xem tài nguyên có phải là tệp không.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn tệp.
	 * @return bool $file có phải là tệp hay không.
	 */
	public function is_file( $file ) {
		return $this->exists( $file ) && ! $this->is_dir( $file );
	}

	/**
	 * Kiểm tra xem tài nguyên có phải là thư mục không.
	 *
	 * @since 2.5.0
	 *
	 * @param string $path Đường dẫn thư mục.
	 * @return bool $path có phải là thư mục hay không.
	 */
	public function is_dir( $path ) {
		$cwd    = $this->cwd();
		$result = @ftp_chdir( $this->link, trailingslashit( $path ) );

		if ( $result && $path === $this->cwd() || $this->cwd() !== $cwd ) {
			@ftp_chdir( $this->link, $cwd );
			return true;
		}

		return false;
	}

	/**
	 * Kiểm tra xem tệp có đọc được không.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return bool $file có đọc được hay không.
	 */
	public function is_readable( $file ) {
		return true;
	}

	/**
	 * Kiểm tra xem tệp hoặc thư mục có ghi được không.
	 *
	 * @since 2.5.0
	 *
	 * @param string $path Đường dẫn đến tệp hoặc thư mục.
	 * @return bool $path có ghi được hay không.
	 */
	public function is_writable( $path ) {
		return true;
	}

	/**
	 * Lấy thời gian truy cập cuối cùng của tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return int|false Dấu thời gian Unix biểu thị thời gian truy cập cuối, false khi thất bại.
	 */
	public function atime( $file ) {
		return false;
	}

	/**
	 * Lấy thời gian sửa đổi tệp.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return int|false Dấu thời gian Unix biểu thị thời gian sửa đổi, false khi thất bại.
	 */
	public function mtime( $file ) {
		return ftp_mdtm( $this->link, $file );
	}

	/**
	 * Lấy kích thước tệp (tính bằng byte).
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return int|false Kích thước tệp tính bằng byte khi thành công, false khi thất bại.
	 */
	public function size( $file ) {
		$size = ftp_size( $this->link, $file );

		return ( $size > -1 ) ? $size : false;
	}

	/**
	 * Đặt thời gian truy cập và sửa đổi của tệp.
	 *
	 * Lưu ý: Nếu $file không tồn tại, nó sẽ được tạo.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file  Đường dẫn đến tệp.
	 * @param int    $time  Tùy chọn. Thời gian sửa đổi để đặt cho tệp.
	 *                      Mặc định 0.
	 * @param int    $atime Tùy chọn. Thời gian truy cập để đặt cho tệp.
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
	 *
	 * @param string           $path  Đường dẫn cho thư mục mới.
	 * @param int|false        $chmod Tùy chọn. Quyền dạng số bát phân (hoặc false để bỏ qua chmod).
	 *                                Mặc định false.
	 * @param string|int|false $chown Tùy chọn. Tên hoặc số người dùng (hoặc false để bỏ qua chown).
	 *                                Mặc định false.
	 * @param string|int|false $chgrp Tùy chọn. Tên hoặc số nhóm (hoặc false để bỏ qua chgrp).
	 *                                Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {
		$path = untrailingslashit( $path );

		if ( empty( $path ) ) {
			return false;
		}

		if ( ! ftp_mkdir( $this->link, $path ) ) {
			return false;
		}

		$this->chmod( $path, $chmod );

		return true;
	}

	/**
	 * Xóa một thư mục.
	 *
	 * @since 2.5.0
	 *
	 * @param string $path      Đường dẫn đến thư mục.
	 * @param bool   $recursive Tùy chọn. Có xóa đệ quy tệp/thư mục hay không.
	 *                          Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function rmdir( $path, $recursive = false ) {
		return $this->delete( $path, $recursive );
	}

	/**
	 * @param string $line
	 * @return array {
	 *     Mảng thông tin tệp.
	 *
	 *     @type string       $name        Tên tệp hoặc thư mục.
	 *     @type string       $perms       Biểu diễn quyền dạng *nix.
	 *     @type string       $permsn      Biểu diễn quyền dạng bát phân.
	 *     @type string|false $number      Số tệp dạng chuỗi, hoặc false nếu không khả dụng.
	 *     @type string|false $owner       Tên hoặc ID chủ sở hữu, hoặc false nếu không khả dụng.
	 *     @type string|false $group       Nhóm quyền tệp, hoặc false nếu không khả dụng.
	 *     @type string|false $size        Kích thước tệp tính bằng byte dạng chuỗi, hoặc false nếu không khả dụng.
	 *     @type string|false $lastmodunix Dấu thời gian Unix sửa đổi cuối dạng chuỗi, hoặc false nếu không khả dụng.
	 *     @type string|false $lastmod     Tháng sửa đổi cuối (3 ký tự) và ngày (không có số 0 đầu), hoặc
	 *                                     false nếu không khả dụng.
	 *     @type string|false $time        Thời gian sửa đổi cuối, hoặc false nếu không khả dụng.
	 *     @type string       $type        Loại tài nguyên. 'f' cho tệp, 'd' cho thư mục, 'l' cho liên kết.
	 *     @type array|false  $files       Nếu là thư mục và `$recursive` là true, chứa một mảng tệp khác.
	 *                                     False nếu không thể liệt kê nội dung thư mục.
	 * }
	 */
	public function parselisting( $line ) {
		static $is_windows = null;

		if ( is_null( $is_windows ) ) {
			$is_windows = stripos( ftp_systype( $this->link ), 'win' ) !== false;
		}

		if ( $is_windows && preg_match( '/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)/', $line, $lucifer ) ) {
			$b = array();

			if ( $lucifer[3] < 70 ) {
				$lucifer[3] += 2000;
			} else {
				$lucifer[3] += 1900; // Sửa lỗi năm 4 chữ số.
			}

			$b['isdir'] = ( '<DIR>' === $lucifer[7] );

			if ( $b['isdir'] ) {
				$b['type'] = 'd';
			} else {
				$b['type'] = 'f';
			}

			$b['size']   = $lucifer[7];
			$b['month']  = $lucifer[1];
			$b['day']    = $lucifer[2];
			$b['year']   = $lucifer[3];
			$b['hour']   = $lucifer[4];
			$b['minute'] = $lucifer[5];
			$b['time']   = mktime( $lucifer[4] + ( strcasecmp( $lucifer[6], 'PM' ) === 0 ? 12 : 0 ), $lucifer[5], 0, $lucifer[1], $lucifer[2], $lucifer[3] );
			$b['am/pm']  = $lucifer[6];
			$b['name']   = $lucifer[8];
		} elseif ( ! $is_windows ) {
			$lucifer = preg_split( '/[ ]/', $line, 9, PREG_SPLIT_NO_EMPTY );

			if ( $lucifer ) {
				// echo $line."\n";
				$lcount = count( $lucifer );

				if ( $lcount < 8 ) {
					return '';
				}

				$b           = array();
				$b['isdir']  = 'd' === $lucifer[0][0];
				$b['islink'] = 'l' === $lucifer[0][0];

				if ( $b['isdir'] ) {
					$b['type'] = 'd';
				} elseif ( $b['islink'] ) {
					$b['type'] = 'l';
				} else {
					$b['type'] = 'f';
				}

				$b['perms']  = $lucifer[0];
				$b['permsn'] = $this->getnumchmodfromh( $b['perms'] );
				$b['number'] = $lucifer[1];
				$b['owner']  = $lucifer[2];
				$b['group']  = $lucifer[3];
				$b['size']   = $lucifer[4];

				if ( 8 === $lcount ) {
					sscanf( $lucifer[5], '%d-%d-%d', $b['year'], $b['month'], $b['day'] );
					sscanf( $lucifer[6], '%d:%d', $b['hour'], $b['minute'] );

					$b['time'] = mktime( $b['hour'], $b['minute'], 0, $b['month'], $b['day'], $b['year'] );
					$b['name'] = $lucifer[7];
				} else {
					$b['month'] = $lucifer[5];
					$b['day']   = $lucifer[6];

					if ( preg_match( '/([0-9]{2}):([0-9]{2})/', $lucifer[7], $l2 ) ) {
						$b['year']   = gmdate( 'Y' );
						$b['hour']   = $l2[1];
						$b['minute'] = $l2[2];
					} else {
						$b['year']   = $lucifer[7];
						$b['hour']   = 0;
						$b['minute'] = 0;
					}

					$b['time'] = strtotime( sprintf( '%d %s %d %02d:%02d', $b['day'], $b['month'], $b['year'], $b['hour'], $b['minute'] ) );
					$b['name'] = $lucifer[8];
				}
			}
		}

		// Thay thế liên kết tượng trưng có định dạng "nguồn -> đích" bằng chỉ tên nguồn.
		if ( isset( $b['islink'] ) && $b['islink'] ) {
			$b['name'] = preg_replace( '/(\s*->\s*.*)$/', '', $b['name'] );
		}

		return $b;
	}

	/**
	 * Lấy chi tiết các tệp trong thư mục hoặc một tệp cụ thể.
	 *
	 * @since 2.5.0
	 *
	 * @param string $path           Đường dẫn đến thư mục hoặc tệp.
	 * @param bool   $include_hidden Tùy chọn. Có bao gồm chi tiết tệp ẩn (tiền tố ".") hay không.
	 *                               Mặc định true.
	 * @param bool   $recursive      Tùy chọn. Có bao gồm đệ quy chi tiết tệp trong thư mục lồng nhau hay không.
	 *                               Mặc định false.
	 * @return array|false {
	 *     Mảng các mảng chứa thông tin tệp. False nếu không thể liệt kê nội dung thư mục.
	 *
	 *     @type array ...$0 {
	 *         Mảng thông tin tệp. Lưu ý rằng một số phần tử có thể không khả dụng trên tất cả hệ thống tệp.
	 *
	 *         @type string           $name        Tên tệp hoặc thư mục.
	 *         @type string           $perms       Biểu diễn quyền dạng *nix.
	 *         @type string           $permsn      Biểu diễn quyền dạng bát phân.
	 *         @type int|string|false $number      Số tệp. Có thể là chuỗi số. False nếu không khả dụng.
	 *         @type string|false     $owner       Tên hoặc ID chủ sở hữu, hoặc false nếu không khả dụng.
	 *         @type string|false     $group       Nhóm quyền tệp, hoặc false nếu không khả dụng.
	 *         @type int|string|false $size        Kích thước tệp tính bằng byte. Có thể là chuỗi số.
	 *                                             False nếu không khả dụng.
	 *         @type int|string|false $lastmodunix Dấu thời gian Unix sửa đổi cuối. Có thể là chuỗi số.
	 *                                             False nếu không khả dụng.
	 *         @type string|false     $lastmod     Tháng sửa đổi cuối (3 ký tự) và ngày (không có số 0 đầu), hoặc
	 *                                             false nếu không khả dụng.
	 *         @type string|false     $time        Thời gian sửa đổi cuối, hoặc false nếu không khả dụng.
	 *         @type string           $type        Loại tài nguyên. 'f' cho tệp, 'd' cho thư mục, 'l' cho liên kết.
	 *         @type array|false      $files       Nếu là thư mục và `$recursive` là true, chứa một mảng tệp khác.
	 *                                             False nếu không thể liệt kê nội dung thư mục.
	 *     }
	 * }
	 */
	public function dirlist( $path = '.', $include_hidden = true, $recursive = false ) {
		if ( $this->is_file( $path ) ) {
			$limit_file = basename( $path );
			$path       = dirname( $path ) . '/';
		} else {
			$limit_file = false;
		}

		$pwd = ftp_pwd( $this->link );

		if ( ! @ftp_chdir( $this->link, $path ) ) { // Không thể chuyển sang thư mục = thư mục không tồn tại.
			return false;
		}

		$list = ftp_rawlist( $this->link, '-a', false );

		@ftp_chdir( $this->link, $pwd );

		if ( empty( $list ) ) { // Mảng trống = thư mục không tồn tại (thư mục thật sẽ hiển thị ít nhất dấu chấm).
			return false;
		}

		$dirlist = array();

		foreach ( $list as $k => $v ) {
			$entry = $this->parselisting( $v );

			if ( empty( $entry ) ) {
				continue;
			}

			if ( '.' === $entry['name'] || '..' === $entry['name'] ) {
				continue;
			}

			if ( ! $include_hidden && '.' === $entry['name'][0] ) {
				continue;
			}

			if ( $limit_file && $entry['name'] !== $limit_file ) {
				continue;
			}

			$dirlist[ $entry['name'] ] = $entry;
		}

		$path = trailingslashit( $path );
		$ret  = array();

		foreach ( (array) $dirlist as $struc ) {
			if ( 'd' === $struc['type'] ) {
				if ( $recursive ) {
					$struc['files'] = $this->dirlist( $path . $struc['name'], $include_hidden, $recursive );
				} else {
					$struc['files'] = array();
				}
			}

			$ret[ $struc['name'] ] = $struc;
		}

		return $ret;
	}

	/**
	 * Hàm hủy.
	 *
	 * @since 2.5.0
	 */
	public function __destruct() {
		if ( $this->link ) {
			ftp_close( $this->link );
		}
	}
}
