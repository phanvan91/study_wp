<?php
/**
 * Các hàm quản trị đã ngừng sử dụng từ các phiên bản WordPress trước. Bạn không nên dùng
 * các hàm này mà hãy tìm các hàm thay thế. Các hàm sẽ bị loại bỏ
 * trong phiên bản sau.
 *
 * @package WordPress
 * @subpackage Deprecated
 */

/*
 * Các hàm ngừng sử dụng đến đây để chết.
 */

/**
 * Hàm đã ngừng sử dụng để bao gồm TinyMCE.
 *
 * @since 2.1.0
 * @deprecated 2.1.0 Sử dụng wp_editor()
 * @see wp_editor()
 */
function tinymce_include() {
	_deprecated_function( __FUNCTION__, '2.1.0', 'wp_editor()' );

	wp_tiny_mce();
}

/**
 * Hàm quản trị không còn sử dụng.
 *
 * @since 2.0.0
 * @deprecated 2.5.0
 *
 */
function documentation_link() {
	_deprecated_function( __FUNCTION__, '2.5.0' );
}

/**
 * Tính toán kích thước mới cho hình ảnh được thu nhỏ.
 *
 * @since 2.0.0
 * @deprecated 3.0.0 Sử dụng wp_constrain_dimensions()
 * @see wp_constrain_dimensions()
 *
 * @param int $width  Chiều rộng hiện tại của hình ảnh
 * @param int $height Chiều cao hiện tại của hình ảnh
 * @param int $wmax   Chiều rộng tối đa mong muốn
 * @param int $hmax   Chiều cao tối đa mong muốn
 * @return array Kích thước đã thu nhỏ (chiều rộng, chiều cao).
 */
function wp_shrink_dimensions( $width, $height, $wmax = 128, $hmax = 96 ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_constrain_dimensions()' );
	return wp_constrain_dimensions( $width, $height, $wmax, $hmax );
}

/**
 * Đã tính toán kích thước mới cho hình ảnh được thu nhỏ.
 *
 * @since 2.0.0
 * @deprecated 3.5.0 Sử dụng wp_constrain_dimensions()
 * @see wp_constrain_dimensions()
 *
 * @param int $width  Chiều rộng hiện tại của hình ảnh
 * @param int $height Chiều cao hiện tại của hình ảnh
 * @return array Kích thước đã thu nhỏ (chiều rộng, chiều cao).
 */
function get_udims( $width, $height ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_constrain_dimensions()' );
	return wp_constrain_dimensions( $width, $height, 128, 96 );
}

/**
 * Hàm kế thừa dùng để tạo điều khiển danh sách chọn chuyên mục.
 *
 * @since 0.71
 * @deprecated 2.6.0 Sử dụng wp_category_checklist()
 * @see wp_category_checklist()
 *
 * @global int $post_ID
 *
 * @param int   $default_category Không sử dụng.
 * @param int   $category_parent  Không sử dụng.
 * @param array $popular_ids      Không sử dụng.
 */
function dropdown_categories( $default_category = 0, $category_parent = 0, $popular_ids = array() ) {
	_deprecated_function( __FUNCTION__, '2.6.0', 'wp_category_checklist()' );
	global $post_ID;
	wp_category_checklist( $post_ID );
}

/**
 * Hàm kế thừa dùng để tạo điều khiển danh sách chọn chuyên mục liên kết.
 *
 * @since 2.1.0
 * @deprecated 2.6.0 Sử dụng wp_link_category_checklist()
 * @see wp_link_category_checklist()
 *
 * @global int $link_id
 *
 * @param int $default_link_category Không sử dụng.
 */
function dropdown_link_categories( $default_link_category = 0 ) {
	_deprecated_function( __FUNCTION__, '2.6.0', 'wp_link_category_checklist()' );
	global $link_id;
	wp_link_category_checklist( $link_id );
}

/**
 * Lấy đường dẫn hệ thống tệp thực tế đến tệp để chỉnh sửa trong trang quản trị.
 *
 * @since 1.5.0
 * @deprecated 2.9.0
 * @uses WP_CONTENT_DIR Đường dẫn hệ thống tệp đầy đủ đến thư mục wp-content.
 *
 * @param string $file Đường dẫn hệ thống tệp tương đối với thư mục wp-content.
 * @return string Đường dẫn hệ thống tệp đầy đủ để chỉnh sửa.
 */
function get_real_file_to_edit( $file ) {
	_deprecated_function( __FUNCTION__, '2.9.0' );

	return WP_CONTENT_DIR . $file;
}

/**
 * Hàm kế thừa dùng để tạo điều khiển danh sách thả xuống chuyên mục.
 *
 * @since 1.2.0
 * @deprecated 3.0.0 Sử dụng wp_dropdown_categories()
 * @see wp_dropdown_categories()
 *
 * @param int   $current_cat     Tùy chọn. ID của chuyên mục hiện tại. Mặc định 0.
 * @param int   $current_parent  Tùy chọn. ID chuyên mục cha hiện tại. Mặc định 0.
 * @param int   $category_parent Tùy chọn. ID cha để lấy các chuyên mục. Mặc định 0.
 * @param int   $level           Tùy chọn. Số cấp độ sâu để hiển thị. Mặc định 0.
 * @param array $categories      Tùy chọn. Các chuyên mục để bao gồm trong điều khiển. Mặc định 0.
 * @return void|false Void nếu thành công, false nếu không tìm thấy chuyên mục nào.
 */
function wp_dropdown_cats( $current_cat = 0, $current_parent = 0, $category_parent = 0, $level = 0, $categories = 0 ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_dropdown_categories()' );
	if (!$categories )
		$categories = get_categories( array('hide_empty' => 0) );

	if ( $categories ) {
		foreach ( $categories as $category ) {
			if ( $current_cat != $category->term_id && $category_parent == $category->parent) {
				$pad = str_repeat( '&#8211; ', $level );
				$category->name = esc_html( $category->name );
				echo "\n\t<option value='$category->term_id'";
				if ( $current_parent == $category->term_id )
					echo " selected='selected'";
				echo ">$pad$category->name</option>";
				wp_dropdown_cats( $current_cat, $current_parent, $category->term_id, $level +1, $categories );
			}
		}
	} else {
		return false;
	}
}

/**
 * Đăng ký một cài đặt và hàm callback lọc dữ liệu của nó.
 *
 * @since 2.7.0
 * @deprecated 3.0.0 Sử dụng register_setting()
 * @see register_setting()
 *
 * @param string   $option_group      Tên nhóm cài đặt. Nên tương ứng với tên khóa tùy chọn được phép.
 *                                    Tên khóa tùy chọn được phép mặc định bao gồm 'general', 'discussion', 'media',
 *                                    'reading', 'writing', và 'options'.
 * @param string   $option_name       Tên của tùy chọn để lọc và lưu.
 * @param callable $sanitize_callback Tùy chọn. Hàm callback lọc giá trị của tùy chọn.
 */
function add_option_update_handler( $option_group, $option_name, $sanitize_callback = '' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'register_setting()' );
	register_setting( $option_group, $option_name, $sanitize_callback );
}

/**
 * Hủy đăng ký một cài đặt.
 *
 * @since 2.7.0
 * @deprecated 3.0.0 Sử dụng unregister_setting()
 * @see unregister_setting()
 *
 * @param string   $option_group      Tên nhóm cài đặt được sử dụng khi đăng ký.
 * @param string   $option_name       Tên của tùy chọn cần hủy đăng ký.
 * @param callable $sanitize_callback Tùy chọn. Đã ngừng sử dụng.
 */
function remove_option_update_handler( $option_group, $option_name, $sanitize_callback = '' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'unregister_setting()' );
	unregister_setting( $option_group, $option_name, $sanitize_callback );
}

/**
 * Xác định ngôn ngữ sử dụng cho tô sáng cú pháp CodePress.
 *
 * @since 2.8.0
 * @deprecated 3.0.0
 *
 * @param string $filename
 */
function codepress_get_lang( $filename ) {
	_deprecated_function( __FUNCTION__, '3.0.0' );
}

/**
 * Thêm JavaScript cần thiết để CodePress hoạt động trên trình soạn thảo tệp giao diện/plugin.
 *
 * @since 2.8.0
 * @deprecated 3.0.0
 */
function codepress_footer_js() {
	_deprecated_function( __FUNCTION__, '3.0.0' );
}

/**
 * Xác định có nên sử dụng CodePress hay không.
 *
 * @since 2.8.0
 * @deprecated 3.0.0
 */
function use_codepress() {
	_deprecated_function( __FUNCTION__, '3.0.0' );
}

/**
 * Lấy tất cả ID người dùng.
 *
 * @deprecated 3.1.0 Sử dụng get_users()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @return array Danh sách ID người dùng.
 */
function get_author_user_ids() {
	_deprecated_function( __FUNCTION__, '3.1.0', 'get_users()' );

	global $wpdb;
	if ( !is_multisite() )
		$level_key = $wpdb->get_blog_prefix() . 'user_level';
	else
		$level_key = $wpdb->get_blog_prefix() . 'capabilities'; // Quản trị viên trang WPMU không có user_levels.

	return $wpdb->get_col( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value != '0'", $level_key) );
}

/**
 * Lấy danh sách người dùng tác giả có thể chỉnh sửa bài viết.
 *
 * @deprecated 3.1.0 Sử dụng get_users()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $user_id ID người dùng.
 * @return array|false Danh sách các tác giả có thể chỉnh sửa. False nếu không có người dùng nào có thể chỉnh sửa.
 */
function get_editable_authors( $user_id ) {
	_deprecated_function( __FUNCTION__, '3.1.0', 'get_users()' );

	global $wpdb;

	$editable = get_editable_user_ids( $user_id );

	if ( !$editable ) {
		return false;
	} else {
		$editable = join(',', $editable);
		$authors = $wpdb->get_results( "SELECT * FROM $wpdb->users WHERE ID IN ($editable) ORDER BY display_name" );
	}

	return apply_filters('get_editable_authors', $authors);
}

/**
 * Lấy ID của bất kỳ người dùng nào có thể chỉnh sửa bài viết.
 *
 * @deprecated 3.1.0 Sử dụng get_users()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int  $user_id       ID người dùng.
 * @param bool $exclude_zeros Tùy chọn. Có loại trừ giá trị không hay không. Mặc định true.
 * @return array Mảng ID người dùng có thể chỉnh sửa, mảng rỗng nếu không có.
 */
function get_editable_user_ids( $user_id, $exclude_zeros = true, $post_type = 'post' ) {
	_deprecated_function( __FUNCTION__, '3.1.0', 'get_users()' );

	global $wpdb;

	if ( ! $user = get_userdata( $user_id ) )
		return array();
	$post_type_obj = get_post_type_object($post_type);

	if ( ! $user->has_cap($post_type_obj->cap->edit_others_posts) ) {
		if ( $user->has_cap($post_type_obj->cap->edit_posts) || ! $exclude_zeros )
			return array($user->ID);
		else
			return array();
	}

	if ( !is_multisite() )
		$level_key = $wpdb->get_blog_prefix() . 'user_level';
	else
		$level_key = $wpdb->get_blog_prefix() . 'capabilities'; // Quản trị viên trang WPMU không có user_levels.

	$query = $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s", $level_key);
	if ( $exclude_zeros )
		$query .= " AND meta_value != '0'";

	return $wpdb->get_col( $query );
}

/**
 * Lấy tất cả người dùng không phải là tác giả.
 *
 * @deprecated 3.1.0 Sử dụng get_users()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 */
function get_nonauthor_user_ids() {
	_deprecated_function( __FUNCTION__, '3.1.0', 'get_users()' );

	global $wpdb;

	if ( !is_multisite() )
		$level_key = $wpdb->get_blog_prefix() . 'user_level';
	else
		$level_key = $wpdb->get_blog_prefix() . 'capabilities'; // Quản trị viên trang WPMU không có user_levels.

	return $wpdb->get_col( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = '0'", $level_key) );
}

if ( ! class_exists( 'WP_User_Search', false ) ) :
/**
 * Lớp Tìm kiếm Người dùng WordPress.
 *
 * @since 2.1.0
 * @deprecated 3.1.0 Sử dụng WP_User_Query
 */
class WP_User_Search {

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var mixed
	 */
	var $results;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var string
	 */
	var $search_term;

	/**
	 * Số trang.
	 *
	 * @since 2.1.0
	 * @access private
	 * @var int
	 */
	var $page;

	/**
	 * Tên vai trò mà người dùng có.
	 *
	 * @since 2.5.0
	 * @access private
	 * @var string
	 */
	var $role;

	/**
	 * Số trang thô.
	 *
	 * @since 2.1.0
	 * @access private
	 * @var int|bool
	 */
	var $raw_page;

	/**
	 * Số lượng người dùng hiển thị mỗi trang.
	 *
	 * @since 2.1.0
	 * @access public
	 * @var int
	 */
	var $users_per_page = 50;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var int
	 */
	var $first_user;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var int
	 */
	var $last_user;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var string
	 */
	var $query_limit;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 3.0.0
	 * @access private
	 * @var string
	 */
	var $query_orderby;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 3.0.0
	 * @access private
	 * @var string
	 */
	var $query_from;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 3.0.0
	 * @access private
	 * @var string
	 */
	var $query_where;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var int
	 */
	var $total_users_for_query = 0;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var bool
	 */
	var $too_many_total_users = false;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.1.0
	 * @access private
	 * @var WP_Error
	 */
	var $search_errors;

	/**
	 * {@internal Thiếu mô tả}}
	 *
	 * @since 2.7.0
	 * @access private
	 * @var string
	 */
	var $paging_text;

	/**
	 * Hàm khởi tạo PHP5 - Thiết lập các thuộc tính của đối tượng.
	 *
	 * @since 2.1.0
	 *
	 * @param string $search_term Chuỗi từ khóa tìm kiếm.
	 * @param int    $page        Tùy chọn. ID trang.
	 * @param string $role        Tên vai trò.
	 * @return WP_User_Search
	 */
	function __construct( $search_term = '', $page = '', $role = '' ) {
		_deprecated_class( 'WP_User_Search', '3.1.0', 'WP_User_Query' );

		$this->search_term = wp_unslash( $search_term );
		$this->raw_page = ( '' == $page ) ? false : (int) $page;
		$this->page = ( '' == $page ) ? 1 : (int) $page;
		$this->role = $role;

		$this->prepare_query();
		$this->query();
		$this->do_paging();
	}

	/**
	 * Hàm khởi tạo PHP4 - Thiết lập các thuộc tính của đối tượng.
	 *
	 * @since 2.1.0
	 *
	 * @param string $search_term Chuỗi từ khóa tìm kiếm.
	 * @param int    $page        Tùy chọn. ID trang.
	 * @param string $role        Tên vai trò.
	 * @return WP_User_Search
	 */
	public function WP_User_Search( $search_term = '', $page = '', $role = '' ) {
		_deprecated_constructor( 'WP_User_Search', '3.1.0', get_class( $this ) );
		self::__construct( $search_term, $page, $role );
	}

	/**
	 * Chuẩn bị truy vấn tìm kiếm người dùng (kế thừa).
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 */
	public function prepare_query() {
		global $wpdb;
		$this->first_user = ($this->page - 1) * $this->users_per_page;

		$this->query_limit = $wpdb->prepare(" LIMIT %d, %d", $this->first_user, $this->users_per_page);
		$this->query_orderby = ' ORDER BY user_login';

		$search_sql = '';
		if ( $this->search_term ) {
			$searches = array();
			$search_sql = 'AND (';
			foreach ( array('user_login', 'user_nicename', 'user_email', 'user_url', 'display_name') as $col )
				$searches[] = $wpdb->prepare( $col . ' LIKE %s', '%' . like_escape($this->search_term) . '%' );
			$search_sql .= implode(' OR ', $searches);
			$search_sql .= ')';
		}

		$this->query_from = " FROM $wpdb->users";
		$this->query_where = " WHERE 1=1 $search_sql";

		if ( $this->role ) {
			$this->query_from .= " INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id";
			$this->query_where .= $wpdb->prepare(" AND $wpdb->usermeta.meta_key = '{$wpdb->prefix}capabilities' AND $wpdb->usermeta.meta_value LIKE %s", '%' . $this->role . '%');
		} elseif ( is_multisite() ) {
			$level_key = $wpdb->prefix . 'capabilities'; // Quản trị viên trang WPMU không có user_levels.
			$this->query_from .= ", $wpdb->usermeta";
			$this->query_where .= " AND $wpdb->users.ID = $wpdb->usermeta.user_id AND meta_key = '{$level_key}'";
		}

		do_action_ref_array( 'pre_user_search', array( &$this ) );
	}

	/**
	 * Thực thi truy vấn tìm kiếm người dùng.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
	 */
	public function query() {
		global $wpdb;

		$this->results = $wpdb->get_col("SELECT DISTINCT($wpdb->users.ID)" . $this->query_from . $this->query_where . $this->query_orderby . $this->query_limit);

		if ( $this->results )
			$this->total_users_for_query = $wpdb->get_var("SELECT COUNT(DISTINCT($wpdb->users.ID))" . $this->query_from . $this->query_where); // Không giới hạn.
		else
			$this->search_errors = new WP_Error('no_matching_users_found', __('No users found.'));
	}

	/**
	 * Chuẩn bị các biến để sử dụng trong mẫu.
	 *
	 * @since 2.1.0
	 * @access public
	 */
	function prepare_vars_for_template_usage() {}

	/**
	 * Xử lý phân trang cho truy vấn tìm kiếm người dùng.
	 *
	 * @since 2.1.0
	 * @access public
	 */
	public function do_paging() {
		if ( $this->total_users_for_query > $this->users_per_page ) { // Phải phân trang kết quả.
			$args = array();
			if ( ! empty($this->search_term) )
				$args['usersearch'] = urlencode($this->search_term);
			if ( ! empty($this->role) )
				$args['role'] = urlencode($this->role);

			$this->paging_text = paginate_links( array(
				'total' => ceil($this->total_users_for_query / $this->users_per_page),
				'current' => $this->page,
				'base' => 'users.php?%_%',
				'format' => 'userspage=%#%',
				'add_args' => $args
			) );
			if ( $this->paging_text ) {
				$this->paging_text = sprintf(
					/* translators: 1: Starting number of users on the current page, 2: Ending number of users, 3: Total number of users. */
					'<span class="displaying-num">' . __( 'Displaying %1$s&#8211;%2$s of %3$s' ) . '</span>%s',
					number_format_i18n( ( $this->page - 1 ) * $this->users_per_page + 1 ),
					number_format_i18n( min( $this->page * $this->users_per_page, $this->total_users_for_query ) ),
					number_format_i18n( $this->total_users_for_query ),
					$this->paging_text
				);
			}
		}
	}

	/**
	 * Lấy kết quả truy vấn tìm kiếm người dùng.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_results() {
		return (array) $this->results;
	}

	/**
	 * Hiển thị văn bản phân trang.
	 *
	 * @see do_paging() Xây dựng văn bản phân trang.
	 *
	 * @since 2.1.0
	 * @access public
	 */
	function page_links() {
		echo $this->paging_text;
	}

	/**
	 * Kiểm tra phân trang có được bật hay không.
	 *
	 * @see do_paging() Xây dựng văn bản phân trang.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return bool
	 */
	function results_are_paged() {
		if ( $this->paging_text )
			return true;
		return false;
	}

	/**
	 * Kiểm tra có từ khóa tìm kiếm hay không.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return bool
	 */
	function is_search() {
		if ( $this->search_term )
			return true;
		return false;
	}
}
endif;

/**
 * Lấy các bài viết có thể chỉnh sửa từ người dùng khác.
 *
 * @since 2.3.0
 * @deprecated 3.1.0 Sử dụng get_posts()
 * @see get_posts()
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int    $user_id ID người dùng không lấy bài viết từ đó.
 * @param string $type    Tùy chọn. Loại bài viết cần lấy. Chấp nhận 'draft', 'pending' hoặc 'any' (tất cả).
 *                        Mặc định 'any'.
 * @return array Danh sách bài viết từ người dùng khác.
 */
function get_others_unpublished_posts( $user_id, $type = 'any' ) {
	_deprecated_function( __FUNCTION__, '3.1.0' );

	global $wpdb;

	$editable = get_editable_user_ids( $user_id );

	if ( in_array($type, array('draft', 'pending')) )
		$type_sql = " post_status = '$type' ";
	else
		$type_sql = " ( post_status = 'draft' OR post_status = 'pending' ) ";

	$dir = ( 'pending' == $type ) ? 'ASC' : 'DESC';

	if ( !$editable ) {
		$other_unpubs = '';
	} else {
		$editable = join(',', $editable);
		$other_unpubs = $wpdb->get_results( $wpdb->prepare("SELECT ID, post_title, post_author FROM $wpdb->posts WHERE post_type = 'post' AND $type_sql AND post_author IN ($editable) AND post_author != %d ORDER BY post_modified $dir", $user_id) );
	}

	return apply_filters('get_others_drafts', $other_unpubs);
}

/**
 * Lấy bản nháp từ người dùng khác.
 *
 * @deprecated 3.1.0 Sử dụng get_posts()
 * @see get_posts()
 *
 * @param int $user_id ID người dùng.
 * @return array Danh sách bản nháp từ người dùng khác.
 */
function get_others_drafts($user_id) {
	_deprecated_function( __FUNCTION__, '3.1.0' );

	return get_others_unpublished_posts($user_id, 'draft');
}

/**
 * Lấy các bài viết đang chờ duyệt từ người dùng khác.
 *
 * @deprecated 3.1.0 Sử dụng get_posts()
 * @see get_posts()
 *
 * @param int $user_id ID người dùng.
 * @return array Danh sách bài viết có trạng thái chờ duyệt từ người dùng khác.
 */
function get_others_pending($user_id) {
	_deprecated_function( __FUNCTION__, '3.1.0' );

	return get_others_unpublished_posts($user_id, 'pending');
}

/**
 * Xuất widget bảng điều khiển QuickPress.
 *
 * @since 3.0.0
 * @deprecated 3.2.0 Sử dụng wp_dashboard_quick_press()
 * @see wp_dashboard_quick_press()
 */
function wp_dashboard_quick_press_output() {
	_deprecated_function( __FUNCTION__, '3.2.0', 'wp_dashboard_quick_press()' );
	wp_dashboard_quick_press();
}

/**
 * Xuất trình soạn thảo TinyMCE.
 *
 * @since 2.7.0
 * @deprecated 3.3.0 Sử dụng wp_editor()
 * @see wp_editor()
 */
function wp_tiny_mce( $teeny = false, $settings = false ) {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_editor()' );

	static $num = 1;

	if ( ! class_exists( '_WP_Editors', false ) )
		require_once ABSPATH . WPINC . '/class-wp-editor.php';

	$editor_id = 'content' . $num++;

	$set = array(
		'teeny' => $teeny,
		'tinymce' => $settings ? $settings : true,
		'quicktags' => false
	);

	$set = _WP_Editors::parse_settings($editor_id, $set);
	_WP_Editors::editor_settings($editor_id, $set);
}

/**
 * Tải trước các hộp thoại TinyMCE.
 *
 * @deprecated 3.3.0 Sử dụng wp_editor()
 * @see wp_editor()
 */
function wp_preload_dialogs() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_editor()' );
}

/**
 * In JavaScript của trình soạn thảo TinyMCE.
 *
 * @deprecated 3.3.0 Sử dụng wp_editor()
 * @see wp_editor()
 */
function wp_print_editor_js() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_editor()' );
}

/**
 * Xử lý quicktags.
 *
 * @deprecated 3.3.0 Sử dụng wp_editor()
 * @see wp_editor()
 */
function wp_quicktags() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_editor()' );
}

/**
 * Trả về các tùy chọn bố cục màn hình.
 *
 * @since 2.8.0
 * @deprecated 3.3.0 WP_Screen::render_screen_layout()
 * @see WP_Screen::render_screen_layout()
 */
function screen_layout( $screen ) {
	_deprecated_function( __FUNCTION__, '3.3.0', '$current_screen->render_screen_layout()' );

	$current_screen = get_current_screen();

	if ( ! $current_screen )
		return '';

	ob_start();
	$current_screen->render_screen_layout();
	return ob_get_clean();
}

/**
 * Trả về các tùy chọn số mục mỗi trang của màn hình.
 *
 * @since 2.8.0
 * @deprecated 3.3.0 Sử dụng WP_Screen::render_per_page_options()
 * @see WP_Screen::render_per_page_options()
 */
function screen_options( $screen ) {
	_deprecated_function( __FUNCTION__, '3.3.0', '$current_screen->render_per_page_options()' );

	$current_screen = get_current_screen();

	if ( ! $current_screen )
		return '';

	ob_start();
	$current_screen->render_per_page_options();
	return ob_get_clean();
}

/**
 * Hiển thị trợ giúp của màn hình.
 *
 * @since 2.7.0
 * @deprecated 3.3.0 Sử dụng WP_Screen::render_screen_meta()
 * @see WP_Screen::render_screen_meta()
 */
function screen_meta( $screen ) {
	$current_screen = get_current_screen();
	$current_screen->render_screen_meta();
}

/**
 * Hành động yêu thích đã ngừng sử dụng trong phiên bản 3.2. Sử dụng thanh quản trị thay thế.
 *
 * @since 2.7.0
 * @deprecated 3.2.0 Sử dụng WP_Admin_Bar
 * @see WP_Admin_Bar
 */
function favorite_actions() {
	_deprecated_function( __FUNCTION__, '3.2.0', 'WP_Admin_Bar' );
}

/**
 * Xử lý tải lên hình ảnh.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_upload_handler()
 * @see wp_media_upload_handler()
 *
 * @return null|string
 */
function media_upload_image() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_media_upload_handler()' );
	return wp_media_upload_handler();
}

/**
 * Xử lý tải lên tệp âm thanh.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_upload_handler()
 * @see wp_media_upload_handler()
 *
 * @return null|string
 */
function media_upload_audio() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_media_upload_handler()' );
	return wp_media_upload_handler();
}

/**
 * Xử lý tải lên tệp video.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_upload_handler()
 * @see wp_media_upload_handler()
 *
 * @return null|string
 */
function media_upload_video() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_media_upload_handler()' );
	return wp_media_upload_handler();
}

/**
 * Xử lý tải lên tệp chung.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_upload_handler()
 * @see wp_media_upload_handler()
 *
 * @return null|string
 */
function media_upload_file() {
	_deprecated_function( __FUNCTION__, '3.3.0', 'wp_media_upload_handler()' );
	return wp_media_upload_handler();
}

/**
 * Xử lý lấy biểu mẫu chèn từ URL cho hình ảnh.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_insert_url_form()
 * @see wp_media_insert_url_form()
 *
 * @return string
 */
function type_url_form_image() {
	_deprecated_function( __FUNCTION__, '3.3.0', "wp_media_insert_url_form('image')" );
	return wp_media_insert_url_form( 'image' );
}

/**
 * Xử lý lấy biểu mẫu chèn từ URL cho tệp âm thanh.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_insert_url_form()
 * @see wp_media_insert_url_form()
 *
 * @return string
 */
function type_url_form_audio() {
	_deprecated_function( __FUNCTION__, '3.3.0', "wp_media_insert_url_form('audio')" );
	return wp_media_insert_url_form( 'audio' );
}

/**
 * Xử lý lấy biểu mẫu chèn từ URL cho tệp video.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_insert_url_form()
 * @see wp_media_insert_url_form()
 *
 * @return string
 */
function type_url_form_video() {
	_deprecated_function( __FUNCTION__, '3.3.0', "wp_media_insert_url_form('video')" );
	return wp_media_insert_url_form( 'video' );
}

/**
 * Xử lý lấy biểu mẫu chèn từ URL cho tệp chung.
 *
 * @deprecated 3.3.0 Sử dụng wp_media_insert_url_form()
 * @see wp_media_insert_url_form()
 *
 * @return string
 */
function type_url_form_file() {
	_deprecated_function( __FUNCTION__, '3.3.0', "wp_media_insert_url_form('file')" );
	return wp_media_insert_url_form( 'file' );
}

/**
 * Thêm văn bản trợ giúp theo ngữ cảnh cho một trang.
 *
 * Tạo tab trợ giúp 'Tổng quan'.
 *
 * @since 2.7.0
 * @deprecated 3.3.0 Sử dụng WP_Screen::add_help_tab()
 * @see WP_Screen::add_help_tab()
 *
 * @param string $screen Handle cho màn hình cần thêm trợ giúp. Thường là
 *                       tên hook được trả về bởi các hàm `add_*_page()`.
 * @param string $help   Nội dung của tab trợ giúp 'Tổng quan'.
 */
function add_contextual_help( $screen, $help ) {
	_deprecated_function( __FUNCTION__, '3.3.0', 'get_current_screen()->add_help_tab()' );

	if ( is_string( $screen ) )
		$screen = convert_to_screen( $screen );

	WP_Screen::add_old_compat_help( $screen, $help );
}

/**
 * Lấy các giao diện được phép cho trang hiện tại.
 *
 * @since 3.0.0
 * @deprecated 3.4.0 Sử dụng wp_get_themes()
 * @see wp_get_themes()
 *
 * @return WP_Theme[] Mảng các đối tượng WP_Theme được đánh chỉ mục theo tên.
 */
function get_allowed_themes() {
	_deprecated_function( __FUNCTION__, '3.4.0', "wp_get_themes( array( 'allowed' => true ) )" );

	$themes = wp_get_themes( array( 'allowed' => true ) );

	$wp_themes = array();
	foreach ( $themes as $theme ) {
		$wp_themes[ $theme->get('Name') ] = $theme;
	}

	return $wp_themes;
}

/**
 * Lấy danh sách các giao diện bị lỗi.
 *
 * @since 1.5.0
 * @deprecated 3.4.0 Sử dụng wp_get_themes()
 * @see wp_get_themes()
 *
 * @return array
 */
function get_broken_themes() {
	_deprecated_function( __FUNCTION__, '3.4.0', "wp_get_themes( array( 'errors' => true )" );

	$themes = wp_get_themes( array( 'errors' => true ) );
	$broken = array();
	foreach ( $themes as $theme ) {
		$name = $theme->get('Name');
		$broken[ $name ] = array(
			'Name' => $name,
			'Title' => $name,
			'Description' => $theme->errors()->get_error_message(),
		);
	}
	return $broken;
}

/**
 * Lấy thông tin về giao diện đang kích hoạt hiện tại.
 *
 * @since 2.0.0
 * @deprecated 3.4.0 Sử dụng wp_get_theme()
 * @see wp_get_theme()
 *
 * @return WP_Theme
 */
function current_theme_info() {
	_deprecated_function( __FUNCTION__, '3.4.0', 'wp_get_theme()' );

	return wp_get_theme();
}

/**
 * Trước đây dùng để hiển thị nút 'Chèn vào Bài viết'.
 *
 * Hiện đã ngừng sử dụng và chỉ còn là hàm rỗng.
 *
 * @deprecated 3.5.0
 */
function _insert_into_post_button( $type ) {
	_deprecated_function( __FUNCTION__, '3.5.0' );
}

/**
 * Trước đây dùng để hiển thị nút phương tiện.
 *
 * Hiện đã ngừng sử dụng và chỉ còn là hàm rỗng.
 *
 * @deprecated 3.5.0
 */
function _media_button($title, $icon, $type, $id) {
	_deprecated_function( __FUNCTION__, '3.5.0' );
}

/**
 * Lấy bài viết hiện có và định dạng nó để chỉnh sửa.
 *
 * @since 2.0.0
 * @deprecated 3.5.0 Sử dụng get_post()
 * @see get_post()
 *
 * @param int $id
 * @return WP_Post
 */
function get_post_to_edit( $id ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'get_post()' );

	return get_post( $id, OBJECT, 'edit' );
}

/**
 * Lấy thông tin trang mặc định để sử dụng.
 *
 * @since 2.5.0
 * @deprecated 3.5.0 Sử dụng get_default_post_to_edit()
 * @see get_default_post_to_edit()
 *
 * @return WP_Post Đối tượng bài viết chứa tất cả dữ liệu bài viết mặc định làm thuộc tính
 */
function get_default_page_to_edit() {
	_deprecated_function( __FUNCTION__, '3.5.0', "get_default_post_to_edit( 'page' )" );

	$page = get_default_post_to_edit();
	$page->post_type = 'page';
	return $page;
}

/**
 * Trước đây dùng để tạo ảnh thu nhỏ từ hình ảnh với kích thước cạnh tối đa cho trước.
 *
 * @since 1.2.0
 * @deprecated 3.5.0 Sử dụng image_resize()
 * @see image_resize()
 *
 * @param mixed $file       Tên tệp hình ảnh gốc, hoặc ID đính kèm.
 * @param int   $max_side   Chiều dài tối đa của một cạnh cho ảnh thu nhỏ.
 * @param mixed $deprecated Không bao giờ sử dụng.
 * @return string Đường dẫn ảnh thu nhỏ nếu thành công, chuỗi lỗi nếu thất bại.
 */
function wp_create_thumbnail( $file, $max_side, $deprecated = '' ) {
	_deprecated_function( __FUNCTION__, '3.5.0', 'image_resize()' );
	return apply_filters( 'wp_create_thumbnail', image_resize( $file, $max_side, $max_side ) );
}

/**
 * Trước đây dùng để hiển thị hộp meta cho vị trí giao diện menu điều hướng.
 *
 * Ngừng sử dụng để thay bằng tab 'Quản lý Vị trí' được thêm vào màn hình quản lý menu điều hướng.
 *
 * @since 3.0.0
 * @deprecated 3.6.0
 */
function wp_nav_menu_locations_meta_box() {
	_deprecated_function( __FUNCTION__, '3.6.0' );
}

/**
 * Trước đây dùng để khởi chạy Trình cập nhật lõi.
 *
 * Ngừng sử dụng để thay bằng việc khởi tạo trực tiếp một instance Core_Upgrader,
 * và gọi phương thức 'upgrade'.
 *
 * @since 2.7.0
 * @deprecated 3.7.0 Sử dụng Core_Upgrader
 * @see Core_Upgrader
 */
function wp_update_core($current, $feedback = '') {
	_deprecated_function( __FUNCTION__, '3.7.0', 'new Core_Upgrader();' );

	if ( !empty($feedback) )
		add_filter('update_feedback', $feedback);

	require ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$upgrader = new Core_Upgrader();
	return $upgrader->upgrade($current);

}

/**
 * Trước đây dùng để khởi chạy Trình cập nhật Plugin.
 *
 * Ngừng sử dụng để thay bằng việc khởi tạo trực tiếp một instance Plugin_Upgrader,
 * và gọi phương thức 'upgrade'.
 * Không sử dụng từ 2.8.0.
 *
 * @since 2.5.0
 * @deprecated 3.7.0 Sử dụng Plugin_Upgrader
 * @see Plugin_Upgrader
 */
function wp_update_plugin($plugin, $feedback = '') {
	_deprecated_function( __FUNCTION__, '3.7.0', 'new Plugin_Upgrader();' );

	if ( !empty($feedback) )
		add_filter('update_feedback', $feedback);

	require ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$upgrader = new Plugin_Upgrader();
	return $upgrader->upgrade($plugin);
}

/**
 * Trước đây dùng để khởi chạy Trình cập nhật Giao diện.
 *
 * Ngừng sử dụng để thay bằng việc khởi tạo trực tiếp một instance Theme_Upgrader,
 * và gọi phương thức 'upgrade'.
 * Không sử dụng từ 2.8.0.
 *
 * @since 2.7.0
 * @deprecated 3.7.0 Sử dụng Theme_Upgrader
 * @see Theme_Upgrader
 */
function wp_update_theme($theme, $feedback = '') {
	_deprecated_function( __FUNCTION__, '3.7.0', 'new Theme_Upgrader();' );

	if ( !empty($feedback) )
		add_filter('update_feedback', $feedback);

	require ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$upgrader = new Theme_Upgrader();
	return $upgrader->upgrade($theme);
}

/**
 * Trước đây dùng để hiển thị liên kết đính kèm. Hiện đã ngừng sử dụng và chỉ còn là hàm rỗng.
 *
 * @since 2.0.0
 * @deprecated 3.7.0
 *
 * @param int|bool $id
 */
function the_attachment_links( $id = false ) {
	_deprecated_function( __FUNCTION__, '3.7.0' );
}

/**
 * Hiển thị biểu tượng màn hình.
 *
 * @since 2.7.0
 * @deprecated 3.8.0
 */
function screen_icon() {
	_deprecated_function( __FUNCTION__, '3.8.0' );
	echo get_screen_icon();
}

/**
 * Lấy biểu tượng màn hình (không còn sử dụng từ 3.8+).
 *
 * @since 3.2.0
 * @deprecated 3.8.0
 *
 * @return string Nhận xét HTML giải thích rằng biểu tượng không còn được sử dụng.
 */
function get_screen_icon() {
	_deprecated_function( __FUNCTION__, '3.8.0' );
	return '<!-- Screen icons are no longer used as of WordPress 3.8. -->';
}

/**
 * Điều khiển widget bảng điều khiển đã ngừng sử dụng.
 *
 * @since 2.5.0
 * @deprecated 3.8.0
 */
function wp_dashboard_incoming_links_output() {}

/**
 * Đầu ra phụ bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_secondary_output() {}

/**
 * Điều khiển widget bảng điều khiển đã ngừng sử dụng.
 *
 * @since 2.7.0
 * @deprecated 3.8.0
 */
function wp_dashboard_incoming_links() {}

/**
 * Điều khiển liên kết đến bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_incoming_links_control() {}

/**
 * Điều khiển plugin bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_plugins() {}

/**
 * Điều khiển chính bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_primary_control() {}

/**
 * Điều khiển bình luận gần đây bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_recent_comments_control() {}

/**
 * Phần phụ bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_secondary() {}

/**
 * Điều khiển phụ bảng điều khiển đã ngừng sử dụng.
 *
 * @deprecated 3.8.0
 */
function wp_dashboard_secondary_control() {}

/**
 * Hiển thị văn bản plugin cho widget tin tức WordPress.
 *
 * @since 2.5.0
 * @deprecated 4.8.0
 *
 * @param string $rss  URL nguồn cấp RSS.
 * @param array  $args Mảng tham số cho nguồn cấp RSS này.
 */
function wp_dashboard_plugins_output( $rss, $args = array() ) {
	_deprecated_function( __FUNCTION__, '4.8.0' );

	// Nguồn cấp plugin cùng liên kết để cài đặt chúng.
	$popular = fetch_feed( $args['url']['popular'] );

	if ( false === $plugin_slugs = get_transient( 'plugin_slugs' ) ) {
		$plugin_slugs = array_keys( get_plugins() );
		set_transient( 'plugin_slugs', $plugin_slugs, DAY_IN_SECONDS );
	}

	echo '<ul>';

	foreach ( array( $popular ) as $feed ) {
		if ( is_wp_error( $feed ) || ! $feed->get_item_quantity() )
			continue;

		$items = $feed->get_items(0, 5);

		// Chọn một plugin ngẫu nhiên chưa được cài đặt.
		while ( true ) {
			// Hủy vòng lặp foreach này nếu không còn plugin nào thuộc loại này.
			if ( 0 === count($items) )
				continue 2;

			$item_key = array_rand($items);
			$item = $items[$item_key];

			list($link, $frag) = explode( '#', $item->get_link() );

			$link = esc_url($link);
			if ( preg_match( '|/([^/]+?)/?$|', $link, $matches ) )
				$slug = $matches[1];
			else {
				unset( $items[$item_key] );
				continue;
			}

			// Slug của plugin ngẫu nhiên này đã được cài đặt chưa? Nếu rồi, thử lại.
			reset( $plugin_slugs );
			foreach ( $plugin_slugs as $plugin_slug ) {
				if ( str_starts_with( $plugin_slug, $slug ) ) {
					unset( $items[$item_key] );
					continue 2;
				}
			}

			// Nếu đến được điểm này, thì plugin ngẫu nhiên chưa được cài đặt và chúng ta có thể dừng vòng while().
			break;
		}

		// Loại bỏ một số mô tả plugin được định dạng sai thường gặp.
		while ( ( null !== $item_key = array_rand($items) ) && str_contains( $items[$item_key]->get_description(), 'Plugin Name:' ) )
			unset($items[$item_key]);

		if ( !isset($items[$item_key]) )
			continue;

		$raw_title = $item->get_title();

		$ilink = wp_nonce_url('plugin-install.php?tab=plugin-information&plugin=' . $slug, 'install-plugin_' . $slug) . '&amp;TB_iframe=true&amp;width=600&amp;height=800';
		echo '<li class="dashboard-news-plugin"><span>' . __( 'Popular Plugin' ) . ':</span> ' . esc_html( $raw_title ) .
			'&nbsp;<a href="' . $ilink . '" class="thickbox open-plugin-details-modal" aria-label="' .
			/* translators: %s: Plugin name. */
			esc_attr( sprintf( _x( 'Install %s', 'plugin' ), $raw_title ) ) . '">(' . __( 'Install' ) . ')</a></li>';

		$feed->__destruct();
		unset( $feed );
	}

	echo '</ul>';
}

/**
 * Trước đây dùng để di chuyển bài viết con sang bài viết cha mới.
 *
 * @since 2.3.0
 * @deprecated 3.9.0
 * @access private
 *
 * @param int $old_ID
 * @param int $new_ID
 */
function _relocate_children( $old_ID, $new_ID ) {
	_deprecated_function( __FUNCTION__, '3.9.0' );
}

/**
 * Thêm trang menu cấp cao nhất trong phần 'đối tượng'.
 *
 * Hàm này nhận một quyền hạn sẽ được dùng để xác định xem
 * trang có được bao gồm trong menu hay không.
 *
 * Hàm được gắn hook để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có quyền hạn cần thiết.
 *
 * @since 2.7.0
 *
 * @deprecated 4.5.0 Sử dụng add_menu_page()
 * @see add_menu_page()
 * @global int $_wp_last_object_menu
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ tiêu đề của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Quyền hạn cần thiết để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param string   $icon_url   Tùy chọn. URL đến biểu tượng sử dụng cho menu này.
 * @return string Hook_suffix của trang kết quả.
 */
function add_object_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '') {
	_deprecated_function( __FUNCTION__, '4.5.0', 'add_menu_page()' );

	global $_wp_last_object_menu;

	$_wp_last_object_menu++;

	return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url, $_wp_last_object_menu);
}

/**
 * Thêm trang menu cấp cao nhất trong phần 'tiện ích'.
 *
 * Hàm này nhận một quyền hạn sẽ được dùng để xác định xem
 * trang có được bao gồm trong menu hay không.
 *
 * Hàm được gắn hook để xử lý đầu ra của trang cũng phải kiểm tra
 * rằng người dùng có quyền hạn cần thiết.
 *
 * @since 2.7.0
 *
 * @deprecated 4.5.0 Sử dụng add_menu_page()
 * @see add_menu_page()
 * @global int $_wp_last_utility_menu
 *
 * @param string   $page_title Văn bản hiển thị trong thẻ tiêu đề của trang khi menu được chọn.
 * @param string   $menu_title Văn bản sử dụng cho menu.
 * @param string   $capability Quyền hạn cần thiết để menu này hiển thị cho người dùng.
 * @param string   $menu_slug  Tên slug để tham chiếu menu này (nên là duy nhất cho menu này).
 * @param callable $callback   Tùy chọn. Hàm được gọi để xuất nội dung cho trang này.
 * @param string   $icon_url   Tùy chọn. URL đến biểu tượng sử dụng cho menu này.
 * @return string Hook_suffix của trang kết quả.
 */
function add_utility_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '') {
	_deprecated_function( __FUNCTION__, '4.5.0', 'add_menu_page()' );

	global $_wp_last_utility_menu;

	$_wp_last_utility_menu++;

	return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url, $_wp_last_utility_menu);
}

/**
 * Vô hiệu hóa tự động hoàn thành trên biểu mẫu 'bài viết' (màn hình Thêm/Sửa Bài viết) cho trình duyệt WebKit,
 * vì chúng bỏ qua cài đặt autocomplete trên textarea trình soạn thảo. Điều này có thể làm hỏng trình soạn thảo
 * khi người dùng điều hướng đến nó bằng nút Quay lại của trình duyệt. Xem #28037
 *
 * Được thay thế bằng wp_page_reload_on_back_button_js() cũng khắc phục vấn đề này.
 *
 * @since 4.0.0
 * @deprecated 4.6.0
 *
 * @link https://core.trac.wordpress.org/ticket/35852
 *
 * @global bool $is_safari
 * @global bool $is_chrome
 */
function post_form_autocomplete_off() {
	global $is_safari, $is_chrome;

	_deprecated_function( __FUNCTION__, '4.6.0' );

	if ( $is_safari || $is_chrome ) {
		echo ' autocomplete="off"';
	}
}

/**
 * Hiển thị JavaScript trên trang.
 *
 * @since 3.5.0
 * @deprecated 4.9.0
 */
function options_permalink_add_js() {
	?>
	<script type="text/javascript">
		jQuery( function() {
			jQuery('.permalink-structure input:radio').change(function() {
				if ( 'custom' == this.value )
					return;
				jQuery('#permalink_structure').val( this.value );
			});
			jQuery( '#permalink_structure' ).on( 'click input', function() {
				jQuery( '#custom_selection' ).prop( 'checked', true );
			});
		} );
	</script>
	<?php
}

/**
 * Lớp trước đây cho bảng danh sách yêu cầu xuất dữ liệu riêng tư.
 *
 * @since 4.9.6
 * @deprecated 5.3.0
 */
class WP_Privacy_Data_Export_Requests_Table extends WP_Privacy_Data_Export_Requests_List_Table {
	function __construct( $args ) {
		_deprecated_function( __CLASS__, '5.3.0', 'WP_Privacy_Data_Export_Requests_List_Table' );

		if ( ! isset( $args['screen'] ) || $args['screen'] === 'export_personal_data' ) {
			$args['screen'] = 'export-personal-data';
		}

		parent::__construct( $args );
	}
}

/**
 * Lớp trước đây cho bảng danh sách yêu cầu xóa dữ liệu riêng tư.
 *
 * @since 4.9.6
 * @deprecated 5.3.0
 */
class WP_Privacy_Data_Removal_Requests_Table extends WP_Privacy_Data_Removal_Requests_List_Table {
	function __construct( $args ) {
		_deprecated_function( __CLASS__, '5.3.0', 'WP_Privacy_Data_Removal_Requests_List_Table' );

		if ( ! isset( $args['screen'] ) || $args['screen'] === 'remove_personal_data' ) {
			$args['screen'] = 'erase-personal-data';
		}

		parent::__construct( $args );
	}
}

/**
 * Trước đây dùng để thêm tùy chọn cho các màn hình yêu cầu riêng tư trước khi chúng là các file riêng biệt.
 *
 * @since 4.9.8
 * @access private
 * @deprecated 5.3.0
 */
function _wp_privacy_requests_screen_options() {
	_deprecated_function( __FUNCTION__, '5.3.0' );
}

/**
 * Trước đây dùng để lọc đầu vào từ media_upload_form_handler() và gán post_title
 * mặc định từ tên file nếu không được cung cấp.
 *
 * @since 2.5.0
 * @deprecated 6.0.0
 *
 * @param array $post       Đối tượng đính kèm WP_Post được chuyển đổi thành mảng.
 * @param array $attachment Mảng metadata đính kèm.
 * @return array Đối tượng bài viết đính kèm được chuyển đổi thành mảng.
 */
function image_attachment_fields_to_save( $post, $attachment ) {
	_deprecated_function( __FUNCTION__, '6.0.0' );

	return $post;
}
