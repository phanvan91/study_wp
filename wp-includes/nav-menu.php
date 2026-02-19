<?php
/**
 * Các hàm Menu Điều hướng
 *
 * @package WordPress
 * @subpackage Nav_Menus
 * @since 3.0.0
 */

/**
 * Trả về đối tượng menu điều hướng.
 *
 * @since 3.0.0
 *
 * @param int|string|WP_Term $menu ID menu, slug, tên, hoặc đối tượng.
 * @return WP_Term|false Đối tượng menu khi thành công, false nếu tham số $menu không được cung cấp hoặc term không tồn tại.
 */
function wp_get_nav_menu_object( $menu ) {
	$menu_obj = false;

	if ( is_object( $menu ) ) {
		$menu_obj = $menu;
	}

	if ( $menu && ! $menu_obj ) {
		$menu_obj = get_term( $menu, 'nav_menu' );

		if ( ! $menu_obj ) {
			$menu_obj = get_term_by( 'slug', $menu, 'nav_menu' );
		}

		if ( ! $menu_obj ) {
			$menu_obj = get_term_by( 'name', $menu, 'nav_menu' );
		}
	}

	if ( ! $menu_obj || is_wp_error( $menu_obj ) ) {
		$menu_obj = false;
	}

	/**
	 * Lọc term nav_menu được truy xuất cho wp_get_nav_menu_object().
	 *
	 * @since 4.3.0
	 *
	 * @param WP_Term|false      $menu_obj Term từ taxonomy nav_menu, hoặc false nếu không tìm thấy gì.
	 * @param int|string|WP_Term $menu     ID menu, slug, tên, hoặc đối tượng được truyền vào wp_get_nav_menu_object().
	 */
	return apply_filters( 'wp_get_nav_menu_object', $menu_obj, $menu );
}

/**
 * Xác định xem ID đã cho có phải là menu điều hướng hay không.
 *
 * Trả về true nếu đúng; false nếu không.
 *
 * @since 3.0.0
 *
 * @param int|string|WP_Term $menu ID menu, slug, tên, hoặc đối tượng menu cần kiểm tra.
 * @return bool Menu có tồn tại hay không.
 */
function is_nav_menu( $menu ) {
	if ( ! $menu ) {
		return false;
	}

	$menu_obj = wp_get_nav_menu_object( $menu );

	if (
		$menu_obj &&
		! is_wp_error( $menu_obj ) &&
		! empty( $menu_obj->taxonomy ) &&
		'nav_menu' === $menu_obj->taxonomy
	) {
		return true;
	}

	return false;
}

/**
 * Đăng ký các vị trí menu điều hướng cho theme.
 *
 * @since 3.0.0
 *
 * @global array $_wp_registered_nav_menus
 *
 * @param string[] $locations Mảng liên kết các định danh vị trí menu (giống slug) và văn bản mô tả.
 */
function register_nav_menus( $locations = array() ) {
	global $_wp_registered_nav_menus;

	add_theme_support( 'menus' );

	foreach ( $locations as $key => $value ) {
		if ( is_int( $key ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Nav menu locations must be strings.' ), '5.3.0' );
			break;
		}
	}

	$_wp_registered_nav_menus = array_merge( (array) $_wp_registered_nav_menus, $locations );
}

/**
 * Hủy đăng ký vị trí menu điều hướng cho theme.
 *
 * @since 3.1.0
 *
 * @global array $_wp_registered_nav_menus
 *
 * @param string $location Định danh vị trí menu.
 * @return bool True khi thành công, false khi thất bại.
 */
function unregister_nav_menu( $location ) {
	global $_wp_registered_nav_menus;

	if ( is_array( $_wp_registered_nav_menus ) && isset( $_wp_registered_nav_menus[ $location ] ) ) {
		unset( $_wp_registered_nav_menus[ $location ] );
		if ( empty( $_wp_registered_nav_menus ) ) {
			_remove_theme_support( 'menus' );
		}
		return true;
	}
	return false;
}

/**
 * Đăng ký một vị trí menu điều hướng cho theme.
 *
 * @since 3.0.0
 *
 * @param string $location    Định danh vị trí menu, giống slug.
 * @param string $description Văn bản mô tả vị trí menu.
 */
function register_nav_menu( $location, $description ) {
	register_nav_menus( array( $location => $description ) );
}
/**
 * Truy xuất tất cả vị trí menu điều hướng đã đăng ký trong theme.
 *
 * @since 3.0.0
 *
 * @global array $_wp_registered_nav_menus
 *
 * @return string[] Mảng liên kết các mô tả menu điều hướng đã đăng ký theo khóa
 *                  là vị trí của chúng. Nếu không có đăng ký nào, trả về mảng rỗng.
 */
function get_registered_nav_menus() {
	global $_wp_registered_nav_menus;
	if ( isset( $_wp_registered_nav_menus ) ) {
		return $_wp_registered_nav_menus;
	}
	return array();
}

/**
 * Truy xuất tất cả vị trí menu điều hướng đã đăng ký và các menu được gán cho chúng.
 *
 * @since 3.0.0
 *
 * @return int[] Mảng liên kết các ID menu điều hướng đã đăng ký theo khóa
 *               là tên vị trí. Nếu không có đăng ký nào, trả về mảng rỗng.
 */
function get_nav_menu_locations() {
	$locations = get_theme_mod( 'nav_menu_locations' );
	return ( is_array( $locations ) ) ? $locations : array();
}

/**
 * Xác định xem vị trí menu điều hướng đã đăng ký có menu được gán hay không.
 *
 * @since 3.0.0
 *
 * @param string $location Định danh vị trí menu.
 * @return bool Vị trí có menu hay không.
 */
function has_nav_menu( $location ) {
	$has_nav_menu = false;

	$registered_nav_menus = get_registered_nav_menus();
	if ( isset( $registered_nav_menus[ $location ] ) ) {
		$locations    = get_nav_menu_locations();
		$has_nav_menu = ! empty( $locations[ $location ] );
	}

	/**
	 * Lọc xem menu điều hướng có được gán cho vị trí chỉ định hay không.
	 *
	 * @since 4.3.0
	 *
	 * @param bool   $has_nav_menu Có menu được gán cho vị trí hay không.
	 * @param string $location     Vị trí menu.
	 */
	return apply_filters( 'has_nav_menu', $has_nav_menu, $location );
}

/**
 * Trả về tên của menu điều hướng.
 *
 * @since 4.9.0
 *
 * @param string $location Định danh vị trí menu.
 * @return string Tên menu.
 */
function wp_get_nav_menu_name( $location ) {
	$menu_name = '';

	$locations = get_nav_menu_locations();

	if ( isset( $locations[ $location ] ) ) {
		$menu = wp_get_nav_menu_object( $locations[ $location ] );

		if ( $menu && $menu->name ) {
			$menu_name = $menu->name;
		}
	}

	/**
	 * Lọc tên menu điều hướng đang được trả về.
	 *
	 * @since 4.9.0
	 *
	 * @param string $menu_name Tên menu.
	 * @param string $location  Định danh vị trí menu.
	 */
	return apply_filters( 'wp_get_nav_menu_name', $menu_name, $location );
}

/**
 * Xác định xem ID đã cho có phải là mục menu điều hướng hay không.
 *
 * @since 3.0.0
 *
 * @param int $menu_item_id ID của mục menu điều hướng tiềm năng.
 * @return bool ID đã cho có phải là mục menu điều hướng hay không.
 */
function is_nav_menu_item( $menu_item_id = 0 ) {
	return ( ! is_wp_error( $menu_item_id ) && ( 'nav_menu_item' === get_post_type( $menu_item_id ) ) );
}

/**
 * Tạo menu điều hướng.
 *
 * Lưu ý rằng `$menu_name` được kỳ vọng đã được thêm dấu gạch chéo trước.
 *
 * @since 3.0.0
 *
 * @param string $menu_name Tên menu.
 * @return int|WP_Error ID menu khi thành công, đối tượng WP_Error khi thất bại.
 */
function wp_create_nav_menu( $menu_name ) {
	// đã_thêm_gạch_chéo ($menu_name)
	return wp_update_nav_menu_object( 0, array( 'menu-name' => $menu_name ) );
}

/**
 * Xóa menu điều hướng.
 *
 * @since 3.0.0
 *
 * @param int|string|WP_Term $menu ID menu, slug, tên, hoặc đối tượng.
 * @return bool|WP_Error True khi thành công, false hoặc đối tượng WP_Error khi thất bại.
 */
function wp_delete_nav_menu( $menu ) {
	$menu = wp_get_nav_menu_object( $menu );
	if ( ! $menu ) {
		return false;
	}

	$menu_objects = get_objects_in_term( $menu->term_id, 'nav_menu' );
	if ( ! empty( $menu_objects ) ) {
		foreach ( $menu_objects as $item ) {
			wp_delete_post( $item );
		}
	}

	$result = wp_delete_term( $menu->term_id, 'nav_menu' );

	// Xóa menu này khỏi bất kỳ vị trí nào.
	$locations = get_nav_menu_locations();
	foreach ( $locations as $location => $menu_id ) {
		if ( $menu_id === $menu->term_id ) {
			$locations[ $location ] = 0;
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );

	if ( $result && ! is_wp_error( $result ) ) {

		/**
		 * Kích hoạt sau khi menu điều hướng đã được xóa thành công.
		 *
		 * @since 3.0.0
		 *
		 * @param int $term_id ID của menu đã xóa.
		 */
		do_action( 'wp_delete_nav_menu', $menu->term_id );
	}

	return $result;
}

/**
 * Lưu thuộc tính của menu hoặc tạo menu mới với các thuộc tính đó.
 *
 * Lưu ý rằng `$menu_data` được kỳ vọng đã được thêm dấu gạch chéo trước.
 *
 * @since 3.0.0
 *
 * @param int   $menu_id   ID của menu hoặc "0" để tạo menu mới.
 * @param array $menu_data Mảng dữ liệu menu.
 * @return int|WP_Error ID menu khi thành công, đối tượng WP_Error khi thất bại.
 */
function wp_update_nav_menu_object( $menu_id = 0, $menu_data = array() ) {
	// đã_thêm_gạch_chéo ($menu_data)
	$menu_id = (int) $menu_id;

	$_menu = wp_get_nav_menu_object( $menu_id );

	$args = array(
		'description' => ( isset( $menu_data['description'] ) ? $menu_data['description'] : '' ),
		'name'        => ( isset( $menu_data['menu-name'] ) ? $menu_data['menu-name'] : '' ),
		'parent'      => ( isset( $menu_data['parent'] ) ? (int) $menu_data['parent'] : 0 ),
		'slug'        => null,
	);

	// Kiểm tra kỹ rằng chúng ta không để một menu lấy tên của menu khác.
	$_possible_existing = get_term_by( 'name', $menu_data['menu-name'], 'nav_menu' );
	if (
		$_possible_existing &&
		! is_wp_error( $_possible_existing ) &&
		isset( $_possible_existing->term_id ) &&
		$_possible_existing->term_id !== $menu_id
	) {
		return new WP_Error(
			'menu_exists',
			sprintf(
				/* translators: %s: Menu name. */
				__( 'The menu name %s conflicts with another menu name. Please try another.' ),
				'<strong>' . esc_html( $menu_data['menu-name'] ) . '</strong>'
			)
		);
	}

	// Menu chưa tồn tại, nên tạo menu mới.
	if ( ! $_menu || is_wp_error( $_menu ) ) {
		$menu_exists = get_term_by( 'name', $menu_data['menu-name'], 'nav_menu' );

		if ( $menu_exists ) {
			return new WP_Error(
				'menu_exists',
				sprintf(
					/* translators: %s: Menu name. */
					__( 'The menu name %s conflicts with another menu name. Please try another.' ),
					'<strong>' . esc_html( $menu_data['menu-name'] ) . '</strong>'
				)
			);
		}

		$_menu = wp_insert_term( $menu_data['menu-name'], 'nav_menu', $args );

		if ( is_wp_error( $_menu ) ) {
			return $_menu;
		}

		/**
		 * Kích hoạt sau khi menu điều hướng được tạo thành công.
		 *
		 * @since 3.0.0
		 *
		 * @param int   $term_id   ID của menu mới.
		 * @param array $menu_data Mảng dữ liệu menu.
		 */
		do_action( 'wp_create_nav_menu', $_menu['term_id'], $menu_data );

		return (int) $_menu['term_id'];
	}

	if ( ! $_menu || ! isset( $_menu->term_id ) ) {
		return 0;
	}

	$menu_id = (int) $_menu->term_id;

	$update_response = wp_update_term( $menu_id, 'nav_menu', $args );

	if ( is_wp_error( $update_response ) ) {
		return $update_response;
	}

	$menu_id = (int) $update_response['term_id'];

	/**
	 * Kích hoạt sau khi menu điều hướng đã được cập nhật thành công.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $menu_id   ID của menu đã cập nhật.
	 * @param array $menu_data Mảng dữ liệu menu.
	 */
	do_action( 'wp_update_nav_menu', $menu_id, $menu_data );
	return $menu_id;
}

/**
 * Lưu thuộc tính của mục menu hoặc tạo mục mới.
 *
 * Các trường menu-item-title, menu-item-description và menu-item-attr-title được kỳ vọng
 * đã thêm dấu gạch chéo vì chúng được truyền trực tiếp đến các API yêu cầu dữ liệu đã thêm gạch chéo.
 *
 * @since 3.0.0
 * @since 5.9.0 Thêm tham số `$fire_after_hooks`.
 *
 * @param int   $menu_id          ID của menu. Nếu là 0, biến mục menu thành bản nháp mồ côi.
 * @param int   $menu_item_db_id  ID của mục menu. Nếu là 0, tạo mục menu mới.
 * @param array $menu_item_data   Dữ liệu của mục menu.
 * @param bool  $fire_after_hooks Có kích hoạt các hook sau khi chèn hay không. Mặc định true.
 * @return int|WP_Error ID cơ sở dữ liệu của mục menu hoặc đối tượng WP_Error khi thất bại.
 */
function wp_update_nav_menu_item( $menu_id = 0, $menu_item_db_id = 0, $menu_item_data = array(), $fire_after_hooks = true ) {
	$menu_id         = (int) $menu_id;
	$menu_item_db_id = (int) $menu_item_db_id;

	// Đảm bảo rằng chúng ta không chuyển đổi các đối tượng không phải nav_menu_item thành đối tượng nav_menu_item.
	if ( ! empty( $menu_item_db_id ) && ! is_nav_menu_item( $menu_item_db_id ) ) {
		return new WP_Error( 'update_nav_menu_item_failed', __( 'The given object ID is not that of a menu item.' ) );
	}

	$menu = wp_get_nav_menu_object( $menu_id );

	if ( ! $menu && 0 !== $menu_id ) {
		return new WP_Error( 'invalid_menu_id', __( 'Invalid menu ID.' ) );
	}

	if ( is_wp_error( $menu ) ) {
		return $menu;
	}

	$defaults = array(
		'menu-item-db-id'         => $menu_item_db_id,
		'menu-item-object-id'     => 0,
		'menu-item-object'        => '',
		'menu-item-parent-id'     => 0,
		'menu-item-position'      => 0,
		'menu-item-type'          => 'custom',
		'menu-item-title'         => '',
		'menu-item-url'           => '',
		'menu-item-description'   => '',
		'menu-item-attr-title'    => '',
		'menu-item-target'        => '',
		'menu-item-classes'       => '',
		'menu-item-xfn'           => '',
		'menu-item-status'        => '',
		'menu-item-post-date'     => '',
		'menu-item-post-date-gmt' => '',
	);

	$args = wp_parse_args( $menu_item_data, $defaults );

	if ( 0 === $menu_id ) {
		$args['menu-item-position'] = 1;
	} elseif ( 0 === (int) $args['menu-item-position'] ) {
		$menu_items = array();

		if ( 0 !== $menu_id ) {
			$menu_items = (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish,draft' ) );
		}

		$last_item = array_pop( $menu_items );

		if ( $last_item && isset( $last_item->menu_order ) ) {
			$args['menu-item-position'] = 1 + $last_item->menu_order;
		} else {
			$args['menu-item-position'] = count( $menu_items );
		}
	}

	$original_parent = 0 < $menu_item_db_id ? get_post_field( 'post_parent', $menu_item_db_id ) : 0;

	if ( 'custom' === $args['menu-item-type'] ) {
		// If custom menu item, trim the URL.
		$args['menu-item-url'] = trim( $args['menu-item-url'] );
	} else {
		/*
		 * If non-custom menu item, then:
		 * - use the original object's URL.
		 * - blank default title to sync with the original object's title.
		 */

		$args['menu-item-url'] = '';

		$original_title = '';

		if ( 'taxonomy' === $args['menu-item-type'] ) {
			$original_object = get_term( $args['menu-item-object-id'], $args['menu-item-object'] );

			if ( $original_object instanceof WP_Term ) {
				$original_parent = get_term_field( 'parent', $args['menu-item-object-id'], $args['menu-item-object'], 'raw' );
				$original_title  = get_term_field( 'name', $args['menu-item-object-id'], $args['menu-item-object'], 'raw' );
			}
		} elseif ( 'post_type' === $args['menu-item-type'] ) {
			$original_object = get_post( $args['menu-item-object-id'] );

			if ( $original_object instanceof WP_Post ) {
				$original_parent = (int) $original_object->post_parent;
				$original_title  = $original_object->post_title;
			}
		} elseif ( 'post_type_archive' === $args['menu-item-type'] ) {
			$original_object = get_post_type_object( $args['menu-item-object'] );

			if ( $original_object instanceof WP_Post_Type ) {
				$original_title = $original_object->labels->archives;
			}
		}

		if ( wp_unslash( $args['menu-item-title'] ) === wp_specialchars_decode( $original_title ) ) {
			$args['menu-item-title'] = '';
		}

		// Hack to get wp to create a post object when too many properties are empty.
		if ( '' === $args['menu-item-title'] && '' === $args['menu-item-description'] ) {
			$args['menu-item-description'] = ' ';
		}
	}

	// Populate the menu item object.
	$post = array(
		'menu_order'   => $args['menu-item-position'],
		'ping_status'  => 0,
		'post_content' => $args['menu-item-description'],
		'post_excerpt' => $args['menu-item-attr-title'],
		'post_parent'  => $original_parent,
		'post_title'   => $args['menu-item-title'],
		'post_type'    => 'nav_menu_item',
	);

	$post_date = wp_resolve_post_date( $args['menu-item-post-date'], $args['menu-item-post-date-gmt'] );
	if ( $post_date ) {
		$post['post_date'] = $post_date;
	}

	$update = 0 !== $menu_item_db_id;

	// New menu item. Default is draft status.
	if ( ! $update ) {
		$post['ID']          = 0;
		$post['post_status'] = 'publish' === $args['menu-item-status'] ? 'publish' : 'draft';
		$menu_item_db_id     = wp_insert_post( $post, true, $fire_after_hooks );
		if ( ! $menu_item_db_id || is_wp_error( $menu_item_db_id ) ) {
			return $menu_item_db_id;
		}

		/**
		 * Fires immediately after a new navigation menu item has been added.
		 *
		 * @since 4.4.0
		 *
		 * @see wp_update_nav_menu_item()
		 *
		 * @param int   $menu_id         ID of the updated menu.
		 * @param int   $menu_item_db_id ID of the new menu item.
		 * @param array $args            An array of arguments used to update/add the menu item.
		 */
		do_action( 'wp_add_nav_menu_item', $menu_id, $menu_item_db_id, $args );
	}

	/*
	 * Associate the menu item with the menu term.
	 * Only set the menu term if it isn't set to avoid unnecessary wp_get_object_terms().
	 */
	if ( $menu_id && ( ! $update || ! is_object_in_term( $menu_item_db_id, 'nav_menu', (int) $menu->term_id ) ) ) {
		$update_terms = wp_set_object_terms( $menu_item_db_id, array( $menu->term_id ), 'nav_menu' );
		if ( is_wp_error( $update_terms ) ) {
			return $update_terms;
		}
	}

	if ( 'custom' === $args['menu-item-type'] ) {
		$args['menu-item-object-id'] = $menu_item_db_id;
		$args['menu-item-object']    = 'custom';
	}

	$menu_item_db_id = (int) $menu_item_db_id;

	// Reset invalid `menu_item_parent`.
	if ( (int) $args['menu-item-parent-id'] === $menu_item_db_id ) {
		$args['menu-item-parent-id'] = 0;
	}

	update_post_meta( $menu_item_db_id, '_menu_item_type', sanitize_key( $args['menu-item-type'] ) );
	update_post_meta( $menu_item_db_id, '_menu_item_menu_item_parent', (string) ( (int) $args['menu-item-parent-id'] ) );
	update_post_meta( $menu_item_db_id, '_menu_item_object_id', (string) ( (int) $args['menu-item-object-id'] ) );
	update_post_meta( $menu_item_db_id, '_menu_item_object', sanitize_key( $args['menu-item-object'] ) );
	update_post_meta( $menu_item_db_id, '_menu_item_target', sanitize_key( $args['menu-item-target'] ) );

	$args['menu-item-classes'] = array_map( 'sanitize_html_class', explode( ' ', $args['menu-item-classes'] ) );
	$args['menu-item-xfn']     = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['menu-item-xfn'] ) ) );
	update_post_meta( $menu_item_db_id, '_menu_item_classes', $args['menu-item-classes'] );
	update_post_meta( $menu_item_db_id, '_menu_item_xfn', $args['menu-item-xfn'] );
	update_post_meta( $menu_item_db_id, '_menu_item_url', sanitize_url( $args['menu-item-url'] ) );

	if ( 0 === $menu_id ) {
		update_post_meta( $menu_item_db_id, '_menu_item_orphaned', (string) time() );
	} elseif ( get_post_meta( $menu_item_db_id, '_menu_item_orphaned' ) ) {
		delete_post_meta( $menu_item_db_id, '_menu_item_orphaned' );
	}

	// Update existing menu item. Default is publish status.
	if ( $update ) {
		$post['ID']          = $menu_item_db_id;
		$post['post_status'] = ( 'draft' === $args['menu-item-status'] ) ? 'draft' : 'publish';

		$update_post = wp_update_post( $post, true );
		if ( is_wp_error( $update_post ) ) {
			return $update_post;
		}
	}

	/**
	 * Fires after a navigation menu item has been updated.
	 *
	 * @since 3.0.0
	 *
	 * @see wp_update_nav_menu_item()
	 *
	 * @param int   $menu_id         ID of the updated menu.
	 * @param int   $menu_item_db_id ID of the updated menu item.
	 * @param array $args            An array of arguments used to update a menu item.
	 */
	do_action( 'wp_update_nav_menu_item', $menu_id, $menu_item_db_id, $args );

	return $menu_item_db_id;
}

/**
 * Returns all navigation menu objects.
 *
 * @since 3.0.0
 * @since 4.1.0 Default value of the 'orderby' argument was changed from 'none'
 *              to 'name'.
 *
 * @param array $args Optional. Array of arguments passed on to get_terms().
 *                    Default empty array.
 * @return WP_Term[] An array of menu objects.
 */
function wp_get_nav_menus( $args = array() ) {
	$defaults = array(
		'taxonomy'   => 'nav_menu',
		'hide_empty' => false,
		'orderby'    => 'name',
	);
	$args     = wp_parse_args( $args, $defaults );

	/**
	 * Filters the navigation menu objects being returned.
	 *
	 * @since 3.0.0
	 *
	 * @see get_terms()
	 *
	 * @param WP_Term[] $menus An array of menu objects.
	 * @param array     $args  An array of arguments used to retrieve menu objects.
	 */
	return apply_filters( 'wp_get_nav_menus', get_terms( $args ), $args );
}

/**
 * Determines whether a menu item is valid.
 *
 * @link https://core.trac.wordpress.org/ticket/13958
 *
 * @since 3.2.0
 * @access private
 *
 * @param object $item The menu item to check.
 * @return bool False if invalid, otherwise true.
 */
function _is_valid_nav_menu_item( $item ) {
	return empty( $item->_invalid );
}

/**
 * Retrieves all menu items of a navigation menu.
 *
 * Note: Most arguments passed to the `$args` parameter – save for 'output_key' – are
 * specifically for retrieving nav_menu_item posts from get_posts() and may only
 * indirectly affect the ultimate ordering and content of the resulting nav menu
 * items that get returned from this function.
 *
 * @since 3.0.0
 *
 * @param int|string|WP_Term $menu Menu ID, slug, name, or object.
 * @param array              $args {
 *     Optional. Arguments to pass to get_posts().
 *
 *     @type string $order                  How to order nav menu items as queried with get_posts().
 *                                          Will be ignored if 'output' is ARRAY_A. Default 'ASC'.
 *     @type string $orderby                Field to order menu items by as retrieved from get_posts().
 *                                          Supply an orderby field via 'output_key' to affect the
 *                                          output order of nav menu items. Default 'menu_order'.
 *     @type string $post_type              Menu items post type. Default 'nav_menu_item'.
 *     @type string $post_status            Menu items post status. Default 'publish'.
 *     @type string $output                 How to order outputted menu items. Default ARRAY_A.
 *     @type string $output_key             Key to use for ordering the actual menu items that get
 *                                          returned. Note that that is not a get_posts() argument
 *                                          and will only affect output of menu items processed in
 *                                          this function. Default 'menu_order'.
 *     @type bool   $nopaging               Whether to retrieve all menu items (true) or paginate
 *                                          (false). Default true.
 *     @type bool   $update_menu_item_cache Whether to update the menu item cache. Default true.
 * }
 * @return array|false Array of menu items, otherwise false.
 */
function wp_get_nav_menu_items( $menu, $args = array() ) {
	$menu = wp_get_nav_menu_object( $menu );

	if ( ! $menu ) {
		return false;
	}

	if ( ! taxonomy_exists( 'nav_menu' ) ) {
		return false;
	}

	$defaults = array(
		'order'                  => 'ASC',
		'orderby'                => 'menu_order',
		'post_type'              => 'nav_menu_item',
		'post_status'            => 'publish',
		'output'                 => ARRAY_A,
		'output_key'             => 'menu_order',
		'nopaging'               => true,
		'update_menu_item_cache' => true,
		'tax_query'              => array(
			array(
				'taxonomy' => 'nav_menu',
				'field'    => 'term_taxonomy_id',
				'terms'    => $menu->term_taxonomy_id,
			),
		),
	);
	$args     = wp_parse_args( $args, $defaults );
	if ( $menu->count > 0 ) {
		$items = get_posts( $args );
	} else {
		$items = array();
	}

	$items = array_map( 'wp_setup_nav_menu_item', $items );

	if ( ! is_admin() ) { // Remove invalid items only on front end.
		$items = array_filter( $items, '_is_valid_nav_menu_item' );
	}

	if ( ARRAY_A === $args['output'] ) {
		$items = wp_list_sort(
			$items,
			array(
				$args['output_key'] => 'ASC',
			)
		);

		$i = 1;

		foreach ( $items as $k => $item ) {
			$items[ $k ]->{$args['output_key']} = $i++;
		}
	}

	/**
	 * Filters the navigation menu items being returned.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $items An array of menu item post objects.
	 * @param object $menu  The menu object.
	 * @param array  $args  An array of arguments used to retrieve menu item objects.
	 */
	return apply_filters( 'wp_get_nav_menu_items', $items, $menu, $args );
}

/**
 * Updates post and term caches for all linked objects for a list of menu items.
 *
 * @since 6.1.0
 *
 * @param WP_Post[] $menu_items Array of menu item post objects.
 */
function update_menu_item_cache( $menu_items ) {
	$post_ids = array();
	$term_ids = array();

	foreach ( $menu_items as $menu_item ) {
		if ( 'nav_menu_item' !== $menu_item->post_type ) {
			continue;
		}

		$object_id = get_post_meta( $menu_item->ID, '_menu_item_object_id', true );
		$type      = get_post_meta( $menu_item->ID, '_menu_item_type', true );

		if ( 'post_type' === $type ) {
			$post_ids[] = (int) $object_id;
		} elseif ( 'taxonomy' === $type ) {
			$term_ids[] = (int) $object_id;
		}
	}

	if ( ! empty( $post_ids ) ) {
		_prime_post_caches( $post_ids, false );
	}

	if ( ! empty( $term_ids ) ) {
		_prime_term_caches( $term_ids );
	}
}

/**
 * Decorates a menu item object with the shared navigation menu item properties.
 *
 * Properties:
 * - ID:               The term_id if the menu item represents a taxonomy term.
 * - attr_title:       The title attribute of the link element for this menu item.
 * - classes:          The array of class attribute values for the link element of this menu item.
 * - db_id:            The DB ID of this item as a nav_menu_item object, if it exists (0 if it doesn't exist).
 * - description:      The description of this menu item.
 * - menu_item_parent: The DB ID of the nav_menu_item that is this item's menu parent, if any. 0 otherwise.
 * - object:           The type of object originally represented, such as 'category', 'post', or 'attachment'.
 * - object_id:        The DB ID of the original object this menu item represents, e.g. ID for posts and term_id for categories.
 * - post_parent:      The DB ID of the original object's parent object, if any (0 otherwise).
 * - post_title:       A "no title" label if menu item represents a post that lacks a title.
 * - target:           The target attribute of the link element for this menu item.
 * - title:            The title of this menu item.
 * - type:             The family of objects originally represented, such as 'post_type' or 'taxonomy'.
 * - type_label:       The singular label used to describe this type of menu item.
 * - url:              The URL to which this menu item points.
 * - xfn:              The XFN relationship expressed in the link of this menu item.
 * - _invalid:         Whether the menu item represents an object that no longer exists.
 *
 * @since 3.0.0
 *
 * @param object $menu_item The menu item to modify.
 * @return object The menu item with standard menu item properties.
 */
function wp_setup_nav_menu_item( $menu_item ) {

	/**
	 * Filters whether to short-circuit the wp_setup_nav_menu_item() output.
	 *
	 * Returning a non-null value from the filter will short-circuit wp_setup_nav_menu_item(),
	 * returning that value instead.
	 *
	 * @since 6.3.0
	 *
	 * @param object|null $modified_menu_item Modified menu item. Default null.
	 * @param object      $menu_item          The menu item to modify.
	 */
	$pre_menu_item = apply_filters( 'pre_wp_setup_nav_menu_item', null, $menu_item );

	if ( null !== $pre_menu_item ) {
		return $pre_menu_item;
	}

	if ( isset( $menu_item->post_type ) ) {
		if ( 'nav_menu_item' === $menu_item->post_type ) {
			$menu_item->db_id            = (int) $menu_item->ID;
			$menu_item->menu_item_parent = ! isset( $menu_item->menu_item_parent ) ? get_post_meta( $menu_item->ID, '_menu_item_menu_item_parent', true ) : $menu_item->menu_item_parent;
			$menu_item->object_id        = ! isset( $menu_item->object_id ) ? get_post_meta( $menu_item->ID, '_menu_item_object_id', true ) : $menu_item->object_id;
			$menu_item->object           = ! isset( $menu_item->object ) ? get_post_meta( $menu_item->ID, '_menu_item_object', true ) : $menu_item->object;
			$menu_item->type             = ! isset( $menu_item->type ) ? get_post_meta( $menu_item->ID, '_menu_item_type', true ) : $menu_item->type;

			if ( 'post_type' === $menu_item->type ) {
				$object = get_post_type_object( $menu_item->object );
				if ( $object ) {
					$menu_item->type_label = $object->labels->singular_name;
					// Denote post states for special pages (only in the admin).
					if ( function_exists( 'get_post_states' ) ) {
						$menu_post   = get_post( $menu_item->object_id );
						$post_states = get_post_states( $menu_post );
						if ( $post_states ) {
							$menu_item->type_label = wp_strip_all_tags( implode( ', ', $post_states ) );
						}
					}
				} else {
					$menu_item->type_label = $menu_item->object;
					$menu_item->_invalid   = true;
				}

				if ( 'trash' === get_post_status( $menu_item->object_id ) ) {
					$menu_item->_invalid = true;
				}

				$original_object = get_post( $menu_item->object_id );

				if ( $original_object ) {
					$menu_item->url = get_permalink( $original_object->ID );
					/** This filter is documented in wp-includes/post-template.php */
					$original_title = apply_filters( 'the_title', $original_object->post_title, $original_object->ID );
				} else {
					$menu_item->url      = '';
					$original_title      = '';
					$menu_item->_invalid = true;
				}

				if ( '' === $original_title ) {
					/* translators: %d: ID of a post. */
					$original_title = sprintf( __( '#%d (no title)' ), $menu_item->object_id );
				}

				$menu_item->title = ( '' === $menu_item->post_title ) ? $original_title : $menu_item->post_title;

			} elseif ( 'post_type_archive' === $menu_item->type ) {
				$object = get_post_type_object( $menu_item->object );
				if ( $object ) {
					$menu_item->title      = ( '' === $menu_item->post_title ) ? $object->labels->archives : $menu_item->post_title;
					$post_type_description = $object->description;
				} else {
					$post_type_description = '';
					$menu_item->_invalid   = true;
				}

				$menu_item->type_label = __( 'Post Type Archive' );
				$post_content          = wp_trim_words( $menu_item->post_content, 200 );
				$post_type_description = ( '' === $post_content ) ? $post_type_description : $post_content;
				$menu_item->url        = get_post_type_archive_link( $menu_item->object );

			} elseif ( 'taxonomy' === $menu_item->type ) {
				$object = get_taxonomy( $menu_item->object );
				if ( $object ) {
					$menu_item->type_label = $object->labels->singular_name;
				} else {
					$menu_item->type_label = $menu_item->object;
					$menu_item->_invalid   = true;
				}

				$original_object = get_term( (int) $menu_item->object_id, $menu_item->object );

				if ( $original_object && ! is_wp_error( $original_object ) ) {
					$menu_item->url = get_term_link( (int) $menu_item->object_id, $menu_item->object );
					$original_title = $original_object->name;
				} else {
					$menu_item->url      = '';
					$original_title      = '';
					$menu_item->_invalid = true;
				}

				if ( '' === $original_title ) {
					/* translators: %d: ID of a term. */
					$original_title = sprintf( __( '#%d (no title)' ), $menu_item->object_id );
				}

				$menu_item->title = ( '' === $menu_item->post_title ) ? $original_title : $menu_item->post_title;

			} else {
				$menu_item->type_label = __( 'Custom Link' );
				$menu_item->title      = $menu_item->post_title;
				$menu_item->url        = ! isset( $menu_item->url ) ? get_post_meta( $menu_item->ID, '_menu_item_url', true ) : $menu_item->url;
			}

			$menu_item->target = ! isset( $menu_item->target ) ? get_post_meta( $menu_item->ID, '_menu_item_target', true ) : $menu_item->target;

			/**
			 * Filters a navigation menu item's title attribute.
			 *
			 * @since 3.0.0
			 *
			 * @param string $item_title The menu item title attribute.
			 */
			$menu_item->attr_title = ! isset( $menu_item->attr_title ) ? apply_filters( 'nav_menu_attr_title', $menu_item->post_excerpt ) : $menu_item->attr_title;

			if ( ! isset( $menu_item->description ) ) {
				/**
				 * Filters a navigation menu item's description.
				 *
				 * @since 3.0.0
				 *
				 * @param string $description The menu item description.
				 */
				$menu_item->description = apply_filters( 'nav_menu_description', wp_trim_words( $menu_item->post_content, 200 ) );
			}

			$menu_item->classes = ! isset( $menu_item->classes ) ? (array) get_post_meta( $menu_item->ID, '_menu_item_classes', true ) : $menu_item->classes;
			$menu_item->xfn     = ! isset( $menu_item->xfn ) ? get_post_meta( $menu_item->ID, '_menu_item_xfn', true ) : $menu_item->xfn;
		} else {
			$menu_item->db_id            = 0;
			$menu_item->menu_item_parent = 0;
			$menu_item->object_id        = (int) $menu_item->ID;
			$menu_item->type             = 'post_type';

			$object                = get_post_type_object( $menu_item->post_type );
			$menu_item->object     = $object->name;
			$menu_item->type_label = $object->labels->singular_name;

			if ( '' === $menu_item->post_title ) {
				/* translators: %d: ID of a post. */
				$menu_item->post_title = sprintf( __( '#%d (no title)' ), $menu_item->ID );
			}

			$menu_item->title  = $menu_item->post_title;
			$menu_item->url    = get_permalink( $menu_item->ID );
			$menu_item->target = '';

			/** This filter is documented in wp-includes/nav-menu.php */
			$menu_item->attr_title = apply_filters( 'nav_menu_attr_title', '' );

			/** This filter is documented in wp-includes/nav-menu.php */
			$menu_item->description = apply_filters( 'nav_menu_description', '' );
			$menu_item->classes     = array();
			$menu_item->xfn         = '';
		}
	} elseif ( isset( $menu_item->taxonomy ) ) {
		$menu_item->ID               = $menu_item->term_id;
		$menu_item->db_id            = 0;
		$menu_item->menu_item_parent = 0;
		$menu_item->object_id        = (int) $menu_item->term_id;
		$menu_item->post_parent      = (int) $menu_item->parent;
		$menu_item->type             = 'taxonomy';

		$object                = get_taxonomy( $menu_item->taxonomy );
		$menu_item->object     = $object->name;
		$menu_item->type_label = $object->labels->singular_name;

		$menu_item->title       = $menu_item->name;
		$menu_item->url         = get_term_link( $menu_item, $menu_item->taxonomy );
		$menu_item->target      = '';
		$menu_item->attr_title  = '';
		$menu_item->description = get_term_field( 'description', $menu_item->term_id, $menu_item->taxonomy );
		$menu_item->classes     = array();
		$menu_item->xfn         = '';

	}

	/**
	 * Filters a navigation menu item object.
	 *
	 * @since 3.0.0
	 *
	 * @param object $menu_item The menu item object.
	 */
	return apply_filters( 'wp_setup_nav_menu_item', $menu_item );
}

/**
 * Returns the menu items associated with a particular object.
 *
 * @since 3.0.0
 *
 * @param int    $object_id   Optional. The ID of the original object. Default 0.
 * @param string $object_type Optional. The type of object, such as 'post_type' or 'taxonomy'.
 *                            Default 'post_type'.
 * @param string $taxonomy    Optional. If $object_type is 'taxonomy', $taxonomy is the name
 *                            of the tax that $object_id belongs to. Default empty.
 * @return int[] The array of menu item IDs; empty array if none.
 */
function wp_get_associated_nav_menu_items( $object_id = 0, $object_type = 'post_type', $taxonomy = '' ) {
	$object_id     = (int) $object_id;
	$menu_item_ids = array();

	$query      = new WP_Query();
	$menu_items = $query->query(
		array(
			'meta_key'       => '_menu_item_object_id',
			'meta_value'     => $object_id,
			'post_status'    => 'any',
			'post_type'      => 'nav_menu_item',
			'posts_per_page' => -1,
		)
	);
	foreach ( (array) $menu_items as $menu_item ) {
		if ( isset( $menu_item->ID ) && is_nav_menu_item( $menu_item->ID ) ) {
			$menu_item_type = get_post_meta( $menu_item->ID, '_menu_item_type', true );
			if (
				'post_type' === $object_type &&
				'post_type' === $menu_item_type
			) {
				$menu_item_ids[] = (int) $menu_item->ID;
			} elseif (
				'taxonomy' === $object_type &&
				'taxonomy' === $menu_item_type &&
				get_post_meta( $menu_item->ID, '_menu_item_object', true ) === $taxonomy
			) {
				$menu_item_ids[] = (int) $menu_item->ID;
			}
		}
	}

	return array_unique( $menu_item_ids );
}

/**
 * Callback for handling a menu item when its original object is deleted.
 *
 * @since 3.0.0
 * @access private
 *
 * @param int $object_id The ID of the original object being trashed.
 */
function _wp_delete_post_menu_item( $object_id ) {
	$object_id = (int) $object_id;

	$menu_item_ids = wp_get_associated_nav_menu_items( $object_id, 'post_type' );

	foreach ( (array) $menu_item_ids as $menu_item_id ) {
		wp_delete_post( $menu_item_id, true );
	}
}

/**
 * Serves as a callback for handling a menu item when its original object is deleted.
 *
 * @since 3.0.0
 * @access private
 *
 * @param int    $object_id The ID of the original object being trashed.
 * @param int    $tt_id     Term taxonomy ID. Unused.
 * @param string $taxonomy  Taxonomy slug.
 */
function _wp_delete_tax_menu_item( $object_id, $tt_id, $taxonomy ) {
	$object_id = (int) $object_id;

	$menu_item_ids = wp_get_associated_nav_menu_items( $object_id, 'taxonomy', $taxonomy );

	foreach ( (array) $menu_item_ids as $menu_item_id ) {
		wp_delete_post( $menu_item_id, true );
	}
}

/**
 * Automatically add newly published page objects to menus with that as an option.
 *
 * @since 3.0.0
 * @access private
 *
 * @param string  $new_status The new status of the post object.
 * @param string  $old_status The old status of the post object.
 * @param WP_Post $post       The post object being transitioned from one status to another.
 */
function _wp_auto_add_pages_to_menu( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status || 'page' !== $post->post_type ) {
		return;
	}
	if ( ! empty( $post->post_parent ) ) {
		return;
	}
	$auto_add = get_option( 'nav_menu_options' );
	if ( empty( $auto_add ) || ! is_array( $auto_add ) || ! isset( $auto_add['auto_add'] ) ) {
		return;
	}
	$auto_add = $auto_add['auto_add'];
	if ( empty( $auto_add ) || ! is_array( $auto_add ) ) {
		return;
	}

	$args = array(
		'menu-item-object-id' => $post->ID,
		'menu-item-object'    => $post->post_type,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
	);

	foreach ( $auto_add as $menu_id ) {
		$items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish,draft' ) );
		if ( ! is_array( $items ) ) {
			continue;
		}
		foreach ( $items as $item ) {
			if ( $post->ID === (int) $item->object_id ) {
				continue 2;
			}
		}
		wp_update_nav_menu_item( $menu_id, 0, $args );
	}
}

/**
 * Deletes auto-draft posts associated with the supplied changeset.
 *
 * @since 4.8.0
 * @access private
 *
 * @param int $post_id Post ID for the customize_changeset.
 */
function _wp_delete_customize_changeset_dependent_auto_drafts( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post || 'customize_changeset' !== $post->post_type ) {
		return;
	}

	$data = json_decode( $post->post_content, true );
	if ( empty( $data['nav_menus_created_posts']['value'] ) ) {
		return;
	}
	remove_action( 'delete_post', '_wp_delete_customize_changeset_dependent_auto_drafts' );
	foreach ( $data['nav_menus_created_posts']['value'] as $stub_post_id ) {
		if ( empty( $stub_post_id ) ) {
			continue;
		}
		if ( 'auto-draft' === get_post_status( $stub_post_id ) ) {
			wp_delete_post( $stub_post_id, true );
		} elseif ( 'draft' === get_post_status( $stub_post_id ) ) {
			wp_trash_post( $stub_post_id );
			delete_post_meta( $stub_post_id, '_customize_changeset_uuid' );
		}
	}
	add_action( 'delete_post', '_wp_delete_customize_changeset_dependent_auto_drafts' );
}

/**
 * Handles menu config after theme change.
 *
 * @access private
 * @since 4.9.0
 */
function _wp_menus_changed() {
	$old_nav_menu_locations    = get_option( 'theme_switch_menu_locations', array() );
	$new_nav_menu_locations    = get_nav_menu_locations();
	$mapped_nav_menu_locations = wp_map_nav_menu_locations( $new_nav_menu_locations, $old_nav_menu_locations );

	set_theme_mod( 'nav_menu_locations', $mapped_nav_menu_locations );
	delete_option( 'theme_switch_menu_locations' );
}

/**
 * Maps nav menu locations according to assignments in previously active theme.
 *
 * @since 4.9.0
 *
 * @param array $new_nav_menu_locations New nav menu locations assignments.
 * @param array $old_nav_menu_locations Old nav menu locations assignments.
 * @return array Nav menus mapped to new nav menu locations.
 */
function wp_map_nav_menu_locations( $new_nav_menu_locations, $old_nav_menu_locations ) {
	$registered_nav_menus   = get_registered_nav_menus();
	$new_nav_menu_locations = array_intersect_key( $new_nav_menu_locations, $registered_nav_menus );

	// Short-circuit if there are no old nav menu location assignments to map.
	if ( empty( $old_nav_menu_locations ) ) {
		return $new_nav_menu_locations;
	}

	// If old and new theme have just one location, map it and we're done.
	if ( 1 === count( $old_nav_menu_locations ) && 1 === count( $registered_nav_menus ) ) {
		$new_nav_menu_locations[ key( $registered_nav_menus ) ] = array_pop( $old_nav_menu_locations );
		return $new_nav_menu_locations;
	}

	$old_locations = array_keys( $old_nav_menu_locations );

	// Map locations with the same slug.
	foreach ( $registered_nav_menus as $location => $name ) {
		if ( in_array( $location, $old_locations, true ) ) {
			$new_nav_menu_locations[ $location ] = $old_nav_menu_locations[ $location ];
			unset( $old_nav_menu_locations[ $location ] );
		}
	}

	// If there are no old nav menu locations left, then we're done.
	if ( empty( $old_nav_menu_locations ) ) {
		return $new_nav_menu_locations;
	}

	/*
	 * If old and new theme both have locations that contain phrases
	 * from within the same group, make an educated guess and map it.
	 */
	$common_slug_groups = array(
		array( 'primary', 'menu-1', 'main', 'header', 'navigation', 'top' ),
		array( 'secondary', 'menu-2', 'footer', 'subsidiary', 'bottom' ),
		array( 'social' ),
	);

	// Go through each group...
	foreach ( $common_slug_groups as $slug_group ) {

		// ...and see if any of these slugs...
		foreach ( $slug_group as $slug ) {

			// ...and any of the new menu locations...
			foreach ( $registered_nav_menus as $new_location => $name ) {

				// ...actually match!
				if ( is_string( $new_location ) && false === stripos( $new_location, $slug ) && false === stripos( $slug, $new_location ) ) {
					continue;
				} elseif ( is_numeric( $new_location ) && $new_location !== $slug ) {
					continue;
				}

				// Then see if any of the old locations...
				foreach ( $old_nav_menu_locations as $location => $menu_id ) {

					// ...and any slug in the same group...
					foreach ( $slug_group as $slug ) {

						// ... have a match as well.
						if ( is_string( $location ) && false === stripos( $location, $slug ) && false === stripos( $slug, $location ) ) {
							continue;
						} elseif ( is_numeric( $location ) && $location !== $slug ) {
							continue;
						}

						// Make sure this location wasn't mapped and removed previously.
						if ( ! empty( $old_nav_menu_locations[ $location ] ) ) {

							// We have a match that can be mapped!
							$new_nav_menu_locations[ $new_location ] = $old_nav_menu_locations[ $location ];

							// Remove the mapped location so it can't be mapped again.
							unset( $old_nav_menu_locations[ $location ] );

							// Go back and check the next new menu location.
							continue 3;
						}
					} // End foreach ( $slug_group as $slug ).
				} // End foreach ( $old_nav_menu_locations as $location => $menu_id ).
			} // End foreach foreach ( $registered_nav_menus as $new_location => $name ).
		} // End foreach ( $slug_group as $slug ).
	} // End foreach ( $common_slug_groups as $slug_group ).

	return $new_nav_menu_locations;
}

/**
 * Prevents menu items from being their own parent.
 *
 * Resets menu_item_parent to 0 when the parent is set to the item itself.
 * For use before saving `_menu_item_menu_item_parent` in nav-menus.php.
 *
 * @since 6.2.0
 * @access private
 *
 * @param array $menu_item_data The menu item data array.
 * @return array The menu item data with reset menu_item_parent.
 */
function _wp_reset_invalid_menu_item_parent( $menu_item_data ) {
	if ( ! is_array( $menu_item_data ) ) {
		return $menu_item_data;
	}

	if (
		! empty( $menu_item_data['ID'] ) &&
		! empty( $menu_item_data['menu_item_parent'] ) &&
		(int) $menu_item_data['ID'] === (int) $menu_item_data['menu_item_parent']
	) {
		$menu_item_data['menu_item_parent'] = 0;
	}

	return $menu_item_data;
}
