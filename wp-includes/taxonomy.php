<?php
/**
 * API Taxonomy cốt lõi
 *
 * @package WordPress
 * @subpackage Taxonomy
 */

//
// Đăng ký taxonomy.
//

/**
 * Tạo các taxonomy ban đầu.
 *
 * Hàm này được gọi hai lần: trong wp-settings.php trước khi plugin được tải (vì
 * lý do tương thích ngược), và một lần nữa trong action {@see 'init'}. Chúng ta phải
 * tránh đăng ký rewrite rules trước action {@see 'init'}.
 *
 * @since 2.8.0
 * @since 5.9.0 Thêm taxonomy `'wp_template_part_area'`.
 *
 * @global WP_Rewrite $wp_rewrite Thành phần rewrite của WordPress.
 */
function create_initial_taxonomies() {
	global $wp_rewrite;

	WP_Taxonomy::reset_default_labels();

	if ( ! did_action( 'init' ) ) {
		$rewrite = array(
			'category'    => false,
			'post_tag'    => false,
			'post_format' => false,
		);
	} else {

		/**
		 * Lọc rewrite base của định dạng bài viết.
		 *
		 * @since 3.1.0
		 *
		 * @param string $context Ngữ cảnh của rewrite base. Mặc định 'type'.
		 */
		$post_format_base = apply_filters( 'post_format_rewrite_base', 'type' );
		$rewrite          = array(
			'category'    => array(
				'hierarchical' => true,
				'slug'         => get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category',
				'with_front'   => ! get_option( 'category_base' ) || $wp_rewrite->using_index_permalinks(),
				'ep_mask'      => EP_CATEGORIES,
			),
			'post_tag'    => array(
				'hierarchical' => false,
				'slug'         => get_option( 'tag_base' ) ? get_option( 'tag_base' ) : 'tag',
				'with_front'   => ! get_option( 'tag_base' ) || $wp_rewrite->using_index_permalinks(),
				'ep_mask'      => EP_TAGS,
			),
			'post_format' => $post_format_base ? array( 'slug' => $post_format_base ) : false,
		);
	}

	register_taxonomy(
		'category',
		'post',
		array(
			'hierarchical'          => true,
			'query_var'             => 'category_name',
			'rewrite'               => $rewrite['category'],
			'public'                => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'_builtin'              => true,
			'capabilities'          => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'edit_categories',
				'delete_terms' => 'delete_categories',
				'assign_terms' => 'assign_categories',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'categories',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
		)
	);

	register_taxonomy(
		'post_tag',
		'post',
		array(
			'hierarchical'          => false,
			'query_var'             => 'tag',
			'rewrite'               => $rewrite['post_tag'],
			'public'                => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'_builtin'              => true,
			'capabilities'          => array(
				'manage_terms' => 'manage_post_tags',
				'edit_terms'   => 'edit_post_tags',
				'delete_terms' => 'delete_post_tags',
				'assign_terms' => 'assign_post_tags',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'tags',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
		)
	);

	register_taxonomy(
		'nav_menu',
		'nav_menu_item',
		array(
			'public'                => false,
			'hierarchical'          => false,
			'labels'                => array(
				'name'          => __( 'Navigation Menus' ),
				'singular_name' => __( 'Navigation Menu' ),
			),
			'query_var'             => false,
			'rewrite'               => false,
			'show_ui'               => false,
			'_builtin'              => true,
			'show_in_nav_menus'     => false,
			'capabilities'          => array(
				'manage_terms' => 'edit_theme_options',
				'edit_terms'   => 'edit_theme_options',
				'delete_terms' => 'edit_theme_options',
				'assign_terms' => 'edit_theme_options',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'menus',
			'rest_controller_class' => 'WP_REST_Menus_Controller',
		)
	);

	register_taxonomy(
		'link_category',
		'link',
		array(
			'hierarchical' => false,
			'labels'       => array(
				'name'                       => __( 'Link Categories' ),
				'singular_name'              => __( 'Link Category' ),
				'search_items'               => __( 'Search Link Categories' ),
				'popular_items'              => null,
				'all_items'                  => __( 'All Link Categories' ),
				'edit_item'                  => __( 'Edit Link Category' ),
				'update_item'                => __( 'Update Link Category' ),
				'add_new_item'               => __( 'Add Link Category' ),
				'new_item_name'              => __( 'New Link Category Name' ),
				'separate_items_with_commas' => null,
				'add_or_remove_items'        => null,
				'choose_from_most_used'      => null,
				'back_to_items'              => __( '&larr; Go to Link Categories' ),
			),
			'capabilities' => array(
				'manage_terms' => 'manage_links',
				'edit_terms'   => 'manage_links',
				'delete_terms' => 'manage_links',
				'assign_terms' => 'manage_links',
			),
			'query_var'    => false,
			'rewrite'      => false,
			'public'       => false,
			'show_ui'      => true,
			'_builtin'     => true,
		)
	);

	register_taxonomy(
		'post_format',
		'post',
		array(
			'public'            => true,
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Formats', 'post format' ),
				'singular_name' => _x( 'Format', 'post format' ),
			),
			'query_var'         => true,
			'rewrite'           => $rewrite['post_format'],
			'show_ui'           => false,
			'_builtin'          => true,
			'show_in_nav_menus' => current_theme_supports( 'post-formats' ),
		)
	);

	register_taxonomy(
		'wp_theme',
		array( 'wp_template', 'wp_template_part', 'wp_global_styles' ),
		array(
			'public'            => false,
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => __( 'Themes' ),
				'singular_name' => __( 'Theme' ),
			),
			'query_var'         => false,
			'rewrite'           => false,
			'show_ui'           => false,
			'_builtin'          => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => false,
		)
	);

	register_taxonomy(
		'wp_template_part_area',
		array( 'wp_template_part' ),
		array(
			'public'            => false,
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => __( 'Template Part Areas' ),
				'singular_name' => __( 'Template Part Area' ),
			),
			'query_var'         => false,
			'rewrite'           => false,
			'show_ui'           => false,
			'_builtin'          => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => false,
		)
	);

	register_taxonomy(
		'wp_pattern_category',
		array( 'wp_block' ),
		array(
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => false,
			'labels'             => array(
				'name'                       => _x( 'Pattern Categories', 'taxonomy general name' ),
				'singular_name'              => _x( 'Pattern Category', 'taxonomy singular name' ),
				'add_new_item'               => __( 'Add Category' ),
				'add_or_remove_items'        => __( 'Add or remove pattern categories' ),
				'back_to_items'              => __( '&larr; Go to Pattern Categories' ),
				'choose_from_most_used'      => __( 'Choose from the most used pattern categories' ),
				'edit_item'                  => __( 'Edit Pattern Category' ),
				'item_link'                  => __( 'Pattern Category Link' ),
				'item_link_description'      => __( 'A link to a pattern category.' ),
				'items_list'                 => __( 'Pattern Categories list' ),
				'items_list_navigation'      => __( 'Pattern Categories list navigation' ),
				'new_item_name'              => __( 'New Pattern Category Name' ),
				'no_terms'                   => __( 'No pattern categories' ),
				'not_found'                  => __( 'No pattern categories found.' ),
				'popular_items'              => __( 'Popular Pattern Categories' ),
				'search_items'               => __( 'Search Pattern Categories' ),
				'separate_items_with_commas' => __( 'Separate pattern categories with commas' ),
				'update_item'                => __( 'Update Pattern Category' ),
				'view_item'                  => __( 'View Pattern Category' ),
			),
			'query_var'          => false,
			'rewrite'            => false,
			'show_ui'            => true,
			'_builtin'           => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'show_tagcloud'      => false,
		)
	);
}

/**
 * Lấy danh sách tên hoặc đối tượng taxonomy đã đăng ký.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param array  $args     Tùy chọn. Mảng các tham số `key => value` để so khớp với các đối tượng taxonomy.
 *                         Mặc định mảng rỗng.
 * @param string $output   Tùy chọn. Kiểu đầu ra trả về trong mảng. Có thể là 'names'
 *                         hoặc 'objects'. Mặc định 'names'.
 * @param string $operator Tùy chọn. Phép toán logic cần thực hiện. Chấp nhận 'and' hoặc 'or'. 'or' nghĩa là chỉ
 *                         cần một phần tử trong mảng khớp; 'and' nghĩa là tất cả phần tử phải khớp.
 *                         Mặc định 'and'.
 * @return string[]|WP_Taxonomy[] Mảng tên hoặc đối tượng taxonomy.
 */
function get_taxonomies( $args = array(), $output = 'names', $operator = 'and' ) {
	global $wp_taxonomies;

	$field = ( 'names' === $output ) ? 'name' : false;

	return wp_filter_object_list( $wp_taxonomies, $args, $operator, $field );
}

/**
 * Trả về tên hoặc đối tượng của các taxonomy đã được đăng ký cho đối tượng hoặc loại đối tượng được yêu cầu,
 * chẳng hạn như đối tượng bài viết hoặc tên post type.
 *
 * Ví dụ:
 *
 *     $taxonomies = get_object_taxonomies( 'post' );
 *
 * Kết quả:
 *
 *     Array( 'category', 'post_tag' )
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param string|string[]|WP_Post $object_type Tên loại đối tượng taxonomy, hoặc một đối tượng (dòng từ bảng posts).
 * @param string                  $output      Tùy chọn. Kiểu đầu ra trả về trong mảng. Chấp nhận
 *                                             'names' hoặc 'objects'. Mặc định 'names'.
 * @return string[]|WP_Taxonomy[] Tên hoặc đối tượng của tất cả taxonomy thuộc `$object_type`.
 */
function get_object_taxonomies( $object_type, $output = 'names' ) {
	global $wp_taxonomies;

	if ( is_object( $object_type ) ) {
		if ( 'attachment' === $object_type->post_type ) {
			return get_attachment_taxonomies( $object_type, $output );
		}
		$object_type = $object_type->post_type;
	}

	$object_type = (array) $object_type;

	$taxonomies = array();
	foreach ( (array) $wp_taxonomies as $tax_name => $tax_obj ) {
		if ( array_intersect( $object_type, (array) $tax_obj->object_type ) ) {
			if ( 'names' === $output ) {
				$taxonomies[] = $tax_name;
			} else {
				$taxonomies[ $tax_name ] = $tax_obj;
			}
		}
	}

	return $taxonomies;
}

/**
 * Lấy đối tượng taxonomy của $taxonomy.
 *
 * Hàm get_taxonomy sẽ kiểm tra trước xem chuỗi tham số được truyền vào
 * có phải là đối tượng taxonomy hay không, nếu đúng thì trả về nó.
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param string $taxonomy Tên của đối tượng taxonomy cần trả về.
 * @return WP_Taxonomy|false Đối tượng taxonomy hoặc false nếu $taxonomy không tồn tại.
 */
function get_taxonomy( $taxonomy ) {
	global $wp_taxonomies;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	return $wp_taxonomies[ $taxonomy ];
}

/**
 * Xác định xem tên taxonomy có tồn tại hay không.
 *
 * Trước đây là is_taxonomy(), được giới thiệu từ 2.3.0.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay lập trình Theme.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param string $taxonomy Tên đối tượng taxonomy.
 * @return bool Taxonomy có tồn tại hay không.
 */
function taxonomy_exists( $taxonomy ) {
	global $wp_taxonomies;

	return is_string( $taxonomy ) && isset( $wp_taxonomies[ $taxonomy ] );
}

/**
 * Xác định xem đối tượng taxonomy có phân cấp hay không.
 *
 * Kiểm tra trước xem taxonomy có phải là đối tượng hay không. Sau đó lấy
 * đối tượng, và cuối cùng trả về giá trị phân cấp trong đối tượng.
 *
 * Giá trị trả về false cũng có thể có nghĩa là taxonomy không tồn tại.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay lập trình Theme.
 *
 * @since 2.3.0
 *
 * @param string $taxonomy Tên đối tượng taxonomy.
 * @return bool Taxonomy có phân cấp hay không.
 */
function is_taxonomy_hierarchical( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	$taxonomy = get_taxonomy( $taxonomy );
	return $taxonomy->hierarchical;
}

/**
 * Tạo hoặc chỉnh sửa đối tượng taxonomy.
 *
 * Lưu ý: Không sử dụng trước hook {@see 'init'}.
 *
 * Một hàm đơn giản để tạo hoặc chỉnh sửa đối tượng taxonomy dựa trên
 * các tham số được cung cấp. Nếu chỉnh sửa đối tượng taxonomy hiện có, lưu ý
 * rằng giá trị `$object_type` từ lần đăng ký gốc sẽ bị
 * ghi đè.
 *
 * @since 2.3.0
 * @since 4.2.0 Giới thiệu tham số `show_in_quick_edit`.
 * @since 4.4.0 Tham số `show_ui` giờ được áp dụng trên màn hình chỉnh sửa term.
 * @since 4.4.0 Tham số `public` giờ kiểm soát việc taxonomy có thể truy vấn ở front end hay không.
 * @since 4.5.0 Giới thiệu tham số `publicly_queryable`.
 * @since 4.7.0 Giới thiệu các tham số `show_in_rest`, 'rest_base' và 'rest_controller_class'
 *              để đăng ký taxonomy trong REST API.
 * @since 5.1.0 Giới thiệu tham số `meta_box_sanitize_cb`.
 * @since 5.4.0 Thêm đối tượng taxonomy đã đăng ký làm giá trị trả về.
 * @since 5.5.0 Giới thiệu tham số `default_term`.
 * @since 5.9.0 Giới thiệu tham số `rest_namespace`.
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param string       $taxonomy    Khóa taxonomy. Không được vượt quá 32 ký tự và chỉ có thể chứa
 *                                  ký tự chữ-số viết thường, dấu gạch ngang và dấu gạch dưới. Xem sanitize_key().
 * @param array|string $object_type Loại đối tượng hoặc mảng các loại đối tượng mà taxonomy sẽ được liên kết.
 * @param array|string $args        {
 *     Tùy chọn. Mảng hoặc chuỗi query string của các tham số để đăng ký taxonomy.
 *
 *     @type string[]      $labels                Mảng các nhãn cho taxonomy này. Mặc định, nhãn Tag được sử dụng
 *                                                cho taxonomy không phân cấp, và nhãn Category được sử dụng
 *                                                cho taxonomy phân cấp. Xem các giá trị được chấp nhận trong
 *                                                get_taxonomy_labels(). Mặc định mảng rỗng.
 *     @type string        $description           Mô tả ngắn gọn về mục đích của taxonomy. Mặc định rỗng.
 *     @type bool          $public                Taxonomy có được sử dụng công khai thông qua
 *                                                giao diện admin hoặc bởi người dùng front-end hay không. Các thiết lập mặc định
 *                                                của `$publicly_queryable`, `$show_ui`, và `$show_in_nav_menus`
 *                                                được kế thừa từ `$public`.
 *     @type bool          $publicly_queryable    Taxonomy có thể truy vấn công khai hay không.
 *                                                Nếu không được thiết lập, mặc định được kế thừa từ `$public`.
 *     @type bool          $hierarchical          Taxonomy có phân cấp hay không. Mặc định false.
 *     @type bool          $show_ui               Có tạo và cho phép giao diện quản lý term trong taxonomy này ở
 *                                                admin hay không. Nếu không thiết lập, mặc định được kế thừa từ `$public`
 *                                                (mặc định true).
 *     @type bool          $show_in_menu          Có hiển thị taxonomy trong menu admin hay không. Nếu true, taxonomy
 *                                                được hiển thị như menu con của menu loại đối tượng. Nếu false, không hiển thị menu.
 *                                                `$show_ui` phải là true. Nếu không thiết lập, mặc định kế thừa từ `$show_ui`
 *                                                (mặc định true).
 *     @type bool          $show_in_nav_menus     Cho phép taxonomy này có sẵn để chọn trong menu điều hướng. Nếu không
 *                                                thiết lập, mặc định kế thừa từ `$public` (mặc định true).
 *     @type bool          $show_in_rest          Có bao gồm taxonomy trong REST API hay không. Thiết lập true
 *                                                để taxonomy có sẵn trong trình soạn thảo khối.
 *     @type string        $rest_base             Để thay đổi URL gốc của route REST API. Mặc định là $taxonomy.
 *     @type string        $rest_namespace        Để thay đổi namespace URL của route REST API. Mặc định là wp/v2.
 *     @type string        $rest_controller_class Tên class Controller REST API. Mặc định là 'WP_REST_Terms_Controller'.
 *     @type bool          $show_tagcloud         Có liệt kê taxonomy trong điều khiển Widget Tag Cloud hay không. Nếu không thiết lập,
 *                                                mặc định kế thừa từ `$show_ui` (mặc định true).
 *     @type bool          $show_in_quick_edit    Có hiển thị taxonomy trong bảng chỉnh sửa nhanh/hàng loạt hay không. Nếu không thiết lập,
 *                                                mặc định kế thừa từ `$show_ui` (mặc định true).
 *     @type bool          $show_admin_column     Có hiển thị cột cho taxonomy trên màn hình danh sách post type hay không.
 *                                                Mặc định false.
 *     @type bool|callable $meta_box_cb           Cung cấp hàm callback cho hiển thị meta box. Nếu không thiết lập,
 *                                                post_categories_meta_box() được dùng cho taxonomy phân cấp, và
 *                                                post_tags_meta_box() được dùng cho không phân cấp. Nếu false, không
 *                                                hiển thị meta box.
 *     @type callable      $meta_box_sanitize_cb  Hàm callback để làm sạch dữ liệu taxonomy được lưu từ meta
 *                                                box. Nếu không định nghĩa callback, một callback phù hợp sẽ được xác định
 *                                                dựa trên giá trị của `$meta_box_cb`.
 *     @type string[]      $capabilities {
 *         Mảng các quyền cho taxonomy này.
 *
 *         @type string $manage_terms Mặc định 'manage_categories'.
 *         @type string $edit_terms   Mặc định 'manage_categories'.
 *         @type string $delete_terms Mặc định 'manage_categories'.
 *         @type string $assign_terms Mặc định 'edit_posts'.
 *     }
 *     @type bool|array    $rewrite {
 *         Kích hoạt xử lý rewrite cho taxonomy này. Mặc định true, sử dụng $taxonomy làm slug. Để ngăn
 *         rewrite, thiết lập false. Để chỉ định rewrite rules, có thể truyền mảng với các khóa sau:
 *
 *         @type string $slug         Tùy chỉnh slug permastruct. Mặc định khóa `$taxonomy`.
 *         @type bool   $with_front   Có nên thêm WP_Rewrite::$front vào trước permastruct hay không. Mặc định true.
 *         @type bool   $hierarchical Có phải rewrite tag phân cấp hay không. Mặc định false.
 *         @type int    $ep_mask      Gán endpoint mask. Mặc định `EP_NONE`.
 *     }
 *     @type string|bool   $query_var             Thiết lập khóa query var cho taxonomy này. Mặc định khóa `$taxonomy`. Nếu
 *                                                false, taxonomy không thể tải tại `?{query_var}={term_slug}`. Nếu là
 *                                                chuỗi, query `?{query_var}={term_slug}` sẽ hợp lệ.
 *     @type callable      $update_count_callback Hoạt động giống hook, sẽ được gọi khi số lượng được
 *                                                cập nhật. Mặc định _update_post_term_count() cho taxonomy gắn với
 *                                                post type, xác nhận rằng đối tượng đã được xuất bản trước
 *                                                khi đếm. Mặc định _update_generic_term_count() cho taxonomy
 *                                                gắn với loại đối tượng khác, như users.
 *     @type string|array  $default_term {
 *         Term mặc định được sử dụng cho taxonomy.
 *
 *         @type string $name         Tên của term mặc định.
 *         @type string $slug         Slug cho term mặc định. Mặc định rỗng.
 *         @type string $description  Mô tả cho term mặc định. Mặc định rỗng.
 *     }
 *     @type bool          $sort                  Có nên sắp xếp term trong taxonomy này theo thứ tự được
 *                                                cung cấp cho `wp_set_object_terms()` hay không. Mặc định null tương đương false.
 *     @type array         $args                  Mảng tham số tự động sử dụng bên trong `wp_get_object_terms()`
 *                                                cho taxonomy này.
 *     @type bool          $_builtin              Taxonomy này là taxonomy "tích hợp sẵn". CHỈ SỬ DỤNG NỘI BỘ!
 *                                                Mặc định false.
 * }
 * @return WP_Taxonomy|WP_Error Đối tượng taxonomy đã đăng ký khi thành công, đối tượng WP_Error khi thất bại.
 */
function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
	global $wp_taxonomies;

	if ( ! is_array( $wp_taxonomies ) ) {
		$wp_taxonomies = array();
	}

	$args = wp_parse_args( $args );

	if ( empty( $taxonomy ) || strlen( $taxonomy ) > 32 ) {
		_doing_it_wrong( __FUNCTION__, __( 'Taxonomy names must be between 1 and 32 characters in length.' ), '4.2.0' );
		return new WP_Error( 'taxonomy_length_invalid', __( 'Taxonomy names must be between 1 and 32 characters in length.' ) );
	}

	$taxonomy_object = new WP_Taxonomy( $taxonomy, $object_type, $args );
	$taxonomy_object->add_rewrite_rules();

	$wp_taxonomies[ $taxonomy ] = $taxonomy_object;

	$taxonomy_object->add_hooks();

	// Thêm term mặc định.
	if ( ! empty( $taxonomy_object->default_term ) ) {
		$term = term_exists( $taxonomy_object->default_term['name'], $taxonomy );
		if ( $term ) {
			update_option( 'default_term_' . $taxonomy_object->name, $term['term_id'] );
		} else {
			$term = wp_insert_term(
				$taxonomy_object->default_term['name'],
				$taxonomy,
				array(
					'slug'        => sanitize_title( $taxonomy_object->default_term['slug'] ),
					'description' => $taxonomy_object->default_term['description'],
				)
			);

			// Cập nhật `term_id` trong options.
			if ( ! is_wp_error( $term ) ) {
				update_option( 'default_term_' . $taxonomy_object->name, $term['term_id'] );
			}
		}
	}

	/**
	 * Kích hoạt sau khi một taxonomy được đăng ký.
	 *
	 * @since 3.3.0
	 *
	 * @param string       $taxonomy    Slug taxonomy.
	 * @param array|string $object_type Loại đối tượng hoặc mảng các loại đối tượng.
	 * @param array        $args        Mảng tham số đăng ký taxonomy.
	 */
	do_action( 'registered_taxonomy', $taxonomy, $object_type, (array) $taxonomy_object );

	/**
	 * Kích hoạt sau khi một taxonomy cụ thể được đăng ký.
	 *
	 * Phần động của tên filter, `$taxonomy`, tham chiếu đến khóa taxonomy.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `registered_taxonomy_category`
	 *  - `registered_taxonomy_post_tag`
	 *
	 * @since 6.0.0
	 *
	 * @param string       $taxonomy    Slug taxonomy.
	 * @param array|string $object_type Loại đối tượng hoặc mảng các loại đối tượng.
	 * @param array        $args        Mảng tham số đăng ký taxonomy.
	 */
	do_action( "registered_taxonomy_{$taxonomy}", $taxonomy, $object_type, (array) $taxonomy_object );

	return $taxonomy_object;
}

/**
 * Hủy đăng ký taxonomy.
 *
 * Không thể dùng để hủy đăng ký các taxonomy tích hợp sẵn.
 *
 * @since 4.5.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Danh sách taxonomy.
 *
 * @param string $taxonomy Tên taxonomy.
 * @return true|WP_Error True khi thành công, WP_Error khi thất bại hoặc taxonomy không tồn tại.
 */
function unregister_taxonomy( $taxonomy ) {
	global $wp_taxonomies;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$taxonomy_object = get_taxonomy( $taxonomy );

	// Không cho phép hủy đăng ký các taxonomy nội bộ.
	if ( $taxonomy_object->_builtin ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Unregistering a built-in taxonomy is not allowed.' ) );
	}

	$taxonomy_object->remove_rewrite_rules();
	$taxonomy_object->remove_hooks();

	// Xóa taxonomy.
	unset( $wp_taxonomies[ $taxonomy ] );

	/**
	 * Kích hoạt sau khi taxonomy bị hủy đăng ký.
	 *
	 * @since 4.5.0
	 *
	 * @param string $taxonomy Tên taxonomy.
	 */
	do_action( 'unregistered_taxonomy', $taxonomy );

	return true;
}

/**
 * Xây dựng đối tượng với tất cả nhãn taxonomy từ đối tượng taxonomy.
 *
 * @since 3.0.0
 * @since 4.3.0 Thêm nhãn `no_terms`.
 * @since 4.4.0 Thêm nhãn `items_list_navigation` và `items_list`.
 * @since 4.9.0 Thêm nhãn `most_used` và `back_to_items`.
 * @since 5.7.0 Thêm nhãn `filter_by_item`.
 * @since 5.8.0 Thêm nhãn `item_link` và `item_link_description`.
 * @since 5.9.0 Thêm nhãn `name_field_description`, `slug_field_description`,
 *              `parent_field_description`, và `desc_field_description`.
 * @since 6.6.0 Thêm nhãn `template_name`.
 *
 * @param WP_Taxonomy $tax Đối tượng taxonomy.
 * @return object {
 *     Đối tượng nhãn taxonomy. Giá trị mặc định đầu tiên dành cho taxonomy không phân cấp
 *     (như tag) và giá trị thứ hai dành cho taxonomy phân cấp (như category).
 *
 *     @type string $name                       Tên chung cho taxonomy, thường ở dạng số nhiều. Giống
 *                                              và bị ghi đè bởi `$tax->label`. Mặc định 'Tags'/'Categories'.
 *     @type string $singular_name              Tên cho một đối tượng của taxonomy này. Mặc định 'Tag'/'Category'.
 *     @type string $search_items               Mặc định 'Search Tags'/'Search Categories'.
 *     @type string $popular_items              Nhãn này chỉ dùng cho taxonomy không phân cấp.
 *                                              Mặc định 'Popular Tags'.
 *     @type string $all_items                  Mặc định 'All Tags'/'All Categories'.
 *     @type string $parent_item                Nhãn này chỉ dùng cho taxonomy phân cấp. Mặc định
 *                                              'Parent Category'.
 *     @type string $parent_item_colon          Giống `parent_item`, nhưng có dấu hai chấm `:` ở cuối.
 *     @type string $name_field_description     Mô tả cho trường Tên trên màn hình Sửa Tag.
 *                                              Mặc định 'Tên là cách nó hiển thị trên trang web của bạn'.
 *     @type string $slug_field_description     Mô tả cho trường Slug trên màn hình Sửa Tag.
 *                                              Mặc định 'Slug là phiên bản thân thiện URL
 *                                              của tên. Thường viết thường và chỉ chứa
 *                                              chữ cái, số và dấu gạch ngang'.
 *     @type string $parent_field_description   Mô tả cho trường Cha trên màn hình Sửa Tag.
 *                                              Mặc định 'Gán term cha để tạo phân cấp.
 *                                              Ví dụ, term Jazz sẽ là cha
 *                                              của Bebop và Big Band'.
 *     @type string $desc_field_description     Mô tả cho trường Mô tả trên màn hình Sửa Tag.
 *                                              Mặc định 'Mô tả không nổi bật theo mặc định;
 *                                              tuy nhiên, một số theme có thể hiển thị nó'.
 *     @type string $edit_item                  Mặc định 'Edit Tag'/'Edit Category'.
 *     @type string $view_item                  Mặc định 'View Tag'/'View Category'.
 *     @type string $update_item                Mặc định 'Update Tag'/'Update Category'.
 *     @type string $add_new_item               Mặc định 'Add Tag'/'Add Category'.
 *     @type string $new_item_name              Mặc định 'New Tag Name'/'New Category Name'.
 *     @type string $template_name              Mặc định 'Tag Archives'/'Category Archives'.
 *     @type string $separate_items_with_commas Nhãn này chỉ dùng cho taxonomy không phân cấp. Mặc định
 *                                              'Separate tags with commas', dùng trong meta box.
 *     @type string $add_or_remove_items        Nhãn này chỉ dùng cho taxonomy không phân cấp. Mặc định
 *                                              'Add or remove tags', dùng trong meta box khi JavaScript
 *                                              bị tắt.
 *     @type string $choose_from_most_used      Nhãn này chỉ dùng cho taxonomy không phân cấp. Mặc định
 *                                              'Choose from the most used tags', dùng trong meta box.
 *     @type string $not_found                  Mặc định 'No tags found'/'No categories found', dùng trong
 *                                              meta box và bảng danh sách taxonomy.
 *     @type string $no_terms                   Mặc định 'No tags'/'No categories', dùng trong bảng danh sách
 *                                              bài viết và media.
 *     @type string $filter_by_item             Nhãn này chỉ dùng cho taxonomy phân cấp. Mặc định
 *                                              'Filter by category', dùng trong bảng danh sách bài viết.
 *     @type string $items_list_navigation      Nhãn cho tiêu đề ẩn phân trang bảng.
 *     @type string $items_list                 Nhãn cho tiêu đề ẩn bảng.
 *     @type string $most_used                  Tiêu đề cho tab Được dùng nhiều nhất. Mặc định 'Most Used'.
 *     @type string $back_to_items              Nhãn hiển thị sau khi term được cập nhật.
 *     @type string $item_link                  Dùng trong trình soạn thảo khối. Tiêu đề cho biến thể khối liên kết điều hướng.
 *                                              Mặc định 'Tag Link'/'Category Link'.
 *     @type string $item_link_description      Dùng trong trình soạn thảo khối. Mô tả cho biến thể khối liên kết
 *                                              điều hướng. Mặc định 'A link to a tag'/'A link to a category'.
 * }
 */
function get_taxonomy_labels( $tax ) {
	$tax->labels = (array) $tax->labels;

	if ( isset( $tax->helps ) && empty( $tax->labels['separate_items_with_commas'] ) ) {
		$tax->labels['separate_items_with_commas'] = $tax->helps;
	}

	if ( isset( $tax->no_tagcloud ) && empty( $tax->labels['not_found'] ) ) {
		$tax->labels['not_found'] = $tax->no_tagcloud;
	}

	$nohier_vs_hier_defaults = WP_Taxonomy::get_default_labels();

	$nohier_vs_hier_defaults['menu_name'] = $nohier_vs_hier_defaults['name'];

	$labels = _get_custom_object_labels( $tax, $nohier_vs_hier_defaults );

	if ( ! isset( $tax->labels->template_name ) && isset( $labels->singular_name ) ) {
		/* translators: %s: Taxonomy name. */
		$labels->template_name = sprintf( _x( '%s Archives', 'taxonomy template name' ), $labels->singular_name );
	}

	$taxonomy = $tax->name;

	$default_labels = clone $labels;

	/**
	 * Lọc các nhãn của một taxonomy cụ thể.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `taxonomy_labels_category`
	 *  - `taxonomy_labels_post_tag`
	 *
	 * @since 4.4.0
	 *
	 * @see get_taxonomy_labels() để xem danh sách đầy đủ các nhãn taxonomy.
	 *
	 * @param object $labels Đối tượng chứa các nhãn của taxonomy dưới dạng biến thành viên.
	 */
	$labels = apply_filters( "taxonomy_labels_{$taxonomy}", $labels );

	// Đảm bảo rằng các nhãn đã được lọc chứa tất cả giá trị mặc định cần thiết.
	$labels = (object) array_merge( (array) $default_labels, (array) $labels );

	return $labels;
}

/**
 * Thêm một taxonomy đã đăng ký vào loại đối tượng.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param string $taxonomy    Tên đối tượng taxonomy.
 * @param string $object_type Tên loại đối tượng.
 * @return bool True nếu thành công, false nếu không.
 */
function register_taxonomy_for_object_type( $taxonomy, $object_type ) {
	global $wp_taxonomies;

	if ( ! isset( $wp_taxonomies[ $taxonomy ] ) ) {
		return false;
	}

	if ( ! get_post_type_object( $object_type ) ) {
		return false;
	}

	if ( ! in_array( $object_type, $wp_taxonomies[ $taxonomy ]->object_type, true ) ) {
		$wp_taxonomies[ $taxonomy ]->object_type[] = $object_type;
	}

	// Lọc bỏ các phần tử rỗng.
	$wp_taxonomies[ $taxonomy ]->object_type = array_filter( $wp_taxonomies[ $taxonomy ]->object_type );

	/**
	 * Kích hoạt sau khi một taxonomy được đăng ký cho loại đối tượng.
	 *
	 * @since 5.1.0
	 *
	 * @param string $taxonomy    Tên taxonomy.
	 * @param string $object_type Tên loại đối tượng.
	 */
	do_action( 'registered_taxonomy_for_object_type', $taxonomy, $object_type );

	return true;
}

/**
 * Gỡ bỏ một taxonomy đã đăng ký khỏi loại đối tượng.
 *
 * @since 3.7.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies Các taxonomy đã đăng ký.
 *
 * @param string $taxonomy    Tên đối tượng taxonomy.
 * @param string $object_type Tên loại đối tượng.
 * @return bool True nếu thành công, false nếu không.
 */
function unregister_taxonomy_for_object_type( $taxonomy, $object_type ) {
	global $wp_taxonomies;

	if ( ! isset( $wp_taxonomies[ $taxonomy ] ) ) {
		return false;
	}

	if ( ! get_post_type_object( $object_type ) ) {
		return false;
	}

	$key = array_search( $object_type, $wp_taxonomies[ $taxonomy ]->object_type, true );
	if ( false === $key ) {
		return false;
	}

	unset( $wp_taxonomies[ $taxonomy ]->object_type[ $key ] );

	/**
	 * Kích hoạt sau khi một taxonomy bị hủy đăng ký khỏi loại đối tượng.
	 *
	 * @since 5.1.0
	 *
	 * @param string $taxonomy    Tên taxonomy.
	 * @param string $object_type Tên loại đối tượng.
	 */
	do_action( 'unregistered_taxonomy_for_object_type', $taxonomy, $object_type );

	return true;
}

//
// API Term.
//

/**
 * Lấy các ID đối tượng của taxonomy và term hợp lệ.
 *
 * Các chuỗi `$taxonomies` phải tồn tại trước khi hàm này tiếp tục.
 * Khi không tìm thấy taxonomy hợp lệ, hàm sẽ trả về WP_Error.
 *
 * `$terms` không được kiểm tra giống như `$taxonomies`, nhưng vẫn cần tồn tại
 * để các ID đối tượng được trả về.
 *
 * Có thể thay đổi thứ tự trả về ID đối tượng bằng cách sử dụng `$args`
 * với mảng ASC hoặc DESC. Giá trị nên nằm trong khóa có tên 'order'.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int|int[]       $term_ids   ID term hoặc mảng các ID term sẽ được sử dụng.
 * @param string|string[] $taxonomies Chuỗi tên taxonomy hoặc mảng chuỗi tên taxonomy.
 * @param array|string    $args       {
 *     Thay đổi thứ tự của các ID đối tượng.
 *
 *     @type string $order Thứ tự lấy term. Chấp nhận 'ASC' hoặc 'DESC'. Mặc định 'ASC'.
 * }
 * @return string[]|WP_Error Mảng các ID đối tượng dưới dạng chuỗi số khi thành công,
 *                           WP_Error nếu taxonomy không tồn tại.
 */
function get_objects_in_term( $term_ids, $taxonomies, $args = array() ) {
	global $wpdb;

	if ( ! is_array( $term_ids ) ) {
		$term_ids = array( $term_ids );
	}
	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}
	foreach ( (array) $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
		}
	}

	$defaults = array( 'order' => 'ASC' );
	$args     = wp_parse_args( $args, $defaults );

	$order = ( 'desc' === strtolower( $args['order'] ) ) ? 'DESC' : 'ASC';

	$term_ids = array_map( 'intval', $term_ids );

	$taxonomies = "'" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "'";
	$term_ids   = "'" . implode( "', '", $term_ids ) . "'";

	$sql = "SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tt.term_id IN ($term_ids) ORDER BY tr.object_id $order";

	$last_changed = wp_cache_get_last_changed( 'terms' );
	$cache_key    = 'get_objects_in_term:' . md5( $sql ) . ":$last_changed";
	$cache        = wp_cache_get( $cache_key, 'term-queries' );
	if ( false === $cache ) {
		$object_ids = $wpdb->get_col( $sql );
		wp_cache_set( $cache_key, $object_ids, 'term-queries' );
	} else {
		$object_ids = (array) $cache;
	}

	if ( ! $object_ids ) {
		return array();
	}
	return $object_ids;
}

/**
 * Cho một truy vấn taxonomy, tạo SQL để nối vào truy vấn chính.
 *
 * @since 3.1.0
 *
 * @see WP_Tax_Query
 *
 * @param array  $tax_query         Truy vấn taxonomy dạng gọn.
 * @param string $primary_table
 * @param string $primary_id_column
 * @return string[]
 */
function get_tax_sql( $tax_query, $primary_table, $primary_id_column ) {
	$tax_query_obj = new WP_Tax_Query( $tax_query );
	return $tax_query_obj->get_sql( $primary_table, $primary_id_column );
}

/**
 * Lấy tất cả dữ liệu term từ cơ sở dữ liệu theo ID term.
 *
 * Mục đích sử dụng hàm get_term là để áp dụng các bộ lọc cho đối tượng term. Có thể
 * lấy đối tượng term từ cơ sở dữ liệu trước khi áp dụng các bộ lọc.
 *
 * ID $term phải thuộc $taxonomy để lấy từ cơ sở dữ liệu. Thất bại có thể
 * được bắt bởi các hook. Thất bại sẽ trả về cùng giá trị như $wpdb
 * trả về cho phương thức get_row.
 *
 * Có hai hook, một dành riêng cho mỗi term, tên 'get_term', và
 * cái thứ hai dành cho tên taxonomy, 'term_$taxonomy'. Cả hai hook nhận
 * đối tượng term và tên taxonomy làm tham số. Cả hai hook được kỳ vọng
 * trả về đối tượng term.
 *
 * Hook {@see 'get_term'} - Nhận hai tham số: đối tượng term và tên taxonomy.
 * Phải trả về đối tượng term. Được dùng trong get_term() như bộ lọc tổng hợp cho mọi
 * $term.
 *
 * Hook {@see 'get_$taxonomy'} - Nhận hai tham số: đối tượng term và tên
 * taxonomy. Phải trả về đối tượng term. $taxonomy sẽ là tên taxonomy, ví dụ
 * nếu là 'category', tên bộ lọc sẽ là 'get_category'. Hữu ích
 * cho các taxonomy tùy chỉnh hoặc gắn vào taxonomy mặc định.
 *
 * @todo Định dạng DocBlock tốt hơn
 *
 * @since 2.3.0
 * @since 4.4.0 Chuyển đổi để trả về đối tượng WP_Term nếu `$output` là `OBJECT`.
 *              Tham số `$taxonomy` được đặt là tùy chọn.
 *
 * @see sanitize_term_field() Tham số $context liệt kê các giá trị khả dụng cho tham số $filter của get_term_by().
 *
 * @param int|WP_Term|object $term     Nếu là số nguyên, dữ liệu term sẽ được lấy từ cơ sở dữ liệu,
 *                                     hoặc từ cache nếu có sẵn.
 *                                     Nếu là đối tượng stdClass (như kết quả truy vấn cơ sở dữ liệu),
 *                                     sẽ áp dụng bộ lọc và trả về đối tượng `WP_Term` với dữ liệu `$term`.
 *                                     Nếu là `WP_Term`, sẽ trả về `$term`.
 * @param string             $taxonomy Tùy chọn. Tên taxonomy mà `$term` thuộc về.
 * @param string             $output   Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                                     tương ứng với đối tượng WP_Term, mảng kết hợp, hoặc mảng số.
 *                                     Mặc định OBJECT.
 * @param string             $filter   Tùy chọn. Cách làm sạch các trường term. Mặc định 'raw'.
 * @return WP_Term|array|WP_Error|null Thể hiện WP_Term (hoặc mảng) khi thành công, tùy thuộc vào giá trị `$output`.
 *                                     WP_Error nếu `$taxonomy` không tồn tại. Null cho các lỗi khác.
 */
function get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
	if ( empty( $term ) ) {
		return new WP_Error( 'invalid_term', __( 'Empty Term.' ) );
	}

	if ( $taxonomy && ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	if ( $term instanceof WP_Term ) {
		$_term = $term;
	} elseif ( is_object( $term ) ) {
		if ( empty( $term->filter ) || 'raw' === $term->filter ) {
			$_term = sanitize_term( $term, $taxonomy, 'raw' );
			$_term = new WP_Term( $_term );
		} else {
			$_term = WP_Term::get_instance( $term->term_id );
		}
	} else {
		$_term = WP_Term::get_instance( $term, $taxonomy );
	}

	if ( is_wp_error( $_term ) ) {
		return $_term;
	} elseif ( ! $_term ) {
		return null;
	}

	// Đảm bảo cho các bộ lọc rằng giá trị này không rỗng.
	$taxonomy = $_term->taxonomy;

	$old_term = $_term;
	/**
	 * Lọc đối tượng term taxonomy.
	 *
	 * Hook {@see 'get_$taxonomy'} cũng có sẵn để nhắm mục tiêu một taxonomy
	 * cụ thể.
	 *
	 * @since 2.3.0
	 * @since 4.4.0 `$_term` giờ là đối tượng `WP_Term`.
	 *
	 * @param WP_Term $_term    Đối tượng term.
	 * @param string  $taxonomy Slug taxonomy.
	 */
	$_term = apply_filters( 'get_term', $_term, $taxonomy );

	/**
	 * Lọc đối tượng term taxonomy.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu
	 * đến slug của taxonomy của term.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `get_category`
	 *  - `get_post_tag`
	 *
	 * @since 2.3.0
	 * @since 4.4.0 `$_term` giờ là đối tượng `WP_Term`.
	 *
	 * @param WP_Term $_term    Đối tượng term.
	 * @param string  $taxonomy Slug taxonomy.
	 */
	$_term = apply_filters( "get_{$taxonomy}", $_term, $taxonomy );

	// Dừng lại nếu callback bộ lọc đã thay đổi kiểu của đối tượng `$_term`.
	if ( ! ( $_term instanceof WP_Term ) ) {
		return $_term;
	}

	// Làm sạch term, theo bộ lọc được chỉ định.
	if ( $_term !== $old_term || $_term->filter !== $filter ) {
		$_term->filter( $filter );
	}

	if ( ARRAY_A === $output ) {
		return $_term->to_array();
	} elseif ( ARRAY_N === $output ) {
		return array_values( $_term->to_array() );
	}

	return $_term;
}

/**
 * Lấy tất cả dữ liệu term từ cơ sở dữ liệu theo trường term và dữ liệu.
 *
 * Cảnh báo: $value không được escape cho $field 'name'. Bạn phải tự làm điều đó,
 * nếu cần.
 *
 * $field mặc định là 'id', do đó cũng có thể sử dụng null cho
 * field, nhưng không khuyến nghị.
 *
 * Nếu $value không tồn tại, giá trị trả về sẽ là false. Nếu $taxonomy tồn tại
 * và tổ hợp $field và $value tồn tại, term sẽ được trả về.
 *
 * Hàm này sẽ luôn trả về term đầu tiên khớp với tổ hợp `$field`-
 * `$value`-`$taxonomy` được chỉ định trong tham số. Nếu truy vấn của bạn
 * có khả năng khớp nhiều hơn một term (như trường hợp khi
 * `$field` là 'name'), hãy cân nhắc sử dụng get_terms() thay thế;
 * theo cách đó, bạn sẽ nhận được tất cả các term khớp, và có thể cung cấp logic riêng
 * để quyết định term nào là mong muốn.
 *
 * @todo Định dạng DocBlock tốt hơn.
 *
 * @since 2.3.0
 * @since 4.4.0 `$taxonomy` là tùy chọn nếu `$field` là 'term_taxonomy_id'. Chuyển đổi để trả về
 *              đối tượng WP_Term nếu `$output` là `OBJECT`.
 * @since 5.5.0 Thêm 'ID' như bí danh của 'id' cho tham số `$field`.
 *
 * @see sanitize_term_field() Tham số $context liệt kê các giá trị khả dụng cho tham số $filter của get_term_by().
 *
 * @param string     $field    Có thể là 'slug', 'name', 'term_id' (hoặc 'id', 'ID'), hoặc 'term_taxonomy_id'.
 * @param string|int $value    Tìm kiếm giá trị term này.
 * @param string     $taxonomy Tên taxonomy. Tùy chọn, nếu `$field` là 'term_taxonomy_id'.
 * @param string     $output   Tùy chọn. Kiểu trả về yêu cầu. Một trong OBJECT, ARRAY_A, hoặc ARRAY_N,
 *                             tương ứng với đối tượng WP_Term, mảng kết hợp, hoặc mảng số.
 *                             Mặc định OBJECT.
 * @param string     $filter   Tùy chọn. Cách làm sạch các trường term. Mặc định 'raw'.
 * @return WP_Term|array|false Thể hiện WP_Term (hoặc mảng) khi thành công, tùy thuộc vào giá trị `$output`.
 *                             False nếu `$taxonomy` không tồn tại hoặc `$term` không được tìm thấy.
 */
function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {

	// Tra cứu 'term_taxonomy_id' không yêu cầu kiểm tra taxonomy.
	if ( 'term_taxonomy_id' !== $field && ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Không cần thực hiện truy vấn cho 'slug' hoặc 'name' rỗng.
	if ( 'slug' === $field || 'name' === $field ) {
		$value = (string) $value;

		if ( 0 === strlen( $value ) ) {
			return false;
		}
	}

	if ( 'id' === $field || 'ID' === $field || 'term_id' === $field ) {
		$term = get_term( (int) $value, $taxonomy, $output, $filter );
		if ( is_wp_error( $term ) || null === $term ) {
			$term = false;
		}
		return $term;
	}

	$args = array(
		'get'                    => 'all',
		'number'                 => 1,
		'taxonomy'               => $taxonomy,
		'update_term_meta_cache' => false,
		'orderby'                => 'none',
		'suppress_filter'        => true,
	);

	switch ( $field ) {
		case 'slug':
			$args['slug'] = $value;
			break;
		case 'name':
			$args['name'] = $value;
			break;
		case 'term_taxonomy_id':
			$args['term_taxonomy_id'] = $value;
			unset( $args['taxonomy'] );
			break;
		default:
			return false;
	}

	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}

	$term = array_shift( $terms );

	// Trong trường hợp 'term_taxonomy_id', ghi đè `$taxonomy` được cung cấp bằng giá trị tìm thấy trong DB.
	if ( 'term_taxonomy_id' === $field ) {
		$taxonomy = $term->taxonomy;
	}

	return get_term( $term, $taxonomy, $output, $filter );
}

/**
 * Gộp tất cả term con vào một mảng duy nhất chứa các ID của chúng.
 *
 * Hàm đệ quy này sẽ gộp tất cả các con của $term vào cùng
 * một mảng ID term. Chỉ hữu ích cho các taxonomy phân cấp.
 *
 * Sẽ trả về mảng rỗng nếu $term không tồn tại trong $taxonomy.
 *
 * @since 2.3.0
 *
 * @param int    $term_id  ID của term cần lấy con.
 * @param string $taxonomy Tên taxonomy.
 * @return array|WP_Error Danh sách ID term. WP_Error được trả về nếu `$taxonomy` không tồn tại.
 */
function get_term_children( $term_id, $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$term_id = (int) $term_id;

	$terms = _get_term_hierarchy( $taxonomy );

	if ( ! isset( $terms[ $term_id ] ) ) {
		return array();
	}

	$children = $terms[ $term_id ];

	foreach ( (array) $terms[ $term_id ] as $child ) {
		if ( $term_id === $child ) {
			continue;
		}

		if ( isset( $terms[ $child ] ) ) {
			$children = array_merge( $children, get_term_children( $child, $taxonomy ) );
		}
	}

	return $children;
}

/**
 * Lấy trường term đã được làm sạch.
 *
 * Hàm này dùng cho mục đích ngữ cảnh và để đơn giản hóa việc sử dụng.
 *
 * @since 2.3.0
 * @since 4.4.0 Tham số `$taxonomy` được đặt là tùy chọn. `$term` giờ cũng chấp nhận đối tượng WP_Term.
 *
 * @see sanitize_term_field()
 *
 * @param string      $field    Trường term cần lấy.
 * @param int|WP_Term $term     ID term hoặc đối tượng.
 * @param string      $taxonomy Tùy chọn. Tên taxonomy. Mặc định rỗng.
 * @param string      $context  Tùy chọn. Cách làm sạch các trường term. Xem sanitize_term_field() để biết các tùy chọn.
 *                              Mặc định 'display'.
 * @return string|int|null|WP_Error Sẽ trả về chuỗi rỗng nếu $term không phải đối tượng hoặc nếu $field không được thiết lập trong $term.
 */
function get_term_field( $field, $term, $taxonomy = '', $context = 'display' ) {
	$term = get_term( $term, $taxonomy );
	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( ! is_object( $term ) ) {
		return '';
	}

	if ( ! isset( $term->$field ) ) {
		return '';
	}

	return sanitize_term_field( $field, $term->$field, $term->term_id, $term->taxonomy, $context );
}

/**
 * Làm sạch term để chỉnh sửa.
 *
 * Giá trị trả về là sanitize_term() và mục đích sử dụng là để làm sạch term cho
 * việc chỉnh sửa. Hàm này dùng cho mục đích ngữ cảnh và đơn giản hóa.
 *
 * @since 2.3.0
 *
 * @param int|object $id       ID term hoặc đối tượng.
 * @param string     $taxonomy Tên taxonomy.
 * @return string|int|null|WP_Error Sẽ trả về chuỗi rỗng nếu $term không phải đối tượng.
 */
function get_term_to_edit( $id, $taxonomy ) {
	$term = get_term( $id, $taxonomy );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( ! is_object( $term ) ) {
		return '';
	}

	return sanitize_term( $term, $taxonomy, 'edit' );
}

/**
 * Lấy các term trong một taxonomy hoặc danh sách taxonomy được cho.
 *
 * Bạn có thể hoàn toàn tùy chỉnh truy vấn trước khi nó được gửi, cũng
 * như kiểm soát đầu ra bằng bộ lọc.
 *
 * Kiểu trả về thay đổi tùy thuộc vào giá trị truyền vào `$args['fields']`. Xem
 * WP_Term_Query::get_terms() để biết chi tiết. Trong mọi trường hợp, đối tượng `WP_Error` sẽ
 * được trả về nếu yêu cầu taxonomy không hợp lệ.
 *
 * Bộ lọc {@see 'get_terms'} sẽ được gọi khi cache có term và sẽ
 * truyền term tìm thấy cùng với mảng $taxonomies và mảng $args.
 * Bộ lọc này cũng được gọi trước khi mảng term được truyền và sẽ truyền
 * mảng term, cùng với $taxonomies và $args.
 *
 * Bộ lọc {@see 'list_terms_exclusions'} truyền các loại trừ đã biên dịch cùng với
 * $args.
 *
 * Bộ lọc {@see 'get_terms_orderby'} truyền mệnh đề `ORDER BY` cho truy vấn
 * cùng với mảng $args.
 *
 * Taxonomy hoặc mảng taxonomy nên được truyền qua tham số 'taxonomy'
 * trong mảng `$args`:
 *
 *     $terms = get_terms( array(
 *         'taxonomy'   => 'post_tag',
 *         'hide_empty' => false,
 *     ) );
 *
 * Trước 4.5.0, taxonomy được truyền như tham số đầu tiên của `get_terms()`.
 *
 * @since 2.3.0
 * @since 4.2.0 Giới thiệu tham số 'name' và 'childless'.
 * @since 4.4.0 Giới thiệu khả năng truyền 'term_id' như bí danh của 'id' cho tham số `orderby`.
 *              Giới thiệu tham số 'meta_query' và 'update_term_meta_cache'. Chuyển đổi để trả về
 *              danh sách đối tượng WP_Term.
 * @since 4.5.0 Thay đổi chữ ký hàm để mảng `$args` có thể được cung cấp như tham số đầu tiên.
 *              Giới thiệu tham số 'meta_key' và 'meta_value'. Giới thiệu khả năng sắp xếp kết quả theo metadata.
 * @since 4.8.0 Giới thiệu tham số 'suppress_filter'.
 *
 * @internal Tham số `$deprecated` được phân tích chỉ cho mục đích tương thích ngược.
 *
 * @param array|string $args       Tùy chọn. Mảng hoặc chuỗi tham số. Xem WP_Term_Query::__construct()
 *                                 để biết thông tin về các tham số được chấp nhận. Mặc định mảng rỗng.
 * @param array|string $deprecated Tùy chọn. Mảng tham số, khi sử dụng định dạng tham số hàm cũ.
 *                                 Nếu có, tham số này sẽ được hiểu là `$args`, và tham số
 *                                 đầu tiên của hàm sẽ được phân tích như taxonomy hoặc mảng taxonomy.
 *                                 Mặc định rỗng.
 * @return WP_Term[]|int[]|string[]|string|WP_Error Mảng term, số đếm dưới dạng chuỗi số,
 *                                                  hoặc WP_Error nếu bất kỳ taxonomy nào không tồn tại.
 *                                                  Xem mô tả hàm để biết thêm thông tin.
 */
function get_terms( $args = array(), $deprecated = '' ) {
	$term_query = new WP_Term_Query();

	$defaults = array(
		'suppress_filter' => false,
	);

	/*
	 * Định dạng tham số cũ ($taxonomy, $args) được ưu tiên.
	 *
	 * Chúng ta phát hiện định dạng tham số cũ bằng cách kiểm tra xem
	 * (a) tham số thứ hai không rỗng được truyền vào, hoặc
	 * (b) tham số đầu tiên không có khóa chung với mảng mặc định (tức là nó là danh sách taxonomy)
	 */
	$_args          = wp_parse_args( $args );
	$key_intersect  = array_intersect_key( $term_query->query_var_defaults, (array) $_args );
	$do_legacy_args = $deprecated || empty( $key_intersect );

	if ( $do_legacy_args ) {
		$taxonomies       = (array) $args;
		$args             = wp_parse_args( $deprecated, $defaults );
		$args['taxonomy'] = $taxonomies;
	} else {
		$args = wp_parse_args( $args, $defaults );
		if ( isset( $args['taxonomy'] ) && null !== $args['taxonomy'] ) {
			$args['taxonomy'] = (array) $args['taxonomy'];
		}
	}

	if ( ! empty( $args['taxonomy'] ) ) {
		foreach ( $args['taxonomy'] as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
			}
		}
	}

	// Không truyền suppress_filter cho WP_Term_Query.
	$suppress_filter = $args['suppress_filter'];
	unset( $args['suppress_filter'] );

	$terms = $term_query->query( $args );

	// Truy vấn đếm không được lọc, vì lý do tương thích ngược.
	if ( ! is_array( $terms ) ) {
		return $terms;
	}

	if ( $suppress_filter ) {
		return $terms;
	}

	/**
	 * Lọc các term tìm thấy.
	 *
	 * @since 2.3.0
	 * @since 4.6.0 Thêm tham số `$term_query`.
	 *
	 * @param array         $terms      Mảng các term tìm thấy.
	 * @param array|null    $taxonomies Mảng taxonomy nếu biết.
	 * @param array         $args       Mảng tham số của get_terms().
	 * @param WP_Term_Query $term_query Đối tượng WP_Term_Query.
	 */
	return apply_filters( 'get_terms', $terms, $term_query->query_vars['taxonomy'], $term_query->query_vars, $term_query );
}

/**
 * Thêm metadata cho term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    ID term.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Giá trị metadata. Mảng và đối tượng được lưu dưới dạng dữ liệu serialize và
 *                           sẽ được trả về cùng kiểu khi lấy ra. Các kiểu dữ liệu khác sẽ
 *                           được lưu dưới dạng chuỗi trong cơ sở dữ liệu:
 *                           - false được lưu và trả về dưới dạng chuỗi rỗng ('')
 *                           - true được lưu và trả về dưới dạng '1'
 *                           - số (cả số nguyên và số thực) được lưu và trả về dưới dạng chuỗi
 *                           Phải có thể serialize nếu không phải kiểu vô hướng.
 * @param bool   $unique     Tùy chọn. Liệu khóa trùng có nên không được thêm hay không.
 *                           Mặc định false.
 * @return int|false|WP_Error ID meta khi thành công, false khi thất bại.
 *                            WP_Error khi term_id không rõ ràng giữa các taxonomy.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
	if ( wp_term_is_shared( $term_id ) ) {
		return new WP_Error( 'ambiguous_term_id', __( 'Term meta cannot be added to terms that are shared between taxonomies.' ), $term_id );
	}

	return add_metadata( 'term', $term_id, $meta_key, $meta_value, $unique );
}

/**
 * Xóa metadata khớp tiêu chí khỏi term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    ID term.
 * @param string $meta_key   Tên metadata.
 * @param mixed  $meta_value Tùy chọn. Giá trị metadata. Nếu được cung cấp,
 *                           chỉ các hàng khớp giá trị mới bị xóa.
 *                           Phải có thể serialize nếu không phải kiểu vô hướng. Mặc định rỗng.
 * @return bool True khi thành công, false khi thất bại.
 */
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'term', $term_id, $meta_key, $meta_value );
}

/**
 * Lấy metadata cho term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id ID term.
 * @param string $key     Tùy chọn. Khóa meta cần lấy. Mặc định,
 *                        trả về dữ liệu cho tất cả khóa. Mặc định rỗng.
 * @param bool   $single  Tùy chọn. Có trả về một giá trị duy nhất hay không.
 *                        Tham số này không có tác dụng nếu `$key` không được chỉ định.
 *                        Mặc định false.
 * @return mixed Mảng giá trị nếu `$single` là false.
 *               Giá trị của trường meta nếu `$single` là true.
 *               False cho `$term_id` không hợp lệ (không phải số, bằng không, hoặc giá trị âm).
 *               Mảng rỗng nếu ID term hợp lệ nhưng không tồn tại được truyền và `$single` là false.
 *               Chuỗi rỗng nếu ID term hợp lệ nhưng không tồn tại được truyền và `$single` là true.
 *               Lưu ý: Các giá trị không serialize được trả về dưới dạng chuỗi:
 *               - giá trị false được trả về dưới dạng chuỗi rỗng ('')
 *               - giá trị true được trả về dưới dạng '1'
 *               - số được trả về dưới dạng chuỗi
 *               Mảng và đối tượng giữ nguyên kiểu ban đầu.
 */
function get_term_meta( $term_id, $key = '', $single = false ) {
	return get_metadata( 'term', $term_id, $key, $single );
}

/**
 * Cập nhật metadata term.
 *
 * Sử dụng tham số `$prev_value` để phân biệt giữa các trường meta có cùng khóa và ID term.
 *
 * Nếu trường meta cho term không tồn tại, nó sẽ được thêm mới.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    ID term.
 * @param string $meta_key   Khóa metadata.
 * @param mixed  $meta_value Giá trị metadata. Phải có thể serialize nếu không phải kiểu vô hướng.
 * @param mixed  $prev_value Tùy chọn. Giá trị trước đó để kiểm tra trước khi cập nhật.
 *                           Nếu được chỉ định, chỉ cập nhật các mục metadata hiện có với
 *                           giá trị này. Nếu không, cập nhật tất cả mục. Mặc định rỗng.
 * @return int|bool|WP_Error ID meta nếu khóa không tồn tại. true khi cập nhật thành công,
 *                           false khi thất bại hoặc nếu giá trị truyền cho hàm
 *                           giống với giá trị đã có trong cơ sở dữ liệu.
 *                           WP_Error khi term_id không rõ ràng giữa các taxonomy.
 */
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	if ( wp_term_is_shared( $term_id ) ) {
		return new WP_Error( 'ambiguous_term_id', __( 'Term meta cannot be added to terms that are shared between taxonomies.' ), $term_id );
	}

	return update_metadata( 'term', $term_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Cập nhật cache metadata cho danh sách ID term.
 *
 * Thực hiện truy vấn SQL để lấy tất cả metadata cho các term khớp `$term_ids` và lưu vào cache.
 * Các lần gọi `get_term_meta()` tiếp theo sẽ không cần truy vấn cơ sở dữ liệu.
 *
 * @since 4.4.0
 *
 * @param array $term_ids Danh sách ID term.
 * @return array|false Mảng metadata khi thành công, false nếu không có gì để cập nhật.
 */
function update_termmeta_cache( $term_ids ) {
	return update_meta_cache( 'term', $term_ids );
}


/**
 * Xếp hàng meta term để tải lười.
 *
 * @since 6.3.0
 *
 * @param array $term_ids Danh sách ID term.
 */
function wp_lazyload_term_meta( array $term_ids ) {
	if ( empty( $term_ids ) ) {
		return;
	}
	$lazyloader = wp_metadata_lazyloader();
	$lazyloader->queue_objects( 'term', $term_ids );
}

/**
 * Lấy tất cả dữ liệu meta, bao gồm ID meta, cho ID term được cho.
 *
 * @since 4.9.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int $term_id ID term.
 * @return array|false Mảng chứa dữ liệu meta, hoặc false khi bảng meta chưa được cài đặt.
 */
function has_term_meta( $term_id ) {
	$check = wp_check_term_meta_support_prefilter( null );
	if ( null !== $check ) {
		return $check;
	}

	global $wpdb;

	return $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id, term_id FROM $wpdb->termmeta WHERE term_id = %d ORDER BY meta_key,meta_id", $term_id ), ARRAY_A );
}

/**
 * Đăng ký khóa meta cho term.
 *
 * @since 4.9.8
 *
 * @param string $taxonomy Taxonomy để đăng ký khóa meta. Truyền chuỗi rỗng
 *                         để đăng ký khóa meta cho tất cả taxonomy hiện có.
 * @param string $meta_key Khóa meta cần đăng ký.
 * @param array  $args     Dữ liệu dùng để mô tả khóa meta khi đăng ký. Xem
 *                         {@see register_meta()} để biết danh sách tham số được hỗ trợ.
 * @return bool True nếu khóa meta được đăng ký thành công, false nếu không.
 */
function register_term_meta( $taxonomy, $meta_key, array $args ) {
	$args['object_subtype'] = $taxonomy;

	return register_meta( 'term', $meta_key, $args );
}

/**
 * Hủy đăng ký khóa meta cho term.
 *
 * @since 4.9.8
 *
 * @param string $taxonomy Taxonomy mà khóa meta hiện đang được đăng ký. Truyền
 *                         chuỗi rỗng nếu khóa meta được đăng ký cho tất cả
 *                         taxonomy hiện có.
 * @param string $meta_key Khóa meta cần hủy đăng ký.
 * @return bool True khi thành công, false nếu khóa meta chưa được đăng ký trước đó.
 */
function unregister_term_meta( $taxonomy, $meta_key ) {
	return unregister_meta_key( 'term', $meta_key, $taxonomy );
}

/**
 * Xác định xem một term taxonomy có tồn tại hay không.
 *
 * Trước đây là is_term(), được giới thiệu từ 2.3.0.
 *
 * Để biết thêm thông tin về hàm này và các hàm theme tương tự, hãy xem
 * bài viết {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} trong Sổ tay lập trình Theme.
 *
 * @since 3.0.0
 * @since 6.0.0 Chuyển đổi để sử dụng `get_terms()`.
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|string $term        Term cần kiểm tra. Chấp nhận ID term, slug, hoặc tên.
 * @param string     $taxonomy    Tùy chọn. Tên taxonomy để sử dụng.
 * @param int        $parent_term Tùy chọn. ID của term cha để giới hạn phạm vi tìm kiếm.
 * @return mixed Trả về null nếu term không tồn tại.
 *               Trả về ID term nếu không chỉ định taxonomy và ID term tồn tại.
 *               Trả về mảng gồm ID term và ID term taxonomy nếu taxonomy được chỉ định và cặp tồn tại.
 *               Trả về 0 nếu ID term 0 được truyền cho hàm.
 */
function term_exists( $term, $taxonomy = '', $parent_term = null ) {
	global $_wp_suspend_cache_invalidation;

	if ( null === $term ) {
		return null;
	}

	$defaults = array(
		'get'                    => 'all',
		'fields'                 => 'ids',
		'number'                 => 1,
		'update_term_meta_cache' => false,
		'order'                  => 'ASC',
		'orderby'                => 'term_id',
		'suppress_filter'        => true,
	);

	// Đảm bảo rằng trong quá trình nhập, các truy vấn không được cache.
	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		$defaults['cache_results'] = false;
	}

	if ( ! empty( $taxonomy ) ) {
		$defaults['taxonomy'] = $taxonomy;
		$defaults['fields']   = 'all';
	}

	/**
	 * Lọc các tham số truy vấn mặc định để kiểm tra xem term có tồn tại hay không.
	 *
	 * @since 6.0.0
	 *
	 * @param array      $defaults    Mảng tham số được truyền cho get_terms().
	 * @param int|string $term        Term cần kiểm tra. Chấp nhận ID term, slug, hoặc tên.
	 * @param string     $taxonomy    Tên taxonomy để sử dụng. Chuỗi rỗng cho biết
	 *                                tìm kiếm trên tất cả taxonomy.
	 * @param int|null   $parent_term ID của term cha để giới hạn phạm vi tìm kiếm.
	 *                                Null cho biết tìm kiếm không bị giới hạn.
	 */
	$defaults = apply_filters( 'term_exists_default_query_args', $defaults, $term, $taxonomy, $parent_term );

	if ( is_int( $term ) ) {
		if ( 0 === $term ) {
			return 0;
		}
		$args  = wp_parse_args( array( 'include' => array( $term ) ), $defaults );
		$terms = get_terms( $args );
	} else {
		$term = trim( wp_unslash( $term ) );
		if ( '' === $term ) {
			return null;
		}

		if ( ! empty( $taxonomy ) && is_numeric( $parent_term ) ) {
			$defaults['parent'] = (int) $parent_term;
		}

		$args  = wp_parse_args( array( 'slug' => sanitize_title( $term ) ), $defaults );
		$terms = get_terms( $args );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			$args  = wp_parse_args( array( 'name' => $term ), $defaults );
			$terms = get_terms( $args );
		}
	}

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	$_term = array_shift( $terms );

	if ( ! empty( $taxonomy ) ) {
		return array(
			'term_id'          => (string) $_term->term_id,
			'term_taxonomy_id' => (string) $_term->term_taxonomy_id,
		);
	}

	return (string) $_term;
}

/**
 * Kiểm tra xem một term có phải là tổ tiên của term khác hay không.
 *
 * Bạn có thể sử dụng ID hoặc đối tượng term cho cả hai tham số.
 *
 * @since 3.4.0
 *
 * @param int|object $term1    ID hoặc đối tượng để kiểm tra xem đây có phải term cha hay không.
 * @param int|object $term2    Term con.
 * @param string     $taxonomy Tên taxonomy mà $term1 và `$term2` thuộc về.
 * @return bool Liệu `$term2` có phải con của `$term1` hay không.
 */
function term_is_ancestor_of( $term1, $term2, $taxonomy ) {
	if ( ! isset( $term1->term_id ) ) {
		$term1 = get_term( $term1, $taxonomy );
	}
	if ( ! isset( $term2->parent ) ) {
		$term2 = get_term( $term2, $taxonomy );
	}

	if ( empty( $term1->term_id ) || empty( $term2->parent ) ) {
		return false;
	}
	if ( $term2->parent === $term1->term_id ) {
		return true;
	}

	return term_is_ancestor_of( $term1, get_term( $term2->parent, $taxonomy ), $taxonomy );
}

/**
 * Làm sạch tất cả trường term.
 *
 * Dựa vào sanitize_term_field() để làm sạch term. Sự khác biệt là
 * hàm này sẽ làm sạch **tất cả** trường. Ngữ cảnh dựa trên
 * sanitize_term_field().
 *
 * `$term` được kỳ vọng là mảng hoặc đối tượng.
 *
 * @since 2.3.0
 *
 * @param array|object $term     Term cần kiểm tra.
 * @param string       $taxonomy Tên taxonomy để sử dụng.
 * @param string       $context  Tùy chọn. Ngữ cảnh để làm sạch term.
 *                               Chấp nhận 'raw', 'edit', 'db', 'display', 'rss',
 *                               'attribute', hoặc 'js'. Mặc định 'display'.
 * @return array|object Term với tất cả trường đã được làm sạch.
 */
function sanitize_term( $term, $taxonomy, $context = 'display' ) {
	$fields = array( 'term_id', 'name', 'description', 'slug', 'count', 'parent', 'term_group', 'term_taxonomy_id', 'object_id' );

	$do_object = is_object( $term );

	$term_id = $do_object ? $term->term_id : ( isset( $term['term_id'] ) ? $term['term_id'] : 0 );

	foreach ( (array) $fields as $field ) {
		if ( $do_object ) {
			if ( isset( $term->$field ) ) {
				$term->$field = sanitize_term_field( $field, $term->$field, $term_id, $taxonomy, $context );
			}
		} else {
			if ( isset( $term[ $field ] ) ) {
				$term[ $field ] = sanitize_term_field( $field, $term[ $field ], $term_id, $taxonomy, $context );
			}
		}
	}

	if ( $do_object ) {
		$term->filter = $context;
	} else {
		$term['filter'] = $context;
	}

	return $term;
}

/**
 * Làm sạch giá trị trường trong term dựa trên ngữ cảnh.
 *
 * Truyền giá trị trường term qua hàm này được coi là đã
 * làm sạch giá trị cho bất kỳ ngữ cảnh nào mà trường term sẽ được sử dụng.
 *
 * Nếu không có ngữ cảnh hoặc ngữ cảnh không được hỗ trợ, các bộ lọc mặc định sẽ
 * được áp dụng.
 *
 * Có đủ bộ lọc cho mỗi ngữ cảnh để hỗ trợ lọc tùy chỉnh
 * mà không cần tạo hàm lọc riêng. Chỉ cần tạo hàm
 * hook vào bộ lọc bạn cần.
 *
 * @since 2.3.0
 *
 * @param string $field    Trường term cần làm sạch.
 * @param string $value    Tìm kiếm giá trị term này.
 * @param int    $term_id  ID term.
 * @param string $taxonomy Tên taxonomy.
 * @param string $context  Ngữ cảnh để làm sạch trường term.
 *                         Chấp nhận 'raw', 'edit', 'db', 'display', 'rss',
 *                         'attribute', hoặc 'js'. Mặc định 'display'.
 * @return mixed Trường đã được làm sạch.
 */
function sanitize_term_field( $field, $value, $term_id, $taxonomy, $context ) {
	$int_fields = array( 'parent', 'term_id', 'count', 'term_group', 'term_taxonomy_id', 'object_id' );
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
		if ( $value < 0 ) {
			$value = 0;
		}
	}

	$context = strtolower( $context );

	if ( 'raw' === $context ) {
		return $value;
	}

	if ( 'edit' === $context ) {

		/**
		 * Lọc trường term để chỉnh sửa trước khi nó được làm sạch.
		 *
		 * Phần động của tên hook, `$field`, tham chiếu đến trường term.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value     Giá trị của trường term.
		 * @param int   $term_id   ID term.
		 * @param string $taxonomy Slug taxonomy.
		 */
		$value = apply_filters( "edit_term_{$field}", $value, $term_id, $taxonomy );

		/**
		 * Lọc trường taxonomy để chỉnh sửa trước khi nó được làm sạch.
		 *
		 * Các phần động của tên bộ lọc, `$taxonomy` và `$field`, tham chiếu
		 * đến slug taxonomy và trường taxonomy, tương ứng.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value   Giá trị của trường taxonomy cần chỉnh sửa.
		 * @param int   $term_id ID term.
		 */
		$value = apply_filters( "edit_{$taxonomy}_{$field}", $value, $term_id );

		if ( 'description' === $field ) {
			$value = esc_html( $value ); // textarea_escaped
		} else {
			$value = esc_attr( $value );
		}
	} elseif ( 'db' === $context ) {

		/**
		 * Lọc giá trị trường term trước khi nó được làm sạch.
		 *
		 * Phần động của tên hook, `$field`, tham chiếu đến trường term.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Giá trị của trường term.
		 * @param string $taxonomy Slug taxonomy.
		 */
		$value = apply_filters( "pre_term_{$field}", $value, $taxonomy );

		/**
		 * Lọc trường taxonomy trước khi nó được làm sạch.
		 *
		 * Các phần động của tên bộ lọc, `$taxonomy` và `$field`, tham chiếu
		 * đến slug taxonomy và tên trường, tương ứng.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value Giá trị của trường taxonomy.
		 */
		$value = apply_filters( "pre_{$taxonomy}_{$field}", $value );

		// Bộ lọc tương thích ngược.
		if ( 'slug' === $field ) {
			/**
			 * Lọc nicename danh mục trước khi nó được làm sạch.
			 *
			 * Sử dụng hook {@see 'pre_$taxonomy_$field'} thay thế.
			 *
			 * @since 2.0.3
			 *
			 * @param string $value Nicename danh mục.
			 */
			$value = apply_filters( 'pre_category_nicename', $value );
		}
	} elseif ( 'rss' === $context ) {

		/**
		 * Lọc trường term để sử dụng trong RSS.
		 *
		 * Phần động của tên hook, `$field`, tham chiếu đến trường term.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Giá trị của trường term.
		 * @param string $taxonomy Slug taxonomy.
		 */
		$value = apply_filters( "term_{$field}_rss", $value, $taxonomy );

		/**
		 * Lọc trường taxonomy để sử dụng trong RSS.
		 *
		 * Các phần động của tên hook, `$taxonomy` và `$field`, tham chiếu
		 * đến slug taxonomy và tên trường, tương ứng.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value Giá trị của trường taxonomy.
		 */
		$value = apply_filters( "{$taxonomy}_{$field}_rss", $value );
	} else {
		// Sử dụng bộ lọc hiển thị theo mặc định.

		/**
		 * Lọc trường term đã được làm sạch để hiển thị.
		 *
		 * Phần động của tên hook, `$field`, tham chiếu đến tên trường term.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Giá trị của trường term.
		 * @param int    $term_id  ID term.
		 * @param string $taxonomy Slug taxonomy.
		 * @param string $context  Ngữ cảnh để lấy giá trị trường term.
		 */
		$value = apply_filters( "term_{$field}", $value, $term_id, $taxonomy, $context );

		/**
		 * Lọc trường taxonomy đã được làm sạch để hiển thị.
		 *
		 * Các phần động của tên bộ lọc, `$taxonomy` và `$field`, tham chiếu
		 * đến slug taxonomy và trường taxonomy, tương ứng.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value   Giá trị của trường taxonomy.
		 * @param int    $term_id ID term.
		 * @param string $context Ngữ cảnh để lấy giá trị trường taxonomy.
		 */
		$value = apply_filters( "{$taxonomy}_{$field}", $value, $term_id, $context );
	}

	if ( 'attribute' === $context ) {
		$value = esc_attr( $value );
	} elseif ( 'js' === $context ) {
		$value = esc_js( $value );
	}

	// Khôi phục kiểu cho các trường số nguyên sau esc_attr().
	if ( in_array( $field, $int_fields, true ) ) {
		$value = (int) $value;
	}

	return $value;
}

/**
 * Đếm số lượng term trong taxonomy.
 *
 * $args mặc định là 'hide_empty' có thể là 'hide_empty=true' hoặc array('hide_empty' => true).
 *
 * @since 2.3.0
 * @since 5.6.0 Thay đổi chữ ký hàm để mảng `$args` có thể được cung cấp như tham số đầu tiên.
 *
 * @internal Tham số `$deprecated` được phân tích chỉ cho mục đích tương thích ngược.
 *
 * @param array|string $args       Tùy chọn. Mảng hoặc chuỗi tham số. Xem WP_Term_Query::__construct()
 *                                 để biết thông tin về các tham số được chấp nhận. Mặc định mảng rỗng.
 * @param array|string $deprecated Tùy chọn. Mảng tham số, khi sử dụng định dạng tham số hàm cũ.
 *                                 Nếu có, tham số này sẽ được hiểu là `$args`, và tham số
 *                                 đầu tiên của hàm sẽ được phân tích như taxonomy hoặc mảng taxonomy.
 *                                 Mặc định rỗng.
 * @return string|WP_Error Chuỗi số chứa số lượng term trong
 *                         taxonomy đó hoặc WP_Error nếu taxonomy không tồn tại.
 */
function wp_count_terms( $args = array(), $deprecated = '' ) {
	$use_legacy_args = false;

	// Kiểm tra xem hàm có đang được sử dụng với chữ ký cũ: `$taxonomy` và `$args` hay không.
	if ( $args
		&& ( is_string( $args ) && taxonomy_exists( $args )
			|| is_array( $args ) && wp_is_numeric_array( $args ) )
	) {
		$use_legacy_args = true;
	}

	$defaults = array( 'hide_empty' => false );

	if ( $use_legacy_args ) {
		$defaults['taxonomy'] = $args;
		$args                 = $deprecated;
	}

	$args = wp_parse_args( $args, $defaults );

	// Tương thích ngược.
	if ( isset( $args['ignore_empty'] ) ) {
		$args['hide_empty'] = $args['ignore_empty'];
		unset( $args['ignore_empty'] );
	}

	$args['fields'] = 'count';

	return get_terms( $args );
}

/**
 * Gỡ liên kết đối tượng khỏi taxonomy hoặc các taxonomy.
 *
 * Sẽ xóa tất cả mối quan hệ giữa đối tượng và bất kỳ term nào trong
 * taxonomy hoặc các taxonomy cụ thể. Không xóa bản thân term hoặc
 * taxonomy.
 *
 * @since 2.3.0
 *
 * @param int          $object_id  ID đối tượng term tham chiếu đến term.
 * @param string|array $taxonomies Danh sách tên taxonomy hoặc tên taxonomy đơn.
 */
function wp_delete_object_term_relationships( $object_id, $taxonomies ) {
	$object_id = (int) $object_id;

	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}

	foreach ( (array) $taxonomies as $taxonomy ) {
		$term_ids = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
		$term_ids = array_map( 'intval', $term_ids );
		wp_remove_object_terms( $object_id, $term_ids, $taxonomy );
	}
}

/**
 * Xóa một term khỏi cơ sở dữ liệu.
 *
 * Nếu term là cha của các term khác, thì các con sẽ được cập nhật về
 * cha của term đó.
 *
 * Metadata liên kết với term sẽ bị xóa.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int          $term     ID term.
 * @param string       $taxonomy Tên taxonomy.
 * @param array|string $args {
 *     Tùy chọn. Mảng tham số để ghi đè ID term mặc định. Mặc định mảng rỗng.
 *
 *     @type int  $default       ID term để đặt làm term mặc định. Chỉ ghi đè
 *                               các term tìm thấy nếu chỉ có một term được tìm thấy. Nếu khác,
 *                               các term tìm thấy sẽ được sử dụng.
 *     @type bool $force_default Tùy chọn. Có ép buộc term được cung cấp làm mặc định được
 *                               gán ngay cả khi đối tượng không bị mất term hay không.
 *                               Mặc định false.
 * }
 * @return bool|int|WP_Error True khi thành công, false nếu term không tồn tại. Zero khi cố
 *                           xóa Category mặc định. WP_Error nếu taxonomy không tồn tại.
 */
function wp_delete_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	$term = (int) $term;

	$ids = term_exists( $term, $taxonomy );
	if ( ! $ids ) {
		return false;
	}
	if ( is_wp_error( $ids ) ) {
		return $ids;
	}

	$tt_id = $ids['term_taxonomy_id'];

	$defaults = array();

	if ( 'category' === $taxonomy ) {
		$defaults['default'] = (int) get_option( 'default_category' );
		if ( $defaults['default'] === $term ) {
			return 0; // Không xóa danh mục mặc định.
		}
	}

	// Không xóa term mặc định của taxonomy tùy chỉnh.
	$taxonomy_object = get_taxonomy( $taxonomy );
	if ( ! empty( $taxonomy_object->default_term ) ) {
		$defaults['default'] = (int) get_option( 'default_term_' . $taxonomy );
		if ( $defaults['default'] === $term ) {
			return 0;
		}
	}

	$args = wp_parse_args( $args, $defaults );

	if ( isset( $args['default'] ) ) {
		$default = (int) $args['default'];
		if ( ! term_exists( $default, $taxonomy ) ) {
			unset( $default );
		}
	}

	if ( isset( $args['force_default'] ) ) {
		$force_default = $args['force_default'];
	}

	/**
	 * Kích hoạt khi xóa term, trước khi bất kỳ thay đổi nào được thực hiện với bài viết hoặc term.
	 *
	 * @since 4.1.0
	 *
	 * @param int    $term     ID term.
	 * @param string $taxonomy Tên taxonomy.
	 */
	do_action( 'pre_delete_term', $term, $taxonomy );

	// Cập nhật các con để trỏ đến cha mới.
	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		$term_obj = get_term( $term, $taxonomy );
		if ( is_wp_error( $term_obj ) ) {
			return $term_obj;
		}
		$parent = $term_obj->parent;

		$edit_ids    = $wpdb->get_results( "SELECT term_id, term_taxonomy_id FROM $wpdb->term_taxonomy WHERE `parent` = " . (int) $term_obj->term_id );
		$edit_tt_ids = wp_list_pluck( $edit_ids, 'term_taxonomy_id' );

		/**
		 * Kích hoạt ngay trước khi các con của term sắp xóa được gán lại cha.
		 *
		 * @since 2.9.0
		 *
		 * @param array $edit_tt_ids Mảng ID term taxonomy cho term được cho.
		 */
		do_action( 'edit_term_taxonomies', $edit_tt_ids );

		$wpdb->update( $wpdb->term_taxonomy, compact( 'parent' ), array( 'parent' => $term_obj->term_id ) + compact( 'taxonomy' ) );

		// Xóa cache cho tất cả term con.
		$edit_term_ids = wp_list_pluck( $edit_ids, 'term_id' );
		clean_term_cache( $edit_term_ids, $taxonomy );

		/**
		 * Kích hoạt ngay sau khi các con của term sắp xóa được gán lại cha.
		 *
		 * @since 2.9.0
		 *
		 * @param array $edit_tt_ids Mảng ID term taxonomy cho term được cho.
		 */
		do_action( 'edited_term_taxonomies', $edit_tt_ids );
	}

	// Lấy term trước khi xóa nó hoặc các mối quan hệ term để có thể truyền cho các action bên dưới.
	$deleted_term = get_term( $term, $taxonomy );

	$object_ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $tt_id ) );

	foreach ( $object_ids as $object_id ) {
		if ( ! isset( $default ) ) {
			wp_remove_object_terms( $object_id, $term, $taxonomy );
			continue;
		}

		$terms = wp_get_object_terms(
			$object_id,
			$taxonomy,
			array(
				'fields'  => 'ids',
				'orderby' => 'none',
			)
		);

		if ( 1 === count( $terms ) && isset( $default ) ) {
			$terms = array( $default );
		} else {
			$terms = array_diff( $terms, array( $term ) );
			if ( isset( $default ) && isset( $force_default ) && $force_default ) {
				$terms = array_merge( $terms, array( $default ) );
			}
		}

		$terms = array_map( 'intval', $terms );
		wp_set_object_terms( $object_id, $terms, $taxonomy );
	}

	// Xóa cache mối quan hệ cho tất cả loại đối tượng sử dụng term này.
	$tax_object = get_taxonomy( $taxonomy );
	foreach ( $tax_object->object_type as $object_type ) {
		clean_object_term_cache( $object_ids, $object_type );
	}

	$term_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->termmeta WHERE term_id = %d ", $term ) );
	foreach ( $term_meta_ids as $mid ) {
		delete_metadata_by_mid( 'term', $mid );
	}

	/**
	 * Kích hoạt ngay trước khi ID term taxonomy bị xóa.
	 *
	 * @since 2.9.0
	 *
	 * @param int $tt_id ID term taxonomy.
	 */
	do_action( 'delete_term_taxonomy', $tt_id );

	$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $tt_id ) );

	/**
	 * Kích hoạt ngay sau khi ID term taxonomy bị xóa.
	 *
	 * @since 2.9.0
	 *
	 * @param int $tt_id ID term taxonomy.
	 */
	do_action( 'deleted_term_taxonomy', $tt_id );

	// Xóa term nếu không có taxonomy nào sử dụng nó.
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term ) ) ) {
		$wpdb->delete( $wpdb->terms, array( 'term_id' => $term ) );
	}

	clean_term_cache( $term, $taxonomy );

	/**
	 * Kích hoạt sau khi term bị xóa khỏi cơ sở dữ liệu và cache được dọn sạch.
	 *
	 * Hook {@see 'delete_$taxonomy'} cũng có sẵn để nhắm mục tiêu một taxonomy
	 * cụ thể.
	 *
	 * @since 2.5.0
	 * @since 4.5.0 Giới thiệu tham số `$object_ids`.
	 *
	 * @param int     $term         ID term.
	 * @param int     $tt_id        ID term taxonomy.
	 * @param string  $taxonomy     Slug taxonomy.
	 * @param WP_Term $deleted_term Bản sao của term đã bị xóa.
	 * @param array   $object_ids   Danh sách ID đối tượng term.
	 */
	do_action( 'delete_term', $term, $tt_id, $taxonomy, $deleted_term, $object_ids );

	/**
	 * Kích hoạt sau khi term trong một taxonomy cụ thể bị xóa.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu đến taxonomy cụ thể
	 * mà term thuộc về.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `delete_category`
	 *  - `delete_post_tag`
	 *
	 * @since 2.3.0
	 * @since 4.5.0 Giới thiệu tham số `$object_ids`.
	 *
	 * @param int     $term         ID term.
	 * @param int     $tt_id        ID term taxonomy.
	 * @param WP_Term $deleted_term Bản sao của term đã bị xóa.
	 * @param array   $object_ids   Danh sách ID đối tượng term.
	 */
	do_action( "delete_{$taxonomy}", $term, $tt_id, $deleted_term, $object_ids );

	return true;
}

/**
 * Xóa một danh mục hiện có.
 *
 * @since 2.0.0
 *
 * @param int $cat_id ID term danh mục.
 * @return bool|int|WP_Error Trả về true nếu hoàn thành hành động xóa; false nếu term không tồn tại;
 *                           Zero khi cố xóa Category mặc định; đối tượng WP_Error cũng
 *                           có thể xảy ra.
 */
function wp_delete_category( $cat_id ) {
	return wp_delete_term( $cat_id, 'category' );
}

/**
 * Lấy các term liên kết với đối tượng được cho, trong các taxonomy được cung cấp.
 *
 * @since 2.3.0
 * @since 4.2.0 Thêm hỗ trợ cho giá trị 'taxonomy', 'parent', và 'term_taxonomy_id' của `$orderby`.
 *              Giới thiệu tham số `$parent`.
 * @since 4.4.0 Giới thiệu tham số `$meta_query` và `$update_term_meta_cache`. Khi `$fields` là 'all' hoặc
 *              'all_with_object_id', mảng đối tượng `WP_Term` sẽ được trả về.
 * @since 4.7.0 Tái cấu trúc để sử dụng WP_Term_Query, và hỗ trợ bất kỳ tham số WP_Term_Query nào.
 * @since 6.3.0 Truyền giá trị tham số `update_term_meta_cache` là false theo mặc định khiến get_terms() không
 *              nạp trước cache meta term.
 *
 * @param int|int[]       $object_ids (Các) ID của (các) đối tượng cần lấy.
 * @param string|string[] $taxonomies Tên taxonomy để lấy term từ đó.
 * @param array|string    $args       Xem WP_Term_Query::__construct() để biết các tham số được hỗ trợ.
 * @return WP_Term[]|int[]|string[]|string|WP_Error Mảng term, số đếm dưới dạng chuỗi số,
 *                                                  hoặc WP_Error nếu bất kỳ taxonomy nào không tồn tại.
 *                                                  Xem WP_Term_Query::get_terms() để biết thêm thông tin.
 */
function wp_get_object_terms( $object_ids, $taxonomies, $args = array() ) {
	if ( empty( $object_ids ) || empty( $taxonomies ) ) {
		return array();
	}

	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
		}
	}

	if ( ! is_array( $object_ids ) ) {
		$object_ids = array( $object_ids );
	}
	$object_ids = array_map( 'intval', $object_ids );

	$defaults = array(
		'update_term_meta_cache' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * Lọc các tham số để lấy term của đối tượng.
	 *
	 * @since 4.9.0
	 *
	 * @param array    $args       Mảng tham số để lấy term cho (các) đối tượng được cho.
	 *                             Xem {@see wp_get_object_terms()} để biết chi tiết.
	 * @param int[]    $object_ids Mảng ID đối tượng.
	 * @param string[] $taxonomies Mảng tên taxonomy để lấy term từ đó.
	 */
	$args = apply_filters( 'wp_get_object_terms_args', $args, $object_ids, $taxonomies );

	/*
	 * Khi một hoặc nhiều taxonomy được truy vấn được đăng ký với mảng 'args',
	 * các tham số đó ghi đè `$args` được truyền cho hàm này.
	 */
	$terms = array();
	if ( count( $taxonomies ) > 1 ) {
		foreach ( $taxonomies as $index => $taxonomy ) {
			$t = get_taxonomy( $taxonomy );
			if ( isset( $t->args ) && is_array( $t->args ) && array_merge( $args, $t->args ) != $args ) {
				unset( $taxonomies[ $index ] );
				$terms = array_merge( $terms, wp_get_object_terms( $object_ids, $taxonomy, array_merge( $args, $t->args ) ) );
			}
		}
	} else {
		$t = get_taxonomy( $taxonomies[0] );
		if ( isset( $t->args ) && is_array( $t->args ) ) {
			$args = array_merge( $args, $t->args );
		}
	}

	$args['taxonomy']   = $taxonomies;
	$args['object_ids'] = $object_ids;

	// Các taxonomy được đăng ký không có tham số 'args' được xử lý ở đây.
	if ( ! empty( $taxonomies ) ) {
		$terms_from_remaining_taxonomies = get_terms( $args );

		// Khóa mảng nên được giữ nguyên cho các giá trị $fields sử dụng term_id làm khóa.
		if ( ! empty( $args['fields'] ) && str_starts_with( $args['fields'], 'id=>' ) ) {
			$terms = $terms + $terms_from_remaining_taxonomies;
		} else {
			$terms = array_merge( $terms, $terms_from_remaining_taxonomies );
		}
	}

	/**
	 * Lọc các term cho một đối tượng hoặc nhiều đối tượng được cho.
	 *
	 * @since 4.2.0
	 *
	 * @param WP_Term[]|int[]|string[]|string $terms      Mảng term hoặc số đếm dưới dạng chuỗi số.
	 * @param int[]                           $object_ids Mảng ID đối tượng mà term được lấy cho.
	 * @param string[]                        $taxonomies Mảng tên taxonomy mà term được lấy từ đó.
	 * @param array                           $args       Mảng tham số để lấy term cho (các)
	 *                                                    đối tượng. Xem wp_get_object_terms() để biết chi tiết.
	 */
	$terms = apply_filters( 'get_object_terms', $terms, $object_ids, $taxonomies, $args );

	$object_ids = implode( ',', $object_ids );
	$taxonomies = "'" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "'";

	/**
	 * Lọc các term cho một đối tượng hoặc nhiều đối tượng được cho.
	 *
	 * Tham số `$taxonomies` được truyền cho bộ lọc này được định dạng như đoạn SQL. Bộ lọc
	 * {@see 'get_object_terms'} được khuyến nghị như giải pháp thay thế.
	 *
	 * @since 2.8.0
	 *
	 * @param WP_Term[]|int[]|string[]|string $terms      Mảng term hoặc số đếm dưới dạng chuỗi số.
	 * @param string                          $object_ids Danh sách ID đối tượng phân cách bằng dấu phẩy.
	 * @param string                          $taxonomies Đoạn SQL chứa tên taxonomy mà term được lấy từ đó.
	 * @param array                           $args       Mảng tham số để lấy term cho (các)
	 *                                                    đối tượng. Xem wp_get_object_terms() để biết chi tiết.
	 */
	return apply_filters( 'wp_get_object_terms', $terms, $object_ids, $taxonomies, $args );
}

/**
 * Thêm term mới vào cơ sở dữ liệu.
 *
 * Một term chưa tồn tại được chèn theo trình tự sau:
 * 1. Term được thêm vào bảng term, sau đó liên kết với taxonomy.
 * 2. Nếu mọi thứ đúng, một số action được kích hoạt.
 * 3. 'term_id_filter' được đánh giá.
 * 4. Cache term được dọn sạch.
 * 5. Một số action khác được kích hoạt.
 * 6. Mảng chứa `term_id` và `term_taxonomy_id` được trả về.
 *
 * Nếu tham số 'slug' không rỗng, nó sẽ được kiểm tra xem term
 * có hợp lệ hay không. Nếu không phải term hợp lệ đã tồn tại, nó được thêm và term_id
 * được cấp.
 *
 * Nếu taxonomy phân cấp, và tham số 'parent' không rỗng,
 * term được chèn và term_id sẽ được cấp.
 *
 * Xử lý lỗi:
 * Nếu `$taxonomy` không tồn tại hoặc `$term` rỗng,
 * đối tượng WP_Error sẽ được trả về.
 *
 * Nếu term đã tồn tại ở cùng cấp phân cấp,
 * hoặc slug và tên term không duy nhất, đối tượng WP_Error sẽ được trả về.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @since 2.3.0
 *
 * @param string       $term     Tên term cần thêm.
 * @param string       $taxonomy Taxonomy để thêm term vào.
 * @param array|string $args {
 *     Tùy chọn. Mảng hoặc chuỗi query string của tham số để chèn term.
 *
 *     @type string $alias_of    Slug của term để biến term này thành bí danh.
 *                               Mặc định chuỗi rỗng. Chấp nhận slug term.
 *     @type string $description Mô tả term. Mặc định chuỗi rỗng.
 *     @type int    $parent      ID của term cha. Mặc định 0.
 *     @type string $slug        Slug term cần sử dụng. Mặc định chuỗi rỗng.
 * }
 * @return array|WP_Error {
 *     Mảng dữ liệu term mới, WP_Error nếu ngược lại.
 *
 *     @type int        $term_id          ID term mới.
 *     @type int|string $term_taxonomy_id ID term taxonomy mới. Có thể là chuỗi số.
 * }
 */
function wp_insert_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	/**
	 * Lọc term trước khi nó được làm sạch và chèn vào cơ sở dữ liệu.
	 *
	 * @since 3.0.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param string|WP_Error $term     Tên term cần thêm, hoặc đối tượng WP_Error nếu có lỗi.
	 * @param string          $taxonomy Slug taxonomy.
	 * @param array|string    $args     Mảng hoặc chuỗi query string của tham số được truyền cho wp_insert_term().
	 */
	$term = apply_filters( 'pre_insert_term', $term, $taxonomy, $args );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( is_int( $term ) && 0 === $term ) {
		return new WP_Error( 'invalid_term_id', __( 'Invalid term ID.' ) );
	}

	if ( '' === trim( $term ) ) {
		return new WP_Error( 'empty_term_name', __( 'A name is required for this term.' ) );
	}

	$defaults = array(
		'alias_of'    => '',
		'description' => '',
		'parent'      => 0,
		'slug'        => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	if ( (int) $args['parent'] > 0 && ! term_exists( (int) $args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}

	$args['name']     = $term;
	$args['taxonomy'] = $taxonomy;

	// Ép kiểu mô tả null thành chuỗi, để tránh lỗi cơ sở dữ liệu.
	$args['description'] = (string) $args['description'];

	$args = sanitize_term( $args, $taxonomy, 'db' );

	// expected_slashed ($name)
	$name        = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );
	$parent      = (int) $args['parent'];

	// Quá trình làm sạch có thể làm tên thành chuỗi rỗng, cần kiểm tra lại.
	if ( '' === $name ) {
		return new WP_Error( 'invalid_term_name', __( 'Invalid term name.' ) );
	}

	$slug_provided = ! empty( $args['slug'] );
	if ( ! $slug_provided ) {
		$slug = sanitize_title( $name );
	} else {
		$slug = $args['slug'];
	}

	$term_group = 0;
	if ( $args['alias_of'] ) {
		$alias = get_term_by( 'slug', $args['alias_of'], $taxonomy );
		if ( ! empty( $alias->term_group ) ) {
			// Bí danh chúng ta muốn đã nằm trong một nhóm, vì vậy hãy sử dụng nhóm đó.
			$term_group = $alias->term_group;
		} elseif ( ! empty( $alias->term_id ) ) {
			/*
			 * Bí danh không nằm trong nhóm nào, vì vậy chúng ta tạo nhóm mới
			 * và thêm bí danh vào đó.
			 */
			$term_group = $wpdb->get_var( "SELECT MAX(term_group) FROM $wpdb->terms" ) + 1;

			wp_update_term(
				$alias->term_id,
				$taxonomy,
				array(
					'term_group' => $term_group,
				)
			);
		}
	}

	/*
	 * Ngăn chặn việc tạo term có tên trùng lặp ở cùng cấp trong phân cấp taxonomy,
	 * trừ khi slug duy nhất đã được cung cấp rõ ràng.
	 */
	$name_matches = get_terms(
		array(
			'taxonomy'               => $taxonomy,
			'name'                   => $name,
			'hide_empty'             => false,
			'parent'                 => $args['parent'],
			'update_term_meta_cache' => false,
		)
	);

	/*
	 * Phép so khớp `name` trong `get_terms()` không phân biệt ký tự có dấu,
	 * vì vậy chúng ta thực hiện phép so sánh chặt chẽ hơn ở đây.
	 */
	$name_match = null;
	if ( $name_matches ) {
		foreach ( $name_matches as $_match ) {
			if ( strtolower( $name ) === strtolower( $_match->name ) ) {
				$name_match = $_match;
				break;
			}
		}
	}

	if ( $name_match ) {
		$slug_match = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $slug_provided || $name_match->slug === $slug || $slug_match ) {
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$siblings = get_terms(
					array(
						'taxonomy'               => $taxonomy,
						'get'                    => 'all',
						'parent'                 => $parent,
						'update_term_meta_cache' => false,
					)
				);

				$existing_term = null;
				$sibling_names = wp_list_pluck( $siblings, 'name' );
				$sibling_slugs = wp_list_pluck( $siblings, 'slug' );

				if ( ( ! $slug_provided || $name_match->slug === $slug ) && in_array( $name, $sibling_names, true ) ) {
					$existing_term = $name_match;
				} elseif ( $slug_match && in_array( $slug, $sibling_slugs, true ) ) {
					$existing_term = $slug_match;
				}

				if ( $existing_term ) {
					return new WP_Error( 'term_exists', __( 'A term with the name provided already exists with this parent.' ), $existing_term->term_id );
				}
			} else {
				return new WP_Error( 'term_exists', __( 'A term with the name provided already exists in this taxonomy.' ), $name_match->term_id );
			}
		}
	}

	$slug = wp_unique_term_slug( $slug, (object) $args );

	$data = compact( 'name', 'slug', 'term_group' );

	/**
	 * Lọc dữ liệu term trước khi nó được chèn vào cơ sở dữ liệu.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $data     Dữ liệu term sẽ được chèn.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền cho wp_insert_term().
	 */
	$data = apply_filters( 'wp_insert_term_data', $data, $taxonomy, $args );

	if ( false === $wpdb->insert( $wpdb->terms, $data ) ) {
		return new WP_Error( 'db_insert_error', __( 'Could not insert term into the database.' ), $wpdb->last_error );
	}

	$term_id = (int) $wpdb->insert_id;

	// Có vẻ không thể truy cập được. Tuy nhiên, được sử dụng trong trường hợp tên term được cung cấp nhưng sau khi làm sạch trở thành chuỗi rỗng.
	if ( empty( $slug ) ) {
		$slug = sanitize_title( $slug, $term_id );

		/** Action này được ghi chú trong wp-includes/taxonomy.php */
		do_action( 'edit_terms', $term_id, $taxonomy );
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );

		/** Action này được ghi chú trong wp-includes/taxonomy.php */
		do_action( 'edited_terms', $term_id, $taxonomy );
	}

	$tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id ) );

	if ( ! empty( $tt_id ) ) {
		return array(
			'term_id'          => $term_id,
			'term_taxonomy_id' => $tt_id,
		);
	}

	if ( false === $wpdb->insert( $wpdb->term_taxonomy, compact( 'term_id', 'taxonomy', 'description', 'parent' ) + array( 'count' => 0 ) ) ) {
		return new WP_Error( 'db_insert_error', __( 'Could not insert term taxonomy into the database.' ), $wpdb->last_error );
	}

	$tt_id = (int) $wpdb->insert_id;

	/*
	 * Kiểm tra xác nhận: nếu chúng ta vừa tạo một term có cùng parent + taxonomy + slug nhưng term_id cao hơn
	 * so với term hiện có, thì chúng ta đã vô tình tạo term trùng lặp. Xóa bản trùng, và sử dụng term_id
	 * và term_taxonomy_id của term cũ hơn thay thế. Sau đó thoát khỏi hàm để các hook "create"
	 * không được kích hoạt.
	 */
	$duplicate_term = $wpdb->get_row( $wpdb->prepare( "SELECT t.term_id, t.slug, tt.term_taxonomy_id, tt.taxonomy FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON ( tt.term_id = t.term_id ) WHERE t.slug = %s AND tt.parent = %d AND tt.taxonomy = %s AND t.term_id < %d AND tt.term_taxonomy_id != %d", $slug, $parent, $taxonomy, $term_id, $tt_id ) );

	/**
	 * Lọc kiểm tra term trùng lặp được thực hiện trong quá trình tạo term.
	 *
	 * Tổ hợp parent + taxonomy + slug của term phải là duy nhất, và wp_insert_term()
	 * thực hiện xác nhận cuối cùng về tính duy nhất này trước khi cho phép tạo term mới.
	 * Các plugin có yêu cầu duy nhất khác có thể sử dụng bộ lọc này
	 * để bỏ qua hoặc sửa đổi kiểm tra term trùng lặp.
	 *
	 * @since 5.1.0
	 *
	 * @param object $duplicate_term Dòng term trùng lặp từ bảng terms, nếu tìm thấy.
	 * @param string $term           Term đang được chèn.
	 * @param string $taxonomy       Tên taxonomy.
	 * @param array  $args           Tham số được truyền cho wp_insert_term().
	 * @param int    $tt_id          term_taxonomy_id cho term mới được tạo.
	 */
	$duplicate_term = apply_filters( 'wp_insert_term_duplicate_term_check', $duplicate_term, $term, $taxonomy, $args, $tt_id );

	if ( $duplicate_term ) {
		$wpdb->delete( $wpdb->terms, array( 'term_id' => $term_id ) );
		$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $tt_id ) );

		$term_id = (int) $duplicate_term->term_id;
		$tt_id   = (int) $duplicate_term->term_taxonomy_id;

		clean_term_cache( $term_id, $taxonomy );
		return array(
			'term_id'          => $term_id,
			'term_taxonomy_id' => $tt_id,
		);
	}

	/**
	 * Kích hoạt ngay sau khi term mới được tạo, trước khi cache term được dọn sạch.
	 *
	 * Hook {@see 'create_$taxonomy'} cũng có sẵn để nhắm mục tiêu một taxonomy
	 * cụ thể.
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param int    $tt_id    ID term taxonomy.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền cho wp_insert_term().
	 */
	do_action( 'create_term', $term_id, $tt_id, $taxonomy, $args );

	/**
	 * Kích hoạt sau khi term mới được tạo cho một taxonomy cụ thể.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu
	 * đến slug của taxonomy mà term được tạo cho.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `create_category`
	 *  - `create_post_tag`
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int   $term_id ID term.
	 * @param int   $tt_id   ID term taxonomy.
	 * @param array $args    Tham số được truyền cho wp_insert_term().
	 */
	do_action( "create_{$taxonomy}", $term_id, $tt_id, $args );

	/**
	 * Lọc ID term sau khi term mới được tạo.
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int   $term_id ID term.
	 * @param int   $tt_id   ID term taxonomy.
	 * @param array $args    Tham số được truyền cho wp_insert_term().
	 */
	$term_id = apply_filters( 'term_id_filter', $term_id, $tt_id, $args );

	clean_term_cache( $term_id, $taxonomy );

	/**
	 * Kích hoạt sau khi term mới được tạo, và sau khi cache term được dọn sạch.
	 *
	 * Hook {@see 'created_$taxonomy'} cũng có sẵn để nhắm mục tiêu một taxonomy
	 * cụ thể.
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param int    $tt_id    ID term taxonomy.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền cho wp_insert_term().
	 */
	do_action( 'created_term', $term_id, $tt_id, $taxonomy, $args );

	/**
	 * Kích hoạt sau khi term mới được tạo trong một taxonomy cụ thể, và sau khi cache term
	 * được dọn sạch.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `created_category`
	 *  - `created_post_tag`
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int   $term_id ID term.
	 * @param int   $tt_id   ID term taxonomy.
	 * @param array $args    Tham số được truyền cho wp_insert_term().
	 */
	do_action( "created_{$taxonomy}", $term_id, $tt_id, $args );

	/**
	 * Kích hoạt sau khi term được lưu, và cache term được dọn sạch.
	 *
	 * Hook {@see 'saved_$taxonomy'} cũng có sẵn để nhắm mục tiêu một taxonomy
	 * cụ thể.
	 *
	 * @since 5.5.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param int    $tt_id    ID term taxonomy.
	 * @param string $taxonomy Slug taxonomy.
	 * @param bool   $update   Term hiện có đang được cập nhật hay không.
	 * @param array  $args     Tham số được truyền cho wp_insert_term().
	 */
	do_action( 'saved_term', $term_id, $tt_id, $taxonomy, false, $args );

	/**
	 * Kích hoạt sau khi term trong một taxonomy cụ thể được lưu, và cache term
	 * được dọn sạch.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `saved_category`
	 *  - `saved_post_tag`
	 *
	 * @since 5.5.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int   $term_id ID term.
	 * @param int   $tt_id   ID term taxonomy.
	 * @param bool  $update  Term hiện có đang được cập nhật hay không.
	 * @param array $args    Tham số được truyền cho wp_insert_term().
	 */
	do_action( "saved_{$taxonomy}", $term_id, $tt_id, false, $args );

	return array(
		'term_id'          => $term_id,
		'term_taxonomy_id' => $tt_id,
	);
}

/**
 * Tạo mối quan hệ giữa term và taxonomy.
 *
 * Liên kết một đối tượng (bài viết, liên kết, v.v.) với term và loại taxonomy. Tạo
 * mối quan hệ term và taxonomy nếu chưa tồn tại. Tạo term nếu
 * chưa tồn tại (sử dụng slug).
 *
 * Mối quan hệ nghĩa là term được nhóm vào hoặc thuộc về taxonomy.
 * Term không có ý nghĩa cho đến khi được đặt trong ngữ cảnh bằng cách xác định taxonomy
 * mà nó thuộc về.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng hóa cơ sở dữ liệu WordPress.
 *
 * @param int              $object_id Đối tượng cần liên kết.
 * @param string|int|array $terms     Slug term đơn, ID term đơn, hoặc mảng slug hoặc ID term.
 *                                    Sẽ thay thế tất cả term liên kết hiện có trong taxonomy này. Truyền
 *                                    mảng rỗng sẽ xóa tất cả term liên kết.
 * @param string           $taxonomy  Ngữ cảnh để liên kết term với đối tượng.
 * @param bool             $append    Tùy chọn. Nếu false sẽ xóa phần khác biệt của term. Mặc định false.
 * @return array|WP_Error ID term taxonomy của các term bị ảnh hưởng hoặc WP_Error khi thất bại.
 */
function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	if ( empty( $terms ) ) {
		$terms = array();
	} elseif ( ! is_array( $terms ) ) {
		$terms = array( $terms );
	}

	if ( ! $append ) {
		$old_tt_ids = wp_get_object_terms(
			$object_id,
			$taxonomy,
			array(
				'fields'                 => 'tt_ids',
				'orderby'                => 'none',
				'update_term_meta_cache' => false,
			)
		);
	} else {
		$old_tt_ids = array();
	}

	$tt_ids     = array();
	$new_tt_ids = array();

	foreach ( (array) $terms as $term ) {
		if ( '' === trim( $term ) ) {
			continue;
		}

		$term_info = term_exists( $term, $taxonomy );

		if ( ! $term_info ) {
			// Bỏ qua nếu ID term không tồn tại được truyền vào.
			if ( is_int( $term ) ) {
				continue;
			}

			$term_info = wp_insert_term( $term, $taxonomy );
		}

		if ( is_wp_error( $term_info ) ) {
			return $term_info;
		}

		$tt_id    = $term_info['term_taxonomy_id'];
		$tt_ids[] = $tt_id;

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $tt_id ) ) ) {
			continue;
		}

		/**
		 * Kích hoạt ngay trước khi mối quan hệ đối tượng-term được thêm.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Thêm tham số `$taxonomy`.
		 *
		 * @param int    $object_id ID đối tượng.
		 * @param int    $tt_id     ID taxonomy của term.
		 * @param string $taxonomy  Slug taxonomy.
		 */
		do_action( 'add_term_relationship', $object_id, $tt_id, $taxonomy );

		$wpdb->insert(
			$wpdb->term_relationships,
			array(
				'object_id'        => $object_id,
				'term_taxonomy_id' => $tt_id,
			)
		);

		/**
		 * Kích hoạt ngay sau khi mối quan hệ đối tượng-term được thêm.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Thêm tham số `$taxonomy`.
		 *
		 * @param int    $object_id ID đối tượng.
		 * @param int    $tt_id     ID taxonomy của term.
		 * @param string $taxonomy  Slug taxonomy.
		 */
		do_action( 'added_term_relationship', $object_id, $tt_id, $taxonomy );

		$new_tt_ids[] = $tt_id;
	}

	if ( $new_tt_ids ) {
		wp_update_term_count( $new_tt_ids, $taxonomy );
	}

	if ( ! $append ) {
		$delete_tt_ids = array_diff( $old_tt_ids, $tt_ids );

		if ( $delete_tt_ids ) {
			$in_delete_tt_ids = "'" . implode( "', '", $delete_tt_ids ) . "'";
			$delete_term_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT tt.term_id FROM $wpdb->term_taxonomy AS tt WHERE tt.taxonomy = %s AND tt.term_taxonomy_id IN ($in_delete_tt_ids)", $taxonomy ) );
			$delete_term_ids  = array_map( 'intval', $delete_term_ids );

			$remove = wp_remove_object_terms( $object_id, $delete_term_ids, $taxonomy );
			if ( is_wp_error( $remove ) ) {
				return $remove;
			}
		}
	}

	$t = get_taxonomy( $taxonomy );

	if ( ! $append && isset( $t->sort ) && $t->sort ) {
		$values     = array();
		$term_order = 0;

		$final_tt_ids = wp_get_object_terms(
			$object_id,
			$taxonomy,
			array(
				'fields'                 => 'tt_ids',
				'update_term_meta_cache' => false,
			)
		);

		foreach ( $tt_ids as $tt_id ) {
			if ( in_array( (int) $tt_id, $final_tt_ids, true ) ) {
				$values[] = $wpdb->prepare( '(%d, %d, %d)', $object_id, $tt_id, ++$term_order );
			}
		}

		if ( $values ) {
			if ( false === $wpdb->query( "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES " . implode( ',', $values ) . ' ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)' ) ) {
				return new WP_Error( 'db_insert_error', __( 'Could not insert term relationship into the database.' ), $wpdb->last_error );
			}
		}
	}

	wp_cache_delete( $object_id, $taxonomy . '_relationships' );
	wp_cache_set_terms_last_changed();

	/**
	 * Kích hoạt sau khi các term của đối tượng đã được thiết lập.
	 *
	 * @since 2.8.0
	 *
	 * @param int    $object_id  ID đối tượng.
	 * @param array  $terms      Mảng các ID hoặc slug term của đối tượng.
	 * @param array  $tt_ids     Mảng các ID taxonomy của term.
	 * @param string $taxonomy   Slug taxonomy.
	 * @param bool   $append     Có nối thêm term mới vào term cũ hay không.
	 * @param array  $old_tt_ids Mảng cũ các ID taxonomy của term.
	 */
	do_action( 'set_object_terms', $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids );

	return $tt_ids;
}

/**
 * Thêm (các) term liên kết với đối tượng cho trước.
 *
 * @since 3.6.0
 *
 * @param int              $object_id ID của đối tượng mà các term sẽ được thêm vào.
 * @param string|int|array $terms     (Các) slug hoặc ID của (các) term cần thêm.
 * @param array|string     $taxonomy  Tên taxonomy.
 * @return array|WP_Error Các ID taxonomy của term bị ảnh hưởng.
 */
function wp_add_object_terms( $object_id, $terms, $taxonomy ) {
	return wp_set_object_terms( $object_id, $terms, $taxonomy, true );
}

/**
 * Xóa (các) term liên kết với đối tượng cho trước.
 *
 * @since 3.6.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int              $object_id ID của đối tượng mà các term sẽ bị xóa khỏi.
 * @param string|int|array $terms     (Các) slug hoặc ID của (các) term cần xóa.
 * @param string           $taxonomy  Tên taxonomy.
 * @return bool|WP_Error True nếu thành công, false hoặc WP_Error nếu thất bại.
 */
function wp_remove_object_terms( $object_id, $terms, $taxonomy ) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	if ( ! is_array( $terms ) ) {
		$terms = array( $terms );
	}

	$tt_ids = array();

	foreach ( (array) $terms as $term ) {
		if ( '' === trim( $term ) ) {
			continue;
		}

		$term_info = term_exists( $term, $taxonomy );
		if ( ! $term_info ) {
			// Bỏ qua nếu ID term không tồn tại được truyền vào.
			if ( is_int( $term ) ) {
				continue;
			}
		}

		if ( is_wp_error( $term_info ) ) {
			return $term_info;
		}

		$tt_ids[] = $term_info['term_taxonomy_id'];
	}

	if ( $tt_ids ) {
		$in_tt_ids = "'" . implode( "', '", $tt_ids ) . "'";

		/**
		 * Kích hoạt ngay trước khi mối quan hệ đối tượng-term bị xóa.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Thêm tham số `$taxonomy`.
		 *
		 * @param int    $object_id ID đối tượng.
		 * @param array  $tt_ids    Mảng các ID taxonomy của term.
		 * @param string $taxonomy  Slug taxonomy.
		 */
		do_action( 'delete_term_relationships', $object_id, $tt_ids, $taxonomy );

		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $object_id ) );

		wp_cache_delete( $object_id, $taxonomy . '_relationships' );
		wp_cache_set_terms_last_changed();

		/**
		 * Kích hoạt ngay sau khi mối quan hệ đối tượng-term bị xóa.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Thêm tham số `$taxonomy`.
		 *
		 * @param int    $object_id ID đối tượng.
		 * @param array  $tt_ids    Mảng các ID taxonomy của term.
		 * @param string $taxonomy  Slug taxonomy.
		 */
		do_action( 'deleted_term_relationships', $object_id, $tt_ids, $taxonomy );

		wp_update_term_count( $tt_ids, $taxonomy );

		return (bool) $deleted;
	}

	return false;
}

/**
 * Tạo slug của term là duy nhất, nếu chưa duy nhất.
 *
 * `$slug` phải là duy nhất toàn cục cho mọi taxonomy, nghĩa là một
 * term taxonomy không thể có slug trùng với term taxonomy khác. Mỗi
 * slug phải là duy nhất toàn cục cho mọi taxonomy.
 *
 * Cách hoạt động là nếu taxonomy mà term thuộc về là
 * phân cấp và có parent, nó sẽ nối thêm parent đó vào $slug.
 *
 * Nếu vẫn không trả về slug duy nhất, thì nó sẽ thử nối thêm một số
 * cho đến khi tìm được số thực sự duy nhất.
 *
 * Mục đích duy nhất của `$term` là để nối thêm parent, nếu tồn tại.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param string $slug Chuỗi sẽ được thử cho slug duy nhất.
 * @param object $term Đối tượng term mà `$slug` sẽ thuộc về.
 * @return string Sẽ trả về slug thực sự duy nhất.
 */
function wp_unique_term_slug( $slug, $term ) {
	global $wpdb;

	$needs_suffix  = true;
	$original_slug = $slug;

	// Từ phiên bản 4.1, slug trùng lặp được cho phép miễn là chúng ở các taxonomy khác nhau.
	if ( ! term_exists( $slug ) || get_option( 'db_version' ) >= 30133 && ! get_term_by( 'slug', $slug, $term->taxonomy ) ) {
		$needs_suffix = false;
	}

	/*
	 * Nếu taxonomy hỗ trợ phân cấp và term có parent, tạo slug duy nhất
	 * bằng cách kết hợp slug của parent.
	 */
	$parent_suffix = '';
	if ( $needs_suffix && is_taxonomy_hierarchical( $term->taxonomy ) && ! empty( $term->parent ) ) {
		$the_parent = $term->parent;
		while ( ! empty( $the_parent ) ) {
			$parent_term = get_term( $the_parent, $term->taxonomy );
			if ( is_wp_error( $parent_term ) || empty( $parent_term ) ) {
				break;
			}
			$parent_suffix .= '-' . $parent_term->slug;
			if ( ! term_exists( $slug . $parent_suffix ) ) {
				break;
			}

			if ( empty( $parent_term->parent ) ) {
				break;
			}
			$the_parent = $parent_term->parent;
		}
	}

	// Nếu không có được slug duy nhất, thử nối thêm số để tạo slug duy nhất.

	/**
	 * Lọc xem slug term duy nhất đề xuất có xấu hay không.
	 *
	 * @since 4.3.0
	 *
	 * @param bool   $needs_suffix Slug có cần được tạo duy nhất bằng hậu tố hay không.
	 * @param string $slug         Slug.
	 * @param object $term         Đối tượng term.
	 */
	if ( apply_filters( 'wp_unique_term_slug_is_bad_slug', $needs_suffix, $slug, $term ) ) {
		if ( $parent_suffix ) {
			$slug .= $parent_suffix;
		}

		if ( ! empty( $term->term_id ) ) {
			$query = $wpdb->prepare( "SELECT slug FROM $wpdb->terms WHERE slug = %s AND term_id != %d", $slug, $term->term_id );
		} else {
			$query = $wpdb->prepare( "SELECT slug FROM $wpdb->terms WHERE slug = %s", $slug );
		}

		if ( $wpdb->get_var( $query ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$num = 2;
			do {
				$alt_slug = $slug . "-$num";
				++$num;
				$slug_check = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM $wpdb->terms WHERE slug = %s", $alt_slug ) );
			} while ( $slug_check );
			$slug = $alt_slug;
		}
	}

	/**
	 * Lọc slug term duy nhất.
	 *
	 * @since 4.3.0
	 *
	 * @param string $slug          Slug term duy nhất.
	 * @param object $term          Đối tượng term.
	 * @param string $original_slug Slug ban đầu được truyền vào hàm để kiểm tra.
	 */
	return apply_filters( 'wp_unique_term_slug', $slug, $term, $original_slug );
}

/**
 * Cập nhật term dựa trên các tham số được cung cấp.
 *
 * `$args` sẽ ghi đè không phân biệt tất cả giá trị có cùng tên trường.
 * Cần cẩn thận để không ghi đè thông tin quan trọng cần thiết cho việc cập nhật,
 * nếu không cập nhật sẽ thất bại (hoặc có thể tạo term mới, cả hai đều không chấp nhận được).
 *
 * Giá trị mặc định sẽ thiết lập 'alias_of', 'description', 'parent', và 'slug' nếu chưa
 * được định nghĩa trong `$args`.
 *
 * 'alias_of' sẽ tạo nhóm term, nếu chưa tồn tại, và
 * cập nhật nó cho `$term`.
 *
 * Nếu tham số 'slug' trong `$args` bị thiếu, thì 'name' sẽ được sử dụng.
 * Nếu bạn đặt 'slug' và nó không duy nhất, thì WP_Error sẽ được trả về.
 * Nếu bạn không truyền slug nào, thì một slug duy nhất sẽ được tạo.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param int          $term_id  ID của term.
 * @param string       $taxonomy Taxonomy của term.
 * @param array        $args {
 *     Tùy chọn. Mảng tham số để cập nhật term.
 *
 *     @type string $alias_of    Slug của term để biến term này thành bí danh.
 *                               Mặc định chuỗi rỗng. Chấp nhận slug term.
 *     @type string $description Mô tả của term. Mặc định chuỗi rỗng.
 *     @type int    $parent      ID của term cha. Mặc định 0.
 *     @type string $slug        Slug term để sử dụng. Mặc định chuỗi rỗng.
 * }
 * @return array|WP_Error Mảng chứa `term_id` và `term_taxonomy_id`,
 *                        WP_Error nếu ngược lại.
 */
function wp_update_term( $term_id, $taxonomy, $args = array() ) {
	global $wpdb;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$term_id = (int) $term_id;

	// Đầu tiên, lấy tất cả các tham số gốc.
	$term = get_term( $term_id, $taxonomy );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( ! $term ) {
		return new WP_Error( 'invalid_term', __( 'Empty Term.' ) );
	}

	$term = (array) $term->data;

	// Escape dữ liệu lấy từ DB.
	$term = wp_slash( $term );

	// Gộp tham số cũ và mới, tham số mới ghi đè lên tham số cũ.
	$args = array_merge( $term, $args );

	$defaults    = array(
		'alias_of'    => '',
		'description' => '',
		'parent'      => 0,
		'slug'        => '',
	);
	$args        = wp_parse_args( $args, $defaults );
	$args        = sanitize_term( $args, $taxonomy, 'db' );
	$parsed_args = $args;

	// expected_slashed ($name)
	$name        = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );

	$parsed_args['name']        = $name;
	$parsed_args['description'] = $description;

	if ( '' === trim( $name ) ) {
		return new WP_Error( 'empty_term_name', __( 'A name is required for this term.' ) );
	}

	if ( (int) $parsed_args['parent'] > 0 && ! term_exists( (int) $parsed_args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}

	$empty_slug = false;
	if ( empty( $args['slug'] ) ) {
		$empty_slug = true;
		$slug       = sanitize_title( $name );
	} else {
		$slug = $args['slug'];
	}

	$parsed_args['slug'] = $slug;

	$term_group = isset( $parsed_args['term_group'] ) ? $parsed_args['term_group'] : 0;
	if ( $args['alias_of'] ) {
		$alias = get_term_by( 'slug', $args['alias_of'], $taxonomy );
		if ( ! empty( $alias->term_group ) ) {
			// Bí danh muốn dùng đã nằm trong một nhóm, vậy hãy dùng nhóm đó.
			$term_group = $alias->term_group;
		} elseif ( ! empty( $alias->term_id ) ) {
			/*
			 * Bí danh chưa nằm trong nhóm nào, nên chúng ta tạo nhóm mới
			 * và thêm bí danh vào đó.
			 */
			$term_group = $wpdb->get_var( "SELECT MAX(term_group) FROM $wpdb->terms" ) + 1;

			wp_update_term(
				$alias->term_id,
				$taxonomy,
				array(
					'term_group' => $term_group,
				)
			);
		}

		$parsed_args['term_group'] = $term_group;
	}

	/**
	 * Lọc term cha.
	 *
	 * Hook vào filter này để kiểm tra xem nó có gây ra vòng lặp phân cấp hay không.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $parent_term ID của term cha.
	 * @param int    $term_id     ID term.
	 * @param string $taxonomy    Slug taxonomy.
	 * @param array  $parsed_args Mảng các tham số cập nhật có thể đã được thay đổi cho term cho trước.
	 * @param array  $args        Tham số được truyền vào wp_update_term().
	 */
	$parent = (int) apply_filters( 'wp_update_term_parent', $args['parent'], $term_id, $taxonomy, $parsed_args, $args );

	// Kiểm tra slug trùng lặp.
	$duplicate = get_term_by( 'slug', $slug, $taxonomy );
	if ( $duplicate && $duplicate->term_id !== $term_id ) {
		/*
		 * Nếu slug rỗng được truyền vào hoặc parent đã thay đổi, đặt lại slug thành giá trị duy nhất.
		 * Nếu không, thoát ra.
		 */
		if ( $empty_slug || ( $parent !== (int) $term['parent'] ) ) {
			$slug = wp_unique_term_slug( $slug, (object) $args );
		} else {
			/* translators: %s: Taxonomy term slug. */
			return new WP_Error( 'duplicate_term_slug', sprintf( __( 'The slug &#8220;%s&#8221; is already in use by another term.' ), $slug ) );
		}
	}

	$tt_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id ) );

	// Kiểm tra xem đây có phải là term dùng chung cần tách hay không.
	$_term_id = _split_shared_term( $term_id, $tt_id );
	if ( ! is_wp_error( $_term_id ) ) {
		$term_id = $_term_id;
	}

	/**
	 * Kích hoạt ngay trước khi các term cho trước được chỉnh sửa.
	 *
	 * @since 2.9.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	do_action( 'edit_terms', $term_id, $taxonomy, $args );

	$data = compact( 'name', 'slug', 'term_group' );

	/**
	 * Lọc dữ liệu term trước khi được cập nhật trong cơ sở dữ liệu.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $data     Dữ liệu term sẽ được cập nhật.
	 * @param int    $term_id  ID term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	$data = apply_filters( 'wp_update_term_data', $data, $term_id, $taxonomy, $args );

	$wpdb->update( $wpdb->terms, $data, compact( 'term_id' ) );

	if ( empty( $slug ) ) {
		$slug = sanitize_title( $name, $term_id );
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );
	}

	/**
	 * Kích hoạt ngay sau khi term được cập nhật trong cơ sở dữ liệu, nhưng trước khi
	 * mối quan hệ term-taxonomy được cập nhật.
	 *
	 * @since 2.9.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	do_action( 'edited_terms', $term_id, $taxonomy, $args );

	/**
	 * Kích hoạt ngay trước khi mối quan hệ term-taxonomy được cập nhật.
	 *
	 * @since 2.9.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $tt_id    ID taxonomy của term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	do_action( 'edit_term_taxonomy', $tt_id, $taxonomy, $args );

	$wpdb->update( $wpdb->term_taxonomy, compact( 'term_id', 'taxonomy', 'description', 'parent' ), array( 'term_taxonomy_id' => $tt_id ) );

	/**
	 * Kích hoạt ngay sau khi mối quan hệ term-taxonomy được cập nhật.
	 *
	 * @since 2.9.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $tt_id    ID taxonomy của term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	do_action( 'edited_term_taxonomy', $tt_id, $taxonomy, $args );

	/**
	 * Kích hoạt sau khi term đã được cập nhật, nhưng trước khi bộ nhớ đệm term được xóa.
	 *
	 * Hook {@see 'edit_$taxonomy'} cũng có sẵn để nhắm đến một
	 * taxonomy cụ thể.
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param int    $tt_id    ID taxonomy của term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	do_action( 'edit_term', $term_id, $tt_id, $taxonomy, $args );

	/**
	 * Kích hoạt sau khi term trong một taxonomy cụ thể đã được cập nhật, nhưng trước khi
	 * bộ nhớ đệm term được xóa.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `edit_category`
	 *  - `edit_post_tag`
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int   $term_id ID term.
	 * @param int   $tt_id   ID taxonomy của term.
	 * @param array $args    Tham số được truyền vào wp_update_term().
	 */
	do_action( "edit_{$taxonomy}", $term_id, $tt_id, $args );

	/** Filter này được ghi chú tại wp-includes/taxonomy.php */
	$term_id = apply_filters( 'term_id_filter', $term_id, $tt_id );

	clean_term_cache( $term_id, $taxonomy );

	/**
	 * Kích hoạt sau khi term đã được cập nhật, và bộ nhớ đệm term đã được xóa.
	 *
	 * Hook {@see 'edited_$taxonomy'} cũng có sẵn để nhắm đến một
	 * taxonomy cụ thể.
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int    $term_id  ID term.
	 * @param int    $tt_id    ID taxonomy của term.
	 * @param string $taxonomy Slug taxonomy.
	 * @param array  $args     Tham số được truyền vào wp_update_term().
	 */
	do_action( 'edited_term', $term_id, $tt_id, $taxonomy, $args );

	/**
	 * Kích hoạt sau khi term cho một taxonomy cụ thể đã được cập nhật, và bộ nhớ đệm
	 * term đã được xóa.
	 *
	 * Phần động của tên hook, `$taxonomy`, tham chiếu đến slug taxonomy.
	 *
	 * Các tên hook có thể bao gồm:
	 *
	 *  - `edited_category`
	 *  - `edited_post_tag`
	 *
	 * @since 2.3.0
	 * @since 6.1.0 Thêm tham số `$args`.
	 *
	 * @param int   $term_id ID term.
	 * @param int   $tt_id   ID taxonomy của term.
	 * @param array $args    Tham số được truyền vào wp_update_term().
	 */
	do_action( "edited_{$taxonomy}", $term_id, $tt_id, $args );

	/** Action này được ghi chú tại wp-includes/taxonomy.php */
	do_action( 'saved_term', $term_id, $tt_id, $taxonomy, true, $args );

	/** Action này được ghi chú tại wp-includes/taxonomy.php */
	do_action( "saved_{$taxonomy}", $term_id, $tt_id, true, $args );

	return array(
		'term_id'          => $term_id,
		'term_taxonomy_id' => $tt_id,
	);
}

/**
 * Bật hoặc tắt việc đếm term.
 *
 * @since 2.5.0
 *
 * @param bool $defer Tùy chọn. Bật nếu true, tắt nếu false.
 * @return bool Việc đếm term đang được bật hay tắt.
 */
function wp_defer_term_counting( $defer = null ) {
	static $_defer = false;

	if ( is_bool( $defer ) ) {
		$_defer = $defer;
		// Xả tất cả các đếm bị trì hoãn.
		if ( ! $defer ) {
			wp_update_term_count( null, null, true );
		}
	}

	return $_defer;
}

/**
 * Cập nhật số lượng term trong taxonomy.
 *
 * Nếu có callback taxonomy được áp dụng, thì nó sẽ được gọi để cập nhật
 * số đếm.
 *
 * Hành động mặc định là đếm số lượng term có mối quan hệ
 * với ID term. Sau khi hoàn thành, thì cập nhật cơ sở dữ liệu.
 *
 * @since 2.3.0
 *
 * @param int|array $terms       term_taxonomy_id của các term.
 * @param string    $taxonomy    Ngữ cảnh của term.
 * @param bool      $do_deferred Có xả các đếm term bị trì hoãn hay không. Mặc định false.
 * @return bool Nếu không có term sẽ trả về false, và nếu thành công sẽ trả về true.
 */
function wp_update_term_count( $terms, $taxonomy, $do_deferred = false ) {
	static $_deferred = array();

	if ( $do_deferred ) {
		foreach ( (array) array_keys( $_deferred ) as $tax ) {
			wp_update_term_count_now( $_deferred[ $tax ], $tax );
			unset( $_deferred[ $tax ] );
		}
	}

	if ( empty( $terms ) ) {
		return false;
	}

	if ( ! is_array( $terms ) ) {
		$terms = array( $terms );
	}

	if ( wp_defer_term_counting() ) {
		if ( ! isset( $_deferred[ $taxonomy ] ) ) {
			$_deferred[ $taxonomy ] = array();
		}
		$_deferred[ $taxonomy ] = array_unique( array_merge( $_deferred[ $taxonomy ], $terms ) );
		return true;
	}

	return wp_update_term_count_now( $terms, $taxonomy );
}

/**
 * Thực hiện cập nhật số đếm term ngay lập tức.
 *
 * @since 2.5.0
 *
 * @param array  $terms    term_taxonomy_id của các term cần cập nhật.
 * @param string $taxonomy Ngữ cảnh của term.
 * @return true Luôn trả về true khi hoàn thành.
 */
function wp_update_term_count_now( $terms, $taxonomy ) {
	$terms = array_map( 'intval', $terms );

	$taxonomy = get_taxonomy( $taxonomy );
	if ( ! empty( $taxonomy->update_count_callback ) ) {
		call_user_func( $taxonomy->update_count_callback, $terms, $taxonomy );
	} else {
		$object_types = (array) $taxonomy->object_type;
		foreach ( $object_types as &$object_type ) {
			if ( str_starts_with( $object_type, 'attachment:' ) ) {
				list( $object_type ) = explode( ':', $object_type );
			}
		}

		if ( array_filter( $object_types, 'post_type_exists' ) == $object_types ) {
			// Chỉ các post type được gắn với taxonomy này.
			_update_post_term_count( $terms, $taxonomy );
		} else {
			// Bộ cập nhật số đếm mặc định.
			_update_generic_term_count( $terms, $taxonomy );
		}
	}

	clean_term_cache( $terms, '', false );

	return true;
}

//
// Bộ nhớ đệm.
//

/**
 * Xóa mối quan hệ taxonomy với term khỏi bộ nhớ đệm.
 *
 * Sẽ xóa toàn bộ mối quan hệ taxonomy chứa term `$object_id`. Các
 * ID term phải tồn tại trong taxonomy `$object_type` để việc xóa
 * diễn ra.
 *
 * @since 2.3.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @see get_object_taxonomies() để biết thêm về $object_type.
 *
 * @param int|array    $object_ids  Một hoặc danh sách (các) ID đối tượng term.
 * @param array|string $object_type Loại đối tượng taxonomy.
 */
function clean_object_term_cache( $object_ids, $object_type ) {
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	if ( ! is_array( $object_ids ) ) {
		$object_ids = array( $object_ids );
	}

	$taxonomies = get_object_taxonomies( $object_type );

	foreach ( $taxonomies as $taxonomy ) {
		wp_cache_delete_multiple( $object_ids, "{$taxonomy}_relationships" );
	}

	wp_cache_set_terms_last_changed();

	/**
	 * Kích hoạt sau khi bộ nhớ đệm term của đối tượng đã được xóa.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $object_ids Mảng các ID đối tượng.
	 * @param string $object_type Loại đối tượng.
	 */
	do_action( 'clean_object_term_cache', $object_ids, $object_type );
}

/**
 * Xóa tất cả các ID term khỏi bộ nhớ đệm.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb                           Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|int[] $ids            Một hoặc mảng các ID term.
 * @param string    $taxonomy       Tùy chọn. Slug taxonomy. Có thể rỗng, trong trường hợp đó các taxonomy của
 *                                  ID term được truyền sẽ được sử dụng. Mặc định rỗng.
 * @param bool      $clean_taxonomy Tùy chọn. Có xóa bộ nhớ đệm toàn bộ taxonomy (true), hay chỉ bộ nhớ đệm
 *                                  đối tượng term riêng lẻ (false). Mặc định true.
 */
function clean_term_cache( $ids, $taxonomy = '', $clean_taxonomy = true ) {
	global $wpdb, $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}

	$taxonomies = array();
	// Nếu không có taxonomy, giả định là tt_ids.
	if ( empty( $taxonomy ) ) {
		$tt_ids = array_map( 'intval', $ids );
		$tt_ids = implode( ', ', $tt_ids );
		$terms  = $wpdb->get_results( "SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ($tt_ids)" );
		$ids    = array();

		foreach ( (array) $terms as $term ) {
			$taxonomies[] = $term->taxonomy;
			$ids[]        = $term->term_id;
		}
		wp_cache_delete_multiple( $ids, 'terms' );
		$taxonomies = array_unique( $taxonomies );
	} else {
		wp_cache_delete_multiple( $ids, 'terms' );
		$taxonomies = array( $taxonomy );
	}

	foreach ( $taxonomies as $taxonomy ) {
		if ( $clean_taxonomy ) {
			clean_taxonomy_cache( $taxonomy );
		}

		/**
		 * Kích hoạt một lần sau khi bộ nhớ đệm term của mỗi taxonomy đã được xóa.
		 *
		 * @since 2.5.0
		 * @since 4.5.0 Thêm tham số `$clean_taxonomy`.
		 *
		 * @param array  $ids            Mảng các ID term.
		 * @param string $taxonomy       Slug taxonomy.
		 * @param bool   $clean_taxonomy Có xóa bộ nhớ đệm toàn bộ taxonomy hay không.
		 */
		do_action( 'clean_term_cache', $ids, $taxonomy, $clean_taxonomy );
	}

	wp_cache_set_terms_last_changed();
}

/**
 * Xóa bộ nhớ đệm cho một taxonomy.
 *
 * @since 4.9.0
 *
 * @param string $taxonomy Slug taxonomy.
 */
function clean_taxonomy_cache( $taxonomy ) {
	wp_cache_delete( 'all_ids', $taxonomy );
	wp_cache_delete( 'get', $taxonomy );
	wp_cache_set_terms_last_changed();

	// Tái tạo phân cấp đã được lưu đệm.
	delete_option( "{$taxonomy}_children" );
	_get_term_hierarchy( $taxonomy );

	/**
	 * Kích hoạt sau khi bộ nhớ đệm của taxonomy đã được xóa.
	 *
	 * @since 4.9.0
	 *
	 * @param string $taxonomy Slug taxonomy.
	 */
	do_action( 'clean_taxonomy_cache', $taxonomy );
}

/**
 * Lấy các đối tượng term đã được lưu đệm cho ID đối tượng cho trước.
 *
 * Các hàm phía trên (như get_the_terms() và is_object_in_term()) chịu trách nhiệm
 * tạo bộ nhớ đệm mối quan hệ đối tượng-term. Hàm hiện tại
 * chỉ lấy dữ liệu mối quan hệ đã có trong bộ nhớ đệm.
 *
 * @since 2.3.0
 * @since 4.7.0 Trả về đối tượng `WP_Error` nếu có lỗi với
 *              bất kỳ term nào khớp.
 *
 * @param int    $id       ID đối tượng term, ví dụ ID bài viết, bình luận, hoặc người dùng.
 * @param string $taxonomy Tên taxonomy.
 * @return bool|WP_Term[]|WP_Error Mảng các đối tượng `WP_Term`, nếu đã được lưu đệm.
 *                                 False nếu bộ nhớ đệm rỗng cho `$taxonomy` và `$id`.
 *                                 WP_Error nếu get_term() trả về đối tượng lỗi cho bất kỳ term nào.
 */
function get_object_term_cache( $id, $taxonomy ) {
	$_term_ids = wp_cache_get( $id, "{$taxonomy}_relationships" );

	// Chúng ta để việc nạp trước bộ nhớ đệm mối quan hệ cho các hàm phía trên.
	if ( false === $_term_ids ) {
		return false;
	}

	// Tương thích ngược cho trường hợp plugin đặt đối tượng vào bộ nhớ đệm thay vì ID.
	$term_ids = array();
	foreach ( $_term_ids as $term_id ) {
		if ( is_numeric( $term_id ) ) {
			$term_ids[] = (int) $term_id;
		} elseif ( isset( $term_id->term_id ) ) {
			$term_ids[] = (int) $term_id->term_id;
		}
	}

	// Điền các đối tượng term.
	_prime_term_caches( $term_ids );

	$terms = array();
	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$terms[] = $term;
	}

	return $terms;
}

/**
 * Cập nhật bộ nhớ đệm cho (các) ID đối tượng term cho trước.
 *
 * Lưu ý: Do lo ngại về hiệu suất, cần rất cẩn thận để chỉ cập nhật
 * bộ nhớ đệm term khi cần thiết. Thời gian xử lý có thể tăng theo cấp số nhân phụ thuộc
 * vào cả số lượng ID term được truyền và số lượng taxonomy mà các term đó
 * thuộc về.
 *
 * Bộ nhớ đệm chỉ được cập nhật cho các term chưa được lưu đệm.
 *
 * @since 2.3.0
 *
 * @param string|int[]    $object_ids  Danh sách phân cách bằng dấu phẩy hoặc mảng các ID đối tượng term.
 * @param string|string[] $object_type Loại đối tượng taxonomy hoặc mảng tương tự.
 * @return void|false Void nếu thành công hoặc nếu tham số `$object_ids` rỗng,
 *                    false nếu tất cả term trong `$object_ids` đã được lưu đệm.
 */
function update_object_term_cache( $object_ids, $object_type ) {
	if ( empty( $object_ids ) ) {
		return;
	}

	if ( ! is_array( $object_ids ) ) {
		$object_ids = explode( ',', $object_ids );
	}

	$object_ids     = array_map( 'intval', $object_ids );
	$non_cached_ids = array();

	$taxonomies = get_object_taxonomies( $object_type );

	foreach ( $taxonomies as $taxonomy ) {
		$cache_values = wp_cache_get_multiple( (array) $object_ids, "{$taxonomy}_relationships" );

		foreach ( $cache_values as $id => $value ) {
			if ( false === $value ) {
				$non_cached_ids[] = $id;
			}
		}
	}

	if ( empty( $non_cached_ids ) ) {
		return false;
	}

	$non_cached_ids = array_unique( $non_cached_ids );

	$terms = wp_get_object_terms(
		$non_cached_ids,
		$taxonomies,
		array(
			'fields'                 => 'all_with_object_id',
			'orderby'                => 'name',
			'update_term_meta_cache' => false,
		)
	);

	$object_terms = array();
	foreach ( (array) $terms as $term ) {
		$object_terms[ $term->object_id ][ $term->taxonomy ][] = $term->term_id;
	}

	foreach ( $non_cached_ids as $id ) {
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $object_terms[ $id ][ $taxonomy ] ) ) {
				if ( ! isset( $object_terms[ $id ] ) ) {
					$object_terms[ $id ] = array();
				}
				$object_terms[ $id ][ $taxonomy ] = array();
			}
		}
	}

	$cache_values = array();
	foreach ( $object_terms as $id => $value ) {
		foreach ( $value as $taxonomy => $terms ) {
			$cache_values[ $taxonomy ][ $id ] = $terms;
		}
	}
	foreach ( $cache_values as $taxonomy => $data ) {
		wp_cache_add_multiple( $data, "{$taxonomy}_relationships" );
	}
}

/**
 * Cập nhật các term trong bộ nhớ đệm.
 *
 * @since 2.3.0
 *
 * @param WP_Term[] $terms    Mảng các đối tượng term cần thay đổi.
 * @param string    $taxonomy Không sử dụng.
 */
function update_term_cache( $terms, $taxonomy = '' ) {
	$data = array();
	foreach ( (array) $terms as $term ) {
		// Tạo bản sao phòng trường hợp mảng được truyền theo tham chiếu.
		$_term = clone $term;

		// ID đối tượng không nên được lưu đệm.
		unset( $_term->object_id );

		$data[ $term->term_id ] = $_term;
	}
	wp_cache_add_multiple( $data, 'terms' );
}

//
// Riêng tư.
//

/**
 * Lấy các term con của taxonomy dưới dạng ID term.
 *
 * @access private
 * @since 2.3.0
 *
 * @param string $taxonomy Tên taxonomy.
 * @return array Rỗng nếu $taxonomy không phân cấp hoặc trả về các term con dưới dạng ID term.
 */
function _get_term_hierarchy( $taxonomy ) {
	if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
		return array();
	}
	$children = get_option( "{$taxonomy}_children" );

	if ( is_array( $children ) ) {
		return $children;
	}
	$children = array();
	$terms    = get_terms(
		array(
			'taxonomy'               => $taxonomy,
			'get'                    => 'all',
			'orderby'                => 'id',
			'fields'                 => 'id=>parent',
			'update_term_meta_cache' => false,
		)
	);
	foreach ( $terms as $term_id => $parent ) {
		if ( $parent > 0 ) {
			$children[ $parent ][] = $term_id;
		}
	}
	update_option( "{$taxonomy}_children", $children );

	return $children;
}

/**
 * Lấy tập con của $terms là hậu duệ của $term_id.
 *
 * Nếu `$terms` là mảng các đối tượng, thì _get_term_children() trả về mảng các đối tượng.
 * Nếu `$terms` là mảng các ID, thì _get_term_children() trả về mảng các ID.
 *
 * @access private
 * @since 2.3.0
 *
 * @param int    $term_id   Term tổ tiên: tất cả term trả về phải là hậu duệ của `$term_id`.
 * @param array  $terms     Tập hợp các term - mảng đối tượng term hoặc ID term - từ đó những term
 *                          là hậu duệ của $term_id sẽ được chọn.
 * @param string $taxonomy  Taxonomy xác định phân cấp của các term.
 * @param array  $ancestors Tùy chọn. Các term tổ tiên đã được xác định. Truyền theo tham chiếu, để theo dõi
 *                          các term tìm thấy khi duyệt đệ quy phân cấp. Mảng các tổ tiên đã xác định được dùng
 *                          để ngăn vòng lặp đệ quy vô hạn. Để tăng hiệu suất, `term_ids` được dùng làm khóa mảng,
 *                          với giá trị là 1. Mặc định mảng rỗng.
 * @return array|WP_Error Tập con của $terms là hậu duệ của $term_id.
 */
function _get_term_children( $term_id, $terms, $taxonomy, &$ancestors = array() ) {
	$empty_array = array();
	if ( empty( $terms ) ) {
		return $empty_array;
	}

	$term_id      = (int) $term_id;
	$term_list    = array();
	$has_children = _get_term_hierarchy( $taxonomy );

	if ( $term_id && ! isset( $has_children[ $term_id ] ) ) {
		return $empty_array;
	}

	// Bao gồm chính term trong mảng tổ tiên, để có thể phát hiện đúng khi vòng lặp xảy ra.
	if ( empty( $ancestors ) ) {
		$ancestors[ $term_id ] = 1;
	}

	foreach ( (array) $terms as $term ) {
		$use_id = false;
		if ( ! is_object( $term ) ) {
			$term = get_term( $term, $taxonomy );
			if ( is_wp_error( $term ) ) {
				return $term;
			}
			$use_id = true;
		}

		// Không đệ quy nếu đã xác định term là con - điều này cho thấy có vòng lặp.
		if ( isset( $ancestors[ $term->term_id ] ) ) {
			continue;
		}

		if ( (int) $term->parent === $term_id ) {
			if ( $use_id ) {
				$term_list[] = $term->term_id;
			} else {
				$term_list[] = $term;
			}

			if ( ! isset( $has_children[ $term->term_id ] ) ) {
				continue;
			}

			$ancestors[ $term->term_id ] = 1;

			$children = _get_term_children( $term->term_id, $terms, $taxonomy, $ancestors );
			if ( $children ) {
				$term_list = array_merge( $term_list, $children );
			}
		}
	}

	return $term_list;
}

/**
 * Cộng số đếm của term con vào số đếm term cha.
 *
 * Tính lại số đếm term bằng cách bao gồm các mục từ term con. Giả định tất cả
 * các term con liên quan đã có trong tham số $terms.
 *
 * @access private
 * @since 2.3.0
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param object[]|WP_Term[] $terms    Danh sách các đối tượng term (truyền theo tham chiếu).
 * @param string             $taxonomy Ngữ cảnh term.
 */
function _pad_term_counts( &$terms, $taxonomy ) {
	global $wpdb;

	// Hàm này chỉ hoạt động cho các taxonomy phân cấp như chuyên mục bài viết.
	if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
		return;
	}

	$term_hier = _get_term_hierarchy( $taxonomy );

	if ( empty( $term_hier ) ) {
		return;
	}

	$term_items  = array();
	$terms_by_id = array();
	$term_ids    = array();

	foreach ( (array) $terms as $key => $term ) {
		$terms_by_id[ $term->term_id ]       = & $terms[ $key ];
		$term_ids[ $term->term_taxonomy_id ] = $term->term_id;
	}

	// Lấy các ID đối tượng và term rồi đưa vào bảng tra cứu.
	$tax_obj      = get_taxonomy( $taxonomy );
	$object_types = esc_sql( $tax_obj->object_type );
	$results      = $wpdb->get_results( "SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON object_id = ID WHERE term_taxonomy_id IN (" . implode( ',', array_keys( $term_ids ) ) . ") AND post_type IN ('" . implode( "', '", $object_types ) . "') AND post_status = 'publish'" );

	foreach ( $results as $row ) {
		$id = $term_ids[ $row->term_taxonomy_id ];

		$term_items[ $id ][ $row->object_id ] = isset( $term_items[ $id ][ $row->object_id ] ) ? ++$term_items[ $id ][ $row->object_id ] : 1;
	}

	// Cập nhật hàng tra cứu của mọi tổ tiên cho mỗi bài viết trong mỗi term.
	foreach ( $term_ids as $term_id ) {
		$child     = $term_id;
		$ancestors = array();
		while ( ! empty( $terms_by_id[ $child ] ) && $parent = $terms_by_id[ $child ]->parent ) {
			$ancestors[] = $child;

			if ( ! empty( $term_items[ $term_id ] ) ) {
				foreach ( $term_items[ $term_id ] as $item_id => $touches ) {
					$term_items[ $parent ][ $item_id ] = isset( $term_items[ $parent ][ $item_id ] ) ? ++$term_items[ $parent ][ $item_id ] : 1;
				}
			}

			$child = $parent;

			if ( in_array( $parent, $ancestors, true ) ) {
				break;
			}
		}
	}

	// Chuyển các ô đã được cập nhật.
	foreach ( (array) $term_items as $id => $items ) {
		if ( isset( $terms_by_id[ $id ] ) ) {
			$terms_by_id[ $id ]->count = count( $items );
		}
	}
}

/**
 * Thêm bất kỳ term nào từ các ID cho trước vào bộ nhớ đệm mà chưa tồn tại trong bộ nhớ đệm.
 *
 * @since 4.6.0
 * @since 6.1.0 Hàm này không còn được đánh dấu là "private".
 * @since 6.3.0 Sử dụng wp_lazyload_term_meta() để tải lười term meta.
 *
 * @global wpdb $wpdb Đối tượng trừu tượng cơ sở dữ liệu WordPress.
 *
 * @param array $term_ids          Mảng các ID term.
 * @param bool  $update_meta_cache Tùy chọn. Có cập nhật bộ nhớ đệm meta hay không. Mặc định true.
 */
function _prime_term_caches( $term_ids, $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $term_ids, 'terms' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_terms = $wpdb->get_results( sprintf( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (%s)", implode( ',', array_map( 'intval', $non_cached_ids ) ) ) );

		update_term_cache( $fresh_terms );
	}

	if ( $update_meta_cache ) {
		wp_lazyload_term_meta( $term_ids );
	}
}

//
// Default callbacks.
//

/**
 * Updates term count based on object types of the current taxonomy.
 *
 * Private function for the default callback for post_tag and category
 * taxonomies.
 *
 * @access private
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int[]       $terms    List of term taxonomy IDs.
 * @param WP_Taxonomy $taxonomy Current taxonomy object of terms.
 */
function _update_post_term_count( $terms, $taxonomy ) {
	global $wpdb;

	$object_types = (array) $taxonomy->object_type;

	foreach ( $object_types as &$object_type ) {
		list( $object_type ) = explode( ':', $object_type );
	}

	$object_types = array_unique( $object_types );

	$check_attachments = array_search( 'attachment', $object_types, true );
	if ( false !== $check_attachments ) {
		unset( $object_types[ $check_attachments ] );
		$check_attachments = true;
	}

	if ( $object_types ) {
		$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );
	}

	$post_statuses = array( 'publish' );

	/**
	 * Filters the post statuses for updating the term count.
	 *
	 * @since 5.7.0
	 *
	 * @param string[]    $post_statuses List of post statuses to include in the count. Default is 'publish'.
	 * @param WP_Taxonomy $taxonomy      Current taxonomy object.
	 */
	$post_statuses = esc_sql( apply_filters( 'update_post_term_count_statuses', $post_statuses, $taxonomy ) );

	foreach ( (array) $terms as $term ) {
		$count = 0;

		// Attachments can be 'inherit' status, we need to base count off the parent's status if so.
		if ( $check_attachments ) {
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status IN ('" . implode( "', '", $post_statuses ) . "') OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) IN ('" . implode( "', '", $post_statuses ) . "') ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term ) );
		}

		if ( $object_types ) {
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ('" . implode( "', '", $post_statuses ) . "') AND post_type IN ('" . implode( "', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );
		}

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_term_taxonomy', $term, $taxonomy->name );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
	}
}

/**
 * Updates term count based on number of objects.
 *
 * Default callback for the 'link_category' taxonomy.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int[]       $terms    List of term taxonomy IDs.
 * @param WP_Taxonomy $taxonomy Current taxonomy object of terms.
 */
function _update_generic_term_count( $terms, $taxonomy ) {
	global $wpdb;

	foreach ( (array) $terms as $term ) {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_term_taxonomy', $term, $taxonomy->name );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
	}
}

/**
 * Creates a new term for a term_taxonomy item that currently shares its term
 * with another term_taxonomy.
 *
 * @ignore
 * @since 4.2.0
 * @since 4.3.0 Introduced `$record` parameter. Also, `$term_id` and
 *              `$term_taxonomy_id` can now accept objects.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|object $term_id          ID of the shared term, or the shared term object.
 * @param int|object $term_taxonomy_id ID of the term_taxonomy item to receive a new term, or the term_taxonomy object
 *                                     (corresponding to a row from the term_taxonomy table).
 * @param bool       $record           Whether to record data about the split term in the options table. The recording
 *                                     process has the potential to be resource-intensive, so during batch operations
 *                                     it can be beneficial to skip inline recording and do it just once, after the
 *                                     batch is processed. Only set this to `false` if you know what you are doing.
 *                                     Default: true.
 * @return int|WP_Error When the current term does not need to be split (or cannot be split on the current
 *                      database schema), `$term_id` is returned. When the term is successfully split, the
 *                      new term_id is returned. A WP_Error is returned for miscellaneous errors.
 */
function _split_shared_term( $term_id, $term_taxonomy_id, $record = true ) {
	global $wpdb;

	if ( is_object( $term_id ) ) {
		$shared_term = $term_id;
		$term_id     = (int) $shared_term->term_id;
	}

	if ( is_object( $term_taxonomy_id ) ) {
		$term_taxonomy    = $term_taxonomy_id;
		$term_taxonomy_id = (int) $term_taxonomy->term_taxonomy_id;
	}

	// If there are no shared term_taxonomy rows, there's nothing to do here.
	$shared_tt_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy tt WHERE tt.term_id = %d AND tt.term_taxonomy_id != %d", $term_id, $term_taxonomy_id ) );

	if ( ! $shared_tt_count ) {
		return $term_id;
	}

	/*
	 * Verify that the term_taxonomy_id passed to the function is actually associated with the term_id.
	 * If there's a mismatch, it may mean that the term is already split. Return the actual term_id from the db.
	 */
	$check_term_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $term_taxonomy_id ) );
	if ( $check_term_id !== $term_id ) {
		return $check_term_id;
	}

	// Pull up data about the currently shared slug, which we'll use to populate the new one.
	if ( empty( $shared_term ) ) {
		$shared_term = $wpdb->get_row( $wpdb->prepare( "SELECT t.* FROM $wpdb->terms t WHERE t.term_id = %d", $term_id ) );
	}

	$new_term_data = array(
		'name'       => $shared_term->name,
		'slug'       => $shared_term->slug,
		'term_group' => $shared_term->term_group,
	);

	if ( false === $wpdb->insert( $wpdb->terms, $new_term_data ) ) {
		return new WP_Error( 'db_insert_error', __( 'Could not split shared term.' ), $wpdb->last_error );
	}

	$new_term_id = (int) $wpdb->insert_id;

	// Update the existing term_taxonomy to point to the newly created term.
	$wpdb->update(
		$wpdb->term_taxonomy,
		array( 'term_id' => $new_term_id ),
		array( 'term_taxonomy_id' => $term_taxonomy_id )
	);

	// Reassign child terms to the new parent.
	if ( empty( $term_taxonomy ) ) {
		$term_taxonomy = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $term_taxonomy_id ) );
	}

	$children_tt_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE parent = %d AND taxonomy = %s", $term_id, $term_taxonomy->taxonomy ) );
	if ( ! empty( $children_tt_ids ) ) {
		foreach ( $children_tt_ids as $child_tt_id ) {
			$wpdb->update(
				$wpdb->term_taxonomy,
				array( 'parent' => $new_term_id ),
				array( 'term_taxonomy_id' => $child_tt_id )
			);
			clean_term_cache( (int) $child_tt_id, '', false );
		}
	} else {
		// If the term has no children, we must force its taxonomy cache to be rebuilt separately.
		clean_term_cache( $new_term_id, $term_taxonomy->taxonomy, false );
	}

	clean_term_cache( $term_id, $term_taxonomy->taxonomy, false );

	/*
	 * Taxonomy cache clearing is delayed to avoid race conditions that may occur when
	 * regenerating the taxonomy's hierarchy tree.
	 */
	$taxonomies_to_clean = array( $term_taxonomy->taxonomy );

	// Clean the cache for term taxonomies formerly shared with the current term.
	$shared_term_taxonomies = $wpdb->get_col( $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) );
	$taxonomies_to_clean    = array_merge( $taxonomies_to_clean, $shared_term_taxonomies );

	foreach ( $taxonomies_to_clean as $taxonomy_to_clean ) {
		clean_taxonomy_cache( $taxonomy_to_clean );
	}

	// Keep a record of term_ids that have been split, keyed by old term_id. See wp_get_split_term().
	if ( $record ) {
		$split_term_data = get_option( '_split_terms', array() );
		if ( ! isset( $split_term_data[ $term_id ] ) ) {
			$split_term_data[ $term_id ] = array();
		}

		$split_term_data[ $term_id ][ $term_taxonomy->taxonomy ] = $new_term_id;
		update_option( '_split_terms', $split_term_data );
	}

	// If we've just split the final shared term, set the "finished" flag.
	$shared_terms_exist = $wpdb->get_results(
		"SELECT tt.term_id, t.*, count(*) as term_tt_count FROM {$wpdb->term_taxonomy} tt
		 LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 GROUP BY t.term_id
		 HAVING term_tt_count > 1
		 LIMIT 1"
	);
	if ( ! $shared_terms_exist ) {
		update_option( 'finished_splitting_shared_terms', true );
	}

	/**
	 * Fires after a previously shared taxonomy term is split into two separate terms.
	 *
	 * @since 4.2.0
	 *
	 * @param int    $term_id          ID of the formerly shared term.
	 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
	 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
	 * @param string $taxonomy         Taxonomy for the split term.
	 */
	do_action( 'split_shared_term', $term_id, $new_term_id, $term_taxonomy_id, $term_taxonomy->taxonomy );

	return $new_term_id;
}

/**
 * Splits a batch of shared taxonomy terms.
 *
 * @since 4.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function _wp_batch_split_terms() {
	global $wpdb;

	$lock_name = 'term_split.lock';

	// Try to lock.
	$lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'off') /* LOCK */", $lock_name, time() ) );

	if ( ! $lock_result ) {
		$lock_result = get_option( $lock_name );

		// Bail if we were unable to create a lock, or if the existing lock is still valid.
		if ( ! $lock_result || ( $lock_result > ( time() - HOUR_IN_SECONDS ) ) ) {
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'wp_split_shared_term_batch' );
			return;
		}
	}

	// Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
	update_option( $lock_name, time() );

	// Get a list of shared terms (those with more than one associated row in term_taxonomy).
	$shared_terms = $wpdb->get_results(
		"SELECT tt.term_id, t.*, count(*) as term_tt_count FROM {$wpdb->term_taxonomy} tt
		 LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 GROUP BY t.term_id
		 HAVING term_tt_count > 1
		 LIMIT 10"
	);

	// No more terms, we're done here.
	if ( ! $shared_terms ) {
		update_option( 'finished_splitting_shared_terms', true );
		delete_option( $lock_name );
		return;
	}

	// Shared terms found? We'll need to run this script again.
	wp_schedule_single_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'wp_split_shared_term_batch' );

	// Rekey shared term array for faster lookups.
	$_shared_terms = array();
	foreach ( $shared_terms as $shared_term ) {
		$term_id                   = (int) $shared_term->term_id;
		$_shared_terms[ $term_id ] = $shared_term;
	}
	$shared_terms = $_shared_terms;

	// Get term taxonomy data for all shared terms.
	$shared_term_ids = implode( ',', array_keys( $shared_terms ) );
	$shared_tts      = $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} WHERE `term_id` IN ({$shared_term_ids})" );

	// Split term data recording is slow, so we do it just once, outside the loop.
	$split_term_data    = get_option( '_split_terms', array() );
	$skipped_first_term = array();
	$taxonomies         = array();
	foreach ( $shared_tts as $shared_tt ) {
		$term_id = (int) $shared_tt->term_id;

		// Don't split the first tt belonging to a given term_id.
		if ( ! isset( $skipped_first_term[ $term_id ] ) ) {
			$skipped_first_term[ $term_id ] = 1;
			continue;
		}

		if ( ! isset( $split_term_data[ $term_id ] ) ) {
			$split_term_data[ $term_id ] = array();
		}

		// Keep track of taxonomies whose hierarchies need flushing.
		if ( ! isset( $taxonomies[ $shared_tt->taxonomy ] ) ) {
			$taxonomies[ $shared_tt->taxonomy ] = 1;
		}

		// Split the term.
		$split_term_data[ $term_id ][ $shared_tt->taxonomy ] = _split_shared_term( $shared_terms[ $term_id ], $shared_tt, false );
	}

	// Rebuild the cached hierarchy for each affected taxonomy.
	foreach ( array_keys( $taxonomies ) as $tax ) {
		delete_option( "{$tax}_children" );
		_get_term_hierarchy( $tax );
	}

	update_option( '_split_terms', $split_term_data );

	delete_option( $lock_name );
}

/**
 * In order to avoid the _wp_batch_split_terms() job being accidentally removed,
 * checks that it's still scheduled while we haven't finished splitting terms.
 *
 * @ignore
 * @since 4.3.0
 */
function _wp_check_for_scheduled_split_terms() {
	if ( ! get_option( 'finished_splitting_shared_terms' ) && ! wp_next_scheduled( 'wp_split_shared_term_batch' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wp_split_shared_term_batch' );
	}
}

/**
 * Checks default categories when a term gets split to see if any of them need to be updated.
 *
 * @ignore
 * @since 4.2.0
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_default_terms( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	if ( 'category' !== $taxonomy ) {
		return;
	}

	foreach ( array( 'default_category', 'default_link_category', 'default_email_category' ) as $option ) {
		if ( (int) get_option( $option, -1 ) === $term_id ) {
			update_option( $option, $new_term_id );
		}
	}
}

/**
 * Checks menu items when a term gets split to see if any of them need to be updated.
 *
 * @ignore
 * @since 4.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_terms_in_menus( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	global $wpdb;
	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT m1.post_id
		FROM {$wpdb->postmeta} AS m1
			INNER JOIN {$wpdb->postmeta} AS m2 ON ( m2.post_id = m1.post_id )
			INNER JOIN {$wpdb->postmeta} AS m3 ON ( m3.post_id = m1.post_id )
		WHERE ( m1.meta_key = '_menu_item_type' AND m1.meta_value = 'taxonomy' )
			AND ( m2.meta_key = '_menu_item_object' AND m2.meta_value = %s )
			AND ( m3.meta_key = '_menu_item_object_id' AND m3.meta_value = %d )",
			$taxonomy,
			$term_id
		)
	);

	if ( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_menu_item_object_id', $new_term_id, $term_id );
		}
	}
}

/**
 * If the term being split is a nav_menu, changes associations.
 *
 * @ignore
 * @since 4.3.0
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_nav_menu_terms( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	if ( 'nav_menu' !== $taxonomy ) {
		return;
	}

	// Update menu locations.
	$locations = get_nav_menu_locations();
	foreach ( $locations as $location => $menu_id ) {
		if ( $term_id === $menu_id ) {
			$locations[ $location ] = $new_term_id;
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Gets data about terms that previously shared a single term_id, but have since been split.
 *
 * @since 4.2.0
 *
 * @param int $old_term_id Term ID. This is the old, pre-split term ID.
 * @return array Array of new term IDs, keyed by taxonomy.
 */
function wp_get_split_terms( $old_term_id ) {
	$split_terms = get_option( '_split_terms', array() );

	$terms = array();
	if ( isset( $split_terms[ $old_term_id ] ) ) {
		$terms = $split_terms[ $old_term_id ];
	}

	return $terms;
}

/**
 * Gets the new term ID corresponding to a previously split term.
 *
 * @since 4.2.0
 *
 * @param int    $old_term_id Term ID. This is the old, pre-split term ID.
 * @param string $taxonomy    Taxonomy that the term belongs to.
 * @return int|false If a previously split term is found corresponding to the old term_id and taxonomy,
 *                   the new term_id will be returned. If no previously split term is found matching
 *                   the parameters, returns false.
 */
function wp_get_split_term( $old_term_id, $taxonomy ) {
	$split_terms = wp_get_split_terms( $old_term_id );

	$term_id = false;
	if ( isset( $split_terms[ $taxonomy ] ) ) {
		$term_id = (int) $split_terms[ $taxonomy ];
	}

	return $term_id;
}

/**
 * Determines whether a term is shared between multiple taxonomies.
 *
 * Shared taxonomy terms began to be split in 4.3, but failed cron tasks or
 * other delays in upgrade routines may cause shared terms to remain.
 *
 * @since 4.4.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $term_id Term ID.
 * @return bool Returns false if a term is not shared between multiple taxonomies or
 *              if splitting shared taxonomy terms is finished.
 */
function wp_term_is_shared( $term_id ) {
	global $wpdb;

	if ( get_option( 'finished_splitting_shared_terms' ) ) {
		return false;
	}

	$tt_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) );

	return $tt_count > 1;
}

/**
 * Generates a permalink for a taxonomy term archive.
 *
 * @since 2.5.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param WP_Term|int|string $term     The term object, ID, or slug whose link will be retrieved.
 * @param string             $taxonomy Optional. Taxonomy. Default empty.
 * @return string|WP_Error URL of the taxonomy term archive on success, WP_Error if term does not exist.
 */
function get_term_link( $term, $taxonomy = '' ) {
	global $wp_rewrite;

	if ( ! is_object( $term ) ) {
		if ( is_int( $term ) ) {
			$term = get_term( $term, $taxonomy );
		} else {
			$term = get_term_by( 'slug', $term, $taxonomy );
		}
	}

	if ( ! is_object( $term ) ) {
		$term = new WP_Error( 'invalid_term', __( 'Empty Term.' ) );
	}

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	$taxonomy = $term->taxonomy;

	$termlink = $wp_rewrite->get_extra_permastruct( $taxonomy );

	/**
	 * Filters the permalink structure for a term before token replacement occurs.
	 *
	 * @since 4.9.0
	 *
	 * @param string  $termlink The permalink structure for the term's taxonomy.
	 * @param WP_Term $term     The term object.
	 */
	$termlink = apply_filters( 'pre_term_link', $termlink, $term );

	$slug = $term->slug;
	$t    = get_taxonomy( $taxonomy );

	if ( empty( $termlink ) ) {
		if ( 'category' === $taxonomy ) {
			$termlink = '?cat=' . $term->term_id;
		} elseif ( $t->query_var ) {
			$termlink = "?$t->query_var=$slug";
		} else {
			$termlink = "?taxonomy=$taxonomy&term=$slug";
		}
		$termlink = home_url( $termlink );
	} else {
		if ( ! empty( $t->rewrite['hierarchical'] ) ) {
			$hierarchical_slugs = array();
			$ancestors          = get_ancestors( $term->term_id, $taxonomy, 'taxonomy' );
			foreach ( (array) $ancestors as $ancestor ) {
				$ancestor_term        = get_term( $ancestor, $taxonomy );
				$hierarchical_slugs[] = $ancestor_term->slug;
			}
			$hierarchical_slugs   = array_reverse( $hierarchical_slugs );
			$hierarchical_slugs[] = $slug;
			$termlink             = str_replace( "%$taxonomy%", implode( '/', $hierarchical_slugs ), $termlink );
		} else {
			$termlink = str_replace( "%$taxonomy%", $slug, $termlink );
		}
		$termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
	}

	// Back compat filters.
	if ( 'post_tag' === $taxonomy ) {

		/**
		 * Filters the tag link.
		 *
		 * @since 2.3.0
		 * @since 2.5.0 Deprecated in favor of {@see 'term_link'} filter.
		 * @since 5.4.1 Restored (un-deprecated).
		 *
		 * @param string $termlink Tag link URL.
		 * @param int    $term_id  Term ID.
		 */
		$termlink = apply_filters( 'tag_link', $termlink, $term->term_id );
	} elseif ( 'category' === $taxonomy ) {

		/**
		 * Filters the category link.
		 *
		 * @since 1.5.0
		 * @since 2.5.0 Deprecated in favor of {@see 'term_link'} filter.
		 * @since 5.4.1 Restored (un-deprecated).
		 *
		 * @param string $termlink Category link URL.
		 * @param int    $term_id  Term ID.
		 */
		$termlink = apply_filters( 'category_link', $termlink, $term->term_id );
	}

	/**
	 * Filters the term link.
	 *
	 * @since 2.5.0
	 *
	 * @param string  $termlink Term link URL.
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 */
	return apply_filters( 'term_link', $termlink, $term, $taxonomy );
}

/**
 * Displays the taxonomies of a post with available options.
 *
 * This function can be used within the loop to display the taxonomies for a
 * post without specifying the Post ID. You can also use it outside the Loop to
 * display the taxonomies for a specific post.
 *
 * @since 2.5.0
 *
 * @param array $args {
 *     Arguments about which post to use and how to format the output. Shares all of the arguments
 *     supported by get_the_taxonomies(), in addition to the following.
 *
 *     @type int|WP_Post $post   Post ID or object to get taxonomies of. Default current post.
 *     @type string      $before Displays before the taxonomies. Default empty string.
 *     @type string      $sep    Separates each taxonomy. Default is a space.
 *     @type string      $after  Displays after the taxonomies. Default empty string.
 * }
 */
function the_taxonomies( $args = array() ) {
	$defaults = array(
		'post'   => 0,
		'before' => '',
		'sep'    => ' ',
		'after'  => '',
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	echo $parsed_args['before'] . implode( $parsed_args['sep'], get_the_taxonomies( $parsed_args['post'], $parsed_args ) ) . $parsed_args['after'];
}

/**
 * Retrieves all taxonomies associated with a post.
 *
 * This function can be used within the loop. It will also return an array of
 * the taxonomies with links to the taxonomy and name.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param array       $args {
 *           Optional. Arguments about how to format the list of taxonomies. Default empty array.
 *
 *     @type string $template      Template for displaying a taxonomy label and list of terms.
 *                                 Default is "Label: Terms."
 *     @type string $term_template Template for displaying a single term in the list. Default is the term name
 *                                 linked to its archive.
 * }
 * @return string[] List of taxonomies.
 */
function get_the_taxonomies( $post = 0, $args = array() ) {
	$post = get_post( $post );

	$args = wp_parse_args(
		$args,
		array(
			/* translators: %s: Taxonomy label, %l: List of terms formatted as per $term_template. */
			'template'      => __( '%s: %l.' ),
			'term_template' => '<a href="%1$s">%2$s</a>',
		)
	);

	$taxonomies = array();

	if ( ! $post ) {
		return $taxonomies;
	}

	foreach ( get_object_taxonomies( $post ) as $taxonomy ) {
		$t = (array) get_taxonomy( $taxonomy );
		if ( empty( $t['label'] ) ) {
			$t['label'] = $taxonomy;
		}
		if ( empty( $t['args'] ) ) {
			$t['args'] = array();
		}
		if ( empty( $t['template'] ) ) {
			$t['template'] = $args['template'];
		}
		if ( empty( $t['term_template'] ) ) {
			$t['term_template'] = $args['term_template'];
		}

		$terms = get_object_term_cache( $post->ID, $taxonomy );
		if ( false === $terms ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy, $t['args'] );
		}
		$links = array();

		foreach ( $terms as $term ) {
			$links[] = wp_sprintf( $t['term_template'], esc_attr( get_term_link( $term ) ), $term->name );
		}
		if ( $links ) {
			$taxonomies[ $taxonomy ] = wp_sprintf( $t['template'], $t['label'], $links, $terms );
		}
	}
	return $taxonomies;
}

/**
 * Retrieves all taxonomy names for the given post.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return string[] An array of all taxonomy names for the given post.
 */
function get_post_taxonomies( $post = 0 ) {
	$post = get_post( $post );

	return get_object_taxonomies( $post );
}

/**
 * Determines if the given object is associated with any of the given terms.
 *
 * The given terms are checked against the object's terms' term_ids, names and slugs.
 * Terms given as integers will only be checked against the object's terms' term_ids.
 * If no terms are given, determines if object is associated with any terms in the given taxonomy.
 *
 * @since 2.7.0
 *
 * @param int                       $object_id ID of the object (post ID, link ID, ...).
 * @param string                    $taxonomy  Single taxonomy name.
 * @param int|string|int[]|string[] $terms     Optional. Term ID, name, slug, or array of such
 *                                             to check against. Default null.
 * @return bool|WP_Error WP_Error on input error.
 */
function is_object_in_term( $object_id, $taxonomy, $terms = null ) {
	$object_id = (int) $object_id;
	if ( ! $object_id ) {
		return new WP_Error( 'invalid_object', __( 'Invalid object ID.' ) );
	}

	$object_terms = get_object_term_cache( $object_id, $taxonomy );
	if ( false === $object_terms ) {
		$object_terms = wp_get_object_terms( $object_id, $taxonomy, array( 'update_term_meta_cache' => false ) );
		if ( is_wp_error( $object_terms ) ) {
			return $object_terms;
		}

		wp_cache_set( $object_id, wp_list_pluck( $object_terms, 'term_id' ), "{$taxonomy}_relationships" );
	}

	if ( is_wp_error( $object_terms ) ) {
		return $object_terms;
	}
	if ( empty( $object_terms ) ) {
		return false;
	}
	if ( empty( $terms ) ) {
		return ( ! empty( $object_terms ) );
	}

	$terms = (array) $terms;

	$ints = array_filter( $terms, 'is_int' );
	if ( $ints ) {
		$strs = array_diff( $terms, $ints );
	} else {
		$strs =& $terms;
	}

	foreach ( $object_terms as $object_term ) {
		// If term is an int, check against term_ids only.
		if ( $ints && in_array( $object_term->term_id, $ints, true ) ) {
			return true;
		}

		if ( $strs ) {
			// Only check numeric strings against term_id, to avoid false matches due to type juggling.
			$numeric_strs = array_map( 'intval', array_filter( $strs, 'is_numeric' ) );
			if ( in_array( $object_term->term_id, $numeric_strs, true ) ) {
				return true;
			}

			if ( in_array( $object_term->name, $strs, true ) ) {
				return true;
			}
			if ( in_array( $object_term->slug, $strs, true ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Determines if the given object type is associated with the given taxonomy.
 *
 * @since 3.0.0
 *
 * @param string $object_type Object type string.
 * @param string $taxonomy    Single taxonomy name.
 * @return bool True if object is associated with the taxonomy, otherwise false.
 */
function is_object_in_taxonomy( $object_type, $taxonomy ) {
	$taxonomies = get_object_taxonomies( $object_type );
	if ( empty( $taxonomies ) ) {
		return false;
	}
	return in_array( $taxonomy, $taxonomies, true );
}

/**
 * Gets an array of ancestor IDs for a given object.
 *
 * @since 3.1.0
 * @since 4.1.0 Introduced the `$resource_type` argument.
 *
 * @param int    $object_id     Optional. The ID of the object. Default 0.
 * @param string $object_type   Optional. The type of object for which we'll be retrieving
 *                              ancestors. Accepts a post type or a taxonomy name. Default empty.
 * @param string $resource_type Optional. Type of resource $object_type is. Accepts 'post_type'
 *                              or 'taxonomy'. Default empty.
 * @return int[] An array of IDs of ancestors from lowest to highest in the hierarchy.
 */
function get_ancestors( $object_id = 0, $object_type = '', $resource_type = '' ) {
	$object_id = (int) $object_id;

	$ancestors = array();

	if ( empty( $object_id ) ) {

		/** This filter is documented in wp-includes/taxonomy.php */
		return apply_filters( 'get_ancestors', $ancestors, $object_id, $object_type, $resource_type );
	}

	if ( ! $resource_type ) {
		if ( is_taxonomy_hierarchical( $object_type ) ) {
			$resource_type = 'taxonomy';
		} elseif ( post_type_exists( $object_type ) ) {
			$resource_type = 'post_type';
		}
	}

	if ( 'taxonomy' === $resource_type ) {
		$term = get_term( $object_id, $object_type );
		while ( ! is_wp_error( $term ) && ! empty( $term->parent ) && ! in_array( $term->parent, $ancestors, true ) ) {
			$ancestors[] = (int) $term->parent;
			$term        = get_term( $term->parent, $object_type );
		}
	} elseif ( 'post_type' === $resource_type ) {
		$ancestors = get_post_ancestors( $object_id );
	}

	/**
	 * Filters a given object's ancestors.
	 *
	 * @since 3.1.0
	 * @since 4.1.1 Introduced the `$resource_type` parameter.
	 *
	 * @param int[]  $ancestors     An array of IDs of object ancestors.
	 * @param int    $object_id     Object ID.
	 * @param string $object_type   Type of object.
	 * @param string $resource_type Type of resource $object_type is.
	 */
	return apply_filters( 'get_ancestors', $ancestors, $object_id, $object_type, $resource_type );
}

/**
 * Returns the term's parent's term ID.
 *
 * @since 3.1.0
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @return int|false Parent term ID on success, false on failure.
 */
function wp_get_term_taxonomy_parent_id( $term_id, $taxonomy ) {
	$term = get_term( $term_id, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}
	return (int) $term->parent;
}

/**
 * Checks the given subset of the term hierarchy for hierarchy loops.
 * Prevents loops from forming and breaks those that it finds.
 *
 * Attached to the {@see 'wp_update_term_parent'} filter.
 *
 * @since 3.1.0
 *
 * @param int    $parent_term `term_id` of the parent for the term we're checking.
 * @param int    $term_id     The term we're checking.
 * @param string $taxonomy    The taxonomy of the term we're checking.
 * @return int The new parent for the term.
 */
function wp_check_term_hierarchy_for_loops( $parent_term, $term_id, $taxonomy ) {
	// Nothing fancy here - bail.
	if ( ! $parent_term ) {
		return 0;
	}

	// Can't be its own parent.
	if ( $parent_term === $term_id ) {
		return 0;
	}

	// Now look for larger loops.
	$loop = wp_find_hierarchy_loop( 'wp_get_term_taxonomy_parent_id', $term_id, $parent_term, array( $taxonomy ) );
	if ( ! $loop ) {
		return $parent_term; // No loop.
	}

	// Setting $parent_term to the given value causes a loop.
	if ( isset( $loop[ $term_id ] ) ) {
		return 0;
	}

	// There's a loop, but it doesn't contain $term_id. Break the loop.
	foreach ( array_keys( $loop ) as $loop_member ) {
		wp_update_term( $loop_member, $taxonomy, array( 'parent' => 0 ) );
	}

	return $parent_term;
}

/**
 * Determines whether a taxonomy is considered "viewable".
 *
 * @since 5.1.0
 *
 * @param string|WP_Taxonomy $taxonomy Taxonomy name or object.
 * @return bool Whether the taxonomy should be considered viewable.
 */
function is_taxonomy_viewable( $taxonomy ) {
	if ( is_scalar( $taxonomy ) ) {
		$taxonomy = get_taxonomy( $taxonomy );
		if ( ! $taxonomy ) {
			return false;
		}
	}

	return $taxonomy->publicly_queryable;
}

/**
 * Determines whether a term is publicly viewable.
 *
 * A term is considered publicly viewable if its taxonomy is viewable.
 *
 * @since 6.1.0
 *
 * @param int|WP_Term $term Term ID or term object.
 * @return bool Whether the term is publicly viewable.
 */
function is_term_publicly_viewable( $term ) {
	$term = get_term( $term );

	if ( ! $term ) {
		return false;
	}

	return is_taxonomy_viewable( $term->taxonomy );
}

/**
 * Sets the last changed time for the 'terms' cache group.
 *
 * @since 5.0.0
 */
function wp_cache_set_terms_last_changed() {
	wp_cache_set_last_changed( 'terms' );
}

/**
 * Aborts calls to term meta if it is not supported.
 *
 * @since 5.0.0
 *
 * @param mixed $check Skip-value for whether to proceed term meta function execution.
 * @return mixed Original value of $check, or false if term meta is not supported.
 */
function wp_check_term_meta_support_prefilter( $check ) {
	if ( get_option( 'db_version' ) < 34370 ) {
		return false;
	}

	return $check;
}
