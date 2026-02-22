<?php
/**
 * Lớp Hệ thống tệp WordPress triển khai SSH2
 *
 * Để sử dụng lớp này bạn phải làm theo các bước sau cho PHP 5.2.6+
 *
 * {@link http://kevin.vanzonneveld.net/techblog/article/make_ssh_connections_with_php/ - Ghi chú cài đặt}
 *
 * Biên dịch libssh2 (Lưu ý: Chỉ phiên bản 0.14 chính thức hoạt động với PHP 5.2.6+ hiện tại, nhưng nhiều người dùng thấy các phiên bản mới nhất cũng hoạt động)
 *
 * cd /usr/src
 * wget https://www.libssh2.org/download/libssh2-0.14.tar.gz
 * tar -zxvf libssh2-0.14.tar.gz
 * cd libssh2-0.14/
 * ./configure
 * make all install
 *
 * Lưu ý: Chưa rời khỏi thư mục!
 *
 * Nhập: pecl install -f ssh2
 *
 * Sao chép file ssh.so được tạo vào thư mục Module PHP của bạn.
 * Mở file PHP.INI và tìm nơi đặt các extension.
 * Thêm vào file PHP.ini: extension=ssh2.so
 *
 * Khởi động lại Apache!
 * Kiểm tra phpinfo() streams để xác nhận rằng: ssh2.shell, ssh2.exec, ssh2.tunnel, ssh2.scp, ssh2.sftp tồn tại.
 *
 * Lưu ý: Kể từ WordPress 2.8, lớp này sử dụng hàm PHP5+ `stream_get_contents()`.
 *
 * @since 2.7.0
 *
 * @package WordPress
 * @subpackage Filesystem
 */
class WP_Filesystem_SSH2 extends WP_Filesystem_Base {

	/**
	 * @since 2.7.0
	 * @var resource
	 */
	public $link = false;

	/**
	 * @since 2.7.0
	 * @var resource
	 */
	public $sftp_link;

	/**
	 * @since 2.7.0
	 * @var bool
	 */
	public $keys = false;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 2.7.0
	 *
	 * @param array $opt
	 */
	public function __construct( $opt = '' ) {
		$this->method = 'ssh2';
		$this->errors = new WP_Error();

		// Kiểm tra xem có thể sử dụng các hàm ssh2 không.
		if ( ! extension_loaded( 'ssh2' ) ) {
			$this->errors->add( 'no_ssh2_ext', __( 'The ssh2 PHP extension is not available' ) );
			return;
		}

		// Đặt giá trị mặc định:
		if ( empty( $opt['port'] ) ) {
			$this->options['port'] = 22;
		} else {
			$this->options['port'] = $opt['port'];
		}

		if ( empty( $opt['hostname'] ) ) {
			$this->errors->add( 'empty_hostname', __( 'SSH2 hostname is required' ) );
		} else {
			$this->options['hostname'] = $opt['hostname'];
		}

		// Kiểm tra các tùy chọn được cung cấp có hợp lệ không.
		if ( ! empty( $opt['public_key'] ) && ! empty( $opt['private_key'] ) ) {
			$this->options['public_key']  = $opt['public_key'];
			$this->options['private_key'] = $opt['private_key'];

			$this->options['hostkey'] = array( 'hostkey' => 'ssh-rsa,ssh-ed25519' );

			$this->keys = true;
		} elseif ( empty( $opt['username'] ) ) {
			$this->errors->add( 'empty_username', __( 'SSH2 username is required' ) );
		}

		if ( ! empty( $opt['username'] ) ) {
			$this->options['username'] = $opt['username'];
		}

		if ( empty( $opt['password'] ) ) {
			// Mật khẩu có thể để trống nếu chúng ta sử dụng khóa.
			if ( ! $this->keys ) {
				$this->errors->add( 'empty_password', __( 'SSH2 password is required' ) );
			} else {
				$this->options['password'] = null;
			}
		} else {
			$this->options['password'] = $opt['password'];
		}
	}

	/**
	 * Kết nối hệ thống tệp.
	 *
	 * @since 2.7.0
	 *
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function connect() {
		if ( ! $this->keys ) {
			$this->link = @ssh2_connect( $this->options['hostname'], $this->options['port'] );
		} else {
			$this->link = @ssh2_connect( $this->options['hostname'], $this->options['port'], $this->options['hostkey'] );
		}

		if ( ! $this->link ) {
			$this->errors->add(
				'connect',
				sprintf(
					/* translators: %s: hostname:port */
					__( 'Failed to connect to SSH2 Server %s' ),
					$this->options['hostname'] . ':' . $this->options['port']
				)
			);

			return false;
		}

		if ( ! $this->keys ) {
			if ( ! @ssh2_auth_password( $this->link, $this->options['username'], $this->options['password'] ) ) {
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
		} else {
			if ( ! @ssh2_auth_pubkey_file( $this->link, $this->options['username'], $this->options['public_key'], $this->options['private_key'], $this->options['password'] ) ) {
				$this->errors->add(
					'auth',
					sprintf(
						/* translators: %s: Username. */
						__( 'Public and Private keys incorrect for %s' ),
						$this->options['username']
					)
				);

				return false;
			}
		}

		$this->sftp_link = ssh2_sftp( $this->link );

		if ( ! $this->sftp_link ) {
			$this->errors->add(
				'connect',
				sprintf(
					/* translators: %s: hostname:port */
					__( 'Failed to initialize a SFTP subsystem session with the SSH2 Server %s' ),
					$this->options['hostname'] . ':' . $this->options['port']
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Lấy đường dẫn bọc luồng PHP ssh2.sftp để mở cho tệp đã cho.
	 *
	 * Phương thức này cũng xử lý lỗi PHP khi thư mục gốc (/) không thể
	 * được mở bởi các hàm PHP, gây ra lỗi giả. Để khắc phục điều này,
	 * đường dẫn được chuyển đổi thành /./ có ý nghĩa tương đương với /
	 * Xem https://bugs.php.net/bug.php?id=64169 để biết thêm chi tiết.
	 *
	 * @since 4.4.0
	 *
	 * @param string $path Đường dẫn Tệp/Thư mục trên máy chủ từ xa cần trả về.
	 * @return string Đường dẫn được bọc ssh2.sftp:// để sử dụng.
	 */
	public function sftp_path( $path ) {
		if ( '/' === $path ) {
			$path = '/./';
		}

		return 'ssh2.sftp://' . $this->sftp_link . '/' . ltrim( $path, '/' );
	}

	/**
	 * @since 2.7.0
	 *
	 * @param string $command
	 * @param bool   $returnbool
	 * @return bool|string True khi thành công, false khi thất bại. Chuỗi nếu lệnh đã được thực thi, `$returnbool`
	 *                     là false (mặc định), và dữ liệu từ luồng kết quả đã được lấy.
	 */
	public function run_command( $command, $returnbool = false ) {
		if ( ! $this->link ) {
			return false;
		}

		$stream = ssh2_exec( $this->link, $command );

		if ( ! $stream ) {
			$this->errors->add(
				'command',
				sprintf(
					/* translators: %s: Command. */
					__( 'Unable to perform command: %s' ),
					$command
				)
			);
		} else {
			stream_set_blocking( $stream, true );
			stream_set_timeout( $stream, FS_TIMEOUT );
			$data = stream_get_contents( $stream );
			fclose( $stream );

			if ( $returnbool ) {
				return ( false === $data ) ? false : '' !== trim( $data );
			} else {
				return $data;
			}
		}

		return false;
	}

	/**
	 * Đọc toàn bộ tệp vào một chuỗi.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Tên tệp cần đọc.
	 * @return string|false Dữ liệu đọc được khi thành công, false nếu không thể mở tệp tạm thời,
	 *                      hoặc nếu không thể lấy tệp.
	 */
	public function get_contents( $file ) {
		return file_get_contents( $this->sftp_path( $file ) );
	}

	/**
	 * Đọc toàn bộ tệp vào một mảng.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return array|false Nội dung tệp trong một mảng khi thành công, false khi thất bại.
	 */
	public function get_contents_array( $file ) {
		return file( $this->sftp_path( $file ) );
	}

	/**
	 * Ghi một chuỗi vào tệp.
	 *
	 * @since 2.7.0
	 *
	 * @param string    $file     Đường dẫn từ xa đến tệp cần ghi dữ liệu.
	 * @param string    $contents Dữ liệu cần ghi.
	 * @param int|false $mode     Tùy chọn. Quyền tệp dạng số bát phân, thường là 0644.
	 *                            Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function put_contents( $file, $contents, $mode = false ) {
		$ret = file_put_contents( $this->sftp_path( $file ), $contents );

		if ( strlen( $contents ) !== $ret ) {
			return false;
		}

		$this->chmod( $file, $mode );

		return true;
	}

	/**
	 * Lấy thư mục làm việc hiện tại.
	 *
	 * @since 2.7.0
	 *
	 * @return string|false Thư mục làm việc hiện tại khi thành công, false khi thất bại.
	 */
	public function cwd() {
		$cwd = ssh2_sftp_realpath( $this->sftp_link, '.' );

		if ( $cwd ) {
			$cwd = trailingslashit( trim( $cwd ) );
		}

		return $cwd;
	}

	/**
	 * Thay đổi thư mục hiện tại.
	 *
	 * @since 2.7.0
	 *
	 * @param string $dir Thư mục hiện tại mới.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chdir( $dir ) {
		return $this->run_command( 'cd ' . $dir, true );
	}

	/**
	 * Thay đổi nhóm của tệp.
	 *
	 * @since 2.7.0
	 *
	 * @param string     $file      Đường dẫn đến tệp.
	 * @param string|int $group     Tên hoặc số nhóm.
	 * @param bool       $recursive Tùy chọn. Nếu đặt là true, thay đổi nhóm tệp đệ quy.
	 *                              Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chgrp( $file, $group, $recursive = false ) {
		if ( ! $this->exists( $file ) ) {
			return false;
		}

		if ( ! $recursive || ! $this->is_dir( $file ) ) {
			return $this->run_command( sprintf( 'chgrp %s %s', escapeshellarg( $group ), escapeshellarg( $file ) ), true );
		}

		return $this->run_command( sprintf( 'chgrp -R %s %s', escapeshellarg( $group ), escapeshellarg( $file ) ), true );
	}

	/**
	 * Thay đổi quyền hệ thống tệp.
	 *
	 * @since 2.7.0
	 *
	 * @param string    $file      Đường dẫn đến tệp.
	 * @param int|false $mode      Tùy chọn. Quyền dạng số bát phân, thường là 0644 cho tệp,
	 *                             0755 cho thư mục. Mặc định false.
	 * @param bool      $recursive Tùy chọn. Nếu đặt là true, thay đổi quyền tệp đệ quy.
	 *                             Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chmod( $file, $mode = false, $recursive = false ) {
		if ( ! $this->exists( $file ) ) {
			return false;
		}

		if ( ! $mode ) {
			if ( $this->is_file( $file ) ) {
				$mode = FS_CHMOD_FILE;
			} elseif ( $this->is_dir( $file ) ) {
				$mode = FS_CHMOD_DIR;
			} else {
				return false;
			}
		}

		if ( ! $recursive || ! $this->is_dir( $file ) ) {
			return $this->run_command( sprintf( 'chmod %o %s', $mode, escapeshellarg( $file ) ), true );
		}

		return $this->run_command( sprintf( 'chmod -R %o %s', $mode, escapeshellarg( $file ) ), true );
	}

	/**
	 * Thay đổi chủ sở hữu của tệp hoặc thư mục.
	 *
	 * @since 2.7.0
	 *
	 * @param string     $file      Đường dẫn đến tệp hoặc thư mục.
	 * @param string|int $owner     Tên người dùng hoặc số.
	 * @param bool       $recursive Tùy chọn. Nếu đặt là true, thay đổi chủ sở hữu tệp đệ quy.
	 *                              Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function chown( $file, $owner, $recursive = false ) {
		if ( ! $this->exists( $file ) ) {
			return false;
		}

		if ( ! $recursive || ! $this->is_dir( $file ) ) {
			return $this->run_command( sprintf( 'chown %s %s', escapeshellarg( $owner ), escapeshellarg( $file ) ), true );
		}

		return $this->run_command( sprintf( 'chown -R %s %s', escapeshellarg( $owner ), escapeshellarg( $file ) ), true );
	}

	/**
	 * Lấy chủ sở hữu tệp.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string|false Tên người dùng của chủ sở hữu khi thành công, false khi thất bại.
	 */
	public function owner( $file ) {
		$owneruid = @fileowner( $this->sftp_path( $file ) );

		if ( ! $owneruid ) {
			return false;
		}

		if ( ! function_exists( 'posix_getpwuid' ) ) {
			return $owneruid;
		}

		$ownerarray = posix_getpwuid( $owneruid );

		if ( ! $ownerarray ) {
			return false;
		}

		return $ownerarray['name'];
	}

	/**
	 * Lấy quyền của tệp hoặc đường dẫn được chỉ định ở dạng bát phân.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string Chế độ của tệp (3 chữ số cuối).
	 */
	public function getchmod( $file ) {
		return substr( decoct( @fileperms( $this->sftp_path( $file ) ) ), -3 );
	}

	/**
	 * Lấy nhóm của tệp.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Đường dẫn đến tệp.
	 * @return string|false Nhóm khi thành công, false khi thất bại.
	 */
	public function group( $file ) {
		$gid = @filegroup( $this->sftp_path( $file ) );

		if ( ! $gid ) {
			return false;
		}

		if ( ! function_exists( 'posix_getgrgid' ) ) {
			return $gid;
		}

		$grouparray = posix_getgrgid( $gid );

		if ( ! $grouparray ) {
			return false;
		}

		return $grouparray['name'];
	}

	/**
	 * Sao chép một tệp.
	 *
	 * @since 2.7.0
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
	 * Nếu di chuyển thư mục thất bại, `copy_dir()` có thể được sử dụng để sao chép đệ quy.
	 *
	 * Sử dụng `move_dir()` để di chuyển thư mục với vô hiệu hóa OPcache và
	 * phương án dự phòng `copy_dir()`.
	 *
	 * @since 2.7.0
	 *
	 * @param string $source      Đường dẫn đến tệp hoặc thư mục nguồn.
	 * @param string $destination Đường dẫn đến tệp hoặc thư mục đích.
	 * @param bool   $overwrite   Tùy chọn. Có ghi đè đích nếu nó tồn tại hay không.
	 *                            Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function move( $source, $destination, $overwrite = false ) {
		if ( $this->exists( $destination ) ) {
			if ( $overwrite ) {
				// Chúng ta cần xóa đích trước khi có thể đổi tên nguồn.
				$this->delete( $destination, false, 'f' );
			} else {
				// Nếu không ghi đè, việc đổi tên sẽ thất bại, nên trả về sớm.
				return false;
			}
		}

		return ssh2_sftp_rename( $this->sftp_link, $source, $destination );
	}

	/**
	 * Xóa một tệp hoặc thư mục.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $file      Đường dẫn đến tệp hoặc thư mục.
	 * @param bool         $recursive Tùy chọn. Nếu đặt là true, xóa tệp và thư mục đệ quy.
	 *                                Mặc định false.
	 * @param string|false $type      Loại tài nguyên. 'f' cho tệp, 'd' cho thư mục.
	 *                                Mặc định false.
	 * @return bool True khi thành công, false khi thất bại.
	 */
	public function delete( $file, $recursive = false, $type = false ) {
		if ( 'f' === $type || $this->is_file( $file ) ) {
			return ssh2_sftp_unlink( $this->sftp_link, $file );
		}

		if ( ! $recursive ) {
			return ssh2_sftp_rmdir( $this->sftp_link, $file );
		}

		$filelist = $this->dirlist( $file );

		if ( is_array( $filelist ) ) {
			foreach ( $filelist as $filename => $fileinfo ) {
				$this->delete( $file . '/' . $filename, $recursive, $fileinfo['type'] );
			}
		}

		return ssh2_sftp_rmdir( $this->sftp_link, $file );
	}

	/**
	 * Checks if a file or directory exists.
	 *
	 * @since 2.7.0
	 *
	 * @param string $path Path to file or directory.
	 * @return bool Whether $path exists or not.
	 */
	public function exists( $path ) {
		return file_exists( $this->sftp_path( $path ) );
	}

	/**
	 * Checks if resource is a file.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file File path.
	 * @return bool Whether $file is a file.
	 */
	public function is_file( $file ) {
		return is_file( $this->sftp_path( $file ) );
	}

	/**
	 * Checks if resource is a directory.
	 *
	 * @since 2.7.0
	 *
	 * @param string $path Directory path.
	 * @return bool Whether $path is a directory.
	 */
	public function is_dir( $path ) {
		return is_dir( $this->sftp_path( $path ) );
	}

	/**
	 * Checks if a file is readable.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Path to file.
	 * @return bool Whether $file is readable.
	 */
	public function is_readable( $file ) {
		return is_readable( $this->sftp_path( $file ) );
	}

	/**
	 * Checks if a file or directory is writable.
	 *
	 * @since 2.7.0
	 *
	 * @param string $path Path to file or directory.
	 * @return bool Whether $path is writable.
	 */
	public function is_writable( $path ) {
		// PHP will base its writable checks on system_user === file_owner, not ssh_user === file_owner.
		return true;
	}

	/**
	 * Gets the file's last access time.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Path to file.
	 * @return int|false Unix timestamp representing last access time, false on failure.
	 */
	public function atime( $file ) {
		return fileatime( $this->sftp_path( $file ) );
	}

	/**
	 * Gets the file modification time.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Path to file.
	 * @return int|false Unix timestamp representing modification time, false on failure.
	 */
	public function mtime( $file ) {
		return filemtime( $this->sftp_path( $file ) );
	}

	/**
	 * Gets the file size (in bytes).
	 *
	 * @since 2.7.0
	 *
	 * @param string $file Path to file.
	 * @return int|false Size of the file in bytes on success, false on failure.
	 */
	public function size( $file ) {
		return filesize( $this->sftp_path( $file ) );
	}

	/**
	 * Sets the access and modification times of a file.
	 *
	 * Note: Not implemented.
	 *
	 * @since 2.7.0
	 *
	 * @param string $file  Path to file.
	 * @param int    $time  Optional. Modified time to set for file.
	 *                      Default 0.
	 * @param int    $atime Optional. Access time to set for file.
	 *                      Default 0.
	 */
	public function touch( $file, $time = 0, $atime = 0 ) {
		// Not implemented.
	}

	/**
	 * Creates a directory.
	 *
	 * @since 2.7.0
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
		$path = untrailingslashit( $path );

		if ( empty( $path ) ) {
			return false;
		}

		if ( ! $chmod ) {
			$chmod = FS_CHMOD_DIR;
		}

		if ( ! ssh2_sftp_mkdir( $this->sftp_link, $path, $chmod, true ) ) {
			return false;
		}

		// Set directory permissions.
		ssh2_sftp_chmod( $this->sftp_link, $path, $chmod );

		if ( $chown ) {
			$this->chown( $path, $chown );
		}

		if ( $chgrp ) {
			$this->chgrp( $path, $chgrp );
		}

		return true;
	}

	/**
	 * Deletes a directory.
	 *
	 * @since 2.7.0
	 *
	 * @param string $path      Path to directory.
	 * @param bool   $recursive Optional. Whether to recursively remove files/directories.
	 *                          Default false.
	 * @return bool True on success, false on failure.
	 */
	public function rmdir( $path, $recursive = false ) {
		return $this->delete( $path, $recursive );
	}

	/**
	 * Gets details for files in a directory or a specific file.
	 *
	 * @since 2.7.0
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
	 *         @type false            $number      File number. Always false in this context.
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
		if ( $this->is_file( $path ) ) {
			$limit_file = basename( $path );
			$path       = dirname( $path );
		} else {
			$limit_file = false;
		}

		if ( ! $this->is_dir( $path ) || ! $this->is_readable( $path ) ) {
			return false;
		}

		$ret = array();
		$dir = dir( $this->sftp_path( $path ) );

		if ( ! $dir ) {
			return false;
		}

		$path = trailingslashit( $path );

		while ( false !== ( $entry = $dir->read() ) ) {
			$struc         = array();
			$struc['name'] = $entry;

			if ( '.' === $struc['name'] || '..' === $struc['name'] ) {
				continue; // Do not care about these folders.
			}

			if ( ! $include_hidden && '.' === $struc['name'][0] ) {
				continue;
			}

			if ( $limit_file && $struc['name'] !== $limit_file ) {
				continue;
			}

			$struc['perms']       = $this->gethchmod( $path . $entry );
			$struc['permsn']      = $this->getnumchmodfromh( $struc['perms'] );
			$struc['number']      = false;
			$struc['owner']       = $this->owner( $path . $entry );
			$struc['group']       = $this->group( $path . $entry );
			$struc['size']        = $this->size( $path . $entry );
			$struc['lastmodunix'] = $this->mtime( $path . $entry );
			$struc['lastmod']     = gmdate( 'M j', $struc['lastmodunix'] );
			$struc['time']        = gmdate( 'h:i:s', $struc['lastmodunix'] );
			$struc['type']        = $this->is_dir( $path . $entry ) ? 'd' : 'f';

			if ( 'd' === $struc['type'] ) {
				if ( $recursive ) {
					$struc['files'] = $this->dirlist( $path . $struc['name'], $include_hidden, $recursive );
				} else {
					$struc['files'] = array();
				}
			}

			$ret[ $struc['name'] ] = $struc;
		}

		$dir->close();
		unset( $dir );

		return $ret;
	}
}
