<?php
/**
 * API Nâng cấp: Lớp WP_Automatic_Updater
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Lớp lõi dùng để xử lý các cập nhật nền tự động.
 *
 * @since 3.7.0
 * @since 4.6.0 Chuyển sang tệp riêng từ wp-admin/includes/class-wp-upgrader.php.
 */
#[AllowDynamicProperties]
class WP_Automatic_Updater {

	/**
	 * Theo dõi kết quả cập nhật trong quá trình xử lý.
	 *
	 * @var array
	 */
	protected $update_results = array();

	/**
	 * Xác định xem toàn bộ trình cập nhật tự động có bị vô hiệu hóa hay không.
	 *
	 * @since 3.7.0
	 *
	 * @return bool True nếu trình cập nhật tự động bị vô hiệu hóa, false nếu ngược lại.
	 */
	public function is_disabled() {
		// Cập nhật nền bị vô hiệu hóa nếu bạn không muốn thay đổi tệp.
		if ( ! wp_is_file_mod_allowed( 'automatic_updater' ) ) {
			return true;
		}

		if ( wp_installing() ) {
			return true;
		}

		// Kiểm soát chi tiết hơn có thể thực hiện thông qua hằng số WP_AUTO_UPDATE_CORE và các bộ lọc.
		$disabled = defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED;

		/**
		 * Lọc xem có vô hiệu hóa hoàn toàn cập nhật nền hay không.
		 *
		 * Có nhiều bộ lọc và điều khiển chi tiết hơn để vô hiệu hóa có chọn lọc.
		 * Bộ lọc này có tên tương đương với hằng số AUTOMATIC_UPDATER_DISABLED.
		 *
		 * Điều này cũng vô hiệu hóa email thông báo cập nhật. Điều đó có thể thay đổi trong tương lai.
		 *
		 * @since 3.7.0
		 *
		 * @param bool $disabled Có nên vô hiệu hóa trình cập nhật hay không.
		 */
		return apply_filters( 'automatic_updater_disabled', $disabled );
	}

	/**
	 * Kiểm tra xem quyền truy cập vào một thư mục nhất định có được phép hay không.
	 *
	 * Được sử dụng khi phát hiện các checkout hệ thống quản lý phiên bản. Có tính đến
	 * các hạn chế `open_basedir` của PHP, để WordPress không cố truy cập
	 * các thư mục mà nó không được phép.
	 *
	 * @since 6.2.0
	 *
	 * @param string $dir Thư mục cần kiểm tra.
	 * @return bool True nếu quyền truy cập thư mục được phép, false nếu ngược lại.
	 */
	public function is_allowed_dir( $dir ) {
		if ( is_string( $dir ) ) {
			$dir = trim( $dir );
		}

		if ( ! is_string( $dir ) || '' === $dir ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: The "$dir" argument. */
					__( 'The "%s" argument must be a non-empty string.' ),
					'$dir'
				),
				'6.2.0'
			);

			return false;
		}

		$open_basedir = ini_get( 'open_basedir' );

		if ( empty( $open_basedir ) ) {
			return true;
		}

		$open_basedir_list = explode( PATH_SEPARATOR, $open_basedir );

		foreach ( $open_basedir_list as $basedir ) {
			if ( '' !== trim( $basedir ) && str_starts_with( $dir, $basedir ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Kiểm tra các checkout hệ thống quản lý phiên bản.
	 *
	 * Kiểm tra Subversion, Git, Mercurial và Bazaar. Đệ quy tìm kiếm lên
	 * hệ thống tệp đến đỉnh ổ đĩa, ưu tiên phát hiện một VCS
	 * checkout ở đâu đó.
	 *
	 * ABSPATH luôn được kiểm tra ngoài `$context` (có thể là
	 * thư mục wp-content chẳng hạn). Giả định cơ bản là nếu bạn đang
	 * sử dụng hệ thống quản lý phiên bản *ở bất kỳ đâu*, thì bạn nên tự quyết định
	 * cách mọi thứ được cập nhật.
	 *
	 * @since 3.7.0
	 *
	 * @param string $context Đường dẫn hệ thống tệp cần kiểm tra, ngoài ABSPATH.
	 * @return bool True nếu phát hiện checkout VCS tại `$context` hoặc ABSPATH,
	 *              hoặc bất kỳ đâu cao hơn. False nếu ngược lại.
	 */
	public function is_vcs_checkout( $context ) {
		$context_dirs = array( untrailingslashit( $context ) );
		if ( ABSPATH !== $context ) {
			$context_dirs[] = untrailingslashit( ABSPATH );
		}

		$vcs_dirs   = array( '.svn', '.git', '.hg', '.bzr' );
		$check_dirs = array();

		foreach ( $context_dirs as $context_dir ) {
			// Đi lên từ $context_dir đến thư mục gốc.
			do {
				$check_dirs[] = $context_dir;

				// Khi đã đến '/' hoặc 'C:\', cần dừng lại. dirname sẽ tiếp tục trả về đầu vào tại đây.
				if ( dirname( $context_dir ) === $context_dir ) {
					break;
				}

				// Tiếp tục lên một cấp mỗi lần.
			} while ( $context_dir = dirname( $context_dir ) );
		}

		$check_dirs = array_unique( $check_dirs );
		$checkout   = false;

		// Tìm kiếm tất cả các thư mục đã tìm thấy để phát hiện hệ thống quản lý phiên bản.
		foreach ( $vcs_dirs as $vcs_dir ) {
			foreach ( $check_dirs as $check_dir ) {
				if ( ! $this->is_allowed_dir( $check_dir ) ) {
					continue;
				}

				$checkout = is_dir( rtrim( $check_dir, '\\/' ) . "/$vcs_dir" );
				if ( $checkout ) {
					break 2;
				}
			}
		}

		/**
		 * Lọc xem trình cập nhật tự động có nên coi một vị trí hệ thống tệp
		 * là có thể được quản lý bởi hệ thống quản lý phiên bản hay không.
		 *
		 * @since 3.7.0
		 *
		 * @param bool $checkout  Có phát hiện checkout VCS tại `$context`
		 *                        hoặc ABSPATH, hoặc bất kỳ đâu cao hơn hay không.
		 * @param string $context Ngữ cảnh hệ thống tệp (đường dẫn) mà
		 *                        trạng thái hệ thống tệp nên được kiểm tra.
		 */
		return apply_filters( 'automatic_updates_is_vcs_checkout', $checkout, $context );
	}

	/**
	 * Kiểm tra xem chúng ta có thể và có nên cập nhật một mục cụ thể hay không.
	 *
	 * @since 3.7.0
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 *
	 * @param string $type    Loại cập nhật đang được kiểm tra: 'core', 'theme',
	 *                        'plugin', 'translation'.
	 * @param object $item    Đề xuất cập nhật.
	 * @param string $context Ngữ cảnh hệ thống tệp (đường dẫn) mà quyền truy cập
	 *                        và trạng thái hệ thống tệp nên được kiểm tra.
	 * @return bool True nếu mục nên được cập nhật, false nếu ngược lại.
	 */
	public function should_update( $type, $item, $context ) {
		// Dùng để kiểm tra xem WP_Filesystem có được thiết lập để cho phép cập nhật không cần giám sát hay không.
		$skin = new Automatic_Upgrader_Skin();

		if ( $this->is_disabled() ) {
			return false;
		}

		// Chỉ nới lỏng kiểm tra hệ thống tệp khi cập nhật không bao gồm tệp mới.
		$allow_relaxed_file_ownership = false;
		if ( 'core' === $type && isset( $item->new_files ) && ! $item->new_files ) {
			$allow_relaxed_file_ownership = true;
		}

		// Nếu không thể tự động cập nhật lõi, vẫn có thể gửi email cho người dùng.
		if ( ! $skin->request_filesystem_credentials( false, $context, $allow_relaxed_file_ownership )
			|| $this->is_vcs_checkout( $context )
		) {
			if ( 'core' === $type ) {
				$this->send_core_update_notification_email( $item );
			}
			return false;
		}

		// Tiếp theo, đây có phải mục chúng ta có thể cập nhật không?
		if ( 'core' === $type ) {
			$update = Core_Upgrader::should_update_to_version( $item->current );
		} elseif ( 'plugin' === $type || 'theme' === $type ) {
			$update = ! empty( $item->autoupdate );

			if ( ! $update && wp_is_auto_update_enabled_for_type( $type ) ) {
				// Kiểm tra xem quản trị viên trang web đã bật tự động cập nhật mặc định cho mục cụ thể hay chưa.
				$auto_updates = (array) get_site_option( "auto_update_{$type}s", array() );
				$update       = in_array( $item->{$type}, $auto_updates, true );
			}
		} else {
			$update = ! empty( $item->autoupdate );
		}

		// Nếu cờ `disable_autoupdate` được đặt, ghi đè mọi lựa chọn của người dùng, nhưng cho phép bộ lọc.
		if ( ! empty( $item->disable_autoupdate ) ) {
			$update = false;
		}

		/**
		 * Lọc xem có tự động cập nhật lõi, plugin, giao diện, hay ngôn ngữ hay không.
		 *
		 * Phần động của tên hook, `$type`, tham chiếu đến loại cập nhật
		 * đang được kiểm tra.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `auto_update_core`
		 *  - `auto_update_plugin`
		 *  - `auto_update_theme`
		 *  - `auto_update_translation`
		 *
		 * Từ WordPress 3.7, các phiên bản phụ và phát triển của lõi, và bản dịch đã
		 * được tự động cập nhật theo mặc định. Các cài đặt mới trên WordPress 5.6 trở lên cũng sẽ
		 * tự động cập nhật phiên bản chính theo mặc định. Bắt đầu từ 5.6, các trang cũ có thể chọn
		 * tự động cập nhật phiên bản chính, và tự động cập nhật cho plugin và giao diện.
		 *
		 * Xem các bộ lọc {@see 'allow_dev_auto_core_updates'}, {@see 'allow_minor_auto_core_updates'},
		 * và {@see 'allow_major_auto_core_updates'} để có cách trực tiếp hơn
		 * để điều chỉnh cập nhật lõi.
		 *
		 * @since 3.7.0
		 * @since 5.5.0 Tham số `$update` chấp nhận giá trị null.
		 *
		 * @param bool|null $update Có cập nhật hay không. Giá trị null được sử dụng nội bộ
		 *                          để phát hiện xem không có gì hook vào bộ lọc này.
		 * @param object    $item   Đề xuất cập nhật.
		 */
		$update = apply_filters( "auto_update_{$type}", $update, $item );

		if ( ! $update ) {
			if ( 'core' === $type ) {
				$this->send_core_update_notification_email( $item );
			}
			return false;
		}

		// Nếu đây là cập nhật lõi, chúng ta có thực sự tương thích với yêu cầu của nó không?
		if ( 'core' === $type ) {
			global $wpdb;

			$php_compat = version_compare( PHP_VERSION, $item->php_version, '>=' );
			if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && empty( $wpdb->is_mysql ) ) {
				$mysql_compat = true;
			} else {
				$mysql_compat = version_compare( $wpdb->db_version(), $item->mysql_version, '>=' );
			}

			if ( ! $php_compat || ! $mysql_compat ) {
				return false;
			}
		}

		// Nếu cập nhật plugin hoặc giao diện, đảm bảo yêu cầu phiên bản PHP tối thiểu được đáp ứng.
		if ( in_array( $type, array( 'plugin', 'theme' ), true ) ) {
			if ( ! empty( $item->requires_php ) && version_compare( PHP_VERSION, $item->requires_php, '<' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Thông báo cho quản trị viên về bản cập nhật lõi.
	 *
	 * @since 3.7.0
	 *
	 * @param object $item Đề xuất cập nhật.
	 * @return bool True nếu quản trị viên được thông báo về cập nhật lõi,
	 *              false nếu ngược lại.
	 */
	protected function send_core_update_notification_email( $item ) {
		$notified = get_site_option( 'auto_core_update_notified' );

		// Không thông báo nếu đã thông báo cùng địa chỉ email về cùng phiên bản.
		if ( $notified
			&& get_site_option( 'admin_email' ) === $notified['email']
			&& $notified['version'] === $item->current
		) {
			return false;
		}

		// Xem liệu có cần thông báo cho người dùng về cập nhật lõi hay không.
		$notify = ! empty( $item->notify_email );

		/**
		 * Lọc xem có nên thông báo cho quản trị viên trang web về bản cập nhật lõi mới hay không.
		 *
		 * Theo mặc định, quản trị viên được thông báo khi đề xuất cập nhật nhận được
		 * từ WordPress.org đặt một cờ cụ thể. Điều này cho phép một số quyền quyết định
		 * về việc có thông báo hay không và khi nào thông báo.
		 *
		 * Bộ lọc này chỉ được đánh giá một lần mỗi bản phát hành. Nếu cùng địa chỉ email
		 * đã được thông báo về cùng phiên bản mới, WordPress sẽ không gửi email
		 * lặp lại cho quản trị viên.
		 *
		 * Bộ lọc này cũng được sử dụng trên about.php để kiểm tra xem một plugin có vô hiệu hóa
		 * các thông báo này hay không.
		 *
		 * @since 3.7.0
		 *
		 * @param bool   $notify Có thông báo cho quản trị viên trang web hay không.
		 * @param object $item   Đề xuất cập nhật.
		 */
		if ( ! apply_filters( 'send_core_update_notification_email', $notify, $item ) ) {
			return false;
		}

		$this->send_email( 'manual', $item );
		return true;
	}

	/**
	 * Cập nhật một mục, nếu phù hợp.
	 *
	 * @since 3.7.0
	 *
	 * @param string $type Loại cập nhật đang được kiểm tra: 'core', 'theme', 'plugin', 'translation'.
	 * @param object $item Đề xuất cập nhật.
	 * @return null|WP_Error
	 */
	public function update( $type, $item ) {
		$skin = new Automatic_Upgrader_Skin();

		switch ( $type ) {
			case 'core':
				// Trình nâng cấp lõi không sử dụng skin của Upgrader trong phần chính của quá trình nâng cấp, thay vào đó kích hoạt một bộ lọc.
				add_filter( 'update_feedback', array( $skin, 'feedback' ) );
				$upgrader = new Core_Upgrader( $skin );
				$context  = ABSPATH;
				break;
			case 'plugin':
				$upgrader = new Plugin_Upgrader( $skin );
				$context  = WP_PLUGIN_DIR; // Không hỗ trợ thư mục Plugin tùy chỉnh, hoặc cập nhật cho WPMU_PLUGIN_DIR.
				break;
			case 'theme':
				$upgrader = new Theme_Upgrader( $skin );
				$context  = get_theme_root( $item->theme );
				break;
			case 'translation':
				$upgrader = new Language_Pack_Upgrader( $skin );
				$context  = WP_CONTENT_DIR; // WP_LANG_DIR;
				break;
		}

		// Xác định xem chúng ta có thể và có nên thực hiện cập nhật này hay không.
		if ( ! $this->should_update( $type, $item, $context ) ) {
			return false;
		}

		/**
		 * Kích hoạt ngay trước khi tự động cập nhật.
		 *
		 * @since 4.4.0
		 *
		 * @param string $type    Loại cập nhật đang được kiểm tra: 'core', 'theme', 'plugin', hoặc 'translation'.
		 * @param object $item    Đề xuất cập nhật.
		 * @param string $context Ngữ cảnh hệ thống tệp (đường dẫn) mà quyền truy cập và trạng thái
		 *                        hệ thống tệp nên được kiểm tra.
		 */
		do_action( 'pre_auto_update', $type, $item, $context );

		$upgrader_item = $item;
		switch ( $type ) {
			case 'core':
				/* translators: %s: WordPress version. */
				$skin->feedback( __( 'Updating to WordPress %s' ), $item->version );
				/* translators: %s: WordPress version. */
				$item_name = sprintf( __( 'WordPress %s' ), $item->version );
				break;
			case 'theme':
				$upgrader_item = $item->theme;
				$theme         = wp_get_theme( $upgrader_item );
				$item_name     = $theme->Get( 'Name' );
				// Thêm phiên bản hiện tại để có thể báo cáo trong email thông báo.
				$item->current_version = $theme->get( 'Version' );
				if ( empty( $item->current_version ) ) {
					$item->current_version = false;
				}
				/* translators: %s: Theme name. */
				$skin->feedback( __( 'Updating theme: %s' ), $item_name );
				break;
			case 'plugin':
				$upgrader_item = $item->plugin;
				$plugin_data   = get_plugin_data( $context . '/' . $upgrader_item );
				$item_name     = $plugin_data['Name'];
				// Thêm phiên bản hiện tại để có thể báo cáo trong email thông báo.
				$item->current_version = $plugin_data['Version'];
				if ( empty( $item->current_version ) ) {
					$item->current_version = false;
				}
				/* translators: %s: Plugin name. */
				$skin->feedback( __( 'Updating plugin: %s' ), $item_name );
				break;
			case 'translation':
				$language_item_name = $upgrader->get_name_for_update( $item );
				/* translators: %s: Project name (plugin, theme, or WordPress). */
				$item_name = sprintf( __( 'Translations for %s' ), $language_item_name );
				/* translators: 1: Project name (plugin, theme, or WordPress), 2: Language. */
				$skin->feedback( sprintf( __( 'Updating translations for %1$s (%2$s)&#8230;' ), $language_item_name, $item->language ) );
				break;
		}

		$allow_relaxed_file_ownership = false;
		if ( 'core' === $type && isset( $item->new_files ) && ! $item->new_files ) {
			$allow_relaxed_file_ownership = true;
		}

		$is_debug = WP_DEBUG && WP_DEBUG_LOG;
		if ( 'plugin' === $type ) {
			$was_active = is_plugin_active( $upgrader_item );
			if ( $is_debug ) {
				error_log( '    Upgrading plugin ' . var_export( $item->slug, true ) . '...' );
			}
		}

		if ( 'theme' === $type && $is_debug ) {
			error_log( '    Upgrading theme ' . var_export( $item->theme, true ) . '...' );
		}

		/*
		 * Bật chế độ bảo trì trước khi nâng cấp plugin hoặc giao diện.
		 *
		 * Điều này tránh phát hiện các lỗi không nghiêm trọng tiềm ẩn
		 * trong khi quét tìm lỗi nghiêm trọng nếu một số tệp vẫn
		 * đang được di chuyển.
		 *
		 * Mặc dù các kiểm tra này chỉ dành cho plugin,
		 * chế độ bảo trì được bật cho tất cả các loại nâng cấp vì bất kỳ
		 * cập nhật nào cũng có thể chứa lỗi hoặc cảnh báo, có thể khiến
		 * quá trình quét bỏ sót lỗi nghiêm trọng trong cập nhật plugin.
		 */
		if ( 'translation' !== $type ) {
			$upgrader->maintenance_mode( true );
		}

		// Trang web này sắp được cập nhật!
		$upgrade_result = $upgrader->upgrade(
			$upgrader_item,
			array(
				'clear_update_cache'           => false,
				// Luôn sử dụng bản build một phần nếu có thể cho cập nhật lõi.
				'pre_check_md5'                => false,
				// Chỉ khả dụng cho cập nhật lõi.
				'attempt_rollback'             => true,
				// Cho phép nới lỏng quyền sở hữu tệp trong một số tình huống.
				'allow_relaxed_file_ownership' => $allow_relaxed_file_ownership,
			)
		);

		/*
		 * Sau khi WP_Upgrader::upgrade() hoàn thành, chế độ bảo trì bị vô hiệu hóa.
		 *
		 * Bật lại chế độ bảo trì trong khi cố phát hiện lỗi nghiêm trọng
		 * và có thể hoàn tác.
		 *
		 * Điều này tránh lỗi nếu trang web được truy cập trong khi có lỗi nghiêm trọng
		 * hoặc khi các tệp vẫn đang được di chuyển.
		 */
		if ( 'translation' !== $type ) {
			$upgrader->maintenance_mode( true );
		}

		// Nếu hệ thống tệp không khả dụng, trả về false.
		if ( false === $upgrade_result ) {
			$upgrade_result = new WP_Error( 'fs_unavailable', __( 'Could not access filesystem.' ) );
		}

		if ( 'core' === $type ) {
			if ( is_wp_error( $upgrade_result )
				&& ( 'up_to_date' === $upgrade_result->get_error_code()
					|| 'locked' === $upgrade_result->get_error_code() )
			) {
				// Cho phép khách truy cập duyệt trang web trở lại.
				$upgrader->maintenance_mode( false );

				/*
				 * Đây không phải lỗi thực sự, xử lý như cập nhật bị bỏ qua thay vì
				 * kích hoạt các quy trình xử lý lỗi sau cập nhật lõi.
				 */
				return false;
			}

			// Lõi không xuất thông tin này, nên hãy thêm vào để không bị nhầm lẫn.
			if ( is_wp_error( $upgrade_result ) ) {
				$upgrade_result->add( 'installation_failed', __( 'Installation failed.' ) );
				$skin->error( $upgrade_result );
			} else {
				$skin->feedback( __( 'WordPress updated successfully.' ) );
			}
		}

		$is_debug = WP_DEBUG && WP_DEBUG_LOG;

		if ( 'theme' === $type && $is_debug ) {
			error_log( '    Theme ' . var_export( $item->theme, true ) . ' has been upgraded.' );
		}

		if ( 'plugin' === $type ) {
			if ( $is_debug ) {
				error_log( '    Plugin ' . var_export( $item->slug, true ) . ' has been upgraded.' );
				if ( is_plugin_inactive( $upgrader_item ) ) {
					error_log( '    ' . var_export( $upgrader_item, true ) . ' is inactive and will not be checked for fatal errors.' );
				}
			}

			if ( $was_active && ! is_wp_error( $upgrade_result ) ) {

				/*
				 * Giới hạn thời gian thông thường là năm phút. Tuy nhiên, do một yêu cầu loopback
				 * sắp được thực hiện, tăng giới hạn thời gian để tính đến điều này.
				 */
				if ( function_exists( 'set_time_limit' ) ) {
					set_time_limit( 10 * MINUTE_IN_SECONDS );
				}

				/*
				 * Tránh tình trạng chạy đua khi có 2 plugin liên tiếp có
				 * lỗi nghiêm trọng. Dường như cần một khoảng trễ nhỏ để loopback
				 * sử dụng mã plugin đã cập nhật trong yêu cầu. Điều này có thể khiến
				 * kiểm tra lỗi nghiêm trọng của plugin thứ hai không chính xác, và cũng có thể ảnh hưởng
				 * đến các kiểm tra plugin tiếp theo.
				 */
				sleep( 2 );

				if ( $this->has_fatal_error() ) {
					$upgrade_result = new WP_Error();
					$temp_backup    = array(
						array(
							'dir'  => 'plugins',
							'slug' => $item->slug,
							'src'  => WP_PLUGIN_DIR,
						),
					);

					$backup_restored = $upgrader->restore_temp_backup( $temp_backup );
					if ( is_wp_error( $backup_restored ) ) {
						$upgrade_result->add(
							'plugin_update_fatal_error_rollback_failed',
							sprintf(
								/* translators: %s: The plugin's slug. */
								__( "The update for '%s' contained a fatal error. The previously installed version could not be restored." ),
								$item->slug
							)
						);

						$upgrade_result->merge_from( $backup_restored );
					} else {
						$upgrade_result->add(
							'plugin_update_fatal_error_rollback_successful',
							sprintf(
								/* translators: %s: The plugin's slug. */
								__( "The update for '%s' contained a fatal error. The previously installed version has been restored." ),
								$item->slug
							)
						);

						$backup_deleted = $upgrader->delete_temp_backup( $temp_backup );
						if ( is_wp_error( $backup_deleted ) ) {
							$upgrade_result->merge_from( $backup_deleted );
						}
					}

					/*
					 * Nếu email không hoạt động, ghi lại (các) thông báo để
					 * tệp log chứa ngữ cảnh cho lỗi nghiêm trọng,
					 * và liệu có thực hiện hoàn tác hay không.
					 *
					 * `trigger_error()` không được sử dụng vì nó xuất stack trace
					 * đến vị trí này thay vì đến lỗi nghiêm trọng, sẽ
					 * xuất hiện phía trên mục này trong tệp log.
					 */
					if ( $is_debug ) {
						error_log( '    ' . implode( "\n", $upgrade_result->get_error_messages() ) );
					}
				} elseif ( $is_debug ) {
					error_log( '    The update for ' . var_export( $item->slug, true ) . ' has no fatal errors.' );
				}
			}
		}

		// Tất cả các quá trình đã hoàn tất. Cho phép khách truy cập duyệt trang web trở lại.
		if ( 'translation' !== $type ) {
			$upgrader->maintenance_mode( false );
		}

		$this->update_results[ $type ][] = (object) array(
			'item'     => $item,
			'result'   => $upgrade_result,
			'name'     => $item_name,
			'messages' => $skin->get_upgrade_messages(),
		);

		return $upgrade_result;
	}

	/**
	 * Khởi chạy quá trình cập nhật nền, lặp qua tất cả các cập nhật đang chờ.
	 *
	 * @since 3.7.0
	 */
	public function run() {
		if ( $this->is_disabled() ) {
			return;
		}

		if ( ! is_main_network() || ! is_main_site() ) {
			return;
		}

		if ( ! WP_Upgrader::create_lock( 'auto_updater' ) ) {
			return;
		}

		$is_debug = WP_DEBUG && WP_DEBUG_LOG;

		if ( $is_debug ) {
			error_log( 'Automatic updates starting...' );
		}

		// Không tự động chạy những thứ này, vì chúng ta sẽ tự xử lý.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );
		remove_action( 'upgrader_process_complete', 'wp_version_check' );
		remove_action( 'upgrader_process_complete', 'wp_update_plugins' );
		remove_action( 'upgrader_process_complete', 'wp_update_themes' );

		// Tiếp theo, plugin.
		wp_update_plugins(); // Kiểm tra cập nhật plugin.
		$plugin_updates = get_site_transient( 'update_plugins' );
		if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
			if ( $is_debug ) {
				error_log( '  Automatic plugin updates starting...' );
			}

			foreach ( $plugin_updates->response as $plugin ) {
				$this->update( 'plugin', $plugin );
			}

			// Buộc làm mới thông tin cập nhật plugin.
			wp_clean_plugins_cache();

			if ( $is_debug ) {
				error_log( '  Automatic plugin updates complete.' );
			}
		}

		// Tiếp theo, các giao diện.
		wp_update_themes();  // Kiểm tra cập nhật giao diện.
		$theme_updates = get_site_transient( 'update_themes' );
		if ( $theme_updates && ! empty( $theme_updates->response ) ) {
			if ( $is_debug ) {
				error_log( '  Automatic theme updates starting...' );
			}

			foreach ( $theme_updates->response as $theme ) {
				$this->update( 'theme', (object) $theme );
			}
			// Buộc làm mới thông tin cập nhật giao diện.
			wp_clean_themes_cache();

			if ( $is_debug ) {
				error_log( '  Automatic theme updates complete.' );
			}
		}

		if ( $is_debug ) {
			error_log( 'Automatic updates complete.' );
		}

		// Tiếp theo, xử lý bất kỳ cập nhật lõi nào.
		wp_version_check(); // Kiểm tra cập nhật lõi.
		$core_update = find_core_auto_update();

		if ( $core_update ) {
			$this->update( 'core', $core_update );
		}

		/*
		 * Dọn dẹp, và kiểm tra bất kỳ bản dịch đang chờ nào.
		 * (Core_Upgrader kiểm tra cập nhật lõi.)
		 */
		$theme_stats = array();
		if ( isset( $this->update_results['theme'] ) ) {
			foreach ( $this->update_results['theme'] as $upgrade ) {
				$theme_stats[ $upgrade->item->theme ] = ( true === $upgrade->result );
			}
		}
		wp_update_themes( $theme_stats ); // Kiểm tra cập nhật giao diện.

		$plugin_stats = array();
		if ( isset( $this->update_results['plugin'] ) ) {
			foreach ( $this->update_results['plugin'] as $upgrade ) {
				$plugin_stats[ $upgrade->item->plugin ] = ( true === $upgrade->result );
			}
		}
		wp_update_plugins( $plugin_stats ); // Kiểm tra cập nhật plugin.

		// Cuối cùng, xử lý bất kỳ bản dịch mới nào.
		$language_updates = wp_get_translation_updates();
		if ( $language_updates ) {
			foreach ( $language_updates as $update ) {
				$this->update( 'translation', $update );
			}

			// Xóa bộ nhớ đệm hiện có.
			wp_clean_update_cache();

			wp_version_check();  // Kiểm tra cập nhật lõi.
			wp_update_themes();  // Kiểm tra cập nhật giao diện.
			wp_update_plugins(); // Kiểm tra cập nhật plugin.
		}

		// Gửi email gỡ lỗi cho quản trị viên cho tất cả các cài đặt phát triển.
		if ( ! empty( $this->update_results ) ) {
			$development_version = str_contains( wp_get_wp_version(), '-' );

			/**
			 * Lọc xem có gửi email gỡ lỗi cho mỗi lần cập nhật nền tự động hay không.
			 *
			 * @since 3.7.0
			 *
			 * @param bool $development_version Theo mặc định, email được gửi nếu
			 *                                  cài đặt là phiên bản phát triển.
			 *                                  Trả về false để không gửi email.
			 */
			if ( apply_filters( 'automatic_updates_send_debug_email', $development_version ) ) {
				$this->send_debug_email();
			}

			if ( ! empty( $this->update_results['core'] ) ) {
				$this->after_core_update( $this->update_results['core'][0] );
			} elseif ( ! empty( $this->update_results['plugin'] ) || ! empty( $this->update_results['theme'] ) ) {
				$this->after_plugin_theme_update( $this->update_results );
			}

			/**
			 * Kích hoạt sau khi tất cả các cập nhật tự động đã chạy.
			 *
			 * @since 3.8.0
			 *
			 * @param array $update_results Kết quả của tất cả các lần cập nhật đã thử.
			 */
			do_action( 'automatic_updates_complete', $this->update_results );
		}

		WP_Upgrader::release_lock( 'auto_updater' );
	}

	/**
	 * Kiểm tra xem có nên gửi email và tránh xử lý các cập nhật trong tương lai sau
	 * khi thử cập nhật lõi hay không.
	 *
	 * @since 3.7.0
	 *
	 * @param object $update_result Kết quả cập nhật lõi. Bao gồm đề xuất cập nhật và kết quả.
	 */
	protected function after_core_update( $update_result ) {
		$wp_version = wp_get_wp_version();

		$core_update = $update_result->item;
		$result      = $update_result->result;

		if ( ! is_wp_error( $result ) ) {
			$this->send_email( 'success', $core_update );
			return;
		}

		$error_code = $result->get_error_code();

		/*
		 * Bất kỳ mã WP_Error nào trong số này đều là lỗi nghiêm trọng, xảy ra sau khi bắt đầu sao chép các tệp lõi.
		 * Không nên thử thực hiện cập nhật nền lần nữa cho đến khi có một cập nhật một-cú-nhấp thành công do người dùng thực hiện.
		 */
		$critical = false;
		if ( 'disk_full' === $error_code || str_contains( $error_code, '__copy_dir' ) ) {
			$critical = true;
		} elseif ( 'rollback_was_required' === $error_code && is_wp_error( $result->get_error_data()->rollback ) ) {
			// A rollback is only critical if it failed too.
			$critical        = true;
			$rollback_result = $result->get_error_data()->rollback;
		} elseif ( str_contains( $error_code, 'do_rollback' ) ) {
			$critical = true;
		}

		if ( $critical ) {
			$critical_data = array(
				'attempted'  => $core_update->current,
				'current'    => $wp_version,
				'error_code' => $error_code,
				'error_data' => $result->get_error_data(),
				'timestamp'  => time(),
				'critical'   => true,
			);
			if ( isset( $rollback_result ) ) {
				$critical_data['rollback_code'] = $rollback_result->get_error_code();
				$critical_data['rollback_data'] = $rollback_result->get_error_data();
			}
			update_site_option( 'auto_core_update_failed', $critical_data );
			$this->send_email( 'critical', $core_update, $result );
			return;
		}

		/*
		 * Any other WP_Error code (like download_failed or files_not_writable) occurs before
		 * we tried to copy over core files. Thus, the failures are early and graceful.
		 *
		 * We should avoid trying to perform a background update again for the same version.
		 * But we can try again if another version is released.
		 *
		 * For certain 'transient' failures, like download_failed, we should allow retries.
		 * In fact, let's schedule a special update for an hour from now. (It's possible
		 * the issue could actually be on WordPress.org's side.) If that one fails, then email.
		 */
		$send               = true;
		$transient_failures = array( 'incompatible_archive', 'download_failed', 'insane_distro', 'locked' );
		if ( in_array( $error_code, $transient_failures, true ) && ! get_site_option( 'auto_core_update_failed' ) ) {
			wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'wp_maybe_auto_update' );
			$send = false;
		}

		$notified = get_site_option( 'auto_core_update_notified' );

		// Don't notify if we've already notified the same email address of the same version of the same notification type.
		if ( $notified
			&& 'fail' === $notified['type']
			&& get_site_option( 'admin_email' ) === $notified['email']
			&& $notified['version'] === $core_update->current
		) {
			$send = false;
		}

		update_site_option(
			'auto_core_update_failed',
			array(
				'attempted'  => $core_update->current,
				'current'    => $wp_version,
				'error_code' => $error_code,
				'error_data' => $result->get_error_data(),
				'timestamp'  => time(),
				'retry'      => in_array( $error_code, $transient_failures, true ),
			)
		);

		if ( $send ) {
			$this->send_email( 'fail', $core_update, $result );
		}
	}

	/**
	 * Sends an email upon the completion or failure of a background core update.
	 *
	 * @since 3.7.0
	 *
	 * @param string $type        The type of email to send. Can be one of 'success', 'fail', 'manual', 'critical'.
	 * @param object $core_update The update offer that was attempted.
	 * @param mixed  $result      Optional. The result for the core update. Can be WP_Error.
	 */
	protected function send_email( $type, $core_update, $result = null ) {
		update_site_option(
			'auto_core_update_notified',
			array(
				'type'      => $type,
				'email'     => get_site_option( 'admin_email' ),
				'version'   => $core_update->current,
				'timestamp' => time(),
			)
		);

		$next_user_core_update = get_preferred_from_update_core();

		// If the update transient is empty, use the update we just performed.
		if ( ! $next_user_core_update ) {
			$next_user_core_update = $core_update;
		}

		if ( 'upgrade' === $next_user_core_update->response
			&& version_compare( $next_user_core_update->version, $core_update->version, '>' )
		) {
			$newer_version_available = true;
		} else {
			$newer_version_available = false;
		}

		/**
		 * Filters whether to send an email following an automatic background core update.
		 *
		 * @since 3.7.0
		 *
		 * @param bool   $send        Whether to send the email. Default true.
		 * @param string $type        The type of email to send. Can be one of
		 *                            'success', 'fail', 'critical'.
		 * @param object $core_update The update offer that was attempted.
		 * @param mixed  $result      The result for the core update. Can be WP_Error.
		 */
		if ( 'manual' !== $type && ! apply_filters( 'auto_core_update_send_email', true, $type, $core_update, $result ) ) {
			return;
		}

		$admin_user = get_user_by( 'email', get_site_option( 'admin_email' ) );

		if ( $admin_user ) {
			$switched_locale = switch_to_user_locale( $admin_user->ID );
		} else {
			$switched_locale = switch_to_locale( get_locale() );
		}

		switch ( $type ) {
			case 'success': // We updated.
				/* translators: Site updated notification email subject. 1: Site title, 2: WordPress version. */
				$subject = __( '[%1$s] Your site has updated to WordPress %2$s' );
				break;

			case 'fail':   // We tried to update but couldn't.
			case 'manual': // We can't update (and made no attempt).
				/* translators: Update available notification email subject. 1: Site title, 2: WordPress version. */
				$subject = __( '[%1$s] WordPress %2$s is available. Please update!' );
				break;

			case 'critical': // We tried to update, started to copy files, then things went wrong.
				/* translators: Site down notification email subject. 1: Site title. */
				$subject = __( '[%1$s] URGENT: Your site may be down due to a failed update' );
				break;

			default:
				return;
		}

		// If the auto-update is not to the latest version, say that the current version of WP is available instead.
		$version = 'success' === $type ? $core_update->current : $next_user_core_update->current;
		$subject = sprintf( $subject, wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $version );

		$body = '';

		switch ( $type ) {
			case 'success':
				$body .= sprintf(
					/* translators: 1: Home URL, 2: WordPress version. */
					__( 'Howdy! Your site at %1$s has been updated automatically to WordPress %2$s.' ),
					home_url(),
					$core_update->current
				);
				$body .= "\n\n";
				if ( ! $newer_version_available ) {
					$body .= __( 'No further action is needed on your part.' ) . ' ';
				}

				// Can only reference the About screen if their update was successful.
				list( $about_version ) = explode( '-', $core_update->current, 2 );
				/* translators: %s: WordPress version. */
				$body .= sprintf( __( 'For more on version %s, see the About WordPress screen:' ), $about_version );
				$body .= "\n" . admin_url( 'about.php' );

				if ( $newer_version_available ) {
					/* translators: %s: WordPress latest version. */
					$body .= "\n\n" . sprintf( __( 'WordPress %s is also now available.' ), $next_user_core_update->current ) . ' ';
					$body .= __( 'Updating is easy and only takes a few moments:' );
					$body .= "\n" . network_admin_url( 'update-core.php' );
				}

				break;

			case 'fail':
			case 'manual':
				$body .= sprintf(
					/* translators: 1: Home URL, 2: WordPress version. */
					__( 'Please update your site at %1$s to WordPress %2$s.' ),
					home_url(),
					$next_user_core_update->current
				);

				$body .= "\n\n";

				/*
				 * Don't show this message if there is a newer version available.
				 * Potential for confusion, and also not useful for them to know at this point.
				 */
				if ( 'fail' === $type && ! $newer_version_available ) {
					$body .= __( 'An attempt was made, but your site could not be updated automatically.' ) . ' ';
				}

				$body .= __( 'Updating is easy and only takes a few moments:' );
				$body .= "\n" . network_admin_url( 'update-core.php' );
				break;

			case 'critical':
				if ( $newer_version_available ) {
					$body .= sprintf(
						/* translators: 1: Home URL, 2: WordPress version. */
						__( 'Your site at %1$s experienced a critical failure while trying to update WordPress to version %2$s.' ),
						home_url(),
						$core_update->current
					);
				} else {
					$body .= sprintf(
						/* translators: 1: Home URL, 2: WordPress latest version. */
						__( 'Your site at %1$s experienced a critical failure while trying to update to the latest version of WordPress, %2$s.' ),
						home_url(),
						$core_update->current
					);
				}

				$body .= "\n\n" . __( "This means your site may be offline or broken. Don't panic; this can be fixed." );

				$body .= "\n\n" . __( "Please check out your site now. It's possible that everything is working. If it says you need to update, you should do so:" );
				$body .= "\n" . network_admin_url( 'update-core.php' );
				break;
		}

		$critical_support = 'critical' === $type && ! empty( $core_update->support_email );
		if ( $critical_support ) {
			// Support offer if available.
			$body .= "\n\n" . sprintf(
				/* translators: %s: Support email address. */
				__( 'The WordPress team is willing to help you. Forward this email to %s and the team will work with you to make sure your site is working.' ),
				$core_update->support_email
			);
		} else {
			// Add a note about the support forums.
			$body .= "\n\n" . __( 'If you experience any issues or need support, the volunteers in the WordPress.org support forums may be able to help.' );
			$body .= "\n" . __( 'https://wordpress.org/support/forums/' );
		}

		// Updates are important!
		if ( 'success' !== $type || $newer_version_available ) {
			$body .= "\n\n" . __( 'Keeping your site updated is important for security. It also makes the internet a safer place for you and your readers.' );
		}

		if ( $critical_support ) {
			$body .= ' ' . __( "Reach out to WordPress Core developers to ensure you'll never have this problem again." );
		}

		// If things are successful and we're now on the latest, mention plugins and themes if any are out of date.
		if ( 'success' === $type && ! $newer_version_available && ( get_plugin_updates() || get_theme_updates() ) ) {
			$body .= "\n\n" . __( 'You also have some plugins or themes with updates available. Update them now:' );
			$body .= "\n" . network_admin_url();
		}

		$body .= "\n\n" . __( 'The WordPress Team' ) . "\n";

		if ( 'critical' === $type && is_wp_error( $result ) ) {
			$body .= "\n***\n\n";
			/* translators: %s: WordPress version. */
			$body .= sprintf( __( 'Your site was running version %s.' ), get_bloginfo( 'version' ) );
			$body .= ' ' . __( 'Some data that describes the error your site encountered has been put together.' );
			$body .= ' ' . __( 'Your hosting company, support forum volunteers, or a friendly developer may be able to use this information to help you:' );

			/*
			 * If we had a rollback and we're still critical, then the rollback failed too.
			 * Loop through all errors (the main WP_Error, the update result, the rollback result) for code, data, etc.
			 */
			if ( 'rollback_was_required' === $result->get_error_code() ) {
				$errors = array( $result, $result->get_error_data()->update, $result->get_error_data()->rollback );
			} else {
				$errors = array( $result );
			}

			foreach ( $errors as $error ) {
				if ( ! is_wp_error( $error ) ) {
					continue;
				}

				$error_code = $error->get_error_code();
				/* translators: %s: Error code. */
				$body .= "\n\n" . sprintf( __( 'Error code: %s' ), $error_code );

				if ( 'rollback_was_required' === $error_code ) {
					continue;
				}

				if ( $error->get_error_message() ) {
					$body .= "\n" . $error->get_error_message();
				}

				$error_data = $error->get_error_data();
				if ( $error_data ) {
					$body .= "\n" . implode( ', ', (array) $error_data );
				}
			}

			$body .= "\n";
		}

		$to      = get_site_option( 'admin_email' );
		$headers = '';

		$email = compact( 'to', 'subject', 'body', 'headers' );

		/**
		 * Filters the email sent following an automatic background core update.
		 *
		 * @since 3.7.0
		 *
		 * @param array $email {
		 *     Array of email arguments that will be passed to wp_mail().
		 *
		 *     @type string $to      The email recipient. An array of emails
		 *                            can be returned, as handled by wp_mail().
		 *     @type string $subject The email's subject.
		 *     @type string $body    The email message body.
		 *     @type string $headers Any email headers, defaults to no headers.
		 * }
		 * @param string $type        The type of email being sent. Can be one of
		 *                            'success', 'fail', 'manual', 'critical'.
		 * @param object $core_update The update offer that was attempted.
		 * @param mixed  $result      The result for the core update. Can be WP_Error.
		 */
		$email = apply_filters( 'auto_core_update_email', $email, $type, $core_update, $result );

		wp_mail( $email['to'], wp_specialchars_decode( $email['subject'] ), $email['body'], $email['headers'] );

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Checks whether an email should be sent after attempting plugin or theme updates.
	 *
	 * @since 5.5.0
	 *
	 * @param array $update_results The results of update tasks.
	 */
	protected function after_plugin_theme_update( $update_results ) {
		$successful_updates = array();
		$failed_updates     = array();

		if ( ! empty( $update_results['plugin'] ) ) {
			/**
			 * Filters whether to send an email following an automatic background plugin update.
			 *
			 * @since 5.5.0
			 * @since 5.5.1 Added the `$update_results` parameter.
			 *
			 * @param bool  $enabled        True if plugin update notifications are enabled, false otherwise.
			 * @param array $update_results The results of plugins update tasks.
			 */
			$notifications_enabled = apply_filters( 'auto_plugin_update_send_email', true, $update_results['plugin'] );

			if ( $notifications_enabled ) {
				foreach ( $update_results['plugin'] as $update_result ) {
					if ( true === $update_result->result ) {
						$successful_updates['plugin'][] = $update_result;
					} else {
						$failed_updates['plugin'][] = $update_result;
					}
				}
			}
		}

		if ( ! empty( $update_results['theme'] ) ) {
			/**
			 * Filters whether to send an email following an automatic background theme update.
			 *
			 * @since 5.5.0
			 * @since 5.5.1 Added the `$update_results` parameter.
			 *
			 * @param bool  $enabled        True if theme update notifications are enabled, false otherwise.
			 * @param array $update_results The results of theme update tasks.
			 */
			$notifications_enabled = apply_filters( 'auto_theme_update_send_email', true, $update_results['theme'] );

			if ( $notifications_enabled ) {
				foreach ( $update_results['theme'] as $update_result ) {
					if ( true === $update_result->result ) {
						$successful_updates['theme'][] = $update_result;
					} else {
						$failed_updates['theme'][] = $update_result;
					}
				}
			}
		}

		if ( empty( $successful_updates ) && empty( $failed_updates ) ) {
			return;
		}

		if ( empty( $failed_updates ) ) {
			$this->send_plugin_theme_email( 'success', $successful_updates, $failed_updates );
		} elseif ( empty( $successful_updates ) ) {
			$this->send_plugin_theme_email( 'fail', $successful_updates, $failed_updates );
		} else {
			$this->send_plugin_theme_email( 'mixed', $successful_updates, $failed_updates );
		}
	}

	/**
	 * Sends an email upon the completion or failure of a plugin or theme background update.
	 *
	 * @since 5.5.0
	 *
	 * @param string $type               The type of email to send. Can be one of 'success', 'fail', 'mixed'.
	 * @param array  $successful_updates A list of updates that succeeded.
	 * @param array  $failed_updates     A list of updates that failed.
	 */
	protected function send_plugin_theme_email( $type, $successful_updates, $failed_updates ) {
		// No updates were attempted.
		if ( empty( $successful_updates ) && empty( $failed_updates ) ) {
			return;
		}

		$unique_failures     = false;
		$past_failure_emails = get_option( 'auto_plugin_theme_update_emails', array() );

		/*
		 * When only failures have occurred, an email should only be sent if there are unique failures.
		 * A failure is considered unique if an email has not been sent for an update attempt failure
		 * to a plugin or theme with the same new_version.
		 */
		if ( 'fail' === $type ) {
			foreach ( $failed_updates as $update_type => $failures ) {
				foreach ( $failures as $failed_update ) {
					if ( ! isset( $past_failure_emails[ $failed_update->item->{$update_type} ] ) ) {
						$unique_failures = true;
						continue;
					}

					// Check that the failure represents a new failure based on the new_version.
					if ( version_compare( $past_failure_emails[ $failed_update->item->{$update_type} ], $failed_update->item->new_version, '<' ) ) {
						$unique_failures = true;
					}
				}
			}

			if ( ! $unique_failures ) {
				return;
			}
		}

		$admin_user = get_user_by( 'email', get_site_option( 'admin_email' ) );

		if ( $admin_user ) {
			$switched_locale = switch_to_user_locale( $admin_user->ID );
		} else {
			$switched_locale = switch_to_locale( get_locale() );
		}

		$body               = array();
		$successful_plugins = ( ! empty( $successful_updates['plugin'] ) );
		$successful_themes  = ( ! empty( $successful_updates['theme'] ) );
		$failed_plugins     = ( ! empty( $failed_updates['plugin'] ) );
		$failed_themes      = ( ! empty( $failed_updates['theme'] ) );

		switch ( $type ) {
			case 'success':
				if ( $successful_plugins && $successful_themes ) {
					/* translators: %s: Site title. */
					$subject = __( '[%s] Some plugins and themes have automatically updated' );
					$body[]  = sprintf(
						/* translators: %s: Home URL. */
						__( 'Howdy! Some plugins and themes have automatically updated to their latest versions on your site at %s. No further action is needed on your part.' ),
						home_url()
					);
				} elseif ( $successful_plugins ) {
					/* translators: %s: Site title. */
					$subject = __( '[%s] Some plugins were automatically updated' );
					$body[]  = sprintf(
						/* translators: %s: Home URL. */
						__( 'Howdy! Some plugins have automatically updated to their latest versions on your site at %s. No further action is needed on your part.' ),
						home_url()
					);
				} else {
					/* translators: %s: Site title. */
					$subject = __( '[%s] Some themes were automatically updated' );
					$body[]  = sprintf(
						/* translators: %s: Home URL. */
						__( 'Howdy! Some themes have automatically updated to their latest versions on your site at %s. No further action is needed on your part.' ),
						home_url()
					);
				}

				break;
			case 'fail':
			case 'mixed':
				if ( $failed_plugins && $failed_themes ) {
					/* translators: %s: Site title. */
					$subject = __( '[%s] Some plugins and themes have failed to update' );
					$body[]  = sprintf(
						/* translators: %s: Home URL. */
						__( 'Howdy! Plugins and themes failed to update on your site at %s.' ),
						home_url()
					);
				} elseif ( $failed_plugins ) {
					/* translators: %s: Site title. */
					$subject = __( '[%s] Some plugins have failed to update' );
					$body[]  = sprintf(
						/* translators: %s: Home URL. */
						__( 'Howdy! Plugins failed to update on your site at %s.' ),
						home_url()
					);
				} else {
					/* translators: %s: Site title. */
					$subject = __( '[%s] Some themes have failed to update' );
					$body[]  = sprintf(
						/* translators: %s: Home URL. */
						__( 'Howdy! Themes failed to update on your site at %s.' ),
						home_url()
					);
				}

				break;
		}

		if ( in_array( $type, array( 'fail', 'mixed' ), true ) ) {
			$body[] = "\n";
			$body[] = __( 'Please check your site now. It’s possible that everything is working. If there are updates available, you should update.' );
			$body[] = "\n";

			// List failed plugin updates.
			if ( ! empty( $failed_updates['plugin'] ) ) {
				$body[] = __( 'The following plugins failed to update. If there was a fatal error in the update, the previously installed version has been restored.' );

				foreach ( $failed_updates['plugin'] as $item ) {
					$body_message = '';
					$item_url     = '';

					if ( ! empty( $item->item->url ) ) {
						$item_url = ' : ' . esc_url( $item->item->url );
					}

					if ( $item->item->current_version ) {
						$body_message .= sprintf(
							/* translators: 1: Plugin name, 2: Current version number, 3: New version number, 4: Plugin URL. */
							__( '- %1$s (from version %2$s to %3$s)%4$s' ),
							html_entity_decode( $item->name ),
							$item->item->current_version,
							$item->item->new_version,
							$item_url
						);
					} else {
						$body_message .= sprintf(
							/* translators: 1: Plugin name, 2: Version number, 3: Plugin URL. */
							__( '- %1$s version %2$s%3$s' ),
							html_entity_decode( $item->name ),
							$item->item->new_version,
							$item_url
						);
					}

					$body[] = $body_message;

					$past_failure_emails[ $item->item->plugin ] = $item->item->new_version;
				}

				$body[] = "\n";
			}

			// List failed theme updates.
			if ( ! empty( $failed_updates['theme'] ) ) {
				$body[] = __( 'These themes failed to update:' );

				foreach ( $failed_updates['theme'] as $item ) {
					if ( $item->item->current_version ) {
						$body[] = sprintf(
							/* translators: 1: Theme name, 2: Current version number, 3: New version number. */
							__( '- %1$s (from version %2$s to %3$s)' ),
							html_entity_decode( $item->name ),
							$item->item->current_version,
							$item->item->new_version
						);
					} else {
						$body[] = sprintf(
							/* translators: 1: Theme name, 2: Version number. */
							__( '- %1$s version %2$s' ),
							html_entity_decode( $item->name ),
							$item->item->new_version
						);
					}

					$past_failure_emails[ $item->item->theme ] = $item->item->new_version;
				}

				$body[] = "\n";
			}
		}

		// List successful updates.
		if ( in_array( $type, array( 'success', 'mixed' ), true ) ) {
			$body[] = "\n";

			// List successful plugin updates.
			if ( ! empty( $successful_updates['plugin'] ) ) {
				$body[] = __( 'These plugins are now up to date:' );

				foreach ( $successful_updates['plugin'] as $item ) {
					$body_message = '';
					$item_url     = '';

					if ( ! empty( $item->item->url ) ) {
						$item_url = ' : ' . esc_url( $item->item->url );
					}

					if ( $item->item->current_version ) {
						$body_message .= sprintf(
							/* translators: 1: Plugin name, 2: Current version number, 3: New version number, 4: Plugin URL. */
							__( '- %1$s (from version %2$s to %3$s)%4$s' ),
							html_entity_decode( $item->name ),
							$item->item->current_version,
							$item->item->new_version,
							$item_url
						);
					} else {
						$body_message .= sprintf(
							/* translators: 1: Plugin name, 2: Version number, 3: Plugin URL. */
							__( '- %1$s version %2$s%3$s' ),
							html_entity_decode( $item->name ),
							$item->item->new_version,
							$item_url
						);
					}
					$body[] = $body_message;

					unset( $past_failure_emails[ $item->item->plugin ] );
				}

				$body[] = "\n";
			}

			// List successful theme updates.
			if ( ! empty( $successful_updates['theme'] ) ) {
				$body[] = __( 'These themes are now up to date:' );

				foreach ( $successful_updates['theme'] as $item ) {
					if ( $item->item->current_version ) {
						$body[] = sprintf(
							/* translators: 1: Theme name, 2: Current version number, 3: New version number. */
							__( '- %1$s (from version %2$s to %3$s)' ),
							html_entity_decode( $item->name ),
							$item->item->current_version,
							$item->item->new_version
						);
					} else {
						$body[] = sprintf(
							/* translators: 1: Theme name, 2: Version number. */
							__( '- %1$s version %2$s' ),
							html_entity_decode( $item->name ),
							$item->item->new_version
						);
					}

					unset( $past_failure_emails[ $item->item->theme ] );
				}

				$body[] = "\n";
			}
		}

		if ( $failed_plugins ) {
			$body[] = sprintf(
				/* translators: %s: Plugins screen URL. */
				__( 'To manage plugins on your site, visit the Plugins page: %s' ),
				admin_url( 'plugins.php' )
			);
			$body[] = "\n";
		}

		if ( $failed_themes ) {
			$body[] = sprintf(
				/* translators: %s: Themes screen URL. */
				__( 'To manage themes on your site, visit the Themes page: %s' ),
				admin_url( 'themes.php' )
			);
			$body[] = "\n";
		}

		// Add a note about the support forums.
		$body[] = __( 'If you experience any issues or need support, the volunteers in the WordPress.org support forums may be able to help.' );
		$body[] = __( 'https://wordpress.org/support/forums/' );
		$body[] = "\n" . __( 'The WordPress Team' );

		if ( '' !== get_option( 'blogname' ) ) {
			$site_title = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		} else {
			$site_title = parse_url( home_url(), PHP_URL_HOST );
		}

		$body    = implode( "\n", $body );
		$to      = get_site_option( 'admin_email' );
		$subject = sprintf( $subject, $site_title );
		$headers = '';

		$email = compact( 'to', 'subject', 'body', 'headers' );

		/**
		 * Filters the email sent following an automatic background update for plugins and themes.
		 *
		 * @since 5.5.0
		 *
		 * @param array  $email {
		 *     Array of email arguments that will be passed to wp_mail().
		 *
		 *     @type string $to      The email recipient. An array of emails
		 *                           can be returned, as handled by wp_mail().
		 *     @type string $subject The email's subject.
		 *     @type string $body    The email message body.
		 *     @type string $headers Any email headers, defaults to no headers.
		 * }
		 * @param string $type               The type of email being sent. Can be one of 'success', 'fail', 'mixed'.
		 * @param array  $successful_updates A list of updates that succeeded.
		 * @param array  $failed_updates     A list of updates that failed.
		 */
		$email = apply_filters( 'auto_plugin_theme_update_email', $email, $type, $successful_updates, $failed_updates );

		$result = wp_mail( $email['to'], wp_specialchars_decode( $email['subject'] ), $email['body'], $email['headers'] );

		if ( $result ) {
			update_option( 'auto_plugin_theme_update_emails', $past_failure_emails );
		}

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Prepares and sends an email of a full log of background update results, useful for debugging and geekery.
	 *
	 * @since 3.7.0
	 */
	protected function send_debug_email() {
		$admin_user = get_user_by( 'email', get_site_option( 'admin_email' ) );

		if ( $admin_user ) {
			$switched_locale = switch_to_user_locale( $admin_user->ID );
		} else {
			$switched_locale = switch_to_locale( get_locale() );
		}

		$body     = array();
		$failures = 0;

		/* translators: %s: Network home URL. */
		$body[] = sprintf( __( 'WordPress site: %s' ), network_home_url( '/' ) );

		// Core.
		if ( isset( $this->update_results['core'] ) ) {
			$result = $this->update_results['core'][0];

			if ( $result->result && ! is_wp_error( $result->result ) ) {
				/* translators: %s: WordPress version. */
				$body[] = sprintf( __( 'SUCCESS: WordPress was successfully updated to %s' ), $result->name );
			} else {
				/* translators: %s: WordPress version. */
				$body[] = sprintf( __( 'FAILED: WordPress failed to update to %s' ), $result->name );
				++$failures;
			}

			$body[] = '';
		}

		// Plugins, Themes, Translations.
		foreach ( array( 'plugin', 'theme', 'translation' ) as $type ) {
			if ( ! isset( $this->update_results[ $type ] ) ) {
				continue;
			}

			$success_items = wp_list_filter( $this->update_results[ $type ], array( 'result' => true ) );

			if ( $success_items ) {
				$messages = array(
					'plugin'      => __( 'The following plugins were successfully updated:' ),
					'theme'       => __( 'The following themes were successfully updated:' ),
					'translation' => __( 'The following translations were successfully updated:' ),
				);

				$body[] = $messages[ $type ];
				foreach ( wp_list_pluck( $success_items, 'name' ) as $name ) {
					/* translators: %s: Name of plugin / theme / translation. */
					$body[] = ' * ' . sprintf( __( 'SUCCESS: %s' ), $name );
				}
			}

			if ( $success_items !== $this->update_results[ $type ] ) {
				// Failed updates.
				$messages = array(
					'plugin'      => __( 'The following plugins failed to update:' ),
					'theme'       => __( 'The following themes failed to update:' ),
					'translation' => __( 'The following translations failed to update:' ),
				);

				$body[] = $messages[ $type ];

				foreach ( $this->update_results[ $type ] as $item ) {
					if ( ! $item->result || is_wp_error( $item->result ) ) {
						/* translators: %s: Name of plugin / theme / translation. */
						$body[] = ' * ' . sprintf( __( 'FAILED: %s' ), $item->name );
						++$failures;
					}
				}
			}

			$body[] = '';
		}

		if ( '' !== get_bloginfo( 'name' ) ) {
			$site_title = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		} else {
			$site_title = parse_url( home_url(), PHP_URL_HOST );
		}

		if ( $failures ) {
			$body[] = trim(
				__(
					"BETA TESTING?
=============

This debugging email is sent when you are using a development version of WordPress.

If you think these failures might be due to a bug in WordPress, could you report it?
 * Open a thread in the support forums: https://wordpress.org/support/forum/alphabeta
 * Or, if you're comfortable writing a bug report: https://core.trac.wordpress.org/

Thanks! -- The WordPress Team"
				)
			);
			$body[] = '';

			/* translators: Background update failed notification email subject. %s: Site title. */
			$subject = sprintf( __( '[%s] Background Update Failed' ), $site_title );
		} else {
			/* translators: Background update finished notification email subject. %s: Site title. */
			$subject = sprintf( __( '[%s] Background Update Finished' ), $site_title );
		}

		$body[] = trim(
			__(
				'UPDATE LOG
=========='
			)
		);
		$body[] = '';

		foreach ( array( 'core', 'plugin', 'theme', 'translation' ) as $type ) {
			if ( ! isset( $this->update_results[ $type ] ) ) {
				continue;
			}

			foreach ( $this->update_results[ $type ] as $update ) {
				$body[] = $update->name;
				$body[] = str_repeat( '-', strlen( $update->name ) );

				foreach ( $update->messages as $message ) {
					$body[] = '  ' . html_entity_decode( str_replace( '&#8230;', '...', $message ) );
				}

				if ( is_wp_error( $update->result ) ) {
					$results = array( 'update' => $update->result );

					// If we rolled back, we want to know an error that occurred then too.
					if ( 'rollback_was_required' === $update->result->get_error_code() ) {
						$results = (array) $update->result->get_error_data();
					}

					foreach ( $results as $result_type => $result ) {
						if ( ! is_wp_error( $result ) ) {
							continue;
						}

						if ( 'rollback' === $result_type ) {
							/* translators: 1: Error code, 2: Error message. */
							$body[] = '  ' . sprintf( __( 'Rollback Error: [%1$s] %2$s' ), $result->get_error_code(), $result->get_error_message() );
						} else {
							/* translators: 1: Error code, 2: Error message. */
							$body[] = '  ' . sprintf( __( 'Error: [%1$s] %2$s' ), $result->get_error_code(), $result->get_error_message() );
						}

						if ( $result->get_error_data() ) {
							$body[] = '         ' . implode( ', ', (array) $result->get_error_data() );
						}
					}
				}

				$body[] = '';
			}
		}

		$email = array(
			'to'      => get_site_option( 'admin_email' ),
			'subject' => $subject,
			'body'    => implode( "\n", $body ),
			'headers' => '',
		);

		/**
		 * Filters the debug email that can be sent following an automatic
		 * background core update.
		 *
		 * @since 3.8.0
		 *
		 * @param array $email {
		 *     Array of email arguments that will be passed to wp_mail().
		 *
		 *     @type string $to      The email recipient. An array of emails
		 *                           can be returned, as handled by wp_mail().
		 *     @type string $subject Email subject.
		 *     @type string $body    Email message body.
		 *     @type string $headers Any email headers. Default empty.
		 * }
		 * @param int   $failures The number of failures encountered while upgrading.
		 * @param mixed $results  The results of all attempted updates.
		 */
		$email = apply_filters( 'automatic_updates_debug_email', $email, $failures, $this->update_results );

		wp_mail( $email['to'], wp_specialchars_decode( $email['subject'] ), $email['body'], $email['headers'] );

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Performs a loopback request to check for potential fatal errors.
	 *
	 * Fatal errors cannot be detected unless maintenance mode is enabled.
	 *
	 * @since 6.6.0
	 *
	 * @global int $upgrading The Unix timestamp marking when upgrading WordPress began.
	 *
	 * @return bool Whether a fatal error was detected.
	 */
	protected function has_fatal_error() {
		global $upgrading;

		$maintenance_file = ABSPATH . '.maintenance';
		if ( ! file_exists( $maintenance_file ) ) {
			return false;
		}

		require $maintenance_file;
		if ( ! is_int( $upgrading ) ) {
			return false;
		}

		$scrape_key   = md5( $upgrading );
		$scrape_nonce = (string) $upgrading;
		$transient    = 'scrape_key_' . $scrape_key;
		set_transient( $transient, $scrape_nonce, 30 );

		$cookies       = wp_unslash( $_COOKIE );
		$scrape_params = array(
			'wp_scrape_key'   => $scrape_key,
			'wp_scrape_nonce' => $scrape_nonce,
		);
		$headers       = array(
			'Cache-Control' => 'no-cache',
		);

		/** This filter is documented in wp-includes/class-wp-http-streams.php */
		$sslverify = apply_filters( 'https_local_ssl_verify', false );

		// Include Basic auth in the loopback request.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		// Time to wait for loopback request to finish.
		$timeout = 50; // 50 seconds.

		$is_debug = WP_DEBUG && WP_DEBUG_LOG;
		if ( $is_debug ) {
			error_log( '    Scraping home page...' );
		}

		$needle_start = "###### wp_scraping_result_start:$scrape_key ######";
		$needle_end   = "###### wp_scraping_result_end:$scrape_key ######";
		$url          = add_query_arg( $scrape_params, home_url( '/' ) );
		$response     = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout', 'sslverify' ) );

		if ( is_wp_error( $response ) ) {
			if ( $is_debug ) {
				error_log( 'Loopback request failed: ' . $response->get_error_message() );
			}
			return true;
		}

		// If this outputs `true` in the log, it means there were no fatal errors detected.
		if ( $is_debug ) {
			error_log( var_export( substr( $response['body'], strpos( $response['body'], '###### wp_scraping_result_start:' ) ), true ) );
		}

		$body                   = wp_remote_retrieve_body( $response );
		$scrape_result_position = strpos( $body, $needle_start );
		$result                 = null;

		if ( false !== $scrape_result_position ) {
			$error_output = substr( $body, $scrape_result_position + strlen( $needle_start ) );
			$error_output = substr( $error_output, 0, strpos( $error_output, $needle_end ) );
			$result       = json_decode( trim( $error_output ), true );
		}

		delete_transient( $transient );

		// Only fatal errors will result in a 'type' key.
		return isset( $result['type'] );
	}
}
