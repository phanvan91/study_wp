<?php
/**
 * API Bảng danh sách: Lớp WP_Links_List_Table
 *
 * @package WordPress
 * @subpackage Administration
 * @since 3.1.0
 */

/**
 * Lớp lõi được sử dụng để triển khai hiển thị liên kết trong bảng danh sách.
 *
 * @since 3.1.0
 *
 * @see WP_List_Table
 */
class WP_Links_List_Table extends WP_List_Table {

	/**
	 * Hàm khởi tạo.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_List_Table::__construct() để biết thêm thông tin về các đối số mặc định.
	 *
	 * @param array $args Mảng kết hợp các đối số.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural' => 'bookmarks',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	/**
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'manage_links' );
	}

	/**
	 * @global int    $cat_id  ID chuyên mục.
	 * @global string $s       Từ khóa tìm kiếm.
	 * @global string $orderby Trường sắp xếp.
	 * @global string $order   Thứ tự sắp xếp.
	 */
	public function prepare_items() {
		global $cat_id, $s, $orderby, $order;

		$cat_id  = ! empty( $_REQUEST['cat_id'] ) ? absint( $_REQUEST['cat_id'] ) : 0;
		$orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : '';
		$order   = ! empty( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : '';
		$s       = ! empty( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

		$args = array(
			'hide_invisible' => 0,
			'hide_empty'     => 0,
		);

		if ( 'all' !== $cat_id ) {
			$args['category'] = $cat_id;
		}
		if ( ! empty( $s ) ) {
			$args['search'] = $s;
		}
		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;
		}
		if ( ! empty( $order ) ) {
			$args['order'] = $order;
		}

		$this->items = get_bookmarks( $args );
	}

	/**
	 */
	public function no_items() {
		_e( 'No links found.' );
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions           = array();
		$actions['delete'] = __( 'Delete' );

		return $actions;
	}

	/**
	 * @global int $cat_id ID chuyên mục.
	 * @param string $which Vị trí của điều hướng bảng.
	 */
	protected function extra_tablenav( $which ) {
		global $cat_id;

		if ( 'top' !== $which ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<?php
			$dropdown_options = array(
				'selected'        => $cat_id,
				'name'            => 'cat_id',
				'taxonomy'        => 'link_category',
				'show_option_all' => get_taxonomy( 'link_category' )->labels->all_items,
				'hide_empty'      => true,
				'hierarchical'    => 1,
				'show_count'      => 0,
				'orderby'         => 'name',
			);

			echo '<label class="screen-reader-text" for="cat_id">' . get_taxonomy( 'link_category' )->labels->filter_by_item . '</label>';

			wp_dropdown_categories( $dropdown_options );

			submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			?>
		</div>
		<?php
	}

	/**
	 * @return string[] Mảng tiêu đề cột được đánh khóa theo tên cột.
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => _x( 'Name', 'link name' ),
			'url'        => __( 'URL' ),
			'categories' => __( 'Categories' ),
			'rel'        => __( 'Relationship' ),
			'visible'    => __( 'Visible' ),
			'rating'     => __( 'Rating' ),
		);
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'name'    => array( 'name', false, _x( 'Name', 'link name' ), __( 'Table ordered by Name.' ), 'asc' ),
			'url'     => array( 'url', false, __( 'URL' ), __( 'Table ordered by URL.' ) ),
			'visible' => array( 'visible', false, __( 'Visible' ), __( 'Table ordered by Visibility.' ) ),
			'rating'  => array( 'rating', false, __( 'Rating' ), __( 'Table ordered by Rating.' ) ),
		);
	}

	/**
	 * Lấy tên cột chính mặc định.
	 *
	 * @since 4.3.0
	 *
	 * @return string Tên cột chính mặc định, trong trường hợp này là 'name'.
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * Xử lý đầu ra cột checkbox.
	 *
	 * @since 4.3.0
	 * @since 5.9.0 Đổi tên `$link` thành `$item` để khớp với lớp cha cho hỗ trợ tham số có tên PHP 8.
	 *
	 * @param object $item Đối tượng liên kết hiện tại.
	 */
	public function column_cb( $item ) {
		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$link = $item;

		?>
		<input type="checkbox" name="linkcheck[]" id="cb-select-<?php echo $link->link_id; ?>" value="<?php echo esc_attr( $link->link_id ); ?>" />
		<label for="cb-select-<?php echo $link->link_id; ?>">
			<span class="screen-reader-text">
			<?php
			/* translators: Hidden accessibility text. %s: Link name. */
			printf( __( 'Select %s' ), $link->link_name );
			?>
			</span>
		</label>
		<?php
	}

	/**
	 * Xử lý đầu ra cột tên liên kết.
	 *
	 * @since 4.3.0
	 *
	 * @param object $link Đối tượng liên kết hiện tại.
	 */
	public function column_name( $link ) {
		$edit_link = get_edit_bookmark_link( $link );
		printf(
			'<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>',
			$edit_link,
			/* translators: %s: Link name. */
			esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $link->link_name ) ),
			$link->link_name
		);
	}

	/**
	 * Xử lý đầu ra cột URL liên kết.
	 *
	 * @since 4.3.0
	 *
	 * @param object $link Đối tượng liên kết hiện tại.
	 */
	public function column_url( $link ) {
		$short_url = url_shorten( $link->link_url );
		echo "<a href='$link->link_url'>$short_url</a>";
	}

	/**
	 * Xử lý đầu ra cột chuyên mục liên kết.
	 *
	 * @since 4.3.0
	 *
	 * @global int $cat_id ID chuyên mục.
	 *
	 * @param object $link Đối tượng liên kết hiện tại.
	 */
	public function column_categories( $link ) {
		global $cat_id;

		$cat_names = array();
		foreach ( $link->link_category as $category ) {
			$cat = get_term( $category, 'link_category', OBJECT, 'display' );
			if ( is_wp_error( $cat ) ) {
				echo $cat->get_error_message();
			}
			$cat_name = $cat->name;
			if ( (int) $cat_id !== $category ) {
				$cat_name = "<a href='link-manager.php?cat_id=$category'>$cat_name</a>";
			}
			$cat_names[] = $cat_name;
		}
		echo implode( ', ', $cat_names );
	}

	/**
	 * Xử lý đầu ra cột quan hệ liên kết.
	 *
	 * @since 4.3.0
	 *
	 * @param object $link Đối tượng liên kết hiện tại.
	 */
	public function column_rel( $link ) {
		echo empty( $link->link_rel ) ? '<br />' : $link->link_rel;
	}

	/**
	 * Xử lý đầu ra cột hiển thị liên kết.
	 *
	 * @since 4.3.0
	 *
	 * @param object $link Đối tượng liên kết hiện tại.
	 */
	public function column_visible( $link ) {
		if ( 'Y' === $link->link_visible ) {
			_e( 'Yes' );
		} else {
			_e( 'No' );
		}
	}

	/**
	 * Xử lý đầu ra cột đánh giá liên kết.
	 *
	 * @since 4.3.0
	 *
	 * @param object $link Đối tượng liên kết hiện tại.
	 */
	public function column_rating( $link ) {
		echo $link->link_rating;
	}

	/**
	 * Xử lý đầu ra cột mặc định.
	 *
	 * @since 4.3.0
	 * @since 5.9.0 Đổi tên `$link` thành `$item` để khớp với lớp cha cho hỗ trợ tham số có tên PHP 8.
	 *
	 * @param object $item        Đối tượng liên kết.
	 * @param string $column_name Tên cột hiện tại.
	 */
	public function column_default( $item, $column_name ) {
		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$link = $item;

		/**
		 * Kích hoạt cho mỗi cột tùy chỉnh liên kết đã đăng ký.
		 *
		 * @since 2.1.0
		 *
		 * @param string $column_name Tên cột tùy chỉnh.
		 * @param int    $link_id     ID liên kết.
		 */
		do_action( 'manage_link_custom_column', $column_name, $link->link_id );
	}

	/**
	 * Tạo các hàng của bảng danh sách.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
		foreach ( $this->items as $link ) {
			$link                = sanitize_bookmark( $link );
			$link->link_name     = esc_attr( $link->link_name );
			$link->link_category = wp_get_link_cats( $link->link_id );
			?>
		<tr id="link-<?php echo $link->link_id; ?>">
			<?php $this->single_row_columns( $link ); ?>
		</tr>
			<?php
		}
	}

	/**
	 * Tạo và hiển thị các liên kết hành động hàng.
	 *
	 * @since 4.3.0
	 * @since 5.9.0 Đổi tên `$link` thành `$item` để khớp với lớp cha cho hỗ trợ tham số có tên PHP 8.
	 *
	 * @param object $item        Liên kết đang được thao tác.
	 * @param string $column_name Tên cột hiện tại.
	 * @param string $primary     Tên cột chính.
	 * @return string Đầu ra hành động hàng cho liên kết, hoặc chuỗi rỗng
	 *                nếu cột hiện tại không phải là cột chính.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		// Khôi phục tên mô tả cụ thể hơn để sử dụng trong phương thức này.
		$link = $item;

		$edit_link = get_edit_bookmark_link( $link );

		$actions           = array();
		$actions['edit']   = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
		$actions['delete'] = sprintf(
			'<a class="submitdelete" href="%s" onclick="return confirm( \'%s\' );">%s</a>',
			wp_nonce_url( "link.php?action=delete&amp;link_id=$link->link_id", 'delete-bookmark_' . $link->link_id ),
			/* translators: %s: Link name. */
			esc_js( sprintf( __( "You are about to delete this link '%s'\n  'Cancel' to stop, 'OK' to delete." ), $link->link_name ) ),
			__( 'Delete' )
		);

		return $this->row_actions( $actions );
	}
}
