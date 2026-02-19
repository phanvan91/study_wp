<?php
/**
 * API Nâng cấp: Lớp Language_Pack_Upgrader
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Lớp lõi dùng để cập nhật/cài đặt gói ngôn ngữ (bản dịch)
 * cho plugin, giao diện và lõi.
 *
 * @since 3.7.0
 * @since 4.6.0 Được chuyển sang file riêng từ wp-admin/includes/class-wp-upgrader.php.
 *
 * @see WP_Upgrader
 */
class Language_Pack_Upgrader extends WP_Upgrader {

	/**
	 * Kết quả của việc nâng cấp gói ngôn ngữ.
	 *
	 * @since 3.7.0
	 * @var array|WP_Error $result
	 * @see WP_Upgrader::$result
	 */
	public $result;

	/**
	 * Có đang thực hiện nâng cấp/cài đặt hàng loạt hay không.
	 *
	 * @since 3.7.0
	 * @var bool $bulk
	 */
	public $bulk = true;

	/**
	 * Nâng cấp gói ngôn ngữ bất đồng bộ sau khi các nâng cấp khác đã hoàn thành.
	 *
	 * Được gắn vào hành động {@see 'upgrader_process_complete'} theo mặc định.
	 *
	 * @since 3.7.0
	 *
	 * @param false|WP_Upgrader $upgrader Tùy chọn. Đối tượng WP_Upgrader hoặc false. Nếu `$upgrader` là
	 *                                    một đối tượng Language_Pack_Upgrader, phương thức sẽ thoát để
	 *                                    tránh đệ quy. Ngoài ra không sử dụng. Mặc định false.
	 */
	public static function async_upgrade( $upgrader = false ) {
		// Tránh đệ quy.
		if ( $upgrader && $upgrader instanceof Language_Pack_Upgrader ) {
			return;
		}

		// Không có gì cần làm?
		$language_updates = wp_get_translation_updates();
		if ( ! $language_updates ) {
			return;
		}

		/*
		 * Tránh can thiệp vào các cài đặt VCS, ít nhất là hiện tại.
		 * Lưu ý: đây không phải là cách lý tưởng để thực hiện điều này.
		 */
		$check_vcs = new WP_Automatic_Updater();
		if ( $check_vcs->is_vcs_checkout( WP_CONTENT_DIR ) ) {
			return;
		}

		foreach ( $language_updates as $key => $language_update ) {
			$update = ! empty( $language_update->autoupdate );

			/**
			 * Lọc việc có cập nhật bản dịch bất đồng bộ cho lõi, plugin hoặc giao diện hay không.
			 *
			 * @since 4.0.0
			 *
			 * @param bool   $update          Có cập nhật hay không.
			 * @param object $language_update Đề nghị cập nhật.
			 */
			$update = apply_filters( 'async_update_translation', $update, $language_update );

			if ( ! $update ) {
				unset( $language_updates[ $key ] );
			}
		}

		if ( empty( $language_updates ) ) {
			return;
		}

		// Tái sử dụng giao diện nâng cấp tự động nếu trình nâng cấp cha đang sử dụng nó.
		if ( $upgrader && $upgrader->skin instanceof Automatic_Upgrader_Skin ) {
			$skin = $upgrader->skin;
		} else {
			$skin = new Language_Pack_Upgrader_Skin(
				array(
					'skip_header_footer' => true,
				)
			);
		}

		$lp_upgrader = new Language_Pack_Upgrader( $skin );
		$lp_upgrader->bulk_upgrade( $language_updates );
	}

	/**
	 * Khởi tạo các chuỗi nâng cấp.
	 *
	 * @since 3.7.0
	 */
	public function upgrade_strings() {
		$this->strings['starting_upgrade'] = __( 'Some of your translations need updating. Sit tight for a few more seconds while they are updated as well.' );
		$this->strings['up_to_date']       = __( 'Your translations are all up to date.' );
		$this->strings['no_package']       = __( 'Update package not available.' );
		/* translators: %s: Package URL. */
		$this->strings['downloading_package'] = sprintf( __( 'Downloading translation from %s&#8230;' ), '<span class="code pre">%s</span>' );
		$this->strings['unpack_package']      = __( 'Unpacking the update&#8230;' );
		$this->strings['process_failed']      = __( 'Translation update failed.' );
		$this->strings['process_success']     = __( 'Translation updated successfully.' );
		$this->strings['remove_old']          = __( 'Removing the old version of the translation&#8230;' );
		$this->strings['remove_old_failed']   = __( 'Could not remove the old translation.' );
	}

	/**
	 * Nâng cấp một gói ngôn ngữ.
	 *
	 * @since 3.7.0
	 *
	 * @param string|false $update Tùy chọn. Có đề nghị cập nhật hay không. Mặc định false.
	 * @param array        $args   Tùy chọn. Các tham số tùy chọn khác, xem
	 *                             Language_Pack_Upgrader::bulk_upgrade(). Mặc định mảng rỗng.
	 * @return array|bool|WP_Error Kết quả nâng cấp, hoặc đối tượng WP_Error.
	 */
	public function upgrade( $update = false, $args = array() ) {
		if ( $update ) {
			$update = array( $update );
		}

		$results = $this->bulk_upgrade( $update, $args );

		if ( ! is_array( $results ) ) {
			return $results;
		}

		return $results[0];
	}

	/**
	 * Nâng cấp nhiều gói ngôn ngữ cùng lúc.
	 *
	 * @since 3.7.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param object[] $language_updates Tùy chọn. Mảng các gói ngôn ngữ cần cập nhật. Xem {@see wp_get_translation_updates()}.
	 *                                   Mặc định mảng rỗng.
	 * @param array    $args {
	 *     Các tham số khác cho nâng cấp nhiều gói ngôn ngữ. Mặc định mảng rỗng.
	 *
	 *     @type bool $clear_update_cache Có xóa bộ nhớ đệm cập nhật khi hoàn thành hay không.
	 *                                    Mặc định true.
	 * }
	 * @return array|bool|WP_Error Sẽ trả về mảng kết quả, hoặc true nếu không có cập nhật,
	 *                             false hoặc WP_Error cho lỗi ban đầu.
	 */
	public function bulk_upgrade( $language_updates = array(), $args = array() ) {
		global $wp_filesystem;

		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		if ( ! $language_updates ) {
			$language_updates = wp_get_translation_updates();
		}

		if ( empty( $language_updates ) ) {
			$this->skin->header();
			$this->skin->set_result( true );
			$this->skin->feedback( 'up_to_date' );
			$this->skin->bulk_footer();
			$this->skin->footer();
			return true;
		}

		if ( 'upgrader_process_complete' === current_filter() ) {
			$this->skin->feedback( 'starting_upgrade' );
		}

		// Gỡ bỏ bất kỳ bộ lọc nâng cấp hiện có nào từ các trình nâng cấp plugin/giao diện #WP29425 & #WP29230.
		remove_all_filters( 'upgrader_pre_install' );
		remove_all_filters( 'upgrader_clear_destination' );
		remove_all_filters( 'upgrader_post_install' );
		remove_all_filters( 'upgrader_source_selection' );

		add_filter( 'upgrader_source_selection', array( $this, 'check_package' ), 10, 2 );

		$this->skin->header();

		// Kết nối tới hệ thống tệp trước.
		$res = $this->fs_connect( array( WP_CONTENT_DIR, WP_LANG_DIR ) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$results = array();

		$this->update_count   = count( $language_updates );
		$this->update_current = 0;

		/*
		 * Hàm mkdir() của hệ thống tệp không đệ quy. Đảm bảo WP_LANG_DIR tồn tại,
		 * vì sau đó chúng ta có thể cần tạo thư mục /plugins hoặc /themes bên trong nó.
		 */
		$remote_destination = $wp_filesystem->find_folder( WP_LANG_DIR );
		if ( ! $wp_filesystem->exists( $remote_destination ) ) {
			if ( ! $wp_filesystem->mkdir( $remote_destination, FS_CHMOD_DIR ) ) {
				return new WP_Error( 'mkdir_failed_lang_dir', $this->strings['mkdir_failed'], $remote_destination );
			}
		}

		$language_updates_results = array();

		foreach ( $language_updates as $language_update ) {

			$this->skin->language_update = $language_update;

			$destination = WP_LANG_DIR;
			if ( 'plugin' === $language_update->type ) {
				$destination .= '/plugins';
			} elseif ( 'theme' === $language_update->type ) {
				$destination .= '/themes';
			}

			++$this->update_current;

			$options = array(
				'package'                     => $language_update->package,
				'destination'                 => $destination,
				'clear_destination'           => true,
				'abort_if_destination_exists' => false, // Chúng ta mong đợi đích đến đã tồn tại.
				'clear_working'               => true,
				'is_multi'                    => true,
				'hook_extra'                  => array(
					'language_update_type' => $language_update->type,
					'language_update'      => $language_update,
				),
			);

			$result = $this->run( $options );

			$results[] = $this->result;

			// Ngăn màn hình xác thực thông tin đăng nhập hiển thị nhiều lần.
			if ( false === $result ) {
				break;
			}

			$language_updates_results[] = array(
				'language' => $language_update->language,
				'type'     => $language_update->type,
				'slug'     => isset( $language_update->slug ) ? $language_update->slug : 'default',
				'version'  => $language_update->version,
			);
		}

		// Gỡ bỏ các hook nâng cấp không cần thiết cho cập nhật bản dịch.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );
		remove_action( 'upgrader_process_complete', 'wp_version_check' );
		remove_action( 'upgrader_process_complete', 'wp_update_plugins' );
		remove_action( 'upgrader_process_complete', 'wp_update_themes' );

		/** Hành động này được ghi nhận trong wp-admin/includes/class-wp-upgrader.php */
		do_action(
			'upgrader_process_complete',
			$this,
			array(
				'action'       => 'update',
				'type'         => 'translation',
				'bulk'         => true,
				'translations' => $language_updates_results,
			)
		);

		// Thêm lại các hook nâng cấp.
		add_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );
		add_action( 'upgrader_process_complete', 'wp_version_check', 10, 0 );
		add_action( 'upgrader_process_complete', 'wp_update_plugins', 10, 0 );
		add_action( 'upgrader_process_complete', 'wp_update_themes', 10, 0 );

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Dọn dẹp các hook, phòng trường hợp có nâng cấp khác trên kết nối này.
		remove_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

		if ( $parsed_args['clear_update_cache'] ) {
			wp_clean_update_cache();
		}

		return $results;
	}

	/**
	 * Kiểm tra nguồn gói có chứa các tệp .mo và .po hay không.
	 *
	 * Được gắn vào bộ lọc {@see 'upgrader_source_selection'} bởi
	 * Language_Pack_Upgrader::bulk_upgrade().
	 *
	 * @since 3.7.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param string|WP_Error $source        Đường dẫn tới nguồn gói đã tải về.
	 * @param string          $remote_source Vị trí nguồn tệp từ xa.
	 * @return string|WP_Error Nguồn như đã truyền, hoặc đối tượng WP_Error khi thất bại.
	 */
	public function check_package( $source, $remote_source ) {
		global $wp_filesystem;

		if ( is_wp_error( $source ) ) {
			return $source;
		}

		// Kiểm tra thư mục có chứa ngôn ngữ hợp lệ.
		$files = $wp_filesystem->dirlist( $remote_source );

		// Kiểm tra xem các tệp mong đợi có tồn tại trong thư mục hay không.
		$po  = false;
		$mo  = false;
		$php = false;
		foreach ( (array) $files as $file => $filedata ) {
			if ( str_ends_with( $file, '.po' ) ) {
				$po = true;
			} elseif ( str_ends_with( $file, '.mo' ) ) {
				$mo = true;
			} elseif ( str_ends_with( $file, '.l10n.php' ) ) {
				$php = true;
			}
		}

		if ( $php ) {
			return $source;
		}

		if ( ! $mo || ! $po ) {
			return new WP_Error(
				'incompatible_archive_pomo',
				$this->strings['incompatible_archive'],
				sprintf(
					/* translators: 1: .po, 2: .mo, 3: .l10n.php */
					__( 'The language pack is missing either the %1$s, %2$s, or %3$s files.' ),
					'<code>.po</code>',
					'<code>.mo</code>',
					'<code>.l10n.php</code>'
				)
			);
		}

		return $source;
	}

	/**
	 * Lấy tên của mục đang được cập nhật.
	 *
	 * @since 3.7.0
	 *
	 * @param object $update Dữ liệu cho một bản cập nhật.
	 * @return string Tên của mục đang được cập nhật.
	 */
	public function get_name_for_update( $update ) {
		switch ( $update->type ) {
			case 'core':
				return 'WordPress'; // Không dịch.

			case 'theme':
				$theme = wp_get_theme( $update->slug );
				if ( $theme->exists() ) {
					return $theme->Get( 'Name' );
				}
				break;
			case 'plugin':
				$plugin_data = get_plugins( '/' . $update->slug );
				$plugin_data = reset( $plugin_data );
				if ( $plugin_data ) {
					return $plugin_data['Name'];
				}
				break;
		}
		return '';
	}

	/**
	 * Xóa các bản dịch hiện có ở nơi mục này sẽ được cài đặt vào.
	 *
	 * @since 5.1.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Lớp con hệ thống tệp WordPress.
	 *
	 * @param string $remote_destination Vị trí trên hệ thống tệp từ xa cần xóa.
	 * @return bool|WP_Error True nếu thành công, WP_Error nếu thất bại.
	 */
	public function clear_destination( $remote_destination ) {
		global $wp_filesystem;

		$language_update    = $this->skin->language_update;
		$language_directory = WP_LANG_DIR . '/'; // Đường dẫn cục bộ để sử dụng với glob().

		if ( 'core' === $language_update->type ) {
			$files = array(
				$remote_destination . $language_update->language . '.po',
				$remote_destination . $language_update->language . '.mo',
				$remote_destination . $language_update->language . '.l10n.php',
				$remote_destination . 'admin-' . $language_update->language . '.po',
				$remote_destination . 'admin-' . $language_update->language . '.mo',
				$remote_destination . 'admin-' . $language_update->language . '.l10n.php',
				$remote_destination . 'admin-network-' . $language_update->language . '.po',
				$remote_destination . 'admin-network-' . $language_update->language . '.mo',
				$remote_destination . 'admin-network-' . $language_update->language . '.l10n.php',
				$remote_destination . 'continents-cities-' . $language_update->language . '.po',
				$remote_destination . 'continents-cities-' . $language_update->language . '.mo',
				$remote_destination . 'continents-cities-' . $language_update->language . '.l10n.php',
			);

			$json_translation_files = glob( $language_directory . $language_update->language . '-*.json' );
			if ( $json_translation_files ) {
				foreach ( $json_translation_files as $json_translation_file ) {
					$files[] = str_replace( $language_directory, $remote_destination, $json_translation_file );
				}
			}
		} else {
			$files = array(
				$remote_destination . $language_update->slug . '-' . $language_update->language . '.po',
				$remote_destination . $language_update->slug . '-' . $language_update->language . '.mo',
				$remote_destination . $language_update->slug . '-' . $language_update->language . '.l10n.php',
			);

			$language_directory     = $language_directory . $language_update->type . 's/';
			$json_translation_files = glob( $language_directory . $language_update->slug . '-' . $language_update->language . '-*.json' );
			if ( $json_translation_files ) {
				foreach ( $json_translation_files as $json_translation_file ) {
					$files[] = str_replace( $language_directory, $remote_destination, $json_translation_file );
				}
			}
		}

		$files = array_filter( $files, array( $wp_filesystem, 'exists' ) );

		// Không có tệp nào để xóa.
		if ( ! $files ) {
			return true;
		}

		// Kiểm tra tất cả các tệp có quyền ghi trước khi xóa đích đến.
		$unwritable_files = array();

		// Kiểm tra quyền ghi.
		foreach ( $files as $file ) {
			if ( ! $wp_filesystem->is_writable( $file ) ) {
				// Thử thay đổi quyền để cho phép ghi và thử lại.
				$wp_filesystem->chmod( $file, FS_CHMOD_FILE );
				if ( ! $wp_filesystem->is_writable( $file ) ) {
					$unwritable_files[] = $file;
				}
			}
		}

		if ( ! empty( $unwritable_files ) ) {
			return new WP_Error( 'files_not_writable', $this->strings['files_not_writable'], implode( ', ', $unwritable_files ) );
		}

		foreach ( $files as $file ) {
			if ( ! $wp_filesystem->delete( $file ) ) {
				return new WP_Error( 'remove_old_failed', $this->strings['remove_old_failed'] );
			}
		}

		return true;
	}
}
