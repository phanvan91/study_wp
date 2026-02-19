<?php
/**
 * Lớp WP_Theme
 *
 * @package WordPress
 * @subpackage Theme
 * @since 3.4.0
 */
#[AllowDynamicProperties]
final class WP_Theme implements ArrayAccess {

	/**
	 * Liệu giao diện có được đánh dấu là có thể cập nhật hay không.
	 *
	 * @since 4.4.0
	 * @var bool
	 *
	 * @see WP_MS_Themes_List_Table
	 */
	public $update = false;

	/**
	 * Các header cho tệp style.css.
	 *
	 * @since 3.4.0
	 * @since 5.4.0 Thêm header `Requires at least` và `Requires PHP`.
	 * @since 6.1.0 Thêm header `Update URI`.
	 * @var string[]
	 */
	private static $file_headers = array(
		'Name'        => 'Theme Name',
		'ThemeURI'    => 'Theme URI',
		'Description' => 'Description',
		'Author'      => 'Author',
		'AuthorURI'   => 'Author URI',
		'Version'     => 'Version',
		'Template'    => 'Template',
		'Status'      => 'Status',
		'Tags'        => 'Tags',
		'TextDomain'  => 'Text Domain',
		'DomainPath'  => 'Domain Path',
		'RequiresWP'  => 'Requires at least',
		'RequiresPHP' => 'Requires PHP',
		'UpdateURI'   => 'Update URI',
	);

	/**
	 * Các giao diện mặc định.
	 *
	 * @since 3.4.0
	 * @since 3.5.0 Thêm giao diện Twenty Twelve.
	 * @since 3.6.0 Thêm giao diện Twenty Thirteen.
	 * @since 3.8.0 Thêm giao diện Twenty Fourteen.
	 * @since 4.1.0 Thêm giao diện Twenty Fifteen.
	 * @since 4.4.0 Thêm giao diện Twenty Sixteen.
	 * @since 4.7.0 Thêm giao diện Twenty Seventeen.
	 * @since 5.0.0 Thêm giao diện Twenty Nineteen.
	 * @since 5.3.0 Thêm giao diện Twenty Twenty.
	 * @since 5.6.0 Thêm giao diện Twenty Twenty-One.
	 * @since 5.9.0 Thêm giao diện Twenty Twenty-Two.
	 * @since 6.1.0 Thêm giao diện Twenty Twenty-Three.
	 * @since 6.4.0 Thêm giao diện Twenty Twenty-Four.
	 * @since 6.7.0 Thêm giao diện Twenty Twenty-Five.
	 * @var string[]
	 */
	private static $default_themes = array(
		'classic'           => 'WordPress Classic',
		'default'           => 'WordPress Default',
		'twentyten'         => 'Twenty Ten',
		'twentyeleven'      => 'Twenty Eleven',
		'twentytwelve'      => 'Twenty Twelve',
		'twentythirteen'    => 'Twenty Thirteen',
		'twentyfourteen'    => 'Twenty Fourteen',
		'twentyfifteen'     => 'Twenty Fifteen',
		'twentysixteen'     => 'Twenty Sixteen',
		'twentyseventeen'   => 'Twenty Seventeen',
		'twentynineteen'    => 'Twenty Nineteen',
		'twentytwenty'      => 'Twenty Twenty',
		'twentytwentyone'   => 'Twenty Twenty-One',
		'twentytwentytwo'   => 'Twenty Twenty-Two',
		'twentytwentythree' => 'Twenty Twenty-Three',
		'twentytwentyfour'  => 'Twenty Twenty-Four',
		'twentytwentyfive'  => 'Twenty Twenty-Five',
	);

	/**
	 * Các thẻ giao diện đã được đổi tên.
	 *
	 * @since 3.8.0
	 * @var string[]
	 */
	private static $tag_map = array(
		'fixed-width'    => 'fixed-layout',
		'flexible-width' => 'fluid-layout',
	);

	/**
	 * Đường dẫn tuyệt đối đến thư mục gốc giao diện, thường là wp-content/themes.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	private $theme_root;

	/**
	 * Dữ liệu header từ tệp style.css của giao diện.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	private $headers = array();

	/**
	 * Dữ liệu header từ tệp style.css của giao diện sau khi đã được làm sạch.
	 *
	 * @since 3.4.0
	 * @var array
	 */
	private $headers_sanitized;

	/**
	 * Liệu giao diện này có phải là giao diện block hay không.
	 *
	 * @since 6.2.0
	 * @var bool
	 */
	private $block_theme;

	/**
	 * Tên header từ style.css của giao diện sau khi được dịch.
	 *
	 * Được lưu cache do các hàm sắp xếp chạy trên tên đã dịch.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	private $name_translated;

	/**
	 * Các lỗi gặp phải khi khởi tạo giao diện.
	 *
	 * @since 3.4.0
	 * @var WP_Error
	 */
	private $errors;

	/**
	 * Tên thư mục chứa các tệp của giao diện, bên trong thư mục gốc giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là tên thư mục của giao diện con.
	 * Nếu không, 'stylesheet' giống với 'template'.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	private $stylesheet;

	/**
	 * Tên thư mục chứa các tệp của giao diện, bên trong thư mục gốc giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là tên thư mục của giao diện cha.
	 * Nếu không, 'template' giống với 'stylesheet'.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	private $template;

	/**
	 * Tham chiếu đến giao diện cha, trong trường hợp giao diện con.
	 *
	 * @since 3.4.0
	 * @var WP_Theme
	 */
	private $parent;

	/**
	 * URL đến thư mục gốc giao diện, thường là URL tuyệt đối tới wp-content/themes.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	private $theme_root_uri;

	/**
	 * Cờ cho biết textdomain của giao diện đã được tải chưa.
	 *
	 * @since 3.4.0
	 * @var bool
	 */
	private $textdomain_loaded;

	/**
	 * Lưu trữ hash md5 của thư mục gốc giao diện, dùng làm khóa cache.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	private $cache_hash;

	/**
	 * Các thư mục template block.
	 *
	 * @since 6.4.0
	 * @var string[]
	 */
	private $block_template_folders;

	/**
	 * Giá trị mặc định cho các thư mục template.
	 *
	 * @since 6.4.0
	 * @var string[]
	 */
	private $default_template_folders = array(
		'wp_template'      => 'templates',
		'wp_template_part' => 'parts',
	);

	/**
	 * Cờ cho biết bộ nhớ cache giao diện có nên được lưu vĩnh viễn hay không.
	 *
	 * Mặc định là false. Có thể thiết lập bằng bộ lọc {@see 'wp_cache_themes_persistently'}.
	 *
	 * @since 3.4.0
	 * @var bool
	 */
	private static $persistently_cache;

	/**
	 * Thời gian hết hạn cho bộ nhớ cache giao diện.
	 *
	 * Mặc định bộ nhớ cache không được sử dụng, nên giá trị này không có tác dụng.
	 *
	 * @since 3.4.0
	 * @var bool
	 */
	private static $cache_expiration = 1800;

	/**
	 * Hàm khởi tạo cho WP_Theme.
	 *
	 * @since 3.4.0
	 *
	 * @global string[] $wp_theme_directories
	 *
	 * @param string        $theme_dir  Thư mục của giao diện trong theme_root.
	 * @param string        $theme_root Thư mục gốc giao diện.
	 * @param WP_Theme|null $_child Nếu đây là giao diện cha, giao diện con có thể được truyền vào để xác thực.
	 */
	public function __construct( $theme_dir, $theme_root, $_child = null ) {
		global $wp_theme_directories;

		// Khởi tạo bộ nhớ cache ở lần chạy đầu tiên.
		if ( ! isset( self::$persistently_cache ) ) {
			/** This action is documented in wp-includes/theme.php */
			self::$persistently_cache = apply_filters( 'wp_cache_themes_persistently', false, 'WP_Theme' );
			if ( self::$persistently_cache ) {
				wp_cache_add_global_groups( 'themes' );
				if ( is_int( self::$persistently_cache ) ) {
					self::$cache_expiration = self::$persistently_cache;
				}
			} else {
				wp_cache_add_non_persistent_groups( 'themes' );
			}
		}

		// Xử lý tên thư mục giao diện dạng số thành chuỗi.
		$theme_dir = (string) $theme_dir;

		$this->theme_root = $theme_root;
		$this->stylesheet = $theme_dir;

		// Sửa lại trường hợp giao diện là 'some-directory/some-theme' nhưng 'some-directory' được truyền vào như phần của theme root.
		if ( ! in_array( $theme_root, (array) $wp_theme_directories, true )
			&& in_array( dirname( $theme_root ), (array) $wp_theme_directories, true )
		) {
			$this->stylesheet = basename( $this->theme_root ) . '/' . $this->stylesheet;
			$this->theme_root = dirname( $theme_root );
		}

		$this->cache_hash = md5( $this->theme_root . '/' . $this->stylesheet );
		$theme_file       = $this->stylesheet . '/style.css';

		$cache = $this->cache_get( 'theme' );

		if ( is_array( $cache ) ) {
			foreach ( array( 'block_template_folders', 'block_theme', 'errors', 'headers', 'template' ) as $key ) {
				if ( isset( $cache[ $key ] ) ) {
					$this->$key = $cache[ $key ];
				}
			}
			if ( $this->errors ) {
				return;
			}
			if ( isset( $cache['theme_root_template'] ) ) {
				$theme_root_template = $cache['theme_root_template'];
			}
		} elseif ( ! file_exists( $this->theme_root . '/' . $theme_file ) ) {
			$this->headers['Name'] = $this->stylesheet;
			if ( ! file_exists( $this->theme_root . '/' . $this->stylesheet ) ) {
				$this->errors = new WP_Error(
					'theme_not_found',
					sprintf(
						/* translators: %s: Theme directory name. */
						__( 'The theme directory "%s" does not exist.' ),
						esc_html( $this->stylesheet )
					)
				);
			} else {
				$this->errors = new WP_Error( 'theme_no_stylesheet', __( 'Stylesheet is missing.' ) );
			}
			$this->template               = $this->stylesheet;
			$this->block_theme            = false;
			$this->block_template_folders = $this->default_template_folders;
			$this->cache_add(
				'theme',
				array(
					'block_template_folders' => $this->block_template_folders,
					'block_theme'            => $this->block_theme,
					'headers'                => $this->headers,
					'errors'                 => $this->errors,
					'stylesheet'             => $this->stylesheet,
					'template'               => $this->template,
				)
			);
			if ( ! file_exists( $this->theme_root ) ) { // Không cache trường hợp này.
				$this->errors->add( 'theme_root_missing', __( '<strong>Error:</strong> The themes directory is either empty or does not exist. Please check your installation.' ) );
			}
			return;
		} elseif ( ! is_readable( $this->theme_root . '/' . $theme_file ) ) {
			$this->headers['Name']        = $this->stylesheet;
			$this->errors                 = new WP_Error( 'theme_stylesheet_not_readable', __( 'Stylesheet is not readable.' ) );
			$this->template               = $this->stylesheet;
			$this->block_theme            = false;
			$this->block_template_folders = $this->default_template_folders;
			$this->cache_add(
				'theme',
				array(
					'block_template_folders' => $this->block_template_folders,
					'block_theme'            => $this->block_theme,
					'headers'                => $this->headers,
					'errors'                 => $this->errors,
					'stylesheet'             => $this->stylesheet,
					'template'               => $this->template,
				)
			);
			return;
		} else {
			$this->headers = get_file_data( $this->theme_root . '/' . $theme_file, self::$file_headers, 'theme' );
			/*
			 * Giao diện mặc định luôn được ưu tiên hơn các giao diện giả mạo.
			 * Xác định đúng các giao diện mặc định nằm trong thư mục con của wp-content/themes.
			 */
			$default_theme_slug = array_search( $this->headers['Name'], self::$default_themes, true );
			if ( $default_theme_slug ) {
				if ( basename( $this->stylesheet ) !== $default_theme_slug ) {
					$this->headers['Name'] .= '/' . $this->stylesheet;
				}
			}
		}

		if ( ! $this->template && $this->stylesheet === $this->headers['Template'] ) {
			$this->errors = new WP_Error(
				'theme_child_invalid',
				sprintf(
					/* translators: %s: Template. */
					__( 'The theme defines itself as its parent theme. Please check the %s header.' ),
					'<code>Template</code>'
				)
			);
			$this->cache_add(
				'theme',
				array(
					'block_template_folders' => $this->get_block_template_folders(),
					'block_theme'            => $this->is_block_theme(),
					'headers'                => $this->headers,
					'errors'                 => $this->errors,
					'stylesheet'             => $this->stylesheet,
				)
			);

			return;
		}

		// (Nếu template được lấy từ cache [và không có lỗi], ta biết nó hợp lệ.)
		if ( ! $this->template ) {
			$this->template = $this->headers['Template'];
		}

		if ( ! $this->template ) {
			$this->template = $this->stylesheet;
			$theme_path     = $this->theme_root . '/' . $this->stylesheet;

			if ( ! $this->is_block_theme() && ! file_exists( $theme_path . '/index.php' ) ) {
				$error_message = sprintf(
					/* translators: 1: templates/index.html, 2: index.php, 3: Documentation URL, 4: Template, 5: style.css */
					__( 'Template is missing. Standalone themes need to have a %1$s or %2$s template file. <a href="%3$s">Child themes</a> need to have a %4$s header in the %5$s stylesheet.' ),
					'<code>templates/index.html</code>',
					'<code>index.php</code>',
					__( 'https://developer.wordpress.org/themes/advanced-topics/child-themes/' ),
					'<code>Template</code>',
					'<code>style.css</code>'
				);
				$this->errors = new WP_Error( 'theme_no_index', $error_message );
				$this->cache_add(
					'theme',
					array(
						'block_template_folders' => $this->get_block_template_folders(),
						'block_theme'            => $this->block_theme,
						'headers'                => $this->headers,
						'errors'                 => $this->errors,
						'stylesheet'             => $this->stylesheet,
						'template'               => $this->template,
					)
				);
				return;
			}
		}

		// Nếu lấy dữ liệu từ cache, ta có thể giả định 'template' đang trỏ đúng chỗ.
		if ( ! is_array( $cache )
			&& $this->template !== $this->stylesheet
			&& ! file_exists( $this->theme_root . '/' . $this->template . '/index.php' )
		) {
			/*
			 * Nếu đang ở trong thư mục chứa giao diện bên trong /themes, tìm giao diện cha ở gần đó.
			 * wp-content/themes/directory-of-themes/*
			 */
			$parent_dir  = dirname( $this->stylesheet );
			$directories = search_theme_directories();

			if ( '.' !== $parent_dir
				&& file_exists( $this->theme_root . '/' . $parent_dir . '/' . $this->template . '/index.php' )
			) {
				$this->template = $parent_dir . '/' . $this->template;
			} elseif ( $directories && isset( $directories[ $this->template ] ) ) {
				/*
				 * Tìm template trong kết quả search_theme_directories(), trong trường hợp nó ở theme root khác.
				 * Chúng ta không tìm trong thư mục con, chỉ trong thư mục gốc giao diện.
				 */
				$theme_root_template = $directories[ $this->template ]['theme_root'];
			} else {
				// Giao diện cha bị thiếu.
				$this->errors = new WP_Error(
					'theme_no_parent',
					sprintf(
						/* translators: %s: Theme directory name. */
						__( 'The parent theme is missing. Please install the "%s" parent theme.' ),
						esc_html( $this->template )
					)
				);
				$this->cache_add(
					'theme',
					array(
						'block_template_folders' => $this->get_block_template_folders(),
						'block_theme'            => $this->is_block_theme(),
						'headers'                => $this->headers,
						'errors'                 => $this->errors,
						'stylesheet'             => $this->stylesheet,
						'template'               => $this->template,
					)
				);
				$this->parent = new WP_Theme( $this->template, $this->theme_root, $this );
				return;
			}
		}

		// Thiết lập giao diện cha, nếu là giao diện con.
		if ( $this->template !== $this->stylesheet ) {
			// Nếu chúng ta là cha, thì có vấn đề. Chỉ cho phép hai thế hệ! Hủy bỏ.
			if ( $_child instanceof WP_Theme && $_child->template === $this->stylesheet ) {
				$_child->parent = null;
				$_child->errors = new WP_Error(
					'theme_parent_invalid',
					sprintf(
						/* translators: %s: Theme directory name. */
						__( 'The "%s" theme is not a valid parent theme.' ),
						esc_html( $_child->template )
					)
				);
				$_child->cache_add(
					'theme',
					array(
						'block_template_folders' => $_child->get_block_template_folders(),
						'block_theme'            => $_child->is_block_theme(),
						'headers'                => $_child->headers,
						'errors'                 => $_child->errors,
						'stylesheet'             => $_child->stylesheet,
						'template'               => $_child->template,
					)
				);
				// Hai giao diện thực sự tham chiếu lẫn nhau qua header Template.
				if ( $_child->stylesheet === $this->template ) {
					$this->errors = new WP_Error(
						'theme_parent_invalid',
						sprintf(
							/* translators: %s: Theme directory name. */
							__( 'The "%s" theme is not a valid parent theme.' ),
							esc_html( $this->template )
						)
					);
					$this->cache_add(
						'theme',
						array(
							'block_template_folders' => $this->get_block_template_folders(),
							'block_theme'            => $this->is_block_theme(),
							'headers'                => $this->headers,
							'errors'                 => $this->errors,
							'stylesheet'             => $this->stylesheet,
							'template'               => $this->template,
						)
					);
				}
				return;
			}
			// Thiết lập giao diện cha. Truyền instance hiện tại để thực hiện kiểm tra ở trên và đánh giá lỗi.
			$this->parent = new WP_Theme( $this->template, isset( $theme_root_template ) ? $theme_root_template : $this->theme_root, $this );
		}

		if ( wp_paused_themes()->get( $this->stylesheet ) && ( ! is_wp_error( $this->errors ) || ! isset( $this->errors->errors['theme_paused'] ) ) ) {
			$this->errors = new WP_Error( 'theme_paused', __( 'This theme failed to load properly and was paused within the admin backend.' ) );
		}

		// Hoàn tất. Nếu không lấy từ cache, lưu vào cache.
		if ( ! is_array( $cache ) ) {
			$cache = array(
				'block_theme'            => $this->is_block_theme(),
				'block_template_folders' => $this->get_block_template_folders(),
				'headers'                => $this->headers,
				'errors'                 => $this->errors,
				'stylesheet'             => $this->stylesheet,
				'template'               => $this->template,
			);
			// Nếu giao diện cha ở thư mục gốc khác, lưu cache. Tránh toàn bộ nhánh gọi filesystem ở trên.
			if ( isset( $theme_root_template ) ) {
				$cache['theme_root_template'] = $theme_root_template;
			}
			$this->cache_add( 'theme', $cache );
		}
	}

	/**
	 * Khi chuyển đổi đối tượng thành chuỗi, tên giao diện được trả về.
	 *
	 * @since 3.4.0
	 *
	 * @return string Tên giao diện, sẵn sàng hiển thị (đã dịch).
	 */
	public function __toString() {
		return (string) $this->display( 'Name' );
	}

	/**
	 * Phương thức magic __isset() cho các thuộc tính trước đây được trả về bởi current_theme_info().
	 *
	 * @since 3.4.0
	 *
	 * @param string $offset Thuộc tính cần kiểm tra.
	 * @return bool Liệu thuộc tính đã cho có được thiết lập hay không.
	 */
	public function __isset( $offset ) {
		static $properties = array(
			'name',
			'title',
			'version',
			'parent_theme',
			'template_dir',
			'stylesheet_dir',
			'template',
			'stylesheet',
			'screenshot',
			'description',
			'author',
			'tags',
			'theme_root',
			'theme_root_uri',
		);

		return in_array( $offset, $properties, true );
	}

	/**
	 * Phương thức magic __get() cho các thuộc tính trước đây được trả về bởi current_theme_info().
	 *
	 * @since 3.4.0
	 *
	 * @param string $offset Thuộc tính cần lấy.
	 * @return mixed Giá trị thuộc tính.
	 */
	public function __get( $offset ) {
		switch ( $offset ) {
			case 'name':
			case 'title':
				return $this->get( 'Name' );
			case 'version':
				return $this->get( 'Version' );
			case 'parent_theme':
				return $this->parent() ? $this->parent()->get( 'Name' ) : '';
			case 'template_dir':
				return $this->get_template_directory();
			case 'stylesheet_dir':
				return $this->get_stylesheet_directory();
			case 'template':
				return $this->get_template();
			case 'stylesheet':
				return $this->get_stylesheet();
			case 'screenshot':
				return $this->get_screenshot( 'relative' );
			// 'author' và 'description' trước đây không trả về dữ liệu đã dịch.
			case 'description':
				return $this->display( 'Description' );
			case 'author':
				return $this->display( 'Author' );
			case 'tags':
				return $this->get( 'Tags' );
			case 'theme_root':
				return $this->get_theme_root();
			case 'theme_root_uri':
				return $this->get_theme_root_uri();
			// Cho trường hợp mảng được chuyển đổi thành đối tượng.
			default:
				return $this->offsetGet( $offset );
		}
	}

	/**
	 * Phương thức triển khai ArrayAccess cho các khóa trước đây được trả về bởi get_themes().
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	#[ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {}

	/**
	 * Phương thức triển khai ArrayAccess cho các khóa trước đây được trả về bởi get_themes().
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $offset
	 */
	#[ReturnTypeWillChange]
	public function offsetUnset( $offset ) {}

	/**
	 * Phương thức triển khai ArrayAccess cho các khóa trước đây được trả về bởi get_themes().
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	#[ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		static $keys = array(
			'Name',
			'Version',
			'Status',
			'Title',
			'Author',
			'Author Name',
			'Author URI',
			'Description',
			'Template',
			'Stylesheet',
			'Template Files',
			'Stylesheet Files',
			'Template Dir',
			'Stylesheet Dir',
			'Screenshot',
			'Tags',
			'Theme Root',
			'Theme Root URI',
			'Parent Theme',
		);

		return in_array( $offset, $keys, true );
	}

	/**
	 * Phương thức triển khai ArrayAccess cho các khóa trước đây được trả về bởi get_themes().
	 *
	 * Author, Author Name, Author URI, và Description trước đây không trả về dữ liệu
	 * đã dịch. Hiện tại chúng ta đang làm vậy vì nó an toàn. Tuy nhiên, vì Name và Title
	 * có thể đã được sử dụng làm khóa cho get_themes(), cả hai vẫn không được dịch để
	 * tương thích ngược. Điều này có nghĩa ['Name'] không lý tưởng, và nên cẩn thận sử dụng
	 * `$theme::display( 'Name' )` để lấy header đã được dịch đúng cách.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		switch ( $offset ) {
			case 'Name':
			case 'Title':
				/*
				 * Xem ghi chú ở trên về việc sử dụng dữ liệu đã dịch. get() không lý tưởng.
				 * Nó chỉ dùng cho tương thích ngược. Hãy sử dụng display().
				 */
				return $this->get( 'Name' );
			case 'Author':
				return $this->display( 'Author' );
			case 'Author Name':
				return $this->display( 'Author', false );
			case 'Author URI':
				return $this->display( 'AuthorURI' );
			case 'Description':
				return $this->display( 'Description' );
			case 'Version':
			case 'Status':
				return $this->get( $offset );
			case 'Template':
				return $this->get_template();
			case 'Stylesheet':
				return $this->get_stylesheet();
			case 'Template Files':
				return $this->get_files( 'php', 1, true );
			case 'Stylesheet Files':
				return $this->get_files( 'css', 0, false );
			case 'Template Dir':
				return $this->get_template_directory();
			case 'Stylesheet Dir':
				return $this->get_stylesheet_directory();
			case 'Screenshot':
				return $this->get_screenshot( 'relative' );
			case 'Tags':
				return $this->get( 'Tags' );
			case 'Theme Root':
				return $this->get_theme_root();
			case 'Theme Root URI':
				return $this->get_theme_root_uri();
			case 'Parent Theme':
				return $this->parent() ? $this->parent()->get( 'Name' ) : '';
			default:
				return null;
		}
	}

	/**
	 * Trả về thuộc tính errors.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Error|false WP_Error nếu có lỗi, hoặc false.
	 */
	public function errors() {
		return is_wp_error( $this->errors ) ? $this->errors : false;
	}

	/**
	 * Xác định xem giao diện có tồn tại hay không.
	 *
	 * Giao diện có lỗi vẫn tồn tại. Giao diện có lỗi 'theme_not_found',
	 * nghĩa là thư mục giao diện không được tìm thấy, thì không tồn tại.
	 *
	 * @since 3.4.0
	 *
	 * @return bool Liệu giao diện có tồn tại hay không.
	 */
	public function exists() {
		return ! ( $this->errors() && in_array( 'theme_not_found', $this->errors()->get_error_codes(), true ) );
	}

	/**
	 * Trả về tham chiếu đến giao diện cha.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Theme|false Giao diện cha, hoặc false nếu giao diện đang kích hoạt không phải giao diện con.
	 */
	public function parent() {
		return isset( $this->parent ) ? $this->parent : false;
	}

	/**
	 * Thực hiện các tác vụ khởi tạo lại.
	 *
	 * Ngăn chặn callback bị chèn vào trong quá trình giải tuần tự hóa đối tượng.
	 */
	public function __wakeup() {
		if ( $this->parent && ! $this->parent instanceof self ) {
			throw new UnexpectedValueException();
		}
		if ( $this->headers && ! is_array( $this->headers ) ) {
			throw new UnexpectedValueException();
		}
		foreach ( $this->headers as $value ) {
			if ( ! is_string( $value ) ) {
				throw new UnexpectedValueException();
			}
		}
		$this->headers_sanitized = array();
	}

	/**
	 * Thêm dữ liệu giao diện vào cache.
	 *
	 * Các mục cache được đánh khóa theo giao diện và loại dữ liệu.
	 *
	 * @since 3.4.0
	 *
	 * @param string       $key  Loại dữ liệu cần lưu (theme, screenshot, headers, post_templates).
	 * @param array|string $data Dữ liệu cần lưu.
	 * @return bool Giá trị trả về từ wp_cache_add().
	 */
	private function cache_add( $key, $data ) {
		return wp_cache_add( $key . '-' . $this->cache_hash, $data, 'themes', self::$cache_expiration );
	}

	/**
	 * Lấy dữ liệu giao diện từ cache.
	 *
	 * Các mục cache được đánh khóa theo giao diện và loại dữ liệu.
	 *
	 * @since 3.4.0
	 *
	 * @param string $key Loại dữ liệu cần lấy (theme, screenshot, headers, post_templates).
	 * @return mixed Dữ liệu đã lấy.
	 */
	private function cache_get( $key ) {
		return wp_cache_get( $key . '-' . $this->cache_hash, 'themes' );
	}

	/**
	 * Xóa cache cho giao diện.
	 *
	 * @since 3.4.0
	 */
	public function cache_delete() {
		foreach ( array( 'theme', 'screenshot', 'headers', 'post_templates' ) as $key ) {
			wp_cache_delete( $key . '-' . $this->cache_hash, 'themes' );
		}
		$this->template               = null;
		$this->textdomain_loaded      = null;
		$this->theme_root_uri         = null;
		$this->parent                 = null;
		$this->errors                 = null;
		$this->headers_sanitized      = null;
		$this->name_translated        = null;
		$this->block_theme            = null;
		$this->block_template_folders = null;
		$this->headers                = array();
		$this->__construct( $this->stylesheet, $this->theme_root );
		$this->delete_pattern_cache();
	}

	/**
	 * Lấy header giao diện thô, chưa định dạng.
	 *
	 * Header đã được làm sạch, nhưng chưa được dịch và chưa được đánh dấu để hiển thị.
	 * Để lấy header giao diện cho hiển thị, sử dụng phương thức display().
	 *
	 * Sử dụng phương thức get_template(), không phải header 'Template', để tìm template.
	 * Header 'Template' chỉ phản ánh những gì được viết trong style.css, trong khi
	 * get_template() tính đến vị trí WordPress thực sự tìm thấy giao diện và
	 * liệu nó có thực sự hợp lệ hay không.
	 *
	 * @since 3.4.0
	 *
	 * @param string $header Header giao diện. Name, Description, Author, Version, ThemeURI, AuthorURI, Status, Tags.
	 * @return string|array|false Chuỗi hoặc mảng (cho header Tags) khi thành công, false khi thất bại.
	 */
	public function get( $header ) {
		if ( ! isset( $this->headers[ $header ] ) ) {
			return false;
		}

		if ( ! isset( $this->headers_sanitized ) ) {
			$this->headers_sanitized = $this->cache_get( 'headers' );
			if ( ! is_array( $this->headers_sanitized ) ) {
				$this->headers_sanitized = array();
			}
		}

		if ( isset( $this->headers_sanitized[ $header ] ) ) {
			return $this->headers_sanitized[ $header ];
		}

		// Nếu giao diện là nhóm cache vĩnh viễn, làm sạch tất cả và lưu cache. Một lần cache_add tốt hơn nhiều lần cache_set.
		if ( self::$persistently_cache ) {
			foreach ( array_keys( $this->headers ) as $_header ) {
				$this->headers_sanitized[ $_header ] = $this->sanitize_header( $_header, $this->headers[ $_header ] );
			}
			$this->cache_add( 'headers', $this->headers_sanitized );
		} else {
			$this->headers_sanitized[ $header ] = $this->sanitize_header( $header, $this->headers[ $header ] );
		}

		return $this->headers_sanitized[ $header ];
	}

	/**
	 * Lấy header giao diện đã được định dạng và dịch để hiển thị.
	 *
	 * @since 3.4.0
	 *
	 * @param string $header    Header giao diện. Name, Description, Author, Version, ThemeURI, AuthorURI, Status, Tags.
	 * @param bool   $markup    Tùy chọn. Có đánh dấu header hay không. Mặc định true.
	 * @param bool   $translate Tùy chọn. Có dịch header hay không. Mặc định true.
	 * @return string|array|false Header đã xử lý. Mảng cho Tags nếu `$markup` là false, chuỗi cho các trường hợp khác.
	 *                            False khi thất bại.
	 */
	public function display( $header, $markup = true, $translate = true ) {
		$value = $this->get( $header );
		if ( false === $value ) {
			return false;
		}

		if ( $translate && ( empty( $value ) || ! $this->load_textdomain() ) ) {
			$translate = false;
		}

		if ( $translate ) {
			$value = $this->translate_header( $header, $value );
		}

		if ( $markup ) {
			$value = $this->markup_header( $header, $value, $translate );
		}

		return $value;
	}

	/**
	 * Làm sạch header giao diện.
	 *
	 * @since 3.4.0
	 * @since 5.4.0 Thêm hỗ trợ cho header `Requires at least` và `Requires PHP`.
	 * @since 6.1.0 Thêm hỗ trợ cho header `Update URI`.
	 *
	 * @param string $header Header giao diện. Chấp nhận 'Name', 'Description', 'Author', 'Version',
	 *                       'ThemeURI', 'AuthorURI', 'Status', 'Tags', 'RequiresWP', 'RequiresPHP',
	 *                       'UpdateURI'.
	 * @param string $value  Giá trị cần làm sạch.
	 * @return string|array Mảng cho header Tags, chuỗi cho các trường hợp khác.
	 */
	private function sanitize_header( $header, $value ) {
		switch ( $header ) {
			case 'Status':
				if ( ! $value ) {
					$value = 'publish';
					break;
				}
				// Tiếp tục qua các trường hợp khác.
			case 'Name':
				static $header_tags = array(
					'abbr'    => array( 'title' => true ),
					'acronym' => array( 'title' => true ),
					'code'    => true,
					'em'      => true,
					'strong'  => true,
				);

				$value = wp_kses( $value, $header_tags );
				break;
			case 'Author':
				// Không nên có thẻ anchor trong Author, nhưng một số giao diện thích thử thách.
			case 'Description':
				static $header_tags_with_a = array(
					'a'       => array(
						'href'  => true,
						'title' => true,
					),
					'abbr'    => array( 'title' => true ),
					'acronym' => array( 'title' => true ),
					'code'    => true,
					'em'      => true,
					'strong'  => true,
				);

				$value = wp_kses( $value, $header_tags_with_a );
				break;
			case 'ThemeURI':
			case 'AuthorURI':
				$value = sanitize_url( $value );
				break;
			case 'Tags':
				$value = array_filter( array_map( 'trim', explode( ',', strip_tags( $value ) ) ) );
				break;
			case 'Version':
			case 'RequiresWP':
			case 'RequiresPHP':
			case 'UpdateURI':
				$value = strip_tags( $value );
				break;
		}

		return $value;
	}

	/**
	 * Đánh dấu header giao diện.
	 *
	 * @since 3.4.0
	 *
	 * @param string       $header    Header giao diện. Name, Description, Author, Version, ThemeURI, AuthorURI, Status, Tags.
	 * @param string|array $value     Giá trị cần đánh dấu. Mảng cho header Tags, chuỗi cho các trường hợp khác.
	 * @param string       $translate Liệu header đã được dịch chưa.
	 * @return string Giá trị đã đánh dấu.
	 */
	private function markup_header( $header, $value, $translate ) {
		switch ( $header ) {
			case 'Name':
				if ( empty( $value ) ) {
					$value = esc_html( $this->get_stylesheet() );
				}
				break;
			case 'Description':
				$value = wptexturize( $value );
				break;
			case 'Author':
				if ( $this->get( 'AuthorURI' ) ) {
					$value = sprintf( '<a href="%1$s">%2$s</a>', $this->display( 'AuthorURI', true, $translate ), $value );
				} elseif ( ! $value ) {
					$value = __( 'Anonymous' );
				}
				break;
			case 'Tags':
				static $comma = null;
				if ( ! isset( $comma ) ) {
					$comma = wp_get_list_item_separator();
				}
				$value = implode( $comma, $value );
				break;
			case 'ThemeURI':
			case 'AuthorURI':
				$value = esc_url( $value );
				break;
		}

		return $value;
	}

	/**
	 * Dịch header giao diện.
	 *
	 * @since 3.4.0
	 *
	 * @param string       $header Header giao diện. Name, Description, Author, Version, ThemeURI, AuthorURI, Status, Tags.
	 * @param string|array $value  Giá trị cần dịch. Mảng cho header Tags, chuỗi cho các trường hợp khác.
	 * @return string|array Giá trị đã dịch. Mảng cho header Tags, chuỗi cho các trường hợp khác.
	 */
	private function translate_header( $header, $value ) {
		switch ( $header ) {
			case 'Name':
				// Được lưu cache cho mục đích sắp xếp.
				if ( isset( $this->name_translated ) ) {
					return $this->name_translated;
				}

				// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
				$this->name_translated = translate( $value, $this->get( 'TextDomain' ) );

				return $this->name_translated;
			case 'Tags':
				if ( empty( $value ) || ! function_exists( 'get_theme_feature_list' ) ) {
					return $value;
				}

				static $tags_list;
				if ( ! isset( $tags_list ) ) {
					$tags_list = array(
						// Kể từ 4.6, các thẻ không còn dùng, chỉ cung cấp bản dịch cho giao diện cũ.
						'black'             => __( 'Black' ),
						'blue'              => __( 'Blue' ),
						'brown'             => __( 'Brown' ),
						'gray'              => __( 'Gray' ),
						'green'             => __( 'Green' ),
						'orange'            => __( 'Orange' ),
						'pink'              => __( 'Pink' ),
						'purple'            => __( 'Purple' ),
						'red'               => __( 'Red' ),
						'silver'            => __( 'Silver' ),
						'tan'               => __( 'Tan' ),
						'white'             => __( 'White' ),
						'yellow'            => __( 'Yellow' ),
						'dark'              => _x( 'Dark', 'color scheme' ),
						'light'             => _x( 'Light', 'color scheme' ),
						'fixed-layout'      => __( 'Fixed Layout' ),
						'fluid-layout'      => __( 'Fluid Layout' ),
						'responsive-layout' => __( 'Responsive Layout' ),
						'blavatar'          => __( 'Blavatar' ),
						'photoblogging'     => __( 'Photoblogging' ),
						'seasonal'          => __( 'Seasonal' ),
					);

					$feature_list = get_theme_feature_list( false ); // Không dùng API.

					foreach ( $feature_list as $tags ) {
						$tags_list += $tags;
					}
				}

				foreach ( $value as &$tag ) {
					if ( isset( $tags_list[ $tag ] ) ) {
						$tag = $tags_list[ $tag ];
					} elseif ( isset( self::$tag_map[ $tag ] ) ) {
						$tag = $tags_list[ self::$tag_map[ $tag ] ];
					}
				}

				return $value;

			default:
				// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
				$value = translate( $value, $this->get( 'TextDomain' ) );
		}
		return $value;
	}

	/**
	 * Trả về tên thư mục chứa các tệp "stylesheet" của giao diện, bên trong thư mục gốc giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là tên thư mục của giao diện con.
	 * Nếu không, get_stylesheet() giống với get_template().
	 *
	 * @since 3.4.0
	 *
	 * @return string Stylesheet.
	 */
	public function get_stylesheet() {
		return $this->stylesheet;
	}

	/**
	 * Trả về tên thư mục chứa các tệp "template" của giao diện, bên trong thư mục gốc giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là tên thư mục của giao diện cha.
	 * Nếu không, get_template() giống với get_stylesheet().
	 *
	 * @since 3.4.0
	 *
	 * @return string Template.
	 */
	public function get_template() {
		return $this->template;
	}

	/**
	 * Trả về đường dẫn tuyệt đối đến thư mục chứa các tệp "stylesheet" của giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là đường dẫn tuyệt đối đến thư mục
	 * chứa các tệp của giao diện con.
	 *
	 * @since 3.4.0
	 *
	 * @return string Đường dẫn tuyệt đối của thư mục stylesheet.
	 */
	public function get_stylesheet_directory() {
		if ( $this->errors() && in_array( 'theme_root_missing', $this->errors()->get_error_codes(), true ) ) {
			return '';
		}

		return $this->theme_root . '/' . $this->stylesheet;
	}

	/**
	 * Trả về đường dẫn tuyệt đối đến thư mục chứa các tệp "template" của giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là đường dẫn tuyệt đối đến thư mục
	 * chứa các tệp của giao diện cha.
	 *
	 * @since 3.4.0
	 *
	 * @return string Đường dẫn tuyệt đối của thư mục template.
	 */
	public function get_template_directory() {
		if ( $this->parent() ) {
			$theme_root = $this->parent()->theme_root;
		} else {
			$theme_root = $this->theme_root;
		}

		return $theme_root . '/' . $this->template;
	}

	/**
	 * Trả về URL đến thư mục chứa các tệp "stylesheet" của giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là URL đến thư mục
	 * chứa các tệp của giao diện con.
	 *
	 * @since 3.4.0
	 *
	 * @return string URL đến thư mục stylesheet.
	 */
	public function get_stylesheet_directory_uri() {
		return $this->get_theme_root_uri() . '/' . str_replace( '%2F', '/', rawurlencode( $this->stylesheet ) );
	}

	/**
	 * Trả về URL đến thư mục chứa các tệp "template" của giao diện.
	 *
	 * Trong trường hợp giao diện con, đây là URL đến thư mục
	 * chứa các tệp của giao diện cha.
	 *
	 * @since 3.4.0
	 *
	 * @return string URL đến thư mục template.
	 */
	public function get_template_directory_uri() {
		if ( $this->parent() ) {
			$theme_root_uri = $this->parent()->get_theme_root_uri();
		} else {
			$theme_root_uri = $this->get_theme_root_uri();
		}

		return $theme_root_uri . '/' . str_replace( '%2F', '/', rawurlencode( $this->template ) );
	}

	/**
	 * Trả về đường dẫn tuyệt đối đến thư mục gốc giao diện.
	 *
	 * Đây thường là đường dẫn tuyệt đối tới wp-content/themes.
	 *
	 * @since 3.4.0
	 *
	 * @return string Thư mục gốc giao diện.
	 */
	public function get_theme_root() {
		return $this->theme_root;
	}

	/**
	 * Trả về URL đến thư mục gốc giao diện.
	 *
	 * Đây thường là URL tuyệt đối tới wp-content/themes. Đây là cơ sở
	 * cho tất cả các URL khác được trả về bởi WP_Theme, nên chúng ta truyền nó cho hàm
	 * get_theme_root_uri() công khai và cho phép nó chạy bộ lọc {@see 'theme_root_uri'}.
	 *
	 * @since 3.4.0
	 *
	 * @return string URI thư mục gốc giao diện.
	 */
	public function get_theme_root_uri() {
		if ( ! isset( $this->theme_root_uri ) ) {
			$this->theme_root_uri = get_theme_root_uri( $this->stylesheet, $this->theme_root );
		}
		return $this->theme_root_uri;
	}

	/**
	 * Trả về tệp ảnh chụp màn hình chính của giao diện.
	 *
	 * Ảnh chụp màn hình chính có tên screenshot.png. Các đuôi gif và jpg cũng được chấp nhận.
	 *
	 * Ảnh chụp màn hình phải nằm trong thư mục stylesheet. (Trong trường hợp giao diện con,
	 * ảnh chụp màn hình của giao diện cha không được kế thừa.)
	 *
	 * @since 3.4.0
	 *
	 * @param string $uri Loại URL trả về, 'relative' hoặc URI tuyệt đối. Mặc định URI tuyệt đối.
	 * @return string|false Tệp ảnh chụp màn hình. False nếu giao diện không có ảnh chụp.
	 */
	public function get_screenshot( $uri = 'uri' ) {
		$screenshot = $this->cache_get( 'screenshot' );
		if ( $screenshot ) {
			if ( 'relative' === $uri ) {
				return $screenshot;
			}
			return $this->get_stylesheet_directory_uri() . '/' . $screenshot;
		} elseif ( 0 === $screenshot ) {
			return false;
		}

		foreach ( array( 'png', 'gif', 'jpg', 'jpeg', 'webp', 'avif' ) as $ext ) {
			if ( file_exists( $this->get_stylesheet_directory() . "/screenshot.$ext" ) ) {
				$this->cache_add( 'screenshot', 'screenshot.' . $ext );
				if ( 'relative' === $uri ) {
					return 'screenshot.' . $ext;
				}
				return $this->get_stylesheet_directory_uri() . '/' . 'screenshot.' . $ext;
			}
		}

		$this->cache_add( 'screenshot', 0 );
		return false;
	}

	/**
	 * Trả về các tệp trong thư mục giao diện.
	 *
	 * @since 3.4.0
	 *
	 * @param string[]|string $type          Tùy chọn. Mảng các đuôi tệp cần tìm, chuỗi đuôi đơn,
	 *                                       hoặc null cho tất cả. Mặc định null.
	 * @param int             $depth         Tùy chọn. Tìm kiếm sâu bao nhiêu cấp. Mặc định quét phẳng (0).
	 *                                       -1 là vô hạn.
	 * @param bool            $search_parent Tùy chọn. Có trả về tệp giao diện cha hay không. Mặc định false.
	 * @return string[] Mảng các tệp, đánh khóa theo đường dẫn tương đối so với thư mục giao diện,
	 *                  với giá trị là đường dẫn tuyệt đối.
	 */
	public function get_files( $type = null, $depth = 0, $search_parent = false ) {
		$files = (array) self::scandir( $this->get_stylesheet_directory(), $type, $depth );

		if ( $search_parent && $this->parent() ) {
			$files += (array) self::scandir( $this->get_template_directory(), $type, $depth );
		}

		return array_filter( $files );
	}

	/**
	 * Trả về các template bài viết của giao diện.
	 *
	 * @since 4.7.0
	 * @since 5.8.0 Bao gồm các block template.
	 *
	 * @return array[] Mảng các mảng template trang, đánh khóa theo loại bài viết và tên tệp,
	 *                 với giá trị là tên header đã dịch.
	 */
	public function get_post_templates() {
		// Nếu bạn làm hỏng giao diện đang kích hoạt và chúng tôi vô hiệu hóa cha, hầu hết mọi thứ vẫn hoạt động. Hãy bỏ qua.
		if ( $this->errors() && $this->errors()->get_error_codes() !== array( 'theme_parent_invalid' ) ) {
			return array();
		}

		$post_templates = $this->cache_get( 'post_templates' );

		if ( ! is_array( $post_templates ) ) {
			$post_templates = array();

			$files = (array) $this->get_files( 'php', 1, true );

			foreach ( $files as $file => $full_path ) {
				if ( ! preg_match( '|Template Name:(.*)$|mi', file_get_contents( $full_path ), $header ) ) {
					continue;
				}

				$types = array( 'page' );
				if ( preg_match( '|Template Post Type:(.*)$|mi', file_get_contents( $full_path ), $type ) ) {
					$types = explode( ',', _cleanup_header_comment( $type[1] ) );
				}

				foreach ( $types as $type ) {
					$type = sanitize_key( $type );
					if ( ! isset( $post_templates[ $type ] ) ) {
						$post_templates[ $type ] = array();
					}

					$post_templates[ $type ][ $file ] = _cleanup_header_comment( $header[1] );
				}
			}

			$this->cache_add( 'post_templates', $post_templates );
		}

		if ( current_theme_supports( 'block-templates' ) ) {
			$block_templates = get_block_templates( array(), 'wp_template' );
			foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
				foreach ( $block_templates as $block_template ) {
					if ( ! $block_template->is_custom ) {
						continue;
					}

					if ( isset( $block_template->post_types ) && ! in_array( $type, $block_template->post_types, true ) ) {
						continue;
					}

					$post_templates[ $type ][ $block_template->slug ] = $block_template->title;
				}
			}
		}

		if ( $this->load_textdomain() ) {
			foreach ( $post_templates as &$post_type ) {
				foreach ( $post_type as &$post_template ) {
					$post_template = $this->translate_header( 'Template Name', $post_template );
				}
			}
		}

		return $post_templates;
	}

	/**
	 * Trả về các template bài viết của giao diện cho một loại bài viết cụ thể.
	 *
	 * @since 3.4.0
	 * @since 4.7.0 Thêm tham số `$post_type`.
	 *
	 * @param WP_Post|null $post      Tùy chọn. Bài viết đang chỉnh sửa, cung cấp cho ngữ cảnh.
	 * @param string       $post_type Tùy chọn. Loại bài viết cần lấy template. Mặc định 'page'.
	 *                                Nếu bài viết được cung cấp, sẽ sử dụng loại bài viết của nó.
	 * @return string[] Mảng tên header template đánh khóa bởi tên tệp template.
	 */
	public function get_page_templates( $post = null, $post_type = 'page' ) {
		if ( $post ) {
			$post_type = get_post_type( $post );
		}

		$post_templates = $this->get_post_templates();
		$post_templates = isset( $post_templates[ $post_type ] ) ? $post_templates[ $post_type ] : array();

		/**
		 * Lọc danh sách template trang cho một giao diện.
		 *
		 * @since 4.9.6
		 *
		 * @param string[]     $post_templates Mảng tên header template đánh khóa bởi tên tệp template.
		 * @param WP_Theme     $theme          Đối tượng giao diện.
		 * @param WP_Post|null $post           Bài viết đang chỉnh sửa, cung cấp cho ngữ cảnh, hoặc null.
		 * @param string       $post_type      Loại bài viết cần lấy template.
		 */
		$post_templates = (array) apply_filters( 'theme_templates', $post_templates, $this, $post, $post_type );

		/**
		 * Lọc danh sách template trang cho một giao diện.
		 *
		 * Phần động của tên hook, `$post_type`, tham chiếu đến loại bài viết.
		 *
		 * Các tên hook có thể bao gồm:
		 *
		 *  - `theme_post_templates`
		 *  - `theme_page_templates`
		 *  - `theme_attachment_templates`
		 *
		 * @since 3.9.0
		 * @since 4.4.0 Chuyển đổi để cho phép kiểm soát hoàn toàn mảng `$page_templates`.
		 * @since 4.7.0 Thêm tham số `$post_type`.
		 *
		 * @param string[]     $post_templates Mảng tên header template đánh khóa bởi tên tệp template.
		 * @param WP_Theme     $theme          Đối tượng giao diện.
		 * @param WP_Post|null $post           Bài viết đang chỉnh sửa, cung cấp cho ngữ cảnh, hoặc null.
		 * @param string       $post_type      Loại bài viết cần lấy template.
		 */
		$post_templates = (array) apply_filters( "theme_{$post_type}_templates", $post_templates, $this, $post, $post_type );

		return $post_templates;
	}

	/**
	 * Quét thư mục để tìm các tệp có đuôi mở rộng nhất định.
	 *
	 * @since 3.4.0
	 *
	 * @param string            $path          Đường dẫn tuyệt đối cần tìm kiếm.
	 * @param array|string|null $extensions    Tùy chọn. Mảng các đuôi tệp cần tìm, chuỗi đuôi đơn,
	 *                                         hoặc null cho tất cả. Mặc định null.
	 * @param int               $depth         Tùy chọn. Tìm kiếm sâu bao nhiêu cấp. Chấp nhận 0, 1+, hoặc
	 *                                         -1 (vô hạn). Mặc định 0.
	 * @param string            $relative_path Tùy chọn. Tên cơ sở của đường dẫn tuyệt đối. Dùng để kiểm soát
	 *                                         đường dẫn trả về cho các tệp tìm thấy, đặc biệt khi hàm này
	 *                                         đệ quy vào cấp sâu hơn. Mặc định rỗng.
	 * @return string[]|false Mảng các tệp, đánh khóa theo đường dẫn tương đối so với thư mục `$path` với
	 *                        tiền tố `$relative_path`, giá trị là đường dẫn tuyệt đối. False nếu không thành công.
	 */
	private static function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
		if ( ! is_dir( $path ) ) {
			return false;
		}

		if ( $extensions ) {
			$extensions  = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		}

		$relative_path = trailingslashit( $relative_path );
		if ( '/' === $relative_path ) {
			$relative_path = '';
		}

		$results = scandir( $path );
		$files   = array();

		/**
		 * Lọc mảng các thư mục và tệp bị loại trừ khi quét thư mục giao diện.
		 *
		 * @since 4.7.4
		 *
		 * @param string[] $exclusions Mảng các thư mục và tệp bị loại trừ.
		 */
		$exclusions = (array) apply_filters( 'theme_scandir_exclusions', array( 'CVS', 'node_modules', 'vendor', 'bower_components' ) );

		foreach ( $results as $result ) {
			if ( '.' === $result[0] || in_array( $result, $exclusions, true ) ) {
				continue;
			}
			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth ) {
					continue;
				}
				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1, $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		}

		return $files;
	}

	/**
	 * Tải textdomain của giao diện.
	 *
	 * Các tệp dịch không được kế thừa từ giao diện cha. TODO: Nếu thất bại cho
	 * giao diện con, có lẽ nên thử tải bản dịch của giao diện cha.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True nếu textdomain được tải thành công hoặc đã được tải.
	 *  False nếu không có textdomain nào được chỉ định trong header, hoặc nếu domain không thể tải.
	 */
	public function load_textdomain() {
		if ( isset( $this->textdomain_loaded ) ) {
			return $this->textdomain_loaded;
		}

		$textdomain = $this->get( 'TextDomain' );
		if ( ! $textdomain ) {
			$this->textdomain_loaded = false;
			return false;
		}

		if ( is_textdomain_loaded( $textdomain ) ) {
			$this->textdomain_loaded = true;
			return true;
		}

		$path       = $this->get_stylesheet_directory();
		$domainpath = $this->get( 'DomainPath' );
		if ( $domainpath ) {
			$path .= $domainpath;
		} else {
			$path .= '/languages';
		}

		$this->textdomain_loaded = load_theme_textdomain( $textdomain, $path );
		return $this->textdomain_loaded;
	}

	/**
	 * Xác định xem giao diện có được phép hay không (chỉ multisite).
	 *
	 * @since 3.4.0
	 *
	 * @param string $check   Tùy chọn. Kiểm tra chỉ cài đặt 'network', cài đặt 'site',
	 *                        hoặc 'both'. Mặc định 'both'.
	 * @param int    $blog_id Tùy chọn. Bỏ qua nếu chỉ kiểm tra cài đặt toàn mạng. Mặc định site hiện tại.
	 * @return bool Liệu giao diện có được phép cho mạng hay không. Trả về true trong single-site.
	 */
	public function is_allowed( $check = 'both', $blog_id = null ) {
		if ( ! is_multisite() ) {
			return true;
		}

		if ( 'both' === $check || 'network' === $check ) {
			$allowed = self::get_allowed_on_network();
			if ( ! empty( $allowed[ $this->get_stylesheet() ] ) ) {
				return true;
			}
		}

		if ( 'both' === $check || 'site' === $check ) {
			$allowed = self::get_allowed_on_site( $blog_id );
			if ( ! empty( $allowed[ $this->get_stylesheet() ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Trả về liệu giao diện này có phải là giao diện dựa trên block hay không.
	 *
	 * @since 5.9.0
	 *
	 * @return bool
	 */
	public function is_block_theme() {
		if ( isset( $this->block_theme ) ) {
			return $this->block_theme;
		}

		$paths_to_index_block_template = array(
			$this->get_file_path( '/templates/index.html' ),
			$this->get_file_path( '/block-templates/index.html' ),
		);

		$this->block_theme = false;

		foreach ( $paths_to_index_block_template as $path_to_index_block_template ) {
			if ( is_file( $path_to_index_block_template ) && is_readable( $path_to_index_block_template ) ) {
				$this->block_theme = true;
				break;
			}
		}

		return $this->block_theme;
	}

	/**
	 * Lấy đường dẫn của một tệp trong giao diện.
	 *
	 * Tìm kiếm trong thư mục stylesheet trước thư mục template để các giao diện
	 * kế thừa từ giao diện cha chỉ cần ghi đè một tệp.
	 *
	 * @since 5.9.0
	 *
	 * @param string $file Tùy chọn. Tệp cần tìm trong thư mục stylesheet.
	 * @return string Đường dẫn của tệp.
	 */
	public function get_file_path( $file = '' ) {
		$file = ltrim( $file, '/' );

		$stylesheet_directory = $this->get_stylesheet_directory();
		$template_directory   = $this->get_template_directory();

		if ( empty( $file ) ) {
			$path = $stylesheet_directory;
		} elseif ( $stylesheet_directory !== $template_directory && file_exists( $stylesheet_directory . '/' . $file ) ) {
			$path = $stylesheet_directory . '/' . $file;
		} else {
			$path = $template_directory . '/' . $file;
		}

		/** This filter is documented in wp-includes/link-template.php */
		return apply_filters( 'theme_file_path', $path, $file );
	}

	/**
	 * Xác định giao diện mặc định WordPress mới nhất đã được cài đặt.
	 *
	 * Phương thức này truy cập hệ thống tệp.
	 *
	 * @since 4.4.0
	 *
	 * @return WP_Theme|false Đối tượng, hoặc false nếu không có giao diện nào được cài đặt.
	 */
	public static function get_core_default_theme() {
		foreach ( array_reverse( self::$default_themes ) as $slug => $name ) {
			$theme = wp_get_theme( $slug );
			if ( $theme->exists() ) {
				return $theme;
			}
		}
		return false;
	}

	/**
	 * Trả về mảng tên stylesheet của các giao diện được phép trên site hoặc mạng.
	 *
	 * @since 3.4.0
	 *
	 * @param int $blog_id Tùy chọn. ID của site. Mặc định site hiện tại.
	 * @return string[] Mảng tên stylesheet.
	 */
	public static function get_allowed( $blog_id = null ) {
		/**
		 * Lọc mảng các giao diện được phép trên mạng.
		 *
		 * Site được cung cấp làm ngữ cảnh để danh sách giao diện được phép trên mạng
		 * có thể được lọc thêm.
		 *
		 * @since 4.5.0
		 *
		 * @param string[] $allowed_themes Mảng tên stylesheet giao diện.
		 * @param int      $blog_id        ID của site.
		 */
		$network = (array) apply_filters( 'network_allowed_themes', self::get_allowed_on_network(), $blog_id );
		return $network + self::get_allowed_on_site( $blog_id );
	}

	/**
	 * Trả về mảng tên stylesheet của các giao diện được phép trên mạng.
	 *
	 * @since 3.4.0
	 *
	 * @return string[] Mảng tên stylesheet.
	 */
	public static function get_allowed_on_network() {
		static $allowed_themes;
		if ( ! isset( $allowed_themes ) ) {
			$allowed_themes = (array) get_site_option( 'allowedthemes' );
		}

		/**
		 * Lọc mảng các giao diện được phép trên mạng.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string[] $allowed_themes Mảng tên stylesheet giao diện.
		 */
		$allowed_themes = apply_filters( 'allowed_themes', $allowed_themes );

		return $allowed_themes;
	}

	/**
	 * Trả về mảng tên stylesheet của các giao diện được phép trên site.
	 *
	 * @since 3.4.0
	 *
	 * @param int $blog_id Tùy chọn. ID của site. Mặc định site hiện tại.
	 * @return string[] Mảng tên stylesheet.
	 */
	public static function get_allowed_on_site( $blog_id = null ) {
		static $allowed_themes = array();

		if ( ! $blog_id || ! is_multisite() ) {
			$blog_id = get_current_blog_id();
		}

		if ( isset( $allowed_themes[ $blog_id ] ) ) {
			/**
			 * Lọc mảng các giao diện được phép trên site.
			 *
			 * @since 4.5.0
			 *
			 * @param string[] $allowed_themes Mảng tên stylesheet giao diện.
			 * @param int      $blog_id        ID của site. Mặc định site hiện tại.
			 */
			return (array) apply_filters( 'site_allowed_themes', $allowed_themes[ $blog_id ], $blog_id );
		}

		$current = get_current_blog_id() === $blog_id;

		if ( $current ) {
			$allowed_themes[ $blog_id ] = get_option( 'allowedthemes' );
		} else {
			switch_to_blog( $blog_id );
			$allowed_themes[ $blog_id ] = get_option( 'allowedthemes' );
			restore_current_blog();
		}

		/*
		 * Đây là tương thích ngược MU rất cũ.
		 * 'allowedthemes' đánh khóa theo stylesheet. 'allowed_themes' đánh khóa theo tên.
		 */
		if ( false === $allowed_themes[ $blog_id ] ) {
			if ( $current ) {
				$allowed_themes[ $blog_id ] = get_option( 'allowed_themes' );
			} else {
				switch_to_blog( $blog_id );
				$allowed_themes[ $blog_id ] = get_option( 'allowed_themes' );
				restore_current_blog();
			}

			if ( ! is_array( $allowed_themes[ $blog_id ] ) || empty( $allowed_themes[ $blog_id ] ) ) {
				$allowed_themes[ $blog_id ] = array();
			} else {
				$converted = array();
				$themes    = wp_get_themes();
				foreach ( $themes as $stylesheet => $theme_data ) {
					if ( isset( $allowed_themes[ $blog_id ][ $theme_data->get( 'Name' ) ] ) ) {
						$converted[ $stylesheet ] = true;
					}
				}
				$allowed_themes[ $blog_id ] = $converted;
			}
			// Thiết lập option để không bao giờ phải trải qua quá trình này nữa.
			if ( is_admin() && $allowed_themes[ $blog_id ] ) {
				if ( $current ) {
					update_option( 'allowedthemes', $allowed_themes[ $blog_id ], false );
					delete_option( 'allowed_themes' );
				} else {
					switch_to_blog( $blog_id );
					update_option( 'allowedthemes', $allowed_themes[ $blog_id ], false );
					delete_option( 'allowed_themes' );
					restore_current_blog();
				}
			}
		}

		/** This filter is documented in wp-includes/class-wp-theme.php */
		return (array) apply_filters( 'site_allowed_themes', $allowed_themes[ $blog_id ], $blog_id );
	}

	/**
	 * Trả về tên thư mục của các thư mục block template.
	 *
	 * @since 6.4.0
	 *
	 * @return string[] {
	 *     Tên thư mục được sử dụng bởi giao diện block.
	 *
	 *     @type string $wp_template      Tên thư mục tương đối với giao diện cho các block template.
	 *     @type string $wp_template_part Tên thư mục tương đối với giao diện cho các phần block template.
	 * }
	 */
	public function get_block_template_folders() {
		// Trả về giá trị đã thiết lập/cache nếu có.
		if ( isset( $this->block_template_folders ) ) {
			return $this->block_template_folders;
		}

		$this->block_template_folders = $this->default_template_folders;

		$stylesheet_directory = $this->get_stylesheet_directory();
		// Nếu giao diện sử dụng thư mục block template không còn dùng.
		if ( file_exists( $stylesheet_directory . '/block-templates' ) || file_exists( $stylesheet_directory . '/block-template-parts' ) ) {
			$this->block_template_folders = array(
				'wp_template'      => 'block-templates',
				'wp_template_part' => 'block-template-parts',
			);
		}
		return $this->block_template_folders;
	}

	/**
	 * Lấy dữ liệu block pattern cho một giao diện cụ thể.
	 * Mỗi pattern được định nghĩa như một tệp PHP và khai báo
	 * metadata bằng các header kiểu plugin. Định nghĩa tối thiểu bắt buộc là:
	 *
	 *     /**
	 *      * Title: My Pattern
	 *      * Slug: my-theme/my-pattern
	 *      *
	 *
	 * Đầu ra của mã nguồn PHP tương ứng với nội dung của pattern, ví dụ:
	 *
	 *     <main><p><?php echo "Hello"; ?></p></main>
	 *
	 * Nếu có thể, phương thức này sẽ thu thập từ cả giao diện cha và giao diện con.
	 *
	 * Các trường khác có thể thiết lập bao gồm:
	 *
	 *     - Description
	 *     - Viewport Width
	 *     - Inserter         (yes/no)
	 *     - Categories       (giá trị phân tách bằng dấu phẩy)
	 *     - Keywords         (giá trị phân tách bằng dấu phẩy)
	 *     - Block Types      (giá trị phân tách bằng dấu phẩy)
	 *     - Post Types       (giá trị phân tách bằng dấu phẩy)
	 *     - Template Types   (giá trị phân tách bằng dấu phẩy)
	 *
	 * @since 6.4.0
	 *
	 * @return array Dữ liệu block pattern.
	 */
	public function get_block_patterns() {
		$can_use_cached = ! wp_is_development_mode( 'theme' );

		$pattern_data = $this->get_pattern_cache();
		if ( is_array( $pattern_data ) ) {
			if ( $can_use_cached ) {
				return $pattern_data;
			}
			// Nếu đang ở chế độ phát triển, xóa cache pattern.
			$this->delete_pattern_cache();
		}

		$dirpath      = $this->get_stylesheet_directory() . '/patterns';
		$pattern_data = array();

		if ( ! file_exists( $dirpath ) ) {
			if ( $can_use_cached ) {
				$this->set_pattern_cache( $pattern_data );
			}
			return $pattern_data;
		}

		$files = (array) self::scandir( $dirpath, 'php', -1 );

		/**
		 * Lọc danh sách tệp block pattern cho một giao diện.
		 *
		 * @since 6.8.0
		 *
		 * @param array  $files   Mảng các tệp giao diện tìm thấy trong thư mục `patterns`.
		 * @param string $dirpath Đường dẫn thư mục `patterns` của giao diện đang được quét.
		 */
		$files = apply_filters( 'theme_block_pattern_files', $files, $dirpath );

		$dirpath = trailingslashit( $dirpath );

		if ( ! $files ) {
			if ( $can_use_cached ) {
				$this->set_pattern_cache( $pattern_data );
			}
			return $pattern_data;
		}

		$default_headers = array(
			'title'         => 'Title',
			'slug'          => 'Slug',
			'description'   => 'Description',
			'viewportWidth' => 'Viewport Width',
			'inserter'      => 'Inserter',
			'categories'    => 'Categories',
			'keywords'      => 'Keywords',
			'blockTypes'    => 'Block Types',
			'postTypes'     => 'Post Types',
			'templateTypes' => 'Template Types',
		);

		$properties_to_parse = array(
			'categories',
			'keywords',
			'blockTypes',
			'postTypes',
			'templateTypes',
		);

		foreach ( $files as $file ) {
			$pattern = get_file_data( $file, $default_headers );

			if ( empty( $pattern['slug'] ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: 1: file name. */
						__( 'Could not register file "%s" as a block pattern ("Slug" field missing)' ),
						$file
					),
					'6.0.0'
				);
				continue;
			}

			if ( ! preg_match( '/^[A-z0-9\/_-]+$/', $pattern['slug'] ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: 1: file name; 2: slug value found. */
						__( 'Could not register file "%1$s" as a block pattern (invalid slug "%2$s")' ),
						$file,
						$pattern['slug']
					),
					'6.0.0'
				);
			}

			// Title là thuộc tính bắt buộc.
			if ( ! $pattern['title'] ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: 1: file name. */
						__( 'Could not register file "%s" as a block pattern ("Title" field missing)' ),
						$file
					),
					'6.0.0'
				);
				continue;
			}

			// Với các thuộc tính kiểu mảng, phân tích dữ liệu phân tách bằng dấu phẩy.
			foreach ( $properties_to_parse as $property ) {
				if ( ! empty( $pattern[ $property ] ) ) {
					$pattern[ $property ] = array_filter( wp_parse_list( (string) $pattern[ $property ] ) );
				} else {
					unset( $pattern[ $property ] );
				}
			}

			// Phân tích các thuộc tính kiểu int.
			$property = 'viewportWidth';
			if ( ! empty( $pattern[ $property ] ) ) {
				$pattern[ $property ] = (int) $pattern[ $property ];
			} else {
				unset( $pattern[ $property ] );
			}

			// Phân tích các thuộc tính kiểu bool.
			$property = 'inserter';
			if ( ! empty( $pattern[ $property ] ) ) {
				$pattern[ $property ] = in_array(
					strtolower( $pattern[ $property ] ),
					array( 'yes', 'true' ),
					true
				);
			} else {
				unset( $pattern[ $property ] );
			}

			$key = str_replace( $dirpath, '', $file );

			$pattern_data[ $key ] = $pattern;
		}

		if ( $can_use_cached ) {
			$this->set_pattern_cache( $pattern_data );
		}

		return $pattern_data;
	}

	/**
	 * Lấy cache block pattern.
	 *
	 * @since 6.4.0
	 * @since 6.6.0 Sử dụng transient để cache bất kể môi trường site.
	 *
	 * @return array|false Trả về mảng các pattern nếu tìm thấy cache, ngược lại false.
	 */
	private function get_pattern_cache() {
		if ( ! $this->exists() ) {
			return false;
		}

		$pattern_data = get_site_transient( 'wp_theme_files_patterns-' . $this->cache_hash );

		if ( is_array( $pattern_data ) && $pattern_data['version'] === $this->get( 'Version' ) ) {
			return $pattern_data['patterns'];
		}
		return false;
	}

	/**
	 * Thiết lập cache block pattern.
	 *
	 * @since 6.4.0
	 * @since 6.6.0 Sử dụng transient để cache bất kể môi trường site.
	 *
	 * @param array $patterns Dữ liệu block pattern cần lưu vào cache.
	 */
	private function set_pattern_cache( array $patterns ) {
		$pattern_data = array(
			'version'  => $this->get( 'Version' ),
			'patterns' => $patterns,
		);

		/**
		 * Lọc thời gian hết hạn cache cho các tệp giao diện.
		 *
		 * @since 6.6.0
		 *
		 * @param int    $cache_expiration Thời gian hết hạn cache tính bằng giây.
		 * @param string $cache_type       Loại cache đang được thiết lập.
		 */
		$cache_expiration = (int) apply_filters( 'wp_theme_files_cache_ttl', self::$cache_expiration, 'theme_block_patterns' );

		// Chúng ta không muốn cache pattern vô thời hạn.
		if ( $cache_expiration <= 0 ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %1$s: The filter name.*/
					__( 'The %1$s filter must return an integer value greater than 0.' ),
					'<code>wp_theme_files_cache_ttl</code>'
				),
				'6.6.0'
			);

			$cache_expiration = self::$cache_expiration;
		}

		set_site_transient( 'wp_theme_files_patterns-' . $this->cache_hash, $pattern_data, $cache_expiration );
	}

	/**
	 * Xóa cache block pattern.
	 *
	 * @since 6.4.0
	 * @since 6.6.0 Sử dụng transient để cache bất kể môi trường site.
	 */
	public function delete_pattern_cache() {
		delete_site_transient( 'wp_theme_files_patterns-' . $this->cache_hash );
	}

	/**
	 * Kích hoạt giao diện cho tất cả các site trên mạng hiện tại.
	 *
	 * @since 4.6.0
	 *
	 * @param string|string[] $stylesheets Tên stylesheet hoặc mảng tên stylesheet.
	 */
	public static function network_enable_theme( $stylesheets ) {
		if ( ! is_multisite() ) {
			return;
		}

		if ( ! is_array( $stylesheets ) ) {
			$stylesheets = array( $stylesheets );
		}

		$allowed_themes = get_site_option( 'allowedthemes' );
		foreach ( $stylesheets as $stylesheet ) {
			$allowed_themes[ $stylesheet ] = true;
		}

		update_site_option( 'allowedthemes', $allowed_themes );
	}

	/**
	 * Vô hiệu hóa giao diện cho tất cả các site trên mạng hiện tại.
	 *
	 * @since 4.6.0
	 *
	 * @param string|string[] $stylesheets Tên stylesheet hoặc mảng tên stylesheet.
	 */
	public static function network_disable_theme( $stylesheets ) {
		if ( ! is_multisite() ) {
			return;
		}

		if ( ! is_array( $stylesheets ) ) {
			$stylesheets = array( $stylesheets );
		}

		$allowed_themes = get_site_option( 'allowedthemes' );
		foreach ( $stylesheets as $stylesheet ) {
			if ( isset( $allowed_themes[ $stylesheet ] ) ) {
				unset( $allowed_themes[ $stylesheet ] );
			}
		}

		update_site_option( 'allowedthemes', $allowed_themes );
	}

	/**
	 * Sắp xếp các giao diện theo tên.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Theme[] $themes Mảng các đối tượng giao diện cần sắp xếp (truyền bằng tham chiếu).
	 */
	public static function sort_by_name( &$themes ) {
		if ( str_starts_with( get_user_locale(), 'en_' ) ) {
			uasort( $themes, array( 'WP_Theme', '_name_sort' ) );
		} else {
			foreach ( $themes as $key => $theme ) {
				$theme->translate_header( 'Name', $theme->headers['Name'] );
			}
			uasort( $themes, array( 'WP_Theme', '_name_sort_i18n' ) );
		}
	}

	/**
	 * Hàm callback cho usort() để sắp xếp tự nhiên các giao diện theo tên.
	 *
	 * Truy cập trực tiếp header Name từ lớp để đạt tốc độ tối đa.
	 * Có thể bị lỗi với HTML nhưng không đáng để chậm lại với strip_tags().
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Theme $a Giao diện thứ nhất.
	 * @param WP_Theme $b Giao diện thứ hai.
	 * @return int Âm nếu `$a` đứng trước `$b` trong thứ tự tự nhiên. Zero nếu bằng nhau.
	 *             Lớn hơn 0 nếu `$a` đứng sau `$b` trong thứ tự tự nhiên. Dùng với usort().
	 */
	private static function _name_sort( $a, $b ) {
		return strnatcasecmp( $a->headers['Name'], $b->headers['Name'] );
	}

	/**
	 * Hàm callback cho usort() để sắp xếp tự nhiên các giao diện theo tên đã dịch.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Theme $a Giao diện thứ nhất.
	 * @param WP_Theme $b Giao diện thứ hai.
	 * @return int Âm nếu `$a` đứng trước `$b` trong thứ tự tự nhiên. Zero nếu bằng nhau.
	 *             Lớn hơn 0 nếu `$a` đứng sau `$b` trong thứ tự tự nhiên. Dùng với usort().
	 */
	private static function _name_sort_i18n( $a, $b ) {
		return strnatcasecmp( $a->name_translated, $b->name_translated );
	}

	private static function _check_headers_property_has_correct_type( $headers ) {
		if ( ! is_array( $headers ) ) {
			return false;
		}
		foreach ( $headers as $key => $value ) {
			if ( ! is_string( $key ) || ! is_string( $value ) ) {
				return false;
			}
		}
		return true;
	}
}
