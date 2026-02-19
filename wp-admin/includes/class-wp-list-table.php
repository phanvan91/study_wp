<?php
/**
 * API Quản trị: Lớp WP_List_Table
 *
 * @package WordPress
 * @subpackage List_Table
 * @since 3.1.0
 */

/**
 * Lớp cơ sở để hiển thị danh sách các mục trong bảng HTML hỗ trợ Ajax.
 *
 * @since 3.1.0
 */
#[AllowDynamicProperties]
class WP_List_Table {

	/**
	 * Danh sách các mục hiện tại.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	public $items;

	/**
	 * Các thông tin khác nhau về bảng hiện tại.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	protected $_args;

	/**
	 * Các thông tin cần thiết để hiển thị phân trang.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	protected $_pagination_args = array();

	/**
	 * Màn hình hiện tại.
	 *
	 * @since 3.1.0
	 * @var WP_Screen
	 */
	protected $screen;

	/**
	 * Các hành động hàng loạt đã được lưu đệm.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	private $_actions;

	/**
	 * Kết quả phân trang đã được lưu đệm.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	private $_pagination;

	/**
	 * Các chế độ chuyển đổi giao diện.
	 *
	 * @since 4.1.0
	 * @var array
	 */
	protected $modes = array();

	/**
	 * Lưu trữ giá trị trả về bởi ->get_column_info().
	 *
	 * @since 4.1.0
	 * @var array
	 */
	protected $_column_headers;

	/**
	 * {@internal Thiếu tóm tắt}
	 *
	 * @var array
	 */
	protected $compat_fields = array( '_args', '_pagination_args', 'screen', '_actions', '_pagination' );

	/**
	 * {@internal Thiếu tóm tắt}
	 *
	 * @var array
	 */
	protected $compat_methods = array(
		'set_pagination_args',
		'get_views',
		'get_bulk_actions',
		'bulk_actions',
		'row_actions',
		'months_dropdown',
		'view_switcher',
		'comments_bubble',
		'get_items_per_page',
		'pagination',
		'get_sortable_columns',
		'get_column_info',
		'get_table_classes',
		'display_tablenav',
		'extra_tablenav',
		'single_row_columns',
	);

	/**
	 * Hàm khởi tạo.
	 *
	 * Lớp con nên gọi hàm khởi tạo này từ hàm khởi tạo của chính nó để ghi đè
	 * các $args mặc định.
	 *
	 * @since 3.1.0
	 *
	 * @param array|string $args {
	 *     Mảng hoặc chuỗi các đối số.
	 *
	 *     @type string $plural   Giá trị số nhiều dùng cho nhãn và các đối tượng được liệt kê.
	 *                            Ảnh hưởng đến tên lớp CSS và nonce sử dụng
	 *                            trong bảng danh sách, ví dụ: 'posts'. Mặc định rỗng.
	 *     @type string $singular Nhãn số ít cho đối tượng được liệt kê, ví dụ: 'post'.
	 *                            Mặc định rỗng.
	 *     @type bool   $ajax     Bảng danh sách có hỗ trợ Ajax hay không. Bao gồm tải
	 *                            và sắp xếp dữ liệu. Nếu true, lớp sẽ gọi
	 *                            phương thức _js_vars() ở footer để cung cấp biến
	 *                            cho các script xử lý sự kiện Ajax. Mặc định false.
	 *     @type string $screen   Chuỗi chứa tên hook dùng để xác định màn hình
	 *                            hiện tại. Nếu để null, màn hình hiện tại sẽ được tự động thiết lập.
	 *                            Mặc định null.
	 * }
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'plural'   => '',
				'singular' => '',
				'ajax'     => false,
				'screen'   => null,
			)
		);

		$this->screen = convert_to_screen( $args['screen'] );

		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

		if ( ! $args['plural'] ) {
			$args['plural'] = $this->screen->base;
		}

		$args['plural']   = sanitize_key( $args['plural'] );
		$args['singular'] = sanitize_key( $args['singular'] );

		$this->_args = $args;

		if ( $args['ajax'] ) {
			// wp_enqueue_script( 'list-table' );
			add_action( 'admin_footer', array( $this, '_js_vars' ) );
		}

		if ( empty( $this->modes ) ) {
			$this->modes = array(
				'list'    => __( 'Compact view' ),
				'excerpt' => __( 'Extended view' ),
			);
		}
	}

	/**
	 * Cho phép đọc các thuộc tính private để tương thích ngược.
	 *
	 * @since 4.0.0
	 * @since 6.4.0 Việc lấy thuộc tính động đã bị loại bỏ.
	 *
	 * @param string $name Thuộc tính cần lấy.
	 * @return mixed Thuộc tính.
	 */
	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return $this->$name;
		}

		wp_trigger_error(
			__METHOD__,
			"The property `{$name}` is not declared. Getting a dynamic property is " .
			'deprecated since version 6.4.0! Instead, declare the property on the class.',
			E_USER_DEPRECATED
		);
		return null;
	}

	/**
	 * Cho phép gán các thuộc tính private để tương thích ngược.
	 *
	 * @since 4.0.0
	 * @since 6.4.0 Việc gán thuộc tính động đã bị loại bỏ.
	 *
	 * @param string $name  Thuộc tính cần kiểm tra đã được gán chưa.
	 * @param mixed  $value Giá trị thuộc tính.
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			$this->$name = $value;
			return;
		}

		wp_trigger_error(
			__METHOD__,
			"The property `{$name}` is not declared. Setting a dynamic property is " .
			'deprecated since version 6.4.0! Instead, declare the property on the class.',
			E_USER_DEPRECATED
		);
	}

	/**
	 * Cho phép kiểm tra các thuộc tính private để tương thích ngược.
	 *
	 * @since 4.0.0
	 * @since 6.4.0 Việc kiểm tra thuộc tính động đã bị loại bỏ.
	 *
	 * @param string $name Thuộc tính cần kiểm tra đã được gán chưa.
	 * @return bool Thuộc tính có phải là thuộc tính tương thích ngược và đã được gán hay không.
	 */
	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return isset( $this->$name );
		}

		wp_trigger_error(
			__METHOD__,
			"The property `{$name}` is not declared. Checking `isset()` on a dynamic property " .
			'is deprecated since version 6.4.0! Instead, declare the property on the class.',
			E_USER_DEPRECATED
		);
		return false;
	}

	/**
	 * Cho phép hủy gán các thuộc tính private để tương thích ngược.
	 *
	 * @since 4.0.0
	 * @since 6.4.0 Việc hủy gán thuộc tính động đã bị loại bỏ.
	 *
	 * @param string $name Thuộc tính cần hủy gán.
	 */
	public function __unset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			unset( $this->$name );
			return;
		}

		wp_trigger_error(
			__METHOD__,
			"A property `{$name}` is not declared. Unsetting a dynamic property is " .
			'deprecated since version 6.4.0! Instead, declare the property on the class.',
			E_USER_DEPRECATED
		);
	}

	/**
	 * Cho phép gọi các phương thức private/protected để tương thích ngược.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name      Phương thức cần gọi.
	 * @param array  $arguments Các đối số truyền vào khi gọi.
	 * @return mixed|bool Giá trị trả về của callback, false nếu không tìm thấy.
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->compat_methods, true ) ) {
			return $this->$name( ...$arguments );
		}
		return false;
	}

	/**
	 * Kiểm tra quyền của người dùng hiện tại.
	 *
	 * @since 3.1.0
	 * @abstract
	 */
	public function ajax_user_can() {
		die( 'function WP_List_Table::ajax_user_can() must be overridden in a subclass.' );
	}

	/**
	 * Chuẩn bị danh sách các mục để hiển thị.
	 *
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 * @since 3.1.0
	 * @abstract
	 */
	public function prepare_items() {
		die( 'function WP_List_Table::prepare_items() must be overridden in a subclass.' );
	}

	/**
	 * Thiết lập tất cả các đối số phân trang cần thiết.
	 *
	 * @since 3.1.0
	 *
	 * @param array|string $args Mảng hoặc chuỗi các đối số chứa thông tin về phân trang.
	 */
	protected function set_pagination_args( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'total_items' => 0,
				'total_pages' => 0,
				'per_page'    => 0,
			)
		);

		if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
			$args['total_pages'] = (int) ceil( $args['total_items'] / $args['per_page'] );
		}

		// Chuyển hướng nếu số trang không hợp lệ và header chưa được gửi.
		if ( ! headers_sent() && ! wp_doing_ajax() && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
			wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
			exit;
		}

		$this->_pagination_args = $args;
	}

	/**
	 * Truy cập các đối số phân trang.
	 *
	 * @since 3.1.0
	 *
	 * @param string $key Đối số phân trang cần lấy. Các giá trị phổ biến bao gồm 'total_items',
	 *                    'total_pages', 'per_page', hoặc 'infinite_scroll'.
	 * @return int Số lượng mục tương ứng với đối số phân trang đã cho.
	 */
	public function get_pagination_arg( $key ) {
		if ( 'page' === $key ) {
			return $this->get_pagenum();
		}

		if ( isset( $this->_pagination_args[ $key ] ) ) {
			return $this->_pagination_args[ $key ];
		}

		return 0;
	}

	/**
	 * Xác định bảng có mục để hiển thị hay không.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function has_items() {
		return ! empty( $this->items );
	}

	/**
	 * Thông báo hiển thị khi không có mục nào.
	 *
	 * @since 3.1.0
	 */
	public function no_items() {
		_e( 'No items found.' );
	}

	/**
	 * Hiển thị hộp tìm kiếm.
	 *
	 * @since 3.1.0
	 *
	 * @param string $text     Nhãn của nút 'gửi'.
	 * @param string $input_id Giá trị thuộc tính ID cho trường nhập tìm kiếm.
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			if ( is_array( $_REQUEST['orderby'] ) ) {
				foreach ( $_REQUEST['orderby'] as $key => $value ) {
					echo '<input type="hidden" name="orderby[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" />';
				}
			} else {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
			}
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
<p class="search-box">
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
	<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
		<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
		<?php
	}

	/**
	 * Tạo các liên kết giao diện xem.
	 *
	 * @since 6.1.0
	 *
	 * @param array $link_data {
	 *     Mảng dữ liệu liên kết.
	 *
	 *     @type string $url     URL của liên kết.
	 *     @type string $label   Nhãn của liên kết.
	 *     @type bool   $current Tùy chọn. Đây có phải là giao diện xem đang được chọn hay không.
	 * }
	 * @return string[] Mảng mã HTML liên kết. Các khóa khớp với mảng đầu vào `$link_data`.
	 */
	protected function get_views_links( $link_data = array() ) {
		if ( ! is_array( $link_data ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: The $link_data argument. */
					__( 'The %s argument must be an array.' ),
					'<code>$link_data</code>'
				),
				'6.1.0'
			);

			return array( '' );
		}

		$views_links = array();

		foreach ( $link_data as $view => $link ) {
			if ( empty( $link['url'] ) || ! is_string( $link['url'] ) || '' === trim( $link['url'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %1$s: The argument name. %2$s: The view name. */
						__( 'The %1$s argument must be a non-empty string for %2$s.' ),
						'<code>url</code>',
						'<code>' . esc_html( $view ) . '</code>'
					),
					'6.1.0'
				);

				continue;
			}

			if ( empty( $link['label'] ) || ! is_string( $link['label'] ) || '' === trim( $link['label'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %1$s: The argument name. %2$s: The view name. */
						__( 'The %1$s argument must be a non-empty string for %2$s.' ),
						'<code>label</code>',
						'<code>' . esc_html( $view ) . '</code>'
					),
					'6.1.0'
				);

				continue;
			}

			$views_links[ $view ] = sprintf(
				'<a href="%s"%s>%s</a>',
				esc_url( $link['url'] ),
				isset( $link['current'] ) && true === $link['current'] ? ' class="current" aria-current="page"' : '',
				$link['label']
			);
		}

		return $views_links;
	}

	/**
	 * Lấy danh sách các giao diện xem có sẵn trên bảng này.
	 *
	 * Định dạng là mảng kết hợp:
	 * - `'id' => 'link'`
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	protected function get_views() {
		return array();
	}

	/**
	 * Hiển thị danh sách các giao diện xem có sẵn trên bảng này.
	 *
	 * @since 3.1.0
	 */
	public function views() {
		$views = $this->get_views();
		/**
		 * Lọc danh sách các giao diện xem có sẵn của bảng danh sách.
		 *
		 * Phần động của tên hook, `$this->screen->id`, tham chiếu
		 * đến ID của màn hình hiện tại.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $views Mảng các giao diện xem có sẵn của bảng danh sách.
		 */
		$views = apply_filters( "views_{$this->screen->id}", $views );

		if ( empty( $views ) ) {
			return;
		}

		$this->screen->render_screen_reader_content( 'heading_views' );

		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo '</ul>';
	}

	/**
	 * Lấy danh sách các hành động hàng loạt có sẵn cho bảng này.
	 *
	 * Định dạng là mảng kết hợp trong đó mỗi phần tử đại diện cho một giá trị và nhãn tùy chọn cấp cao nhất,
	 * hoặc một mảng đại diện cho optgroup và các tùy chọn của nó.
	 *
	 * Với tùy chọn tiêu chuẩn, khóa phần tử mảng là giá trị trường và giá trị phần tử mảng là nhãn trường.
	 *
	 * Với optgroup, khóa phần tử mảng là nhãn và giá trị phần tử mảng là mảng kết hợp
	 * các tùy chọn như trên.
	 *
	 * Ví dụ:
	 *
	 *     [
	 *         'edit'         => 'Edit',
	 *         'delete'       => 'Delete',
	 *         'Change State' => [
	 *             'feature' => 'Featured',
	 *             'sale'    => 'On Sale',
	 *         ]
	 *     ]
	 *
	 * @since 3.1.0
	 * @since 5.6.0 Hành động hàng loạt giờ có thể chứa mảng tùy chọn để tạo optgroup.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Hiển thị dropdown hành động hàng loạt.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which Vị trí của hành động hàng loạt: 'top' hoặc 'bottom'.
	 *                      Được đánh dấu là tùy chọn để tương thích ngược.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();

			/**
			 * Lọc các mục trong menu hành động hàng loạt của bảng danh sách.
			 *
			 * Phần động của tên hook, `$this->screen->id`, tham chiếu
			 * đến ID của màn hình hiện tại.
			 *
			 * @since 3.1.0
			 * @since 5.6.0 Hành động hàng loạt giờ có thể chứa mảng tùy chọn để tạo optgroup.
			 *
			 * @param array $actions Mảng các hành động hàng loạt có sẵn.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' .
			/* translators: Hidden accessibility text. */
			__( 'Select bulk action' ) .
		'</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk actions' ) . "</option>\n";

		foreach ( $this->_actions as $key => $value ) {
			if ( is_array( $value ) ) {
				echo "\t" . '<optgroup label="' . esc_attr( $key ) . '">' . "\n";

				foreach ( $value as $name => $title ) {
					$class = ( 'edit' === $name ) ? ' class="hide-if-no-js"' : '';

					echo "\t\t" . '<option value="' . esc_attr( $name ) . '"' . $class . '>' . $title . "</option>\n";
				}
				echo "\t" . "</optgroup>\n";
			} else {
				$class = ( 'edit' === $key ) ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $key ) . '"' . $class . '>' . $value . "</option>\n";
			}
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', 'bulk_action', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}

	/**
	 * Lấy hành động hiện tại được chọn từ dropdown hành động hàng loạt.
	 *
	 * @since 3.1.0
	 *
	 * @return string|false Tên hành động. False nếu không có hành động nào được chọn.
	 */
	public function current_action() {
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) ) {
			return false;
		}

		if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) {
			return $_REQUEST['action'];
		}

		return false;
	}

	/**
	 * Tạo mã HTML cần thiết cho danh sách các liên kết hành động hàng.
	 *
	 * @since 3.1.0
	 *
	 * @param string[] $actions        Mảng các liên kết hành động.
	 * @param bool     $always_visible Các hành động có luôn hiển thị hay không.
	 * @return string Mã HTML cho các hành động hàng.
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );

		if ( ! $action_count ) {
			return '';
		}

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		if ( 'excerpt' === $mode ) {
			$always_visible = true;
		}

		$output = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

		$i = 0;

		foreach ( $actions as $action => $link ) {
			++$i;

			$separator = ( $i < $action_count ) ? ' | ' : '';

			$output .= "<span class='$action'>{$link}{$separator}</span>";
		}

		$output .= '</div>';

		$output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' .
			/* translators: Hidden accessibility text. */
			__( 'Show more details' ) .
		'</span></button>';

		return $output;
	}

	/**
	 * Hiển thị dropdown để lọc các mục trong bảng danh sách theo tháng.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb      $wpdb      Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 * @global WP_Locale $wp_locale Đối tượng ngôn ngữ ngày giờ WordPress.
	 *
	 * @param string $post_type Loại bài viết.
	 */
	protected function months_dropdown( $post_type ) {
		global $wpdb, $wp_locale;

		/**
		 * Lọc có xóa dropdown 'Tháng' khỏi bảng danh sách bài viết hay không.
		 *
		 * @since 4.2.0
		 *
		 * @param bool   $disable   Có vô hiệu hóa dropdown hay không. Mặc định false.
		 * @param string $post_type Loại bài viết.
		 */
		if ( apply_filters( 'disable_months_dropdown', false, $post_type ) ) {
			return;
		}

		/**
		 * Lọc có bỏ qua việc thực hiện truy vấn dropdown tháng hay không.
		 *
		 * @since 5.7.0
		 *
		 * @param object[]|false $months    Kết quả dropdown 'Tháng'. Mặc định false.
		 * @param string         $post_type Loại bài viết.
		 */
		$months = apply_filters( 'pre_months_dropdown_query', false, $post_type );

		if ( ! is_array( $months ) ) {
			$extra_checks = "AND post_status != 'auto-draft'";
			if ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) {
				$extra_checks .= " AND post_status != 'trash'";
			} elseif ( isset( $_GET['post_status'] ) ) {
				$extra_checks = $wpdb->prepare( ' AND post_status = %s', $_GET['post_status'] );
			}

			$months = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
					FROM $wpdb->posts
					WHERE post_type = %s
					$extra_checks
					ORDER BY post_date DESC",
					$post_type
				)
			);
		}

		/**
		 * Lọc kết quả dropdown 'Tháng'.
		 *
		 * @since 3.7.0
		 *
		 * @param object[] $months    Mảng kết quả truy vấn dropdown tháng.
		 * @param string   $post_type Loại bài viết.
		 */
		$months = apply_filters( 'months_dropdown_results', $months, $post_type );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 === $month_count && 0 === (int) $months[0]->month ) ) {
			return;
		}

		$selected_month = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php echo get_post_type_object( $post_type )->labels->filter_by_date; ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $selected_month, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
		<?php
		foreach ( $months as $arc_row ) {
			if ( 0 === (int) $arc_row->year ) {
				continue;
			}

			$month = zeroise( $arc_row->month, 2 );
			$year  = $arc_row->year;

			printf(
				"<option %s value='%s'>%s</option>\n",
				selected( $selected_month, $year . $month, false ),
				esc_attr( $year . $month ),
				/* translators: 1: Month name, 2: 4-digit year. */
				esc_html( sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year ) )
			);
		}
		?>
		</select>
		<?php
	}

	/**
	 * Hiển thị bộ chuyển đổi giao diện xem.
	 *
	 * @since 3.1.0
	 *
	 * @param string $current_mode Chế độ xem hiện tại.
	 */
	protected function view_switcher( $current_mode ) {
		?>
		<input type="hidden" name="mode" value="<?php echo esc_attr( $current_mode ); ?>" />
		<div class="view-switch">
		<?php
		foreach ( $this->modes as $mode => $title ) {
			$classes      = array( 'view-' . $mode );
			$aria_current = '';

			if ( $current_mode === $mode ) {
				$classes[]    = 'current';
				$aria_current = ' aria-current="page"';
			}

			printf(
				"<a href='%s' class='%s' id='view-switch-$mode'$aria_current>" .
					"<span class='screen-reader-text'>%s</span>" .
				"</a>\n",
				esc_url( remove_query_arg( 'attachment-filter', add_query_arg( 'mode', $mode ) ) ),
				implode( ' ', $classes ),
				$title
			);
		}
		?>
		</div>
		<?php
	}

	/**
	 * Hiển thị bong bóng đếm bình luận.
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id          ID bài viết.
	 * @param int $pending_comments Số bình luận đang chờ duyệt.
	 */
	protected function comments_bubble( $post_id, $pending_comments ) {
		$post_object   = get_post( $post_id );
		$edit_post_cap = $post_object ? 'edit_post' : 'edit_posts';

		if ( ! current_user_can( $edit_post_cap, $post_id )
			&& ( post_password_required( $post_id )
				|| ! current_user_can( 'read_post', $post_id ) )
		) {
			// Người dùng không có quyền truy cập bài viết nên không thể xem bình luận.
			return false;
		}

		$approved_comments = get_comments_number();

		$approved_comments_number = number_format_i18n( $approved_comments );
		$pending_comments_number  = number_format_i18n( $pending_comments );

		$approved_only_phrase = sprintf(
			/* translators: %s: Number of comments. */
			_n( '%s comment', '%s comments', $approved_comments ),
			$approved_comments_number
		);

		$approved_phrase = sprintf(
			/* translators: %s: Number of comments. */
			_n( '%s approved comment', '%s approved comments', $approved_comments ),
			$approved_comments_number
		);

		$pending_phrase = sprintf(
			/* translators: %s: Number of comments. */
			_n( '%s pending comment', '%s pending comments', $pending_comments ),
			$pending_comments_number
		);

		if ( ! $approved_comments && ! $pending_comments ) {
			// Không có bình luận nào.
			printf(
				'<span aria-hidden="true">&#8212;</span>' .
				'<span class="screen-reader-text">%s</span>',
				__( 'No comments' )
			);
		} elseif ( $approved_comments && 'trash' === get_post_status( $post_id ) ) {
			// Không tạo liên kết bong bóng bình luận cho bài viết đã xóa.
			printf(
				'<span class="post-com-count post-com-count-approved">' .
					'<span class="comment-count-approved" aria-hidden="true">%s</span>' .
					'<span class="screen-reader-text">%s</span>' .
				'</span>',
				$approved_comments_number,
				$pending_comments ? $approved_phrase : $approved_only_phrase
			);
		} elseif ( $approved_comments ) {
			// Liên kết bong bóng bình luận đến các bình luận đã duyệt.
			printf(
				'<a href="%s" class="post-com-count post-com-count-approved">' .
					'<span class="comment-count-approved" aria-hidden="true">%s</span>' .
					'<span class="screen-reader-text">%s</span>' .
				'</a>',
				esc_url(
					add_query_arg(
						array(
							'p'              => $post_id,
							'comment_status' => 'approved',
						),
						admin_url( 'edit-comments.php' )
					)
				),
				$approved_comments_number,
				$pending_comments ? $approved_phrase : $approved_only_phrase
			);
		} else {
			// Không liên kết bong bóng bình luận khi không có bình luận đã duyệt.
			printf(
				'<span class="post-com-count post-com-count-no-comments">' .
					'<span class="comment-count comment-count-no-comments" aria-hidden="true">%s</span>' .
					'<span class="screen-reader-text">%s</span>' .
				'</span>',
				$approved_comments_number,
				$pending_comments ?
				/* translators: Hidden accessibility text. */
				__( 'No approved comments' ) :
				/* translators: Hidden accessibility text. */
				__( 'No comments' )
			);
		}

		if ( $pending_comments ) {
			printf(
				'<a href="%s" class="post-com-count post-com-count-pending">' .
					'<span class="comment-count-pending" aria-hidden="true">%s</span>' .
					'<span class="screen-reader-text">%s</span>' .
				'</a>',
				esc_url(
					add_query_arg(
						array(
							'p'              => $post_id,
							'comment_status' => 'moderated',
						),
						admin_url( 'edit-comments.php' )
					)
				),
				$pending_comments_number,
				$pending_phrase
			);
		} else {
			printf(
				'<span class="post-com-count post-com-count-pending post-com-count-no-pending">' .
					'<span class="comment-count comment-count-no-pending" aria-hidden="true">%s</span>' .
					'<span class="screen-reader-text">%s</span>' .
				'</span>',
				$pending_comments_number,
				$approved_comments ?
				/* translators: Hidden accessibility text. */
				__( 'No pending comments' ) :
				/* translators: Hidden accessibility text. */
				__( 'No comments' )
			);
		}
	}

	/**
	 * Lấy số trang hiện tại.
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	public function get_pagenum() {
		$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

		if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
			$pagenum = $this->_pagination_args['total_pages'];
		}

		return max( 1, $pagenum );
	}

	/**
	 * Lấy số lượng mục hiển thị trên một trang.
	 *
	 * @since 3.1.0
	 *
	 * @param string $option        Tên tùy chọn người dùng.
	 * @param int    $default_value Tùy chọn. Số lượng mục hiển thị. Mặc định 20.
	 * @return int
	 */
	protected function get_items_per_page( $option, $default_value = 20 ) {
		$per_page = (int) get_user_option( $option );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $default_value;
		}

		/**
		 * Lọc số lượng mục hiển thị trên mỗi trang của bảng danh sách.
		 *
		 * Tên hook động, `$option`, tham chiếu đến tùy chọn `per_page` tùy thuộc
		 * vào loại bảng danh sách đang sử dụng. Các tên bộ lọc có thể bao gồm:
		 *
		 *  - `edit_comments_per_page`
		 *  - `sites_network_per_page`
		 *  - `site_themes_network_per_page`
		 *  - `themes_network_per_page`
		 *  - `users_network_per_page`
		 *  - `edit_post_per_page`
		 *  - `edit_page_per_page`
		 *  - `edit_{$post_type}_per_page`
		 *  - `edit_post_tag_per_page`
		 *  - `edit_category_per_page`
		 *  - `edit_{$taxonomy}_per_page`
		 *  - `site_users_network_per_page`
		 *  - `users_per_page`
		 *
		 * @since 2.9.0
		 *
		 * @param int $per_page Số lượng mục hiển thị. Mặc định 20.
		 */
		return (int) apply_filters( "{$option}", $per_page );
	}

	/**
	 * Hiển thị phân trang.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which Vị trí phân trang: 'top' hoặc 'bottom'.
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items     = $this->_pagination_args['total_items'];
		$total_pages     = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . sprintf(
			/* translators: %s: Number of items. */
			_n( '%s item', '%s items', $total_items ),
			number_format_i18n( $total_items )
		) . '</span>';

		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;

		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $total_pages === $current ) {
			$disable_last = true;
			$disable_next = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = sprintf(
				'<span class="screen-reader-text">%s</span>' .
				'<span id="table-paging" class="paging-input">' .
				'<span class="tablenav-paging-text">',
				/* translators: Hidden accessibility text. */
				__( 'Current Page' )
			);
		} else {
			$html_current_page = sprintf(
				'<label for="current-page-selector" class="screen-reader-text">%s</label>' .
				"<input class='current-page' id='current-page-selector' type='text'
					name='paged' value='%s' size='%d' aria-describedby='table-paging' />" .
				"<span class='tablenav-paging-text'>",
				/* translators: Hidden accessibility text. */
				__( 'Current Page' ),
				$current,
				strlen( $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		$page_links[] = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
			_x( '%1$s of %2$s', 'paging' ),
			$html_current_page,
			$html_total_pages
		) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * Lấy danh sách các cột.
	 *
	 * Định dạng là:
	 * - `'tên-nội-bộ' => 'Tiêu đề'`
	 *
	 * @since 3.1.0
	 * @abstract
	 *
	 * @return array
	 */
	public function get_columns() {
		die( 'function WP_List_Table::get_columns() must be overridden in a subclass.' );
	}

	/**
	 * Lấy danh sách các cột có thể sắp xếp.
	 *
	 * Định dạng là:
	 * - `'tên-nội-bộ' => 'orderby'`
	 * - `'tên-nội-bộ' => array( 'orderby', bool, 'abbr', 'orderby-text', 'initially-sorted-column-order' )` -
	 * - `'tên-nội-bộ' => array( 'orderby', 'asc' )` - Phần tử thứ hai thiết lập thứ tự sắp xếp ban đầu.
	 * - `'tên-nội-bộ' => array( 'orderby', true )`  - Phần tử thứ hai đặt thứ tự ban đầu là giảm dần.
	 *
	 * Ở định dạng thứ hai, truyền true làm tham số thứ hai sẽ đặt thứ tự
	 * sắp xếp ban đầu là giảm dần. Các tham số tiếp theo thêm tên cột viết tắt
	 * dùng cho thuộc tính 'abbr', chuỗi có thể dịch cho sắp xếp hiện tại,
	 * và thứ tự ban đầu cho cột sắp xếp mặc định, 'asc' hoặc 'desc' (mặc định: false).
	 *
	 * @since 3.1.0
	 * @since 6.3.0 Thêm 'abbr', 'orderby-text' và 'initially-sorted-column-order'.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Lấy tên cột chính mặc định.
	 *
	 * @since 4.3.0
	 *
	 * @return string Tên cột chính mặc định, trong trường hợp này là chuỗi rỗng.
	 */
	protected function get_default_primary_column_name() {
		$columns = $this->get_columns();
		$column  = '';

		if ( empty( $columns ) ) {
			return $column;
		}

		/*
		 * Cần xác định cột chính để giao diện đáp ứng hiển thị nội dung,
		 * vì vậy sử dụng cột đầu tiên không phải checkbox làm mặc định.
		 */
		foreach ( $columns as $col => $column_name ) {
			if ( 'cb' === $col ) {
				continue;
			}

			$column = $col;
			break;
		}

		return $column;
	}

	/**
	 * Lấy tên cột chính.
	 *
	 * Phương thức công khai bọc WP_List_Table::get_default_primary_column_name().
	 *
	 * @since 4.4.0
	 *
	 * @return string Tên cột chính mặc định.
	 */
	public function get_primary_column() {
		return $this->get_primary_column_name();
	}

	/**
	 * Lấy tên cột chính.
	 *
	 * @since 4.3.0
	 *
	 * @return string Tên cột chính.
	 */
	protected function get_primary_column_name() {
		$columns = get_column_headers( $this->screen );
		$default = $this->get_default_primary_column_name();

		/*
		 * Nếu cột chính không tồn tại,
		 * sử dụng cột đầu tiên không phải checkbox làm mặc định.
		 */
		if ( ! isset( $columns[ $default ] ) ) {
			$default = self::get_default_primary_column_name();
		}

		/**
		 * Filters the name of the primary column for the current list table.
		 *
		 * @since 4.3.0
		 *
		 * @param string $default Column name default for the specific list table, e.g. 'name'.
		 * @param string $context Screen ID for specific list table, e.g. 'plugins'.
		 */
		$column = apply_filters( 'list_table_primary_column', $default, $this->screen->id );

		if ( empty( $column ) || ! isset( $columns[ $column ] ) ) {
			$column = $default;
		}

		return $column;
	}

	/**
	 * Gets a list of all, hidden, and sortable columns, with filter applied.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	protected function get_column_info() {
		// $_column_headers is already set / cached.
		if (
			isset( $this->_column_headers ) &&
			is_array( $this->_column_headers )
		) {
			/*
			 * Backward compatibility for `$_column_headers` format prior to WordPress 4.3.
			 *
			 * In WordPress 4.3 the primary column name was added as a fourth item in the
			 * column headers property. This ensures the primary column name is included
			 * in plugins setting the property directly in the three item format.
			 */
			if ( 4 === count( $this->_column_headers ) ) {
				return $this->_column_headers;
			}

			$column_headers = array( array(), array(), array(), $this->get_primary_column_name() );
			foreach ( $this->_column_headers as $key => $value ) {
				$column_headers[ $key ] = $value;
			}

			$this->_column_headers = $column_headers;

			return $this->_column_headers;
		}

		$columns = get_column_headers( $this->screen );
		$hidden  = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();
		/**
		 * Filters the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen.
		 *
		 * @since 3.1.0
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;
			// Descending initial sorting.
			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}
			// Current sorting translatable string.
			if ( ! isset( $data[2] ) ) {
				$data[2] = '';
			}
			// Initial view sorted column and asc/desc order, default: false.
			if ( ! isset( $data[3] ) ) {
				$data[3] = false;
			}
			// Initial order for the initial sorted column, default: false.
			if ( ! isset( $data[4] ) ) {
				$data[4] = false;
			}

			$sortable[ $id ] = $data;
		}

		$primary               = $this->get_primary_column_name();
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		return $this->_column_headers;
	}

	/**
	 * Returns the number of visible columns.
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	public function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden                    = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
		return count( $columns ) - count( $hidden );
	}

	/**
	 * Prints column headers, accounting for hidden and sortable columns.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $with_id Whether to set the ID attribute or not
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		// When users click on a column header to sort by other columns.
		if ( isset( $_GET['orderby'] ) ) {
			$current_orderby = $_GET['orderby'];
			// In the initial view there's no orderby parameter.
		} else {
			$current_orderby = '';
		}

		// Not in the initial view and descending order.
		if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
			$current_order = 'desc';
		} else {
			// The initial view is not always 'asc', we'll take care of this below.
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />
			<label for="cb-select-all-' . $cb_counter . '">' .
				'<span class="screen-reader-text">' .
					/* translators: Hidden accessibility text. */
					__( 'Select All' ) .
				'</span>' .
				'</label>';
			++$cb_counter;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class          = array( 'manage-column', "column-$column_key" );
			$aria_sort_attr = '';
			$abbr_attr      = '';
			$order_text     = '';

			if ( in_array( $column_key, $hidden, true ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			if ( isset( $sortable[ $column_key ] ) ) {
				$orderby       = isset( $sortable[ $column_key ][0] ) ? $sortable[ $column_key ][0] : '';
				$desc_first    = isset( $sortable[ $column_key ][1] ) ? $sortable[ $column_key ][1] : false;
				$abbr          = isset( $sortable[ $column_key ][2] ) ? $sortable[ $column_key ][2] : '';
				$orderby_text  = isset( $sortable[ $column_key ][3] ) ? $sortable[ $column_key ][3] : '';
				$initial_order = isset( $sortable[ $column_key ][4] ) ? $sortable[ $column_key ][4] : '';

				/*
				 * We're in the initial view and there's no $_GET['orderby'] then check if the
				 * initial sorting information is set in the sortable columns and use that.
				 */
				if ( '' === $current_orderby && $initial_order ) {
					// Use the initially sorted column $orderby as current orderby.
					$current_orderby = $orderby;
					// Use the initially sorted column asc/desc order as initial order.
					$current_order = $initial_order;
				}

				/*
				 * True in the initial view when an initial orderby is set via get_sortable_columns()
				 * and true in the sorted views when the actual $_GET['orderby'] is equal to $orderby.
				 */
				if ( $current_orderby === $orderby ) {
					// The sorted column. The `aria-sort` attribute must be set only on the sorted column.
					if ( 'asc' === $current_order ) {
						$order          = 'desc';
						$aria_sort_attr = ' aria-sort="ascending"';
					} else {
						$order          = 'asc';
						$aria_sort_attr = ' aria-sort="descending"';
					}

					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					// The other sortable columns.
					$order = strtolower( $desc_first );

					if ( ! in_array( $order, array( 'desc', 'asc' ), true ) ) {
						$order = $desc_first ? 'desc' : 'asc';
					}

					$class[] = 'sortable';
					$class[] = 'desc' === $order ? 'asc' : 'desc';

					/* translators: Hidden accessibility text. */
					$asc_text = __( 'Sort ascending.' );
					/* translators: Hidden accessibility text. */
					$desc_text  = __( 'Sort descending.' );
					$order_text = 'asc' === $order ? $asc_text : $desc_text;
				}

				if ( '' !== $order_text ) {
					$order_text = ' <span class="screen-reader-text">' . $order_text . '</span>';
				}

				// Print an 'abbr' attribute if a value is provided via get_sortable_columns().
				$abbr_attr = $abbr ? ' abbr="' . esc_attr( $abbr ) . '"' : '';

				$column_display_name = sprintf(
					'<a href="%1$s">' .
						'<span>%2$s</span>' .
						'<span class="sorting-indicators">' .
							'<span class="sorting-indicator asc" aria-hidden="true"></span>' .
							'<span class="sorting-indicator desc" aria-hidden="true"></span>' .
						'</span>' .
						'%3$s' .
					'</a>',
					esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ),
					$column_display_name,
					$order_text
				);
			}

			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . implode( ' ', $class ) . "'";
			}

			echo "<$tag $scope $id $class $aria_sort_attr $abbr_attr>$column_display_name</$tag>";
		}
	}

	/**
	 * Print a table description with information about current sorting and order.
	 *
	 * For the table initial view, information about initial orderby and order
	 * should be provided via get_sortable_columns().
	 *
	 * @since 6.3.0
	 * @access public
	 */
	public function print_table_description() {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		if ( empty( $sortable ) ) {
			return;
		}

		// When users click on a column header to sort by other columns.
		if ( isset( $_GET['orderby'] ) ) {
			$current_orderby = $_GET['orderby'];
			// In the initial view there's no orderby parameter.
		} else {
			$current_orderby = '';
		}

		// Not in the initial view and descending order.
		if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
			$current_order = 'desc';
		} else {
			// The initial view is not always 'asc', we'll take care of this below.
			$current_order = 'asc';
		}

		foreach ( array_keys( $columns ) as $column_key ) {

			if ( isset( $sortable[ $column_key ] ) ) {
				$orderby       = isset( $sortable[ $column_key ][0] ) ? $sortable[ $column_key ][0] : '';
				$desc_first    = isset( $sortable[ $column_key ][1] ) ? $sortable[ $column_key ][1] : false;
				$abbr          = isset( $sortable[ $column_key ][2] ) ? $sortable[ $column_key ][2] : '';
				$orderby_text  = isset( $sortable[ $column_key ][3] ) ? $sortable[ $column_key ][3] : '';
				$initial_order = isset( $sortable[ $column_key ][4] ) ? $sortable[ $column_key ][4] : '';

				if ( ! is_string( $orderby_text ) || '' === $orderby_text ) {
					return;
				}
				/*
				 * We're in the initial view and there's no $_GET['orderby'] then check if the
				 * initial sorting information is set in the sortable columns and use that.
				 */
				if ( '' === $current_orderby && $initial_order ) {
					// Use the initially sorted column $orderby as current orderby.
					$current_orderby = $orderby;
					// Use the initially sorted column asc/desc order as initial order.
					$current_order = $initial_order;
				}

				/*
				 * True in the initial view when an initial orderby is set via get_sortable_columns()
				 * and true in the sorted views when the actual $_GET['orderby'] is equal to $orderby.
				 */
				if ( $current_orderby === $orderby ) {
					/* translators: Hidden accessibility text. */
					$asc_text = __( 'Ascending.' );
					/* translators: Hidden accessibility text. */
					$desc_text  = __( 'Descending.' );
					$order_text = 'asc' === $current_order ? $asc_text : $desc_text;
					echo '<caption class="screen-reader-text">' . $orderby_text . ' ' . $order_text . '</caption>';

					return;
				}
			}
		}
	}

	/**
	 * Displays the table.
	 *
	 * @since 3.1.0
	 */
	public function display() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
		<?php $this->print_table_description(); ?>
	<thead>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>

	<tbody id="the-list"
		<?php
		if ( $singular ) {
			echo " data-wp-lists='list:$singular'";
		}
		?>
		>
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>

	<tfoot>
	<tr>
		<?php $this->print_column_headers( false ); ?>
	</tr>
	</tfoot>

</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Gets a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @since 3.1.0
	 *
	 * @return string[] Array of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		return array( 'widefat', 'fixed', 'striped', $mode_class, $this->_args['plural'] );
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which The location of the navigation: Either 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
			<?php
		endif;
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

		<br class="clear" />
	</div>
		<?php
	}

	/**
	 * Displays extra controls between bulk actions and pagination.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {}

	/**
	 * Generates the tbody element for the list table.
	 *
	 * @since 3.1.0
	 */
	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/**
	 * Generates the list table rows.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * @param object|array $item
	 * @param string $column_name
	 */
	protected function column_default( $item, $column_name ) {}

	/**
	 * @param object|array $item
	 */
	protected function column_cb( $item ) {}

	/**
	 * Generates the columns for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item.
	 */
	protected function single_row_columns( $item ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
			}

			/*
			 * Comments column uses HTML in the display name with screen reader text.
			 * Strip tags to get closer to a user-friendly string.
			 */
			$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

			$attributes = "class='$classes' $data";

			if ( 'cb' === $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item );
				echo '</th>';
			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
				echo call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
				);
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( array( $this, 'column_' . $column_name ), $item );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			} else {
				echo "<td $attributes>";
				echo $this->column_default( $item, $column_name );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			}
		}
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @since 4.3.0
	 *
	 * @param object|array $item        The item being acted upon.
	 * @param string       $column_name Current column name.
	 * @param string       $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string
	 *                if the current column is not the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return $column_name === $primary ? '<button type="button" class="toggle-row"><span class="screen-reader-text">' .
			/* translators: Hidden accessibility text. */
			__( 'Show more details' ) .
		'</span></button>' : '';
	}

	/**
	 * Handles an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 3.1.0
	 */
	public function ajax_response() {
		$this->prepare_items();

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}

		$rows = ob_get_clean();

		$response = array( 'rows' => $rows );

		if ( isset( $this->_pagination_args['total_items'] ) ) {
			$response['total_items_i18n'] = sprintf(
				/* translators: Number of items. */
				_n( '%s item', '%s items', $this->_pagination_args['total_items'] ),
				number_format_i18n( $this->_pagination_args['total_items'] )
			);
		}
		if ( isset( $this->_pagination_args['total_pages'] ) ) {
			$response['total_pages']      = $this->_pagination_args['total_pages'];
			$response['total_pages_i18n'] = number_format_i18n( $this->_pagination_args['total_pages'] );
		}

		die( wp_json_encode( $response ) );
	}

	/**
	 * Sends required variables to JavaScript land.
	 *
	 * @since 3.1.0
	 */
	public function _js_vars() {
		$args = array(
			'class'  => get_class( $this ),
			'screen' => array(
				'id'   => $this->screen->id,
				'base' => $this->screen->base,
			),
		);

		printf( "<script type='text/javascript'>list_args = %s;</script>\n", wp_json_encode( $args ) );
	}
}
