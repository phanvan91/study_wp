<?php
/**
 * API Nâng cấp: Lớp Core_Upgrader
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Lớp lõi dùng để cập nhật lõi WordPress.
 *
 * Cho phép WordPress tự nâng cấp kết hợp với
 * tệp wp-admin/includes/update-core.php.
 *
 * Lưu ý: Các hàm và phương thức mới được giới thiệu không thể sử dụng ở đây.
 * Tất cả các hàm phải có sẵn trong phiên bản trước đang được nâng cấp
 * vì tệp này cũng được sử dụng ở đó.
 *
 * @since 2.8.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader.php.
 *
 * @see WP_Upgrader
 */
class Core_Upgrader extends WP_Upgrader {

	/**
	 * Khởi tạo các chuỗi nâng cấp.
	 *
	 * @since 2.8.0
	 */
	public function upgrade_strings() {
		$this->strings['up_to_date'] = __( 'WordPress is at the latest version.' );
		$this->strings['locked']     = __( 'Another update is currently in progress.' );
		$this->strings['no_package'] = __( 'Update package not available.' );
		/* translators: %s: URL gói. */
		$this->strings['downloading_package']   = sprintf( __( 'Downloading update from %s&#8230;' ), '<span class="code pre">%s</span>' );
		$this->strings['unpack_package']        = __( 'Unpacking the update&#8230;' );
		$this->strings['copy_failed']           = __( 'Could not copy files.' );
		$this->strings['copy_failed_space']     = __( 'Could not copy files. You may have run out of disk space.' );
		$this->strings['start_rollback']        = __( 'Attempting to restore the previous version.' );
		$this->strings['rollback_was_required'] = __( 'Due to an error during updating, WordPress has been restored to your previous version.' );
	}

	/**
	 * Nâng cấp lõi WordPress.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem                Lớp con hệ thống tệp WordPress.
	 * @global callable           $_wp_filesystem_direct_method
	 *
	 * @param object $current Đối tượng phản hồi cho biết WordPress có phải phiên bản mới nhất không.
	 * @param array  $args {
	 *     Tùy chọn. Các tham số để nâng cấp lõi WordPress. Mặc định mảng rỗng.
	 *
	 *     @type bool $pre_check_md5    Có kiểm tra checksum tệp trước khi
	 *                                  thực hiện nâng cấp hay không. Mặc định true.
	 *     @type bool $attempt_rollback Có cố khôi phục thay đổi nếu
	 *                                  có vấn đề hay không. Mặc định false.
	 *     @type bool $do_rollback      Có thực hiện "nâng cấp" này như một lần khôi phục hay không.
	 *                                  Mặc định false.
	 * }
	 * @return string|false|WP_Error Phiên bản WordPress mới khi thành công, false hoặc WP_Error khi thất bại.
	 */
	public function upgrade( $current, $args = array() ) {
		global $wp_filesystem;

		require ABSPATH . WPINC . '/version.php'; // $wp_version;

		$start_time = time();

		$defaults    = array(
			'pre_check_md5'                => true,
			'attempt_rollback'             => false,
			'do_rollback'                  => false,
			'allow_relaxed_file_ownership' => false,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		// Có bản cập nhật nào không?
		if ( ! isset( $current->response ) || 'latest' === $current->response ) {
			return new WP_Error( 'up_to_date', $this->strings['up_to_date'] );
		}

		$res = $this->fs_connect( array( ABSPATH, WP_CONTENT_DIR ), $parsed_args['allow_relaxed_file_ownership'] );
		if ( ! $res || is_wp_error( $res ) ) {
			return $res;
		}

		$wp_dir = trailingslashit( $wp_filesystem->abspath() );

		$partial = true;
		if ( $parsed_args['do_rollback'] ) {
			$partial = false;
		} elseif ( $parsed_args['pre_check_md5'] && ! $this->check_files() ) {
			$partial = false;
		}

		/*
		 * Nếu API trả về cập nhật từng phần, sử dụng nó, trừ khi đang
		 * cài đặt lại. Nếu vượt qua số phiên bản new_bundled, thì sử dụng
		 * zip new_bundled. Tuy nhiên nếu hằng số được đặt để bỏ qua các mục đi kèm thì không.
		 * Nếu API trả về zip no_content, sử dụng nó. Cuối cùng, mặc định là zip đầy đủ.
		 */
		if ( $parsed_args['do_rollback'] && $current->packages->rollback ) {
			$to_download = 'rollback';
		} elseif ( $current->packages->partial && 'reinstall' !== $current->response && $wp_version === $current->partial_version && $partial ) {
			$to_download = 'partial';
		} elseif ( $current->packages->new_bundled && version_compare( $wp_version, $current->new_bundled, '<' )
			&& ( ! defined( 'CORE_UPGRADE_SKIP_NEW_BUNDLED' ) || ! CORE_UPGRADE_SKIP_NEW_BUNDLED ) ) {
			$to_download = 'new_bundled';
		} elseif ( $current->packages->no_content ) {
			$to_download = 'no_content';
		} else {
			$to_download = 'full';
		}

		// Khóa để ngăn nhiều cập nhật lõi xảy ra đồng thời.
		$lock = WP_Upgrader::create_lock( 'core_updater', 15 * MINUTE_IN_SECONDS );
		if ( ! $lock ) {
			return new WP_Error( 'locked', $this->strings['locked'] );
		}

		$download = $this->download_package( $current->packages->$to_download, false );

		/*
		 * Cho phép chữ ký lỗi mềm.
		 * CẢNH BÁO: Điều này có thể bị loại bỏ trong tương lai.
		 */
		if ( is_wp_error( $download ) && $download->get_error_data( 'softfail-filename' ) ) {
			// Xuất lỗi thất bại dưới dạng phản hồi bình thường, không phải lỗi:
			/** Bộ lọc này được ghi nhận trong wp-admin/includes/update-core.php */
			apply_filters( 'update_feedback', $download->get_error_message() );

			// Báo cáo lỗi này về WordPress.org cho mục đích gỡ lỗi.
			wp_version_check(
				array(
					'signature_failure_code' => $download->get_error_code(),
					'signature_failure_data' => $download->get_error_data(),
				)
			);

			// Giả vờ lỗi này không xảy ra.
			$download = $download->get_error_data( 'softfail-filename' );
		}

		if ( is_wp_error( $download ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return $download;
		}

		$working_dir = $this->unpack_package( $download );
		if ( is_wp_error( $working_dir ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return $working_dir;
		}

		// Sao chép update-core.php từ phiên bản mới vào vị trí.
		if ( ! $wp_filesystem->copy( $working_dir . '/wordpress/wp-admin/includes/update-core.php', $wp_dir . 'wp-admin/includes/update-core.php', true ) ) {
			$wp_filesystem->delete( $working_dir, true );
			WP_Upgrader::release_lock( 'core_updater' );
			return new WP_Error( 'copy_failed_for_update_core_file', __( 'The update cannot be installed because some files could not be copied. This is usually due to inconsistent file permissions.' ), 'wp-admin/includes/update-core.php' );
		}
		$wp_filesystem->chmod( $wp_dir . 'wp-admin/includes/update-core.php', FS_CHMOD_FILE );

		wp_opcache_invalidate( ABSPATH . 'wp-admin/includes/update-core.php' );
		require_once ABSPATH . 'wp-admin/includes/update-core.php';

		if ( ! function_exists( 'update_core' ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return new WP_Error( 'copy_failed_space', $this->strings['copy_failed_space'] );
		}

		$result = update_core( $working_dir, $wp_dir );

		// Trong trường hợp có vấn đề, chúng ta có thể khôi phục lại.
		if ( $parsed_args['attempt_rollback'] && $current->packages->rollback && ! $parsed_args['do_rollback'] ) {
			$try_rollback = false;
			if ( is_wp_error( $result ) ) {
				$error_code = $result->get_error_code();
				/*
				 * Không phải tất cả các lỗi đều như nhau. Các mã này là nghiêm trọng: copy_failed__copy_dir,
				 * mkdir_failed__copy_dir, copy_failed__copy_dir_retry, và disk_full.
				 * do_rollback cho phép update_core() kích hoạt khôi phục nếu cần.
				 */
				if ( str_contains( $error_code, 'do_rollback' ) ) {
					$try_rollback = true;
				} elseif ( str_contains( $error_code, '__copy_dir' ) ) {
					$try_rollback = true;
				} elseif ( 'disk_full' === $error_code ) {
					$try_rollback = true;
				}
			}

			if ( $try_rollback ) {
				/** Bộ lọc này được ghi nhận trong wp-admin/includes/update-core.php */
				apply_filters( 'update_feedback', $result );

				/** Bộ lọc này được ghi nhận trong wp-admin/includes/update-core.php */
				apply_filters( 'update_feedback', $this->strings['start_rollback'] );

				$rollback_result = $this->upgrade( $current, array_merge( $parsed_args, array( 'do_rollback' => true ) ) );

				$original_result = $result;
				$result          = new WP_Error(
					'rollback_was_required',
					$this->strings['rollback_was_required'],
					(object) array(
						'update'   => $original_result,
						'rollback' => $rollback_result,
					)
				);
			}
		}

		/** Hành động này được ghi nhận trong wp-admin/includes/class-wp-upgrader.php */
		do_action(
			'upgrader_process_complete',
			$this,
			array(
				'action' => 'update',
				'type'   => 'core',
			)
		);

		// Xóa bộ nhớ đệm cập nhật hiện tại.
		delete_site_transient( 'update_core' );

		if ( ! $parsed_args['do_rollback'] ) {
			$stats = array(
				'update_type'      => $current->response,
				'success'          => true,
				'fs_method'        => $wp_filesystem->method,
				'fs_method_forced' => defined( 'FS_METHOD' ) || has_filter( 'filesystem_method' ),
				'fs_method_direct' => ! empty( $GLOBALS['_wp_filesystem_direct_method'] ) ? $GLOBALS['_wp_filesystem_direct_method'] : '',
				'time_taken'       => time() - $start_time,
				'reported'         => $wp_version,
				'attempted'        => $current->version,
			);

			if ( is_wp_error( $result ) ) {
				$stats['success'] = false;
				// Có xảy ra khôi phục không?
				if ( ! empty( $try_rollback ) ) {
					$stats['error_code'] = $original_result->get_error_code();
					$stats['error_data'] = $original_result->get_error_data();
					// Khôi phục có thành công không? Nếu không, thu thập lỗi của nó.
					$stats['rollback'] = ! is_wp_error( $rollback_result );
					if ( is_wp_error( $rollback_result ) ) {
						$stats['rollback_code'] = $rollback_result->get_error_code();
						$stats['rollback_data'] = $rollback_result->get_error_data();
					}
				} else {
					$stats['error_code'] = $result->get_error_code();
					$stats['error_data'] = $result->get_error_data();
				}
			}

			wp_version_check( $stats );
		}

		WP_Upgrader::release_lock( 'core_updater' );

		return $result;
	}

	/**
	 * Xác định xem phiên bản WordPress lõi này có nên cập nhật lên phiên bản được đề xuất hay không.
	 *
	 * @since 3.7.0
	 *
	 * @param string $offered_ver Phiên bản được đề xuất, theo định dạng x.y.z.
	 * @return bool True nếu nên cập nhật lên phiên bản được đề xuất, ngược lại false.
	 */
	public static function should_update_to_version( $offered_ver ) {
		require ABSPATH . WPINC . '/version.php'; // $wp_version; // x.y.z

		$current_branch = implode( '.', array_slice( preg_split( '/[.-]/', $wp_version ), 0, 2 ) ); // x.y
		$new_branch     = implode( '.', array_slice( preg_split( '/[.-]/', $offered_ver ), 0, 2 ) ); // x.y

		$current_is_development_version = (bool) strpos( $wp_version, '-' );

		// Mặc định:
		$upgrade_dev   = get_site_option( 'auto_update_core_dev', 'enabled' ) === 'enabled';
		$upgrade_minor = get_site_option( 'auto_update_core_minor', 'enabled' ) === 'enabled';
		$upgrade_major = get_site_option( 'auto_update_core_major', 'unset' ) === 'enabled';

		// WP_AUTO_UPDATE_CORE = true (tất cả), 'beta', 'rc', 'development', 'branch-development', 'minor', false.
		if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
			if ( false === WP_AUTO_UPDATE_CORE ) {
				// Mặc định là tắt, trừ khi bộ lọc cho phép.
				$upgrade_dev   = false;
				$upgrade_minor = false;
				$upgrade_major = false;
			} elseif ( true === WP_AUTO_UPDATE_CORE
				|| in_array( WP_AUTO_UPDATE_CORE, array( 'beta', 'rc', 'development', 'branch-development' ), true )
			) {
				// TẤT CẢ các cập nhật cho lõi.
				$upgrade_dev   = true;
				$upgrade_minor = true;
				$upgrade_major = true;
			} elseif ( 'minor' === WP_AUTO_UPDATE_CORE ) {
				// Chỉ cập nhật nhỏ cho lõi.
				$upgrade_dev   = false;
				$upgrade_minor = true;
				$upgrade_major = false;
			}
		}

		// 1: Nếu đã ở phiên bản đó, không cần cập nhật.
		if ( $offered_ver === $wp_version ) {
			return false;
		}

		// 2: Nếu đang chạy phiên bản mới hơn, không cập nhật.
		if ( version_compare( $wp_version, $offered_ver, '>' ) ) {
			return false;
		}

		$failure_data = get_site_option( 'auto_core_update_failed' );
		if ( $failure_data ) {
			// Nếu đây là lỗi cập nhật nghiêm trọng, không thể cập nhật.
			if ( ! empty( $failure_data['critical'] ) ) {
				return false;
			}

			// Không thông báo có thể cập nhật trên update-core.php nếu có lỗi không nghiêm trọng đã ghi.
			if ( $wp_version === $failure_data['current'] && str_contains( $offered_ver, '.1.next.minor' ) ) {
				return false;
			}

			/*
			 * Không thể cập nhật nếu đang thử lại cùng cập nhật A sang B đã gây lỗi không nghiêm trọng.
			 * Một số lỗi không nghiêm trọng cho phép thử lại, như download_failed.
			 * 3.7.1 => 3.7.2 dẫn đến files_not_writable, nếu vẫn ở 3.7.1 và vẫn cố cập nhật lên 3.7.2.
			 */
			if ( empty( $failure_data['retry'] ) && $wp_version === $failure_data['current'] && $offered_ver === $failure_data['attempted'] ) {
				return false;
			}
		}

		// 3: 3.7-alpha-25000 -> 3.7-alpha-25678 -> 3.7-beta1 -> 3.7-beta2.
		if ( $current_is_development_version ) {

			/**
			 * Lọc việc có bật cập nhật tự động lõi cho phiên bản phát triển hay không.
			 *
			 * @since 3.7.0
			 *
			 * @param bool $upgrade_dev Có bật cập nhật tự động cho phiên bản phát triển hay không.
			 */
			if ( ! apply_filters( 'allow_dev_auto_core_updates', $upgrade_dev ) ) {
				return false;
			}
			// Nếu không thì tiếp tục sang nhánh cập nhật nhỏ + lớn bên dưới.
		}

		// 4: Cập nhật nhỏ trong nhánh (3.7.0 -> 3.7.1 -> 3.7.2 -> 3.7.4).
		if ( $current_branch === $new_branch ) {

			/**
			 * Lọc việc có bật cập nhật tự động lõi nhỏ hay không.
			 *
			 * @since 3.7.0
			 *
			 * @param bool $upgrade_minor Có bật cập nhật tự động lõi nhỏ hay không.
			 */
			return apply_filters( 'allow_minor_auto_core_updates', $upgrade_minor );
		}

		// 5: Cập nhật phiên bản lớn (3.7.0 -> 3.8.0 -> 3.9.1).
		if ( version_compare( $new_branch, $current_branch, '>' ) ) {

			/**
			 * Lọc việc có bật cập nhật tự động lõi lớn hay không.
			 *
			 * @since 3.7.0
			 *
			 * @param bool $upgrade_major Có bật cập nhật tự động lõi lớn hay không.
			 */
			return apply_filters( 'allow_major_auto_core_updates', $upgrade_major );
		}

		// Nếu không chắc chắn, không cập nhật.
		return false;
	}

	/**
	 * So sánh checksum tệp trên đĩa với checksum mong đợi.
	 *
	 * @since 3.7.0
	 *
	 * @global string $wp_version       Chuỗi phiên bản WordPress.
	 * @global string $wp_local_package Mã ngôn ngữ của gói.
	 *
	 * @return bool True nếu checksum khớp, ngược lại false.
	 */
	public function check_files() {
		global $wp_version, $wp_local_package;

		$checksums = get_core_checksums( $wp_version, isset( $wp_local_package ) ? $wp_local_package : 'en_US' );

		if ( ! is_array( $checksums ) ) {
			return false;
		}

		foreach ( $checksums as $file => $checksum ) {
			// Bỏ qua các tệp được cập nhật.
			if ( str_starts_with( $file, 'wp-content' ) ) {
				continue;
			}
			if ( ! file_exists( ABSPATH . $file ) || md5_file( ABSPATH . $file ) !== $checksum ) {
				return false;
			}
		}

		return true;
	}
}
