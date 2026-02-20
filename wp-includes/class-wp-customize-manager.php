<?php
/**
 * Các lớp Trình quản lý Tùy biến WordPress
 *
 * @package WordPress
 * @subpackage Customize
 * @since 3.4.0
 */

/**
 * Lớp Trình quản lý Tùy biến.
 *
 * Khởi tạo trải nghiệm Tùy biến ở phía máy chủ.
 *
 * Thiết lập quy trình chuyển đổi giao diện nếu một giao diện khác
 * giao diện đang hoạt động đang được xem trước và tùy biến.
 *
 * Đóng vai trò như factory cho các Control và Setting Tùy biến, và
 * khởi tạo các Control và Setting Tùy biến mặc định.
 *
 * @since 3.4.0
 */
#[AllowDynamicProperties]
final class WP_Customize_Manager {
	/**
	 * Một thể hiện của giao diện đang được xem trước.
	 *
	 * @since 3.4.0
	 * @var WP_Theme
	 */
	protected $theme;

	/**
	 * Tên thư mục của giao diện đã hoạt động trước đó (trong theme_root).
	 *
	 * @since 3.4.0
	 * @var string
	 */
	protected $original_stylesheet;

	/**
	 * Có phải đây là lần tải trang Trình tùy biến hay không.
	 *
	 * @since 3.4.0
	 * @var bool
	 */
	protected $previewing = false;

	/**
	 * Các phương thức và thuộc tính xử lý việc quản lý widget trong Trình tùy biến.
	 *
	 * @since 3.9.0
	 * @var WP_Customize_Widgets
	 */
	public $widgets;

	/**
	 * Các phương thức và thuộc tính xử lý việc quản lý menu điều hướng trong Trình tùy biến.
	 *
	 * @since 4.3.0
	 * @var WP_Customize_Nav_Menus
	 */
	public $nav_menus;

	/**
	 * Các phương thức và thuộc tính xử lý việc làm mới có chọn lọc trong bản xem trước Trình tùy biến.
	 *
	 * @since 4.5.0
	 * @var WP_Customize_Selective_Refresh
	 */
	public $selective_refresh;

	/**
	 * Các thể hiện đã đăng ký của WP_Customize_Setting.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Các thể hiện cấp cao nhất đã được sắp xếp của WP_Customize_Panel và WP_Customize_Section.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	protected $containers = array();

	/**
	 * Các thể hiện đã đăng ký của WP_Customize_Panel.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	protected $panels = array();

	/**
	 * Danh sách các thành phần lõi.
	 *
	 * @since 4.5.0
	 * @var array
	 */
	protected $components = array( 'nav_menus' );

	/**
	 * Các thể hiện đã đăng ký của WP_Customize_Section.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	protected $sections = array();

	/**
	 * Các thể hiện đã đăng ký của WP_Customize_Control.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	protected $controls = array();

	/**
	 * Các loại panel có thể được render từ mẫu JS.
	 *
	 * @since 4.3.0
	 * @var array
	 */
	protected $registered_panel_types = array();

	/**
	 * Các loại section có thể được render từ mẫu JS.
	 *
	 * @since 4.3.0
	 * @var array
	 */
	protected $registered_section_types = array();

	/**
	 * Các loại control có thể được render từ mẫu JS.
	 *
	 * @since 4.1.0
	 * @var array
	 */
	protected $registered_control_types = array();

	/**
	 * URL ban đầu đang được xem trước.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $preview_url;

	/**
	 * URL để dẫn người dùng đến khi đóng Trình tùy biến.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $return_url;

	/**
	 * Ánh xạ từ 'panel', 'section', 'control' tới ID cần được tự động lấy nét.
	 *
	 * @since 4.4.0
	 * @var string[]
	 */
	protected $autofocus = array();

	/**
	 * Kênh truyền thông điệp.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $messenger_channel;

	/**
	 * Có nên tải bản sửa đổi tự động lưu của changeset hay không.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	protected $autosaved = false;

	/**
	 * Có cho phép phân nhánh changeset hay không.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	protected $branching = true;

	/**
	 * Có nên xem trước các cài đặt hay không.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	protected $settings_previewed = true;

	/**
	 * Có phải một changeset nội dung khởi đầu đã được lưu hay không.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	protected $saved_starter_content_changeset = false;

	/**
	 * Các giá trị chưa được làm sạch cho Cài đặt Tùy biến được phân tích từ $_POST['customized'].
	 *
	 * @var array
	 */
	private $_post_values;

	/**
	 * UUID của changeset.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	private $_changeset_uuid;

	/**
	 * ID bài viết changeset.
	 *
	 * @since 4.7.0
	 * @var int|false
	 */
	private $_changeset_post_id;

	/**
	 * Dữ liệu changeset được tải từ bài viết customize_changeset.
	 *
	 * @since 4.7.0
	 * @var array|null
	 */
	private $_changeset_data;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 3.4.0
	 * @since 4.7.0 Thêm tham số `$args`.
	 *
	 * @param array $args {
	 *     Các tham số.
	 *
	 *     @type null|string|false $changeset_uuid     UUID của changeset, là `post_name` cho bài viết customize_changeset chứa trạng thái đã tùy biến.
	 *                                                 Mặc định là `null` dẫn đến UUID được tạo ngay lập tức. Nếu cung cấp `false`, thì
	 *                                                 UUID changeset sẽ được xác định trong `after_setup_theme`: khi bộ lọc
	 *                                                 `customize_changeset_branching` trả về false, thì UUID mặc định sẽ là
	 *                                                 của bài viết `customize_changeset` gần nhất có trạng thái khác 'auto-draft',
	 *                                                 'publish', hoặc 'trash'. Ngược lại, nếu phân nhánh changeset được bật, thì UUID ngẫu nhiên sẽ được sử dụng.
	 *     @type string            $theme              Giao diện để xem trước (cho chuyển đổi giao diện). Mặc định là tham số truy vấn customize_theme hoặc theme.
	 *     @type string            $messenger_channel  Kênh truyền thông điệp. Mặc định là tham số truy vấn customize_messenger_channel.
	 *     @type bool              $settings_previewed Có nên xem trước các cài đặt hay không. Mặc định là true.
	 *     @type bool              $branching          Có cho phép phân nhánh changeset hay không; nếu không, các changeset là tuyến tính. Mặc định là true.
	 *     @type bool              $autosaved          Có nên tải dữ liệu từ bản sửa đổi tự động lưu của changeset nếu có hay không. Mặc định là false.
	 * }
	 */
	public function __construct( $args = array() ) {

		$args = array_merge(
			array_fill_keys( array( 'changeset_uuid', 'theme', 'messenger_channel', 'settings_previewed', 'autosaved', 'branching' ), null ),
			$args
		);

		// Lưu ý rằng định dạng UUID sẽ được xác thực trong phương thức setup_theme().
		if ( ! isset( $args['changeset_uuid'] ) ) {
			$args['changeset_uuid'] = wp_generate_uuid4();
		}

		/*
		 * Giao diện và messenger_channel nên được cung cấp qua $args,
		 * nhưng chúng cũng được kiểm tra trong biến toàn cục $_REQUEST ở đây để tương thích ngược.
		 */
		if ( ! isset( $args['theme'] ) ) {
			if ( isset( $_REQUEST['customize_theme'] ) ) {
				$args['theme'] = wp_unslash( $_REQUEST['customize_theme'] );
			} elseif ( isset( $_REQUEST['theme'] ) ) { // Không còn dùng.
				$args['theme'] = wp_unslash( $_REQUEST['theme'] );
			}
		}
		if ( ! isset( $args['messenger_channel'] ) && isset( $_REQUEST['customize_messenger_channel'] ) ) {
			$args['messenger_channel'] = sanitize_key( wp_unslash( $_REQUEST['customize_messenger_channel'] ) );
		}

		// Không tải thành phần 'widgets' nếu giao diện khối được kích hoạt.
		if ( ! wp_is_block_theme() ) {
			$this->components[] = 'widgets';
		}

		$this->original_stylesheet = get_stylesheet();
		$this->theme               = wp_get_theme( 0 === validate_file( $args['theme'] ) ? $args['theme'] : null );
		$this->messenger_channel   = $args['messenger_channel'];
		$this->_changeset_uuid     = $args['changeset_uuid'];

		foreach ( array( 'settings_previewed', 'autosaved', 'branching' ) as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$this->$key = (bool) $args[ $key ];
			}
		}

		require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';
		require_once ABSPATH . WPINC . '/class-wp-customize-panel.php';
		require_once ABSPATH . WPINC . '/class-wp-customize-section.php';
		require_once ABSPATH . WPINC . '/class-wp-customize-control.php';

		require_once ABSPATH . WPINC . '/customize/class-wp-customize-color-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-media-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-upload-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-image-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-background-image-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-background-position-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-cropped-image-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-site-icon-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-header-image-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-theme-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-code-editor-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-widget-area-customize-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-widget-form-customize-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-item-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-location-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-name-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-locations-control.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-auto-add-control.php';

		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menus-panel.php';

		require_once ABSPATH . WPINC . '/customize/class-wp-customize-themes-panel.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-themes-section.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-sidebar-section.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-section.php';

		require_once ABSPATH . WPINC . '/customize/class-wp-customize-custom-css-setting.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-filter-setting.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-header-image-setting.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-background-image-setting.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-item-setting.php';
		require_once ABSPATH . WPINC . '/customize/class-wp-customize-nav-menu-setting.php';

		/**
		 * Lọc các thành phần lõi của Trình tùy biến cần tải.
		 *
		 * Điều này cho phép loại trừ các thành phần lõi khỏi việc khởi tạo bằng
		 * cách lọc chúng ra khỏi mảng. Lưu ý rằng bộ lọc này thường chạy
		 * trong hành động {@see 'plugins_loaded'}, nên không thể thêm
		 * trong giao diện.
		 *
		 * @since 4.4.0
		 *
		 * @see WP_Customize_Manager::__construct()
		 *
		 * @param string[]             $components Mảng các thành phần lõi cần tải.
		 * @param WP_Customize_Manager $manager    Thể hiện WP_Customize_Manager.
		 */
		$components = apply_filters( 'customize_loaded_components', $this->components, $this );

		require_once ABSPATH . WPINC . '/customize/class-wp-customize-selective-refresh.php';
		$this->selective_refresh = new WP_Customize_Selective_Refresh( $this );

		if ( in_array( 'widgets', $components, true ) ) {
			require_once ABSPATH . WPINC . '/class-wp-customize-widgets.php';
			$this->widgets = new WP_Customize_Widgets( $this );
		}

		if ( in_array( 'nav_menus', $components, true ) ) {
			require_once ABSPATH . WPINC . '/class-wp-customize-nav-menus.php';
			$this->nav_menus = new WP_Customize_Nav_Menus( $this );
		}

		add_action( 'setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );

		// Không sinh tiến trình cron (đặc biệt là cron thay thế) khi đang chạy Trình tùy biến.
		remove_action( 'init', 'wp_cron' );

		// Không chạy kiểm tra cập nhật khi render các control.
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );

		add_action( 'wp_ajax_customize_save', array( $this, 'save' ) );
		add_action( 'wp_ajax_customize_trash', array( $this, 'handle_changeset_trash_request' ) );
		add_action( 'wp_ajax_customize_refresh_nonces', array( $this, 'refresh_nonces' ) );
		add_action( 'wp_ajax_customize_load_themes', array( $this, 'handle_load_themes_request' ) );
		add_filter( 'heartbeat_settings', array( $this, 'add_customize_screen_to_heartbeat_settings' ) );
		add_filter( 'heartbeat_received', array( $this, 'check_changeset_lock_with_heartbeat' ), 10, 3 );
		add_action( 'wp_ajax_customize_override_changeset_lock', array( $this, 'handle_override_changeset_lock_request' ) );
		add_action( 'wp_ajax_customize_dismiss_autosave_or_lock', array( $this, 'handle_dismiss_autosave_or_lock_request' ) );

		add_action( 'customize_register', array( $this, 'register_controls' ) );
		add_action( 'customize_register', array( $this, 'register_dynamic_settings' ), 11 ); // Cho phép mã nguồn tạo cài đặt trước.
		add_action( 'customize_controls_init', array( $this, 'prepare_controls' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_control_scripts' ) );

		// Render các mẫu Common, Panel, Section, và Control.
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'render_panel_templates' ), 1 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'render_section_templates' ), 1 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'render_control_templates' ), 1 );

		// Xuất cài đặt video header cùng với phản hồi từng phần.
		add_filter( 'customize_render_partials_response', array( $this, 'export_header_video_settings' ), 10, 3 );

		// Xuất các cài đặt sang JS thông qua biến _wpCustomizeSettings.
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_pane_settings' ), 1000 );

		// Thêm thông báo cập nhật giao diện.
		if ( current_user_can( 'install_themes' ) || current_user_can( 'update_themes' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
			add_action( 'customize_controls_print_footer_scripts', 'wp_print_admin_notice_templates' );
		}
	}

	/**
	 * Trả về true nếu đây là yêu cầu Ajax.
	 *
	 * @since 3.4.0
	 * @since 4.2.0 Thêm tham số `$action`.
	 *
	 * @param string|null $action Hành động Ajax được cung cấp có đang chạy hay không.
	 * @return bool True nếu là yêu cầu Ajax, false nếu không.
	 */
	public function doing_ajax( $action = null ) {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		if ( ! $action ) {
			return true;
		} else {
			/*
			 * Lưu ý: chúng ta không thể chỉ dùng doing_action( "wp_ajax_{$action}" ) vì cần
			 * kiểm tra trước khi admin-ajax.php đến được điểm đó.
			 */
			return isset( $_REQUEST['action'] ) && wp_unslash( $_REQUEST['action'] ) === $action;
		}
	}

	/**
	 * Hàm bọc wp_die tùy chỉnh. Trả về thông báo tiêu chuẩn cho giao diện
	 * hoặc thông báo Ajax.
	 *
	 * @since 3.4.0
	 *
	 * @param string|WP_Error $ajax_message Giá trị trả về Ajax.
	 * @param string          $message      Tùy chọn. Thông báo giao diện người dùng.
	 */
	protected function wp_die( $ajax_message, $message = null ) {
		if ( $this->doing_ajax() ) {
			wp_die( $ajax_message );
		}

		if ( ! $message ) {
			$message = __( 'An error occurred while customizing. Please refresh the page and try again.' );
		}

		if ( $this->messenger_channel ) {
			ob_start();
			wp_enqueue_scripts();
			wp_print_scripts( array( 'customize-base' ) );

			$settings = array(
				'messengerArgs' => array(
					'channel' => $this->messenger_channel,
					'url'     => wp_customize_url(),
				),
				'error'         => $ajax_message,
			);
			$message .= ob_get_clean();
			ob_start();
			?>
			<script>
			( function( api, settings ) {
				var preview = new api.Messenger( settings.messengerArgs );
				preview.send( 'iframe-loading-error', settings.error );
			} )( wp.customize, <?php echo wp_json_encode( $settings ); ?> );
			</script>
			<?php
			$message .= wp_get_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
		}

		wp_die( $message );
	}

	/**
	 * Trả về trình xử lý wp_die() Ajax nếu đây là yêu cầu tùy biến.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0
	 *
	 * @return callable Trình xử lý die.
	 */
	public function wp_die_handler() {
		_deprecated_function( __METHOD__, '4.7.0' );

		if ( $this->doing_ajax() || isset( $_POST['customized'] ) ) {
			return '_ajax_wp_die_handler';
		}

		return '_default_wp_die_handler';
	}

	/**
	 * Bắt đầu xem trước và tùy biến giao diện.
	 *
	 * Kiểm tra xem biến truy vấn customize có tồn tại không. Khởi tạo bộ lọc để lọc giao diện đang hoạt động.
	 *
	 * @since 3.4.0
	 *
	 * @global string $pagenow Tên tệp của màn hình hiện tại.
	 */
	public function setup_theme() {
		global $pagenow;

		// Kiểm tra quyền truy cập customize.php vì phương thức này được gọi trước khi customize.php có thể chạy bất kỳ mã nào.
		if ( 'customize.php' === $pagenow && ! current_user_can( 'customize' ) ) {
			if ( ! is_user_logged_in() ) {
				auth_redirect();
			} else {
				wp_die(
					'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to customize this site.' ) . '</p>',
					403
				);
			}
			return;
		}

		// Nếu changeset được cung cấp không hợp lệ.
		if ( isset( $this->_changeset_uuid ) && false !== $this->_changeset_uuid && ! wp_is_uuid( $this->_changeset_uuid ) ) {
			$this->wp_die( -1, __( 'Invalid changeset UUID' ) );
		}

		/*
		 * Xóa dữ liệu post đến nếu người dùng thiếu token CSRF (nonce). Lưu ý rằng ứng dụng
		 * tùy biến sẽ chèn tham số truy vấn customize_preview_nonce vào tất cả các yêu cầu Ajax.
		 * Đối với hành vi tương tự ở nơi khác trong WordPress, xem rest_cookie_check_errors() sẽ đăng xuất
		 * người dùng khi không có nonce hợp lệ.
		 */
		$has_post_data_nonce = (
			check_ajax_referer( 'preview-customize_' . $this->get_stylesheet(), 'nonce', false )
			||
			check_ajax_referer( 'save-customize_' . $this->get_stylesheet(), 'nonce', false )
			||
			check_ajax_referer( 'preview-customize_' . $this->get_stylesheet(), 'customize_preview_nonce', false )
		);
		if ( ! current_user_can( 'customize' ) || ! $has_post_data_nonce ) {
			unset( $_POST['customized'] );
			unset( $_REQUEST['customized'] );
		}

		/*
		 * Nếu chưa xác thực thì yêu cầu UUID changeset hợp lệ để tải bản xem trước.
		 * Theo cách này, UUID đóng vai trò như khóa bí mật. Nếu kênh truyền thông điệp có mặt,
		 * thì gửi mã chưa xác thực để yêu cầu xác thực lại.
		 */
		if ( ! current_user_can( 'customize' ) && ! $this->changeset_post_id() ) {
			$this->wp_die( $this->messenger_channel ? 0 : -1, __( 'Non-existent changeset UUID.' ) );
		}

		if ( ! headers_sent() ) {
			send_origin_headers();
		}

		// Ẩn thanh quản trị nếu chúng ta đang nhúng trong iframe tùy biến.
		if ( $this->messenger_channel ) {
			show_admin_bar( false );
		}

		if ( $this->is_theme_active() ) {
			// Khi giao diện được tải xong, chúng ta sẽ xác thực nó.
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
		} else {
			/*
			 * Nếu giao diện được yêu cầu không phải là giao diện đang hoạt động và người dùng không có
			 * quyền switch_themes, thoát.
			 */
			if ( ! current_user_can( 'switch_themes' ) ) {
				$this->wp_die( -1, __( 'Sorry, you are not allowed to edit theme options on this site.' ) );
			}

			// Nếu giao diện có lỗi khi tải, thoát.
			if ( $this->theme()->errors() ) {
				$this->wp_die( -1, $this->theme()->errors()->get_error_message() );
			}

			// Nếu giao diện không được phép theo cài đặt multisite, thoát.
			if ( ! $this->theme()->is_allowed() ) {
				$this->wp_die( -1, __( 'The requested theme does not exist.' ) );
			}
		}

		// Đảm bảo UUID changeset được thiết lập ngay sau khi giao diện được tải.
		add_action( 'after_setup_theme', array( $this, 'establish_loaded_changeset' ), 5 );

		/*
		 * Nhập nội dung khởi đầu của giao diện cho các cài đặt mới khi vào trình tùy biến.
		 * Nhập nội dung khởi đầu tại after_setup_theme:100 để bất kỳ
		 * lệnh gọi add_theme_support( 'starter-content' ) nào đã được thực hiện.
		 */
		if ( get_option( 'fresh_site' ) && 'customize.php' === $pagenow ) {
			add_action( 'after_setup_theme', array( $this, 'import_theme_starter_content' ), 100 );
		}

		$this->start_previewing_theme();
	}

	/**
	 * Thiết lập changeset đã tải.
	 *
	 * Phương thức này chạy ngay tại after_setup_theme và áp dụng bộ lọc 'customize_changeset_branching' để xác định
	 * xem các changeset đồng thời có được phép hay không. Sau đó nếu Trình tùy biến không được khởi tạo với tham số `changeset_uuid`,
	 * phương thức này sẽ xác định UUID nào nên được sử dụng. Nếu phân nhánh changeset bị tắt, thì changeset được lưu gần nhất
	 * sẽ được tải theo mặc định. Ngược lại, nếu không có changeset đã lưu nào hoặc nếu phân nhánh changeset
	 * được bật, thì UUID mới sẽ được tạo.
	 *
	 * @since 4.9.0
	 *
	 * @global string $pagenow Tên tệp của màn hình hiện tại.
	 */
	public function establish_loaded_changeset() {
		global $pagenow;

		if ( empty( $this->_changeset_uuid ) ) {
			$changeset_uuid = null;

			if ( ! $this->branching() && $this->is_theme_active() ) {
				$unpublished_changeset_posts = $this->get_changeset_posts(
					array(
						'post_status'               => array_diff( get_post_stati(), array( 'auto-draft', 'publish', 'trash', 'inherit', 'private' ) ),
						'exclude_restore_dismissed' => false,
						'author'                    => 'any',
						'posts_per_page'            => 1,
						'order'                     => 'DESC',
						'orderby'                   => 'date',
					)
				);
				$unpublished_changeset_post  = array_shift( $unpublished_changeset_posts );
				if ( ! empty( $unpublished_changeset_post ) && wp_is_uuid( $unpublished_changeset_post->post_name ) ) {
					$changeset_uuid = $unpublished_changeset_post->post_name;
				}
			}

			// Nếu chưa có UUID changeset nào được thiết lập, thì tạo một UUID mới.
			if ( empty( $changeset_uuid ) ) {
				$changeset_uuid = wp_generate_uuid4();
			}

			$this->_changeset_uuid = $changeset_uuid;
		}

		if ( is_admin() && 'customize.php' === $pagenow ) {
			$this->set_changeset_lock( $this->changeset_post_id() );
		}
	}

	/**
	 * Callback để xác thực giao diện khi nó đã được tải
	 *
	 * @since 3.4.0
	 */
	public function after_setup_theme() {
		$doing_ajax_or_is_customized = ( $this->doing_ajax() || isset( $_POST['customized'] ) );
		if ( ! $doing_ajax_or_is_customized && ! validate_current_theme() ) {
			wp_redirect( 'themes.php?broken=true' );
			exit;
		}
	}

	/**
	 * Nếu giao diện cần xem trước không phải là giao diện đang hoạt động, thêm các callback bộ lọc
	 * để thay thế nó khi chạy.
	 *
	 * @since 3.4.0
	 */
	public function start_previewing_theme() {
		// Thoát nếu đã đang xem trước.
		if ( $this->is_preview() ) {
			return;
		}

		$this->previewing = true;

		if ( ! $this->is_theme_active() ) {
			add_filter( 'template', array( $this, 'get_template' ) );
			add_filter( 'stylesheet', array( $this, 'get_stylesheet' ) );
			add_filter( 'pre_option_current_theme', array( $this, 'current_theme' ) );

			// @link: https://core.trac.wordpress.org/ticket/20027
			add_filter( 'pre_option_stylesheet', array( $this, 'get_stylesheet' ) );
			add_filter( 'pre_option_template', array( $this, 'get_template' ) );

			// Xử lý thư mục gốc giao diện tùy chỉnh.
			add_filter( 'pre_option_stylesheet_root', array( $this, 'get_stylesheet_root' ) );
			add_filter( 'pre_option_template_root', array( $this, 'get_template_root' ) );
		}

		/**
		 * Kích hoạt khi bản xem trước giao diện Trình tùy biến đã bắt đầu.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Manager $manager Thể hiện WP_Customize_Manager.
		 */
		do_action( 'start_previewing_theme', $this );
	}

	/**
	 * Dừng xem trước giao diện đã chọn.
	 *
	 * Gỡ bỏ các bộ lọc thay đổi giao diện đang hoạt động.
	 *
	 * @since 3.4.0
	 */
	public function stop_previewing_theme() {
		if ( ! $this->is_preview() ) {
			return;
		}

		$this->previewing = false;

		if ( ! $this->is_theme_active() ) {
			remove_filter( 'template', array( $this, 'get_template' ) );
			remove_filter( 'stylesheet', array( $this, 'get_stylesheet' ) );
			remove_filter( 'pre_option_current_theme', array( $this, 'current_theme' ) );

			// @link: https://core.trac.wordpress.org/ticket/20027
			remove_filter( 'pre_option_stylesheet', array( $this, 'get_stylesheet' ) );
			remove_filter( 'pre_option_template', array( $this, 'get_template' ) );

			// Xử lý thư mục gốc giao diện tùy chỉnh.
			remove_filter( 'pre_option_stylesheet_root', array( $this, 'get_stylesheet_root' ) );
			remove_filter( 'pre_option_template_root', array( $this, 'get_template_root' ) );
		}

		/**
		 * Kích hoạt khi bản xem trước giao diện Trình tùy biến đã dừng.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Manager $manager Thể hiện WP_Customize_Manager.
		 */
		do_action( 'stop_previewing_theme', $this );
	}

	/**
	 * Lấy trạng thái xem các cài đặt có đang hoặc sẽ được xem trước hay không.
	 *
	 * @since 4.9.0
	 *
	 * @see WP_Customize_Setting::preview()
	 *
	 * @return bool
	 */
	public function settings_previewed() {
		return $this->settings_previewed;
	}

	/**
	 * Lấy trạng thái xem dữ liệu từ bản sửa đổi tự động lưu của changeset có nên được tải hay không nếu nó tồn tại.
	 *
	 * @since 4.9.0
	 *
	 * @see WP_Customize_Manager::changeset_data()
	 *
	 * @return bool Có đang sử dụng bản sửa đổi changeset tự động lưu hay không.
	 */
	public function autosaved() {
		return $this->autosaved;
	}

	/**
	 * Xem phân nhánh changeset có được phép hay không.
	 *
	 * @since 4.9.0
	 *
	 * @see WP_Customize_Manager::establish_loaded_changeset()
	 *
	 * @return bool Có phải đang phân nhánh changeset hay không.
	 */
	public function branching() {

		/**
		 * Lọc xem phân nhánh changeset có được phép hay không. / Filters whether or not changeset branching is allowed.
		 *
		 * Theo mặc định trong lõi, khi phân nhánh changeset không được phép, các changeset sẽ hoạt động
		 * tuyến tính trong đó chỉ có một changeset đã lưu tồn tại tại một thời điểm (với trạng thái 'draft' hoặc
		 * 'future'). Điều này làm cho Trình tùy biến hoạt động theo cách tương tự như đi đến
		 * "chỉnh sửa" một bài viết hiện có: tất cả người dùng sẽ thay đổi cùng một bài viết, và bản sửa đổi
		 * tự động lưu sẽ được tạo cho bài viết đó.
		 *
		 * Ngược lại, khi phân nhánh changeset được phép, thì mô hình giống như người dùng đi đến
		 * "thêm mới" cho một trang và mỗi người dùng thay đổi độc lập với nhau vì
		 * họ đều đang hoạt động trên các trang riêng biệt của mình, mỗi người nhận được
		 * bản nháp tự động ban đầu riêng và sau khi được lưu lần đầu, các bản sửa đổi tự động lưu trên đầu
		 * bài viết cụ thể của người dùng đó.
		 *
		 * Vì các changeset tuyến tính được coi là phù hợp hơn cho đa số người dùng WordPress,
		 * chúng là mặc định. Đối với các trang WordPress có quản lý trang web nặng trong Trình tùy biến
		 * bởi nhiều người dùng thì nên bật phân nhánh changeset bằng bộ lọc này.
		 *
		 * @since 4.9.0
		 *
		 * @param bool                 $allow_branching Có cho phép phân nhánh hay không. Nếu `false`, mặc định,
		 *                                              thì chỉ có một changeset đã lưu tồn tại tại một thời điểm.
		 * @param WP_Customize_Manager $wp_customize    Manager instance.
		 */
		$this->branching = apply_filters( 'customize_changeset_branching', $this->branching, $this );

		return $this->branching;
	}

	/**
	 * Lấy UUID của changeset.
	 *
	 * @since 4.7.0
	 *
	 * @see WP_Customize_Manager::establish_loaded_changeset()
	 *
	 * @return string UUID.
	 */
	public function changeset_uuid() {
		if ( empty( $this->_changeset_uuid ) ) {
			$this->establish_loaded_changeset();
		}
		return $this->_changeset_uuid;
	}

	/**
	 * Lấy giao diện đang được tùy biến.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Theme
	 */
	public function theme() {
		if ( ! $this->theme ) {
			$this->theme = wp_get_theme();
		}
		return $this->theme;
	}

	/**
	 * Lấy các cài đặt đã đăng ký.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function settings() {
		return $this->settings;
	}

	/**
	 * Lấy các control đã đăng ký.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function controls() {
		return $this->controls;
	}

	/**
	 * Lấy các container đã đăng ký.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function containers() {
		return $this->containers;
	}

	/**
	 * Lấy các section đã đăng ký.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function sections() {
		return $this->sections;
	}

	/**
	 * Lấy các panel đã đăng ký.
	 *
	 * @since 4.0.0
	 *
	 * @return array Các panel.
	 */
	public function panels() {
		return $this->panels;
	}

	/**
	 * Kiểm tra xem giao diện hiện tại có đang hoạt động hay không.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_theme_active() {
		return $this->get_stylesheet() === $this->original_stylesheet;
	}

	/**
	 * Đăng ký styles/scripts và khởi tạo bản xem trước của mỗi cài đặt
	 *
	 * @since 3.4.0
	 */
	public function wp_loaded() {

		/*
		 * Đăng ký vô điều kiện các loại lõi cho panel, section, và control
		 * trong trường hợp plugin gỡ bỏ tất cả các hành động customize_register.
		 */
		$this->register_panel_type( 'WP_Customize_Panel' );
		$this->register_panel_type( 'WP_Customize_Themes_Panel' );
		$this->register_section_type( 'WP_Customize_Section' );
		$this->register_section_type( 'WP_Customize_Sidebar_Section' );
		$this->register_section_type( 'WP_Customize_Themes_Section' );
		$this->register_control_type( 'WP_Customize_Color_Control' );
		$this->register_control_type( 'WP_Customize_Media_Control' );
		$this->register_control_type( 'WP_Customize_Upload_Control' );
		$this->register_control_type( 'WP_Customize_Image_Control' );
		$this->register_control_type( 'WP_Customize_Background_Image_Control' );
		$this->register_control_type( 'WP_Customize_Background_Position_Control' );
		$this->register_control_type( 'WP_Customize_Cropped_Image_Control' );
		$this->register_control_type( 'WP_Customize_Site_Icon_Control' );
		$this->register_control_type( 'WP_Customize_Theme_Control' );
		$this->register_control_type( 'WP_Customize_Code_Editor_Control' );
		$this->register_control_type( 'WP_Customize_Date_Time_Control' );

		/**
		 * Kích hoạt khi WordPress đã tải xong, cho phép khởi tạo scripts và styles.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Manager $manager Thể hiện WP_Customize_Manager.
		 */
		do_action( 'customize_register', $this );

		if ( $this->settings_previewed() ) {
			foreach ( $this->settings as $setting ) {
				$setting->preview();
			}
		}

		if ( $this->is_preview() && ! is_admin() ) {
			$this->customize_preview_init();
		}
	}

	/**
	 * Ngăn các yêu cầu Ajax theo dõi chuyển hướng khi xem trước giao diện
	 * bằng cách trả về phản hồi 200 thay vì 30x.
	 *
	 * Thay vào đó, JS sẽ phát hiện header location.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0
	 *
	 * @param int $status Trạng thái.
	 * @return int
	 */
	public function wp_redirect_status( $status ) {
		_deprecated_function( __FUNCTION__, '4.7.0' );

		if ( $this->is_preview() && ! is_admin() ) {
			return 200;
		}

		return $status;
	}

	/**
	 * Tìm ID bài viết changeset cho một UUID changeset cho trước.
	 *
	 * @since 4.7.0
	 *
	 * @param string $uuid UUID của changeset.
	 * @return int|null Trả về ID bài viết nếu thành công và null nếu thất bại.
	 */
	public function find_changeset_post_id( $uuid ) {
		$cache_group       = 'customize_changeset_post';
		$changeset_post_id = wp_cache_get( $uuid, $cache_group );
		if ( $changeset_post_id && 'customize_changeset' === get_post_type( $changeset_post_id ) ) {
			return $changeset_post_id;
		}

		$changeset_post_query = new WP_Query(
			array(
				'post_type'              => 'customize_changeset',
				'post_status'            => get_post_stati(),
				'name'                   => $uuid,
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'cache_results'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'lazy_load_term_meta'    => false,
			)
		);
		if ( ! empty( $changeset_post_query->posts ) ) {
			// Lưu ý: 'fields'=>'ids' không được sử dụng để lưu cache đối tượng bài viết vì nó sẽ cần thiết.
			$changeset_post_id = $changeset_post_query->posts[0]->ID;
			wp_cache_set( $uuid, $changeset_post_id, $cache_group );
			return $changeset_post_id;
		}

		return null;
	}

	/**
	 * Lấy các bài viết changeset.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args {
	 *     Các tham số truyền vào `get_posts()` để truy vấn changeset.
	 *
	 *     @type int    $posts_per_page             Số bài viết trả về. Mặc định là -1 (tất cả bài viết).
	 *     @type int    $author                     Tác giả bài viết. Mặc định là người dùng hiện tại.
	 *     @type string $post_status                Trạng thái changeset. Mặc định là 'auto-draft'.
	 *     @type bool   $exclude_restore_dismissed  Có loại trừ các bản nháp tự động changeset đã bị bỏ qua hay không. Mặc định là true.
	 * }
	 * @return WP_Post[] Các changeset bản nháp tự động.
	 */
	protected function get_changeset_posts( $args = array() ) {
		$default_args = array(
			'exclude_restore_dismissed' => true,
			'posts_per_page'            => -1,
			'post_type'                 => 'customize_changeset',
			'post_status'               => 'auto-draft',
			'order'                     => 'DESC',
			'orderby'                   => 'date',
			'no_found_rows'             => true,
			'cache_results'             => true,
			'update_post_meta_cache'    => false,
			'update_post_term_cache'    => false,
			'lazy_load_term_meta'       => false,
		);
		if ( get_current_user_id() ) {
			$default_args['author'] = get_current_user_id();
		}
		$args = array_merge( $default_args, $args );

		if ( ! empty( $args['exclude_restore_dismissed'] ) ) {
			unset( $args['exclude_restore_dismissed'] );
			$args['meta_query'] = array(
				array(
					'key'     => '_customize_restore_dismissed',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		return get_posts( $args );
	}

	/**
	 * Bỏ qua tất cả các bản nháp tự động của người dùng hiện tại (ngoại trừ bản hiện tại).
	 *
	 * @since 4.9.0
	 * @return int Số lượng bản nháp tự động đã bị bỏ qua.
	 */
	protected function dismiss_user_auto_draft_changesets() {
		$changeset_autodraft_posts = $this->get_changeset_posts(
			array(
				'post_status'               => 'auto-draft',
				'exclude_restore_dismissed' => true,
				'posts_per_page'            => -1,
			)
		);
		$dismissed                 = 0;
		foreach ( $changeset_autodraft_posts as $autosave_autodraft_post ) {
			if ( $autosave_autodraft_post->ID === $this->changeset_post_id() ) {
				continue;
			}
			if ( update_post_meta( $autosave_autodraft_post->ID, '_customize_restore_dismissed', true ) ) {
				++$dismissed;
			}
		}
		return $dismissed;
	}

	/**
	 * Lấy ID bài viết changeset cho changeset đã tải.
	 *
	 * @since 4.7.0
	 *
	 * @return int|null ID bài viết nếu thành công hoặc null nếu chưa có bài viết nào được lưu.
	 */
	public function changeset_post_id() {
		if ( ! isset( $this->_changeset_post_id ) ) {
			$post_id = $this->find_changeset_post_id( $this->changeset_uuid() );
			if ( ! $post_id ) {
				$post_id = false;
			}
			$this->_changeset_post_id = $post_id;
		}
		if ( false === $this->_changeset_post_id ) {
			return null;
		}
		return $this->_changeset_post_id;
	}

	/**
	 * Lấy dữ liệu được lưu trong bài viết changeset.
	 *
	 * @since 4.7.0
	 *
	 * @param int $post_id ID bài viết changeset.
	 * @return array|WP_Error Dữ liệu changeset hoặc WP_Error nếu có lỗi.
	 */
	protected function get_changeset_post_data( $post_id ) {
		if ( ! $post_id ) {
			return new WP_Error( 'empty_post_id' );
		}
		$changeset_post = get_post( $post_id );
		if ( ! $changeset_post ) {
			return new WP_Error( 'missing_post' );
		}
		if ( 'revision' === $changeset_post->post_type ) {
			if ( 'customize_changeset' !== get_post_type( $changeset_post->post_parent ) ) {
				return new WP_Error( 'wrong_post_type' );
			}
		} elseif ( 'customize_changeset' !== $changeset_post->post_type ) {
			return new WP_Error( 'wrong_post_type' );
		}
		$changeset_data = json_decode( $changeset_post->post_content, true );
		$last_error     = json_last_error();
		if ( $last_error ) {
			return new WP_Error( 'json_parse_error', '', $last_error );
		}
		if ( ! is_array( $changeset_data ) ) {
			return new WP_Error( 'expected_array' );
		}
		return $changeset_data;
	}

	/**
	 * Lấy dữ liệu changeset.
	 *
	 * @since 4.7.0
	 * @since 4.9.0 Phương thức này sẽ trả về dữ liệu changeset với bản sửa đổi tự động lưu của người dùng được gộp lên trên, nếu có và $autosaved là true.
	 *
	 * @return array Dữ liệu changeset.
	 */
	public function changeset_data() {
		if ( isset( $this->_changeset_data ) ) {
			return $this->_changeset_data;
		}
		$changeset_post_id = $this->changeset_post_id();
		if ( ! $changeset_post_id ) {
			$this->_changeset_data = array();
		} else {
			if ( $this->autosaved() && is_user_logged_in() ) {
				$autosave_post = wp_get_post_autosave( $changeset_post_id, get_current_user_id() );
				if ( $autosave_post ) {
					$data = $this->get_changeset_post_data( $autosave_post->ID );
					if ( ! is_wp_error( $data ) ) {
						$this->_changeset_data = $data;
					}
				}
			}

			// Tải dữ liệu từ changeset nếu nó chưa được tải từ bản tự động lưu.
			if ( ! isset( $this->_changeset_data ) ) {
				$data = $this->get_changeset_post_data( $changeset_post_id );
				if ( ! is_wp_error( $data ) ) {
					$this->_changeset_data = $data;
				} else {
					$this->_changeset_data = array();
				}
			}
		}
		return $this->_changeset_data;
	}

	/**
	 * Các ID cài đặt nội dung khởi đầu.
	 *
	 * @since 4.7.0
	 * @var array
	 */
	protected $pending_starter_content_settings_ids = array();

	/**
	 * Nhập nội dung khởi đầu của giao diện vào trạng thái đã tùy biến.
	 *
	 * @since 4.7.0
	 *
	 * @param array $starter_content Nội dung khởi đầu. Mặc định là `get_theme_starter_content()`.
	 */
	public function import_theme_starter_content( $starter_content = array() ) {
		if ( empty( $starter_content ) ) {
			$starter_content = get_theme_starter_content();
		}

		$changeset_data = array();
		if ( $this->changeset_post_id() ) {
			/*
			 * Không nhập lại nội dung khởi đầu vào changeset đã được lưu vĩnh viễn.
			 * Điều này cần được xem xét lại trong tương lai khi cho phép chuyển đổi
			 * giao diện với các changeset đã lưu nháp/đã lên lịch, vì chuyển sang
			 * giao diện khác có thể dẫn đến nhiều nội dung khởi đầu hơn được áp dụng.
			 * Tuy nhiên, khi thực hiện lưu rõ ràng, hiện tại có thể xảy ra trường hợp
			 * các menu điều hướng và mục menu điều hướng cụ thể bị mất cờ starter_content,
			 * dẫn đến việc tạo bản sao vì chúng không được tái sử dụng. Xem #40146.
			 */
			if ( 'auto-draft' !== get_post_status( $this->changeset_post_id() ) ) {
				return;
			}

			$changeset_data = $this->get_changeset_post_data( $this->changeset_post_id() );
		}

		$sidebars_widgets = isset( $starter_content['widgets'] ) && ! empty( $this->widgets ) ? $starter_content['widgets'] : array();
		$attachments      = isset( $starter_content['attachments'] ) && ! empty( $this->nav_menus ) ? $starter_content['attachments'] : array();
		$posts            = isset( $starter_content['posts'] ) && ! empty( $this->nav_menus ) ? $starter_content['posts'] : array();
		$options          = isset( $starter_content['options'] ) ? $starter_content['options'] : array();
		$nav_menus        = isset( $starter_content['nav_menus'] ) && ! empty( $this->nav_menus ) ? $starter_content['nav_menus'] : array();
		$theme_mods       = isset( $starter_content['theme_mods'] ) ? $starter_content['theme_mods'] : array();

		// Widget.
		$max_widget_numbers = array();
		foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
			$sidebar_widget_ids = array();
			foreach ( $widgets as $widget ) {
				list( $id_base, $instance ) = $widget;

				if ( ! isset( $max_widget_numbers[ $id_base ] ) ) {

					// Khi $settings là đối tượng dạng mảng, lấy mảng nội tại để sử dụng với array_keys().
					$settings = get_option( "widget_{$id_base}", array() );
					if ( $settings instanceof ArrayObject || $settings instanceof ArrayIterator ) {
						$settings = $settings->getArrayCopy();
					}

					unset( $settings['_multiwidget'] );

					// Tìm số widget tối đa cho loại này.
					$widget_numbers = array_keys( $settings );
					if ( count( $widget_numbers ) > 0 ) {
						$widget_numbers[]               = 1;
						$max_widget_numbers[ $id_base ] = max( ...$widget_numbers );
					} else {
						$max_widget_numbers[ $id_base ] = 1;
					}
				}
				$max_widget_numbers[ $id_base ] += 1;

				$widget_id  = sprintf( '%s-%d', $id_base, $max_widget_numbers[ $id_base ] );
				$setting_id = sprintf( 'widget_%s[%d]', $id_base, $max_widget_numbers[ $id_base ] );

				$setting_value = $this->widgets->sanitize_widget_js_instance( $instance );
				if ( empty( $changeset_data[ $setting_id ] ) || ! empty( $changeset_data[ $setting_id ]['starter_content'] ) ) {
					$this->set_post_value( $setting_id, $setting_value );
					$this->pending_starter_content_settings_ids[] = $setting_id;
				}
				$sidebar_widget_ids[] = $widget_id;
			}

			$setting_id = sprintf( 'sidebars_widgets[%s]', $sidebar_id );
			if ( empty( $changeset_data[ $setting_id ] ) || ! empty( $changeset_data[ $setting_id ]['starter_content'] ) ) {
				$this->set_post_value( $setting_id, $sidebar_widget_ids );
				$this->pending_starter_content_settings_ids[] = $setting_id;
			}
		}

		$starter_content_auto_draft_post_ids = array();
		if ( ! empty( $changeset_data['nav_menus_created_posts']['value'] ) ) {
			$starter_content_auto_draft_post_ids = array_merge( $starter_content_auto_draft_post_ids, $changeset_data['nav_menus_created_posts']['value'] );
		}

		// Tạo chỉ mục tất cả các bài viết cần thiết và slug của chúng.
		$needed_posts = array();
		$attachments  = $this->prepare_starter_content_attachments( $attachments );
		foreach ( $attachments as $attachment ) {
			$key                  = 'attachment:' . $attachment['post_name'];
			$needed_posts[ $key ] = true;
		}
		foreach ( array_keys( $posts ) as $post_symbol ) {
			if ( empty( $posts[ $post_symbol ]['post_name'] ) && empty( $posts[ $post_symbol ]['post_title'] ) ) {
				unset( $posts[ $post_symbol ] );
				continue;
			}
			if ( empty( $posts[ $post_symbol ]['post_name'] ) ) {
				$posts[ $post_symbol ]['post_name'] = sanitize_title( $posts[ $post_symbol ]['post_title'] );
			}
			if ( empty( $posts[ $post_symbol ]['post_type'] ) ) {
				$posts[ $post_symbol ]['post_type'] = 'post';
			}
			$needed_posts[ $posts[ $post_symbol ]['post_type'] . ':' . $posts[ $post_symbol ]['post_name'] ] = true;
		}
		$all_post_slugs = array_merge(
			wp_list_pluck( $attachments, 'post_name' ),
			wp_list_pluck( $posts, 'post_name' )
		);

		/*
		 * Lấy tất cả các loại bài viết được tham chiếu trong nội dung khởi đầu để sử dụng trong truy vấn.
		 * Điều này cần thiết vì 'any' sẽ không bao gồm các loại bài viết chưa được đăng ký.
		 */
		$post_types = array_filter( array_merge( array( 'attachment' ), wp_list_pluck( $posts, 'post_type' ) ) );

		// Tái sử dụng các bài viết nội dung khởi đầu bản nháp tự động được tham chiếu trong trạng thái tùy biến hiện tại.
		$existing_starter_content_posts = array();
		if ( ! empty( $starter_content_auto_draft_post_ids ) ) {
			$existing_posts_query = new WP_Query(
				array(
					'post__in'       => $starter_content_auto_draft_post_ids,
					'post_status'    => 'auto-draft',
					'post_type'      => $post_types,
					'posts_per_page' => -1,
				)
			);
			foreach ( $existing_posts_query->posts as $existing_post ) {
				$post_name = $existing_post->post_name;
				if ( empty( $post_name ) ) {
					$post_name = get_post_meta( $existing_post->ID, '_customize_draft_post_name', true );
				}
				$existing_starter_content_posts[ $existing_post->post_type . ':' . $post_name ] = $existing_post;
			}
		}

		// Tái sử dụng các bài viết không phải bản nháp tự động.
		if ( ! empty( $all_post_slugs ) ) {
			$existing_posts_query = new WP_Query(
				array(
					'post_name__in'  => $all_post_slugs,
					'post_status'    => array_diff( get_post_stati(), array( 'auto-draft' ) ),
					'post_type'      => 'any',
					'posts_per_page' => -1,
				)
			);
			foreach ( $existing_posts_query->posts as $existing_post ) {
				$key = $existing_post->post_type . ':' . $existing_post->post_name;
				if ( isset( $needed_posts[ $key ] ) && ! isset( $existing_starter_content_posts[ $key ] ) ) {
					$existing_starter_content_posts[ $key ] = $existing_post;
				}
			}
		}

		// Tệp đính kèm về mặt kỹ thuật là bài viết nhưng được xử lý khác.
		if ( ! empty( $attachments ) ) {

			$attachment_ids = array();

			foreach ( $attachments as $symbol => $attachment ) {
				$file_array    = array(
					'name' => $attachment['file_name'],
				);
				$file_path     = $attachment['file_path'];
				$attachment_id = null;
				$attached_file = null;
				if ( isset( $existing_starter_content_posts[ 'attachment:' . $attachment['post_name'] ] ) ) {
					$attachment_post = $existing_starter_content_posts[ 'attachment:' . $attachment['post_name'] ];
					$attachment_id   = $attachment_post->ID;
					$attached_file   = get_attached_file( $attachment_id );
					if ( empty( $attached_file ) || ! file_exists( $attached_file ) ) {
						$attachment_id = null;
						$attached_file = null;
					} elseif ( $this->get_stylesheet() !== get_post_meta( $attachment_post->ID, '_starter_content_theme', true ) ) {

						// Tạo lại metadata tệp đính kèm vì nó đã được tạo trước đó cho một giao diện khác.
						$metadata = wp_generate_attachment_metadata( $attachment_post->ID, $attached_file );
						wp_update_attachment_metadata( $attachment_id, $metadata );
						update_post_meta( $attachment_id, '_starter_content_theme', $this->get_stylesheet() );
					}
				}

				// Chèn bản nháp tự động tệp đính kèm vì nó chưa tồn tại hoặc tệp đính kèm đã bị mất.
				if ( ! $attachment_id ) {

					// Sao chép tệp đến vị trí tạm để tệp gốc không bị xóa khỏi giao diện sau khi sideload.
					$temp_file_name = wp_tempnam( wp_basename( $file_path ) );
					if ( $temp_file_name && copy( $file_path, $temp_file_name ) ) {
						$file_array['tmp_name'] = $temp_file_name;
					}
					if ( empty( $file_array['tmp_name'] ) ) {
						continue;
					}

					$attachment_post_data = array_merge(
						wp_array_slice_assoc( $attachment, array( 'post_title', 'post_content', 'post_excerpt' ) ),
						array(
							'post_status' => 'auto-draft', // Để tệp đính kèm sẽ được dọn rác trong một tuần nếu changeset không bao giờ được xuất bản.
						)
					);

					$attachment_id = media_handle_sideload( $file_array, 0, null, $attachment_post_data );
					if ( is_wp_error( $attachment_id ) ) {
						continue;
					}
					update_post_meta( $attachment_id, '_starter_content_theme', $this->get_stylesheet() );
					update_post_meta( $attachment_id, '_customize_draft_post_name', $attachment['post_name'] );
				}

				$attachment_ids[ $symbol ] = $attachment_id;
			}
			$starter_content_auto_draft_post_ids = array_merge( $starter_content_auto_draft_post_ids, array_values( $attachment_ids ) );
		}

		// Bài viết & trang.
		if ( ! empty( $posts ) ) {
			foreach ( array_keys( $posts ) as $post_symbol ) {
				if ( empty( $posts[ $post_symbol ]['post_type'] ) || empty( $posts[ $post_symbol ]['post_name'] ) ) {
					continue;
				}
				$post_type = $posts[ $post_symbol ]['post_type'];
				if ( ! empty( $posts[ $post_symbol ]['post_name'] ) ) {
					$post_name = $posts[ $post_symbol ]['post_name'];
				} elseif ( ! empty( $posts[ $post_symbol ]['post_title'] ) ) {
					$post_name = sanitize_title( $posts[ $post_symbol ]['post_title'] );
				} else {
					continue;
				}

				// Sử dụng bài viết bản nháp tự động hiện có nếu đã tồn tại một bài với cùng loại và tên.
				if ( isset( $existing_starter_content_posts[ $post_type . ':' . $post_name ] ) ) {
					$posts[ $post_symbol ]['ID'] = $existing_starter_content_posts[ $post_type . ':' . $post_name ]->ID;
					continue;
				}

				// Chuyển đổi ký hiệu ảnh đại diện.
				if ( ! empty( $posts[ $post_symbol ]['thumbnail'] )
					&& preg_match( '/^{{(?P<symbol>.+)}}$/', $posts[ $post_symbol ]['thumbnail'], $matches )
					&& isset( $attachment_ids[ $matches['symbol'] ] ) ) {
					$posts[ $post_symbol ]['meta_input']['_thumbnail_id'] = $attachment_ids[ $matches['symbol'] ];
				}

				if ( ! empty( $posts[ $post_symbol ]['template'] ) ) {
					$posts[ $post_symbol ]['meta_input']['_wp_page_template'] = $posts[ $post_symbol ]['template'];
				}

				$r = $this->nav_menus->insert_auto_draft_post( $posts[ $post_symbol ] );
				if ( $r instanceof WP_Post ) {
					$posts[ $post_symbol ]['ID'] = $r->ID;
				}
			}

			$starter_content_auto_draft_post_ids = array_merge( $starter_content_auto_draft_post_ids, wp_list_pluck( $posts, 'ID' ) );
		}

		// Cài đặt nav_menus_created_posts là lý do tại sao thành phần nav_menus là phụ thuộc để thêm bài viết.
		if ( ! empty( $this->nav_menus ) && ! empty( $starter_content_auto_draft_post_ids ) ) {
			$setting_id = 'nav_menus_created_posts';
			$this->set_post_value( $setting_id, array_unique( array_values( $starter_content_auto_draft_post_ids ) ) );
			$this->pending_starter_content_settings_ids[] = $setting_id;
		}

		// Menu điều hướng.
		$placeholder_id              = -1;
		$reused_nav_menu_setting_ids = array();
		foreach ( $nav_menus as $nav_menu_location => $nav_menu ) {

			$nav_menu_term_id    = null;
			$nav_menu_setting_id = null;
			$matches             = array();

			// Tìm menu giữ chỗ hiện có với nội dung khởi đầu để tái sử dụng.
			foreach ( $changeset_data as $setting_id => $setting_params ) {
				$can_reuse = (
					! empty( $setting_params['starter_content'] )
					&&
					! in_array( $setting_id, $reused_nav_menu_setting_ids, true )
					&&
					preg_match( '#^nav_menu\[(?P<nav_menu_id>-?\d+)\]$#', $setting_id, $matches )
				);
				if ( $can_reuse ) {
					$nav_menu_term_id              = (int) $matches['nav_menu_id'];
					$nav_menu_setting_id           = $setting_id;
					$reused_nav_menu_setting_ids[] = $setting_id;
					break;
				}
			}

			if ( ! $nav_menu_term_id ) {
				while ( isset( $changeset_data[ sprintf( 'nav_menu[%d]', $placeholder_id ) ] ) ) {
					--$placeholder_id;
				}
				$nav_menu_term_id    = $placeholder_id;
				$nav_menu_setting_id = sprintf( 'nav_menu[%d]', $placeholder_id );
			}

			$this->set_post_value(
				$nav_menu_setting_id,
				array(
					'name' => isset( $nav_menu['name'] ) ? $nav_menu['name'] : $nav_menu_location,
				)
			);
			$this->pending_starter_content_settings_ids[] = $nav_menu_setting_id;

			// @todo Thêm hỗ trợ cho menu_item_parent.
			$position = 0;
			foreach ( $nav_menu['items'] as $nav_menu_item ) {
				$nav_menu_item_setting_id = sprintf( 'nav_menu_item[%d]', $placeholder_id-- );
				if ( ! isset( $nav_menu_item['position'] ) ) {
					$nav_menu_item['position'] = $position++;
				}
				$nav_menu_item['nav_menu_term_id'] = $nav_menu_term_id;

				if ( isset( $nav_menu_item['object_id'] ) ) {
					if ( 'post_type' === $nav_menu_item['type'] && preg_match( '/^{{(?P<symbol>.+)}}$/', $nav_menu_item['object_id'], $matches ) && isset( $posts[ $matches['symbol'] ] ) ) {
						$nav_menu_item['object_id'] = $posts[ $matches['symbol'] ]['ID'];
						if ( empty( $nav_menu_item['title'] ) ) {
							$original_object        = get_post( $nav_menu_item['object_id'] );
							$nav_menu_item['title'] = $original_object->post_title;
						}
					} else {
						continue;
					}
				} else {
					$nav_menu_item['object_id'] = 0;
				}

				if ( empty( $changeset_data[ $nav_menu_item_setting_id ] ) || ! empty( $changeset_data[ $nav_menu_item_setting_id ]['starter_content'] ) ) {
					$this->set_post_value( $nav_menu_item_setting_id, $nav_menu_item );
					$this->pending_starter_content_settings_ids[] = $nav_menu_item_setting_id;
				}
			}

			$setting_id = sprintf( 'nav_menu_locations[%s]', $nav_menu_location );
			if ( empty( $changeset_data[ $setting_id ] ) || ! empty( $changeset_data[ $setting_id ]['starter_content'] ) ) {
				$this->set_post_value( $setting_id, $nav_menu_term_id );
				$this->pending_starter_content_settings_ids[] = $setting_id;
			}
		}

		// Tùy chọn.
		foreach ( $options as $name => $value ) {

			// Tuần tự hóa giá trị để kiểm tra các ký hiệu bài viết.
			$value = maybe_serialize( $value );

			if ( is_serialized( $value ) ) {
				if ( preg_match( '/s:\d+:"{{(?P<symbol>.+)}}"/', $value, $matches ) ) {
					if ( isset( $posts[ $matches['symbol'] ] ) ) {
						$symbol_match = $posts[ $matches['symbol'] ]['ID'];
					} elseif ( isset( $attachment_ids[ $matches['symbol'] ] ) ) {
						$symbol_match = $attachment_ids[ $matches['symbol'] ];
					}

					// Nếu có bất kỳ kết quả khớp ký hiệu nào, cập nhật giá trị.
					if ( isset( $symbol_match ) ) {
						// Thay thế các kết quả khớp chuỗi tìm được bằng ID bài viết.
						$value = str_replace( $matches[0], "i:{$symbol_match}", $value );
					} else {
						continue;
					}
				}
			} elseif ( preg_match( '/^{{(?P<symbol>.+)}}$/', $value, $matches ) ) {
				if ( isset( $posts[ $matches['symbol'] ] ) ) {
					$value = $posts[ $matches['symbol'] ]['ID'];
				} elseif ( isset( $attachment_ids[ $matches['symbol'] ] ) ) {
					$value = $attachment_ids[ $matches['symbol'] ];
				} else {
					continue;
				}
			}

			// Giải tuần tự hóa giá trị sau khi kiểm tra các ký hiệu bài viết, để chúng có thể được tham chiếu đúng.
			$value = maybe_unserialize( $value );

			if ( empty( $changeset_data[ $name ] ) || ! empty( $changeset_data[ $name ]['starter_content'] ) ) {
				$this->set_post_value( $name, $value );
				$this->pending_starter_content_settings_ids[] = $name;
			}
		}

		// Tùy chỉnh giao diện.
		foreach ( $theme_mods as $name => $value ) {

			// Tuần tự hóa giá trị để kiểm tra các ký hiệu bài viết.
			$value = maybe_serialize( $value );

			// Kiểm tra xem giá trị đã được tuần tự hóa chưa.
			if ( is_serialized( $value ) ) {
				if ( preg_match( '/s:\d+:"{{(?P<symbol>.+)}}"/', $value, $matches ) ) {
					if ( isset( $posts[ $matches['symbol'] ] ) ) {
						$symbol_match = $posts[ $matches['symbol'] ]['ID'];
					} elseif ( isset( $attachment_ids[ $matches['symbol'] ] ) ) {
						$symbol_match = $attachment_ids[ $matches['symbol'] ];
					}

					// Nếu có bất kỳ kết quả khớp ký hiệu nào, cập nhật giá trị.
					if ( isset( $symbol_match ) ) {
						// Thay thế các kết quả khớp chuỗi tìm được bằng ID bài viết.
						$value = str_replace( $matches[0], "i:{$symbol_match}", $value );
					} else {
						continue;
					}
				}
			} elseif ( preg_match( '/^{{(?P<symbol>.+)}}$/', $value, $matches ) ) {
				if ( isset( $posts[ $matches['symbol'] ] ) ) {
					$value = $posts[ $matches['symbol'] ]['ID'];
				} elseif ( isset( $attachment_ids[ $matches['symbol'] ] ) ) {
					$value = $attachment_ids[ $matches['symbol'] ];
				} else {
					continue;
				}
			}

			// Giải tuần tự hóa giá trị sau khi kiểm tra các ký hiệu bài viết, để chúng có thể được tham chiếu đúng.
			$value = maybe_unserialize( $value );

			// Xử lý ảnh header như trường hợp đặc biệt vì cài đặt có định dạng cũ.
			if ( 'header_image' === $name ) {
				$name     = 'header_image_data';
				$metadata = wp_get_attachment_metadata( $value );
				if ( empty( $metadata ) ) {
					continue;
				}
				$value = array(
					'attachment_id' => $value,
					'url'           => wp_get_attachment_url( $value ),
					'height'        => $metadata['height'],
					'width'         => $metadata['width'],
				);
			} elseif ( 'background_image' === $name ) {
				$value = wp_get_attachment_url( $value );
			}

			if ( empty( $changeset_data[ $name ] ) || ! empty( $changeset_data[ $name ]['starter_content'] ) ) {
				$this->set_post_value( $name, $value );
				$this->pending_starter_content_settings_ids[] = $name;
			}
		}

		if ( ! empty( $this->pending_starter_content_settings_ids ) ) {
			if ( did_action( 'customize_register' ) ) {
				$this->_save_starter_content_changeset();
			} else {
				add_action( 'customize_register', array( $this, '_save_starter_content_changeset' ), 1000 );
			}
		}
	}

	/**
	 * Chuẩn bị các tệp đính kèm nội dung khởi đầu.
	 *
	 * Đảm bảo rằng các tệp đính kèm hợp lệ và có slug và tên/đường dẫn tệp.
	 *
	 * @since 4.7.0
	 *
	 * @param array $attachments Các tệp đính kèm.
	 * @return array Các tệp đính kèm đã chuẩn bị.
	 */
	protected function prepare_starter_content_attachments( $attachments ) {
		$prepared_attachments = array();
		if ( empty( $attachments ) ) {
			return $prepared_attachments;
		}

		// Đây là Cách của WordPress.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $attachments as $symbol => $attachment ) {

			// Cần có tệp và URL đến tệp hiện không được phép.
			if ( empty( $attachment['file'] ) || preg_match( '#^https?://$#', $attachment['file'] ) ) {
				continue;
			}

			$file_path = null;
			if ( file_exists( $attachment['file'] ) ) {
				$file_path = $attachment['file']; // Có thể là đường dẫn tuyệt đối đến tệp trong plugin.
			} elseif ( is_child_theme() && file_exists( get_stylesheet_directory() . '/' . $attachment['file'] ) ) {
				$file_path = get_stylesheet_directory() . '/' . $attachment['file'];
			} elseif ( file_exists( get_template_directory() . '/' . $attachment['file'] ) ) {
				$file_path = get_template_directory() . '/' . $attachment['file'];
			} else {
				continue;
			}
			$file_name = wp_basename( $attachment['file'] );

			// Bỏ qua các loại tệp không được nhận dạng.
			$checked_filetype = wp_check_filetype( $file_name );
			if ( empty( $checked_filetype['type'] ) ) {
				continue;
			}

			// Đảm bảo post_name được thiết lập vì nó không tự động được tạo từ post_title cho các bài viết bản nháp tự động mới.
			if ( empty( $attachment['post_name'] ) ) {
				if ( ! empty( $attachment['post_title'] ) ) {
					$attachment['post_name'] = sanitize_title( $attachment['post_title'] );
				} else {
					$attachment['post_name'] = sanitize_title( preg_replace( '/\.\w+$/', '', $file_name ) );
				}
			}

			$attachment['file_name']         = $file_name;
			$attachment['file_path']         = $file_path;
			$prepared_attachments[ $symbol ] = $attachment;
		}
		return $prepared_attachments;
	}

	/**
	 * Lưu changeset nội dung khởi đầu.
	 *
	 * @since 4.7.0
	 */
	public function _save_starter_content_changeset() {

		if ( empty( $this->pending_starter_content_settings_ids ) ) {
			return;
		}

		$this->save_changeset_post(
			array(
				'data'            => array_fill_keys( $this->pending_starter_content_settings_ids, array( 'starter_content' => true ) ),
				'starter_content' => true,
			)
		);
		$this->saved_starter_content_changeset = true;

		$this->pending_starter_content_settings_ids = array();
	}

	/**
	 * Lấy các giá trị cài đặt chưa được làm sạch đã thay đổi trong trạng thái tùy biến hiện tại.
	 *
	 * Mảng trả về bao gồm sự hợp nhất của ba nguồn:
	 * 1. Nếu giao diện hiện không hoạt động, thì mảng cơ sở là bất kỳ
	 *    theme mods nào đã được lưu tạm đã sửa đổi trước đó nhưng chưa được xuất bản.
	 * 2. Các giá trị từ changeset hiện tại, nếu tồn tại.
	 * 3. Nếu người dùng có quyền tùy biến, các giá trị được phân tích từ
	 *    dữ liệu JSON `$_POST['customized']` đến.
	 * 4. Bất kỳ giá trị post nào được thiết lập bằng lập trình qua `WP_Customize_Manager::set_post_value()`.
	 *
	 * Tên "unsanitized_post_values" là tên kế thừa từ khi trạng thái tùy biến
	 * chỉ được lấy từ `$_POST['customized']`. Tuy nhiên,
	 * giá trị trả về sẽ đến từ bài viết changeset hiện tại và từ
	 * dữ liệu post đến.
	 *
	 * @since 4.1.1
	 * @since 4.7.0 Thêm tham số `$args` và gộp với giá trị changeset và theme mods đã lưu tạm.
	 *
	 * @param array $args {
	 *     Các tham số.
	 *
	 *     @type bool $exclude_changeset Có nên loại trừ giá trị changeset hay không. Mặc định là false.
	 *     @type bool $exclude_post_data Có nên loại trừ giá trị post đầu vào hay không. Mặc định là false khi thiếu quyền customize.
	 * }
	 * @return array
	 */
	public function unsanitized_post_values( $args = array() ) {
		$args = array_merge(
			array(
				'exclude_changeset' => false,
				'exclude_post_data' => ! current_user_can( 'customize' ),
			),
			$args
		);

		$values = array();

		// Cho phép giá trị mặc định là từ theme mods đã lưu tạm nếu đang chuyển đổi giao diện và không có changeset nào.
		if ( ! $this->is_theme_active() ) {
			$stashed_theme_mods = get_option( 'customize_stashed_theme_mods' );
			$stylesheet         = $this->get_stylesheet();
			if ( isset( $stashed_theme_mods[ $stylesheet ] ) ) {
				$values = array_merge( $values, wp_list_pluck( $stashed_theme_mods[ $stylesheet ], 'value' ) );
			}
		}

		if ( ! $args['exclude_changeset'] ) {
			foreach ( $this->changeset_data() as $setting_id => $setting_params ) {
				if ( ! array_key_exists( 'value', $setting_params ) ) {
					continue;
				}
				if ( isset( $setting_params['type'] ) && 'theme_mod' === $setting_params['type'] ) {

					// Đảm bảo rằng các giá trị theme mods chỉ được sử dụng nếu chúng được lưu dưới giao diện đang hoạt động.
					$namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
					if ( preg_match( $namespace_pattern, $setting_id, $matches ) && $this->get_stylesheet() === $matches['stylesheet'] ) {
						$values[ $matches['setting_id'] ] = $setting_params['value'];
					}
				} else {
					$values[ $setting_id ] = $setting_params['value'];
				}
			}
		}

		if ( ! $args['exclude_post_data'] ) {
			if ( ! isset( $this->_post_values ) ) {
				if ( isset( $_POST['customized'] ) ) {
					$post_values = json_decode( wp_unslash( $_POST['customized'] ), true );
				} else {
					$post_values = array();
				}
				if ( is_array( $post_values ) ) {
					$this->_post_values = $post_values;
				} else {
					$this->_post_values = array();
				}
			}
			$values = array_merge( $values, $this->_post_values );
		}
		return $values;
	}

	/**
	 * Trả về giá trị đã được làm sạch cho một cài đặt cho trước từ trạng thái tùy biến hiện tại.
	 *
	 * Tên "post_value" là tên kế thừa từ khi trạng thái tùy biến chỉ
	 * được lấy từ `$_POST['customized']`. Tuy nhiên, giá trị trả về sẽ đến
	 * từ bài viết changeset hiện tại và từ dữ liệu post đến.
	 *
	 * @since 3.4.0
	 * @since 4.1.1 Giới thiệu tham số `$default_value`.
	 * @since 4.6.0 `$default_value` giờ được trả về sớm khi giá trị post của cài đặt không hợp lệ.
	 *
	 * @see WP_REST_Server::dispatch()
	 * @see WP_REST_Request::sanitize_params()
	 * @see WP_REST_Request::has_valid_params()
	 *
	 * @param WP_Customize_Setting $setting       Đối tượng kế thừa từ WP_Customize_Setting.
	 * @param mixed                $default_value Giá trị trả về nếu `$setting` không có giá trị post (thêm từ 4.2.0)
	 *                                            hoặc giá trị post không hợp lệ (thêm từ 4.6.0).
	 * @return string|mixed Giá trị đã làm sạch hoặc `$default_value` được cung cấp.
	 */
	public function post_value( $setting, $default_value = null ) {
		$post_values = $this->unsanitized_post_values();
		if ( ! array_key_exists( $setting->id, $post_values ) ) {
			return $default_value;
		}

		$value = $post_values[ $setting->id ];
		$valid = $setting->validate( $value );
		if ( is_wp_error( $valid ) ) {
			return $default_value;
		}

		$value = $setting->sanitize( $value );
		if ( is_null( $value ) || is_wp_error( $value ) ) {
			return $default_value;
		}

		return $value;
	}

	/**
	 * Ghi đè giá trị của một cài đặt trong trạng thái tùy biến hiện tại.
	 *
	 * Tên "post_value" là tên kế thừa từ khi trạng thái tùy biến
	 * chỉ được lấy từ `$_POST['customized']`.
	 *
	 * @since 4.2.0
	 *
	 * @param string $setting_id ID cho thể hiện WP_Customize_Setting.
	 * @param mixed  $value      Giá trị post.
	 */
	public function set_post_value( $setting_id, $value ) {
		$this->unsanitized_post_values(); // Điền _post_values từ $_POST['customized'].
		$this->_post_values[ $setting_id ] = $value;

		/**
		 * Thông báo khi giá trị post chưa làm sạch của một cài đặt cụ thể đã được thiết lập.
		 *
		 * Kích hoạt khi phương thức WP_Customize_Manager::set_post_value() được gọi.
		 *
		 * Phần động của tên hook, `$setting_id`, tham chiếu đến ID cài đặt.
		 *
		 * @since 4.4.0
		 *
		 * @param mixed                $value   Giá trị post chưa làm sạch của cài đặt.
		 * @param WP_Customize_Manager $manager Thể hiện WP_Customize_Manager.
		 */
		do_action( "customize_post_value_set_{$setting_id}", $value, $this );

		/**
		 * Thông báo khi giá trị post chưa làm sạch của bất kỳ cài đặt nào đã được thiết lập.
		 *
		 * Kích hoạt khi phương thức WP_Customize_Manager::set_post_value() được gọi.
		 *
		 * Điều này hữu ích cho các thể hiện `WP_Customize_Setting` theo dõi
		 * để cập nhật giá trị xem trước đã được lưu cache.
		 *
		 * @since 4.4.0
		 *
		 * @param string               $setting_id ID cài đặt.
		 * @param mixed                $value      Giá trị post chưa làm sạch của cài đặt.
		 * @param WP_Customize_Manager $manager    Thể hiện WP_Customize_Manager.
		 */
		do_action( 'customize_post_value_set', $setting_id, $value, $this );
	}

	/**
	 * In các cài đặt JavaScript.
	 *
	 * @since 3.4.0
	 */
	public function customize_preview_init() {

		/*
		 * Giờ đây các bản xem trước Trình tùy biến được tải vào iframe qua yêu cầu GET
		 * và URL tự nhiên với UUID giao dịch được thêm vào, chúng ta cần đảm bảo rằng
		 * các phản hồi không bao giờ được cache bởi proxy. Trong thực tế, điều này sẽ không
		 * cần thiết nếu người dùng đã đăng nhập. Nhưng nếu truy cập ẩn danh được
		 * cho phép thì cookie xác thực sẽ không được gửi và WordPress sẽ
		 * không gửi header không cache theo mặc định.
		 */
		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'X-Robots: noindex, nofollow, noarchive' );
			header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		}
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter( 'wp_headers', array( $this, 'filter_iframe_security_headers' ) );

		/*
		 * Nếu bản xem trước đang được phục vụ bên trong iframe xem trước Trình tùy biến, và
		 * nếu người dùng không có quyền customize, thì được giả định rằng
		 * phiên của người dùng đã hết hạn và họ cần xác thực lại.
		 */
		if ( $this->messenger_channel && ! current_user_can( 'customize' ) ) {
			$this->wp_die(
				-1,
				sprintf(
					/* translators: %s: customize_messenger_channel */
					__( 'Unauthorized. You may remove the %s param to preview as frontend.' ),
					'<code>customize_messenger_channel<code>'
				)
			);
			return;
		}

		$this->prepare_controls();

		add_filter( 'wp_redirect', array( $this, 'add_state_query_params' ) );

		wp_enqueue_script( 'customize-preview' );
		wp_enqueue_style( 'customize-preview' );
		add_action( 'wp_head', array( $this, 'customize_preview_loading_style' ) );
		add_action( 'wp_head', array( $this, 'remove_frameless_preview_messenger_channel' ) );
		add_action( 'wp_footer', array( $this, 'customize_preview_settings' ), 20 );
		add_filter( 'get_edit_post_link', '__return_empty_string' );

		/**
		 * Kích hoạt khi bản xem trước Trình tùy biến đã được khởi tạo và các cài đặt
		 * JavaScript đã được in.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Manager $manager Thể hiện WP_Customize_Manager.
		 */
		do_action( 'customize_preview_init', $this );
	}

	/**
	 * Lọc các header X-Frame-Options và Content-Security-Policy để đảm bảo giao diện có thể tải trong trình tùy biến.
	 *
	 * @since 4.7.0
	 *
	 * @param array $headers Các header.
	 * @return array Các header.
	 */
	public function filter_iframe_security_headers( $headers ) {
		$headers['X-Frame-Options']         = 'SAMEORIGIN';
		$headers['Content-Security-Policy'] = "frame-ancestors 'self'";
		return $headers;
	}

	/**
	 * Thêm các tham số truy vấn trạng thái tùy biến vào URL cho trước nếu xem trước được cho phép.
	 *
	 * @since 4.7.0
	 *
	 * @see wp_redirect()
	 * @see WP_Customize_Manager::get_allowed_url()
	 *
	 * @param string $url URL.
	 * @return string URL.
	 */
	public function add_state_query_params( $url ) {
		$parsed_original_url = wp_parse_url( $url );
		$is_allowed          = false;
		foreach ( $this->get_allowed_urls() as $allowed_url ) {
			$parsed_allowed_url = wp_parse_url( $allowed_url );
			$is_allowed         = (
				$parsed_allowed_url['scheme'] === $parsed_original_url['scheme']
				&&
				$parsed_allowed_url['host'] === $parsed_original_url['host']
				&&
				str_starts_with( $parsed_original_url['path'], $parsed_allowed_url['path'] )
			);
			if ( $is_allowed ) {
				break;
			}
		}

		if ( $is_allowed ) {
			$query_params = array(
				'customize_changeset_uuid' => $this->changeset_uuid(),
			);
			if ( ! $this->is_theme_active() ) {
				$query_params['customize_theme'] = $this->get_stylesheet();
			}
			if ( $this->messenger_channel ) {
				$query_params['customize_messenger_channel'] = $this->messenger_channel;
			}
			$url = add_query_arg( $query_params, $url );
		}

		return $url;
	}

	/**
	 * Ngăn gửi trạng thái 404 khi trả về phản hồi cho bản xem trước tùy biến,
	 * vì nó làm cho jQuery Ajax thất bại. Gửi 200 thay thế.
	 *
	 * @since 4.0.0
	 * @deprecated 4.7.0
	 */
	public function customize_preview_override_404_status() {
		_deprecated_function( __METHOD__, '4.7.0' );
	}

	/**
	 * In phần tử base cho khung xem trước.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0
	 */
	public function customize_preview_base() {
		_deprecated_function( __METHOD__, '4.7.0' );
	}

	/**
	 * In giải pháp tạm để xử lý thẻ HTML5 trong IE < 9.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0 Trình tùy biến không còn hỗ trợ IE8, nên tất cả trình duyệt được hỗ trợ đều nhận dạng HTML5.
	 */
	public function customize_preview_html5() {
		_deprecated_function( __FUNCTION__, '4.7.0' );
	}

	/**
	 * In CSS cho các chỉ báo đang tải của bản xem trước Trình tùy biến.
	 *
	 * @since 4.2.0
	 */
	public function customize_preview_loading_style() {
		?>
		<style>
			body.wp-customizer-unloading {
				opacity: 0.25;
				cursor: progress !important;
				-webkit-transition: opacity 0.5s;
				transition: opacity 0.5s;
			}
			body.wp-customizer-unloading * {
				pointer-events: none !important;
			}
			form.customize-unpreviewable,
			form.customize-unpreviewable input,
			form.customize-unpreviewable select,
			form.customize-unpreviewable button,
			a.customize-unpreviewable,
			area.customize-unpreviewable {
				cursor: not-allowed !important;
			}
		</style>
		<?php
	}

	/**
	 * Xóa tham số truy vấn customize_messenger_channel khỏi cửa sổ xem trước khi nó không nằm trong iframe.
	 *
	 * Điều này đảm bảo rằng thanh quản trị sẽ được hiển thị. Nó cũng đảm bảo rằng điều hướng liên kết sẽ
	 * hoạt động như mong đợi vì khung cha không được gửi URL để điều hướng đến.
	 *
	 * @since 4.7.0
	 */
	public function remove_frameless_preview_messenger_channel() {
		if ( ! $this->messenger_channel ) {
			return;
		}
		ob_start();
		?>
		<script>
		( function() {
			if ( parent !== window ) {
				return;
			}
			const url = new URL( location.href );
			if ( url.searchParams.has( 'customize_messenger_channel' ) ) {
				url.searchParams.delete( 'customize_messenger_channel' );
				location.replace( url );
			}
		} )();
		</script>
		<?php
		wp_print_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
	}

	/**
	 * In cài đặt JavaScript cho khung xem trước.
	 *
	 * @since 3.4.0
	 */
	public function customize_preview_settings() {
		$post_values                 = $this->unsanitized_post_values( array( 'exclude_changeset' => true ) );
		$setting_validities          = $this->validate_setting_values( $post_values );
		$exported_setting_validities = array_map( array( $this, 'prepare_setting_validity_for_js' ), $setting_validities );

		// Lưu ý rằng REQUEST_URI không được truyền vào home_url() vì điều này làm hỏng các cài đặt thư mục con.
		$self_url           = empty( $_SERVER['REQUEST_URI'] ) ? home_url( '/' ) : sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$state_query_params = array(
			'customize_theme',
			'customize_changeset_uuid',
			'customize_messenger_channel',
		);
		$self_url           = remove_query_arg( $state_query_params, $self_url );

		$allowed_urls  = $this->get_allowed_urls();
		$allowed_hosts = array();
		foreach ( $allowed_urls as $allowed_url ) {
			$parsed = wp_parse_url( $allowed_url );
			if ( empty( $parsed['host'] ) ) {
				continue;
			}
			$host = $parsed['host'];
			if ( ! empty( $parsed['port'] ) ) {
				$host .= ':' . $parsed['port'];
			}
			$allowed_hosts[] = $host;
		}

		$switched_locale = switch_to_user_locale( get_current_user_id() );
		$l10n            = array(
			'shiftClickToEdit'  => __( 'Shift-click to edit this element.' ),
			'linkUnpreviewable' => __( 'This link is not live-previewable.' ),
			'formUnpreviewable' => __( 'This form is not live-previewable.' ),
		);
		if ( $switched_locale ) {
			restore_previous_locale();
		}

		$settings = array(
			'changeset'         => array(
				'uuid'      => $this->changeset_uuid(),
				'autosaved' => $this->autosaved(),
			),
			'timeouts'          => array(
				'selectiveRefresh' => 250,
				'keepAliveSend'    => 1000,
			),
			'theme'             => array(
				'stylesheet' => $this->get_stylesheet(),
				'active'     => $this->is_theme_active(),
			),
			'url'               => array(
				'self'          => $self_url,
				'allowed'       => array_map( 'sanitize_url', $this->get_allowed_urls() ),
				'allowedHosts'  => array_unique( $allowed_hosts ),
				'isCrossDomain' => $this->is_cross_domain(),
			),
			'channel'           => $this->messenger_channel,
			'activePanels'      => array(),
			'activeSections'    => array(),
			'activeControls'    => array(),
			'settingValidities' => $exported_setting_validities,
			'nonce'             => current_user_can( 'customize' ) ? $this->get_nonces() : array(),
			'l10n'              => $l10n,
			'_dirty'            => array_keys( $post_values ),
		);

		foreach ( $this->panels as $panel_id => $panel ) {
			if ( $panel->check_capabilities() ) {
				$settings['activePanels'][ $panel_id ] = $panel->active();
				foreach ( $panel->sections as $section_id => $section ) {
					if ( $section->check_capabilities() ) {
						$settings['activeSections'][ $section_id ] = $section->active();
					}
				}
			}
		}
		foreach ( $this->sections as $id => $section ) {
			if ( $section->check_capabilities() ) {
				$settings['activeSections'][ $id ] = $section->active();
			}
		}
		foreach ( $this->controls as $id => $control ) {
			if ( $control->check_capabilities() ) {
				$settings['activeControls'][ $id ] = $control->active();
			}
		}

		ob_start();
		?>
		<script>
			var _wpCustomizeSettings = <?php echo wp_json_encode( $settings ); ?>;
			_wpCustomizeSettings.values = {};
			(function( v ) {
				<?php
				/*
				 * Tuần tự hóa các cài đặt riêng biệt khỏi quá trình tuần tự hóa _wpCustomizeSettings ban đầu
				 * để tránh đỉnh sử dụng bộ nhớ.
				 * @todo Có thể chúng ta không cần xuất các giá trị vì bảng điều khiển đồng bộ chúng.
				 */
				foreach ( $this->settings as $id => $setting ) {
					if ( $setting->check_capabilities() ) {
						printf(
							"v[%s] = %s;\n",
							wp_json_encode( $id ),
							wp_json_encode( $setting->js_value() )
						);
					}
				}
				?>
			})( _wpCustomizeSettings.values );
		</script>
		<?php
		wp_print_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
	}

	/**
	 * In chữ ký để đảm bảo Trình tùy biến đã được thực thi đúng cách.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0
	 */
	public function customize_preview_signature() {
		_deprecated_function( __METHOD__, '4.7.0' );
	}

	/**
	 * Xóa chữ ký trong trường hợp Trình tùy biến không được thực thi đúng cách.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0
	 *
	 * @param callable|null $callback Tùy chọn. Giá trị được truyền qua cho bộ lọc {@see 'wp_die_handler'}.
	 *                                Mặc định null.
	 * @return callable|null Giá trị được truyền qua cho bộ lọc {@see 'wp_die_handler'}.
	 */
	public function remove_preview_signature( $callback = null ) {
		_deprecated_function( __METHOD__, '4.7.0' );

		return $callback;
	}

	/**
	 * Xác định xem có phải đang xem trước giao diện hay không.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True nếu đang xem trước, false nếu không.
	 */
	public function is_preview() {
		return (bool) $this->previewing;
	}

	/**
	 * Lấy tên mẫu của giao diện đang xem trước.
	 *
	 * @since 3.4.0
	 *
	 * @return string Tên mẫu.
	 */
	public function get_template() {
		return $this->theme()->get_template();
	}

	/**
	 * Lấy tên stylesheet của giao diện đang xem trước.
	 *
	 * @since 3.4.0
	 *
	 * @return string Tên stylesheet.
	 */
	public function get_stylesheet() {
		return $this->theme()->get_stylesheet();
	}

	/**
	 * Lấy thư mục gốc mẫu của giao diện đang xem trước.
	 *
	 * @since 3.4.0
	 *
	 * @return string Thư mục gốc giao diện.
	 */
	public function get_template_root() {
		return get_raw_theme_root( $this->get_template(), true );
	}

	/**
	 * Lấy thư mục gốc stylesheet của giao diện đang xem trước.
	 *
	 * @since 3.4.0
	 *
	 * @return string Thư mục gốc giao diện.
	 */
	public function get_stylesheet_root() {
		return get_raw_theme_root( $this->get_stylesheet(), true );
	}

	/**
	 * Lọc giao diện đang hoạt động và trả về tên của giao diện đang xem trước.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $current_theme {@internal Tham số không được sử dụng}
	 * @return string Tên giao diện.
	 */
	public function current_theme( $current_theme ) {
		return $this->theme()->display( 'Name' );
	}

	/**
	 * Xác thực các giá trị cài đặt.
	 *
	 * Việc xác thực bị bỏ qua cho các cài đặt chưa đăng ký hoặc cho các giá trị đã
	 * là null vì chúng sẽ bị bỏ qua. Quá trình làm sạch được áp dụng
	 * cho các giá trị vượt qua xác thực, và các giá trị trở thành null hoặc `WP_Error`
	 * sau khi làm sạch được đánh dấu không hợp lệ.
	 *
	 * @since 4.6.0
	 *
	 * @see WP_REST_Request::has_valid_params()
	 * @see WP_Customize_Setting::validate()
	 *
	 * @param array $setting_values Ánh xạ từ ID cài đặt đến các giá trị cần xác thực và làm sạch.
	 * @param array $options {
	 *     Các tùy chọn.
	 *
	 *     @type bool $validate_existence  Có kiểm tra sự tồn tại của cài đặt hay không.
	 *     @type bool $validate_capability Có kiểm tra quyền của cài đặt hay không.
	 * }
	 * @return array Ánh xạ từ ID cài đặt đến giá trị trả về của phương thức xác thực, `true` hoặc `WP_Error`.
	 */
	public function validate_setting_values( $setting_values, $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'validate_capability' => false,
				'validate_existence'  => false,
			)
		);

		$validities = array();
		foreach ( $setting_values as $setting_id => $unsanitized_value ) {
			$setting = $this->get_setting( $setting_id );
			if ( ! $setting ) {
				if ( $options['validate_existence'] ) {
					$validities[ $setting_id ] = new WP_Error( 'unrecognized', __( 'Setting does not exist or is unrecognized.' ) );
				}
				continue;
			}
			if ( $options['validate_capability'] && ! current_user_can( $setting->capability ) ) {
				$validity = new WP_Error( 'unauthorized', __( 'Unauthorized to modify setting due to capability.' ) );
			} else {
				if ( is_null( $unsanitized_value ) ) {
					continue;
				}
				$validity = $setting->validate( $unsanitized_value );
			}
			if ( ! is_wp_error( $validity ) ) {
				/** Bộ lọc này được ghi chú trong wp-includes/class-wp-customize-setting.php */
				$late_validity = apply_filters( "customize_validate_{$setting->id}", new WP_Error(), $unsanitized_value, $setting );
				if ( is_wp_error( $late_validity ) && $late_validity->has_errors() ) {
					$validity = $late_validity;
				}
			}
			if ( ! is_wp_error( $validity ) ) {
				$value = $setting->sanitize( $unsanitized_value );
				if ( is_null( $value ) ) {
					$validity = false;
				} elseif ( is_wp_error( $value ) ) {
					$validity = $value;
				}
			}
			if ( false === $validity ) {
				$validity = new WP_Error( 'invalid_value', __( 'Invalid value.' ) );
			}
			$validities[ $setting_id ] = $validity;
		}
		return $validities;
	}

	/**
	 * Chuẩn bị tính hợp lệ của cài đặt để xuất ra phía client (JS).
	 *
	 * Chuyển đổi thể hiện `WP_Error` thành mảng phù hợp để truyền vào
	 * mô hình JS `wp.customize.Notification`.
	 *
	 * @since 4.6.0
	 *
	 * @param true|WP_Error $validity Tính hợp lệ của cài đặt.
	 * @return true|array Nếu `$validity` là WP_Error, các mã lỗi sẽ được ánh xạ mảng
	 *                    với `message` và `data` tương ứng để truyền vào
	 *                    mô hình JS `wp.customize.Notification`.
	 */
	public function prepare_setting_validity_for_js( $validity ) {
		if ( is_wp_error( $validity ) ) {
			$notification = array();
			foreach ( $validity->errors as $error_code => $error_messages ) {
				$notification[ $error_code ] = array(
					'message' => implode( ' ', $error_messages ),
					'data'    => $validity->get_error_data( $error_code ),
				);
			}
			return $notification;
		} else {
			return true;
		}
	}

	/**
	 * Xử lý yêu cầu WP Ajax customize_save để lưu/cập nhật changeset.
	 *
	 * @since 3.4.0
	 * @since 4.7.0 Ngữ nghĩa của phương thức này đã thay đổi để cập nhật changeset, tùy chọn cũng thay đổi trạng thái và các thuộc tính khác.
	 */
	public function save() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'unauthenticated' );
		}

		if ( ! $this->is_preview() ) {
			wp_send_json_error( 'not_preview' );
		}

		$action = 'save-customize_' . $this->get_stylesheet();
		if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
			wp_send_json_error( 'invalid_nonce' );
		}

		$changeset_post_id = $this->changeset_post_id();
		$is_new_changeset  = empty( $changeset_post_id );
		if ( $is_new_changeset ) {
			if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->create_posts ) ) {
				wp_send_json_error( 'cannot_create_changeset_post' );
			}
		} else {
			if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $changeset_post_id ) ) {
				wp_send_json_error( 'cannot_edit_changeset_post' );
			}
		}

		if ( ! empty( $_POST['customize_changeset_data'] ) ) {
			$input_changeset_data = json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true );
			if ( ! is_array( $input_changeset_data ) ) {
				wp_send_json_error( 'invalid_customize_changeset_data' );
			}
		} else {
			$input_changeset_data = array();
		}

		// Xác thực tiêu đề.
		$changeset_title = null;
		if ( isset( $_POST['customize_changeset_title'] ) ) {
			$changeset_title = sanitize_text_field( wp_unslash( $_POST['customize_changeset_title'] ) );
		}

		// Xác thực tham số trạng thái changeset.
		$is_publish       = null;
		$changeset_status = null;
		if ( isset( $_POST['customize_changeset_status'] ) ) {
			$changeset_status = wp_unslash( $_POST['customize_changeset_status'] );
			if ( ! get_post_status_object( $changeset_status ) || ! in_array( $changeset_status, array( 'draft', 'pending', 'publish', 'future' ), true ) ) {
				wp_send_json_error( 'bad_customize_changeset_status', 400 );
			}
			$is_publish = ( 'publish' === $changeset_status || 'future' === $changeset_status );
			if ( $is_publish && ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->publish_posts ) ) {
				wp_send_json_error( 'changeset_publish_unauthorized', 403 );
			}
		}

		/*
		 * Xác thực tham số ngày changeset. Ngày được giả định là giờ địa phương cho
		 * WP nếu ở định dạng MySQL (YYYY-MM-DD HH:MM:SS). Ngược lại, ngày
		 * được phân tích bằng strtotime() để có thể cung cấp định dạng ngày ISO
		 * hoặc chuỗi như "+10 minutes".
		 */
		$changeset_date_gmt = null;
		if ( isset( $_POST['customize_changeset_date'] ) ) {
			$changeset_date = wp_unslash( $_POST['customize_changeset_date'] );
			if ( preg_match( '/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $changeset_date ) ) {
				$mm         = substr( $changeset_date, 5, 2 );
				$jj         = substr( $changeset_date, 8, 2 );
				$aa         = substr( $changeset_date, 0, 4 );
				$valid_date = wp_checkdate( $mm, $jj, $aa, $changeset_date );
				if ( ! $valid_date ) {
					wp_send_json_error( 'bad_customize_changeset_date', 400 );
				}
				$changeset_date_gmt = get_gmt_from_date( $changeset_date );
			} else {
				$timestamp = strtotime( $changeset_date );
				if ( ! $timestamp ) {
					wp_send_json_error( 'bad_customize_changeset_date', 400 );
				}
				$changeset_date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp );
			}
		}

		$lock_user_id = null;
		$autosave     = ! empty( $_POST['customize_changeset_autosave'] );
		if ( ! $is_new_changeset ) {
			$lock_user_id = wp_check_post_lock( $this->changeset_post_id() );
		}

		// Buộc yêu cầu tự lưu khi changeset đang bị khóa.
		if ( $lock_user_id && ! $autosave ) {
			$autosave           = true;
			$changeset_status   = null;
			$changeset_date_gmt = null;
		}

		if ( $autosave && ! defined( 'DOING_AUTOSAVE' ) ) { // Back-compat.
			define( 'DOING_AUTOSAVE', true );
		}

		$autosaved = false;
		$r         = $this->save_changeset_post(
			array(
				'status'   => $changeset_status,
				'title'    => $changeset_title,
				'date_gmt' => $changeset_date_gmt,
				'data'     => $input_changeset_data,
				'autosave' => $autosave,
			)
		);
		if ( $autosave && ! is_wp_error( $r ) ) {
			$autosaved = true;
		}

		// Nếu changeset đã bị khóa và yêu cầu tự lưu không phải là lỗi, thì bây giờ trả về rõ ràng với thất bại.
		if ( $lock_user_id && ! is_wp_error( $r ) ) {
			$r = new WP_Error(
				'changeset_locked',
				__( 'Changeset is being edited by other user.' ),
				array(
					'lock_user' => $this->get_lock_user_data( $lock_user_id ),
				)
			);
		}

		if ( is_wp_error( $r ) ) {
			$response = array(
				'message' => $r->get_error_message(),
				'code'    => $r->get_error_code(),
			);
			if ( is_array( $r->get_error_data() ) ) {
				$response = array_merge( $response, $r->get_error_data() );
			} else {
				$response['data'] = $r->get_error_data();
			}
		} else {
			$response       = $r;
			$changeset_post = get_post( $this->changeset_post_id() );

			// Hủy bỏ tất cả các bài viết changeset bản nháp tự động khác cho người dùng này (chúng hoạt động như các bản sửa đổi tự lưu), vì chỉ nên có một.
			if ( $is_new_changeset ) {
				$this->dismiss_user_auto_draft_changesets();
			}

			// Lưu ý rằng nếu trạng thái changeset là publish, thì nó sẽ được đặt thành Thùng rác nếu bản sửa đổi không được hỗ trợ.
			$response['changeset_status'] = $changeset_post->post_status;
			if ( $is_publish && 'trash' === $response['changeset_status'] ) {
				$response['changeset_status'] = 'publish';
			}

			if ( 'publish' !== $response['changeset_status'] ) {
				$this->set_changeset_lock( $changeset_post->ID );
			}

			if ( 'future' === $response['changeset_status'] ) {
				$response['changeset_date'] = $changeset_post->post_date;
			}

			if ( 'publish' === $response['changeset_status'] || 'trash' === $response['changeset_status'] ) {
				$response['next_changeset_uuid'] = wp_generate_uuid4();
			}
		}

		if ( $autosave ) {
			$response['autosaved'] = $autosaved;
		}

		if ( isset( $response['setting_validities'] ) ) {
			$response['setting_validities'] = array_map( array( $this, 'prepare_setting_validity_for_js' ), $response['setting_validities'] );
		}

		/**
		 * Lọc dữ liệu phản hồi cho yêu cầu Ajax customize_save thành công.
		 *
		 * Bộ lọc này không áp dụng nếu có lỗi nonce hoặc xác thực.
		 *
		 * @since 4.2.0
		 *
		 * @param array                $response Thông tin bổ sung được trả về sự kiện 'saved'
		 *                                       trên `wp.customize`.
		 * @param WP_Customize_Manager $manager  Thể hiện WP_Customize_Manager.
		 */
		$response = apply_filters( 'customize_save_response', $response, $this );

		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $response );
		} else {
			wp_send_json_success( $response );
		}
	}

	/**
	 * Lưu bài viết cho changeset đã tải.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args {
	 *     Tham số cho bài viết changeset.
	 *
	 *     @type array  $data            Dữ liệu changeset bổ sung tùy chọn. Giá trị sẽ được gộp lên trên bất kỳ giá trị bài viết hiện có nào.
	 *     @type string $status          Trạng thái bài viết. Tùy chọn. Nếu được cung cấp, quá trình lưu sẽ là giao dịch và bản sửa đổi bài viết sẽ được cho phép.
	 *     @type string $title           Tiêu đề bài viết. Tùy chọn.
	 *     @type string $date_gmt        Ngày theo GMT. Tùy chọn.
	 *     @type int    $user_id         ID người dùng đang lưu changeset. Tùy chọn, mặc định là ID người dùng hiện tại.
	 *     @type bool   $starter_content Dữ liệu có phải nội dung khởi đầu hay không. Nếu false (mặc định), $starter_content sẽ bị xóa cho bất kỳ $data nào được lưu.
	 *     @type bool   $autosave        Đây có phải là yêu cầu tạo bản sửa đổi tự lưu hay không.
	 * }
	 *
	 * @return array|WP_Error Trả về mảng khi thành công và WP_Error với dữ liệu mảng khi lỗi.
	 */
	public function save_changeset_post( $args = array() ) {

		$args = array_merge(
			array(
				'status'          => null,
				'title'           => null,
				'data'            => array(),
				'date_gmt'        => null,
				'user_id'         => get_current_user_id(),
				'starter_content' => false,
				'autosave'        => false,
			),
			$args
		);

		$changeset_post_id       = $this->changeset_post_id();
		$existing_changeset_data = array();
		if ( $changeset_post_id ) {
			$existing_status = get_post_status( $changeset_post_id );
			if ( 'publish' === $existing_status || 'trash' === $existing_status ) {
				return new WP_Error(
					'changeset_already_published',
					__( 'The previous set of changes has already been published. Please try saving your current set of changes again.' ),
					array(
						'next_changeset_uuid' => wp_generate_uuid4(),
					)
				);
			}

			$existing_changeset_data = $this->get_changeset_post_data( $changeset_post_id );
			if ( is_wp_error( $existing_changeset_data ) ) {
				return $existing_changeset_data;
			}
		}

		// Thất bại nếu cố gắng xuất bản nhưng hook xuất bản bị thiếu.
		if ( 'publish' === $args['status'] && false === has_action( 'transition_post_status', '_wp_customize_publish_changeset' ) ) {
			return new WP_Error( 'missing_publish_callback' );
		}

		// Xác thực ngày.
		$now = gmdate( 'Y-m-d H:i:59' );
		if ( $args['date_gmt'] ) {
			$is_future_dated = ( mysql2date( 'U', $args['date_gmt'], false ) > mysql2date( 'U', $now, false ) );
			if ( ! $is_future_dated ) {
				return new WP_Error( 'not_future_date', __( 'You must supply a future date to schedule.' ) ); // Only future dates are allowed.
			}

			if ( ! $this->is_theme_active() && ( 'future' === $args['status'] || $is_future_dated ) ) {
				return new WP_Error( 'cannot_schedule_theme_switches' ); // This should be allowed in the future, when theme is a regular setting.
			}
			$will_remain_auto_draft = ( ! $args['status'] && ( ! $changeset_post_id || 'auto-draft' === get_post_status( $changeset_post_id ) ) );
			if ( $will_remain_auto_draft ) {
				return new WP_Error( 'cannot_supply_date_for_auto_draft_changeset' );
			}
		} elseif ( $changeset_post_id && 'future' === $args['status'] ) {

			// Thất bại nếu trạng thái mới là future nhưng ngày của bài viết hiện tại không ở trong tương lai.
			$changeset_post = get_post( $changeset_post_id );
			if ( mysql2date( 'U', $changeset_post->post_date_gmt, false ) <= mysql2date( 'U', $now, false ) ) {
				return new WP_Error( 'not_future_date', __( 'You must supply a future date to schedule.' ) );
			}
		}

		if ( ! empty( $is_future_dated ) && 'publish' === $args['status'] ) {
			$args['status'] = 'future';
		}

		// Xác thực tham số tự lưu. Xem _wp_post_revision_fields() để biết tại sao các trường này không được phép.
		if ( $args['autosave'] ) {
			if ( $args['date_gmt'] ) {
				return new WP_Error( 'illegal_autosave_with_date_gmt' );
			} elseif ( $args['status'] ) {
				return new WP_Error( 'illegal_autosave_with_status' );
			} elseif ( $args['user_id'] && get_current_user_id() !== $args['user_id'] ) {
				return new WP_Error( 'illegal_autosave_with_non_current_user' );
			}
		}

		// Yêu cầu được thực hiện thông qua wp.customize.previewer.save().
		$update_transactionally = (bool) $args['status'];
		$allow_revision         = (bool) $args['status'];

		// Bổ sung giá trị bài viết với bất kỳ dữ liệu nào được cung cấp.
		foreach ( $args['data'] as $setting_id => $setting_params ) {
			if ( is_array( $setting_params ) && array_key_exists( 'value', $setting_params ) ) {
				$this->set_post_value( $setting_id, $setting_params['value'] ); // Add to post values so that they can be validated and sanitized.
			}
		}

		// Lưu ý rằng ngoài dữ liệu bài viết, điều này sẽ bao gồm bất kỳ theme mod đã được lưu trữ tạm nào.
		$post_values = $this->unsanitized_post_values(
			array(
				'exclude_changeset' => true,
				'exclude_post_data' => false,
			)
		);
		$this->add_dynamic_settings( array_keys( $post_values ) ); // Ensure settings get created even if they lack an input value.

		/*
		 * Lấy danh sách ID cho các cài đặt có giá trị khác với giá trị hiện tại
		 * đã lưu trong changeset. Bằng cách bỏ qua các giá trị đã giống nhau, tập con
		 * của các cài đặt đã thay đổi có thể được truyền vào validate_setting_values để ngăn
		 * người dùng không đủ quyền sửa đổi một cài đặt mà họ có khả năng
		 * bị chặn lưu. Điều này cũng ngăn người dùng chạm vào các cài đặt
		 * đã lưu trước đó và ghi đè user_id liên quan nếu họ không thay đổi gì.
		 */
		$changed_setting_ids = array();
		foreach ( $post_values as $setting_id => $setting_value ) {
			$setting = $this->get_setting( $setting_id );

			if ( $setting && 'theme_mod' === $setting->type ) {
				$prefixed_setting_id = $this->get_stylesheet() . '::' . $setting->id;
			} else {
				$prefixed_setting_id = $setting_id;
			}

			$is_value_changed = (
				! isset( $existing_changeset_data[ $prefixed_setting_id ] )
				||
				! array_key_exists( 'value', $existing_changeset_data[ $prefixed_setting_id ] )
				||
				$existing_changeset_data[ $prefixed_setting_id ]['value'] !== $setting_value
			);
			if ( $is_value_changed ) {
				$changed_setting_ids[] = $setting_id;
			}
		}

		/**
		 * Kích hoạt trước khi xác thực lưu diễn ra.
		 *
		 * Plugin có thể thêm bộ lọc {@see 'customize_validate_{$this->ID}'} đúng lúc
		 * tại thời điểm này để bắt bất kỳ cài đặt nào được đăng ký sau `customize_register`.
		 * Phần động của tên hook, `$this->ID` tham chiếu đến ID cài đặt.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Customize_Manager $manager Thể hiện WP_Customize_Manager.
		 */
		do_action( 'customize_save_validation_before', $this );

		// Xác thực các cài đặt.
		$validated_values      = array_merge(
			array_fill_keys( array_keys( $args['data'] ), null ), // Make sure existence/capability checks are done on value-less setting updates.
			$post_values
		);
		$setting_validities    = $this->validate_setting_values(
			$validated_values,
			array(
				'validate_capability' => true,
				'validate_existence'  => true,
			)
		);
		$invalid_setting_count = count( array_filter( $setting_validities, 'is_wp_error' ) );

		/*
		 * Dừng sớm nếu có các cài đặt không hợp lệ khi cập nhật là giao dịch.
		 * Cập nhật changeset là giao dịch khi trạng thái được cung cấp trong yêu cầu.
		 */
		if ( $update_transactionally && $invalid_setting_count > 0 ) {
			$response = array(
				'setting_validities' => $setting_validities,
				/* translators: %s: Number of invalid settings. */
				'message'            => sprintf( _n( 'Unable to save due to %s invalid setting.', 'Unable to save due to %s invalid settings.', $invalid_setting_count ), number_format_i18n( $invalid_setting_count ) ),
			);
			return new WP_Error( 'transaction_fail', '', $response );
		}

		// Lấy/gộp dữ liệu cho changeset.
		$original_changeset_data = $this->get_changeset_post_data( $changeset_post_id );
		$data                    = $original_changeset_data;
		if ( is_wp_error( $data ) ) {
			$data = array();
		}

		// Đảm bảo rằng tất cả giá trị bài viết được bao gồm trong dữ liệu changeset.
		foreach ( $post_values as $setting_id => $post_value ) {
			if ( ! isset( $args['data'][ $setting_id ] ) ) {
				$args['data'][ $setting_id ] = array();
			}
			if ( ! isset( $args['data'][ $setting_id ]['value'] ) ) {
				$args['data'][ $setting_id ]['value'] = $post_value;
			}
		}

		foreach ( $args['data'] as $setting_id => $setting_params ) {
			$setting = $this->get_setting( $setting_id );
			if ( ! $setting || ! $setting->check_capabilities() ) {
				continue;
			}

			// Bỏ qua cập nhật changeset cho các giá trị cài đặt không hợp lệ.
			if ( isset( $setting_validities[ $setting_id ] ) && is_wp_error( $setting_validities[ $setting_id ] ) ) {
				continue;
			}

			$changeset_setting_id = $setting_id;
			if ( 'theme_mod' === $setting->type ) {
				$changeset_setting_id = sprintf( '%s::%s', $this->get_stylesheet(), $setting_id );
			}

			if ( null === $setting_params ) {
				// Xóa hoàn toàn cài đặt khỏi changeset.
				unset( $data[ $changeset_setting_id ] );
			} else {

				if ( ! isset( $data[ $changeset_setting_id ] ) ) {
					$data[ $changeset_setting_id ] = array();
				}

				// Gộp bất kỳ tham số cài đặt bổ sung nào đã được cung cấp với các tham số hiện có.
				$merged_setting_params = array_merge( $data[ $changeset_setting_id ], $setting_params );

				// Bỏ qua cập nhật tham số cài đặt nếu không thay đổi (đảm bảo user_id không bị ghi đè).
				if ( $data[ $changeset_setting_id ] === $merged_setting_params ) {
					continue;
				}

				$data[ $changeset_setting_id ] = array_merge(
					$merged_setting_params,
					array(
						'type'              => $setting->type,
						'user_id'           => $args['user_id'],
						'date_modified_gmt' => current_time( 'mysql', true ),
					)
				);

				// Xóa cờ starter_content trong dữ liệu nếu changeset không được cập nhật rõ ràng cho nội dung khởi đầu.
				if ( empty( $args['starter_content'] ) ) {
					unset( $data[ $changeset_setting_id ]['starter_content'] );
				}
			}
		}

		$filter_context = array(
			'uuid'          => $this->changeset_uuid(),
			'title'         => $args['title'],
			'status'        => $args['status'],
			'date_gmt'      => $args['date_gmt'],
			'post_id'       => $changeset_post_id,
			'previous_data' => is_wp_error( $original_changeset_data ) ? array() : $original_changeset_data,
			'manager'       => $this,
		);

		/**
		 * Lọc dữ liệu cài đặt sẽ được lưu trữ vào changeset.
		 *
		 * Plugin có thể bổ sung dữ liệu thêm (chẳng hạn như meta bổ sung cho cài đặt) vào changeset bằng bộ lọc này.
		 *
		 * @since 4.7.0
		 *
		 * @param array $data Dữ liệu changeset đã cập nhật, ánh xạ ID cài đặt tới các mảng chứa mục $value và tùy chọn siêu dữ liệu khác.
		 * @param array $context {
		 *     Ngữ cảnh bộ lọc.
		 *
		 *     @type string               $uuid          UUID changeset.
		 *     @type string               $title         Tiêu đề được yêu cầu cho bài viết changeset.
		 *     @type string               $status        Trạng thái được yêu cầu cho bài viết changeset.
		 *     @type string               $date_gmt      Ngày được yêu cầu cho bài viết changeset theo định dạng MySQL và múi giờ GMT.
		 *     @type int|false            $post_id       ID bài viết cho changeset, hoặc false nếu chưa tồn tại.
		 *     @type array                $previous_data Dữ liệu trước đó chứa trong changeset.
		 *     @type WP_Customize_Manager $manager       Thể hiện Trình quản lý.
		 * }
		 */
		$data = apply_filters( 'customize_changeset_save_data', $data, $filter_context );

		// Chuyển giao diện nếu đang xuất bản thay đổi ngay bây giờ.
		if ( 'publish' === $args['status'] && ! $this->is_theme_active() ) {
			// Tạm thời dừng xem trước giao diện để cho phép switch_themes() hoạt động đúng cách.
			$this->stop_previewing_theme();
			switch_theme( $this->get_stylesheet() );
			update_option( 'theme_switched_via_customizer', true );
			$this->start_previewing_theme();
		}

		// Thu thập dữ liệu cho wp_insert_post()/wp_update_post().
		$post_array = array(
			// JSON_UNESCAPED_SLASHES is only to improve readability as slashes needn't be escaped in storage.
			'post_content' => wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ),
		);
		if ( $args['title'] ) {
			$post_array['post_title'] = $args['title'];
		}
		if ( $changeset_post_id ) {
			$post_array['ID'] = $changeset_post_id;
		} else {
			$post_array['post_type']   = 'customize_changeset';
			$post_array['post_name']   = $this->changeset_uuid();
			$post_array['post_status'] = 'auto-draft';
		}
		if ( $args['status'] ) {
			$post_array['post_status'] = $args['status'];
		}

		// Đặt lại ngày bài viết về hiện tại nếu đang xuất bản, ngược lại truyền post_date_gmt và chuyển đổi cho post_date.
		if ( 'publish' === $args['status'] ) {
			$post_array['post_date_gmt'] = '0000-00-00 00:00:00';
			$post_array['post_date']     = '0000-00-00 00:00:00';
		} elseif ( $args['date_gmt'] ) {
			$post_array['post_date_gmt'] = $args['date_gmt'];
			$post_array['post_date']     = get_date_from_gmt( $args['date_gmt'] );
		} elseif ( $changeset_post_id && 'auto-draft' === get_post_status( $changeset_post_id ) ) {
			/*
			 * Keep bumping the date for the auto-draft whenever it is modified;
			 * this extends its life, preserving it from garbage-collection via
			 * wp_delete_auto_drafts().
			 */
			$post_array['post_date']     = current_time( 'mysql' );
			$post_array['post_date_gmt'] = '';
		}

		$this->store_changeset_revision = $allow_revision;
		add_filter( 'wp_save_post_revision_post_has_changed', array( $this, '_filter_revision_post_has_changed' ), 5, 3 );

		/*
		 * Update the changeset post. The publish_customize_changeset action will cause the settings in the
		 * changeset to be saved via WP_Customize_Setting::save(). Updating a post with publish status will
		 * trigger WP_Customize_Manager::publish_changeset_values().
		 */
		add_filter( 'wp_insert_post_data', array( $this, 'preserve_insert_changeset_post_content' ), 5, 3 );
		if ( $changeset_post_id ) {
			if ( $args['autosave'] && 'auto-draft' !== get_post_status( $changeset_post_id ) ) {
				// See _wp_translate_postdata() for why this is required as it will use the edit_post meta capability.
				add_filter( 'map_meta_cap', array( $this, 'grant_edit_post_capability_for_changeset' ), 10, 4 );

				$post_array['post_ID']   = $post_array['ID'];
				$post_array['post_type'] = 'customize_changeset';

				$r = wp_create_post_autosave( wp_slash( $post_array ) );

				remove_filter( 'map_meta_cap', array( $this, 'grant_edit_post_capability_for_changeset' ), 10 );
			} else {
				$post_array['edit_date'] = true; // Prevent date clearing.

				$r = wp_update_post( wp_slash( $post_array ), true );

				// Delete autosave revision for user when the changeset is updated.
				if ( ! empty( $args['user_id'] ) ) {
					$autosave_draft = wp_get_post_autosave( $changeset_post_id, $args['user_id'] );
					if ( $autosave_draft ) {
						wp_delete_post( $autosave_draft->ID, true );
					}
				}
			}
		} else {
			$r = wp_insert_post( wp_slash( $post_array ), true );
			if ( ! is_wp_error( $r ) ) {
				$this->_changeset_post_id = $r; // Update cached post ID for the loaded changeset.
			}
		}
		remove_filter( 'wp_insert_post_data', array( $this, 'preserve_insert_changeset_post_content' ), 5 );

		$this->_changeset_data = null; // Reset so WP_Customize_Manager::changeset_data() will re-populate with updated contents.

		remove_filter( 'wp_save_post_revision_post_has_changed', array( $this, '_filter_revision_post_has_changed' ) );

		$response = array(
			'setting_validities' => $setting_validities,
		);

		if ( is_wp_error( $r ) ) {
			$response['changeset_post_save_failure'] = $r->get_error_code();
			return new WP_Error( 'changeset_post_save_failure', '', $response );
		}

		return $response;
	}

	/**
	 * Preserves the initial JSON post_content passed to save into the post.
	 *
	 * This is needed to prevent KSES and other {@see 'content_save_pre'} filters
	 * from corrupting JSON data.
	 *
	 * Note that WP_Customize_Manager::validate_setting_values() have already
	 * run on the setting values being serialized as JSON into the post content
	 * so it is pre-sanitized.
	 *
	 * Also, the sanitization logic is re-run through the respective
	 * WP_Customize_Setting::sanitize() method when being read out of the
	 * changeset, via WP_Customize_Manager::post_value(), and this sanitized
	 * value will also be sent into WP_Customize_Setting::update() for
	 * persisting to the DB.
	 *
	 * Multiple users can collaborate on a single changeset, where one user may
	 * have the unfiltered_html capability but another may not. A user with
	 * unfiltered_html may add a script tag to some field which needs to be kept
	 * intact even when another user updates the changeset to modify another field
	 * when they do not have unfiltered_html.
	 *
	 * @since 5.4.1
	 *
	 * @param array $data                An array of slashed and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as originally passed to wp_insert_post().
	 * @return array Filtered post data.
	 */
	public function preserve_insert_changeset_post_content( $data, $postarr, $unsanitized_postarr ) {
		if (
			isset( $data['post_type'] ) &&
			isset( $unsanitized_postarr['post_content'] ) &&
			'customize_changeset' === $data['post_type'] ||
			(
				'revision' === $data['post_type'] &&
				! empty( $data['post_parent'] ) &&
				'customize_changeset' === get_post_type( $data['post_parent'] )
			)
		) {
			$data['post_content'] = $unsanitized_postarr['post_content'];
		}
		return $data;
	}

	/**
	 * Trashes or deletes a changeset post.
	 *
	 * The following re-formulates the logic from `wp_trash_post()` as done in
	 * `wp_publish_post()`. The reason for bypassing `wp_trash_post()` is that it
	 * will mutate the the `post_content` and the `post_name` when they should be
	 * untouched.
	 *
	 * @since 4.9.0
	 *
	 * @see wp_trash_post()
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int|WP_Post $post The changeset post.
	 * @return mixed A WP_Post object for the trashed post or an empty value on failure.
	 */
	public function trash_changeset_post( $post ) {
		global $wpdb;

		$post = get_post( $post );

		if ( ! ( $post instanceof WP_Post ) ) {
			return $post;
		}
		$post_id = $post->ID;

		if ( ! EMPTY_TRASH_DAYS ) {
			return wp_delete_post( $post_id, true );
		}

		if ( 'trash' === get_post_status( $post ) ) {
			return false;
		}

		$previous_status = $post->post_status;

		/** This filter is documented in wp-includes/post.php */
		$check = apply_filters( 'pre_trash_post', null, $post, $previous_status );
		if ( null !== $check ) {
			return $check;
		}

		/** This action is documented in wp-includes/post.php */
		do_action( 'wp_trash_post', $post_id, $previous_status );

		add_post_meta( $post_id, '_wp_trash_meta_status', $previous_status );
		add_post_meta( $post_id, '_wp_trash_meta_time', time() );

		$new_status = 'trash';
		$wpdb->update( $wpdb->posts, array( 'post_status' => $new_status ), array( 'ID' => $post->ID ) );
		clean_post_cache( $post->ID );

		$post->post_status = $new_status;
		wp_transition_post_status( $new_status, $previous_status, $post );

		/** This action is documented in wp-includes/post.php */
		do_action( "edit_post_{$post->post_type}", $post->ID, $post );

		/** This action is documented in wp-includes/post.php */
		do_action( 'edit_post', $post->ID, $post );

		/** This action is documented in wp-includes/post.php */
		do_action( "save_post_{$post->post_type}", $post->ID, $post, true );

		/** This action is documented in wp-includes/post.php */
		do_action( 'save_post', $post->ID, $post, true );

		/** This action is documented in wp-includes/post.php */
		do_action( 'wp_insert_post', $post->ID, $post, true );

		wp_after_insert_post( get_post( $post_id ), true, $post );

		wp_trash_post_comments( $post_id );

		/** This action is documented in wp-includes/post.php */
		do_action( 'trashed_post', $post_id, $previous_status );

		return $post;
	}

	/**
	 * Handles request to trash a changeset.
	 *
	 * @since 4.9.0
	 */
	public function handle_changeset_trash_request() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'unauthenticated' );
		}

		if ( ! $this->is_preview() ) {
			wp_send_json_error( 'not_preview' );
		}

		if ( ! check_ajax_referer( 'trash_customize_changeset', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_nonce',
					'message' => __( 'There was an authentication problem. Please reload and try again.' ),
				)
			);
		}

		$changeset_post_id = $this->changeset_post_id();

		if ( ! $changeset_post_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'No changes saved yet, so there is nothing to trash.' ),
					'code'    => 'non_existent_changeset',
				)
			);
			return;
		}

		if ( $changeset_post_id ) {
			if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->delete_post, $changeset_post_id ) ) {
				wp_send_json_error(
					array(
						'code'    => 'changeset_trash_unauthorized',
						'message' => __( 'Unable to trash changes.' ),
					)
				);
			}

			$lock_user = (int) wp_check_post_lock( $changeset_post_id );

			if ( $lock_user && get_current_user_id() !== $lock_user ) {
				wp_send_json_error(
					array(
						'code'     => 'changeset_locked',
						'message'  => __( 'Changeset is being edited by other user.' ),
						'lockUser' => $this->get_lock_user_data( $lock_user ),
					)
				);
			}
		}

		if ( 'trash' === get_post_status( $changeset_post_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Changes have already been trashed.' ),
					'code'    => 'changeset_already_trashed',
				)
			);
			return;
		}

		$r = $this->trash_changeset_post( $changeset_post_id );
		if ( ! ( $r instanceof WP_Post ) ) {
			wp_send_json_error(
				array(
					'code'    => 'changeset_trash_failure',
					'message' => __( 'Unable to trash changes.' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Changes trashed successfully.' ),
			)
		);
	}

	/**
	 * Re-maps 'edit_post' meta cap for a customize_changeset post to be the same as 'customize' maps.
	 *
	 * There is essentially a "meta meta" cap in play here, where 'edit_post' meta cap maps to
	 * the 'customize' meta cap which then maps to 'edit_theme_options'. This is currently
	 * required in core for `wp_create_post_autosave()` because it will call
	 * `_wp_translate_postdata()` which in turn will check if a user can 'edit_post', but the
	 * the caps for the customize_changeset post type are all mapping to the meta capability.
	 * This should be able to be removed once #40922 is addressed in core.
	 *
	 * @since 4.9.0
	 *
	 * @link https://core.trac.wordpress.org/ticket/40922
	 * @see WP_Customize_Manager::save_changeset_post()
	 * @see _wp_translate_postdata()
	 *
	 * @param string[] $caps    Array of the user's capabilities.
	 * @param string   $cap     Capability name.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds the context to the cap. Typically the object ID.
	 * @return array Capabilities.
	 */
	public function grant_edit_post_capability_for_changeset( $caps, $cap, $user_id, $args ) {
		if ( 'edit_post' === $cap && ! empty( $args[0] ) && 'customize_changeset' === get_post_type( $args[0] ) ) {
			$post_type_obj = get_post_type_object( 'customize_changeset' );
			$caps          = map_meta_cap( $post_type_obj->cap->$cap, $user_id );
		}
		return $caps;
	}

	/**
	 * Marks the changeset post as being currently edited by the current user.
	 *
	 * @since 4.9.0
	 *
	 * @param int  $changeset_post_id Changeset post ID.
	 * @param bool $take_over Whether to take over the changeset. Default false.
	 */
	public function set_changeset_lock( $changeset_post_id, $take_over = false ) {
		if ( $changeset_post_id ) {
			$can_override = ! (bool) get_post_meta( $changeset_post_id, '_edit_lock', true );

			if ( $take_over ) {
				$can_override = true;
			}

			if ( $can_override ) {
				$lock = sprintf( '%s:%s', time(), get_current_user_id() );
				update_post_meta( $changeset_post_id, '_edit_lock', $lock );
			} else {
				$this->refresh_changeset_lock( $changeset_post_id );
			}
		}
	}

	/**
	 * Refreshes changeset lock with the current time if current user edited the changeset before.
	 *
	 * @since 4.9.0
	 *
	 * @param int $changeset_post_id Changeset post ID.
	 */
	public function refresh_changeset_lock( $changeset_post_id ) {
		if ( ! $changeset_post_id ) {
			return;
		}

		$lock = get_post_meta( $changeset_post_id, '_edit_lock', true );
		$lock = explode( ':', $lock );

		if ( $lock && ! empty( $lock[1] ) ) {
			$user_id         = (int) $lock[1];
			$current_user_id = get_current_user_id();
			if ( $user_id === $current_user_id ) {
				$lock = sprintf( '%s:%s', time(), $user_id );
				update_post_meta( $changeset_post_id, '_edit_lock', $lock );
			}
		}
	}

	/**
	 * Filters heartbeat settings for the Customizer.
	 *
	 * @since 4.9.0
	 *
	 * @global string $pagenow The filename of the current screen.
	 *
	 * @param array $settings Current settings to filter.
	 * @return array Heartbeat settings.
	 */
	public function add_customize_screen_to_heartbeat_settings( $settings ) {
		global $pagenow;

		if ( 'customize.php' === $pagenow ) {
			$settings['screenId'] = 'customize';
		}

		return $settings;
	}

	/**
	 * Gets lock user data.
	 *
	 * @since 4.9.0
	 *
	 * @param int $user_id User ID.
	 * @return array|null User data formatted for client.
	 */
	protected function get_lock_user_data( $user_id ) {
		if ( ! $user_id ) {
			return null;
		}

		$lock_user = get_userdata( $user_id );

		if ( ! $lock_user ) {
			return null;
		}

		$user_details = array(
			'id'   => $lock_user->ID,
			'name' => $lock_user->display_name,
		);

		if ( get_option( 'show_avatars' ) ) {
			$user_details['avatar'] = get_avatar_url( $lock_user->ID, array( 'size' => 128 ) );
		}

		return $user_details;
	}

	/**
	 * Checks locked changeset with heartbeat API.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $response  The Heartbeat response.
	 * @param array  $data      The $_POST data sent.
	 * @param string $screen_id The screen id.
	 * @return array The Heartbeat response.
	 */
	public function check_changeset_lock_with_heartbeat( $response, $data, $screen_id ) {
		if ( isset( $data['changeset_uuid'] ) ) {
			$changeset_post_id = $this->find_changeset_post_id( $data['changeset_uuid'] );
		} else {
			$changeset_post_id = $this->changeset_post_id();
		}

		if (
			array_key_exists( 'check_changeset_lock', $data )
			&& 'customize' === $screen_id
			&& $changeset_post_id
			&& current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $changeset_post_id )
		) {
			$lock_user_id = wp_check_post_lock( $changeset_post_id );

			if ( $lock_user_id ) {
				$response['customize_changeset_lock_user'] = $this->get_lock_user_data( $lock_user_id );
			} else {

				// Refreshing time will ensure that the user is sitting on customizer and has not closed the customizer tab.
				$this->refresh_changeset_lock( $changeset_post_id );
			}
		}

		return $response;
	}

	/**
	 * Removes changeset lock when take over request is sent via Ajax.
	 *
	 * @since 4.9.0
	 */
	public function handle_override_changeset_lock_request() {
		if ( ! $this->is_preview() ) {
			wp_send_json_error( 'not_preview', 400 );
		}

		if ( ! check_ajax_referer( 'customize_override_changeset_lock', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_nonce',
					'message' => __( 'Security check failed.' ),
				)
			);
		}

		$changeset_post_id = $this->changeset_post_id();

		if ( empty( $changeset_post_id ) ) {
			wp_send_json_error(
				array(
					'code'    => 'no_changeset_found_to_take_over',
					'message' => __( 'No changeset found to take over' ),
				)
			);
		}

		if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $changeset_post_id ) ) {
			wp_send_json_error(
				array(
					'code'    => 'cannot_remove_changeset_lock',
					'message' => __( 'Sorry, you are not allowed to take over.' ),
				)
			);
		}

		$this->set_changeset_lock( $changeset_post_id, true );

		wp_send_json_success( 'changeset_taken_over' );
	}

	/**
	 * Determines whether a changeset revision should be made.
	 *
	 * @since 4.7.0
	 * @var bool
	 */
	protected $store_changeset_revision;

	/**
	 * Filters whether a changeset has changed to create a new revision.
	 *
	 * Note that this will not be called while a changeset post remains in auto-draft status.
	 *
	 * @since 4.7.0
	 *
	 * @param bool    $post_has_changed Whether the post has changed.
	 * @param WP_Post $latest_revision  The latest revision post object.
	 * @param WP_Post $post             The post object.
	 * @return bool Whether a revision should be made.
	 */
	public function _filter_revision_post_has_changed( $post_has_changed, $latest_revision, $post ) {
		unset( $latest_revision );
		if ( 'customize_changeset' === $post->post_type ) {
			$post_has_changed = $this->store_changeset_revision;
		}
		return $post_has_changed;
	}

	/**
	 * Publishes the values of a changeset.
	 *
	 * This will publish the values contained in a changeset, even changesets that do not
	 * correspond to current manager instance. This is called by
	 * `_wp_customize_publish_changeset()` when a customize_changeset post is
	 * transitioned to the `publish` status. As such, this method should not be
	 * called directly and instead `wp_publish_post()` should be used.
	 *
	 * Please note that if the settings in the changeset are for a non-activated
	 * theme, the theme must first be switched to (via `switch_theme()`) before
	 * invoking this method.
	 *
	 * @since 4.7.0
	 *
	 * @see _wp_customize_publish_changeset()
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $changeset_post_id ID for customize_changeset post. Defaults to the changeset for the current manager instance.
	 * @return true|WP_Error True or error info.
	 */
	public function _publish_changeset_values( $changeset_post_id ) {
		global $wpdb;

		$publishing_changeset_data = $this->get_changeset_post_data( $changeset_post_id );
		if ( is_wp_error( $publishing_changeset_data ) ) {
			return $publishing_changeset_data;
		}

		$changeset_post = get_post( $changeset_post_id );

		/*
		 * Temporarily override the changeset context so that it will be read
		 * in calls to unsanitized_post_values() and so that it will be available
		 * on the $wp_customize object passed to hooks during the save logic.
		 */
		$previous_changeset_post_id = $this->_changeset_post_id;
		$this->_changeset_post_id   = $changeset_post_id;
		$previous_changeset_uuid    = $this->_changeset_uuid;
		$this->_changeset_uuid      = $changeset_post->post_name;
		$previous_changeset_data    = $this->_changeset_data;
		$this->_changeset_data      = $publishing_changeset_data;

		// Parse changeset data to identify theme mod settings and user IDs associated with settings to be saved.
		$setting_user_ids   = array();
		$theme_mod_settings = array();
		$namespace_pattern  = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
		$matches            = array();
		foreach ( $this->_changeset_data as $raw_setting_id => $setting_params ) {
			$actual_setting_id    = null;
			$is_theme_mod_setting = (
				isset( $setting_params['value'] )
				&&
				isset( $setting_params['type'] )
				&&
				'theme_mod' === $setting_params['type']
				&&
				preg_match( $namespace_pattern, $raw_setting_id, $matches )
			);
			if ( $is_theme_mod_setting ) {
				if ( ! isset( $theme_mod_settings[ $matches['stylesheet'] ] ) ) {
					$theme_mod_settings[ $matches['stylesheet'] ] = array();
				}
				$theme_mod_settings[ $matches['stylesheet'] ][ $matches['setting_id'] ] = $setting_params;

				if ( $this->get_stylesheet() === $matches['stylesheet'] ) {
					$actual_setting_id = $matches['setting_id'];
				}
			} else {
				$actual_setting_id = $raw_setting_id;
			}

			// Keep track of the user IDs for settings actually for this theme.
			if ( $actual_setting_id && isset( $setting_params['user_id'] ) ) {
				$setting_user_ids[ $actual_setting_id ] = $setting_params['user_id'];
			}
		}

		$changeset_setting_values = $this->unsanitized_post_values(
			array(
				'exclude_post_data' => true,
				'exclude_changeset' => false,
			)
		);
		$changeset_setting_ids    = array_keys( $changeset_setting_values );
		$this->add_dynamic_settings( $changeset_setting_ids );

		/**
		 * Fires once the theme has switched in the Customizer, but before settings
		 * have been saved.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
		 */
		do_action( 'customize_save', $this );

		/*
		 * Ensure that all settings will allow themselves to be saved. Note that
		 * this is safe because the setting would have checked the capability
		 * when the setting value was written into the changeset. So this is why
		 * an additional capability check is not required here.
		 */
		$original_setting_capabilities = array();
		foreach ( $changeset_setting_ids as $setting_id ) {
			$setting = $this->get_setting( $setting_id );
			if ( $setting && ! isset( $setting_user_ids[ $setting_id ] ) ) {
				$original_setting_capabilities[ $setting->id ] = $setting->capability;
				$setting->capability                           = 'exist';
			}
		}

		$original_user_id = get_current_user_id();
		foreach ( $changeset_setting_ids as $setting_id ) {
			$setting = $this->get_setting( $setting_id );
			if ( $setting ) {
				/*
				 * Set the current user to match the user who saved the value into
				 * the changeset so that any filters that apply during the save
				 * process will respect the original user's capabilities. This
				 * will ensure, for example, that KSES won't strip unsafe HTML
				 * when a scheduled changeset publishes via WP Cron.
				 */
				if ( isset( $setting_user_ids[ $setting_id ] ) ) {
					wp_set_current_user( $setting_user_ids[ $setting_id ] );
				} else {
					wp_set_current_user( $original_user_id );
				}

				$setting->save();
			}
		}
		wp_set_current_user( $original_user_id );

		// Update the stashed theme mod settings, removing the active theme's stashed settings, if activated.
		if ( did_action( 'switch_theme' ) ) {
			$other_theme_mod_settings = $theme_mod_settings;
			unset( $other_theme_mod_settings[ $this->get_stylesheet() ] );
			$this->update_stashed_theme_mod_settings( $other_theme_mod_settings );
		}

		/**
		 * Fires after Customize settings have been saved.
		 *
		 * @since 3.6.0
		 *
		 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
		 */
		do_action( 'customize_save_after', $this );

		// Restore original capabilities.
		foreach ( $original_setting_capabilities as $setting_id => $capability ) {
			$setting = $this->get_setting( $setting_id );
			if ( $setting ) {
				$setting->capability = $capability;
			}
		}

		// Restore original changeset data.
		$this->_changeset_data    = $previous_changeset_data;
		$this->_changeset_post_id = $previous_changeset_post_id;
		$this->_changeset_uuid    = $previous_changeset_uuid;

		/*
		 * Convert all autosave revisions into their own auto-drafts so that users can be prompted to
		 * restore them when a changeset is published, but they had been locked out from including
		 * their changes in the changeset.
		 */
		$revisions = wp_get_post_revisions( $changeset_post_id, array( 'check_enabled' => false ) );
		foreach ( $revisions as $revision ) {
			if ( str_contains( $revision->post_name, "{$changeset_post_id}-autosave" ) ) {
				$wpdb->update(
					$wpdb->posts,
					array(
						'post_status' => 'auto-draft',
						'post_type'   => 'customize_changeset',
						'post_name'   => wp_generate_uuid4(),
						'post_parent' => 0,
					),
					array(
						'ID' => $revision->ID,
					)
				);
				clean_post_cache( $revision->ID );
			}
		}

		return true;
	}

	/**
	 * Updates stashed theme mod settings.
	 *
	 * @since 4.7.0
	 *
	 * @param array $inactive_theme_mod_settings Mapping of stylesheet to arrays of theme mod settings.
	 * @return array|false Returns array of updated stashed theme mods or false if the update failed or there were no changes.
	 */
	protected function update_stashed_theme_mod_settings( $inactive_theme_mod_settings ) {
		$stashed_theme_mod_settings = get_option( 'customize_stashed_theme_mods' );
		if ( empty( $stashed_theme_mod_settings ) ) {
			$stashed_theme_mod_settings = array();
		}

		// Delete any stashed theme mods for the active theme since they would have been loaded and saved upon activation.
		unset( $stashed_theme_mod_settings[ $this->get_stylesheet() ] );

		// Merge inactive theme mods with the stashed theme mod settings.
		foreach ( $inactive_theme_mod_settings as $stylesheet => $theme_mod_settings ) {
			if ( ! isset( $stashed_theme_mod_settings[ $stylesheet ] ) ) {
				$stashed_theme_mod_settings[ $stylesheet ] = array();
			}

			$stashed_theme_mod_settings[ $stylesheet ] = array_merge(
				$stashed_theme_mod_settings[ $stylesheet ],
				$theme_mod_settings
			);
		}

		$autoload = false;
		$result   = update_option( 'customize_stashed_theme_mods', $stashed_theme_mod_settings, $autoload );
		if ( ! $result ) {
			return false;
		}
		return $stashed_theme_mod_settings;
	}

	/**
	 * Refreshes nonces for the current preview.
	 *
	 * @since 4.2.0
	 */
	public function refresh_nonces() {
		if ( ! $this->is_preview() ) {
			wp_send_json_error( 'not_preview' );
		}

		wp_send_json_success( $this->get_nonces() );
	}

	/**
	 * Deletes a given auto-draft changeset or the autosave revision for a given changeset or delete changeset lock.
	 *
	 * @since 4.9.0
	 */
	public function handle_dismiss_autosave_or_lock_request() {
		// Calls to dismiss_user_auto_draft_changesets() and wp_get_post_autosave() require non-zero get_current_user_id().
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'unauthenticated', 401 );
		}

		if ( ! $this->is_preview() ) {
			wp_send_json_error( 'not_preview', 400 );
		}

		if ( ! check_ajax_referer( 'customize_dismiss_autosave_or_lock', 'nonce', false ) ) {
			wp_send_json_error( 'invalid_nonce', 403 );
		}

		$changeset_post_id = $this->changeset_post_id();
		$dismiss_lock      = ! empty( $_POST['dismiss_lock'] );
		$dismiss_autosave  = ! empty( $_POST['dismiss_autosave'] );

		if ( $dismiss_lock ) {
			if ( empty( $changeset_post_id ) && ! $dismiss_autosave ) {
				wp_send_json_error( 'no_changeset_to_dismiss_lock', 404 );
			}
			if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $changeset_post_id ) && ! $dismiss_autosave ) {
				wp_send_json_error( 'cannot_remove_changeset_lock', 403 );
			}

			delete_post_meta( $changeset_post_id, '_edit_lock' );

			if ( ! $dismiss_autosave ) {
				wp_send_json_success( 'changeset_lock_dismissed' );
			}
		}

		if ( $dismiss_autosave ) {
			if ( empty( $changeset_post_id ) || 'auto-draft' === get_post_status( $changeset_post_id ) ) {
				$dismissed = $this->dismiss_user_auto_draft_changesets();
				if ( $dismissed > 0 ) {
					wp_send_json_success( 'auto_draft_dismissed' );
				} else {
					wp_send_json_error( 'no_auto_draft_to_delete', 404 );
				}
			} else {
				$revision = wp_get_post_autosave( $changeset_post_id, get_current_user_id() );

				if ( $revision ) {
					if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->delete_post, $changeset_post_id ) ) {
						wp_send_json_error( 'cannot_delete_autosave_revision', 403 );
					}

					if ( ! wp_delete_post( $revision->ID, true ) ) {
						wp_send_json_error( 'autosave_revision_deletion_failure', 500 );
					} else {
						wp_send_json_success( 'autosave_revision_deleted' );
					}
				} else {
					wp_send_json_error( 'no_autosave_revision_to_delete', 404 );
				}
			}
		}

		wp_send_json_error( 'unknown_error', 500 );
	}

	/**
	 * Adds a customize setting.
	 *
	 * @since 3.4.0
	 * @since 4.5.0 Return added WP_Customize_Setting instance.
	 *
	 * @see WP_Customize_Setting::__construct()
	 * @link https://developer.wordpress.org/themes/customize-api
	 *
	 * @param WP_Customize_Setting|string $id   Customize Setting object, or ID.
	 * @param array                       $args Optional. Array of properties for the new Setting object.
	 *                                          See WP_Customize_Setting::__construct() for information
	 *                                          on accepted arguments. Default empty array.
	 * @return WP_Customize_Setting The instance of the setting that was added.
	 */
	public function add_setting( $id, $args = array() ) {
		if ( $id instanceof WP_Customize_Setting ) {
			$setting = $id;
		} else {
			$class = 'WP_Customize_Setting';

			/** This filter is documented in wp-includes/class-wp-customize-manager.php */
			$args = apply_filters( 'customize_dynamic_setting_args', $args, $id );

			/** This filter is documented in wp-includes/class-wp-customize-manager.php */
			$class = apply_filters( 'customize_dynamic_setting_class', $class, $id, $args );

			$setting = new $class( $this, $id, $args );
		}

		$this->settings[ $setting->id ] = $setting;
		return $setting;
	}

	/**
	 * Registers any dynamically-created settings, such as those from $_POST['customized']
	 * that have no corresponding setting created.
	 *
	 * This is a mechanism to "wake up" settings that have been dynamically created
	 * on the front end and have been sent to WordPress in `$_POST['customized']`. When WP
	 * loads, the dynamically-created settings then will get created and previewed
	 * even though they are not directly created statically with code.
	 *
	 * @since 4.2.0
	 *
	 * @param array $setting_ids The setting IDs to add.
	 * @return array The WP_Customize_Setting objects added.
	 */
	public function add_dynamic_settings( $setting_ids ) {
		$new_settings = array();
		foreach ( $setting_ids as $setting_id ) {
			// Skip settings already created.
			if ( $this->get_setting( $setting_id ) ) {
				continue;
			}

			$setting_args  = false;
			$setting_class = 'WP_Customize_Setting';

			/**
			 * Filters a dynamic setting's constructor args.
			 *
			 * For a dynamic setting to be registered, this filter must be employed
			 * to override the default false value with an array of args to pass to
			 * the WP_Customize_Setting constructor.
			 *
			 * @since 4.2.0
			 *
			 * @param false|array $setting_args The arguments to the WP_Customize_Setting constructor.
			 * @param string      $setting_id   ID for dynamic setting, usually coming from `$_POST['customized']`.
			 */
			$setting_args = apply_filters( 'customize_dynamic_setting_args', $setting_args, $setting_id );
			if ( false === $setting_args ) {
				continue;
			}

			/**
			 * Allow non-statically created settings to be constructed with custom WP_Customize_Setting subclass.
			 *
			 * @since 4.2.0
			 *
			 * @param string $setting_class WP_Customize_Setting or a subclass.
			 * @param string $setting_id    ID for dynamic setting, usually coming from `$_POST['customized']`.
			 * @param array  $setting_args  WP_Customize_Setting or a subclass.
			 */
			$setting_class = apply_filters( 'customize_dynamic_setting_class', $setting_class, $setting_id, $setting_args );

			$setting = new $setting_class( $this, $setting_id, $setting_args );

			$this->add_setting( $setting );
			$new_settings[] = $setting;
		}
		return $new_settings;
	}

	/**
	 * Retrieves a customize setting.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id Customize Setting ID.
	 * @return WP_Customize_Setting|void The setting, if set.
	 */
	public function get_setting( $id ) {
		if ( isset( $this->settings[ $id ] ) ) {
			return $this->settings[ $id ];
		}
	}

	/**
	 * Removes a customize setting.
	 *
	 * Note that removing the setting doesn't destroy the WP_Customize_Setting instance or remove its filters.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id Customize Setting ID.
	 */
	public function remove_setting( $id ) {
		unset( $this->settings[ $id ] );
	}

	/**
	 * Adds a customize panel.
	 *
	 * @since 4.0.0
	 * @since 4.5.0 Return added WP_Customize_Panel instance.
	 *
	 * @see WP_Customize_Panel::__construct()
	 *
	 * @param WP_Customize_Panel|string $id   Customize Panel object, or ID.
	 * @param array                     $args Optional. Array of properties for the new Panel object.
	 *                                        See WP_Customize_Panel::__construct() for information
	 *                                        on accepted arguments. Default empty array.
	 * @return WP_Customize_Panel The instance of the panel that was added.
	 */
	public function add_panel( $id, $args = array() ) {
		if ( $id instanceof WP_Customize_Panel ) {
			$panel = $id;
		} else {
			$panel = new WP_Customize_Panel( $this, $id, $args );
		}

		$this->panels[ $panel->id ] = $panel;
		return $panel;
	}

	/**
	 * Retrieves a customize panel.
	 *
	 * @since 4.0.0
	 *
	 * @param string $id Panel ID to get.
	 * @return WP_Customize_Panel|void Requested panel instance, if set.
	 */
	public function get_panel( $id ) {
		if ( isset( $this->panels[ $id ] ) ) {
			return $this->panels[ $id ];
		}
	}

	/**
	 * Removes a customize panel.
	 *
	 * Note that removing the panel doesn't destroy the WP_Customize_Panel instance or remove its filters.
	 *
	 * @since 4.0.0
	 *
	 * @param string $id Panel ID to remove.
	 */
	public function remove_panel( $id ) {
		// Removing core components this way is _doing_it_wrong().
		if ( in_array( $id, $this->components, true ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: Panel ID, 2: Link to 'customize_loaded_components' filter reference. */
					__( 'Removing %1$s manually will cause PHP warnings. Use the %2$s filter instead.' ),
					$id,
					sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( 'https://developer.wordpress.org/reference/hooks/customize_loaded_components/' ),
						'<code>customize_loaded_components</code>'
					)
				),
				'4.5.0'
			);
		}
		unset( $this->panels[ $id ] );
	}

	/**
	 * Registers a customize panel type.
	 *
	 * Registered types are eligible to be rendered via JS and created dynamically.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Panel
	 *
	 * @param string $panel Name of a custom panel which is a subclass of WP_Customize_Panel.
	 */
	public function register_panel_type( $panel ) {
		$this->registered_panel_types[] = $panel;
	}

	/**
	 * Renders JS templates for all registered panel types.
	 *
	 * @since 4.3.0
	 */
	public function render_panel_templates() {
		foreach ( $this->registered_panel_types as $panel_type ) {
			$panel = new $panel_type( $this, 'temp', array() );
			$panel->print_template();
		}
	}

	/**
	 * Adds a customize section.
	 *
	 * @since 3.4.0
	 * @since 4.5.0 Return added WP_Customize_Section instance.
	 *
	 * @see WP_Customize_Section::__construct()
	 *
	 * @param WP_Customize_Section|string $id   Customize Section object, or ID.
	 * @param array                       $args Optional. Array of properties for the new Section object.
	 *                                          See WP_Customize_Section::__construct() for information
	 *                                          on accepted arguments. Default empty array.
	 * @return WP_Customize_Section The instance of the section that was added.
	 */
	public function add_section( $id, $args = array() ) {
		if ( $id instanceof WP_Customize_Section ) {
			$section = $id;
		} else {
			$section = new WP_Customize_Section( $this, $id, $args );
		}

		$this->sections[ $section->id ] = $section;
		return $section;
	}

	/**
	 * Retrieves a customize section.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id Section ID.
	 * @return WP_Customize_Section|void The section, if set.
	 */
	public function get_section( $id ) {
		if ( isset( $this->sections[ $id ] ) ) {
			return $this->sections[ $id ];
		}
	}

	/**
	 * Removes a customize section.
	 *
	 * Note that removing the section doesn't destroy the WP_Customize_Section instance or remove its filters.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id Section ID.
	 */
	public function remove_section( $id ) {
		unset( $this->sections[ $id ] );
	}

	/**
	 * Registers a customize section type.
	 *
	 * Registered types are eligible to be rendered via JS and created dynamically.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Section
	 *
	 * @param string $section Name of a custom section which is a subclass of WP_Customize_Section.
	 */
	public function register_section_type( $section ) {
		$this->registered_section_types[] = $section;
	}

	/**
	 * Renders JS templates for all registered section types.
	 *
	 * @since 4.3.0
	 */
	public function render_section_templates() {
		foreach ( $this->registered_section_types as $section_type ) {
			$section = new $section_type( $this, 'temp', array() );
			$section->print_template();
		}
	}

	/**
	 * Adds a customize control.
	 *
	 * @since 3.4.0
	 * @since 4.5.0 Return added WP_Customize_Control instance.
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Control|string $id   Customize Control object, or ID.
	 * @param array                       $args Optional. Array of properties for the new Control object.
	 *                                          See WP_Customize_Control::__construct() for information
	 *                                          on accepted arguments. Default empty array.
	 * @return WP_Customize_Control The instance of the control that was added.
	 */
	public function add_control( $id, $args = array() ) {
		if ( $id instanceof WP_Customize_Control ) {
			$control = $id;
		} else {
			$control = new WP_Customize_Control( $this, $id, $args );
		}

		$this->controls[ $control->id ] = $control;
		return $control;
	}

	/**
	 * Retrieves a customize control.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id ID of the control.
	 * @return WP_Customize_Control|void The control object, if set.
	 */
	public function get_control( $id ) {
		if ( isset( $this->controls[ $id ] ) ) {
			return $this->controls[ $id ];
		}
	}

	/**
	 * Removes a customize control.
	 *
	 * Note that removing the control doesn't destroy the WP_Customize_Control instance or remove its filters.
	 *
	 * @since 3.4.0
	 *
	 * @param string $id ID of the control.
	 */
	public function remove_control( $id ) {
		unset( $this->controls[ $id ] );
	}

	/**
	 * Registers a customize control type.
	 *
	 * Registered types are eligible to be rendered via JS and created dynamically.
	 *
	 * @since 4.1.0
	 *
	 * @param string $control Name of a custom control which is a subclass of
	 *                        WP_Customize_Control.
	 */
	public function register_control_type( $control ) {
		$this->registered_control_types[] = $control;
	}

	/**
	 * Renders JS templates for all registered control types.
	 *
	 * @since 4.1.0
	 */
	public function render_control_templates() {
		if ( $this->branching() ) {
			$l10n = array(
				/* translators: %s: User who is customizing the changeset in customizer. */
				'locked'                => __( '%s is already customizing this changeset. Please wait until they are done to try customizing. Your latest changes have been autosaved.' ),
				/* translators: %s: User who is customizing the changeset in customizer. */
				'locked_allow_override' => __( '%s is already customizing this changeset. Do you want to take over?' ),
			);
		} else {
			$l10n = array(
				/* translators: %s: User who is customizing the changeset in customizer. */
				'locked'                => __( '%s is already customizing this site. Please wait until they are done to try customizing. Your latest changes have been autosaved.' ),
				/* translators: %s: User who is customizing the changeset in customizer. */
				'locked_allow_override' => __( '%s is already customizing this site. Do you want to take over?' ),
			);
		}

		foreach ( $this->registered_control_types as $control_type ) {
			$control = new $control_type(
				$this,
				'temp',
				array(
					'settings' => array(),
				)
			);
			$control->print_template();
		}
		?>

		<script type="text/html" id="tmpl-customize-control-default-content">
			<#
			var inputId = _.uniqueId( 'customize-control-default-input-' );
			var descriptionId = _.uniqueId( 'customize-control-default-description-' );
			var describedByAttr = data.description ? ' aria-describedby="' + descriptionId + '" ' : '';
			#>
			<# switch ( data.type ) {
				case 'checkbox': #>
					<span class="customize-inside-control-row">
						<input
							id="{{ inputId }}"
							{{{ describedByAttr }}}
							type="checkbox"
							value="{{ data.value }}"
							data-customize-setting-key-link="default"
						>
						<label for="{{ inputId }}">
							{{ data.label }}
						</label>
						<# if ( data.description ) { #>
							<span id="{{ descriptionId }}" class="description customize-control-description">{{{ data.description }}}</span>
						<# } #>
					</span>
					<#
					break;
				case 'radio':
					if ( ! data.choices ) {
						return;
					}
					#>
					<# if ( data.label ) { #>
						<label for="{{ inputId }}" class="customize-control-title">
							{{ data.label }}
						</label>
					<# } #>
					<# if ( data.description ) { #>
						<span id="{{ descriptionId }}" class="description customize-control-description">{{{ data.description }}}</span>
					<# } #>
					<# _.each( data.choices, function( val, key ) { #>
						<span class="customize-inside-control-row">
							<#
							var value, text;
							if ( _.isObject( val ) ) {
								value = val.value;
								text = val.text;
							} else {
								value = key;
								text = val;
							}
							#>
							<input
								id="{{ inputId + '-' + value }}"
								type="radio"
								value="{{ value }}"
								name="{{ inputId }}"
								data-customize-setting-key-link="default"
								{{{ describedByAttr }}}
							>
							<label for="{{ inputId + '-' + value }}">{{ text }}</label>
						</span>
					<# } ); #>
					<#
					break;
				default:
					#>
					<# if ( data.label ) { #>
						<label for="{{ inputId }}" class="customize-control-title">
							{{ data.label }}
						</label>
					<# } #>
					<# if ( data.description ) { #>
						<span id="{{ descriptionId }}" class="description customize-control-description">{{{ data.description }}}</span>
					<# } #>

					<#
					var inputAttrs = {
						id: inputId,
						'data-customize-setting-key-link': 'default'
					};
					if ( 'textarea' === data.type ) {
						inputAttrs.rows = '5';
					} else if ( 'button' === data.type ) {
						inputAttrs['class'] = 'button button-secondary';
						inputAttrs.type = 'button';
					} else {
						inputAttrs.type = data.type;
					}
					if ( data.description ) {
						inputAttrs['aria-describedby'] = descriptionId;
					}
					_.extend( inputAttrs, data.input_attrs );
					#>

					<# if ( 'button' === data.type ) { #>
						<button
							<# _.each( _.extend( inputAttrs ), function( value, key ) { #>
								{{{ key }}}="{{ value }}"
							<# } ); #>
						>{{ inputAttrs.value }}</button>
					<# } else if ( 'textarea' === data.type ) { #>
						<textarea
							<# _.each( _.extend( inputAttrs ), function( value, key ) { #>
								{{{ key }}}="{{ value }}"
							<# }); #>
						>{{ inputAttrs.value }}</textarea>
					<# } else if ( 'select' === data.type ) { #>
						<# delete inputAttrs.type; #>
						<select
							<# _.each( _.extend( inputAttrs ), function( value, key ) { #>
								{{{ key }}}="{{ value }}"
							<# }); #>
							>
							<# _.each( data.choices, function( val, key ) { #>
								<#
								var value, text;
								if ( _.isObject( val ) ) {
									value = val.value;
									text = val.text;
								} else {
									value = key;
									text = val;
								}
								#>
								<option value="{{ value }}">{{ text }}</option>
							<# } ); #>
						</select>
					<# } else { #>
						<input
							<# _.each( _.extend( inputAttrs ), function( value, key ) { #>
								{{{ key }}}="{{ value }}"
							<# }); #>
							>
					<# } #>
			<# } #>
		</script>

		<script type="text/html" id="tmpl-customize-notification">
			<li class="notice notice-{{ data.type || 'info' }} {{ data.alt ? 'notice-alt' : '' }} {{ data.dismissible ? 'is-dismissible' : '' }} {{ data.containerClasses || '' }}" data-code="{{ data.code }}" data-type="{{ data.type }}">
				<div class="notification-message">{{{ data.message || data.code }}}</div>
				<# if ( data.dismissible ) { #>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						_e( 'Dismiss' );
						?>
					</span></button>
				<# } #>
			</li>
		</script>

		<script type="text/html" id="tmpl-customize-changeset-locked-notification">
			<li class="notice notice-{{ data.type || 'info' }} {{ data.containerClasses || '' }}" data-code="{{ data.code }}" data-type="{{ data.type }}">
				<div class="notification-message customize-changeset-locked-message {{ data.lockUser.avatar ? 'has-avatar' : '' }}">
					<# if ( data.lockUser.avatar ) { #>
						<img class="customize-changeset-locked-avatar" src="{{ data.lockUser.avatar }}" alt="{{ data.lockUser.name }}" />
					<# } #>
					<p class="currently-editing">
						<# if ( data.message ) { #>
							{{{ data.message }}}
						<# } else if ( data.allowOverride ) { #>
							<?php
							echo esc_html( sprintf( $l10n['locked_allow_override'], '{{ data.lockUser.name }}' ) );
							?>
						<# } else { #>
							<?php
							echo esc_html( sprintf( $l10n['locked'], '{{ data.lockUser.name }}' ) );
							?>
						<# } #>
					</p>
					<p class="notice notice-error notice-alt" hidden></p>
					<p class="action-buttons">
						<# if ( data.returnUrl !== data.previewUrl ) { #>
							<a class="button customize-notice-go-back-button" href="{{ data.returnUrl }}"><?php _e( 'Go back' ); ?></a>
						<# } #>
						<a class="button customize-notice-preview-button" href="{{ data.frontendPreviewUrl }}"><?php _e( 'Preview' ); ?></a>
						<# if ( data.allowOverride ) { #>
							<button class="button button-primary wp-tab-last customize-notice-take-over-button"><?php _e( 'Take over' ); ?></button>
						<# } #>
					</p>
				</div>
			</li>
		</script>

		<script type="text/html" id="tmpl-customize-code-editor-lint-error-notification">
			<li class="notice notice-{{ data.type || 'info' }} {{ data.alt ? 'notice-alt' : '' }} {{ data.dismissible ? 'is-dismissible' : '' }} {{ data.containerClasses || '' }}" data-code="{{ data.code }}" data-type="{{ data.type }}">
				<div class="notification-message">{{{ data.message || data.code }}}</div>

				<p>
					<# var elementId = 'el-' + String( Math.random() ); #>
					<input id="{{ elementId }}" type="checkbox">
					<label for="{{ elementId }}"><?php _e( 'Update anyway, even though it might break your site?' ); ?></label>
				</p>
			</li>
		</script>

		<?php
		/* The following template is obsolete in core but retained for plugins. */
		?>
		<script type="text/html" id="tmpl-customize-control-notifications">
			<ul>
				<# _.each( data.notifications, function( notification ) { #>
					<li class="notice notice-{{ notification.type || 'info' }} {{ data.altNotice ? 'notice-alt' : '' }}" data-code="{{ notification.code }}" data-type="{{ notification.type }}">{{{ notification.message || notification.code }}}</li>
				<# } ); #>
			</ul>
		</script>

		<script type="text/html" id="tmpl-customize-preview-link-control" >
			<# var elementPrefix = _.uniqueId( 'el' ) + '-' #>
			<p class="customize-control-title">
				<?php esc_html_e( 'Share Preview Link' ); ?>
			</p>
			<p class="description customize-control-description"><?php esc_html_e( 'See how changes would look live on your website, and share the preview with people who can\'t access the Customizer.' ); ?></p>
			<div class="customize-control-notifications-container"></div>
			<div class="preview-link-wrapper">
				<label for="{{ elementPrefix }}customize-preview-link-input" class="screen-reader-text">
					<?php
					/* translators: Hidden accessibility text. */
					esc_html_e( 'Preview Link' );
					?>
				</label>
				<a href="" target="">
					<span class="preview-control-element" data-component="url"></span>
					<span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						_e( '(opens in a new tab)' );
						?>
					</span>
				</a>
				<input id="{{ elementPrefix }}customize-preview-link-input" readonly tabindex="-1" class="preview-control-element" data-component="input">
				<button class="customize-copy-preview-link preview-control-element button button-secondary" data-component="button" data-copy-text="<?php esc_attr_e( 'Copy' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied' ); ?>" ><?php esc_html_e( 'Copy' ); ?></button>
			</div>
		</script>
		<script type="text/html" id="tmpl-customize-selected-changeset-status-control">
			<# var inputId = _.uniqueId( 'customize-selected-changeset-status-control-input-' ); #>
			<# var descriptionId = _.uniqueId( 'customize-selected-changeset-status-control-description-' ); #>
			<# if ( data.label ) { #>
				<label for="{{ inputId }}" class="customize-control-title">{{ data.label }}</label>
			<# } #>
			<# if ( data.description ) { #>
				<span id="{{ descriptionId }}" class="description customize-control-description">{{{ data.description }}}</span>
			<# } #>
			<# _.each( data.choices, function( choice ) { #>
				<# var choiceId = inputId + '-' + choice.status; #>
				<span class="customize-inside-control-row">
					<input id="{{ choiceId }}" type="radio" value="{{ choice.status }}" name="{{ inputId }}" data-customize-setting-key-link="default">
					<label for="{{ choiceId }}">{{ choice.label }}</label>
				</span>
			<# } ); #>
		</script>
		<?php
	}

	/**
	 * Helper function to compare two objects by priority, ensuring sort stability via instance_number.
	 *
	 * @since 3.4.0
	 * @deprecated 4.7.0 Use wp_list_sort()
	 *
	 * @param WP_Customize_Panel|WP_Customize_Section|WP_Customize_Control $a Object A.
	 * @param WP_Customize_Panel|WP_Customize_Section|WP_Customize_Control $b Object B.
	 * @return int
	 */
	protected function _cmp_priority( $a, $b ) {
		_deprecated_function( __METHOD__, '4.7.0', 'wp_list_sort' );

		if ( $a->priority === $b->priority ) {
			return $a->instance_number - $b->instance_number;
		} else {
			return $a->priority - $b->priority;
		}
	}

	/**
	 * Prepares panels, sections, and controls.
	 *
	 * For each, check if required related components exist,
	 * whether the user has the necessary capabilities,
	 * and sort by priority.
	 *
	 * @since 3.4.0
	 */
	public function prepare_controls() {

		$controls       = array();
		$this->controls = wp_list_sort(
			$this->controls,
			array(
				'priority'        => 'ASC',
				'instance_number' => 'ASC',
			),
			'ASC',
			true
		);

		foreach ( $this->controls as $id => $control ) {
			if ( ! isset( $this->sections[ $control->section ] ) || ! $control->check_capabilities() ) {
				continue;
			}

			$this->sections[ $control->section ]->controls[] = $control;
			$controls[ $id ]                                 = $control;
		}
		$this->controls = $controls;

		// Prepare sections.
		$this->sections = wp_list_sort(
			$this->sections,
			array(
				'priority'        => 'ASC',
				'instance_number' => 'ASC',
			),
			'ASC',
			true
		);
		$sections       = array();

		foreach ( $this->sections as $section ) {
			if ( ! $section->check_capabilities() ) {
				continue;
			}

			$section->controls = wp_list_sort(
				$section->controls,
				array(
					'priority'        => 'ASC',
					'instance_number' => 'ASC',
				)
			);

			if ( ! $section->panel ) {
				// Top-level section.
				$sections[ $section->id ] = $section;
			} else {
				// This section belongs to a panel.
				if ( isset( $this->panels [ $section->panel ] ) ) {
					$this->panels[ $section->panel ]->sections[ $section->id ] = $section;
				}
			}
		}
		$this->sections = $sections;

		// Prepare panels.
		$this->panels = wp_list_sort(
			$this->panels,
			array(
				'priority'        => 'ASC',
				'instance_number' => 'ASC',
			),
			'ASC',
			true
		);
		$panels       = array();

		foreach ( $this->panels as $panel ) {
			if ( ! $panel->check_capabilities() ) {
				continue;
			}

			$panel->sections      = wp_list_sort(
				$panel->sections,
				array(
					'priority'        => 'ASC',
					'instance_number' => 'ASC',
				),
				'ASC',
				true
			);
			$panels[ $panel->id ] = $panel;
		}
		$this->panels = $panels;

		// Sort panels and top-level sections together.
		$this->containers = array_merge( $this->panels, $this->sections );
		$this->containers = wp_list_sort(
			$this->containers,
			array(
				'priority'        => 'ASC',
				'instance_number' => 'ASC',
			),
			'ASC',
			true
		);
	}

	/**
	 * Enqueues scripts for customize controls.
	 *
	 * @since 3.4.0
	 */
	public function enqueue_control_scripts() {
		foreach ( $this->controls as $control ) {
			$control->enqueue();
		}

		if ( ! is_multisite() && ( current_user_can( 'install_themes' ) || current_user_can( 'update_themes' ) || current_user_can( 'delete_themes' ) ) ) {
			wp_enqueue_script( 'updates' );
			wp_localize_script(
				'updates',
				'_wpUpdatesItemCounts',
				array(
					'totals' => wp_get_update_data(),
				)
			);
		}
	}

	/**
	 * Determines whether the user agent is iOS.
	 *
	 * @since 4.4.0
	 *
	 * @return bool Whether the user agent is iOS.
	 */
	public function is_ios() {
		return wp_is_mobile() && preg_match( '/iPad|iPod|iPhone/', $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Gets the template string for the Customizer pane document title.
	 *
	 * @since 4.4.0
	 *
	 * @return string The template string for the document title.
	 */
	public function get_document_title_template() {
		if ( $this->is_theme_active() ) {
			/* translators: %s: Document title from the preview. */
			$document_title_tmpl = __( 'Customize: %s' );
		} else {
			/* translators: %s: Document title from the preview. */
			$document_title_tmpl = __( 'Live Preview: %s' );
		}
		$document_title_tmpl = html_entity_decode( $document_title_tmpl, ENT_QUOTES, 'UTF-8' ); // Because exported to JS and assigned to document.title.
		return $document_title_tmpl;
	}

	/**
	 * Sets the initial URL to be previewed.
	 *
	 * URL is validated.
	 *
	 * @since 4.4.0
	 *
	 * @param string $preview_url URL to be previewed.
	 */
	public function set_preview_url( $preview_url ) {
		$preview_url       = sanitize_url( $preview_url );
		$this->preview_url = wp_validate_redirect( $preview_url, home_url( '/' ) );
	}

	/**
	 * Gets the initial URL to be previewed.
	 *
	 * @since 4.4.0
	 *
	 * @return string URL being previewed.
	 */
	public function get_preview_url() {
		if ( empty( $this->preview_url ) ) {
			$preview_url = home_url( '/' );
		} else {
			$preview_url = $this->preview_url;
		}
		return $preview_url;
	}

	/**
	 * Determines whether the admin and the frontend are on different domains.
	 *
	 * @since 4.7.0
	 *
	 * @return bool Whether cross-domain.
	 */
	public function is_cross_domain() {
		$admin_origin = wp_parse_url( admin_url() );
		$home_origin  = wp_parse_url( home_url() );
		$cross_domain = ( strtolower( $admin_origin['host'] ) !== strtolower( $home_origin['host'] ) );
		return $cross_domain;
	}

	/**
	 * Gets URLs allowed to be previewed.
	 *
	 * If the front end and the admin are served from the same domain, load the
	 * preview over ssl if the Customizer is being loaded over ssl. This avoids
	 * insecure content warnings. This is not attempted if the admin and front end
	 * are on different domains to avoid the case where the front end doesn't have
	 * ssl certs. Domain mapping plugins can allow other urls in these conditions
	 * using the customize_allowed_urls filter.
	 *
	 * @since 4.7.0
	 *
	 * @return array Allowed URLs.
	 */
	public function get_allowed_urls() {
		$allowed_urls = array( home_url( '/' ) );

		if ( is_ssl() && ! $this->is_cross_domain() ) {
			$allowed_urls[] = home_url( '/', 'https' );
		}

		/**
		 * Filters the list of URLs allowed to be clicked and followed in the Customizer preview.
		 *
		 * @since 3.4.0
		 *
		 * @param string[] $allowed_urls An array of allowed URLs.
		 */
		$allowed_urls = array_unique( apply_filters( 'customize_allowed_urls', $allowed_urls ) );

		return $allowed_urls;
	}

	/**
	 * Gets messenger channel.
	 *
	 * @since 4.7.0
	 *
	 * @return string Messenger channel.
	 */
	public function get_messenger_channel() {
		return $this->messenger_channel;
	}

	/**
	 * Sets URL to link the user to when closing the Customizer.
	 *
	 * URL is validated.
	 *
	 * @since 4.4.0
	 *
	 * @param string $return_url URL for return link.
	 */
	public function set_return_url( $return_url ) {
		$return_url       = sanitize_url( $return_url );
		$return_url       = remove_query_arg( wp_removable_query_args(), $return_url );
		$return_url       = wp_validate_redirect( $return_url );
		$this->return_url = $return_url;
	}

	/**
	 * Gets URL to link the user to when closing the Customizer.
	 *
	 * @since 4.4.0
	 *
	 * @global array $_registered_pages
	 *
	 * @return string URL for link to close Customizer.
	 */
	public function get_return_url() {
		global $_registered_pages;

		$referer                    = wp_get_referer();
		$excluded_referer_basenames = array( 'customize.php', 'wp-login.php' );

		if ( $this->return_url ) {
			$return_url = $this->return_url;

			$return_url_basename = wp_basename( parse_url( $this->return_url, PHP_URL_PATH ) );
			$return_url_query    = parse_url( $this->return_url, PHP_URL_QUERY );

			if ( 'themes.php' === $return_url_basename && $return_url_query ) {
				parse_str( $return_url_query, $query_vars );

				/*
				 * If the return URL is a page added by a theme to the Appearance menu via add_submenu_page(),
				 * verify that it belongs to the active theme, otherwise fall back to the Themes screen.
				 */
				if ( isset( $query_vars['page'] ) && ! isset( $_registered_pages[ "appearance_page_{$query_vars['page']}" ] ) ) {
					$return_url = admin_url( 'themes.php' );
				}
			}
		} elseif ( $referer && ! in_array( wp_basename( parse_url( $referer, PHP_URL_PATH ) ), $excluded_referer_basenames, true ) ) {
			$return_url = $referer;
		} elseif ( $this->preview_url ) {
			$return_url = $this->preview_url;
		} else {
			$return_url = home_url( '/' );
		}

		return $return_url;
	}

	/**
	 * Sets the autofocused constructs.
	 *
	 * @since 4.4.0
	 *
	 * @param array $autofocus {
	 *     Mapping of 'panel', 'section', 'control' to the ID which should be autofocused.
	 *
	 *     @type string $control ID for control to be autofocused.
	 *     @type string $section ID for section to be autofocused.
	 *     @type string $panel   ID for panel to be autofocused.
	 * }
	 */
	public function set_autofocus( $autofocus ) {
		$this->autofocus = array_filter( wp_array_slice_assoc( $autofocus, array( 'panel', 'section', 'control' ) ), 'is_string' );
	}

	/**
	 * Gets the autofocused constructs.
	 *
	 * @since 4.4.0
	 *
	 * @return string[] {
	 *     Mapping of 'panel', 'section', 'control' to the ID which should be autofocused.
	 *
	 *     @type string $control ID for control to be autofocused.
	 *     @type string $section ID for section to be autofocused.
	 *     @type string $panel   ID for panel to be autofocused.
	 * }
	 */
	public function get_autofocus() {
		return $this->autofocus;
	}

	/**
	 * Gets nonces for the Customizer.
	 *
	 * @since 4.5.0
	 *
	 * @return array Nonces.
	 */
	public function get_nonces() {
		$nonces = array(
			'save'                     => wp_create_nonce( 'save-customize_' . $this->get_stylesheet() ),
			'preview'                  => wp_create_nonce( 'preview-customize_' . $this->get_stylesheet() ),
			'switch_themes'            => wp_create_nonce( 'switch_themes' ),
			'dismiss_autosave_or_lock' => wp_create_nonce( 'customize_dismiss_autosave_or_lock' ),
			'override_lock'            => wp_create_nonce( 'customize_override_changeset_lock' ),
			'trash'                    => wp_create_nonce( 'trash_customize_changeset' ),
		);

		/**
		 * Filters nonces for Customizer.
		 *
		 * @since 4.2.0
		 *
		 * @param string[]             $nonces  Array of refreshed nonces for save and
		 *                                      preview actions.
		 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
		 */
		$nonces = apply_filters( 'customize_refresh_nonces', $nonces, $this );

		return $nonces;
	}

	/**
	 * Prints JavaScript settings for parent window.
	 *
	 * @since 4.4.0
	 */
	public function customize_pane_settings() {

		$login_url = add_query_arg(
			array(
				'interim-login'   => 1,
				'customize-login' => 1,
			),
			wp_login_url()
		);

		// Ensure dirty flags are set for modified settings.
		foreach ( array_keys( $this->unsanitized_post_values() ) as $setting_id ) {
			$setting = $this->get_setting( $setting_id );
			if ( $setting ) {
				$setting->dirty = true;
			}
		}

		$autosave_revision_post  = null;
		$autosave_autodraft_post = null;
		$changeset_post_id       = $this->changeset_post_id();
		if ( ! $this->saved_starter_content_changeset && ! $this->autosaved() ) {
			if ( $changeset_post_id ) {
				if ( is_user_logged_in() ) {
					$autosave_revision_post = wp_get_post_autosave( $changeset_post_id, get_current_user_id() );
				}
			} else {
				$autosave_autodraft_posts = $this->get_changeset_posts(
					array(
						'posts_per_page'            => 1,
						'post_status'               => 'auto-draft',
						'exclude_restore_dismissed' => true,
					)
				);
				if ( ! empty( $autosave_autodraft_posts ) ) {
					$autosave_autodraft_post = array_shift( $autosave_autodraft_posts );
				}
			}
		}

		$current_user_can_publish = current_user_can( get_post_type_object( 'customize_changeset' )->cap->publish_posts );

		// @todo Include all of the status labels here from script-loader.php, and then allow it to be filtered.
		$status_choices = array();
		if ( $current_user_can_publish ) {
			$status_choices[] = array(
				'status' => 'publish',
				'label'  => __( 'Publish' ),
			);
		}
		$status_choices[] = array(
			'status' => 'draft',
			'label'  => __( 'Save Draft' ),
		);
		if ( $current_user_can_publish ) {
			$status_choices[] = array(
				'status' => 'future',
				'label'  => _x( 'Schedule', 'customizer changeset action/button label' ),
			);
		}

		// Prepare Customizer settings to pass to JavaScript.
		$changeset_post = null;
		if ( $changeset_post_id ) {
			$changeset_post = get_post( $changeset_post_id );
		}

		// Determine initial date to be at present or future, not past.
		$current_time = current_time( 'mysql', false );
		$initial_date = $current_time;
		if ( $changeset_post ) {
			$initial_date = get_the_time( 'Y-m-d H:i:s', $changeset_post->ID );
			if ( $initial_date < $current_time ) {
				$initial_date = $current_time;
			}
		}

		$lock_user_id = false;
		if ( $this->changeset_post_id() ) {
			$lock_user_id = wp_check_post_lock( $this->changeset_post_id() );
		}

		$settings = array(
			'changeset'              => array(
				'uuid'                  => $this->changeset_uuid(),
				'branching'             => $this->branching(),
				'autosaved'             => $this->autosaved(),
				'hasAutosaveRevision'   => ! empty( $autosave_revision_post ),
				'latestAutoDraftUuid'   => $autosave_autodraft_post ? $autosave_autodraft_post->post_name : null,
				'status'                => $changeset_post ? $changeset_post->post_status : '',
				'currentUserCanPublish' => $current_user_can_publish,
				'publishDate'           => $initial_date,
				'statusChoices'         => $status_choices,
				'lockUser'              => $lock_user_id ? $this->get_lock_user_data( $lock_user_id ) : null,
			),
			'initialServerDate'      => $current_time,
			'dateFormat'             => get_option( 'date_format' ),
			'timeFormat'             => get_option( 'time_format' ),
			'initialServerTimestamp' => floor( microtime( true ) * 1000 ),
			'initialClientTimestamp' => -1, // To be set with JS below.
			'timeouts'               => array(
				'windowRefresh'           => 250,
				'changesetAutoSave'       => AUTOSAVE_INTERVAL * 1000,
				'keepAliveCheck'          => 2500,
				'reflowPaneContents'      => 100,
				'previewFrameSensitivity' => 2000,
			),
			'theme'                  => array(
				'stylesheet'  => $this->get_stylesheet(),
				'active'      => $this->is_theme_active(),
				'_canInstall' => current_user_can( 'install_themes' ),
			),
			'url'                    => array(
				'preview'       => sanitize_url( $this->get_preview_url() ),
				'return'        => sanitize_url( $this->get_return_url() ),
				'parent'        => sanitize_url( admin_url() ),
				'activated'     => sanitize_url( home_url( '/' ) ),
				'ajax'          => sanitize_url( admin_url( 'admin-ajax.php', 'relative' ) ),
				'allowed'       => array_map( 'sanitize_url', $this->get_allowed_urls() ),
				'isCrossDomain' => $this->is_cross_domain(),
				'home'          => sanitize_url( home_url( '/' ) ),
				'login'         => sanitize_url( $login_url ),
			),
			'browser'                => array(
				'mobile' => wp_is_mobile(),
				'ios'    => $this->is_ios(),
			),
			'panels'                 => array(),
			'sections'               => array(),
			'nonce'                  => $this->get_nonces(),
			'autofocus'              => $this->get_autofocus(),
			'documentTitleTmpl'      => $this->get_document_title_template(),
			'previewableDevices'     => $this->get_previewable_devices(),
			'l10n'                   => array(
				'confirmDeleteTheme'   => __( 'Are you sure you want to delete this theme?' ),
				/* translators: %d: Number of theme search results, which cannot currently consider singular vs. plural forms. */
				'themeSearchResults'   => __( '%d themes found' ),
				/* translators: %d: Number of themes being displayed, which cannot currently consider singular vs. plural forms. */
				'announceThemeCount'   => __( 'Displaying %d themes' ),
				/* translators: %s: Theme name. */
				'announceThemeDetails' => __( 'Showing details for theme: %s' ),
			),
		);

		// Temporarily disable installation in Customizer. See #42184.
		$filesystem_method = get_filesystem_method();
		ob_start();
		$filesystem_credentials_are_stored = request_filesystem_credentials( self_admin_url() );
		ob_end_clean();
		if ( 'direct' !== $filesystem_method && ! $filesystem_credentials_are_stored ) {
			$settings['theme']['_filesystemCredentialsNeeded'] = true;
		}

		// Prepare Customize Section objects to pass to JavaScript.
		foreach ( $this->sections() as $id => $section ) {
			if ( $section->check_capabilities() ) {
				$settings['sections'][ $id ] = $section->json();
			}
		}

		// Prepare Customize Panel objects to pass to JavaScript.
		foreach ( $this->panels() as $panel_id => $panel ) {
			if ( $panel->check_capabilities() ) {
				$settings['panels'][ $panel_id ] = $panel->json();
				foreach ( $panel->sections as $section_id => $section ) {
					if ( $section->check_capabilities() ) {
						$settings['sections'][ $section_id ] = $section->json();
					}
				}
			}
		}

		ob_start();
		?>
		<script>
			var _wpCustomizeSettings = <?php echo wp_json_encode( $settings ); ?>;
			_wpCustomizeSettings.initialClientTimestamp = _.now();
			_wpCustomizeSettings.controls = {};
			_wpCustomizeSettings.settings = {};
			<?php

			// Serialize settings one by one to improve memory usage.
			echo "(function ( s ){\n";
			foreach ( $this->settings() as $setting ) {
				if ( $setting->check_capabilities() ) {
					printf(
						"s[%s] = %s;\n",
						wp_json_encode( $setting->id ),
						wp_json_encode( $setting->json() )
					);
				}
			}
			echo "})( _wpCustomizeSettings.settings );\n";

			// Serialize controls one by one to improve memory usage.
			echo "(function ( c ){\n";
			foreach ( $this->controls() as $control ) {
				if ( $control->check_capabilities() ) {
					printf(
						"c[%s] = %s;\n",
						wp_json_encode( $control->id ),
						wp_json_encode( $control->json() )
					);
				}
			}
			echo "})( _wpCustomizeSettings.controls );\n";
			?>
		</script>
		<?php
		wp_print_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
	}

	/**
	 * Returns a list of devices to allow previewing.
	 *
	 * @since 4.5.0
	 *
	 * @return array List of devices with labels and default setting.
	 */
	public function get_previewable_devices() {
		$devices = array(
			'desktop' => array(
				'label'   => __( 'Enter desktop preview mode' ),
				'default' => true,
			),
			'tablet'  => array(
				'label' => __( 'Enter tablet preview mode' ),
			),
			'mobile'  => array(
				'label' => __( 'Enter mobile preview mode' ),
			),
		);

		/**
		 * Filters the available devices to allow previewing in the Customizer.
		 *
		 * @since 4.5.0
		 *
		 * @see WP_Customize_Manager::get_previewable_devices()
		 *
		 * @param array $devices List of devices with labels and default setting.
		 */
		$devices = apply_filters( 'customize_previewable_devices', $devices );

		return $devices;
	}

	/**
	 * Registers some default controls.
	 *
	 * @since 3.4.0
	 */
	public function register_controls() {

		/* Themes (controls are loaded via ajax) */

		$this->add_panel(
			new WP_Customize_Themes_Panel(
				$this,
				'themes',
				array(
					'title'       => $this->theme()->display( 'Name' ),
					'description' => (
					'<p>' . __( 'Looking for a theme? You can search or browse the WordPress.org theme directory, install and preview themes, then activate them right here.' ) . '</p>' .
					'<p>' . __( 'While previewing a new theme, you can continue to tailor things like widgets and menus, and explore theme-specific options.' ) . '</p>'
					),
					'capability'  => 'switch_themes',
					'priority'    => 0,
				)
			)
		);

		$this->add_section(
			new WP_Customize_Themes_Section(
				$this,
				'installed_themes',
				array(
					'title'      => __( 'Installed themes' ),
					'action'     => 'installed',
					'capability' => 'switch_themes',
					'panel'      => 'themes',
					'priority'   => 0,
				)
			)
		);

		if ( ! is_multisite() ) {
			$this->add_section(
				new WP_Customize_Themes_Section(
					$this,
					'wporg_themes',
					array(
						'title'       => __( 'WordPress.org themes' ),
						'action'      => 'wporg',
						'filter_type' => 'remote',
						'capability'  => 'install_themes',
						'panel'       => 'themes',
						'priority'    => 5,
					)
				)
			);
		}

		// Themes Setting (unused - the theme is considerably more fundamental to the Customizer experience).
		$this->add_setting(
			new WP_Customize_Filter_Setting(
				$this,
				'active_theme',
				array(
					'capability' => 'switch_themes',
				)
			)
		);

		/* Site Identity */

		$this->add_section(
			'title_tagline',
			array(
				'title'    => __( 'Site Identity' ),
				'priority' => 20,
			)
		);

		$this->add_setting(
			'blogname',
			array(
				'default'    => get_option( 'blogname' ),
				'type'       => 'option',
				'capability' => 'manage_options',
			)
		);

		$this->add_control(
			'blogname',
			array(
				'label'   => __( 'Site Title' ),
				'section' => 'title_tagline',
			)
		);

		$this->add_setting(
			'blogdescription',
			array(
				'default'    => get_option( 'blogdescription' ),
				'type'       => 'option',
				'capability' => 'manage_options',
			)
		);

		$this->add_control(
			'blogdescription',
			array(
				'label'   => __( 'Tagline' ),
				'section' => 'title_tagline',
			)
		);

		// Add a setting to hide header text if the theme doesn't support custom headers.
		if ( ! current_theme_supports( 'custom-header', 'header-text' ) ) {
			$this->add_setting(
				'header_text',
				array(
					'theme_supports'    => array( 'custom-logo', 'header-text' ),
					'default'           => 1,
					'sanitize_callback' => 'absint',
				)
			);

			$this->add_control(
				'header_text',
				array(
					'label'    => __( 'Display Site Title and Tagline' ),
					'section'  => 'title_tagline',
					'settings' => 'header_text',
					'type'     => 'checkbox',
				)
			);
		}

		$this->add_setting(
			'site_icon',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage', // Previewed with JS in the Customizer controls window.
			)
		);

		$this->add_control(
			new WP_Customize_Site_Icon_Control(
				$this,
				'site_icon',
				array(
					'label'       => __( 'Site Icon' ),
					'description' => sprintf(
						/* translators: 1: pixel value for icon size. 2: pixel value for icon size. */
						'<p>' . __( 'The Site Icon is what you see in browser tabs, bookmark bars, and within the WordPress mobile apps. It should be square and at least <code>%1$s by %2$s</code> pixels.' ) . '</p>',
						512,
						512
					),
					'section'     => 'title_tagline',
					'priority'    => 60,
					'height'      => 512,
					'width'       => 512,
				)
			)
		);

		$this->add_setting(
			'custom_logo',
			array(
				'theme_supports' => array( 'custom-logo' ),
				'transport'      => 'postMessage',
			)
		);

		$custom_logo_args = get_theme_support( 'custom-logo' );
		$this->add_control(
			new WP_Customize_Cropped_Image_Control(
				$this,
				'custom_logo',
				array(
					'label'         => __( 'Logo' ),
					'section'       => 'title_tagline',
					'priority'      => 8,
					'height'        => isset( $custom_logo_args[0]['height'] ) ? $custom_logo_args[0]['height'] : null,
					'width'         => isset( $custom_logo_args[0]['width'] ) ? $custom_logo_args[0]['width'] : null,
					'flex_height'   => isset( $custom_logo_args[0]['flex-height'] ) ? $custom_logo_args[0]['flex-height'] : null,
					'flex_width'    => isset( $custom_logo_args[0]['flex-width'] ) ? $custom_logo_args[0]['flex-width'] : null,
					'button_labels' => array(
						'select'       => __( 'Select logo' ),
						'change'       => __( 'Change logo' ),
						'remove'       => __( 'Remove' ),
						'default'      => __( 'Default' ),
						'placeholder'  => __( 'No logo selected' ),
						'frame_title'  => __( 'Select logo' ),
						'frame_button' => __( 'Choose logo' ),
					),
				)
			)
		);

		$this->selective_refresh->add_partial(
			'custom_logo',
			array(
				'settings'            => array( 'custom_logo' ),
				'selector'            => '.custom-logo-link',
				'render_callback'     => array( $this, '_render_custom_logo_partial' ),
				'container_inclusive' => true,
			)
		);

		/* Colors */

		$this->add_section(
			'colors',
			array(
				'title'    => __( 'Colors' ),
				'priority' => 40,
			)
		);

		$this->add_setting(
			'header_textcolor',
			array(
				'theme_supports'       => array( 'custom-header', 'header-text' ),
				'default'              => get_theme_support( 'custom-header', 'default-text-color' ),

				'sanitize_callback'    => array( $this, '_sanitize_header_textcolor' ),
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		// Input type: checkbox, with custom value.
		$this->add_control(
			'display_header_text',
			array(
				'settings' => 'header_textcolor',
				'label'    => __( 'Display Site Title and Tagline' ),
				'section'  => 'title_tagline',
				'type'     => 'checkbox',
				'priority' => 40,
			)
		);

		$this->add_control(
			new WP_Customize_Color_Control(
				$this,
				'header_textcolor',
				array(
					'label'   => __( 'Header Text Color' ),
					'section' => 'colors',
				)
			)
		);

		// Input type: color, with sanitize_callback.
		$this->add_setting(
			'background_color',
			array(
				'default'              => get_theme_support( 'custom-background', 'default-color' ),
				'theme_supports'       => 'custom-background',

				'sanitize_callback'    => 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$this->add_control(
			new WP_Customize_Color_Control(
				$this,
				'background_color',
				array(
					'label'   => __( 'Background Color' ),
					'section' => 'colors',
				)
			)
		);

		/* Custom Header */

		if ( current_theme_supports( 'custom-header', 'video' ) ) {
			$title       = __( 'Header Media' );
			$description = '<p>' . __( 'If you add a video, the image will be used as a fallback while the video loads.' ) . '</p>';

			$width  = absint( get_theme_support( 'custom-header', 'width' ) );
			$height = absint( get_theme_support( 'custom-header', 'height' ) );
			if ( $width && $height ) {
				$control_description = sprintf(
					/* translators: 1: .mp4, 2: Header size in pixels. */
					__( 'Upload your video in %1$s format and minimize its file size for best results. Your theme recommends dimensions of %2$s pixels.' ),
					'<code>.mp4</code>',
					sprintf( '<strong>%s &times; %s</strong>', $width, $height )
				);
			} elseif ( $width ) {
				$control_description = sprintf(
					/* translators: 1: .mp4, 2: Header width in pixels. */
					__( 'Upload your video in %1$s format and minimize its file size for best results. Your theme recommends a width of %2$s pixels.' ),
					'<code>.mp4</code>',
					sprintf( '<strong>%s</strong>', $width )
				);
			} else {
				$control_description = sprintf(
					/* translators: 1: .mp4, 2: Header height in pixels. */
					__( 'Upload your video in %1$s format and minimize its file size for best results. Your theme recommends a height of %2$s pixels.' ),
					'<code>.mp4</code>',
					sprintf( '<strong>%s</strong>', $height )
				);
			}
		} else {
			$title               = __( 'Header Image' );
			$description         = '';
			$control_description = '';
		}

		$this->add_section(
			'header_image',
			array(
				'title'          => $title,
				'description'    => $description,
				'theme_supports' => 'custom-header',
				'priority'       => 60,
			)
		);

		$this->add_setting(
			'header_video',
			array(
				'theme_supports'    => array( 'custom-header', 'video' ),
				'transport'         => 'postMessage',
				'sanitize_callback' => 'absint',
				'validate_callback' => array( $this, '_validate_header_video' ),
			)
		);

		$this->add_setting(
			'external_header_video',
			array(
				'theme_supports'    => array( 'custom-header', 'video' ),
				'transport'         => 'postMessage',
				'sanitize_callback' => array( $this, '_sanitize_external_header_video' ),
				'validate_callback' => array( $this, '_validate_external_header_video' ),
			)
		);

		$this->add_setting(
			new WP_Customize_Filter_Setting(
				$this,
				'header_image',
				array(
					'default'        => sprintf( get_theme_support( 'custom-header', 'default-image' ), get_template_directory_uri(), get_stylesheet_directory_uri() ),
					'theme_supports' => 'custom-header',
				)
			)
		);

		$this->add_setting(
			new WP_Customize_Header_Image_Setting(
				$this,
				'header_image_data',
				array(
					'theme_supports' => 'custom-header',
				)
			)
		);

		/*
		 * Switch image settings to postMessage when video support is enabled since
		 * it entails that the_custom_header_markup() will be used, and thus selective
		 * refresh can be utilized.
		 */
		if ( current_theme_supports( 'custom-header', 'video' ) ) {
			$this->get_setting( 'header_image' )->transport      = 'postMessage';
			$this->get_setting( 'header_image_data' )->transport = 'postMessage';
		}

		$this->add_control(
			new WP_Customize_Media_Control(
				$this,
				'header_video',
				array(
					'theme_supports'  => array( 'custom-header', 'video' ),
					'label'           => __( 'Header Video' ),
					'description'     => $control_description,
					'section'         => 'header_image',
					'mime_type'       => 'video',
					'active_callback' => 'is_header_video_active',
				)
			)
		);

		$this->add_control(
			'external_header_video',
			array(
				'theme_supports'  => array( 'custom-header', 'video' ),
				'type'            => 'url',
				'description'     => __( 'Or, enter a YouTube URL:' ),
				'section'         => 'header_image',
				'active_callback' => 'is_header_video_active',
			)
		);

		$this->add_control( new WP_Customize_Header_Image_Control( $this ) );

		$this->selective_refresh->add_partial(
			'custom_header',
			array(
				'selector'            => '#wp-custom-header',
				'render_callback'     => 'the_custom_header_markup',
				'settings'            => array( 'header_video', 'external_header_video', 'header_image' ), // The image is used as a video fallback here.
				'container_inclusive' => true,
			)
		);

		/* Custom Background */

		$this->add_section(
			'background_image',
			array(
				'title'          => __( 'Background Image' ),
				'theme_supports' => 'custom-background',
				'priority'       => 80,
			)
		);

		$this->add_setting(
			'background_image',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-image' ),
				'theme_supports'    => 'custom-background',
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
			)
		);

		$this->add_setting(
			new WP_Customize_Background_Image_Setting(
				$this,
				'background_image_thumb',
				array(
					'theme_supports'    => 'custom-background',
					'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
				)
			)
		);

		$this->add_control( new WP_Customize_Background_Image_Control( $this ) );

		$this->add_setting(
			'background_preset',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-preset' ),
				'theme_supports'    => 'custom-background',
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
			)
		);

		$this->add_control(
			'background_preset',
			array(
				'label'   => _x( 'Preset', 'Background Preset' ),
				'section' => 'background_image',
				'type'    => 'select',
				'choices' => array(
					'default' => _x( 'Default', 'Default Preset' ),
					'fill'    => __( 'Fill Screen' ),
					'fit'     => __( 'Fit to Screen' ),
					'repeat'  => _x( 'Repeat', 'Repeat Image' ),
					'custom'  => _x( 'Custom', 'Custom Preset' ),
				),
			)
		);

		$this->add_setting(
			'background_position_x',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-position-x' ),
				'theme_supports'    => 'custom-background',
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
			)
		);

		$this->add_setting(
			'background_position_y',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-position-y' ),
				'theme_supports'    => 'custom-background',
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
			)
		);

		$this->add_control(
			new WP_Customize_Background_Position_Control(
				$this,
				'background_position',
				array(
					'label'    => __( 'Image Position' ),
					'section'  => 'background_image',
					'settings' => array(
						'x' => 'background_position_x',
						'y' => 'background_position_y',
					),
				)
			)
		);

		$this->add_setting(
			'background_size',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-size' ),
				'theme_supports'    => 'custom-background',
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
			)
		);

		$this->add_control(
			'background_size',
			array(
				'label'   => __( 'Image Size' ),
				'section' => 'background_image',
				'type'    => 'select',
				'choices' => array(
					'auto'    => _x( 'Original', 'Original Size' ),
					'contain' => __( 'Fit to Screen' ),
					'cover'   => __( 'Fill Screen' ),
				),
			)
		);

		$this->add_setting(
			'background_repeat',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-repeat' ),
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
				'theme_supports'    => 'custom-background',
			)
		);

		$this->add_control(
			'background_repeat',
			array(
				'label'   => __( 'Repeat Background Image' ),
				'section' => 'background_image',
				'type'    => 'checkbox',
			)
		);

		$this->add_setting(
			'background_attachment',
			array(
				'default'           => get_theme_support( 'custom-background', 'default-attachment' ),
				'sanitize_callback' => array( $this, '_sanitize_background_setting' ),
				'theme_supports'    => 'custom-background',
			)
		);

		$this->add_control(
			'background_attachment',
			array(
				'label'   => __( 'Scroll with Page' ),
				'section' => 'background_image',
				'type'    => 'checkbox',
			)
		);

		/*
		 * If the theme is using the default background callback, we can update
		 * the background CSS using postMessage.
		 */
		if ( get_theme_support( 'custom-background', 'wp-head-callback' ) === '_custom_background_cb' ) {
			foreach ( array( 'color', 'image', 'preset', 'position_x', 'position_y', 'size', 'repeat', 'attachment' ) as $prop ) {
				$this->get_setting( 'background_' . $prop )->transport = 'postMessage';
			}
		}

		/*
		 * Static Front Page
		 * See also https://core.trac.wordpress.org/ticket/19627 which introduces the static-front-page theme_support.
		 * The following replicates behavior from options-reading.php.
		 */

		$this->add_section(
			'static_front_page',
			array(
				'title'           => __( 'Homepage Settings' ),
				'priority'        => 120,
				'description'     => __( 'You can choose what&#8217;s displayed on the homepage of your site. It can be posts in reverse chronological order (classic blog), or a fixed/static page. To set a static homepage, you first need to create two Pages. One will become the homepage, and the other will be where your posts are displayed.' ),
				'active_callback' => array( $this, 'has_published_pages' ),
			)
		);

		$this->add_setting(
			'show_on_front',
			array(
				'default'    => get_option( 'show_on_front' ),
				'capability' => 'manage_options',
				'type'       => 'option',
			)
		);

		$this->add_control(
			'show_on_front',
			array(
				'label'   => __( 'Your homepage displays' ),
				'section' => 'static_front_page',
				'type'    => 'radio',
				'choices' => array(
					'posts' => __( 'Your latest posts' ),
					'page'  => __( 'A static page' ),
				),
			)
		);

		$this->add_setting(
			'page_on_front',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
			)
		);

		$this->add_control(
			'page_on_front',
			array(
				'label'          => __( 'Homepage' ),
				'section'        => 'static_front_page',
				'type'           => 'dropdown-pages',
				'allow_addition' => true,
			)
		);

		$this->add_setting(
			'page_for_posts',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
			)
		);

		$this->add_control(
			'page_for_posts',
			array(
				'label'          => __( 'Posts page' ),
				'section'        => 'static_front_page',
				'type'           => 'dropdown-pages',
				'allow_addition' => true,
			)
		);

		/* Custom CSS */
		$section_description  = '<p>';
		$section_description .= __( 'Add your own CSS code here to customize the appearance and layout of your site.' );
		$section_description .= sprintf(
			' <a href="%1$s" class="external-link" target="_blank">%2$s<span class="screen-reader-text"> %3$s</span></a>',
			esc_url( __( 'https://developer.wordpress.org/advanced-administration/wordpress/css/' ) ),
			__( 'Learn more about CSS' ),
			/* translators: Hidden accessibility text. */
			__( '(opens in a new tab)' )
		);
		$section_description .= '</p>';

		$section_description .= '<p id="editor-keyboard-trap-help-1">' . __( 'When using a keyboard to navigate:' ) . '</p>';
		$section_description .= '<ul>';
		$section_description .= '<li id="editor-keyboard-trap-help-2">' . __( 'In the editing area, the Tab key enters a tab character.' ) . '</li>';
		$section_description .= '<li id="editor-keyboard-trap-help-3">' . __( 'To move away from this area, press the Esc key followed by the Tab key.' ) . '</li>';
		$section_description .= '<li id="editor-keyboard-trap-help-4">' . __( 'Screen reader users: when in forms mode, you may need to press the Esc key twice.' ) . '</li>';
		$section_description .= '</ul>';

		if ( 'false' !== wp_get_current_user()->syntax_highlighting ) {
			$section_description .= '<p>';
			$section_description .= sprintf(
				/* translators: 1: Link to user profile, 2: Additional link attributes, 3: Accessibility text. */
				__( 'The edit field automatically highlights code syntax. You can disable this in your <a href="%1$s" %2$s>user profile%3$s</a> to work in plain text mode.' ),
				esc_url( get_edit_profile_url() ),
				'class="external-link" target="_blank"',
				sprintf(
					'<span class="screen-reader-text"> %s</span>',
					/* translators: Hidden accessibility text. */
					__( '(opens in a new tab)' )
				)
			);
			$section_description .= '</p>';
		}

		$section_description .= '<p class="section-description-buttons">';
		$section_description .= '<button type="button" class="button-link section-description-close">' . __( 'Close' ) . '</button>';
		$section_description .= '</p>';

		$this->add_section(
			'custom_css',
			array(
				'title'              => __( 'Additional CSS' ),
				'priority'           => 200,
				'description_hidden' => true,
				'description'        => $section_description,
			)
		);

		$custom_css_setting = new WP_Customize_Custom_CSS_Setting(
			$this,
			sprintf( 'custom_css[%s]', get_stylesheet() ),
			array(
				'capability' => 'edit_css',
				'default'    => '',
			)
		);
		$this->add_setting( $custom_css_setting );

		$this->add_control(
			new WP_Customize_Code_Editor_Control(
				$this,
				'custom_css',
				array(
					'label'       => __( 'CSS code' ),
					'section'     => 'custom_css',
					'settings'    => array( 'default' => $custom_css_setting->id ),
					'code_type'   => 'text/css',
					'input_attrs' => array(
						'aria-describedby' => 'editor-keyboard-trap-help-1 editor-keyboard-trap-help-2 editor-keyboard-trap-help-3 editor-keyboard-trap-help-4',
					),
				)
			)
		);
	}

	/**
	 * Returns whether there are published pages.
	 *
	 * Used as active callback for static front page section and controls.
	 *
	 * @since 4.7.0
	 *
	 * @return bool Whether there are published (or to be published) pages.
	 */
	public function has_published_pages() {

		$setting = $this->get_setting( 'nav_menus_created_posts' );
		if ( $setting ) {
			foreach ( $setting->value() as $post_id ) {
				if ( 'page' === get_post_type( $post_id ) ) {
					return true;
				}
			}
		}

		return 0 !== count(
			get_pages(
				array(
					'number'       => 1,
					'hierarchical' => 0,
				)
			)
		);
	}

	/**
	 * Adds settings from the POST data that were not added with code, e.g. dynamically-created settings for Widgets
	 *
	 * @since 4.2.0
	 *
	 * @see add_dynamic_settings()
	 */
	public function register_dynamic_settings() {
		$setting_ids = array_keys( $this->unsanitized_post_values() );
		$this->add_dynamic_settings( $setting_ids );
	}

	/**
	 * Loads themes into the theme browsing/installation UI.
	 *
	 * @since 4.9.0
	 */
	public function handle_load_themes_request() {
		check_ajax_referer( 'switch_themes', 'nonce' );

		if ( ! current_user_can( 'switch_themes' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST['theme_action'] ) ) {
			wp_send_json_error( 'missing_theme_action' );
		}
		$theme_action = sanitize_key( $_POST['theme_action'] );
		$themes       = array();
		$args         = array();

		// Define query filters based on user input.
		if ( ! array_key_exists( 'search', $_POST ) ) {
			$args['search'] = '';
		} else {
			$args['search'] = sanitize_text_field( wp_unslash( $_POST['search'] ) );
		}

		if ( ! array_key_exists( 'tags', $_POST ) ) {
			$args['tag'] = '';
		} else {
			$args['tag'] = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['tags'] ) );
		}

		if ( ! array_key_exists( 'page', $_POST ) ) {
			$args['page'] = 1;
		} else {
			$args['page'] = absint( $_POST['page'] );
		}

		require_once ABSPATH . 'wp-admin/includes/theme.php';

		if ( 'installed' === $theme_action ) {

			// Load all installed themes from wp_prepare_themes_for_js().
			$themes = array( 'themes' => array() );
			foreach ( wp_prepare_themes_for_js() as $theme ) {
				$theme['type']      = 'installed';
				$theme['active']    = ( isset( $_POST['customized_theme'] ) && $_POST['customized_theme'] === $theme['id'] );
				$themes['themes'][] = $theme;
			}
		} elseif ( 'wporg' === $theme_action ) {

			// Load WordPress.org themes from the .org API and normalize data to match installed theme objects.
			if ( ! current_user_can( 'install_themes' ) ) {
				wp_die( -1 );
			}

			// Arguments for all queries.
			$wporg_args = array(
				'per_page' => 100,
				'fields'   => array(
					'reviews_url' => true, // Explicitly request the reviews URL to be linked from the customizer.
				),
			);

			$args = array_merge( $wporg_args, $args );

			if ( '' === $args['search'] && '' === $args['tag'] ) {
				$args['browse'] = 'new'; // Sort by latest themes by default.
			}

			// Load themes from the .org API.
			$themes = themes_api( 'query_themes', $args );
			if ( is_wp_error( $themes ) ) {
				wp_send_json_error();
			}

			// This list matches the allowed tags in wp-admin/includes/theme-install.php.
			$themes_allowedtags                     = array_fill_keys(
				array( 'a', 'abbr', 'acronym', 'code', 'pre', 'em', 'strong', 'div', 'p', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img' ),
				array()
			);
			$themes_allowedtags['a']                = array_fill_keys( array( 'href', 'title', 'target' ), true );
			$themes_allowedtags['acronym']['title'] = true;
			$themes_allowedtags['abbr']['title']    = true;
			$themes_allowedtags['img']              = array_fill_keys( array( 'src', 'class', 'alt' ), true );

			// Prepare a list of installed themes to check against before the loop.
			$installed_themes = array();
			$wp_themes        = wp_get_themes();
			foreach ( $wp_themes as $theme ) {
				$installed_themes[] = $theme->get_stylesheet();
			}
			$update_php = network_admin_url( 'update.php?action=install-theme' );

			// Set up properties for themes available on WordPress.org.
			foreach ( $themes->themes as &$theme ) {
				$theme->install_url = add_query_arg(
					array(
						'theme'    => $theme->slug,
						'_wpnonce' => wp_create_nonce( 'install-theme_' . $theme->slug ),
					),
					$update_php
				);

				$theme->name        = wp_kses( $theme->name, $themes_allowedtags );
				$theme->version     = wp_kses( $theme->version, $themes_allowedtags );
				$theme->description = wp_kses( $theme->description, $themes_allowedtags );
				$theme->stars       = wp_star_rating(
					array(
						'rating' => $theme->rating,
						'type'   => 'percent',
						'number' => $theme->num_ratings,
						'echo'   => false,
					)
				);
				$theme->num_ratings = number_format_i18n( $theme->num_ratings );
				$theme->preview_url = set_url_scheme( $theme->preview_url );

				// Handle themes that are already installed as installed themes.
				if ( in_array( $theme->slug, $installed_themes, true ) ) {
					$theme->type = 'installed';
				} else {
					$theme->type = $theme_action;
				}

				// Set active based on customized theme.
				$theme->active = ( isset( $_POST['customized_theme'] ) && $_POST['customized_theme'] === $theme->slug );

				// Map available theme properties to installed theme properties.
				$theme->id            = $theme->slug;
				$theme->screenshot    = array( $theme->screenshot_url );
				$theme->authorAndUri  = wp_kses( $theme->author['display_name'], $themes_allowedtags );
				$theme->compatibleWP  = is_wp_version_compatible( $theme->requires ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$theme->compatiblePHP = is_php_version_compatible( $theme->requires_php ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName

				if ( isset( $theme->parent ) ) {
					$theme->parent = $theme->parent['slug'];
				} else {
					$theme->parent = false;
				}
				unset( $theme->slug );
				unset( $theme->screenshot_url );
				unset( $theme->author );
			} // End foreach().
		} // End if().

		/**
		 * Filters the theme data loaded in the customizer.
		 *
		 * This allows theme data to be loading from an external source,
		 * or modification of data loaded from `wp_prepare_themes_for_js()`
		 * or WordPress.org via `themes_api()`.
		 *
		 * @since 4.9.0
		 *
		 * @see wp_prepare_themes_for_js()
		 * @see themes_api()
		 * @see WP_Customize_Manager::__construct()
		 *
		 * @param array|stdClass       $themes  Nested array or object of theme data.
		 * @param array                $args    List of arguments, such as page, search term, and tags to query for.
		 * @param WP_Customize_Manager $manager Instance of Customize manager.
		 */
		$themes = apply_filters( 'customize_load_themes', $themes, $args, $this );

		wp_send_json_success( $themes );
	}


	/**
	 * Callback for validating the header_textcolor value.
	 *
	 * Accepts 'blank', and otherwise uses sanitize_hex_color_no_hash().
	 * Returns default text color if hex color is empty.
	 *
	 * @since 3.4.0
	 *
	 * @param string $color
	 * @return mixed
	 */
	public function _sanitize_header_textcolor( $color ) {
		if ( 'blank' === $color ) {
			return 'blank';
		}

		$color = sanitize_hex_color_no_hash( $color );
		if ( empty( $color ) ) {
			$color = get_theme_support( 'custom-header', 'default-text-color' );
		}

		return $color;
	}

	/**
	 * Callback for validating a background setting value.
	 *
	 * @since 4.7.0
	 *
	 * @param string               $value   Repeat value.
	 * @param WP_Customize_Setting $setting Setting.
	 * @return string|WP_Error Background value or validation error.
	 */
	public function _sanitize_background_setting( $value, $setting ) {
		if ( 'background_repeat' === $setting->id ) {
			if ( ! in_array( $value, array( 'repeat-x', 'repeat-y', 'repeat', 'no-repeat' ), true ) ) {
				return new WP_Error( 'invalid_value', __( 'Invalid value for background repeat.' ) );
			}
		} elseif ( 'background_attachment' === $setting->id ) {
			if ( ! in_array( $value, array( 'fixed', 'scroll' ), true ) ) {
				return new WP_Error( 'invalid_value', __( 'Invalid value for background attachment.' ) );
			}
		} elseif ( 'background_position_x' === $setting->id ) {
			if ( ! in_array( $value, array( 'left', 'center', 'right' ), true ) ) {
				return new WP_Error( 'invalid_value', __( 'Invalid value for background position X.' ) );
			}
		} elseif ( 'background_position_y' === $setting->id ) {
			if ( ! in_array( $value, array( 'top', 'center', 'bottom' ), true ) ) {
				return new WP_Error( 'invalid_value', __( 'Invalid value for background position Y.' ) );
			}
		} elseif ( 'background_size' === $setting->id ) {
			if ( ! in_array( $value, array( 'auto', 'contain', 'cover' ), true ) ) {
				return new WP_Error( 'invalid_value', __( 'Invalid value for background size.' ) );
			}
		} elseif ( 'background_preset' === $setting->id ) {
			if ( ! in_array( $value, array( 'default', 'fill', 'fit', 'repeat', 'custom' ), true ) ) {
				return new WP_Error( 'invalid_value', __( 'Invalid value for background size.' ) );
			}
		} elseif ( 'background_image' === $setting->id || 'background_image_thumb' === $setting->id ) {
			$value = empty( $value ) ? '' : sanitize_url( $value );
		} else {
			return new WP_Error( 'unrecognized_setting', __( 'Unrecognized background setting.' ) );
		}
		return $value;
	}

	/**
	 * Exports header video settings to facilitate selective refresh.
	 *
	 * @since 4.7.0
	 *
	 * @param array                          $response          Response.
	 * @param WP_Customize_Selective_Refresh $selective_refresh Selective refresh component.
	 * @param array                          $partials          Array of partials.
	 * @return array
	 */
	public function export_header_video_settings( $response, $selective_refresh, $partials ) {
		if ( isset( $partials['custom_header'] ) ) {
			$response['custom_header_settings'] = get_header_video_settings();
		}

		return $response;
	}

	/**
	 * Callback for validating the header_video value.
	 *
	 * Ensures that the selected video is less than 8MB and provides an error message.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Error $validity
	 * @param mixed    $value
	 * @return mixed
	 */
	public function _validate_header_video( $validity, $value ) {
		$video = get_attached_file( absint( $value ) );
		if ( $video ) {
			$size = filesize( $video );
			if ( $size > 8 * MB_IN_BYTES ) {
				$validity->add(
					'size_too_large',
					__( 'This video file is too large to use as a header video. Try a shorter video or optimize the compression settings and re-upload a file that is less than 8MB. Or, upload your video to YouTube and link it with the option below.' )
				);
			}
			if ( ! str_ends_with( $video, '.mp4' ) && ! str_ends_with( $video, '.mov' ) ) { // Check for .mp4 or .mov format, which (assuming h.264 encoding) are the only cross-browser-supported formats.
				$validity->add(
					'invalid_file_type',
					sprintf(
						/* translators: 1: .mp4, 2: .mov */
						__( 'Only %1$s or %2$s files may be used for header video. Please convert your video file and try again, or, upload your video to YouTube and link it with the option below.' ),
						'<code>.mp4</code>',
						'<code>.mov</code>'
					)
				);
			}
		}
		return $validity;
	}

	/**
	 * Callback for validating the external_header_video value.
	 *
	 * Ensures that the provided URL is supported.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Error $validity
	 * @param mixed    $value
	 * @return mixed
	 */
	public function _validate_external_header_video( $validity, $value ) {
		$video = sanitize_url( $value );
		if ( $video ) {
			if ( ! preg_match( '#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#', $video ) ) {
				$validity->add( 'invalid_url', __( 'Please enter a valid YouTube URL.' ) );
			}
		}
		return $validity;
	}

	/**
	 * Callback for sanitizing the external_header_video value.
	 *
	 * @since 4.7.1
	 *
	 * @param string $value URL.
	 * @return string Sanitized URL.
	 */
	public function _sanitize_external_header_video( $value ) {
		return sanitize_url( trim( $value ) );
	}

	/**
	 * Callback for rendering the custom logo, used in the custom_logo partial.
	 *
	 * This method exists because the partial object and context data are passed
	 * into a partial's render_callback so we cannot use get_custom_logo() as
	 * the render_callback directly since it expects a blog ID as the first
	 * argument.
	 *
	 * @see WP_Customize_Manager::register_controls()
	 *
	 * @since 4.5.0
	 *
	 * @return string Custom logo.
	 */
	public function _render_custom_logo_partial() {
		return get_custom_logo();
	}
}
