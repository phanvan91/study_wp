<?php
/**
 * API Bảng danh sách: Lớp WP_MS_Sites_List_Table
 *
 * @package WordPress
 * @subpackage Administration
 * @since 3.1.0
 */

/**
 * Lớp cốt lõi dùng để hiển thị các website trong bảng danh sách cho quản trị mạng.
 *
 * @since 3.1.0
 *
 * @see WP_List_Table
 */
class WP_MS_Sites_List_Table extends WP_List_Table {

	/**
	 * Danh sách trạng thái website.
	 *
	 * @since 4.3.0
	 * @var array
	 */
	public $status_list;

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_List_Table::__construct() để biết thêm thông tin về các tham số mặc định.
	 *
	 * @param array $args Mảng kết hợp các tham số.
	 */
	public function __construct( $args = array() ) {
		$this->status_list = array(
			'archived' => array( 'site-archived', __( 'Archived' ) ),
			'spam'     => array( 'site-spammed', _x( 'Spam', 'site' ) ),
			'deleted'  => array( 'site-deleted', __( 'Deleted' ) ),
			'mature'   => array( 'site-mature', __( 'Mature' ) ),
		);

		parent::__construct(
			array(
				'plural' => 'sites',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	/**
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'manage_sites' );
	}

	/**
	 * Chuẩn bị danh sách website để hiển thị.
	 *
	 * @since 3.1.0
	 *
	 * @global string $mode Chế độ xem bảng danh sách.
	 * @global string $s
	 * @global wpdb   $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 */
	public function prepare_items() {
		global $mode, $s, $wpdb;

		if ( ! empty( $_REQUEST['mode'] ) ) {
			$mode = 'excerpt' === $_REQUEST['mode'] ? 'excerpt' : 'list';
			set_user_setting( 'sites_list_mode', $mode );
		} else {
			$mode = get_user_setting( 'sites_list_mode', 'list' );
		}

		$per_page = $this->get_items_per_page( 'sites_network_per_page' );

		$pagenum = $this->get_pagenum();

		$s    = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
		$wild = '';
		if ( str_contains( $s, '*' ) ) {
			$wild = '*';
			$s    = trim( $s, '*' );
		}

		/*
		 * Nếu mạng lớn và không đang tìm kiếm, chỉ hiển thị
		 * các website mới nhất không phân trang để tránh truy vấn đếm tốn kém.
		 */
		if ( ! $s && wp_is_large_network() ) {
			if ( ! isset( $_REQUEST['orderby'] ) ) {
				$_GET['orderby']     = '';
				$_REQUEST['orderby'] = '';
			}
			if ( ! isset( $_REQUEST['order'] ) ) {
				$_GET['order']     = 'DESC';
				$_REQUEST['order'] = 'DESC';
			}
		}

		$args = array(
			'number'     => (int) $per_page,
			'offset'     => (int) ( ( $pagenum - 1 ) * $per_page ),
			'network_id' => get_current_network_id(),
		);

		if ( empty( $s ) ) {
			// Không có gì để làm.
		} elseif ( preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $s )
			|| preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s )
			|| preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s )
			|| preg_match( '/^[0-9]{1,3}\.$/', $s )
		) {
			// Địa chỉ IPv4.
			$reg_blog_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT blog_id FROM {$wpdb->registration_log} WHERE {$wpdb->registration_log}.IP LIKE %s",
					$wpdb->esc_like( $s ) . ( ! empty( $wild ) ? '%' : '' )
				)
			);

			if ( $reg_blog_ids ) {
				$args['site__in'] = $reg_blog_ids;
			}
		} elseif ( is_numeric( $s ) && empty( $wild ) ) {
			$args['ID'] = $s;
		} else {
			$args['search'] = $s;

			if ( ! is_subdomain_install() ) {
				$args['search_columns'] = array( 'path' );
			}
		}

		$order_by = isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		if ( 'registered' === $order_by ) {
			// 'registered' là tên trường hợp lệ.
		} elseif ( 'lastupdated' === $order_by ) {
			$order_by = 'last_updated';
		} elseif ( 'blogname' === $order_by ) {
			if ( is_subdomain_install() ) {
				$order_by = 'domain';
			} else {
				$order_by = 'path';
			}
		} elseif ( 'blog_id' === $order_by ) {
			$order_by = 'id';
		} elseif ( ! $order_by ) {
			$order_by = false;
		}

		$args['orderby'] = $order_by;

		if ( $order_by ) {
			$args['order'] = ( isset( $_REQUEST['order'] ) && 'DESC' === strtoupper( $_REQUEST['order'] ) ) ? 'DESC' : 'ASC';
		}

		if ( wp_is_large_network() ) {
			$args['no_found_rows'] = true;
		} else {
			$args['no_found_rows'] = false;
		}

		// Xét đến vai trò mà người dùng đã chọn.
		$status = isset( $_REQUEST['status'] ) ? wp_unslash( trim( $_REQUEST['status'] ) ) : '';
		if ( in_array( $status, array( 'public', 'archived', 'mature', 'spam', 'deleted' ), true ) ) {
			$args[ $status ] = 1;
		}

		/**
		 * Lọc các tham số cho truy vấn website trong bảng danh sách website.
		 *
		 * @since 4.6.0
		 *
		 * @param array $args Mảng các tham số get_sites().
		 */
		$args = apply_filters( 'ms_sites_list_table_query_args', $args );

		$_sites = get_sites( $args );
		if ( is_array( $_sites ) ) {
			update_site_cache( $_sites );

			$this->items = array_slice( $_sites, 0, $per_page );
		}

		$total_sites = get_sites(
			array_merge(
				$args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_sites,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 */
	public function no_items() {
		_e( 'No sites found.' );
	}

	/**
	 * Lấy các liên kết để lọc website theo trạng thái.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$counts = wp_count_sites();

		$statuses = array(
			/* translators: %s: Number of sites. */
			'all'      => _nx_noop(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				'sites'
			),

			/* translators: %s: Number of sites. */
			'public'   => _n_noop(
				'Public <span class="count">(%s)</span>',
				'Public <span class="count">(%s)</span>'
			),

			/* translators: %s: Number of sites. */
			'archived' => _n_noop(
				'Archived <span class="count">(%s)</span>',
				'Archived <span class="count">(%s)</span>'
			),

			/* translators: %s: Number of sites. */
			'mature'   => _n_noop(
				'Mature <span class="count">(%s)</span>',
				'Mature <span class="count">(%s)</span>'
			),

			/* translators: %s: Number of sites. */
			'spam'     => _nx_noop(
				'Spam <span class="count">(%s)</span>',
				'Spam <span class="count">(%s)</span>',
				'sites'
			),

			/* translators: %s: Number of sites. */
			'deleted'  => _n_noop(
				'Deleted <span class="count">(%s)</span>',
				'Deleted <span class="count">(%s)</span>'
			),
		);

		$view_links       = array();
		$requested_status = isset( $_REQUEST['status'] ) ? wp_unslash( trim( $_REQUEST['status'] ) ) : '';
		$url              = 'sites.php';

		foreach ( $statuses as $status => $label_count ) {
			if ( (int) $counts[ $status ] > 0 ) {
				$label = sprintf(
					translate_nooped_plural( $label_count, $counts[ $status ] ),
					number_format_i18n( $counts[ $status ] )
				);

				$full_url = 'all' === $status ? $url : add_query_arg( 'status', $status, $url );

				$view_links[ $status ] = array(
					'url'     => esc_url( $full_url ),
					'label'   => $label,
					'current' => $requested_status === $status || ( '' === $requested_status && 'all' === $status ),
				);
			}
		}

		return $this->get_views_links( $view_links );
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();
		if ( current_user_can( 'delete_sites' ) ) {
			$actions['delete'] = __( 'Delete' );
		}
		$actions['spam']    = _x( 'Mark as spam', 'site' );
		$actions['notspam'] = _x( 'Not spam', 'site' );

		return $actions;
	}

	/**
	 * @global string $mode Chế độ xem bảng danh sách.
	 *
	 * @param string $which Vị trí điều hướng phân trang: 'top' hoặc 'bottom'.
	 */
	protected function pagination( $which ) {
		global $mode;

		parent::pagination( $which );

		if ( 'top' === $which ) {
			$this->view_switcher( $mode );
		}
	}

	/**
	 * Hiển thị các điều khiển bổ sung giữa hành động hàng loạt và phân trang.
	 *
	 * @since 5.3.0
	 *
	 * @param string $which Vị trí markup điều hướng bảng bổ sung: 'top' hoặc 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
		<?php
		if ( 'top' === $which ) {
			ob_start();

			/**
			 * Kích hoạt trước nút Lọc trên bảng danh sách website đa site.
			 *
			 * @since 5.3.0
			 *
			 * @param string $which Vị trí markup điều hướng bảng bổ sung: 'top' hoặc 'bottom'.
			 */
			do_action( 'restrict_manage_sites', $which );

			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo $output;
				submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'site-query-submit' ) );
			}
		}
		?>
		</div>
		<?php
		/**
		 * Kích hoạt ngay sau thẻ đóng div "actions" trong tablenav cho
		 * bảng danh sách website đa site.
		 *
		 * @since 5.3.0
		 *
		 * @param string $which Vị trí markup điều hướng bảng bổ sung: 'top' hoặc 'bottom'.
		 */
		do_action( 'manage_sites_extra_tablenav', $which );
	}

	/**
	 * @return string[] Mảng tiêu đề cột được đánh chỉ mục theo tên cột.
	 */
	public function get_columns() {
		$sites_columns = array(
			'cb'          => '<input type="checkbox" />',
			'blogname'    => __( 'URL' ),
			'lastupdated' => __( 'Last Updated' ),
			'registered'  => _x( 'Registered', 'site' ),
			'users'       => __( 'Users' ),
		);

		if ( has_filter( 'wpmublogsaction' ) ) {
			$sites_columns['plugins'] = __( 'Actions' );
		}

		/**
		 * Lọc các cột website hiển thị trong bảng danh sách Website.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string[] $sites_columns Mảng các cột website hiển thị. Mặc định 'cb',
		 *                               'blogname', 'lastupdated', 'registered', 'users'.
		 */
		return apply_filters( 'wpmu_blogs_columns', $sites_columns );
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {

		if ( is_subdomain_install() ) {
			$blogname_abbr         = __( 'Domain' );
			$blogname_orderby_text = __( 'Table ordered by Site Domain Name.' );
		} else {
			$blogname_abbr         = __( 'Path' );
			$blogname_orderby_text = __( 'Table ordered by Site Path.' );
		}

		return array(
			'blogname'    => array( 'blogname', false, $blogname_abbr, $blogname_orderby_text ),
			'lastupdated' => array( 'lastupdated', true, __( 'Last Updated' ), __( 'Table ordered by Last Updated.' ) ),
			'registered'  => array( 'blog_id', true, _x( 'Registered', 'site' ), __( 'Table ordered by Site Registered Date.' ), 'desc' ),
		);
	}

	/**
	 * Xử lý đầu ra cột hộp kiểm.
	 *
	 * @since 4.3.0
	 * @since 5.9.0 Đổi tên `$blog` thành `$item` để phù hợp với lớp cha cho hỗ trợ tham số đặt tên PHP 8.
	 *
	 * @param array $item Website hiện tại.
	 */
	public function column_cb( $item ) {
		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$blog = $item;

		if ( ! is_main_site( $blog['blog_id'] ) ) :
			$blogname = untrailingslashit( $blog['domain'] . $blog['path'] );
			?>
			<input type="checkbox" id="blog_<?php echo $blog['blog_id']; ?>" name="allblogs[]" value="<?php echo esc_attr( $blog['blog_id'] ); ?>" />
			<label for="blog_<?php echo $blog['blog_id']; ?>">
				<span class="screen-reader-text">
				<?php
				/* translators: %s: Site URL. */
				printf( __( 'Select %s' ), $blogname );
				?>
				</span>
			</label>
			<?php
		endif;
	}

	/**
	 * Xử lý đầu ra cột ID.
	 *
	 * @since 4.4.0
	 *
	 * @param array $blog Website hiện tại.
	 */
	public function column_id( $blog ) {
		echo $blog['blog_id'];
	}

	/**
	 * Xử lý đầu ra cột tên website.
	 *
	 * @since 4.3.0
	 *
	 * @global string $mode Chế độ xem bảng danh sách.
	 *
	 * @param array $blog Website hiện tại.
	 */
	public function column_blogname( $blog ) {
		global $mode;

		$blogname = untrailingslashit( $blog['domain'] . $blog['path'] );

		?>
		<strong>
			<?php
			printf(
				'<a href="%1$s" class="edit">%2$s</a>',
				esc_url( network_admin_url( 'site-info.php?id=' . $blog['blog_id'] ) ),
				$blogname
			);

			$this->site_states( $blog );
			?>
		</strong>
		<?php
		if ( 'list' !== $mode ) {
			switch_to_blog( $blog['blog_id'] );
			echo '<p>';
			printf(
				/* translators: 1: Site title, 2: Site tagline. */
				__( '%1$s &#8211; %2$s' ),
				get_option( 'blogname' ),
				'<em>' . get_option( 'blogdescription' ) . '</em>'
			);
			echo '</p>';
			restore_current_blog();
		}
	}

	/**
	 * Xử lý đầu ra cột cập nhật lần cuối.
	 *
	 * @since 4.3.0
	 *
	 * @global string $mode Chế độ xem bảng danh sách.
	 *
	 * @param array $blog Website hiện tại.
	 */
	public function column_lastupdated( $blog ) {
		global $mode;

		if ( 'list' === $mode ) {
			$date = __( 'Y/m/d' );
		} else {
			$date = __( 'Y/m/d g:i:s a' );
		}

		if ( '0000-00-00 00:00:00' === $blog['last_updated'] ) {
			_e( 'Never' );
		} else {
			echo mysql2date( $date, $blog['last_updated'] );
		}
	}

	/**
	 * Xử lý đầu ra cột ngày đăng ký.
	 *
	 * @since 4.3.0
	 *
	 * @global string $mode Chế độ xem bảng danh sách.
	 *
	 * @param array $blog Website hiện tại.
	 */
	public function column_registered( $blog ) {
		global $mode;

		if ( 'list' === $mode ) {
			$date = __( 'Y/m/d' );
		} else {
			$date = __( 'Y/m/d g:i:s a' );
		}

		if ( '0000-00-00 00:00:00' === $blog['registered'] ) {
			echo '&#x2014;';
		} else {
			echo mysql2date( $date, $blog['registered'] );
		}
	}

	/**
	 * Xử lý đầu ra cột người dùng.
	 *
	 * @since 4.3.0
	 *
	 * @param array $blog Website hiện tại.
	 */
	public function column_users( $blog ) {
		$user_count = wp_cache_get( $blog['blog_id'] . '_user_count', 'blog-details' );
		if ( ! $user_count ) {
			$blog_users = new WP_User_Query(
				array(
					'blog_id'     => $blog['blog_id'],
					'fields'      => 'ID',
					'number'      => 1,
					'count_total' => true,
				)
			);
			$user_count = $blog_users->get_total();
			wp_cache_set( $blog['blog_id'] . '_user_count', $user_count, 'blog-details', 12 * HOUR_IN_SECONDS );
		}

		printf(
			'<a href="%1$s">%2$s</a>',
			esc_url( network_admin_url( 'site-users.php?id=' . $blog['blog_id'] ) ),
			number_format_i18n( $user_count )
		);
	}

	/**
	 * Xử lý đầu ra cột plugin.
	 *
	 * @since 4.3.0
	 *
	 * @param array $blog Website hiện tại.
	 */
	public function column_plugins( $blog ) {
		if ( has_filter( 'wpmublogsaction' ) ) {
			/**
			 * Kích hoạt bên trong cột phụ 'Hành động' của bảng danh sách Website.
			 *
			 * Mặc định cột này bị ẩn trừ khi có hook nào đó được gắn vào action.
			 *
			 * @since MU (3.0.0)
			 *
			 * @param int $blog_id ID website.
			 */
			do_action( 'wpmublogsaction', $blog['blog_id'] );
		}
	}

	/**
	 * Xử lý đầu ra cho cột mặc định.
	 *
	 * @since 4.3.0
	 * @since 5.9.0 Đổi tên `$blog` thành `$item` để phù hợp với lớp cha cho hỗ trợ tham số đặt tên PHP 8.
	 *
	 * @param array  $item        Website hiện tại.
	 * @param string $column_name Tên cột hiện tại.
	 */
	public function column_default( $item, $column_name ) {
		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$blog = $item;

		/**
		 * Kích hoạt cho mỗi cột tùy chỉnh đã đăng ký trong bảng danh sách Website.
		 *
		 * @since 3.1.0
		 *
		 * @param string $column_name Tên cột cần hiển thị.
		 * @param int    $blog_id     ID website.
		 */
		do_action( 'manage_sites_custom_column', $column_name, $blog['blog_id'] );
	}

	/**
	 * Generates the list table rows.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
		foreach ( $this->items as $blog ) {
			$blog  = $blog->to_array();
			$class = '';
			reset( $this->status_list );

			foreach ( $this->status_list as $status => $col ) {
				if ( '1' === $blog[ $status ] ) {
					$class = " class='{$col[0]}'";
				}
			}

			echo "<tr{$class}>";

			$this->single_row_columns( $blog );

			echo '</tr>';
		}
	}

	/**
	 * Determines whether to output comma-separated site states.
	 *
	 * @since 5.3.0
	 *
	 * @param array $site
	 */
	protected function site_states( $site ) {
		$site_states = array();

		// $site is still an array, so get the object.
		$_site = WP_Site::get_instance( $site['blog_id'] );

		if ( is_main_site( $_site->id ) ) {
			$site_states['main'] = __( 'Main' );
		}

		reset( $this->status_list );

		$site_status = isset( $_REQUEST['status'] ) ? wp_unslash( trim( $_REQUEST['status'] ) ) : '';
		foreach ( $this->status_list as $status => $col ) {
			if ( '1' === $_site->{$status} && $site_status !== $status ) {
				$site_states[ $col[0] ] = $col[1];
			}
		}

		/**
		 * Filters the default site display states for items in the Sites list table.
		 *
		 * @since 5.3.0
		 *
		 * @param string[] $site_states An array of site states. Default 'Main',
		 *                              'Archived', 'Mature', 'Spam', 'Deleted'.
		 * @param WP_Site  $site        The current site object.
		 */
		$site_states = apply_filters( 'display_site_states', $site_states, $_site );

		if ( ! empty( $site_states ) ) {
			$state_count = count( $site_states );

			$i = 0;

			echo ' &mdash; ';

			foreach ( $site_states as $state ) {
				++$i;

				$separator = ( $i < $state_count ) ? ', ' : '';

				echo "<span class='post-state'>{$state}{$separator}</span>";
			}
		}
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 4.3.0
	 *
	 * @return string Name of the default primary column, in this case, 'blogname'.
	 */
	protected function get_default_primary_column_name() {
		return 'blogname';
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @since 4.3.0
	 * @since 5.9.0 Renamed `$blog` to `$item` to match parent class for PHP 8 named parameter support.
	 *
	 * @param array  $item        Site being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions output for sites in Multisite, or an empty string
	 *                if the current column is not the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$blog = $item;

		$blogname = untrailingslashit( $blog['domain'] . $blog['path'] );

		// Preordered.
		$actions = array(
			'edit'       => '',
			'backend'    => '',
			'activate'   => '',
			'deactivate' => '',
			'archive'    => '',
			'unarchive'  => '',
			'spam'       => '',
			'unspam'     => '',
			'delete'     => '',
			'visit'      => '',
		);

		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( network_admin_url( 'site-info.php?id=' . $blog['blog_id'] ) ),
			__( 'Edit' )
		);

		$actions['backend'] = sprintf(
			'<a href="%1$s" class="edit">%2$s</a>',
			esc_url( get_admin_url( $blog['blog_id'] ) ),
			__( 'Dashboard' )
		);

		if ( ! is_main_site( $blog['blog_id'] ) ) {
			if ( '1' === $blog['deleted'] ) {
				$actions['activate'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=activateblog&amp;id=' . $blog['blog_id'] ),
							'activateblog_' . $blog['blog_id']
						)
					),
					_x( 'Activate', 'site' )
				);
			} else {
				$actions['deactivate'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=deactivateblog&amp;id=' . $blog['blog_id'] ),
							'deactivateblog_' . $blog['blog_id']
						)
					),
					__( 'Deactivate' )
				);
			}

			if ( '1' === $blog['archived'] ) {
				$actions['unarchive'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=unarchiveblog&amp;id=' . $blog['blog_id'] ),
							'unarchiveblog_' . $blog['blog_id']
						)
					),
					__( 'Unarchive' )
				);
			} else {
				$actions['archive'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=archiveblog&amp;id=' . $blog['blog_id'] ),
							'archiveblog_' . $blog['blog_id']
						)
					),
					_x( 'Archive', 'verb; site' )
				);
			}

			if ( '1' === $blog['spam'] ) {
				$actions['unspam'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=unspamblog&amp;id=' . $blog['blog_id'] ),
							'unspamblog_' . $blog['blog_id']
						)
					),
					_x( 'Not Spam', 'site' )
				);
			} else {
				$actions['spam'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=spamblog&amp;id=' . $blog['blog_id'] ),
							'spamblog_' . $blog['blog_id']
						)
					),
					_x( 'Spam', 'site' )
				);
			}

			if ( current_user_can( 'delete_site', $blog['blog_id'] ) ) {
				$actions['delete'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							network_admin_url( 'sites.php?action=confirm&amp;action2=deleteblog&amp;id=' . $blog['blog_id'] ),
							'deleteblog_' . $blog['blog_id']
						)
					),
					__( 'Delete' )
				);
			}
		}

		$actions['visit'] = sprintf(
			'<a href="%1$s" rel="bookmark">%2$s</a>',
			esc_url( get_home_url( $blog['blog_id'], '/' ) ),
			__( 'Visit' )
		);

		/**
		 * Filters the action links displayed for each site in the Sites list table.
		 *
		 * The 'Edit', 'Dashboard', 'Delete', and 'Visit' links are displayed by
		 * default for each site. The site's status determines whether to show the
		 * 'Activate' or 'Deactivate' link, 'Unarchive' or 'Archive' links, and
		 * 'Not Spam' or 'Spam' link for each site.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $actions  An array of action links to be displayed.
		 * @param int      $blog_id  The site ID.
		 * @param string   $blogname Site path, formatted depending on whether it is a sub-domain
		 *                           or subdirectory multisite installation.
		 */
		$actions = apply_filters( 'manage_sites_action_links', array_filter( $actions ), $blog['blog_id'], $blogname );

		return $this->row_actions( $actions );
	}
}
