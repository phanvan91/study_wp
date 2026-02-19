<?php
/**
 * API Menu Điều hướng: Các hàm template
 *
 * @package WordPress
 * @subpackage Nav_Menus
 * @since 3.0.0
 */

// Không tải trực tiếp.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/** Lớp Walker_Nav_Menu */
require_once ABSPATH . WPINC . '/class-walker-nav-menu.php';

/**
 * Hiển thị menu điều hướng.
 *
 * @since 3.0.0
 * @since 4.7.0 Thêm đối số `item_spacing`.
 * @since 5.5.0 Thêm đối số `container_aria_label`.
 *
 * @param array $args {
 *     Tùy chọn. Mảng các đối số menu điều hướng.
 *
 *     @type int|string|WP_Term $menu                 Menu mong muốn. Chấp nhận ID menu, slug, tên, hoặc đối tượng.
 *                                                    Mặc định rỗng.
 *     @type string             $menu_class           Lớp CSS sử dụng cho phần tử ul tạo thành menu.
 *                                                    Mặc định 'menu'.
 *     @type string             $menu_id              ID được áp dụng cho phần tử ul tạo thành menu.
 *                                                    Mặc định là slug menu, tăng dần.
 *     @type string             $container            Có bọc phần tử ul hay không, và bọc bằng gì.
 *                                                    Mặc định 'div'.
 *     @type string             $container_class      Lớp được áp dụng cho container.
 *                                                    Mặc định 'menu-{menu slug}-container'.
 *     @type string             $container_id         ID được áp dụng cho container. Mặc định rỗng.
 *     @type string             $container_aria_label Thuộc tính aria-label được áp dụng cho container
 *                                                    khi nó là phần tử nav. Mặc định rỗng.
 *     @type callable|false     $fallback_cb          Nếu menu không tồn tại, hàm callback dự phòng sẽ được gọi.
 *                                                    Mặc định 'wp_page_menu'. Đặt false để không có dự phòng.
 *     @type string             $before               Văn bản trước markup liên kết. Mặc định rỗng.
 *     @type string             $after                Văn bản sau markup liên kết. Mặc định rỗng.
 *     @type string             $link_before          Văn bản trước nội dung liên kết. Mặc định rỗng.
 *     @type string             $link_after           Văn bản sau nội dung liên kết. Mặc định rỗng.
 *     @type bool               $echo                 Có echo menu hay trả về nó. Mặc định true.
 *     @type int                $depth                Bao nhiêu cấp của hệ thống phân cấp sẽ được bao gồm.
 *                                                    0 nghĩa là tất cả. Mặc định 0.
 *                                                    Mặc định 0.
 *     @type object             $walker               Thể hiện của lớp walker tùy chỉnh. Mặc định rỗng.
 *     @type string             $theme_location       Vị trí theme sẽ được sử dụng. Phải đăng ký với
 *                                                    register_nav_menu() để người dùng có thể chọn.
 *     @type string             $items_wrap           Cách các mục danh sách nên được bọc. Sử dụng định dạng printf() với
 *                                                    các placeholder được đánh số. Mặc định là ul với id và class.
 *     @type string             $item_spacing         Có giữ khoảng trắng trong HTML của menu hay không.
 *                                                    Chấp nhận 'preserve' hoặc 'discard'. Mặc định 'preserve'.
 * }
 * @return void|string|false Void nếu đối số 'echo' là true, đầu ra menu nếu 'echo' là false.
 *                           False nếu không có mục nào hoặc không tìm thấy menu.
 */
function wp_nav_menu( $args = array() ) {
	static $menu_id_slugs = array();

	$defaults = array(
		'menu'                 => '',
		'container'            => 'div',
		'container_class'      => '',
		'container_id'         => '',
		'container_aria_label' => '',
		'menu_class'           => 'menu',
		'menu_id'              => '',
		'echo'                 => true,
		'fallback_cb'          => 'wp_page_menu',
		'before'               => '',
		'after'                => '',
		'link_before'          => '',
		'link_after'           => '',
		'items_wrap'           => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		'item_spacing'         => 'preserve',
		'depth'                => 0,
		'walker'               => '',
		'theme_location'       => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! in_array( $args['item_spacing'], array( 'preserve', 'discard' ), true ) ) {
		// Giá trị không hợp lệ, quay về mặc định.
		$args['item_spacing'] = $defaults['item_spacing'];
	}

	/**
	 * Lọc các đối số được sử dụng để hiển thị menu điều hướng.
	 *
	 * @since 3.0.0
	 *
	 * @see wp_nav_menu()
	 *
	 * @param array $args Mảng các đối số wp_nav_menu().
	 */
	$args = apply_filters( 'wp_nav_menu_args', $args );
	$args = (object) $args;

	/**
	 * Lọc xem có bỏ qua đầu ra wp_nav_menu() hay không.
	 *
	 * Trả về giá trị khác null từ bộ lọc sẽ bỏ qua wp_nav_menu(),
	 * echo giá trị đó nếu $args->echo là true, trả về giá trị đó trong trường hợp ngược lại.
	 *
	 * @since 3.9.0
	 *
	 * @see wp_nav_menu()
	 *
	 * @param string|null $output Đầu ra menu điều hướng để bỏ qua. Mặc định null.
	 * @param stdClass    $args   Đối tượng chứa các đối số wp_nav_menu().
	 */
	$nav_menu = apply_filters( 'pre_wp_nav_menu', null, $args );

	if ( null !== $nav_menu ) {
		if ( $args->echo ) {
			echo $nav_menu;
			return;
		}

		return $nav_menu;
	}

	// Lấy menu điều hướng dựa trên menu được yêu cầu.
	$menu = wp_get_nav_menu_object( $args->menu );

	// Lấy menu điều hướng dựa trên theme_location.
	$locations = get_nav_menu_locations();
	if ( ! $menu && $args->theme_location && $locations && isset( $locations[ $args->theme_location ] ) ) {
		$menu = wp_get_nav_menu_object( $locations[ $args->theme_location ] );
	}

	// Lấy menu đầu tiên có các mục nếu vẫn chưa tìm thấy menu.
	if ( ! $menu && ! $args->theme_location ) {
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu_maybe ) {
			$menu_items = wp_get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) );
			if ( $menu_items ) {
				$menu = $menu_maybe;
				break;
			}
		}
	}

	if ( empty( $args->menu ) ) {
		$args->menu = $menu;
	}

	// Nếu menu tồn tại, lấy các mục của nó.
	if ( $menu && ! is_wp_error( $menu ) && ! isset( $menu_items ) ) {
		$menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
	}

	/*
	 * Nếu không tìm thấy menu:
	 *  - Sử dụng dự phòng (nếu có chỉ định), hoặc thoát.
	 *
	 * Nếu không tìm thấy mục menu nào:
	 *  - Sử dụng dự phòng, nhưng chỉ khi không có vị trí theme nào được chỉ định.
	 *  - Nếu không, thoát.
	 */
	if ( ( ! $menu || is_wp_error( $menu ) || ( isset( $menu_items ) && empty( $menu_items ) && ! $args->theme_location ) )
		&& isset( $args->fallback_cb ) && $args->fallback_cb && is_callable( $args->fallback_cb ) ) {
			return call_user_func( $args->fallback_cb, (array) $args );
	}

	if ( ! $menu || is_wp_error( $menu ) ) {
		return false;
	}

	$nav_menu = '';
	$items    = '';

	$show_container = false;
	if ( $args->container ) {
		/**
		 * Lọc danh sách các thẻ HTML hợp lệ để sử dụng làm container menu.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $tags Các thẻ HTML được chấp nhận để sử dụng làm container menu.
		 *                       Mặc định là mảng chứa 'div' và 'nav'.
		 */
		$allowed_tags = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav' ) );

		if ( is_string( $args->container ) && in_array( $args->container, $allowed_tags, true ) ) {
			$show_container = true;
			$class          = $args->container_class ? ' class="' . esc_attr( $args->container_class ) . '"' : ' class="menu-' . $menu->slug . '-container"';
			$id             = $args->container_id ? ' id="' . esc_attr( $args->container_id ) . '"' : '';
			$aria_label     = ( 'nav' === $args->container && $args->container_aria_label ) ? ' aria-label="' . esc_attr( $args->container_aria_label ) . '"' : '';
			$nav_menu      .= '<' . $args->container . $id . $class . $aria_label . '>';
		}
	}

	// Thiết lập các biến $menu_item.
	_wp_menu_item_classes_by_context( $menu_items );

	$sorted_menu_items        = array();
	$menu_items_with_children = array();
	foreach ( (array) $menu_items as $menu_item ) {
		/*
		 * Sửa `menu_item_parent` không hợp lệ. Xem: https://core.trac.wordpress.org/ticket/56926.
		 * So sánh dưới dạng chuỗi. Plugin có thể thay đổi ID thành chuỗi.
		 */
		if ( (string) $menu_item->ID === (string) $menu_item->menu_item_parent ) {
			$menu_item->menu_item_parent = 0;
		}

		$sorted_menu_items[ $menu_item->menu_order ] = $menu_item;
		if ( $menu_item->menu_item_parent ) {
			$menu_items_with_children[ $menu_item->menu_item_parent ] = true;
		}
	}

	// Thêm lớp menu-item-has-children nơi phù hợp.
	if ( $menu_items_with_children ) {
		foreach ( $sorted_menu_items as &$menu_item ) {
			if ( isset( $menu_items_with_children[ $menu_item->ID ] ) ) {
				$menu_item->classes[] = 'menu-item-has-children';
			}
		}
	}

	unset( $menu_items, $menu_item );

	/**
	 * Lọc danh sách đã sắp xếp các đối tượng mục menu trước khi tạo HTML của menu.
	 *
	 * @since 3.1.0
	 *
	 * @param array    $sorted_menu_items Các mục menu, sắp xếp theo thứ tự menu của từng mục.
	 * @param stdClass $args              Đối tượng chứa các đối số wp_nav_menu().
	 */
	$sorted_menu_items = apply_filters( 'wp_nav_menu_objects', $sorted_menu_items, $args );

	$items .= walk_nav_menu_tree( $sorted_menu_items, $args->depth, $args );
	unset( $sorted_menu_items );

	// Thuộc tính.
	if ( ! empty( $args->menu_id ) ) {
		$wrap_id = $args->menu_id;
	} else {
		$wrap_id = 'menu-' . $menu->slug;

		while ( in_array( $wrap_id, $menu_id_slugs, true ) ) {
			if ( preg_match( '#-(\d+)$#', $wrap_id, $matches ) ) {
				$wrap_id = preg_replace( '#-(\d+)$#', '-' . ++$matches[1], $wrap_id );
			} else {
				$wrap_id = $wrap_id . '-1';
			}
		}
	}
	$menu_id_slugs[] = $wrap_id;

	$wrap_class = $args->menu_class ? $args->menu_class : '';

	/**
	 * Lọc nội dung danh sách HTML cho các mục menu điều hướng.
	 *
	 * @since 3.0.0
	 *
	 * @see wp_nav_menu()
	 *
	 * @param string   $items Nội dung danh sách HTML cho các mục menu.
	 * @param stdClass $args  Đối tượng chứa các đối số wp_nav_menu().
	 */
	$items = apply_filters( 'wp_nav_menu_items', $items, $args );
	/**
	 * Lọc nội dung danh sách HTML cho một menu điều hướng cụ thể.
	 *
	 * @since 3.0.0
	 *
	 * @see wp_nav_menu()
	 *
	 * @param string   $items Nội dung danh sách HTML cho các mục menu.
	 * @param stdClass $args  Đối tượng chứa các đối số wp_nav_menu().
	 */
	$items = apply_filters( "wp_nav_menu_{$menu->slug}_items", $items, $args );

	// Không in bất kỳ markup nào nếu không có mục nào tại thời điểm này.
	if ( empty( $items ) ) {
		return false;
	}

	$nav_menu .= sprintf( $args->items_wrap, esc_attr( $wrap_id ), esc_attr( $wrap_class ), $items );
	unset( $items );

	if ( $show_container ) {
		$nav_menu .= '</' . $args->container . '>';
	}

	/**
	 * Lọc nội dung HTML cho menu điều hướng.
	 *
	 * @since 3.0.0
	 *
	 * @see wp_nav_menu()
	 *
	 * @param string   $nav_menu Nội dung HTML cho menu điều hướng.
	 * @param stdClass $args     Đối tượng chứa các đối số wp_nav_menu().
	 */
	$nav_menu = apply_filters( 'wp_nav_menu', $nav_menu, $args );

	if ( $args->echo ) {
		echo $nav_menu;
	} else {
		return $nav_menu;
	}
}

/**
 * Thêm thuộc tính lớp cho ngữ cảnh hiện tại, nếu phù hợp.
 *
 * @access private
 * @since 3.0.0
 *
 * @global WP_Query   $wp_query   Đối tượng truy vấn WordPress.
 * @global WP_Rewrite $wp_rewrite Thành phần viết lại WordPress.
 *
 * @param array $menu_items Các đối tượng mục menu hiện tại cần thêm thông tin thuộc tính lớp.
 */
function _wp_menu_item_classes_by_context( &$menu_items ) {
	global $wp_query, $wp_rewrite;

	$queried_object    = $wp_query->get_queried_object();
	$queried_object_id = (int) $wp_query->queried_object_id;

	$active_object               = '';
	$active_ancestor_item_ids    = array();
	$active_parent_item_ids      = array();
	$active_parent_object_ids    = array();
	$possible_taxonomy_ancestors = array();
	$possible_object_parents     = array();
	$home_page_id                = (int) get_option( 'page_for_posts' );

	if ( $wp_query->is_singular && ! empty( $queried_object->post_type ) && ! is_post_type_hierarchical( $queried_object->post_type ) ) {
		foreach ( (array) get_object_taxonomies( $queried_object->post_type ) as $taxonomy ) {
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$term_hierarchy = _get_term_hierarchy( $taxonomy );
				$terms          = wp_get_object_terms( $queried_object_id, $taxonomy, array( 'fields' => 'ids' ) );
				if ( is_array( $terms ) ) {
					$possible_object_parents = array_merge( $possible_object_parents, $terms );
					$term_to_ancestor        = array();
					foreach ( (array) $term_hierarchy as $ancestor => $descendents ) {
						foreach ( (array) $descendents as $desc ) {
							$term_to_ancestor[ $desc ] = $ancestor;
						}
					}

					foreach ( $terms as $desc ) {
						do {
							$possible_taxonomy_ancestors[ $taxonomy ][] = $desc;
							if ( isset( $term_to_ancestor[ $desc ] ) ) {
								$_desc = $term_to_ancestor[ $desc ];
								unset( $term_to_ancestor[ $desc ] );
								$desc = $_desc;
							} else {
								$desc = 0;
							}
						} while ( ! empty( $desc ) );
					}
				}
			}
		}
	} elseif ( ! empty( $queried_object->taxonomy ) && is_taxonomy_hierarchical( $queried_object->taxonomy ) ) {
		$term_hierarchy   = _get_term_hierarchy( $queried_object->taxonomy );
		$term_to_ancestor = array();
		foreach ( (array) $term_hierarchy as $ancestor => $descendents ) {
			foreach ( (array) $descendents as $desc ) {
				$term_to_ancestor[ $desc ] = $ancestor;
			}
		}
		$desc = $queried_object->term_id;
		do {
			$possible_taxonomy_ancestors[ $queried_object->taxonomy ][] = $desc;
			if ( isset( $term_to_ancestor[ $desc ] ) ) {
				$_desc = $term_to_ancestor[ $desc ];
				unset( $term_to_ancestor[ $desc ] );
				$desc = $_desc;
			} else {
				$desc = 0;
			}
		} while ( ! empty( $desc ) );
	}

	$possible_object_parents = array_filter( $possible_object_parents );

	$front_page_url         = home_url();
	$front_page_id          = (int) get_option( 'page_on_front' );
	$privacy_policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

	foreach ( (array) $menu_items as $key => $menu_item ) {

		$menu_items[ $key ]->current = false;

		$classes   = (array) $menu_item->classes;
		$classes[] = 'menu-item';
		$classes[] = 'menu-item-type-' . $menu_item->type;
		$classes[] = 'menu-item-object-' . $menu_item->object;

		// Mục menu này được đặt làm 'Trang Đầu'.
		if ( 'post_type' === $menu_item->type && $front_page_id === (int) $menu_item->object_id ) {
			$classes[] = 'menu-item-home';
		}

		// Mục menu này được đặt làm 'Trang Chính sách Bảo mật'.
		if ( 'post_type' === $menu_item->type && $privacy_policy_page_id === (int) $menu_item->object_id ) {
			$classes[] = 'menu-item-privacy-policy';
		}

		// Nếu mục menu tương ứng với thuật ngữ taxonomy cho đối tượng bài viết không phân cấp đang được truy vấn.
		if ( $wp_query->is_singular && 'taxonomy' === $menu_item->type
			&& in_array( (int) $menu_item->object_id, $possible_object_parents, true )
		) {
			$active_parent_object_ids[] = (int) $menu_item->object_id;
			$active_parent_item_ids[]   = (int) $menu_item->db_id;
			$active_object              = $queried_object->post_type;

			// Nếu mục menu tương ứng với đối tượng bài viết hoặc taxonomy đang được truy vấn.
		} elseif (
			(int) $menu_item->object_id === $queried_object_id
			&& (
				( ! empty( $home_page_id ) && 'post_type' === $menu_item->type
					&& $wp_query->is_home && $home_page_id === (int) $menu_item->object_id )
				|| ( 'post_type' === $menu_item->type && $wp_query->is_singular )
				|| ( 'taxonomy' === $menu_item->type
					&& ( $wp_query->is_category || $wp_query->is_tag || $wp_query->is_tax )
					&& $queried_object->taxonomy === $menu_item->object )
			)
		) {
			$classes[]                   = 'current-menu-item';
			$menu_items[ $key ]->current = true;
			$ancestor_id                 = (int) $menu_item->db_id;

			while (
				( $ancestor_id = (int) get_post_meta( $ancestor_id, '_menu_item_menu_item_parent', true ) )
				&& ! in_array( $ancestor_id, $active_ancestor_item_ids, true )
			) {
				$active_ancestor_item_ids[] = $ancestor_id;
			}

			if ( 'post_type' === $menu_item->type && 'page' === $menu_item->object ) {
				// Các lớp tương thích ngược cho trang để phù hợp với wp_page_menu().
				$classes[] = 'page_item';
				$classes[] = 'page-item-' . $menu_item->object_id;
				$classes[] = 'current_page_item';
			}

			$active_parent_item_ids[]   = (int) $menu_item->menu_item_parent;
			$active_parent_object_ids[] = (int) $menu_item->post_parent;
			$active_object              = $menu_item->object;

			// Nếu mục menu tương ứng với trang lưu trữ loại bài viết đang được truy vấn.
		} elseif (
			'post_type_archive' === $menu_item->type
			&& is_post_type_archive( array( $menu_item->object ) )
		) {
			$classes[]                   = 'current-menu-item';
			$menu_items[ $key ]->current = true;
			$ancestor_id                 = (int) $menu_item->db_id;

			while (
				( $ancestor_id = (int) get_post_meta( $ancestor_id, '_menu_item_menu_item_parent', true ) )
				&& ! in_array( $ancestor_id, $active_ancestor_item_ids, true )
			) {
				$active_ancestor_item_ids[] = $ancestor_id;
			}

			$active_parent_item_ids[] = (int) $menu_item->menu_item_parent;

			// Nếu mục menu tương ứng với URL hiện tại đang được yêu cầu.
		} elseif ( 'custom' === $menu_item->object && isset( $_SERVER['HTTP_HOST'] ) ) {
			$_root_relative_current = untrailingslashit( $_SERVER['REQUEST_URI'] );

			// Nếu đây là trang tùy chỉnh thì nó sẽ loại bỏ biến truy vấn khỏi URL trước khi vào khối so sánh.
			if ( is_customize_preview() ) {
				$_root_relative_current = strtok( untrailingslashit( $_SERVER['REQUEST_URI'] ), '?' );
			}

			$current_url        = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_root_relative_current );
			$raw_item_url       = strpos( $menu_item->url, '#' ) ? substr( $menu_item->url, 0, strpos( $menu_item->url, '#' ) ) : $menu_item->url;
			$item_url           = set_url_scheme( untrailingslashit( $raw_item_url ) );
			$_indexless_current = untrailingslashit( preg_replace( '/' . preg_quote( $wp_rewrite->index, '/' ) . '$/', '', $current_url ) );

			$matches = array(
				$current_url,
				urldecode( $current_url ),
				$_indexless_current,
				urldecode( $_indexless_current ),
				$_root_relative_current,
				urldecode( $_root_relative_current ),
			);

			if ( $raw_item_url && in_array( $item_url, $matches, true ) ) {
				$classes[]                   = 'current-menu-item';
				$menu_items[ $key ]->current = true;
				$ancestor_id                 = (int) $menu_item->db_id;

				while (
					( $ancestor_id = (int) get_post_meta( $ancestor_id, '_menu_item_menu_item_parent', true ) )
					&& ! in_array( $ancestor_id, $active_ancestor_item_ids, true )
				) {
					$active_ancestor_item_ids[] = $ancestor_id;
				}

				if ( in_array( home_url(), array( untrailingslashit( $current_url ), untrailingslashit( $_indexless_current ) ), true ) ) {
					// Tương thích ngược cho liên kết trang chủ để phù hợp với wp_page_menu().
					$classes[] = 'current_page_item';
				}
				$active_parent_item_ids[]   = (int) $menu_item->menu_item_parent;
				$active_parent_object_ids[] = (int) $menu_item->post_parent;
				$active_object              = $menu_item->object;

				// Thêm lớp 'current-menu-item' cho mục trang đầu khi có các đối số truy vấn bổ sung.
			} elseif ( $item_url === $front_page_url && is_front_page() ) {
				$classes[] = 'current-menu-item';
			}

			if ( untrailingslashit( $item_url ) === home_url() ) {
				$classes[] = 'menu-item-home';
			}
		}

		// Tương thích ngược với wp_page_menu(): thêm "current_page_parent" vào liên kết trang chủ tĩnh cho mọi truy vấn không phải trang.
		if ( ! empty( $home_page_id ) && 'post_type' === $menu_item->type
			&& empty( $wp_query->is_page ) && $home_page_id === (int) $menu_item->object_id
		) {
			$classes[] = 'current_page_parent';
		}

		$menu_items[ $key ]->classes = array_unique( $classes );
	}
	$active_ancestor_item_ids = array_filter( array_unique( $active_ancestor_item_ids ) );
	$active_parent_item_ids   = array_filter( array_unique( $active_parent_item_ids ) );
	$active_parent_object_ids = array_filter( array_unique( $active_parent_object_ids ) );

	// Thiết lập lớp cho phần tử cha.
	foreach ( (array) $menu_items as $key => $parent_item ) {
		$classes                                   = (array) $parent_item->classes;
		$menu_items[ $key ]->current_item_ancestor = false;
		$menu_items[ $key ]->current_item_parent   = false;

		if (
			isset( $parent_item->type )
			&& (
				// Đối tượng bài viết tổ tiên.
				(
					'post_type' === $parent_item->type
					&& ! empty( $queried_object->post_type )
					&& is_post_type_hierarchical( $queried_object->post_type )
					&& in_array( (int) $parent_item->object_id, $queried_object->ancestors, true )
					&& (int) $parent_item->object_id !== $queried_object->ID
				) ||

				// Thuật ngữ tổ tiên.
				(
					'taxonomy' === $parent_item->type
					&& isset( $possible_taxonomy_ancestors[ $parent_item->object ] )
					&& in_array( (int) $parent_item->object_id, $possible_taxonomy_ancestors[ $parent_item->object ], true )
					&& (
						! isset( $queried_object->term_id ) ||
						(int) $parent_item->object_id !== $queried_object->term_id
					)
				)
			)
		) {
			if ( ! empty( $queried_object->taxonomy ) ) {
				$classes[] = 'current-' . $queried_object->taxonomy . '-ancestor';
			} else {
				$classes[] = 'current-' . $queried_object->post_type . '-ancestor';
			}
		}

		if ( in_array( (int) $parent_item->db_id, $active_ancestor_item_ids, true ) ) {
			$classes[] = 'current-menu-ancestor';

			$menu_items[ $key ]->current_item_ancestor = true;
		}
		if ( in_array( (int) $parent_item->db_id, $active_parent_item_ids, true ) ) {
			$classes[] = 'current-menu-parent';

			$menu_items[ $key ]->current_item_parent = true;
		}
		if ( in_array( (int) $parent_item->object_id, $active_parent_object_ids, true ) ) {
			$classes[] = 'current-' . $active_object . '-parent';
		}

		if ( 'post_type' === $parent_item->type && 'page' === $parent_item->object ) {
			// Các lớp tương thích ngược cho trang để phù hợp với wp_page_menu().
			if ( in_array( 'current-menu-parent', $classes, true ) ) {
				$classes[] = 'current_page_parent';
			}
			if ( in_array( 'current-menu-ancestor', $classes, true ) ) {
				$classes[] = 'current_page_ancestor';
			}
		}

		$menu_items[ $key ]->classes = array_unique( $classes );
	}
}

/**
 * Lấy nội dung danh sách HTML cho các mục menu điều hướng.
 *
 * @uses Walker_Nav_Menu để tạo nội dung danh sách HTML.
 * @since 3.0.0
 *
 * @param array    $items Các mục menu, sắp xếp theo thứ tự menu của từng mục.
 * @param int      $depth Độ sâu của mục tham chiếu đến các phần tử cha.
 * @param stdClass $args  Đối tượng chứa các đối số wp_nav_menu().
 * @return string Nội dung danh sách HTML cho các mục menu.
 */
function walk_nav_menu_tree( $items, $depth, $args ) {
	$walker = ( empty( $args->walker ) ) ? new Walker_Nav_Menu() : $args->walker;

	return $walker->walk( $items, $depth, $args );
}

/**
 * Ngăn ID mục menu bị sử dụng nhiều hơn một lần.
 *
 * @since 3.0.1
 * @access private
 *
 * @param string $id
 * @param object $item
 * @return string
 */
function _nav_menu_item_id_use_once( $id, $item ) {
	static $_used_ids = array();

	if ( in_array( $item->ID, $_used_ids, true ) ) {
		return '';
	}

	$_used_ids[] = $item->ID;

	return $id;
}

/**
 * Xóa lớp `menu-item-has-children` khỏi các mục menu cấp thấp nhất.
 *
 * Hàm này chạy trên bộ lọc {@see 'nav_menu_css_class'}. Các tham số $args và $depth
 * được thêm vào sau khi bộ lọc ban đầu được giới thiệu trong
 * WordPress 3.0.0 nên cần cho phép các trường hợp bộ lọc
 * được gọi mà không có chúng.
 *
 * @see https://core.trac.wordpress.org/ticket/56926
 *
 * @since 6.2.0
 *
 * @param string[]       $classes   Mảng các lớp CSS được áp dụng cho phần tử `<li>` của mục menu.
 * @param WP_Post        $menu_item Đối tượng mục menu hiện tại.
 * @param stdClass|false $args      Đối tượng các đối số wp_nav_menu(). Mặc định false ($args chưa được chỉ định khi bộ lọc được gọi).
 * @param int|false      $depth     Độ sâu của mục menu. Mặc định false ($depth chưa được chỉ định khi bộ lọc được gọi).
 * @return string[] Các lớp menu điều hướng đã được sửa đổi.
 */
function wp_nav_menu_remove_menu_item_has_children_class( $classes, $menu_item, $args = false, $depth = false ) {
	/*
	 * Xử lý trường hợp bộ lọc được gọi mà không có tham số $args hoặc $depth.
	 *
	 * Điều này xảy ra khi theme sử dụng walker tùy chỉnh gọi bộ lọc `nav_menu_css_class`
	 * sử dụng các định dạng cũ trước khi tham số $args và
	 * $depth được giới thiệu.
	 *
	 * Vì cả hai tham số này đều cần thiết để hàm này xác định
	 * cả độ sâu hiện tại và độ sâu tối đa của cây menu, hàm không
	 * cố gắng xóa lớp `menu-item-has-children` nếu các tham số này
	 * chưa được thiết lập.
	 */
	if ( false === $depth || false === $args ) {
		return $classes;
	}

	// Độ sâu tối đa tính từ 1.
	$max_depth = isset( $args->depth ) ? (int) $args->depth : 0;
	// Độ sâu tính từ 0 nên cần tăng thêm một.
	$depth = $depth + 1;

	// Toàn bộ cây menu được hiển thị.
	if ( 0 === $max_depth ) {
		return $classes;
	}

	/*
	 * Xóa lớp `menu-item-has-children` khỏi các mục menu cấp thấp nhất.
	 * -1 được sử dụng để hiển thị tất cả mục menu ở một cấp nên lớp này
	 * nên được xóa khỏi tất cả mục menu.
	 */
	if ( -1 === $max_depth || $depth >= $max_depth ) {
		$classes = array_diff( $classes, array( 'menu-item-has-children' ) );
	}

	return $classes;
}
